<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_tradein_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_an_tradein_list.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Trade-in</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <script type="text/javascript" language="javascript" src="../vehiculos/vehiculos.inc.js"></script>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
    <script>
	function abrirDivFlotante1(nomObjeto, verTabla, valor, valor2) {
		byId('tblUnidadFisica').style.display = 'none';
		byId('tblLista').style.display = 'none';
		byId('tblAjusteInventario').style.display = 'none';
		byId('tblTradeInCxP').style.display = 'none';
		
		if (verTabla == "tblUnidadFisica") {
			document.forms['frmUnidadFisica'].reset();
			byId('txtIdTradeIn').value = "";
				byId('hddIdModulo').value = "";
				byId('hddIdFormaPago').value = "";
				byId('hddIdConceptoPago').value = "";
				byId('hddIdEmpresa').value = "";
			
			byId('txtIdCliente_2').className = "inputHabilitado";
			
			xajax_formTradeIn(valor, xajax.getFormValues('frmAjusteInventario'));
			
			tituloDiv1 = 'Generar Nuevo <?php echo $spanAnticipo; ?>';
		} else if (verTabla == "tblAjusteInventario") {
			document.forms['frmAjusteInventario'].reset();
			byId('hddIdDcto').value = "";
			byId('hddIdTradeInAjusteInventario').value = "";
			
			byId('trCxP').style.display = 'none';
			byId('trCxC').style.display = 'none';
			
			byId('trAsignarUnidadFisica').style.display = 'none';
			byId('trUnidadFisica').style.display = '';
			byId('trDatosAnticipo').style.display = 'none';
			byId('trPolizasNoDevengadas').style.display = 'none';
			byId('trTotalCredito').style.display = 'none';
			
			byId('txtIdProv').className = 'inputHabilitado';
			byId('txtIdMotivo').className = 'inputHabilitado';
			byId('txtObservacionCxP').className = 'inputHabilitado';
			
			byId('txtIdMotivoCxC').className = 'inputHabilitado';
			byId('txtObservacionCxC').className = 'inputHabilitado';
			
			byId('txtIdMotivoAntCxC').className = 'inputHabilitado';
			
			if (inArray(valor, [0,1])) {
				byId('txtIdCliente').className = 'inputHabilitado';
				byId('txtIdCliente').readOnly = false;
				byId('btnListarCliente').style.display = '';
				
				byId('txtPlacaAjuste').className = 'inputHabilitado';
				byId('txtPlacaAjuste').readOnly = false;
				
				byId('txtFechaFabricacionAjuste').className = 'inputHabilitado';
				byId('txtFechaFabricacionAjuste').readOnly = false;
				
				byId('txtKilometrajeAjuste').className = 'inputHabilitado';
				byId('txtKilometrajeAjuste').readOnly = false;
				
				byId('txtFechaExpiracionMarbeteAjuste').className = 'inputHabilitado';
				byId('txtFechaExpiracionMarbeteAjuste').readOnly = false;
				
				byId('txtSerialCarroceriaAjuste').className = 'inputCompletoHabilitado';
				byId('txtSerialCarroceriaAjuste').readOnly = false;
				byId('txtSerialCarroceriaAjuste').onblur = function () { xajax_buscarCarroceria(xajax.getFormValues('frmAjusteInventario')); }
				
				byId('txtSerialMotorAjuste').className = 'inputHabilitado';
				byId('txtSerialMotorAjuste').readOnly = false;
				
				byId('txtNumeroVehiculoAjuste').className = 'inputHabilitado';
				byId('txtNumeroVehiculoAjuste').readOnly = false;

				byId('txtTituloVehiculoAjuste').className = 'inputHabilitado';
				byId('txtTituloVehiculoAjuste').readOnly = false;
				
				byId('txtRegistroLegalizacionAjuste').className = 'inputHabilitado';
				byId('txtRegistroLegalizacionAjuste').readOnly = false;
				
				byId('txtRegistroFederalAjuste').className = 'inputHabilitado';
				byId('txtRegistroFederalAjuste').readOnly = false;
				
				byId('txtObservacion').className = 'inputCompletoHabilitado';
				byId('txtIdUnidadFisicaAjuste').className = '';
				
				
				byId('trDatosAnticipo').style.display = '';
				byId('trPolizasNoDevengadas').style.display = '';
				byId('trTotalCredito').style.display = '';
				
				byId('datosVale').style.display = '';
				byId('btnGuardarAjusteInventario').style.display = '';
				byId('btnGenerarTradein').style.display = 'none';
				byId('btnListarVehiculos').style.display = 'none';
				
				byId('txtAllowance').onblur = function() { setFormatoRafk(this,2); calcularMonto(this.id); }
				byId('txtAllowance').className = 'inputHabilitado';
				byId('txtAllowance').readOnly = false;
				byId('txtAcv').onblur = function() { setFormatoRafk(this,2); calcularMonto(); }
				byId('txtAcv').className = 'inputHabilitado';
				byId('txtAcv').readOnly = false;
				byId('txtPayoff').className = 'inputHabilitado';
				byId('txtSubTotal').className = 'inputCompleto';
				
				jQuery(function($){
					$("#txtFechaFabricacionAjuste").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
					$("#txtFechaExpiracionMarbeteAjuste").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
				});
				
				new JsDatePick({
					useMode:2,
					target:"txtFechaFabricacionAjuste",
					dateFormat:"<?php echo spanDatePick; ?>",
					cellColorScheme:"orange"
				});
				
				new JsDatePick({
					useMode:2,
					target:"txtFechaExpiracionMarbeteAjuste",
					dateFormat:"<?php echo spanDatePick; ?>",
					cellColorScheme:"orange"
				});
							
				if (valor2 === 1) { // Solo mostrar datos para anticipo y cambiar boton
					byId('trUnidadFisica').style.display = 'none';
					byId('datosVale').style.display = 'none';
					byId('btnGuardarAjusteInventario').style.display = 'none';
					byId('btnGenerarTradein').style.display = '';
					byId('btnListarVehiculos').style.display = '';                                                    
					byId('txtAllowance').className = '';
					byId('txtAllowance').readOnly = true;
					byId('txtAcv').className = '';
					byId('txtAcv').readOnly = true;
				}
				
				xajax_formAjusteInventario(xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmAjusteInventario'), valor);
				
				tituloDiv1 = 'Ingreso de veh&iacute;culo Trade-In';
			} else {
				byId('txtIdCliente').className = 'inputInicial';
				byId('txtIdCliente').readOnly = true;
				byId('btnListarCliente').style.display = 'none';
				
				byId('txtPlacaAjuste').className = 'inputInicial';
				byId('txtPlacaAjuste').readOnly = true;
				
				byId('txtFechaFabricacionAjuste').className = 'inputInicial';
				byId('txtFechaFabricacionAjuste').readOnly = true;
				
				byId('txtKilometrajeAjuste').className = 'inputInicial';
				byId('txtKilometrajeAjuste').readOnly = true;
				
				byId('txtFechaExpiracionMarbeteAjuste').className = 'inputInicial';
				byId('txtFechaExpiracionMarbeteAjuste').readOnly = true;
				
				byId('txtSerialCarroceriaAjuste').className = 'inputInicial';
				byId('txtSerialCarroceriaAjuste').readOnly = true;
				byId('txtSerialCarroceriaAjuste').onblur = function () { }
				
				byId('txtSerialMotorAjuste').className = 'inputInicial';
				byId('txtSerialMotorAjuste').readOnly = true;
				
				byId('txtNumeroVehiculoAjuste').className = 'inputInicial';
				byId('txtNumeroVehiculoAjuste').readOnly = true;

				byId('txtTituloVehiculoAjuste').className = 'inputInicial';
				byId('txtTituloVehiculoAjuste').readOnly = true;
				
				byId('txtRegistroLegalizacionAjuste').className = 'inputInicial';
				byId('txtRegistroLegalizacionAjuste').readOnly = true;
				
				byId('txtRegistroFederalAjuste').className = 'inputInicial';
				byId('txtRegistroFederalAjuste').readOnly = true;
				
				byId('btnGuardarAjusteInventario').style.display = '';
				byId('btnGenerarTradein').style.display = 'none';
				byId('btnListarVehiculos').style.display = 'none';
				
				xajax_formEditarTradeIn(valor2);
				
				tituloDiv1 = 'Editar veh&iacute;culo Trade-In';
			}
		} else if (verTabla == "tblTradeInCxP") {
			document.forms['frmTradeInCxP'].reset();
			byId('hddIdTradeInCxP').value = "";
			
			xajax_formTradeInCxP(valor);
			
			tituloDiv1 = 'Cambiar Nota de Débito de Cuentas por Pagar';
		}
		
		byId(verTabla).style.display = '';
		openImg(nomObjeto);
		byId('tdFlotanteTitulo1').innerHTML = tituloDiv1;
		
		if (verTabla == "tblUnidadFisica") {
			byId('txtNombreUnidadBasica').focus();
			byId('txtNombreUnidadBasica').select();
		} else if (verTabla == "tblAjusteInventario") {
			if(!(valor2 > 0)) {
				byId('txtIdCliente').focus();
				byId('txtIdCliente').select();
			}
		}
	}
	
	function abrirDivFlotante2(nomObjeto, verTabla, valor, valor2, valor3) {
		byId('tblListaMotivo').style.display = 'none';
		byId('tblLista2').style.display = 'none';
		
		if (verTabla == "tblListaMotivo") {
			document.forms['frmBuscarMotivo'].reset();
			
			byId('hddObjDestinoMotivo').value = valor;
			byId('hddPagarCobrarMotivo').value = valor2;
			byId('hddIngresoEgresoMotivo').value = valor3;
			
			byId('btnBuscarMotivo').click();
			
			tituloDiv2 = 'Motivos';
		} else if (verTabla == "tblLista2") {
			byId('trBuscarCliente').style.display = 'none';
			byId('trBuscarProveedor').style.display = 'none';
			byId('trBuscarNotaCargo').style.display = 'none';
			if (valor == "Cliente") {
				document.forms['frmBuscarCliente'].reset();
				
				byId('trBuscarCliente').style.display = '';
				
				byId('txtCriterioBuscarCliente').className = 'inputHabilitado';
				
				byId('hddObjDestinoCliente').value = valor2;
				
				byId('btnBuscarCliente').click();
				
				tituloDiv2 = 'Clientes';
				byId(verTabla).style.width = "960px";
			} else if (valor == "Prov") {
				document.forms['frmBuscarProveedor'].reset();
				
				byId('hddObjDestinoProveedor').value = valor;
				
				byId('trBuscarProveedor').style.display = '';
				byId('txtCriterioBuscarProveedor').className = 'inputHabilitado';
				
				byId('btnBuscarProveedor').click();
				
				tituloDiv2 = 'Proveedores';
				byId(verTabla).style.width = "760px";
			} else if (valor == "NotaCargo") {
				document.forms['frmBuscarNotaCargo'].reset();
				
				byId('hddObjDestinoNotaCargo').value = valor;
				
				byId('trBuscarNotaCargo').style.display = '';
				byId('txtCriterioBuscarNotaCargo').className = 'inputHabilitado';
				
				byId('btnBuscarNotaCargo').click();
				
				byId('hddIdTradeInCxP').value = valor2;
				
				tituloDiv2 = 'Notas de Cargo de Cuentas por Pagar';
				byId(verTabla).style.width = "960px";
			}
		}
		
		byId(verTabla).style.display = '';
		if (nomObjeto != null) { openImg(nomObjeto); }
		byId('tdFlotanteTitulo2').innerHTML = tituloDiv2;
		
		if (verTabla == "tblListaMotivo") {
			byId('txtCriterioBuscarMotivo').focus();
			byId('txtCriterioBuscarMotivo').select();
		} else if (verTabla == "tblLista2") {
			if (valor == "Cliente") {
				byId('txtCriterioBuscarCliente').focus();
				byId('txtCriterioBuscarCliente').select();
			} else if (valor == "Prov") {
				byId('txtCriterioBuscarProveedor').focus();
				byId('txtCriterioBuscarProveedor').select();
			} else if (valor == "NotaCargo") {
				byId('txtCriterioBuscarNotaCargo').focus();
				byId('txtCriterioBuscarNotaCargo').select();
			}
		}
	}     
	
	function validarFrmAjusteInventario() {
		error = false;
		if (!(validarCampo('txtIdEmpresa', 't', '') == true
		&& validarCampo('txtIdCliente', 't', '') == true
		&& validarCampo('lstClaveMovimiento', 't', 'lista') == true
		&& validarCampo('lstAlmacenAjuste', 't', 'lista') == true
		&& validarCampo('txtAllowance', 't', 'monto') == true
		&& validarCampo('txtAcv', 't', 'monto') == true
		&& validarCampo('txtPayoff', 't', '') == true
		&& validarCampo('txtCreditoNeto', 't', '') == true)) {
			validarCampo('txtIdEmpresa', 't', '');
			validarCampo('txtIdCliente', 't', '');
			validarCampo('lstClaveMovimiento', 't', 'lista');
			validarCampo('lstAlmacenAjuste', 't', 'lista');
			validarCampo('txtAllowance', 't', 'monto');
			validarCampo('txtAcv', 't', 'monto');
			validarCampo('txtPayoff', 't', '');
			validarCampo('txtCreditoNeto', 't', '');
			
			error = true;
		}
		
		if (!(byId('hddIdTradeInAjusteInventario').value > 0)) {
			if (!(validarCampo('txtSubTotal', 't', 'monto') == true
			&& validarCampo('txtMontoAnticipo', 't', '') == true
				&& validarCampo('lstModulo', 't', 'lista') == true
				&& validarCampo('lstFormaPago', 't', 'lista') == true
				&& validarCampo('lstConceptoPago', 't', 'lista') == true)) {
				validarCampo('txtSubTotal', 't', 'monto');
				validarCampo('txtMontoAnticipo', 't', '');
					validarCampo('lstModulo', 't', 'lista');
					validarCampo('lstFormaPago', 't', 'lista');
					validarCampo('lstConceptoPago', 't', 'lista');
				
				error = true;
			}
			
			if (byId('hddMontoAnticipo').value >= 0) {
				if (!(validarCampo('txtObservacion', 't', '') == true)) {
					validarCampo('txtObservacion', 't', '');
					
					error = true;
				}
			}
		}
		
		if (!(byId('txtIdUnidadFisicaAjuste').value > 0)) {
			if (!(validarCampo('lstUnidadBasica', 't', 'lista') == true
			&& validarCampo('lstAno', 't', 'lista') == true
			&& validarCampo('lstCondicion', 't', 'lista') == true
			&& validarCampo('txtKilometrajeAjuste', 't', '') == true
			&& validarCampo('txtFechaFabricacionAjuste', 't', '') == true
			&& validarCampo('lstColorExterno1', 't', 'lista') == true
			&& validarCampo('lstColorInterno1', 't', 'lista') == true
			&& validarCampo('txtSerialCarroceriaAjuste', 't', '') == true
			&& validarCampo('txtSerialMotorAjuste', 't', '') == true
			&& validarCampo('txtNumeroVehiculoAjuste', 't', '') == true
			&& validarCampo('txtTituloVehiculoAjuste', 't', '') == true
			&& validarCampo('txtRegistroLegalizacionAjuste', 't', '') == true
			&& validarCampo('txtRegistroFederalAjuste', 't', '') == true
			&& validarCampo('lstEstadoVentaAjuste', 't', 'lista') == true
			&& validarCampo('lstMoneda', 't', 'lista') == true)) {
				validarCampo('lstUnidadBasica', 't', 'lista');
				validarCampo('lstAno', 't', 'lista');
				validarCampo('lstCondicion', 't', 'lista');
				validarCampo('txtKilometrajeAjuste', 't', '');
				validarCampo('txtFechaFabricacionAjuste', 't', '');
				validarCampo('lstColorExterno1', 't', 'lista');
				validarCampo('lstColorInterno1', 't', 'lista');
				validarCampo('txtSerialCarroceriaAjuste', 't', '');
				validarCampo('txtSerialMotorAjuste', 't', '');
				validarCampo('txtNumeroVehiculoAjuste', 't', '');
				validarCampo('txtTituloVehiculoAjuste', 't', '');
				validarCampo('txtRegistroLegalizacionAjuste', 't', '');
				validarCampo('txtRegistroFederalAjuste', 't', '');
				validarCampo('lstEstadoVentaAjuste', 't', 'lista');
				validarCampo('lstMoneda', 't', 'lista');
				
				error = true;
			}
			
			if (byId('lstTipoVale').value == 3) { // 3 = Nota de Crédito de CxC
				if (!(validarCampo('txtNroDcto', 't', '') == true)) {
					validarCampo('txtNroDcto', 't', '');
					
					error = true;
				}
			}
		}
		
		if (parseNumRafk(byId('hddMontoAntCxC').value) != 0) {
			if (!(validarCampo('txtIdMotivoAntCxC', 't', '') == true
			&& validarCampo('txtMontoAntCxC', 't', 'numPositivo') == true)) {
				validarCampo('txtIdMotivoAntCxC', 't', '');
				validarCampo('txtMontoAntCxC', 't', 'numPositivo');
					
				error = true;
			}
		}
		
		if (parseNumRafk(byId('hddMontoCxP').value) != 0) {
			if (!(validarCampo('txtIdProv', 't', '') == true
			&& validarCampo('txtIdMotivo', 't', '') == true
			&& validarCampo('txtMontoCxP', 't', 'numPositivo') == true)) {
				validarCampo('txtIdProv', 't', '');
				validarCampo('txtIdMotivo', 't', '');
				validarCampo('txtMontoCxP', 't', 'numPositivo');
					
				error = true;
			}
		}
		
		if (parseNumRafk(byId('hddMontoCxC').value) != 0) {
			if (!(validarCampo('txtIdMotivoCxC', 't', '') == true
			&& validarCampo('txtMontoCxC', 't', 'numPositivo') == true)) {
				validarCampo('txtIdMotivoCxC', 't', '');
				validarCampo('txtMontoCxC', 't', 'numPositivo');
					
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar el trade-in?') == true) {
				calcularMonto();
				xajax_guardarAjusteInventario(xajax.getFormValues('frmAjusteInventario'), xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmListaUnidadFisica'));
			}
		}
	}
	
	function validarFrmTradeInCxP() {
		error = false;
		if (!(validarCampo('lstProveedorTradeInCxP', 't', 'lista') == true)) {
			validarCampo('lstProveedorTradeInCxP', 't', 'lista');
			
			error = true;
		}
		
		if (byId('hddIdNotaCargo').value > 0) {
			if (!(validarCampo('txtNumeroNotaCargoAnt', 't', '') == true
			&& validarCampo('txtNumeroNotaCargo', 't', '') == true)) {
				validarCampo('txtNumeroNotaCargoAnt', 't', '');
				validarCampo('txtNumeroNotaCargo', 't', '');
				
				error = true;
			}
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar los cambios realizados?') == true) {
				xajax_guardarTradeInCxP(xajax.getFormValues('frmTradeInCxP'), xajax.getFormValues('frmListaUnidadFisica'));
			}
		}
	}
	
	function validarFrmUnidadFisica() {
		if (confirm('¿Seguro desea generar un nuevo anticipo a partir de este vehículo?')){
			this.disabled = true;
			validarFrmGenerarAnticipo();
		}
	}
	
	/**
	 * Se encarga de crear tradein y anticipo a partir de un trade in ya realizado
	 * @returns void
	 */
	function validarFrmGenerarAnticipo(){
		error = false;
		
		if (!(validarCampo('txtIdTradeIn', 't', '') == true
		&& validarCampo('hddIdEmpresa', 't', '') == true
		&& validarCampo('hddIdModulo', 't', '') == true
		&& validarCampo('hddIdFormaPago', 't', '') == true
		&& validarCampo('hddIdConceptoPago', 't', '') == true
		&& validarCampo('txtIdCliente_2', 't', '') == true
		&& validarCampo('txtIdUnidadFisica', 't', '') == true
		&& validarCampo('txtAllowance_2', 't', 'monto') == true
		&& validarCampo('txtAcv_2', 't', 'monto') == true
		&& validarCampo('txtPayoff_2', 't', '') == true
		&& validarCampo('txtCreditoNeto_2', 't', '') == true)) {
			validarCampo('txtIdTradeIn', 't', '');
			validarCampo('hddIdEmpresa', 't', '');
			validarCampo('hddIdModulo', 't', '');
			validarCampo('hddIdFormaPago', 't', '');
			validarCampo('hddIdConceptoPago', 't', '');
			validarCampo('txtIdCliente_2', 't', '');
			validarCampo('txtIdUnidadFisica', 't', '');
			validarCampo('txtAllowance_2', 't', 'monto');
			validarCampo('txtAcv_2', 't', 'monto');
			validarCampo('txtPayoff_2', 't', '');
			validarCampo('txtCreditoNeto_2', 't', '');
			
			error = true;
		}
		
		if(error){
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
		} else {
			calcularMonto();
			xajax_generarNuevoAnticipo(xajax.getFormValues('frmUnidadFisica'));
		}            
	}
	
	/**
	 * Se encarga de crear tradein y anticipo a partir de un vehiculo en unidad fisica
	 * @returns void
	 */
	function validarFrmGenerarTradein(){
		error = false;
		
		if (!(validarCampo('txtIdEmpresa', 't', '') == true
		&& validarCampo('txtIdCliente', 't', '') == true		
		&& validarCampo('txtIdUnidadFisicaAjuste', 't', '') == true		
		&& validarCampo('txtSubTotal', 't', 'monto') == true
		&& validarCampo('txtMontoAnticipo', 't', '') == true
		&& validarCampo('txtAllowance', 't', 'monto') == true
		&& validarCampo('txtAcv', 't', 'monto') == true
		&& validarCampo('txtPayoff', 't', '') == true
		&& validarCampo('txtCreditoNeto', 't', '') == true
		&& validarCampo('txtObservacion', 't', '') == true
			&& validarCampo('lstModulo', 't', 'lista') == true
			&& validarCampo('lstFormaPago', 't', 'lista') == true
			&& validarCampo('lstConceptoPago', 't', 'lista') == true)) {
			validarCampo('txtIdEmpresa', 't', '');
			validarCampo('txtIdCliente', 't', '');			
			validarCampo('txtIdUnidadFisicaAjuste', 't', '');			
			validarCampo('txtSubTotal', 't', 'monto');
			validarCampo('txtMontoAnticipo', 't', '');
			validarCampo('txtAllowance', 't', 'monto');
			validarCampo('txtAcv', 't', 'monto');
			validarCampo('txtPayoff', 't', '');
			validarCampo('txtCreditoNeto', 't', '');
			validarCampo('txtObservacion', 't', '');
				validarCampo('lstModulo', 't', 'lista');
				validarCampo('lstFormaPago', 't', 'lista');
				validarCampo('lstConceptoPago', 't', 'lista');
				
			error = true;
		}
		
		if (parseNumRafk(byId('txtPayoff').value) > 0) {
			if (!(validarCampo('txtIdProv', 't', '') == true
			&& validarCampo('txtIdMotivo', 't', '') == true)) {
				validarCampo('txtIdProv', 't', '');
				validarCampo('txtIdMotivo', 't', '');	
					
				error = true;
			}
		}
		
		if (parseNumRafk(byId('txtPayoff').value) > parseNumRafk(byId('txtAllowance').value)) {
			if (!(validarCampo('txtIdMotivoCxC', 't', '') == true)) {
				validarCampo('txtIdMotivoCxC', 't', '');	
					
				error = true;
			}
		}
				
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar el trade-in?') == true) {
				calcularMonto();
				xajax_generarNuevoAnticipo(xajax.getFormValues('frmAjusteInventario'), 1);				
			}
		}
	}
	
	function calcularMonto(objAccion){
		if (!(byId('hddIdTradeInAjusteInventario').value > 0) && objAccion == 'txtAllowance') {
			byId('txtAcv').value = byId('txtAllowance').value; 
		}
		
		byId('trtxtAllowanceAnt').style.display = 'none';
		byId('trtxtAcvAnt').style.display = 'none';
		byId('trtxtPayoffAnt').style.display = 'none';
		byId('trtxtCreditoNetoAnt').style.display = 'none';
		
		byId('trDatosAnticipo').style.display = 'none';
		byId('trConceptoPago').style.display = 'none';
		byId('trMotivoAntCxC').style.display = 'none';
		
		setFormatoRafk(byId('txtAllowanceAnt'),2);
		setFormatoRafk(byId('txtAllowance'),2);
		setFormatoRafk(byId('txtAcvAnt'),2);
		setFormatoRafk(byId('txtAcv'),2);
		setFormatoRafk(byId('txtPayoffAnt'),2);
		setFormatoRafk(byId('txtPayoff'),2);
		
		txtAllowance = parseNumRafk(byId('txtAllowance').value);
		txtAllowanceAnt = parseNumRafk(byId('txtAllowanceAnt').value);
		txtAcv = parseNumRafk(byId('txtAcv').value);
		txtAcvAnt = parseNumRafk(byId('txtAcvAnt').value);
		txtPayoff = parseNumRafk(byId('txtPayoff').value);
		txtPayoffAnt = parseNumRafk(byId('txtPayoffAnt').value);
		
		txtCreditoNeto = txtAllowance - txtPayoff;
		txtCreditoNetoAnt = txtAllowanceAnt - txtPayoffAnt;
		
		
		// SI EL MONTO DEL DCTO VARIA DE POSITIVO A NEGATIVO Y VICEVERSA, INICIALIZA EL CAMPO DEL MOTIVO
		if ((parseNumRafk(byId('hddMontoCxP').value) < 0 && (txtPayoff - txtPayoffAnt) > 0)
		|| (parseNumRafk(byId('hddMontoCxP').value) > 0 && (txtPayoff - txtPayoffAnt) < 0)) {
			byId('txtIdMotivo').value = "";
			byId('txtMotivo').value = "";
		}
		
		byId('txtMontoCxP').value = (((txtPayoff - txtPayoffAnt) < 0) ? (-1) : 1) * (txtPayoff - txtPayoffAnt);
		byId('hddMontoCxP').value = txtPayoff - txtPayoffAnt;
		
		// SI EL MONTO DEL DCTO VARIA DE POSITIVO A NEGATIVO Y VICEVERSA, INICIALIZA EL CAMPO DEL MOTIVO
		if ((parseNumRafk(byId('hddMontoCxC').value) < 0 && ((-1) * (txtCreditoNeto - txtCreditoNetoAnt)) > 0)
		|| (parseNumRafk(byId('hddMontoCxC').value) > 0 && ((-1) * (txtCreditoNeto - txtCreditoNetoAnt)) < 0)) {
			byId('txtIdMotivoCxC').value = "";
			byId('txtMotivoCxC').value = "";
		}
		
		
		if (byId('hddIdTradeInAjusteInventario').value > 0 && (txtAllowance != txtAllowanceAnt || txtAcv != txtAcvAnt || txtPayoff != txtPayoffAnt)) {
			hddMontoAnticipo = -1;
			
			if (txtAllowance > txtAcv) {												//alert("EL ALLOWANCE ES MAYOR AL ACV");
				if (txtAllowance != txtAllowanceAnt && txtAcv == txtAcvAnt) {			//alert("a // VARIO EL ALLOWANCE");
					if (txtAllowance > txtAllowanceAnt) {								//alert("a1 // EL ALLOWANCE AUMENTO");
						hddMontoAntCxC = 0;
					} else {															//alert("a2 // EL ALLOWANCE DISMINUYO");
						if (txtAcv == txtAcvAnt) {										//alert("a2.1 // NO VARIO EL ACV");
							hddMontoAntCxC = 0;
						} else {
							hddMontoAntCxC = txtAcv - txtAllowance;
						}
					}
				} else if (txtAcv != txtAcvAnt && txtAllowance == txtAllowanceAnt) {	//alert("b // VARIO EL ACV");
					if (txtAcv > txtAcvAnt) {											//alert("b1 // EL ACV AUMENTO");
						if (txtAllowance == txtAllowanceAnt) {							//alert("b1.1 // NO VARIO EL ALLOWANCE");
							hddMontoAntCxC = txtAcv - txtAcvAnt;
						} else {
							hddMontoAntCxC = txtAcv - txtAllowance;
						}
					} else {															//alert("b2 // EL ACV DISMINUYO");
						if (txtAllowance == txtAllowanceAnt) {							//alert("b2.1 // NO VARIO EL ALLOWANCE");
							hddMontoAntCxC = txtAcv - txtAcvAnt;
						} else {
							hddMontoAntCxC = txtAllowance - txtAcv;
						}
					}
				} else {																//alert("c");
					hddMontoAntCxC = txtAcvAnt - txtAcv;
				}
				
			} else if (txtAcv > txtAllowance) {											//alert("EL ACV ES MAYOR AL ALLOWANCE");
				if (txtAllowance != txtAllowanceAnt && txtAcv == txtAcvAnt) {			//alert("a // VARIO EL ALLOWANCE");
					if (txtAllowance < txtAllowanceAnt) {								//alert("a1 // EL ALLOWANCE DISMINUYO");
						hddMontoAntCxC = 0;
					} else {															//alert("a2 // EL ALLOWANCE AUMENTO");
						hddMontoAntCxC = txtAcv - txtAllowance;
					}
				} else if (txtAcv != txtAcvAnt && txtAllowance == txtAllowanceAnt) {	//alert("b // VARIO EL ACV");
					if (txtAcv > txtAcvAnt) {											//alert("b1 // EL ACV AUMENTO");
						hddMontoAntCxC = txtAllowance - txtAcv;
					} else {															//alert("b2 // EL ACV DISMINUYO");
						hddMontoAntCxC = txtAcv - txtAllowance;
					}
				} else {																//alert("c");
					hddMontoAntCxC = txtAcvAnt - txtAcv;
				}
				
			} else if (txtAllowance > txtAllowanceAnt) {								//alert("ALLOWANCE CAMBIO MAYOR");
				if (txtAcv != txtAcvAnt) {												//alert("a // VARIO EL ACV");
					if (txtAcv > txtAcvAnt) {											//alert("a1 // EL ACV AUMENTO");
						if (txtAllowance != txtAcv) {									//alert("a1.a // EL ALLOWANCE Y ACV SON DISTINTOS");
							hddMontoAntCxC = txtAcvAnt - txtAcv;
						} else {														//alert("a1.b // EL ALLOWANCE Y ACV SON IGUALES");
							hddMontoAntCxC = 0;
						}
					} else {															//alert("a2 // EL ACV DISMINUYO");
						hddMontoAntCxC = txtAcv - txtAcvAnt;
					}
				} else {																//alert("b // VARIO EL ALLOWANCE");
					hddMontoAntCxC = txtAllowanceAnt - txtAllowance;
				}
				
			} else if (txtAllowance < txtAllowanceAnt) {								//alert("ALLOWANCE CAMBIO MENOR");
				if (txtAcv != txtAcvAnt) {												//alert("a // VARIO EL ACV");
					if (txtAcv > txtAcvAnt) {											//alert("a1 // EL ACV AUMENTO");
						hddMontoAntCxC = txtAcv - txtAcvAnt;
					} else {															//alert("a2 // EL ACV DISMINUYO");
						if (txtAllowance != txtAcv) {									//alert("a2.a // EL ALLOWANCE Y ACV SON DISTINTOS");
							hddMontoAntCxC = txtAcvAnt - txtAcv;
						} else {														//alert("a2.b // EL ALLOWANCE Y ACV SON IGUALES");
							hddMontoAntCxC = 0;
						}
					}
				} else {																//alert("b // VARIO EL ALLOWANCE");
					hddMontoAntCxC = txtAllowanceAnt - txtAllowance;
				}
				
			} else if (txtAcv > txtAcvAnt) {											//alert("ACV CAMBIO MAYOR");
				if (txtAllowance != txtAllowanceAnt) {									//alert("a // VARIO EL ALLOWANCE");
					hddMontoAntCxC = txtAllowance - txtAllowanceAnt;
				} else {																//alert("b // VARIO EL ACV");
					hddMontoAntCxC = txtAcvAnt - txtAcv;
				}
				
			} else if (txtAcv < txtAcvAnt) {											//alert("ACV CAMBIO MENOR");
				if (txtAllowance != txtAllowanceAnt) {									//alert("a // VARIO EL ALLOWANCE");
					hddMontoAntCxC = txtAllowanceAnt - txtAllowance;
				} else {																//alert("b // VARIO EL ACV");
					hddMontoAntCxC = txtAcvAnt - txtAcv;
				}
				
			} else { // VARIO EL PAYOFF
				hddMontoAntCxC = txtAcv - txtAllowance;
			}
			
			
			txtMontoPago = 0; // TRADE-IN
			txtMontoAntCxC = 0;
			if (hddMontoAntCxC != 0) { // El Allowance y el Acv son distintos
				byId('trDatosAnticipo').style.display = '';////////
				
				if (hddMontoAntCxC < 0) { // Va en contra del negocio
					byId('trMotivoAntCxC').style.display = '';
					byId('spnMotivoAntCxC').innerHTML = "Cuenta por Cobrar (Nota de Crédito)";
					byId('aListarMotivoAntCxC').onclick = function () {
						abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoAntCxC', 'CC', 'E');
					}
					byId('txtIdMotivoAntCxC').onblur = function () {
						xajax_asignarMotivo(this.value, 'MotivoAntCxC', 'CC', 'E', 'false');
					}
					
					txtMontoAntCxC = (-1) * hddMontoAntCxC;
					txtMontoAnticipo = txtMontoPago + txtMontoAntCxC;
					//hddMontoAnticipo = txtMontoAnticipo;
				} else if (hddMontoAntCxC > 0) { // Va a favor del negocio
					byId('trMotivoAntCxC').style.display = '';
					byId('spnMotivoAntCxC').innerHTML = "Cuenta por Cobrar (Nota de Débito)";
					byId('aListarMotivoAntCxC').onclick = function () {
						abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoAntCxC', 'CC', 'I');
					}
					byId('txtIdMotivoAntCxC').onblur = function () {
						xajax_asignarMotivo(this.value, 'MotivoAntCxC', 'CC', 'I', 'false');
					}
					
					txtMontoAntCxC = hddMontoAntCxC;
					txtMontoAnticipo = txtMontoPago - txtMontoAntCxC;
					//hddMontoAnticipo = txtMontoAnticipo;
				}
				
				if (txtCreditoNeto < 0) {////////
					txtMontoAnticipo = 0;
				} else if (txtCreditoNeto > 0) {////////
					txtMontoAnticipo = 0;////////
				} else if (txtCreditoNeto == 0) {////////
					txtMontoAnticipo = 0;
				}
			} else { // El Allowance y el Acv son iguales
				if (txtCreditoNetoAnt < 0) {////////
					txtMontoAnticipo = 0;
				} else if (txtCreditoNetoAnt > 0) {////////
					txtMontoAnticipo = 0;////////
				} else {
					txtMontoAnticipo = 0;
				}
			}
			
			byId('txtCreditoNeto').value = txtCreditoNeto;
			byId('txtSubTotal').value = txtAcv;
			
			byId('txtMontoPago').value = txtMontoPago;
			byId('txtMontoAntCxC').value = txtMontoAntCxC;
			byId('hddMontoAntCxC').value = hddMontoAntCxC;
			byId('txtMontoAnticipo').value = txtMontoAnticipo;
			byId('hddMontoAnticipo').value = hddMontoAnticipo;
			
			if (txtCreditoNeto != txtCreditoNetoAnt) {
				if (txtAllowance > txtAllowanceAnt
				&& txtAcv == txtAcvAnt) {
					txtMontoCxC = (txtCreditoNeto - txtCreditoNetoAnt);////////
					
				} else if (txtAllowance < txtAllowanceAnt
				&& txtAcv == txtAcvAnt) {
					txtMontoCxC = (txtCreditoNeto - txtCreditoNetoAnt);////////
					
				} else if (txtPayoff != txtPayoffAnt
				&& txtAllowance == txtAllowanceAnt
				&& txtAcv == txtAcvAnt) {
					txtMontoCxC = ((txtCreditoNeto) - (txtCreditoNetoAnt));////////
					
				} else if (txtAcv == txtAcvAnt) {
					txtMontoCxC = 0;////////
					
				} else {
					txtMontoCxC = (txtCreditoNeto - txtCreditoNetoAnt);////////
				}
			} else {
				txtMontoCxC = txtCreditoNeto;////////
			}
			
			byId('txtMontoCxC').value = ((((-1) * txtMontoCxC) < 0) ? (-1) : 1) * ((-1) * txtMontoCxC);
			byId('hddMontoCxC').value = ((-1) * txtMontoCxC);
		} else if (!(byId('hddIdTradeInAjusteInventario').value > 0)) {
			byId('trDatosAnticipo').style.display = '';
			byId('trConceptoPago').style.display = '';
			
			hddMontoAntCxC = txtAcv - txtAllowance;
			
			txtMontoPago = 0;
			txtMontoAntCxC = 0;
			if (hddMontoAntCxC != 0) { // El Allowance y el Acv son distintos
				txtMontoPago = txtAcv;
				
				if (hddMontoAntCxC < 0) { // Va en contra del negocio
					byId('trMotivoAntCxC').style.display = '';
					byId('spnMotivoAntCxC').innerHTML = "Cuenta por Cobrar (Nota de Crédito)";
					byId('aListarMotivoAntCxC').onclick = function () {
						abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoAntCxC', 'CC', 'E');
					}
					byId('txtIdMotivoAntCxC').onblur = function () {
						xajax_asignarMotivo(this.value, 'MotivoAntCxC', 'CC', 'E', 'false');
					}
					
					txtMontoAntCxC = (-1) * hddMontoAntCxC;
					txtMontoAnticipo = txtMontoPago + txtMontoAntCxC;
					hddMontoAnticipo = txtMontoAnticipo;
				} else if (hddMontoAntCxC > 0) { // Va a favor del negocio
					byId('trMotivoAntCxC').style.display = '';
					byId('spnMotivoAntCxC').innerHTML = "Cuenta por Cobrar (Nota de Débito)";
					byId('aListarMotivoAntCxC').onclick = function () {
						abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoAntCxC', 'CC', 'I');
					}
					byId('txtIdMotivoAntCxC').onblur = function () {
						xajax_asignarMotivo(this.value, 'MotivoAntCxC', 'CC', 'I', 'false');
					}
					
					txtMontoAntCxC = hddMontoAntCxC;
					txtMontoAnticipo = txtMontoPago - txtMontoAntCxC;
					hddMontoAnticipo = txtMontoAnticipo;
				}
			
				if (txtCreditoNeto < 0) {
					txtMontoPago = 0;
					txtMontoAnticipo = 0;
					hddMontoAnticipo = txtMontoAnticipo;
				} else if (txtCreditoNeto > 0) {
					if (txtPayoff > 0) {
						if (txtAllowance != txtAcv) {
							txtMontoPago = (txtAllowance - txtPayoff) - (txtAllowance - txtAcv);
						} else {
							txtMontoPago = txtAllowance - txtPayoff;
						}
					}
					txtMontoAnticipo = txtCreditoNeto;
					hddMontoAnticipo = txtMontoAnticipo;
				} else if (txtCreditoNeto == 0) {
					txtMontoPago = 0;
					txtMontoAnticipo = 0;
					hddMontoAnticipo = 0;
				}
			} else { // El Allowance y el Acv son iguales
				if (txtCreditoNeto < 0) {
					txtMontoPago = 0;
					txtMontoAnticipo = 0;
					hddMontoAnticipo = txtMontoAnticipo;
				} else if (txtCreditoNeto > 0) {
					txtMontoPago = txtCreditoNeto;
					txtMontoAnticipo = txtCreditoNeto;
					hddMontoAnticipo = txtMontoAnticipo;
				} else {
					txtMontoPago = 0;
					txtMontoAnticipo = 0;
					hddMontoAnticipo = 0;
				}
			}
			
			byId('txtCreditoNeto').value = txtCreditoNeto;
			byId('txtSubTotal').value = txtAcv;
			
			byId('txtMontoPago').value = txtMontoPago;
			byId('txtMontoAntCxC').value = txtMontoAntCxC;
			byId('hddMontoAntCxC').value = hddMontoAntCxC;
			byId('txtMontoAnticipo').value = txtMontoAnticipo;
			byId('hddMontoAnticipo').value = hddMontoAnticipo;
			
			txtMontoCxC = txtCreditoNeto - txtCreditoNetoAnt;////////
			
			byId('txtMontoCxC').value = ((((-1) * txtMontoCxC) < 0) ? (-1) : 1) * ((-1) * txtMontoCxC);
			byId('hddMontoCxC').value = ((-1) * txtMontoCxC);
		}
		
		setFormatoRafk(byId('txtAllowance'),2);
		setFormatoRafk(byId('txtAcv'),2);
		setFormatoRafk(byId('txtPayoff'),2);
		setFormatoRafk(byId('txtCreditoNeto'),2);
		setFormatoRafk(byId('txtSubTotal'),2);
		
		setFormatoRafk(byId('txtMontoPago'),2);
		setFormatoRafk(byId('txtMontoAntCxC'),2);
		setFormatoRafk(byId('txtMontoAnticipo'),2);
		
		if (txtPayoff - txtPayoffAnt != 0) {
			byId('legendCuentaPagar').innerHTML = (txtPayoff - txtPayoffAnt > 0) ? "Cuenta por Pagar (Nota de Débito)" : "Cuenta por Pagar (Nota de Crédito)";
			byId('trCxP').style.display = '';
			byId('aListarMotivo').onclick = function () {
				if (txtPayoff - txtPayoffAnt > 0) {
					abrirDivFlotante2(this, 'tblListaMotivo', 'Motivo', 'CP', 'E');
				} else {
					abrirDivFlotante2(this, 'tblListaMotivo', 'Motivo', 'CP', 'I');
				}
			}
			byId('txtIdMotivo').onblur = function () {
				if (txtPayoff - txtPayoffAnt > 0) {
					xajax_asignarMotivo(this.value, 'Motivo', 'CP', 'E', 'false');
				} else {
					xajax_asignarMotivo(this.value, 'Motivo', 'CP', 'I', 'false');
				}
			}
		} else {
			byId('trCxP').style.display = 'none';
			byId('txtIdProv').value = "";
			byId('txtIdProv').onblur();
			byId('txtIdMotivo').value = "";
			byId('txtIdMotivo').onblur();
			byId('txtMontoCxP').value = 0;
			byId('hddMontoCxP').value = 0;
		}
		
		if (txtCreditoNeto - txtCreditoNetoAnt != 0) {
			byId('legendCuentaCobrar').innerHTML = (txtCreditoNeto - txtCreditoNetoAnt > 0) ? "Cuenta por Cobrar (Nota de Crédito)" : "Cuenta por Cobrar (Nota de Débito)";
			byId('trCxC').style.display = '';
			byId('aListarMotivoCxC').onclick = function () {
				if (txtCreditoNeto - txtCreditoNetoAnt > 0) {
					abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoCxC', 'CC', 'E');
				} else {
					abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoCxC', 'CC', 'I');
				}
			}
			byId('txtIdMotivoCxC').onblur = function () {
				if (txtCreditoNeto - txtCreditoNetoAnt > 0) {
					xajax_asignarMotivo(this.value, 'MotivoCxC', 'CC', 'E', 'false');
				} else {
					xajax_asignarMotivo(this.value, 'MotivoCxC', 'CC', 'I', 'false');
				}
			}
			
			// SI EL ANTICIPO SE CREA CON MONTO, SE OCULTA LA OPCION DE NOTA DE CREDITO
			if (hddMontoAnticipo > 0
			|| (hddMontoAntCxC != 0 && hddMontoAnticipo == -1 && txtCreditoNeto >= 0)
			|| (hddMontoAnticipo == -1 && hddMontoAntCxC == txtMontoCxC)
			|| txtMontoCxC == 0) {
				byId('trCxC').style.display = 'none';
				byId('txtIdMotivoCxC').value = "";
				byId('txtIdMotivoCxC').onblur();
				byId('txtMontoCxC').value = 0;
				byId('hddMontoCxC').value = 0;
			}
		} else {
			byId('trCxC').style.display = 'none';
			byId('txtIdMotivoCxC').value = "";
			byId('txtIdMotivoCxC').onblur();
			byId('txtMontoCxC').value = 0;
			byId('hddMontoCxC').value = 0;
		}
		
		setFormatoRafk(byId('txtMontoCxP'),2);
		setFormatoRafk(byId('txtMontoCxC'),2);
		
		
		if (txtAllowance != txtAllowanceAnt && byId('hddIdTradeInAjusteInventario').value > 0) {
			byId('trtxtAllowanceAnt').style.display = '';
		}
		
		if (txtAcv != txtAcvAnt && byId('hddIdTradeInAjusteInventario').value > 0) {
			byId('trtxtAcvAnt').style.display = '';
		}
		
		if (txtPayoff != txtPayoffAnt && byId('hddIdTradeInAjusteInventario').value > 0) {
			byId('trtxtPayoffAnt').style.display = '';
		}
		
		if (txtCreditoNeto != txtCreditoNetoAnt && byId('hddIdTradeInAjusteInventario').value > 0) {
			byId('trtxtCreditoNetoAnt').style.display = '';
		}
		
		xajax_calcularTradeIn(xajax.getFormValues('frmAjusteInventario'));
	}
	
	function calcularMonto2(objAccion){ // CALCULAR MONTO FUNCION VIEJA
		if (!(byId('hddIdTradeInAjusteInventario').value > 0) && objAccion == 'txtAllowance') {
			byId('txtAcv').value = byId('txtAllowance').value; 
		}
		
		setFormatoRafk(byId('txtAllowanceAnt'),2);
		setFormatoRafk(byId('txtAllowance'),2);
		setFormatoRafk(byId('txtAcvAnt'),2);
		setFormatoRafk(byId('txtAcv'),2);
		setFormatoRafk(byId('txtPayoffAnt'),2);
		setFormatoRafk(byId('txtPayoff'),2);
		
		txtAllowance = parseNumRafk(byId('txtAllowance').value);
		txtAllowanceAnt = parseNumRafk(byId('txtAllowanceAnt').value);
		txtAcv = parseNumRafk(byId('txtAcv').value);
		txtAcvAnt = parseNumRafk(byId('txtAcvAnt').value);
		txtPayoff = parseNumRafk(byId('txtPayoff').value);
		txtPayoffAnt = parseNumRafk(byId('txtPayoffAnt').value);
		
		txtCreditoNeto = txtAllowance - txtPayoff;
		txtCreditoNetoAnt = txtAllowanceAnt - txtPayoffAnt;
		
		if (isNaN(txtCreditoNeto)) {
			txtCreditoNeto = txtAllowance;
			byId('txtPayoff').value = 0;
		}
		
		// SI EL MONTO DEL DCTO VARIA DE POSITIVO A NEGATIVO Y VICEVERSA, INICIALIZA EL CAMPO DEL MOTIVO
		if ((parseNumRafk(byId('hddMontoCxP').value) < 0 && (txtPayoff - txtPayoffAnt) > 0)
		|| (parseNumRafk(byId('hddMontoCxP').value) > 0 && (txtPayoff - txtPayoffAnt) < 0)) {
			byId('txtIdMotivo').value = "";
			byId('txtMotivo').value = "";
		}
		
		byId('txtMontoCxP').value = (((txtPayoff - txtPayoffAnt) < 0) ? (-1) : 1) * (txtPayoff - txtPayoffAnt);
		byId('hddMontoCxP').value = txtPayoff - txtPayoffAnt;
		
		if (txtPayoff > 0 && txtPayoff != txtPayoffAnt) {
		} else {
			byId('txtIdProv').value = '';
			byId('txtNombreProv').value = '';
			byId('txtIdMotivo').value = '';
			byId('txtMotivo').value = '';
		}
		
		// SI EL MONTO DEL DCTO VARIA DE POSITIVO A NEGATIVO Y VICEVERSA, INICIALIZA EL CAMPO DEL MOTIVO
		if ((parseNumRafk(byId('hddMontoCxC').value) < 0 && ((-1) * (txtCreditoNeto - txtCreditoNetoAnt)) > 0)
		|| (parseNumRafk(byId('hddMontoCxC').value) > 0 && ((-1) * (txtCreditoNeto - txtCreditoNetoAnt)) < 0)) {
			byId('txtIdMotivoCxC').value = "";
			byId('txtMotivoCxC').value = "";
		}
		
		byId('txtMontoCxC').value = ((((-1) * (txtCreditoNeto - txtCreditoNetoAnt)) < 0) ? (-1) : 1) * ((-1) * (txtCreditoNeto - txtCreditoNetoAnt));
		byId('hddMontoCxC').value = (-1) * (txtCreditoNeto - txtCreditoNetoAnt);
		
		byId('trMotivoAntCxC').style.display = 'none';
		if (txtCreditoNeto < 0 && (txtCreditoNeto != txtCreditoNetoAnt && txtAllowanceAnt == 0)) {
			byId('trDatosAnticipo').style.display = '';
			byId('trConceptoPago').style.display = '';
			byId('txtMontoPago').value = 0;
			byId('txtMontoAnticipo').value = 0;
			byId('hddMontoAnticipo').value = 0;
		} else if (txtCreditoNeto > 0 && (txtCreditoNeto != txtCreditoNetoAnt && txtAllowanceAnt == 0)) {
			byId('trDatosAnticipo').style.display = '';
			byId('trConceptoPago').style.display = '';
			byId('txtIdMotivoCxC').value = '';
			byId('txtMotivoCxC').value = '';
			if (txtAcv > 0 && txtPayoff > 0) {
				byId('txtMontoPago').value = txtCreditoNeto;
			} else {
				byId('txtMontoPago').value = (txtAcv > 0) ? txtAllowance : 0;
			}
			byId('txtMontoAnticipo').value = txtCreditoNeto;
			byId('hddMontoAnticipo').value = txtCreditoNeto;
			byId('txtMontoCxC').value = 0;
			byId('hddMontoCxC').value = 0;
		} else if ((txtAllowance != txtAllowanceAnt || txtAcv != txtAcvAnt) && txtAcv != txtAllowance) {
			byId('trDatosAnticipo').style.display = '';
			byId('trConceptoPago').style.display = 'none';
			byId('txtMontoPago').value = 0;
			byId('txtMontoAnticipo').value = 0;
			byId('hddMontoAnticipo').value = -1;
		} else if (!(byId('hddIdTradeInAjusteInventario').value > 0)) {
			byId('trDatosAnticipo').style.display = '';
			byId('trConceptoPago').style.display = 'none';
			byId('txtMontoPago').value = 0;
			byId('txtMontoAntCxC').value = 0;
			byId('txtMontoAnticipo').value = 0;
			byId('hddMontoAnticipo').value = -1;
		} else {
			byId('trDatosAnticipo').style.display = 'none';
			byId('trConceptoPago').style.display = 'none';
			byId('txtMontoPago').value = -1;
			byId('txtMontoAntCxC').value = -1;
			byId('txtMontoAnticipo').value = -1;
			byId('hddMontoAnticipo').value = -1;
		}
		
		byId('txtCreditoNeto').value = txtCreditoNeto;
		byId('txtSubTotal').value = txtAcv;
		
		// CALCULA SI EXISTE UN MONTO QUE VAYA EN CONTRA DE LA UTILIDAD
		montoOtro = (txtAcvAnt == 0) ? txtAcv - txtAllowance : (txtAcv - txtAcvAnt) + (txtAllowanceAnt - txtAllowance);
		byId('txtMontoAntCxC').value = ((montoOtro < 0) ? (-1) : 1) * montoOtro;
		byId('hddMontoAntCxC').value = montoOtro;
		if (montoOtro < 0) {
			byId('trMotivoAntCxC').style.display = '';
			byId('spnMotivoAntCxC').innerHTML = "Cuenta por Cobrar (Nota de Crédito)";
			byId('aListarMotivoAntCxC').onclick = function () {
				abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoAntCxC', 'CC', 'E');
			}
			byId('txtIdMotivoAntCxC').onblur = function () {
				xajax_asignarMotivo(this.value, 'MotivoAntCxC', 'CC', 'E', 'false');
			}
			
			if (txtAllowanceAnt > 0 && txtAllowance != txtAcv) {
				byId('txtMontoPago').value = 0;
			} else if (txtCreditoNeto > 0 && txtPayoff == 0) {
				byId('txtMontoPago').value = txtAcv;
			} else if (txtCreditoNeto > 0 && txtPayoff > 0) {
				byId('txtMontoPago').value = txtAcv - txtPayoff;
			} else {
				byId('txtMontoPago').value = 0;
			}
		} else if (montoOtro > 0) {
			byId('trMotivoAntCxC').style.display = '';
			byId('spnMotivoAntCxC').innerHTML = "Cuenta por Cobrar (Nota de Débito)";
			byId('aListarMotivoAntCxC').onclick = function () {
				abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoAntCxC', 'CC', 'I');
			}
			byId('txtIdMotivoAntCxC').onblur = function () {
				xajax_asignarMotivo(this.value, 'MotivoAntCxC', 'CC', 'I', 'false');
			}
			
			if (txtAllowanceAnt > 0 && txtAllowance != txtAcv) {
				byId('txtMontoPago').value = 0;
			} else if (txtCreditoNeto > 0 && txtPayoff == 0) {
				byId('txtMontoPago').value = txtAcv;
			} else if (txtCreditoNeto > 0 && txtPayoff > 0) {
				byId('txtMontoPago').value = txtAcv - txtPayoff;
			} else {
				byId('txtMontoPago').value = 0;
			}
		} else if (montoOtro == 0) {
			byId('txtIdMotivoAntCxC').value = '';
			byId('txtIdMotivoAntCxC').onblur();
		} else {
			byId('txtIdMotivoAntCxC').value = '';
			byId('txtIdMotivoAntCxC').onblur();
			byId('txtMontoAntCxC').value = 0;
			byId('hddMontoAnticipo').value = -1;
		}
		
		if (txtPayoff - txtPayoffAnt != 0) {
			byId('legendCuentaPagar').innerHTML = (txtPayoff - txtPayoffAnt > 0) ? "Cuenta por Pagar (Nota de Débito)" : "Cuenta por Pagar (Nota de Crédito)";
			byId('trCxP').style.display = '';
			byId('aListarMotivo').onclick = function () {
				if (txtPayoff - txtPayoffAnt > 0) {
					abrirDivFlotante2(this, 'tblListaMotivo', 'Motivo', 'CP', 'E');
				} else {
					abrirDivFlotante2(this, 'tblListaMotivo', 'Motivo', 'CP', 'I');
				}
			}
			byId('txtIdMotivo').onblur = function () {
				if (txtPayoff - txtPayoffAnt > 0) {
					xajax_asignarMotivo(this.value, 'Motivo', 'CP', 'E', 'false');
				} else {
					xajax_asignarMotivo(this.value, 'Motivo', 'CP', 'I', 'false');
				}
			}
		} else {
			byId('trCxP').style.display = 'none';
			byId('txtIdProv').value = "";
			byId('txtIdProv').onblur();
			byId('txtIdMotivo').value = "";
			byId('txtIdMotivo').onblur();
		}
		
		if (txtCreditoNeto - txtCreditoNetoAnt != 0 && montoOtro == 0) {
			byId('legendCuentaCobrar').innerHTML = (txtCreditoNeto - txtCreditoNetoAnt > 0) ? "Cuenta por Cobrar (Nota de Crédito)" : "Cuenta por Cobrar (Nota de Débito)";
			byId('trCxC').style.display = '';
			byId('aListarMotivoCxC').onclick = function () {
				if (txtCreditoNeto - txtCreditoNetoAnt > 0) {
					abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoCxC', 'CC', 'E');
				} else {
					abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoCxC', 'CC', 'I');
				}
			}
			byId('txtIdMotivoCxC').onblur = function () {
				if (txtCreditoNeto - txtCreditoNetoAnt > 0) {
					xajax_asignarMotivo(this.value, 'MotivoCxC', 'CC', 'E', 'false');
				} else {
					xajax_asignarMotivo(this.value, 'MotivoCxC', 'CC', 'I', 'false');
				}
			}
			
			// SI EL ANTICIPO SE CREA CON MONTO, SE OCULTA LA OPCION DE NOTA DE CREDITO
			if (txtCreditoNeto > 0 && txtCreditoNeto != txtCreditoNetoAnt && txtAllowanceAnt == 0) {
				byId('trCxC').style.display = 'none';
			}
		} else {
			byId('trCxC').style.display = 'none';
			byId('txtIdMotivoCxC').value = "";
			byId('txtIdMotivoCxC').onblur();
		}
		
		byId('trtxtAllowanceAnt').style.display = 'none';
		if (txtAllowance != txtAllowanceAnt && byId('hddIdTradeInAjusteInventario').value > 0) {
			byId('trtxtAllowanceAnt').style.display = '';
		}
		
		byId('trtxtAcvAnt').style.display = 'none';
		if (txtAcv != txtAcvAnt && byId('hddIdTradeInAjusteInventario').value > 0) {
			byId('trtxtAcvAnt').style.display = '';
		}
		
		byId('trtxtPayoffAnt').style.display = 'none';
		if (txtPayoff != txtPayoffAnt && byId('hddIdTradeInAjusteInventario').value > 0) {
			byId('trtxtPayoffAnt').style.display = '';
		}
		
		byId('trtxtCreditoNetoAnt').style.display = 'none';
		if (txtCreditoNeto != txtCreditoNetoAnt && byId('hddIdTradeInAjusteInventario').value > 0) {
			byId('trtxtCreditoNetoAnt').style.display = '';
		}
		
		
		setFormatoRafk(byId('txtAllowance'),2);
		setFormatoRafk(byId('txtAcv'),2);
		setFormatoRafk(byId('txtPayoff'),2);
		setFormatoRafk(byId('txtCreditoNeto'),2);
		
		setFormatoRafk(byId('txtMontoPago'),2);
		setFormatoRafk(byId('txtMontoAntCxC'),2);
		setFormatoRafk(byId('hddMontoAntCxC'),2);
		setFormatoRafk(byId('txtMontoAnticipo'),2);
		setFormatoRafk(byId('txtSubTotal'),2);
		
		setFormatoRafk(byId('txtMontoCxP'),2);
		setFormatoRafk(byId('hddMontoCxP'),2);
		setFormatoRafk(byId('txtMontoCxC'),2);
		setFormatoRafk(byId('hddMontoCxC'),2);
		
		xajax_calcularTradeIn(xajax.getFormValues('frmAjusteInventario'));
	}
	</script>
