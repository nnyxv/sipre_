<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_articulo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_taller_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Talleres</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <!--<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script language="javascript" type="text/javascript">
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblTaller').style.display = 'none';
		
		if (verTabla == "tblTaller") {
			document.forms['frmTaller'].reset();
			byId('hddIdTaller').value = '';
			
			byId('txtNombre').className = 'inputHabilitado';
			byId('txtCedula').className = 'inputHabilitado';
			byId('txtDireccion').className = 'inputHabilitado';
			byId('txtTelefono').className = 'inputHabilitado';
			byId('txtContacto').className = 'inputHabilitado';
			
			xajax_formTaller(valor);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Taller';
			} else {
				tituloDiv1 = 'Agregar Taller';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblTaller") {
			byId('txtNombre').focus();
			byId('txtNombre').select();
		}
	}
	
	function validarFrmTaller() {
		if (validarCampo('txtNombre','t','') == true
		&& validarCampo('txtDireccion','t','') == true
		&& validarCampo('txtTelefono','t','') == true
		&& validarCampo('txtContacto','t','') == true) {
			xajax_guardarTaller(xajax.getFormValues('frmTaller'), xajax.getFormValues('frmListaTaller'));
		} else {
			validarCampo('txtNombre','t','');
			validarCampo('txtDireccion','t','');
			validarCampo('txtTelefono','t','');
			validarCampo('txtContacto','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminarLote(){
		if (confirm('¿Seguro desea eliminar los registros seleccionados?') == true) {
			xajax_eliminarTallerBloque(xajax.getFormValues('frmListaTaller'));
		}
	}
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaRepuestos">Talleres</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr class="noprint">
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblTaller');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    	<button type="button" onclick="validarEliminarLote();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
                    	<button type="button" onclick="xajax_encabezadoEmpresa('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>'); window.print();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarTaller(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaTaller" name="frmListaTaller" style="margin:0">
            	<div id="divListaTalleres" style="width:100%"></div>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>

<form id="frmTaller" name="frmTaller" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblTaller" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td width="35%"><input type="text" id="txtNombre" name="txtNombre" maxlength="50" size="26"></td>
            	<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span><?php echo $spanProvCxP; ?>:</td>
                <td width="35%">
                <div style="float:left">
                    <input type="text" id="txtCedula" name="txtCedula" maxlength="18" size="20" style="text-align:center"/>
                </div>
                <div style="float:left">
                    <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI; ?>"/>
                </div>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Dirección:</td>
                <td colspan="3"><textarea cols="66" id="txtDireccion" name="txtDireccion" rows="3"></textarea></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                <td><input type="text" id="txtTelefono" name="txtTelefono" maxlength="30" size="30"></td>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Contacto:</td>
                <td><input type="text" id="txtContacto" name="txtContacto" size="20"></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdTaller" name="hddIdTaller"/>
            <button type="submit" id="btnGuardarTaller" name="btnGuardarTaller" onclick="validarFrmTaller();">Guardar</button>
            <button type="button" id="btnCancelarTaller" name="btnCancelarTaller" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
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

xajax_listaTaller(0,'id_taller','DESC');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>