<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if (!validaAcceso("cc_concepto_list","insertar")){
	echo "<script> alert('Acceso Denegado'); window.location='../index2.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_concepto_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Conceptos</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
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
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblConcepto').style.display = 'none';
		
		if (verTabla == "tblConcepto") {
			document.forms['frmConcepto'].reset();
			$('.cbxItmImpuesto').closest('tr').remove();
			byId('hddIdConcepto').value = '';
			
			byId('btnGuardarConcepto').style.display = '';
			byId('btnAgregarImpuesto').style.display = '';
			byId('btnEliminarImpuesto').style.display = '';
			
			xajax_formConcepto(valor, xajax.getFormValues('frmConcepto'), valor2);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Concepto';
			} else {
				tituloDiv1 = 'Agregar Concepto';
			}
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblConcepto") {
			byId('txtCodigoConcepto').focus();
			byId('txtCodigoConcepto').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaImpuesto').style.display = 'none';
			
		if (verTabla == "tblListaImpuesto") {
			document.forms['frmBuscarImpuesto'].reset();
			
			byId('btnBuscarImpuesto').click();
			tituloDiv2 = 'Impuestos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaImpuesto") {
			byId('txtCriterioBuscarImpuesto').focus();
			byId('txtCriterioBuscarImpuesto').select();
		}
	}
	
	function eliminarImpuestoConcepto(){
		$('.cbxItmImpuesto:checked').closest('tr').remove();
		
		$('.cbxItmImpuesto').closest('tr').each(function(index, element) {
			clase = ((index % 2) == 0) ? 'trResaltar4' : 'trResaltar5';
            element.className = clase + ' textoGris_11px';
        });
	}
	
	function validarEliminar(idConcepto){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarConcepto(idConcepto, xajax.getFormValues('frmListaConcepto'), 'false');
		}
	}
	
	function validarEliminarLote(){
		if (confirm('Seguro desea eliminar el(los) registro(s) seleccionado(s)?') == true) {
			xajax_eliminarConceptoLote(xajax.getFormValues('frmListaConcepto'), xajax.getFormValues('frmBuscar'));
		}
	}
	
	function validarFrmConcepto() {
		if (validarCampo('txtCodigoConcepto','t','') == true
		&& validarCampo('txtDescripcionConcepto','t','') == true
		&& validarCampo('lstTipoConcepto','t','lista') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true) {
			byId('btnGuardarConcepto').disabled = true;
			byId('btnCancelarConcepto').disabled = true;
			xajax_guardarConcepto(xajax.getFormValues('frmConcepto'), xajax.getFormValues('frmListaConcepto'));
		} else {
			validarCampo('txtCodigoConcepto','t','');
			validarCampo('txtDescripcionConcepto','t','');
			validarCampo('lstTipoConcepto','t','lista');
			validarCampo('lstEstatus','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr class="solo_print">
			<td align="left" id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td class="tituloPaginaCuentasPorCobrar"><span id="tituloPagina">Conceptos</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblConcepto');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    <a class="modalImg" id="aEliminar" rel="#divFlotante2" onclick="validarEliminarLote();">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/cross.png" title="Eliminar"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
                    </a>
					</td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Tipo Concepto:</td>
                    <td id="tdlstTipoConceptoBuscar"></td>
                	<td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstEstatusBuscar" name="lstEstatusBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Inactivo</option>
                            <option selected="selected" value="1">Activo</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarConcepto(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaConcepto" name="frmListaConcepto" style="margin:0">
            	<div id="divListaConcepto" style="width:100%"></div>
            </form>
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

<form id="frmConcepto" name="frmConcepto" style="margin:0;" onsubmit="return false;">
	<table id="tblConcepto" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td  align="right"class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Código:</td>
                <td width="70%"><input type="text" id="txtCodigoConcepto" name="txtCodigoConcepto"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td><input type="text" id="txtDescripcionConcepto" name="txtDescripcionConcepto" size="40"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Concepto:</td>
                <td id="tdlstTipoConcepto"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td>
                    <select id="lstEstatus" name="lstEstatus">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option selected="selected" value="1">Activo</option>
                    </select>
                </td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
        <td>
            <fieldset><legend class="legend">Impuestos</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <a class="modalImg" id="aNuevoImpuesto" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaImpuesto');">
                                    <button type="button" id="btnAgregarImpuesto" name="btnAgregarImpuesto"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                <button type="button" id="btnEliminarImpuesto" name="btnEliminarImpuesto" onclick="eliminarImpuestoConcepto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" class="texto_9px" width="100%">
                        <tr align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmImpuesto" onclick="selecAllChecks(this.checked,this.id,'frmConcepto');"/></td>
                            <td width="25%%">Tipo Impuesto</td>
                            <td width="55%">Observación</td>
                            <td width="20%">% Impuesto</td>
                        </tr>
                        <tr id="trItmPieImpuesto"></tr>
                        </table>
                    </td>
                </tr>
                </table>
            </fieldset>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
        	<input type="hidden" id="hddIdConcepto" name="hddIdConcepto"/>
            <button type="submit" id="btnGuardarConcepto" name="btnGuardarConcepto" onclick="validarFrmConcepto();">Aceptar</button>
            <button type="button" id="btnCancelarConcepto" name="btnCancelarConcepto" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaImpuesto" width="680">
    <tr>
    	<td>
        <form id="frmBuscarImpuesto" name="frmBuscarImpuesto" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarImpuesto" name="txtCriterioBuscarImpuesto" class="inputHabilitado" onkeyup="byId('btnBuscarImpuesto').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarImpuesto" name="btnBuscarImpuesto" onclick="xajax_buscarImpuesto(xajax.getFormValues('frmBuscarImpuesto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarImpuesto'].reset(); byId('btnBuscarImpuesto').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        	<div id="divListaImpuesto" style="width:100%"></div>
		</td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" class="close" id="btnCancelarImpuesto">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('lstEstatusBuscar').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

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

xajax_cargaLstTipoConcepto('lstTipoConceptoBuscar');
xajax_listaConcepto(0, 'id_concepto', 'DESC', '|' + byId('lstEstatusBuscar').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>