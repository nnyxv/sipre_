<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_pedido_compra_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_pedido_compra_historico_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Histórico de Pedido de Compra</title>
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
	function validarEliminar(idPedidoCompra){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarPedidoCompra(idPedidoCompra, xajax.getFormValues('frmListaPedidoCompra'));
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
        	<td class="tituloPaginaVehiculos">Histórico de Pedido de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Empresa:</td>
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
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarPedidoCompra(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
				</tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPedidoCompra" name="frmListaPedidoCompra" style="margin:0">
            	<div id="divListaPedidoCompra" style="width:100%">
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
                        	<td><img src="../img/iconos/ico_marron.gif"/></td><td>Anulado</td>
                            <td>&nbsp;</td>
                        	<!--<td><img src="../img/iconos/ico_gris.gif"/></td><td>Nota de Crédito</td>
                            <td>&nbsp;</td>-->
                        	<td><img src="../img/iconos/ico_morado.gif"/></td><td>Compra Registrada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Forma de Pago Totalmente Asignadas</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif"/></td><td>Forma de Pago Parcialmente Asignadas</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"/></td><td>Forma de Pago Sin Asignar</td>
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
                            <td><img src="../img/iconos/ico_examinar.png"/></td><td>Ver Unidades</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_green.png"/></td><td>Ver Cartas de Solicitud</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Pedido Compra PDF</td>
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
    
<form id="frmUnidadPedido" name="frmUnidadPedido" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblUnidadPedido" width="960">
    <tr>
    	<td><div id="divListaUnidadesPedido" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td>
        	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table>
                    <tr>
                    	<td><img src="../img/iconos/car_go.png"/></td><td>Vendido</td>
                        <td>&nbsp;</td>
                    	<td><img src="../img/iconos/car_error.png"/></td><td>Reservado</td>
                        <td>&nbsp;</td>
                    	<td><img src="../img/iconos/cancel.png"/></td><td>Anulado</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/accept.png"/></td><td>Disponible</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/siniestrado.png"/></td><td>Siniestrado</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/almacen_buen_estado.png"/></td><td>Inspeccionado</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/transito.png"/></td><td>En Transito</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/error.png"/></td><td>No Registrado</td>
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
                        <td><img src="../img/iconos/ico_vehiculo_normal.png"/></td><td>Vehículo Normal</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_vehiculo_flotilla.png"/></td><td>Vehículo por Flotilla</td>
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
                        <td><img src="../img/iconos/ico_view.png"/></td><td>Ver Registro de Compra</td>
                        <td>&nbsp;</td>
                        <td><img src="../img/iconos/ico_print.png"/></td><td>Imprimir Comprobante de Retención</td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" onclick="byId('divFlotante').style.display='none';">Cerrar</button>
        </td>
    </tr>
    </table>
</form>

	<table border="0" id="tblSolicitudCompra" width="560">
    <tr>
    	<td><div id="divListaSolicitudCompra" style="width:100%"></div></td>
    </tr>
    	<td align="right"><hr>
            <button type="button" onclick="byId('divFlotante').style.display='none';">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
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
xajax_listaPedidoCompra(0, "idAsignacion", "DESC", '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>