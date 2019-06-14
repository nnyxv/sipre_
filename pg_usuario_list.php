<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_usuario_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_pg_usuario_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Usuarios</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png"/>
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>

    <link rel="stylesheet" type="text/css" media="all" href="js/calendar-green.css"/>
    <script type="text/javascript" language="javascript" src="js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="js/calendar-setup.js"></script>
    
    <link rel="stylesheet" type="text/css" href="js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="js/jquerytools/tabs-panes.css"/>

    <script language="javascript">

    	function enviarfrmPrivilegio(){
    		var usuarioObjetivo = byId('hddIdUsuarioObj').value;
    		var usuarioCopiar = byId('hddIdUsuarioCopy').value;
    		var eliminarPrivilegios =document.frmPrivilegio.cbxEliminarPrivilegios.checked;
    		xajax_formPrivilegios(usuarioObjetivo,usuarioCopiar,eliminarPrivilegios);
	}

	function abrirDivFlotante4(nomObjeto, verTabla, valor) {
		byId('tblListaEmpleado').style.display = 'none';
		
		if (verTabla == "tblListaEmpleado") {
			document.forms['frmBuscarEmpleado'].reset();
			byId('hddObjDestino').value = '';
			
			byId('txtCriterioBuscarEmpleado').className = 'inputHabilitado';
			
			byId('btnBuscarEmpleado').click();
				
			tituloDiv4 = 'Empleados';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo4').innerHTML = tituloDiv4;
		
		if (verTabla == "tblListaEmpleado") {
			byId('txtCriterioBuscarEmpleado').focus();
			byId('txtCriterioBuscarEmpleado').select();
		}
	}

	function abrirDivFlotante3(nomObjeto, verTabla, valor) {		
		byId('tblPrivilegio').style.display = 'none';

		if (verTabla == "tblPrivilegio") {
			document.forms['frmPrivilegio'].reset();
			
			byId('hddIdEmpleadoCopy').className = 'inputHabilitado';
			
			xajax_cargarEmpleadoPrivilegios(valor);

			if (valor > 0) {
				byId('hddIdEmpleadoObj').className = 'inputInicial';
				byId('aListarEmpleado').style.display = '';

				byId('hddIdEmpleadoObj').readOnly = true;

				tituloDiv3 = 'Copiar Privilegios de Usuario';
			} 
		}

		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo3').innerHTML = tituloDiv3;
	}

	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		tblUsuario = (byId('tblUsuario').style.display == '') ? '' : 'none';
		
		byId('tblUsuario').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante11') != undefined) {
			byId('imgCerrarDivFlotante11').onclick = function () {
				byId('tblUsuario').style.display = tblUsuario;
				
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
		
		if (verTabla == "tblUsuario") {
			document.forms['frmUsuario'].reset();
			byId('hddIdUsuario').value = '';
			
			byId('txtUsuario').className = 'inputHabilitado';
			byId('txtContrasena').className = 'inputHabilitado';
			byId('lstEmpresa').className = 'inputHabilitado';
			byId('lstEmpleado').className = 'inputHabilitado';
			byId('lstPerfil').className = 'inputHabilitado';
			
			xajax_formUsuario(valor, xajax.getFormValues('frmUsuario'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Usuario';
			} else {
				tituloDiv1 = 'Agregar Usuario';
			}
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblDesbloqueoVenta") {
			byId('txtCriterioDesbloqueoVenta').focus();
			byId('txtCriterioDesbloqueoVenta').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		tblConfiguracion = (byId('tblConfiguracion').style.display == '') ? '' : 'none';
		tblListaEmpresa = (byId('tblListaEmpresa').style.display == '') ? '' : 'none';
		
		byId('tblConfiguracion').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
			
		if (byId('imgCerrarDivFlotante21') != undefined) {
			byId('imgCerrarDivFlotante21').onclick = function () {
				byId('tblConfiguracion').style.display = tblConfiguracion;
				byId('tblListaEmpresa').style.display = tblListaEmpresa;
				
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
		
		if (verTabla == "tblConfiguracion") {
			document.forms['frmConfiguracion'].reset();
			byId('hddIdConfigUsuario').value = '';
			byId('txtValor').innerHTML = '';
			
			byId('lstConfiguracion').className = 'inputHabilitado';
			byId('txtValor').className = 'inputHabilitado';
			
			xajax_formConfiguracion(valor, xajax.getFormValues('frmUsuario'));
			
			if (valor > 0) {
				byId('txtIdEmpresa').className = 'inputInicial';
				
				byId('txtIdEmpresa').readOnly = true;
				byId('aListarEmpresa').style.display = 'none';
				
				tituloDiv2 = 'Editar Configuración';
			} else {
				byId('txtIdEmpresa').className = 'inputHabilitado';
				
				byId('txtIdEmpresa').readOnly = false;
				byId('aListarEmpresa').style.display = '';
				
				tituloDiv2 = 'Agregar Configuración';
			}
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv2 = 'Empresas';
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		}
	}

	function validarDesbloquear(idUsuario) {
		if (confirm('¿Desea desbloquear el registro seleccionado?') == true) {
			xajax_desbloquearSesion(idUsuario, xajax.getFormValues('frmListaUsuario'));
		}
	}

	function validarEliminar(idUsuario) {
		if (confirm('¿Desea eliminar los registros seleccionado(s)?') == true) {
			window.location = "pg_mantenimiento_usuario_eliminar.php?id="+idUsuario;
		}
	}
	
	function validarFrmConfiguracion() {
		if (validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('lstConfiguracion','t','lista') == true
		&& validarCampo('txtValor','t','') == true) {
			xajax_insertarConfiguracion(xajax.getFormValues('frmConfiguracion'), xajax.getFormValues('frmUsuario'));
		} else {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('lstModulo','t','listaExceptCero');
			validarCampo('lstConfiguracion','t','lista');
			validarCampo('txtValor','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmUsuario() {
		if (validarCampo('txtUsuario','t','') == true
		&& validarCampo('txtContrasena','t','') == true
		&& validarCampo('lstEmpresa','t','lista') == true
		&& validarCampo('lstEmpleado','t','lista') == true
		&& validarCampo('lstPerfil','t','lista') == true) {
			xajax_guardarUsuario(xajax.getFormValues('frmUsuario'), xajax.getFormValues('frmListaUsuario'));
		} else {
			validarCampo('txtUsuario','t','');
			validarCampo('txtContrasena','t','');
			validarCampo('lstEmpresa','t','lista');
			validarCampo('lstEmpleado','t','lista');
			validarCampo('lstPerfil','t','lista');

			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarInsertarEmpresa(idEmpresa) {
		// BLOQUEA LOS BOTONES DEL LISTADO
		for (cont = 1; cont <= 20; cont++) {
			if (!(byId('btnInsertarEmpresa' + cont) == undefined)) {
				byId('btnInsertarEmpresa' + cont).disabled = true;
			}
		}
		xajax_insertarEmpresa(idEmpresa, xajax.getFormValues('frmUsuario'));
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>

    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaErp">Usuarios</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblUsuario');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    	<button type="button" onclick="xajax_exportarUsuarios(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
					</td>
                </tr>
                </table>

			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstEstatusBuscar" name="lstEstatusBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Todos ]</option>
                            <option value="1" selected="selected">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Modo del Cargo:</td>
                    <td>
                        <select id="lstUnipersonal" name="lstUnipersonal" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Todos ]</option>
                            <option value="0">Multipersonal</option>
                            <option value="1">Unipersonal</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarUsuario(xajax.getFormValues('frmBuscar'));"/>Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaUsuario" name="frmListaUsuario" style="margin:0">
            	<div id="divListaUsuario" style="width:100%"></div>
            </form>
            </td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	<table>
                        <tr>
							<td><img src="img/iconos/ico_verde.gif"/></td><td>Activo</td>
                            <td>&nbsp;</td>
							<td><img src="img/iconos/ico_rojo.gif"/></td><td>Inactivo</td>
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
                    <td width="25"><img src="img/iconos/ico_info.gif" width="25" class="puntero"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="img/iconos/lock.png"/></td><td>Desbloquear</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/edit_privilegios.png"/></td><td>Editar Privilegios</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/group_link.png"/></td><td>Copiar Privilegios</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/pencil.png"/></td><td>Editar</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/cross.png"/></td><td>Eliminar</td>
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante11" src="img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante12" src="img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>

<form id="frmUsuario" name="frmUsuario" style="margin:0" onsubmit="return false;">
    <div id="tblUsuario" style="max-height:520px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr>
            <td>
                <table border="0" width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                    <td id="tdlstCredito"><input type="text" name="txtUsuario" id="txtUsuario" maxlength="20" size="28"/></td>
                    <td align="right" class="tituloCampo"><span class="textoNegrita_9px">(1)</span><span class="textoRojoNegrita">*</span>Contraseña:</td>
                    <td><input type="password" name="txtContrasena" id="txtContrasena" maxlength="30" size="24"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td id="tdlstEmpresa" width="38%">
                        <select id="lstEmpresa" name="lstEmpresa">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Empleado:</td>
                    <td id="tdlstEmpleado" width="30%">
                        <select id="lstEmpleado" name="lstEmpleado">
                            <option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoNegrita_9px">(2)</span><span class="textoRojoNegrita">*</span>Perfil Precargado:</td>
                    <td>
                        <table>
                        <tr>
                            <td id="tdlstPerfil">
                                <select name="lstPerfil" id="lstPerfil">
                                    <option value="-1">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td>
                                <label><input type="checkbox" id="cbxRecargar" name="cbxRecargar" value="1"/> Recargar Privilegios</label>
                            </td>
                        </tr>
                        </table>
                    </td>
                    <td align="right" class="tituloCampo">Enviar Correo:</td>
                    <td>
                        <label><input type="radio" id="rbtEnviarCorreoSi" name="rbtEnviarCorreo" value="1"/> Si</label>
                        <label><input type="radio" id="rbtEnviarCorreoNo" name="rbtEnviarCorreo" checked="checked" value="0"/> No</label>
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
                        <li><a href="#">Empresas</a></li>
                        <li><a href="#">Parámetro</a></li>
                    </ul>
                    
                    <!-- tab "panes" -->
                    <div class="pane">
                        <table width="100%">
                        <tr>
                            <td align="left">
                                <a class="modalImg" id="aNuevo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa');">
                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                <button id="btnEliminar" name="btnEliminar" onclick="xajax_eliminarEmpresaUsuario(xajax.getFormValues('frmUsuario'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="max-height:250px; overflow:auto; width:100%;">
                                    <table border="0" class="texto_9px" width="100%">
                                    <tr align="center" class="tituloColumna">
                                        <td><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,3);"/></td>
                                        <td width="14%">R.I.F.</td>
                                        <td width="80%">Empresa</td>
                                        <td width="6%">Predet.</td>
                                    </tr>
                                    <tr id="trItmPie"></tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        </table>
                    </div>
                    
                    <div class="pane">
                        <table width="100%">
                        <tr>
                            <td align="left">
                                <a class="modalImg" id="aNuevoConfig" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblConfiguracion');">
                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                <button id="btnEliminarConfig" name="btnEliminarConfig" onclick="xajax_eliminarConfiguracionUsuario(xajax.getFormValues('frmUsuario'));"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="max-height:250px; overflow:auto; width:100%;">
                                    <table border="0" class="texto_9px" width="100%">
                                    <tr align="center" class="tituloColumna">
                                        <td><input type="checkbox" id="cbxItmConfig" onclick="selecAllChecks(this.checked,this.id,3);"/></td>
                                        <td width="30%">Empresa</td>
                                        <td width="16%">Módulo</td>
                                        <td width="30%">Parámetro</td>
                                        <td width="24%">Valor</td>
                                    </tr>
                                    <tr id="trItmPieConfig"></tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="img/iconos/ico_info.gif" width="25"/></td>
                    <td>&nbsp;&nbsp;</td>
                    <td align="left">
                        (1) La clave se filtra mediante MD5 para mayor seguridad, puede ingresar
                        una nueva clave y al guardar se le aplicará el filtro nuevamente.
                        <br><br>
                        (2) Indica el perfil que fué utilizado al crear el usuario, para Recargar
                        los privilegios del perfil al usuario debe marcar la casilla "Recragar
                        privilegios" o cambiar el perfil.
                        <br><br>
                        NOTA: al Recargar los privilegios del usuario por el perfil se eliminarán
                        primero todos los privilegios del usuario para ser reemplazados por los del perfil. 
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <input type="hidden" name="hddIdUsuario" id="hddIdUsuario" readonly="readonly"/>
                <button type="submit" id="btnGuardarUsuario" name="btnGuardarUsuario" onclick="validarFrmUsuario();">Guardar</button>
                <button type="button" id="btnCancelarUsuario" name="btnCancelarUsuario" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="puntero" id="imgCerrarDivFlotante21" src="img/iconos/cross.png" title="Cerrar"/><img class="close puntero" id="imgCerrarDivFlotante22" src="img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmConfiguracion" name="frmConfiguracion" onsubmit="return false;" style="margin:0">
    <table border="0" id="tblConfiguracion" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td width="84%">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(null, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                        	<button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
                <td id="tdlstModulo"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Parámetro</td>
                <td>
                	<table width="100%">
                    <tr>
                    	<td id="tdlstConfiguracion">
                            <select id="lstConfiguracion" name="lstConfiguracion">
                                <option value="-1">[ Seleccione ]</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                    	<td class="textoNegrita_10px" id="tdConfiguracion"></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Valor:</td>
                <td>
                	<textarea id="txtValor" name="txtValor" rows="10" style="width:99%"></textarea>
                    <textarea id="txtValorAntes" name="txtValorAntes" style="display:none; width:99%"></textarea>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdConfigUsuario" name="hddIdConfigUsuario"/>
            <button type="submit" id="btnGuardarConfiguracion" name="btnGuardarConfiguracion" onclick="validarFrmConfiguracion();">Guardar</button>
            <button type="button" id="btnCancelarConfiguracion" name="btnCancelarConfiguracion" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>

    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino"/>
            <input type="hidden" id="hddNomVentana" name="hddNomVentana"/>
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
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante32" src="img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
	
		<form id="frmPrivilegio" name="frmPrivilegio" style="margin:0" onsubmit="return false;">
		<table border="0" id="tblPrivilegio" width="560">		
			<tr>
				<td>
					<table width="100%">
							<tr align="left">
								<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empleado Objetivo:</td>
								<td width="85%">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td><input type="text" id="hddIdEmpleadoObj" name="hddIdEmpleadoObj" onblur="xajax_asignarEmpleado(this.value, 'false');" size="6" style="text-align:right;"/></td>
											
											<td>&nbsp;</td>
											
											<td><input type="text" id="txtNombreEmpleadoObj" name="txtNombreEmpleadoObj" readonly="readonly" size="45"/></td>
											</tr>
									</table>
								</td>
							</tr>
							
							<tr align="left">
								<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Usuario Objetivo:</td>
								
								<td width="75%">
									<table cellpadding="0" cellspacing="0">
									<tr>
										<td><input type="text" id="hddIdUsuarioObj" name="hddIdUsuarioObj" readonly="readonly" size="6" style="text-align:right;"/></td>
										
										<td>&nbsp;</td>
										
										<td><input type="text" id="txtNombreUsuarioObj" name="txtNombreUsuarioObj" readonly="readonly" size="45"/></td>
									</tr>
									</table>
								</td>
							</tr>
					</table>
				</td>
			</tr>
			
			<tr>
				<td>
					<table width="100%">
							<tr align="left">
								<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Copiar de Empleado:</td>
								<td width="85%">
									<table cellpadding="0" cellspacing="0">
										<tr>
											<td><input type="text" id="hddIdEmpleadoCopy" name="hddIdEmpleadoCopy" onblur="xajax_asignarEmpleadoPrivilegios(this.value, 'false');" size="6" style="text-align:right;"/></td>
											
											<td>
												<a class="modalImg" id="aListarEmpleado" rel="#divFlotante4" onclick="abrirDivFlotante4(this, 'tblListaEmpleado')">
													<button type="button" id="btnListarEmpleado" name="btnListarEmpleado" title="Listar"><img src="img/iconos/help.png"/></button>
												</a>
											</td>
											
											<td><input type="text" id="txtNombreEmpleadoCopy" name="txtNombreEmpleadoCopy" readonly="readonly" size="40"/></td>
											</tr>
									</table>
								</td>
							</tr>
							
							<tr align="left">
								<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Copiar de Usuario:</td>
								
								<td width="75%">
									<table cellpadding="0" cellspacing="0">
									<tr>
										<td><input type="text" id="hddIdUsuarioCopy" name="hddIdUsuarioCopy" readonly="readonly" size="6" style="text-align:right;"/></td>
										
										<td>&nbsp;</td>
										
										<td><input type="text" id="txtNombreUsuarioCopy" name="txtNombreUsuarioCopy" readonly="readonly" size="45"/></td>
									</tr>
									</table>
								</td>
							</tr>
					</table>
				</td>
			</tr>			
			
			<tr>
				<td align="right">
					<hr>
					<label><input type="checkbox" id="cbxEliminarPrivilegios" name="cbxEliminarPrivilegios"/>Eliminar Privilegios Anteriores</label>&nbsp;&nbsp;
					<button type="button" id="btnGuardarClaveEspecial" name="btnGuardarClaveEspecial" onclick="enviarfrmPrivilegio();">Guardar</button>
					<button type="button" id="btnCancelarClaveEspecial" name="btnCancelarClaveEspecial" class="close">Cancelar</button>
				</td>
			</tr>
		</table>
	</form>
</div>

<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo4" class="handle"><table><tr><td id="tdFlotanteTitulo4" width="100%"></td></tr></table></div>

	<table border="0" id="tblListaEmpleado" width="760">
		<tr>
			<td>
				<form id="frmBuscarEmpleado" name="frmBuscarEmpleado" style="margin:0" onsubmit="return false;">
					<input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly"/>
					<table align="right">
						<tr align="left">
							<td align="right" class="tituloCampo" width="120">Criterio:</td>
							
							<td><input type="text" id="txtCriterioBuscarEmpleado" name="txtCriterioBuscarEmpleado" onkeyup="byId('btnBuscarEmpleado').click();"/></td>
							
							<td>
								<button type="submit" id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscarEmpleado'));">Buscar</button>
								<button type="button" onclick="document.forms['frmBuscarEmpleado'].reset(); byId('btnBuscarEmpleado').click();">Limpiar</button>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
		
		<tr>
			<td>
				<form id="frmListaEmpleado" name="frmListaEmpleado" style="margin:0" onsubmit="return false;">
					<div id="divListaEmpleado" style="width:100%"></div>
				</form>
			</td>
		</tr>
		
		<tr>
			<td align="right"><hr>
				<button type="button" id="btnCancelarListaEmpleado" name="btnCancelarListaEmpleado" class="close">Cerrar</button>
			</td>
		</tr>
	</table>
</div>

<script language="javascript">
byId('lstEstatusBuscar').className = "inputHabilitado";
byId('lstUnipersonal').className = "inputHabilitado";
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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

byId('btnBuscar').click();

var theHandle1 = document.getElementById("divFlotanteTitulo1");
var theRoot1   = document.getElementById("divFlotante1");
Drag.init(theHandle1, theRoot1);

var theHandle2 = document.getElementById("divFlotanteTitulo2");
var theRoot2   = document.getElementById("divFlotante2");
Drag.init(theHandle2, theRoot2);

var theHandle3 = document.getElementById("divFlotanteTitulo3");
var theRoot3   = document.getElementById("divFlotante3");
Drag.init(theHandle3, theRoot3);

var theHandle4 = document.getElementById("divFlotanteTitulo4");
var theRoot4   = document.getElementById("divFlotante4");
Drag.init(theHandle4, theRoot4);
</script>