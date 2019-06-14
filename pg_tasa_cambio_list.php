<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_tasa_cambio_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_pg_tasa_cambio_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tasa de Cambio</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <!--<script type="text/javascript" language="javascript" src="js/mootools.js"></script>-->
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblTasaCambio').style.display = 'none';
		
		if (verTabla == "tblTasaCambio") {
			document.forms['frmTasaCambio'].reset();
			byId('hddIdTasaCambio').value = '';
			
			byId('txtNombreTasaCambio').className = 'inputHabilitado';
			byId('txtIdMonedaExtranjera').className = 'inputHabilitado';
			byId('txtIdMonedaNacional').className = 'inputHabilitado';
			byId('txtMontoTasaCambio').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			
			xajax_formTasaCambio(valor);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Tasa de Cambio';
			} else {
				tituloDiv1 = 'Agregar Tasa de Cambio';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblTasaCambio") {
			byId('txtNombreTasaCambio').focus();
			byId('txtNombreTasaCambio').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblListaMoneda').style.display = 'none';
		
		if (verTabla == "tblListaMoneda") {
			document.forms['frmBuscarMoneda'].reset();
			
			byId('hddObjDestino').value = valor;
			
			byId('txtCriterioBuscarMoneda').className = 'inputHabilitado';
			
			byId('btnBuscarMoneda').click();
			
			tituloDiv2 = 'Monedas';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaMoneda") {
			byId('txtCriterioBuscarMoneda').focus();
			byId('txtCriterioBuscarMoneda').select();
		}
	}
	
	function validarFrmTasaCambio() {
		if (validarCampo('txtNombreTasaCambio','t','') == true
		&& validarCampo('txtIdMonedaExtranjera','t','numPositivo') == true
		&& validarCampo('txtIdMonedaNacional','t','numPositivo') == true
		&& validarCampo('txtMontoTasaCambio','t','numPositivo') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true) {
			byId('btnGuardarTasaCambio').disabled = true;
			byId('btnCancelarTasaCambio').disabled = true;
			xajax_guardarTasaCambio(xajax.getFormValues('frmTasaCambio'), xajax.getFormValues('frmListaTasaCambio'));
		} else {
			validarCampo('txtNombreTasaCambio','t','');
			validarCampo('txtIdMonedaExtranjera','t','numPositivo');
			validarCampo('txtIdMonedaNacional','t','numPositivo');
			validarCampo('txtMontoTasaCambio','t','numPositivo');
			validarCampo('lstEstatus','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idTasaCambio){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarTasaCambio(idTasaCambio, xajax.getFormValues('frmListaTasaCambio'));
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
        	<td class="tituloPaginaErp">Tasa de Cambio</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblTasaCambio');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right">			
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarTasaCambio(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaTasaCambio" name="frmListaTasaCambio" style="margin:0">
            	<div id="divListaTasaCambio" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                        <tr>
                        	<td><img src="img/iconos/ico_verde.gif"/></td><td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/ico_rojo.gif"/></td><td>Inactivo</td>
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
    
<form id="frmTasaCambio" name="frmTasaCambio" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblTasaCambio" width="500">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td><input type="text" id="txtNombreTasaCambio" name="txtNombreTasaCambio" size="30"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Moneda Extranjera:</td>
                <td width="70%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdMonedaExtranjera" name="txtIdMonedaExtranjera" onkeyup="xajax_asignarMoneda(this.value,'MonedaExtranjera');" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMoneda', 'MonedaExtranjera');">
                            <button type="button" id="btnListarMonedaExtranjera" name="btnListarMonedaExtranjera" title="Listar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtMonedaExtranjera" name="txtMonedaExtranjera" readonly="readonly" size="26"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda Nacional:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdMonedaNacional" name="txtIdMonedaNacional" onkeyup="xajax_asignarMoneda(this.value,'MonedaNacional');" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMoneda', 'MonedaNacional');">
                            <button type="button" id="btnListarMonedaNacional" name="btnListarMonedaNacional" title="Listar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtMonedaNacional" name="txtMonedaNacional" readonly="readonly" size="26"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tasa de Cambio:</td>
                <td><input type="text" id="txtMontoTasaCambio" name="txtMontoTasaCambio" onblur="setFormatoRafk(this,3);" onkeypress="return validarSoloNumerosReales(event);" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
            	<td>
                	<select id="lstEstatus" name="lstEstatus">
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
            <input type="hidden" id="hddIdTasaCambio" name="hddIdTasaCambio"/>
            <button type="submit" id="btnGuardarTasaCambio" name="btnGuardarTasaCambio" onclick="validarFrmTasaCambio();">Guardar</button>
            <button type="button" id="btnCancelarTasaCambio" name="btnCancelarTasaCambio" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaMoneda" width="700">
    <tr>
    	<td>
        <form id="frmBuscarMoneda" name="frmBuscarMoneda" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMoneda" name="txtCriterioBuscarMoneda" onkeyup="byId('btnBuscarMoneda').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarMoneda" name="btnBuscarMoneda" onclick="xajax_buscarMoneda(xajax.getFormValues('frmBuscarMoneda'));">Buscar</button>
                    <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td><div id="divListaMoneda" style="width:100%"></div></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaMoneda" name="btnCancelarListaMoneda" class="close">Cerrar</button>
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

xajax_listaTasaCambio(0,'id_tasa_cambio','ASC');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>