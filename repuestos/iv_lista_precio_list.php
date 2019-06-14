<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_lista_precio_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_lista_precio_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Listado de Precios</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
            <td class="tituloPaginaRepuestos">Listado de Precios</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<button type="button" onclick="xajax_imprimirPrecioArt(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                    	<button type="button" onclick="xajax_exportarPreciosArt(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
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
                    <td align="right" class="tituloCampo">Aplica Impuesto:</td>
                    <td>
                    	<select id="lstAplicaIva" name="lstAplicaIva" onchange="byId('btnBuscar').click();" style="width:99%">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="0">No</option>
                        	<option value="1">Si</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Mostrar Columna:</td>
                    <td>
                    	<select multiple id="lstMostrarColumna" name="lstMostrarColumna" onchange="byId('btnBuscar').click();" style="width:99%">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option selected="selected" value="1">Código</option>
                        	<option selected="selected" value="2">Disponible</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo de Artículo:</td>
                    <td id="tdlstTipoArticulo"></td>
                    <td align="right" class="tituloCampo">Clasificación:</td>
                    <td>
                        <select multiple id="lstVerClasificacion" name="lstVerClasificacion" onchange="byId('btnBuscar').click();" style="width:99%">
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
                	<td align="right" class="tituloCampo">Ver:</td>
                    <td>
                        <select multiple id="lstSaldos" name="lstSaldos" onchange="byId('btnBuscar').click();" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">Disponible</option>
                            <option value="2">No Disponible</option>
                        </select>
					</td>
                	<td align="right" class="tituloCampo">Precio:</td>
                    <td id="tdlstPrecio">
                        <select multiple id="lstPrecio" name="lstPrecio" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarPrecioArt(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaPreciosArt" name="frmListaPreciosArt">
            	<div id="divListaPreciosArt" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                        <tr>
							<td><img src="../img/iconos/accept.png" /></td><td>Si Aplica Impuesto</td>
                        </tr>
                        </table>
                        
                        <table>
                        <tr>
                            <td class="divMsjInfo4">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>Unid. No Disponible para la Venta</td>
                        </tr>
                        </table>
                        
                        <table>
                        <tr>
							<td>Unid. Disponible = Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada</td>
                        </tr>
                        </table>
                    </td>
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
byId('lstAplicaIva').className = 'inputHabilitado';
byId('lstMostrarColumna').className = 'inputHabilitado';
byId('lstVerClasificacion').className = 'inputHabilitado';
byId('lstSaldos').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

var lstVerClasificacion = $.map($("#lstVerClasificacion option:selected"), function (el, i) { return el.value; });
var lstSaldos = $.map($("#lstSaldos option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstTipoArticulo();
xajax_cargaLstPrecios();
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

xajax_listaPrecioArticulo(0,'art.codigo_articulo','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||' + $('#lstMostrarColumna').val() + '||' + lstVerClasificacion.join() + '|' + lstSaldos.join());
</script>                  