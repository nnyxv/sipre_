<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_historico_cierre_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_historico_cierre_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Histórico de Cierre</title>
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
	function validarGuardarAprobacion() {
		if (confirm('¿Seguro desea aprobar esta caja?') == true) {
			xajax_guardarAprobacion(xajax.getFormValues('frmVerificacion'), xajax.getFormValues('frmListaCierre'));
		}
	}
	
	function validarGuardarValidacion() {
		if (confirm('¿Seguro desea validar esta caja?') == true) {
			xajax_guardarValidacion(xajax.getFormValues('frmVerificacion'), xajax.getFormValues('frmListaCierre'));
		}
	}
	
	function validarImprimirCaja() {
		xajax_abrirPortadaCaja(xajax.getFormValues('frmVerificacionPortadaCaja'));
	}
	</script>
</head>

<body class="bodyVehiculos">
	<script type="text/javascript" language="javascript" src="../js/wz_tooltip/wz_tooltip.js"></script>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
    
	<div id="divInfo" class="print" style="vertical-align:middle">
		<table align="center" border="0" width="100%">
		<tr>
			<td class="tituloPaginaCajaRS">Histórico de Cierre</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<button type="button" onclick="xajax_exportarListadoCierre(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarCierre(xajax.getFormValues('frmBuscar')); return false;" style="margin:0" >
				<table border="0" align="right">
                <tr id="trEmpresa" align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
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
					<td align="right" class="tituloCampo" width="120">Estatus:</td>
					<td align="left" id="tdVerificacion"><label>
						<select id="slctVerificacion" name="slctVerificacion" class="inputHabilitado" onchange="byId('btnBuscar').click();">
							<option value="-1" selected="selected">[ Seleccione ]</option>
							<option value="0">Caja No Verificada</option>
							<option value="1">Caja Aprobada</option>
							<option value="2">Caja Validada</option>
						</select></label>
					</td>
					<td>
						<button type="submit" id="btnBuscar" onclick="xajax_buscarCierre(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>
				</table>
            </form>
			</td>
		</tr>
		<tr>
			<td>
            <form id="frmListaCierre" name="frmListaCierre" style="margin:0">
            	<div id="tdListadoCierre" style="width:100%"></div>
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
							<td><img src="../img/iconos/ico_examinar.png"/></td><td>Portada de Caja</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/application_view_columns.png"/></td><td>Recibos por Medio de Pago</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/find.png"/></td><td>Verificación de Caja</td>
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
							<td><img src="../img/iconos/ico_verde.gif"/></td><td>Caja Validada</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/ico_azul.gif"/></td><td>Caja Aprobada</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/ico_rojo.gif"/></td><td>Caja No Verificada</td>
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

