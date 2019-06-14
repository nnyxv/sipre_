<?php 
function buscarSeguimiento($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstPosibilidadCierreBus'],
		$frmBuscar['textDesdeCita'],
		$frmBuscar['textHastaCita'],
		$frmBuscar['textDesdeCreacion'],
		$frmBuscar['textHastaCreacion'],
		$frmBuscar['listVendedorEquipo'],
		$frmBuscar['textCriterio']);

	$objResponse->loadCommands(lstSeguimiento(0, "seguimiento.id_seguimiento","ASC", $valBusq));

		
	return $objResponse;
}

function cargarLstPosibilidadCierre($idPosibilidadCierre = "", $idObjDestino = "", $idEmpresa = "") {
	
	$objResponse = new xajaxResponse();

	$idEmpresa = ($idEmpresa == "") ? $_SESSION['idEmpresaUsuarioSysGts'] : $idEmpresa;
	
	$query = sprintf("SELECT * 
						FROM crm_posibilidad_cierre
						WHERE activo = %s AND id_empresa = %s 
						ORDER BY posicion_posibilidad_cierre ASC",
					valTpDato(1,"int"),
					valTpDato($idEmpresa, "int"),
					valTpDato(1,"int"));
	//jafm esta validacion hace que no muestre todos los estatus -- AND fin_trafico = %s
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rsNum = mysql_num_rows($rs);
	
	$idObjSelect = ($idObjDestino == "tdLstPosibilidadCierre") ? "lstPosibilidadCierre" : "lstPosibilidadCierreBus";
	$nameObjSelect = ($idObjDestino == "tdLstPosibilidadCierre") ? "lstPosibilidadCierre" : "lstPosibilidadCierreBus";
	$onchange = ($idObjDestino == "tdLstPosibilidadCierre") ? "" : "onchange=\"byId('btnBuscar').click();\"";
	$class = ($idObjDestino == "tdLstPosibilidadCierre") ? "inputInicial" : "inputHabilitado";
	
	while ($rows = mysql_fetch_array($rs)) {
		$selected = ($rows['id_posibilidad_cierre'] == $idPosibilidadCierre) ? "selected=\"selected\"" : "";
		$htmlOption .= sprintf("<option %s value=\"%s\">%s.- %s</option>",
			$selected, $rows['id_posibilidad_cierre'],$rows['posicion_posibilidad_cierre'], utf8_encode($rows['nombre_posibilidad_cierre']));
	}
	$styleObj = "width:199px";
	
	$html .= sprintf("<select style=\"%s\" id=\"%s\" name=\"%s\" class=\"%s\" %s>",$styleObj, $idObjSelect,$nameObjSelect,$class,$onchange);
		$html .= '<option value="-1">[ Seleccione ]</option>';
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign($idObjDestino, 'innerHTML', $html);

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

		//CONSULTA LOS EMPLEADOS POR EQUIPO
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
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("perfil_prospecto.id_posibilidad_cierre = %s",
				valTpDato($valCadBusq[1], "int"));
	}else{
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("activo = %s",
				valTpDato(1, "int"));
		/*jafm
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("fin_trafico = %s",
				valTpDato(1, "int"));
		*/
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond2.sprintf("id_empresa = %s",
					valTpDato($valCadBusq[0], "int"));
		}else{
			$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond2.sprintf("id_empresa = %s",
					valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
		}

		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("perfil_prospecto.id_posibilidad_cierre IN (SELECT id_posibilidad_cierre
										FROM crm_posibilidad_cierre %s)", $sqlBusq2);
	}
	
	if($valCadBusq[2] != "-1" && $valCadBusq[2] != "" && $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fechaProximaEntrevista BETWEEN %s AND %s",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"));
	}

	if($valCadBusq[4] != "-1" && $valCadBusq[4] != "" && $valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_registro) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "text"),
				valTpDato(date("Y-m-d",strtotime($valCadBusq[5])), "text"));
	}

	if($valCadBusq[6] != "-1" && $valCadBusq[6]) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("seguimiento_diario.id_empleado_vendedor = %s",
				valTpDato($valCadBusq[6], "int"));
	}

	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
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
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"),
				valTpDato("%".$valCadBusq[7]."%", "text"));
	}


	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("seguimiento.estatus = %s AND perfil_prospecto.estatus = %s",
			valTpDato(1, "int"),
			valTpDato(1, "int"));

	$query = sprintf("SELECT
							seguimiento.id_seguimiento, seguimiento.id_cliente, seguimiento.id_empleado_creador, seguimiento.id_empleado_actualiza, seguimiento.id_empresa, seguimiento.observacion_seguimiento,
							CONCAT_WS(' ',cliente.nombre, cliente.apellido) AS nombre_cliente,
							seguimiento_diario.id_seguimiento_diario, seguimiento_diario.id_equipo, equipo.jefe_equipo, seguimiento_diario.id_empleado_vendedor, fecha_registro, fecha_asignacion_vendedor,
							perfil_prospecto.id_perfil_prospecto, perfil_prospecto.id_posibilidad_cierre, fechaProximaEntrevista,
							posibilidad_cierre.nombre_posibilidad_cierre, img_posibilidad_cierre,
							grupositems.item, dealer.color_identificador,
							(SELECT nom_uni_bas FROM an_prospecto_vehiculo prospecto_vehiculo
										INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
									WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) AS nom_uni_bas,
					
								(SELECT an_unidad_fisica.serial_chasis FROM an_unidad_fisica
									WHERE  an_unidad_fisica.id_uni_bas = (SELECT an_uni_bas.id_uni_bas FROM an_prospecto_vehiculo
																					INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
																				WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)

									GROUP BY an_unidad_fisica.id_uni_bas ) AS serial_chasis,


								(SELECT precio_unidad_basica  FROM an_prospecto_vehiculo prospecto_vehiculo
										INNER JOIN an_uni_bas uni_bas ON uni_bas.id_uni_bas = prospecto_vehiculo.id_unidad_basica
									WHERE prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1) AS precio_unidad_basica,
					
								IFNULL((SELECT COUNT(an_unidad_fisica.id_uni_bas) FROM an_unidad_fisica
									WHERE  an_unidad_fisica.id_uni_bas = (SELECT an_uni_bas.id_uni_bas FROM an_prospecto_vehiculo
																				INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
																			WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
										AND estado_venta = 'DISPONIBLE'
									GROUP BY an_unidad_fisica.id_uni_bas ), 0) AS disponible_unidad_fisica,
			
							IFNULL(
								(
									SELECT
										uni_bas.id_uni_bas
									FROM
										an_tradein tradein
									INNER JOIN cj_cc_anticipo cxc_ant ON tradein.id_anticipo = cxc_ant.idAnticipo
									INNER JOIN an_unidad_fisica uni_fis ON tradein.id_unidad_fisica = uni_fis.id_unidad_fisica
									INNER JOIN an_uni_bas uni_bas ON uni_fis.id_uni_bas = uni_bas.id_uni_bas
									WHERE
										cxc_ant.idCliente = seguimiento.id_cliente
								),
								0
							) AS id_uni_bas,
		
							CONCAT_WS(' ',empleado.nombre_empleado, empleado.apellido) AS nombre_usuario_creador,
							(SELECT CONCAT_WS(' ',nombre_empleado, pg_empleado.apellido) FROM pg_empleado
									WHERE pg_empleado.id_empleado = seguimiento_diario.id_empleado_vendedor) AS nobre_vendedor
						FROM crm_seguimiento seguimiento
							INNER JOIN cj_cc_cliente cliente ON cliente.id = seguimiento.id_cliente
							INNER JOIN crm_seguimiento_diario seguimiento_diario ON seguimiento_diario.id_seguimiento = seguimiento.id_seguimiento
							INNER JOIN crm_perfil_prospecto perfil_prospecto ON cliente.id = perfil_prospecto.id
							LEFT JOIN crm_posibilidad_cierre posibilidad_cierre ON posibilidad_cierre.id_posibilidad_cierre = perfil_prospecto.id_posibilidad_cierre
							LEFT JOIN crm_equipo equipo ON equipo.id_equipo = seguimiento_diario.id_equipo
							LEFT JOIN crm_ingreso_prospecto dealer ON dealer.id_dealer = seguimiento_diario.id_dealer
							INNER JOIN grupositems ON grupositems.idItem = (SELECT id_medio FROM an_prospecto_vehiculo
																				INNER JOIN an_uni_bas ON an_uni_bas.id_uni_bas = an_prospecto_vehiculo.id_unidad_basica
																			WHERE an_prospecto_vehiculo.id_cliente = seguimiento.id_cliente LIMIT 1)
							INNER JOIN pg_empleado empleado ON empleado.id_empleado = seguimiento.id_empleado_creador
						%s",
			$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	//$objResponse->alert($queryLimit);//jafm
	$rsLimit = mysql_query($queryLimit);
	$rsLimit2 = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		$rs2 = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr align=\"center\">";
			$htmlTh .= "<td>";
				$htmlTh .= "<table width=\"100%\" border=\"0\">";
					$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
						$htmlTh .= "<td style=\"width:26px\">Id</td>";
						$htmlTh .= "<td width=\"8%\">Entrada</td>";
						$htmlTh .= "<td width=\"11%\">Fuente</td>";
						$htmlTh .= "<td width=\"11%\">Nombre Cliente</td>";
						$htmlTh .= "<td width=\"19%\">Modelo Interes</td>";
						$htmlTh .= "<td width=\"13%\">VIN</td>";
						$htmlTh .= "<td id=\"tdActividadLstEncabezado\" style=\"min-width:18%\" rowspan=\"2\" ></td>";
						$htmlTh .= sprintf("<td width=\"%s\">%s Precio</td>","10%",cAbrevMoneda);
						$htmlTh .= sprintf("<td style=\"min-width:86px\" rowspan=\"2\"></td>");
					$htmlTh .= "</tr>";
					$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
						$htmlTh .= "<td style=\"min-width:10px\">N </td>";
						$htmlTh .= "<td>Asignada</td>";
						$htmlTh .= "<td>Gerente de Mesa</td>";
						$htmlTh .= "<td>Proxima Cita</td>";
						$htmlTh .= "<td>Trade In</td>";
						$htmlTh .= "<td>Vendedor</td>";
						$htmlTh .= "<td >Comentarios</td>";
					$htmlTh .= "</tr>";
				$htmlTh .= "</table>";
			$htmlTh .= "</td>";
		$htmlTh .= "</tr>";
	$idSeguimiento = 0;
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$arrayIdSeguimiento [] = $row['id_seguimiento'];

		//BUSCAR LOS TRADE IN POR SEGUIMIENTOS
		if( $row['id_seguimiento'] > 0){

			$sqlSegTrade = sprintf("SELECT seg_trade.id_seguimiento_tradein
											FROM crm_seguimiento_tradein seg_trade
											WHERE seg_trade.id_seguimiento = %s",
					$row['id_seguimiento']);
			$rsSegTrade = mysql_query($sqlSegTrade);
			if (!$rsSegTrade) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlSegTrade);

			while($rowTradeIn = mysql_fetch_assoc($rsSegTrade)){
				$sqlTradeIn = sprintf("SELECT pro_trad.serial_carroceria, seg_trad.id_prospecto_tradein
												FROM crm_seguimiento_tradein AS seg_trad
													INNER JOIN an_prospecto_tradein AS pro_trad ON seg_trad.id_prospecto_tradein = pro_trad.id_prospecto_tradein
													INNER JOIN crm_seguimiento AS seg ON seg_trad.id_seguimiento = seg.id_seguimiento
												WHERE seg_trad.id_prospecto_tradein = pro_trad.id_prospecto_tradein
													AND seg_trad.id_seguimiento = seg.id_seguimiento AND seg_trad.id_seguimiento_tradein = %s",
						valTpDato($rowTradeIn['id_seguimiento_tradein'], "text"));
				$rsTradeIn = mysql_query($sqlTradeIn);
				if (!$rsTradeIn) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$rsTradeIn);
				$numRows = mysql_num_rows($rsTradeIn);
					
				if($numRows > 0){
					while ($rowVin = mysql_fetch_assoc($rsTradeIn)) {
						$vin[] = $rowVin['serial_carroceria'];
						$idTradeIn[] = $rowVin['id_prospecto_tradein'];
					}
				}
			}

			$resulAncho = count($vin);

			if($resulAncho == 0){
				$width = '220px';
			} elseif($resulAncho == 2){
				$width = '170px';
			} else{
				$width = '170px';
			}
		}

		$imgFoto = (!file_exists($row['img_posibilidad_cierre'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['img_posibilidad_cierre'];

		$htmlTb .= "<tr class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" class=\"divGris trResaltar4\" width=\"100%\">";
					$htmlTb .= sprintf("<tr align=\"left\" style=\"background-color:%s\">",
						$row['color_identificador']);
						$htmlTb .= sprintf("<td align=\"center\" class=\"%s modalImg puntero\" id=\"aEditar\" title=\"Editar\" rel=\"#divFlotante\" style=\"min-width:24px\" onclick=\"abrirFrom(this,'frmSeguimiento','tdFlotanteTitulo', %s, 'tblProspecto')\" style=\"background-color:%s\">%s</td>",
							$clase,
							$row['id_seguimiento'],
							$row['color_identificador'],
							$row['id_seguimiento']);
						$htmlTb .= "<td align=\"center\" style=\"min-width:8%\">".date("d-m-Y h:i a", strtotime($row['fecha_registro']))."</td>";
						$htmlTb .= "<td style=\"min-width:12%\">".strtoupper(utf8_encode($row['item']))."</td>";
						$htmlTb .= "<td style=\"min-width: 12%\">".strtoupper(utf8_encode($row['nombre_cliente']))."</td>";
						$htmlTb .= "<td style=\"min-width:17%\">".strtoupper(utf8_encode($row['nom_uni_bas']))."</td>";
						$htmlTb .= "<td align=\"center\" style=\"min-width:14%\">".utf8_encode($row['serial_chasis'])."</td>";
						$htmlTb .= sprintf("<td id=\"tdActividadLstSelect%s\" style=\"width:%s\" rowspan=\"2\"></td>",
								$row['id_seguimiento'],
								"18%");
								$htmlTb .= sprintf("<td align=\"right\" style=\"width:%s\">%s</td>",
										"11%",
										cAbrevMoneda." ".number_format($row['precio_unidad_basica'],2,".",","));
								$htmlTb .= sprintf("<td id=\"aPosibleCierre\"  onclick=\"abrirFrom(this, 'frmBusPosibleCierre', 'tdFlotanteTitulo12', %s, 'tblLstPosibleCierre')\" rel=\"#divFlotante12\" rowspan=\"2\" style=\"min-width:%s\">".
										"<img src=\"%s\" title=\"%s\" height=\"80\" width=\"80\"/>".
										"</td>",
										$row['id_seguimiento'], "30%",
										$imgFoto, utf8_encode($row['nombre_posibilidad_cierre']));
								$htmlTb .= "</tr>";
								$htmlTb .= sprintf("<tr style=\"background-color:%s\">",$row['color_identificador']);
								$htmlTb .= sprintf("<td id=\"aNotas\"/></td>",
										$row['id_seguimiento']);
							/* jafm se comento ya que muestra una imagen para editar y esto es un reporte
								class='modalImg puntero' onclick=\"abrirFrom(this, 'frmBusNotas', 'tdFlotanteTitulo9', %s, 'tblLstNotas')\" rel=\"#divFlotante9\"><img src=\"../img/iconos/text_signature.png\"
								*/

								$htmlTb .= sprintf("<td>%s</td>",
								$hora = ($row['fecha_asignacion_vendedor'] != "") ?  date("h.i a", strtotime($row['fecha_asignacion_vendedor'])) : "");
								$htmlTb .= "<td>".utf8_encode($row['nombre_usuario_creador'])."</td>";
								$htmlTb .= "<td>".(($row['fechaProximaEntrevista'] != "") ? date("d-m-Y", strtotime($row['fechaProximaEntrevista'])): "")."</td>";
								$htmlTb .= "<td>";
									$htmlTb .= "<table border=\"0\" width=\"100%\">";
										$htmlTb .= "<tr>";
											$htmlTb .= "<td>";
												$htmlTb .= "<table border=\"0\" width=\"100%\">";
													if($vin[0] != ''){
														$htmlTb .= "<tr>";
															$htmlTb .= "<td align='left'>";
																$htmlTb .= "<div style=\"width: 220px;\">";
																	$htmlTb .= "Vin: ".utf8_encode($vin[0]);
																$htmlTb .= "</div>";
															$htmlTb .= "</td>";
														$htmlTb .= "</tr>";
													} else{
														$htmlTb .= "<tr>";
															$htmlTb .= "<td align='left'>";
																$htmlTb .= "<div style=\"width: {$width};\">";
																	$htmlTb .= "Vin: No asignado"; // MSJ 1
																$htmlTb .= "</div>";
															$htmlTb .= "</td>";
														$htmlTb .= "</tr>";
													}
													if($vin[1]){
														$htmlTb .= "<tr>";
															$htmlTb .= "<td align='left'>";
																$htmlTb .= "<div style=\"width: {$width};\">";
																	$htmlTb .= "Vin: ".utf8_encode($vin[1]);
																$htmlTb .= "</div>";
															$htmlTb .= "</td>";
														$htmlTb .= "</tr>";
													} else{
														$htmlTb .= "<tr>";
															$htmlTb .= "<td width='12px' align='left'>";
																$htmlTb .= "<div style=\"width: {$width};\">";
																	$htmlTb .= "Vin: No asignado";  // MSJ 2
																$htmlTb .= "</div>";
															$htmlTb .= "</td>";
														$htmlTb .= "</tr>";
													}
												$htmlTb .= "</table>";
											$htmlTb .= "</td>";
										$htmlTb .= "</tr>";
									$htmlTb .= "</table>";
								$htmlTb .= "</td>";
								$htmlTb .= "<td>".utf8_encode($row['nobre_vendedor'])."</td>";
								$htmlTb .= "<td>".utf8_encode($row['observacion_seguimiento'])."</td>";
							$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
				$htmlTb .= "</tr>";
				unset($vin);
				unset($idTradeIn);
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

	if(isset($arrayIdSeguimiento)){
		foreach($arrayIdSeguimiento as $indice => $valor){
			$objResponse->loadCommands(listaActSegSelect(0,"","",$valCadBusq[0]."|".$valor));
		}
	}
		//jafm  se usa para imprimir los totales
		$cond="";
		$sqlBusq="";
		$sqlBusq = $cond.sprintf(" where fecha_creacion_prospecto BETWEEN %s AND %s",
						valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "text"),
						valTpDato(date("Y-m-d",strtotime($valCadBusq[5])), "text"));
		$objResponse->loadCommands(totalizar(1,$sqlBusq));
		$objResponse->loadCommands(totalizar(2,$sqlBusq));
		$objResponse->loadCommands(totalizar(3,$sqlBusq));
		//jafm
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

function listaActSegSelect ($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") { // EMPRESA
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

		$query2 =  sprintf("SELECT * FROM crm_actividad_seguimiento WHERE id_seguimiento = %s AND id_actividad = %s",
				valTpDato($valCadBusq[1],"int"),
				valTpDato($row['id_actividad'],"int"));
		$rs2 = mysql_query($query2);
		if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query2);
		$numRows2 = mysql_num_rows($rs2);
		$row2 = mysql_fetch_assoc($rs2);

		$check = ($row['id_actividad'] == $row2['id_actividad']) ? "checked='checked'":"";

		$htmlTb .= (fmod($contFila, 4) == 1) ? "<tr align=\"center\">" : "";
		$htmlTb .= sprintf("<td width=\"%s\" align=\"center\" title=\"%s\">".
				"<input id=\"checkActividad%s\" name=\"checkActividad%s[]\" type=\"checkbox\" value=\"%s\" %s disabled=\"disabled\" \">".
				"<input name=\"hiddIdSeguimientoAct%s.%s\" id=\"hiddIdSeguimientoAct%s.%s\" type=\"hidden\" value=\"%s\" />".
				"</td>",
				(100 / $totalRows)."%",utf8_encode($row['nombre_actividad']),
				$row['id_actividad'],$valCadBusq[1],$row['id_actividad'],$check,
				$valCadBusq[1],$row['id_actividad'],$valCadBusq[1],$row['id_actividad'],$row2['id_actividad_seguimiento']);
		$htmlTb .= (fmod($contFila, 4) == 0) ? "</tr>" : "";
	}

	$htmlTb .= "</table>";

	if (!($totalRows > 0)) {
		$htmlTb .= "<td>";
		$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\" height=\"100%\">";
		$htmlTb .= "<tr>";
		$htmlTb .= "<td align=\"center\"></td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdActividadLstSelect".$valCadBusq[1],"innerHTML",$htmlTb);

	return $objResponse;
}

	function totalizar($tipo,$where)//jafm
	 {
		$objResponse = new xajaxResponse();						
		$total="0";
		$sql="SELECT count(0)as total FROM cj_cc_cliente $where";
					
		$rsTotal = mysql_query($sql);
		
		while ($row = mysql_fetch_assoc($rsTotal)) {
			$total = $row['total'];
		}
	
		if (!$rsTotal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);			 
		if ($tipo=1)//prospectos nuevos
		{			
			$objResponse->assign("totalnuevos","innerHTML",$total);
		}if ($tipo=2)//prospectos 	que pasaron a ser clientes
		{
			$objResponse->assign("totalconvercliente","innerHTML",$total);
		}if ($tipo=3)//clientes rechazados
		{
			$objResponse->assign("totalrechazados","innerHTML",$total);
		}		
		return  $objResponse;
	 }//jafm

$xajax->register(XAJAX_FUNCTION,"buscarSeguimiento");
$xajax->register(XAJAX_FUNCTION,"cargarLstPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"listaActSegEncabezado");
$xajax->register(XAJAX_FUNCTION,"listaActSegSelect");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"lstSeguimiento");

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