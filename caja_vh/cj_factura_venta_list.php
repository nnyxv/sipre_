<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_factura_venta_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_factura_venta_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Pedidos y/o Ordenes por Facturar</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>
	
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
		function validarDevolverContrato(idContrato){			
			if (confirm('¿Seguro desea Devolver el Contrato?') == true) {
				xajax_devolverContrato(idContrato);
			}
		}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCaja">Pedidos y/o Ordenes por Facturar</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr class="noprint">
			<td>
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr id="trEmpresa" align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;Desde:&nbsp;</td>
                            <td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                            <td>&nbsp;Hasta:&nbsp;</td>
                            <td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Vendedor:</td>
                    <td id="tdlstEmpleado"></td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
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
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Vehículos</a></li>
                        <li><a href="#">Alquiler</a></li>
                        <li><a href="#">Financiamiento</a></li>
                    </ul>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                    	<table align="left" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <button type="button" onclick="xajax_imprimirPedido(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                            </td>
                        </tr>
                        </table>
                        
                        <table border="0" width="100%">
                        <tr>
                            <td>
                            <form id="frmListaPedidoVenta" name="frmListaPedidoVenta" style="margin:0">
                                <div id="divListaPedidoVenta" style="width:100%"></div>
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
                                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Pedido Autorizado</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/book_next.png"/></td><td>Facturar</td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </div>
                    <div class="pane">
                    	<table align="left" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
               	             <button type="button" onclick="xajax_imprimirPedidoAlquiler(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                            </td>
                        </tr>
                        </table>
                        
                    	<table border="0" width="100%">
                        <tr>
                            <td>
                            <form id="frmListaPedidoVentaAlquiler" name="frmListaPedidoVentaAlquiler" style="margin:0">
                                <div id="divListaPedidoVentaAlquiler" style="width:100%"></div>
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
                                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Contrato Cerrado</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/ico_return.png"/></td><td>Devolver Contrato</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/book_next.png"/></td><td>Facturar</td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </div>
					<div class="pane">
                    	<table align="left" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
               	             <button type="button" onclick="xajax_imprimirPedidoAdmon(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                            </td>
                        </tr>
                        </table>
                        
                    	<table border="0" width="100%">
                        <tr>
                            <td>
                            <form id="frmListaPedidoVentaAdmon" name="frmListaPedidoVentaAdmon" style="margin:0">
                                <div id="divListaPedidoVentaAdmon" style="width:100%"></div>
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
                                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Pedido Autorizado</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/book_next.png"/></td><td>Facturar</td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </div>
				</div>
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
byId('txtCriterio').className = 'inputHabilitado';

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"vino"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"vino"
	});
};

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>