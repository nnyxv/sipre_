<?php


function reconversionFactura($idFactura){
	$objResponse = new xajaxResponse();
	$id_usuario = $_SESSION['idUsuarioSysGts'];
	$idFactura2 =$idFactura;

	$queryValidacion = "SELECT * FROM cj_cc_factura_reconversion WHERE id_factura = $idFactura2";
	$rsValidacion = mysql_query($queryValidacion);
	$bandera = mysql_num_rows($rsValidacion);
	
	$queryValidacion2 = "SELECT * FROM cj_cc_encabezadofactura  WHERE idFactura = $idFactura2 ";
	$rsValidacion2 = mysql_query($queryValidacion2);
	$numReg2 = mysql_fetch_array($rsValidacion2);
	
	$fechaRegistro = $numReg2['fechaRegistroFactura'];
	//if ($rsValidacion2) return $objResponse->alert($fechaRegistro);
	$dateTime_fechaReconversion = '2018-08-20';


	$queryConsultaidCliente = "SELECT idCliente FROM 
									  cj_cc_encabezadofactura 
							   WHERE idFactura = $idFactura2 ";
	$rsConsulta1 = mysql_query($queryConsultaidCliente);
	$valor1 = mysql_fetch_array($rsConsulta1);
	$bandera2 = mysql_num_rows($rsConsulta1);
	//return $objResponse->alert("$queryConsultaidCliente" .   $valor1['idCliente']);//11799



	$queryConsultaidClienteEmpresa = "SELECT id_cliente_empresa FROM 
											cj_cc_cliente_empresa
										WHERE id_cliente =". $valor1['idCliente'];//42849
	$rsConsulta2 = mysql_query($queryConsultaidClienteEmpresa);
	$valor2 = mysql_fetch_array($rsConsulta2);
	$bandera3 = mysql_num_rows($rsConsulta2);
	//return $objResponse->alert("$queryConsultaidCliente" .   $valor2['id_cliente_empresa']);



	/*$queryConsultaidClienteEmpresa2 = "SELECT id_cliente_empresa FROM
										 cj_cc_cliente_empresa 
										WHERE id_cliente =".$valor2['id_cliente_empresa'];
	$rsConsulta3 = mysql_query($queryConsultaidClienteEmpresa2);
	$valor3 = mysql_fetch_array($rsConsulta3);
	$bandera4 = mysql_num_rows($rsConsulta3);
	return $objResponse->alert("$queryConsultaidCliente" .   $valor3['id_cliente_empresa']);*/
	
	 //return $objResponse->alert("$queryValidacion    $bandera");
	 
	if($fechaRegistro < $dateTime_fechaReconversion){	 
		if($bandera == 0){
	
			//TABLA1
			$queryFactura1 = "UPDATE sa_iv_pagos 
							  SET montoPagado = montoPagado/100000 
							  WHERE id_factura = $idFactura2";
			$rsFactura1 = mysql_query($queryFactura1);
			if (!$rsFactura1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura1);
	
	
			//TABLA2
			$queryFactura2 = "UPDATE cj_cc_encabezadofactura  
							  SET montoTotalFactura = montoTotalFactura/100000, 
								  subTotalFactura = subTotalFactura/100000,
								  baseImponible =baseImponible/100000,
								  calculoIvaFactura = calculoIvaFactura/100000,
								  saldoFactura	=  saldoFactura/100000
							  WHERE idFactura = $idFactura2";
			$rsFactura2 = mysql_query($queryFactura2);
			if (!$rsFactura2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura2);
	
	
	
	
			//TABLA3
			$queryFactura3 = "UPDATE cj_cc_factura_detalle  
							  SET costo_compra  = costo_compra/100000,
								  costo_promedio_compra = costo_promedio_compra/100000,
								  precio_unitario = precio_unitario/100000 
							  WHERE id_factura = $idFactura2";
			$rsFactura3 = mysql_query($queryFactura3);
			if (!$rsFactura3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura3);
	
	
	
	
			//TABLA4
			$queryFactura4 = "UPDATE cj_cc_factura_iva  
							  SET base_imponible  = base_imponible/100000, 
								  subtotal_iva = subtotal_iva/100000 
							  WHERE id_factura = $idFactura2";
			$rsFactura4 = mysql_query($queryFactura4);
			if (!$rsFactura1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura4);
	
	
	
	
			//TABLA5
			$queryFactura5 = "UPDATE cj_cc_retenciondetalle  
							  SET baseImponible  = baseImponible/100000,
								  totalCompraIncluyendoIva = totalCompraIncluyendoIva/100000,
								  comprasSinIva = comprasSinIva/100000,
								  impuestoIva = impuestoIva/100000,
								  ivaRetenido = ivaRetenido/100000
							  WHERE idFactura = $idFactura2";
			$rsFactura5 = mysql_query($queryFactura5);
			if (!$rsFactura5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura5);
	
	
			//TABLA 6
				$queryCreditoDispClienteEmpresa ="UPDATE cj_cc_credito 
													SET limitecredito = limitecredito/100000,
													creditoreservado = creditoreservado/100000,
													creditodisponible = creditodisponible/100000
													WHERE id_cliente_empresa =". $valor2['id_cliente_empresa'];
			$rsFactura6 = mysql_query($queryCreditoDispClienteEmpresa);
			if (!$rsFactura6) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryCreditoDispClienteEmpresa);
	
	
			$queryReconversion = "INSERT INTO cj_cc_factura_reconversion (id_factura,id_usuario) VALUES ($idFactura2,$id_usuario)";
			$rsReconversion = mysql_query($queryReconversion);
			if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
	
			
			/*$queryArticulo = sprintf("UPDATE sa_det_orden_articulo SET precio_unitario = precio_unitario/1000, costo = costo / 1000 WHERE id_orden = %s",valTpDato($idOrden, "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryArticulo);
	
			$queryTempario = sprintf("UPDATE sa_det_orden_tempario SET precio = precio/1000, precio_tempario_tipo_orden = precio_tempario_tipo_orden / 1000 WHERE id_orden = %s",valTpDato($idOrden, "int"));
			$rsTempario = mysql_query($queryTempario);
			if (!$rsTempario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryTempario);
	
			$queryReconversion = sprintf("INSERT INTO sa_orden_reconversion(id_orden,id_usuario) VALUES (%s,%s)",valTpDato($idOrden, "int"),valTpDato($id_usuario, "int"));
			$rsReconversion = mysql_query($queryReconversion);
			if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
	
			$mensaje = "Items Actualizados, Por favor haga click en 'Guardar'";
			$objResponse->alert("$mensaje");
			$objResponse->script("location.reload()");
			return $objResponse;*/
	
			$mensaje = "Items Actualizados'";
			$objResponse->alert("$mensaje");
			$objResponse->script("location.reload()");
			return $objResponse;
			
		}else{
			return $objResponse->alert("Los items esta factura ya fueron convertidos a Bolivares Soberanos, no puede repetir el proceso");			
		}
	}else{
		return $objResponse->alert("No está permitido reconvertir una factura con fecha igual o posterior al 20 de Agosto de 2018");
	}

}

