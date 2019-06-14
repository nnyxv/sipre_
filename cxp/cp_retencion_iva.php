<?php
require_once("../connections/conex.php");
require_once("../control/iforms.inc.php");

@session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if(!(validaAcceso("cp_retencion_iva"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Retención Impuesto</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorPagar.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function validarFrmBuscar(){
		if (validarCampo('txtFechaDesde','t','') == true
		&& validarCampo('txtFechaHasta','t','') == true){
			window.open("reportes/cp_retenciones_iva_excel.php?valBusq=" + byId('txtFechaDesde').value + "|" + byId('txtFechaHasta').value);
		} else {
			validarCampo('txtFechaDesde','t','')
			validarCampo('txtFechaHasta','t','')
			
			alert("Los Campos en Rojo Son Requeridos");
		}
	}			
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_pagar.php"); ?></div>
 	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCuentasPorPagar">Retención Impuesto</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
            	<table width="100%">
                <tr>
                	<td>
                    <fieldset><legend class="legend">Rango de Fecha</legend>
                    	<table>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Desde:</td>
                            <td><input type="text" name="txtFechaDesde" id="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo" width="120">Hasta:</td>
                            <td><input type="text" name="txtFechaHasta" id="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td align="right"><hr>
	                    <button type="button" onclick="validarFrmBuscar();">Generar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        </table>
    </div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
byId('txtFechaDesde').className = 'inputHabilitado';
byId('txtFechaHasta').className = 'inputHabilitado';

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   $("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"torqoise"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"torqoise"
	});
};
</script>