<?php
require_once("connections/conex.php");

session_start();
/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_centro_costo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');

//Instanciando el objeto xajax
$xajax = new xajax();

//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_pg_centro_costo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Centros de Costo</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/');//indicamos al objeto xajax se encargue de generar javascript necesario ?>

    <link rel="stylesheet" type="text/css" href="style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">

    <script type="text/javascript" language="javascript" src="js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    <script type="text/javascript" src="vehiculos/vehiculos.inc.js" ></script>

	<script>
	function validarCentroCosto() {
		if (validarCampo('lstEmpresa','t','listaExceptCero') == true
		&& validarCampo('lstDepartamento','t','listaExceptCero') == true
		&& validarCampo('txtCodigo','t','') == true
		&& validarCampo('txtNombre','t','') == true) {
			xajax_guardarCentroCosto(xajax.getFormValues('frmCentroCosto'));
		} else {
			validarCampo('lstEmpresa','t','listaExceptCero')
			validarCampo('lstDepartamento','t','listaExceptCero')
			validarCampo('txtCodigo','t','')
			validarCampo('txtNombre','t','')
	
			alert("Los campos señalados en rojo son requeridos.");
			return false;
		}
	}
	
	function limpiarForm() {
		$('lstEmpresa').className      = "inputInicial";
		$('lstDepartamento').className = "inputInicial";
		$('txtCodigo').className       = "inputInicial";
		$('txtNombre').className       = "inputInicial";
	}
    </script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table border="0" width="80%" align="center">
        <tr>
            <td class="tituloPaginaErp">Centros de Costo</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <button type="button" onclick="limpiarForm(); xajax_levantarDivFlotante();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                </tr>
                </table>
            
            <form id="frmBuscarCentroCosto" name="frmBuscarCentroCosto" style="margin:0" onsubmit="return false;">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscar').click();" size="20"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_listarCentroCosto(0,'id_unidad_centro_costo','',$('txtDescripcionBusq').value);">Buscar</button>
                        <button type="button" class="noprint" onclick="document.forms['frmBuscarCentroCosto'].reset(); $('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td id="tdListarCentroCosto"></td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>

<form id="frmCentroCosto" name="frmCentroCosto" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblAno" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td id="tdSelEmpresa" width="70%">
                    <select id="lstEmpresa" name="lstEmpresa">
                        <option value="-1">[ Seleccione ] </option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Departamento:</td>
                <td id="tdSelDepartamento">
                    <select id="lstDepartamento" name="lstDepartamento">
                        <option value="-1">[ Seleccione ] </option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Codigo:</td>
                <td><input type="text" id="txtCodigo" name="txtCodigo" size="30"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td><input type="text" id="txtNombre" name="txtNombre" size="30"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdCentroCosto" name="hddIdCentroCosto"/>
            <button type="submit" id="btnGuardar" name="btnGuardar" onclick="validarCentroCosto();">Guardar</button>
            <button type="button" onclick="$('divFlotante').style.display='none'; limpiarForm();">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
xajax_listarCentroCosto(0,'id_unidad_centro_costo','','');

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>