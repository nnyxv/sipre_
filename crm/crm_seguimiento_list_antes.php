<?php
require_once("../connections/conex.php");
	
session_start();

/* ValidaciÃ³n del MÃ³dulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_seguimiento_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin ValidaciÃ³n del MÃ³dulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');//clase xajax
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');//Configuranto la ruta del manejador de script
$xajax->configure( 'defaultMode', 'synchronous' ); 

include("controladores/ac_crm_seguimiento_list.php"); //contiene todas las funciones xajax
include("../controladores/ac_iv_general.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Control de Trafico</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>

    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    <link rel="stylesheet" type="text/css" href="../js/domDragCrm.css">
    
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
	<link rel="stylesheet" type="text/css" media="all" href="../js/jsdatepick-calendar/jsDatePick_ltr.min.css"/>
	<script type="text/javascript" language="javascript" src="../js/jsdatepick-calendar/jsDatePick.jquery.min.1.3.js"></script>
    																					
	<script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
    
    <script src="../js/highcharts/js/highcharts.js"></script>
	<script src="../js/highcharts/js/modules/exporting.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs.css"/>
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/tabs-panes.css"/>
    
	<link type="text/css" rel="stylesheet" href="../js/jquery.mmenu.all.css" />

<script type="text/javascript">
function abrirFrom(idObj, forms, IdObjTitulo, valor, valor2, valor3){ 

	if(IdObjTitulo != "tdFlotanteTitulo12" && IdObjTitulo != "tdFlotanteTitulo13"
		&& IdObjTitulo != "tdFlotanteTitulo14" && IdObjTitulo != "tdFlotanteTitulo4"
		&& IdObjTitulo != "tdFlotanteTitulo5"){	
		document.forms[forms].reset();
		xajax_limpiarListas();
	}

	titulo = '';
	
	if(IdObjTitulo == "tdFlotanteTitulo"){	

		RecorrerForm("frmSeguimiento","text","class","inputHabilitado",["txtEmpresa","txtNombreEmpleado"]);
		xajax_asignarEmpresa(byId('lstEmpresa').value);
		xajax_asignarEmpleado('<?php echo $_SESSION['idEmpleadoSysGts']; ?>', byId('txtIdEmpresa').value);
		//xajax_eliminarModelo(xajax.getFormValues('frmSeguimiento'),false);
		if(valor == 0) {
		    $("#rdCliente").removeClass('selected');
		    $("#rdProspecto").addClass('selected');
			byId('hddIdPerfilProspecto').value = '';
			byId('hddIdClienteProspecto').value = '';
			byId('hddIdSeguimiento').value = '';
			byId('rdCliente').disabled = false;
			byId('rdProspecto').disabled = false;
			byId('rdProspecto').checked = true;
			byId('rdCliente').onclick = function() {
				abrirFrom(this,'frmBusCliente','tdFlotanteTitulo5', 3, 'tblLstCliente');
			}
			byId('rdProspecto').onclick = function() {
				abrirFrom(this,'frmBusCliente','tdFlotanteTitulo5', 1, 'tblLstCliente');
			}	
			var titulo = "Agregar Control de Trafico";
			xajax_cargarDatos(); 
		}else{

			var titulo = "Editar Control de Trafico";	
			xajax_cargarDatos(valor);
			
			if(byId('rdProspecto').value == "activo"){
			    $("#rdCliente").removeClass('selected');
			    $("#rdProspecto").addClass('selected');
			} else{
				$("#rdProspecto").removeClass('selected');
			    $("#rdCliente").addClass('selected');
			}
			byId('rdCliente').disabled = true;
			byId('rdProspecto').disabled = true;
		}	
	} else if (IdObjTitulo == "tdFlotanteTitulo2"){
		titulo = "Empresa";
		xajax_listaEmpresa(0,"","","");
	}else if (IdObjTitulo == "tdFlotanteTitulo3"){
		titulo = "Empleado";
	}else if (IdObjTitulo == "tdFlotanteTitulo4"){
		titulo = "Modelo de Interes";
		byId('txtIdEmpresaBuscarModelo').value =  byId('txtIdEmpresa').value;
		byId('txtEmpresaBuscarModelo').value =  byId('txtEmpresa').value;
		document.forms['frmModelo'].reset();
		byId('btnBuscarModelo').click();
		xajax_cargaLstMedio();
		xajax_cargaLstPlanPago();
		$('#ListLogup').hide();
		$('#ListModelInteres').show();
		return;
	}else if (IdObjTitulo == "tdFlotanteTitulo5"){
		byId('lstTipoCuentaCliente').onchange = function() { selectedOption(this.id,valor); }
		xajax_listaCliente( 0, "","",byId('txtIdEmpresa').value+"||||"+valor);
		if(valor == 3){// CLIENTE
			$('.remover').remove();
			titulo = "Cliente";
			arrayElement = new Array(
				'txtIdEmpresa','hddIdEmpleado',
				'txtCompania','txtFechaNacimiento',
				'txtFechaUltAtencion','txtFechaUltEntrevista',
				'txtFechaProxEntrevista','txtUrbanizacionProspecto',
				'txtCalleProspecto','txtCasaProspecto',
				'txtMunicipioProspecto','txtCiudadProspecto',
				'txtEstadoProspecto','txtTelefonoProspecto',
				'txtOtroTelefonoProspecto','txtUrbanizacionComp',
				'txtCalleComp','txtCasaComp',
				'txtMunicipioComp','txtEstadoComp',
				'txtTelefonoComp','txtOtroTelefonoComp',
				'txtEmailComp');
			RecorrerForm('frmSeguimiento','text','class','inputInicial',arrayElement);
			arrayElement2 = new Array(
				'txtIdEmpresa','hddIdEmpleado',
				'txtCompania','txtFechaNacimiento',
				'txtFechaUltAtencion','txtFechaUltEntrevista',
				'txtFechaProxEntrevista','txtUrbanizacionProspecto',
				'txtCalleProspecto','txtCasaProspecto',
				'txtMunicipioProspecto','txtCiudadProspecto',
				'txtEstadoProspecto','txtTelefonoProspecto',
				'txtOtroTelefonoProspecto','txtUrbanizacionComp',
				'txtCalleComp','txtCasaComp',
				'txtMunicipioComp','txtEstadoComp',
				'txtTelefonoComp','txtOtroTelefonoComp',
				'txtEmailComp');
			RecorrerForm('frmSeguimiento','text','readOnly',true,arrayElement2);
			xajax_cargaLstEquipo('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>',"","Postventa");
		}else if (valor == 1) {//PROSPECTO
			$('.remover').remove();	
			titulo = "Prospecto";
			xajax_cargaLstEquipo('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>'.value+'');
			RecorrerForm('frmSeguimiento','text','class','inputHabilitado',['txtEmpresa','txtNombreEmpleado']);
			RecorrerForm('frmSeguimiento','text','readOnly',false,['txtEmpresa','txtNombreEmpleado']);
		}
	}else if (IdObjTitulo == "tdFlotanteTitulo6"){
		titulo = "Posibilidad de Cierre";
		byId('textHddIdEmpresa').value = byId('lstEmpresa').value;
		byId('hddSeguimientoPosibleCierre').value = valor;
		xajax_listaPosibleCierre(0, "posicion_posibilidad_cierre", "ASC", byId('lstEmpresa').value+"|"+ valor);
		xajax_asignarEmpresa(byId('lstEmpresa').value, "divFlotante6");
	}else if(IdObjTitulo == "tdFlotanteTitulo7"){
		titulo = "Asignacion de Actividad";
		byId('textIdEmpVendedor').className = 'inputInicial';
		byId('textFechAsignacion').className = 'inputHabilitado';
		xajax_cargarDtosAsignacion(valor, valor2);
		document.getElementById('textFechAsignacion').focus();
		document.getElementById('textFechAsignacion').select();
	}else if (IdObjTitulo == "tdFlotanteTitulo8"){
		xajax_listaPosibleCierreObsv(0, "posicion_posibilidad_cierre", "ASC", 'asda');
	}else if (IdObjTitulo == "tdFlotanteTitulo9"){
		titulo = "Registro de Notas/Seguimiento";
		xajax_formNotas(valor);
	}else if (IdObjTitulo == "tdFlotanteTitulo10"){
		titulo = "Citas para Hoy";
		xajax_formListCitas();
		xajax_cargaLstVendedor('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'Citas');
		xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>','onchange=\"xajax_cargaLstVendedor(this.value);xajax_cargarLstPosibilidadCierre(\'\', \'tdLstPosibilidadCierreBus\',this.value); byId(\'btnBuscar\').click();\"', "lstEmpresaCitas");
	}else if (IdObjTitulo == "tdFlotanteTitulo11"){
		titulo = "Ingreso de veh&iacute;culo Trade-In";
		
		byId('hddIdDcto').value = "";
		byId('hddIdTradeInAjusteInventario').value = "";
		
		byId('trAsignarUnidadFisica').style.display = 'none';
		byId('trUnidadFisica').style.display = '';
		
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
			
			byId('txtRegistroLegalizacionAjuste').className = 'inputHabilitado';
			byId('txtRegistroLegalizacionAjuste').readOnly = false;
			
			byId('txtRegistroFederalAjuste').className = 'inputHabilitado';
			byId('txtRegistroFederalAjuste').readOnly = false;
			
			byId('txtObservacion').className = 'inputCompletoHabilitado';
			byId('txtIdUnidadFisicaAjuste').className = '';
			
			
			byId('datosVale').style.display = '';
			byId('btnGuardarAjusteInventario').style.display = '';
			byId('btnEditatTradein').style.display = 'none';
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
				byId('btnEditatTradein').style.display = '';
				byId('btnListarVehiculos').style.display = '';                                                    
				byId('txtAllowance').className = '';
				byId('txtAllowance').readOnly = true;
				byId('txtAcv').className = '';
				byId('txtAcv').readOnly = true;
			}
			
		xajax_formAjusteInventario(xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmAjusteInventario'), valor, valor3);
	} else if (IdObjTitulo == "tdFlotanteTitulo12"){
		titulo = "Log Up";
	} else if (IdObjTitulo == "tdFlotanteTitulo13"){
		titulo = "Asignaci&oacute;n de Vendedor";
		xajax_cargaLstEquipo('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
	} else if (IdObjTitulo == "tdFlotanteTitulo14"){
		titulo = "Ingreso al Dealer";
	} else if (IdObjTitulo == "tdFlotanteTitulo15"){
		titulo = "Continuar Seguimiento";
	}
		
	openImg(idObj);
	byId(IdObjTitulo).innerHTML = titulo;
}

function validarFrmAjusteInventario() {
	error = false;
	
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
		&& validarCampo('txtRegistroLegalizacionAjuste', 't', '') == true
		&& validarCampo('txtRegistroFederalAjuste', 't', '') == true
		&& validarCampo('lstAlmacenAjuste', 't', 'lista') == true
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
			validarCampo('txtRegistroLegalizacionAjuste', 't', '');
			validarCampo('txtRegistroFederalAjuste', 't', '');
			validarCampo('lstAlmacenAjuste', 't', 'lista');
			validarCampo('lstEstadoVentaAjuste', 't', 'lista');
			validarCampo('lstMoneda', 't', 'lista');
			
			error = true;
		}
	}
	
	if (error == true) {
		alert("Los campos seÃ±alados en rojo son requeridos, o no cumplen con el formato establecido");
		return false;
	} else {
		if (confirm('Â¿Seguro desea guardar el trade-in?') == true) {
			calcularMonto();
			xajax_guardarAjusteInventario(xajax.getFormValues('frmAjusteInventario'), xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmListaUnidadFisica'));
		}
	}
}

function RecorrerForm(nameFrm,typeElemen,accion,valor,arrayElement){ 
	var frm = document.getElementById(nameFrm);
	var arrayIdElement= new Array();
	for (i=0; i < frm.elements.length; i++)	{// RECORRE LOS ELEMENTOS DEL FROM
		if(frm.elements[i].type == typeElemen){
			if(arrayElement != null){
				existe = arrayElement.indexOf(frm.elements[i].id) > -1;
				if(!existe){
					arrayIdElement.push(frm.elements[i].id);
				}
			}else{
				arrayIdElement.push(frm.elements[i].id);
			}
		}
	}
/*console.log(arrayIdElement);*/
	for(var indice in arrayIdElement){
		switch(accion){
			case "class": document.getElementById(arrayIdElement[indice]).className = valor;  break;
			case "readOnly": document.getElementById(arrayIdElement[indice]).readOnly = valor;  break;
			case "disabled": document.getElementById(arrayIdElement[indice]).disabled = valor;  break;
		}
	}
}	

