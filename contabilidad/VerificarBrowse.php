<?php
session_start();
//$TexUsuario =  $_REQUEST["TexUsuario"];
//$TexClave =  $_REQUEST["TexClave"];
$TexUsuario =  $_SESSION["nombreUsuarioSysGts"];
$TexClave =  "1234"; ?>

<html>
<body>
<form name="form1" id="form1">
    <input type="hidden" name="TexUsuario" value="<?=$TexUsuario?>">
    <input type="hidden" name="TexClave"  value="<?=$TexClave?>">
</form>                    	
</body>
</html>

<script language='Javascript'>
var browserName = navigator.appName;
var browserVer = parseInt(navigator.appVersion);
if (browserName == "Netscape") {
	document.form1.target='_self';
	document.form1.method='post';
	document.form1.action='VerificarAccesoSistema.php';
	document.form1.submit();
} else {
	if (browserName == "Microsoft Internet Explorer") {
		alert('Actualmente SIPRE no se puede ejecutar en Internet Explorer');
		document.form1.target='_self';
		document.form1.method='post';
		document.form1.action='FrmIzquierda.php';
		document.form1.submit();
	}
}
</script>