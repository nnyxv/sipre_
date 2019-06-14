<?php

function actualizarCheque($valForm){
	$objResponse = new xajaxResponse();
	
	if(!$valForm['cbxChequeEntregado']){
		return $objResponse->alert("Debe indicar que fue entregado");
	}
	
	mysql_query("START TRANSACTION;");
	
	$queryCheq = sprintf("SELECT * from te_cheques WHERE id_cheque = %s",
		valTpDato($valForm['hddIdCheque'], "int"));
	$rsCheq = mysql_query($queryCheq);
	if(!$rsCheq) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCheq = mysql_fetch_assoc($rsCheq);
	
	$queryUpdate = sprintf("UPDATE te_cheques SET entregado = 1 WHERE id_cheque = %s",
		valTpDato($valForm['hddIdCheque'], "int"));
	$rsUpdate = mysql_query($queryUpdate);	
	if (!$rsUpdate) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	if($rowCheq["entregado"] == 0){//SINO HA SIDO ENTREGADO, ES LA PRIMERA VEZ, CALCULAR CUENTAS		
		$queryChequera = sprintf("SELECT ultimo_nro_chq, disponibles, id_cuenta FROM te_chequeras WHERE id_chq = %s",
			valTpDato($rowCheq['id_chequera'], "int"));	
		$rsChequera = mysql_query($queryChequera);
		if(!$rsChequera) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowChequera = mysql_fetch_assoc($rsChequera);	
			
		$queryCuenta = sprintf("SELECT saldo_tem, Diferido FROM cuentas WHERE idCuentas = %s",
			valTpDato($rowChequera['id_cuenta'], "int"));
		$rsCuenta = mysql_query($queryCuenta);
		if(!$rsCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowCuenta = mysql_fetch_assoc($rsCuenta);
		
		$saldoTem = $rowCuenta['saldo_tem'] - str_replace(',','.',$rowCheq['monto_cheque']);
		$diferido = $rowCuenta['Diferido'] - str_replace(',','.',$rowCheq['monto_cheque']);
	
		$updateCuenta = sprintf("UPDATE cuentas SET saldo_tem = %s, Diferido = %s WHERE idCuentas = %s",
			valTpDato($saldoTem, "real_inglesa"),
			valTpDato($diferido, "real_inglesa"), 
			valTpDato($rowChequera['id_cuenta'], "int"));		
		$rsUpdateCuenta = mysql_query($updateCuenta);
		if(!$rsUpdateCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnCancelar').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function anularCheque($formCheque, $borrarISLR = "NO"){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_generar_cheque.php?acc=3","eliminar")){ return $objResponse; }
	
	mysql_query("START TRANSACTION;");

	$queryCheque = sprintf("SELECT * FROM te_cheques WHERE id_cheque = %s AND estado_documento != 3",
		valTpDato($formCheque['hddIdChequeA'], "int"));
	$rsCheque = mysql_query($queryCheque);
	if (!$rsCheque) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCheque = mysql_fetch_assoc($rsCheque);
	if(mysql_num_rows($rsCheque) == 0){
		return $objResponse->alert("No puedes anular un cheque que ya ha sido conciliado");
	}

	$queryDatosChequera = sprintf("SELECT anulados, id_cuenta FROM te_chequeras WHERE id_chq = %s",
		valTpDato($rowCheque['id_chequera'], "int"));
	$rsDatosChequera = mysql_query($queryDatosChequera);
	if (!$rsDatosChequera) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDatosChequera = mysql_fetch_assoc($rsDatosChequera);

	$queryUpdateChequera = sprintf("UPDATE te_chequeras SET anulados = (anulados + 1) WHERE id_chq = %s",
		valTpDato($rowCheque['id_chequera'], "int"));
	$rsUpdateChequera = mysql_query($queryUpdateChequera);
	if (!$rsUpdateChequera) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

	$queryCuenta = sprintf("SELECT saldo_tem FROM cuentas WHERE idCuentas = %s",
		valTpDato($rowDatosChequera['id_cuenta'], "int"));
	$rsCuenta = mysql_query($queryCuenta);
	if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_assoc($rsCuenta);

	$saldoActual= $rowCuenta['saldo_tem'] + $rowCheque['monto_cheque'];
	$queryUpdateSaldo = sprintf("UPDATE cuentas SET saldo_tem = %s WHERE idCuentas = %s", 
		valTpDato($saldoActual, "real_inglesa"), 
		valTpDato($rowDatosChequera['id_cuenta'], "int"));
	$rsUpdateSaldo = mysql_query($queryUpdateSaldo);
	if(!$rsUpdateSaldo){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryEstadoCuenta = sprintf("UPDATE te_estado_cuenta SET 
		tipo_documento = 'CH ANULADO', 
		desincorporado = 0, 
		suma_resta = 1 
	WHERE id_documento = %s 
	AND tipo_documento = 'CH' 
	AND numero_documento = %s",
		valTpDato($formCheque['hddIdChequeA'], "int"),
		valTpDato($rowCheque['numero_cheque'], "int"));
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

	if ($rowCheque['id_factura'] == 0) {//sino tiene documento asignado (id de factura o id de nota de cargo) puede poseer propuesta de varios pagos
		$queryFacturasPropuesta = sprintf("SELECT 
			te_propuesta_pago_detalle.id_propuesta_pago,
			te_propuesta_pago_detalle.id_factura,
			te_propuesta_pago_detalle.monto_pagar,
			te_propuesta_pago_detalle.monto_retenido,
			te_propuesta_pago_detalle.sustraendo_retencion,
			te_propuesta_pago_detalle.porcentaje_retencion,
			te_propuesta_pago_detalle.tipo_documento
		FROM te_propuesta_pago
			INNER JOIN te_propuesta_pago_detalle ON (te_propuesta_pago.id_propuesta_pago = te_propuesta_pago_detalle.id_propuesta_pago) 
		WHERE te_propuesta_pago.id_cheque = %s",
			valTpDato($formCheque['hddIdChequeA'], "int"));
		$rsFacturasPropuesta = mysql_query($queryFacturasPropuesta);
		if(!$rsFacturasPropuesta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$tienePropuesta = mysql_num_rows($rsFacturasPropuesta);
              
		while ($rowFacturasPropuesta = mysql_fetch_assoc($rsFacturasPropuesta)){//si tiene alguna propuesta
			if($rowFacturasPropuesta['tipo_documento'] == 0){//Factura
				$queryFacturaSaldo = sprintf("SELECT saldo_factura, total_cuenta_pagar FROM cp_factura WHERE id_factura = %s",
					valTpDato($rowFacturasPropuesta['id_factura'], "int"));
				$rsFacturaSaldo  = mysql_query($queryFacturaSaldo);
				if (!$rsFacturaSaldo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$rowFacturaSaldo  = mysql_fetch_assoc($rsFacturaSaldo );
				
				if($borrarISLR == "SI"){
					$montoFactura = $rowFacturasPropuesta['monto_pagar'] + $rowFacturasPropuesta['monto_retenido'];
				}else{
					$montoFactura = $rowFacturasPropuesta['monto_pagar'];
				}
				
				$totalMontoFactura = $rowFacturaSaldo['saldo_factura']+$montoFactura;
				
				if($totalMontoFactura == $rowFacturaSaldo['total_cuenta_pagar']){
					$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
				}elseif($totalMontoFactura == 0){
					$cambioEstado = 1;
				}else{
					$cambioEstado = 2;
				}
				
				$queryUptadeFactura = sprintf("UPDATE cp_factura SET estatus_factura = %s, saldo_factura = %s 
				WHERE id_factura = %s",
					valTpDato($cambioEstado, "int"),
					valTpDato($totalMontoFactura, "real_inglesa"), 
					valTpDato($rowFacturasPropuesta['id_factura'], "int"));
				$rsUpdateFactura = mysql_query($queryUptadeFactura);
				if (!$rsUpdateFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

				$queryDeletePago = sprintf("UPDATE cp_pagos_documentos SET 
					estatus = NULL, 
					fecha_anulado = NOW(), 
					id_empleado_anulado = %s 
				WHERE id_documento_pago = %s 
				AND tipo_pago = 'CHEQUE' 
				AND tipo_documento_pago = 'FA' 
				AND id_documento = %s",
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($rowFacturasPropuesta['id_factura'], "int"), 
					valTpDato($formCheque['hddIdChequeA'], "int"));
				$rsDeletePago = mysql_query($queryDeletePago);
				if (!$rsDeletePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}else{//nota de cargo
				$queryNotaCargo = sprintf("SELECT saldo_notacargo, total_cuenta_pagar FROM cp_notadecargo WHERE id_notacargo = %s;",
					valTpDato($rowFacturasPropuesta['id_factura'], "int"));
				$rsNotaCargo  = mysql_query($queryNotaCargo);
				if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$rowNotaCargo  = mysql_fetch_assoc($rsNotaCargo);
				
				if($borrarISLR == "SI"){
					$montoNotaCargo = $rowFacturasPropuesta['monto_pagar'] + $rowFacturasPropuesta['monto_retenido'];
				}else{
					$montoNotaCargo = $rowFacturasPropuesta['monto_pagar'];
				}
				
				$totalMontoNotaCargo = $rowNotaCargo['saldo_notacargo']+$montoNotaCargo;
                                
				if($totalMontoNotaCargo == $rowNotaCargo['total_cuenta_pagar']){
					$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
				}elseif($totalMontoNotaCargo == 0){
					$cambioEstado = 1;
				}else{
					$cambioEstado = 2;
				}
				
				$queryUptadeNotaCargo = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = %s, saldo_notacargo = %s 
				WHERE id_notacargo = %s",
					valTpDato($cambioEstado, "int"),
					valTpDato($totalMontoNotaCargo, "real_inglesa"), 
					valTpDato($rowFacturasPropuesta['id_factura'], "int"));
				$rsUpdateNotaCargo = mysql_query($queryUptadeNotaCargo);
				if (!$rsUpdateNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }				
				
				$queryDeletePago = sprintf("UPDATE cp_pagos_documentos SET 
					estatus = NULL, 
					fecha_anulado = NOW(), 
					id_empleado_anulado = %s 
				WHERE id_documento_pago = %s 
				AND tipo_pago = 'CHEQUE' 
				AND tipo_documento_pago = 'ND' 
				AND id_documento = %s",
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($rowFacturasPropuesta['id_factura'], "int"), 
					valTpDato($formCheque['hddIdChequeA'], "int"));
				$rsDeletePago = mysql_query($queryDeletePago);
				if (!$rsDeletePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}

			$id_propuesta_pago = $rowFacturasPropuesta['id_propuesta_pago']; 
			
			///////////////////////////////////////Detalles Cheque Anulado		
			$queryDetalleCheque = sprintf("INSERT INTO te_cheques_anulados_detalle (id_cheque, id_factura, monto_pagar, sustraendo_retencion, porcentaje_retencion, monto_retenido, tipo_documento) 
			VALUES (%s, %s, %s, %s, %s, %s, %s)",
				valTpDato($formCheque['hddIdChequeA'], "int"),
				valTpDato($rowFacturasPropuesta['id_factura'], "int"),
				valTpDato($rowFacturasPropuesta['monto_pagar'], "real_inglesa"),
				valTpDato($rowFacturasPropuesta['sustraendo_retencion'], "real_inglesa"),
				valTpDato($rowFacturasPropuesta['porcentaje_retencion'], "real_inglesa"),
				valTpDato($rowFacturasPropuesta['monto_retenido'], "real_inglesa"),
				valTpDato($rowFacturasPropuesta['tipo_documento'], "int"));			
			$rsDetalleCheque = mysql_query($queryDetalleCheque);
			if (!$rsDetalleCheque) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		}                
		
		//borrar propuesta de pago tenga o no tenga
		$queryDeletePropuestaDetalle = sprintf("DELETE FROM te_propuesta_pago_detalle WHERE id_propuesta_pago = %s", 
			valTpDato($id_propuesta_pago, "int"));
		$rsDeletePropuestaDetalle = mysql_query($queryDeletePropuestaDetalle);
		if (!$rsDeletePropuestaDetalle) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		$queryDeletePropuesta = sprintf("DELETE FROM te_propuesta_pago WHERE id_propuesta_pago = %s", 
			valTpDato($id_propuesta_pago, "int"));
		$rsDeletePropuesta = mysql_query($queryDeletePropuesta);
		if (!$rsDeletePropuesta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}else{//si tiene documento cargado verificar si es nota o factura            
		if($rowCheque['tipo_documento'] == "0"){
			$queryFacturaSaldo = sprintf("SELECT saldo_factura, total_cuenta_pagar FROM cp_factura WHERE id_factura = %s",
				valTpDato($rowCheque['id_factura'], "int"));
			$rsFacturaSaldo  = mysql_query($queryFacturaSaldo);
			if (!$rsFacturaSaldo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowFacturaSaldo  = mysql_fetch_assoc($rsFacturaSaldo );

			$queryRetenido = sprintf("SELECT * FROM te_retencion_cheque WHERE id_factura = %s AND id_cheque = %s",
				valTpDato($rowCheque['id_factura'], "int"), 
				valTpDato($rowCheque['id_cheque'], "int"));
			$rsRetenido  = mysql_query($queryRetenido);
			if (!$rsRetenido) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowRetenido  = mysql_fetch_assoc($rsRetenido );
			
			if($borrarISLR == "SI"){
				$montoFactura = $rowCheque['monto_cheque'] + $rowRetenido['monto_retenido'];
			}else{
				$montoFactura = $rowCheque['monto_cheque'];
			}
			
			$totalMontoFactura = $rowFacturaSaldo['saldo_factura']+$montoFactura;
			
			if($totalMontoFactura == $rowFacturaSaldo['total_cuenta_pagar']){
				$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
			}elseif($totalMontoFactura == 0){
				$cambioEstado = 1;
			}else{
				$cambioEstado = 2;
			}

			$queryUptadeFactura = sprintf("UPDATE cp_factura SET estatus_factura = %s, saldo_factura = %s WHERE id_factura = %s",
				valTpDato($cambioEstado, "int"),
				valTpDato($totalMontoFactura, "real_inglesa"), 
				valTpDato($rowCheque['id_factura'], "int"));	
			$rsUpdateFactura = mysql_query($queryUptadeFactura);
			if (!$rsUpdateFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			$queryDeletePago = sprintf("UPDATE cp_pagos_documentos SET 
				estatus = NULL, 
				fecha_anulado = NOW(), 
				id_empleado_anulado = %s 
			WHERE id_documento_pago = %s 
			AND tipo_pago = 'CHEQUE' 
			AND tipo_documento_pago = 'FA' 
			AND id_documento = %s",
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowCheque['id_factura'], "int"),
				valTpDato($formCheque['hddIdChequeA'], "int"));
			$rsDeletePago = mysql_query($queryDeletePago);
			if (!$rsDeletePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }                        
		}else{                    
			$queryNotaCargo = sprintf("SELECT saldo_notacargo, total_cuenta_pagar FROM cp_notadecargo WHERE id_notacargo = %s",
				valTpDato($rowCheque['id_factura'], "int"));
			$rsNotaCargo  = mysql_query($queryNotaCargo);
			if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowNotaCargo  = mysql_fetch_assoc($rsNotaCargo);
			
			$queryRetenido = sprintf("SELECT * FROM te_retencion_cheque WHERE id_factura = %s AND id_cheque = %s",
				valTpDato($rowCheque['id_factura'], "int"), 
				valTpDato($rowCheque['id_cheque'], "int"));
			$rsRetenido  = mysql_query($queryRetenido);
			if (!$rsRetenido) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowRetenido  = mysql_fetch_assoc($rsRetenido );
			
			if($borrarISLR == "SI"){
				$montoNotaCargo= $rowCheque['monto_cheque'] + $rowRetenido['monto_retenido'];
			}else{
				$montoNotaCargo= $rowCheque['monto_cheque'];
			}
			
			$totalMontoNotaCargo= $rowNotaCargo['saldo_notacargo']+$montoNotaCargo;
			
			if($totalMontoNotaCargo == $rowNotaCargo['total_cuenta_pagar']){
				$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
			}elseif($totalMontoNotaCargo == 0){
				$cambioEstado = 1;
			}else{
				$cambioEstado = 2;
			}
			
			$queryUptadeNotaCargo = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = %s, saldo_notacargo = %s WHERE id_notacargo = %s",
				valTpDato($cambioEstado, "int"),
				valTpDato($totalMontoNotaCargo, "real_inglesa"),
				valTpDato($rowCheque['id_factura'], "int"));
			$rsUpdateNotaCargo = mysql_query($queryUptadeNotaCargo);
			if (!$rsUpdateNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			$queryDeletePago = sprintf("UPDATE cp_pagos_documentos SET 
				estatus = NULL, 
				fecha_anulado = NOW(), 
				id_empleado_anulado = %s 
			WHERE id_documento_pago = %s 
			AND tipo_pago = 'CHEQUE' 
			AND tipo_documento_pago = 'ND' 
			AND id_documento = %s",
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowCheque['id_factura'], "int"), 
				valTpDato($formCheque['hddIdChequeA'], "int"));
			$rsDeletePago = mysql_query($queryDeletePago);
			if (!$rsDeletePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		}
	}

	$queryChequeAnulado = sprintf("INSERT INTO te_cheques_anulados (numero_cheque, beneficiario_proveedor, id_beneficiario_proveedor, fecha_registro, fecha_cheque, concepto, observacion, monto_cheque, id_chequera, id_empresa, id_usuario, id_factura, comision, id_cheque, tipo_documento)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($rowCheque['numero_cheque'], "int"),
		valTpDato($rowCheque['beneficiario_proveedor'], "int"),
		valTpDato($rowCheque['id_beneficiario_proveedor'], "int"),
		valTpDato("NOW()", "campo"),
		valTpDato($rowCheque['fecha_registro'], "date"),
		valTpDato($rowCheque['concepto'], "text"),
		valTpDato($rowCheque['observacion'], "text"),
		valTpDato($rowCheque['monto_cheque'], "real_inglesa"),
		valTpDato($rowCheque['id_chequera'], "int"),
		valTpDato($rowCheque['id_empresa'], "int"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($rowCheque['id_factura'], "int"),
		valTpDato($formCheque['txtComision'], "real_inglesa"),
		valTpDato($formCheque['hddIdChequeA'], "int"),
		valTpDato($rowCheque['tipo_documento'], "int"));
	$rsChequeAnulado = mysql_query($queryChequeAnulado);
	if (!$rsChequeAnulado) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$idChequeAnulado = mysql_insert_id();


	////////////////////////DELETE//////////////////////////////////////
	//eliminar los pagos de impuestos
	if($borrarISLR == "SI"){
		$queryBorrarISLR = sprintf("
		UPDATE cp_pagos_documentos SET estatus = NULL, fecha_anulado = NOW(), id_empleado_anulado = %s WHERE 
		id_documento_pago IN (
		SELECT id_factura FROM te_retencion_cheque WHERE
								id_cheque = %s
								AND tipo_documento = 0 
								AND estado = 0 
		)  
		AND (tipo_documento_pago = 'FA' OR tipo_documento_pago = 'ND') 
		AND tipo_pago = 'ISLR'            
		AND numero_documento IN (
		SELECT id_retencion_cheque FROM te_retencion_cheque WHERE
								id_cheque = %s
								AND tipo_documento = 0
								AND estado = 0 
		) ",
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($formCheque['hddIdChequeA'], "int"),
			valTpDato($formCheque['hddIdChequeA'], "int"));		
		$rsBorrarISLR = mysql_query($queryBorrarISLR);
		if(!$rsBorrarISLR) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }					
	}
        
	if($borrarISLR == "SI"){
		$queryDeleteRetencion = sprintf("UPDATE te_retencion_cheque SET anulado = 1 WHERE id_cheque = %s AND estado = 0", 
			valTpDato($formCheque['hddIdChequeA'], "int"));
		$rsDeleteRetencion = mysql_query($queryDeleteRetencion);
		if (!$rsDeleteRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }        
	}

	$queryDeleteCheque = sprintf("DELETE FROM te_cheques WHERE id_cheque = %s", 
		valTpDato($formCheque['hddIdChequeA'], "int"));
	$rsDeleteCheque = mysql_query($queryDeleteCheque);
	if (!$rsDeleteCheque) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	

	mysql_query("COMMIT;");
        
	//Modifcar Ernesto
	if(function_exists("generarChequesAnuladoTe")){
	   generarChequesAnuladoTe($idChequeAnulado,"","");
	}
	//Modifcar Ernesto
        
	$objResponse->script("byId('btnCancelarAnular').click();
	byId('btnBuscar').click();");
	
	$objResponse->alert("Anulado Correctamente");
        
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
	
	$objResponse->script("xajax_cargaLstCuenta(xajax.getFormValues('frmCheque'));");
	$objResponse->script("byId('btnCancelarBanco').click();");
	
	return $objResponse;
}

function asignarBeneficiario($idBeneficiario){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = %s",
		valTpDato($idBeneficiario, "int"));
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdBeneficiario","value",$row['id_beneficiario']);
	$objResponse->assign("hddBeneficiario_O_Provedor","value","0");
	$objResponse->assign("txtNombreBeneficiario","value", utf8_encode($row['nombre_beneficiario']));
	$objResponse->assign("txtCiRifBeneficiario","value",$row['lci_rif']."-".$row['ci_rif_beneficiario']);

	$objResponse->script("xajax_asignarDetallesRetencion(".$row['idretencion'].")");
	$objResponse->script("byId('btnCancelarBeneficiariosProveedores').click();");
	
	return $objResponse;
}

function asignarDetallesCuenta($idChequera){
	$objResponse = new xajaxResponse();
	
	$queryDetalleCuenta = sprintf("SELECT nombreBanco, numeroCuentaCompania, saldo_tem FROM vw_te_cheques WHERE id_chequera = %s Limit 1",$idChequera);
	$rsDetalleCuenta = mysql_query($queryDetalleCuenta);
	if(!$rsDetalleCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDetalleCuenta = mysql_fetch_assoc($rsDetalleCuenta);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($rowDetalleCuenta['nombreBanco']));
	$objResponse->assign("tdLstCuenta","innerHTML"," <input type='text' id='lstCuenta' name='lstCuenta' readonly='readonly' value='".$rowDetalleCuenta['numeroCuentaCompania']."' size='25'/>");
	$objResponse->assign("txtSaldoCuenta","value",number_format($rowDetalleCuenta['saldo_tem'],'2','.',','));
	$objResponse->assign("hddSaldoCuenta","value",$rowDetalleCuenta['saldo_tem']);
	
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

function asignarEmpresa($idEmpresa, $accion){
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
	
	if ($accion == 0){
		$objResponse->assign("txtNombreBanco","value","");
		$objResponse->assign("hddIdBanco","value","-1");
		$objResponse->assign("txtSaldoCuenta","value","");
		$objResponse->assign("hddSaldoCuenta","value","");
		$objResponse->assign("hddIdChequera","value","");
		$objResponse->script("xajax_cargaLstCuenta(xajax.getFormValues('frmCheque'));");
	}
	
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
	}else{
		$objResponse->assign("hddIva","value","0");
		$objResponse->assign("hddBaseImponible","value","0");
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
	}else{
		$objResponse->assign("hddIva","value","0");
		$objResponse->assign("hddBaseImponible","value","0");
	}
	
	$objResponse->script("xajax_verificarRetencionISLR(".$idNotaCargo.",1);
	byId('btnCancelarFacturaNota').click();");
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $cargando){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdBeneficiario","value",$row['id_proveedor']);
	$objResponse->assign("hddBeneficiario_O_Provedor","value","1");
	$objResponse->assign("txtNombreBeneficiario","value",utf8_encode($row['nombre']));
	$objResponse->assign("txtCiRifBeneficiario","value",$row['lrif']."-".$row['rif']);
	
	$query2 = sprintf("SELECT reimpuesto FROM cp_prove_credito WHERE id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rs2 = mysql_query($query2);
	if(!$rs2) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row2 = mysql_fetch_assoc($rs2);
	
	if($cargando == "SI"){
		//si esta cargando no mostrar retencion
	}else{
		//retencion automatica configurada desde cuentas por pagar
		//$objResponse->script("xajax_asignarDetallesRetencion(".$row2['reimpuesto'].")");
	}
	
	$objResponse->script("byId('btnCancelarBeneficiariosProveedores').click();");
	
	return $objResponse;
}

function asignarProveedor2($idProveedor){//solo para listado
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

function buscarCliente($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",$valForm['txtCriterioBusqProveedor']."|".$valForm['buscarListado']);

	if ($valForm['buscarProv'] == "1") {		
		$objResponse->loadCommands(listaProveedor(0, "", "", $valBusq));
	} elseif($valForm['buscarProv'] == "2") {
		$objResponse->loadCommands(listaBeneficiarios(0, "", "", $valBusq));
	}
	
	return $objResponse;
}

function buscarDocumento($valForm,$idEmpresa,$idProveedor,$facturaNota) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$idEmpresa,
		$idProveedor,
		$valForm['lstModulo'],
		((count($valForm['cbxDiasVencidos']) > 0) ? implode(",",$valForm['cbxDiasVencidos']) : "-1"),
		$valForm['txtCriterioBuscarFacturaNota']);
		
	if ($valForm['buscarTipoDcto'] == "2") { // FACTURA
		$objResponse->loadCommands(listaFactura(0,'','', $valBusq));
	} else { // NOTA DE CARGO		
		$objResponse->loadCommands(listaNotaCargo(0,'','', $valBusq));
	}
	
	return $objResponse;
}

function buscarCheque($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['lstEstado'],
		$valForm['txtCriterio'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['txtIdProv'],
		$valForm['txtConceptoBuscar']);
	//ordenamiento en historico siempre mostrar primero el ultimo
	if($_GET['acc'] == 3){//3 es historico devolucion            
		$objResponse->loadCommands(listaCheques(0,"fecha_registro","DESC",$valBusq));
	}else{
		$objResponse->loadCommands(listaCheques(0,"numero_cheque","ASC",$valBusq));
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

function cargarChequera($idCuenta){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_chequeras WHERE id_cuenta = %s AND disponibles > 0",
		valTpDato($idCuenta, "int"));
	$rs = mysql_query($query);
	$row = mysql_fetch_assoc($rs);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if (mysql_num_rows($rs) == 0){		
		$objResponse->script("byId('btnBuscarCliente').disabled = true;							 
			byId('txtConcepto').disabled = true;
			byId('txtComentario').disabled = true;
			byId('txtMonto').disabled = true;
			byId('btnAceptar').disabled = true;");
		if($idCuenta > 0){
			$objResponse->alert("La Cuenta Seleccionada no tiene Chequeras Disponibles");
		}
	}else{		
		$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat));
		$objResponse->assign("txtMonto","value","");
		$objResponse->script("byId('btnBuscarCliente').disabled = false;							 
			byId('txtConcepto').disabled = false;
			byId('txtComentario').disabled = false;
			byId('txtMonto').disabled = false;
			byId('btnAceptar').disabled = false;");						  
	}
	
	return $objResponse;
}

function cargaSaldoCuenta($idCuenta){
	$objResponse = new xajaxResponse();

	$queryCuenta = sprintf("SELECT * FROM cuentas WHERE idCuentas = %s",
		valTpDato($idCuenta, "int"));
	$rsCuenta = mysql_query($queryCuenta);
	if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_assoc($rsCuenta);
	
	$queryChequera = sprintf("SELECT id_chq, ultimo_nro_chq FROM te_chequeras WHERE id_cuenta = %s AND disponibles > 0", 
		valTpDato($idCuenta, "int"));
	$rsChequera = mysql_query($queryChequera);
	if(!$rsChequera) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowChequera = mysql_fetch_assoc($rsChequera);
	
	$diferido = $rowCuenta['Diferido'];	
	$objResponse->assign("txtSaldoCuenta","value",number_format($rowCuenta['saldo_tem'],'2','.',','));
	$objResponse->assign("hddSaldoCuenta","value",number_format($rowCuenta['saldo_tem'],'2',',',''));
	$objResponse->assign("hddIdChequera","value",$rowChequera['id_chq']);
	$objResponse->assign("txtDiferido","value",number_format($diferido,'2','.',''));
	$objResponse->assign("hddDiferido","value",number_format($diferido,'2','.',''));	
	
	if($rowChequera['ultimo_nro_chq'] == NULL){//cuando no tiene chequera
		$objResponse->assign("numCheque","value","");
	}else{
		if(configChequeManual()){//NRO CHEQUE MANUAL
			$objResponse->assign("numCheque","value","");
			$objResponse->script("byId('numCheque').readOnly = false;
								  byId('spanChequeManual').style.display = '';
								  byId('numCheque').className = 'inputHabilitado';");
		}else{//NRO CHEQUE AUTOMATICO
			$objResponse->assign("numCheque","value",$rowChequera['ultimo_nro_chq']+1);
			$objResponse->script("byId('numCheque').readOnly = true;
								  byId('spanChequeManual').style.display = 'none';
								  byId('numCheque').className = '';");
		}
	}
	return $objResponse;
}

function cargaLstCuenta($valForm){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}else{
		$disabled = "";
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idBanco = %s AND id_empresa = %s",
			valTpDato($valForm['hddIdBanco'], "int"),
			valTpDato($valForm['hddIdEmpresa'], "int"));
	}
	
	$query = sprintf("SELECT * FROM cuentas %s", $sqlBusq);
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstCuenta\" name=\"lstCuenta\" ".$disabled." class=\"inputHabilitado\" onchange=\"xajax_cargaSaldoCuenta(this.value); xajax_cargarChequera(this.value);\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)){
		$html .= "<option value=\"".$row['idCuentas']."\">".$row['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdLstCuenta","innerHTML",$html);
		
	return $objResponse;
}

function cargaLstEstado(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_estados_principales ORDER BY id_estados_principales");
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstEstado\" name=\"lstEstado\" onChange=\"byId('btnBuscar').click();\" class=\"inputHabilitado\">";
	$html .="<option selected=\"selected\" value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_estados_principales']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdLstEstado","innerHTML",$html);
	
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

function formAnularCheque($idCheque){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_cheques WHERE id_cheque = %s LIMIT 1",
		valTpDato($idCheque, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNumCheque", "value", $row['numero_cheque']);
	$objResponse->assign("hddIdChequeA", "value", $row['id_cheque']);
	
	return $objResponse;
}

function formCheque($idCheque, $accion){
	$objResponse = new xajaxResponse();
	
	if ($accion == "nuevo") {
		$objResponse->script("
			byId('txtNombreEmpresa').className = 'inputInicial';
			byId('txtNombreBanco').className = 'inputInicial';		
			byId('txtSaldoCuenta').className = 'inputInicial';
			byId('txtIdBeneficiario').className = 'inputInicial';
			byId('txtCiRifBeneficiario').className = 'inputInicial';
			byId('txtNombreBeneficiario').className = 'inputInicial';
			byId('txtFechaRegistro').className = 'inputInicial';
			byId('txtFechaLiberacion').className = 'inputHabilitado';
			byId('txtMonto').className = 'inputHabilitado';		
			byId('txtDescripcionFactura').innerHTML = '';
			byId('selRetencionISLR').disabled = 'disabled';		
			byId('btnAceptar').style.display = '';
			byId('btnActualizar').style.display = 'none';
			byId('trChequeEntregado').style.display = 'none';
			byId('trSaldoCuenta').style.display = '';
			byId('hddPorcentajeRetencion').value = '0';
			byId('tdTextoRetencionISLR').style.display = 'none';
			byId('tdMontoRetencionISLR').style.display = 'none';
			byId('txtMontoRetencionISLR').value = 0;			
			byId('tdTxtSaldoFactura').style.display = '';
			byId('tdSaldoFactura').style.display = '';
			$('#txtFechaRegistro').next('.JsDatePickBox').remove();");
		
		$objResponse->loadCommands(cargaLstCuenta());
		$objResponse->loadCommands(asignarEmpresa($_SESSION["idEmpresaUsuarioSysGts"],0));
		
		return $objResponse;
	}
	
	$queryCheque = sprintf("SELECT * FROM te_cheques WHERE id_cheque = %s", 
		valTpDato($idCheque, "int"));
	$rsCheque = mysql_query($queryCheque);
	$rowCheque = mysql_fetch_assoc($rsCheque);
	
	$objResponse->script("xajax_asignarEmpresa(".$rowCheque['id_empresa'].",1)");
	$objResponse->script("xajax_asignarDetallesCuenta(".$rowCheque['id_chequera'].")");
	
	if($rowCheque['beneficiario_proveedor'] == 0){
		$objResponse->script("xajax_asignarBeneficiario(".$rowCheque['id_beneficiario_proveedor'].")");
	}else{
		$objResponse->script("xajax_asignarProveedor(".$rowCheque['id_beneficiario_proveedor'].", 'SI')");
	}
	
	$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat,strtotime($rowCheque['fecha_registro'])));
	$objResponse->assign("numCheque","value",$rowCheque['numero_cheque']);
	$objResponse->assign("txtFechaLiberacion","value",date(spanDateFormat,strtotime($rowCheque['fecha_liberacion'])));
	$objResponse->assign("hddIdCheque","value",$rowCheque['id_cheque']);
	$objResponse->assign("txtConcepto","value",utf8_encode($rowCheque['concepto']));
	$objResponse->assign("txtComentario","value",utf8_encode($rowCheque['observacion']));
	$objResponse->assign("txtMonto","value",number_format($rowCheque['monto_cheque'],'2',',','.'));
	
	if($rowCheque['id_factura']){            
		if($rowCheque['tipo_documento'] == 0){ //factura            
			$queryFactura = sprintf("SELECT 
				numero_factura_proveedor, 
				fecha_origen, 
				fecha_vencimiento, 
				observacion_factura 
			FROM cp_factura 
			WHERE id_factura = %s",
				valTpDato($rowCheque['id_factura'], "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }			
			$rowFactura = mysql_fetch_assoc($rsFactura);
			
			$objResponse->assign("txtIdFactura","value",$rowCheque['id_factura']);
			$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
			$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_origen'])));
			$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_vencimiento'])));
			$objResponse->assign("txtDescripcionFactura","innerHTML",utf8_encode($rowFactura['observacion_factura']));
			$objResponse->assign("txtSaldoFactura","value",number_format($rowCheque['monto_cheque'],'2',',','.'));
			$objResponse->assign("tdFacturaNota","innerHTML","FACTURA");
                
		}else{ //nota de cargo
			$queryNota = sprintf("SELECT 
				numero_notacargo, 
				fecha_origen_notacargo, 
				fecha_vencimiento_notacargo, 
				observacion_notacargo 
			FROM cp_notadecargo 
			WHERE id_notacargo = %s",
				valTpDato($rowCheque['id_factura'], "int"));
			$rsNota = mysql_query($queryNota);
			if (!$rsNota) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }			
			$rowNota = mysql_fetch_assoc($rsNota);
				
			$objResponse->assign("txtIdFactura","value",$rowCheque['id_factura']);
			$objResponse->assign("txtNumeroFactura","value",utf8_encode($rowNota['numero_notacargo']));
			$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat,strtotime($rowNota['fecha_origen_notacargo'])));
			$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($rowNota['fecha_vencimiento_notacargo'])));
			$objResponse->assign("txtDescripcionFactura","innerHTML",utf8_encode($rowNota['observacion_notacargo']));
			$objResponse->assign("tdFacturaNota","innerHTML","NOTA DE CARGO");
		}

		$objResponse->script("byId('tdTxtSaldoFactura').style.display = 'none';
			byId('tdSaldoFactura').style.display = 'none';");
	}else{// sino posee documento
		$objResponse->assign("txtIdFactura","value","");
		$objResponse->assign("txtNumeroFactura","value","");
		$objResponse->assign("txtFechaRegistroFactura","value","");
		$objResponse->assign("txtFechaVencimientoFactura","value","");
		$objResponse->assign("txtDescripcionFactura","innerHTML","");
		$objResponse->assign("txtSaldoFactura","value","");
		
		$queryTienePropuesta = sprintf("SELECT id_propuesta_pago FROM te_propuesta_pago WHERE id_cheque = %s LIMIT 1",
			valTpDato($idCheque, "int"));
		$rsTienePropuesta = mysql_query($queryTienePropuesta);
		if (!$rsTienePropuesta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		if(mysql_num_rows($rsTienePropuesta)){
			$rowTienePropuesta = mysql_fetch_assoc($rsTienePropuesta);
			$imagen = sprintf("<a class=\"modalImg\" id=\"aVerPropuesta\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblListaPropuestaPago', '%s');\"><img src=\"../img/iconos/ico_view.png\" style=\"vertical-align: top;\" class=\"puntero\"></a>",
				$rowTienePropuesta['id_propuesta_pago']);
			$objResponse->assign("tdFacturaNota","innerHTML","PROPUESTA DE PAGO ".$imagen);
		}else{
			$objResponse->assign("tdFacturaNota","innerHTML","SIN DOCUMENTO");
		}
	}
	
	if ($rowCheque['entregado']){
		$checked = "checked";
		$disabled = "disabled";
	}else{
		$checked = false;
		$disabled = false;
	}
		
	if($accion == "ver"){
		$objResponse->script("byId('btnActualizar').style.display = 'none';");	
	}else if($accion == "editar"){
		$objResponse->script("byId('btnActualizar').style.display = '';
			byId('btnActualizar').disabled = '".$disabled."';");
	}
	
	$objResponse->script("byId('btnAceptar').style.display = 'none';
		byId('trChequeEntregado').style.display = '';
		byId('cbxChequeEntregado').checked = '".$checked."';
		byId('trSaldoCuenta').style.display = 'none';");
	
	return $objResponse;
}

function formEditarFechaCheque($idCheque){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT id_cheque, numero_cheque, fecha_registro FROM te_cheques WHERE id_cheque = %s",
		valTpDato($idCheque, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCheque = mysql_fetch_assoc($rs);
	
	$query = sprintf("SELECT comprobant, fecha, documento FROM ".DBASE_CONTAB.".enviadosacontabilidad
	WHERE dt = '05'
	AND ct = '14'
	AND cc = '05'
	AND documento REGEXP '^[0-9]+$'
	AND idobject = %s",
		valTpDato($idCheque, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEnviadoContabilidad = mysql_fetch_assoc($rs);
	$cantidadEnviado = mysql_num_rows($rs);
	
	if($cantidadEnviado > 1){
		$objResponse->script("byId('btnCancelarEditarFechaCheque').click();");
		return $objResponse->alert("No se puede cargar porque hay mas de un documento con el mismo Id en contabilidad");
	}
	
	$query = sprintf("SELECT * FROM ".DBASE_CONTAB.".movimiendif
	WHERE DT = '05'
	AND CT = '14'
	AND cc = '05'
	AND comprobant = %s
	AND fecha = %s
	AND documento = %s",
		valTpDato($rowEnviadoContabilidad['comprobant'], "int"),
		valTpDato($rowEnviadoContabilidad['fecha'], "date"),
		valTpDato($rowEnviadoContabilidad['documento'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if(mysql_num_rows($rs) > 2){
		$objResponse->script("byId('btnCancelarEditarFechaCheque').click();");
		return $objResponse->alert("No se puede cargar porque el comprobante tiene asientos de mas con el mismo numero de documento");
	}	
	
	$enviado = ($cantidadEnviado) ? 'SI' : 'NO';
	$fechaContabilidad = ($rowEnviadoContabilidad['fecha']) ? date(spanDateFormat,strtotime($rowEnviadoContabilidad['fecha'])) : '';
	
	$objResponse->assign("hddIdChequeEditarFecha","value",$rowCheque['id_cheque']);
	$objResponse->assign("txtNumeroChequeEditar","value",$rowCheque['numero_cheque']);
	$objResponse->assign("txtFechaChequeRegistrado","value",date(spanDateFormat,strtotime($rowCheque['fecha_registro'])));
	$objResponse->assign("txtEnviadoContabilidad","value",$enviado);
	$objResponse->assign("txtNumeroComprobanteContabilidad","value",$rowEnviadoContabilidad['comprobant']);
	$objResponse->assign("txtFechaComprobanteContabilidad","value",$fechaContabilidad);
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo, $idCheque) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	$objResponse->assign("hddIdChequeEditarFechaPermiso","value",$idCheque);
	
	return $objResponse;
}

function guardarCheque($valForm){
	$objResponse = new xajaxResponse();
	
	if($valForm['txtIdFactura'] == ''){//sino se envio id de factura o nota de cargo
		errorGuardarDcto($objResponse);
		return $objResponse->alert("No se pueden generar cheques sin un documento asociado FA o ND");
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
		if ($valForm['hddTipoDocumento'] == 0) {//FACT
			$query = sprintf("SELECT * FROM cp_factura
			WHERE %s < fecha_origen AND id_factura = %s",
				valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
				valTpDato($valForm['txtIdFactura'], "int"));
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
				valTpDato($valForm['txtIdFactura'], "int"));
			$rs = mysql_query($query);
			if (!$rs){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			if (mysql_num_rows($rs)) {
				errorGuardarDcto($objResponse);
				return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha del documento (ND)");
			}
		}
	}
	
	$query = sprintf("SELECT 
		te_chequeras.ultimo_nro_chq,
		te_chequeras.id_chq,
		te_chequeras.disponibles,
		cuentas.numeroCuentaCompania,
		bancos.nombreBanco
	FROM te_chequeras 
		INNER JOIN cuentas ON (te_chequeras.id_cuenta = cuentas.idCuentas)
		INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
	WHERE id_chq = %s AND disponibles > 0",
		valTpDato($valForm['hddIdChequera'], "int"));
	$rs = mysql_query($query);
	if(!$rs) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	$chequesDisponible = mysql_num_rows($rs);
	
	if($chequesDisponible == 0){
		errorGuardarDcto($objResponse);
		return $objResponse->alert("La chequera Nro: ".$row['id_chq']." no posee cheques disponible: ".$row['disponibles'].""); 
	}
	
	$queryFolioCheque = "SELECT numero_actual FROM te_folios WHERE id_folios = 4";
	$rsFolioCheque = mysql_query($queryFolioCheque);
	if (!$rsFolioCheque) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowFolioCheque = mysql_fetch_assoc($rsFolioCheque);
	
	$updateFolioCheque = "UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 4;";
	$rsUpdateFolioCheque = mysql_query($updateFolioCheque);	
	if(!$rsUpdateFolioCheque){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if(configChequeManual()){//NRO CHEQUE MANUAL
		$numeroChequeGenerado = trim($valForm["numCheque"]);
		if($numeroChequeGenerado == ""){
			errorGuardarDcto($objResponse); return $objResponse->alert("Debe asignar Nro de Cheque");
		}
	}else{//NRO CHEQUE AUTOMATICO
		$numeroChequeGenerado = $row['ultimo_nro_chq']+1;
	}
	        
	$queryCheque = sprintf("INSERT INTO te_cheques (numero_cheque, folio_tesoreria, beneficiario_proveedor, id_beneficiario_proveedor, fecha_registro, fecha_liberacion, concepto, observacion, monto_cheque, id_chequera, estado_documento, fecha_aplicacion, id_empresa, desincorporado, id_usuario, id_factura, tipo_documento)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($numeroChequeGenerado, "int"),
		valTpDato($rowFolioCheque['numero_actual'], "int"),
		valTpDato($valForm['hddBeneficiario_O_Provedor'], "int"),
		valTpDato($valForm['txtIdBeneficiario'], "int"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaLiberacion'])), "date"),
		valTpDato($valForm['txtConcepto'], "text"),
		valTpDato($valForm['txtComentario'], "text"),
		valTpDato($valForm['txtMonto'], "real_inglesa"),
		valTpDato($valForm['hddIdChequera'], "int"),
		valTpDato(2, "int"),//1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
		valTpDato($valForm['hddIdEmpresa'], "int"),
		valTpDato(1, "int"),//0 = desincorporado, 1 = normal
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($valForm['txtIdFactura'], "int"),
		valTpDato($valForm['hddTipoDocumento'], "int"));	
	mysql_query("SET NAMES 'utf8'");
	$rsCheque = mysql_query($queryCheque);
	if (!$rsCheque) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$idCheque = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	if ($valForm['hddTipoDocumento'] == 0) {
		$tipoDocumento = "FA";
	} else {
		$tipoDocumento = "ND";
	}
	
	$queryCpPago = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($valForm['txtIdFactura'], "int"),
		valTpDato($tipoDocumento, "text"),
		valTpDato('Cheque', "text"),
		valTpDato($idCheque, "int"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
		valTpDato($numeroChequeGenerado, "int"),
		valTpDato('-', "text"),
		valTpDato($row['nombreBanco'], "text"),
		valTpDato('-', "text"),
		valTpDato($row['numeroCuentaCompania'], "text"),
		valTpDato($valForm['txtMonto'], "real_inglesa"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$consultaCpPago = mysql_query($queryCpPago);		
	if (!$consultaCpPago){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }		
	mysql_query("SET NAMES 'latin1';");
	
	$queryChequera = sprintf("SELECT ultimo_nro_chq, disponibles, id_cuenta FROM te_chequeras WHERE id_chq = %s",
		valTpDato($valForm['hddIdChequera'], "int"));	
	$rsChequera = mysql_query($queryChequera);
	if(!$rsChequera) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowChequera = mysql_fetch_assoc($rsChequera);
	
	$updateChequera = sprintf("UPDATE te_chequeras SET ultimo_nro_chq = %s, disponibles = (disponibles - 1) WHERE id_chq = %s",
		valTpDato($numeroChequeGenerado, "int"),
		valTpDato($valForm['hddIdChequera'], "int"));								
	$rsUpdateChequera = mysql_query($updateChequera);
	if(!$rsUpdateChequera) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$queryCuenta = sprintf("SELECT Diferido FROM cuentas WHERE idCuentas = %s",
		valTpDato($rowChequera['id_cuenta'], "int"));
	$rsCuenta = mysql_query($queryCuenta);
	if(!$rsCuenta) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_assoc($rsCuenta);

	$diferido = $rowCuenta['Diferido'] + str_replace(',','.',$valForm['txtMonto']);
	$updateCuenta = sprintf("UPDATE cuentas SET Diferido = %s WHERE idCuentas = %s",
		valTpDato($diferido, "real_inglesa"),
		valTpDato($rowChequera['id_cuenta'], "int"));
	$rsUpdateCuenta = mysql_query($updateCuenta);
	if(!$rsUpdateCuenta) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

	$queryEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato('CH', "text"),
		valTpDato($idCheque, "int"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])).date(" H:i:s"), "date"),
		valTpDato($rowChequera['id_cuenta'], "int"),
		valTpDato($valForm['hddIdEmpresa'], "int"),
		valTpDato($valForm['txtMonto'], "real_inglesa"),
		valTpDato(0, "int"),//0 = resta, 1 = suma
		valTpDato($numeroChequeGenerado, "int"),
		valTpDato(1, "int"),//0 = desincorporado, 1 = normal
		valTpDato($valForm['txtComentario'], "text"),
		valTpDato(2, "int"));//1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
	mysql_query("SET NAMES 'utf8'");
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	mysql_query("SET NAMES 'latin1';");
	
	$saldoValidarFactura = round($valForm['txtSaldoFactura'] - ($valForm['txtMontoRetencionISLR'] + $valForm['txtMonto']),2);
	
	//VERIFICAR QUE EL MONTO A PAGAR NO SEA SUPERIOR AL SALDO Y TAMPOCO NEGATIVO EL SALDO
	if($valForm['hddTipoDocumento'] == 0){//FACT                          
		if($saldoValidarFactura < 0) { errorGuardarDcto($objResponse); return $objResponse->alert("El saldo de la factura no puede quedar en negativo: ".$saldoValidarFactura); }
	}else{//NOTA
		if($saldoValidarFactura < 0) { errorGuardarDcto($objResponse); return $objResponse->alert("El saldo de la nota de cargo no puede quedar en negativo: ".$saldoValidarFactura); }
	}
		
	if($valForm['txtMontoRetencionISLR'] != 0){
		if($valForm['selRetencionISLR'] == "1" || $valForm['selRetencionISLR'] == ""){//si hay monto pero el select esta mal
			errorGuardarDcto($objResponse);
			return $objResponse->alert("Existe monto de retencion pero no se ha seleccionado retencion Cod: ".$valForm['selRetencionISLR']);
		}
		$queryRetencion = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_cheque, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo, fecha_registro)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($valForm['txtIdFactura'], "int"), 
			valTpDato($idCheque, "int"), 
			valTpDato($valForm['selRetencionISLR'], "int"), 
			valTpDato($valForm['hddBaseImponible'], "real_inglesa"), 
			valTpDato($valForm['hddSustraendoRetencion'], "real_inglesa"), 
			valTpDato($valForm['hddPorcentajeRetencion'], "real_inglesa"), 
			valTpDato($valForm['txtMontoRetencionISLR'], "real_inglesa"),
			valTpDato($valForm['hddCodigoRetencion'], "text"), 
			valTpDato($valForm['hddTipoDocumento'], "int"),
			valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"));
		$rsRetencion = mysql_query($queryRetencion);
		if (!$rsRetencion){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$idRetencion = mysql_insert_id();
				
		$queryCpPagoISLR = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($valForm['txtIdFactura'], "int"),
			valTpDato($tipoDocumento, "text"),
			valTpDato('ISLR', "text"),
			valTpDato($idRetencion, "int"),
			valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
			valTpDato($idRetencion, "int"),
			valTpDato('-', "text"),
			valTpDato($row['nombreBanco'], "text"),
			valTpDato('-', "text"),
			valTpDato($row['numeroCuentaCompania'], "text"),
			valTpDato($valForm['txtMontoRetencionISLR'], "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8'");		
		$consultaCpPagoISLR = mysql_query($queryCpPagoISLR);		
		if (!$consultaCpPagoISLR){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");		
	}

	if ($saldoValidarFactura == 0){
		$estatusFactura = "1";
	}else{
		$estatusFactura = "2";
	}
		
	if($valForm['hddTipoDocumento']==0){
		$queryUptadeFactura = sprintf("UPDATE cp_factura SET estatus_factura = %s, saldo_factura = %s WHERE id_factura = %s",
			valTpDato($estatusFactura, "int"), 
			valTpDato($saldoValidarFactura, "real_inglesa"), 
			valTpDato($valForm['txtIdFactura'], "int"));		
		$rsUpdateFactura = mysql_query($queryUptadeFactura);
		if (!$rsUpdateFactura){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
	}else{
		$queryFacturaActualizaSaldo = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = %s, saldo_notacargo = %s WHERE id_notacargo = %s", 
			valTpDato($estatusFactura, "int"), 
			valTpDato($saldoValidarFactura, "real_inglesa"), 
			valTpDato($valForm['txtIdFactura'], "int"));
		$rsFacturaActualizaSaldo = mysql_query($queryFacturaActualizaSaldo);
		if(!$rsFacturaActualizaSaldo) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	//Modifcar Ernesto
	if(function_exists("generarChequesTe")){
	   generarChequesTe($idCheque,"","");
	}
	//Modifcar Ernesto
	
	$objResponse->script("byId('btnCancelar').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function guardarEditarFechaCheque($frmEditarFechaCheque){
	$objResponse = new xajaxResponse();
	
	if ($frmEditarFechaCheque['txtFechaChequeRegistrado'] == $frmEditarFechaCheque['txtFechaChequeEditar']) {
		return $objResponse->alert("La nueva fecha no debe ser la misma que la actual");
	}
	
	mysql_query("START TRANSACTION");
	
	//VERIFICAR QUE NO SEA MENOR A LA FECHA DE PROCESO DE CONTABILIDAD
	$query = sprintf("SELECT * FROM ".DBASE_CONTAB.".parametros
	WHERE %s < fec_proceso",
		valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])), "date"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if (mysql_num_rows($rs)) {
		return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha de proceso de contabilidad");
	}
	
	//VERIFICAR QUE NO SEA MENOR A LA FECHA DEL DOCUMENTO
	$query = sprintf("SELECT id_factura, tipo_documento FROM te_cheques WHERE id_cheque = %s",
		valTpDato($frmEditarFechaCheque['hddIdChequeEditarFecha'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	$rowCheque = mysql_fetch_assoc($rs);
	
	if($rowCheque['id_factura'] > 0){//cheque individual
		if ($rowCheque['tipo_documento'] == 0) {//FACT
			$query = sprintf("SELECT * FROM cp_factura
			WHERE %s < fecha_origen AND id_factura = %s",
				valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])), "date"),
				valTpDato($rowCheque['id_factura'], "int"));
			$rs = mysql_query($query);
			if (!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			if (mysql_num_rows($rs)) { return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha del documento (FA)"); }
		} else {//NOTA
			$query = sprintf("SELECT * FROM cp_notadecargo
			WHERE %s < fecha_origen_notacargo AND id_notacargo = %s",
				valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])), "date"),
				valTpDato($rowCheque['id_factura'], "int"));
			$rs = mysql_query($query);
			if (!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			if (mysql_num_rows($rs)) { return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha del documento (ND)"); }
		}
	} else {//cheque por propuesta da pago		
		$queryDetProp = sprintf("SELECT prop_det.id_factura, prop_det.tipo_documento 
			FROM te_propuesta_pago prop
				INNER JOIN te_propuesta_pago_detalle prop_det ON (prop.id_propuesta_pago = prop_det.id_propuesta_pago)
			WHERE prop.id_cheque = %s",
			valTpDato($frmEditarFechaCheque['hddIdChequeEditarFecha'], "int"));
		$rsDetProp = mysql_query($queryDetProp);
		if (!$rsDetProp){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		 
		while($rowDetProp = mysql_fetch_assoc($rsDetProp)){
			if ($rowDetProp['tipo_documento'] == 0) {//FACT
				$query = sprintf("SELECT * FROM cp_factura
				WHERE %s < fecha_origen AND id_factura = %s",
					valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])), "date"),
					valTpDato($rowDetProp['id_factura'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				if (mysql_num_rows($rs)) { return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha del documento (FA)"); }
			} else {//NOTA
				$query = sprintf("SELECT * FROM cp_notadecargo
				WHERE %s < fecha_origen_notacargo AND id_notacargo = %s",
					valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])), "date"),
					valTpDato($rowDetProp['id_factura'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				if (mysql_num_rows($rs)) { return $objResponse->alert("La fecha seleccionada no puede ser menor a la fecha del documento (ND)"); }
			}
		}
	}
	
	//CAMBIAR FECHAS DE DOCUMENTOS Y MOVIMIENTOS
	$query = sprintf("UPDATE te_cheques SET fecha_registro = %s, fecha_aplicacion = %s WHERE id_cheque = %s",
		valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])), "date"),
		valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])), "date"),
		valTpDato($frmEditarFechaCheque['hddIdChequeEditarFecha'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$query = sprintf("UPDATE te_estado_cuenta SET fecha_registro = %s WHERE id_documento = %s AND tipo_documento = 'CH'",
		valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])).date(" H:i:s"), "date"),
		valTpDato($frmEditarFechaCheque['hddIdChequeEditarFecha'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$query = sprintf("UPDATE cp_pagos_documentos SET fecha_pago = %s WHERE id_documento = %s AND tipo_pago = 'Cheque'",
		valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaChequeEditar'])), "date"),
		valTpDato($frmEditarFechaCheque['hddIdChequeEditarFecha'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	//BORRAR DE ENVIADOS A CONTABILIDAD
	$query = sprintf("DELETE FROM ".DBASE_CONTAB.".enviadosacontabilidad
	WHERE dt = '05'
	AND ct = '14'
	AND cc = '05'
	AND documento = %s
	AND idobject = %s",
		valTpDato($frmEditarFechaCheque['txtNumeroChequeEditar'], "int"),
		valTpDato($frmEditarFechaCheque['hddIdChequeEditarFecha'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	//BORRAR ASIENTOS
	$query = sprintf("DELETE FROM ".DBASE_CONTAB.".movimiendif
	WHERE DT = '05'
	AND CT = '14'
	AND cc = '05'
	AND comprobant = %s
	AND fecha = %s
	AND documento = %s",
		valTpDato($frmEditarFechaCheque['txtNumeroComprobanteContabilidad'], "int"),
		valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaComprobanteContabilidad'])), "date"),
		valTpDato($frmEditarFechaCheque['txtNumeroChequeEditar'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	
	//SI EL COMPROBANTE QUEDO VACIO ELIMINARLO
	$query = sprintf("DELETE enc
	FROM ".DBASE_CONTAB.".enc_dif enc
		LEFT JOIN ".DBASE_CONTAB.".movimiendif det ON (enc.comprobant = det.comprobant AND enc.fecha = det.fecha)
	WHERE enc.comprobant = %s
	AND enc.fecha = %s
	AND det.comprobant IS NULL",
		valTpDato($frmEditarFechaCheque['txtNumeroComprobanteContabilidad'], "int"),
		valTpDato(date("Y-m-d",strtotime($frmEditarFechaCheque['txtFechaComprobanteContabilidad'])), "date"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT");
	
	$objResponse->alert("Fecha cambiada correctamente");
	$objResponse->script("byId('btnCancelarEditarFechaCheque').click();
	byId('btnBuscar').click();");
	
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

function listaBeneficiarios($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	//$valCadBusq[0] criterio
	//$valCadBusq[1] == "1" si es para buscar en el listado        
	$buscarListado = $valCadBusq[1];
	
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
		
		if($buscarListado == "1"){
			$onclickAsignar = "onclick=\"xajax_asignarBeneficiario2('".$row['id']."');\"";//busqueda en listado
		}else{
			$onclickAsignar = "onclick=\"xajax_asignarBeneficiario('".$row['id']."');\"";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" ".$onclickAsignar." title=\"Seleccionar Beneficiario\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
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

function listaFactura($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s OR cxp_fact.id_empresa IN ((SELECT id_empresa_reg 
													FROM vw_iv_empresas_sucursales 
													WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))
		AND cxp_fact.id_proveedor = %s 
		AND cxp_fact.estatus_factura <> 1 
		AND cxp_fact.id_factura NOT IN (SELECT te_propuesta_pago_detalle.id_factura 
											FROM te_propuesta_pago_detalle 
											INNER JOIN te_propuesta_pago ON te_propuesta_pago_detalle.id_propuesta_pago = te_propuesta_pago.id_propuesta_pago
											WHERE te_propuesta_pago_detalle.tipo_documento <> 1 
											AND te_propuesta_pago.estatus_propuesta = 0
											
											UNION ALL
											
											SELECT te_propuesta_pago_detalle_transferencia.id_factura 
											FROM te_propuesta_pago_detalle_transferencia 
											INNER JOIN te_propuesta_pago_transferencia ON te_propuesta_pago_detalle_transferencia. id_propuesta_pago = te_propuesta_pago_transferencia.id_propuesta_pago
											WHERE te_propuesta_pago_detalle_transferencia.tipo_documento <> 1
											AND te_propuesta_pago_transferencia.estatus_propuesta = 0
											)",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[1],"int"));  
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde1",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde2",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde3",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("masDe",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_fact.numero_factura_proveedor LIKE %s
		OR cxp_fact.numero_control_factura LIKE %s
		OR cxp_fact.observacion_factura LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
		
	$query = sprintf("SELECT cxp_fact.*,
		DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) as dias_vencidos,
	
		(SELECT orden_tot.id_orden_tot FROM sa_orden_tot orden_tot
		WHERE orden_tot.id_factura = cxp_fact.id_factura) AS id_orden_tot,
		
		(CASE cxp_fact.estatus_factura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(CASE id_modulo
			WHEN 1 THEN
				(SELECT COUNT(orden_tot.id_factura)
				FROM sa_orden_tot orden_tot
					INNER JOIN sa_orden_tot_detalle orden_tot_det ON (orden_tot.id_orden_tot = orden_tot_det.id_orden_tot)
				WHERE orden_tot.id_factura = cxp_fact.id_factura)
			WHEN 2 THEN
				(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
				+
				(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura)
			ELSE
				(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
				WHERE cxp_fact_det.id_factura = cxp_fact.id_factura)
		END) AS cant_items,
		
		(SELECT SUM(cxp_fact_det.cantidad) FROM cp_factura_detalle cxp_fact_det
		WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS cant_piezas,
		
		moneda_local.abreviacion AS abreviacion_moneda_local,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact.id_factura
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT reten_cheque.id_retencion_cheque FROM te_retencion_cheque reten_cheque
		WHERE reten_cheque.id_factura = cxp_fact.id_factura
			AND reten_cheque.tipo IN (0)
			AND reten_cheque.anulado IS NULL) AS id_retencion_cheque,
		
		(SELECT
			nota_cargo.id_notacargo
		FROM cp_notadecargo nota_cargo
			INNER JOIN an_unidad_fisica uni_fis ON (nota_cargo.id_detalles_pedido_compra = uni_fis.id_pedido_compra_detalle)
			INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN pg_motivo motivo ON (nota_cargo.id_motivo = motivo.id_motivo)
			INNER JOIN pg_modulos modulo ON (nota_cargo.id_modulo = modulo.id_modulo)
		WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura) AS id_nota_cargo_planmayor,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
		
		(IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
				FROM cp_factura_iva cxp_fact_iva
				WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total_iva,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva cxp_fact_iva
					WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total,
		
		cxp_fact.activa,
		vw_iv_usuario.nombre_empleado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
		LEFT JOIN vw_iv_usuarios vw_iv_usuario ON (cxp_fact.id_empleado_creador = vw_iv_usuario.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);  
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\" class=\"texto_10px\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= "<td width=\"1%\" colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFactura", "14%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "28%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "descripcion_estado_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "2%", $pageNum, "dias_vencidos", $campOrd, $tpOrd, $valBusq, $maxRows, "D&iacute;as Vencidos");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "saldo_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Veh&iacute;culos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administraci&oacute;n")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0 || $row['id_orden_tot'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		switch($row['activa']) {
			case "" : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Compra Registrada (Con Devoluci&oacute;n)\"/>"; break;
			case 1 : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
			default : $imgEstatusRegistroCompra = "";
		}
		
		switch($row['estatus_factura']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$diasVencidos = ($row['dias_vencidos'] > 0) ? $row['dias_vencidos'] : 0;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td><button type=\"button\" onclick=\"xajax_asignarFactura('".$row['id_factura']."');\" title=\"Seleccionar Factura\"><img src=\"../img/iconos/select.png\"/></button></td>";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusRegistroCompra."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado']) > 0) ? "title=\"Factura Nro: ".utf8_encode($row['numero_factura_proveedor']).". Registrado por: ".utf8_encode($row['nombre_empleado'])."\"" : "").">".date(spanDateFormat,strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat,strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat,strtotime($row['fecha_vencimiento']))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".(($row['id_nota_cargo_planmayor'] > 0) ? "<img src=\"../img/iconos/ico_plan_mayor.png\" title=\"Factura por Plan Mayor\"/>" : "")."</td>";
					$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($row['numero_factura_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['observacion_factura']) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($row['observacion_factura'])."<span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$diasVencidos."</td>";			
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['saldo_factura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"24\">";
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
		$htmlTb .= "<td colspan=\"24\">";
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

function listaNotaCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nd.id_empresa = %s OR cxp_nd.id_empresa IN ((SELECT id_empresa_reg 
														FROM vw_iv_empresas_sucursales 
														WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))
            AND cxp_nd.id_proveedor = %s 
			AND cxp_nd.estatus_notacargo <> 1 
			AND cxp_nd.id_notacargo NOT IN (SELECT te_propuesta_pago_detalle.id_factura 
														FROM te_propuesta_pago_detalle 
														INNER JOIN te_propuesta_pago ON te_propuesta_pago_detalle.id_propuesta_pago = te_propuesta_pago.id_propuesta_pago
														WHERE te_propuesta_pago_detalle.tipo_documento <> 0 														
														AND te_propuesta_pago.estatus_propuesta = 0
														
														UNION ALL
														
														SELECT te_propuesta_pago_detalle_transferencia.id_factura 
														FROM te_propuesta_pago_detalle_transferencia 
														INNER JOIN te_propuesta_pago_transferencia ON te_propuesta_pago_detalle_transferencia. id_propuesta_pago = te_propuesta_pago_transferencia.id_propuesta_pago
														WHERE te_propuesta_pago_detalle_transferencia.tipo_documento <> 0
														AND te_propuesta_pago_transferencia.estatus_propuesta = 0
														)",
				valTpDato($valCadBusq[0],"int"),
				valTpDato($valCadBusq[0],"int"),
				valTpDato($valCadBusq[1],"int"));
		
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_nd.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde1",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde2",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde3",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("masDe",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
			OR prov.nombre LIKE %s
			OR cxp_nd.numero_notacargo LIKE %s
			OR cxp_nd.numero_control_notacargo LIKE %s
			OR (SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) LIKE %s
			OR observacion_notacargo LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}

		   
	$query = sprintf("SELECT cxp_nd.*,
		DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) as dias_vencidos,
	
		(CASE cxp_nd.estatus_notacargo
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_nota_cargo,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.id_nota_cargo = cxp_nd.id_notacargo
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT
			fact_comp.id_factura
		FROM an_unidad_fisica uni_fis
			INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
			INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
		WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) AS id_factura_planmayor,
		
		(SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
		WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) AS serial_carroceria,
		
		(IFNULL(cxp_nd.subtotal_notacargo, 0)
			- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
			+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) AS total_gasto FROM cp_notacargo_gastos cxp_nd_gasto
					WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
						AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) AS total_iva FROM cp_notacargo_iva cxp_nd_iva
					WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0)) AS total,
		
		vw_iv_usuario.nombre_empleado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_proveedor prov
		INNER JOIN cp_notadecargo cxp_nd ON (prov.id_proveedor = cxp_nd.id_proveedor)
		LEFT JOIN pg_motivo motivo ON (cxp_nd.id_motivo = motivo.id_motivo)
		LEFT JOIN vw_iv_usuarios vw_iv_usuario ON (cxp_nd.id_empleado_creador = vw_iv_usuario.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)  %s", $sqlBusq);  
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\" class=\"texto_10px\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= "<td width=\"1%\" colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "14%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_origen_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_vencimiento_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "numero_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "34%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "8%", $pageNum, "descripcion_estado_nota_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "2%", $pageNum, "dias_vencidos", $campOrd, $tpOrd, $valBusq, $maxRows, "D&iacute;as Vencidos");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "8%", $pageNum, "saldo_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Nota de D&eacute;bito");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Veh&iacute;culos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administraci&oacute;n\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		switch($row['estatus_notacargo']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$diasVencidos = ($row['dias_vencidos'] > 0) ? $row['dias_vencidos'] : 0;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCargo('".$row['id_notacargo']."');\" title=\"Seleccionar Nota Cargo\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";			
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado']) > 0) ? "title=\"Nro. Nota de D&eacute;bito: ".utf8_encode($row['numero_notacargo']).". Registrado por: ".utf8_encode($row['nombre_empleado'])."\"" : "").">".date(spanDateFormat,strtotime($row['fecha_origen_notacargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_notacargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_vencimiento_notacargo']))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".(($row['id_factura_planmayor'] > 0 || $row['id_detalles_pedido_compra'] > 0) ? "<img src=\"../img/iconos/ico_plan_mayor.png\" title=\"Nota de D&eacute;bito de Factura por Plan Mayor\"/>" : "")."</td>";
					$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($row['numero_notacargo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['serial_carroceria']) > 0) ? "<tr><td><span class=\"textoNegrita_10px\">".utf8_encode($row['serial_carroceria'])."</span></td></tr>" : "";
				$htmlTb .= ($row['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacion_notacargo']) > 0) ? "<tr><td>".utf8_encode($row['observacion_notacargo'])."</td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_nota_cargo']."</td>";
			$htmlTb .= "<td align=\"center\">".$diasVencidos."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_notacargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"24\">";
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
		$htmlTb .= "<td colspan=\"24\">";
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

function listaCheques($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$objResponse->setCharacterEncoding('UTF-8');
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($_GET['acc'] == 1){//CHEQUES INDIVIDUALES TIENE IMPRESION
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_propuesta_pago = 0 AND entregado != 1");
		
		$objResponse->script("byId('btnNuevo').style.display = '';");
		$objResponse->assign("tdReferenciaPagina","innerHTML","Cheques Individuales");
	}else if ($_GET['acc'] == 2){//IMPRESION DE CHEQUES PROPUESTA DE PAGO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_propuesta_pago > 0 AND entregado != 1");
		
		$objResponse->script("byId('btnNuevo').style.display = 'none';");
		$objResponse->assign("tdReferenciaPagina","innerHTML","Cheques Propuesta Pago");
	}else if ($_GET['acc'] == 3){//HISTORICO DE CHEQUES
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("entregado = 1");
		
		$objResponse->script("byId('btnNuevo').style.display = 'none';");
		$objResponse->assign("tdReferenciaPagina","innerHTML","Historico Cheques");
	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estado_documento = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("numero_cheque = %s",
			valTpDato($valCadBusq[2], "numero_cheque"));
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[4] !=""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_registro BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "date")); 
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_beneficiario_proveedor = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("concepto LIKE %s",
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		vw_te_cheques.*, 
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor
	FROM vw_te_cheques
		INNER JOIN cp_proveedor prov ON (vw_te_cheques.id_beneficiario_proveedor = prov.id_proveedor) %s", $sqlBusq);
	
	if($campOrd == "fecha_cheque" OR $campOrd == "fecha_registro"){//agrupar por fecha y luego ordenar por numero
		$campOrd2 = $campOrd." ".$tpOrd.", numero_cheque DESC";
	}else{
		$campOrd2 = $campOrd." ".$tpOrd;
	}
        
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s ", $campOrd2) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$queryLimit); }			
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	        
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaCheques", "", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "", $pageNum, "entregado", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "12%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "6%", $pageNum, "numero_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Cheque");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "8%", $pageNum, "monto_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Cheque");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "1%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "1%", $pageNum, "fecha_aplicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "1%", $pageNum, "fecha_conciliacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Conciliaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "40%", $pageNum, "concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Concepto");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "15%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco Compa&ntilde;ia");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "15%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta Compa&ntilde;ia");
		$htmlTh .= "<td colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$queryRetencion = sprintf("SELECT id_retencion_cheque 
			FROM te_retencion_cheque 
			WHERE id_cheque = %s AND tipo_documento = 0",
			valTpDato($row['id_cheque'],"int"));
		$rsRetencion = mysql_query($queryRetencion);
		if (!$rsRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$tieneRetencion = "";
		if (mysql_num_rows($rsRetencion)){
			$tieneRetencion = "verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?id=".$row['id_cheque']."&documento=1',700,700);";
		}
		
		switch($row['estado_documento']){
			case 1: $imgEstado = "<img src=\"../img/iconos/ico_rojo.gif\">"; break;
			case 2: $imgEstado = "<img src=\"../img/iconos/ico_amarillo.gif\">"; break;
			case 3: $imgEstado = "<img src=\"../img/iconos/ico_verde.gif\">"; break;
			default : $imgEstado = "";
		}
		
		$imgEntregado = ($row['entregado'])	? "<img src=\"../img/iconos/ico_azul.gif\">" : "<img src=\"../img/iconos/ico_gris.gif\">";
		
		$fechaAplicacion = ($row['fecha_aplicacion']) ? date(spanDateFormat,strtotime($row['fecha_aplicacion'])) : "-" ;
		$fechaConciliacion = ($row['fecha_conciliacion']) ? date(spanDateFormat,strtotime($row['fecha_conciliacion'])) : "-";
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEntregado."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['id_proveedor'].".- ".$row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_cheque']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_cheque'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaAplicacion."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaConciliacion."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['concepto'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroCuentaCompania']."</td>";
			$htmlTb .= "<td align=\"center\">";
			if($row['impresion'] == 1){//si es puerto y si ya fue impreso
				$htmlTb .= "<img src=\"../img/iconos/chk_list_act.png\" title=\"Reimprimir Cheque\" onclick=\"verVentana('reportes/te_imprimir_cheque_pdf.php?id=".$row['id_cheque']."&reimpresion=1',700,700);\" class=\"puntero\">";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\"><a class=\"modalImg\" id=\"aNuevo\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCheque', 'ver', ".$row['id_cheque'].");\"><img class=\"puntero\" title=\"Ver Cheque\"  src=\"../img/iconos/ico_view.png\" ></a></td>";
			
			if ($_GET['acc'] == 1 || $_GET['acc'] == 2){//1= Cheque individual, 2 = Impresion de propuesta
				$img = "";
				if($row['impresion'] == 1){//si ya impreso editar para enviar al historico
					$img = "<a class=\"modalImg\" id=\"aNuevo\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCheque', 'editar', ".$row['id_cheque'].");\"><img class=\"puntero\" title=\"Editar\" src=\"../img/iconos/ico_edit.png\" ></a>";
				}else{//sino fue impreso permitir imprimir
					$img = "<img class=\"puntero\" onclick=\"window.open('te_impresion_cheque.php?acc=".$_GET['acc']."&id=".$row['id_cheque']."','_self'); ".$tieneRetencion."\" title=\"Imprimir Cheque\" src=\"../img/iconos/ico_print.png\">";
				}
				
				$htmlTb .= "<td align=\"center\" >".$img."</td>";
			}
			
			if ($_GET['acc'] == 3){//Historico de cheques
			$htmlTb .= sprintf("<td align='center' class=\"puntero\" title=\"Editar Fecha Cheque\"><a class=\"modalImg\" id=\"aEditarFechaCheque%s\" rel=\"#divFlotante2\" onclick=\"byId('hddChequeRegistrado').value = 1; abrirDivFlotante2(this, 'tblPermiso', 'te_cheque_fecha_registo', %s);\"><img src=\"../img/iconos/select_date.png\"/></a></td>",
				$row['id_cheque'],
				$row['id_cheque']);	
				
				$htmlTb .= "<td align='center' class=\"puntero\" title=\"Anular Cheque\"><a class=\"modalImg\" id=\"aAnular\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAnularCheque', ".$row['id_cheque'].");\"><img src=\"../img/iconos/delete.png\"/></a></td>";			
				
	 			$sPar = "idobject=".$row['id_cheque'];
				$sPar.= "&ct=14";
				$sPar.= "&dt=05";
				$sPar.= "&cc=05";
			// Modificado Ernesto
			$htmlTb .= "<td  align=\"center\">";
				$htmlTb .= "<img onclick=\"verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?$sPar', 1000, 500);\" src=\"../img/iconos/new_window.png\" title=\"Ver Movimiento Contable\"/>";
			$htmlTb .= "</td>";
			}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCheques(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheques(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaCheques","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	//$valCadBusq[0] criterio
	//$valCadBusq[1] == "1" si es para buscar en el listado        
	$buscarListado = $valCadBusq[1];
	
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
		
		if($buscarListado == "1"){
			$onclickAsignar = "onclick=\"xajax_asignarProveedor2('".$row['id_proveedor']."');\"";//busqueda en listado
		}else{
			$onclickAsignar = "onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."');\"";
		}
				
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" ".$onclickAsignar."  title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
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

function tieneImpuesto($idCheque){
    $objResponse = new xajaxResponse();
    
	$queryISLR = sprintf("
	SELECT * FROM cp_pagos_documentos WHERE 
	id_documento_pago IN (
	SELECT id_factura FROM te_retencion_cheque WHERE
							id_cheque = %s
							AND tipo_documento = 0 
							AND estado = 0 
	)  
	AND (tipo_documento_pago = 'FA' OR tipo_documento_pago = 'ND') 
	AND tipo_pago = 'ISLR'            
	AND numero_documento IN (
	SELECT id_retencion_cheque FROM te_retencion_cheque WHERE
							id_cheque = %s
							AND tipo_documento = 0
							AND estado = 0 
	) ",
		valTpDato($idCheque, "int"),
		valTpDato($idCheque, "int"));	
	$rsISLR = mysql_query($queryISLR);
	if(!$rsISLR) { return $objResponse->setReturnValue(mysql_error()."\n\nLine: ".__LINE__); }
	$tiene = mysql_num_rows($rsISLR);
	
	if($tiene){
		return $objResponse->setReturnValue("SI");
	}else{
		return $objResponse->setReturnValue("NO"); 
	}            
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
		if ($frmPermiso['hddModulo'] == "te_propuesta_pago") {
			$objResponse->assign("hddPasoClaveSobreGiro","value",1);
			$objResponse->script("byId('btnAceptar').disabled = false;");
		} else if ($frmPermiso['hddModulo'] == "te_cheque_fecha_registo") {
			if ($frmPermiso['hddChequeRegistrado'] == 1) {//cambiar fecha cheque ya registrado
				$objResponse->script(sprintf("
				setTimeout(function(){
					abrirDivFlotante2(byId('aEditarFechaCheque%s'), 'tblEditarFechaCheque', %s);
				},1500);",
				valTpDato($frmPermiso['hddIdChequeEditarFechaPermiso'], "int"),
				valTpDato($frmPermiso['hddIdChequeEditarFechaPermiso'], "int")));
			} else {//cambiar fecha cheque nuevo
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
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
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



function verificarClave($valForm){
	$objResponse = new xajaxResponse();
	
	$queryClave = sprintf("SELECT contrasena FROM vw_pg_claves_modulos WHERE id_usuario = %s AND id_clave_modulo = 34",
					valTpDato($_SESSION['idUsuarioSysGts'],'int'));
	$rsClave = mysql_query($queryClave);
	if (!$rsClave) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);
	
	if (mysql_num_rows($rsClave)){
		$rowClave = mysql_fetch_assoc($rsClave);
		if ($rowClave['contrasena'] == $valForm['txtClaveAprobacion']){
			$objResponse->assign("hddPermiso","value",1);
			$objResponse->script("byId('divFlotanteClave').style.display = 'none';");
			$objResponse->script("byId('btnAceptar').disabled = false;");
		}else{
			$objResponse->alert("Clave Errada.");
		}
	}else{
		$objResponse->alert("No tiene permiso para realizar esta accion");
		//$objResponse->script("byId('divFlotante').style.display = 'none';");
		$objResponse->script("byId('divFlotanteClave').style.display = 'none';");
	}
	
	return $objResponse;
}

function verPropuesta($idPropuesta){
    $objResponse = new xajaxResponse();
    
    $queryPropuesta = sprintf("SELECT 
		te_propuesta_pago.fecha_propuesta_pago, 
		te_propuesta_pago.estatus_propuesta,
		te_cheques.numero_cheque 
	FROM te_propuesta_pago
		INNER JOIN te_cheques ON (te_propuesta_pago.id_cheque = te_cheques.id_cheque) 
	WHERE te_propuesta_pago.id_propuesta_pago = %s",
		valTpDato($idPropuesta, "int"));
    $rsPropuesta = mysql_query($queryPropuesta);
    if (!$rsPropuesta) { return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }    
    $rowPropuesta = mysql_fetch_assoc($rsPropuesta);
    
    $queryDetalle = sprintf("SELECT 
		te_propuesta_pago_detalle.id_factura, 
		te_propuesta_pago_detalle.monto_pagar, 
		te_propuesta_pago_detalle.sustraendo_retencion, 
		te_propuesta_pago_detalle.porcentaje_retencion, 
		te_propuesta_pago_detalle.monto_retenido, 
		te_propuesta_pago_detalle.codigo, 
		te_propuesta_pago_detalle.tipo_documento,
		IF(te_propuesta_pago_detalle.tipo_documento = 0, 
			(SELECT numero_factura_proveedor FROM cp_factura WHERE cp_factura.id_factura = te_propuesta_pago_detalle.id_factura),
			(SELECT numero_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_propuesta_pago_detalle.id_factura)) as numero_documento
	FROM te_propuesta_pago_detalle 
	WHERE id_propuesta_pago = %s",
		valTpDato($idPropuesta, "int"));    
    $rsDetalle = mysql_query($queryDetalle);
    if (!$rsDetalle) { return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
    
    $tabla = "";
    $tabla .= "<table class='tabla-propuesta'>";
    
    $tabla .= "<tr>";
    $tabla .= "<th>Tipo Documento</th>";
    $tabla .= "<th>N&uacute;mero Documento</th>";
    $tabla .= "<th>Monto a Pagar</th>";
    $tabla .= "<th>Sustraendo Retenci&oacute;n</th>";
    $tabla .= "<th>Porcentaje Retenci&oacute;n</th>";
    $tabla .= "<th>Monto Retenido</th>";
    $tabla .= "<th>Codigo</th>";
    $tabla .= "</tr>";
    
    while($rowDetalle = mysql_fetch_assoc($rsDetalle)){
        if($rowDetalle['tipo_documento'] == 0){
            $tipoDocumento = "FACTURA";
        }else{
            $tipoDocumento = "NOTA DE CARGO";
        }
        
        $tabla .= "<tr>";    
        $tabla .= "<td>".$tipoDocumento."</td>";
        $tabla .= "<td idFacturaNotaOculta='".$rowDetalle['id_factura']."'>".$rowDetalle['numero_documento']."</td>";
        $tabla .= "<td>".$rowDetalle['monto_pagar']."</td>";
        $tabla .= "<td>".$rowDetalle['sustraendo_retencion']."</td>";
        $tabla .= "<td>".$rowDetalle['porcentaje_retencion']."</td>";
        $tabla .= "<td>".$rowDetalle['monto_retenido']."</td>";
        $tabla .= "<td>".$rowDetalle['codigo']."</td>";        
        $tabla .= "</tr>";
    }
    
    $tabla .= "</table>";
    
    if($rowPropuesta['estatus_propuesta'] == 1){
        $estado = "APROBADA";
    }else{
        $estado = "NO APROBADA";
    }
    
    $objResponse->assign("numeroPropuestaPago","innerHTML",$idPropuesta);
    $objResponse->assign("fechaPropuestaPago","innerHTML",date(spanDateFormat,strtotime($rowPropuesta['fecha_propuesta_pago'])));
    $objResponse->assign("numeroChequePropuestaPago","innerHTML",$rowPropuesta['numero_cheque']);
    $objResponse->assign("estadoPropuestaPago","innerHTML",$estado);
    
    $objResponse->assign("detallePropuestaPago","innerHTML",$tabla);
    			
    return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"asignarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"actualizarCheque");
$xajax->register(XAJAX_FUNCTION,"anularCheque");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBeneficiario");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesCuenta");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarFactura");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor2");
$xajax->register(XAJAX_FUNCTION,"buscarCheque");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarDocumento");
$xajax->register(XAJAX_FUNCTION,"cargarChequera");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstado");
$xajax->register(XAJAX_FUNCTION,"cargaListRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"formAnularCheque");
$xajax->register(XAJAX_FUNCTION,"formCheque");
$xajax->register(XAJAX_FUNCTION,"formEditarFechaCheque");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarCheque");
$xajax->register(XAJAX_FUNCTION,"guardarEditarFechaCheque");
$xajax->register(XAJAX_FUNCTION,"listaBanco");
$xajax->register(XAJAX_FUNCTION,"listarBeneficiarios");
$xajax->register(XAJAX_FUNCTION,"listaCheques");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaFactura");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaNotaCargo");
$xajax->register(XAJAX_FUNCTION,"tieneImpuesto");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
$xajax->register(XAJAX_FUNCTION,"verificarClave");
$xajax->register(XAJAX_FUNCTION,"verificarRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"verPropuesta");

function errorGuardarDcto($objResponse){
	$objResponse->script("desbloquearGuardado();");
}

?>