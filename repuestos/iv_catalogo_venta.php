<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_catalogo_venta"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_catalogo_venta.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Catalogo de Artículos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/multibox/multibox.css">
	<script type="text/javascript" language="javascript" src="../js/multibox/mootools.js"></script>
	<script type="text/javascript" language="javascript" src="../js/multibox/multibox.js"></script>
    <script type="text/javascript" language="javascript" src="../js/multibox/overlay.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
	
    <script>
	function validarFrmArticuloPreseleccionado() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('lstEmpresaArt','t','lista') == true
		&& validarCampo('txtCantidadArt','t','cantidad') == true
		&& validarCampo('lstPrecioArt','t','lista') == true
		&& validarCampo('txtPrecioArt','t','monto') == true) {
			xajax_guardarArticuloPreseleccionado(xajax.getFormValues('frmArticuloPreseleccionado'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('lstEmpresaArt','t','lista');
			validarCampo('txtCantidadArt','t','cantidad');
			validarCampo('lstPrecioArt','t','lista');
			validarCampo('txtPrecioArt','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminarArticuloPreseleccionado(idPreseleccionVenta) {
		if (confirm('¿Seguro desea eliminar este registro?') == true)
			xajax_eliminarArticuloPreseleccionado(idPreseleccionVenta);
	}
	
	function validarFrmDcto() {
		if (validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtFechaVencimientoPresupuesto','t','fecha') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true) {
			xajax_guardarPresupuesto(xajax.getFormValues('frmDcto'));
		} else {
			validarCampo('txtIdCliente','t','');
			validarCampo('txtFechaVencimientoPresupuesto','t','fecha');
			validarCampo('lstMoneda','t','lista');
			validarCampo('txtNumeroSiniestro','t','');
			validarCampo('lstClaveMovimiento','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'), xajax.getFormValues('frmArticuloPreseleccionado'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
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
        	<td class="tituloPaginaRepuestos">Catálogo de Artículos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <fieldset><legend class="legend">Opciones de Búsqueda</legend>
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0px">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModelo">
                        <select id="lstModelo" name="lstModelo">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo" width="120">Unidad Básica:</td>
                    <td id="tdlstUnidadBasica">
                        <select id="lstUnidadBasica" name="lstUnidadBasica">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Tipo Artículo:</td>
                    <td id="tdlstTipo">
                        <select id="lstTipo" name="lstTipo">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Marca:</td>
                    <td id="tdlstMarca">
                        <select id="lstMarca" name="lstMarca">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo">Sección:</td>
                    <td id="tdlstSeccion">
                        <select id="lstSeccion" name="lstSeccion">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo">Sub-Sección:</td>
                    <td id="tdlstSubSeccion">
                        <select id="lstSubSeccion" name="lstSubSeccion">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
	                <td></td>
	                <td></td>
                	<td align="right" class="tituloCampo">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td align="right">
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarCatalogo(xajax.getFormValues('frmBuscar'))">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
            </fieldset>
            </td>
        </tr>
        <tr>
        	<td>
            	<table width="100%">
                <tr>
                	<td align="left" valign="top" width="25%">
                    <fieldset><legend class="legend">Articulos Preseleccionados</legend>
                    <form id="frmListaArticuloPreseleccionado" name="frmListaArticuloPreseleccionado" style="margin:0px">
	                    <table border="0" width="100%">
                        <tr>
                        	<td id="tdListadoArticulosPreseleccionados">&nbsp;</td>
                        </tr>
                        <tr>
                        	<td align="right">
                            	<button type="button" id="btnPresupuesto" name="btnPresupuesto" onclick="xajax_formPresupuesto(xajax.getFormValues('frmListaArticuloPreseleccionado'));" style="display:none">Presupuesto</button>
                            </td>
                        </tr>
                        </table>
					</form>
                    </fieldset>
                    </td>
                    <td style="border-right:1px solid #000000">&nbsp;</td>
                    <td id="tdListadoArticulos" valign="top" width="75%">
                    	<table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                        <tr>
                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td align="center">Ingrese los datos del Artículo a Buscar</td>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
    <table border="0" id="tblPermiso" style="display:none" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                	<input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnPermiso" onclick="validarFrmPermiso();">Aceptar</button>
            <button type="button" id="btnCancelarPermiso" onclick="
            if (byId('hddModulo').value == 'iv_catalogo_venta_precio_editado'
			|| byId('hddModulo').value == 'iv_catalogo_venta_precio_editado_bajar'
            || byId('hddModulo').value == 'iv_catalogo_venta_precio_venta') {
            	byId('tblPermiso').style.display = 'none';
                byId('tblArticuloPreseleccionado').style.display = '';
                byId('tblDcto').style.display = 'none';
                
                byId('divFlotante').style.display = '';
                centrarDiv(byId('divFlotante'));
                
                byId('txtCantidadArt').focus();
			} else {
            	byId('divFlotante').style.display = 'none';
			}">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
	
<form id="frmArticuloPreseleccionado" name="frmArticuloPreseleccionado" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblArticuloPreseleccionado" style="display:none" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td id="tdlstEmpresaArt"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                <td><input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                <td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" onkeypress="return validarSoloNumeros(event);" size="12"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="20%"><?php echo $spanPrecioUnitario; ?>:</td>
                <td width="80%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                    	<td id="tdlstPrecioArt"></td>
                        <td>&nbsp;</td>
                        <td align="right">
                        	<input type="hidden" id="hddPrecioArtPredet" name="hddPrecioArtPredet" readonly="readonly"/>
                            <input type="hidden" id="hddBajarPrecio" name="hddBajarPrecio" readonly="readonly"/>
                        	<input type="hidden" id="hddIdArtPrecio" name="hddIdArtPrecio" readonly="readonly"/>
                            <input type="text" id="txtPrecioArt" name="txtPrecioArt" class="inputHabilitado" maxlength="17" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="10" style="text-align:right"/>
						</td>
                        <td>&nbsp;</td>
                        <td align="center" id="tdDesbloquearPrecio"></td>
					</tr>
                    </table>
				</td>
            </tr>
			</table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
        	<input type="hidden" id="hddIdArticulo" name="hddIdArticulo"/>
            <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/>
            <button type="submit" onclick="validarFrmArticuloPreseleccionado();">Aceptar</button>
            <button type="button" onclick="byId('divFlotante').style.display = 'none';">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
    
<form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblDcto" style="display:none" width="960">
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td colspan="3">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
						<td>&nbsp;</td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo">Fecha:</td>
                <td><input type="text" id="txtFechaPresupuesto" name="txtFechaPresupuesto" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                <td colspan="3">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right;"/></td>
                        <td><button type="button" id="btnInsertarCliente" name="btnInsertarCliente" onclick="byId('frmBuscarCliente').reset(); xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmBuscar'));" title="Listar"><img src="../img/iconos/help.png"/></button></td>
                        <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo" id="tdFechaVencimiento"><span class="textoRojoNegrita">*</span>Fecha de Vencimiento:</td>
                <td id="tdFechaVencimientoObj">
                <div style="float:left">
                    <input type="text" id="txtFechaVencimientoPresupuesto" name="txtFechaVencimientoPresupuesto" class="inputHabilitado" readonly="readonly" size="10" style="text-align:center"/>
                </div>
                <div style="float:left">
                    <img src="../img/iconos/ico_date.png" id="imgFechaVencimientoPresupuesto" name="imgFechaVencimientoPresupuesto" class="puntero noprint"/>
                </div>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                <td colspan="3" id="tdlstMoneda"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Nro. Siniestro:</td>
                <td colspan="3"><input type="text" id="txtNumeroSiniestro" name="txtNumeroSiniestro" class="inputHabilitado" size="25"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="13%"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                <td width="17%">
                	<select id="lstTipoClave" name="lstTipoClave" onchange="xajax_cargaLstClaveMovimiento(this.value)" disabled="disabled">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="3" selected="selected">3.- VENTA</option>
                        <option value="4">4.- SALIDA</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="13%"><span class="textoRojoNegrita">*</span>Clave:</td>
                <td id="tdlstClaveMovimiento" width="24%">
                    <select id="lstClaveMovimiento" name="lstClaveMovimiento">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="13%">Tipo de Pago:</td>
                <td width="20%">
                    <label><input type="radio" id="rbtTipoPagoCredito" name="rbtTipoPago" value="0"/> Crédito</label>
                    <label><input type="radio" id="rbtTipoPagoContado" name="rbtTipoPago" value="1" checked="checked"/> Contado</label>
                </td>
            </tr>
			</table>
        </td>
	</tr>
    <tr>
    	<td><div id="tdListadoArticulosPresupuesto" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr>
                <td align="right" id="tdGastos" valign="top" width="50%"></td>
                <td valign="top" width="50%">
                    <table border="0" width="100%">
                    <tr align="right">
                        <td class="tituloCampo" width="36%">Subtotal:</td>
                        <td style="border-top:1px solid;" width="24%"></td>
                        <td style="border-top:1px solid;" width="13%"></td>
                        <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="5%"></td>
                        <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                    </tr>
                    </table>
				</td>
			</tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnAceptarDcto" name="btnAceptarDcto" onclick="validarFrmDcto();">Aceptar</button>
            <button type="button" onclick="byId('divFlotante').style.display='none';">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
  	
    <table border="0" id="tblLista" width="560">
    <tr id="trBuscarCliente">
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td>
                    <input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/>
                </td>
                <td>
                    <button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmBuscar'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td id="tdListado"></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarLista" onclick="byId('divFlotante2').style.display='none';">Cancelar</button>
        </td>
    </tr>
    </table>
</div>

<script>
Calendar.setup({
	inputField : "txtFechaVencimientoPresupuesto",
	ifFormat : "%d-%m-%Y",
	button : "imgFechaVencimientoPresupuesto"
});

var box = {};
window.addEvent('domready', function(){
	box = new MultiBox('mb', {descClassName: 'multiBoxDesc', useOverlay: true});
});

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstBusq('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaArticuloPreseleccionado();
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

/*var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);*/
</script>