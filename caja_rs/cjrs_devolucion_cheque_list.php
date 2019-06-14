<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_devolucion_cheque_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_devolucion_cheque_list.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Devolución de Cheques</title>
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
	
	<script>
		function validarCrearNotaCargo(numeroCheque,monto,idCliente,idBanco){
			if (confirm("Seguro desea generar una nota de cargo por el cheque "+numeroCheque+"?")){
				xajax_guardarNotaCargo(numeroCheque,monto,idCliente,idBanco)
			}
		}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
            <td class="tituloPaginaCajaRS">Devolución de Cheques</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr class="noprint">
			<td align="right">
				<table align="left">
				<tr>
					<td>
						<button type="button" onclick="xajax_imprimirDevolucionCheque(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button>
					</td>
				</tr>
				</table>
				<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
					<table align="right" border="0">
					<tr id="trEmpresa" align="left">
                        <td align="right" class="tituloCampo" width="120">Empresa:</td>
                        <td id="tdlstEmpresa">
                            <select id="lstEmpresa" name="lstEmpresa">
                                <option value="-1">[ Todos ]</option>
                            </select>
                        </td>
                    </tr>
					<tr align="left">
						<td align="right" class="tituloCampo" width="120">Fecha:</td>
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
						<td align="right" class="tituloCampo" width="120">Criterio:</td>
						<td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>	
						<td>
							<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscar(xajax.getFormValues('frmBuscar'));">Buscar</button>
							<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
						</td>
					</tr>
					</table>
				</form>
			</td>
		</tr>
		<tr>
			<td id="tdListadoCheques"></td>
		</tr>
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
						<table>
						<tr>
							<td><img src="../img/iconos/arrow_rotate_clockwise.png" /></td>
							<td>Generar Nota de Débito</td>
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
byId('txtFechaDesde').className = 'inputHabilitado';
byId('txtFechaHasta').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

byId('txtFechaDesde').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

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

xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_validarAperturaCaja();
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listadoCheques(0,'','ASC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value);
</script>