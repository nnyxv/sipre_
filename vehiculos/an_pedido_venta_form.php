<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_pedido_venta_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_pedido_venta_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Pedido de Venta</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
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
		tblListaEmpresa = (byId('tblListaEmpresa').style.display == '') ? '' : 'none';
		tblLista = (byId('tblLista').style.display == '') ? '' : 'none';
		tblListaAdicional = (byId('tblListaAdicional').style.display == '') ? '' : 'none';
		tblArticulo = (byId('tblArticulo').style.display == '') ? '' : 'none';
		tblPermiso = (byId('tblPermiso').style.display == '') ? '' : 'none';
		
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblLista').style.display = 'none';
		byId('tblListaAdicional').style.display = 'none';
		byId('tblArticulo').style.display = 'none';
		byId('tblPermiso').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante11') != undefined) {
			byId('imgCerrarDivFlotante11').onclick = function () {
				byId('tblListaEmpresa').style.display = tblListaEmpresa;
				byId('tblLista').style.display = tblLista;
				byId('tblListaAdicional').style.display = tblListaAdicional;
				byId('tblArticulo').style.display = tblArticulo;
				byId('tblPermiso').style.display = tblPermiso;
				
				setTimeout(function(){
					(byId('imgCerrarDivFlotante11') == undefined) ? byId('imgCerrarDivFlotante1').id = 'imgCerrarDivFlotante11' : '';
					byId('imgCerrarDivFlotante12').id = 'imgCerrarDivFlotante1';
					
					byId('imgCerrarDivFlotante11').style.display = 'none';
					byId('imgCerrarDivFlotante1').style.display = '';
				}, 5);
			};
		}
		
		if (byId('imgCerrarDivFlotante11') == undefined && byId('imgCerrarDivFlotante1') != undefined
		&& byId('imgCerrarDivFlotante1').className == 'puntero') {
			byId('imgCerrarDivFlotante1').id = 'imgCerrarDivFlotante11';
		}
		
		if (byId('imgCerrarDivFlotante12') == undefined && byId('imgCerrarDivFlotante1') != undefined
		&& byId('imgCerrarDivFlotante1').className == 'close puntero') {
			byId('imgCerrarDivFlotante1').id = 'imgCerrarDivFlotante12';
		}
		
		if (nomObjeto != null) {
			byId('imgCerrarDivFlotante12').id = 'imgCerrarDivFlotante1';
			
			byId('imgCerrarDivFlotante11').style.display = 'none';
			byId('imgCerrarDivFlotante1').style.display = '';
		} else {
			byId('imgCerrarDivFlotante11').id = 'imgCerrarDivFlotante1';
			
			byId('imgCerrarDivFlotante1').style.display = '';
			byId('imgCerrarDivFlotante12').style.display = 'none';
		}
		
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
			byId('btnCancelarListaEmpresa').onclick = function () { byId('imgCerrarDivFlotante1').click(); }
		} else if (verTabla == "tblLista") {
			byId('trBuscarCliente').style.display = 'none';
			byId('trBuscarEmpleado').style.display = 'none';
			byId('trBuscarUnidadBasica').style.display = 'none';
			byId('trBuscarUnidadFisica').style.display = 'none';
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('trBuscarCliente').style.display = '';
				byId('btnGuardarLista').style.display = 'none';
				
				byId('btnBuscarCliente').click();
				
				tituloDiv1 = 'Clientes';
				byId(verTabla).width = "760";
			} else if (valor == "Empleado") {
				document.forms['frmBuscarEmpleado'].reset();
				
				byId('txtCriterioBuscarEmpleado').className = 'inputHabilitado';
				
				byId('trBuscarEmpleado').style.display = '';
				byId('btnGuardarLista').style.display = 'none';
				
				byId('btnBuscarEmpleado').click();
				
				tituloDiv1 = 'Empleados';
				byId(verTabla).width = "760";
			} else if (valor == "Gasto") {
				document.forms['frmLista'].reset();
				
				byId('btnGuardarLista').style.display = '';
				
				xajax_formGastosArticulo(xajax.getFormValues('frmListaArticulo'), valor2);
				
				tituloDiv1 = 'Cargos del Artículo';
				byId(verTabla).width = "960";
			} else if (valor == "UnidadBasica") {
				document.forms['frmBuscarUnidadBasica'].reset();
				
				byId('txtCriterioBuscarUnidadBasica').className = 'inputHabilitado';
				
				byId('trBuscarUnidadBasica').style.display = '';
				byId('btnGuardarLista').style.display = 'none';
				
				byId('hddObjDestinoUnidadBasica').value = 'Lista';
				
				byId('btnBuscarUnidadBasica').click();
				
				xajax_cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "BuscarUnidadBasica", "true", "", "", "byId('btnBuscarUnidadBasica').click();");
				xajax_cargaLstAnoBuscar('lstAnoBuscarUnidadBasica', '', "byId('btnBuscarUnidadBasica').click();");
				
				tituloDiv1 = 'Unidades Básicas';
				byId(verTabla).width = "760";
			} else if (valor == "UnidadFisica") {
				document.forms['frmBuscarUnidadFisica'].reset();
				
				byId('txtCriterioBuscarUnidadFisica').className = 'inputHabilitado';
				
				byId('trBuscarUnidadFisica').style.display = '';
				byId('btnGuardarLista').style.display = 'none';
				
				byId('btnBuscarUnidadFisica').click();
				
				tituloDiv1 = 'Unidades Físicas';
				byId(verTabla).width = "760";
			}
			byId('btnCancelarLista').onclick = function () { byId('imgCerrarDivFlotante1').click(); }
		} else if (verTabla == "tblListaAdicional") {
			document.forms['frmBuscarAdicional'].reset();
			
			byId('txtCriterioBuscarAdicional').className = 'inputHabilitado';
			
			byId('btnGuardarLista').style.display = 'none';
			
			byId('btnBuscarAdicional').click();
			
			tituloDiv1 = 'Adicionales';
			byId('btnCancelarListaAdicional').onclick = function () { byId('imgCerrarDivFlotante1').click(); }
			byId(verTabla).width = "760";
		} else if (verTabla == "tblArticulo") {
			if (validarCampo('txtIdEmpresa','t','') == true
			&& validarCampo('txtIdCliente','t','') == true) {
				document.forms['frmBuscarArticulo'].reset();
				document.forms['frmDatosArticulo'].reset();
				byId('txtDescripcionArt').innerHTML = '';
				byId('hddIdIvaArt').value = '';
				
				byId('txtCodigoArt').className = 'inputInicial';   
				byId('txtCantidadArt').className = 'inputHabilitado';
				byId('txtPrecioArt').className = 'inputHabilitado';
				
				byId('tdMsjArticulo').style.display = 'none';
				
				cerrarVentana = false;
				
				byId('divListaArticulo').innerHTML = '';
			} else {
				validarCampo('txtIdEmpresa','t','');
				validarCampo('txtIdCliente','t','');
				
				alert('Los campos señalados en rojo son requeridos');
				return false;
			}
			
			tituloDiv1 = 'Artículos';
			byId('btnCancelarArticulo').onclick = function () { byId('imgCerrarDivFlotante1').click(); }
		} else if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModuloPermiso').value = '';
			
			byId('txtContrasenaPermiso').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv1 = 'Ingreso de Clave Especial';
			byId('btnCancelarPermiso').onclick = function () { byId('imgCerrarDivFlotante1').click(); }
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			} else if (valor == "Empleado") {
				byId('txtCriterioBuscarEmpleado').focus();
				byId('txtCriterioBuscarEmpleado').select();
			} else if (valor == "Gasto") {
				if (byId('txtMontoGasto1') != undefined) {
					byId('txtMontoGasto1').focus();
					byId('txtMontoGasto1').select();
				}
			} else if (valor == "UnidadBasica") {
				byId('txtCriterioBuscarUnidadBasica').focus();
				byId('txtCriterioBuscarUnidadBasica').select();
			} else if (valor == "UnidadFisica") {
				byId('txtCriterioBuscarUnidadFisica').focus();
				byId('txtCriterioBuscarUnidadFisica').select();
			}
		} else if (verTabla == "tblListaAdicional") {
			byId('txtCriterioBuscarAdicional').focus();
			byId('txtCriterioBuscarAdicional').select();
		} else if (verTabla == "tblPermiso") {
			byId('txtContrasenaPermiso').focus();
			byId('txtContrasenaPermiso').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		tblCliente = (byId('tblCliente').style.display == '') ? '' : 'none';
		
		byId('tblCliente').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante21') != undefined) {
			byId('imgCerrarDivFlotante21').onclick = function () {
				byId('tblCliente').style.display = tblCliente;
				
				setTimeout(function(){
					(byId('imgCerrarDivFlotante21') == undefined) ? byId('imgCerrarDivFlotante2').id = 'imgCerrarDivFlotante21' : '';
					byId('imgCerrarDivFlotante22').id = 'imgCerrarDivFlotante2';
					
					byId('imgCerrarDivFlotante21').style.display = 'none';
					byId('imgCerrarDivFlotante2').style.display = '';
				}, 5);
			};
		}
		
		if (byId('imgCerrarDivFlotante21') == undefined && byId('imgCerrarDivFlotante2') != undefined
		&& byId('imgCerrarDivFlotante2').className == 'puntero') {
			byId('imgCerrarDivFlotante2').id = 'imgCerrarDivFlotante21';
		}
		
		if (byId('imgCerrarDivFlotante22') == undefined && byId('imgCerrarDivFlotante2') != undefined
		&& byId('imgCerrarDivFlotante2').className == 'close puntero') {
			byId('imgCerrarDivFlotante2').id = 'imgCerrarDivFlotante22';
		}
		
		if (nomObjeto != null) {
			byId('imgCerrarDivFlotante22').id = 'imgCerrarDivFlotante2';
			
			byId('imgCerrarDivFlotante21').style.display = 'none';
			byId('imgCerrarDivFlotante2').style.display = '';
		} else {
			byId('imgCerrarDivFlotante21').id = 'imgCerrarDivFlotante2';
			
			byId('imgCerrarDivFlotante2').style.display = '';
			byId('imgCerrarDivFlotante22').style.display = 'none';
		}
		
		if (verTabla == "tblCliente") {
			document.forms['frmCliente'].reset();
			byId('hddIdCliente').value = '';
			
			byId('lstContribuyente').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			
			byId('txtUrbanizacion').className = 'inputHabilitado';
			byId('txtCalle').className = 'inputHabilitado';
			byId('txtCasa').className = 'inputHabilitado';
			byId('txtMunicipio').className = 'inputHabilitado';
			byId('txtCiudad').className = 'inputHabilitado';
			byId('txtEstado').className = 'inputHabilitado';
			byId('txtTelefono').className = 'inputHabilitado';
			byId('txtOtroTelefono').className = 'inputHabilitado';
			byId('txtCorreo').className = 'inputHabilitado';
			
			byId('txtUrbanizacionPostalCliente').className = 'inputHabilitado';
			byId('txtCallePostalCliente').className = 'inputHabilitado';
			byId('txtCasaPostalCliente').className = 'inputHabilitado';
			byId('txtMunicipioPostalCliente').className = 'inputHabilitado';
			byId('txtCiudadPostalCliente').className = 'inputHabilitado';
			byId('txtEstadoPostalCliente').className = 'inputHabilitado';
			
			byId('lstReputacionCliente').className = 'inputHabilitado';
			byId('lstTipoCliente').className = 'inputHabilitado';
			byId('lstDescuento').className = 'inputInicial';
			byId('txtFechaDesincorporar').className = 'inputHabilitado';
			
			byId('txtCedulaContacto').className = 'inputHabilitado';
			byId('txtNombreContacto').className = 'inputHabilitado';
			byId('txtTelefonoContacto').className = 'inputHabilitado';
			byId('txtCorreoContacto').className = 'inputCompletoHabilitado';
			
			xajax_formCliente(valor, xajax.getFormValues('frmCliente'));
			
			if (valor > 0) {
				byId('lstTipo').className = 'inputInicial';
				byId('txtCedula').className = 'inputInicial';
				byId('txtNombre').className = 'inputInicial';
				byId('txtApellido').className = 'inputInicial';
				byId('txtNit').className = 'inputInicial';
				byId('txtLicencia').className = 'inputHabilitado';
				
				byId('txtCedula').readOnly = true;
				byId('aDesbloquearCedula').style.display = '';
				byId('txtNombre').readOnly = true;
				byId('txtApellido').readOnly = true;
				byId('aDesbloquearNombre').style.display = '';
				byId('txtNit').readOnly = true;
				
				tituloDiv2 = 'Editar Cliente';
			} else {
				byId('lstTipo').className = 'inputHabilitado';
				byId('txtCedula').className = 'inputHabilitado';
				byId('txtNombre').className = 'inputHabilitado';
				byId('txtApellido').className = 'inputHabilitado';
				byId('txtNit').className = 'inputHabilitado';
				byId('txtLicencia').className = 'inputHabilitado';
				
				byId('txtCedula').readOnly = false;
				byId('aDesbloquearCedula').style.display = 'none';
				byId('txtNombre').readOnly = false;
				byId('txtApellido').readOnly = false;
				byId('aDesbloquearNombre').style.display = 'none';
				byId('txtNit').readOnly = false;
				
				tituloDiv2 = 'Agregar Cliente';
			}
			byId('btnCancelarCliente').onclick = function () { byId('imgCerrarDivFlotante2').click(); }
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblCliente") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		}
	}
	
	function validarFrmDatosArticulo() {
		error = false;
		if (!(validarCampo('txtCodigoArt','t','') == true
		&& validarCampo('txtCantidadArt','t','cantidad') == true
		&& validarCampo('lstPrecioArt','t','lista') == true
		&& validarCampo('txtPrecioArt','t','monto') == true)) {
			validarCampo('txtCodigoArt','t','');
			validarCampo('txtCantidadArt','t','cantidad');
			validarCampo('lstPrecioArt','t','lista');
			validarCampo('txtPrecioArt','t','monto');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_insertarArticulo(xajax.getFormValues('frmDatosArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
	}
	
	function validarFrmDcto() {
		error = false;
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtIdEmpleado','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('lstMoneda','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtFechaReserva','t','fecha') == true
		&& validarCampo('txtFechaEntrega','t','fecha') == true
		&& validarCampo('txtPorcInicial','t','numPositivo') == true
		&& validarCampo('txtMontoInicial','t','numPositivo') == true
		&& validarCampo('lstGerenteVenta','t','lista') == true
		&& validarCampo('txtFechaVenta','t','fecha') == true
		&& validarCampo('lstGerenteAdministracion','t','lista') == true
		&& validarCampo('txtFechaAdministracion','t','fecha') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdEmpleado','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('lstMoneda','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtFechaReserva','t','fecha');
			validarCampo('txtFechaEntrega','t','fecha');
			validarCampo('txtPorcInicial','t','numPositivo');
			validarCampo('txtMontoInicial','t','numPositivo');
			validarCampo('lstGerenteVenta','t','lista');
			validarCampo('txtFechaVenta','t','fecha');
			validarCampo('lstGerenteAdministracion','t','lista');
			validarCampo('txtFechaAdministracion','t','fecha');
			
			error = true;
		}
		
		if (byId('lstMoneda').value != byId('hddIdMoneda').value) {
			if (!(validarCampo('txtTasaCambio','t','monto') == true
			&& validarCampo('txtFechaTasaCambio','t','fecha') == true)) {
				validarCampo('txtTasaCambio','t','monto');
				validarCampo('txtFechaTasaCambio','t','fecha');
				
				error = true;
			}
		}
		
		if ((byId('rbtTipoPagoCredito').checked == true && parseNumRafk(byId('txtPorcInicial').value) == 100)
		|| (byId('rbtTipoPagoContado').checked == true && parseNumRafk(byId('txtPorcInicial').value) != 100)) {
			alert('Porcentaje de <?php echo $spanInicial; ?> incorrecto para el tipo de venta seleccionado');
			return false;
		}
		
		// VERIFICA SI EL PEDIDO TIENE ADICIONALES AGREGADOS
		var frm = document.forms['frmDcto'];
        contAdicional = 0;
        for (i = 0; i < frm.length; i++) {
			if (frm.elements[i].id == "cbxPieAdicional") {
				contAdicional++;
			}
        }
		
		if (byId('txtIdUnidadBasica').value > 0 || contAdicional == 0) {
			if (byId('txtIdUnidadBasica').value > 0) {
				if (!(validarCampo('txtIdUnidadBasica','t','') == true
				&& validarCampo('txtIdUnidadFisica','t','') == true
				&& validarCampo('txtPrecioBase','t','monto') == true
				&& validarCampo('txtDescuento','t','numPositivo') == true)) {
					validarCampo('txtIdUnidadBasica','t','');
					validarCampo('txtIdUnidadFisica','t','');
					validarCampo('txtPrecioBase','t','monto');
					validarCampo('txtDescuento','t','numPositivo');
					
					error = true;
				}
				
				if (parseNumRafk(byId('txtPorcInicial').value) > 100) {
					alert('Cantidad de porcentaje incorrecto');
					return false;
				}
				
				if (parseNumRafk(byId('txtSaldoFinanciar').value) > 0 && !(byId('hddSinBancoFinanciar').value == 1)) {
					if (!(validarCampo('lstBancoFinanciar', 't', 'lista') == true)) {
						validarCampo('lstBancoFinanciar', 't', 'lista');
						
						error = true;
					}
				}
			} else {
				alert('Debe agregar al menos una unidad física o un adicional para crear el pedido');
				return false;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar el pedido?') == true) {
				xajax_guardarDcto(xajax.getFormValues('frmDcto'), 'true');
			}
		}
	}
	
    function validarFrmGenerar(){
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtBuscarPresupuesto','t','') == true) {
			xajax_cargarDcto('', '', '', '', byId('txtIdEmpresa').value, byId('txtBuscarPresupuesto').value, '');
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtBuscarPresupuesto','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
    }
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasenaPermiso','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasenaPermiso','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
	<script type="text/javascript" language="javascript" src="../js/wz_tooltip/wz_tooltip.js"></script>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Pedido de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
		</tr>
        <tr>
        	<td>
            <form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
	            <input type="hidden" id="hddTipoPedido" name="hddTipoPedido"/>
            
            	<table border="0" width="100%">
                <tr>
                	<td colspan="2">
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="58%">
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
                            <td width="12%"></td>
                            <td width="18%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empleado:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpleado" name="txtIdEmpleado" onblur="xajax_asignarEmpleado(this.value, 'false');" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpleado" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Empleado');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        </table>
					</td>
				</tr>
                <tr id="trBuscarPresupuesto">
                    <td colspan="2">
                        <table align="center">
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Nro. Presupuesto:</td>
                            <td><input type="text" id="txtBuscarPresupuesto" name="txtBuscarPresupuesto" maxlength="50" onkeyup="if (event.keyCode == 13) { validarFrmGenerar(); }" style="text-align:center"/></td>
                        </tr>
                        <tr>
                            <td colspan="2"><button type="button" onclick="validarFrmGenerar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/plus.png"/></td><td>&nbsp;</td><td>Generar Pedido</td></tr></table></button></td>
                        </tr>
                        </table>
					</td>
				</tr>
                <tr id="trFieldsetCliente">
                	<td colspan="2">
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
                                        <tr>
                                        <tr>
                                            <td>Disponible:</td>
                                            <td><input type="text" id="txtCreditoCliente" name="txtCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        <tr>
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
                                        <select id="lstTipoClave" name="lstTipoClave">
                                            <option value="-1">[ Seleccione ]</option>
                                            <option value="1">1.- COMPRA</option>
                                            <option value="2">2.- ENTRADA</option>
                                            <option value="3">3.- VENTA</option>
                                            <option value="4">4.- SALIDA</option>
                                        </select>
                                    </td>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Clave:</td>
                                    <td width="28%">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td id="tdlstClaveMovimiento"></td>
                                            <td>&nbsp;</td>
                                            <td>
                                            <a class="modalImg" id="aDesbloquearClaveMovimiento" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'iv_pedido_venta_clave_mov');">
                                            	<img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                            </a>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                    <td align="right" class="tituloCampo" width="12%">Tipo de Pago:</td>
                                    <td width="20%">
                                        <label><input type="radio" id="rbtTipoPagoCredito" name="rbtTipoPago" value="0"/> Crédito</label>
                                        <label><input type="radio" id="rbtTipoPagoContado" name="rbtTipoPago" value="1" checked="checked"/> Contado</label>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td valign="top" width="30%">
                            <fieldset><legend class="legend">Datos del Pedido</legend>
                				<input type="hidden" id="txtIdFactura" name="txtIdFactura"/>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%">Nro. Pedido:</td>
                                    <td width="60%">
                                        <input type="text" id="txtNumeroPedidoPropio" name="txtNumeroPedidoPropio" readonly="readonly" size="20" style="text-align:center"/>
                                        <input type="hidden" id="txtIdPedido" name="txtIdPedido" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                	<td align="right" class="tituloCampo">Fecha:</td>
                                	<td><input type="text" id="txtFechaPedido" name="txtFechaPedido" readonly="readonly" style="text-align:center" size="10"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                                    <td>
                                    	<table cellpadding="0" cellspacing="0">
                                        <tr align="left">
                                            <td id="tdlstMoneda">
                                                <select id="lstMoneda" name="lstMoneda">
                                                    <option value="-1">[ Seleccione ]</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td id="tdlstTasaCambio"></td>
                                        </tr>
                                        <tr id="trTasaCambio" align="left">
                                            <td>
                                            	<table width="100%">
                                                <tr>
                                                	<td>Tasa de Cambio:</td>
                                                	<td><input type="text" id="txtTasaCambio" name="txtTasaCambio" readonly="readonly" size="16" style="text-align:right"/></td>
                                                </tr>
                                                <tr>
                                                	<td>de Fecha:</td>
                                                	<td><input type="text" id="txtFechaTasaCambio" name="txtFechaTasaCambio" autocomplete="off" size="10" style="text-align:center"/></td>
                                                </tr>
                                                </table>
                                                <input type="hidden" id="hddIdMoneda" name="hddIdMoneda"/>
                                                <input type="hidden" id="hddIncluirImpuestos" name="hddIncluirImpuestos"/>
            									<input type="hidden" id="hddModoCompra" name="hddModoCompra"/>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Nro. Presupuesto:</td>
                                    <td>
                                        <input type="text" id="txtNumeroPresupuestoVenta" name="txtNumeroPresupuestoVenta" readonly="readonly" size="20" style="text-align:center"/>
                                        <input type="hidden" id="hddIdPresupuestoVenta" name="hddIdPresupuestoVenta"/>
                                        <input type="hidden" id="hddPresupuestoImportado" name="hddPresupuestoImportado"/>
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
                                	<td id="tdMsjPedido" colspan="2"></td>
                                </tr>
                                </table>
                            </fieldset>
                            </td>
						</tr>
                        </table>
                    </td>
				</tr>
                <tr id="trFieldsetUnidadFisica">
                	<td colspan="2">
                    <fieldset><legend class="legend">Unidad Física</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Unidad Básica:</td>
                        	<td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdUnidadBasica" name="txtIdUnidadBasica" onblur="xajax_asignarUnidadBasica(this.value, 'false');" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarUnidadBasica" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'UnidadBasica');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreUnidadBasica" name="txtNombreUnidadBasica" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
							</td>
                            <td align="right" class="tituloCampo">Unidad Física:</td>
                        	<td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdUnidadFisica" name="txtIdUnidadFisica" onblur="xajax_asignarUnidadFisica(this.value, 'false');" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarUnidadFisica" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'UnidadFisica');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td style="display:none"><input type="text" id="txtNombreUnidadFisica" name="txtNombreUnidadFisica" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                                <input type="hidden" id="hddIdUnidadFisicaAnterior" name="hddIdUnidadFisicaAnterior"/>
							</td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%">Marca:</td>
                        	<td width="22%"><input type="text" id="txtMarca" name="txtMarca" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo" width="12%">Modelo:</td>
                        	<td width="20%"><input type="text" id="txtModelo" name="txtModelo" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo" width="12%">Versión:</td>
                        	<td width="22%"><input type="text" id="txtVersion" name="txtVersion" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Año:</td>
                        	<td><input type="text" id="txtAno" name="txtAno" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo">Color:</td>
                        	<td><input type="text" id="txtColorExterno1" name="txtColorExterno1" readonly="readonly"/></td>
                            <td></td>
                        	<td></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanSerialCarroceria; ?>:</td>
                        	<td><input type="text" id="txtSerialCarroceria" name="txtSerialCarroceria" maxlength="<?php echo substr($arrayValidarCarroceria[0], -6,2); ?>" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo"><?php echo $spanSerialMotor; ?>:</td>
                        	<td><input type="text" id="txtSerialMotor" name="txtSerialMotor" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo"><?php echo $spanKilometraje; ?>:</td>
                        	<td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtKilometraje" name="txtKilometraje" readonly="readonly"/></td>
                                    <td>&nbsp;</td>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearKilometraje" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'an_pedido_venta_form_unidad_fisica');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                        	<td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtPlaca" name="txtPlaca" readonly="readonly"/></td>
                                    <td>&nbsp;</td>
                                    <td>
                                    <a class="modalImg" id="aDesbloquearPlaca" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'an_pedido_venta_form_unidad_fisica');">
                                        <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                    </a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                            <td></td>
                        	<td></td>
                            <td align="right" class="tituloCampo">Condición:</td>
                        	<td><input type="text" id="txtCondicion" name="txtCondicion" readonly="readonly"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha Reserva Venta:</td>
                            <td><input type="text" id="txtFechaReserva" name="txtFechaReserva" autocomplete="off" size="10" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha de Entrega:</td>
                            <td id="fet"><input type="text" id="txtFechaEntrega" name="txtFechaEntrega" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
				</tr>
                <tr id="trFieldsetVehiculoUsado">
                	<td valign="top" width="50%">
                    <fieldset id="fieldsetVehiculoUsado"><legend class="legend">Vehículo Usado Tomado a Cambio</legend>
                    	<table border="0" width="100%">
                        <tr id="trAgregarTradeIn" align="left">
                        	<td colspan="2">
                            <a class="modalImg" id="aAgregarTradeIn" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaTradeIn');">
                                <button type="button" title="Agregar Trade-In"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                                <button type="button" id="btnQuitarTradeIn" name="btnQuitarTradeIn" onclick="xajax_eliminarTradeIn(xajax.getFormValues('frmTotalDcto'));" title="Quitar Trade-In"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr id="trItemTradeIn" align="left" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmTradeIn" onclick="selecAllChecks(this.checked,this.id,'frmTotalDcto');"/></td>
                            <td width="100%"></td>
                        </tr>
                        <tr id="trItmPieTradeIn"></tr>
                        </table>
                    </fieldset>
                    
                    <fieldset id="fieldsetFormaPago"><legend class="legend">Forma de Pago</legend>
                    	<table border="0" width="100%">
                        <tr id="trAgregarFormaPago" align="left">
                        	<td colspan="4">
                            <a class="modalImg" id="aAgregarFormaPago" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaFormaPago');">
                                <button type="button" title="Agregar Forma de Pago"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                                <button type="button" id="btnQuitarFormaPago" name="btnQuitarFormaPago" onclick="xajax_eliminarFormaPago(xajax.getFormValues('frmTotalDcto'));" title="Quitar Forma de Pago"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr align="right">
                        	<td class="tituloCampo" width="30%"><?php echo $spanAnticipo; ?> por <br>Adicionales y Opcionales:</td>
                            <td width="30%"></td>
                            <td id="tdMontoAnticipoMoneda" width="6%"></td>
                            <td width="34%"><input type="text" id="txtMontoAnticipo" name="txtMontoAnticipo" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" nowrap="nowrap"><?php echo $spanInicial; ?> por la Unidad:
                                <input type="hidden" id="hddTipoInicial" name="hddTipoInicial">
                            </td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr align="right">
                                    <td valign="top">
                                        <input type="radio" id="rbtInicialPorc" name="rbtInicial" onclick="
                                        byId('hddTipoInicial').value = 0;
                                        byId('txtPorcInicial').readOnly = false;
                                        byId('txtMontoInicial').readOnly = true;
                                        byId('txtPorcInicial').className = 'inputHabilitado';
                                        byId('txtMontoInicial').className = 'inputCompleto';" value="1"/>
                                    </td>
                                    <td><input type="text" id="txtPorcInicial" name="txtPorcInicial" onblur="setFormatoRafk(this, 2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));" onkeypress="return validarSoloNumerosReales(event);" maxlength="5" size="6" style="text-align:right"/> %</td>
                                </tr>
                                </table>
                            </td>
                            <td id="tdMontoInicialMoneda"></td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr align="right">
                                    <td valign="top">
                                        <input type="radio" id="rbtInicialMonto" name="rbtInicial" onclick="
                                        byId('hddTipoInicial').value = 1;
                                        byId('txtPorcInicial').readOnly = true;
                                        byId('txtMontoInicial').readOnly = false;
                                        byId('txtPorcInicial').className = 'inputInicial';
                                        byId('txtMontoInicial').className = 'inputCompletoHabilitado';" value="2"/>
                                    </td>
                                    <td width="100%"><input type="text" id="txtMontoInicial" name="txtMontoInicial" class="inputCompleto" onblur="setFormatoRafk(this, 2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right;"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr id="trMontoCashBack" align="right">
                            <td class="tituloCampo">Cash Back:</td>
                            <td></td>
                            <td id="tdMontoCashBack"></td>
                            <td><input type="text" id="txtMontoCashBack" name="txtMontoCashBack" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right;"/></td>
                        </tr>
                        <tr id="trPagoContado" align="right">
                            <td class="tituloCampo">Pago Contado:</td>
                            <td></td>
                            <td id="tdPagoContadoMoneda"></td>
                        	<td></td>
                        </tr>
                        <tr id="trPND" align="right">
                            <td class="tituloCampo">PND:</td>
                            <td></td>
                            <td id="tdPagoPNDMoneda"></td>
                        	<td></td>
                        </tr>
                        <tr id="trTotalPND" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total PND:</td>
                            <td></td>
                            <td id="tdPagoTotalPNDMoneda"></td>
                        	<td></td>
                        </tr>
                        <tr id="trOtrosPagos" align="right">
                            <td class="tituloCampo">Otros Pagos:</td>
                            <td></td>
                            <td id="tdPagoOtrosMoneda"></td>
                        	<td></td>
                        </tr>
                        <tr id="trTotalOtrosPagos" align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total Otros Pagos:</td>
                            <td></td>
                            <td id="tdPagoTotalOtrosMoneda"></td>
                        	<td></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset><legend class="legend">Contrato a Pagarse de acuedo con</legend>
                    	<table border="0" width="100%">
                        <tr align="right" class="trResaltarTotal">
                        	<td class="tituloCampo">Total Pedido:</td>
                            <td></td>
                            <td id="tdTotalPedidoMoneda"></td>
                            <td><input type="text" id="txtTotalPedido" name="txtTotalPedido" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                        	<td class="tituloCampo">Complemento <?php echo $spanInicial; ?>:</td>
                            <td></td>
                            <td id="tdMontoComplementoInicialMoneda"></td>
                            <td><input type="text" id="txtMontoComplementoInicial" name="txtMontoComplementoInicial" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Saldo a Financiar:</td>
                            <td></td>
                            <td id="tdSaldoFinanciarMoneda"></td>
                            <td><input type="text" id="txtSaldoFinanciar" name="txtSaldoFinanciar" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr id="trBancoFinanciar" align="right">
                            <td class="tituloCampo">Entidad Bancaria:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td id="tdlstBancoFinanciar"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table id="tblSinBancoFinanciar" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><div id="lblSinBancoFinanciar" class="checkbox-label"><label><input type="checkbox" id="cbxSinBancoFinanciar" name="cbxSinBancoFinanciar" onclick="xajax_asignarSinBancoFinanciar(xajax.getFormValues('frmDcto'));" value="1"/><?php echo $spanPedidoVentaSinBanco; ?></label></div></td>
                                            <td>&nbsp;</td>
                                            <td>
                                            <a class="modalImg" id="aDesbloquearSinBancoFinanciar" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblPermiso', 'an_pedido_venta_form_entidad_bancaria');">
                                                <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                                            </a>
                                            </td>
                                        </tr>
                                        </table>
                                        <input type="hidden" id="hddSinBancoFinanciar" name="hddSinBancoFinanciar"/>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right" class="trResaltar5">
                            <td id="tdMesesFinanciar" width="26%"></td>
                            <td id="tdFechaCuotaFinanciar" width="34%"></td>
                            <td id="tdCuotasFinanciarMoneda" width="6%"></td>
                            <td id="tdCuotasFinanciar" width="34%"></td>
                        </tr>
                        <tr id="trCuotasFinanciar2" align="right" class="trResaltar4" style="display:none">
                            <td id="tdMesesFinanciar2"></td>
                            <td id="tdFechaCuotaFinanciar2"></td>
                            <td id="tdCuotasFinanciarMoneda2"></td>
                            <td id="tdCuotasFinanciar2"></td>
                        </tr>
                        <tr id="trCuotasFinanciar3" align="right" class="trResaltar5" style="display:none">
                            <td id="tdMesesFinanciar3"></td>
                            <td id="tdFechaCuotaFinanciar3"></td>
                            <td id="tdCuotasFinanciarMoneda3"></td>
                            <td id="tdCuotasFinanciar3"></td>
                        </tr>
                        <tr id="trCuotasFinanciar4" align="right" class="trResaltar4" style="display:none">
                            <td id="tdMesesFinanciar4"></td>
                            <td id="tdFechaCuotaFinanciar4"></td>
                            <td id="tdCuotasFinanciarMonedaFinal"></td>
                            <td id="tdCuotasFinanciar4"></td>
                        </tr>
                        <tr id="trMontoFLAT" align="right">
                            <td class="tituloCampo">Comisi&oacute;n FLAT:</td>
                            <td><input type="text" id="txtPorcFLAT" name="txtPorcFLAT" maxlength="5" readonly="readonly" size="6" style="text-align:right"/> %</td>
                            <td id="tdMontoFLATMoneda"></td>
                            <td><input type="text" id="txtMontoFLAT" name="txtMontoFLAT" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                        	<td class="tituloCampo">Precio Total:</td>
                            <td></td>
                            <td id="tdPrecioTotalMoneda"></td>
                            <td><input type="text" id="txtPrecioTotal" name="txtPrecioTotal" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset><legend class="legend">Seguro</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Compañía de Seguros:</td>
                            <td id="tdlstPoliza" colspan="3"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nombre de la Agencia:</td>
                            <td colspan="3"><input type="text" id="txtNombreAgenciaSeguro" name="txtNombreAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Dirección de la Agencia:</td>
                            <td colspan="3">
                            	<table width="100%">
                                <tr>
                                	<td colspan="4"><textarea id="txtDireccionAgenciaSeguro" name="txtDireccionAgenciaSeguro" class="inputHabilitado" rows="3" style="width:99%"></textarea></td>
                                </tr>
                                <tr align="right">
                                    <td class="tituloCampo" width="20%"><?php echo utf8_encode($spanCiudad); ?>:</td>
                                    <td width="30%"><input type="text" id="txtCiudadAgenciaSeguro" name="txtCiudadAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                                    <td class="tituloCampo" width="20%">País:</td>
                                    <td width="30%"><input type="text" id="txtPaisAgenciaSeguro" name="txtPaisAgenciaSeguro" class="inputCompletoHabilitado"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Teléfono de la Agencia:</td>
                            <td colspan="3">
                            <div style="float:left">
                                <input type="text" name="txtTelefonoAgenciaSeguro" id="txtTelefonoAgenciaSeguro" class="inputHabilitado" size="16" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Numero de Póliza:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtNumPoliza" name="txtNumPoliza" class="inputHabilitado"  style="text-align:center;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" width="30%">Precio del Seguro (Prima):</td>
                            <td width="34%"></td>
                            <td width="6%"></td>
                            <td width="30%"><input type="text" id="txtMontoSeguro" name="txtMontoSeguro" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Periodo:</td>
                            <td></td>
                            <td></td>
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr align="right">
                                    <td>Meses:</td>
                                    <td><input type="text" id="txtPeriodoPoliza" name="txtPeriodoPoliza" class="inputHabilitado" size="6" style="text-align:center;"/></td>
                                </tr>
                                </table>
							</td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Deducible:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtDeduciblePoliza" name="txtDeduciblePoliza" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td align="right" class="tituloCampo">Fecha Efectividad:</td>
                            <td></td>
                            <td></td>
                    		<td><input type="text" id="txtFechaEfect" name="txtFechaEfect" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="right">
                          <td align="right" class="tituloCampo">Fecha Expiracion:</td>
                          <td></td>
                          <td></td>                   		 
                   		  <td><input type="text" id="txtFechaExpi" name="txtFechaExpi" class="inputHabilitado" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo"><?php echo $spanInicial; ?>:</td>
                            <td></td>
                            <td></td>
                            <td><input type="text" id="txtInicialPoliza" name="txtInicialPoliza" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Cuotas:</td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0">
                                <tr align="right">
                                    <td>Meses:</td>
                                    <td><input type="text" id="txtMesesPoliza" name="txtMesesPoliza" class="inputHabilitado" size="6" style="text-align:center;"/></td>
                                </tr>
                                </table>
                            </td>
                            <td></td>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr align="right">
                                    <td>Monto:</td>
                                    <td width="100%"><input type="text" id="txtCuotasPoliza" name="txtCuotasPoliza" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2);" onchange="percent();" style="text-align:right;"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left" style="display:none">
                            <td align="right" class="tituloCampo">Cheque a Nombre de:</td>
                            <td id="cheque_poliza" colspan="3" height="22"></td>
                        </tr>
                        <tr align="left" style="display:none">
                            <td align="right" class="tituloCampo">Financiada:</td>
                            <td id="financiada" colspan="3" height="22"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                	<td valign="top" width="50%">
                    <fieldset id="fieldsetVentaUnidad"><legend class="legend">Venta de la Unidad</legend>
                    	<table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="26%"><?php echo $spanPrecioUnitario; ?> Base:</td>
                            <td width="34%"></td>
                            <td width="6%" id="tdPrecioBaseMoneda"></td>
                            <td width="34%"><input type="text" id="txtPrecioBase" name="txtPrecioBase" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Descuento:</td>
                            <td></td>
                            <td id="tdDescuentoMoneda"></td>
                            <td><input type="text" id="txtDescuento" name="txtDescuento" class="inputCompletoHabilitado" onblur="setFormatoRafk(this, 2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">
                                Precio Venta:
                                <br><span id="spanPorcIva" class="textoNegrita_10px"></span>
                            </td>
                            <td>
                                <input type="hidden" id="txtPorcIva" name="txtPorcIva"/>
                                <input type="hidden" id="txtPorcIvaLujo" name="txtPorcIvaLujo"/>
                            </td>
                            <td id="tdPrecioVentaMoneda"></td>
                            <td><input type="text" id="txtPrecioVenta" name="txtPrecioVenta" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Opcionales:</td>
                            <td></td>
                            <td id="tdTotalArticulo"></td>
                            <td><input type="text" id="txtTotalOpcionales" name="txtTotalOpcionales" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Precio:</td>
                            <td></td>
                            <td id="tdTotalArticulo"></td>
                            <td><input type="text" id="txtPrecioVentaOpcional" name="txtPrecioVentaOpcional" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" id="trItmPieAdicionalPreterminado">
                            <td class="tituloCampo">Adicionales:</td>
                            <td></td>
                            <td id="tdTotalAdicional"></td>
                            <td><input type="text" id="txtTotalAdicionales" name="txtTotalAdicionales" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" nowrap="nowrap"><?php echo $spanInicial; ?>:</td>
                            <td></td>
                            <td id="tdMontoAnticipoUnidadMoneda"></td>
                            <td><input type="text" id="txtMontoAnticipoUnidad" name="txtMontoAnticipoUnidad" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" nowrap="nowrap">Cash Back:</td>
                            <td></td>
                            <td id="tdMontooCashBackUnidadMoneda"></td>
                            <td><input type="text" id="txtMontoCashBackUnidad" name="txtMontoCashBackUnidad" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset id="fieldsetArticulo"><legend class="legend">Venta de Opcionales</legend>
                    	<table border="0" width="100%">
                        <tr id="trAgregarArticulo" align="left">
                        	<td colspan="4">
                            <a class="modalImg" id="aAgregarArticulo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblArticulo');">
                                <button type="button" title="Agregar Articulo"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                                <button type="button" id="btnQuitarArticulo" name="btnQuitarArticulo" onclick="xajax_eliminarArticuloLote(xajax.getFormValues('frmDcto'));" title="Quitar Articulo"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr id="trItemArticulo" align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmArticulo" onclick="selecAllChecks(this.checked,this.id,'frmDcto');"/></td>
                            <td width="4%">Nro.</td>
                            <td width="14%">Código</td>
                            <td width="40%">Descripción</td>
                            <td width="6%">Cantidad</td>
                            <td style="display:none" width="6%">Pendiente</td>
                            <td style="display:none" width="8%">Gastos</td>
                            <td width="8%"><?php echo $spanPrecioUnitario; ?></td>
                            <td width="4%">% Impuesto</td>
                            <td style="display:none" width="5%">Total</td>
                            <td width="5%">Total C/Impto.</td>
                        </tr>
                		<tr id="trItmPieArticulo" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="7">Total Opcionales:</td>
                            <td><input type="text" id="txtTotalOpcional" name="txtTotalOpcional" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset id="fieldsetAdicional"><legend class="legend">Venta de Adicionales</legend>
                    	<table border="0" width="100%">
                        <tr id="trAgregarAdicional" align="left">
                        	<td colspan="5">
                            <a class="modalImg" id="aAgregarAdicional" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaAdicional');">
                                <button type="button" title="Agregar Adicional"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                                <button type="button" id="btnQuitarAdicional" name="btnQuitarAdicional" onclick="xajax_eliminarAdicionalLote(xajax.getFormValues('frmDcto'));" title="Quitar Adicional"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr id="trItemAdicional" align="center" class="tituloColumna">
                            <td><input type="checkbox" id="cbxItmAdicional" onclick="selecAllChecks(this.checked,this.id,'frmDcto');"/></td>
                            <td>Nro.</td>
                            <td colspan="2"></td>
                            <td>Total C/Impto.</td>
                        </tr>
                        <tr id="trItmPieAdicional" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="4">Total Adicionales:</td>
                            <td>
	                            <input type="text" id="txtTotalAdicionalNormal" name="txtTotalAdicionalNormal" class="inputSinFondo" readonly="readonly" style="text-align:right"/>
                                <input type="hidden" id="txtTotalAdicionalPredeterminado" name="txtTotalAdicionalPredeterminado" class="inputSinFondo" readonly="readonly" style="text-align:right"/>
                            	<input type="hidden" id="txtTotalAdicional" name="txtTotalAdicional" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                        	<td class="tituloCampo" colspan="4">Total Otros Adicionales:</td>
                            <td><input type="text" id="txtTotalAdicionalContrato" name="txtTotalAdicionalContrato" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        <tr>
                        	<td></td>
                            <td width="4%"></td>
                        	<td width="38%"></td>
                        	<td width="38%"></td>
                            <td width="20%"></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset id="fielsetPresupuestoAccesorios"><legend class="legend">Presupuesto Accesorios</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nro. Presupuesto Acc.:</td>
                            <td></td>
                            <td></td>
                            <td>
                            	<table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <input type="text" id="txtNumeroPresupuestoAcc" name="txtNumeroPresupuestoAcc" readonly="readonly" size="20" style="text-align:center"/>
                                		<input type="hidden" id="hddIdPresupuestoAcc" name="hddIdPresupuestoAcc"/>
                                    </td>
                                    <td></td>
                                    <td>
                                    <a id="aEditarPresupuestoAcc" target="_self"><img src="../img/iconos/pencil.png" title="Editar Presupuesto Accesorios"/></a>
                                    <a id="aPresupuestoAccPDF" target="_self"><img src="../img/iconos/page_white_acrobat.png" title="Presupuesto Accesorios PDF"/></a>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" width="26%">Subtotal:</td>
                            <td width="34%"></td>
                            <td id="tdSubTotalPresupuestoAccesorioMoneda" width="6%"></td>
                            <td width="34%"><input type="text" id="txtSubTotalPresupuestoAccesorio" name="txtSubTotalPresupuestoAccesorio" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Impuesto:</td>
                            <td></td>
                            <td id="tdTotalImpuestoAccesorioMoneda"></td>
                            <td><input type="text" id="txtTotalImpuestoAccesorio" name="txtTotalImpuestoAccesorio" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Total:</td>
                            <td></td>
                            <td id="tdTotalPresupuestoAccesorioMoneda"></td>
                            <td><input type="text" id="txtTotalPresupuestoAccesorio" name="txtTotalPresupuestoAccesorio" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset id="fieldsetOtros"><legend class="legend">Otros</legend>
                    	<table border="0" width="100%">
                        <tr align="right" class="trResaltarTotal">
                        	<td class="tituloCampo" width="26%">Total <?php echo $spanInicial; ?>, Adicionales:</td>
                            <td width="34%"></td>
                            <td id="tdTotalInicialGastosMoneda" width="6%"></td>
                            <td width="34%"><input type="text" id="txtTotalInicialAdicionales" name="txtTotalInicialAdicionales" class="inputSinFondo" readonly="readonly" style="text-align:right;"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    <fieldset><legend class="legend">Observaciones</legend>
                    	<textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea>
                    </fieldset>
                    
                    <fieldset><legend class="legend">Comprobación / Validación del Pedido</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="24%"><span class="textoRojoNegrita">*</span>Gerente Ventas:</td>
                            <td id="tdlstGerenteVenta" width="26%"></td>
                            <td align="right" class="tituloCampo" width="24%"><span class="textoRojoNegrita">*</span>Fecha:</td>
                            <td width="26%"><input type="text" id="txtFechaVenta" name="txtFechaVenta" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Gerente Administración:</td>
                            <td id="tdlstGerenteAdministracion"></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha:</td>
                            <td valign="middle"><input type="text" id="txtFechaAdministracion" name="txtFechaAdministracion" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
				</tr>
                <tr id="trBtnGuardar">
                    <td align="right" colspan="2"><hr>
                        <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                        <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.location.href='an_pedido_venta_list.php';"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
                    </td>
                </tr>
                </table>
            </form>
			</td>
		</tr>
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante11" src="../img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante12" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
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
            <button type="button" id="btnCancelarListaEmpresa" name="btnCancelarListaEmpresa">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <div id="tblLista" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
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
                <table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblCliente');">
                        <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
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
        <tr id="trBuscarUnidadBasica">
            <td>
            <form id="frmBuscarUnidadBasica" name="frmBuscarUnidadBasica" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddObjDestinoUnidadBasica" name="hddObjDestinoUnidadBasica"/>
                <input type="hidden" id="hddNomVentanaUnidadBasica" name="hddNomVentanaUnidadBasica"/>
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Marca:</td>
                    <td id="tdlstMarcaBuscarUnidadBasica"></td>
                    <td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModeloBuscarUnidadBasica"></td>
                    <td align="right" class="tituloCampo" width="120">Versión:</td>
                    <td id="tdlstVersionBuscarUnidadBasica"></td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td align="right" class="tituloCampo">Año:</td>
                    <td id="tdlstAnoBuscarUnidadBasica"></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarUnidadBasica" name="txtCriterioBuscarUnidadBasica" onkeyup="byId('btnBuscarUnidadBasica').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarUnidadBasica" name="btnBuscarUnidadBasica" onclick="xajax_buscarUnidadBasica(xajax.getFormValues('frmBuscarUnidadBasica'), xajax.getFormValues('frmDcto'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarUnidadBasica'].reset(); byId('btnBuscarUnidadBasica').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr id="trBuscarUnidadFisica">
            <td>
            <form id="frmBuscarUnidadFisica" name="frmBuscarUnidadFisica" onsubmit="return false;" style="margin:0">
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
                        <button type="button" id="btnCancelarLista" name="btnCancelarLista">Cerrar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        </table>
	</div>
    
    <div id="tblListaAdicional" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBuscarAdicional" name="frmBuscarAdicional" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarAdicional" name="txtCriterioBuscarAdicional" onkeyup="byId('btnBuscarAdicional').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarAdicional" name="btnBuscarAdicional" onclick="xajax_buscarAdicional(xajax.getFormValues('frmBuscarAdicional'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarAdicional'].reset(); byId('btnBuscarAdicional').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaAdicional" name="frmListaAdicional" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddNumeroItmAdicional" name="hddNumeroItmAdicional">
                <table width="100%">
                <tr>
                    <td>
                        <div class="wrap">
                            <!-- the tabs -->
                            <ul class="tabs">
                                <li><a href="#">Adicionales</a></li>
                                <li><a href="#">Paquetes</a></li>
                            </ul>
                            
                            <!-- tab "panes" -->
                            <div id="divListaAdicional" class="pane">
                            </div>
                            
                            <div id="divListaPaquete" class="pane">
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td align="right"><hr>
                        <button type="button" id="btnCancelarListaAdicional" name="btnCancelarListaAdicional">Cerrar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
		</tr>
        </table>
    </div>
    
    <table border="0" id="tblArticulo" style="display:none" width="960">
    <tr>
    	<td>
        <form id="frmBuscarArticulo" name="frmBuscarArticulo" onsubmit="return false;" style="margin:0">
        	<table align="right" border="0">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="120">Buscar por:</td>
                <td>
                	<select id="lstBuscarArticulo" name="lstBuscarArticulo" class="inputHabilitado" style="width:150px">
                    	<option value="1">Marca</option>
                        <option value="2">Tipo Artículo</option>
                        <option value="3">Sección</option>
                        <option value="4">Sub-Sección</option>
                        <option selected="selected" value="5">Descripción</option>
                        <option value="6">Cód. Barra</option>
                        <option value="7">Cód. Artículo Prov.</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarArticulo" name="txtCriterioBuscarArticulo" class="inputHabilitado"/></td>
                <td align="right" class="tituloCampo" width="120">Código:</td>
                <td id="tdCodigoArt"></td>
                <td align="right" nowrap="nowrap">
                	<button type="submit" id="btnBuscarArticulo" name="btnBuscarArticulo" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBuscarArticulo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarArticulo'].reset(); byId('btnBuscarArticulo').click();">Limpiar</button>
				</td>
			</tr>
			</table>
		</form>
		</td>
    </tr>
    <tr>
    	<td><div id="divListaArticulo" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td>
        <form id="frmDatosArticulo" name="frmDatosArticulo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddIdArticulo" name="hddIdArticulo"/>
            <input type="hidden" id="hddNumeroArt" name="hddNumeroArt"/>
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
            <tr>
            	<td class="divMsjAlerta" colspan="5" id="tdMsjArticulo" style="display:none"></td>
            </tr>
            </table>
        </fieldset>
            
            <table border="0" width="100%">
            <tr>
            	<td width="10%"></td>
                <td width="28%"></td>
                <td width="10%"></td>
                <td width="22%"></td>
                <td width="30%"></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cantidad:</td>
                <td>
					<table cellpadding="0" cellspacing="0">
					<tr>
                		<td><input type="text" id="txtCantidadArt" name="txtCantidadArt" class="inputHabilitado" maxlength="6" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" size="10" style="text-align:right;"/></td>
                        <td>&nbsp;</td>
                    	<td><input type="text" id="txtUnidadArt" name="txtUnidadArt" readonly="readonly" size="15"/></td>
					</tr>
                    </table>
				</td>
                <td id="tdUbicacion" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ubicación:</td>
                <td id="tdlstUbicacion">
                    <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td id="tdlstCasillaArt" width="100%"></td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtCantidadUbicacion" name="txtCantidadUbicacion" readonly="readonly" size="10" style="text-align:right"/></td>
                    </tr>
                    </table>
                </td>
                <td rowspan="2" width="34%">
                	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                    <tr>
                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                        <td align="center">
                            <table>
                            <tr>
                                <td><img src="../img/iconos/tick.png"/></td><td>Suficiente Disp.</td>
                                <td><img src="../img/iconos/error.png"/></td><td>Poca Disp.</td>
							</tr>
                            <tr>
                                <td><img src="../img/iconos/cancel.png"/></td><td>Sin Disp.</td>
                            </tr>
                            </table>
                        </td>
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
                            <input type="hidden" id="txtPrecioSugerido" name="txtPrecioSugerido" readonly="readonly" size="10" style="text-align:right"/>
                        </td>
                        <td align="center" id="tdDesbloquearPrecio"></td>
                    </tr>
                    </table>
                    <input type="hidden" id="hddIdArtPrecio" name="hddIdArtPrecio"/>
                    <input type="hidden" id="hddIdPrecioArtPredet" name="hddIdPrecioArtPredet"/>
                    <input type="hidden" id="hddPrecioArtPredet" name="hddPrecioArtPredet"/>
                    <input type="hidden" id="hddBajarPrecio" name="hddBajarPrecio"/>
                    <a class="modalImg" id="aDesbloquearPrecioArt" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso2', 'iv_catalogo_venta_precio_venta');"></a>
                </td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Impuesto:</td>
                <td>
                    <input type="hidden" id="hddIdIvaArt" name="hddIdIvaArt"/>
                    <input type="text" id="txtIvaArt" name="txtIvaArt" readonly="readonly" size="10" style="text-align:right"/>
                </td>
            </tr>
            <tr align="left">
                <td align="right" colspan="5"><hr>
                    <button type="submit" id="btnGuardarArticulo" name="btnGuardarArticulo" onclick="validarFrmDatosArticulo();">Aceptar</button>
                    <button type="button" id="btnCancelarArticulo" name="btnCancelarArticulo">Cerrar</button>
                </td>
            </tr>
            </table>
        </form>
		</td>
	</tr>
    </table>
    
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
                    <input type="password" id="txtContrasenaPermiso" name="txtContrasenaPermiso" size="30"/>
                    <input type="hidden" id="hddModuloPermiso" name="hddModuloPermiso" readonly="readonly" size="30"/>
                </td>
            </tr>
        	</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Aceptar</button>
			<button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso">Cancelar</button>
			</td>
		</tr>
	</table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante21" src="../img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante22" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmCliente" name="frmCliente" onsubmit="return false;" style="margin:0">
	<div id="tblCliente" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table border="0" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Pago:</td>
                    <td id="tdlstCredito">
                        <select id="lstCredito" name="lstCredito">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Contribuyente:</td>
                    <td>
                    	<select id="lstContribuyente" name="lstContribuyente" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="No">No</option>
                            <option value="Si">Si</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
                    <td>
                        <select id="lstEstatus" name="lstEstatus" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo:</td>
                    <td width="22%">
                        <select name="lstTipo" id="lstTipo" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="1">Natural</option>
                            <option value="2">Juridico</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span><?php echo $spanClienteCxC; ?>:</td>
                    <td nowrap="nowrap" width="22%">
                        <table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtCedula" name="txtCedula" maxlength="18" size="20" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI; ?>"/>
                            </div>
                            </td>
                            <td>&nbsp;</td>
                            <td>
                            <a class="modalImg" id="aDesbloquearCedula" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'cc_cliente_list_cedula');">
                                <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                            </a>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span><?php echo $spanNIT; ?>:</td>
                    <td width="20%">
                    <div style="float:left">
                        <input type="text" id="txtNit" name="txtNit" maxlength="18" size="20" style="text-align:center"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoNIT; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre(s):</td>
                    <td>
                    	<table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" name="txtNombre" id="txtNombre" maxlength="50" size="26"/></td>
                            <td>&nbsp;</td>
                            <td>
                            <a class="modalImg" id="aDesbloquearNombre" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'cc_cliente_list_nombre');">
                                <img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
                            </a>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo">Apellido(s):</td>
                    <td><input type="text" name="txtApellido" id="txtApellido" maxlength="50" size="26"/></td>
                    <td align="right" class="tituloCampo">Licencia:</td>
                    <td><input type="text" id="txtLicencia" name="txtLicencia" maxlength="18" size="20" style="text-align:center"/></td>
                </tr>
                <tr>
                	<td colspan="6">
                    <fieldset><legend class="legend">Dirección</legend>
                        <div class="wrap">
                            <!-- the tabs -->
                            <ul class="tabs">
                                <li><a href="#">Residencial</a></li>
                                <li><a href="#">Postal</a></li>
                            </ul>
                            
                            <!-- tab "panes" -->
                            <div class="pane">
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanUrbanizacion); ?>:</td>
                                    <td width="21%"><input type="text" id="txtUrbanizacion" name="txtUrbanizacion" style="width:99%"/></td>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanCalleAv); ?>:</td>
                                    <td width="22%"><input type="text" id="txtCalle" name="txtCalle" style="width:99%"/></td>
                                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanCasaEdif); ?>:</td>
                                    <td width="21%"><input type="text" id="txtCasa" name="txtCasa" style="width:99%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanMunicipio); ?>:</td>
                                    <td><input type="text" id="txtMunicipio" name="txtMunicipio" style="width:99%"/></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanCiudad); ?>:</td>
                                    <td><input type="text" name="txtCiudad" id="txtCiudad" style="width:99%"/></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo utf8_encode($spanEstado); ?>:</td>
                                    <td><input type="text" name="txtEstado" id="txtEstado" style="width:99%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtTelefono" id="txtTelefono" size="18" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                    <td align="right" class="tituloCampo">Otro Telf.:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtOtroTelefono" id="txtOtroTelefono" size="18" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                    <td align="right" class="tituloCampo"><?php echo $spanEmail; ?>:</td>
                                    <td>
                                        <div style="float:left">
                                            <input type="text" name="txtCorreo" id="txtCorreo" maxlength="50" style="width:99%"/>
                                        </div>
                                        <div style="float:left">
                                            <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                                        </div>
                                    </td>
                                </tr>
                                </table>
                            </div>
                            
                            <div class="pane">
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanUrbanizacion); ?>:</td>
                                    <td width="21%"><input type="text" name="txtUrbanizacionPostalCliente" id="txtUrbanizacionPostalCliente" style="width:99%"/></td>
                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCalleAv); ?>:</td>
                                    <td width="22%"><input type="text" name="txtCallePostalCliente" id="txtCallePostalCliente" style="width:99%"/></td>
                                    <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCasaEdif); ?>:</td>
                                    <td width="21%"><input type="text" name="txtCasaPostalCliente" id="txtCasaPostalCliente" style="width:99%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanMunicipio); ?>:</td>
                                    <td><input type="text" name="txtMunicipioPostalCliente" id="txtMunicipioPostalCliente" style="width:99%"/></td>
                                    <td align="right" class="tituloCampo"><?php echo utf8_encode($spanCiudad); ?>:</td>
                                    <td><input type="text" name="txtCiudadPostalCliente" id="txtCiudadPostalCliente" style="width:99%"/></td>
                                    <td align="right" class="tituloCampo"><?php echo $spanEstado; ?>:</td>
                                    <td><input type="text" name="txtEstadoPostalCliente" id="txtEstadoPostalCliente" style="width:99%"/></td>
                                </tr>
                                </table>
							</div>
                        </div>
					</fieldset>
                    </td>
                </tr>
                </table>
                
                <table border="0" width="100%">
                <tr>
                    <td valign="top" width="40%">
                    <fieldset><legend class="legend">Datos del Contacto</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%"><?php echo $spanClienteCxC; ?>:</td>
                            <td width="60%">
                            <div style="float:left">
                                <input type="text" id="txtCedulaContacto" name="txtCedulaContacto" maxlength="18" size="20" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Nombre(s) y Apellido(s):</td>
                            <td><input type="text" id="txtNombreContacto" name="txtNombreContacto" size="26" maxlength="50"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Teléfono:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtTelefonoContacto" name="txtTelefonoContacto" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanEmail; ?>:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtCorreoContacto" name="txtCorreoContacto"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                            </div>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="60%">
                    <fieldset><legend class="legend">Otros Datos</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha de Creación:</td>
                            <td><input type="text" id="txtFechaCreacion" name="txtFechaCreacion" readonly="readonly" size="10" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo">Fecha de Desincorporación:</td>
                            <td><input type="text" id="txtFechaDesincorporar" name="txtFechaDesincorporar" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">                        
                            <td align="right" class="tituloCampo" width="25%">Paga Impuesto:</td>
                            <td width="25%"><input type="checkbox" id="cbxPagaImpuesto" name="cbxPagaImpuesto" checked="checked"/></td>
                            <td align="right" class="tituloCampo" width="25%">Bloquea Venta:</td>
                            <td width="25%">
                            <div style="float:left">
								<input type="checkbox" id="cbxBloquearVenta" name="cbxBloquearVenta" checked="checked"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Bloquea al cliente para que haga pedidos desde el sistema de solicitudes si tiene facturas vencidas"/>
                            </div>
                            </td>
                		</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>% Descuento:</td>
                            <td>
                                <select name="lstDescuento" id="lstDescuento" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="0">0</option>
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                </select>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Cliente:</td>
                            <td>
                                <select name="lstTipoCliente" id="lstTipoCliente" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="Todos">Todos</option>
                                    <option value="Repuestos">Repuestos</option>
                                    <option value="Servicios">Servicios</option>
                                    <option value="Vehiculos">Vehiculos</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Reputacion:</td>
                            <td>
                                <select name="lstReputacionCliente" id="lstReputacionCliente" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="CLIENTE C">Cliente C</option>
                                    <option value="CLIENTE B">Cliente B</option>
                                    <option value="CLIENTE A">Cliente A</option>
                                </select>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Clave Mov.<br>Venta Mostrador:</td>
                            <td id="tdlstClaveMovimiento" colspan="3"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
			
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Empresas</a></li>
                        <li><a href="#">Exenciones</a></li>
                    </ul>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                        <table width="100%">
                        <tr align="left">
                            <td>
                                <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                <button type="button" id="btnEliminar" name="btnEliminar" onclick="xajax_eliminarClienteEmpresa(xajax.getFormValues('frmCliente'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" class="texto_9px" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,2);"/></td>
                                    <td width="40%">Empresa</td>
                                    <td width="12%">Días Crédito</td>
                                    <td width="12%">Forma Pago</td>
                                    <td width="12%">Limite Crédito</td>
                                    <td width="12%">Crédito Reservado</td>
                                    <td width="12%">Crédito Disponible</td>
                                    <td></td>
                                </tr>
                                <tr id="trItmPie"></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </div>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                        <table width="100%">
                        <tr align="left">
                            <td>
                                <a class="modalImg" id="aNuevoImpuesto" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaImpuesto');">
                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                <button type="button" id="btnEliminarImpuesto" name="btnEliminarImpuesto" onclick="xajax_eliminarClienteImpuesto(xajax.getFormValues('frmCliente'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table border="0" class="texto_9px" width="100%">
                                <tr align="center" class="tituloColumna">
                                    <td><input type="checkbox" id="cbxItmImpuesto" onclick="selecAllChecks(this.checked,this.id,'frmCliente');"/></td>
                                    <td width="25%%">Tipo Impuesto</td>
                                    <td width="55%">Observación</td>
                                    <td width="20%">% Impuesto</td>
                                </tr>
                                <tr id="trItmPieImpuesto"></tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </div>
				</div>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
				<input type="hidden" name="hddIdCliente" id="hddIdCliente" readonly="readonly"/>
                <button type="submit" id="btnGuardarCliente" name="btnGuardarCliente" onclick="validarFrmCliente();">Guardar</button>
                <button type="button" id="btnCancelarCliente" name="btnCancelarCliente">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<script>
byId('hddTipoPedido').value = "<?php echo $_GET['vw']; ?>";

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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargarDcto('<?php echo $_GET['id']; ?>', '<?php echo $_GET['idPresupuesto']; ?>', '<?php echo $_GET['idPedidoFinanciamiento']; ?>', '<?php echo $_GET['idFactura']; ?>', '<?php echo $_GET['txtIdEmpresa']; ?>', '<?php echo $_GET['txtBuscarPresupuesto']; ?>', '<?php echo $_GET['vw']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>