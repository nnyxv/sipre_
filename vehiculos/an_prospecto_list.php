<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_prospecto_list"))) {
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
include("controladores/ac_an_prospecto_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Prospectos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
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
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		tblProspecto = (byId('tblProspecto').style.display == '') ? '' : 'none';
		tblCliente = (byId('tblCliente').style.display == '') ? '' : 'none';
		
		byId('tblProspecto').style.display = 'none';
		byId('tblCliente').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante11') != undefined) {
			byId('imgCerrarDivFlotante11').onclick = function () {
				byId('tblProspecto').style.display = tblProspecto;
				byId('tblCliente').style.display = tblCliente;
				
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
				xajax_cargaDocumentosNecesario(valor);
				
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
				
				tituloDiv1 = 'Editar Prospecto';
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
				
				tituloDiv1 = 'Agregar Prospecto';
			}
		} else if (verTabla == "tblCliente") {
			document.forms['frmCliente'].reset();
			byId('hddIdCliente').value = '';
			
			byId('lstContribuyente').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			byId('txtNit').className = 'inputHabilitado';
			
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
				byId('txtLicencia').className = 'inputHabilitado';
				
				byId('txtCedula').readOnly = true;
				byId('txtNombre').readOnly = true;
				byId('txtApellido').readOnly = true;
				
				tituloDiv1 = 'Editar Cliente';
			} else {
				byId('lstTipo').className = 'inputHabilitado';
				byId('txtCedula').className = 'inputHabilitado';
				byId('txtNombre').className = 'inputHabilitado';
				byId('txtApellido').className = 'inputHabilitado';
				byId('txtLicencia').className = 'inputHabilitado';
				
				byId('txtCedula').readOnly = false;
				byId('txtNombre').readOnly = false;
				byId('txtApellido').readOnly = false;
				
				tituloDiv1 = 'Agregar Cliente';
			}
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblProspecto") {
			byId('txtNombreProspecto').focus();
			byId('txtNombreProspecto').select();
		} else if (verTabla == "tblCliente") {
			byId('txtNombre').focus();
			byId('txtNombre').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		tblListaEmpresa = (byId('tblListaEmpresa').style.display == '') ? '' : 'none';
		tblLista = (byId('tblLista').style.display == '') ? '' : 'none';
		tblCredito = (byId('tblCredito').style.display == '') ? '' : 'none';
		tblModelo = (byId('tblModelo').style.display == '') ? '' : 'none';
		
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblLista').style.display = 'none';
		byId('tblCredito').style.display = 'none';
		byId('tblModelo').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante21') != undefined) {
			byId('imgCerrarDivFlotante21').onclick = function () {
				byId('tblListaEmpresa').style.display = tblListaEmpresa;
				byId('tblLista').style.display = tblLista;
				byId('tblCredito').style.display = tblCredito;
				byId('tblModelo').style.display = tblModelo;
				
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
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblLista") {
			if (valor == "Empleado") {
				document.forms['frmBuscarEmpleado'].reset();
				
				byId('txtCriterioBuscarEmpleado').className = 'inputHabilitado';
				
				byId('trBuscarEmpleado').style.display = '';
				byId('trBuscarCliente').style.display = 'none';
				byId('btnGuardarLista').style.display = 'none';
				
				byId('btnBuscarEmpleado').click();
				
				tituloDiv2 = 'Empleados';
				byId(verTabla).width = "760";
			}
		} else if (verTabla == "tblCredito") {
			document.forms['frmCredito'].reset();
			
			byId('txtDiasCredito').className = 'inputHabilitado';
			byId('txtLimiteCredito').className = 'inputHabilitado';
			
			xajax_formCredito(valor, xajax.getFormValues('frmCliente'));
			
			tituloDiv2 = 'Crédito';
		} else if (verTabla == "tblModelo") {
			document.forms['frmBuscarModelo'].reset();
			
			byId('txtCriterioBuscarModelo').className = 'inputHabilitado';
			
			byId('btnBuscarModelo').click();
			
			xajax_cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "BuscarModelo", "true", "", "", "byId('btnBuscarModelo').click();");
			xajax_cargaLstAnoBuscar('lstAnoBuscarModelo', '', 'byId(\'btnBuscarModelo\').click();');
	
			tituloDiv2 = 'Modelo de Interés';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblLista") {
			if (valor == "Empleado") {
				byId('txtCriterioBuscarEmpleado').focus();
				byId('txtCriterioBuscarEmpleado').select();
			}
		} else if (verTabla == "tblCredito") {
			byId('txtDiasCredito').focus();
			byId('txtDiasCredito').select();
		} else if (verTabla == "tblModelo") {
			byId('txtCriterioBuscarModelo').focus();
			byId('txtCriterioBuscarModelo').select();
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
			xajax_guardarProspecto(xajax.getFormValues('frmProspecto'), xajax.getFormValues('frmListaCliente'));
		}
	}
	
	function validarFrmCliente() {
		error = false;
		
		if (!(validarCampo('lstContribuyente','t','lista') == true
		&& validarCampo('lstCredito','t','listaExceptCero') == true
		&& validarCampo('lstTipo','t','lista') == true
		&& validarCampo('txtNombre','t','') == true
		&& validarCampo('txtUrbanizacion','t','') == true
		&& validarCampo('txtCalle','t','') == true
		&& validarCampo('txtCasa','t','') == true
		&& validarCampo('txtMunicipio','t','') == true
		&& validarCampo('txtCiudad','t','') == true
		&& validarCampo('txtEstado','t','') == true
		&& validarCampo('txtTelefono','t','telefono') == true
		&& validarCampo('txtOtroTelefono','','telefono') == true
		&& validarCampo('txtCorreo','','email') == true
		&& validarCampo('lstReputacionCliente','t','lista') == true
		&& validarCampo('lstTipoCliente','t','lista') == true
		&& validarCampo('lstDescuento','t','listaExceptCero') == true
		&& validarCampo('lstContribuyente','t','listaExceptCero') == true
		&& validarCampo('lstEstatus','t','lista') == true
		&& validarCampo('lstClaveMovimiento','t','lista') == true
		&& validarCampo('txtTelefonoContacto','','telefono') == true
		&& validarCampo('txtCorreoContacto','','email') == true)) {
			validarCampo('lstContribuyente','t','lista');
			validarCampo('lstCredito','t','listaExceptCero');
			validarCampo('lstTipo','t','lista');
			validarCampo('txtNombre','t','');
			validarCampo('txtUrbanizacion','t','');
			validarCampo('txtCalle','t','');
			validarCampo('txtCasa','t','');
			validarCampo('txtMunicipio','t','');
			validarCampo('txtCiudad','t','');
			validarCampo('txtEstado','t','');
			validarCampo('txtTelefono','t','telefono');
			validarCampo('txtOtroTelefono','','telefono');
			validarCampo('txtCorreo','','email');
			validarCampo('lstReputacionCliente','t','lista');
			validarCampo('lstTipoCliente','t','lista');
			validarCampo('lstDescuento','t','listaExceptCero');
			validarCampo('lstContribuyente','t','listaExceptCero');
			validarCampo('lstEstatus','t','lista');
			validarCampo('lstClaveMovimiento','t','lista');
			validarCampo('txtTelefonoContacto','','telefono');
			validarCampo('txtCorreoContacto','','email');
			
			error = true;
		}
		
		if (byId('lstTipo').value == 1) { // 1 = Natural, 2 = Juridico
			if (!(validarCampo('txtApellido','t','') == true)) {
				validarCampo('txtApellido','t','');
				
				error = true;
			}
		} else if (byId('lstTipo').value == 2) { // 1 = Natural, 2 = Juridico
		} else {
			byId('txtApellido').className = "inputInicial";
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarCliente(xajax.getFormValues('frmCliente'),xajax.getFormValues('frmListaCliente'));
		}
	}
	
	function validarInsertarEmpresa(idEmpresa) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarEmpresa' + cont) == undefined)) {
				byId('btnInsertarEmpresa' + cont).disabled = true;
			}
		}
		xajax_insertarClienteEmpresa(idEmpresa, xajax.getFormValues('frmCliente'));
		xajax_perfil_prospectacion(0);
	}
	
	//CAPTURA EL MOTIVO DE RECHAZO
	function motivoRechazo(){
		var motivo = $('#posibilidad_cierre option:selected').html();
		/*if(motivo != 'Rechazo'){
			//$('#lstMotivoRechazo').empty();
			xajax_cargaLstMotivoRechazo('', motivo);
			//$('#lstMotivoRechazo').html('');
			//$('#lstMotivoRechazo').append(new Option('[Seleccione]', true, true));
		} else {*/
			xajax_cargaLstMotivoRechazo('', motivo);
		//}
	}
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Prospectos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblProspecto');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
						<button type="button" onclick="xajax_exportarCliente(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="5"></td>
                </tr>
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Tipo de Pago:</td>
                	<td>
                        <select id="lstTipoPago" name="lstTipoPago" onchange="byId('btnBuscar').click();" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="no">Contado</option>
                            <option value="si">Crédito</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstEstatusBuscar" name="lstEstatusBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option selected="selected" value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Paga Impuesto:</td>
                    <td>
                    	<select id="lstPagaImpuesto" name="lstPagaImpuesto" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">No</option>
                            <option value="1">Si</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModulo"></td>
                	<td align="right" class="tituloCampo">Ver:</td>
                    <td>
                    	<select multiple="multiple" id="lstTipoCuentaCliente" name="lstTipoCuentaCliente" onchange="byId('btnBuscar').click();">
                        	<option value="-1">[ Seleccione ]</option>
                            <option value="1">Prospecto</option>
                            <option value="2">Prospecto Aprobado (Cliente Venta)</option>
                            <option value="3">Sin Prospectación (Cliente Post-Venta)</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Vendedor:</td>
                    <td id="tdlstEmpleado"></td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
                    <td>
                    	<button type="submit" id="btnBuscar" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscar'));">Buscar</button>
                    	<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaCliente" name="frmListaCliente" style="margin:0">
				<div id="divListaCliente" style="width:100%"></div>
            </form>
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
                            <td><img src="../img/iconos/ico_verde.gif"/></td><td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Anulado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/user_comment.png"/></td><td>Prospecto</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/user_green.png"/></td><td>Prospecto Aprobado (Cliente Venta)</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/user_gray.png"/></td><td>Sin Prospectación (Cliente Post-Venta)</td>
                        </tr>
                        </table>
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
                            <td><img src="../img/iconos/user_edit.png"/></td><td>Editar Prospecto</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/accept.png"/></td><td>Aprobar Prospecto</td>
                        </tr>
                        </table>
                    </td>
				</tr>
				</table>
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
	
