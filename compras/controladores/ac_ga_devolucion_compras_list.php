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

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaRegistroCompra(0, "id_factura", "DESC", $valBusq));
	
	return $objResponse;
}

function formNotaCredito($idFacturaCompra) {
	$objResponse = new xajaxResponse();
	
	//BUSCA LOS DATOS DE LA FACTURA DE COMPRA
	$queryFact = sprintf("SELECT 
		id_movimiento,
		ga_movimiento.id_clave_movimiento,
		id_documento, 
		id_factura, 
		id_empresa, 
		numero_factura_proveedor, 
		numero_control_factura, 
		fecha_factura_proveedor, 
		fecha_origen, 
		cp_factura. id_proveedor, 
		cp_factura.id_modulo, 
		aplica_libros, 
		nombre AS nombre_proveedor, 
		descripcion AS descripcion_clave_momiento, 
		pg_clave_movimiento.tipo AS tipo_clave_momiento,
		(CASE pg_clave_movimiento.tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS descripcion_tipo_movimiento,
		(IFNULL(cp_factura.subtotal_factura, 0) - IFNULL(cp_factura.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cp_factura_gasto.monto) AS total_monto_gasto FROM cp_factura_gasto
					WHERE cp_factura_gasto.id_factura = cp_factura.id_factura AND cp_factura_gasto.id_modo_gasto IN(1,3)), 0)
			+ IFNULL((SELECT SUM(cp_factura_iva.subtotal_iva) AS total_iva FROM cp_factura_iva
					WHERE cp_factura_iva.id_factura = cp_factura.id_factura), 0)
		) AS totalFactura
		
	FROM ga_movimiento
		INNER JOIN cp_factura on cp_factura.id_factura = ga_movimiento.id_documento
		INNER JOIN cp_proveedor ON cp_proveedor.id_proveedor = cp_factura.id_proveedor
		INNER JOIN pg_clave_movimiento ON pg_clave_movimiento.id_clave_movimiento = ga_movimiento.id_clave_movimiento
	WHERE id_documento = %s",
		valTpDato($idFacturaCompra, "int"));
	$rsFactura = mysql_query($queryFact);
	if(!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowsFactura = mysql_fetch_array($rsFactura);
	
	$objResponse->assign("txtHiddIdFactura","value",$idFacturaCompra);

	$objResponse->loadCommands(asignarEmpresaUsuario($rowsFactura['id_empresa'], "Empresa", "ListaEmpresa"));
	$objResponse->loadCommands(listaDetalleFactura(0,'','',$rowsFactura['id_factura']));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Devolución de Compra (Nro. Factura: ".$rowsFactura['numero_factura_proveedor'].")");
	$objResponse->assign("txtIdProv","value",$rowsFactura['id_proveedor']);
	
	$objResponse->call("selectedOption","lstAplicaLibro",$rowsFactura['aplica_libros']);
	$objResponse->call("asignarAplicaLibro",$rowsFactura['aplica_libros']);
	
	$objResponse->loadCommands(cargaLstClaveMovimiento('lstClaveMovimiento', '3', '4', '', '', '', ''));
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimientoNotaCredito",$rowsFactura['id_modulo'],"4","","3"));

	$objResponse->assign("txtFechaRegistroNotaCredito","value",date(spanDateFormat));
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowsFactura['nombre_proveedor']));
	$objResponse->assign("txtNumeroFactura","value",$rowsFactura['numero_factura_proveedor']);
	$objResponse->assign("txtNumeroControlFactura","value",$rowsFactura['numero_control_factura']);
	$objResponse->assign("txtFechaRegistroFactura","value",implode("-", array_reverse(explode("-", $rowsFactura['fecha_origen']))));
	$objResponse->assign("txtFechaProveedorFactura","value",implode("-", array_reverse(explode("-", $rowsFactura['fecha_factura_proveedor']))));
	$objResponse->assign("txtTipoClaveFactura","value",utf8_encode($rowsFactura['descripcion_tipo_movimiento']));
	$objResponse->assign("hhdTipoClaveFactura","value",utf8_encode($rowsFactura['tipo_clave_momiento']));
	$objResponse->assign("hddIdClaveMovimiento","value",$rowsFactura['id_clave_movimiento']);
	$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowsFactura['descripcion_clave_momiento']));
	$objResponse->assign("txtTotalFacturaCompra","value",number_format($rowsFactura['totalFactura'], 2, ".", ","));
	$objResponse->assign("txtTotalOrden","value",number_format($rowsFactura['totalFactura'], 2, ".", ","));

	return $objResponse;
}

