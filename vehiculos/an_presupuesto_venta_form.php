<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_presupuesto_venta_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include('modelos/md_an_prospecto_list.php');
include("controladores/ac_an_presupuesto_venta_form.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Presupuesto de Venta</title>
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
		tblProspecto = (byId('tblProspecto').style.display == '') ? '' : 'none';
		tblModelo = (byId('tblModelo').style.display == '') ? '' : 'none';
		tblPermiso2 = (byId('tblPermiso2').style.display == '') ? '' : 'none';
		
		byId('tblProspecto').style.display = 'none';
		byId('tblModelo').style.display = 'none';
		byId('tblPermiso2').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante21') != undefined) {
			byId('imgCerrarDivFlotante21').onclick = function () {
				byId('tblProspecto').style.display = tblProspecto;
				byId('tblModelo').style.display = tblModelo;
				byId('tblPermiso2').style.display = tblPermiso2;
				
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
		
		if (verTabla == "tblProspecto") {
			document.forms['frmProspecto'].reset();
			byId('hddIdClienteProspecto').value = '';
			
			$('#documentoRecaudados').hide();
			$('#actividadAsignadas').hide();
			$('#actividadSeguimiento').hide();
			
			byId('txtCompania').className = 'inputHabilitado';
			
			byId('lstEstadoCivil').className = 'inputHabilitado';
			byId('txtFechaNacimiento').className = 'inputHabilitado';
			byId('lstNivelSocial').className = 'inputHabilitado';
			byId('txtObservacion').className = 'inputHabilitado';
			
			byId('txtUrbanizacionProspecto').className = 'inputHabilitado';
			byId('txtCalleProspecto').className = 'inputHabilitado';
			byId('txtCasaProspecto').className = 'inputHabilitado';
			byId('txtMunicipioProspecto').className = 'inputHabilitado';
			byId('txtCiudadProspecto').className = 'inputHabilitado';
			byId('txtEstadoProspecto').className = 'inputHabilitado';
			byId('txtTelefonoProspecto').className = 'inputHabilitado';
			byId('txtOtroTelefonoProspecto').className = 'inputHabilitado';
			byId('txtCorreoProspecto').className = 'inputHabilitado';
			
			byId('txtUrbanizacionPostalProspecto').className = 'inputHabilitado';
			byId('txtCallePostalProspecto').className = 'inputHabilitado';
			byId('txtCasaPostalProspecto').className = 'inputHabilitado';
			byId('txtMunicipioPostalProspecto').className = 'inputHabilitado';
			byId('txtCiudadPostalProspecto').className = 'inputHabilitado';
			byId('txtEstadoPostalProspecto').className = 'inputHabilitado';
			
			byId('txtUrbanizacionComp').className = 'inputHabilitado';
			byId('txtCalleComp').className = 'inputHabilitado';
			byId('txtCasaComp').className = 'inputHabilitado';
			byId('txtMunicipioComp').className = 'inputHabilitado';
			byId('txtEstadoComp').className = 'inputHabilitado';
			byId('txtTelefonoComp').className = 'inputHabilitado';
			byId('txtOtroTelefonoComp').className = 'inputHabilitado';
			byId('txtEmailComp').className = 'inputHabilitado';
			
			byId('txtFechaUltAtencion').className = 'inputHabilitado';
			byId('txtFechaUltEntrevista').className = 'inputHabilitado';
			byId('txtFechaProxEntrevista').className = 'inputHabilitado';
			
			xajax_formProspecto(valor, xajax.getFormValues('frmProspecto'));
			
			if (valor > 0) {
				//xajax_cargaDocumentosNecesario(valor);
				
				byId('lstTipoProspecto').className = 'inputInicial';
				byId('txtCedulaProspecto').className = 'inputInicial';
				byId('txtNitProspecto').className = 'inputInicial';
				byId('txtNombreProspecto').className = 'inputInicial';
				byId('txtApellidoProspecto').className = 'inputInicial';
				byId('txtLicenciaProspecto').className = 'inputHabilitado';
				
				byId('txtCedulaProspecto').readOnly = true;
				byId('txtNitProspecto').readOnly = true;
				byId('txtNombreProspecto').readOnly = true;
				byId('txtApellidoProspecto').readOnly = true;
				
				tituloDiv2 = 'Editar Prospecto';
			} else {
				byId('lstTipoProspecto').className = 'inputHabilitado';
				byId('txtCedulaProspecto').className = 'inputHabilitado';
				byId('txtNitProspecto').className = 'inputHabilitado';
				byId('txtNombreProspecto').className = 'inputHabilitado';
				byId('txtApellidoProspecto').className = 'inputHabilitado';
				byId('txtLicenciaProspecto').className = 'inputHabilitado';
				
				byId('txtCedulaProspecto').readOnly = false;
				byId('txtNitProspecto').readOnly = false;
				byId('txtNombreProspecto').readOnly = false;
				byId('txtApellidoProspecto').readOnly = false;
				
				tituloDiv2 = 'Agregar Prospecto';
			}
			byId('btnCancelarProspecto').onclick = function () { byId('imgCerrarDivFlotante2').click(); }
		} else if (verTabla == "tblModelo") {
			document.forms['frmBuscarModelo'].reset();
			
			byId('txtCriterioBuscarModelo').className = 'inputHabilitado';
			
			byId('hddObjDestinoModelo').value = 'ListaModelo';
			
			byId('btnBuscarModelo').click();
			
			xajax_cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "BuscarModelo", "true", "", "", "byId('btnBuscarModelo').click();");
			xajax_cargaLstAnoBuscar('lstAnoBuscarModelo', '', 'byId(\'btnBuscarModelo\').click();');
	
			tituloDiv2 = 'Modelo de Interés';
			byId('btnCancelarModelo').onclick = function () { byId('imgCerrarDivFlotante2').click(); }
		} else if (verTabla == "tblPermiso2") {
			document.forms['frmPermiso2'].reset();
			byId('hddModuloPermiso2').value = '';
			
			byId('txtContrasenaPermiso2').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion2(valor);
			
			tituloDiv2 = 'Ingreso de Clave Especial';
			byId('btnCancelarPermiso2').onclick = function () { byId('imgCerrarDivFlotante2').click(); }
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblProspecto") {
			byId('txtNombreProspecto').focus();
			byId('txtNombreProspecto').select();
		} else if (verTabla == "tblPermiso2") {
			byId('txtContrasenaPermiso2').focus();
			byId('txtContrasenaPermiso2').select();
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
		&& validarCampo('txtPorcInicial','t','numPositivo') == true
		&& validarCampo('txtMontoInicial','t','numPositivo') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtIdEmpleado','t','');
			validarCampo('txtIdCliente','t','');
			validarCampo('lstMoneda','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtPorcInicial','t','numPositivo');
			validarCampo('txtMontoInicial','t','numPositivo');
			
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
				&& validarCampo('txtPrecioBase','t','monto') == true
				&& validarCampo('txtDescuento','t','numPositivo') == true)) {
					validarCampo('txtIdUnidadBasica','t','');
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
				xajax_guardarDcto(xajax.getFormValues('frmDcto'));
			}
		}
	}
	
	function validarFrmProspecto() {
		error = false;
		
		if (byId('lstTipoProspecto') != undefined && byId('lstTipoProspecto').style.display != 'none') {
			if (!(validarCampo('lstTipoProspecto','t','lista') == true)) {
				validarCampo('lstTipoProspecto','t','lista');
				
				error = true;
			}
		}
		
		if (!(validarCampo('txtNombreProspecto','t','') == true
		&& validarCampo('txtTelefonoProspecto','t','telefono') == true
		&& validarCampo('txtOtroTelefonoProspecto','','telefono') == true
		&& validarCampo('txtCorreoProspecto','t','email') == true
		&& validarCampo('txtTelefonoComp','','telefono') == true
		&& validarCampo('txtOtroTelefonoComp','','telefono') == true
		&& validarCampo('txtEmailComp','','email') == true)) {
			validarCampo('txtNombreProspecto','t','');
			validarCampo('txtTelefonoProspecto','t','telefono');
			validarCampo('txtOtroTelefonoProspecto','','telefono');
			validarCampo('txtCorreoProspecto','t','email');
			validarCampo('txtTelefonoComp','','telefono');
			validarCampo('txtOtroTelefonoComp','','telefono');
			validarCampo('txtEmailComp','','email');
			
			error = true;
		}
		
		if (inArray(byId('lstTipoProspecto').value, [-1,1])) { // -1 = Seleccione, 1 = Natural, 2 = Juridico
			if (!(validarCampo('txtApellidoProspecto','t','') == true)) {
				validarCampo('txtApellidoProspecto','t','');
				
				error = true;
			}
		} else if (byId('lstTipoProspecto').value == 2) { // -1 = Seleccione, 1 = Natural, 2 = Juridico
		} else {
			byId('txtApellidoProspecto').className = "inputInicial";
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarProspecto(xajax.getFormValues('frmProspecto'), xajax.getFormValues('frmDcto'));
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
        	<td class="tituloPaginaVehiculos">Presupuesto de Venta</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
		</tr>
        <tr>
        	<td>
            <form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
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
                                    <td align="right" class="tituloCampo" width="40%">Nro. Presupuesto:</td>
                                    <td width="60%">
                                        <input type="text" id="txtNumeroPresupuestoVenta" name="txtNumeroPresupuestoVenta" readonly="readonly" size="20" style="text-align:center"/>
                                        <input type="hidden" id="hddIdPresupuestoVenta" name="hddIdPresupuestoVenta"/>
                                        <input type="hidden" id="hddPresupuestoImportado" name="hddPresupuestoImportado"/>
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
                            <td></td>
                        	<td></td>
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
                            <td></td>
                        	<td></td>
                            <td></td>
                        	<td></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
				</tr>
                <tr id="trFieldsetVehiculoUsado">
                	<td valign="top" width="50%">
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
                    </td>
				</tr>
                <tr id="trBtnGuardar">
                    <td align="right" colspan="2"><hr>
                        <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                        <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.location.href='an_presupuesto_venta_list.php';"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
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
                    <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblProspecto');">
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
                <td id="tdUbicacion" align="right"></td>
                <td id="tdlstUbicacion"></td>
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
    
<form id="frmProspecto" name="frmProspecto" onsubmit="return false;" style="margin:0">
	<div class="pane" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" id="tblProspecto" width="100%">
        <tr>
            <td>
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td>
                    <fieldset><legend class="legend">Datos Generales</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td width="11%"></td>
                            <td width="23%"></td>
                            <td width="11%"></td>
                            <td width="23%"></td>
                            <td width="11%"></td>
                            <td width="21%"></td>
                        </tr>
                        <tr id="trCedulaProspecto" align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo:</td>
                            <td>
                                <select id="lstTipoProspecto" name="lstTipoProspecto" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">Natural</option>
                                    <option value="2">Juridico</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanClienteCxC; ?>:</td>
                            <td nowrap="nowrap">
                            <div style="float:left">
                                <input type="text" id="txtCedulaProspecto" name="txtCedulaProspecto" maxlength="18" size="20" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI; ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo"><?php echo $spanNIT; ?>:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtNitProspecto" name="txtNitProspecto" maxlength="18" size="20" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoNIT; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre(s):</td>
                            <td><input type="text"  id="txtNombreProspecto"name="txtNombreProspecto" size="25" maxlength="50"/></td>
                            <td align="right" class="tituloCampo">Apellido(s):</td>
                            <td><input type="text" id="txtApellidoProspecto" name="txtApellidoProspecto" size="25" maxlength="50"/></td>
                            <td align="right" class="tituloCampo">Licencia:</td>
                            <td><input type="text" id="txtLicenciaProspecto" name="txtLicenciaProspecto" maxlength="18" size="20" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table border="0" width="100%">
                        <tr>
                            <td valign="top">
                            <fieldset><legend class="legend">Dirección</legend>
                                <div class="wrap">
                                    <!-- the tabs -->
                                    <ul class="tabs">
                                        <li><a href="#">Residencial</a></li>
                                        <li><a href="#">Postal</a></li>
                                        <li><a href="#">Trabajo</a></li>
                                    </ul>
                                    
                                    <!-- tab "panes" -->
                                    <div class="pane">
                                    	<table border="0" width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanUrbanizacion); ?>:</td>
                                            <td width="21%"><input type="text" name="txtUrbanizacionProspecto" id="txtUrbanizacionProspecto" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCalleAv); ?>:</td>
                                            <td width="22%"><input type="text" name="txtCalleProspecto" id="txtCalleProspecto" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCasaEdif); ?>:</td>
                                            <td width="21%"><input type="text" name="txtCasaProspecto" id="txtCasaProspecto" style="width:99%"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo"><?php echo utf8_encode($spanMunicipio); ?>:</td>
                                            <td><input type="text" name="txtMunicipioProspecto" id="txtMunicipioProspecto" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo"><?php echo utf8_encode($spanCiudad); ?>:</td>
                                            <td><input type="text" name="txtCiudadProspecto" id="txtCiudadProspecto" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo"><?php echo utf8_encode($spanEstado); ?>:</td>
                                            <td><input type="text" name="txtEstadoProspecto" id="txtEstadoProspecto" style="width:99%"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                                            <td>
                                            <div style="float:left">
                                                <input type="text" name="txtTelefonoProspecto" id="txtTelefonoProspecto" size="16" style="text-align:center"/>
                                            </div>
                                            <div style="float:left">
                                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                            </div>
                                            </td>
                                            <td align="right" class="tituloCampo">Otro Telf.:</td>
                                            <td>
                                            <div style="float:left">
                                                <input type="text" name="txtOtroTelefonoProspecto" id="txtOtroTelefonoProspecto" size="16" style="text-align:center"/>
                                            </div>
                                            <div style="float:left">
                                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                            </div>
                                            </td>
                                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanEmail; ?>:</td>
                                            <td>
                                            <div style="float:left">
                                                <input type="text" name="txtCorreoProspecto" id="txtCorreoProspecto" maxlength="50" style="width:99%"/>
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
                                            <td width="21%"><input type="text" name="txtUrbanizacionPostalProspecto" id="txtUrbanizacionPostalProspecto" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCalleAv); ?>:</td>
                                            <td width="22%"><input type="text" name="txtCallePostalProspecto" id="txtCallePostalProspecto" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCasaEdif); ?>:</td>
                                            <td width="21%"><input type="text" name="txtCasaPostalProspecto" id="txtCasaPostalProspecto" style="width:99%"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo"><?php echo utf8_encode($spanMunicipio); ?>:</td>
                                            <td><input type="text" name="txtMunicipioPostalProspecto" id="txtMunicipioPostalProspecto" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo"><?php echo utf8_encode($spanCiudad); ?>:</td>
                                            <td><input type="text" name="txtCiudadPostalProspecto" id="txtCiudadPostalProspecto" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo"><?php echo utf8_encode($spanEstado); ?>:</td>
                                            <td><input type="text" name="txtEstadoPostalProspecto" id="txtEstadoPostalProspecto" style="width:99%"/></td>
                                        </tr>
                                        </table>
                                    </div>
                                    
                                    <div class="pane">
                                    	<table border="0" width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanUrbanizacion); ?>:</td>
                                            <td width="21%"><input type="text" name="txtUrbanizacionComp" id="txtUrbanizacionComp" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCalleAv); ?>:</td>
                                            <td width="22%"><input type="text" name="txtCalleComp" id="txtCalleComp" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo" width="12%"><?php echo utf8_encode($spanCasaEdif); ?>:</td>
                                            <td width="21%"><input type="text" name="txtCasaComp" id="txtCasaComp" style="width:99%"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo"><?php echo utf8_encode($spanMunicipio); ?>:</td>
                                            <td><input type="text" name="txtMunicipioComp" id="txtMunicipioComp" style="width:99%"/></td>
                                            <td align="right" class="tituloCampo"><?php echo $spanEstado; ?>:</td>
                                            <td><input type="text" name="txtEstadoComp" id="txtEstadoComp" style="width:99%"/></td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Teléfono:</td>
                                            <td>
                                            <div style="float:left">
                                                <input type="text" name="txtTelefonoComp" id="txtTelefonoComp" size="16" style="text-align:center"/>
                                            </div>
                                            <div style="float:left">
                                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                            </div>
                                            </td>
                                            <td align="right" class="tituloCampo">Otro Telf.:</td>
                                            <td>
                                            <div style="float:left">
                                                <input type="text" name="txtOtroTelefonoComp" id="txtOtroTelefonoComp" size="16" style="text-align:center"/>
                                            </div>
                                            <div style="float:left">
                                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                            </div>
                                            </td>
                                            <td align="right" class="tituloCampo"><?php echo $spanEmail; ?>:</td>
                                            <td>
                                            <div style="float:left">
                                                <input type="text" name="txtEmailComp" id="txtEmailComp" maxlength="50" style="width:99%"/>
                                            </div>
                                            <div style="float:left">
                                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                                            </div>
                                            </td>
                                        </tr>
                                        </table>
                                    </div>
								</div>
                            </fieldset>
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
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#">Modelo de Interes</a></li>
                        <li><a href="#">Datos Adicionales</a></li>
                        <li><a href="#">Seguimiento</a></li>
                    </ul>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                        <table border="0" width="100%">
                        <tr align="left">
                            <td colspan="6">
                                <a class="modalImg" id="aNuevoModelo" rel="#divFlotante2" onclick="abrirDivFlotante2(null, 'tblModelo');">
                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                <button id="btnEliminar" name="btnEliminarModelo" onclick="xajax_eliminarModelo(xajax.getFormValues('frmProspecto'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr align="center" class="tituloColumna">
                            <td></td>
                            <td width="40%">Modelo</td>
                            <td width="15%"><?php echo $spanPrecioUnitario; ?> Base</td>
                            <td width="15%">Medio</td>
                            <td width="15%">Niv. Interés</td>
                            <td width="15%">Plan Pago</td>
                        </tr>
                        <tr id="trItmPieModeloInteres"></tr>
                        </table>
                        <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                    </div>
                    
                    <div class="pane">
                        <table border="0" width="100%"> <!--sf/*-/*--->
                        <tr align="left">
                            <td align="right" class="tituloCampo">Compañia:</td>
                            <td><input type="text" name="txtCompania" id="txtCompania" maxlength="50"/></td>
                            <td align="right" class="tituloCampo">Puesto:</td>
                            <td id="td_select_puesto" align="left"></td>
                            <td align="right" class="tituloCampo">Título:</td>
                            <td id="td_select_titulo" align="left"></td>
                        </tr>
                         <tr align="left">
                            <td align="right" class="tituloCampo">Nivel de Influencia:</td>
                            <td id="td_select_nivel_influencia"></td>
                            <td align="right" class="tituloCampo">Sector:</td>
                            <td id="td_select_sector"></td>
                            <td align="right" class="tituloCampo">Estatus:</td>
                            <td id="td_select_estatus"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="14%">Estado Civil:</td>
                            <td id="tdlstEstadoCivil" width="19%">
                                <select size="1" name="lstEstadoCivil" id="lstEstadoCivil">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="14%">Sexo:</td>
                            <td width="19%">
                                <input type="radio" name="rdbSexo" id="rdbSexoM" value="M"/>M
                                <input type="radio" name="rdbSexo" id="rdbSexoF" value="F"/>F
                            </td>
                            <td align="right" class="tituloCampo" width="14%">Fecha Nacimiento:</td>
                            <td width="20%"><input type="text" id="txtFechaNacimiento" name="txtFechaNacimiento" size="12" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Clase Social:</td>
                            <td>
                                <select name="lstNivelSocial" id="lstNivelSocial" style="width:99%">
                                    <option value="">[ Seleccione ]</option>
                                    <option value="3">Alta</option>
                                    <option value="2">Media</option>
                                    <option value="1">Baja</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" rowspan="2">Observación:</td>
                            <td colspan="4" rowspan="2"><textarea id="txtObservacion" name="txtObservacion" cols="45" rows="2"></textarea></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Posibilidad de cierre:</td>
                            <td id="td_select_posibilidad_cierre"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Motivo de Rechazo:</td>
                            <td id="td_select_motivo_rechazo"></td>
                        </tr>
                        </table>
                    </div>
                    
                    <div class="pane">
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="16%">Ultima Atención:</td>
                            <td width="17%"><input type="text" id="txtFechaUltAtencion" name="txtFechaUltAtencion" autocomplete="off" size="10" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo" width="16%">Ultima Entrevista:</td>
                            <td width="17%"><input type="text" id="txtFechaUltEntrevista" name="txtFechaUltEntrevista" autocomplete="off" size="10" style="text-align:center"/></td>
                            <td align="right" class="tituloCampo" width="16%">Próxima Entrevista:</td>
                            <td width="18%"><input type="text" id="txtFechaProxEntrevista" name="txtFechaProxEntrevista" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                        
                    	<table border="0" width="100%">
                        <tr>
                            <td valign="top" width="50%">
                            <fieldset id="documentoRecaudados" style="display:none;"><legend class="legend">Documentos Requeridos</legend>
                                <div id="divDocumentosAEntregar" style="max-height:250px; overflow:auto;"></div>
                            </fieldset>
                            <fieldset id="actividadAsignadas" style="display:none;"><legend class="legend">Historial de Actividaddes</legend>
                                <div id="divActividad" style="max-height:250px; overflow:auto;"></div>
                            </fieldset>
                            </td>
                            <td valign="top" width="50%">
                            <fieldset id="actividadSeguimiento" style="display:none;"><legend class="legend">Seguimiento de Actividades</legend>
                                <div id="divActividadSeguimiento" style="max-height:250px; overflow:auto;"></div>
                            </fieldset>
                            </td>
                        </tr>
                        </table>
                        
                        <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                        <tr>
                            <td align="center"width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                            <td>
                                <table align="center">
                                <tr>
                                    <td><img width="13" height="13" src="../img/minselect.png"></td><td>Consignado</td> 
                                    <td>&nbsp;</td>
                                    <td><img title="Asginado" src="../img/iconos/ico_aceptar_azul.png"></td><td>Actividad Asignada</td>
                                    <td>&nbsp;</td>
                                    <td><img title="Finalizada" src="../img/iconos/ico_aceptar.gif"></td><td>Actividad Finalizada</td> 
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/arrow_rotate_clockwise.png"></td><td>Activada Finalizada Automaticamente</td>
                                </tr>
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
            	<input type="hidden" name="hddIdPerfilProspecto" id="hddIdPerfilProspecto" readonly="readonly"/>
                <input type="hidden" name="hddIdClienteProspecto" id="hddIdClienteProspecto" readonly="readonly"/>
                <button type="submit" id="btnGuardarProspecto" name="btnGuardarProspecto" onclick="validarFrmProspecto();">Guardar</button>
                <button type="button" id="btnCancelarProspecto" name="btnCancelarProspecto">Cancelar</button> 
            </td>
        </tr>
        </table>
	</div>
</form>
	
    <div id="tblModelo" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBuscarModelo" name="frmBuscarModelo" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddObjDestinoModelo" name="hddObjDestinoModelo"/>
                <input type="hidden" id="hddNomVentanaModelo" name="hddNomVentanaModelo"/>
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Marca:</td>
                    <td id="tdlstMarcaBuscarModelo"></td>
                    <td align="right" class="tituloCampo" width="120">Modelo:</td>
                    <td id="tdlstModeloBuscarModelo"></td>
                    <td align="right" class="tituloCampo" width="120">Versión:</td>
                    <td id="tdlstVersionBuscarModelo"></td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td align="right" class="tituloCampo">Año:</td>
                    <td id="tdlstAnoBuscarModelo"></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarModelo" name="txtCriterioBuscarModelo" onkeyup="byId('btnBuscarModelo').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarModelo" name="btnBuscarModelo" onclick="xajax_buscarUnidadBasicaModelo(xajax.getFormValues('frmBuscarModelo'), xajax.getFormValues('frmDcto'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarModelo'].reset(); byId('btnBuscarModelo').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
                <div id="divListaModelo" style="width:100%"></div>
            </td>
        </tr>
        <tr>
            <td align="right" colspan="6"><hr>
                <button type="button" id="btnCancelarModelo" name="btnCancelarModelo">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
    
<form id="frmPermiso2" name="frmPermiso2" onsubmit="return false;" style="margin:0px">
	<table border="0" id="tblPermiso2" style="display:none" width="560">
    <tr>
        <td>
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="25%">Acción:</td>
                <td width="75%"><input type="text" id="txtDescripcionPermiso2" name="txtDescripcionPermiso2" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td>
                    <input type="password" id="txtContrasenaPermiso2" name="txtContrasenaPermiso2" size="30"/>
                    <input type="hidden" id="hddModuloPermiso2" name="hddModuloPermiso2" readonly="readonly" size="30"/>
                </td>
            </tr>
        	</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="submit" id="btnGuardarPermiso2" name="btnGuardarPermiso2" onclick="validarFrmPermiso2();">Aceptar</button>
			<button type="button" id="btnCancelarPermiso2" name="btnCancelarPermiso2">Cancelar</button>
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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargarDcto('<?php echo $_GET['id']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>