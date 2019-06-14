<?php
require("../connections/conex.php");

session_start();

/* Validación del Módulo */
require('../inc_sesion.php');

require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

require("../controladores/ac_iv_general.php");
require("controladores/ac_te_general.php");
require("controladores/ac_te_generar_cheque.php");

//modificado Ernesto
if(file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")){
	require("../contabilidad/GenerarEnviarContabilidadDirecto.php");
}
//Fin modificado Ernesto

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title>.: SIPRE <?php echo cVERSION; ?> :. Tesoreria - Cheque</title>
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
    
	<style type="text/css">
	#tdFacturas, #tdNotaCargo, #tdBeneficiarios, #tdProveedores{
		-webkit-border-top-left-radius: 10px;
		-webkit-border-top-right-radius: 10px;
		-moz-border-radius-topleft: 10px;
		-moz-border-radius-topright: 10px;
		border-top-left-radius: 10px;
		border-top-right-radius: 10px;

		border-color:#CCCCCC;                                  
	}
        
        .tabla-propuesta{
           border: 1px solid #999999;
           border-collapse: collapse; 
        }
        .tabla-propuesta td{
            border: 1px solid #999999;
            padding: 7px;
        }
        .tabla-propuesta th{
            border: 1px solid #999999;
            background-color: #f0f0f0;
            padding: 7px;
        }
	</style>
    <script>	
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblCheque').style.display = 'none';
		
		if (verTabla == "tblCheque") {
			document.forms['frmCheque'].reset();
			
			xajax_formCheque(valor2, valor);
			
			if (valor == 'nuevo') {
				tituloDiv1 = 'Nuevo Cheque';
			} else if (valor == 'ver') {
				tituloDiv1 = 'Ver Cheque';
			} else if (valor == 'editar') {
				tituloDiv1 = 'Editar Cheque';
			}
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblPermiso').style.display = 'none';
		byId('tblListaBanco').style.display = 'none';
		byId('tblListaEmpresa').style.display = 'none';
		byId('tblBeneficiariosProveedores').style.display = 'none';
		byId('tblFacturasNotas').style.display = 'none';
		byId('tblAnularCheque').style.display = 'none';
		byId('tblEditarFechaCheque').style.display = 'none';		
		
		if (verTabla == "tblPermiso") {
			document.forms['frmPermiso'].reset();
			byId('hddModulo').value = '';
			
			byId('txtContrasena').className = 'inputHabilitado';
			
			xajax_formValidarPermisoEdicion(valor, valor2);
			
			tituloDiv2 = 'Ingreso de Clave Especial';
		} else if (verTabla == "tblListaBanco") {			
			document.forms['frmBuscarBanco'].reset();
			xajax_listaBanco();
			
			tituloDiv2 = 'Bancos';
		} else if (verTabla == "tblListaEmpresa") {
			document.forms['frmBuscarEmpresa'].reset();			
			xajax_listaEmpresa();
			
			tituloDiv2 = 'Empresas';
		} else if (verTabla == "tblBeneficiariosProveedores") {
			
			tituloDiv2 = 'Beneficario / Proveedor';
		} else if (verTabla == "tblFacturasNotas") {
			byId('tdContenidoDocumento').innerHTML = '';
			tituloDiv2 = 'Factura / Nota de Cargo';
		} else if (verTabla == "tblAnularCheque") {
			document.forms['frmAnular'].reset();
			byId('hddIdChequeA').value = '';
			
			xajax_formAnularCheque(valor);
			
			tituloDiv2 = 'Anular Cheque';
		} else if (verTabla == "tblListaPropuestaPago"){
			limpiarPropuesta();
			xajax_verPropuesta(valor);
			
			tituloDiv2 = 'Propuesta de Pago';
		} else if (verTabla == "tblEditarFechaCheque"){
			document.forms['frmEditarFechaCheque'].reset();
			byId('hddIdChequeEditarFecha').value = '';
			
			xajax_formEditarFechaCheque(valor);
			
			tituloDiv2 = 'Editar Fecha Cheque';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblPermiso") {
			byId('txtContrasena').focus();
			byId('txtContrasena').select();
		} else if (verTabla == "tblListaBanco") {			
			byId('txtCriterioBuscarBanco').focus();
			byId('txtCriterioBuscarBanco').select();
		} else if (verTabla == "tblListaEmpresa") {
			byId('txtCriterioBuscarEmpresa').focus();
			byId('txtCriterioBuscarEmpresa').select();
		} else if (verTabla == "tblBeneficiariosProveedores") {
			byId('txtCriterioBusqProveedor').focus();
			byId('txtCriterioBusqProveedor').select();
		} else if (verTabla == "tblFacturasNotas") {
			byId('txtCriterioBuscarFacturaNota').focus();
			byId('txtCriterioBuscarFacturaNota').select();
		} else if (verTabla == "tblAnularCheque") {
			byId('txtComision').focus();
			byId('txtComision').select();
		}
	}
	
	function exportarExcel(){
		objInputs = xajax.getFormValues('frmBuscar');
		window.open("reportes/te_cheques_excel.php?valBusq="+JSON.stringify(objInputs)+"&acc=<?php echo $_GET['acc']; ?>");
	}
        
	function validarActualizarCheque(){		
		if(byId('cbxChequeEntregado').checked){
			xajax_actualizarCheque(xajax.getFormValues('frmCheque'));
		}else{
			alert('Debe indicar que fue entregado');
		}
	}
        
	function validarCheque(){
		if (validarCampo('txtNombreEmpresa','t','') == true
		&&  validarCampo('txtNombreBanco','t','') == true
		&&  validarCampo('lstCuenta','t','lista') == true
		&&  validarCampo('txtSaldoCuenta','t','') == true
		&&  validarCampo('txtIdBeneficiario','t','') == true
 		&&  validarCampo('txtCiRifBeneficiario','t','') == true
		&&  validarCampo('txtNombreBeneficiario','t','') == true
		&&  validarCampo('txtIdFactura','t','') == true
		&&  validarCampo('txtNumeroFactura','t','') == true
		&&  validarCampo('txtFechaRegistro','t','') == true
		&&  validarCampo('txtFechaLiberacion','t','') == true
		&&  validarCampo('txtMonto','t','') == true
		&&  validarCampo('txtConcepto','t','') == true
		&&  validarCampo('numCheque','t','') == true
		&&  validarCampo('txtComentario','t','') == true
		&&  byId('txtMonto').value > 0){                        
			xajax_guardarCheque(xajax.getFormValues('frmCheque'));
		} else {
			validarCampo('txtNombreEmpresa','t','')
			validarCampo('txtNombreBanco','t','')
			validarCampo('lstCuenta','t','lista')
			validarCampo('txtSaldoCuenta','t','')
			validarCampo('txtIdBeneficiario','t','')
			validarCampo('txtCiRifBeneficiario','t','')
			validarCampo('txtNombreBeneficiario','t','')
			validarCampo('txtIdFactura','t','')
			validarCampo('txtNumeroFactura','t','')
			validarCampo('txtFechaRegistro','t','')
			validarCampo('txtFechaLiberacion','t','')
			validarCampo('txtMonto','t','')
			validarCampo('txtConcepto','t','')
			validarCampo('numCheque','t','')
			validarCampo('txtComentario','t','')
			if	(byId('txtMonto').value <= 0)
				byId('txtMonto').className = 'inputErrado';
			
			alert("Los campos señalados en rojo son requeridos");
                        desbloquearGuardado();
         
			return false;
		}
	}
	
	function validarLongitud(campo){
		if (byId(campo).value.length > 119){
			var cadena = byId(campo).value.substring(0,119);
			byId(campo).value = cadena;
		}
	}	
	
	function validarMonto(){
		if (parseFloat(byId('txtMonto').value) > parseFloat(byId('txtSaldoFactura').value) && byId('hddPasoClaveSobreGiro').value != 1){

			byId('btnAceptar').disabled = true;
			if (confirm('El monto de la propuesta es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
				byId('aDesbloquearClaveSobreGiro').click();
				return false;
			}else{
				return false;
			}
		}else{
			if (parseFloat(byId('txtMonto').value)+parseFloat(byId('hddDiferido').value) > parseFloat(byId('hddSaldoCuenta').value) && byId('hddPasoClaveSobreGiro').value != 1){
				byId('btnAceptar').disabled = true;
				if (confirm('El monto de la propuesta es mayor que el saldo en la cuenta Desea Sobregirar la Cuenta?') == true){
					byId('aDesbloquearClaveSobreGiro').click();
					return false;
				}else{
					return false;
				}
			}else{
				byId('txtMonto').className = 'inputHabilitado';
			}
		}			
	}
	
	function validarProveedor(){
		if (validarCampo('txtNombreEmpresa','t','') == true
		&&  validarCampo('txtNombreBanco','t','') == true
		&&  validarCampo('lstCuenta','t','lista') == true
		&&  validarCampo('txtSaldoCuenta','t','') == true
		&&  validarCampo('txtIdBeneficiario','t','') == true
		&&  validarCampo('txtCiRifBeneficiario','t','') == true
		&&  validarCampo('txtNombreBeneficiario','t','') == true){			
			return true;
		}else{
			validarCampo('txtNombreEmpresa','t','')
			validarCampo('txtNombreBanco','t','')
			validarCampo('lstCuenta','t','lista')
			validarCampo('txtSaldoCuenta','t','')
			validarCampo('txtIdBeneficiario','t','')
			validarCampo('txtCiRifBeneficiario','t','')
			validarCampo('txtNombreBeneficiario','t','')
		
			alert("Los campos señalados en rojo son requeridos");
			
			setTimeout(function(){//si estaba abierto e intenta segunda vez
				byId('btnCancelarFacturaNota').click();
			},2000);
			
			return false;
		}
	}
	
	function calcularRetencion(){
		if ((parseFloat(byId('txtSaldoFactura').value) >= parseFloat(byId('hddMontoMayorAplicar').value)) && (byId('hddPorcentajeRetencion').value > 0) ){
			byId('tdTextoRetencionISLR').style.display = '';
			byId('tdMontoRetencionISLR').style.display = '';
			
			if (byId('hddIva').value == 0){
				var monto_retencion = (byId('txtSaldoFactura').value * byId('hddPorcentajeRetencion').value / 100)-( byId('hddSustraendoRetencion').value);
				byId('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
				var monto = (parseFloat(byId('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
				byId('txtMonto').value = number_format(monto,'2','.','');
			}else{
				var monto_retencion = (byId('hddBaseImponible').value * byId('hddPorcentajeRetencion').value / 100)-(byId('hddSustraendoRetencion').value);
				byId('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');
				var monto = (parseFloat(byId('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
				byId('txtMonto').value = number_format(monto,'2','.','');
			}
		
		}else{
			byId('tdTextoRetencionISLR').style.display = 'none';
			byId('tdMontoRetencionISLR').style.display = 'none';
			byId('txtMonto').value = byId('txtSaldoFactura').value;
			byId('txtMontoRetencionISLR').value = 0;
		}
	}
        
	//gregor SI SE USA LA BASE CALCULAR RESPECTO A LA BASE
	function calcularConBase(){		
		var monto_retencion = (byId('hddBaseImponible').value * (byId('hddPorcentajeRetencion').value / 100))-( byId('hddSustraendoRetencion').value);
		byId('txtMontoRetencionISLR').value = number_format(monto_retencion,'2','.','');   

		var monto = (parseFloat(byId('txtSaldoFactura').value))-(parseFloat(number_format(monto_retencion,'2','.','')));
		byId('txtMonto').value = number_format(monto,'2','.','');
	}
	
    function number_format( number, decimals, dec_point, thousands_sep ){
		var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
		var d = dec_point == undefined ? "," : dec_point;
		var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
		var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}
	
	function validarFrmAnularCheque(){
		if (validarCampo('txtComision','t','') == true){                    
			//compruebo si tiene impuesto el cheque
			var tiene = xajax.call('tieneImpuesto', {mode:'synchronous', parameters:[byId('hddIdChequeA').value]});
			
			if(tiene == "SI"){
				if(confirm("El cheque posee ISLR, si ya fue declarado no deberia ser eliminado, ¿deseas eliminar el impuesto?")){
					 xajax_anularCheque(xajax.getFormValues('frmAnular'),"SI");
				 }else{
					 xajax_anularCheque(xajax.getFormValues('frmAnular'));
				 }
			}else if(tiene == 'NO'){
				xajax_anularCheque(xajax.getFormValues('frmAnular'));
			}else{
				alert('Error: ' +tiene);
				return false;
			}           
			
		 }else{
		 	validarCampo('txtComision','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		 }
	}
	
	function validarFrmEditarFechaCheque(){
		if (validarCampo('txtNumeroChequeEditar','t','') == true
		&& validarCampo('txtFechaChequeRegistrado','t','') == true
		&& validarCampo('txtFechaChequeEditar','t','') == true
		&& validarCampo('hddIdChequeEditarFecha','t','') == true){	
			if (confirm('¿Seguro desea editar la fecha del cheque?')) {
				xajax_guardarEditarFechaCheque(xajax.getFormValues('frmEditarFechaCheque'));
			}
		}else{
			validarCampo('txtNumeroChequeEditar','t','');
			validarCampo('txtFechaChequeRegistrado','t','');
			validarCampo('txtFechaChequeEditar','t','');
			validarCampo('hddIdChequeEditarFecha','t','');
		
			alert("Los campos señalados en rojo son requeridos");			
			return false;
		}
	}
	
	function limpiarPropuesta(){        
		byId('numeroPropuestaPago').innerHTML = "";
		byId('fechaPropuestaPago').innerHTML = "";
		byId('numeroChequePropuestaPago').innerHTML = "";
		byId('estadoPropuestaPago').innerHTML = "";
		byId('detallePropuestaPago').innerHTML = "";
	}
	
	function numeros(e) {
		tecla = (document.all) ? e.keyCode : e.which;
		if (tecla == 0 || tecla == 8)
			return true;
		patron = /[0-9]/;
		te = String.fromCharCode(tecla);
		return patron.test(te);
	}
	
	function desbloquearGuardado(){                    
		byId('btnAceptar').disabled = false;
	}
	
	function validarFrmPermiso() {
		if (validarCampo('txtContrasena','t','') == true) {
			xajax_validarPermiso(xajax.getFormValues('frmPermiso'));
		} else {
			validarCampo('txtContrasena','t','');
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
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
        	<td class="tituloPaginaTesoreria" id="tdReferenciaPagina"></td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
            <td align="right">
				<table align="left" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblCheque', 'nuevo');">
				<button type="button" id="btnNuevo"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_new.png"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
                    <button type="button" id="btnExportarExcel" onclick="exportarExcel();" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>XLS</td></tr></table></button>    
					</td>
				</tr>
				</table>
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
				<table align="right" border="0">
				<tr align="left">
                    <td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" align="left"></td>
                    <td align="right" class="tituloCampo" width="120">Proveedor:</td>
					<td>
                   	  <table cellpadding="0" cellspacing="0">
                            <td>
                                <input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor2(this.value);" size="6" style="text-align:right" class="inputHabilitado"/>
                            </td>
                            <td>
                                <a class="modalImg" id="aProveedor" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblBeneficiariosProveedores', 0);">
                                    <button type="button" title="Seleccionar Proveedor / Beneficiario" class="puntero"
                                        onclick="
                                        byId('tdBeneficiarios').className = 'rafktabs_title';
                                        byId('tdProveedores').className = 'rafktabs_titleActive';
                                            
                                        byId('buscarListado').value = '1';//listado1 de proveedores
                                        byId('buscarProv').value = '1';//proveedor
                                        byId('btnBuscarCliente').click();
                                        
                                         byId('tdProveedores').onclick = function(){
                                            byId('tdBeneficiarios').className = 'rafktabs_title';
                                            byId('tdProveedores').className = 'rafktabs_titleActive';
                                            byId('buscarProv').value = '1';//proveedor
                                            byId('btnBuscarCliente').click();
                                            };
                                            
                                         byId('tdBeneficiarios').onclick = function(){
                                            byId('tdBeneficiarios').className = 'rafktabs_titleActive';
                                            byId('tdProveedores').className = 'rafktabs_title';
                                            byId('buscarProv').value = '2';//beneficiario
                                            byId('btnBuscarCliente').click();
                                            };">
                                            <img src="../img/iconos/help.png"/>
                                    </button>
                                </a>
                            </td>
                            <td>
                                <input type="text" name="nombreProveedorBuscar" id="nombreProveedorBuscar" readonly="readonly" size="30"></input>
                            </td>
                        </table>
                    </td>
				</tr>
				<tr align="left">
                    <td align="right" class="tituloCampo" width="120">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" size="10" class="inputHabilitado" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                    
                    <td align="right" class="tituloCampo">
                        Concepto:
                    </td>
                    <td>
                        <input type="text" name="txtConceptoBuscar" id="txtConceptoBuscar" class="inputHabilitado"></input>
					</td>
				</tr>
				<tr align="left">
					<td align="right" class="tituloCampo" width="120">Estado:</td>
					<td id="tdLstEstado"></td>
					<td align="right" class="tituloCampo" width="120">Nro. Cheque:</td>
					<td><input type="text" name="txtCriterio" id="txtCriterio" onkeyup="byId('btnBuscar').click();" class="inputHabilitado"/></td>
					<td>
						<button type="button" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarCheque(xajax.getFormValues('frmBuscar'))">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
					</td>
                </tr>			
                </table>
                </form>
            </td>
        </tr>
        <tr>
            <td id="tdListaCheques"></td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_gris.gif"></td>
                            <td>No Entregado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_azul.gif"></td>
                            <td>Entregado</td>
                            <td>&nbsp;</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td width="25"></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_rojo.gif"></td>
                            <td>Por Aplicar</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_amarillo.gif"></td>
                            <td>Aplicado</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_verde.gif"></td>
                            <td>Conciliado</td>
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
	<div style="max-height:480px; overflow-y:auto; overflow-x : hidden; width:925px;">
    <form id="frmCheque" name="frmCheque" onsubmit="return false;">
    <table border="0" id="tblCheque" width="910">
    	<tr align="left">
    		<td>
    			<fieldset><legend class="legend">Datos Empresa</legend>
    			<table width="100%">
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td colspan="3" align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreEmpresa" name="txtNombreEmpresa" size="25" readonly="readonly"/><input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa"/></td>
                                <td>
                                    <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaEmpresa');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                </table>
                </fieldset>
             </td>
             <td>  
    			<fieldset><legend class="legend">Datos Bancos</legend>
    			<table width="100%">
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Banco:</td>
                    <td colspan="3" align="left">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtNombreBanco" name="txtNombreBanco" size="25" readonly="readonly"/>
                                    <input type="hidden" id="hddIdBanco" name="hddIdBanco"/>
                                </td>
                                <td>
									<a onclick="abrirDivFlotante2(this, 'tblListaBanco');" rel="#divFlotante2" id="aListarBanco" class="modalImg"><button title="Listar" type="button"><img src="../img/iconos/help.png"></button>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Cuentas:</td>
                    <td colspan="3" id="tdLstCuenta"></td>
                </tr>
                <tr id="trSaldoCuenta">
                    <td align="right" class="tituloCampo" width="120">Saldo Cuenta:</td>
                    <td colspan="3">
                        <input type="hidden" id="hddIdChequera" name="hddIdChequera" />
                        <input type="hidden" id="hddSaldoCuenta" name="hddSaldoCuenta" />
                        <input type="text" id="txtSaldoCuenta" name="txtSaldoCuenta" size="25" readonly="readonly" style="text-align:right"/>
                    </td>
                    
                    <td align="right" class="tituloCampo" width="110">Diferido:</td>
                        <td align="left" width="200">
                        <input type="text" id="txtDiferido" name="txtDiferido" readonly="readonly" style="text-align:right" /> 
                        <input type="hidden" id="hddDiferido" name="hddDiferido" />
                         <input type="hidden" id="hddPasoClaveSobreGiro" name="hddPasoClaveSobreGiro"/>
						<a class="modalImg" id="aDesbloquearClaveSobreGiro" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblPermiso', 'te_propuesta_pago');" style="display:none;">
							<img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear"/>
						</a>
                        </td>
                </tr>
                </table>
                </fieldset>
              </td>
           </tr>
           <tr align="left">
              <td colspan="2">
                <fieldset><legend class="legend">Datos del Beneficiario o Proveedor</legend>
                <table width="100%" border="0">
                <tr>
                    <td class="tituloCampo" width="25%" align="right">
                        <span class="textoRojoNegrita">*</span>Beneficiario o Proveedor:
                    </td>
                    <td width="10%" align="left">
                        <table>
                            <tr>
                                <td>
                                    <input type="text" id="txtIdBeneficiario" name="txtIdBeneficiario" readonly="readonly" size="10"/>
                                </td>
                                <td>
                                    <input type="hidden" id="hddBeneficiario_O_Provedor" name="hddBeneficiario_O_Provedor" />
                                    <a class="modalImg" id="aProveedor" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblBeneficiariosProveedores', 0);">
                                        <button type="button" title="Seleccionar Proveedor / Beneficiario" class="puntero"
                                            onclick="
                                            byId('tdBeneficiarios').className = 'rafktabs_title';
                                            byId('tdProveedores').className = 'rafktabs_titleActive';
                                            byId('txtIdFactura').value = '';
                                            byId('txtNumeroFactura').value = '';
                                            byId('txtSaldoFactura').value = '';
                                            byId('txtFechaRegistroFactura').value = '';
                                            byId('txtFechaVencimientoFactura').value = '';
                                            byId('txtDescripcionFactura').innerHTML = '';
                                                
                                            byId('buscarListado').value = '0';//listado1 de proveedores
                                            byId('buscarProv').value = '1';//proveedor
                                            byId('btnBuscarCliente').click();
                                            
                                             byId('tdProveedores').onclick = function(){
                                                byId('tdBeneficiarios').className = 'rafktabs_title';
                                                byId('tdProveedores').className = 'rafktabs_titleActive';
                                                byId('buscarProv').value = '1';//proveedor
                                                byId('btnBuscarCliente').click();
                                                };
                                                
                                             byId('tdBeneficiarios').onclick = function(){
                                                byId('tdBeneficiarios').className = 'rafktabs_titleActive';
                                                byId('tdProveedores').className = 'rafktabs_title';
                                                byId('buscarProv').value = '2';//beneficiario
                                                byId('btnBuscarCliente').click();
                                                };">
                                                <img src="../img/iconos/help.png"/>
                                        </button>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="tituloCampo" align="right">
                        <span class="textoRojoNegrita">*</span><?php echo $spanProvCxP; ?>
                    </td>
                    <td align="left" colspan="1">
                        <input type="text" id="txtCiRifBeneficiario" name="txtCiRifBeneficiario" readonly="readonly" size="30" />
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" align="right" width="20%">
                        <span class="textoRojoNegrita">*</span>Nombre:
                    </td>
                    <td align="left">
                        <input type="text" id="txtNombreBeneficiario" name="txtNombreBeneficiario" readonly="readonly" size="50" />
                    </td>
                    <td class="tituloCampo" align="right" width="20%">
                        <span class="textoRojoNegrita">*</span>Retenci&oacute;n ISLR:
                        <input type="hidden" id="hddMontoMayorAplicar" name="hddMontoMayorAplicar" />
                        <input type="hidden" id="hddPorcentajeRetencion" name="hddPorcentajeRetencion" />
                        <input type="hidden" id="hddCodigoRetencion" name="hddCodigoRetencion" />
                        <input type="hidden" id="hddSustraendoRetencion" name="hddSustraendoRetencion" />
                    </td>
                    <td align="left" id="tdRetencionISLR">

                    </td>
                </tr>
                <tr>
                	<td></td>
                    <td></td>
                    <td colspan="2" id="tdInfoRetencionISLR"></td>
                </tr>
                </table>
                </fieldset>
               
                <fieldset><legend class="legend">Detalles del Documento</legend>
                <table border="0" width="100%">
                <tr align="left">
                    <td class="tituloCampo" width="15%" align="right"><span class="textoRojoNegrita">*</span>Factura/Nota:</td>
                    <td width="10%" align="left">
                        <table>
                            <tr>
                                <td>
                                    <input type="text" id="txtIdFactura" name="txtIdFactura" readonly="readonly" size="10"/>
                                </td>
                                <td>
                                    <a class="modalImg" id="aListarFacturaNota" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblFacturasNotas');">
                                    <button type="button" id="btnInsertarFactura" name="btnInsertarFactura" title="Seleccionar Factura" class="puntero"
                                        onclick="                                            
	                                    if(validarProveedor() === true){
                                        	document.forms['frmBuscarDocumento'].reset();
	                                        byId('tblFacturasNotas').style.display = '';
	                                        byId('tdContenidoDocumento').style.display = '';
	                                        byId('tdFacturas').className = 'rafktabs_titleActive';
	                                        byId('tdNotaCargo').className = 'rafktabs_title';
	                                        byId('txtIdFactura').value = '';
	                                        byId('txtNumeroFactura').value = '';
	                                        byId('txtSaldoFactura').value = '';
	                                        byId('txtFechaRegistroFactura').value = '';
	                                        byId('txtFechaVencimientoFactura').value = '';
	                                        byId('txtDescripcionFactura').innerHTML = '';
	                                        byId('tdFacturaNota').innerHTML = 'SIN DOCUMENTO';

	                                        //si cierra y abre no muestra el buscador input correcto

	                                        byId('buscarTipoDcto').value = '2';//factura
                                            byId('btnBuscarFacturaNota').click();

	                                        byId('tdFacturas').onclick = function(){
	                                            byId('tdNotaCargo').className = 'rafktabs_title';
	                                            byId('tdFacturas').className = 'rafktabs_titleActive';
	                                            byId('buscarTipoDcto').value = '2';//factura
	                                            byId('btnBuscarFacturaNota').click();
	                                            };

	                                         byId('tdNotaCargo').onclick = function(){
	                                            byId('tdNotaCargo').className = 'rafktabs_titleActive';
	                                            byId('tdFacturas').className = 'rafktabs_title';
	                                            byId('buscarTipoDcto').value = '1';//nota de cargo
	                                            byId('btnBuscarFacturaNota').click();
	                                            };
	                                    }">
                                        <img src="../img/iconos/help.png"/>
                                    </button>
									</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="tituloCampo" width="10%" align="right"><span class="textoRojoNegrita">*</span>N&uacute;mero</td>
                    <td><input type="text" id="txtNumeroFactura" name="txtNumeroFactura" readonly="readonly" />
                    <td class="tituloCampo" width="15%" align="right" id="tdSaldoFactura">Saldo Factura</td>
                    <td id="tdTxtSaldoFactura"><input type="text" id="txtSaldoFactura" name="txtSaldoFactura" readonly="readonly" />
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">Fecha Registro</td>
                    <td align="left" colspan="1">
                        <input type="text" id="txtFechaRegistroFactura" name="txtFechaRegistroFactura" readonly="readonly" size="15" />
                    </td>
                    <td class="tituloCampo" align="right" width="20%">Fecha Vencimiento</td>
                    <td align="left">
                        <input type="text" id="txtFechaVencimientoFactura" name="txtFechaVencimientoFactura" readonly="readonly" size="15" />
                    </td>
                    
                    <td class="tituloCampo" align="right" width="20%">Base Imponible</td>
                    <td align="left">
                        <input type="text" id="hddBaseImponible" onkeyup="calcularConBase();" name="hddBaseImponible" size="15" />
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">Descripción</td>
                    <td align="left" colspan="4">
                        <textarea id="txtDescripcionFactura" name="txtDescripcionFactura" readonly="readonly" cols="55"></textarea>
                        <input type="hidden" id="hddIva" name="hddIva" />
<!--                        <input type="hidden" id="hddBaseImponible" name="hddBaseImponible" />-->
                        <input type="hidden" id="hddMontoExento" name="hddMontoExento" />
                        <input type="hidden" id="hddTipoDocumento" name="hddTipoDocumento" />
                    </td>
                    <td>
                        <table width="100%" border="0">
                            <tr>
                                <td id="tdFacturaNota" style="white-space:nowrap;" class="divMsjInfo2" align="center">SIN DOCUMENTO</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                </table>
                </fieldset>
                
                <fieldset><legend class="legend">Detalles del Cheque</legend>
                <table border="0" width="100%">
                <tr>
                    <td class="tituloCampo" align="right" width="10%">
                        <span class="textoRojoNegrita">*</span>Fecha:
                    </td>
                    <td align="left" >
                        <input type="text" id="txtFechaRegistro" name="txtFechaRegistro" readonly="readonly" size="15"/>
						<a class="modalImg" id="aDesbloquearFechaRegistro" rel="#divFlotante2" onclick="byId('hddChequeRegistrado').value = 0; abrirDivFlotante2(this, 'tblPermiso', 'te_cheque_fecha_registo');">
							<img src="../img/iconos/lock_go.png" style="cursor:pointer" title="Desbloquear">
						</a>
                    </td>
                    <td class="tituloCampo" align="right" width="120">
                        <span class="textoRojoNegrita">*</span>Fecha Liberación:
                    </td>
                    <td align="left">
                        <input type="text" id="txtFechaLiberacion" name="txtFechaLiberacion" class="inputHabilitado" readonly="readonly" size="15"/>
                    </td>
                    <td align="left" width="30%">
                    </td>
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">
                        <span class="textoRojoNegrita">*</span>Número de Cheque:                    
                    </td>
                    <td colspan="2">
                    <input type="text" readonly="readonly" id="numCheque" name="numCheque" size="25" onkeypress="return numeros(event);" style="text-align:left"/><span id="spanChequeManual" style="display:none;" class="textoRojoNegrita"> (Nro Cheque Manual)</span>
                    </td>
    
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">
                        <span class="textoRojoNegrita">*</span>Concepto:
                    </td>
                    <td colspan="4" align="left">
                        <textarea id="txtConcepto" name="txtConcepto" cols="48" rows="2" class="inputHabilitado" disabled="disabled" onkeyup="validarLongitud('txtConcepto');" onblur="validarLongitud('txtConcepto'); byId('txtComentario').value = this.value; validarMonto();"></textarea>
                        <input type="hidden" id="hddIdCheque" name="hddIdCheque" />
                    </td> 
                </tr>
                <tr>
                    <td class="tituloCampo" align="right">
                        <span class="textoRojoNegrita">*</span>Observación:
                    </td>
                    <td colspan="4" align="left">
                        <textarea id="txtComentario" name="txtComentario" cols="48" rows="2" class="inputHabilitado" disabled="disabled" onkeyup="validarLongitud('txtComentario');" onblur="validarLongitud('txtComentario');"></textarea>
                    </td>
                </tr>
                <tr id="trChequeEntregado" style="display:none">
                    <td class="tituloCampo" align="right">
                    	<span class="textoRojoNegrita">*</span>Cheque Entregado:
                    </td>
                    <td colspan="4" align="left">
                        <input type="checkbox" id="cbxChequeEntregado" name="cbxChequeEntregado" />
                    </td>
         	   </tr>
                <tr>
                    <td colspan="6">
                        <table width="100%">
                            <tr>
                                <td>
                                    <hr>
                                    <div style="max-height:150px; overflow:auto; padding:1px">
                                    <table border="0" class="tabla" cellpadding="2" width="97%" style="margin:auto;">
                                    	<tr>
                                            <td class="tituloCampo" align="right">
                                                <span class="textoRojoNegrita">*</span>Monto: 
                                            </td>
                                            <td colspan="2" align="left">
                                                <input type="text" id="txtMonto" name="txtMonto" class="inputHabilitado" size="30" style="text-align:right" onkeypress="return validarSoloNumerosReales(event);" onblur="validarMonto();" onfocus="byId('btnAceptar').disabled = false;"/>
                                            </td>
                                            <td class="tituloCampo" align="right" id="tdTextoRetencionISLR" style="display:none">
                                                <span class="textoRojoNegrita">*</span>Retencion ISLR: 
                                            </td>
                                            <td colspan="2" align="left" id="tdMontoRetencionISLR" style="display:none">
                                                <input type="text" id="txtMontoRetencionISLR" name="txtMontoRetencionISLR" size="30" style="text-align:right" readonly="readonly" />
                                            </td>
                                        </tr>
                                    </table>
                                    </div>
                                </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
                </fieldset>
    		</td>
    	</tr>
    	<tr>
            <td align="right" colspan="2"><hr>
            	<button type="button" id="btnActualizar" name="btnActualizar" onclick="validarActualizarCheque();" style="display:none">Actualizar</button>
            	<button type="button" id="btnAceptar" name="btnAceptar" onclick="this.disabled = true; validarCheque();" disabled="disabled">Aceptar</button>
            	<button type="button" id="btnCancelar" class="close">Cancelar</button>
            </td>
    	</tr>
    </table>
    </form>
    </div>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:2;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    
    <form id="frmPermiso" name="frmPermiso" onsubmit="return false;" style="margin:0px">
        <table border="0" id="tblPermiso" style="display:none" width="560">
        <tr>
            <td>
                <table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="25%">Acción:</td>
                    <td width="75%"><input type="text" id="txtDescripcionPermiso" name="txtDescripcionPermiso" class="inputSinFondo" readonly="readonly" style="text-align:left"/></td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Ingrese Clave:</td>
                    <td>
                        <input type="password" id="txtContrasena" name="txtContrasena" size="30"/>
                        <input type="hidden" id="hddModulo" name="hddModulo" readonly="readonly" size="30"/>
                        <input type="hidden" id="hddIdChequeEditarFechaPermiso" name="hddIdChequeEditarFechaPermiso" readonly="readonly" size="30"/>
                        <input type="hidden" id="hddChequeRegistrado" name="hddChequeRegistrado" readonly="readonly" size="30"/>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="submit" id="btnGuardarPermiso" name="btnGuardarPermiso" onclick="validarFrmPermiso();">Aceptar</button>
                <button type="button" id="btnCancelarPermiso" name="btnCancelarPermiso" class="close">Cancelar</button>
                </td>
            </tr>
        </table>
    </form>
    
    <table border="0" id="tblListaEmpresa" style="display:none" width="610">
    <tr>
        <td>
            <form onsubmit="return false;" style="margin:0" name="frmBuscarEmpresa" id="frmBuscarEmpresa">
                <table align="right">
                <tr align="left">
                    <td width="120" align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" onkeyup="byId('btnBuscarEmpresa').click();" class="inputHabilitado" name="txtCriterioBuscarEmpresa" id="txtCriterioBuscarEmpresa"></td>
                    <td>
                        <button onclick="xajax_buscarEmpresa(xajax.getFormValues('frmBuscarEmpresa'));" name="btnBuscarEmpresa" id="btnBuscarEmpresa" type="button">Buscar</button>
                        <button onclick="document.forms['frmBuscarEmpresa'].reset(); byId('btnBuscarEmpresa').click();" type="button">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
        <td id="tdListaEmpresa"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarEmpresa" name="btnCancelarEmpresa" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    
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
       
   	<table border="0" id="tblBeneficiariosProveedores" style="display:none;" width="700px">
    <tr>
    	<td>
			<form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0">
                    <tr align="center">
                        <td class="rafktabs_title" id="tdBeneficiarios" width="120">Beneficiarios</td>
                        <td class="rafktabs_title" id="tdProveedores" width="120">Proveedores</td>
		            </tr>
                    <tr>
                	<td align="right" class="tituloCampo" width="15">Criterio:</td>
                	<td>
                        <input type="hidden" id="buscarProv" name="buscarProv" value="2" />
                        <input type="hidden" id="buscarListado" name="buscarListado" value="0" />
                        <input type="text" id="txtCriterioBusqProveedor" name="txtCriterioBusqProveedor" onkeyup="byId('btnBuscarCliente').click();" class="inputHabilitado"/>
					</td>
                        <td><button type="button" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));" class="puntero">Buscar</button>
                        	<button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
                        </td>
                </tr>
					</table>
				</td>
            </tr>
            <tr>
                <td class="rafktabs_panel" id="tdContenido" style="border:0px;"></td>
            </tr>
            </table>
            </form>
        </td>
    </tr>
	<tr>
    	<td align="right"><hr>
			<button type="button" id="btnCancelarBeneficiariosProveedores" name="btnCancelarBeneficiariosProveedores" class="close">Cancelar</button>
		</td>
    </tr>
    </table>
    
   	<table id="tblFacturasNotas" border="0" style="display:none" width="1050">
    <tr>
    	<td>
        	<form id="frmBuscarDocumento" name="frmBuscarDocumento" onsubmit="byId('btnBuscarFacturaNota').click(); return false;" style="margin:0">
        	<table cellpadding="0" cellspacing="0" width="100%">
            <tr>
            	<td>
					<table align="right">
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Departamento:</td>
                            <td id="tdlstModulo"></td>
                            <td align="right" class="tituloCampo" width="120">Criterio:</td>
                        <td>
                            <input type="hidden" id="buscarTipoDcto" name="buscarTipoDcto" value="2" />
                            <input type="text" id="txtCriterioBuscarFacturaNota" name="txtCriterioBuscarFacturaNota" onkeyup="byId('btnBuscarFacturaNota').click();" class="inputHabilitado"/>
                        </td>
                             <td>
			     <button type="button" id="btnBuscarFacturaNota" name="btnBuscarFacturaNota" onclick="xajax_buscarDocumento(xajax.getFormValues('frmBuscarDocumento'), byId('hddIdEmpresa').value, byId('txtIdBeneficiario').value, byId('buscarTipoDcto').value);">Buscar</button>
                             <button type="button" onclick="document.forms['frmBuscarDocumento'].reset(); byId('btnBuscarFacturaNota').click();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
            	<td align="left">
                	<table cellpadding="0" cellspacing="0" width="100%">                    
                    <tr align="center">
                        <td class="rafktabs_title" id="tdFacturas"  width="120">Facturas</td>
                        <td class="rafktabs_title" id="tdNotaCargo" width="120">Notas De Cargo</td>                        
						<td align="right">
                            <table style="margin-right: 100px;">
                                <tr>
                                    <td width="120" align="right" class="tituloCampo">Días Vencidos:</td>
                                    <td align="left" id="tdDiasVencidos">
                                </tr>
                            </table>
                        </td>
                 	</tr>
					</table>
				</td>
            </tr>
            <tr>
				<td class="rafktabs_panel" id="tdContenidoDocumento" style="display:none; border:0px;"></td>
            </tr>
            </table></form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarFacturaNota" name="btnCancelarFacturaNota" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    
	<form id="frmAnular" name="frmAnular" onsubmit="return false;">
	<table border="0" id="tblAnularCheque">
		<tr>
			<td align="right" class="tituloCampo" width="120">Nro Cheque:</td>
            <td>
                <input type="text" id="txtNumCheque" name="txtNumCheque"  readonly="readonly">
                <input type="hidden" id="hddIdChequeA" name="hddIdChequeA" readonly="readonly" />
            </td>
        </tr>
		<tr>
			<td align="right" class="tituloCampo" width="120">Comision:</td>
			<td><label>
				<input name="txtComision" id="txtComision" type="txt" class="inputHabilitado" />
			</label></td>
		</tr>
		<tr>
			<td align="right" colspan="2"><hr>
			<button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmAnularCheque();">Aceptar</button>
			<button type="button" id="btnCancelarAnular" name="btnCancelarAnular" class="close">Cancelar</button>
			</td>
		</tr>
	</table>
    </form>
    
    <table id="tblListaPropuestaPago" border="0" style="display:none" width="780">
    <tr>
		<td>
            <table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td class="tituloCampo" align="right" width="120">Nro. Propuesta:</td>
                    <td id="numeroPropuestaPago" ></td>
                    <td class="tituloCampo" align="right" width="120">Fecha Propuesta:</td>
                    <td id="fechaPropuestaPago" ></td>
                    <td class="tituloCampo" align="right" width="120">Nro. Cheque:</td>
                    <td id="numeroChequePropuestaPago" ></td>
                    <td class="tituloCampo" align="right" width="120">Estado Propuesta:</td>
                    <td id="estadoPropuestaPago" ></td>
                </tr>
            </table>
		</td>
	</tr>
    <tr>
    	<td>
            <fieldset>
                <legend class="legend">Detalles de la Propuesta</legend>
                <div id="detallePropuestaPago"></div>
            </fieldset>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarPropuesta" name="btnCancelarPropuesta" class="close">Cancelar</button>
        </td>
    </tr>
    </table>
    
    <form id="frmEditarFechaCheque" name="frmEditarFechaCheque" onsubmit="return false;" style="margin:0px">
    <table border="0" id="tblEditarFechaCheque" style="display:none" width="320">
    <tr>
    	<td>
        	<fieldset><legend class="legend">Datos Cheque</legend>
        	<table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nro. Cheque:</td>
                <td>
                    <input type="text" id="txtNumeroChequeEditar" name="txtNumeroChequeEditar"  readonly="readonly">
                    <input type="hidden" id="hddIdChequeEditarFecha" name="hddIdChequeEditarFecha" readonly="readonly" />
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Fecha Cheque:</td>
                <td>
                    <input type="text" id="txtFechaChequeRegistrado" name="txtFechaChequeRegistrado"  readonly="readonly">
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Nueva Fecha:</td>
                <td>
                    <input type="text" id="txtFechaChequeEditar" name="txtFechaChequeEditar"  readonly="readonly" class="inputHabilitado">
                </td>
            </tr>
            </table>
            </fieldset>
        </td>
    </tr>
    <tr>
    	<td>
        	<fieldset><legend class="legend">Datos Contabilidad</legend>
        	<table width="100%">
            <tr>
                <td align="right" class="tituloCampo" width="120">Enviado:</td>
                <td>
                    <input type="text" id="txtEnviadoContabilidad" name="txtEnviadoContabilidad"  readonly="readonly">
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="120">Nro. Comprobante:</td>
                <td>
                    <input type="text" id="txtNumeroComprobanteContabilidad" name="txtNumeroComprobanteContabilidad"  readonly="readonly">
                </td>
            </tr>
            <tr>
                <td align="right" class="tituloCampo" width="120">Fecha Comprobante:</td>
                <td>
                    <input type="text" id="txtFechaComprobanteContabilidad" name="txtFechaComprobanteContabilidad"  readonly="readonly">
                </td>
            </tr>
            </table>
            </fieldset>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="2"><hr>
        <button type="button" onclick="validarFrmEditarFechaCheque();">Aceptar</button>
        <button type="button" id="btnCancelarEditarFechaCheque" name="btnCancelarEditarFechaCheque" class="close">Cancelar</button>
        </td>
    </tr>
	</table>
    </form>
</div>

<script>

<?php  //ordenamiento en historico siempre mostrar primero el ultimo
	if($_GET['acc'] == 3){//3 es historico devolucion ?> 
			xajax_listaCheques(0,'fecha_registro','DESC');
<?php } else { ?>
			xajax_listaCheques(0,'numero_cheque','ASC');
<?php } ?>

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',"onchange=\"byId('btnBuscar').click();\"");
xajax_cargaLstEstado();
xajax_cargaListRetencionISLR();
xajax_cargaLstModulo('', "onchange=\"byId('btnBuscarDocumento').click();\"");//te general
xajax_cargarDiasVencidos();//te general

$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});

new JsDatePick({
	useMode:2,
	target:"txtFechaDesde",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaHasta",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaLiberacion",
	dateFormat:"<?php echo spanDatePick; ?>",
	cellColorScheme:"red"
});

new JsDatePick({
	useMode:2,
	target:"txtFechaChequeEditar",
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

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

</script>