<?php

function aprobarPropuesta($valForm){
	$objResponse = new xajaxResponse();
	                
	if(trim($valForm["txtFechaRegistro"]) == "" || trim($valForm["txtFechaLiberacion"]) == ""){
		errorGuardarDcto($objResponse);
		return $objResponse->alert("Debes seleccionar fecha");
	}
        
	mysql_query("START TRANSACTION;");
	
	if ($valForm['txtFechaRegistro'] != date(spanDateFormat)) {//si cambio la fecha
		//VERIFICAR QUE NO SEA MENOR A LA FECHA DE PROCESO DE CONTABILIDAD
		$query = sprintf("SELECT * FROM ".DBASE_CONTAB.".parametros
		WHERE %s < fec_proceso",
			valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"));
		$rs = mysql_query($query);
		if (!$rs){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		if (mysql_num_rows($rs)) {
			errorGuardarDcto($objResponse);
			return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha de proceso de contabilidad");
		}
		
		//VERIFICAR QUE NO SEA MENOR A LA FECHA DEL DOCUMENTO
		$queryDetProp = sprintf("SELECT id_factura, tipo_documento FROM te_propuesta_pago_detalle WHERE id_propuesta_pago = %s",
			valTpDato($valForm['hddIdPropuestaAprobar'], "int"));
		$rsDetProp = mysql_query($queryDetProp);
		if (!$rsDetProp){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		 
		while($rowDetProp = mysql_fetch_assoc($rsDetProp)){
			if ($rowDetProp['tipo_documento'] == 0) {//FACT
				$query = sprintf("SELECT * FROM cp_factura
				WHERE %s < fecha_origen AND id_factura = %s",
					valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
					valTpDato($rowDetProp['id_factura'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				if (mysql_num_rows($rs)) {
					errorGuardarDcto($objResponse);
					return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha del documento (FA)");
				}
			} else {//NOTA
				$query = sprintf("SELECT * FROM cp_notadecargo
				WHERE %s < fecha_origen_notacargo AND id_notacargo = %s",
					valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
					valTpDato($rowDetProp['id_factura'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				if (mysql_num_rows($rs)) {
					errorGuardarDcto($objResponse);
					return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha del documento (ND)");
				}
			}
		}
	}
	
	$queryPropuesta = sprintf("SELECT id_propuesta_pago, id_chequera, monto_pagar, id_proveedor, id_empresa, saldo_tem, idCuentas 
	FROM vw_te_propuesta_pago WHERE id_propuesta_pago = %s",
		valTpDato($valForm['hddIdPropuestaAprobar'], "int"));
	$rsPropuesta = mysql_query($queryPropuesta);
	if (!$rsPropuesta) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$rowPropuesta = mysql_fetch_assoc($rsPropuesta);
	$idChequera = $rowPropuesta['id_chequera'];
	
	if($rowPropuesta["estatus_propuesta"] == 1){
		errorGuardarDcto($objResponse);
		return $objResponse->alert("Esta propuesta ya fue aprobada");
	}
	
	$queryChequeDisponible = sprintf("SELECT * FROM te_chequeras WHERE id_cuenta = %s AND disponibles > 0",
		valTpDato($rowPropuesta['idCuentas'], "int"));
	$rsChequeDisponible = mysql_query($queryChequeDisponible);
	$rowChequeDisponible = mysql_fetch_assoc($rsChequeDisponible);
	if (!$rsChequeDisponible){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if (mysql_num_rows($rsChequeDisponible) == 0){	
		errorGuardarDcto($objResponse);
		return $objResponse->alert("No Tiene Cheques Disponibles Para Esta Cuenta");		
	}else{
		if($rowChequeDisponible['id_chq'] != $rowPropuesta['id_chequera']){		
			$updateChequera = sprintf("UPDATE te_propuesta_pago SET id_chequera = %s WHERE id_propuesta_pago = %s",
				valTpDato($rowChequeDisponible['id_chq'], "int"),
				valTpDato($valForm['hddIdPropuestaAprobar'], "int"));
			$rsUpdateChequera = mysql_query($updateChequera);			
			if (!$rsUpdateChequera){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$idChequera = $rowChequeDisponible['id_chq'];		
		}				
	}
	
	$queryMonto = sprintf("SELECT SUM(monto_pagar) AS monto FROM te_propuesta_pago_detalle WHERE id_propuesta_pago = %s",
		valTpDato($rowPropuesta['id_propuesta_pago'], "int"));
	$rsMonto = mysql_query($queryMonto);
	if (!$rsMonto){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }        
	$rowMonto = mysql_fetch_assoc($rsMonto);
	
	$queryFolioCheque = "SELECT numero_actual FROM te_folios WHERE id_folios = 4";
	$rsFolioCheque = mysql_query($queryFolioCheque);
	if(!$rsFolioCheque){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$rowFolioCheque = mysql_fetch_assoc($rsFolioCheque);
	
	$updateFolioCheque = "UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 4";
	$rsUpdateFolioCheque = mysql_query($updateFolioCheque);
	if(!$rsUpdateFolioCheque){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryChequera = sprintf("SELECT 
		te_chequeras.ultimo_nro_chq, 
		te_chequeras.disponibles, 
		te_chequeras.id_cuenta,
		cuentas.numeroCuentaCompania,
		bancos.nombreBanco
	FROM te_chequeras 
		INNER JOIN cuentas ON (te_chequeras.id_cuenta = cuentas.idCuentas)
		INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
	WHERE id_chq = %s",
		valTpDato($idChequera, "int"));
	$rsChequera = mysql_query($queryChequera);
	if(!$rsChequera){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowChequera = mysql_fetch_assoc($rsChequera);
        
	if(configChequeManual()){//NRO CHEQUE MANUAL
		$numeroChequeGenerado = trim($valForm["numeroChequeManual"]);
		if($numeroChequeGenerado == ""){
			errorGuardarDcto($objResponse); return $objResponse->alert("Debe asignar Nro de Cheque");
		}
	}else{//NRO CHEQUE AUTOMATICO
		$numeroChequeGenerado = $rowChequera['ultimo_nro_chq']+ 1;
	}
        
	$updateChequera = sprintf("UPDATE te_chequeras SET ultimo_nro_chq = %s, disponibles = (disponibles - 1) WHERE id_chq = %s",
		valTpDato($numeroChequeGenerado, "int"),
		valTpDato($idChequera, "int"));
	$rsUpdateChequera = mysql_query($updateChequera);	
	if(!$rsUpdateChequera){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryCheque = sprintf("INSERT INTO te_cheques (numero_cheque, folio_tesoreria, beneficiario_proveedor, id_beneficiario_proveedor, fecha_registro, fecha_liberacion, concepto, observacion, monto_cheque, id_chequera, estado_documento, fecha_aplicacion, id_empresa, desincorporado, id_usuario, id_factura) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($numeroChequeGenerado, "int"),
		valTpDato($rowFolioCheque['numero_actual'], "int"),
		valTpDato(1, "int"),// 0 = beneficiaro, 1 = proveedor
		valTpDato($rowPropuesta['id_proveedor'], "int"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
 		valTpDato(date("Y-m-d",strtotime($valForm["txtFechaLiberacion"])), "date"),
		valTpDato($valForm['txtConceptoCheque'], "text"),
		valTpDato($valForm['txtObservacionCheque'], "text"),
		valTpDato($rowMonto['monto'], "real_inglesa"),
		valTpDato($idChequera, "int"),
		valTpDato(2, "int"),//1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
		valTpDato($rowPropuesta['id_empresa'], "int"),
		valTpDato(1, "int"),//0 = desincorporado, 1 = normal
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(0, "int"));// 0 = Sin documento asociado, viene de propuesta
	mysql_query("SET NAMES 'utf8'");
	$rsCheque = mysql_query($queryCheque);
	if(!$rsCheque){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$idCheque = mysql_insert_id();
	
	$queryUpdate = sprintf("UPDATE te_propuesta_pago SET estatus_propuesta = 1, id_cheque = %s WHERE id_propuesta_pago = %s",
		valTpDato($idCheque, "int"),
		valTpDato($valForm['hddIdPropuestaAprobar'], "int"));
	$rsUpdate = mysql_query($queryUpdate);
	if(!$rsUpdate){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	mysql_query("SET NAMES 'latin1';");
	
	$queryEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato('CH', "text"),
		valTpDato($idCheque, "int"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])).date(" H:i:s"), "date"),
		valTpDato($rowChequera['id_cuenta'], "int"),
		valTpDato($rowPropuesta['id_empresa'], "int"),
		valTpDato($rowMonto['monto'], "real_inglesa"),
		valTpDato(0, "int"),//0 = resta, 1 = suma
		valTpDato($numeroChequeGenerado, "int"),
		valTpDato(1, "int"),//0 = desincorporado, 1 = normal
		valTpDato($valForm['txtObservacionCheque'], "text"),
		valTpDato(2, "int"));//1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
	mysql_query("SET NAMES 'utf8'");
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if(!$rsEstadoCuenta){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	mysql_query("SET NAMES 'latin1';");
	
	$queryPropuestaDetalle = sprintf("SELECT * FROM te_propuesta_pago_detalle WHERE id_propuesta_pago = %s",
		valTpDato($valForm['hddIdPropuestaAprobar'], "int"));
	$rsPropuestaDetalle = mysql_query($queryPropuestaDetalle);
	if(!$rsPropuestaDetalle){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	while($rowPropuestaDetalle = mysql_fetch_assoc($rsPropuestaDetalle)){		
		if ($rowPropuestaDetalle['tipo_documento'] == 0) {
			$tipoDocumento='FA';
		}else{
			$tipoDocumento='ND';
		}

		$queryCpPago = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($rowPropuestaDetalle['id_factura'], "int"),
			valTpDato($tipoDocumento, "text"),
			valTpDato('Cheque', "text"),
			valTpDato($idCheque, "int"),
			valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
			valTpDato($numeroChequeGenerado, "int"),
			valTpDato('-', "text"),
			valTpDato($rowChequera['nombreBanco'], "text"),
			valTpDato('-', "text"),
			valTpDato($rowChequera['numeroCuentaCompania'], "text"),
			valTpDato($rowPropuestaDetalle['monto_pagar'], "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$consultaCpPago = mysql_query($queryCpPago);		
		if (!$consultaCpPago){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		if ($rowPropuestaDetalle['monto_retenido'] != 0){
			$queryRetencion = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_cheque, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo, fecha_registro) 
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW());",
				valTpDato($rowPropuestaDetalle['id_factura'], "int"), 
				valTpDato($idCheque, "int"), 
				valTpDato($rowPropuestaDetalle['id_retencion'],"int"), 
				valTpDato($rowPropuestaDetalle['base_imponible_retencion'], "real_inglesa"), 
				valTpDato($rowPropuestaDetalle['sustraendo_retencion'], "real_inglesa"), 
				valTpDato($rowPropuestaDetalle['porcentaje_retencion'], "real_inglesa"), 
				valTpDato($rowPropuestaDetalle['monto_retenido'], "real_inglesa"), 
				valTpDato($rowPropuestaDetalle['codigo'], "text"), 
				valTpDato($rowPropuestaDetalle['tipo_documento'], "int"),
				valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"));			
			$rsRetencion = mysql_query($queryRetencion);
			if (!$rsRetencion){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

			$idRetencion = mysql_insert_id();		
			$queryCpPagoISLR = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($rowPropuestaDetalle['id_factura'], "int"),
				valTpDato($tipoDocumento, "text"),
				valTpDato('ISLR', "text"),
				valTpDato($idRetencion, "int"),
				valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
				valTpDato($idRetencion, "int"),
				valTpDato('-', "text"),
				valTpDato($rowChequera['nombreBanco'], "text"),
				valTpDato('-', "text"),
				valTpDato($rowChequera['numeroCuentaCompania'], "text"),
				valTpDato($rowPropuestaDetalle['monto_retenido'], "real_inglesa"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$consultaCpPagoISLR = mysql_query($queryCpPagoISLR);		
			if (!$consultaCpPagoISLR){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}

		if($rowPropuestaDetalle['tipo_documento']==0){//FACTURA
			$queryFactura = sprintf("SELECT numero_factura_proveedor, saldo_factura FROM cp_factura WHERE id_factura = %s", 
				valTpDato($rowPropuestaDetalle['id_factura'], "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowFactura = mysql_fetch_assoc($rsFactura);
                        
			$saldoValidarFactura = round(round($rowFactura['saldo_factura'],2) - ($rowPropuestaDetalle['monto_retenido'] + $rowPropuestaDetalle['monto_pagar']),2);

			if($saldoValidarFactura < 0){
				errorGuardarDcto($objResponse);
				return $objResponse->alert("El saldo de la factura Nro ".$rowFactura['numero_factura_proveedor']." no puede quedar en negativo: ".$saldoValidarFactura);                                
			}
			
			if($saldoValidarFactura == 0){
				$estatusFactura = "1";
			}else{
				$estatusFactura = "2";
			}

			$queryUptadeFactura = sprintf("UPDATE cp_factura SET estatus_factura = %s, saldo_factura = %s WHERE id_factura = %s",
				valTpDato($estatusFactura, "int"), 
				valTpDato($saldoValidarFactura, "real_inglesa"), 
				valTpDato($rowPropuestaDetalle['id_factura'], "int"));
			$rsUpdateFactura = mysql_query($queryUptadeFactura);
			if (!$rsUpdateFactura){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
		}else{//NOTA DE CARGO				
			$queryNota = sprintf("SELECT numero_notacargo, saldo_notacargo FROM cp_notadecargo WHERE id_notacargo = %s",
				valTpDato($rowPropuestaDetalle['id_factura'], "int"));
			$rsNota = mysql_query($queryNota);
			if(!$rsNota){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }								
			$rowNota = mysql_fetch_assoc($rsNota);
			
			$saldoValidarNota = round(round($rowNota['saldo_notacargo'],2) - ($rowPropuestaDetalle['monto_retenido'] + $rowPropuestaDetalle['monto_pagar']),2);
				
			if($saldoValidarNota < 0){
				errorGuardarDcto($objResponse);
				return $objResponse->alert("El saldo de la nota debito Nro ".utf8_encode($rowNota['numero_notacargo'])." no puede quedar en negativo: ".$saldoValidarNota);                                
			}
                        
			if($saldoValidarNota == 0){
				$estatusNota = "1";
			}else{
				$estatusNota = "2";
			}
						
			$queryUptadeNota = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = %s, saldo_notacargo = %s WHERE id_notacargo = %s",
				valTpDato($estatusNota, "int"), 
				valTpDato($saldoValidarNota, "real_inglesa"), 
				valTpDato($rowPropuestaDetalle['id_factura'], "int"));
			$rsUpdateNota = mysql_query($queryUptadeNota);
			if(!$rsUpdateNota){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
		}		
	}//fin while
	
	mysql_query("COMMIT;");
        
	//Modifcar Ernesto
	if(function_exists("generarChequesTe")){
	   generarChequesTe($idCheque,"","");
	}
	//Modifcar Ernesto
	
	$objResponse->alert("Propuesta aprobada exitosamente");
	$objResponse->script("byId('btnCancelarPropuesta').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function asignarBanco($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_cargaLstCuenta(xajax.getFormValues('frmBuscar'),".$row['idBanco'].");");
	$objResponse->script("byId('btnCancelarBanco').click();");
	
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
	$objResponse->assign("nombreProveedorBuscar","value",utf8_encode($row['nombre']));
  
	$objResponse->script("byId('btnCancelarBeneficiariosProveedores').click();
		byId('btnBuscar').click();");
	
	return $objResponse;
}

function buscarProveedor($valForm){
    $objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",$valForm['txtCriterioBusqProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "", "", $valBusq));
		
	return $objResponse;
}

function buscarPropuesta($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtIdProv'],
		$valForm['hddIdBanco'],
		$valForm['lstCuenta']);
	
	$objResponse->loadCommands(listaPropuesta(0, "", "", $valBusq));
		
	return $objResponse;
}

function cargaLstCuenta($valForm){
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idBanco = %s AND id_empresa = %s",
		valTpDato($valForm['hddIdBanco'], "int"),
		valTpDato($valForm['lstEmpresa'], "int"));
	
	$query = sprintf("SELECT * FROM cuentas %s", $sqlBusq);
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstCuenta\" name=\"lstCuenta\" ".$disabled." class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)){
		$html .= "<option value=\"".$row['idCuentas']."\">".$row['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdLstCuenta","innerHTML",$html);
		
	return $objResponse;
}

function eliminarPropuesta($frmEliminarPropuesta){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$queryNum = sprintf("SELECT id_chequera FROM te_propuesta_pago WHERE id_propuesta_pago = %s",
		valTpDato($frmEliminarPropuesta['hddIdPropuestaEliminar'], "int"));
	$rsNum = mysql_query($queryNum);
	if(!$rsNum){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowNum = mysql_fetch_assoc($rsNum);
	
	$queryDiferido = sprintf("SELECT te_chequeras.id_chq, te_chequeras.id_cuenta, cuentas.idCuentas, cuentas.Diferido 
	FROM cuentas 
		INNER JOIN te_chequeras ON (cuentas.idCuentas = te_chequeras.id_cuenta) 
	WHERE te_chequeras.id_chq = %s",
		valTpDato($rowNum['id_chequera'], "int"));
	$rsDiferido = mysql_query($queryDiferido);
	if(!$rsDiferido){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDiferido = mysql_fetch_assoc($rsDiferido);		
		
	$queryMontoPropuesta = sprintf("SELECT SUM(monto_pagar) as monto 
	FROM te_propuesta_pago_detalle 
	WHERE id_propuesta_pago = %s",
		valTpDato($frmEliminarPropuesta['hddIdPropuestaEliminar'], "int"));	
	$rsMontoPropuesta = mysql_query($queryMontoPropuesta);
	if(!$rsMontoPropuesta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowMontoPropuesta = mysql_fetch_assoc($rsMontoPropuesta);			
					
	$diferido = $rowDiferido['Diferido'] - $rowMontoPropuesta['monto'];
	$updateSaldo_Diferido = sprintf("UPDATE cuentas SET Diferido = %s WHERE idCuentas = %s",
		valTpDato($diferido, "real_inglesa"),
		valTpDato($rowDiferido['id_cuenta'], "int"));
	$rsUpdateSaldo_Diferido = mysql_query($updateSaldo_Diferido);
	if(!$rsUpdateSaldo_Diferido){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryDelete = sprintf("DELETE FROM te_propuesta_pago WHERE id_propuesta_pago = %s",
		valTpDato($frmEliminarPropuesta['hddIdPropuestaEliminar'], "int"));
	$rsDelete = mysql_query($queryDelete);
	if(!$rsDelete){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	
	$queryDeleteDetalle = sprintf("DELETE FROM te_propuesta_pago_detalle WHERE id_propuesta_pago = %s",
		valTpDato($frmEliminarPropuesta['hddIdPropuestaEliminar'], "int"));
	$rsDeleteDetalle = mysql_query($queryDeleteDetalle);	
	if(!$rsDeleteDetalle){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Propuesta eliminada");
	$objResponse->script("byId('btnCancelarEliminarPropuesta').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function formEliminarPropuesta($idPropuesta){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT id_propuesta_pago, fecha_propuesta_pago 
	FROM te_propuesta_pago 
	WHERE id_propuesta_pago = %s AND estatus_propuesta = 0",
		valTpDato($idPropuesta, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$row = mysql_fetch_assoc($rs);	
	
	$objResponse->assign("txtIdPropuestaEliminar","value",$row['id_propuesta_pago']);
	$objResponse->assign("hddIdPropuestaEliminar","value",$row['id_propuesta_pago']);
	$objResponse->assign("txtFechaPropuestaEliminar","value",date(spanDateFormat, strtotime($row['fecha_propuesta_pago'])));	
	
	return $objResponse;
}

function formPropuesta($idPropuesta){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT id_chequera, idCuentas FROM vw_te_propuesta_pago WHERE id_propuesta_pago = %s",
		valTpDato($idPropuesta, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$row = mysql_fetch_assoc($rs);
	$idChequera = $row['id_chequera'];	
	
	$queryChequeDisponible = sprintf("SELECT * FROM te_chequeras WHERE id_cuenta = %s AND disponibles > 0",
		valTpDato($row['idCuentas'], "int"));
	$rsChequeDisponible = mysql_query($queryChequeDisponible);
	$rowChequeDisponible = mysql_fetch_assoc($rsChequeDisponible);
	if (!$rsChequeDisponible){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if (mysql_num_rows($rsChequeDisponible) == 0){
		return $objResponse->alert("No tiene cheques disponibles para esta cuenta");		
	}else{
		if($rowChequeDisponible['id_chq'] != $row['id_chequera']){//TIENE CHEQUES PERO NO DE LA MISMA CHEQUERA
			$idChequera = $rowChequeDisponible['id_chq'];		
		}				
	}
	
	$queryChequera = sprintf("SELECT ultimo_nro_chq FROM te_chequeras WHERE id_chq = %s",
		valTpDato($idChequera, "int"));
	$rsChequera = mysql_query($queryChequera);
	if (!$rsChequera) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$rowChequera = mysql_fetch_assoc($rsChequera);
	
	if(configChequeManual()){//NRO CHEQUE MANUAL
		$objResponse->assign("numeroChequeManual","value","");
		$objResponse->script("byId('numeroChequeManual').readOnly = false;
							  byId('spanChequeManual').style.display = '';
							  byId('numeroChequeManual').className = 'inputHabilitado';");
	}else{//NRO CHEQUE AUTOMATICO
		$objResponse->assign("numeroChequeManual","value",$rowChequera['ultimo_nro_chq']+1);
		$objResponse->script("byId('numeroChequeManual').readOnly = true;
							  byId('spanChequeManual').style.display = 'none';
							  byId('numeroChequeManual').className = '';");
	}
	
	
	$objResponse->assign("txtIdPropuestaAprobar","value",$idPropuesta);
	$objResponse->assign("hddIdPropuestaAprobar","value",$idPropuesta);
	$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat));
	$objResponse->script("
	byId('txtFechaRegistro').className = 'inputInicial';
	$('#txtFechaRegistro').next('.JsDatePickBox').remove();
	desbloquearGuardado();");
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo, $idPropuestaPagoEliminar) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	$objResponse->assign("hddIdPropuestaPermisoEliminar","value",$idPropuestaPagoEliminar);
	
	return $objResponse;
}

function listaPropuesta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_propuesta != 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
			valTpDato($valCadBusq[1],"int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idBanco = %s",
			valTpDato($valCadBusq[2],"int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idCuentas = %s",
			valTpDato($valCadBusq[3],"int"));
	}
	
	$query = sprintf("SELECT 
		vw_te_propuesta_pago.*, 
		(SELECT SUM(det.monto_pagar) 
			FROM te_propuesta_pago_detalle det
			WHERE det.id_propuesta_pago = vw_te_propuesta_pago.id_propuesta_pago) AS monto,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor
	FROM vw_te_propuesta_pago 
		INNER JOIN cp_proveedor prov ON (vw_te_propuesta_pago.id_proveedor = prov.id_proveedor) %s", $sqlBusq);	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);                
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	if($totalRows == NULL){
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}	
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaPropuesta", "", $pageNum, "id_propuesta_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro.");
		$htmlTh .= ordenarCampo("xajax_listaPropuesta", "15", $pageNum, "fecha_propuesta_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPropuesta", "25%", $pageNum, "prov.id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaPropuesta", "25%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listaPropuesta", "20%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaPropuesta", "15%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
				
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".$row['id_propuesta_pago']."</td>";
			$htmlTb .= "<td>".date(spanDateFormat,strtotime($row['fecha_propuesta_pago']))."</td>";
			$htmlTb .= "<td align='left'>".utf8_encode($row['id_proveedor'].".- ".$row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align='left'>".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td>".$row['numeroCuentaCompania']."</td>";
			$htmlTb .= "<td align='right'>".number_format($row['monto'],2,'.',',')."</td>";
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Ver Propuesta\"><img src=\"../img/iconos/ico_view.png\" onclick=\"window.open('te_propuesta_pago.php?id_propuesta=".$row['id_propuesta_pago']."&acc=0','_self');\"/></td>";
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Editar Propuesta\"><img src=\"../img/iconos/pencil.png\" onclick=\"window.open('te_propuesta_pago.php?id_propuesta=".$row['id_propuesta_pago']."&acc=1','_self');\"/></td>";
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Aprobar Propuesta\"><a class=\"modalImg\" id=\"aAnular\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAprobarPropuesta', ".$row['id_propuesta_pago'].");\"><img src=\"../img/iconos/ico_aceptar.gif\"\"/></a></td>";
			$htmlTb .= sprintf("<td align='center' class=\"puntero\" title=\"Eliminar Propuesta\"><a class=\"modalImg\" id=\"aEliminarPropuesta%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', 'te_propuesta_pago_mantenimiento', %s);\"><img src=\"../img/iconos/ico_quitar.gif\"/></a></td>",
				$row['id_propuesta_pago'],
				$row['id_propuesta_pago']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPropuesta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPropuesta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPropuesta(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPropuesta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPropuesta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdListaPropuesta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
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

function listaBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("banco.idBanco != 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("banco.nombreBanco LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		banco.idBanco, 
		banco.nombreBanco, 
		banco.sucursal 
	FROM bancos banco
		INNER JOIN cuentas cuenta ON (cuenta.idBanco = banco.idBanco) %s GROUP BY banco.idBanco", $sqlBusq);	
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
		$htmlTh .= "<td width=\"5%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'><button type=\"button\" onclick=\"xajax_asignarBanco('".$row['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button></td>";
			$htmlTb .= "<td align=\"center\">".$row['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['sucursal'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBanco(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
				
	$objResponse->assign("tdListaBanco","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarPermiso($frmPermiso) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($frmPermiso['hddModulo'] == "te_propuesta_pago_mantenimiento") {
			$objResponse->script(sprintf("
			setTimeout(function(){
				abrirDivFlotante2(byId('aEliminarPropuesta%s'), 'tblEliminarPropuesta', %s);
			},1500);", 
			valTpDato($frmPermiso['hddIdPropuestaPermisoEliminar'], "int"),
			valTpDato($frmPermiso['hddIdPropuestaPermisoEliminar'], "int")));
		} else if ($frmPermiso['hddModulo'] == "te_cheque_fecha_registo") {
			$objResponse->script("
				byId('txtFechaRegistro').className = 'inputHabilitado';
				$('#txtFechaRegistro').next('.JsDatePickBox').remove();
				new JsDatePick({
					useMode:2,
					target:'txtFechaRegistro',
					dateFormat:'".spanDatePick."',
					cellColorScheme:'red'

				});");			
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"aprobarPropuesta");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarPropuesta");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"eliminarPropuesta");
$xajax->register(XAJAX_FUNCTION,"formEliminarPropuesta");
$xajax->register(XAJAX_FUNCTION,"formPropuesta");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"listaPropuesta");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaBanco");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function errorGuardarDcto($objResponse){
    $objResponse->script("desbloquearGuardado();");
}

?>