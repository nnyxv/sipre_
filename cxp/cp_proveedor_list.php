<?php
require_once("../connections/conex.php");

@session_start();

/* Validación del Módulo */
require_once("../inc_sesion.php");
if (!validaAcceso("cp_proveedor_list")){
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cp_proveedor_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Proveedores</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorPagar.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblProveedor').style.display = 'none';
		
		if (verTabla == "tblProveedor") {
			document.forms['frmProveedor'].reset();
			byId('hddIdProveedor').value = '';
			
			byId('lstTipoProveedor').className = 'inputHabilitado';
			byId('lstContribuyente').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			byId('txtNombreProveedor').className = 'inputHabilitado';
			byId('txtCiRif').className = 'inputHabilitado';
			byId('txtNit').className = 'inputHabilitado';
			byId('txtEstado').className = 'inputHabilitado';
			byId('txtCiudad').className = 'inputHabilitado';
			byId('txtDireccion').className = 'inputHabilitado';
			byId('txtTelefono').className = 'inputHabilitado';
			byId('txtFax').className = 'inputHabilitado';
			byId('txtOtroTelf').className = 'inputHabilitado';
			byId('txtEmail').className = 'inputHabilitado';
			byId('lstTipoProveedorNacImp').className = 'inputHabilitado';
			byId('lstTipo').className = 'inputHabilitado';
			byId('txtNombreContacto').className = 'inputHabilitado';
			byId('txtTelefonoContacto').className = 'inputHabilitado';
			byId('txtCedulaContacto').className = 'inputHabilitado';
			byId('txtCorreoContacto').className = 'inputHabilitado';
			byId('lstDescuento').className = 'inputHabilitado';
			byId('lstDiasCredito').className = 'inputHabilitado';
			byId('txtLimiteCredito').className = 'inputHabilitado';
			byId('lstPlanMayor').className = 'inputHabilitado';
			byId('txtObservaciones').className = 'inputHabilitado';
			
			xajax_formProveedor(valor, xajax.getFormValues('frmProveedor'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Proveedor';
			} else {
				tituloDiv1 = 'Agregar Proveedor';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblProveedor") {
			byId('txtNombreProveedor').focus();
			byId('txtNombreProveedor').select();
		}
	}
	
	function validarFrmProveedor() {
		error = false;
		
		if (!(validarCampo('lstContribuyente','t','lista') == true
		&& validarCampo('lstEstatus','','listaExceptCero') == true
		&& validarCampo('txtNombreProveedor','t','') == true
		&& validarCampo('lstPais','t','lista') == true
		&& validarCampo('txtEstado','t','') == true
		&& validarCampo('txtCiudad','t','') == true
		&& validarCampo('txtDireccion','t','') == true
		&& validarCampo('txtTelefono','t','telefono') == true
		&& validarCampo('txtEmail','','email') == true
		//&& validarCampo('txtNombreContacto','t','') == true
		&& validarCampo('txtTelefonoContacto','','telefono') == true
		&& validarCampo('txtCorreoContacto','','email') == true
		&& validarCampo('txtOtroTelf','','telefono') == true
		&& validarCampo('txtFax','','telefono') == true)) {
			validarCampo('lstContribuyente','t','lista');
			validarCampo('lstEstatus','','listaExceptCero');
			validarCampo('txtNombreProveedor','t','');
			validarCampo('lstPais','t','lista');
			validarCampo('txtEstado','t','');
			validarCampo('txtCiudad','t','');
			validarCampo('txtDireccion','t','');
			validarCampo('txtTelefono','t','telefono');
			validarCampo('txtEmail','','email');
			//validarCampo('txtNombreContacto','t','');
			validarCampo('txtTelefonoContacto','','telefono');
			validarCampo('txtCorreoContacto','','email');
			validarCampo('txtOtroTelf','','telefono');
			validarCampo('txtFax','','telefono');
			
			error = true;
		}
		
		if (byId('lstTipoProveedor').value == "Si") {
			if (!(validarCampo('txtLimiteCredito','t','') == true)) {
				validarCampo('txtLimiteCredito','t','');
				
				error = true;
			}
		}
				
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarProveedor(xajax.getFormValues('frmProveedor'), xajax.getFormValues('frmListaProveedor'));
		}
	}
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_pagar.php"); ?></div>
 	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaCuentasPorPagar">Proveedores<br></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblProveedor');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    	<button type="button" onclick="xajax_exportarProveedor(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>
                    </td>
                </tr>
                </table>
            
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Tipo de Pago:</td>
                	<td>
                        <select id="lstTipoPago" name="lstTipoPago" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Todos ]</option>
                            <option value="no">Contado</option>
                            <option value="si">Crédito</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstEstatusBuscar" name="lstEstatusBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Todos ]</option>
                            <option value="">Inactivo</option>
                            <option selected="selected" value="Activo">Activo</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" name="txtCriterio" id="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                	<td>
                        <button type="submit" name="btnBuscar" id="btnBuscar" value="Buscar" style="cursor:default" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" name="btnLimpiar" id="btnLimpiar" value="Limpiar" style="cursor:default" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaProveedor" name="frmListaProveedor" style="margin:0">
                <div id="divListaProveedor" style="width:100%"></div>
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
                                <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Inactivo</td>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmProveedor" name="frmProveedor" onsubmit="return false;" style="margin:0">
	<div class="pane" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" id="tblProveedor" width="100%">
        <tr>
            <td colspan="2">
            <fieldset><legend class="legend">Datos Proveedor</legend>
                <table border="0" id="tblProveedorContado" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Proveedor:</td>
                    <td id="tdTipoProveedorContadoCredito">
                        <select name="lstTipoProveedor" id="lstTipoProveedor" onchange="xajax_tipoProveedor(this.value);" style="width:99%">
                            <option value="No" selected="selected">Contado</option>
                            <option value="Si">Credito</option>
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
                            <option value="">Inactivo</option>
                            <option value="Activo" selected="selected">Activo</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                    <td colspan="3">
                        <input type="text" id="txtNombreProveedor" name="txtNombreProveedor" size="45"/>
                        <input type="hidden" id="hddIdProveedor" name="hddIdProveedor"/>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span><?php echo $spanProvCxP; ?>:</td>
                    <td width="22%">
                    <div style="float:left">
                    	<input type="text" name="txtCiRif" id="txtCiRif" maxlength="18" size="20" style="text-align:center"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoRIF; ?>"/>
                    </div>
                    </td>
                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span><?php echo $spanNIT; ?>:</td>
                    <td width="22%">
                    <div style="float:left">
                        <input type="text" id="txtNit" name="txtNit" maxlength="18" size="20" style="text-align:center"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoNIT; ?>"/>
                    </div>
                    </td>
                    <td width="12%"></td>
                    <td width="20%"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Pais:</td>
                    <td id="tdlstPais">
                        <select name="lstPais" id="lstPais">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanEstado; ?>:</td>
                    <td><input type="text" id="txtEstado" name="txtEstado" size="20"/></td>
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ciudad:</td>
                    <td><input type="text" id="txtCiudad" name="txtCiudad" size="20"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Direccion:</td>
                    <td colspan="5"><textarea name="txtDireccion" cols="72" rows="2" id="txtDireccion"></textarea></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtTelefono" name="txtTelefono" size="18" style="text-align:center"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                    </div>
                    </td>
                    <td align="right" class="tituloCampo">Otro Teléfono:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtOtroTelf" name="txtOtroTelf" size="18" style="text-align:center"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                    </div>
                    </td>
                    <td align="right" class="tituloCampo">FAX:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtFax" name="txtFax" size="18" style="text-align:center"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Email:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtEmail" name="txtEmail" size="25"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                    </div>
                    </td>
                    <td align="right" class="tituloCampo">Origen:</td>
                    <td>
                        <select name="lstTipoProveedorNacImp" id="lstTipoProveedorNacImp" style="width:99%">
                            <option value="">[ Seleccione ]</option>
                            <option value="Nacional">Nacional</option>
                            <option value="Importado">Importado</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Proveedor de:</td>
                    <td>
                        <select name="lstTipo" id="lstTipo" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="Repuestos">Repuestos</option>
                            <option value="Servicios">Servicios</option>
                            <option value="Vehiculos">Vehiculos</option>
                            <option value="Administracion">Administracion</option>
                            <option value="Todos">Todos</option>
                        </select>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td valign="top" width="38%"> 
            <fieldset><legend class="legend">Datos del Contacto</legend>
                <table border="0" id="tblContactoProveedor" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="30%"><?php echo $spanClienteCxC; ?>:</td>
                    <td width="70%">
                    <div style="float:left">
                        <input type="text" id="txtCedulaContacto" name="txtCedulaContacto" maxlength="18" size="20" style="text-align:center"/>
                    </div>
                    <div style="float:left">
						<img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI.$titleFormatoRIF; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Nombre(s) y Apellido(s):</td>
                    <td><input type="text" id="txtNombreContacto" name="txtNombreContacto" size="25"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Telefono:</td>
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
                    <td align="right" class="tituloCampo">Correo:</td>
                    <td>
                    <div style="float:left">
                        <input type="text" id="txtCorreoContacto" name="txtCorreoContacto" size="30"/>
                    </div>
                    <div style="float:left">
                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                    </div>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
            <td valign="top" width="62%">
            <fieldset><legend class="legend">Datos Adicionales Proveedor</legend>
                <table border="0" id="tblAdicionalProveedor" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="25%">Forma de Pago:</td>
                    <td id="tdlstFormaPago" width="25%">
                        <select name="lstFormaPago" id="lstFormaPago">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="25%">Fecha Creacion:</td>
                    <td width="25%"><input type="text" id="txtFechaCracion" name="txtFechaCracion" readonly="readonly" size="10" style="text-align:center"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Descuento:</td>
                    <td>
                        <select name="lstDescuento" id="lstDescuento" style="width:99%">
                            <option value="0">N/A</option>
                            <option value="5">5%</option>
                            <option value="10">10%</option>
                            <option value="15">15%</option>
                            <option value="20">20%</option>
                            <option value="25">25%</option>
                            <option value="30">30%</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Tipo Moneda:</td>
                    <td id="tdlstMoneda"></td>
                </tr>
                <tr align="left"> 
                    <td align="right" class="tituloCampo">Banco:</td>
                    <td id="tdlstBanco"></td>
                    <td align="right" class="tituloCampo">Sucursal:</td>
                    <td><input type="text" id="txtSucursal" name="txtSucursal" size="25"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo Cuenta:</td>
                    <td>
                        <select name="lstTipoCuenta" id="lstTipoCuenta" style="width:99%">
                            <option value="Corriente">Corriente</option>
                            <option value="Ahorro">Ahorro</option>
                            <option value="Electronica">Electronica</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo">Nro. Cuenta:</td>
                    <td><input type="text" id="txtNumeroCuenta" name="txtNumeroCuenta" size="25"/></td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td colspan="2">
            <fieldset style="display:none" id="divProveedorCredito"><legend class="legend">Datos Proveedor Credito</legend>
                <table border="0" id="tblProveedorCredito" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="12%">Dias Credito:</td>
                    <td width="13%">
                        <select name="lstDiasCredito" id="lstDiasCredito" style="width:99%">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="30">30</option>
                            <option value="60">60</option>
                            <option value="90">90</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Limite Credito</td>
                    <td width="13%"><input type="text" id="txtLimiteCredito" name="txtLimiteCredito" onblur="setFormatoRafk(this,2);" size="15" style="text-align:right;"/></td>         
                    <td align="right" class="tituloCampo" width="12%">Plan Mayor:</td>
                    <td width="13%">
                        <select name="lstPlanMayor" id="lstPlanMayor" style="width:99%">
                            <option value="0">No</option>
                            <option value="1">Si</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="12%">Retencion:</td>
                    <td id="tdlstRetencion" width="13%">
                        <select id="lstRetencion" name="lstRetencion">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="center" class="tituloArea" colspan="8">Documentos Requeridos</td>
                </tr>
                <tr>
                    <td colspan="8">
                        <table border="0" width="100%">
                        <tr align="left">
                            <td width="50%"><label><input name="d1" type="checkbox" id="d1" value="1"/>Fotocopia del Registro Mecantil. (Ultima Publicación)</label></td>
                            <td width="50%"><label><input name="d2" type="checkbox" id="d2" value="1"/> Fotocopia del <?php echo $spanRIF; ?></label></td>
                        </tr>
                        <tr align="left">
                            <td><label><input name="d3" type="checkbox" id="d3" value="1"/>Original de la carta de autorizacion de un representante legal. (Si es el Caso)</label></td>
                            <td><label><input name="d4" type="checkbox" id="d4" value="1"/> Original de referencias comerciales o de clientes, para persona juridica. (Por lo Menos Una)</label></td>
                        </tr>
                        <tr align="left">
                            <td><label><input name="d5" type="checkbox" id="d5" value="1"/> Original de referencia bancaria para personas juridicas. (Por lo Menos Una)</label></td>
                            <td><label><input name="d6" type="checkbox" id="d6" value="1"/> Original de la carta de autorizacion de abono en cuenta. (Si el Pago es Abono en Cuenta)</label></td>
                        </tr>
                        <tr align="left">
                            <td><label><input name="d7" type="checkbox" id="d7" value="1"/> Ultima declaracion del I.S.R.L, para persona juridica.</label></td>
                            <td><label><input name="d8" type="checkbox" id="d8" value="1"/> Original de la carta de justificación técnica (Especificaciones Técnicas) y económica (No sea Monopolio, Precios Competitivos)</label></td>
                        </tr>
                        <tr align="left">
                            <td colspan="4"><label><input name="d9" type="checkbox" id="d9" value="1"/> Fianza fiel cumplimiento emitido por un Banco o Compañia Aseguradora o Afianzadora y Estados Financieros visados por un contador público.(para aquellos proveedores nuevos que reciban anticipos o que por la importancia se requiera, de acuerdo a lo establecido en la norma H de este procedimiento)</label></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="16%">Observaciones:</td>
                    <td width="84%"><textarea id="txtObservaciones" name="txtObservaciones" style="width:99%"></textarea></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right" colspan="2"><hr>
                <button type="submit" id="btnGuardarProveedor" name="btnGuardarProveedor" onclick="validarFrmProveedor();">Guardar</button>      
                <button type="button" id="btnCancelarProveedor" name="btnCancelarProveedor" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<script>
byId('lstTipoPago').className = 'inputHabilitado';
byId('lstEstatusBuscar').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

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

xajax_listaProveedor(0, 'id_proveedor', 'DESC', '|' + byId('lstTipoPago').value + '|' + byId('lstEstatusBuscar').value);

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>