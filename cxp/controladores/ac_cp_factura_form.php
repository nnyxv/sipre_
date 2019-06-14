<?php

function reconversion($idFactura){
	$objResponse = new xajaxResponse();
	$id_usuario = $_SESSION['idUsuarioSysGts'];
	$idFactura2 =$idFactura;

	//Verifico si la nota de carggo ya tiene reconversion
	$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_factura = $idFactura2";
	$rsValidacion = mysql_query($queryValidacion);
	$numReg = mysql_num_rows($rsValidacion);

	$queryValidacion2 = "SELECT * FROM cp_factura  WHERE id_factura = $idFactura2 ";
	$rsValidacion2 = mysql_query($queryValidacion2);
	$numReg2 = mysql_fetch_array($rsValidacion2);
	
	$fechaRegistro = $numReg2['fecha_origen'];
	//if ($rsValidacion2) return $objResponse->alert($fechaRegistro);
	$dateTime_fechaReconversion = '2018-08-20';
	
	
	if($fechaRegistro < $dateTime_fechaReconversion){
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
		
	
			$mensaje = "Items Actualizados";
			$objResponse->alert("$mensaje");
			$objResponse->script("location.reload()");
			return $objResponse;
				
		}else{
			return $objResponse->alert("Los items de esta factura ya fueron convertidos a Bolivares Soberanos, no puede repetir el proceso");
			
		}
	}else{
		return $objResponse->alert("No está permitido reconvertir una factura con fecha igual o posterior al 20 de Agosto de 2018");
	}
}



function asignarAnticipo($idAnticipo, $nombreObjeto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT 
		cxp_ant.id_anticipo,
		cxp_ant.fechaanticipo,
		cxp_ant.numeroAnticipo,
		prov.nombre,
		cxp_ant.total,
		cxp_ant.saldoanticipo,
		cxp_ant.estado
	FROM cp_anticipo cxp_ant
		INNER JOIN cp_proveedor prov ON (cxp_ant.id_proveedor = prov.id_proveedor)
	WHERE cxp_ant.id_anticipo = %s",
		valTpDato($idAnticipo,"int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$idAnticipo);
	$objResponse->assign("txtNumero".$nombreObjeto,"value",$rowDcto['numeroAnticipo']);
	$objResponse->assign("txtFecha".$nombreObjeto,"value",date(spanDateFormat, strtotime($rowDcto['fechaanticipo'])));
	$objResponse->assign("txtEstado".$nombreObjeto,"value",$rowDcto['estado']);
	$objResponse->assign("txtTotal".$nombreObjeto,"value",number_format($rowDcto['total'], 2, ".", ","));
	$objResponse->assign("txtSaldo".$nombreObjeto,"value",number_format($rowDcto['saldoanticipo'], 2, ".", ","));
	
	$objResponse->script("
	byId('btnCancelarLista".$nombreObjeto."').click();");
	
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

function asignarFechaRegistro($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = $frmDcto['lstModulo'];
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig17 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion IN (17,102,205,402) AND config_emp.status = 1 AND config_emp.id_empresa = %s AND config.id_modulo = %s;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idModulo, "int"));
	$rsConfig17 = mysql_query($queryConfig17);
	if (!$rsConfig17) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig17 = mysql_num_rows($rsConfig17);
	$rowConfig17 = mysql_fetch_assoc($rsConfig17);
	
	$valor = explode("|",$rowConfig17['valor']);
	
	$txtFechaProveedor = explode("-",date("Y-m-d",strtotime($frmDcto['txtFechaProveedor'])));
	if ($txtFechaProveedor[1] > 0 && $txtFechaProveedor[2] > 0 && $txtFechaProveedor[0] > 0) {
		if (checkdate($txtFechaProveedor[1], $txtFechaProveedor[2], $txtFechaProveedor[0])) { // EVALUA QUE LA FECHA EXISTA
			$txtFechaRegistroCompra = date(spanDateFormat);
			$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
			if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
				if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
					&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
					if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
					|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
						$txtFechaRegistroCompra = $txtFechaProveedor;
					} else {
						$objResponse->script("byId('cbxFechaRegistro').checked = false;");
						$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
					}
				} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
					&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
					$objResponse->script("byId('cbxFechaRegistro').checked = false;");
					$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
				} else {
					$txtFechaRegistroCompra = $txtFechaProveedor;
				}
			} else if ($frmDcto['cbxFechaRegistro'] == 1) {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
			}
			
			$objResponse->assign("txtFechaRegistroCompra","value",$txtFechaRegistroCompra);
		} else {
			$objResponse->assign("txtFechaProveedor","value","");
		}
	} else {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
	}
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $nombreObjeto, $asigDescuento = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor prov
	WHERE id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowProv['nombre_proveedor']));
	$objResponse->assign("txtRif".$nombreObjeto,"value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContacto".$nombreObjeto,"value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$nombreObjeto,"value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonos".$nombreObjeto,"value",$rowProv['telefono']);
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value",$rowProvCredito['diascredito']);
		
		$objResponse->call("selectedOption","lstTipoPago",1);
	} else {
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value","0");
		
		$objResponse->call("selectedOption","lstTipoPago",0);
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaProveedor').click();");
	}
	
	return $objResponse;
}

function asignarMetodoPago($idMetodoPago){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('fieldsetTransferencia').style.display = 'none';
	byId('fieldsetCheque').style.display = 'none';
	byId('fieldsetAnticipo').style.display = 'none';
	byId('fieldsetNotaCredito').style.display = 'none';");
	
	switch($idMetodoPago) {
		case 1 : $objResponse->script("byId('fieldsetTransferencia').style.display = '';"); break;
		case 2 : $objResponse->script("byId('fieldsetCheque').style.display = '';"); break;
		case 3 : $objResponse->script("byId('fieldsetAnticipo').style.display = '';"); break;
		case 4 : $objResponse->script("byId('fieldsetNotaCredito').style.display = '';"); break;
	}
	
	return $objResponse;
}

