<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_ubicacion_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_ubicacion_historico_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Histórico de Relación Artículo con Ubicación</title>
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
        	<td class="tituloPaginaRepuestos">Histórico de Relación Artículo con Ubicación</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_imprimirUbicacion(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
                        <button type="button" onclick="xajax_exportarUbicacion(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                	<td></td>
                    <td></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Artículos:</td>
                	<td colspan="3">
                    	<label><input type="checkbox" id="cbxVerArtUnaUbic" name="cbxVerArtUnaUbic" checked="checked" value="1"/> Con Una Ubicación</label>
                        <label><input type="checkbox" id="cbxVerArtMultUbic" name="cbxVerArtMultUbic" checked="checked" value="2"/> Con Múltiple Ubicación</label>
					</td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ubicaciones:</td>
                	<td colspan="3">
                    	<label><input type="checkbox" id="cbxVerUbicLibre" name="cbxVerUbicLibre" checked="checked" value="1"/> Libres</label>
                        <label><input type="checkbox" id="cbxVerUbicOcup" name="cbxVerUbicOcup" checked="checked" value="2"/> Ocupadas</label>
						<label><input type="checkbox" id="cbxVerUbicDisponible" name="cbxVerUbicDisponible" checked="checked" value="3"/> Con Disponibilidad</label>
                        <label><input type="checkbox" id="cbxVerUbicSinDisponible" name="cbxVerUbicSinDisponible" checked="checked" value="4"/> Sin Disponibilidad</label>
					</td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Estatus:</td>
                    <td>
                        <select id="lstEstatus" name="lstEstatus" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Ubicación Inactiva</option>
                            <option selected="selected" value="1">Ubicación Activa</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ubicación:</td>
                	<td colspan="3">
                        <table>
                        <tr align="center">
                            <td class="tituloCampo">Almacen</td>
                            <td class="tituloCampo">Calle</td>
                            <td class="tituloCampo">Estante</td>
                            <td class="tituloCampo">Tramo</td>
                            <td class="tituloCampo">Casilla</td>
                        </tr>
                        <tr>
                            <td id="tdlstAlmacenBusqueda">
                                <select id="lstAlmacenBusqueda" name="lstAlmacenBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstCalleBusqueda">
                                <select id="lstCalleBusqueda" name="lstCalleBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstEstanteBusqueda">
                                <select id="lstEstanteBusqueda" name="lstEstanteBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstTramoBusqueda">
                                <select id="lstTramoBusqueda" name="lstTramoBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstCasillaBusqueda">
                                <select id="lstCasillaBusqueda" name="lstCasillaBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Código:</td>
                    <td id="tdCodigoArt"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarUbicacion(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaUbicacion" name="frmListaUbicacion" style="margin:0">
                <div id="divListaUbicacion" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif" /></td>
                            <td>Ubicación Activa</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_gris.gif" /></td>
                            <td>Ubicación Inactiva</td>
                        </tr>
                        </table>
                        
                        <table>
                        <tr>
                            <td class="divMsjInfo">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>Ubicación Disponible</td>
                            <td>&nbsp;</td>
                            <td class="divMsjError">&nbsp;&nbsp;&nbsp;*</td>
                            <td>Ubicación Ocupada</td>
                            <td>&nbsp;</td>
                            <td class="divMsjInfo3">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>Ubicación Inactiva</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	Unid. Disponible = Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
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
byId('lstEstatus').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>',"onchange=\"xajax_objetoCodigoDinamico('tdCodigoArt',this.value); xajax_cargaLstBusqueda('almacenes', 'lstPadre', 'Busqueda', this.value, 'null', 'null'); byId('btnBuscar').click();\"");
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstBusqueda('almacenes', 'lstPadre', 'Busqueda', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'null', 'null');
xajax_listaUbicacion(0, 'CONCAT(descripcion_almacen, ubicacion)', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('cbxVerArtUnaUbic').value + '|' + byId('cbxVerArtMultUbic').value + '|' + byId('cbxVerUbicLibre').value + '|' + byId('cbxVerUbicOcup').value + '|' + byId('cbxVerUbicDisponible').value + '|' + byId('cbxVerUbicSinDisponible').value + '|' + byId('lstEstatus').value);

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>