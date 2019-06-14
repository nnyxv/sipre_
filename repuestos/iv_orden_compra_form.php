<?php
require_once("../connections/conex.php");
set_time_limit(0);
ini_set('memory_limit', '-1');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_orden_compra_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_orden_compra_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Orden de Compra</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <!--<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblDesaprobarDcto').style.display = 'none';
		byId('tblArticulo').style.display = 'none';
		byId('tblCliente').style.display = 'none';
		
		if (verTabla == "tblDesaprobarDcto") {
			document.forms['frmDesaprobarDcto'].reset();
			
			tituloDiv1 = 'Desaprobar Documento';
		} else if (verTabla == "tblArticulo") {
			byId('txtCodigoArt').className = 'inputInicial';   
			byId('txtCantidadArt').className = 'inputHabilitado';
			byId('txtCostoArt').className = 'inputHabilitado';
			
			xajax_asignarArticulo(valor, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'))
			
			tituloDiv1 = 'Editar Artículo';
		} else if (verTabla == "tblCliente") {
			xajax_formDatosCliente(valor, xajax.getFormValues('frmDcto'));
			
			tituloDiv1 = 'Datos de Cliente';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblDesaprobarDcto") {
			byId('lstEstatusPedido').focus();
		} else if (verTabla == "tblArticulo") {
			byId('txtCantidadArt').focus();
			byId('txtCantidadArt').select();
		}
	}
	
	function buscarEnColumna(valor, buscarEnColumna) {
		var frm = document.forms['frmListaArticulo'];
		for (i = 0; i < frm.length; i++){
			if (frm.elements[i].id == "cbx") {
				indice = frm.elements[i].value;
				
				byId('trItm:' + indice).style.display = '';
				if (buscarEnColumna == 'porcentaje_grupo'
				&& byId('lstTarifaAdValorem' + indice).value != valor && valor != "") {
					byId('trItm:' + indice).style.display = 'none';
				}
			}
		}
	}
	
	function validarFrmDatosArticulo() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadArt','t','') == true
		&& validarCampo('txtCostoArt','t','') == true
		&& validarCampo('lstIvaArt','t','listaExceptCero') == true) {
			if (byId('rbtTipoArtCliente').checked == true
			&& validarCampo('txtNombreClienteArt','t','') != true) {
				alert("Los campos señalados en rojo son requeridos");
				return false;
			} else
				xajax_editarArticulo(xajax.getFormValues('frmArticulo'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadArt','t','');
			validarCampo('txtCostoArt','t','');
			validarCampo('lstIvaArt','t','listaExceptCero');
			
			if (byId('rbtTipoArtCliente').checked == true)
				validarCampo('txtNombreClienteArt','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDesaprobarDcto() {
		if (validarCampo('lstEstatusPedido','t','listaExceptCero') == true) {
			if (confirm('¿Seguro desea desaprobar el Pedido?') == true) {
				xajax_desaprobarDcto(xajax.getFormValues('frmDesaprobarDcto'), xajax.getFormValues('frmDcto'));
			}
		} else {
			validarCampo('lstEstatusPedido','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtFechaOrdenCompra','t','fecha') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('lstContacto','t','lista') == true
		&& validarCampo('lstRespRecepcion','t','lista') == true
		&& validarCampo('txtFechaEntrega','t','fecha') == true
		&& validarCampo('lstTipoTransporte','t','lista') == true)) {
			validarCampo('txtFechaOrdenCompra','t','fecha');
			validarCampo('lstMoneda','t','lista');
			validarCampo('lstContacto','t','lista');
			validarCampo('lstRespRecepcion','t','lista');
			validarCampo('txtFechaEntrega','t','fecha');
			validarCampo('lstTipoTransporte','t','lista');
			
			error = true;
		}
		
		if (!(byId('lstTasaCambio') == undefined)) {
			if (!(validarCampo('lstTasaCambio','','listaExceptCero') == true)) {
				validarCampo('lstTasaCambio','','listaExceptCero');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea aprobar la Orden?') == true) {
				xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			}
		}
	}
	</script>
</head>

<body class="bodyVehiculos" onload="xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Orden de Compra</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
        		<table width="100%">
                <tr>
                    <td align="left">
                        <table border="0" width="100%">
                        <tr>
                            <td align="left" rowspan="3" width="70%"></td>
                            <td align="right" class="tituloCampo" width="12%">Id Orden:</td>
                            <td width="18%"><input type="text" id="txtIdOrdenCompra" name="txtIdOrdenCompra" readonly="readonly" size="20" style="text-align:center"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td><input type="text" id="txtFechaOrdenCompra" name="txtFechaOrdenCompra" size="10" style="text-align:center;"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Id Pedido:</td>
                            <td><input type="text" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="20" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" width="100%">
                        <tr>
                            <td class="tituloArea" colspan="6">Datos del Proveedor</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Razón Social:</td>
                            <td colspan="3">
                            	<table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdProv" name="txtIdProv" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td>&nbsp;</td>
                                	<td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
								</tr>
                                </table>
							</td>
                            <td align="right" class="tituloCampo"><?php echo $spanProvCxP; ?>:</td>
                            <td><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%">Persona Contacto:</td>
                            <td width="24%"><input type="text" id="txtContactoProv" name="txtContactoProv" readonly="readonly" size="26"/></td>
                            <td align="right" class="tituloCampo" width="12%">Cargo:</td>
                            <td width="20%"><input type="text" id="txtCargoContactoProv" name="txtCargoContactoProv" readonly="readonly" size="26"/></td>
                            <td align="right" class="tituloCampo" width="12%">Email:</td>
                            <td width="20%"><input type="text" id="txtEmailContactoProv" name="txtEmailContactoProv" readonly="readonly" size="26"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                            <td colspan="3" rowspan="2"><textarea id="txtDireccionProv" name="txtDireccionProv" cols="60" readonly="readonly" rows="2"></textarea></td>
                            <td align="right" class="tituloCampo">Teléfono:</td>
                            <td><input type="text" id="txtTelefonoProv" name="txtTelefonoProv" readonly="readonly" size="18" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fax:</td>
                            <td><input type="text" id="txtFaxProv" name="txtFaxProv" readonly="readonly" size="12" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                            <td colspan="3">
                            	<table cellpadding="0" cellspacing="0">
                                <tr align="left">
                                    <td id="tdlstMoneda">
                                        <select id="lstMoneda" name="lstMoneda">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                    <td id="tdlstTasaCambio"></td>
                                    <td>
                                        <input type="text" id="txtTasaCambio" name="txtTasaCambio" readonly="readonly" size="16" style="text-align:right"/>
                                        <input type="hidden" id="hddIdMoneda" name="hddIdMoneda" readonly="readonly"/>
                                        <input type="hidden" id="hddIncluirImpuestos" name="hddIncluirImpuestos" readonly="readonly"/>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" width="100%">
                        <tr>
                            <td class="tituloArea" colspan="6">Datos de la Compra</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Factura a Nombre:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td>&nbsp;</td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
							</td>
                            <td align="right" class="tituloCampo"><?php echo $spanProvCxP; ?>:</td>
                            <td><input type="text" id="txtRif" name="txtRif" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Dirección:</td>
                            <td colspan="3"><textarea cols="60" id="txtDireccion" name="txtDireccion" readonly="readonly" rows="2"></textarea></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                            <td>
                            	<label><input type="radio" id="rbtTipoPagoCredito" name="rbtTipoPago" value="0" checked="checked"/> Crédito</label>
                                <label><input type="radio" id="rbtTipoPagoContado" name="rbtTipoPago" value="1"/> Contado</label>
							</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Contacto:</td>
                            <td id="tdlstContacto" width="24%">
                                <select id="lstContacto" name="lstContacto">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="12%">Cargo:</td>
                            <td width="20%"><input type="text" id="txtCargo" name="txtCargo" readonly="readonly" size="26"/></td>
                            <td align="right" class="tituloCampo" width="12%">Email:</td>
                            <td width="20%"><input type="text" id="txtEmail" name="txtEmail" readonly="readonly" size="26"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Resp. Recepción:</td>
                            <td id="tdlstRespRecepcion">
                                <select id="lstRespRecepcion" name="lstRespRecepcion">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Entrega:</td>
                            <td><input type="text" id="txtFechaEntrega" name="txtFechaEntrega" size="10" style="text-align:center;"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Transporte:</td>
                            <td>
                                <select id="lstTipoTransporte" name="lstTipoTransporte" style="width:150px">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">Propio</option>
                                    <option value="2">Terceros</option>
                                </select>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
    		</form>
            </td>
		</tr>
        <tr>
        	<td>
            <form id="frmListaArticulo" name="frmListaArticulo" onsubmit="return false;" style="margin:0">
                <table border="0" class="texto_9px" width="100%">
                <tr align="center" class="tituloColumna">
                	<td rowspan="2"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaArticulo');"/></td>
                	<td rowspan="2" width="4%">Nro.</td>
                	<td rowspan="2"></td>
                	<td rowspan="2"></td>
                    <td rowspan="2" width="14%">Código</td>
                    <td rowspan="2" width="40%">Descripción</td>
                    <td rowspan="2" width="6%">Ped.</td>
                    <td rowspan="2" width="6%">Recib.</td>
                    <td rowspan="2" width="6%">Pend.</td>
                    <td rowspan="2" width="8%">Costo Unit.</td>
                    <td rowspan="2" width="4%">% Impuesto</td>
                    <td width="4%">% ADV</td>
                    <td rowspan="2" width="8%">Total</td>
                </tr>
                <tr align="center" class="tituloColumna">
                    <td id="tdlstArancelGrupoBuscar"></td>
                </tr>
                <tr id="trItmPie"></tr>
				</table>
			</form>
            </td>
		</tr>
        <tr>
        	<td align="right">
            <form id="frmTotalDcto" name="frmTotalDcto" onsubmit="return false;" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
            	<table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    <fieldset id="fieldsetGastos"><legend class="legend">Gastos</legend>
                    	<table border="0" width="100%">
                        <tr id="trAgregarGasto" align="left">
                        	<td colspan="7">
                                <a class="modalImg" id="aAgregarGasto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaGasto');">
                                    <button type="button" title="Agregar Gastos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                    <button type="button" id="btnQuitarGasto" name="btnQuitarGasto" onclick="xajax_eliminarGasto(xajax.getFormValues('frmTotalDcto'));" title="Quitar Gastos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr id="trGastoItem" align="left" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmGasto" onclick="selecAllChecks(this.checked,this.id,'frmTotalDcto');"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Gasto Manual por Item:</td>
                            <td colspan="5">
                            <div style="float:left">
                            	<select id="lstGastoItem" name="lstGastoItem" onchange="xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">
                                	<option value="-1">[ Seleccione ]</option>
                                	<option value="0">No</option>
                                	<option value="1">Si</option>
                                </select>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="No: Realiza el prorrateo automático entre los items agregados &#10;Si: Permitirá ingresar manualmente el gasto correspondiente a cada item agregado y los totales de los gastos deberán coincidir"/>
                            </div>
                            </td>
                        </tr>
                        <tr id="trItmPieGasto" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="4">Total Gastos:</td>
                            <td><input type="text" id="txtTotalGasto" name="txtTotalGasto" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                        	<td></td>
                        	<td width="26%"></td>
                            <td width="14%"></td>
                            <td width="8%"></td>
                            <td width="24%"></td>
                            <td width="14%"></td>
                            <td width="14%"></td>
						</tr>
                        </table>
					</fieldset>
                    	
                    	<table width="100%">
                        <tr align="left">
                            <td class="tituloCampo">Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea></td>
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
                        <tr id="trTotal" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalRegistroMoneda"></td>
                            <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr>
                            <td colspan="5"><hr></td>
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
                <tr>
                	<td valign="top">
                		<table border="0" width="100%">
                        <tr>
                            <td align="center" class="tituloArea" colspan="4">Otros Datos</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="26%">Según Cotización Nro.:</td>
                            <td width="34%"><input type="text" id="txtCotizacion" name="txtCotizacion" size="20" style="text-align:center"/></td>
                        	<td align="right" class="tituloCampo" width="16%">Fecha:</td>
                        	<td width="24%"><input type="text" id="txtFechaCotizacion" name="txtFechaCotizacion" size="10" style="text-align:center;"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Condiciones de Pago:</td>
                            <td colspan="3"><input type="text" id="txtCondicionesPago" name="txtCondicionesPago" size="26"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Son:</td>
                            <td colspan="3"><textarea id="txtMontoEnLetras" name="txtMontoEnLetras" rows="2" readonly="readonly" style="width:99%"></textarea></td>
                        </tr>
                        </table>
					</td>
                    <td valign="top">
                    	<table border="0" width="100%">
                        <tr>
                            <td align="center" class="tituloArea">Aprobación</td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="17%">Preparado:</td>
                                    <td width="46%">
                                    	<table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="hddIdEmpleadoPreparado" name="hddIdEmpleadoPreparado" readonly="readonly" size="6" style="text-align:right"/></td>
                                            <td>&nbsp;</td>
                                            <td><input type="text" id="txtNombreEmpleadoPreparado" name="txtNombreEmpleadoPreparado" readonly="readonly" size="25"/></td>
                                        </tr>
                                        </table>
									</td>
                                    <td align="right" class="tituloCampo" width="17%">Fecha:</td>
                                    <td width="20%"><input type="text" id="txtFechaPreparado" name="txtFechaPreparado" readonly="readonly" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Aprobado:</td>
                                    <td>
                                    	<table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="hddIdEmpleadoAprobado" name="hddIdEmpleadoAprobado" readonly="readonly" size="6" style="text-align:right"/></td>
                                            <td>&nbsp;</td>
                                            <td><input type="text" id="txtNombreEmpleadoAprobado" name="txtNombreEmpleadoAprobado" readonly="readonly" size="25"/></td>
                                        </tr>
                                        </table>
									</td>
                                    <td align="right" class="tituloCampo">Fecha:</td>
                                    <td><input type="text" id="txtFechaAprobado" name="txtFechaAprobado" readonly="readonly" style="text-align:center" size="10"/></td>
                                </tr>
                                </table>
                            </td>
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
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/select.png"/></td><td>&nbsp;</td><td>Aprobar</td></tr></table></button>
			<a class="modalImg" id="aDesaprobar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblDesaprobarDcto');">
                <button type="button" title="Desaprobar"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Desaprobar</td></tr></table></button>
            </a>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('iv_orden_compra_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
            </td>
        </tr>
        </table>
	</div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmDesaprobarDcto" name="frmDesaprobarDcto" onsubmit="return false;" style="margin:0">
    <table id="tblDesaprobarDcto" style="display:none" width="400">
    <tr align="left">
        <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Estatus:</td>
        <td width="70%">
            <select id="lstEstatusPedido" name="lstEstatusPedido" class="inputHabilitado" style="width:200px">
                <option value="-1">[ Seleccione ]</option>
                <option style="background-color:#FFFF00" value="0">Pendiente por Terminar</option>
                <!--<option style="background-color:#009900; color:#FFFFFF" value="1">Pedido</option>-->
                <option style="background-color:#663300; color:#FFFFFF" value="5">Anulada</option>
            </select>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
            <button type="submit" id="btnGuardarDesaprobarDcto" name="btnGuardarDesaprobarDcto" onclick="validarFrmDesaprobarDcto();">Aceptar</button>
            <button type="button" id="btnCancelarDesaprobarDcto" name="btnCancelarDesaprobarDcto" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
    
<form id="frmArticulo" name="frmArticulo" onsubmit="return false;" style="margin:0">
    <input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
    <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" readonly="readonly"/>
    <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
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
                <td><input type="text" id="txtCodigoArt" name="txtCodigoArt" readonly="readonly" size="25"/></td>
                <td rowspan="3" valign="top"><textarea id="txtDescripcionArt" name="txtDescripcionArt" class="inputSinFondo" rows="3" readonly="readonly" style="text-align:left"></textarea></td>
                <td align="right" class="tituloCampo">Ult. Compra:</td>
                <td><input type="text" id="txtFechaUltCompraArt" name="txtFechaUltCompraArt" class="inputSinFondo" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Sección:</td>
                <td><input type="text" id="txtSeccionArt" name="txtSeccionArt" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
                <td align="right" class="tituloCampo">Ult. Venta:</td>
                <td><input type="text" id="txtFechaUltVentaArt" name="txtFechaUltVentaArt" class="inputSinFondo" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Tipo Artículo:</td>
                <td><input type="text" id="txtTipoPiezaArt" name="txtTipoPiezaArt" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
                <td align="right" class="tituloCampo">Unid. Disponible:</td>
                <td><input type="text" id="txtCantDisponible" name="txtCantDisponible" readonly="readonly" size="10" style="text-align:right;"/></td>
            </tr>
			</table>
        </fieldset>
            
            <table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
                <td width="28%"></td>
                <td width="10%"></td>
                <td width="12%"></td>
                <td width="10%"></td>
                <td width="30%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                <td>
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                		<td><input type="text" id="txtCantidadArt" name="txtCantidadArt" maxlength="6" size="12" style="text-align:right;"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
                    </tr>
					</table>
                </td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Costo:</td>
                <td><input type="text" id="txtCostoArt" name="txtCostoArt" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado" size="10" style="text-align:right"/></td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td id="tdlstIvaArt">
                	<select id="lstIvaArt" name="lstIvaArt">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Tipo:</td>
                <td>
                	<label><input type="radio" id="rbtTipoArtReposicion" name="rbtTipoArt" onclick="byId('txtIdClienteArt').value = ''; byId('txtNombreClienteArt').value = ''; byId('btnInsertarClienteArt').style.display = 'none';" value="0" checked="checked"/> Reposicion</label>
                    &nbsp;&nbsp;
                    <label><input type="radio" id="rbtTipoArtCliente" name="rbtTipoArt" onclick="byId('txtIdClienteArt').value = ''; byId('txtNombreClienteArt').value = ''; byId('btnInsertarClienteArt').style.display = '';" value="1" /> Cliente</label>
				</td>
                <td align="right" class="tituloCampo">Nombre:</td>
                <td colspan="3">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdClienteArt" name="txtIdClienteArt" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" id="aInsertarClienteArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaCliente');">
                            <button type="button" id="btnInsertarClienteArt" title="Listar"><img src="../img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNombreClienteArt" name="txtNombreClienteArt" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
				</td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarDatosArticulo" name="btnGuardarDatosArticulo" onclick="validarFrmDatosArticulo();">Aceptar</button>
            <button type="button" id="btnCancelarDatosArticulo" name="btnCancelarDatosArticulo" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
    
    <table border="0" id="tblCliente" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                <td width="55%">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
                <td align="right" class="tituloCampo" width="15%"><?php echo $spanProvCxP; ?>:</td>
                <td width="15%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                <td rowspan="2"><textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly" rows="3"></textarea></td>
                <td align="right" class="tituloCampo">Teléfono:</td>
                <td><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Días Crédito:</td>
                <td><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
			<button type="button" id="btnCancelarCliente" name="btnCancelarCliente" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
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

window.onload = function(){
	jQuery(function($){
		$("#txtFechaOrdenCompra").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaEntrega").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaCotizacion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaOrdenCompra",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaEntrega",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaCotizacion",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
};

xajax_cargaLstArancelGrupoBuscar();
xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>