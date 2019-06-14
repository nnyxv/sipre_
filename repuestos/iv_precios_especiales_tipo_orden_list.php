<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_precios_especiales_tipo_orden_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_precios_especiales_tipo_orden_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Precios Especiales por Tipo de Orden</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<!--<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function validarFrmPrecioEspecialTipoOrden() {
		if (validarCampo('lstPrecio','t','lista') == true) {
			xajax_guardarPrecioEspecialTipoOrden(xajax.getFormValues('frmPrecioEspecialTipoOrden'), xajax.getFormValues('frmListaPrecioEspecialTipoOrden'));
		} else {
			validarCampo('lstPrecio','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDatosArticulo() {	
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('lstPrecioArt','t','lista') == true) {
			xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmPrecioEspecialTipoOrden'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('lstPrecioArt','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmListaArticuloLote() {	
		if (validarCampo('lstPrecioArtLote','t','lista') == true) {
			xajax_insertarArticuloLote(xajax.getFormValues('frmListaArticuloLote'), xajax.getFormValues('frmPrecioEspecialTipoOrden'));
		} else {
			validarCampo('lstPrecioArtLote','t','lista');
			
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
        	<td class="tituloPaginaRepuestos">Precios Especiales por Tipo de Orden</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">			
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarPrecioEspecialTipoOrden(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPrecioEspecialTipoOrden" name="frmListaPrecioEspecialTipoOrden" style="margin:0">
            	<div id="divListaPrecioEspecialTipoOrden" style="width:100%;"></div>
            </form>
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
    
<form id="frmPrecioEspecialTipoOrden" name="frmPrecioEspecialTipoOrden" onsubmit="return false;" style="margin:0">
    <div id="tblPrecioEspecialTipoOrden" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td width="82%">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td>&nbsp;</td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Orden:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdTipoOrden" name="txtIdTipoOrden" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td>&nbsp;</td>
                            <td><input type="text" id="txtDescripcionTipoOrden" name="txtDescripcionTipoOrden" readonly="readonly" size="26"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Precio Especial General:</td>
                    <td id="tdlstPrecio">
                        <select id="lstPrecio" name="lstPrecio">
                            <option value="-1">[ Seleccione]</option>
                        </select>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
            <fieldset><legend class="legend">Asignación de Precio Por Artículo</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <a class="modalImg" id="aNuevoLote" rel="#divFlotante2" onclick="xajax_formArticuloLote(this.id);">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_registrar_compra.gif"/></td><td>&nbsp;</td><td>Agregar por Lote</td></tr></table></button>
                        </a>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="xajax_formArticulo(this.id);">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button id="btnEliminar" name="btnEliminar" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmPrecioEspecialTipoOrden'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" class="texto_9px" width="100%">
                        <tr align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                            <td width="18%">Código</td>
                            <td width="60%">Descripción</td>
                            <td width="22%">Tipo de Precio</td>
                        </tr>
                        <tr id="trItmPie"></tr>
                        </table>
                    </td>
                </tr>
                </table>
                <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdTipoOrden" name="hddIdTipoOrden"/>
                <button type="submit" id="btnGuardar" name="btnGuardar" onclick="validarFrmPrecioEspecialTipoOrden();">Guardar</button>
                <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
    </div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblArticulo" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" onsubmit="return false;" style="margin:0">
        	<table align="right" border="0">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="120">Buscar por:</td>
                <td>
                	<select id="lstBuscarArticulo" name="lstBuscarArticulo" class="inputHabilitado" style="width:150px">
                    	<option value="1">Marca</option>
                        <option value="2">Tipo Artículo</option>
                        <option value="3">Sección</option>
                        <option value="4">Sub-Sección</option>
                        <option selected="selected" value="5">Descripción</option>
                        <option value="6">Cód. Barra</option>
                        <option value="7">Cód. Artículo Prov.</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarArticulo" name="txtCriterioBuscarArticulo" class="inputHabilitado"/></td>
                <td align="right" class="tituloCampo" width="120">Código:</td>
                <td id="tdCodigoArt"></td>
                <td align="right" nowrap="nowrap">
                	<button type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArticulo'].reset(); byId('btnBuscarArticulo').click();">Limpiar</button>
				</td>
			</tr>
			</table>
		</form>
		</td>
    </tr>
    <tr>
    	<td><div id="divListadoArticulos"></div></td>
    </tr>
    <tr>
    	<td>
        <form id="frmDatosArticulo" name="frmDatosArticulo" onsubmit="return false;" style="margin:0">
        	<input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
        <fieldset>
        	<table border="0" width="100%">
            <tr>
                <td width="10%"></td>
                <td width="30%"></td>
                <td width="38%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                <td>
                    <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" readonly="readonly"/>
                    <input type="hidden" id="hddIdArt" name="hddIdArt" readonly="readonly"/>
                	<input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/>
				</td>
                <td rowspan="3" valign="top">
                	<textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="50" rows="3" readonly="readonly"></textarea>
				</td>
                <td align="right" class="tituloCampo">Ult. Compra:</td>
                <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="38"/></td>
                <td align="right" class="tituloCampo">Ult. Venta:</td>
                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Tipo de Pieza:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
                <td align="right" class="tituloCampo">Unid. Disponible:</td>
                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right"/></td>
            </tr>
            </table>
		</fieldset>
            
            <table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
                <td width="28%"></td>
                <td width="10%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
                <td width="30%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanPrecioUnitario; ?>:</td>
                <td id="tdlstPrecioArt" colspan="5">
                	<select id="lstPrecioArt" name="lstPrecioArt">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
			</tr>
            <tr>
                <td align="right" colspan="6">
                    <hr>
                    <button type="submit" id="btnGuardarArticulo" onclick="validarFrmDatosArticulo();">Aceptar</button>
                    <button type="button" id="btnCancelarArticulo" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
		</td>
	</tr>
    </table>
    
    <table border="0" id="tblArticuloLote" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticuloLote" name="frmBuscarArticuloLote" onsubmit="return false;" style="margin:0">
        	<table align="right" border="0">
            <tr align="left">
	            <td align="right" class="tituloCampo" width="120">Tipo Artículo:</td>
                <td id="tdlstTipoArticulo"></td>
                <td align="right" nowrap="nowrap">
                	<button type="submit" id="btnBuscarArticuloLote" name="btnBuscarArticuloLote" onclick="xajax_buscarArticuloLote(xajax.getFormValues('frmBuscarArticuloLote'), xajax.getFormValues('frmPrecioEspecialTipoOrden'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArticuloLote'].reset(); byId('btnBuscarArticuloLote').click();">Limpiar</button>
				</td>
            </tr>
            </table>
		</form>
        </td>
    </tr>
    <tr>
    	<td>
        	<form id="frmListaArticuloLote" name="frmListaArticuloLote" style="margin:0">
            	<div id="divListaArticuloLote" style="max-height:300px; overflow:auto; width:100%">
                	<table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
                </div>
                <table width="100%">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="10%"><span class="textoRojoNegrita">*</span><?php echo $spanPrecioUnitario; ?>:</td>
                    <td id="tdlstPrecioArtLote" width="90%"></td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarArticuloLote" name="btnGuardarArticuloLote" onclick="validarFrmListaArticuloLote();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloLote" name="btnCancelarArticuloLote" class="close">Cerrar</button>
		</td>
    </tr>
    </table>
</div>

<script>
function openImg(idObj) {
	jQuery.noConflict();
	
	var oldMaskZ = null;
	var $oldMask = jQuery(null);
	
	jQuery(".modalImg").each(function() {
		jQuery(idObj).overlay({
			//effect: 'apple',
			oneInstance: false,
			zIndex: 10100,
			
			onLoad: function() {
				if (jQuery.mask.isLoaded()) {
					oldMaskZ = jQuery.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = jQuery.mask.getExposed();
					jQuery.mask.getConf().closeSpeed = 0;
					jQuery.mask.close();
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
				jQuery.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					
					jQuery(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
	});
}

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaPrecioEspecialTipoOrden(0, 'id_tipo_orden', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>