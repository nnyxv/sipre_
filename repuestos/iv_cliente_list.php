<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_cliente_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_iv_cliente_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Repuestos - Clientes</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
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
		byId('tblCliente').style.display = 'none';
		
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
				
				tituloDiv1 = 'Editar Cliente';
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
				
				tituloDiv1 = 'Agregar Cliente';
			}
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblCliente") {
			byId('txtNombre').focus();
			byId('txtNombre').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblPermiso').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblCredito').style.display = 'none';
		byId('tblListaImpuesto').style.display = 'none';
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor);
			
			tituloDiv2 = 'Ingreso de Clave Especial';
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblCredito") {
			document.forms['frmCredito'].reset();
			
			byId('txtDiasCredito').className = 'inputHabilitado';
			byId('txtLimiteCredito').className = 'inputHabilitado';
			
			xajax_formCredito(valor, xajax.getFormValues('frmCliente'));
			
			tituloDiv2 = 'Crédito';
		} else if (verTabla == "tblListaImpuesto") {
			document.forms['frmBuscarImpuesto'].reset();
			
			byId('btnBuscarImpuesto').click();
			
			tituloDiv2 = 'Impuestos';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblCredito") {
			byId('txtDiasCredito').focus();
			byId('txtDiasCredito').select();
		} else if (verTabla == "tblListaImpuesto") {
			byId('txtCriterioBuscarImpuesto').focus();
			byId('txtCriterioBuscarImpuesto').select();
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
	
	function validarFrmCredito() {
		error = false;
		
		if (!(validarCampo('txtDiasCredito','t','') == true
		&& validarCampo('txtLimiteCredito','t','') == true
		&& validarCampo('lstFormaPago','t','lista') == true)) {
			validarCampo('txtDiasCredito','t','');
			validarCampo('txtLimiteCredito','t','');
			validarCampo('lstFormaPago','t','lista');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_asignarCredito(xajax.getFormValues('frmCredito'));
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
	
	function validarInsertarEmpresa(idEmpresa) {
		xajax_insertarClienteEmpresa(idEmpresa, xajax.getFormValues('frmCliente'));
	}
	
	function validarInsertarImpuesto(idImpuesto) {
		xajax_insertarImpuesto(idImpuesto, xajax.getFormValues('frmCliente'));
	}
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_repuestos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaRepuestos">Clientes</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblCliente');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
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
                    <td></td>
                    <td></td>
                    <td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModulo"></td>
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
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmCliente" name="frmCliente" onsubmit="return false;" style="margin:0">
	<div id="tblCliente" style="max-height:520px; overflow:auto; width:960px;">
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
                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                <button type="button" id="btnEliminarImpuesto" name="btnEliminarImpuesto" onclick="xajax_eliminarClienteImpuesto(xajax.getFormValues('frmCliente'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
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
                <button type="button" id="btnCancelarCliente" name="btnCancelarCliente" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>

<form id="frmPermiso" name="frmPermiso" style="margin:0px" onsubmit="return false;">
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
    
	<table border="0" id="tblListaImpuesto" width="760">
    <tr>
    	<td>
        <form id="frmBuscarImpuesto" name="frmBuscarImpuesto" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarImpuesto" name="txtCriterioBuscarImpuesto" class="inputHabilitado" onkeyup="byId('btnBuscarImpuesto').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarImpuesto" name="btnBuscarImpuesto" onclick="xajax_buscarImpuesto(xajax.getFormValues('frmBuscarImpuesto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarImpuesto'].reset(); byId('btnBuscarImpuesto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divListaImpuesto" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarListaImpuesto" name="btnCancelarListaImpuesto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('lstTipoPago').className = 'inputHabilitado';
byId('lstEstatusBuscar').className = 'inputHabilitado';
byId('lstPagaImpuesto').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesincorporar").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesincorporar",
		dateFormat:"<?php echo spanDatePick; ?>",
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstModuloBuscar();
xajax_listaCliente(0,'id','DESC','<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|'+byId('lstTipoPago').value+'|'+byId('lstEstatusBuscar').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>