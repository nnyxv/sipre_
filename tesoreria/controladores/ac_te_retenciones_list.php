<?php

function anularRetencion($idRetencion){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_retenciones_list","eliminar")){ return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$queryRetencion = sprintf("SELECT id_factura, tipo, monto_retenido 
		FROM te_retencion_cheque 
		WHERE id_retencion_cheque = %s AND anulado IS NULL",
		valTpDato($idRetencion,"int"));
	$rsRetencion = mysql_query($queryRetencion);
	if(!$rsRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowRetencion = mysql_fetch_assoc($rsRetencion);
		
	if(mysql_num_rows($rsRetencion) == 0) { return $objResponse->alert("La retencion ya fue anulada"); }
	
	$idDocumento = $rowRetencion["id_factura"];
	$tipo = $rowRetencion["tipo"]; // 0 = factura, 1 = nota de cargo
	$montoRetenido = $rowRetencion["monto_retenido"];
	
	if($tipo == 0){//FACTURA
		$queryDocumento = sprintf("SELECT total_cuenta_pagar, saldo_factura AS saldo_documento 
			FROM cp_factura WHERE id_factura = %s",
			valTpDato($idDocumento,"int"));	
	}else if($tipo == 1){//NOTA DE CARGO
		$queryDocumento = sprintf("SELECT total_cuenta_pagar, saldo_notacargo AS saldo_documento 
			FROM cp_notadecargo WHERE id_notacargo = %s",
			valTpDato($idDocumento,"int"));
	}
	
	$rsDocumento = mysql_query($queryDocumento);
	if(!$rsDocumento) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDocumento = mysql_fetch_assoc($rsDocumento);
	
	$nuevoSaldo = $rowDocumento['saldo_documento'] + $montoRetenido;

	if($nuevoSaldo == $rowDocumento['total_cuenta_pagar']){
		$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
	}elseif($nuevoSaldo == 0){
		$cambioEstado = 1;
	}else{
		$cambioEstado = 2;
	}
	
	if($tipo == 0){//FACTURA
		$queryUpdateDoc = sprintf("UPDATE cp_factura SET saldo_factura = %s, estatus_factura = %s 
			WHERE id_factura = %s",
			valTpDato($nuevoSaldo, "real_inglesa"),
			valTpDato($cambioEstado, "int"),
			valTpDato($idDocumento, "int"));	
	}else if($tipo == 1){//NOTA DE CARGO
		$queryUpdateDoc = sprintf("UPDATE cp_notadecargo SET saldo_notacargo = %s, estatus_notacargo = %s 
			WHERE id_notacargo = %s",
			valTpDato($nuevoSaldo, "real_inglesa"),
			valTpDato($cambioEstado, "int"),
			valTpDato($idDocumento, "int"));
	}
	
	$rsUpdateDoc = mysql_query($queryUpdateDoc);
	if(!$rsUpdateDoc) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryUpdatePago = sprintf("UPDATE cp_pagos_documentos SET estatus = NULL, fecha_anulado = NOW(), id_empleado_anulado = %s
		WHERE tipo_pago = 'ISLR' AND id_documento = %s",
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($idRetencion, "int"));
	$rsUpdatePago = mysql_query($queryUpdatePago);
	if(!$rsUpdatePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryUpdateRetencion = sprintf("UPDATE te_retencion_cheque SET anulado = 1
		WHERE id_retencion_cheque = %s",
		valTpDato($idRetencion, "int"));
	$rsUpdateRetencion = mysql_query($queryUpdateRetencion);
	if(!$rsUpdateRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Retencion anulada con exito");
	$objResponse->script("byId('btnBuscar').click();");
	
	return $objResponse;
}

function asignarBeneficiario1($idBeneficiario){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = %s",
		valTpDato($idBeneficiario, "int"));
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdProv","value",$row['id_beneficiario']);
	$objResponse->assign("hddSelBePro","value",'0');
	$objResponse->assign("txtNombreProv","value",utf8_encode($row['nombre_beneficiario']));
    $objResponse->script("byId('btnCancelarBeneficiariosProveedores').click();");
	
	return $objResponse;
}

function asignarDetallesRetencion($idRetencion){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_retenciones WHERE id = %s",
		valTpDato($idRetencion, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$row = mysql_fetch_assoc($rs);

	$objResponse->assign("hddMontoMayorAplicar","value",$row['importe']);
	$objResponse->assign("hddPorcentajeRetencion","value",$row['porcentaje']);
	$objResponse->assign("hddSustraendoRetencion","value",$row['sustraendo']);
	$objResponse->assign("hddCodigoRetencion","value",$row['codigo']);
	$objResponse->script("calcularRetencion();");
	
	return $objResponse;
}

function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
	$nombreSucursal = "";		
	if ($rowEmpresa['id_empresa_padre_suc'] > 0){
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	}
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse->assign("txtNombreEmpresa","value",$empresa);
	$objResponse->assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$objResponse->script("byId('btnCancelarEmpresa').click();");
	
	return $objResponse;
}

function asignarFactura($idFactura){
	$objResponse = new xajaxResponse();
	
	$queryFactura = sprintf("SELECT numero_factura_proveedor, fecha_origen, fecha_vencimiento, observacion_factura, saldo_factura 
		FROM cp_factura 
		WHERE id_factura = %s",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if(!$rsFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }			
	$rowFactura = mysql_fetch_assoc($rsFactura);
		
	$objResponse->assign("txtIdFactura","value",$idFactura);
	$objResponse->assign("txtNumeroFactura","value",utf8_encode($rowFactura['numero_factura_proveedor']));
	$objResponse->assign("txtSaldoFactura","value",$rowFactura['saldo_factura']);
	$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_origen'])));
	$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_vencimiento'])));
	$objResponse->assign("txtDescripcionFactura","innerHTML",utf8_encode($rowFactura['observacion_factura']));
	$objResponse->assign("hddTipoDocumento","value","0");
	$objResponse->assign("tdFacturaNota","innerHTML","FACTURA");
	
	$queryIvaFactura = sprintf("SELECT base_imponible, iva 
		FROM cp_factura_iva 
		WHERE id_factura = %s",
		valTpDato($idFactura, "int"));
	$rsIvaFactura = mysql_query($queryIvaFactura);	
	if (!$rsIvaFactura) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);
	
	if (mysql_num_rows($rsIvaFactura)) {
		$rowIvaFactura = mysql_fetch_assoc($rsIvaFactura);
		$objResponse->assign("hddIva","value",$rowIvaFactura['iva']);
		$objResponse->assign("hddBaseImponible","value",$rowIvaFactura['base_imponible']);
		$objResponse->assign("txtBaseRetencionISLR","value",$rowIvaFactura['base_imponible']);
	} else {
		$objResponse->assign("hddIva","value","0");
		$objResponse->assign("hddBaseImponible","value","0");
		$objResponse->assign("txtBaseRetencionISLR","value","0");
	}
	
	$objResponse->script("xajax_verificarRetencionISLR(".$idFactura.",0);
	byId('btnCancelarFacturaNota').click();");
	
	return $objResponse;
}

function asignarNotaCargo($idNotaCargo){
	$objResponse = new xajaxResponse();
	
	$queryNotaCargo = sprintf("SELECT numero_notacargo,fecha_origen_notacargo, fecha_vencimiento_notacargo , observacion_notacargo, saldo_notacargo 
		FROM cp_notadecargo 
		WHERE id_notacargo = %s",
		valTpDato($idNotaCargo, "int"));
	$rsNotaCargo = mysql_query($queryNotaCargo);		
	if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);
		
	$objResponse->assign("txtIdFactura","value",$idNotaCargo);
	$objResponse->assign("txtNumeroFactura","value",utf8_encode($rowNotaCargo['numero_notacargo']));
	$objResponse->assign("txtSaldoFactura","value",$rowNotaCargo['saldo_notacargo']);
	$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat,strtotime($rowNotaCargo['fecha_origen_notacargo'])));
	$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($rowNotaCargo['fecha_vencimiento_notacargo'])));
	$objResponse->assign("txtDescripcionFactura","innerHTML", utf8_encode($rowNotaCargo['observacion_notacargo']));
	$objResponse->assign("hddTipoDocumento","value","1");
	$objResponse->assign("tdFacturaNota","innerHTML","NOTA DE CARGO");
	
	$queryIvaNotaCargo = sprintf("SELECT baseimponible, iva  FROM cp_notacargo_iva WHERE id_notacargo = %s",
		valTpDato($idNotaCargo, "int"));
	$rsIvaNotaCargo = mysql_query($queryIvaNotaCargo);	
	if (!$rsIvaNotaCargo) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);
	
	if (mysql_num_rows($rsIvaNotaCargo)){
		$rowIvaNotaCargo = mysql_fetch_assoc($rsIvaNotaCargo);
		$objResponse->assign("hddIva","value",$rowIvaNotaCargo['iva']);
		$objResponse->assign("hddBaseImponible","value",$rowIvaNotaCargo['baseimponible']);
		$objResponse->assign("txtBaseRetencionISLR","value",$rowIvaNotaCargo['baseimponible']);
	}else{
		$objResponse->assign("hddIva","value","0");
		$objResponse->assign("hddBaseImponible","value","0");
		$objResponse->assign("txtBaseRetencionISLR","value","0");
	}
	
	$objResponse->script("xajax_verificarRetencionISLR(".$idNotaCargo.",1);
	byId('btnCancelarFacturaNota').click();");
	
	return $objResponse;
}

