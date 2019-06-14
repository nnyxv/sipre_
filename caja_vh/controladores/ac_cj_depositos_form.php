<?php


function buscar($frmPagos){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		valTpDato($frmPagos['selTipoPago'], "int"));
	
	$objResponse->script("xajax_listaPagoDepositar(0,'','','".$valBusq."',10,'',xajax.getFormValues('frmPlanilla'));");
	
	return $objResponse;
}

function calcularDeposito($frmPlanilla){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPagos = explode("|",$frmPlanilla['hddObjDetallePago']);
	
	if (isset($arrayObjPagos)){
		foreach ($arrayObjPagos as $indice => $valor) {
			if (isset($frmPlanilla['hddIdTabla'.$valor])){
				$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$i++;
				
				$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
				$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
				
				$arrayCriterioBusqueda = explode("|",$frmPlanilla['hddIdTabla'.$valor]);
				// POS 0 = nombre "tabla"
				// POS 1 = campo "idPago"
				// POS 2 = valor "idPago"
				// POS 3 = campo "montoPagado"
				// POS 4 = campo "idFormaPago"
				if ($arrayCriterioBusqueda[0] != "") {
					$sqlConsultaPago = sprintf("SELECT %s, %s FROM %s WHERE %s = %s",
						$arrayCriterioBusqueda[4], $arrayCriterioBusqueda[3], $arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
					
					$rsConsultaPago = mysql_query($sqlConsultaPago);
					if (!$rsConsultaPago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlConsultaPago);
					$rowConsultaPago = mysql_fetch_array($rsConsultaPago);
					
					if ($rowConsultaPago[$arrayCriterioBusqueda[4]] == 1 || $rowConsultaPago[$arrayCriterioBusqueda[4]] == 'EF') {
						$totalEfectivo += $rowConsultaPago[$arrayCriterioBusqueda[3]];
					} else if ($rowConsultaPago[$arrayCriterioBusqueda[4]] == 2 || $rowConsultaPago[$arrayCriterioBusqueda[4]] == 'CH') {
						$totalCheque += $rowConsultaPago[$arrayCriterioBusqueda[3]];
					} else if ($rowConsultaPago[$arrayCriterioBusqueda[4]] == 3 || $rowConsultaPago[$arrayCriterioBusqueda[4]] == 'OT') {
						$totalOtro += $rowConsultaPago[$arrayCriterioBusqueda[3]];
					}
				}
				
				$cadena .= "|".$valor;
			}
		}
	}
	
	$objResponse->assign("hddObjDetallePago","value",$cadena);
	
	$objResponse->assign("txtTotalEfectivo","value",number_format($totalEfectivo,2,',','.'));
	$objResponse->assign("txtTotalCheques","value",number_format($totalCheque,2,',','.'));
	$objResponse->assign("txtTotalOtro","value",number_format($totalOtro,2,',','.'));
	$objResponse->assign("txtTotalDeposito","value",number_format($totalEfectivo + $totalCheque,2,',','.'));
	
	$objResponse->script("$('btnBuscar').click();");
	
	if (count($arrayObjPagos) > 1) {
		$objResponse->script("$('trPlanillaDeposito').style.display = '';");
		$objResponse->script("$('btnDepositar').disabled = ''");
	} else {
		$objResponse->script("document.forms['frmPlanilla'].reset();");
		$objResponse->script("$('trPlanillaDeposito').style.display = 'none';");
		$objResponse->script("$('btnDepositar').disabled = 'disabled'");
	}
	
	return $objResponse;
}

function cargaSelBanco(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT banco.*
	FROM bancos banco
		INNER JOIN cuentas cuenta ON (banco.idBanco = cuenta.idBanco)
	GROUP BY banco.idBanco ORDER BY banco.nombreBanco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = sprintf("<select id=\"selBanco\" name=\"selBanco\" class=\"inputHabilitado\" onchange=\"xajax_cargarCuentas(this.value);\">");
		$html .= ($totalRows > 1) ? "<option value=\"\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_array($rs)){
		$selected = ($selId == $row['idBanco'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargarCuentas($row["idBanco"])); }
		
		$html .= "<option ".$selected." value=\"".$row["idBanco"]."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdSelBanco","innerHTML",$html);
	
	return $objResponse;
}

