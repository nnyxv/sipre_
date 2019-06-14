<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_planilla_deposito_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

$currentPage = $_SERVER["PHP_SELF"];

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_planilla_deposito_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Depósitos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>
	
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>

	<script>
    function validarFrmNroPlanilla(){
        if (validarCampo('txtNuevoNroPlanilla','t','') == true){
            xajax_guardarPlanilla(xajax.getFormValues('frmPlanilla'), xajax.getFormValues('frmListaDeposito'));
        } else {
            validarCampo('txtNuevoNroPlanilla','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
        }
    }
    </script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table align="center" border="0" width="100%">
		<tr>
			<td align="center" class="tituloPaginaCaja">Depósitos<br/><span class="textoNegroNegrita_10px">(Lista de Dep&oacute;sitos)</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
            <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarDeposito(xajax.getFormValues('frmBuscar')); return false;" style="margin:0" >
				<table align="right" border="0">
                <tr id="trEmpresa" align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                </tr>
				<tr>
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
					<td align="right" class="tituloCampo" width="120">Nro. Depósito:</td>
					<td align="left">
						<input type="text" id="txtCriterio" name="txtCriterio" size="16" onkeyup="xajax_buscarDeposito(xajax.getFormValues('frmBuscar'));"/>
					</td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarDeposito(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>	
				</table>
            </form>
			</td>
		</tr>
		<tr>
			<td colspan="2">
            <form id="frmListaDeposito" name="frmListaDeposito" style="margin:0">
            	<div id="divListaDeposito" style="width:100%"></div>
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
							<td><img src="../img/iconos/pencil.png"/></td>
							<td>Editar</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/ico_print.png"/></td>
							<td>Imprimir</td>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
<form id="frmListaArtPedido" name="frmListaArtPedido" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblListaArtPedido" width="960">
	<tr>
		<td id="tdlistaPlanilla"></td>	
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante').style.display='none';">Cerrar</button>
		</td>
	</tr>
	</table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
<form id="frmPlanilla" name="frmPlanilla" onsubmit="return false;" style="margin:0">
	<table border="0" width="360">
    <tr>
        <td>
            <table border="0" width="100%">
            <tr id="trId" align="left">
                <td align="right" class="tituloCampo" width="30%">Nro. Planilla:</td>
                <td width="30%"><input type="text" id="txtNroPlanilla" name="txtNroPlanilla" readonly="readonly"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Nuevo Nro. Planilla:</td>
                <td><input type="text" id="txtNuevoNroPlanilla" name="txtNuevoNroPlanilla"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <input type="hidden" id="hddIdPlanilla" name="hddIdPlanilla"/>
            <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
            <button type="submit" id="btnEditar" name="btnEditar" onclick="validarFrmNroPlanilla();">Guardar</button>
            <button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante2').style.display='none';">Cancelar</button>
        </td>
    </tr>
	</table>
</form>
</div>

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
		cellColorScheme:"vino"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"vino"
	});
};

xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaPlanilla(0,'','','' + '|' + -1);

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>