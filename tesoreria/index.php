<?php
require_once ("../connections/conex.php");

@session_start();
/* Validación del Módulo */
include('../inc_sesion.php');
if (!validaAcceso("te")){
	echo "
	<script type=\"text/javascript\">
		alert('Acceso Denegado');
		window.location='../index2.php';
	</script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_index.php");

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria</title>
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
</head>

<body class="bodyErp">
<div id="divGeneralPorcentaje" align="center">
	<div class="noprint" align="center">
		<?php include ('banner_tesoreria.php'); ?>
    </div>
    <div id="divInfo" class="print" style="vertical-align:middle" align="center">
    	<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
        <span class="textoVinotintoNegrita_24px">SISTEMA DE TESORERIA</span>
        <br/>
        <span class="textoGrisNegrita_13px">Versión <?php echo cVERSION; ?></span>
    	
        <br/><br/>
        

	</div>
    <div class="noprint" align="center">    
    	<?php include ('pie_pagina.php'); ?>
    </div>    
</div>
</body>
</html>