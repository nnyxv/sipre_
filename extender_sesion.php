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
	<title>.: SIPRE - Continuar Sesion:.</title>
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
</head>
<body>
	<script type="text/javascript">
	// LLAMANDO A LA RUTINA QUE DETERMINA LA CADUCACION DE LA SESION	
	/*var r = confirm("<?php echo ("¡Queda poco tiempo de sesión! ¿Desea continuar con la sesión activa?"); ?>");
	if (r == true) {
		popupSession = null;
		xajax_controlSession(1);
		
		window.parent.opener.iniciarConteo();
	} else {
		xajax_controlSession(0);
		window.opener.location.href='<?php echo $raiz."index.php"; ?>';
	}*/
	</script>
</body>
</html>
