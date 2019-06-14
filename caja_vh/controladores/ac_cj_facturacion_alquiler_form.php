<?php

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

function buscarAnticipoNotaCreditoChequeTransferencia($frmBuscarAnticipoNotaCreditoChequeTransferencia, $frmDcto, $frmDetallePago, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indicePago => $valorPago) {
			if ($frmListaPagos['txtIdFormaPago'.$valorPago] == $frmDetallePago['selTipoPago']
			&& $frmListaPagos['hddEstatusPago'.$valorPago] == 1) {
				$arrayIdDocumento[] = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
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

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
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
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.status
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
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
		
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
	}
	
	if ($rowCliente['id'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",$tdMsjCliente);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
		
	return $objResponse;
}

function asignarEmpleado($idEmpleado) {
	$objResponse = new xajaxResponse();
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	$queryEmpleado = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	$objResponse->script("byId('btnCancelarLista').click();");
	
	return $objResponse;
}

function asignarUnidadFisica($idUnidadFisica){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.placa,
			(CASE uni_fis.id_condicion_unidad
				WHEN 1 THEN	'NUEVO'
				WHEN 2 THEN	'USADO'
				WHEN 3 THEN	'USADO PARTICULAR'
			END) AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			clase.nom_clase,
			clase.id_clase,
			uni_bas.nom_uni_bas,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			uni_fis.kilometraje,
			uni_fis.id_uni_bas,
			alm.nom_almacen,
			vw_iv_modelo.nom_ano,
			vw_iv_modelo.nom_modelo,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE uni_fis.id_unidad_fisica = %s",
		valTpDato($idUnidadFisica,"int"));
	
	$rs = mysql_query($sql);	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);

	$objResponse->assign("txtIdUnidadFisica","value",utf8_encode($row['id_unidad_fisica']));
	$objResponse->assign("txtSerialCarroceriaVehiculo","value",utf8_encode($row['serial_carroceria']));
	$objResponse->assign("txtPlacaVehiculo","value",utf8_encode($row['placa']));
	$objResponse->assign("txtUnidadBasica","value",utf8_encode($row['nom_uni_bas']));
	$objResponse->assign("txtMarcaVehiculo","value",utf8_encode($row['nom_marca']));
	$objResponse->assign("txtModeloVehiculo","value",utf8_encode($row['nom_modelo']));
	$objResponse->assign("txtAnoVehiculo","value",utf8_encode($row['nom_ano']));
	$objResponse->assign("txtColorVehiculo","value",utf8_encode($row['color_externo1']));
	$objResponse->assign("txtClaseVehiculo","value",utf8_encode($row['nom_clase']));
	$objResponse->assign("txtCondicionVehiculo","value",utf8_encode($row['condicion_unidad']));
	$objResponse->assign("txtAlmacenVehiculo","value",utf8_encode($row['nom_almacen']));
	$objResponse->assign("txtKilometrajeVehiculo","value",utf8_encode($row['kilometraje']));
	$objResponse->assign("hddIdUnidadBasica","value",$row['id_uni_bas']);
	$objResponse->assign("hddIdModelo","value",utf8_encode($row['id_modelo']));
	$objResponse->assign("hddIdClase","value",utf8_encode($row['id_clase']));

	$objResponse->script("byId('btnCancelarLista').click();");

	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaPrecio, $frmListaAccesorio, $frmListaAdicional, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtDescuento = ($txtDescuento == "") ? 0 : $txtDescuento;
	$porcDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);	
	
	$idMonedaLocal = $frmDcto['lstMoneda'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	
	foreach($frmListaPrecio["hddTotalPrecio"] as $valor){//LOS INPUT SON ARRAY nombre[]
		$txtSubTotal += $valor;
	}
	
	foreach($frmListaAccesorio["hddTotalAccesorio"] as $valor){
		$txtSubTotal += $valor;
	}
	
	foreach($frmListaAdicional["hddTotalAccesorio"] as $valor){
		$txtSubTotal += $valor;
	}
		
	// BASES IMPONIBLE Y DESCUENTOS POR ITEM
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$txtSubTotalDescuento = 0;
	$arrayIva = array();
	$arrayBaseIva = array();
	$arrayPorcIva = array();
	$arrayDescIva = array();
	
	$sql = "SELECT idIva, observacion FROM pg_iva";
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	while($row = mysql_fetch_assoc($rs)){
		$arrayDescIva[$row["idIva"]] = $row["observacion"];
	}
	
	//ITEM PRECIOS
	foreach($frmListaPrecio["hddIdDetallePrecioContrato"] as $indice => $valor){//SOLO SE USA EL INDICE QUE INDICA CADA ITEM
		$subTotalItm = $frmListaPrecio["hddTotalPrecio"][$indice] - (($frmListaPrecio["hddTotalPrecio"][$indice] * $txtDescuento) / 100);
		//iva por item
		$arrayIdIvaItm = array_filter(explode("|", $frmListaPrecio["hddIdIvaPrecio"][$indice]));
		$arrayPorcIvaItm = array_filter(explode("|", $frmListaPrecio["hddPorcentajeIvaPrecio"][$indice]));
		foreach($arrayIdIvaItm as $indiceIva => $idIvaItm){
			$arrayPorcIva[$idIvaItm] = $arrayPorcIvaItm[$indiceIva];//porc iva por item
			$arrayBaseIva[$idIvaItm] += $subTotalItm;//base imponible por item
			$arrayIva[$idIvaItm] += ($subTotalItm * $arrayPorcIvaItm[$indiceIva]) / 100;//subtotal iva por item
		}
		//exento
		if(count($arrayIdIvaItm) == 0){ $txtTotalExento += $subTotalItm; }
	}
	
	//ITEM ACCESORIOS
	foreach($frmListaAccesorio["hddIdDetalleAccesorioContrato"] as $indice => $valor){
		$subTotalItm = $frmListaAccesorio["hddTotalAccesorio"][$indice] - (($frmListaAccesorio["hddTotalAccesorio"][$indice] * $txtDescuento) / 100);
		
		$arrayIdIvaItm = array_filter(explode("|", $frmListaAccesorio["hddIdIvaAccesorio"][$indice]));
		$arrayPorcIvaItm = array_filter(explode("|", $frmListaAccesorio["hddPorcentajeIvaAccesorio"][$indice]));
		foreach($arrayIdIvaItm as $indiceIva => $idIvaItm){
			$arrayPorcIva[$idIvaItm] = $arrayPorcIvaItm[$indiceIva];
			$arrayBaseIva[$idIvaItm] += $subTotalItm;
			$arrayIva[$idIvaItm] += ($subTotalItm * $arrayPorcIvaItm[$indiceIva]) / 100;
		}
		
		if(count($arrayIdIvaItm) == 0){ $txtTotalExento += $subTotalItm; }
	}
	
	//ITEM ADICIONALES
	foreach($frmListaAdicional["hddIdDetalleAccesorioContrato"] as $indice => $valor){
		$subTotalItm = $frmListaAdicional["hddTotalAccesorio"][$indice] - (($frmListaAdicional["hddTotalAccesorio"][$indice] * $txtDescuento) / 100);
		
		$arrayIdIvaItm = array_filter(explode("|", $frmListaAdicional["hddIdIvaAccesorio"][$indice]));
		$arrayPorcIvaItm = array_filter(explode("|", $frmListaAdicional["hddPorcentajeIvaAccesorio"][$indice]));
		foreach($arrayIdIvaItm as $indiceIva => $idIvaItm){			
			$arrayPorcIva[$idIvaItm] = $arrayPorcIvaItm[$indiceIva];
			$arrayBaseIva[$idIvaItm] += $subTotalItm;
			$arrayIva[$idIvaItm] += ($subTotalItm * $arrayPorcIvaItm[$indiceIva]) / 100;
		}
		
		if(count($arrayIdIvaItm) == 0){ $txtTotalExento += $subTotalItm; }
	}
	
	// CREA LOS ELEMENTOS DE IVA
	$objResponse->script("$('.trIva').remove();");
	foreach ($arrayIva as $indiceIva => $valorIva) {
		//$totalIva = $valorIva + (($valorIva * $arrayPorcIva[$indiceIva]) / 100);
		$objResponse->script(sprintf("
		$('#trNetoContrato').before('".
			"<tr align=\"right\" class=\"textoGris_11px trIva\">".
				"<td class=\"tituloCampo\">%s:".
					"<input type=\"hidden\" name=\"hddIdIva[]\" value=\"%s\"/>".
				"<td nowrap=\"nowrap\"><input type=\"text\" name=\"txtBaseImpIva[]\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"<td nowrap=\"nowrap\"><input type=\"text\" name=\"txtIva[]\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
				"<td>%s</td>".
				"<td><input type=\"text\" name=\"txtSubTotalIva[]\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
			"</tr>');", 
			utf8_encode($arrayDescIva[$indiceIva]), 
			$indiceIva,//id iva
			number_format(round($arrayBaseIva[$indiceIva], 2), 2, ".", ","), //monto base imponible
			$arrayPorcIva[$indiceIva], "%", //porcentaje iva
			$abrevMonedaLocal, 
			number_format(round($valorIva, 2), 2, ".", ",")//subtotal iva
			));
		
		$subTotalIva += round(doubleval($valorIva), 2);
	}
		
	$txtSubTotalDescuento = $txtSubTotal * ($porcDescuento / 100);
	$txtTotalOrden = doubleval($txtSubTotal) - doubleval($txtSubTotalDescuento);
	$txtTotalOrden += doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva);

	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento","value",number_format($txtDescuento, 2, ".", ","));	
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));	
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalFactura","value",number_format($txtTotalOrden, 2, ".", ","));
	$objResponse->assign("txtMontoPorPagar","value",number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("tdSubTotalMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdDescuentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalRegistroMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalFacturaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExoneradoMoneda", "innerHTML", $abrevMonedaLocal);

	return $objResponse;
}

