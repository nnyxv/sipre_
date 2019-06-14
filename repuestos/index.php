<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if(!(validaAcceso("repuestos"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
	
// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
/*$Result1 = actualizarSaldos();
if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }*/

// ACTUALIZA EL DESCUENTO EN EL KARDEX
$insertSQL = sprintf("UPDATE iv_kardex SET 
	subtotal_descuento = (porcentaje_descuento * precio) / 100
WHERE tipo_movimiento IN (3)
	AND subtotal_descuento > 0;");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// ACTUALIZA EL DESCUENTO EN EL DETALLE DEL MOVIMIENTO
$insertSQL = sprintf("UPDATE iv_movimiento_detalle SET 
	subtotal_descuento = (porcentaje_descuento * precio) / 100
WHERE id_movimiento IN (SELECT id_movimiento FROM iv_movimiento WHERE id_tipo_movimiento IN (3))
	AND subtotal_descuento > 0;");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorPagar.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
</head>

<body>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
        <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
        <span class="textoAzulNegrita_24px">SISTEMA DE REPUESTOS</span>
        <br>
        <span class="textoGrisNegrita_13px">Versión 3.0</span>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>