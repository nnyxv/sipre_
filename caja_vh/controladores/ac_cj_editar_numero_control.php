<?php
function cargarPagina($idEmpresa){
	
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
		
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$objResponse->script("
		byId('trEmpresa').style.display = 'none';");
		$objResponse->script("
		byId('trSelEmpresa').style.display = '';");
		
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$objResponse->script("
		byId('trEmpresa').style.display = '';");
		$objResponse->script("
		byId('trSelEmpresa').style.display = 'none';");
	}
	
	return $objResponse;
}

function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rs = mysql_query($query) or die (mysql_error());
		$html = "<select id=\"selEmpresa\" name=\"selEmpresa\" onchange=\"xajax_cargarNumeroControl(this.value)\">";
		while ($row = mysql_fetch_assoc($rs)) {
			$nombreSucursal = "";
			if ($row['id_empresa_padre_suc'] > 0)
				$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
				
			$selected = "";
			if ($selId == $row['id_empresa_reg'] || $_SESSION['idEmpresaUsuarioSysGts'] == $row['id_empresa_reg'])
				$selected = "selected='selected'";
				
			$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
		}
	$html .= "</select>";
		
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	$objResponse->loadCommands(cargarNumeroControl($_SESSION['idEmpresaUsuarioSysGts']));
	
	return $objResponse;
}

function cargarNumeroControl($id_empresa_numeracion, $nroInicio, $nroActual, $descripcion){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	if ((!xvalidaAcceso($objResponse,"cj_editar_numero_control","editar") && in_array($idCajaPpal, array(1)))
	|| (!xvalidaAcceso($objResponse,"cjrs_editar_numero_control","editar") && in_array($idCajaPpal, array(2)))) { return $objResponse; }
	
	$objResponse->assign("txtDescripcion","value",$descripcion);
	$objResponse->assign("txtNumeroInicial","value",$nroInicio);
	$objResponse->assign("txtNumeroActual","value",$nroActual);
	$objResponse->assign("hddIdEmpresaNumeracion","value",$id_empresa_numeracion);
	
	return $objResponse;
}

function editarNumero($frmNumeroControl, $frmBuscar){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	$numeroActual = $frmNumeroControl['txtNumeroActual'];
	$hddIdEmpresaNumeracion = $frmNumeroControl['hddIdEmpresaNumeracion'];
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	
	if ((!xvalidaAcceso($objResponse,"cj_editar_numero_control","editar") && in_array($idCajaPpal, array(1)))
	|| (!xvalidaAcceso($objResponse,"cjrs_editar_numero_control","editar") && in_array($idCajaPpal, array(2)))) { return $objResponse; }
	
	// GUARDA EN LA AUDITORIA EL USUARIO QUE REALIZO LA MODIFICACION
	//CONSULTO EN NUMERO ACTUAL
	$queryAct = sprintf("SELECT numero_actual FROM pg_empresa_numeracion WHERE id_empresa_numeracion = %s;",
		valTpDato($hddIdEmpresaNumeracion, "int"));
	$rsAct = mysql_query($queryAct);
	if (!$rsAct) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAct = mysql_fetch_array($rsAct);
	
	//INSERTO CABECERA DE LA AUDITORIA
	$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios (tipo_documento, id_documento, id_usuario, fecha_cambio, tabla_editada) 
	VALUES (5, %s, %s, NOW(), %s);",//1 = FA, 2 = ND, 3 = AN, 4 = NC, 5 = NRO CONTROL
		valTpDato($idEmpresa, "int"),
		valTpDato($idUsuario, "int"),
		valTpDato("pg_empresa_numeracion", "text"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idAuditoria = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	//INSERTO EL DETALLE DE LA AUDITORIA
	$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios_detalle (id_auditoria_cambios, campo_editado, valor_antiguo, valor_nuevo)
	VALUES (%s, %s, %s, %s);",
		valTpDato($idAuditoria, "int"),
		valTpDato("numero_actual", "text"),
		valTpDato($rowAct['numero_actual'], "text"),
		valTpDato($rowAct['numero_actual'], "text"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idAuditoriaDet = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
		
	//ACTUALIZA EL NUMERO ACTUAL
	$query = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = %s
	WHERE id_empresa_numeracion = %s",
		valTpDato($numeroActual, "int"),
		valTpDato($hddIdEmpresaNumeracion, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	else {
		$objResponse->alert("Nro. de Control editado exitosamente.");
		$objResponse->script("byId('divFlotante').style.display = 'none'");
		$objResponse->loadCommands(listadoNroControl(0,'nombreNumeraciones','ASC', $_SESSION['idEmpresaUsuarioSysGts']));
	}
	
	//ACTUALIZO EL DETALLE CON EL NUMERO ACTUAL REAL	
	$query = sprintf("UPDATE cc_auditoria_cambios_detalle SET valor_nuevo = %s
	WHERE id_auditoria_cambios = %s",
		valTpDato($numeroActual, "int"),
		valTpDato($idAuditoriaDet, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	else {
		$objResponse->alert("Nro. de Control editado exitosamente.");
		$objResponse->script("byId('divFlotante').style.display = 'none'");
		$objResponse->loadCommands(listadoNroControl(0,'nombreNumeraciones','ASC', $_SESSION['idEmpresaUsuarioSysGts']));
	}
			
	return $objResponse;
}

function listadoNroControl($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
	$sqlBusq .= $cond.("nombreNumeraciones LIKE '%control%')");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("pg_empresa_numeracion.id_empresa = %s)",
			valTpDato($valCadBusq[0], "int"));
	}
		
	$query = sprintf("SELECT *,
		pg_empresa_numeracion.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM pg_numeracion
	INNER JOIN pg_empresa_numeracion ON (pg_numeracion.id_numeracion = pg_empresa_numeracion.id_numeracion) 
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (pg_empresa_numeracion.id_empresa = vw_iv_emp_suc.id_empresa_reg)
%s", $sqlBusq);

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

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoNroControl", "30%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoNroControl", "30%", $pageNum, "nombreNumeraciones", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listadoNroControl", "20%", $pageNum, "numero_inicio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Inicio");
		$htmlTh .= ordenarCampo("xajax_listadoNroControl", "20%", $pageNum, "numero_actual", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Actual");
		$htmlTh .= ordenarCampo("xajax_listadoNroControl", "", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreNumeraciones'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['numero_inicio'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['numero_actual'],2,".",",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_frmNroControl('%s','%s','%s','%s');\" src=\"../img/iconos/pencil.png\" title=\"Editar Nro. Control\"/></td>",$row['id_empresa_numeracion'],$row['numero_inicio'],$row['numero_actual'],utf8_encode($row['nombreNumeraciones'])); //EDITAR
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoNroControl(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoNroControl(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoNroControl(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoNroControl(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoNroControl(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoNroControl","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function frmNroControl($id_empresa_numeracion, $nroInicio, $nroActual, $descripcion){
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(cargarNumeroControl($id_empresa_numeracion, $nroInicio, $nroActual, $descripcion));
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Nro. Control");
	$objResponse->script("
		if (byId('divFlotante').style.display == 'none') {
			byId('divFlotante').style.display='';
			centrarDiv(byId('divFlotante'));}");
	
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarNumeroControl");
$xajax->register(XAJAX_FUNCTION,"editarNumero");
$xajax->register(XAJAX_FUNCTION,"listadoNroControl");
$xajax->register(XAJAX_FUNCTION,"frmNroControl");
$xajax->register(XAJAX_FUNCTION,"frmNroControl");
?>