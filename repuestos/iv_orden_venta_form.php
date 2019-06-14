<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("iv_orden_venta_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_orden_venta_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Aprobación de Pedido</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
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
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblLista').style.display = 'none';
		byId('tblArticulo').style.display = 'none';
		byId('tblDesaprobarDcto').style.display = 'none';
		byId('tblListaGasto').style.display = 'none';
		
		if (verTabla == "tblLista") {
			if (valor == "Gasto") {
				document.forms['frmLista'].reset();
				
				byId('btnGuardarLista').style.display = '';
				
				xajax_formGastosArticulo(xajax.getFormValues('frmListaArticulo'), valor2);
				
				tituloDiv1 = 'Cargos del Artículo';
				byId(verTabla).width = "960";
			}
		} else if (verTabla == "tblArticulo") {
			document.forms['frmDatosArticulo'].reset();
			byId('txtDescripcionArt').innerHTML = '';
			byId('hddIdIvaArt').value = '';
			
			byId('txtCodigoArt').className = 'inputInicial';   
			byId('txtCantidadArt').className = 'inputHabilitado';
			byId('txtPrecioArt').className = 'inputHabilitado';
			
			cerrarVentana = false;
			
			xajax_asignarArticulo(valor, valor2, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'));
			
			tituloDiv1 = 'Editar Artículo';
		} else if (verTabla == "tblDesaprobarDcto") {
			document.forms['frmDesaprobarDcto'].reset();
			
			byId('lstEstatusPedido').className = 'inputHabilitado';
			
			tituloDiv1 = 'Desaprobar Documento';
		} else if (verTabla == "tblListaGasto") {
			document.forms['frmBuscarGasto'].reset();
			
			byId('btnBuscarGasto').click();
			
			tituloDiv1 = 'Gastos / Cargos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			if (valor == "Gasto") {
				if (byId('txtMontoGasto1') != undefined) {
					byId('txtMontoGasto1').focus();
					byId('txtMontoGasto1').select();
				}
			}
		} else if (verTabla == "tblArticulo") {
			byId('txtCantidadArt').focus();
			byId('txtCantidadArt').select();
		} else if (verTabla == "tblDesaprobarDcto") {
			byId('lstEstatusPedido').focus();
		} else if (verTabla == "tblListaGasto") {
			byId('txtCriterioBuscarGasto').focus();
			byId('txtCriterioBuscarGasto').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblPermiso').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv2 = 'Ingreso de Clave Especial';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
	
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		}
	}
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'), xajax.getFormValues('frmDatosArticulo'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDatosArticulo() {
		if (validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadArt','t','cantidad') == true
		&& validarCampo('lstPrecioArt','t','lista') == true
		&& validarCampo('txtPrecioArt','t','monto') == true) {
			xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadArt','t','cantidad');
			validarCampo('lstPrecioArt','t','lista');
			validarCampo('txtPrecioArt','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDcto(accion) {
		if (accion == 0) {
			if (validarCampo('lstEstatusPedido','t','listaExceptCero') == true) {
				if (confirm('¿Seguro desea desaprobar el Pedido?') == true) {
					xajax_desaprobarDcto(xajax.getFormValues('frmDesaprobarDcto'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
				}
			} else {
				validarCampo('lstEstatusPedido','t','listaExceptCero');
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		} else if (accion == 1) {
			if (validarCampo('txtIdPedido','t','') == true
			&& validarCampo('txtNombreEmpleadoAprobado','t','') == true) {
				if (confirm('¿Seguro desea aprobar el Pedido?') == true) {
					byId('aDesaprobar').style.display = 'none';
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');
				}
			} else {
				validarCampo('txtIdPedido','t','');
				validarCampo('txtNombreEmpleadoAprobado','t','');
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		}
	}
	
	function validarInsertarGasto(idGasto) {
		xajax_insertarGasto(idGasto, xajax.getFormValues('frmTotalDcto'));
	}
	</script>
</head>

<body class="bodyVehiculos">
	<script type="text/javascript" language="javascript" src="../js/wz_tooltip/wz_tooltip.js"></script>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Aprobación de Pedido</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
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
                    <td align="right" class="tituloCampo" width="12%">Fecha:</td>
                    <td width="18%"><input type="text" id="txtFechaAprobacion" name="txtFechaAprobacion" readonly="readonly" style="text-align:center" size="10"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empleado:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpleado" name="txtIdEmpleado" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                	<td colspan="4">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="70%">
                            <fieldset><legend class="legend">Cliente</legend>
                                <table width="100%">
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
                                    <td align="right" class="tituloCampo">Crédito:</td>
                                    <td>
                                    	<table border="0" cellspacing="0" width="100%">
                                        <tr>
                                        	<td width="40%">Días:</td>
                                        	<td width="60%"><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        <tr>
                                        <tr>
                                        	<td>Disponible:</td>
                                        	<td><input type="text" id="txtCreditoCliente" name="txtCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        <tr>
                                        </table>
									</td>
                                </tr>
                                </table>
                            </fieldset>

                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                                    <td width="16%"><input type="text" id="txtTipoClave" name="txtTipoClave" readonly="readonly" size="14"/></td>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clave:</td>
                                    <td width="28%"><input type="text" id="txtClaveMovimiento" name="txtClaveMovimiento" readonly="readonly" size="30"/></td>
                                    <td align="right" class="tituloCampo" width="12%">Tipo de Pago:</td>
                                    <td width="20%">
                                        <input type="hidden" id="hddTipoPago" name="hddTipoPago" readonly="readonly"/>
                                        <input type="text" id="txtTipoPago" name="txtTipoPago" class="divMsjInfo2" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Datos del Pedido</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%">Nro. Pedido:</td>
                                    <td width="60%">
                                        <input type="hidden" id="hddSobregiroAprobado" name="hddSobregiroAprobado" readonly="readonly"/>
                                        <input type="hidden" id="txtIdPedido" name="txtIdPedido" readonly="readonly"/>
                                        <input type="text" id="txtNumeroPedidoPropio" name="txtNumeroPedidoPropio" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Fecha:</td>
                                    <td><input type="text" id="txtFechaPedido" name="txtFechaPedido" readonly="readonly" style="text-align:center" size="10"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                                    <td>
                                        <input type="hidden" id="hddIdMoneda" name="hddIdMoneda"/>
                                        <input type="text" id="txtMoneda" name="txtMoneda" readonly="readonly" size="20"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Nro. Referencia:</td>
                                    <td><input type="text" id="txtNumeroReferencia" name="txtNumeroReferencia" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Nro. Presupuesto:</td>
                                    <td>
                                    	<input type="hidden" id="hddIdPresupuestoVenta" name="hddIdPresupuestoVenta" readonly="readonly" size="20" style="text-align:center"/>
                                        <input type="text" id="txtNumeroPresupuestoVenta" name="txtNumeroPresupuestoVenta" readonly="readonly" size="20" style="text-align:center"/>
									</td>
                                </tr>
                                </table>
                            </fieldset>
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
        	<td align="left">
                <button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));" style="cursor:default" title="Eliminar Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaArticulo" name="frmListaArticulo" onsubmit="return false;" style="margin:0">
            	<table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaArticulo');"/></td>
                	<td></td>
                	<td width="4%">Nro.</td>
                    <td width="14%">Código</td>
                    <td width="46%">Descripción</td>
                    <td width="6%">Cantidad</td>
                    <td width="8%">Cargos</td>
                    <td width="8%"><?php echo $spanPrecioUnitario; ?></td>
                    <td width="4%">% Impuesto</td>
                    <td width="10%">Total</td>
                </tr>
                <tr id="trItmPie"></tr>
				</table>
			</form>
            </td>
		</tr>
        <tr>
        	<td>
            <form id="frmTotalDcto" name="frmTotalDcto" onsubmit="return false;" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
            	<table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    <fieldset id="fieldsetGastos"><legend class="legend">Cargos</legend>
                    	<table border="0" width="100%">
                        <tr id="trAgregarGasto" align="left">
                        	<td colspan="7">
                                <a class="modalImg" id="aAgregarGasto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaGasto');">
                                    <button type="button" title="Agregar Cargos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                    <button type="button" id="btnQuitarGasto" name="btnQuitarGasto" onclick="xajax_eliminarGasto(xajax.getFormValues('frmTotalDcto'));" title="Quitar Cargos"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr id="trGastoItem" align="left" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmGasto" onclick="selecAllChecks(this.checked,this.id,'frmTotalDcto');"/></td>
                            <td colspan="6"></td>
                        </tr>
                        <tr id="trItmPieGasto" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="4">Total Cargos:</td>
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
                        <table border="0" width="100%">
                        <tr align="left">
                            <td class="tituloCampo">Observación:</td>
                        </tr>
                        <tr align="left">
                            <td><textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea></td>
                        </tr>
                        </table>
                        <table border="0" width="100%">
                        <tr>
                            <td align="center" class="tituloArea">Aprobación</td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="17%">Aprobado:</td>
                                    <td width="46%">
                                    	<table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="hddIdEmpleadoAprobado" name="hddIdEmpleadoAprobado" readonly="readonly" size="6" style="text-align:right"/></td>
                                            <td>&nbsp;</td>
                                            <td><input type="text" id="txtNombreEmpleadoAprobado" name="txtNombreEmpleadoAprobado" readonly="readonly" size="25"/></td>
                                        </tr>
                                        </table>
									</td>
                                    <td align="right" class="tituloCampo" width="17%">Fecha:</td>
                                    <td width="20%"><input type="text" id="txtFechaAprobado" name="txtFechaAprobado" readonly="readonly" style="text-align:center" size="10"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td valign="top" width="50%">
                    	<table width="100%">
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
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearDescuento" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'iv_pedido_venta_form_descuento');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
										<input type="hidden" id="hddConfig19" name="hddConfig19"/>
                                    </td>
                                	<td nowrap="nowrap">
                                        <input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true', 'true');" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="5" style="text-align:right"/>%
									</td>
								</tr>
                                </table>
                            </td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Cargos Con Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoConIvaMoneda"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Cargos Sin Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoSinIvaMoneda"></td>
                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trNetoPedido" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalRegistroMoneda"></td>
                            <td><input type="text" id="txtTotalOrden" name="txtTotalOrden" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
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
            	<a class="modalImg" id="aGuardarSobregiro" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'iv_sobregiro_cliente');"></a>
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto(1);"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/select.png"/></td><td>&nbsp;</td><td>Aprobar</td></tr></table></button>
            <a class="modalImg" id="aDesaprobar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblDesaprobarDcto');">
                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/cross.png" title="Nuevo"/></td><td>&nbsp;</td><td>Desaprobar</td></tr></table></button>
            </a>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('iv_orden_venta_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
	<table border="0" id="tblLista" style="display:none" width="960">
    <tr>
    	<td>
        <form id="frmLista" name="frmLista" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddNumeroItm" name="hddNumeroItm" readonly="readonly">
        	<table width="100%">
            <tr>
            	<td><div id="divLista" style="width:100%;"></div></td>
			</tr>
            <tr>
                <td align="right"><hr>
                    <button type="submit" id="btnGuardarLista" name="btnGuardarLista"  onclick="xajax_asignarGasto(xajax.getFormValues('frmLista'), xajax.getFormValues('frmListaArticulo'));">Aceptar</button>
                    <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cancelar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>

    <div id="tblArticulo" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmDatosArticulo" name="frmDatosArticulo" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddIdArticulo" name="hddIdArticulo" readonly="readonly"/>
                <input type="hidden" id="hddIdArticuloCosto" name="hddIdArticuloCosto" readonly="readonly"/>
                <input type="hidden" id="hddNumeroArt" name="hddNumeroArt" readonly="readonly"/>
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
                    <td width="12%"></td>
                    <td width="34%"></td>
                    <td width="12%"></td>
                    <td width="20%"></td>
                    <td width="22%"></td>
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
                    <td id="tdUbicacion" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ubicación:</td>
                    <td id="tdlstUbicacion">
                        <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td id="tdlstCasillaArt"></td>
                            <td>&nbsp;</td>
                            <td><input type="text" id="txtCantidadUbicacion" name="txtCantidadUbicacion" readonly="readonly" size="10" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanPrecioUnitario; ?>:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td id="tdlstPrecioArt" width="100%"></td>
                            <td>&nbsp;</td>
                            <td id="tdMonedaPrecioArt"></td>
                            <td>&nbsp;</td>
                            <td align="right">
                                <input type="text" id="txtPrecioArt" name="txtPrecioArt" maxlength="17" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="10" style="text-align:right"/>
                            </td>
                            <td align="center" id="tdDesbloquearPrecio"></td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddIdArtPrecio" name="hddIdArtPrecio" readonly="readonly"/>
                        <input type="hidden" id="hddIdPrecioArtPredet" name="hddIdPrecioArtPredet" readonly="readonly"/>
                        <input type="hidden" id="hddPrecioArtPredet" name="hddPrecioArtPredet" readonly="readonly"/>
                        <input type="hidden" id="hddBajarPrecio" name="hddBajarPrecio" readonly="readonly"/>
                        <a class="modalImg" id="aDesbloquearPrecioArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'iv_catalogo_venta_precio_venta');"></a>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                    <td>
                        <input type="hidden" id="hddIdIvaArt" name="hddIdIvaArt" readonly="readonly"/>
                        <input type="text" id="txtIvaArt" name="txtIvaArt" readonly="readonly" size="10" style="text-align:right"/>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Precio Sugerido:</td>
                    <td><input type="text" id="txtPrecioSugerido" name="txtPrecioSugerido" maxlength="17" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="10" style="text-align:right"/></td>
                </tr>
                <tr>
                    <td align="right" colspan="5"><hr>
                        <button type="submit" id="btnGuardarArticulo" name="btnGuardarArticulo" onclick="validarFrmDatosArticulo();">Aceptar</button>
                        <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo" class="close">Cancelar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
	</div>
    
    <table border="0" id="tblDesaprobarDcto" width="560">
    <tr>
    	<td>
        <form id="frmDesaprobarDcto" name="frmDesaprobarDcto" onsubmit="return false;" style="margin:0">
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Estatus:</td>
                <td width="75%">
                	<select id="lstEstatusPedido" name="lstEstatusPedido">
                    	<option value="-1">[ Seleccione ]</option>
                        <option style="background-color:#FFFF00" value="0">Pendiente por Terminar</option>
                        <!--<option style="background-color:#009900; color:#FFFFFF" value="1">Pedido</option>-->
                        <option style="background-color:#663300; color:#FFFFFF" value="5">Anulada</option>
                    </select>
                </td>
			</tr>
            <tr>
                <td align="right" colspan="2"><hr>
                    <button type="submit" id="btnGuardarDesaprobarDcto" name="btnGuardarDesaprobarDcto" onclick="validarFrmDcto(0);">Aceptar</button>
                    <button type="button" id="btnCancelarDesaprobarDcto" name="btnCancelarDesaprobarDcto" class="close">Cancelar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>
	
    <table border="0" id="tblListaGasto" width="760">
    <tr>
        <td>
        <form id="frmBuscarGasto" name="frmBuscarGasto" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarGasto" name="txtCriterioBuscarGasto" class="inputHabilitado" onkeyup="byId('btnBuscarGasto').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarGasto" name="btnBuscarGasto" onclick="xajax_buscarGasto(xajax.getFormValues('frmBuscarGasto'));">Buscar</button>
                    <button type="button" onclick="byId('txtCriterioBuscarGasto').value = ''; byId('btnBuscarGasto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaGasto" name="frmListaGasto" style="margin:0" onsubmit="return false;">
            <div id="divListaGasto" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaGasto" name="btnCancelarListaGasto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
	
<form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
	<table border="0" id="tblPermiso" style="display:none" width="560">
    <tr>
        <td>
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="25%">Acción:</td>
                <td width="75%"><input type="text" id="txtDescripcionPermiso" name="txtDescripcionPermiso" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td>
                    <input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
                </td>
            </tr>
        	</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Aceptar</button>
			<button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" class="close">Cancelar</button>
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

<?php if ($_GET['id'] > 0) { ?>
	xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmTotalDcto'));
<?php } ?>

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>