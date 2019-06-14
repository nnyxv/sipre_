<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_expediente_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_expediente_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Expedientes de Importación</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
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
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblExpediente').style.display = 'none';
		
		if (verTabla == "tblExpediente") {
			document.forms['frmExpediente'].reset();
			byId('hddIdExpediente').value = '';
			
			byId('txtNumeroExpediente').className = 'inputHabilitado';
			byId('txtNumeroEmbarque').className = 'inputHabilitado';
			
			if (valor > 0) {
				xajax_cargarExpediente(valor, xajax.getFormValues('frmExpediente'));
			} else {
				xajax_formExpediente(xajax.getFormValues('frmExpediente'));
			}
			tituloDiv1 = 'Expediente de Importación';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblExpediente") {
			byId('txtNumeroExpediente').focus();
			byId('txtNumeroExpediente').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaOtrosCargos').style.display = 'none';
		byId('tblFacturaOtroCargo').style.display = 'none';
		byId('tblCondicionGasto').style.display = 'none';
		byId('fieldsetDatosFactura').style.display = 'none';
		
		if (verTabla == "tblListaOtrosCargos") {
			xajax_listaOtrosCargos(0, 'nombre', 'ASC');
			tituloDiv2 = 'Otros Cargos';
		} else if (verTabla == "tblFacturaOtroCargo") {
			document.forms['frmBuscarRegistroCompra'].reset();
			document.forms['frmListaRegistroCompra'].reset();
			
			byId('hddIdModulo').value = valor;
			if (valor == 0) {
				
				byId('btnBuscarRegistroCompra').click();
				tituloDiv2 = 'Lista Reg. Compra';
			} else if (valor == 3) {
				document.forms['frmFacturaGasto'].reset();
				byId('hddItmGasto').value = '';
				
				byId('tblCondicionGasto').style.display = '';
				byId('lstCondicionGasto').className = 'inputHabilitado';
				
				byId('hddItmGastoListaRegistroCompra').value = valor2;
				
				xajax_cargarFacturaCargo(valor2, xajax.getFormValues('frmExpediente'), xajax.getFormValues('frmFacturaGasto'));
				
				tituloDiv2 = 'Lista Reg. Compra Cargo';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblFacturaOtroCargo") {
			byId('txtCriterioBuscarRegistroCompra').focus();
			byId('txtCriterioBuscarRegistroCompra').select();
		}
	}
	
	function seleccionarCondicion(idCondicionGasto) {
		if (idCondicionGasto == 1 && byId('lstAsociaDocumento').value == 1) { // 1 = Real && 1 = Si
			byId('fieldsetDatosFactura').style.display = 'none';
			byId('fieldsetListaRegistroCompra').style.display = '';
			
			byId('txtSubTotalFacturaGasto').className = 'inputSinFondo';
			byId('txtSubTotalFacturaGasto').readOnly = true;
		} else if (idCondicionGasto == 2 || byId('lstAsociaDocumento').value == 0) { // 2 = Estimado || 0 = No
			byId('fieldsetDatosFactura').style.display = '';
			byId('fieldsetListaRegistroCompra').style.display = 'none';
			
			byId('txtSubTotalFacturaGasto').className = 'inputHabilitado';
			byId('txtSubTotalFacturaGasto').readOnly = false;
			byId('txtSubTotalFacturaGasto').size = '17';
			
			byId('txtSubTotalFacturaGasto').focus();
			byId('txtSubTotalFacturaGasto').select();
		}
	}
	
	function validarFrmExpediente() {
		if (validarCampo('txtNumeroExpediente','t','') == true
		&& validarCampo('txtNumeroEmbarque','t','') == true) {
			xajax_guardarExpediente(xajax.getFormValues('frmExpediente'), xajax.getFormValues('frmListaExpediente'));
		} else {
			validarCampo('txtNumeroExpediente','t','');
			validarCampo('txtNumeroEmbarque','t','')
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmFacturaGasto() {
		error = false;
		if (byId('lstCondicionGasto').value == 2 || byId('lstAsociaDocumento').value == 0) { // 2 = Estimado || 0 = No
			if (!(validarCampo('lstCondicionGasto','t','lista') == true
			&& validarCampo('txtSubTotalFacturaGasto','t','monto') == true)) {
				validarCampo('lstCondicionGasto','t','lista');
				validarCampo('txtSubTotalFacturaGasto','t','monto');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_asignarFacturaCargo(xajax.getFormValues('frmListaRegistroCompra'), xajax.getFormValues('frmExpediente'), xajax.getFormValues('frmFacturaGasto'));
		}
	}
	
	function validarInsertarFacturaCompra(idFacturaCompra) {
		xajax_insertarFacturaCompra(idFacturaCompra, xajax.getFormValues('frmExpediente'));
	}
	
	function validarInsertarCargo(idGasto) {
		xajax_insertarCargo(idGasto, xajax.getFormValues('frmExpediente'));
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Expedientes de Importación</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblExpediente', '%s');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</a>
                    </td>
                </tr>
                </table>
				
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
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
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarExpediente(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
				</tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaExpediente" name="frmListaExpediente" style="margin:0">
            	<div id="divListaExpediente" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
				</div>
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
                            <td><img src="../img/iconos/pencil.png" /></td><td>Editar</td>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmExpediente" name="frmExpediente" onsubmit="return false;" style="margin:0">
	<input type="hidden" id="hddIdExpediente" name="hddIdExpediente"/>
	<div id="tblExpediente" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Nro. Expediente:</td>
                    <td width="32%"><input type="text" id="txtNumeroExpediente" name="txtNumeroExpediente"/></td>
                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Nro. Embarque / BL:</td>
                    <td width="36%"><input type="text" id="txtNumeroEmbarque" name="txtNumeroEmbarque"/></td>
				</tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td colspan="4">
            <fieldset><legend class="legend">Registro de Compra</legend>
            	<table align="left">
                <tr>
                    <td>
                    <a class="modalImg" id="aAgregarFactura" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblFacturaOtroCargo', 0);">
                        <button type="button" title="Agregar Factura"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                    </a>
                        <button type="button" id="btnEliminarFactura" name="btnEliminarFactura" onclick="xajax_eliminarFacturaCompra(xajax.getFormValues('frmExpediente'));" title="Eliminar Factura"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
				</tr>
                </table>
                
                <table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                    <td><input type="checkbox" id="cbxItmFactura" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                    <td width="8%">Fecha Registro Compra</td>
                    <td width="8%">Fecha</td>
                    <td width="8%">Nro. Factura</td>
                    <td width="26%">Tipo de Pedido</td>
                    <td width="8%">Nro. Pedido</td>
                    <td width="8%">Nro. Referencia</td>
                    <td width="16%">Proveedor</td>
                    <td width="6%">Items</td>
                    <td width="12%">Total Factura</td>
                </tr>
                <tr id="trItmPieFactura"></tr>
                </table>
                <input type="hidden" id="hddObjFactura" name="hddObjFactura" readonly="readonly"/>
			</fieldset>

            <fieldset><legend class="legend">Otros Cargos</legend>
            	<table align="left">
                <tr>
                    <td>
                    <a class="modalImg" id="aAgregarOtrosCargos" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaOtrosCargos');">
                        <button type="button" title="Agregar Cargo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                    </a>
                        <button type="button" id="btnEliminarOtrosCargos" name="btnEliminarOtrosCargos" onclick="xajax_eliminarCargo(xajax.getFormValues('frmExpediente'));" title="Eliminar Cargo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
				</tr>
                </table>
                
                <table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                    <td><input type="checkbox" id="cbxItmCargo" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                    <td width="32%">Gasto</td>
                    <td width="11%">Fecha Reg. Compra</td>
                    <td width="10%">Nro. Factura</td>
                    <td width="10%">Nro. Control</td>
                    <td width="25%">Proveedor</td>
                    <td width="12%">Subtotal</td>
                    <td></td>
                </tr>
                <tr id="trItmPieCargo" align="right" class="trResaltarTotal">
                	<td class="tituloCampo" colspan="6">Total:</td>
                    <td><span id="spnTotalFacturas"></span></td>
                    <td></td>
                </tr>
                </table>
                <input type="hidden" id="hddObjCargo" name="hddObjCargo" readonly="readonly"/>
			</fieldset>
            </td>
        </tr>
        <tr>
            <td align="right" colspan="4"><hr>
	            <button type="button" id="btnGuardarExpediente" name="btnGuardarExpediente" onclick="validarFrmExpediente();">Guardar</button>
                <button type="button" id="btnCancelarExpediente" name="btnCancelarExpediente" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <table border="0" id="tblListaOtrosCargos" width="760">
    <tr>
    	<td><div id="divListaOtrosCargos" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
        	<button type="button" id="btnCancelarOtrosCargos" name="btnCancelarOtrosCargos" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
   
    <div id="tblFacturaOtroCargo" style="max-height:520px; overflow:auto; width:960px;">
        <table width="100%">
        <tr>
            <td>
            <form id="frmFacturaGasto" name="frmFacturaGasto" style="margin:0" onsubmit="return false;">
                <input type="hidden" id="hddItmGasto" name="hddItmGasto"/>
                
                <table id="tblCondicionGasto" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="14%">Condición Gasto:</td>
                    <td width="20%">
                        <select id="lstCondicionGasto" name="lstCondicionGasto">
                            <option value="-1">[ Seleccion ]</option>
                            <option value="1">Real</option>
                            <option value="2">Estimado</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="14%">Asocia Documento:</td>
                    <td width="52%">
                        <select id="lstAsociaDocumento" name="lstAsociaDocumento">
                            <option value="-1">[ Seleccion ]</option>
                            <option value="0">No</option>
                            <option value="1">Si</option>
                        </select>
                    </td>
                </tr>
                </table>
                
            <fieldset id="fieldsetDatosFactura"><legend class="legend">Datos de la Factura</legend>
                <table width="100%">
                <tr>
                    <td>
                        <table>
                        <tr align="right">
                            <td class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Subtotal:</td>
                            <td><input type="text" id="txtSubTotalFacturaGasto" name="txtSubTotalFacturaGasto" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right"><hr>
                        <button type="submit" id="btnGuardarFacturaGasto" name="btnGuardarFacturaGasto" onclick="validarFrmFacturaGasto();">Aceptar</button>
                        <button type="button" id="btnCancelarFacturaGasto" name="btnCancelarFacturaGasto" class="close">Cerrar</button>
                    </td>
                </tr>
                </table>
            </fieldset>
            </form>
            
            <fieldset id="fieldsetListaRegistroCompra"><legend class="legend">Lista Reg. Compra</legend>
                <table width="100%">
                <tr>
                    <td>
                    <form id="frmBuscarRegistroCompra" name="frmBuscarRegistroCompra" style="margin:0" onsubmit="return false;">
        				<input type="hidden" id="hddIdModulo" name="hddIdModulo"/>
                        <table align="right">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" id="txtCriterioBuscarRegistroCompra" name="txtCriterioBuscarRegistroCompra" class="inputHabilitado" onkeyup="byId('btnBuscarRegistroCompra').click();"/></td>
                            <td>
                                <button type="submit" id="btnBuscarRegistroCompra" name="btnBuscarRegistroCompra" onclick="xajax_buscarRegistroCompra(xajax.getFormValues('frmBuscarRegistroCompra'));">Buscar</button>
                                <button type="button" onclick="document.forms['frmBuscarRegistroCompra'].reset(); byId('btnBuscarRegistroCompra').click();">Limpiar</button>
                                <input type="hidden" id="hddObjDestinoRegistroCompra" name="hddObjDestinoRegistroCompra"/>
                            </td>
                        </tr>
                        </table>
                    </form>
                    </td>
                </tr>
                <tr>
                    <td>
                    <form id="frmListaRegistroCompra" name="frmListaRegistroCompra" style="margin:0" onsubmit="return false;">
                        <input type="hidden" id="hddItmGastoListaRegistroCompra" name="hddItmGastoListaRegistroCompra"/>
                        <div id="divListaRegistroCompra" style="width:100%">
                            <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                            <tr>
                                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                            </tr>
                            </table>
                        </div>
                    </form>
                    </td>
                </tr>
                <tr>
                    <td align="right"><hr>
                        <button type="button" id="btnCancelarListaRegistroCompra" name="btnCancelarListaRegistroCompra" class="close">Cerrar</button>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        </table>
    </div>
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
xajax_listaExpediente(0, 'id_expediente', 'DESC');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>