function asignarArticuloImpuesto($frmArticuloImpuesto, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$hddIdIvaItm = implode(",",$frmArticuloImpuesto['lstIvaCbx']);
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
			$contFila = $valorItm;
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (6,9,2)
				AND iva.idIva IN (%s);",
				valTpDato($hddIdIvaItm, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$contIva = 0;
			$ivaUnidad = "";
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= cargaLstIvaItm("lstIvaItm".$contFila.":".$contIva, $rowIva['idIva']);
				$ivaUnidad .= sprintf("<input type=\"hidden\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
				"<input type=\"checkbox\" id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
					$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
					$contFila.":".$contIva);
			}
			
			$objResponse->assign("divIvaItm".$contFila,"innerHTML",$ivaUnidad);
		}
	}
	
	$objResponse->script("
	byId('btnCancelarArticuloImpuesto').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function asignarClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento
	WHERE id_clave_movimiento = %s;",
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
	
	//$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarCondicionMostrar($idPago, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if (in_array($frmDcto['lstModulo'],array(0,1,3))) {
		$updateSQL = sprintf("UPDATE sa_iv_pagos SET
			id_condicion_mostrar = IF(id_condicion_mostrar = 1, NULL, 1)
		WHERE idPago = %s;",
			valTpDato($idPago, "int"));
	} else if (in_array($frmDcto['lstModulo'],array(2))) {
		$updateSQL = sprintf("UPDATE an_pagos SET
			id_condicion_mostrar = IF(id_condicion_mostrar = 1, NULL, 1)
		WHERE idPago = %s;",
			valTpDato($idPago, "int"));
	}
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function asignarDepartamento($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	
	if ($frmDcto['lstModulo'] == 3) {
		$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	if ($idCliente > 0) {
		if (!($frmDcto['lstModulo'] == 3 && $frmDcto['lstAplicaLibro'] == 1)) {
			// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
			$arrayObj = $frmListaArticulo['cbx'];
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					$objResponse->script("
					fila = document.getElementById('trItmArticulo:".$valor."');
					padre = fila.parentNode;
					padre.removeChild(fila);");
				}
			}
		}
		
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObjIva = $frmTotalDcto['cbxIva'];
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				$objResponse->script("
				fila = document.getElementById('trIva:".$valor."');
				padre = fila.parentNode;
				padre.removeChild(fila);");
			}
		}
		
		$objResponse->script("byId('frmTotalDcto').reset();");
		$objResponse->assign("txtObservacion","value",$frmTotalDcto['txtObservacion']);
	}
	
	$objResponse->loadCommands(cargaLstVendedor($idEmpresa));
	
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $frmDcto['lstModulo'], $frmDcto['lstTipoMovimiento'], $frmDcto['hddTipoPagoCliente'], "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));
	
	$objResponse->script("
	byId('txtNumeroFactura').value = '';
	byId('txtNumeroControlFactura').value = '';
	byId('txtFechaFactura').value = '';
	byId('txtFechaVencimiento').value = '';
	
	byId('txtSubTotal').onblur = function() {
		setFormatoRafk(this,2);
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
	}
	byId('txtTotalExento').onblur = function() {
		setFormatoRafk(this,2);
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
	}
	byId('txtTotalExonerado').onblur = function() {
		setFormatoRafk(this,2);
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
	}");
	
	if ($frmDcto['lstModulo'] == 3 && $frmDcto['lstAplicaLibro'] == 1) {
		$objResponse->script("
		byId('txtNumeroFactura').readOnly = true;
		byId('txtNumeroFactura').className = 'inputInicial';
		
		byId('txtSubTotal').className = 'inputSinFondo';
		byId('txtSubTotal').readOnly = true;
		byId('txtTotalExento').className = 'inputSinFondo';
		byId('txtTotalExento').readOnly = true;
		byId('txtTotalExonerado').className = 'inputSinFondo';
		byId('txtTotalExonerado').readOnly = true;
		
		byId('trListaArticulo').style.display = '';");
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$frmDcto['txtDiasCreditoCliente']);
		
		$objResponse->assign("tdtxtFechaFactura","innerHTML","<input type=\"text\" id=\"txtFechaFactura\" name=\"txtFechaFactura\" size=\"10\" style=\"text-align:center\" value=\"".date(spanDateFormat)."\"/>");
		$objResponse->assign("tdtxtFechaVencimiento","innerHTML","<input type=\"text\" id=\"txtFechaVencimiento\" name=\"txtFechaVencimiento\" size=\"10\" style=\"text-align:center\" value=\"".$fechaVencimiento."\"/>");
			
		$objResponse->script("
		byId('txtFechaFactura').readOnly = true;
		byId('txtFechaFactura').className = 'inputInicial';
		byId('txtFechaVencimiento').readOnly = true;
		byId('txtFechaVencimiento').className = 'inputInicial';");
		
		$Result1 = buscarNumeroControl($idEmpresa, $idClaveMovimiento);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->assign("txtNumeroControlFactura","value",($Result1[1]));
		}
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->script("
		byId('txtNumeroFactura').readOnly = false;
		byId('txtNumeroFactura').className = 'inputHabilitado';
		byId('txtFechaFactura').readOnly = false;
		byId('txtFechaFactura').className = 'inputHabilitado';
		byId('txtFechaVencimiento').readOnly = false;
		byId('txtFechaVencimiento').className = 'inputHabilitado';
		
		byId('txtSubTotal').className = 'inputHabilitado';
		byId('txtSubTotal').readOnly = false;
		byId('txtTotalExento').className = 'inputHabilitado';
		byId('txtTotalExento').readOnly = false;
		byId('txtTotalExonerado').className = 'inputHabilitado';
		byId('txtTotalExonerado').readOnly = false;
		
		byId('trListaArticulo').style.display = 'none';
		
		jQuery(function($){
			$('#txtFechaFactura').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			$('#txtFechaVencimiento').maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		});
		new JsDatePick({
			useMode:2,
			target:\"txtFechaFactura\",
			dateFormat:\"".spanDatePick."\",
			cellColorScheme:\"purple\"
		});
		new JsDatePick({
			useMode:2,
			target:\"txtFechaVencimiento\",
			dateFormat:\"".spanDatePick."\",
			cellColorScheme:\"purple\"
		});");
		
		// CARGA LOS IMPUESTOS 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1 ORDER BY iva");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$indiceIva = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$indiceIva++;
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputHabilitado\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trGastosSinIva').before(elemento);
				
				byId('txtBaseImpIva%s').onblur = function() {
					setFormatoRafk(this,2);
					xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
				}
				byId('txtBaseImpIva%s').onkeypress = function(e) {
					return validarSoloNumerosReales(e);
				}",
				$indiceIva,
					$indiceIva, utf8_encode($rowIva['observacion']),
						$indiceIva, $indiceIva, $rowIva['idIva'],
						$indiceIva, $indiceIva, $rowIva['lujo'],
						$indiceIva,
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
					$indiceIva, $indiceIva, $rowIva['iva'], "%",
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
				
				$indiceIva,
				
				$indiceIva,
				
				$indiceIva));
		}
		
		if ($frmDcto['txtIdCliente'] > 0 || !($frmDcto['lstModulo'] >= 0)) {
			$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		}
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

