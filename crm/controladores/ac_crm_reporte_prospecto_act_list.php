<?php 
function buscarCliente($frmBusCliente, $frmSeguimiento) {
	$objResponse = new xajaxResponse();
	
	if($frmBusCliente['lstTipoCuentaCliente'] == "-1"){
		$objResponse->script("byId('lstTipoCuentaCliente').className = 'inputErrado';");
		return $objResponse->alert("Debe Seleccionar el tipo de Cliente");
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmSeguimiento['txtIdEmpresa'],
		$frmBusCliente['lstTipoPago'],
		$frmBusCliente['lstEstatusBuscar'],
		$frmBusCliente['lstPagaImpuesto'],
		$frmBusCliente['lstTipoCuentaCliente'],
		$frmBusCliente['txtCriterio']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarSeguimiento($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['listVendedorEquipo'],
		$frmBuscar['textDesdeCreacion'],
		$frmBuscar['textHastaCreacion'],
		$frmBuscar['textCriterio']);
	
	$objResponse->loadCommands(lstSeguimiento(0, "seguimiento.id_seguimiento","ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstEquipo($idEmpresa, $selId = "", $tipo = "Ventas") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT id_equipo, nombre_equipo FROM crm_equipo 
		WHERE activo = %s AND tipo_equipo = %s AND id_empresa = %s",
	valTpDato(1,"int"),
	valTpDato($tipo,"text"),
	valTpDato($idEmpresa,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEquipo\" name=\"lstEquipo\" class=\"inputHabilitado\" style=\"width:150px\" onchange=\"xajax_insertarIntegrante(this.value);\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_equipo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_equipo']."\">".utf8_encode($row['nombre_equipo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdTipoEquipo","innerHTML","Equipo de ".$tipo);
	$objResponse->assign("tdLstEquipo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstVendedor($idEmpresa = ""){
	$objResponse = new xajaxResponse();
	
	if($idEmpresa != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_equipo.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("crm_equipo.activo = %s",
		valTpDato(1, "int"));
				
	$arrayClaveFiltro = claveFiltroEmpleado();
	if($arrayClaveFiltro[0] == true){//CONDICION TIPO DE EQUIPO
		if($arrayClaveFiltro[1] == 1 || $arrayClaveFiltro[1] == 2){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("crm_equipo.tipo_equipo = %s",
				valTpDato($arrayClaveFiltro[2], "int"));	
		}
		
	}else{
		$objResponse->alert($arrayClaveFiltro[1]);	
	}
	//CONSULTA LOS EQUIPO 
	$queryEquipo = sprintf("SELECT  distinct crm_equipo.id_equipo,
			nombre_equipo,
			tipo_equipo,
			crm_equipo.activo,
			crm_equipo.id_empresa 
		FROM crm_equipo 
		INNER JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_equipo = crm_equipo.id_equipo
		%s ORDER BY id_equipo ASC",$sqlBusq);
	$rsEquipo = mysql_query($queryEquipo);
	if(!$rsEquipo) return $objResponse->alert(mysql_error."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rsNumEquipo = mysql_num_rows($rsEquipo);
	$count = 0;
	while($rowEuipo = mysql_fetch_array($rsEquipo)){
		$htmlOption .= sprintf("<optgroup label=\"%s\">","Equipo - ".$rowEuipo['nombre_equipo']);

		//CONSULTA LOS EMPLEAO POR EQUIPO
		$queryEmp = sprintf("SELECT 
				vw_pg_empleado.id_empleado,
				vw_pg_empleado.nombre_empleado,
				vw_pg_empleado.nombre_cargo,
				vw_pg_empleado.clave_filtro,
				vw_pg_empleado.activo,
				crm_integrantes_equipos.id_equipo,
				crm_integrantes_equipos.activo
			FROM vw_pg_empleados vw_pg_empleado
				LEFT JOIN crm_integrantes_equipos ON  vw_pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado   
			WHERE crm_integrantes_equipos.activo = %s AND crm_integrantes_equipos.id_equipo = %s
			ORDER BY crm_integrantes_equipos.id_equipo DESC",
		valTpDato(1, "int"),
		valTpDato($rowEuipo['id_equipo'], "int"));
		$rsEmp = mysql_query($queryEmp);
		if(!$rsEmp)return $objResponse->alert(mysql_error."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rsNumEmp = mysql_num_rows($rsEmp);
		$count = 0;
		while($rowEmp =  mysql_fetch_array($rsEmp)){
			$count ++;
			$htmlOption .= sprintf("<option value=\"%s\">%s.-  %s</option>",
				$rowEmp['id_empleado'],$count,utf8_encode($rowEmp['nombre_empleado']));
		}
		
		$htmlOption .= "</optgroup>";
	}
	
	$html = "<select id=\"listVendedorEquipo\" name=\"listVendedorEquipo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Selecione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdLstVendedor","innerHTML",$html);
	return $objResponse;
}

function listaActSegEncabezado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("actividad_seguimiento = %s",
		valTpDato(1,"int"));
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = %s",
		valTpDato(1,"int"));

	$query = sprintf("SELECT * FROM crm_actividad %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTb = "<table border=\"0\" width=\"100%\" class=\"divGris\">";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		$htmlTb .= (fmod($contFila, 4) == 1) ? "<tr align=\"center\">" : "";
		
		$htmlTb .= sprintf("<td width=\"%s\" align=\"center\" title=\"%s\">%s</td>",
			(100 / $totalRows)."%",utf8_encode($row['nombre_actividad']),utf8_encode($row['nombre_actividad_abreviatura']));
		$htmlTb .= (fmod($contFila, 4) == 0) ? "</tr>" : "";
	}

	$htmlTb .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td>";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td align=\"center\">No Tiene Actividad de Seguimiento Configuradas</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdActividadLstEncabezado","innerHTML",$htmlTb);
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	global $spanClienteCxC;
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÃ‰DITO");

	$objResponse->call("selectedOption","lstTipoCuentaCliente",$valCadBusq[4]);
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa LIKE %s",
			valTpDato($valCadBusq[0], "text"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("credito LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("status LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("paga_impuesto = %s ",
			valTpDato($valCadBusq[3], "boolean"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		if ($valCadBusq[4] == 1) {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0
			AND tipo_cuenta_cliente = 1)");
		} else if ($valCadBusq[4] == 2) {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) = 0
			AND tipo_cuenta_cliente = 2)");
		} else {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0)
			AND tipo_cuenta_cliente = 2");
		}
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', nombre, apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR perfil_prospecto.compania LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente.id,
		cliente.tipo,
		cliente.nombre,
		cliente.apellido,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		cliente.telf,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		bloquea_venta,
		paga_impuesto,
		tipo_cuenta_cliente,
		perfil_prospecto.compania,
		(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos
	FROM cj_cc_cliente cliente
		LEFT JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
//$objResponse->alert($queryLimit);	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, $spanClienteCxC);
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "apellido", $campOrd, $tpOrd, $valBusq, $maxRows, "Apellido");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "TelÃ©fono");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "compania", $campOrd, $tpOrd, $valBusq, $maxRows, "CompaÃ±ia");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "cantidad_modelos", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant. Modelos");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "paga_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Paga Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['status']) {
			case "Inactivo" : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case "Activo" : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>%s</td>",$imgEstatus);
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['telf'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['compania'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_modelos']."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['paga_impuesto'] == 1) ? "SI" : "NO")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
			$htmlTb .= sprintf("<td><button type=\"button\" id=\"btnCliente\" name=\"btnCliente\" title=\"Listar\" onclick=\"xajax_cargarDatos('',%s); byId('btnCerraCliente').click();\"><img src=\"../img/iconos/tick.png\"/></button></td>",$row['id']);
			//$row['id']
				
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
	
	// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo,
        id_empresa		
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanCI));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		//asignarVendedor($idVEndedor, $idEmpresa)
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button type=\"button\" onclick=\"xajax_asignarEmpleado(%s,%s);byId('btnCerrarEmpleado').click();\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button></td>",$row['id_empleado'],$row['id_empresa']);
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListEmpleado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_empresa_reg <> 100");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "8%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanRIF));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "33%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "33%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarEmpresa%s\" onclick=\"xajax_asignarEmpresa(%s); byId('btnCerrarEmp').click();\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_empresa_reg']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($nombreSucursal)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function lstSeguimiento($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	global $spanClienteCxC;

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seguimiento.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	$arrayClave = claveFiltroEmpleado();
	if($arrayClave[0] == true){
		if($arrayClave[1] == 1 || $arrayClave[1] == 2){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(seguimiento.id_empleado_creador = %s OR id_empleado_vendedor = %s 
					OR (SELECT jefe_equipo FROM crm_equipo equipo WHERE activo = %s AND jefe_equipo = %s LIMIT %s))",
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato(1,"int"),
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			valTpDato(1,"int"));
		}
	}else{
		$objResponse->alert($arrayClave[1]);
	}

	if($valCadBusq[2] != "-1" && $valCadBusq[2] != "" && $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"));
	}
	
	if($valCadBusq[1] != "-1" && $valCadBusq[1]) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seguimiento_diario.id_empleado_vendedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}

	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("observacion_seguimiento LIKE %s
		OR CONCAT_WS(' ',cliente.nombre, cliente.apellido) LIKE %s
		OR ((SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
					WHERE empleado.id_empleado = seguimiento_diario. id_empleado_vendedor) IN (
								SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
										WHERE empleado.id_empleado = seguimiento_diario.id_empleado_vendedor AND 
											CONCAT_WS(' ',nombre_empleado, empleado.apellido) LIKE %s))
		OR ((SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
				INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
			WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}

	$query = sprintf("SELECT DISTINCT
		seguimiento_diario.id_empleado_vendedor,
		(SELECT CONCAT_WS(' ',nombre_empleado, pg_empleado.apellido) FROM pg_empleado 
				WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS nombre_vendedor,
		(SELECT pg_empleado.cedula FROM pg_empleado 
				WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS ci_vendedor
	FROM crm_seguimiento seguimiento 
		INNER JOIN cj_cc_cliente cliente ON cliente.id = seguimiento.id_cliente
		INNER JOIN crm_seguimiento_diario seguimiento_diario ON seguimiento_diario.id_seguimiento = seguimiento.id_seguimiento
		INNER JOIN crm_perfil_prospecto perfil_prospecto ON cliente.id = perfil_prospecto.id   
		LEFT JOIN crm_posibilidad_cierre posibilidad_cierre ON posibilidad_cierre.id_posibilidad_cierre = perfil_prospecto.id_posibilidad_cierre
		LEFT JOIN crm_equipo equipo ON equipo.id_equipo = seguimiento_diario.id_equipo
		INNER JOIN grupositems ON grupositems.idItem = (SELECT id_medio FROM an_prospecto_vehiculo
															INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
														WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
		INNER JOIN pg_empleado empleado ON empleado.id_empleado = seguimiento.id_empleado_creador
	%s",
	 $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("seguimiento_diario.id_empleado_vendedor = %s",
				valTpDato($row['id_empleado_vendedor'], "int"));
		
		if ($totalRows > 0 && $row['nombre_vendedor'] != '') {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">"."Vendedor:"."</td>";
				$htmlTb .= "<td colspan=\"11\">".utf8_encode($row['nombre_vendedor'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">C.I.:</td>";
				$htmlTb .= "<td colspan=\"11\">".utf8_encode($row['ci_vendedor'])."</td>";
			$htmlTb .= "</tr>";
			
			$htmlTb .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTb .= "<td style=\"min-width:2px\">Id</td>";
				$htmlTb .= "<td width=\"22%\">Nombre Cliente</td>";
				$htmlTb .= "<td width=\"22%\">Seguimiento Creado Por</td>";
				$htmlTb .= "<td width=\"22%\">Fecha de Asignación</td>";
				$htmlTb .= "<td width=\"22%\">Estatus</td>";
			$htmlTb .= "</tr>";
			
			$queryDet = sprintf("SELECT
				seguimiento.id_seguimiento, seguimiento.id_cliente, seguimiento.id_empleado_creador, seguimiento.id_empleado_actualiza, seguimiento.id_empresa, seguimiento.observacion_seguimiento,
				CONCAT_WS(' ',cliente.nombre, cliente.apellido) AS nombre_cliente,
				seguimiento_diario.id_seguimiento_diario, seguimiento_diario.id_equipo, equipo.jefe_equipo, seguimiento_diario.id_empleado_vendedor, fecha_registro, fecha_asignacion_vendedor,
				perfil_prospecto.id_perfil_prospecto, perfil_prospecto.id_posibilidad_cierre, fechaProximaEntrevista,
				posibilidad_cierre.nombre_posibilidad_cierre, img_posibilidad_cierre,
				grupositems.item,
				(SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
							INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
						WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) AS nom_uni_bas,
			
					(SELECT precio_unidad_basica  FROM an_prospecto_vehiculo prospecto_vehiculo
							INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
						WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) AS precio_unidad_basica,
			
					IFNULL((SELECT COUNT(an_unidad_fisica.id_uni_bas) FROM an_unidad_fisica
						WHERE  an_unidad_fisica.id_uni_bas = (SELECT an_uni_bas.id_uni_bas FROM an_prospecto_vehiculo
																	INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
																WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
							AND estado_venta = 'DISPONIBLE'
						GROUP BY an_unidad_fisica.id_uni_bas ), 0) AS disponible_unidad_fisica,
			
				IFNULL((SELECT uni_bas.nom_uni_bas
							FROM an_tradein tradein
						INNER JOIN cj_cc_anticipo cxc_ant ON tradein.id_anticipo = cxc_ant.idAnticipo
						INNER JOIN an_unidad_fisica uni_fis ON tradein.id_unidad_fisica = uni_fis.id_unidad_fisica
						INNER JOIN an_uni_bas uni_bas ON uni_fis.id_uni_bas = uni_bas.id_uni_bas
							WHERE cxc_ant.idCliente = seguimiento.id_cliente), '-') AS tradeIn,
			
				CONCAT_WS(' ',empleado.nombre_empleado, empleado.apellido) AS nombre_usuario_creador,
				(SELECT CONCAT_WS(' ',nombre_empleado, pg_empleado.apellido) FROM pg_empleado
						WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS nombre_vendedor,
				(SELECT CONCAT_WS(' ',nombre_empleado, pg_empleado.apellido) FROM pg_empleado
					WHERE pg_empleado.id_empleado = seguimiento.id_empleado_creador) AS nombre_creador
			FROM crm_seguimiento seguimiento
				INNER JOIN cj_cc_cliente cliente ON cliente.id = seguimiento.id_cliente
				INNER JOIN crm_seguimiento_diario seguimiento_diario ON seguimiento_diario.id_seguimiento = seguimiento.id_seguimiento
				INNER JOIN crm_perfil_prospecto perfil_prospecto ON cliente.id = perfil_prospecto.id
				LEFT JOIN crm_posibilidad_cierre posibilidad_cierre ON posibilidad_cierre.id_posibilidad_cierre = perfil_prospecto.id_posibilidad_cierre
				LEFT JOIN crm_equipo equipo ON equipo.id_equipo = seguimiento_diario.id_equipo
				INNER JOIN grupositems ON grupositems.idItem = (SELECT id_medio FROM an_prospecto_vehiculo
																	INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
																WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
				INNER JOIN pg_empleado empleado ON empleado.id_empleado = seguimiento.id_empleado_creador
			%s %s",
					$sqlBusq, $sqlBusq2);
			
			$rsLimitDet = mysql_query($queryDet);
			if (!$rsLimitDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryDet);
			$totalRowsDet = mysql_num_rows($rsLimitDet);
			
			$contFila2 = 0;
			
			while ($rowDet = mysql_fetch_assoc($rsLimitDet)) {
				if($rowDet['nombre_vendedor'] != ''){
					$clase2 = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila2++;
						
					if($rowDet['nombre_vendedor'] == ''){
						$seguimiento = 'NO';
					} else{
						$seguimiento = 'SI';
					}
						
					$date = new DateTime($rowDet['fecha_registro']);
					$fecha_reg =  $date->format('d-m-Y');
					
					$htmlTb .= "<tr class=\"".$clase2."\" height=\"24\">";
						$htmlTb .= sprintf("<td style=\"min-width:2px\">%s</td>",
								$rowDet['id_seguimiento']);
						$htmlTb .= "<td width=\"22%\">".utf8_encode($rowDet['nombre_cliente'])."</td>";
						$htmlTb .= "<td width=\"22%\">".utf8_encode($rowDet['nombre_creador'])."</td>";
						$htmlTb .= "<td width=\"20%\">".utf8_encode($fecha_reg)."</td>";
						$htmlTb .= "<td width=\"22%\">".utf8_encode($rowDet['nombre_posibilidad_cierre'])."</td>";
					$htmlTb .= "</tr>";
				}
			}
			if($totalRowsDet > 0){
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"2\">Total de clientes:</td>
								<td>".number_format($totalRowsDet, 2, ".", ",")."</td>
								<td></td>
								<td></td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr><td><br/></td></tr>";
			}

		}
		
		if($row['nombre_vendedor'] == ''){ 
			$totalRows--;
			$contFila--;
		}
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_lstSeguimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLstSeguimiento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->loadCommands(listaActSegEncabezado(0,"","",$valCadBusq[0]));
	return $objResponse;
}
function exportarCliente($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s",
			$frmBuscar['lstEmpresa'],
			$frmBuscar['listVendedorEquipo'],
			$frmBuscar['textDesdeCreacion'],
			$frmBuscar['textHastaCreacion'],
			$frmBuscar['textCriterio']);

	$objResponse->script("window.open('reportes/crm_vendedor_excel.php?valBusq=".$valBusq."','_self');");

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarSeguimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"lstSeguimiento");
$xajax->register(XAJAX_FUNCTION,"exportarCliente");

function claveFiltroEmpleado(){
	
	//AVERIGUAR VENTA O POSTVENTA
	$queryUsuario = sprintf("SELECT id_empleado,
	CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado,
    clave_filtro,
		(CASE clave_filtro
			  WHEN 1 THEN 'Ventas'		
              WHEN 2 THEN 'Ventas'
			  WHEN 4 THEN 'Postventa'
              WHEN 5 THEN 'Postventa'
              WHEN 6 THEN 'Postventa'
              WHEN 7 THEN 'Postventa'
              WHEN 8 THEN 'Postventa'
              WHEN 26 THEN 'Postventa'
              WHEN 400 THEN 'Postventa'
		END) AS tipo
        
	FROM pg_empleado 
		INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
	WHERE id_empleado = %s ",
	valTpDato($_SESSION['idEmpleadoSysGts'],"int"));
	
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryUsuario);
	$row = mysql_fetch_array($rsUsuario);

	return array(true, $rowClave['clave_filtro'], $row['tipo']);

}

function insertarItemModeloInteres($contFila, $idProspectoVehiculo = "", $idUnidadBasica = "", $hddPrecioUnidadBasica = "", $txtIdMedio = "", $txtIdNivelInteres = "", $txtIdPlanPago = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idProspectoVehiculo > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryProspectoVehiculo = sprintf("SELECT 
			prospecto_veh.id_prospecto_vehiculo,
			prospecto_veh.id_cliente,
			prospecto_veh.id_unidad_basica,
			prospecto_veh.precio_unidad_basica,
			prospecto_veh.id_medio,
			prospecto_veh.id_plan_pago,
			prospecto_veh.id_nivel_interes
		FROM an_prospecto_vehiculo prospecto_veh
		WHERE prospecto_veh.id_prospecto_vehiculo = %s;",
			valTpDato($idProspectoVehiculo, "int"));
		$rsProspectoVehiculo = mysql_query($queryProspectoVehiculo);
		if (!$rsProspectoVehiculo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsProspectoVehiculo = mysql_num_rows($rsProspectoVehiculo);
		$rowProspectoVehiculo = mysql_fetch_assoc($rsProspectoVehiculo);
	}
	
	$idUnidadBasica = ($idUnidadBasica == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_unidad_basica'] : $idUnidadBasica;
	$hddPrecioUnidadBasica = ($hddPrecioUnidadBasica == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['precio_unidad_basica'] : $hddPrecioUnidadBasica;
	$txtIdMedio = ($txtIdMedio == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_medio'] : $txtIdMedio;
	$txtIdNivelInteres = ($txtIdNivelInteres == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_nivel_interes'] : $txtIdNivelInteres;
	$txtIdPlanPago = ($txtIdPlanPago == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_plan_pago'] : $txtIdPlanPago;
	
	// BUSCA LOS DATOS DE LA UNIDAD BASICA
	$query = sprintf("SELECT
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
	FROM vw_iv_modelos vw_iv_modelo
	WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// BUSCA LOS DATOS DEL MEDIO
	$query = sprintf("SELECT item AS medio FROM grupositems WHERE idItem = %s;",
		valTpDato($txtIdMedio, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowMedio = mysql_fetch_assoc($rs);
	
	// BUSCA LOS DATOS DEL PLAN DE PAGO
	$query = sprintf("SELECT item AS plan_pago FROM grupositems WHERE idItem = %s;",
		valTpDato($txtIdPlanPago, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowPlanPago = mysql_fetch_assoc($rs);
	
	switch($txtIdNivelInteres) {
		case "1" : $txtNivelInteres = "Bajo"; break;
		case "2" : $txtNivelInteres = "Medio"; break;
		case "3" : $txtNivelInteres = "Alto"; break;
	}
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieModeloInteres').before('".
		"<tr id=\"trItmModeloInteres:%s\" align=\"left\"  class=\"textoGris_11px %s\" >".
			"<td title=\"trItmModeloInteres:%s\"><input id=\"cbxItmModeloInteres\" name=\"cbxItmModeloInteres[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"hddPrecioUnidadBasica%s\" name=\"hddPrecioUnidadBasica%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdProspectoVehiculo%s\" name=\"hddIdProspectoVehiculo%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdUnidadBasica%s\" name=\"hddIdUnidadBasica%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdMedio%s\" name=\"hddIdMedio%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdNivelInteres%s\" name=\"hddIdNivelInteres%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPlanPago%s\" name=\"hddIdPlanPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['vehiculo']),
				$contFila, $contFila, number_format($hddPrecioUnidadBasica, 2, ".", ","),
			utf8_encode($rowMedio['medio']),
			utf8_encode($txtNivelInteres),
			utf8_encode($rowPlanPago['plan_pago']),
				$contFila, $contFila, $idProspectoVehiculo,
				$contFila, $contFila, $idUnidadBasica,
				$contFila, $contFila, $txtIdMedio,
				$contFila, $contFila, $txtIdNivelInteres,
				$contFila, $contFila, $txtIdPlanPago);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarActividadSeguimiento($contFila, $idActividad, $idSeguimiento){
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	$query = sprintf("SELECT 
			crm_actividad.id_actividad,
			nombre_actividad,
			tipo,
			posicion_actividad,
			id_actividad_seguimiento,
			id_seguimiento
		FROM crm_actividad 
		LEFT JOIN crm_actividad_seguimiento ON crm_actividad_seguimiento.id_actividad = crm_actividad.id_actividad
		WHERE crm_actividad.id_actividad = %s",
	valTpDato($idActividad,"int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	$query2 = sprintf("SELECT id_seguimiento FROM crm_actividad_seguimiento
		WHERE id_actividad = %s AND id_seguimiento = %s",
	valTpDato($idActividad,"int"),
	valTpDato($idSeguimiento,"int"));
	$rs2 = mysql_query($query2);
	if (!$rs2) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row2 = mysql_fetch_assoc($rs2);
	$numRs = mysql_num_rows($rs2);

	$htmlItmPie = sprintf("$('#trItmPieActividadSeguimiento').before('".
		"<tr id=\"trItmActSeguimiento:%s\" align=\"left\"  class=\"textoGris_11px %s\" >".
			"<td title=\"trItmActSeguimiento:%s\">".
				"<input id=\"cbxItmActSeguimiento\" name=\"cbxItmActSeguimiento[]\" type=\"checkbox\" value=\"%s\" %s />".
				"<input id=\"cbxItmActSeguimientoHdde\" name=\"cbxItmActSeguimientoHdde[]\" style=\"display:none\" checked=\"checked\"  type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"textActSeguimiento\" name=\"textActSeguimiento[]\" type=\"hidden\" value=\"%s\"/>".
			"</td>".	
			"<td align=\"center\" width=\"%s\">%s</td>".	
			"<td width=\"%s\">%s</td>".
			"<td width=\"%s\">%s</td>".
			"<td align=\"center\" width=\"%s\">%s</td>".
		"</tr>');",
		$contFila,$clase,
			$contFila,
				$row['id_actividad'],$check = ($numRs > 0) ? "checked=\"checked\"" : "",
				$contFila,
				$row['id_actividad_seguimiento'],
				"8%",$contFila,
				"65%",$row['nombre_actividad'],
				"25%",$row['tipo'],
				"20%",$row['posicion_actividad']);
				
	return array(true, $htmlItmPie, $contFila,$numRs."\n".$query2);
}

function itemIntegrante($contFila, $idEmpleado = "", $idEmpleadoJefe ="", $checkIdEmpleado = ""){
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;

	$query = sprintf("SELECT id_empleado, nombre_empleado, nombre_cargo,nombre_departamento
		FROM vw_pg_empleados vw_pg_empleado 
		WHERE vw_pg_empleado.id_empleado = %s",
	valTpDato($idEmpleado,"int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	$rows = mysql_fetch_array($rs);
	
	$check = ($checkIdEmpleado != "") ? "checked=\"checked\"":"";
	$jefe = ($idEmpleado == $idEmpleadoJefe) ? "<img src=\"../img/iconos/user_suit.png\" />" :"";
	$htmlItmPie = sprintf("$('#trItmIntegrante').before('".
		"<tr id=\"trItmIntegrante%s\" class=\"%s textoGris_11px remover\">".
			"<td>".
				"<input id=\"rdItemIntegrante%s\" name=\"rdItemIntegrante\" %s type=\"radio\" value=\"%s\">".
"<input type=\"checkbox\" id=\"checkHddntemIntegrante\" name=\"checkHddntemIntegrante[]\" checked=\"checked\" style=\"display:none\" value =\"%s\"/>".
				"<input id=\"hddIdEmpleado%s\" type=\"hidden\" value=\"%s\" name=\"hddIdEmpleado%s\">".
			"</td>".
			"<td class=\"textoNegrita_9px\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"center\">%s</td>".
		"</tr>')",
			$contFila,$clase,
				  $rows['id_empleado'],$check,$rows['id_empleado'],
					$contFila,
					$contFila,$rows['id_empleado'],$contFila,
				$contFila,
				utf8_encode($rows['nombre_empleado']),
				utf8_encode($rows['nombre_cargo']),
				$rows['nombre_cargo'],
				$jefe);

	return array(true, $htmlItmPie, $contFila,$query);
}
function sanear_string($string)
{

    $string = trim($string);

    $string = str_replace(
        array(/*'Ã¡',*/ 'Ã ', 'Ã¤', 'Ã¢', 'Âª', /*'Ã�',*/ 'Ã€', 'Ã‚', 'Ã„'),
        array(/*'a',*/ 'a', 'a', 'a', 'a', /*'A',*/ 'A', 'A', 'A'),
        $string
    );

    $string = str_replace(
        array(/*'Ã©',*/ 'Ã¨', 'Ã«', 'Ãª', /*'Ã‰',*/ 'Ãˆ', 'ÃŠ', 'Ã‹'),
        array(/*'e',*/ 'e', 'e', 'e', /*'E',*/ 'E', 'E', 'E'),
        $string
    );

    $string = str_replace(
        array(/*'Ã­',*/ 'Ã¬', 'Ã¯', 'Ã®', /*'Ã�',*/ 'ÃŒ', 'Ã�', 'ÃŽ'),
        array(/*'i',*/ 'i', 'i', 'i', /*'I',*/ 'I', 'I', 'I'),
        $string
    );

    $string = str_replace(
        array(/*'Ã³',*/ 'Ã²', 'Ã¶', 'Ã´', /*'Ã“',*/ 'Ã’', 'Ã–', 'Ã”'),
        array(/*'o',*/ 'o', 'o', 'o', /*'O',*/ 'O', 'O', 'O'),
        $string
    );

    $string = str_replace(
        array(/*'Ãº',*/ 'Ã¹', 'Ã¼', 'Ã»', /*'Ãš',*/ 'Ã™', 'Ã›', 'Ãœ'),
        array(/*'u',*/ 'u', 'u', 'u', /*'U',*/ 'U', 'U', 'U'),
        $string
    );

    $string = str_replace(
        array(/*'Ã±', 'Ã‘',*/ 'Ã§', 'Ã‡'),
        array(/*'n', 'N',*/ 'c', 'C',),
        $string
    );

    //Esta parte se encarga de eliminar cualquier caracter extraÃ±o
    $string = str_replace(
        array("\\", "Â¨", "Âº", "-","_", "~", "#", "@", "|", "!", "\"", "Â·", "$", "%", "&", /*"/",*/ "(", ")", "?",
		   "'","Â¡", "Â¿","[", "^", "`", "]","+", "}", "{", "Â¨", "Â´",">", "< ", ";", /*",",*/ ":","."/*, " "*/),
		' ',
        $string
    );


    return $string;
}
?>