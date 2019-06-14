<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_accesorio_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_accesorio_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Adicionales</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblPermiso').style.display = 'none';
		byId('tblAccesorio').style.display = 'none';
		
		if (verTabla == "tblAccesorio") {
			document.forms['frmAccesorio'].reset();
			byId('hddIdAccesorio').value = '';
			
			byId('txtNombre').className = 'inputHabilitado';
			byId('txtDescripcion').className = 'inputHabilitado';
			byId('lstPoseeIva').className = 'inputHabilitado';
			byId('txtPrecio').className = 'inputHabilitado';
			byId('txtCosto').className = 'inputHabilitado';
			byId('lstGeneraComision').className = 'inputHabilitado';
			byId('lstIncluirCostoCompraUnidad').className = 'inputHabilitado';
			
			xajax_formAccesorio(valor);
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Adicional';
			} else {
				tituloDiv1 = 'Agregar Adicional';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblAccesorio") {
			byId('txtNombre').focus();
			byId('txtNombre').select();
		}
	}
	
	function validarFrmAccesorio() {
		if (validarCampo('txtNombre','t','') == true
		&& validarCampo('txtDescripcion','t','') == true
		&& validarCampo('lstPoseeIva','t','listaExceptCero') == true
		&& validarCampo('txtPrecio','t','monto') == true
		&& validarCampo('txtCosto','t','numPositivo') == true) {
			xajax_guardarAccesorio(xajax.getFormValues('frmAccesorio'), xajax.getFormValues('frmListaAccesorio'));
		} else {
			validarCampo('txtNombre','t','');
			validarCampo('txtDescripcion','t','');
			validarCampo('lstPoseeIva','t','listaExceptCero');
			validarCampo('txtPrecio','t','monto');
			validarCampo('txtCosto','t','numPositivo');
			
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
			xajax_eliminarAccesorio(idAccesorio, xajax.getFormValues('frmListaAccesorio'));
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
			<td class="tituloPaginaVehiculos">Adicionales</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblAccesorio');">
							<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
						</a>
					</td>
				</tr>
				</table>
                
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio"/></td>
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
            <form id="frmListaAccesorio" name="frmListaAccesorio" style="margin:0">
                <div id="divListaAccesorio" style="width:100%"></div>
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

<form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
	<table border="0" id="tblPermiso" style="display:none" width="360">
    <tr>
        <td>
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="30%"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                <td width="70%">
                    <input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                    <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
                </td>
            </tr>
        	</table>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFormPermiso();">Aceptar</button>
			<button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" onclick="
            byId('tblPermiso').style.display = 'none';
            byId('tblAccesorio').style.display = '';
            byId('divFlotante1').style.display = '';
            centrarDiv(byId('divFlotante1'));">Cancelar</button>
			</td>
		</tr>
	</table>
</form>
    
<form id="frmAccesorio" name="frmAccesorio" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblAccesorio" width="760">
    <tr>
        <td>
            <table width="100%">
            <tr align="left">
                <td align="right" class="tituloCampo" width="28%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td width="72%"><input type="text" id="txtNombre" name="txtNombre" size="40"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td><input type="text" id="txtDescripcion" name="txtDescripcion" size="70"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Aplica Impuesto:</td>
                <td>
                    <select id="lstPoseeIva" name="lstPoseeIva">
                        <option value="-1">[ Seleccione ]</option>
                        <option value="0">No</option>
                        <option value="1">Si</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">
                    <span class="textoRojoNegrita">*</span><?php echo $spanPrecioUnitario; ?>:
                    <br>
                    <span class="textoNegrita_10px">(Para Ventas)</span>
                </td>
                <td><input type="text" id="txtPrecio" name="txtPrecio" size="12" style="text-align:right"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">
                    <span class="textoRojoNegrita">*</span>Costo:
                    <br>
                    <span class="textoNegrita_10px">(Para Compras)</span>
                </td>
                <td><input type="text" id="txtCosto" name="txtCosto" size="12" style="text-align:right"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">
                    <span class="textoRojoNegrita">*</span>Genera Comision:
                    <br>
                    <span class="textoNegrita_10px">(Para Ventas)</span>
                </td>
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
                        <td><img id="imgGeneraComision" src="../img/iconos/lock_go.png" onclick="xajax_formValidarPermisoEdicion('an_accesorio_list_genera_comision');" style="cursor:pointer" title="Desbloquear"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Incluir en el Costo de la Unidad:</td>
                <td>
                    <table border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <select id="lstIncluirCostoCompraUnidad" name="lstIncluirCostoCompraUnidad">
                                <option value="-1">[ Seleccione ]</option>
                                <option value="0">No</option>
                                <option value="1">Si</option>
                            </select>
                        </td>
                        <td>&nbsp;</td>
                        <td><img id="imgIncluirCostoCompraUnidad" src="../img/iconos/lock_go.png" onclick="xajax_formValidarPermisoEdicion('an_accesorio_list_incluir_costo_unidad');" style="cursor:pointer" title="Desbloquear"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <input type="hidden" id="hddIdAccesorio" name="hddIdAccesorio"/>
            <button type="submit" id="btnGuardarAccesorio" name="btnGuardarAccesorio" onclick="validarFrmAccesorio();">Guardar</button>
            <button type="button" id="btnCancelarAccesorio" name="btnCancelarAccesorio" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<script>
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

xajax_listaAccesorio();

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>