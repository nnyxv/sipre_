<?php
require_once("../connections/conex.php");
	
session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_seguimiento_list"))) 
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";

/*clase xajax*/
require ('../controladores/xajax/xajax_core/xajax.inc.php');

//Instanciando el objeto xajax
$xajax = new xajax();

//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');//Configuranto la ruta del manejador de script
$xajax->configure( 'defaultMode', 'synchronous' ); 

//contiene todas las funciones xajax
include("controladores/ac_crm_seguimiento_list.php"); 
include("../controladores/ac_iv_general.php");

$xajax->processRequest();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Control de Tráfico</title>
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
    
    <link type="text/css" rel="stylesheet" href="../style/jquery.mmenu.all.css" />
	
	<script type="text/javascript">
	function abrirFrom(idObj, forms, IdObjTitulo, valor, valor2, valor3){ 
		if (IdObjTitulo == "tdFlotanteTitulo"
		|| IdObjTitulo == "tdFlotanteTitulo10"
		|| IdObjTitulo == "tdFlotanteTitulo11") {
			$('#btnsGuardarSeguimiento').hide();	
			document.forms[forms].reset();
		}

		titulo = '';

		if(IdObjTitulo == "tdFlotanteTitulo"){
			xajax_listaTipoContacto();

			RecorrerForm("frmSeguimiento","text","class","inputHabilitado",["txtEmpresa","txtNombreEmpleado"]);
			xajax_asignarEmpresa(byId('lstEmpresa').value);
			xajax_asignarEmpleado('<?php echo $_SESSION['idEmpleadoSysGts']; ?>', byId('txtIdEmpresa').value);
			xajax_formModoIngreso(xajax.getFormValues('frmBuscar'));

			$('#tituloLogUp').text('Lista de Vendedores');

			//OCULTAR TODAS LAS VISTAS MENOS LA PRIMERA
			$("#tblListVendedor").show();
			$("#tblModoIngreso").hide();
			$("#tblListProspecto").hide();
			$("#tblListdatosAdicionales").hide();
			$('#datosProspecto').hide();
			$('#datosProsClien').show();
			$('#divBuscarProspecto').show();

			if(valor == 0) {
				$("#rdCliente").removeClass('selected');
				$("#rdProspecto").addClass('selected');

				byId('abtnEditar').value = 0;

				//byId('lstEquipoTemp').value = '';
				byId('hddIdPerfilProspecto').value = '';
				byId('hddIdClienteProspecto').value = '';
				byId('hddIdSeguimiento').value = '';
				byId('rdCliente').disabled = false;
				byId('rdProspecto').disabled = false;
				byId('rdProspecto').checked = true;
				byId('rdCliente').onclick = function() {
					abrirFrom(this,'frmBusCliente','tdFlotanteTitulo2', 3, 'tblLstCliente');
				}
				byId('rdProspecto').onclick = function() {
					abrirFrom(this,'frmBusCliente','tdFlotanteTitulo2', 1, 'tblLstCliente');
				}	
				var titulo = "Agregar Control de Trafico";

				xajax_cargarDatos('', '', false, xajax.getFormValues('frmSeguimiento'));
				xajax_cargaLstTipoEquipo();
				xajax_insertarIntegrante();
			}else{
				var titulo = "Editar Control de Trafico";
				byId('abtnEditar').value = 1;

				xajax_cargarDatos(valor, '', false, xajax.getFormValues('frmSeguimiento'));

				if(byId('rdProspecto').value == "activo"){
					$("#rdCliente").removeClass('selected');
					$("#rdProspecto").addClass('selected');
				} else{
					$("#rdProspecto").removeClass('selected');
					$("#rdCliente").addClass('selected');
				}

				$('#divBuscarProspecto').hide();
				byId('rdCliente').disabled = true;
				byId('rdProspecto').disabled = true;
			}
		} else if (IdObjTitulo == "tdFlotanteTitulo2"){
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
					'txtUrbanizacionPostalProspecto','txtCallePostalProspecto',
					'txtCasaPostalProspecto','txtMunicipioPostalProspecto',
					'txtCiudadPostalProspecto','txtEstadoPostalProspecto',
					'txtOtroTelefonoProspecto','txtUrbanizacionComp',
					'txtCalleComp','txtCasaComp',
					'txtMunicipioComp','txtEstadoComp',
					'txtTelefonoComp','txtOtroTelefonoComp',
					'txtEmailComp', 'txtValNombreProspecto',
					'txtValApellidoProspecto', 'txtValTelefonoProspecto');
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
					'txtUrbanizacionPostalProspecto','txtCallePostalProspecto',
					'txtCasaPostalProspecto','txtMunicipioPostalProspecto',
					'txtCiudadPostalProspecto','txtEstadoPostalProspecto',
					'txtCalleComp','txtCasaComp',
					'txtMunicipioComp','txtEstadoComp',
					'txtTelefonoComp','txtOtroTelefonoComp',
					'txtEmailComp', 'txtValNombreProspecto',
					'txtValApellidoProspecto', 'txtValTelefonoProspecto');
				RecorrerForm('frmSeguimiento','text','readOnly',true,arrayElement2);
				
			} else if (valor == 1) {//PROSPECTO
				$('.remover').remove();	
				titulo = "Prospecto";
				
				RecorrerForm('frmSeguimiento','text','class','inputHabilitado',['txtEmpresa','txtNombreEmpleado']);
				RecorrerForm('frmSeguimiento','text','readOnly',false,['txtEmpresa','txtNombreEmpleado']);
			}
		} else if (IdObjTitulo == "tdFlotanteTitulo3"){
			titulo = "Continuar Seguimiento";
		} else if (IdObjTitulo == "tdFlotanteTitulo4"){
			titulo = "Empresa";
			xajax_listaEmpresa(0,"","","");
		} else if (IdObjTitulo == "tdFlotanteTitulo5"){
			titulo = "Empleado";
			xajax_listaEmpleado(0,"","","");
		} else if (IdObjTitulo == "tdFlotanteTitulo6"){
			titulo = "Coincidencias de Clientes";
		} else if (IdObjTitulo == "tdFlotanteTitulo7"){
			titulo = "No hay coincidencias";
		} else if (IdObjTitulo == "tdFlotanteTitulo8"){
			titulo = "Modelo de Interes";
			byId('txtIdEmpresaBuscarModelo').value =  byId('txtIdEmpresa').value;
			byId('txtEmpresaBuscarModelo').value =  byId('txtEmpresa').value;
			byId('btnBuscarModelo').click();
			$('#ListModelInteres').show();
		} else if (IdObjTitulo == "tdFlotanteTitulo9"){
			titulo = "Registro de Notas/Seguimiento";
			
			xajax_formNota(valor);
			xajax_listaNota(0,'','',valor);
		} else if (IdObjTitulo == "tdFlotanteTitulo10"){
			titulo = "Asignacion de Actividad";
			byId('textIdEmpVendedor').className = 'inputInicial';
			byId('textFechAsignacion').className = 'inputHabilitado';
			
			xajax_cargarDtosAsignacion(valor, valor2);
			
			document.getElementById('textFechAsignacion').focus();
			document.getElementById('textFechAsignacion').select();
		} else if (IdObjTitulo == "tdFlotanteTitulo11"){
			titulo = "Ingreso de veh&iacute;culo Trade-In";

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

			byId('btnGuardarAjusteInventario').style.display = '';
			byId('btnEditatTradein').style.display = 'none';

			byId('txtAllowance').onblur = function() { setFormatoRafk(this,2); calcularMonto(this.id); }
			byId('txtAllowance').className = 'inputHabilitado';
			byId('txtAllowance').readOnly = false;
			byId('txtAcv').onblur = function() { setFormatoRafk(this,2); calcularMonto(); }
			byId('txtAcv').className = 'inputHabilitado';
			byId('txtAcv').readOnly = false;
			byId('txtPayoff').className = 'inputHabilitado';

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

		xajax_formAjusteInventario(xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmAjusteInventario'), valor, valor2, valor3);
		} else if (IdObjTitulo == "tdFlotanteTitulo12"){
			titulo = "Posibilidad de Cierre";
			byId('textHddIdEmpresa').value = byId('lstEmpresa').value;
			byId('hddSeguimientoPosibleCierre').value = valor;
			xajax_listaPosibleCierre(0, "posicion_posibilidad_cierre", "ASC", byId('lstEmpresa').value+"|"+ valor);
			xajax_asignarEmpresa(byId('lstEmpresa').value, "divFlotante6");
		} else if (IdObjTitulo == "tdFlotanteTitulo13"){
			xajax_listaPosibleCierreObsv();
		} else if (IdObjTitulo == "tdFlotanteTitulo14"){
			titulo = "Citas para Hoy";
			
			$('#btnBuscarCitas').click();
			
			xajax_cargaLstVendedor('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', 'Citas');
			xajax_cargaLstEmpresaFinal('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>','onchange=\"xajax_cargaLstVendedor(this.value);xajax_cargaLstPosibilidadCierre(\'\', \'tdLstPosibilidadCierreBus\',this.value); byId(\'btnBuscar\').click();\"', "lstEmpresaCitas");
		} else if (IdObjTitulo == "tdFlotanteTitulo15"){
			var numRows = xajax_listaActividadCierre(valor, valor3);
		} else if (IdObjTitulo == "tdFlotanteTitulo16"){
			titulo = "Modelos de Interes";
			xajax_listaModelosInteres(0, "vw_iv_modelo.id_uni_bas", "DESC", valor);
		}

		openImg(idObj);
		byId(IdObjTitulo).innerHTML = titulo;
	}
	
	function showView(vista, validar){
		byId('trCedulaProspecto').style.display = '';
		byId('lstTipoProspecto').style.display = '';
		<?php if (in_array(idArrayPais,array(2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico ?>
			byId('trCedulaProspecto').style.display = 'none';
			byId('lstTipoProspecto').style.display = 'none';
		<?php } ?>
		
		switch (vista) { 
			case "listVendedor":
				xajax_cargaLstEquipo('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>', $('#lstEquipo').val());
				xajax_insertarIntegrante($('#lstEquipo').val(), $('#rdItemIntegrante').val());
	
				$('#tituloLogUp').text('Lista de Vendedores');
				$("#tblListVendedor").show();
				$("#tblModoIngreso").hide();
				$('#tblListProspecto').hide();
				$('#tblListdatosAdicionales').hide();
				$('#btnsGuardarSeguimiento').hide();
				break;
	
			case "listIngreso":
				$('#lstEquipo').val($('#lstEquipoTemp').val());
				$('#rdItemIntegrante').val($('input:radio[name= rdItemIntegranteTemp]:checked').val());
	
				if(validar == true){
					if (validarCampo('lstEquipoTemp','t','lista') == true
						&& $('input[name="rdItemIntegranteTemp"]').is(':checked')) {
					} else {
						document.getElementById('lstEquipoTemp').className = 'inputErrado';
						alert("Los campos señalados en rojo son requeridos");
						return false;
					}
				}
				$('#tituloLogUp').text('Modo de Ingreso al Dealer');
				$("#tblListVendedor").hide();
				$("#tblModoIngreso").show();
				$('#tblListProspecto').hide();
				$('#tblListdatosAdicionales').hide();
				$('#btnsGuardarSeguimiento').hide();
				break;
	
			case "listProspecto":
				if(validar == true){
					if ( !$('input[name="rdTipoIngreso"]').is(':checked')) {
						alert("Debe seleccionar el Ingreso al Dealer, son requeridos");
						return false;
					}
					
					var editar = $('#abtnEditar').val();
					if(editar == 0){
						$('#datosProspecto').hide();
						$('#datosProsClien').show();
					} else{
						$('#datosProspecto').show();
						$('#datosProsClien').hide();
					}
				}
	
				var tipoEquipo = $('#comboxTipoEquipo').val();
				if(tipoEquipo == 'Postventa'){
					$("#rdProspecto").hide();
					$("#btnNuevoProspecto").hide();
					$('#btnProspClient').hide();
					$('#btnClient').show();
					$('.spanCrearNuevo').hide();
					$('.spanNoCrearNuevo').show();
					
				} else{
					$("#rdProspecto").show();
					$("#btnNuevoProspecto").show();
					$('#btnProspClient').show();
					$('#btnClient').hide();
					$('.spanCrearNuevo').show();
					$('.spanNoCrearNuevo').hide();
				}
	
				$('#tituloLogUp').text('Agregar Prospecto/Cliente');
				$("#tblListVendedor").hide();
				$("#tblModoIngreso").hide();
				$('#tblListProspecto').show();
				$('#tblListdatosAdicionales').hide();
				$('#btnsGuardarSeguimiento').hide();
				break;
	
			case "listDatosAdicional":
				error = false;
				
				if (validar == true) {
					if (byId('lstTipoProspecto') != undefined && byId('lstTipoProspecto').style.display != 'none') {
						if (!(validarCampo('lstTipoProspecto','t','lista') == true)) {
							validarCampo('lstTipoProspecto','t','lista');
							
							error = true;
						}
					}
					
					if (!(validarCampo('txtIdEmpresa','t','') == true
					&& validarCampo('hddIdEmpleado','t','') == true
					&& validarCampo('txtNombreProspecto','t','') == true
					&& validarCampo('txtMunicipioProspecto','t','') == true
					&& validarCampo('txtTelefonoProspecto','t','telefono') == true
					&& validarCampo('txtOtroTelefonoProspecto','','telefono') == true
					&& validarCampo('txtCorreoProspecto','t','email') == true
					&& validarCampo('txtTelefonoComp','','telefono') == true
					&& validarCampo('txtOtroTelefonoComp','','telefono') == true
					&& validarCampo('txtEmailComp','','email') == true)) {
						validarCampo('txtIdEmpresa','t','');
						validarCampo('hddIdEmpleado','t','');
						validarCampo('txtNombreProspecto','t','');
						validarCampo('txtMunicipioProspecto','t','');
						validarCampo('txtTelefonoProspecto','t','telefono');
						validarCampo('txtOtroTelefonoProspecto','','telefono');
						validarCampo('txtCorreoProspecto','t','email');
						validarCampo('txtTelefonoComp','','telefono');
						validarCampo('txtOtroTelefonoComp','','telefono');
						validarCampo('txtEmailComp','','email');
						
						error = true;
					}
					
					if (inArray(byId('lstTipoProspecto').value, [-1,1])) { // -1 = Seleccione, 1 = Natural, 2 = Juridico
						if (!(validarCampo('txtApellidoProspecto','t','') == true)) {
							validarCampo('txtApellidoProspecto','t','');
							
							error = true;
						}
					} else if (byId('lstTipoProspecto').value == 2) { // -1 = Seleccione, 1 = Natural, 2 = Juridico
					} else {
						byId('txtApellidoProspecto').className = "inputInicial";
					}
					
					if (error == true) {
						alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
						return false;
					}
				}
	
				$('#tituloLogUp').text('Datos Adicionales');
				$("#tblListVendedor").hide();
				$("#tblModoIngreso").hide();
				$('#tblListProspecto').hide();
				$('#tblListdatosAdicionales').show();
				$('#btnsGuardarSeguimiento').show();
				break;
		}
	}
	
	function validarFrmAjusteInventario(valor) {				
		error = false;
		
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
			&& validarCampo('txtObservacionTrade', 't', '') == true)) {
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
				validarCampo('txtObservacionTrade', 't', '');//jafm
				error = true;
		}
		
		if (error == true) {
			alert("Los campos se\u00F1alados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			if (confirm('¿Seguro desea guardar el trade-in?') == true) {
				calcularMonto();
				xajax_guardarAjusteInventario(xajax.getFormValues('frmAjusteInventario'), xajax.getFormValues('frmUnidadFisica'), xajax.getFormValues('frmListaUnidadFisica'), valor);
			}
		}
	}
	
	function RecorrerForm(nameFrm, typeElemen, accion, valor, arrayElement){
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
		if (byId('lstTipoProspecto') != undefined && byId('lstTipoProspecto').style.display != 'none') {
			if (!(validarCampo('lstTipoProspecto','t','lista') == true)) {
				validarCampo('lstTipoProspecto','t','lista');
				
				error = true;
			}
		}
		
		if (!(validarCampo('txtIdEmpresa','t','') == true
		&& validarCampo('hddIdEmpleado','t','') == true
		&& validarCampo('txtNombreProspecto','t','') == true
		&& validarCampo('txtMunicipioProspecto','t','') == true
		&& validarCampo('txtTelefonoProspecto','t','telefono') == true
		&& validarCampo('txtOtroTelefonoProspecto','','telefono') == true
		&& validarCampo('txtCorreoProspecto','t','email') == true
		&& validarCampo('txtTelefonoComp','','telefono') == true
		&& validarCampo('txtOtroTelefonoComp','','telefono') == true
		&& validarCampo('txtEmailComp','','email') == true)) {
			validarCampo('txtIdEmpresa','t','');
			validarCampo('hddIdEmpleado','t','');
			validarCampo('txtNombreProspecto','t','');
			validarCampo('txtMunicipioProspecto','t','');
			validarCampo('txtTelefonoProspecto','t','telefono');
			validarCampo('txtOtroTelefonoProspecto','','telefono');
			validarCampo('txtCorreoProspecto','t','email');
			validarCampo('txtTelefonoComp','','telefono');
			validarCampo('txtOtroTelefonoComp','','telefono');
			validarCampo('txtEmailComp','','email');
			
			error = true;
		}
		
		if (inArray(byId('lstTipoProspecto').value, [-1,1])) { // -1 = Seleccione, 1 = Natural, 2 = Juridico
			if (!(validarCampo('txtApellidoProspecto','t','') == true)) {
				validarCampo('txtApellidoProspecto','t','');
				
				error = true;
			}
		} else if (byId('lstTipoProspecto').value == 2) { // -1 = Seleccione, 1 = Natural, 2 = Juridico
		} else {
			byId('txtApellidoProspecto').className = "inputInicial";
		}
		
		if (error == true) {
			alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
			return false;
		} else {
			xajax_guardarSeguimiento(xajax.getFormValues('frmSeguimiento'));
		}
	}
	
	function validarFrmNotas() { 
		if (validarCampo('textNotas','t','') == true) {
			xajax_guardarNotas(xajax.getFormValues('frmBusNotas'));
		} else {
			validarCampo('textNotas','t','');
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function validarFrmObservacion() {
		if (validarCampo('textObservacionCierre','t','') == true) {
			xajax_guardarObservacion(xajax.getFormValues('frmPosibleCierre'));
		} else {
			validarCampo('textObservacionCierre','t','');
			alert("Los campos señalados en rojo son requeridos");
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
			case 'tipo_contacto': 
				var value = $('#tipoIngreso').val()+'';
				
				$("input[value='"+value+"']").attr('checked', true);
				$('#rdItemIntegrante'+$('input:radio[name= rdItemIntegranteTemp]:checked').val()+'').val();
				$("#tituloLogUp").text('Tipo de Contacto');
				break;
			case "adicional": 
				$("#tituloLogUp").text('Datos Adicionales');
				break;
			case 'interes': 
				$("#tituloLogUp").text('Modelo de Interes');
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
		txtCreditoNetoAnt = txtAllowanceAnt - txtPayoffAnt;

		setFormatoRafk(byId('txtAllowance'),2);
		setFormatoRafk(byId('txtAcv'),2);
		setFormatoRafk(byId('txtPayoff'),2);
		setFormatoRafk(byId('txtCreditoNeto'),2);

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
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function btnOcultarDatos(accion){
		if (accion == 'hide') {
			$('#datosProspecto').hide(2);
			$('#datosProsClien').show(2);
		} else {
			byId('hddIdClienteProspecto').value = '';
			selectedOption(byId('lstTipoProspecto').id, -1);
			byId('txtCedulaProspecto').value = '';
			byId('txtFechaNacimiento').value = '';
			byId('txtNombreProspecto').value = '';
			byId('txtApellidoProspecto').value = '';
			byId('txtLicenciaProspecto').value = '';
			
			byId('txtUrbanizacionProspecto').value = '';
			byId('txtCalleProspecto').value = '';
			byId('txtCasaProspecto').value = '';
			byId('txtMunicipioProspecto').value = '';
			byId('txtCiudadProspecto').value = '';
			byId('txtEstadoProspecto').value = '';
			byId('txtTelefonoProspecto').value = '';
			byId('txtOtroTelefonoProspecto').value = '';
			byId('txtCorreoProspecto').value = '';
			
			byId('txtUrbanizacionPostalProspecto').value = '';
			byId('txtCallePostalProspecto').value = '';
			byId('txtCasaPostalProspecto').value = '';
			byId('txtMunicipioPostalProspecto').value = '';
			byId('txtCiudadPostalProspecto').value = '';
			byId('txtEstadoPostalProspecto').value = '';
			
			byId('txtUrbanizacionComp').value = '';
			byId('txtCalleComp').value = '';
			byId('txtCasaComp').value = '';
			byId('txtMunicipioComp').value = '';
			byId('txtEstadoComp').value = '';
			byId('txtTelefonoComp').value = '';
			byId('txtOtroTelefonoComp').value = '';
			byId('txtEmailComp').value = '';
			
			xajax_eliminarModelo(xajax.getFormValues('frmSeguimiento'), false);
			
			$('#datosProsClien').hide(2);
			$('#datosProspecto').show(2);
			
			document.getElementById("rdProspecto2").checked = true;
		}
	}
	
	function validarEliminarTrade(idTradeIn){
		if (confirm('¿Seguro desea eliminar este registro?') == true) {
			xajax_eliminarTradeIn(idTradeIn);
		}
	}
	</script>
</head>
<body class="bodyVehiculos">
    <div id="divGeneralPorcentaje">
        <div class="noprint"><?php include("banner_crm.php"); ?></div>
        
        <div id="divInfo" class="print">
            <table width="100%" border="0"> <!--tabla principa-->
            <tr>
                <td class="tituloPaginaCrm">Control de Tráfico</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>
                    <table align="left" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td><h2><?php echo date("l, F d Y"); ?></h2></td>
                    </tr>
                    <tr>
                        <td>
                        <a class="modalImg" id="aNuevo" rel="#divFlotante" onclick="abrirFrom(this,'frmSeguimiento','tdFlotanteTitulo', 0, 'tblProspecto')">
                            <button type="button" id="btnNuevo" style="cursor:default">
                                <table align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><img class="puntero" src="../img/iconos/ico_new.png" title="Editar"/></td>
                                        <td>&nbsp;</td>
                                        <td>Nuevo Tráfico</td>
                                    </tr>
                                </table>
                            </button>
                        </a>
                        <a class="modalImg" id="aCitas" rel="#divFlotante14" onclick="abrirFrom(this,'frmBusCitas','tdFlotanteTitulo14', 0, 'tblCitas')">
                            <button type="button" id="btnNuevo" style="cursor:default">
                                <table align="center" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td><img class="puntero" src="../img/iconos/cita_entrada.png" title="Editar"/></td>
                                        <td>&nbsp;</td>
                                        <td>Lista de Citas</td>
                                    </tr>
                                </table>
                            </button>
                        </a>
                        </td>
                    </tr>
                    </table>
                    
                <form id="frmBuscar" name="frmBuscar" onsubmit="return false;" style="margin:0">
                    <table align="right" border="0">
                    <tr align="left">
                        <td align="right" class="tituloCampo">Empresa:</td>
                        <td id="tdlstEmpresa"></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo" width="120">Fecha Creación:</td>
                        <td>
                            <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td>&nbsp;Desde:&nbsp;</td>
                                <td><input type="text" id="textDesdeCreacion" name="textDesdeCreacion" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                                <td>&nbsp;Hasta:&nbsp;</td>
                                <td><input type="text" id="textHastaCreacion" name="textHastaCreacion" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                            </tr>
                            </table>
                        </td>
                        <td align="right" class="tituloCampo" width="120">Fecha Próxima Cita:</td>
                        <td>
                            <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td>&nbsp;Desde:&nbsp;</td>
                                <td><input type="text" id="textDesdeCita" name="textDesdeCita" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                                <td>&nbsp;Hasta:&nbsp;</td>
                                <td><input type="text" id="textHastaCita" name="textHastaCita" autocomplete="off" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center"/></td>
                            </tr>
                            </table>
                        </td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Posible Cierre:</td>
                        <td id="tdLstPosibilidadCierreBus"></td>
                        <td align="right" class="tituloCampo">Vendedor:</td>
                        <td id="tdLstVendedor"></td>
                    </tr>
                    <tr align="left">
                        <td align="right" class="tituloCampo">Modo de Ingreso:</td>
                        <td id="tdlstModoIngreso"></td>
                        <td align="right" class="tituloCampo">Criterio:</td>
                        <td><input id="textCriterio" name="textCriterio" class="inputHabilitado" onblur="byId('btnBuscar').click();" /></td>
                        <td align="right">
                            <button type="button" id="btnBuscar" onclick="xajax_buscarControlTrafico(xajax.getFormValues('frmBuscar'));">Buscar</button>
                            <button type="button" id="btnLimpiar" onclick="this.form.reset(); byId('btnBuscar').click();">Limpiar</button>
                        </td>
                    </tr>
                    </table>
                </form>
                </td>
            </tr>
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
                                    <td><img src="../img/iconos/text_signature.png" /></td><td>Notas</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/ico_edit.png" /></td><td>Editar Trade-In</td>
                                    <td>&nbsp;</td>
                                    <td><img src="../img/iconos/car_go.png" /></td><td>Agregar Trade-In</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    </table>
                </td>
            </tr>
            </table>
        </div><!-- fin contenedor interno-->
        
        <div class="noprint"><?php include("pie_pagina.php"); ?></div>
    </div> <!--fin del contenedor general-->
</body>
</html>
<?php include("crm_seguimiento_list_divFlotante.php"); ?>

<script> 
byId('textDesdeCreacion').className = 'inputHabilitado';
byId('textHastaCreacion').className = 'inputHabilitado';
byId('textDesdeCita').className = 'inputHabilitado';
byId('textHastaCita').className = 'inputHabilitado';

byId('textDesdeCreacion').value = "<?php echo date(spanDateFormat); ?>";
byId('textHastaCreacion').value = "<?php echo date(spanDateFormat); ?>";

window.onload = function(){
	jQuery(function($){
		$("#txtFechaNacimiento").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaUltAtencion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaUltEntrevista").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#txtFechaProxEntrevista").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#textDesdeCita").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#textHastaCita").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#textHastaCreacion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#textDesdeCreacion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
		$("#textFechAsignacion").maskInput("<?php echo spanDateMask; ?>",{placeholder:" "});
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaNacimiento",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme :"bananasplit",
	});//textDesdeCreacion
	new JsDatePick({
		useMode:2,
		target:"txtFechaUltAtencion",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme :"bananasplit",
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaUltEntrevista",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme :"bananasplit",
	});
	new JsDatePick({
		useMode:2,
		target:"txtFechaProxEntrevista",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme :"bananasplit",
	});
	new JsDatePick({
		useMode:2,
		target:"textDesdeCita",
		cellColorScheme :"bananasplit",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	new JsDatePick({
		useMode:2,
		target:"textHastaCita",
		cellColorScheme :"bananasplit",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	a = new JsDatePick({
		useMode:2,
		target:"textHastaCreacion",
		cellColorScheme :"bananasplit",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	b = new JsDatePick({
		useMode:2,
		target:"textDesdeCreacion",
		cellColorScheme :"bananasplit",
		dateFormat:"<?php echo spanDatePick; ?>"
	});
	c = new JsDatePick({
		useMode:2,
		target:"textFechAsignacion",
		dateFormat:"<?php echo spanDatePick; ?>",
		cellColorScheme :"bananasplit",
	});
	
	a.addOnSelectedDelegate(function(){
		byId('btnBuscar').click();
	});
	b.addOnSelectedDelegate(function(){
		byId('btnBuscar').click();		
	});
	c.addOnSelectedDelegate(function(){
		xajax_cargaLstHora(xajax.getFormValues('formAsignarActividadSeg'));
	});
}

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
			closeOnEsc: false ,
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

xajax_cargarPagina('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');

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
var theHandle = document.getElementById("divFlotanteTitulo9");
var theRoot   = document.getElementById("divFlotante9");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo10");
var theRoot   = document.getElementById("divFlotante10");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("divFlotanteTitulo11");
var theRoot   = document.getElementById("divFlotante11");
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