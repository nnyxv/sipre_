<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");

mysql_query("START TRANSACTION;");

// BUSCA LAS RETENCIONES QUE NO TIENEN COMPROBANTE
$queryArt = sprintf("SELECT
	cxc_fact.idFactura,
	cxc_fact.idCliente,
	cxc_fact.numeroFactura,
	cxc_fact.numeroControl,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.montoTotalFactura,
	cxc_fact.subtotalFactura,
	cxc_fact.baseImponible,
	cxc_fact.porcentajeIvaFactura,
	cxc_fact.calculoIvaFactura,
	cxc_fact.base_imponible_iva_lujo,
	cxc_fact.porcentajeIvaDeLujoFactura,
	cxc_fact.calculoIvaDeLujoFactura,
	cxc_pago.fechaPago,
	cxc_pago.numeroDocumento,
	cxc_pago.montoPagado
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
WHERE cxc_pago.formaPago = 9
	AND cxc_fact.idFactura NOT IN (SELECT retencion_det.idFactura
									FROM cj_cc_retencioncabezera retencion
                                    	INNER JOIN cj_cc_retenciondetalle retencion_det ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera))
	AND cxc_pago.estatus IN (1);");
$rsArt = mysql_query($queryArt);
if (!$rsArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowFactura = mysql_fetch_assoc($rsArt)) {
	$fechaRegistroPago = $rowFactura['fechaPago'];
	$idCliente = $rowFactura['idCliente'];
	
	$porcImpuesto = $rowFactura['porcentajeIvaFactura'] + $rowFactura['porcentajeIvaDeLujoFactura'];
	$subTotalImpuesto = $rowFactura['calculoIvaFactura'] + $rowFactura['calculoIvaDeLujoFactura'];
	$porcRetenido = ($subTotalImpuesto > 0) ? $rowFactura['montoPagado'] * 100 / $subTotalImpuesto : 0;
	
	// BUSCA SI YA EXISTE UN COMPROBANTE CON LA INFORMACION INGRESADA
	$queryRetencionCabecera = sprintf("SELECT * FROM cj_cc_retencioncabezera retencion
	WHERE retencion.numeroComprobante LIKE %s
		AND retencion.fechaComprobante = %s
		AND retencion.anoPeriodoFiscal = %s
		AND retencion.mesPeriodoFiscal = %s
		AND retencion.idCliente = %s;",
		valTpDato($rowFactura['numeroDocumento'], "text"),
		valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
		valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
		valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
		valTpDato($idCliente, "int"));
	$rsRetencionCabecera = mysql_query($queryRetencionCabecera);
	if (!$rsRetencionCabecera) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRetenciones = mysql_num_rows($rsRetencionCabecera);
	$rowRetencionCabecera = mysql_fetch_array($rsRetencionCabecera);
	
	echo "<span style=\"size:8px;\">".$queryRetencionCabecera."<br><br></span>";
	
	if ($totalRowsRetenciones > 0) {
		$idRetencionCabecera = $rowRetencionCabecera['idRetencionCabezera'];
	} else {
		$insertSQL = sprintf("INSERT INTO cj_cc_retencioncabezera (numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idCliente, idRegistrosUnidadesFisicas)
		VALUES (%s, %s, %s, %s, %s, %s)",
			valTpDato($rowFactura['numeroDocumento'], "text"),
			valTpDato(date("Y-m-d", strtotime($fechaRegistroPago)), "date"),
			valTpDato(date("Y", strtotime($fechaRegistroPago)), "text"),
			valTpDato(date("m", strtotime($fechaRegistroPago)), "text"),
			valTpDato($idCliente, "int"),
			valTpDato(0, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idRetencionCabecera = mysql_insert_id();
	}
	
	$insertSQL = sprintf("INSERT INTO cj_cc_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($idRetencionCabecera, "int"),
		valTpDato($rowFactura['fechaRegistroFactura'], "date"),
		valTpDato($rowFactura['idFactura'], "int"),
		valTpDato($rowFactura['numeroControl'], "text"),
		valTpDato(" ", "text"),
		valTpDato(" ", "text"),
		valTpDato(" ", "text"),
		valTpDato(" ", "text"),
		valTpDato($rowFactura['montoTotalFactura'], "real_inglesa"),
		valTpDato($rowFactura['subtotalFactura'], "real_inglesa"),
		valTpDato($rowFactura['baseImponible'], "real_inglesa"),
		valTpDato($porcImpuesto, "real_inglesa"),
		valTpDato($subTotalImpuesto, "real_inglesa"),
		valTpDato($rowFactura['montoPagado'], "real_inglesa"),
		valTpDato($porcRetenido, "int"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	echo "<span style=\"size:8px;\">".$insertSQL."<br><br></span>";
}

mysql_query("COMMIT;");

echo "<h1>COMPROBANTES REGISTRADOS CON EXITO</h1>";
?>