function asignarProveedor($idProveedor){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdProv","value",$row['id_proveedor']);
	$objResponse->assign("hddSelBePro","value",'1');
	$objResponse->assign("txtNombreProv","value",utf8_encode($row['nombre']));
	$objResponse->script("byId('btnCancelarBeneficiariosProveedores').click();");
	
	return $objResponse;
}

function buscarCliente1($valform) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']);

	if ($valform['buscarProv'] == "1") {		
		$objResponse->loadCommands(listaProveedor(0, "", "", $valBusq));
	} elseif($valform['buscarProv'] == "2") {
		$objResponse->loadCommands(listaBeneficiarios(0, "", "", $valBusq));
	}
	
	return $objResponse;
}

function buscarDocumento($valForm, $idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$idEmpresa,
		$valForm['txtCriterioBuscarFacturaNota']);
		
	if ($valForm['buscarTipoDcto'] == "2") { // FACTURA
		$objResponse->loadCommands(listaFactura(0,'','', $valBusq));
	} else { // NOTA DE CARGO		
		$objResponse->loadCommands(listaNotaCargo(0,'','', $valBusq));
	}
	
	return $objResponse;
}

function buscarEmpresa($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarRetenciones($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtIdProv'],
		$valForm['txtFecha'],
		$valForm['txtCriterio'],
		$valForm['listAnulado'],
		$valForm['listPago']);

	$objResponse->loadCommands(listaRetencion(0, "", "", $valBusq));

	return $objResponse;
}

function cargaLstAdministradoraPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array(0 => "Sin Firma de Administración", 1 => "Con Firma de Administración");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_exportarRetencionLotes(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstAdministradoraPDF\" name=\"lstAdministradoraPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAdministradoraPDF","innerHTML",$html);
	
	return $objResponse;
}

function cargaListRetencionISLR(){
	$objResponse = new xajaxResponse();
	
	$queryRetenciones = "SELECT * FROM te_retenciones WHERE activo = 1";
	$rsRetenciones = mysql_query($queryRetenciones);
	
	$html = "<select id=\"selRetencionISLR\" name=\"selRetencionISLR\" class=\"inputHabilitado\" disabled=\"disabled\" onchange=\"xajax_asignarDetallesRetencion(this.value)\">";
	
	while ($rowRetenciones = mysql_fetch_assoc($rsRetenciones)) {
		$html .= "<option value=\"".$rowRetenciones['id']."\">".utf8_encode($rowRetenciones['descripcion'])."</option>";
	}
	$html .= "</select>";
		
	$objResponse->assign("tdRetencionISLR","innerHTML",$html);
	$objResponse->assign("hddMontoMayorAplicar","innerHTML","0");
	$objResponse->assign("hddPorcentajeRetencion","innerHTML","0");
	$objResponse->assign("hddSustraendoRetencion","innerHTML","0");

	return $objResponse;
}

