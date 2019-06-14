<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');
if(!(validaAcceso("te_documentos_aplicados"))) {
	echo "<script>alert('Acceso Denegado'); window.location.href = 'index.php';</script>";
}
/* Fin Validación del Módulo */

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_te_documentos_aplicados.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Documentos Para Aplicar</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
		
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css" />
    <script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
	<script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblListaBanco').style.display = 'none';
		byId('tblListaAuditoria').style.display = 'none';
		byId('tblAplicacionDesaplicacion').style.display = 'none';
		
		if (verTabla == "tblListaBanco") {			
			document.forms['frmBuscarBanco'].reset();
			
			xajax_listaBanco();
			
			tituloDiv1 = 'Bancos';
		} else if (verTabla == "tblListaAuditoria") {			
			xajax_listaAuditoria(0,'','',valor);
			
			tituloDiv1 = 'Comentarios';
		} else if (verTabla == "tblAplicacionDesaplicacion") {
			document.forms['frmAplicarDesaplicarDcto'].reset();
			
			xajax_formAplicarDesaplicarDcto(valor, valor2);
			
			if (valor2 == 1) {
				tituloDiv1 = 'Desaplicación de documento';
			} else {
				tituloDiv1 = 'Aplicación de documento';
			}			
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblListaBanco") {			
			byId('txtCriterioBuscarBanco').focus();
			byId('txtCriterioBuscarBanco').select();
		} else if (verTabla == "tblListaAuditoria") {		
		} else if (verTabla == "tblAplicacionDesaplicacion") {
			byId('txtObservacion').focus();
			byId('txtObservacion').select();
		}
	}
	
	function validarAplicarBloque(){
		if($('input[name="cbxItmDesaplicado[]"]:checked').length == 0){
			return alert('Debe seleccionar al menos un registro');
		}
		
		if (confirm('¿Seguro desea aplicar este registro?') == true) {
			xajax_aplicarBloque(xajax.getFormValues('frmListaEstadoCuenta'));
		}
	}
	
	function validarDesaplicarBloque(){
		if($('input[name="cbxItmAplicado[]"]:checked').length == 0){
			return alert('Debe seleccionar al menos un registro');
		}
		
		if (confirm('¿Seguro desea desaplicar este registro?') == true) {
			xajax_desaplicarBloque(xajax.getFormValues('frmListaEstadoCuenta1'));
		}
	}
	
	function validarFormAplicarDesaplicarDcto() {		
		acc = byId('hddAccAplicarDesaplicar').value;
		
		if(acc == 1){//DESAPLICAR
			if (validarCampo('txtObservacion','t','') == true) {
				xajax_desaplicarEstadoCuenta(xajax.getFormValues('frmAplicarDesaplicarDcto'));
			} else {
				validarCampo('txtObservacion','t','');
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		} else if(acc == 2) {//APLICAR
			if (validarCampo('txtFechaAplicar','t','') == true
			&& validarCampo('txtObservacion','t','') == true) {
				xajax_aplicarEstadoCuenta(xajax.getFormValues('frmAplicarDesaplicarDcto'));
			} else {
				validarCampo('txtFechaAplicar','t','');
				validarCampo('txtObservacion','t','');
				
				alert("Los campos señalados en rojo son requeridos");
				return false;
			}
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_tesoreria.php"); ?></div>
	
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaTesoreria">Aplicar Documentos</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td align="right">
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" align="left"></td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Banco:</td>
                    <td align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/>
                                    <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                                </td>
                                <td>
									<a onclick="abrirDivFlotante1(this, 'tblListaBanco');" rel="#divFlotante1" id="aListarBanco" class="modalImg"><button title="Listar" type="button"><img src="../img/iconos/help.png"></button>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="tituloCampo" align="right" width="120">Nro. Cuenta:</td>
                    <td id="tdLstCuenta" align="left"></td>
                    <td>
						<button type="button" id="btnBuscar" name="btnBuscar" onclick="byId('btnBuscarDesaplicado').click(); byId('btnBuscarAplicado').click();">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
				</tr>		
                </table>
                </form>
            </td>
        </tr>
        </table>
        
        <table width="100%">
        <tr>
        	<td valign="top" width="50%">
            	<form id="frmListaEstadoCuenta" name="frmListaEstadoCuenta">
            	<fieldset><legend class="legend">Documentos Por Aplicar</legend>
                <table width="100%">
                <tr>
                	<td>
                        <table align="left" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <button type="button" id="btnActivar" name="btnActivar" onclick="validarAplicarBloque();" >
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_agregar.gif"/></td><td>&nbsp;</td><td>Aplicar</td></tr>
                                    </table>
                                </button>
                            </td>
                        </tr>
                        </table>
                    
                        <table align="right" border="0">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Fecha:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;Desde:&nbsp;</td>
                                    <td><input type="text" id="txtFechaDesde1" name="txtFechaDesde1" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                                    <td>&nbsp;Hasta:&nbsp;</td>
                                    <td><input type="text" id="txtFechaHasta1" name="txtFechaHasta1" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" name="txtCriterioDesaplicado" id="txtCriterioDesaplicado" onkeyup="byId('btnBuscarDesaplicado').click();" class="inputHabilitado"/></td>
                            <td>
                                <button type="button" id="btnBuscarDesaplicado" name="btnBuscarDesaplicado" onclick="xajax_buscarEstadoCuentaDesaplicado(xajax.getFormValues('frmListaEstadoCuenta'), byId('lstCuenta').value);">Buscar</button>
                                <button type="button" onclick="document.forms['frmListaEstadoCuenta'].reset(); byId('btnBuscarDesaplicado').click();">Limpiar</button>
                            </td>
                        </tr>		
                        </table>
                    </td>
                </tr>
            	<tr>
        			<td id="tdListaEstadoCuentaDesaplicado"></td>
                </tr>
                </table>
            	</fieldset>
                </form>
            </td>
            <td valign="top" width="50%">
            	<form id="frmListaEstadoCuenta1" name="frmListaEstadoCuenta1">
            	<fieldset><legend class="legend">Documentos Aplicados</legend>
                <table width="100%">
                <tr>
                	<td>
                        <table align="left" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <button type="button" id="btnActivar" name="btnActivar" onclick="validarDesaplicarBloque();" >
                                    <table align="center" cellpadding="0" cellspacing="0">
                                        <tr><td>&nbsp;</td><td><img class="puntero" src="../img/iconos/ico_quitar.gif"/></td><td>&nbsp;</td><td>Desaplicar</td></tr>
                                    </table>
                                </button>
                            </td>
                        </tr>
                        </table>
                        
                        <table align="right" border="0">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Fecha:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>&nbsp;Desde:&nbsp;</td>
                                    <td><input type="text" id="txtFechaDesde2" name="txtFechaDesde2" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                                    <td>&nbsp;Hasta:&nbsp;</td>
                                    <td><input type="text" id="txtFechaHasta2" name="txtFechaHasta2" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                            <td><input type="text" name="txtCriterioAplicado" id="txtCriterioAplicado" onkeyup="byId('btnBuscarAplicado').click();" class="inputHabilitado"/></td>
                            <td>
                                <button type="button" id="btnBuscarAplicado" name="btnBuscarAplicado" onclick="xajax_buscarEstadoCuentaAplicado(xajax.getFormValues('frmListaEstadoCuenta1'), byId('lstCuenta').value);">Buscar</button>
                                <button type="button" onclick="document.forms['frmListaEstadoCuenta1'].reset(); byId('btnBuscarAplicado').click();">Limpiar</button>
                            </td>
                        </tr>		
                        </table>
                    </td>
                </tr>
		            <td id="tdListaEstadoAplicado"></td>
                </tr>
                </table>
             	</fieldset>
                </form>
            </td>
        </tr>
         <tr>
        	<td colspan="2">
	            <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                	<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                	<td align="center">
                    	<table>
                        <tr>
                            <td><img src="../img/iconos/ico_rojo.gif" /></td>
                            <td>Por Aplicar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif" /></td>
                            <td>Aplicado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif" /></td>
                            <td>Concialiado</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td colspan="2">
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                	<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                	<td align="center">
                    	<table>
                        <tr>
                            <td><img src="../img/iconos/ico_agregar.gif" /></td>
                            <td>Aplicar Documentos</td>
                            <td><img src="../img/iconos/ico_quitar.gif" /></td>
                            <td>Desaplicar Documentos</td>
                            <td><img src="../img/iconos/ico_comentario.png" /></td>
                            <td>Comentarios</td>
                            <td><img src="../img/iconos/ico_comentario_f2.png" /></td>
                            <td>Sin Comentarios</td>
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
        
    <table border="0" id="tblListaBanco" style="display:none" width="610">
    <tr>
        <td>
            <form onsubmit="return false;" style="margin:0" name="frmBuscarBanco" id="frmBuscarBanco">
                <table align="right">
                <tr align="left">
                    <td width="120" align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" onkeyup="byId('btnBuscarBanco').click();" class="inputHabilitado" name="txtCriterioBuscarBanco" id="txtCriterioBuscarBanco"></td>
                    <td>
                        <button onclick="xajax_buscarBanco(xajax.getFormValues('frmBuscarBanco'));" name="btnBuscarBanco" id="btnBuscarBanco" type="button">Buscar</button>
                        <button onclick="document.forms['frmBuscarBanco'].reset(); byId('btnBuscarBanco').click();" type="button">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListaBanco"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarBanco" name="btnCancelarBanco" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    
    <table border="0" id="tblListaAuditoria" style="display:none" width="610">
    <tr>
        <td id="tdListaAuditoria"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarAuditoria" name="btnCancelarAuditoria" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
        
    <form id="frmAplicarDesaplicarDcto" name="frmAplicarDesaplicarDcto" onsubmit="return false;" style="margin:0px">
    <table border="0" id="tblAplicacionDesaplicacion" style="display:none" width="490px">
    <tr align="left">
    	<td>
        	<table width="100%">
            <tr>
            	<td align="right" class="tituloCampo">Nro Documento:</td>
                <td>
                	<input type="text" id="txtNroDocumento" name="txtNroDocumento" readonly="readonly" />
                </td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo">Tipo Documento:</td>
                <td>
                	<input type="text" id="txtTipoDocumento" name="txtTipoDocumento" readonly="readonly" />
                </td>
            </tr>
            <tr>
            	<td align="right" class="tituloCampo">Fecha Registro:</td>
                <td>
                	<input type="text" id="txtFechaRegistro" name="txtFechaRegistro" readonly="readonly" />
                </td>
            </tr>
            <tr>            
            	<td align="right" class="tituloCampo" >Fecha Aplicado:</td>
                <td>
                	<input type="text" id="txtFechaAplicado" name="txtFechaAplicado" readonly="readonly" />
                </td>
            </tr>
            <tr id="trFechaAplicar" style="display: none;">            
            	<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fecha a Aplicar:</td>
                <td>
                	<input type="text" id="txtFechaAplicar" name="txtFechaAplicar" readonly="readonly" class="inputHabilitado" />
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" ><span class="textoRojoNegrita">*</span>Ingrese Observación:</td>
                <td>
                    <input type="hidden" id="hddAccAplicarDesaplicar" name="hddAccAplicarDesaplicar"/>
                    <input type="hidden" id="hddIdEstadoCuenta" name="hddIdEstadoCuenta"/>
                	<textarea  id="txtObservacion" name="txtObservacion" cols="45" rows="5" class="inputHabilitado"></textarea>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnGuardarAplicarDesaplicarDcto" name="btnGuardarAplicarDesaplicarDcto" onclick="validarFormAplicarDesaplicarDcto();">Aceptar</button>
            <button type="button" id="btnCancelarAplicarDesaplicarDcto" name="btnCancelarAplicarDesaplicarDcto" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    </form>
</div>

<script>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',"onchange=\"byId('btnBuscar').click();\"");
xajax_cargaLstCuenta();

$("#txtFechaDesde1").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta1").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaDesde2").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta2").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde1",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});
	
new JsDatePick({
	useMode:2,
	target:"txtFechaHasta1",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde2",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});
	
new JsDatePick({
	useMode:2,
	target:"txtFechaHasta2",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaAplicar",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

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

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);
</script>