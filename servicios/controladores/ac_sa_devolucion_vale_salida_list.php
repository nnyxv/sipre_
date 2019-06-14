<?php

function buscarOrden($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEmpleadoVendedor'],
		$valForm['lstTipoOrden'],
		$valForm['lstEstadoOrden'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listadoOrdenes(0, "numero_vale", "DESC", $valBusq));
	
	return $objResponse;
}


function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "", $idEmpresa = "") {
	$objResponse = new xajaxResponse();
	
	if($idEmpresa == ""){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];	
	}
		
	$query = sprintf("SELECT DISTINCT empleado.id_empleado, empleado.nombre_empleado
	FROM sa_vale_salida vale_salida
		INNER JOIN vw_pg_empleados empleado ON (vale_salida.id_empleado = empleado.id_empleado)
		WHERE vale_salida.id_empresa = $idEmpresa
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_empleado'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}


function cargaLstEmpresaBuscar($selId = "") {
	$objResponse = new xajaxResponse();
			
	$query = sprintf("SELECT DISTINCT id_empresa, nombre_empresa FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_empresa'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_empresa']."\">".utf8_encode($row['nombre_empresa'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);

	return $objResponse;
}


function cargaLstEstadoOrden($selId = "") {
	$objResponse = new xajaxResponse();
			
	$query = "SELECT * FROM sa_estado_orden
	WHERE activo = 1
	ORDER BY sa_estado_orden.orden";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEstadoOrden\" name=\"lstEstadoOrden\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_estado_orden'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_estado_orden']."\">".utf8_encode($row['nombre_estado'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoOrden","innerHTML",$html);
	
	return $objResponse;
}


function cargaLstTipoOrden($selId = "", $idEmpresa="") {
	$objResponse = new xajaxResponse();
	
	//si al cargar la orden no se le envia una empresa a cargar (del listado de empresa) que cargue el por defecto.
	if($idEmpresa == ""){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	$query = "SELECT * FROM sa_tipo_orden WHERE sa_tipo_orden.id_empresa = $idEmpresa  ORDER BY sa_tipo_orden.nombre_tipo_orden";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoOrden\" name=\"lstTipoOrden\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_tipo_orden'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_tipo_orden']."\">".utf8_encode($row['nombre_tipo_orden'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoOrden","innerHTML",$html);
	
	return $objResponse;
}


function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vale_salida.estado_vale = 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vale_salida.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(vale_salida.fecha_vale) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vale_salida.id_empleado = %s",
			valTpDato($valCadBusq[3],"int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_tipo_orden = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_estado_orden = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			FROM cj_cc_cliente cliente
			WHERE cliente.id = (IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
								FROM sa_recepcion r
								WHERE r.id_recepcion = orden.id_recepcion ), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																			FROM sa_cita c
																			WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																							FROM sa_recepcion r
																							WHERE r.id_recepcion = orden.id_recepcion))))) LIKE %s
		
		OR orden.numero_orden LIKE %s
		OR recepcion.numeracion_recepcion LIKE %s
		OR nom_uni_bas LIKE %s
		OR placa LIKE %s
		OR chasis LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"));
	}
	
	$query = sprintf("SELECT
		vale_salida.id_vale_salida,
		vale_salida.fecha_vale,
		vale_salida.numero_vale,
		vale_salida.estado_vale,
		orden.tiempo_orden,
		orden.id_orden,
		orden.numero_orden,
		orden.id_recepcion,
		orden.id_empresa,
		tipo_orden.nombre_tipo_orden,
		recepcion.numeracion_recepcion,
		
		(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		FROM cj_cc_cliente cliente
		WHERE cliente.id = (IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
							FROM sa_recepcion r
							WHERE r.id_recepcion = orden.id_recepcion ), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																		FROM sa_cita c
																		WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																						FROM sa_recepcion r
																						WHERE r.id_recepcion = orden.id_recepcion))))) AS nombre_cliente,
		uni_bas.nom_uni_bas,
		reg_placas.placa,
		reg_placas.chasis,
		id_orden_retrabajo,
		vale_salida.monto_total
	FROM sa_orden orden
		INNER JOIN sa_recepcion recepcion ON (orden.id_recepcion = recepcion.id_recepcion)
		INNER JOIN sa_cita cita ON (recepcion.id_cita = cita.id_cita)
		INNER JOIN en_registro_placas reg_placas ON (cita.id_registro_placas = reg_placas.id_registro_placas)
		INNER JOIN an_uni_bas uni_bas ON (reg_placas.id_unidad_basica = uni_bas.id_uni_bas)
		INNER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
		INNER JOIN sa_estado_orden estado_orden ON (orden.id_estado_orden = estado_orden.id_estado_orden)
		LEFT JOIN sa_retrabajo_orden orden_retrabajo ON (orden.id_orden = orden_retrabajo.id_orden)
		INNER JOIN sa_vale_salida vale_salida ON (orden.id_orden = vale_salida.id_orden) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	//return $objResponse->alert($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		//$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "6%", $pageNum, "fecha_vale", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "6%", $pageNum, "numero_vale", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Vale"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "6%", $pageNum, "tiempo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Orden"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Orden"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Recepcion"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "12%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Orden"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "24%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "6%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, ("Catálogo"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "7%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Placa"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "11%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, ("Chasis"));
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "8%", $pageNum, "monto_total", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total Vale"));
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgPedidoModulo = "";
		$imgEstatusPedido = "";
//		if ($row['id_orden'] == "") {
//			$imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Factura CxC\"/>";
//			
//			if (strtoupper($row['anulada']) == "NO" || strtoupper($row['anulada']) == "")
//				$imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
//			else
//				$imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>";
//		} else {
//			$imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Factura Servicios\"/>";
//			
//			if (strtoupper($row['anulada']) == "NO" || strtoupper($row['anulada']) == "")
//				$imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
//			else
//				$imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>";
//		}
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			//$htmlTb .= "<td align=\"center\">".$imgPedidoModulo."</td>";
			//$htmlTb .= "<td align=\"center\">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['fecha_vale']))."</td>";
			$htmlTb .= "<td align=\"right\" idvalesalidaoculto=\"".$row['id_vale_salida']."\">".($row['numero_vale'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">";
				$htmlTb .= ($row['tiempo_orden']) ? date("d-m-Y",strtotime($row['tiempo_orden'])) : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" idordenoculta=\"".$row['id_orden']."\">".($row['numero_orden'])."</td>";
			$htmlTb .= "<td align=\"right\" idrecepcionoculta=\"".$row['id_recepcion']."\">".($row['numeracion_recepcion'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['placa']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['chasis']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_total'],2,".",",")."</td>";
			$htmlTb .= "<td class=\"noprint\">";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|3', 1000, 500);\" src=\"../img/iconos/ico_view.png\"/>",
					$row['id_vale_salida']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td class=\"noprint\">";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('sa_orden_form.php?doc_type=3&id=%s&ide=%s&acc=2&cons=1&dev=0&idVale=%s','_self');\" src=\"../img/iconos/arrow_rotate_clockwise.png\"/>",
					$row['id_orden'],
					$row['id_empresa'],
					$row['id_vale_salida']);
			$htmlTb .= "</td>";
			
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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
	
	$objResponse->assign("divListaOrdenes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	//$objResponse->script("limpiar_select();");
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"listadoOrdenes");
?>