function calcularPagos($frmListaPagos, $frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	if (isset($arrayObj2)) {
		$i = 0;
		foreach ($arrayObj2 as $indicePago => $valorPago) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmPago:".$valorPago,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmPago:".$valorPago,"innerHTML",$i);
			
			$txtMontoPagadoFactura += str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
		}
	}
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	$objResponse->assign("txtMontoPagadoFactura","value",number_format($txtMontoPagadoFactura, 2, ".", ","));
	$objResponse->assign("txtMontoPorPagar","value",number_format(str_replace(",", "", $frmTotalDcto['txtTotalOrden']) - $txtMontoPagadoFactura, 2, ".", ","));
	
	return $objResponse;
}

function calcularPagosDeposito($frmDeposito, $frmDetallePago) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
	$arrayObj3 = $frmDeposito['cbx3'];
	if (isset($arrayObj3)) {
		$i = 0;
		foreach ($arrayObj3 as $indice3 => $valor3) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDetalle:".$valor3,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDetalle:".$valor3,"innerHTML",$i);
			
			$txtMontoPagadoDeposito += str_replace(",", "", $frmDeposito['txtMontoDetalleDeposito'.$valor3]);
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
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstCuentaCompania($row['idBanco'], $tipoPago)); }
		
		$html .= "<option ".$selected."  value=\"".$row['idBanco']."\">".utf8_encode($row['banco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselBancoCompania","innerHTML",$html);
	
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
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstTarjetaCuenta($row['idCuentas'], $tipoPago)); }
		
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".utf8_encode($row['numeroCuentaCompania'])."</option>";
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
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:150px\">";
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
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row['idTipoTarjetaCredito'])); }
			
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

function cargaLstTipoPago($idFormaPago = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$idFormaPago = (is_array($idFormaPago)) ? implode(",",$idFormaPago) : $idFormaPago;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia Bancaria, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito
	// 9 = Retención, 10 = Retencion I.S.L.R., 11 = Otro
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idFormaPago NOT IN (11)");
	
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
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPago($row['idFormaPago'])); }
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPago']."\">".$row['nombreFormaPago']."</option>";
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
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPagoDetalleDeposito($row['idFormaPago'])); }
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPago']."\">".$row['nombreFormaPago']."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarSaldoDocumento($formaPago, $idDocumento, $frmListaPagos) {
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

