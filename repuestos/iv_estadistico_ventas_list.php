<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_estadistico_ventas_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_estadistico_ventas_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Estadístico de Ventas</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
	<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function validarBuscar() {
		if (validarCampo('lstAno','t','lista') == true
		&& validarCampo('lstSaldos','t','lista') == true) {
			xajax_buscarEstadisticoVenta(xajax.getFormValues('frmBuscar'));
		} else {
			validarCampo('lstAno','t','lista');
			validarCampo('lstSaldos','t','lista');
			
			alert('Los campos señalados en rojo son requeridos');
			return false;
		}
	}
	
	function validarExportar() {
		if (validarCampo('lstAno','t','lista') == true
		&& validarCampo('lstSaldos','t','lista') == true) {
			xajax_exportarEstadisticoVentas(xajax.getFormValues('frmBuscar'));
		} else {
			validarCampo('lstAno','t','lista');
			validarCampo('lstSaldos','t','lista');
			
			alert('Los campos señalados en rojo son requeridos');
			return false;
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
        	<td class="tituloPaginaRepuestos">Estadístico de Ventas</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="validarExportar()"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
            	<table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Año:</td>
                    <td id="tdlstAno"></td>
                    <td></td>
                    <td></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ver:</td>
                    <td>
                    	<select multiple id="lstSaldos" name="lstSaldos" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option selected="selected" value="1">Año Seleccionado</option>
                            <option selected="selected" value="2">Año Anterior al Seleccionado</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Ver Clasificación:</td>
                    <td>
                        <select multiple id="lstVerClasificacion" name="lstVerClasificacion" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="F">F</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
	                    <button type="submit" id="btnBuscar" onclick="validarBuscar();">Buscar</button>
	                    <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
			</form>
            </td>
        </tr>
        <tr>
        	<td id="tdListadoEstadisticoVentas">
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">Ingrese los datos del Estadístico de Ventas a Buscar</td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
byId('lstVerClasificacion').className = "inputHabilitado";
byId('lstSaldos').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstAno('<?php echo date("Y")?>');
</script>