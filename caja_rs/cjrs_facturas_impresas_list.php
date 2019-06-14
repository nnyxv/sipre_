<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_facturas_impresas_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cjrs_facturas_impresas_list.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Facturas Impresas</title>
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
    <script language="javascript">
    
    function enviarfrmEditarConsecutivo(){
            var consecutivoFiscal = byId('hddConsecutivoFiscal').value;
            var idFactura = byId('hddIdFactura').value;
            xajax_formEditarConsecutivoFiscal(idFactura,consecutivoFiscal);
    }

    function reimprimirFactura(nomObjeto, verTabla, valor){
        confirmar=confirm("¿Reimprimir esta factura?");
        if(confirmar){
            xajax_reimprimirFactura(valor);
        }
    }

    function abrirDivFlotante3(nomObjeto, verTabla, valor) {        
        byId('tblEditarConsecutivo').style.display = 'none';

        if (verTabla == "tblEditarConsecutivo") {
            document.forms['frmEditarConsecutivo'].reset();
            tituloDiv3 = 'Editar Consecutivo Fiscal';
        }

        byId(verTabla).style.display = '';
        openImg(nomObjeto);
        byId('tdFlotanteTitulo3').innerHTML = tituloDiv3;
        xajax_buscarFacturaEditarConsecutivoFiscal(valor);
    }
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCajaRS" >Facturas Impresas</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr style="vertical-align:top">
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr><td><button type="button" onclick="xajax_cargaLstOrientacionPDF();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button></td></tr>
                        <tr><td id="tdlstOrientacionPDF"></td></tr>
                        </table>
                    </td>
                    <td>
                        <button type="button" onclick="xajax_exportarFactura(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
                <table align="right" border="0">
                <tr align="left">
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
                    <td align="right" class="tituloCampo" width="120">Vendedor:</td>
                    <td id="tdlstEmpleado"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Aplica Libro:</td>
                    <td>
                        <select id="lstAplicaLibro" name="lstAplicaLibro" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">No</option>
                            <option value="1">Si</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Estado Factura:</td>
                    <td>
                        <select multiple id="lstEstadoFactura" name="lstEstadoFactura" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option selected="selected" value="0">No Cancelado</option>
                            <option selected="selected" value="1">Cancelado</option>
                            <option selected="selected" value="2">Cancelado Parcial</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo Pago:</td>
                    <td>
                        <select id="lstTipoPago" name="lstTipoPago" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Crédito</option>
                            <option value="1">Contado</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Módulo:</td>
                    <td id="tdlstModulo"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Ver:</td>
                    <td>
                        <select id="lstEstadoPedido" name="lstEstadoPedido" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="NO">Factura</option>
                            <option value="SI">Factura (Con Devolución)</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Estado Fiscal:</td>
                    <td>
                        <select multiple id="lstEstadoFiscal" name="lstEstadoFiscal" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option selected="selected" value="1">Impresa</option>
                            <option selected="selected" value="2">No Impresa</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>	
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarFactura(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
		<tr>
			<td>
            	<div id="divListaFactura" style="width:100%">
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
                            <td><img src="../img/iconos/ico_gris.gif" /></td><td>Factura (Con Devolución)</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_morado.gif" /></td><td>Factura</td>
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
                	<td></td>
                	<td></td>
                	<td class="tituloCampo">Total Neto:</td>
                    <td><span id="spnTotalNeto"></span></td>
				</tr>
                <tr align="right">
                	<td></td>
                	<td></td>
                    <td class="tituloCampo">Total Impuesto:</td>
                    <td><span id="spnTotalIva"></span></td>
				</tr>
                <tr align="right" class="trResaltarTotal">
                    <td class="tituloCampo" width="120">Saldo Factura(s):</td>
                    <td width="150"><span id="spnSaldoFacturas"></span></td>
                    <td class="tituloCampo" width="120">Total Factura(s):</td>
                    <td width="150"><span id="spnTotalFacturas"></span></td>
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
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante32" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
        <form id="frmEditarConsecutivo" name="frmEditarConsecutivo" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblEditarConsecutivo" width="560">       
        <tr>
            <td>
                <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" >Empresa:</td>
                            <td>
                                <div id="nomEmpresa"></div>
                            </td>
                            <td align="right" class="tituloCampo" >Cliente:</td>
                            <td>
                                <div id="nomCliente"></div>
                            </td>
                        </tr>

                        <tr align="left">
                            <td align="right" class="tituloCampo" >Nro. Factura:</td>
                            <td>
                                <div id="nroFactura"></div>
                            </td>
                            <td align="right" class="tituloCampo" >Nro. Control:</td>
                            <td>
                                <div id="nroControl"></div>
                            </td>
                        </tr>
                        
                        <tr align="left">
                            <td align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Consecutivo Fiscal:</td>
                            
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="hddConsecutivoFiscal" name="hddConsecutivoFiscal" style="text-align:right;"/>
                                    <input type="hidden" id="hddIdFactura" name="hddIdFactura" value=""/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td align="right">
                <hr>
                <button type="button" id="btnGuardarClaveEspecial" name="btnGuardarClaveEspecial" onclick="enviarfrmEditarConsecutivo();">Guardar</button>
                <button type="button" id="btnCancelarClaveEspecial" name="btnCancelarClaveEspecial" class="close">Cancelar</button>
            </td>
        </tr>
    </table>
</form>
</div>
<script>
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
byId('txtFechaDesde').className = 'inputHabilitado';
byId('txtFechaHasta').className = 'inputHabilitado';
byId('lstAplicaLibro').className = 'inputHabilitado';
byId('lstEstadoFactura').className = 'inputHabilitado';
byId('lstTipoPago').className = 'inputHabilitado';
byId('lstEstadoPedido').className = 'inputHabilitado';
byId('lstEstadoFiscal').className = 'inputHabilitado';
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

var lstEstadoFactura = $.map($("#lstEstadoFactura option:selected"), function (el, i) { return el.value; });
var lstEstadoFiscal = $.map($("#lstEstadoFiscal option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstVendedor();
xajax_cargaLstModulo();
xajax_listaFactura(0,'idFactura','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value +
'||' + byId('lstAplicaLibro').value + '|' + lstEstadoFactura.join() + '|' + byId('lstTipoPago').value);

var theHandle3 = document.getElementById("divFlotanteTitulo3");
var theRoot3   = document.getElementById("divFlotante3");
Drag.init(theHandle3, theRoot3);
</script>