<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(validaAcceso("ga_solicitud_compra_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_solicitud_compra_historico_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Hist&oacute;rico Solicitudes de Compra</title>
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
    
    <script>
		function abrePdf(idSolicituCompras, seccionEmpUsuario){
			window.open('reportes/ga_solicitud_compra_pdf.php?idSolCom='+idSolicituCompras+'&session='+seccionEmpUsuario);
		}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_compras.php"); ?></div>

    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCompras">Hist&oacute;rico Solicitudes de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
                <table align="right" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                                <table  border="0" align="right">
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="120">Empresa:</td>
                                        <td id="tdlstEmpresa" colspan="2"></td>
                                    </tr>
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="120">Fecha Solicitud:</td>
                                        <td colspan="2">&nbsp;Desde:&nbsp;<input id="txtFechaDesde"  name="txtFechaDesde" class="inputHabilitado" type="text" style="text-align:center" size="10" autocomplete="off">&nbsp;Hasta:&nbsp;<input id="txtFechaHasta" name="txtFechaHasta" class="inputHabilitado" type="text" style="text-align:center" size="10" autocomplete="off"></td>
                                    </tr> 
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="120">Tipo De Compra:</td>
                                        <td id="tdlsttipCompra">&nbsp;</td>
                                    </tr>
                                    <tr align="left">
                                        <td align="right" class="tituloCampo" width="120">Nro. Solicitud:</td>
                                        <td><input type="text" id="txtNroSolicitud" name="txtNroSolicitud" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                                        <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                        <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                                         <td>
                                            <button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_BuscarSolicituComp(xajax.getFormValues('frmBuscar'));" style="cursor:default">Buscar</button>
                                            <button type="button" id="btnLimpiar" name="btnLimpiar" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();" style="cursor:default">Limpiar</button>
                                        </td>
                                    </tr>
                                </table>
                            </form>
                        </td>
                    </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td id="tdListSolictComp"></td>
        </tr>
        <tr>
        	<td class="divMsjInfo2">
                <table cellpadding="0" cellspacing="0"  width="100%">
                    <tr>
                        <td width="25" align="left"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">
                            <table>
                                <tr>
                                <td><img src="../img/iconos/aprob_control_calidad.png"/></td>
                                <td>Ordenada</td>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/page_white_acrobat.png"/></td>
                                <td>Archivo PDF</td>
                                <td>&nbsp;</td>
                                </tr>
                            </table>
                        </td>
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
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', '');
xajax_combLstTipCompra();

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
	
xajax_BuscarSolicituComp(xajax.getFormValues('frmBuscar'));
</script>