function asignarMostrarContado($idPago, $cbxCondicionMostrar, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
		id_credito_tradein = %s
	WHERE idFactura = %s;",
		valTpDato($frmDcto['lstCreditoTradeIn'], "int"),
		valTpDato($frmDcto['hddIdFactura'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	if ($idPago > 0) {
		if (in_array($frmDcto['lstModulo'],array(0,1,3))) {
			$updateSQL = sprintf("UPDATE sa_iv_pagos SET
				id_mostrar_contado = %s
			WHERE idPago = %s;",
				valTpDato($cbxCondicionMostrar, "int"),
				valTpDato($idPago, "int"));
		} else if (in_array($frmDcto['lstModulo'],array(2))) {
			$updateSQL = sprintf("UPDATE an_pagos SET
				id_mostrar_contado = %s
			WHERE idPago = %s;",
				valTpDato($cbxCondicionMostrar, "int"),
				valTpDato($idPago, "int"));
		}
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarConcepto($frmBuscarConcepto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarConcepto['lstTipoConceptoBuscar'],
		$frmBuscarConcepto['txtCriterioBuscarConcepto']);
	
	$objResponse->loadCommands(listaConcepto(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmArticulo:".$valor,"className",$clase);
			$objResponse->assign("tdNumItmArticulo:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjItmArticulo","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	if (isset($arrayObjIva) && isset($arrayObj)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trIva:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $frmListaArticulo['txtIdEmpresa'];
	$txtDescuento = round(str_replace(",", "", $frmTotalDcto['txtDescuento']),2);
	$txtSubTotalDescuento = round(str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']),2);
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// SUMA LOS PAGOS
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalPago += ($frmListaArticulo['hddEstatusPago'.$valor] == 1) ? str_replace(",", "", $frmListaArticulo['txtMontoPago'.$valor]) : 0;
		}
	}
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]) * str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valor]);
			
			$txtSubTotal += $txtTotalItm;
		}
	} else {
		$txtSubTotal = round(str_replace(",", "", $frmTotalDcto['txtSubTotal']),2);
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IMPUESTO
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]) * str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valor]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
			
			$subTotalItm = $txtTotalItm;
			$totalDescuentoItm = ($hddTotalDescuentoItm > 0 || !($txtSubTotal > 0)) ? $hddTotalDescuentoItm : ($subTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
			$subTotalItm = $subTotalItm - $totalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$arrayPosIvaItm[$frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = $frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]];
						$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;",
							valTpDato($frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]], "int"));
						$rsIva = mysql_query($queryIva);
						if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$rowIva = mysql_fetch_assoc($rsIva);
						$arrayIvaItm[] = $rowIva['iva'];
					}
				}
			}
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);", 
				valTpDato(implode(",", $arrayIdIvaItm), "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$idIva = $rowIva['idIva'];
				$porcIva = $rowIva['iva'];
				$lujoIva = $rowIva['lujo'];
				$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if ($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
					$txtTotalExento += $subTotalItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
					$subTotalIvaItm = ($subTotalItm * $porcIva) / 100;
					
					$existIva = false;
					if (isset($arrayIva)) {
						foreach ($arrayIva as $indiceIva => $valorIva) {
							if ($arrayIva[$indiceIva][0] == $idIva) {
								$arrayIva[$indiceIva][1] += $subTotalItm;
								$arrayIva[$indiceIva][2] += $subTotalIvaItm;
								$existIva = true;
							}
						}
					}
					
					if ($idIva > 0 && $existIva == false
					&& ($txtTotalItm - $hddTotalDescuentoItm) > 0) {
						$arrayIva[] = array(
							$idIva,
							$subTotalItm,
							$subTotalIvaItm,
							$porcIva,
							$lujoIva,
							$rowIva['observacion']);
					}
				}
			}
			
			if ($totalRowsIva == 0) {
				$txtTotalExento += $subTotalItm;
			}
			
			$objResponse->assign("txtTotalItm".$valor, "value", number_format($txtTotalItm, 2, ".", ","));
			
			$subTotalDescuentoItm += $txtCantItm * str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
		}
	}
	
	// CREA LOS ELEMENTOS DE IVA
	if (isset($arrayIva)) {
		foreach ($arrayIva as $indiceIva => $valorIva) {
			if ($arrayIva[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIva:%s');
					if (obj == undefined)
						$('#trGastosSinIva').before(elemento);", 
					$indiceIva, 
						$indiceIva, utf8_encode($arrayIva[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][1], 2), 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIva[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][2], 2), 2, ".", ","), 
						
					$indiceIva));
			}
			
			$subTotalIva += round(doubleval($arrayIva[$indiceIva][2]), 2);
		}
	}
	
	if (count($arrayObj) > 0 && $frmDcto['txtIdCliente'] > 0) {
		$txtSubTotalDescuento = round(str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']),2);
		$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
		$totalFactura = $txtSubTotal - $txtSubTotalDescuento + $subTotalIva;
		
		$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($txtDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
		$objResponse->assign("txtTotalFactura","value",number_format($totalFactura, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';");
	} else {
		if (isset($frmTotalDcto['cbxIva'])) {
			foreach ($frmTotalDcto['cbxIva'] as $indice => $valor) {
				// BUSCA EL IVA DE VENTA POR DEFECTO PARA CALCULAR EL EXENTO 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
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
				
				$totalSubtotalIva += $txtSubTotalIva;
				
				// BUSCA LA BASE IMPONIBLE MAYOR
				if ($totalRows > 0 && $txtBaseImpIva > 0) {
					$txtBaseImpIvaVenta = $txtBaseImpIva;
				}
			}
		}
		
		$txtTotalExento = round(str_replace(",", "", $frmTotalDcto['txtTotalExento']),2);
		$txtTotalExonerado = round(str_replace(",", "", $frmTotalDcto['txtTotalExonerado']),2);
	
		$totalDcto = $txtSubTotal - $txtSubTotalDescuento + $totalSubtotalIva + $txtGastosConIva + $txtGastosSinIva;
		$txtTotalPago = (str_replace(",", "", $frmTotalDcto['txtTotalSaldo']) == 0 && ($txtTotalPago == 0 || $txtTotalPago > $totalDctoPorPagar)) ? $totalDctoPorPagar : $txtTotalPago;
		$txtTotalSaldo = $totalDctoPorPagar - $txtTotalPago;
		if (!($frmDcto['hddIdFactura'] > 0)) {
			$txtTotalExento = $txtSubTotal - $txtSubTotalDescuento - $txtBaseImpIvaVenta + $txtGastosConIva;
		}
		
		$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($txtDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
		$objResponse->assign("txtTotalFactura","value",number_format($totalDcto, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpresa').readOnly = false;
		byId('aListarEmpresa').style.display = '';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdCliente').readOnly = false;
		byId('aListarCliente').style.display = '';");
	}
	
	if ($frmDcto['lstModulo'] >= 0) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		
		byId('txtIdCliente').onblur = function() { }
		
		byId('lstAplicaLibro').className = 'inputInicial';
		byId('lstAplicaLibro').onchange = function () {
			selectedOption(this.id,'".$frmDcto['lstAplicaLibro']."');
		}");
	} else {
		$objResponse->script("
		byId('txtIdCliente').onblur = function() { xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false'); }
		
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('lstAplicaLibro').onchange = function () { }");
	}
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	if ($selId != "-1" && $selId != "") {
		$cond = (strlen($sqlBusq) > 0) ? " OR " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(clave_mov.id_clave_movimiento = %s
		AND clave_mov.id_modulo IN (%s))",
			valTpDato($selId, "int"),
			valTpDato($idModulo, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		clave_mov.tipo,
		(CASE clave_mov.tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento clave_mov %s
	ORDER BY clave_mov.tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento clave_mov %s %s ORDER BY clave_mov.clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = "";
			if ($selId == $rowClaveMov['id_clave_movimiento']) {
				$selected = "selected=\"selected\"";
				
				$objResponse->loadCommands(asignarClaveMovimiento($rowClaveMov['id_clave_movimiento'], ""));
			}
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCreditoTradeIn($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_asignarMostrarContado('', '', xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"";
	
	$array = array("0" => "Crédito Negativo", "1" => "Crédito Positivo");
	$totalRows = count($array);
	
	$html .= "<select id=\"lstCreditoTradeIn\" name=\"lstCreditoTradeIn\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCreditoTradeIn","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstIva($nombreObjeto, $selId = "", $selVal = "") {
	$objResponse = new xajaxResponse();
	
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1 ORDER BY iva;");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	$selected = "";
	if ((in_array(0,explode(",",$selId)) || in_array(-1,explode(",",$selId))) && !(count(explode(",",$selId)) > 1) && $selId != "") {
		$selected = "selected=\"selected\"";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if (in_array($rowIva['iva'],explode(",",$selVal)) && in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['iva'],explode(",",$selVal))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if ($selId == "" && $rowIva['tipo'] == 6 && $rowIva['activo'] == 1) { // IMPUESTO PREDETERMINADO
				$selected = "selected=\"selected\"";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
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

function cargaLstVendedor($idEmpresa = "", $selId = ""){
	$objResponse = new xajaxResponse();
	
	if (!$selId){
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
		$html = "<select id=\"lstVendedor\" name=\"lstVendedor\" class=\"inputHabilitado\" style=\"width:99%\">";
			$html .= "<option value=\"0\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {                   
			$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
		}
		$html .= "</select>";
	} else {
		$query = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s;",
			valTpDato($selId, "int"));
		$rs = mysql_query($query);
		if (!$rs) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		$html .= "<input type=\"text\" id=\"lstVendedor\" name=\"lstVendedor\" readonly=\"readonly\" value=\"".utf8_encode($row['nombre_empleado'])."\"/>";
	}
	
	$objResponse->assign("tdlstVendedor","innerHTML",$html);
		
	return $objResponse;
}

function eliminarArticulo($trItmArticulo, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($trItmArticulo) && $trItmArticulo > 0) {
		$objResponse->script("
		fila = document.getElementById('trItmArticulo:".$trItmArticulo."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
			
		$objResponse->script("xajax_eliminarArticulo('', xajax.getFormValues('frmListaArticulo'));");
	}
	
	//$objResponse->loadCommands(calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto));
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function formArticuloImpuesto() {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(cargaLstIva("lstIvaCbx"));
	
	return $objResponse;
}

function formDcto($idFactura, $acc){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$objResponse->script("byId('trCreditoTradeIn').style.display = 'none';");
	
	if ($idFactura > 0) {
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('aListarCliente').style.display = 'none';
		
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdCliente').readOnly = true;
		byId('txtNumeroFactura').readOnly = true;
		byId('txtFechaFactura').readOnly = true;
		byId('txtFechaVencimiento').readOnly = true;
		byId('txtNumeroSiniestro').readOnly = true;
		byId('txtObservacion').readOnly = true;
		
		byId('btnFacturaVentaPDF').style.display = 'none';
		byId('btnReciboPagoPDF').style.display = 'none';
		
		byId('txtSubTotalDescuento').className = 'inputSinFondo';
		byId('txtSubTotalDescuento').readOnly = true;
		
		byId('trListaArticulo').style.display = 'none';
		byId('trListaPagoDcto').style.display = '';
		byId('fieldsetNotaCredito').style.display = 'none';");
		
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFactura = sprintf("SELECT cxc_fact.*,
			cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
			cxc_ec.tipoDocumentoN,
			cxc_ec.tipoDocumento,
			(CASE cxc_fact.estadoFactura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_fact_vent
		FROM cj_cc_encabezadofactura cxc_fact
			LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
		WHERE cxc_fact.idFactura = %s", valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_array($rsFactura);
		
		switch($rowFactura['estadoFactura']) {
			case 0 : $classEstatus = "divMsjError"; $imgEstatus = "<img src=\"../img/iconos/no_cancelado.png\">"; break;
			case 1 : $classEstatus = "divMsjInfo"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
			case 2 : $classEstatus = "divMsjAlerta"; $imgEstatus = "<img src=\"../img/iconos/cancelado_parcial.png\">"; break;
		}
		
		if ($acc == 0) { // 0 = CONSULTAR, 1 = EDITAR
			$objResponse->script("
			byId('txtNumeroControlFactura').readOnly = true;
			
			byId('btnGuardar').style.display = 'none'");
		
			$objResponse->script("
			byId('lstAplicaLibro').onchange = function() {
				selectedOption(this.id,'".$rowFactura['aplicaLibros']."');
			}");
		} else if ($acc == 1) {
			$objResponse->script("
			byId('lstAplicaLibro').className = 'inputHabilitado';
			byId('txtNumeroControlFactura').className = 'inputHabilitado';");
		}
		
		if (in_array($rowFactura['idDepartamentoOrigenFactura'],array(2)) && in_array(idArrayPais,array(3))) {
			$objResponse->script("byId('trCreditoTradeIn').style.display = '';");
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowFactura['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarCliente($rowFactura['idCliente'], $rowFactura['id_empresa'], "", $rowFactura['condicionDePago'], $rowFactura['id_clave_movimiento'], "false", "false", "false"));
		$objResponse->loadCommands(asignarEmpleado($rowFactura['id_empleado_creador']));
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $rowFactura['idDepartamentoOrigenFactura'], "3", $rowFactura['condicionDePago'], "1", $rowFactura['id_clave_movimiento'], "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"")); 
		
		$objResponse->assign("txtFechaFactura","value",date(spanDateFormat, strtotime($rowFactura['fechaRegistroFactura'])));
		$objResponse->assign("hddIdFactura","value",$idFactura);
		$objResponse->assign("txtNumeroFactura","value",$rowFactura['numeroFactura']);
		$objResponse->assign("txtNumeroControlFactura","value",$rowFactura['numeroControl']);
		$objResponse->assign("txtFechaVencimiento","value",date(spanDateFormat, strtotime($rowFactura['fechaVencimientoFactura'])));
		$objResponse->loadCommands(cargaLstModulo($rowFactura['idDepartamentoOrigenFactura'], "selectedOption(this.id,'".$rowFactura['idDepartamentoOrigenFactura']."');", true));
		$objResponse->loadCommands(cargaLstVendedor($rowFactura['id_empresa'], $rowFactura['idVendedor']));
		$objResponse->assign("txtNumeroSiniestro","value",$rowFactura['numeroSiniestro']);
		$objResponse->call("selectedOption","lstAplicaLibro",$rowFactura['aplicaLibros']);
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowFactura['estado_fact_vent']);
		$objResponse->call("selectedOption","lstTipoMovimiento",3);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowFactura['observacionFactura']));
		$objResponse->assign("tdTipoPago","innerHTML","<input type=\"hidden\" id=\"hddTipoPago\" name=\"hddTipoPago\" value=\"".$rowFactura['condicionDePago']."\"/><input type=\"text\" id=\"txtTipoPago\" name=\"txtTipoPago\" class=\"divMsjInfo2\" readonly=\"readonly\" size=\"20\" style=\"text-align:center\" value=\"".(($rowFactura['condicionDePago'] == 0) ? "CRÉDITO" : "CONTADO")."\"/>");
		$objResponse->loadCommands(cargaLstCreditoTradeIn($rowFactura['id_credito_tradein']));
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id,'".(3)."');
		}");
		
		$objResponse->script("
		byId('lstClaveMovimiento').className = 'inputInicial';
		byId('lstClaveMovimiento').onchange = function() {
			selectedOption(this.id,'".($rowFactura['id_clave_movimiento'])."');
		}");
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array($rowFactura['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
		$objDcto->tipoDocumento = $rowFactura['tipoDocumento'];
		$objDcto->tipoDocumentoMovimiento = (in_array($rowFactura['tipoDocumento'],array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $rowFactura['idDepartamentoOrigenFactura'];
		$objDcto->idDocumento = $idFactura;
		$objDcto->mostrarDocumento = "verVentanaPDF";
		$aVerDcto = $objDcto->verDocumento();
		
		$objResponse->script("
		byId('btnFacturaVentaPDF').style.display = '';
		byId('btnFacturaVentaPDF').onclick = function() { ".$aVerDcto." }");
		
		$queryGasto = sprintf("SELECT
			cxc_fact_gasto.id_factura_gasto,
			cxc_fact_gasto.id_factura,
			cxc_fact_gasto.tipo,
			cxc_fact_gasto.porcentaje_monto,
			cxc_fact_gasto.monto,
			cxc_fact_gasto.estatus_iva,
			cxc_fact_gasto.id_iva,
			cxc_fact_gasto.iva,
			gasto.*
		FROM pg_gastos gasto
			INNER JOIN cj_cc_factura_gasto cxc_fact_gasto ON (gasto.id_gasto = cxc_fact_gasto.id_gasto)
		WHERE id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsGasto = mysql_query($queryGasto);
		if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsGasto = mysql_num_rows($rsGasto);
		while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
			if ($rowGasto['estatus_iva'] == 0) {
				$txtGastosSinIva += $rowGasto['monto'];
			} else if ($rowGasto['estatus_iva'] == 1) {
				$txtGastosConIva += $rowGasto['monto'];
			}
		}
		
		// CARGA LOS IMPUESTOS 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
		$queryIva = sprintf("SELECT 
			cxc_fact_iva.id_factura_iva,
			cxc_fact_iva.id_factura,
			cxc_fact_iva.base_imponible,
			cxc_fact_iva.subtotal_iva,
			cxc_fact_iva.id_iva,
			cxc_fact_iva.iva,
			iva.observacion,
			cxc_fact_iva.lujo
		FROM cj_cc_factura_iva cxc_fact_iva
			INNER JOIN pg_iva iva ON (cxc_fact_iva.id_iva = iva.idIva)
		WHERE cxc_fact_iva.id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$indice = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$indice++;
			
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
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trGastosSinIva').before(elemento);",
				$indice,
					$indice, utf8_encode($rowIva['observacion']),
						$indice, $indice, $rowIva['id_iva'],
						$indice, $indice, $rowIva['lujo'],
						$indice,
					$indice, $indice, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
					$indice, $indice, $rowIva['iva'], "%",
					$indice, $indice, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
				
				$indice));
		}
		
		$objResponse->assign("txtSubTotal","value",number_format($rowFactura['subtotalFactura'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($rowFactura['porcentaje_descuento'], 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowFactura['descuentoFactura'], 2, ".", ","));
		$objResponse->assign("txtFlete","value",number_format($rowFactura['fletesFactura'], 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
		$objResponse->assign("txtTotalFactura","value",number_format($rowFactura['montoTotalFactura'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowFactura['saldoFactura'], 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($rowFactura['montoExento'], 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowFactura['montoExonerado'], 2, ".", ","));
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		$queryPago = sprintf("
		SELECT 
			cxc_pago.idPago,
			cxc_pago.id_factura,
			cxc_pago.fechaPago,
			cxc_pago.numeroDocumento AS id_documento,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
		FROM an_pagos cxc_pago
			INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		WHERE cxc_pago.id_factura = %s
			
		UNION
		
		SELECT 
			cxc_pago.idPago,
			cxc_pago.id_factura,
			cxc_pago.fechaPago,
			cxc_pago.numeroDocumento AS id_documento,
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
		FROM sa_iv_pagos cxc_pago
			INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		WHERE cxc_pago.id_factura = %s;",
			valTpDato($idFactura, "int"),
			valTpDato($idFactura, "int"));
		$rsPago = mysql_query($queryPago);
		if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPago = mysql_num_rows($rsPago);
		while ($rowPago = mysql_fetch_array($rsPago)) {
			$Result1 = insertarItemMetodoPago($contFila, $rowPago['idPago'], $rowPago['id_factura'], $rowPago['idCaja']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
			
			// SUMA LOS PAGOS
			$txtTotalPago += ($rowPago['estatus'] == 1) ? $rowPago['montoPagado'] : 0;
		}
		$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
		
		if ($totalRowsPago > 0) {
			if (in_array($rowFactura['idDepartamentoOrigenFactura'],array(2,4,5))) {
				$aVerPago = sprintf("verVentana('../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idTpDcto=1&id=%s', 960, 550);", $idFactura);
			} else if (in_array($rowFactura['idDepartamentoOrigenFactura'],array(0,1,3))) {
				$aVerPago = sprintf("verVentana('../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idTpDcto=1&id=%s', 960, 550);", $idFactura);
			}
			
			$objResponse->script("
			byId('btnReciboPagoPDF').style.display = '';
			byId('btnReciboPagoPDF').onclick = function() { ".$aVerPago." }");
		}
		
		
		$queryNotaCredito = sprintf("SELECT 
			idNotaCredito,
			numeracion_nota_credito,
			montoNetoNotaCredito,
			fechaNotaCredito
		FROM cj_cc_notacredito
		WHERE idDocumento = %s
			AND tipoDocumento LIKE 'FA'",
			valTpDato($idFactura, "int"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNotaCredito = mysql_num_rows($rsNotaCredito);
		if ($totalRowsNotaCredito > 0) {
			$htmlTblIni = "<table border=\"0\" width=\"100%\">";
			$htmlTh = "<tr align=\"center\" class='tituloColumna'>";
				$htmlTh .= "<td width=\"20%\">Fecha Pago</td>";
				$htmlTh .= "<td width=\"30%\">Forma de Pago</td>";
				$htmlTh .= "<td width=\"25%\">Nro. Documento</td>";
				$htmlTh .= "<td width=\"25%\">Monto</td>";
			$htmlTh .= "</tr>";
			$htmlTb = "";
			while ($rowNotaCredito = mysql_fetch_array($rsNotaCredito)){
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$aVerDcto = "<a id=\"aVerDcto\" href=\"cc_nota_credito_form.php?id=".$rowNotaCredito['idNotaCredito']."&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Nota de Credito\"/><a>";
				
				$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($rowNotaCredito['fechaNotaCredito']))."</td>";
					$htmlTb .= "<td>".("Nota de Credito")."</td>";
					$htmlTb .= "<td>";
						$htmlTb .= "<table>";
						$htmlTb .= "<tr>";
							$htmlTb .= "<td>".$aVerDcto."</td>";
							$htmlTb .= "<td>";
								$htmlTb .= sprintf("<input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>",
									$contFila,
									$contFila,
									$rowNotaCredito['numeracion_nota_credito']);
								$htmlTb .= sprintf("<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/>",
									$contFila,
									$contFila,
									$rowPago['idNotaCredito']);
							$htmlTb .= "</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"right\">".number_format($rowNotaCredito['montoNetoNotaCredito'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
			}
			
			$htmlTb .= "<td colspan=\"4\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjAlerta\" width=\"100%\">";
				$htmlTb .= "<tr height=\"24\">";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/book_previous.png\"/></td>";
					$htmlTb .= "<td align=\"center\">Factura Devuelta</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTblFin .= "</table>";
			
			$objResponse->assign("divNotaCredito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
			
			$objResponse->script("byId('fieldsetNotaCredito').style.display = '';");
		}
	} else {
		$objResponse->script("
		byId('aListarCliente').style.display = '';
		
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('txtNumeroFactura').className = 'inputHabilitado';
		byId('txtNumeroControlFactura').className = 'inputHabilitado';
		byId('txtFechaFactura').className = 'inputHabilitado';
		byId('txtFechaVencimiento').className = 'inputHabilitado';
		byId('txtNumeroSiniestro').className = 'inputHabilitado';
		byId('txtSubTotalDescuento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('btnFacturaVentaPDF').style.display = 'none';
		byId('btnReciboPagoPDF').style.display = 'none';
		
		byId('trListaPagoDcto').style.display = 'none';
		byId('fieldsetNotaCredito').style.display = 'none';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');"));
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts']));
		$objResponse->loadCommands(cargaLstModulo(-1, "validarAsignarDepartamento();"));
		$objResponse->loadCommands(cargaLstVendedor($idEmpresa));
		
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$objResponse->assign("hddIdMoneda","value",$rowMoneda['idmoneda']);
		
		$objResponse->script("
		byId('lstAplicaLibro').onchange = function() {
			xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}");
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption('lstTipoMovimiento', '3');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",3);
		
		$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idFactura = $frmDcto['hddIdFactura'];
	$idModulo = $frmDcto['lstModulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	$idTipoPago = $frmDcto['rbtTipoPago'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idFactura > 0) {
		if (!xvalidaAcceso($objResponse,"cc_captura_facturas_list","editar")) { return $objResponse; }

		// CONSULTA EL NUMERO DE CONTROL  ANTERIOR
		$queryFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowFactura = mysql_fetch_array($rsFactura);
		
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
			numeroControl = %s,
			aplicaLibros = %s
		WHERE idFactura = %s;",
			valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
			valTpDato($frmDcto['lstAplicaLibro'], "int"), // 0 = No, 1 = Si
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// GUARDA EN LA AUDITORIA EL USUARIO QUE REALIZO LA MODIFICACION
		$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios (tipo_documento, id_documento, id_usuario, fecha_cambio, tabla_editada) 
		VALUES (1, %s, %s, NOW(), %s);",
			valTpDato($idFactura, "int"),
			valTpDato($idUsuario, "int"),
			valTpDato("cj_cc_encabezadofactura", "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idAuditoria = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQL = sprintf("INSERT INTO cc_auditoria_cambios_detalle (id_auditoria_cambios, campo_editado, valor_antiguo, valor_nuevo)
		VALUES (%s, %s, %s, %s);",
			valTpDato($idAuditoria, "int"),
			valTpDato("numeroControl", "text"),
			valTpDato($rowFactura['numeroControl'], "text"),
			valTpDato($frmDcto['txtNumeroControlFactura'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"cc_captura_facturas_list","insertar")) { return $objResponse; }
		
		if (isset($arrayObj)) {
			$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			foreach($arrayObj as $indice => $valor) {
				$objResponse->script("byId('txtDescItm".$valor."').className = 'inputCompleto'");
				$objResponse->script("byId('txtCantItm".$valor."').className = 'inputCompleto'");
				$objResponse->script("byId('txtPrecioItm".$valor."').className = 'inputCompleto'");
				
				if (!(strlen($frmListaArticulo['txtDescItm'.$valor]) > 0)) { $arrayCantidadInvalida[] = "txtDescItm".$valor; }
				if (!($frmListaArticulo['txtCantItm'.$valor] > 0)) { $arrayCantidadInvalida[] = "txtCantItm".$valor; }
				if (!($frmListaArticulo['txtPrecioItm'.$valor] > 0)) { $arrayCantidadInvalida[] = "txtPrecioItm".$valor; }
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
			
			// NUMERACION DEL DOCUMENTO
			$queryNumeracion = sprintf("SELECT *
			FROM pg_empresa_numeracion emp_num
				INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
			WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
											WHERE clave_mov.id_clave_movimiento = %s)
				AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																								WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC LIMIT 1;",
				valTpDato($idClaveMovimiento, "int"),
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
		} else {
			$numeroActual = $frmDcto['txtNumeroFactura'];
		}
			
		$txtDiasCreditoCliente = (strtotime($frmDcto['txtFechaVencimiento']) - strtotime($frmDcto['txtFechaFactura'])) / 86400;
		
		// SI EL TOTAL DEL DOCUMENTO ES CERO (FOLIO ANULADO) EL ESTATUS DE LA FACTURA SERÁ: CANCELADO
		$estatusFactura = (str_replace(",", "", $frmTotalDcto['txtTotalFactura']) == 0) ? 1 : 0; // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		
		$txtBaseImponibleIva = 0;
		$txtIva = 0;
		$txtSubTotalIva = 0;
		$txtBaseImponibleIvaLujo = 0;
		$txtIvaLujo = 0;
		$txtSubTotalIvaLujo = 0;
		// INSERTA LOS IMPUESTOS DEL PEDIDO
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				switch ($frmTotalDcto['hddLujoIva'.$valor]) {
					case 0 :
						$txtBaseImponibleIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
						$txtIva += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
						$txtSubTotalIva += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
						break;
					case 1 :
						$txtBaseImponibleIvaLujo = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
						$txtIvaLujo += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
						$txtSubTotalIvaLujo += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
						break;
				}
			}
		}
		
		// INSERTAR LOS DATOS DE LA FACTURA
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezadofactura (id_empresa, idCliente, numeroFactura, numeroControl, fechaRegistroFactura, fechaVencimientoFactura, idDepartamentoOrigenFactura, idVendedor, id_clave_movimiento, numeroPedido, numeroSiniestro, condicionDePago, diasDeCredito, estadoFactura, montoTotalFactura, saldoFactura, observacionFactura, subtotalFactura, interesesFactura, fletesFactura, porcentaje_descuento, descuentoFactura, baseImponible, porcentajeIvaFactura, calculoIvaFactura, base_imponible_iva_lujo, porcentajeIvaDeLujoFactura, calculoIvaDeLujoFactura, montoExento, montoExonerado, estatus_factura, anulada, aplicaLibros, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idEmpresa, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($numeroActual, "text"),
			valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVencimiento'])), "date"),
			valTpDato($idModulo, "int"),
			valTpDato($frmDcto['lstVendedor'], "int"),
			valTpDato($idClaveMovimiento, "int"),
			valTpDato("", "int"),
			valTpDato($frmDcto['txtNumeroSiniestro'], "text"),
			valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
			valTpDato($txtDiasCreditoCliente, "int"),
			valTpDato($estatusFactura, "int"), // 0 = No Cancelada, 1 = Cancelada , 2 = Parcialmente Cancelada
			valTpDato($frmTotalDcto['txtTotalFactura'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalFactura'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($txtBaseImponibleIva, "real_inglesa"),
			valTpDato($txtIva, "real_inglesa"),
			valTpDato($txtSubTotalIva, "real_inglesa"),
			valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
			valTpDato($txtIvaLujo, "real_inglesa"),
			valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato(((in_array(idArrayPais,array(3))) ? 1 : 2), "int"), // Null o 1 = Aprobada, 2 = Aplicada / Cerrada
			valTpDato("NO", "text"),
			valTpDato($frmDcto['lstAplicaLibro'], "int"), // 0 = No, 1 = Si
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idFactura = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$arrayIdDctoContabilidad[] = array(
			$idFactura,
			$idModulo,
			"VENTA");
		
		// INSERTA EL DETALLE DEL DOCUMENTO
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				$idConcepto = $frmListaArticulo['hddIdArticuloItm'.$valor];
				$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
				$cantDespachada = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
				$cantPendiente = doubleval($cantPedida) - doubleval($cantDespachada);
				$precioUnitario = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valor]);
				$costoUnitario = str_replace(",", "", $frmListaArticulo['hddCostoItm'.$valor]);
				$hddIdIvaItm = "";
				$hddIvaItm = 0;
				if (isset($arrayObjIvaItm)) {// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					foreach ($arrayObjIvaItm as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]];
							$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;",
								valTpDato($hddIdIvaItm, "int"));
							$rsIva = mysql_query($queryIva);
							if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$rowIva = mysql_fetch_assoc($rsIva);
							$hddIvaItm = $rowIva['iva'];
						}
					}
				}
				$totalArticulo = $cantDespachada * $precioUnitario;
				
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_adm (id_factura, id_concepto, descripcion, cantidad, devuelto, precio_unitario, costo_unitario, id_iva, iva, estatus)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idConcepto, "int"),
					valTpDato($frmListaArticulo['txtDescItm'.$valor], "text"),
					valTpDato($cantPedida, "real_inglesa"),
					valTpDato($cantPendiente, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($hddIdIvaItm, "int"),
					valTpDato($hddIvaItm, "real_inglesa"),
					valTpDato(1, "text")); // 0 = Pendiente, 1 = Entregado, 2 = Devuelto
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idFacturaDetalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]];
							$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;",
								valTpDato($hddIdIvaItm, "int"));
							$rsIva = mysql_query($queryIva);
							if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$rowIva = mysql_fetch_assoc($rsIva);
							$hddIvaItm = $rowIva['iva'];
							
							if ($hddIdIvaItm > 0) {
								$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_adm_impuesto (id_factura_detalle_adm, id_impuesto, impuesto) 
								VALUE (%s, %s, %s);",
									valTpDato($idFacturaDetalle, "int"),
									valTpDato($hddIdIvaItm, "int"),
									valTpDato($hddIvaItm, "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) {
									if (mysql_errno() == 1062) {
										return $objResponse->alert("Existe algún item con el impuesto repetido");
									} else {
										return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
									}
								}
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
				}
			}
		}
		
		// INSERTA LOS IMPUESTOS DEL PEDIDO
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				if (str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
					$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
						valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['hddLujoIva'.$valor], "boolean"));
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
			valTpDato("FA", "text"),
			valTpDato($idFactura, "int"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
			valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		if (isset($arrayObj)) {
			$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
		
		// ACTUALIZA EL CREDITO DISPONIBLE
		$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
			creditodisponible = limitecredito - (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
														WHERE cxc_fact.idCliente = cliente_emp.id_cliente
															AND cxc_fact.id_empresa = cliente_emp.id_empresa
															AND cxc_fact.estadoFactura IN (0,2)), 0)
												+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
														WHERE cxc_nd.idCliente = cliente_emp.id_cliente
															AND cxc_nd.id_empresa = cliente_emp.id_empresa
															AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
												- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
														WHERE cxc_ant.idCliente = cliente_emp.id_cliente
															AND cxc_ant.id_empresa = cliente_emp.id_empresa
															AND cxc_ant.estadoAnticipo IN (1,2)
															AND cxc_ant.estatus = 1), 0)
												- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
														WHERE cxc_nc.idCliente = cliente_emp.id_cliente
															AND cxc_nc.id_empresa = cliente_emp.id_empresa
															AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
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
			creditoreservado = (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
								WHERE cxc_fact.idCliente = cliente_emp.id_cliente
									AND cxc_fact.id_empresa = cliente_emp.id_empresa
									AND cxc_fact.estadoFactura IN (0,2)), 0)
								+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
								WHERE cxc_nd.idCliente = cliente_emp.id_cliente
									AND cxc_nd.id_empresa = cliente_emp.id_empresa
									AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
								- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
								WHERE cxc_ant.idCliente = cliente_emp.id_cliente
									AND cxc_ant.id_empresa = cliente_emp.id_empresa
									AND cxc_ant.estadoAnticipo IN (1,2)
									AND cxc_ant.estatus = 1), 0)
								- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
								WHERE cxc_nc.idCliente = cliente_emp.id_cliente
									AND cxc_nc.id_empresa = cliente_emp.id_empresa
									AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
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
	}
	
	mysql_query("COMMIT;");
		
	$objResponse->assign("txtIdFactura","value",$idFactura);
	$objResponse->assign("txtNumeroFactura","value",$numeroActual);
	
	$objResponse->alert("Factura Guardada con Éxito");
	
	$objResponse->script(sprintf("window.location.href='cc_consulta_facturas_list.php';"));
	
	if (isset($arrayObj)) {
		$objResponse->script("verVentana('../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=".$idFactura."', 960, 550);");
	}
	
	// CONTABILIZA DOCUMENTO
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO SHEDYMAR
			if ($tipoDcto == "VENTA") {
				$idFactura = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					/*case 0 : if (function_exists("generarVentasRe")) { generarVentasRe($idFactura,"",""); } break;
					case 1 : if (function_exists("generarVentasSe")) { generarVentasSe($idFactura,"",""); } break;
					case 2 : if (function_exists("generarVentasVe")) { generarVentasVe($idFactura,"",""); } break;*/
					case 3 : if (function_exists("generarVentasAd")) { generarVentasAd($idFactura,"",""); } break;
				}
			}
			// MODIFICADO SHEDYMAR
		}
	}
	
	return $objResponse;
}

function insertarArticulo($idConcepto, $frmDcto, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	
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
		$arrayObj = $frmListaArticulo['cbx'];
		$contFila = $arrayObj[count($arrayObj)-1];
		
		if (count($arrayObj) < $rowConfig5['valor']) {
			$Result1 = insertarItemArticulo($contFila, "", $idCliente, $idConcepto, $cantPedida, $precioUnitario, $costoUnitario, $abrevMonedaCostoUnitario, $idIva);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert(utf8_encode("Solo puede agregar un máximo de ".$rowConfig5['valor']." items por Pedido"));
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
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

function listaConcepto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus = 1");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(concep.id_concepto = %s
		OR concep.descripcion LIKE %s)",
			valTpDato($valCadBusq[1], "int"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT concep.*,
		tipo_concep.descripcion AS tipo_concepto
	FROM cj_cc_concepto concep
		INNER JOIN cj_cc_tipo_concepto tipo_concep ON (concep.id_tipo_concepto = tipo_concep.id_tipo_concepto) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "20%", $pageNum, "codigo_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "60%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "20%", $pageNum, "tipo_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Concepto");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarArticulo%s\" onclick=\"validarInsertarArticulo('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_concepto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['codigo_concepto'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_concepto'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaConcepto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"asignarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarCondicionMostrar");
$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarMostrarContado");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarConcepto");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCreditoTradeIn");
$xajax->register(XAJAX_FUNCTION,"cargaLstIva");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"formArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"formDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaConcepto");
$xajax->register(XAJAX_FUNCTION,"reconversionFactura");

// FUNCION AGREGADA EL 17-09-2012
function actualizarNumeroControl($idEmpresa, $idClaveMovimiento){
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
			
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	return array(true, "");
}

function buscarNumeroControl($idEmpresa, $idClaveMovimiento){
	// VERIFICA VALORES DE CONFIGURACION (Formato Nro. Control)
	$queryConfig401 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 401 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig401 = mysql_query($queryConfig401);
	if (!$rsConfig401) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig401 = mysql_num_rows($rsConfig401);
	$rowConfig401 = mysql_fetch_assoc($rsConfig401);
	
	if (!($totalRowsConfig401 > 0)) return array(false, "No existe un formato de numero de control establecido");
		
	$valor = explode("|",$rowConfig401['valor']);
	$separador = $valor[0];
	$formato = (strlen($separador) > 0) ? explode($separador,$valor[1]) : $valor[1];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	if (strlen($separador) > 0 && isset($formato)) {
		foreach($formato as $indice => $valor) {
			$numeroActualFormato[] = ($indice == count($formato)-1) ? str_pad($numeroActual,strlen($valor),"0",STR_PAD_LEFT) : str_pad(0,strlen($valor),"0",STR_PAD_LEFT);
		}
		$numeroActualFormato = implode($separador, $numeroActualFormato);
	} else {
		$numeroActualFormato = str_pad($numeroActual,strlen($formato),"0",STR_PAD_LEFT);
	}

	return array(true, $numeroActualFormato);
}

function cargaLstIvaItm($nombreObjeto, $selId = "", $selVal = "") {
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1 ORDER BY iva");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\" style=\"width:99%\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	$selected = "";
	if ((in_array(0,explode(",",$selId)) || in_array(-1,explode(",",$selId))) && !(count(explode(",",$selId)) > 1) && $selId != "") {
		$selected = "selected=\"selected\"";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if (in_array($rowIva['iva'],explode(",",$selVal)) && in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['iva'],explode(",",$selVal))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if ($selId == "" && $rowIva['tipo'] == 6 && $rowIva['activo'] == 1) { // IMPUESTO PREDETERMINADO
				$selected = "selected=\"selected\"";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
			
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
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

function insertarItemArticulo($contFila, $hddIdFacturaVentaDetalle = "", $idCliente = "", $idConcepto = "", $cantPedida = "", $precioUnitario = "", $costoUnitario = "", $abrevMonedaCostoUnitario = "", $idIva = "") {
	$contFila++;
	
	if ($hddIdFacturaVentaDetalle > 0) {
		
	}
	
	$idConcepto = ($idConcepto == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_concepto'] : $idConcepto;
	$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
	$precioUnitario = ($precioUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $precioUnitario;
	$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['costo_unitario'] : $costoUnitario;
	$idIva = ($idIva == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_iva'] : $idIva;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryConcepto = sprintf("SELECT * FROM cj_cc_concepto WHERE id_concepto = %s;",
		valTpDato($idConcepto, "int"));
	$rsConcepto = mysql_query($queryConcepto);
	if (!$rsConcepto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsConcepto = mysql_num_rows($rsConcepto);
	$rowConcepto = mysql_fetch_assoc($rsConcepto);
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE ((iva.tipo IN (6) AND iva.activo = 1)
			OR (iva.tipo IN (6,9,2) AND idIva = %s AND %s IS NOT NULL))
		AND iva.idIva NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
							WHERE cliente_imp_exento.id_cliente = %s);", 
		valTpDato($idIva, "int"), 
		valTpDato($idIva, "int"), 
		valTpDato($idCliente, "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contIva = 0;
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= cargaLstIvaItm("lstIvaItm".$contFila.":".$contIva, $rowIva['idIva']);
		$ivaUnidad .= sprintf("<input type=\"hidden\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
		"<input type=\"checkbox\" id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItmArticulo:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmPago:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmArticulo:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDescItm%s\" name=\"txtDescItm%s\" class=\"inputCompleto\" maxlength=\"255\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td title=\"trItmArticulo:%s\"><a id=\"aEliminar:%s\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" readonly=\"readonly\" value=\"%s\"></td>".
		"</tr>');
		
		byId('txtCantItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtPrecioItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('aEliminar:%s').onclick = function() {
			xajax_eliminarArticulo('%s', xajax.getFormValues('frmListaArticulo'));
		}",
		$contFila, $clase,
			$contFila, $contFila,
				 $contFila,
			$contFila, $contFila,
			utf8_encode($rowConcepto['codigo_concepto']),
			$contFila, $contFila, utf8_encode($rowConcepto['descripcion']),
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$contFila, $contFila, number_format($precioUnitario, 2, ".", ","),
			$contFila, str_replace("'","\'",$ivaUnidad),
			$contFila, $contFila, number_format($cantPedida * $precioUnitario, 2, ".", ","),
			$contFila, $contFila,
				$contFila, $contFila, $idConcepto,
		
		$contFila,
		$contFila,
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemMetodoPago($contFila, $idPago = "", $idFactura = "", $idCaja = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoClientePago = "", $txtCuentaClientePago = "", $txtMontoPago = "") {
	global $raiz;
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("
		SELECT
			cxc_pago.idPago,
			cxc_pago.id_factura,
			cxc_pago.fechaPago,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
			IF (cxc_pago.id_cheque IS NOT NULL,
				cxc_pago.id_cheque,
				IF (cxc_pago.id_transferencia IS NOT NULL,
					cxc_pago.id_transferencia,
					cxc_pago.numeroDocumento)) AS id_documento,
			
			cxc_pago.id_factura AS id_documento_pagado,
			(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS numero_documento_pagado,
			'FA' AS tipo_documento_pagado,
			
			(CASE cxc_pago.formaPago
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
			
			(CASE cxc_pago.formaPago
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
			
			(CASE cxc_pago.formaPago
				WHEN 8 THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.formaPago
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
			WHERE ret_punto_pago.id_pago = cxc_pago.idPago
				AND ret_punto_pago.id_caja = cxc_pago.idCaja
				AND id_tipo_documento = 1) AS nombre_tarjeta,
			
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.id_condicion_mostrar,
			cxc_pago.id_mostrar_contado,
			cxc_pago.idCaja,
			cxc_pago.id_apertura,
			cxc_pago.idCierre,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
				INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
				INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
				LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
				LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
				LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxc_pago.id_empleado_creador = vw_pg_empleado.id_empleado)
				LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
				INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
					INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
		WHERE cxc_pago.idPago = %s
			AND cxc_pago.id_factura = %s
			AND caja.idCaja = %s
			
		UNION
		
		SELECT 
			cxc_pago.idPago,
			cxc_pago.id_factura,
			cxc_pago.fechaPago,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
			IF (cxc_pago.id_cheque IS NOT NULL,
				cxc_pago.id_cheque,
				IF (cxc_pago.id_transferencia IS NOT NULL,
					cxc_pago.id_transferencia,
					cxc_pago.numeroDocumento)) AS id_documento,
			
			cxc_pago.id_factura AS id_documento_pagado,
			(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS numero_documento_pagado,
			'FA' AS tipo_documento_pagado,
			
			(CASE cxc_pago.formaPago
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
			
			(CASE cxc_pago.formaPago
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
			
			(CASE cxc_pago.formaPago
				WHEN 8 THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.formaPago
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
			WHERE ret_punto_pago.id_pago = cxc_pago.idPago
				AND ret_punto_pago.id_caja = cxc_pago.idCaja
				AND id_tipo_documento = 1) AS nombre_tarjeta,
			
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.id_condicion_mostrar,
			cxc_pago.id_mostrar_contado,
			cxc_pago.idCaja,
			cxc_pago.id_apertura,
			cxc_pago.idCierre,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
				INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
				INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
				LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
				LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
				LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxc_pago.id_empleado_creador = vw_pg_empleado.id_empleado)
				LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
				INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
					INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
		WHERE cxc_pago.idPago = %s
			AND cxc_pago.id_factura = %s
			AND caja.idCaja = %s;",
			valTpDato($idPago, "int"),
			valTpDato($idFactura, "int"),
			valTpDato($idCaja, "int"),
			valTpDato($idPago, "int"),
			valTpDato($idFactura, "int"),
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
	$txtMontoPago = ($txtMontoPago == "" && $totalRows > 0) ? $row['montoPagado'] : $txtMontoPago;
	$hddEstatusPago = ($hddEstatusPago == "" && $totalRows > 0) ? $row['estatus'] : 1;
	$descripcionMotivo = (strlen($row['descripcion_motivo']) > 0) ? "<div align=\"left\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_motivo'])."</span></div>" : "";
	$observacionDctoPago = (strlen($row['observacion_documento']) > 0) ? "<div align=\"left\"><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_documento'])."</span></div>" : "";
	$estatusPago = "";
	if ($totalRows > 0 && $row['estatus'] == 2) {
		$estatusPago = "<div align=\"center\">PAGO PENDIENTE</div>";
	} else if ($totalRows > 0 && $row['estatus'] != 1) {
		$estatusPago = "<div align=\"center\">PAGO ANULADO</div>";
	}
	$empleadoCreadorPago = (strlen($row['nombre_empleado']) > 0) ? "<span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".utf8_encode($row['nombre_empleado'])."</span>" : "";
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
			"<td %s><table border=\"0\" width=\"%s\"><tr>".
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

function validarAperturaCaja($idEmpresa, $fecha) {
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
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura
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
		$queryVerificarApertura = sprintf("SELECT * FROM sa_iv_apertura
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