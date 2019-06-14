<?php
require_once("connections/conex.php");

session_start();

/* Validación del Módulo */
include('inc_sesion.php');
if(!(validaAcceso("pg_clave_movimiento_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

include("controladores/ac_iv_general.php");
include("controladores/ac_pg_clave_movimiento_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Claves de Movimiento</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="js/domDragErp.css">
    <script type="text/javascript" language="javascript" src="js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="js/maskedinput/jquery.maskedinput.js"></script>
	
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblClaveMovimiento').style.display = 'none';
		
		if (verTabla == "tblClaveMovimiento") {
			document.forms['frmClaveMovimiento'].reset();
			
			byId('hddIdClaveMovimiento').value = '';
			
			byId('txtClave').className = 'inputHabilitado';
			byId('txtDescripcion').className = 'inputHabilitado';
			byId('lstTipoClave').className = 'inputHabilitado';
			byId('txtIdNumeracionDocumento').className = 'inputHabilitado';
			byId('txtIdNumeracionControl').className = 'inputHabilitado';
			byId('txtContraCuenta').className = 'inputHabilitado';
			byId('txtPrefijoFolioMultiple').className = 'inputHabilitado';
			byId('txtArea').className = 'inputHabilitado';
			
			xajax_formClaveMovimiento(valor, xajax.getFormValues('frmCliente'));
			
			if (valor > 0) {
				tituloDiv1 = 'Editar Clave de Movimiento';
			} else {
				tituloDiv1 = 'Agregar Clave de Movimiento';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblClaveMovimiento") {
			byId('txtClave').focus();
			byId('txtClave').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaNumeracion').style.display = 'none';
		
		if (verTabla == "tblListaNumeracion") {
			document.forms['frmBuscarNumeracion'].reset();
			
			byId('hddObjDestinoNumeracion').value = valor;
			
			byId('btnBuscarNumeracion').click();
			
			tituloDiv2 = 'Numeración';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaNumeracion") {
			byId('txtCriterioBuscarNumeracion').focus();
			byId('txtCriterioBuscarNumeracion').select();
		}
	}
	
	function validarFrmClaveMovimiento() {
		if (validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('txtClave','t','') == true
		&& validarCampo('txtDescripcion','t','') == true
		&& validarCampo('lstTipoClave','t','lista') == true
		&& validarCampo('lstDctoGenerado','t','listaExceptCero') == true) {
			xajax_guardarClaveMovimiento(xajax.getFormValues('frmClaveMovimiento'), xajax.getFormValues('frmListaClaveMovimiento'));
		} else {
			validarCampo('lstModulo','t','listaExceptCero');
			validarCampo('txtClave','t','');
			validarCampo('txtDescripcion','t','');
			validarCampo('lstTipoClave','t','lista');
			validarCampo('lstDctoGenerado','t','listaExceptCero');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("sysgts_menu.inc.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaErp">Claves de Movimiento</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                	<td>
                    <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblClaveMovimiento');">
                    	<button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
					</a>
                    	<button type="button" onclick="if (confirm('¿Desea eliminar los registros seleccionado(s)?') == true) xajax_eliminarClaveMovimiento(xajax.getFormValues('frmListaClaveMovimiento'), xajax.getFormValues('frmListaClaveMovimiento'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_delete.png"/></td><td>&nbsp;</td><td>Eliminar</td></tr></table></button>
                    	<button type="button" onclick="xajax_encabezadoEmpresa('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>'); window.print();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                    </td>
                </tr>
                </table>
                
			<form id="frmBuscar" name="frmBuscar" style="margin:0" onsubmit="return false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Tipo Mov.:</td>
                    <td id="tdlstTipoMovimiento"></td>
                    <td align="right" class="tituloCampo" width="120">Dcto. Generado:</td>
                    <td id="tdlstDctoGeneradoBuscar">
                        <select id="lstDctoGeneradoBuscar" name="lstDctoGeneradoBuscar">
                        	<option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Módulo:</td>
                    <td id="tdlstModuloBuscar">
                        <select id="lstModuloBuscar" name="lstModuloBuscar">
                        	<option value="-1">[ Seleccione ]</option>
                        </select>
                    </td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Tipo de Pago:</td>
                    <td id="tdlstTipoPago"></td>
                	<td align="right" class="tituloCampo">Estatus:</td>
                    <td id="tdlstEstatusBuscar"></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" onclick="xajax_buscarClaveMovimiento(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
			</td>
        </tr>
        <tr>
        	<td>
            <form id="frmListaClaveMovimiento" name="frmListaClaveMovimiento" style="margin:0">
            	<div id="divListaClaveMovimiento" style="width:100%"></div>
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
    
<form id="frmClaveMovimiento" name="frmClaveMovimiento" style="margin:0" onsubmit="return false;">
	<input type="hidden" id="hddIdClaveMovimiento" name="hddIdClaveMovimiento">
    <table border="0" id="tblClaveMovimiento" width="960">
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
                <td id="tdlstModulo">
                	<select id="lstModulo" name="lstModulo">
                    	<option value="-1">[ Seleccione ]</option>
                    </select>
                </td>
                <td></td>
                <td></td>
                <td align="right" class="tituloCampo">Estatus:</td>
                <td id="tdlstEstatus"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave:</td>
                <td><input type="text" id="txtClave" name="txtClave" size="20"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Descripción:</td>
                <td colspan="3"><input type="text" id="txtDescripcion" name="txtDescripcion" size="50"></td>
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Dcto. Generado:</td>
                <td id="tdlstDctoGenerado"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov.:</td>
                <td>
                	<select id="lstTipoClave" name="lstTipoClave" onchange="xajax_asignarTipoMovimiento(xajax.getFormValues('frmClaveMovimiento'))">
                    	<option value="-1">[ Seleccione ]</option>
                        <option value="1">1.- Compras</option>
                        <option value="2">2.- Entradas</option>
                        <option value="3">3.- Ventas</option>
                        <option value="4">4.- Salidas</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo">Clave Mov.:</td>
                <td id="tdlstClaveMovimiento">
                    <select id="lstClaveMovimiento" name="lstClaveMovimiento">
                        <option>[ Seleccione ]</option>
                    </select>
                </td>
            	<td align="right" class="tituloCampo">Tipo de Pago:</td>
                <td>
                	<label><input id="rbtPagoContado" name="rbtPagoContado" type="checkbox"/> Contado</label>
                    <br>
                    <label><input id="rbtPagoCredito" name="rbtPagoCredito" type="checkbox"/> Crédito</label>
                </td>
            </tr>
            <tr align="left">
	            <td align="right" class="tituloCampo">Numeración Dcto.:</td>
                <td colspan="3">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdNumeracionDocumento" name="txtIdNumeracionDocumento" onblur="xajax_asignarNumeracion(this.value, 'NumeracionDocumento', 'false');" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaNumeracion', 'NumeracionDocumento');">
                            <button type="button" id="btnListarNumeracionDocumento" name="btnListarNumeracionDocumento" title="Listar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNumeracionDocumento" name="txtNumeracionDocumento" readonly="readonly" size="26"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
	            <td align="right" class="tituloCampo">Numeración Control:</td>
                <td colspan="3">
                	<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td><input type="text" id="txtIdNumeracionControl" name="txtIdNumeracionControl" onblur="xajax_asignarNumeracion(this.value, 'NumeracionControl', 'false');" size="6" style="text-align:right"/></td>
                        <td>
                        <a class="modalImg" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaNumeracion', 'NumeracionControl');">
                            <button type="button" id="btnListarNumeracionControl" name="btnListarNumeracionControl" title="Listar"><img src="img/iconos/help.png"/></button>
                        </a>
                        </td>
                        <td><input type="text" id="txtNumeracionControl" name="txtNumeracionControl" readonly="readonly" size="26"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo">Contra Cuenta:</td>
                <td><input type="text" id="txtContraCuenta" name="txtContraCuenta"></td>
            	<td align="right" class="tituloCampo">Prefijo Folio Multiple:</td>
                <td><input type="text" id="txtPrefijoFolioMultiple" name="txtPrefijoFolioMultiple"></td>
            	<td align="right" class="tituloCampo">Área:</td>
                <td><input type="text" id="txtArea" name="txtArea"></td>
            </tr>
            <tr align="left">
            	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>¿Afecta Consumo?:</td>
                <td width="18%">
                	<label><input id="rbtAfectaConsumoSi" name="rbtAfectaConsumo" type="radio" value="1"/> Si</label>
                    <br>
                    <label><input id="rbtAfectaConsumoNo" name="rbtAfectaConsumo" type="radio" value="0" checked="checked"/> No</label>
				</td>
            	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Prov. Individual:</td>
                <td width="22%">
                	<label><input id="rbtProvIndividualSi" name="rbtProvIndividual" type="radio" value="1"/> Si</label>
                    <br>
                    <label><input id="rbtProvIndividualNo" name="rbtProvIndividual" type="radio" value="0" checked="checked"/> No</label>
				</td>
            	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Estadistica:</td>
                <td width="18%">
                    <label><input id="rbtEstadisticaSi" name="rbtEstadistica" type="radio" value="1"/> Si</label>
                    <br>
                    <label><input id="rbtEstadisticaNo" name="rbtEstadistica" type="radio" value="0" checked="checked"/> No</label>
				</td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="submit" id="btnGuardarClaveMovimiento" name="btnGuardarClaveMovimiento" onclick="validarFrmClaveMovimiento();">Guardar</button>
            <button type="button" id="btnCancelarClaveMovimiento" name="btnCancelarClaveMovimiento" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <table border="0" id="tblListaNumeracion" width="700">
    <tr>
    	<td>
        <form id="frmBuscarNumeracion" name="frmBuscarNumeracion" style="margin:0" onsubmit="return false;">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarNumeracion" name="txtCriterioBuscarNumeracion" class="inputHabilitado" onkeyup="byId('btnBuscarNumeracion').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarNumeracion" name="btnBuscarNumeracion" onclick="xajax_buscarNumeracion(xajax.getFormValues('frmBuscarNumeracion'));">Buscar</button>
                    <input type="hidden" id="hddObjDestinoNumeracion" name="hddObjDestinoNumeracion"/>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td><div id="divListaNumeracion" style="width:100%"></div></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaNumeracion" name="btnCancelarListaNumeracion" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
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

var lstTipoMovimiento = $.map($("#lstTipoMovimiento option:selected"), function (el, i) { return el.value; });
var lstDctoGeneradoBuscar = $.map($("#lstDctoGeneradoBuscar option:selected"), function (el, i) { return el.value; });
var lstModuloBuscar = $.map($("#lstModuloBuscar option:selected"), function (el, i) { return el.value; });

xajax_cargaLstTipoMovimiento();
xajax_cargaLstDctoGeneradoBuscar("-1");
xajax_cargaLstModuloBuscar();
xajax_cargaLstTipoPago();
xajax_cargaLstEstatusBuscar();
xajax_listaClaveMovimiento(0, "vw_pg_clave_mov.descripcion_modulo", "ASC", lstTipoMovimiento.join() + '|' + lstDctoGeneradoBuscar.join() + '|' + lstModuloBuscar.join());

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>