</head>

<body class="bodyVehiculos">
	<script type="text/javascript" language="javascript" src="../js/wz_tooltip/wz_tooltip.js"></script>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_vehiculos.php"); ?></div>
    
    <div id="divInfo" class="print">
    	<table border="0" width="100%">
        <tr>
        	<td class="tituloPaginaVehiculos">Trade-in</td>
        </tr>
        <tr>
        	<td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            	<table align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblAjusteInventario', 0); xajax_validarAperturaCaja();">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/ico_new.png" title="Nuevo Registro Trade-In"/></td><td>&nbsp;</td><td>Nuevo</td></tr></table></button>
                        </a>
                    </td>
                    <td>&nbsp;
                        <a class="modalImg" id="aNuevo" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblAjusteInventario', 0, 1); xajax_validarAperturaCaja();">
                            <button type="button"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img class="puntero" src="../img/iconos/generarPresupuesto.png" title="Agregar vehículo existente a Trade-In"/></td><td>&nbsp;</td><td>Registrados</td></tr></table></button>
                        </a>
                    </td>
                </tr>
                </table>
                
            <form id="frmBuscar" name="frmBuscar" onsubmit="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar')); return false;" style="margin:0">
                <table align="right" border="0">
                <tr align="left">
                	<td align="right" class="tituloCampo">Empresa:</td>
                    <td id="tdlstEmpresa" colspan="3"></td>
				</tr>
                <tr align="left">
                	<td align="right" class="tituloCampo">Fecha:</td>
                    <td>
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                        	<td>&nbsp;Desde:&nbsp;</td>
                        	<td><input type="text" id="txtFechaDesde" name="txtFechaDesde" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        	<td>&nbsp;Hasta:&nbsp;</td>
                        	<td><input type="text" id="txtFechaHasta" name="txtFechaHasta" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                	<td align="right" class="tituloCampo">Filtrar por Fecha:</td>
                    <td id="tdlstTipoFecha"></td>
				</tr>
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Estado de Compra:</td>
                    <td id="tdlstEstadoCompraBuscar"></td>
                    <td align="right" class="tituloCampo" width="120">Estado de Venta:</td>
                    <td id="tdlstEstadoVentaBuscar"></td>  
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Almacén:</td>
                    <td id="tdlstAlmacen"></td>
                    <td align="right" class="tituloCampo" width="120">Estado Trade-In:</td>
                    <td>
                        <select class="inputHabilitado" id="lstAnuladoTradein" name="lstAnuladoTradein" style="width:99%">
                            <option value="-1">[ Seleccione ]</option>
                            <option value="0">Activo</option>
                            <option value="1">Anulado</option>
                        </select>
                    </td>
                </tr>
                <tr align="left">
                	<td></td>
                	<td></td>
                    <td align="right" class="tituloCampo">Criterio:</td>
                    <td><input type="text" name="txtCriterio" id="txtCriterio"/></td>
                    <td>
                        <button type="submit" id="btnBuscar" name="btnBuscar" onclick="xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar'));">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscar'].reset(); byId('btnBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
        	</form>
            </td>
        </tr>
        <tr>
            <td>
                <form id="frmListaUnidadFisica" name="frmListaUnidadFisica" style="margin:0">
                    <div id="divListaUnidadFisica" style="width:100%"></div>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_new.png"/></td><td>Nuevo Registro Trade-In</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/generarPresupuesto.png"/></td><td>Agregar vehículo existente a Trade-In</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/book_next.png"/></td><td>Volver a Generar <?php echo $spanAnticipo; ?></td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/pencil.png"/></td><td>Editar Trade-In</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_cambio.png"/></td><td>Cambiar Nota de Débito CxP</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_white_acrobat.png"/></td><td>Ver Vale de Entrada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page.png"/></td><td>Ver <?php echo $spanAnticipo; ?></td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_green.png"/></td><td>Ver Nota Cargo CxP</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/page_red.png"/></td><td>Ver Nota Cargo CxC</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" cellpadding="0" cellspacing="0" class="divMsjInfo2">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/ico_verde.gif"/></td><td>Activo</td>
                            <td><img src="../img/iconos/ico_rojo.gif"/></td><td>Anulado</td>                                            
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
	<div id="divFlotanteTitulo1" class="handle"><table><tr><td id="tdFlotanteTitulo1" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