function exportarListado($valForm){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtIdProv'],
		$valForm['txtFecha'],
		$valForm['txtCriterio'],
		$valForm['listAnulado'],
		$valForm['listPago']);

	$objResponse->script(sprintf("window.open('reportes/te_retenciones_islr_excel.php?valBusq=%s');", $valBusq));
	
	return $objResponse;
}

function exportarListadoSeniat($valForm){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtIdProv'],
		$valForm['txtFecha'],
		$valForm['txtCriterio'],
		$valForm['listAnulado'],
		$valForm['listPago']);

	$objResponse->script(sprintf("window.open('reportes/te_retenciones_islr_seniat_excel.php?valBusq=%s');", $valBusq));
	
	return $objResponse;
}

function exportarRetencionLotes($valForm){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtIdProv'],
		$valForm['txtFecha'],
		$valForm['txtCriterio'],
		$valForm['listAnulado'],
		$valForm['listPago']);

	$objResponse->script(sprintf("verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?documento=4&valBusq=%s&lstAdministradoraPDF=%s',700,700);", 
	$valBusq, $valForm['lstAdministradoraPDF']));
	
	$objResponse->assign("tdlstAdministradoraPDF","innerHTML","");
	
	return $objResponse;
}

function guardarRetencion($frmRetencion){	
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_retenciones_list","insertar")){ return $objResponse; }

	if($frmRetencion['txtMontoRetencionISLR'] < 0){
		return $objResponse->alert("La retencion no puede ser negativa: ".$frmRetencion['txtMontoRetencionISLR']);
	}
	
	if($frmRetencion['txtMontoRetencionISLR'] == 0){
		return $objResponse->alert("La retencion no puede ser Cero: ".$frmRetencion['txtMontoRetencionISLR']);
	}
	
	if ($frmRetencion['txtMontoRetencionISLR'] > 0){		
		mysql_query("START TRANSACTION;");
		
		$idDocumento = $frmRetencion['txtIdFactura'];
		$tipoDocumento = $frmRetencion['hddTipoDocumento'];// 0 = factura, 1 = nota de cargo
		$fecha = date("Y-m-d", strtotime($frmRetencion["txtFechaRetencion"]));
		$tipoDocumentoPago = ($tipoDocumento == '1') ? 'ND' : 'FA';
		
		//verifico si ya tiene:
		$query = sprintf("SELECT te_retenciones.descripcion
		FROM cp_pagos_documentos pago
			INNER JOIN te_retencion_cheque ON te_retencion_cheque.id_retencion_cheque = pago.id_documento
			INNER JOIN te_retenciones ON te_retencion_cheque.id_retencion = te_retenciones.id
		WHERE pago.tipo_pago = 'ISLR' 
			AND pago.estatus = 1 
			AND pago.tipo_documento_pago = %s 
			AND pago.id_documento_pago = %s",
			valTpDato($tipoDocumentoPago,"text"),
			valTpDato($idDocumento,"int"));				
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
		$row = mysql_fetch_assoc($rs);
		
		if(mysql_num_rows($rs) > 0){//si tiene retencion activa
			return	$objResponse->alert("El documento ya posee retencion: \n".utf8_encode($row['descripcion'])."");
		}
		
		$queryRetencion = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo, tipo_documento, fecha_registro)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idDocumento,"int"), 
			valTpDato($frmRetencion['selRetencionISLR'],"int"),
			valTpDato($frmRetencion['txtBaseRetencionISLR'],"real_inglesa"), 
			valTpDato($frmRetencion['hddSustraendoRetencion'],"real_inglesa"), 
			valTpDato($frmRetencion['hddPorcentajeRetencion'],"real_inglesa"), 
			valTpDato($frmRetencion['txtMontoRetencionISLR'],"real_inglesa"), 
			valTpDato($frmRetencion['hddCodigoRetencion'],"text"), 
			$tipoDocumento,// 0 = factura, 1 = nota de cargo
			2,// 0 = Cheque, 1 = Transferencia, 2 = Sin Documento
			valTpDato($fecha,"date"));
		$rsRetencion = mysql_query($queryRetencion);
		if (!$rsRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		$idRetencionCheque = mysql_insert_id();
		
		$queryPago = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago,tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idDocumento, "int"), 
			valTpDato($tipoDocumentoPago, "text"),// FA, ND
			valTpDato('ISLR', "text"),
			valTpDato($idRetencionCheque, "int"),
			valTpDato($fecha,"date"),
			valTpDato($idRetencionCheque, "int"),
			valTpDato('-', "text"),
			valTpDato('-', "text"),
			valTpDato('-', "text"),
			valTpDato('-', "text"),
			valTpDato($frmRetencion['txtMontoRetencionISLR'], "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));		
		$rsPago = mysql_query($queryPago);		
		if (!$rsPago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		if($tipoDocumento == '1'){// 0 = factura, 1 = nota cargo
			$sql = sprintf("SELECT saldo_notacargo AS saldo_documento, total_cuenta_pagar FROM cp_notadecargo WHERE id_notacargo = %s LIMIT 1",
				valTpDato($idDocumento, "int"));
		}else{
			$sql = sprintf("SELECT saldo_factura AS saldo_documento, total_cuenta_pagar FROM cp_factura WHERE id_factura = %s LIMIT 1",
				valTpDato($idDocumento, "int"));
		}
		
		$rs = mysql_query($sql);
		if (!$rs) { $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		$saldoNuevo = $row['saldo_documento'] - $frmRetencion['txtMontoRetencionISLR'];
		
		if($saldoNuevo < 0){ return $objResponse->alert('El saldo del documento '.$tipoDocumentoPago.'  '.$frmRetencion['txtNumeroFactura'].' no puede ser negativo: '.$saldoNuevo.''); 
		}

		if($saldoNuevo == $row['total_cuenta_pagar']){
			$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
		}elseif($saldoNuevo == 0){
			$cambioEstado = 1;
		}else{
			$cambioEstado = 2;
		}
		
		if($tipoDocumento == '1'){// 0 = factura, 1 = nota cargo
			$sqlSaldo = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = %s, saldo_notacargo = %s 
				WHERE id_notacargo = %s ;",
			valTpDato($cambioEstado, "int"),
			valTpDato($saldoNuevo, "real_inglesa"),
			valTpDato($idDocumento, "int"));
		}else{
			$sqlSaldo = sprintf("UPDATE cp_factura SET estatus_factura = %s, saldo_factura = %s 
				WHERE id_factura = %s ;",
			valTpDato($cambioEstado, "int"),
			valTpDato($saldoNuevo, "real_inglesa"), 
			valTpDato($idDocumento, "int"));
		}		
	
		$rs = mysql_query($sqlSaldo);
		if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$objResponse->script("verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?id=".$idRetencionCheque."&documento=3',700,700);");
		
		$objResponse->alert("Retencion creada correctamente");
		$objResponse->script("byId('btnCancelarRetencion').click();");
		$objResponse->script("byId('btnBuscar').click();");
		
		mysql_query("COMMIT");		
	}
	
	return $objResponse;
}