function guardarDcto($frmDcto, $frmBuscar){
	$objResponse = new xajaxResponse();
	
	$idFacturaCompra = $frmDcto['txtHiddIdFactura'];	
	mysql_query("START TRANSACTION;");
	
	//BUSCA LOS DATOS DE LA FACTURA A DEVOLVER
	$queryBusFactura = sprintf("SELECT * FROM cp_factura WHERE id_factura = %s;", 
		valTpDato($idFacturaCompra, "int"));
	$rsFactura = mysql_query($queryBusFactura);
	if (!$rsFactura) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	$idModoCompra = $rowFactura['id_modo_compra'];
	$idModulo = $rowFactura['id_modulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idClaveMovimiento = $frmDcto['lstClaveMovimientoNotaCredito']; 

	if ($frmDcto['lstActivo'] == "" && str_replace(",", "", $frmDcto['txtTotalFacturaCompra']) != str_replace(",", "", $frmDcto['txtTotalOrden'])) {
		errorGuardarDcto($objResponse); 
		return $objResponse->alert("Para anular el registro de compra, la devolucion debe tener el mismo monto");
	}
	
	if (str_replace(",", "", $frmDcto['txtTotalOrden']) > str_replace(",", "", $frmDcto['txtTotalFacturaCompra'])) {
		errorGuardarDcto($objResponse); 
		return $objResponse->alert("La devolución no puede tener un monto mayor al del registro de compra");
	}
	
	if ($frmDcto['lstAplicaLibro'] == 0) {//SI NO VA A APLICAR A LIBRO
		// NUMERACION DEL DOCUMENTO
		if ($frmDcto['lstActivo'] == 0 && $frmDcto['lstActivo'] != ""){
		} else {
			$idNumeraciones = 9; // 9 = Nota Crédito CxP
		}
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE (emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
										WHERE clave_mov.id_clave_movimiento = %s)
			OR emp_num.id_numeracion = %s)
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre 
																							FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato($idClaveMovimiento, "int"),
			valTpDato($idNumeraciones, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$frmDcto['txtNumeroControl'] = $numeroActual;
	} else {
		$numeroActual = $frmDcto['txtNumeroNotaCredito'];
	}

	//GUARDA LOS DATOS DE DEVOLUCION
	$insertDevolSQL = sprintf("INSERT INTO cp_notacredito (id_empresa, id_proveedor, saldo_notacredito, fecha_notacredito, observacion_notacredito, estado_notacredito, id_documento, tipo_documento, subtotal_notacredito, subtotal_descuento, total_cuenta_pagar, id_departamento_notacredito, monto_exonerado_notacredito, monto_exento_notacredito, aplica_libros_notacredito, numero_nota_credito, numero_control_notacredito, fecha_registro_notacredito, id_empleado_creador)
	SELECT 
		id_empresa, 
		id_proveedor, 
		%s, 
		%s, 
		%s, 
		%s, 
		id_factura, 
		'FA', 
		subtotal_factura, 
		subtotal_descuento, 
		total_cuenta_pagar, 
		id_modulo, 
		monto_exonerado, 
		monto_exento, 
		%s, 
		%s, 
		%s,
		%s, 
		%s
	FROM cp_factura
	WHERE id_factura = %s;",
		valTpDato($frmDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaProveedor'])), "date"),
		valTpDato($frmDcto['txtObservacionNotaCredito'], "text"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($frmDcto['lstAplicaLibro'], "boolean"),
		valTpDato($numeroActual, "text"),
		valTpDato($frmDcto['txtNumeroControl'], "text"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaRegistroNotaCredito'])), "date"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertDevolSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	$arrayIdDctoContabilidad[] = array($idNotaCredito,$idModulo,"NOTA CREDITO CXP");

	//INSERTA EL MOVIMIENTO
	$insertMovSQL = sprintf("INSERT INTO ga_movimiento (id_tipo_clave_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	SELECT 
		%s, 
		%s, 
		%s, 
		%s, 
		%s, 
		id_cliente_proveedor, 
		tipo_costo, 
		NOW(), 
		%s, 
		credito
	FROM ga_movimiento
	WHERE id_documento = %s 
	AND id_tipo_clave_movimiento = 1;",
		valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaRegistroNotaCredito'])), "date"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");	
	$Result1 = mysql_query($insertMovSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimiento = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DEL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO ga_movimiento_detalle (id_movimiento, id_articulo, cantidad, precio, costo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
	SELECT 
		%s,
		mov_det.id_articulo,
		mov_det.cantidad,
		mov_det.precio,
		mov_det.costo,
		mov_det.porcentaje_descuento,
		mov_det.subtotal_descuento,
		mov_det.tipo_costo,
		mov_det.promocion,
		mov_det.id_moneda_costo,
		mov_det.id_moneda_costo_cambio
	FROM ga_movimiento mov
		INNER JOIN ga_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
	WHERE mov.id_documento = %s 
	AND mov.id_tipo_clave_movimiento = 1;",
		valTpDato($idMovimiento, "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	//REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
	$insertSQL = sprintf("INSERT INTO ga_kardex (id_documento, id_articulo, tipo_movimiento, cantidad, id_clave_movimiento, estado, fecha_movimiento, observacion, hora_movimiento)
	SELECT 
		ga_movimiento.id_documento,
		ga_movimiento_detalle.id_articulo, 
		%s,  
		ga_movimiento_detalle.cantidad, 
		%s,
		%s,
		NOW(),
		%s, 
		SYSDATE()
	FROM ga_movimiento_detalle
		INNER JOIN ga_movimiento ON ga_movimiento.id_movimiento = ga_movimiento_detalle.id_movimiento
		INNER JOIN ga_kardex ON ga_kardex.id_documento = ga_movimiento.id_documento
	WHERE ga_movimiento.id_documento = %s AND id_tipo_clave_movimiento = 1 
	GROUP BY ga_movimiento_detalle.id_articulo;",
		valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
		valTpDato($frmDcto['txtObservacionNotaCredito'], "text"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");

	//BUSCA LOS KARDEX DEL DOCUMENTO
	$query = sprintf("SELECT * 
	FROM ga_kardex
	WHERE id_documento = %s
	AND tipo_movimiento = %s
	AND id_clave_movimiento = %s
	#AND tipo_documento_movimiento = %s;",
		valTpDato($idFacturaCompra, "int"),//idNotaCredito modificacdo 
		valTpDato($frmDcto['hhdTipoClaveFactura'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($frmDcto['hddIdClaveMovimiento'], "int"),
		valTpDato(2, "int")); // 1 = Vale Entrada / Salida, 2 = Nota Credito

	$rs = mysql_query($query);
	if (!$rs) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
 	while ($row = mysql_fetch_assoc($rs)) {
		$idArticulo = $row['id_articulo'];
		$idCasilla = $row['id_casilla'];
		$cantPedida = $row['cantidad'];
		$costoUnitario = $row['costo'];
		$precioUnitario = $row['precio'];
	
		$queryArtEmp = sprintf("SELECT
		ga_articulos.codigo_articulo,
		(((ifnull(ga_articulos_almacen.cantidad_entrada,0) -
		   ifnull(ga_articulos_almacen.cantidad_salida,0)) -
		   ifnull(ga_articulos_almacen.cantidad_reservada,0))-
		   ifnull(ga_articulos_almacen.cantidad_espera,0)) AS cantidad_disponible_logica
		FROM ga_articulos
			INNER JOIN ga_articulos_almacen on ga_articulos_almacen.id_articulo = ga_articulos.id_articulo
		WHERE ga_articulos.id_articulo = %s ",
			valTpDato($idArticulo, "int"));
		$rsArtEmp = mysql_query($queryArtEmp);
		if (!$rsArtEmp) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
		
		if (doubleval($rowArtEmp['cantidad_disponible_logica'] - $cantPedida) >= 0) {

			// BUSCA EL ULTIMO COSTO DEL ARTICULO
			/*$queryCostoArt = sprintf("SELECT * FROM ga_articulos_costos WHERE id_articulo = %s ORDER BY fecha_registro DESC LIMIT 1;",
				valTpDato($idArticulo, "int"));
		$objResponse->alert("BUSCA EL ULTIMO COSTO DEL ARTICULO \n".$queryCostoArt);
			$rsCostoArt = mysql_query($queryCostoArt);
			if (!$rsCostoArt) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
			
			$costoUnitarioKardex = ($rowConfig12['valor'] == 1) ? $costoUnitario : round($rowCostoArt['costo_promedio'],3);
			$precioUnitario = $costoUnitarioKardex;
			$costoCargo = ($rowConfig12['valor'] == 1) ? $costoCargo : 0;
			$porcentajeDescuento = ($rowConfig12['valor'] == 1) ? $porcentajeDescuento : 0;
			$subtotalDescuento = ($rowConfig12['valor'] == 1) ? $subtotalDescuento : 0;
			
			// MODIFICA EL MOVIMIENTO DEL ARTICULO
			$updateSQL = sprintf("UPDATE iv_kardex SET
				precio = %s,
				costo = %s,
		costo_cargo = %s,
		porcentaje_descuento = %s,
		subtotal_descuento = %s
			WHERE id_kardex = %s;",
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitarioKardex, "real_inglesa"),
				valTpDato($costoCargo, "real_inglesa"),
				valTpDato($porcentajeDescuento, "real_inglesa"),
				valTpDato($subtotalDescuento, "real_inglesa"),
				valTpDato($row['id_kardex'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");*/
			
			//MODIFICA EL MOVIMIENTO DEL ARTICULO
			$updateSQL = sprintf("UPDATE ga_articulos_almacen SET
				cantidad_entrada = %s
			WHERE id_articulo = %s;",
				valTpDato(doubleval($rowArtEmp['cantidad_disponible_logica'] - $cantPedida), "real_inglesa"),
				valTpDato($idArticulo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");		
		} else {
			$contSinDisponibilidad++;
			
			$msjArticulo .= ($msjArticulo != "") ? "": "El(Los) registro(s):\n";
			$msjArticulo .= ($contSinDisponibilidad % 4 == 1) ? "\n" : "";
			
			$msjArticulo .= str_pad("(".elimCaracter($rowArtEmp['codigo_articulo'],";").")", 30, " ", STR_PAD_RIGHT);
		}
	}
	
	if ($contSinDisponibilidad > 0) {
		$msjArticulo .= "\n\nno posee(n) disponible la cantidad suficiente";
		errorGuardarDcto($objResponse); 
		return $objResponse->alert(utf8_encode($msjArticulo));
	}
	
	//INSERTA LOS GASTOS DEL PEDIDO
	$insertSQL = sprintf("INSERT INTO cp_notacredito_gastos (id_notacredito, id_gastos_notacredito, tipo_gasto_notacredito, porcentaje_monto, monto_gasto_notacredito, estatus_iva_notacredito, id_iva_notacredito, iva_notacredito, id_modo_gasto, afecta_documento, id_condicion_gasto)
	SELECT 
		%s,
		id_gasto,
		tipo,
		porcentaje_monto,
		monto,
		estatus_iva,
		id_iva,
		iva,
		id_modo_gasto,
		afecta_documento,
		id_condicion_gasto
	FROM cp_factura_gasto
	WHERE id_factura = %s;",
		valTpDato($idNotaCredito, "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	$insertSQL = sprintf("INSERT INTO cp_notacredito_iva (id_notacredito, baseimponible_notacredito, subtotal_iva_notacredito, id_iva_notacredito, iva_notacredito, lujo)
	SELECT 
		%s,
		base_imponible,
		subtotal_iva,
		id_iva,
		iva,
		lujo
	FROM cp_factura_iva
	WHERE id_factura = %s;",
		valTpDato($idNotaCredito, "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	//REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaRegistroNotaCredito'])), "date"),
		valTpDato(3, "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// CREACION DE LA RETENCION DEL IMPUESTO
	// BUSCA EL COMPROBANTE DE RETENCION DE LA FACTURA DE COMPRA A DEVOLVER
	$queryRetencionDetalle = sprintf("SELECT
		retencion.idRetencionCabezera,
		retencion.numeroComprobante,
		retencion.fechaComprobante,
		SUM(retencion_det.IvaRetenido) AS IvaRetenido
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.idFactura = %s
	GROUP BY retencion_det.idFactura;",
		valTpDato($idFacturaCompra, "int"));
	$rsRetencionDetalle = mysql_query($queryRetencionDetalle);
	if (!$rsRetencionDetalle) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsRetencionDetalle = mysql_num_rows($rsRetencionDetalle);
	$rowRetencionDetalle = mysql_fetch_assoc($rsRetencionDetalle);
	
	// VERIFICA QUE LA DEVOLUCION PERTENEZCA AL MISMO PERIODO FISCAL DE LA FACTURA DE COMPRA
	if ($totalRowsRetencionDetalle > 0
	&& ((date("d", strtotime($rowRetencionDetalle['fechaComprobante'])) <= 15 && date("d",strtotime($frmDcto['txtFechaRegistroNotaCredito'])) <= 15)
		|| (date("d", strtotime($rowRetencionDetalle['fechaComprobante'])) > 15 && date("d",strtotime($frmDcto['txtFechaRegistroNotaCredito'])) > 15))
	&& date("m-Y", strtotime($rowRetencionDetalle['fechaComprobante'])) == date("m-Y",strtotime($frmDcto['txtFechaRegistroNotaCredito']))) {
		$idRetencionCabezera = $rowRetencionDetalle['idRetencionCabezera'];
		$ivaRetenido = $rowRetencionDetalle['IvaRetenido'];
		
		// INSERTA EL DETALLE DE LA RETENCION
		$insertSQL = sprintf("INSERT INTO cp_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, id_nota_cargo, id_nota_credito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
		SELECT
			retencion.idRetencionCabezera,
			%s,
			retencion_det.idFactura,
			retencion_det.numeroControlFactura,
			NULL,
			%s,
			%s,
			retencion_det.idFactura,
			(-1) * retencion_det.totalCompraIncluyendoIva,
			(-1) * retencion_det.comprasSinIva,
			(-1) * retencion_det.baseImponible,
			(-1) * retencion_det.porcentajeAlicuota,
			(-1) * retencion_det.impuestoIva,
			(-1) * retencion_det.IvaRetenido,
			(-1) * retencion_det.porcentajeRetencion
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = %s;",
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaProveedor'])), "date"),
			valTpDato($idNotaCredito, "int"),
			valTpDato("03", "text"), // 01 = FACTURA, 02 = NOTA DEBITO, 03 = NOTA CREDITO
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA EL PAGO DEBIDO A LA RETENCION
		$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idNotaCredito, "int"),
			valTpDato("NC", "text"),
			valTpDato("RETENCION", "text"),
			valTpDato($idRetencionCabezera, "int"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaRegistroNotaCredito'])), "date"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($rowRetencionDetalle['numeroComprobante'], "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato($ivaRetenido, "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	switch ($idModoCompra) {
		case 1 : // 1 = Nacional
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
			$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
				nota_cred.saldo_notacredito = (IFNULL(nota_cred.subtotal_notacredito, 0)
									- IFNULL(nota_cred.subtotal_descuento, 0)
									+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
											FROM cp_notacredito_gastos nota_cred_gasto
											WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito
												AND nota_cred_gasto.id_modo_gasto IN (1,3)
												AND nota_cred_gasto.afecta_documento IN (1)), 0)
									+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
											FROM cp_notacredito_iva nota_cred_iva
											WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
									- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
													OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
												AND pago_dcto.estatus = 1), 0))
			WHERE nota_cred.id_notacredito = %s
				AND nota_cred.estado_notacredito IN (1,2);",
				valTpDato($idNotaCredito, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			break;
		case 2 : // 2 = Importacion
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
			$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
				nota_cred.saldo_notacredito = (IFNULL((SELECT 
													SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
												FROM cp_factura_detalle a
													INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
													INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
												WHERE a.id_factura = %s), 0)
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_modo_gasto IN (1)
													AND fact_comp_gasto.afecta_documento IN (1)
													AND fact_comp_gasto.id_factura = %s), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
														OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
													AND pago_dcto.estatus = 1), 0))
			WHERE nota_cred.estado_notacredito IN (1,2)
				AND nota_cred.id_notacredito = %s
				AND (SELECT COUNT(fact_comp_det.id_factura)
					FROM cp_factura_detalle fact_comp_det
					WHERE fact_comp_det.id_factura = %s) > 0;",
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idNotaCredito, "int"),
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			break;
	}
	
	// BUSCA LOS DATOS DE LA NOTA DE CREDITO
	$queryNotaCredito = sprintf("SELECT * FROM cp_notacredito nota_cred
	WHERE nota_cred.id_notacredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$rsNotaCredito = mysql_query($queryNotaCredito);
	if (!$rsNotaCredito) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsNotaCredito = mysql_num_rows($rsNotaCredito);
	$rowNotaCredito = mysql_fetch_assoc($rsNotaCredito);
	
	if ($rowFactura['estatus_factura'] == 0 || $rowFactura['estatus_factura'] == 2) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
		if (doubleval($rowFactura['saldo_factura']) >= doubleval($rowNotaCredito['saldo_notacredito'])) {
			// INSERTA EL PAGO DEBIDO A LA NOTA DE CREDITO
			$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFacturaCompra, "int"),
				valTpDato("FA", "text"),
				valTpDato("NC", "text"),
				valTpDato($idNotaCredito, "int"),
				valTpDato("NOW()", "campo"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowNotaCredito['numero_nota_credito'], "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato($rowNotaCredito['saldo_notacredito'], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert($insertSQL."\n".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else if (doubleval($rowFactura['saldo_factura']) < doubleval($rowNotaCredito['saldo_notacredito'])) {
			// INSERTA EL PAGO DEBIDO A LA NOTA DE CREDITO
			$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFacturaCompra, "int"),
				valTpDato("FA", "text"),
				valTpDato("NC", "text"),
				valTpDato($idNotaCredito, "int"),
				valTpDato("NOW()", "campo"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowNotaCredito['numero_nota_credito'], "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato($rowFactura['saldo_factura'], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	switch ($idModoCompra) {
	case 1 : // 1 = Nacional
		// ACTUALIZA EL SALDO DE LA FACTURA
		$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
			fact_comp.saldo_factura = (IFNULL(fact_comp.subtotal_factura, 0)
								- IFNULL(fact_comp.subtotal_descuento, 0)
								+ IFNULL((SELECT SUM(fact_comp_gasto.monto) AS total_gasto
										FROM cp_factura_gasto fact_comp_gasto
										WHERE fact_comp_gasto.id_factura = fact_comp.id_factura
											AND fact_comp_gasto.id_modo_gasto IN (1,3)
											AND fact_comp_gasto.afecta_documento IN (1)), 0)
								+ IFNULL((SELECT SUM(fact_comp_iva.subtotal_iva) AS total_iva
										FROM cp_factura_iva fact_comp_iva
										WHERE fact_comp_iva.id_factura = fact_comp.id_factura), 0)
								- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
										WHERE pago_dcto.id_documento_pago = fact_comp.id_factura
											AND pago_dcto.tipo_documento_pago LIKE 'FA'
											AND pago_dcto.estatus = 1), 0))
		WHERE fact_comp.id_factura = %s
			AND fact_comp.estatus_factura NOT IN (1);",
			valTpDato($idFacturaCompra, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
		// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
			fact_comp.estatus_factura = (CASE
								WHEN (saldo_factura = 0) THEN
									1
								WHEN (saldo_factura > 0 AND saldo_factura < (IFNULL(fact_comp.subtotal_factura, 0)
													- IFNULL(fact_comp.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(fact_comp_gasto.monto) AS total_gasto
															FROM cp_factura_gasto fact_comp_gasto
															WHERE fact_comp_gasto.id_factura = fact_comp.id_factura
																AND fact_comp_gasto.id_modo_gasto IN (1,3)
																AND fact_comp_gasto.afecta_documento IN (1)), 0)
													+ IFNULL((SELECT SUM(fact_comp_iva.subtotal_iva) AS total_iva
															FROM cp_factura_iva fact_comp_iva
															WHERE fact_comp_iva.id_factura = fact_comp.id_factura), 0)
													- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
															WHERE pago_dcto.id_documento_pago = fact_comp.id_factura
																AND pago_dcto.tipo_documento_pago LIKE 'FA'), 0))) THEN
									2
								ELSE
									0
							END)
		WHERE fact_comp.id_factura = %s;",
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
		$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
			nota_cred.saldo_notacredito = (IFNULL(nota_cred.subtotal_notacredito, 0)
								- IFNULL(nota_cred.subtotal_descuento, 0)
								+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
										FROM cp_notacredito_gastos nota_cred_gasto
										WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito
											AND nota_cred_gasto.id_modo_gasto IN (1,3)
											AND nota_cred_gasto.afecta_documento IN (1)), 0)
								+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
										FROM cp_notacredito_iva nota_cred_iva
										WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
								- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
										WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
												OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
											AND pago_dcto.estatus = 1), 0))
		WHERE nota_cred.id_notacredito = %s
			AND nota_cred.estado_notacredito IN (1,2);",
			valTpDato($idNotaCredito, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

		// ACTUALIZA EL ESTADO DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
		$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
			nota_cred.estado_notacredito = (CASE
									WHEN (saldo_notacredito = 0) THEN
										3
									WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL(nota_cred.subtotal_notacredito, 0)
										- IFNULL(nota_cred.subtotal_descuento, 0)
										+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
												FROM cp_notacredito_gastos nota_cred_gasto
												WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito
													AND nota_cred_gasto.id_modo_gasto IN (1,3)
													AND nota_cred_gasto.afecta_documento IN (1)), 0)
										+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
												FROM cp_notacredito_iva nota_cred_iva
												WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
														OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
													AND pago_dcto.estatus = 1), 0))) THEN
										2
									WHEN (saldo_notacredito = (IFNULL(nota_cred.subtotal_notacredito, 0)
										- IFNULL(nota_cred.subtotal_descuento, 0)
										+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
												FROM cp_notacredito_gastos nota_cred_gasto
												WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito
													AND nota_cred_gasto.id_modo_gasto IN (1,3)
													AND nota_cred_gasto.afecta_documento IN (1)), 0)
										+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
												FROM cp_notacredito_iva nota_cred_iva
												WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
														OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
													AND pago_dcto.estatus = 1), 0))) THEN
										1
									ELSE
										0
								END)
		WHERE nota_cred.id_notacredito = %s;",
			valTpDato($idNotaCredito, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		break;
	case 2 : // 2 = Importacion
		// ACTUALIZA EL SALDO DE LA FACTURA DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
		$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
			fact_comp.saldo_factura = (IFNULL((SELECT 
													SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
												FROM cp_factura_detalle a
													INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
													INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
												WHERE a.id_factura = fact_comp.id_factura), 0)
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_modo_gasto IN (1)
													AND fact_comp_gasto.afecta_documento IN (1)
													AND fact_comp_gasto.id_factura = fact_comp.id_factura), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE pago_dcto.id_documento_pago = fact_comp.id_factura
													AND pago_dcto.tipo_documento_pago LIKE 'FA'
													AND pago_dcto.estatus = 1), 0))
		WHERE fact_comp.id_modo_compra IN (2)
			AND fact_comp.estatus_factura IN (0,2)
			AND fact_comp.id_factura = %s
			AND (SELECT COUNT(fact_comp_det.id_factura)
				FROM cp_factura_detalle fact_comp_det
				WHERE fact_comp_det.id_factura = fact_comp.id_factura) > 0;",
			valTpDato($idFacturaCompra, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
		// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
			fact_comp.estatus_factura = (CASE
								WHEN (saldo_factura = 0) THEN
									1
								WHEN (saldo_factura > 0 AND saldo_factura < (IFNULL((SELECT 
																SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
															FROM cp_factura_detalle a
																INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
																INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
															WHERE a.id_factura = fact_comp.id_factura), 0)
													+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
															FROM cp_factura_gasto fact_comp_gasto
															WHERE fact_comp_gasto.id_modo_gasto IN (1)
																AND fact_comp_gasto.afecta_documento IN (1)
																AND fact_comp_gasto.id_factura = fact_comp.id_factura), 0)
													- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
															WHERE pago_dcto.id_documento_pago = fact_comp.id_factura
																AND pago_dcto.tipo_documento_pago LIKE 'FA'), 0))) THEN
									2
								ELSE
									0
							END)
		WHERE fact_comp.id_factura = %s;",
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
		$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
			nota_cred.saldo_notacredito = (IFNULL((SELECT 
												SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
											FROM cp_factura_detalle a
												INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
												INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
											WHERE a.id_factura = %s), 0)
									+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
											FROM cp_factura_gasto fact_comp_gasto
											WHERE fact_comp_gasto.id_modo_gasto IN (1)
												AND fact_comp_gasto.afecta_documento IN (1)
												AND fact_comp_gasto.id_factura = %s), 0)
									- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
													OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
												AND pago_dcto.estatus = 1), 0))
		WHERE nota_cred.estado_notacredito IN (1,2)
			AND nota_cred.id_notacredito = %s
			AND (SELECT COUNT(fact_comp_det.id_factura)
				FROM cp_factura_detalle fact_comp_det
				WHERE fact_comp_det.id_factura = %s) > 0;",
			valTpDato($idFacturaCompra, "int"),
			valTpDato($idFacturaCompra, "int"),
			valTpDato($idNotaCredito, "int"),
			valTpDato($idFacturaCompra, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

		// ACTUALIZA EL ESTADO DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
		$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
			nota_cred.estado_notacredito = (CASE
									WHEN (saldo_notacredito = 0) THEN
										3
									WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL((SELECT 
													SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
												FROM cp_factura_detalle a
													INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
													INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
												WHERE a.id_factura = nota_cred.id_documento), 0)
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_modo_gasto IN (1)
													AND fact_comp_gasto.afecta_documento IN (1)
													AND fact_comp_gasto.id_factura = nota_cred.id_documento), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
														OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
													AND pago_dcto.estatus = 1), 0))) THEN
										2
									WHEN (saldo_notacredito = (IFNULL((SELECT 
													SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
												FROM cp_factura_detalle a
													INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
													INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
												WHERE a.id_factura = nota_cred.id_documento), 0)
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_modo_gasto IN (1)
													AND fact_comp_gasto.afecta_documento IN (1)
													AND fact_comp_gasto.id_factura = nota_cred.id_documento), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
														OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
													AND pago_dcto.estatus = 1), 0))) THEN
										1
									ELSE
										0
								END)
		WHERE nota_cred.id_notacredito = %s;",
			valTpDato($idNotaCredito, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		break;
	}
	
	// ACTUALIZA EL ESTATUS DE LA FACTURA DE COMPRA
	$updateSQL = sprintf("UPDATE cp_factura SET
		aplica_libros = %s,
		activa = %s
	WHERE id_factura = %s;",
		valTpDato($frmDcto['lstAplicaLibro'], "boolean"),
		valTpDato($frmDcto['lstActivo'], "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");	
	
	errorGuardarDcto($objResponse); 
	$objResponse->alert("Devolución de registro de compra guardado con éxito");	
	$objResponse->script("verVentana('reportes/ga_nota_credito_pdf.php?valBusq=".$idNotaCredito."', 960, 550);");	
	$objResponse->script("byId('btnCancelarDcto').click();");

	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA CREDITO CXP") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasCreditoCpRe")) { generarNotasCreditoCpRe($idNotaCredito,"",""); } break;
					case 1 : if (function_exists("generarNotasCreditoCpSe")) { generarNotasCreditoCpSe($idNotaCredito,"",""); } break;
					case 2 : if (function_exists("generarNotasCreditoCpVe")) { generarNotasCreditoCpVe($idNotaCredito,"",""); } break;
					case 3 : if (function_exists("generarNotasCreditoCpAd")) { generarNotasCreditoCpAd($idNotaCredito,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->script("byId('btnBuscar').click();");

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
		$sqlBusq .= $cond.sprintf("(pago_contado = 1 OR pago_credito = 1)");
		
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1 AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
//$objResponse->alert($query);
	$rs = mysql_query($query);

	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);

		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function listaRegistroCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(" activa IS NOT NULL ");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(estatus_orden_compra = 3
	OR id_solicitud_compra IS NULL)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_origen BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_control_factura LIKE %s
		OR numero_factura_proveedor LIKE %s
		OR id_solicitud_compra LIKE %s
		OR numero_solicitud LIKE %s
		OR id_proveedor LIKE %s
		OR rif_proveedor LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_ga_facturas_compra.*,
		#CUNETA LA CANTIDAD ITEMS DE LA FACTURA
		(SELECT COUNT(fact_comp_det.id_factura) AS items
		FROM cp_factura_detalle fact_comp_det
		WHERE (fact_comp_det.id_factura = vw_ga_facturas_compra.id_factura)) AS items,
		
		#CALCULAR EL TOTAL DE LA FACTURA
		(CASE
			WHEN (vw_ga_facturas_compra.id_factura IS NULL) THEN
				(((vw_ga_facturas_compra.subtotal_pedido - vw_ga_facturas_compra.subtotal_descuento_pedido)
				+
				IFNULL((SELECT SUM(ord_gasto.monto) AS total_gasto
					FROM ga_orden_compra_gasto ord_gasto
					WHERE (ord_gasto.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)), 0))
					
				+
				IFNULL((SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM ga_orden_compra_iva ped_iva
					WHERE (ped_iva.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)), 0))
			WHEN (vw_ga_facturas_compra.id_factura IS NOT NULL) THEN
				(((vw_ga_facturas_compra.subtotal_factura - vw_ga_facturas_compra.subtotal_descuento)
				+
				IFNULL((SELECT SUM(fac_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fac_gasto
					WHERE (fac_gasto.id_factura = vw_ga_facturas_compra.id_factura)), 0))
				+
				IFNULL((SELECT SUM(fac_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva fac_iva
					WHERE (fac_iva.id_factura = vw_ga_facturas_compra.id_factura)), 0))
		END) AS total,
		
		(SELECT retencion.idRetencionCabezera FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		 WHERE retencion_det.idFactura = vw_ga_facturas_compra.id_factura 
		 	AND vw_ga_facturas_compra.id_modulo = 3 LIMIT 1) AS idRetencionCabezera
			
	FROM vw_ga_facturas_compra  
	#INNER JOIN cp_factura_detalle ON cp_factura_detalle.id_factura = vw_ga_facturas_compra.id_factura
	%s HAVING items != 0", $sqlBusq); //AND activa IS NOT NULL en cp_factura
//$objResponse->alert($query);
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "5%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "5%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "5%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "27%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "27%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Observacion");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "5%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		/*switch($row['estatus_orden_compra']) {
			case "" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Factura CxP\"/>"; break;
			default : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Factura Compras\"/>"; break;
		}
		
		switch($row['estatus_orden_compra']) {
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Convertido a Orden\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/aprob_control_calidad.png\" title=\"Compra Registrada\"/>"; break;
			default : ""; break;
		}*/
		
		$imgEstatusPedido = "<img src=\"../img/iconos/aprob_control_calidad.png\" title=\"Compra Registrada\"/>";	
 		$imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Factura Compras\"/>";
			
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_control_factura']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td>".htmlentities($row["id_proveedor"].".- ".$row['nombre']).$observacion."</td>";
			$htmlTb .= "<td>".ucfirst(strtolower(htmlentities($row['observacion_factura'])))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'],2,".",",")."</td>";
			$htmlTb .= "<td>";
			/*if ($row['idRetencionCabezera'] > 0) {
			$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" target=\"_self\"><img src=\"../img/iconos/ico_print.png\" title=\"Imprimir Comprobante de Retención\"/></a>",
				$row['idRetencionCabezera']);
			}*/
			$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDevolver%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblNotaCredito', '%s', 1)\"><img class=\"puntero\" src=\"../img/iconos/arrow_rotate_clockwise.png\" title=\"Devolver Compra\"/></a>",
				$contFila,$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/ga_registro_compra_pdf.php?valBusq=%s', 960, 550);\" target=\"_self\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro Compra PDF\"/></a>",
					$row['id_factura']);
			$htmlTb .= "</td>";
			
			// MODIFICADO ERNESTO
			/*$sPar = "idobject=".$row['id_factura'];
			$sPar .= "&ct=01";
			$sPar .= "&dt=01";
			$sPar .= "&cc=01";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?".$sPar."', 960, 550);\" target=\"_self\"><img src=\"../img/iconos/new_window.png\" title=\"Ver Movimiento Contable\"/></a>");
			$htmlTb .= "</td>";*/
			// MODIFICADO ERNESTO
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaRegistroCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaDetalleFactura($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_factura = %s", valTpDato($valBusq, "int"));	
	
	$query = sprintf("SELECT id_factura_detalle, id_factura, cp_factura_detalle.id_articulo, codigo_articulo, descripcion, cantidad, pendiente, devuelto, precio_unitario, iva, (cantidad * precio_unitario) AS total_articulo
	FROM cp_factura_detalle
		INNER JOIN ga_articulos ON ga_articulos.id_articulo = cp_factura_detalle.id_articulo %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "5%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro");
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "10%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Codigo. Art");
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "30%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion. Art");
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "5%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant");
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "5%", $pageNum, "pendiente", $campOrd, $tpOrd, $valBusq, $maxRows, "Pen");
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "5%", $pageNum, "devuelto", $campOrd, $tpOrd, $valBusq, $maxRows, "Dev");
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "10%", $pageNum, "precio_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unit.");
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "5%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaDetalleFactura", "10%", $pageNum, "total_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Art");
	$htmlTh .= "</tr>";
	
	$num = 1;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td align=\"center\">%s</td>",$num ++);
			$htmlTb .= sprintf("<td align=\"center\"><input type=\"text\" id=\"txtCodArt%s\" name=\"txtCodArt%s\" readonly=\"readonly\" value =\"%s\" class=\"inputSinFondo\" style=\"text-align:center\" size=\"10\"/></td>",
				$row['id_factura_detalle'],$row['id_factura_detalle'],utf8_encode($row['codigo_articulo']));
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",utf8_encode($row['descripcion']));
			$htmlTb .= sprintf("<td align=\"left\"><input type=\"text\" id=\"txtCantArt%s\" name=\"txtCantArt%s\" readonly=\"readonly\" value =\"%s\" class=\"inputSinFondo\" style=\"text-align:center\" size=\"5\"/></td>",
				$row['id_factura_detalle'],$row['id_factura_detalle'],number_format($row['cantidad'], 2, ".", ","));
			$htmlTb .= sprintf("<td align=\"left\"><input type=\"text\" id=\"txtPendArt%s\" name=\"txtPendArt%s\" readonly=\"readonly\" value =\"%s\" class=\"inputSinFondo\" style=\"text-align:center\" size=\"5\"/></td>",
				$row['id_factura_detalle'],$row['id_factura_detalle'],number_format($row['pendiente'], 2, ".", ","));
			$htmlTb .= sprintf("<td align=\"left\"><input type=\"text\" id=\"txtDevuelArt%s\" name=\"txtDevuelArt%s\" readonly=\"readonly\" value =\"%s\" class=\"inputSinFondo\" style=\"text-align:center\" size=\"5\"/></td>",
				$row['id_factura_detalle'],$row['id_factura_detalle'],number_format($row['devuelto'], 2, ".", ","));
			$htmlTb .= sprintf("<td align=\"left\"><input type=\"text\" id=\"txtCostUnit%s\" name=\"txtCostUnit%s\" readonly=\"readonly\" value =\"%s\" class=\"inputSinFondo\" size=\"10\"/></td>",
				$row['id_factura_detalle'],$row['id_factura_detalle'],number_format($row['precio_unitario'], 2, ".", ","));
			$htmlTb .= sprintf("<td align=\"left\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" value =\"%s\" class=\"inputSinFondo\" style=\"text-align:center\" size=\"5\"/></td>",
				$row['id_factura_detalle'],$row['id_factura_detalle'],number_format($row['iva'], 2, ".", ","));
			$htmlTb .= sprintf("<td align=\"left\"><input type=\"text\" id=\"txtTotalArt%s\" name=\"txtTotalArt%s\" readonly=\"readonly\" value =\"%s\" class=\"inputSinFondo\" size=\"10\"/></td>",
				$row['id_factura_detalle'],$row['id_factura_detalle'],number_format($row['total_articulo'], 2, ".", ","));
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDetalleFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDetalleFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaDetalleFactura(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDetalleFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDetalleFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaDetalleFactura","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"formNotaCredito");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"listaRegistroCompra");
$xajax->register(XAJAX_FUNCTION,"listaDetalleFactura");
$xajax->register(XAJAX_FUNCTION,"reconversion");

function errorGuardarDcto($objResponse) {
	$objResponse->script("
	byId('btnGuardarDcto').disabled = false;
	byId('btnCancelarDcto').disabled = false;");
}
?>