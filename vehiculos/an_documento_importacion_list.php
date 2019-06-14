<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_documento_importacion_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_documento_importacion_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Documentos de Importación Pendientes</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblGastoImportacion').style.display = 'none';
		byId('tblFacturaGasto').style.display = 'none';
		
		if (verTabla == "tblGastoImportacion") {
			xajax_cargarGastoImportacion(valor);
			tituloDiv1 = 'Gastos Nacionales de Importación';
		} else if (verTabla == "tblFacturaGasto") {
			document.forms['frmFacturaGasto'].reset();
			
			byId('txtNumeroFacturaProveedor').className = 'inputHabilitado';
			byId('txtNumeroFacturaProveedorCargo').className = 'inputHabilitado';
			
			xajax_frmFacturaGasto(valor);
			
			tituloDiv1 = 'Gastos Nacionales de Importación';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblFacturaOtroCargo').style.display = 'none';
		
		if (verTabla == "tblFacturaOtroCargo") {
			document.forms['frmBuscarRegistroCompra'].reset();
			document.forms['frmListaRegistroCompra'].reset();
			
			byId('txtFechaDesdeBuscarRegistroCompra').className = "inputHabilitado";
			byId('txtFechaHastaBuscarRegistroCompra').className = "inputHabilitado";
			byId('txtCriterioBuscarRegistroCompra').className = 'inputHabilitado';
			
			byId('btnBuscarRegistroCompra').click();
			tituloDiv2 = 'Lista Reg. Compra Cargo';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblFacturaOtroCargo") {
			byId('txtCriterioBuscarRegistroCompra').focus();
			byId('txtCriterioBuscarRegistroCompra').select();
		}
	}
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtBaseImpIva0','t','monto') == true)) {
			validarCampo('txtBaseImpIva0','t','monto') == true;
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea actualizar el registro de compra?') == true) {
				byId('btnGuardarTotalDcto').disabled = true;
				byId('btnCancelarTotalDcto').disabled = true;
				
				xajax_guardarBaseImponible(xajax.getFormValues('frmTotalDcto'));
			}
		}
	}
	
	function validarFrmFacturaGasto() {
		error = false;
		if (!(validarCampo('txtNumeroFacturaProveedor','t','') == true
		&& validarCampo('txtNumeroFacturaProveedorCargo','t','') == true)) {
			validarCampo('txtNumeroFacturaProveedor','t','');
			validarCampo('txtNumeroFacturaProveedorCargo','t','');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea aplicarle al cargo el registro de compra seleccionado?') == true) {
				byId('btnGuardarFacturaGasto').disabled = true;
				byId('btnCancelarFacturaGasto').disabled = true;
				
				xajax_guardarFacturaGasto(xajax.getFormValues('frmFacturaGasto'), xajax.getFormValues('frmListaGastoImportacion'));
			}
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Documentos de Importación Pendientes</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="window.location.href='iv_documento_importacion_form.php';"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</td>
                </tr>
                </table>
                
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
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarGastoImportacion(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
				</tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaGastoImportacion" name="frmListaGastoImportacion" style="margin:0">
            	<div id="divListaGastoImportacion" style="width:100%"></div>
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
                            <td><img src="../img/iconos/page_link.png"/></td><td>Asignar Dcto.</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/folder_edit.png"/></td><td>Editar Gastos Nacionales de Importación</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar</td>
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
	
<form id="frmTotalDcto" name="frmTotalDcto" onsubmit="return false;" style="margin:0">
    <input type="hidden" id="hddIdFactura" name="hddIdFactura"/>
    <input type="hidden" id="hddIdModulo" name="hddIdModulo"/>
    <input type="hidden" id="lstMoneda" name="lstMoneda" readonly="readonly"/>
    <input type="hidden" id="hddIdMoneda" name="hddIdMoneda" readonly="readonly"/>
    <input type="hidden" id="hddNacionalizar" name="hddNacionalizar"/>
	<div class="pane" style="max-height:500px; overflow:auto;">
        <table border="0" id="tblGastoImportacion" width="960">
        <tr>
            <td valign="top" width="50%">
            <fieldset><legend class="legend">Datos del Registro de Compra</legend>
            	<table width="100%">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="27%">Nro. Factura Prov.:</td>
                    <td width="23%"><span id="spnNumeroFacturaProveedor" name="spnNumeroFacturaProveedor"></span></td>
                	<td align="right" class="tituloCampo" width="27%">Nro. Control Prov.:</td>
                    <td width="23%"><span id="spnNumeroControl" name="spnNumeroControl"></span></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Fecha Factura Prov.:</td>
                    <td><span id="spnFechaProveedor" name="spnFechaProveedor"></span></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Tasa Cambio:</td>
                	<td><input type="text" id="txtTasaCambio" name="txtTasaCambio" readonly="readonly" size="16" style="text-align:right"/></td>
                </tr>
                </table>
            </fieldset>
            </td>
            <td valign="top" width="50%">
            <fieldset><legend class="legend">Gastos Nacionales de Importación</legend>
                <div id="tdGastos" width="100%"></div>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table border="0" width="100%">
                <tr align="right">
                    <td class="tituloCampo" width="36%">Subtotal:</td>
                    <td style="border-top:1px solid;" width="24%"></td>
                    <td style="border-top:1px solid;" width="13%"></td>
                    <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="5%"></td>
                    <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="right">
                    <td class="tituloCampo">Gastos:</td>
                    <td></td>
                    <td></td>
                    <td id="tdGastoConIvaMoneda"></td>
                    <td><input type="text" id="txtGastos" name="txtGastos" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="right" class="trResaltarTotal">
                    <td class="tituloCampo">Total Registro Compra:</td>
                    <td></td>
                    <td></td>
                    <td id="tdTotalRegistroMoneda"></td>
                    <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="right" class="trResaltarTotal">
                    <td class="tituloCampo">Total ADV:</td>
                    <td></td>
                    <td></td>
                    <td id="tdTotalAdValorem"></td>
                    <td><input type="text" id="txtSubTotalAdValorem" name="txtSubTotalAdValorem" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="right" id="trRetencionIva" style="display:none"></tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td align="right" colspan="2"><hr>
				<button type="submit" id="btnGuardarTotalDcto" name="btnGuardarTotalDcto" onclick="validarFrmDcto();">Guardar</button>
				<button type="button" id="btnCancelarTotalDcto" name="btnCancelarTotalDcto" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>

<form id="frmFacturaGasto" name="frmFacturaGasto" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblFacturaGasto" width="960">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Nro. Factura:</td>
            	<td width="32%"><input type="text" id="txtNumeroFacturaProveedor" name="txtNumeroFacturaProveedor" readonly="readonly" size="20" style="text-align:center"/></td>
            	<td align="right" class="tituloCampo" width="14%">Proveedor:</td>
            	<td width="40%">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td></td>
                        <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Gasto:</td>
            	<td><input type="text" id="txtNombreGasto" name="txtNombreGasto" readonly="readonly" size="45"/></td>
            	<td align="right" class="tituloCampo">Subtotal Estimado:</td>
            	<td><input type="text" id="txtSubtotalEstimado" name="txtSubtotalEstimado" maxlength="12" readonly="readonly" size="16" style="text-align:right"/></td>
            </tr>
            </table>
            
		<fieldset><legend class="legend">Factura del Cargo</legend>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Nro. Factura:</td>
            	<td width="36%">
                	<table border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <input type="text" id="txtNumeroFacturaProveedorCargo" name="txtNumeroFacturaProveedorCargo" readonly="readonly" size="20" style="text-align:center"/>
                            <input type="hidden" id="txtIdFacturaCargo" name="txtIdFacturaCargo" readonly="readonly" size="20" style="text-align:center"/>
                        </td>
                        <td></td>
                        <td>
                        <a class="modalImg" id="aListarFacturaCompra" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblFacturaOtroCargo');">
                            <button type="button" id="btnListarFacturaCompra" name="btnListarFacturaCompra" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                    </tr>
                    </table>
				</td>
            	<td align="right" class="tituloCampo" width="14%">Proveedor:</td>
            	<td width="36%">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdProvCargo" name="txtIdProvCargo" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td></td>
                        <td><input type="text" id="txtNombreProvCargo" name="txtNombreProvCargo" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
				</td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Subtotal:</td>
            	<td><input type="text" id="txtSubtotal" name="txtSubtotal" maxlength="12" readonly="readonly" size="16" style="text-align:right"/></td>
            </tr>
            </table>
        </fieldset>
        </td>
    </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" id="hddIdFacturaGasto" name="hddIdFacturaGasto"/>
	            <button type="button" id="btnGuardarFacturaGasto" name="btnGuardarFacturaGasto" onclick="validarFrmFacturaGasto();">Guardar</button>
                <button type="button" id="btnCancelarFacturaGasto" name="btnCancelarFacturaGasto" class="close">Cancelar</button>
            </td>
        </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
	
    <div id="tblFacturaOtroCargo" style="max-height:500px; overflow:auto; width:960px;">
        <table width="100%">
        <tr>
            <td>
            <fieldset id="fieldsetListaRegistroCompra"><legend class="legend">Lista Reg. Compra</legend>
                <table width="100%">
                <tr>
                    <td>
                    <form id="frmBuscarRegistroCompra" name="frmBuscarRegistroCompra" onsubmit="return false;" style="margin:0">
                        <table align="right">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Fecha:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;Desde:&nbsp;</td>
                                    <td><input type="text" id="txtFechaDesdeBuscarRegistroCompra" name="txtFechaDesdeBuscarRegistroCompra" autocomplete="off" size="10" style="text-align:center"/></td>
                                    <td>&nbsp;Hasta:&nbsp;</td>
                                    <td><input type="text" id="txtFechaHastaBuscarRegistroCompra" name="txtFechaHastaBuscarRegistroCompra" autocomplete="off" size="10" style="text-align:center"/></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" id="txtCriterioBuscarRegistroCompra" name="txtCriterioBuscarRegistroCompra" onkeyup="byId('btnBuscarRegistroCompra').click();"/></td>
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
                    <form id="frmListaRegistroCompra" name="frmListaRegistroCompra" onsubmit="return false;" style="margin:0">
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
	   $("#txtFechaDesdeBuscarRegistroCompra").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	   $("#txtFechaHastaBuscarRegistroCompra").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesdeBuscarRegistroCompra",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHastaBuscarRegistroCompra",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
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
xajax_listaGastoImportacion(0, 'id_factura_gasto', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>