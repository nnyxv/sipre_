<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_despacho_venta_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_despacho_venta_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Despacho de Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaDespacho').style.display = 'none';
		
		if (verTabla == "tblListaDespacho") {
			document.forms['frmListaDespacho'].reset();
			
			xajax_formListaDespacho(valor, valor2);
			
			tituloDiv1 = 'Bultos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaDespacho") {
			byId('txtNumeroFactura').focus();
			byId('txtNumeroFactura').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblDespacho').style.display = 'none';
		
		if (verTabla == "tblDespacho") {
			document.forms['frmDespacho'].reset();
			document.forms['frmListaSerialDespacho'].reset();
			document.forms['frmAgregarSerialDespacho'].reset();
			byId('hddIdBultoVenta').value = '';
			
			byId('txtIdArticuloDespacho').className = 'inputHabilitado';
			byId('txtSerialArticuloDespacho').className = 'inputHabilitado';
			
			xajax_formDespacho(xajax.getFormValues('frmListaSerialDespacho'));
			
			tituloDiv2 = 'Bultos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblDespacho") {
			byId('txtIdArticuloDespacho').focus();
			byId('txtIdArticuloDespacho').select();
		}
	}
	
	function validarFrmDespacho() {
		if (validarCampo('hddIdBultoVenta','t','') == true
		&& validarCampo('txtNumeroBulto','t','') == true) {
			if (confirm('¿Seguro desea registrar el bulto?') == true) {
				xajax_guardarDespacho(xajax.getFormValues('frmDespacho'), xajax.getFormValues('frmListaSerialDespacho'), xajax.getFormValues('frmListaDespacho'));
			}
		} else {
			validarCampo('hddIdBultoVenta','t','');
			validarCampo('txtNumeroBulto','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDespachoCancelar() {
		if (byId('hddIdBultoVenta').value > 0) {
			if (confirm('¿Seguro Desea Anular el Bulto?') == true) {
				xajax_cancelarDespacho(xajax.getFormValues('frmDespacho'), xajax.getFormValues('frmListaDespacho'));
			}
		} else {
			byId('btnCancelarDespachoOculto').click();
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Despacho de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
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
                            <td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                            <td>&nbsp;Hasta:&nbsp;</td>
                            <td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Vendedor:</td>
                    <td id="tdlstEmpleadoVendedor"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Clave Mov.:</td>
                    <td id="tdlstClaveMovimiento"></td>
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
            	<div id="divListaPedidoVenta" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
				</div>
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
                            <td><img src="../img/iconos/package_add.png"/></td><td>Bultos</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Pedido Venta PDF / Factura Venta PDF</td>
                        </tr>
                        </table>
                    </td>
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

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmListaDespacho" name="frmListaDespacho" onsubmit="return false;" style="margin:0">
    <div id="tblListaDespacho" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="14%">Nro. Factura:</td>
                    <td width="20%"><input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="20" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo" width="14%">Nro. Pedido:</td>
                    <td width="52%"><input type="text" id="txtNumeroPedidoPropio" name="txtNumeroPedidoPropio" readonly="readonly" size="20" style="text-align:center"/></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr align="left">
            <td>
                <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblDespacho');">
                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                </a>
                <!--<button id="btnEliminar" name="btnEliminar" onclick="xajax_eliminarEmpresaUsuario(xajax.getFormValues('frmListaDespacho'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>-->
            </td>
        </tr>
        <tr>
            <td>
                <div id="divListaDespacho" style="width:100%;"></div>
            </td>
        </tr>
        <tr>
            <td>
            <fieldset><legend class="legend">Cantidad de Artículos</legend>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="14%">A Despachar:</td>
                    <td width="19%"><input type="text" id="txtCantDespachar" name="txtCantDespachar" readonly="readonly" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo" width="14%">Incluidas en Bultos:</td>
                    <td width="20%"><input type="text" id="txtCantIncluida" name="txtCantIncluida" readonly="readonly" style="text-align:center"/></td>
                    <td align="right" class="tituloCampo" width="14%">Pendientes:</td>
                    <td width="19%"><input type="text" id="txtCantPendiente" name="txtCantPendiente" readonly="readonly" style="text-align:center"/></td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdFactura" name="hddIdFactura" readonly="readonly"/>
                <input type="hidden" id="txtIdPedido" name="txtIdPedido" readonly="readonly"/>
                <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblDespacho" width="960">
    <tr>
    	<td>
        <form id="frmDespacho" name="frmDespacho" onsubmit="return false;" style="margin:0">
        	<input type="hidden" id="hddIdBultoVenta" name="hddIdBultoVenta" readonly="readonly"/>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="15%">Nro. Bulto:</td>
            	<td width="85%"><input type="text" id="txtNumeroBulto" name="txtNumeroBulto" readonly="readonly" style="text-align:center"/></td>
            </tr>
            </table>
		</form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaSerialDespacho" name="frmListaSerialDespacho" onsubmit="return false;" style="margin:0">
        <fieldset><legend class="legend">Items Registrados</legend>
	        <div id="divListaSerialDespacho" style="width:100%">
            	<table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td></td>
                    <td width="16%">Fecha Creación</td>
                	<td width="24%">Código</td>
                	<td width="35%">Descripción</td>
                	<td width="25%">Serial</td>
                </tr>
				<tr id="trItmPie"></tr>
                </table>
            </div>
        </fieldset>
        </form>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="frmAgregarSerialDespacho" name="frmAgregarSerialDespacho" onsubmit="return false;" style="margin:0">
        	<table>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cód. Barra:</td>
                <td><input type="text" id="txtIdArticuloDespacho" name="txtIdArticuloDespacho"/></td>
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Serial:</td>
                <td><input type="text" id="txtSerialArticuloDespacho" name="txtSerialArticuloDespacho" /></td>
                <td><button type="submit" id="btnAceptarDespacho" name="btnAceptarDespacho" onclick="xajax_insertarSerialDespacho(xajax.getFormValues('frmAgregarSerialDespacho'), xajax.getFormValues('frmDespacho'), xajax.getFormValues('frmListaSerialDespacho'), xajax.getFormValues('frmListaDespacho'));">Aceptar</button></td>
            </tr>
            </table>
		</form>
        	<div id="divMsj"></div>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
	        <button type="button" id="btnGuardarDespacho" name="btnGuardarDespacho" onclick="validarFrmDespacho();">Guardar</button>
            <button type="button" id="btnCancelarDespacho" name="btnCancelarDespacho" onclick="validarFrmDespachoCancelar();">Cancelar</button>
            <button type="button" id="btnCancelarDespachoOculto" name="btnCancelarDespachoOculto" class="close" style="display:none"></button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   $("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>"
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpleado('','lstEmpleadoVendedor');
xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '3');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>