function asignarNotaCredito($idNotaCredito, $nombreObjeto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT 
		cxp_nc.id_notacredito,
		cxp_nc.fecha_notacredito,
		cxp_nc.numero_nota_credito,
		prov.nombre,
		
		(IFNULL(cxp_nc.subtotal_notacredito, 0)
			- IFNULL(cxp_nc.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
					WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
			+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
					WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
		) AS total,
		
		cxp_nc.saldo_notacredito,
		cxp_nc.estado_notacredito
	FROM cp_notacredito cxp_nc
		INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)
	WHERE cxp_nc.id_notacredito = %s",
		valTpDato($idNotaCredito,"int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$idNotaCredito);
	$objResponse->assign("txtNumero".$nombreObjeto,"value",$rowDcto['numero_nota_credito']);
	$objResponse->assign("txtFecha".$nombreObjeto,"value",date(spanDateFormat, strtotime($rowDcto['fecha_notacredito'])));
	$objResponse->assign("txtEstado".$nombreObjeto,"value",$rowDcto['estado_notacredito']);
	$objResponse->assign("txtTotal".$nombreObjeto,"value",number_format($rowDcto['total'], 2, ".", ","));
	$objResponse->assign("txtSaldo".$nombreObjeto,"value",number_format($rowDcto['saldo_notacredito'], 2, ".", ","));
	
	$objResponse->script("
	byId('btnCancelarLista".$nombreObjeto."').click();");
	
	return $objResponse;
}

function buscarAnticipo($frmBuscarAnticipo, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarAnticipo['hddObjDestinoAnticipo'],
		$frmDcto['txtIdProv'],
		$frmBuscarAnticipo['txtCriterioBuscarAnticipo']);
	
	$objResponse->loadCommands(listaAnticipo(0, "numeroAnticipo", "ASC", $valBusq));
		
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

function buscarNotaCredito($frmBuscarNotaCredito, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarNotaCredito['hddObjDestinoNotaCredito'],
		$frmDcto['txtIdEmpresa'],
		$frmDcto['txtIdProv'],
		$frmBuscarNotaCredito['txtCriterioBuscarNotaCredito']);
	
	$objResponse->loadCommands(listaNotaCredito(0, "numero_nota_credito", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarProveedor['hddObjDestinoProveedor'],
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx2'];
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmPago:".$valorItm,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valorItm,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$idMonedaOrigen = $frmDcto['hddIdMoneda'];
	$idModoCompra = $frmDcto['lstModoCompra'];
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	$incluirIvaMonedaOrigen = $rowMonedaOrigen['incluir_impuestos'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = (SELECT idmoneda FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1);");
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// SUMA LOS PAGOS
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalPago += ($frmListaPagoDcto['hddEstatusPago'.$valor] == 1) ? str_replace(",","",$frmListaPagoDcto['txtMontoPago'.$valor]) : 0;
		}
	}
	
	$txtIdFactura = $frmDcto['txtIdFactura'];
	$txtSubTotal = round(str_replace(",","",$frmTotalDcto['txtSubTotal']),2);
	$txtDescuento = round(str_replace(",","",$frmTotalDcto['txtDescuento']),2);
	$txtSubTotalDescuento = round(str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']),2);
	$txtTotalExento = round(str_replace(",","",$frmTotalDcto['txtTotalExento']),2);
	$txtTotalExonerado = round(str_replace(",","",$frmTotalDcto['txtTotalExonerado']),2);
	
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
	
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IMPUESTO
	$gastosConIvaOrigen = 0;
	$gastosSinIvaOrigen = 0;
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice2 => $valor2) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s;",
				valTpDato($frmTotalDcto['hddIdGasto'.$valor2], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGasto = mysql_fetch_assoc($rsGasto);
			
			if ($frmTotalDcto['hddTipoGasto'.$valor2] == 0) { // SACA EL MONTO MEDIANTE EL PORCENTAJE
				$porcentaje = ($txtSubTotal == 0) ? 0 : str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valor2]);
				$monto = ($txtSubTotal == 0) ? 0 : $porcentaje * ($txtSubTotal / 100);
				$objResponse->assign('txtMontoGasto'.$valor2,"value",number_format($monto, 2, ".", ","));
			} else if ($frmTotalDcto['hddTipoGasto'.$valor2] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
				$monto = ($txtSubTotal == 0) ? 0 : str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor2]);
				$porcentaje = ($txtSubTotal == 0) ? 0 : $monto * (100 / $txtSubTotal);
				$objResponse->assign('txtPorcGasto'.$valor2,"value",number_format($porcentaje, 2, ".", ","));
			}
			
			$monto = str_replace(",","",$monto);
			
			if ($idModoCompra == 2 && ($incluirIvaMonedaOrigen == 1 || $incluirIvaMonedaLocal == 1)) { // 2 = Importacion
				if ($frmTotalDcto['hddIdIvaGasto'.$valor2] > 0) {
					$estatusIva = 1;
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
				} else if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 1) {
					$estatusIva = 1;
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
				} else if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 0 && $incluirIvaMonedaLocal == 1) {
					$estatusIva = 0;
					// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1;");
				} else if ($rowGasto['id_modo_gasto'] == 3 && $incluirIvaMonedaLocal == 1) {
					$estatusIva = 1;
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
				}
			} else {
				$estatusIva = 1;
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
			}
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$idIva = $rowIva['idIva'];
			$porcIva = $rowIva['iva'];
			$lujoIva = ($rowIva['tipo'] == 2 || $rowIva['tipo'] == 3) ? "1" : "0";
			
			if ($totalRowsIva == 0 || $estatusIva == 0) {
				if ($rowGasto['id_modo_gasto'] == 1) { // 1 = Gastos
					if ($rowGasto['afecta_documento'] == 1) {
						$gastosSinIvaOrigen += $monto;
					} else {
						$gastosNoAfectaOrigen += $monto;
					}
				} else if ($rowGasto['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
					$gastosSinIva += $monto;
				}
			} else {
				if ($idModoCompra == 2 && $incluirIvaMonedaLocal == 1) { // 2 = Importacion
					$ivaArt = $porcIva;
				} else {
					$ivaArt = ($frmDcto['txtIdPedido'] > 0) ? str_replace(",","",$frmTotalDcto['hddIvaGasto'.$valor2]) : $porcIva;
				}
				
				$existIva = false;
				if (isset($arrayIva)) {
					foreach ($arrayIva as $indiceIva => $valorIva) {
						if ($arrayIva[$indiceIva][0] == $idIva) {
							$arrayIva[$indiceIva][1] += $monto;
							$arrayIva[$indiceIva][2] += ($monto * ($ivaArt / 100));
							$existIva = true;
						}
					}
				}
				
				if ($idIva > 0 && $existIva == false && $monto > 0) {
					$arrayIva[] = array(
						$idIva,
						$monto,
						($monto * ($ivaArt / 100)),
						$ivaArt,
						$lujoIva,
						$rowIva['observacion']);
				}
				
				if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 1) {
					if ($rowGasto['afecta_documento'] == 1) {
						$gastosConIvaOrigen += $monto;
					} else {
						$gastosNoAfectaOrigen += $monto;
					}
				} else if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 0 && $incluirIvaMonedaLocal == 1) {
					$gastosConIva += $monto;
				} else if ($rowGasto['id_modo_gasto'] == 3 && $incluirIvaMonedaLocal == 1) {
					$gastosConIva += $monto;
				}
			}
		}
		$txtGastosConIva = $gastosConIvaOrigen + $gastosConIva;
		$txtGastosSinIva = $gastosSinIvaOrigen + $gastosSinIva;
	} else {
		$txtGastosConIva = round(str_replace(",","",$frmTotalDcto['txtGastosConIva']),2);
		$txtGastosSinIva = round(str_replace(",","",$frmTotalDcto['txtGastosSinIva']),2);
	}
	
	if (isset($frmTotalDcto['cbxIva'])) {
		foreach ($frmTotalDcto['cbxIva'] as $indice => $valor) {
			// BUSCA EL IVA DE VENTA POR DEFECTO PARA CALCULAR EL EXENTO
			$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1 AND iva.idIva = %s ORDER BY iva",
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBaseImpIva = str_replace(",","",$frmTotalDcto['txtBaseImpIva'.$valor]);
			
			$txtIva = str_replace(",","",$frmTotalDcto['txtIva'.$valor]);
			$txtSubTotalIva = ($txtIdFactura > 0) ? str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valor]) : $txtBaseImpIva * $txtIva / 100;
			
			$objResponse->assign("txtSubTotalIva".$valor,"value",number_format($txtSubTotalIva, 2, ".", ","));
			
			$totalSubtotalIva += $txtSubTotalIva;
			
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

	/*-------------------------MODIFICACION PARA IMPORTACION 21/11/2017----------------------*/
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
						$queryFacturaImportacion = sprintf("SELECT * FROM cp_factura_importacion WHERE id_factura = %s;",
							valTpDato($frmDcto['txtIdFactura'], "int"));
						$rsFacturaImportacion = mysql_query($queryFacturaImportacion);
						if (!$rsFacturaImportacion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						$rowFacturaImportacion = mysql_fetch_array($rsFacturaImportacion);
	/*-----------------------------------------------------------------------------------------*/
	
	$txtTotalOrden = $txtSubTotal - $txtSubTotalDescuento + round($totalSubtotalIva, 2) + round($txtGastosConIva, 2) + round($txtGastosSinIva, 2)+round($rowFacturaImportacion['total_advalorem_diferencia'],2);
	$totalDctoPorPagar = (str_replace(",","",$frmTotalDcto['txtTotalPorPagar']) > 0 || $idModoCompra == 2) ? str_replace(",","",$frmTotalDcto['txtTotalPorPagar']) : $txtTotalOrden;
	$txtTotalPago = (str_replace(",","",$frmTotalDcto['txtTotalSaldo']) == 0 && ($txtTotalPago == 0 || $txtTotalPago > $totalDctoPorPagar)) ? $totalDctoPorPagar : $txtTotalPago;
	$txtTotalSaldo = $totalDctoPorPagar - $txtTotalPago;
	if (!($txtIdFactura > 0)) {
		$txtTotalExento = $txtSubTotal - $txtSubTotalDescuento - $txtBaseImpIvaVenta + $txtGastosConIva;
	}
	
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
	$objResponse->assign("txtTotalPedido","value",number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalPago","value",number_format($txtTotalPago, 2, ".", ","));
	
	//$objResponse->assign("txtTotalSaldo","value",number_format($txtTotalSaldo , 2, ".", ","));
	
	return $objResponse;
}

function cargaLstBanco($selId = "", $nombreObjeto = "", $onChange = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM bancos ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCuenta($idBanco, $selId = "", $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM cuentas WHERE idBanco = %s ORDER BY numeroCuentaCompania ASC",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idCuentas']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".utf8_encode($row['numeroCuentaCompania'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
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

function eliminarMetodoPago($frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaPagoDcto['cbxItm'])) {
		foreach ($frmListaPagoDcto['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmPago:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function formFactura($idFactura, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx2'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idFactura > 0) {
		// BUSCA LOS DATOS DE LA FACTURA DE IMPORTACION PARA SABER EL SALDO POR PAGAR
		$queryFactura = sprintf("SELECT cxp_fact.*,
			(CASE cxp_fact.id_modo_compra
				WHEN 1 THEN
					(IFNULL(cxp_fact.subtotal_factura, 0)
						- IFNULL(cxp_fact.subtotal_descuento, 0)
						+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
								FROM cp_factura_gasto cxp_fact_gasto
								WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
									AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
						+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
								FROM cp_factura_iva cxp_fact_iva
								WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0))
				WHEN 2 THEN
					(CASE cxp_fact.id_modulo
						WHEN 0 THEN
							IFNULL((SELECT 
										SUM(a.cantidad * (b.costo_unitario * cxp_fact_imp.tasa_cambio))
									FROM cp_factura_detalle a
										INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
										INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
									WHERE a.id_factura = cxp_fact.id_factura), 0)
							+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
									FROM cp_factura_gasto cxp_fact_gasto
									WHERE cxp_fact_gasto.id_modo_gasto IN (1)
										AND cxp_fact_gasto.afecta_documento IN (1)
										AND cxp_fact_gasto.id_factura = cxp_fact.id_factura), 0)
						WHEN 2 THEN
							IFNULL((SELECT 
										SUM((b.costo_unitario * cxp_fact_imp.tasa_cambio))
									FROM cp_factura_detalle_unidad a
										INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
										INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
									WHERE a.id_factura = cxp_fact.id_factura), 0)
							 + IFNULL((SELECT 
										SUM(a.cantidad * (b.costo_unitario * cxp_fact_imp.tasa_cambio))
									FROM cp_factura_detalle_accesorio a
										INNER JOIN cp_factura_detalle_accesorio_importacion b ON (b.id_factura_detalle_accesorio = a.id_factura_detalle_accesorio)
										INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
									WHERE a.id_factura = cxp_fact.id_factura), 0)
							+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
									FROM cp_factura_gasto cxp_fact_gasto
									WHERE cxp_fact_gasto.id_modo_gasto IN (1)
										AND cxp_fact_gasto.afecta_documento IN (1)
										AND cxp_fact_gasto.id_factura = cxp_fact.id_factura), 0)
						ELSE
							(IFNULL(cxp_fact.subtotal_factura, 0)
								- IFNULL(cxp_fact.subtotal_descuento, 0)
								+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
										FROM cp_factura_gasto cxp_fact_gasto
										WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
											AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
								+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
										FROM cp_factura_iva cxp_fact_iva
										WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0))
					END)
			END) AS total_por_pagar
		FROM cp_factura cxp_fact
		WHERE cxp_fact.id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_assoc($rsFactura);
		
		$objResponse->assign("txtTotalPorPagar","value",number_format($rowFactura['total_por_pagar'], 2, ".", ","));
		
		// ACTUALIZA EL SALDO DE LA FACTURA DE IMPORTACION
		$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
			cxp_fact.saldo_factura = (ROUND(%s, 2) - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
										WHERE cxp_pago.id_documento_pago = cxp_fact.id_factura
											AND cxp_pago.tipo_documento_pago LIKE 'FA'
											AND cxp_pago.estatus = 1), 0))
		WHERE cxp_fact.id_modo_compra IN (2)
			AND cxp_fact.estatus_factura IN (0,2)
			AND cxp_fact.id_factura = %s;",
			valTpDato($rowFactura['total_por_pagar'], "real_inglesa"),
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA EL ESTATUS DE LA FACTURA DE IMPORTACION (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
			cxp_fact.estatus_factura = (CASE
											WHEN (ROUND(cxp_fact.saldo_factura, 2) <= 0) THEN
												1
											WHEN (ROUND(cxp_fact.saldo_factura, 2) > 0 AND ROUND(cxp_fact.saldo_factura, 2) < (%s)) THEN
												2
											ELSE
												0
										END)
		WHERE cxp_fact.id_modo_compra IN (2)
			AND cxp_fact.id_factura = %s
			AND (SELECT COUNT(cxp_fact.id_factura) FROM cp_pagos_documentos cxp_pago
				WHERE cxp_pago.tipo_documento_pago LIKE 'FA'
					AND cxp_pago.estatus = 1
					AND id_documento_pago = cxp_fact.id_factura);",
			valTpDato($rowFactura['total_por_pagar'], "int"),
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		
		$objResponse->script("
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('aListarProv').style.display = 'none';
		byId('txtIdProv').readOnly = true;
		byId('txtIdProv').className = 'inputInicial';
		byId('txtNumeroFactura').readOnly = true;
		byId('txtNumeroFactura').className = 'inputInicial';
		byId('txtNumeroControl').readOnly = true;
		byId('txtNumeroControl').className = 'inputInicial';
		byId('txtFechaProveedor').readOnly = true;
		byId('txtFechaProveedor').className = 'inputInicial';
		byId('lblFechaRegistro').style.display = 'none';
		byId('lstTipoPago').className = 'inputInicial';
		byId('lstModoCompra').className = 'inputInicial';
		byId('lstAplicaLibro').className = 'inputInicial';
		byId('txtObservacionFactura').readOnly = true;
		byId('txtObservacionFactura').className = 'inputInicial';
		
		byId('btnRegistroCompraPDF').style.display = 'none';
		
		byId('txtSubTotal').readOnly = true;
		byId('txtSubTotal').className = 'inputSinFondo';
		byId('txtSubTotal').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtDescuento').readOnly = true;
		byId('txtDescuento').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtTotalExento').readOnly = true;
		byId('txtTotalExento').className = 'inputSinFondo';
		byId('txtTotalExento').onblur = function() {
			setFormatoRafk(this,2);
		}
		byId('txtTotalExonerado').readOnly = true;
		byId('txtTotalExonerado').className = 'inputSinFondo';
		byId('txtTotalExonerado').onblur = function() {
			setFormatoRafk(this,2);
		}
		
		byId('trBtnListaPagoDcto').style.display = 'none';
		byId('trListaPagoDcto').style.display = '';
		
		byId('btnGuardar').style.display = 'none';
		
		byId('fieldsetGastos').style.display = 'none';
		byId('fieldsetGastosImportación').style.display = 'none';
		byId('fieldsetDatosImportación').style.display = 'none';
		byId('fieldsetPlanMayor').style.display = 'none';");
		
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFactura = sprintf("SELECT cxp_fact.*,
			
			(SELECT retencion.idRetencionCabezera
			FROM cp_retenciondetalle retencion_det
				INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
			WHERE retencion_det.idFactura = cxp_fact.id_factura
			LIMIT 1) AS idRetencionCabezera,
			
			(CASE cxp_fact.estatus_factura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_factura,
		
			(SELECT SUM(IFNULL(cxp_fact_gasto.monto, 0)) FROM cp_factura_gasto cxp_fact_gasto
			WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
				AND cxp_fact_gasto.id_modo_gasto IN (1,3) 
				AND cxp_fact_gasto.iva > 0) AS subtotal_gastos_con_iva,
			
			(SELECT SUM(IFNULL(cxp_fact_gasto.monto, 0)) FROM cp_factura_gasto cxp_fact_gasto
			WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
				AND cxp_fact_gasto.id_modo_gasto IN (1,3) 
				AND (cxp_fact_gasto.iva = 0 OR cxp_fact_gasto.iva IS NULL)) AS subtotal_gastos_sin_iva,
			
			(SELECT SUM(cxp_fact_gasto.monto) FROM cp_factura_gasto cxp_fact_gasto
			WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
				AND cxp_fact_gasto.id_modo_gasto IN (1)
				AND afecta_documento IN (0)) AS total_gastos_no_afecta_documento,
			
			(SELECT SUM(cxp_fact_gasto.monto) FROM cp_factura_gasto cxp_fact_gasto
			WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
				AND cxp_fact_gasto.id_modo_gasto IN (3)) AS total_gastos_por_importacion,
			
			(SELECT SUM(cxp_fact_iva.subtotal_iva) FROM cp_factura_iva cxp_fact_iva
			WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura) AS total_impuestos,
			
			(CASE cxp_fact.id_modulo
				WHEN 0 THEN
					IFNULL((SELECT
						SUM(cxp_fact_det2.cantidad * (((cxp_fact_det_imp.costo_unitario + cxp_fact_det_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_imp.porcentaje_grupo) / 100)
					FROM cp_factura_detalle cxp_fact_det2
						INNER JOIN cp_factura_detalle_importacion cxp_fact_det_imp ON (cxp_fact_det2.id_factura_detalle = cxp_fact_det_imp.id_factura_detalle)
						INNER JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact_det2.id_factura = cxp_fact_imp.id_factura)
					WHERE cxp_fact_det2.id_factura = cxp_fact.id_factura), 0)
				WHEN 2 THEN
					IFNULL((SELECT
								SUM((((cxp_fact_det_unidad_imp.costo_unitario + cxp_fact_det_unidad_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_unidad_imp.porcentaje_grupo) / 100)
							FROM cp_factura_detalle_unidad cxp_fact_det_unidad
								INNER JOIN cp_factura_detalle_unidad_importacion cxp_fact_det_unidad_imp ON (cxp_fact_det_unidad.id_factura_detalle_unidad = cxp_fact_det_unidad_imp.id_factura_detalle_unidad)
								INNER JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact_det_unidad.id_factura = cxp_fact_imp.id_factura)
							WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura), 0)
					+ IFNULL((SELECT
								SUM((((cxp_fact_det_acc_imp.costo_unitario + cxp_fact_det_acc_imp.gasto_unitario) * cxp_fact_imp.tasa_cambio) * cxp_fact_det_acc_imp.porcentaje_grupo) / 100)
							FROM cp_factura_detalle_accesorio cxp_fact_det_acc
								INNER JOIN cp_factura_detalle_accesorio_importacion cxp_fact_det_acc_imp ON (cxp_fact_det_acc.id_factura_detalle_accesorio = cxp_fact_det_acc_imp.id_factura_detalle_accesorio)
								INNER JOIN cp_factura_importacion cxp_fact_imp ON (cxp_fact_det_acc.id_factura = cxp_fact_imp.id_factura)
							WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura), 0)
			END) AS subtotal_advalorem
		FROM cp_factura cxp_fact
		WHERE id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_assoc($rsFactura);
		
		// ACTUALIZA EL ADVALOREM DE LA IMPORTACION
		$updateSQL = sprintf("UPDATE cp_factura_importacion SET
			total_advalorem = %s
		WHERE id_factura = %s
			AND total_advalorem = 0;",
			valTpDato($rowFactura['subtotal_advalorem'], "real_inglesa"),
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// BUSCA LOS DATOS DE LA FACTURA DE IMPORTACION
		$queryFacturaImportacion = sprintf("SELECT * FROM cp_factura_importacion WHERE id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsFacturaImportacion = mysql_query($queryFacturaImportacion);
		if (!$rsFacturaImportacion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFacturaImportacion = mysql_fetch_assoc($rsFacturaImportacion);
		
		// BUSCA LOS DATOS DE LA NOTA DE CARGO POR PLAN MAYOR
		$queryNotaCargo = sprintf("SELECT
			cxp_nd.id_notacargo,
			cxp_nd.numero_notacargo,
			cxp_nd.numero_control_notacargo,
			cxp_nd.fecha_origen_notacargo,
			cxp_nd.fecha_notacargo,
			cxp_nd.estatus_notacargo,
			(CASE cxp_nd.estatus_notacargo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_nota_cargo,
			(CASE cxp_nd.tipo_pago_notacargo
				WHEN 0 THEN 'Contado'
				WHEN 1 THEN 'Crédito'
			END) AS tipo_pago_notacargo,
			cxp_nd.saldo_notacargo,
			modulo.descripcionModulo,
			(CASE cxp_nd.aplica_libros_notacargo
				WHEN 0 THEN 'NO'
				WHEN 1 THEN 'SI'
			END) AS aplica_libros_notacargo,
			motivo.id_motivo,
			motivo.descripcion
		FROM cp_notadecargo cxp_nd
			INNER JOIN an_unidad_fisica uni_fis ON (cxp_nd.id_detalles_pedido_compra = uni_fis.id_pedido_compra_detalle)
			INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN pg_motivo motivo ON (cxp_nd.id_motivo = motivo.id_motivo)
			INNER JOIN pg_modulos modulo ON (cxp_nd.id_modulo = modulo.id_modulo)
		WHERE cxp_fact_det_unidad.id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsNotaCargo = mysql_query($queryNotaCargo);
		if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNotaCargo = mysql_num_rows($rsNotaCargo);
		$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);
		
		if ($totalRowsNotaCargo > 0) {
			$objResponse->script("byId('fieldsetPlanMayor').style.display = '';");
		}
		
		switch($rowFactura['estatus_factura']) {
			case 0 : $claseEstatus = "divMsjError"; break;
			case 1 : $claseEstatus = "divMsjInfo"; break;
			case 2 : $claseEstatus = "divMsjAlerta"; break;
		}
		
		switch($rowFactura['activa']) {
			case 1 : $claseActiva = ""; break;
			default : $claseActiva = "divMsjError"; break;
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowFactura['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarProveedor($rowFactura['id_proveedor'], "Prov", "false"));
		$objResponse->loadCommands(asignarEmpleado($rowFactura['id_empleado_creador']));
		
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat, strtotime($rowFactura['fecha_origen'])));
		$objResponse->assign("txtIdFactura","value",$rowFactura['id_factura']);
		$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
		$objResponse->assign("txtNumeroControl","value",$rowFactura['numero_control_factura']);
		$objResponse->assign("txtFechaProveedor","value",date(spanDateFormat, strtotime($rowFactura['fecha_factura_proveedor'])));
		$objResponse->call("selectedOption","lstTipoPago",$rowFactura['tipo_pago']);
		$objResponse->call("selectedOption","lstModoCompra",$rowFactura['id_modo_compra']);
		$objResponse->loadCommands(cargaLstModulo($rowFactura['id_modulo'], true));
		$objResponse->call("selectedOption","lstAplicaLibro",$rowFactura['aplica_libros']);
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $claseEstatus));
		$objResponse->assign("txtEstatus","value",$rowFactura['estado_factura']);
		$objResponse->script(sprintf("byId('tdtxtActiva').className = '%s';", $claseActiva));
		$objResponse->assign("txtActiva","value",(($rowFactura['activa'] == 1) ? "" : "Anulada"));
		$objResponse->assign("txtObservacionFactura","value",utf8_encode($rowFactura['observacion_factura']));
		$objResponse->assign("hddIdMoneda","value",$rowFacturaImportacion['id_moneda_tasa_cambio']);
		
		$objResponse->script("
		byId('lstAplicaLibro').onchange = function() {
			selectedOption(this.id,'".$rowFactura['aplica_libros']."');
		}");
		
		$objResponse->script("
		byId('lstTipoPago').onchange = function() {
			selectedOption(this.id,'".$rowFactura['tipo_pago']."');
		}");
		
		$objResponse->script("
		byId('lstModoCompra').onchange = function() {
			selectedOption(this.id,'".$rowFactura['id_modo_compra']."');
		}");
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
		$objDcto->tipoDocumento = "FA";
		$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $rowFactura['id_modulo'];
		$objDcto->idDocumento = $rowFactura['id_factura'];
		$objDcto->mostrarDocumento = "verVentanaPDF";
		$aVerDcto = $objDcto->verDocumento();
		
		$objResponse->script("
		byId('btnRegistroCompraPDF').style.display = '';
		byId('btnRegistroCompraPDF').onclick = function() { ".$aVerDcto." }");
		
		// ASIGNA LOS DATOS DE LA NOTA DE CARGO
		switch($rowNotaCargo['estatus_notacargo']) {
			case 0 : $claseEstatus = "divMsjError"; break;
			case 1 : $claseEstatus = "divMsjInfo"; break;
			case 2 : $claseEstatus = "divMsjAlerta"; break;
		}
		
		$objResponse->assign("txtIdNotaCargo","value",$rowNotaCargo['id_notacargo']);
		$objResponse->assign("txtNumeroNotaCargo","value",$rowNotaCargo['numero_notacargo']);
		$objResponse->assign("txtNumeroControlNotaCargo","value",$rowNotaCargo['numero_control_notacargo']);
		$objResponse->assign("txtFechaRegistroNotaCargo","value",date(spanDateFormat, strtotime($rowNotaCargo['fecha_origen_notacargo'])));
		$objResponse->assign("txtFechaNotaCargo","value",date(spanDateFormat, strtotime($rowNotaCargo['fecha_notacargo'])));
		$objResponse->assign("txtTipoPago","value",$rowNotaCargo['tipo_pago_notacargo']);
		$objResponse->assign("txtModulo","value",$rowNotaCargo['descripcionModulo']);
		$objResponse->assign("txtAplicaLibro","value",$rowNotaCargo['aplica_libros_notacargo']);
		$objResponse->assign("txtIdMotivo","value",$rowNotaCargo['id_motivo']);
		$objResponse->assign("txtMotivo","value",$rowNotaCargo['descripcion']);
		$objResponse->script(sprintf("byId('tdtxtEstatusNotaCargo').className = '%s';", $claseEstatus));
		$objResponse->assign("txtEstatusNotaCargo","value",$rowNotaCargo['estado_nota_cargo']);
		
		$objResponse->script(sprintf("byId('aVerNotaCargo').href = 'cp_nota_cargo_form.php?id=%s&vw=v';", $rowNotaCargo['id_notacargo']));
		
		// CARGA LOS GASTOS
		$queryGasto = sprintf("SELECT
			cxp_fact_gasto.id_gasto,
			gasto.nombre,
			cxp_fact_gasto.tipo,
			cxp_fact_gasto.porcentaje_monto,
			cxp_fact_gasto.monto,
			cxp_fact_gasto.id_iva,
			cxp_fact_gasto.iva,
			cxp_fact_gasto.id_modo_gasto,
			cxp_fact_gasto.afecta_documento
		FROM cp_factura_gasto cxp_fact_gasto
			INNER JOIN pg_gastos gasto ON (cxp_fact_gasto.id_gasto = gasto.id_gasto)
		WHERE cxp_fact_gasto.id_factura = %s
			AND cxp_fact_gasto.id_modo_gasto IN (1);",
			valTpDato($idFactura, "int"));
		$rsGasto = mysql_query($queryGasto);
		if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsGasto = mysql_num_rows($rsGasto);
		$indice = 0;
		while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
			$indice++;
			
			$html = "";
			if ($rowGasto['id_iva'] > 0) {
				$html .= "<table id=\"trIvaGasto".$indice."\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img id=\"imgIvaGasto".$indice."\" name=\"imgIvaGasto".$indice."\" src=\"../img/iconos/accept.png\"/>"."</td>";
					$html .= "<td align=\"right\">";
						$html .= sprintf("<input type=\"text\" id=\"hddIvaGasto%s\" name=\"hddIvaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdIvaGasto%s\" name=\"hddIdIvaGasto%s\" value=\"%s\">",
							$indice, $indice, $rowGasto['iva'],
							$indice, $indice, $rowGasto['id_iva']);
					$html .= "</td>";
					$html .= "<td>%</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
			if ($rowGasto['id_modo_gasto'] == 1 && $rowGasto['afecta_documento'] == 0) {
				$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trGasto:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trGasto:%s\">%s:</div>".
						"<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddTipoGasto%s\" name=\"hddTipoGasto%s\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td><input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td>%s</td>".
				"</tr>';
				
				obj = byId('trGasto:%s');
				if(obj == undefined)
					$('#trItmPieGastos').before(elemento);",
				$indice,
					$indice, utf8_encode($rowGasto['nombre']),
						$indice,
						$indice, $indice, $rowGasto['id_gasto'],
						$indice, $indice, $rowGasto['tipo'],
					$indice, $indice, $rowGasto['porcentaje_monto'], "%",
					$indice, $indice, number_format(round($rowGasto['monto'],2), 2, ".", ","),
					$html,
				
				$indice));
		}
		if ($totalRowsGasto > 0) {
			$objResponse->script("byId('fieldsetGastos').style.display = '';");
		}
		
		// CARGA LOS GASTOS POR IMPORTACION
		$queryGasto = sprintf("SELECT
			cxp_fact_gasto.id_gasto,
			gasto.nombre,
			cxp_fact_gasto.tipo,
			cxp_fact_gasto.porcentaje_monto,
			cxp_fact_gasto.monto,
			cxp_fact_gasto.id_iva,
			cxp_fact_gasto.iva,
			cxp_fact_gasto.id_modo_gasto,
			cxp_fact_gasto.afecta_documento
		FROM cp_factura_gasto cxp_fact_gasto
			INNER JOIN pg_gastos gasto ON (cxp_fact_gasto.id_gasto = gasto.id_gasto)
		WHERE cxp_fact_gasto.id_factura = %s
			AND cxp_fact_gasto.id_modo_gasto IN (3);",
			valTpDato($idFactura, "int"));
		$rsGasto = mysql_query($queryGasto);
		if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsGasto = mysql_num_rows($rsGasto);
		//$indice = 0;
		while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
			$indice++;
			
			$html = "";
			if ($rowGasto['id_iva'] > 0) {
				$html .= "<table id=\"trIvaGasto".$indice."\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img id=\"imgIvaGasto".$indice."\" name=\"imgIvaGasto".$indice."\" src=\"../img/iconos/accept.png\" title=\"Aplica Impuesto\"/>"."</td>";
					$html .= "<td align=\"right\">";
						$html .= sprintf("<input type=\"text\" id=\"hddIvaGasto%s\" name=\"hddIvaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdIvaGasto%s\" name=\"hddIdIvaGasto%s\" value=\"%s\">",
							$indice, $indice, $rowGasto['iva'],
							$indice, $indice, $rowGasto['id_iva']);
					$html .= "</td>";
					$html .= "<td>%</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
			if ($rowGasto['id_modo_gasto'] == 1 && $rowGasto['afecta_documento'] == 0) {
				$html .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trGastoImportacion:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trGastoImportacion:%s\">%s:</div>".
						"<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddTipoGasto%s\" name=\"hddTipoGasto%s\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td><input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td>%s</td>".
				"</tr>';
				
				obj = byId('trGastoImportacion:%s');
				if(obj == undefined)
					$('#trItmPieGastosImportacion').before(elemento);",
				$indice,
					$indice, utf8_encode($rowGasto['nombre']),
						$indice,
						$indice, $indice, $rowGasto['id_gasto'],
						$indice, $indice, $rowGasto['tipo'],
					$indice, $indice, $rowGasto['porcentaje_monto'], "%",
					$indice, $indice, number_format(round($rowGasto['monto'],2), 2, ".", ","),
					$html,
				
				$indice));
		}
		if ($totalRowsGasto > 0) {
			$objResponse->script("byId('fieldsetGastosImportación').style.display = '';");
		}
		if ($rowFacturaImportacion['total_advalorem'] > 0) {
			$objResponse->script("byId('fieldsetDatosImportación').style.display = '';");
		}
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT
			cxp_fact_iva.id_factura_iva,
			cxp_fact_iva.id_factura,
			cxp_fact_iva.base_imponible,
			cxp_fact_iva.subtotal_iva,
			cxp_fact_iva.id_iva,
			cxp_fact_iva.iva,
			iva.observacion
		FROM cp_factura_iva cxp_fact_iva
			INNER JOIN pg_iva iva ON (cxp_fact_iva.id_iva = iva.idIva)
		WHERE cxp_fact_iva.id_factura = %s
		ORDER BY iva",
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
						"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
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
						$indice,
					$indice, $indice, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
					$indice, $indice, $rowIva['iva'], "%",
					$indice, $indice, number_format(round($rowIva['subtotal_iva'],2), 2, ".", ","),
				
				$indice));
		}
		
		$objResponse->assign("txtTotalGastosNoAfecta","value",number_format($rowFactura['total_gastos_no_afecta_documento'], 2, ".", ","));
		$objResponse->assign("txtTotalGastosImportacion","value",number_format($rowFactura['total_gastos_por_importacion'], 2, ".", ","));
		$objResponse->assign("txtTotalImpuesto","value",number_format($rowFactura['total_impuestos'], 2, ".", ","));
		$objResponse->assign("txtMontoAdvalorem","value",number_format($rowFacturaImportacion['total_advalorem'], 2, ".", ","));
		$porcDescuento = $rowFactura['subtotal_descuento'] * 100 / $rowFactura['subtotal_factura'];
		$objResponse->assign("txtSubTotal","value",number_format($rowFactura['subtotal_factura'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($porcDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowFactura['subtotal_descuento'], 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($rowFactura['subtotal_gastos_con_iva'], 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($rowFactura['subtotal_gastos_sin_iva'], 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($rowFactura['monto_exento'], 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowFactura['monto_exonerado'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowFactura['saldo_factura'], 2, ".", ","));
		
		// BUSCA LOS PAGOS DEL DOCUMENTO
		$query = sprintf("SELECT * FROM cp_pagos_documentos cxp_pago
		WHERE cxp_pago.tipo_documento_pago LIKE 'FA'
			AND cxp_pago.id_documento_pago = %s;",
			valTpDato($idFactura, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemMetodoPago($contFila, $row['id_pago']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		if ($rowFactura['saldo_factura'] > 0 && $_GET['vw'] != "v" && $_GET['vw'] != "e") {
			$objResponse->script("
			byId('trBtnListaPagoDcto').style.display = '';
			
			byId('btnGuardar').style.display = '';");
		} else if ((date(str_replace("d","01",spanDateFormat), strtotime($rowFactura['fecha_origen'])) == date(str_replace("d","01",spanDateFormat)) || in_array($row['estatus_factura'],array(0))) && $_GET['vw'] == "e") {
			$objResponse->script("
			byId('lstAplicaLibro').readOnly = false;
			byId('lstAplicaLibro').className = 'inputHabilitado';
			byId('lstAplicaLibro').onchange = function() { }
			byId('txtNumeroFactura').readOnly = false;
			byId('txtNumeroFactura').className = 'inputHabilitado';
			byId('txtNumeroControl').readOnly = false;
			byId('txtNumeroControl').className = 'inputHabilitado';
			byId('txtFechaProveedor').readOnly = false;
			byId('txtFechaProveedor').className = 'inputHabilitado';
			byId('txtObservacionFactura').readOnly = false;
			byId('txtObservacionFactura').className = 'inputHabilitado';
			
			byId('btnGuardar').style.display = '';");
			
			$objResponse->script("
			jQuery(function($){
				$(\"#txtFechaProveedor\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			});
			
			new JsDatePick({
				useMode:2,
				target:\"txtFechaProveedor\",
				dateFormat:\"".spanDatePick."\",
				cellColorScheme:\"torqoise\"
			});");
		}
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdProv').className = 'inputHabilitado';
		byId('txtNumeroFactura').className = 'inputHabilitado';
		byId('txtNumeroControl').className = 'inputHabilitado';
		byId('txtFechaProveedor').className = 'inputHabilitado';
		byId('lstTipoPago').className = 'inputHabilitado';
		byId('lstModoCompra').className = 'inputHabilitado';
		byId('lstAplicaLibro').className = 'inputHabilitado';
		byId('txtObservacionFactura').className = 'inputHabilitado';
		
		byId('btnRegistroCompraPDF').style.display = 'none';
		
		byId('txtSubTotal').className = 'inputHabilitado';
		byId('txtSubTotal').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('rbtInicialMonto').checked = true;
		
		byId('txtTotalExento').className = 'inputHabilitado';
		byId('txtTotalExento').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtTotalExonerado').className = 'inputHabilitado';
		byId('txtTotalExonerado').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('trBtnListaPagoDcto').style.display = 'none';
		byId('trListaPagoDcto').style.display = 'none';
		
		byId('fieldsetGastos').style.display = 'none';
		byId('fieldsetGastosImportación').style.display = 'none';
		byId('fieldsetDatosImportación').style.display = 'none';
		byId('fieldsetPlanMayor').style.display = 'none';");
		
		// CARGA LOS IMPUESTOS
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,8,3) AND iva.estado = 1 ORDER BY iva");
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
					xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));
				}
				byId('txtBaseImpIva%s').onkeypress = function(e) {
					return validarSoloNumerosReales(e);
				}",
				$indiceIva,
					$indiceIva, utf8_encode($rowIva['observacion']),
						$indiceIva, $indiceIva, $rowIva['idIva'],
						$indiceIva,
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
					$indiceIva, $indiceIva, $rowIva['iva'], "%",
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
				
				$indiceIva,
				
				$indiceIva,
				
				$indiceIva));
		}
		
		$objResponse->assign("tdGastos","innerHTML",formularioGastos());
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstModulo());
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts']));
		$objResponse->assign("txtSubTotal","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format(0, 2, ".", ","));
		//$objResponse->assign("txtTotalSaldo","value",number_format(0, 2, ".", ","));
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
		
		$objResponse->script("
		jQuery(function($){
			$(\"#txtFechaProveedor\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		});
		
		new JsDatePick({
			useMode:2,
			target:\"txtFechaProveedor\",
			dateFormat:\"".spanDatePick."\",
			cellColorScheme:\"torqoise\"
		});");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaPagoDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx2'];
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idFactura = $frmDcto['txtIdFactura'];
	$idModulo = $frmDcto['lstModulo'];
	
	mysql_query("START TRANSACTION;");
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig17 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion IN (17,102,205,402) AND config_emp.status = 1 AND config_emp.id_empresa = %s AND config.id_modulo = %s;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idModulo, "int"));
	$rsConfig17 = mysql_query($queryConfig17);
	if (!$rsConfig17) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig17 = mysql_num_rows($rsConfig17);
	$rowConfig17 = mysql_fetch_assoc($rsConfig17);
	
	$valor = explode("|",$rowConfig17['valor']);
	
	$txtFechaRegistroCompra = date(spanDateFormat);
	$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
	if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
		if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
			if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
			|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
				$txtFechaRegistroCompra = $txtFechaProveedor;
			} else {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
			}
		} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
			$objResponse->script("byId('cbxFechaRegistro').checked = false;");
			$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
		} else {
			$txtFechaRegistroCompra = $txtFechaProveedor;
		}
	} else if ($frmDcto['cbxFechaRegistro'] == 1) {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
		return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
	}
		
	// CONSULTA FECHA ORIGEN DE LA FACTURA
	$query = sprintf("SELECT * FROM cp_factura
	WHERE id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
				
	if ($idFactura > 0) {
		if ((date(str_replace("d","01",spanDateFormat), strtotime($row['fecha_origen'])) == date(str_replace("d","01",spanDateFormat)) || in_array($row['estatus_factura'],array(0))) && $_GET['vw'] == "e") {
			// ACTUALIZA LOS DATOS DE LA FACTURA
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				cxp_fact.numero_factura_proveedor = %s,
				cxp_fact.numero_control_factura = %s,
				cxp_fact.fecha_factura_proveedor = %s,
				cxp_fact.observacion_factura = %s,
				cxp_fact.aplica_libros = %s
			WHERE cxp_fact.id_factura = %s;",
				valTpDato($frmDcto['txtNumeroFactura'], "text"),
				valTpDato($frmDcto['txtNumeroControl'], "text"),
				valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
				valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
				valTpDato($frmDcto['lstAplicaLibro'], "int"),
				valTpDato($idFactura,'int'));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
			// ACTUALIZA LOS DATOS DEL DETALLE DE LA RETENCION
			$updateSQL = sprintf("UPDATE cp_retenciondetalle SET
				numeroControlFactura = %s,
				fechaFactura = %s
			WHERE idFactura = %s;",
				valTpDato($frmDcto['txtNumeroControl'], "text"),
				valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
				valTpDato($idFactura,'int'));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// ACTUALIZA LOS DATOS DE LA FACTURA SI PERTENECE A UN TOT
			$updateSQL = sprintf("UPDATE sa_orden_tot SET
				numero_factura_proveedor = %s,
				numero_control_factura = %s,
				fecha_factura_proveedor = %s
			WHERE id_factura = %s;",
				valTpDato($frmDcto['txtNumeroFactura'], "text"),
				valTpDato($frmDcto['txtNumeroControl'], "text"),
				valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
				valTpDato($idFactura,'int'));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmListaPagoDcto['hddIdPago'.$valor] == 0) {
					$insertSQL = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
					VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato('FA', "text"),
						valTpDato($frmListaPagoDcto['txtMetodoPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "text"),
						valTpDato(date("Y-m-d", strtotime($frmListaPagoDcto['txtFechaPago'.$valor])), "text"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($frmListaPagoDcto['txtNumeroDctoPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtBancoProveedorPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtBancoCompaniaPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtCuentaProveedorPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtCuentaCompaniaPago'.$valor], "text"),
						valTpDato($frmListaPagoDcto['txtMontoPago'.$valor], "real_inglesa"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					switch ($frmListaPagoDcto['txtMetodoPago'.$valor]) {
						case "Transferencia" :
							break;
						case "Cheque" :
							break;
						case "AN" :
							// VERIFICA SI EL SALDO DEL ANTICIPO ES MAYOR AL MONTO
							$query = sprintf("SELECT * FROM cp_anticipo
							WHERE id_anticipo = %s
								AND saldoanticipo >= %s;",
								valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"),
								str_replace(",","",$frmListaPagoDcto['txtMontoPago'.$valor]));
							$rs = mysql_query($query);
							if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRows = mysql_num_rows($rs);
							$row = mysql_fetch_assoc($rs);
							
							if ($totalRows > 0) {
								// ACTUALIZA EL SALDO DEL ANTICIPO
								$updateSQL = sprintf("UPDATE cp_anticipo SET
									saldoanticipo = saldoanticipo - %s
								WHERE id_anticipo = %s;",
									valTpDato($frmListaPagoDcto['txtMontoPago'.$valor], "real_inglesa"),
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								$Result1 = mysql_query($queryAnticipoActualiza);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								
								// ACTUALIZA EL ESTATUS DEL ANTICIPO
								$updateSQL = sprintf("UPDATE cp_anticipo SET
									estado = (CASE
													WHEN saldoanticipo = 0 THEN	3
													WHEN saldoanticipo > 0 THEN	2
												END)
								WHERE id_anticipo = %s;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								$Result1 = mysql_query($queryAnticipoActualiza);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							} else {
								return $objResponse->alert("No posee saldo suficiente en el Anticipo, elimine dicho pago y agréguelo nuevamente");
							}
							break;
						case "NC" :
							// VERIFICA SI EL SALDO DE LA NOTA DE CREDITO ES MAYOR AL MONTO
							$query = sprintf("SELECT * FROM cp_notacredito
							WHERE id_notacredito = %s
								AND saldo_notacredito >= %s;",
								valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"),
								str_replace(",","",$frmListaPagoDcto['txtMontoPago'.$valor]));
							$rs = mysql_query($query);
							if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRows = mysql_num_rows($rs);
							$row = mysql_fetch_assoc($rs);
							
							if ($totalRows > 0) {
								// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
								$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
									saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
														- IFNULL(cxp_nc.subtotal_descuento, 0)
														+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
																WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
														+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
																WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
														) - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
															WHERE ((tipo_pago LIKE 'NC' AND id_documento = cxp_nc.id_notacredito)
																	OR (tipo_documento_pago LIKE 'NC' AND id_documento_pago = cxp_nc.id_notacredito))
																AND cxp_pago.estatus = 1), 0)
								WHERE id_notacredito = %s
									AND estado_notacredito NOT IN (3);",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
								
								// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Sin Asignar, 2 = Asignado Parcial, 3 = Asignado)
								$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
									estado_notacredito = (CASE
															WHEN (saldo_notacredito = 0) THEN
																3
															WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL(cxp_nc.subtotal_notacredito, 0)
																		- IFNULL(cxp_nc.subtotal_descuento, 0)
																		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
																				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
																		+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
																				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))) THEN
																2
															WHEN (saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
																		- IFNULL(cxp_nc.subtotal_descuento, 0)
																		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
																				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
																		+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
																				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))) THEN
																1
															ELSE
																0
														END)
								WHERE id_notacredito = %s;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								
								// VERIFICA EL SALDO DE LA NOTA DE CREDITO A VER SI ESTA NEGATIVO
								$querySaldoDcto = sprintf("SELECT cxp_nc.*,
									prov.nombre AS nombre_proveedor
								FROM cp_notacredito cxp_nc
									INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)
								WHERE cxp_nc.id_notacredito = %s
									AND ROUND(cxp_nc.saldo_notacredito, 2) < 0;",
									valTpDato($frmListaPagoDcto['txtIdNumeroDctoPago'.$valor], "int"));
								$rsSaldoDcto = mysql_query($querySaldoDcto);
								if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
								$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
								$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
								if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Crédito Nro. ".$rowSaldoDcto['numero_nota_credito']." del Proveedor ".$rowSaldoDcto['nombre_proveedor']." presenta un saldo negativo"); }
							} else {
								return $objResponse->alert("No posee saldo suficiente en la Nota de Crédito, elimine dicho pago y agréguelo nuevamente");
							}
							break;
					}
					
					// ACTUALIZA EL SALDO DE LA FACTURA
					$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
						cxp_fact.saldo_factura = (ROUND(IFNULL(cxp_fact.total_cuenta_pagar, 0), 2)
													- IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
															WHERE cxp_pago.id_documento_pago = cxp_fact.id_factura
																AND cxp_pago.tipo_documento_pago LIKE 'FA'
																AND cxp_pago.estatus = 1), 0))
					WHERE cxp_fact.id_factura = %s
						AND cxp_fact.estatus_factura NOT IN (1);",
						valTpDato($idFactura, "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
					$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
						cxp_fact.estatus_factura = (CASE
														WHEN (ROUND(cxp_fact.saldo_factura, 2) = 0 OR ROUND(cxp_fact.saldo_factura, 2) < 0) THEN
															1
														WHEN (ROUND(cxp_fact.saldo_factura, 2) > 0
															AND ROUND(cxp_fact.saldo_factura, 2) < ROUND(cxp_fact.total_cuenta_pagar, 2)) THEN
															2
														ELSE
															0
													END)
					WHERE cxp_fact.id_factura = %s;",
						valTpDato($idFactura, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// VERIFICA EL SALDO DE LA FACTURA A VER SI ESTA NEGATIVO
					$querySaldoDcto = sprintf("SELECT cxp_fact.*,
						prov.nombre AS nombre_proveedor
					FROM cp_factura cxp_fact
						INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
					WHERE cxp_fact.id_factura = %s
						AND ROUND(cxp_fact.saldo_factura, 2) < 0;",
						valTpDato($idFactura, "int"));
					$rsSaldoDcto = mysql_query($querySaldoDcto);
					if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
					$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
					$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
					if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Factura Nro. ".$rowSaldoDcto['numero_factura_proveedor']." del Proveedor ".$rowSaldoDcto['nombre_proveedor']." presenta un saldo negativo"); }
				}
			}
		}
		
		mysql_query("COMMIT;");
	} else {
		$queryProv = sprintf("SELECT prov.credito, prov_cred.*
		FROM cp_proveedor prov
			LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
		WHERE prov.id_proveedor = %s;",
			valTpDato($frmDcto['txtIdProv'], "int"));
		$rsProv = mysql_query($queryProv);
		if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProv = mysql_fetch_assoc($rsProv);
		
		$txtDiasCreditoProv = ($rowProv['diascredito'] > 0) ? $rowProv['diascredito'] : 0;
		
		// SI EL PROVEEDOR ES A CREDITO SE LE SUMA LA CANTIDAD DE DIAS DE CREDITO PARA LA FECHA DE VENCIMIENTO
		$fechaVencimiento = ($rowProv['credito'] == "Si") ? suma_fechas(spanDateFormat,$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
		
		// INSERTAR LOS DATOS DE LA FACTURA
		$insertSQL = sprintf("INSERT INTO cp_factura (id_empresa, id_modo_compra, numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_moneda, id_modulo, id_pedido_compra, id_orden_compra, estatus_factura, observacion_factura, tipo_pago, monto_exento, monto_exonerado, subtotal_factura, porcentaje_descuento, subtotal_descuento, total_cuenta_pagar, saldo_factura, chasis, aplica_libros, activa, fecha_registro, id_empleado_creador)
		VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($frmDcto['lstModoCompra'], "int"), // 1 = Nacional, 2 = Importacion
			valTpDato($frmDcto['txtNumeroFactura'], "text"),
			valTpDato($frmDcto['txtNumeroControl'], "text"),
			valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
			valTpDato("", "int"),
			valTpDato($idModulo, "int"),
			valTpDato("", "int"),
			valTpDato("", "int"),
			valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
			valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
			valTpDato($frmDcto['lstTipoPago'], "int"), // 0 = Contado, 1 = Credito
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalPedido'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalPedido'], "real_inglesa"),
			valTpDato("", "text"),
			valTpDato($frmDcto['lstAplicaLibro'], "int"),
			valTpDato(1, "int"), // Null = Anulada, 1 = Activa
			valTpDato("NOW()", "campo"), // Null = Anulada, 1 = Activa
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idFactura = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	
		$arrayIdDctoContabilidad = array(
			$idFactura,
			$idModulo,
			"COMPRA_CXP");
		
		// INSERTA LOS GASTOS DEL PEDIDO
		if (isset($frmTotalDcto['cbxGasto'])) {
			foreach ($frmTotalDcto['cbxGasto'] as $indice => $valor) {
				// BUSCA LOS DATOS DEL GASTO
				$queryGasto = sprintf("SELECT * FROM pg_gastos
				WHERE id_gasto = %s;",
					valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
				$rsGasto = mysql_query($queryGasto);
				if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowGastos = mysql_fetch_assoc($rsGasto);
				
				if ($idModoCompra == 2 && $rowGastos['id_modo_gasto'] == 1) { // 2 = Importacion && 1 = Gastos
					$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor]) * $txtTasaCambio;
					$porcMontoGasto = ($montoGasto * 100) / $txtSubTotal;
				} else {
					$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor]);
					$porcMontoGasto = str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valor]);
				}
				
				if ($montoGasto != 0) {
					$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto, estatus_iva, id_iva, iva, id_modo_gasto, afecta_documento)
					SELECT %s, id_gasto, %s, %s, %s, estatus_iva, %s, %s, id_modo_gasto, afecta_documento
					FROM pg_gastos
					WHERE id_gasto = %s;",
						valTpDato($idFactura, "int"),
						valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
						valTpDato($porcMontoGasto, "real_inglesa"),
						valTpDato($montoGasto, "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"),
						valTpDato($frmTotalDcto['hddIvaGasto'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				if (str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
					$insertSQL = sprintf("INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva)
					VALUE (%s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIva'.$valor], "real_inglesa"),
						valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("FA", "text"),
			valTpDato($idFactura, "int"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		mysql_query("COMMIT;");
			
		$objResponse->assign("txtIdFactura","value",$idFactura);
		
		if (isset($arrayIdDctoContabilidad)) {
			foreach ($arrayIdDctoContabilidad as $indice => $valor) {
				$idModulo = $arrayIdDctoContabilidad[$indice][1];
				$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
				
				// MODIFICADO ERNESTO
				if ($tipoDcto == "COMPRA_CXP") {
					$idFactura = $arrayIdDctoContabilidad[$indice][0];
					switch ($idModulo) {
						case 0 : if (function_exists("generarComprasRe")) { generarComprasRe($idFactura,"",""); } break;
						case 1 : if (function_exists("generarComprasSe")) { generarComprasSe($idFactura,"",""); } break;
						case 2 : if (function_exists("generarComprasVe")) { generarComprasVe($idFactura,"",""); } break;
						case 3 : if (function_exists("generarComprasAd")) { generarComprasAd($idFactura,"",""); } break;
					}
				}
				// MODIFICADO ERNESTO
			}
		}
	}
	
	$objResponse->alert("Factura Guardado con Éxito");
	
	$comprobanteRetencion = ($frmTotalDcto['rbtRetencion'] == 1) ? 0 : 1;
	
	$objResponse->script(sprintf("window.location.href='cp_factura_historico_list.php';"));
	
	return $objResponse;
}

function insertarMetodoPago($frmMetodoPago, $frmListaPagoDcto) {
	$objResponse = new xajaxResponse();
	
	$arrayMetodoPago = array(1 => "Transferencia", 2 => "Cheque", 3 => "AN", 4 => "NC");
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaPagoDcto['cbx2'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	switch($frmMetodoPago['lstMetodoPago']) { // 1 = Transferencia, 2 = Cheque, 3 = Anticipo, 4 = Nota Credito
		case 1 : // Transferencia
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoCompaniaTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoCompaniaPago = $row['nombreBanco'];
			
			// BUSCA LOS DATOS DE LA CUENTA
			$query = sprintf("SELECT * FROM cuentas
			WHERE idCuentas = %s;",
				valTpDato($frmMetodoPago['lstCuentaCompaniaTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtCuentaCompaniaPago = $row['numeroCuentaCompania'];
			
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoProveedorTransferencia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoProveedorPago = $row['nombreBanco'];
			
			$Result1 = insertarItemMetodoPago($contFila, "", date(spanDateFormat), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], "", $frmMetodoPago['txtNumeroTransferencia'], $txtBancoCompaniaPago, $txtCuentaCompaniaPago, $txtBancoProveedorPago, $frmMetodoPago['txtCuentaProveedorTransferencia'], str_replace(",","",$frmMetodoPago['txtMontoTransferencia']));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
			break;
		case 2 : // Cheque
			// BUSCA LOS DATOS DEL BANCO 
			$query = sprintf("SELECT * FROM bancos
			WHERE idBanco = %s;",
				valTpDato($frmMetodoPago['lstBancoCompaniaCheque'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtBancoCompaniaPago = $row['nombreBanco'];
			
			// BUSCA LOS DATOS DE LA CUENTA
			$query = sprintf("SELECT * FROM cuentas
			WHERE idCuentas = %s;",
				valTpDato($frmMetodoPago['lstCuentaCompaniaCheque'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$txtCuentaCompaniaPago = $row['numeroCuentaCompania'];
			
			$Result1 = insertarItemMetodoPago($contFila, "", date(spanDateFormat), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], "", $frmMetodoPago['txtNumeroCheque'], $txtBancoCompaniaPago, $txtCuentaCompaniaPago, "-", "-", str_replace(",","",$frmMetodoPago['txtMontoCheque']));
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
			break;
		case 3 : // AN
			// VERIFICA SI EL SALDO DEL ANTICIPO ES MAYOR AL MONTO
			$query = sprintf("SELECT * FROM cp_anticipo
			WHERE id_anticipo = %s
				AND saldoanticipo >= %s;",
				valTpDato($frmMetodoPago['txtIdAnticipo'], "int"),
				str_replace(",","",$frmMetodoPago['txtMontoAnticipo']));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			if ($totalRows > 0) {
				$Result1 = insertarItemMetodoPago($contFila, "", date(spanDateFormat), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], $frmMetodoPago['txtIdAnticipo'], $frmMetodoPago['txtNumeroAnticipo'], "-", "-", "-", "-", str_replace(",","",$frmMetodoPago['txtMontoAnticipo']));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			} else {
				$objResponse->loadCommands(asignarAnticipo($frmMetodoPago['txtIdAnticipo'], "Anticipo"));
				
				return $objResponse->alert("No posee saldo suficiente en el Anticipo");
			}
			break;
		case 4 : // NC
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if (!($frmListaPagoDcto['hddIdPago'.$valor] > 0)
					&& $frmListaPagoDcto['txtMetodoPago'.$valor] == $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']]
					&& $frmListaPagoDcto['txtIdNumeroDctoPago'.$valor] == $frmMetodoPago['txtIdNotaCredito']) {
						return $objResponse->alert("Este item ya se encuentra incluido");
					}
				}
			}
			
			// VERIFICA SI EL SALDO DE LA NOTA DE CREDITO ES MAYOR AL MONTO
			$query = sprintf("SELECT * FROM cp_notacredito
			WHERE id_notacredito = %s
				AND saldo_notacredito >= %s;",
				valTpDato($frmMetodoPago['txtIdNotaCredito'], "int"),
				str_replace(",","",$frmMetodoPago['txtMontoNotaCredito']));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			if ($totalRows > 0) {
				$Result1 = insertarItemMetodoPago($contFila, "", date(spanDateFormat), $arrayMetodoPago[$frmMetodoPago['lstMetodoPago']], $frmMetodoPago['txtIdNotaCredito'], $frmMetodoPago['txtNumeroNotaCredito'], "-", "-", "-", "-", str_replace(",","",$frmMetodoPago['txtMontoNotaCredito']));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			} else {
				$objResponse->loadCommands(asignarNotaCredito($frmMetodoPago['txtIdNotaCredito'], "NotaCredito"));
				
				return $objResponse->alert("No posee saldo suficiente en la Nota de Crédito");
			}
			break;
	}
	
	$objResponse->script("
	byId('btnCancelarMetodoPago').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function listaAnticipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_ant.estado NOT IN (0,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
		valTpDato($valCadBusq[1], "int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR CONCAT_WS('', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_ant.numeroAnticipo LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxp_ant.id_anticipo,
		cxp_ant.fechaanticipo,
		cxp_ant.numeroAnticipo,
		prov.nombre,
		cxp_ant.total,
		cxp_ant.saldoanticipo,
		cxp_ant.estado
	FROM cp_anticipo cxp_ant
		INNER JOIN cp_proveedor prov ON (cxp_ant.id_proveedor = prov.id_proveedor) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "fechaanticipo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "18%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Anticipo"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "16%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarAnticipo('".$row['id_anticipo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaanticipo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroAnticipo']."</td>";
			$htmlTb .= "<td>".utf8_decode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nc.estado_notacredito NOT IN (0,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = cxp_nc.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = cxp_nc.id_empresa))",
		valTpDato($valCadBusq[1], "int"),
		valTpDato($valCadBusq[1], "int"),
		valTpDato($valCadBusq[1], "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
		valTpDato($valCadBusq[2], "int"));
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR CONCAT_WS('', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_nc.numero_nota_credito LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxp_nc.id_notacredito,
		cxp_nc.fecha_notacredito,
		cxp_nc.numero_nota_credito,
		prov.nombre AS nombre_proveedor,
		cxp_nc.observacion_notacredito,
		
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		
		(IFNULL(cxp_nc.subtotal_notacredito, 0)
			- IFNULL(cxp_nc.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
					WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito), 0)
			+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
					WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
		) AS total,
		
		cxp_nc.saldo_notacredito,
		cxp_nc.estado_notacredito,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_proveedor prov
		INNER JOIN cp_notacredito cxp_nc ON (prov.id_proveedor = cxp_nc.id_proveedor)
		LEFT JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "20%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "fecha_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Nota de Crédito Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "numero_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "44%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "saldo_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Saldo Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, ("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNotaCredito('".$row['id_notacredito']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_notacredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_nota_credito']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= ($row['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacion_notacredito']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_notacredito'])."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_notacredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNotaCredito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor prov %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarAnticipo");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarFechaRegistro");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"asignarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstBanco");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"eliminarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"formFactura");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarMetodoPago");
$xajax->register(XAJAX_FUNCTION,"listaAnticipo");
$xajax->register(XAJAX_FUNCTION,"listaNotaCredito");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"reconversion");

function insertarItemMetodoPago($contFila, $idPago = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoProveedorPago = "", $txtCuentaProveedorPago = "", $txtMontoPago = "") {
	global $raiz;
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("SELECT cxp_pago.*,
			
			(CASE cxp_pago.tipo_pago
				WHEN 'NC' THEN
					(SELECT cxp_nc.id_departamento_notacredito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = cxp_pago.id_documento)
				WHEN 'AN' THEN
					(SELECT cxp_ant.idDepartamento FROM cp_anticipo cxp_ant WHERE cxp_ant.id_anticipo = cxp_pago.id_documento)
				WHEN 'TRANSFERENCIA' THEN
					NULL
				WHEN 'CHEQUE' THEN
					NULL
				ELSE
					NULL
			END) AS id_modulo_documento_pago,
			
			(CASE cxp_pago.tipo_pago
				WHEN 'NC' THEN
					(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = cxp_pago.id_documento)
				WHEN 'AN' THEN
					(SELECT cxp_ant.numeroAnticipo FROM cp_anticipo cxp_ant WHERE cxp_ant.id_anticipo = cxp_pago.id_documento)
				WHEN 'TRANSFERENCIA' THEN
					IFNULL((SELECT transf.numero_transferencia FROM te_transferencia transf WHERE transf.id_transferencia = cxp_pago.id_documento),
						cxp_pago.numero_documento)
				WHEN 'CHEQUE' THEN
					IFNULL((SELECT cheque.numero_cheque FROM te_cheques cheque WHERE cheque.id_cheque = cxp_pago.id_documento),
						cxp_pago.numero_documento)
				ELSE
					cxp_pago.numero_documento
			END) AS numero_documento,
			
			(CASE cxp_pago.tipo_pago
				WHEN 'NC' THEN
					(SELECT CONCAT(motivo.id_motivo, '.- ', motivo.descripcion)
					FROM cp_notacredito cxp_nc
						INNER JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
					WHERE cxp_nc.id_notacredito = cxp_pago.id_documento)
			END) AS descripcion_motivo,
			
			(CASE cxp_pago.tipo_pago
				WHEN 'NC' THEN
					(SELECT cxp_nc.observacion_notacredito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = cxp_pago.id_documento)
				WHEN 'ISLR' THEN
					(SELECT
						(CASE ret_cheque.tipo_documento
							WHEN 0 THEN
								CONCAT('RETENCION DEL CHEQUE NRO.', IFNULL(cheque.numero_cheque, cheque_anulado.numero_cheque))
							WHEN 1 THEN
								CONCAT('RETENCION DE LA TRANSFERENCIA NRO.', IFNULL(transferencia.numero_transferencia, transferencia_anulada.numero_transferencia))
						END)
					FROM te_retencion_cheque ret_cheque
						LEFT JOIN te_cheques cheque ON (ret_cheque.id_cheque = cheque.id_cheque
							AND ret_cheque.tipo_documento = 0)
						LEFT JOIN te_cheques_anulados cheque_anulado ON (ret_cheque.id_cheque = cheque_anulado.id_cheque
							AND ret_cheque.tipo_documento = 0)
						LEFT JOIN te_transferencias_anuladas transferencia_anulada ON (ret_cheque.id_cheque = transferencia_anulada.id_transferencia_anulada
							AND ret_cheque.tipo_documento = 1)
						LEFT JOIN te_transferencia transferencia ON (ret_cheque.id_cheque = transferencia.id_transferencia
							AND ret_cheque.tipo_documento = 1)
					WHERE ret_cheque.id_retencion_cheque = cxp_pago.id_documento)
			END) AS observacion_documento,
			
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
		FROM cp_pagos_documentos cxp_pago
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxp_pago.id_empleado_creador = vw_pg_empleado.id_empleado)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxp_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		WHERE cxp_pago.id_pago = %s;",
			valTpDato($idPago, "int"));
		$rs = mysql_query($query);
		if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	} else {
		$cbxItm = sprintf("<input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>",
			$contFila);
	}
	
	$classMontoPago = ($row['estatus'] != 1 && $totalRows > 0) ? "class=\"divMsjError\"" : "";
	
	$txtFechaPago = ($txtFechaPago == "" && $totalRows > 0) ? $row['fecha_pago'] : $txtFechaPago;
	$txtHoraPago = ($txtHoraPago == "" && $totalRows > 0) ? $row['tiempo_registro'] : $txtHoraPago;
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['tipo_pago'] : $txtMetodoPago;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento'] : $txtNumeroDctoPago;
	$txtBancoCompaniaPago = ($txtBancoCompaniaPago == "" && $totalRows > 0) ? $row['banco_compania'] : $txtBancoCompaniaPago;
	$txtCuentaCompaniaPago = ($txtCuentaCompaniaPago == "" && $totalRows > 0) ? $row['cuenta_compania'] : $txtCuentaCompaniaPago;
	$txtBancoProveedorPago = ($txtBancoProveedorPago == "" && $totalRows > 0) ? $row['banco_proveedor'] : $txtBancoProveedorPago;
	$txtCuentaProveedorPago = ($txtCuentaProveedorPago == "" && $totalRows > 0) ? $row['cuenta_proveedor'] : $txtCuentaProveedorPago;
	$txtMontoPago = ($txtMontoPago == "" && $totalRows > 0) ? $row['monto_cancelado'] : $txtMontoPago;
	$hddEstatusPago = ($hddEstatusPago == "" && $totalRows > 0) ? $row['estatus'] : 1;
	$descripcionMotivo = (strlen($row['descripcion_motivo']) > 0) ? "<div align=\"left\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_motivo'])."</span></div>" : "";
	$observacionDctoPago = (strlen($row['observacion_documento']) > 0) ? "<div align=\"left\"><span class=\"textoNegritaCursiva_9px\">".preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$row['observacion_documento']))))."</span></div>" : "";
	$estatusPago = ($row['estatus'] != 1 && $totalRows > 0) ? "<div align=\"center\">PAGO ANULADO</div>" : "";
	$empleadoCreadorPago = (strlen($row['nombre_empleado']) > 0) ? "<span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".utf8_encode($row['nombre_empleado'])."</span>" : "";
	$empleadoAnuladoPago = (strlen($row['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por: </span><span class=\"textoNegrita_9px\">".utf8_encode($row['nombre_empleado_anulado'])."</span></div><div align=\"center\">(".date(spanDateFormat." h:i:s a", strtotime($row['fecha_anulado'])).")</div>" : "";
	
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
	
	switch ($txtMetodoPago) {
		case "NC" :
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("NC",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
			$objDcto->tipoDocumento = "NC";
			$objDcto->tipoDocumentoMovimiento = (in_array("NC",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $row['id_modulo_documento_pago'];
			$objDcto->idDocumento = $txtIdNumeroDctoPago;
			$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
			break;
		case "RETENCION" :
			$aVerDcto = "<a href=\"javascript:verVentana(\'../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=".$txtIdNumeroDctoPago."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Comprobante de Retención\"/><a>"; break;
	}
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPiePago').before('".
		"<tr id=\"trItmPago:%s\" align=\"left\" class=\"textoGris_11px %s\" height=\"24\">".
			"<td align=\"center\" title=\"trItmPago:%s\">%s".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtFechaPago%s\" name=\"txtFechaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"<div>%s</div>".
				"<div>%s</div></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtMetodoPago%s\" name=\"txtMetodoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr>".
				"<td nowrap=\"nowrap\">%s</td>".
				"<td>%s</td>".
				"<td width=\"%s\"><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr></table>".
				"%s".
				"%s".
				"%s</td>".
			"<td %s><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtBancoProveedorPago%s\" name=\"txtBancoProveedorPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCuentaProveedorPago%s\" name=\"txtCuentaProveedorPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtMontoPago%s\" name=\"txtMontoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusPago%s\" name=\"hddEstatusPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $cbxItm,
				$contFila,
			$classMontoPago, $contFila, $contFila, utf8_encode(date(spanDateFormat, strtotime($txtFechaPago))),
				utf8_encode(date("h:i:s a", strtotime($txtHoraPago))),
				$empleadoCreadorPago,
			$classMontoPago, $contFila, $contFila, ($txtMetodoPago),
				$estatusPago,
			$classMontoPago, "100%",
				$aVerDcto,
				$imgDctoModulo,
				"100%", $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
					$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				$descripcionMotivo,
				$observacionDctoPago,
				$empleadoAnuladoPago,
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCuentaCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoProveedorPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCuentaProveedorPago),
			$classMontoPago, $contFila, $contFila, utf8_encode(number_format($txtMontoPago, 2, ".", ",")),
				$contFila, $contFila, $idPago,
				$contFila, $contFila, $hddEstatusPago);
	
	return array(true, $htmlItmPie, $contFila);
}
?>