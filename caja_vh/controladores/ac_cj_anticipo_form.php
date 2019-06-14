<?php


function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($estatusCliente != "-1" && $estatusCliente != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.status = %s",
			valTpDato($estatusCliente, "text"));
	}
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.reputacionCliente,
		cliente.status,
		cliente.tipo_cuenta_cliente
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$idClaveMovimiento = ($idClaveMovimiento == "") ? $rowCliente['id_clave_movimiento_predeterminado'] : $idClaveMovimiento;
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoCliente);
		
		$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
		$objResponse->assign("txtCreditoCliente","value",number_format($rowClienteCredito['creditodisponible'], 2, ".", ","));
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		$objResponse->assign("hddTipoPagoCliente","value",0);
		
		if (function_exists("cargaLstClaveMovimiento")) { 
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "3", "", "1", $idClaveMovimiento,
			((in_array($idModulo,array(0))) ? "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""
				: "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\"")));
			
			if (in_array($idModulo,array(0))) {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = '';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
					xajax_cargaLstClaveMovimiento('lstClaveMovimiento','".$idModulo."','3','','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
				}");
			} else {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = 'none';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
				}");
			}
		}
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		$objResponse->assign("hddTipoPagoCliente","value",1);
		
		if (function_exists("cargaLstClaveMovimiento")) { 
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "3", "", "1", $idClaveMovimiento,
			((in_array($idModulo,array(0))) ? "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""
				: "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\"")));
			
			if (in_array($idModulo,array(0))) {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = '';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
					xajax_cargaLstClaveMovimiento('lstClaveMovimiento','".$idModulo."','3','','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
				}");
			} else {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = 'none';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
				}");
			}
		}
	}
	
	if ($rowCliente['id_cliente'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$tipoCuentaCliente = $rowCliente['tipo_cuenta_cliente'];
	if ($tipoCuentaCliente == 1) {
		$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">PROSPECTO [".$rowCliente['reputacionCliente']."]</div>";
		$backgroundReputacion = '#FFFFCC'; // AMARILLO
	} else {
		switch ($rowCliente['id_reputacion_cliente']) {
			case 1 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#FFEEEE'; // ROJO
				break;
			case 2 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#DDEEFF'; // AZUL
				break;
			case 3 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#E6FFE6'; // VERDE
				break;
		}
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id_cliente']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",$tdMsjCliente);
	$objResponse->assign("tblIdCliente","style.background",$backgroundReputacion);
	
	$objResponse->script("xajax_listaAnticipoNoCancelado(0, 'idAnticipo', 'DESC', '".$idEmpresa."|".$idCliente."|' + byId('hddIdAnticipo').value);");
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

function asignarEmpleado($idEmpleado, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	$queryEmpleado = sprintf("SELECT vw_pg_empleado.* FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarPorcentajeTarjetaCredito($idCuenta, $idTarjeta) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto
	WHERE id_cuenta = %s
		AND id_tipo_tarjeta = %s",
		valTpDato($idCuenta, "int"),
		valTpDato($idTarjeta, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("porcentajeRetencion","value",$row['porcentaje_islr']);
	$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	
	$objResponse->script("calcularPorcentajeTarjetaCredito();");
	
	return $objResponse;
}

function buscarAnticipo($frmBuscarAnticipo, $frmDcto, $frmListaDctoPagado){
    $objResponse = new xajaxResponse();
	
    // DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS	
    foreach($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
		$arrayAnticipo = explode("|",$valor);
		$hddIdPago = $arrayAnticipo[0];
		if (!($hddIdPago > 0)) {
			$arrayIdAnticipo[] = $arrayAnticipo[2];
		}
    }

    $valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmDcto['txtIdCliente'],
		$frmDcto['lstTipoAnticipo'],
		$frmBuscarAnticipo['lstTipoDcto'],
		implode(",",$arrayIdAnticipo),
		$frmBuscarAnticipo['txtFechaDesde'],
		$frmBuscarAnticipo['txtFechaHasta'],
		$frmBuscarAnticipo['txtCriterioBuscarAnticipo']);
	
	switch ($frmBuscarAnticipo['lstTipoDcto']) {
    	case "FACTURA" : $objResponse->loadCommands(listaFactura(0, "numeroControl", "DESC", $valBusq)); break;
    	case "NOTA DEBITO" : $objResponse->loadCommands(listaNotaDebito(0, "idNotaCargo", "DESC", $valBusq)); break;
	}

    return $objResponse;
}

function buscarAnticipoNotaCreditoChequeTransferencia($frmBuscarAnticipoNotaCreditoChequeTransferencia, $frmDcto, $frmDetallePago, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPiePago = $frmListaPagoDcto['cbxPiePago'];
	
	if (isset($arrayObjPiePago)) {
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			if ($frmListaPagoDcto['txtIdFormaPago'.$valorPiePago] == $frmDetallePago['selTipoPago']
			&& $frmListaPagoDcto['hddEstatusPago'.$valorPiePago] == 1) {
				$arrayIdDocumento[] = $frmListaPagoDcto['txtIdNumeroDctoPago'.$valorPiePago];
			}
		}
	}
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarAnticipoNotaCreditoChequeTransferencia['txtCriterioAnticipoNotaCreditoChequeTransferencia'],
		$frmDcto['txtIdCliente'],
		$frmDetallePago['selTipoPago'],
		(($arrayIdDocumento) ? implode(",",$arrayIdDocumento) : ""));
		
	$objResponse->loadCommands(listaAnticipoNotaCreditoChequeTransferencia(0,"","",$valBusq));
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function calcularPagos($frmListaPagoDcto, $frmListaDctoPagado, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('fieldsetFormaPago').className = '';
	byId('fieldsetDesglosePago').className = '';");
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPiePago = $frmListaPagoDcto['cbxPiePago'];
	if (isset($arrayObjPiePago)) {
		$i = 0;
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmPago:".$valorPiePago,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmPago:".$valorPiePago,"innerHTML",$i);
			
			// 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
			if (!in_array($frmListaPagoDcto['txtIdConceptoPago'.$valorPiePago], array(7,8,9))) {
				$txtMontoPagadoAnticipo += ($frmListaPagoDcto['hddEstatusPago'.$valorPiePago] == 1) ? str_replace(",", "", $frmListaPagoDcto['txtMonto'.$valorPiePago]) : 0;
			}
		}
	}
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObjPiePago) > 0) ? implode("|",$arrayObjPiePago) : ""));
	
	$objResponse->assign("txtMontoPagadoAnticipo","value",number_format($txtMontoPagadoAnticipo,2,".",","));
	$objResponse->assign("txtMontoPorPagar","value",number_format(str_replace(",", "", $frmDcto['txtTotalAnticipo']) - $txtMontoPagadoAnticipo,2,".",","));
	
	
    $txtMontoPago = str_replace(",", "", $frmDcto['txtTotalAnticipo']); // Viene con formato 0,000.00	
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmListaDctoPagado['cbx4'];
	if (isset($arrayObj4)) {
		$i = 0;
		foreach ($arrayObj4 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDctoPagado:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDctoPago:".$valor,"innerHTML",$i);
			
			$arrayAnticipo = explode("|",$frmListaDctoPagado['cbxDctoAgregado'][$indice]);
			
			$txtTotalDctoPagadosAnticipo += ($frmListaDctoPagado['hddEstatusPago'.$valor] == 1) ? str_replace(",", "", $frmListaDctoPagado['txtMontoPagado'.$valor]) : 0;
		}
	}
	
    $totalFaltaPorPagar = $txtMontoPago - $txtTotalDctoPagadosAnticipo;
	
    $objResponse->assign("txtTotalDctoPagado","value",number_format($txtTotalDctoPagadosAnticipo, 2, ".", ","));
    $objResponse->assign("txtMontoRestante","value",number_format($totalFaltaPorPagar, 2, ".", ","));
	
	if (count($arrayObj4) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtMontoPago').readOnly = true;
		byId('txtMontoPago').className = 'inputInicial';");
    } else if (!($frmDcto['hddIdCheque'] > 0)) {
		$objResponse->script("
		byId('txtMontoPago').readOnly = false;
		byId('txtMontoPago').className = 'inputHabilitado';");
    }
	
	return $objResponse;
}

