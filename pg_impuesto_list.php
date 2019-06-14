<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_impuesto_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_pg_impuesto_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Impuestos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>

	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblImpuesto').style.display = 'none';
		
		if (verTabla == "tblImpuesto") {
			document.forms['frmImpuesto'].reset();
			byId('hddIdImpuesto').value = '';
			
			byId('txtObservacion').className = 'inputHabilitado';
			byId('txtImpuesto').className = 'inputHabilitado';
			byId('lstEstado').className = 'inputHabilitado';
			
			xajax_formImpuesto(valor, xajax.getFormValues('frmImpuesto'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Impuesto';
			} else {
				tituloDiv1 = 'Agregar Impuesto';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblImpuesto") {
			byId('txtObservacion').focus();
			byId('txtObservacion').select();
		}
	}
	
	function validarFrmImpuesto() {
		if (validarCampo('txtObservacion','t','') == true
		&& validarCampo('txtImpuesto','t','monto') == true
		&& validarCampo('lstTipoImpuesto','t','lista') == true
		&& validarCampo('lstEstado','t','listaExceptCero') == true) {
			xajax_guardarImpuesto(xajax.getFormValues('frmImpuesto'), xajax.getFormValues('frmListaImpuesto'));
		} else {
			validarCampo('txtObservacion','t','');
			validarCampo('txtImpuesto','t','monto');
			validarCampo('lstTipoImpuesto','t','lista');
			validarCampo('lstEstado','t','listaExceptCero');

			alert("Los campos señalados en rojo son requeridos.");
			return false;
		}
	}
	
	function validarEliminar(idImpuesto){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarImpuesto(idImpuesto, xajax.getFormValues('frmListaImpuesto'));
		}
	}

	function validarPredeterminar(idIvaNuevo, estatusPredet){
		if (confirm("Ya existe un Impuesto del mismo tipo como predeterminado. ¿Desea predeterminarlo de igual forma?") == true) {
			xajax_predeterminarImpuesto(idIvaNuevo, estatusPredet, xajax.getFormValues('frmListaImpuesto'));
		}
	}
    </script>

</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
        <tr>
            <td class="tituloPaginaErp">Impuestos</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImpuesto');">
                    	<button type="button" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
            <form id="frmBuscar" name="frmBuscar">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstEstatusBuscar" name="lstEstatusBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Inactivo</option>
                            <option selected="selected" value="1">Activo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Tipo Impuesto:</td>
                    <td id="tdlstTipoImpuestoBuscar"></td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" size="20"/></td>
                    <td>
                        <button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarImpuesto(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaImpuesto" name="frmListaImpuesto" style="margin:0">
            	<div id="divListaImpuesto" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="img/iconos/ico_info.gif" width="25" class="puntero"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="img/iconos/accept.png"/></td><td>Predeterminar</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/cancel.png"/></td><td>Quitar Predeterminado</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/pencil.png"/></td><td>Editar</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/ico_delete.png"/></td><td>Eliminar</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan ="2">
                <table cellpadding="0" cellspacing="0" class="divMsjError" width="100%">
                <tr>
                    <td width="25"><img src="img/iconos/ico_info3.gif" width="25" class="puntero"/></td>
                    <td align="center">
                        <table>
                        <tr><td>&nbsp;</td>
                            <td>Si el impuesto es Predeterminado no puede desactivarse hasta que se defina otro del mismo Tipo.</td>
                            <td>&nbsp;</td>
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

<form id="frmImpuesto" name="frmImpuesto" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblImpuesto" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Observación:</td>
                <td width="75%"><input type="text" id="txtObservacion" name="txtObservacion" size="30"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td><input type="text" id="txtImpuesto" name="txtImpuesto" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo:</td>
                <td id="tdSelTipoImpuesto">
                    <select id="lstTipoImpuesto" name="lstTipoImpuesto">
                        <option value="-1">[ Seleccione ] </option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estado:</td>
                <td>
                    <select id="lstEstado" name="lstEstado">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>  
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdImpuesto" name="hddIdImpuesto"/>
            <button type="submit" id="btnGuardarImpuesto" name="btnGuardarImpuesto" onclick="validarFrmImpuesto();">Guardar</button>
            <button type="button" id="btnCancelarImpuesto" name="btnCancelarImpuesto" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('lstEstatusBuscar').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

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

xajax_cargaLstTipoImpuestoBuscar();
xajax_listaImpuesto(0, 'idIva', 'ASC', byId('lstEstatusBuscar').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>