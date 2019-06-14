<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_empresa_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_pg_empresa_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Empresas</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/');//indicamos al objeto xajax se encargue de generar javascript necesario ?>

    <link rel="stylesheet" type="text/css" href="style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="js/maskedinput/jquery.maskedinput.js"></script>

	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblEmpresa').style.display = 'none';
		
		if (verTabla == "tblEmpresa") {
			document.forms['frmEmpresa'].reset();
			byId('hddIdEmpresa').value = '';
			
			byId('txtEmpresa').className = "inputHabilitado";
			byId('txtCodigoEmpresa').className = "inputHabilitado";
			byId('txtRif').className = "inputHabilitado";
			byId('txtNit').className = "inputHabilitado";
			byId('txtDireccion').className = "inputHabilitado";
			byId('txtCiudadEmpresa').className = "inputHabilitado";
			byId('txtSucursal').className = "inputHabilitado";
			byId('txtFamiliaEmpresa').className = "inputHabilitado";
			byId('txtFax').className = "inputHabilitado";
			byId('txtTelefono1').className = "inputHabilitado";
			byId('txtTelefono2').className = "inputHabilitado";
			byId('txtTelefono3').className = "inputHabilitado";
			byId('txtTelefono4').className = "inputHabilitado";
			byId('txtCorreo').className = "inputHabilitado";
			byId('txtWeb').className = "inputHabilitado";
			byId('txtContribuyente').className = "inputHabilitado";
			byId('txtPaqCombo').className = "inputHabilitado";
			
			byId('txtNombreTaller').className = "inputHabilitado";
			byId('txtDireccionTaller').className = "inputHabilitado";
			byId('txtContactosTaller').className = "inputHabilitado";
			byId('txtFaxTaller').className = "inputHabilitado";
			byId('txtTelefonoTaller1').className = "inputHabilitado";
			byId('txtTelefonoTaller2').className = "inputHabilitado";
			byId('txtTelefonoTaller3').className = "inputHabilitado";
			byId('txtTelefonoTaller4').className = "inputHabilitado";
			
			byId('txtNombreAsistencia').className = "inputHabilitado";
			byId('txtTelefonoAsistencia').className = "inputInicial";
			byId('txtTelefonoServicio').className = "inputHabilitado";
			
			byId('txtFormatoCodigoRepuestos').className = "inputHabilitado";
			byId('txtFormatoCodigoCompras').className = "inputHabilitado";
			
			byId('imgGrupo').className = "inputInicial";
			byId('hddUrlImgGrupo').className = "inputInicial";
			byId('imgEmpresa').className = "inputInicial";
			byId('hddUrlImgEmpresa').className = "inputInicial";
			byId('imgFirmaAdmon').className = "inputInicial";
			byId('hddUrlImgFirmaAdmon').className = "inputInicial";
			byId('imgFirmaTesoreria').className = "inputInicial";
			byId('hddUrlImgFirmaTesoreria').className = "inputInicial";
			byId('imgFirmaSello').className = "inputInicial";
			byId('hddUrlImgFirmaSello').className = "inputInicial";
			byId('imgGrupo').src = '';
			byId('imgEmpresa').src = '';
			byId('imgFirmaAdmon').src = '';
			byId('imgFirmaTesoreria').src = '';
			byId('imgFirmaSello').src = '';
			
			xajax_formEmpresa(valor, xajax.getFormValues('frmEmpresa'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Empresa';
			} else {
				tituloDiv1 = 'Agregar Empresa';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblEmpresa") {
			byId('txtEmpresa').focus();
			byId('txtEmpresa').select();
		}
	}
	
	function validarFrmEmpresa(){
		if (validarCampo('txtEmpresa','t','') == true
		&& validarCampo('txtCodigoEmpresa','t','') == true
		&& validarCampo('txtFamiliaEmpresa','t','') == true
		&& validarCampo('txtContribuyente','t','listaExceptCero') == true
		&& validarCampo('txtCorreo','','email') == true
		&& validarCampo('txtDireccion','t','') == true
		&& validarCampo('txtSucursal','t','') == true
		&& validarCampo('txtPaqCombo','t','listaExceptCero') == true
		&& validarCampo('txtCiudadEmpresa','t','') == true
		&& validarCampo('txtTelefono1','t','telefono') == true
		&& validarCampo('txtTelefono2','','telefono') == true
		&& validarCampo('txtTelefono3','','telefono') == true
		&& validarCampo('txtTelefono4','','telefono') == true
		&& validarCampo('txtNombreTaller','t','') == true
		&& validarCampo('txtDireccionTaller','t','') == true
		&& validarCampo('txtContactosTaller','t','') == true
		&& validarCampo('txtTelefonoTaller1','t','telefono') == true
		&& validarCampo('txtTelefonoTaller2','','telefono') == true
		&& validarCampo('txtTelefonoTaller3','','telefono') == true
		&& validarCampo('txtTelefonoTaller4','','telefono') == true
		&& validarCampo('txtNombreAsistencia','t','') == true
		&& validarCampo('txtTelefonoAsistencia','t','') == true
		&& validarCampo('txtTelefonoServicio','t','telefono') == true
		&& validarCampo('txtFormatoCodigoRepuestos','t','') == true
		&& validarCampo('txtFormatoCodigoCompras','t','') == true) {
			byId('btnGuardarEmpresa').disabled = true;
			byId('btnCancelarEmpresa').disabled = true;;
			xajax_guardarEmpresa(xajax.getFormValues('frmEmpresa'), xajax.getFormValues('frmListaEmpresa'));
		} else {
			validarCampo('txtEmpresa','t','')
			validarCampo('txtCodigoEmpresa','t','');
			validarCampo('txtFamiliaEmpresa','t','');
			validarCampo('txtContribuyente','t','listaExceptCero');
			validarCampo('txtCorreo','','email');
			validarCampo('txtDireccion','t','');
			validarCampo('txtSucursal','t','');
			validarCampo('txtPaqCombo','t','listaExceptCero');
			validarCampo('txtCiudadEmpresa','t','');
			validarCampo('txtTelefono1','t','telefono');
			validarCampo('txtTelefono2','','telefono');
			validarCampo('txtTelefono3','','telefono');
			validarCampo('txtTelefono4','','telefono');
			validarCampo('txtNombreTaller','t','');
			validarCampo('txtDireccionTaller','t','');
			validarCampo('txtContactosTaller','t','');
			validarCampo('txtTelefonoTaller1','t','telefono');
			validarCampo('txtTelefonoTaller2','','telefono');
			validarCampo('txtTelefonoTaller3','','telefono');
			validarCampo('txtTelefonoTaller4','','telefono');
			validarCampo('txtNombreAsistencia','t','');
			validarCampo('txtTelefonoAsistencia','t','');
			validarCampo('txtTelefonoServicio','t','telefono');
			validarCampo('txtFormatoCodigoRepuestos','t','');
			validarCampo('txtFormatoCodigoCompras','t','');

			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		}
	}
	
	function validarEliminar(idEmpresa){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarEmpresa(idEmpresa, xajax.getFormValues('frmListaEmpresa'));
		}
	}
    </script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
        <tr>
            <td class="tituloPaginaErp" colspan="2">Empresas</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblEmpresa');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Nuevo"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
            
            <form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"  onkeyup="byId('btnBuscar').click();" size="20"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            <form id="frmListaEmpresa" name="frmListaEmpresa" style="margin:0">
            	<div id="divListaEmpresa" style="width:100%"></div>
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
    
