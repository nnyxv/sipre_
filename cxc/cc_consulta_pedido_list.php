<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_consulta_pedido_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
$xajax = new xajax();
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_consulta_pedido_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Histórico de Pedidos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
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
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCuentasPorCobrar" colspan="2">Histórico de Pedidos</td>
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
                        <button type="button" onclick="xajax_exportarPedido(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
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
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo">Tipo Pago:</td>
                    <td>
                    	<select id="lstTipoPago" name="lstTipoPago" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="0">Crédito</option>
                        	<option value="1">Contado</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Vendedor:</td>
                    <td id="tdlstEmpleado"></td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Estado Pedido:</td>
					<td id="tdlstEstadoPedido"></td>
					<td align="right" class="tituloCampo" width="120">Módulo:</td>
					<td id="tdlstModulo"></td>
				</tr>
				<tr align="left">
                	<td></td>
                	<td></td>
					<td align="right" class="tituloCampo">Criterio:</td>
					<td><input type="text" id="txtCriterio" name="txtCriterio"/></td>	
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPedido(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
        <tr>
            <td>
            <form id="frmListaPedido" name="frmListaPedido" style="margin:0">
                <div id="divListaPedido" style="width:100%">
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
                        	<td><img src="../img/iconos/ico_verde.gif"/></td><td>Pedido Pendiente</td>
                          	<td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Pedido Autorizado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"/></td><td>Pedido Desautorizado</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_marron.gif"/></td><td>Pedido Anulado</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_gris.gif"/></td><td>Nota de Crédito</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_morado.gif"/></td><td>Facturado</td>  
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
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_view.png"/></td><td>Ver Pedido</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Pedido Venta PDF</td>
                            <td>&nbsp;</td>
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
                	<td class="tituloCampo">Total Neto:</td>
                    <td><span id="spnTotalNeto"></span></td>
				</tr>
                <tr align="right">
                    <td class="tituloCampo">Total Impuesto:</td>
                    <td><span id="spnTotalImpuesto"></span></td>
				</tr>
                <tr align="right" class="trResaltarTotal">
                    <td class="tituloCampo" width="120">Total Pedido(s):</td>
                    <td width="150"><span id="spnTotalPedidos"></span></td>
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
byId('lstTipoPago').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	
new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"purple"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"purple"
});

function openImg(idObj) {
	var oldMaskZ = null;
	var $oldMask = $(null);
	
	$(".modalImg").each(function() {
		$(idObj).overlay({
			//effect: 'apple',
			oneInstance: false,
			zIndex: 10100,
			
			onLoad: function() {
				if ($.mask.isLoaded()) {
					oldMaskZ = $.mask.getConf().zIndex; // this is a second overlay, get old settings
					$oldMask = $.mask.getExposed();
					$.mask.getConf().closeSpeed = 0;
					$.mask.close();
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0,
						closeSpeed: 0
					});
				} else { // ABRE LA PRIMERA VENTANA
					this.getOverlay().expose({
						color: '#000000',
						zIndex: 10090,
						closeOnClick: false,
						closeOnEsc: false
					});
				} // Other onLoad functions
			},
			onClose: function() {
				$.mask.close();
				if ($oldMask != null) { // re-expose previous overlay if there was one
					$oldMask.expose({
						color: '#000000',
						zIndex: oldMaskZ,
						closeOnClick: false,
						closeOnEsc: false,
						loadSpeed: 0
					});
					
					$(".apple_overlay").css("zIndex", oldMaskZ + 2); // Assumes the other overlay has apple_overlay class
				}
			}
		}).load();
	});
}

xajax_cargaLstEmpresaFinal("<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>", "byId('btnBuscar').click();");
xajax_cargaLstVendedor();
xajax_cargaLstEstadoPedido();
xajax_cargaLstModulo();
xajax_listaPedido(0,'numero_pedido','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

</script>