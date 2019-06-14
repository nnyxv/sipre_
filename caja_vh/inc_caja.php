<?php
$arrayApertCajaPpal = array(1 => "an_apertura", 2 => "sa_iv_apertura");
$arrayCierreCajaPpal = array(1 => "an_cierredecaja", 2 => "sa_iv_cierredecaja");
$arrayEncabezadoPagoFA = array(1 => "cj_cc_encabezado_pago_v", 2 => "cj_cc_encabezado_pago_rs");
$arrayEncabezadoPagoND = array(1 => "cj_cc_encabezado_pago_nc_v", 2 => "cj_cc_encabezado_pago_nc_rs");
$arrayDetallePagoFA = array(1 => "an_pagos", 2 => "sa_iv_pagos");

$idCajaPpal = 1; // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
$idModuloPpal = "2,4,5";
$apertCajaPpal = $arrayApertCajaPpal[$idCajaPpal];
$cierreCajaPpal = $arrayCierreCajaPpal[$idCajaPpal];

$queryCaja = sprintf("SELECT * FROM caja WHERE caja.idCaja = %s;",
	valTpDato($idCajaPpal, "int"));
$rsCaja = mysql_query($queryCaja);
if (!$rsCaja) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowCaja = mysql_fetch_assoc($rsCaja);

$nombreCajaPpal = $rowCaja['descripcion'];
?>