function calcularPagosDeposito($frmDeposito, $frmDetallePago) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	if (isset($arrayObj3)) {
		$i = 0;
		foreach ($arrayObj3 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDetalle:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDetalle:".$valor,"innerHTML",$i);
			
			$txtMontoPagadoDeposito += str_replace(",", "", $frmDeposito['txtMontoDetalleDeposito'.$valor]);
		}
	}
	$objResponse->assign("hddObjDetallePagoDeposito","value",((count($arrayObj3) > 0) ? implode("|",$arrayObj3) : ""));
	
	$objResponse->assign("txtTotalDeposito","value",number_format($txtMontoPagadoDeposito, 2, ".", ","));
	$objResponse->assign("txtSaldoDepositoBancario","value",number_format(str_replace(",", "", $frmDetallePago['txtMontoPago']) - $txtMontoPagadoDeposito, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstBancoCliente($nombreObjeto, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected."  value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstBancoCompania($tipoPago = "", $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_cargaLstCuentaCompania(this.value,".$tipoPago.");\"";
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select name=\"selBancoCompania\" id=\"selBancoCompania\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstCuentaCompania($row["idBanco"], $tipoPago)); }
		
		$html .= "<option ".$selected."  value=\"".$row["idBanco"]."\">".utf8_encode($row["banco"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselBancoCompania","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstConceptoPago($nombreObjeto){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_formapago = 11 AND estatus = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(($idCajaPpal == 1) ? "id_concepto NOT IN (2)" : "id_concepto NOT IN (1,2,6,7,8,9)");
	
	$query = sprintf("SELECT * FROM cj_conceptos_formapago %s ORDER BY descripcion ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['id_concepto'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["id_concepto"]."\">".utf8_encode($row["descripcion"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCuentaCompania($idBanco, $tipoPago, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_cargaLstTarjetaCuenta(this.value,".$tipoPago.");\"";
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas
	WHERE idBanco = %s
		AND estatus = 1",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select name=\"selNumeroCuenta\" id=\"selNumeroCuenta\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idCuentas'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstTarjetaCuenta($row["idCuentas"], $tipoPago)); }
		
		$html .= "<option ".$selected." value=\"".$row["idCuentas"]."\">".utf8_encode($row["numeroCuentaCompania"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("divselNumeroCuenta","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTarjetaCuenta($idCuenta, $tipoPago, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 5) { // Tarjeta de Crédito
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito FROM tipotarjetacredito 
		WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta FROM te_retencion_punto
										WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN (6))
		ORDER BY descripcionTipoTarjetaCredito",
			valTpDato($idCuenta, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select id=\"tarjeta\" name=\"tarjeta\" class=\"inputHabilitado\" onchange=\"xajax_asignarPorcentajeTarjetaCredito(".$idCuenta.",this.value)\" style=\"width:200px\">";
			$html .= "<option value=\"\">[ Seleccione ]</option>";
		while($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row['idTipoTarjetaCredito'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row["idTipoTarjetaCredito"])); }
			
			$html .= "<option ".$selected." value=\"".$row['idTipoTarjetaCredito']."\">".$row['descripcionTipoTarjetaCredito']."</option>";
		}
		$html .= "</select>";
		$objResponse->assign("tdtarjeta","innerHTML",$html);
	} else if ($tipoPago == 6) { // Tarjeta de Debito
		$query = sprintf("SELECT porcentaje_comision FROM te_retencion_punto WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta IN (6);",
			valTpDato($idCuenta,'int'));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	}
	
	return $objResponse;
}

function cargaLstTipoAnticipo($selId = "", $bloquearObj = false){//si es puerto rico, permitir cambio y uso de tipo de cheque suplidor
    $objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";

	$array = array("1" => "Cliente");
	$totalRows = count($array);
	
	$html .= "<select id=\"lstTipoAnticipo\" name=\"lstTipoAnticipo\" ".$class." ".$onChange." style=\"width:99%\">";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoAnticipo","innerHTML", $html);

    return $objResponse;
}

function cargaLstTipoDcto($selId = ""){
	$objResponse = new xajaxResponse();
	
	$array = array("FACTURA" => "Factura", "NOTA DEBITO" => "Nota de Débito");
	
	$html = "<select id=\"lstTipoDcto\" name=\"lstTipoDcto\" class=\"inputHabilitado\" onchange=\"byId('btnBuscarAnticipo').click();\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoDcto","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoPago($idFormaPago = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$idFormaPago = (is_array($idFormaPago)) ? implode(",",$idFormaPago) : $idFormaPago;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito
	// 9 = Retencion, 10 = Retencion I.S.L.R., 11 = Otro
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idFormaPago NOT IN (7,9,10)");
	
	if ($idFormaPago != "-1" && $idFormaPago != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idFormaPago IN (%s)",
			valTpDato($idFormaPago, "campo"));
	}
	
	$query = sprintf("SELECT * FROM formapagos %s ORDER BY nombreFormaPago ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"selTipoPago\" name=\"selTipoPago\" class=\"inputHabilitado\" onchange=\"asignarTipoPago(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPago($row["idFormaPago"])); }
		
		$html .= "<option ".$selected." value=\"".$row["idFormaPago"]."\">".$row["nombreFormaPago"]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoPagoDetalleDeposito($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM formapagos where idFormaPago <= 2");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select name=\"lstTipoPago\" id=\"lstTipoPago\" class=\"inputHabilitado\" onchange=\"asignarTipoPagoDetalleDeposito(this.value)\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPagoDetalleDeposito($row["idFormaPago"])); }
		
		$html .= "<option ".$selected." value=\"".$row["idFormaPago"]."\">".$row["nombreFormaPago"]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarDcto($idAnticipo, $idCliente, $idModulo, $vw, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('txtObservacion').className = 'inputHabilitado';");
	
	if ($idAnticipo > 0) {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';");
		
		// BUSCA LOS DATOS DEL ANTICIPO
		$queryAnticipo = sprintf("SELECT cxc_ant.*,
			IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, 0) AS saldoAnticipo,
			IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, NULL) AS estadoAnticipo,
			(CASE cxc_ant.estatus
				WHEN 1 THEN
					(CASE cxc_ant.estadoAnticipo
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado (No Asignado)'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
						WHEN 4 THEN 'No Cancelado (Asignado)'
					END)
				ELSE
					'Anulado'
			END) AS descripcion_estado_anticipo,
			
			IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
						AND cxc_pago.estatus IN (1,2)
						AND cxc_pago.id_concepto IS NOT NULL), 0) AS total_conceptos_pagos
		FROM cj_cc_anticipo cxc_ant WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
		$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
		
		if ($vw == "a") {
			$objResponse->script("
			byId('txtTotalAnticipo').className = 'inputSinFondo';
			byId('txtTotalAnticipo').readOnly = true;
			byId('txtObservacion').className = 'inputInicial';
			byId('txtObservacion').readOnly = true;
			
			byId('tblFormaPago').style.display = 'none';
			byId('trListaDctoPagado').style.display = '';");
			
			$objResponse->loadCommands(cargaLstTipoDcto());
		} else {
			$objResponse->script("
			byId('trListaDctoPagado').style.display = 'none';");
			
			if ((in_array($rowAnticipo['estadoAnticipo'],array(0))
				|| (in_array($rowAnticipo['estadoAnticipo'],array(4))
					&& $rowAnticipo['totalPagadoAnticipo'] > 0
					&& $rowAnticipo['saldoAnticipo'] == 0))
			&& $rowAnticipo['total_conceptos_pagos'] == 0) {
				$objResponse->script("
				byId('txtTotalAnticipo').className = 'inputCompletoHabilitado';
				byId('txtTotalAnticipo').readOnly = false;");
			} else {
				$objResponse->script("
				byId('txtTotalAnticipo').className = 'inputSinFondo';
				byId('txtTotalAnticipo').readOnly = true;");
			}
		}
		
		switch($rowAnticipo['estadoAnticipo']) {
			case "" : $classEstatus = "divMsjInfo5"; break;
			case 0 : $classEstatus = "divMsjError"; $imgEstatus = "<img src=\"../img/iconos/no_cancelado.png\">"; break;
			case 1 : $classEstatus = "divMsjInfo"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
			case 2 : $classEstatus = "divMsjAlerta"; $imgEstatus = "<img src=\"../img/iconos/cancelado_parcial.png\">"; break;
			case 3 : $classEstatus = "divMsjInfo3"; break;
			case 4 : $classEstatus = "divMsjInfo4"; break;
		}
		
		$idEmpresa = $rowAnticipo['id_empresa'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "", false));
		$objResponse->loadCommands(asignarEmpleado($rowAnticipo['id_empleado_creador'], false));
		$objResponse->loadCommands(asignarCliente($rowAnticipo['idCliente']));
		
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
		
		$objResponse->assign("hddIdAnticipo","value",$idAnticipo);
		$objResponse->loadCommands(cargaLstTipoAnticipo(1, true));
		$objResponse->assign("txtNumeroAnticipo","value",$rowAnticipo['numeroAnticipo']);
		$objResponse->assign("txtFecha","value",date(spanDateFormat, strtotime($rowAnticipo['fechaAnticipo'])));
		$objResponse->loadCommands(cargaLstModulo($rowAnticipo['idDepartamento'],"",true));
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowAnticipo['descripcion_estado_anticipo']);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowAnticipo['observacionesAnticipo']));
		
		if (in_array($rowAnticipo['idDepartamento'],array(2,4,5))) {
			$aVerDcto = sprintf("verVentana('../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
				$rowAnticipo['idAnticipo']);
		} else if (in_array($rowAnticipo['idDepartamento'],array(0,1,3))) {
			$aVerDcto = sprintf("verVentana('../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s', 960, 550);",
				$rowAnticipo['idAnticipo']);
		}
		
		$objResponse->script("
		byId('btnAnticipoPDF').style.display = '';
		byId('btnAnticipoPDF').onclick = function() { ".$aVerDcto." }");
		
		$objResponse->assign("txtTotalAnticipo","value",number_format($rowAnticipo['montoNetoAnticipo'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowAnticipo['saldoAnticipo'], 2, ".", ","));
		
		// BUSCA LOS DATOS DEL ANTICIPO
		$queryDetalleAnticipo = sprintf("SELECT cxc_pago.*,
			CONCAT_WS(' ', forma_pago.nombreFormaPago, IF(concepto_forma_pago.descripcion IS NOT NULL, CONCAT('(', concepto_forma_pago.descripcion, ')'), NULL)) AS nombreFormaPago,
			concepto_forma_pago.descripcion AS descripcion_concepto_forma_pago,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			banco_emp.nombreBanco AS nombre_banco_empresa
		FROM cj_cc_detalleanticipo cxc_pago
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoClienteDetalleAnticipo = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_emp.idBanco)
			INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsDetalleAnticipo = mysql_query($queryDetalleAnticipo);
		if (!$rsDetalleAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsDetalleAnticipo = mysql_num_rows($rsDetalleAnticipo);
		while ($rowDetalleAnticipo = mysql_fetch_assoc($rsDetalleAnticipo)) {
			if ($rowDetalleAnticipo['id_cheque'] > 0) {
				$txtIdNumeroDctoPago = $rowDetalleAnticipo['id_cheque'];
			} else if ($rowDetalleAnticipo['id_transferencia'] > 0) {
				$txtIdNumeroDctoPago = $rowDetalleAnticipo['id_transferencia'];
			} else if (in_array($rowDetalleAnticipo['id_forma_pago'],array(7,8))) { // 7 = Anticipo, 8 = Nota de Crédito
				$txtIdNumeroDctoPago = $rowDetalleAnticipo['numeroControlDetalleAnticipo'];
			}
			
			$Result1 = insertarItemMetodoPago($contFila, $rowDetalleAnticipo['idDetalleAnticipo'], $rowDetalleAnticipo['id_forma_pago'], $txtIdNumeroDctoPago, $rowDetalleAnticipo['numeroControlDetalleAnticipo'], $rowDetalleAnticipo['bancoClienteDetalleAnticipo'], $rowDetalleAnticipo['numeroCuentaCliente'], $rowDetalleAnticipo['bancoCompaniaDetalleAnticipo'], $rowDetalleAnticipo['numeroCuentaCompania'], $txtFechaDeposito, $lstTipoTarjeta, $rowDetalleAnticipo['id_concepto'], $rowDetalleAnticipo['montoDetalleAnticipo'], $rowDetalleAnticipo['estatus']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObjPiePago[] = $contFila;
			}
		}
		
		switch($rowAnticipo['estadoAnticipo']) { // 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
			case 0 : $objResponse->loadCommands(cargaLstTipoPago("1,2,3,4,5,6,8,11","1")); break;
			default : $objResponse->loadCommands(cargaLstTipoPago("1,2,3,4,5,6,8","1")); break;
		}
		
		$objResponse->call(asignarTipoPago,"1");
		
		if ($vw == "a") {
			// CARGA LOS PAGOS EN DONDE SE A APLICADO EL ANTICIPO
			$queryPago = sprintf("SELECT q.*,
				
				(CASE q.tipoDocumento
					WHEN ('FA') THEN
						cxc_fact.fechaRegistroFactura
					WHEN ('ND') THEN
						cxc_nd.fechaRegistroNotaCargo
					WHEN ('AN') THEN
						cxc_ant.fechaAnticipo
				END) AS fechaRegistroFactura,
				
				(CASE q.tipoDocumento
					WHEN ('FA') THEN
						cxc_fact.numeroFactura
					WHEN ('ND') THEN
						cxc_nd.numeroNotaCargo
					WHEN ('AN') THEN
						cxc_ant.numeroAnticipo
				END) AS numeroFactura,
				
				(CASE q.tipoDocumento
					WHEN ('FA') THEN
						cxc_fact.idDepartamentoOrigenFactura
					WHEN ('ND') THEN
						cxc_nd.idDepartamentoOrigenNotaCargo
					WHEN ('AN') THEN
						cxc_ant.idDepartamento
				END) AS id_modulo,
				
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				
				(CASE q.tipoDocumento
					WHEN ('FA') THEN
						cxc_fact.estadoFactura
					WHEN ('ND') THEN
						cxc_nd.estadoNotaCargo
					WHEN ('AN') THEN
						cxc_ant.estadoAnticipo
				END) AS estadoFactura,
				
				(CASE q.tipoDocumento
					WHEN ('FA') THEN
						(CASE cxc_fact.estadoFactura
							WHEN 0 THEN 'No Cancelado'
							WHEN 1 THEN 'Cancelado'
							WHEN 2 THEN 'Cancelado Parcial'
						END)
					WHEN ('ND') THEN
						(CASE cxc_nd.estadoNotaCargo
							WHEN 0 THEN 'No Cancelado'
							WHEN 1 THEN 'Cancelado'
							WHEN 2 THEN 'Cancelado Parcial'
						END)
					WHEN ('AN') THEN
						(CASE cxc_ant.estadoAnticipo
							WHEN 0 THEN 'No Cancelado'
							WHEN 1 THEN 'Cancelado (No Asignado)'
							WHEN 2 THEN 'Asignado Parcial'
							WHEN 3 THEN 'Asignado'
							WHEN 4 THEN 'No Cancelado (Asignado)'
						END)
				END) AS descripcion_estado_factura,
				
				(CASE q.tipoDocumento
					WHEN ('FA') THEN
						cxc_fact.observacionFactura
					WHEN ('ND') THEN
						cxc_nd.observacionNotaCargo
					WHEN ('AN') THEN
						cxc_ant.observacionesAnticipo
				END) AS observacionFactura,
				
				(CASE q.tipoDocumento
					WHEN ('AN') THEN
						(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
						FROM cj_cc_detalleanticipo cxc_pago
							INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
						WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
							AND cxc_pago.id_forma_pago IN (11))
				END) AS descripcion_concepto_forma_pago,
				
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM (SELECT
						cxc_pago.idPago,
						cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
						cxc_ec.tipoDocumentoN,
						cxc_ec.tipoDocumento,
						cxc_pago.id_factura,
						cxc_pago.fechaPago,
						cxc_pago.formaPago,
						cxc_pago.tipoCheque,
						cxc_pago.numeroDocumento AS id_anticipo,
						cxc_pago.montoPagado,
						cxc_pago.estatus,
						cxc_pago.tiempo_registro,
						'an_pagos' AS tabla
					FROM an_pagos cxc_pago
						LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_pago.id_factura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
					WHERE cxc_pago.formaPago IN (7)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT
						cxc_pago.idPago,
						cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
						cxc_ec.tipoDocumentoN,
						cxc_ec.tipoDocumento,
						cxc_pago.id_factura,
						cxc_pago.fechaPago,
						cxc_pago.formaPago,
						cxc_pago.tipoCheque,
						cxc_pago.numeroDocumento AS id_anticipo,
						cxc_pago.montoPagado,
						cxc_pago.estatus,
						cxc_pago.tiempo_registro,
						'sa_iv_pagos' AS tabla
					FROM sa_iv_pagos cxc_pago
						LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_pago.id_factura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
					WHERE cxc_pago.formaPago IN (7)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT
						cxc_pago.id_det_nota_cargo,
						cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
						cxc_ec.tipoDocumentoN,
						cxc_ec.tipoDocumento,
						cxc_pago.idNotaCargo,
						cxc_pago.fechaPago,
						cxc_pago.idFormaPago,
						cxc_pago.tipoCheque,
						cxc_pago.numeroDocumento AS id_anticipo,
						cxc_pago.monto_pago,
						cxc_pago.estatus,
						cxc_pago.tiempo_registro,
						'cj_det_nota_cargo' AS tabla
					FROM cj_det_nota_cargo cxc_pago
						LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_pago.idNotaCargo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'ND')
					WHERE cxc_pago.idFormaPago IN (7)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT
						cxc_pago.idDetalleAnticipo,
						cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
						cxc_ec.tipoDocumentoN,
						cxc_ec.tipoDocumento,
						cxc_pago.idAnticipo,
						cxc_pago.fechaPagoAnticipo,
						cxc_pago.id_forma_pago,
						NULL AS tipoCheque,
						cxc_pago.numeroControlDetalleAnticipo AS id_anticipo,
						cxc_pago.montoDetalleAnticipo,
						cxc_pago.estatus,
						cxc_pago.tiempo_registro,
						'cj_cc_detalleanticipo' AS tabla
					FROM cj_cc_detalleanticipo cxc_pago
						LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_pago.idAnticipo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'AN')
					WHERE cxc_pago.id_forma_pago IN (7)
						AND cxc_pago.estatus IN (1)) AS q
				LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (q.id_factura = cxc_fact.idFactura AND q.tipoDocumento IN ('FA'))
				LEFT JOIN cj_cc_notadecargo cxc_nd ON (q.id_factura = cxc_nd.idNotaCargo AND q.tipoDocumento IN ('ND'))
				LEFT JOIN cj_cc_anticipo cxc_ant ON (q.id_factura = cxc_ant.idAnticipo AND q.tipoDocumento IN ('AN'))
				RIGHT JOIN cj_cc_cliente cliente ON ((cxc_fact.idCliente = cliente.id AND q.tipoDocumento IN ('FA'))
					OR (cxc_nd.idCliente = cliente.id AND q.tipoDocumento IN ('ND'))
					OR (cxc_ant.idCliente = cliente.id AND q.tipoDocumento IN ('AN')))
				RIGHT JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON ((cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg AND q.tipoDocumento IN ('FA'))
					OR (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg AND q.tipoDocumento IN ('ND'))
					OR (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg AND q.tipoDocumento IN ('AN')))
			WHERE q.id_anticipo = %s
			ORDER BY q.tiempo_registro ASC;",
				valTpDato($idAnticipo, "int"));
			$rsPago = mysql_query($queryPago);
			if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$arrayObj4 = NULL;
			while ($rowPago = mysql_fetch_assoc($rsPago)) {
				$Result1 = insertarItemDctoPagado($contFila, $rowPago['idPago'], $rowPago['tabla'], $rowPago['tipoDocumento'], $rowPago['id_factura'], $rowPago['id_modulo'], $rowPago['fechaPago'], $rowPago['tiempo_registro'], $rowPago['nombre_empresa'], $rowPago['fechaRegistroFactura'], $rowPago['numeroFactura'], $rowPago['id_cliente'], $rowPago['nombre_cliente'], $rowPago['estadoFactura'], $rowPago['descripcion_concepto_forma_pago'], $rowPago['observacionFactura'], $rowPago['descripcion_estado_factura'], $rowPago['montoPagado']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj4[] = $contFila;
				}
			}
			
			$objResponse->script("calcularPagos();");
		}
		
		$objResponse->script("
		xajax_calcularPagos(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'));");
	} else {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObjPiePago = $frmListaPagoDcto['cbxPiePago'];
		
		// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
		if (isset($arrayObjPiePago)) {
			foreach($arrayObjPiePago as $indice => $valor) {
				$objResponse->script("
				fila = document.getElementById('trItmPago:".$valor."');
				padre = fila.parentNode;
				padre.removeChild(fila);");
			}
		}
		
		$objResponse->script("
		document.forms['frmDcto'].reset();
		byId('hddIdAnticipo').value = '';
		byId('hddNuevoAnticipo').value = '';
		
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtTotalAnticipo').className = 'inputCompletoHabilitado';
		byId('txtTotalAnticipo').readOnly = false;
		
		byId('trListaDctoPagado').style.display = 'none';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts'], false));
		$objResponse->loadCommands(asignarCliente($idCliente));
		
		$objResponse->assign("txtFecha","value",date(spanDateFormat));

		$objResponse->loadCommands(cargaLstTipoAnticipo(1, true));
		$objResponse->loadCommands(cargaLstModulo($idModulo));
		
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
		
		$objResponse->loadCommands(cargaLstTipoPago("","1"));
		$objResponse->call(asignarTipoPago,"1");
	}
	
	$objResponse->script("
	cerrarVentana = false;");
	
	return $objResponse;
}

function cargarSaldoDocumento($formaPago, $idDocumento, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	if ($formaPago == 2) { // CHEQUES
		$documento = "Cheque";
		
		$query = sprintf("SELECT saldo_cheque AS saldoDocumento, numero_cheque AS numeroDocumento
		FROM cj_cc_cheque WHERE id_cheque = %s", $idDocumento);
	} else if ($formaPago == 4) { // TRANSFERENCIAS
		$documento = "Transferencia";
		
		$query = sprintf("SELECT saldo_transferencia AS saldoDocumento, numero_transferencia AS numeroDocumento
		FROM cj_cc_transferencia WHERE id_transferencia = %s", $idDocumento);
	} else if ($formaPago == 7) { // ANTICIPOS
		$documento = "Anticipo";
		
		$query = sprintf("SELECT
			saldoAnticipo AS saldoDocumento,
			numeroAnticipo AS numeroDocumento
		FROM cj_cc_anticipo
		WHERE idAnticipo = %s;",
			valTpDato($idDocumento, "int"));
	} else if ($formaPago == 8) { // NOTAS DE CREDITO
		$documento = "Nota de Crédito";
		
		$query = sprintf("SELECT
			saldoNotaCredito AS saldoDocumento,
			numeracion_nota_credito AS numeroDocumento
		FROM cj_cc_notacredito
		WHERE idNotaCredito = %s;",
			valTpDato($idDocumento, "int"));
	}
	$rsSelectDocumento = mysql_query($query);
	if (!$rsSelectDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowSelectDocumento = mysql_fetch_array($rsSelectDocumento);
	
	$objResponse->assign("hddIdDocumento","value",$idDocumento);
	$objResponse->assign("txtNroDocumento","value",$rowSelectDocumento['numeroDocumento']);
	$objResponse->assign("txtSaldoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'], 2, ".", ","));
	$objResponse->assign("txtMontoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'], 2, ".", ","));
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML",$documento);
	
	$objResponse->script("
	byId('txtMontoDocumento').focus();
	byId('txtMontoDocumento').select();");
		
	return $objResponse;
}

function cargarSaldoDocumentoPagar($tipoDcto, $idDocumento, $frmListaDctoPagado){
    $objResponse = new xajaxResponse();

	//ojo se usa el monto completo porque ya no tiene saldo al agregarse a la factura
	switch ($tipoDcto) {
		case "FA" : 
			$documento = "Factura";
			$queryDocumento = sprintf("SELECT
				cxc_fact.numeroFactura AS numeroDocumento,
				IFNULL(cxc_fact.saldoFactura,0) AS saldoDocumento,
				
				IFNULL((SELECT SUM(q.montoPagado)
						FROM (SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM an_pagos cxc_pago
							WHERE cxc_pago.estatus IN (2)
							
							UNION
							
							SELECT cxc_pago.idPago, cxc_pago.id_factura, cxc_pago.montoPagado FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.estatus IN (2)) AS q
						WHERE q.id_factura = cxc_fact.idFactura),0) AS monto_pagos_pendientes
			FROM cj_cc_encabezadofactura cxc_fact
			WHERE cxc_fact.idFactura = %s",
				valTpDato($idDocumento, "int"));
			break;
		case "ND" : 
			$documento = "Nota de Débito";
			$queryDocumento = sprintf("SELECT
				cxc_nd.numeroNotaCargo AS numeroDocumento,
				IFNULL(cxc_nd.saldoNotaCargo,0) AS saldoDocumento,
			
				IFNULL((SELECT SUM(q.monto_pago)
						FROM (SELECT cxc_pago.id_det_nota_cargo, cxc_pago.idNotaCargo, cxc_pago.monto_pago FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.estatus IN (2)) AS q
						WHERE q.idNotaCargo = cxc_nd.idNotaCargo),0) AS monto_pagos_pendientes
			FROM cj_cc_notadecargo cxc_nd
			WHERE cxc_nd.idNotaCargo = %s",
				valTpDato($idDocumento, "int"));
			break;
		case "AN" : 
			$documento = "Anticipo";
			$queryDocumento = sprintf("SELECT
				cxc_ant.numeroAnticipo AS numeroDocumento,
				(IFNULL(cxc_ant.montoNetoAnticipo,0)
					- IFNULL(cxc_ant.totalPagadoAnticipo,0)) AS saldoDocumento
			FROM cj_cc_anticipo cxc_ant
			WHERE cxc_ant.idAnticipo = %s",
				valTpDato($idDocumento, "int"));
			break;
	}
	$rsDocumento = mysql_query($queryDocumento);
	if (!$rsDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDocumento = mysql_fetch_array($rsDocumento);
	
	foreach ($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
		$arrayAnticipo = explode("|",$valor);
		$hddIdPago = $arrayAnticipo[0];
		$hddTipoDocumento = $arrayAnticipo[1];
		$idAnticipo = $arrayAnticipo[2];
		
        if (!($hddIdPago > 0) && $hddTipoDocumento == $tipoDcto && $idAnticipo == $idDocumento) {
			usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante2').click();");
			return $objResponse->alert("El documento seleccionado ya se encuentra agregado");
        }
    }
	
    $objResponse->assign("hddIdDocumento","value",$idDocumento);
	$objResponse->assign("hddTipoDocumento","value",$tipoDcto);
    $objResponse->assign("txtNroDocumento","value",$rowDocumento['numeroDocumento']); 
    $objResponse->assign("txtSaldoDocumento","value",number_format($rowDocumento['saldoDocumento'], 2, ".", ","));
    $objResponse->assign("txtSaldoDiferidoDocumento","value",number_format($rowDocumento['monto_pagos_pendientes'], 2, ".", ","));
    $objResponse->assign("txtMontoDocumento","value",number_format($rowDocumento['saldoDocumento'], 2, ".", ","));
	
    $objResponse->assign("tdFlotanteTitulo2","innerHTML",$documento);
	
	$objResponse->script("
	byId('txtMontoDocumento').focus();
	byId('txtMontoDocumento').select();");

    return $objResponse;
}

function eliminarDetalleDeposito($pos, $frmDetallePago) {
	$objResponse = new xajaxResponse();
	
	$arrayPosiciones = explode("|",$frmDetallePago['hddObjDetalleDeposito']);
	$arrayFormaPago = explode("|",$frmDetallePago['hddObjDetalleDepositoFormaPago']);
	$arrayBanco = explode("|",$frmDetallePago['hddObjDetalleDepositoBanco']);
	$arrayNroCuenta = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCuenta']);
	$arrayNroCheque = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCheque']);
	$arrayMonto = explode("|",$frmDetallePago['hddObjDetalleDepositoMonto']);
	
	$cadenaPosiciones = "";
	$cadenaFormaPago = "";
	$cadenaBanco = "";
	$cadenaNroCuenta = "";
	$cadenaNroCheque = "";
	$cadenaMonto = "";
	
	foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
		if ($valorDeposito != $pos && $valorDeposito != '') {
			$cadenaPosiciones .= $valorDeposito."|";
			$cadenaFormaPago .= $arrayFormaPago[$indiceDeposito]."|";
			$cadenaBanco .= $arrayBanco[$indiceDeposito]."|";
			$cadenaNroCuenta .= $arrayNroCuenta[$indiceDeposito]."|";
			$cadenaNroCheque .= $arrayNroCheque[$indiceDeposito]."|";
			$cadenaMonto .= $arrayMonto[$indiceDeposito]."|";
		}
	}
	
	$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosiciones);
	$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPago);
	$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBanco);
	$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuenta);
	$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroCheque);
	$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMonto);
	
	return $objResponse;
}

function eliminarPago($frmListaPagoDcto, $pos) {
	$objResponse = new xajaxResponse();
	
	if ($frmListaPagoDcto['txtIdFormaPago'.$pos] == 3)
		$objResponse->script("xajax_eliminarDetalleDeposito(".$pos.",xajax.getFormValues('frmDetallePago'))");
		
	$objResponse->script("
	fila = document.getElementById('trItmPago:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function eliminarPagoDetalleDeposito($frmDeposito, $pos) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	fila = document.getElementById('trItmDetalle:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
			
	$montoEliminado = $frmDeposito['txtMontoDetalleDeposito'.$pos];
	
	$objResponse->script("xajax_calcularPagosDeposito(xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDetallePago'))");
	
	return $objResponse;
}

function formDeposito($frmDeposito) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj3)) {
		foreach($arrayObj3 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmDetalle:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
				
	$objResponse->loadCommands(cargaLstTipoPagoDetalleDeposito());
	$objResponse->loadCommands(cargaLstBancoCliente("lstBancoDeposito"));
	
	$objResponse->script("
	byId('txtSaldoDepositoBancario').value = byId('txtMontoPago').value;
	byId('txtTotalDeposito').value = '0.00';");
	
	return $objResponse;
}

function guardarAnticipo($frmDcto, $frmDetallePago, $frmListaPagoDcto, $frmListaDctoPagado){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if ((!xvalidaAcceso($objResponse,"cj_anticipo_list","insertar") && in_array($idCajaPpal, array(1)))
	|| (!xvalidaAcceso($objResponse,"cjrs_anticipo_list","insertar") && in_array($idCajaPpal, array(2)))) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPiePago = $frmListaPagoDcto['cbxPiePago'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmListaPagoDcto['cbx3'];
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$idAnticipo = $frmDcto['hddIdAnticipo'];
	
	$idModulo = $frmDcto['lstModulo'];
	$txtTotalAnticipo = $frmDcto['txtTotalAnticipo'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// VERIFICA SI EXISTE ALGUNA FORMA DE PAGO OTRO CON ESTATUS PENDIENTE
	foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
		if ($idAnticipo > 0) {
			$txtMontoPagadoAnticipo += ($frmListaPagoDcto['hddEstatusPago'.$valorPiePago] == 1) ? str_replace(",", "", $frmListaPagoDcto['txtMonto'.$valorPiePago]) : 0;
		} else {
			// 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
			if (in_array($frmListaPagoDcto['txtIdConceptoPago'.$valorPiePago], array(7,8,9))) {
				$existePagoPendiente = true;
				$txtMontoOtroAnticipo += (in_array($frmListaPagoDcto['hddEstatusPago'.$valorPiePago], array(1,2))) ? str_replace(",", "", $frmListaPagoDcto['txtMonto'.$valorPiePago]) : 0;
			}
			$txtMontoPagadoAnticipo += (in_array($frmListaPagoDcto['hddEstatusPago'.$valorPiePago], array(1,2))) ? str_replace(",", "", $frmListaPagoDcto['txtMonto'.$valorPiePago]) : 0;
		}
	}
	
	if ($existePagoPendiente == true) {
		if (!(($txtMontoOtroAnticipo == str_replace(",", "", $frmDcto['txtTotalAnticipo']) && $txtMontoOtroAnticipo == $txtMontoPagadoAnticipo)
		|| $txtMontoPagadoAnticipo == str_replace(",", "", $frmDcto['txtTotalAnticipo']))) {
			return $objResponse->alert('El desglose de los pagos deben coincidir con el monto del anticipo');
		}
	} else {
		if (str_replace(",", "", $frmListaPagoDcto['txtMontoPagadoAnticipo']) > str_replace(",", "", $frmDcto['txtTotalAnticipo'])) {
			return $objResponse->alert('El monto a pagar es mayor al monto total del anticipo');
		}
	}
	
	if ($idAnticipo > 0) {
		// BUSCA LOS DATOS DEL ANTICIPO
		$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
		$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
		
		$existeCambioMonto = ($rowAnticipo['montoNetoAnticipo'] != str_replace(",", "", $frmDcto['txtTotalAnticipo'])) ? true : false;
		
		$updateSQL = sprintf("UPDATE cj_cc_anticipo cxc_ant SET
			cxc_ant.montoNetoAnticipo = %s,
			cxc_ant.observacionesAnticipo = %s
		WHERE cxc_ant.idAnticipo = %s;",
			valTpDato($txtTotalAnticipo, "real_inglesa"),
			valTpDato($frmDcto['txtObservacion'], "text"),
			valTpDato($idAnticipo, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		$numeroActualAnticipo = $frmDcto['txtNumeroAnticipo'];
	} else {
		// NUMERACION DEL DOCUMENTO (ANTICIPO)
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(((in_array($idCajaPpal,array(1))) ? 43 : 42), "int"), // 42 = Anticipo CXC Repuestos y Servicios, 43 = Anticipo CXC Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (ANTICIPO)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualAnticipo = $numeroActual;
		
		// INSERTA LOS DATOS DEL ANTICIPO
		$insertSQL = sprintf("INSERT INTO cj_cc_anticipo (idCliente, id_empleado_creador, montoNetoAnticipo, saldoAnticipo, totalPagadoAnticipo, fechaAnticipo, observacionesAnticipo, estadoAnticipo, numeroAnticipo, idDepartamento, id_empresa)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idCliente, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($txtTotalAnticipo, "real_inglesa"),
			valTpDato($txtTotalAnticipo, "real_inglesa"),
			valTpDato($txtTotalAnticipo, "real_inglesa"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato($frmDcto['txtObservacion'], "text"),
			valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado/No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
			valTpDato($numeroActualAnticipo, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($idEmpresa, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idAnticipo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("AN", "text"),
			valTpDato($idAnticipo, "int"),
			valTpDato("NOW()", "campo"),
			valTpDato("4", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	if (isset($arrayObjPiePago)) {
		$existeNuevoPago = false;
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			if (!($frmListaPagoDcto['hddIdPago'.$valorPiePago] > 0)) {
				$existeNuevoPago = true;
			}
		}
		
		if ($existeNuevoPago == true) {
			// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
			$queryAperturaCaja = sprintf("SELECT * FROM ".$apertCajaPpal." ape
			WHERE idCaja = %s
				AND statusAperturaCaja IN (1,2)
				AND (ape.id_empresa = %s
					OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
											WHERE suc.id_empresa = %s));",
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			$rsAperturaCaja = mysql_query($queryAperturaCaja);
			if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
			
			$idApertura = $rowAperturaCaja['id'];
			$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
			
			$arrayObjPago = array();
			foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
				$txtIdFormaPago = $frmListaPagoDcto['txtIdFormaPago'.$valorPiePago];
				$hddIdPago = $frmListaPagoDcto['hddIdPago'.$valorPiePago];
				
				if (!($hddIdPago > 0)) {
					if (isset($txtIdFormaPago)) {
						$arrayDetallePago = array(
							"idCajaPpal" => $idCajaPpal,
							"apertCajaPpal" => $apertCajaPpal,
							"idApertura" => $idApertura,
							"fechaRegistroPago" => $fechaRegistroPago,
								//"idReporteImpresion" => $idReporteImpresion,
							"cbxPosicionPago" => $valorPiePago,
							"hddIdPago" => $hddIdPago,
							"txtIdFormaPago" => $txtIdFormaPago,
							"txtIdConceptoPago" => $frmListaPagoDcto['txtIdConceptoPago'.$valorPiePago],
							"txtIdNumeroDctoPago" => $frmListaPagoDcto['txtIdNumeroDctoPago'.$valorPiePago],
							"txtNumeroDctoPago" => $frmListaPagoDcto['txtNumeroDctoPago'.$valorPiePago],
							"txtIdBancoCliente" => $frmListaPagoDcto['txtIdBancoCliente'.$valorPiePago],
							"txtCuentaClientePago" => $frmListaPagoDcto['txtCuentaClientePago'.$valorPiePago],
							"txtIdBancoCompania" => $frmListaPagoDcto['txtIdBancoCompania'.$valorPiePago],
							"txtIdCuentaCompaniaPago" => $frmListaPagoDcto['txtIdCuentaCompaniaPago'.$valorPiePago],
							"txtCuentaCompaniaPago" => asignarNumeroCuenta($frmListaPagoDcto['txtIdCuentaCompaniaPago'.$valorPiePago]),
							"txtFechaDeposito" => $frmListaPagoDcto['txtFechaDeposito'.$valorPiePago],
							"txtTipoTarjeta" => $frmListaPagoDcto['txtTipoTarjeta'.$valorPiePago],
							"hddObjDetalleDeposito" => $frmDetallePago['hddObjDetalleDeposito'],
							"hddObjDetalleDepositoFormaPago" => $frmDetallePago['hddObjDetalleDepositoFormaPago'],
							"hddObjDetalleDepositoBanco" => $frmDetallePago['hddObjDetalleDepositoBanco'],
							"hddObjDetalleDepositoNroCuenta" => $frmDetallePago['hddObjDetalleDepositoNroCuenta'],
							"hddObjDetalleDepositoNroCheque" => $frmDetallePago['hddObjDetalleDepositoNroCheque'],
							"hddObjDetalleDepositoMonto" => $frmDetallePago['hddObjDetalleDepositoMonto'],
							"txtMonto" => $frmListaPagoDcto['txtMonto'.$valorPiePago],
							"cbxCondicionMostrar" => $frmListaPagoDcto['cbxCondicionMostrar'.$valorPiePago],
							"lstSumarA" => $frmListaPagoDcto['lstSumarA'.$valorPiePago]
						);
						
						$arrayObjPago[] = $arrayDetallePago;
					}
				}
			}
			
			$objDcto = new Documento;
			$objDcto->idModulo = $idModulo;
			$objDcto->idDocumento = $idAnticipo;
			$objDcto->idEmpresa = $idEmpresa;
			$objDcto->idCliente = $idCliente;
			$Result1 = $objDcto->guardarReciboPagoCxCAN(
				$idCajaPpal,
				$apertCajaPpal,
				$idApertura,
				$fechaRegistroPago,
				$arrayObjPago);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			$idReporteImpresion = $Result1['idReporteImpresion'];
		}
	}
	
	
	// DOCUMENTOS POR COBRAR
	if (isset($frmListaDctoPagado['cbxDctoAgregado'])) {
		// BUSCA LOS DATOS DEL ANTICIPO (0 = Anulado; 1 = Activo)
		$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo cxc_ant
		WHERE cxc_ant.idAnticipo = %s
			AND cxc_ant.estatus = 1;",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAnticipo = mysql_fetch_array($rsAnticipo);
		
		
		// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$queryAperturaCaja = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE idCaja = %s
			AND statusAperturaCaja IN (1,2)
			AND (ape.id_empresa = %s
				OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
												WHERE suc.id_empresa = %s));",
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsAperturaCaja = mysql_query($queryAperturaCaja);
		if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$idApertura = $rowAperturaCaja['id'];
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
		$idBancoCliente = 1;
		$idBancoCompania = 1;
		$numeroCuentaCliente = "-";
		$numeroCuentaCompania = "-";
		
		foreach($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
			$arrayAnticipo = explode("|",$valor);
			$hddIdPago = $arrayAnticipo[0];
			$tipoDcto = $arrayAnticipo[1];
			$montoPagadoAnticipo = str_replace(",", "", $arrayAnticipo[3]);
			
			if ($tipoDcto == "FA" && !($hddIdPago > 0)) {
				$idFactura = $arrayAnticipo[2];
				
				//consulto anticipo para verificar monto, se usa monto porque el saldo ya esta en cero al cargarse a una fact
				$queryFactura = sprintf("SELECT
					cxc_fact.idDepartamentoOrigenFactura,
					cxc_fact.numeroFactura,
					cxc_fact.saldoFactura AS monto_faltante_pago
				FROM cj_cc_encabezadofactura cxc_fact
				WHERE cxc_fact.idFactura = %s;",
					valTpDato($idFactura, "int"));
				$rsFactura = mysql_query($queryFactura);
				if (!$rsFactura) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
				$rowFactura = mysql_fetch_assoc($rsFactura);
				
				$idModulo = $rowFactura['idDepartamentoOrigenFactura'];
				$nuevoSaldoFactura = $rowFactura['monto_faltante_pago'] - $montoPagadoFactura;
				
				if ($nuevoSaldoFactura < 0) {
					return $objResponse->alert("El pago de la factura Nro ".$rowFactura['numeroFactura']." no puede quedar en negativo: ".$nuevoSaldoFactura);
				}
				
				$arrayObjPago = array();
				$arrayDetallePago = array(
					"idCajaPpal" => $idCajaPpal,
					"apertCajaPpal" => $apertCajaPpal,
					"idApertura" => $idApertura,
					"numeroActualFactura" => $rowFactura['numeroFactura'],
					"fechaRegistroPago" => $fechaRegistroPago,
						//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
						//"idEncabezadoPago" => $idEncabezadoPago,
					"cbxPosicionPago" => $valor,
					"hddIdPago" => $hddIdPago,
					"txtIdFormaPago" => 7, // 7 = Anticipo
					"txtIdNumeroDctoPago" => $idAnticipo,
						//"txtNumeroDctoPago" => $txtNumeroDctoPago,
						//"txtIdBancoCliente" => $txtIdBancoCliente,
						//"txtCuentaClientePago" => $txtCuentaClientePago,
						//"txtIdBancoCompania" => $txtIdBancoCompania,
						//"txtIdCuentaCompaniaPago" => $txtIdCuentaCompaniaPago,
						//"txtCuentaCompaniaPago" => $txtCuentaCompaniaPago,
						//"txtFechaDeposito" => $txtFechaDeposito,
						//"txtTipoTarjeta" => $txtTipoTarjeta,
						//"hddObjDetalleDeposito" => $hddObjDetalleDeposito,
						//"hddObjDetalleDepositoFormaPago" => $hddObjDetalleDepositoFormaPago,
						//"hddObjDetalleDepositoBanco" => $hddObjDetalleDepositoBanco,
						//"hddObjDetalleDepositoNroCuenta" => $hddObjDetalleDepositoNroCuenta,
						//"hddObjDetalleDepositoNroCheque" => $hddObjDetalleDepositoNroCheque,
						//"hddObjDetalleDepositoMonto" => $hddObjDetalleDepositoMonto,
					"txtMonto" => $montoPagadoAnticipo,
					"cbxCondicionMostrar" => $frmListaDctoPagado['cbxCondicionMostrar'.$valorPago],
					"lstSumarA" => $frmListaDctoPagado['cbxMostrarContado'.$valorPago]
				);
				
				$arrayObjPago[] = $arrayDetallePago;
				
				$objDcto = new Documento;
				$objDcto->idModulo = $idModulo;
				$objDcto->idDocumento = $idFactura;
				$objDcto->idEmpresa = $idEmpresa;
				$objDcto->idCliente = $idCliente;
				$Result1 = $objDcto->guardarReciboPagoCxCFA(
					$idCajaPpal,
					$apertCajaPpal,
					$idApertura,
					$fechaRegistroPago,
					$arrayObjPago);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				$idEncabezadoReciboPago = $Result1['idEncabezadoReciboPago'];
				
				$arrayIdReciboVentana[] = $idEncabezadoReciboPago;
				
			} else if ($tipoDcto == "ND" && !($hddIdPago > 0)) {
				$idNotaCargo = $arrayAnticipo[2];
				
				//consulto anticipo para verificar monto, se usa monto porque el saldo ya esta en cero al cargarse a una fact
				$queryNotaDebito = sprintf("SELECT
					cxc_nd.idDepartamentoOrigenNotaCargo,
					cxc_nd.numeroNotaCargo,
					cxc_nd.saldoNotaCargo AS monto_faltante_pago
				FROM cj_cc_notadecargo cxc_nd
				WHERE cxc_nd.idNotaCargo = %s;",
					valTpDato($idNotaCargo, "int"));
				$rsNotaDebito = mysql_query($queryNotaDebito);
				if (!$rsNotaDebito) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
				$rowNotaDebito = mysql_fetch_assoc($rsNotaDebito);
				
				$idModulo = $rowNotaDebito['idDepartamentoOrigenNotaCargo'];
				$nuevoSaldoNotaDebito = $rowNotaDebito['monto_faltante_pago'] - $montoPagadoNotaDebito;
				
				if ($nuevoSaldoNotaDebito < 0) {
					return $objResponse->alert("El pago de la nota de débito Nro ".$rowNotaDebito['numeroNotaDebito']." no puede quedar en negativo: ".$nuevoSaldoNotaDebito);
				}
				
				$arrayObjPago = array();
				$arrayDetallePago = array(
					"idCajaPpal" => $idCajaPpal,
					"apertCajaPpal" => $apertCajaPpal,
					"idApertura" => $idApertura,
					"fechaRegistroPago" => $fechaRegistroPago,
						//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
						//"idEncabezadoPago" => $idEncabezadoPago,
					"cbxPosicionPago" => $valor,
					"hddIdPago" => $hddIdPago,
					"txtIdFormaPago" => 7, // 7 = Anticipo
					"txtIdNumeroDctoPago" => $idAnticipo,
						//"txtNumeroDctoPago" => $txtNumeroDctoPago,
						//"txtIdBancoCliente" => $txtIdBancoCliente,
						//"txtCuentaClientePago" => $txtCuentaClientePago,
						//"txtIdBancoCompania" => $txtIdBancoCompania,
						//"txtIdCuentaCompaniaPago" => $txtIdCuentaCompaniaPago,
						//"txtCuentaCompaniaPago" => $txtCuentaCompaniaPago,
						//"txtFechaDeposito" => $txtFechaDeposito,
						//"txtTipoTarjeta" => $txtTipoTarjeta,
						//"hddObjDetalleDeposito" => $hddObjDetalleDeposito,
						//"hddObjDetalleDepositoFormaPago" => $hddObjDetalleDepositoFormaPago,
						//"hddObjDetalleDepositoBanco" => $hddObjDetalleDepositoBanco,
						//"hddObjDetalleDepositoNroCuenta" => $hddObjDetalleDepositoNroCuenta,
						//"hddObjDetalleDepositoNroCheque" => $hddObjDetalleDepositoNroCheque,
						//"hddObjDetalleDepositoMonto" => $hddObjDetalleDepositoMonto,
					"txtMonto" => $montoPagadoAnticipo
				);
				
				$arrayObjPago[] = $arrayDetallePago;
				
				$objDcto = new Documento;
				$objDcto->idModulo = $idModulo;
				$objDcto->idDocumento = $idNotaCargo;
				$objDcto->idEmpresa = $idEmpresa;
				$objDcto->idCliente = $idCliente;
				$Result1 = $objDcto->guardarReciboPagoCxCND(
					$idCajaPpal,
					$apertCajaPpal,
					$idApertura,
					$fechaRegistroPago,
					$arrayObjPago);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				$idEncabezadoReciboPago = $Result1['idEncabezadoReciboPago'];
				
				$arrayIdReciboVentana[] = $idEncabezadoReciboPago;
			}
			
			// ACTUALIZA EL CREDITO DISPONIBLE
			$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
															WHERE cxc_ant.idCliente = cliente_emp.id_cliente
																AND cxc_ant.id_empresa = cliente_emp.id_empresa
																AND cxc_ant.estadoAnticipo IN (1,2)
																AND cxc_ant.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
											WHERE cxc_ant.idCliente = cliente_emp.id_cliente
												AND cxc_ant.id_empresa = cliente_emp.id_empresa
												AND cxc_ant.estadoAnticipo IN (1,2)
												AND cxc_ant.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} // FIN foreach de cbxDctoAgregado
	}
	
	$objDcto = new Documento;
	$Result1 = $objDcto->actualizarAnticipo($idAnticipo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	
	if ($existeNuevoPago == true || $existeCambioMonto == true) {
		// BUSCA LOS DATOS DEL ANTICIPO
		$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
		$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
		
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
		if (in_array($rowAnticipo['estadoAnticipo'], array(2,3))) {
			// BUSCO SI EL ANTICIPO ESTABA ASIGNADO COMO PAGO PENDIENTE
			$queryPago = sprintf("SELECT query.* FROM (SELECT 
					cxc_pago.idPago,
					cxc_pago.id_factura,
					cxc_fact.id_empresa,
					cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
					1 AS tipo_documento,
					cxc_fact.saldoFactura,
					cxc_pago.fechaPago,
					cxc_pago.formaPago,
					cxc_pago.numeroDocumento,
					cxc_pago.montoPagado,
					cxc_pago.tomadoEnComprobante,
					cxc_pago.tomadoEnCierre,
					cxc_pago.idCaja,
					cxc_pago.idCierre,
					cxc_pago.estatus
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
				WHERE cxc_pago.formaPago IN (7)
				
				UNION
				
				SELECT 
					cxc_pago.idPago,
					cxc_pago.id_factura,
					cxc_fact.id_empresa,
					cxc_fact.idDepartamentoOrigenFactura,
					1 AS tipo_documento,
					cxc_fact.saldoFactura,
					cxc_pago.fechaPago,
					cxc_pago.formaPago,
					cxc_pago.numeroDocumento,
					cxc_pago.montoPagado,
					cxc_pago.tomadoEnComprobante,
					cxc_pago.tomadoEnCierre,
					cxc_pago.idCaja,
					cxc_pago.idCierre,
					cxc_pago.estatus
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
				WHERE cxc_pago.formaPago IN (7)
				
				UNION
				
				SELECT 
					cxc_pago.id_det_nota_cargo,
					cxc_pago.idNotaCargo,
					cxc_nd.id_empresa,
					cxc_nd.idDepartamentoOrigenNotaCargo,
					2 AS tipo_documento,
					cxc_nd.saldoNotaCargo,
					cxc_pago.fechaPago,
					cxc_pago.idFormaPago,
					cxc_pago.numeroDocumento,
					cxc_pago.monto_pago,
					cxc_pago.tomadoEnComprobante,
					cxc_pago.tomadoEnCierre,
					cxc_pago.idCaja,
					cxc_pago.idCierre,
					cxc_pago.estatus
				FROM cj_cc_notadecargo cxc_nd
					INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
				WHERE cxc_pago.idFormaPago IN (7)) AS query
			WHERE query.numeroDocumento = %s
				AND query.estatus IN (2);",
				valTpDato($idAnticipo, "int"));
			$rsPago = mysql_query($queryPago);
			if (!$rsPago) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsPago = mysql_num_rows($rsPago);
			while ($rowPago = mysql_fetch_assoc($rsPago)) {
				$idCaja = $rowPago['idCaja'];
				$idModulo = $rowPago['id_modulo'];
				
				if ($rowPago['idCierre'] > 0) { // SI EL PAGO ES DE UNA CAJA YA CERRADA
					if ($rowPago['tipo_documento'] == 1) { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
						$idFactura = $rowPago['id_factura'];
						
						if (in_array($idCaja,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
							// ANULA EL PAGO
							$udpateSQL = sprintf("UPDATE an_pagos SET
								estatus = NULL,
								fecha_anulado = %s,
								id_empleado_anulado = %s
							WHERE idPago = %s;",
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($rowPago['idPago'], "int"));
							$Result1 = mysql_query($udpateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
							// BUSCA LOS PAGOS DEL DOCUMENTO
							$queryPagoPendiente = sprintf("SELECT * FROM an_pagos WHERE idPago = %s;",
								valTpDato($rowPago['idPago'], "int"));
							$rsPagoPendiente = mysql_query($queryPagoPendiente);
							if (!$rsPagoPendiente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRowsPagoPendiente = mysql_num_rows($rsPagoPendiente);
							$arrayObjPago = array();
							while ($rowPagoPendiente = mysql_fetch_array($rsPagoPendiente)) {
								if ($rowPagoPendiente['formaPago'] == 2) { // 2 = Cheque
									$txtIdNumeroDctoPago = $rowPagoPendiente['id_cheque'];
								} else if ($rowPagoPendiente['formaPago'] == 4) { // 4 = Transferencia Bancaria
									$txtIdNumeroDctoPago = $rowPagoPendiente['id_transferencia'];
								} else {
									$txtIdNumeroDctoPago = $rowPagoPendiente['numeroDocumento'];
								}
								
								$arrayDetallePago = array(
									"idCajaPpal" => $idCaja,
									"apertCajaPpal" => ((in_array($idCaja,array(1))) ? "an_apertura" : "sa_iv_apertura"),
									"idApertura" => $idApertura,
									"numeroActualFactura" => $rowPagoPendiente['numeroFactura'],
									"fechaRegistroPago" => $fechaRegistroPago,
										//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
										//"idEncabezadoPago" => $idEncabezadoPago,
										//"cbxPosicionPago" => $cbxPosicionPago,
										//"hddIdPago" => $hddIdPago,
									"txtIdFormaPago" => $rowPagoPendiente['formaPago'],
									"txtIdNumeroDctoPago" => $txtIdNumeroDctoPago,
									"txtNumeroDctoPago" => $rowPagoPendiente['numeroDocumento'],
									"txtIdBancoCliente" => $rowPagoPendiente['bancoOrigen'],
									"txtCuentaClientePago" => $rowPagoPendiente['numero_cuenta_cliente'],
									"txtIdBancoCompania" => $rowPagoPendiente['bancoDestino'],
										//"txtIdCuentaCompaniaPago" => $txtIdCuentaCompaniaPago,
									"txtCuentaCompaniaPago" => $rowPagoPendiente['cuentaEmpresa'],
										//"txtFechaDeposito" => $txtFechaDeposito,
										//"txtTipoTarjeta" => $txtTipoTarjeta,
										//"hddObjDetalleDeposito" => $hddObjDetalleDeposito,
										//"hddObjDetalleDepositoFormaPago" => $hddObjDetalleDepositoFormaPago,
										//"hddObjDetalleDepositoBanco" => $hddObjDetalleDepositoBanco,
										//"hddObjDetalleDepositoNroCuenta" => $hddObjDetalleDepositoNroCuenta,
										//"hddObjDetalleDepositoNroCheque" => $hddObjDetalleDepositoNroCheque,
										//"hddObjDetalleDepositoMonto" => $hddObjDetalleDepositoMonto,
									"txtMonto" => $rowPagoPendiente['montoPagado'],
									"cbxCondicionMostrar" => $rowPagoPendiente['id_condicion_mostrar'],
									"lstSumarA" => $rowPagoPendiente['id_mostrar_contado']
								);
								
								$arrayObjPago[] = $arrayDetallePago;
							}
						} else if (in_array($idCaja,array(2))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
							// ANULA EL PAGO
							$udpateSQL = sprintf("UPDATE sa_iv_pagos SET
								estatus = NULL,
								fecha_anulado = %s,
								id_empleado_anulado = %s
							WHERE idPago = %s;",
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($rowPago['idPago'], "int"));
							$Result1 = mysql_query($udpateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							
							// BUSCA LOS PAGOS DEL DOCUMENTO
							$queryPagoPendiente = sprintf("SELECT * FROM sa_iv_pagos WHERE idPago = %s;",
								valTpDato($rowPago['idPago'], "int"));
							$rsPagoPendiente = mysql_query($queryPagoPendiente);
							if (!$rsPagoPendiente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRowsPagoPendiente = mysql_num_rows($rsPagoPendiente);
							$arrayObjPago = array();
							while ($rowPagoPendiente = mysql_fetch_array($rsPagoPendiente)) {
								if ($rowPagoPendiente['formaPago'] == 2) { // 2 = Cheque
									$txtIdNumeroDctoPago = $rowPagoPendiente['id_cheque'];
								} else if ($rowPagoPendiente['formaPago'] == 4) { // 4 = Transferencia Bancaria
									$txtIdNumeroDctoPago = $rowPagoPendiente['id_transferencia'];
								} else {
									$txtIdNumeroDctoPago = $rowPagoPendiente['numeroDocumento'];
								}
								
								$arrayDetallePago = array(
									"idCajaPpal" => $idCaja,
									"apertCajaPpal" => ((in_array($idCaja,array(1))) ? "an_apertura" : "sa_iv_apertura"),
									"idApertura" => $idApertura,
									"numeroActualFactura" => $rowPagoPendiente['numeroFactura'],
									"fechaRegistroPago" => $fechaRegistroPago,
										//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
										//"idEncabezadoPago" => $idEncabezadoPago,
										//"cbxPosicionPago" => $cbxPosicionPago,
										//"hddIdPago" => $hddIdPago,
									"txtIdFormaPago" => $rowPagoPendiente['formaPago'],
									"txtIdNumeroDctoPago" => $txtIdNumeroDctoPago,
									"txtNumeroDctoPago" => $rowPagoPendiente['numeroDocumento'],
									"txtIdBancoCliente" => $rowPagoPendiente['bancoOrigen'],
									"txtCuentaClientePago" => $rowPagoPendiente['numero_cuenta_cliente'],
									"txtIdBancoCompania" => $rowPagoPendiente['bancoDestino'],
										//"txtIdCuentaCompaniaPago" => $txtIdCuentaCompaniaPago,
									"txtCuentaCompaniaPago" => $rowPagoPendiente['cuentaEmpresa'],
										//"txtFechaDeposito" => $txtFechaDeposito,
										//"txtTipoTarjeta" => $txtTipoTarjeta,
										//"hddObjDetalleDeposito" => $hddObjDetalleDeposito,
										//"hddObjDetalleDepositoFormaPago" => $hddObjDetalleDepositoFormaPago,
										//"hddObjDetalleDepositoBanco" => $hddObjDetalleDepositoBanco,
										//"hddObjDetalleDepositoNroCuenta" => $hddObjDetalleDepositoNroCuenta,
										//"hddObjDetalleDepositoNroCheque" => $hddObjDetalleDepositoNroCheque,
										//"hddObjDetalleDepositoMonto" => $hddObjDetalleDepositoMonto,
									"txtMonto" => $rowPagoPendiente['montoPagado'],
									"cbxCondicionMostrar" => $rowPagoPendiente['id_condicion_mostrar'],
									"lstSumarA" => $rowPagoPendiente['id_mostrar_contado']
								);
								
								$arrayObjPago[] = $arrayDetallePago;
							}
						}
						
						$objDcto = new Documento;
						$objDcto->idModulo = $idModulo;
						$objDcto->idDocumento = $idFactura;
						$objDcto->idEmpresa = $idEmpresa;
						$objDcto->idCliente = $idCliente;
						$Result1 = $objDcto->guardarReciboPagoCxCFA(
							$idCaja,
							((in_array($idCaja,array(1))) ? "an_apertura" : "sa_iv_apertura"),
							$idApertura,
							$fechaRegistroPago,
							$arrayObjPago);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						$idEncabezadoReciboPago = $Result1['idEncabezadoReciboPago'];
						
					} else if ($rowPago['tipo_documento'] == 2) { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
						$idNotaCargo = $rowPago['id_factura'];
						
						// ANULA EL PAGO
						$udpateSQL = sprintf("UPDATE cj_det_nota_cargo SET
							estatus = NULL,
							fecha_anulado = %s,
							id_empleado_anulado = %s
						WHERE id_det_nota_cargo = %s;",
							valTpDato("NOW()", "campo"),
							valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
							valTpDato($rowPago['idPago'], "int"));
						$Result1 = mysql_query($udpateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						// BUSCA LOS PAGOS DEL DOCUMENTO
						$queryPagoPendiente = sprintf("SELECT * FROM cj_det_nota_cargo WHERE id_det_nota_cargo = %s;",
							valTpDato($rowPago['idPago'], "int"));
						$rsPagoPendiente = mysql_query($queryPagoPendiente);
						if (!$rsPagoPendiente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsPagoPendiente = mysql_num_rows($rsPagoPendiente);
						$arrayObjPago = array();
						while ($rowPagoPendiente = mysql_fetch_array($rsPagoPendiente)) {
							if ($rowPagoPendiente['idFormaPago'] == 2) { // 2 = Cheque
								$txtIdNumeroDctoPago = $rowPagoPendiente['id_cheque'];
							} else if ($rowPagoPendiente['idFormaPago'] == 4) { // 4 = Transferencia Bancaria
								$txtIdNumeroDctoPago = $rowPagoPendiente['id_transferencia'];
							} else {
								$txtIdNumeroDctoPago = $rowPagoPendiente['numeroDocumento'];
							}
							
							$arrayDetallePago = array(
								"idCajaPpal" => $idCaja,
								"apertCajaPpal" => ((in_array($idCaja,array(1))) ? "an_apertura" : "sa_iv_apertura"),
								"idApertura" => $idApertura,
								"fechaRegistroPago" => $fechaRegistroPago,
									//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
									//"idEncabezadoPago" => $idEncabezadoPago,
									//"cbxPosicionPago" => $cbxPosicionPago,
									//"hddIdPago" => $hddIdPago,
								"txtIdFormaPago" => $rowPagoPendiente['idFormaPago'],
								"txtIdNumeroDctoPago" => $txtIdNumeroDctoPago,
								"txtNumeroDctoPago" => $rowPagoPendiente['numeroDocumento'],
								"txtIdBancoCliente" => $rowPagoPendiente['bancoOrigen'],
								"txtCuentaClientePago" => $rowPagoPendiente['numero_cuenta_cliente'],
								"txtIdBancoCompania" => $rowPagoPendiente['bancoDestino'],
									//"txtIdCuentaCompaniaPago" => $txtIdCuentaCompaniaPago,
								"txtCuentaCompaniaPago" => $rowPagoPendiente['cuentaEmpresa'],
									//"txtFechaDeposito" => $txtFechaDeposito,
									//"txtTipoTarjeta" => $txtTipoTarjeta,
									//"hddObjDetalleDeposito" => $hddObjDetalleDeposito,
									//"hddObjDetalleDepositoFormaPago" => $hddObjDetalleDepositoFormaPago,
									//"hddObjDetalleDepositoBanco" => $hddObjDetalleDepositoBanco,
									//"hddObjDetalleDepositoNroCuenta" => $hddObjDetalleDepositoNroCuenta,
									//"hddObjDetalleDepositoNroCheque" => $hddObjDetalleDepositoNroCheque,
									//"hddObjDetalleDepositoMonto" => $hddObjDetalleDepositoMonto,
								"txtMonto" => $rowPagoPendiente['monto_pago'],
								"cbxCondicionMostrar" => $rowPagoPendiente['id_condicion_mostrar'],
								"lstSumarA" => $rowPagoPendiente['id_mostrar_contado']
							);
							
							$arrayObjPago[] = $arrayDetallePago;
						}
						
						$objDcto = new Documento;
						$objDcto->idModulo = $idModulo;
						$objDcto->idDocumento = $idNotaCargo;
						$objDcto->idEmpresa = $idEmpresa;
						$objDcto->idCliente = $idCliente;
						$Result1 = $objDcto->guardarReciboPagoCxCND(
							$idCaja,
							((in_array($idCaja,array(1))) ? "an_apertura" : "sa_iv_apertura"),
							$idApertura,
							$fechaRegistroPago,
							$arrayObjPago);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						$idEncabezadoReciboPago = $Result1['idEncabezadoReciboPago'];
						
					}
				} else { // SI EL PAGO ES DE UNA CAJA ABIERTA
					if ($rowPago['tipo_documento'] == 1) { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
						$idFactura = $rowPago['id_factura'];
						
						if (in_array($idCaja,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
							// ACTUALIZA EL ESTADO DEL PAGO
							$udpateSQL = sprintf("UPDATE an_pagos SET estatus = 1 WHERE idPago = %s;",
								valTpDato($rowPago['idPago'], "int"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($udpateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
						} else if (in_array($idCaja,array(2))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
							// ACTUALIZA EL ESTADO DEL PAGO
							$udpateSQL = sprintf("UPDATE sa_iv_pagos SET estatus = 1 WHERE idPago = %s;",
								valTpDato($rowPago['idPago'], "int"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($udpateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
						}
						
						$objDcto = new Documento;
						$Result1 = $objDcto->actualizarFactura($idFactura);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						
					} else if ($rowPago['tipo_documento'] == 2) { // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
						$idNotaCargo = $rowPago['id_factura'];
						
						// ACTUALIZA EL ESTADO DEL PAGO
						$udpateSQL = sprintf("UPDATE cj_det_nota_cargo SET estatus = 1 WHERE id_det_nota_cargo = %s",
							valTpDato($rowPago['idPago'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($udpateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						$objDcto = new Documento;
						$Result1 = $objDcto->actualizarNotaDebito($idNotaCargo);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						
					}
					
					$objDcto = new Documento;
					$Result1 = $objDcto->actualizarAnticipo($idAnticipo);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					
				}//fin else cierre == 0
			}//fin while todos los pagos anticipo
		}//fin if estado anticipo = 3
		
		($idEncabezadoReciboPago > 0) ? $arrayIdReciboVentana[] = $idEncabezadoReciboPago : "";
		($idReporteImpresion > 0) ? $arrayIdReciboImpresionVentana[] = $idReporteImpresion : "";
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Anticipo guardado correctamente");
	
	if (in_array($idCajaPpal, array(1))){
		if (count($arrayIdReciboVentana) > 0) {
			$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s',960,550);", implode(",",$arrayIdReciboVentana)));
		}
		if (count($arrayIdReciboImpresionVentana) > 0) {
			$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s',960,550);", implode(",",$arrayIdReciboImpresionVentana)));
		}
	} else if (in_array($idCajaPpal, array(2))){
		if (count($arrayIdReciboVentana) > 0) {
			$objResponse->script(sprintf("verVentana('reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s',960,550);", implode(",",$arrayIdReciboVentana)));
		}
		if (count($arrayIdReciboImpresionVentana) > 0) {
			$objResponse->script(sprintf("verVentana('reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s',960,550);", implode(",",$arrayIdReciboImpresionVentana)));
		}
	}
	
	if ($frmDcto['hddNuevoAnticipo'] == 1) {
		$objResponse->loadCommands(cargarDcto("", $idCliente, $idModulo, "", $frmListaPagoDcto));
	} else {
		if (in_array($idCajaPpal, array(1))){
			$objResponse->script(sprintf("
			cerrarVentana = true;
			window.location.href='cj_anticipo_list.php';"));
		} else if (in_array($idCajaPpal, array(2))){
			$objResponse->script(sprintf("
			cerrarVentana = true;
			window.location.href='cjrs_anticipo_list.php';"));
		}
	}
	
	// MODIFICADO ERNESTO
	if (in_array($idCajaPpal,array(1))) { // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		if (function_exists("generarAnticiposVe")) { generarAnticiposVe($idAnticipo,"",""); } 
	} else {
		if (function_exists("generarAnticiposRe")) { generarAnticiposRe($idAnticipo,"",""); } 
	}
	// MODIFICADO ERNESTO
	
	return $objResponse;
}

function insertarDctoPagado($frmAnticipoNotaCreditoChequeTransferencia, $frmListaDctoPagado, $frmListaAnticipo){
    $objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmListaDctoPagado['cbx4'];
	$contFila = $arrayObj4[count($arrayObj4)-1];
	
    if (str_replace(",", "", $frmListaDctoPagado['txtMontoRestante']) < str_replace(",", "", $frmAnticipoNotaCreditoChequeTransferencia['txtMontoDocumento'])){
        return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo del anticipo");
    }

    foreach ($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
		$arrayAnticipo = explode("|",$valor);
		$hddIdPago = $arrayAnticipo[0];
		$tipoDcto = $arrayAnticipo[1];
		$idAnticipo = $arrayAnticipo[2];
		
        if (!($hddIdPago > 0) && $tipoDcto == $frmAnticipoNotaCreditoChequeTransferencia['hddTipoDocumento'] && $idAnticipo == $frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento']) {
			return $objResponse->alert("El documento seleccionado ya se encuentra agregado");
        }
    }
	
	$hddTipoDocumento = $frmAnticipoNotaCreditoChequeTransferencia['hddTipoDocumento'];
	
	switch ($hddTipoDocumento) {
		case "FA" :
			$queryDocumento = sprintf("SELECT DISTINCT
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_fact.idFactura,
				cxc_fact.fechaRegistroFactura,
				cxc_fact.fechaVencimientoFactura,
				cxc_fact.fecha_pagada,
				cxc_fact.fecha_cierre,
				cxc_fact.numeroFactura,
				cxc_fact.numeroControl,
				cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
				cxc_fact.condicionDePago,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_ped_vent.id_pedido_venta
					WHEN 2 THEN	ped_vent.id_pedido
				END) AS id_pedido,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_ped_vent.id_pedido_venta_propio
					WHEN 2 THEN	ped_vent.numeracion_pedido
				END) AS numeracion_pedido,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_pres_vent.id_presupuesto_venta
					WHEN 2 THEN	pres_vent.id_presupuesto
				END) AS id_presupuesto,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_pres_vent.numeracion_presupuesto
					WHEN 2 THEN	pres_vent.numeracion_presupuesto
				END) AS numeracion_presupuesto,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				uni_fis.placa,
				ped_comp_det.flotilla,
				cxc_fact.estadoFactura,
				(CASE cxc_fact.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS descripcion_estado_factura,
				cxc_fact.aplicaLibros,
				cxc_fact.observacionFactura,
				cxc_fact.anulada,
				cxc_fact.saldoFactura,
				cxc_fact.montoTotalFactura,
				
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
				
				(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				WHERE fact_det_acc2.id_factura = cxc_fact.idFactura) AS cantidad_accesorios,
				cxc_fact.anulada,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_encabezadofactura cxc_fact
				LEFT JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
				LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
				INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
				LEFT JOIN iv_pedido_venta iv_ped_vent ON (cxc_fact.numeroPedido = iv_ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
				LEFT JOIN iv_presupuesto_venta iv_pres_vent ON (iv_ped_vent.id_presupuesto_venta = iv_pres_vent.id_presupuesto_venta)
				LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
				LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
				LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_fact.idFactura = %s;", 
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
		case "ND" : 
			$queryDocumento = sprintf("SELECT
				cxc_nd.idNotaCargo AS idFactura,
				cxc_nd.numeroNotaCargo AS numeroFactura,
				cxc_nd.numeroControlNotaCargo,
				cxc_nd.fechaRegistroNotaCargo AS fechaRegistroFactura,
				cxc_nd.fechaVencimientoNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cxc_nd.estadoNotaCargo AS estadoFactura,
				(CASE cxc_nd.estadoNotaCargo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS descripcion_estado_factura,
				cxc_nd.montoTotalNotaCargo,
				cxc_nd.saldoNotaCargo,
				cxc_nd.observacionNotaCargo AS observacionFactura,
				cxc_nd.aplicaLibros,
				
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
				
				(IFNULL(cxc_nd.subtotalNotaCargo, 0)
					- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
				
				(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
					+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_iva,
				
				(IFNULL(cxc_nd.subtotalNotaCargo, 0)
					- IFNULL(cxc_nd.descuentoNotaCargo, 0)
					+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
						+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0))) AS total,
				
				uni_fis.id_unidad_fisica,
				uni_fis.serial_carroceria,
				uni_fis.serial_motor,
				uni_fis.serial_chasis,
				uni_fis.placa,
				
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_notadecargo cxc_nd
				INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
				LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nd.id_unidad_fisica_bono = uni_fis.id_unidad_fisica)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_nd.idNotaCargo = %s;", 
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
		case "AN" : 
			$queryDocumento = sprintf("SELECT
				cxc_ant.idAnticipo AS idFactura,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cxc_ant.montoNetoAnticipo,
				cxc_ant.totalPagadoAnticipo,
				cxc_ant.saldoAnticipo,
				cxc_ant.fechaAnticipo AS fechaRegistroFactura,
				cxc_ant.numeroAnticipo AS numeroFactura,
				cxc_ant.idDepartamento AS id_modulo,
				cxc_ant.estadoAnticipo AS estadoFactura,
				(CASE cxc_ant.estadoAnticipo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END) AS descripcion_estado_factura,
				cxc_ant.observacionesAnticipo AS observacionFactura,
				
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
					AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
				
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
				cxc_ant.estatus
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_ant.idAnticipo = %s;", 
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
	}
    $rsDocumento = mysql_query($queryDocumento);
    if (!$rsDocumento) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
    $rowDocumento = mysql_fetch_array($rsDocumento);
	
    $idDocumento = $frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'];
    $txtMontoPagado = str_replace(",", "", $frmAnticipoNotaCreditoChequeTransferencia['txtMontoDocumento']);
	
	$Result1 = insertarItemDctoPagado($contFila, "", "", $hddTipoDocumento, $idDocumento, $rowDocumento['id_modulo'], date("Y-m-d"), date(), $rowDocumento['nombre_empresa'], $rowDocumento['fechaRegistroFactura'], $rowDocumento['numeroFactura'], $rowDocumento['id_cliente'], $rowDocumento['nombre_cliente'], $rowDocumento['estadoFactura'], $rowDocumento['descripcion_concepto_forma_pago'], $rowDocumento['observacionFactura'], $rowDocumento['descripcion_estado_factura'], $txtMontoPagado);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj4[] = $contFila;
	}
	
    $objResponse->script("calcularPagos();");
	
	$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	
	switch ($hddTipoDocumento) {
		case "FA" :
			$objResponse->loadCommands(listaFactura(
				$frmListaAnticipo['pageNum'],
				$frmListaAnticipo['campOrd'],
				$frmListaAnticipo['tpOrd'],
				$frmListaAnticipo['valBusq']));
			break;
		case "ND" :
			$objResponse->loadCommands(listaNotaDebito(
				$frmListaAnticipo['pageNum'],
				$frmListaAnticipo['campOrd'],
				$frmListaAnticipo['tpOrd'],
				$frmListaAnticipo['valBusq']));
			break;
		case "AN" :
			$objResponse->loadCommands(listaAnticipo(
				$frmListaAnticipo['pageNum'],
				$frmListaAnticipo['campOrd'],
				$frmListaAnticipo['tpOrd'],
				$frmListaAnticipo['valBusq']));
			break;
	}

    return $objResponse;
}

function insertarPago($frmListaPagoDcto, $frmDetallePago, $frmDeposito, $frmLista, $frmDcto){
	$objResponse = new xajaxResponse();
	
	if (str_replace(",", "", $frmListaPagoDcto['txtMontoPorPagar']) < str_replace(",", "", $frmDetallePago['txtMontoPago'])) {
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo del Anticipo");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPiePago = $frmListaPagoDcto['cbxPiePago'];
	$contFila = $arrayObjPiePago[count($arrayObjPiePago)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	
	$idFormaPago = $frmDetallePago['selTipoPago'];
	$txtIdNumeroDctoPago = $frmDetallePago['hddIdAnticipoNotaCreditoChequeTransferencia'];
	$txtNumeroDctoPago = $frmDetallePago['txtNumeroDctoPago'];
	$txtIdBancoCliente = $frmDetallePago['selBancoCliente'];
	$txtCuentaClientePago = $frmDetallePago['txtNumeroCuenta'];
	$txtIdBancoCompania = $frmDetallePago['selBancoCompania'];
	$txtIdCuentaCompaniaPago = $frmDetallePago['selNumeroCuenta'];
	$txtFechaDeposito = $frmDetallePago['txtFechaDeposito'];
	$lstTipoTarjeta = $frmDetallePago['tarjeta'];
	$porcRetencion = $frmDetallePago['porcentajeRetencion'];
	$montoRetencion = $frmDetallePago['montoTotalRetencion'];
	$porcComision = $frmDetallePago['porcentajeComision'];
	$montoComision = $frmDetallePago['montoTotalComision'];
	$txtIdConceptoPago = $frmDetallePago['selConceptoPago'];
	$txtMontoPago = str_replace(",", "", $frmDetallePago['txtMontoPago']);
	
	// 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
	$hddEstatusPago = (in_array($txtIdConceptoPago, array(7,8,9))) ? 2 : 1;
	
	// VERIFICA QUE NO EXISTA MAS DE UN PAGO OTRO CON ESTATUS PENDIENTE
	foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
		if (($txtIdConceptoPago > 0 && $frmListaPagoDcto['txtIdConceptoPago'.$valorPiePago] > 0)
		&& (in_array($txtIdConceptoPago, array(7,8,9)) || in_array($frmListaPagoDcto['txtIdConceptoPago'.$valorPiePago], array(7,8,9)))) {
			return $objResponse->alert("No puede agregar más de una forma de pago \"Otro\" pendiente de pago");
		} else if (($txtIdConceptoPago > 0 && $frmListaPagoDcto['txtIdConceptoPago'.$valorPiePago] > 0)
		&& (in_array($txtIdConceptoPago, array(1,6)) || in_array($frmListaPagoDcto['txtIdConceptoPago'.$valorPiePago], array(1,6)))) {
			return $objResponse->alert("No puede agregar más de una forma de pago \"Otro\"");
		}
	}
	
	$Result1 = insertarItemMetodoPago($contFila, "", $idFormaPago, $txtIdNumeroDctoPago, $txtNumeroDctoPago, $txtIdBancoCliente, $txtCuentaClientePago, $txtIdBancoCompania, $txtIdCuentaCompaniaPago, $txtFechaDeposito, $lstTipoTarjeta, $txtIdConceptoPago, $txtMontoPago, $hddEstatusPago);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObjPiePago[] = $contFila;
	}
	
	if ($idFormaPago == 3) {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = explode("|", $frmDeposito['hddObjDetallePagoDeposito']);
		
		$cadenaFormaPagoDeposito = '';
		$cadenaNroDocumentoDeposito = '';
		$cadenaBancoClienteDeposito = '';
		$cadenaNroCuentaDeposito = '';
		$cadenaMontoDeposito = '';
		foreach($arrayObj as $indice => $valor) {
			if (isset($frmDeposito['txtIdFormaPagoDetalleDeposito'.$valor])) {
				$cadenaPosicionDeposito .= $contFila."|";
				$cadenaFormaPagoDeposito .= $frmDeposito['txtIdFormaPagoDetalleDeposito'.$valor]."|";		
				$cadenaNroDocumentoDeposito .= $frmDeposito['txtNumeroDocumentoDetalleDeposito'.$valor]."|";
				$cadenaBancoClienteDeposito .= $frmDeposito['txtIdBancoClienteDetalleDeposito'.$valor]."|";
				$cadenaNroCuentaDeposito .= $frmDeposito['txtNumeroCuentaDetalleDeposito'.$valor]."|";
				$cadenaMontoDeposito .= $frmDeposito['txtMontoDetalleDeposito'.$valor]."|";
			}
		}
		$cadenaPosicionDeposito = $frmDetallePago['hddObjDetalleDeposito'].$cadenaPosicionDeposito;
		$cadenaFormaPagoDeposito = $frmDetallePago['hddObjDetalleDepositoFormaPago'].$cadenaFormaPagoDeposito;
		$cadenaBancoClienteDeposito = $frmDetallePago['hddObjDetalleDepositoBanco'].$cadenaBancoClienteDeposito;
		$cadenaNroCuentaDeposito = $frmDetallePago['hddObjDetalleDepositoNroCuenta'].$cadenaNroCuentaDeposito;
		$cadenaNroDocumentoDeposito = $frmDetallePago['hddObjDetalleDepositoNroCheque'].$cadenaNroDocumentoDeposito;
		$cadenaMontoDeposito = $frmDetallePago['hddObjDetalleDepositoMonto'].$cadenaMontoDeposito;
		
		$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosicionDeposito);
		$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPagoDeposito);
		$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBancoClienteDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuentaDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroDocumentoDeposito);
		$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMontoDeposito);
	}
	
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObjPiePago) > 0) ? implode("|",$arrayObjPiePago) : ""));
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'))");
	
	switch ($idFormaPago) {
		case 2 : // 2 = CHEQUE
			if ($txtIdNumeroDctoPago > 0) {
				$objResponse->loadCommands(cargaLstTipoPago("","2"));
				$objResponse->call(asignarTipoPago,"2");
				$objResponse->script("
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
				byId('imgCerrarDivFlotante2').click();");
			} else {
				$objResponse->loadCommands(cargaLstTipoPago("","1"));
				$objResponse->call(asignarTipoPago,"1");
			}
			break;
		case 3 : // 3 = DEPOSITO
			$objResponse->loadCommands(cargaLstTipoPago("","1"));
			$objResponse->call(asignarTipoPago,"1");
			$objResponse->script("
			byId('imgCerrarDivFlotante1').click();"); break;
		case 4 : // 4 = TRANSFERENCIA
			if ($txtIdNumeroDctoPago > 0) {
				$objResponse->loadCommands(cargaLstTipoPago("","4"));
				$objResponse->call(asignarTipoPago,"4");
				$objResponse->script("
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
				byId('imgCerrarDivFlotante2').click();");
			} else {
				$objResponse->loadCommands(cargaLstTipoPago("","1"));
				$objResponse->call(asignarTipoPago,"1");
			}
			break;
		case 7 : // 7 = ANTICIPO
			$objResponse->loadCommands(cargaLstTipoPago("","7"));
			$objResponse->call(asignarTipoPago,"7");
			/*$objResponse->loadCommands(listaAnticipoNotaCreditoChequeTransferencia(
				$frmLista['pageNum'],
				$frmLista['campOrd'],
				$frmLista['tpOrd'],
				$frmLista['valBusq']));*/
			$objResponse->script("
			byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
			byId('imgCerrarDivFlotante2').click();"); break;
		case 8 : // 8 = NOTA CREDITO
			$objResponse->loadCommands(cargaLstTipoPago("","8"));
			$objResponse->call(asignarTipoPago,"8");
			$objResponse->script("
			byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
			byId('imgCerrarDivFlotante2').click();"); break;
		default:
			$objResponse->loadCommands(cargaLstTipoPago("","1"));
			$objResponse->call(asignarTipoPago,"1");
	}
	
	return $objResponse;
}

function insertarPagoDeposito($frmDeposito) {
	$objResponse = new xajaxResponse();
		
	if (str_replace(",", "", $frmDeposito['txtMontoDeposito']) > str_replace(",", "", $frmDeposito['txtSaldoDepositoBancario'])) {
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo del Deposito.");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmDeposito['cbx3'];
	$contFila = $arrayObj3[count($arrayObj3)-1] + 1;
	
	if ($frmDeposito['lstTipoPago'] == 1) {
		$tipoPago = "Efectivo";
		$bancoCliente = "-";
		$numeroCuenta = "-";
		$numeroControl = "-";
		$montoPagado = str_replace(",", "", $frmDeposito['txtMontoDeposito']);
		$bancoClienteOculto = "-";
	} else if ($frmDeposito['lstTipoPago'] == 2) {
		$tipoPago = "Cheque";
		$bancoCliente = asignarBanco($frmDeposito['lstBancoDeposito']);
		$numeroCuenta = $frmDeposito['txtNroCuentaDeposito'];
		$numeroControl = $frmDeposito['txtNroChequeDeposito'];
		$montoPagado = str_replace(",", "", $frmDeposito['txtMontoDeposito']);
		$bancoClienteOculto = $frmDeposito['lstBancoDeposito'];
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$objResponse->script(sprintf("$('#trItmPieDeposito').before('".
		"<tr align=\"left\" id=\"trItmDetalle:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmDetalle:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx3\" name=\"cbx3[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMontoDetalleDeposito%s\" name=\"txtMontoDetalleDeposito%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><button type=\"button\" onclick=\"confirmarEliminarPagoDetalleDeposito(%s);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"/></button>".
				"<input type=\"hidden\" id=\"txtIdFormaPagoDetalleDeposito%s\" name=\"txtIdFormaPagoDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtNumeroDocumentoDetalleDeposito%s\" name=\"txtNumeroDocumentoDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoClienteDetalleDeposito%s\" name=\"txtIdBancoClienteDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtNumeroCuentaDetalleDeposito%s\" name=\"txtNumeroCuentaDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$tipoPago,
			$bancoCliente,
			$numeroCuenta,
			$numeroControl,
			$contFila, $contFila, number_format($montoPagado, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $frmDeposito['lstTipoPago'],
				$contFila, $contFila, $numeroControl,
				$contFila, $contFila, $bancoClienteOculto,
				$contFila, $contFila, $numeroCuenta,
				$contFila, $contFila, $montoPagado));
	
	$objResponse->script("
	xajax_cargaLstTipoPagoDetalleDeposito('1');
	asignarTipoPagoDetalleDeposito('1');");
	
	$objResponse->script("xajax_calcularPagosDeposito(xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDetallePago'))");
	
	return $objResponse;
}

function listaAnticipoNoCancelado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $idModuloPpal;
    global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
		valTpDato($idModuloPpal, "campo"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_ant.estadoAnticipo IN (0,4)
	AND cxc_ant.estatus = 1)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_ant.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_ant.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
		valTpDato($valCadBusq[1], "int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_ant.idAnticipo <> %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	$query = sprintf("SELECT
		cxc_ant.idAnticipo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_ant.montoNetoAnticipo,
		cxc_ant.totalPagadoAnticipo,
		IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, 0) AS saldoAnticipo,
		(cxc_ant.montoNetoAnticipo - cxc_ant.totalPagadoAnticipo) AS saldoPorCobrarAnticipo,
		cxc_ant.fechaAnticipo,
		cxc_ant.numeroAnticipo,
		cxc_ant.idDepartamento,
		IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, NULL) AS estadoAnticipo,
		(CASE cxc_ant.estatus
			WHEN 1 THEN
				(CASE cxc_ant.estadoAnticipo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END)
			ELSE
				'Anulado'
		END) AS descripcion_estado_anticipo,
		cxc_ant.observacionesAnticipo,
		
		cxc_ant.id_empleado_creador,
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		cxc_ant.fecha_anulado,
		cxc_ant.id_empleado_anulado,
		vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
		cxc_ant.motivo_anulacion,
		
		(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
			AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		cxc_ant.estatus
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_ant.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_ant.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNoCancelado", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNoCancelado", "6%", $pageNum, "fechaAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNoCancelado", "8%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNoCancelado", "38%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNoCancelado", "10%", $pageNum, "estadoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNoCancelado", "10%", $pageNum, "saldoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNoCancelado", "10%", $pageNum, "montoNetoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Anticipo");
		$htmlTh .= "<td colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['idDepartamento']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['idDepartamento'];
		}
		
		switch($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anticipo Anulado\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Anticipo Activo\"/>"; break;
			default : $imgEstatus = $row['estatus'];
		}
		
		switch($row['estadoAnticipo']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<div ".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Nro. Anticipo: ".$row['numeroAnticipo'].". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fechaAnticipo']))."</div>";
				$htmlTb .= (strlen($row['fecha_anulado']) > 0) ? "<div class=\"textoNegritaCursiva_9px textoRojoNegrita\" ".((strlen($row['nombre_empleado_anulado']) > 0) ? "title=\"Nro. Anticipo: ".$row['numeroAnticipo'].". Anulado por: ".utf8_encode($row['nombre_empleado_anulado'])."\"" : "").">".date(spanDateFormat, strtotime($row['fecha_anulado']))."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("AN",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "AN";
				$objDcto->tipoDocumentoMovimiento = (in_array("AN",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['idDepartamento'];
				$objDcto->idDocumento = $row['idAnticipo'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td nowrap=\"nowrap\">".$imgDctoModulo."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroAnticipo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</div>" : "";
				$htmlTb .= ((strlen($row['observacionesAnticipo']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionesAnticipo'])."</div>" : "");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($row['descripcion_estado_anticipo'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoAnticipo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td colspan=\"2\">".number_format($row['montoNetoAnticipo'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				if ($row['totalPagadoAnticipo'] != $row['montoNetoAnticipo'] && $row['totalPagadoAnticipo'] > 0) {
					$htmlTb .= "<tr align=\"right\" class=\"textoNegrita_9px\">";
						$htmlTb .= "<td>Pagado:</td>";
						$htmlTb .= "<td width=\"100%\">".number_format($row['totalPagadoAnticipo'], 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
				}
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (in_array($row['estatus'], array(1)) && in_array($row['estadoAnticipo'], array(0,4))) {
				if (in_array($row['idDepartamento'], array(2,4,5))){ // 2 = Vehiculos, 4 = Alquiler, 5 = Financiamiento
					$htmlTb .= sprintf("<a href=\"cj_anticipo_form.php?id=%s\" target=\"_self\"><img src=\"../img/iconos/money_add.png\" title=\"Pagar Anticipo\"/></a>",
						$row['idAnticipo']);
				} else if (in_array($row['idDepartamento'], array(0,1,3))){ // 0 = Repuestos, 1 = Servicios, 3 = Administración
					$htmlTb .= sprintf("<a href=\"cjrs_anticipo_form.php?id=%s\" target=\"_self\"><img src=\"../img/iconos/money_add.png\" title=\"Pagar Anticipo\"/></a>",
						$row['idAnticipo']);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['saldoAnticipo'] > 0 && $row['estatus'] == 1) {
				$htmlTb .= sprintf("<a href=\"cj_anticipo_form.php?id=%s&vw=a\" target=\"_self\"><img src=\"../img/iconos/application_view_columns_add.png\" title=\"Pagar Dctos.\"/></a>",
					$row['idAnticipo']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal['saldoAnticipo'] += $row['saldoAnticipo'];
		$arrayTotal['saldoPorCobrarAnticipo'] += $row['saldoPorCobrarAnticipo'];
		$arrayTotal['montoNetoAnticipo'] += $row['montoNetoAnticipo'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['saldoAnticipo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['montoNetoAnticipo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"5\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal['saldoAnticipo'] += $row['saldoAnticipo'];
				$arrayTotalFinal['saldoPorCobrarAnticipo'] += $row['saldoPorCobrarAnticipo'];
				$arrayTotalFinal['montoNetoAnticipo'] += $row['montoNetoAnticipo'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['saldoAnticipo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['montoNetoAnticipo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"5\"></td>";
			$htmlTb .= "</tr>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNoCancelado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNoCancelado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipoNoCancelado(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNoCancelado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNoCancelado(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->script("byId('trListaAnticipoNoCancelado').style.display = 'none';");
	if ($totalRows > 0 && !($valCadBusq[2] > 0)) {
		$htmlTblIni = 
		"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo\" width=\"100%\">".
		"<tr>".
			"<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\"/></td>".
			"<td align=\"center\">"."El cliente tiene anticipo(s) pendiente(s) por cancelar"."</td>".
		"</tr>".
		"</table>".$htmlTblIni;
		
		$objResponse->script("byId('trListaAnticipoNoCancelado').style.display = '';");
		$objResponse->assign("divListaAnticipoNoCancelado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	}
	
	return $objResponse;
}

function listaAnticipoNotaCreditoChequeTransferencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $idModuloPpal;
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($valCadBusq[2] == 2) { // CHEQUES
		$campoIdCliente = "id_cliente";
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$campoIdCliente = "id_cliente";
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$campoIdCliente = "idCliente";
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$campoIdCliente = "idCliente";
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(dcto.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = dcto.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = dcto.id_empresa)
	OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = dcto.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[2] == 2) { // CHEQUES
		// 1 = Normal, 2 = Bono Suplidor, 3 = PND
		$sqlBusq .= $cond.sprintf("(id_departamento IN (%s)
		AND ((%s = %s AND dcto.tipo_cheque = 1) OR dcto.tipo_cheque IN (2,3)))",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		// 1 = Normal, 2 = Bono Suplidor, 3 = PND
		$sqlBusq .= $cond.sprintf("(id_departamento IN (%s)
		AND ((%s = %s AND dcto.tipo_transferencia = 1) OR dcto.tipo_transferencia IN (2,3)))",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$sqlBusq .= $cond.sprintf("(idDepartamento IN (%s)
		AND %s = %s)",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$sqlBusq .= $cond.sprintf("(idDepartamentoNotaCredito IN (%s)
		AND %s = %s)",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[2] == 2) { // CHEQUES
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado
		$sqlBusq .= $cond.sprintf("estatus IN (1,2) AND saldo_cheque > 0 AND estatus = 1"); // 1 = tipo cliente
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$sqlBusq .= $cond.sprintf("estatus IN (1,2) AND saldo_transferencia > 0 AND estatus = 1");//1 = tipo cliente
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
		$sqlBusq .= $cond.sprintf("estadoAnticipo IN (0,1,2) AND estatus = 1");
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado
		$sqlBusq .= $cond.sprintf("estadoNotaCredito IN (1,2)");
	}
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 2) { // CHEQUES
			$sqlBusq .= $cond.sprintf("(dcto.numero_cheque LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
			$sqlBusq .= $cond.sprintf("(dcto.numero_transferencia LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 7) { // ANTICIPOS
			$sqlBusq .= $cond.sprintf("(numeroAnticipo LIKE %s
			OR cxc_ant.observacionesAnticipo LIKE %s)",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		}
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 2) { // CHEQUES
			$sqlBusq .= $cond.sprintf("dcto.id_cheque NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 4) { // TRANSFERENCIA
			$sqlBusq .= $cond.sprintf("dcto.id_transferencia NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 7) { // ANTICIPOS
			$sqlBusq .= $cond.sprintf("idAnticipo NOT IN (%s)",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf("idNotaCredito NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		}
	}
	
	if ($valCadBusq[2] == 2) { // CHEQUES
		$query = sprintf("SELECT
			cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
			cxc_ec.tipoDocumentoN,
			cxc_ec.tipoDocumento,
			dcto.id_cheque AS idDocumento,
			dcto.fecha_cheque AS fechaDocumento,
			dcto.numero_cheque AS numeroDocumento,
			dcto.id_departamento AS id_modulo,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.saldo_cheque AS saldoDocumento,
			dcto.observacion_cheque AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_cheque dcto 
			INNER JOIN cj_cc_cliente cliente ON (dcto.id_cliente = cliente.id)
			LEFT JOIN cj_cc_estadocuenta cxc_ec ON (dcto.id_cheque = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'CH')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$query = sprintf("SELECT
			cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
			cxc_ec.tipoDocumentoN,
			cxc_ec.tipoDocumento,
			dcto.id_transferencia AS idDocumento,
			dcto.fecha_transferencia AS fechaDocumento,
			dcto.numero_transferencia AS numeroDocumento,
			dcto.id_departamento AS id_modulo,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.saldo_transferencia AS saldoDocumento,
			dcto.observacion_transferencia AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_transferencia dcto 
			INNER JOIN cj_cc_cliente cliente ON (dcto.id_cliente = cliente.id)
			LEFT JOIN cj_cc_estadocuenta cxc_ec ON (dcto.id_transferencia = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'TB')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$query = sprintf("SELECT
			cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
			cxc_ec.tipoDocumentoN,
			cxc_ec.tipoDocumento,
			dcto.idAnticipo AS idDocumento,
			dcto.fechaAnticipo AS fechaDocumento,
			dcto.numeroAnticipo AS numeroDocumento,
			dcto.idDepartamento AS id_modulo,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.saldoAnticipo AS saldoDocumento,
			dcto.observacionesAnticipo AS observacionDocumento,
		
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = dcto.idAnticipo
				AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_anticipo dcto
			INNER JOIN cj_cc_cliente cliente ON (dcto.idCliente = cliente.id)
			LEFT JOIN cj_cc_estadocuenta cxc_ec ON (dcto.idAnticipo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'AN')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$query = sprintf("SELECT
			cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
			cxc_ec.tipoDocumentoN,
			cxc_ec.tipoDocumento,
			dcto.idNotaCredito AS idDocumento,
			dcto.fechaNotaCredito AS fechaDocumento,
			dcto.numeracion_nota_credito AS numeroDocumento,
			dcto.idDepartamentoNotaCredito AS id_modulo,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.saldoNotaCredito AS saldoDocumento,
			dcto.observacionesNotaCredito AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notacredito dcto
			INNER JOIN cj_cc_cliente cliente ON (dcto.idCliente = cliente.id)
			LEFT JOIN cj_cc_estadocuenta cxc_ec ON (dcto.idNotaCredito = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'NC')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	}
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "10%", $pageNum, "fechaDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "14%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Documento"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "42%", $pageNum, "observacionDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "20%", $pageNum, "saldoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"".utf8_encode("Financiamiento")."\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$onClick = sprintf("abrirDivFlotante2(this, 'tblAnticipoNotaCreditoChequeTransferencia', '%s', '%s');",
			$valCadBusq[2],
			$row['idDocumento']);
		
		if ($valCadBusq[2] == 7) { // 7 = Anticipo
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT *
			FROM cj_cc_detalleanticipo cxc_pago
				RIGHT JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.idAnticipo = cxc_ant.idAnticipo)
				LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				LEFT JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND (cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT'
					OR cxc_ant.estadoAnticipo IN (0));",
				valTpDato($row['idDocumento'], "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while ($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
				if ((in_array($rowAnticipo['id_concepto'],array(2))
					&& in_array(idArrayPais,array(3))
					&& ($rowAnticipo['saldoAnticipo'] > 0 || ($rowAnticipo['saldoAnticipo'] == 0 && $rowAnticipo['estadoAnticipo'] == 1)))
				|| ((in_array($rowAnticipo['id_concepto'],array(1,6,7,8,9)) || in_array($rowAnticipo['estadoAnticipo'],array(0))) && $rowAnticipo['saldoAnticipo'] > 0)) {
					$onClick = sprintf("
					byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = '%s';
					byId('txtNumeroDctoPago').value = '%s';
					byId('txtMontoPago').value = '%s';
					
					xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));",
						$rowAnticipo['idAnticipo'],
						$rowAnticipo['numeroAnticipo'],
						$rowAnticipo['saldoAnticipo']);
				}
			}
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDcto%s\" rel=\"#divFlotante2\" onclick=\"%s\"><button type=\"button\" title=\"Seleccionar\"><img class=\"puntero\" src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$onClick);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaDocumento']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = $row['tipoDocumento'];
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['idDocumento'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$imgDctoModulo."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroDocumento'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</div>" : "";
				$htmlTb .= (strlen($row['observacionDocumento']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionDocumento'])."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoDocumento'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CREDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_Ws(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cliente.telf LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente_emp.id_empresa,
		cliente.id,
		cliente.tipo,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.nit AS nit_cliente,
		cliente.licencia AS licencia_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		cliente.bloquea_venta,
		cliente.paga_impuesto,
		perfil_prospecto.compania,
		(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				1
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0, 2, NULL)
		END) AS tipo_cuenta_cliente,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				'Prospecto'
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0,
					'Prospecto Aprobado (Cliente Venta)',
					'Sin Prospectación (Cliente Post-Venta)')
		END) AS descripcion_tipo_cuenta_cliente
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"".utf8_encode("Prospecto")."\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"".utf8_encode("Prospecto Aprobado (Cliente Venta)")."\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"".utf8_encode("Sin Prospectación (Cliente Post-Venta)")."\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaFactura($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $idModuloPpal;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)
	AND cxc_fact.saldoFactura > 0
	AND cxc_fact.estadoFactura NOT IN (1)",
		valTpDato($idModuloPpal, "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[2] == 1 && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
    if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idFactura NOT IN (%s) ",
			valTpDato($valCadBusq[4], "campo"));
    }
	
	if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR an_ped_vent.id_pedido LIKE %s
		OR pres_vent.id_presupuesto LIKE %s
		OR pres_vent.numeracion_presupuesto LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s
		OR (CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) LIKE %s
		OR cxc_fact.observacionFactura LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
		cxc_ec.tipoDocumentoN,
		cxc_ec.tipoDocumento,
		cxc_fact.idFactura,
		cxc_fact.id_empresa,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.fechaVencimientoFactura,
		cxc_fact.fecha_pagada,
		cxc_fact.fecha_cierre,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
		vw_pg_empleado_vendedor.id_empleado AS id_empleado_vendedor,
		vw_pg_empleado_vendedor.nombre_empleado AS nombre_empleado_vendedor,
		cxc_fact.condicionDePago,
		cxc_fact.numeroPedido,
		
		(SELECT an_ped_vent2.id_pedido FROM an_pedido an_ped_vent2
		WHERE an_ped_vent2.id_factura_cxc = cxc_fact.idFactura
			AND an_ped_vent2.estado_pedido IN (0,1,2,3,4)) AS id_pedido_reemplazo,
		
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		cond_unidad.descripcion AS condicion_unidad,
		ped_comp_det.flotilla,
		cxc_fact.estadoFactura,
		(CASE cxc_fact.estadoFactura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		cxc_fact.aplicaLibros,
		cxc_fact.anulada,
		cxc_fact.estatus_factura,
		cxc_fact.subtotalFactura,
		cxc_fact.descuentoFactura,
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
		IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
				WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_impuestos,
		cxc_fact.montoTotalFactura,
		cxc_fact.saldoFactura,
		cxc_fact.observacionFactura,
		
		vw_pg_empleado_creador.id_empleado AS id_empleado_creador,
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.fecha
			WHEN 1 THEN		orden.tiempo_orden
			WHEN 2 THEN		an_ped_vent.fecha
			ELSE			NULL
		END) AS fecha_pedido,
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		NULL
			WHEN 1 THEN		NULL
			WHEN 2 THEN		an_ped_vent.fecha_reserva_venta
			ELSE			NULL
		END) AS fecha_reserva_venta,
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		NULL
			WHEN 1 THEN		orden.tiempo_entrega
			WHEN 2 THEN		an_ped_vent.fecha_entrega
			ELSE			NULL
		END) AS fecha_entrega,
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) AS numero_pedido,
		ped_vent.estatus_pedido_venta,
		tipo_orden.nombre_tipo_orden,
		banco.nombreBanco,
		
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN
				IFNULL((SELECT COUNT(cxc_fact_det.id_factura) FROM cj_cc_factura_detalle cxc_fact_det
						WHERE cxc_fact_det.id_factura = cxc_fact.idFactura), 0)
			WHEN 1 THEN
				(IFNULL((SELECT COUNT(sa_fact_det_art.idFactura) FROM sa_det_fact_articulo sa_fact_det_art
						WHERE sa_fact_det_art.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_nota.idFactura) FROM sa_det_fact_notas sa_fact_det_nota
							WHERE sa_fact_det_nota.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_temp.idFactura) FROM sa_det_fact_tempario sa_fact_det_temp
							WHERE sa_fact_det_temp.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_tot.idFactura) FROM sa_det_fact_tot sa_fact_det_tot
							WHERE sa_fact_det_tot.idFactura = cxc_fact.idFactura), 0))
			WHEN 2 THEN
				(IFNULL((SELECT COUNT(cxc_fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
						WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(cxc_fact_det_vehic.id_factura) FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
							WHERE cxc_fact_det_vehic.id_factura = cxc_fact.idFactura), 0))
			WHEN 3 THEN
				IFNULL((SELECT COUNT(cxc_fact_det_adm.id_factura) FROM cj_cc_factura_detalle_adm cxc_fact_det_adm
					WHERE cxc_fact_det_adm.id_factura = cxc_fact.idFactura), 0)
		END) AS cant_items,
		
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 2 THEN
				IFNULL((SELECT COUNT(cxc_fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
						WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura), 0)
		END) AS cant_accesorios
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
		LEFT JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
		LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
			LEFT JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
		LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica AND cxc_fact.idDepartamentoOrigenFactura = 2)
				LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
				LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_vendedor ON (cxc_fact.idVendedor = vw_pg_empleado_vendedor.id_empleado)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_fact.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFactura", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "fecha_pagada", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Pagada");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "fecha_cierre", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Cierre");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "LPAD(CONVERT(numeroFactura, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "LPAD(CONVERT(numeroControl, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "LPAD(CONVERT(numero_pedido, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido / Orden");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "10%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "condicionDePago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "descripcion_estado_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
		
		$imgEstatusPedido = "";
		if (in_array($row['id_modulo'],array(0))) {
			switch($row['estatus_pedido_venta']) {
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Aprobado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Factura\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
				default : $imgEstatusPedido = "";
			}
		} else {
			$imgEstatusPedido = ($row['anulada'] == "SI") ? "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>" : "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
		}
		
		$imgEstatusUnidadAsignacion = "";
		if (in_array($row['id_modulo'],array(2))) {
			switch ($row['flotilla']) {
				case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
				case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
				default : $imgEstatusUnidadAsignacion = "";
			}
		}
		
		switch($row['estadoFactura']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
			
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAgregar%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAnticipoNotaCreditoChequeTransferencia', 'FA', '%s');\"><button type=\"button\" title=\"%s\"><img src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$row['idFactura'],
					utf8_encode("Seleccionar"));
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Factura Nro: ".utf8_encode($row['numeroFactura']).". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fechaVencimientoFactura']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($row['fecha_pagada'] != "") ? date(spanDateFormat, strtotime($row['fecha_pagada'])) : "")."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($row['fecha_cierre'] != "") ? date(spanDateFormat, strtotime($row['fecha_cierre'])) : "")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = $row['tipoDocumento'];
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['idFactura'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroFactura'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "PD";
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['numeroPedido'];
				$objDcto->mostrarDocumento = "verPDF";
				$aVerDcto = $objDcto->verPedido();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numero_pedido'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['serial_carroceria'])."</div>";
				$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode($row['condicion_unidad'])."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicionDePago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\" ".$rowspan.">";
				$htmlTb .= ($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\" ".$class." ".$rowspan.">";
				$htmlTb .= "<div style=\"padding:5px\">".$row['descripcion_estado_factura']."</div>";
			if (in_array(idArrayPais,array(3)) && $row['estatus_factura'] == 2) {
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo6\" width=\"100%\">";
				$htmlTb .= "<tr align=\"center\">";
					$htmlTb .= "<td height=\"25\" width=\"25\"><img src=\"../img/iconos/lock.png\"/></td>";
					$htmlTb .= "<td>Venta Cerrada</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['montoTotalFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($row['saldoFactura'] < $row['montoTotalFactura']) {
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "FA";
				$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['idFactura'];
				$objDcto->mostrarDocumento = "verReciboPDF";
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= $aVerDcto;
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal['cant_items'] += $row['cant_items'];
		$arrayTotal['saldoFactura'] += $row['saldoFactura'];
		$arrayTotal['montoTotalFactura'] += $row['montoTotalFactura'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"19\">".("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['montoTotalFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalFinal['cant_items'] += $arrayTotal['cant_items'];
		$arrayTotalFinal['saldoFactura'] += $arrayTotal['saldoFactura'];
		$arrayTotalFinal['montoTotalFactura'] += $arrayTotal['montoTotalFactura'];
		
		if ($pageNum == $totalPages) {
			if ($totalPages > 0) {
				$rs = mysql_query($query);
				$arrayTotalFinal = array();
				while ($row = mysql_fetch_assoc($rs)) {
					$arrayTotalFinal['cant_items'] += $row['cant_items'];
					$arrayTotalFinal['saldoFactura'] += $row['saldoFactura'];
					$arrayTotalFinal['montoTotalFactura'] += $row['montoTotalFactura'];
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"19\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['cant_items'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['saldoFactura'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['montoTotalFactura'], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"4\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFactura(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaDebito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)
	AND cxc_nd.saldoNotaCargo > 0
	AND cxc_nd.estadoNotaCargo NOT IN (1)",
		valTpDato($idModuloPpal, "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nd.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nd.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[2] == 1 && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
    if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.idNotaCargo NOT IN (%s) ",
			valTpDato($valCadBusq[4], "campo"));
    }
	
	if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.fechaRegistroNotaCargo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nd.numeroNotaCargo LIKE %s
		OR cxc_nd.numeroControlNotaCargo LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cxc_nd.observacionNotaCargo LIKE %s
		OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
			FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_nd.idNotaCargo,
		cxc_nd.numeroNotaCargo,
		cxc_nd.numeroControlNotaCargo,
		cxc_nd.fechaRegistroNotaCargo,
		cxc_nd.fechaVencimientoNotaCargo,
		cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_nd.estadoNotaCargo,
		(CASE cxc_nd.estadoNotaCargo
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_nota_cargo,
		cxc_nd.montoTotalNotaCargo,
		cxc_nd.saldoNotaCargo,
		cxc_nd.observacionNotaCargo,
		cxc_nd.aplicaLibros,
		
		(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
		
		(IFNULL(cxc_nd.subtotalNotaCargo, 0)
			- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
		
		(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
			+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_iva,
		
		(IFNULL(cxc_nd.subtotalNotaCargo, 0)
			- IFNULL(cxc_nd.descuentoNotaCargo, 0)
			+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
				+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0))) AS total,
		
		uni_fis.id_unidad_fisica,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nd.id_unidad_fisica_bono = uni_fis.id_unidad_fisica)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
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
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "fechaRegistroNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "fechaVencimientoNotaCargo",$campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "numeroNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "numeroControlNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "36%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "descripcion_estado_nota_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "saldoNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Por Cobrar");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "montoTotalNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Nota de Débito");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"".utf8_encode("Financiamiento")."\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		switch($row['estadoNotaCargo']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAgregar%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAnticipoNotaCreditoChequeTransferencia', 'ND', '%s');\"><button type=\"button\" title=\"%s\"><img src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$row['idNotaCargo'],
					utf8_encode("Seleccionar"));
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaRegistroNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaVencimientoNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroNotaCargo']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroControlNotaCargo']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
				$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<div class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</div>" : "";
				$htmlTb .= (strlen($row['serial_carroceria']) > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($row['serial_carroceria'])."</div>" : "";
				$htmlTb .= (strlen($row['observacionNotaCargo']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionNotaCargo'])."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\"".$class.">".$row['descripcion_estado_nota_cargo']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoTotalNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Débito PDF\"/></a>",
					$row['idNotaCargo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['saldoNotaCargo'] < $row['total']) {
				if (in_array($row['id_modulo'], array(2,4,5))){ // 2 = Vehiculos, 4 = Alquiler, 5 = Financiamiento
					$aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idTpDcto=2&id=%s", $row['idNotaCargo']);
				} else if (in_array($row['id_modulo'], array(0,1,3))){ // 0 = Repuestos, 1 = Servicios, 3 = Administración
					$aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idTpDcto=2&id=%s", $row['idNotaCargo']);
				}
			} else {
				$aVerDctoAux = "";
			}
				$htmlTb .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".("Recibo(s) de Pago(s)")."\"/></a>" : "";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[13] += $row['saldoNotaCargo'];
		$arrayTotal[14] += $row['montoTotalNotaCargo'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">".("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[13] += $row['saldoNotaCargo'];
				$arrayTotalFinal[14] += $row['montoTotalNotaCargo'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">".("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"3\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipo");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"calcularPagosDeposito");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstConceptoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoAnticipo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumentoPagar");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"formDeposito");
$xajax->register(XAJAX_FUNCTION,"guardarAnticipo");
$xajax->register(XAJAX_FUNCTION,"insertarDctoPagado");
$xajax->register(XAJAX_FUNCTION,"insertarPago");
$xajax->register(XAJAX_FUNCTION,"insertarPagoDeposito");
$xajax->register(XAJAX_FUNCTION,"listaAnticipoNoCancelado");
$xajax->register(XAJAX_FUNCTION,"listaAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaFactura");
$xajax->register(XAJAX_FUNCTION,"listaNotaDebito");

function asignarBanco($idBanco) {
	$query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s;", valTpDato($idBanco, "int"));
	$rs = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	return utf8_encode($row['nombreBanco']);
}

function asignarNumeroCuenta($idCuenta) {
	$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s;", valTpDato($idCuenta, "int"));
	$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	
	return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}

function informacionCheque($idCheque){
	$query = sprintf("SELECT 
		cj_cc_cheque.id_banco_cliente,
		cj_cc_cheque.cuenta_cliente AS numero_cuenta_cliente,
		bancos.nombreBanco AS nombre_banco_cliente
	FROM cj_cc_cheque 
		INNER JOIN bancos ON cj_cc_cheque.id_banco_cliente = bancos.idBanco
	WHERE cj_cc_cheque.id_cheque = %s LIMIT 1",
		valTpDato($idCheque, "int"));
	$rsQuery = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowQuery = mysql_fetch_assoc($rsQuery);
	if(mysql_num_rows($rsQuery) == 0) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$query); }
	
	return $rowQuery;
}

function informacionTransferencia($idTransferencia){
	$query = sprintf("SELECT
		cj_cc_transferencia.cuenta_compania AS numero_cuenta_compania,
		cj_cc_transferencia.id_banco_compania,
		cj_cc_transferencia.id_banco_cliente,
		cj_cc_transferencia.id_cuenta_compania,						   
		bancos.nombreBanco AS nombre_banco_cliente,
		bancos2.nombreBanco AS nombre_banco_compania
	FROM cj_cc_transferencia 
		INNER JOIN bancos ON cj_cc_transferencia.id_banco_cliente = bancos.idBanco
		INNER JOIN bancos bancos2 ON cj_cc_transferencia.id_banco_compania = bancos2.idBanco
	WHERE cj_cc_transferencia.id_transferencia = %s LIMIT 1",
		$idTransferencia);
	$rsQuery = mysql_query($query) or die(mysql_error()." Linea: ".__LINE__." Query: ".$query);
	$rowQuery = mysql_fetch_assoc($rsQuery);
	if(mysql_num_rows($rsQuery) == 0){ die(mysql_error()." Linea: ".__LINE__." Query: ".$query); }
	
	return $rowQuery;
}

function insertarItemDctoPagado($contFila, $hddIdPago, $tablaPago, $hddTipoDocumento = "", $idDocumento = "", $idModulo = "", $txtFechaPago = "", $txtHoraPago = "", $nombreEmpresa = "", $fechaRegistroFactura = "", $numeroFactura = "", $idCliente = "", $nombreCliente = "", $estadoFactura = "", $descripcionConceptoFormaPago = "", $observacionFactura = "", $descripcionEstadoFactura = "", $txtMontoPagado = "") {
	global $raiz;
	
	$contFila++;
	
	switch($idModulo) {
		case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
		case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
		case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
		case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
		case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
		case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
		default : $imgDctoModulo = $idModulo;
	}
	
	switch($estadoFactura) {
		case 0 : $class = "class=\"divMsjError\""; break;
		case 1 : $class = "class=\"divMsjInfo\""; break;
		case 2 : $class = "class=\"divMsjAlerta\""; break;
		case 3 : $class = "class=\"divMsjInfo3\""; break;
		case 4 : $class = "class=\"divMsjInfo4\""; break;
	}
	
	$objDcto = new Documento;
	$objDcto->raizDir = $raiz;
	$objDcto->tipoMovimiento = (in_array($hddTipoDocumento,array("FA","ND","AN","CH","TB"))) ? 3 : 2;
	$objDcto->tipoDocumento = $hddTipoDocumento;
	$objDcto->tipoDocumentoMovimiento = (in_array($hddTipoDocumento,array("NC"))) ? 2 : 1;
	$objDcto->idModulo = $idModulo;
	$objDcto->idDocumento = $idDocumento;
	$aVerDcto = str_replace("\"", "\\\"", str_replace("'", "\'", $objDcto->verDocumento()));
	
	$btnEliminar = (!($hddIdPago > 0 && $tablaPago != "")) ? "<button type=\"button\" onclick=\"validarEliminarDcto(this);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"></button>" : "";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieDctoPagado').before('".
		"<tr id=\"trItmDctoPagado:%s\" name=\"trItmDctoPagado\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td><input type=\"checkbox\" name=\"cbxDctoAgregado[]\" checked=\"checked\" style=\"display:none;\" value=\"%s|%s|%s|%s\"/>".
				"<input id=\"cbx4\" name=\"cbx4[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmDctoPago:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td align=\"center\">%s".
				"<div>%s</div></td>".
			"<td>%s</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td>".
				"<table border=\"0\" width=\"%s\">".
				"<tr>".
					"<td nowrap=\"nowrap\">%s</td><td>%s</td>".
					"<td align=\"right\" width=\"%s\">%s</td>".
				"</tr>".
				"</table>".
			"</td>".
			"<td><div>%s</div>".
				"%s".
				"%s".
			"</td>".
			"<td align=\"center\" %s>%s</td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMontoPagado%s\" name=\"txtMontoPagado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoDocumento%s\" name=\"hddTipoDocumento%s\" value=\"%s\"/></td>".
		"</tr>');",	
		$contFila, $clase,
			$hddIdPago, $hddTipoDocumento, $idDocumento, $txtMontoPagado,
				$contFila,
			$contFila, $contFila,
			date(spanDateFormat, strtotime($txtFechaPago)),
				utf8_encode(date("h:i:s a", strtotime($txtHoraPago))),
			$nombreEmpresa,
			$hddTipoDocumento,
			date(spanDateFormat, strtotime($fechaRegistroFactura)),
			"100%",
				$aVerDcto, $imgDctoModulo,
				"100%", $numeroFactura,
			utf8_encode($idCliente.".- ".$nombreCliente),
				((strlen($descripcionConceptoFormaPago) > 0) ? "<div class=\"textoNegrita_9px\">".preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$descripcionConceptoFormaPago))))."</div>" : ""),
				((strlen($observacionFactura) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$observacionFactura))))."</div>" : ""),
			$class, utf8_encode($descripcionEstadoFactura),
			$contFila, $contFila, number_format($txtMontoPagado, 2, ".", ","),
			$btnEliminar,
				$contFila, $contFila, $hddIdPago,
				$contFila, $contFila, $hddTipoDocumento);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemMetodoPago($contFila, $hddIdPago, $idFormaPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtIdBancoCliente = "", $txtCuentaClientePago = "", $txtIdBancoCompania = "", $txtIdCuentaCompaniaPago = "", $txtFechaDeposito = "", $lstTipoTarjeta = "", $txtIdConceptoPago = "", $txtMontoPago = "", $hddEstatusPago = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia Bancaria, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito, 9 = Retención, 10 = Retencion I.S.L.R., 11 = Otro
	if (in_array($idFormaPago,array(3,5,6)) || (in_array($idFormaPago,array(4)) && !($txtIdNumeroDctoPago > 0))) {
		$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",
			valTpDato($txtIdCuentaCompaniaPago, "int"));
		$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta);
		if (!$rsBuscarNumeroCuenta) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	}
	
	$queryFormaPago = sprintf("SELECT * FROM formapagos WHERE idFormaPago = %s;", valTpDato($idFormaPago, "int"));
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsFormaPago = mysql_num_rows($rsFormaPago);
	$rowFormaPago = mysql_fetch_array($rsFormaPago);
	
	$nombreFormaPago = $rowFormaPago['nombreFormaPago'];
	
	$txtBancoClientePago = "-";
	$txtBancoCompaniaPago = "-";
	$txtCuentaCompaniaPago = "-";
	switch ($idFormaPago) {
		case 1 : // 1 = Efectivo
			break;
		case 2 : // 2 = Cheque
			if ($txtIdNumeroDctoPago > 0) {
				$arrayInformacionCheque = informacionCheque($txtIdNumeroDctoPago);
				$txtIdBancoCliente = $arrayInformacionCheque['id_banco_cliente'];
				$txtBancoClientePago = $arrayInformacionCheque['nombre_banco_cliente'];
				$txtCuentaClientePago = $arrayInformacionCheque['numero_cuenta_cliente'];
			} else {
				$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			}
			break;
		case 3 : // 3 = Deposito
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			break;
		case 4 : // 4 = Transferencia Bancaria
			if ($txtIdNumeroDctoPago > 0) {
				$arrayInformacionTransferencia = informacionTransferencia($txtIdNumeroDctoPago);
				$txtIdBancoCliente = $arrayInformacionTransferencia['id_banco_cliente'];
				$txtBancoClientePago = $arrayInformacionTransferencia['nombre_banco_cliente'];
				
				$txtIdBancoCompania = $arrayInformacionTransferencia['id_banco_compania'];
				$txtBancoCompaniaPago = $arrayInformacionTransferencia['nombre_banco_compania'];
				$txtIdCuentaCompaniaPago = $arrayInformacionTransferencia['id_cuenta_compania'];
				$txtCuentaCompaniaPago = $arrayInformacionTransferencia['numero_cuenta_compania'];
			} else {
				$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
				$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
				$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			}
			break;
		case 5 : // 5 = Tarjeta de Crédito
			$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			break;
		case 6 : // 6 = Tarjeta de Debito
			$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			
			$lstTipoTarjeta = 6;
			break;
		case 7 : // 7 = Anticipo
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT cxc_ant.*,
				concepto_forma_pago.id_concepto,
				concepto_forma_pago.descripcion
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT';",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				$hddIdConcepto = $rowAnticipo['id_concepto'];
				$arrayConceptoAnticipo[] = $rowAnticipo['descripcion'];
				$observacionDcto = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowAnticipo['observacionesAnticipo']))));
			}
			
			$nombreFormaPago .= (($totalRowsAnticipo > 0) ? "<br><span class=\"textoNegrita_10px\">(".implode(", ", $arrayConceptoAnticipo).")</span>" : "");
			break;
		case 8 : // 8 = Nota de Crédito
			// BUSCA EL TIPO DEL ANTICIPO
			$queryNotaCredito = sprintf("SELECT cxc_nc.*,
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) AS descripcion_motivo
			FROM cj_cc_notacredito cxc_nc
				LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
				INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_nc.idNotaCredito = %s;",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsNotaCredito = mysql_num_rows($rsNotaCredito);
			$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
			
			$descripcionMotivo = $rowNotaCredito['descripcion_motivo'];
			$observacionDcto = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowNotaCredito['observacionesNotaCredito']))));
			break;
		case 9 : // 9 = Retención
			break;
		case 10 : // 10 = Retencion I.S.L.R.
			break;
		case 11 : // 11 = Otro
			$query = sprintf("SELECT * FROM cj_conceptos_formapago
			WHERE id_concepto = %s
				AND estatus = 1;",
				valTpDato($txtIdConceptoPago, "int"));
			$rs = mysql_query($query);
			if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_array($rs);
			
			$nombreFormaPago .= (($totalRows > 0) ? "<br><span class=\"textoNegrita_10px\">(".utf8_encode($row['descripcion']).")</span>" : "");
			break;
	}
	
	$classMontoPago = "";
	if (in_array($hddEstatusPago,array(2))) {
		$classMontoPago = "class=\"divMsjAlerta\"";
	} else if (in_array($hddEstatusPago,array(3))) {
		$classMontoPago = "class=\"divMsjInfo4\"";
	} else if ($hddEstatusPago != 1) {
		$classMontoPago = "class=\"divMsjError\"";
	}
	
	$estatusPago = "";
	if (in_array($hddEstatusPago,array(2))) {
		$estatusPago = "<div align=\"center\">PAGO PENDIENTE</div>";
	} else if (in_array($hddEstatusPago,array(3))) {
		$estatusPago = "<div align=\"center\">PAGO RESERVADO</div>";
	} else if ($hddEstatusPago != 1) {
		$estatusPago = "<div align=\"center\">PAGO ANULADO</div>";
	}
	
	$btnEliminar = (!($hddIdPago > 0)) ? sprintf("<button type=\"button\" id=\"btnEliminarPago%s\" onclick=\"confirmarEliminarPago(%s);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"></button>", $contFila, $contFila) : "";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPiePago').before('".
		"<tr align=\"left\" id=\"trItmPago:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmPago:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxPiePago\" name=\"cbxPiePago[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"center\" %s>%s".
				"%s</td>".
			"<td %s><table width=\"%s\">".
				"<tr><td>%s</td><td><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr>".
				"</table></td>".
			"<td %s><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td align=\"right\" %s><input type=\"text\" id=\"txtMonto%s\" name=\"txtMonto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtFechaDeposito%s\" name=\"txtFechaDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdFormaPago%s\" name=\"txtIdFormaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdConcepto%s\" name=\"txtIdConcepto%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoCompania%s\" name=\"txtIdBancoCompania%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdCuentaCompaniaPago%s\" name=\"txtIdCuentaCompaniaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoCliente%s\" name=\"txtIdBancoCliente%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtTipoTarjeta%s\" name=\"txtTipoTarjeta%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdConceptoPago%s\" name=\"txtIdConceptoPago%s\" readonly=\"readonly\" value=\"%s\" title=\"txtIdConceptoPago\"/>".
				"<input type=\"hidden\" id=\"hddEstatusPago%s\" name=\"hddEstatusPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$classMontoPago, $nombreFormaPago,
				$estatusPago,
			$classMontoPago, "100%",
				$aVerDcto, $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
					$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoClientePago),
				$contFila, $contFila, $txtCuentaClientePago,
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
				$contFila, $contFila, $txtCuentaCompaniaPago,
			$classMontoPago, $contFila, $contFila, number_format($txtMontoPago, 2, ".", ","),
			$btnEliminar,
				$contFila, $contFila, $hddIdPago,
				$contFila, $contFila, $txtFechaDeposito,
				$contFila, $contFila, $idFormaPago,
				$contFila, $contFila, $hddIdConcepto,
				$contFila, $contFila, $txtIdBancoCompania,
				$contFila, $contFila, $txtIdCuentaCompaniaPago,
				$contFila, $contFila, $txtIdBancoCliente,
				$contFila, $contFila, $lstTipoTarjeta,
				$contFila, $contFila, $txtIdConceptoPago,
				$contFila, $contFila, $hddEstatusPago);
	
	return array(true, $htmlItmPie, $contFila);
}

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