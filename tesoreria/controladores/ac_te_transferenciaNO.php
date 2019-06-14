<?php
function anularTransferencia($idTrans, $borrarISLR = "NO"){    
    $objResponse = new xajaxResponse();
	
	$id_usuario = $_SESSION['idUsuarioSysGts'];
	
    mysql_query("START TRANSACTION;");

	$queryTransferencia = sprintf("SELECT * FROM te_transferencia WHERE id_transferencia = %s",$idTrans);	
	$rsTransferencia = mysql_query($queryTransferencia);
	
	if (!$rsTransferencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTransferencia = mysql_fetch_array($rsTransferencia);
 
  	if($rowTransferencia['estado_documento']==3){
		return $objResponse->alert("No puede ser eliminada la transferencia, esta ya ha sido conciliada");
	}
		
	$queryCuenta = sprintf("SELECT saldo_tem FROM cuentas WHERE idCuentas = %s",$rowTransferencia['id_cuenta']); 
	$rsCuenta = mysql_query($queryCuenta);
	
	if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_array($rsCuenta);

	///////////////////////////R E C O N V E R S I Ó N   M O N E T A R I A////////////////////////////
	if($rowTransferencia['fecha_registro'] < '2018-08-20'){
		$montoTransfer = $rowTransferencia['monto_transferencia']/100000;		
	}else{
		$montoTransfer = $rowTransferencia['monto_transferencia'];		
	}	
	//////////////////////////////////////////////////////////////////////////////////////////////////
					
//	$saldoActual= $rowCuenta['saldo_tem'] + $rowTransferencia['monto_transferencia'];
	$saldoActual= $rowCuenta['saldo_tem'] + $montoTransfer;
	
	$queryUpdateSaldo = sprintf("UPDATE cuentas SET saldo_tem = %s WHERE idCuentas = %s", $saldoActual, $rowTransferencia['id_cuenta']);
	$rsUpdateSaldo = mysql_query($queryUpdateSaldo);
	
	if(!$rsUpdateSaldo){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($rowTransferencia['id_documento']==0){//si es cero es porque puede tener propuestas de pago
		$queryFacturasPropuesta = sprintf("SELECT 
												te_propuesta_pago_detalle_transferencia.id_propuesta_pago,
												te_propuesta_pago_detalle_transferencia.id_factura,
												te_propuesta_pago_detalle_transferencia.monto_pagar,
												te_propuesta_pago_detalle_transferencia.monto_retenido,
												te_propuesta_pago_detalle_transferencia.sustraendo_retencion,
												te_propuesta_pago_detalle_transferencia.porcentaje_retencion,
												te_propuesta_pago_detalle_transferencia.tipo_documento
											FROM te_propuesta_pago_transferencia 
											INNER JOIN te_propuesta_pago_detalle_transferencia ON (te_propuesta_pago_transferencia.id_propuesta_pago = te_propuesta_pago_detalle_transferencia.id_propuesta_pago) 
											WHERE te_propuesta_pago_transferencia.id_transfererencia = %s",$idTrans);
	
		$rsFacturasPropuesta = mysql_query($queryFacturasPropuesta);
		if (!$rsFacturasPropuesta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		while($rowFacturasPropuesta = mysql_fetch_array($rsFacturasPropuesta)){
			if($rowFacturasPropuesta['tipo_documento']==0){///////Factura 							
				/////////////////////////////////////////R E C O N V E R S I O N   M O N E T A R I A///////////////////////////////////////////////
				if($rowTransferencia['fecha_registro'] < '2018-08-20'){					
					$idFactura2 = $rowFacturasPropuesta['id_factura'];
					
					$objResponse->alert("Se esta intentando anular una transferencia correspondiente a una fecha anterior a la Reconversion Monetaria. Verificando si los documentos asociados fueron convertidos, por favor espere un momento... ");

					//Verifico si la factura ya tiene reconversion
					$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_factura = $idFactura2";
					$rsValidacion = mysql_query($queryValidacion);
					$numReg = mysql_num_rows($rsValidacion);
				
					if($numReg == 0){				
						//TABLA1
						$queryFactura1 = "UPDATE cp_factura 
											SET monto_exento = monto_exento/100000,
											monto_exonerado = monto_exonerado/100000,
											subtotal_factura = subtotal_factura/100000,
											subtotal_descuento = subtotal_descuento/100000,
											total_cuenta_pagar = total_cuenta_pagar/100000,
											saldo_factura = saldo_factura/100000
											WHERE id_factura = $idFactura2 ";
						$rsNota1 = mysql_query($queryFactura1);
						if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura1);
				
						//TABLA2
						$queryFactura2 = "UPDATE cp_factura_iva 
											SET subtotal_iva = subtotal_iva/100000,
											base_imponible = base_imponible/100000
											WHERE id_factura = $idFactura2 ";
						$rsNota2 = mysql_query($queryFactura2);
						if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura2);
				
						//TABLA3
						$queryFactura3 = "UPDATE cp_factura_gasto 
											SET monto = monto/100000
											WHERE id_factura = $idFactura2 ";
						$rsNota3 = mysql_query($queryFactura3);
						if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura3);
				
						//TABLA4
						$queryFactura4 = "UPDATE cp_factura_detalle 
											SET precio_unitario = precio_unitario/100000
											WHERE id_factura = $idFactura2 ";
						$rsNota4 = mysql_query($queryFactura4);
						if (!$rsNota4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura4);
				
						//TABLA5
						$queryFactura5 = "UPDATE cp_pagos_documentos 
											SET monto_cancelado = monto_cancelado/100000
											WHERE id_documento_pago = $idFactura2 ";
						$rsNota5 = mysql_query($queryFactura5);
						if (!$rsNota5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura5);
				
						//TABLA6
							$queryReconversion = "INSERT INTO cp_reconversion (id_factura,id_usuarios) VALUES ($idFactura2,$id_usuario)";
						$rsReconversion = mysql_query($queryReconversion);
						if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
					
				
						$mensaje = "Items Actualizados. Anulando transferencia, espere un momento...";
						$objResponse->alert("$mensaje");						
							
					}else{						
						$objResponse->alert("Los items de la factura correspondiente a la transferencia fueron convertidos a Bolivares Soberanos. Anulando transferencia, espere un momento...");
					}
				}
				
				$queryFacturaSaldo = sprintf("SELECT saldo_factura, total_cuenta_pagar FROM cp_factura WHERE id_factura = %s;",$rowFacturasPropuesta['id_factura']);
				$rsFacturaSaldo  = mysql_query($queryFacturaSaldo);
				if (!$rsFacturaSaldo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$rowFacturaSaldo  = mysql_fetch_array($rsFacturaSaldo );
				
				if($borrarISLR == "SI"){
					$MontoFactura= $rowFacturasPropuesta['monto_pagar'] + $rowFacturasPropuesta['monto_retenido'];
				}else{
					$MontoFactura= $rowFacturasPropuesta['monto_pagar'];
				}
				
				if($rowTransferencia['fecha_registro'] < '2018-08-20'){	
					$MontoFactura = $MontoFactura/100000;
				}
				
				$TotalMontoFactura= $rowFacturaSaldo['saldo_factura']+$MontoFactura;
				
				if($TotalMontoFactura == $rowFacturaSaldo['total_cuenta_pagar']){
					$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
				}elseif($TotalMontoFactura == 0){
					$cambioEstado = 1;
				}else{
					$cambioEstado = 2;
				}
				
				$queryUptadeFactura = sprintf("UPDATE cp_factura SET estatus_factura = '%s', saldo_factura = %s WHERE id_factura = %s ;",$cambioEstado,$TotalMontoFactura, $rowFacturasPropuesta['id_factura']);
				$rsUpdateFactura = mysql_query($queryUptadeFactura);
				if (!$rsUpdateFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

				$queryDeletePago = sprintf("UPDATE cp_pagos_documentos SET estatus = NULL, fecha_anulado = NOW(), id_empleado_anulado = %s WHERE id_documento_pago = %s AND tipo_pago = 'Transferencia' AND tipo_documento_pago = 'FA' AND id_documento = %s",$_SESSION['idEmpleadoSysGts'],$rowFacturasPropuesta['id_factura'], $idTrans);
				$rsDeletePago = mysql_query($queryDeletePago);
				if (!$rsDeletePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			}else{ //Nota de Cargo
				if($rowTransferencia['fecha_registro'] < '2018-08-20'){
					$idNotaCargo2 = $rowFacturasPropuesta['id_factura'];
										
					$objResponse->alert("Se esta intentando anular una transferencia correspondiente a una fecha anterior a la Reconversion Monetaria. Verificando si los documentos asociados fueron convertidos, por favor espere un momento... ");

					//Verifico si la nota de cargo ya tiene reconversion
					$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_notacargo = $idNotaCargo2";
					$rsValidacion = mysql_query($queryValidacion);
					$numReg = mysql_num_rows($rsValidacion);
				
					if($numReg == 0){				
						//TABLA1
						$queryNotaCargo1 = "UPDATE cp_notacargo_detalle_motivo 
											SET precio_unitario = precio_unitario/100000
											WHERE id_notacargo = $idNotaCargo2 ";
						$rsNota1 = mysql_query($queryNotaCargo1);
						if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo1);
				
						//TABLA2
						$queryNotaCargo2 = "UPDATE cp_notadecargo 
											SET monto_exento_notacargo = monto_exento_notacargo/100000,
											monto_exonerado_notacargo = monto_exonerado_notacargo/100000,
											subtotal_notacargo = subtotal_notacargo/100000,
											subtotal_descuento_notacargo = subtotal_descuento_notacargo/100000,
											total_cuenta_pagar = total_cuenta_pagar/100000,
											saldo_notacargo = saldo_notacargo/100000
											WHERE id_notacargo = $idNotaCargo2 ";
						$rsNota2 = mysql_query($queryNotaCargo2);
						if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo2);
				
						//TABLA3
						$queryNotaCargo3 = "UPDATE cp_pagos_documentos 
											SET monto_cancelado = monto_cancelado/100000
											WHERE id_documento_pago = $idNotaCargo2 ";
						$rsNota3 = mysql_query($queryNotaCargo3);
						if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo3);
														
						//TABLA6
							$queryReconversion = "INSERT INTO cp_reconversion (id_notacargo,id_usuarios) VALUES ($idNotaCargo2,$id_usuario)";
						$rsReconversion = mysql_query($queryReconversion);
						if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);					
				
						$mensaje = "Items Actualizados. Anulando transferencia, espere un momento...";
						$objResponse->alert("$mensaje");
							
					}else{						
						$objResponse->alert("Los items de la nota de cargo correspondiente a la transferencia ya fueron convertidos a Bolivares Soberanos. Anulando transferencia, espere un momento...");						
					}
				}
				
				$queryNotaCargo = sprintf("SELECT saldo_notacargo, total_cuenta_pagar FROM cp_notadecargo WHERE id_notacargo = %s;",$rowFacturasPropuesta['id_factura']);
				$rsNotaCargo  = mysql_query($queryNotaCargo);
                                if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$rowNotaCargo  = mysql_fetch_array($rsNotaCargo);
				
				if($borrarISLR == "SI"){
					$MontoNotaCargo = $rowFacturasPropuesta['monto_pagar'] + $rowFacturasPropuesta['monto_retenido'];
				}else{
					$MontoNotaCargo = $rowFacturasPropuesta['monto_pagar'];
				}
				
				if($rowTransferencia['fecha_registro'] < '2018-08-20'){
					$MontoNotaCargo = $MontoNotaCargo/100000;
				}
				
				$TotalMontoNotaCargo = $rowNotaCargo['saldo_notacargo']+$MontoNotaCargo;
                                
				if($TotalMontoNotaCargo == $rowNotaCargo['total_cuenta_pagar']){
					$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
				}elseif($TotalMontoNotaCargo == 0){
					$cambioEstado = 1;
				}else{
					$cambioEstado = 2;
				}
				
				$queryUptadeNotaCargo = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = '%s', saldo_notacargo = %s WHERE id_notacargo = %s ;",$cambioEstado,$TotalMontoNotaCargo, $rowFacturasPropuesta['id_factura']);
				$rsUpdateNotaCargo = mysql_query($queryUptadeNotaCargo);
				if (!$rsUpdateNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }				
				
				$queryDeletePago = sprintf("UPDATE cp_pagos_documentos SET estatus = NULL, fecha_anulado = NOW(), id_empleado_anulado = %s WHERE id_documento_pago = %s AND tipo_pago = 'Transferencia' AND tipo_documento_pago = 'ND' AND id_documento = %s",$_SESSION['idEmpleadoSysGts'],$rowFacturasPropuesta['id_factura'], $idTrans);
				$rsDeletePago = mysql_query($queryDeletePago);
				if (!$rsDeletePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

			}

			$id_propuesta_pago = $rowFacturasPropuesta['id_propuesta_pago']; 
			
			$queryDetalleTransferenciaAnulada = sprintf("INSERT INTO te_transferencias_anuladas_detalle(id_transferencia, id_propuesta_pago, id_factura, monto_pagar, sustraendo_retencion, porcentaje_retencion, monto_retenido, tipo_documento)  VALUES 
                                ('%s', '%s', '%s','%s','%s','%s','%s','%s');",
                                $idTrans,
                                $rowFacturasPropuesta['id_propuesta_pago'],
                                $rowFacturasPropuesta['id_factura'],
                                $montoTransfer,
                                $rowFacturasPropuesta['sustraendo_retencion'],
                                $rowFacturasPropuesta['porcentaje_retencion'],
                                $rowFacturasPropuesta['monto_retenido'],
                                $rowFacturasPropuesta['tipo_documento']);
			$rsDetalleTransferenciaAnulada = mysql_query($queryDetalleTransferenciaAnulada);
			if (!$rsDetalleTransferenciaAnulada) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                        
		}//fin while
		
	}else{//sino no tiene propuesta de pago:		
		if($rowTransferencia['tipo_documento']==0){//si es factura		
			/////////////////////////////////////////R E C O N V E R S I O N   M O N E T A R I A///////////////////////////////////////////////
			if($rowTransferencia['fecha_registro'] < '2018-08-20'){					
				$idFactura2 = $rowTransferencia['id_documento'];
				
				$objResponse->alert("Se esta intentando anular una transferencia correspondiente a una fecha anterior a la Reconversion Monetaria. Verificando si los documentos asociados fueron convertidos, por favor espere un momento... ");
			
				//Verifico si la factura ya tiene reconversion
				$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_factura = $idFactura2";
				$rsValidacion = mysql_query($queryValidacion);
				$numReg = mysql_num_rows($rsValidacion);
			
				if($numReg == 0){				
					//TABLA1
					$queryFactura1 = "UPDATE cp_factura 
										SET monto_exento = monto_exento/100000,
										monto_exonerado = monto_exonerado/100000,
										subtotal_factura = subtotal_factura/100000,
										subtotal_descuento = subtotal_descuento/100000,
										total_cuenta_pagar = total_cuenta_pagar/100000,
										saldo_factura = saldo_factura/100000
										WHERE id_factura = $idFactura2 ";
					$rsNota1 = mysql_query($queryFactura1);
					if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura1);
			
					//TABLA2
					$queryFactura2 = "UPDATE cp_factura_iva 
										SET subtotal_iva = subtotal_iva/100000,
										base_imponible = base_imponible/100000
										WHERE id_factura = $idFactura2 ";
					$rsNota2 = mysql_query($queryFactura2);
					if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura2);
			
					//TABLA3
					$queryFactura3 = "UPDATE cp_factura_gasto 
										SET monto = monto/100000
										WHERE id_factura = $idFactura2 ";
					$rsNota3 = mysql_query($queryFactura3);
					if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura3);
			
					//TABLA4
					$queryFactura4 = "UPDATE cp_factura_detalle 
										SET precio_unitario = precio_unitario/100000
										WHERE id_factura = $idFactura2 ";
					$rsNota4 = mysql_query($queryFactura4);
					if (!$rsNota4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura4);
			
					//TABLA5
					$queryFactura5 = "UPDATE cp_pagos_documentos 
										SET monto_cancelado = monto_cancelado/100000
										WHERE id_documento_pago = $idFactura2 ";
					$rsNota5 = mysql_query($queryFactura5);
					if (!$rsNota5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura5);
			
					//TABLA6
						$queryReconversion = "INSERT INTO cp_reconversion (id_factura,id_usuarios) VALUES ($idFactura2,$id_usuario)";
					$rsReconversion = mysql_query($queryReconversion);
					if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
				
			
					$mensaje = "Items Actualizados. Anulando transferencia, espere un momento...";
					$objResponse->alert("$mensaje");						
						
				}else{						
					$objResponse->alert("Los items de la factura correspondiente a la transferencia fueron convertidos a Bolivares Soberanos. Anulando transferencia, espere un momento...");
				}
			}
		
			$queryFacturaSaldo = sprintf("SELECT saldo_factura, total_cuenta_pagar FROM cp_factura WHERE id_factura = %s;",
									$rowTransferencia['id_documento']);
			$rsFacturaSaldo  = mysql_query($queryFacturaSaldo);
			if (!$rsFacturaSaldo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowFacturaSaldo  = mysql_fetch_array($rsFacturaSaldo );

			$queryRetenido = sprintf("SELECT * FROM te_retencion_cheque WHERE id_factura = %s AND id_cheque = %s;",
								$rowTransferencia['id_documento'], $rowTransferencia['id_transferencia']);
			$rsRetenido  = mysql_query($queryRetenido);
			if (!$rsRetenido) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowRetenido  = mysql_fetch_array($rsRetenido );
			
			if($borrarISLR == "SI"){
				$MontoFactura= $rowTransferencia['monto_transferencia'] + $rowRetenido['monto_retenido'];
			}else{
				$MontoFactura= $rowTransferencia['monto_transferencia'];
			}
			
			if($rowTransferencia['fecha_registro'] < '2018-08-20'){
				$MontoFactura = $MontoFactura/100000;
			}
			
			$TotalMontoFactura= $rowFacturaSaldo['saldo_factura']+$MontoFactura;
			
			if($TotalMontoFactura == $rowFacturaSaldo['total_cuenta_pagar']){
				$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
			}elseif($TotalMontoFactura == 0){
				$cambioEstado = 1;
			}else{
				$cambioEstado = 2;
			}

			$queryUptadeFactura = sprintf("UPDATE cp_factura SET estatus_factura = '%s', saldo_factura = %s WHERE id_factura = %s ;",$cambioEstado,$TotalMontoFactura, $rowTransferencia['id_documento']);	
			$rsUpdateFactura = mysql_query($queryUptadeFactura);
			if (!$rsUpdateFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			$queryDeletePago = sprintf("UPDATE cp_pagos_documentos SET estatus = NULL, fecha_anulado = NOW(), id_empleado_anulado = %s WHERE id_documento_pago = %s AND tipo_pago = 'Transferencia' AND tipo_documento_pago = 'FA' AND id_documento = %s",$_SESSION['idEmpleadoSysGts'],$rowTransferencia['id_documento'], $idTrans);
			$rsDeletePago = mysql_query($queryDeletePago);
			if (!$rsDeletePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                        
		}else{//si es nota de cargo
			if($rowTransferencia['fecha_registro'] < '2018-08-20'){
				$idNotaCargo2 = $rowTransferencia['id_documento'];
									
				$objResponse->alert("Se esta intentando anular una transferencia correspondiente a una fecha anterior a la Reconversion Monetaria. Verificando si los documentos asociados fueron convertidos, por favor espere un momento... ");
	
				//Verifico si la nota de cargo ya tiene reconversion
				$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_notacargo = $idNotaCargo2";
				$rsValidacion = mysql_query($queryValidacion);
				$numReg = mysql_num_rows($rsValidacion);
			
				if($numReg == 0){				
					//TABLA1
					$queryNotaCargo1 = "UPDATE cp_notacargo_detalle_motivo 
										SET precio_unitario = precio_unitario/100000
										WHERE id_notacargo = $idNotaCargo2 ";
					$rsNota1 = mysql_query($queryNotaCargo1);
					if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo1);
			
					//TABLA2
					$queryNotaCargo2 = "UPDATE cp_notadecargo 
										SET monto_exento_notacargo = monto_exento_notacargo/100000,
										monto_exonerado_notacargo = monto_exonerado_notacargo/100000,
										subtotal_notacargo = subtotal_notacargo/100000,
										subtotal_descuento_notacargo = subtotal_descuento_notacargo/100000,
										total_cuenta_pagar = total_cuenta_pagar/100000,
										saldo_notacargo = saldo_notacargo/100000
										WHERE id_notacargo = $idNotaCargo2 ";
					$rsNota2 = mysql_query($queryNotaCargo2);
					if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo2);
			
					//TABLA3
					$queryNotaCargo3 = "UPDATE cp_pagos_documentos 
										SET monto_cancelado = monto_cancelado/100000
										WHERE id_documento_pago = $idNotaCargo2 ";
					$rsNota3 = mysql_query($queryNotaCargo3);
					if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo3);
													
					//TABLA6
						$queryReconversion = "INSERT INTO cp_reconversion (id_notacargo,id_usuarios) VALUES ($idNotaCargo2,$id_usuario)";
					$rsReconversion = mysql_query($queryReconversion);
					if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);					
			
					$mensaje = "Items Actualizados. Anulando transferencia, espere un momento...";
					$objResponse->alert("$mensaje");
						
				}else{						
					$objResponse->alert("Los items de la nota de cargo correspondiente a la transferencia ya fueron convertidos a Bolivares Soberanos. Anulando transferencia, espere un momento...");						
				}
			}
                    
			$queryNotaCargo = sprintf("SELECT saldo_notacargo, total_cuenta_pagar FROM cp_notadecargo WHERE id_notacargo = %s;",
								$rowTransferencia['id_documento']);
			$rsNotaCargo  = mysql_query($queryNotaCargo);
			if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowNotaCargo  = mysql_fetch_array($rsNotaCargo);
			
			$queryRetenido = sprintf("SELECT * FROM te_retencion_cheque WHERE id_factura = %s AND id_cheque = %s;",
								$rowTransferencia['id_documento'], 
								$rowTransferencia['id_transferencia']);
			$rsRetenido  = mysql_query($queryRetenido);
			if (!$rsRetenido) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowRetenido  = mysql_fetch_array($rsRetenido );
			
			if($borrarISLR == "SI"){
				$MontoNotaCargo= $rowTransferencia['monto_transferencia'] + $rowRetenido['monto_retenido'];
			}else{
				$MontoNotaCargo= $rowTransferencia['monto_transferencia'];
			}
			
			if($rowTransferencia['fecha_registro'] < '2018-08-20'){
				$MontoNotaCargo = $MontoNotaCargo/100000;
			}						
			
			$TotalMontoNotaCargo= $rowNotaCargo['saldo_notacargo']+$MontoNotaCargo;						
			
			if($TotalMontoNotaCargo == $rowNotaCargo['total_cuenta_pagar']){
				$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
			}elseif($TotalMontoNotaCargo == 0){
				$cambioEstado = 1;
			}else{
				$cambioEstado = 2;
			}			
			
			$queryUptadeNotaCargo = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = '%s', saldo_notacargo = %s WHERE id_notacargo = %s ;",$cambioEstado,$TotalMontoNotaCargo, $rowTransferencia['id_documento']);
			$rsUpdateNotaCargo = mysql_query($queryUptadeNotaCargo);
			if (!$rsUpdateNotaCargo) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			$queryDeletePago = sprintf("UPDATE cp_pagos_documentos SET estatus = NULL, fecha_anulado = NOW(), id_empleado_anulado = %s WHERE id_documento_pago = %s AND tipo_pago = 'Transferencia' AND tipo_documento_pago = 'ND' AND id_documento = %s",$_SESSION['idEmpleadoSysGts'],$rowTransferencia['id_documento'], $idTrans);
			$rsDeletePago = mysql_query($queryDeletePago);
			if (!$rsDeletePago) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		}
	}    	
	    
	$queryTransferenciaAnulada = sprintf("INSERT INTO te_transferencias_anuladas(numero_transferencia, num_cuenta, beneficiario_proveedor, id_beneficiario_proveedor, fecha_registro, fecha_transferencia, observacion, monto_transferencia, id_cuenta, id_empresa, id_usuario, id_documento, id_transferencia, tipo_documento)  VALUES 
	('%s', '%s', '%s', '%s', NOW(),'%s','%s', '%s',%s, %s, %s, %s, %s, %s);",
						$rowTransferencia['numero_transferencia'],
						$rowTransferencia['num_cuenta'],
						$rowTransferencia['beneficiario_proveedor'],
						$rowTransferencia['id_beneficiario_proveedor'],
						$rowTransferencia['fecha_registro'],//esta es la fecha de transferencia
						$rowTransferencia['observacion'],
						$montoTransfer,
						$rowTransferencia['id_cuenta'],
						$rowTransferencia['id_empresa'],
						$_SESSION['idUsuarioSysGts'],
						$rowTransferencia['id_documento'],
						$idTrans,
						$rowTransferencia['tipo_documento']);
	$rsTransferenciaAnulada = mysql_query($queryTransferenciaAnulada);
	if (!$rsTransferenciaAnulada) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$idTransferenciaAnulada = mysql_insert_id();//Debe usar en contabilidad
	                
	//eliminar los pagos de impuestos
	if($borrarISLR == "SI"){
		$queryBorrarISLR = sprintf("
		UPDATE cp_pagos_documentos SET estatus = NULL, fecha_anulado = NOW(), id_empleado_anulado = %s WHERE 
		id_documento_pago IN (
		SELECT id_factura FROM te_retencion_cheque WHERE
								id_cheque = %s
								AND tipo_documento = 1 
								AND estado = 0 
		)  
		AND (tipo_documento_pago = 'FA' OR tipo_documento_pago = 'ND') 
		AND tipo_pago = 'ISLR'            
		AND numero_documento IN (
		SELECT id_retencion_cheque FROM te_retencion_cheque WHERE
								id_cheque = %s
								AND tipo_documento = 1
								AND estado = 0 
		) ",
		$_SESSION['idEmpleadoSysGts'],
		$idTrans,
		$idTrans);
		
		$rsBorrarISLR = mysql_query($queryBorrarISLR);
		if(!$rsBorrarISLR) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					
	}

	$queryEstadoCuenta = sprintf("DELETE FROM te_estado_cuenta WHERE id_documento = %s AND tipo_documento = 'TR' AND numero_documento= '%s' ",$idTrans,$rowTransferencia['numero_transferencia']);
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
                
	if($borrarISLR == "SI"){
		$queryDeleteRetencion = sprintf("UPDATE te_retencion_cheque SET anulado = 1 WHERE id_cheque= %s AND estado= 0 AND tipo_documento = 1" ,$idTrans);
		$rsDeleteRetencion = mysql_query($queryDeleteRetencion);
		if (!$rsDeleteRetencion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryDeleteRetencion);
	}
		
	$queryDeleteTransferencia = sprintf("DELETE FROM te_transferencia WHERE id_transferencia= %s",$idTrans);
	$rsDeleteTransferencia = mysql_query($queryDeleteTransferencia);
	if (!$rsDeleteTransferencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
	$queryDeletePropuestaDetalle = sprintf("DELETE FROM te_propuesta_pago_detalle_transferencia WHERE id_propuesta_pago = %s " ,valTpDato($id_propuesta_pago,"int"));
	$rsDeletePropuestaDetalle = mysql_query($queryDeletePropuestaDetalle);
	if (!$rsDeletePropuestaDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryDeletePropuestaDetalle);
		
	$queryDeletePropuesta = sprintf("DELETE FROM te_propuesta_pago_transferencia WHERE id_propuesta_pago = %s " ,valTpDato($id_propuesta_pago,"int"));
	$rsDeletePropuesta = mysql_query($queryDeletePropuesta);
	if (!$rsDeletePropuesta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);		

	mysql_query("COMMIT;");
	
	$objResponse->script("byId('divAnular').style.display = 'none';");
	$objResponse->script("xajax_listadoTransferencia(0,'fecha_registro','DESC','-1|0||');");
	$objResponse->alert("Se ha anulado la transferencia con exito");
	
	return $objResponse;
}

