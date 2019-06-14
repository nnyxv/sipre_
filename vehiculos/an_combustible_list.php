<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_combustible_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_an_combustible_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Combustible</title>
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
		&& validarCampo('txtDescripcion','t','') == true
		) {
			xajax_guardarCombustible(xajax.getFormValues('frmCombustible'), xajax.getFormValues('frmListaCombustible'));
		} else {
			validarCampo('txtNombre','t','');
			validarCampo('txtDescripcion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idCombustible){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarCombustible(idCombustible, xajax.getFormValues('frmListaCombustible'));
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralVehiculos">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Combustible</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_formCombustible();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="100">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('btnBuscar').click();"/></td>
                    <td>
                        <input type="submit" id="btnBuscar" onclick="xajax_buscarCombustible(xajax.getFormValues('frmBuscar'));" value="Buscar"/>
						<input type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();" value="Limpiar"/>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaCombustible" name="frmListaCombustible" style="margin:0">
                <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td id="tdListaCombustible"></td>
                </tr>
                </table>
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




<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmCombustible" name="frmCombustible" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblCombustible" width="450px">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td width="75%"><input type="text" id="txtNombre" name="txtNombre" size="20"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td><input type="text" id="txtDescripcion" name="txtDescripcion" size="50"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="hidden" id="hddIdCombustible" name="hddIdCombustible"/>
            <input type="submit" onclick="validarForm();" value="Guardar">
            <input type="button" onclick="$('divFlotante').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
</form>
</div>
<script>
xajax_listadoCombustible();
</script>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot   = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
</script>