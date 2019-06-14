<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_tipo_pago_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_crm_contenido_correo.php");
include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Correo</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCrm.css">
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
	//VALIDA LOS CAMPOS DEL FORM 
	function validarForm() {
		if ( validarCampo('lstTipoCorreo','t','listaExceptCero') == true
		&& validarCampo('txtAsuntoCorreo','t','') == true 
		&& validarCampo('contenidoCorreo','t','') == true 
		&& validarCampo('lstEstatus','t','listaExceptCero') == true) {
			xajax_guardarContenidoCorreo(xajax.getFormValues('frmContenidoCorreo'), xajax.getFormValues('frmListaConfiguracion'));
		} else {
			validarCampo('lstTipoCorreo','t','listaExceptCero');
			validarCampo('txtAsuntoCorreo','t','');
			validarCampo('contenidoCorreo','t','');
			validarCampo('lstEstatus','t','listaExceptCero');
			
			alert("Los campos en color rojo son obligatorios");
			return false;
		}
	}
	
	//CONFIRMACION DE ELIMINACION
	function validarEliminar(idTipoPago){
	//return 	alert(idTipoPago);
	
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarTipoPago(idTipoPago);
		}
	}
	
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include("banner_crm.php"); ?>
    </div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCrm">Correo</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="xajax_formContenidoCorreo(this.id);">
                    	<button type="button" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaConfiguracion" name="frmListaConfiguracion" style="margin:0">
            	<div id="divListaConfiguracion" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif" /></td>
                            <td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif" /></td>
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
    
    <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
<form id="frmContenidoCorreo" name="frmContenidoCorreo" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblClaveEspecial" width="560">
    <tr>
    	<td>
        	<table width="100%" border="0">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Correo:</td>
                <td>
                	<select id="lstTipoCorreo" name="lstTipoCorreo">
                    	<option value="">[ Seleccione ]</option>
                        <option value="correoCumpleaño">Cumpleaño</option>
                        <option value="ofreserServicio">Ofreser servicio</option>
                    </select>
                </td>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Asunto del correo:</td>
                <td><input type="text" id="txtAsuntoCorreo" name="txtAsuntoCorreo" size="40"/></td>
            </tr>
                        <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Contenido del correo:</td>
                <td><textarea name="contenidoCorreo" id="contenidoCorreo" cols="50" rows="5"></textarea></td>
            </tr>

            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td>
                	<select id="lstEstatus" name="lstEstatus">
                    	<option value="">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>

                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="hidden" id="hddIdContenidoCorreo" name="hddIdContenidoCorreo"/>
            <button type="submit" id="btnGuardar" name="btnGuardar" onclick="validarForm();">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_save.png"/></td>
                        <td>&nbsp;</td>
                        <td>Guardar</td>
                    </tr>
                </table>
            </button>
            <button type="button" id="btnCancelar" name="btnCancelar" class="close">
                <table align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_error.gif"/></td>
                        <td>&nbsp;</td>
                        <td>Cancelar</td>
                    </tr>
                </table>
            </button>
        </td>
    </tr>
    </table>
</form>
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

xajax_cargaLstEmpresa('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

xajax_listadoContenidoCorreo(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>
<script language="javascript">
var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

</script>