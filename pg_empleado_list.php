<?php
require_once("connections/conex.php");

session_start();

// Validación del Módulo
include('inc_sesion.php');
if(!(validaAcceso("pg_empleado_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
// Fin Validación del Módulo

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_pg_empleado_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Empleados</title>
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
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblEmpleado').style.display = 'none';
		
		if (verTabla == "tblEmpleado") {
			document.forms['frmEmpleado'].reset();
			byId('hddIdEmpleado').value = '';
			
			byId('txtCedula').className = 'inputHabilitado';
			byId('txtNombre').className = 'inputHabilitado';
			byId('txtApellido').className = 'inputHabilitado';
			byId('txtTelefono').className = 'inputHabilitado';
			byId('txtCelular').className = 'inputHabilitado';
			byId('txtDireccion').className = 'inputHabilitado';
			byId('txtCorreo').className = 'inputHabilitado';
			byId('lstEmpresaEmpleado').className = 'inputHabilitado';
			byId('lstDepartamento').className = 'inputHabilitado';
			byId('lstCargo').className = 'inputHabilitado';
			byId('lstEstatus').className = 'inputHabilitado';
			byId('txtCodigo').className = 'inputHabilitado';
			
			xajax_formEmpleado(valor);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Empleado';
			} else {
				tituloDiv1 = 'Agregar Empleado';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblEmpleado") {
			byId('txtCedula').focus();
			byId('txtCedula').select();
		}
	}
	
	function validarFrmEmpleado() {
		if (validarCampo('txtCedula','t','') == true
		&& validarCampo('txtNombre','t','') == true
		&& validarCampo('txtApellido','t','') == true
		&& validarCampo('txtTelefono','t','telefono') == true
		&& validarCampo('txtCelular','','telefono') == true
		&& validarCampo('txtDireccion','t','') == true
		&& validarCampo('txtCorreo','t','') == true
		&& validarCampo('lstEmpresaEmpleado','t','lista') == true
		&& validarCampo('lstDepartamento','t','lista') == true
		&& validarCampo('lstCargo','t','lista') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true
		&& validarCampo('txtCodigo','t','') == true) {
			xajax_guardarEmpleado(xajax.getFormValues('frmEmpleado'), xajax.getFormValues('frmListaEmpleado'));
		} else {
			validarCampo('txtCedula','t','');
			validarCampo('txtNombre','t','');
			validarCampo('txtApellido','t','');
			validarCampo('txtTelefono','t','telefono');
			validarCampo('txtCelular','','telefono');
			validarCampo('txtDireccion','t','');
			validarCampo('txtCorreo','t','');
			validarCampo('lstEmpresaEmpleado','t','lista');
			validarCampo('lstDepartamento','t','lista');
			validarCampo('lstCargo','t','lista');
			validarCampo('lstEstatus','t','listaExceptCero');
			validarCampo('txtCodigo','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idEmpleado){
		if (confirm('Seguro desea eliminar este registro?') == true) {
			xajax_eliminarEmpleado(idEmpleado, xajax.getFormValues('frmListaEmpleado'));
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
        	<td class="tituloPaginaErp">Empleados</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblEmpleado');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    	<button type="button" onclick="xajax_exportarEmpleados(xajax.getFormValues('frmBuscar'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
					</td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Empresa:</td>
                    <td id="tdlstEmpresa"></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Modo del Cargo:</td>
                    <td>
                        <select id="lstUnipersonal" name="lstUnipersonal" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Todos ]</option>
                            <option value="0">Multipersonal</option>
                            <option value="1">Unipersonal</option>
                        </select>
                    </td>
                	<td align="right" class="tituloCampo" width="120">Estatus:</td>
                    <td>
                        <select id="lstEstatusBuscar" name="lstEstatusBuscar" onchange="byId('btnBuscar').click();">
                            <option value="-1">[ Todos ]</option>
                            <option value="1" selected="selected">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </td>
				</tr>
                <tr>
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarEmpleado(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaEmpleado" name="frmListaEmpleado" style="margin:0">
            	<div id="divListaEmpleado" style="width:100%"></div>
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
                            <td><img src="img/iconos/pencil.png"/></td><td>Editar</td>
                            <td>&nbsp;</td>
                            <td><img src="img/iconos/ico_delete.png"/></td><td>Eliminar</td>
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
    
<form id="frmEmpleado" name="frmEmpleado" style="margin:0" onsubmit="return false;">
    <table border="0" id="tblEmpleado" width="760">
    <tr>
    	<td>
        	<table width="100%">
            <tr>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>C.I.:</td>
                <td><input type="text" id="txtCedula" name="txtCedula" size="20"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td width="34%"><input type="text" id="txtNombre" name="txtNombre" size="25" maxlength="50"/></td>
            	<td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Apellido:</td>
                <td width="34%"><input type="text" id="txtApellido" name="txtApellido" size="25" maxlength="50"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Teléfono:</td>
                <td>
                <div style="float:left">
                    <input type="text" name="txtTelefono" id="txtTelefono" size="18" style="text-align:center"/>
                </div>
                <div style="float:left">
                    <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                </div>
                </td>
            	<td align="right" class="tituloCampo">Celular:</td>
                <td>
                <div style="float:left">
                    <input type="text" name="txtCelular" id="txtCelular" size="18" style="text-align:center"/>
                </div>
                <div style="float:left">
                    <img src="img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                </div>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Dirección:</td>
                <td colspan="3"><input type="text" id="txtDireccion" name="txtDireccion" size="80"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Correo:</td>
                <td colspan="3"><input type="text" id="txtCorreo" name="txtCorreo" size="30" maxlength="50"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                <td id="tdlstEmpresaEmpleado">
                    <select id="lstEmpresaEmpleado" name="lstEmpresaEmpleado">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Departamento:</td>
                <td id="tdlstDepartamento">
                    <select id="lstDepartamento" name="lstDepartamento">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cargo:</td>
                <td id="tdlstCargo">
                    <select id="lstCargo" name="lstCargo">
                        <option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus Cargo:</td>
                <td>
                	<select id="lstEstatus" name="lstEstatus">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="1" selected="selected">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Fecha Ingreso:</td>
                <td><input type="text" id="txtFechaIngreso" name="txtFechaIngreso" readonly="readonly" size="10" style="text-align:center"/></td>
                <td align="right" class="tituloCampo">Fecha Egreso:</td>
                <td><input type="text" id="txtFechaEgreso" name="txtFechaEgreso" readonly="readonly" size="10" style="text-align:center"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Código:</td>
                <td colspan="3"><input type="text" id="txtCodigo" name="txtCodigo" size="15"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <input type="hidden" id="hddIdEmpleado" name="hddIdEmpleado"/>
            <button type="submit" id="btnGuardarEmpleado" name="btnGuardarEmpleado" onclick="validarFrmEmpleado();">Guardar</button>
            <button type="button" id="btnCancelarEmpleado" name="btnCancelarEmpleado" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
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

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaEmpleado(0, "nombre_cargo", "ASC", '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>|' + byId('lstUnipersonal').value + '|' + byId('lstEstatusBuscar').value)

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>