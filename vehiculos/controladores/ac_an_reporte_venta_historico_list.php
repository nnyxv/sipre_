<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstTipoFecha']) ? implode(",",$frmBuscar['lstTipoFecha']) : $frmBuscar['lstTipoFecha']),
		(is_array($frmBuscar['lstEmpleadoVendedor']) ? implode(",",$frmBuscar['lstEmpleadoVendedor']) : $frmBuscar['lstEmpleadoVendedor']),
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstTipoPago']) ? implode(",",$frmBuscar['lstTipoPago']) : $frmBuscar['lstTipoPago']),
		(is_array($frmBuscar['lstAnuladaFactura']) ? implode(",",$frmBuscar['lstAnuladaFactura']) : $frmBuscar['lstAnuladaFactura']),
		(is_array($frmBuscar['lstEstadoFactura']) ? implode(",",$frmBuscar['lstEstadoFactura']) : $frmBuscar['lstEstadoFactura']),
		(is_array($frmBuscar['lstItemFactura']) ? implode(",",$frmBuscar['lstItemFactura']) : $frmBuscar['lstItemFactura']),
		(is_array($frmBuscar['lstItemPago']) ? implode(",",$frmBuscar['lstItemPago']) : $frmBuscar['lstItemPago']),
		(is_array($frmBuscar['lstBancoFinanciar']) ? implode(",",$frmBuscar['lstBancoFinanciar']) : $frmBuscar['lstBancoFinanciar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaFacturaVenta(0, "numeroControl", "DESC", $valBusq));
		
	return $objResponse;
}

function cargaLstAnuladaFactura($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$array = array("NO" => "Factura", "SI" => "Factura (Con Devolución)");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstAnuladaFactura\" name=\"lstAnuladaFactura\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAnuladaFactura","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstBancoFinanciar($selId = "", $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE nombreBanco <> '-'
	ORDER BY nombreBanco;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstClaveMovimiento($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento
	WHERE id_modulo = 0
		AND tipo = 3
	GROUP BY tipo
	ORDER BY tipo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstClaveMovimiento\" name=\"lstClaveMovimiento\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento
		WHERE id_modulo IN (0)
			AND tipo = %s
		ORDER BY descripcion",
			valTpDato($row['tipo'],"int"));
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".htmlentities($rowClaveMov['descripcion'])."</option>";
		}
		
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstClaveMovimiento","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpleado($nombreObjeto = "", $objetoDestino = "", $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN vw_pg_empleados empleado ON (cxc_fact.idVendedor = empleado.id_empleado)
	WHERE cxc_fact.idDepartamentoOrigenFactura = 2
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstItemFactura($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$array = array(1 => "Vehículo", 2 => "Adicionales", 3 => "Accesorios");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstItemFactura\" name=\"lstItemFactura\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstItemFactura","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstItemPago($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$array = array(1 => "Bono", 2 => "Trade-In", 3 => "PND", 4 => "Upside Down", 5 => "Ajuste Trade-In");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstItemPago\" name=\"lstItemPago\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstItemPago","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstTipoFecha($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$array = array(1 => "De Registro", 2 => "De Entrega", 3 => "De Pagada", 4 => "De Cierre");
	$totalRows = count($array);
	
	$html .= "<select id=\"lstTipoFecha\" name=\"lstTipoFecha\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoFecha","innerHTML", $html);
	
	return $objResponse;
}

function exportarFacturaVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstTipoFecha']) ? implode(",",$frmBuscar['lstTipoFecha']) : $frmBuscar['lstTipoFecha']),
		(is_array($frmBuscar['lstEmpleadoVendedor']) ? implode(",",$frmBuscar['lstEmpleadoVendedor']) : $frmBuscar['lstEmpleadoVendedor']),
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstTipoPago']) ? implode(",",$frmBuscar['lstTipoPago']) : $frmBuscar['lstTipoPago']),
		(is_array($frmBuscar['lstAnuladaFactura']) ? implode(",",$frmBuscar['lstAnuladaFactura']) : $frmBuscar['lstAnuladaFactura']),
		(is_array($frmBuscar['lstEstadoFactura']) ? implode(",",$frmBuscar['lstEstadoFactura']) : $frmBuscar['lstEstadoFactura']),
		(is_array($frmBuscar['lstItemFactura']) ? implode(",",$frmBuscar['lstItemFactura']) : $frmBuscar['lstItemFactura']),
		(is_array($frmBuscar['lstItemPago']) ? implode(",",$frmBuscar['lstItemPago']) : $frmBuscar['lstItemPago']),
		(is_array($frmBuscar['lstBancoFinanciar']) ? implode(",",$frmBuscar['lstBancoFinanciar']) : $frmBuscar['lstBancoFinanciar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_reporte_venta_historico_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirFacturaVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstTipoFecha']) ? implode(",",$frmBuscar['lstTipoFecha']) : $frmBuscar['lstTipoFecha']),
		(is_array($frmBuscar['lstEmpleadoVendedor']) ? implode(",",$frmBuscar['lstEmpleadoVendedor']) : $frmBuscar['lstEmpleadoVendedor']),
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstTipoPago']) ? implode(",",$frmBuscar['lstTipoPago']) : $frmBuscar['lstTipoPago']),
		(is_array($frmBuscar['lstAnuladaFactura']) ? implode(",",$frmBuscar['lstAnuladaFactura']) : $frmBuscar['lstAnuladaFactura']),
		(is_array($frmBuscar['lstEstadoFactura']) ? implode(",",$frmBuscar['lstEstadoFactura']) : $frmBuscar['lstEstadoFactura']),
		(is_array($frmBuscar['lstItemFactura']) ? implode(",",$frmBuscar['lstItemFactura']) : $frmBuscar['lstItemFactura']),
		(is_array($frmBuscar['lstItemPago']) ? implode(",",$frmBuscar['lstItemPago']) : $frmBuscar['lstItemPago']),
		(is_array($frmBuscar['lstBancoFinanciar']) ? implode(",",$frmBuscar['lstBancoFinanciar']) : $frmBuscar['lstBancoFinanciar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/an_reporte_venta_historico_pdf.php?valBusq=%s', 960, 550)", $valBusq));
	
	return $objResponse;
}

function listaFacturaVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[3] == "2") {
			$sqlBusq .= $cond.sprintf("an_ped_vent.fecha_entrega BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		} else if ($valCadBusq[3] == "3") {
			$sqlBusq .= $cond.sprintf("DATE(cxc_fact.fecha_pagada) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		} else if ($valCadBusq[3] == "4") {
			$sqlBusq .= $cond.sprintf("DATE(cxc_fact.fecha_cierre) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		} else {
			$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.aplicaLibros = %s",
			valTpDato($valCadBusq[5], "boolean"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.condicionDePago = %s",
			valTpDato($valCadBusq[6], "boolean"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.anulada IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[7])."'", "defined", "'".str_replace(",","','",$valCadBusq[7])."'"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.estadoFactura IN (%s)",
			valTpDato($valCadBusq[8], "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$arrayBusq = "";
		if (in_array(1, explode(",",$valCadBusq[9]))) { // Vehiculo
			$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_vehic2.id_factura)
									FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic2 WHERE cxc_fact_det_vehic2.id_factura = cxc_fact.idFactura) > 0");
		}
		if (in_array(2, explode(",",$valCadBusq[9]))) { // Adicionales
			$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_acc2.id_factura)
									FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc2
										INNER JOIN an_accesorio acc ON (cxc_fact_det_acc2.id_accesorio = acc.id_accesorio)
									WHERE cxc_fact_det_acc2.id_factura = cxc_fact.idFactura
										AND acc.id_tipo_accesorio IN (1)) > 0");
		}
		if (in_array(3, explode(",",$valCadBusq[9]))) { // Accesorios
			$arrayBusq[] = sprintf("(SELECT COUNT(cxc_fact_det_acc2.id_factura)
									FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc2
										INNER JOIN an_accesorio acc ON (cxc_fact_det_acc2.id_accesorio = acc.id_accesorio)
									WHERE cxc_fact_det_acc2.id_factura = cxc_fact.idFactura
										AND acc.id_tipo_accesorio IN (2)) > 0");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
	}
	
	if (in_array(1, explode(",",$valCadBusq[10]))
	|| in_array(2, explode(",",$valCadBusq[10]))
	|| in_array(3, explode(",",$valCadBusq[10]))
	|| in_array(4, explode(",",$valCadBusq[10]))
	|| in_array(5, explode(",",$valCadBusq[10]))) {
		$arrayBusq = "";
		if (in_array(1, explode(",",$valCadBusq[10]))) {
			$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = cxc_fact.idFactura
										AND cxc_pago.formaPago IN (7)
										AND cxc_pago.estatus IN (1,2)
										AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																		FROM cj_cc_anticipo cxc_ant
																			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																		WHERE cxc_pago.id_concepto IN (1,6))) > 0");
		} else if (in_array(2, explode(",",$valCadBusq[10]))) {
			$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = cxc_fact.idFactura
										AND cxc_pago.formaPago IN (7)
										AND cxc_pago.estatus IN (1,2)
										AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																		FROM cj_cc_anticipo cxc_ant
																			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																		WHERE cxc_pago.id_concepto IN (2))) > 0");
		} else if (in_array(3, explode(",",$valCadBusq[10]))) {
			$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = cxc_fact.idFactura
										AND cxc_pago.formaPago IN (7)
										AND cxc_pago.estatus IN (1,2)
										AND cxc_pago.numeroDocumento IN (SELECT cxc_ant.idAnticipo
																		FROM cj_cc_anticipo cxc_ant
																			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
																		WHERE cxc_pago.id_concepto IN (7,8,9))) > 0");
		} else if (in_array(4, explode(",",$valCadBusq[10]))) {
			$arrayBusq[] = sprintf("(SELECT COUNT(tradein_cxc.id_nota_cargo_cxc)
									FROM an_pagos cxc_pago
										INNER JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.numeroDocumento = cxc_ant.idAnticipo)
										INNER JOIN an_tradein tradein ON (cxc_ant.idAnticipo = tradein.id_anticipo)
										INNER JOIN an_tradein_cxc tradein_cxc ON (tradein.id_tradein = tradein_cxc.id_tradein
											AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL)
									WHERE cxc_pago.id_factura = cxc_fact.idFactura) > 0");
		} else if (in_array(5, explode(",",$valCadBusq[10]))) {
			$arrayBusq[] = sprintf("(SELECT COUNT(*) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = cxc_fact.idFactura
										AND cxc_pago.formaPago IN (7)
										AND cxc_pago.estatus IN (1,2)
										AND cxc_pago.numeroDocumento IN (SELECT tradein_cxc.id_anticipo FROM an_tradein_cxc tradein_cxc
																		WHERE tradein_cxc.id_anticipo IS NOT NULL
																			AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)) > 0");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
	}
	
	if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("an_ped_vent.id_banco_financiar IN (%s)",
			valTpDato($valCadBusq[11], "campo"));
	}
	
	if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR an_ped_vent.id_pedido LIKE %s
		OR an_ped_vent.numeracion_pedido LIKE %s
		OR pres_vent.id_presupuesto LIKE %s
		OR pres_vent.numeracion_presupuesto LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT(vw_iv_modelo.nom_uni_bas,': ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) LIKE %s
		OR uni_fis.placa LIKE %s
		OR poliza.nombre_poliza LIKE %s
		OR pres_acc.id_presupuesto_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
		cxc_ec.tipoDocumentoN,
		cxc_ec.tipoDocumento,
		cxc_fact.idFactura,
		cxc_fact.id_empresa,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.fechaVencimientoFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
		cxc_fact.condicionDePago AS condicion_pago,
		an_ped_vent.id_pedido,
		an_ped_vent.numeracion_pedido,
		an_ped_vent.fecha_entrega,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		vw_iv_modelo.nom_ano,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		cond_unidad.descripcion AS condicion_unidad,
		cxc_fact.estadoFactura,
		(CASE cxc_fact.estadoFactura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		banco.nombreBanco,
		poliza.nombre_poliza,
		an_ped_vent.monto_seguro,
		pres_acc.id_presupuesto_accesorio,
		vw_pg_empleado.nombre_empleado,
		cxc_fact_det_vehic.precio_unitario,
		cxc_fact_det_vehic.costo_compra,
		an_ped_vent.vexacc1 AS subtotal_accesorios,
		
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
		
		IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
				WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva,
		
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)
			+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
						WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
			+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
						WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
		
		cxc_fact.saldoFactura,
		cxc_fact.anulada,
		cxc_fact.fecha_pagada,
		cxc_fact.fecha_cierre,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN an_presupuesto_accesorio pres_acc ON (an_ped_vent.id_presupuesto = pres_acc.id_presupuesto)
		LEFT JOIN an_poliza poliza ON (an_ped_vent.id_poliza = poliza.id_poliza)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"4\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "8%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "CONVERT(numeracion_pedido, SIGNED)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "CONVERT(numeracion_presupuesto, SIGNED)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "nom_ano", $campOrd, $tpOrd, $valBusq, $maxRows, "Año Vehículo");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Entidad Bancaria");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "descripcion_estado_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "nombre_poliza", $campOrd, $tpOrd, $valBusq, $maxRows, "Seguro");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "monto_seguro", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto del Seguro");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "subtotal_accesorios", $campOrd, $tpOrd, $valBusq, $maxRows, "Subtotal Accesorios");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "4%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['id_pedido'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
			
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>" : "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
			
		switch ($row['flotilla']) {
			case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
			case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
			default : $imgEstatusUnidadAsignacion = "";
		}
		
		switch($row['estadoFactura']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
		$objDcto->tipoDocumento = "FA";
		$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $row['id_modulo'];
		$objDcto->idDocumento = $row['idFactura'];
		$aVerDcto = $objDcto->verDocumento();
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroFactura'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".sprintf("<img class=\"puntero\" onclick=\"verVentana('an_ventas_pedido_editar.php?view=view&id=%s', 960, 550);\" src=\"../img/iconos/page_red.png\" title=\"Pedido de Venta\"/>",
						$row['id_pedido'])."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeracion_pedido'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".(($row['id_presupuesto_accesorio'] > 0) ? sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s', 960, 550);\" src=\"../img/iconos/page.png\" title=\"Presupuesto Accesorio\"/>",
						$row['id_presupuesto_accesorio']) : "")."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeracion_presupuesto'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nom_ano'])."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".htmlentities($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicion_pago'] == 0) ? "divMsjAlerta" : "divMsjInfo")."\">";
				$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class." nowrap=\"nowrap\">".$row['descripcion_estado_factura']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['serial_carroceria'])."</div>";
				$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode($row['condicion_unidad'])."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".htmlentities($row['nombre_poliza'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_seguro'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['subtotal_accesorios'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[16] += $row['monto_seguro'];
		$arrayTotal[17] += $row['subtotal_accesorios'];
		$arrayTotal[18] += $row['saldoFactura'];
		$arrayTotal[19] += $row['total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"19\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[16],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[17],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[18],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[19],2)."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[16] += $row['monto_seguro'];
				$arrayTotalFinal[17] += $row['subtotal_accesorios'];
				$arrayTotalFinal[18] += $row['saldoFactura'];
				$arrayTotalFinal[19] += $row['total'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"19\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[16],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[17],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[18],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[19],2)."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaFacturaVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_iva'];
		$totalFacturas += $row['total'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalFacturas, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnuladaFactura");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstItemFactura");
$xajax->register(XAJAX_FUNCTION,"cargaLstItemPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoFecha");
$xajax->register(XAJAX_FUNCTION,"exportarFacturaVenta");
$xajax->register(XAJAX_FUNCTION,"imprimirFacturaVenta");
$xajax->register(XAJAX_FUNCTION,"listaFacturaVenta");
?>