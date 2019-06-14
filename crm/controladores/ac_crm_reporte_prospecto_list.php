<?php 
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

function cargaLstActividad($idEmpresa = ""){
	$objResponse = new xajaxResponse();

		//CONSULTA LOS EMPLEAO POR EQUIPO
		$queryAct = sprintf("SELECT
			crm_actividad.nombre_actividad,
			crm_actividad.id_actividad
		FROM
			crm_actividad
		WHERE crm_actividad.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
		$rsAct = mysql_query($queryAct);
		if(!$rsAct)return $objResponse->alert(mysql_error."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rsActividad = mysql_num_rows($rsAct);
		while($rowActividad=  mysql_fetch_array($rsAct)){
			$count ++;
			$htmlOption .= sprintf("<option value=\"%s\">%s</option>",
					$rowActividad['id_actividad'],utf8_encode($rowActividad['nombre_actividad']));
		}
		

	$html = "<select id=\"lstTipoAct\" style=\"width:80%;\" name=\"lstTipoAct\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
	$html .= "<option value=\"-1\">[ Selecione ]</option>";
	$html .= $htmlOption;
	$html .= "</select>";

	$objResponse->assign("tdlstTipoActividad","innerHTML",$html);
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

function buscarSeguimiento($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
			$frmBuscar['lstEmpresa'],
			$frmBuscar['listVendedorEquipo'],
			$frmBuscar['lstTipoAct'],
			$frmBuscar['textDesdeCreacion'],
			$frmBuscar['textHastaCreacion'],
			$frmBuscar['textCriterio']);

	$objResponse->loadCommands(lstSeguimiento(0, "seg.id_seguimiento","ASC", $valBusq));

	return $objResponse;
}

function lstSeguimiento($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	global $spanClienteCxC;

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seg.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	$arrayClave = claveFiltroEmpleado();
	if($arrayClave[0] == true){
		if($arrayClave[1] == 1 || $arrayClave[1] == 2){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(seg.id_empleado_creador = %s OR id_empleado_vendedor = %s 
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

	if($valCadBusq[3] != "-1" && $valCadBusq[3] != "" && $valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "text"));
	}
	
	if($valCadBusq[1] != "-1" && $valCadBusq[1]) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seg_dia.id_empleado_vendedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}

	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("act.id_actividad = %s",
				valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ',cliente.nombre, cliente.apellido) LIKE %s
		OR ((SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
					WHERE empleado.id_empleado = seg_dia. id_empleado_vendedor) IN (
								SELECT CONCAT_WS(' ',nombre_empleado, empleado.apellido) FROM pg_empleado empleado 
										WHERE empleado.id_empleado = seg_dia.id_empleado_vendedor AND 
											CONCAT_WS(' ',nombre_empleado, empleado.apellido) LIKE %s))
		OR ((SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
				INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
			WHERE prospecto_vehiculo.id_cliente = seg.id_cliente LIMIT 1) LIKE %s))",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("seg.estatus = %s AND perfil_prospecto.estatus = %s",
			valTpDato(1, "int"),
			valTpDato(1, "int"));
	
	$query = sprintf("	SELECT DISTINCT
							CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombre_cliente,
							CASE
								WHEN cliente.tipo_cuenta_cliente = 1 THEN 'Prospecto'
								ELSE 'Cliente'
							END AS tipo_cliente,
							cliente.ci,
							cliente.id,
							CONCAT_WS(' ',emp.nombre_empleado,emp.apellido) AS nombre_empleado
						FROM
							crm_seguimiento seg
						INNER JOIN crm_seguimiento_diario AS seg_dia ON seg_dia.id_seguimiento = seg.id_seguimiento
						INNER JOIN crm_actividad_seguimiento AS act_seg ON seg.id_seguimiento = act_seg.id_seguimiento
						INNER JOIN crm_actividad AS act ON act_seg.id_actividad = act.id_actividad
						INNER JOIN crm_actividades_ejecucion AS act_eje ON act_eje.id_actividad = act.id_actividad
						INNER JOIN cj_cc_cliente AS cliente ON seg.id_cliente = cliente.id
						INNER JOIN pg_empleado AS emp ON seg_dia.id_empleado_vendedor = emp.id_empleado
						INNER JOIN crm_perfil_prospecto perfil_prospecto ON cliente.id = perfil_prospecto.id %s",
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

		if ($totalRows > 0 && $row['nombre_cliente'] != '') {
			$htmlTb .= "<tr align=\"left\" class=\"trResaltar4\" height=\"24\">";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" width=\"22%\" class=\"tituloCampo\">Nombre Cliente</td>";
				$htmlTb .= "<td width=\"22%\" align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">"."Vendedor:"."</td>";
				$htmlTb .= "<td colspan=\"11\" align=\"left\">".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">C.I.:</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($row['ci'])."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Tipo de Cliente:</td>";
				$htmlTb .= "<td align=\"left\" colspan=\"11\">".utf8_encode($row['tipo_cliente'])."</td>";
			$htmlTb .= "</tr>";
			
			$htmlTb .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTb .= "<td style=\"min-width:2px\">Nombre de Actividad</td>";
				$htmlTb .= "<td width=\"16%\">Fecha de Asignación</td>";
				$htmlTb .= "<td width=\"16%\">Fecha de Creacion</td>";
				$htmlTb .= "<td width=\"22%\">Tipo de Venta</td>";
				$htmlTb .= "<td width=\"22%\">Estatus</td>";
			$htmlTb .= "</tr>";
		}
		
		$query2 = sprintf("SELECT DISTINCT
									seg.id_seguimiento,
									act.nombre_actividad,
									CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
									cliente.ci,
									act_seg.id_actividad_seguimiento,
									act_eje.fecha_asignacion,
									act_eje.fecha_creacion,
									act.tipo,
									CASE
										WHEN act.activo = 1 THEN 'Activo'
										ELSE 'Inactivo'
									END AS estatus,
									CONCAT_WS(' ', emp.nombre_empleado, emp.apellido) AS nombre_empleado
								FROM crm_actividad_seguimiento AS act_seg
									INNER JOIN crm_seguimiento AS seg ON act_seg.id_seguimiento = seg.id_seguimiento
									INNER JOIN crm_actividad AS act ON act_seg.id_actividad = act.id_actividad
									INNER JOIN crm_seguimiento_diario AS seg_dia ON seg_dia.id_seguimiento = seg.id_seguimiento
									INNER JOIN cj_cc_cliente AS cliente ON seg.id_cliente = cliente.id
									INNER JOIN crm_perfil_prospecto perfil_prospecto ON cliente.id = perfil_prospecto.id
									INNER JOIN crm_actividades_ejecucion AS act_eje ON act_eje.id_actividad = act.id_actividad AND act_eje.id = cliente.id AND act_eje.id_actividad_seguimiento = act_seg.id_actividad_seguimiento
									INNER JOIN pg_empleado AS emp ON seg_dia.id_empleado_vendedor = emp.id_empleado 
									WHERE cliente.id = %s AND seg.estatus = 1 AND perfil_prospecto.estatus = 1",
						utf8_encode($row['id']));
		
		$rsAct = mysql_query($query2);
		if (!$rsAct) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		if ($totalRows == NULL) {
			$rs = mysql_query($query2);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
			$totalRows = mysql_num_rows($rs);
		}
		
		while($rowAct = mysql_fetch_assoc($rsAct)){
			$date = new DateTime($rowAct['fecha_asignacion']);
			$fecha_asig =  $date->format('d-m-Y');
			
			$date = new DateTime($rowAct['fecha_creacion']);
			$fecha_creacion =  $date->format('d-m-Y');
			
			$htmlTb.= "<tr class=\"trResaltar4\" align=\"left\" height=\"22\">";
				$htmlTb .= "<td align=\"left\" width=\"22%\">".utf8_encode($rowAct['nombre_actividad'])."</td>";
				$htmlTb .= "<td align=\"center\" width=\"22%\">".utf8_encode($fecha_asig)."</td>";
				$htmlTb .= "<td align=\"center\" width=\"22%\">".utf8_encode($fecha_creacion)."</td>";
				$htmlTb .= "<td align=\"left\" width=\"22%\">".utf8_encode($rowAct['tipo'])."</td>";
				$htmlTb .= "<td align=\"left\" width=\"22%\">".utf8_encode($rowAct['estatus'])."</td>";
			$htmlTb .= "</tr>";
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

function exportarClienteActividad($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['listVendedorEquipo'],
		$frmBuscar['lstTipoAct'],
		$frmBuscar['textDesdeCreacion'],
		$frmBuscar['textHastaCreacion'],
		$frmBuscar['textCriterio']);

	$objResponse->script("window.open('reportes/crm_actividades_prospecto_excel.php?valBusq=".$valBusq."','_self');");

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarSeguimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"cargaLstActividad");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"lstSeguimiento");
$xajax->register(XAJAX_FUNCTION,"exportarClienteActividad");

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
?>