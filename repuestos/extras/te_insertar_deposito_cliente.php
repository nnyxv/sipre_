<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");

mysql_query("START TRANSACTION;");

// BUSCA LOS DEPOSITOS QUE NO ESTAN INCLUIDOS EN TESORERIA
$queryArt = sprintf("SELECT q.*
FROM (
	SELECT
		id_empresa,
		idPago,
		fechaPago,
			cxc_pago.formaPago,
		numeroDocumento AS numero_documento_pago,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
		'an_pagos' AS tabla,
		'an_det_pagos_deposito_factura' AS tabla_deposito,
		'idPago' AS campo_id_pago,
		(SELECT caja.descripcion FROM caja WHERE caja.idCaja = cxc_pago.idCaja) AS nombre_caja
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
	WHERE formaPago = 3 AND MONTH(`fechaPago`) = '04' AND YEAR(`fechaPago`) = '2016'

	UNION

	SELECT
		id_empresa,
		idPago,
		fechaPago,
			cxc_pago.formaPago,
		numeroDocumento AS numero_documento_pago,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
		'sa_iv_pagos' AS tabla,
		'an_det_pagos_deposito_factura' AS tabla_deposito,
		'idPago' AS campo_id_pago,
		(SELECT caja.descripcion FROM caja WHERE caja.idCaja = cxc_pago.idCaja) AS nombre_caja
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
	WHERE formaPago = 3 AND MONTH(`fechaPago`) = '04' AND YEAR(`fechaPago`) = '2016'

	UNION

	SELECT
		id_empresa,
		cxc_pago.id_det_nota_cargo AS idPago,
		fechaPago,
			cxc_pago.idFormaPago,
		numeroDocumento AS numero_documento_pago,
			cxc_pago.cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.monto_pago AS montoPagado,
		'cj_det_nota_cargo' AS tabla,
		'cj_det_pagos_deposito_nota_cargo' AS tabla_deposito,
		'id_det_nota_cargo' AS campo_id_pago,
		(SELECT caja.descripcion FROM caja WHERE caja.idCaja = cxc_pago.idCaja) AS nombre_caja
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
	WHERE idFormaPago = 3 AND MONTH(fechaPago) = '04' AND YEAR(`fechaPago`) = '2016'

	UNION

	SELECT
		id_empresa,
		cxc_pago.idDetalleAnticipo AS idPago,
		fechaPagoAnticipo,
			cxc_pago.id_forma_pago,
		numeroControlDetalleAnticipo AS numero_documento_pago,
			cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
			cxc_pago.idCaja,
			cxc_pago.montoDetalleAnticipo AS montoPagado,
		'cj_cc_detalleanticipo' AS tabla,
		'cj_cc_det_pagos_deposito_anticipos' AS tabla_deposito,
		'idDetalleAnticipo' AS campo_id_pago,
		(SELECT caja.descripcion FROM caja WHERE caja.idCaja = cxc_pago.idCaja) AS nombre_caja
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
	WHERE id_forma_pago = 3 AND MONTH(fechaPagoAnticipo) = '04' AND YEAR(`fechaPagoAnticipo`) = '2016') AS q
WHERE q.numero_documento_pago NOT IN (SELECT dep.numero_deposito_banco FROM te_depositos dep);");
$rsPago = mysql_query($queryArt);
if (!$rsPago) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowPago = mysql_fetch_assoc($rsPago)) {
	$idFormaPago = $rowPago['formaPago'];
	$nombreTabla = $rowPago['tabla_deposito'];
	$idTabla = $rowPago['campo_id_pago'];
	
	$idEmpresa = $rowPago['id_empresa'];
	$idCajaPpal = $rowPago['idCaja'];
	$nombreCajaPpal = $rowPago['nombre_caja'];
	$fechaApertura = $rowPago['fechaPago'];
	$idUsuario = 8;
	
	if ($idFormaPago == 3) { // 3 = Deposito
		$montoTotalEfectivoDeposito = 0;
		$montoTotalChequeDeposito = 0;
		
		// CONSULTA MONTO TOTAL EFECTIVO Y CHEQUE
		$sqlSelectMontos = sprintf("SELECT SUM(monto) AS monto, idFormaPago FROM %s WHERE %s = %s GROUP BY idFormaPago",
			$nombreTabla,
			$idTabla,
			valTpDato($rowPago['idPago'], "int"));
		$rsSelectMontos = mysql_query($sqlSelectMontos);
		if (!$rsSelectMontos) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowSelectMontos = mysql_fetch_array($rsSelectMontos)){
			if ($rowSelectMontos['idFormaPago'] == 1) {
				$montoTotalEfectivoDeposito = $rowSelectMontos['monto'];
			} else if ($rowSelectMontos['idFormaPago'] == 2) {
				$montoTotalChequeDeposito = $rowSelectMontos['monto'];
			}
		}
		
		// CONSULTA PARA EL ID DE LA CUENTA
		$queryCuenta = sprintf("SELECT idCuentas FROM cuentas WHERE numeroCuentaCompania LIKE %s",
			valTpDato($rowPago['cuentaEmpresa'], "text"));
		$rsCuenta = mysql_query($queryCuenta);
		if (!$rsCuenta) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCuenta = mysql_fetch_array($rsCuenta);
		
		// CONSULTA CORRELATIVO NUMERO DE FOLIO
		$sqlSelectFolioTesoreriaDeposito = sprintf("SELECT numero_actual FROM te_folios WHERE id_folios = 2");
		$rsSelectFolioTesoreriaDeposito = mysql_query($sqlSelectFolioTesoreriaDeposito);
		if (!$rsSelectFolioTesoreriaDeposito) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowSelectFolioTesoreriaDeposito = mysql_fetch_array($rsSelectFolioTesoreriaDeposito);
		
		$folioDeposito = $rowSelectFolioTesoreriaDeposito['numero_actual'];
		
		// AUMENTAR EL CORRELATIVO DEL FOLIO
		$sqlUpdateFolioTesoreriaDeposito = "UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 2";
		$rsUpdateFolioTesoreriaDeposito = mysql_query($sqlUpdateFolioTesoreriaDeposito);
		if (!$rsUpdateFolioTesoreriaDeposito) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$observacionDepositos = trim(sprintf('INGRESO %s DIA %s DEPOSITO CLIENTE (%s)',
			$nombreCajaPpal,
			date(spanDateFormat,strtotime($fechaApertura)),
			$rowPago['numero_documento_pago']));
		
		// INSERTAR EL DEPOSITO EN TESORERIA
		$sqlInsertDepositoTesoreria = sprintf("INSERT INTO te_depositos (id_numero_cuenta, fecha_registro, fecha_aplicacion, numero_deposito_banco, estado_documento, origen, id_usuario, monto_total_deposito, id_empresa, desincorporado, monto_efectivo, monto_cheques_total, observacion, folio_deposito)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($rowCuenta['idCuentas'], "int"),
			valTpDato($fechaApertura, "date"),
			valTpDato($fechaApertura, "date"),
			valTpDato($rowPago['numero_documento_pago'], "text"),
			valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
			valTpDato($idCajaPpal, "int"), // 0 = Tesoreria, 1 = Caja Vehiculo, 2 = Caja Repuestos y Servicios, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos (Relacion Tabla te_origen)
			valTpDato($idUsuario, "int"),
			valTpDato($rowPago['montoPagado'], "double"),
			valTpDato($idEmpresa, "int"),
			valTpDato(1, "int"), // 0 = Desincorporado, 1 = Activo
			valTpDato($montoTotalEfectivoDeposito, "int"),
			valTpDato($montoTotalChequeDeposito, "int"),
			valTpDato($observacionDepositos, "text"),
			valTpDato($folioDeposito, "int"));
		$rsInsertDepositoTesoreria = mysql_query($sqlInsertDepositoTesoreria);
		if (!$rsInsertDepositoTesoreria) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idDeposito = mysql_insert_id();
		
		echo "<pre>".$sqlInsertDepositoTesoreria."</pre>";
		
		// INSERTAR DETALLES DEPOSITO
		$sqlSelectDetallesDepositoPago = sprintf("SELECT idBanco, numero_cuenta, numero_cheque, monto FROM %s WHERE %s = %s AND idFormaPago = 2",
			$nombreTabla,
			$idTabla,
			valTpDato($rowPago['idPago'], "int"));
		$rsSelectDetallesDepositoPago = mysql_query($sqlSelectDetallesDepositoPago);
		if (!$rsSelectDetallesDepositoPago) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowSelectDetallesDepositoPago = mysql_fetch_array($rsSelectDetallesDepositoPago)){
			$sqlInsertDetalleDeposito = sprintf("INSERT INTO te_deposito_detalle (id_deposito, id_banco, numero_cuenta_cliente, numero_cheques, monto)
			VALUES (%s, %s, %s, %s, %s)",
				valTpDato($idDeposito, "int"),
				valTpDato($rowSelectDetallesDepositoPago['idBanco'], "int"),
				valTpDato($rowSelectDetallesDepositoPago['numero_cuenta'], "text"),
				valTpDato($rowSelectDetallesDepositoPago['numero_cheque'], "text"),
				valTpDato($rowSelectDetallesDepositoPago['monto'], "double"));
			$rsInsertDetalleDeposito = mysql_query($sqlInsertDetalleDeposito);
			if (!$rsInsertDetalleDeposito) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		// INSERTAR EL MOVIMIENTO EN LA TABLA DE ESTADO_CUENTA
		$insertSQL = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales, id_conciliacion)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato("DP", "text"), // DP = Deposito, NC = Nota Credito, ND = Nota Debito, CH = Cheque, CH ANULADO = Cheque Anulado, TR = Transferencia
			valTpDato($idDeposito, "int"),
			valTpDato("NOW()", "campo"),
			valTpDato($rowCuenta['idCuentas'], "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($rowPago['montoPagado'], "double"),
			valTpDato(1, "int"), // 1 = Suma, 0 = Resta
			valTpDato($rowPago['numero_documento_pago'], "text"),
			valTpDato(1, "int"), // 1 = Activo, 0 = Desincorporado
			valTpDato($observacionDepositos, "text"),
			valTpDato(2, "int"), // 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada (Relacion Tabla te_estados_principales)
			valTpDato(0, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".$insertSQL."</pre>";
		
		// AFECTAR EL SALDO EN CUENTA
		$updateSQL = sprintf("UPDATE cuentas SET saldo_tem = saldo_tem + %s WHERE idCuentas = %s;",
			valTpDato($rowPago['montoPagado'], "double"),
			valTpDato($rowCuenta['idCuentas'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<br><br>";
	}
}

mysql_query("COMMIT;");

echo "<h1>DEPOSITOS INGRESADOS CON EXITO</h1>";
?>