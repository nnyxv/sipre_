<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if ((!validaAcceso("cc_captura_facturas_list","insertar") && $_GET['acc'] == 0 && $_GET['id'] == 0)
|| (!validaAcceso("cc_captura_facturas_list","editar") && $_GET['acc'] == 1 && $_GET['id'] > 0)){
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_factura_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - Factura</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
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
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblArticuloImpuesto').style.display = 'none';
		
		if (verTabla == "tblLista") {
			byId('trBuscarEmpleado').style.display = 'none';
			byId('trBuscarCliente').style.display = 'none';
			byId('trBuscarConcepto').style.display = 'none';
			byId('btnGuardarLista').style.display = 'none';
			
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('trBuscarCliente').style.display = '';
				
				byId('btnBuscarCliente').click();
				
				tituloDiv1 = 'Clientes';
				byId(verTabla).width = "960";
			} else if (valor == "Gasto") {
				document.forms['frmLista'].reset();
				
				byId('btnGuardarLista').style.display = '';
				
				xajax_formGastosArticulo(xajax.getFormValues('frmListaArticulo'), valor2);
				
				tituloDiv1 = 'Gastos del Artículo';
				byId(verTabla).width = "960";
			} else if (valor == "Empleado") {
				document.forms['frmBuscarEmpleado'].reset();
				
				byId('txtCriterioBuscarEmpleado').className = 'inputHabilitado';
				
				byId('trBuscarEmpleado').style.display = '';
				
				byId('btnBuscarEmpleado').click();
				
				tituloDiv1 = 'Empleados';
				byId(verTabla).width = "760";
			} else if (valor == "Concepto") {
				document.forms['frmBuscarConcepto'].reset();
				
				byId('txtCriterioBuscarConcepto').className = 'inputHabilitado';
				
				byId('trBuscarConcepto').style.display = '';
				
				byId('btnBuscarConcepto').click();
				
				tituloDiv1 = 'Conceptos';
				byId(verTabla).width = "760";
			}
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
		} else if (verTabla == "tblArticuloImpuesto") {
			xajax_formArticuloImpuesto();
			tituloDiv1 = 'Editar Impuesto de Articulos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			} else if (valor == "Gasto") {
				if (byId('txtMontoGasto1') != undefined) {
					byId('txtMontoGasto1').focus();
					byId('txtMontoGasto1').select();
				}
			} else if (valor == "Empleado") {
				byId('txtCriterioBuscarEmpleado').focus();
				byId('txtCriterioBuscarEmpleado').select();
			}
		}
	}
	
	function validarAsignarDepartamento() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('lstAplicaLibro','t','listaExceptCero') == true) {
			xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('lstAplicaLibro','t','listaExceptCero');
			
			selectedOption('lstModulo',-1);
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmArticuloImpuesto() {
		if (validarCampo('lstIvaCbx','t','listaExceptCero') == true) {
			xajax_asignarArticuloImpuesto(xajax.getFormValues('frmArticuloImpuesto'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('lstIvaCbx','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmDcto(){
		error = false;
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('lstAplicaLibro','t','listaExceptCero') == true
		&& validarCampo('txtNumeroControlFactura','t','') == true
		&& validarCampo('txtFechaFactura','t','fecha') == true
		&& validarCampo('txtFechaVencimiento','t','fecha') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('lstVendedor','t','lista') == true
		&& validarCampo('txtTotalFactura','t','numPositivo') == true
		&& validarCampo('txtTotalExento','t','numPositivo') == true
		&& validarCampo('txtTotalExonerado','t','numPositivo') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('lstAplicaLibro','t','listaExceptCero');
			validarCampo('txtNumeroControlFactura','t','');
			validarCampo('txtFechaFactura','t','fecha');
			validarCampo('txtFechaVencimiento','t','fecha');
			validarCampo('lstModulo','t','listaExceptCero');
			validarCampo('lstVendedor','t','lista');
			validarCampo('txtTotalFactura','t','numPositivo');
			validarCampo('txtTotalExento','t','numPositivo');
			validarCampo('txtTotalExonerado','t','numPositivo');
			
			error = true;
		}
		
		if (!(byId('hddIdFactura').value > 0)) {
			if (!(validarCampo('txtObservacion','t','') == true)) {
				validarCampo('txtObservacion','t','');
				
				error = true;
			}
		}
		
		if (byId('lstModulo').value == 3) {
			if (!(byId('hddIdFactura').value > 0) && byId('lstAplicaLibro').value == 1) {
				if (!(validarCampo('lstClaveMovimiento','t','lista') == true)) {
					validarCampo('lstClaveMovimiento','t','lista');
					
					error = true;
				}
				
				if (!(byId('hddObjItmArticulo').value.length > 0)) {
					alert("Debe agregar articulos al pedido");
					return false;
				}
			}
		} else {
			if (!(validarCampo('txtNumeroFactura','t','') == true)) {
				validarCampo('txtNumeroFactura','t','');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea Registrar el Documento?') == true) {
				xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			}
		}
	}
	
	function validarInsertarArticulo(idArticulo) {
		xajax_insertarArticulo(idArticulo, xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'));
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr class="solo_print">
			<td align="left" id="tdEncabezadoImprimir"></td>
		</tr>
		<tr>
			<td class="tituloPaginaCuentasPorCobrar"><span id="tituloPagina">Factura</span></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
			<form id="frmDcto" name="frmDcto">
            	<table border="0" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                            <td>
                            <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                            </a>
                            </td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="12%">Registrado por:</td>
                    <td width="58%">
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpleado" name="txtIdEmpleado" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" width="12%"></td>
                    <td width="18%"></td>
                </tr>
                <tr>
                	<td colspan="4">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="70%">
                            <fieldset><legend class="legend">Cliente</legend>
                                <table id="tblIdCliente" border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                    <td colspan="3">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarCliente" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Cliente');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                        </tr>
                                        <tr align="center">
                                            <td id="tdMsjCliente" colspan="3"></td>
                                        </tr>
                                        </table>
                                        <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
                                        <input type="hidden" id="hddTipoPagoCliente" name="hddTipoPagoCliente"/>
                                    </td>
                                    <td align="right" class="tituloCampo"><?php echo $spanClienteCxC; ?>:</td>
                                    <td><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                                    <td colspan="3" rowspan="2"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="55" readonly="readonly" rows="3"></textarea></td>
                                    <td align="right" class="tituloCampo"><?php echo $spanNIT; ?>:</td>
                                    <td><input type="text" id="txtNITCliente" name="txtNITCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Días Crédito:</td>
                                    <td>
                                        <table border="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td width="40%">Días:</td>
                                            <td width="60%"><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        </tr>
                                        <tr>
                                            <td>Disponible:</td>
                                            <td><input type="text" id="txtCreditoCliente" name="txtCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="16%">Teléfono:</td>
                                    <td width="15%"><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                                    <td align="right" class="tituloCampo" width="16%">Otro Teléfono:</td>
                                    <td width="15%"><input type="text" id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                                    <td width="16%"></td>
                                    <td width="22%"></td>
                                </tr>
                                </table>
                            </fieldset>
                            
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                                    <td width="16%">
                                        <select id="lstTipoMovimiento" name="lstTipoMovimiento" style="width:99%">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="1">1.- COMPRA</option>
                                            <option value="2">2.- ENTRADA</option>
                                            <option value="3">3.- VENTA</option>
                                            <option value="4">4.- SALIDA</option>
                                        </select>
                                    </td>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                                    <td id="tdlstClaveMovimiento" width="28%"></td>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                                    <td id="tdTipoPago" width="20%">
                                        <label><input type="radio" id="rbtTipoPagoCredito" name="rbtTipoPago" value="0"/> Crédito</label>
                                        <label><input type="radio" id="rbtTipoPagoContado" name="rbtTipoPago" value="1" checked="checked"/> Contado</label>
                                    </td>
                                </tr>
                                <tr id="trCreditoTradeIn" align="left">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td align="right" class="tituloCampo">Trade-In:</td>
                                    <td id="tdlstCreditoTradeIn"></td>
                                </tr>
                                </table>
                                
                                <table align="left" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <button type="button" id="btnFacturaVentaPDF"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Factura Venta PDF</td></tr></table></button>
                                        <button type="button" id="btnReciboPagoPDF"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/print.png"/></td><td>&nbsp;</td><td>Recibo(s) de Pago(s)</td></tr></table></button>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Factura</legend>
                                <input type="hidden" id="hddIdFactura" name="hddIdFactura"/>
                            	<input type="hidden" id="hddIdMoneda" name="hddIdMoneda"/>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Aplica Libros:</td>
                                    <td width="60%">
                                    	<select id="lstAplicaLibro" name="lstAplicaLibro" style="width:99%">
                                        	<option value="-1">[ Seleccione ]</option>
                                            <option value="0">NO</option>
                                            <option value="1">SI</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
                                    <td id="tdlstModulo"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Factura:</td>
                                    <td><input type="text" id="txtNumeroFactura" name="txtNumeroFactura" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
                                    <td><input type="text" id="txtNumeroControlFactura" name="txtNumeroControlFactura" size="20" style="color:#F00; font-weight:bold; text-align:center;"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Registro:</td>
                                    <td id="tdtxtFechaFactura"><input type="text" id="txtFechaFactura" name="txtFechaFactura" autocomplete="off" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Vencimiento:</td>
                                    <td id="tdtxtFechaVencimiento"><input type="text" id="txtFechaVencimiento" name="txtFechaVencimiento" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Vendedor:</td>
                                    <td align="left" id="tdlstVendedor"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Nro. Siniestro:</td>
                                    <td><input type="text" id="txtNumeroSiniestro" name="txtNumeroSiniestro" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td id="tdtxtEstatus" colspan="2"><input type="text" id="txtEstatus" name="txtEstatus" class="inputSinFondo" readonly="readonly" size="20" style="text-align:center"/></td>
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
        <tr id="trListaArticulo" align="left">
            <td>
                <a class="modalImg" id="aImpuestoArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArticuloImpuesto');">
                    <button type="button" title="Impuesto Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/text_signature.png"/></td><td>&nbsp;</td><td>Impuesto</td></tr></table></button>
                </a>
                
            <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaArticulo');"/></td>
                	<td width="4%">Nro.</td>
					<td width="14%">Código</td>
                    <td width="54%">Descripción</td>
                    <td width="6%">Cantidad</td>
                    <td width="8%">Precio Unit.</td>
                    <td width="4%">% Impuesto</td>
                    <td width="10%">Total</td>
                    <td>
                        <a class="modalImg" id="aAgregarArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Concepto');">
                            <img class='puntero' src="../img/iconos/add.png" title='Agregar'/>
                        </a>
                        <input type="hidden" id="hddObjItmArticulo" name="hddObjItmArticulo" readonly="readonly" title="hddObjItmArticulo"/>
                    </td>
                </tr>
                <tr id="trItmPie"></tr>
                </table>
			</form>
			</td>
        </tr>
        <tr id="trListaPagoDcto">
        	<td>
            <fieldset><legend class="legend">Desglose de Pagos</legend>
            <form id="frmListaPagoDcto" name="frmListaPagoDcto" style="margin:0">
            	<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
            	<table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                	<td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaPagoDcto');"/></td>
                    <td><img src="../img/iconos/information.png" title="Mostrar en pagos de la factura"/></td>
                    <td><img src="../img/iconos/information.png" title="En la copia del banco sumar al: &#10;C = Pago de Contado &#10;T = Trade In"/></td>
                	<td width="8%">Fecha Pago</td>
					<td width="6%">Nro. Recibo</td>
                	<td width="12%">Forma de Pago</td>
                	<td width="26%">Nro. Tranferencia / Cheque / Anticipo / Nota de Crédito</td>
                    <td width="15%">Banco Cliente / Cuenta Cliente</td>
                    <td width="15%">Banco Compañia / Cuenta Compañia</td>
                    <td width="8%">Caja</td>
                    <td width="10%">Monto</td>
                </tr>
                <tr id="trItmPiePago" align="right" class="trResaltarTotal">
                	<td class="tituloCampo" colspan="10">Total Pagos:</td>
                	<td><input type="text" id="txtTotalPago" name="txtTotalPago" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                </tr>
                </table>
            </form>
            </fieldset>
            </td>
		</tr>
		<tr>
			<td align="right">
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
                <table border="0" width="100%">
                <tr>
                    <td valign="top" width="50%">
                    <fieldset id="fieldsetNotaCredito"><legend class="legend">Nota de Crédito por Devolución</legend>
                    	<div id="divNotaCredito"></div>
                    </fieldset>
                    
                        <table width="100%">
                        <tr align="left">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
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
                            <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" onblur="setFormatoRafk(this,2);" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td nowrap="nowrap">
                                <input type="text" id="txtDescuento" name="txtDescuento" readonly="readonly" size="6" style="text-align:right"/>%
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Gastos Con Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoConIvaMoneda"></td>
                            <td><input type="text" id="txtGastosConIva" name="txtGastosConIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                        <tr align="right" id="trGastosSinIva">
                            <td class="tituloCampo">Gastos Sin Impuesto:</td>
                            <td></td>
                            <td></td>
                            <td id="tdGastoSinIvaMoneda"></td>
                            <td><input type="text" id="txtGastosSinIva" name="txtGastosSinIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr id="trNetoPresupuesto" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalRegistroMoneda"></td>
                            <td><input type="text" id="txtTotalFactura" name="txtTotalFactura" readonly="readonly" size="17" style="text-align:right" class="inputSinFondo"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal3">
                            <td class="tituloCampo">Saldo:</td>
                            <td></td>
                            <td></td>
                            <td id="tdTotalSaldoMoneda"></td>
                            <td><input type="text" id="txtTotalSaldo" name="txtTotalSaldo" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
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



				<script type="text/javascript">
					
					console.log('<?php 
					$v1=$_GET['id']; 
					echo $v1; ?>');

				</script>


				<script type="text/javascript">
					
						console.log('<?php 



					$query = "SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = $v1 ";

					$rs = mysql_query($query);
					$reg=mysql_fetch_array($rs);
					echo "consulta";
					echo $reg['fechaRegistroFactura'];
					$fechaRegistroFactura = $reg['fechaRegistroFactura'];
					$dateTime_fechaReconversion = '2018-08-20';

					 ?>');

				</script>

            	
			<td align="right"><hr>
				<?php
            		/*if($fechaRegistroFactura > $dateTime_fechaReconversion){
            			echo "<button style=\"display:none;\" type=\"button\" id=\"btnReconversionMonetaria\" onClick=\"reconversionMonetaria(0)\"><table align=\"left\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/ico_cambio.png\" alt=\"imagen\"></td><td>&nbsp;</td><td>Reconversion </td></tr></table></button>";
					}else{
						echo "<input type='hidden' value='1' id='muestraBtnReconversion'>";
						echo "<button type=\"button\" id=\"btnReconversionMonetaria\" onClick=\"reconversionMonetaria(1)\"><table align=\"left\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/ico_cambio.png\" alt=\"imagen\"></td><td>&nbsp;</td><td>Reconversion </td></tr></table></button>";
					}*/

            	?>
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    <tr id="trBuscarEmpleado">
    	<td>
        <form id="frmBuscarEmpleado" name="frmBuscarEmpleado" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado" onkeyup="byId('btnBuscarEmpleado').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpleado'].reset(); byId('btnBuscarEmpleado').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarCliente">
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarConcepto">
    	<td>
        <form id="frmBuscarConcepto" name="frmBuscarConcepto" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarConcepto" name="txtCriterioBuscarConcepto" onkeyup="byId('btnBuscarConcepto').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarConcepto" name="btnBuscarConcepto" onclick="xajax_buscarConcepto(xajax.getFormValues('frmBuscarConcepto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarConcepto'].reset(); byId('btnBuscarConcepto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="frmLista" name="frmLista" onsubmit="return false;" style="margin:0">
			<input type="hidden" id="hddNumeroItm" name="hddNumeroItm">
        	<table width="100%">
            <tr>
            	<td><div id="divLista" style="width:100%;"></div></td>
			</tr>
            <tr>
                <td align="right"><hr>
                    <button type="submit" id="btnGuardarLista" name="btnGuardarLista" onclick="xajax_asignarGasto(xajax.getFormValues('frmLista'), xajax.getFormValues('frmListaArticulo'));">Aceptar</button>
                    <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana"/>
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaEmpresa" name="frmListaEmpresa" onsubmit="return false;" style="margin:0">
            <div id="divListaEmpresa" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa" class="close">Cerrar</button>
        </td>
    </tr>
    </table>

<form id="frmArticuloImpuesto" name="frmArticuloImpuesto" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblArticuloImpuesto" style="display:none" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td id="tdlstIvaCbx" width="70%">
                	<select id="lstIvaCbx" name="lstIvaCbx">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
			</tr>
            </table>
		</td>
	</tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarArticuloImpuesto" name="btnGuardarArticuloImpuesto" onclick="validarFrmArticuloImpuesto();">Aceptar</button>
            <button type="button" id="btnCancelarArticuloImpuesto" name="btnCancelarArticuloImpuesto" class="close">Cerrar</button>
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

xajax_formDcto('<?php echo $_GET['id']; ?>','<?php echo $_GET['acc']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>

<script type="text/javascript">
xajax_objetoCodigoDinamico('tdCodigoArt','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstTipoOrden();



function reconversionMonetaria(activo){

	if(activo == 0){

		alert("No está permitido reconvertir una factura con fecha posterior al 20-Agosto-2018");
		return false;

	}else{

		var idFacturaReconversion = <?php echo $_GET['id'] ?>;
		var confirmacion = confirm("¿Desea realizar la reconversión de Factura de repuesto? Esta acción no se puede revertir");

		if (confirmacion == true){
			mensaje = xajax_reconversionFactura(idFacturaReconversion);

		}else{
			return false;
		}
	}

	return true;
}
bloquearForm();
</script>