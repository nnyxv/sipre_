<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_factura_venta_list","insertar"))) {
	echo "<script>alert('Acceso Denegado');	top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_cjrs_facturacion_devolucion_servicios_form.php");
require("../controladores/ac_pg_calcular_comision_servicio.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO
$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE 2.0 :. Caja de Repuestos y Servicios - Pago y Facturación de Servicios</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
	<link rel="stylesheet" type="text/css" href="clases/styleRafkLista.css">
	
	<script type="text/javascript" language="javascript" src="../js/mootools.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
		
	<link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
	<script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
	<script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/jquery.js" ></script>
	<script type="text/javascript" language="javascript" src="../js/jquery.maskedinput.js" ></script>
	<script type="text/javascript" language="javascript" src="../vehiculos/vehiculos.inc.js"></script>
		
	<script>
	jQuery.noConflict();
		jQuery(function($){
			//$("#numeroCuenta").mask("9999-9999-99-9999999999",{placeholder:" "});
			//$("#txtNroCuentaDeposito").mask("9999-9999-99-9999999999",{placeholder:" "});
		});
	</script>
	
	<script>
	function setestaticpopup(url,marco,_w,_h){
		var x = (screen.width - _w) / 2;
		var y = (screen.height - _h) / 2;
		var r= window.open(url,marco,"toolbar=0,scrollbars=no,location=0,statusbar=0,menubar=0,resizable=0,width="+_w+",height="+_h+",top="+y+",left="+x+"");
		r.focus();
		return r;
	}
	
	function validarDevolucion(){	
		if (validarCampo('lstClaveMovimiento','t','lista') == true && validarCampo('txtMotivoRetrabajo','t','') == true)
		{
			if(confirm("Desea generar la Nota Credito?"))
				xajax_devolverFacturaVenta(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'));
			else
				return false;
		}
		else
		{
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtMotivoRetrabajo','t','');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	
	
	}
	
	function validarNroControl(){
		if (validarCampo('txtNroControl','t','') == true && validarCampo('lstClaveMovimiento','t','lista') == true )
		{
			if($('hddItemsNoAprobados').value == 1)
				$cadena = "La Orden tiene Items No aprobados. Estos mismos no seran reflejados en la Factura.\n";
			else
				$cadena = "";
				
			if(confirm($cadena + "Desea Generar la Factura?")){
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_guardarFactura(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListadoPagos'));
			} else
				return false;
		}
		else
		{
			validarCampo('txtNroControl','t','');
			validarCampo('lstClaveMovimiento','t','lista');
			$('txtNroControl').focus();
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarMotivoRetrabajo(){
		if (validarCampo('txtMotivoRetrabajo','t','') == true && validarCampo('txtIdValeRecepcion','t','') == true)
		{
			if(confirm("Desea Generar la Orden Tipo Retrabajo?"))
				xajax_generarDctoApartirDeOrden(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'));
		}
		else
		{
			validarCampo('txtMotivoRetrabajo','t','');
			validarCampo('txtIdValeRecepcion','t','');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function bloquearForm(){
		$('txtFechaVencimientoPresupuesto').readOnly = true;
		$('imgFechaVencimientoPresupuesto').style.visibility = 'hidden';
		$('txtNumeroPresupuestoPropio').readOnly = true;
		$('txtNumeroReferencia').readOnly = true;
		$('lstMoneda').disabled = true;
		$('btnInsertarEmp').disabled = true;
		if($('hddTipoDocumento').value == 3 || $('hddTipoDocumento').value == 4)
		{
			$('btnGuardar').disabled = false;
			$('btnCancelar').disabled = false;
		}
		else
		{
			$('btnGuardar').disabled = true;
			$('btnCancelar').disabled = true;
		
		}
	}
		
	// FUNCIONES AGREGADAS EL 10-03-2014
	function cambiar(){
		validarCampo('selTipoPago','t','lista');
		
		if (byId('selTipoPago').value == 1){ efectivo();}
		else if (byId('selTipoPago').value == 2){ cheques();}
		else if (byId('selTipoPago').value == 3){ deposito();}
		else if (byId('selTipoPago').value == 4){ transferencia();}
		else if (byId('selTipoPago').value == 5){ tarjetaCredito();}
		else if (byId('selTipoPago').value == 6){ tarjetaDebito();}
		else if (byId('selTipoPago').value == 7){ anticipo();}
		else if (byId('selTipoPago').value == 8){ notaCredito();}
		else if (byId('selTipoPago').value == 9){ retencion();}
		else if (byId('selTipoPago').value == 10){ retencionISLR();}
	}
		
	function cambiarTipoPagoDetalleDeposito(){
	if (byId('lstTipoPago').value == 1){
		byId('trBancoCliente').style.display = 'none';
		byId('trNroCuenta').style.display = 'none';
		byId('trNroCheque').style.display = 'none';
		byId('trMonto').style.display = '';
	}
	else if (byId('lstTipoPago').value == 2){
		byId('trBancoCliente').style.display = '';
		byId('trNroCuenta').style.display = '';
		byId('trNroCheque').style.display = '';
		byId('trMonto').style.display = '';}
		
	}
	
	function confirmarEliminarPago(pos){
		if(confirm("Desea elmiminar el pago?"))
			xajax_eliminarPago(xajax.getFormValues('frmListadoPagos'),pos);
	}
	
	function confirmarEliminarPagoDetalleDeposito(pos){
		if(confirm("Desea elmiminar el detalle del deposito?"))
			xajax_eliminarPagoDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'),pos);
	}
	
	function efectivo(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = 'none';
		byId('tdBancoCliente').style.display = 'none';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = 'none';
		byId('tdBancoCompania').style.display = 'none';
		byId('tdTituloPorcentajeComision').style.display = 'none';
		byId('tdPorcentajeComision').style.display = 'none';
		byId('tdTituloMontoComision').style.display = 'none';
		byId('tdMontoComision').style.display = 'none';
		byId('tdTituloNumeroCuenta').style.display = 'none';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = 'none';
		byId('tdTituloNumeroDocumento').style.display = 'none';
		byId('tdNumeroDocumento').style.display = 'none';
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = '';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';
	}
	
	function cheques(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = '';
		byId('tdBancoCliente').style.display = '';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = 'none';
		byId('tdBancoCompania').style.display = 'none';
		byId('tdTituloPorcentajeComision').style.display = 'none';
		byId('tdPorcentajeComision').style.display = 'none';
		byId('tdTituloMontoComision').style.display = 'none';
		byId('tdMontoComision').style.display = 'none';
		byId('tdTituloNumeroCuenta').style.display = '';
		byId('tdNumeroCuentaTexto').style.display = '';
		byId('tdNumeroCuentaSelect').style.display = 'none';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
		
		xajax_cargarBancoCompania(2);
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = '';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';
	}
	
	function deposito(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = '';
		byId('tdBancoCliente').style.display = 'none';
		byId('tdTablaFechaDeposito').style.display = '';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = '';
		byId('tdBancoCompania').style.display = '';
		byId('tdTituloPorcentajeComision').style.display = 'none';
		byId('tdPorcentajeComision').style.display = 'none';
		byId('tdTituloMontoComision').style.display = 'none';
		byId('tdMontoComision').style.display = 'none';
		byId('tdTituloNumeroCuenta').style.display = '';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = '';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('tdEtiquetaBancoOFechaDep').innerHTML = 'Fecha Deposito:';
		
		xajax_cargarBancoCompania(3);
		
		byId('btnAgregarDetDeposito').style.display = '';
		byId('agregar').style.display = 'none';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';
	}
	
	function transferencia(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = '';
		byId('tdBancoCliente').style.display = '';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = '';
		byId('tdBancoCompania').style.display = '';
		byId('tdTituloPorcentajeComision').style.display = 'none';
		byId('tdPorcentajeComision').style.display = 'none';
		byId('tdTituloMontoComision').style.display = 'none';
		byId('tdMontoComision').style.display = 'none';
		byId('tdTituloNumeroCuenta').style.display = '';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = '';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
		
		xajax_cargarBancoCompania(4);
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = '';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';
	}
	
	function tarjetaCredito(){
		byId('tdTituloTipoTarjeta').style.display = '';
		byId('tdTipoTarjetaCredito').style.display = '';
		byId('tdEtiquetaBancoOFechaDep').style.display = '';
		byId('tdBancoCliente').style.display = '';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = '';
		byId('tdPorcentajeRetencion').style.display = '';
		byId('tdTituloMontoRetencion').style.display = '';
		byId('tdMontoRetencion').style.display = '';
		byId('tdTituloBancoCompania').style.display = '';
		byId('tdBancoCompania').style.display = '';
		byId('tdTituloPorcentajeComision').style.display = '';
		byId('tdPorcentajeComision').style.display = '';
		byId('tdTituloMontoComision').style.display = '';
		byId('tdMontoComision').style.display = '';
		byId('tdTituloNumeroCuenta').style.display = '';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = '';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
		
		xajax_cargarBancoCompania(5);
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = '';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';
	}
		
	function tarjetaDebito(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = '';
		byId('tdBancoCliente').style.display = '';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = '';
		byId('tdBancoCompania').style.display = '';
		byId('tdTituloPorcentajeComision').style.display = '';
		byId('tdPorcentajeComision').style.display = '';
		byId('tdTituloMontoComision').style.display = '';
		byId('tdMontoComision').style.display = '';
		byId('tdTituloNumeroCuenta').style.display = '';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = '';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('tdEtiquetaBancoOFechaDep').innerHTML = 'Banco Cliente:';
		
		xajax_cargarBancoCompania(6);
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = '';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';
	}
	
	function anticipo(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = 'none';
		byId('tdBancoCliente').style.display = 'none';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = 'none';
		byId('tdBancoCompania').style.display = 'none';
		byId('tdTituloPorcentajeComision').style.display = 'none';
		byId('tdPorcentajeComision').style.display = 'none';
		byId('tdTituloMontoComision').style.display = 'none';
		byId('tdMontoComision').style.display = 'none';
		byId('tdTituloNumeroCuenta').style.display = 'none';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = 'none';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = 'none';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = '';
	}
	
	function notaCredito(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = 'none';
		byId('tdBancoCliente').style.display = 'none';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = 'none';
		byId('tdBancoCompania').style.display = 'none';
		byId('tdTituloPorcentajeComision').style.display = 'none';
		byId('tdPorcentajeComision').style.display = 'none';
		byId('tdTituloMontoComision').style.display = 'none';
		byId('tdMontoComision').style.display = 'none';
		byId('tdTituloNumeroCuenta').style.display = 'none';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = 'none';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = 'none';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = '';
	}
	
	function retencion(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = 'none';
		byId('tdBancoCliente').style.display = 'none';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = 'none';
		byId('tdBancoCompania').style.display = 'none';
		byId('tdTituloPorcentajeComision').style.display = 'none';
		byId('tdPorcentajeComision').style.display = 'none';
		byId('tdTituloMontoComision').style.display = 'none';
		byId('tdMontoComision').style.display = 'none';
		byId('tdTituloNumeroCuenta').style.display = 'none';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = 'none';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = '';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';
	}
	
	function retencionISLR(){
		byId('tdTituloTipoTarjeta').style.display = 'none';
		byId('tdTipoTarjetaCredito').style.display = 'none';
		byId('tdEtiquetaBancoOFechaDep').style.display = 'none';
		byId('tdBancoCliente').style.display = 'none';
		byId('tdTablaFechaDeposito').style.display = 'none';
		byId('tdTituloPorcentajeRetencion').style.display = 'none';
		byId('tdPorcentajeRetencion').style.display = 'none';
		byId('tdTituloMontoRetencion').style.display = 'none';
		byId('tdMontoRetencion').style.display = 'none';
		byId('tdTituloBancoCompania').style.display = 'none';
		byId('tdBancoCompania').style.display = 'none';
		byId('tdTituloPorcentajeComision').style.display = 'none';
		byId('tdPorcentajeComision').style.display = 'none';
		byId('tdTituloMontoComision').style.display = 'none';
		byId('tdMontoComision').style.display = 'none';
		byId('tdTituloNumeroCuenta').style.display = 'none';
		byId('tdNumeroCuentaTexto').style.display = 'none';
		byId('tdNumeroCuentaSelect').style.display = 'none';
		byId('tdTituloNumeroDocumento').style.display = '';
		byId('tdNumeroDocumento').style.display = '';
		
		byId('btnAgregarDetDeposito').style.display = 'none';
		byId('agregar').style.display = '';
		byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';
	}
	
	function validar(){
		error = false;
		if (byId('selTipoPago').value == 1){/*EFECTIVO*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
			}
		}
		else if (byId('selTipoPago').value == 2){/*CHEQUES*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('numeroCuenta','t','') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('numeroCuenta','t','');
				validarCampo('numeroControl','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
			}
		}
		else if (byId('selTipoPago').value == 3){/*DEPOSITO*/
			if (validarCampo('txtTotalFactura','t','monto') == true
			 && validarCampo('montoPago','t','monto') == true
			 && validarCampo('txtFechaDeposito','t','fecha') == true
			 && validarCampo('selBancoCompania','t','lista') == true
			 && validarCampo('selNumeroCuenta','t','') == true
			 && validarCampo('numeroControl','t','') == true){
				byId('divFlotanteDep').style.display = '';
				byId('tdFlotanteTituloDep').innerHTML = 'Detalle Deposito';
				byId('tblDetallePago').style.display = '';
				byId('tblListadosAnticipoNotaCredito').style.display = 'none';
				document.forms['frmDetalleDeposito'].reset();
				centrarDiv(byId('divFlotanteDep'));
				
				byId('txtSaldoDepositoBancario').value = byId('montoPago').value;
				byId('hddSaldoDepositoBancario').value = byId('montoPago').value;
				byId('txtTotalDeposito').value = "0.00";
			 }
			 else {
				validarCampo('txtTotalFactura','t','monto') == true
				validarCampo('montoPago','t','monto');
				validarCampo('txtFechaDeposito','t','fecha');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('numeroControl','t','');
				alert("Los campos señalados en rojo son requeridos");
			}
		}
		else if (byId('selTipoPago').value == 4){/*TRANSFERENCIA*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','');
				validarCampo('numeroControl','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
			}
		}
		else if (byId('selTipoPago').value == 5){/*TARJETA DE CREDITO*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('tarjeta','t','lista') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('numeroControl','t','') == true
			&& validarCampo('porcentajeRetencion','t','numPositivo') == true
			&& validarCampo('montoTotalRetencion','t','numPositivo') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				validarCampo('tarjeta','t','lista');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('numeroControl','t','');
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
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
			}
		}
		else if (byId('selTipoPago').value == 6){/*TARJETA DE DEBITO*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('selBancoCliente','t','lista') == true
			&& validarCampo('selBancoCompania','t','lista') == true
			&& validarCampo('selNumeroCuenta','t','lista') == true
			&& validarCampo('numeroControl','t','') == true
			&& validarCampo('porcentajeComision','t','numPositivo') == true
			&& validarCampo('montoTotalComision','t','numPositivo') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				validarCampo('selBancoCliente','t','lista');
				validarCampo('selBancoCompania','t','lista');
				validarCampo('selNumeroCuenta','t','lista');
				validarCampo('numeroControl','t','');
				validarCampo('porcentajeComision','t','numPositivo');
				validarCampo('montoTotalComision','t','numPositivo');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
			}
		}
		else if (byId('selTipoPago').value == 7){/*ANTICIPO*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				validarCampo('numeroControl','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
			}
		}
		else if (byId('selTipoPago').value == 8){/*NOTA CREDITO*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				validarCampo('numeroControl','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'));
			}
		}
		else if (byId('selTipoPago').value == 9){/*RETENCION*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				validarCampo('numeroControl','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
			}
		}
		else if (byId('selTipoPago').value == 10){/*RETENCION ISLR*/
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('montoPago','t','monto') == true
			&& validarCampo('numeroControl','t','') == true)){
				validarCampo('txtTotalFactura','t','monto');
				validarCampo('montoPago','t','monto');
				validarCampo('numeroControl','t','');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelar').disabled = true;
				xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
			}
		}
		else {
			if (!(validarCampo('txtTotalFactura','t','monto') == true
			&& validarCampo('selTipoPago','t','lista') == true)){
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
	
	function validarAgregarAnticipoNotaCredito(){
		var saldo = $('txtSaldoDocumento').value.replace(/,/gi,'');
		var monto = $('txtMontoDocumento').value.replace(/,/gi,'');
		var montoFaltaPorPagar = $('txtMontoPorPagar').value.replace(/,/gi,'');
		
		if (parseFloat(saldo) < parseFloat(monto))
			alert("El monto a pagar no puede ser mayor que el saldo del documento");
		else{
			if (parseFloat(montoFaltaPorPagar) >= parseFloat(monto)){
				if (confirm("Desea cargar el pago?")){
					$('numeroControl').value = $('txtNroDocumento').value;
					$('hddIdAnticipoNotaCredito').value = $('hddIdDocumento').value;
					$('montoPago').value = $('txtMontoDocumento').value;
					
					$('divFlotanteDep').style.display = 'none';
					$('divFlotante1').style.display = 'none';
					
					xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
				}
			}
			else
				alert("El monto a pagar no puede ser mayor que el saldo de la Factura")
		}
	}
	
	function validarDetalleDeposito(){
		error = false;
		if (byId('lstTipoPago').value == 1){/*EFECTIVO*/
			if (!(validarCampo('txtMontoDeposito','t','monto') == true)){
				validarCampo('txtMontoDeposito','t','monto');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			} else {
				byId('btnAgregarMontoDeposito').disabled = true;
				byId('btnGuardarDetalleDeposito').disabled = true;
				byId('btnCancelarDetalleDeposito').disabled = true;
				xajax_cargarPagoDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'));
			}
		}
		else if (byId('lstTipoPago').value == 2){/*CHEQUES*/
			if (!(validarCampo('txtMontoDeposito','t','monto') == true
			&& validarCampo('lstBancoDeposito','t','lista') == true
			&& validarCampo('txtNroCuentaDeposito','t','') == true
			&& validarCampo('txtNroChequeDeposito','t','') == true)){
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
				byId('agregar').disabled = true;
				byId('btnGuardar').disabled = true;
				byId('btnCancelarDetalleDeposito').disabled = true;
				xajax_cargarPagoDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'));
			}
		}
		else {
			if (!(validarCampo('lstTipoPago','t','lista') == true)){
				validarCampo('lstTipoPago','t','lista');
				
				error = true;
			}
			
			if (error == true) {
				alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
				return false;
			}
		}
	}
	
	function validarAgregarDeposito(){
		if($('txtSaldoDepositoBancario').value == 0){
			xajax_cargarPago(xajax.getFormValues('frmListadoPagos'), xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmDetalleDeposito'),xajax.getFormValues('frmTotalPresupuesto'));
		}
		else
			alert("El saldo del detalle del deposito debe ser 0 (cero)");
	}
	
	function validarSoloNumerosConPunto(evento){
		if (arguments.length > 1)
			color = arguments[1];
		
		if (evento.target)
			idObj = evento.target.id;
		else if (evento.srcElement)
			idObj = evento.srcElement.id;
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 0)
		&& (teclaCodigo != 8)
		&& (teclaCodigo != 13)
		&& (teclaCodigo != 46)
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)) {
			return false;
		}
	}
	
	function calcularMontoTotalTarjetaCredito() {
		if (byId('selTipoPago').value == 5){
		byId("montoTotalRetencion").value = formato(parsenum(parsenum(byId("montoPago").value) * parsenum(byId("porcentajeRetencion").value) / 100));
		byId("montoTotalComision").value = formato(parsenum(parsenum(byId("montoPago").value) * parsenum(byId("porcentajeComision").value) / 100));
		}else if(byId('selTipoPago').value){
		byId("montoTotalComision").value = formato(parsenum(parsenum(byId("montoPago").value) * parsenum(byId("porcentajeComision").value) / 100));
		}
	}
	</script>
        
        <style type="text/css">
            .noRomper{
                white-space: nowrap;
            }    
        </style>
</head>

<body class="bodyVehiculos" onload="xajax_validarAperturaCaja();
xajax_validarTipoDocumento('<?php echo $_GET['doc_type']; ?>','<?php echo $_GET['id']; ?>','<?php echo $_GET['ide']; ?>','<?php echo $_GET['acc']; ?>', xajax.getFormValues('frmTotalPresupuesto')); //if($('hddAccionTipoDocumento').value != 1) //xajax_visualizarMecanicoEnOrden();">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cjrs.php"); ?></div>
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCajaRS" id="tituloPaginaCajaRS">Nota de Crédito de Servicios</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td align="left">
			<form id="frmPresupuesto" name="frmPresupuesto" style="margin:0">
				<input type="hidden" name="hddTipoOrdenAnt" id="hddTipoOrdenAnt" value="0"/>
				<table border="0" width="100%">
                <tr>
					<td colspan="2">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="60%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresaOrden" name="txtIdEmpresaOrden" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 0);" size="6" style="text-align:right;"/></td>
                                    <td></td>
                                    <td><input type="text" id="txtEmpresaOrden" name="txtEmpresaOrden" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo" width="12%">Fecha:</td>
                            <td width="18%"><input type="text" id="txtFechaPresupuesto" name="txtFechaPresupuesto" readonly="readonly" style="text-align:center" size="10"/></td>
                        </tr>
                        </table>
					</td>
                </tr>
				<tr style="display:none">
					<td align="left" class="tituloCampo" width="17%"><span class="textoRojoNegrita">*</span>Empresa:</td>
					<td width="47%">
						<table cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="8" value="<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>"/>
							</td>
							<td><button type="button" id="btnInsertarEmp" name="btnInsertarEmp" onclick="xajax_listadoEmpresas(0,'','','');" title="Listar"><img src="../img/iconos/help.png"/></button></td>
							<td>&nbsp;<input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="50"/></td>
						</tr>
						</table>
					</td>
					<td colspan="2" rowspan="2" align="left"></td>
				</tr>
				<tr style="display:none">
					<td align="left" class="tituloCampo">Empleado:</td>
					<td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="25"/></td>
				</tr>
				<tr>
					<td valign="top">
					<fieldset>
					<legend class="legend">Cliente</legend>
                        <table border="0" width="100%">
                        <tr>
							<td align="right" class="tituloCampo" width="120">Nro. Vale:</td>
							<td>
								<table width="25%" border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td width="21%">
										<label>
                                        <input name="numeracionRecepcionMostrar" type="text" id="numeracionRecepcionMostrar" size="8" readonly="readonly"/>
                                        <input name="txtIdValeRecepcion" type="hidden" id="txtIdValeRecepcion" size="8" readonly="readonly"/>
                                        </label>
									</td>
									<td class="noprint"></td>
								</tr>
								</table>
							</td>
							<td class="tituloCampo" align="right" width="120">Fecha Vale:</td>
							<td><input name="txtFechaRecepcion" type="text" id="txtFechaRecepcion" size="18" readonly="readonly"/></td>
						</tr>
						<tr>
							<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cliente:</td>
							<td>
								<table cellpadding="0" cellspacing="0">
								<tr>
									<td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="8"/></td>
									<td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="47"/></td>
								</tr>
								</table>
							</td>
							<td align="right" class="tituloCampo" width="120"><?php echo $spanClienteCxC; ?>:</td>
							<td width="15%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="18"/></td>
						</tr>
						<tr>
							<td align="right" class="tituloCampo" width="120">Dirección:</td>
                            <td rowspan="3"><textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly"></textarea>
								<input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly"/>
								<input type="hidden" id="hddAgregarOrdenFacturada" name="hddAgregarOrdenFacturada" readonly="readonly"/>
							</td>
                            <td align="right" class="tituloCampo" width="120">Teléfono:</td>
							<td><input type="text" id="txtTelefonosCliente" name="txtTelefonosCliente" readonly="readonly" size="25"/></td>
						</tr>
						</table>
					</fieldset>
                    
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" id="tdTipoMov" class="tituloCampo" style="display:none" width="120"><span class="textoRojoNegrita">*</span>Tipo:</td>
                            <td style="display:none" id="tdLstTipoClave">
                                <?php 
                                    if(isset($_GET['dev'])) {
                                        if($_GET['dev'] == 1) {
                                            $valorSelectEntrada = "selected='selected'";
                                            $valorSelectSalida = "";
                                        } else {
                                            $valorSelectEntrada = "";
                                            $valorSelectSalida = "selected='selected'";
                                        }
                                    } else {
                                        $valorSelectEntrada = "";
                                        $valorSelectSalida = "selected='selected'";
                                    }
                                ?>
                                <select id="lstTipoClave" name="lstTipoClave" onchange="xajax_cargaLstClaveMovimiento(this.value)">
                                    <option value="-1">Seleccione...</option>
                                    <option value="2"<?php echo $valorSelectEntrada;?>>Entrada</option>
                                    <option value="3"<?php echo $valorSelectSalida;?>>Venta</option>
                                    <option value="4">Salida</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" style="display:none" id="tdClave" width="120"><span class="textoRojoNegrita">*</span>Clave:</td>
                            <td colspan="3" id="tdlstClaveMovimiento" style="display:none">
                                <select id="lstClaveMovimiento" name="lstClaveMovimiento">
                                <!--<option value="-1">Seleccione...</option>-->
                                </select>
                            </td>
                            <td class="tituloCampo" align="right" width="120">Tipo de Orden:</td>
                            <td id="tdlstTipoOrden">
                            <!--<select id="lstTipoOrden" name="lstTipoOrden">
                                    <option value="-1">Seleccione...</option>
                                </select>-->
                                <script>
                                    //xajax_cargaLstTipoOrden();
                                </script>
                                <label></label>
                            </td>
                        </table>
                        
					</td>  
					<td valign="top">
					<fieldset>
						<legend id="lydTipoDocumento" class="legend"></legend>
						<table border="0" width="100%">
						<tr>
							<td>
								<table border="0" id="fldPresupuesto">
								<tr>
									<td align="right" class="tituloCampo" id="tdNroFacturaVenta" style="display:none" width="120">Nro. Factura</td>
									<td id="tdTxtNroFacturaVenta" style="display:none">
										<label><input name="txtNroFacturaVentaServ" type="text" id="txtNroFacturaVentaServ" size="25"/></label>
									</td>
									<td style="display:none">&nbsp;</td>
									<td style="display:none">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" id="tdIdDocumento" width="120"></td>
                                    <td width="67%">
                                    <input type="text" id="numeroOrdenMostrar" name="numeroOrdenMostrar" readonly="readonly" size="25" value="0"/>
									<input type="hidden" id="txtIdPresupuesto" name="txtIdPresupuesto" readonly="readonly" size="25" value="0"/>
                                    </td>
								</tr>
								<tr>
									<td class="tituloCampo" align="right" width="120">Estado:</td>
									<td><input name="txtEstadoOrden" id="txtEstadoOrden" type="text" readonly="readonly"/></td>
								</tr>
								<tr>
									<td align="right" class="tituloCampo" id="tdNroControl" style="display:none"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
									<td colspan="3" id="tdTxtNroControl" style="display:none">
									<div style="float:left"><input type="text" id="txtNroControl" name="txtNroControl" size="16" style="color:#F00; font-weight:bold; text-align:center;"/>
									</div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: 00-000000"/>
                                    </div>
									</td>
								</tr>
								<tr align="right" class="trResaltarTotal">
									<td align="right" class="tituloCampo" width="120">Total Factura:</td>
									<td align="left"><input type="text" id="txtTotalFactura" name="txtTotalFactura"  class="inputSinFondo" size="20" style="text-align:right" onblur="byId('txtMontoPorPagar').value = formato(parsenum(this.value)); byId('hddMontoFaltaPorPagar').value = formato(parsenum(this.value));byId('hddMontoPorPagar').value = formato(parsenum(this.value)); this.value = formato(parsenum(this.value));" readonly="readonly"/></td>
								</tr>
								</table>
								<table border="0" style="display:none">
								<tr id="tdFechaVecDoc" style="display:none">
									<td align="left" class="tituloCampo" width="20%">Fecha Venc</td>
									<td>
										<div style="float:left">
											<input type="text" id="txtFechaVencimientoPresupuesto" name="txtFechaVencimientoPresupuesto" readonly="readonly" size="20"/>
										</div>
										<div style="float:left">
											<img src="../img/iconos/ico_date.png" id="imgFechaVencimientoPresupuesto" name="imgFechaVencimientoPresupuesto" class="puntero noprint"/>
											<script type="text/javascript">
												Calendar.setup({
												inputField : "txtFechaVencimientoPresupuesto",
												ifFormat : "%d-%m-%Y",
												button : "imgFechaVencimientoPresupuesto"
												});
											</script>
										</div>
									</td>
									<td>&nbsp;</td>
								</tr>
								<tr style="display:none">
									<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Presu:</td>
									<td><input type="text" id="txtNumeroPresupuestoPropio" name="txtNumeroPresupuestoPropio" size="25"/></td>
									<td>&nbsp;</td>
								</tr>
								<tr style="display:none">
									<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
									<td id="tdlstMoneda">
										<select id="lstMoneda" name="lstMoneda">
											<option value="-1">Seleccione...</option>
										</select>
									</td>
									<td id="tdlstMoneda">&nbsp;</td>
								</tr>
								<tr style="display:none">
									<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Refer:</td>
									<td><input type="text" id="txtNumeroReferencia" name="txtNumeroReferencia" size="25"/></td>
									<td>&nbsp;</td>
								</tr>
								<tr style="display:none">
									<td align="left" class="tituloCampo" id="tdNroPresupuesto" style="display:none"></td>
									<td id="tdTxtNroPresupuesto" style="display:none"><input type="text" id="txtNroPresupuesto" name="txtNroPresupuesto" size="25" readonly="readonly"/></td>
									<td style="display:none">&nbsp;</td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
					</fieldset>
					</td>
				</tr>
				<tr>
					<td valign="top">
					<fieldset>
					<legend class="legend">Datos del vehiculo</legend>
						<table width="100%" border="0">
						<tr>
							<td align="right" class="tituloCampo" width="120">Placa:</td>
							<td><input type="text" id="txtPlacaVehiculo" name="txtPlacaVehiculo" readonly="readonly"/></td>
							<td>&nbsp;</td>
							<td align="right" class="tituloCampo" width="120">A&ntilde;o:
								<input type="hidden" name="hdd_id_modelo" id="hdd_id_modelo"/>
								<input type="hidden" name="hddIdUnidadBasica" id="hddIdUnidadBasica"/>
							</td>
							<td><input type="text" id="txtAnoVehiculo" name="txtAnoVehiculo" readonly="readonly"/></td>
						</tr>
						<tr>
							<td align="right" class="tituloCampo" width="120">Chasis:</td>
							<td><input type="text" id="txtChasisVehiculo" name="txtChasisVehiculo" readonly="readonly"/></td>
							<td>&nbsp;</td>
							<td align="right" class="tituloCampo" width="120">Color:</td>
							<td><input type="text" id="txtColorVehiculo" name="txtColorVehiculo" readonly="readonly"/></td>
						</tr>
						<tr>
							<td align="right" class="tituloCampo" width="120">Marca:</td>
							<td><input type="text" id="txtMarcaVehiculo" name="txtMarcaVehiculo" readonly="readonly"/></td>
							<td>&nbsp;</td>
							<td align="right" class="tituloCampo" width="120">F. venta:</td>
							<td><label><input type="text" name="txtFechaVentaVehiculo" id="txtFechaVentaVehiculo" readonly="readonly"/></label>
							</td>
						</tr>
						<tr>
							<td align="right" class="tituloCampo" width="120">Modelo:</td>
							<td><input type="text" id="txtModeloVehiculo" name="txtModeloVehiculo" readonly="readonly"/></td>
							<td>&nbsp;</td>
							<td align="right" class="tituloCampo" width="120">Kilometraje:</td>
							<td><label><input type="text" name="txtKilometrajeVehiculo" id="txtKilometrajeVehiculo" readonly="readonly"/></label>
							</td>
						</tr>
						<tr>
							<td align="right" class="tituloCampo" width="120">Unidad Basica:</td>
							<td><label><input type="text" name="txtUnidadBasica" id="txtUnidadBasica" readonly="readonly"/></label>
							</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						</table>
					</fieldset>
					</td>
				</tr>
				<tr>
					<td colspan="2" valign="top"></td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		<tr>
			<td align="left">
				<form name="frm_agregar_paq" id="frm_agregar_paq" style="margin:0">
				<table width="100%" border="0" cellpadding="0">				
				<tr>
					<td colspan="9">
						<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea" style="border:0px;">
						<tr>
							<td width="44%" height="22" align="left">
								
							</td>
							<td width="56%" align="left">PAQUETES</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="tituloColumna">
					<td width="22" align="center" class="color_column_insertar_eliminar_item" id="tdInsElimPaq" style="width:20px; display:none;"><input type="checkbox" id="cbxItmPaq" onclick="selecAllChecks(this.checked,this.id,2);"/> </td>
					<td width="51" align="center" class="celda_punteada">C&oacute;digo</td>
					<td width="661" align="center" class="celda_punteada">Descripci&oacute;n</td>
					<td width="99" align="center" class="celda_punteada">Total</td>
					<td width="15" align="center" class="celda_punteada"></td>
					<td width="49" align="center" class="color_column_aprobacion_item" id="tdPaqAprob" style="text-align:center; width:20px;"><input type="checkbox" id="cbxItmPaqAprob" onclick="selecAllChecksName(this.checked,'cbxItmPaqAprob[]',2); xajax_calcularTotalDcto();" checked="checked"/>
					</td>
				</tr>
				<tr id="trm_pie_paquete"></tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td align="left">&nbsp;</td>
		</tr>
		<tr>
			<td></td>
		</tr>
		<tr>
			<td>
				<form id="frmListaArticulo" name="frmListaArticulo" style="margin:0">
				<table width="100%" border="0" cellpadding="0" cellspacing="0" id="tblListaArticulo">
				<tr>
					<td  colspan="11" class="tituloArea" style="border:0px;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="40%" height="22" align="left">
								
							</td>
							<td width="60%" align="left">REPUESTOS GENERALES</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
				<table width="100%" border="0" cellpadding="0">
				<tr class="tituloColumna">
					<td style="width:20px; display:none;" id="tdInsElimRep" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,3);"/>
					</td>
					
					<td>Código</td>
					<td>Descripción</td>
                                        <td>Lote</td>
					<td>Cantidad</td>					
					<td>Precio Unit.</td>
					<td>% Impuesto</td>
					<td title="Total sin Impuestos">Total S/I</td>
					<td>Total</td>
					<td>&nbsp;</td>
					<td style="text-align:center; width:20px; " id="tdRepAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmAprob" onclick="selecAllChecks(this.checked,this.id,3); xajax_calcularTotalDcto();" checked="checked"/></td>
				</tr>
				<tr id="trItmPie"></tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<form id="frmListaManoObra" name="frmListaManoObra" style="margin:0">
				<table border="0" width="100%">
				<tr>
					<td colspan="16" class="tituloArea" style="border:0px;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="40%" height="22" align="left">
								
							</td>
							<td width="60%" align="left">MANO DE OBRA GENERAL</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="tituloColumna">
					<td style="width:20px; display:none;" id="tdInsElimManoObra" class="color_column_insertar_eliminar_item"><input type="checkbox" id="cbxItmTemp" onclick="selecAllChecks(this.checked,this.id,4);"/></td>
					<td id="tdCodigoMecanico" style="display:none">Código Mec&aacute;nico</td>
					<td id="tdNombreMecanico" style="display:none">Nombre Mec&aacute;nico</td>
					<td>Secci&oacute;n</td>
					<td>Subsecci&oacute;n</td>
					<td>Código Tempario</td>
					<td>Descripción</td>
					<td>Origen</td>
					<td>Modo</td>
					<td>Operador</td>
					<td>Precio</td>
					<td>Total</td>
					<td style="width:20px;" id="tdTempAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmTempAprob" onclick="selecAllChecks(this.checked,this.id,4); xajax_calcularTotalDcto();" checked="checked"/></td>
				</tr>
				<tr id="trm_pie_tempario"></tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<form id="frmListaTot" name="frmListaTot" style="margin:0">
				<table border="0" width="100%">
				<tr>
					<td colspan="19" class="tituloArea" style="border:0px;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="36%" height="22" align="left">
                                                            
							</td>
							<td width="100%" align="left">TRABAJOS OTROS TALLERES (T.O.T)</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="tituloColumna">
					<td id="tdInsElimTot" class="color_column_insertar_eliminar_item" style="width:20px; display:none;"><input type="checkbox" id="cbxItmTot" onclick="selecAllChecks(this.checked,this.id,5);"/></td>
					<td>Nro. T.O.T.</td>
					<td>Proveedor</td>
					<td>Tipo Pago</td>
					<td>Monto T.O.T.</td>
					<td>Porcentaje T.O.T.</td>
					<td>Monto Total</td>
					<td style="width:20px;" id="tdTotAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmTotAprob" onclick="selecAllChecks(this.checked,this.id,5); xajax_calcularTotalDcto();" checked="checked"/></td>
				</tr>
				<tr id="trm_pie_tot"></tr>
				</table> 
				</form>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<form id="frmListaNota" name="frmListaNota" style="margin:0">
				<table border="0" width="100%">
				<tr>
					<td colspan="12" class="tituloArea" style="border:0px;">
						<table width="100%" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="40%" height="22" align="left">
                                                            
							</td>
							<td width="100%" align="left">NOTAS / CARGO ADICIONAL</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="tituloColumna">
					<td width="38" class="color_column_insertar_eliminar_item" id="tdInsElimNota" style="width:20px">
						<input type="checkbox" id="cbxItmNota" onclick="selecAllChecks(this.checked,this.id,6);"/></td>
					<td width="593">Descripción</td>
					<td width="291">Total</td>
					<td width="20" class="color_column_aprobacion_item" id="tdNotaAprob" style="width:20px;"><input type="checkbox" id="cbxItmNotaAprob" onclick="selecAllChecks(this.checked,this.id,6); xajax_calcularTotalDcto();" checked="checked"/></td>
				</tr>
				<tr id="trm_pie_nota"></tr>
				</table>
				</form>
			</td>
		<tr>
		<tr>
			<td align="right">
			<form id="frmTotalPresupuesto" name="frmTotalPresupuesto" style="margin:0"><hr>
				<input type="hidden" name="hddDevolucionFactura" id="hddDevolucionFactura" value="<?php echo $_GET['dev'];?>"/>
				<input type="hidden" id="hddObj" name="hddObj"/>
				<input type="hidden" id="hddObjPaquete" name="hddObjPaquete" readonly="readonly"/>
				<input type="hidden" id="hddObjRepuestosPaquete" name="hddObjRepuestosPaquete" readonly="readonly"/>
				<input type="hidden" id="hddObjTempario" name="hddObjTempario" readonly="readonly"/>
				<input type="hidden" id="hddObjTot" name="hddObjTot" readonly="readonly"/>
				<input type="hidden" id="hddObjNota" name="hddObjNota" readonly="readonly"/>
				<input type="hidden" name="hddTipoDocumento" id="hddTipoDocumento" value="<?php echo $_GET['doc_type'];?>"/>
				<input type="hidden" name="hddAccionTipoDocumento" id="hddAccionTipoDocumento" value="<?php echo $_GET['acc'];?>"/>
				<input type="hidden" name="hddMecanicoEnOrden" id="hddMecanicoEnOrden"/>
				<input type="hidden" name="hddItemsCargados" id="hddItemsCargados"/>
				<input type="hidden" name="hddNroItemsPorDcto" id="hddNroItemsPorDcto" value="40"/>
				<input type="hidden" name="hddObjDescuento" id="hddObjDescuento"/>
				<input type="hidden" name="hddObjCons" id="hddObjCons" value="<?php echo $_GET['cons'];?>"/>
				<input type="hidden" name="hddItemsNoAprobados" id="hddItemsNoAprobados"/>
				<input type="hidden" id="hddOrdenEscogida" name="hddOrdenEscogida"/>
				<input type="hidden" id="hddLaOrdenEsRetrabajo" name="hddLaOrdenEsRetrabajo" value="<?php echo $_GET['ret'];?>"/>
				<table border="0" width="100%">
				<tr>
					<td width="40%" colspan="2" align="right" id="tdGastos">
						<table cellpadding="0" cellspacing="0" width="100%" class="divMsjInfo" id="tblLeyendaOrden">
						<tr>
							<td width="25"><img src="../img/iconos/ico_info2.gif" width="25"/></td>
							<td align="center">
								<table>
								<tr>
									<td><img src="../img/iconos/ico_aceptar.gif"/></td>
									<td>Paquete o Repuesto Disponibilidad Suficiente</td>
								</tr>
								<tr>
									<td><img src="../img/iconos/ico_alerta.gif"/></td>
									<td>Paquete o Repuesto Poca Disponibilidad</td>
								</tr>
								<tr>
									<td><img src="../img/iconos/cancel.png"/></td>
									<td>Paquete o Repuesto sin Disponibilidad</td>
								</tr>
								<tr>
									<td class="color_column_insertar_eliminar_item" style="border:1px dotted #999999">&nbsp;</td>
									<td>Eliminar Item</td>
								</tr>
								<tr>
									<td class="color_column_aprobacion_item" style="border:1px dotted #999999">&nbsp;</td>
									<td>Aprobar Item</td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
						<table id="tblMotivoRetrabajo" style="display:none" cellpadding="0" cellspacing="0" align="center">
						<tr>
							<td colspan="2" class="tituloCampo">Motivo:</td>
						</tr>
						<tr>
							<td colspan="2"><textarea name="txtMotivoRetrabajo" id="txtMotivoRetrabajo" cols="45" rows="5"></textarea></td>
						</tr>
						</table>
					</td>
					<td valign="top" width="50%">
						<table border="0" width="100%">
						<tr align="right">
							<td class="tituloCampo" width="36%">Subtotal:</td>
                            <td style="border-top:1px solid;" width="25%"></td>
                            <td style="border-top:1px solid;" width="15%"></td>
							<td style="border-top:1px solid;" width="23%"><input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly" size="18" style="text-align:right" class="inputSinFondo"/></td>
						</tr>
						<tr align="right">
                            <td class="tituloCampo">Descuento:</td>
							<td></td>
							<td nowrap="nowrap">
								<input type="hidden" name="hddPuedeAgregarDescuentoAdicional" id="hddPuedeAgregarDescuentoAdicional"/> 
								<input type="text" id="txtDescuento" name="txtDescuento" size="6" style="text-align:right" readonly="readonly" value="0" />%</td>
							<td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" size="18" readonly="readonly" style="text-align:right" class="inputSinFondo"/></td>
						</tr>
						<tr id="trm_pie_dcto"></tr>
                                                <tr align="right" style="display:none">
							<td class="tituloCampo">Base Imponible:</td>
                            <td></td>
							<td></td>
							<td align="right"><input type="text" id="txtBaseImponible" name="txtBaseImponible" size="18" readonly="readonly" style="text-align:right" class="inputSinFondo"/></td>
						</tr>
						<tr align="right" style="display:none">
							<td class="tituloCampo">Items Con Impuesto:</td>
                            <td></td>
                            <td></td>
							<td align="right"><input type="text" id="txtGastosConIva" name="txtGastosConIva" readonly="readonly" size="18" style="text-align:right"/></td>
						</tr>
						<!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
						<tr align="right" id="trGastosSinIva">
							<td class="tituloCampo">Exento:</td>
                            <td></td>
							<td></td>
							<td align="right"><input type="text" id="txtMontoExento" name="txtMontoExento" readonly="readonly" size="18" style="text-align:right" class="inputSinFondo"/></td>
						</tr>                                                
                                                
                                                <?php 
                                                    $arrayIvas = cargarIvasOrden($_GET["id"]);
                                                    foreach($arrayIvas as $key => $arrayIva){//funcion en ac_iv_general 
                                                ?>
                                                    <tr align="right">
                                                        <td class="tituloCampo"><?php echo $arrayIva["observacion"]; ?></td>
                                                        <td>
                                                            <input style="display:none" class="puntero" type="checkbox" name="ivaActivo[]" checked="checked" id="ivaActivo<?php echo $key; ?>"  value="<?php echo $key; ?>" onclick="return false"/>                                    
                                                            <input class="inputSinFondo" type="text" id="txtBaseImponibleIva<?php echo $key; ?>" name="txtBaseImponibleIva<?php echo $key; ?>" readonly="readonly" size="18" style="text-align:right"/>                                    
                                                        </td>
                                                        <td>                                    
                                                            <input type="hidden" id="hddIdIvaVenta<?php echo $key; ?>" name="hddIdIvaVenta<?php echo $key; ?>" value="<?php echo $key ?>"  readonly="readonly"/>
                                                            <input type="text" id="txtIvaVenta<?php echo $key; ?>" name="txtIvaVenta<?php echo $key; ?>" value="<?php echo $arrayIva["iva"]; ?>"  readonly="readonly" size="6" style="text-align:right" value="0"/>%
                                                        </td>
                                                        <td><input class="inputSinFondo" type="text" id="txtTotalIva<?php echo $key; ?>" name="txtTotalIva<?php echo $key; ?>" readonly="readonly" size="18" style="text-align:right"/></td>
                                                    </tr>
                                                <?php } ?>
                                                
						<tr align="right" id="trNetoPresupuesto" class="trResaltarTotal">
							<td id="tdEtiqTipoDocumento" class="tituloCampo"></td>
                            <td></td>
							<td></td>
							<td align="right"><input type="text" id="txtTotalPresupuesto" name="txtTotalPresupuesto" readonly="readonly" size="18" style="text-align:right" class="inputSinFondo"/></td>
						</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="2"></td>
				</tr>
				<tr>
					<td colspan="2"><!-- <fieldset>
					<legend>Generar a:</legend>
					<p>&nbsp;</p>
					</fieldset>-->
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
		<tr id="trFormaDePago" style="display:none">	
			<td width="100%">
				<form id="frmDetallePago" name="frmDetallePago">
				<fieldset><legend class="legend">Forma de Pago</legend>
				<table border="0" width="100%">
                <tr>
                    <td align="right" class="tituloCampo" width="12%">Forma de Pago:</td>
                    <td id="tdselTipoPago" width="26%">
                    <div align="justify">
                        <select name="selTipoPago" id="selTipoPago" onChange="cambiar()">
                            <option>Tipo pago</option>
                        </select>
                        <script>xajax_cargarTipoPago();</script>
                    </div>
                    </td>
                    <td width="28"></td>
                    <td align="right" scope="row" class="tituloCampo" id="tdTituloTipoTarjeta" style="display:none;">Tipo Tarjeta:</td>
                    <td align="left" scope="row" id="tdTipoTarjetaCredito" colspan="4" style="display:none;">
                        <select id="tarjeta" name="tarjeta">
                            <option value="">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td height="23" id="tdEtiquetaBancoOFechaDep" align="left" class="tituloCampo">Banco Cliente:</td>
                    <td id="tdBancoCliente" scope="row" align="left">
                        <select name="selBancoCliente" id="selBancoCliente">
                            <option value="">[ Seleccione ]</option>
                        </select>
                        <script>xajax_cargarBancoCliente("tdBancoCliente","selBancoCliente");</script>
                    </td>
                    <td id="tdTablaFechaDeposito" style="display:none">
                        <table width="26%" border="0" cellpadding="0" cellspacing="0" id="tblFechaDeposito">
                            <tr>
                                <td width="35%">
                                    <label><input type="text" name="txtFechaDeposito" id="txtFechaDeposito" readonly="readonly" onClick="imgFechaDeposito.onclick();"/></label>
                                </td>
                                <td width="65%" align="left">
                                    <img id="imgFechaDeposito" src="../img/iconos/ico_date.png" width="21" height="17"/>
                                    <script type="text/javascript">
                                        Calendar.setup({
                                        inputField : "txtFechaDeposito", // id del campo de texto
                                        ifFormat : "%d-%m-%Y", // formato de la fecha que se escriba en el campo de texto
                                        button : "imgFechaDeposito" // el id del boton que lanzara el calendario
                                        });
                                    </script>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="28"></td>
                    <td class="tituloCampo" id="tdTituloPorcentajeRetencion" width="140" style="display:none;">Porcentaje Retenci&oacute;n:</td>
                    <td width="75" scope="row" id='tdPorcentajeRetencion' style="display:none">
                        <input name="porcentajeRetencion" type="text" id="porcentajeRetencion" size="10" readonly="readonly" style="text-align:right; background-color:#EEEEEE;"/>
                    </td>
                    <td scope="row" class="tituloCampo" id="tdTituloMontoRetencion" style="display:none;" width="80">Monto:</td>
                    <td align="left" scope="row" width="100" id="tdMontoRetencion" style="display:none;">
                        <input name="montoTotalRetencion" type="text" id="montoTotalRetencion" style="text-align:right; background-color:#EEEEEE;" size="19" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td height="23" class="tituloCampo" align="left" id="tdTituloBancoCompania">Banco Compa&ntilde;ia:</td>
                    <td height="23" id="tdBancoCompania" align="left" ><div align="justify">
                        <select name="selBancoCompania" id="selBancoCompania">
                            <option></option>
                        </select>
                        <script>//xajax_cargarBancoCompania();</script></div></td>
                    <td width="28"></td>
                    <td class="tituloCampo" id="tdTituloPorcentajeComision" width="140" style="display:none;">Porcentaje Comisi&oacute;n:</td>
                    <td width="75" scope="row" id="tdPorcentajeComision" style="display:none;">
                        <input name="porcentajeComision" type="text" id="porcentajeComision" size="10" readonly="readonly" style="text-align:right; background-color:#EEEEEE;" value="0.00"/>
                    </td>
                    <td scope="row" class="tituloCampo" id="tdTituloMontoComision" style="display:none;" width="80">Monto:</td>
                    <td align="left" scope="row" width="100" id="tdMontoComision" style="display:none;">
                        <input name="montoTotalComision" type="text" id="montoTotalComision" style="text-align:right; background-color:#EEEEEE;" size="19" value="0.00" readonly="readonly"/>
                    </td>
                </tr>
                <tr>
                    <td height="23" class="tituloCampo" align="left" id="tdTituloNumeroCuenta">Nro. de Cuenta:</td>
                    <td colspan="8" id="tdNumeroCuentaTexto"><div align="justify"><strong>
                        <input name="numeroCuenta" type="text" id="numeroCuenta" size="30"/></strong></div>
                    </td>
                    <td colspan="8" id="tdNumeroCuentaSelect" style="display:none"><div align="justify"><strong>
                        <select id="selNumeroCuenta" name="selNumeroCuenta">
                            <option value="">[ Seleccione ]</option>
                        </select></strong></div>
                    </td>
                </tr>
                <tr>
                    <td height="23" class="tituloCampo" align="left" id="tdTituloNumeroDocumento">Nro.:</td>
                    <td height="23" colspan="1" id="tdNumeroDocumento"><div align="justify"><strong>
                        <input name="numeroControl" type="text" id="numeroControl" size="30"/>
                        <input type="hidden" id="hddIdAnticipoNotaCredito" name="hddIdAnticipoNotaCredito"/></strong></div>
                    </td>
                    <td width="28" id="tdImgAgregarFormaAnticipoNotaCredito">
                        <button style="display:none" type="button" id="btnAgregarDetAnticipoNotaCredito" name="btnAgregarDetAnticipoNotaCredito" onClick="byId('btnBuscarAnticipoNotaCredito').click();" title="Buscar Documento"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/find.png" width="16" height="16"/></td><td>&nbsp;</td><td>Buscar Documento</td></tr></table></button>
                    </td>
                </tr>
                <tr>
                    <td height="23" class="tituloCampo" align="left">Monto:</td>
                    <td width="178" height="23"><div align="justify"><strong>
                        <input type="text" name="montoPago" id="montoPago" onblur="this.value=formato(parsenum(this.value)); calcularMontoTotalTarjetaCredito();" onkeypress="return validarSoloNumerosConPunto(event);" style="text-align:right" class="inputHabilitado"/></strong>
                        <input name="ocultoAgregarPagoAnticipo" type="hidden" id="ocultoAgregarPagoAnticipo" value="1"/></div>
                    </td>
                    <td width="28" id="tdImgAgregarFormaDeposito">
                        <button style="display:none" type="button" id="btnAgregarDetDeposito" name="btnAgregarDetDeposito" onClick="validar();" title="Agregar Detalle Deposito"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/money_add.png" width="16" height="16"/></td><td>&nbsp;</td><td>Agregar Detalle Deposito</td></tr></table></button>
                    </td>
                    <td width="429" height="23" align="left" colspan="6">
                        <button type="button" id="agregar" name="agregar" onclick="validar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar Pago</td></tr></table></button>
                        <input type="hidden" id="hddObjDetallePago" name="hddObjDetallePago"/>
                        <input type="hidden" id="hddObjDetalleDeposito" name="hddObjDetalleDeposito"/>
                        <input type="hidden" id="hddObjDetalleDepositoFormaPago" name="hddObjDetalleDepositoFormaPago"/>
                        <input type="hidden" id="hddObjDetalleDepositoBanco" name="hddObjDetalleDepositoBanco"/>
                        <input type="hidden" id="hddObjDetalleDepositoNroCuenta" name="hddObjDetalleDepositoNroCuenta"/>
                        <input type="hidden" id="hddObjDetalleDepositoNroCheque" name="hddObjDetalleDepositoNroCheque"/>
                        <input type="hidden" id="hddObjDetalleDepositoMonto" name="hddObjDetalleDepositoMonto"/>
                        <input type="hidden" id="hddMontoFaltaPorPagar" name="hddMontoFaltaPorPagar"/>
                    </td><!-- botones()-->
                </tr>
				</table>
				</fieldset>
				</form>
			</td>
		</tr>
		<tr id="trDesgloseDePagos" style="display:none">	
			<td>
				<form id="frmListadoPagos" name="frmListadoPagos">
				<fieldset><legend class="legend">Desglose de Pagos</legend>
				<table>
				<tr align="center" class="tituloColumna">
					<td width="15%" class="tituloColumna">Tipo Pago</td>
					<td width="20%" class="tituloColumna">Banco Cliente</td>
					<td width="20%" class="tituloColumna">Banco Compañia</td>
					<td width="25%" class="tituloColumna">Cuenta</td>
					<td width="10%" class="tituloColumna">Nro. Control</td>
					<td width="10%" class="tituloColumna">Monto</td>
					<td class="tituloColumna">&nbsp;</td>
				</tr>
				<tr id="trItmPiePago"></tr>
				<tr class="tituloColumna">
					<td colspan="3"><strong>Monto que falta por pagar:
						<input type="text" name="txtMontoPorPagar" id="txtMontoPorPagar" readonly="readonly" value="0" style="text-align:right;" class="trResaltarTotal3"/>
						<input type="hidden" name="hddMontoPorPagar" id="hddMontoPorPagar"/></strong>
					</td>
					<td colspan="4"><strong>Monto Pagado de la Factura:
						<input type="text" name="txtMontoPagadoFactura" id="txtMontoPagadoFactura" value="0" readonly="readonly" style="text-align:right" class="trResaltarTotal"/></strong>
					</td>
				</tr>
				</table>
				</fieldset>
				</form>
			</td>
		</tr>
	
		<tr>
			<td align="right">&nbsp;</td>
		</tr>
		<tr>
			<td align="right"></td>
		</tr>
		<tr>
			<td align="right" class="noprint">
				<button class="noprint" type="button" id="btnGuardar" name="btnGuardar" onclick="
					if($('hddDevolucionFactura').value != '')
					{
						if($('hddDevolucionFactura').value == 1)
							validarDevolucion();
					} else {
					if($('hddTipoDocumento').value == 3)
						validarNroControl(); 
					else {
						if($('hddTipoDocumento').value == 4) {
							xajax_generarDctoApartirDeOrden(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmTotalPresupuesto'));
					} else {
						if(parseInt($('hddItemsCargados').value) > parseInt($('hddNroItemsPorDcto').value))
							alert('La Orden tiene ' + $('hddItemsCargados').value + ' items incluyendo el contenido de Paquetes. El Nro. máximo son ' + $('hddNroItemsPorDcto').value + ' items. Si desea continuar elimine items o abra un Nueva Orden.');
						else
							if($('hddLaOrdenEsRetrabajo').value != 5)
								validarFormPresupuesto();
							else
								validarMotivoRetrabajo();
						}
					}
				}" style="cursor:default">
					<table width="73" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td width="10">&nbsp;</td>
						<td width="18"><img src="../img/iconos/save.png"/></td>
						<td width="10">&nbsp;</td>
						<td width="47">Guardar</td>
					</tr>
					</table>
				</button>
				<button class="noprint" type="button" id="btnCancelar" name="btnCancelar" onclick="
					if($('hddTipoDocumento').value == 3)
					{
						if($('hddObjCons').value == 0)
							window.location.href='cjrs_devolucion_venta_list.php';
						else
							window.location.href='cjrs_factura_venta_list.php';
					} else
						window.location.href='cjrs_factura_venta_list.php';
						" style="cursor:default">
					<table width="77" align="center" cellpadding="0" cellspacing="0">
					<tr>
						<td width="10">&nbsp;</td>
						<td width="18"><img src="../img/iconos/cancel.png"/></td>
						<td width="10">&nbsp;</td>
						<td width="51">Cancelar</td>
					</tr>
					</table>
				</button>
			</td>
		</tr>
		</table>
	</div>
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
	
	<!-- TABLA DE PAQUETES -->
	<table id="tblGeneralPaquetes" cellpadding="0" border="0" cellspacing="0" width="100%">
	<tr>
		<td id="tdHrTblPaquetes">
			<table border="0" id="tblPaquetes" style="display:none" width="980px">
			<tr>
				<td>
					<form id="frmBuscarPaquete" name="frmBuscarPaquete" style="margin:0" onsubmit="xajax_asignarArticulo($('txtDescripcionBusq').value, xajax.getFormValues('frmPresupuesto')); xajax_buscarArticulo(xajax.getFormValues('frmBuscarPaquete'), xajax.getFormValues('frmPresupuesto')); return false;">
					<table border="0" width="100%" id="tblBusquedaPaquete">
					<tr style="visibility:hidden" align="left">
						<td align="right" class="tituloCampo" width="6%">&nbsp;</td>
						<td width="19%">&nbsp;</td>
						<td align="right" class="tituloCampo" width="11%">&nbsp;</td>
						<td width="17%">&nbsp;</td>
						<td width="16%"></td>
						<td width="24%"></td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" style="visibility:hidden">&nbsp;</td>
						<td style="visibility:hidden">&nbsp;</td>
						<td align="right" class="tituloCampo" style="visibility:hidden">&nbsp;</td>
						<td style="visibility:hidden">&nbsp;</td>
						<td align="right" class="tituloCampo">Código / Descripci&oacute;n:</td>
						<td><input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscarPaquete').click();" size="30"/></td>
						<td width="7%">
						<input type="button" id="btnBuscarPaquete" name="btnBuscarPaquete" onclick="xajax_buscarPaquete(xajax.getFormValues('frmBuscarPaquete'));" value="Buscar..."/></td>
					</tr>
					</table>
				</form>
				</td>
			</tr>
			</table>
		<form id="frmDatosPaquete" name="frmDatosPaquete" style="margin:0">
			<table border="0" id="tblPaquetes2" style="display:none" width="980px">
			<tr>
			<!-- xajax_asignarArticulo($('txtDescripcionBusq').value, xajax.getFormValues('frmPresupuesto')); xajax_buscarArticulo(xajax.getFormValues('frmBuscarPaquete'));-->
				<td id="tdListadoPaquetes">
					<table width="100%">
					<tr class="tituloColumna">
						<td width="10%"></td>
						<td width="30%">Código</td>
						<td width="60%">Descripción</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table width="100%" id="tblListadoTemparioPorPaquete" style="display:none">
					<input type="hidden" id="txtCodigoPaquete" name="txtCodigoPaquete" class="text_sin_border" style="display:none; text-align:center;" readonly="readonly"/>
					<input type="hidden" name="txtDescripcionPaquete" id="txtDescripcionPaquete" class="text_sin_border" style="display:none" readonly="readonly"/>
					<input type="text" name="hddEscogioPaquete" id="hddEscogioPaquete" class="text_sin_border" style="display:none" readonly="readonly"/>
					<tr>
						<td id="tdListadoTempario">
							<table width="100%" border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td colspan="5" class="tituloPaginaServicios" id="tdEncabPaquete"></td>
							</tr>
							<tr>
								<td colspan="4" class="tituloArea" align="center">Mano de Obra</td>
							</tr>
							<tr class="tituloColumna">
								<td>Código</td>
								<td>Descripción</td>
								<td>Modo</td>
								<td>Precio</td>
							</tr>
							</table>
						</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table width="100%" id="tblListadoRepuestosPorPaquete" style="display:none">
					<tr>
						<td>
							<div id="tdListadoRepuestos" style="overflow:scroll; height:140px;">
							<table width="98%">
							<tr>
								<td colspan="5" class="tituloArea" align="center">Repuestos</td>
							</tr>
							<tr class="tituloColumna">
								<td></td>
								<td>C&oacute;digo</td>
								<td>Descripci&oacute;n</td>
								<td>Marca</td>
								<td>Cantidad</td>
							</tr>
							</table>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<table width="100%" id="trPieTotalPaq" style="display:none">
							<tr>
								<td width="36%" rowspan="2" align="left">
									<table cellpadding="0" cellspacing="0" width="100%" class="divMsjInfo" id="tblLeyendaAccAlmacen" style="display:none">
									<tr>
										<td width="25"><img src="../img/iconos/ico_info2.gif" width="25"/></td>
										<td align="center">
											<table>
											<tr>
												<td><img src="../img/iconos/ico_aceptar_azul.png"/></td>
												<td>Agregar Almacen</td>
											</tr>
											<tr>
												<td><img src="../img/iconos/delete.png"/></td>
												<td>Eliminar Almacen</td>
											</tr>
											</table>
										</td>
									</tr>
									</table>
								</td>
								<td width="10%" height="26" align="left" class="tituloColumna">M.O Aprob.</td>
								<td width="12%" align="left"><input type="text" id="txtNroManoObraAprobPaq" name="txtNroManoObraAprobPaq" readonly="readonly" value="0"/></td>
								<td width="9%" class="tituloColumna" align="right">Rep.Aprob.</td>
								<td width="13%" align="left"><input type="text" id="txtNroRepuestoAprobPaq" name="txtNroRepuestoAprobPaq" readonly="readonly" value="0"/></td>
								<td width="11%" align="right" class="tituloColumna">Total Aprob.</td>
								<td width="9%" align="left"><input type="text" id="txtTotalItemAprobPaq" name="txtTotalItemAprobPaq" readonly="readonly" value="0"/></td>
							</tr>
							<tr>
								<td align="left" class="tituloColumna">Total M.O
									<input type="hidden" id="hddManObraAproXpaq" name="hddManObraAproXpaq" readonly="readonly"/>
									<input type="hidden" id="hddTotalArtExento" name="hddTotalArtExento" readonly="readonly"/>
									<input type="hidden" id="hddTotalArtConIva" name="hddTotalArtConIva" readonly="readonly"/></td>
								<td align="left"><input type="text" id="txtTotalManoObraPaq" name="txtTotalManoObraPaq" readonly="readonly"/></td>
								<td class="tituloColumna" align="right">Total Repto</td>
								<td align="left"><input type="text" id="txtTotalRepPaq" name="txtTotalRepPaq" readonly="readonly"/></td>
								<td align="right" class="tituloColumna">Total Paq.</td>
								<td align="left">
									<input type="text" id="txtPrecioPaquete" name="txtPrecioPaquete" readonly="readonly"/>
									<input type="hidden" name="txtNumeroSolicitud" id="txtNumeroSolicitud" readonly="readonly"/>
								</td>
							</tr>
							</table>
<!-- </body>
</html>-->
						</td>
					</tr>
					<tr>
						<td class="divMsjInfo" colspan="8" width="35%" id="tdDivMsjInfoRpto">
							<table cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td width="25"><img src="../img/iconos/ico_info2.gif" width="25"/></td>
								<td colspan="8" align="center">
									<table>
									<tr>
										<td width="32"><img src="../img/iconos/ico_aceptar.gif"/></td>
										<td width="263">Art&iacute;culo con Disponibilidad Suficiente</td>
										<td width="32"><img src="../img/iconos/ico_alerta.gif"/></td>
										<td width="238">Art&iacute;culo con Poca Disponibilidad</td>
										<td width="32"><img src="../img/iconos/cancel.png"/></td>
										<td width="234">Art&iacute;culo sin Disponibilidad<input type="hidden" id="hddRepAproXpaq" name="hddRepAproXpaq" readonly="readonly"/>
										<input type="hidden" name="hddArticuloSinDisponibilidad" id="hddArticuloSinDisponibilidad" value=""/>
										<input type="hidden" name="hddArtEnPaqSinPrecio" id="hddArtEnPaqSinPrecio" value=""/>
										<input type="hidden" name="hddTempEnPaqSinPrecio" id="hddTempEnPaqSinPrecio" value=""/>
										<input type="hidden" name="hddArtNoDispPaquete" id="hddArtNoDispPaquete" value=""/>
										<input type="hidden" name="hddObjRepuestoPaq" id="hddObjRepuestoPaq" value=""/>
										<input type="hidden" name="hddObjTemparioPaq" id="hddObjTemparioPaq" value=""/></td>
										<td width="41"><img src="../img/50.png"/></td>
										<td width="223">Sin Precio Asignado</td>
									</tr>
									</table>
								</td>
							</tr>
							</table>
						</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td id="tdBtnAccionesPaq" align="right"><hr/>
				<input type="button" onclick="validarPaquete();" id="btnAsignarPaquete" value="Aceptar">
				<input type="button" style="display:none" id="btnGuardarAlmacenesPaquete" value="Guardar" onclick="xajax_actualizarSolicitud(xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frmDatosPaquete'));">
				<input type="button" id="btnCancelarDivPpalPaq" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none';" value="Cancelar">
				<input type="button" id="btnCancelarDivSecPaq" onclick="
					$('tdListadoTempario').style.display = 'none';
					$('tdListadoRepuestos').style.display = 'none';	
					$('tblListadoRepuestosPorPaquete').style.display = 'none';	
					$('trPieTotalPaq').style.display = 'none';	
					$('tdDivMsjInfoRpto').style.display = 'none';
					$('tdListadoPaquetes').style.display='';
					$('tblBusquedaPaquete').style.display='';
					$('btnCancelarDivPpalPaq').style.display='';
					this.style.display = 'none';
					$('btnAsignarPaquete').style.display='none';" value="Cancelar">
				</td>
			</tr>
			</table>
		</form>
		</td>
	</tr>
	</table>        
        
        
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
		
        <!-- Movimientos Articulos Almancen -->
	<table width="100%" border="0" id="tblMtosArticulos">	
            <tr>
                    <td colspan="2"></td>
            </tr>
            <tr>
                    <td colspan="2" id="tdListadoEstadoMtoArt">
                    </td>
            </tr>
            <tr>
                    <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                    <td>&nbsp;</td>
                    <td align="right"><input type="button" name="btnCancelarMtoArt" id="btnCancelarMtoArt" value="Cancelar" onclick="$('divFlotante2').style.display='none'; $('divFlotante').style.display='none';"/></td>
            </tr>
        </table>
</div>

<div id="divFlotanteDep" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTituloDep" class="handle"><table><tr><td id="tdFlotanteTituloDep" width="100%"></td></tr></table></div>
	<form id="frmDetalleDeposito" name="frmDetalleDeposito" style="margin:0">
	<table border="0" id="tblDetallePago" style="display:none" width="700px">
	<tr>
		<td width="32%">&nbsp;</td>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr align="left">
		<td class="tituloCampo" width="120">Forma de Pago:</td>
		<td id="tdlstTipoPago" colspan="5">
			<select id="lstTipoPago" name="lstTipoPago">
				<option value="-1">[ Seleccione ]</option>
			</select>
			<script>
				xajax_cargarTipoPagoDetalleDeposito();
			</script>
		</td>
	</tr>
	<tr id="trBancoCliente" style="display:none">
		<td class="tituloCampo">Banco:</td>
		<td colspan="5" id="tdBancoDeposito">
			<select id="lstBancoDeposito" name="lstBancoDeposito">
				<option value="-1">[ Seleccione ]</option>
			</select>
			<script>
				xajax_cargarBancoCliente("tdBancoDeposito","lstBancoDeposito");
			</script>
		</td>
	</tr>
	<tr id="trNroCuenta" style="display:none">
		<td class="tituloCampo">Nro. Cuenta:</td>
		<td colspan="5"><input type="text" name="txtNroCuentaDeposito" id="txtNroCuentaDeposito" size="30"/></td>
	</tr>
	<tr id="trNroCheque" style="display:none">
		<td class="tituloCampo">Nro. Cheque:</td>
		<td colspan="5"><input type="text" name="txtNroChequeDeposito" id="txtNroChequeDeposito"/></td>
	</tr>
	<tr id="trMonto" style="display:none">
		<td class="tituloCampo">Monto:</td>
		<td colspan="5" align="left">
			<input type="text" name="txtMontoDeposito" id="txtMontoDeposito" onChange="this.value=formato(parsenum(this.value));" onkeypress="return inputnum(event);"/>
			<button type="button" id="btnAgregarMontoDeposito" name="btnAgregarMontoDeposito" onclick="validarDetalleDeposito();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Agregar</td></tr></table></button>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td colspan="5">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">
			<table width="100%" border="0" cellpadding="0">
			<tr class="tituloColumna" align="center">
				<td>Forma de Pago</td>
				<td>Banco</td>
				<td>Nro. Cuenta</td>
				<td>Nro. Cheque</td>
				<td>Monto</td>
				<td>&nbsp;</td>
			</tr>
			<tr id="trItmPieDeposito"></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="tituloColumna">Saldo:</td>
		<td>
			<input name="txtSaldoDepositoBancario" type="text" id="txtSaldoDepositoBancario" readonly="readonly" style="text-align:right" class="trResaltarTotal3"/>
			<input name="hddSaldoDepositoBancario" type="hidden" id="hddSaldoDepositoBancario" readonly="readonly"/>
		</td>
		<td>&nbsp;</td>
		<td class="tituloColumna">Total:</td>
		<td><input name="txtTotalDeposito" type="text" id="txtTotalDeposito" readonly="readonly" style="text-align:right" class="trResaltarTotal"/></td>
	</tr>
	<tr>
		<td colspan="6"><hr/></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td width="54%" colspan="3"><input type="hidden" name="hddObjDetallePagoDeposito" id="hddObjDetallePagoDeposito"/></td>
		<td width="7%" align="right">
			<button type="button" id="btnGuardarDetalleDeposito" name="btnGuardarDetalleDeposito" onclick="validarAgregarDeposito();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
		</td>
		<td width="7%" align="left">
			<button type="button" id="btnCancelarDetalleDeposito" name="btnCancelarDetalleDeposito" onclick="byId('divFlotanteDep').style.display='none'; xajax_eliminarPagoDetalleDepositoForzado(xajax.getFormValues('frmDetalleDeposito'))" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
	</form>
	
	<table border="0" id="tblListadosAnticipoNotaCredito" style="display:none" width="1050px">
	<tr id="trBuscarAnticipoNotaCredito">
		<td>
			<form id="frmBuscarAnticipoNotaCredito" name="frmBuscarAnticipoNotaCredito" onsubmit="byId('btnBuscarAnticipoNotaCredito').click(); return false;" style="margin:0">
			<table align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioAnticipoNotaCredito" name="txtCriterioAnticipoNotaCredito" onkeyup="byId('btnBuscarAnticipoNotaCredito').click();"/></td>
				<td>
					<button type="submit" id="btnBuscarAnticipoNotaCredito" name="btnBuscarAnticipoNotaCredito" onclick="xajax_buscarAnticipoNotaCredito(xajax.getFormValues('frmBuscarAnticipoNotaCredito'),byId('txtIdCliente').value,byId('selTipoPago').value,xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListadoPagos'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarAnticipoNotaCredito'].reset(); byId('btnBuscarAnticipoNotaCredito').click();">Limpiar</button>
				</td>
			</tr>
			</table>
			</form>
		</td>
	</tr>
	<tr>
		<td>
			<form id="frmListado" name="frmListado" style="margin:0" onsubmit="return false;">
			<table width="100%">
			<tr>
				<td id="tdListadoAnticipoNotaCredito"></td>
			</tr>
			<tr>
				<td align="right"><hr>
					<button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotanteDep').style.display = 'none';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
				</td>
			</tr>
			</table>
			</form>
		</td>
	</tr>
	</table>
</div>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
	<form id="frmDetalleAnticipoNotaCredito" name="frmDetalleAnticipoNotaCredito" style="margin:0">
	<table border="0" id="tblDetalleAnticipoNotaCredito" width="290px">
	<tr>
		<td class="tituloCampo" width="120">Nro. Documento:</td>
		<td><input type="text" name="txtNroDocumento" id="txtNroDocumento" size="15"/></td>
	</tr>
	<tr>
		<td class="tituloCampo" width="120">Saldo:</td>
		<td><input type="text" name="txtSaldoDocumento" id="txtSaldoDocumento" readonly="readonly" size="15" style="text-align:right"/></td>
	</tr>
	<tr>
		<td class="tituloCampo" width="120">Monto a Pagar:</td>
		<td><input type="text" name="txtMontoDocumento" id="txtMontoDocumento" onChange="this.value=formato(parsenum(this.value));" onkeypress="return inputnum(event);" size="15" style="text-align:right" class="inputHabilitado"/></td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr/>
			<button type="button" id="btnAceptarMontoDocumento" name="btnAceptarMontoDocumento" onclick="validarAgregarAnticipoNotaCredito();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button><input type="hidden" name="hddIdDocumento" id="hddIdDocumento"/>
			<button type="button" id="btnCancelar" name="btnCancelar" onclick="byId('divFlotante1').style.display='none';" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
	</form>
</div>

<script>
	bloquearForm();
</script>
<script language="javascript">
	var theHandle = document.getElementById("divFlotanteTitulo");
	var theRoot = document.getElementById("divFlotante");
	Drag.init(theHandle, theRoot);
	
	var theHandle2 = document.getElementById("divFlotanteTitulo2");
	var theRoot2 = document.getElementById("divFlotante2");
	Drag.init(theHandle2, theRoot2);
	
	var theHandle = document.getElementById("divFlotanteTituloDep");
	var theRoot = document.getElementById("divFlotanteDep");
	Drag.init(theHandle, theRoot);	
</script>