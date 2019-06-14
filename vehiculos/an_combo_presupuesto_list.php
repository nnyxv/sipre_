<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_combo_presupuesto_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_an_combo_presupuesto_list.php");

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
	
	<link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
	<link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
	
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaPresupuesto').style.display = 'none';
		byId('tblCombo').style.display = 'none';
		
		if (verTabla == "tblListaPresupuesto") {
			document.forms['frmBuscarPresupuesto'].reset();
			
			byId('txtCriterioBuscarPresupuesto').className = 'inputHabilitado';
			
			byId('hddObjDestinoPresupuesto').value = valor;
			
			byId('btnBuscarPresupuesto').click();
			
			tituloDiv1 = 'Presupuestos';
		} else if (verTabla == "tblCombo") {
			document.forms['frmCombo'].reset();
			document.forms['frmListaAccesorio'].reset();
			
			byId('txtCombo').readOnly = true;
			byId('txtObservacion').readOnly = true;;
			
			xajax_divVerCombo(valor);
			
			tituloDiv1 = 'Ver Combo';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaPresupuesto") {
			byId('txtCriterioBuscarPresupuesto').focus();
			byId('txtCriterioBuscarPresupuesto').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaCombo').style.display = 'none';
		
		if (verTabla == "tblListaCombo") {
			document.forms['frmBuscarAccesorio'].reset();
			document.forms['frmAccesorio'].reset();
			
			byId('txtCriterioBuscarCombo').className = 'inputHabilitado';
			byId('txtCriterioBuscarAccesorio').className = 'inputHabilitado';
			
			xajax_listaCombo(0, 'nombre_combo', 'ASC');
			xajax_listaAccesorio();
			
			tituloDiv2 = 'Lista de Accesorios';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaCombo") {
			byId('txtCriterioBuscarCombo').focus();
			byId('txtCriterioBuscarCombo').select();
		}
	}
	
	function validarFrmAccesorio() {
		if (validarCampo('txtCodigo','t','') == true) {
			byId('btnAgregar').disabled = true;
			byId('btnCerrar').disabled = true;
			xajax_insertarAccesorio(xajax.getFormValues('frmAccesorio'),xajax.getFormValues('frmListaAccesorio'));
		} else {
			validarCampo('txtCodigo','t','');
			
			alert("Los campos señalados en rojo son requeridos.");
			return false;
		}
	}
	
	function validarFrmDcto() {
		if (validarCampo('txtIdPresupuesto','t','') == true
		&& validarCampo('hddObj','t','') == true) {
			xajax_guardarPresupuesto(xajax.getFormValues('frmDcto'),xajax.getFormValues('frmListaAccesorio'));
		} else {
			validarCampo('txtIdPresupuesto','t','');
			
			alert("Los campos señalados en rojo son requeridos o debe agregar al menos un accesorio.");
			return false;
		}
	}
	
	function validarInsertar(idCombo) {
		if (confirm('¿Seguro desea agregar este combo?') == true) {
			xajax_insertarCombo(idCombo,xajax.getFormValues('frmListaAccesorio'));
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
			<td class="tituloPaginaVehiculos">Presupuesto de Accesorios</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table align="left" border="0" width="100%">
                <tr>
                    <td>
                    <form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
                        <table width="100%">
                        <tr>
                            <td valign="top" width="70%">
                            <fieldset><legend class="legend">Cliente</legend>
                                <table width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                    <td width="55%">
                                    	<table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right"/></td>
                                            <td></td>
                                            <td><input type="text" id="txtCliente" name="txtCliente" readonly="readonly" size="45"/></td>
                                        </tr>
                                        </table>
                                    </td>
								</tr>
                                </table>
							</fieldset>
                            </td>
                        	<td valign="top" width="30%">
                            <fieldset><legend class="legend">Presupuesto de Accesorios</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="40%">Nro. Presupuesto Acc.:</td>
                                    <td width="60%">
                                        <input type="text" id="hddIdPresupuestoAccesorio" name="hddIdPresupuestoAccesorio" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Fecha:</td>
                                    <td><input type="text" id="txtFecha" name="txtFecha" size="10" readonly="readonly" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Presupuesto:</td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdPresupuesto" name="txtIdPresupuesto" readonly="readonly" size="20" style="text-align:center"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarPresupuesto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblListaPresupuesto', 'Presupuesto');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                        </tr>
                                        </table>
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
                    <td id="tdAccesorio" name="tdAccesorio" colspan="2">
                    <form id="frmListaAccesorio" name="frmListaAccesorio" onsubmit="return false;" style="margin:0">
                    <fieldset><legend class="legend">Accesorios</legend>
                        <table border="0" width="100%">
                        <tr id="icoAgregarQuitar">
                            <td colspan="7" align="left">
                            <a class="modalImg" id="aAgregarArticulo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaCombo');">
                                <button type="button" title="Agregar Accesorio"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                            </a>
                            	<button type="button" id="btnQuitarArticulo" name="btnQuitarArticulo" onclick="xajax_eliminarAccesorio(xajax.getFormValues('frmListaAccesorio'));" title="Eliminar Accesorio"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
                        </tr>
                        <tr align="center" class="tituloColumna">
                            <td id="bntcheckbox"></td>
                            <td width="20%">Código</td>
                            <td width="50%">Descripción</td>
                            <td width="10%">Precio</td>
                            <td width="10%">Impuesto</td>
                            <td width="10%">Precio Final</td>
                        </tr>
                        <tr id="trItmPie"></tr>
                        <tr align="right">
                            <td colspan="3" rowspan="3"></td>
                            <td class="tituloCampo" colspan="2">Total Sin Impuesto:</td>
                            <td style="border-top:1px solid;"><input type="text" id="txtSubTotal" name="txtSubTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo" colspan="2">Total Impuesto:</td>
                            <td><input type="text" id="txtTotalIva" name="txtTotalIva" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo" colspan="2">Total Con Impuesto:</td>
                            <td><input type="text" id="txtTotal" name="txtTotal" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                        <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
                    </fieldset>
                    </form>
                    </td>
                </tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align="right"><hr>
				<button type="submit" id="btnGuardar" name="btnGuardar" onclick="validarFrmDcto();">Guardar</button>
				<button type="button" id="btnCancelar" name="btnCancelar" onclick="window.location.href='an_presupuesto_venta_list.php';">Cancelar</button>
			</td>
		</tr>
	</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><a id="aCerrarDivFlotante" onclick="byId('divFlotante1').style.display='none';"><img class="close puntero" id="imgCerrarDivFlotante" src="../img/iconos/cross.png" title="Cerrar"/></a></td></tr></table></div>
    
    <table border="0" id="tblListaPresupuesto" width="960">
    <tr>
        <td>
        <form id="frmBuscarPresupuesto" name="frmBuscarPresupuesto" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoPresupuesto" name="hddObjDestinoPresupuesto" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarPresupuesto" name="txtCriterioBuscarPresupuesto" onkeyup="byId('btnBuscarPresupuesto').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarPresupuesto" name="btnBuscarPresupuesto" onclick="xajax_buscarPresupuesto(xajax.getFormValues('frmBuscarPresupuesto'));">Buscar</button>
                    <button type="button" onclick="byId('txtCriterioBuscarPresupuesto').value = ''; byId('btnBuscarPresupuesto').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaPresupuesto" name="frmListaPresupuesto" onsubmit="return false;" style="margin:0">
            <div id="divListaPresupuesto" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaPresupuesto" name="btnCancelarListaPresupuesto" class="close">Cerrar</button>
        </td>
    </tr>
    </table>

   	<div id="tblCombo" style="max-height:500px; overflow:auto; width:960px">
        <table border="0" width="100%">
        <tr>
            <td colspan="2">
                <form id="frmCombo" name="frmCombo">
                    <table border="0" width="100%">
                    <tr id="trId">
                        <td>
                        <fieldset><legend class="legend">Datos del Combo</legend>
                            <table border="0" width="100%">
                            <tr align="left" id="trId">
                                <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Combo:</td>
                                <td width="66%"><input type="text" id="txtCombo" name="txtCombo" size="40"/></td>
                                <td align="right" class="tituloCampo" width="12%">Fecha Creación:</td>
                                <td width="10%"><input type="text" id="txtFechaCombo" name="txtFechaCombo" size="10" readonly="readonly" style="text-align:center"/></td>
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
            <td>
                <table border="0" width="100%">
                <tr>
                    <td>
                    <form id="frmObservacion" name="frmObservacion" onsubmit="return false;" style="margin:0">
                    <fieldset><legend class="legend">Accesorios</legend>
                        <div id="divListaComboDetalle"></div>
                        <table align="right" border="0" width="100%">
                        <tr>
                            <td id="tdObservacion" rowspan="2" valign="top" width="50%">
                            <fieldset><legend class="legend">Observación</legend>
                                    <textarea id="txtObservacion" name="txtObservacion" rows="3" style="width:99%"></textarea>
                            </fieldset>
                            </td>
                            <td valign="top" width="50%">
                        		<table border="0" width="100%">
                                <tr align="right">
                                	<td rowspan="3" width="42%"></td>
                                    <td class="tituloCampo" width="36%">Total Sin Impuesto:</td>
                                    <td style="border-top:1px solid;" width="22%"><input type="text" id="txtSubTotalC" name="txtSubTotalC" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                <tr align="right">
                                    <td class="tituloCampo">Total Impuesto:</td>
                                    <td><input type="text" id="txtTotalIvaC" name="txtTotalIvaC" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                <tr align="right" class="trResaltarTotal">
                                    <td class="tituloCampo">Total Con Impuesto:</td>
                                    <td><input type="text" id="txtTotalC" name="txtTotalC" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </form>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnCancelar" name="btnCancelar" class="close">Cerrar</button>
        </tr>
        </table>
	</div>    
</div>

<!--DIV AGREGAR/QUITAR ACCESORIOS-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><a id="aCerrarDivFlotante2" onclick="byId('divFlotante2').style.display='none';"><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></a></td></tr></table></div>
    
    <table id="tblListaCombo" width="960">
    <tr>
    	<td>
            <div class="wrap">
                <!-- the tabs -->
                <ul class="tabs">
                    <li><a href="#">Combos</a></li>
                    <li><a href="#">Accesorios</a></li>
                </ul>
                <div class="pane">
                    <table border="0" width="100%">
                    <tr>
                        <td>
                        <form id="frmBuscarCombo" name="frmBuscarCombo" onsubmit="return false;" style="margin:0">
                            <table align="right" border="0">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                <td>
                                    <input type="text" id="txtCriterioBuscarCombo" name="txtCriterioBuscarCombo" onkeyup="byId('btnBuscarCombo').click();"/>
                                </td>
                                <td>
                                    <button type="submit" id="btnBuscarCombo" name="btnBuscarCombo" onclick="xajax_buscarCombo(xajax.getFormValues('frmBuscarCombo'));">Buscar</button>
                                    <button type="button" onclick="document.forms['frmBuscarCombo'].reset(); byId('btnBuscarCombo').click();">Limpiar</button>
                                </td>
                            </tr>
                            </table>
                        </form>
                        </td>
                    </tr>
                    <tr>
                        <td><div id="divListaCombo"></div></td>
                    </tr>
                    <tr>
                        <td align="right" colspan="8"><hr>
                            <button type="button" id="btnCerrar" name="btnCerrar" class="close">Cerrar</button>
                        </td>
                    </tr>
                    </table>
                </div>
                <!-- tab "panes" -->
                <div class="pane">
                    <table border="0" width="100%">
                    <tr>
                        <td>
                        <form id="frmBuscarAccesorio" name="frmBuscarAccesorio" onsubmit="return false;" style="margin:0">
                            <table align="right" border="0">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                                <td><input type="text" id="txtCriterioBuscarAccesorio" name="txtCriterioBuscarAccesorio" onkeyup="byId('btnBuscarAccesorio').click();"/></td>
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
                        <td><div id="divListaAccesorio"></div></td>
                    </tr>
                    <tr>
                        <td>
                        <form id="frmAccesorio" name="frmAccesorio" onsubmit="return false;" style="margin:0">
                            <table width="100%">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="10%"><span class="textoRojoNegrita">*</span>Codigo:</td>
                                <td width="20%">
                                    <input type="hidden" id="hddIdCodigo" name="hddIdCodigo" readonly="readonly"/>
                                    <input type="text" id="txtCodigo" name="txtCodigo" size="20" style="text-align:left" readonly="readonly"/>
                                </td>
                                <td align="right" class="tituloCampo" width="10%">Accesorio:</td>
                                <td width="30%">
                                    <input type="hidden" id="hddIdAccesorio" name="hddIdAccesorio" readonly="readonly"/>
                                    <input type="text" id="txtAccesorio" name="txtAccesorio" size="40" style="text-align:left" readonly="readonly"/>
                                </td>
                                <td align="right" class="tituloCampo" width="10%"><?php echo $spanPrecioUnitario; ?>:</td>
                                <td width="20%">
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
                                <td align="right" colspan="8"><hr>
                                    <button type="submit" id="btnAgregar" name="btnAgregar" onclick="validarFrmAccesorio();">Agregar</button>
                                    <button type="button" id="btnCerrar" name="btnCerrar" class="close">Cerrar</button>
                                </td>
                            </tr>
                            </table>
                        </form>
                        </td>
                    </tr>
                    </table>
                </div>
            </div>
		</td>
	</tr>
    </table>
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

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane");
});

<?php if (isset($_GET['id'])) { ?>
	xajax_cargarPresupuesto('<?php echo $_GET['id'] ?>');
<?php } ?>

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>