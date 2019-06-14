<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_pagos_cargados_dia_contado"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
} else {
	header("location: cjrs_pagos_cargados_dia.php?tipoPago=1");
}
/* Fin Validación del Módulo */
?>
