<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");

mysql_query("START TRANSACTION;");

// VERIFICA SI TIENE IMPUESTO DE VENTA
$queryIva = sprintf("SELECT 
	cxc_fact.idFactura,
	cxc_fact.baseImponible,
	cxc_fact.porcentajeIvaFactura,
	cxc_fact.calculoIvaFactura
FROM cj_cc_encabezadofactura cxc_fact
WHERE cxc_fact.calculoIvaFactura > 0
	AND cxc_fact.idFactura NOT IN (SELECT id_factura FROM cj_cc_factura_iva);",
	valTpDato($idDocumento,"int"));
$rsIva = mysql_query($queryIva, $conex);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsIva = mysql_num_rows($rsIva);
while ($rowIva = mysql_fetch_assoc($rsIva)) {
	$idDocumento = $rowIva['idFactura'];
	
	// VERIFICA SI TIENE DETALLE DE IMPUESTO
	$queryFactIva = sprintf("SELECT * FROM cj_cc_factura_iva
	WHERE id_factura = %s
		AND id_iva IN (SELECT idIva FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1);",
		valTpDato($idDocumento,"int"));
	$rsFactIva = mysql_query($queryFactIva, $conex);
	if (!$rsFactIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFactIva = mysql_num_rows($rsFactIva);
	
	if ($totalRowsIva > 0 && !($totalRowsFactIva > 0)) {
		// INSERTA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
		SELECT %s, %s, %s, idIva, %s, IF (iva.tipo IN (3,2), 1, NULL) FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1;",
			valTpDato($idDocumento, "int"),
			valTpDato($rowIva['baseImponible'], "real_inglesa"),
			valTpDato($rowIva['calculoIvaFactura'],"real_inglesa"),
			valTpDato($rowIva['porcentajeIvaFactura'], "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
}

// VERIFICA SI TIENE IMPUESTO DE VENTA AL LUJO
$queryIva = sprintf("SELECT 
	cxc_fact.idFactura,
	cxc_fact.base_imponible_iva_lujo,
	cxc_fact.porcentajeIvaDeLujoFactura,
	cxc_fact.calculoIvaDeLujoFactura
FROM cj_cc_encabezadofactura cxc_fact
WHERE cxc_fact.calculoIvaDeLujoFactura > 0
	AND cxc_fact.idFactura NOT IN (SELECT id_factura FROM cj_cc_factura_iva);");
$rsIva = mysql_query($queryIva, $conex);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsIva = mysql_num_rows($rsIva);
while ($rowIva = mysql_fetch_assoc($rsIva)) {
	$idDocumento = $rowIva['idFactura'];
	
	// VERIFICA SI TIENE DETALLE DE IMPUESTO
	$queryFactIva = sprintf("SELECT * FROM cj_cc_factura_iva
	WHERE id_factura = %s
		AND id_iva IN (SELECT idIva FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1);",
		valTpDato($idDocumento,"int"));
	$rsFactIva = mysql_query($queryFactIva, $conex);
	if (!$rsFactIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsFactIva = mysql_num_rows($rsFactIva);
	
	if ($totalRowsIva > 0 && !($totalRowsFactIva > 0)) {
		// INSERTA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
		SELECT %s, %s, %s, idIva, %s, IF (iva.tipo IN (3,2), 1, NULL) FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1;",
			valTpDato($idDocumento, "int"),
			valTpDato($rowIva['base_imponible_iva_lujo'], "real_inglesa"),
			valTpDato($rowIva['calculoIvaDeLujoFactura'],"real_inglesa"),
			valTpDato($rowIva['porcentajeIvaDeLujoFactura'], "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
}


// VERIFICA SI TIENE IMPUESTO DE VENTA
$queryIva = sprintf("SELECT 
	cxc_nc.idNotaCredito,
	cxc_nc.baseimponibleNotaCredito,
	cxc_nc.porcentajeIvaNotaCredito,
	cxc_nc.ivaNotaCredito
FROM cj_cc_notacredito cxc_nc
WHERE cxc_nc.ivaNotaCredito > 0
	AND cxc_nc.idNotaCredito NOT IN (SELECT id_nota_credito FROM cj_cc_nota_credito_iva);");
$rsIva = mysql_query($queryIva, $conex);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsIva = mysql_num_rows($rsIva);
while ($rowIva = mysql_fetch_assoc($rsIva)) {
	$idDocumento = $rowIva['idNotaCredito'];
	
	// VERIFICA SI TIENE DETALLE DE IMPUESTO
	$queryNotaCredIva = sprintf("SELECT * FROM cj_cc_nota_credito_iva
	WHERE id_nota_credito = %s
		AND id_iva IN (SELECT idIva FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1);",
		valTpDato($idDocumento,"int"));
	$rsNotaCredIva = mysql_query($queryNotaCredIva, $conex);
	if (!$rsNotaCredIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsNotaCredIva = mysql_num_rows($rsNotaCredIva);
	
	if ($totalRowsIva > 0 && !($totalRowsNotaCredIva > 0)) {
		// INSERTA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_iva (id_nota_credito, base_imponible, subtotal_iva, id_iva, iva, lujo)
		SELECT %s, %s, %s, idIva, %s, IF (iva.tipo IN (3,2), 1, NULL) FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1;",
			valTpDato($idDocumento, "int"),
			valTpDato($rowIva['baseimponibleNotaCredito'], "real_inglesa"),
			valTpDato($rowIva['ivaNotaCredito'],"real_inglesa"),
			valTpDato($rowIva['porcentajeIvaNotaCredito'], "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
}

// VERIFICA SI TIENE IMPUESTO DE VENTA AL LUJO
$queryIva = sprintf("SELECT 
	cxc_nc.idNotaCredito,
	cxc_nc.base_imponible_iva_lujo,
	cxc_nc.porcentajeIvaDeLujoNotaCredito,
	cxc_nc.ivaLujoNotaCredito
FROM cj_cc_notacredito cxc_nc
WHERE cxc_nc.ivaLujoNotaCredito > 0
	AND cxc_nc.idNotaCredito NOT IN (SELECT id_nota_credito FROM cj_cc_nota_credito_iva);");
$rsIva = mysql_query($queryIva, $conex);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsIva = mysql_num_rows($rsIva);
while ($rowIva = mysql_fetch_assoc($rsIva)) {
	$idDocumento = $rowIva['idNotaCredito'];
	
	// VERIFICA SI TIENE DETALLE DE IMPUESTO
	$queryNotaCredIva = sprintf("SELECT * FROM cj_cc_nota_credito_iva
	WHERE id_nota_credito = %s
		AND id_iva IN (SELECT idIva FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1);",
		valTpDato($idDocumento,"int"));
	$rsNotaCredIva = mysql_query($queryNotaCredIva, $conex);
	if (!$rsNotaCredIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsNotaCredIva = mysql_num_rows($rsNotaCredIva);
	
	if ($totalRowsIva > 0 && !($totalRowsNotaCredIva > 0)) {
		// INSERTA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_iva (id_nota_credito, base_imponible, subtotal_iva, id_iva, iva, lujo)
		SELECT %s, %s, %s, idIva, %s, IF (iva.tipo IN (3,2), 1, NULL) FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1;",
			valTpDato($idDocumento, "int"),
			valTpDato($rowIva['base_imponible_iva_lujo'], "real_inglesa"),
			valTpDato($rowIva['ivaLujoNotaCredito'],"real_inglesa"),
			valTpDato($rowIva['porcentajeIvaDeLujoNotaCredito'], "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
}

// INSERTA EL DETALLE DE LOS IMPUESTOS
$insertSQL = sprintf("INSERT INTO iv_pedido_venta_detalle_impuesto (id_pedido_venta_detalle, id_impuesto, impuesto)
SELECT id_pedido_venta_detalle, id_iva, iva FROM iv_pedido_venta_detalle
WHERE iva > 0
	AND id_pedido_venta_detalle NOT IN (SELECT id_pedido_venta_detalle FROM iv_pedido_venta_detalle_impuesto)");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// INSERTA EL DETALLE DE LOS IMPUESTOS
$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_impuesto (id_factura_detalle, id_impuesto, impuesto)
SELECT id_factura_detalle, id_iva, iva FROM cj_cc_factura_detalle
WHERE iva > 0
	AND id_factura_detalle NOT IN (SELECT id_factura_detalle FROM cj_cc_factura_detalle_impuesto)");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// INSERTA EL DETALLE DE LOS IMPUESTOS
$insertSQL = sprintf("INSERT INTO iv_pedido_compra_detalle_impuesto (id_pedido_compra_detalle, id_impuesto, impuesto)
SELECT id_pedido_compra_detalle, id_iva, iva FROM iv_pedido_compra_detalle
WHERE iva > 0
	AND id_pedido_compra_detalle NOT IN (SELECT id_pedido_compra_detalle FROM iv_pedido_compra_detalle_impuesto)");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// INSERTA EL DETALLE DE LOS IMPUESTOS
$insertSQL = sprintf("INSERT INTO iv_pedido_compra_gasto_impuesto (id_pedido_compra_gasto, id_impuesto, impuesto)
SELECT id_pedido_compra_gasto, id_iva, iva FROM iv_pedido_compra_gasto
WHERE iva > 0
	AND id_pedido_compra_gasto NOT IN (SELECT id_pedido_compra_gasto FROM iv_pedido_compra_gasto_impuesto)");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// INSERTA EL DETALLE DE LOS IMPUESTOS
$insertSQL = sprintf("INSERT INTO cp_factura_detalle_impuesto (id_factura_detalle, id_impuesto, impuesto)
SELECT id_factura_detalle, id_iva, iva FROM cp_factura_detalle
WHERE iva > 0
	AND id_factura_detalle NOT IN (SELECT id_factura_detalle FROM cp_factura_detalle_impuesto)");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// INSERTA EL DETALLE DE LOS IMPUESTOS DE LOS GASTOS QUE NO LO TENIAN EN DICHA TABLA
$insertSQL = sprintf("INSERT INTO cp_factura_gasto_impuesto (id_factura_gasto, id_impuesto, impuesto)
SELECT id_factura_gasto, id_iva, iva FROM cp_factura_gasto
WHERE iva > 0
	AND id_factura_gasto NOT IN (SELECT id_factura_gasto FROM cp_factura_gasto_impuesto)");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// INSERTA EL DETALLE DE LOS IMPUESTOS DE LOS GASTOS QUE NO LO TENIAN EN DICHA TABLA
$insertSQL = sprintf("INSERT INTO cp_notacredito_gasto_impuesto (id_notacredito_gasto, id_impuesto, impuesto)
SELECT id_notacredito_gastos, id_iva_notacredito, iva_notacredito FROM cp_notacredito_gastos
WHERE iva_notacredito > 0
	AND id_notacredito_gastos NOT IN (SELECT id_notacredito_gasto FROM cp_notacredito_gasto_impuesto)");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// BUSCA LOS MOVIMIENTOS DE SALIDA
$query = sprintf("SELECT * FROM iv_movimiento mov
WHERE mov.id_tipo_movimiento IN (4)
	AND mov.tipo_documento_movimiento = 1
	AND mov.id_movimiento NOT IN (SELECT id_movimiento FROM iv_movimiento_detalle);");
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while ($row = mysql_fetch_assoc($rs)) {
	$queryDet = sprintf("SELECT mov_det.*
	FROM iv_kardex kardex
		INNER JOIN iv_movimiento_detalle mov_det ON (kardex.id_kardex = mov_det.id_kardex)
	WHERE kardex.id_documento = %s
		AND kardex.tipo_movimiento IN (%s)
		AND kardex.id_clave_movimiento = %s
		AND (kardex.tipo_documento_movimiento = %s
			OR kardex.tipo_documento_movimiento IS NULL AND %s IS NULL)
		AND kardex.fecha_movimiento = %s;",
		valTpDato($row['id_documento'],"int"),
		valTpDato($row['id_tipo_movimiento'],"int"),
		valTpDato($row['id_clave_movimiento'],"int"),
		valTpDato($row['tipo_documento_movimiento'],"int"),
		valTpDato($row['tipo_documento_movimiento'],"int"),
		valTpDato($row['fecha_captura'],"date"));
	$rsDet = mysql_query($queryDet, $conex);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDet = mysql_num_rows($rsDet);
	while ($rowDet = mysql_fetch_assoc($rsDet)) {
		// ACTUALIZA DETALLE AL MOVIMIENTO CORRECTO
		$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
			id_movimiento = %s
		WHERE id_movimiento_detalle = %s;",
			valTpDato($row['id_movimiento'], "int"),
			valTpDato($rowDet['id_movimiento_detalle'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
}


// ACTUALIZA LA CUENTA POR PAGAR DE LA FACTURA
$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
	cxp_fact.total_cuenta_pagar = (CASE cxp_fact.id_modo_compra
									WHEN 1 THEN
										(IFNULL(cxp_fact.subtotal_factura, 0)
											- IFNULL(cxp_fact.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
														AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
											+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva FROM cp_factura_iva cxp_fact_iva
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
										END)
								END)
WHERE cxp_fact.total_cuenta_pagar = 0;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

// ACTUALIZA LA CUENTA POR PAGAR DE LA NOTA DE CARGO
$updateSQL = sprintf("UPDATE cp_notadecargo cxp_nd SET
	cxp_nd.total_cuenta_pagar = (IFNULL(cxp_nd.subtotal_notacargo, 0)
									- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
									+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) AS total_gasto FROM cp_notacargo_gastos cxp_nd_gasto
											WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
												AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)
									+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) AS total_iva FROM cp_notacargo_iva cxp_nd_iva
											WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0))
WHERE cxp_nd.total_cuenta_pagar = 0;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

// ACTUALIZA LA CUENTA POR PAGAR DE LA NOTA DE CREDITO
$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
	cxp_nc.total_cuenta_pagar = (CASE 
									WHEN (cxp_nc.id_documento = 0 OR cxp_nc.id_documento IS NULL OR cxp_nc.tipo_documento LIKE 'NC') THEN
										(IFNULL(cxp_nc.subtotal_notacredito, 0)
										- IFNULL(cxp_nc.subtotal_descuento, 0)
										+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
												WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
													AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
										+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
												WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))
								END)
WHERE (cxp_nc.id_documento = 0 OR cxp_nc.id_documento IS NULL OR cxp_nc.tipo_documento LIKE 'NC')
	AND cxp_nc.total_cuenta_pagar = 0;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc, cp_factura cxp_fact SET
	cxp_nc.total_cuenta_pagar = (CASE 
									WHEN (cxp_nc.tipo_documento LIKE 'FA') THEN
										(CASE cxp_fact.id_modo_compra
											WHEN 1 THEN
												(IFNULL(cxp_fact.subtotal_factura, 0)
													- IFNULL(cxp_fact.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto FROM cp_factura_gasto cxp_fact_gasto
															WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
																AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
													+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva FROM cp_factura_iva cxp_fact_iva
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
												END)
										END)
								END)
WHERE (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento LIKE 'FA')
	AND cxp_nc.total_cuenta_pagar = 0;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc, cp_notadecargo cxp_nd SET
	cxp_nc.total_cuenta_pagar = (CASE 
									WHEN (cxp_nc.tipo_documento LIKE 'ND') THEN
										(IFNULL(cxp_nd.subtotal_notacargo, 0)
											- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
											+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) AS total_gasto FROM cp_notacargo_gastos cxp_nd_gasto
													WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
														AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)
											+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) AS total_iva FROM cp_notacargo_iva cxp_nd_iva
													WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0))
								END)
WHERE (cxp_nc.id_documento = cxp_nd.id_notacargo AND cxp_nc.tipo_documento LIKE 'ND')
	AND cxp_nc.total_cuenta_pagar = 0;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);


// ACTUALIZA EL NUMERO DE NOTA DE CREDITO CON EL DE SU FACTURA A LOS QUE NO TIENEN
$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
	cxp_nc.numero_nota_credito = (SELECT cxp_fact.numero_factura_proveedor FROM cp_factura cxp_fact
								WHERE cxp_fact.id_factura = cxp_nc.id_documento)
WHERE cxp_nc.numero_nota_credito = ''
	AND cxp_nc.id_documento > 0
	AND cxp_nc.tipo_documento LIKE 'FA';");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// BUSCA LAS NOTAS DE CREDITO DE FACTURAS QUE NO ESTAN INCLUIDAS COMO PAGO
$query = sprintf("SELECT cxp_nc.*,
	(IFNULL(cxp_nc.subtotal_notacredito, 0)
		- IFNULL(cxp_nc.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
				FROM cp_notacredito_gastos cxp_nc_gasto
				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
					AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
				FROM cp_notacredito_iva cxp_nc_iva
				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)) AS total,

	(SELECT cxp_fact.total_cuenta_pagar FROM cp_factura cxp_fact
	WHERE cxp_fact.id_factura = cxp_nc.id_documento) AS total_cuenta_pagar,
	
	(SELECT SUM(IFNULL(cxp_pago.monto_cancelado,0)) FROM cp_pagos_documentos cxp_pago
	WHERE cxp_pago.id_documento_pago = cxp_nc.id_documento
		AND cxp_pago.tipo_documento_pago LIKE 'FA'
		AND cxp_pago.estatus = 1) AS total_pagos
FROM cp_notacredito cxp_nc
WHERE cxp_nc.id_documento > 0
	AND cxp_nc.tipo_documento LIKE 'FA'
	AND cxp_nc.id_notacredito NOT IN (SELECT cxp_pago.id_documento FROM cp_pagos_documentos cxp_pago
									WHERE cxp_pago.id_documento_pago = cxp_nc.id_documento
										AND cxp_pago.tipo_documento_pago LIKE 'FA'
										AND cxp_pago.tipo_pago LIKE 'NC');");
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while ($row = mysql_fetch_assoc($rs)) {
	$txtSaldo = $row['total_cuenta_pagar'] - $row['total_pagos'];
	
	$txtMontoPago = ($txtSaldo > $row['total']) ? $row['total'] : $txtSaldo;
	
	$insertSQL = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
	VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($row['id_documento'], "int"),
		valTpDato('FA', "text"),
		valTpDato('NC', "text"),
		valTpDato($row['id_notacredito'], "text"),
		valTpDato(date("Y-m-d", strtotime($row['fecha_registro_notacredito'])), "text"),
		valTpDato($row['id_empleado_creador'], "int"),
		valTpDato($row['numero_nota_credito'], "text"),
		valTpDato("-", "text"),
		valTpDato("-", "text"),
		valTpDato("-", "text"),
		valTpDato("-", "text"),
		valTpDato($txtMontoPago, "real_inglesa"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
}
									
// BUSCA LAS FACTURAS QUE TIENEN COMO SALDO CERO, PERO QUE SUS PAGOS SUMADOS NO INDICAN ESE MONTO COMO PAGADO
"SELECT
	cxp_fact.id_proveedor,
	cxp_fact.id_factura,
	COUNT(cxp_fact.id_proveedor) AS cant_facturas,
	
	SUM(ROUND(cxp_fact.total_cuenta_pagar, 2)) AS total_cuenta_pagar,
	
	SUM((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
		WHERE cxp_pago.id_documento_pago = cxp_fact.id_factura
			AND cxp_pago.tipo_documento_pago LIKE 'FA'
			AND cxp_pago.estatus = 1)) AS total_pagos,
	
	(SUM(ROUND(cxp_fact.total_cuenta_pagar, 2))
		- SUM((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
				WHERE cxp_pago.id_documento_pago = cxp_fact.id_factura
					AND cxp_pago.tipo_documento_pago LIKE 'FA'
					AND cxp_pago.estatus = 1))) AS total_diferencia
FROM cp_factura cxp_fact
WHERE cxp_fact.saldo_factura = 0
	AND ROUND(cxp_fact.total_cuenta_pagar, 2) > (SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
												WHERE cxp_pago.id_documento_pago = cxp_fact.id_factura
													AND cxp_pago.tipo_documento_pago LIKE 'FA'
													AND cxp_pago.estatus = 1)
GROUP BY cxp_fact.id_proveedor, cxp_fact.id_factura;";

// BUSCO LAS NOTA DE CREDITO QUE TIENEN SALDO EN CERO PERO QUE SUS PAGOS NO INDICAN LO MISMO
"SELECT
	cxp_nc.id_proveedor,
	cxp_nc.id_notacredito,
	COUNT(cxp_nc.id_proveedor) AS cant_nota_credito,
	
	SUM(ROUND(cxp_nc.total_cuenta_pagar, 2)) AS total_cuenta_pagar,
	
	SUM((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
		WHERE cxp_pago.id_documento = cxp_nc.id_notacredito
			AND cxp_pago.tipo_pago LIKE 'NC'
			AND cxp_pago.estatus = 1)) AS total_pagado,
	
	(SUM(ROUND(cxp_nc.total_cuenta_pagar, 2))
		- SUM((SELECT SUM(IFNULL(cxp_pago.monto_cancelado,0)) FROM cp_pagos_documentos cxp_pago
				WHERE cxp_pago.id_documento = cxp_nc.id_notacredito
					AND cxp_pago.tipo_pago LIKE 'NC'
					AND cxp_pago.estatus = 1))) AS total_diferencia
FROM cp_notacredito cxp_nc
WHERE cxp_nc.saldo_notacredito = 0
	AND ROUND(cxp_nc.total_cuenta_pagar, 2) > IFNULL((SELECT SUM(IFNULL(cxp_pago.monto_cancelado,0)) FROM cp_pagos_documentos cxp_pago
													WHERE cxp_pago.id_documento = cxp_nc.id_notacredito
														AND cxp_pago.tipo_pago LIKE 'NC'
														AND cxp_pago.estatus = 1),0)
GROUP BY cxp_nc.id_proveedor, cxp_nc.id_notacredito";

// ACTUALIZA EL NUMERO DE NOTA DE CREDITO CON EL DE SU NOTA DE CARGO A LOS QUE NO TIENEN
$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
	cxp_nc.numero_nota_credito = (SELECT cxp_nd.numero_notacargo FROM cp_notadecargo cxp_nd
								WHERE cxp_nd.id_notacargo = cxp_nc.id_documento)
WHERE cxp_nc.numero_nota_credito = ''
	AND cxp_nc.id_documento > 0
	AND cxp_nc.tipo_documento LIKE 'ND';");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// BUSCA LAS NOTAS DE CREDITO DE NOTAS DE CARGO QUE NO ESTAN INCLUIDAS COMO PAGO
$query = sprintf("SELECT cxp_nc.*,
	(IFNULL(cxp_nc.subtotal_notacredito, 0)
		- IFNULL(cxp_nc.subtotal_descuento, 0)
		+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
				FROM cp_notacredito_gastos cxp_nc_gasto
				WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
					AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
		+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
				FROM cp_notacredito_iva cxp_nc_iva
				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)) AS total,

	(SELECT
		(IFNULL(cxp_nd.subtotal_notacargo, 0)
			- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
			+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) AS total_gasto
					FROM cp_notacargo_gastos cxp_nd_gasto
					WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo), 0)
			+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) AS total_iva
					FROM cp_notacargo_iva cxp_nd_iva
					WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0))
	FROM cp_notadecargo cxp_nd
	WHERE cxp_nd.id_notacargo = cxp_nc.id_documento) AS total_cuenta_pagar,
	
	(SELECT SUM(IFNULL(cxp_pago.monto_cancelado,0)) FROM cp_pagos_documentos cxp_pago
	WHERE cxp_pago.id_documento_pago = cxp_nc.id_documento
		AND cxp_pago.tipo_documento_pago LIKE 'ND'
		AND cxp_pago.estatus = 1) AS total_pagos
FROM cp_notacredito cxp_nc
WHERE cxp_nc.id_documento > 0
	AND cxp_nc.tipo_documento LIKE 'ND'
	AND cxp_nc.id_notacredito NOT IN (SELECT cxp_pago.id_documento FROM cp_pagos_documentos cxp_pago
									WHERE cxp_pago.id_documento_pago = cxp_nc.id_documento
										AND cxp_pago.tipo_documento_pago LIKE 'ND'
										AND cxp_pago.tipo_pago LIKE 'NC');");
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while ($row = mysql_fetch_assoc($rs)) {
	$txtSaldo = $row['total_cuenta_pagar'] - $row['total_pagos'];
	
	$txtMontoPago = ($txtSaldo > $row['total']) ? $row['total'] : $txtSaldo;
	
	$insertSQL = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
	VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($row['id_documento'], "int"),
		valTpDato('ND', "text"),
		valTpDato('NC', "text"),
		valTpDato($row['id_notacredito'], "text"),
		valTpDato(date("Y-m-d", strtotime($row['fecha_registro_notacredito'])), "text"),
		valTpDato($row['id_empleado_creador'], "int"),
		valTpDato($row['numero_nota_credito'], "text"),
		valTpDato("-", "text"),
		valTpDato("-", "text"),
		valTpDato("-", "text"),
		valTpDato("-", "text"),
		valTpDato($txtMontoPago, "real_inglesa"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
}

// BUSCA LAS NOTAS DE CARGO QUE TIENEN COMO SALDO CERO, PERO QUE SUS PAGOS SUMADOS NO INDICAN ESE MONTO COMO PAGADO
"SELECT
	cxp_nd.id_proveedor,
	COUNT(cxp_nd.id_proveedor) AS cant_notas_cargo,
	
	SUM(cxp_nd.total_cuenta_pagar) AS total_cuenta_pagar,
	
	SUM((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
		WHERE cxp_pago.id_documento_pago = cxp_nd.id_notacargo
			AND cxp_pago.tipo_documento_pago LIKE 'ND'
			AND cxp_pago.estatus = 1)) AS total_pagos,
	
	(SUM(cxp_nd.total_cuenta_pagar)
		- SUM((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
				WHERE cxp_pago.id_documento_pago = cxp_nd.id_notacargo
					AND cxp_pago.tipo_documento_pago LIKE 'ND'
					AND cxp_pago.estatus = 1))) AS total_diferencia
FROM cp_notadecargo cxp_nd
WHERE cxp_nd.saldo_notacargo = 0
	AND ROUND(cxp_nd.total_cuenta_pagar, 2) > (SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
												WHERE cxp_pago.id_documento_pago = cxp_nd.id_notacargo
													AND cxp_pago.tipo_documento_pago LIKE 'ND'
													AND cxp_pago.estatus = 1)
GROUP BY cxp_nd.id_proveedor;";


mysql_query("COMMIT;");

echo "<h1>SALDOS DE DCTOS Y PAGOS INGRESADOS CON EXITO</h1>";
?>