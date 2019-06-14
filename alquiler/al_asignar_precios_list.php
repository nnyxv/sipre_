<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("al_asignar_precios_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_al_asignar_precios_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Alquiler - Asignar Precios</title>
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
		byId('tblPrecio').style.display = 'none';
		$('.itemTiempo').remove();
		
		if (verTabla == "tblPrecio") {
			document.forms['frmPrecio'].reset();
			byId('hddIdClase').value = '';
			byId('hddIdPrecioEliminar').value = '';
			
			xajax_formPrecio(valor);
			
			if (valor > 0) {				
				tituloDiv1 = 'Editar Precio';
			} else {				
				tituloDiv1 = 'Agregar Precio';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;		
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaPrecio').style.display = 'none';
		
		if (verTabla == "tblListaPrecio") {
			document.forms['frmBuscarPrecio'].reset();
			
			byId('btnBuscarPrecio').click();			
			tituloDiv2 = 'Precios';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaPrecio") {
			byId('txtCriterioBuscarPrecio').focus();
			byId('txtCriterioBuscarPrecio').select();
		}
	}
	
	function eliminarTiempo(obj, idPrecioEliminar){
		$(obj).parent().parent().remove();
		
		if(idPrecioEliminar != ""){		
			hddIdPrecioEliminar = $("#hddIdPrecioEliminar").val();
			if(hddIdPrecioEliminar == ""){
				$("#hddIdPrecioEliminar").val(idPrecioEliminar);
			}else{
				$("#hddIdPrecioEliminar").val(hddIdPrecioEliminar+","+idPrecioEliminar);
			}
		}
	}
	
	function validarFrmPrecio() {
		error = false;
								
		if (!(validarCampo('txtNombre','t','') == true
		&& validarCampo('txtDescripcion','t','') == true)) {
			validarCampo('txtNombre','t','');
			validarCampo('txtDescripcion','t','');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarPrecio(xajax.getFormValues('frmPrecio'));
		}
	}
	
	function validarEliminar(idClase){
		if (confirm('¿Seguro desea eliminar todos los precios de la clase?') == true) {
			xajax_eliminarPrecio(idClase);
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
			<td class="tituloPaginaAlquiler">Asignar Precios</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
                
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">					
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarClase(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>            
                <div id="divListaClase" style="width:100%"></div>
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
    
<form id="frmPrecio" name="frmPrecio" style="margin:0" onsubmit="return false;">
	<table border="0" id="tblPrecio" width="680">
    <tr>
        <td>
            <table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nombre Clase:</td>
                <td><input type="text" id="txtNombre" name="txtNombre" readonly="readonly" onkeypress="return validarSoloTextoNumero(event);" size="20"/></td>
                <td width="120">&nbsp;</td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripci&oacute;n Clase:</td>
                <td colspan="2"><input type="text" id="txtDescripcion" name="txtDescripcion" readonly="readonly" onkeypress="return validarSoloTextoNumero(event);" style="width:99%"/></td>
            </tr>          
            </table>
        </td>
    </tr>
    <tr>
    	<td colspan="15">
            <fieldset>
                <legend class="legend">Precios</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaPrecio', 'Precio', 'ListaPrecio');">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>                        
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" width="100%" class="tablaResaltarPar">
                        <thead>
                            <tr align="center" class="tituloColumna">  
                                <td width="40%">C&oacute;digo Precio</td>                          
                                <td width="60%">Descripci&oacute;n Precio</td>
                                <td></td>
                            </tr>
                        </thead>
                        <tr id="trItmPie"></tr>
                        </table>
                    </td>
                </tr>
                </table>
        </fieldset>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <input type="hidden" id="hddIdClase" name="hddIdClase"/>
            <input type="hidden" id="hddIdPrecioEliminar" name="hddIdPrecioEliminar"/>
            <button type="submit" id="btnGuardarPrecio" name="btnGuardarPrecio" onclick="validarFrmPrecio();">Guardar</button>
            <button type="button" id="btnCancelarPrecio" name="btnCancelarPrecio" class="close">Cancelar</button>
        </td>
    </tr>
    </table>

</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaPrecio" width="760">
    <tr>
    	<td>
        <form id="frmBuscarPrecio" name="frmBuscarPrecio" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarPrecio" name="txtCriterioBuscarPrecio" class="inputHabilitado" onkeyup="byId('btnBuscarPrecio').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarPrecio" name="btnBuscarPrecio" onclick="xajax_buscarPrecio(xajax.getFormValues('frmBuscarPrecio'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarPrecio'].reset(); byId('btnBuscarPrecio').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        	<div id="divListaPrecio" style="width:100%"></div>
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

xajax_listaClase(0, 'id_clase', 'ASC');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>