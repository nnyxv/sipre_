<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
$raiz = "../";
require_once("../../connections/conex.php");

session_start();

require ('../../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../../controladores/xajax/');

include("../../controladores/ac_if_generar_cierre_mensual.php");
include("../../controladores/ac_iv_general.php"); 

$xajax->processRequest();

$xajax->printJavascript('../../controladores/xajax/');

mysql_query("START TRANSACTION;");

$Result1 = actualizarSaldos();
if ($Result1[0] != true && strlen($Result1[1]) > 0) { $arrayLoteInvalido[] = ($Result1[1]); }

$Result1 = actualizarMovimientoTotal();
if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }

mysql_query("COMMIT;");

echo "<h1>DETALLES GENERADOS CON EXITO</h1>";

echo "<pre>".((isset($arrayLoteInvalido)) ? implode(", ", $arrayLoteInvalido) : "")."</pre>";
?>