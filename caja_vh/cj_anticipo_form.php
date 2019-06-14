<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cj_anticipo_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_anticipo_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Anticipo</title>
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
		byId('tblDeposito').style.display = 'none';
		byId('tblLista').style.display = 'none';
		byId('tblListaAnticipo').style.display = 'none';
		
		if (verTabla == "tblDeposito") {
			if (valor == "Deposito") {
				if (validarCampo('txtTotalAnticipo','t','monto') == true
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
					validarCampo('txtTotalAnticipo','t','monto') == true
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
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('trBuscarCliente').style.display = '';
				byId('trBuscarAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('btnBuscarCliente').click();
				
				tituloDiv1 = 'Clientes';
				byId(verTabla).width = "960";
			} else if (valor == "AnticipoNotaCreditoChequeTransferencia") {
				document.forms['frmBuscarAnticipoNotaCreditoChequeTransferencia'].reset();
				
				byId('trBuscarCliente').style.display = 'none';
				byId('trBuscarAnticipoNotaCreditoChequeTransferencia').style.display = '';
				
				byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').className = 'inputHabilitado';
			
				xajax_buscarAnticipoNotaCreditoChequeTransferencia(xajax.getFormValues('frmBuscarAnticipoNotaCreditoChequeTransferencia'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmListaPagoDcto'));
				
				tituloDiv1 = 'Listado';
			}
		} else if (verTabla == "tblListaAnticipo") {
			document.forms['frmBuscarAnticipo'].reset();
			
			byId('txtFechaDesde').className = 'inputHabilitado';
			byId('txtFechaHasta').className = 'inputHabilitado';
			byId('txtCriterioBuscarAnticipo').className = 'inputHabilitado';
			
			/*byId('txtFechaDesde').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
			byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";*/
			
			selectedOption('lstTipoDcto', valor);
			byId('lstTipoDcto').onchange = function(){ selectedOption(this.id, valor); }
			byId('lstTipoDcto').className = 'inputInicial';
			
			byId('btnBuscarAnticipo').click();
			
			tituloDiv1 = 'Dctos. Por Cobrar';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			} else if (valor == "AnticipoNotaCreditoChequeTransferencia") {
				byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').focus();
				byId('txtCriterioAnticipoNotaCreditoChequeTransferencia').select();
			}
		} else if (valor == "tblListaAnticipo") {
			byId('txtCriterioBuscarAnticipo').focus();
			byId('txtCriterioBuscarAnticipo').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			byId('txtMontoDocumento').className = 'inputHabilitado';
			
			if (inArray(valor, ['AN','FA','ND'])) {
				xajax_cargarSaldoDocumentoPagar(valor, valor2, xajax.getFormValues('frmListaDctoPagado'));
			} else {
				xajax_cargarSaldoDocumento(valor, valor2, xajax.getFormValues('frmListaPagoDcto'));
			}
			
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
		
		byId('trConceptoPago').style.display = 'none';
		
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
		
		xajax_cargaLstConceptoPago("selConceptoPago");
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
				byId('tdNumeroDocumento').innerHTML = 'Nro. Referencia:';
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
			case '8' : // NOTA CREDITO
				byId('trNumeroDocumento').style.display = '';
				byId('txtNumeroDctoPago').className = 'inputInicial';
				byId('txtNumeroDctoPago').readOnly = true;
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').style.display = '';
				
				byId('btnGuardarDetallePago').style.display = 'none';
				break;
			case '11' :  // OTRO
				byId('trConceptoPago').style.display = '';
				
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
	
	function calcularPagos(){
		/*$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado)').removeClass();//limpio de clases
		$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado):odd').addClass('trResaltar5');//odd impar
		$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado):even').addClass('trResaltar4');//even par*/
		
		$('tr[name=trItmDctoPagado]').removeClass();//limpio de clases
		$('tr[name=trItmDctoPagado]:odd').addClass('trResaltar5');//odd impar
		$('tr[name=trItmDctoPagado]:even').addClass('trResaltar4');//even par
		
		xajax_calcularPagos(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'));
	}
	
	function calcularPorcentajeTarjetaCredito() {
		if (byId('selTipoPago').value == 5) {
			byId('montoTotalRetencion').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeRetencion').value) / 100,2);
			byId('montoTotalComision').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeComision').value) / 100,2);
		} else if (byId('selTipoPago').value) {
			byId('montoTotalComision').value = formatoRafk(parseNumRafk(byId('txtMontoPago').value) * parseNumRafk(byId('porcentajeComision').value) / 100,2);
		}
		xajax_calcularPagos(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'));
	}
	
	function confirmarEliminarPago(pos) {
		if (confirm("Desea eliminar el pago?"))
			xajax_eliminarPago(xajax.getFormValues('frmListaPagoDcto'),pos);
	}
	
	function confirmarEliminarPagoDetalleDeposito(pos) {
		if (confirm("Desea eliminar el detalle del deposito?"))
			xajax_eliminarPagoDetalleDeposito(xajax.getFormValues('frmDeposito'),pos);
	}
	
	function validarFrmAgregarDcto(tipoDcto) {
		error = false;
		if (!(validarCampo('txtIdCliente', 't', '') == true
		&& validarCampo('lstModulo', 't', 'listaExceptCero') == true
		&& validarCampo('txtTotalAnticipo', 't', 'monto') == true)) {
			validarCampo('txtIdCliente', 't', '');
			validarCampo('lstModulo', 't', 'listaExceptCero');
			validarCampo('txtTotalAnticipo', 't', 'monto');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos");
			return false;
		} else {
			abrirDivFlotante1(byId('aAgregarFactura'), 'tblListaAnticipo', tipoDcto);
		}
	}
	
	function validarFrmAnticipo(){
		error = false;
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtFecha','t','fecha') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('txtObservacion','t','') == true
		&& validarCampo('txtTotalAnticipo','t','monto') == true)){
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtFecha','t','fecha');
			validarCampo('lstModulo','t','listaExceptCero');
			validarCampo('txtObservacion','t','');
			validarCampo('txtTotalAnticipo','t','monto');
			
			error = true;
		}
		
		if (byId('selTipoPago').value > 0 && parseNumRafk(byId('txtMontoPago').value) > 0) {
			byId('fieldsetFormaPago').className = 'divMsjError';
			
			alert("Existe un pago que no a terminado de agregar");
			return false;
		}
		
		if (parseNumRafk(byId('txtTotalAnticipo').value) == parseNumRafk(byId('txtMontoPorPagar').value) && !byId('cbxPiePago')) {
			byId('fieldsetDesglosePago').className = 'divMsjError';
			if (!confirm('El anticipo no tiene pagos cargados. ¿Seguro desea registrarlo como No Cancelado?')) {
				return false;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_calcularPagos(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'));
			
			if (byId('hddIdAnticipo').value > 0) {
				if (confirm("¿Seguro desea guardar el(los) pago(s) del anticipo?")) {
					xajax_guardarAnticipo(xajax.getFormValues('frmDcto'),xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListaPagoDcto'),xajax.getFormValues('frmListaDctoPagado'));
				}
			} else {
				if (confirm("¿Seguro desea generar el anticipo?")) {
					if (confirm("¿Desea crear otro anticipo con el mismo cliente?") == true) {
						byId('hddNuevoAnticipo').value = 1;
					}
					xajax_guardarAnticipo(xajax.getFormValues('frmDcto'),xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListaPagoDcto'),xajax.getFormValues('frmListaDctoPagado'));
				}
			}
		}
	}
	
	function validarFrmAnticipoNotaCreditoChequeTransferencia() {
		var saldo = parseNumRafk(byId('txtSaldoDocumento').value);
		var monto = parseNumRafk(byId('txtMontoDocumento').value);
		var valor = byId('hddTipoDocumento').value;
		
		if (parseFloat(saldo) < parseFloat(monto)) {
			alert("El monto a pagar no puede ser mayor que el saldo del documento");
		} else {
			if (inArray(valor, ['AN','FA','ND'])) {
				var montoFaltaPorPagar = parseNumRafk(byId('txtMontoRestante').value);
				
				if (parseFloat(montoFaltaPorPagar) >= parseFloat(monto)) {
					if (confirm("Desea cargar el pago?")) {
						xajax_insertarDctoPagado(xajax.getFormValues('frmAnticipoNotaCreditoChequeTransferencia'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmListaAnticipo'));
					}
				} else {
					alert("El monto a pagar no puede ser mayor que el saldo disponible");
				}
			} else {
				var montoFaltaPorPagar = parseNumRafk(byId('txtMontoPorPagar').value);
				
				if (parseFloat(montoFaltaPorPagar) >= parseFloat(monto)) {
					if (confirm("Desea cargar el pago?")) {
						byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = byId('hddIdDocumento').value;
						byId('txtNumeroDctoPago').value = byId('txtNroDocumento').value;
						byId('txtMontoPago').value = byId('txtMontoDocumento').value;
						
						error = false;
						if (!(validarCampo('txtTotalAnticipo','t','monto') == true
						&& validarCampo('txtMontoPago','t','monto') == true
						&& validarCampo('txtNumeroDctoPago','t','') == true
						&& validarCampo('txtMontoDocumento','t','monto') == true)) {
							validarCampo('txtTotalAnticipo','t','monto');
							validarCampo('txtMontoPago','t','monto');
							validarCampo('txtNumeroDctoPago','t','');
							validarCampo('txtMontoDocumento','t','monto');
							
							error = true;
						}
						
						if (error == true) {
							alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
							return false;
						} else {
							xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));
						}
					}
				} else {
					alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
				}
			}
		}
	}
	
	function validarFrmDeposito() {
		if (byId('txtSaldoDepositoBancario').value == 0) {
			xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDcto'));
		} else {
			alert("El saldo del detalle del deposito debe ser 0 (cero)");
		}
	}
	
	function validarFrmDetallePago() {
		error = false;
		if (byId('selTipoPago').value == 1) { // EFECTIVO
			if (!(validarCampo('txtTotalAnticipo','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true)) {
				validarCampo('txtTotalAnticipo','t','monto');
				validarCampo('txtMontoPago','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 2) { // CHEQUES
			if (!(validarCampo('txtTotalAnticipo','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('txtNumeroCuenta','t','') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalAnticipo','t','monto');
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
				xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 4) { // TRANSFERENCIA
			if (!(validarCampo('txtTotalAnticipo','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalAnticipo','t','monto');
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
				xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 5) { // TARJETA DE CREDITO
			if (!(validarCampo('txtTotalAnticipo','t','monto') == true
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
				validarCampo('txtTotalAnticipo','t','monto');
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
				xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 6) { // TARJETA DE DEBITO
			if (!(validarCampo('txtTotalAnticipo','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)) {
				validarCampo('txtTotalAnticipo','t','monto');
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
				xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 9) { // RETENCION
			if (!(validarCampo('txtTotalAnticipo','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalAnticipo','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 10) { // RETENCION ISLR
			if (!(validarCampo('txtTotalAnticipo','t','monto') == true
			&& validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('txtNumeroDctoPago','t','') == true)) {
				validarCampo('txtTotalAnticipo','t','monto');
				validarCampo('txtMontoPago','t','monto');
				validarCampo('txtNumeroDctoPago','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmTotalDcto'));
			}
		} else if (byId('selTipoPago').value == 11){ // OTRO
			if (!(validarCampo('txtMontoPago','t','monto') == true
			&& validarCampo('selConceptoPago','t','lista') == true)){
				validarCampo('txtMontoPago','t','monto');
				validarCampo('selConceptoPago','t','lista');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				xajax_insertarPago(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDcto'));
			}
		} else {
			if (!(validarCampo('txtTotalAnticipo','t','monto') == true
			&& validarCampo('selTipoPago','t','lista') == true)) {
				validarCampo('txtTotalAnticipo','t','monto');
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
	
	function validarEliminarDcto(objBoton){
		if (confirm('¿Seguro desea eliminar el documento seleccionado?') == true) {
			 $(objBoton).closest('tr').remove();
			 calcularPagos();
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
			<td class="tituloPaginaCaja">Anticipo</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
			<form id="frmDcto" name="frmDcto" style="margin:0">
                <table border="0" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
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
                    <td width="12%"></td>
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
                            
                            <fieldset><legend class="legend">Observación</legend>
                                <table>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Observación:</td>
                                    <td><textarea name="txtObservacion" id="txtObservacion" cols="60" rows="2"></textarea></td>
                                </tr>
                                </table>
                            </fieldset>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Anticipo</legend>
                                <input type="hidden" id="hddIdAnticipo" name="hddIdAnticipo"/>
                                <input type="hidden" id="hddNuevoAnticipo" name="hddNuevoAnticipo"/>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Anticipo:</td>
                                    <td colspan="2">
                                        <div id="tdlstTipoAnticipo"></div>
                                        <input type="hidden" id="hddTipoAnticipo" name="hddTipoAnticipo"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Módulo:</td>
                                    <td id="tdlstModulo" colspan="2" width="60%"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Anticipo:</td>
                                    <td colspan="2"><input type="text" id="txtNumeroAnticipo" name="txtNumeroAnticipo" placeholder="Por Asignar" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Fecha Registro:</td>
                                    <td><input type="text" id="txtFecha" name="txtFecha" readonly="readonly" size="10" style="text-align:center"/></td>
                                </tr>
                                <tr align="right" class="trResaltarTotal">
                                    <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto:</td>
                                    <td id="tdTotalRegistroMoneda"></td>
                                    <td><input type="text" id="txtTotalAnticipo" name="txtTotalAnticipo" size="20" style="text-align:right" onblur="setFormatoRafk(this,2); xajax_calcularPagos(xajax.getFormValues('frmListaPagoDcto'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'))"/></td>
                                </tr>
                                <tr align="right" class="trResaltarTotal3">
                                    <td class="tituloCampo">Saldo Disponible:</td>
                                    <td id="tdTotalSaldoMoneda"></td>
                                    <td><input type="text" id="txtTotalSaldo" name="txtTotalSaldo" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td id="tdtxtEstatus" colspan="3"><input type="text" id="txtEstatus" name="txtEstatus" class="inputSinFondo" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
                                </table>
                            </fieldset>
                            `	
                                <table align="left" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <button type="button" id="btnAnticipoPDF"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_white_acrobat.png"/></td><td>&nbsp;</td><td>Anticipo PDF</td></tr></table></button>
                                    </td>
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
		<tr id="trListaAnticipoNoCancelado" style="display:none">
        	<td>
            <fieldset id="fieldsetAnticipoNoCancelado"><legend class="legend">Anticipos No Cancelados</legend>
            	<div id="divListaAnticipoNoCancelado" style="width:100%"></div>
            </fieldset>
            </td>
		</tr>
		<tr>
			<td width="100%">
            <fieldset id="fieldsetFormaPago"><legend class="legend">Forma de Pago</legend>
            <form id="frmDetallePago" name="frmDetallePago" style="margin:0">
				<table id="tblFormaPago" border="0" width="100%">
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
                <tr id="trConceptoPago" align="left">
                    <td align="right" class="tituloCampo" width="120">Concepto de Pago:</td>
                    <td id="tdselConceptoPago">
                        <select id="selConceptoPago" name="selConceptoPago" style="width:200px">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
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
                            <a class="modalImg" id="btnAgregarDetAnticipoNotaCreditoChequeTransferencia" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'AnticipoNotaCreditoChequeTransferencia');">
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
                <input type="hidden" id="hddMontoFaltaPorPagar" name="hddMontoFaltaPorPagar"/>
            </form>
            
            <form id="frmListaPagoDcto" name="frmListaPagoDcto" style="margin:0">
                <fieldset id="fieldsetDesglosePago"><legend class="legend">Desglose de Pagos</legend>
                    <table width="100%">
                    <tr align="center" class="tituloColumna">
                    	<td></td>
                        <td width="12%">Forma de Pago</td>
                        <td width="48%">Nro. Tranferencia / Cheque / Anticipo / Nota Crédito</td>
                        <td width="15%">Banco Cliente / Cuenta Cliente</td>
                        <td width="15%">Banco Compañia / Cuenta Compañia</td>
                        <td width="10%">Monto</td>
                        <td></td>
                    </tr>
                    <tr id="trItmPiePago" class="trResaltarTotal">
                    	<td align="right" class="tituloCampo" colspan="5">Total Pagos:</td>
                        <td><input type="text" id="txtMontoPagadoAnticipo" name="txtMontoPagadoAnticipo" class="inputSinFondo" readonly="readonly" style="text-align:right" value="0.00"/></td>
                        <td></td>
                    </tr>
                    <tr class="trResaltarTotal3">
                    	<td align="right" class="tituloCampo" colspan="5">Total Faltante:</td>
                        <td>
                            <input type="text" id="txtMontoPorPagar" name="txtMontoPorPagar" class="inputSinFondo" readonly="readonly" style="text-align:right;" value="0.00"/>
                            <input type="hidden" name="hddSaldoAnticipo" id="hddSaldoAnticipo"/>
                        </td>
                        <td></td>
                    </tr>
                    </table>
                </fieldset>
            </form>
            </fieldset>
			</td>
		</tr>
        <tr id="trListaDctoPagado">
			<td width="100%">
			<form id="frmListaDctoPagado" name="frmListaDctoPagado" onsubmit="return false;">
				<fieldset><legend class="legend">Dctos. Por Cobrar</legend>
                
                <table border="0" >
                <tr>
                    <td align="left">
                        <button type="button" id="btnAgregarFactura" onclick="validarFrmAgregarDcto('FACTURA');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td id="tituloFacturas">Agregar Factura a Cobrar</td></tr></table></button>
            			<a class="modalImg" id="aAgregarFactura" rel="#divFlotante1"></a>
                        
                        <button type="button" id="btnAgregarNotaDebito" onclick="validarFrmAgregarDcto('NOTA DEBITO');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td id="tituloNotaDebitos">Agregar Nota de Débito a Cobrar</td></tr></table></button>
            			<a class="modalImg" id="aAgregarNotaDebito" rel="#divFlotante1"></a>
                    </td>
                </tr> 
                </table>
                    
                <table width="100%">
                <tr>
                    <td>
                        <table width="100%" id="tablaAnticiposAgregados">
                        <tr align="center" class="tituloColumna">
                        	<td></td>
                			<td width="4%">Nro.</td>
                            <td width="8%">Fecha Pago</td>
                            <td width="16%">Empresa</td>
                            <td width="10%">Dcto. Pagado</td>
                            <td width="8%">Fecha Registro Dcto.</td>
                            <td width="10%">Nro. Dcto.</td>
                            <td width="24%">Cliente</td>
                            <td width="10%">Estado Dcto.</td>
                            <td width="10%">Monto</td>
                            <td></td>
                        </tr>
                        <tr id="trItmPieDctoPagado" class="trResaltarTotal">
                            <td align="right" class="tituloCampo" colspan="9">Total Dctos. Pagados:</td>
                            <td><input type="text" id="txtTotalDctoPagado" name="txtTotalDctoPagado" class="inputSinFondo" readonly="readonly" style="text-align:right" value="0.00"/></td>
                            <td></td>
                        </tr>
                        <tr class="trResaltarTotal3">
                            <td align="right" class="tituloCampo" colspan="9">Total Saldo Disponible:</td>
                            <td>
                                <input type="text" id="txtMontoRestante" name="txtMontoRestante" class="inputSinFondo" readonly="readonly" style="text-align:right;" value="0.00"/>
                                <input type="hidden" id="hddSaldoAnticipo" name="hddSaldoAnticipo"/>
                                <input type="hidden" id="hddMontoPorPagar" name="hddMontoPorPagar"/>
                            </td>
                            <td></td>
                        </tr>
                        </table>                
                    </td>
                </tr>
                </table>
				</fieldset>
			</form>
			</td>
		</tr>
		<tr align="right">
			<td colspan="8"><hr>
				<button type="button" id="btnGuardarPago" name="btnGuardarPago" onclick="validarFrmAnticipo();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
				<button type="button" id="btnCancelar" name="btnCancelar" onclick="top.history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
	<tr id="trBuscarAnticipoNotaCreditoChequeTransferencia">
		<td>
        <form id="frmBuscarAnticipoNotaCreditoChequeTransferencia" name="frmBuscarAnticipoNotaCreditoChequeTransferencia" onsubmit="return false;" style="margin:0">
			<table align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioAnticipoNotaCreditoChequeTransferencia" name="txtCriterioAnticipoNotaCreditoChequeTransferencia" onkeyup="byId('btnBuscarAnticipoNotaCreditoChequeTransferencia').click();"/></td>
				<td>
					<button type="submit" id="btnBuscarAnticipoNotaCreditoChequeTransferencia" name="btnBuscarAnticipoNotaCreditoChequeTransferencia" onclick="xajax_buscarAnticipoNotaCreditoChequeTransferencia(xajax.getFormValues('frmBuscarAnticipoNotaCreditoChequeTransferencia'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmListaPagoDcto'));">Buscar</button>
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
			<input type="hidden" id="hddNumeroItm" name="hddNumeroItm"/>
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
    
<div id="tblListaAnticipo" style="max-height:520px; overflow:auto; width:960px;">
    <table border="0" width="100%">
	<tr id="trBuscarAnticipo">
		<td>
        <form id="frmBuscarAnticipo" name="frmBuscarAnticipo" onsubmit="return false;" style="margin:0">
			<table align="right">
			<tr align="left">
				<td align="right" class="tituloCampo" width="120">Tipo de Dcto.:</td>
                <td id="tdlstTipoDcto"></td>
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
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioBuscarAnticipo" name="txtCriterioBuscarAnticipo" onkeyup="byId('btnBuscarAnticipo').click();"/></td>
				<td>
					<button type="button" id="btnBuscarAnticipo" onclick="xajax_buscarAnticipo(xajax.getFormValues('frmBuscarAnticipo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaDctoPagado'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarAnticipo'].reset(); byId('btnBuscarAnticipo').click();">Limpiar</button>
				</td>
			</tr>
			</table>
        </form>
		</td>
	</tr>
	<tr>
		<td>
        <form id="frmListaAnticipo" name="frmListaAnticipo" onsubmit="return false;" style="margin:0">
			<table width="100%">
			<tr>
				<td id="divListaAnticipo"></td>
			</tr>
			<tr>
				<td align="right"><hr>
					<button type="button" id="btnCancelarListaAnticipo" name="btnCancelarListaAnticipo" class="close">Cerrar</button>
				</td>
			</tr>
			</table>
        </form>
		</td>
	</tr>
	</table>
</div>
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
		<td align="right" class="tituloCampo">Saldo Diferido:</td>
		<td><input type="text" id="txtSaldoDiferidoDocumento" name="txtSaldoDiferidoDocumento" readonly="readonly" style="text-align:right"/></td>
	</tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Monto a Cobrar:</td>
		<td><input type="text" id="txtMontoDocumento" name="txtMontoDocumento" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
            <input type="hidden" id="hddIdDocumento" name="hddIdDocumento"/>
            <input type="hidden" id="hddTipoDocumento" name="hddTipoDocumento"/>
			<button type="submit" id="btnAceptarAnticipoNotaCreditoChequeTransferencia" name="btnAceptarAnticipoNotaCreditoChequeTransferencia" onclick="validarFrmAnticipoNotaCreditoChequeTransferencia();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button>
			<button type="button" id="btnCancelarAnticipoNotaCreditoChequeTransferencia" name="btnCancelarAnticipoNotaCreditoChequeTransferencia" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
</div>

<script language="javascript">
window.onload = function(){
	jQuery(function($){
		$("#txtFechaDeposito").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		
		//$("#txtNumeroCuenta").maskInput("9999-9999-99-9999999999",{placeholder:" "});
		//$("#txtNroCuentaDeposito").maskInput("9999-9999-99-9999999999",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDeposito",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"vino"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"vino"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
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

xajax_cargarDcto('<?php echo $_GET['id']; ?>', '', '', '<?php echo $_GET['vw']; ?>', xajax.getFormValues('frmListaPagoDcto'));

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>