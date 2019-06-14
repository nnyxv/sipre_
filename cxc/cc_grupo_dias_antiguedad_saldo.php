<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_grupo_dias_antiguedad_saldo"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_grupo_dias_antiguiedad_saldo.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Dias Antigüedad de Saldos</title>
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
	
	<script>
		function validar(){
			if (validarCampo('txtCodigoCliente','t','') == true) {	
				xajax_listarTodoCliente(xajax.getFormValues('frmDias'));
			} else {
				validarCampo('txtCodigoCliente','t','');
				alert("Los campos señalados en rojo son requeridos");
				return false;		
			}
		}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCuentasPorCobrar">Grupo Dias Antigüedad de Saldos
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmDias" name="frmDias" style="margin:0">
				<table border="0" width="100%" align="center">
				<tr>
					<td colspan="2">
						<table border="0" width="100%" align="center">
						<tr>
							<td valign="top">
								<table border="0" align="center">
								<tr align="left">
									<td align="right" class="tituloCampo" width="120">Fecha Actual:</td>
									<td align="left">
										<input type="text" name="txtFechaInicial" id="txtFechaInicial" style="text-align:center" size="10" readonly="readonly"/>
									</td>
									<td align="right" class="tituloCampo" width="120">Último Cambio:</td>
									<td align="left">
										<input type="text" id="txtFechaFinal" name="txtFechaFinal" style="text-align:center" size="10" readonly="readonly"/>
									</td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td>
						<fieldset><legend class="legend">Grupo I</legend>
						<table border="0" width="100%" align="center">
						<tr>
							<td valign="top" width="50%">
								<table border="0">
								<tr align="left">
									<td align="right" class="tituloCampo" width="120">Desde:</td>
									<td id="tdlstDesde1"></td>
									<td align="right" class="tituloCampo" width="120">Hasta:</td>
									<td id="tdlstHasta1"></td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
						</fieldset>
					</td>
					<td>
						<fieldset><legend class="legend">Grupo II</legend>
						<table border="0" width="100%" align="center">
						<tr>
							<td valign="top" width="50%">
								<table border="0">
								<tr align="left">
									<td align="right" class="tituloCampo" width="120">Desde:</td>
									<td id="tdlstDesde2"></td>
									<td align="right" class="tituloCampo" width="120">Hasta:</td>
									<td id="tdlstHasta2"></td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
						</fieldset>
					</td>
				</tr>
				<tr>
					<td>
						<fieldset><legend class="legend">Grupo III</legend>
						<table border="0" width="100%" align="center">
						<tr>
							<td valign="top" width="50%">
								<table border="0">
								<tr align="left">
									<td align="right" class="tituloCampo" width="120">Desde:</td>
									<td id="tdlstDesde3"></td>
									<td align="right" class="tituloCampo" width="120">Hasta:</td>
									<td id="tdlstHasta3"></td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
						</fieldset>
					</td>
					<td>
						<fieldset><legend class="legend">Grupo IV</legend>
						<table border="0" width="100%" align="center">
						<tr>
							<td valign="top" width="50%">
								<table border="0">
								<tr align="left">
									<td align="right" class="tituloCampo" width="120">Más de:</td>
									<td id="tdlstMasDe"></td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
						</fieldset>
					</td>
				</tr>
				<tr>
					<td align="right" colspan="2"><hr>
						<button type="button" id="bttGenerar" name="bttGenerar" onclick="xajax_guardarDias(xajax.getFormValues('frmDias'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
					</td>
				</tr>
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
byId('txtFechaInicial').className = 'inputHabilitado';
byId('txtFechaFinal').className = 'inputHabilitado';

byId('txtFechaInicial').value = "<?php echo date(spanDateFormat); ?>";

xajax_cargarDias();
</script>