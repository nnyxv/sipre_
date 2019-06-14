<?php

require("../connections/conex.php");
session_start();
require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de scritp
$xajax->configure('javascript URI', '../controladores/xajax/');

require("controladores/ac_te_general.php");
require("controladores/ac_te_propuesta_pago_tr.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Transferencia</title>
	<link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>   
	<link rel="stylesheet" type="text/css" href="../js/domDragTesoreria.css"/>
    
    <script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquery.js" ></script>
    <script type="text/javascript" language="javascript" src="../js/jquery.maskedinput.js" ></script>
    <script>
    jQuery.noConflict();
            jQuery(function($){
                //$("#txtNumCuenta").mask("9999-9999-99-9999999999",{placeholder:" "}); panama no usa
            });
    </script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
<style type="text/css">        
	.tablaStripped tr:nth-child(odd) {		
		background-color: #FFF4F4;
	}
	
</style>
<script>	
	function validarMonto(){
		if (parseFloat($('txtMontoAPagar').value) > parseFloat($('txtSaldoFactura').value)){
			alert("El monto a pagar no puede ser mayor que el saldo de la factura");
			$('btnAceptar').disabled = 'disabled';
			//$('txtMontoAPagar').focus();
			return false;
		}else{
			$('btnAceptar').disabled = '';
		}
	}
	
    function number_format( number, decimals, dec_point, thousands_sep ){
		var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
		var d = dec_point == undefined ? "," : dec_point;
		var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
		var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}
	
	function calcularRetencion(){
		if ((parseFloat($('txtSaldoFactura').value) >= parseFloat($('hddMontoMayorAplicar').value)) && ($('hddPorcentajeRetencion').value > 0) ){
			$('tdTextoRetencionISLR').style.display = '';
			$('tdMontoRetencionISLR').style.display = '';
			
			if ($('hddIva').value == 0){
				var monto_retencion = ($('txtSaldoFactura').value * ($('hddPorcentajeRetencion').value / 100))-( $('hddSustraendoRetencion').value);
				$('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
				var monto = (parseFloat($('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
				$('txtMontoAPagar').value = number_format(monto,'2','.','');
			}else{
				var monto_retencion = ($('hddBaseImponible').value * ($('hddPorcentajeRetencion').value / 100))-( $('hddSustraendoRetencion').value);
				$('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
				var monto = (parseFloat($('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
				$('txtMontoAPagar').value = number_format(monto,'2','.','');
			}
		}else{
			$('tdTextoRetencionISLR').style.display = 'none';
			$('tdMontoRetencionISLR').style.display = 'none';
			$('txtMontoAPagar').value = $('txtSaldoFactura').value;
			$('txtMontoRetencionISLR').value = 0;
		}
	}
        
	//gregor, SE USA AL CAMBIAR EL MONTO A PAGAR, PARA QUE PAGUE EL MONTO Y NO LA BASE
	function calcularRetencionMano(){
		if ((parseFloat($('txtSaldoFactura').value) >= parseFloat($('hddMontoMayorAplicar').value)) && ($('hddPorcentajeRetencion').value > 0) ){
			
			var monto_retencion = ($('txtMontoAPagar').value * ($('hddPorcentajeRetencion').value / 100))-( $('hddSustraendoRetencion').value);
			$('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
			//var monto = (parseFloat($('txtSaldoFactura').value))-(parseFloat(monto_retencion));
			//$('txtMontoAPagar').value = number_format(monto,'2','.','');
		}
		
	}
        
	//gregor SI SE USA LA BASE CALCULAR RESPECTO A LA BASE NO SE USA AQUI
	function calcularConBase(){
		var monto_retencion = ($('hddBaseImponible').value * ($('hddPorcentajeRetencion').value / 100))-( $('hddSustraendoRetencion').value);
		$('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');   
		var monto = (parseFloat($('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
		$('txtMontoAPagar').value = number_format(monto,'2','.','');
	}
	
	function validar(){
		
		if (validarCampo('txtMontoAPagar','t','') == true && $('txtMontoAPagar').value >= 0){
			var cadena1 = $('hddMontoPropuesta').value;
			var cadena2 = $('txtMontoAPagar').value;
			var cadena3 = $('txtDiferido').value;
			var Diferido = $('hddDiferido').value;
			
			
			if ($('hddMontoPropuesta').value == ''){
				if (parseFloat(cadena2)+ parseFloat(cadena3)> $('hddSaldoCuenta').value && $('hddPermiso').value == 0){
					if (confirm('El monto de la propuesta es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
						$('divFlotante2').style.display = '';
						centrarDiv($('divFlotante2'));
						$('tdFlotanteTitulo2').innerHTML = 'Aprobación';
						return false;
					}else{
						return false;
					}
				}
				$('hddMontoPropuesta').value = parseFloat(cadena2);
				$('hddDiferido').value = parseFloat(cadena2) + parseFloat(Diferido);
				$('txtMontoPropuesta').value = number_format(cadena2,2,'.',',');
				$('txtDiferido').value = number_format(parseFloat(cadena2)+parseFloat(Diferido),2,'.',',');
				
			}else{
				sum = parseFloat(cadena2)+parseFloat(Diferido);
				saldo = $('hddSaldoCuenta').value;
				if (sum > saldo && $('hddPermiso').value == 0){
					if (confirm('El monto de la propuesta es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
						$('divFlotante2').style.display = '';
						centrarDiv($('divFlotante2'));
						$('tdFlotanteTitulo2').innerHTML = 'Aprobación';
						return false;
					}else{
						return false;
					}
				}
				$('hddMontoPropuesta').value = parseFloat(cadena1) + parseFloat(cadena2);
				$('hddDiferido').value = parseFloat(Diferido) + parseFloat(cadena2);
				$('txtMontoPropuesta').value = number_format(parseFloat(cadena2)+parseFloat(cadena1),2,'.',',');
				$('txtDiferido').value = number_format(parseFloat(cadena2)+parseFloat(Diferido),2,'.',',');										
							
			}
			
			$('arrayIdFactura').value += '|' + $('hddIdFactura').value;
			$('arrayTipoDocumento').value += '|' + $('hddTipoDocumento').value;
			$('arrayMonto').value += '|' + $('txtMontoAPagar').value;
			$('arraySustraendoRetencion').value += ($('hddSustraendoRetencion').value == '') ? '|' + 0 : '|' + $('hddSustraendoRetencion').value;
			$('arrayPorcentajeRetencion').value += ($('hddPorcentajeRetencion').value == '') ? '|' + 0 : '|' + $('hddPorcentajeRetencion').value;
			$('arrayMontoRetenido').value += ($('txtMontoRetencionISLR').value == '') ? '|' + 0 : '|' + $('txtMontoRetencionISLR').value;
			$('arrayCodigoRetencion').value += ($('hddCodigoRetencion').value == '') ? '|' + 0 : '|' + $('hddCodigoRetencion').value;
			$('arrayIdRetencion').value += ($('hddIdRetencion').value == '') ? '|' + 0 : '|' + $('hddIdRetencion').value;
			$('arrayBaseImponibleRetencion').value += ($('hddBaseImponible').value == '') ? '|' + 0 : '|' + $('hddBaseImponible').value;
			$('divFlotante').style.display = 'none';
			
			byId('btnBuscarDocumento').click();//refrescar listado
			
			xajax_asignarFactura(xajax.getFormValues('frmFacturaIndividual'),$('hddIdFactura').value,$('hddTipoDocumento').value);
		 
		 }else{
		 	validarCampo('txtMontoAPagar','t','');
			if ($('txtMontoAPagar').value < 1) $('txtMontoAPagar').className = "inputErrado";
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
	
	function quitarFactura(idFactura){

		var cadenaIdFactura = $('arrayIdFactura').value;
		var arrayIdFactura = cadenaIdFactura.split("|");
		
		var cadenaMonto = $('arrayMonto').value;
		var arrayMonto = cadenaMonto.split("|");
                
		var cadenaTipoDocumento = $('arrayTipoDocumento').value;
		var arrayTipoDocumento = cadenaTipoDocumento.split("|");
		
		var cadenaSustraendoRetencion = $('arraySustraendoRetencion').value;
		var arraySustraendoRetencion = cadenaSustraendoRetencion.split("|");
		
		var cadenaPorcentajeRetencion = $('arrayPorcentajeRetencion').value;
		var arrayPorcentajeRetencion = cadenaPorcentajeRetencion.split("|");
		
		var cadenaMontoRetenido = $('arrayMontoRetenido').value;
		var arrayMontoRetenido = cadenaMontoRetenido.split("|");
				
		var cadenaCodigoRetencion = $('arrayCodigoRetencion').value;
		var arrayCodigoRetencion = cadenaCodigoRetencion.split("|");
				
		var cadenaIdRetencion = $('arrayIdRetencion').value;
		var arrayIdRetencion = cadenaIdRetencion.split("|");
				
		var cadenaBaseImponibleRetencion = $('arrayBaseImponibleRetencion').value;
		var arrayBaseImponibleRetencion = cadenaBaseImponibleRetencion.split("|");
				
		cadenaIdFactura = "";
		cadenaMonto = "";
		cadenaTipoDocumento = "";
		cadenaSustraendoRetencion = "";
		cadenaPorcentajeRetencion = "";
		cadenaMontoRetenido = "";
		cadenaCodigoRetencion = "";
		cadenaIdRetencion = "";
		cadenaBaseImponibleRetencion = "";
		cadenaIdChequera = "";
	
		for(i = 1; i < arrayIdFactura.length; i++){
			if (idFactura != arrayIdFactura[i]+'x'+arrayTipoDocumento[i]){
				cadenaIdFactura += '|' + arrayIdFactura[i];
				cadenaMonto += '|' + arrayMonto[i];
				cadenaTipoDocumento += '|' + arrayTipoDocumento[i];
				cadenaSustraendoRetencion += '|' + arraySustraendoRetencion[i];
				cadenaPorcentajeRetencion += '|' + arrayPorcentajeRetencion[i];
				cadenaMontoRetenido += '|' + arrayMontoRetenido[i];
				cadenaCodigoRetencion += '|' + arrayCodigoRetencion[i];
				cadenaIdRetencion += '|' + arrayIdRetencion[i];
				cadenaBaseImponibleRetencion += '|' + arrayBaseImponibleRetencion[i];
			}else{
				montoRestar = arrayMonto[i];
				//alert(arrayMonto[i]);
				montoPropuesta = $('hddMontoPropuesta').value;
				montoDiferido = $('hddDiferido').value;
				$('hddMontoPropuesta').value = number_format(montoPropuesta - montoRestar,2,'.','');
				$('txtMontoPropuesta').value = number_format(montoPropuesta - montoRestar,2,'.',',');
				$('hddDiferido').value = number_format(montoDiferido - montoRestar,2,'.','');
				$('txtDiferido').value = number_format(montoDiferido - montoRestar,2,'.',',');
			}
		}

		$('arrayIdFactura').value = cadenaIdFactura;
		$('arrayMonto').value = cadenaMonto;
		$('arrayTipoDocumento').value = cadenaTipoDocumento;
		$('arraySustraendoRetencion').value = cadenaSustraendoRetencion;
		$('arrayPorcentajeRetencion').value = cadenaPorcentajeRetencion;
		$('arrayMontoRetenido').value = cadenaMontoRetenido;
		$('arrayCodigoRetencion').value = cadenaCodigoRetencion;
		$('arrayIdRetencion').value = cadenaIdRetencion;
		$('arrayBaseImponibleRetencion').value = cadenaBaseImponibleRetencion;
	}
	
	function confirmarCambio(prove_o_banco){
		if($('cbxItm').checked){
			$('cbxItm').checked = '';
		}
				
		if($('arrayIdFactura').value != ""){
			 if(confirm("Si selecciona otro " + prove_o_banco + " se borraran las facturas seleccionadas de la propuesta de pago desea continuar?")){
				if(prove_o_banco == "Proveedor"){
					xajax_buscarCliente();
				}else{
					xajax_listBanco();
				}
	
				$('cbxItm').click();
				$('arrayIdFactura').value = "";
				$('arrayMonto').value = "";
				$('arrayTipoDocumento').value = "";
				$('arraySustraendoRetencion').value = "";
				$('arrayPorcentajeRetencion').value = "";
				$('arrayMontoRetenido').value = "";
				$('arrayCodigoRetencion').value = "";
				$('arrayIdRetencion').value = "";
				$('arrayBaseImponibleRetencion').value = "";
				$('txtMontoPropuesta').value = 0;
				$('hddMontoPropuesta').value = 0;
				xajax_eliminarFactura(xajax.getFormValues('frmFacturas'));
			}
		}else{
			if(prove_o_banco == "Proveedor"){
				xajax_listarProveedores(0, "", "");
			}else{
				xajax_listBanco();
			}
		}
	}
	
	function confirmarCambioCuenta(idCuenta){	
		if ($('cbxItm').checked){
			$('cbxItm').checked = '';
		}
			
		if ($('arrayIdFactura').value != ""){
			 if(confirm("Si selecciona otra Cuenta se borraran las facturas seleccionadas de la propuesta de pago desea continuar?")){
				xajax_cargaSaldoCuenta(idCuenta,xajax.getFormValues('frmBuscar'));
	
				$('cbxItm').click();
				$('arrayIdFactura').value = "";
				$('arrayMonto').value = "";
				$('arrayTipoDocumento').value = "";
				$('arraySustraendoRetencion').value = "";
				$('arrayPorcentajeRetencion').value = "";
				$('arrayMontoRetenido').value = "";
				$('arrayCodigoRetencion').value = "";
				$('arrayIdRetencion').value = "";
				$('arrayBaseImponibleRetencion').value = "";
				$('txtMontoPropuesta').value = 0;
				$('hddMontoPropuesta').value = 0;
				xajax_eliminarFactura(xajax.getFormValues('frmFacturas'));
			}else{
				xajax_comboCuentas(xajax.getFormValues('frmBuscar'),$('hddIdBanco').value);
			}
		}else{
			xajax_cargaSaldoCuenta(idCuenta,xajax.getFormValues('frmBuscar'));
		}
	}
	
	function validarCuentaSeleccionada(idFactura, tipo){
		
		if (validarCampo('txtNombreBanco','t','') == true
		 && validarCampo('selCuenta','t','lista') == true
		 && validarCampo('txtSaldoCuenta','t','')== true
		 && validarCampo('txtNumCuenta','t','')== true){
			 
			xajax_facturaSeleccionada(idFactura, tipo);
		
		}else{
			validarCampo('txtNombreBanco','t','');
			validarCampo('selCuenta','t','lista');
			validarCampo('txtSaldoCuenta','t','');
			validarCampo('txtNumCuenta','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarGuardado(){
		if ($('arrayIdFactura').value == ""){
			alert("No ha seleccionado ninguna factura");
			desbloquearGuardado();
		}else{
			xajax_guardarPropuesta(xajax.getFormValues('frmBuscar'));
		}
	}
	
	function validarSoloNumerosRealesTesoreria (evento) {
		if (arguments.length > 1){
			color = arguments[1];
		}
		
		if (evento.target){
			idObj = evento.target.id
		}else if(evento.srcElement){
			idObj = evento.srcElement.id;
		}
		
		teclaCodigo = (document.all) ? evento.keyCode : evento.which;
		
		if ((teclaCodigo != 0)
		&& (teclaCodigo != 8)
		&& (teclaCodigo != 13)
		&& (teclaCodigo != 46)
		&& (teclaCodigo <= 47 || teclaCodigo >= 58)) {
			return false;
		}
	}
	
	function validarClaveAprobacion(){
		if (validarCampo('txtClaveAprobacion','t','') == true){
			xajax_verificarClave(xajax.getFormValues('frmClave'));
		 }else{
		 	validarCampo('txtClaveAprobacion','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
        
	function desbloquearGuardado(){
		byId('btnGuardar').disabled = false;
	}
	
</script>    
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint">
	<?php include ('banner_tesoreria.php'); ?>
    </div>

    <div id="divInfo" class="print">
		<table width="100%" border="0">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
			<tr>
				<td class="tituloPaginaTesoreria" colspan="2">Propuesta de Pago Transferencia</td>
			</tr>
			<tr class="noprint">
				<td align="right">
					<form id="frmBuscar" name="frmBuscar" style="margin:0">
					<table width="100%" border="0">
						<tr>
							<td width="177" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
							<td width="217" align="left">
                  				<table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>
                                        	<input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/>
                                        	<input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/>
                                        </td>
                                        <td><button type="button" id="btnListEmpresa"  name="btnListEmpresa" onclick="xajax_listEmpresa(0,'','',$('hddIdProveedorCabecera').value);" title="Seleccionar Empresa"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                                    </tr>
                                </table>
							</td>
                            <td width="177" align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Proveedor:</td>
                            <td colspan="3" align="left">
                  				<table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>
                                        	<input type="text" id="txtProveedorCabecera" name="txtProveedorCabecera" size="40" readonly="readonly"/>
                                        	<input type="hidden" id="hddIdProveedorCabecera" name="hddIdProveedorCabecera"/>
                                        </td>
                                        <td><button type="button" id="btnListarProveedor"  name="btnListarProveedor" onclick="confirmarCambio('Proveedor');" title="Seleccionar Proveedor"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                                    </tr>
                                </table>
							</td>
                            <td width="177" align="right" class="tituloCampo" id="tdTextoNumeroPropuesta">N&uacute;mero Propuesta:</td>
                            <td id="tdNumeroPropuesta"><input type="text" id="txtNumeroPropuesta" name="txtNumeroPropuesta" size="40" readonly="readonly"/></td>      
							<td align="right" class="tituloCampo" width="110">Saldo Cuenta:</td>
                            <td align="left" width="301">
                                <input type="text" id="txtSaldoCuenta" name="txtSaldoCuenta" readonly="readonly" style="text-align:right" />
                                                                  
                                <input type="hidden" id="hddSaldoCuenta" name="hddSaldoCuenta" />
                                <input type="hidden" id="hddIdCuenta" name="hddIdCuenta" />
                            </td>  
						</tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="110"><span class="textoRojoNegrita">*</span>Banco:</td>
                            <td colspan="1" align="left">
                                <table cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>
                                            <input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/>
                                            <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                                        </td>
                                        <td><button type="button" id="btnListBanco" name="btnListBanco" onclick="confirmarCambio('Banco');" title="Seleccionar Beneficiario"><img src="../img/iconos/ico_pregunta.gif"/></button></td>
                                    </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo" width="110"><span class="textoRojoNegrita">*</span>Cuentas:</td>
                            <td align="left" colspan="3" id="tdSelCuentas">
                            	<select name="selCuenta" id="selCuenta" class="inputHabilitado">
                            	    <option value="-1">Seleccione</option>
                                </select>
                            </td>
							<td align="right" class="tituloCampo" width="110">Diferido:</td>
                            <td align="left" width="200">
                            <input type="text" id="txtDiferido" name="txtDiferido" readonly="readonly" style="text-align:right" /> 
                            <input type="hidden" id="hddDiferido" name="hddDiferido" />
                            </td>
                        </tr>
                        <tr>
                    <td align="right" class="tituloCampo" width="110"><span class="textoRojoNegrita">*</span>Cuenta:</td>
                            <td align="left" width="301">
                                <input type="text" id="txtNumCuenta" name="txtNumCuenta" class="inputHabilitado" size="30" style="text-align:center" />
                            </td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td align="right" class="tituloCampo" width="110">Monto Propuesta:</td>
                            <td align="left" width="301">
                                <input type="text" id="txtMontoPropuesta" name="txtMontoPropuesta" readonly="readonly" style="text-align:right" />
                                <input type="hidden" id="hddMontoPropuesta" name="hddMontoPropuesta" />
                            </td>
                     </tr>
                        <tr>
                            <td align="left" colspan="6" id="tdEditar">
                            	<button type="button" id="btnAgregarFactura"  name="btnAgregarFactura" onclick="byId('buscarFact').value = 1; byId('btnBuscarDocumento').click();" title="Seleccionar Empresa" disabled="disabled">Agregar Factura</button>
                                <button type="button" id="btnAgregarNotaCargo"  name="btnAgregarNotaCargo" onclick="byId('buscarFact').value = 2; byId('btnBuscarDocumento').click();" title="Agregar Nota Cargo" disabled="disabled">Agregar Nota Cargo</button>
                                <button type="button" id="btnEliminarFactura"  name="btnEliminarFactura" onclick="xajax_eliminarFactura(xajax.getFormValues('frmFacturas'));">Eliminar Factura</button>
                            </td>
                            <td align="left" colspan="6"  id="tdVer" style="display:none">
                            	<button class="noprint" type="button" id="btnImprimir" name="btnImprimir" onclick="xajax_encabezadoEmpresa($('hddIdEmpresa').value); window.print();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/print.png" alt="print"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
							</td>
                        </tr>
                    	<tr>
                        	<td colspan="7">
                                <input type="hidden" id="arrayIdFactura" name="arrayIdFactura" title="arrayIdFactura"/>
                                <input type="hidden" id="arrayMonto" name="arrayMonto" title="arrayMonto"/>
                                 <input type="hidden" id="arrayTipoDocumento" name="arrayTipoDocumento" title="arrayTipoDocumento"/>
                                <input type="hidden" id="arraySustraendoRetencion" name="arraySustraendoRetencion" title="arraySustraendoRetencion"/>
                                <input type="hidden" id="arrayPorcentajeRetencion" name="arrayPorcentajeRetencion" title="arrayPorcentajeRetencion"/>
                                <input type="hidden" id="arrayMontoRetenido" name="arrayMontoRetenido" title="arrayMontoRetenido"/>
                                <input type="hidden" id="arrayCodigoRetencion" name="arrayCodigoRetencion" title="arraySustraendoRetencion"/>
                                <input type="hidden" id="arrayIdRetencion" name="arrayIdRetencion" title="arrayIdRetencion"/>
                                <input type="hidden" id="arrayBaseImponibleRetencion" name="arrayBaseImponibleRetencion" title="arrayBaseImponibleRetencion"/>
                                <input type="hidden" id="hddIdPropuesta" name="hddIdPropuesta" title="hddIdPropuesta"/>
                                <input type="hidden" id="hddPermiso" name="hddPermiso" title="hddPermiso" value="0"/>
                                <input type="hidden" id="hddObj" name="hddObj" title="hddObj"/>
                            </td>
						</tr>
					</table>
					</form>
                </td>
            </tr>
            <tr>
            	<td colspan="8">
                	<form id="frmFacturas" name="frmFacturas">
                	<table width="100%">
                    	<tr>
                            <td id="tdListadoFacturas">
                                <table border="0" class="tablaStripped" cellpadding="2" width="100%">
                                    <tr class="tituloColumna">
                                        <td width='1%' class="noprint"><input type="checkbox" id="cbxItm" onclick="selecAllChecks(this.checked,this.id,1);"/></td>
                                        <td width='2%'>Cod.</td>
                                        <td width='15%'>Proveedor</td>
                                        <td width='25%'>Descripci&oacute;n</td>
                                        <td width='10%'>N&uacute;mero</td>
                                        <td width='10%'>Fecha</td>
                                        <td width='10%'>D&iacute;as Vencidos</td>
                                        <td width='10%'>Saldo</td>
                                        <td width='10%'>Monto</td>
                                        <td width='10%'>Retenci&oacute;n</td>
                                    </tr>
                                    <tr id="trItmPie"></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                	</form>
                </td>
            </tr>     
            <tr class="solo_print">
                <td align="left" id="tdPrueb"></td>

            </tr>
            <tr>
                <td>
                    <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                    <tr>
                        <td width="25" align="left"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr class="noprint" id="trBotones">
                <td align="right">
                    <button type="button" id="btnGuardar" onclick="this.disabled = true;  validarGuardado();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                    <button type="button" id="btnCancelar" name="btnCancelar" onclick="window.open('te_propuesta_pago_tr_mantenimiento.php','_self');" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_error.gif"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
                </td>
            </tr>
		</table>
    </div>
    <div class="noprint">
	<?php include ('pie_pagina.php'); ?>
    </div>
</div>
</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:2;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
   	<form id="frmFacturaIndividual" name="frmFacturaIndividual" >
    <table border="0" id="tblFactura" width="560">
        <tr align="left">
            <td>
            	<fieldset><legend class="legend">Datos Factura</legend>
            	<table border="0">
                	<tr>
                    	<td align="right" class="tituloCampo" width="400">Proveedor:</td>
                        <td><input type="text" readonly="readonly" id="txtProveedor" name="txtProveedor" size="53"/></td>
                    </tr>
                    <tr>
                    	<td class="tituloCampo" align="right" width="400">
                            <span class="textoRojoNegrita">*</span>Retenci&oacute;n ISLR:
                            <input type="hidden" id="hddMontoMayorAplicar" name="hddMontoMayorAplicar"/>
                            <input type="hidden" id="hddPorcentajeRetencion" name="hddPorcentajeRetencion"/>
                            <input type="hidden" id="hddSustraendoRetencion" name="hddSustraendoRetencion"/>
                            <input type="hidden" id="hddCodigoRetencion" name="hddCodigoRetencion"/>
                            <input type="hidden" id="hddIdRetencion" name="hddIdRetencion"/>
                        </td>
                        <td align="left" id="tdRetencionISLR">
                            
                        </td>
                    </tr>
                    <tr>
                    	<td></td>
                    	<td id="tdInfoRetencionISLR"></td>
                    </tr>
                	<tr>
                        <td align="right" class="tituloCampo" >N&uacute;mero Factura:</td>
                        <td>
                        	<input type="text" readonly="readonly" id="txtNumeroFactura" name="txtNumeroFactura"/>
                            <input type="hidden" id="hddIdFactura" name="hddIdFactura"/>
                            <input type="hidden" id="hddTipoDocumento" name="hddTipoDocumento"/>
                        </td>
                    </tr>
                    <tr>
                    	<td align="right" class="tituloCampo" >Descripci&oacute;n:</td>
                        <td><textarea id="txtDescripcion" name="txtDescripcion" cols="50" readonly="readonly"></textarea></td>
                    </tr>
                	<tr>
                    	<td align="right" class="tituloCampo" >Saldo Factura:</td>
                        <td>
                        	<input type="text" readonly="readonly" id="txtSaldoFactura" name="txtSaldoFactura" style="text-align:right" />
                            <input type="hidden" id="hddIva" name="hddIva" />
                            
                        </td>
                        
                    </tr>
                    <tr>
                        <td align="right" class="tituloCampo" >Base Imponible:</td>
                        <td><input type="text" id="hddBaseImponible" class="inputHabilitado" onkeyup="xajax_asignarDetallesRetencion(byId('selRetencionISLR').value,'SI');" name="hddBaseImponible" /></td>
                    </tr>
                    <tr>
                    	<td align="right" class="tituloCampo" >Monto a Pagar:</td>
                        <td><input type="text" id="txtMontoAPagar" name="txtMontoAPagar" class="inputHabilitado" size="20" style="text-align:right" onkeypress="return validarSoloNumerosRealesTesoreria(event);" onblur="validarMonto(); calcularRetencionMano();" onfocus="$('btnAceptar').disabled = '';"/></td>
                    </tr>
                    <tr>
						<td class="tituloCampo" align="right" id="tdTextoRetencionISLR" style="display:none">
							Monto Retenido: 
						</td>
						<td colspan="2" align="left" id="tdMontoRetencionISLR" style="display:none">
							<input type="text" id="txtMontoRetencionISLR" name="txtMontoRetencionISLR" size="20" style="text-align:right" readonly="readonly" />
                    	</td>
                    </tr>
                </table>
				</fieldset>
            </td>
        </tr>
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr>
                <input type="button" id="btnAceptar" name="btnAceptar" onclick="validar();" value="Aceptar">
                <input type="button" id="btnCancelar1" name="btnCancelar1" onclick="$('divFlotante').style.display='none';" value="Cancelar">
            </td>
        </tr>
    </table>
    </form>
</div>

<div id="divFlotante1" class="root" style="position:absolute; cursor:auto; display:none; left:0px; top:0px; z-index:1; ">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td></tr></table></div>
   	<table border="0" id="tblBancos" style="display:none" width="610">
        
         <tr id="trBuscarCliente">
    	<td>
        </td>
    </tr>
        
        <tr>
            <td id="tdDescripcion">
            </td>
        </tr>
        
        <tr>
            <td align="right" id="tdBotonesDiv">
                <hr>
                <input type="button" id="" name="" onclick="$('divFlotante1').style.display='none';" value="Cerrar">
            </td>
        </tr>
    </table>
</div>

<div id="divFlotanteDoc" class="root" style="position:absolute; cursor:auto; display:none; left:0px; top:0px; z-index:1; ">
	<div id="divFlotanteTituloDoc" class="handle"><table><tr><td id="tdFlotanteTituloDoc" width="100%"></td></tr></table></div>
  	<table border="0" id="tblFacturasNcargos" width="1050px">
    <tr id="trBuscarDocumento">
    	<td>
        	<form id="frmBuscarDocumento" name="frmBuscarDocumento" onsubmit="$('btnBuscarDocumento').click(); return false;" style="margin:0">
            	<table>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Departamento:</td>
                    <td id="tdlstModulo"></td>
                	<td align="right" class="tituloCampo" width="115">Criterio:</td>
                	<td>
                        <input type="hidden" id="buscarFact" name="buscarFact" value = "1"/>
                    	<input type="text" id="txtCriterioBusq" name="txtCriterioBusq" class="inputHabilitado" onkeyup="$('btnBuscarDocumento').click();"/>
					</td>
                    <td>
                    	<button type="button" id="btnBuscarDocumento" name="btnBuscarDocumento" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'),xajax.getFormValues('frmBuscar'));" >Buscar</button>
                    </td>
                    <td>
                    	<button type="button" onClick="byId('frmBuscarDocumento').reset(); byId('btnBuscarDocumento').click();" >Limpiar</button>
                    </td>
                </tr>
                <tr>
                    <td width="120" align="right" class="tituloCampo">D&iacute;as Vencidos:</td>
                    <td colspan="10" align="left" id="tdDiasVencidos">
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td id="tdListadoFacNcargo"></td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" onclick="$('divFlotanteDoc').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
</div>

<div id="divFlotanteProv" class="root" style="position:absolute; cursor:auto; display:none; left:0px; top:0px; z-index:1; ">
	<div id="divFlotanteTituloProv" class="handle"><table><tr><td id="tdFlotanteTituloProv" width="100%"></td></tr></table></div>
  	<table border="0" id="tblListadoProveedor" width="600px">
    <tr id="trBuscarCliente">
    	<td>
        	<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="$('btnBuscarCliente').click(); return false;" style="margin:0">
            	<table>
                <tr>
                	<td align="right" class="tituloCampo" width="115">Criterio:</td>
                	<td>
                    	<input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" class="inputHabilitado" onkeyup="$('btnBuscarCliente').click();"/>
					</td>
                    <td><input type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));" value="Buscar..."/></td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td id="tdListadoProveedores"></td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" onclick="$('divFlotanteProv').style.display='none';" value="Cancelar">
        </td>
    </tr>
    </table>
</div>

<div id="divFlotante2" class="root" style="position:absolute; cursor:auto; display:none; left:0px;; top:0px; z-index:3;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
	<form id="frmClave" name="frmClave" onsubmit="return false;">
	<table border="0" id="tblClaveAprobacionOrden">
		<tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
        <tr>
			<td colspan="2" id="tdTituloListado">&nbsp;</td>
		</tr>
		<tr>
			<td align="right" class="tituloCampo">Clave:</td>
			<td><label>
				<input name="txtClaveAprobacion" id="txtClaveAprobacion" type="password" class="inputInicial" />
			</label></td>
		</tr>
		<tr><td colspan="2">&nbsp;</td></tr>
		<tr>
			<td align="right" colspan="2">
			<hr>
			<input type="submit" onclick="validarClaveAprobacion();" value="Aceptar" />
			<input type="button" onclick="$('divFlotante2').style.display='none';" value="Cancelar">
			</td>
		</tr>
	</table>
    </form>
</div>


<script language="javascript">
xajax_comboRetencionISLR();
xajax_cargaLstModulo('', "onchange=\"byId('btnBuscarDocumento').click();\"");//te general
xajax_cargarDiasVencidos();//te general
xajax_asignarEmpresa(0,0);

<?php if(isset($_GET['id_propuesta'])){//si tiene propuesta  ?>
	$('hddIdPropuesta').value = <?php echo $_GET['id_propuesta']; ?>;
	
		if (<?php echo $_GET['id_propuesta']; ?> != 0){//id propuesta
			$("tdTextoNumeroPropuesta").style.display = '';
			$("tdNumeroPropuesta").style.display = '';
			$("txtNumeroPropuesta").value = <?php echo $_GET['id_propuesta']; ?>;
			xajax_cargarPropuesta(<?php echo $_GET['id_propuesta']; ?>);
		}else{//nunca entra, solo es validacion
			$("tdTextoNumeroPropuesta").style.display = 'none';
			$("tdNumeroPropuesta").style.display = 'none';
			xajax_asignarEmpresa(0,0);
		}
		
		if (<?php echo $_GET['acc']; ?> == 0){//ver
			$("tdEditar").style.display = 'none';
			$("tdVer").style.display = '';
			$("trBotones").style.display = 'none';
		}else{//editar
			$("tdEditar").style.display = '';
			$("tdVer").style.display = 'none';
			$("trBotones").style.display = '';
		}
<?php } ?>

var theHandle = byId("divFlotanteTitulo");
var theRoot   = byId("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = byId("divFlotanteTitulo1");
var theRoot   = byId("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = byId("divFlotanteTituloProv");
var theRoot   = byId("divFlotanteProv");
Drag.init(theHandle, theRoot);

var theHandle = byId("divFlotanteTituloDoc");
var theRoot   = byId("divFlotanteDoc");
Drag.init(theHandle, theRoot);
	
</script>