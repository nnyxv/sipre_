<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_facturas_por_pagar_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
$xajax = new xajax();
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_facturas_por_pagar_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Facturas por Cobrar</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
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
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCajaRS">Facturas por Cobrar</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr style="vertical-align:top">
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr><td><button type="button" onclick="xajax_cargaLstOrientacionPDF();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button></td></tr>
                        <tr><td id="tdlstOrientacionPDF"></td></tr>
                        </table>
                    </td>
                </tr>
                </table>
                
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo">Empresa:</td>
					<td id="tdlstEmpresa"></td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo">Fecha:</td>
					<td>
						<table cellpadding="0" cellspacing="0">
						<tr>
							<td>&nbsp;Desde:&nbsp;</td>
							<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
							<td>&nbsp;Hasta:&nbsp;</td>
							<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
						</tr>
						</table>
					</td>
                	<td align="right" class="tituloCampo">Filtrar por Fecha:</td>
                    <td id="tdlstTipoFecha"></td>
                    <td align="right" class="tituloCampo">Vendedor:</td>
                    <td id="tdlstEmpleado"></td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo">Aplica Libro:</td>
					<td>
						<select id="lstAplicaLibro" name="lstAplicaLibro" onchange="byId('btnBuscar').click();">
							<option value="-1">[ Seleccione ]</option>
							<option value="0">No</option>
							<option value="1">Si</option>
						</select>
					</td>
					<td align="right" class="tituloCampo">Tipo Pago:</td>
                    <td>
                    	<select id="lstTipoPago" name="lstTipoPago" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="0">Crédito</option>
                        	<option value="1">Contado</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo">Ver:</td>
                    <td id="tdlstAnuladaFactura"></td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Estado Factura:</td>
					<td id="tdlstEstadoFactura"></td>
					<td align="right" class="tituloCampo" width="120">Módulo:</td>
					<td id="tdlstModulo"></td>
					<td align="right" class="tituloCampo" width="120">Tipo de Orden:</td>
					<td id="tdlstTipoOrden"></td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo">Item Facturado:</td>
                    <td id="tdlstItemFactura"></td>
                	<td align="right" class="tituloCampo">Item Pago:</td>
                	<td id="tdlstItemPago"></td>
                    <td align="right" class="tituloCampo">Condición:</td>
                    <td id="tdlstCondicionBuscar"></td>
				</tr>
				<tr align="left">
                	<td></td>
                	<td></td>
                	<td></td>
                	<td></td>
					<td align="right" class="tituloCampo">Criterio:</td>
					<td><input type="text" id="txtCriterio" name="txtCriterio"/></td>	
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarFactura(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
        <tr>
            <td>
            <form id="frmListaFactura" name="frmListaFactura" style="margin:0">
                <div id="divListaFactura" style="width:100%">
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                    </tr>
                    </table>
                </div>
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
							<td><img src="../img/iconos/money_add.png"/></td><td>Pagar Factura</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_view.png"/></td><td>Ver</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Factura Venta PDF</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/print.png"/></td><td>Recibo(s) de Pago(s)</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td align="right"><hr>
            	<table>
                <tr align="right">
                	<td></td>
                	<td></td>
                	<td class="tituloCampo">Total Neto:</td>
                    <td><span id="spnTotalNeto"></span></td>
				</tr>
                <tr align="right">
                	<td></td>
                	<td></td>
                    <td class="tituloCampo">Total Impuesto:</td>
                    <td><span id="spnTotalIva"></span></td>
				</tr>
                <tr align="right" class="trResaltarTotal">
                    <td class="tituloCampo" width="120">Saldo Factura(s):</td>
                    <td width="150"><span id="spnSaldoFacturas"></span></td>
                    <td class="tituloCampo" width="120">Total Factura(s):</td>
                    <td width="150"><span id="spnTotalFacturas"></span></td>
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
byId('txtFechaDesde').className = 'inputHabilitado';
byId('txtFechaHasta').className = 'inputHabilitado';
byId('lstAplicaLibro').className = 'inputHabilitado';
byId('lstTipoPago').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"brown"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"brown"
	});
};

//var lstEmpleado = $.map($("#lstEmpleado option:selected"), function (el, i) { return el.value; });
var lstEstadoFactura = $.map($("#lstEstadoFactura option:selected"), function (el, i) { return el.value; });
//var lstModulo = $.map($("#lstModulo option:selected"), function (el, i) { return el.value; });

xajax_cargaLstTipoFecha();
xajax_cargaLstVendedor();
xajax_cargaLstAnuladaFactura();
xajax_cargaLstEstadoFactura();
xajax_cargaLstModulo();
xajax_cargaLstTipoOrden('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstItemPago();
xajax_cargaLstItemFactura();
xajax_cargaLstCondicionBuscar();
xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
</script>