<form id="frmProspecto" name="frmProspecto" onsubmit="return false;" style="margin:0">
	<div class="pane" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" id="tblProspecto" width="100%">
        <tr>
            <td>
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Vendedor:</td>
                            <td width="88%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpleado" name="txtIdEmpleado" onblur="xajax_asignarEmpleado(this.value, byId('txtIdEmpresa').value, 'false');" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpleado" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblLista', 'Empleado');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
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
                                <a class="modalImg" id="aNuevoModelo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblModelo');">
                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                <button id="btnEliminar" name="btnEliminarModelo" onclick="xajax_eliminarModelo(xajax.getFormValues('frmProspecto'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
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
                <button type="button" id="btnCancelarProspecto" name="btnCancelarProspecto" class="close">Cancelar</button> 
            </td>
        </tr>
        </table>
	</div>
</form>
    
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
                    <div style="float:left">
	                    <input type="text" id="txtCedula" name="txtCedula" maxlength="18" size="20" style="text-align:center"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI; ?>"/>
                    </div>
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
                    <td><input type="text" name="txtNombre" id="txtNombre" maxlength="50" size="26"/></td>
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
                
            <fieldset><legend class="legend">Empresas</legend>
                <table width="100%">
                <tr align="left">
                    <td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                        </a>
                        <button type="button" id="btnEliminar" name="btnEliminar" onclick="xajax_eliminarClienteEmpresa(xajax.getFormValues('frmCliente'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
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
                        <tr id="trItmPieClienteEmpresa"></tr>
                        </table>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
				<input type="hidden" name="hddIdCliente" id="hddIdCliente" readonly="readonly"/>
                <button type="submit" id="btnGuardarCliente" name="btnGuardarCliente" onclick="validarFrmCliente();">Guardar</button>
                <button type="button" id="btnCancelarCliente" name="btnCancelarCliente" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante21" src="../img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante22" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
    	<td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly"/>
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
    
    <table border="0" id="tblLista" style="display:none" width="960">
    <tr id="trBuscarEmpleado">
    	<td>
        <form id="frmBuscarEmpleado" name="frmBuscarEmpleado" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado" onkeyup="byId('btnBuscarEmpleado').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'), xajax.getFormValues('frmProspecto'));">Buscar</button>
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
   
