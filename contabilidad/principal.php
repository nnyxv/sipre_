<?php
session_start();

require ('xajax/xajax_core/xajax.inc.php');

$xajax = new xajax();

$xajax->configure('javascript URI', '/xajax/');

include "logout.php";

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE 3.0 :. Contabilidad</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorPagar.css" />

	<script language="JavaScript" type="text/JavaScript">
    <!--
    function MM_reloadPage(init) { //reloads the window if Nav4 resized
		if (init==true) with (navigator) {
			if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {
				document.MM_pgW=innerWidth; document.MM_pgH=innerHeight; onresize=MM_reloadPage;
			}
		} else if (innerWidth!=document.MM_pgW || innerHeight != document.MM_pgH)
			location.reload();
    }
    MM_reloadPage(true);
    //-->
    
    function EjeRecoger(){
		/*var oFrame	=	parent.document.all("lfFrame");
		var oFrameCen=	parent.document.all("CeFrame");*/
		if (document.Principal.BtnRecoger.value == "- Menu"){
			document.getElementById("lfFrame").width=0;
			document.getElementById("CeFrame").width= 1000;
			document.Principal.BtnRecoger.value = "+ Menu";
		} else {
			document.getElementById("lfFrame").width=240;
			document.getElementById("CeFrame").width= 760;
			document.Principal.BtnRecoger.value = "- Menu";
		}
    }
	
    function EjePrincipal(){
		/*document.Principal.Method='post';
		document.Principal.action='index.htm';
		document.Principal.submit();*/
		location.href='../index2.php'
    }
    </script>
</head>
<body style="margin:0px; padding:0px">
	<a id="aMenuErp" href="../index2.php"></a>
<form name="Principal" style="margin:0px">
    <input type="button" disabled name="BtnRecoger" onClick="EjeRecoger();" style="display:none" value="- Menu">  
    <input type="button" name="BtnPrincipal" onClick="xajax_salir();" style="display:none" value="Principal" >  
    
    <iframe id="topFrame" name="topFrame" src="FrmArriba.htm" style="display:none"></iframe>
    <iframe id="lfFrame" name="leftFrame" src="VerificarBrowse.php" style="display:none"></iframe>
    <iframe id="CeFrame" name="mainFrame" src="VerificarBrowse.php" style="height:1000px; border:0px; margin:0px; padding:0px;" width="100%"></iframe>
</form>
</body>
</html>