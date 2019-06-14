<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_orden_compra_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_orden_compra_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Ordenes de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../js/domDragCompras.css">
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_compras.php"); ?></div>

    <div id="divInfo" class="print">
    <table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
    <tr>
		<td class="tituloPaginaCompras">Ordenes de Compra</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr class="noprint">
        <td>
            <table align="left" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td>
					<button type="button" onclick="xajax_encabezadoEmpresa(byId('lstEmpresa').value); window.print();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                </td>
            </tr>
            </table>
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table border="0" align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="2"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha Solicitud:</td>
                    <td colspan="2">&nbsp;Desde:&nbsp;<input id="txtFechaDesde"  name="txtFechaDesde" class="inputHabilitado" type="text" style="text-align:center" size="10" autocomplete="off">&nbsp;Hasta:&nbsp;<input id="txtFechaHasta" name="txtFechaHasta" class="inputHabilitado" type="text" style="text-align:center" size="10" autocomplete="off"></td>
                </tr> 
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Tipo De Compra:</td>
                    <td>
                        <select id="lstTipoCompra" name="lstTipoCompra" onchange="byId('btnBuscar').click();" class="inputHabilitado">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="2">Cargos (Activos Fijo)</option>
                            <option value="3">Servicios</option>
                            <option value="4">Gastos / Activos</option>
                        </select>
                    </td>
                    <td id="tdLisEstado" colspan=""></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Nro. Solicitud:</td>
                    <td><input type="text" id="txtNroSolicitud" name="txtNroSolicitud" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"></td>
                     <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td>
            <div id="divListaPedidoVenta" style="width:100%">
                <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                </tr>
                </table>
            </div>
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
                        <td><img src="../img/iconos/ico_aceptar_naranja.png"/></td>
                        <td>Procesado</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_importar.gif"/></td>
                        <td>Aprobar</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/page_white_acrobat.png"/></td>
                        <td>Solicitud de Compra Pdf </td>
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaPedidoCompra(0,'','','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|<?php echo date("01-m-Y"); ?>|<?php echo date(spanDateFormat); ?>');

byId('txtFechaDesde').value = "<?php echo date(spanDateFormat,strtotime(date("01-m-Y"))); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen"
});

</script>