<form id="frmUnidadFisica" name="frmUnidadFisica" onsubmit="return false;" style="margin:0">
    <div id="tblUnidadFisica" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
            <td>
                <table border="0" width="100%">
                <tr>
                    <td width="68%"></td>
                    <td width="32%"></td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Datos de la Unidad</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td width="30%">
                            	<input type="text" id="txtNombreUnidadBasica" name="txtNombreUnidadBasica" readonly="readonly" size="24"/>
				            	<input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica"/>
							</td>
                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
                            <td width="30%"><input type="text" id="txtClaveUnidadBasica" name="txtClaveUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcion" name="txtDescripcion" cols="20" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaUnidadBasica" name="txtMarcaUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td><input type="text" id="txtModeloUnidadBasica" name="txtModeloUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Versión:</td>
                            <td><input type="text" id="txtVersionUnidadBasica" name="txtVersionUnidadBasica" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Año:</td>
                            <td><input type="text" id="txtAno" name="txtAno" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlaca" name="txtPlaca" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Condición:</td>
                            <td><input type="text" id="txtCondicion" name="txtCondicion" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fabricación:</td>
                            <td><input type="text" id="txtFechaFabricacion" name="txtFechaFabricacion" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?>:</td>
                            <td><input type="text" id="txtKilometraje" name="txtKilometraje" readonly="readonly" size="24" style="text-align:right"/></td>
                            <td align="right" class="tituloCampo">Expiración Marbete:</td>
                            <td><input type="text" id="txtFechaExpiracionMarbete" name="txtFechaExpiracionMarbete" readonly="readonly" size="10" style="text-align:center"/></td>
						</tr>
                        </table>
                    </fieldset>
                    </td>
                    <td rowspan="2" valign="top">
                        <table border="0" width="100%">
                        <tr>
                            <td align="center" class="imgBorde" colspan="2"><img id="imgArticulo" width="220"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Id Unidad Física:</td>
                            <td width="60%"><input type="text" id="txtIdUnidadFisica" name="txtIdUnidadFisica" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Almacén:</td>
                            <td><input type="text" id="txtAlmacen" name="txtAlmacen" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Compra:</td>
                            <td><input type="text" id="txtEstadoCompra" name="txtEstadoCompra" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Venta:</td>
                            <td>
                            	<div id="tdlstEstadoVenta"></div>
                            	<input type="hidden" id="hddEstadoVenta" name="hddEstadoVenta">
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                    <fieldset><legend class="legend">Colores</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Color Externo 1:</td>
                            <td width="30%"><input type="text" id="txtColorExterno1" name="txtColorExterno1" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo" width="20%">Color Externo 2:</td>
                            <td width="30%"><input type="text" id="txtColorExterno2" name="txtColorExterno2" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Color Interno 1:</td>
                            <td><input type="text" id="txtColorInterno1" name="txtColorInterno1" readonly="readonly" size="24"/></td>
                            <td align="right" class="tituloCampo">Color Interno 2:</td>
                            <td><input type="text" id="txtColorInterno2" name="txtColorInterno2" readonly="readonly" size="24"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                	<td valign="top">
                    <fieldset><legend class="legend">Seriales</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialCarroceria; ?>:</td>
                            <td width="30%">
                            <div style="float:left">
                                <input type="text" id="txtSerialCarroceria" name="txtSerialCarroceria" maxlength="<?php echo substr($arrayValidarCarroceria[0], -6,2); ?>" readonly="readonly"/>
                            </div>
                            <div style="float:left">
                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo utf8_encode($titleFormatoCarroceria); ?>"/>
                            </div>
                            </td>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotor" name="txtSerialMotor" readonly="readonly"/></td>
						</tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Vehículo:</td>
                            <td><input type="text" id="txtNumeroVehiculo" name="txtNumeroVehiculo" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. de Titulo del Vehiculo:</td>
                            <td><input type="text" id="txtTituloVehiculo" name="txtTituloVehiculo"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanRegistroLegalizacion; ?>:</td>
                            <td><input type="text" id="txtRegistroLegalizacion" name="txtRegistroLegalizacion" readonly="readonly"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederal" name="txtRegistroFederal" readonly="readonly"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top">
                    <fieldset><legend class="legend">Trade-In</legend>
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="45%">Id Trade-In:</td>
                            <td width="55%"><input type="text" id="txtIdTradeIn" name="txtIdTradeIn" class="inputCompleto" maxlength="12" readonly="readonly" style="text-align:center"/></td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Allowance:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td><input type="text" id="txtAllowance_2" name="txtAllowance_2" class="inputCompleto" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto por el cual será recibido" /></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">ACV:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td><input type="text" id="txtAcv_2" name="txtAcv_2" class="inputCompleto" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Valor en el inventario" /></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo">Payoff:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td><input type="text" id="txtPayoff_2" name="txtPayoff_2" class="inputCompleto" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto total adeudado" /></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo">Crédito Neto:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td><input type="text" id="txtCreditoNeto_2" name="txtCreditoNeto_2" class="inputCompleto" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Crédito Neto" /></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <fieldset><legend class="legend">Datos Personales</legend>
                    <table width="100%" border="0">
                    <tbody><tr>
                        <td width="15%" align="right" class="tituloCampo">Cliente:</td>
                        <td width="85%">
                        	<table cellpadding="0" cellspacing="0">
                            <tr>
                                <td><input type="text" id="txtIdCliente_2" name="txtIdCliente_2" onblur="xajax_asignarCliente('Cliente_2', this.value, byId('hddIdEmpresa').value, '', '', 'true', 'false');" size="6" style="text-align:right;"/></td>
                                <td>
                                <a class="modalImg" id="aListarCliente2" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblLista2', 'Cliente', 'Cliente_2');">
                                    <button type="button" id="btnListarCliente_2" name="btnListarCliente_2" title="Listar"><img src="../img/iconos/help.png"/></button>
                                </a>
                                </td>
                                <td><input type="text" id="txtNombreCliente_2" name="txtNombreCliente_2" readonly="readonly" size="45"/></td>
                            </tr>
                            </table>
                            <input type="hidden" id="hddIdEmpresa" name="hddIdEmpresa" />
                        </td>
                    </tr>
                    </tbody></table>
                </fieldset>
            </td>
        </tr>   
        <tr>
            <td>
                <table border="0" width="100%">
                <tr>
                    <td valign="top" width="32%">
                    <fieldset><legend class="legend">Datos del <?php echo $spanAnticipo; ?></legend>                                    
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="40%">Nro. <?php echo $spanAnticipo; ?>:</td>
                            <td width="60%"><input type="text" id="txtNumeroAnticipo_2" name="txtNumeroAnticipo_2" class="inputCompleto" readonly="readonly" style="text-align:center"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Módulo:<input type="hidden" id="hddIdModulo" name="hddIdModulo"/></td>
                            <td id="tdlstModulo_2"></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Monto <?php echo $spanAnticipo; ?>:</td>
                            <td><input type="text" id="txtMontoAnticipo_2" name="txtMontoAnticipo_2" class="inputCompleto" readonly="readonly" style="text-align:right" /></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="34%">
                    <fieldset><legend class="legend">Forma de Pago</legend>
                        <table border="0" width="100%">                                    
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Forma de Pago:</td>
                            <td width="60%">
                            	<input type="hidden" id="hddIdFormaPago" name="hddIdFormaPago"/>
                            	<input type="text" id="lstFormaPago_2" class="inputCompleto" readonly="readonly"/>
							</td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Concepto de Pago:</td>
                            <td>
                            	<input type="hidden" id="hddIdConceptoPago" name="hddIdConceptoPago"/>
                            	<input id="lstConceptoPago_2" class="inputCompleto" readonly="readonly"/>
							</td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Subtotal Vale:</td>
                            <td align="right"><input type="text" id="txtSubTotal_2" name="txtSubTotal_2" class="inputCompleto" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="34%">
                    <fieldset><legend class="legend">Observación Vale - <?php echo $spanAnticipo; ?></legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="15%">Observación:</td>
                            <td width="85%"><textarea id="txtObservacion_2" name="txtObservacion_2" readonly="readonly" rows="3" style="width:99%"></textarea></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td colspan="20">
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita"></span>Adeudado a:</td>
                            <td width="85%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdProv_2" name="txtIdProv_2" readonly="readonly" style="text-align:right;" size="6"/></td>
                                    <td><input type="text" id="txtNombreProv_2" name="txtNombreProv_2" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>      
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnGenerarAnticipo" name="btnGenerarAnticipo" onclick="validarFrmUnidadFisica();">Generar Nuevo <?php echo $spanAnticipo; ?></button>
                <button type="button" id="btnCancelarGenerarAnticipo" name="btnCancelarGenerarAnticipo" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
	
    <table border="0" id="tblLista" style="display:none" width="960">
    <tr>
    	<td>
        	<form id="frmBuscarLista" name="frmBuscarLista" onsubmit="return false;" style="margin:0">
            	<table align="right">
                <tr align="left">
                	<td align="right" class="tituloCampo" width="120">Criterio:</td>
                	<td><input type="text" id="txtCriterioBuscarLista" name="txtCriterioBuscarLista" onkeyup="byId('btnBuscarLista').click();"/></td>
                    <td>
                    	<button type="submit" id="btnBuscarLista" name="btnBuscarLista">Buscar</button>
						<button type="button" onclick="document.forms['frmBuscarLista'].reset(); byId('btnBuscarLista').click();">Limpiar</button>
					</td>
                </tr>
                </table>
            </form>
        </td>
    </tr>
    <tr>
    	<td><div id="divLista" style="width:100%"></div></td>
    </tr>
    <tr>
    	<td align="right"><hr>
            <button type="button" id="btnCancelarLista" name="btnCancelarLista" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
	
