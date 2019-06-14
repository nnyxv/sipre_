<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_cargo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_pg_cargo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cargos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/');//indicamos al objeto xajax se encargue de generar javascript necesario ?>

    <link rel="stylesheet" type="text/css" href="style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="js/maskedinput/jquery.maskedinput.js"></script>

	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblCargo').style.display = 'none';
		
		if (verTabla == "tblCargo") {
			document.forms['frmCargo'].reset();
			byId('hddIdCargo').value = '';
			
			byId('txtCodigo').className = "inputHabilitado";
			byId('txtCargo').className = "inputHabilitado";
			
			xajax_formCargo(valor, xajax.getFormValues('frmCargo'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Cargo';
			} else {
				tituloDiv1 = 'Agregar Cargo';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblCargo") {
			byId('txtCodigo').focus();
			byId('txtCodigo').select();
		}
	}
	
	function validarFrmCargo(){
		if (validarCampo('txtCargo','t','') == true
		&& validarCampo('txtCodigo','t','') == true
		&& validarCampo('lstUnipersonal','t','listaExceptCero') == true) {
			byId('btnGuardarCargo').disabled = true;
			byId('btnCancelarCargo').disabled = true;
			xajax_guardarCargo(xajax.getFormValues('frmCargo'), xajax.getFormValues('frmListaCargo'));
		} else {
			validarCampo('txtCargo','t','')
			validarCampo('txtCodigo','t','');
			validarCampo('lstUnipersonal','t','listaExceptCero')

			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idCargo){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarCargo(idCargo, xajax.getFormValues('frmListaCargo'));
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
            <td class="tituloPaginaErp">Cargos</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblCargo');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" size="20"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarCargo(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaCargo" name="frmListaCargo" style="margin:0">
            	<div id="divListaCargo" style="width:100%"></div>
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

<form id="frmCargo" name="frmCargo" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblCargo" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>C&oacute;digo:</td>
                <td width="70%"><input type="text" id="txtCodigo" name="txtCodigo" size="30"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cargo:</td>
                <td><input type="text" id="txtCargo" name="txtCargo" size="30"/></td>
            </tr>
            <tr align="left">                                  
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unipersonal:</td>
                <td>
                    <select id="lstUnipersonal" name="lstUnipersonal">
                        <option value="-1">[ Seleccione ] </option>
                        <option value="1">Si</option>
                        <option value="0">No</option>
                    </select>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdCargo" name="hddIdCargo"/>
            <button type="button" id="btnGuardarCargo" name="btnGuardarCargo" onclick="validarFrmCargo();">Guardar</button>
            <button type="button" id="btnCancelarCargo" name="btnCancelarCargo" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script language="javascript">
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

xajax_listaCargo(0,'id_cargo','ASC','');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>