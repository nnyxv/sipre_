<?php
require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cc_estado_cuenta_cuentas_por_cobrar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cc_estado_cuenta_cuentas_por_cobrar.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Cobrar - E.C. Cuentas Por Cobrar</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	
	<link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorCobrar.css"/>
	<script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	<script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
	
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
	<script>
	function formListaEmpresa(nomObjeto, objDestino, nomVentana){
		openImg(nomObjeto);
		
		document.forms['frmBuscarEmpresa'].reset();
		
		byId('hddObjDestino').value = objDestino;
		byId('hddNomVentana').value = nomVentana;
		
		byId('btnBuscarEmpresa').click();
		
		byId('tblListaEmpresa').style.display = '';
		
		byId('tdFlotanteTitulo2').innerHTML = "Empresas";
		
		byId('txtCriterioBuscarEmpresa').focus();
		byId('txtCriterioBuscarEmpresa').select();
	}
		
	function validar(){
		if (byId('radioOpcion1').checked == true) {
			if (validarCampo('txtCodigoCliente','t','') == true
			&& validarCampo('txtIdEmpresa','t','') == true
			&& validarCampo('txtFecha','t','') == true) {
				xajax_listarFacturaIndividual(byId('txtCodigoCliente').value,byId('txtIdEmpresa').value,byId('txtFecha').value,xajax.getFormValues('frmCliente'));
			} else {
				validarCampo('txtCodigoCliente','t','')
				validarCampo('txtIdEmpresa','t','')
				validarCampo('txtFecha','t','')				
				alert('Los campos señalados en rojo son requeridos');
				return false;
			}
		} else if (byId('radioOpcion2').checked == true) {
			if (validarCampo('txtFecha','t','') == true) {
				byId('txtCodigoCliente').className = "inputInicial";
				byId('txtCodigoCliente').value = '';
				byId('txtNombreCliente').value = '';
				byId('txtCedulaRifCliente').value = '';
				byId('txtNITCliente').value = '';
				byId('txtTelefonoCliente').value = '';
				byId('txtDireccionCliente').value = '';
				
				xajax_listarFacturaDetalle('',byId('txtIdEmpresa').value,byId('txtFecha').value,xajax.getFormValues('frmCliente'));
			} else {
				alert('Los campos señalados en rojo son requeridos');
				return false;
			}
		} else if (byId('radioOpcion3').checked == true) {
			if (validarCampo('txtFecha','t','') == true) {
				byId('txtCodigoCliente').className = "inputInicial";
				byId('txtCodigoCliente').value = '';
				byId('txtNombreCliente').value = '';
				byId('txtCedulaRifCliente').value = '';
				byId('txtNITCliente').value = '';
				byId('txtTelefonoCliente').value = '';
				byId('txtDireccionCliente').value = '';
				
				xajax_listarNotaCargoDetalle('',byId('txtIdEmpresa').value,byId('txtFecha').value,xajax.getFormValues('frmCliente'));
			} else {
				alert('Los campos señalados en rojo son requeridos');
				return false;
			}
		} else if (byId('radioOpcion4').checked == true) {
			if (validarCampo('txtCodigoCliente','t','') == true
			&& validarCampo('txtIdEmpresa','t','') == true
			&& validarCampo('txtFecha','t','') == true) {
				xajax_listarNotaCargoIndividual(byId('txtCodigoCliente').value,byId('txtIdEmpresa').value,byId('txtFecha').value,xajax.getFormValues('frmCliente'));
			} else {
				validarCampo('txtCodigoCliente','t','')
				validarCampo('txtIdEmpresa','t','')
				validarCampo('txtFecha','t','')				
				alert('Los campos señalados en rojo son requeridos');
				return false;
			}
		} else if (byId('radioOpcion5').checked == true) {
			if (validarCampo('txtFecha','t','') == true) {
				byId('txtCodigoCliente').className = "inputInicial";
				byId('txtCodigoCliente').value = '';
				byId('txtNombreCliente').value = '';
				byId('txtCedulaRifCliente').value = '';
				byId('txtNITCliente').value = '';
				byId('txtTelefonoCliente').value = '';
				byId('txtDireccionCliente').value = '';
				
				xajax_listarFacturaResumen('',byId('txtIdEmpresa').value,byId('txtFecha').value,xajax.getFormValues('frmCliente'));
			} else {
				alert('Los campos señalados en rojo son requeridos');
				return false;
			}
		} else if (byId('radioOpcion6').checked == true) {
			if (validarCampo('txtFecha','t','') == true) {
				byId('txtCodigoCliente').className = "inputInicial";
				byId('txtCodigoCliente').value = '';
				byId('txtNombreCliente').value = '';
				byId('txtCedulaRifCliente').value = '';
				byId('txtNITCliente').value = '';
				byId('txtTelefonoCliente').value = '';
				byId('txtDireccionCliente').value = '';
				
				xajax_listarNotaCargoResumen('',byId('txtIdEmpresa').value,byId('txtFecha').value,xajax.getFormValues('frmCliente'));
			} else {
				alert('Los campos señalados en rojo son requeridos');
				return false;
			}
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_cobrar.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCuentasPorCobrar">E.C. Cuentas Por Cobrar</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table align="left">
				<tr>
					<td>
						<!--<button type="button" onclick="
						if (byId('radioOpcion1').checked == true) {
							if (validarCampo('txtCodigoCliente','t','') == true
							&& validarCampo('txtIdEmpresa','t','') == true
							&& validarCampo('txtFecha','t','') == true) {
								xajax_exportarAntiguedadSaldo(xajax.getFormValues('frmCliente'));
							} else {
								validarCampo('txtCodigoCliente','t','')
								validarCampo('txtIdEmpresa','t','')
								validarCampo('txtFecha','t','')				
								alert('Los campos señalados en rojo son requeridos');
								return false;
							}
						} else if (byId('radioOpcion2').checked == true) {
							if (validarCampo('txtFecha','t','') == true) {
								byId('txtCodigoCliente').className = 'inputInicial';
								byId('txtCodigoCliente').value = '';
								byId('txtNombreCliente').value = '';
								byId('txtCedulaRifCliente').value = '';
								byId('txtNITCliente').value = '';
								byId('txtTelefonoCliente').value = '';
								byId('txtDireccionCliente').value = '';
								
								xajax_exportarAntiguedadSaldo(xajax.getFormValues('frmCliente'));
							} else {
								alert('Los campos señalados en rojo son requeridos');
								return false;
							}
						} else if (byId('radioOpcion3').checked == true) {
							if (validarCampo('txtFecha','t','') == true) {
								byId('txtCodigoCliente').className = 'inputInicial';
								byId('txtCodigoCliente').value = '';
								byId('txtNombreCliente').value = '';
								byId('txtCedulaRifCliente').value = '';
								byId('txtNITCliente').value = '';
								byId('txtTelefonoCliente').value = '';
								byId('txtDireccionCliente').value = '';
								
								xajax_exportarAntiguedadSaldo(xajax.getFormValues('frmCliente'));
							} else {
								alert('Los campos señalados en rojo son requeridos');
								return false;
							}
						} else if (byId('radioOpcion4').checked == true) {
							if (validarCampo('txtCodigoCliente','t','') == true
							&& validarCampo('txtIdEmpresa','t','') == true
							&& validarCampo('txtFecha','t','') == true) {
								xajax_exportarAntiguedadSaldo(xajax.getFormValues('frmCliente'));
							} else {
								validarCampo('txtCodigoCliente','t','')
								validarCampo('txtIdEmpresa','t','')
								validarCampo('txtFecha','t','')				
								alert('Los campos señalados en rojo son requeridos');
								return false;
							}
                         }" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>-->
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmCliente" name="frmCliente" style="margin:0">
			<table border="0" width="100%">
			<tr>
				<td colspan="2">
					<table border="0" cellpadding="0" cellspacing="0">
					<tr align="left">
						<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
						<td>
							<input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresa(this.value);" size="6" class="inputHabilitado" style="text-align:right;"/></td>
						<td>
							<a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="formListaEmpresa(this,'Empresa','ListaEmpresa');">
								<button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar">
									<img src="../img/iconos/help.png"/>
								</button>
							</a>
						</td>
						<td>
							<input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/>
						</td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td valign="top" width="60%">
					<fieldset><legend class="legend">Datos del Cliente</legend>
					<table border="0" width="100%" align="center">
					<tr>
						<td valign="top" width="70%">
							<table border="0">
							<tr align="left">
								<td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cliente:</td>
								<td>
									<table cellpadding="0" cellspacing="0">
									<tr>
										<td>
											<input type="text" id="txtCodigoCliente" name="txtCodigoCliente" class="inputHabilitado" size="6" readonly="readonly" style="text-align:right"/>
										</td>
										<td id="tdBttCliente">
											<a class="modalImg" id="aInsertarArt" rel="#divFlotante" onclick="openImg(this); byId('btnBuscarCliente').click();">
												<button type="button" id="btnInsertarCliente" name="btnInsertarCliente" title="Seleccionar Cliente">	
													<img src="../img/iconos/help.png"/>
												</button>
											</a>
										</td>
										<td>
											<input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/>
										</td>
									</tr>
									</table>
								</td>
								<td align="right" class="tituloCampo" width="120"><?php echo $spanClienteCxC; ?>:</td>
								<td><input type="text" id="txtCedulaRifCliente" name="txtCedulaRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
							</tr>
							<tr align="left">
								<td align="right" class="tituloCampo" rowspan="2" width="120">Dirección:</td>
								<td rowspan="2"><textarea cols="55" id="txtDireccionCliente" name="txtDireccionCliente" readonly="readonly" rows="3"></textarea></td>
								<td align="right" class="tituloCampo" width="120"><?php echo $spanNIT; ?>:</td>
								<td><input type="text" id="txtNITCliente" name="txtNITCliente" readonly="readonly" size="16" style="text-align:center"/></td>
							</tr>
							<tr align="left">
								<td align="right" class="tituloCampo" width="120">Teléfono:</td>
								<td><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="12" style="text-align:center"/></td>
							</tr>
							</table>
						</td>
					</tr>
					</table>
					</fieldset>
				</td>
				<td valign="top" width="40%">
					<fieldset><legend class="legend">Estado de Cuenta</legend>
					<table border="0" width="100%" align="center">
					<tr align="left">
						<td align="right" class="tituloCampo">Cuentas Por Cobrar al:</td>
						<td colspan="2">
							<input type="text" name="txtFecha" id="txtFecha" style="text-align:center" size="10" readonly="readonly"/>
						</td>
					</tr>
					<tr align="left">
						<td align="right" class="tituloCampo" rowspan="4">Documento de Estado de Cuenta:</td>
						<td><input type="radio" id="radioOpcion1" name="radioOpcion" checked="checked" value="1"/>Facturas (Individual)</td>
						<td colspan="2"><input type="radio" id="radioOpcion2" name="radioOpcion" value="2"/>Facturas (Detallado)</td>
						<td><input type="radio" id="radioOpcion5" name="radioOpcion" value="5"/>Facturas (Resumen)</td>
					</tr>
					<tr align="left">
					</tr>
					<tr align="left">
						<td><input type="radio" id="radioOpcion4" name="radioOpcion" value="4"/>Notas de Débito (Individual)</td>
						<td colspan="2"><input type="radio" id="radioOpcion3" name="radioOpcion" value="3"/>Notas de Débito (Detallado)</td>
						<td><input type="radio" id="radioOpcion6" name="radioOpcion" value="6"/>Notas de Débito (Resumen)</td>
					</tr>
					<tr align="left">
					</tr>
					</table>
					</fieldset>
					<fieldset><legend class="legend">Módulos</legend>
					<table border="0" class="tabla" width="100%">
					<tr>
						<td id="tdModulos" valign="top" width="50%"></td>
					</tr>
					</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td align="right" colspan="2"><hr>
					<button type="button" id="bttGenerar" name="bttGenerar" onclick="validar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_examinar.png"/></td><td>&nbsp;</td><td>Generar</td></tr></table></button>
				</td>
			</tr>
			</table>
			</form>
			</td>
		</tr>
		<tr>
			<td id="tdCabeceraEstado"></td>
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
							<td><img src="../img/iconos/book_previous.png"/></td>
							<td>Factura Devuelta</td>
							<td>&nbsp;</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align="center">
				<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
					<tr>
						<td width="25">
							<img src="../img/iconos/ico_info.gif" width="25"/>
						</td>
						<td align="center">
							<table>
								<tr>
									<td></td>
									<td>Este estado de cuenta mostrará los documentos ingresados en sistema según sus pagos aplicados a partir de la fecha seleccionada, es decir, un estado de cuenta a la fecha real seleccionada.</td>
									<td>&nbsp;</td><td>&nbsp;</td>
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
	<table border="0" id="tblListadoCliente" width="700px">
	<tr id="trBuscarCliente">
		<td>
		<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="byId('btnBuscarCliente').click(); return false;" style="margin:0">
			<table border="0" align="right">
			<tr>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td>
					<input type="text" id="txtCriterioBusqCliente" name="txtCriterioBusqCliente" onkeyup="byId('btnBuscarCliente').click();"/>
				</td>
				<td>
					<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
			</tr>
			</table>
		</form>
		</td>
	</tr>
	<tr>
		<td id="tdListadoClientes"></td>
	</tr>
	<tr>
		<td align="right"><hr>
			<button type="button" id="btnCancelar" name="btnCancelar" class="close">Cancelar</button>
		</td>
	</tr>
	</table>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><a id="aCerrarDivFlotante2" onclick="byId('divFlotante2').style.display='none';"><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></a></td></tr></table></div>
	<table border="0" id="tblListaEmpresa" width="700">
	<tr>
		<td>
		<form id="frmBuscarEmpresa" name="frmBuscarEmpresa" style="margin:0" onsubmit="return false;">
			<input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
			<input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
			<table align="right">
			<tr align="left">
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td>
					<input type="text" id="txtCriterioBuscarEmpresa" name="txtCriterioBuscarEmpresa" class="inputHabilitado" onkeyup="byId('btnBuscarEmpresa').click();"/></td>
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
		<form id="frmListaEmpresa" name="frmListaEmpresa" style="margin:0" onsubmit="return false;">
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
<script>
byId('txtFecha').className = 'inputHabilitado';

byId('txtFecha').value = "<?php echo date(spanDateFormat); ?>";

window.onload = function(){
	jQuery(function($){
		$("#txtFecha").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"purple"
	});
};

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

xajax_cargarFecha();
xajax_cargarModulos();
xajax_asignarEmpresaUsuario("<?php echo $_SESSION['idEmpresaUsuarioSysGts'];?>", "Empresa", "ListaEmpresa");

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>