function validarFrmSeguimiento(){
	RecorrerForm('frmSeguimiento','button','disabled',true);
	if (validarCampo('txtIdEmpresa','t','') == true
	&& validarCampo('txtEmpresa','t','') == true
	&& validarCampo('hddIdEmpleado','t','') == true
	&& validarCampo('txtNombreEmpleado','t','') == true
	&& validarCampo('txtNombreProspecto','t','') == true
	&& validarCampo('txtCedulaProspecto','t','') == true
	&& validarCampo('txtTelefonoProspecto','t','') == true
	&& validarCampo('txtEmailProspecto','t','') == true
	&& validarCampo('txtFechaNacimiento','t','') == true
	&& validarCampo('lstTipoProspecto','t','lista') == true) {
		xajax_guardarLogup(xajax.getFormValues('frmSegui'));
		xajax_listaVendedorTemp(xajax.getFormValues('frmAsigVendedor'));
		xajax_listaAsigDealerTemp(xajax.getFormValues('frmIngDealer'));
		xajax_guardarSeguimiento(xajax.getFormValues('frmSeguimiento'));
	} else {
		validarCampo('txtIdEmpresa','t','');
		validarCampo('txtEmpresa','t','');
		validarCampo('hddIdEmpleado','t','');
		validarCampo('txtNombreEmpleado','t','');
		validarCampo('txtNombreProspecto','t','');
		validarCampo('txtCedulaProspecto','t','');
		validarCampo('txtTelefonoProspecto','t','lista');
		validarCampo('txtEmailProspecto','t','lista');
		validarCampo('txtEmailProspecto','t','');
		validarCampo('txtFechaNacimiento','t','lista');
		RecorrerForm('frmSeguimiento','button','disabled',false);
		alert("Los campos seÃ±alados en rojo son requeridos");
		return false;
	}
}

function validarFrmModelo() {
	if (validarCampo('txtUnidadBasica','t','') == true
	&& validarCampo('lstMedio','t','lista') == true
	&& validarCampo('lstNivelInteres','t','lista') == true
	&& validarCampo('lstPlanPago','t','lista') == true) {
		xajax_insertarModelo(xajax.getFormValues('frmModelo'), xajax.getFormValues('frmSeguimiento'));
	} else {
		validarCampo('txtUnidadBasica','t','');
		validarCampo('lstMedio','t','lista');
		validarCampo('lstNivelInteres','t','lista');
		validarCampo('lstPlanPago','t','lista');
		alert("Los campos seÃ±alados en rojo son requeridos");
		return false;
	}
}

function validarFrmNotas() { 
	if (validarCampo('textNotas','t','') == true) {
		xajax_guardarNotas(xajax.getFormValues('frmBusNotas'));
	} else {
		validarCampo('textNotas','t','');
		alert("Los campos seÃ±alados en rojo son requeridos");
		return false;
	}
}

function validarFrmObservacion() {
	if (validarCampo('textObservacionCierre','t','') == true) {
		xajax_guardarObservacion(xajax.getFormValues('frmPosibleCierre'));
	} else {
		validarCampo('textObservacionCierre','t','');
		alert("Los campos seÃ±alados en rojo son requeridos");
		return false;
	}
}

function getModelChecked(){
	var checkboxValues = new Array();
	
	$('input[name="cbxItmModeloInteres[]"]:checked').each(function() {
		checkboxValues.push($(this).val());
	});

	return checkboxValues;
}