function listaBeneficiarios($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT(lci_rif,'-',ci_rif_beneficiario) LIKE %s
		OR CONCAT(lci_rif,ci_rif_beneficiario) LIKE %s
		OR nombre_beneficiario LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_beneficiario AS id,
		CONCAT(lci_rif,'-',ci_rif_beneficiario) as rif_beneficiario,
		nombre_beneficiario
	FROM te_beneficiarios %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
        
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaBeneficiarios", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo"));
		$htmlTh .= ordenarCampo("xajax_listaBeneficiarios", "20%", $pageNum, "rif_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."/".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listaBeneficiarios", "65%", $pageNum, "nombre_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarBeneficiario1('".$row['id']."');\" title=\"Seleccionar Beneficiario\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_beneficiario'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_beneficiario'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBeneficiarios(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBeneficiarios(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBeneficiarios(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBeneficiarios(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBeneficiarios(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdContenido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("usuario_empresa.id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre_empresa LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}

	$query = sprintf("SELECT 
		usuario_empresa.id_empresa_reg,
		CONCAT_WS(' - ', usuario_empresa.nombre_empresa, usuario_empresa.nombre_empresa_suc) AS nombre_empresa
	FROM vw_iv_usuario_empresa usuario_empresa %s", $sqlBusq);	
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
		$htmlTh .= "<td width='5%'></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$row['id_empresa_reg']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}		
	
	$objResponse->assign("tdListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaFactura($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s OR cxp_fact.id_empresa IN ((SELECT id_empresa_reg 
													FROM vw_iv_empresas_sucursales 
													WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))
		AND cxp_fact.estatus_factura <> 1",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("(cxp_fact.numero_factura_proveedor LIKE %s 
		OR cxp_fact.numero_control_factura LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
		
	$query = sprintf("SELECT 
		cxp_fact.*, 
		empresa.nombre_empresa, 
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre
	FROM cp_factura cxp_fact
		INNER JOIN pg_empresa empresa ON cxp_fact.id_empresa = empresa.id_empresa 
		INNER JOIN cp_proveedor prov ON cxp_fact.id_proveedor = prov.id_proveedor %s", $sqlBusq);  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"2%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFactura", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "5%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "5%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Control");		
		$htmlTh .= ordenarCampo("xajax_listaFactura", "20%", $pageNum, "observacion_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "5%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "5%", $pageNum, "saldo_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarFactura('".$row['id_factura']."');\" title=\"Seleccionar Factura\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['id_proveedor'].".- ".$row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_factura_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_control_factura'])."</td>";
			$htmlTb .= "<td align=\"left\" class=\"texto_9px\">".utf8_encode($row['observacion_factura'])."</td>";
			$htmlTb .= "<td>".date(spanDateFormat,strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['saldo_factura']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFactura(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
							  
	$objResponse->assign("tdContenidoDocumento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nd.id_empresa = %s OR cxp_nd.id_empresa IN ((SELECT id_empresa_reg 
													FROM vw_iv_empresas_sucursales 
													WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))
		AND cxp_nd.estatus_notacargo <> 1",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("(cxp_nd.numero_notacargo LIKE %s 
		OR cxp_nd.numero_control_notacargo LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}

	$query = sprintf("SELECT 
		cxp_nd.*, 
		empresa.nombre_empresa, 
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre
	FROM cp_notadecargo cxp_nd
		INNER JOIN pg_empresa empresa ON cxp_nd.id_empresa = empresa.id_empresa 
		INNER JOIN cp_proveedor prov ON cxp_nd.id_proveedor = prov.id_proveedor %s", $sqlBusq);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"2%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "5%", $pageNum, "numero_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "5%", $pageNum, "numero_control_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Control");
		
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "20%", $pageNum, "observacion_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "5%", $pageNum, "fecha_origen_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "5%", $pageNum, "saldo_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCargo('".$row['id_notacargo']."');\" title=\"Seleccionar Nota Cargo\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['id_proveedor'].".- ".$row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_notacargo'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_control_notacargo'])."</td>";
			$htmlTb .= "<td align=\"left\" class=\"texto_9px\">".utf8_encode($row['observacion_notacargo'])."</td>";
			$htmlTb .= "<td>".date(spanDateFormat,strtotime($row['fecha_origen_notacargo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['saldo_notacargo']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		  
	$objResponse->assign("tdContenidoDocumento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;	
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";		
		$sqlBusq .= $cond.sprintf("(id_proveedor LIKE %s
		OR CONCAT_WS('-',lrif,rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_proveedor,
		CONCAT_WS('-',lrif,rif) AS rif_proveedor,
		nombre
	FROM cp_proveedor %s", $sqlBusq);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);	
	$rsLimit = mysql_query($queryLimit);        
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanProvCxP);
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."');\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdContenido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
			
	return $objResponse;
}

function listaRetencion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	        
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_te_retencion_cheque.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
        
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_te_retencion_cheque.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
        
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE_FORMAT(vw_te_retencion_cheque.fecha_registro, %s) = %s",
			valTpDato('%Y/%m', "text"),
			valTpDato(date("Y/m",strtotime('01-'.$valCadBusq[2])), "text"));
	}
        
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_te_retencion_cheque.numero_factura LIKE %s 
		OR vw_te_retencion_cheque.numero_control_factura LIKE %s)",
			valTpDato('%'.$valCadBusq[3].'%', 'text'),
			valTpDato('%'.$valCadBusq[3].'%', 'text'));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[4] == 1){
			$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado = 1 ");
		}else{
			$sqlBusq .= $cond.sprintf(" vw_te_retencion_cheque.anulado IS NULL ");
		}
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_te_retencion_cheque.tipo_documento = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	$query = sprintf("SELECT 
		vw_te_retencion_cheque.id_retencion_cheque,
		vw_te_retencion_cheque.id_cheque,
		vw_te_retencion_cheque.id_proveedor,
		vw_te_retencion_cheque.rif_proveedor,
		vw_te_retencion_cheque.nombre,
		vw_te_retencion_cheque.numero_control_factura,
		vw_te_retencion_cheque.numero_factura,
		vw_te_retencion_cheque.id_factura,
		vw_te_retencion_cheque.codigo,
		vw_te_retencion_cheque.subtotal_factura,
		vw_te_retencion_cheque.monto_retenido,
		vw_te_retencion_cheque.porcentaje_retencion,
		vw_te_retencion_cheque.descripcion,
		vw_te_retencion_cheque.base_imponible_retencion,
		vw_te_retencion_cheque.sustraendo_retencion,
		vw_te_retencion_cheque.tipo,
		vw_te_retencion_cheque.tipo_documento, 
		vw_te_retencion_cheque.anulado,
		vw_te_retencion_cheque.fecha_registro
	FROM vw_te_retencion_cheque %s", $sqlBusq);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);        
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "anulado", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "5%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "8%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "RIF Retenido");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "30%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Doc.");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "5%", $pageNum, "numero_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "5%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Pago");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "5%", $pageNum, "subtotal_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Operaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "id_retencion_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Comprob.");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "10%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Retenci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "codigo", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo Concepto");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "base_imponible_retencion", $campOrd, $tpOrd, $valBusq, $maxRows, "Base Retenci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "monto_retenido", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Retenido");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "porcentaje_retencion", $campOrd, $tpOrd, $valBusq, $maxRows, "Porcentaje Retenci&oacute;n");
				$htmlTh .= ordenarCampo("xajax_listaRetencion", "1%", $pageNum, "sustraendo_retencion", $campOrd, $tpOrd, $valBusq, $maxRows, "Sustraendo Retenci&oacute;n");
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= "<td width=\"1%\"></td>";		
	$htmlTh .= "</tr>";
	
	$cont = 0;
	$contb = 1;
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;		
		
		if($row['anulado'] == 1){// NULL = Activo, 1 = Anulado
			$imgAnulado = "<img title=\"Anulado\" src=\"../img/iconos/ico_rojo.gif\">";
		}else{
			$imgAnulado = "<img title=\"Activo\" src=\"../img/iconos/ico_verde.gif\">";
		}
		
		$tipoDocumento = ($row["tipo"] == 0) ? "FA" : "ND"; // 0 = FA, 1 = ND
		
		$tipoPago = ""; // NULL = ninguno, 0 = CH, 1 = TR
		if($row["tipo_documento"] == 0){
			$tipoPago = "CH";
		}elseif($row["tipo_documento"] == 1){
			$tipoPago = "TR";
		}
		
		$botonAnular = "";
		if($row['id_cheque'] == "" && $row['anulado'] != 1){//sin pago asociado y no anulado
			$botonAnular = sprintf("<img title=\"Anular\" class=\"puntero\" src=\"../img/iconos/delete.png\" onclick=\"if(confirm('¿Deseas anular la retencion?')){ xajax_anularRetencion(%s); }\"></img>",
				$row['id_retencion_cheque']);
		}
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgAnulado."</td>";
			$htmlTb .= "<td align=\"center\">".$contb."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_proveedor']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['id_proveedor'].".- ".$row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoDocumento."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_control_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoPago."</td>";
			$htmlTb .= "<td align=\"right\">".$row['subtotal_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_retencion_cheque']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['codigo']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['base_imponible_retencion']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['monto_retenido']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['porcentaje_retencion']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['sustraendo_retencion']."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Imprimir\"><img class=\"puntero\" src=\"../img/iconos/page_white_acrobat.png\" onclick=\"verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?id=".$row['id_retencion_cheque']."&documento=3',700,700);\" ></td>";
			$htmlTb .= "<td>".$botonAnular."</td>";
			$cont +=  $row['monto_retenido'];
			$contb += 1;
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"22\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRetencion(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
	$htmlTblFin .= "<br><br>";
	
	$queryTotales = sprintf("SELECT 
		vw_te_retencion_cheque.rif_proveedor,
		vw_te_retencion_cheque.nombre,
		vw_te_retencion_cheque.numero_control_factura,
		vw_te_retencion_cheque.id_factura,
		vw_te_retencion_cheque.codigo,
		vw_te_retencion_cheque.monto_retenido,
		vw_te_retencion_cheque.porcentaje_retencion,
		vw_te_retencion_cheque.id_retencion_cheque,
		vw_te_retencion_cheque.descripcion,
		vw_te_retencion_cheque.base_imponible_retencion,
		vw_te_retencion_cheque.sustraendo_retencion,
		vw_te_retencion_cheque.tipo,
		vw_te_retencion_cheque.tipo_documento,  
		vw_te_retencion_cheque.fecha_registro
	FROM vw_te_retencion_cheque %s ", $sqlBusq); 
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	while($rowTotales = mysql_fetch_assoc($rsTotales)){
		$contTotal +=  $rowTotales['monto_retenido'];	
	}
	
	$htmlx.="<table align=\"center\" class=\"tabla\" border=\"1\" width=\"60%\">";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total por P&aacute;gina:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($cont,'2','.',',')."</td>";
		$htmlx.="</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($contTotal,'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaRetencion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);	
		
	return $objResponse;
}

