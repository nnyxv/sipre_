<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_poliza_seguro_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_poliza_seguro_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Pólizas de Seguro</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script>
	function validarForm() {
		if (validarCampo('txtNombre','t','') == true
		&& validarCampo('txtDireccion','t','') == true
		&& validarCampo('txtPais','t','') == true
		&& validarCampo('txtCiudad','t','') == true
		&& validarCampo('txtCompSeguros','t','') == true
		&& validarCampo('txtTelfAgencia','t','') == true
		&& validarCampo('lstEstatus','t','listaExceptCero') == true) {
		/*&& validarCampo('txtPolizaContado','t','monto') == true
		&& validarCampo('txtInicial','t','monto') == true
		&& validarCampo('txtMeses','t','cantidad') == true
		&& validarCampo('txtMontoCuotas','t','monto') == true
		&& validarCampo('txtCheque','t','') == true
		&& validarCampo('txtFinanciada','t','') == true*/
			xajax_guardarPoliza(xajax.getFormValues('frmPoliza'), xajax.getFormValues('frmListaPoliza'));
		} else {
			validarCampo('txtNombre','t','');
			validarCampo('txtDireccion','t','');
			validarCampo('txtPais','t','');
			validarCampo('txtCiudad','t','');
			validarCampo('txtCompSeguros','t','');
			validarCampo('txtTelfAgencia','t','');
			validarCampo('lstEstatus','t','listaExceptCero');
			/*validarCampo('txtPolizaContado','t','monto');
			validarCampo('txtInicial','t','monto');
			validarCampo('txtMeses','t','cantidad');
			validarCampo('txtMontoCuotas','t','monto');
			validarCampo('txtCheque','t','');
			validarCampo('txtFinanciada','t','');*/
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarEliminar(idPoliza){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarPoliza(idPoliza, xajax.getFormValues('frmListaPoliza'));
		}
	}
	
	function formListaEmpresa() {
		xajax_listadoEmpresas();
		
		byId('tdFlotanteTitulo2').innerHTML = "Empresas";
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Pólizas de Seguro</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="xajax_formPoliza(this.id);">
                    	<button type="button" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                    </a>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right">			
                <tr align="left">
                	<td class="tituloCampo" width="120" align="right">Estatus:</td>
					<td>
                        <select id="lstActivoBuscar" name="lstActivoBuscar" onchange="byId('btnBuscar').click();" class="inputHabilitado">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Inactivo</option>
                            <option value="1">Activo</option>
                        </select>
                    </td>
                    
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarPoliza(xajax.getFormValues('frmBuscar'));" >Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
			</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaPoliza" name="frmListaPoliza" style="margin:0">
            	<div id="divListaPoliza" style="width:100%"></div>
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
                        	<td><img src="../img/iconos/ico_verde.gif" /></td>
                            <td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif" /></td>
                            <td>Inactivo</td>
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

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td><td><a id="aCerrarDivFlotante" onclick="byId('divFlotante').style.display='none';"><img class="close puntero" id="imgCerrarDivFlotante" src="../img/iconos/cross.png" title="Cerrar"/></a></td></tr></table></div>
    
<form id="frmPoliza" name="frmPoliza" onsubmit="return false;" style="margin:0">
    <table border="0" width="450">
    <tr>
    	<td>
        	<table width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                <td><input type="text" id="txtNombre" name="txtNombre" size="35"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Direcion:</td>
                <td><input type="text" id="txtDireccion" name="txtDireccion" size="35"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ciudad:</td>
                <td><input type="text" id="txtCiudad" name="txtCiudad" size="35"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Pais:</td>
                <td><input type="text" id="txtPais" name="txtPais" size="35"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Compañia de Seguros</td>
                <td><input type="text" id="txtCompSeguros" name="txtCompSeguros" size="35"/></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tlf. Agencia</td>
                <td><input type="text" id="txtTelfAgencia" name="txtTelfAgencia" size="35"/></td>
            </tr>
            <!--
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Poliza a Contado:</td>
                <td><input type="text" id="txtPolizaContado" name="txtPolizaContado" style="text-align:right"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanInicial; ?>:</td>
                <td><input type="text" id="txtInicial" name="txtInicial" style="text-align:right"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Meses:</td>
                <td><input type="text" id="txtMeses" name="txtMeses" style="text-align:right"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cuotas:</td>
                <td><input type="text" id="txtMontoCuotas" name="txtMontoCuotas" style="text-align:right"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cheque:</td>
                <td><input type="text" id="txtCheque" name="txtCheque" size="30"/></td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Financiada:</td>
                <td><input type="text" id="txtFinanciada" name="txtFinanciada" size="30"/></td>
            </tr>-->
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estatus:</td>
            	<td>
                	<select id="lstEstatus" name="lstEstatus">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="0">Inactivo</option>
                        <option value="1">Activo</option>
                    </select>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="hidden" id="hddIdPoliza" name="hddIdPoliza"/>
            <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarForm();">Guardar</button>
            <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
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

xajax_listaPoliza();

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>