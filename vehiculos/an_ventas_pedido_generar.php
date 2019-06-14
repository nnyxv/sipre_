<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");
validaModulo("an_pedido_venta_list",insertar);

$loadscript = " onload=\"";
	$loadscript .= "percent(); asignarPrecio();";
$loadscript .= "\"";

include "an_ventas_pedido_insertar.php";
?>