<?php

function reconversionFactura($idNotaCargo){
	$objResponse = new xajaxResponse();
	$id_usuario = $_SESSION['idUsuarioSysGts'];
	$idNotaCargo2 =$idNotaCargo;

	//Verifico si la nota de carggo ya tiene reconversion
	$queryValidacion = "SELECT * FROM cj_cc_notacargo_reconversion WHERE id_notacargo = $idNotaCargo2";
	$rsValidacion = mysql_query($queryValidacion);
	$numReg = mysql_num_rows($rsValidacion);//cantidad de registros con ese id bandera
	
	$queryValidacion2 = "SELECT * FROM cj_cc_notadecargo WHERE idNotaCargo = $idNotaCargo2 ";
	$rsValidacion2 = mysql_query($queryValidacion2);
	$numReg2 = mysql_fetch_array($rsValidacion2);
	
	$fechaRegistro = $numReg2['fechaRegistroNotaCargo'];
	//if ($rsValidacion2) return $objResponse->alert($fechaRegistro);
	$dateTime_fechaReconversion = '2018-08-20';

	//Consulto el id del cliente normal
	$queryConsultaidCliente = "SELECT idCliente FROM 
									  cj_cc_notadecargo 
							   WHERE id_notacargo = $idNotaCargo2 ";
	$rsConsulta1 = mysql_query($queryConsultaidCliente);
	$valor1 = mysql_fetch_array($rsConsulta1);
	$numReg1 = mysql_num_rows($rsConsulta1);
	//return $objResponse->alert("$queryConsultaidCliente" .   $valor1['idCliente']);//11799


	//Consulto el cliente empresa
	$queryConsultaidClienteEmpresa = "SELECT id_cliente_empresa FROM 
											cj_cc_cliente_empresa
										WHERE id_cliente =". $valor1['idCliente'];//42849
	$rsConsulta2 = mysql_query($queryConsultaidClienteEmpresa);
	$valor2 = mysql_fetch_array($rsConsulta2);
	$numReg2 = mysql_num_rows($rsConsulta2);
	//return $objResponse->alert("$queryConsultaidCliente" .   $valor2['id_cliente_empresa']);


	$consultaRapida = "SELECT montoTotalNotaCargo  
						   FROM cj_cc_notadecargo 
						   WHERE idNotaCargo = $idNotaCargo2 ";
		$rsConsulta3 = mysql_query($consultaRapida);
		$valor3 = mysql_fetch_array($rsConsulta3);
		$numReg3 = mysql_num_rows($rsConsulta3);
		//return $objResponse->alert("$consultaRapida" .   $valor3['montoExentoNotaCargo']);



	/*$queryConsultaidClienteEmpresa2 = "SELECT id_cliente_empresa FROM
										 cj_cc_cliente_empresa 
										WHERE id_cliente =".$valor2['id_cliente_empresa'];
	$rsConsulta3 = mysql_query($queryConsultaidClienteEmpresa2);
	$valor3 = mysql_fetch_array($rsConsulta3);
	$bandera4 = mysql_num_rows($rsConsulta3);
	return $objResponse->alert("$queryConsultaidCliente" .   $valor3['id_cliente_empresa']);*/
	
	 //return $objResponse->alert("$queryValidacion    $bandera");
	 
	if($fechaRegistro < $dateTime_fechaReconversion){
		if($numReg == 0){
			if ($valor3['montoTotalNotaCargo'] > 1) {
				//TABLA1
			$queryNotaCargo1 = "UPDATE cj_cc_notadecargo 
								SET montoTotalNotaCargo = montoTotalNotaCargo/100000,
								subtotalNotaCargo = subtotalNotaCargo/100000,
								montoExentoNotaCargo = montoExentoNotaCargo/100000
								WHERE idNotaCargo = $idNotaCargo2 ";
			$rsNota1 = mysql_query($queryNotaCargo1);
			if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo1);
	
			//TABLA2
			$queryNotaCargo2 = "UPDATE cj_cc_nota_cargo_detalle_motivo  
								SET precio_unitario  = precio_unitario/100000
								WHERE id_nota_cargo = $idNotaCargo2 ";
			$rsNota2 = mysql_query($queryNotaCargo2);
			if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo2);
	
	
			//TABLA3
			$queryNotaCargo3 = "UPDATE cj_det_nota_cargo   
								SET monto_pago  = monto_pago/100000
								WHERE idNotaCargo = $idNotaCargo2 ";
			$rsNota3 = mysql_query($queryNotaCargo3);
			if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo3);
	
	
			//TABLA4
			$queryNotaCargo4 = "UPDATE cj_cc_nota_cargo_detalle_motivo    
								SET precio_unitario  = precio_unitario/100000
								WHERE id_nota_cargo = $idNotaCargo2 ";
			$rsNota4 = mysql_query($queryNotaCargo4);
			if (!$rsNota4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo4);
	
	
			//TABLA5
			$queryReconversion = "INSERT INTO cj_cc_notacargo_reconversion (id_notacargo,id_usuario) VALUES ($idNotaCargo2,$id_usuario)";
			$rsReconversion = mysql_query($queryReconversion);
			if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
	
	
	
			$mensaje = "Items Actualizados'";
			$objResponse->alert("$mensaje");
			$objResponse->script("location.reload()");
			return $objResponse;
			}else if ($valor3['montoExentoNotaCargo'] < 1){
	
					//TABLA1
			$queryNotaCargo1 = "UPDATE cj_cc_notadecargo 
								SET montoTotalNotaCargo = 0.5,
								subtotalNotaCargo = 0.5,
								montoExentoNotaCargo = 0.5
								WHERE idNotaCargo = $idNotaCargo2 ";
			$rsNota1 = mysql_query($queryNotaCargo1);
			if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo1);
	
			//TABLA2
			$queryNotaCargo2 = "UPDATE cj_cc_nota_cargo_detalle_motivo  
								SET precio_unitario  = 0.5
								WHERE id_nota_cargo = $idNotaCargo2 ";
			$rsNota2 = mysql_query($queryNotaCargo2);
			if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo2);
	
	
			//TABLA3
			$queryNotaCargo3 = "UPDATE cj_det_nota_cargo   
								SET monto_pago  = 0.5
								WHERE idNotaCargo = $idNotaCargo2 ";
			$rsNota3 = mysql_query($queryNotaCargo3);
			if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo3);
	
	
			//TABLA4
			$queryNotaCargo4 = "UPDATE cj_cc_nota_cargo_detalle_motivo    
								SET precio_unitario  = 0.5
								WHERE id_nota_cargo = $idNotaCargo2 ";
			$rsNota4 = mysql_query($queryNotaCargo4);
			if (!$rsNota4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo4);
	
	
			//TABLA5
			$queryReconversion = "INSERT INTO cj_cc_notacargo_reconversion (id_notacargo,id_usuario) VALUES ($idNotaCargo2,$id_usuario)";
			$rsReconversion = mysql_query($queryReconversion);
			if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
	
	
	
			$mensaje = "Items Actualizados'";
			$objResponse->alert("$mensaje");
			$objResponse->script("location.reload()");
			return $objResponse;
	
			}

			/*$consultaRapida = "SELECT montoExentoNotaCargo 
							   FROM cj_cc_notadecargo 
							   WHERE idNotaCargo = $idNotaCargo2 ";
			$rsConsulta3 = mysql_query($consultaRapida);
			$valor3 = mysql_fetch_array($rsConsulta3);
			$numReg3 = mysql_num_rows($rsConsulta3);
	
	
	
	
			//CONDICION 1
	
	
			if ($valor3['montoExentoNotaCargo']>1) {*/
			
	
	}
	
		/*//CONDICION 2
	
			} if ($valor3['precio_unitario']<1) {
				//TABLA1
			$queryNotaCargo1 = "UPDATE cj_cc_notadecargo 
								SET montoTotalNotaCargo = 0.5,
								subtotalNotaCargo = 0.5,
								montoExentoNotaCargo = 0.5
								WHERE idNotaCargo = $idNotaCargo2 ";
			$rsNota1 = mysql_query($queryNotaCargo1);
			if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo1);
	
			//TABLA2
			$queryNotaCargo2 = "UPDATE cj_cc_nota_cargo_detalle_motivo  
								SET precio_unitario  = 0.5
								WHERE id_nota_cargo = $idNotaCargo2 ";
			$rsNota2 = mysql_query($queryNotaCargo2);
			if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo2);
	
	
			//TABLA3
			$queryNotaCargo3 = "UPDATE cj_det_nota_cargo   
								SET monto_pago  = 0.5
								WHERE idNotaCargo = $idNotaCargo2 ";
			$rsNota3 = mysql_query($queryNotaCargo3);
			if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo3);
	
	
			//TABLA4
			$queryNotaCargo4 = "UPDATE cj_cc_nota_cargo_detalle_motivo    
								SET precio_unitario  = 0.5
								WHERE id_nota_cargo = $idNotaCargo2 ";
			$rsNota4 = mysql_query($queryNotaCargo4);
			if (!$rsNota4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryNotaCargo4);
	
	
			//TABLA5
			$queryReconversion = "INSERT INTO cj_cc_notacargo_reconversion (id_notacargo,id_usuario) VALUES ($idNotaCargo2,$id_usuario)";
			$rsReconversion = mysql_query($queryReconversion);
			if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
	
	
	
			$mensaje = "Items Actualizados'";
			$objResponse->alert("$mensaje");
			$objResponse->script("location.reload()");
			return $objResponse;
	
			}*/else{
			return $objResponse->alert("Los items esta factura ya fueron convertidos a Bolivares Soberanos, no puede repetir el proceso");
			
		}
	}else{
		return $objResponse->alert("No está permitido reconvertir una nota de cargo con fecha igual o posterior al 20 de Agosto de 2018");
	}
}

function asignarClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s;",
		valTpDato($idClaveMovimiento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['pago_contado'] == 1 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 1 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	}
	
	$objResponse->script($accion);

	return $objResponse;
}

function asignarDepartamento($frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	
	if ($frmDcto['lstModulo'] >= 0) {
		if (in_array($idModulo, array(2,4,5))) {
			$idCaja = 1;
		} else if (in_array($idModulo, array(0,1,3))) {
			$idCaja = 2;
		}
		
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"), $idCaja);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	if ($frmDcto['cbxNroAutomatico'] == 1) {
		$objResponse->script("
		byId('txtNumeroNotaCargo').readOnly = true;
		byId('txtNumeroNotaCargo').className = 'inputInicial';
		byId('txtNumeroControlNotaCargo').readOnly = true;
		byId('txtNumeroControlNotaCargo').className = 'inputInicial';");
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$frmDcto['txtDiasCreditoCliente']);
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			$objResponse->assign("tdtxtFecha","innerHTML","<input type=\"text\" id=\"txtFecha\" name=\"txtFecha\" size=\"10\" style=\"text-align:center\" value=\"".date(spanDateFormat)."\"/>");
			$objResponse->assign("tdtxtFechaVencimiento","innerHTML","<input type=\"text\" id=\"txtFechaVencimiento\" name=\"txtFechaVencimiento\" size=\"10\" style=\"text-align:center\" value=\"".$fechaVencimiento."\"/>");
			
			$objResponse->script("
			byId('txtFecha').readOnly = true;
			byId('txtFecha').className = 'inputInicial';
			byId('txtFechaVencimiento').readOnly = true;
			byId('txtFechaVencimiento').className = 'inputInicial';");
		} else {
			$objResponse->script("
			byId('txtFecha').readOnly = false;
			byId('txtFecha').className = 'inputHabilitado';
			byId('txtFechaVencimiento').readOnly = false;
			byId('txtFechaVencimiento').className = 'inputHabilitado';
			
			jQuery(function($){
				$('#txtFecha').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
				$('#txtFechaVencimiento').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFecha\",
				dateFormat:\"".spanDatePick."\",
				cellColorScheme:\"purple\"
			});
			new JsDatePick({
				useMode:2,
				target:\"txtFechaVencimiento\",
				dateFormat:\"".spanDatePick."\",
				cellColorScheme:\"purple\"
			});");
			
			$objResponse->assign("txtNumeroNotaCargo","value","");
			$objResponse->assign("txtNumeroControlNotaCargo","value","");
			$objResponse->assign("txtFecha","value","");
			$objResponse->assign("txtFechaVencimiento","value","");
		}
	} else {
		$objResponse->script("
		byId('txtNumeroNotaCargo').readOnly = false;
		byId('txtNumeroNotaCargo').className = 'inputHabilitado';
		byId('txtNumeroControlNotaCargo').readOnly = false;
		byId('txtNumeroControlNotaCargo').className = 'inputHabilitado';");
		
		$objResponse->assign("txtNumeroControlNotaCargo","value","");
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$frmDcto['txtDiasCreditoCliente']);
		
		if ($frmDcto['lstAplicaLibro'] == 1) {
			$objResponse->assign("tdtxtFecha","innerHTML","<input type=\"text\" id=\"txtFecha\" name=\"txtFecha\" size=\"10\" style=\"text-align:center\" value=\"".date(spanDateFormat)."\"/>");
			$objResponse->assign("tdtxtFechaVencimiento","innerHTML","<input type=\"text\" id=\"txtFechaVencimiento\" name=\"txtFechaVencimiento\" size=\"10\" style=\"text-align:center\" value=\"".$fechaVencimiento."\"/>");
			
			$objResponse->script("
			byId('txtFecha').readOnly = true;
			byId('txtFecha').className = 'inputInicial';
			byId('txtFechaVencimiento').readOnly = true;
			byId('txtFechaVencimiento').className = 'inputInicial';");
		} else {
			$objResponse->script("
			byId('txtFecha').readOnly = false;
			byId('txtFecha').className = 'inputHabilitado';
			byId('txtFechaVencimiento').readOnly = false;
			byId('txtFechaVencimiento').className = 'inputHabilitado';
			
			jQuery(function($){
				$('#txtFecha').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
				$('#txtFechaVencimiento').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFecha\",
				dateFormat:\"".spanDatePick."\",
				cellColorScheme:\"purple\"
			});
			new JsDatePick({
				useMode:2,
				target:\"txtFechaVencimiento\",
				dateFormat:\"".spanDatePick."\",
				cellColorScheme:\"purple\"
			});");
			
			$objResponse->assign("txtFecha","value","");
			$objResponse->assign("txtFechaVencimiento","value","");
		}
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false"){
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
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
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

function buscarCliente($frmBuscarCliente, $frmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarMotivo($frmBuscarMotivo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarMotivo['hddObjDestinoMotivo'],
		$frmBuscarMotivo['txtCriterioBuscarMotivo']);
	
	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaMotivo, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaMotivo['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmMotivo:".$valor,"className",$clase);
			$objResponse->assign("tdNumItmMotivo:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjItmMotivo","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagoDcto['cbx2'];
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice2 => $valor2) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmPago:".$valor2,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor2,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjPago","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	$objResponse->assign("hddObjIva","value",((count($arrayObjIva) > 0) ? implode("|",$arrayObjIva) : ""));
	
	// SUMA LOS PAGOS
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice2 => $valor2) {
			$txtTotalPago += ($frmListaPagoDcto['hddEstatusPago'.$valor2] == 1) ? str_replace(",", "", $frmListaPagoDcto['txtMontoPago'.$valor2]) : 0;
		}
	}
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",", "", $frmListaMotivo['txtPrecioItm'.$valor]);
			
			$txtSubTotal += $txtTotalItm;
		}
	} else {
		$txtSubTotal = round(str_replace(",", "", $frmTotalDcto['txtSubTotal']),2);
	}
	
	$txtDescuento = round(str_replace(",", "", $frmTotalDcto['txtDescuento']),2);
	$txtFlete = round(str_replace(",", "", $frmTotalDcto['txtFlete']),2);
	$txtSubTotalDescuento = round(str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']),2);
	$txtTotalExento = round(str_replace(",", "", $frmTotalDcto['txtTotalExento']),2);
	$txtTotalExonerado = round(str_replace(",", "", $frmTotalDcto['txtTotalExonerado']),2);
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	if (isset($frmTotalDcto['cbxIva'])) {
		foreach ($frmTotalDcto['cbxIva'] as $indice => $valor) {
			// BUSCA EL IVA DE VENTA POR DEFECTO PARA CALCULAR EL EXENTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 AND iva.idIva = %s ORDER BY iva",
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBaseImpIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
			$txtIva = str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
			$txtSubTotalIva = $txtBaseImpIva * $txtIva / 100;
			
			$objResponse->assign("txtSubTotalIva".$valor,"value",number_format($txtSubTotalIva, 2, ".", ","));
			
			$totalSubtotalIva += round($txtSubTotalIva, 2);
			
			// BUSCA LA BASE IMPONIBLE MAYOR
			if ($totalRows > 0 && $txtBaseImpIva > 0) {
				$txtBaseImpIvaVenta = $txtBaseImpIva;
			}
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = true;
			byId('txtDescuento').className = 'inputInicial';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = true;
			byId('txtSubTotalDescuento').className = 'inputInicial';");
		}
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = false;
			byId('txtDescuento').className = 'inputHabilitado';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = false;
			byId('txtSubTotalDescuento').className = 'inputHabilitado';");
		}
	}
	$txtDescuento = ($txtDescuento > 0) ? $txtDescuento : 0;
	$txtSubTotalDescuento = ($txtSubTotalDescuento > 0) ? $txtSubTotalDescuento : 0;
	
	$txtTotalNotaCargo = $txtSubTotal - $txtSubTotalDescuento + $txtFlete + $totalSubtotalIva;
	$txtTotalExento = $txtSubTotal - $txtSubTotalDescuento + $txtFlete - $txtBaseImpIvaVenta;
	//Aqui si se adapta al soverano
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtFlete", "value", number_format($txtFlete, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalNotaCargo", "value", number_format($txtTotalNotaCargo, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstSumarPagoItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$array = array(
		"" => array("abrev" => "-", "descripcion" => "-"),
		"1" => array("abrev" => "C", "descripcion" => "Pago de Contado"),
		"2" => array("abrev" => "T", "descripcion" => "Trade In"));
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:40px\">";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		$html .= "<optgroup label=\"".utf8_encode($valor['descripcion'])."\">";
			$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$indice."\">".($valor['abrev'])."</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstVendedor($idEmpresa = "", $selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((clave_filtro IN (1,3,5,7,9,10) AND activo = 1)
	OR id_empleado = %s)",
		valTpDato($selId, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado %s ORDER BY nombre_empleado;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstVendedor\" name=\"lstVendedor\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"0\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {                   
		$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstVendedor","innerHTML",$html);
		
	return $objResponse;
}

function eliminarMotivo($trItmMotivo, $frmListaMotivo) {
	$objResponse = new xajaxResponse();
	
	if (isset($trItmMotivo) && $trItmMotivo > 0) {
		$objResponse->script("
		fila = document.getElementById('trItmMotivo:".$trItmMotivo."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
		
		$objResponse->script("xajax_eliminarMotivo('', xajax.getFormValues('frmListaMotivo'));");
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
		
	return $objResponse;
}

function eliminarMotivoLote($frmListaMotivo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaMotivo['cbxItm'])) {
		foreach ($frmListaMotivo['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmMotivo:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function formDcto($idNotaCargo, $acc){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	if ($idNotaCargo > 0) {
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		byId('txtIdCliente').readOnly = true;
		byId('txtNumeroNotaCargo').readOnly = true;
		byId('lblNroAutomatico').style.display = 'none';
		byId('txtFecha').readOnly = true;
		byId('txtObservacion').readOnly = true;
		
		byId('btnNotaCargoPDF').style.display = 'none';
		byId('btnReciboPagoPDF').style.display = 'none';
		
		byId('txtSubTotalDescuento').readOnly = true;
		byId('txtSubTotalDescuento').className = 'inputSinFondo';
		byId('txtFlete').readOnly = true;
		byId('txtFlete').className = 'inputSinFondo';
		byId('txtTotalExonerado').readOnly = true;
		byId('txtTotalExonerado').className = 'inputSinFondo';
		byId('txtTotalExento').readOnly = true;
		byId('txtTotalExento').className = 'inputSinFondo';
		
		byId('trListaPagoDcto').style.display = '';
		
		byId('aAgregarMotivo').style.display = 'none';
		byId('btnQuitarMotivo').style.display = 'none';");
		
		if ($acc == 0) { // 0 = CONSULTAR, 1 = EDITAR
			$objResponse->script("
			byId('txtNumeroControlNotaCargo').readOnly = true;
			
			byId('btnGuardar').style.display = 'none'");
		} else if ($acc == 1) {
			$objResponse->script("
			byId('txtNumeroControlNotaCargo').className = 'inputHabilitado';");
		}
		
		// BUSCA LOS DATOS DE LA NOTA DE CARGO
		$queryNotaCargo = sprintf("SELECT cxc_nd.*,
			(CASE cxc_nd.estadoNotaCargo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_nota_cargo,
			motivo.descripcion AS descripcion_motivo
		FROM pg_motivo motivo
			RIGHT JOIN cj_cc_notadecargo cxc_nd ON (motivo.id_motivo = cxc_nd.id_motivo)
		WHERE idNotaCargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaCargo = mysql_query($queryNotaCargo);
		if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCargo = mysql_fetch_array($rsNotaCargo);
		
		switch($rowNotaCargo['estadoNotaCargo']) {
			case 0 : $classEstatus = "divMsjError"; $imgEstatus = "<img src=\"../img/iconos/no_cancelado.png\">"; break;
			case 1 : $classEstatus = "divMsjInfo"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
			case 2 : $classEstatus = "divMsjAlerta"; $imgEstatus = "<img src=\"../img/iconos/cancelado_parcial.png\">"; break;
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowNotaCargo['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarCliente($rowNotaCargo['idCliente'], $rowNotaCargo['id_empresa'], ""));
		$objResponse->loadCommands(asignarEmpleado($rowNotaCargo['id_empleado_creador']));
		
		$objResponse->assign("txtFecha","value",date(spanDateFormat, strtotime($rowNotaCargo['fechaRegistroNotaCargo'])));
		$objResponse->assign("hddIdNotaCargo","value",$idNotaCargo);
		$objResponse->assign("txtNumeroNotaCargo","value",$rowNotaCargo['numeroNotaCargo']);
		$objResponse->assign("txtNumeroControlNotaCargo","value",$rowNotaCargo['numeroControlNotaCargo']);
		$objResponse->assign("txtFechaVencimiento","value",date(spanDateFormat, strtotime($rowNotaCargo['fechaVencimientoNotaCargo'])));
		$objResponse->loadCommands(cargaLstModulo($rowNotaCargo['idDepartamentoOrigenNotaCargo'], "selectedOption(this.id,'".$rowNotaCargo['idDepartamentoOrigenNotaCargo']."');", true));
		$objResponse->call("selectedOption","lstAplicaLibro",$rowNotaCargo['aplicaLibros']);
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowNotaCargo['estado_nota_cargo']);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowNotaCargo['observacionNotaCargo']));
		$objResponse->assign("tdTipoPago","innerHTML","<input type=\"hidden\" id=\"hddTipoPago\" name=\"hddTipoPago\" value=\"".$rowNotaCargo['tipoNotaCargo']."\"/><input type=\"text\" id=\"txtTipoPago\" name=\"txtTipoPago\" class=\"divMsjInfo2\" readonly=\"readonly\" size=\"20\" style=\"text-align:center\" value=\"".(($rowNotaCargo['tipoNotaCargo'] == 0) ? "CRÉDITO" : "CONTADO")."\"/>");
		
		$objResponse->script("
		byId('lstAplicaLibro').onchange = function() {
			selectedOption(this.id,'".$rowNotaCargo['aplicaLibros']."');
		}");
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array("ND",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
		$objDcto->tipoDocumento = "ND";
		$objDcto->tipoDocumentoMovimiento = (in_array("ND",array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $rowNotaCargo['idDepartamentoOrigenNotaCargo'];
		$objDcto->idDocumento = $idNotaCargo;
		$objDcto->mostrarDocumento = "verVentanaPDF";
		$aVerDcto = $objDcto->verDocumento();
		
		$objResponse->script("
		byId('btnNotaCargoPDF').style.display = '';
		byId('btnNotaCargoPDF').onclick = function() { ".$aVerDcto." }");
		
		// CARGA EL DETALLE DE LA NOTA DE DEBITO
		$queryNotaDebitoDet = sprintf("SELECT * FROM cj_cc_nota_cargo_detalle_motivo WHERE id_nota_cargo = %s
		ORDER BY id_nota_cargo_detalle_motivo ASC;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaDebitoDet = mysql_query($queryNotaDebitoDet);
		if (!$rsNotaDebitoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj = NULL;
		while ($rowNotaDebitoDet = mysql_fetch_assoc($rsNotaDebitoDet)) {
			$Result1 = insertarItemMotivo($contFila, $rowNotaDebitoDet['id_nota_cargo_detalle_motivo'], $rowNotaDebitoDet['id_motivo'], $rowNotaDebitoDet['precio_unitario']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		//aqui lo coloco pero luego se me borra
		$objResponse->assign("txtSubTotal","value",number_format($rowNotaCargo['subtotalNotaCargo'],2,".",","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowNotaCargo['descuentoNotaCargo'],2,".",","));
		$objResponse->assign("txtFlete","value",number_format($rowNotaCargo['fletesNotaCargo'],2,".",","));
		$objResponse->assign("txtBaseImponible","value",number_format($rowNotaCargo['baseImponibleNotaCargo'],2,".",","));
		$objResponse->assign("txtTotalExento","value",number_format($rowNotaCargo['montoExentoNotaCargo'],2,".",","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowNotaCargo['montoExoneradoNotaCargo'],2,".",","));
		$objResponse->assign("txtTotalNotaCargo","value",number_format($rowNotaCargo['montoTotalNotaCargo'],2,".",","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowNotaCargo['saldoNotaCargo'], 2, ".", ","));
		
		// CARGA LOS IMPUESTOS 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
		$queryIva = sprintf("SELECT
			cxc_nd_iva.id_nota_cargo_iva,
			cxc_nd_iva.id_nota_cargo,
			cxc_nd_iva.base_imponible,
			cxc_nd_iva.subtotal_iva,
			cxc_nd_iva.id_iva,
			cxc_nd_iva.iva,
			iva.observacion
		FROM cj_cc_nota_cargo_iva cxc_nd_iva
			INNER JOIN pg_iva iva ON (cxc_nd_iva.id_iva = iva.idIva)
		WHERE cxc_nd_iva.id_nota_cargo = %s
		ORDER BY iva",
			valTpDato($idNotaCargo, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$contFila++;
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
						"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				$('#trNetoOrden').before(elemento);",
				$contFila,
					$contFila, utf8_encode($rowIva['observacion']),
						$contFila, $contFila, $rowIva['id_iva'],
						$contFila, $contFila, $rowIva['lujo'],
						$contFila,
					$contFila, $contFila, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
					$contFila, $contFila, $rowIva['iva'], "%",
					$contFila, $contFila, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
				
				$contFila));
		}
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		$queryPago = sprintf("SELECT 
			cxc_pago.id_det_nota_cargo,
			cxc_pago.idNotaCargo,
			cxc_pago.fechaPago,
			cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
			cxc_pago.numeroDocumento AS id_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo_documento_pago,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.monto_pago,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			cxc_pago.id_apertura,
			cxc_pago.idCierre,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		WHERE cxc_pago.idNotaCargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsPago = mysql_query($queryPago);
		if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPago = mysql_num_rows($rsPago);
		while ($rowPago = mysql_fetch_assoc($rsPago)) {
			$Result1 = insertarItemMetodoPago($contFila, $rowPago['id_det_nota_cargo'], $rowPago['idNotaCargo'], $rowPago['idCaja']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		if ($totalRowsPago > 0) {
			if (in_array($rowFactura['idDepartamentoOrigenFactura'],array(2,4,5))) {
				$aVerPago = sprintf("verVentana('../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idTpDcto=2&id=%s', 960, 550);", $idNotaCargo);
			} else if (in_array($rowFactura['idDepartamentoOrigenFactura'],array(0,1,3))) {
				$aVerPago = sprintf("verVentana('../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idTpDcto=2&id=%s', 960, 550);", $idNotaCargo);
			}
			
			$objResponse->script("
			byId('btnReciboPagoPDF').style.display = '';
			byId('btnReciboPagoPDF').onclick = function() { ".$aVerPago." }");
		}
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('txtFecha').className = 'inputHabilitado';
		byId('txtSubTotalDescuento').className = 'inputHabilitado';
		byId('txtFlete').className = 'inputHabilitado';
		byId('txtTotalExonerado').className = 'inputHabilitado';
		byId('txtTotalExento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('btnNotaCargoPDF').style.display = 'none';
		byId('btnReciboPagoPDF').style.display = 'none';
		
		byId('trListaPagoDcto').style.display = 'none';
		
		jQuery(function($){
			$('#txtFecha').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		});
		
		new JsDatePick({
			useMode:2,
			target:\"txtFecha\",
			dateFormat:\"".spanDatePick."\",
			cellColorScheme:\"purple\"
		});");
		
		// CARGA LOS IMPUESTOS (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = "SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1;";
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)){
			$contFila++;
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
						"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputHabilitado\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trNetoOrden').before(elemento);
				
				byId('txtBaseImpIva%s').onblur = function() {
					setFormatoRafk(this,2);
					xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
				}
				byId('txtBaseImpIva%s').onkeypress = function(e) {
					return validarSoloNumerosReales(e);
				}",
				$contFila,
					$contFila, utf8_encode($rowIva['observacion']),
						$contFila, $contFila, $rowIva['idIva'],
						$contFila, $contFila, $rowIva['lujo'],
						$contFila,
					$contFila, $contFila, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
					$contFila, $contFila, $rowIva['iva'], "%",
					$contFila, $contFila, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
				
				$contFila,
				
				$contFila,
				
				$contFila));
		}
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');"));
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts']));
		$objResponse->loadCommands(cargaLstModulo(-1, "xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));"));
		$objResponse->loadCommands(cargaLstVendedor($idEmpresa));
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaMotivo, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaMotivo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagoDcto['cbx2'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idNotaCargo = $frmDcto['hddIdNotaCargo'];
	$idModulo = $frmDcto['lstModulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	$idTipoPago = $frmDcto['rbtTipoPago'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idNotaCargo > 0) {
		// CONSULTA EL NUMERO DE CONTROL  ANTERIOR
		$selectNotaCargo = sprintf("SELECT * FROM cj_cc_notadecargo WHERE idNotaCargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaCargo = mysql_query($selectNotaCargo);
		if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNotaCargo = mysql_fetch_array($rsNotaCargo);
		
		$updateSQL = sprintf("UPDATE cj_cc_notadecargo SET
			numeroControlNotaCargo = %s
		WHERE idNotaCargo = %s;",
			valTpDato($frmDcto['txtNumeroControlNotaCargo'], "text"),
			valTpDato($idNotaCargo, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// GUARDA EN LA AUDITORIA EL USUARIO QUE REALIZO LA MODIFICACION
		$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios (tipo_documento, id_documento, id_usuario, fecha_cambio, tabla_editada) 
		VALUES (2, %s, %s, NOW(), %s);",
			valTpDato($idNotaCargo, "int"),
			valTpDato($idUsuario, "int"),
			valTpDato("cj_cc_notadecargo", "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idAuditoria = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios_detalle (id_auditoria_cambios, campo_editado, valor_antiguo, valor_nuevo)
		VALUES (%s, %s, %s, %s);",
			valTpDato($idAuditoria, "int"),
			valTpDato("numeroControlNotaCargo", "text"),
			valTpDato($rowNotaCargo['numeroControlNotaCargo'], "text"),
			valTpDato($frmDcto['txtNumeroControlNotaCargo'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		foreach($arrayObj as $indice => $valor) {
			$objResponse->script("byId('txtPrecioItm".$valor."').className = 'inputCompleto'");
			
			if (!($frmListaMotivo['txtPrecioItm'.$valor] > 0)) { $arrayCantidadInvalida[] = "txtPrecioItm".$valor; }
		}
		
		// SI HAY CANTIDADES INVALIDAS O NO DISPONIBLES SE PINTARAN DICHOS OBJETOS, CONTENIDOS EN LAS MATRICES
		if (count($arrayCantidadInvalida) > 0) {
			if (count($arrayCantidadInvalida) > 0) {
				foreach ($arrayCantidadInvalida as $indice => $valor) {
					$objResponse->script("byId('".$valor."').className = 'inputCompletoErrado'");
				}
			}
			
			return $objResponse->alert(("Los campos señalados en rojo son invalidos"));
		}
		
		if ($frmDcto['lstModulo'] >= 0) {
			if (in_array($idModulo, array(2,4,5))) {
				$idCaja = 1;
			} else if (in_array($idModulo, array(0,1,3))) {
				$idCaja = 2;
			}
			
			$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"), $idCaja);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
		
		$txtSubTotalDescuento = (str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']) > 0) ? str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']) : 0;
		$txtDescuento = ($frmTotalDcto['txtSubTotal'] > 0) ? (100 * $txtSubTotalDescuento) / str_replace(",", "", $frmTotalDcto['txtSubTotal']) : 0;
		$txtDescuento = ($txtDescuento != "") ? $txtDescuento : 0;
		
		$txtBaseImponibleIva = 0;
		$txtIva = 0;
		$txtSubTotalIva = 0;
		$txtBaseImponibleIvaLujo = 0;
		$txtIvaLujo = 0;
		$txtSubTotalIvaLujo = 0;
		// INSERTA LOS IMPUESTOS DEL PEDIDO
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indiceIva => $valorIva) {
				if (str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valorIva]) != 0) {
					switch ($frmTotalDcto['hddLujoIva'.$valorIva]) {
						case 0 :
							$txtBaseImponibleIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valorIva]);
							$txtIva += str_replace(",", "", $frmTotalDcto['txtIva'.$valorIva]);
							$txtSubTotalIva += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valorIva]);
							break;
						case 1 :
							$txtBaseImponibleIvaLujo = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valorIva]);
							$txtIvaLujo += str_replace(",", "", $frmTotalDcto['txtIva'.$valorIva]);
							$txtSubTotalIvaLujo += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valorIva]);
							break;
					}
				}
			}
		}
		
		if ($frmDcto['cbxNroAutomatico'] == 1) {
			// NUMERACION DEL DOCUMENTO
			if (in_array($idModulo,array(2,4,5)) && $frmDcto['lstAplicaLibro'] == 1){
				$idNumeraciones = 13; // 13 = Nota de Débito Vehículos
			} else if (in_array($idModulo,array(0,1,3)) && $frmDcto['lstAplicaLibro'] == 1){
				$idNumeraciones = 23; // 23 = Nota de Débito Repuestos y Servicios
			} else {
				$idNumeraciones = 24; // 24 = Nota de Débito CxC
			}
			
			// NUMERACION DEL DOCUMENTO
			$queryNumeracion = sprintf("SELECT *
			FROM pg_empresa_numeracion emp_num
				INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
			WHERE (emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
											WHERE clave_mov.id_clave_movimiento = %s)
					OR emp_num.id_numeracion = %s)
				AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																								WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC LIMIT 1;",
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($idNumeraciones, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			$rsNumeracion = mysql_query($queryNumeracion);
			if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
			
			$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
			$idNumeraciones = $rowNumeracion['id_numeracion'];
			$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
			
			// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
			WHERE id_empresa_numeracion = %s;",
				valTpDato($idEmpresaNumeracion, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$numeroActualControl = $numeroActual;
		} else {
			$numeroActual = $frmDcto['txtNumeroNotaCargo'];
			$numeroActualControl = $frmDcto['txtNumeroControlNotaCargo'];
		}
		
		$txtDiasCreditoCliente = (strtotime($frmDcto['txtFechaVencimiento']) - strtotime($frmDcto['txtFecha'])) / 86400;
		
		// INSERTA LA NOTA DE DEBITO
		$insertSQL = sprintf("INSERT INTO cj_cc_notadecargo (id_empresa, idCliente, numeroNotaCargo, numeroControlNotaCargo, fechaRegistroNotaCargo, fechaVencimientoNotaCargo, idDepartamentoOrigenNotaCargo, referencia_nota_cargo, tipoNotaCargo, diasDeCreditoNotaCargo, estadoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, observacionNotaCargo, subtotalNotaCargo, fletesNotaCargo, interesesNotaCargo, descuentoNotaCargo, baseImponibleNotaCargo, porcentajeIvaNotaCargo, calculoIvaNotaCargo, base_imponible_iva_lujo, porcentaje_iva_lujo, ivaLujoNotaCargo, montoExentoNotaCargo, montoExoneradoNotaCargo, aplicaLibros, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($numeroActual, "text"),
			valTpDato($numeroActualControl, "text"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVencimiento'])), "date"),
			valTpDato($idModulo, "int"),
			valTpDato(1, "int"), // 0 = Cheque Devuelto, 1 = Otros
			valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Credito, 1 = Contado
			valTpDato($txtDiasCreditoCliente, "int"),
			valTpDato("0", "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
			valTpDato($frmTotalDcto['txtTotalNotaCargo'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalNotaCargo'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtFlete'], "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato($txtSubTotalDescuento, "real_inglesa"),
			valTpDato($txtBaseImponibleIva, "real_inglesa"),
			valTpDato($txtIva, "real_inglesa"),
			valTpDato($txtSubTotalIva, "real_inglesa"),
			valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
			valTpDato($txtIvaLujo, "real_inglesa"),
			valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato($frmDcto['lstAplicaLibro'], "boolean"), // 0 = No, 1 = Si
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));	
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idNotaCargo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$arrayIdDctoContabilidad = array(
			$idNotaCargo,
			$idModulo,
			"NOTA_CARGO_CXC");
		
		// INSERTA EL DETALLE DEL DOCUMENTO
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if (!($frmListaMotivo['hddIdNotaCargoDet'.$valor] > 0)) {
					$idMotivo = $frmListaMotivo['hddIdMotivoItm'.$valor];
					$precioUnitario = str_replace(",", "", $frmListaMotivo['txtPrecioItm'.$valor]);
					
					$insertSQL = sprintf("INSERT INTO cj_cc_nota_cargo_detalle_motivo (id_nota_cargo, id_motivo, precio_unitario)
					VALUE (%s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato($idMotivo, "int"),
						valTpDato($precioUnitario, "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idNotaDebitoDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
			}
			
			// ACTUALIZA EL MOTIVO DE LA NOTA DE DEBITO
			$updateSQL = sprintf("UPDATE cj_cc_notadecargo SET
				id_motivo = %s
			WHERE idNotaCargo = %s;",
				valTpDato($idMotivo, "int"),
				valTpDato($idNotaCargo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		// INSERTA LOS IMPUESTOS DEL PEDIDO
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indiceIva => $valorIva) {
				if (str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valorIva]) != 0) {
					$insertSQL = sprintf("INSERT INTO cj_cc_nota_cargo_iva (id_nota_cargo, base_imponible, subtotal_iva, id_iva, iva, lujo)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idNotaCargo, "int"),
						valTpDato($frmTotalDcto['txtBaseImpIva'.$valorIva], "real_inglesa"),
						valTpDato($frmTotalDcto['txtSubTotalIva'.$valorIva], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIva'.$valorIva], "int"),
						valTpDato($frmTotalDcto['txtIva'.$valorIva], "real_inglesa"),
						valTpDato($frmTotalDcto['hddLujoIva'.$valorIva], "boolean"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("ND", "text"),
			valTpDato($idNotaCargo, "int"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Nota de Débito Guardada con Éxito");
		
	$objResponse->script("window.location.href = 'cc_consulta_nota_cargo_list.php';");
	
	$objResponse->script(sprintf("verVentana('reportes/cc_nota_cargo_pdf.php?valBusq=%s',960,550)", $idNotaCargo));
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA_CARGO_CXC") {
				$idNotaCargo = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasCargoRe")) { generarNotasCargoRe($idNotaCargo,"",""); } break;
					case 1 : if (function_exists("generarNotasCargoRe")) { generarNotasCargoRe($idNotaCargo,"",""); } break;
					case 2 : if (function_exists("generarNotasCargoVe")) { generarNotasCargoVe($idNotaCargo,"",""); } break;
					//case 3 : if (function_exists("generarComprasAd")) { generarComprasAd($idNotaCargo,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	return $objResponse;
}

function insertarMotivo($idMotivo, $frmDcto, $frmListaMotivo) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Venta)
	$queryConfig5 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 5 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig5 = mysql_query($queryConfig5);
	if (!$rsConfig5) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowConfig5 = mysql_fetch_assoc($rsConfig5);
	
	if ($hddNumeroArt == "") {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = $frmListaMotivo['cbx'];
		$contFila = $arrayObj[count($arrayObj)-1];
		
		foreach ($arrayObj as $indice => $valor){
			if ($frmListaMotivo['hddIdMotivoItm'.$valor] == $idMotivo) {
				return $objResponse->alert("El motivo seleccionado ya se encuentra agregado");
			}
		}
		
		if (count($arrayObj) < $rowConfig5['valor']) {
			$Result1 = insertarItemMotivo($contFila, "", $idMotivo, $precioUnitario);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert(("Solo puede agregar un máximo de ".$rowConfig5['valor']." items por documento"));
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanClienteCxC;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
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
		END) AS descripcion_tipo_cuenta_cliente,
		vw_pg_empleado.nombre_empleado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"".("Prospecto")."\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"".("Prospecto Aprobado (Cliente Venta)")."\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"".("Sin Prospectación (Cliente Post-Venta)")."\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CC'
	AND ingreso_egreso LIKE 'I'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT motivo.*,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion,
		
		(CASE motivo.ingreso_egreso
			WHEN 'I' THEN	'Ingreso'
			WHEN 'E' THEN	'Egreso'
		END) AS descripcion_tipo_transaccion
	FROM pg_motivo motivo %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "10%", $pageNum, "id_motivo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "54%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Módulo"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "16%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Transacción"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['modulo']) {
			case "CC" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>"; break;
			case "CP" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>"; break;
			case "CJ" : break;
			case "TE" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>"; break;
			default : $imgPedidoModulo = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarMotivo%s\" onclick=\"validarInsertarMotivo('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_motivo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($row['descripcion_modulo_transaccion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".($row['descripcion_tipo_transaccion'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMotivo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstSumarPagoItm");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivo");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivoLote");
$xajax->register(XAJAX_FUNCTION,"formDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarMotivo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"reconversionFactura");

function insertarItemMetodoPago($contFila, $idPago = "", $idNotaCargo = "", $idCaja = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoClientePago = "", $txtCuentaClientePago = "", $txtMontoPago = "") {
	global $raiz;
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("
		SELECT
			cxc_pago.id_det_nota_cargo AS idPago,
			cxc_pago.idNotaCargo,
			cxc_pago.fechaPago,
			cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
			IF (cxc_pago.id_cheque IS NOT NULL,
				cxc_pago.id_cheque,
				IF (cxc_pago.id_transferencia IS NOT NULL,
					cxc_pago.id_transferencia,
					cxc_pago.numeroDocumento)) AS id_documento,
			
			cxc_pago.idNotaCargo AS id_documento_pagado,
			(SELECT cxc_nd.numeroNotaCargo FROM cj_cc_notadecargo cxc_nd WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS numero_documento_pagado,
			'ND' AS tipo_documento_pagado,
			
			(CASE cxc_pago.idFormaPago
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.id_departamento FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						NULL)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.id_departamento FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						NULL)
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo_documento_pago,
			
			(CASE cxc_pago.idFormaPago
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.numero_cheque FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						cxc_pago.numeroDocumento)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.numero_transferencia FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						cxc_pago.numeroDocumento)
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 8 THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.idFormaPago
				WHEN 2 THEN
					IF (cxc_pago.id_cheque IS NOT NULL,
						(SELECT cxc_ch.observacion_cheque FROM cj_cc_cheque cxc_ch WHERE cxc_ch.id_cheque = cxc_pago.id_cheque),
						NULL)
				WHEN 4 THEN
					IF (cxc_pago.id_transferencia IS NOT NULL,
						(SELECT cxc_tb.observacion_transferencia FROM cj_cc_transferencia cxc_tb WHERE cxc_tb.id_transferencia = cxc_pago.id_transferencia),
						NULL)
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			
			(SELECT tipo_tarjeta.descripcionTipoTarjetaCredito
			FROM cj_cc_retencion_punto_pago ret_punto_pago
				INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				INNER JOIN tipotarjetacredito tipo_tarjeta ON (ret_punto.id_tipo_tarjeta = tipo_tarjeta.idTipoTarjetaCredito)
			WHERE ret_punto_pago.id_pago = cxc_pago.id_det_nota_cargo
				AND ret_punto_pago.id_caja = cxc_pago.idCaja
				AND id_tipo_documento = 2) AS nombre_tarjeta,
			
			cxc_pago.monto_pago,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			cxc_pago.id_apertura,
			cxc_pago.idCierre,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
				INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
				INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
				LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
				LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
				LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxc_pago.id_empleado_creador = vw_pg_empleado.id_empleado)
				LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
				INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
					INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND recibo.idTipoDeDocumento = 2)
		WHERE cxc_pago.id_det_nota_cargo = %s
			AND cxc_pago.idNotaCargo = %s
			AND cxc_pago.idCaja = %s;",
			valTpDato($idPago, "int"),
			valTpDato($idNotaCargo, "int"),
			valTpDato($idCaja, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	} else {
		$cbxItm = sprintf("<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>",
			$contFila);
	}
	
	$classMontoPago = "";
	if ($totalRows > 0 && $row['estatus'] == 2) {
		$classMontoPago = "class=\"divMsjAlerta\"";
	} else if ($totalRows > 0 && $row['estatus'] != 1) {
		$classMontoPago = "class=\"divMsjError\"";
	}
	
	$txtFechaPago = ($txtFechaPago == "" && $totalRows > 0) ? $row['fechaPago'] : $txtFechaPago;
	$txtHoraPago = ($txtHoraPago == "" && $totalRows > 0) ? $row['tiempo_registro'] : $txtHoraPago;
	$txtNumeroRecibo = ($txtNumeroRecibo == "" && $totalRows > 0) ? $row['numeroComprobante'] : $txtNumeroRecibo;
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['nombreFormaPago'] : $txtMetodoPago;
	$txtNombreTarjeta = ($txtNombreTarjeta == "" && $totalRows > 0) ? $row['nombre_tarjeta'] : $txtNombreTarjeta;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento'] : $txtNumeroDctoPago;
	$txtBancoCompaniaPago = ($txtBancoCompaniaPago == "" && $totalRows > 0) ? $row['nombre_banco_empresa'] : $txtBancoCompaniaPago;
	$txtCuentaCompaniaPago = ($txtCuentaCompaniaPago == "" && $totalRows > 0) ?  $row['cuentaEmpresa'] : $txtCuentaCompaniaPago;
	$txtBancoClientePago = ($txtBancoClientePago == "" && $totalRows > 0) ? $row['nombre_banco_cliente'] : $txtBancoClientePago;
	$txtCuentaClientePago = ($txtCuentaClientePago == "" && $totalRows > 0) ?  $row['numero_cuenta_cliente'] : $txtCuentaClientePago;
	$txtCajaPago = ($txtCajaPago == "" && $totalRows > 0) ? $row['nombre_caja'] : $txtCajaPago;
	$txtMontoPago = ($txtMontoPago == "" && $totalRows > 0) ? $row['monto_pago'] : $txtMontoPago;
	$hddEstatusPago = ($hddEstatusPago == "" && $totalRows > 0) ? $row['estatus'] : 1;
	$descripcionMotivo = (strlen($row['descripcion_motivo']) > 0) ? "<div align=\"left\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_motivo'])."</span></div>" : "";
	$observacionDctoPago = (strlen($row['observacion_documento']) > 0) ? "<div align=\"left\"><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_documento'])."</span></div>" : "";
	$estatusPago = "";
	if ($totalRows > 0 && $row['estatus'] == 2) {
		$estatusPago = "<div align=\"center\">PAGO PENDIENTE</div>";
	} else if ($totalRows > 0 && $row['estatus'] != 1) {
		$estatusPago = "<div align=\"center\">PAGO ANULADO</div>";
	}
	$empleadoCreadorPago = (strlen($row['nombre_empleado']) > 0) ? "<span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".$row['nombre_empleado']."</span>" : "";
	$empleadoAnuladoPago = (strlen($row['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por: </span><span class=\"textoNegrita_9px\">".$row['nombre_empleado_anulado']."</span></div><div align=\"center\">(".date(spanDateFormat." h:i:s a", strtotime($row['fecha_anulado'])).")</div>" : "";
	$checkedCondicionMostrar = ($row['id_condicion_mostrar'] > 0) ?  "checked=\"checked\"" : "";
	$checkedMostrarContado = $row['id_mostrar_contado'];
	
	$objDcto = new Documento;
	$objDcto->raizDir = $raiz;
	$objDcto->tipoDocumento = "FA";
	$objDcto->idModulo = $row['id_modulo'];
	$objDcto->idDocumento = $row['id_recibo_pago'];
	$aVerRecibo = str_replace("'","\'",$objDcto->verRecibo());
	
	switch($row['id_modulo_documento_pago']) {
		case "" : $imgDctoModulo = ""; break;
		case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
		case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
		case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
		case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
		case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
		case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
		default : $imgDctoModulo = $row['id_modulo'];
	}
	
	$txtMetodoPagoConcepto = "";
	switch ($row['idFormaPago']) { // 2 = Cheques, 4 = Transferencia Bancaria, 7 = Anticipo, 8 = Nota Crédito
		case 2 : // 2 = Cheques
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("CH",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
			$objDcto->tipoDocumento = "CH";
			$objDcto->tipoDocumentoMovimiento = (in_array("CH",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $row['id_modulo_documento_pago'];
			$objDcto->idDocumento = $txtIdNumeroDctoPago;
			$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
			break;
		case 4 : // 4 = Transferencia Bancaria
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("TB",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
			$objDcto->tipoDocumento = "TB";
			$objDcto->tipoDocumentoMovimiento = (in_array("TB",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $row['id_modulo_documento_pago'];
			$objDcto->idDocumento = $txtIdNumeroDctoPago;
			$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
			break;
		case 7 : // 7 = Anticipo
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("AN",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
			$objDcto->tipoDocumento = "AN";
			$objDcto->tipoDocumentoMovimiento = (in_array("AN",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $row['id_modulo_documento_pago'];
			$objDcto->idDocumento = $txtIdNumeroDctoPago;
			$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
			
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT
				concepto_forma_pago.descripcion
			FROM cj_cc_detalleanticipo det_anticipo
				INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT';",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				$arrayConceptoAnticipo[] = $rowAnticipo['descripcion'];
			}
			$txtMetodoPagoConcepto = (($totalRowsAnticipo > 0) ? "<br><span class=\"textoNegrita_10px\">(".implode(", ", $arrayConceptoAnticipo).")</span>" : "");
			
			break;
		case 8 : // 8 = Nota Crédito
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("NC",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
			$objDcto->tipoDocumento = "NC";
			$objDcto->tipoDocumentoMovimiento = (in_array("NC",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $row['id_modulo_documento_pago'];
			$objDcto->idDocumento = $txtIdNumeroDctoPago;
			$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
			break;
	}
	
	switch ($row['idCaja']) {
		case 1 : $aVerDctoAux = sprintf("../caja_vh/cj_cierre_caja_historico.php?idApertura=%s&idCierre=%s&fecha=%s&idDcto=%s&idPago=%s",
					$row['id_apertura'], $row['idCierre'], $row['fechaPago'], $row['id_documento_pagado'], $row['idPago']); break;
		case 2 : $aVerDctoAux = sprintf("../caja_rs/cjrs_cierre_caja_historico.php?idApertura=%s&idCierre=%s&fecha=%s&idDcto=%s&idPago=%s",
					$row['id_apertura'], $row['idCierre'], $row['fechaPago'], $row['id_documento_pagado'], $row['idPago']); break;
		default : $aVerDctoAux = "";
	}
	$aVerDctoCierre = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/ico_examinar.png\" title=\"Portada de Caja\"/></a>" : "";
	
	switch ($row['idCaja']) {
		case 1 : $aVerDctoAux = sprintf("../caja_vh/cj_pagos_cargados_dia_historico.php?idApertura=%s&idCierre=%s&fecha=%s&idDcto=%s&idPago=%s",
					$row['id_apertura'], $row['idCierre'], $row['fechaPago'], $row['id_documento_pagado'], $row['idPago']); break;
		case 2 : $aVerDctoAux = sprintf("../caja_rs/cjrs_pagos_cargados_dia_historico.php?idApertura=%s&idCierre=%s&fecha=%s&idDcto=%s&idPago=%s",
					$row['id_apertura'], $row['idCierre'], $row['fechaPago'], $row['id_documento_pagado'], $row['idPago']); break;
		default : $aVerDctoAux = "";
	}
	$aVerDctoCierre .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/application_view_columns.png\" title=\"Recibos por Medio de Pago\"/></a>" : "";
	
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf(
	"$('#trItmPiePago').before('".
		"<tr id=\"trItmPago:%s\" align=\"left\" class=\"textoGris_11px %s\" height=\"24\">".
			"<td align=\"center\" title=\"trItmPago:%s\">%s".
				"<input type=\"checkbox\" id=\"cbx2\" name=\"cbx2[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"divMsjInfo2\">%s</td>".
			"<td class=\"divMsjInfo\">%s</td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtFechaPago%s\" name=\"txtFechaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"<div>%s</div>".
				"<div>%s</div></td>".
			"<td %s><table width=\"%s\"><tr align=\"right\"><td>%s</td><td width=\"%s\">%s</td></tr></table></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtMetodoPago%s\" name=\"txtMetodoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s".
				"%s".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr>".
				"<td nowrap=\"nowrap\">%s</td>".
				"<td>%s</td>".
				"<td width=\"%s\"><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr></table>".
				"<div>%s</div>".
				"<div>%s</div>".
				"<div>%s</div></td>".
			"<td %s><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtCajaPago%s\" name=\"txtCajaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s</td>".
			"<td %s><input type=\"text\" id=\"txtMontoPago%s\" name=\"txtMontoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusPago%s\" name=\"hddEstatusPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byName('cbxCondicionMostrar%s')[0].onclick = function () {
			xajax_asignarCondicionMostrar('%s', xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byName('lstSumarA%s')[0].onchange = function () {
			xajax_asignarMostrarContado('%s', this.value, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}",
		$contFila, $clase,
			$contFila, $cbxItm,
				$contFila,
			(in_array(idArrayPais,array(3))) ? "<input type=\"checkbox\" id=\"cbxCondicionMostrar\" name=\"cbxCondicionMostrar".$contFila."\" ".$checkedCondicionMostrar." value=\"1\">" : "",
			(in_array(idArrayPais,array(3))) ? cargaLstSumarPagoItm("lstSumarA".$contFila, $checkedMostrarContado) : "",
			$classMontoPago, $contFila, $contFila, utf8_encode(date(spanDateFormat, strtotime($txtFechaPago))),
				utf8_encode(date("h:i:s a", strtotime($txtHoraPago))),
				$empleadoCreadorPago,
			$classMontoPago, "100%", $aVerRecibo, "100%", $txtNumeroRecibo,
			$classMontoPago, $contFila, $contFila, ($txtMetodoPago),
				$txtNombreTarjeta,
				$txtMetodoPagoConcepto,
				$estatusPago,
			$classMontoPago, "100%",
				$aVerDcto,
				$imgDctoModulo,
				"100%", $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
					$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				$descripcionMotivo,
				preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$observacionDctoPago)))),
				utf8_encode($empleadoAnuladoPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoClientePago),
				$contFila, $contFila, utf8_encode($txtCuentaClientePago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
				$contFila, $contFila, utf8_encode($txtCuentaCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCajaPago),
				$aVerDctoCierre,
			$classMontoPago, $contFila, $contFila, utf8_encode(number_format($txtMontoPago, 2, ".", ",")),
				$contFila, $contFila, $idPago,
				$contFila, $contFila, $hddEstatusPago,
			
			$contFila,
				$idPago,
			
			$contFila,
				$idPago);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemMotivo($contFila, $hddIdNotaCargoDet = "", $idMotivo = "", $precioUnitario = "") {
	$contFila++;
	
	if ($hddIdNotaCargoDet > 0) {
		
	}
	
	$idMotivo = ($idMotivo == "" && $totalRowsNotaDebitoDet > 0) ? $rowNotaDebitoDet['id_motivo'] : $idMotivo;
	$precioUnitario = ($precioUnitario == "" && $totalRowsNotaDebitoDet > 0) ? $rowNotaDebitoDet['precio_unitario'] : $precioUnitario;
	$aClassReadonly = ($hddIdNotaCargoDet > 0) ? "class=\"inputSinFondo\" readonly=\"readonly\"" : "class=\"inputCompleto\"";
	$aEliminar = ($hddIdNotaCargoDet > 0) ? "" :
		sprintf("<a id=\"aEliminarItm:%s\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>",
			$contFila);
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryMotivo = sprintf("SELECT motivo.*,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion,
		
		(CASE motivo.ingreso_egreso
			WHEN 'I' THEN	'Ingreso'
			WHEN 'E' THEN	'Egreso'
		END) AS descripcion_tipo_transaccion
	FROM pg_motivo motivo WHERE id_motivo = %s;",
		valTpDato($idMotivo, "int"));
	$rsMotivo = mysql_query($queryMotivo);
	if (!$rsMotivo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsMotivo = mysql_num_rows($rsMotivo);
	$rowMotivo = mysql_fetch_assoc($rsMotivo);
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItmMotivo:%s\" title=\"trItmMotivo:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmMotivo:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmMotivo:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDescItm%s\" name=\"txtDescItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" %s onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdNotaCargoDet%s\" name=\"hddIdNotaCargoDet%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdMotivoItm%s\" name=\"hddIdMotivoItm%s\" readonly=\"readonly\" value=\"%s\"></td>".
		"</tr>');
		
		byId('txtPrecioItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaMotivo'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('aEliminarItm:%s').onclick = function() {
			xajax_eliminarMotivo('%s', xajax.getFormValues('frmListaMotivo'));
		}",
		$contFila, $contFila, $clase,
			$contFila, $contFila,
				 $contFila,
			$contFila, $contFila,
			$rowMotivo['id_motivo'],
			$contFila, $contFila, $rowMotivo['descripcion'],
			$rowMotivo['descripcion_modulo_transaccion'],
			$rowMotivo['descripcion_tipo_transaccion'],
			$contFila, $contFila, $aClassReadonly, number_format($precioUnitario, 2, ".", ","),
			$aEliminar,
				$contFila, $contFila, $hddIdNotaCargoDet,
				$contFila, $contFila, $idMotivo,
		
		$contFila,
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function validarAperturaCaja($idEmpresa, $fecha, $idCaja) {
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
	$queryCierreCaja = sprintf("
	SELECT fechaAperturaCaja FROM an_apertura
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND idCaja = %s
		AND id_empresa = %s
		
	UNION
	
	SELECT fechaAperturaCaja FROM sa_iv_apertura
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND idCaja = %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idCaja, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idCaja, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("
		SELECT * FROM an_apertura
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND idCaja = %s
			AND id_empresa = %s
			
		UNION
		
		SELECT * FROM sa_iv_apertura
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND idCaja = %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idCaja, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idCaja, "int"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>