function cargarCuentas($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas WHERE idBanco = %s AND estatus = 1",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"selNumeroCuenta\" name=\"selNumeroCuenta\" class=\"inputHabilitado\" style=\"width:250px\">";
		$html .= ($totalRows > 1) ? "<option value=\"\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_array($rs)){
		$selected = ($selId == $row['idCuentas'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["idCuentas"]."\">".utf8_encode($row["numeroCuentaCompania"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdSelNumeroCuenta","innerHTML",$html);
	
	return $objResponse;
}

function depositarPlanilla($frmPlanilla){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $nombreCajaPpal;
	
	if ((!xvalidaAcceso($objResponse,"cj_depositos_form") && in_array($idCajaPpal, array(1)))
	|| (!xvalidaAcceso($objResponse,"cjrs_depositos_form") && in_array($idCajaPpal, array(2)))) { return $objResponse; }
	
	mysql_query("START TRANSACTION");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	
	$fechaRegistroPago = date(spanDateFormat);
	$numeroDeposito = date("Ymd");
		
	// INSERTA EL ENCABEZADO DEL DEPÓSITO
	$queryCabeceraPlanillaCaja = sprintf("INSERT INTO an_encabezadodeposito (fechaPlanilla, numeroDeposito, id_usuario, idCaja, id_empresa)
	VALUES (%s, %s, %s, %s, %s)",
		valTpDato("NOW()", "campo"),
		valTpDato($numeroDeposito, "int"),
		valTpDato($idUsuario, "int"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idEmpresa, "int"));
	$rsCabeceraPlanillaCaja = mysql_query($queryCabeceraPlanillaCaja);
	if (!$rsCabeceraPlanillaCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryCabeceraPlanillaCaja);
	$idPlanillaDeposito = mysql_insert_id();
	
	// CONSULTA EL NUMERO ACTUAL DE FOLIO EN TESORERIA
	$selectFolioDepositoTesoreria = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 2");
	$rsFolioDepositoTesoreria = mysql_query($selectFolioDepositoTesoreria);
	if (!$rsFolioDepositoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$selectFolioDepositoTesoreria);
	$rowFolioDepositoTesoreria = mysql_fetch_array($rsFolioDepositoTesoreria);
	
	// ACTUALIZA EL NUMERO ACTUAL DE FOLIO EN TESORERIA
	// id_folios = 2: deposito
	$sqlUpdateFolioDepositoTesoreria = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 2",
		valTpDato($rowFolioDepositoTesoreria['numero_actual'] + 1, "int"));
	$rsUpdateFolioDepositoTesoreria = mysql_query($sqlUpdateFolioDepositoTesoreria);
	if (!$rsUpdateFolioDepositoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlUpdateFolioDepositoTesoreria);
	
	// CONSULTA NUMERO DE CUENTA Y BANCO A DEPOSITAR
	$selectNumeroCuenta = sprintf("SELECT numeroCuentaCompania, idBanco FROM cuentas WHERE idCuentas = %s", $frmPlanilla['selNumeroCuenta']);
	$rsNumeroCuenta = mysql_query($selectNumeroCuenta);
	if (!$rsNumeroCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$selectNumeroCuenta);
	$rowNumeroCuenta = mysql_fetch_array($rsNumeroCuenta);
	
	// INSERTA EL DEPOSITO EN TESORERIA
	$insertSQL = sprintf("INSERT INTO te_depositos (id_empresa, id_planilla_deposito, id_numero_cuenta, fecha_registro, fecha_aplicacion, folio_deposito, numero_deposito_banco, origen, estado_documento, monto_efectivo, monto_cheques_total, monto_total_deposito, observacion, desincorporado, id_usuario)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($idEmpresa, "int"),
		valTpDato($idPlanillaDeposito, "int"),
		valTpDato($frmPlanilla['selNumeroCuenta'], "int"),
		valTpDato("NOW()", "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato($rowFolioDepositoTesoreria['numero_actual'], "int"),
		valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
		valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
		valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato("Deposito Cierre ".$nombreCajaPpal." ".date(spanDateFormat, strtotime($fechaRegistroPago)), "text"),
		valTpDato(1, "int"), // 0 = Desincorporado, 1 = Normal
		valTpDato($idUsuario, "int"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$insertSQL);
	$idDeposito = mysql_insert_id();
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPagos = explode("|",$frmPlanilla['hddObjDetallePago']);
	
	foreach ($arrayObjPagos as $indice => $valor) {
		if ($valor != ""){
			$arrayCriterioBusqueda = explode("|",$frmPlanilla['hddIdTabla'.$valor]);
			// POS 0 = nombre "tabla"
			// POS 1 = campo "idPago"
			// POS 2 = valor "idPago"
			// POS 3 = campo "montoPagado"
			// POS 4 = campo "idFormaPago"
			if (in_array($arrayCriterioBusqueda[0],array("an_pagos","sa_iv_pagos"))) { // FACTURA
				$sqlConsultaPago = sprintf("SELECT
					idPago AS idPago,
					formaPago AS tipoPago,
					bancoOrigen AS bancoOrigen,
					bancoDestino AS bancoDestino,
					cuentaEmpresa AS numeroCuenta,
					numeroDocumento AS numeroControl,
					montoPagado AS montoPago,
					fechaPago AS fechaPago,
					1 AS tipoDocumento,
					(CASE
						WHEN (an_ape.idCaja = 1) THEN		an_ape.fechaAperturaCaja
						WHEN (sa_iv_ape.idCaja = 2) THEN	sa_iv_ape.fechaAperturaCaja
					END) AS fechaAperturaCaja
				FROM %s cxc_pago
					LEFT JOIN an_apertura an_ape ON (cxc_pago.id_apertura = an_ape.id AND cxc_pago.idCaja = 1)
					LEFT JOIN sa_iv_apertura sa_iv_ape ON (cxc_pago.id_apertura = sa_iv_ape.id AND cxc_pago.idCaja = 2)
				WHERE %s = %s",
					$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			} else if ($arrayCriterioBusqueda[0] == 'cj_det_nota_cargo') { // NOTA DE CARGO
				$sqlConsultaPago = sprintf("SELECT
					id_det_nota_cargo AS idPago,
					idFormaPago AS tipoPago,
					bancoOrigen AS bancoOrigen,
					bancoDestino AS bancoDestino,
					cuentaEmpresa AS numeroCuenta,
					numeroDocumento AS numeroControl,
					monto_pago AS montoPago,
					fechaPago AS fechaPago,
					2 AS tipoDocumento,
					(CASE
						WHEN (an_ape.idCaja = 1) THEN		an_ape.fechaAperturaCaja
						WHEN (sa_iv_ape.idCaja = 2) THEN	sa_iv_ape.fechaAperturaCaja
					END) AS fechaAperturaCaja
				FROM %s cxc_pago
					LEFT JOIN an_apertura an_ape ON (cxc_pago.id_apertura = an_ape.id AND cxc_pago.idCaja = 1)
					LEFT JOIN sa_iv_apertura sa_iv_ape ON (cxc_pago.id_apertura = sa_iv_ape.id AND cxc_pago.idCaja = 2)
				WHERE %s = %s",
					$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			} else if ($arrayCriterioBusqueda[0] == 'cj_cc_detalleanticipo') { // ANTICIPO
				$sqlConsultaPago = sprintf("SELECT
					idDetalleAnticipo AS idPago,
					tipoPagoDetalleAnticipo AS tipoPago,
					bancoClienteDetalleAnticipo AS bancoOrigen,
					bancoCompaniaDetalleAnticipo AS bancoDestino,
					numeroCuentaCompania AS numeroCuenta,
					numeroControlDetalleAnticipo AS numeroControl,
					montoDetalleAnticipo AS montoPago,
					fechaPagoAnticipo AS fechaPago,
					4 AS tipoDocumento,
					(CASE
						WHEN (an_ape.idCaja = 1) THEN		an_ape.fechaAperturaCaja
						WHEN (sa_iv_ape.idCaja = 2) THEN	sa_iv_ape.fechaAperturaCaja
					END) AS fechaAperturaCaja
				FROM %s cxc_pago
					LEFT JOIN an_apertura an_ape ON (cxc_pago.id_apertura = an_ape.id AND cxc_pago.idCaja = 1)
					LEFT JOIN sa_iv_apertura sa_iv_ape ON (cxc_pago.id_apertura = sa_iv_ape.id AND cxc_pago.idCaja = 2)
				WHERE %s = %s",
					$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			} else if ($arrayCriterioBusqueda[0] == 'cj_cc_cheque') { // CHEQUE
				$sqlConsultaPago = sprintf("SELECT
					id_cheque AS idPago,
					2 AS tipoPago,
					id_banco_cliente AS bancoOrigen,
					1 AS bancoDestino,
					'-' AS numeroCuenta,
					numero_cheque AS numeroControl,
					monto_neto_cheque AS montoPago,
					fecha_cheque AS fechaPago,
					5 AS tipoDocumento,
					(CASE
						WHEN (an_ape.idCaja = 1) THEN		an_ape.fechaAperturaCaja
						WHEN (sa_iv_ape.idCaja = 2) THEN	sa_iv_ape.fechaAperturaCaja
					END) AS fechaAperturaCaja
				FROM %s cxc_pago
					LEFT JOIN an_apertura an_ape ON (cxc_pago.id_apertura = an_ape.id AND cxc_pago.idCaja = 1)
					LEFT JOIN sa_iv_apertura sa_iv_ape ON (cxc_pago.id_apertura = sa_iv_ape.id AND cxc_pago.idCaja = 2)
				WHERE %s = %s",
					$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			}
			$rsConsultaPago = mysql_query($sqlConsultaPago);
			if (!$rsConsultaPago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlConsultaPago);
			$rowConsultaPago = mysql_fetch_array($rsConsultaPago);
			
			$fechaRegistroPago = $rowConsultaPago['fechaAperturaCaja'];
			
			if (in_array($rowConsultaPago['tipoPago'],array(1,"EF"))) { // PAGO EN EFECTIVO
				$totalEfectivo += $rowConsultaPago['montoPago'];
				
				// INSERTA EL DETALLE DEL DEPÓSITO EN EFECTIVO
				$insertSQLDepositoDet = sprintf("INSERT INTO an_detalledeposito (idPlanilla, numeroDeposito, idTipoDocumento, formaPago, idPagoRelacionadoConNroCheque, numeroCheque, banco, numeroCuenta, monto, idBancoAdepositar, numeroCuentaBancoAdepositar, conformado, tipoDeCheque, anulada, idCaja)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idPlanillaDeposito, "int"),
					valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
					valTpDato($rowConsultaPago['tipoDocumento'], "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
					valTpDato(1, "int"), // 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito, 9 = Retencion, 10 = Retencion I.S.L.R., 11 = Otro (Relacion Tabla formapagos)
					valTpDato($rowConsultaPago['idPago'], "int"),
					valTpDato('-', "text"),
					valTpDato(1, "int"),
					valTpDato('-', "text"),
					valTpDato($rowConsultaPago['montoPago'], "double"),
					valTpDato($rowNumeroCuenta['idBanco'], "int"),
					valTpDato($rowNumeroCuenta['numeroCuentaCompania'], "text"),
					valTpDato(2, "int"), // 1 = Por Conformar, 2 = Conformado
					valTpDato(0, "int"), // 0 = Efectivo, 1 = Local, 2 = Otra Plaza
					valTpDato('NO', "text"),
					valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			} else if (in_array($rowConsultaPago['tipoPago'],array(2,"CH"))) { // PAGO EN CHEQUE
				$totalCheque += $rowConsultaPago['montoPago'];
				
				$tipoCheque = ($rowConsultaPago['bancoOrigen'] == $rowNumeroCuenta['idBanco']) ? 1 : 2;
				
				// INSERTA EL DETALLE DEL DEPÓSITO EN CHEQUES
				$insertSQLDepositoDet = sprintf("INSERT INTO an_detalledeposito (idPlanilla, numeroDeposito, idTipoDocumento, formaPago, idPagoRelacionadoConNroCheque, numeroCheque, banco, numeroCuenta, monto, idBancoAdepositar, numeroCuentaBancoAdepositar, conformado, tipoDeCheque, anulada, idCaja)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idPlanillaDeposito, "int"),
					valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
					valTpDato($rowConsultaPago['tipoDocumento'], "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
					valTpDato(2, "int"), // 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito, 9 = Retencion, 10 = Retencion I.S.L.R., 11 = Otro (Relacion Tabla formapagos)
					valTpDato($rowConsultaPago['idPago'], "int"),
					valTpDato($rowConsultaPago['numeroControl'], "text"),
					valTpDato($rowConsultaPago['bancoOrigen'], "int"),
					valTpDato($rowConsultaPago['numeroCuenta'], "text"),
					valTpDato($rowConsultaPago['montoPago'], "double"),
					valTpDato($rowNumeroCuenta['idBanco'], "int"),
					valTpDato($rowNumeroCuenta['numeroCuentaCompania'], "text"),
					valTpDato(2, "int"), // 1 = Por Conformar, 2 = Conformado
					valTpDato($tipoCheque, "int"), // 0 = Efectivo, 1 = Local, 2 = Otra Plaza
					valTpDato('NO', "text"),
					valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
						
				// INSERTA EL DETALLE DEL DEPÓSITO EN CHEQUES EN TESORERIA
				$queryDetallePlanillaTesoreria = sprintf("INSERT INTO te_deposito_detalle (id_deposito, id_banco, numero_cuenta_cliente, numero_cheques, monto)
				VALUES (%s, %s, %s, %s, %s)",
					valTpDato($idDeposito, "int"),
					valTpDato($rowNumeroCuenta['idBanco'], "int"),
					valTpDato($rowConsultaPago['numeroCuenta'], "text"),
					valTpDato($rowConsultaPago['numeroControl'], "text"),
					valTpDato($rowConsultaPago['montoPago'], "double"));
				$rsDetallePlanillaTesoreria = mysql_query($queryDetallePlanillaTesoreria);
				if (!$rsDetallePlanillaTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryDetallePlanillaTesoreria);
			} else if (in_array($rowConsultaPago['tipoPago'],array(11,"OT"))) {
				$totalOtro += $rowConsultaPago['montoPago'];
				
				// INSERTA EL DETALLE DEL DEPÓSITO EN OTROS
				$insertSQLDepositoDet = sprintf("INSERT INTO an_detalledeposito (idPlanilla, numeroDeposito, idTipoDocumento, formaPago, idPagoRelacionadoConNroCheque, numeroCheque, banco, numeroCuenta, monto, idBancoAdepositar, numeroCuentaBancoAdepositar, conformado, tipoDeCheque, anulada, idCaja)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idPlanillaDeposito, "int"),
					valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
					valTpDato($rowConsultaPago['tipoDocumento'], "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
					valTpDato(11, "int"), // 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito, 9 = Retencion, 10 = Retencion I.S.L.R., 11 = Otro (Relacion Tabla formapagos)
					valTpDato($rowConsultaPago['idPago'], "int"),
					valTpDato('-', "text"),
					valTpDato(1, "int"),
					valTpDato('-', "text"),
					valTpDato($rowConsultaPago['montoPago'], "double"),
					valTpDato($rowNumeroCuenta['idBanco'], "int"),
					valTpDato($rowNumeroCuenta['numeroCuentaCompania'], "text"),
					valTpDato(2, "int"), // 1 = Por Conformar, 2 = Conformado
					valTpDato(0, "int"), // 0 = Efectivo, 1 = Local, 2 = Otra Plaza
					valTpDato('NO', "text"),
					valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			}
			$Result1 = mysql_query($insertSQLDepositoDet);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$insertSQLDepositoDet);
			
			// CAMBIAR EL ESTADO DEL PAGO (0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado)
			$sqlUpdatePago = sprintf("UPDATE %s SET tomadoEnCierre = 2 WHERE %s = %s",
				$arrayCriterioBusqueda[0], $arrayCriterioBusqueda[1], $arrayCriterioBusqueda[2]);
			$rsUpdatePago = mysql_query($sqlUpdatePago);
			if (!$rsUpdatePago) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlUpdatePago);
			
			$objResponse->loadCommands(eliminarPago($valor));
		}
	}
	
	// ACTUALIZA MONTOS EN EL DEPOSITO DE TESORERIA
	$updateDepositoTesoreria = sprintf("UPDATE te_depositos SET
		monto_total_deposito = %s,
		monto_efectivo = %s,
		monto_cheques_total = %s,
		observacion = %s
	WHERE id_deposito = %s",
		valTpDato($totalEfectivo + $totalCheque + $totalOtro, "double"),
		valTpDato($totalEfectivo + $totalOtro, "double"),
		valTpDato($totalCheque, "double"),
		valTpDato("Deposito Cierre ".$nombreCajaPpal." ".date(spanDateFormat, strtotime($fechaRegistroPago)), "text"),
		valTpDato($idDeposito, "int"));
	$rsUpdateDepositoTesoreria = mysql_query($updateDepositoTesoreria);
	if (!$rsUpdateDepositoTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$updateDepositoTesoreria);
	
	// INSERTA EL DEPOSITO EN EL ESTADO DE CUENTA DE TESORERIA
	$sqlInsertEstadoCuentaTesoreria = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato("DP", "text"),
		valTpDato($idDeposito, "int"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmPlanilla['selNumeroCuenta'], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($totalEfectivo + $totalCheque + $totalOtro, "double"),
		valTpDato(1, "int"),
		valTpDato($frmPlanilla['txtNumeroPlanilla'], "text"),
		valTpDato(1, "int"),
		valTpDato("Deposito Cierre ".$nombreCajaPpal." ".date(spanDateFormat, strtotime($fechaRegistroPago)), "text"),
		valTpDato(2, "int"),
		valTpDato(0, "int"));
	$rsInsertEstadoCuentaTesoreria = mysql_query($sqlInsertEstadoCuentaTesoreria);
	if (!$rsInsertEstadoCuentaTesoreria) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlInsertEstadoCuentaTesoreria);
	
	mysql_query("COMMIT");
	
	$objResponse->alert("Planilla de Deposito guardada exitosamente.");
	
	$objResponse->assign("hddObjDetallePago","value","");
	$objResponse->assign("txtNumeroPlanilla","value","");
	
	$objResponse->script("xajax_calcularDeposito(xajax.getFormValues('frmPlanilla'))");
	
	// MODIFICADO ERNESTO
	if (in_array($idCajaPpal,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		if (function_exists("generarDepositosTeVe")) { generarDepositosTeVe($idDeposito,"",""); } 
	} else {
		if (function_exists("generarDepositosTeRe")) { generarDepositosTeRe($idDeposito,"",""); }
	}
	// MODIFICADO ERNESTO
	
	return $objResponse;
}

function eliminarPago($pos){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	fila = document.getElementById('trItm:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
	
	$objResponse->script("xajax_calcularDeposito(xajax.getFormValues('frmPlanilla'))");
	
	return $objResponse;
}

function insertarPago($frmPagos, $frmPlanilla){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	if ((!xvalidaAcceso($objResponse,"cj_depositos_form") && in_array($idCajaPpal, array(1)))
	|| (!xvalidaAcceso($objResponse,"cjrs_depositos_form") && in_array($idCajaPpal, array(2)))) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPagos = explode("|",$frmPlanilla['hddObjDetallePago']);
	$contFila = $arrayObjPagos[count($arrayObjPagos)-1];
	
	if (isset($frmPagos['cbxItm'])){
		foreach($frmPagos['cbxItm'] as $indice => $valor){
			$arrayValorPago = explode("|",$valor);
			
			$idPago = $arrayValorPago[0];
			$tablaPago = $arrayValorPago[1];
			
			if (in_array($tablaPago,array("an_pagos","sa_iv_pagos"))){
				$campoIdPago = "idPago";
				$campoIdFormaPago = "formaPago";
				$campoMontoPago = "montoPagado";
				
				$sqlDetallePago = sprintf("SELECT
					cxc_pago.idPago,
					cxc_pago.fechaPago,
					cxc_pago.numeroDocumento AS numero_documento,
					forma_pago.idFormaPago,
					forma_pago.nombreFormaPago,
					cxc_pago.bancoOrigen,
					banco_cliente.nombreBanco AS nombre_banco_cliente,
					cxc_pago.numero_cuenta_cliente,
					cxc_pago.bancoDestino,
					banco_emp.nombreBanco AS nombre_banco_empresa,
					cxc_pago.cuentaEmpresa,
					cxc_pago.montoPagado
				FROM %s cxc_pago
					INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
					LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
					LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
				WHERE idPago = %s;",
					$tablaPago,
					valTpDato($idPago, "int"));
			} else if ($tablaPago == "cj_det_nota_cargo"){
				$campoIdPago = "id_det_nota_cargo";
				$campoIdFormaPago = "idFormaPago";
				$campoMontoPago = "monto_pago";
				
				$sqlDetallePago = sprintf("SELECT
					cxc_pago.id_det_nota_cargo AS idPago,
					cxc_pago.fechaPago,
					cxc_pago.numeroDocumento AS numero_documento,
					forma_pago.idFormaPago,
					forma_pago.nombreFormaPago,
					cxc_pago.bancoOrigen,
					banco_cliente.nombreBanco AS nombre_banco_cliente,
					cxc_pago.numero_cuenta_cliente,
					cxc_pago.bancoDestino,
					banco_emp.nombreBanco AS nombre_banco_empresa,
					cxc_pago.cuentaEmpresa,
					cxc_pago.monto_pago AS montoPagado
				FROM cj_det_nota_cargo cxc_pago
					INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
					LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
					LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
				WHERE id_det_nota_cargo = %s;",
					valTpDato($idPago, "int"));
			} else if ($tablaPago == "cj_cc_detalleanticipo"){
				$campoIdPago = "idDetalleAnticipo";
				$campoIdFormaPago = "id_forma_pago";
				$campoMontoPago = "montoDetalleAnticipo";
				
				$sqlDetallePago = sprintf("SELECT
					cxc_pago.idDetalleAnticipo AS idPago,
					cxc_pago.fechaPagoAnticipo AS fechaPago,
					cxc_pago.numeroControlDetalleAnticipo AS numero_documento,
					forma_pago.idFormaPago,
					forma_pago.nombreFormaPago,
					cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
					banco_cliente.nombreBanco AS nombre_banco_cliente,
					cxc_pago.numeroCuentaCliente AS numero_cuenta_cliente,
					cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
					banco_emp.nombreBanco AS nombre_banco_empresa,
					cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
					cxc_pago.montoDetalleAnticipo AS montoPagado
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
					LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoClienteDetalleAnticipo = banco_cliente.idBanco)
					LEFT JOIN bancos banco_emp ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_emp.idBanco)
				WHERE idDetalleAnticipo = %s;",
					valTpDato($idPago, "int"));
			} else if ($tablaPago == "cj_cc_cheque"){
				$campoIdPago = "id_cheque";
				$campoIdFormaPago = "2";
				$campoMontoPago = "monto_neto_cheque";
				
				$sqlDetallePago = sprintf("SELECT
					cxc_pago.id_cheque AS idPago,
					cxc_pago.fecha_cheque AS fechaPago,
					cxc_pago.numero_cheque AS numero_documento,
					forma_pago.idFormaPago,
					forma_pago.nombreFormaPago,
					cxc_pago.id_banco_cliente AS bancoOrigen,
					banco_cliente.nombreBanco AS nombre_banco_cliente,
					cxc_pago.cuenta_cliente AS numero_cuenta_cliente,
					NULL AS bancoDestino,
					NULL AS nombre_banco_empresa,
					NULL AS cuentaEmpresa,
					cxc_pago.monto_neto_cheque AS montoPagado
				FROM cj_cc_cheque cxc_pago
					INNER JOIN formapagos forma_pago ON (2 = forma_pago.idFormaPago)
					LEFT JOIN bancos banco_cliente ON (cxc_pago.id_banco_cliente = banco_cliente.idBanco)
				WHERE id_cheque = %s;",
					valTpDato($idPago, "int"));
			}
			$rsDetallePago = mysql_query($sqlDetallePago);
			if (!$rsDetallePago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowDetallePago = mysql_fetch_array($rsDetallePago);
			
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px %s', 'title':'trItm:%s'}).adopt([
					new Element('td', {'id':'tdNumItm:%s', 'align':'center', 'class':'textoNegrita_9px'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"<button type='button' onclick='confirmarEliminarPago(%s);' title='Eliminar Pago' class='puntero'><img src='../img/iconos/delete.png'/></button>".
						"<input type='hidden' id='hddIdTabla%s' name='hddIdTabla%s' title='hddIdTabla' value='%s'/>\")
				]);
					elemento.injectBefore('trPie');",
				$contFila, $clase, $contFila,
					$contFila, $contFila,
					date(spanDateFormat, strtotime($rowDetallePago['fechaPago'])),
					utf8_encode($rowDetallePago['nombreFormaPago']),
					utf8_encode($rowDetallePago['numero_documento']),
					utf8_encode($rowDetallePago['nombre_banco_cliente']),
					utf8_encode($rowDetallePago['numero_cuenta_cliente']),
					number_format($rowDetallePago['montoPagado'], 2, ".", ","),
					$contFila, $contFila,
						$contFila, $contFila, $tablaPago."|".$campoIdPago."|".$rowDetallePago['idPago']."|".$campoMontoPago."|".$campoIdFormaPago));
			
			$arrayObjPagos[] = $contFila;
		}
		
		$objResponse->assign("hddObjDetallePago","value",implode("|",$arrayObjPagos));
		
		$objResponse->script("xajax_calcularDeposito(xajax.getFormValues('frmPlanilla'))");
	} else {
		$objResponse->alert("Debe seleccionar al menos un pago.");
	}
	
	return $objResponse;
}

function listaPagoDepositar($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL, $frmPlanilla){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $idModuloPpal;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] == 1) {
		$tipoPagoAnticipo = " AND cxc_pago.tipoPagoDetalleAnticipo IN ('EF')";
		$tipoPagoFactura = " AND cxc_pago.formaPago IN (1)";
		$tipoPagoNotaCargo = " AND cxc_pago.idFormaPago IN (1)";
		$tipoPagoCheque = " AND 2 IN (1)";
	} else if ($valCadBusq[0] == 2) {
		$tipoPagoAnticipo = " AND cxc_pago.tipoPagoDetalleAnticipo IN ('CH')";
		$tipoPagoFactura = " AND cxc_pago.formaPago IN (2)";
		$tipoPagoNotaCargo = " AND cxc_pago.idFormaPago IN (2)";
		$tipoPagoCheque = " AND 2 IN (2)";
	} else {
		$tipoPagoAnticipo = " AND cxc_pago.tipoPagoDetalleAnticipo IN ('EF', 'CH')";
		$tipoPagoFactura = " AND cxc_pago.formaPago IN (1,2)";
		$tipoPagoNotaCargo = " AND cxc_pago.idFormaPago IN (1,2)";
		$tipoPagoCheque = " AND 2 IN (1,2)";
	}
	
	if ($frmPlanilla['hddObjDetallePago'] != "") {
		$arregloPos = explode("|",$frmPlanilla['hddObjDetallePago']);
		
		foreach ($arregloPos as $indicePos => $valosPos) {
			$arregloDetallePago = explode("|",$frmPlanilla['hddIdTabla'.$valosPos]);
			
			if (in_array($arregloDetallePago[0],array("an_pagos","sa_iv_pagos"))) {
				$campoIdPagoFA = $arregloDetallePago[1];
				$arrayIdFactura[] = $arregloDetallePago[2];
			} else if ($arregloDetallePago[0] == "cj_det_nota_cargo") {
				$campoIdPagoND = $arregloDetallePago[1];
				$arrayIdNotaCargo[] = $arregloDetallePago[2];
			} else if ($arregloDetallePago[0] == "cj_cc_detalleanticipo") {
				$campoIdPagoAN = $arregloDetallePago[1];
				$arrayIdAnticipo[] = $arregloDetallePago[2];
			} else if ($arregloDetallePago[0] == "cj_cc_cheque") {
				$campoIdPagoCH = $arregloDetallePago[1];
				$arrayIdCheque[] = $arregloDetallePago[2];
			}
		}
		
		if (count($arrayIdFactura) > 0) { $idFactura = " AND cxc_pago.".$campoIdPagoFA." NOT IN (".implode(",", $arrayIdFactura).")"; }
		if (count($arrayIdNotaCargo) > 0) { $idNotaCargo = " AND cxc_pago.".$campoIdPagoND." NOT IN (".implode(",", $arrayIdNotaCargo).")"; }
		if (count($arrayIdAnticipo) > 0) { $idAnticipo = " AND cxc_pago.".$campoIdPagoAN." NOT IN (".implode(",", $arrayIdAnticipo).")"; }
		if (count($arrayIdCheque) > 0) { $idCheque = " AND cxc_ch.".$campoIdPagoCH." NOT IN (".implode(",", $arrayIdCheque).")"; }
	}
					
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
		$andEmpresaPagoFA = sprintf(" AND cxc_fact.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaPagoND = sprintf(" AND cxc_nd.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaPagoAN = sprintf(" AND cxc_ant.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaPagoCH = sprintf(" AND cxc_ch.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
		$andEmpresaPagoFA = "";
		$andEmpresaPagoND = "";
		$andEmpresaPagoAN = "";
		$andEmpresaPagoCH = "";
	}
	
	$query = sprintf("SELECT 
		cxc_pago.idPago,
		'FACTURA' AS tipoDoc,
		cxc_fact.idDepartamentoOrigenFactura AS idDepartamento,
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
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
		AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.formaPago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
		AND cxc_pago.tomadoEnComprobante = 1
		AND cxc_pago.tomadoEnCierre IN (1)
		AND cxc_pago.estatus IN (1)
		".$tipoPagoFactura."
		".$andEmpresaPagoFA."
		".$idFactura."
	
	UNION
	
	SELECT 
		cxc_pago.idPago,
		'FACTURA' AS tipoDoc,
		cxc_fact.idDepartamentoOrigenFactura AS idDepartamento,
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
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
		AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.formaPago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
		AND cxc_pago.tomadoEnComprobante = 1
		AND cxc_pago.tomadoEnCierre IN (1)
		AND cxc_pago.estatus IN (1)
		".$tipoPagoFactura."
		".$andEmpresaPagoFA."
		".$idFactura."
	
	UNION
	
	SELECT 
		cxc_pago.id_det_nota_cargo AS idPago,
		'NOTA DEBITO' AS tipoDoc,
		cxc_nd.idDepartamentoOrigenNotaCargo AS idDepartamento,
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
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
		AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.idFormaPago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
		AND cxc_pago.tomadoEnComprobante = 1
		AND cxc_pago.tomadoEnCierre IN (1)
		AND cxc_pago.estatus IN (1)
		".$tipoPagoNotaCargo."
		".$andEmpresaPagoND."
		".$idNotaCargo."
	
	UNION
	
	SELECT 
		cxc_pago.idDetalleAnticipo AS idPago,
		CONCAT_WS(' ', 'ANTICIPO', IF(concepto_forma_pago.descripcion IS NOT NULL, CONCAT('(', concepto_forma_pago.descripcion, ')'), NULL)) AS tipoDoc,
		cxc_ant.idDepartamento AS idDepartamento,
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
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cxc_pago.idCaja IN (".valTpDato($idCajaPpal, "campo").")
		AND cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.id_forma_pago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
		AND cxc_pago.tomadoEnCierre IN (1)
		AND cxc_pago.estatus IN (1)
		AND cxc_ant.estatus IN (1)
		".$tipoPagoAnticipo."
		".$andEmpresaPagoAN."
		".$idAnticipo."
	
	UNION
	
	SELECT 
		cxc_ch.id_cheque AS idPago,
		'CHEQUE' AS tipoDoc,
		cxc_ch.id_departamento AS idDepartamento,
		NULL AS id_documento_pagado,
		'-' AS numero_documento,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_ch.fecha_cheque AS fechaPago,
		recibo.idReporteImpresion AS id_recibo_pago,
		recibo.numeroReporteImpresion AS nro_comprobante,
		2 AS formaPago,
		(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 2) AS nombreFormaPago,
		NULL AS id_concepto,
		cxc_ch.numero_cheque AS numero_documento_pago,
		cxc_ch.id_banco_cliente AS bancoOrigen,
		banco_origen.nombreBanco AS nombre_banco_origen,
		1 AS bancoDestino,
		'-' AS nombre_banco_destino,
		'-' AS cuentaEmpresa,
		cxc_ch.idCaja,
		cxc_ch.total_pagado_cheque AS montoPagado,
		cxc_ch.estatus AS estatus,
		cxc_ch.estatus AS estatus_pago,
		DATE(cxc_ch.fecha_anulado) AS fecha_anulado,
		'cj_cc_cheque' AS tabla,
		'id_cheque' AS campo_id_pago,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_cheque cxc_ch
		INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
		INNER JOIN bancos banco_origen on (cxc_ch.id_banco_cliente = banco_origen.idBanco)
		INNER JOIN pg_reportesimpresion recibo ON (cxc_ch.id_cheque = recibo.idDocumento AND cxc_ch.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'CH')
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ch.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cxc_ch.idCaja IN (".valTpDato($idCajaPpal, "campo").")
		AND cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
		AND cxc_ch.tomadoEnComprobante = 1
		AND cxc_ch.tomadoEnCierre IN (1)
		AND cxc_ch.estatus IN (1)
		".$tipoPagoCheque."
		".$andEmpresaPagoCH."
		".$idCheque);
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
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "6%", $pageNum, "fechaAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Pago");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "6%", $pageNum, "tipoDoc", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "8%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto. Pagado");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "8%", $pageNum, "nro_comprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Recibo");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "20%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "10%", $pageNum, "nombreFormaPago", $campOrd, $tpOrd, $valBusq, $maxRows, "Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "8%", $pageNum, "numero_documento_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "20%", $pageNum, "nombre_banco_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPagoDepositar", "10%", $pageNum, "montoPagado", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Pago");
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,'frmPagos');\"/></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if ($row['formaPago'] == 1 || $row['formaPago'] == 'EF') {
			$img = "<img src='../img/iconos/money.png' title='Efectivo'/>";
			$formaPago = "Efectivo";
		} else if ($row['formaPago'] == 2 || $row['formaPago'] == 'CH') {
			$img = "<img src='../img/iconos/cheque.png' title='Cheque'/>";
			$formaPago = "Cheque";
		} else if ($row['formaPago'] == 11 || $row['formaPago'] == 'OT') {
			$queryConcepto = sprintf("SELECT * FROM cj_conceptos_formapago WHERE id_concepto = %s;",
				valTpDato($row['id_concepto'], "int"));
			$rsConcepto = mysql_query($queryConcepto);
			if (!$rsConcepto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowConcepto = mysql_fetch_assoc($rsConcepto);
			
			$img = "<img src='../img/iconos/text_signature.png' title='Otro'/>";
			$formaPago = "Otro (".utf8_encode($rowConcepto['descripcion']).")";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaPago']))."</td>";
			$htmlTb .= "<td align=\"center\">".($row['tipoDoc'])."</td>";
			$htmlTb .= "<td align=\"right\">".($row['numero_documento'])."</td>";
			$htmlTb .= "<td align=\"right\">".($row['nro_comprobante'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".$classPago."\">".utf8_encode($row['nombreFormaPago']).$estatusPago."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_documento_pago'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_banco_origen'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoPagado'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".$img."</td>";
			$htmlTb .= "<td><input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" class=\"puntero\" title=\"Seleccionar Pago\" value=\"".$row['idPago']."|".$row['tabla']."\"/></td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPagoDepositar(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"18\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("$('btnCargarPlanilla').style.display = '';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"calcularDeposito");
$xajax->register(XAJAX_FUNCTION,"cargaSelBanco");
$xajax->register(XAJAX_FUNCTION,"cargarCuentas");
$xajax->register(XAJAX_FUNCTION,"depositarPlanilla");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"insertarPago");
$xajax->register(XAJAX_FUNCTION,"listaPagoDepositar");
?>