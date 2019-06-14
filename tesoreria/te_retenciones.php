<?php

require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("te_retenciones"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_retenciones.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento de Retenciones</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    
    <script>
	
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		if (verTabla == 'tblRetencion') {
			document.forms['frmRetencion'].reset();
			byId('hddIdRetencion').value = '';
			
			xajax_formRetencion(valor);
			
			if (valor > 0) {
				tituloDiv1 = 'Nueva Retención';
			} else {
				tituloDiv1 = 'Editar Retención';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo').innerHTML = tituloDiv1;
	}
	
	function validarRetencion(){
		if (validarCampo('txtDescripcion','t','') == true
		&&  validarCampo('txtImporte','t','') == true
		&&  validarCampo('txtUnidadTributaria','t','') == true
		&&  validarCampo('txtRetencion','t','monto') == true
		&&  validarCampo('txtSustraendo','t','') == true
		&&  validarCampo('txtCodigo','t','') == true){
			xajax_guardarRetencion(xajax.getFormValues('frmRetencion'));
		} else {
			validarCampo('txtDescripcion','t','');
			validarCampo('txtImporte','t','');
			validarCampo('txtUnidadTributaria','t','');
			validarCampo('txtRetencion','t','monto');
			validarCampo('txtSustraendo','t','');
			validarCampo('txtCodigo','t','');
						
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function letrasNumerosEspeciales(e) {
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[-,.()0-9A-Za-z\s ]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}
	
	function numeros(e) {
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[0-9]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include ('banner_tesoreria.php'); ?></div>	
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaTesoreria" colspan="2">Mantenimiento de Retenciones</td>
		</tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
		<tr class="noprint">
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="abrirDivFlotante1(this, 'tblRetencion');">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
					</td>
				</tr>
				</table>
                
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr>
                    <td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td align="left">
                        <select id="lstEstado" name="lstEstado" onchange="byId('btnBuscar').click();" class="inputHabilitado">
                            <option value="-1">[ Todos ]</option>
                            <option selected="selected" value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                    <td><button id="btnBuscar" name="btnBuscar" type="button" class="noprint" onclick="xajax_buscarRetencion(xajax.getFormValues('frmBuscar'));" >Buscar</button>									
                    </td>
                    <td>
                        <button type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();" >Limpiar</button>
                    </td>
                </tr>
                </table>
                </form>
            </td>
        </tr>    
        <tr>
            <td id="tdListaRetenciones">
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                <tr>
                    <td width="25"></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_verde.gif"/></td>
                            <td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td>
                            <td>Inactivo</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
		</table>
    </div>
	<div class="noprint"><?php include("pie_pagina.php") ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    <form id="frmRetencion" name="frmRetencion">
    <table border="0" id="tblRetencion" width="370px">
    <tr align="left">
    	<td>
            <table border="0">
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Descripcion:</td>
                <td>
                    <input type="text" id="txtDescripcion" name="txtDescripcion" size="30" onkeypress="return letrasNumerosEspeciales(event);" class="inputHabilitado"/>
                    <input type="hidden" id="hddIdRetencion" name="hddIdRetencion" />
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Importe >= Para Aplicar:</td>
                <td><input type="text" id="txtImporte" name="txtImporte" size="30" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);" class="inputHabilitado"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Unidad Tributaria:</td>
                <td><input type="text" id="txtUnidadTributaria" name="txtUnidadTributaria"  size="30" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);" class="inputHabilitado"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>% Retención:</td>
                <td><input type="text" id="txtRetencion" name="txtRetencion"  size="30" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);" class="inputHabilitado"/></td>
    </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Sustraendo en Retención:</td>
                <td><input type="text" id="txtSustraendo" name="txtSustraendo"  size="30" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);" class="inputHabilitado"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Codigo Concepto:</td>
                <td><input type="text" id="txtCodigo" name="txtCodigo"  size="30" onkeypress="return numeros(event);" class="inputHabilitado"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Estado:</td>
                <td>
                    <select name="lstActivo" id="lstActivo" class="inputHabilitado">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
        <td align="right"><hr>
        	<button type="button" id="btnGuardar" onclick="validarRetencion();">Guardar</button>
            <button type="button" id="btnCancelar" class="close">Cancelar</button>
        </td>
    </tr>
	</table>
    </form>
</div>

<script language="javascript">
xajax_listaRetenciones(0,'','','1');

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

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>