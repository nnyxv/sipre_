<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
/*if(!(validaAcceso("re"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}*/
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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Informe Gerencial</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleInforme.css">
    
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    
    <script src="../js/login-modernizr/modernizr.custom.63321.js"></script>
    <link rel="stylesheet" type="text/css" href="../js/login-modernizr/font-awesome.css" />
    
    <style>
	body {
		background: #365A96 url(../img/login/blurred<?php echo rand(1, 13); ?>.jpg) no-repeat center top;
		background-attachment:fixed;
		-webkit-background-size: cover;
		-moz-background-size: cover;
		background-size: cover;
		padding:10px;
	}
	</style>
</head>

<body>
<div>
	<div><?php include("banner_informe.php"); ?></div>
    
    <div id="divInfo" style="text-align:center">
    	<br><br><br><br><br><br><br><br><br><br>
    	<p style="font-size:24px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; text-shadow:3px 3px 0 rgba(51,51,51,0.8);">
        	<span style="display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;">INFORME GERENCIAL</span>
            <br>
            <span style="font-size:18px; display:inline-block; text-transform:uppercase; color:#B7D154; padding-left:2px;">Versión 3.0</span>
        </p>
    </div>
	
    <div><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>