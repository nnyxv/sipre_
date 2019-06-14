<?php
require_once("../connections/conex.php");

session_start();

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_devolucion_venta_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Nota de Crédito de Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblArticulo').style.display = 'none';
		
		if (verTabla == "tblArticulo") {
			xajax_asignarArticulo(valor, xajax.getFormValues('frmListaArticulo'));
			
			tituloDiv1 = 'Editar Artículo';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblArticulo") {
			byId('txtCantidadRecibArt').focus();
			byId('txtCantidadRecibArt').select();
		}
	}
	
	function validarFrmDatosArticulo() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadRecibArt','t','cantidad') == true) {
			xajax_editarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadRecibArt','t','cantidad');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDcto() {
		if (validarCampo('txtIdPedido','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtTotalOrden','t','monto') == true) {
			if (byId('hddObj').value.length > 0) {
				if (confirm('¿Seguro desea guardar la Aprobación de la Nota de Crédito?') == true) {
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
				}
			} else {
				alert("Debe agregar articulos a la Aprobación de la Nota de Crédito");
				return false;
			}
		} else {
			validarCampo('txtIdPedido','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtTotalOrden','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Nota de Crédito de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmDcto" name="frmDcto" style="margin:0">
            	<table border="0" width="100%">
                <tr>
                	<td colspan="2">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="58%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td></td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo" width="12%">Nro. Nota Crédito:</td>
                            <td width="18%">
                                <input type="hidden" id="txtIdNotaCredito" name="txtIdNotaCredito" readonly="readonly" size="20"/>
                                <input type="hidden" id="hddIdMoneda" name="hddIdMoneda" readonly="readonly" size="20"/>
                                <input type="text" id="txtNumeroNotaCredito" name="txtNumeroNotaCredito" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"></td>
                            <td align="right" class="tituloCampo">Fecha Registro:</td>
                            <td>
                                <input type="text" id="txtFechaNotaCredito" name="txtFechaNotaCredito" readonly="readonly" size="10" style="text-align:center"/>
                            </td>
                        </tr>
                        </table>
					</td>
				</tr>
                <tr>
                	<td valign="top" width="70%">
                    <fieldset><legend class="legend">Cliente</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                            <td width="46%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td></td>
                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                </tr>
                                <tr align="center">
                                    <td id="tdMsjCliente" colspan="3"></td>
                                </tr>
                                </table>
                                <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
                            </td>
                            <td align="right" class="tituloCampo" width="16%"><?php echo $spanClienteCxC; ?>:</td>
                            <td width="22%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Dirección:</td>
                            <td rowspan="3"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="55" readonly="readonly" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Teléfono:</td>
                            <td><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Otro Teléfono:</td>
                            <td><input type="text" id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Días Crédito:</td>
                            <td><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    	
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                            <td width="16%">
                                <select id="lstTipoClave" name="lstTipoClave">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">1.- COMPRA</option>
                                    <option value="2" selected="selected">2.- ENTRADA</option>
                                    <option value="3">3.- VENTA</option>
                                    <option value="4">4.- SALIDA</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td id="tdlstClaveMovimiento" width="28%">
                                <select id="lstClaveMovimiento" name="lstClaveMovimiento">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td width="12%"></td>
                            <td width="20%"></td>
                        </tr>
                        </table>
                    </td>
                    <td valign="top" width="30%">
                    <fieldset><legend class="legend">Datos de la Factura</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Nro. Factura:</td>
                            <td width="60%">
                                <input type="hidden" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="20"/>
                                <input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Control:</td>
                            <td><input type="text" id="txtNumeroControlFactura" name="txtNumeroControlFactura" readonly="readonly" size="20" style="color:#F00; font-weight:bold; text-align:center;"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fecha Emisión:</td>
                            <td><input type="text" id="txtFechaFactura" name="txtFechaFactura" readonly="readonly" size="10" style="text-align:center"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fecha Venc.:</td>
                            <td><input type="text" id="txtFechaVencimientoFactura" name="txtFechaVencimientoFactura" readonly="readonly" size="10" style="text-align:center"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Pedido:</td>
                            <td>
                                <input type="hidden" id="hddFechaPedido" name="hddFechaPedido" readonly="readonly" size="10"/>
                                <input type="hidden" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="20" style="text-align:center"/>
                            	<input type="text" id="txtNumeroPedidoPropio" name="txtNumeroPedidoPropio" readonly="readonly" size="20" style="text-align:center"/>
							</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Vendedor:</td>
                            <td>
                                <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/>
                                <input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="26"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Tipo Mov.:</td>
                            <td><input type="text" id="txtTipoClaveFactura" name="txtTipoClaveFactura" readonly="readonly" size="26"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Clave Mov.:</td>
                            <td>
                                <input type="hidden" id="hddIdClaveMovimiento" name="hddIdClaveMovimiento" readonly="readonly"/>
                                <input type="text" id="txtClaveMovimiento" name="txtClaveMovimiento" readonly="readonly" size="26"/>
                            </td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Tipo de Pago:</td>
                            <td>
                                <input type="hidden" id="hddTipoPago" name="hddTipoPago" readonly="readonly"/>
                                <input type="text" id="txtTipoPago" name="txtTipoPago" class="divMsjInfo2" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                        <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" style="cursor:default" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
					</td>
				</tr>
                </table>
                
                <table align="right" cellpadding="0" cellspacing="0" class="divMsjInfo2" width="200">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td class="trResaltar6" style="border:1px solid #000000">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                            <td>Articulo Sin Ubicación</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                    <td width="4%">Nro.</td>
                	<td></td>
                    <td width="14%">Código</td>
                    <td width="42%">Descripción</td>
                    <td width="6%">Cant.</td>
                    <td width="6%">Dev.</td>
                    <td width="6%">Pend.</td>
                    <td width="8%"><?php echo $spanPrecioUnitario; ?></td>
                    <td width="4%">% Impuesto</td>
                    <td width="10%">Total</td>
                </tr>
                <tr id="trItmPieDetalle"></tr>
                </table>
			</form>
            </td>
		</tr>
        <tr>
        	<td>
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                <table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    	<div id="tdGastos" width="100%"></div>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td class="tituloCampo">Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacionNotaCredito" name="txtObservacionNotaCredito" rows="3" style="width:99%"></textarea></td>
                        </tr>
                        </table>
                    </td>
                    <td valign="top" width="50%">
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="36%">Subtotal:</td>
                            <td style="border-top:1px solid;" width="24%"></td>
                            <td style="border-top:1px solid;" width="13%"></td>
                            <td style="border-top:1px solid;" id="tdSubTotalMoneda" width="5%"></td>
                            <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td nowrap="nowrap">
                            	<input type="radio" id="rbtInicialPorc" name="rbtInicial" onclick="byId('txtDescuento').readOnly = false; byId('txtSubTotalDescuento').readOnly = true;" style="display:none" value="1">
                                
                                <input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="6" style="text-align:right"/>%
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td>
                            	<input type="radio" id="rbtInicialMonto" name="rbtInicial" onclick="byId('txtDescuento').readOnly = true; byId('txtSubTotalDescuento').readOnly = false;" style="display:none" value="2">
                                
                            	<input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/>
							</td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Gastos Con Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoConIvaMoneda"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IVA-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Gastos Sin Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoSinIvaMoneda"></td>
                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trNetoOrden" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalRegistroMoneda"></td>
                            <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="5" style="border-top:1px solid;"></td>
                        </tr>
                        <tr align="right">
                        	<td class="tituloCampo">Exento:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExentoMoneda"></td>
                            <td><input type="text" id="txtTotalExento" name="txtTotalExento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                        	<td class="tituloCampo">Exonerado:</td>
                            <td></td>
                            <td></td>
                            <td id="tdExoneradoMoneda"></td>
                            <td><input type="text" id="txtTotalExonerado" name="txtTotalExonerado" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
					</td>
				</tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td align="right"><hr>
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('iv_devolucion_venta_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
	
<form id="frmDatosArticulo" name="frmDatosArticulo" onsubmit="return false;" style="margin:0">
    <input type="hidden" id="hddNumeroArt" name="hddNumeroArt" />
    <table border="0" id="tblArticulo" width="960">
    <tr>
    	<td>
        <fieldset>
        	<table border="0" width="100%">
            <tr>
                <td width="10%"></td>
                <td width="30%"></td>
                <td width="38%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                <td>
                    <input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/>
                    <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
                </td>
                <td rowspan="3" valign="top"><textarea id="txtDescripcionArt" name="txtDescripcionArt" cols="50" rows="3" readonly="readonly"></textarea></td>
                <td align="right" class="tituloCampo">Ult. Compra:</td>
                <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" readonly="readonly" size="38"/></td>
                <td align="right" class="tituloCampo">Ult. Venta:</td>
                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Tipo Artículo:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" readonly="readonly" size="25"/></td>
                <td align="right" class="tituloCampo">Unid. Disponible:</td>
                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right"/></td>
            </tr>
			</table>
		</fieldset>
            
            <table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
                <td width="28%"></td>
                <td width="10%"></td>
                <td width="52%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Cantidad Facturada:</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" onkeypress="return validarSoloNumeros(event);" readonly="readonly" size="10" style="text-align:right"/></td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
                    </tr>
					</table>
                </td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad Devuelta:</td>
                <td><input type="text" id="txtCantidadRecibArt" name="txtCantidadRecibArt" class="inputHabilitado" size="10" style="text-align:right"/></td>
			</tr>
            </table>
		</td>
	</tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarArticulo" name="btnGuardarArticulo" onclick="validarFrmDatosArticulo();">Aceptar</button>
            <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
byId('txtObservacionNotaCredito').className = 'inputHabilitado';

<?php if (isset($_GET['id'])) { ?>
	xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
<?php } ?>

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