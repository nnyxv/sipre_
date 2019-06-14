<?php

require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require("../inc_sesion.php");
if(!(validaAcceso("alquiler"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = '../'; </script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Alquiler</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_alquiler.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
        <span class="textoNaranjaOscuroNegrita_24px">SISTEMA DE ALQUILER</span>
        <br>
        <span class="textoGrisNegrita_13px">Versión <?php echo cVERSION; ?></span>
	</div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>