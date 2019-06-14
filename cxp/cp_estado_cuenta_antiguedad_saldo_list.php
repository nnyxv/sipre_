<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cp_estado_cuenta_antiguedad_saldo_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');

//Instanciando el objeto xajax
$xajax = new xajax();

//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cp_estado_cuenta_antiguedad_saldo_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Estado de Cuenta Antigüedad de Saldos</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCuentasPorPagar.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
	
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblListaProveedor').style.display = 'none';
		
		if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();
			
			byId('hddObjDestino').value = (valor == undefined) ? '' : valor;
			byId('hddNomVentana').value = (valor2 == undefined) ? '' : valor2;
			
			byId('btnBuscarEmpresa').click();
			
			tituloDiv1 = 'Empresas';
		} else if (verTabla == "tblListaProveedor") {
			document.forms['frmBuscarProveedor'].reset();
			
			byId('hddObjDestinoProveedor').value = valor;
			
			byId('btnBuscarProveedor').click();
			
			tituloDiv1 = 'Proveedores';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblListaProveedor") {
			byId('txtCriterioBuscarProveedor').focus();
			byId('txtCriterioBuscarProveedor').select();
		}
	}
	
	function validarFrmBuscar(){
		error = false;
		
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('txtFecha','t','') == true
		&& validarCampo('lstTipoDetalle','t','lista') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('txtFecha','t','');
			validarCampo('lstTipoDetalle','t','lista');
			
			error = true;
		}
		
		if (byId('radioOpcion1').checked == true) {
			if (!(validarCampo('txtIdProv','t','') == true)) {
				validarCampo('txtIdProv','t','');
				
				error = true;
			}
		} else if (inArray(true, [byId('radioOpcion2').checked, byId('radioOpcion3').checked, byId('radioOpcion4').checked])) {
			byId('txtIdProv').className = "inputInicial";
			byId('txtIdProv').value = '';
			byId('txtNombreProv').value = '';
			byId('txtDireccionProv').innerHTML = '';
			byId('txtRifProv').value = '';
			byId('txtNitProv').value = '';
			byId('txtTelefonosProv').value = '';
			byId('txtDiasCreditoProv').value = '';
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_buscarEstadoCuenta(xajax.getFormValues('frmBuscar'));
		}
	}
	</script>
</head>