<form  action="controladores/ac_upload_file_empresa.php" enctype="multipart/form-data" method="post" id="frmEmpresa" name="frmEmpresa" target="iframeUpload">
	<div class="pane" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" id="tblEmpresa" width="100%">
        <tr>
            <td>
                <table border="0" width="100%">
                <tr>
                    <td colspan="2">
                    <fieldset><legend class="legend">Datos de la Empresa</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                        	<td align="right" class="tituloCampo">Empresa Principal:</td>
                            <td id="tdlstEmpresaPpal"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="35%"><input type="text" id="txtEmpresa" name="txtEmpresa" size="30"/></td>
                            <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>C&oacute;digo:</td>
                            <td width="35%"><input type="text" id="txtCodigoEmpresa" name="txtCodigoEmpresa" size="20"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanRIF; ?>:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtRif" name="txtRif" maxlength="18" size="20" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoRIF; ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanNIT; ?>:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtNit" name="txtNit" maxlength="18" size="20" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoNIT; ?>"/>
                            </div></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="2"><span class="textoRojoNegrita">*</span>Direcci&oacute;n:</td>
                            <td rowspan="2"><textarea name="txtDireccion" cols="30" rows="2" id="txtDireccion"></textarea></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ciudad:</td>
                            <td><input type="text" id="txtCiudadEmpresa" name="txtCiudadEmpresa" size="20"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Sucursal:</td>
                            <td><input type="text" id="txtSucursal" name="txtSucursal" size="20"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Grupo:</td>
                            <td><input type="text" id="txtFamiliaEmpresa" name="txtFamiliaEmpresa" size="20"/></td>
                            <td align="right" class="tituloCampo">Fax:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtFax" name="txtFax" size="12" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tel&eacute;fono #1:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtTelefono1" name="txtTelefono1" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo">Tel&eacute;fono #2:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtTelefono2" name="txtTelefono2" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Tel&eacute;fono #3:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtTelefono3" name="txtTelefono3" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo">Tel&eacute;fono #4:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtTelefono4" name="txtTelefono4" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Correo:</td>
                            <td><input type="text" id="txtCorreo" name="txtCorreo" size="30"/></td>
    
                            <td align="right" class="tituloCampo">Web:</td>
                            <td><input type="text" id="txtWeb" name="txtWeb" size="30"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Contribuyente Especial:</td>
                            <td>
                                <select id="txtContribuyente" name="txtContribuyente">
                                    <option value="-1">[Seleccione]</option>
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Paquete por Combo:</td>
                            <td>
                                <select id="txtPaqCombo" name="txtPaqCombo">
                                    <option value="-1">[Seleccione]</option>
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Datos del Taller</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Taller:</td>
                            <td colspan="3"><input type="text" id="txtNombreTaller" name="txtNombreTaller" size="30"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="2"><span class="textoRojoNegrita">*</span>Direcci&oacute;n:</td>
                            <td rowspan="2"><textarea name="txtDireccionTaller" cols="20" rows="2" id="txtDireccionTaller"></textarea></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Contacto:</td>
                            <td><input type="text" id="txtContactosTaller" name="txtContactosTaller" size="20"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Fax:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtFaxTaller" name="txtFaxTaller" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="18%"><span class="textoRojoNegrita">*</span>Tel&eacute;fono #1:</td>
                            <td width="32%">
                            <div style="float:left">
                            	<input type="text" id="txtTelefonoTaller1" name="txtTelefonoTaller1" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo" width="18%">Tel&eacute;fono #2:</td>
                            <td width="32%">
                            <div style="float:left">
                            	<input type="text" id="txtTelefonoTaller2" name="txtTelefonoTaller2" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Tel&eacute;fono #3:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtTelefonoTaller3" name="txtTelefonoTaller3" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo">Tel&eacute;fono #4:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtTelefonoTaller4" name="txtTelefonoTaller4" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top">
                    <fieldset><legend class="legend">Datos de Asistencia</legend>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td width="55%"><input type="text" id="txtNombreAsistencia" name="txtNombreAsistencia" size="20"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tel&eacute;fono Asistencia:</td>
                            <td><input type="text" id="txtTelefonoAsistencia" name="txtTelefonoAsistencia" size="20" onkeypress="return validarTelefono(event);"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tel&eacute;fono Servicio:</td>
                            <td>
                            <div style="float:left">
                            	<input type="text" id="txtTelefonoServicio" name="txtTelefonoServicio" size="18" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                            </div>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    <fieldset><legend class="legend">Configuración de Formato</legend>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>Cód. Repuestos:</td>
                            <td width="55%">
                            <div style="float:left">
                                <input type="text" id="txtFormatoCodigoRepuestos" name="txtFormatoCodigoRepuestos" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: Long. Caract.-Long. Caract.-Long. Caract.-{n}"/>
                            </div>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cód. Compras:</td>
                            <td>
                            <div style="float:left">
                                <input type="text" id="txtFormatoCodigoCompras" name="txtFormatoCodigoCompras" style="text-align:center"/>
                            </div>
                            <div style="float:left">
                                <img src="img/iconos/information.png" title="Formato Ej.: Long. Caract.-Long. Caract.-Long. Caract.-{n}"/>
                            </div>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <fieldset><legend class="legend">Imágenes</legend>
	                    <input type="hidden" id="accTipoImg" name="accTipoImg"/>
                        <iframe name="iframeUpload" style="display:none"></iframe>
                        <table border="0" width="100%">
                        <tr>
                        	<td>
                            	<table border="0" width="100%">
                                <tr align="left">
                                    <td class="tituloCampo" width="50%">Logo Grupo:</td>
                                    <td class="tituloCampo" width="50%">Logo Empresa:</td>
                                </tr>
                                <tr>
                                    <td align="center" class="imgBorde" colspan="1"><img id="imgGrupo" src="" width="150"/></td>
                                    <td align="center" class="imgBorde" colspan="1"><img id="imgEmpresa" src="" width="150"/></td>
                                </tr>
                                <tr>
                                    <td align="left">
                                        <input type="file" id="fleUrlGrupo" name="fleUrlGrupo" onchange="byId('accTipoImg').value = 1; javascript: submit();"/>
                                        <input type="hidden" id="hddUrlImgGrupo" name="hddUrlImgGrupo"/></td>
                                    <td align="left">
                                        <input type="file" id="fleUrlEmpresa" name="fleUrlEmpresa" onchange="byId('accTipoImg').value = 2; javascript: submit();"/>
                                        <input type="hidden" id="hddUrlImgEmpresa" name="hddUrlImgEmpresa"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                        	<td>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td class="tituloCampo" width="33%">Firma Gte. Admon:</td>
                                    <td class="tituloCampo" width="34%">Firma Gte. Tesoreria:</td>
                                    <td class="tituloCampo" width="33%">Firma y Sello Tesoreria:</td>
                                </tr>
                                <tr>
                                    <td align="center" class="imgBorde" colspan="1"><img id="imgFirmaAdmon" src="" width="150"/></td>
                                    <td align="center" class="imgBorde" colspan="1"><img id="imgFirmaTesoreria" src="" width="150"/></td>
                                    <td align="center" class="imgBorde" colspan="1"><img id="imgFirmaSello" src="" width="150"/></td>
                                </tr>
                                <tr>
                                    <td align="left">
                                        <input type="file" id="fleUrlFirmaAdmon" name="fleUrlFirmaAdmon" onchange="byId('accTipoImg').value = 3; javascript: submit();"/>
                                        <input type="hidden" id="hddUrlImgFirmaAdmon" name="hddUrlImgFirmaAdmon"/></td>
                                    <td align="left">
                                        <input type="file" id="fleUrlFirmaTesoreria" name="fleUrlFirmaTesoreria" onchange="byId('accTipoImg').value = 4; javascript: submit();"/>
                                        <input type="hidden" id="hddUrlImgFirmaTesoreria" name="hddUrlImgFirmaTesoreria"/></td>
                                    <td align="left">
                                        <input type="file" id="fleUrlFirmaSello" name="fleUrlFirmaSello" onchange="byId('accTipoImg').value = 5; javascript: submit();"/>
                                        <input type="hidden" id="hddUrlImgFirmaSello" name="hddUrlImgFirmaSello"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td align="right" colspan="2"><hr>
                        <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/>
                        <button type="button" id="btnGuardarEmpresa" name="btnGuardarEmpresa" onclick="validarFrmEmpresa();">Guardar</button>
                        <button type="button" id="btnCancelarEmpresa" name="btnCancelarEmpresa" class="close">Cancelar</button>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>
<script>
byId('txtCriterio').className = "inputHabilitado";

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

xajax_listaEmpresa(0,'id_empresa_padre','ASC','');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>