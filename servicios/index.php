<?php
@session_start();
define('PAGE_PRIV','se');
require_once("../inc_sesion.php");
require_once ("../connections/conex.php");

	
@require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');
	$cancelXajax=true;
	//require_once("control/main_control.inc.php");	
	
	function load_page(){
		$r = new xajaxResponse();
		$r->setCharacterEncoding('ISO-8859-1');
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			$r->script('window.location="../index2.php";');
						
			return $r;
		}
                
		return $r;
	}
        
	$xajax->register(XAJAX_FUNCTION,"load_page");
	$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!-- saved from url=(0014)about:internet -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>.: SIPRE <?php echo cVERSION; ?> :. Servicios</title>
<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
td img {display: block;}body {
	/*background-image: url(img3/fondo.jpg);
	background-repeat: repeat-x;*/
}
.tabla titulo *{
	font-family:Verdana, Arial, Helvetica, sans-serif;
}
.tabla_titulo td{
	text-align:center;
}
.tabla_titulo thead td{
	font-size:12pt;
	font-weight:bold;
}
#floter{	
	background-color:#FFFFFF;
	font-family:Verdana, Arial, Helvetica, sans-serif;
	font-size: 11px;
	margin: 0px auto;
	text-align:center;
	width:100%;
}


.numeroVersion{
-webkit-transform: rotate(-10deg); -moz-transform: rotate(-10deg);
filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=1.5); display: inline-block;
font-size:16px;
color:#060;
font-style:italic;
font-weight: bold;
text-shadow: 1px 2px 3px #666;
/*text-shadow: 3px 2px 0px rgba(150, 150, 150, 1);*/
}


</style>
<?php
	//includeScripts();
	//getXajaxJavascript();
	 $xajax->printJavascript('../controladores/xajax/');
	//includeModalBox();
?>

    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
</head>
<body id="capa">
<div id="floter">
<?php include("banner_servicios.php"); ?>
</div>

<div id="divInfo" class="print" style="vertical-align:middle;">
    	<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
		<div id="floter">
                    <span class="textoNegroNegrita_24px">SISTEMA SERVICIOS</span>
                    <br />
                    <span class="textoGrisNegrita_13px">Versi&oacute;n <numeroVersion class="numeroVersionNO"><?php echo cVERSION; ?></numeroVersion></span>
		</div><br />
		
</div>


<div style=" background:#FFFFFF; margin: auto;">
<?php include("pie_pagina.php"); ?>
</div>

<script type="text/javascript">
	xajax_load_page();
</script>
</body>
</html>