<body>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cuentas_por_pagar.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table border="0" width="100%">
        <tr>
            <td class="tituloPaginaCuentasPorPagar">Estado de Cuenta Antigüedad de Saldos</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>
                <table align="left">
                <tr>
                    <td>
                        <button type="button" onclick="
                        if (byId('radioOpcion1').checked == true) {
                            if (validarCampo('txtIdProv','t','') == true
                            &&  validarCampo('txtFecha','t','') == true) {
                                xajax_exportarAntiguedadSaldo(xajax.getFormValues('frmBuscar'));
                            } else {
                                alert('Los campos señalados en rojo son requeridos');
                                return false;
                            }
                        } else if (inArray(true, [byId('radioOpcion2').checked, byId('radioOpcion3').checked, byId('radioOpcion4').checked])) {
                            if (validarCampo('txtFecha','t','') == true) {
                                byId('txtIdProv').className = 'inputInicial';
                                byId('txtIdProv').value = '';
                                byId('txtNombreProv').value = '';
                                byId('txtDireccionProv').innerHTML = '';
                                byId('txtRifProv').value = '';
                                byId('txtNitProv').value = '';
                                byId('txtTelefonosProv').value = '';
                                byId('txtDiasCreditoProv').value = '';
                                
                               xajax_exportarAntiguedadSaldo(xajax.getFormValues('frmBuscar'));
                            } else {
                                alert('Los campos señalados en rojo son requeridos');
                                return false;
                            }
                        }" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table>
                        </button>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="left">
            <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table border="0" width="100%">
                <tr>
                    <td colspan="2">
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td width="88%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpresa" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaEmpresa', 'Empresa', 'ListaEmpresa');">
                                        <button type="button" id="btnListarEmpresa" name="btnListarEmpresa" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
					</td>
                </tr>
                <tr>
                    <td valign="top" width="65%">
					<fieldset><legend class="legend">Proveedor</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Razón Social:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value, 'Prov', 'true', 'false');" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarProv" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaProveedor', 'Prov');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="4" width="18%">Dirección:</td>
                            <td rowspan="4" width="44%"><textarea id="txtDireccionProv" name="txtDireccionProv" readonly="readonly" rows="3" style="width:99%"></textarea></td>
                            <td align="right" class="tituloCampo" width="18%"><?php echo $spanProvCxP; ?>:</td>
                            <td width="20%"><input type="text" id="txtRifProv" name="txtRifProv" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanNIT; ?>:</td>
                            <td><input type="text" id="txtNitProv" name="txtNitProv" readonly="readonly" size="16" style="text-align:right"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Teléfonos:</td>
                            <td><input type="text" id="txtTelefonosProv" name="txtTelefonosProv" readonly="readonly" size="12" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Días Crédito:</td>
                            <td><input type="text" id="txtDiasCreditoProv" name="txtDiasCreditoProv" readonly="readonly" size="12" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    	
                        <table align="right" border="0">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Días Vencidos:</td>
                            <td id="tdDiasVencidos" valign="top"></td>
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" name="txtCriterio" id="txtCriterio" /></td>
                        </tr>
                        </table>
                    </td>
                    <td valign="top" width="35%">
					<fieldset><legend class="legend">Tipo de Estado de Cuenta</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="25%">Generar al:</td>
                            <td width="75%"><input type="text" id="txtFecha" name="txtFecha" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="2">Ver Estado de Cuenta:</td>
                            <td>
                            	<label><input type="radio" id="radioOpcion1" name="radioOpcion" checked="checked" value="1"/> Individual</label>
                                <br>
                                <label><input type="radio" id="radioOpcion2" name="radioOpcion" value="2"/> General</label>
                                <br>
                                <label><input type="radio" id="radioOpcion3" name="radioOpcion" value="3"/> General por proveedor</label>
                                <br>
                                <label><input type="radio" id="radioOpcion4" name="radioOpcion" value="4"/> General por documento</label>
							</td>
                        </tr>
                        <tr align="left">
                            <td>
                            	<select id="lstTipoDetalle" name="lstTipoDetalle" style="width:99%">
                                	<option id="-1">[ Seleccione ]</option>
                                    <option selected="selected" value="1">Detallado por Empresa</option>
                                    <option value="2">Consolidado</option>
                                </select>
                            </td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Módulo:</td>
                    		<td id="tdModulos" valign="top"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Tipo de Dcto.:</td>
                    		<td id="tdTipoDocumento" valign="top"></td>
                        </tr>
                        </table>
					</fieldset>
                    </td>
                </tr>
                <tr>
                    <td align="right" colspan="2"><hr>
                    	<button type="submit" id="btnGenerar" name="btnGenerar" onclick="validarFrmBuscar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_examinar.png"/></td><td>&nbsp;</td><td>Generar</td></tr></table></button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
        	<td><div id="divListaEstadoCuenta" style="width:100%"></div></td>
        </tr>
        </table>
    </div>
	
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <table border="0" id="tblListaEmpresa" width="760">
    <tr>
        <td>
        <form id="frmBuscarEmpresa" name="frmBuscarEmpresa" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestino" name="hddObjDestino" readonly="readonly" />
            <input type="hidden" id="hddNomVentana" name="hddNomVentana" readonly="readonly" />
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
    
    <table border="0" id="tblListaProveedor" width="760">
    <tr>
        <td>
        <form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoProveedor" name="hddObjDestinoProveedor" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaProveedor" name="hddNomVentanaProveedor" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarProveedor" name="txtCriterioBuscarProveedor" class="inputHabilitado" onkeyup="byId('btnBuscarProveedor').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarProveedor'].reset(); byId('btnBuscarProveedor').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaProveedor" name="frmListaProveedor" onsubmit="return false;" style="margin:0">
            <div id="divListaProveedor" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaProveedor" name="btnCancelarListaProveedor" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('txtIdEmpresa').className = 'inputHabilitado';
byId('txtIdProv').className = 'inputHabilitado';
byId('txtFecha').className = 'inputHabilitado';
byId('lstTipoDetalle').className = 'inputHabilitado';
byId('txtCriterio').className = 'inputHabilitado';

byId('txtFecha').value = "<?php echo date(spanDateFormat); ?>";

window.onload = function(){
	jQuery(function($){
		$("#txtFecha").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFecha",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"torqoise"
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

xajax_asignarEmpresaUsuario('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', "Empresa", "ListaEmpresa");
xajax_cargarDiasVencidos();
xajax_cargarModulos();
xajax_cargarTipoDocumento();

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>