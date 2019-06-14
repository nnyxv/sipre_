<?php
require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Documento sin título</title>
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
</head>

<body style="background-color:#000">
<?php
if (session_status() === PHP_SESSION_ACTIVE) { // si las sesiones están habilidatas, y existe una
	echo "<script>window.open('".$raiz."index.php');</script>";
	session_unset();
	session_destroy();
}
?>
</body>
</html>