<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if (!validaAcceso("cc_tipo_concepto_list","insertar")){
	echo "<script> alert('Acceso Denegado'); window.location='../index2.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_tipo_concepto_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Tipos de Conceptos</title>
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
		byId('tblTipoConcepto').style.display = 'none';
		
		if (verTabla == "tblTipoConcepto") {
			document.forms['frmTipoConcepto'].reset();
			byId('hddIdTipoConcepto').value = '';
			
			byId('btnGuardarTipoConcepto').style.display = '';
			
			xajax_formTipoConcepto(valor, xajax.getFormValues('frmTipoConcepto'), valor2);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Tipo Concepto';
			} else {
				tituloDiv1 = 'Agregar Tipo Concepto';
			}
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblTipoConcepto") {
			byId('txtDescripcionTipoConcepto').focus();
			byId('txtDescripcionTipoConcepto').select();
		}
	}
	
	function validarEliminar(idTipoConcepto){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarTipoConcepto(idTipoConcepto, xajax.getFormValues('frmListaTipoConcepto'), 'false');
		}
	}
	
	function validarEliminarLote(){
		if (confirm('Seguro desea eliminar el(los) registro(s) seleccionado(s)?') == true) {
			xajax_eliminarTipoConceptoLote(xajax.getFormValues('frmListaTipoConcepto'), xajax.getFormValues('frmBuscar'));
		}
	}
	
	function validarFrmTipoConcepto() {
		if (validarCampo('txtDescripcionTipoConcepto','t','') == true) {
			byId('btnGuardarTipoConcepto').disabled = true;
			byId('btnCancelarTipoConcepto').disabled = true;
			xajax_guardarTipoConcepto(xajax.getFormValues('frmTipoConcepto'), xajax.getFormValues('frmListaTipoConcepto'));
		} else {
			validarCampo('txtDescripcionTipoConcepto','t','');
			
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
			<td class="tituloPaginaCuentasPorCobrar"><span id="tituloPagina">Tipos de Conceptos</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblTipoConcepto');">
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
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarTipoConcepto(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaTipoConcepto" name="frmListaTipoConcepto" style="margin:0">
            	<div id="divListaTipoConcepto" style="width:100%"></div>
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

<form id="frmTipoConcepto" name="frmTipoConcepto" style="margin:0;" onsubmit="return false;">
	<table id="tblTipoConcepto" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td width="70%"><input type="text" id="txtDescripcionTipoConcepto" name="txtDescripcionTipoConcepto" size="40"/></td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
        <td align="right"><hr>
        	<input type="hidden" id="hddIdTipoConcepto" name="hddIdTipoConcepto"/>
            <button type="submit" id="btnGuardarTipoConcepto" name="btnGuardarTipoConcepto" onclick="validarFrmTipoConcepto();">Aceptar</button>
            <button type="button" id="btnCancelarTipoConcepto" name="btnCancelarTipoConcepto" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
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

xajax_listaTipoConcepto(0, 'id_tipo_concepto', 'DESC');
</script>