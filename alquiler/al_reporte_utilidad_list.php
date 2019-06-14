<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("al_reporte_utilidad_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_al_reporte_utilidad_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Alquiler - Reporte de Utilidad</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragAlquiler.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_alquiler.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaAlquiler">Histórico de Reporte de Utilidad</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
            <td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <button type="button" onclick="xajax_exportarUtilidadPdf(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                    	<button type="button" onclick="xajax_exportarUtilidadExcel(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
                </tr>
                </table>
            
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo">Movimiento:</td>
                    <td>
                    	<select id="lstVerMovimiento" name="lstVerMovimiento" onchange="byId('btnBuscar').click();">
                        	<option value="-1" selected="selected">[ Seleccione ]</option>
                            <option  value="1">Vehículos con Ventas</option>
                            <option value="2">Vehículos con Gastos</option>
                            <option value="3" selected="selected">Vehiculos con Ventas/Gastos</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">ver:</td>
                    <td>
                    	<select id="lstVerDcto" name="lstVerDcto" onchange="byId('btnBuscar').click();">
                        	<option value="-1" selected="selected">[ Seleccione ]</option>
                            <option  value="1">Factura (Sin Devoluci&oacute;n)</option>
                            <option value="2">Factura (Con Devoluci&oacute;n)</option>                         
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmlistaUtilidad" name="frmlistaUtilidad" style="margin:0">
            	<div id="divlistaUtilidad" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                        <tr>
                            <td>Ventas = Ventas netas (subtotal - descuento) - Devoluciones (Notas de créditos netas)</td>
                        </tr>
                        <tr>
                            <td>Costo = Costo asociado por unidad en Compra</td>
                        </tr>
                        <tr>
                            <td>Utilidad = Venta - Costo</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        <tr>
        	<td align="right"><hr>
            	<table>
                <tr align="right">
                	<td class="tituloCampo" width="120">Total Venta:</td>
                    <td width="150"><span id="spnTotalVenta"></span></td>
				</tr>
                <tr align="right">
                    <td class="tituloCampo">Total Costo:</td>
                    <td><span id="spnTotalCosto"></span></td>
				</tr>
                <tr align="right" class="trResaltarTotal">
                    <td class="tituloCampo">Total Utilidad:</td>
                    <td><span id="spnTotalUtilidad"></span></td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
    </div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
byId('txtFechaDesde').value = "<?php echo date("01-m-Y"); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";

byId('txtCriterio').className = "inputHabilitado";
byId('lstVerDcto').className = "inputHabilitado";
byId('lstVerMovimiento').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   $("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"naranja"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"naranja"
	});
};

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpleado('','lstEmpleadoVendedor','tdlstEmpleadoVendedor');
xajax_listaUtilidad(0,'id_unidad_fisica','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|'+byId('txtFechaDesde').value+'|'+byId('txtFechaHasta').value+'||3');
</script>