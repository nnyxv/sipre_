<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_documento_importacion_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_documento_importacion_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Registro de Documento de Importación</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblListaProveedor').style.display = 'none';
		byId('tblListaArticuloCompra').style.display = 'none';
		byId('tblListaFacturaGasto').style.display = 'none';
		
		if (verTabla == "tblListaProveedor") {
			document.forms['frmBuscarProveedor'].reset();
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			
			byId('btnBuscarProveedor').click();
			
			tituloDiv1 = 'Proveedores';
		} else if (verTabla == "tblListaArticuloCompra") {
			document.forms['frmBuscarArticuloCompra'].reset();
			
			byId('btnBuscarArticuloCompra').click();
			
			tituloDiv1 = 'Artículos de Compra';
		} else if (verTabla == "tblListaFacturaGasto") {
			document.forms['frmBuscarFacturaGasto'].reset();
			
			byId('btnBuscarFacturaGasto').click();
			
			tituloDiv1 = 'Dctos. de Importación Pendientes';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaProveedor") {
			byId('txtCriterioBuscarProveedor').focus();
			byId('txtCriterioBuscarProveedor').select();
		} else if (verTabla == "tblListaArticuloCompra") {
			byId('txtCriterioBuscarArticuloCompra').focus();
			byId('txtCriterioBuscarArticuloCompra').select();
		} else if (verTabla == "tblListaFacturaGasto") {
			byId('txtCriterioBuscarFacturaGasto').focus();
			byId('txtCriterioBuscarFacturaGasto').select();
		}
	}
	
	function validarFrmFacturaGasto() {
		error = false;
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtNumeroFacturaGasto','t','') == true
		&& validarCampo('txtNumeroControlFacturaGasto','t','') == true
		&& validarCampo('txtFechaFacturaGasto','t','fecha') == true
		&& validarCampo('lstTipoPagoFacturaGasto','t','listaExceptCero') == true
		&& validarCampo('txtIdProvFacturaGasto','t','') == true
		&& validarCampo('txtTotalFacturaGasto','t','monto') == true
		&& validarCampo('lstClaveMovimientoFacturaGasto','t','lista') == true
		&& validarCampo('txtPorcDescuentoFacturaGasto','t','numPositivo') == true
		&& validarCampo('lstRetencionImpuesto','t','listaExceptCero') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtNumeroFacturaGasto','t','');
			validarCampo('txtNumeroControlFacturaGasto','t','');
			validarCampo('txtFechaFacturaGasto','t','fecha');
			validarCampo('lstTipoPagoFacturaGasto','t','listaExceptCero');
			validarCampo('txtIdProvFacturaGasto','t','');
			validarCampo('txtTotalFacturaGasto','t','monto');
			validarCampo('lstClaveMovimientoFacturaGasto','t','lista');
			validarCampo('txtPorcDescuentoFacturaGasto','t','numPositivo');
			validarCampo('lstRetencionImpuesto','t','listaExceptCero');
			
			error = true;
		}
		
		var cadena = byId('hddObjItmArticuloFacturaGasto').value;
		var arrayObj = cadena.split("|");
		
		for (var i = 0; i < arrayObj.length; i++) {
			if (arrayObj[i] > 0) {
				if (!(validarCampo('txtCantItmFacturaGasto' + arrayObj[i],'t','cantidad') == true
				&& validarCampo('txtCostoItmFacturaGasto' + arrayObj[i],'t','monto') == true
				&& validarCampo('lstIvaItmFacturaGasto' + arrayObj[i],'t','listaExceptCero') == true)) {
					validarCampo('txtCantItmFacturaGasto' + arrayObj[i],'t','cantidad');
					validarCampo('txtCostoItmFacturaGasto' + arrayObj[i],'t','monto');
					validarCampo('lstIvaItmFacturaGasto' + arrayObj[i],'t','listaExceptCero');
					
					error = true;
				}
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea registrar la compra?') == true) {
				byId('btnAceptarFacturaGasto').disabled = true;
				byId('btnCancelarFacturaGasto').disabled = true;
				
				xajax_guardarFacturaGasto(xajax.getFormValues('frmFacturaGasto'));
			}
		}
	}
	
	function validarInsertarArticuloFacturaGasto(idArticulo) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarArticuloFacturaGasto' + cont) == undefined)) {
				byId('btnInsertarArticuloFacturaGasto' + cont).disabled = true;
			}
		}
		xajax_insertarArticuloFacturaGasto(idArticulo, xajax.getFormValues('frmFacturaGasto'));
	}
	
	function validarInsertarFacturaGasto(idFacturaGasto) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarFacturaGasto' + cont) == undefined)) {
				byId('btnInsertarFacturaGasto' + cont).disabled = true;
			}
		}
		xajax_insertarFacturaGasto(idFacturaGasto, xajax.getFormValues('frmFacturaGasto'));
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Registro de Documento de Importación</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <form id="frmFacturaGasto" name="frmFacturaGasto" onsubmit="return false;" style="margin:0"> 
                <input type="hidden" id="hddIdFacturaGasto" name="hddIdFacturaGasto" readonly="readonly"/>
                
                <table border="0" width="100%">
                <tr id="trDatosGastoImportacion">
                	<td>
                    <fieldset><legend class="legend">Datos del Gasto de Importación</legend>
                    	<table width="100%">
                        <tr align="left">
                        	<td align="right" class="tituloCampo" width="12%">Gasto:</td>
                        	<td width="26%"><input type="text" id="txtNombreGasto" name="txtNombreGasto" readonly="readonly" size="45"/></td>
                        	<td align="right" class="tituloCampo" width="12%">Subtotal:</td>
                            <td width="50%"><input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr id="trCabeceraFacturaGasto" align="left">
                    <td>
                        <table border="0" width="100%">
                        <tr>
                        	<td width="12%"></td>
                        	<td width="38%"></td>
                        	<td width="11%"></td>
                        	<td width="14%"></td>
                        	<td width="11%"></td>
                        	<td width="14%"></td>
                        </tr>
                        <tr align="left">
                			<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                        	<td>
                            	<table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td>&nbsp;</td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                            <td></td>
                            <td></td>
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtFechaRegistroCompra" name="txtFechaRegistroCompra" readonly="readonly" size="10" style="text-align:center"/>
                            </div>
                            </td>
                        </tr>
                        <tr>
                        	<td colspan="2" rowspan="4" valign="top">
                            <fieldset><legend class="legend">Proveedor</legend>
                            	<table width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Proveedor:</td>
                                    <td colspan="3">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdProvFacturaGasto" name="txtIdProvFacturaGasto" onblur="xajax_asignarProveedor(this.value,'ProvFacturaGasto');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarProvFacturaGasto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaProveedor', 'ProvFacturaGasto');">
                                                <button type="button" id="btnListarProvFacturaGasto" name="btnListarProvFacturaGasto" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreProvFacturaGasto" name="txtNombreProvFacturaGasto" readonly="readonly" size="45"/></td>
                                        </tr>
                                        </table>
                                    </td>
								</tr>
                                <tr align="left">
                                	<td align="right" class="tituloCampo" rowspan="2" width="20%">Dirección:</td>
                                    <td rowspan="2" width="38%"><textarea id="txtDireccionProvFacturaGasto" name="txtDireccionProvFacturaGasto" cols="28" readonly="readonly" rows="3"></textarea></td>
                                	<td align="right" class="tituloCampo" width="20%"><?php echo $spanProvCxP; ?>:</td>
                                    <td width="22%"><input type="text" id="txtRifProvFacturaGasto" name="txtRifProvFacturaGasto" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                	<td align="right" class="tituloCampo">Teléfono</td>
                                	<td><input type="text" id="txtTelefonoProvFacturaGasto" name="txtTelefonoProvFacturaGasto" readonly="readonly" size="18" style="text-align:center"/></td>
                                </tr>
                                </table>
							</fieldset>
							</td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Factura Prov.:</td>
                            <td><input type="text" id="txtNumeroFacturaGasto" name="txtNumeroFacturaGasto" size="20" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control Prov.:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtNumeroControlFacturaGasto" name="txtNumeroControlFacturaGasto" size="20" style="text-align:center"/>&nbsp;
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: 00-000000 / Máquinas Fiscales"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Factura Prov.:</td>
                            <td><input type="text" id="txtFechaFacturaGasto" name="txtFechaFacturaGasto" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                        	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                            <td>
                                <select id="lstTipoClave" name="lstTipoClave" onchange="selectedOption(this.id,1); xajax_cargaLstClaveMovimiento('lstClaveMovimientoFacturaGasto', '3', this.value)">
                                	<option value="-1">[ Seleccione ]</option>
                                    <option value="1" selected="selected">1.- COMPRA</option>
                                    <option value="2">2.- ENTRADA</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td id="tdlstClaveMovimientoFacturaGasto" colspan="3">
                                <select id="lstClaveMovimientoFacturaGasto" name="lstClaveMovimientoFacturaGasto">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                            <td>
                                <select id="lstTipoPagoFacturaGasto" name="lstTipoPagoFacturaGasto">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="0">Contado</option>
                                    <option value="1">Crédito</option>
                                </select>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td>
                        <table border="0" width="100%">
                        <tr align="center" class="tituloColumna">
                        	<td width="4%">Nro.</td>
                            <td width="16%">Código</td>
                            <td width="29%">Descripción</td>
                            <td width="12%">Recib.</td>
                            <td width="14%">Costo Unit.</td>
                            <td width="10%">% Impuesto</td>
                            <td width="14%">Total</td>
                            <td>
                            	<a class="modalImg" id="aAgregarArticuloCompra" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaArticuloCompra');">
									<img class='puntero' src="../img/iconos/add.png" title='Agregar'/>
                                </a>
                                <input type="hidden" id="hddObjItmArticuloFacturaGasto" name="hddObjItmArticuloFacturaGasto" readonly="readonly" title="hddObjItmArticuloFacturaGasto"/>
                            </td>
                        </tr>
                        <tbody id="tbodyItmArticuloFacturaGasto">
                        <tr id="trItmArticuloFacturaGasto"></tr>
                        </tbody>
                        <tr align="right">
                            <td colspan="3" rowspan="10" valign="top">
                                <table width="100%">
                                <tr align="left">
                                    <td class="tituloCampo">Observación:</td>
                                </tr>
                                <tr align="left">
                                    <td><textarea id="txtObservacionFacturaGasto" name="txtObservacionFacturaGasto" rows="3" style="width:99%"></textarea></td>
                                </tr>
                                </table>
                                
							<fieldset id="fieldsetFacturaGasto"><legend class="legend">Dctos. de Importación</legend>
                                <table border="0" class="texto_9px" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td width="15%">Factura</td>
                                    <td width="30%">Proveedor</td>
                                    <td width="40%">Gasto</td>
                                    <td width="15%">Subtotal</td>
                                    <td>
                                        <a class="modalImg" id="aAgregarFacturaGasto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaFacturaGasto');">
                                            <img class='puntero' src="../img/iconos/add.png" title='Agregar'/>
                                        </a>
                                        <input type="hidden" id="hddObjItmFacturaGasto" name="hddObjItmFacturaGasto" readonly="readonly" title="hddObjItmFacturaGasto"/>
                                    </td>
                                </tr>
                                <tr id="trItmFacturaGasto" class="trResaltarTotal">
                                	<td align="right" class="tituloCampo" colspan="3">Total:</td>
                                    <td align="right"><input type="text" id="txtTotalDocumentosImportacion" name="txtTotalDocumentosImportacion" class="inputSinFondo" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                </table>
							</fieldset>
                            </td>
                            <td class="tituloCampo">Subtotal:</td>
                            <td style="border-top:1px solid;"></td>
                            <td style="border-top:1px solid;"></td>
                            <td style="border-top:1px solid;"><input type="text" id="txtSubTotalFacturaGasto" name="txtSubTotalFacturaGasto" class="inputSinFondo" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td nowrap="nowrap"><input type="text" id="txtPorcDescuentoFacturaGasto" name="txtPorcDescuentoFacturaGasto" onblur="setFormatoRafk(this,2); xajax_calcularFacturaGasto(xajax.getFormValues('frmFacturaGasto'));" onkeypress="return validarSoloNumerosReales(event);" size="6" style="text-align:right"/>%</td>
                            <td><input type="text" id="txtDescuentoFacturaGasto" name="txtDescuentoFacturaGasto" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trTotalFacturaGasto" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtTotalFacturaGasto" name="txtTotalFacturaGasto" class="inputSinFondo" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td colspan="4"><hr></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Exento:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtExentoFacturaGasto" name="txtExentoFacturaGasto" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Exonerado:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtExoneradoFacturaGasto" name="txtExoneradoFacturaGasto" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" id="trRetencionIva" style="display:none">
                        	<td class="tituloCampo">Retención de Impuesto:</td>
                            <td colspan="3">
                                <table border="0" width="100%">
                                <tr>
                                	<td id="tdlstRetencionImpuesto"></td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                                        <tr>
                                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                            <td align="center">Usted es Contribuyente Especial</td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnAceptarFacturaGasto" name="btnAceptarFacturaGasto" onclick="validarFrmFacturaGasto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelarFacturaGasto" name="btnCancelarFacturaGasto" onclick="window.location.href='an_documento_importacion_list.php';"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
    <table border="0" id="tblListaProveedor" width="760">
    <tr>
    	<td>
        <form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarProveedor" name="txtCriterioBuscarProveedor" class="inputHabilitado" onkeyup="byId('btnBuscarProveedor').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarProveedor'].reset(); byId('btnBuscarProveedor').click();">Limpiar</button>
                    <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td><div id="divListaProveedor" style="width:100%"></div></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaProveedor" name="btnCancelarListaProveedor" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
	<table border="0" id="tblListaArticuloCompra" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticuloCompra" name="frmBuscarArticuloCompra" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarArticuloCompra" name="txtCriterioBuscarArticuloCompra" class="inputHabilitado" onkeyup="byId('btnBuscarArticuloCompra').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarArticuloCompra" name="btnBuscarArticuloCompra" onclick="xajax_buscarArticuloFacturaGasto(xajax.getFormValues('frmBuscarArticuloCompra'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArticuloCompra'].reset(); byId('btnBuscarArticuloCompra').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaArticuloCompra" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaArticuloCompra" name="btnCancelarListaArticuloCompra" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
	<table border="0" id="tblListaFacturaGasto" width="960">
    <tr>
    	<td>
        <form id="frmBuscarFacturaGasto" name="frmBuscarFacturaGasto" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarFacturaGasto" name="txtCriterioBuscarFacturaGasto" class="inputHabilitado" onkeyup="byId('btnBuscarFacturaGasto').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarFacturaGasto" name="btnBuscarFacturaGasto" onclick="xajax_buscarFacturaGasto(xajax.getFormValues('frmBuscarFacturaGasto'), xajax.getFormValues('frmFacturaGasto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarFacturaGasto'].reset(); byId('btnBuscarFacturaGasto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaFacturaGasto" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaFacturaGasto" name="btnCancelarListaFacturaGasto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
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

window.onload = function(){
	jQuery(function($){
	   $("#txtFechaFacturaGasto").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaFacturaGasto",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
};

xajax_nuevoDcto('<?php echo $_GET['id']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>