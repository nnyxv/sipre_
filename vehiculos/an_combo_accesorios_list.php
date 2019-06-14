<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_combo_accesorios_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
include("controladores/ac_an_combo_accesorio_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Accesorios Para Combos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css" />
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<script>
	function validarForm() {
		if (validarCampo('txtCodigo','t','') == true
		&& validarCampo('txtDescripcion','t','') == true
		&& validarCampo('lstPoseeIva','t','listaExceptCero') == true
		&& validarCampo('lstActivo','t','listaExceptCero') == true
		&& validarCampo('txtPrecio','t','monto') == true
		) {
			xajax_guardarAccesorio(xajax.getFormValues('frmAccesorio'), xajax.getFormValues('frmListadoAccesorio'));
		} else {
			validarCampo('txtCodigo','t','');
			validarCampo('txtDescripcion','t','');
			validarCampo('lstPoseeIva','t','listaExceptCero');
			validarCampo('lstActivo','t','listaExceptCero');
			validarCampo('txtPrecio','t','monto');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFormPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
		xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idAccesorio){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarAccesorio(idAccesorio, xajax.getFormValues('frmListadoAccesorio'));
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
	<div id="divInfo" class="print">
		<table border="0" width="100%">
			<tr>
				<td class="tituloPaginaVehiculos">Accesorios Para Combos</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td>
					<table align="left">
						<tr>
							<td>
								<a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="xajax_nuevoAccesorio(this.id);">
                                    <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                                </a>
							</td>
						</tr>
					</table>
					<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
						<table align="right" border="0">
							<tr align="left">
                                                                <td align="right" class="tituloCampo" width="120">Activo:</td>
                                                                <td>
                                                                    <select id="lstActivoBuscar" name="lstActivoBuscar">
                                                                        <option value="-1">[ Seleccione ]</option>
                                                                        <option value="0">No</option>
                                                                        <option value="1">Si</option>
                                                                    </select>
                                                                </td>
								<td align="right" class="tituloCampo" width="100">Criterio:</td>
								<td><input type="text" id="txtCriterio" name="txtCriterio"onkeyup="byId('btnBuscar').click();"/></td>
								<td>
									<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarAccesorio(xajax.getFormValues('frmBuscar'));">Buscar</button>
									<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
								</td>
							</tr>
						</table>
					</form>
				</td>
			</tr>
			<tr>
				<td>
                <form id="frmListadoAccesorio" name="frmListadoAccesorio" style="margin:0">
	                <div id="tdListaAccesorio"></div>
                </form>
				</td>
			</tr>
                        <tr>
                            <table width="100%" cellpadding="0" cellspacing="0" class="divMsjInfo2">
                                   <tbody><tr>
                                           <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                           <td align="center">
                                                   <table>
                                                   <tbody><tr>
                                                           <td><img src="../img/iconos/ico_verde.gif"></td>
                                                           <td>Activo</td>
                                                           <td><img src="../img/iconos/ico_rojo.gif"></td>
                                                           <td>Inactivo</td>                                            
                                                   </tr>
                                                   </tbody></table>
                                           </td>
                                   </tr>
                               </tbody>
                           </table>   
                       </tr> 
		</table>
	</div>
	
	<div class="noprint">
		<?php include("pie_pagina.php"); ?>
	</div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td><td><a id="aCerrarDivFlotante" onclick="byId('divFlotante').style.display='none';"><img class="close puntero" id="imgCerrarDivFlotante" src="../img/iconos/cross.png" title="Cerrar"/></a></td></tr></table></div>

<form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
	<table border="0" id="tblPermiso" style="display:none" width="360">
	<tr>
		<td>
			<table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="68%">
                    <input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
                </td>
            </tr>
			</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<input type="submit" onclick="validarFormPermiso();" value="Aceptar">
			<input type="button" id="btnCancelarPermiso" onclick="
            byId('tblPermiso').style.display = 'none';
            byId('tblAccesorio').style.display = '';
            byId('divFlotante').style.display = '';" value="Cancelar">
		</td>
	</tr>
	</table>
</form>

<form id="frmAccesorio" name="frmAccesorio" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblAccesorio" width="680px">
	<tr>
		<td>
			<table width="100%">
			<tr align="left">
				<td align="right" class="tituloCampo" width="32%"><span class="textoRojoNegrita">*</span>Código:</td>
				<td width="68%"><input type="text" id="txtCodigo" name="txtCodigo" size="40"/></td>
			</tr>
			<tr align="left">
				<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
				<td><input type="text" id="txtDescripcion" name="txtDescripcion" size="70"/></td>
			</tr>
			<tr align="left">
				<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>I.V.A.:</td>
				<td>
					<select id="lstPoseeIva" name="lstPoseeIva">
						<option value="-1">[ Seleccione ]</option>
						<option value="0">No</option>
						<option value="1">Si</option>
					</select>
				</td>
			</tr>
			<tr align="left">
				<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Precio (Para Ventas):</td>
				<td><input type="text" id="txtPrecio" name="txtPrecio" size="12" style="text-align:right"/></td>
			</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Activo:</td>
                            <td>
                                <select id="lstActivo" name="lstActivo">                                        
                                        <option value="0">No</option>
                                        <option value="1" selected="selected">Si</option>
                                </select>
                            </td>
			</tr>
                            <!--
			<tr align="left">
				<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Genera Comisión (Para Ventas):</td>
				<td>
					<table border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td>
							<select id="lstGeneraComision" name="lstGeneraComision">
							<option value="-1">[ Seleccione ]</option>
							<option value="0">No</option>
							<option value="1">Si</option>
							</select>
						</td>
						<td>&nbsp;</td>
						<td align="center"><img id="imgGeneraComision" src="../img/iconos/lock_go.png" onclick="xajax_formValidarPermisoEdicion('an_accesorio_list_genera_comision');" style="cursor:pointer"/></td>
					</tr>
					</table>
				</td>
			</tr>-->
			</table>
		</td>
	</tr>
	<tr>
		<td align="right">
			<hr>
			<input type="hidden" id="hddIdAccesorio" name="hddIdAccesorio"/>
			<button type="submit" id="btnGuardar" name="btnGuardar" onclick="validarForm();">Guardar</button>
			<button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
		</td>
	</tr>
	</table>
</form>
</div>
<script>
byId('txtCriterio').className = 'inputHabilitado';
byId('lstActivoBuscar').className = 'inputHabilitado';
xajax_listadoAccesorio();

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

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>
