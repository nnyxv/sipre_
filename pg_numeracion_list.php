<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_numeracion_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_pg_numeracion_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Numeraciones</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <!--<script type="text/javascript" language="javascript" src="js/mootools.js"></script>-->
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    
    <script>
	function formListaEmpresas(nomObjeto, objDestino, nomVentana) {
		openImg(nomObjeto);
		
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = objDestino;
		byId('hddNomVentana').value = nomVentana;
		
		byId('btnBuscarEmpresa').click();
		
		byId('tblListaEmpresa').style.display = '';
		byId('tblListaNumeracion').style.display = 'none';
		
		byId('tdFlotanteTitulo2').innerHTML = "Empresas";
		
		byId('txtCriterioBuscarEmpresa').focus();
		byId('txtCriterioBuscarEmpresa').select();
	}
	
	function formListaNumeracion() {
		document.forms['frmBuscarNumeracion'].reset();
		
		byId('btnBuscarNumeracion').click();
		
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblListaNumeracion').style.display = '';
		
		byId('tdFlotanteTitulo2').innerHTML = "Numeración";
		
		byId('txtCriterioBuscarNumeracion').focus();
		byId('txtCriterioBuscarNumeracion').select();
	}
	
	function validarForm() {
		if (validarCampo('txtIdEmpresa','t','numPositivo') == true
		&& validarCampo('txtIdNumeracion','t','numPositivo') == true
		&& validarCampo('txtNumeroInicio','t','numPositivo') == true
		&& validarCampo('txtNumeroActual','t','numPositivo') == true
		&& validarCampo('lstAplicaSucursales','t','listaExceptCero') == true) {
			xajax_guardarEmpresaNumeracion(xajax.getFormValues('frmEmpresaNumeracion'), xajax.getFormValues('frmListaEmpresaNumeracion'));
		} else {
			validarCampo('txtIdEmpresa','t','numPositivo');
			validarCampo('txtIdNumeracion','t','numPositivo');
			validarCampo('txtNumeroInicio','t','numPositivo');
			validarCampo('txtNumeroActual','t','numPositivo');
			validarCampo('lstAplicaSucursales','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idTasaCambio){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarEmpresaNumeracion(idTasaCambio, xajax.getFormValues('frmListaEmpresaNumeracion'));
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
        	<td class="tituloPaginaErp">Numeraciones</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="xajax_formEmpresaNumeracion(this.id);">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select name="lstEmpresa" id="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarEmpresaNumeracion(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaEmpresaNumeracion" name="frmListaEmpresaNumeracion" style="margin:0">
            	<div id="divListaEmpresaNumeracion" style="width:100%"></div>
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
    
<form id="frmEmpresaNumeracion" name="frmEmpresaNumeracion" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblEmpresaNumeracion" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td width="75%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="formListaEmpresas(this,'Empresa','ListaEmpresa');">
                        	<button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Numeración:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdNumeracion" name="txtIdNumeracion" onblur="xajax_asignarNumeracion(this.value);" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" rel="#divFlotante2" onclick="openImg(this); formListaNumeracion();">
                            <button type="button" id="btnListarNumeracion" name="btnListarNumeracion" title="Listar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNumeracion" name="txtNumeracion" readonly="readonly" size="26"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Número Inicio:</td>
                <td><input type="text" id="txtNumeroInicio" name="txtNumeroInicio" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Número Actual:</td>
            	<td><input type="text" id="txtNumeroActual" name="txtNumeroActual" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Aplicar a sus Sucursales:</td>
            	<td>
                	<select id="lstAplicaSucursales" name="lstAplicaSucursales">
                    	<option value="-1">[ Seleccione]</option>
                    	<option value="0">No</option>
                    	<option value="1">Si</option>
                    </select>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdEmpresaNumeracion" name="hddIdEmpresaNumeracion"/>
            <button type="submit" id="btnGuardarEmpresaNumeracion" name="btnGuardarEmpresaNumeracion" onclick="validarForm();">Guardar</button>
            <button type="button" id="btnCancelarEmpresaNumeracion" name="btnCancelarEmpresaNumeracion" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpresa" name="frmListaEmpresa" style="margin:0" onsubmit="return false;">
            <div id="divListaEmpresa" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaNumeracion" width="760">
    <tr>
    	<td>
        <form id="frmBuscarNumeracion" name="frmBuscarNumeracion" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarNumeracion" name="txtCriterioBuscarNumeracion" class="inputHabilitado" onkeyup="byId('btnBuscarNumeracion').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarNumeracion" name="btnBuscarNumeracion" onclick="xajax_buscarNumeracion(xajax.getFormValues('frmBuscarNumeracion'));">Buscar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td><div id="divListaNumeracion" style="width:100%"></div></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaNumeracion" name="btnCancelarListaNumeracion" class="close">Cerrar</button>
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaEmpresaNumeracion(0,'id_numeracion','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>