function tieneImpuesto($idTransferencia){
    $objResponse = new xajaxResponse();
    
	$queryISLR = sprintf("
	SELECT * FROM cp_pagos_documentos WHERE 
	id_documento_pago IN (
	SELECT id_factura FROM te_retencion_cheque WHERE
							id_cheque = %s
							AND tipo_documento = 1 
							AND estado = 0 
	)  
	AND (tipo_documento_pago = 'FA' OR tipo_documento_pago = 'ND') 
	AND tipo_pago = 'ISLR'            
	AND numero_documento IN (
	SELECT id_retencion_cheque FROM te_retencion_cheque WHERE
							id_cheque = %s
							AND tipo_documento = 1
							AND estado = 0 
	) ",
	$idTransferencia,
	$idTransferencia);
	
	$rsISLR = mysql_query($queryISLR);
	if(!$rsISLR) { return $objResponse->setReturnValue(mysql_error()."\n\nLine: ".__LINE__); }
	$tiene = mysql_num_rows($rsISLR);
	
	if($tiene){
		return $objResponse->setReturnValue("SI");
	}else{
		return $objResponse->setReturnValue("NO"); 
	}            
}

function asignarBanco($id_banco){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("byId('divFlotante2').style.display = 'none'");	
	
	$query = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmTransferencia'));
						  byId('divFlotante1').style.display = 'none'");
	
	return $objResponse;
}

