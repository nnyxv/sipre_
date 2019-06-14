<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_paquete_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_an_paquete_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Paquetes</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function validarForm() {
		if (validarCampo('txtNombre','t','') == true
		&& validarCampo('txtDescripcion','t','') == true) {
			xajax_guardarPaquete(xajax.getFormValues('frmPaquete'), xajax.getFormValues('frmListaPaquete'));
		} else {
			validarCampo('txtNombre','t','');
			validarCampo('txtDescripcion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormAccesorio() {
		if (validarCampo('txtNombrePaqueteAccesorio','t','') == true
		&& validarCampo('lstAccesorio','t','lista') == true) {
			xajax_guardarAccesorio(xajax.getFormValues('frmAccesorioPaquete'), xajax.getFormValues('frmListaAccesorio'));
		} else {
			validarCampo('txtNombrePaqueteAccesorio','t','');
			validarCampo('lstAccesorio','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idPaquete){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarPaquete(idPaquete, xajax.getFormValues('frmListaPaquete'));
		}
	}
	
	function validarEliminarAccesorio(idAccesorio){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAccesorio(idAccesorio, xajax.getFormValues('frmListaAccesorio'));
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
        	<td class="tituloPaginaVehiculos">Paquetes</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_formPaquete();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                </tr>
                </table>
            
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPaquete(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPaquete" name="frmListaPaquete" style="margin:0">
                <div id="divListaPaquete" style="width:100%"></div>
            </form>
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

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmPaquete" name="frmPaquete" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblPaquete" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td width="70%"><input type="text" id="txtNombre" name="txtNombre" size="20"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td><input type="text" id="txtDescripcion" name="txtDescripcion" size="50"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdPaquete" name="hddIdPaquete" readonly="readonly"/>
            <button type="submit" onclick="validarForm();">Guardar</button>
            <button type="button" onclick="byId('divFlotante1').style.display='none';">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
	
	<table border="0" id="tblListaAccesorio" width="960">
    <tr>
    	<td>
        <form id="frmAccesorio" name="frmAccesorio" style="margin:0">
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="15%">Paquete:</td>
                <td width="85%">
            		<input type="hidden" id="hddIdPaqueteListaAccesorio" name="hddIdPaqueteListaAccesorio" readonly="readonly"/>
                    <input type="text" id="txtNombrePaqueteListaAccesorio" name="txtNombrePaqueteListaAccesorio" readonly="readonly" size="40"/>
				</td>
            </tr>
			</table>
		</form>
		</td>
    </tr>
    <tr align="left">
    	<td>
        	<button type="button" id="btnInsertarArt" name="btnInsertarArt" onclick="xajax_formAccesorio(xajax.getFormValues('frmAccesorio'))" style="cursor:default" title="Agregar Articulo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_agregar.gif"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="frmListaAccesorio" name="frmListaAccesorio" style="margin:0">
			<div id="divListaAccesorio" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" onclick="byId('divFlotante2').style.display='none'; byId('divFlotante1').style.display='none';">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
<form id="frmAccesorioPaquete" name="frmAccesorioPaquete" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblAccesorioPaquete" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Paquete:</td>
                <td width="75%">
                	<input type="hidden" id="hddIdPaqueteAccesorio" name="hddIdPaqueteAccesorio" readonly="readonly"/>
                    <input type="text" id="txtNombrePaqueteAccesorio" name="txtNombrePaqueteAccesorio" readonly="readonly" size="40"/>
				</td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Accesorio:</td>
                <td id="tdlstAccesorio">
                    <select id="lstAccesorio" name="lstAccesorio">
                        <option>[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
			</table>
		</td>
	</tr>
    <tr>
        <td align="right"><hr>
            <input type="hidden" id="hddIdAccesorio" name="hddIdAccesorio" readonly="readonly"/>
            <button type="submit" onclick="validarFormAccesorio();">Guardar</button>
            <button type="button" onclick="byId('divFlotante2').style.display='none';">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
xajax_listaPaquete();

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>