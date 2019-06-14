<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if((!validaAcceso("al_presupuesto_venta_list","insertar") && !$_GET['id'])
|| (!validaAcceso("al_presupuesto_venta_list","editar") && $_GET['id'] > 0)) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_al_presupuesto_venta_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Alquiler - Presupuesto de Alquiler</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragAlquiler.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>

    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jstimepicker/jquery.timepicker.min.css"/>
    <script type="text/javascript" language="javascript" src="../js/jstimepicker/jquery.timepicker.min.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    	
	<style>
    button{
        cursor:pointer;
    }
    </style>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblLista').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblPrecio').style.display = 'none';
		byId('divLista').innerHTML = '';
		byId('datosItem').style.display = 'none';
		byId('btnGuardarItem').style.display = 'none';
		byId('tblCambiarUnidad').style.display = 'none';
		
		document.forms['frmLista'].reset();
		byId('txtCantidadItem').value = "";
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('trBuscarEmpleado').style.display = 'none';
				byId('trBuscarCliente').style.display = '';
				byId('trBuscarUnidadFisica').style.display = 'none';
				byId('trBuscarAccesorio').style.display = 'none';
				byId('trBuscarAdicional').style.display = 'none';
				
				if(nomObjeto.id == 'aListarClientePago'){
					byId('hddClientePago').value = 1;
				}else{
					byId('hddClientePago').value = 0;
				}
				byId('btnBuscarCliente').click();
				
				tituloDiv1 = 'Clientes';
				byId(verTabla).width = "760";
			} else if (valor == "Empleado") {
				document.forms['frmBuscarEmpleado'].reset();
				
				byId('txtCriterioBuscarEmpleado').className = 'inputHabilitado';
				
				byId('trBuscarEmpleado').style.display = '';
				byId('trBuscarCliente').style.display = 'none';
				byId('trBuscarUnidadFisica').style.display = 'none';
				byId('trBuscarAccesorio').style.display = 'none';
				byId('trBuscarAdicional').style.display = 'none';
				
				byId('btnBuscarEmpleado').click();
				
				tituloDiv1 = 'Empleados';
				byId(verTabla).width = "760";
			} else if (valor == "UnidadFisica") {
				document.forms['frmBuscarUnidadFisica'].reset();
				
				byId('txtCriterioBuscarUnidadFisica').className = 'inputHabilitado';
				
				byId('trBuscarEmpleado').style.display = 'none';
				byId('trBuscarCliente').style.display = 'none';
				byId('trBuscarUnidadFisica').style.display = '';
				byId('trBuscarAccesorio').style.display = 'none';
				byId('trBuscarAdicional').style.display = 'none';
				
				byId('btnBuscarUnidadFisica').click();
				
				tituloDiv1 = 'Vehiculos';
				byId(verTabla).width = "760";
			} else if (valor == "Accesorio"){
				if (validarCampo('txtIdEmpresa','t','') == true
				&& validarCampo('txtIdCliente','t','') == true
				&& validarCampo('txtIdClientePago','t','') == true
				&& validarCampo('lstTipoContrato','t','lista') == true
				&& validarCampo('lstMoneda','t','lista') == true
				&& validarCampo('lstTipoPago','t','listaExceptCero') == true 
				&& validarCampo('txtIdUnidadFisica','t','') == true){
					document.forms['frmBuscarAccesorio'].reset();
					
					byId('txtCriterioBuscarAccesorio').className = 'inputHabilitado';
					
					byId('datosItem').style.display = '';
					byId('btnGuardarItem').style.display = '';
					byId('trBuscarEmpleado').style.display = 'none';
					byId('trBuscarCliente').style.display = 'none';
					byId('trBuscarUnidadFisica').style.display = 'none';
					byId('trBuscarAccesorio').style.display = '';
					byId('trBuscarAdicional').style.display = 'none';
					
					byId('btnBuscarAccesorio').click();
					
					tituloDiv1 = 'Accesorios';
					byId(verTabla).width = "760";
				} else {
					validarCampo('txtIdEmpresa','t','');
					validarCampo('txtIdCliente','t','');
					validarCampo('txtIdClientePago','t','');
					validarCampo('lstTipoContrato','t','lista');
					validarCampo('lstMoneda','t','lista');
					validarCampo('lstTipoPago','t','listaExceptCero');
					validarCampo('txtIdUnidadFisica','t','');
									
					alert('Los campos señalados en rojo son requeridos');
					setTimeout(function(){//si estaba abierto e intenta segunda vez
						byId('btnCancelarLista').click();
					},2000);
					return false;
				}
			} else if (valor == "Adicional"){
				if (validarCampo('txtIdEmpresa','t','') == true
				&& validarCampo('txtIdCliente','t','') == true
				&& validarCampo('txtIdClientePago','t','') == true
				&& validarCampo('lstTipoContrato','t','lista') == true
				&& validarCampo('lstMoneda','t','lista') == true
				&& validarCampo('lstTipoPago','t','listaExceptCero') == true 
				&& validarCampo('txtIdUnidadFisica','t','') == true){
						
					document.forms['frmBuscarAdicional'].reset();
					
					byId('txtCriterioBuscarAdicional').className = 'inputHabilitado';
					
					byId('datosItem').style.display = '';
					byId('btnGuardarItem').style.display = '';
					byId('trBuscarEmpleado').style.display = 'none';
					byId('trBuscarCliente').style.display = 'none';
					byId('trBuscarUnidadFisica').style.display = 'none';
					byId('trBuscarAccesorio').style.display = 'none';
					byId('trBuscarAdicional').style.display = '';
					
					byId('btnBuscarAdicional').click();
					
					tituloDiv1 = 'Adicionales';
					byId(verTabla).width = "760";
				} else {
					validarCampo('txtIdEmpresa','t','');
					validarCampo('txtIdCliente','t','');
					validarCampo('txtIdClientePago','t','');
					validarCampo('lstTipoContrato','t','lista');
					validarCampo('lstMoneda','t','lista');
					validarCampo('lstTipoPago','t','listaExceptCero');
					validarCampo('txtIdUnidadFisica','t','');
									
					alert('Los campos señalados en rojo son requeridos');
					setTimeout(function(){//si estaba abierto e intenta segunda vez
						byId('btnCancelarLista').click();
					},2000);
					return false;
				}
			}
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
		} else if (verTabla == "tblPrecio") {
			if (validarCampo('txtIdEmpresa','t','') == true
			&& validarCampo('txtIdCliente','t','') == true
			&& validarCampo('txtIdClientePago','t','') == true
			&& validarCampo('lstTipoContrato','t','lista') == true
			&& validarCampo('lstMoneda','t','lista') == true
			&& validarCampo('lstTipoPago','t','listaExceptCero') == true 
			&& validarCampo('txtIdUnidadFisica','t','') == true
			&& validarCampo('txtFechaSalida','t','') == true
			&& validarCampo('txtFechaEntrada','t','') == true
			&& validarCampo('txtDiasContrato','t','') == true
			) {
				document.forms['frmBuscarPrecio'].reset();
							
				byId('divListaPrecio').innerHTML = '';
				byId('btnBuscarPrecio').click();
			} else {
				validarCampo('txtIdEmpresa','t','');
				validarCampo('txtIdCliente','t','');
				validarCampo('txtIdClientePago','t','');
				validarCampo('lstTipoContrato','t','lista');
				validarCampo('lstMoneda','t','lista');
				validarCampo('lstTipoPago','t','listaExceptCero');
				validarCampo('txtIdUnidadFisica','t','');
				validarCampo('txtFechaSalida','t','');
				validarCampo('txtFechaEntrada','t','');
				validarCampo('txtDiasContrato','t','');
							
				alert('Los campos señalados en rojo son requeridos');
				setTimeout(function(){//si estaba abierto e intenta segunda vez
					byId('btnCancelarPrecio').click();
				},2000);
				return false;
			}
			
			tituloDiv1 = 'Precios / Tarifas';
		} else if (verTabla == "tblCambiarUnidad") {
			document.forms['frmCambiarUnidad'].reset();
			
			byId('txtIdCambioUnidad').value = byId('txtIdUnidadFisica').value;
			byId('txtPlacaCambioUnidad').value = byId('txtPlacaVehiculo').value
			
			xajax_cargarLstEstadoAdicionalEntradaCambio();
			
			tituloDiv1 = 'Cambio de Vehículo';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			} else if (valor == "Empleado") {
				byId('txtCriterioBuscarEmpleado').focus();
				byId('txtCriterioBuscarEmpleado').select();
			} else if (valor == "UnidadFisica") {
				byId('txtCriterioBuscarUnidadFisica').focus();
				byId('txtCriterioBuscarUnidadFisica').select();
			} else if (valor == "Accesorio") {
				byId('txtCriterioBuscarAccesorio').focus();
				byId('txtCriterioBuscarAccesorio').select();
			} else if (valor == "Adicional") {
				byId('txtCriterioBuscarAdicional').focus();
				byId('txtCriterioBuscarAdicional').select();
			}
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblPrecio") {
			byId('txtCriterioBuscarPrecio').focus();
			byId('txtCriterioBuscarPrecio').select();
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
	
	function calcularDcto(){
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPrecio'), xajax.getFormValues('frmListaAccesorio'), xajax.getFormValues('frmListaAdicional'), xajax.getFormValues('frmTotalDcto'));
	}
	
	function eliminarItemsAccesorio(clase){
		$('.'+clase+':enabled:checked').each(function(){//checkbox con dicha clase que esten habilitados y checados
			if(this.value > 0){
				if(byId('hddIdDetAccesorioEliminar').value == ""){
					byId('hddIdDetAccesorioEliminar').value = this.value;
				}else{
					byId('hddIdDetAccesorioEliminar').value = byId('hddIdDetAccesorioEliminar').value+ ',' +this.value;
				}
			}
			$(this).parent().parent().remove();//tr
			calcularDcto();
		});
	}
	
	function eliminarItemsPrecio(){
		$('.checkboxPrecio:enabled:checked').each(function(){//checkbox con dicha clase que esten habilitados y checados
			if(this.value > 0){  
				if(byId('hddIdDetPrecioEliminar').value == ""){
					byId('hddIdDetPrecioEliminar').value = this.value;
				}else{
					byId('hddIdDetPrecioEliminar').value = byId('hddIdDetPrecioEliminar').value+ ',' +this.value;
				}				
			}
			$(this).parent().parent().remove();//tr
			calcularDcto();
		});
	}
	
	function reasignarPrecio(){//cuando se establece fecha final se debe re-asignar y re-calcular		
		var arrayIdPrecioCargado = $('input[name="hddIdPrecio[]"]'); //siempre agarra el primer item
		var arrayIdPrecioDetalleCargado = $('input[name="hddIdPrecioDetalle[]"]');
		//alert(arrayIdPrecioCargado.length);
		if(arrayIdPrecioCargado.length > 0){		
			$('.checkboxPrecio').each(function(){//elimino todos los precios cargados
				if(this.value > 0){  
					if(byId('hddIdDetPrecioEliminar').value == ""){
						byId('hddIdDetPrecioEliminar').value = this.value;
					}else{
						byId('hddIdDetPrecioEliminar').value = byId('hddIdDetPrecioEliminar').value+ ',' +this.value;
					}				
				}
				$(this).parent().parent().remove();//tr
			});			
		}
		
		$(arrayIdPrecioCargado).each(function(i, item) {//recarga los precios con la cantidad de dias
			//alert(arrayIdPrecioCargado[i].value + " CONC " + arrayIdPrecioDetalleCargado[i].value);
			xajax_asignarPrecio(arrayIdPrecioCargado[i].value, xajax.getFormValues('frmListaPrecio'), xajax.getFormValues('frmDcto'), arrayIdPrecioDetalleCargado[i].value);
		});
		
	}
	
	function seleccionarTodosItems(objCbx, clase){
		$('.'+clase+':enabled').each(function(){//checkbox con dicha clase que esten habilitados
			this.checked = objCbx.checked;
		});
	}
	
	function validarFrmCambioUnidad(){
		if (validarCampo('txtIdCambioUnidad','t','') == true
		&& validarCampo('txtKilometrajeEntradaCambio','t','') == true
		&& validarCampo('lstCombustibleEntradaCambio','t','listaExceptCero') == true
		&& validarCampo('lstEstadoAdicionalEntradaCambio','t','lista') == true
		&& validarCampo('txtMotivoCambio','t','') == true) {
			xajax_asignarCambioUnidad(xajax.getFormValues('frmCambiarUnidad'));
		} else {
			validarCampo('txtIdCambioUnidad','t','');
			validarCampo('txtKilometrajeEntradaCambio','t','');
			validarCampo('lstCombustibleEntradaCambio','t','listaExceptCero');
			validarCampo('lstEstadoAdicionalEntradaCambio','t','lista') == true;
			validarCampo('txtMotivoCambio','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
		
	function validarFrmDcto() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdEmpleado','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtIdClientePago','t','') == true
		&& validarCampo('lstTipoContrato','t','lista') == true
		&& validarCampo('lstEstadoAdicionalSalida','t','lista') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('lstTipoPago','t','listaExceptCero') == true
		&& validarCampo('txtIdUnidadFisica','t','') == true
		&& validarCampo('txtFechaSalida','t','') == true
		&& validarCampo('txtHoraSalida','t','') == true
		&& validarCampo('txtKilometrajeSalida','t','') == true
		&& validarCampo('lstCombustibleSalida','t','listaExceptCero') == true
		&& validarCampo('txtFechaEntrada','t','') == true
		&& validarCampo('txtHoraEntrada','t','') == true
		&& validarCampo('txtDiasContrato','t','cantidad') == true) {
			
			//SINO ES ACTIVOS DEBE TENER ITEM AGREGADO
			if(byId('hddIdFiltroContrato').value != "3"){
				if (($(".checkboxPrecio").length > 0 || $(".checkboxAccesorio").length > 0 || $(".checkboxAdicional").length > 0) ) {				
					//no hacer nada y continua a la confirmacion
				} else {
					alert("Debe agregar items al presupuesto");
					return false;
				}
			}
			
			msj = '¿Seguro desea Guardar el Presupuesto?';
			
			if(byId('hddAcc').value == "1"){//SI ESTA FINALIZANDO VALIDAR DATOS DE ENTRADA
				msj = '¿Seguro desea Aprobar el Presupuesto?';				
			}
			
			if (confirm(msj) == true) {
				xajax_guardarDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPrecio'), xajax.getFormValues('frmListaAccesorio'), xajax.getFormValues('frmListaAdicional'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaCambioUnidades'));
			}

		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdEmpleado','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('txtIdClientePago','t','');
			validarCampo('lstTipoContrato','t','lista');
			validarCampo('lstEstadoAdicionalSalida','t','lista');
			validarCampo('lstMoneda','t','lista');
			validarCampo('lstTipoPago','t','listaExceptCero');
			validarCampo('txtIdUnidadFisica','t','');
			validarCampo('txtFechaSalida','t','');
			validarCampo('txtHoraSalida','t','');
			validarCampo('txtKilometrajeSalida','t','');
			validarCampo('lstCombustibleSalida','t','listaExceptCero');
			validarCampo('txtFechaEntrada','t','');
			validarCampo('txtHoraEntrada','t','');
			validarCampo('txtDiasContrato','t','cantidad');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}

	function validarFrmItem(){
		if (validarCampo('txtCantidadItem','t','monto') == true &&
			validarCampo('txtPrecioItem','t','monto') == true) {
			xajax_asignarAccesorio(byId('hddIdItem').value, xajax.getFormValues('frmListaAccesorio'), xajax.getFormValues('frmListaAdicional'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmLista'), 1);
		} else {
			validarCampo('txtCantidadItem','t','monto');
			validarCampo('txtPrecioItem','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}

	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarSoloTextoNumero(evento) {
		if (arguments.length > 1)
			color = arguments[1];
		
		if (evento.target)
			idObj = evento.target.id
		else if (evento.srcElement)
			idObj = evento.srcElement.id;
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 0)
		&& (teclaCodigo != 8)
		&& (teclaCodigo != 32)
		&& (teclaCodigo < 65 || teclaCodigo > 90)
		&& (teclaCodigo < 97 || teclaCodigo > 122)
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)
		&& (teclaCodigo != 225) /* á */
		&& (teclaCodigo != 233) /* é */
		&& (teclaCodigo != 237) /* í */
		&& (teclaCodigo != 243) /* ó */
		&& (teclaCodigo != 250) /* ú */
		&& (teclaCodigo != 193) /* Á */
		&& (teclaCodigo != 201) /* É */
		&& (teclaCodigo != 205) /* Í */
		&& (teclaCodigo != 211) /* Ó */
		&& (teclaCodigo != 218) /* Ú */
		&& (teclaCodigo != 209) /* Ñ */
		&& (teclaCodigo != 241) /* ñ */
		) {
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_alquiler.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaAlquiler" id="tituloPaginaAlquiler"></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="left">
            <form id="frmDcto" name="frmDcto" style="margin:0">
            <input type="hidden" id="hddIdDetAccesorioEliminar" name="hddIdDetAccesorioEliminar" readonly="readonly"/>
            <input type="hidden" id="hddIdDetPrecioEliminar" name="hddIdDetPrecioEliminar" readonly="readonly"/>
            	<table border="0" width="100%">
                <tr align="left">                    
                    <td width="60%">
                        <table cellpadding="0" width="100%">
                        <tr align="left">
                        	<td align="right" class="tituloCampo" width="16%" ><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;" class="inputHabilitado"/></td>
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
                            <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Empleado:</td>
                            <td>
								<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpleado" name="txtIdEmpleado" onblur="xajax_asignarEmpleado(this.value, 'false');" size="6" style="text-align:right" class="inputHabilitado"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpleado" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Empleado');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45" /></td>
                                </tr>
                                </table>
                            </td>  
                        </tr>
                        </table>
                        
						<fieldset><legend class="legend">Cliente Presupuesto</legend>
                            <table border="0" width="100%">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                <td width="46%">
                                    <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente('', this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false');" size="6" style="text-align:right" class="inputHabilitado"/></td>
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
                                </td>
                                <td align="right" class="tituloCampo" width="16%"><?php echo utf8_encode($spanClienteCxC); ?>:</td>
                                <td width="22%"><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo" rowspan="3">Dirección:</td>
                                <td rowspan="3"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="57" readonly="readonly" rows="2"></textarea></td>
                                <td align="right" class="tituloCampo">Teléfono:</td>
                                <td><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Otro Teléfono:</td>
                                <td><input type="text" id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                            </tr>
                            <tr align="left" style="display:none;">
                                <td align="right" class="tituloCampo">Días Crédito:</td>
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
                        
						<fieldset><legend class="legend">Cliente Pago</legend>
                            <table border="0" width="100%">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                <td width="46%">
                                    <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><input type="text" id="txtIdClientePago" name="txtIdClientePago" onblur="xajax_asignarCliente('Pago',this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false');" size="6" style="text-align:right" class="inputHabilitado"/></td>
                                        <td>
                                        <a class="modalImg" id="aListarClientePago" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Cliente');">
                                            <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                        </a>
                                        </td>
                                        <td><input type="text" id="txtNombreClientePago" name="txtNombreClientePago" readonly="readonly" size="45"/></td>
                                    </tr>
                                    <tr align="center">
                                        <td id="tdMsjClientePago" colspan="3"></td>
                                    </tr>
                                    </table>
                                    <input type="hidden" id="hddPagaImpuestoPago" name="hddPagaImpuestoPago"/>
                                </td>
                                <td align="right" class="tituloCampo" width="16%"><?php echo utf8_encode($spanClienteCxC); ?>:</td>
                                <td width="22%"><input type="text" id="txtRifClientePago" name="txtRifClientePago" readonly="readonly" size="16" style="text-align:right"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo" rowspan="3">Dirección:</td>
                                <td rowspan="3"><textarea id="txtDireccionClientePago" name="txtDireccionClientePago" cols="57" readonly="readonly" rows="5"></textarea></td>
                                <td align="right" class="tituloCampo">Teléfono:</td>
                                <td><input type="text" id="txtTelefonoClientePago" name="txtTelefonoClientePago" readonly="readonly" size="18" style="text-align:center"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Otro Teléfono:</td>
                                <td><input type="text" id="txtOtroTelefonoClientePago" name="txtOtroTelefonoClientePago" readonly="readonly" size="18" style="text-align:center"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Días Crédito:</td>
                                <td>
                                    <table border="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td width="40%">Días:</td>
                                        <td width="60%"><input type="text" id="txtDiasCreditoClientePago" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                    <tr>
                                    <tr>
                                        <td>Disponible:</td>
                                        <td><input type="text" id="txtCreditoClientePago" name="txtCreditoClientePago" readonly="readonly" size="12" style="text-align:right"/></td>
                                    <tr>
                                    </table>
                                </td>
                            </tr>
                            </table>
                        </fieldset>
                    </td>
                    <td>
                        <table>
                        <tr>
							<td width="40%">
                            <fieldset><legend class="legend">Datos del Presupuesto</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Fecha:</td>
			                        <td width="18%"><input type="text" id="txtFechaContrato" name="txtFechaContrato" readonly="readonly" style="text-align:center" size="10"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%">Nro. Presupuesto:</td>
                                    <td width="60%">
                                        <input type="text" id="txtNumeroContrato" name="txtNumeroContrato" readonly="readonly" size="20" style="text-align:center"/>
                                        <input type="hidden" id="hddIdContrato" name="hddIdContrato" readonly="readonly"/>
										<input type="hidden" id="hddAcc" name="hddAcc" readonly="readonly" value="<?php echo $_GET["acc"]; ?>" />
                                        
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo de Contrato:</td>
                                    <td width="20%" id="tdlstTipoContrato">
                                    	<select id="lstTipoContrato" name="lstTipoContrato"> 
                                        	<option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
									<input type="hidden" id="hddIdFiltroContrato" name="hddIdFiltroContrato" readonly="readonly" />
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Estado Veh&iacute;culo Salida:</td>
                                    <td width="20%" id="tdlstEstadoAdicionalSalida">
                                    	<select id="lstEstadoAdicionalSalida" name="lstEstadoAdicionalSalida" class="inputHabilitado"> 
                                        	<option value="">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita" id="spanTextoRojo" style="display:none;">*</span>Estado Veh&iacute;culo Entrada:</td>
                                    <td width="20%" id="tdlstEstadoAdicionalEntrada">
                                    	<select id="lstEstadoAdicionalEntrada" name="lstEstadoAdicionalEntrada" class="inputInicial"> 
                                        	<option value="">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                                    <td id="tdlstMoneda"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo de Pago:</td>
                                    <td width="20%" id="tdlstTipoPago">
                                    	<select id="lstTipoPago" name="lstTipoPago"> 
                                        	<option value="-1">[ Sin Asignar ]</option>
                                        </select>
                                    </td>
                                </tr>
                                </table>
                            </fieldset>
							</td>
                        </tr>    
                        </table>                        
                    </td>                    
				</tr>
                <tr>
                	<td colspan="4">
                    	<table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                        	<td valign="top" width="60%">
        						<fieldset>
                                <legend class="legend">Salida, Entrada, Tiempos</legend>
                                <table border="0" width="100%">
                                <tr class="tituloColumna" align="center">
                                    <td colspan="4">Salida</td>
                                    <td colspan="4">Entrada</td>
                                </tr>
                                <tr>
                                    <td width="14%" align="right" class="tituloCampo" style="white-space: nowrap;"><span class="textoRojoNegrita">*</span>Fecha Salida:</td>
                                    <td width="10%"><input type="text" id="txtFechaSalida" name="txtFechaSalida" size="10" readonly="readonly" class="inputHabilitado" /></td>
                                    <td width="14%" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Hora Salida:</td>
                                    <td width="10%"><input type="text" id="txtHoraSalida" name="txtHoraSalida" size="10" readonly="readonly" class="inputHabilitado" /></td>
                                    <td width="14%" align="right" class="tituloCampo" style="white-space: nowrap;"><span class="textoRojoNegrita">*</span>Fecha Entrada:</td>
                                    <td width="10%"><input type="text" id="txtFechaEntrada" name="txtFechaEntrada" size="10" readonly="readonly" class="inputHabilitado" /></td>
                                    <td width="14%" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Hora Entrada:</td>
                                    <td width="10%"><input type="text" id="txtHoraEntrada" name="txtHoraEntrada" size="10" readonly="readonly" class="inputHabilitado" /></td>
                                </tr>
        
                                <tr>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanKilometraje); ?>:</td>
                                    <td><input type="text" id="txtKilometrajeSalida" name="txtKilometrajeSalida" size="10" class="inputHabilitado" onkeypress="return validarSoloNumeros(event);"/></td>
									<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Combustible:</td>
                                    <td>
                                        <select id="lstCombustibleSalida" name="lstCombustibleSalida" class="inputHabilitado">
                                            <option value="">-</option>
                                            <option value="0.00">0</option>
                                            <option value="0.25">1/4</option>
                                            <option value="0.50">1/2</option>
                                            <option value="0.75">3/4</option>
                                            <option value="1.00">1</option>
                                        </select>
                                    </td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita spanRojoFinalizar" style="display:none;">*</span><?php echo utf8_encode($spanKilometraje); ?>:</td>
                                    <td><input type="text" id="txtKilometrajeEntrada" readonly="readonly" name="txtKilometrajeEntrada" size="10" onkeypress="return validarSoloNumeros(event);"/></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita spanRojoFinalizar" style="display:none;">*</span>Combustible:</td>
                                    <td>
                                        <select id="lstCombustibleEntrada" name="lstCombustibleEntrada" >
                                            <option value="">-</option>
                                            <option value="0.00">0</option>
                                            <option value="0.25">1/4</option>
                                            <option value="0.50">1/2</option>
                                            <option value="0.75">3/4</option>
                                            <option value="1.00">1</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita spanRojoFinalizar" style="display:none;">*</span>Fecha Final:</td>
                                    <td><input type="text" id="txtFechaEntradaFinal" name="txtFechaEntradaFinal" size="10" readonly="readonly" /></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita spanRojoFinalizar" style="display:none;">*</span>Hora Final:</td>
                                    <td><input type="text" id="txtHoraEntradaFinal" name="txtHoraEntradaFinal" size="10" readonly="readonly" /></td>
                                </tr>
                                <tr class="tituloColumna" align="center">
                                    <td colspan="8">Tiempos</td>
                                </tr>
                                <tr>
                                	<td align="right" class="tituloCampo">D&iacute;as Contrato:</td>
                                    <td><input type="text" id="txtDiasContrato" name="txtDiasContrato" size="10" readonly="readonly" class="inputInicial"/></td>
                                    <td align="right" class="tituloCampo">D&iacute;as Sobre Tiempo:</td>
                                    <td><input type="text" id="txtDiasSobreTiempo" name="txtDiasSobreTiempo" size="10" readonly="readonly" class="inputInicial" /></td>
                                	<td align="right" class="tituloCampo">D&iacute;as Bajo Tiempo:</td>
                                    <td><input type="text" id="txtDiasBajoTiempo" name="txtDiasBajoTiempo" size="10" readonly="readonly" class="inputInicial"/></td>
                                    <td align="right" class="tituloCampo">D&iacute;as Total:</td>
                                    <td><input type="text" id="txtDiasTotal" name="txtDiasTotal" size="10" readonly="readonly" class="inputInicial"/></td>
                                </tr>
                                </table>
                                </fieldset>
                            </td>
                            <td valign="top" width="40%">
                                <fieldset><legend class="legend">Datos del Veh&iacute;culo</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                	<td align="right" width="21%" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro Unidad:</td>
                                    <td>
										<table cellspacing="0" cellpadding="0">
                                        <tr>
                                        	<td><input type="text" style="text-align:right" id="txtIdUnidadFisica" name="txtIdUnidadFisica" readonly="readonly" size="6">
                                            </td>
                                            <td>
                                            	<a onclick="abrirDivFlotante1(this, 'tblLista', 'UnidadFisica');" rel="#divFlotante1" id="aListarUnidadFisica" class="modalImg">
                                                <button title="Listar" type="button"><img src="../img/iconos/help.png"></button>
                                                </a>
                                                <a onclick="
                                                if(byId('hddVehiculoYaCambiado').value == 1){
                                                	byId('aListarUnidadFisica').click();
                                                }else{
                                                	abrirDivFlotante1(this, 'tblCambiarUnidad', 'cambiarUnidadFisica');
                                                }" rel="#divFlotante1" id="aCambiarUnidadFisica" class="modalImg" style="display:none;">
                                                <button title="Cambiar Unidad" type="button"><img src="../img/iconos/pencil.png"></button>
                                                </a>
                                                <input type="hidden" name="hddVehiculoYaCambiado" id="hddVehiculoYaCambiado" value="" />
 											</td>
	                                    </tr>
                                    	</table>
                        			</td>
                                    <td align="right" class="tituloCampo">Clase:</td>
                                    <td>
                                    	<input type="hidden" size="18" name="hddIdClase" id="hddIdClase">
                                    	<input type="text" size="18" readonly="readonly" name="txtClaseVehiculo" id="txtClaseVehiculo">
                                    </td>
                                </tr>
                                
                                <tr align="left">
                                    <td align="right" width="21%" class="tituloCampo"><?php echo utf8_encode($spanPlaca); ?>:</td>
                                    <td width="29%">
                                        <input type="text" style="text-align:center" size="18" readonly="readonly" name="txtPlacaVehiculo" id="txtPlacaVehiculo">
                                    </td>
                                    <td align="right" width="21%" class="tituloCampo">Año:</td>
                                    <td width="29%"><input type="text" style="text-align:center" size="18" readonly="readonly" name="txtAnoVehiculo" id="txtAnoVehiculo">
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanSerialCarroceria); ?>:</td>
                                    <td>
                                        <input type="text" style="text-align:center" size="18" readonly="readonly" name="txtSerialCarroceriaVehiculo" id="txtSerialCarroceriaVehiculo">
                                    </td>
                                    <td align="right" class="tituloCampo">Color:</td>
                                    <td><input type="text" size="18" readonly="readonly" name="txtColorVehiculo" id="txtColorVehiculo"></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Marca:</td>
                                    <td><input type="text" size="18" readonly="readonly" name="txtMarcaVehiculo" id="txtMarcaVehiculo"></td>
                                    <td align="right" class="tituloCampo">Unidad Básica:</td>
                                    <td>
                                        <input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica">
                                        <input type="text" size="18" readonly="readonly" id="txtUnidadBasica" name="txtUnidadBasica">
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Modelo:</td>
                                    <td>
                                        <input type="hidden" name="hddIdModelo" id="hddIdModelo">
                                        <input type="text" size="18" readonly="readonly" name="txtModeloVehiculo" id="txtModeloVehiculo">
                                    </td>
                                    <td align="right" class="tituloCampo">Almac&eacute;n:</td>
                                    <td><input type="text" size="18" readonly="readonly" id="txtAlmacenVehiculo" name="txtAlmacenVehiculo"></td>
                                </tr>
                                <tr align="left">
                                	<td align="right" class="tituloCampo">Condici&oacute;n:</td>
                                    <td><input type="text" size="18" readonly="readonly" id="txtCondicionVehiculo" name="txtCondicionVehiculo"></td>
                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanKilometraje); ?>:</td>
                                    <td><input type="text" size="18" readonly="readonly" id="txtKilometrajeVehiculo" name="txtKilometrajeVehiculo"></td>
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
        	<td>
	        <fieldset>
	            <legend class="legend">Cambios de Vehículos</legend>
            	<table border="0" width="100%">
                <tr>
                    <td>
                    <form id="frmListaCambioUnidades" name="frmListaCambioUnidades" style="margin:0">
                        <table class="tablaResaltarPar"  border="0" width="100%">
                        <thead>
                            <tr align="center" class="tituloColumna">
                                <td width="20%">Vehículo Anterior</td>
                                <td width="10%">Fecha Cambio</td>
                                <td width="20%">Empleado Cambio</td>
                                <td width="30%">Motivo</td>
                            </tr>
                        </thead>
                        <tbody>
	                        <tr id="trItmPieCambioUnidad"></tr>
                        </tbody>
                        </table>
                    </form>
                    </td>
                </tr>
                </table>   
            </fieldset>             
            </td>
        </tr>
        <tr>
        	<td>
	        <fieldset>
	            <legend class="legend">Precio / Tarifa</legend>
            	<table border="0" width="100%">
                <tr>
                    <td align="left">
                        <a class="modalImg" id="aAgregarPrecio" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPrecio');">
                            <button type="button" title="Agregar Precio"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button type="button" id="btnQuitarPrecio" name="btnQuitarPrecio" onclick="eliminarItemsPrecio();" title="Eliminar Precio"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
                </tr>
                <tr>
                    <td>
                    <form id="frmListaPrecio" name="frmListaPrecio" style="margin:0">
                        <table class="tablaResaltarPar"  border="0" width="100%">
                        <thead>
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" onclick="seleccionarTodosItems(this, 'checkboxPrecio');"/></td>
                                <td width="20%">Código</td>
                                <td width="30%">Descripción</td>
                                <td width="10%">Días</td>
                                <td width="15%">Precio</td>
                                <td width="15%">% Impuesto</td>
                                <td width="10%">Total</td>
                            </tr>
                        </thead>
                        <tbody>
	                        <tr id="trItmPiePrecio"></tr>
                        </tbody>
                        </table>
                    </form>
                    </td>
                </tr>
                </table>   
            </fieldset>             
            </td>
        </tr>
        <tr>
        	<td>
	        <fieldset>
	            <legend class="legend">Accesorios</legend>
            	<table border="0" width="100%">
                <tr>
                    <td align="left">
                        <a class="modalImg" id="aAgregarAccesorio" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Accesorio');">
                            <button type="button" title="Agregar Accesorio"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button type="button" id="btnQuitarAccesorio" name="btnQuitarAccesorio" onclick="eliminarItemsAccesorio('checkboxAccesorio');" title="Eliminar Accesorio"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
                </tr>
                <tr>
                    <td>
                    <form id="frmListaAccesorio" name="frmListaAccesorio" style="margin:0">
                        <table class="tablaResaltarPar" border="0" width="100%">
                        <thead>
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" onclick="seleccionarTodosItems(this,'checkboxAccesorio');"/></td>
                                <td width="20%">Código</td>
                                <td width="30%">Descripción</td>
                                <td width="10%">Cantidad</td>
                                <td width="15%">Precio</td>
                                <td width="15%">% Impuesto</td>
                                <td width="10%">Total</td>
                            </tr>
                        </thead>
                        <tbody>
                        	<tr id="trItmPieAccesorio"></tr>
                        </tbody>
                        </table>
                    </form>
                    </td>
                </tr>
                </table>   
            </fieldset>             
            </td>
        </tr>
        <tr>
        	<td>
	        <fieldset>
	            <legend class="legend">Adicionales</legend>
            	<table border="0" width="100%">
                <tr>
                    <td align="left">
                        <a class="modalImg" id="aAgregarAdicional" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Adicional');">
                            <button type="button" title="Agregar Adicional"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button type="button" id="btnQuitarAdicional" name="btnQuitarAdicional" onclick="eliminarItemsAccesorio('checkboxAdicional');" title="Eliminar Adicional"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                    </td>
                </tr>
                <tr>
                    <td>
                    <form id="frmListaAdicional" name="frmListaAdicional" style="margin:0">
                        <table class="tablaResaltarPar" border="0" width="100%">
                        <thead>
                            <tr align="center" class="tituloColumna">
                                <td><input type="checkbox" onclick="seleccionarTodosItems(this,'checkboxAdicional');"/></td>
                                <td width="20%">Código</td>
                                <td width="30%">Descripción</td>
                                <td width="10%">Cantidad</td>
                                <td width="15%">Precio</td>
                                <td width="15%">% Impuesto</td>
                                <td width="10%">Total</td>
                            </tr>
                        </thead>
                        <tbody>
                        	<tr id="trItmPieAdicional"></tr>
                        </tbody>
                        </table>
                    </form>
                    </td>
                </tr>
                </table>   
            </fieldset>             
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmTotalDcto" name="frmTotalDcto" style="margin:0">
                <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>                
                <table border="0" width="100%">
                <tr>
                	<td valign="top" width="50%">                    
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
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearDescuento" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'al_contrato_venta_form_descuento');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
										<input type="hidden" id="hddConfig500" name="hddConfig500"/>
                                    </td>
                                	<td nowrap="nowrap">
										<input type="text" id="txtDescuento" name="txtDescuento" onblur="setFormatoRafk(this,2); calcularDcto();" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" size="6" style="text-align:right"/>%
									</td>
								</tr>
                                </table>
							</td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtSubTotalDescuento" name="txtSubTotalDescuento" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <!--AQUI SE INSERTAN LAS FILAS PARA EL IMPUESTO-->
                        <tr id="trNetoContrato" align="right" class="trResaltarTotal">
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
				<tr id="trMensajeCerrar" style="display:none;">
                	<td></td>
					<td>
                        <table cellspacing="0" cellpadding="0" width="100%" class="divMsjInfo2" >
                        <tr>
                            <td width="25"><img width="25" src="../img/iconos/ico_info.gif"></td>
                            <td align="center" id="tdMensajeCerrar"> 	                           
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
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
            	<a class="modalImg" id="aGuardar" rel="#divFlotante1"></a>
                <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('al_presupuesto_venta_list.php','_self');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
                <td>
                	<input type="hidden" id="hddClientePago" name="hddClientePago" />
                	<input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/>
                </td>
                <td>
                	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarUnidadFisica">
    	<td>
        <form id="frmBuscarUnidadFisica" name="frmBuscarVehiculo" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarUnidadFisica" name="txtCriterioBuscarUnidadFisica" onkeyup="byId('btnBuscarUnidadFisica').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarUnidadFisica" name="btnBuscarUnidadFisica" onclick="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscarUnidadFisica'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarUnidadFisica'].reset(); byId('btnBuscarUnidadFisica').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarAccesorio">
    	<td>
        <form id="frmBuscarAccesorio" name="frmBuscarAccesorio" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarAccesorio" name="txtCriterioBuscarAccesorio" onkeyup="byId('btnBuscarAccesorio').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarAccesorio" name="btnBuscarAccesorio" onclick="xajax_buscarAccesorio(xajax.getFormValues('frmBuscarAccesorio'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarAccesorio'].reset(); byId('btnBuscarAccesorio').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr id="trBuscarAdicional">
    	<td>
        <form id="frmBuscarAdicional" name="frmBuscarAdicional" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarAdicional" name="txtCriterioBuscarAdicional" onkeyup="byId('btnBuscarAdicional').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarAdicional" name="btnBuscarAdicional" onclick="xajax_buscarAdicional(xajax.getFormValues('frmBuscarAdicional'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarAdicional'].reset(); byId('btnBuscarAdicional').click();">Limpiar</button>
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
            	<td><div id="divLista" style="width:100%; max-height: 500px;  overflow-y:auto;"></div></td>
			</tr>
            <tr id="datosItem" style="display:none;">
            	<td>
                    <table>
                    <tr>
                    	<td align="right" class="tituloCampo">C&oacute;digo:</td>
                    	<td colspan="3">
                        	<input type="hidden" style="text-align:left" name="hddIdItem" id="hddIdItem">
                        	<input type="text" style="text-align:left" readonly="readonly" class="inputSinFondo" name="txtCodigoItem" id="txtCodigoItem">
                        </td>
                    </tr>
                    <tr>
                    	<td align="right" class="tituloCampo">Descripci&oacute;n:</td>
                    	<td colspan="3">
                        	<input type="text" style="text-align:left" readonly="readonly" class="inputSinFondo" name="txtDescripcionItem" id="txtDescripcionItem">
                        </td>
                    </tr>
                    <tr align="left">                    
                        <td align="right" class="tituloCampo" width="100"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                        <td>
                            <input type="text" style="text-align:right;" size="12" maxlength="6" name="txtCantidadItem" id="txtCantidadItem" class="inputHabilitado" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);">
                        </td>
                        <td align="right" class="tituloCampo" width="100"><span class="textoRojoNegrita">*</span>Precio:</td>
                        <td>
                            <input type="text" style="text-align:right;" size="12" maxlength="6" name="txtPrecioItem" id="txtPrecioItem" class="inputHabilitado" onkeypress="return validarSoloNumerosReales(event);" onblur="setFormatoRafk(this,2);">
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>  
            <tr>
                <td align="right"><hr>
                	<button type="button" id="btnGuardarItem" name="btnGuardarItem" onclick="validarFrmItem();">Aceptar</button>
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
                <td><input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
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
	
    <table border="0" id="tblPrecio" style="display:none" width="960">
    <tr>
    	<td>
        <form id="frmBuscarPrecio" name="frmBuscarPrecio" onsubmit="return false;" style="margin:0">
        	<table align="right" border="0">
            <tr align="left">            	
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarPrecio" name="txtCriterioBuscarPrecio" class="inputHabilitado"/></td>
                <td align="right" nowrap="nowrap">
                	<button type="submit" id="btnBuscarPrecio" name="btnBuscarPrecio" onclick="xajax_buscarPrecio(xajax.getFormValues('frmBuscarPrecio'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaPrecio'), xajax.getFormValues('frmTotalDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarPrecio'].reset(); byId('btnBuscarPrecio').click();">Limpiar</button>
				</td>
			</tr>
			</table>
        </form>
		</td>
    </tr>
    <tr>
    	<td><div id="divListaPrecio" style="width:100%"></div></td>
    </tr>    
    <tr>
        <td align="right" colspan="5"><hr>
            <button class="close" name="btnCancelarPrecio" id="btnCancelarPrecio" type="button">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblCambiarUnidad" style="display:none" width="500">
    <tr>
    	<td>
        <form id="frmCambiarUnidad" name="frmCambiarUnidad" onsubmit="return false;" style="margin:0">
        	<table border="0">
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro Unidad:</td>
                <td><input type="text" readonly="readonly" id="txtIdCambioUnidad" name="txtIdCambioUnidad" size="6" /></td>
                <td align="right" class="tituloCampo"><?php echo utf8_encode($spanPlaca); ?>:</td>
                <td><input type="text" readonly="readonly" id="txtPlacaCambioUnidad" name="txtPlacaCambioUnidad" /></td>
            </tr>
            <tr align="left">            	
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanKilometraje); ?>:</td>
                <td><input type="text" id="txtKilometrajeEntradaCambio" name="txtKilometrajeEntradaCambio" size="10" onkeypress="return validarSoloNumeros(event);" class="inputHabilitado"/></td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Combustible:</td>
                <td>
                    <select id="lstCombustibleEntradaCambio" name="lstCombustibleEntradaCambio" class="inputHabilitado">
                        <option value="">-</option>
                        <option value="0.00">0</option>
                        <option value="0.25">1/4</option>
                        <option value="0.50">1/2</option>
                        <option value="0.75">3/4</option>
                        <option value="1.00">1</option>
                    </select>
                </td>
			</tr>
			<tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estado Vehículo:</td>
                <td colspan="3" id="tdlstEstadoAdicionalEntradaCambio">
                    <select class="inputHabilitado" name="lstEstadoAdicionalEntradaCambio" id="lstEstadoAdicionalEntradaCambio"> 
                        <option value="">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo de Cambio:</td>
                <td colspan="3"><input type="text" id="txtMotivoCambio" name="txtMotivoCambio" onkeypress="return validarSoloTextoNumero(event);" size="40" class="inputHabilitado"/></td>
            </tr>
			</table>
        </form>
		</td>
    </tr>
    <tr>
        <td align="right" colspan="5"><hr>
        	<button name="btnGuardarCambiarUnidad" id="btnGuardarCambiarUnidad" onclick="validarFrmCambioUnidad();" type="button">Guardar</button>
            <button class="close" name="btnCancelarCambiarUnidad" id="btnCancelarCambiarUnidad" type="button">Cerrar</button>
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

<?php if(!esFinalizar()){ ?>
	fecha1 = new JsDatePick({
		useMode:2,
		target:"txtFechaSalida",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"naranja"
	});
	
	fecha2 = new JsDatePick({
		useMode:2,
		target:"txtFechaEntrada",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"naranja"
	});	
<?php }?>
	
<?php if(esFinalizar()){ ?>	
	/*fecha3 = new JsDatePick({
		useMode:2,
		target:"txtFechaEntradaFinal",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"naranja"
	});*/
<?php }?>

<?php if(!esFinalizar()){ ?>	
	fecha1.setOnSelectedDelegate(function(){
		byId('txtFechaSalida').value = this.getSelectedDayFormatted();
		this.closeCalendar();
		xajax_calcularTiempo(xajax.getFormValues('frmDcto'));
	});
	fecha2.setOnSelectedDelegate(function(){
		byId('txtFechaEntrada').value = this.getSelectedDayFormatted();
		this.closeCalendar();
		xajax_calcularTiempo(xajax.getFormValues('frmDcto'), 1);
	});
<?php }?>

<?php if(esFinalizar()){ ?>
	/*fecha3.setOnSelectedDelegate(function(){
		byId('txtFechaEntradaFinal').value = this.getSelectedDayFormatted();
		this.closeCalendar();
		xajax_calcularTiempo(xajax.getFormValues('frmDcto'), 1);	
	});*/
<?php }?>

<?php if(!esFinalizar()){ ?>
	$('#txtHoraSalida').timepicker({
		scrollbar: true, startTime:'6', 
		minTime: '6', maxTime: '19', 
		dynamic:false/*, 
		timeFormat: 'HH:mm:ss'*/,
		change: function(time) {
			xajax_calcularTiempo(xajax.getFormValues('frmDcto'), 1);
        } 
	});
		
	$('#txtHoraEntrada').timepicker({
		scrollbar: true,
		startTime:'6',
		minTime: '6',
		maxTime: '19',
		dynamic:false,
		change: function(time) {
			xajax_calcularTiempo(xajax.getFormValues('frmDcto'), 1);
        } 		 
	});
<?php }?>

<?php if(esFinalizar()){ ?>
	/*$('#txtHoraEntradaFinal').timepicker({ 
		scrollbar: true, 
		startTime:'6', 
		minTime: '6', 
		maxTime: '19', 
		dynamic:false,
		change: function(time) {
			xajax_calcularTiempo(xajax.getFormValues('frmDcto'), 1);
        } 
	});*/
<?php }?>

byId('hddIdDetAccesorioEliminar').value = "";//importante limpiar
byId('hddIdDetPrecioEliminar').value = "";

xajax_cargarDcto('<?php echo $_GET['id']; ?>', xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

</script>