function verificarRetencionISLR($idDocumento, $tipoDocumento){
	$objResponse = new xajaxResponse();
	
	if($tipoDocumento == 0){//FACTURA
		$tipoDocumentoPago = "FA";
	}elseif($tipoDocumento == 1){//NOTA DE CARGO
		$tipoDocumentoPago = "ND";
	}
	
	$query = sprintf("SELECT te_retenciones.descripcion
	FROM cp_pagos_documentos pago
		INNER JOIN te_retencion_cheque ON te_retencion_cheque.id_retencion_cheque = pago.id_documento
		INNER JOIN te_retenciones ON te_retencion_cheque.id_retencion = te_retenciones.id
	WHERE pago.tipo_pago = 'ISLR' 
		AND pago.estatus = 1 
		AND pago.tipo_documento_pago = %s 
		AND pago.id_documento_pago = %s",
		valTpDato($tipoDocumentoPago,"text"),
		valTpDato($idDocumento,"int"));				
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$row = mysql_fetch_assoc($rs);
	
	if(mysql_num_rows($rs) == 0){//sino tiene retencion, permitir agregar
		$objResponse->script("byId('selRetencionISLR').disabled = false;
					  		calcularRetencion()");							
	}else{
		$descripcionRetencion = "El documento ya posee retenci&oacute;n: <br><b>".utf8_encode($row['descripcion'])."</b>";
		$objResponse->assign("selRetencionISLR","value","1");
		$objResponse->script("byId('selRetencionISLR').disabled = true;
							xajax_asignarDetallesRetencion(1);");//lo coloca en 0 si ya habia monto
	}
	
	$objResponse->assign("tdInfoRetencionISLR","innerHTML",$descripcionRetencion);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarFactura");
$xajax->register(XAJAX_FUNCTION,"asignarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarRetenciones");
$xajax->register(XAJAX_FUNCTION,"buscarDocumento");
$xajax->register(XAJAX_FUNCTION,"cargaLstAdministradoraPDF");
$xajax->register(XAJAX_FUNCTION,"cargaListRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"exportarListado");
$xajax->register(XAJAX_FUNCTION,"exportarListadoSeniat");
$xajax->register(XAJAX_FUNCTION,"exportarRetencionLotes");
$xajax->register(XAJAX_FUNCTION,"guardarRetencion");
$xajax->register(XAJAX_FUNCTION,"listaRetencion");
$xajax->register(XAJAX_FUNCTION,"listaBeneficiarios");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaFactura");
$xajax->register(XAJAX_FUNCTION,"listaNotaCargo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente1");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarBeneficiario1");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"verificarRetencionISLR");

?>