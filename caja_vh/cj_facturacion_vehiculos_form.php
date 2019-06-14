<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_factura_venta_list","insertar"))) {
	echo "<script>alert('Acceso Denegado');top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_facturacion_vehiculos_form.php");
include("../controladores/ac_pg_calcular_comision.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Facturación de Venta de Vehículos</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
		
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblArticuloImpuesto').style.display = 'none';
		byId('tblListaItemPedido').style.display = 'none';
		byId('tblDeposito').style.display = 'none';
		byId('tblLista').style.display = 'none';
		byId('tblBono').style.display = 'none';
		
		if (verTabla == "tblArticuloImpuesto") {
			xajax_formArticuloImpuesto();
			tituloDiv1 = 'Editar Impuesto de Articulos';
		} else if (verTabla == "tblListaItemPedido") {
			document.forms['frmListaItemPedido'].reset();
			
			xajax_formItemsPedido('<?php echo $_GET['id']; ?>')
			
			tituloDiv1 = 'Items a Facturar';
		} else if (verTabla == "tblDeposito") {
			if (valor == "Deposito") {
				if (validarCampo('txtTotalFactura','t','monto') == true
				&& validarCampo('txtMontoPago','t','monto') == true
				&& validarCampo('txtFechaDeposito','t','fecha') == true
				&& validarCampo('selBancoCompania','t','lista') == true
				&& validarCampo('selNumeroCuenta','t','') == true
				&& validarCampo('txtNumeroDctoPago','t','') == true) {
					document.forms['frmDeposito'].reset();
					
					byId('tblDeposito').style.display = '';
					byId('tblLista').style.display = 'none';
					
					xajax_formDeposito(xajax.getFormValues('frmDeposito'));
					
					tituloDiv1 = 'Detalle Deposito';
				} else {
					validarCampo('txtTotalFactura','t','monto') == true
					validarCampo('txtMontoPago','t','monto');
					validarCampo('txtFechaDeposito','t','fecha');
					validarCampo('selBancoCompania','t','lista');
					validarCampo('selNumeroCuenta','t','');
					validarCampo('txtNumeroDctoPago','t','');
					
					alert("Los campos señalados en rojo son requeridos");
					return false;
				}
			}
		} else if (verTabla == "tblLista") {
			document.forms['frmBuscarAnticipoNotaCreditoChequeTransferencia'].reset();
			
			byId('trBuscarAnticipoNotaCreditoChequeTransferencia').style.display = '';
			
			byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').className = 'inputHabilitado';
			
			xajax_buscarAnticipoNotaCreditoChequeTransferencia(xajax.getFormValues('frmBuscarAnticipoNotaCreditoChequeTransferencia'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmListaPagos'));
			
			tituloDiv1 = 'Listado';
		} else if (verTabla == "tblBono") {
			document.forms['frmBono'].reset();
			
			byId('tdlstAnticipoBono').innerHTML = '';
			byId('trlstAnticipoBono').style.display = 'none';
			
			byId('txtIdClienteBono').className = 'inputHabilitado';
			byId('txtIdMotivoBono').className = 'inputHabilitado';
			byId('txtMontoBono').className = 'inputHabilitado';
			
			byId('hddNumeroItmBono').value = valor;
			
			xajax_formBono(xajax.getFormValues('frmBono'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmListaPagos'));
			
			tituloDiv1 = 'Agregar Bono';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').focus();
			byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').select();
		} else if (verTabla == "tblBono") {
			byId('txtIdClienteBono').focus();
			byId('txtIdClienteBono').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2, valor3) {
		byId('tblAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
		byId('tblLista2').style.display = 'none';
		byId('tblListaMotivo').style.display = 'none';
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			byId('txtMontoDocumento').className = 'inputHabilitado';
			
			xajax_cargarSaldoDocumento(valor, valor2, xajax.getFormValues('frmListaPagos'));
			
			tituloDiv2 = '';
		} else if (verTabla == "tblLista2") {
			byId('trBuscarEmpleado').style.display = 'none';
			byId('trBuscarCliente').style.display = 'none';
			byId('trBuscarConcepto').style.display = 'none';
			byId('btnGuardarLista2').style.display = 'none';
			
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('trBuscarCliente').style.display = '';
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('hddObjDestinoCliente').value = valor2;
				
				byId('btnBuscarCliente').click();
				
				tituloDiv2 = 'Clientes';
				byId(verTabla).width = "960";
			}
		} else if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			
			byId('txtCriterioBuscarMotivo').className = 'inputHabilitado';
			
			byId('hddObjDestinoMotivo').value = valor;
			byId('hddPagarCobrarMotivo').value = valor2;
			byId('hddIngresoEgresoMotivo').value = valor3;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv2 = 'Motivos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			byId('txtMontoDocumento').focus();
			byId('txtMontoDocumento').select();
		} else if (verTabla == "tblLista2") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			}
		} else if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		}
	}
	
	function asignarTipoPago(idFormaPago) {
		byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = '';
		byId('txtFechaDeposito').value = '';
		byId('txtNumeroCuenta').value = '';
		byId('txtNumeroDctoPago').value = '';
		byId('txtMontoPago').value = '';
		
		byId('trTipoTarjeta').style.display = 'none';
		byId('trPorcentajeRetencion').style.display = 'none';
		byId('trPorcentajeComision').style.display = 'none';
		
		byId('trBancoFechaDeposito').style.display = 'none';
		byId('tdselBancoCliente').style.display = 'none';
		byId('txtFechaDeposito').style.display = 'none';
		
		byId('trBancoCompania').style.display = 'none';
		byId('tdselBancoCompania').style.display = 'none';
		
		byId('trNumeroCuenta').style.display = 'none';
		byId('txtNumeroCuenta').style.display = 'none';
		byId('divselNumeroCuenta').style.display = 'none';
		
		byId('trNumeroDocumento').style.display = 'none';
		byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
		byId('btnAgregarDetDeposito').style.display = 'none';
		
		xajax_cargaLstBancoCliente("selBancoCliente");
		
		switch(idFormaPago) {
			case '1' : // EFECTIVO
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '2' : // CHEQUE
				byId('tdEtiquetaBancoFechaDeposito').innerHTML = 'Banco Cliente:';
				byId('trBancoFechaDeposito').style.display = '';
				byId('tdselBancoCliente').style.display = '';
				
				byId('trNumeroCuenta').style.display = '';
				byId('txtNumeroCuenta').style.display = '';
				byId('txtNumeroCuenta').className = 'inputHabilitado';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				xajax_cargaLstBancoCompania(2);
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '3' : // DEPOSITO
				byId('tdEtiquetaBancoFechaDeposito').innerHTML = 'Fecha Deposito:';
				byId('tdNumeroDocumento').innerHTML = 'Nro. Planilla:';
				byId('trBancoFechaDeposito').style.display = '';
				byId('txtFechaDeposito').style.display = '';
				
				byId('trBancoCompania').style.display = '';
				byId('tdselBancoCompania').style.display = '';
				
				byId('trNumeroCuenta').style.display = '';
				byId('divselNumeroCuenta').style.display = '';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				byId('btnAgregarDetDeposito').style.display = '';
				
				byId('txtFechaDeposito').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				xajax_cargaLstBancoCompania(3);
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '4' : // TRANSFERENCIA
				byId('tdEtiquetaBancoFechaDeposito').innerHTML = 'Banco Cliente:';
				byId('trBancoFechaDeposito').style.display = '';
				byId('tdselBancoCliente').style.display = '';
				
				byId('trBancoCompania').style.display = '';
				byId('tdselBancoCompania').style.display = '';
				
				byId('trNumeroCuenta').style.display = '';
				byId('divselNumeroCuenta').style.display = '';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				xajax_cargaLstBancoCompania(4);
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '5' : // TARJETA CREDITO
				byId('trTipoTarjeta').style.display = '';
				byId('trPorcentajeRetencion').style.display = '';
				byId('trPorcentajeComision').style.display = '';
				
				byId('tdEtiquetaBancoFechaDeposito').innerHTML = 'Banco Cliente:';
				byId('tdNumeroDocumento').innerHTML = 'Nro. Recibo:';
				byId('trBancoFechaDeposito').style.display = '';
				byId('tdselBancoCliente').style.display = '';
				
				byId('trBancoCompania').style.display = '';
				byId('tdselBancoCompania').style.display = '';
				
				byId('trNumeroCuenta').style.display = '';
				byId('divselNumeroCuenta').style.display = '';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				xajax_cargaLstBancoCompania(5);
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '6' : // TARJETA DEBITO
				byId('trPorcentajeComision').style.display = '';
				
				byId('tdEtiquetaBancoFechaDeposito').innerHTML = 'Banco Cliente:';
				byId('tdNumeroDocumento').innerHTML = 'Nro. Recibo:';
				byId('trBancoFechaDeposito').style.display = '';
				byId('tdselBancoCliente').style.display = '';
				
				byId('trBancoCompania').style.display = '';
				byId('tdselBancoCompania').style.display = '';
				
				byId('trNumeroCuenta').style.display = '';
				byId('divselNumeroCuenta').style.display = '';
				
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				xajax_cargaLstBancoCompania(6);
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '7' : // ANTICIPO
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputInicial';
				byId('txtNumeroDctoPago').readOnly = true;
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '8' : // NOTA CREDITO
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputInicial';
				byId('txtNumeroDctoPago').readOnly = true;
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '9' : // RETENCION
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
			case '10' :  // RETENCION ISLR
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputHabilitado';
				byId('txtNumeroDctoPago').readOnly = false;
				
				byId('btnGuardarDetallePago').style.display = '';
				break;
		}
	}
	
	function asignarTipoPagoDetalleDeposito(idFormaPago) {
		byId('txtNroCuentaDeposito').value = '';
		byId('txtNroChequeDeposito').value = '';
		byId('txtMontoDeposito').value = '';
		
		switch(idFormaPago) {
			case '1' : // EFECTIVO
				byId('trBancoCliente').style.display = 'none';
				byId('trNroCuenta').style.display = 'none';
				byId('trNroCheque').style.display = 'none';
				byId('trMonto').style.display = '';
				break;
			case '2' : // CHEQUE
				byId('trBancoCliente').style.display = '';
				byId('trNroCuenta').style.display = '';
				byId('trNroCheque').style.display = '';
				byId('trMonto').style.display = '';
				break;
		}
	}
	
	function calcularPorcentajeTarjetaCredito() {
		if (byId('selTipoPago').value == 5) {
			byId('montoTotalRetencion').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeRetencion').value) / 100,2);
			byId('montoTotalComision').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeComision').value) / 100,2);
		} else if (byId('selTipoPago').value) {
			byId('montoTotalComision').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeComision').value) / 100,2);
		}
	}
	
	function confirmarEliminarPago(pos) {
		if (confirm("Desea eliminar el pago?"))
			xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),pos);
	}
	
	function confirmarEliminarPagoDetalleDeposito(pos) {
		if (confirm("Desea eliminar el detalle del deposito?"))
			xajax_eliminarPagoDetalleDeposito(xajax.getFormValues('frmDeposito'),pos);
	}
	
	function validarEliminarBono(pos) {
		if (confirm("Desea eliminar el bono?")) {
			xajax_eliminarBono(pos);
		}
	}
	
	function validarFrmAnticipoNotaCreditoChequeTransferencia() {
		var saldo = parseNumRafk(byId('txtSaldoDocumento').value);
		var monto = parseNumRafk(byId('txtMontoDocumento').value);
		var montoFaltaPorPagar = parseNumRafk(byId('txtMontoPorPagar').value);
		
		if (parseFloat(saldo) < parseFloat(monto)) {
			alert("El monto a pagar no puede ser mayor que el saldo del documento");
		} else {
			if (parseFloat(montoFaltaPorPagar) >= parseFloat(monto)) {
				if (confirm("Desea cargar el pago?")) {
					byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = byId('hddIdDocumento').value;
					byId('txtNumeroDctoPago').value = byId('txtNroDocumento').value;
					byId('txtMontoPago').value = byId('txtMontoDocumento').value;
					
					error = false;
					if (!(validarCampo('txtTotalFactura','t','monto') == true
					&& validarCampo('txtMontoPago','t','monto') == true
					&& validarCampo('txtNumeroDctoPago','t','') == true
					&& validarCampo('txtMontoDocumento','t','monto') == true)) {
						validarCampo('txtTotalFactura','t','monto');
						validarCampo('txtMontoPago','t','monto');
						validarCampo('txtNumeroDctoPago','t','');
						validarCampo('txtMontoDocumento','t','monto');
						
						error = true;
					}
					
					if (error == true) {
						alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
						return false;
					} else {
						xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
					}
				}
			} else {
				alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
			}
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
	
	function validarFrmBono() {
		error = false;
		if (!(validarCampo('txtIdClienteBono','t','') == true
		&& validarCampo('txtIdMotivoBono','t','') == true
		&& validarCampo('txtMontoBono','t','monto') == true)) {
			validarCampo('txtIdClienteBono','t','');
			validarCampo('txtIdMotivoBono','t','');
			validarCampo('txtMontoBono','t','monto');
			
			error = true;
		}
		
		if (byId('lstAnticipoBono') != undefined) {
			if (!(validarCampo('lstAnticipoBono','t','lista') == true)) {
				validarCampo('lstAnticipoBono','t','lista');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_insertarBono(xajax.getFormValues('frmBono'),xajax.getFormValues('frmListaArticulo'));
		}
	}
	
	function validarFrmDcto() {
		if (validarCampo('txtIdPedido','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtNumeroControlFactura','t','') == true
		&& validarCampo('txtTotalOrden','t','monto') == true
		&& validarCampo('txtMontoPorPagar','t','numPositivo') == true) {
			if (confirm("¿Seguro Desea Facturar los Items Seleccionados?") == true) {
				if (byId('hddObj').value.length > 0) {
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmListaPagos'));
				} else {
					alert("Debe agregar articulos al pedido");
					return false;
				}
			}
		} else {
			validarCampo('txtIdPedido','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtNumeroControlFactura','t','');
			validarCampo('txtTotalOrden','t','monto');
			validarCampo('txtMontoPorPagar','t','numPositivo');
			
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		}
	}
	
	function validarFrmDeposito() {
		if (byId('txtSaldoDepositoBancario').value == 0) {
			xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
		} else {
			alert("El saldo del detalle del deposito debe ser 0 (cero)");
		}
	}
	
	function validarFrmDetallePago() {
		error = false;
		if (byId('selTipoPago').value == 1) { // EFECTIVO
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 2) { // CHEQUES
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('txtNumeroCuenta','t','') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('txtNumeroCuenta','t','');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 4) { // TRANSFERENCIA
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 5) { // TARJETA DE CREDITO
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('tarjeta','t','lista') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true
			&& validarCampo('porcentajeRetencion','t','numPositivo') == true
			&& validarCampo('montoTotalRetencion','t','numPositivo') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('tarjeta','t','lista');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('txtNumeroDctoPago','t','');
				validarCampo('porcentajeRetencion','t','numPositivo');
				validarCampo('montoTotalRetencion','t','numPositivo');
				validarCampo('porcentajeComision','t','numPositivo');
				validarCampo('montoTotalComision','t','numPositivo');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 6) { // TARJETA DE DEBITO
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('txtNumeroDctoPago','t','');
				validarCampo('porcentajeComision','t','numPositivo');
				validarCampo('montoTotalComision','t','numPositivo');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 9) { // RETENCION
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 10) { // RETENCION ISLR
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));
			}
		} else {
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('selTipoPago','t','lista') == true)) {
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('selTipoPago','t','lista');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			}
		}
	}
	
	function validarFrmDetalleDeposito() {
		error = false;
		if (byId('lstTipoPago').value == 1) { // EFECTIVO
			if (!(validarCampo('txtMontoDeposito','t','monto') == true)) {
				validarCampo('txtMontoDeposito','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPagoDeposito(xajax.getFormValues('frmDeposito'));
			}
		} else if (byId('lstTipoPago').value == 2) { // CHEQUES
			if (!(validarCampo('txtMontoDeposito','t','monto') == true
			&& validarCampo('lstBancoDeposito','t','lista') == true
			&& validarCampo('txtNroCuentaDeposito','t','') == true
			&& validarCampo('txtNroChequeDeposito','t','') == true)) {
				validarCampo('txtMontoDeposito','t','monto');
				validarCampo('lstBancoDeposito','t','lista');
				validarCampo('txtNroCuentaDeposito','t','');
				validarCampo('txtNroChequeDeposito','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPagoDeposito(xajax.getFormValues('frmDeposito'));
			}
		} else {
			if (!(validarCampo('lstTipoPago','t','lista') == true)) {
				validarCampo('lstTipoPago','t','lista');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			}
		}
	}
	
	function validarFrmListaItemPedido() {
		if (confirm("¿Desea facturar los Items Seleccionados?") == true) {
			xajax_insertarItem(xajax.getFormValues('frmListaItemPedido'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
        <tr>
            <td class="tituloPaginaCaja">Facturación de Venta de Vehículos</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            <form id="frmDcto" name="frmDcto" style="margin:0">
            	<table align="right" border="0" width="100%">
                <tr>
                	<td colspan="2">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="88%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
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
                                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                <td colspan="3">
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
                                <select id="lstTipoClave" name="lstTipoClave" onchange="xajax_cargaLstClaveMovimiento(this.value)" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">1.- COMPRA</option>
                                    <option value="2">2.- ENTRADA</option>
                                    <option value="3">3.- VENTA</option>
                                    <option value="4">4.- SALIDA</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td width="28%">
                                <input type="hidden" id="hddIdClaveMovimiento" name="hddIdClaveMovimiento"/>
                                <input type="text" id="txtClaveMovimiento" name="txtClaveMovimiento" readonly="readonly" size="30"/>
                            </td>
                            <td align="right" class="tituloCampo" width="12%">Tipo de Pago:</td>
                            <td width="20%">
                                <input type="hidden" id="hddTipoPago" name="hddTipoPago"/>
                                <input type="text" id="txtTipoPago" name="txtTipoPago" class="divMsjInfo2" readonly="readonly" size="20" style="text-align:center"/>
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
                    </td>
                	<td valign="top" width="30%">
                    <fieldset><legend class="legend">Datos de la Factura</legend>
                        <input type="hidden" id="txtIdFacturaEditada" name="txtIdFacturaEditada"/>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Nro. Factura:</td>
                            <td width="60%">
                                <input type="hidden" id="txtIdFactura" name="txtIdFactura"/>
                                <input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fecha Registro:</td>
                            <td>
                                <input type="text" id="txtFechaFactura" name="txtFechaFactura" onchange="xajax_asignarFechaCredito(xajax.getFormValues('frmDcto'))" readonly="readonly" size="16" style="text-align:center"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fecha Vencimiento:</td>
                            <td><input type="text" id="txtFechaVencimientoFactura" name="txtFechaVencimientoFactura" readonly="readonly" size="16" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Pedido:</td>
                            <td>
                            	<input type="hidden" id="txtIdPedido" name="txtIdPedido"/>
                                <input type="text" id="txtNumeroPedido" name="txtNumeroPedido" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                        </tr>
                        <tr><td id="tdMsjPedido" colspan="2"></td></tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Presupuesto:</td>
                            <td>
                            	<input type="hidden" id="txtIdPresupuesto" name="txtIdPresupuesto"/>
                            	<input type="text" id="txtNumeroPresupuesto" name="txtNumeroPresupuesto" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Financiamiento:</td>
                            <td>
                                <input type="hidden" id="hddIdFinanciamiento" name="hddIdFinanciamiento"/>
                                <input type="text" id="txtNumeroFinanciamiento" name="txtNumeroFinanciamiento" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Vendedor:</td>
                            <td>
                                <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado"/>
                                <input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="20"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtNumeroControlFactura" name="txtNumeroControlFactura" size="16" style="color:#F00; font-weight:bold; text-align:center;"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: 00-000000"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total Factura:</td>
                            <td><input type="text" id="txtTotalFactura" name="txtTotalFactura" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
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
        	<td align="left">
                <a class="modalImg" id="aImpuestoArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArticuloImpuesto');">
                    <button type="button" title="Impuesto Artículo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/text_signature.png"/></td><td>&nbsp;</td><td>Impuesto</td></tr></table></button>
                </a>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <table border="0" width="100%">
                <tr class="tituloColumna">
                	<td><input id="cbxItmAdicional" onclick="selecAllChecks(this.checked,this.id,'frmListaArticulo');" type="checkbox"></td>
                    <td width="4%">Nro.</td>
                    <td width="14%">Código</td>
                    <td width="54%">Descripción</td>
                    <td width="6%">Cant.</td>
                    <td width="8%">Precio Unit.</td>
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
                    <fieldset id="fieldsetAdicionalOtro"><legend class="legend">Otros Adicionales</legend>
                    	<table border="0" width="100%">
                        <tr class="tituloColumna">
                            <td></td>
                            <td width="6%">Nro.</td>
                            <td width="0%"></td>
                            <td width="48%">Descripción</td>
                            <td width="6%">Cant.</td>
                            <td width="14%">Precio Unit.</td>
                            <td width="10%">% Impuesto</td>
                            <td width="14%">Total</td>
                        </tr>
                        <tr id="trItmPieAdicionalOtro" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="7">Total Otros Adicionales:</td>
                            <td><input type="text" id="txtTotalAdicionalOtro" name="txtTotalAdicionalOtro" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
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
                                
                            	<input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="6" style="text-align:right"/>%
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td>
                            	<input type="radio" id="rbtInicialMonto" name="rbtInicial" onclick="byId('txtDescuento').readOnly = true; byId('txtSubTotalDescuento').readOnly = false;" style="display:none" value="2">
                                
                            	<input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" onblur="setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/>
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
                            <td class="tituloCampo">Total Factura:</td>
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
        <tr id="trFormaDePago">
			<td width="100%">
            <fieldset><legend class="legend">Forma de Pago</legend>
            <form id="frmDetallePago" name="frmDetallePago" style="margin:0">
				<table border="0" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="12%">Forma de Pago:</td>
                    <td id="tdselTipoPago" width="26%"></td>
                    <td rowspan="7" valign="top" width="62%">
                    	<table width="100%">
                        <tr>
                        	<td width="20%"></td>
                            <td width="16%"></td>
                        	<td width="20%"></td>
                            <td width="44%"></td>
                        </tr>
                        <tr id="trTipoTarjeta" style="display:none;">
                            <td align="right" class="tituloCampo" scope="row">Tipo Tarjeta:</td>
                            <td id="tdtarjeta" colspan="4" scope="row">
                                <select id="tarjeta" name="tarjeta" style="width:200px">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="trPorcentajeRetencion" style="display:none;">
                            <td align="right" class="tituloCampo">Porcentaje Retenci&oacute;n:</td>
                            <td scope="row">
                                <input type="text" id="porcentajeRetencion" name="porcentajeRetencion" readonly="readonly" size="10" style="text-align:right; background-color:#EEEEEE;"/>
                            </td>
                            <td align="right" class="tituloCampo" scope="row">Monto:</td>
                            <td scope="row">
                                <input type="text" id="montoTotalRetencion" name="montoTotalRetencion" readonly="readonly" size="19" style="text-align:right; background-color:#EEEEEE;"/>
                            </td>
                        </tr>
                        <tr id="trPorcentajeComision" style="display:none;">
                            <td align="right" class="tituloCampo">Porcentaje Comisi&oacute;n:</td>
                            <td scope="row">
                                <input type="text" id="porcentajeComision" name="porcentajeComision" readonly="readonly" size="10" style="text-align:right; background-color:#EEEEEE;" value="0.00"/>
                            </td>
                            <td align="right" class="tituloCampo" scope="row">Monto:</td>
                            <td scope="row">
                                <input type="text" id="montoTotalComision" name="montoTotalComision" readonly="readonly" size="19" style="text-align:right; background-color:#EEEEEE;" value="0.00"/>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr id="trBancoFechaDeposito" align="left">
                    <td id="tdEtiquetaBancoFechaDeposito" align="right" class="tituloCampo">Banco Cliente:</td>
                    <td scope="row">
                    	<div id="tdselBancoCliente">
                            <select id="selBancoCliente" name="selBancoCliente" style="width:200px">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </div>
                        <input type="text" id="txtFechaDeposito" name="txtFechaDeposito" autocomplete="off" size="10" style="text-align:center"/>
                    </td>
                </tr>
                <tr id="trBancoCompania" align="left">
                    <td align="right" class="tituloCampo">Banco Compa&ntilde;ia:</td>
                    <td id="tdselBancoCompania">
                        <select id="selBancoCompania" name="selBancoCompania" style="width:200px">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr id="trNumeroCuenta" align="left">
                    <td align="right" class="tituloCampo">Nro. de Cuenta:</td>
                    <td>
                        <div id="divselNumeroCuenta" style="display:none">
                            <select id="selNumeroCuenta" name="selNumeroCuenta" style="width:200px">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </div>
			<input type="text" id="txtNumeroCuenta" name="txtNumeroCuenta" size="30"/>
                    </td>
                </tr>
                <tr id="trNumeroDocumento" align="left">
                    <td id="tdNumeroDocumento" align="right" class="tituloCampo">Nro.:</td>
                    <td>
                    	<table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtNumeroDctoPago" name="txtNumeroDctoPago"/></td>
                        	<td>
                            <a class="modalImg" id="btnAgregarDetAnticipoNotaCreditoChequeTransferencia" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png"/></td><td>&nbsp;</td><td>Buscar Documento</td></tr></table></button>
                            </a>
                            <a class="modalImg" id="btnAgregarDetDeposito" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblDeposito', 'Deposito');">
                                <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/money_add.png"/></td><td>&nbsp;</td><td>Agregar Detalle Deposito</td></tr></table></button>
                            </a>
                            </td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddIdAnticipoNotaCreditoChequeTransferencia" name="hddIdAnticipoNotaCreditoChequeTransferencia"/>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Monto:</td>
                    <td>
                    	<table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        	<td><input type="text" id="txtMontoPago" name="txtMontoPago" class="inputHabilitado" onblur="setFormatoRafk(this,2); calcularPorcentajeTarjetaCredito();" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                            <td><button type="button" id="btnGuardarDetallePago" name="btnGuardarDetallePago" onclick="validarFrmDetallePago();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Pago</td></tr></table></button></td>
                        </tr>
                        </table>
                    </td>
                </tr>
				</table>
                <input type="hidden" id="hddObjDetallePago" name="hddObjDetallePago"/>
                <input type="hidden" id="hddObjDetalleDeposito" name="hddObjDetalleDeposito"/>
                <input type="hidden" id="hddObjDetalleDepositoFormaPago" name="hddObjDetalleDepositoFormaPago"/>
                <input type="hidden" id="hddObjDetalleDepositoBanco" name="hddObjDetalleDepositoBanco"/>
                <input type="hidden" id="hddObjDetalleDepositoNroCuenta" name="hddObjDetalleDepositoNroCuenta"/>
                <input type="hidden" id="hddObjDetalleDepositoNroCheque" name="hddObjDetalleDepositoNroCheque"/>
                <input type="hidden" id="hddObjDetalleDepositoMonto" name="hddObjDetalleDepositoMonto"/>
            </form>
            
            <form id="frmListaPagos" name="frmListaPagos" style="margin:0">
                <fieldset><legend class="legend">Desglose de Pagos</legend>
                    <table width="100%">
                    <tr align="center" class="tituloColumna">
                    	<td></td>
                        <td><img src="../img/iconos/information.png" title="Mostrar en pagos de la factura"/></td>
                        <td><img src="../img/iconos/information.png" title="En la copia del banco sumar al: &#10;C = Pago de Contado &#10;T = Trade In"/></td>
                        <td width="12%">Forma de Pago</td>
                        <td width="48%">Nro. Tranferencia / Cheque / Anticipo / Nota Crédito</td>
                        <td width="15%">Banco Cliente / Cuenta Cliente</td>
                        <td width="15%">Banco Compañia / Cuenta Compañia</td>
                        <td width="10%">Monto</td>
                        <td></td>
                    </tr>
                    <tr id="trItmPiePago" class="trResaltarTotal">
                    	<td align="right" class="tituloCampo" colspan="7">Total Pagos:</td>
                        <td><input type="text" id="txtMontoPagadoFactura" name="txtMontoPagadoFactura" class="inputSinFondo" readonly="readonly" style="text-align:right" value="0.00"/></td>
                        <td></td>
                    </tr>
                    <tr class="trResaltarTotal3">
                    	<td align="right" class="tituloCampo" colspan="7">Total Faltante:</td>
                        <td><input type="text" id="txtMontoPorPagar" name="txtMontoPorPagar" class="inputSinFondo" readonly="readonly" style="text-align:right;" value="0.00"/></td>
                        <td></td>
                    </tr>
                    </table>
                </fieldset>
            </form>
            </fieldset>
			</td>
		</tr>
		<tr align="right">
			<td colspan="8"><hr>
            			<a class="modalImg" id="aAgregarArticulo" rel="#divFlotante1"></a>
                		<button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                		<button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('cj_factura_venta_list.php','_self');" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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

<form id="frmListaItemPedido" name="frmListaItemPedido" onsubmit="return false;" style="margin:0">
    <div id="tblListaItemPedido" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td>
                <table align="right" border="0">
                <tr>
                    <td align="right" class="tituloCampo" width="140">Nro. Pedido:</td>
                    <td>
                        <input type="hidden" id="txtIdPedidoItems" name="txtIdPedidoItems"/>
                        <input type="text" id="txtNumeroPedidoItems" name="txtNumeroPedidoItems" readonly="readonly" style="text-align:center"/>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td><div id="divListaItemsPedido" style="width:100%"></div></td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">Seleccione los Items a Facturar</td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="submit" id="btnGuardarListaItemPedido" name="btnGuardarListaItemPedido" onclick="validarFrmListaItemPedido();">Aceptar</button>
                <button type="button" id="btnCancelarListaItemPedido" name="btnCancelarListaItemPedido" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>

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
        <td>
            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
            <tr>
                <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                <td align="center">
                    <table>
                    <tr>
                        <td>Para seleccionar multiples impuestos se debe presionar la tecla Ctrl</td>
                    </tr>
                    </table>
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
    
<form id="frmDeposito" name="frmDeposito" style="margin:0">
	<table border="0" id="tblDeposito" width="760">
    <tr>
    	<td width="20%"></td>
    	<td width="80%"></td>
    </tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Forma de Pago:</td>
		<td id="tdlstTipoPago">
			<select id="lstTipoPago" name="lstTipoPago" style="width:200px">
				<option value="-1">[ Seleccione ]</option>
			</select>
		</td>
	</tr>
	<tr id="trBancoCliente" align="left" style="display:none">
		<td align="right" class="tituloCampo">Banco:</td>
		<td id="tdlstBancoDeposito">
			<select id="lstBancoDeposito" name="lstBancoDeposito" style="width:200px">
				<option value="-1">[ Seleccione ]</option>
			</select>
		</td>
	</tr>
	<tr id="trNroCuenta" align="left" style="display:none">
		<td align="right" class="tituloCampo">Nro. Cuenta:</td>
		<td><input type="text" name="txtNroCuentaDeposito" id="txtNroCuentaDeposito" size="30"/></td>
	</tr>
	<tr id="trNroCheque" align="left" style="display:none">
		<td align="right" class="tituloCampo">Nro. Cheque:</td>
		<td><input type="text" name="txtNroChequeDeposito" id="txtNroChequeDeposito"/></td>
	</tr>
	<tr id="trMonto" align="left" style="display:none">
		<td align="right" class="tituloCampo">Monto:</td>
		<td>
        	<table border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" name="txtMontoDeposito" id="txtMontoDeposito" class="inputHabilitado" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                <td><button type="button" id="btnGuardarDetalleDeposito" name="btnGuardarDetalleDeposito" onclick="validarFrmDetalleDeposito();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Pago</td></tr></table></button></td>
            </tr>
            </table>
        </td>
	</tr>
	<tr>
		<td colspan="2">
			<table border="0" width="100%">
			<tr class="tituloColumna" align="center">
            	<td></td>
				<td width="20%">Forma de Pago</td>
				<td width="20%">Banco</td>
				<td width="20%">Nro. Cuenta</td>
				<td width="20%">Nro. Cheque</td>
				<td width="20%">Monto</td>
				<td>&nbsp;</td>
			</tr>
			<tr id="trItmPieDeposito" class="trResaltarTotal">
                <td align="right" class="tituloCampo" colspan="5">Total Pagos:</td>
                <td><input type="text" id="txtTotalDeposito" name="txtTotalDeposito" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                <td></td>
            </tr>
            <tr class="trResaltarTotal3">
                <td align="right" class="tituloCampo" colspan="5">Total Faltante:</td>
                <td><input type="text" id="txtSaldoDepositoBancario" name="txtSaldoDepositoBancario" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                <td></td>
            </tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
        	<input type="hidden" id="hddObjDetallePagoDeposito" name="hddObjDetallePagoDeposito"/>
			<button type="button" id="btnGuardarDeposito" name="btnGuardarDeposito" onclick="validarFrmDeposito();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
            <button type="button" id="btnCancelarDeposito" name="btnCancelarDeposito" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
	
	<table border="0" id="tblLista" width="960">
	<tr id="trBuscarAnticipoNotaCreditoChequeTransferencia">
		<td>
        <form id="frmBuscarAnticipoNotaCreditoChequeTransferencia" name="frmBuscarAnticipoNotaCreditoChequeTransferencia" onsubmit="return false;" style="margin:0">
			<table align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioAnticipoNotaCreditoChequeTransferencia" name="txtCriterioAnticipoNotaCreditoChequeTransferencia"/></td>
				<td>
					<button type="submit" id="btnBuscarAnticipoNotaCreditoChequeTransferencia" name="btnBuscarAnticipoNotaCreditoChequeTransferencia" onclick="xajax_buscarAnticipoNotaCreditoChequeTransferencia(xajax.getFormValues('frmBuscarAnticipoNotaCreditoChequeTransferencia'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmListaPagos'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarAnticipoNotaCreditoChequeTransferencia'].reset(); byId('btnBuscarAnticipoNotaCreditoChequeTransferencia').click();">Limpiar</button>
				</td>
			</tr>
			</table>
        </form>
		</td>
	</tr>
    <tr>
    	<td>
        <form id="frmLista" name="frmLista" onsubmit="return false;" style="margin:0">
            <table width="100%">
            <tr>
                <td><div id="divLista" style="width:100%;"></div></td>
            </tr>
            <tr>
                <td align="right"><hr>
                    <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
	</table>

<form id="frmBono" name="frmBono" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblBono" width="560">
    <tr>
        <td>
        <fieldset><legend class="legend">Cuenta por Cobrar (Bono)</legend>
            <table border="0" width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                <td colspan="2">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdClienteBono" name="txtIdClienteBono" onblur="xajax_asignarCliente('ClienteBono', this.value, '', '', '', 'true', 'false');" size="6" style="text-align:right"/></td>
                        <td><a class="modalImg" id="aListarClienteBono" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblLista2', 'Cliente', 'ClienteBono');"><button type="button" title="Listar"><img src="../img/iconos/help.png"/></button></a></td>
                        <td><input type="text" id="txtNombreClienteBono" name="txtNombreClienteBono" readonly="readonly" size="40"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo:</td>
                <td colspan="2">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdMotivoBono" name="txtIdMotivoBono" onblur="xajax_asignarMotivo(this.value, 'MotivoBono', 'CC', 'I', 'false');" size="6" style="text-align:right"/></td>
                        <td><a class="modalImg" id="aListarMotivoBono" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoBono', 'CC', 'I');"><button type="button" title="Listar"><img src="../img/iconos/help.png"/></button></a></td>
                        <td><input type="text" id="txtMotivoBono" name="txtMotivoBono" readonly="readonly" size="40"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Monto Bono:</td>
                <td width="45%"></td>
                <td align="right" width="30%"><input type="text" id="txtMontoBono" name="txtMontoBono" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
            </tr>
            <tr id="trlstAnticipoBono" align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Bono Descuento:</td>
                <td id="tdlstAnticipoBono"></td>
                <td align="right"><input type="text" id="txtMontoDescuentoBono" name="txtMontoDescuentoBono" readonly="readonly" style="text-align:right"/></td>
            </tr>
            </table>
        </fieldset>
        </td>
	</tr>
    <tr align="left">
        <td align="right" colspan="2"><hr>
            <input type="hidden" id="hddNumeroItmBono" name="hddNumeroItmBono"/>
            <button type="submit" id="btnAceptarBono" name="btnAceptarBono" onclick="validarFrmBono();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button>
            <button type="button" id="btnCancelarBono" name="btnCancelarBono" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmAnticipoNotaCreditoChequeTransferencia" name="frmAnticipoNotaCreditoChequeTransferencia" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblAnticipoNotaCreditoChequeTransferencia" width="360">
	<tr align="left">
		<td align="right" class="tituloCampo" width="40%">Nro. Documento:</td>
		<td width="60%"><input type="text" id="txtNroDocumento" name="txtNroDocumento" readonly="readonly" size="20" style="text-align:center"/></td>
	</tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Saldo:</td>
		<td><input type="text" id="txtSaldoDocumento" name="txtSaldoDocumento" readonly="readonly" style="text-align:right"/></td>
	</tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Monto a Cobrar:</td>
		<td><input type="text" id="txtMontoDocumento" name="txtMontoDocumento" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
	</tr>
	<tr align="left">
		<td align="right" colspan="2"><hr>
            <input type="hidden" id="hddIdDocumento" name="hddIdDocumento"/>
			<button type="submit" id="btnAceptarAnticipoNotaCreditoChequeTransferencia" name="btnAceptarAnticipoNotaCreditoChequeTransferencia" onclick="validarFrmAnticipoNotaCreditoChequeTransferencia();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button>
			<button type="button" id="btnCancelarAnticipoNotaCreditoChequeTransferencia" name="btnCancelarAnticipoNotaCreditoChequeTransferencia" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
	
    <table border="0" id="tblLista2" style="display:none" width="960">
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
            <input type="hidden" id="hddObjDestinoCliente" name="hddObjDestinoCliente" readonly="readonly" />
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
        <form id="frmLista2" name="frmLista2" onsubmit="return false;" style="margin:0">
			<input type="hidden" id="hddNumeroItm" name="hddNumeroItm">
        	<table width="100%">
            <tr>
            	<td><div id="divLista2" style="width:100%;"></div></td>
			</tr>
            <tr>
                <td align="right"><hr>
                    <button type="submit" id="btnGuardarLista2" name="btnGuardarLista2" onclick="xajax_asignarGasto(xajax.getFormValues('frmLista2'), xajax.getFormValues('frmListaArticulo'));">Aceptar</button>
                    <button type="button" id="btnCancelarLista2" name="btnCancelarLista2" class="close">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaMotivo" width="960">
    <tr>
        <td>
        <form id="frmBuscarMotivo" name="frmBuscarMotivo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
            <input type="hidden" id="hddPagarCobrarMotivo" name="hddPagarCobrarMotivo" readonly="readonly" />
            <input type="hidden" id="hddIngresoEgresoMotivo" name="hddIngresoEgresoMotivo" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaMotivo" name="hddNomVentanaMotivo" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo" onkeyup="byId('btnBuscarMotivo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarMotivo" name="btnBuscarMotivo" onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarMotivo'].reset(); byId('btnBuscarMotivo').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaMotivo" name="frmListaMotivo" onsubmit="return false;" style="margin:0">
            <div id="divListaMotivo" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaMotivo" name="btnCancelarListaMotivo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('txtNumeroControlFactura').className = 'inputHabilitado';
byId('txtObservacion').className = 'inputHabilitado';

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDeposito").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		
		//$("#txtNumeroCuenta").maskInput("9999-9999-99-9999999999",{placeholder:" "});
		//$("#txtNroCuentaDeposito").maskInput("9999-9999-99-9999999999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDeposito",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"vino"
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

xajax_cargaLstTipoPago('','1');
asignarTipoPago('1');
xajax_cargaLstBancoCompania();
<?php if (isset($_GET['id'])) { ?>
	byId('aAgregarArticulo').onclick = function (e) {
		abrirDivFlotante1(this, 'tblListaItemPedido', '<?php echo $_GET['id']; ?>');
	}
	byId('aAgregarArticulo').click();
<?php } ?>

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>