<!--VENTANA DE VERIFICACION-->
<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmVerificacion" name="frmVerificacion" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblVerificacion" width="600">
	<tr>
		<td colspan="2">
			<table align="center">
			<tr>
				<td align="right" class="tituloCampo" width="120">Fecha Caja:</td>
				<td><input type="text" id="txtFechaCaja" name="txtFechaCaja" readonly="readonly" size="12" style="text-align:center; border:none"/></td>
				<td align="right" class="tituloCampo" width="120">Hora Cierre:</td>
				<td><input type="text" id="txtHoraCierre" name="txtHoraCierre" readonly="readonly" size="12" style="text-align:center; border:none"/></td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<table align="center">
			<tr>
				<td>
					<input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" style="border:none;"/>
					<input type="hidden" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="12" style="text-align:center; border:none"/>
                </td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
	<table border="1" style="border-collapse:collapse; border-color:#000000; border-top:none;" width="100%">
	<tr valign="top" align="center"> 
		<td><div>APROBACIÓN</div></td>
		<td><div>VALIDACIÓN</div></td>
	</tr>
	<tr valign="top">
		<td width="25%">
			<table cellspacing="0" border="0" width="100%">
			<tr align="left"><td colspan="2">Aprobado Por:</td></tr>
			<tr>
				<td align="center" colspan="2">
					<input type="text" id="txtEmpleadoAprobacion" name="txtEmpleadoAprobacion" readonly="readonly" size="40" style="border:none"/>
					<input type="hidden" id="txtIdEmpleadoAprobacion" name="txtIdEmpleadoAprobacion" readonly="readonly" size="6" style="text-align:right;"/>
				</td>
			</tr>
			<tr align="left"><td colspan="2">Usuario:</td></tr>
			<tr>
				<td align="center" colspan="2">
					<input type="text" id="txtUsuarioAprobacion" name="txtUsuarioAprobacion" readonly="readonly" size="40" style="border:none"/>
					<input type="hidden" id="txtIdUsuarioAprobacion" name="txtIdUsuarioAprobacion" readonly="readonly" size="6" style="text-align:right;"/>
				</td>
			</tr>
			<tr align="left"><td colspan="2">Fecha/Hora Aprobación:</td></tr>
			<tr>
				<td align="center" colspan="2">
					<input type="text" id="txtFechaAprobacion" name="txtFechaAprobacion" readonly="readonly" size="10" style="text-align:left; border:none"/>
					<input type="text" id="txtHoraAprobacion" name="txtHoraAprobacion" readonly="readonly" size="25" style="text-align:left; border:none"/>
				</td>
			</tr>
			</table>
		</td>
		<td width="25%">
			<table cellspacing="0" border="0" width="100%">
			<tr align="left"><td colspan="2">Validado Por:</td></tr>
			<tr>
				<td align="center" colspan="2">
					<input type="text" id="txtEmpleadoValidacion" name="txtEmpleadoValidacion" readonly="readonly" size="40" style="border:none"/>
					<input type="hidden" id="txtIdEmpleadoValidacion" name="txtIdEmpleadoValidacion" readonly="readonly" size="6" style="text-align:right;"/>
				</td>
			</tr>
			<tr align="left"><td colspan="2">Usuario:</td></tr>
			<tr>
				<td align="center" colspan="2">
					<input type="text" id="txtUsuarioValidacion" name="txtUsuarioValidacion" readonly="readonly" size="40" style="border:none"/>
					<input type="hidden" id="txtIdUsuarioValidacion" name="txtIdUsuarioValidacion" readonly="readonly" size="6" style="text-align:right;"/>
				</td>
			</tr>
			<tr align="left"><td colspan="2">Fecha/Hora Validación:</td></tr>
			<tr>
				<td align="center" colspan="2">
					<input type="text" id="txtFechaValidacion" name="txtFechaValidacion" readonly="readonly" size="10" style="text-align:left; border:none"/>
					<input type="text" id="txtHoraValidacion" name="txtHoraValidacion" readonly="readonly" size="25" style="text-align:left; border:none"/>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="right" colspan="4"><hr>
            <input type="hidden" id="hddIdApertura" name="hddIdApertura"/>
            <input type="hidden" id="hddIdCierre" name="hddIdCierre"/>
			<button type="button" id="btnGuardarAprobacion" name="btnGuardarAprobacion" onclick="validarGuardarAprobacion();" style="display:none"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_aceptar_amarillo.png"/></td><td>&nbsp;</td><td>Aprobar</td></tr></table></button>
			<button type="button" id="btnGuardarValidacion" name="btnGuardarValidacion" onclick="validarGuardarValidacion();" style="display:none"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_aceptar.gif"/></td><td>&nbsp;</td><td>Validar</td></tr></table></button>
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante1').style.display='none';"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
</div>

<!--VENTANA DE PORTADA DE CAJA-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
<form id="frmVerificacionPortadaCaja" name="frmVerificacionPortadaCaja" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblVerificacion" width="360">
	<tr>
		<td colspan="2">
			<table width="100%">
			<tr align="left">
				<td align="right" class="tituloCampo">Fecha Cierre:</td>
                <td><input type="text" id="txtFechaCierre" name="txtFechaCierre" autocomplete="off" readonly="readonly" size="10" style="text-align:center"/></td>
			</tr>
			<tr align="left">
				<td align="right" class="tituloCampo" width="40%">Tipo Pago:</td>
				<td id="tdVerificacionPortadaCaja" width="60%"><label>
					<select id="slctVerificacionPortadaCaja" name="slctVerificacionPortadaCaja" class="inputHabilitado">
						<option value="2" selected="selected">[ Seleccione ]</option>
						<option value="1">Contado</option>
						<option value="0">Crédito</option>
					</select></label>
				</td>
			</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="right" colspan="4"><hr>
            <input type="hidden" id="hddIdAperturaPortada" name="hddIdAperturaPortada"/>
            <input type="hidden" id="hddIdCierrePortada" name="hddIdCierrePortada"/>
            <input type="hidden" id="hddIdAbrir" name="hddIdAbrir"/>
			<button type="button" id="btnGuardarVerificacionPortadaCaja" name="btnGuardarVerificacionPortadaCaja" onclick="validarImprimirCaja();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Aceptar</td></tr></table></button>
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante2').style.display='none';"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
</div>

<script>
byId('txtFechaDesde').className = 'inputHabilitado';
byId('txtFechaHasta').className = 'inputHabilitado';

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
</script>