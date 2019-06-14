<?php

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

function cargarDcto($idFactura) {
	$objResponse = new xajaxResponse();
		
	if ($idFactura > 0) {
		$objResponse->script("
		byId('txtObservacion').className = 'inputHabilitado';");
		
		$sql = sprintf("SELECT
				cxc_fact.id_empresa,
				cxc_fact.numeroFactura,
				cxc_fact.numeroControl,
				cxc_fact.numeroPedido,
				cxc_fact.fechaRegistroFactura,
				cxc_fact.fechaVencimientoFactura,
				cxc_fact.fecha_pagada,
				cxc_fact.fecha_cierre,
				CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
				cxc_fact.idCliente AS id_cliente,
				cxc_fact.observacionFactura,
				cxc_fact.subtotalFactura AS subtotal_factura,
				cxc_fact.porcentaje_descuento,
				cxc_fact.descuentoFactura AS subtotal_descuento,
				cxc_fact.baseImponible AS base_imponible,
				cxc_fact.porcentajeIvaFactura AS porcentaje_iva,
				cxc_fact.calculoIvaFactura AS subtotal_iva,
				cxc_fact.porcentajeIvaDeLujoFactura AS porcentaje_iva_lujo,
				cxc_fact.calculoIvaDeLujoFactura AS subtotal_iva_lujo,
				cxc_fact.montoExento AS monto_exento,
				cxc_fact.montoExonerado AS monto_exonerado,
				cxc_fact.condicionDePago,
				cxc_fact.montoTotalFactura,
				cxc_fact.diasDeCredito,
				
				contrato.*,
				tipo_contrato.id_clave_movimiento_dev,
				clave_mov.clave,
				clave_mov.descripcion AS descripcion_clave_movimiento,
				presupuesto.numero_presupuesto_venta,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN al_contrato_venta contrato ON (cxc_fact.numeroPedido = contrato.id_contrato_venta)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			INNER JOIN pg_clave_movimiento clave_mov ON (tipo_contrato.id_clave_movimiento_dev = clave_mov.id_clave_movimiento)
			INNER JOIN pg_empleado empleado ON (cxc_fact.idVendedor = empleado.id_empleado)
			LEFT JOIN al_presupuesto_venta presupuesto ON (contrato.id_presupuesto_venta = presupuesto.id_presupuesto_venta)
			WHERE cxc_fact.idFactura = %s
			AND contrato.estatus_contrato_venta = 3;",
			valTpDato($idFactura, "int"));
		$rs = mysql_query($sql);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowDcto = mysql_fetch_assoc($rs);
		
		$idContrato = $rowDcto["id_contrato_venta"];
		
		if(mysql_num_rows($rs) == 0){
			$objResponse->alert("El Contrato no puede ser cargado debido a que su estado no es válido");
			return $objResponse->script("byId('btnCancelar').click();");
		}
		
		$Result1 = validarAperturaCaja($rowDcto['id_empresa'], date("Y-m-d"));
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
			valTpDato($idContrato, "int"));
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
			valTpDato($idContrato, "int"));
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
		
		$Result1 = buscarNumeroControl($rowDcto['id_empresa'], $rowDcto['id_clave_movimiento_dev']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->assign("txtNumeroControlNotaCredito","value",($Result1[1]));
		}
					
		// DATOS DEL CONTRATO
		$objResponse->assign("txtIdEmpresa","value",$rowDcto['id_empresa']);
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowDcto['nombre_empresa']));
		$objResponse->assign("hddIdContrato","value",$rowDcto['id_contrato_venta']);
		$objResponse->assign("txtNumeroContrato","value",$rowDcto['numero_contrato_venta']);
		$objResponse->assign("txtFechaContrato","value",date("d-m-Y",strtotime($rowDcto['fecha_creacion'])));
		$objResponse->loadCommands(cargaLstMoneda($rowDcto['id_moneda']));
		$objResponse->loadCommands(cargarLstTipoContrato($rowDcto['id_tipo_contrato'], $rowDcto['id_empresa']));
		
		$objResponse->assign("txtDescuento","value",$rowDcto['porcentaje_descuento']);
		
		$objResponse->assign("txtFechaSalida","value",fecha($rowDcto['fecha_salida']));
		$objResponse->assign("txtHoraSalida","value",tiempo($rowDcto['fecha_salida']));
		$objResponse->assign("txtFechaEntrada","value",fecha($rowDcto['fecha_entrada']));
		$objResponse->assign("txtHoraEntrada","value",tiempo($rowDcto['fecha_entrada']));
		$objResponse->assign("txtFechaEntradaFinal","value",fecha($rowDcto['fecha_final']));
		$objResponse->assign("txtHoraEntradaFinal","value",tiempo($rowDcto['fecha_final']));
		
		$objResponse->assign("txtKilometrajeSalida","value",$rowDcto['kilometraje_salida']);
		$objResponse->assign("lstCombustibleSalida","value",$rowDcto['nivel_combustible_salida']);
		$objResponse->assign("txtKilometrajeEntrada","value",$rowDcto['kilometraje_entrada']);
		$objResponse->assign("lstCombustibleEntrada","value",$rowDcto['nivel_combustible_entrada']);
		
		$objResponse->assign("txtDiasContrato","value",$rowDcto['dias_contrato']);
		$objResponse->assign("txtDiasSobreTiempo","value",$rowDcto['dias_sobre_tiempo']);
		$objResponse->assign("txtDiasBajoTiempo","value",$rowDcto['dias_bajo_tiempo']);
		$objResponse->assign("txtDiasTotal","value",$rowDcto['dias_total']);
		
		$idCliente = $rowDcto['id_cliente'];
		$idCondicionPago = $rowDcto['condicion_pago'];
		$idEmpresa = $rowDcto['id_empresa'];
		
		// DATOS DE LA UNIDAD
		$sql = sprintf("SELECT
				uni_fis.id_unidad_fisica,
				uni_fis.id_activo_fijo,
				uni_fis.serial_carroceria,
				uni_fis.serial_motor,
				uni_fis.serial_carroceria,
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
			valTpDato($rowDcto["id_unidad_fisica"],"int"));
		
		$rs = mysql_query($sql);	
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUnidad = mysql_fetch_assoc($rs);
	
		$objResponse->assign("txtIdUnidadFisica","value",utf8_encode($rowUnidad['id_unidad_fisica']));
		$objResponse->assign("txtSerialCarroceriaVehiculo","value",utf8_encode($rowUnidad['serial_carroceria']));
		$objResponse->assign("txtPlacaVehiculo","value",utf8_encode($rowUnidad['placa']));
		$objResponse->assign("txtUnidadBasica","value",utf8_encode($rowUnidad['nom_uni_bas']));
		$objResponse->assign("txtMarcaVehiculo","value",utf8_encode($rowUnidad['nom_marca']));
		$objResponse->assign("txtModeloVehiculo","value",utf8_encode($rowUnidad['nom_modelo']));
		$objResponse->assign("txtAnoVehiculo","value",utf8_encode($rowUnidad['nom_ano']));
		$objResponse->assign("txtColorVehiculo","value",utf8_encode($rowUnidad['color_externo1']));
		$objResponse->assign("txtClaseVehiculo","value",utf8_encode($rowUnidad['nom_clase']));
		$objResponse->assign("txtCondicionVehiculo","value",utf8_encode($rowUnidad['condicion_unidad']));
		$objResponse->assign("txtAlmacenVehiculo","value",utf8_encode($rowUnidad['nom_almacen']));
		$objResponse->assign("txtKilometrajeVehiculo","value",utf8_encode($rowUnidad['kilometraje']));
		$objResponse->assign("hddIdUnidadBasica","value",$rowUnidad['id_uni_bas']);
		$objResponse->assign("hddIdModelo","value",utf8_encode($rowUnidad['id_modelo']));
		$objResponse->assign("hddIdClase","value",utf8_encode($rowUnidad['id_clase']));
		
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
	
		if ($idCondicionPago == 0) { // 0 = Credito, 1 = Contado
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
				
				$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
			} else {			
				$objResponse->assign("txtDiasCreditoCliente","value","0");
			}
		
		} else if ($idCondicionPago == 1) { // 0 = Credito, 1 = Contado		
			$objResponse->assign("txtDiasCreditoCliente","value","0");
		}
		
		$nombreCondicionPago = ($idCondicionPago == 0) ? "CREDITO" : "CONTADO";
		$objResponse->assign("hddTipoPago","value",$idCondicionPago);
		$objResponse->assign("txtTipoPago","value",$nombreCondicionPago);
		
		// DATOS DE LA NOTA DE CREDITO
		$objResponse->assign("txtFechaNotaCredito","value",date("d-m-Y"));		
		$objResponse->assign("hddIdClaveMovimiento","value",$rowDcto['id_clave_movimiento_dev']);
		$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowDcto['clave'].") ".$rowDcto['descripcion_clave_movimiento']));
		$objResponse->call("selectedOption","lstTipoClave",2);
		$objResponse->script("byId('lstTipoClave').onchange = function(){ selectedOption(this.id,'".(2)."'); };");
		
		// DATOS DEL CLIENTE
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
		$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
		$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
		$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
		$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
		$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
		$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));

		// DATOS DE LA FACTURA
		$objResponse->assign("txtIdEmpresa","value",$rowDcto['id_empresa']);
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowDcto['nombre_empresa']));
		$objResponse->assign("hddIdFactura","value",$idFactura);
		$objResponse->assign("txtFechaFactura","value",date("d-m-Y",strtotime($rowDcto['fechaRegistroFactura'])));
		$objResponse->assign("txtFechaVencimientoFactura","value",date("d-m-Y",strtotime($rowDcto['fechaVencimientoFactura'])));
		$objResponse->assign("txtNumeroFactura","value",$rowDcto['numeroFactura']);
		$objResponse->assign("txtNumeroControlFactura","value",$rowDcto['numeroControl']);
		$objResponse->assign("txtIdPresupuesto","value",$rowDcto['numeroPresupuesto']);
		$objResponse->assign("txtIdPedido","value",$rowDcto['numeroPedido']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowDcto['nombre_empleado']));
	

		

		// DATOS DEL PRESUPUESTO
		$objResponse->assign("hddIdPresupuestoVenta","value", $rowDcto['id_presupuesto_venta']);
		$objResponse->assign("txtNumeroPresupuestoVenta","value", $rowDcto['numero_presupuesto_venta']);			
		
		$objResponse->script("
		byId('lstMoneda').className = 'inputInicial';
		byId('lstMoneda').onchange = function () {
			selectedOption(this.id,".$rowDcto["id_moneda"].");
		}");
		
		$objResponse->script("
		byId('lstCombustibleSalida').onchange = function () {
			selectedOption(this.id,".$rowDcto["nivel_combustible_salida"].");
		}");
		
		$objResponse->script("
		byId('lstCombustibleEntrada').onchange = function () {
			selectedOption(this.id,'".$rowDcto["nivel_combustible_entrada"]."');
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

function guardarDcto($frmDcto, $frmListaPrecio, $frmListaAccesorio, $frmListaAdicional, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_devolucion_alquiler_form","insertar")) { return $objResponse; }
	
	$idDocumentoVenta = $frmDcto['hddIdContrato'];
	
	$sql = sprintf("SELECT 
						contrato.* 
					FROM al_contrato_venta contrato 
					WHERE contrato.id_contrato_venta = %s;",
		valTpDato($idDocumentoVenta, "int"));
	$rs = mysql_query($sql);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	if($row["estatus_contrato_venta"] != 3){
		$objResponse->alert("El Contrato no puede ser actualizado debido a que su estado no es válido");
		return $objResponse;
	}
	
	$idFactura = $frmDcto['hddIdFactura'];
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowFact = mysql_fetch_array($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion, 4 = Alquiler
	$idEmpleadoAsesor = $rowFact['idVendedor'];
	$idClaveMovimiento = $frmDcto['hddIdClaveMovimiento'];
	$idTipoPago = $frmDcto['hddTipoPago'];
		
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// VERIFICA SI EL DOCUMENTO YA HA SIDO DEVUELTO
	$queryVerif = sprintf("SELECT * FROM cj_cc_notacredito
	WHERE idDocumento = %s
		AND tipoDocumento LIKE 'FA'
		AND idDepartamentoNotaCredito IN (%s)",
		valTpDato($idFactura, "int"),
		valTpDato($idModulo, "int"));
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if (mysql_num_rows($rsVerif) > 0) {
		return $objResponse->alert('Este documento ya ha sido devuelto');
	}
	
	mysql_query("START TRANSACTION;");
				
	$updateSQL = sprintf("UPDATE al_contrato_venta SET estatus_contrato_venta = 4 WHERE id_contrato_venta = %s;",
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
	
	if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado la numeracion de notas de credito"); }
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// INSERTA LOS DATOS DE LA NOTA DE CREDITO
	$insertSQL = sprintf("INSERT INTO cj_cc_notacredito (numeracion_nota_credito, id_empresa, idCliente, id_clave_movimiento, id_empleado_vendedor, idDepartamentoNotaCredito, montoNetoNotaCredito, saldoNotaCredito, fechaNotaCredito, observacionesNotaCredito, estadoNotaCredito, idDocumento, tipoDocumento, porcentajeIvaNotaCredito, ivaNotaCredito, subtotalNotaCredito, porcentajeIvaDeLujoNotaCredito, ivaLujoNotaCredito, montoExoneradoCredito, montoExentoCredito, baseimponibleNotaCredito, numeroControl, porcentaje_descuento, subtotal_descuento, aplicaLibros, estatus_nota_credito, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($numeroActual, "text"),
		valTpDato($idEmpresa, "int"),
		valTpDato($rowFact['idCliente'], "int"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpleadoAsesor, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($rowFact['montoTotalFactura'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($idFactura, "int"),
		valTpDato("FA", "text"),
		valTpDato($rowFact['porcentajeIvaFactura'], "real_inglesa"),
		valTpDato($rowFact['calculoIvaFactura'], "real_inglesa"),
		valTpDato($rowFact['subtotalFactura'], "real_inglesa"),
		valTpDato($rowFact['porcentajeIvaDeLujoFactura'], "real_inglesa"),
		valTpDato($rowFact['calculoIvaDeLujoFactura'], "real_inglesa"),
		valTpDato($rowFact['montoExonerado'], "real_inglesa"),
		valTpDato($rowFact['montoExento'], "real_inglesa"),
		valTpDato($rowFact['baseImponible'], "real_inglesa"),
		valTpDato($frmDcto['txtNumeroControlNotaCredito'], "text"),
		valTpDato($rowFact['porcentaje_descuento'], "real_inglesa"),
		valTpDato($rowFact['descuentoFactura'], "real_inglesa"),
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato(2, "int"), // 1 = Aprobada, 2 = Aplicada
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idNotaCredito,
		$idModulo,
		"NOTA CREDITO");
	
	// INSERTA EL PRECIO / TARIFA
	foreach($frmListaPrecio['hddIdDetallePrecioContrato'] as $indice => $idDetallePrecioContrato){
		$insertSQL = sprintf("INSERT INTO al_nota_credito_detalle_precio (id_nota_credito, id_precio, id_precio_detalle, id_tipo_precio, dias, precio, dias_calculado, total_precio)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idNotaCredito, "int"),
			valTpDato($frmListaPrecio['hddIdPrecio'][$indice], "int"),
			valTpDato($frmListaPrecio['hddIdPrecioDetalle'][$indice], "int"),
			valTpDato($frmListaPrecio['hddIdTipoPrecio'][$indice], "int"),
			valTpDato($frmListaPrecio['hddDiasPrecio'][$indice], "int"),
			valTpDato($frmListaPrecio['hddPrecio'][$indice], "real_inglesa"),
			valTpDato($frmListaPrecio['hddDiasPrecioCalculado'][$indice], "int"),
			valTpDato($frmListaPrecio['hddTotalPrecio'][$indice], "real_inglesa"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDetalleNotaCreditoPrecio = mysql_insert_id();
		
		$arrayIdPrecioImpuesto = array_filter(explode("|", $frmListaPrecio['hddIdIvaPrecio'][$indice]));
		$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaPrecio['hddPorcentajeIvaPrecio'][$indice]));
		
		foreach($arrayIdPrecioImpuesto as $indiceImpuesto => $idImpuesto){
			$insertSQL = sprintf("INSERT INTO al_nota_credito_detalle_precio_impuesto (id_nota_credito_detalle_precio, id_impuesto, impuesto)
			VALUE (%s, %s, %s);",
				valTpDato($idDetalleNotaCreditoPrecio, "int"),
				valTpDato($arrayIdPrecioImpuesto[$indiceImpuesto], "int"),
				valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}		
	}
	
	//INSERTA ACCESORIOS
	foreach($frmListaAccesorio['hddIdDetalleAccesorioContrato'] as $indice => $idDetalleAccesorioContrato){
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios (id_nota_credito, id_accesorio, id_tipo_accesorio, cantidad, precio_unitario, costo_compra, tipo_accesorio)
		VALUE (%s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idNotaCredito, "int"),
			valTpDato($frmListaAccesorio['hddIdAccesorio'][$indice], "int"),
			valTpDato($frmListaAccesorio['hddIdTipoAccesorio'][$indice], "int"),
			valTpDato($frmListaAccesorio['hddCantidadAccesorio'][$indice], "real_inglesa"),
			valTpDato($frmListaAccesorio['hddPrecioAccesorio'][$indice], "real_inglesa"),
			valTpDato($frmListaAccesorio['hddCostoAccesorio'][$indice], "real_inglesa"),
			valTpDato(2, "int"));//1 = Paquete, 2 = Accesorio
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDetalleNotaCreditoAccesorio = mysql_insert_id();
		
		$arrayIdAccesorioImpuesto = array_filter(explode("|", $frmListaAccesorio['hddIdIvaAccesorio'][$indice]));
		$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaAccesorio['hddPorcentajeIvaAccesorio'][$indice]));
		
		foreach($arrayIdAccesorioImpuesto as $indiceImpuesto => $idImpuesto){
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios_impuesto (id_nota_credito_detalle_accesorios, id_impuesto, impuesto)
			VALUE (%s, %s, %s);",
				valTpDato($idDetalleNotaCreditoAccesorio, "int"),
				valTpDato($arrayIdAccesorioImpuesto[$indiceImpuesto], "int"),
				valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	//INSERTA ADICIONALES
	foreach($frmListaAdicional['hddIdDetalleAccesorioContrato'] as $indice => $idDetalleAccesorioContrato){
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios (id_nota_credito, id_accesorio, id_tipo_accesorio, cantidad, precio_unitario, costo_compra, tipo_accesorio)
		VALUE (%s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idNotaCredito, "int"),
			valTpDato($frmListaAdicional['hddIdAccesorio'][$indice], "int"),
			valTpDato($frmListaAdicional['hddIdTipoAccesorio'][$indice], "int"),
			valTpDato($frmListaAdicional['hddCantidadAccesorio'][$indice], "real_inglesa"),
			valTpDato($frmListaAdicional['hddPrecioAccesorio'][$indice], "real_inglesa"),
			valTpDato($frmListaAdicional['hddCostoAccesorio'][$indice], "real_inglesa"),
			valTpDato(2, "int"));//1 = Paquete, 2 = Accesorio
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDetalleNotaCreditoAccesorio = mysql_insert_id();
		
		$arrayIdAccesorioImpuesto = array_filter(explode("|", $frmListaAdicional['hddIdIvaAccesorio'][$indice]));
		$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaAdicional['hddPorcentajeIvaAccesorio'][$indice]));
		
		foreach($arrayIdAccesorioImpuesto as $indiceImpuesto => $idImpuesto){
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios_impuesto (id_nota_credito_detalle_accesorios, id_impuesto, impuesto)
			VALUE (%s, %s, %s);",
				valTpDato($idDetalleNotaCreditoAccesorio, "int"),
				valTpDato($arrayIdAccesorioImpuesto[$indiceImpuesto], "int"),
				valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
		
	// INSERTA LOS IMPUESTOS DE LA NOTA DE CREDITO
	foreach ($frmTotalDcto['hddIdIva'] as $indice => $idIva) {
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_iva (id_nota_credito, base_imponible, subtotal_iva, id_iva, iva)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($idNotaCredito, "int"),
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
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
		valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	if (in_array($rowFact['estadoFactura'],array(0,2))) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
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
		
		$fechaRegistroPago = $rowAperturaCaja["fechaAperturaCaja"];
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(45, "int"), // 45 = Recibo de Pago Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s)",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
			valTpDato(0, "int"),		
			valTpDato($idFactura, "int"),
			valTpDato($idModulo, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO %s (id_factura, fecha_pago)
		VALUES (%s, %s)",
			valTpDato(((in_array($idCajaPpal,array(1))) ? "cj_cc_encabezado_pago_v" : "cj_cc_encabezado_pago_rs"), "campo"),
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idEncabezadoPago = mysql_insert_id();
		
		if ($rowFact['estadoFactura'] == 0) { // 0 = No Cancelado
			if ($rowFact['saldoFactura'] == str_replace(",","",$frmTotalDcto['txtTotalOrden'])) {
				$saldoFactura = $frmTotalDcto['txtTotalOrden'];
				$estatusFactura = 1; // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $frmTotalDcto['txtTotalOrden'];
			} else if ($rowFact['saldoFactura'] > str_replace(",","",$frmTotalDcto['txtTotalOrden'])) {
				$saldoFactura = $frmTotalDcto['txtTotalOrden'];
				$estatusFactura = 2; // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = $frmTotalDcto['txtTotalOrden'];
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				saldoFactura = (saldoFactura - %s),
				estadoFactura = %s,
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($saldoFactura, "real_inglesa"),
				valTpDato($estatusFactura, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL PAGO DEBIDO A LA RETENCION
			$insertSQL = sprintf("INSERT INTO an_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, montoPagado, numeroFactura, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, idCierre, estatus, id_empleado_creador, id_encabezado_v)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
				valTpDato(8, "int"),
				valTpDato($idNotaCredito, "text"),
				valTpDato(1, "int"),
				valTpDato(1, "int"),
				valTpDato($saldoNotaCred, "real_inglesa"),
				valTpDato($rowFact['numeroFactura'], "text"),
				valTpDato(1, "int"),
				valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($rowAperturaCaja["id"], "int"),
				valTpDato(0, "int"),
				valTpDato(1, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($idEncabezadoPago, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idPago = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
		} else if ($rowFact['estadoFactura'] == 2) { // 2 = Parcialmente Cancelado
			if ($rowFact['saldoFactura'] == str_replace(",","",$frmTotalDcto['txtTotalOrden'])) {
				$saldoFactura = $frmTotalDcto['txtTotalOrden'];
				$estatusFactura = 1; // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $rowFact['saldoFactura'];
			} else if ($rowFact['saldoFactura'] > str_replace(",","",$frmTotalDcto['txtTotalOrden'])) {
				$saldoFactura = $frmTotalDcto['txtTotalOrden'];
				$estatusFactura = 2; // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = $frmTotalDcto['txtTotalOrden'];
			} else if ($rowFact['saldoFactura'] < str_replace(",","",$frmTotalDcto['txtTotalOrden'])) {
				$saldoFactura = $rowFact['saldoFactura'];
				$estatusFactura = 1; // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $rowFact['saldoFactura'];
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				saldoFactura = (saldoFactura - %s),
				estadoFactura = %s,
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($saldoFactura, "real_inglesa"),
				valTpDato($estatusFactura, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL PAGO DEBIDO A LA RETENCION
			$insertSQL = sprintf("INSERT INTO an_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, montoPagado, numeroFactura, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, idCierre, estatus, id_empleado_creador, id_encabezado_v)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)), "date"),
				valTpDato(8, "int"),
				valTpDato($idNotaCredito, "text"),
				valTpDato(1, "int"),
				valTpDato(1, "int"),
				valTpDato($saldoNotaCred, "real_inglesa"),
				valTpDato($rowFact['numeroFactura'], "text"),
				valTpDato(1, "int"),
				valTpDato(0, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato($rowAperturaCaja["id"], "int"),
				valTpDato(0, "int"),
				valTpDato(1, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($idEncabezadoPago, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idPago = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
		
		$arrayIdDctoContabilidad[] = array(
			$idPago,
			$idModulo,
			"CAJAENTRADA");
		
		// INSERTA EL DETALLE DEL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
		VALUES (%s, %s)",
			valTpDato($idEncabezadoReciboPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	} else if ($rowFact['estadoFactura'] == 1) { // 1 = Cancelado
		// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
			anulada = 'SI'
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	// ACTUALIZA EL SALDO Y EL MONTO PAGADO DE LA NOTA DE CREDITO
	$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
		saldoNotaCredito = montoNetoNotaCredito
	WHERE cxc_nc.idNotaCredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DEPENDIENDO DE SUS PAGOS
	$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
		saldoNotaCredito = saldoNotaCredito
							- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
										WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
											AND cxc_pago.formaPago IN (8)
											AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
												AND cxc_pago.formaPago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
											WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
												AND cxc_pago.idFormaPago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0)
								+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
											WHERE cxc_pago.numeroControlDetalleAnticipo = cxc_nc.idNotaCredito
												AND cxc_pago.id_forma_pago IN (8)
												AND cxc_pago.estatus IN (1,2)), 0))
	WHERE cxc_nc.idNotaCredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
	// ACTUALIZA EL ESTATUS DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado)
	$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
		estadoNotaCredito = (CASE
							WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
								AND ROUND(saldoNotaCredito, 2) > 0) THEN
								0
							WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
								AND ROUND(saldoNotaCredito, 2) <= 0
								AND cxc_nc.idNotaCredito IN (SELECT * 
															FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																WHERE cxc_pago.formaPago IN (8)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																WHERE cxc_pago.formaPago IN (8)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																WHERE cxc_pago.idFormaPago IN (8)
																	AND cxc_pago.estatus IN (1)
																
																UNION
																
																SELECT cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
																WHERE cxc_pago.id_forma_pago IN (8)
																	AND cxc_pago.estatus IN (1)) AS q)) THEN
								3
							WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
								AND ROUND(montoNetoNotaCredito, 2) = ROUND(saldoNotaCredito, 2)) THEN
								1
							WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
								AND ROUND(montoNetoNotaCredito, 2) > ROUND(saldoNotaCredito, 2)
								AND ROUND(saldoNotaCredito, 2) > 0) THEN
								2
							WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
								AND ROUND(saldoNotaCredito, 2) <= 0) THEN
								3
						END)
	WHERE cxc_nc.idNotaCredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// VERIFICA EL SALDO DE LA NOTA DE CREDITO A VER SI ESTA NEGATIVO
	$querySaldoDcto = sprintf("SELECT cxc_nc.*,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
	WHERE idNotaCredito = %s
		AND saldoNotaCredito < 0;",
		valTpDato($idNotaCredito, "int"));
	$rsSaldoDcto = mysql_query($querySaldoDcto);
	if (!$rsSaldoDcto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }			
	$totalRowsSaldoDcto = mysql_num_rows($rsSaldoDcto);
	$rowSaldoDcto = mysql_fetch_assoc($rsSaldoDcto);
	if ($totalRowsSaldoDcto > 0) { return $objResponse->alert("La Nota de Crédito Nro. ".$rowSaldoDcto['numeracion_nota_credito']." del Cliente ".$rowSaldoDcto['nombre_cliente']." presenta un saldo negativo"); }
	
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
		valTpDato($rowFact['idCliente'], "int"),
		valTpDato($idEmpresa, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("COMMIT");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA CREDITO") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasRe")) { generarNotasRe($idNotaCredito,"",""); } break;
					case 1 : if (function_exists("generarNotasVentasSe")) { generarNotasVentasSe($idNotaCredito,"",""); } break;
					case 2 : if (function_exists("generarNotasVentasVe")) { generarNotasVentasVe($idNotaCredito,"",""); } break;
					case 4 : if (function_exists("generarNotasVentasAl")) { generarNotasVentasAl($idNotaCredito,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert("Nota de Credito Guardada con Exito");
	$objResponse->script("
	window.location.href='cj_devolucion_venta_list.php';
	verVentana('../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=".$idNotaCredito."', 960, 550);");
	
		
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
			"<td><input type=\"checkbox\" value=\"%s\" class=\"%s\" style=\"display:none;\" />".
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

function insertarPrecio($idDetallePrecioContrato, $idPrecio, $nombrePrecio, $idPrecioDetalle, $descripcion, $precio, $diasPrecio, $idTipoPrecio, $diasCalculado, $totalPrecio, $arrayIva){
	$objResponse = new xajaxResponse();
	
	foreach($arrayIva as $idIva => $arrayIvaCargado){
		$arrayIdIva[] = $arrayIvaCargado["idIva"];
		$arrayPorcentajesIva[] = $arrayIvaCargado["iva"];
	}
	
	$htmlItmPie = sprintf("$('#trItmPiePrecio').before('".
		"<tr>".
			"<td><input type=\"checkbox\" value=\"%s\" class=\"checkboxPrecio\" style=\"display:none;\" />".
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

$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarLstTipoContrato");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarAccesorio");
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
		return array(false, "Debe cerrar la caja del dia: ".date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja'])));
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
		$fechaTiempo = date("d-m-Y",strtotime($fechaTiempo));
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
		$fechaTiempo = date("d-m-Y h:i A",strtotime($fechaTiempo));
	}	
	return $fechaTiempo;
}

?>