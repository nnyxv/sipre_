<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");

mysql_query("START TRANSACTION;");

// ACTUALIZA Y ACTIVA LOS PAGOS CON CHEQUES Y TRANSFERENCIAS
$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
	estatus = 1,
	fecha_anulado = NULL,
	id_empleado_anulado = NULL
WHERE tipo_pago IN ('Cheque', 'Transferencia');");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

// BUSCA LOS CHEQUES ANULADOS
$queryArt = sprintf("SELECT 
	cheque_anulado.id_cheque,
	cheque_anulado.fecha_registro,
	cheque_anulado.numero_cheque,
	cheque_anulado.id_factura,
	cheque_anulado.tipo_documento,
	cheque_anulado.monto_cheque,
	usuario.id_empleado
FROM te_cheques_anulados cheque_anulado
	INNER JOIN pg_usuario usuario ON (cheque_anulado.id_usuario = usuario.id_usuario);");
$rsArt = mysql_query($queryArt);
if (!$rsArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowArt = mysql_fetch_assoc($rsArt)) {
	$arrayCheque = NULL;
	if ($rowArt['id_factura'] > 0) { // INDIVIDUAL
		$arrayCheque[] = array(
			"id_documento_pagado" => $rowArt['id_factura'],
			"tipo_documento_pagado" => $rowArt['tipo_documento'],
			"monto_pagado" => $rowArt['monto_cheque']);
	} else { // POR PROPUESTA DE PAGO
		$queryDetalleArt = sprintf("SELECT
			id_factura,
			tipo_documento,
			monto_pagar
		FROM te_cheques_anulados_detalle
		WHERE id_cheque = %s;",
			valTpDato($rowArt['id_cheque'], "int"));
		$rsDetalleArt = mysql_query($queryDetalleArt);
		if (!$rsDetalleArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowDetalleArt = mysql_fetch_assoc($rsDetalleArt)) {
			$arrayCheque[] = array(
				"id_documento_pagado" => $rowDetalleArt['id_factura'],
				"tipo_documento_pagado" => $rowDetalleArt['tipo_documento'],
				"monto_pagado" => $rowDetalleArt['monto_pagar']);
		}
	}
	
	if (isset($arrayCheque)) {
		foreach ($arrayCheque as $indice => $valor) {
			$txtIdDocumento = $valor['id_documento_pagado'];
			$txtTipoDocumento = $valor['tipo_documento_pagado'];
			$txtMontoPagado = $valor['monto_pagado'];
			
			$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
				id_documento = %s
			WHERE numero_documento LIKE %s
				AND monto_cancelado = %s
				AND tipo_pago LIKE 'Cheque'
				AND estatus = 1;",
				valTpDato($rowArt['id_cheque'], "int"),
				valTpDato($rowArt['numero_cheque'], "text"),
				valTpDato($txtMontoPagado, "real_inglesa"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
		estatus = NULL,
		fecha_anulado = %s,
		id_empleado_anulado = %s
	WHERE id_documento = %s
		AND tipo_pago LIKE 'Cheque'
		AND estatus = 1;",
		valTpDato(date("Y-m-d H:i:s", strtotime($rowArt['fecha_registro'])), "date"),
		valTpDato($rowArt['id_empleado'], "int"),
		valTpDato($rowArt['id_cheque'], "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	echo "<span style=\"size:8px;\">".$updateSQL."<br><br></span>";
}

// BUSCA LAS TRANSFERENCIAS ANULADAS
$queryArt = sprintf("SELECT 
	transf_anulada.id_transferencia,
	transf_anulada.fecha_registro,
	transf_anulada.numero_transferencia,
	transf_anulada.id_documento,
	transf_anulada.tipo_documento,
	transf_anulada.monto_transferencia,
	usuario.id_empleado
FROM te_transferencias_anuladas transf_anulada
	INNER JOIN pg_usuario usuario ON (transf_anulada.id_usuario = usuario.id_usuario);");
$rsArt = mysql_query($queryArt);
if (!$rsArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowArt = mysql_fetch_assoc($rsArt)) {
	$arrayTransferencia = NULL;
	if ($rowArt['id_documento'] > 0) { // INDIVIDUAL
		$arrayTransferencia[] = array(
			"id_documento_pagado" => $rowArt['id_documento'],
			"tipo_documento_pagado" => $rowArt['tipo_documento'],
			"monto_pagado" => $rowArt['monto_transferencia']);
	} else { // POR PROPUESTA DE PAGO
		$queryDetalleArt = sprintf("SELECT
			id_factura,
			tipo_documento,
			monto_pagar
		FROM te_transferencias_anuladas_detalle
		WHERE id_transferencia = %s;",
			valTpDato($rowArt['id_transferencia'], "int"));
		$rsDetalleArt = mysql_query($queryDetalleArt);
		if (!$rsDetalleArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowDetalleArt = mysql_fetch_assoc($rsDetalleArt)) {
			$arrayTransferencia[] = array(
				"id_documento_pagado" => $rowDetalleArt['id_factura'],
				"tipo_documento_pagado" => $rowDetalleArt['tipo_documento'],
				"monto_pagado" => $rowDetalleArt['monto_pagar']);
		}
	}
	
	if (isset($arrayTransferencia)) {
		foreach ($arrayTransferencia as $indice => $valor) {
			$txtIdDocumento = $valor['id_documento_pagado'];
			$txtTipoDocumento = $valor['tipo_documento_pagado'];
			$txtMontoPagado = $valor['monto_pagado'];
			
			$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
				id_documento = %s
			WHERE numero_documento LIKE %s
				AND monto_cancelado = %s
				AND tipo_pago LIKE 'Transferencia'
				AND estatus = 1;",
				valTpDato($rowArt['id_transferencia'], "int"),
				valTpDato($rowArt['numero_transferencia'], "text"),
				valTpDato($txtMontoPagado, "real_inglesa"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
		estatus = NULL,
		fecha_anulado = %s,
		id_empleado_anulado = %s
	WHERE id_documento = %s
		AND tipo_pago LIKE 'Transferencia'
		AND estatus = 1;",
		valTpDato(date("Y-m-d H:i:s", strtotime($rowArt['fecha_registro'])), "date"),
		valTpDato($rowArt['id_empleado'], "int"),
		valTpDato($rowArt['id_transferencia'], "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
}


// BUSCA LOS CHEQUES
$queryArt = sprintf("SELECT 
	cheque.id_cheque,
	cheque.numero_cheque,
	banco.nombreBanco,
	cuenta.numeroCuentaCompania,
	cheque.id_factura,
	cheque.tipo_documento,
	cheque.monto_cheque,
	usuario.id_empleado
FROM te_cheques cheque
	INNER JOIN te_chequeras chequera ON (cheque.id_chequera = chequera.id_chq)
	INNER JOIN cuentas cuenta ON (chequera.id_cuenta = cuenta.idCuentas)
	INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco)
	INNER JOIN pg_usuario usuario ON (cheque.id_usuario = usuario.id_usuario);");
$rsArt = mysql_query($queryArt);
if (!$rsArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowArt = mysql_fetch_assoc($rsArt)) {
	$arrayCheque = NULL;
	if ($rowArt['id_factura'] > 0) { // INDIVIDUAL
		$arrayCheque[] = array(
			"id_documento_pagado" => $rowArt['id_factura'],
			"tipo_documento_pagado" => $rowArt['tipo_documento'],
			"monto_pagado" => $rowArt['monto_cheque']);
	} else { // POR PROPUESTA DE PAGO
		$queryDetalleArt = sprintf("SELECT 
			propuesta_pago_det.id_factura,
			propuesta_pago_det.tipo_documento,
			propuesta_pago_det.monto_pagar
		FROM te_propuesta_pago propuesta_pago
			INNER JOIN te_propuesta_pago_detalle propuesta_pago_det ON (propuesta_pago.id_propuesta_pago = propuesta_pago_det.id_propuesta_pago)
		WHERE propuesta_pago.id_cheque = %s;",
			valTpDato($rowArt['id_cheque'], "int"));
		$rsDetalleArt = mysql_query($queryDetalleArt);
		if (!$rsDetalleArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowDetalleArt = mysql_fetch_assoc($rsDetalleArt)) {
			$arrayCheque[] = array(
				"id_documento_pagado" => $rowDetalleArt['id_factura'],
				"tipo_documento_pagado" => $rowDetalleArt['tipo_documento'],
				"monto_pagado" => $rowDetalleArt['monto_pagar']);
		}
	}
	
	if (isset($arrayCheque)) {
		foreach ($arrayCheque as $indice => $valor) {
			$txtIdDocumento = $valor['id_documento_pagado'];
			$txtTipoDocumento = $valor['tipo_documento_pagado'];
			$txtMontoPagado = $valor['monto_pagado'];
			
			$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
				id_documento = %s,
				id_empleado_creador = %s,
				banco_compania = %s,
				cuenta_compania = %s
			WHERE numero_documento LIKE %s
				AND monto_cancelado = %s
				AND tipo_pago LIKE 'Cheque'
				AND estatus = 1;",
				valTpDato($rowArt['id_cheque'], "int"),
				valTpDato($rowArt['id_empleado'], "int"),
				valTpDato($rowArt['nombreBanco'], "text"),
				valTpDato($rowArt['numeroCuentaCompania'], "text"),
				valTpDato($rowArt['numero_cheque'], "text"),
				valTpDato($txtMontoPagado, "real_inglesa"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
}

// BUSCA LAS TRANSFERENCIAS
$queryArt = sprintf("SELECT 
	transf.id_transferencia,
	transf.numero_transferencia,
	banco.nombreBanco,
	cuenta.numeroCuentaCompania,
	transf.id_documento,
	transf.tipo_documento,
	transf.monto_transferencia,
	usuario.id_empleado
FROM te_transferencia transf
	INNER JOIN cuentas cuenta ON (transf.id_cuenta = cuenta.idCuentas)
	INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco)
	INNER JOIN pg_usuario usuario ON (transf.id_usuario = usuario.id_usuario);");
$rsArt = mysql_query($queryArt);
if (!$rsArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowArt = mysql_fetch_assoc($rsArt)) {
	$arrayTransferencia = NULL;
	if ($rowArt['id_documento'] > 0) { // INDIVIDUAL
		$arrayTransferencia[] = array(
			"id_documento_pagado" => $rowArt['id_documento'],
			"tipo_documento_pagado" => $rowArt['tipo_documento'],
			"monto_pagado" => $rowArt['monto_transferencia']);
	} else { // POR PROPUESTA DE PAGO
		$queryDetalleArt = sprintf("SELECT 
			propuesta_pago_det_transf.id_factura,
			propuesta_pago_det_transf.tipo_documento,
			propuesta_pago_det_transf.monto_pagar
		FROM te_propuesta_pago_transferencia propuesta_pago_transf
			INNER JOIN te_propuesta_pago_detalle_transferencia propuesta_pago_det_transf ON (propuesta_pago_transf.id_propuesta_pago = propuesta_pago_det_transf.id_propuesta_pago)
		WHERE propuesta_pago_transf.id_transfererencia = %s;",
			valTpDato($rowArt['id_transferencia'], "int"));
		$rsDetalleArt = mysql_query($queryDetalleArt);
		if (!$rsDetalleArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowDetalleArt = mysql_fetch_assoc($rsDetalleArt)) {
			$arrayTransferencia[] = array(
				"id_documento_pagado" => $rowDetalleArt['id_factura'],
				"tipo_documento_pagado" => $rowDetalleArt['tipo_documento'],
				"monto_pagado" => $rowDetalleArt['monto_pagar']);
		}
	}
	
	if (isset($arrayTransferencia)) {
		foreach ($arrayTransferencia as $indice => $valor) {
			$txtIdDocumento = $valor['id_documento_pagado'];
			$txtTipoDocumento = $valor['tipo_documento_pagado'];
			$txtMontoPagado = $valor['monto_pagado'];
			
			$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
				id_documento = %s,
				id_empleado_creador = %s,
				banco_compania = %s,
				cuenta_compania = %s
			WHERE numero_documento LIKE %s
				AND monto_cancelado = %s
				AND tipo_pago LIKE 'Transferencia'
				AND estatus = 1;",
				valTpDato($rowArt['id_transferencia'], "int"),
				valTpDato($rowArt['id_empleado'], "int"),
				valTpDato($rowArt['nombreBanco'], "text"),
				valTpDato($rowArt['numeroCuentaCompania'], "text"),
				valTpDato($rowArt['numero_transferencia'], "text"),
				valTpDato($txtMontoPagado, "real_inglesa"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
}

// BUSCA LOS ISLR DE LOS CHEQUES
$queryArt = sprintf("SELECT 
	ret_cheque.id_retencion_cheque,
	cheque.id_cheque,
	cheque.numero_cheque,
	banco.nombreBanco,
	cuenta.numeroCuentaCompania,
	usuario.id_empleado
FROM te_cheques cheque
	INNER JOIN te_chequeras chequera ON (cheque.id_chequera = chequera.id_chq)
	INNER JOIN cuentas cuenta ON (chequera.id_cuenta = cuenta.idCuentas)
	INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco)
	INNER JOIN pg_usuario usuario ON (cheque.id_usuario = usuario.id_usuario)
	INNER JOIN te_retencion_cheque ret_cheque ON (cheque.id_cheque = ret_cheque.id_cheque)
		AND (ret_cheque.tipo_documento = 0);");
$rsArt = mysql_query($queryArt);
if (!$rsArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowArt = mysql_fetch_assoc($rsArt)) {
	$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
		id_documento = %s,
		id_empleado_creador = %s,
		banco_compania = %s,
		cuenta_compania = %s
	WHERE numero_documento LIKE %s
		AND tipo_pago LIKE 'ISLR';",
		valTpDato($rowArt['id_retencion_cheque'], "int"),
		valTpDato($rowArt['id_empleado'], "int"),
		valTpDato($rowArt['nombreBanco'], "text"),
		valTpDato($rowArt['numeroCuentaCompania'], "text"),
		valTpDato($rowArt['id_retencion_cheque'], "text"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
}

// BUSCA LOS ISLR DE LAS TRANSFERENCIAS
$queryArt = sprintf("SELECT 
	ret_cheque.id_retencion_cheque,
	transferencia.id_transferencia,
	transferencia.numero_transferencia,
	banco.nombreBanco,
	cuenta.numeroCuentaCompania,
	usuario.id_empleado
FROM te_transferencia transferencia
	INNER JOIN cuentas cuenta ON (transferencia.id_cuenta = cuenta.idCuentas)
	INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco)
	INNER JOIN pg_usuario usuario ON (transferencia.id_usuario = usuario.id_usuario)
	INNER JOIN te_retencion_cheque ret_cheque ON (transferencia.id_transferencia = ret_cheque.id_cheque)
		AND (ret_cheque.tipo_documento = 1);");
$rsArt = mysql_query($queryArt);
if (!$rsArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowArt = mysql_fetch_assoc($rsArt)) {
	$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
		id_documento = %s,
		id_empleado_creador = %s,
		banco_compania = %s,
		cuenta_compania = %s
	WHERE numero_documento LIKE %s
		AND tipo_pago LIKE 'ISLR';",
		valTpDato($rowArt['id_retencion_cheque'], "int"),
		valTpDato($rowArt['id_empleado'], "int"),
		valTpDato($rowArt['nombreBanco'], "text"),
		valTpDato($rowArt['numeroCuentaCompania'], "text"),
		valTpDato($rowArt['id_retencion_cheque'], "text"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
}

// ANULA LAS RETENCIONES DE ISLR
$updateSQL = sprintf("UPDATE cp_pagos_documentos SET
	id_documento = numero_documento,
	estatus = NULL,
	fecha_anulado = fecha_pago
WHERE tipo_pago LIKE 'ISLR'
	AND estatus = 1
	AND numero_documento NOT IN (SELECT id_retencion_cheque FROM te_retencion_cheque);");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);


mysql_query("COMMIT;");

echo "<h1>PAGOS ANULADOS CON EXITO, PAGOS RELACIONADOS CON EXITO</h1>";
?>