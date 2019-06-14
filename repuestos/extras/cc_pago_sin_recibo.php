<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");

mysql_query("START TRANSACTION;");

// ACTUALIZA EL DEPARTAMENTO EN EL RECIBO DE PAGO
$updateSQL = sprintf("UPDATE cj_encabezadorecibopago recibo SET
	id_departamento = (SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
                        WHERE cxc_fact.idFactura = recibo.numero_tipo_documento)
WHERE recibo.idTipoDeDocumento = 1;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

// ACTUALIZA EL DEPARTAMENTO EN EL RECIBO DE PAGO
$updateSQL = sprintf("UPDATE cj_encabezadorecibopago recibo SET
	id_departamento = (SELECT cxc_nd.idDepartamentoOrigenNotaCargo FROM cj_cc_notadecargo cxc_nd
                        WHERE cxc_nd.idNotaCargo = recibo.numero_tipo_documento)
WHERE recibo.idTipoDeDocumento = 2;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

// LE AGREGA EL ID DE LA FACTURA
$updateSQL = sprintf("UPDATE sa_iv_pagos cxc_pago SET
	id_factura = (SELECT cxc_fact.idFactura FROM cj_cc_encabezadofactura cxc_fact
				WHERE cxc_fact.numeroFactura LIKE cxc_pago.numeroFactura
					AND cxc_fact.idDepartamentoOrigenFactura IN (0,1,3)
					AND cxc_fact.subtotalFactura > 0)
WHERE id_factura IS NULL;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

// BUSCA LAS FACTURAS DONDE SUS PAGOS SUMADOS ES MAYOR AL MONTO TOTAL
$queryFact = sprintf("SELECT
	cxc_fact.idFactura,
    cxc_fact.idDepartamentoOrigenFactura,
	cxc_fact.montoTotalFactura,
	(SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
		WHERE cxc_pago.id_factura = cxc_fact.idFactura
        	AND cxc_pago.estatus IN (1)) AS total_pagos,
	(IFNULL(cxc_fact.montoTotalFactura, 0)
		- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.id_factura = cxc_fact.idFactura
        			AND cxc_pago.estatus IN (1)), 0)) AS diferencia_pago
FROM cj_cc_encabezadofactura cxc_fact
WHERE (SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
		WHERE cxc_pago.id_factura = cxc_fact.idFactura
        	AND cxc_pago.estatus IN (1)) > cxc_fact.montoTotalFactura
	AND (SELECT COUNT(cxc_pago.formaPago) FROM sa_iv_pagos cxc_pago
		WHERE cxc_pago.id_factura = cxc_fact.idFactura
			AND cxc_pago.formaPago IN (8)
        	AND cxc_pago.estatus IN (1)) > 0;");
$rsFact = mysql_query($queryFact);
if (!$rsFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowFact = mysql_fetch_assoc($rsFact)) {
	if ($rowFact['montoTotalFactura'] == (-1) * $rowFact['diferencia_pago']) {
		$deleteSQL = sprintf("DELETE FROM sa_iv_pagos
		WHERE id_factura = %s
			AND formaPago IN (8);",
			valTpDato($rowFact['idFactura'], "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".($deleteSQL)."</pre>";
	}
}


// LOS PAGOS QUE NO TIENEN RECIBO DE CONTABILIDAD
$queryPago = sprintf("SELECT * FROM sa_iv_pagos WHERE id_encabezado_rs = 0;");
$rsPago = mysql_query($queryPago);
if (!$rsPago) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowPago = mysql_fetch_assoc($rsPago)) {
	$idPago = $rowPago['idPago'];
	$idFactura = $rowPago['id_factura'];
	$fechaRegistroPago = date("Y-m-d",strtotime($rowPago['fechaPago']));
	
	// VERIFICA SI EN EL DIA HAY UN RECIBO DE PAGO QUE NO TENGA DETALLES PARA ASIGNARSELO
	$queryRecibo = sprintf("SELECT * FROM cj_cc_encabezado_pago_rs
	WHERE id_factura = %s
		AND fecha_pago = %s
		AND id_encabezado_rs NOT IN (SELECT id_encabezado_rs FROM sa_iv_pagos);",
		valTpDato($idFactura, "int"),
		valTpDato($fechaRegistroPago, "date"));
	$rsRecibo = mysql_query($queryRecibo);
	if (!$rsRecibo) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRecibo = mysql_num_rows($rsRecibo);
	$rowRecibo = mysql_fetch_assoc($rsRecibo);
	
	if (!($idFactura > 0)) {
		echo "<pre>".sprintf("SELECT * FROM sa_iv_pagos WHERE idPago = %s UNION ", valTpDato($idPago, "int"))."</pre>";
	} else if ($totalRowsRecibo > 0) {
		$idEncabezadoPago = $rowRecibo['id_encabezado_rs'];
		
		$updateSQL = sprintf("UPDATE sa_iv_pagos SET
			id_encabezado_rs = %s
		WHERE idPago = %s;",
			valTpDato($idEncabezadoPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$query = sprintf("SELECT * FROM cj_cc_encabezado_pago_rs
		WHERE id_factura = %s
			AND fecha_pago = %s UNION ",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		
		echo "<pre>".($updateSQL."<br>".$query)."</pre>";
	} else if (!($totalRowsRecibo > 0)) {
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
		VALUES (%s, %s);",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoPago = mysql_insert_id();
		
		$updateSQL = sprintf("UPDATE sa_iv_pagos SET
			id_encabezado_rs = %s
		WHERE idPago = %s;",
			valTpDato($idEncabezadoPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".($insertSQL."<br>".$updateSQL)."</pre>";
	}
}


// BUSCA LOS PAGOS QUE NO TIENEN RECIBO
$queryPago = sprintf("SELECT * FROM sa_iv_pagos cxc_pago
WHERE cxc_pago.idPago NOT IN (SELECT recibo_det.idPago
							FROM cj_encabezadorecibopago recibo
								INNER JOIN cj_detallerecibopago recibo_det ON (recibo.idComprobante = recibo_det.idComprobantePagoFactura AND recibo.idTipoDeDocumento = 1)
							WHERE recibo.id_departamento IN (0,1,3));");
$rsPago = mysql_query($queryPago);
if (!$rsPago) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowPago = mysql_fetch_assoc($rsPago)) {
	$idPago = $rowPago['idPago'];
	$idFactura = $rowPago['id_factura'];
	$fechaRegistroPago = date("Y-m-d",strtotime($rowPago['fechaPago']));
	
	// VERIFICA SI EXISTE UN RECIBO DEL DOCUMENTO QUE NO TENGA ASOCIADO EL DETALLE DEL PAGO
	$queryRecibo = sprintf("SELECT * FROM cj_encabezadorecibopago recibo
	WHERE recibo.numero_tipo_documento = %s
		AND recibo.fechaComprobante = %s
		AND recibo.id_departamento IN (0,1,3)
		AND recibo.idTipoDeDocumento = 1
		AND recibo.idComprobante NOT IN (SELECT recibo_det.idComprobantePagoFactura FROM cj_detallerecibopago recibo_det);",
		valTpDato($idFactura, "int"),
		valTpDato($fechaRegistroPago, "date"));
	$rsRecibo = mysql_query($queryRecibo);
	if (!$rsRecibo) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRecibo = mysql_num_rows($rsRecibo);
	$rowRecibo = mysql_fetch_assoc($rsRecibo);
	
	if ($totalRowsRecibo > 0) {
		echo "<pre>".($queryRecibo)."</pre>";
	} else if (!($totalRowsRecibo > 0)) {
		$queryFact = sprintf("SELECT *
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		WHERE cxc_pago.idPago = %s;",
			valTpDato($idPago, "int"));
		$rsFact = mysql_query($queryFact);
		if (!$rsFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFact = mysql_fetch_assoc($rsFact);
		
		$idEmpresa = $rowFact['id_empresa'];
		$idModulo = $rowFact['idDepartamentoOrigenFactura'];
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(44, "int"), // 44 = Recibo de Pago Repuestos y Servicios
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento)
		VALUES (%s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = OT
			valTpDato(0, "int"),		
			valTpDato($idFactura, "int"),
			valTpDato($idModulo, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL DETALLE DEL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
		VALUES (%s, %s);",
			valTpDato($idEncabezadoReciboPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".($queryFact."<br>".$insertSQL)."</pre>";
	}
}




////////////////////////////////////////////////// PAGOS MODULO DE VEHICULOS //////////////////////////////////////////////////

// LOS PAGOS QUE NO TIENEN RECIBO DE CONTABILIDAD
$queryPago = sprintf("SELECT * FROM an_pagos WHERE id_encabezado_v = 0;");
$rsPago = mysql_query($queryPago);
if (!$rsPago) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowPago = mysql_fetch_assoc($rsPago)) {
	$idPago = $rowPago['idPago'];
	$idFactura = $rowPago['id_factura'];
	$fechaRegistroPago = date("Y-m-d",strtotime($rowPago['fechaPago']));
	
	// VERIFICA SI EN EL DIA HAY UN RECIBO DE PAGO QUE NO TENGA DETALLES PARA ASIGNARSELO
	$queryRecibo = sprintf("SELECT * FROM cj_cc_encabezado_pago_v
	WHERE id_factura = %s
		AND fecha_pago = %s
		AND id_encabezado_v NOT IN (SELECT id_encabezado_v FROM an_pagos);",
		valTpDato($idFactura, "int"),
		valTpDato($fechaRegistroPago, "date"));
	$rsRecibo = mysql_query($queryRecibo);
	if (!$rsRecibo) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRecibo = mysql_num_rows($rsRecibo);
	$rowRecibo = mysql_fetch_assoc($rsRecibo);
	
	if (!($idFactura > 0)) {
		echo "<pre>".sprintf("SELECT * FROM an_pagos WHERE idPago = %s UNION ", valTpDato($idPago, "int"))."</pre>";
	} else if ($totalRowsRecibo > 0) {
		$idEncabezadoPago = $rowRecibo['id_encabezado_v'];
		
		$updateSQL = sprintf("UPDATE an_pagos SET
			id_encabezado_v = %s
		WHERE idPago = %s;",
			valTpDato($idEncabezadoPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$query = sprintf("SELECT * FROM cj_cc_encabezado_pago_v
		WHERE id_factura = %s
			AND fecha_pago = %s UNION ",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		
		echo "<pre>".($updateSQL."<br>".$query)."</pre>";
	} else if (!($totalRowsRecibo > 0)) {
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_v (id_factura, fecha_pago)
		VALUES (%s, %s);",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoPago = mysql_insert_id();
		
		$updateSQL = sprintf("UPDATE an_pagos SET
			id_encabezado_v = %s
		WHERE idPago = %s;",
			valTpDato($idEncabezadoPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".($insertSQL."<br>".$updateSQL)."</pre>";
	}
}

// BUSCA LOS PAGOS QUE NO TIENEN RECIBO
$queryPago = sprintf("SELECT * FROM an_pagos cxc_pago
WHERE cxc_pago.idPago NOT IN (SELECT recibo_det.idPago
							FROM cj_encabezadorecibopago recibo
								INNER JOIN cj_detallerecibopago recibo_det ON (recibo.idComprobante = recibo_det.idComprobantePagoFactura AND recibo.idTipoDeDocumento = 1)
							WHERE recibo.id_departamento IN (2));");
$rsPago = mysql_query($queryPago);
if (!$rsPago) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowPago = mysql_fetch_assoc($rsPago)) {
	$idPago = $rowPago['idPago'];
	$idFactura = $rowPago['id_factura'];
	$fechaRegistroPago = date("Y-m-d",strtotime($rowPago['fechaPago']));
	
	// VERIFICA SI EXISTE UN RECIBO DEL DOCUMENTO QUE NO TENGA ASOCIADO EL DETALLE DEL PAGO
	$queryRecibo = sprintf("SELECT * FROM cj_encabezadorecibopago recibo
	WHERE recibo.numero_tipo_documento = %s
		AND recibo.fechaComprobante = %s
		AND recibo.id_departamento IN (2)
		AND recibo.idTipoDeDocumento = 1
		AND recibo.idComprobante NOT IN (SELECT recibo_det.idComprobantePagoFactura FROM cj_detallerecibopago recibo_det);",
		valTpDato($idFactura, "int"),
		valTpDato($fechaRegistroPago, "date"));
	$rsRecibo = mysql_query($queryRecibo);
	if (!$rsRecibo) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRecibo = mysql_num_rows($rsRecibo);
	$rowRecibo = mysql_fetch_assoc($rsRecibo);
	
	if ($totalRowsRecibo > 0) {
		echo "<pre>".($queryRecibo)."</pre>";
	} else if (!($totalRowsRecibo > 0)) {
		$queryFact = sprintf("SELECT *
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		WHERE cxc_pago.idPago = %s;",
			valTpDato($idPago, "int"));
		$rsFact = mysql_query($queryFact);
		if (!$rsFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFact = mysql_fetch_assoc($rsFact);
		
		$idEmpresa = $rowFact['id_empresa'];
		$idModulo = $rowFact['idDepartamentoOrigenFactura'];
		
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
		if (!$rsNumeracion) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento)
		VALUES (%s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = OT
			valTpDato(0, "int"),		
			valTpDato($idFactura, "int"),
			valTpDato($idModulo, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL DETALLE DEL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
		VALUES (%s, %s);",
			valTpDato($idEncabezadoReciboPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".($queryFact."<br>".$insertSQL)."</pre>";
	}
}




///////////////////////////////////////////// PAGOS DE NOTAS DE CARGO MODULO DE REPUESTOS, SERVICIOS, ADMINISTRACION /////////////////////////////////////////////

// LOS PAGOS QUE NO TIENEN RECIBO DE CONTABILIDAD
$queryPago = sprintf("SELECT *
FROM cj_cc_notadecargo cxc_nd
	INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
WHERE cxc_nd.idDepartamentoOrigenNotaCargo IN (0,1,3)
	AND cxc_pago.id_encabezado_nc = 0;");
$rsPago = mysql_query($queryPago);
if (!$rsPago) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowPago = mysql_fetch_assoc($rsPago)) {
	$idPago = $rowPago['id_det_nota_cargo'];
	$idNotaCargo = $rowPago['idNotaCargo'];
	$fechaRegistroPago = date("Y-m-d",strtotime($rowPago['fechaPago']));
	
	// VERIFICA SI EN EL DIA HAY UN RECIBO DE PAGO QUE NO TENGA DETALLES PARA ASIGNARSELO
	$queryRecibo = sprintf("SELECT * FROM cj_cc_encabezado_pago_nc_rs
	WHERE id_nota_cargo = %s
		AND fecha_pago = %s
		AND id_encabezado_nc_rs NOT IN (SELECT cxc_pago.id_encabezado_nc
										FROM cj_cc_notadecargo cxc_nd
											INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
										WHERE cxc_nd.idDepartamentoOrigenNotaCargo IN (0,1,3));",
		valTpDato($idNotaCargo, "int"),
		valTpDato($fechaRegistroPago, "date"));
	$rsRecibo = mysql_query($queryRecibo);
	if (!$rsRecibo) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRecibo = mysql_num_rows($rsRecibo);
	$rowRecibo = mysql_fetch_assoc($rsRecibo);
	
	if (!($idNotaCargo > 0)) {
		echo "<pre>".sprintf("SELECT * FROM cj_det_nota_cargo WHERE id_det_nota_cargo = %s UNION ", valTpDato($idPago, "int"))."</pre>";
	} else if ($totalRowsRecibo > 0) {
		$idEncabezadoPago = $rowRecibo['id_encabezado_nc_rs'];
		
		$updateSQL = sprintf("UPDATE cj_det_nota_cargo SET
			id_encabezado_nc = %s
		WHERE id_det_nota_cargo = %s;",
			valTpDato($idEncabezadoPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$query = sprintf("SELECT * FROM cj_cc_encabezado_pago_nc_rs
		WHERE id_nota_cargo = %s
			AND fecha_pago = %s UNION ",
			valTpDato($idNotaCargo, "int"),
			valTpDato($fechaRegistroPago, "date"));
		
		echo "<pre>".($updateSQL."<br>".$query)."</pre>";
	} else if (!($totalRowsRecibo > 0)) {
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_rs (id_nota_cargo, fecha_pago)
		VALUES (%s, %s);",
			valTpDato($idNotaCargo, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoPago = mysql_insert_id();
		
		$updateSQL = sprintf("UPDATE cj_det_nota_cargo SET
			id_encabezado_nc = %s
		WHERE id_det_nota_cargo = %s;",
			valTpDato($idEncabezadoPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".($insertSQL."<br>".$updateSQL)."</pre>";
	}
}




///////////////////////////////////////////// PAGOS DE NOTAS DE CARGO MODULO DE VEHICULOS /////////////////////////////////////////////

// LOS PAGOS QUE NO TIENEN RECIBO DE CONTABILIDAD
$queryPago = sprintf("SELECT *
FROM cj_cc_notadecargo cxc_nd
	INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
WHERE cxc_nd.idDepartamentoOrigenNotaCargo IN (2)
	AND cxc_pago.id_encabezado_nc = 0;");
$rsPago = mysql_query($queryPago);
if (!$rsPago) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowPago = mysql_fetch_assoc($rsPago)) {
	$idPago = $rowPago['id_det_nota_cargo'];
	$idNotaCargo = $rowPago['idNotaCargo'];
	$fechaRegistroPago = date("Y-m-d",strtotime($rowPago['fechaPago']));
	
	// VERIFICA SI EN EL DIA HAY UN RECIBO DE PAGO QUE NO TENGA DETALLES PARA ASIGNARSELO
	$queryRecibo = sprintf("SELECT * FROM cj_cc_encabezado_pago_nc_v
	WHERE id_nota_cargo = %s
		AND fecha_pago = %s
		AND id_encabezado_nc_v NOT IN (SELECT cxc_pago.id_encabezado_nc
										FROM cj_cc_notadecargo cxc_nd
											INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
										WHERE cxc_nd.idDepartamentoOrigenNotaCargo IN (2));",
		valTpDato($idNotaCargo, "int"),
		valTpDato($fechaRegistroPago, "date"));
	$rsRecibo = mysql_query($queryRecibo);
	if (!$rsRecibo) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRecibo = mysql_num_rows($rsRecibo);
	$rowRecibo = mysql_fetch_assoc($rsRecibo);
	
	if (!($idNotaCargo > 0)) {
		echo "<pre>".sprintf("SELECT * FROM cj_det_nota_cargo WHERE id_det_nota_cargo = %s UNION ", valTpDato($idPago, "int"))."</pre>";
	} else if ($totalRowsRecibo > 0) {
		$idEncabezadoPago = $rowRecibo['id_encabezado_nc_v'];
		
		$updateSQL = sprintf("UPDATE cj_det_nota_cargo SET
			id_encabezado_nc = %s
		WHERE id_det_nota_cargo = %s;",
			valTpDato($idEncabezadoPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$query = sprintf("SELECT * FROM cj_cc_encabezado_pago_nc_v
		WHERE id_nota_cargo = %s
			AND fecha_pago = %s UNION ",
			valTpDato($idNotaCargo, "int"),
			valTpDato($fechaRegistroPago, "date"));
		
		echo "<pre>".($updateSQL."<br>".$query)."</pre>";
	} else if (!($totalRowsRecibo > 0)) {
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_nc_v (id_nota_cargo, fecha_pago)
		VALUES (%s, %s);",
			valTpDato($idNotaCargo, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idEncabezadoPago = mysql_insert_id();
		
		$updateSQL = sprintf("UPDATE cj_det_nota_cargo SET
			id_encabezado_nc = %s
		WHERE id_det_nota_cargo = %s;",
			valTpDato($idEncabezadoPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".($insertSQL."<br>".$updateSQL)."</pre>";
	}
}

mysql_query("COMMIT;");

echo "<h1>RECIBOS CREADOS Y LIGADOS CON EXITO</h1>";
?>