<form id="frmAjusteInventario" name="frmAjusteInventario" onsubmit="return false;" style="margin:0">
    <div id="tblAjusteInventario" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresa" name="txtIdEmpresa" size="6" readonly="readonly" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
		</tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td valign="top" width="65%">
                    <fieldset><legend class="legend">Datos Personales</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="15%"><span class="textoRojoNegrita">*</span>Cliente:</td>
                            <td width="85%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" onblur="xajax_asignarCliente('Cliente', this.value, byId('txtIdEmpresa').value, '', '', 'true', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarCliente" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblLista2', 'Cliente', 'Cliente');">
                                        <button type="button" id="btnListarCliente" name="btnListarCliente" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreCliente" name="txtNombreCliente" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%">Nro. Unidad Fisica:</td>
                            <td width="30%">
                            	<table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td> <input type="text" id="txtIdUnidadFisicaAjuste" name="txtIdUnidadFisicaAjuste" readonly="readonly" size="24" style="text-align:center"/></td>
                                    <td>
                                    <a  class="modalImg" id="aNuevo" rel="#divFlotante3" onclick="byId('btnBuscarVehiculo').click();">
                                        <button type="button" id="btnListarVehiculos" name="btnListarVehiculos" title="Listar Vehículos"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                </tr>
                                </table>
							</td>
                            <td align="right" class="tituloCampo" width="20%">Estado de Venta:</td>
                            <td width="30%"><input type="text" id="txtEstadoVenta" name="txtEstadoVenta" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </td>
                    <td id="datosVale" style="display:none;" valign="top" width="35%">
                    <fieldset><legend class="legend">Datos del Vale</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%">Nro. Vale:</td>
                            <td width="60%">
                                <input type="text" id="txtNumeroVale" name="txtNumeroVale" readonly="readonly" size="20" style="text-align:center;"/>
                                <input type="hidden" id="hddIdVale" name="hddIdVale" readonly="readonly"/>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo de Vale</td>
                            <td>
                                <select id="lstTipoVale" name="lstTipoVale" onchange="xajax_asignarTipoVale(this.value);" style="width:99%">
                                    <!--<option value="-1">[ Seleccione ]</option>-->
                                    <option value="1">Entrada / Salida</option>
                                    <!--<option value="3">Nota de Crédito de CxC</option>-->
                                </select>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo Mov:</td>
                            <td id="tdlstTipoMovimiento"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Clave Mov.:</td>
                            <td id="tdlstClaveMovimiento"></td>
                        </tr>
                        <tr align="left" id="trNroDcto" style="display:none">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Nota Crédito:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <input type="hidden" id="hddIdDcto" name="hddIdDcto" readonly="readonly"/>
                                        <input type="text" id="txtNroDcto" name="txtNroDcto" readonly="readonly" size="20" style="text-align:center;"/>
                                    </td>
                                    <td>
                                    <a class="modalImg" id="aListarDcto" rel="#divFlotante1" onclick="abrirDivFlotante1(this, 'tblLista');">
                                        <button type="button" id="btnListarDcto" name="btnListarDcto" title="Listar"><img src="../img/iconos/help.png"/></button>
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
            </td>
        </tr>
        <tr id="trUnidadFisica">
        	<td>
            <fieldset><legend class="legend">Unidad Física</legend>
            	<table width="100%">
                <tr>
                	<td valign="top" width="68%">
                    <fieldset><legend class="legend">Datos de la Unidad</legend>
                        <table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td id="tdlstUnidadBasica" width="30%"></td>
                            <td align="right" class="tituloCampo" width="20%">Clave:</td>
                            <td width="30%"><input type="text" id="txtClaveUnidadBasicaAjuste" name="txtClaveUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" rowspan="3">Descripción:</td>
                            <td rowspan="3"><textarea id="txtDescripcionAjuste" name="txtDescripcionAjuste" cols="20" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaUnidadBasicaAjuste" name="txtMarcaUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td><input type="text" id="txtModeloUnidadBasicaAjuste" name="txtModeloUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Versión:</td>
                            <td><input type="text" id="txtVersionUnidadBasicaAjuste" name="txtVersionUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Año:</td>
                            <td id="tdlstAno"></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Condición:</td>
                            <td id="tdlstCondicion"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlacaAjuste" name="txtPlacaAjuste" size="24"/></td>
                            <td align="right" class="tituloCampo">Tipo Tablilla</td>
                            <td id="tdlstTipoTablilla"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fabricación:</td>
                            <td><input type="text" id="txtFechaFabricacionAjuste" name="txtFechaFabricacionAjuste" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?>:</td>
                            <td><input type="text" id="txtKilometrajeAjuste" name="txtKilometrajeAjuste" onkeypress="return validarSoloNumeros(event);" size="24" style="text-align:right"/></td>
                            <td align="right" class="tituloCampo">Expiración Marbete:</td>
                            <td><input type="text" id="txtFechaExpiracionMarbeteAjuste" name="txtFechaExpiracionMarbeteAjuste" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="32%">
                    	<table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>Almacén:</td>
                            <td id="tdlstAlmacenAjuste" width="60%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Estado Compra:</td>
                            <td id="tdlstEstadoCompraAjuste"><input type="text" id="txtEstadoCompraAjuste" name="txtEstadoCompraAjuste" readonly="readonly" size="24" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Estado Venta:</td>
                            <td id="tdlstEstadoVentaAjuste"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Moneda:</td>
                            <td id="tdlstMoneda"></td>
                        </tr>
                        </table>
                    </td>
				</tr>
                <tr>
                	<td valign="top">
                    <fieldset><legend class="legend">Colores</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Color Externo 1:</td>
                            <td id="tdlstColorExterno1" width="30%"></td>
                            <td align="right" class="tituloCampo" width="20%">Color Externo 2:</td>
                            <td id="tdlstColorExterno2" width="30%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Color Interno 1:</td>
                            <td id="tdlstColorInterno1"></td>
                            <td align="right" class="tituloCampo">Color Interno 2:</td>
                            <td id="tdlstColorInterno2"></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td rowspan="2" valign="top">
                    <fieldset><legend class="legend">Trade-In</legend>
                        <table border="0" width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="45%">Id Trade-In:</td>
                            <td width="55%"><input type="text" id="hddIdTradeInAjusteInventario" name="hddIdTradeInAjusteInventario" class="inputCompleto" maxlength="12" readonly="readonly" style="text-align:center"/></td>
						</tr>
                        <tr align="right">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Allowance:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtAllowance" name="txtAllowance" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto por el cual será recibido" /></td>
                                </tr>
                                <tr id="trtxtAllowanceAnt">
                                	<td class="textoNegrita_10px">Anterior:</td>
                                	<td><input type="text" id="txtAllowanceAnt" name="txtAllowanceAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>ACV:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtAcv" name="txtAcv" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Valor en el inventario" /></td>
								</tr>
                                <tr id="trtxtAcvAnt">
                                	<td class="textoNegrita_10px">Anterior:</td>
                                	<td><input type="text" id="txtAcvAnt" name="txtAcvAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
								</tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Payoff:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtPayoff" name="txtPayoff" class="inputHabilitado" onblur="setFormatoRafk(this,2); calcularMonto();" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto total adeudado" /></td>
								</tr>
                                <tr id="trtxtPayoffAnt">
                                	<td class="textoNegrita_10px">Anterior:</td>
                                	<td><input type="text" id="txtPayoffAnt" name="txtPayoffAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
								</tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="right" class="trResaltarTotal">
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Crédito Neto:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtCreditoNeto" name="txtCreditoNeto" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Crédito Neto" /></td>
								</tr>
                                <tr id="trtxtCreditoNetoAnt">
                                	<td class="textoNegrita_10px">Anterior:</td>
                                	<td><input type="text" id="txtCreditoNetoAnt" name="txtCreditoNetoAnt" class="inputSinFondo textoNegrita_10px" readonly="readonly" style="text-align:right"/></td>
								</tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    
                    	<table width="100%">
                        <tr align="right">
                            <td class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>Subtotal Vale:</td>
                            <td width="55%"><input type="text" id="txtSubTotal" name="txtSubTotal" maxlength="12" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                	<td>
                    <fieldset><legend class="legend">Seriales</legend>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialCarroceria; ?></td>
                            <td width="30%">
                            	<table cellpadding="0" cellspacing="0">
                                <tr>
                                	<td>
                                    <div style="float:left">
                                        <input type="text" id="txtSerialCarroceriaAjuste" name="txtSerialCarroceriaAjuste" maxlength="<?php echo substr($arrayValidarCarroceria[0], -6,2); ?>"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo utf8_encode($titleFormatoCarroceria); ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                <tr id="trAsignarUnidadFisica">
	                                <td><label><input type="checkbox" id="cbxAsignarUnidadFisica" name="cbxAsignarUnidadFisica" onclick="xajax_buscarCarroceria(xajax.getFormValues('frmAjusteInventario'));" value="1"/>Asignar unidad física anteriormente vendida</label></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotorAjuste" name="txtSerialMotorAjuste"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Vehículo:</td>
                            <td><input type="text" id="txtNumeroVehiculoAjuste" name="txtNumeroVehiculoAjuste"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. de Titulo del Vehiculo:</td>
                            <td><input type="text" id="txtTituloVehiculoAjuste" name="txtTituloVehiculoAjuste"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanRegistroLegalizacion; ?>:</td>
                            <td><input type="text" id="txtRegistroLegalizacionAjuste" name="txtRegistroLegalizacionAjuste"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederalAjuste" name="txtRegistroFederalAjuste"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
			</fieldset>
            </td>
        </tr>
        <tr>
            <td>
                <table border="0" width="100%">
                <tr id="trDatosAnticipo">
                    <td colspan="2" valign="top" width="100%">
                    <fieldset><legend class="legend">Datos del <?php echo $spanAnticipo; ?></legend>
                    	<table border="0" width="100%">
                        <tr id="trConceptoPago">
                        	<td valign="top" width="60%">
                            	<table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Nro. <?php echo $spanAnticipo; ?>:</td>
                                    <td width="30%">
                                        <input type="text" id="txtNumeroAnticipo" name="txtNumeroAnticipo" placeholder="Por Asignar" readonly="readonly" size="20" style="text-align:center"/>
                                    </td>
                                    <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Forma de Pago:</td>
                                    <td id="tdTipoPago" width="30%">
                                        <select id="lstFormaPago" name="lstFormaPago" onchange="cambiar()" style="width:99%">
                                            <option>Tipo Pago</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Módulo:</td>
                                    <td id="tdlstModulo"></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Concepto de Pago:</td>
                                    <td id="tdlstConceptoPago">
                                        <select id="lstConceptoPago" name="lstConceptoPago" style="width:99%">
                                            <option value="-1">[ Seleccione ]</option>
                                        </select>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        	<td valign="top" width="40%">
                            	<table width="100%">
                                <tr align="left">
                                    <td class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
                                </tr>
                                <tr align="left">
                                    <td><textarea id="txtObservacion" name="txtObservacion" rows="3"></textarea></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                        	<td colspan="2">
                            	<table width="100%">
                                <tr align="left">
                                	<td width="16%"></td>
                                	<td width="50%"></td>
                                	<td align="right" class="tituloCampo" width="16%">Trade-In:</td>
                                	<td width="18%"><input type="text" id="txtMontoPago" name="txtMontoPago" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                                </tr>
                                <tr id="trMotivoAntCxC" align="left">
                                    <td align="right" class="tituloCampo">
                                    	<span class="textoRojoNegrita">*</span>Motivo:
                                        <br />
                                        <span id="spnMotivoAntCxC" class="textoNegrita_10px"></span>
                                    </td>
                                    <td>
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td><input type="text" id="txtIdMotivoAntCxC" name="txtIdMotivoAntCxC" onblur="xajax_asignarMotivo(this.value, 'MotivoAntCxC', 'CC', 'I', 'false');" size="6" style="text-align:right;"/></td>
                                            <td>
                                            <a class="modalImg" id="aListarMotivoAntCxC" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoAntCxC', 'CC', 'I');">
                                                <button type="button" id="btnListarMotivoAntCxC" name="btnListarMotivoAntCxC" title="Listar"><img src="../img/iconos/help.png"/></button>
                                            </a>
                                            </td>
                                            <td><input type="text" id="txtMotivoAntCxC" name="txtMotivoAntCxC" readonly="readonly" size="36"/></td>
                                        </tr>
                                        </table>
                                    </td>
                                	<td align="right" class="tituloCampo">Contra Utilidad:</td>
                                	<td>
                                    	<input type="text" id="txtMontoAntCxC" name="txtMontoAntCxC" class="inputCompleto" readonly="readonly" style="text-align:right"/>
        		                    	<input type="hidden" id="hddMontoAntCxC" name="hddMontoAntCxC" class="inputCompleto" readonly="readonly" style="text-align:right"/>
									</td>
                                </tr>
                                <tr align="left" class="trResaltarTotal">
                                	<td></td>
                                	<td></td>
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Monto <?php echo $spanAnticipo; ?>:</td>
                                    <td>
                                    	<input type="text" id="txtMontoAnticipo" name="txtMontoAnticipo" class="inputCompleto" readonly="readonly" style="text-align:right"/>
                                        <input type="hidden" id="hddMontoAnticipo" name="hddMontoAnticipo" class="inputCompleto" readonly="readonly" style="text-align:right"/>
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
                    <td id="trCxP" valign="top" width="50%">
                    <fieldset><legend id="legendCuentaPagar" class="legend">Cuenta por Pagar</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Adeudado a:</td>
                            <td width="80%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdProv" name="txtIdProv" onblur="xajax_asignarProveedor(this.value, 'Prov', 'true', 'false');" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarProv" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblLista2', 'Prov');">
                                        <button type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreProv" name="txtNombreProv" readonly="readonly" size="36"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Motivo:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdMotivo" name="txtIdMotivo" onblur="xajax_asignarMotivo(this.value, 'Motivo', 'CP', 'E', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarMotivo" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo', 'Motivo', 'CP', 'E');">
                                        <button type="button" id="btnListarMotivo" name="btnListarMotivo" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtMotivo" name="txtMotivo" readonly="readonly" size="36"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Total:</td>
                        	<td>
                            	<input type="text" id="txtMontoCxP" name="txtMontoCxP" class="inputCompleto" readonly="readonly" style="text-align:right"/>
                            	<input type="hidden" id="hddMontoCxP" name="hddMontoCxP" class="inputCompleto" readonly="readonly" style="text-align:right"/>
							</td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
                        	<td>
                            	<textarea id="hddObservacionCxP" name="hddObservacionCxP" readonly="readonly" rows="3" style="width:99%"></textarea>
                            	<textarea id="txtObservacionCxP" name="txtObservacionCxP" rows="3" style="width:99%"></textarea>
							</td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td id="trCxC" valign="top" width="50%">
                    <fieldset><legend id="legendCuentaCobrar" class="legend">Cuenta por Cobrar</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Motivo:</td>
                            <td width="80%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdMotivoCxC" name="txtIdMotivoCxC" onblur="xajax_asignarMotivo(this.value, 'MotivoCxC', 'CC', 'I', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarMotivoCxC" rel="#divFlotante2" onclick="abrirDivFlotante2(this, 'tblListaMotivo', 'MotivoCxC', 'CC', 'I');">
                                        <button type="button" id="btnListarMotivoCxC" name="btnListarMotivoCxC" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtMotivoCxC" name="txtMotivoCxC" readonly="readonly" size="36"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Total:</td>
                        	<td>
                            	<input type="text" id="txtMontoCxC" name="txtMontoCxC" class="inputCompleto" readonly="readonly" style="text-align:right"/>
                                <input type="hidden" id="hddMontoCxC" name="hddMontoCxC" class="inputCompleto" readonly="readonly" style="text-align:right"/>
							</td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observación:</td>
                        	<td>
                            	<textarea id="hddObservacionCxC" name="hddObservacionCxC" readonly="readonly" rows="3" style="width:99%"></textarea>
                            	<textarea id="txtObservacionCxC" name="txtObservacionCxC" rows="3" style="width:99%"></textarea>
							</td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr id="trPolizasNoDevengadas">
                	<td colspan="2">
                    <fieldset><legend class="legend">Pólizas No Devengadas</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                        	<td colspan="2">
                                <a class="modalImg" id="aAgregarPoliza" rel="#divFlotante1" onclick="xajax_insertarPoliza(xajax.getFormValues('frmAjusteInventario'));">
                                    <button type="button" title="Agregar Polizas"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/add.png"/></td><td>&nbsp;</td><td>Agregar</td></tr></table></button>
                                </a>
                                    <button type="button" id="btnQuitarPoliza" name="btnQuitarPoliza" onclick="xajax_eliminarPoliza(xajax.getFormValues('frmAjusteInventario'));" title="Quitar Polizas"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
                            </td>
						</tr>
                        <tr id="trItmPiePoliza" align="right" class="trResaltarTotal">
                            <td class="tituloCampo" width="80%">Total Pólizas No Devengadas:</td>
                            <td width="20%"><input type="text" id="txtTotalPoliza" name="txtTotalPoliza" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>                
            </td>
        </tr>
        <tr id="trTotalCredito">
        	<td>
                <table border="0" width="100%">
                <tr align="right" class="trResaltarTotal2">
                    <td align="right" class="tituloCampo" width="80%">Total Crédito:</td>
                    <td width="20%"><input type="text" id="txtTotalCredito" name="txtTotalCredito" class="inputSinFondo" readonly="readonly" style="text-align:right"/></td>
            	</tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnGuardarAjusteInventario" name="btnGuardarAjusteInventario" onclick="validarFrmAjusteInventario();" style="display:none;">Guardar</button>
                <button type="button" id="btnGenerarTradein" name="btnGenerarTradein" onclick="validarFrmGenerarTradein();" style="display:none;">Generar a Trade-In</button>
                <button type="button" id="btnCancelarAjusteInventario" name="btnCancelarAjusteInventario" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>

