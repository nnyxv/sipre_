<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_registro_compra_historico_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_registro_compra_historico_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Histórico de Registro de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
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
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblImportacion').style.display = 'none';
		
		if (verTabla == "tblImportacion") {
			document.forms['frmImportacion'].reset();
			
			byId('txtCriterioImportacion').className = 'inputHabilitado';
			
			xajax_formImportacion(valor);
			
			tituloDiv1 = 'Detalles de la Importación';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Histórico de Registro de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_exportarRegistroCompra(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    </td>
                </tr>
                </table>
				
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
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
                	<td align="right" class="tituloCampo" width="120">Clave Mov.:</td>
                    <td id="tdlstClaveMovimiento"></td>
                	<td align="right" class="tituloCampo" width="120">Modo de Compra:</td>
                    <td>
                    	<select id="lstModoCompra" name="lstModoCompra">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="1">Nacional</option>
                        	<option value="2">Importación</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td></td>
                    <td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"></td>
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
                        	<td><img src="../img/iconos/ico_marron.gif"/></td><td>Pedido Anulado</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_gris.gif"/></td><td>Compra Registrada (Con Devolución)</td>
                            <td>&nbsp;</td>
                        	<td><img src="../img/iconos/ico_morado.gif"/></td><td>Compra Registrada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Orden Aprobada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif"/></td><td>Pedido Cerrado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"/></td><td>Pedido Pendiente por Terminar</td>
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
                            <td><img src="../img/iconos/tag_blue.png"/></td><td>Etiqueta</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_examinar.png"/></td><td>Ver Detalle</td>
                            <td>&nbsp;</td>
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
        <tr>
        	<td align="right"><hr>
            	<table>
                <tr align="right">
                	<td class="tituloCampo" width="120">Total Neto:</td>
                    <td width="150"><span id="spnTotalNeto"></span></td>
				</tr>
                <tr align="right">
                    <td class="tituloCampo">Total Impuesto:</td>
                    <td><span id="spnTotalIva"></span></td>
				</tr>
                <tr align="right" class="trResaltarTotal">
                    <td class="tituloCampo">Total Factura(s):</td>
                    <td><span id="spnTotalFacturas"></span></td>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmImportacion" name="frmImportacion" onsubmit="return false;" style="margin:0">
    <div id="tblImportacion" style="max-height:520px; overflow:auto; width:960px;">
		<input type="hidden" id="hddIdRegistroCompra" name="hddIdRegistroCompra"/>
        <table border="0" width="100%">
        <tr>
            <td>
                <!-- the tabs -->
                <ul class="tabs">
                    <li><a href="#">Básicos</a></li>
                    <li><a href="#">Registro</a></li>
                    <li><a href="#">Gastos Importación</a></li>
                    <li><a href="#">Otros Cargos</a></li>
                    <li><a id="aTabsExpediente" href="#">Expediente</a></li>
                </ul>
                
                <!-- tab "panes" -->
                <div class="panes">
                    <div>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="15%">Actividad del Importador:</td>
                            <td width="18%"><span id="spnActividadImportador"></span></td>
                            <td align="right" class="tituloCampo" width="15%">Clase de Importador:</td>
                            <td width="19%"><span id="spnClaseImportador"></span></td>
                            <td align="right" class="tituloCampo" width="15%">Clase de Solicitud:</td>
                            <td width="18%"><span id="spnClaseSolicitud"></span></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Puerto de Llegada:</td>
                            <td><span id="spnPuertoLlegada"></span></td>
                            <td align="right" class="tituloCampo">Destino Final:</td>
                            <td><span id="spnDestinoFinal"></span></td>
                            <td align="right" class="tituloCampo">Compañia Transportadora:</td>
                            <td><span id="spnCompaniaTransporte"></span></td>
                        </tr>
                        </table>
                    </div>
                    
                    <div>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Exportador:</td>
                            <td colspan="3"><span id="spnNombreProvExportador"></span></td>
                            <td align="right" class="tituloCampo">Vía de Envio:</td>
                            <td><span id="spnViaEnvio"></span></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Consignatario:</td>
                            <td colspan="5"><span id="spnNombreProvConsignatario"></span></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="13%">Aduana:</td>
                            <td width="31%"><span id="spnNombrePaisAduana"></span></td>
                            <td align="right" class="tituloCampo" width="13%">Nro. Expediente:</td>
                            <td width="15%"><span id="spnExpediente"></span></td>
                            <td align="right" class="tituloCampo" width="13%">Nro. Planilla Importación:</td>
                            <td width="15%"><span id="spnPlanillaImportacion"></span></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">País Origen:</td>
                            <td><span id="spnNombrePaisOrigen"></span></td>
                            <td align="right" class="tituloCampo">Nro. Embarque / BL:</td>
                            <td><span id="spnNumeroEmbarque"></span></td>
                            <td align="right" class="tituloCampo">Nro. Dcto. Transporte:</td>
                            <td><span id="spnDctoTransporte"></span></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">País Compra:</td>
                            <td><span id="spnNombrePaisCompra"></span></td>
                            <td align="right" class="tituloCampo">Puerto de Embarque:</td>
                            <td><span id="spnPuertoEmbarque"></span></td>
                            <td align="right" class="tituloCampo">Fecha Dcto. Transporte:</td>
                            <td><span id="spnFechaDctoTransporte"></span></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Moneda de Negociación:</td>
                            <td><span id="spnMonedaNegociacion"></span></td>
                            <td align="right" class="tituloCampo">% Seguro:</td>
                            <td><span id="spnPorcSeguro"></span></td>
                            <td align="right" class="tituloCampo">Vencimiento Dcto. Transporte:</td>
                            <td><span id="spnFechaVencDctoTransporte"></span></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Diferencia Cambiaria:</td>
                            <td><span id="spnDiferenciaCambiaria"></span></td>
                            <td></td>
                            <td></td>
                            <td align="right" class="tituloCampo">Fecha Estimada Llegada:</td>
                            <td><span id="spnFechaEstimadaLlegada"></span></td>
                        </tr>
                        </table>
                    </div>
                    
                    <div>
                        <table width="100%">
                        <tr>
                            <td id="divListaGastosImportacionFactura"></td>
                        </tr>
                        </table>
                    </div>
                    
                    <div>
                        <table width="100%">
                        <tr>
                            <td id="divListaOtrosCargosFactura"></td>
                        </tr>
                        <tr>
                            <td>
                                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                                <tr>
                                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                    <td align="center">
                                        <table>
                                        <tr>
                                            <td><img src="../img/iconos/ico_verde.gif"/></td><td>Factura Registrada</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/ico_azul.gif"/></td><td>Factura Registrada con Cargo Estimado</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Factura Sin Registrar con Cargo Estimado</td>
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
                                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Registro Compra PDF</td>
                                            <td>&nbsp;</td>
                                            <td><img src="../img/iconos/ico_print.png"/></td><td>Comprobante de Retención</td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </div>
                    
                    <div>
                        <table width="100%">
                        <tr>
                            <td>
                                <table align="left" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <button type="button" onclick="xajax_exportarExpediente(xajax.getFormValues('frmImportacion'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
                            
                                <table align="right" border="0">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120">Código:</td>
                                    <td id="tdCodigoArt"></td>
                                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                    <td><input type="text" id="txtCriterioImportacion" name="txtCriterioImportacion"/></td>
                                    <td>
                                        <button type="submit" id="btnBuscarDesbloqueoVenta" onclick="xajax_buscarArticuloExpediente(xajax.getFormValues('frmImportacion'));">Buscar</button>
                                        <button type="button" onclick="document.forms['frmImportacion'].reset(); byId('btnBuscarDesbloqueoVenta').click();">Limpiar</button>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td id="divListaExpediente"></td>
                        </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<script>
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('lstModoCompra').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

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
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
};

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

$(function() {
    // setup ul.tabs to work as tabs for each div directly under div.panes
    $("ul.tabs").tabs("div.panes > div");
});

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '1', '', '', '', 'onchange=\"byId(\'btnBuscar\').click();\"');
xajax_listaRegistroCompra(0, 'id_factura', 'DESC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>