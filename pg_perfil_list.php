<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_perfil_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_pg_perfil_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Perfiles de Usuario</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/');//indicamos al objeto xajax se encargue de generar javascript necesario ?>

    <link rel="stylesheet" type="text/css" href="style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">

    <script type="text/javascript" language="javascript" src="js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>

	<script>
	function validarCargo(){
		if (validarCampo('txtPerfil','t','') == true) {
			xajax_guardarPerfil(xajax.getFormValues('frmPerfil'));
		} else {
			validarCampo('txtPerfil','t','')

			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}

	function limpiarForm(){
		$('txtPerfil').className = "inputInicial";
	}
    </script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
            <td class="tituloPaginaErp">Perfiles de Usuario</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left">
                <tr>
                    <td>
                        <button type="button" name="btnNuevo" value="Nuevo" onclick=" limpiarForm(); xajax_levantarDivFlotante();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                </tr>
                </table>
            
            <form id="frmBuscarPerfil" name="frmBuscarPerfil">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('btnBuscar').click();" size="20"/></td>
                    <td>
                        <button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_listarPerfil(0,'','',$('txtCriterio').value);">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarPerfil'].reset(); $('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td id="tdlistarPerfil"></td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>

<form id="frmPerfil" name="frmPerfil" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblPerfil" width="360">
    <tr>
    	<td>
        	<table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="60%"><span class="textoRojoNegrita">*</span>Nombre del Perfil:</td>
                    <td><input type="text" id="txtPerfil" name="txtPerfil" size="30"/></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdPerfil" name="hddIdPerfil"/>
            <button type="submit" id="btnGuardar" name="btnGuardar" onclick="validarCargo();">Guardar</button>
            <button type="button" onclick="$('divFlotante').style.display='none'; limpiarForm();">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
xajax_listarPerfil(0,'id_perfil','','');

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>