<?php

function aprobarPropuesta($idPropuesta,$aux){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("desbloquearGuardado();");
        
	mysql_query("START TRANSACTION;");
	
	$queryPropuesta = sprintf("SELECT id_propuesta_pago, id_cuenta, num_cta_transferencia, monto_pagar, id_proveedor, id_empresa, saldo_tem,idCuentas FROM vw_te_propuesta_pago_transferencia WHERE id_propuesta_pago = %s",$idPropuesta['hddIdPropuestaA']);
	$rsPropuesta = mysql_query($queryPropuesta);	
	if (!$rsPropuesta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);	
	$rowPropuesta = mysql_fetch_array($rsPropuesta);
	
	$queryMonto = sprintf("SELECT SUM(monto_pagar) AS monto FROM te_propuesta_pago_detalle_transferencia WHERE id_propuesta_pago = %s",$rowPropuesta['id_propuesta_pago']);
	$rsMonto = mysql_query($queryMonto);
	if (!$rsMonto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowMonto = mysql_fetch_array($rsMonto);
	
	
	$queryFolioTransferencia = "SELECT numero_actual FROM te_folios WHERE id_folios = 5";
	$rsFolioTransferencia = mysql_query($queryFolioTransferencia);	
	if (!$rsFolioTransferencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);	
	$rowFolioTransferencia = mysql_fetch_array($rsFolioTransferencia);
	
	$updateFolioTransferencia = sprintf("UPDATE te_folios SET numero_actual = %s WHERE id_folios = 5;",$rowFolioTransferencia['numero_actual']+1);
	$rsUpdateFolioTransferencia = mysql_query($updateFolioTransferencia);	
	if (!$rsUpdateFolioTransferencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
	$queryTransferencia = sprintf("INSERT INTO te_transferencia(id_transferencia, numero_transferencia, num_cuenta, folio_tesoreria, beneficiario_proveedor, id_beneficiario_proveedor, fecha_registro, observacion, monto_transferencia, id_cuenta, estado_documento, fecha_conciliacion, fecha_aplicacion, id_empresa, desincorporado, id_usuario, id_documento) VALUES
	('' , '%s', '%s', '%s', 1, %s, '%s', '%s', '%s', '%s', '2', NULL , '%s' , %s, '1', %s, '');",
		$idPropuesta['NumeroTransferencia'],
		$rowPropuesta['num_cta_transferencia'],
		$rowFolioTransferencia['numero_actual'],
		$rowPropuesta['id_proveedor'],
		date("Y-m-d",strtotime($idPropuesta['txtFechaRegistro'])),
		$idPropuesta['txtObservacionTr'],
		$rowMonto['monto'],
		$rowPropuesta['idCuentas'],
		date("Y-m-d",strtotime($idPropuesta['txtFechaRegistro'])),
		$rowPropuesta['id_empresa'],
		$_SESSION['idUsuarioSysGts']);
	mysql_query("SET NAMES 'utf8'");
	$rsTransferencia = mysql_query($queryTransferencia);
	if (!$rsTransferencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$idTransferencia = mysql_insert_id();
	
	$queryUpdate = sprintf("UPDATE te_propuesta_pago_transferencia SET estatus_propuesta = 1, id_transfererencia = %s WHERE id_propuesta_pago = %s",$idTransferencia,$idPropuesta['hddIdPropuestaA']);
	$rsUpdate = mysql_query($queryUpdate);	
	if (!$rsUpdate) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$queryCuenta = sprintf("SELECT saldo_tem, Diferido FROM cuentas WHERE idCuentas = %s",$rowPropuesta['idCuentas']);
	$rsCuenta = mysql_query($queryCuenta);
	if (!$rsCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_array($rsCuenta);
	
	$saldoTem = $rowCuenta['saldo_tem'] - str_replace(',','.',$rowMonto['monto']);
	$Diferido = $rowCuenta['Diferido'] - str_replace(',','.',$rowMonto['monto']);

	$updateCuenta = sprintf("UPDATE cuentas SET saldo_tem = '%s', Diferido = '%s' WHERE idCuentas = %s ;",$saldoTem,$Diferido, $rowPropuesta['idCuentas']);
	$rsUpdateCuenta = mysql_query($updateCuenta);
	if (!$rsUpdateCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("SET NAMES 'latin1';");
	
	$queryEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta(id_estado_cuenta, tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales) VALUES
								('', 'TR', '%s', '%s', %s, %s, '%s', '0', '%s', '1', '%s', '2');",
								$idTransferencia,
								date("Y-m-d",strtotime($idPropuesta['txtFechaRegistro']))." ".date("H:i:s"),
								$rowPropuesta['idCuentas'],
								$rowPropuesta['id_empresa'],
								$rowMonto['monto'],
								$idPropuesta['NumeroTransferencia'],
								$idPropuesta['txtObservacionTr']);
	
	mysql_query("SET NAMES 'utf8'");
	
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if(!$rsEstadoCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("SET NAMES 'latin1';");
	
	$queryPropuestaDetalle = sprintf("SELECT * FROM te_propuesta_pago_detalle_transferencia WHERE id_propuesta_pago = %s",
								$idPropuesta['hddIdPropuestaA']);
	$rsPropuestaDetalle = mysql_query($queryPropuestaDetalle);
	if(!$rsPropuestaDetalle){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	while ($rowPropuestaDetalle = mysql_fetch_array($rsPropuestaDetalle)){
		
		if ($rowPropuestaDetalle['tipo_documento']==0){
			$tipoDocumento='FA';
		}else{
			$tipoDocumento='ND';
		}

		$queryCpPago = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago,tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) VALUES ('%s', '%s', 'Transferencia', '%s', NOW(), '%s', '%s', '%s', '%s', '%s', '%s', %s)",
								$rowPropuestaDetalle['id_factura'],
								$tipoDocumento,
								$idTransferencia,
								$idPropuesta['NumeroTransferencia'],
								'-',
								CuentaBanco(1,$rowPropuesta['idCuentas']),
								'-',
								CuentaBanco(0,$rowPropuesta['idCuentas']),
								$rowPropuestaDetalle['monto_pagar'],
								$_SESSION['idEmpleadoSysGts']);
                    
		$consultaCpPago = mysql_query($queryCpPago);		
		if (!$consultaCpPago){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		if ($rowPropuestaDetalle['monto_retenido'] != 0){
			
			$queryRetencion = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_cheque, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo_documento, tipo, fecha_registro) 
			VALUES (%s, %s, %s, '%s', '%s', '%s', '%s', '%s', 1, '%s', NOW());",
								$rowPropuestaDetalle['id_factura'], 
								$idTransferencia,
								valTpDato($rowPropuestaDetalle['id_retencion'],"int"), 
								$rowPropuestaDetalle['base_imponible_retencion'], 
								$rowPropuestaDetalle['sustraendo_retencion'], 
								$rowPropuestaDetalle['porcentaje_retencion'], 
								$rowPropuestaDetalle['monto_retenido'], 
								$rowPropuestaDetalle['codigo'], 
								$rowPropuestaDetalle['tipo_documento']);
			
			$rsRetencion = mysql_query($queryRetencion);
			if (!$rsRetencion){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                            
			if ($rowPropuestaDetalle['tipo_documento']==0){
				$tipoDocumento='FA';
			}else{
				$tipoDocumento='ND';
			}
                            
			$idRetencion = mysql_insert_id();		
			$queryCpPagoISLR = sprintf ( "INSERT INTO cp_pagos_documentos(id_documento_pago,tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
			VALUES ('%s', '%s', 'ISLR', '%s', NOW(), '%s', '%s', '%s', '%s', '%s', '%s', %s)",
								$rowPropuestaDetalle['id_factura'],
								$tipoDocumento,
								$idRetencion,
								$idRetencion,
								'-',
								CuentaBanco(1,$rowPropuestaDetalle['id_factura']),
								'-',
								CuentaBanco(0,$rowPropuestaDetalle['id_factura']),
								$rowPropuestaDetalle['monto_retenido'],
								$_SESSION['idEmpleadoSysGts']);

			$consultaCpPagoISLR = mysql_query($queryCpPagoISLR);		
			if (!$consultaCpPagoISLR){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		}

		if($rowPropuestaDetalle['tipo_documento']==0){//FACTURA
			$queryFactura = sprintf("SELECT numero_factura_proveedor, saldo_factura FROM cp_factura WHERE id_factura = %s",$rowPropuestaDetalle['id_factura']);
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

			$rowFactura = mysql_fetch_array($rsFactura);
                        
			$saldoValidarFactura = round(round($rowFactura['saldo_factura'],2) - ($rowPropuestaDetalle['monto_retenido'] + $rowPropuestaDetalle['monto_pagar']),2);

			if($saldoValidarFactura < 0){
				return $objResponse->alert("El saldo de la factura Nro ".$rowFactura['numero_factura_proveedor']." no puede quedar en negativo: ".$saldoValidarFactura);                                
			}
                        
			if($saldoValidarFactura == 0){
				$estatusFactura = "1";
			}else{
				$estatusFactura = "2";
			}

			$queryUptadeFactura = sprintf("UPDATE cp_factura SET estatus_factura = '%s', saldo_factura = '%s' WHERE id_factura = %s ;",$estatusFactura, $saldoValidarFactura, $rowPropuestaDetalle['id_factura']);
			$rsUpdateFactura = mysql_query($queryUptadeFactura);
			if (!$rsUpdateFactura){ return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }

		}else{//NOTA DE CARGO
				
			$queryNota = sprintf("SELECT numero_notacargo, saldo_notacargo  FROM cp_notadecargo WHERE id_notacargo = %s",$rowPropuestaDetalle['id_factura']);
			$rsNota = mysql_query($queryNota);
			if(!$rsNota){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }					
			
			$rowNota = mysql_fetch_array($rsNota);
			
			$saldoValidarNota = round(round($rowNota['saldo_notacargo'],2) - ($rowPropuestaDetalle['monto_retenido'] + $rowPropuestaDetalle['monto_pagar']),2);
				
			if($saldoValidarNota < 0){
				return $objResponse->alert("El saldo de la nota debito Nro ".utf8_encode($rowNota['numero_notacargo'])." no puede quedar en negativo: ".$saldoValidarNota);                                
			}
                        
			if($saldoValidarNota == 0){
				$estatusNota = "1";
			}else{
				$estatusNota = "2";
			}
						
			$queryUptadeNota = sprintf("UPDATE cp_notadecargo SET estatus_notacargo = '%s', saldo_notacargo = '%s' WHERE id_notacargo = %s ;",$estatusNota, $saldoValidarNota, $rowPropuestaDetalle['id_factura']);
			$rsUpdateNota = mysql_query($queryUptadeNota);
			if(!$rsUpdateNota){ return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }

		}
		
	}//fin while
	
	mysql_query("COMMIT;");
        
	//Modifcar Ernesto
	if(function_exists("generarTransferenciaTe")){
		generarTransferenciaTe($idTransferencia,"","");
	}
	//Modifcar Ernesto
	
	$objResponse->alert("Propuesta aprobada exitosamente");
	$objResponse->script("document.getElementById('divTransferencia').style.display = 'none'");
	$objResponse->script("document.getElementById('btnBuscar').click();");
	
	return $objResponse;
}

function asignarBanco($id_banco){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	$objResponse->assign("txtSaldoCuenta","value","");
	$objResponse->assign("hddSaldoCuenta","value","");
	$objResponse->assign("hddIdChequera","value","");
	
	$objResponse->script("document.getElementById('txtNombreBanco').className = 'inputInicial';
						  document.getElementById('divFlotante1').style.display = 'none';
						  xajax_comboCuentas(xajax.getFormValues('frmBuscar'),".$row['idBanco'].",0);");
	
	return $objResponse;
}

function asignarEmpresa($idEmpresa,$accion,$idProveedor){
	$objResponse = new xajaxResponse();
	$idEmpresa = ($idEmpresa == 0) ? $_SESSION['idEmpresaUsuarioSysGts'] : $idEmpresa;
	
	$idProveedor = ($idProveedor == "") ? 0 : $idProveedor;
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$nombreSucursal = "";
	
	if ($rowEmpresa['id_empresa_padre_suc'] > 0){
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";
	}
	
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse -> assign("txtNombreEmpresa","value",$empresa);
	$objResponse -> assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	if ($accion == 0){
		$objResponse->assign("txtNombreBanco","value","");
		$objResponse->assign("hddIdBanco","value","-1");
		$objResponse->assign("txtSaldoCuenta","value","");
		$objResponse->assign("hddSaldoCuenta","value","");
		$objResponse->assign("hddIdChequera","value","");
	}
	
	$objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarProveedor($id_proveedor){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$id_proveedor."'";
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtProveedorCabecera","value",utf8_encode($row['nombre']));
	$objResponse->assign("hddIdProveedorCabecera","value",$row['id_proveedor']);
	$objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");
	$objResponse->script("xajax_buscarFactura(xajax.getFormValues('frmBuscar'));");
	
	return $objResponse;
}

function buscarProveedor($valForm){
    $objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listarProveedores(0,'','','%s');",
		$valForm['txtCriterioBusq']));
		
	return $objResponse;
}

function buscarPropuesta($valForm){
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listarPropuestas(0,'','','%s|%s|%s|%s');",
		$valForm['hddIdEmpresa'],
		$valForm['hddIdProveedorCabecera'],
		$valForm['hddIdBanco'],
		$valForm['selCuenta']));
		
	return $objResponse;
}

function comboCuentas($valForm,$idBanco = "",$idChequera = ""){
	$objResponse = new xajaxResponse();
	
	if($idChequera != 0){
		$queryIdCuenta = sprintf("SELECT id_cuenta FROM te_chequeras WHERE id_chq = %s",
			valTpDato($idChequera,"int"));
		$rsIdCuenta = mysql_query($queryIdCuenta);		
		if (!$rsIdCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$rowIdCuenta = mysql_fetch_array($rsIdCuenta);
		$idCuenta = $rowIdCuenta['id_cuenta'];
	}else{
		$idCuenta = 0;
	}
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}else{
		$condicion = "WHERE idBanco = '".$idBanco."' AND id_empresa = '".$valForm['hddIdEmpresa']."'";
		$disabled = "";
	}
	
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" class=\"inputHabilitado\" ".$disabled."\">";
	$html .= "<option value=\"-1\">Seleccione</option>";
	while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
		if ($rowCuentas['idCuentas'] == $idCuenta){
			$selected = "selected='selected'";
		}else{
			$selected = "";
		}			
		$html .= "<option value=\"".$rowCuentas['idCuentas']."\" ".$selected.">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentas","innerHTML",$html);
	
	return $objResponse;
}

function eliminarPropuesta($id_propuesta){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$queryNum = sprintf("SELECT id_cuenta FROM te_propuesta_pago_transferencia WHERE id_propuesta_pago = %s",$id_propuesta);
	$rsNum = mysql_query($queryNum);
	if(!$rsNum){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowNum = mysql_fetch_array($rsNum);
	
	$queryDiferido = sprintf("SELECT idCuentas, Diferido FROM cuentas WHERE idCuentas = %s",$rowNum['id_cuenta']);
	$rsDiferido = mysql_query($queryDiferido);
	if(!$rsDiferido){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDiferido = mysql_fetch_array($rsDiferido);		
		
	$queryMontoPropuesta = sprintf("SELECT sum(`monto_pagar`) as monto FROM `te_propuesta_pago_detalle_transferencia` where `id_propuesta_pago`= %s",$id_propuesta);
	$rsMontoPropuesta = mysql_query($queryMontoPropuesta);
	if(!$rsMontoPropuesta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowMontoPropuesta = mysql_fetch_array($rsMontoPropuesta);			
					
	$Diferido=$rowDiferido['Diferido'] - $rowMontoPropuesta['monto'];
	$updateSaldo_Diferido = sprintf("UPDATE cuentas SET Diferido = '%s' WHERE idCuentas = %s ;",$Diferido,$rowDiferido['idCuentas']);
	$rsUpdateSaldo_Diferido = mysql_query($updateSaldo_Diferido);
	if(!$rsUpdateSaldo_Diferido){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	
	
	$queryDeleteDetalle = sprintf("DELETE FROM te_propuesta_pago_detalle_transferencia WHERE id_propuesta_pago = %s",$id_propuesta);
	$rsDeleteDetalle = mysql_query($queryDeleteDetalle);	
	if(!$rsDeleteDetalle){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
		
	$queryDelete = sprintf("DELETE FROM te_propuesta_pago_transferencia WHERE id_propuesta_pago = %s",$id_propuesta);
	$rsDelete = mysql_query($queryDelete);
	
	if (!$rsDelete) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Propuesta Borrada Exitosamente");	
	$objResponse->script("document.getElementById('btnBuscar').click();");
	
	return $objResponse;
}

function listarPropuestas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$valCadBusq[0] = ($valCadBusq[0] == "0") ? $_SESSION['idEmpresaUsuarioSysGts'] : $valCadBusq[0];
	
	$cond = " WHERE estatus_propuesta <> 1 ";
	
	if ($valCadBusq[0] != ""){
		if ($cond == ""){
			$cond = sprintf(" WHERE id_empresa = %s ",valTpDato($valCadBusq[0],"int"));
		}else{
			$cond .= sprintf( "AND id_empresa = %s ",valTpDato($valCadBusq[0],"int"));
		}
	}
	
	if ($valCadBusq[1] != ""){
		if ($cond == ""){
			$cond = sprintf(" WHERE id_proveedor = %s ",valTpDato($valCadBusq[1],"int"));
		}else{
			$cond .= sprintf(" AND id_proveedor = %s ",valTpDato($valCadBusq[1],"int"));
		}
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[2] != "-1"){
		if ($cond == ""){
			$cond = sprintf(" WHERE idBanco = %s ",valTpDato($valCadBusq[2],"int"));
		}else{
			$cond .= sprintf(" AND idBanco = %s ",valTpDato($valCadBusq[2],"int"));
		}
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[3] != "-1"){
		if ($cond == ""){
			$cond = sprintf(" WHERE idCuentas = %s ",valTpDato($valCadBusq[3],"int"));
		}else{
			$cond .= sprintf(" AND idCuentas = %s ",valTpDato($valCadBusq[3],"int"));
		}
	}
	
	$queryPropuesta = sprintf("SELECT * FROM vw_te_propuesta_pago_transferencia");
	$queryPropuesta .= $cond;
	$rsPropuesta = mysql_query($queryPropuesta);
	if(!$rsPropuesta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitPropuesta = sprintf(" %s %s LIMIT %d OFFSET %d", $queryPropuesta, $sqlOrd, $maxRows, $startRow);
                
	$rsLimitPropuesta = mysql_query($queryLimitPropuesta);
	if(!$rsLimitPropuesta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if($totalRows == NULL){
		$rsPropuesta = mysql_query($queryPropuesta);
		if(!$rsPropuesta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsPropuesta);
	}
	
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarPropuestas", "", $pageNum, "id_propuesta_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro.");
		$htmlTh .= ordenarCampo("xajax_listarPropuestas", "15", $pageNum, "fecha_propuesta_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listarPropuestas", "25%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listarPropuestas", "25%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listarPropuestas", "20%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta");
		$htmlTh .= ordenarCampo("xajax_listarPropuestas", "15%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowPropuesta = mysql_fetch_assoc($rsLimitPropuesta)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$queryMonto = sprintf("SELECT SUM(monto_pagar) AS monto FROM te_propuesta_pago_detalle_transferencia WHERE id_propuesta_pago = %s",$rowPropuesta['id_propuesta_pago']);
		$rsMonto = mysql_query($queryMonto);
		$rowMonto = mysql_fetch_array($rsMonto);
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".$rowPropuesta['id_propuesta_pago']."</td>";
			$htmlTb .= "<td>".date(spanDateFormat,strtotime($rowPropuesta['fecha_propuesta_pago']))."</td>";
			$htmlTb .= "<td>".proveedor($rowPropuesta['id_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($rowPropuesta['nombreBanco'])."</td>";
			$htmlTb .= "<td>".$rowPropuesta['numeroCuentaCompania']."</td>";
			$htmlTb .= "<td align='right'>".number_format($rowMonto['monto'],2,'.',',')."</td>";
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Ver Propuesta\">"."<img src=\"../img/iconos/ico_view.png\" onclick=\"window.open('te_propuesta_pago_transferencia.php?id_propuesta=".$rowPropuesta['id_propuesta_pago']."&acc=0','_self');\"/>"."</td>";
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Editar Propuesta\">"."<img src=\"../img/iconos/ico_edit.png\" onclick=\"window.open('te_propuesta_pago_transferencia.php?id_propuesta=".$rowPropuesta['id_propuesta_pago']."&acc=1','_self');\"/>"."</td>";
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Aprobar Propuesta\">"."<img src=\"../img/iconos/ico_aceptar.gif\" onclick=\"limpiarFormulario(); if (confirm('Desea aprobar la propuesta ".$rowPropuesta['id_propuesta_pago']."?') == true){
				document.getElementById('divTransferencia').style.display = '';
				centrarDiv(document.getElementById('divTransferencia'));
				document.getElementById('tdFlotanteTituloTransferencia').innerHTML = 'Aprobar Propuesta';
				document.getElementById('txtIdPropuestaA').value = '".$rowPropuesta['id_propuesta_pago']."';
				document.getElementById('hddIdPropuestaA').value = '".$rowPropuesta['id_propuesta_pago']."';
			}\"/>"."</td>";
			$htmlTb .= "<td align='center' class=\"puntero\" title=\"Eliminar Propuesta\">"."<img src=\"../img/iconos/ico_quitar.gif\" onclick=\"if (confirm('Desea eliminar la propuesta ".$rowPropuesta['id_propuesta_pago']."?') == true){
				document.getElementById('divFlotante').style.display = '';
				centrarDiv(document.getElementById('divFlotante'));
				document.getElementById('tdFlotanteTitulo').innerHTML = 'Eliminar Propuesta';
				document.getElementById('txtIdPropuesta').value = '".$rowPropuesta['id_propuesta_pago']."';
				document.getElementById('hddIdPropuesta').value = '".$rowPropuesta['id_propuesta_pago']."';
			}\"/>"."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPropuestas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPropuestas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarPropuestas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPropuestas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPropuestas(%s,'%s','%s','%s',%s);\">%s</a>",
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

	$objResponse->assign("tdListadoPropuestas","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listarProveedores($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	$sqlBusq = sprintf(" WHERE CONCAT(lrif,'-',rif) LIKE %s
						OR CONCAT(lrif,rif) LIKE %s
						OR nombre LIKE %s",
				valTpDato("%".$valCadBusq[0]."%", "text"),
				valTpDato("%".$valCadBusq[0]."%", "text"),
				valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$queryProveedor = sprintf("SELECT
								id_proveedor,
								CONCAT(lrif,'-',rif) as rif,
								nombre
							FROM cp_proveedor %s", 
							$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitProveedor = sprintf(" %s %s LIMIT %d OFFSET %d", $queryProveedor, $sqlOrd, $maxRows, $startRow);        

	$rsProveedor = mysql_query($queryProveedor);
    if(!$rsProveedor){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$rsLimitProveedor = mysql_query($queryLimitProveedor);
	if(!$rsLimitProveedor){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsProveedor = mysql_query($queryProveedor);
		if(!$rsProveedor){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsProveedor);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula / RIF.");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowProveedor = mysql_fetch_assoc($rsLimitProveedor)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$rowProveedor['id_proveedor']."');\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td>".$rowProveedor['id_proveedor']."</td>";
			$htmlTb .= "<td>".$rowProveedor['rif']."</td>";
			$htmlTb .= "<td>".utf8_encode($rowProveedor['nombre'])."</td>";
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdDescripcion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("document.getElementById('tdFlotanteTitulo1').innerHTML = 'Proveedores';
						  document.getElementById('divFlotante1').style.display = '';
						  document.getElementById('tblBancos').style.display = '';
						  centrarDiv(document.getElementById('divFlotante1'));");
	return $objResponse;
}

function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT * FROM bancos WHERE idBanco != '1'";
	$rsBanco = mysql_query($queryBanco);
	if(!$rsBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitBanco = mysql_query($queryLimitBanco);
 	if(!$rsLimitBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco);
		if (!$rsBanco) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
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
		
	$objResponse->assign("tdDescripcion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("document.getElementById('divFlotante1').style.display = '';
						  document.getElementById('tblBancos').style.display = '';
						  document.getElementById('tdFlotanteTitulo1').innerHTML = 'Seleccione Banco';
							  centrarDiv(document.getElementById('divFlotante1'));");	
		
	return $objResponse;
}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$valCadBusq[0] = ($valCadBusq[0] == "") ? 0 : $valCadBusq[0];
        
	if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa);
	if(!$rsLimitEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa);
		if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
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
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$rowBanco['id_empresa_reg']."',0,".$valCadBusq[0].");\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
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
		
	$objResponse->assign("tdDescripcion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("document.getElementById('divFlotante1').style.display = '';
						  document.getElementById('tblBancos').style.display = '';
						  document.getElementById('tdFlotanteTitulo1').innerHTML = 'Seleccione Empresa';
						  centrarDiv(document.getElementById('divFlotante1'))");	
	
	return $objResponse;
}

function verificarClave($valForm){
	$objResponse = new xajaxResponse();
	
	$queryClave = sprintf("SELECT contrasena FROM vw_pg_claves_modulos WHERE id_usuario = %s AND id_clave_modulo = 33",
				valTpDato($_SESSION['idUsuarioSysGts'],'int'));
	$rsClave = mysql_query($queryClave);	
	if (!$rsClave) return $objResponse->alert(mysql_error()."\n\nLINE: "._LINE_);
	
	if (mysql_num_rows($rsClave)){
		$rowClave = mysql_fetch_array($rsClave);
		if ($rowClave['contrasena'] == $valForm['txtClaveAprobacion']){
			$objResponse->script("xajax_eliminarPropuesta('".$valForm['hddIdPropuesta']."')");
			$objResponse->script("document.getElementById('divFlotante').style.display = 'none';");
		}else{
			$objResponse->alert(utf8_encode("clave Errada."));
		}
	}else{
		$objResponse->alert("No tiene permiso para realizar esta accion");
		$objResponse->script("document.getElementById('divFlotante').style.display = 'none';");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"aprobarPropuesta");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarPropuesta");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"eliminarPropuesta");
$xajax->register(XAJAX_FUNCTION,"listarPropuestas");
$xajax->register(XAJAX_FUNCTION,"listarProveedores");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"verificarClave");

function proveedor($id_proveedor){
	$queryProveedor = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = %s LIMIT 1",$id_proveedor);
	$rsProveedor = mysql_query($queryProveedor) or die(mysql_error()."\n\nLine: ".__LINE__);
	$rowProveedor = mysql_fetch_assoc($rsProveedor);
		
	return utf8_encode($rowProveedor['nombre']);
}


function CuentaBanco($clave,$id){
	
	$query = sprintf("SELECT 
						cuentas.numeroCuentaCompania,
						bancos.nombreBanco
					FROM cuentas
					INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco) 
					WHERE cuentas.idCuentas = '%s'",
					$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);

	if ($clave == 1){
		$respuesta = utf8_encode($row['nombreBanco']);
	}else{
		$respuesta = $row['numeroCuentaCompania'];	
	}

	return $respuesta;
}

?>