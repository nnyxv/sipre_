<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_imprimir_etiquetas"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_imprimir_etiquetas.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Imprimir Etiquetas</title>
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
		<tr class="solo_print">
			<td align="left" id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td class="tituloPaginaRepuestos">Imprimir Etiquetas</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr class="noprint">
			<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo">Empresa:</td>
					<td id="tdlstEmpresa" colspan="3"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ver:</td>
                	<td colspan="3">
                        <select multiple id="lstSaldos" name="lstSaldos" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">Disponible</option>
                            <option value="2">No Disponible</option>
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
                                <select id="lstEstanteAct" name="lstEstanteBusqueda">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="tdlstTramoBusqueda">
                                <select id="lstTramoAct" name="lstTramoBusqueda">
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
					<td id="tdlstAno"><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="$('btnBuscar').click();"/></td>
					<td>
						<button type="submit" id="btnBuscar" onclick="xajax_buscarUbicacion(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); $('btnBuscar').click();">Limpiar</button>
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
			<td><hr>
				<table cellpadding="0" cellspacing="0" width="100%" border="0">
				<tr>
					<td align="left" width="50%">
                    	<button type="button" id="btnAgregarArticulo" name="btnAgregarArticulo" onclick="xajax_insertarArticulo(xajax.getFormValues('frmListaUbicacion'), xajax.getFormValues('frmArticulos'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
						<button type="button" id="btnQuitarArticulo" name="btnQuitarArticulo" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmArticulos'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
						<button type="button" id="btnEtiqueta" name="btnEtiqueta" onclick="xajax_imprimirEtiqueta(xajax.getFormValues('frmArticulos'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/tag_blue.png"/></td><td>&nbsp;</td><td>Etiqueta</td></tr></table></button>
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
			<form id="frmArticulos" name="frmArticulos" style="margin:0">
				<table border="0" width="100%">
				<tr align="center" class="tituloColumna">
					<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmArticulos');"/></td>
                	<td width="4%">Nro.</td>
					<td width="8%">Nro. Copias</td>
                    <td width="14%">Ubicación</td>
                    <td width="12%">Código</td>
					<td width="50%">Descripción</td>
					<td width="12%">Tasa Cambio</td>
				</tr>
				<tr id="trItmPie"></tr>
				</table>
			</form>
			</td>
		</tr>
        </table>
	</div>
	
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
byId('lstSaldos').className = "inputHabilitado";

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', "onchange=\"xajax_objetoCodigoDinamico('tdCodigoArt',this.value); xajax_cargaLstBusqueda('almacenes', 'lstPadre', 'Busqueda', this.value, 'null', 'null'); byId('btnBuscar').click();\"");
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstBusqueda('almacenes', 'lstPadre', 'Busqueda', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'null', 'null');

xajax_listaUbicacion(0, 'CONCAT(descripcion_almacen, ubicacion, id_articulo_costo)', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>