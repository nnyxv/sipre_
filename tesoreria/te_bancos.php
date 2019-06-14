<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("te_bancos"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_bancos.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Bancos</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    
    <script>
	
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		if (valor == 'nuevo') {
			document.forms['frmBanco'].reset();
			byId('hddIdBanco').value = 0;
			
			xajax_nuevoBanco();
			$('#frmBanco').find('input,select,textarea').attr("class","inputHabilitado");
			$('#frmBanco').find('input,textarea').attr('readonly', false);
			
			tituloDiv1 = 'Nuevo Banco';
		} else if (valor == 'ver') {
			xajax_formBanco(valor2, 1);
			
			$('#frmBanco').find('input,select,textarea').attr("class","inputInicial");
			$('#frmBanco').find('input,textarea').attr('readonly', true);
									
			tituloDiv1 = 'Ver Banco';
		} else if (valor == 'editar') {
			xajax_formBanco(valor2, 2);
			
			$('#frmBanco').find('input,select,textarea').attr("class","inputHabilitado");
			$('#frmBanco').find('input,textarea').attr('readonly', false);
			
			tituloDiv1 = 'Editar Banco';
		}
		
		openImg(nomObjeto);
		byId('tdFlotanteTitulo').innerHTML = tituloDiv1;
	}
	
	function validarBanco(){
		if (validarCampo('txtNombreBanco','t','') == true){
				xajax_guardarBanco(xajax.getFormValues('frmBanco'));
		} else {
			validarCampo('txtNombreBanco','t','');
						
			alert("El campo señalado en rojo es requerido");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include ('banner_tesoreria.php'); ?></div>	
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaTesoreria" colspan="2">Bancos</td>
		</tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
		<tr class="noprint">
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="abrirDivFlotante1(this, '', 'nuevo');">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
					</td>
				</tr>
				</table>
				<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Criterio:</td>
					<td><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" onkeyup="byId('btnBuscar').click();"/></td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarBanco(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>
				</table>
				</form>
			</td>
		</tr>
		<tr>
			<td id="tdListaBancos"></td>
		</tr>
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25" class="puntero"/></td>
					<td align="center">
						<table>
						<tr>
							<td><img src="../img/iconos/ico_view.png"/></td>
							<td>Ver</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/pencil.png"/></td>
							<td>Editar</td>
							<td>&nbsp;</td>
							<td><img src="../img/iconos/cross.png"/></td>
							<td>Eliminar</td>
							<td>&nbsp;</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</td>
		</tr>
		</table>
	</div>
	<div class="noprint"><?php include("pie_pagina.php") ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
	<form id="frmBanco" name="frmBanco">
	<table border="0" id="tblBanco" width="700px">
	<tr align="left">
		<td>
			<table border="0">
			<tr>
				<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nombre:</td>
				<td>
					<input type="text" id="txtNombreBanco" name="txtNombreBanco" size="30" />
					<input type="hidden" id="hddIdBanco" name="hddIdBanco" />
				</td>
				<td align="right" class="tituloCampo" width="120">Sucursal:</td>
				<td><input type="text" id="txtSucursalBanco" name="txtSucursalBanco" size="30" /></td>
			</tr>       
			<tr>
                <td align="right" class="tituloCampo" width="120" rowspan="3">
                	<img title="Panama y Venezuela es informativo. En Puerto Rico se utiliza versión corta para la impresión de cheques" src="../img/iconos/information.png" style="vertical-align:middle"/>
                	Direcci&oacute;n (Corta):
                </td>
                <td rowspan="3"><textarea cols="27" rows="3" id="txtDireccionBanco" name="txtDireccionBanco" /></textarea></td>
                <td align="right" class="tituloCampo" width="120">
                    <img title="Código Banco y Código Sucursal se usan para cheques de Puerto Rico" src="../img/iconos/information.png" style="vertical-align:middle"/>
                    C&oacute;digo Banco:
                </td>
                <td>
                    <input type="text" id="txtCodigo1" name="txtCodigo1" size="30" />
                </td>
			</tr>            
			<tr>
                <!--<td></td>
                <td></td>-->
                <td align="right" class="tituloCampo" width="120">
                    C&oacute;digo Sucursal:
                </td>
                <td>                    
                    <input type="text" id="txtCodigo2" name="txtCodigo2" size="30" />
                </td>
			</tr>            
            <tr>				
<!--                <td></td>
                <td></td>-->
                <td align="right" class="tituloCampo" width="120"><?php echo $spanRIF; ?>:</td>
                <td><input type="text" id="txtRIF" name="txtRIF" size="30" /></td>            
            </tr>
			<tr>
                <td align="right" class="tituloCampo" width="120">Tel&eacute;fono:</td>
                <td><input type="text" id="txtTelefonoBanco" name="txtTelefonoBanco" size="30" onkeypress="return validarTelefono(event);" /></td>
                <td align="right" class="tituloCampo" width="120">Fax:</td>
                <td><input type="text" id="txtFaxBanco" name="txtFaxBanco" size="30" onkeypress="return validarTelefono(event);" /></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo" width="120">Email:</td>
				<td><input type="text" id="txtEmailBanco" name="txtEmailBanco" size="30" /></td>
				<td align="right" class="tituloCampo" width="120">Porecentaje Flat:</td>
				<td><input type="text" id="txtPorcentajeFlatBanco" name="txtPorcentajeFlatBanco" size="30" onkeypress="return validarSoloNumerosReales(event);" /></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo" width="120">DSBC Locales:</td>
				<td><input type="text" id="txtDSBCLocalesBanco" name="txtDSBCLocalesBanco" size="30" onkeypress="return validarSoloNumeros(event);" /></td>
				<td align="right" class="tituloCampo" width="120">DSBC Foraneos:</td>
				<td><input type="text" id="txtDSBCForaneosBanco" name="txtDSBCForaneosBanco" size="30" onkeypress="return validarSoloNumeros(event);" /></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo" width="120">Es cooperativa:</td>
				<td>
					<select name="selCooperativa" id="selCooperativa">
                        <option value="0">NO</option>
                        <option value="1">SI</option>
                    </select>
				</td>
			</tr>     
			</table>
            
            <fieldset>
            <legend class="legend">Direcci&oacute;n Banco</legend>
			<table border="0">
			<tr>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanUrbanizacion); ?>:</td>
				<td><input type="text" id="txtUrbanizacion" name="txtUrbanizacion" size="27" /></td>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanCalleAv); ?>:</td>
				<td><input type="text" id="txtCalle" name="txtCalle" size="27" /></td>
			</tr>
            <tr>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanCasaEdif); ?>:</td>
				<td><input type="text" id="txtCasa" name="txtCasa" size="27" /></td>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanMunicipio); ?>:</td>
				<td><input type="text" id="txtMunicipio" name="txtMunicipio" size="27" /></td>
			</tr>
            <tr>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanCiudad); ?>:</td>
				<td><input type="text" id="txtCiudad" name="txtCiudad" size="27" /></td>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanEstado); ?>:</td>
				<td><input type="text" id="txtEstado" name="txtEstado" size="27" /></td>
			</tr>
            </table>            
            </fieldset>
            
            <fieldset>
            <legend class="legend">Direcci&oacute;n Postal Banco</legend>
			<table border="0">
			<tr>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanUrbanizacion); ?>:</td>
				<td><input type="text" id="txtUrbanizacionPostal" name="txtUrbanizacionPostal" size="27" /></td>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanCalleAv); ?>:</td>
				<td><input type="text" id="txtCallePostal" name="txtCallePostal" size="27" /></td>
			</tr>
            <tr>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanCasaEdif); ?>:</td>
				<td><input type="text" id="txtCasaPostal" name="txtCasaPostal" size="27" /></td>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanMunicipio); ?>:</td>
				<td><input type="text" id="txtMunicipioPostal" name="txtMunicipioPostal" size="27" /></td>
			</tr>
            <tr>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanCiudad); ?>:</td>
				<td><input type="text" id="txtCiudadPostal" name="txtCiudadPostal" size="27" /></td>
				<td align="right" class="tituloCampo" width="120"><?php echo ($spanEstado); ?>:</td>
				<td><input type="text" id="txtEstadoPostal" name="txtEstadoPostal" size="27" /></td>
			</tr>
            </table>   
            </fieldset>
            
            <fieldset>
            <legend class="legend">Formato de Impresión de Cheques</legend>
			<table border="0" width="100%">
			<tr>
				<td align="right" class="tituloCampo" width="120">Nombre Proveedor:</td>
				<td>&nbsp;X:&nbsp;<input type="text" id="txtXproveedor" name="txtXproveedor" autocomplete="off" size="5" class="inputHabilitado" style="text-align:center"/>&nbsp;Y:&nbsp;<input type="text" id="txtYproveedor" name="txtYproveedor" autocomplete="off" size="5" class="inputHabilitado" style="text-align:center"/></td>
				<td align="right" class="tituloCampo" width="120">Cantidad:</td>
				<td>&nbsp;X:&nbsp;<input type="text" id="txtXcantidad" name="txtXcantidad" autocomplete="off" size="5" class="inputHabilitado" style="text-align:center"/>&nbsp;Y:&nbsp;<input type="text" id="txtYcantidad" name="txtYcantidad" autocomplete="off" size="5" class="inputHabilitado" style="text-align:center"/></td>
			</tr>
			<tr>
				<td align="right" class="tituloCampo" width="120">Fecha:</td>
				<td>&nbsp;X:&nbsp;<input type="text" id="txtXfecha" name="txtXfecha" autocomplete="off" size="5" class="inputHabilitado" style="text-align:center"/>&nbsp;Y:&nbsp;<input type="text" id="txtYfecha" name="txtYfecha" autocomplete="off" size="5" class="inputHabilitado" style="text-align:center"/></td>
				<td align="right" class="tituloCampo" width="120" style="white-space:nowrap;"><img title="Monto en Letras (Panamá)" src="../img/iconos/information.png" style="vertical-align:middle;"/>
                Cantidad (Letras):</td>
				<td>&nbsp;X:&nbsp;<input type="text" id="txtXcantidadLetras" name="txtXcantidadLetras" autocomplete="off" size="5" class="inputHabilitado" style="text-align:center"/>&nbsp;Y:&nbsp;<input type="text" id="txtYcantidadLetras" name="txtYcantidadLetras" autocomplete="off" size="5" class="inputHabilitado" style="text-align:center"/></td>
			</tr>
            </table>   
            </fieldset>
		</td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="button" id="btnGuardar" name="btnGuardar" onclick="validarBanco();">Guardar</button>
			<button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
		</td>
	</tr>
	</table>
	</form>
</div>

<script language="javascript">
byId('btnBuscar').click();

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
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);
</script>