<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_presupuesto_venta_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_presupuesto_venta_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Presupuestos de Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function validarAnular(idPresupuesto){
		if (confirm('¿Seguro desea anular este registro?') == true) {
			xajax_anularPresupuesto(idPresupuesto, xajax.getFormValues('frmListaPresupuesto'));
		}
	}
	
	function validarDesautorizar(idPresupuesto){
		if (confirm('¿Seguro desea desautorizar este registro?') == true) {
			xajax_desautorizarPresupuesto(idPresupuesto, xajax.getFormValues('frmListaPresupuesto'));
		}
	}
	
	function validarAutorizar(idPresupuesto){
		if (confirm('¿Seguro desea autorizar este registro?') == true) {
			xajax_autorizarPresupuesto(idPresupuesto, xajax.getFormValues('frmListaPresupuesto'));
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Presupuestos de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    	<?php
                        $presupuestoVentaForm = (in_array(idArrayPais,array(1,2,3))) ?
						sprintf("an_ventas_presupuesto_insertar.php") :
						sprintf("an_presupuesto_venta_form.php"); ?>
                    	<button type="button" onclick="window.location.href='<?php echo $presupuestoVentaForm; ?>';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        <button type="button" onclick="window.location.href='an_combo_presupuesto_list.php';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo Presupuesto Accesorios</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
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
                    <td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                    	<select id="lstEstatusPedido" name="lstEstatusPedido" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                            <option value="33">Presupuesto Desautorizado</option>
                            <option value="00">Presupuesto Autorizado</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarPresupuesto(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPresupuesto" name="frmListaPresupuesto" style="margin:0">
            	<div id="divListaPresupuesto" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_marron.gif"/></td><td>Anulado</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_gris.gif"/></td><td>Nota de Crédito</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_morado.gif"/></td><td>Facturado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Pedido Autorizado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"/></td><td>Pedido Desautorizado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif"/></td><td>Presupuesto Autorizado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_naranja.gif"/></td><td>Presupuesto Desautorizado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Presupuesto Anulado</td>
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
                            <td><img src="../img/iconos/coins.png"/></td><td>Generar Financiamiento</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/book_next.png"/></td><td>Generar Pedido</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar Presupuesto</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/generarPresupuesto.png"/></td><td>Editar Presupuesto Accesorios</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/accept.png"/></td><td>Autorizar Presupuesto</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cancel.png"/></td><td>Desautorizar Presupuesto</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_delete.png"/></td><td>Anular Presupuesto</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Presupuesto Venta PDF</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_print.png"/></td><td>Imprimir Presupuesto</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_green.png"/></td><td>Presupuesto Accesorio PDF</td>
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
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('lstEstatusPedido').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>", 
		cellColorScheme:"orange"
	});
};

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaPresupuesto(0, 'id_presupuesto', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|||'+byId('lstEstatusPedido').value);
</script>