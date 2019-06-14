<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if (!(validaAcceso("cjrs_factura_venta_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_cjrs_facturacion_repuestos_form.php");
require("../controladores/ac_pg_calcular_comision.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Pago y Facturación de Repuestos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblTipoPagoCambio').style.display = 'none';
		byId('tblArticuloImpuesto').style.display = 'none';
		byId('tblDeposito').style.display = 'none';
		byId('tblLista').style.display = 'none';
		
		if (verTabla == "tblTipoPagoCambio") {
			tituloDiv1 = 'Editar Impuesto';
		} else if (verTabla == "tblArticuloImpuesto") {
			xajax_formArticuloImpuesto();
			tituloDiv1 = 'Editar Impuesto de Articulos';
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
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').focus();
			byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			byId('txtMontoDocumento').className = 'inputHabilitado';
			
			xajax_cargarSaldoDocumento(valor, valor2, xajax.getFormValues('frmListaPagos'));
			
			tituloDiv2 = '';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			byId('txtMontoDocumento').focus();
			byId('txtMontoDocumento').select();
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
	
	function cambiarTipoPago(lstTipoPagoCambio){
		base_imponible = parseFloat(byId('txtBaseImponibleOrden').value.replace(/,/g, ""));
		monto_limite = parseFloat(2000000);//dos millones
		
		if (lstTipoPagoCambio == 1 && (base_imponible <= monto_limite)) {
			xajax_cargaLstIva("lstIvaCbxCambio", 20, "", true);
			byId('txtObservacion').value = 'Se aplica rebaja de la alicuota impositiva general del IVA segun Gaceta Oficial Nro. 41.239 de fecha 19-09-2017, Decreto Nro. 3.085';
		} else if (lstTipoPagoCambio == 1 && (base_imponible > monto_limite)) {
			xajax_cargaLstIva("lstIvaCbxCambio", 18, "", true);
			byId('txtObservacion').value = 'Se aplica rebaja de la alicuota impositiva general del IVA segun Gaceta Oficial Nro. 41.239 de fecha 19-09-2017, Decreto Nro. 3.085';
		} else {
			xajax_cargaLstIva("lstIvaCbxCambio", 1, "", true);
			byId('txtObservacion').value = '';
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
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtIdPedido','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtNumeroControlFactura','t','') == true
		&& validarCampo('txtDescuento','t','numPositivo') == true
		&& validarCampo('txtMontoPorPagar','t','numPositivo') == true)) {
			validarCampo('txtIdPedido','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtNumeroControlFactura','t','');
			validarCampo('txtDescuento','t','numPositivo');
			validarCampo('txtMontoPorPagar','t','numPositivo');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm("¿Seguro desea facturar el Pedido?") == true) {
				if (byId('hddObj').value.length > 0) {
					xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'),xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListaPagos'));
					
				} else {
					alert("Debe agregar articulos al pedido");
					return false;
				}
			}
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
	
	function validarFrmImpuesto() {
		if (validarCampo('lstIvaCbxCambio','t','listaExceptCero') == true &&
			validarCampo('lstTipoPagoCambio','t','lista') == true) {
			xajax_asignarArticuloImpuesto(xajax.getFormValues('frmImpuestoCambio'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		} else {
			validarCampo('lstIvaCbxCambio','t','listaExceptCero');
			validarCampo('lstTipoPagoCambio','t','lista');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCajaRS">Pago y Facturación de Repuestos</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmDcto" name="frmDcto" style="margin:0">
				<input type="hidden" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="20"/>
				<input type="hidden" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="20"/>
				<input type="hidden" id="hddFechaPedido" name="hddFechaPedido" readonly="readonly" size="20"/>
                <table align="right" border="0" width="100%">
                <tr>
                	<td colspan="2">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="58%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 0);" size="6" style="text-align:right;"/></td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo" width="12%">Fecha Registro:</td>
                            <td width="18%"><input type="text" id="txtFechaFactura" name="txtFechaFactura" readonly="readonly" style="text-align:center" size="10"/></td>
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
                                <select id="lstTipoClave" name="lstTipoClave" onchange="xajax_cargaLstClaveMovimiento(this.value)" disabled="disabled">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">1.- COMPRA</option>
                                    <option value="2">2.- ENTRADA</option>
                                    <option value="3" selected="selected">3.- VENTA</option>
                                    <option value="4">4.- SALIDA</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td width="28%">
                                <input type="hidden" id="hddIdClaveMovimiento" name="hddIdClaveMovimiento" readonly="readonly"/>
                                <input type="text" id="txtClaveMovimiento" name="txtClaveMovimiento" readonly="readonly" size="30"/>
                            </td>
                            <td align="right" class="tituloCampo" width="12%">Tipo de Pago:</td>
                            <td width="20%">
                                <input type="hidden" id="hddTipoPago" name="hddTipoPago" readonly="readonly"/>
                                <input type="text" id="txtTipoPago" name="txtTipoPago" class="divMsjInfo2" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                        </tr>
                        </table>
                    </td>
                	<td valign="top" width="30%">
                    <fieldset><legend class="legend">Datos de la Factura</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Nro. Factura:</td>
                            <td width="60%">
                                <input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Pedido:</td>
                                        <td>
                                <input type="text" id="txtNumeroPedidoPropio" name="txtNumeroPedidoPropio" readonly="readonly" size="20" style="text-align:center"/>
                                        </td>
                                    </tr>
                                    <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Vencimiento:</td>
                            <td><input type="text" id="txtFechaVencimientoFactura" name="txtFechaVencimientoFactura" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                            <td>
                            	<input type="hidden" id="hddIdMoneda" name="hddIdMoneda"/>
                            	<input type="text" id="txtMoneda" name="txtMoneda" readonly="readonly" size="20"/>
                            </td>
                        </tr>
						<tr align="left">
							<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Vendedor:</td>
                            <td>
                                <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/>
                                <input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="20"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
                            <td nowrap="nowrap">
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
                <a class="modalImg" id="aImpuestoCambio" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblTipoPagoCambio');" style="display:none;">
                    <button type="button" title="Impuesto"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/text_signature.png"/></td><td>&nbsp;</td><td>Cambio Impuesto</td></tr></table></button>
                </a>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
                <table border="0" width="100%">
                <tr align="center" class="tituloColumna">
                    <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,'frmListaArticulo');"/></td>
                    <td width="4%">Nro.</td>
                    <td width="14%">Código</td>
                    <td width="46%">Descripción</td>
                    <td width="6%">Cant.</td>
                    <td width="8%">Cargos</td>
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
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                	<td valign="top" width="50%">
                    <fieldset id="fieldsetGastos"><legend class="legend">Cargos</legend>
                    	<table border="0" width="100%">
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
			<!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
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
                        <tr align="right">
			    <td style="border-top:1px solid;" class="tituloCampo">Exento:</td>
			    <td style="border-top:1px solid;"></td>
			    <td style="border-top:1px solid;"></td>
                            <td style="border-top:1px solid;" id="tdExentoMoneda"></td>
			    <td style="border-top:1px solid;"><input type="text" id="txtTotalExento" name="txtTotalExento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
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
        <tr>
	    <td align="right"><hr>
		<button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
		<button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('cjrs_factura_venta_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
    
<form id="frmImpuestoCambio" name="frmImpuestoCambio" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblTipoPagoCambio" style="display:none" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Base Imponible:</td>
                <td>
                	<input type="text" id="txtBaseImponibleOrden" name="txtBaseImponibleOrden" readonly="readonly" size="20" style="text-align:center">
				</td>
			</tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Pago:</td>
                <td>
                	<select id="lstTipoPagoCambio" name="lstTipoPagoCambio" onchange="cambiarTipoPago(this.value);" style="width:200px;">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="1">ELECTRÓNICO (Transferencia, Tarjeta)</option>
                        <option value="2">EFECTIVO (Efectivo, Cheques, Depósito)</option>
                        <option value="3">AMBOS</option>
                    </select>
				</td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td id="tdlstIvaCbxCambio" width="70%">
                	<select id="lstIvaCbxCambio" name="lstIvaCbxCambio">
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
                        <td>
                        	Impuesto para pagos electrónicos:<br />
                        	(Monto <= 2.000.000) = 9% impuesto<br />
                            (Monto > 2.000.000) = 7% impuesto                  
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarImpuesto" name="btnGuardarImpuesto" onclick="validarFrmImpuesto();">Aceptar</button>
        </td>
    </tr>
    </table>
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
				<td><input type="text" id="txtCriterioAnticipoNotaCreditoChequeTransferencia" name="txtCriterioAnticipoNotaCreditoChequeTransferencia" onkeyup="byId('btnBuscarAnticipoNotaCreditoChequeTransferencia').click();"/></td>
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
		cellColorScheme:"brown"
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
	xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
<?php } ?>

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>