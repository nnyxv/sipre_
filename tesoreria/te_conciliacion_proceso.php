<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("te_conciliacion"))) {
	echo "<script>alert('Acceso Denegado'); window.location.href = 'index.php';</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("controladores/ac_te_conciliacion_proceso.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Conciliación</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>    
    
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaAuditoria').style.display = 'none';
		
		if (verTabla == "tblListaAuditoria") {			
			xajax_listaAuditoria(0,'','',valor);
			
			tituloDiv1 = 'Comentarios';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaAuditoria") {		
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_tesoreria.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaTesoreria">Conciliación</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
				<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td align="left">
                        <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" value=""/>
                        <input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" />
                    </td>
                </tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center" value="<?php echo date(spanDateFormat, strtotime("01-".$_GET["txtFechaConciliacion"])); ?>"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center" value="<?php echo date(spanDateFormat, strtotime(date("t-m-Y", strtotime("01-".$_GET["txtFechaConciliacion"])))); ?>"/></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Banco:</td>
                    <td align="left">
                        <input type="hidden" id="hddIdBanco" name="hddIdBanco" value=""/>
                        <input type="text" id="txtBanco" name="txtBanco" readonly="readonly" />
                    </td> 
                </tr>
                <tr align="left">
                   <td class="tituloCampo" align="right" width="120">Nº Cuenta:</td>
                    <td align="left">
                        <input type="hidden" id="hddSaldoBancoSaldo1" name="hddSaldoBancoSaldo1" value=""/>
                        <input type="hidden" id="hddIdCuenta" name="hddIdCuenta" value=""/>																	
                        <input type="text" id="txtCuenta" name="txtCuenta" readonly="readonly"/>
                    </td>
                    <td class="tituloCampo" align="right" width="120">Mes Conciliación:</td>
                    <td align="left">
                        <input type="text" id="txtFecha" name="txtFecha" readonly="readonly" size="10" style="text-align:center" value=""/>
                    </td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="byId('txtFechaDesde').value=byId('txtFechaDesde').defaultValue;  byId('txtFechaHasta').value = byId('txtFechaHasta').defaultValue; byId('btnBuscar').click();">Limpiar</button>
                    </td>
				</tr>
                </table>
                </form>
            </td>
        </tr>
        </table>
        
        <table width="100%">
        <tr>
            <td valign="top">
            	<form id="frmListaEstadoCuenta" name="frmListaEstadoCuenta">
            	<fieldset><legend class="legend">Documentos Aplicados</legend>
                <table width="100%">
                <tr>
		            <td id="tdListaEstadoAplicado"></td>
                </tr>
                <tr>
                	<td>
						<br><br><br><br><br><br>
                        <table align=right width="100%">
						<tr>
							<td align="right"><button type="button" id="btnGuardarConciliacion" name="btnGuardarConciliacion" onclick="xajax_guardarConciliacion(xajax.getFormValues('frmListaEstadoCuenta'),xajax.getFormValues('frmBuscar'));">Guardar</button></td>
                            <td align="left"><button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('te_conciliacion.php','_self')">Cancelar</button></td>
						</tr>
                        </table>
                    </td>
                </tr>
                </table>
                <table width="100%">
                <tr>
		            <td><input type="hidden" id="hddObj" name="hddObj"/></td>
                </tr>
                </table>
             	</fieldset>
                </form>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaAuditoria" style="display:none" width="610">
    <tr>
        <td id="tdListaAuditoria"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarAuditoria" name="btnCancelarAuditoria" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</div>

<script>

xajax_cargarDcto();

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
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

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>