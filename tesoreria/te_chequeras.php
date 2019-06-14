<?php

require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("te_chequeras"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_chequeras.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Mantenimiento de Chequeras</title>
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
		if (verTabla == 'tblChequera') {
			document.forms['frmChequera'].reset();
			byId('hddIdChequera').value = '';
			
			xajax_formChequera(valor);
			
			if (valor > 0) {
				tituloDiv1 = 'Ver Chequera';
			} else {
				tituloDiv1 = 'Nueva Chequera';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo').innerHTML = tituloDiv1;
	}
	
	function validarChequera(){
		if (validarCampo('selBancoNuevaChequera','t','lista') == true
		&&  validarCampo('selCuentaNuevaChequera','t','lista') == true
		&&  validarCampo('txtNumeroInicial','t','cantidad') == true
		&&  validarCampo('txtNumeroFinal','t','cantidad') == true){
			if (parseFloat(byId('txtNumeroInicial').value) < parseFloat(byId('txtNumeroFinal').value)) {
				xajax_guardarChequera(xajax.getFormValues('frmChequera'));
			} else {
				alert("Numeros Invalidos");
				byId('txtNumeroInicial').className = "inputErrado";
				byId('txtNumeroFinal').className = "inputErrado";
			}
		} else {
			validarCampo('selBancoNuevaChequera','t','lista');
			validarCampo('selCuentaNuevaChequera','t','lista');
			validarCampo('txtNumeroInicial','t','cantidad');
			validarCampo('txtNumeroFinal','t','cantidad');
						
			alert("Los campos señalados en rojo son requeridos");
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
			<td class="tituloPaginaTesoreria" colspan="2">Mantenimiento de Chequeras</td>
		</tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
		<tr class="noprint">
			<td>
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="abrirDivFlotante1(this, 'tblChequera');">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
					</td>
				</tr>
				</table>
                
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                <table align="right" border="0">
                <tr>
                    <td align="right" class="tituloCampo" width="120">Banco:</td>
                    <td id="tdSelBancos" align="left"></td>
                    <td align="right" class="tituloCampo" width="120">Cuenta:</td>
                    <td id="tdSelCuentasBusq" align="left"></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Estado:</td>
                    <td align="left">
                        <select id="lstEstado" name="lstEstado" onchange="byId('btnBuscar').click();" class="inputHabilitado">
                            <option value="-1">[ Todos ]</option>
                            <option value="SI">Activo</option>
                            <option value="NO">Inactivo</option>
                        </select>
                    </td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                    <td><button id="btnBuscar" name="btnBuscar" type="button" class="noprint" onclick="xajax_buscarChequera(xajax.getFormValues('frmBuscar'));" >Buscar</button>									
                    </td>
                    <td>
                        <button type="button" class="noprint" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();" >Limpiar</button>
                    </td>
                </tr>
                </table>
                </form>
            </td>
        </tr>    
        <tr>
			<td id="tdListaChequeras"></td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                <tr>
                    <td width="25"></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_verde.gif"/></td>
                            <td>Activo</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td>
                            <td>Inactivo</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellspacing="0" cellpadding="0" class="divMsjInfo2">
                <tr>
                    <td width="25"></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_view.png"/></td>
                            <td>Ver Chequera</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_quitar.gif"/></td>
                            <td>Anular Chequera</td>
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
    <form id="frmChequera" name="frmChequera">
    <table border="0" id="tblChequera" width="700px">
    <tr align="left">
    	<td>
            <table border="0">
            <tr id="tr1">
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Banco:</td>
                <td id="tdSelBancoNuevaChequera">
                    <select id="selBancoNuevaChequera" name="selBancoNuevaChequera">
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nro de cuenta:</td>
                <td id="tdSelCuentas">
                    <select id="selCuentaNuevaChequera" name="selCuentaNuevaChequera" disabled="disabled">
                    </select>
                </td>
            </tr>
            <tr id="tr2">
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Número inicial:</td>
                <td><input type="text" id="txtNumeroInicial" name="txtNumeroInicial" onkeypress="return validarSoloNumeros(event);"/></td>
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Número final:</td>
                <td><input type="text" id="txtNumeroFinal" name="txtNumeroFinal" onkeypress="return validarSoloNumeros(event);"/></td>
            </tr>
            <tr id="tr3">
                <td align="right" class="tituloCampo" width="120">Chequera activa:</td>
                <td>
                    <select id="selChequeraActiva" name="selChequeraActiva" onchange="selectedOption(this.id, 'SI');">
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                    </select>
                </td>
                <td id="td1" align="right" class="tituloCampo" width="120">Último nro de cheque:</td>
                <td id="td2">
                    <input type="text" id="txtUltimoNumeroCheque" name="txtUltimoNumeroCheque" readonly="readonly"/>
                    <input type="hidden" id="hddIdChequera" name="hddIdChequera" />
                </td>
            </tr>
            <tr id="tr4">
                <td align="right" class="tituloCampo" width="120">Impresos:</td>
                <td><input type="text" id="txtImpresos" name="txtImpresos" readonly="readonly"/></td>
                <td align="right" class="tituloCampo" width="120">Anulados:</td>
                <td><input type="text" id="txtAnulados" name="txtAnulados" readonly="readonly"/></td>
            </tr>
            <tr id="tr5">
                <td align="right" class="tituloCampo" width="120">Disponibles:</td>
                <td><input type="text" id="txtDisponibles" name="txtDisponibles" readonly="readonly"/></td>
                <td align="right" class="tituloCampo" width="120">Cantidad de cheques:</td>
                <td><input type="text" id="txtCantidadCheque" name="txtCantidadCheque" readonly="readonly"/></td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
        <td align="right"><hr>
        	<button type="button" id="btnGuardar" onclick="validarChequera();">Guardar</button>
            <button type="button" id="btnCancelar" class="close">Cancelar</button>
        </td>
    </tr>
	</table>
    </form>
</div>

<script language="javascript">
xajax_comboBancos(0,"tdSelBancos","selBancos","byId('btnBuscar').click(); xajax_comboCuentasBusq(this.value);");
xajax_comboCuentasBusq(0);
xajax_listaChequeras(0,'','');


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