function menuBar(idObj){

	var id = "table#"+idObj;
	var current = "ul.mm-listview >li."+idObj;
	
	$("table.log_up").css({'display': 'none'});
	$(id).show();
	$("ul.mm-listview >li").removeClass('current');
	
	$("ul > li#"+idObj).addClass('current');
	
	switch (idObj) { 
		case "adicional": 
			$("#tituloLogUp").text('Datos Adicionales');
			break;
		case 'interes': 
			$("#tituloLogUp").text('Modelo de Interes');
			break;
		case 'entrevista': 
			$("#tituloLogUp").text('Entrevistas');
			break;
		case 'observacion': 
			$("#tituloLogUp").text('Observaci\u00F3n');
			break;
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
	
	byId('txtCreditoNeto').value = txtAllowance - txtPayoff;
	byId('txtSubTotal').value = txtAcv;
	txtCreditoNetoAnt = txtAllowanceAnt - txtPayoffAnt;
	
	setFormatoRafk(byId('txtAllowance'),2);
	setFormatoRafk(byId('txtAcv'),2);
	setFormatoRafk(byId('txtPayoff'),2);
	setFormatoRafk(byId('txtCreditoNeto'),2);
	setFormatoRafk(byId('txtSubTotal'),2);
	
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
}

function validarFrmActSeguimiento() {
	if (validarCampo('textFechAsignacion','t','') == true
		&& validarCampo('textIdEmpVendedor','t','') == true
		&& validarCampo('lstActividadSeg','t','lista') == true
		&& validarCampo('listHora','t','lista') == true) {
		xajax_guardarActividadSeguimiento(xajax.getFormValues('formAsignarActividadSeg'));
	} else {
		validarCampo('textFechAsignacion','t','');
		validarCampo('textIdEmpVendedor','t','');
		validarCampo('lstActividadSeg','t','lista')
		validarCampo('listHora','t','lista');
		
		alert("Los campos seÃ±alados en rojo son requeridos");
		return false;
	}
}
</script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_crm.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table width="100%" border="0"> <!--tabla principa-->
            <tr><td class="tituloPaginaCrm">Control de Trafico</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
            	<td>
                	<table border="0" width="100%">
                    	<tr>
                            <td valign="top" align="left" width="50%">
                                <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="abrirFrom(this,'frmSeguimiento','tdFlotanteTitulo', 0, 'tblProspecto')">
                                    <button type="button" id="btnNuevo" style="cursor:default">
                                        <table align="center" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td>
                                                <td>&nbsp;</td>
                                                <td>Agregar trafico Nuevo</td>
                                            </tr>
                                        </table>
                                    </button>
                                </a>
                            </td>
                            <td align="right" width="50%">
                            	<form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                                    <table border="0" width="100%">
                                        <tr>
                                            <td align="right" class="tituloCampo" width="20%">Empresa:</td>
                                            <td id="tdlstEmpresa">&nbsp;</td>
					                        <td valign="top" align="right" width="50%">
					                        	<a class="modalImg" id="aCitas" rel="#divFlotante10" onclick="abrirFrom(this,'frmBusCitas','tdFlotanteTitulo10', 0, 'tblCitas')">
				                                    <button type="button" id="btnNuevo" style="cursor:default">
				                                        <table align="center" cellpadding="0" cellspacing="0">
				                                            <tr>
				                                                <td>&nbsp;</td>
				                                                <td><img class="puntero" src="../img/iconos/cita_entrada.png" title="Editar"/></td>
				                                                <td>&nbsp;</td>
				                                                <td>Lista de Citas</td>
				                                            </tr>
				                                        </table>
				                                    </button>
				                                </a>
				                            </td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Posible Cierre:</td>
                                            <td id="tdLstPosibilidadCierreBus">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Vendedor:</td>
                                            <td id="tdLstVendedor">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Fecha Proxima Cita</td>
                                            <td>
                                                Desde: <input id="textDesdeCita" name="textDesdeCita" class="inputHabilitado" value="" style="width:30%; text-align:center" />
                                                Hasta: <input id="textHastaCita" name="textHastaCita" class="inputHabilitado" value="" style="width:30%; text-align:center" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="right" class="tituloCampo" width="120">Fecha Creacion</td>
                                            <td>
                                            	Desde: <input id="textDesdeCreacion" name="textDesdeCreacion" class="inputHabilitado" value="<?php echo date("d-m-Y") ?>" style="width:30%; text-align:center" />
                                                Hasta: <input id="textHastaCreacion" name="textHastaCreacion" class="inputHabilitado" value="<?php echo date("d-m-Y") ?>" style="width:30%; text-align:center" />
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="120">Criterio</td>
                                            <td width="57%"><input id="textCriterio" name="textCriterio" class="inputHabilitado" style="width:88%" onblur="byId('btnBuscar').click();" /></td>
                                            <td align="right">
                                                <button type="button" id="btnBuscar" onclick="xajax_buscarSeguimiento(xajax.getFormValues('frmBuscar'))">Buscar</button>
                                                <button type="button" id="btnLimpiar" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                                            </td>
                                        </tr>
                                    </table>
                                </form>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr><td align="left" ><h2><?php echo date("l, F d Y"); ?></h2></td></tr>
            <tr>
            	<td>
                	<form id="frmLstSeguimiento" name="frmLstSeguimiento" onsubmit="return false;" style="margin:0">
                    	<div id="divLstSeguimiento"></div>
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
		                        	<td><img src="../img/iconos/text_signature.png" /></td>
		                            <td>Notas</td>
		                            <td>&nbsp;</td>
		                            <td><img src="../img/iconos/plus.png" /></td>
		                            <td>Editar Trade-In</td>
		                            <td>&nbsp;</td>
		                            <td><img src="../img/iconos/car_go.png" /></td>
		                            <td>Agregar Trade-In</td>
		                        </tr>
		                        </table>
		                    </td>
						</tr>
					</table>
            	</td>
            </tr>
        </table>
    </div> <!-- fin contenedor interno-->

    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div> <!--fin del contenedor general-->
</body>
</html>
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
	<form id="frmSeguimiento" name="frmSeguimiento" style="margin:0" onsubmit="return false;">
	<div class="pane" style="max-height:600px; overflow:auto; width:960px;">
        <table border="0" id="tblProspecto" width="100%">
        <tr>
            <td>
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td>
                        <table border="0" width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                            <td>
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" class="" id="txtIdEmpresa" name="txtIdEmpresa" onblur="xajax_asignarEmpresaUsuario(this.value, 'Empresa', 'ListaEmpresa', '', 'false');" size="6" style="text-align:right;"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpresa" rel="#divFlotante2" onclick="abrirFrom(this,'frmBusEmpresa','tdFlotanteTitulo2', '', 'tblListEmpresa')">
                                        <button id="btnAsigEmp" name="btnAsigEmp" type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtEmpresa" name="txtEmpresa" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Empleado:</td>
                            <td width="88%">
                                <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td><input type="text" id="hddIdEmpleado" name="hddIdEmpleado" readonly="readonly" size="6" style="text-align:right"/></td>
                                    <td>
                                    <a class="modalImg" id="aListarEmpleado" rel="#divFlotante3" onclick="abrirFrom(this,'frmBusEmpelado','tdFlotanteTitulo3', '', 'tblListEmpleado')">
                                        <button id="btnLstEmpleado" name="btnLstEmpleado" type="button" title="Listar"><img src="../img/iconos/help.png"/></button>
                                    </a>
                                    </td>
                                    <td><input type="text" id="txtNombreEmpleado" name="txtNombreEmpleado" readonly="readonly" size="45"/></td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="12%"><span class="textoRojoNegrita">*</span>Tipo De Control de Trafico:</td>
                            <td width="88%"> 
								<button style="cursor:default" type="button" id="rdCliente" value="3" name="rdTipo" rel="#divFlotante5">
                                    <table cellspacing="0" cellpadding="0" align="center">
                                        <tbody>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td><img title="Editar" src="../img/iconos/ico_cliente.gif" class="puntero"></td>
                                                <td>&nbsp;</td>
	                                        	<td>Cliente&nbsp;&nbsp;</td>
	                                     	</tr>
	                                	 </tbody>
                                	</table>
                            	</button>
                                <button style="cursor:default" type="button" id="rdProspecto" value="1" name="rdTipo" rel="#divFlotante5">
                                	<table cellspacing="0" cellpadding="0" align="center">
                                		<tbody>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td><img title="Editar" src="../img/iconos/people1.png" class="puntero"></td>
                                                <td>&nbsp;</td>
                                                <td>Prospecto</td>
	                                    	</tr>
	                                	</tbody>
                                	</table>
                            	</button>
                            </td >
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr >
                    <td>
                    <fieldset><legend class="legend">Datos Generales</legend>
                        <table border="0" width="100%">
                        <tr>
                            <td width="11%"></td>
                            <td width="26%"></td>
                            <td width="11%"></td>
                            <td width="25%"></td>
                            <td width="11%"></td>
                            <td width="16%"></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Tipo:</td>
                            <td>
                                <select id="lstTipoProspecto" name="lstTipoProspecto" class="inputHabilitado" style="width:96%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">Natural</option>
                                    <option value="2">Juridico</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanClienteCxC; ?>:</td>
                            <td nowrap="nowrap">
	                            <div style="float:left">
	                                <input type="text" id="txtCedulaProspecto" name="txtCedulaProspecto" maxlength="18" size="20" style="text-align:center; width:194px;"/>
	                            </div>
	                            <div style="float:left">
	                                <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCI; ?>"/>
	                            </div>
                            </td>
                            <td align="right" class="tituloCampo" width="14%"><span class="textoRojoNegrita">*</span>Fecha Nacimiento:</td>
                          	<td width="20%"><input type="text" id="txtFechaNacimiento" name="txtFechaNacimiento" size="12" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre:</td>
                            <td><input type="text"  style="width:94%" id="txtNombreProspecto"name="txtNombreProspecto" size="25" maxlength="50"/></td>
                            <td align="right" class="tituloCampo">Apellido:</td>
                            <td><input type="text" id="txtApellidoProspecto" style="width:88%" name="txtApellidoProspecto" size="25" maxlength="50"/></td>
 						</tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr >
                    <td>
                        <table border="0" width="100%">
                        <tr>
                            <td valign="top" width="50%">
                            <fieldset><legend class="legend">DirecciÃ³n Particular</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo">UrbanizaciÃ³n:</td>
                                    <td colspan="3"><input type="text" name="txtUrbanizacionProspecto" id="txtUrbanizacionProspecto" style="width:99%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Calle / Av.:</td>
                                    <td><input type="text" name="txtCalleProspecto" id="txtCalleProspecto" style="width:97%"/></td>
                                    <td align="right" class="tituloCampo">Casa / Edif.:</td>
                                    <td><input type="text" name="txtCasaProspecto" id="txtCasaProspecto" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo $spanMunicipio; ?>:</td>
                                    <td><input type="text" name="txtMunicipioProspecto" id="txtMunicipioProspecto" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Ciudad:</td>
                                    <td><input type="text" name="txtCiudadProspecto" id="txtCiudadProspecto" style="width:97%"/></td>
                                    <td align="right" class="tituloCampo"><?php echo $spanEstado; ?>:</td>
                                    <td><input type="text" name="txtEstadoProspecto" id="txtEstadoProspecto" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>TelÃ©fono:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtTelefonoProspecto" id="txtTelefonoProspecto" size="16" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                    <td align="right" class="tituloCampo">Otro Telf.:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtOtroTelefonoProspecto" id="txtOtroTelefonoProspecto" size="16" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanEmail; ?>:</td>
                                    <td colspan="3">
                                    <div style="float:left">
                                        <input type="text" name="txtEmailProspecto" id="txtEmailProspecto" size="30" maxlength="50"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                </table>
                            </fieldset>
                            </td>
                            <td valign="top" width="50%">
                            <fieldset><legend class="legend">DirecciÃ³n de Trabajo</legend>
                                <table border="0" width="100%">
                                <tr align="left">
                                    <td align="right" class="tituloCampo">UrbanizaciÃ³n:</td>
                                    <td colspan="3"><input type="text" name="txtUrbanizacionComp" id="txtUrbanizacionComp" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Calle / Av.:</td>
                                    <td><input type="text" name="txtCalleComp" id="txtCalleComp" style="width:97%"/></td>
                                    <td align="right" class="tituloCampo">Casa / Edif.:</td>
                                    <td><input type="text" name="txtCasaComp" id="txtCasaComp" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo $spanMunicipio; ?>:</td>
                                    <td><input type="text" name="txtMunicipioComp" id="txtMunicipioComp" style="width:97%"/></td>
                                    <td align="right" class="tituloCampo"><?php echo $spanEstado; ?>:</td>
                                    <td><input type="text" name="txtEstadoComp" id="txtEstadoComp" style="width:97%"/></td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo">TelÃ©fono:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtTelefonoComp" id="txtTelefonoComp" size="16" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                    <td align="right" class="tituloCampo">Otro Telf.:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" name="txtOtroTelefonoComp" id="txtOtroTelefonoComp" size="16" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoTelf; ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo"><?php echo $spanEmail; ?>:</td>
                                    <td colspan="3">
                                    <div style="float:left">
                                        <input type="text" name="txtEmailComp" id="txtEmailComp" size="30" maxlength="50"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo $titleFormatoCorreo; ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                </table>
                            </fieldset>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <!-- 
        	---
        	--- LISTAS (Log Up, Asignar vendedor e Ingreso al Dealer)
        	---
         -->
        <tr>
            <td align="left">
	          	<form name="frm_agregar_paq" id="frm_agregar_paq" style="margin:0">
	 				<table border="0"  width="100%">
						<tr>
	                    	<td colspan="9">
	                    		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea">
	                        		<tr>
		                            	<td width="44%" height="22" align="left">
			                            	<button type="button" id="btnInsertarLogUp" name="btnInsertarLogUp" rel="#divFlotante12" onclick="abrirFrom(this,'frmSegui','tdFlotanteTitulo12', '', 'tblListModelInteres');" title="Insertar Log Up"><img src="../img/iconos/ico_agregar.gif"/></button>
		                            	<td width="56%" align="left">Log-up</td>
	                          		</tr>
	                       	 	</table>
	                       	 </td>
						</tr>
                      	<tr class="tituloColumna">
                      		<td width="5%" align="center" class="celda_punteada">Id</td>
                            <td width="48%" align="center" class="celda_punteada">Modelo</td>
                            <td width="13%" align="center" class="celda_punteada">Precio</td>
                            <td width="13%" align="center" class="celda_punteada">Medio</td>
                            <td width="13%" align="center" class="celda_punteada">Niv. Interes</td>
                            <td width="13%" align="center" class="celda_punteada">Plan Pago</td>
                     	</tr>
	                    <tr id="trItmPieModeloInteres2"></tr>
	                    <tr id="trMsjError">
	                    	<td colspan="6">
								<table cellpadding="0" cellspacing="0" colpsan="6" class="divMsjError" width="100%">
									<tr>
										<td align="center">No se ha asignado un Modelo de Interes</td>
									</tr>
								</table>
							</td>
						</tr>
	                </table>
				</form>          
			</td>
		</tr>
		<tr>
            <td align="left">
	          	<form name="frm_agregar_paq" id="frm_agregar_paq" style="margin:0">
	 				<table border="0"  width="100%">
						<tr>
	                    	<td colspan="9">
	                    		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea">
	                        		<tr>
		                            	<td width="44%" height="22" align="left">
			                            	<button type="button" id="btnInsertarAsignacion" name="btnInsertarAsignacion" rel="#divFlotante13" onclick="abrirFrom(this,'frmSeguimiento','tdFlotanteTitulo13', '', 'tblListAsignacion');" title="Asignacion vendedor"><img src="../img/iconos/ico_agregar.gif"/></button>
		                            	<td width="56%" align="left">Asignaci&oacute;n de vendedor</td>
	                          		</tr>
	                       	 	</table>
	                       	 </td>
						</tr>
                      	<tr class="tituloColumna">
                            <td width="5%" align="center" class="celda_punteada">Id</td>
                            <td width="30%" align="center" class="celda_punteada">Nombre Vendedor</td>
                            <td width="38%" align="center" class="celda_punteada">Cargo</td>
                            <td width="38%" align="center" class="celda_punteada">Departamento</td>
                     	</tr>
	                    <tr id="trItmPieAsignacion"></tr>
	                    <tr id="trMsjError2">
	                    	<td colspan="5">
								<table cellpadding="0" cellspacing="0" colpsan="5" class="divMsjError" width="100%">
									<tr>
										<td align="center">No se ha asignado un Vendedor</td>
									</tr>
								</table>
							</td>
						</tr>
	                </table>
				</form>          
			</td>
		</tr>
		<tr>
            <td align="left">
	          	<form name="frm_agregar_paq" id="frm_agregar_paq" style="margin:0">
	 				<table border="0"  width="100%">
						<tr>
	                    	<td colspan="9">
	                    		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea">
	                        		<tr>
		                            	<td width="44%" height="22" align="left">
			                            	<button type="button" id="btnIngDealer" name="btnIngDealer" rel="#divFlotante14" onclick="abrirFrom(this,'frmSeguimiento','tdFlotanteTitulo14', '', 'tblListIngreso');" title="Ingreso al Dealer"><img src="../img/iconos/ico_agregar.gif"/></button>
		                            	<td width="56%" align="left">Ingreso al Dealer</td>
	                          		</tr>
	                       	 	</table>
	                       	 </td>
						</tr>
                      	<tr class="tituloColumna">
                            <td width="8%" align="center" class="celda_punteada">C&oacute;digo</td>
                            <td width="30%" align="center" class="celda_punteada">Tipo de Ingreso</td>
                            <td width="14%" align="center" class="celda_punteada">Color identificaci&oacute;n</td>
                            <td style="text-align:center; width:20px; display:none" id="tdPaqAprob" class="color_column_aprobacion_item"><input type="checkbox" id="cbxItmPaqAprob" onclick="selecAllChecks(this.checked,this.id,2); xajax_calcularTotalDcto();" checked="checked"   /></td>
                     	</tr>
	                    <tr id="trItmPieIngreso"></tr>
	                    <tr id="trMsjError3">
	                    	<td colspan="5">
								<table cellpadding="0" cellspacing="0" colpsan="5" class="divMsjError" width="100%">
									<tr>
										<td align="center">No se ha especificado el ingreso al dealer</td>
									</tr>
								</table>
							</td>
						</tr>
	                </table>
				</form>          
			</td>
		</tr>
        <tr>
            <td align="right"><hr>
            	<input type="hidden" name="hddIdPerfilProspecto" id="hddIdPerfilProspecto" readonly="readonly"/>
                <input type="hidden" name="hddIdClienteProspecto" id="hddIdClienteProspecto" readonly="readonly"/>
                <input type="hidden" name="hddIdSeguimiento" id="hddIdSeguimiento" readonly="readonly"/>
                <div >
                	<button type="button" id="btnGuardarProspecto" name="btnGuardarProspecto" onclick="validarFrmSeguimiento();">Guardar</button>
            		<button type="button" id="btnCancelarProspecto" name="btnCancelarProspecto" class="close">Cancelar</button> 
            	</div>
            </td>
        </tr>
        </table>
	</div>
	<button type="hidden" style="display:none" id="abtnValidarSeguimiento" title="btnValidarSeguimiento" rel="#divFlotante15" name="btnValidarSeguimiento" class="modalImg" onclick="abrirFrom(this, 'frmValidarSeguimiento', 'tdFlotanteTitulo15', '', 'tbValidarSeguimiento');"></button>
	<div id="frmDatosAdicional"></div>
	<div id="frmModeloInteres"></div>
	<div id="frmIngresoDealer"></div>
</form>
</div>

<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%"></td></tr></table></div>
    <table width="760" id="tblListEmpresa" >
    	<tr>
        	<td align="right">
            	<form id="frmBusEmpresa" name="frmBusEmpresa" style="margin:0" onsubmit="return false;">
                	<table>
                    	<tr>
                        	<td class="tituloCampo" width="120" align="right">Criterio</td>
                            <td><input id="textCriterio" name="textCriterio" class="inputHabilitado" width="50%" onblur="byId('btnBuscarEmpresa').clik();"/></td>
                        </tr>
                        <tr align="right">
                        	<td colspan="2">
                            	<button id="btnBuscarEmpresa" name="btnBuscarEmpresa" onclick="">Buscar</button>
                                <button id="btnLimpiarEmpresa" name="btnLimpiarEmpresa" onblur="byId('btnBuscarEmpresa').clik();document.forms['frmBusEmpresa'].reset();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr><td id="tdListEmpresa"></td></tr>
        <tr>
            <td align="right"><hr />
            	<button id="btnCerrarEmp" name="btnCerrarEmp" class="close">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<div id="divFlotante3" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle"><table><tr><td id="tdFlotanteTitulo3" width="100%"></td></tr></table></div>
    <table width="760" id="tblListEmpleado" >
    	<tr>
        	<td align="right">
            	<form id="frmBusEmpelado" name="frmBusEmpelado" style="margin:0" onsubmit="return false;">
                	<table>
                    	<tr>
                        	<td class="tituloCampo" width="120" align="right">Criterio</td>
                            <td><input id="textCriterio" name="textCriterio" class="inputHabilitado" width="50%" onblur="byId('btnBuscarEmpleado').clik();"/></td>
                        </tr>
                        <tr align="right">
                        	<td colspan="2">
                            	<button id="btnBuscarEmpleado" name="btnBuscarEmpleado" onclick="">Buscar</button>
                                <button id="btnLimpiarEmpleado" name="btnLimpiarEmpleado" onblur="byId('btnBuscarEmpleado').clik();document.forms['frmBusEmpelado'].reset();">Limpiar</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr><td id="tdListEmpleado"></td></tr>
        <tr>
            <td align="right"><hr />
            	<button id="btnCerrarEmpleado" name="btnCerrarEmpleado" class="close">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<div id="divFlotante5" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo5" class="handle"><table><tr><td id="tdFlotanteTitulo5" width="100%"></td></tr></table></div>
    <table width="100%" id="tblLstCliente"  width="760"> 
        <tr>
            <td>
                <form id="frmBusCliente" name="frmBusCliente" style="margin:0" onsubmit="return false;">
                    <table align="right" border="0">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="120">Tipo de Pago:</td>
                            <td>
                                <select id="lstTipoPago" name="lstTipoPago" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();" style="width:99%">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="no">Contado</option>
                                    <option value="si">CrÃ©dito</option>
                                </select>
                            </td>
                            <td align="right" class="tituloCampo" width="120">Estatus:</td>
                            <td>
                                <select id="lstEstatusBuscar" name="lstEstatusBuscar" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option selected="selected" value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo" width="120">Paga Impuesto:</td>
                            <td>
                                <select id="lstPagaImpuesto" name="lstPagaImpuesto" class="inputHabilitado" onchange="byId('btnBuscarCliente').click();">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="0">No</option>
                                    <option value="1">Si</option>
                                </select>
                            </td>
                             <td align="right" class="tituloCampo">Ver:</td>
                            <td>
                                <select id="lstTipoCuentaCliente" name="lstTipoCuentaCliente">
                                    <option value="-1">[ Seleccione ]</option>
                                    <option value="1">Prospecto</option>
                                    <option  value="3">Prospecto Aprobado</option>
                                   <option value="2">Cliente Sin ProspectaciÃ³n</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Criterio:</td>
                            <td colspan="3"><input type="text" id="txtCriterio" name="txtCriterio" class="inputHabilitado" style="width:80%" onkeyup="byId('btnBuscarCliente').click();"/></td>
                        </tr>
                        <tr align="right">
                            <td colspan="5">                       
                                <button type="button" id="btnBuscarCliente" onclick="xajax_buscarCliente(xajax.getFormValues('frmBusCliente'),xajax.getFormValues('frmSeguimiento'));">
                                Buscar</button>
                                <button type="button" onclick="document.forms['frmBusCliente'].reset(); byId('btnBuscarCliente').click();">
                                Limpiar</button>
                            </td>
                        </tr>
                    </table>
                
                </form>
            &nbsp;</td>
        </tr>
        <tr>
        	<td><div id="divCliente"></div></td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
        	<td align="right"><hr />
            	<button type="button" id="btnCerraCliente" name="btnCerraCliente" class="close"> Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<!--posible cierre-->
<div id="divFlotante6" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo6" class="handle"><table><tr><td id="tdFlotanteTitulo6" width="100%"></td></tr></table></div>
    <table id="tblLstPosibleCierre"  width="760"> 
        <tr align="right">
            <td>
            <form id="frmBusPosibleCierre" name="frmBusPosibleCierre" style="margin:0" onsubmit="return false;"> 
                <table border="0">
                <tr>
                    <td class="tituloCampo" align="right" width="120">Empresa</td>
                    <td>
                        <table cellspacing="0" cellpadding="0">
                        <tr>
                            <td><input type="text" id="textIdEmpresaPosibleCierre" name="textIdEmpresaPosibleCierre" size="6" style="text-align:center;"></td>
                            <td><input type="text" id="textEmpresaPosibleCierre" name="textEmpresaPosibleCierre" readonly="readonly"></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo">Criterio</td>
                    <td>
                        <input id="textCriterioPosibleCierre" name="textCriterioPosibleCierre" class="inputHabilitado" onblur="byId('btnBusPosibleCierre').click();">
                    </td>
                    <td>
                        <input id="textHddIdEmpresa" name="textHddIdEmpresa" type="hidden">
                        <button id="btnBusPosibleCierre" name="btnBusPosibleCierre" onclick="xajax_buscarPosibleCierre(xajax.getFormValues('frmBusPosibleCierre'))">Buscar</button>
                        <button id="btnLimPosibleCierre" name="btnLimPosibleCierre" onclick="document.forms['frmBusPosibleCierre'].reset(); byId('btnBusPosibleCierre').click(); xajax_asignarEmpresa(byId('lstEmpresa').value, 'divFlotante6');">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td>
            	<form id="frmPosibleCierre" name="frmPosibleCierre" style="margin:0" onsubmit="return false;"> 
                	<div id="divfrmPosibleCierre"></div>
                    <input id="hddSeguimientoPosibleCierre" name="hddSeguimientoPosibleCierre" type="hidden" />
                </form>
            </td>
        </tr>
        <tr>
            <td>
           
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td align="right"><hr />
            	<button type="button" id="btnGuargarObservacion" style="display: none;" onclick="validarFrmObservacion();" name="btnGuargarObservacion"> Guardar</button>
                <button type="button" id="btnCerrafrmPosibleCierre" name="btnCerrafrmPosibleCierre" class="close"> Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<div id="divFlotante7" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo7" class="handle">
    	<table><tr><td id="tdFlotanteTitulo7" width="100%"></td></tr></table>
    </div>
    <form id="formAsignarActividadSeg"  name="formAsignarActividadSeg" onsubmit="return false;">
        <table border="0" width="760">
            <tr id="trAsignarActTipo" align="left">
                <td align="right" class="tituloCampo">Tipo de Actividad:</td>
                <td id=""><input name="txtTipoActividad" id="txtTipoActividad" type="text" style="text-align:center" readonly="readonly"/> </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Actividad:</td>
                <td id="tdListActividad">
                	<select id="lstActividadSeg" name="lstActividadSeg">
                    	<option value="">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nombre del Vendedor:</td>
                <td>
                    <input name="textIdEmpVendedor" id="textIdEmpVendedor" type="text" readonly="readonly" style="text-align:center" size="6"/>
                    <input name="nombreVendedor" id="nombreVendedor" type="text" readonly="readonly" style="text-align:center" size="45"/>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Fechas de Asignacion:</td>
                <td><input id="textFechAsignacion" name="textFechAsignacion" type="text" class="inputHabilitado" style="text-align:center"/></td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Hora de Asignacion:</td>
                <td id="tdSelectHora">
                	<select id="listHora" name="listHora">
                    	<option value="">[ Seleccione ]</option>
                    </select>
                </td>
            </tr>
            <tr  align="left">
                <td id="tdNombreCliente" align="right" class="tituloCampo"></td> 
                <td>
                    <input type="text" id="idClienteHidd" name="idClienteHidd" readonly="readonly" style="text-align:center" size="6"/>
                    <input type="text" id="textNombreCliente" name="textNombreCliente" readonly="readonly" style="text-align:center" size="45"/>
                </td>
            </tr>
            <tr id="trTipoFinalizacion" align="left" style="display:none">
                <td  align="right" class="tituloCampo" >Tipo de Finalizacion</td>
                <td>
                <select id='comboxEstadoActAgenda' name='comboxEstadoActAgenda' class="inputHabilitado"> 
                        <option value=''>[ Seleccione ]</option>
                        <option value='0'>No Efectiva</option>
                        <option value='1'>Efectiva</option>
                 </select>
                </td>
            </tr>
            <tr align="left">
                <td align="right" class="tituloCampo">Nota:</td>
                <td>
                <textarea id="textNotaCliente" name="textNotaCliente" cols="40" rows="2" class="inputHabilitado"></textarea>
                </td>
                 </tr>
            <tr>
                <td align="right" colspan="2"><hr>
                	<input name="textHoraAsignacion" id="textHoraAsignacion" type="hidden" readonly="readonly"/>
                    <input name="hddIdSeguimientoAct" id="hddIdSeguimientoAct" type="hidden" value=""/>
                    <input name="hddIdEquipo" id="hddIdEquipo" type="hidden" value=""/>
                    <input name="hddIdIntegrante" id="hddIdIntegrante" type="hidden" value=""/>
                    <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmActSeguimiento();">Guardar</button>
                    <button type="button" id="butCancelarAsignacion" name="butCancelarAsignacion" onclick="byId('btnBuscar').click();" class="close">Cancelar</button>
                </td>
            </tr>
        </table>
    </form>
</div>

<!--posible cierre-->
<div id="divFlotante8" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo8" class="handle"><table><tr><td id="tdFlotanteTitulo8" width="100%"></td></tr></table></div>
    <table id="tblLstPosibleCierreObsv"  width="760"> 
        <tr align="right">
            <td>
                <form id="frmBusPosibleCierreObsv" name="frmBusPosibleCierreObsv" style="margin:0" onsubmit="return false;"> 
                	<div id="divfrmPosibleCierreObsv"></div>
                    <input id="hddSeguimientoPosibleCierre" name="hddSeguimientoPosibleCierre" type="hidden" />
                </form>
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td align="right"><hr />
                <button type="button" id="btnCerrafrmPosibleCierre" name="btnCerrafrmPosibleCierre" class="close"> Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<!--Agregar Notas-->
<div id="divFlotante9" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo9" class="handle"><table><tr><td id="tdFlotanteTitulo9" width="100%"></td></tr></table></div>
    <table id="tblLstNotas"  width="760"> 
        <tr align="right">
            <td>
                <form id="frmBusNotas" name="frmBusNotas" style="margin:0" onsubmit="return false;"> 
                	<div id="divfrmNotas"></div>
                </form>
            </td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
            <td align="right"><hr />
            	<button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFrmNotas();">Guardar</button>
                <button type="button" id="btnCerrarNotas" name="btnCerrarNotas" class="close"> Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<!--Lista de Citas-->
<div id="divFlotante10" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo10" class="handle"><table><tr><td id="tdFlotanteTitulo10" width="100%"></td></tr></table></div>
	<div class="pane" style="max-height:520px; overflow:auto; width:960px;">
    	<table width="100%" id="tblLstCitas"> 
	        <tr>
	            <td>
	                <form id="frmBusCitas" name="frmBusCitas" style="margin:0" onsubmit="return false;">
	                <table border="0" align="right" width="50%">			
		                <tr align="left">
    						<td align="right" class="tituloCampo" width="20%">Empresa:</td>
                            <td id="tdlstEmpresaCitas">&nbsp;</td>
		                 </tr>
                         <tr>
                         	<td align="right" class="tituloCampo" width="120">Vendedor:</td>
                            <td id="tdLstVendedorCitas">&nbsp;</td>
                         </tr>
		                 <tr>
		                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
		                    <td colspan="3">
		                       <input type="text" id="textCriterioCitas" name="textCriterioCitas" class="inputHabilitado" style="width:88%" /> 
		                    </td>
		                    <td align="left">
		                        <button type="button" id="btnBuscarCitas" onclick="xajax_buscarCitas(xajax.getFormValues('frmBusCitas'));">Buscar</button>
								<button type="button" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
		                    </td>
		                </tr>
		                <tr>
		                    <td colspan="4">&nbsp;</td>
		                </tr>
	                </table>
                </form>
            &nbsp;</td>
        </tr>
        <tr>
        	<td><div id="divfrmCitas"></div></td>
        </tr>
        <tr><td>&nbsp;</td></tr>
        <tr>
        	<td align="right"><hr />
            	<button type="button" id="btnCerraCliente" name="btnCerraCliente" class="close"> Cerrar</button>
            </td>
        </tr>
    </table>
    </div>
</div>
<div id="divFlotante11" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo11" class="handle"><table><tr><td id="tdFlotanteTitulo11" width="100%"></td><td><img class="close puntero" id="imgCerrarDivFlotante1" src="../img/iconos/cross.png" title="Cerrar"/></td></tr></table></div>
    
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
    <div id="tblAjusteInventario" style="max-height:520px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>
        	<td>
            	<table width="100%">
                <tr align="left">
                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empresa:</td>
                    <td>
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="txtIdEmpresaTrade" name="txtIdEmpresaTrade" size="6" readonly="readonly" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="txtEmpresaTrade" name="txtEmpresaTrade" readonly="readonly" size="45"/></td>
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
                                    <td><input type="text" id="txtIdCliente" name="txtIdCliente" readonly="readonly" size="6" style="text-align:right;"/></td>
                                    <td style="display:none;">
										<input type="hidden" id="hddIdSeguimientoTrade" name="hddIdSeguimientoTrade" size="6" style="text-align:right;"/>
                                   		<input type="hidden" id="hddIdCliente" name="hddIdCliente"  size="6" style="text-align:right;"/>
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
                                        <button type="button" id="btnListarVehiculos" name="btnListarVehiculos" title="Listar VehÃ­culos"><img src="../img/iconos/help.png"/></button>
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
                    <td id="datosVale" valign="top" width="35%">
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
                                    <!--<option value="3">Nota de CrÃ©dito de CxC</option>-->
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
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. Nota CrÃ©dito:</td>
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
            <fieldset><legend class="legend">Unidad FÃ­sica</legend>
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
                            <td align="right" class="tituloCampo" rowspan="3">DescripciÃ³n:</td>
                            <td rowspan="3"><textarea id="txtDescripcionAjuste" name="txtDescripcionAjuste" cols="20" rows="3"></textarea></td>
                            <td align="right" class="tituloCampo">Marca:</td>
                            <td><input type="text" id="txtMarcaUnidadBasicaAjuste" name="txtMarcaUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">Modelo:</td>
                            <td><input type="text" id="txtModeloUnidadBasicaAjuste" name="txtModeloUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo">VersiÃ³n:</td>
                            <td><input type="text" id="txtVersionUnidadBasicaAjuste" name="txtVersionUnidadBasicaAjuste" readonly="readonly" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>AÃ±o:</td>
                            <td id="tdlstAno"></td>
                            <td align="right" class="tituloCampo"><?php echo $spanPlaca; ?>:</td>
                            <td><input type="text" id="txtPlacaAjuste" name="txtPlacaAjuste" size="24"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>CondiciÃ³n:</td>
                            <td id="tdlstCondicion"></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>FabricaciÃ³n:</td>
                            <td><input type="text" id="txtFechaFabricacionAjuste" name="txtFechaFabricacionAjuste" autocomplete="off" size="10" style="text-align:center"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span><?php echo $spanKilometraje; ?>:</td>
                            <td><input type="text" id="txtKilometrajeAjuste" name="txtKilometrajeAjuste" onkeypress="return validarSoloNumeros(event);" size="24" style="text-align:right"/></td>
                            <td align="right" class="tituloCampo">ExpiraciÃ³n Marbete:</td>
                            <td><input type="text" id="txtFechaExpiracionMarbeteAjuste" name="txtFechaExpiracionMarbeteAjuste" readonly="readonly" size="10" style="text-align:center"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                    <td valign="top" width="32%">
                    	<table width="100%">
                        <tr align="left">
                            <td align="right" class="tituloCampo" width="40%"><span class="textoRojoNegrita">*</span>AlmacÃ©n:</td>
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
                            <td class="tituloCampo" width="45%"><span class="textoRojoNegrita">*</span>Allowance:</td>
                            <td width="55%">
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtAllowance" name="txtAllowance" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="Monto por el cual serÃ¡ recibido" /></td>
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
                            <td class="tituloCampo"><span class="textoRojoNegrita">*</span>CrÃ©dito Neto:</td>
                            <td>
                            	<table cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                	<td colspan="2"><input type="text" id="txtCreditoNeto" name="txtCreditoNeto" maxlength="12" onkeypress="return validarSoloNumerosReales(event);" readonly="readonly" style="text-align:right"/></td>
                                	<td><img src="../img/iconos/information.png" style="margin-bottom:-3px;" title="CrÃ©dito Neto" /></td>
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
                                        <input type="text" id="txtSerialCarroceriaAjuste" name="txtSerialCarroceriaAjuste"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/information.png" title="Formato Ej.: <?php echo utf8_encode($titleFormatoCarroceria); ?>"/>
                                    </div>
                                    </td>
                                </tr>
                                <tr id="trAsignarUnidadFisica">
	                                <td><label><input type="checkbox" id="cbxAsignarUnidadFisica" name="cbxAsignarUnidadFisica" onclick="xajax_buscarCarroceria(xajax.getFormValues('frmAjusteInventario'));" value="1"/>Asignar unidad fÃ­sica anteriormente vendida</label></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span><?php echo $spanSerialMotor; ?>:</td>
                            <td width="30%"><input type="text" id="txtSerialMotorAjuste" name="txtSerialMotorAjuste"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Nro. VehÃ­culo:</td>
                            <td><input type="text" id="txtNumeroVehiculoAjuste" name="txtNumeroVehiculoAjuste"/></td>
                        </tr>
                        <tr align="left">
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro LegalizaciÃ³n:</td>
                            <td><input type="text" id="txtRegistroLegalizacionAjuste" name="txtRegistroLegalizacionAjuste"/></td>
                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Registro Federal:</td>
                            <td><input type="text" id="txtRegistroFederalAjuste" name="txtRegistroFederalAjuste"/></td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                	<td>
                    <fieldset><legend class="legend">Observaci&oacute;n</legend>
                        <table border="0" width="100%">
	                        <tr align="left">
	                            <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Observaci&oacute;n:</td>
	                            <td colspan="4" rowspan="2"><textarea id="txtObservacionTrade" name="txtObservacionTrade" class="inputHabilitado" cols="60" rows="2"></textarea></td>
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
            <td align="right"><hr>
            	<input type="hidden" id="hddIdTradeInAjusteInventario" name="hddIdTradeInAjusteInventario"/>
                <button type="button" id="btnGuardarAjusteInventario" name="btnGuardarAjusteInventario" onclick="validarFrmAjusteInventario();" style="display:none;">Guardar</button>
                <button type="button" id="btnEditatTradein" name="btnEditatTradein" onclick="validarFrmAjusteInventario();" style="display:none;">Editar</button>
                <button type="button" id="btnCancelarAjusteInventario" name="btnCancelarAjusteInventario" class="close">Cancelar</button>
            </td>
        </tr>
        </table>
	</div>
</form>
</div>
<!--Agregar Log Up-->
	<div id="divFlotante12" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
		<div id="divFlotanteTitulo12" class="handle"><table><tr><td id="tdFlotanteTitulo12" width="100%"></td></tr></table></div>
       	<form id="frmSegui" name="frmSegui" style="margin:0" onsubmit="return false;">
       	<table border="0" width="100%" id="ListLogup" id="tblListModelInteres">
       		<tr><td height="12px" align="right" colspan="4" id="tituloLogUp" class="tituloPaginaCrm">Datos Adicionales</td></tr>
       		<tr>
       			<td>
					<div class="men">
						<a href="#menu"></a>
					</div>
					<nav id="menu" class="mm-menu mm-offcanvas mm-current">
						<div class="mm-panels">
						  	<div class="mm-panel mm-opened mm-current" id="mm-0">
					    
					    	<ul class="mm-listview">
								<li id="adicional" onclick="menuBar(this.id);" class="current"><a href="#">Datos Adicional</a></li>
								<li id="interes" onclick="menuBar(this.id);"><a href="#">Modelo Interes</a></li>
								<li id="entrevista" onclick="menuBar(this.id);"><a href="#">Entrevistas</a></li>
								<li id="observacion" onclick="menuBar(this.id);"><a href="#">Observaci&oacute;n</a></li>
							</ul>
						</div>
					</nav>
       			</td>
       			<td style="width: 810px;">
       				<!-- DATOS ADICIONALES-->
		       		<table border="0" width="100%" id="adicional" class="log_up">
		       			<div>
				       	<tr align="left">
				            <td align="right" class="tituloCampo">CompaÃ±ia:</td>
				           	<td><input type="text" style='width: 92%;' name="txtCompania" id="txtCompania" maxlength="50"/></td>
				            <td align="right" class="tituloCampo">Puesto:</td>
				            <td id="tdLstPuesto" align="left">
				                <select>
				                	<option value="">[ Seleccione ]</option>
				            	</select>
				            </td>
				            <td align="right" class="tituloCampo">TÃ­tulo:</td>
				            <td id="tdLstTitulo" align="left">
				                <select>
				                	<option value="">[ Seleccione ]</option>
				            	</select>
				        	</td>
				        </tr>
				        <tr align="left">
				          <td align="right" class="tituloCampo">Nivel de Influencia:</td>
				          <td id="tdLstNivelInfluencia">
				                <select>
				                	<option value="">[ Seleccione ]</option>
								</select>
				           </td>
				           <td align="right" class="tituloCampo">Sector:</td>
				           <td id="tdLstSector">
				                <select>
				                	<option value="">[ Seleccione ]</option>
				            	</select>
				            </td>
				            <td align="right" class="tituloCampo">Estatus:</td>
				         	<td id="td_select_estatus"></td>
				         </tr>
				         <tr align="left">
				            <td align="right" class="tituloCampo" width="14%">Estado Civil:</td>
				            <td id="tdlstEstadoCivil" width="19%">
				                <select size="1" name="lstEstadoCivil" id="lstEstadoCivil">
				                	<option value="-1">[ Seleccione ]</option>
				            	</select>
				            </td>
				            <td align="right" class="tituloCampo" width="14%">Sexo:</td>
				            <td width="19%">
				                <input type="radio" name="rdbSexo" id="rdbSexoM" class="rdbSexoM" value="M"/>M
				            	<input type="radio" name="rdbSexo" id="rdbSexoF" class="rdbSexoF" value="F"/>F
				          	</td>
				          </tr>
				          <tr align="left">
				               <td align="right" class="tituloCampo">Clase Social:</td>
				               <td>
				                    <select style='width: 94%;' name="lstNivelSocial" id="lstNivelSocial" class="inputHabilitado lstNivelSocial">
				                        <option value="">[ Seleccione ]</option>
				                        <option value="3">Alta</option>
				                        <option value="2">Media</option>
				                    	<option value="1">Baja</option>
				                	</select>
				                </td>
				                <td align="right" class="tituloCampo" rowspan="2">ObservaciÃ³n:</td>
				            	<td colspan="4" rowspan="2"><textarea id="txtObservacion" name="txtObservacion" class="inputHabilitado txtObservacion" cols="45" rows="2"></textarea></td>
				            </tr>
				            <tr align="left">
				                <td align="right" class="tituloCampo">Motivo de Rechazo:</td>
				                <td id="tdLstMotivoRechazo">
				                    <select>
				                    	<option value="">[ Seleccione ]</option>
				                	</select>
				           		</td>
				            </tr>
				            <tr align="left">
				               <td align="right" class="tituloCampo">Posibilidad de cierre:</td>
				               <td colspan="" id="tdLstPosibilidadCierre">
				                    <select>
				                    	<option value="">[ Seleccione ]</option>
				               		</select>
				                </td>
				                <td colspan="4">
				                	<img id="imgPosibleCierrePerfil" width="80" height="80"/>
				            	</td>
				            </tr>
							<tr>
						        <td align="right" colspan="6"><hr>
						        	<button type="button" id="btnAsigModelo" name="btnAsigModelo" onclick="xajax_guardarLogup(xajax.getFormValues('frmSegui'));">Aceptar</button>
						    		<button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
						    	</td>
							</tr>
						</table>
						
					<!-- MODELO DE INTERES-->
	                <table cellpadding="0" border="0" width="100%" id="interes" class="log_up" style="display:none;">
	                	<tr valign="top">
	                		<td  style="height:236px" valign="top">
			                	<table cellpadding="0" border="0" width="100%">
			                        <tr align="left" valign="top">
			                            <td colspan="6" valign="top">
			                                <a class="modalImg" id="aNuevoModelo" rel="#divFlotante4" onclick="abrirFrom(this,'frmBuscarModelo','tdFlotanteTitulo4', '', 'tblListModelInteres')">
			                                    <button id="btnAgregarModelo" name="btnAgregarModelo" type="button">
			                                        <table align="center" cellpadding="0" cellspacing="0">
			                                            <tr>
			                                                <td>&nbsp;</td>
			                                                <td><img src="../img/iconos/add.png"/></td>
			                                                <td>&nbsp;</td>
			                                                <td>Agregar</td>
			                                            </tr>
			                                        </table>
			                                    </button>
			                                </a>
			                                <button id="btnEliminarModelo" name="btnEliminarModelo" onclick="xajax_eliminarModelo(getModelChecked());"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/delete.png"/></td><td>&nbsp;</td><td>Quitar</td></tr></table></button>
			                            </td>
			                        </tr>
			                        <tr align="center" class="tituloColumna">
			                            <td></td>
			                            <td width="40%">Modelo</td>
			                            <td width="15%">Precio</td>
			                            <td width="15%">Medio</td>
			                            <td width="15%">Niv. InterÃ©s</td>
			                            <td width="15%">Plan Pago</td>
			                        </tr>
			                        <tr id="trItmPieModeloInteres"></tr>
			                    </table>
			                    <input type="hidden" id="hddObj" name="hddObj" readonly="readonly"/>
		                    </td>
	                    </tr>
                    	<tr>
					        <td align="right" colspan="6"><hr>
					        	<button type="button" id="btnAsigModelo" name="btnAsigModelo" onclick="xajax_guardarLogup(xajax.getFormValues('frmSegui'));">Aceptar</button>
						    	<button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
					    	</td>
				    	</tr>
	              	</table>
	
	         		<!-- ENTREVISTA-->
	                <table border="0" width="100%" id="entrevista" class="log_up" style="display:none;">
	                	<tr>
	                		<td style="height:236px">
			                	<table border="0" width="100%">
			                        <tr align="left">
			                            <td align="right" class="tituloCampo" width="16%">Ultima AtenciÃ³n:</td>
			                            <td width="17%"><input type="text" id="txtFechaUltAtencion" name="txtFechaUltAtencion" autocomplete="off" size="10" style="text-align:center"/></td>
			                            <td align="right" class="tituloCampo" width="16%">Ultima Entrevista:</td>
			                            <td width="17%"><input type="text" id="txtFechaUltEntrevista" name="txtFechaUltEntrevista" autocomplete="off" size="10" style="text-align:center"/></td>
			                            <td align="right" class="tituloCampo" width="16%">PrÃ³xima Entrevista:</td>
			                            <td width="18%"><input type="text" id="txtFechaProxEntrevista" name="txtFechaProxEntrevista" autocomplete="off" size="10" style="text-align:center"/></td>
			                        </tr>
			                    </table>
			                </td>
			        	</tr>
        				<tr>
					        <td align="right" colspan="6"><hr>
					        	<button type="button" id="btnAsigModelo" name="btnAsigModelo" onclick="xajax_guardarLogup(xajax.getFormValues('frmSegui'));">Aceptar</button>
						    	<button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
					    	</td>
				    	</tr>
	                </table>
	                    
	                <!-- SEGUIMIENTO-->
	                <table border="0" width="100%" id="observacion" class="log_up" style="display:none;">
		            	<tr>
	                		<td style="height:236px">
			                	<table border="0" width="100%">
			                    	<tr align="left">
			                        	<td align="right" class="tituloCampo">Observacion:</td>
			                            <td colspan="4">
			                            	<textarea id="textAreaObservacion" name="textAreaObservacion" class="inputHabilitado" rows="2" cols="80" ></textarea>
			                            </td>
			                        </tr>
			                     </table>
		                 	</td>
	                     </tr>
                     	 <tr>
							<td align="right" colspan="6"><hr>
					        	<button type="button" id="btnAsigModelo" name="btnAsigModelo" onclick="xajax_guardarLogup(xajax.getFormValues('frmSegui'));">Aceptar</button>
					    		<button type="button" id="btnCancelarModelo" name="btnCancelarModelo" class="close">Cancelar</button>
						    </td>
						  </tr>
		        	</table> 
	            </td>
            </tr> 
            </table>
		    <!-- LISTA DE MODELOS-->
		    <div id="divFlotante4"  style="cursor:auto; left:0px; top:0px; z-index:0;">
				<div id="tblModelo" style="max-height:720px; overflow:auto; width:960px;">
					<table border="0" width="100%" style="display:none; overflow:auto; " id="ListModelInteres" id="tblListModelInteres">
				        <tr>
				            <td>
					            <form id="frmBuscarModelo" name="frmBuscarModelo" style="margin:0" onsubmit="return false;">
					                <table align="right" border="0">
					                    <tr align="left">
					                        <td align="right" class="tituloCampo" width="100">Empresa:</td>
					                        <td>
					                            <input type="text" id="txtIdEmpresaBuscarModelo" name="txtIdEmpresaBuscarModelo" size="5" readonly="readonly"/>
					                            <input type="text" id="txtEmpresaBuscarModelo" name="txtEmpresaBuscarModelo" size="45" readonly="readonly"/>
					                        </td>
					                    </tr>
					                    <tr align="left"> 
					                        <td align="right" class="tituloCampo" width="100">Criterio:</td>
					                        <td><input type="text" id="txtCriterioBuscarModelo" name="txtCriterioBuscarModelo" class="inputHabilitado" size="60" onkeyup="byId('btnBuscarModelo').click();"/></td>
					                    </tr>
					                    <tr align="right">   
					                        <td colspan="2">
					                            <button type="button" id="btnBuscarModelo" name="btnBuscarModelo" onclick="xajax_buscarModelo(xajax.getFormValues('frmBuscarModelo'), xajax.getFormValues('frmSeguimiento'));">Buscar</button>
					                            <button type="button" onclick="document.forms['frmBuscarModelo'].reset(); byId('btnBuscarModelo').click();">Limpiar</button>
					                        </td>
					                    </tr>
					                </table>
					            </form>
					            </td>
					        </tr>
					        <tr>
					            <td>
					                <div id="divListaModelo" style="width:100%"></div>
					            </td>
					        </tr>
					        <tr>
					            <td>
					            <form id="frmModelo" name="frmModelo" style="margin:0" onsubmit="return false;">
					                <table width="100%">
						                <tr align="left">
						                    <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Unidad BÃ¡sica:</td>
						                    <td colspan="3">
						                        <input type="text" id="txtUnidadBasica" name="txtUnidadBasica" readonly="readonly" size="65"/>
						                    </td>
						                    <td align="right" class="tituloCampo">
						                        <?php echo $spanPrecioUnitario; ?>:
						                        <br><span class="textoNegrita_10px">(Sin Incluir Impuestos)</span>
						                    </td>
						                    <td><input type="text" id="txtPrecioUnidadBasica" name="txtPrecioUnidadBasica"  onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" class="inputHabilitado" size="16" style="text-align:right"/></td>
						                </tr>
						                <tr align="left">
						                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Fuente de InformaciÃ³n:</td>
						                    <td id="tdlstMedio" width="18%"></td>
						                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Nivel de Interes:</td>
						                    <td width="18%">
						                        <select id="lstNivelInteres" name="lstNivelInteres">
						                            <option value="-1">[ Seleccione ]</option>
						                            <option value="3">Alto</option>
						                            <option value="2">Medio</option>
						                            <option value="1">Bajo</option>
						                        </select>
						                    </td>
						                    <td align="right" class="tituloCampo" width="16%"><span class="textoRojoNegrita">*</span>Plan de Pago:</td>
						                    <td id="tdlstPlanPago" width="16%"></td>
						                </tr>
						                <tr>
						                    <td align="right" colspan="6"><hr>
						                        <input type="hidden" id="hddIdUnidadBasica" name="hddIdUnidadBasica" readonly="readonly"/>
						                        <button type="button" id="btnGuardarModelo" name="btnGuardarModelo" onclick="validarFrmModelo();">Guardar</button>
						                        <button type="button" id="btnCancelarModelo" name="btnCancelarModelo" onclick="$('#ListLogup').show();$('#ListModelInteres').hide();">Cancelar</button>
						                    </td>
						                </tr>
					                </table>
					            </form>
					         </td>
					    </tr>
					</table>
				</div>
			</div>
		</form>
	</div>
<!-- ASIGNACION-->
	<div id="divFlotante13" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; max-height:720px; overflow:auto; width:960px;">
		<div id="divFlotanteTitulo13" class="handle"><table><tr><td id="tdFlotanteTitulo13" width="100%"></td></tr></table></div>
       			<form id="frmAsigVendedor" name="frmAsigVendedor" style="margin:0" onsubmit="return false;">
       				<table border="0" width="100%" id="ListLogup" id="tblListAsignacion">
                    	<table border="0" width="100%">
                   		 	<tr><td height="6px"> </td></tr>
                            <tr align="left">
                                <td>
                                	<table border="0" width="100%">
                                    	<tr>
                                        	 <td id="tdTipoEquipo" align="right" class="tituloCampo" width="120"></td>
                                             <td id="tdLstEquipo" align="left"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr><td height="6px"> </td></tr>
                            <tr>
                            	<td>
                                    <fieldset>
                                        <legend class="legend" >Integrante Del Equipo</legend>
                                        <table border="0" width="100%">
                                            <tr align="center" class="tituloColumna">
                                                <td width=""></td>
                                                <td width="">id</td>
                                                <td width="">Nombre Vendedor</td>
                                                <td width="">Cargo</td>
                                                <td width="">Departamento</td>
                                                <td width=""></td>
                                            </tr>
                                            <tr id="trItmIntegrante"></tr>
                                        </table>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr> 
                            	<td colspan="6">
                                    <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%"> 
                                        <tr>
                                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                            <td align="center">
                                                <table>
                                                    <tr>
                                                        <td><img src="../img/iconos/user_suit.png" /></td><td>Jefe de Equipo</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
								<td align="right" colspan="6"><hr>
							    	<button type="button" id="btnAsigVendedor" name="btnAsigVendedor" onclick="xajax_listaVendedorTemp(xajax.getFormValues('frmAsigVendedor'));">Aceptar</button>
							 		<button type="button" id="btnCancelarAsig" name="btnCancelarAsig" class="close">Cancelar</button>
							    </td>
						  </tr>
                	</table>
    		</table>
    	 </form>
	</div>
	<!-- INGRESO DEALER -->
	<div id="divFlotante14" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; max-height:720px; overflow:auto; width:960px;">
		<div id="divFlotanteTitulo14" class="handle"><table><tr><td id="tdFlotanteTitulo14" width="100%"></td></tr></table></div>
       	<form id="frmIngDealer" name="frmIngDealer" style="margin:0" onsubmit="return false;">
        	<table border="0" width="100%">
            	<tr><td height="6px"> </td></tr>
            	<tr>
            		<td id="divFormasDealer"></td>
            	</tr>
       			<tr><td height="8px"> </td></tr>
       			<tr>
					<td align="right" colspan="6"><hr>
				    	<button type="button" id="btnAsigDealer" name="btnAsigDealer" onclick="xajax_listaAsigDealerTemp(xajax.getFormValues('frmIngDealer'));">Aceptar</button>
					 	<button type="button" id="btnCancelarAsigDealer" name="btnCancelarAsigDealer" class="close">Cancelar</button>
					</td>
				</tr>
       		</table>
    	 </form>
	</div>
	<!-- Verificar Seguimiento -->
	<div id="divFlotante15" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; max-height:720px; overflow:auto; width:450px;">
       	<div id="divFlotanteTitulo15" class="divMsjAlerta2"><table><tr><td id="tdFlotanteTitulo15" width="100%"></td></tr></table></div>
       	<form id="frmValidarSeguimiento" name="frmValidarSeguimiento" style="margin:0" onsubmit="return false;">
        	<table border="0" width="100%">
        		<tr><td height="8px"> </td></tr>
            	<tr>
            		<td align="center"><img class="puntero" src="../img/iconos/alert.png" title="Advertencia"/></td>
            		<td style="font-weight: bold;">
            			Desea continuar el seguimiento de <span id="nbCliente"></span> que est&aacute; sin concluir?
            		</td>
            	</tr>
            	<tr><td height="8px"></td></tr>
       			<tr>
					<td align="center" colspan="6"><hr>
						<input type="hidden" id="idCliente" name="idCliente"></input>
				    	<button type="button" id="btnOpcSi" name="btnOpcSi" onclick="xajax_cargarDatos('', $('#idCliente').val());">Si</button>
					 	<button type="button" id="btnOpcNo" name="btnOpcNo" onclick="xajax_cargarDatos('', $('#idCliente').val(), true);">No</button>
					 	<button type="hidden" style="display:none;" id="btnValidarSeguimiento" name="btnValidarSeguimiento" class="close"></button>
					</td>
				</tr>
       		</table>
    	 </form>
	</div>
<script> 
window.onload = function(){
	jQuery(function($){
		$("#txtFechaNacimiento").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaUltAtencion").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaUltEntrevista").maskInput("99-99-9999",{placeholder:" "});
		$("#txtFechaProxEntrevista").maskInput("99-99-9999",{placeholder:" "});
		$("#textDesdeCita").maskInput("99-99-9999",{placeholder:" "});
		$("#textHastaCita").maskInput("99-99-9999",{placeholder:" "});
		$("#textHastaCreacion").maskInput("99-99-9999",{placeholder:" "});
		$("#textDesdeCreacion").maskInput("99-99-9999",{placeholder:" "});
		$("#textFechAsignacion").maskInput("99-99-9999",{placeholder:" "});
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaNacimiento",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});//textDesdeCreacion
	new JsDatePick({
		useMode:2,
		target:"txtFechaUltAtencion",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaUltEntrevista",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaProxEntrevista",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});
	new JsDatePick({
		useMode:2,
		target:"textDesdeCita",
		cellColorScheme :"bananasplit",
		dateFormat:"%d-%m-%Y"
	});
	new JsDatePick({
		useMode:2,
		target:"textHastaCita",
		cellColorScheme :"bananasplit",
		dateFormat:"%d-%m-%Y"
	});
	a = new JsDatePick({
		useMode:2,
		target:"textHastaCreacion",
		cellColorScheme :"bananasplit",
		dateFormat:"%d-%m-%Y"
	});
	b = new JsDatePick({
		useMode:2,
		target:"textDesdeCreacion",
		cellColorScheme :"bananasplit",
		dateFormat:"%d-%m-%Y"
	});
	c = new JsDatePick({
		useMode:2,
		target:"textFechAsignacion",
		dateFormat:"%d-%m-%Y",
		cellColorScheme :"bananasplit",
	});
	
	a.addOnSelectedDelegate(function(){
		byId('btnBuscar').click();
	});
	b.addOnSelectedDelegate(function(){
		byId('btnBuscar').click();		
	});
	
	c.addOnSelectedDelegate(function(){
		xajax_cargarListHora(xajax.getFormValues('formAsignarActividadSeg'));
	});
}
xajax_cargarLstPosibilidadCierre("", "tdLstPosibilidadCierreBus");
xajax_cargaLstVendedor('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>','onchange=\"xajax_cargaLstVendedor(this.value);xajax_cargarLstPosibilidadCierre(\'\', \'tdLstPosibilidadCierreBus\',this.value); byId(\'btnBuscar\').click();\"');
xajax_listaEmpleado(0,"","","");
xajax_lstSeguimiento(0,"seguimiento.id_seguimiento","ASC","<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>||||"+"<?php echo date("Y-m-d"); ?>"+"|"+"<?php echo date("Y-m-d"); ?>");
xajax_formaIngreso(<?php echo $_SESSION['idEmpleadoSysGts']; ?>);

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

$(document).ready(function(){
	
	$("ul.tabs li a").click(function(event){
		var btn = $("ul.tabs li a.current").attr("id");

		switch (btn) { 
			case '1': 
				$("#1").attr({name:'2', id:'2'});
				break;
			case '2': 
				$("#2").attr({name:'3', id:'3'});
				break;
			case '3': 
				$("#3").attr({name:'4', id:'4'});
				break;
			case '4':
				$("#4").attr({name:'5', id:'5'});
				break;
			case '5': 
				$("#5").attr({name:'6', id:'6'});
				break;
		}
	});
	
	$("#rdProspecto").addClass('selected');
	$("#1").hide();
	$("#2").show();

	
	$("#rdProspecto").click(function(){
	    $("#rdCliente").removeClass('selected');
	    $(this).addClass('selected');
	});
	$("#rdCliente").click(function(){
	    $("#rdProspecto").removeClass('selected');
	    $(this).addClass('selected');
	});
});

var theHandle = document.getElementById("divFlotanteTitulo");
var theRoot   = document.getElementById("divFlotante");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo2");
var theRoot   = document.getElementById("divFlotante2");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo3");
var theRoot   = document.getElementById("divFlotante3");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo5");
var theRoot   = document.getElementById("divFlotante5");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo6");
var theRoot   = document.getElementById("divFlotante6");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo7");
var theRoot   = document.getElementById("divFlotante7");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo8");
var theRoot   = document.getElementById("divFlotante8");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo12");
var theRoot   = document.getElementById("divFlotante12");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo13");
var theRoot   = document.getElementById("divFlotante13");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo14");
var theRoot   = document.getElementById("divFlotante14");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo15");
var theRoot   = document.getElementById("divFlotante15");
Drag.init(theHandle, theRoot);
</script>