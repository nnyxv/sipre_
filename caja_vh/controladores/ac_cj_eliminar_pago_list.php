<?php


function buscarPago($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstFormaPago'])) ? implode(",",$frmBuscar['lstFormaPago']) : $frmBuscar['lstFormaPago'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPago(0, "query.fechaPago", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstFormaPago($idFormaPago = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$idFormaPago = (is_array($idFormaPago)) ? implode(",",$idFormaPago) : $idFormaPago;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia, 5 = Tarjeta de Credito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito
	// 9 = Retencion, 10 = Retencion I.S.L.R., 11 = Otro
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idFormaPago NOT IN (7,8,9,10)",
		valTpDato($idFormaPago, "campo"));*/
	
	if ($idFormaPago != "-1" && $idFormaPago != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idFormaPago IN (%s)",
			valTpDato($idFormaPago, "campo"));
	}
	
	$query = sprintf("SELECT * FROM formapagos %s ORDER BY nombreFormaPago ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstFormaPago\" name=\"lstFormaPago\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPago($row["idFormaPago"])); }
		
		$html .= "<option ".$selected." value=\"".$row["idFormaPago"]."\">".$row["nombreFormaPago"]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstFormaPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarPagina($idEmpresa){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	if ($rowConfig400['valor'] == 0) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"selectedOption(this.id,'".$idEmpresa."');\""));
	} else {
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa));
	}
	
	$objResponse->script("xajax_buscarPago(xajax.getFormValues('frmBuscar'));");
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	return $objResponse;
}