<form id="frmTradeInCxP" name="frmTradeInCxP" onsubmit="return false;" style="margin:0">
    <div id="tblTradeInCxP" style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
        	<td><div id="divListaTradeInCxP" style="width:100%"></div></td>
        </tr>
        <tr>
        	<td>
            <fieldset><legend class="legend">Adeudado a:</legend>
	            <table width="100%">
                <tr>
                	<td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Razón Social:</td>
                	<td id="tdlstProveedorTradeInCxP" width="36%"></td>
                    <td width="50%"></td>
                </tr>
                </table>
            </fieldset>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" width="100%">
                <tr>
                	<td width="50%">
					<fieldset><legend class="legend">Dcto. Anterior</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="22%"><span class="textoRojoNegrita">*</span>Nro. Nota de Débito:</td>
                            <td width="36%">
                                <input type="text" id="txtNumeroNotaCargoAnt" name="txtNumeroNotaCargoAnt" readonly="readonly" size="20" style="text-align:center"/>
                                <input type="hidden" id="hddIdNotaCargoAnt" name="hddIdNotaCargoAnt" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                            <td align="right" class="tituloCampo" width="22%">Fecha Registro:</td>
                            <td width="20%"><input type="text" id="txtFechaRegistroNotaCargoAnt" name="txtFechaRegistroNotaCargoAnt" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Razón Social:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdProvTradeInCxPAnt" name="txtIdProvTradeInCxPAnt" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td></td>
                                    <td><input type="text" id="txtNombreProvTradeInCxPAnt" name="txtNombreProvTradeInCxPAnt" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Total:</td>
                            <td colspan="3"><input type="text" id="txtTotalOrdenTradeInCxPAnt" name="txtTotalOrdenTradeInCxPAnt" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                	<td width="50%">
					<fieldset><legend class="legend">Dcto. Nuevo</legend>
                    	<table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="22%"><span class="textoRojoNegrita">*</span>Nro. Nota de Débito:</td>
                            <td width="36%">
                                <input type="text" id="txtNumeroNotaCargo" name="txtNumeroNotaCargo" readonly="readonly" size="20" style="text-align:center"/>
                                <input type="hidden" id="hddIdNotaCargo" name="hddIdNotaCargo" readonly="readonly" size="20" style="text-align:center"/>
                            </td>
                            <td align="right" class="tituloCampo" width="22%">Fecha Registro:</td>
                            <td width="20%"><input type="text" id="txtFechaRegistroNotaCargo" name="txtFechaRegistroNotaCargo" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Razón Social:</td>
                            <td colspan="3">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="txtIdProvTradeInCxP" name="txtIdProvTradeInCxP" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td></td>
                                    <td><input type="text" id="txtNombreProvTradeInCxP" name="txtNombreProvTradeInCxP" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Total:</td>
                            <td colspan="3"><input type="text" id="txtTotalOrdenTradeInCxP" name="txtTotalOrdenTradeInCxP" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
					</fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td align="right"><hr>
            	<input type="hidden" id="hddIdTradeInCxP" name="hddIdTradeInCxP"/>
                <button type="button" id="btnGuardarTradeInCxP" name="btnGuardarTradeInCxP" onclick="validarFrmTradeInCxP();">Guardar</button>
                <button type="button" id="btnCancelarTradeInCxP" name="btnCancelarTradeInCxP" class="close">Cancelar</button>
            </td>
		</tr>
        </table>
	</div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante2" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
    <table border="0" id="tblListaMotivo" width="960">
    <tr>
        <td>
        <form id="frmBuscarMotivo" name="frmBuscarMotivo" onsubmit="return false;" style="margin:0">
            <input type="hidden" id="hddObjDestinoMotivo" name="hddObjDestinoMotivo" readonly="readonly" />
            <input type="hidden" id="hddPagarCobrarMotivo" name="hddPagarCobrarMotivo" readonly="readonly" />
            <input type="hidden" id="hddIngresoEgresoMotivo" name="hddIngresoEgresoMotivo" readonly="readonly" />
            <input type="hidden" id="hddNomVentanaMotivo" name="hddNomVentanaMotivo" readonly="readonly" />
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarMotivo" name="txtCriterioBuscarMotivo" class="inputHabilitado" onkeyup="byId('btnBuscarMotivo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarMotivo" name="btnBuscarMotivo" onclick="xajax_buscarMotivo(xajax.getFormValues('frmBuscarMotivo'));">Buscar</button>
                    <button type="button" onclick="document.forms['frmBuscarMotivo'].reset(); byId('btnBuscarMotivo').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td>
        <form id="frmListaMotivo" name="frmListaMotivo" onsubmit="return false;" style="margin:0">
            <div id="divListaMotivo" style="width:100%"></div>
        </form>
        </td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaMotivo" name="btnCancelarListaMotivo" class="close">Cerrar</button>
        </td>
    </tr>
    </table>
    
    <div id="tblLista2" style="max-height:500px; overflow:auto; width:960px;">
        <table border="0" width="100%">
        <tr id="trBuscarCliente">
            <td>
            <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddObjDestinoCliente" name="hddObjDestinoCliente" readonly="readonly" />
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
        <tr id="trBuscarProveedor">
            <td>
            <form id="frmBuscarProveedor" name="frmBuscarProveedor" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddObjDestinoProveedor" name="hddObjDestinoProveedor" readonly="readonly" />
                <input type="hidden" id="hddNomVentanaProveedor" name="hddNomVentanaProveedor" readonly="readonly" />
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarProveedor" name="txtCriterioBuscarProveedor" onkeyup="byId('btnBuscarProveedor').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarProveedor" name="btnBuscarProveedor" onclick="xajax_buscarProveedor(xajax.getFormValues('frmBuscarProveedor'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarProveedor'].reset(); byId('btnBuscarProveedor').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr id="trBuscarNotaCargo">
            <td>
            <form id="frmBuscarNotaCargo" name="frmBuscarNotaCargo" onsubmit="return false;" style="margin:0">
                <input type="hidden" id="hddObjDestinoNotaCargo" name="hddObjDestinoNotaCargo" readonly="readonly" />
                <input type="hidden" id="hddNomVentanaNotaCargo" name="hddNomVentanaNotaCargo" readonly="readonly" />
                <table align="right">
                <tr align="left">
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioBuscarNotaCargo" name="txtCriterioBuscarNotaCargo" onkeyup="byId('btnBuscarNotaCargo').click();"/></td>
                    <td>
                        <button type="submit" id="btnBuscarNotaCargo" name="btnBuscarNotaCargo" onclick="xajax_buscarNotaCargo(xajax.getFormValues('frmBuscarNotaCargo'), xajax.getFormValues('frmBuscar'));">Buscar</button>
                        <button type="button" onclick="document.forms['frmBuscarNotaCargo'].reset(); byId('btnBuscarNotaCargo').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
        	<form id="frmLista2" name="frmLista2" onsubmit="return false;" style="margin:0">
                <table width="100%">
                <tr>
                    <td><div id="divLista2" style="width:100%;"></div></td>
                </tr>
                <tr>
                    <td align="right"><hr>
                        <button type="button" id="btnCancelarLista2" name="btnCancelarLista2" class="close">Cerrar</button>
                    </td>
                </tr>
            	</table>
        	</form>
            </td>
        </tr>
        </table>
	</div>
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:12000;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%">Listado Unidades Físicas</td><td><img class="close puntero" id="imgCerrarDivFlotante3" src="../img/iconos/cross.png" onclick="byId('divFlotante3').style.display = 'none';" title="Cerrar"/></td></tr></table></div>
      
    <table border="0" id="tblListaVehiculo" width="960">
    <tr>
        <td>
        <form id="frmBuscarVehiculo" name="frmBuscarVehiculo" onsubmit="return false;" style="margin:0">
            <table align="right">
            <tr align="left">
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioBuscarVehiculo" name="txtCriterioBuscarVehiculo" onkeyup="byId('btnBuscarVehiculo').click();"/></td>
                <td>
                    <button type="submit" id="btnBuscarVehiculo" name="btnBuscarVehiculo" onclick="xajax_buscarVehiculo(byId('txtCriterioBuscarVehiculo').value);">Buscar</button>
                    <button type="button" onclick="byId('txtCriterioBuscarVehiculo').value = ''; byId('btnBuscarVehiculo').click();">Limpiar</button>
                </td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td><div id="divListaVehiculos" style="width:100%;"></div></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" id="btnCancelarListaVehiculos" name="btnCancelarListaVehiculos" onclick="byId('divFlotante3').style.display = 'none';">Cerrar</button>
        </td>
    </tr>
    </table>
</div>

<script>
byId('txtCriterio').className = "inputHabilitado";

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

//var lstEstadoCompraBuscar = $.map($("#lstEstadoCompraBuscar option:selected"), function (el, i) { return el.value; });
//var lstEstadoVentaBuscar = $.map($("#lstEstadoVentaBuscar option:selected"), function (el, i) { return el.value; });

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstTipoFecha();
xajax_cargaLstEstadoCompraBuscar('lstEstadoCompraBuscar', '');
xajax_cargaLstEstadoVentaBuscar('lstEstadoVentaBuscar', '');
xajax_cargaLstAlmacen('lstAlmacen', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_listaUnidadFisica(0, 'CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)', 'ASC', '<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

xajax_cargaLstModulo();
xajax_cargaLstFormaPago();
xajax_cargarConceptoPago();
xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', '2', '', '5,6');

var theHandle = document.getElementById("divFlotanteTitulo1");
var theRoot   = document.getElementById("divFlotante1");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

window.onload = function(){
	jQuery(function($){
		$("#txtFechaDesde").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaHasta").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaDesde",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
	
	new JsDatePick({
		useMode:2,
		target:"txtFechaHasta",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme:"orange"
	});
};
</script>