function asignarBeneficiario($id_beneficiario){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_beneficiarios WHERE id_beneficiario = '".$id_beneficiario."'";
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtIdBeneficiario","value",$row['id_beneficiario']);
	$objResponse->assign("hddBeneficiario_O_Provedor","value","0");
	$objResponse->assign("txtNombreBeneficiario","value", utf8_encode($row['nombre_beneficiario']));
	$objResponse->assign("txtCiRifBeneficiario","value",$row['lci_rif']."-".$row['ci_rif_beneficiario']);

	$objResponse->script("xajax_asignarDetallesRetencion(".$row['idretencion'].")");
	$objResponse->script("byId('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarDetallesCuenta($idTransferencia){
	$objResponse = new xajaxResponse();
	
	$queryDetalleCuenta = sprintf("SELECT nombreBanco, numeroCuentaCompania, saldo_tem FROM vw_te_transferencia WHERE id_transferencia = %s Limit 1",$idTransferencia);
	$rsDetalleCuenta = mysql_query($queryDetalleCuenta);
	if(!$rsDetalleCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDetalleCuenta = mysql_fetch_array($rsDetalleCuenta);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($rowDetalleCuenta['nombreBanco']));
	$objResponse->assign("tdSelCuentas","innerHTML"," <input type='text' id='selCuenta' name='selCuenta' class='inputHabilitado' readonly='readonly' value='".$rowDetalleCuenta['numeroCuentaCompania']."' size='25'/>");
	$objResponse->assign("txtSaldoCuenta","value",number_format($rowDetalleCuenta['saldo_tem'],'2','.',','));
	$objResponse->assign("hddSaldoCuenta","value",$rowDetalleCuenta['saldo_tem']);
	
	return $objResponse; 
}

function asignarDetallesRetencion($idRetencion){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_retenciones WHERE id = '".$idRetencion."'";
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$row = mysql_fetch_array($rs);

	$objResponse->assign("hddMontoMayorAplicar","value",$row['importe']);
	$objResponse->assign("hddPorcentajeRetencion","value",$row['porcentaje']);
	$objResponse->assign("hddSustraendoRetencion","value",$row['sustraendo']);
	$objResponse->assign("hddCodigoRetencion","value",$row['codigo']);
	$objResponse->script("calcularRetencion();");
	
	return $objResponse;
}

function asignarEmpresa($idEmpresa,$accion){
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
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
		$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmCheque'));");
	}
	
	$objResponse->script("byId('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarFactura($idFactura){
	$objResponse = new xajaxResponse();
	
	$queryFactura = sprintf("SELECT numero_factura_proveedor, fecha_origen, fecha_vencimiento, observacion_factura, saldo_factura FROM cp_factura WHERE id_factura = '%s'",$idFactura);
	$rsFactura = mysql_query($queryFactura);
	if(!$rsFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
	$rowFactura = mysql_fetch_assoc($rsFactura);
		
	$objResponse->assign("txtIdFactura","value",$idFactura);
	$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("txtSaldoFactura","value",$rowFactura['saldo_factura']);
	$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_origen'])));
	$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_vencimiento'])));
	$objResponse->assign("txtDescripcionFactura","innerHTML",utf8_encode($rowFactura['observacion_factura']));
	$objResponse->assign("hddTipoDocumento","value","0");
	$objResponse->assign("tdFacturaNota","innerHTML","FACTURA");
	
	$queryIvaFactura = sprintf("SELECT base_imponible, iva FROM cp_factura_iva WHERE id_factura = %s ",$idFactura);
	$rsIvaFactura = mysql_query($queryIvaFactura);
	
	if (!$rsIvaFactura) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);
			
	if (mysql_num_rows($rsIvaFactura)){
		$rowIvaFactura = mysql_fetch_array($rsIvaFactura);
		$objResponse->assign("hddIva","value",$rowIvaFactura['iva']);
		$objResponse->assign("hddBaseImponible","value",$rowIvaFactura['base_imponible']);
	}else{
		$objResponse->assign("hddIva","value","0");
		$objResponse->assign("hddBaseImponible","value","0");
	}
			
	$objResponse->script("xajax_verificarRetencionISLR(".$idFactura.",0);
						byId('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarNotaCargo($idNotaCargo){
	$objResponse = new xajaxResponse();
	
	$queryNotaCargo = sprintf("SELECT numero_notacargo,fecha_origen_notacargo, fecha_vencimiento_notacargo , observacion_notacargo, saldo_notacargo FROM cp_notadecargo WHERE id_notacargo = '%s'",$idNotaCargo);
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
	
	$queryIvaNotaCargo = sprintf("SELECT baseimponible, iva  FROM cp_notacargo_iva WHERE id_notacargo = %s ",$idNotaCargo);
	$rsIvaNotaCargo = mysql_query($queryIvaNotaCargo);
	
	if (!$rsIvaNotaCargo) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);
	
	if (mysql_num_rows($rsIvaNotaCargo)){
		$rowIvaNotaCargo = mysql_fetch_array($rsIvaNotaCargo);
		$objResponse->assign("hddIva","value",$rowIvaNotaCargo['iva']);
		$objResponse->assign("hddBaseImponible","value",$rowIvaNotaCargo['baseimponible']);
	}else{
		$objResponse->assign("hddIva","value","0");
		$objResponse->assign("hddBaseImponible","value","0");
	}
			
	$objResponse->script("xajax_verificarRetencionISLR(".$idNotaCargo.",1);
						byId('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarProveedor($id_proveedor, $cargando){
	$objResponse = new xajaxResponse();        
	
	$query = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$id_proveedor."'";
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtIdBeneficiario","value",$row['id_proveedor']);
	$objResponse->assign("hddBeneficiario_O_Provedor","value","1");
	$objResponse->assign("txtNombreBeneficiario","value",utf8_encode($row['nombre']));
	$objResponse->assign("txtCiRifBeneficiario","value",$row['lrif']."-".$row['rif']);
	$objResponse->assign("txtNumCuenta","value",$row['ncuenta']);
	
	$query2 = "SELECT reimpuesto FROM cp_prove_credito WHERE id_proveedor = '".$id_proveedor."'";
	$rs2 = mysql_query($query2);
	if(!$rs2) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row2 = mysql_fetch_array($rs2);
	
	if($cargando == "SI"){
		//si esta cargando no mostrar retencion
	}else{
		//retencion automatica configurada desde cuentas por pagar
		//$objResponse->script("xajax_asignarDetallesRetencion(".$row2['reimpuesto'].")");
	}
	
	$objResponse->script("byId('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarProveedor2($id_proveedor){//solo para listado
	$objResponse = new xajaxResponse();        
        
	$query = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$id_proveedor."'";
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("idProveedorBuscar","value",$row['id_proveedor']);
	$objResponse->assign("nombreProveedorBuscar","value",utf8_encode($row['nombre']));
  
	$objResponse->script("byId('divFlotante1').style.display = 'none';
						byId('btnBuscar').click();");
	
	return $objResponse;
}

function buscarTransferencia($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoTransferencia(0,'fecha_registro','DESC','%s|%s|%s|%s|%s|%s|%s');",
		$valForm['selEmpresa'],
		$valForm['selEstado'],
		$valForm['txtBusq'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['idProveedorBuscar'],
		$valForm['conceptoBuscar']
		));
	
	return $objResponse;
}

function cargaSaldoCuenta($id_cuenta){
	$objResponse = new xajaxResponse();

	$queryCuenta = sprintf("SELECT * FROM cuentas WHERE  idCuentas = '%s'",$id_cuenta);
	$rsCuenta = mysql_query($queryCuenta);
	if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_array($rsCuenta);
	
	$queryChequera = sprintf("SELECT id_chq, ultimo_nro_chq FROM te_chequeras WHERE id_cuenta = '%s' AND disponibles <>0",$id_cuenta);
	$rsChequera = mysql_query($queryChequera);
	if(!$rsChequera) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowChequera = mysql_fetch_array($rsChequera);
	
	$Diferido = $rowCuenta['Diferido'];	
	
	$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat));
	$objResponse->assign("txtSaldoCuenta","value",number_format($rowCuenta['saldo_tem'],'2','.',','));
	$objResponse->assign("hddSaldoCuenta","value",number_format($rowCuenta['saldo_tem'],'2',',',''));
	$objResponse->assign("hddIdCuenta","value",$id_cuenta);
	$objResponse->assign("txtDiferido","value",number_format($Diferido,'2','.',''));
	$objResponse->assign("hddDiferido","value",number_format($Diferido,'2','.',''));
	$objResponse->assign("numCheque","value",$rowChequera['ultimo_nro_chq']+1);
	$objResponse->assign("txtMonto","value","");
	
	$objResponse->script("byId('btnBuscarCliente').disabled = false;
							  byId('txtComentario').disabled = false;
							  byId('txtMonto').disabled = false;
							  byId('btnAceptar').disabled = false;");
	
	return $objResponse;
}

function comboCuentas($valForm){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}else{
		$condicion = "WHERE idBanco = '".$valForm['hddIdBanco']."' AND id_empresa = '".$valForm['hddIdEmpresa']."'";
		$disabled = "";
	}
	
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" ".$disabled." class=\"inputHabilitado\" onchange=\"xajax_cargaSaldoCuenta(this.value);\">";
		$html .= "<option value=\"-1\">Seleccione</option>";
	while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
		$html .= "<option value=\"".$rowCuentas['idCuentas']."\">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentas","innerHTML",$html);
		
	return $objResponse;
}

function comboEmpresa($idTd,$idSelect,$selId){
	$objResponse = new xajaxResponse();
	
	if ($selId){
		$idEmpresa = $selId;
	}else{
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY id_empresa_reg",$_SESSION['idUsuarioSysGts']);
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$html = "<select id=\"".$idSelect."\" name=\"".$idSelect."\" class=\"inputHabilitado\" onChange=\"xajax_buscarTransferencia(xajax.getFormValues('frmBuscar'))\">";
	$html .="<option value=\"0\">Todas</option>";
	
	while($row = mysql_fetch_assoc($rs)){
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0){ $nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")"; }
		
		$selected = "";			
		if ($idEmpresa == $row['id_empresa_reg']){ $selected = "selected='selected'"; }
		
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
	}
	
	$html .= "</select>";
		
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function comboEstado(){
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM te_estados_principales ORDER BY id_estados_principales");
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                
	$html = "<select id=\"selEstado\" name=\"selEstado\" class=\"inputHabilitado\" onChange=\"xajax_buscarTransferencia(xajax.getFormValues('frmBuscar'))\">";
	$html .="<option selected=\"selected\" value=\"0\">Todos los Estados</option>";
	
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "selected='selected'";
		$html .= "<option value=\"".$row['id_estados_principales']."\">".utf8_encode($row['descripcion'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdSelEstado","innerHTML",$html);
	
	return $objResponse;
}

function comboRetencionISLR(){
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


function guardarTransferencia($valForm){    
	$objResponse = new xajaxResponse();
	
	if($valForm['txtIdFactura'] == ''){//sino se envio id de factura o nota de cargo
		errorGuardarDcto($objResponse);
		return $objResponse->alert("No se pueden generar transferencias sin un documento asociado FA o ND");
	}
	
	mysql_query("START TRANSACTION;");
	
	$queryFolio = sprintf("SELECT * FROM te_folios WHERE id_folios = '5'");
	$rsFolio = mysql_query($queryFolio);
	if (!$rsFolio) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()); }
	$rowFolio = mysql_fetch_array($rsFolio);
	
	$queryFoliosUpdate = sprintf("UPDATE te_folios SET numero_actual = '%s' WHERE id_folios = '5'",$rowFolio['numero_actual']+1);	
	$rsFoliosUpdate = mysql_query($queryFoliosUpdate);
	if (!$rsFoliosUpdate){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()); }
		
	$queryTransferencia = sprintf("INSERT INTO te_transferencia(id_transferencia, numero_transferencia,num_cuenta, folio_tesoreria, beneficiario_proveedor, id_beneficiario_proveedor, fecha_registro, observacion, monto_transferencia, id_cuenta, estado_documento, fecha_conciliacion, fecha_aplicacion, id_empresa, desincorporado, id_usuario, id_documento,tipo_documento)  VALUES 
	('', '%s', '%s', '%s', %s, %s, '%s', '%s', '%s', '%s', '2', NULL , NOW() , %s, '1', %s, '%s','%s');",
			$valForm['numTransferencia'],
			$valForm['txtNumCuenta'],
			$rowFolio['numero_actual'],
			$valForm['hddBeneficiario_O_Provedor'],
			$valForm['txtIdBeneficiario'],
			date("Y-m-d",strtotime($valForm['txtFechaRegistro'])),
			$valForm['txtComentario'],
			str_replace(',','.',$valForm['txtMonto']),
			$valForm['hddIdCuenta'],
			$valForm['hddIdEmpresa'],
			$_SESSION['idUsuarioSysGts'],
			$valForm['txtIdFactura'],
			$valForm['hddTipoDocumento']);
		
	mysql_query("SET NAMES 'utf8'");
	
	$rsTransferencia = mysql_query($queryTransferencia);
	if (!$rsTransferencia){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$idTransferencia = mysql_insert_id();		
	
	if ($valForm['hddTipoDocumento']==0){
		$tipoDocumento='FA';
	}else{
		$tipoDocumento='ND';        
	}
	
	$queryCpPago = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago,tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) VALUE ('%s', '%s', 'Transferencia', '%s', NOW(), '%s', '%s', '%s', '%s', '%s', '%s', %s)",
                        $valForm['txtIdFactura'],
                        $tipoDocumento,
                        $idTransferencia,
                        $valForm['numTransferencia'],
                        '-',
                        CuentaBanco(1,$valForm['hddIdCuenta']),//$valFormPagos['bancoCompania'.$valor],
                        '-',
                        CuentaBanco(0,$valForm['hddIdCuenta']),//$valFormPagos['cuentaCompania'.$valor],
                        str_replace(',','.',$valForm['txtMonto']),
                        $_SESSION['idEmpleadoSysGts']);
                
	$consultaCpPago = mysql_query($queryCpPago);		
	if (!$consultaCpPago){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("SET NAMES 'latin1';");
	
	$querySaldoCuenta = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valForm['hddIdCuenta']);
	$rsSaldoCuenta = mysql_query($querySaldoCuenta);
	if (!$rsSaldoCuenta){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()); }
	
	$rowSaldoCuenta = mysql_fetch_array($rsSaldoCuenta);
	
	$restoCuenta = $rowSaldoCuenta['saldo_tem'] - $valForm['txtMonto'];
	
	$queryCuentaActualiza = sprintf("UPDATE cuentas SET saldo_tem = '%s' WHERE idCuentas = '%s'", $restoCuenta, $valForm['hddIdCuenta']);
	$rsCuentaActualiza = mysql_query($queryCuentaActualiza);
	if (!$rsCuentaActualiza){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()); }	
	
	$queryEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta(id_estado_cuenta, tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales) VALUES
                                        ('', 'TR', '%s', '%s', %s, %s, '%s', '0', '%s', '1', '%s', '2');",
							$idTransferencia,
							date("Y-m-d",strtotime($valForm['txtFechaRegistro']))." ".date("H:i:s"),
							$valForm['hddIdCuenta'],
							$valForm['hddIdEmpresa'],
							str_replace(',','.',$valForm['txtMonto']),
							$valForm['numTransferencia'],
							$valForm['txtComentario']);
	mysql_query("SET NAMES 'utf8'");
	
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("SET NAMES 'latin1';");
	
	$saldoValidarFactura = round($valForm['txtSaldoFactura'] - ($valForm['txtMontoRetencionISLR'] + $valForm['txtMonto']),2);
	
	//VERIFICAR QUE EL MONTO A PAGAR NO SEA SUPERIOR AL SALDO Y TAMPOCO NEGATIVO EL SALDO
	if($valForm['hddTipoDocumento']==0){
		if($saldoValidarFactura < 0) { errorGuardarDcto($objResponse); return $objResponse->alert("El saldo de la factura no puede quedar en negativo: ".$saldoValidarFactura); }
	}else{
		if($saldoValidarFactura < 0) { errorGuardarDcto($objResponse); return $objResponse->alert("El saldo de la nota de cargo no puede quedar en negativo: ".$saldoValidarFactura); }
	}
		
	if($valForm['txtMontoRetencionISLR'] != 0){
		$queryRetencion = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_cheque, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo_documento, tipo, fecha_registro) 
		VALUES (%s, %s, %s, '%s', '%s', '%s', '%s','%s',1, '%s', NOW());",
							$valForm['txtIdFactura'], 
							$idTransferencia,                                                     
							valTpDato($valForm['selRetencionISLR'],"int"), 
							$valForm['hddBaseImponible'], 
							$valForm['hddSustraendoRetencion'], 
							$valForm['hddPorcentajeRetencion'], 
							$valForm['txtMontoRetencionISLR'],
							$valForm['hddCodigoRetencion'], 
							$valForm['hddTipoDocumento']);

		$rsRetencion = mysql_query($queryRetencion);
		if (!$rsRetencion){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$idRetencion = mysql_insert_id();
				
		$queryCpPagoISLR = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago,tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
		VALUES ('%s', '%s', 'ISLR', '%s', NOW(), '%s', '%s', '%s', '%s', '%s', '%s', %s)",
							$valForm['txtIdFactura'],
							$tipoDocumento,
							$idRetencion,
							$idRetencion,
							'-',
							CuentaBanco(1,$valForm['hddIdCuenta']),//$valFormPagos['bancoCompania'.$valor],
							'-',
							CuentaBanco(0,$valForm['hddIdCuenta']),//$valFormPagos['cuentaCompania'.$valor],
							$valForm['txtMontoRetencionISLR'],
							$_SESSION['idEmpleadoSysGts']);
				
		$consultaCpPagoISLR = mysql_query($queryCpPagoISLR);		
		if (!$consultaCpPagoISLR){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
	}

	if ($saldoValidarFactura == 0){
		$estatusFactura = "1";
	}else{
		$estatusFactura = "2";
	}
		
	if($valForm['hddTipoDocumento']==0){
		$queryUptadeFactura = sprintf("UPDATE cp_factura SET estatus_factura = '%s', saldo_factura = '%s' WHERE id_factura = %s ;", $estatusFactura, $saldoValidarFactura, $valForm['txtIdFactura']);
		
		$rsUpdateFactura = mysql_query($queryUptadeFactura);
		if (!$rsUpdateFactura){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }

	}else{
		$queryFacturaActualizaSaldo = sprintf("UPDATE cp_notadecargo SET saldo_notacargo = '%s', estatus_notacargo = '%s'  WHERE id_notacargo = '%s'", $saldoValidarFactura, $estatusFactura, $valForm['txtIdFactura']);
		$rsFacturaActualizaSaldo = mysql_query($queryFacturaActualizaSaldo);
		if(!$rsFacturaActualizaSaldo) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	//Modifcar Ernesto
	if(function_exists("generarTransferenciaTe")){
		generarTransferenciaTe($idTransferencia,"","");
	}
	//Modifcar Ernesto
	
	$objResponse->script("byId('divFlotante').style.display = 'none';
                            byId('divFlotante1').style.display = 'none';
                            xajax_buscarTransferencia(xajax.getFormValues('frmBuscar'))");
	
	return $objResponse;
}

function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT bancos.idBanco, bancos.nombreBanco, bancos.sucursal FROM bancos INNER JOIN cuentas ON (cuentas.idBanco = bancos.idBanco) WHERE bancos.idBanco != '1' GROUP BY idBanco";
	$rsBanco = mysql_query($queryBanco);
	if(!$rsBanco) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitBanco = mysql_query($queryLimitBanco);
	if(!$rsLimitBanco) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco);
		if(!$rsBanco) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsBanco);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
		$htmlTh .= ordenarCampo("xajax_listBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");					
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowBanco = mysql_fetch_assoc($rsLimitBanco)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarBanco('".$rowBanco['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['sucursal'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listBanco(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("tdDescripcionArticulo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("byId('divFlotante1').style.display = '';
						  byId('tblBancos').style.display = '';
						  byId('tdFlotanteTitulo1').innerHTML = 'Seleccione Banco';
						  centrarDiv(byId('divFlotante1'));
						  byId('tblBeneficiariosProveedores').style.display = 'none';
						  byId('tblFacturasNcargos').style.display = 'none';");
		
		
	return $objResponse;
}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
        
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa);
	if(!$rsLimitEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa);
		if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsEmpresa);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";    		
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");			
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowBanco = mysql_fetch_assoc($rsLimitEmpresa)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$rowBanco['id_empresa_reg']."',0);\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombre_empresa']." - ".$rowBanco['nombre_empresa_suc'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("tdDescripcionArticulo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$objResponse->script("byId('tblBancos').style.display = '';
						  byId('txtNombreBanco').value = '';
						  byId('txtSaldoCuenta').value = '';
						  byId('hddSaldoCuenta').value = '';
						  
						  byId('tdFlotanteTitulo1').innerHTML = 'Seleccione Empresa';
						  byId('divFlotante1').style.display = '';
						  centrarDiv(byId('divFlotante1'));
						  
						  byId('tblBeneficiariosProveedores').style.display = 'none';
						  byId('tblFacturasNcargos').style.display = 'none';");
						  
	return $objResponse;
}	

function listaFacturas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
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
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "14%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "28%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "descripcion_estado_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "2%", $pageNum, "dias_vencidos", $campOrd, $tpOrd, $valBusq, $maxRows, "D&iacute;as Vencidos");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "saldo_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
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
			$htmlTb .= "<td><button type=\"button\" onclick=\"xajax_asignarFactura('".$row['id_factura']."');\" title=\"Seleccionar Factura\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
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
	$objResponse->script("byId('tblFacturasNcargos').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Factura / Nota de Cargo");
	$objResponse->script("
		if (byId('divFlotante1').style.display == 'none') {
			byId('divFlotante1').style.display = '';
			centrarDiv(byId('divFlotante1'));
			
			document.forms['frmBuscarDocumento'].reset();
			byId('txtCriterioBusqFacturaNota').focus();
		}
	");
	
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
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCargo('".$row['id_notacargo']."');\" title=\"Seleccionar Nota Cargo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";			
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
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
	$objResponse->script("byId('tblFacturasNcargos').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Factura / Nota de Cargo");
	$objResponse->script("
		if (byId('divFlotante1').style.display == 'none') {
			byId('divFlotante1').style.display = '';
			centrarDiv(byId('divFlotante1'));
			
			document.forms['frmBuscarDocumento'].reset();
			byId('txtCriterioBusqFacturaNota').focus();
		}
	");
	
	return $objResponse;	
}

function listarBeneficiarios($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
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
						CONCAT(lci_rif,'-',ci_rif_beneficiario) AS rif_beneficiario,
						nombre_beneficiario
					FROM te_beneficiarios %s", $sqlBusq);

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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios", "20%", $pageNum, "rif_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula / RIF.");
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios", "65%", $pageNum, "nombre_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
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
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" ".$onclickAsignar." title=\"Seleccionar Beneficiario\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_beneficiario']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarBeneficiarios(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("tdContenido","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("byId('tblBeneficiariosProveedores').style.display = '';");	

	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (byId('divFlotante1').style.display == 'none') {
			byId('divFlotante1').style.display = '';
			centrarDiv(byId('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			byId('txtCriterioBusqBeneficiario').focus();
		}
	");
	
	return $objResponse;
}

function listadoTransferencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$objResponse->setCharacterEncoding('UTF-8');
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	$acc= $_GET['acc'];
	
	if ($_GET['acc'] == 1){
		$objResponse->script("byId('btnNuevo').style.display = '';");
		$cadenaFiltro = " WHERE id_propuesta_pago = 0 AND impreso <> 1";
		$objResponse->assign("tdReferenciaPagina","innerHTML","Transferencias Individuales");
		$cadenaTd = "<td width=\"1%\"></td>";
		$colspan = 13;
		$auxTd = true;
	}else if ($_GET['acc'] == 2){
		$objResponse->script("byId('btnNuevo').style.display = 'none';");
		$cadenaFiltro = " WHERE id_propuesta_pago > 0 AND impreso <> 1";
		$objResponse->assign("tdReferenciaPagina","innerHTML","Transferencia En Lote");
		$cadenaTd = "<td width=\"1%\"></td>";
		$colspan = 13;
		$auxTd = true;
	}else{
		$objResponse->script("byId('btnNuevo').style.display = 'none';");
		$cadenaFiltro = " WHERE impreso = 1";
		$objResponse->assign("tdReferenciaPagina","innerHTML","Historico Transferencias");		
		$cadenaTd = "<td width=\"1%\"></td><td width=\"1%\"></td>";

		$colspan = 13;
		$auxTd = false;
	}
	
	if($valCadBusq[0] != 0){
		if($valCadBusq[0] == -1){
			if($cadenaFiltro == ""){
				$cadenaFiltro = sprintf(" WHERE id_empresa = %s",$_SESSION['idEmpresaUsuarioSysGts']);
			}else{
				$cadenaFiltro .= sprintf(" AND id_empresa = %s",$_SESSION['idEmpresaUsuarioSysGts']);
			}
		}else{
			if($cadenaFiltro == ""){
				$cadenaFiltro = sprintf(" WHERE id_empresa = %s",$valCadBusq[0]);
			}else{
				$cadenaFiltro .= sprintf(" AND id_empresa = %s",$valCadBusq[0]);
			}
		}
	}
	
	if($valCadBusq[1] != 0){
		if($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE estado_documento = %s",$valCadBusq[1]);
		}else{
			$cadenaFiltro .= sprintf(" AND estado_documento = %s",$valCadBusq[1]);
		}
	}
	
	if($valCadBusq[2] != ""){
		if($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE numero_transferencia LIKE '%s'",'%%'.$valCadBusq[2].'%%');
		}else{
			$cadenaFiltro .= sprintf(" AND numero_transferencia LIKE '%s'",'%%'.$valCadBusq[2].'%%');
		}
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[4] !=""){
		if ($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE fecha_registro BETWEEN '%s' AND '%s'",
                                date("Y-m-d",strtotime($valCadBusq[3])),
                                date("Y-m-d",strtotime($valCadBusq[4])));
		}else{
			$cadenaFiltro .= sprintf(" AND fecha_registro BETWEEN '%s' AND '%s'",
					date("Y-m-d",strtotime($valCadBusq[3])),
					date("Y-m-d",strtotime($valCadBusq[4])));
		}			
	}
        
	if ($valCadBusq[5] != ""){
		if ($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE id_beneficiario_proveedor = '%s'",$valCadBusq[5]);
		}else{
			$cadenaFiltro .= sprintf(" AND id_beneficiario_proveedor = '%s'",$valCadBusq[5]);
		}
	}
	
	if ($valCadBusq[6] != ""){
		if ($cadenaFiltro == ""){
			$cadenaFiltro = sprintf(" WHERE concepto LIKE %s",
								valTpDato("%%".$valCadBusq[6]."%%","text"));
		}else{
			$cadenaFiltro .= sprintf(" AND concepto LIKE %s",
								valTpDato("%%".$valCadBusq[6]."%%","text"));
		}
	}
	
	$query = sprintf("SELECT * FROM vw_te_transferencia".$cadenaFiltro);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
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
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "10%", $pageNum, "id_beneficiario_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "10%", $pageNum, "numero_transferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Transferencia");
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "8%", $pageNum, "monto_transferencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Transferencia");
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "7%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "7%", $pageNum, "fecha_aplicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "7%", $pageNum, "fecha_conciliacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Conciliaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "100%", $pageNum, "concepto", $campOrd, $tpOrd, $valBusq, $maxRows, ("Concepto"));
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "15%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, ("Banco Compa&ntilde;ia"));
		$htmlTh .= ordenarCampo("xajax_listadoTransferencia", "15%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cuenta Compa&ntilde;ia"));
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$queryRetencion = sprintf("SELECT id_retencion_cheque FROM te_retencion_cheque WHERE id_cheque = %s AND tipo_documento=1",valTpDato($row['id_transferencia'],"int"));
		$rsRetencion = mysql_query($queryRetencion);
		if (!$rsRetencion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		if (mysql_num_rows($rsRetencion)){
			$tieneRetencion = "verVentana('reportes/te_imprimir_constancia_retencion_pdf.php?id=".$row['id_transferencia']."&documento=2',700,700);";
		}else{
			$tieneRetencion = "";
		}
		
		if ($row['fecha_aplicacion'] == null){
			$fechaAplicacion = "-";
		}else{
			$fechaAplicacion = date(spanDateFormat,strtotime($row['fecha_aplicacion']));
		}
		
		if ($row['fecha_conciliacion'] == null){
			$fechaConciliacion = "-";
		}else{
			$fechaConciliacion = date(spanDateFormat,strtotime($row['fecha_conciliacion']));
		}
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".estadoDocumento($row['estado_documento'])."</td>";
			$htmlTb .= "<td align=\"center\">".empresa($row['id_empresa'])."</td>";
			$htmlTb .= "<td>".NombreBP($row['beneficiario_proveedor'],$row['id_beneficiario_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_transferencia']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_transferencia'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaAplicacion."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaConciliacion."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['concepto'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroCuentaCompania']."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Ver Transferencia\"><img class=\"puntero\" onclick=\"xajax_verTransferencia(".$row['id_transferencia']."); \" src=\"../img/iconos/ico_view.png\" ></td>";
			$htmlTb .= "<td align=\"center\" title=\"Imprimir\"><img class=\"puntero\" src=\"../img/iconos/ico_print.png\" <img class=\"puntero\" onclick=\"window.open('te_impresion_transferencia.php?id=".$row['id_transferencia']."&acc=".$acc."','_self'); ".$tieneRetencion."\" src=\"../img/iconos/ico_print.png\"></td>";
			
		if ($_GET['acc'] == 3){
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Anular Transferencia\">"."<img src=\"../img/iconos/ico_quitar.gif\" onclick=\"if (confirm('Desea anular la Transferencia ".$row['numero_transferencia']."?') == true){
				byId('divFlotanteAnular').style.display = '';
				centrarDiv(byId('divFlotanteAnular'));
				byId('tdFlotanteTitulo').innerHTML = 'Eliminar Transferencia';
				byId('txtNumTransferencia').value = '".$row['numero_transferencia']."';
				byId('hddIdTransferencia').value = '".$row['id_transferencia']."';}\"/>"."</td>";
			$sPar = "idobject=".$row['id_transferencia'];
				 $sPar.= "&ct=14";
				 $sPar.= "&dt=04";
				 $sPar.= "&cc=05";
			// Modificado Ernesto
			$htmlTb .= "<td  align=\"center\">";
				$htmlTb .= "<img onclick=\"verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?$sPar', 1000, 500);\" src=\"../img/iconos/new_window.png\" title=\"Ver Movimiento Contable\"/>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";	
		}
			
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"18\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTransferencia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoTransferencia","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listarProveedores($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	//$valCadBusq[0] criterio
	//$valCadBusq[1] == "1" si es para buscar en el listado        
	$buscarListado = $valCadBusq[1];
	
	$sqlBusq = sprintf(" WHERE CONCAT_WS('-',lrif,rif) LIKE %s
							OR CONCAT_WS('',lrif,rif) LIKE %s
							OR nombre LIKE %s",
				valTpDato("%".$valCadBusq[0]."%", "text"),
				valTpDato("%".$valCadBusq[0]."%", "text"),
				valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
						id_proveedor AS id,
						CONCAT_WS('-',lrif,rif) as rif_proveedor,
						nombre
					FROM cp_proveedor %s", 
				$sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula / RIF.");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if($buscarListado == "1"){
			$onclickAsignar = "onclick=\"xajax_asignarProveedor2('".$row['id']."');\"";//busqueda en listado
		}else{
			$onclickAsignar = "onclick=\"xajax_asignarProveedor('".$row['id']."');\"";
		}
				
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" ".$onclickAsignar."  title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_proveedor']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarProveedores(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("tdContenido","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("byId('tblBeneficiariosProveedores').style.display = '';");
	
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (byId('divFlotante1').style.display == 'none') {
			byId('divFlotante1').style.display = '';
			centrarDiv(byId('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			byId('txtCriterioBusqProveedor').focus();
		}
	");
	
	return $objResponse;
}

function verTransferencia($idTransferencia){
	$objResponse = new xajaxResponse();

	$queryTransferencia = sprintf("SELECT * FROM te_transferencia WHERE id_transferencia = %s",$idTransferencia);
	$rsTransferencia = mysql_query($queryTransferencia);
	$rowTransferencia = mysql_fetch_array($rsTransferencia);

	$objResponse->script("xajax_asignarEmpresa(".$rowTransferencia['id_empresa'].",1)");
	$objResponse->script("xajax_asignarDetallesCuenta(".$idTransferencia.")");
	
	if($rowTransferencia['beneficiario_proveedor'] == 0){
		$objResponse->script("xajax_asignarBeneficiario(".$rowTransferencia['id_beneficiario_proveedor'].")");
	}else{
		$objResponse->script("xajax_asignarProveedor(".$rowTransferencia['id_beneficiario_proveedor'].",'SI')");
	}
	
	$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat,strtotime($rowTransferencia['fecha_registro'])));
	$objResponse->assign("numTransferencia","value",$rowTransferencia['numero_transferencia']);
	$objResponse->assign("txtNumCuenta","value",$rowTransferencia['num_cuenta']);
	$objResponse->assign("hddIdCuenta","value",$rowTransferencia['id_cuenta']);
	$objResponse->assign("txtComentario","value",utf8_encode($rowTransferencia['observacion']));
	$objResponse->assign("txtMonto","value",number_format($rowTransferencia['monto_transferencia'],'2',',','.'));	

	if($rowTransferencia['id_documento']){
		if($rowTransferencia['tipo_documento']==0){//factura
		
			$queryFactura = sprintf("SELECT numero_factura_proveedor,	fecha_origen, fecha_vencimiento, observacion_factura FROM cp_factura WHERE id_factura = '%s'",$rowTransferencia['id_documento']);
			$rsFactura = mysql_query($queryFactura);			
			if (!$rsFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			$rowFactura = mysql_fetch_assoc($rsFactura);
			
			$objResponse->assign("txtIdFactura","value",$rowTransferencia['id_documento']);
			$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
			$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_origen'])));
			$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_vencimiento'])));
			$objResponse->assign("txtDescripcionFactura","innerHTML",$rowFactura['observacion_factura']);
			$objResponse->assign("txtSaldoFactura","value",number_format($rowTransferencia['monto_transferencia'],'2',',','.'));
			$objResponse->assign("tdFacturaNota","innerHTML","FACTURA");
			
			$objResponse->script("byId('tdTxtSaldoFactura').style.display = 'none';
					  byId('tdSaldoFactura').style.display = 'none';");
                    
		}else{//nota de cargo
			
			$queryNotaCargo = sprintf("SELECT * FROM cp_notadecargo WHERE id_notacargo = '%s'",$rowTransferencia['id_documento']);
			$rsNotaCargo = mysql_query($queryNotaCargo);
			if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);

			$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);

			$objResponse->assign("txtIdFactura","value",$rowTransferencia['id_documento']);
			$objResponse->assign("txtNumeroFactura","value",utf8_encode($rowNotaCargo['numero_notacargo']));
			$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat,strtotime($rowNotaCargo['fecha_origen_notacargo'])));
			$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($rowNotaCargo['fecha_vencimiento_notacargo'])));
			$objResponse->assign("txtDescripcionFactura","innerHTML",$rowNotaCargo['observacion_notacargo']);
			$objResponse->assign("txtSaldoFactura","value",number_format($rowTransferencia['monto_transferencia'],'2',',','.'));
			$objResponse->assign("tdFacturaNota","innerHTML","NOTA DE CARGO");

			$objResponse->script("byId('tdTxtSaldoFactura').style.display = 'none';
					  byId('tdSaldoFactura').style.display = 'none';");
		}
                
	}else{//sino posee documento
		$objResponse->assign("txtIdFactura","value","");
		$objResponse->assign("txtNumeroFactura","value","");
		$objResponse->assign("txtFechaRegistroFactura","value","");
		$objResponse->assign("txtFechaVencimientoFactura","value","");
		$objResponse->assign("txtDescripcionFactura","innerHTML","");
		$objResponse->assign("txtSaldoFactura","value","");
		
		$queryTienePropuesta = sprintf("SELECT id_propuesta_pago FROM te_propuesta_pago_transferencia WHERE id_transfererencia = %s LIMIT 1",
								$idTransferencia);
		$rsTienePropuesta = mysql_query($queryTienePropuesta);
		if (!$rsTienePropuesta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		if(mysql_num_rows($rsTienePropuesta)){
			$rowTienePropuesta = mysql_fetch_assoc($rsTienePropuesta);
			$imagen = sprintf("<img src='../img/iconos/ico_view.png' style='vertical-align: top;' onclick='limpiarPropuesta(); xajax_verPropuesta(%s);' class='puntero'>",
							valTpDato($rowTienePropuesta['id_propuesta_pago'],"int"));
			$objResponse->assign("tdFacturaNota","innerHTML","PROPUESTA DE PAGO ".$imagen);
			
		}else{
			$objResponse->assign("tdFacturaNota","innerHTML","SIN DOCUMENTO");
		}
	}
	
	$objResponse->script("byId('btnAceptar').style.display = 'none';
						  byId('btnActualizar').style.display = 'none';
						  byId('divFlotante').style.display = '';
						  centrarDiv(byId('divFlotante'));
						  byId('tdFlotanteTitulo').innerHTML = 'Ver Transferencia';
						  byId('trSaldoCuenta').style.display = 'none';");
	
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

function buscarCliente($valform,$pro_bene) {
	$objResponse = new xajaxResponse();
        	
	if($pro_bene=="1"){
		$valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']."|".$valform['buscarListado']);
		$objResponse->loadCommands(listarProveedores(0, "", "", $valBusq));
	}elseif($pro_bene=="0"){
		$valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']."|".$valform['buscarListado']);
		$objResponse->loadCommands(listarBeneficiarios(0, "", "", $valBusq));
	}else{//boton buscar pro_bene es null porque no se envia
		if($valform['buscarProv']=="1"){
			$valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']."|".$valform['buscarListado']);
			$objResponse->loadCommands(listarProveedores(0, "", "", $valBusq));
		}elseif($valform['buscarProv']=="2"){
			$valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']."|".$valform['buscarListado']);
			$objResponse->loadCommands(listarBeneficiarios(0, "", "", $valBusq));
		}
	}
	
	return $objResponse;
}

function buscarDocumento($frmBuscar,$idEmpresa,$idProveedor,$facturaNota) {
	$objResponse = new xajaxResponse();
	
	if($facturaNota == "2"){//FACTURA
		$objResponse->script(sprintf("xajax_listaFacturas(0,'','', '%s|%s|%s|%s|%s')",
								$idEmpresa,
								$idProveedor,
								$frmBuscar['lstModulo'],
								((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
								$frmBuscar['txtCriterioBusqFacturaNota']));
	}elseif($facturaNota == "1"){//NOTA
		$objResponse->script(sprintf("xajax_listaNotaCargo(0,'','', '%s|%s|%s|%s|%s')",
								$idEmpresa,
								$idProveedor,
								$frmBuscar['lstModulo'],
								((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
								$frmBuscar['txtCriterioBusqFacturaNota']));
	}
	
	return $objResponse;
}

function verificarClave($valForm){
	$objResponse = new xajaxResponse();
	
	$queryClave = sprintf("SELECT contrasena FROM vw_pg_claves_modulos WHERE id_usuario = %s AND id_clave_modulo = 34",
					valTpDato($_SESSION['idUsuarioSysGts'],'int'));
	$rsClave = mysql_query($queryClave);
	if (!$rsClave) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);
	
	if (mysql_num_rows($rsClave)){
		$rowClave = mysql_fetch_array($rsClave);
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



function verificarClaveAnular($valForm){
	$objResponse = new xajaxResponse();
	
	$queryClave = sprintf("SELECT contrasena FROM vw_pg_claves_modulos WHERE id_usuario = %s AND id_clave_modulo = 47",
					valTpDato($_SESSION['idUsuarioSysGts'],'int'));
	$rsClave = mysql_query($queryClave);	
	if (!$rsClave) return $objResponse->alert(mysql_error()."\n\nLINE: "._LINE_);
	
	if (mysql_num_rows($rsClave)){
		$rowClave = mysql_fetch_array($rsClave);
		if ($rowClave['contrasena'] == $valForm['txtClaveAnular']){
			$objResponse->script("antesAnular('".$valForm['hddIdTransferencia']."');");
			$objResponse->script("byId('divFlotanteAnular').style.display = 'none';");
		}else{
			$objResponse->alert("Clave Errada.");
		}
	}else{
		$objResponse->alert("No tiene permiso para realizar esta accion");
		$objResponse->script("byId('divFlotanteAnular').style.display = 'none';");
	}
	
	return $objResponse;
}

function verPropuesta($idPropuesta){    
	$objResponse = new xajaxResponse();
	
	$queryPropuesta = sprintf("SELECT 
								te_propuesta_pago_transferencia.fecha_propuesta_pago, 
								te_propuesta_pago_transferencia.estatus_propuesta, 
								te_propuesta_pago_transferencia.num_cta_transferencia,
								te_transferencia.numero_transferencia 
							FROM te_propuesta_pago_transferencia
							INNER JOIN te_transferencia ON (te_propuesta_pago_transferencia.id_transfererencia = te_transferencia.id_transferencia) 
							WHERE te_propuesta_pago_transferencia.id_propuesta_pago = %s LIMIT 1",
					   $idPropuesta);
    
    $rsPropuesta = mysql_query($queryPropuesta);
    if (!$rsPropuesta) { return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
    
    $rowPropuesta = mysql_fetch_assoc($rsPropuesta);
    
    $queryDetalle = sprintf("SELECT 
								te_propuesta_pago_detalle_transferencia.id_factura, 
								te_propuesta_pago_detalle_transferencia.monto_pagar, 
								te_propuesta_pago_detalle_transferencia.sustraendo_retencion, 
								te_propuesta_pago_detalle_transferencia.porcentaje_retencion, 
								te_propuesta_pago_detalle_transferencia.monto_retenido, 
								te_propuesta_pago_detalle_transferencia.codigo, 
								te_propuesta_pago_detalle_transferencia.tipo_documento,
								
								IF(te_propuesta_pago_detalle_transferencia.tipo_documento = 0, 
									(SELECT numero_factura_proveedor FROM cp_factura WHERE cp_factura.id_factura = te_propuesta_pago_detalle_transferencia.id_factura),
									(SELECT numero_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_propuesta_pago_detalle_transferencia.id_factura)) as numero_documento
									
                            FROM te_propuesta_pago_detalle_transferencia 
                            WHERE id_propuesta_pago = %s",
                        $idPropuesta);
    
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
    $objResponse->assign("numeroTransferenciaPropuestaPago","innerHTML",$rowPropuesta['numero_transferencia']);
    $objResponse->assign("estadoPropuestaPago","innerHTML",$estado);
    
    $objResponse->assign("detallePropuestaPago","innerHTML",$tabla);
    
    
    $objResponse->script("byId('divFlotante3').style.display = '';
			centrarDiv(byId('divFlotante3'));");
			
    return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"asignarNotaCargo");
$xajax->register(XAJAX_FUNCTION,"listaNotaCargo");
$xajax->register(XAJAX_FUNCTION,"buscarDocumento");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"actualizarTransferencia");
$xajax->register(XAJAX_FUNCTION,"anularTransferencia");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBeneficiario");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesCuenta");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarFactura");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor2");//listado busqueda
$xajax->register(XAJAX_FUNCTION,"buscarTransferencia");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"comboEmpresa");
$xajax->register(XAJAX_FUNCTION,"comboEstado");
$xajax->register(XAJAX_FUNCTION,"comboRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"editarTransferencia");
$xajax->register(XAJAX_FUNCTION,"guardarTransferencia");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"listarBeneficiarios");
$xajax->register(XAJAX_FUNCTION,"listadoTransferencia");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaFacturas");
$xajax->register(XAJAX_FUNCTION,"listarProveedores");
$xajax->register(XAJAX_FUNCTION,"verTransferencia");
$xajax->register(XAJAX_FUNCTION,"verificarClave");
$xajax->register(XAJAX_FUNCTION,"verificarClaveAnular");
$xajax->register(XAJAX_FUNCTION,"verificarRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"tieneImpuesto");
$xajax->register(XAJAX_FUNCTION,"verPropuesta");

function NombreBP($Bene_Prove,$id){
	if ($Bene_Prove == 1){		
		$query = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = '%s'",$id);
		$rs = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_array($rs);
		
		$respuesta = utf8_encode($row['nombre']);
	}else{		
		$query = sprintf("SELECT nombre_beneficiario FROM te_beneficiarios WHERE id_beneficiario = '%s'",$id);
		$rs = mysql_query($query) or die(mysql_error());
		$row = mysql_fetch_array($rs);
		
		$respuesta = utf8_encode($row['nombre_beneficiario']);		
	}
		
	return $respuesta;
}


function empresa($id){
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombre_empresa']);
	
	return $respuesta;
}

function estadoDocumento($id){

	$query = sprintf("SELECT * FROM te_estados_principales WHERE id_estados_principales = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['id_estados_principales'] == 1){
		$respuesta .= " <img src=\"../img/iconos/ico_rojo.gif\">";
	}elseif($row['id_estados_principales'] == 2){
		$respuesta .= " <img src=\"../img/iconos/ico_amarillo.gif\">";
	}elseif($row['id_estados_principales'] == 3){
		$respuesta .= " <img src=\"../img/iconos/ico_verde.gif\">";
	}
	
	return $respuesta;
}

function cambioImg($id,$acc,$tieneRetencion){
	
	$sql = sprintf("SELECT * FROM te_cheques WHERE id_cheque = '%s'",$id);
	$rs = mysql_query($sql);
	$row = mysql_fetch_array($rs);
	
	if($row['impresion'] == 1){
		$img = "<img class=\"puntero\" onclick=\"xajax_editarCheque(".$row['id_cheque'].")\" src=\"../img/iconos/ico_edit.png\" >";
	}else{
		$img = "<img class=\"puntero\" onclick=\"window.open('te_impresion_cheque.php?acc=".$acc."&id=".$row['id_cheque']."','_self'); ".$tieneRetencion."\" src=\"../img/iconos/ico_print.png\">";
	}
	
	return $img;
}


function CuentaBanco($clave,$id){
	
	$query = sprintf("SELECT 
						cuentas.numeroCuentaCompania,
						bancos.nombreBanco 
					FROM cuentas
					INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco) 
					WHERE cuentas.idCuentas = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if ($clave == 1){
		$respuesta = $row['nombreBanco'];	
	}else{
		$respuesta = $row['numeroCuentaCompania'];
	}
	
	return utf8_encode($respuesta);
}

function errorGuardarDcto($objResponse){
	$objResponse->script("desbloquearGuardado();");
}

?>