function eliminarPago($idPago, $tablaPago, $frmListaPago) {
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if ((!xvalidaAcceso($objResponse,"cj_eliminar_pago_list","eliminar") && in_array($idCajaPpal, array(1)))
	|| (!xvalidaAcceso($objResponse,"cjrs_eliminar_pago_list","eliminar") && in_array($idCajaPpal, array(2)))) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL PAGO
	switch ($tablaPago) {
		case "an_pagos" :
			$queryPago = sprintf("SELECT
				cxc_pago.idPago,
				cxc_fact.idFactura AS id_documento_pagado,
				cxc_fact.id_empresa,
				cxc_fact.idCliente,
				cxc_pago.fechaPago,
				cxc_pago.formaPago AS id_forma_pago,
				NULL AS id_concepto,
				(CASE
					WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
						cxc_pago.id_cheque
					WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
						cxc_pago.id_transferencia
					ELSE
						cxc_pago.numeroDocumento
				END) AS id_documento_pago,
				cxc_pago.id_cheque,
				cxc_pago.id_transferencia,
				cxc_pago.idCaja,
				cxc_pago.montoPagado AS monto_pago,
				'an_pagos' AS tabla,
				'idPago' AS campo_id_pago
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			WHERE cxc_pago.idPago = %s
				AND cxc_pago.idCaja = %s
				AND cxc_pago.estatus IS NOT NULL;",
				valTpDato($idPago, "int"),
				valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			break;
		case "sa_iv_pagos" :
			$queryPago = sprintf("SELECT
				cxc_pago.idPago,
				cxc_fact.idFactura AS id_documento_pagado,
				cxc_fact.id_empresa,
				cxc_fact.idCliente,
				cxc_pago.fechaPago,
				cxc_pago.formaPago AS id_forma_pago,
				NULL AS id_concepto,
				(CASE
					WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
						cxc_pago.id_cheque
					WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
						cxc_pago.id_transferencia
					ELSE
						cxc_pago.numeroDocumento
				END) AS id_documento_pago,
				cxc_pago.id_cheque,
				cxc_pago.id_transferencia,
				cxc_pago.idCaja,
				cxc_pago.montoPagado AS monto_pago,
				'sa_iv_pagos' AS tabla,
				'idPago' AS campo_id_pago
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			WHERE cxc_pago.idPago = %s
				AND cxc_pago.idCaja = %s
				AND cxc_pago.estatus IS NOT NULL;",
				valTpDato($idPago, "int"),
				valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			break;
		case "cj_det_nota_cargo" :
			$queryPago = sprintf("SELECT
				cxc_pago.id_det_nota_cargo AS idPago,
				cxc_nd.idNotaCargo AS id_documento_pagado,
				cxc_nd.id_empresa,
				cxc_nd.idCliente,
				cxc_pago.fechaPago,
				cxc_pago.idFormaPago AS id_forma_pago,
				NULL AS id_concepto,
				(CASE
					WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
						cxc_pago.id_cheque
					WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
						cxc_pago.id_transferencia
					ELSE
						cxc_pago.numeroDocumento
				END) AS id_documento_pago,
				cxc_pago.id_cheque,
				cxc_pago.id_transferencia,
				cxc_pago.idCaja,
				cxc_pago.monto_pago,
				'cj_det_nota_cargo' AS tabla,
				'id_det_nota_cargo' AS campo_id_pago
			FROM cj_cc_notadecargo cxc_nd
				INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			WHERE cxc_pago.id_det_nota_cargo = %s
				AND cxc_pago.idCaja = %s
				AND cxc_pago.estatus IS NOT NULL;",
				valTpDato($idPago, "int"),
				valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			break;
		case "cj_cc_detalleanticipo" :
			$queryPago = sprintf("SELECT
				cxc_pago.idDetalleAnticipo AS idPago,
				cxc_ant.idAnticipo AS id_documento_pagado,
				cxc_ant.id_empresa,
				cxc_ant.idCliente,
				cxc_pago.fechaPagoAnticipo AS fechaPago,
				cxc_pago.id_forma_pago,
				cxc_pago.id_concepto AS id_concepto,
				(CASE
					WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
						cxc_pago.id_cheque
					WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
						cxc_pago.id_transferencia
					ELSE
						cxc_pago.numeroControlDetalleAnticipo
				END) AS id_documento_pago,
				cxc_pago.id_cheque,
				cxc_pago.id_transferencia,
				cxc_pago.idCaja,
				cxc_pago.montoDetalleAnticipo AS monto_pago,
				'cj_cc_detalleanticipo' AS tabla,
				'idDetalleAnticipo' AS campo_id_pago
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			WHERE cxc_pago.idDetalleAnticipo = %s
				AND cxc_pago.idCaja = %s
				AND cxc_pago.estatus IS NOT NULL;",
				valTpDato($idPago, "int"),
				valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			break;
	}
	$rsPago = mysql_query($queryPago);
	if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPago = mysql_num_rows($rsPago);
	$rowPago = mysql_fetch_assoc($rsPago);
	
	$idEmpresa = $rowPago['id_empresa'];
	$idCaja = $rowPago['idCaja'];
	$idDocumentoPagado = $rowPago['id_documento_pagado'];
	$idDocumentoPago = $rowPago['id_documento_pago'];
	$fechaPago = $rowPago['fechaPago'];
	$tablaPago = $rowPago['tabla'];
	$campoIdPago = $rowPago['campo_id_pago'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
	$queryAperturaCaja = sprintf("SELECT *,
		(CASE ape.statusAperturaCaja
			WHEN 0 THEN 'CERRADA TOTALMENTE'
			WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
			WHEN 2 THEN 'CERRADA PARCIALMENTE'
			ELSE 'CERRADA TOTALMENTE'
		END) AS estatus_apertura_caja
	FROM ".$apertCajaPpal." ape
		INNER JOIN caja ON (ape.idCaja = caja.idCaja)
		LEFT JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
	WHERE caja.idCaja = %s
		AND ape.statusAperturaCaja IN (1,2)
		AND (ape.id_empresa = %s
			OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s));",
		valTpDato(spanDatePick, "date"),
		valTpDato($idCaja, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsAperturaCaja = mysql_query($queryAperturaCaja);
	if (!$rsAperturaCaja) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
	
	$idApertura = $rowAperturaCaja['id'];
	$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
	
	// ANULA EL PAGO
	$udpateSQL = sprintf("UPDATE %s SET
		estatus = NULL,
		fecha_anulado = %s,
		id_empleado_anulado = %s
	WHERE %s = %s;",
		valTpDato($tablaPago, "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($campoIdPago, "campo"),
		valTpDato($idPago, "int"));
	$Result1 = mysql_query($udpateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	switch ($rowPago['id_forma_pago']) {
		case 1 : // 1 = Efectivo
			$campo = "saldoEfectivo";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = $txtMonto;
			break;
		case 2 : // 2 = Cheque
			if ($rowPago['id_cheque'] > 0) {
				$campo = "";
				$txtMonto = 0;
				$txtMontoSaldoCaja = 0;
			} else {
				$campo = "saldoCheques";
				$txtMonto = $rowPago['monto_pago'];
				$txtMontoSaldoCaja = $txtMonto;
			} break;
		case 3 : // 3 = Deposito
			$campo = "saldoDepositos";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = $txtMonto;
			break;
		case 4 : // 4 = Transferencia
			if ($rowPago['id_transferencia'] > 0) {
				$campo = "";
				$txtMonto = 0;
				$txtMontoSaldoCaja = 0;
			} else {
				$campo = "saldoTransferencia";
				$txtMonto = $rowPago['monto_pago'];
				$txtMontoSaldoCaja = $txtMonto;
			}
			break;
		case 5 : // 5 = Tarjeta de Crédito
			$campo = "saldoTarjetaCredito";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = $txtMonto;
			break;
		case 6 : // 6 = Tarjeta de Debito
			$campo = "saldoTarjetaDebito";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = $txtMonto;
			break;
		case 7 : // 7 = Anticipo
			$campo = "saldoAnticipo";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = 0;
			break;
		case 8 : // 8 = Nota de Crédito
			$campo = "saldoNotaCredito";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = $txtMonto;
			break;
		case 9 : // 9 = Retencion
			$campo = "saldoRetencion";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = $txtMonto;
			break;
		case 10 : // 10 = Retencion ISLR
			$campo = "saldoRetencionISLR";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = $txtMonto;
			break;
		case 11 : // 11 = Otro
			$campo = "saldoOtro";
			$txtMonto = $rowPago['monto_pago'];
			$txtMontoSaldoCaja = (in_array($rowPago['id_concepto'], array(7,8,9))) ? 0 : $txtMonto;
			break;
	}
	
	// ACTUALIZA LOS SALDOS EN LA APERTURA (NO TOMA EN CUENTA 7 = Anticipo EN EL SALDO DE LA CAJA)
	if ($fechaPago == $fechaRegistroPago && strlen($campo) > 0) {
		$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
			%s = %s - %s,
			saldoCaja = saldoCaja - %s
		WHERE id = %s;",
			$campo, $campo, valTpDato($txtMonto, "real_inglesa"),
			valTpDato($txtMontoSaldoCaja, "real_inglesa"),
			valTpDato($rowAperturaCaja['id'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	if ($rowPago['id_forma_pago'] == 2) { // 2 = Cheque
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarCheque($idDocumentoPago);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
	} else if ($rowPago['id_forma_pago'] == 3) { // 3 = Deposito
		if (in_array($tablaPago, array("an_pagos","sa_iv_pagos"))) {
			$deleteSQL = sprintf("DELETE FROM an_det_pagos_deposito_factura
			WHERE idPago = %s
				AND idCaja = %s;",
				valTpDato($idPago, "int"),
				valTpDato($idCaja, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else if (in_array($tablaPago, array("cj_det_nota_cargo"))) {
			$deleteSQL = sprintf("DELETE FROM cj_det_pagos_deposito_nota_cargo
			WHERE id_det_nota_cargo = %s
				AND idCaja = %s;",
				valTpDato($idPago, "int"),
				valTpDato($idCaja, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else if (in_array($tablaPago, array("cj_cc_detalleanticipo"))) {
			$deleteSQL = sprintf("DELETE FROM cj_cc_det_pagos_deposito_anticipos
			WHERE idDetalleAnticipo = %s
				AND idCaja = %s;",
				valTpDato($idPago, "int"),
				valTpDato($idCaja, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	} else if ($rowPago['id_forma_pago'] == 4) { // 4 = Transferencia
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarTransferencia($idDocumentoPago);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
	} else if (in_array($rowPago['id_forma_pago'], array(5,6))) { // 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito
		if (in_array($tablaPago, array("an_pagos","sa_iv_pagos","cj_det_nota_cargo","cj_cc_detalleanticipo"))) {
			$deleteSQL = sprintf("DELETE FROM cj_cc_retencion_punto_pago
			WHERE id_pago = %s
				AND id_caja = %s;",
				valTpDato($idPago, "int"),
				valTpDato($idCaja, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	} else if ($rowPago['id_forma_pago'] == 7) { // 7 = Anticipo
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarAnticipo($idDocumentoPago);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
	} else if ($rowPago['id_forma_pago'] == 8) { // 8 = Nota de Crédito
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarNotaCredito($idDocumentoPago);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
	} else if ($rowPago['id_forma_pago'] == 9) { // 9 = Retencion
		if (in_array($tablaPago, array("an_pagos","sa_iv_pagos"))) {
			$deleteSQL = sprintf("DELETE FROM cj_cc_retencioncabezera
			WHERE numeroComprobante = %s
				AND idCliente = %s;",
				valTpDato($idDocumentoPago, "int"),
				valTpDato($rowPago['idCliente'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// ACTUALIZA EL SALDO Y ESTADO DEL DOCUMENTO
	switch ($tablaPago) {
		case "an_pagos" :
			$objDcto = new Documento;
			$Result1 = $objDcto->actualizarFactura($idDocumentoPagado);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			break;
		case "sa_iv_pagos" :
			$objDcto = new Documento;
			$Result1 = $objDcto->actualizarFactura($idDocumentoPagado);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			break;
		case "cj_det_nota_cargo" :
			$objDcto = new Documento;
			$Result1 = $objDcto->actualizarNotaDebito($idDocumentoPagado);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			break;
		case "cj_cc_detalleanticipo" :
			$objDcto = new Documento;
			$Result1 = $objDcto->actualizarAnticipo($idDocumentoPagado);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			break;
	}
	
	if ($rowPago['id_forma_pago'] == 7) { // 7 = Anticipo
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_anticipo = %s;",
			valTpDato($idDocumentoPago, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			$queryPago = sprintf("SELECT *
			FROM (SELECT 
					cxc_pago.idPago,
					cxc_pago.id_factura AS id_documento_pagado,
					cxc_pago.formaPago AS id_forma_pago,
					(CASE
						WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
							cxc_pago.id_cheque
						WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
							cxc_pago.id_transferencia
						ELSE
							cxc_pago.numeroDocumento
					END) AS id_documento_pago,
					'an_pagos' AS tabla,
					'idPago' AS campo_id_pago
				FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (8)
					AND cxc_pago.estatus IS NOT NULL
				
				UNION
			
				SELECT 
					cxc_pago.idPago,
					cxc_pago.id_factura AS id_documento_pagado,
					cxc_pago.formaPago AS id_forma_pago,
					(CASE
						WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
							cxc_pago.id_cheque
						WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
							cxc_pago.id_transferencia
						ELSE
							cxc_pago.numeroDocumento
					END) AS id_documento_pago,
					'sa_iv_pagos' AS tabla,
					'idPago' AS campo_id_pago
				FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (8)
					AND cxc_pago.estatus IS NOT NULL
				
				UNION
				
				SELECT
					cxc_pago.id_det_nota_cargo AS idPago,
					cxc_pago.idNotaCargo AS id_documento_pagado,
					cxc_pago.idFormaPago AS id_forma_pago,
					(CASE
						WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
							cxc_pago.id_cheque
						WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
							cxc_pago.id_transferencia
						ELSE
							cxc_pago.numeroDocumento
					END) AS id_documento_pago,
					'cj_det_nota_cargo' AS tabla,
					'id_det_nota_cargo' AS campo_id_pago
				FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (8)
					AND cxc_pago.estatus IS NOT NULL
				
				UNION
				
				SELECT
					cxc_pago.idDetalleAnticipo AS idPago,
					cxc_pago.idAnticipo AS id_documento_pagado,
					cxc_pago.id_forma_pago,
					(CASE
						WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
							cxc_pago.id_cheque
						WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
							cxc_pago.id_transferencia
						ELSE
							cxc_pago.numeroControlDetalleAnticipo
					END) AS id_documento_pago,
					'cj_cc_detalleanticipo' AS tabla,
					'idDetalleAnticipo' AS campo_id_pago
				FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (8)
					AND cxc_pago.estatus IS NOT NULL) AS q
			WHERE q.id_documento_pagado = %s;",
				valTpDato($idDocumentoPagado, "int"));
			$rsPago = mysql_query($queryPago);
			if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsPago = mysql_num_rows($rsPago);
			while ($rowPago = mysql_fetch_array($rsPago)) {
				if ($rowPago['id_forma_pago'] == 8 && $rowPago['id_documento_pago'] == $rowTradeInNotaCredito['id_nota_credito_cxc']) {
					$objResponse->script("xajax_eliminarPago('".$rowPago['idPago']."', '".$rowPago['tabla']."', xajax.getFormValues('frmListaPago'));");
				}
			}
		}
	} else if ($rowPago['id_forma_pago'] == 8) { // 8 = Nota de Crédito
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_nota_credito_cxc = %s;",
			valTpDato($idDocumentoPago, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			$queryPago = sprintf("SELECT *
			FROM (SELECT 
					cxc_pago.idPago,
					cxc_pago.id_factura AS id_documento_pagado,
					cxc_pago.formaPago AS id_forma_pago,
					(CASE
						WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
							cxc_pago.id_cheque
						WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
							cxc_pago.id_transferencia
						ELSE
							cxc_pago.numeroDocumento
					END) AS id_documento_pago,
					'an_pagos' AS tabla,
					'idPago' AS campo_id_pago
				FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (7)
					AND cxc_pago.estatus IS NOT NULL
				
				UNION
				
				SELECT 
					cxc_pago.idPago,
					cxc_pago.id_factura AS id_documento_pagado,
					cxc_pago.formaPago AS id_forma_pago,
					(CASE
						WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
							cxc_pago.id_cheque
						WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
							cxc_pago.id_transferencia
						ELSE
							cxc_pago.numeroDocumento
					END) AS id_documento_pago,
					'sa_iv_pagos' AS tabla,
					'idPago' AS campo_id_pago
				FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (7)
					AND cxc_pago.estatus IS NOT NULL
				
				UNION
				
				SELECT
					cxc_pago.id_det_nota_cargo AS idPago,
					cxc_pago.idNotaCargo AS id_documento_pagado,
					cxc_pago.idFormaPago AS id_forma_pago,
					(CASE
						WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
							cxc_pago.id_cheque
						WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
							cxc_pago.id_transferencia
						ELSE
							cxc_pago.numeroDocumento
					END) AS id_documento_pago,
					'cj_det_nota_cargo' AS tabla,
					'id_det_nota_cargo' AS campo_id_pago
				FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (7)
					AND cxc_pago.estatus IS NOT NULL
				
				UNION
				
				SELECT
					cxc_pago.idDetalleAnticipo AS idPago,
					cxc_pago.idAnticipo AS id_documento_pagado,
					cxc_pago.id_forma_pago,
					(CASE
						WHEN (cxc_pago.id_cheque IS NOT NULL) THEN
							cxc_pago.id_cheque
						WHEN (cxc_pago.id_transferencia IS NOT NULL) THEN
							cxc_pago.id_transferencia
						ELSE
							cxc_pago.numeroControlDetalleAnticipo
					END) AS id_documento_pago,
					'cj_cc_detalleanticipo' AS tabla,
					'idDetalleAnticipo' AS campo_id_pago
				FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (7)
					AND cxc_pago.estatus IS NOT NULL) AS q
			WHERE q.id_documento_pagado = %s;",
				valTpDato($idDocumentoPagado, "int"));
			$rsPago = mysql_query($queryPago);
			if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsPago = mysql_num_rows($rsPago);
			while ($rowPago = mysql_fetch_array($rsPago)) {
				if ($rowPago['id_forma_pago'] == 7 && $rowPago['id_documento_pago'] == $rowTradeInNotaCredito['id_anticipo']) {
					$objResponse->script("xajax_eliminarPago('".$rowPago['idPago']."', '".$rowPago['tabla']."', xajax.getFormValues('frmListaPago'));");
				}
			}
		}
	}
			
	// VERIFICA SI EL ANTICIPO FUE APLICADO COMO FORMA DE PAGO A ALGUN DOCUMENTO
	switch ($tablaPago) {
		case "cj_cc_detalleanticipo" :
			// BUSCA LOS DOCUMENTOS PAGADOS
			$query = sprintf("SELECT
				cxc_pago.idPago AS id_pago,
				cxc_pago.id_factura,
				NULL AS id_nota_cargo,
				NULL AS id_anticipo,
				cxc_pago.idCaja,
				cxc_pago.montoPagado,
				cxc_pago.estatus,
				'an_pagos' AS tabla
			FROM an_pagos cxc_pago
			WHERE cxc_pago.formaPago IN (7)
				AND cxc_pago.numeroDocumento = %s
			
			UNION
			
			SELECT
				cxc_pago.idPago,
				cxc_pago.id_factura,
				NULL AS id_nota_cargo,
				NULL AS id_anticipo,
				cxc_pago.idCaja,
				cxc_pago.montoPagado,
				cxc_pago.estatus,
				'sa_iv_pagos' AS tabla
			FROM sa_iv_pagos cxc_pago
			WHERE cxc_pago.formaPago IN (7)
				AND cxc_pago.numeroDocumento = %s
			
			UNION
			
			SELECT
				cxc_pago.id_det_nota_cargo,
				NULL AS id_factura,
				cxc_pago.idNotaCargo,
				NULL AS id_anticipo,
				cxc_pago.idCaja,
				cxc_pago.monto_pago,
				cxc_pago.estatus,
				'cj_det_nota_cargo' AS tabla
			FROM cj_det_nota_cargo cxc_pago
			WHERE cxc_pago.idFormaPago IN (7)
				AND cxc_pago.numeroDocumento = %s
			
			UNION
			
			SELECT
				cxc_pago.idDetalleAnticipo,
				NULL AS id_factura,
				NULL AS idNotaCargo,
				cxc_pago.idAnticipo,
				cxc_pago.idCaja,
				cxc_pago.montoDetalleAnticipo,
				cxc_pago.estatus,
				'cj_cc_detalleanticipo' AS tabla
			FROM cj_cc_detalleanticipo cxc_pago
			WHERE cxc_pago.id_forma_pago IN (7)
				AND cxc_pago.numeroControlDetalleAnticipo = %s;",
				valTpDato($idDocumentoPagado, "int"),
				valTpDato($idDocumentoPagado, "int"),
				valTpDato($idDocumentoPagado, "int"),
				valTpDato($idDocumentoPagado, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($row = mysql_fetch_assoc($rs)) {
				$objResponse->loadCommands(eliminarPago($row['id_pago'], $row['tabla'], $frmListaPago));
			}
		break;
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Pago eliminado exitosamente");
	
	$objResponse->loadCommands(listaPago(
		$frmListaPago['pageNum'],
		$frmListaPago['campOrd'],
		$frmListaPago['tpOrd'],
		$frmListaPago['valBusq']));
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	return $objResponse;
}

function listaPago($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
	$queryAperturaCaja = sprintf("SELECT *,
		(CASE ape.statusAperturaCaja
			WHEN 0 THEN 'CERRADA TOTALMENTE'
			WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
			WHEN 2 THEN 'CERRADA PARCIALMENTE'
			ELSE 'CERRADA TOTALMENTE'
		END) AS estatus_apertura_caja
	FROM ".$apertCajaPpal." ape
		INNER JOIN caja ON (ape.idCaja = caja.idCaja)
		LEFT JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
	WHERE caja.idCaja = %s
		AND ape.statusAperturaCaja IN (1,2)
		AND (ape.id_empresa = %s
			OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s));",
		valTpDato(spanDatePick, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	$rsAperturaCaja = mysql_query($queryAperturaCaja);
	if (!$rsAperturaCaja) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
	
	$fechaApertura = $rowAperturaCaja['fechaAperturaCaja'];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((cxc_pago.fechaPago = %s
		AND cxc_pago.idCaja = %s
		AND (cxc_pago.tomadoEnCierre IN (0) OR (cxc_pago.tomadoEnCierre IN (2) AND (id_cheque IS NOT NULL OR id_transferencia IS NOT NULL)))
		AND (cxc_pago.idCierre IS NULL OR cxc_pago.idCierre = 0))
	OR (cxc_pago.fechaPago <> %s
		AND cxc_pago.idCaja = %s
		AND cxc_pago.estatus IN (2)))",
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("((cxc_pago.fechaPago = %s
		AND cxc_pago.idCaja = %s
		AND (cxc_pago.tomadoEnCierre IN (0) OR (cxc_pago.tomadoEnCierre IN (2) AND (id_cheque IS NOT NULL OR id_transferencia IS NOT NULL)))
		AND (cxc_pago.idCierre IS NULL OR cxc_pago.idCierre = 0))
	OR (cxc_pago.fechaPago <> %s
		AND cxc_pago.idCaja = %s
		AND cxc_pago.estatus IN (2)))",
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("((cxc_pago.fechaPago = %s
		AND cxc_pago.idCaja = %s
		AND (cxc_pago.tomadoEnCierre IN (0) OR (cxc_pago.tomadoEnCierre IN (2) AND (id_cheque IS NOT NULL OR id_transferencia IS NOT NULL)))
		AND (cxc_pago.idCierre IS NULL OR cxc_pago.idCierre = 0))
	OR (cxc_pago.fechaPago <> %s
		AND cxc_pago.idCaja = %s
		AND cxc_pago.estatus IN (2)))",
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("((cxc_pago.fechaPagoAnticipo = %s
		AND cxc_pago.idCaja = %s
		AND (cxc_pago.tomadoEnCierre IN (0) OR (cxc_pago.tomadoEnCierre IN (2) AND (id_cheque IS NOT NULL OR id_transferencia IS NOT NULL)))
		AND (cxc_pago.idCierre IS NULL OR cxc_pago.idCierre = 0 OR cxc_pago.idCierre = -1))
	OR (cxc_pago.fechaPagoAnticipo <> %s
		AND cxc_pago.idCaja = %s
		AND cxc_pago.estatus IN (2)))",
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(cxc_nd.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nd.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
			
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("(cxc_ant.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_ant.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pago.fechaPago BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxc_pago.fechaPago BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_pago.fechaPago BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_pago.fechaPagoAnticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pago.formaPago IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxc_pago.formaPago IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_pago.idFormaPago IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_pago.id_forma_pago IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR recibo.numeroComprobante LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR recibo.numeroComprobante LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(cxc_nd.numeroNotaCargo LIKE %s
		OR cxc_nd.numeroControlNotaCargo LIKE %s
		OR recibo.numeroComprobante LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("(cxc_ant.numeroAnticipo LIKE %s
		OR recibo.numeroReporteImpresion LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT query.*
	FROM (
		SELECT 
			cxc_pago.idPago,
			1 AS idTipoDeDocumento,
			'FA' AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipo_documento_pagado,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo_documento_pagado,
			cxc_fact.idFactura AS id_documento_pagado,
			cxc_fact.numeroFactura AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			cxc_pago.formaPago,
			forma_pago.nombreFormaPago,
			NULL AS id_concepto,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
					FROM cj_cc_detalleanticipo cxc_pago
						INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
					WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
						AND cxc_pago.id_forma_pago IN (11))
			END) AS descripcion_concepto_forma_pago,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento_pago,
			cxc_pago.bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'an_pagos' AS tabla,
			'idPago' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		
		UNION
		
		SELECT 
			cxc_pago.idPago,
			1 AS idTipoDeDocumento,
			'FA' AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipo_documento_pagado,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo_documento_pagado,
			cxc_fact.idFactura AS id_documento_pagado,
			cxc_fact.numeroFactura AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			cxc_pago.formaPago,
			forma_pago.nombreFormaPago,
			NULL AS id_concepto,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
					FROM cj_cc_detalleanticipo cxc_pago
						INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
					WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
						AND cxc_pago.id_forma_pago IN (11))
			END) AS descripcion_concepto_forma_pago,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento_pago,
			cxc_pago.bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'sa_iv_pagos' AS tabla,
			'idPago' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		
		UNION
		
		SELECT 
			cxc_pago.id_det_nota_cargo AS idPago,
			2 AS idTipoDeDocumento,
			'ND' AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 2) AS tipo_documento_pagado,
			cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo_documento_pagado,
			cxc_nd.idNotaCargo AS id_documento_pagado,
			cxc_nd.numeroNotaCargo AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			cxc_pago.idFormaPago AS formaPago,
			forma_pago.nombreFormaPago,
			NULL AS id_concepto,
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
					FROM cj_cc_detalleanticipo cxc_pago
						INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
					WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
						AND cxc_pago.id_forma_pago IN (11))
			END) AS descripcion_concepto_forma_pago,
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento_pago,
			cxc_pago.bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.monto_pago AS montoPagado,
			cxc_pago.estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'cj_det_nota_cargo' AS tabla,
			'id_det_nota_cargo' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			INNER JOIN formapagos forma_pago on (cxc_pago.idFormaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_nd.idDepartamentoOrigenNotaCargo = recibo.id_departamento AND recibo.idTipoDeDocumento = 2)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		
		UNION
		
		SELECT 
			cxc_pago.idDetalleAnticipo AS idPago,
			4 AS idTipoDeDocumento,
			'AN' AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 4) AS tipo_documento_pagado,
			cxc_ant.idDepartamento AS id_modulo_documento_pagado,
			cxc_ant.idAnticipo AS id_documento_pagado,
			cxc_ant.numeroAnticipo AS numero_documento,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPagoAnticipo AS fechaPago,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			cxc_pago.id_forma_pago AS formaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.id_concepto AS id_concepto,
			concepto_forma_pago.descripcion AS descripcion_concepto_forma_pago,
			cxc_pago.numeroControlDetalleAnticipo AS numero_documento_pago,
			cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
			banco_origen.nombreBanco AS nombre_banco_origen,
			cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
			banco_destino.nombreBanco AS nombre_banco_destino,
			cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoDetalleAnticipo AS montoPagado,
			cxc_ant.estatus AS estatus,
			cxc_pago.estatus AS estatus_pago,
			DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
			'cj_cc_detalleanticipo' AS tabla,
			'idDetalleAnticipo' AS campo_id_pago,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			INNER JOIN formapagos forma_pago on (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen on (cxc_pago.bancoClienteDetalleAnticipo = banco_origen.idBanco)
			INNER JOIN bancos banco_destino on (cxc_pago.bancoCompaniaDetalleAnticipo = banco_destino.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion AND cxc_ant.idDepartamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'AN')
			LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		) AS query", $sqlBusq, $sqlBusq2, $sqlBusq3, $sqlBusq4);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPago", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPago", "6%", $pageNum, "tipo_documento_pagado", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaPago", "8%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto. Pagado");
		$htmlTh .= ordenarCampo("xajax_listaPago", "26%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPago", "6%", $pageNum, "fechaPago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Pago");
		$htmlTh .= ordenarCampo("xajax_listaPago", "8%", $pageNum, "nro_comprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Recibo");
		$htmlTh .= ordenarCampo("xajax_listaPago", "10%", $pageNum, "nombreFormaPago", $campOrd, $tpOrd, $valBusq, $maxRows, "Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPago", "8%", $pageNum, "numero_documento_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPago", "10%", $pageNum, "montoPagado", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Pagado");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo_documento_pagado']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo_documento_pagado'];
		}
		
		switch ($row['idTipoDeDocumento']) {
			case 1 : // 1 = Factura
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
					$row['id_documento_pagado']);
				switch ($row['id_modulo_documento_pagado']) {
					case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_factura_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					default : $aVerDctoAux = "";
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Factura Venta PDF")."\"/></a>" : "";
				break;
			case 2 : // 2 = Nota de Débito
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_debito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/><a>",
					$row['id_documento_pagado']);
				$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/><a>",
					$row['id_documento_pagado']);
				break;
			case 3 : // 3 = Nota de Crédito
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/><a>",
					$row['id_documento_pagado']);
				switch ($row['id_modulo_documento_pagado']) {
					case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $row['id_documento_pagado']); break;
					default : $aVerDctoAux = "";
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
				break;
			case 4 : // 4 = Anticipo
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_anticipo_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Anticipo")."\"/><a>",
					$row['id_documento_pagado']);
				if (in_array($row['id_modulo_documento_pagado'],array(2,4,5))) {
					$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s", $row['id_documento_pagado']);
				} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
					$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s", $row['id_documento_pagado']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			case 5 : // 5 = Cheque
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_cheque_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Cheque")."\"/><a>",
					$row['id_documento_pagado']);
				if (in_array($row['id_modulo_documento_pagado'],array(2,4,5))) {
					$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=5&id=%s", $row['id_documento_pagado']);
				} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
					$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=5&id=%s", $row['id_documento_pagado']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			case 6 : // 6 = Transferencia
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_transferencia_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Transferencia")."\"/><a>",
					$row['id_documento_pagado']);
				if (in_array($row['id_modulo_documento_pagado'],array(2,4,5))) {
					$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=6&id=%s", $row['id_documento_pagado']);
				} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
					$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s", $row['id_documento_pagado']);
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
				break;
			default : $aVerDcto = "";
		}
		
		switch($row['estatus_pago']) {
			case 1 : $classPago = ""; $estatusPago = ""; break;
			case 2 : $classPago = "divMsjAlerta"; $estatusPago = "PAGO PENDIENTE"; break;
			default : $classPago = "divMsjError"; $estatusPago = "PAGO ANULADO"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".($row['tipo_documento_pagado'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$imgDctoModulo."</td>";
					$htmlTb .= "<td width=\"100%\">".$row['numero_documento']."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaPago']))."</td>";
			$htmlTb .= "<td>";
			switch ($row['idTipoDeDocumento']) {
				case 1 : // 1 = Factura
					if (in_array($row['id_modulo_documento_pagado'],array(2,4,5))) {
						$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
						$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					}
					$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
					break;
				case 2 : // 2 = Nota de Débito
					if (in_array($row['id_modulo_documento_pagado'],array(2,4,5))) {
						$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
						$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					}
					$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
					break;
				case 4 : // 4 = Anticipo
					if (in_array($row['id_modulo_documento_pagado'],array(2,4,5))) {
						$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
						$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					}
					$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
					break;
				case 5 : // 5 = Cheque
					if (in_array($row['id_modulo_documento_pagado'],array(2,4,5))) {
						$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
						$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					}
					$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
					break;
				case 6 : // 6 = Transferencia
					if (in_array($row['id_modulo_documento_pagado'],array(2,4,5))) {
						$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					} else if (in_array($row['id_modulo_documento_pagado'],array(0,1,3))) {
						$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $row['id_recibo_pago']);
					}
					$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
					break;
				default : $aVerDcto = "";
			}
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".$row['nro_comprobante']."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td class=\"".$classPago."\">";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"center\">";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombreFormaPago'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<tr align=\"center\"><td class=\"textoNegrita_9px\">(".utf8_encode($row['descripcion_concepto_forma_pago']).")</td></tr>" : "";
				$htmlTb .= (strlen($estatusPago) > 0) ? "<tr align=\"center\"><td class=\"textoNegritaCursiva_9px\">".$estatusPago."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_documento_pago'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoPagado'],2,".",",")."</td>";
			$htmlTb .= "<td>";
			if (in_array($row['estatus_pago'], array(1,2))) {
				if (in_array($row['id_modulo_documento_pagado'], array(2,4,5))){ // 2 = Vehiculos, 4 = Alquiler, 5 = Financiamiento
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDesbloquearPago%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPermiso', 'cj_eliminar_pagos');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>",
						$contFila);
				} else if (in_array($row['id_modulo_documento_pagado'], array(0,1,3))){ // 0 = Repuestos, 1 = Servicios, 3 = Administración
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDesbloquearPago%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPermiso', 'cj_rs_eliminar_pagos');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>",
						$contFila);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (in_array($row['estatus_pago'], array(1,2))) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEliminarPago%s\" style=\"display:none\" onclick=\"validarEliminarPago(%s, '%s');\"><img src=\"../img/iconos/delete.png\" style=\"cursor:pointer\" title=\"Eliminar Pago\"/></a>",
					$contFila,
					$row['idPago'],
					$row['tabla']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPago(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPago(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPago(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPago(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPago(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPago","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		if (in_array($row['estatus_pago'], array(1,2))) {
			$totalPagos += $row['montoPagado'];
		}
	}
	
	$objResponse->assign("spnTotalPagos","innerHTML",number_format($totalPagos, 2, ".", ","));
	
	return $objResponse;
}

function validarPermiso($frmPermiso, $frmDatosArticulo) {
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPermiso = mysql_num_rows($rsPermiso);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($totalRowsPermiso > 0) {
		if (($frmPermiso['hddModulo'] == "cj_eliminar_pagos" && in_array($idCajaPpal, array(1)))
		|| ($frmPermiso['hddModulo'] == "cj_rs_eliminar_pagos" && in_array($idCajaPpal, array(2))) ) {
			for ($cont = 1; $cont <= 20; $cont++) {
				$objResponse->script("
				byId('aDesbloquearPago".$cont."').style.display = 'none';
				byId('aEliminarPago".$cont."').style.display = '';");
			}
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstFormaPago");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"listaPago");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function validarAperturaCaja($idEmpresa, $fecha) {
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal." ape
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>