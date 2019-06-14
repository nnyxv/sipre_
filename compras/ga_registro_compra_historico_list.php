<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("ga_registro_compra_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_registro_compra_historico_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Registro de Facturas de Compra</title>
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
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script type="text/javascript" language="javascript">
    	function cargarAtributo(){/*COLOCAR EL ATRIBUTO TITEL*/
			var fechaDesde = document.getElementById("txtFechaDesde").value;
			var fechaHasta = document.getElementById('txtFechaHasta').value;
				if( fechaDesde != "" && fechaHasta != "" ){
					var titulo = "Informe de compras desde: "+fechaDesde+" Hasta: "+fechaHasta;
				} else {
					var titulo = "Informe de compras";
				}
			
			
			byId('btnExportarExcel').setAttribute("title", titulo);
		}
    </script>
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
        	<td class="tituloPaginaCompras">Histórico de Registro de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td><!--BOTON IMPRIMIR-->
                    	<button type="button" id="btnImprimir" name="btnImprimir" onclick="xajax_encabezadoEmpresa(byId('lstEmpresa').value); window.print();" style="cursor:default">
                            <table align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_print.png"/></td>
                                    <td>&nbsp;</td>
                                    <td>Imprimir</td>
                                </tr>
                            </table>
                        </button>
                    </td>
                    <td><!--BOTON EXPORTAR EXCEL-->
                    	<button type="button" id="btnExportarExcel" name="btnExportarExcel" onmouseover="cargarAtributo();" onclick="xajax_exportarExcel(xajax.getFormValues('frmBuscar'));" style="cursor:default">
                            <table align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/page_excel.png"/></td>
                                    <td>&nbsp;</td>
                                    <td>Exportar</td>
                                </tr>
                            </table>
                        </button>
                    </td>
                </tr>
                </table>				
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="2"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Nro. Sol./Orden:</td>
                    <td><input type="text" id="txtNroSolicitud" name="txtNroSolicitud" autocomplete="off" onkeyup="byId('btnBuscar').click();"></td>
                	<td align="right" class="tituloCampo" width="120">Nro. Fact./Control:</td>
                    <td><input type="text" id="txtNroFactura" name="txtNroFactura" autocomplete="off" onkeyup="byId('btnBuscar').click();" /></td>
				</tr>
                <tr align="left">
                	<td></td>
                    <td></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"></td>
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
            	<div id="divListaRegistroCompra" style="width:100%">
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
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Registro Compra PDF</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_print.png"/></td><td>Comprobante de Retención</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_red.png"/></td><td>Comprobante de Retención ISLR</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/new_window.png"/></td><td>Movimiento Contable</td>
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
byId('txtNroFactura').className = "inputHabilitado";
byId('txtNroSolicitud').className = "inputHabilitado";
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaRegistroCompra(0,'id_factura','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|||' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value);
</script>