function eliminarPago($frmListaPagos, $pos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$idDocumento = $frmListaPagos['txtIdNumeroDctoPago'.$pos];
	
	if ($frmListaPagos['txtIdFormaPago'.$pos] == 3) { // 3 = Deposito
		$objResponse->script("xajax_eliminarDetalleDeposito(".$pos.",xajax.getFormValues('frmDetallePago'))");
	} else if ($frmListaPagos['txtIdFormaPago'.$pos] == 7) { // 7 = Anticipo
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_anticipo = %s;",
			valTpDato($idDocumento, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if (isset($arrayObj2)) {
				foreach ($arrayObj2 as $indicePago => $valorPago) {
					if ($frmListaPagos['txtIdFormaPago'.$valorPago] == 8 && $frmListaPagos['txtIdNumeroDctoPago'.$valorPago] == $rowTradeInNotaCredito['id_nota_credito_cxc']) {
						$objResponse->script("xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),'".$valorPago."');");
					}
				}
			}
		}
	} else if ($frmListaPagos['txtIdFormaPago'.$pos] == 8) { // 8 = Nota de Crédito
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_nota_credito_cxc = %s;",
			valTpDato($idDocumento, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if (isset($arrayObj2)) {
				foreach ($arrayObj2 as $indicePago => $valorPago) {
					if ($frmListaPagos['txtIdFormaPago'.$valorPago] == 7 && $frmListaPagos['txtIdNumeroDctoPago'.$valorPago] == $rowTradeInNotaCredito['id_anticipo']) {
						$objResponse->script("xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),'".$valorPago."');");
					}
				}
			}
		}
	}
	
	$objResponse->script("
	fila = document.getElementById('trItmPago:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'))");
	
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
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
	$arrayObj3 = $frmDeposito['cbx3'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj3)) {
		foreach ($arrayObj3 as $indice => $valor) {
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

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda']) {
			$selected = "selected=\"selected\"";
		} else if ($row['predeterminada'] == 1 && $selId == "") {
			$selected = "selected=\"selected\"";
		}
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".utf8_encode($row['descripcion']." (".$row['abreviacion'].")")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargarDcto($idDocumento) {
	$objResponse = new xajaxResponse();
	
	if ($idDocumento > 0) {
		$objResponse->script("
		byId('txtObservacion').className = 'inputHabilitado';");
		
		$sql = sprintf("SELECT
				contrato.*,
				tipo_contrato.id_clave_movimiento,
				clave_mov.clave,
				clave_mov.descripcion AS descripcion_clave_movimiento,
				presupuesto.numero_presupuesto_venta,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM al_contrato_venta contrato
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			INNER JOIN pg_clave_movimiento clave_mov ON (tipo_contrato.id_clave_movimiento = clave_mov.id_clave_movimiento)
			LEFT JOIN al_presupuesto_venta presupuesto ON (contrato.id_presupuesto_venta = presupuesto.id_presupuesto_venta)
			WHERE contrato.id_contrato_venta = %s
			AND contrato.estatus_contrato_venta = 2;",
			valTpDato($idDocumento, "int"));
		$rs = mysql_query($sql);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if(mysql_num_rows($rs) == 0){
			$objResponse->alert("El Contrato no puede ser cargado debido a que su estado no es válido");
			return $objResponse->script("byId('btnCancelar').click();");
		}
		
		$Result1 = validarAperturaCaja($row['id_empresa'], date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
		
		// CARGA EL PRECIO DEL CONTRATO
		$sqlPrecioDet = sprintf("SELECT 
				al_contrato_venta_precio.*,
				al_precios.nombre_precio,
				al_precios_detalle.descripcion
			FROM al_contrato_venta_precio 
			INNER JOIN al_precios_detalle ON al_contrato_venta_precio.id_precio_detalle = al_precios_detalle.id_precio_detalle
			INNER JOIN al_precios ON al_precios_detalle.id_precio = al_precios.id_precio
			WHERE id_contrato_venta = %s",
			valTpDato($idDocumento, "int"));
		$rsPrecioDet = mysql_query($sqlPrecioDet);
		if (!$rsPrecioDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($rowPrecioDet = mysql_fetch_assoc($rsPrecioDet)) {
			$arrayIva = array();
			$sqlIva = sprintf("SELECT id_impuesto, impuesto FROM al_contrato_venta_precio_impuesto WHERE id_contrato_venta_precio = %s",
				valTpDato($rowPrecioDet["id_contrato_venta_precio"], "int"));
			$rsIva = mysql_query($sqlIva);			
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			while($rowIva = mysql_fetch_assoc($rsIva)){
				$arrayIva[$rowIva["id_impuesto"]] = array('idIva' => $rowIva["id_impuesto"], 'iva' => $rowIva["impuesto"]);
			}
			
			$objResponse->loadCommands(insertarPrecio($rowPrecioDet["id_contrato_venta_precio"], $rowPrecioDet["id_precio"], $rowPrecioDet["nombre_precio"], $rowPrecioDet["id_precio_detalle"], $rowPrecioDet["descripcion"], $rowPrecioDet["precio"], $rowPrecioDet["dias"], $rowPrecioDet["id_tipo_precio"], $rowPrecioDet['dias_calculado'], $rowPrecioDet['total_precio'], $arrayIva));
		}
		
		// CARGA LOS ACCESORIOS Y ADICIONALES
		$sqlAccesorioDet = sprintf("SELECT 
				al_contrato_venta_accesorio.*,
				nom_accesorio,
				des_accesorio 
			FROM al_contrato_venta_accesorio 
			INNER JOIN an_accesorio ON al_contrato_venta_accesorio.id_accesorio = an_accesorio.id_accesorio
			WHERE id_contrato_venta = %s",
			valTpDato($idDocumento, "int"));
		$rsAccesorioDet = mysql_query($sqlAccesorioDet);
		if (!$rsAccesorioDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($rowAccesorioDet = mysql_fetch_assoc($rsAccesorioDet)) {
			$arrayIva = array();
			$sqlIva = sprintf("SELECT id_impuesto, impuesto FROM al_contrato_venta_accesorio_impuesto WHERE id_contrato_venta_accesorio = %s",
				valTpDato($rowAccesorioDet["id_contrato_venta_accesorio"], "int"));
			$rsIva = mysql_query($sqlIva);			
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			while($rowIva = mysql_fetch_assoc($rsIva)){
				$arrayIva[$rowIva["id_impuesto"]] = array('idIva' => $rowIva["id_impuesto"], 'iva' => $rowIva["impuesto"]);
			}
			
			$objResponse->loadCommands(insertarAccesorio($rowAccesorioDet["id_contrato_venta_accesorio"], $rowAccesorioDet["id_accesorio"], $rowAccesorioDet["id_tipo_accesorio"], $rowAccesorioDet["nom_accesorio"], $rowAccesorioDet["des_accesorio"], $rowAccesorioDet["cantidad"], $rowAccesorioDet["precio"], $rowAccesorioDet["costo"], $arrayIva));
		}
		
		$Result1 = buscarNumeroControl($row['id_empresa'], $row['id_clave_movimiento']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->assign("txtNumeroControlFactura","value",($Result1[1]));
		}
					
		// DATOS DEL CONTRATO
		$objResponse->assign("txtIdEmpresa","value",$row['id_empresa']);
		$objResponse->assign("txtEmpresa","value",utf8_encode($row['nombre_empresa']));
		$objResponse->assign("hddIdContrato","value",$row['id_contrato_venta']);
		$objResponse->assign("txtNumeroContrato","value",$row['numero_contrato_venta']);
		$objResponse->assign("txtFechaContrato","value",fecha($row['fecha_creacion']));
		$objResponse->assign("txtFechaFactura","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstMoneda($row['id_moneda']));
		$objResponse->loadCommands(cargarLstTipoContrato($row['id_tipo_contrato'], $row['id_empresa']));
		
		$objResponse->assign("txtDescuento","value",$row['porcentaje_descuento']);
		$objResponse->assign("txtObservacion","value",utf8_encode($row['observacion']));
		$objResponse->loadCommands(asignarEmpleado($row['id_empleado_creador']));
		$objResponse->loadCommands(asignarUnidadFisica($row['id_unidad_fisica']));
		$objResponse->assign("hddIdClaveMovimiento","value",$row['id_clave_movimiento']);
		$objResponse->assign("txtClaveMovimiento","value",utf8_encode($row['clave'].") ".$row['descripcion_clave_movimiento']));
		$objResponse->call("selectedOption","lstTipoClave",3);
		$objResponse->script("byId('lstTipoClave').onchange = function(){ selectedOption(this.id,'".(3)."'); };");
		
		$objResponse->assign("txtFechaSalida","value",fecha($row['fecha_salida']));
		$objResponse->assign("txtHoraSalida","value",tiempo($row['fecha_salida']));
		$objResponse->assign("txtFechaEntrada","value",fecha($row['fecha_entrada']));
		$objResponse->assign("txtHoraEntrada","value",tiempo($row['fecha_entrada']));
		$objResponse->assign("txtFechaEntradaFinal","value",fecha($row['fecha_final']));
		$objResponse->assign("txtHoraEntradaFinal","value",tiempo($row['fecha_final']));
		
		$objResponse->assign("txtKilometrajeSalida","value",$row['kilometraje_salida']);
		$objResponse->assign("lstCombustibleSalida","value",$row['nivel_combustible_salida']);
		$objResponse->assign("txtKilometrajeEntrada","value",$row['kilometraje_entrada']);
		$objResponse->assign("lstCombustibleEntrada","value",$row['nivel_combustible_entrada']);
		
		$objResponse->assign("txtDiasContrato","value",$row['dias_contrato']);
		$objResponse->assign("txtDiasSobreTiempo","value",$row['dias_sobre_tiempo']);
		$objResponse->assign("txtDiasBajoTiempo","value",$row['dias_bajo_tiempo']);
		$objResponse->assign("txtDiasTotal","value",$row['dias_total']);
		
		$idCliente = $row['id_cliente_pago'];
		$condicionPago = $row['condicion_pago'];
		$idEmpresa = $row['id_empresa'];

		// DATOS DEL CLIENTE
		$objResponse->loadCommands(asignarCliente($idCliente, $row['id_empresa'], "", $row['condicion_pago'], "false", "false", "false"));
		// CARGA LOS DATOS DEL CLIENTE
		$queryCliente = sprintf("SELECT
			cliente.id,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cliente.direccion,
			cliente.telf,
			cliente.otrotelf,
			cliente.descuento,
			cliente.credito,
			cliente.id_clave_movimiento_predeterminado,
			cliente.paga_impuesto
		FROM cj_cc_cliente cliente
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCliente = mysql_fetch_assoc($rsCliente);
	
		if ($condicionPago == 0) { // 0 = Credito, 1 = Contado
			$objResponse->assign("hddTipoPago","value",$condicionPago);
			$objResponse->assign("txtTipoPago","value","CREDITO");
		
			if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
				$queryClienteCredito = sprintf("SELECT cliente_cred.*
				FROM cj_cc_credito cliente_cred
					INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente_cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
				WHERE cliente_emp.id_cliente = %s
					AND cliente_emp.id_empresa = %s;",
					valTpDato($idCliente, "int"),
					valTpDato($idEmpresa, "int"));
				$rsClienteCredito = mysql_query($queryClienteCredito);
				if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsClienteCredito = mysql_num_rows($rsClienteCredito);
				$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
			
				$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
			
				$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoCliente);
			
				$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
			} else {
				$fechaVencimiento = date(spanDateFormat);
			
				$objResponse->assign("txtDiasCreditoCliente","value","0");
			}
		
			$objResponse->script("
			byId('trFormaDePago').style.display = 'none';");
		
		} else if ($condicionPago == 1) { // 0 = Credito, 1 = Contado
			$objResponse->assign("hddTipoPago","value",$condicionPago);
			$objResponse->assign("txtTipoPago","value","CONTADO");
		
			$fechaVencimiento = date(spanDateFormat);
		
			$objResponse->assign("txtDiasCreditoCliente","value","0");
		
			$objResponse->script("
			byId('trFormaDePago').style.display = '';");
		}

		$objResponse->assign("txtFechaVencimientoFactura","value",fecha($fechaVencimiento));

		// DATOS DEL PRESUPUESTO
		$objResponse->assign("hddIdPresupuestoVenta","value", $row['id_presupuesto_venta']);
		$objResponse->assign("txtNumeroPresupuestoVenta","value", $row['numero_presupuesto_venta']);			
		
		$objResponse->script("
		byId('lstMoneda').className = 'inputInicial';
		byId('lstMoneda').onchange = function () {
			selectedOption(this.id,".$row["id_moneda"].");
		}");
		
		$objResponse->script("
		byId('lstCombustibleSalida').onchange = function () {
			selectedOption(this.id,".$row["nivel_combustible_salida"].");
		}");
		
		$objResponse->script("
		byId('lstCombustibleEntrada').onchange = function () {
			selectedOption(this.id,'".$row["nivel_combustible_entrada"]."');
		}");
		
		$objResponse->script("calcularDcto();");
		
	}
	
	return $objResponse;
}

function cargarLstTipoContrato($idTipoContrato = "", $idEmpresa){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT
						id_tipo_contrato, 
						nombre_tipo_contrato
					FROM al_tipo_contrato 					
					WHERE id_tipo_contrato = %s",
		valTpDato($idTipoContrato,"int"));
	
	$rs = mysql_query($sql);	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$tienePermisoTipoContrato = mysql_num_rows($rs);
	
	$html = "<select id=\"lstTipoContrato\" name=\"lstTipoContrato\" >";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if($idTipoContrato == $row['id_tipo_contrato']){
			$selected = "selected=\"selected\"";
		}		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_contrato']."\">".utf8_encode($row['nombre_tipo_contrato'])."</option>";
	}
	$html .= "</select>";
		
	
	$objResponse->assign("tdlstTipoContrato","innerHTML",$html);
	
	return $objResponse;
}



function guardarDcto($frmDcto, $frmListaPrecio, $frmListaAccesorio, $frmListaAdicional, $frmTotalDcto, $frmDetallePago, $frmListaPagos){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_factura_venta_list","insertar")) { return $objResponse; }
	
	$idDocumentoVenta = $frmDcto['hddIdContrato'];
	
	$sql = sprintf("SELECT 
						contrato.* 
					FROM al_contrato_venta contrato 
					WHERE contrato.id_contrato_venta = %s;",
		valTpDato($idDocumentoVenta, "int"));
	$rs = mysql_query($sql);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	if($row["estatus_contrato_venta"] != 2){
		$objResponse->alert("El Contrato no puede ser actualizado debido a que su estado no es válido");
		return $objResponse;
	}
	
	$idEmpresa = $row['id_empresa'];
	$idModulo = 4; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion, 4 = Alquiler
	$idCliente = $frmDcto['txtIdCliente'];
	$idUnidadFisica = $row['id_unidad_fisica'];
	$idEmpleadoAsesor = $frmDcto['txtIdEmpleado'];
	$idClaveMovimiento = $frmDcto['hddIdClaveMovimiento'];
	$idTipoPago = $frmDcto['hddTipoPago'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// VERIFICA SI EL DOCUMENTO YA HA SIDO FACTURADO
	$queryVerif = sprintf("SELECT * FROM cj_cc_encabezadofactura
	WHERE numeroPedido = %s
		AND idDepartamentoOrigenFactura IN (%s)",
		valTpDato($idDocumentoVenta, "int"),
		valTpDato($idModulo, "int"));
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if (mysql_num_rows($rsVerif) > 0) {
		return $objResponse->alert('Este documento ya ha sido facturado');
	}
	
	// VERIFICA QUE EL DOOCUMENTO A CONTADO ESTE CANCELADO EN TU TOTALIDAD
	if ($idTipoPago == 1) { // 0 = Credito, 1 = Contado
		if ($frmListaPagos['txtMontoPorPagar'] != 0) {
			return $objResponse->alert('Debe cancelar el monto total de la factura');
		}
	}
	
	mysql_query("START TRANSACTION;");
				
	$updateSQL = sprintf("UPDATE al_contrato_venta SET estatus_contrato_venta = 3 WHERE id_contrato_venta = %s;",
		valTpDato($idDocumentoVenta, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }	
	
	$baseImponibleIva = 0;
	$porcIva = 0;
	$subTotalIva = 0;
	$baseImponibleIvaLujo = 0;
	$porcIvaLujo = 0;
	$subTotalIvaLujo = 0;

	//iva simple
	foreach ($frmTotalDcto['hddIdIva'] as $indice => $idIva) {
		$baseImponibleIva = $frmTotalDcto['txtBaseImpIva'][$indice];
		$porcIva += $frmTotalDcto['txtIva'][$indice];
		$subTotalIva += $frmTotalDcto['txtSubTotalIva'][$indice];
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
	
	if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado la numeracion de facturas"); }
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$numeroActualFactura = $numeroActual;
	
	// INSERTA LOS DATOS DE LA FACTURA
	$insertSQL = sprintf("INSERT INTO cj_cc_encabezadofactura (id_empresa, idCliente, numeroFactura, numeroControl, fechaRegistroFactura, fechaVencimientoFactura, idDepartamentoOrigenFactura, idVendedor, id_clave_movimiento, numeroPedido, condicionDePago, diasDeCredito, estadoFactura, montoTotalFactura, saldoFactura, observacionFactura, subtotalFactura, porcentaje_descuento, descuentoFactura, baseImponible, porcentajeIvaFactura, calculoIvaFactura, base_imponible_iva_lujo, porcentajeIvaDeLujoFactura, calculoIvaDeLujoFactura, montoExento, montoExonerado, estatus_factura, anulada, aplicaLibros, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idCliente, "int"),
		valTpDato($numeroActualFactura, "text"),
		valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaVencimientoFactura'])), "date"),
		valTpDato($idModulo, "int"),
		valTpDato($idEmpleadoAsesor, "int"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idDocumentoVenta, "int"),
		valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($frmDcto['txtDiasCreditoCliente'], "int"),
		valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada , 2 = Parcialmente Cancelada
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($baseImponibleIva, "real_inglesa"),
		valTpDato($porcIva, "real_inglesa"),
		valTpDato($subTotalIva, "real_inglesa"),
		valTpDato($baseImponibleIvaLujo, "real_inglesa"),
		valTpDato($porcIvaLujo, "real_inglesa"),
		valTpDato($subTotalIvaLujo, "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
		valTpDato(((in_array(idArrayPais,array(3))) ? 1 : 2), "int"), // Null o 1 = Aprobada, 2 = Aplicada / Cerrada
		valTpDato("NO", "text"),
		valTpDato(1, "int"), // 0 = No, 1 = Si
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
	
	// INSERTA EL PRECIO / TARIFA
	foreach($frmListaPrecio['hddIdDetallePrecioContrato'] as $indice => $idDetallePrecioContrato){
		$insertSQL = sprintf("INSERT INTO al_factura_detalle_precio (id_factura, id_precio, id_precio_detalle, id_tipo_precio, dias, precio, dias_calculado, total_precio)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idFactura, "int"),
			valTpDato($frmListaPrecio['hddIdPrecio'][$indice], "int"),
			valTpDato($frmListaPrecio['hddIdPrecioDetalle'][$indice], "int"),
			valTpDato($frmListaPrecio['hddIdTipoPrecio'][$indice], "int"),
			valTpDato($frmListaPrecio['hddDiasPrecio'][$indice], "int"),
			valTpDato($frmListaPrecio['hddPrecio'][$indice], "real_inglesa"),
			valTpDato($frmListaPrecio['hddDiasPrecioCalculado'][$indice], "int"),
			valTpDato($frmListaPrecio['hddTotalPrecio'][$indice], "real_inglesa"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDetalleFacturaPrecio = mysql_insert_id();
		
		$arrayIdPrecioImpuesto = array_filter(explode("|", $frmListaPrecio['hddIdIvaPrecio'][$indice]));
		$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaPrecio['hddPorcentajeIvaPrecio'][$indice]));
		
		foreach($arrayIdPrecioImpuesto as $indiceImpuesto => $idImpuesto){
			$insertSQL = sprintf("INSERT INTO al_factura_detalle_precio_impuesto (id_factura_detalle_precio, id_impuesto, impuesto)
			VALUE (%s, %s, %s);",
				valTpDato($idDetalleFacturaPrecio, "int"),
				valTpDato($arrayIdPrecioImpuesto[$indiceImpuesto], "int"),
				valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}		
	}
	
	//INSERTA ACCESORIOS
	foreach($frmListaAccesorio['hddIdDetalleAccesorioContrato'] as $indice => $idDetalleAccesorioContrato){
		$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_accesorios (id_factura, id_accesorio, id_tipo_accesorio, cantidad, precio_unitario, costo_compra, tipo_accesorio)
		VALUE (%s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idFactura, "int"),
			valTpDato($frmListaAccesorio['hddIdAccesorio'][$indice], "int"),
			valTpDato($frmListaAccesorio['hddIdTipoAccesorio'][$indice], "int"),
			valTpDato($frmListaAccesorio['hddCantidadAccesorio'][$indice], "real_inglesa"),
			valTpDato($frmListaAccesorio['hddPrecioAccesorio'][$indice], "real_inglesa"),
			valTpDato($frmListaAccesorio['hddCostoAccesorio'][$indice], "real_inglesa"),
			valTpDato(2, "int"));//1 = Paquete, 2 = Accesorio
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDetalleFacturaAccesorio = mysql_insert_id();
		
		$arrayIdAccesorioImpuesto = array_filter(explode("|", $frmListaAccesorio['hddIdIvaAccesorio'][$indice]));
		$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaAccesorio['hddPorcentajeIvaAccesorio'][$indice]));
		
		foreach($arrayIdAccesorioImpuesto as $indiceImpuesto => $idImpuesto){
			$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_accesorios_impuesto (id_factura_detalle_accesorios, id_impuesto, impuesto)
			VALUE (%s, %s, %s);",
				valTpDato($idDetalleFacturaAccesorio, "int"),
				valTpDato($arrayIdAccesorioImpuesto[$indiceImpuesto], "int"),
				valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	//INSERTA ADICIONALES
	foreach($frmListaAdicional['hddIdDetalleAccesorioContrato'] as $indice => $idDetalleAccesorioContrato){
		$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_accesorios (id_factura, id_accesorio, id_tipo_accesorio, cantidad, precio_unitario, costo_compra, tipo_accesorio)
		VALUE (%s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idFactura, "int"),
			valTpDato($frmListaAdicional['hddIdAccesorio'][$indice], "int"),
			valTpDato($frmListaAdicional['hddIdTipoAccesorio'][$indice], "int"),
			valTpDato($frmListaAdicional['hddCantidadAccesorio'][$indice], "real_inglesa"),
			valTpDato($frmListaAdicional['hddPrecioAccesorio'][$indice], "real_inglesa"),
			valTpDato($frmListaAdicional['hddCostoAccesorio'][$indice], "real_inglesa"),
			valTpDato(2, "int"));//1 = Paquete, 2 = Accesorio
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDetalleFacturaAccesorio = mysql_insert_id();
		
		$arrayIdAccesorioImpuesto = array_filter(explode("|", $frmListaAdicional['hddIdIvaAccesorio'][$indice]));
		$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaAdicional['hddPorcentajeIvaAccesorio'][$indice]));
		
		foreach($arrayIdAccesorioImpuesto as $indiceImpuesto => $idImpuesto){
			$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_accesorios_impuesto (id_factura_detalle_accesorios, id_impuesto, impuesto)
			VALUE (%s, %s, %s);",
				valTpDato($idDetalleFacturaAccesorio, "int"),
				valTpDato($arrayIdAccesorioImpuesto[$indiceImpuesto], "int"),
				valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
		
	// INSERTA LOS IMPUESTOS DE LA FACTURA
	foreach ($frmTotalDcto['hddIdIva'] as $indice => $idIva) {
		$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($idFactura, "int"),
			valTpDato($frmTotalDcto['txtBaseImpIva'][$indice], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalIva'][$indice], "real_inglesa"),
			valTpDato($idIva, "int"),
			valTpDato($frmTotalDcto['txtIva'][$indice], "real_inglesa"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }		
		
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("FA", "text"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	// INSERTA EL PAGO DEL DOCUMENTO (PAGO DE FACTURAS) SOLO SI ES DE CONTADO
	if ($idTipoPago == 1 || count($arrayObj2) > 0) { // 0 = Credito, 1 = Contado
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
		foreach($arrayObj2 as $indice2 => $valor2) {
			$txtIdFormaPago = $frmListaPagos['txtIdFormaPago'.$valor2];
			$hddIdPago = $frmListaPagos['hddIdPago'.$valor2];
			
			if (!($hddIdPago > 0)) {
				if (isset($txtIdFormaPago)) {
					$arrayDetallePago = array(
						"idCajaPpal" => $idCajaPpal,
						"apertCajaPpal" => $apertCajaPpal,
						"idApertura" => $idApertura,
						"numeroActualFactura" => $numeroActualFactura,
						"fechaRegistroPago" => $fechaRegistroPago,
							//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
							//"idEncabezadoPago" => $idEncabezadoPago,
						"cbxPosicionPago" => $valor2,
						"hddIdPago" => $hddIdPago,
						"txtIdFormaPago" => $txtIdFormaPago,
						"txtIdNumeroDctoPago" => $frmListaPagos['txtIdNumeroDctoPago'.$valor2],
						"txtNumeroDctoPago" => $frmListaPagos['txtNumeroDctoPago'.$valor2],
						"txtIdBancoCliente" => $frmListaPagos['txtIdBancoCliente'.$valor2],
						"txtCuentaClientePago" => $frmListaPagos['txtCuentaClientePago'.$valor2],
						"txtIdBancoCompania" => $frmListaPagos['txtIdBancoCompania'.$valor2],
						"txtIdCuentaCompaniaPago" => $frmListaPagos['txtIdCuentaCompaniaPago'.$valor2],
						"txtCuentaCompaniaPago" => asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valor2]),
						"txtFechaDeposito" => $frmListaPagos['txtFechaDeposito'.$valor2],
						"txtTipoTarjeta" => $frmListaPagos['txtTipoTarjeta'.$valor2],
						"hddObjDetalleDeposito" => $frmDetallePago['hddObjDetalleDeposito'],
						"hddObjDetalleDepositoFormaPago" => $frmDetallePago['hddObjDetalleDepositoFormaPago'],
						"hddObjDetalleDepositoBanco" => $frmDetallePago['hddObjDetalleDepositoBanco'],
						"hddObjDetalleDepositoNroCuenta" => $frmDetallePago['hddObjDetalleDepositoNroCuenta'],
						"hddObjDetalleDepositoNroCheque" => $frmDetallePago['hddObjDetalleDepositoNroCheque'],
						"hddObjDetalleDepositoMonto" => $frmDetallePago['hddObjDetalleDepositoMonto'],
						"txtMonto" => $frmListaPagos['txtMonto'.$valor2],
						"cbxCondicionMostrar" => $frmListaPagos['cbxCondicionMostrar'.$valor2],
						"lstSumarA" => $frmListaPagos['lstSumarA'.$valor2]
					);
					
					$arrayObjPago[] = $arrayDetallePago;
				}
			}
		}
		
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
	}
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//

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
														SUM(IFNULL(an_ped_vent.subtotal, 0)
															- IFNULL(an_ped_vent.subtotal_descuento, 0)
															+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																	WHERE ped_vent_gasto.id_pedido_venta = an_ped_vent.id_pedido_venta), 0)
															+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																	WHERE ped_vent_iva.id_pedido_venta = an_ped_vent.id_pedido_venta), 0))
													FROM iv_pedido_venta an_ped_vent
													WHERE an_ped_vent.id_cliente = cliente_emp.id_cliente
														AND an_ped_vent.id_empresa = cliente_emp.id_empresa
														AND an_ped_vent.estatus_pedido_venta IN (2)), 0)),
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
										SUM(IFNULL(an_ped_vent.subtotal, 0)
											- IFNULL(an_ped_vent.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
													WHERE ped_vent_gasto.id_pedido_venta = an_ped_vent.id_pedido_venta), 0)
											+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
													WHERE ped_vent_iva.id_pedido_venta = an_ped_vent.id_pedido_venta), 0))
									FROM iv_pedido_venta an_ped_vent
									WHERE an_ped_vent.id_cliente = cliente_emp.id_cliente
										AND an_ped_vent.id_empresa = cliente_emp.id_empresa
										AND an_ped_vent.estatus_pedido_venta IN (2)
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
	
	mysql_query("COMMIT;");
	
	//CONTABILIZA DOCUMENTO
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "VENTA") {
				$idFactura = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarVentasRe")) { generarVentasRe($idFactura,"",""); } break;
					case 1 : if (function_exists("generarVentasSe")) { generarVentasSe($idFactura,"",""); } break;
					case 2 : if (function_exists("generarVentasVe")) { generarVentasVe($idFactura,"",""); } break;
					case 3 : if (function_exists("generarVentasAd")) { generarVentasAd($idFactura,"",""); } break;
					case 4 : if (function_exists("generarVentasAl")) { generarVentasAl($idFactura,"",""); } break;
					case 5 : if (function_exists("generarVentasFi")) { generarVentasFi($idFactura,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert("Factura Guardada con Exito");
	$objResponse->script(sprintf("window.location.href='cj_factura_venta_list.php';"));	
	$objResponse->script("verVentana('../alquiler/reportes/al_factura_venta_pdf.php?valBusq=".$idFactura."', 960, 550);");
	
	if ($idEncabezadoReciboPago > 0) {
		$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s',960,550)", $idEncabezadoReciboPago));
	}
	
	return $objResponse;
}

function insertarAccesorio($idDetalleAccesorioContrato, $idAccesorio, $idTipoAccesorio, $nombreAccesorio, $descripcionAccesorio, $cantidadAccesorio, $precioAccesorio, $costoAccesorio, $arrayIva){
	$objResponse = new xajaxResponse();

	if($idTipoAccesorio == 1){// 1 = Adicional, 2 = Accesorio, 3 = Contrato
		$itmPie = "#trItmPieAdicional";
		$checkboxClase = "checkboxAdicional";
	}elseif($idTipoAccesorio == 2){
		$itmPie = "#trItmPieAccesorio";
		$checkboxClase = "checkboxAccesorio";
	}
	
	foreach($arrayIva as $idIva => $arrayIvaCargado){
		$arrayIdIva[] = $arrayIvaCargado["idIva"];
		$arrayPorcentajesIva[] = $arrayIvaCargado["iva"];
	}

	$htmlItmPie = sprintf("$('%s').before('".
		"<tr>".
			"<td><input type=\"checkbox\" value=\"%s\" class=\"%s\" />".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" name=\"hddIdDetalleAccesorioContrato[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdTipoAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddCantidadAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddPrecioAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddCostoAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddTotalAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdIvaAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddPorcentajeIvaAccesorio[]\" value=\"%s\" />".
			"</td>".
		"</tr>');",
		$itmPie,
		$idDetalleAccesorioContrato, $checkboxClase,
		//td
		utf8_encode($nombreAccesorio),
		utf8_encode($descripcionAccesorio),
		number_format($cantidadAccesorio, 2, ".", ","),
		number_format($precioAccesorio, 2, ".", ","),
		implode(" <br> ", $arrayPorcentajesIva),
		number_format($cantidadAccesorio * $precioAccesorio, 2, ".", ","),
		//hidden
		$idDetalleAccesorioContrato,
		$idAccesorio,
		$idTipoAccesorio,
		$cantidadAccesorio,
		$precioAccesorio,
		$costoAccesorio,
		round($cantidadAccesorio * $precioAccesorio,2),
		implode("|", $arrayIdIva),
		implode("|", $arrayPorcentajesIva));


	$objResponse->script($htmlItmPie);
	
	return $objResponse;
}

function insertarPago($frmListaPagos, $frmDetallePago, $frmDeposito, $frmLista, $frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	$contFila = $arrayObj2[count($arrayObj2)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
	$arrayObj3 = $frmDeposito['cbx3'];
	
	if (str_replace(",", "", $frmTotalDcto['txtTotalOrden']) < str_replace(",", "", $frmDetallePago['txtMontoPago'])) {
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
	}
	
    foreach ($arrayObj2 as $indicePago => $valorPago) {
		$hddIdPago = $frmListaPagos['hddIdPago'.$valorPago];
		$txtIdFormaPago = $frmListaPagos['txtIdFormaPago'.$valorPago];
		$txtIdNumeroDctoPago = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
		
        if (!($hddIdPago > 0)
		&& $txtIdFormaPago == $frmDetallePago['selTipoPago']
		&& $txtIdNumeroDctoPago > 0 && $txtIdNumeroDctoPago == $frmDetallePago['hddIdAnticipoNotaCreditoChequeTransferencia']) {
			return $objResponse->alert("El documento seleccionado ya se encuentra agregado");
        }
    }
	
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
	$txtMontoPago = str_replace(",", "", $frmDetallePago['txtMontoPago']);
	
	$Result1 = insertarItemMetodoPago($contFila, $idFormaPago, $txtIdNumeroDctoPago, $txtNumeroDctoPago, $txtIdBancoCliente, $txtCuentaClientePago, $txtIdBancoCompania, $txtIdCuentaCompaniaPago, $txtFechaDeposito, $lstTipoTarjeta, $porcRetencion, $montoRetencion, $porcComision, $montoComision, $txtMontoPago);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj2[] = $contFila;
	}
	
	if ($idFormaPago == 3) { // 3 = Deposito
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = explode("|", $frmDeposito['hddObjDetallePagoDeposito']);
		
		$cadenaFormaPagoDeposito = "";
		$cadenaNroDocumentoDeposito = "";
		$cadenaBancoClienteDeposito = "";
		$cadenaNroCuentaDeposito = "";
		$cadenaMontoDeposito = "";
		foreach ($arrayObj as $indice => $valor) {
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
	} else if ($idFormaPago == 7) { // 7 = Anticipo
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT cxc_nc.*
		FROM an_tradein_cxc tradein_cxc
			INNER JOIN cj_cc_notacredito cxc_nc ON (tradein_cxc.id_nota_credito_cxc = cxc_nc.idNotaCredito)
		WHERE tradein_cxc.id_anticipo = %s;",
			valTpDato($txtIdNumeroDctoPago, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if ($rowTradeInNotaCredito['saldoNotaCredito'] > 0) {
				$Result1 = insertarItemMetodoPago($contFila, 8, $rowTradeInNotaCredito['idNotaCredito'], $rowTradeInNotaCredito['numeracion_nota_credito'], "", "", "", "", "", "", "", "", "", "", $rowTradeInNotaCredito['saldoNotaCredito']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj2[] = $contFila;
				}
			}
		}
	} else if ($idFormaPago == 8) { // 8 = Nota de Crédito
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_nota_credito_cxc = %s;",
			valTpDato($txtIdNumeroDctoPago, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		$rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito);
		
		if ($totalRowsTradeInNotaCredito > 0) {
			$idFormaPago = 7; // // 7 = Anticipo
		}
	}
	
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'))");
	
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
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
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

function listaAnticipoNotaCreditoChequeTransferencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
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
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.id_departamento AS id_modulo,
			dcto.id_cheque AS idDocumento,
			dcto.saldo_cheque AS saldoDocumento,
			dcto.numero_cheque AS numeroDocumento,
			dcto.fecha_cheque AS fechaDocumento,
			dcto.observacion_cheque AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_cheque dcto 
			INNER JOIN cj_cc_cliente cliente ON (dcto.id_cliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$query = sprintf("SELECT
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.id_departamento AS id_modulo,
			dcto.id_transferencia AS idDocumento,
			dcto.saldo_transferencia AS saldoDocumento,
			dcto.numero_transferencia AS numeroDocumento,
			dcto.fecha_transferencia AS fechaDocumento,
			dcto.observacion_transferencia AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_transferencia dcto 
			INNER JOIN cj_cc_cliente cliente ON (dcto.id_cliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$query = sprintf("SELECT
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.idDepartamento AS id_modulo,
			dcto.idAnticipo AS idDocumento,
			dcto.saldoAnticipo AS saldoDocumento,
			dcto.numeroAnticipo AS numeroDocumento,
			dcto.fechaAnticipo AS fechaDocumento,
			dcto.observacionesAnticipo AS observacionDocumento,
		
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = dcto.idAnticipo
				AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_anticipo dcto
			INNER JOIN cj_cc_cliente cliente ON (dcto.idCliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$query = sprintf("SELECT
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.idDepartamentoNotaCredito AS id_modulo,
			dcto.idNotaCredito AS idDocumento,
			dcto.saldoNotaCredito AS saldoDocumento,
			dcto.numeracion_nota_credito AS numeroDocumento,
			dcto.fechaNotaCredito AS fechaDocumento,
			dcto.observacionesNotaCredito AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notacredito dcto
			INNER JOIN cj_cc_cliente cliente ON (dcto.idCliente = cliente.id)
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
			$idAnticipo = $row['idDocumento'];
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT *,
				(CASE
					WHEN (cxc_pago.id_concepto = 2) THEN
						IF (cxc_ant.saldoAnticipo > (SELECT tradein.total_credito FROM an_tradein tradein
													WHERE tradein.id_anticipo = cxc_ant.idAnticipo
														AND tradein.anulado IS NULL) AND cxc_ant.saldoAnticipo > 0,
							(SELECT tradein.total_credito FROM an_tradein tradein
							WHERE tradein.id_anticipo = cxc_ant.idAnticipo
								AND tradein.anulado IS NULL),
							cxc_ant.saldoAnticipo)
					ELSE
						cxc_ant.saldoAnticipo
				END) AS saldo_anticipo
			FROM cj_cc_anticipo cxc_ant
				LEFT JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				LEFT JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND (cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT'
					OR cxc_ant.estadoAnticipo IN (0));",
				valTpDato($idAnticipo, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while ($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
				if ((in_array($rowAnticipo['id_concepto'],array(2))
					&& in_array(idArrayPais,array(3))
					&& ($rowAnticipo['saldo_anticipo'] > 0 || ($rowAnticipo['saldo_anticipo'] == 0 && $rowAnticipo['estadoAnticipo'] == 1)))
				|| ((in_array($rowAnticipo['id_concepto'],array(1,6,7,8,9)) || in_array($rowAnticipo['estadoAnticipo'],array(0))) && $rowAnticipo['saldo_anticipo'] > 0)) {
					$onClick = sprintf("
					byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = '%s';
					byId('txtNumeroDctoPago').value = '%s';
					byId('txtMontoPago').value = '%s';
					
					xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));",
						$idAnticipo,
						$rowAnticipo['numeroAnticipo'],
						$rowAnticipo['saldo_anticipo']);
				}
			}
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDcto%s\" rel=\"#divFlotante2\" onclick=\"%s\"><button type=\"button\" title=\"Seleccionar\"><img class=\"puntero\" src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$onClick);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaDocumento']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroDocumento']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</span></td></tr>" : "";
				$htmlTb .= (strlen($row['observacionDocumento']) > 0) ? "<tr><td class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionDocumento'])."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoDocumento'], 2, ".", ",")."</td>";
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
		$htmlTb .= "<td colspan=\"12\">";
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

function insertarPrecio($idDetallePrecioContrato, $idPrecio, $nombrePrecio, $idPrecioDetalle, $descripcion, $precio, $diasPrecio, $idTipoPrecio, $diasCalculado, $totalPrecio, $arrayIva){
	$objResponse = new xajaxResponse();
	
	foreach($arrayIva as $idIva => $arrayIvaCargado){
		$arrayIdIva[] = $arrayIvaCargado["idIva"];
		$arrayPorcentajesIva[] = $arrayIvaCargado["iva"];
	}
	
	$htmlItmPie = sprintf("$('#trItmPiePrecio').before('".
		"<tr>".
			"<td><input type=\"checkbox\" value=\"%s\" class=\"checkboxPrecio\" />".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" name=\"hddIdDetallePrecioContrato[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdPrecioDetalle[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdTipoPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddDiasPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddDiasPrecioCalculado[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddTotalPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdIvaPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddPorcentajeIvaPrecio[]\" value=\"%s\" />".
			"</td>".
		"</tr>');",
		$idDetallePrecioContrato,
		//td
		utf8_encode($nombrePrecio),
		utf8_encode($descripcion),		
		$diasCalculado,
		number_format($precio, 2, ".", ","),
		implode(" <br> ", $arrayPorcentajesIva),
		number_format($totalPrecio, 2, ".", ","),
		//hidden
		$idDetallePrecioContrato,
		$idPrecio,
		$idPrecioDetalle,
		$idTipoPrecio,
		$diasPrecio,
		$precio,
		$diasCalculado,
		$totalPrecio,
		implode("|", $arrayIdIva),
		implode("|", $arrayPorcentajesIva));


	$objResponse->script($htmlItmPie);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"calcularPagosDeposito");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarLstTipoContrato");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"formDeposito");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarAccesorio");
$xajax->register(XAJAX_FUNCTION,"insertarPago");
$xajax->register(XAJAX_FUNCTION,"insertarPagoDeposito");
$xajax->register(XAJAX_FUNCTION,"listaAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"insertarPrecio");

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

function insertarItemMetodoPago($contFila, $idFormaPago, $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtIdBancoCliente = "", $txtCuentaClientePago = "", $txtIdBancoCompania = "", $txtIdCuentaCompaniaPago = "", $txtFechaDeposito = "", $lstTipoTarjeta = "", $porcRetencion = "", $montoRetencion = "", $porcComision = "", $montoComision = "", $txtMontoPago = "") {
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
			break;
	}
	
	$checkedCondicionMostrar = "checked=\"checked\"";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPiePago').before('".
		"<tr align=\"left\" id=\"trItmPago:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmPago:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"divMsjInfo2\">%s</td>".
			"<td class=\"divMsjInfo\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><table width=\"%s\">".
				"<tr><td>%s</td><td><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr>".
				"%s".
				"%s".
				"</table></td>".
			"<td><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMonto%s\" name=\"txtMonto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><button type=\"button\" id=\"btnEliminarPago%s\" onclick=\"confirmarEliminarPago(%s);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"/></button>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtFechaDeposito%s\" name=\"txtFechaDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdFormaPago%s\" name=\"txtIdFormaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdConcepto%s\" name=\"txtIdConcepto%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoCompania%s\" name=\"txtIdBancoCompania%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdCuentaCompaniaPago%s\" name=\"txtIdCuentaCompaniaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoCliente%s\" name=\"txtIdBancoCliente%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtTipoTarjeta%s\" name=\"txtTipoTarjeta%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			(in_array(idArrayPais,array(3))) ? "<input type=\"checkbox\" id=\"cbxCondicionMostrar\" name=\"cbxCondicionMostrar".$contFila."\" ".$checkedCondicionMostrar." value=\"1\">" : "",
			(in_array(idArrayPais,array(3))) ? cargaLstSumarPagoItm("lstSumarA".$contFila, $checkedMostrarContado) : "",
			utf8_encode($nombreFormaPago),
			"100%",
				$aVerDcto, $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
					$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				((strlen($descripcionMotivo) > 0) ? "<tr><td><span class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($descripcionMotivo)."</span></td></tr>" : ""),
				((strlen($observacionDcto) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".($observacionDcto)."</span></td></tr>" : ""),
			$contFila, $contFila, utf8_encode($txtBancoClientePago),
				$contFila, $contFila, $txtCuentaClientePago,
			$contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
				$contFila, $contFila, $txtCuentaCompaniaPago,
			$contFila, $contFila, number_format($txtMontoPago, 2, ".", ","),
			$contFila, $contFila,
				$contFila, $contFila, $hddIdPago,
				$contFila, $contFila, $txtFechaDeposito,
				$contFila, $contFila, $idFormaPago,
				$contFila, $contFila, $hddIdConcepto,
				$contFila, $contFila, $txtIdBancoCompania,
				$contFila, $contFila, $txtIdCuentaCompaniaPago,
				$contFila, $contFila, $txtIdBancoCliente,
				$contFila, $contFila, $lstTipoTarjeta);
	
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

function fecha($fechaTiempo){
	if($fechaTiempo != ""){
		$fechaTiempo = date(spanDateFormat,strtotime($fechaTiempo));
	}	
	return $fechaTiempo;
}

function tiempo($fechaTiempo){
	if($fechaTiempo != ""){
		$fechaTiempo = date("h:i A",strtotime($fechaTiempo));
	}	
	return $fechaTiempo;
}

function fechaTiempo($fechaTiempo){
	if($fechaTiempo != ""){
		$fechaTiempo = date(spanDateFormat." h:i A",strtotime($fechaTiempo));
	}	
	return $fechaTiempo;
}

?>