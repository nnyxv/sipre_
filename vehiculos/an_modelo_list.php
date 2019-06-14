<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_modelo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_an_modelo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Modelo</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblModelo').style.display = 'none';
		
		if (verTabla == "tblModelo") {
			document.forms['frmModelo'].reset();
			byId('hddIdModelo').value = '';
			
			byId('txtNombre').className = 'inputHabilitado';
			byId('txtDescripcion').className = 'inputHabilitado';
			
			xajax_formModelo(valor, xajax.getFormValues('frmModelo'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Modelo';
			} else {
				tituloDiv1 = 'Agregar Modelo';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblModelo") {
			byId('txtDescripcion').focus();
			byId('txtDescripcion').select();
		}
	}
	
	function validarFrmModelo() {
		if (validarCampo('lstMarca','t','lista') == true
		&& validarCampo('txtNombre','t','') == true
		&& validarCampo('txtDescripcion','t','') == true) {
			byId('btnGuardarModelo').disabled = true;
			byId('btnCancelarModelo').disabled = true;
			xajax_guardarModelo(xajax.getFormValues('frmModelo'), xajax.getFormValues('frmListaModelo'));
		} else {
			validarCampo('lstMarca','t','lista');
			validarCampo('txtNombre','t','');
			validarCampo('txtDescripcion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idModelo){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarModelo(idModelo, xajax.getFormValues('frmListaModelo'));
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Modelo</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblModelo');">
                    	<button type="button" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Marca:</td>
                    <td id="tdlstMarcaBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModeloBuscar"></td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarModelo(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaModelo" name="frmListaModelo" style="margin:0">
            	<div id="divListaModelo" style="width:100%"></div>
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
    
<form id="frmModelo" name="frmModelo" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblModelo" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Marca:</td>
                <td id="tdlstMarca" width="70%"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Modelo:</td>
                <td><input type="text" id="txtNombre" name="txtNombre" size="26"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td><input type="text" id="txtDescripcion" name="txtDescripcion" size="26"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdModelo" name="hddIdModelo"/>
            <button type="submit" id="btnGuardarModelo" name="btnGuardarModelo" onclick="validarFrmModelo();">Guardar</button>
            <button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
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

xajax_cargaLstMarcaModeloVersion("lstPadre", "Buscar");
xajax_listaModelo(0, 'id_modelo', 'ASC');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>