<?php

require_once ("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("te_cuentas"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

include("controladores/ac_te_cuentas.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Cuentas</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    <link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <!--<script type="text/javascript" language="javascript" src="../js/jquery.maskedinput.js" ></script>-->
    
    <script>
	
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		if (valor == 'nuevo') {
			document.forms['frmCuenta'].reset();
			byId('hddIdCuenta').value = 0;
			byId('txtSaldoLibros').value = 0;
			byId('txtSaldoAnteriorConciliado').value = 0;			
			$('#frmCuenta').find('input,select').not('#txtProximoNroCheque').attr("class","inputHabilitado");
			$('#frmCuenta').find('input').not('#txtProximoNroCheque').attr('readonly', false);
			
			xajax_nuevaCuenta();
			
			tituloDiv1 = 'Nueva Cuenta';
		} else if (valor == 'ver') {
			xajax_verCuenta(valor2, 1);
			$('#frmCuenta').find('input,select').not('#txtProximoNroCheque').attr("class","inputInicial");
			$('#frmCuenta').find('input').not('#txtProximoNroCheque').attr('readonly', true);			
									
			tituloDiv1 = 'Ver Cuenta';
		} else if (valor == 'editar') {
			xajax_verCuenta(valor2, 2);
			$('#frmCuenta').find('input,select').not('#txtProximoNroCheque').attr("class","inputHabilitado");
			$('#frmCuenta').find('input').not('#txtProximoNroCheque').attr('readonly', false);
			
			tituloDiv1 = 'Editar Cuenta';
		}
		
		openImg(nomObjeto);
		byId('tdFlotanteTitulo').innerHTML = tituloDiv1;
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblComisionPunto').style.display = 'none';
		
		if (verTabla == "tblComisionPunto") {			
			document.forms['frmTarjeta'].reset();
			
			if (valor == 'nuevo') {
				xajax_agregarNuevaTarjeta(valor2);
				
				tituloDiv2 = 'Agregar Tarjeta';			
			} else if (valor == 'editar') {
				xajax_editarTarjeta(valor2);
				
				tituloDiv2 = 'Editar Tarjeta';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblComisionPunto") {			
			byId('txtComision').focus();
			byId('txtComision').select();
		}
	}
	
	function validarFrmCuenta(){
		if (validarCampo('selBancoCuentaNueva','t','lista') == true
		&&  validarCampo('txtNumeroCuenta','t','numeroCuenta') == true
		&&  validarCampo('selTipoCuenta','t','lista') == true 
		&&  validarCampo('selMonedas','t','lista') == true 
		&&  validarCampo('txtSaldoLibros','t','') == true
		&&  validarCampo('txtSaldoAnteriorConciliado','t','') == true
		&&  validarCampo('selEstatus','t','listaExceptCero') == true 
		){
			xajax_guardarCuenta(xajax.getFormValues('frmCuenta'));
		} else {
			validarCampo('selBancoCuentaNueva','t','lista')
			validarCampo('txtNumeroCuenta','t','numeroCuenta')
			validarCampo('selTipoCuenta','t','lista')
			validarCampo('selMonedas','t','lista')
			validarCampo('txtSaldoLibros','t','')
			validarCampo('txtSaldoAnteriorConciliado','t','')
			validarCampo('selEstatus','t','listaExceptCero')
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function verRetencionPunto(check){
		if(check.checked){
			byId('tblpunto').style.display = '';				
		}else{
			byId('tblpunto').style.display = 'none';	
		}
	}
		
	function validarFormTarjeta(){
		if (validarCampo('selTarjeta','t','lista') == true
		&& validarCampo('txtComision','t','') == true){
			
		} else {
			validarCampo('selTarjeta','t','lista');
			validarCampo('txtComision','t','');
			
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
			<td class="tituloPaginaTesoreria" colspan="2">Cuentas</td>
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
                    <td align="right" class="tituloCampo" width="120">Empresa/Sucursal:</td>
                    <td id="tdSelEmpresa" align="left">
                        <select>
                            <option>Seleccione</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Banco:</td>
                    <td id="tdSelBancos"></td>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterio" name="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
                    <td><button id="btnBuscar" name="btnBuscar" type="button" class="noprint" onclick="xajax_buscarCuenta(xajax.getFormValues('frmBuscar'));" >Buscar</button>									
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
            <td id="tdListaCuentas"></td>
        </tr>
		</table>
    </div>
	<div class="noprint"><?php include("pie_pagina.php") ?></div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    <form id="frmCuenta" name="frmCuenta">
    <table border="0" id="tblCuenta" width="800px">
    <tr align="left">
    	<td>
            <table border="0">
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Banco:</td>
                <td id="tdSelBancoCuentaNueva">
                    <select id="selBancoCuentaNueva" name="selBancoCuentaNueva">
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Numero de Cuenta:</td>
                <td>
                    <input type="text" id="txtNumeroCuenta" name="txtNumeroCuenta" size="25"  />
                    <input type="hidden" id="hddIdCuenta" name="hddIdCuenta" />
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Tipo Cuenta:</td>
                <td align="left">
                    <select id="selTipoCuenta" name="selTipoCuenta">
                        <option value="Corriente">Corriente</option>
                        <option value="Ahorro">Ahorro</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="50%">Posee Punto de Venta</td>
                <td><input type="checkbox" id="cbxItm" onclick="verRetencionPunto(this)"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firma Electronica:</td>
                <td><input type="text" id="txtFirmaElectronica" name="txtFirmaElectronica" size="30" /></td>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Moneda</td>
                <td align="left" id="tdSelMonedas">
                    <select id="selMonedas" name="selMonedas">
                    </select>
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Aplicar Debito Bancario:</td>
                <td>
                    <select id="selAplicaDebito" name="selAplicaDebito">
                        <option value="0" selected="selected">NO</option>
                        <option value="1">SI</option>
                    </select>
                </td>
                <td align="right" class="tituloCampo" width="50%">Cuenta Debitos Bancarios:</td>
                <td><input type="text" id="txtCuentaDebitosBancarios" name="txtCuentaDebitosBancarios"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Saldo Anterior Conciliado:</td>
                <td><input type="text" id="txtSaldoLibros" name="txtSaldoLibros" onkeypress="return validarSoloNumerosReales(event);" /></td>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Saldo en Libros:</td>
                <td><input type="text" id="txtSaldoAnteriorConciliado" name="txtSaldoAnteriorConciliado" onkeypress="return validarSoloNumerosReales(event);"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Proximo nro Cheque:</td>
                <td><input type="text" id="txtProximoNroCheque" name="txtProximoNroCheque" readonly="readonly"/></td>
                <td align="right" class="tituloCampo" width="50%"><span class="textoRojoNegrita">*</span>Estatus</td>
                <td>
                    <select id="selEstatus" name="selEstatus">
                        <option value="1">Activa</option>
                        <option value="0">Inactiva</option>
                    </select>                    
                </td>
            </tr>
            <tr>
                <td>&ensp;</td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 1:</td>
                <td><input type="text" id="txtFirmante1" name="txtFirmante1"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 1:</td>
                <td><input type="text" id="txtTipoFirmante1" name="txtTipoFirmante1"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 2:</td>
                <td><input type="text" id="txtFirmante2" name="txtFirmante2"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 2:</td>
                <td><input type="text" id="txtTipoFirmante2" name="txtTipoFirmante2"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 3:</td>
                <td><input type="text" id="txtFirmante3" name="txtFirmante3"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 3:</td>
                <td><input type="text" id="txtTipoFirmante3" name="txtTipoFirmante3"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 4:</td>
                <td><input type="text" id="txtFirmante4" name="txtFirmante4"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 4:</td>
                <td><input type="text" id="txtTipoFirmante4" name="txtTipoFirmante4"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 5:</td>
                <td><input type="text" id="txtFirmante5" name="txtFirmante5"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 5:</td>
                <td><input type="text" id="txtTipoFirmante5" name="txtTipoFirmante5"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Firmante 6:</td>
                <td><input type="text" id="txtFirmante6" name="txtFirmante6"/></td>
                <td align="right" class="tituloCampo" width="50%">Tipo Firmante 6:</td>
                <td><input type="text" id="txtTipoFirmante6" name="txtTipoFirmante6"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Combinacion 1:</td>
                <td><input type="text" id="txtCombinacion1" name="txtCombinacion1"/></td>
                <td align="right" class="tituloCampo" width="50%">Restriccion Combinacion 1:</td>
                <td><input type="text" id="txtRestriccionCombinacion1" name="txtRestriccionCombinacion1"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Combinacion 2:</td>
                <td><input type="text" id="txtCombinacion2" name="txtCombinacion2"/></td>
                <td align="right" class="tituloCampo" width="50%">Restriccion Combinacion 2:</td>
                <td><input type="text" id="txtRestriccionCombinacion2" name="txtRestriccionCombinacion2"/></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="50%">Combinacion 3:</td>
                <td><input type="text" id="txtCombinacion3" name="txtCombinacion3"/></td>
                <td align="right" class="tituloCampo" width="50%">Restriccion Combinacion 3:</td>
                <td><input type="text" id="txtRestriccionCombinacion3" name="txtRestriccionCombinacion3"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td id="tblpunto"></td>
    </tr>  
    <tr align="right">
    	<td align="right"><hr>
        	<button style="display:none" type="button" id="btnGuardar" onclick="validarFrmCuenta();">Guardar</button>
            <button type="button" id="btnCancelar" class="close">Cancelar</button>
		</td>
    </tr>   
    </table>
    </form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:2;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%">Agregar Tarjeta</td></tr></table></div>
	<form id="frmTarjeta" name="frmTarjeta" onsubmit="return false;">
	<table border="0" id="tblComisionPunto">
    <tr>
    	<td>
            <table border="0">
            <tr>
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Tarjeta</td>
                <td id="tdSelTarjetas" align="left"></td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Comision:</td>
                <td>
                    <label>
                        <input name="txtComision" id="txtComision" type="text"/>
                    </label>
                    <input type="hidden" id="hddId" name="hddId"/>
                </td>
            </tr>    
            <tr>
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>ISLR:</td>
                <td>
                    <label>
                        <input name="txtISLR" id="txtISLR" type="text"/>
                    </label>
                </td>
            </tr>
            </table>
		</td>
	</tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="GuardarModifica" name="" onclick="xajax_guardarCambioTarjeta(xajax.getFormValues('frmTarjeta'));">Guardar</button>
            <button type="button" id="GuardarNuevo" name="" onclick="xajax_insertarNuevaTarjeta(xajax.getFormValues('frmTarjeta'));">Guardar</button>
            <button type="button" id="btnCancelarTarjeta" class="close">Cancelar</button>
        </td>
    </tr>
	</table>
    </form>
</div>

<script language="javascript">
//$("#customer_phone").mask("9999-9999-99-9999999999",{placeholder:" "});               

xajax_comboBancos(0,"tdSelBancos","selBancos","byId('btnBuscar').click();");
xajax_listaCuentas(0,'','', <?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>);
xajax_comboEmpresa();

//byId('btnBuscar').click();

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
	
var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);        
</script>