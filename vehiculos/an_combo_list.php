<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_combo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
include("controladores/ac_an_combo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Presupuesto de Accesorios</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css" />
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<script>
		function validarDivAccesorio() {
			if (validarCampo('txtAccesorio','t','text') == true) {
				
			/*DESHABILITA BOTON AGREGAR*/
				byId('btnAgregar').disabled = true;
				byId('btnCerrar').disabled = true;
				
				xajax_insertarAccesorio(xajax.getFormValues('frmAccesorio'),xajax.getFormValues('frmListadoAccesorio'));
				
			} else {
			
				validarCampo('txtAccesorio','t','text');
				
				alert("Los campos señalados en rojo son requeridos.");
				return false;
			}	
		}
		
		function validarEliminar(idAccesorio){
			if (confirm('¿Seguro desea eliminar este combo?') == true) {
				xajax_eliminarCombo(idAccesorio, xajax.getFormValues('frmListaAccesorio'));
			}
		}
		
		function validarForm() {
			if (!(validarCampo('hddObj','t','') == true)) {
				alert("Debe agregar al menos un Accesorio.");
				return false;
				
			} else {
				validarFormTxt()
				
			}
		}
		
		function validarFormTxt() {
			if (!(validarCampo('txtCombo','t','') == true)) {
				validarCampo('txtCombo','t','');
				
				alert("Los campos señalados en rojo son obligatorios.");
				return false;
			} else {
				xajax_guardarCombo(xajax.getFormValues('frmCombo'),xajax.getFormValues('frmListadoAccesorio'),xajax.getFormValues('frmObservacion'));
				
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
			<td class="tituloPaginaVehiculos">Combos de Accesorios</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table align="left">
					<tr>
						<td>
							<a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="xajax_divNuevoCombo(this.id,xajax.getFormValues('frmListadoAccesorio'));">
								<button type="button">
									<table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table>
								</button>
							</a>
						</td>
					</tr>
			</table>
			<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
					<td align="right" class="tituloCampo" width="100">Criterio:</td>
					<td>
						<input type="text" id="txtCriterio" name="txtCriterio"onkeyup="byId('btnBuscar').click();"/>
					</td>
					<td>
						<button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarCombo(xajax.getFormValues('frmBuscar'));">Buscar</button>
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
					<table cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td id="tdListaAccesorio"></td>
						</tr>
					</table>
				</form>
			</td>
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

	<div style="max-height:500px; overflow:auto; width:960px">
	<table border="0" width="100%">
		<tr>
			<td>
				<form id="frmCombo" name="frmCombo">
					<input type="hidden" id="hddIdCombo" name="hddIdCombo"/>
					<table border="0" width="100%">
						<tr>
							<td>
								<fieldset><legend class="legend">Datos del Combo</legend>
									<table border="0">
										<tr>
											<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>
												Combo:
											</td>
											<td align="left">
												<input type="text" id="txtCombo" name="txtCombo" size="40"/>
											</td>
											<td align="left" class="tituloCampo">
												Fecha Creación:
											</td>
											<td align="left">
												<input type="text" id="txtFechaCombo" name="txtFechaCombo" size="10" class="textoBttn" readonly="readonly" style="text-align:center"/>
											</td>
										</tr>
									</table>
								</fieldset>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
		<tr>
			<td id="tdAccesorio" name="tdAccesorio">
				<fieldset><legend class="legend">Accesorios</legend>
				<form id="frmListadoAccesorio" name="frmListadoAccesorio" onsubmit="return false;" style="margin:0">
					<table border="0" width="100%">
						<tr id="icoAgregarQuitar">
							<td colspan="7">
							<a class="modalImg" id="aInsertarArt" rel="#divFlotante2" onclick="openImg(this); xajax_divAccesorio(xajax.getFormValues('frmCombo'));">
								<button type="button" title="Agregar Accesorio">
									<table align="center" cellpadding="0" cellspacing="0">
										<tr>
											<td>&nbsp;</td>
											<td><img src="../img/iconos/ico_agregar.gif"/></td>
											<td>&nbsp;</td>
										</tr>
									</table>
								</button>
							</a>
								<button type="button" id="btnEliminarArt" name="btnEliminarArt" onclick="xajax_eliminarAccesorio(xajax.getFormValues('frmListadoAccesorio'));" style="cursor:default" title="Eliminar Accesorio">
									<table align="center" cellpadding="0" cellspacing="0">
										<tr>
											<td>&nbsp;</td>
											<td><img src="../img/iconos/ico_quitar.gif"/></td>
											<td>&nbsp;</td>
										</tr>
									</table>
								</button>
							</td>
						</tr>
						<tr align="center" class="tituloColumna">
							<td id="bntcheckbox"></td>
							<td width="20%">Código</td>
							<td width="50%">Descripción</td>
							<td width="10%">Precio Sin I.V.A</td>
							<td width="10%">I.V.A 12%</td>
							<td width="10%">Precio Final</td>
						</tr>
						<tr id="trItmPie"></tr>
						<tr>
							<td colspan="4"></td>
							<td class="tituloCampo" align="right">TOTAL Sin IV.A:</td>
							<td>
								<input type="text" id="txtSubTotal" name="txtSubTotal" readonly="readonly" size="16" style="text-align:right"/>
							</td>
						</tr>
						<tr>
							<td colspan="4"></td>
							<td class="tituloCampo" align="right">Total I.V.A:</td>
							<td>
								<input type="text" id="txtTotalIva" name="txtTotalIva" readonly="readonly" size="16" style="text-align:right"/>
							</td>
						</tr>
						<tr>
							<td colspan="4"></td>
							<td class="tituloCampo" align="right">Total Con I.V.A:</td>
							<td>
								<input type="text" id="txtTotal" name="txtTotal" readonly="readonly" size="16" style="text-align:right"/>
							</td>
						</tr>
					</table>
					<input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
				</form>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td id="tdObservacion" name="tdObservacion">
				<fieldset><legend class="legend">Observación</legend>
				<form id="frmObservacion" name="frmObservacion" onsubmit="return false;" style="margin:0">
					<table border="0" width="100%">
						<tr>
							<td>
								<textarea id="txtObservacion" name="txtObservacion" rows="2" style="width:100%"></textarea>
							</td>
						</tr>
					</table>
				</form>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td align="right">
				<hr>
				<button type="submit" id="btnGuardarCombo" name="btnGuardarCombo" onclick="validarForm();">Guardar</button>
				<button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
			</td>
		</tr>
	</table>
	</div>
</div>

<!--DIV AGREGAR/QUITAR ACCESORIOS-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><a id="aCerrarDivFlotante2" onclick="byId('divFlotante2').style.display='none';"><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></a></td></tr></table></div>
	<table border="0" width="800px">
		<tr>
			<td>
				<form id="frmBuscarAccesorio" name="frmBuscarAccesorio" onsubmit="return false;" style="margin:0">
					<table align="right" border="0">
						<tr align="left">
							<td align="right" class="tituloCampo" width="100">Criterio:</td>
							<td>	
								<input type="text" id="txtCriterioAccesorio" name="txtCriterioAccesorio"onkeyup="byId('btnBuscarAccesorio').click();"/>
							</td>
								<td>
								<button type="submit" id="btnBuscarAccesorio" name="btnBuscarAccesorio" onclick="xajax_buscarAccesorio(xajax.getFormValues('frmBuscarAccesorio'));">Buscar</button>
								<button type="button" onclick="document.forms['frmBuscarAccesorio'].reset(); byId('btnBuscarAccesorio').click();">Limpiar</button>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
		<tr>
			<td>
				<div id="divListadoAccesorio"></div>
			</td>
		</tr>
		<tr>
			<td>
				<form id="frmAccesorio" name="frmAccesorio" onsubmit="return false;" style="margin:0">
					<table width="100%">
						<tr>
							<td align="right" class="tituloCampo">
								Codigo:
							</td>
							<td>
								<input type="hidden" id="hddIdCodigo" name="hddIdCodigo" readonly="readonly"/>
								<input type="text" id="txtCodigo" name="txtCodigo" size="40" style="text-align:left" readonly="readonly"/>
							</td>
							<td align="right" class="tituloCampo">
								Accesorio:
							</td>
							<td>
								<input type="hidden" id="hddIdAccesorio" name="hddIdAccesorio" readonly="readonly"/>
								<input type="text" id="txtAccesorio" name="txtAccesorio" size="40" style="text-align:left" readonly="readonly"/>
							</td>
							<td align="right" class="tituloCampo"><?php echo $spanPrecioUnitario; ?>:</td>
							<td>
								<input type="text" id="txtPrecio" name="txtPrecio" size="16" style="text-align:right" readonly="readonly"/>
							</td>
						</tr>
						<tr>
							<td colspan="8">
								<table cellpadding="0" cellspacing="0" class="divMsjInfo" width="100%">
									<tr>
										<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
										<td align="center">El precio de los artículos No Incluye Impuesto</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td align="right" colspan="8">
								<hr>
								<input id="btnAgregar" type="submit" onclick="validarDivAccesorio();" value="Agregar">
								<input type="button" id="btnCerrar" class="close" value="Cerrar">
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
	</table>
</div>

<script>
xajax_listadoCombo();

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

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>
