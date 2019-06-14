<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("al_servicio_mantenimiento_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_al_servicio_mantenimiento_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Alquiler - Recordatorios de Servicios y Mantenimientos</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
	<link rel="stylesheet" type="text/css" href="../js/domDragAlquiler.css">
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblServicioMantenimiento').style.display = 'none';
		$('.itemMarca').remove();
		
		if (verTabla == "tblServicioMantenimiento") {
			document.forms['frmServicioMantenimiento'].reset();
			byId('hddIdServicioMantenimiento').value = '';
			
			byId('lstActivo').className = 'inputHabilitado';
			byId('txtDescripcionServicioMantenimiento').className = 'inputHabilitado';
			byId('txtKilometraje').className = 'inputHabilitado';
			byId('txtKilometrajeAntes').className = 'inputHabilitado';
			byId('txtKilometrajeDespues').className = 'inputHabilitado';
			
			xajax_formServicioMantenimiento(valor);	
			
			if (valor > 0) {				
				tituloDiv1 = 'Editar Servicio / Mantenimiento';
			} else {
				tituloDiv1 = 'Agregar Servicio / Mantenimiento';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblServicioMantenimiento") {
			byId('txtDescripcionServicioMantenimiento').focus();
			byId('txtDescripcionServicioMantenimiento').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaMarca').style.display = 'none';
		
		if (verTabla == "tblListaMarca") {
			document.forms['frmBuscarMarca'].reset();
			
			byId('btnBuscarMarca').click();			
			tituloDiv2 = 'Marcas';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaMarca") {
			byId('txtCriterioBuscarMarca').focus();
			byId('txtCriterioBuscarMarca').select();
		}
	}
	
	function eliminarMarca(obj){
		$(obj).parent().parent().remove();
	}
	
	function validarFrmServicioMantenimiento() {
		error = false;
				
		if (!(validarCampo('lstActivo','t','listaExceptCero') == true
		&& validarCampo('txtDescripcionServicioMantenimiento','t','') == true
		&& validarCampo('txtKilometraje','t','') == true
		&& validarCampo('txtKilometrajeAntes','t','') == true
		&& validarCampo('txtKilometrajeDespues','t','') == true)) {
			validarCampo('lstActivo','t','listaExceptCero');
			validarCampo('txtDescripcionServicioMantenimiento','t','');
			validarCampo('txtKilometraje','t','');
			validarCampo('txtKilometrajeAntes','t','');
			validarCampo('txtKilometrajeDespues','t','');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarServicioMantenimiento(xajax.getFormValues('frmServicioMantenimiento'));
		}
	}
	
	function validarEliminar(idServicioMantenimiento){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarServicioMantenimiento(idServicioMantenimiento);
		}
	}
	
	function validarSoloTextoNumero(evento) {
		if (arguments.length > 1)
			color = arguments[1];
		
		if (evento.target)
			idObj = evento.target.id
		else if (evento.srcElement)
			idObj = evento.srcElement.id;
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 0)
		&& (teclaCodigo != 8)
		&& (teclaCodigo != 32)
		&& (teclaCodigo < 65 || teclaCodigo > 90)
		&& (teclaCodigo < 97 || teclaCodigo > 122)
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)
		&& (teclaCodigo != 225) /* á */
		&& (teclaCodigo != 233) /* é */
		&& (teclaCodigo != 237) /* í */
		&& (teclaCodigo != 243) /* ó */
		&& (teclaCodigo != 250) /* ú */
		&& (teclaCodigo != 193) /* Á */
		&& (teclaCodigo != 201) /* É */
		&& (teclaCodigo != 205) /* Í */
		&& (teclaCodigo != 211) /* Ó */
		&& (teclaCodigo != 218) /* Ú */
		&& (teclaCodigo != 209) /* Ñ */
		&& (teclaCodigo != 241) /* ñ */
		) {
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_alquiler.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaAlquiler">Recordatorios de Servicios y Mantenimientos</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblServicioMantenimiento');">
							<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
						</a>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
					<td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstActivoBuscar" name="lstActivoBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Inactivo</option>
                            <option value="1">Activo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarServicioMantenimiento(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>            
                <div id="divListaServicioMantenimiento" style="width:100%"></div>
            </td>
        </tr>            
        <tr>
             <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
             <tr>
                <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                <td align="center">
                    <table>
                    <tr>
                        <td><img src="../img/iconos/ico_verde.gif"></td><td>Activo</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_rojo.gif"></td><td>Inactivo</td>                                            
                    </tr>
                    </table>
                </td>
            </tr>
            </table>   
        </tr>                    
	</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmServicioMantenimiento" name="frmServicioMantenimiento" style="margin:0" onsubmit="return false;">
	<table border="0" id="tblServicioMantenimiento" width="680">
    <tr>
        <td>
            <table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td colspan="2"><input type="text" id="txtDescripcionServicioMantenimiento" name="txtDescripcionServicioMantenimiento" onkeypress="return validarSoloTextoNumero(event);" style="width:99%"/></td>
                <td width="120">&nbsp;</td>
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td>
                    <select id="lstActivo" name="lstActivo" style="width:99%">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1" selected="selected">Activo</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?>:</td>
                <td><input type="text" id="txtKilometraje" name="txtKilometraje" onkeypress="return validarSoloNumeros(event);" style="width:99%"/></td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?> Antes:</td>
                <td><input type="text" id="txtKilometrajeAntes" name="txtKilometrajeAntes" onkeypress="return validarSoloNumeros(event);" style="width:99%"/></td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?> Después:</td>
                <td><input type="text" id="txtKilometrajeDespues" name="txtKilometrajeDespues" onkeypress="return validarSoloNumeros(event);" style="width:99%"/></td>
			</tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td colspan="15">
            <fieldset>
                <legend class="legend">Marcas</legend>                
                <table width="100%">
                <tr align="left">
                    <td>
						<a class="modalImg" id="aNuevaMarca" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMarca');">
							<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
						</a>
                    </td>
                </tr>
                <tr>
                    <td>
                    	<div style="max-height:360px; overflow-y:auto; overflow-x : hidden;">
                        <table border="0" width="100%" class="texto_9px tablaResaltarPar">
                        <thead>
                        <tr align="center" class="tituloColumna">
                        	<td width="100%">Marca</td>
                        	<td></td>
                        </tr>
                        </thead>
                        <tr id="trItmPieMarca"></tr>
                        </table>
                        </div>
                    </td>
                </tr>
                </table>                
			</fieldset>
        </td>
    </tr>
	<tr>
        <td>
            <table cellspacing="0" cellpadding="0" width="100%" class="divMsjInfo2">
            <tr>
                <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                <td align="center">
                    <table>
                    <tr>
                        <td>El recordatorio se ralizará entre el "Antes y Después"</td>
                    </tr>
                    <tr>
                        <td>Ej: 30000 Base, Antes: 2000, Después: 5000 = Entre 28000 y 35000</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <input type="hidden" id="hddIdServicioMantenimiento" name="hddIdServicioMantenimiento"/>
            <button type="button" id="btnGuardarServicioMantenimiento" name="btnGuardarServicioMantenimiento" onclick="validarFrmServicioMantenimiento();">Guardar</button>
            <button type="button" id="btnCancelarServicioMantenimiento" name="btnCancelarServicioMantenimiento" class="close">Cancelar</button>
        </td>
    </tr>
    </table>

</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaMarca" width="680">
    <tr>
    	<td>
        <form id="frmBuscarMarca" name="frmBuscarMarca" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMarca" name="txtCriterioBuscarMarca" class="inputHabilitado" onkeyup="byId('btnBuscarMarca').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarMarca" name="btnBuscarMarca" onclick="xajax_buscarMarca(xajax.getFormValues('frmBuscarMarca'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarMarca'].reset(); byId('btnBuscarMarca').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        	<div id="divListaMarca" style="width:100%"></div>
		</td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('lstActivoBuscar').className = 'inputHabilitado';
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

xajax_listaServicioMantenimiento();

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

</script>