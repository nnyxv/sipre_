<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');

if(!(validaAcceso("ga_devolucion_compras_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}

/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_ga_devolucion_compras_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Compras - Devolución de Facturas de Compra</title>
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
	
    	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
			byId('tblNotaCredito').style.display = 'none';
			
			if (verTabla == "tblNotaCredito") {
				document.forms['frmDcto'].reset();
				
				byId('txtNumeroNotaCredito').className = "inputHabilitado";
				byId('txtNumeroControl').className = "inputHabilitado";
				byId('txtFechaProveedor').className = "inputHabilitado";
				byId('lstAplicaLibro').className = "inputHabilitado";
				byId('lstActivo').className = "inputHabilitado";
				byId('txtObservacionNotaCredito').className = "inputHabilitado";
				
				xajax_formNotaCredito(valor, valor2);
				
				tituloDiv1 = 'Devolución de Compra';
			}
			
			byId(verTabla).style.display = '';
			openImg(nomObjeto);
			byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
			
			if (verTabla == "tblNotaCredito") {
				byId('txtNumeroNotaCredito').focus();
				byId('txtNumeroNotaCredito').select();
			}	
		}
		
		function asignarAplicaLibro(valor) {
			switch(valor){
				case "0":
					byId('txtNumeroNotaCredito').className = "inputInicial";
					byId('txtNumeroControl').className = "inputInicial";
					
					byId('txtNumeroNotaCredito').readOnly = true;
					byId('txtNumeroControl').readOnly = true;
					
					byId('txtNumeroNotaCredito').value = '';
					byId('txtNumeroControl').value = '';
						break;
				case "1":
					byId('txtNumeroNotaCredito').className = "inputHabilitado";
					byId('txtNumeroControl').className = "inputHabilitado";
					
					byId('txtNumeroNotaCredito').readOnly = false;
					byId('txtNumeroControl').readOnly = false;
						break;
			}
		}
		
		function validarFrmDcto() {
			error = false;
			if (!(validarCampo('txtFechaRegistroNotaCredito','t','fecha') == true
				&& validarCampo('txtFechaProveedor','t','fecha') == true
				&& validarCampo('lstTipoClave','t','lista') == true
				&& validarCampo('lstClaveMovimientoNotaCredito','t','lista') == true
				&& validarCampo('lstAplicaLibro','t','listaExceptCero') == true
				&& validarCampo('lstActivo','','listaExceptCero') == true)) {
				
					validarCampo('txtFechaRegistroNotaCredito','t','fecha');
					validarCampo('txtFechaProveedor','t','fecha');
					validarCampo('lstTipoClave','t','lista');
					validarCampo('lstClaveMovimientoNotaCredito','t','lista');
					validarCampo('lstAplicaLibro','t','listaExceptCero');
					validarCampo('lstActivo','','listaExceptCero');
					
					error = true;
			}
			if (byId('lstAplicaLibro').value == 1) {
				if (!(validarCampo('txtNumeroNotaCredito','t','') == true
						&& validarCampo('txtNumeroControl','t','numeroControl') == true)) {
							validarCampo('txtNumeroNotaCredito','t','');
							validarCampo('txtNumeroControl','t','numeroControl');
							
							error = true;
					}
			}
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				if (confirm('¿Seguro desea registrar la devolución?') == true) {
					byId('btnGuardarDcto').disabled = 'disabled';
					byId('btnCancelarDcto').disabled = 'disabled';
					
					xajax_guardarDcto(xajax.getFormValues('frmDcto'),xajax.getFormValues('frmBuscar'));
				}
			}
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
        	<td class="tituloPaginaCompras">Devolución de Facturas de Compra </td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    	<button type="button" onclick="xajax_encabezadoEmpresa(byId('lstEmpresa').value); window.print();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                    </td>
                </tr>
                </table>
				
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
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
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Criterio:</td>
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
                            <td><img src="../img/iconos/ico_compras.gif" /></td><td>Facturas de Compras</td>
                        	<td>&nbsp;</td>
                            <td><img src="../img/iconos/aprob_control_calidad.png" /></td><td>Compra Registrada</td>
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
                            <td><img src="../img/iconos/arrow_rotate_clockwise.png"/></td><td>Devolucion de Factura de Compra</td>
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
<!--FORMULARIO DE PARA DEVOLUCION-->
<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
    <div id="divFlotanteTitulo1" class="handle">
        <table>
            <tr><td id="tdFlotanteTitulo1" width="100%"></td></tr>
        </table>
    </div>

    <form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
        <table id="tblNotaCredito" width="960px" border="0">
          <tr align="left">
            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
            <td colspan="3">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                    </tr>
                </table>
            </td>
          </tr>
          <tr>
            <td align="right" class="tituloCampo">Razón Social:</td>
            <td>
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                    </tr>
                </table>
            </td>
            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Registro:</td>
            <td><input type="text" id="txtFechaRegistroNotaCredito" name="txtFechaRegistroNotaCredito" readonly="readonly" size="10" style="text-align:center"/></td>
          </tr>
          <tr>
            <td colspan="2" valign="top">
            	<fieldset>
                	<legend class="legend">Datos de la Devolución</legend>
                	<table border="0" width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="22%"><span class="textoRojoNegrita">*</span>Nro. Nota Crédito:
                            <br>
                            <span class="textoNegrita_10px">(Proveedor)</span>
                        </td>
                        <td width="26%"><input type="text" id="txtNumeroNotaCredito" name="txtNumeroNotaCredito" size="20" style="text-align:center;"/></td>
                        <td align="right" class="tituloCampo" width="22%"><span class="textoRojoNegrita">*</span>Nro. Control:
                            <br>
                            <span class="textoNegrita_10px">(Proveedor)</span>
                        </td>
                        <td width="30%">
                        <div style="float:left">
                            <input type="text" id="txtNumeroControl" name="txtNumeroControl" size="20" style="text-align:center"/>&nbsp;
                        </div>
                        <div style="float:left">
                            <img src="../img/iconos/information.png" title="Formato Ej.: 00-000000 / Máquinas Fiscales"/>
                        </div>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Nota Crédito:
                            <br>
                            <span class="textoNegrita_10px">(Proveedor)</span>
                        </td>
                        <td><input type="text" id="txtFechaProveedor" name="txtFechaProveedor" size="10" style="text-align:center"/></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Aplica a Libro:</td>
                        <td>
                            <select id="lstAplicaLibro" name="lstAplicaLibro" onchange="asignarAplicaLibro(this.value)">
                                <option value="-1">[ Seleccione ]</option>
                                <option value="0">NO</option>
                                <option value="1">SI</option>
                            </select>
                        </td>
                        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Anular Registro de Compra:</td>
                        <td>
                            <select id="lstActivo" name="lstActivo">
                                <option value="-1">[ Seleccione ]</option>
                                <option value="0">NO</option>
                                <option value="">SI</option>
                            </select>
                        </td>
                    </tr>
                    </table>
                </fieldset>
                <table border="0" width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                        <td width="22%">
                            <select id="lstTipoClave" name="lstTipoClave" onchange="selectedOption(this.id,4); xajax_cargaLstClaveMovimiento(this.value);">
                                <option value="-1">[ Seleccione ]</option>
                                <option value="1">1.- COMPRA</option>
                                <option value="2">2.- ENTRADA</option>
                                <option value="3">3.- VENTA</option>
                                <option value="4" selected="selected">4.- SALIDA</option>
                            </select>
                        </td>
                        <td align="right" class="tituloCampo" width="20%">
                        	<span class="textoRojoNegrita">*</span>Clave Mov.:
                        </td>
                        <td id="tdlstClaveMovimientoNotaCredito" width="38%"></td>
                    </tr>
                </table>
            </td>
            <td colspan="2" valign="top">
            	<fieldset>
                	<legend class="legend">Datos de la Factura de Compra</legend>
                    
                    <table border="0" width="100%">
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="38%">Nro. Factura:</td>
                        <td width="62%"><input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="20" style="text-align:center"/></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Nro. Control:</td>
                        <td><input type="text" id="txtNumeroControlFactura" name="txtNumeroControlFactura" readonly="readonly" size="20" style="text-align:center"/></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Fecha Registro:</td>
                        <td><input type="text" id="txtFechaRegistroFactura" name="txtFechaRegistroFactura" readonly="readonly" size="10" style="text-align:center"/></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Fecha Emisión:
                            <br>
                            <span class="textoNegrita_10px">(Proveedor)</span>
						</td>
                        <td><input type="text" id="txtFechaProveedorFactura" name="txtFechaProveedorFactura" readonly="readonly" size="10" style="text-align:center"/></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Tipo Mov.:</td>
                        <td>
                        	<input type="text" id="txtTipoClaveFactura" name="txtTipoClaveFactura" readonly="readonly" size="26"/>
                            <input type="hidden" id="hhdTipoClaveFactura" name="hhdTipoClaveFactura" readonly="readonly" size="26"/>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Clave Mov.:</td>
                        <td>
                        	<input type="hidden" id="hddIdClaveMovimiento" name="hddIdClaveMovimiento" readonly="readonly"/>
                            <input type="text" id="txtClaveMovimiento" name="txtClaveMovimiento" readonly="readonly" size="26"/>
                        </td>
                    </tr>
                    <tr align="right" class="trResaltarTotal">
                        <td class="tituloCampo">Total Registro Compra:</td>
                        <td><input type="text" id="txtTotalFacturaCompra" name="txtTotalFacturaCompra" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
					</tr>
                    </table>
                </fieldset>
            </td>
          </tr>
          <tr>
            <td colspan="4">
            	<div id="divListaDetalleFactura" style="max-height:300px; overflow:auto; width:100%;"></div>
            </td>
          </tr>
          <tr>
            <td colspan="4">
                <table border="0" width="100%">
                    <tr>
                        <td valign="top" width="50%">
                            <table>
                                <tr align="left">
                                    <td class="tituloCampo">Observación:</td>
                                </tr>
                                <tr align="left">
                                    <td><textarea id="txtObservacionNotaCredito" name="txtObservacionNotaCredito" cols="55" rows="3"></textarea></td>
                                </tr>
                            </table>
                        </td>
                        <td valign="top" width="50%">
                            <table border="0" width="100%">
                                <tr id="trNetoOrden" align="right" class="trResaltarTotal">
                                    <td class="tituloCampo" width="36%">Total Devolución Compra:</td>
                                    <td width="24%"></td>
                                    <td width="13%"></td>
                                    <td id="tdTotalRegistroMoneda" width="5%"></td>
                                    <td width="22%"><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>            	
            </td>
          </tr>
          <tr>
            <td colspan="4" align="right"><hr>
        	<input type="hidden" id="txtHiddIdFactura" name="txtHiddIdFactura"/>
             <!-- <button type="hidden" id="btnGuardarDcto" name="btnGuardarDcto" onclick="reconversionMonetaria();"><img width="11px" src="../img/iconos/ico_cambio.png" >Reconversion</button> -->
            <button type="submit" id="btnGuardarDcto" name="btnGuardarDcto" onclick="validarFrmDcto();">Aceptar</button>
            <button type="button" id="btnCancelarDcto" name="btnCancelarDcto" class="close">Cerrar</button>
            </td>
          </tr>
        </table>
    </form>
</div>

<script>
byId('txtFechaDesde').className = "inputHabilitado";
byId('txtFechaHasta').className = "inputHabilitado";
byId('txtCriterio').className = "inputHabilitado";

byId('txtFechaDesde').value = "<?php echo date(spanDateFormat, strtotime(date("01-m-Y"))); ?>";
byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaProveedor").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen",
});

new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen",
});

new JsDatePick({
	useMode:2,
	target:"txtFechaProveedor",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"armygreen",
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaRegistroCompra(0,'id_factura','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('txtFechaDesde').value + '|' + byId('txtFechaHasta').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

</script>


<script type="text/javascript">
    
    function alerta(){
        alert(document.getElementById('txtHiddIdFactura').value);
    }
</script>


<script type="text/javascript">
    
    function reconversionMonetaria(){

      
        var idFacturareconversion = document.getElementById('txtHiddIdFactura').value;
        var confirmacion = confirm("¿Desea realizar la reconversión de la factura ? Esta acción no se puede revertir");

        if (confirmacion == true){
            mensaje = xajax_reconversion(idFacturareconversion);

        }
}

</script>




               