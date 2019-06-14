<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_clave_especial_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_pg_clave_especial_list.php");

//$xajax->setFlag('debug', true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. Claves Especiales</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="style/styleRafk.css">
	
	<link rel="stylesheet" type="text/css" href="js/domDragErp.css"/>
	<script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="js/maskedinput/jquery.maskedinput.js"></script>
	
	<script>
	/*------------------------------------------MODIFICACIONES----------------------------------------*/
	function enviarfrmCopiarClaveUsuario(){
		var empleadoObjetivo = byId('hddIdEmpleadoObjetivo').value;
		var empleadoCopiar = byId('hddIdEmpleadoCopiar').value;
		var usuarioObjetivo = byId('hddIdUsuarioObjetivo').value;
		var usuarioCopiar = byId('hddIdUsuarioCopiar').value;
		var claveUsuario = byId('txtContrasenaObjetivo').value;
		/*alert(empleadoObjetivo);
		alert(empleadoCopiar);
		alert(claveUsuario);*/
		xajax_formCopiarClaveUsuario(empleadoObjetivo,empleadoCopiar,claveUsuario,usuarioObjetivo,usuarioCopiar);
	}
	function abrirDivFlotante3(nomObjeto, verTabla) {
		byId('tblCopiarClaveUsuario').style.display = 'none';
		
		if (verTabla == "tblCopiarClaveUsuario") {
			document.forms['frmCopiarClaveUsuario'].reset();
			byId('hddIdClaveUsuarioObjetivo').value = '';

			byId('hddIdUsuarioObjetivo').className = 'inputInicial';
			byId('txtContrasenaObjetivo').className = 'inputInicial';

			byId('txtContrasenaObjetivo').readOnly = true;

			byId('hddIdEmpleadoObjetivo').className = 'inputHabilitado';
			byId('hddIdEmpleadoCopiar').className = 'inputHabilitado';
			byId('aListarEmpleadoObjetivo').style.display = '';

			byId('hddIdEmpleadoObjetivo').readOnly = false;

			tituloDiv3 = 'Copiar Claves Especiales';
		}

		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo3').innerHTML = tituloDiv3;

		if (verTabla == "tblCopiarClaveUsuario") {
			byId('txtContrasenaObjetivo').focus();
			byId('txtContrasenaObjetivo').select();
		}
	}

	function abrirDivFlotante4(nomObjeto, verTabla, valor) {
		byId('tblListaEmpleadoCopiarClave').style.display = 'none';
		
		if (verTabla == "tblListaEmpleadoCopiarClave") {
			document.forms['frmBuscarEmpleadoCopiarClaves'].reset();
			byId('hddObjDestino').value = '';
			byId('hddTarget').value=valor;
			
			byId('txtCriterioBuscarEmpleadoCopiarClaves').className = 'inputHabilitado';
			
			byId('btnBuscarEmpleadoCopiarClaves').click();
				
			tituloDiv4 = 'Empleados';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo4').innerHTML = tituloDiv4;
		
		if (verTabla == "tblListaEmpleadoCopiarClave") {
			byId('txtCriterioBuscarEmpleadoCopiarClaves').focus();
			byId('txtCriterioBuscarEmpleadoCopiarClaves').select();
		}
	}
	/*------------------------------------------------------------------------------------------------*/
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblClaveUsuario').style.display = 'none';
		
		if (verTabla == "tblClaveUsuario") {
			document.forms['frmClaveUsuario'].reset();
			byId('hddIdClaveUsuario').value = '';
			
			byId('hddIdUsuario').className = 'inputInicial';
			byId('txtContrasena').className = 'inputInicial';
			
			byId('txtContrasena').readOnly = true;
			
			byId('trlstEmpresa').style.display = 'none';
			
			xajax_formClaveUsuario(valor, valor2);
			
			if (valor > 0) {
				byId('hddIdEmpleado').className = 'inputInicial';
				byId('aListarEmpleado').style.display = 'none';
				
				byId('hddIdEmpleado').readOnly = true;
				
				tituloDiv1 = 'Editar Clave Especial';
			} else {
				byId('hddIdEmpleado').className = 'inputHabilitado';
				byId('aListarEmpleado').style.display = '';
				
				byId('hddIdEmpleado').readOnly = false;
				
				tituloDiv1 = 'Agregar Clave Especial';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblClaveUsuario") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor) {
		byId('tblListaEmpleado').style.display = 'none';
		
		if (verTabla == "tblListaEmpleado") {
			document.forms['frmBuscarEmpleado'].reset();
			byId('hddObjDestino').value = '';
			
			byId('txtCriterioBuscarEmpleado').className = 'inputHabilitado';
			
			byId('btnBuscarEmpleado').click();
				
			tituloDiv2 = 'Empleados';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaEmpleado") {
			byId('txtCriterioBuscarEmpleado').focus();
			byId('txtCriterioBuscarEmpleado').select();
		}
	}
	
	function validarFrmClaveUsuario() {
		error = false;
		if (!(validarCampo('hddIdEmpleado', 't', '') == true
		&& validarCampo('hddIdUsuario', 't', '') == true
		&& validarCampo('lstModuloClave', 't', 'lista') == true
		&& validarCampo('txtContrasena', 't', '') == true)) {
			validarCampo('hddIdEmpleado', 't', '');
			validarCampo('hddIdUsuario', 't', '');
			validarCampo('lstModuloClave', 't', 'lista');
			validarCampo('txtContrasena', 't', '');
			
			error = true;
		}
		
		if (byId('lstModulo').value == "1") {
			if (!(validarCampo('lstEmpresa', 't', 'lista') == true)) {
				validarCampo('lstEmpresa', 't', 'lista');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			byId('btnGuardarClaveEspecial').disabled = true;
			byId('btnCancelarClaveEspecial').disabled = true;
			xajax_guardarClaveUsuario(xajax.getFormValues('frmClaveUsuario'), xajax.getFormValues('frmListaClaveUsuario'));
		}
	}
	
	function validarEliminar(idClaveUsuario, idModulo){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarClaveUsuario(idClaveUsuario, idModulo, xajax.getFormValues('frmListaClaveUsuario'));
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
        	<td class="tituloPaginaErp">Claves Especiales</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblClaveUsuario');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>

                    <td>
                    <a class="modalImg" id="aCopiarClaves" rel="#divFlotante3" onclick="abrirDivFlotante3(this, 'tblCopiarClaveUsuario');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img class="puntero" src="img/iconos/group_link.png" title="Copiar Claves Especiales"/></td><td>&nbsp;</td><td>Copiado de Claves</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModuloBuscar"></td>
                	<td align="right" class="tituloCampo" width="120">Acción:</td>
                    <td id="tdlstModuloClaveBuscar"></td>
				</tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarClaveUsuario(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaClaveUsuario" name="frmListaClaveUsuario" style="margin:0">
            	<div id="divListaClaveUsuario" style="width:100%"></div>
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
        </table>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>
<!--  --------------------------------------------------------------------------------- -->
<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td></tr></table></div>

	<form id="frmCopiarClaveUsuario" name="frmCopiarClaveUsuario" style="margin:0" onsubmit="return false;">
		<table border="0" id="tblCopiarClaveUsuario" width="560">
			<tr>
				<td>
					<table width="100%">
						<tr align="left">
							<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empleado Objetivo:</td>
							<td width="85%">
								<table cellpadding="0" cellspacing="0">
									<tr>
										<td><input type="text" id="hddIdEmpleadoObjetivo" name="hddIdEmpleadoObjetivo" onblur="xajax_asignarEmpleadoCopiarClave(this.value, 'false',1);" size="6" style="text-align:right;"/></td>
										<td>
											<a class="modalImg" id="aListarEmpleadoObjetivo" rel="#divFlotante4" onclick="abrirDivFlotante4(this, 'tblListaEmpleadoCopiarClave',1)">
												<button type="button" id="btnListarEmpleadoObjetivo" name="btnListarEmpleadoObjetivo" title="Listar"><img src="img/iconos/help.png"/></button>
											</a>
										</td>
										<td><input type="text" id="txtNombreEmpleadoObjetivo" name="txtNombreEmpleadoObjetivo" readonly="readonly" size="45"/></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr align="left">
							<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Usuario Objetivo:</td>
							<td width="75%">
								<table cellpadding="0" cellspacing="0">
									<tr>
										<td><input type="text" id="hddIdUsuarioObjetivo" name="hddIdUsuarioObjetivo" readonly="readonly" size="6" style="text-align:right;"/></td>
										<td>&nbsp;</td>
										<td><input type="text" id="txtNombreUsuarioObjetivo" name="txtNombreUsuarioObjetivo" readonly="readonly" size="50"/></td>
									</tr>
								</table>
							</td>
						</tr>
						
						
						<tr align="left">
							<td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empleado A Copiar:</td>
							<td width="85%">
								<table cellpadding="0" cellspacing="0">
									<tr>
										<td><input type="text" id="hddIdEmpleadoCopiar" name="hddIdEmpleadoCopiar" onblur="xajax_asignarEmpleadoCopiarClave(this.value, 'false',2);" size="6" style="text-align:right;"/></td>
										<td>
											<a class="modalImg" id="aListarEmpleadoCopiar" rel="#divFlotante4" onclick="abrirDivFlotante4(this, 'tblListaEmpleadoCopiarClave',2)">
												<button type="button" id="btnListarEmpleadoCopiar" name="btnListarEmpleadoCopiar" title="Listar"><img src="img/iconos/help.png"/></button>
											</a>
										</td>
										<td><input type="text" id="txtNombreEmpleadoCopiar" name="txtNombreEmpleadoCopiar" readonly="readonly" size="45"/></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr align="left">
							<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Usuario A Copiar:</td>
							<td width="75%">
								<table cellpadding="0" cellspacing="0">
									<tr>
										<td><input type="text" id="hddIdUsuarioCopiar" name="hddIdUsuarioCopiar" readonly="readonly" size="6" style="text-align:right;"/></td>
										<td>&nbsp;</td>
										<td><input type="text" id="txtNombreUsuarioCopiar" name="txtNombreUsuarioCopiar" readonly="readonly" size="50"/></td>
									</tr>
								</table>
							</td>
						</tr>
						

						<tr align="left">
							<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Contraseña:</td>
							<td><input type="text" id="txtContrasenaObjetivo" name="txtContrasenaObjetivo" size="20"/></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align="right"><hr>
					<input type="hidden" id="hddIdClaveUsuarioObjetivo" name="hddIdClaveUsuarioObjetivo"/>
					<button type="submit" id="btnGuardarClaveEspecialObjetivo" name="btnGuardarClaveEspecialObjetivo" onclick="enviarfrmCopiarClaveUsuario();">Guardar</button>
					<button type="button" id="btnCancelarClaveEspecialObjetivo" name="btnCancelarClaveEspecialObjetivo" class="close">Cancelar</button>
				</td>
			</tr>
		</table>
	</form>
</div>

<!--  --------------------------------------------------------------------------------- -->
<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo4" class="handle"><table><tr><td id="tdFlotanteTitulo4" width="100%"></td></tr></table></div>

	<table border="0" id="tblListaEmpleadoCopiarClave" width="760">
		<tr>
			<td>
				<form id="frmBuscarEmpleadoCopiarClaves" name="frmBuscarEmpleadoCopiarClaves" style="margin:0" onsubmit="return false;">
					<input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly"/>
					<input type="hidden" id="hddTarget" name="hddTarget"/>
					<table align="right">
						<tr align="left">
							<td align="right" class="tituloCampo" width="120">Criterio:</td>
							<td><input type="text" id="txtCriterioBuscarEmpleadoCopiarClaves" name="txtCriterioBuscarEmpleadoCopiarClaves" onkeyup="byId('btnBuscarEmpleadoCopiarClaves').click();"/></td>
							
							<td>
								<button type="submit" id="btnBuscarEmpleadoCopiarClaves" name="btnBuscarEmpleadoCopiarClaves" onclick="xajax_buscarEmpleadoCopiarClave(xajax.getFormValues('frmBuscarEmpleadoCopiarClaves'));">Buscar</button>
								<button type="button" onclick="document.forms['frmBuscarEmpleadoCopiarClaves'].reset(); byId('btnBuscarEmpleadoCopiarClaves').click();">Limpiar</button>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
		
		<tr>
			<td>
				<form id="frmListaEmpleado" name="frmListaEmpleado" style="margin:0" onsubmit="return false;">
					<div id="divListaEmpleadoCopiarClave" style="width:100%"></div>
				</form>
			</td>
		</tr>
		
		<tr>
			<td align="right"><hr>
				<button type="button" id="btnCancelarListaEmpleadoCopiarClave" name="btnCancelarListaEmpleadoCopiarClave" class="close">Cerrar</button>
			</td>
		</tr>
	</table>
</div>
<!--  --------------------------------------------------------------------------------- -->
<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
    
<form id="frmClaveUsuario" name="frmClaveUsuario" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblClaveUsuario" width="560">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Empleado:</td>
                <td width="85%">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="hddIdEmpleado" name="hddIdEmpleado" onblur="xajax_asignarEmpleado(this.value, 'false');" size="6" style="text-align:right;"/></td>
                        <td>
                        <a class="modalImg" id="aListarEmpleado" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpleado')">
                            <button type="button" id="btnListarEmpleado" name="btnListarEmpleado" title="Listar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="25%"><span class="textoRojoNegrita">*</span>Usuario:</td>
                <td width="75%">
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="hddIdUsuario" name="hddIdUsuario" readonly="readonly" size="6" style="text-align:right;"/></td>
                        <td>&nbsp;</td>
                        <td><input type="text" id="txtNombreUsuario" name="txtNombreUsuario" readonly="readonly" size="45"/></td>
                    </tr>
                    </table>
				</td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
                <td id="tdlstModulo"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Acción:</td>
                <td id="tdlstModuloClave">
                	<select id="lstModuloClave" name="lstModuloClave">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr id="trlstEmpresa" align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td id="tdlstEmpresa"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Contraseña:</td>
                <td><input type="text" id="txtContrasena" name="txtContrasena" size="20"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdClaveUsuario" name="hddIdClaveUsuario"/>
            <button type="submit" id="btnGuardarClaveEspecial" name="btnGuardarClaveEspecial" onclick="validarFrmClaveUsuario();">Guardar</button>
            <button type="button" id="btnCancelarClaveEspecial" name="btnCancelarClaveEspecial" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
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

xajax_cargaLstModuloBuscar();
xajax_cargaLstModuloClaveBuscar();
xajax_listaClaveUsuario(0, 'descripcion', 'ASC');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot   = document.getElementById("divFlotante4");
Drag.init(theHandle, theRoot);
</script>