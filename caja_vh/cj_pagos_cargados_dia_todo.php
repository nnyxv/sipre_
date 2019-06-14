<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_pagos_cargados_dia_todo"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
} else {
	header("location: cj_pagos_cargados_dia.php?tipoPago=0,1");
}
/* Fin Validación del Módulo */
?>