<form id="frmCredito" name="frmCredito" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblCredito" width="360">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="34%"><span class="textoRojoNegrita">*</span>Días:</td>
            	<td width="66%"><input type="text" id="txtDiasCredito" name="txtDiasCredito" onblur="setFormatoRafk(this,2);" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Límite:</td>
            	<td><input type="text" id="txtLimiteCredito" name="txtLimiteCredito" onblur="setFormatoRafk(this,2);" size="16" style="text-align:right"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Forma de Pago:</td>
            	<td id="tdlstFormaPago"></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <input type="hidden" id="hddNumeroItm" name="hddNumeroItm" readonly="readonly"/>
			<button type="submit" id="btnGuardarCredito" name="btnGuardarCredito" onclick="validarFrmCredito();">Aceptar</button>
            <button type="button" id="btnCancelarCredito" name="btnCancelarCredito" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</form>
	
    <div id="tblModelo" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
            <form id="frmBuscarModelo" name="frmBuscarModelo" onsubmit="return false;" style="margin:0">
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
                        <button type="submit" id="btnBuscarModelo" name="btnBuscarModelo" onclick="xajax_buscarUnidadBasicaModelo(xajax.getFormValues('frmBuscarModelo'), xajax.getFormValues('frmProspecto'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarModelo'].reset(); byId('btnBuscarModelo').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td><div id="divListaModelo" style="width:100%"></div></td>
        </tr>
        <tr>
            <td align="right" colspan="6"><hr>
                <button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</div>

<script>
byId('lstTipoPago').className = 'inputHabilitado';
byId('lstEstatusBuscar').className = 'inputHabilitado';
byId('lstPagaImpuesto').className = 'inputHabilitado';
byId('lstTipoCuentaCliente').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

window.onload = function(){
	jQuery(function($){
		$("#txtFechaNacimiento").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaUltAtencion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaUltEntrevista").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaProxEntrevista").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaDesincorporar").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaNacimiento",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaUltAtencion",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaUltEntrevista",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaProxEntrevista",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesincorporar",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>