<?php
require_once("../../connections/conex.php");

session_start();

$currentPage = $_SERVER["PHP_SELF"];

require ('../../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../../controladores/xajax/');

include("ac_an_ajuste_inventario_vale_salida_imp.php");

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
    <?php $xajax->printJavascript('../../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../../style/styleRafk.css">
</head>

<body>
<table align="center" border="0" style="font-family:'Courier New', Courier, monospace; font-size:11pt;" width="840">
<tr>
	<td align="right">
    	<button type="button" class="noprint" onclick="window.print(); parent.$('hddImpresion').value = 'true';">Imprimir</button>
    </td>
</tr>
<tr>
	<td align="center">VALE DE SALIDA</td>
</tr>
<tr>
	<td>
        <table border="0" width="100%">
        <tr>
            <td width="28%"></td>
            <td width="14%"></td>
            <td width="14%"></td>
            <td width="10%"></td>
            <td align="right" width="20%">NRO. V. SALIDA:</td>
            <td align="left" id="tdIdValeSalida" width="14%"></td>
        </tr>
        <tr>
            <td colspan="4"></td>
            <td align="right">FECHA EMISIÓN:</td>
            <td align="left" id="tdFechaEmision"></td>
        </tr>
        <tr>
            <td align="left">CLIENTE:</td>
            <td align="left"><?php echo $spanClienteCxC; ?>:</td>
            <td align="right">NRO. PEDIDO:</td>
            <td align="left" colspan="3" id="tdIdPedidoCompra"></td>
        </tr>
        <tr>
            <td align="left" id="tdNombreCliente"></td>
            <td align="left" id="tdRifCliente"></td>
        </tr>
        <tr>
        	<td height="10"></td>
        </tr>
        <tr>
            <td align="left" colspan="6">DIRECCIÓN:</td>
        </tr>
        <tr>
            <td align="left" colspan="6" id="tdDireccionCliente">&nbsp;</td>
        </tr>
        </table>
	</td>
</tr>
<tr>
	<td height="15"></td>
</tr>
<tr>
	<td>
    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
        	<td align="center" style="border-top:2px solid #000000; border-bottom:2px solid #000000;">CÓDIGO</td>
            <td align="center" colspan="2" style="border-top:2px solid #000000; border-bottom:2px solid #000000;">DESCRIPCIÓN</td>
            <td align="center" style="border-top:2px solid #000000; border-bottom:2px solid #000000;">IMPORTE</td>
		</tr>
        <tr>
        	<td align="center" id="tdUnidadBasica" style="padding-top:20px;" valign="top"></td>
        	<td align="center" colspan="2" style="padding-top:20px;" valign="top">
            	<table border="0" width="60%">
                <tr>
                	<td align="right" width="10%">MARCA:</td>
                    <td align="left" id="tdMarca" width="50%"></td>
                </tr>
                <tr>
                	<td align="right">MODELO:</td>
                    <td align="left" id="tdModelo"></td>
                </tr>
                <tr>
                	<td align="right">VERSIÓN:</td>
                    <td align="left" id="tdVersion"></td>
                </tr>
                	<td align="right"><?php echo $spanPlaca; ?>:</td>
                    <td align="left" id="tdPlaca"></td>
                </tr>
                	<td align="right">AÑO:</td>
                    <td align="left" id="tdAno"></td>
                </tr>
                	<td align="right">CARROCERIA:</td>
                    <td align="left" id="tdCarroceria"></td>
                </tr>
                	<td align="right">MOTOR:</td>
                    <td align="left" id="tdMotor"></td>
                </tr>
                	<td align="right">COLOR:</td>
                    <td align="left" id="tdColor"></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td colspan="3"></td>
            <td><hr></td>
        </tr>
        <tr>
        	<td colspan="2"></td>
        	<td align="right">MONTO VEHÍCULO:</td>
            <td align="right" id="tdMontoVehiculo"></td>
        </tr>
        <tr height="450">
        	<td colspan="4">&nbsp;</td>
        </tr>
        <tr>
        	<td colspan="4"><hr></td>
        </tr>
        <tr>
        	<td align="left" colspan="2">OBSERVACIONES:</td>
            <td align="right">SUBTOTAL:</td>
            <td align="right" id="tdSubTotal"></td>
        </tr>
        <tr>
        	<td align="left" colspan="2" id="tdObservacion"></td>
        	<td align="right">TOTAL:</td>
        	<td align="right" id="tdTotal"></td>
        </tr>
        <tr>
        	<td width="15%"></td>
        	<td width="45%"></td>
        	<td width="20%"></td>
        	<td width="20%"></td>
        </tr>
        </table>
	</td>
</tr>
</table>
</body>
</html>
<script>
xajax_cargarValeSalida('<?php echo $_GET['id']; ?>');
</script>