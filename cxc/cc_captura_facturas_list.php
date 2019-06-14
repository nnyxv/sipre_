<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_captura_facturas_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
$xajax = new xajax();
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_captura_facturas_list.php");
	
/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . "../clases/phpExcel_1.7.8/Classes/");
require_once('PHPExcel.php');
require_once('PHPExcel/Reader/Excel2007.php');

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Facturas</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblImportarArchivo').style.display = 'none';
		
		if (verTabla == "tblImportarArchivo") {
			if (validarCampo('lstEmpresa','t','lista') == true) {
				document.forms['frmImportarArchivo'].reset();
				byId('hddUrlArchivo').value = '';
				
				byId('fleUrlArchivo').className = 'inputHabilitado';
				
				xajax_formImportarFactura(xajax.getFormValues('frmImportarArchivo'));
			} else {
				validarCampo('lstEmpresa','t','lista');
				
				alert('Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido');
				return false;
			}
			
			tituloDiv1 = 'Importar Cuentas por Cobrar';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblImportarArchivo") {
			byId('fleUrlArchivo').focus();
			byId('fleUrlArchivo').select();
		}
	}
	
	function validarFrmImportarArchivo() {
		if (validarCampo('hddUrlArchivo','t','') == true) {
			xajax_importarFactura(xajax.getFormValues('frmImportarArchivo'), xajax.getFormValues('frmListaFactura'));
		} else {
			validarCampo('hddUrlArchivo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCuentasPorCobrar" colspan="2">Facturas de Clientes</td>
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
                        <button type="button" id="btnNueva" onclick="xajax_nuevaFactura();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr><td><button type="button" onclick="xajax_cargaLstOrientacionPDF();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>PDF</td></tr></table></button></td></tr>
                        <tr><td id="tdlstOrientacionPDF"></td></tr>
                        </table>
                    </td>
                    <td>
                        <button type="button" onclick="xajax_exportarFactura(xajax.getFormValues('frmBuscar'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    </td>
                    <td>
                    <a class="modalImg" id="aImportar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblImportarArchivo');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel_import.png"/></td><td>&nbsp;</td><td>Importar</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo">Empresa:</td>
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
                	<td align="right" class="tituloCampo">Filtrar por Fecha:</td>
                    <td id="tdlstTipoFecha"></td>
                    <td align="right" class="tituloCampo">Vendedor:</td>
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
					<td align="right" class="tituloCampo">Tipo Pago:</td>
                    <td>
                    	<select id="lstTipoPago" name="lstTipoPago" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                        	<option value="0">Crédito</option>
                        	<option value="1">Contado</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo">Ver:</td>
                    <td id="tdlstAnuladaFactura"></td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Estado Factura:</td>
					<td id="tdlstEstadoFactura"></td>
					<td align="right" class="tituloCampo" width="120">Módulo:</td>
					<td id="tdlstModulo"></td>
					<td align="right" class="tituloCampo" width="120">Tipo de Orden:</td>
					<td id="tdlstTipoOrden"></td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo">Item Facturado:</td>
                    <td id="tdlstItemFactura"></td>
                	<td align="right" class="tituloCampo">Item Pago:</td>
                	<td id="tdlstItemPago"></td>
                    <td align="right" class="tituloCampo">Condición:</td>
                    <td id="tdlstCondicionBuscar"></td>
				</tr>
				<tr align="left">
                	<td></td>
                	<td></td>
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
            <form id="frmListaFactura" name="frmListaFactura" style="margin:0">
                <div id="divListaFactura" style="width:100%">
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
							<td><img src="../img/iconos/pencil.png"/></td><td>Editar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_view.png"/></td><td>Ver</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Factura Venta PDF</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/print.png"/></td><td>Recibo(s) de Pago(s)</td>
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

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form action="controladores/ac_upload_file_tmp.php" enctype="multipart/form-data" id="frmImportarArchivo" name="frmImportarArchivo" method="post" style="margin:0" target="iframeUpload">
    <table border="0" id="tblImportarArchivo" width="960">
    <tr align="left">
        <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Archivo de Excel:</td>
        <td width="85%">
            <input type="file" id="fleUrlArchivo" name="fleUrlArchivo" class="inputHabilitado" onchange="javascript:submit();" size="100"/>
            <iframe name="iframeUpload" style="display:none"></iframe>
            <input type="hidden" id="hddUrlArchivo" name="hddUrlArchivo" readonly="readonly"/>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table width="100%">
                    <tr>
                        <td>El Formato del Archivo Excel a Importar debe ser el siguiente (Incluir los nombres de las columnas en la primera fila):</td>
                    </tr>
                    <tr>
                    	<td>
                            <div style="max-height:300px; max-width:920px; overflow:auto; width:100%;">
                                <table width="100%">
                                <tr align="center" class="tituloColumna">
                                	<td width="5%"></td>
                                    <td width="%">Id Empresa</td>
                                    <td width="%">Id Cliente</td>
                                    <td width="%">Número Factura</td>
                                    <td width="%">Número Control</td>
                                    <td width="%">Fecha Registro<div class="textoNegrita_8px">(<?php echo spanDateFormat; ?>)</div></td>
                                    <td width="%">Fecha Vencimiento<div class="textoNegrita_8px">(<?php echo spanDateFormat; ?>)</div></td>
                                    <td width="%">Id Módulo</td>
                                    <td width="%">Id Empleado</td>
                                    <td width="%">Tipo Pago<div class="textoNegrita_8px">(0 = Credito, 1 = Contado)</div></td>
                                    <td width="%">Estado Factura<div class="textoNegrita_8px">(0 = No Cancelado, 1 = Cancelado, 2 = Cancelado Parcial)</div></td>
                                    <td width="%">Total Factura</td>
                                    <td width="%">Saldo Factura</td>
                                    <td width="%">Observación</td>
                                    <td width="%">Aplica Libro<div class="textoNegrita_8px">(0 = No, 1 = Si)</div></td>
                                </tr>
                				<tr id="trItmPie"></tr>
                                </table>
							</div>
						</td>
					</tr>
                    </table>
                </td>
            </tr>
            </table>
            <div id="divMsjImportar"></div>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
            <button type="submit" id="btnGuardarImportarArchivo" name="btnGuardarImportarArchivo" onclick="validarFrmImportarArchivo();">Aceptar</button>
            <button type="button" id="btnCancelarImportarArchivo" name="btnCancelarImportarArchivo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('txtFechaDesde').className = 'inputHabilitado';
byId('txtFechaHasta').className = 'inputHabilitado';
byId('lstAplicaLibro').className = 'inputHabilitado';
byId('lstTipoPago').className = 'inputHabilitado';
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
		cellColorScheme:"purple"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"purple"
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

//var lstEmpleado = $.map($("#lstEmpleado option:selected"), function (el, i) { return el.value; });
var lstEstadoFactura = $.map($("#lstEstadoFactura option:selected"), function (el, i) { return el.value; });
//var lstModulo = $.map($("#lstModulo option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal("<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>", "onchange=\"xajax_cargaLstTipoOrden('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>'); byId('btnBuscar').click();\"");
xajax_cargaLstTipoFecha();
xajax_cargaLstVendedor();
xajax_cargaLstAnuladaFactura();
xajax_cargaLstEstadoFactura();
xajax_cargaLstModulo();
xajax_cargaLstTipoOrden('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstItemPago();
xajax_cargaLstItemFactura();
xajax_cargaLstCondicionBuscar();
xajax_listaFactura(0,'LPAD(CONVERT(numeroControl, SIGNED), 10, 0)','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value +
'||' + byId('lstAplicaLibro').value + '|' + byId('lstTipoPago').value + '|' + lstEstadoFactura.join());

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>