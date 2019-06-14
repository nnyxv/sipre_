<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_cobranza_pendiente_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_cobranza_pendiente_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Cobranza Pendiente</title>
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
	
	<script language="javascript" type="text/javascript">
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tdDetalleCobranza').style.display = 'none';
		
		if (verTabla == "tdDetalleCobranza") {
		
			xajax_listadoDetalleCobranza('0', "", "",valor);
		
			tituloDiv1 = 'Cobranza Pendiente';
		} 
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCuentasPorCobrar">Cobranza Pendiente</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td id="tdCobranzaPendiente"></td>
		</tr>
		<tr>
			<td align="right">
				<table>
				<tr align="left">
					<td align="right" class="tituloCampo">Total Facturas:</td>
					<td><input type="text" id="txtTotalFactura" name="txtTotalFactura" style="text-align:right" class="trResaltarTotal3"/></td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="160">Total Notas de Débito:</td>
					<td><input type="text" id="txtTotalNcargo" name="txtTotalNcargo" style="text-align:right" class="trResaltarTotal3"/></td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo">Total General:</td>
					<td><input type="text" id="txtTotalGeneral" name="txtTotalGeneral" style="text-align:right" class="trResaltarTotal"/></td>
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

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><a id="aCerrarDivFlotante1" onclick="byId('divFlotante1').style.display='none';"><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></a></td></tr></table></div>
		<table border="0"width="700">
		<tr align="left">
			<td align="right" class="tituloCampo" width="120">Cliente:</td>
			<td><input type="text" name="txtNombreCliente" id="txtNombreCliente" size="45" readonly="readonly"/></td>
		</tr>
		<tr>
			<td colspan="2" id="tdDetalleCobranza"></td>
		</tr>
		<tr>
			<td colspan="2" align="right"><hr>
				<button type="button" id="btnCancelarDetalle" name="btnCancelarDetalle" class="close">Cancelar</button>
			</td>
		</tr>
		</table>
</div>
<script>
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
</script>

<script language="javascript">
var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

xajax_listadoCobranzaPendiente();
</script>