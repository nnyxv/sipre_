<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_devolucion_venta_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_devolucion_venta_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Facturas a Devolver</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
	
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
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2, valor3) {
		byId('tblPermiso').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			byId('hddIdOrden').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			byId('hddModulo').value = valor;
			byId('hddIdOrden').value = valor2;
			byId('txtNumOrden').value = valor3;
			
			tituloDiv1 = 'Devolver Orden';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		}
	}
	
	function validarDevolverPedido(idPedido, hddIdItm) {
		if (confirm('¿Seguro desea Devolver el Pedido?') == true) {
			byId('imgDevolverPedido' + hddIdItm).style.display = 'none';
			xajax_devolverPedido(idPedido, xajax.getFormValues('frmListaPedidoVenta'));
		}
	}
	
	function validarFrmPermiso(){
		if (validarCampo('txtContrasena','t','') == true) {
			if (confirm('¿Seguro desea devolver el registro seleccionado?') == true){
				xajax_validarPermiso(xajax.getFormValues('frmPermiso'),1);
			}
		} else {
		 	validarCampo('txtContrasena','t','')
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
        	<td class="tituloPaginaCajaRS">Facturas a Devolver</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
					<tr id="trEmpresa" align="left">
                        <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                </tr>
                <tr align="left">
						<td align="right" class="tituloCampo">Fecha:</td>
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
                </tr>
                <tr align="left">
						<td align="right" class="tituloCampo" width="120">Tipo de Pago:</td>
						<td>
                            <select id="lstCondicionPago" name="lstCondicionPago" class="inputHabilitado" onchange="byId('btnBuscar').click();">
                                <option value="-1">[ Todos ]</option>
                                <option value="0">Credito</option>
                                <option value="1">Contado</option>
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
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Repuestos</a></li>
                        <li><a href="#">Servicios</a></li>
                        <li><a href="#">Administración</a></li>
                    </ul>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                    	<table align="left" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <button type="button" onclick="xajax_imprimirDevolucionRepuestos(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
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
                                            <td><img src="../img/iconos/ico_gris.gif"/></td><td>Factura (Con Devolución)</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/arrow_rotate_clockwise.png"/></td><td>Generar Nota de Crédito</td>
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
               	             <button type="button" onclick="xajax_imprimirDevolucionServicios(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                            </td>
                        </tr>
                        </table>
                        
                    	<table border="0" width="100%">
                        <tr>
                            <td>
                            <form id="frmListaOrdenServicio" name="frmListaOrdenServicio" style="margin:0">
                                <div id="divListaOrdenServicio" style="width:100%"></div>
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
                                            <td><img src="../img/iconos/arrow_rotate_clockwise.png"/></td><td>Generar Nota de Crédito</td>
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
               	             <button type="button" onclick="xajax_imprimirDevolucionAdmon(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                            </td>
                        </tr>
                        </table>
                        
                    	<table border="0" width="100%">
                        <tr>
                            <td>
                            <form id="frmListaFacturaAdmon" name="frmListaFacturaAdmon" style="margin:0">
                                <div id="divListaFacturaAdmon" style="width:100%"></div>
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
                                            <td><img src="../img/iconos/arrow_rotate_clockwise.png"/></td><td>Generar Nota de Crédito</td>
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

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
	
<form id="frmPermiso" name="frmPermiso" style="margin:0px" onsubmit="return false;">
	<table border="0" id="tblPermiso" width="360">
	<tr>
		<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Nro. Orden:</td>
                <td width="68%">
                	<input type="text" id="txtNumOrden" name="txtNumOrden" readonly="readonly" size="20" style="text-align:center">
					<input type="hidden" id="hddIdOrden" name="hddIdOrden"/>
				</td>
			</tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td>
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo"/>
				</td>
            </tr>
            </table>
        </td>
	</tr>
	<tr>
		<td align="right"><hr>
            <button type="sumbit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Guardar</button>
            <button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" class="close">Cancelar</button>
		</td>
	</tr>
	</table>
</form>
</div>

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
		cellColorScheme:"brown"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"brown"
	});
};

function openImg(idObj) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			//effect: 'apple',
			oneInstance: false,
			zIndex: 10100,
			
			onLoad: function() {
				if ($.mask.isLoaded()) {
					oldMaskZ = $.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = $.mask.getExposed();
					$.mask.getConf().closeSpeed = 0;
					$.mask.close();
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0,
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA

					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false
					});
				} // Other onLoad functions
			},
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					
					$(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
	});
}

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>