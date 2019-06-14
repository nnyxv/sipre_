<?php
require_once("../connections/conex.php");

session_start();

$currentPage = $_SERVER["PHP_SELF"];

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_an_transferencia_almacen_imp.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Vale de Salida</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
</head>

<body>
<table align="center" border="0" style="font-family:'Courier New', Courier, monospace; font-size:11pt;" width="840">
<tr>
	<td align="right">
    	<button type="button" class="noprint" onclick="window.print(); parent.byId('hddImpresion').value = 'true';">Imprimir</button>
    </td>
</tr>
<tr>
	<td class="tituloPaginaVehiculos">Transferencia de Almac&eacute;n</td>
</tr>
<tr>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>
    	<table width="100%">
        <tr>
            <td align="center">
                En el día <b><span id="spnFecha"></span></b> fue cambiada la unidad física <b><span id="spnIdUnidadFisica"></span></b> con <?php echo $spanPlaca; ?> <b><span id="spnPlaca"></span></b> del<br>Almacen: <b><span id="spnAlmacenAnterior"></span></b> por: <b><span id="spnAlmacenDestino"></span></b>, Estado de Venta: <b><span id="spnEstadoVentaAnterior"></span></b> por: <b><span id="spnEstadoVentaDestino"></span></b>
            </td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
            <td align="center">
                <table width="100%">
                <tr>
                    <td align="center" width="50%">Elaborado por:</td>
                    <td align="center" width="50%">Autorizado por:</td>
                </tr>
                <tr>
                    <td align="center" id="tdElaborado"></td>
                    <td align="center" id="tdAutorizado"></td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
	</td>
</tr>
</table>
</body>
</html>
<script>
xajax_cargarTransferenciaAlmacen('<?php echo $_GET['id']; ?>');
</script>