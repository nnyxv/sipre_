<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("cjrs_nota_credito_list","insertar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_nota_credito_form.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Nota de Crédito</title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css" />
    
    <link rel="stylesheet" type="text/css" href="../js/domDragCaja.css" />
    <script type="text/javascript" language="javascript" src="../js/jquery05092012.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
		
	<script language="javascript">
	function abrirDivFlotante1(nomObjeto, verTabla, valor) {
		byId('tblLista').style.display = 'none';
		byId('tblListaAnticipo').style.display = 'none';
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('trBuscarCliente').style.display = '';
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('btnBuscarCliente').click();
				
				tituloDiv1 = 'Clientes';
				byId(verTabla).width = "960";
			}
		} else if (verTabla == "tblListaAnticipo") {
			document.forms['frmBuscarAnticipo'].reset();
			
			byId('txtFechaDesde').className = 'inputHabilitado';
			byId('txtFechaHasta').className = 'inputHabilitado';
			byId('txtCriterioBuscarAnticipo').className = 'inputHabilitado';
			
			/*byId('txtFechaDesde').value = "<?php echo date(str_replace("d","01",spanDateFormat)); ?>";
			byId('txtFechaHasta').value = "<?php echo date(spanDateFormat); ?>";*/
			
			selectedOption('lstTipoDcto', valor);
			byId('lstTipoDcto').onchange = function(){ selectedOption(this.id, valor); }
			byId('lstTipoDcto').className = 'inputInicial';
			
			byId('btnBuscarAnticipo').click();
			
			tituloDiv1 = 'Dctos. Por Cobrar';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblLista") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			}
		} else if (valor == "tblListaAnticipo") {
			byId('txtCriterioBuscarAnticipo').focus();
			byId('txtCriterioBuscarAnticipo').select();
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2) {
		byId('tblAnticipoNotaCreditoChequeTransferencia').style.display = 'none';
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			
			byId('txtMontoDocumento').className = 'inputHabilitado';
			
			xajax_cargarSaldoDocumentoPagar(valor, valor2, xajax.getFormValues('frmListaDctoPagado'));
			
			tituloDiv2 = 'Anticipos';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblAnticipoNotaCreditoChequeTransferencia") {
			byId('txtMontoDocumento').focus();
			byId('txtMontoDocumento').select();
		}
	}
	
	function validarEliminarDcto(objBoton){
		if (confirm('¿Seguro desea eliminar el documento seleccionado?') == true) {
			 $(objBoton).closest('tr').remove();
			 calcularPagos();
		}
	}
	
	function validarFrmAgregarDcto(tipoDcto) {
		error = false;
		if (!(validarCampo('txtIdCliente', 't', '') == true
		&& validarCampo('lstTipoNotaCredito', 't', 'lista') == true
		&& validarCampo('txtTotalNotaCredito', 't', 'monto') == true)) {
			validarCampo('txtIdCliente', 't', '');
			validarCampo('lstTipoNotaCredito', 't', 'lista');
			validarCampo('txtTotalNotaCredito', 't', 'monto');
			
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos");
			return false;
		} else {
			abrirDivFlotante1(byId('aAgregarAnticipoOtro'), 'tblListaAnticipo', tipoDcto);
		}
	}
	
	function validarFrmDcto(){
		lstTipoNotaCredito = byId('lstTipoNotaCredito').value;
		error = false;
		
		/*if(inArray(lstTipoNotaCredito, [1,3])){//tipo cliente y pnd requiere cliente
			if (!(validarCampo('txtIdCliente','t','') == true)){					
				validarCampo('txtIdCliente','t','');
				error = true;
			}
		}*/
		
		if(!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('lstTipoNotaCredito','t','') == true
		&& validarCampo('txtIdCliente','t','') == true
		&& validarCampo('txtFecha','t','fecha') == true
		&& validarCampo('lstModulo','t','listaExceptCero') == true
		&& validarCampo('txtObservacion','t','') == true)){			
			validarCampo('txtIdEmpresa','t','');
			validarCampo('lstTipoNotaCredito','t','');	
			validarCampo('txtIdCliente','t','');	
			validarCampo('txtFecha','t','fecha');
			validarCampo('lstModulo','t','listaExceptCero');
			validarCampo('txtObservacion','t','');
			error = true;
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
		} else {
			if(inArray(lstTipoNotaCredito, [1,4])) {
				mensaje = "¿Seguro desea generar la Nota de Crédito?";
			} else if(lstTipoNotaCredito == "2") {
				mensaje = "¿Seguro desea generar pago de anticipo suplidor?";
			} else if(lstTipoNotaCredito == "3") {
				mensaje = "¿Seguro desea generar pago de anticipo PND?";
			}
			
			if (confirm(mensaje)) { 
				xajax_guardarNotaCredito(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmListaDctoPagado'));
			}
		}
	}
	
	function validarFrmAnticipoNotaCreditoChequeTransferencia(){
		var saldo = byId('txtSaldoDocumento').value.replace(/,/gi,'');
		var monto = byId('txtMontoDocumento').value.replace(/,/gi,'');
		var montoFaltaPorPagar = byId('txtMontoRestante').value.replace(/,/gi,'');
		
		if (parseFloat(saldo) < parseFloat(monto)) {
			alert("El monto a pagar no puede ser mayor que el saldo del documento");
		} else {
			if (parseFloat(montoFaltaPorPagar) >= parseFloat(monto)) {
				if (confirm("Desea cargar el pago?")) {
					xajax_insertarDctoPagado(xajax.getFormValues('frmAnticipoNotaCreditoChequeTransferencia'), xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmListaAnticipo'));
				}
			} else {
				alert("El monto a pagar no puede ser mayor que el saldo del nota_credito");
			}
		}
	}
	
	function asignarTipoNotaCredito(lstTipoNotaCredito){
		if (limpiarNotaCredito() != true) {
			selectedOption('lstTipoNotaCredito', byId('hddTipoNotaCredito').value);
		} else {
			byId('hddTipoNotaCredito').value = lstTipoNotaCredito;
		}
		
		if (inArray(lstTipoNotaCredito, [1,3])) { // 1 = Cliente, 3 = PND
			if (lstTipoNotaCredito == 1) { // 1 = Cliente
				byId('btnAgregarFactura').style.display = "";
				byId('btnAgregarNotaDebito').style.display = "";
				byId('btnAgregarAnticipo').style.display = "";
				byId('btnAgregarAnticipoOtro').style.display = "none";
				byId('tituloAnticipos').innerHTML = "";
			} else if (lstTipoNotaCredito == 3) { // 3 = PND
				byId('btnAgregarFactura').style.display = "";
				byId('btnAgregarNotaDebito').style.display = "";
				byId('btnAgregarAnticipo').style.display = "";
				byId('btnAgregarAnticipoOtro').style.display = "";
				byId('tituloAnticipos').innerHTML = "<span class='textoRojoNegrita'>*</span>Agregar Anticipo a Cobrar (PND):";
				byId('tdFlotanteTitulo1').innerHTML = "Anticipos (Bono Suplidor)";
			}
		} else if (lstTipoNotaCredito == 2) { // 2 = Bono Suplidor
			byId('btnAgregarFactura').style.display = "";
			byId('btnAgregarNotaDebito').style.display = "";
			byId('btnAgregarAnticipo').style.display = "";
			byId('btnAgregarAnticipoOtro').style.display = "";
			byId('tituloAnticipos').innerHTML = "<span class='textoRojoNegrita'>*</span>Agregar Anticipo a Cobrar (Bono Suplidor):";
			byId('tdFlotanteTitulo1').innerHTML = "Anticipos (PND)";
		} else if (lstTipoNotaCredito == 4) { // 4 = Varios Clientes
			byId('btnAgregarFactura').style.display = "";
			byId('btnAgregarNotaDebito').style.display = "";
			byId('btnAgregarAnticipo').style.display = "";
			byId('btnAgregarAnticipoOtro').style.display = "none";
			byId('tituloAnticipos').innerHTML = "";
		} else {
			byId('btnAgregarFactura').style.display = "none";
			byId('btnAgregarNotaDebito').style.display = "none";
			byId('btnAgregarAnticipo').style.display = "none";
			byId('btnAgregarAnticipoOtro').style.display = "none";
		}
	}
	
	function calcularPagos(){
		/*$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado)').removeClass();//limpio de clases
		$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado):odd').addClass('trResaltar5');//odd impar
		$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado):even').addClass('trResaltar4');//even par*/
		
		$('tr[name=trItmDctoPagado]').removeClass();//limpio de clases
		$('tr[name=trItmDctoPagado]:odd').addClass('trResaltar5');//odd impar
		$('tr[name=trItmDctoPagado]:even').addClass('trResaltar4');//even par
		
		xajax_calcularPagos(byId('txtTotalNotaCredito').value, xajax.getFormValues('frmListaDctoPagado'), xajax.getFormValues('frmDcto'));
	}
	
	function copiarMontoCheque(){
		var saldo = byId('txtTotalNotaCredito').value;
		byId('txtMontoRestante').value = saldo;
		calcularPagos();
	}
	
	function limpiarNotaCredito(){
		if ((byId('cbx4') != undefined && confirm('Se elimaran todos los documentos a Cobrar. ¿Seguro desea cambiar el tipo de nota_credito?'))
		|| byId('cbx4') == undefined) {
			// Cierro todas las ventanas:
			byId('btnCancelarLista').click();
			byId('btnCancelarAnticipoNotaCreditoChequeTransferencia').click();
			byId('btnCancelarListaAnticipo').click();
			
			// Quito anticipos y reestablesco montos del nota_credito
			$('#tablaAnticiposAgregados tr:not(:first):not(:last):not(#trItmPieDctoPagado)').remove(); // Quita anticipos si los hay
			copiarMontoCheque();//reinicio monto por pagar
			byId('txtTotalDctoPagado').value = '0.00';//reinicio monto pagado
			
			return true;
		} else {
			return false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_cj.php"); ?></div>
    
	<div id="divInfo" class="print">
		<table border="0" width="100%">
		<tr>
			<td class="tituloPaginaCaja">Nota de Crédito</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="left">
			<form id="frmDcto" name="frmDcto" onsubmit="return false;" style="margin:0">
				<table border="0" width="100%">
                <tr align="left">
					<td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empresa:</td>
					<td width="58%">
						<table cellpadding="0" cellspacing="0">
						<tr>
							<td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" readonly="readonly" size="6" style="text-align:right;"/></td>
							<td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
						</tr>
						</table>
					</td>
                    <td align="right" width="12%"></td>
                    <td width="18%"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Registrado por:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpleado" name="txtIdEmpleado" readonly="readonly" size="6" style="text-align:right"/></td>
                            <td></td>
                            <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
				<tr>
					<td colspan="4">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td valign="top" width="70%">
							<fieldset><legend class="legend">Cliente</legend>
                                <table id="tblIdCliente" border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Cliente:</td>
                                    <td colspan="3">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false');" size="6" style="text-align:right"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarCliente" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista', 'Cliente');">
                                                <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                        </tr>
                                        <tr align="center">
                                            <td id="tdMsjCliente" colspan="3"></td>
                                        </tr>
                                        </table>
                                        <input type="hidden" id="hddPagaImpuesto" name="hddPagaImpuesto"/>
                                    </td>
                                    <td align="right" class="tituloCampo"><?php echo $spanClienteCxC; ?>:</td>
                                    <td><input type="text" id="txtRifCliente" name="txtRifCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" rowspan="2">Dirección:</td>
                                    <td colspan="3" rowspan="2"><textarea id="txtDireccionCliente" name="txtDireccionCliente" cols="55" readonly="readonly" rows="3"></textarea></td>
                                    <td align="right" class="tituloCampo"><?php echo $spanNIT; ?>:</td>
                                    <td><input type="text" id="txtNITCliente" name="txtNITCliente" readonly="readonly" size="16" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Días Crédito:</td>
                                    <td>
                                        <table border="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td width="40%">Días:</td>
                                            <td width="60%"><input type="text" id="txtDiasCreditoCliente" name="txtDiasCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        </tr>
                                        <tr>
                                            <td>Disponible:</td>
                                            <td><input type="text" id="txtCreditoCliente" name="txtCreditoCliente" readonly="readonly" size="12" style="text-align:right"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="16%">Teléfono:</td>
                                    <td width="15%"><input type="text" id="txtTelefonoCliente" name="txtTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                                    <td align="right" class="tituloCampo" width="16%">Otro Teléfono:</td>
                                    <td width="15%"><input type="text" id="txtOtroTelefonoCliente" name="txtOtroTelefonoCliente" readonly="readonly" size="18" style="text-align:center"/></td>
                                    <td width="16%"></td>
                                    <td width="22%"></td>
                                </tr>
                                </table>
                            </fieldset>
                            
                            <fieldset><legend class="legend">Observación</legend>
                                <table>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120"><span class="textoRojoNegrita">*</span>Observación:</td>
                                    <td><textarea name="txtObservacion" id="txtObservacion" cols="60" rows="2"></textarea></td>
                                </tr>
                                </table>
							</fieldset>
							</td>
							<td valign="top" width="30%">
							<fieldset><legend class="legend">Nota de Crédito</legend>
								<input type="hidden" id="hddIdNotaCredito" name="hddIdNotaCredito"/>
								<table border="0" width="100%">
								<tr align="left">
                                    <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Tipo de Nota de Crédito:</td>
                                    <td colspan="2" width="60%">
	                                    <div id="tdlstTipoNotaCredito"></div>
										<input type="hidden" id="hddTipoNotaCredito" name="hddTipoNotaCredito"/>
                                    </td>
                                </tr>
								<tr align="left">
									<td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
									<td id="tdlstModulo" colspan="2"></td>
								</tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Nota de Crédito:</td>
                                    <td colspan="2"><input type="text" id="txtNumeroNotaCredito" name="txtNumeroNotaCredito" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Control:</td>
                                    <td colspan="2"><input type="text" id="txtNumeroControlNotaCredito" name="txtNumeroControlNotaCredito" readonly="readonly" size="20" style="color:#F00; font-weight:bold; text-align:center;"/></td>
                                </tr>
                                <tr align="left">
				                    <td align="right" class="tituloCampo">Fecha Registro:</td>
                				    <td colspan="2"><input type="text" id="txtFecha" name="txtFecha" readonly="readonly" size="10" style="text-align:center"/></td>
								</tr>
                                <tr align="left">                        
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Vendedor:</td>
                                    <td id="tdlstVendedor" colspan="2">
                                        <select name="lstVendedor" id="lstVendedor"></select>
                                    </td>
                                </tr>
                                <tr id="trNetoOrden" align="right" class="trResaltarTotal">
                                    <td class="tituloCampo">Total:</td>
                                    <td id="tdTotalRegistroMoneda"></td>
                                    <td><input type="text" id="txtTotalNotaCredito" name="txtTotalNotaCredito" class="inputSinFondo" readonly="readonly" size="17" style="text-align:right"/></td>
                                </tr>
                                <tr align="right" class="trResaltarTotal3">
                                    <td class="tituloCampo">Saldo Disponible:</td>
                                    <td id="tdTotalSaldoMoneda"></td>
                                    <td><input type="text" id="txtTotalSaldo" name="txtTotalSaldo" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                <tr align="left">
                                    <td id="tdtxtEstatus" colspan="3"><input type="text" id="txtEstatus" name="txtEstatus" class="inputSinFondo" readonly="readonly" size="20" style="text-align:center"/></td>
                                </tr>
								</table>
							</fieldset>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</form>
			</td>
		</tr>
        <tr>
			<td width="100%">
			<form id="frmListaDctoPagado" name="frmListaDctoPagado" onsubmit="return false;">
				<fieldset><legend class="legend">Dctos. Por Cobrar</legend>
                
                <table border="0" >
                <tr>
                    <td align="left">
                        <button type="button" id="btnAgregarFactura" onclick="validarFrmAgregarDcto('FACTURA');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td id="tituloFacturas">Agregar Factura a Cobrar</td></tr></table></button>
            			<a class="modalImg" id="aAgregarFactura" rel="#divFlotante1"></a>
                        
                        <button type="button" id="btnAgregarNotaDebito" onclick="validarFrmAgregarDcto('NOTA DEBITO');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td id="tituloNotaDebitos">Agregar Nota de Débito a Cobrar</td></tr></table></button>
            			<a class="modalImg" id="aAgregarNotaDebito" rel="#divFlotante1"></a>
                        
                        <button type="button" id="btnAgregarAnticipo" onclick="validarFrmAgregarDcto('ANTICIPO');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td id="tituloAnticipo">Agregar Anticipo a Cobrar</td></tr></table></button>
            			<a class="modalImg" id="aAgregarAnticipo" rel="#divFlotante1"></a>
                        
                        <button type="button" id="btnAgregarAnticipoOtro" onclick="validarFrmAgregarDcto('ANTICIPO_OTRO');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td id="tituloAnticipos">Agregar Anticipo a Cobrar</td></tr></table></button>
            			<a class="modalImg" id="aAgregarAnticipoOtro" rel="#divFlotante1"></a>
                    </td>
                </tr> 
                </table>
                    
                <table width="100%">
                <tr>
                    <td>
                        <table width="100%" id="tablaAnticiposAgregados">
                        <tr align="center" class="tituloColumna">
                        	<td></td>
                			<td width="4%">Nro.</td>
                            <td width="8%">Fecha Pago</td>
                            <td width="16%">Empresa</td>
                            <td width="10%">Dcto. Pagado</td>
                            <td width="8%">Fecha Registro Dcto.</td>
                            <td width="10%">Nro. Dcto.</td>
                            <td width="24%">Cliente</td>
                            <td width="10%">Estado Dcto.</td>
                            <td width="10%">Monto</td>
                            <td></td>
                        </tr>
                        <tr id="trItmPieDctoPagado" class="trResaltarTotal">
                            <td align="right" class="tituloCampo" colspan="9">Total Dctos. Pagados:</td>
                            <td><input type="text" id="txtTotalDctoPagado" name="txtTotalDctoPagado" class="inputSinFondo" readonly="readonly" style="text-align:right" value="0.00"/></td>
                            <td></td>
                        </tr>
                        <tr class="trResaltarTotal3">
                            <td align="right" class="tituloCampo" colspan="9">Total Saldo Disponible:</td>
                            <td>
                                <input type="text" id="txtMontoRestante" name="txtMontoRestante" class="inputSinFondo" readonly="readonly" style="text-align:right;" value="0.00"/>
                                <input type="hidden" id="hddSaldoAnticipo" name="hddSaldoAnticipo"/>
                                <input type="hidden" id="hddMontoPorPagar" name="hddMontoPorPagar"/>
                            </td>
                            <td></td>
                        </tr>
                        </table>                
                    </td>
                </tr>
                </table>
				</fieldset>
			</form>
			</td>
		</tr>
		<tr align="right">
			<td colspan="8"><hr>
				<button type="button" id="btnGuardarPago" name="btnGuardarPago" onclick="validarFrmDcto();" ><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
				<button type="button" id="btnCancelar" name="btnCancelar" onclick="top.history.back();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
			</td>
		</tr>
		</table>
	</div>
    
	<div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<div id="divFlotante1" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
	<table border="0" id="tblLista" style="display:none" width="960">
    <tr id="trBuscarCliente">
    	<td>
        <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarCliente" name="txtCriterioBuscarCliente" onkeyup="byId('btnBuscarCliente').click();"/></td>
                <td>
                	<button type="submit" id="btnBuscarCliente" name="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'), xajax.getFormValues('frmDcto'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarCliente'].reset(); byId('btnBuscarCliente').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
	<tr>
		<td>
        <form id="frmLista" name="frmLista" onsubmit="return false;" style="margin:0">
			<input type="hidden" id="hddNumeroItm" name="hddNumeroItm"/>
            <table width="100%">
            <tr>
                <td><div id="divLista" style="width:100%;"></div></td>
            </tr>
			<tr>
				<td align="right"><hr>
                    <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
				</td>
			</tr>
			</table>
        </form>
		</td>
	</tr>
	</table>
    
<div id="tblListaAnticipo" style="max-height:520px; overflow:auto; width:960px;">
    <table border="0" width="100%">
	<tr id="trBuscarAnticipo">
		<td>
        <form id="frmBuscarAnticipo" name="frmBuscarAnticipo" onsubmit="return false;" style="margin:0">
			<table align="right">
			<tr align="left">
				<td align="right" class="tituloCampo" width="120">Tipo de Dcto.:</td>
                <td id="tdlstTipoDcto"></td>
			</tr>
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Fecha:</td>
                <td>
                    <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;Desde:&nbsp;</td>
                        <td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        <td>&nbsp;Hasta:&nbsp;</td>
                        <td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                    </tr>
                    </table>
                </td>
				<td align="right" class="tituloCampo" width="120">Criterio:</td>
				<td><input type="text" id="txtCriterioBuscarAnticipo" name="txtCriterioBuscarAnticipo" onkeyup="byId('btnBuscarAnticipo').click();"/></td>
				<td>
					<button type="button" id="btnBuscarAnticipo" onclick="xajax_buscarAnticipo(xajax.getFormValues('frmBuscarAnticipo'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaDctoPagado'));">Buscar</button>
					<button type="button" onclick="document.forms['frmBuscarAnticipo'].reset(); byId('btnBuscarAnticipo').click();">Limpiar</button>
				</td>
			</tr>
			</table>
        </form>
		</td>
	</tr>
	<tr>
		<td>
        <form id="frmListaAnticipo" name="frmListaAnticipo" onsubmit="return false;" style="margin:0">
			<table width="100%">
			<tr>
				<td id="divListaAnticipo"></td>
			</tr>
			<tr>
				<td align="right"><hr>
					<button type="button" id="btnCancelarListaAnticipo" name="btnCancelarListaAnticipo" class="close">Cerrar</button>
				</td>
			</tr>
			</table>
        </form>
		</td>
	</tr>
	</table>
</div>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmAnticipoNotaCreditoChequeTransferencia" name="frmAnticipoNotaCreditoChequeTransferencia" onsubmit="return false;" style="margin:0">
	<table border="0" id="tblAnticipoNotaCreditoChequeTransferencia" width="360">
	<tr align="left">
		<td align="right" class="tituloCampo" width="40%">Nro. Documento:</td>
		<td width="60%"><input type="text" id="txtNroDocumento" name="txtNroDocumento" readonly="readonly" size="20" style="text-align:center"/></td>
	</tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Saldo:</td>
		<td><input type="text" id="txtSaldoDocumento" name="txtSaldoDocumento" readonly="readonly" style="text-align:right"/></td>
	</tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Saldo Diferido:</td>
		<td><input type="text" id="txtSaldoDiferidoDocumento" name="txtSaldoDiferidoDocumento" readonly="readonly" style="text-align:right"/></td>
	</tr>
	<tr align="left">
		<td align="right" class="tituloCampo">Monto a Cobrar:</td>
		<td><input type="text" id="txtMontoDocumento" name="txtMontoDocumento" onblur="setFormatoRafk(this,2);" onclick="if (this.value <= 0) { this.select(); }" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
	</tr>
	<tr>
		<td align="right" colspan="2"><hr>
            <input type="hidden" id="hddIdDocumento" name="hddIdDocumento"/>
            <input type="hidden" id="hddTipoDocumento" name="hddTipoDocumento"/>
			<button type="submit" id="btnAceptarAnticipoNotaCreditoChequeTransferencia" name="btnAceptarAnticipoNotaCreditoChequeTransferencia" onclick="validarFrmAnticipoNotaCreditoChequeTransferencia();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Guardar</td></tr></table></button>
			<button type="button" id="btnCancelarAnticipoNotaCreditoChequeTransferencia" name="btnCancelarAnticipoNotaCreditoChequeTransferencia" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td>Cancelar</td></tr></table></button>
		</td>
	</tr>
	</table>
</form>
</div>

<script language="javascript">
window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"vino"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"vino"
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

xajax_cargarDcto('<?php echo $_GET['id']; ?>');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);
</script>