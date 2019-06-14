<?php
require_once("../connections/conex.php");
	
session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_asignacion_actividad_list"))) {
	echo "<script> alert('Acceso Denegado'); window.location.href = 'index.php'; </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');//clase xajax
$xajax = new xajax();//Instanciando el objeto xajax

$xajax->configure('javascript URI', '../controladores/xajax/');//Configuranto la ruta del manejador de script
$xajax->configure( 'defaultMode', 'synchronous' ); 

include("controladores/ac_crm_asignacion_actividad_list.php"); //contiene todas las funciones xajax
include("../controladores/ac_iv_general.php");

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. CRM - Asignación de Actividades</title>
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
    
	<script>
    //ABRE EL FORMULARIO DE LA ASIGNACION DE ACTIVIDADES
	function abrirNuevaAsignacion(idIntegrante, horaAsignacion, idActividaEjecucion){
		document.getElementById('formAsignarActividadAgen').reset();
		byId('tipoFinalizado').className = 'inputHabilitado';
		byId('idClienteHidd').className = 'inputInicial';
		byId('textNombreCliente').className = 'inputInicial';
		byId('textNotaCliente').className = 'inputCompletoHabilitado';	
		
		xajax_cargarDatos(xajax.getFormValues('fromTipoEquipo'),idIntegrante,horaAsignacion,idActividaEjecucion);
	}
		
	function abrirNuevaAsignacionPostVenta(Idobj,idCliente,nombreCliente){
		
		var tipoEquipo = document.getElementById('comboxTipoEquipo').value;
		
		if(tipoEquipo != "Postventa"){
			return alert("Debe Seleccionar Tipo de Equipo Postventa");	
		}
		
		if(document.formAsignarActividad2.comboListIntegrante && document.formAsignarActividad2.horaSelect){//SI EXISTEN LOS CAMPOS
			$('#comboListIntegrante').remove();
			$('#horaSelect').remove();
		}
	
		xajax_cargarLstEquipo(tipoEquipo,"tdEquipoServicio");
		xajax_cargarLstActivida(tipoEquipo,"tdListActividadServicio");
		
		document.forms['formAsignarActividad2'].reset();
				
		document.getElementById('txtTipoActividadS').value = document.getElementById('comboxTipoEquipo').value;
		document.getElementById('textIdNombreClienteS').value = idCliente;
		document.getElementById('textNombreClienteS').value = nombreCliente;
		
		openImg(Idobj);
	}
	
	function acciones(idObjeto,accion,valor){
		switch (accion){
			case "disabled":
				if(valor == true){
					document.getElementById(idObjeto).disabled = true;	
				}else{
					document.getElementById(idObjeto).disabled = false;
				}
			break;
			case "show":
				document.getElementById(idObjeto).style.display = '';
			break;
			case "hide":
				document.getElementById(idObjeto).style.display = 'none';
			break;				
		}
	}
		
		var array_js = new Array();
	function crearGrafico(datos,nombreActividad,nombreIntegrante){
	
		var mesesLetras = new Array();//Meses a mostrar
		var estatusFinalizado = new Array();//todos los datos finalizado
		var estatusAsignado = new Array();// todos los datos asignados
		var estatusFinalizadoTarde = new Array();//todos los datos finalizado tarde
		var estatusFinalizadoAuto = new Array();//todos los finalizados auto
		
		for( var property in datos ){
			mesesLetras.push(mesesDiminutivo(property));//llenado array meses
			
			var separacion = datos[property].split("|");
			
			Finalizado = (separacion[0] != "")? separacion[0]: 0;
			Asignado = (separacion[1] != "")? separacion[1]: 0;
			FinalizadoTarde = (separacion[2] != "")? separacion[2]: 0;
			FinalizadoAuto = (separacion[3] != "")? separacion[3]: 0;
	
			estatusFinalizado.push(parseFloat(Finalizado));
			estatusAsignado.push(parseFloat(Asignado));
			estatusFinalizadoTarde.push(parseFloat(FinalizadoTarde));
			estatusFinalizadoAuto.push(parseFloat(FinalizadoAuto));
		//console.log( property +" - "+datos[property] );
		}
	
		var grafico = new Highcharts.Chart({
			chart: {
				renderTo: 'divGraficos1',
				type: 'column'
			},
			title: { text: nombreActividad },
			subtitle: { text: nombreIntegrante },
			xAxis: { categories: mesesLetras },
			yAxis: {
				min: 0,
				title: {
					text: 'Cantidades (Total)'
				}
			},
			plotOptions: {
				column: {
					pointPadding: 0.2,
					borderWidth: 0
				}
			},
			series: [{
					name: 'Finalizado',
					data: estatusFinalizado
				}, {
					name: 'Asignado',
					data: estatusAsignado
				}, {
					name: 'Finalizo tarde',
					data: estatusFinalizadoTarde
				}, {
					name: 'Finalizado auto',
					data: estatusFinalizadoAuto
			}]
		});
		array_js =  new Array();	
	}
	
	var arrayActInteg = new Array(); 
	function crearGrafico2(nombreEquipo,nombreActividad){
		var nombresIntegrantes = new Array();
		var tipoFinalizacionEfectivo = new Array();
		var tipoFinalizacionNoEfectivo = new Array();
		
		for(var key in arrayActInteg){
	
			nombresIntegrantes.push(key);
			var separacion = arrayActInteg[key].split("|");
	
			NoEfectivo = (separacion[0] != "")? separacion[0]: 0;
			Efectivo = (separacion[1] != "")? separacion[1]: 0;
			
			tipoFinalizacionNoEfectivo.push(parseFloat(NoEfectivo));
			tipoFinalizacionEfectivo.push(parseFloat(Efectivo));
			//console.log(NoEfectivo+" ---- "+Efectivo /*key +" - "+arrayActInteg[key]*/ );
		}	
		
		var grafico = new Highcharts.Chart({
			 chart: {
				renderTo: 'divGraficos2',// DONDE SE UBICA
				type: 'bar'
			},
			title: { text: nombreEquipo }, // TITULO
			subtitle: { text: nombreActividad }, // SUBTITULO
			xAxis: { categories: nombresIntegrantes }, //NOMBRE DE LOS INTEGRANTES
			yAxis: {
				min: 0,
				title: {
					text: 'Cantidades (Total)'
				}
			},
			plotOptions: {
				column: {
					pointPadding: 0.2,
					borderWidth: 0
				}
			},
			series: [{
				//color: '#AA4643',
				name: 'Efectiva',
				data: tipoFinalizacionEfectivo
			}, {
				//color: '#4572A7',
				name: 'No Efectiva',
				data: tipoFinalizacionNoEfectivo
			}]
		});
		arrayActInteg = new Array();
	}
	
	//EFECTO MOSTRAR Y OCULTAR ACTIVIDADES
	function mostrarOcultatActividad(idEquipo){
		if($('#DetEquipoActividades' + idEquipo).is(':visible')){
		//ocultar
			$('#DetEquipoActividades' + idEquipo).slideUp();
			$('#imgVerActividades'  + idEquipo).show();
			$('#imgOcutarActividades'  + idEquipo).hide();
		}else{
			//mostrar
			$('#DetEquipoActividades' + idEquipo).slideDown();
			xajax_listaDetActIntegrante(0,"estatus","",idEquipo)
			$('#imgOcutarActividades'  + idEquipo).show();
			$('#imgVerActividades'  + idEquipo).hide();
		}
	}
	
	function mesesDiminutivo(numeroMes){
		switch(parseInt(numeroMes)){
			case 1: numeroMes = "Ene"; break;
			case 2: numeroMes = "Feb"; break;
			case 3: numeroMes = "Mar"; break;
			case 4: numeroMes = "Abr"; break;
			case 5: numeroMes = "May"; break;
			case 6: numeroMes = "Jun"; break;
			case 7: numeroMes = "Jul"; break;
			case 8: numeroMes = "Ago"; break;
			case 9: numeroMes = "Sep"; break;
			case 10: numeroMes = "Oct"; break;
			case 11: numeroMes = "Nov"; break;
			case 12: numeroMes = "Dic"; break;
			default:  numeroMes = numeroMes;
		}
		return numeroMes;
	}
	
	//PARA MOSTRAR Y OCULTAR LOS DATOS DE LOS VEHICULOS DEL CLIENTE
	function mostrarOcultatVehiculo(idClienteContacto){
		var meses = $('#LisMeses').val();
		if($('#clienteContacto' + idClienteContacto).is(':visible')){
		//ocultar
			$('#clienteContacto' + idClienteContacto).hide();
		
			$('#butMostraVehiculo'  + idClienteContacto).show();
			$('#butOcultarVehiculo'  + idClienteContacto).hide();
		}else{
			//mostrar
			$('#clienteContacto' + idClienteContacto).show();
			
			xajax_listMostrarVehiculos('0','','',idClienteContacto+"|"+meses)
			$('#butOcultarVehiculo'  + idClienteContacto).show();
			$('#butMostraVehiculo'  + idClienteContacto).hide();
		}
	}		
	
	function selectFechaEquipo(){
		if(document.getElementById('comboxTipoEquipo').value == ""){ //
			$("ul.tabs").tabs("> .pane", {initialIndex: 1});
			alert('Debe Seleccionar un Tipo Equipo ');
			return;
		} 
		if(document.getElementById('comboListEquipo').value == ""){
			alert('Debe Seleccionar un Equipo');
			$("ul.tabs").tabs("> .pane", {initialIndex: 1});
			return;
		} 
		if(document.getElementById('fechaSelectEquipo').value != ""){
			xajax_selectIntegrante(document.getElementById('fechaSelectEquipo').value,document.getElementById('comboListEquipo').value); 
			$('ul.tabs').tabs('> .pane', {initialIndex: 0});
		}
	}
	//SELECCIONA TODO LOS CHECK DE SERVICIO
	var aux = true;
	function seleccionarTodosCheckbox(){
		if(aux == true){
		$("input[id=chekAsignaActividad]").each ( function() {
			$(this).attr("checked", "checked");
		});
			 aux = false;
		}else{
		$("input[id=chekAsignaActividad]").each ( function() {
			$(this).removeAttr("checked");
			});
				 aux = true;
		}
	}
	
	//SOLO PARA LLAMAR A LAS FUNCJONES XAJAX EN CADA TARGE  
	function llamarXajax(numTabs, fecha){ 
	/*	var tipoEquipo = document.getElementById('comboxTipoEquipo').value; 
		if(document.fromTipoEquipo.comboListEquipo){ //SI EXISTE EL CAMPO
			var idEquipo = document.getElementById('comboListEquipo').value; 
		}
	*/	
		if(fecha == null){
			var fechaAsignacion = (document.getElementById('fechaSelectEquipo').value);
		} else {
			var fechaAsignacion = fecha;
		}
		
		switch(numTabs){
			case 2: //RESUMEN
				xajax_listaActDiaActual(0,"","", 1+"|"+ document.getElementById('comboxTipoEquipo').value);
				xajax_listaActAtrasadasDia(0,"","", 1+"|"+ document.getElementById('comboxTipoEquipo').value);
				xajax_listaActDiaSiguiente(0,"","", 1+"|"+ document.getElementById('comboxTipoEquipo').value);
			break;
			
			case 3: //RESUMEN DETALLADO
				xajax_listaActPorEquipoDet(0,"","", document.getElementById('comboxTipoEquipo').value);
				xajax_listaActSemPasada(0,"","", document.getElementById('comboxTipoEquipo').value);
				xajax_listaActTresMeses(0,"","", document.getElementById('comboxTipoEquipo').value);
				xajax_listaActFinalizadasAuto(0,"","", 3+"|"+document.getElementById('comboxTipoEquipo').value);
				xajax_listaActFinalizadasTarde(0,"","", 2+"|"+document.getElementById('comboxTipoEquipo').value);
				xajax_listaActFinalizadasNoEfectiva(0,"","", 0+"|"+document.getElementById('comboxTipoEquipo').value);
				xajax_listaActFinalizadasEfectiva(0,"","", 1+"|"+document.getElementById('comboxTipoEquipo').value);
				xajax_listaActAsignadas(0,"","", 1+"|"+document.getElementById('comboxTipoEquipo').value);
				xajax_listaActFinalizadas(0,"","", 1+"|"+document.getElementById('comboxTipoEquipo').value);
			break;
				
			case 4: //ESTADISTICA
				if(document.getElementById('comboxTipoEquipo').value == ""){
					alert('Debe seleccionar un tipo de equipo');
					$('ul.tabs').tabs('> .pane', {initialIndex: 1});
				} else {
					xajax_cargarLstEquipo(xajax.getFormValues("fromTipoEquipo"),"tdListEquipoGrafico","Todo")
					xajax_cargarLstEquipo(xajax.getFormValues("fromTipoEquipo"),"tdListEquipoGrafico2")
					xajax_cargarLstActivida(document.getElementById('comboxTipoEquipo').value,"tdListTipoActividaGrafico","","Todos");
				}
			break;
					  
			case 5: //SERVICIO
				xajax_cargarLstMeses(5);
				//xajax_ListServicioPendiente(0,'',''); 
				byId('butBuscarServicio').click();
			break;
		}
	}
	
	//LIMPIA EL AREA DONDE SE GENERAR EL GRAFICO
	function limpiarGrafico(idGrafico){
		$("#"+idGrafico).empty();
		if(idGrafico == "divGraficos1"){
			byId('fechaInicio').className = 'inputHabilitado';
			byId('fechaFin').className = 'inputHabilitado';
		} else {
			byId('fechaInicio2').className = 'inputHabilitado';
			byId('fechaFin2').className = 'inputHabilitado';
		}
	}
	
	//VALIDAR CAMPO ACTIVIDAD
	function validarFromAgenda(){
		if (validarCampo('listActividad','t','lista') == true
			&& validarCampo('idClienteHidd','t','') == true
			&& validarCampo('textNombreCliente','t','') == true) {
			
			xajax_guardaActividadAgenda(xajax.getFormValues('formAsignarActividadAgen'));
			
		} else {
			validarCampo('listActividad','t','lista');
			validarCampo('idClienteHidd','t','');
			validarCampo('textNombreCliente','t','');
			alert("Los campos en color rojos son obligatorios 1");
			return false;
		}
	}
	
	function validarGenerarGrafico(){
		if(validarCampo('fechaInicio','t','') == true && validarCampo('fechaFin','t','') == true){ 
			xajax_generarGrafico(xajax.getFormValues("formGenerarGrafico"));
		} else {
			validarCampo('fechaInicio','t','');
			validarCampo('fechaFin','t','');
			alert("Los campos en color rojo son obligatorios");
		}
	}
	
	function validarGenerarGrafico2(){
		if(validarCampo('fechaInicio2','t','') == true && 
			validarCampo('fechaFin2','t','') == true){ 
			xajax_generarGrafico2(xajax.getFormValues("formGenerarGrafico2"));
		} else {
			validarCampo('fechaInicio2','t','');
			validarCampo('fechaFin2','t','');
			alert("Los campos en color rojo son obligatorios");
		}	
	}
	
	function validarFormActPostVenta() {
		if (validarCampo('listActividad','t','listaExceptCero') == true
			&& validarCampo('comboListEquipoS','t','listaExceptCero') == true
			&& validarCampo('comboListIntegrante','t','listaExceptCero') == true
			&& validarCampo('fechaAsignacion2','t','') == true
			&& validarCampo('horaSelect','t','') == true ) {
				xajax_guardaActividadPostVenta(xajax.getFormValues('formAsignarActividad2'));
		} else {
			validarCampo('textIdNombreClienteS','t','');
			validarCampo('textNombreClienteS','t','');
			validarCampo('comboListEquipoS','t','');
			validarCampo('listActividad','t','');
			validarCampo('fechaAsignacion2','t','');
			if(document.formAsignarActividad2.comboListIntegrante && document.formAsignarActividad2.horaSelect){//SI EXISTEN LOS CAMPOS
				validarCampo('comboListIntegrante','t','');
				validarCampo('horaSelect','t','');
			}
			alert("Los campos en color rojo son obligatorios");
		}
	}
	
	//ELIMINAR ACTIVIDAD EN EJECUCION
	function validarEliminarActividad(){
		if(confirm("¿Estas seguro que desea eliminar la actividad?") == true){
			xajax_eliminarActivida(xajax.getFormValues('formAsignarActividadAgen'));
		}
	}
    </script>

</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_crm.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table width="100%" border="0"> <!--tabla principa-->
        <tr><td class="tituloPaginaCrm">Asignación de Actividades</td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr align="right">
            <td>
            <form id="fromTipoEquipo" name="fromTipoEquipo" onsubmit="false;">
                <table align="right" border="0">
                <tr align="left">
                    <td align="right" class="tituloCampo">Empresa </td>
                    <td colspan="3">
                    	<table cellpadding="0" cellspacing="0">
                        <tr>
                            <td><input type="text" id="textIdEmpresaBus" name="textIdEmpresaBus" readonly="readonly" size="6" style="text-align:right;"/></td>
                            <td></td>
                            <td><input type="text" id="textEmpresaBus" name="textEmpresaBus" readonly="readonly" size="45"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td align="right" class="tituloCampo">Fecha</td>
                    <td colspan="3"><input type="text" id="fechaSelectEquipo" name="fechaSelectEquipo" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center" value="<?php echo date("d-m-Y"); ?>"/></td>
                </tr>
                <tr>
                    <td align="right" class="tituloCampo" width="120">Tipo de Equipo:</td>
                    <td id="tdTipoEquipo"></td>
                    <td align="right" class="tituloCampo" width="120">Equipos:</td>
                    <td id="tdListEquipo">
                        <select class="inputHabilitado">
                           <option value="">[ Seleccione ]</option>
                        </select>
                    </td>
                    <td align="right">
                        <button type="button" id="btnBuscarAgenda" name="btnBuscarAgenda" onclick="selectFechaEquipo();">Buscar</button>
                        <button type="button" id="btnLimpiarAgenda" name="btnLimpiarAgenda" onclick="document.forms['fromTipoEquipo'].reset(); $('ul.tabs').tabs('> .pane', {initialIndex: 1});">Limpiar</button> 
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr>
            <td align="right"><!-- <div id="tabla"></div>-->
            <p align="right"><h2><?php echo date("l, F d Y"); ?></h2></p>
            <div class="wrap">
                <!-- the tabs -->
                <ul class="tabs">
                    <li><a href="#" onclick="llamarXajax(1);">Asignar Actividad</a></li>
                    <li><a href="#" onclick="llamarXajax(2);">Resumen</a></li>
                    <li><a href="#" onclick="llamarXajax(3);">Resumen Detallado</a></li>
                    <li><a href="#" onclick="llamarXajax(4);">Estadisticas</a></li>
                    <li><a href="#" id="tabsServicio" onclick="llamarXajax(5);"  >Servicio</a></li><!--style="display:none"-->
                </ul>
                
                <div id="tablaHora" class="pane"> <!--Asignar Actividad--> 
                    <table class="divMsjError" width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="25"><img width="25" src="../img/iconos/ico_fallido.gif"></td>
                            <td align="center">No se encontraron registros</td>
                        </tr>
                    </table>
                </div>
                <div class="pane"> <!--Resumen-->
                    <table width="100%" border="0" align="center">
                      <tr>
                        <td id="tdActividadesDiaActual" valign="top"></td>
                        <td id="tdActividadesAtrazadasDia" valign="top"></td>
                        <td id="tdActividadesDiaSiguiente" valign="top"></td>
                      </tr>
                    </table>                        
                </div>
                <div class="pane">  <!-- Resumen detallado-->
                    <table width="100%" border="0" align="center">
                      <tr>
                        <td id="tdNombreEquipo" valign="top" colspan="2"></td>
                        <!--<td valign="top"></td>-->
                      </tr>
                      <tr>
                        <td id="tdActividadesUnSemana" valign="top"></td>
                        <td id="tdActividadesTresMeses" valign="top"></td>
                      </tr>
                      <tr>
                        <td id="tdActividadesFinalizadasAuto" valign="top"></td>
                        <td id="tdAcitivadaAsignacioFinalizadasTardes" valign="top"></td>
                      </tr>
                      <tr>
                        <td id="tdActividaddesFinalizadasNoEfectivas" valign="top"></td>
                        <td id="tdActividaddesFinalizadasEfectivas" valign="top"></td>
                      </tr>
                      <tr>
                        <td id="tdActividadesAsignadad" valign="top"></td>
                        <td id="tdActividadesFinalizadas" valign="top"></td>
                      </tr>
                    </table>                         
                </div>
                
                <div class="pane"> <!--Estadisticas-->
                    <div id="divGeneralGrafico1">
                        <fieldset><legend class="legend">Gráfico de Actividades</legend>
                            <form id="formGenerarGrafico" name="formGenerarGrafico">
                            	<table border="0" align="right">
                                <tr align="left">
                                    <td class="tituloCampo" align="right">Fecha:</td>
                                    <td colspan="3">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;Desde:&nbsp;</td>
                                            <td><input type="text" id="fechaInicio" name="fechaInicio" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center" value="<?php echo date(str_replace("d","01",spanDateFormat)); ?>"/></td>
                                            <td>&nbsp;Hasta:&nbsp;</td>
                                            <td><input type="text" id="fechaFin" name="fechaFin" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center" value="<?php echo date(spanDateFormat); ?>"/></td>
                                        </tr>
                                        </table>
									</td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120">Equipos:</td>
                                    <td id="tdListEquipoGrafico"> </td>
                                    <td align="right" class="tituloCampo" width="120">Integrantes:</td>
                                    <td id="tdListIntegrantesGrafico"></td>
                                </tr>
                                <tr align="left">
                                	<td></td>
                                	<td></td>
                                    <td align="right" class="tituloCampo">Actividades:</td>
                                    <td id="tdListTipoActividaGrafico"></td>
                                    <td>
                                        <button type="button" id="butBuscar" name="butBuscar" onclick="validarGenerarGrafico();">Buscar</button>
                                        <button type="button" id="butLimpiar" name="butLimpiar" onclick="this.form.reset(); limpiarGrafico('divGraficos1');">Limpiar</button>
                                    </td>
                                </tr>
                            	</table> 
                            	
                            	<div id="divGraficos1"></div>
                        	</form>
                        </fieldset>
                    </div>
                    <div id="divGeneralGrafico2">
                        <fieldset><legend class="legend">Gráfico de Desempeño</legend>
                            <form id="formGenerarGrafico2" name="formGenerarGrafico2">
                            	<table border="0" align="right">
                                <tr align="left">
                                    <td align="right" class="tituloCampo">Fecha:</td>
                                    <td colspan="3">
                                        <table cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td>&nbsp;Desde:&nbsp;</td>
                                            <td><input type="text" id="fechaInicio2" name="fechaInicio2" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center" value="<?php echo date(str_replace("d","01",spanDateFormat)); ?>"/></td>
                                            <td>&nbsp;Hasta:&nbsp;</td>
                                            <td><input type="text" id="fechaFin2" name="fechaFin2" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" size="10" style="text-align:center" value="<?php echo date(spanDateFormat); ?>"/></td>
                                        </tr>
                                        </table>
									</td>
                                </tr>
                                <tr align="left">
                                    <td align="right" class="tituloCampo" width="120">Equipos:</td>
                                    <td id="tdListEquipoGrafico2"></td>
                                    <td align="right" class="tituloCampo" width="120">Actividades:</td>
                                    <td>
                                        <select id='tipoFinalizado' name='tipoFinalizado' class="inputHabilitado"> 
                                            <option value=''>[ Seleccione ]</option>
                                            <option value='0'>No Efectiva</option>
                                            <option value='1'>Efectiva</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" id="butBuscar2" name="butBuscar2" onclick="validarGenerarGrafico2();">Buscar</button>
                                        <button type="button" id="butLimpiar2" name="butBuscar2" onclick="this.form.reset(); limpiarGrafico('divGraficos2');">Limpiar</button>
                                    </td>
                                </tr>
                                </table> 
                            </form>
                            <div id="divGraficos2"></div>
                        </fieldset>
                    </div>                           
                </div>
                
                <div class="pane"> <!--servicio--->
                    <form  id="formBuscar" name="formBuscar" onsubmit="retunr false" style="margin:0">
                        <table align="right" border="0">
                            <tr align="left">
                                <td class="tituloCampo" align="right">Entrada a Taller Hace:</td>
                                <td id="tdConboListMese" ></td>
                                <td align="right">
                                    <button type="button" id="butAsignarAutomatico" name="butAsignarAutomatico" onclick="xajax_guardaActividadAuto(xajax.getFormValues('formListServicioPendiente'));">
                                    Asignar Automatico
                                    </button>
                                </td>
                            </tr>
                            <tr align="left">
                                <td class="tituloCampo" align="right">Criterio:</td>
                                <td colspan="2">
                                <input type="text" id="textCriterio" name="textCriterio" class="inputHabilitado" size="40"/>
                                </td>
                            </tr>
                            <tr >
                                <td align="right" colspan="3">
                                <button type="button" id="butBuscarServicio" name="butBuscarServicio" onclick="xajax_buscarClienteServicio(xajax.getFormValues('formBuscar'));">
                                Buscar
                                </button>
                                <button type="button" id="butLimpiaServicior" name="<strong>butLimpiar</strong>" onclick="this.form.reset(); byId('butBuscarServicio').click();">
                                Limpiar
                                </button>
                                </td>
                            </tr>
                        </table>
                    </form>
                    <br /><br /><br /><br /><br />
                    <form name="formListServicioPendiente" id="formListServicioPendiente">
                        <div id="divServicio"></div>
                    </form>
                </div>
            </div>
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
                            <td><img src="../img/iconos/cita_entrada.png"/></td><td>Pendiente</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cita_programada.png"/></td><td>Finalizada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cita_entrada_retrazada.png"/></td><td>Finalizada Tarde</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/arrow_rotate_clockwise.png"/></td><td>Finalizada Automáticamente</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
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
                            <td><img src="../img/iconos/time_go.png"/></td><td>Retrasada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/exclamation.png"/></td><td>No Efectiva</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/tick.png"/></td><td>Efectiva</td>
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
<!--ASIGNACION DE ACTIVIDAD
 divFlotanteTitulo2 divFlotante2
-->
<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo2" width="100%">Asignacion de Actividad</td></tr></table></div>
    
<form id="formAsignarActividadAgen"  name="formAsignarActividadAgen" onsubmit="return false;">
    <table border="0" width="760">
    <tr align="left">
        <td align="right" class="tituloCampo"><span class="textoRojoNegrita">*</span>Empleado:</td>
        <td colspan="3">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="textIdIntegrante" name="textIdIntegrante" readonly="readonly" size="6" style="text-align:right"/></td>
                <td></td>
                <td><input type="text" id="nombreVendedor" name="nombreVendedor" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="trAsignarActTipo" align="left">
        <td align="right" class="tituloCampo" width="20%">Tipo de Actividad:</td>
        <td width="30%"><input name="txtTipoActividad" id="txtTipoActividad" type="text" readonly="readonly"/></td>
        <td align="right" class="tituloCampo" width="20%"><span class="textoRojoNegrita">*</span>Actividad:</td>
        <td id="tdListActividad" width="30%"></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Asignada para:</td>
        <td colspan="3">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td>&nbsp;Fecha:&nbsp;</td>
                <td><input type="text" id="textFechAsignacion" name="textFechAsignacion" autocomplete="off" class="inputHabilitado" placeholder="<?php echo spanDateFormat; ?>" readonly="readonly" size="10" style="text-align:center"/></td>
                <td>&nbsp;Hora:&nbsp;</td>
                <td>
                    <input type="text" name="textHoraAsignacion" id="textHoraAsignacion" readonly="readonly" size="10" style="text-align:center"/>
                    <input name="textHoraAsignacion2" id="textHoraAsignacion2" type="hidden" readonly="readonly"/>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="trEstadoActividad" align="left" style="display:none">
        <td align="right" class="tituloCampo">Estado de Actividad:</td>
        <td id="tdcomboxEstado" colspan="3"></td>
    </tr>
    <tr align="left">
        <td id="tdNombreCliente" align="right" class="tituloCampo"></td> 
        <td colspan="3">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="idClienteHidd" name="idClienteHidd" readonly="readonly" size="6" style="text-align:right"/></td>
                <td>
                <a class="modalImg" id="listCliente" rel="#divFlotante2" onclick="openImg(this);">
                   <button type="button" title="Seleccionar"><img src="../img/iconos/help.png"/></button>
                </a>
                </td>
                <td><input type="text" id="textNombreCliente" name="textNombreCliente" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="trTipoFinalizacion" align="left" style="display:none">
        <td align="right" class="tituloCampo" >Tipo de Finalizacion</td>
        <td colspan="3">
            <select id='comboxEstadoActAgenda' name='comboxEstadoActAgenda' class="inputHabilitado"> 
                <option value=''>[ Seleccione ]</option>
                <option value='0'>No Efectiva</option>
                <option value='1'>Efectiva</option>
             </select>
        </td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Nota:</td>
        <td colspan="3"><textarea id="textNotaCliente" name="textNotaCliente" class="inputCompletoHabilitado" rows="2"></textarea></td>
    </tr>
    <tr align="left" id="trObservacion" style="display:none;">
        <td align="right" class="tituloCampo">Observaci&oacute;n:</td>
        <td colspan="3">
            <textarea id="textObservacion" name="textObservacion" class="inputCompletoHabilitado" rows="2"></textarea>
        </td>
    </tr>
    <tr>
        <td align="right" colspan="4"><hr>
            <input name="hddActEjecucion" id="hddActEjecucion" type="hidden" value=""/>
            <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarFromAgenda();"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
            <button type="button" id="botEliminar" name="botEliminar" onclick="validarEliminarActividad()" style="display:none">
                <table align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td><img src="../img/iconos/cross.png"/></td><td>&nbsp;</td><td>Eliminar</td>
                </tr>
                </table>
            </button>
            <button type="button" id="butCancelarAsignacion" name="butCancelarAsignacion" class="close"><table align="center" cellpadding="0" cellspacing="0"><tr><td><img src="../img/iconos/cancel.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
        </td>
    </tr>
    </table>
</form>
</div>
<!--LISTA DE CLIENTE OCULTOS-->
<div id="divFlotante2" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo2" class="handle"><table><tr><td id="tituloTdListCleinte" width="100%"></td></tr></table></div>
    
	<div style="max-height:500px; overflow:auto; width:960px;">
    	<table border="0" width="100%">
        <tr>	
            <td>
            <form id="frmBuscarCliente" name="frmBuscarCliente" onsubmit="return false;">
                <table align="right" border="0">
                <tr>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="textCriterio" name="textCriterio" class="inputHabilitado"/></td>
                    <td>
                        <input type="hidden" id="textTipoActividad" name="textTipoActividad" value=""/>
                        <button type="button" id="buttBuscar" name="buttBuscar" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));">Buscar</button>
                        <button type="button" id="buttLimpiar" name="buttLimpiar" onclick="document.getElementById('frmBuscarCliente').reset(); byId('buttBuscar').click();">Limpiar</button>
                    </td>
                </tr>
                </table>
            </form>
            </td>
        </tr>
        <tr><td><div id="tdListCliente"></div></td></tr>
        <tr>
            <td align="right"><hr>
                <button type="button" class="close" id="butCerraListCliente">Cerrar</button>
            </td>
        </tr>
        </table>
	</div>
</div>
<!-- MUESTRA LOS DATOS DEL VEHICULO Y EL RANGO QUE SE VA OFRECER -->
<div id="divFlotante3" class="root" style="max-height:650px; overflow:auto; cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo3" class="handle">
        <table border="0" ><tr align="left"><td id="tdFlotanteCargarKm"></td></tr></table>
    </div>
    <form id="formCargarKm"  name="formAsignarActividad">
        <table border="0" width="760">
            <tr>
                <td>
                    <fieldset>
                        <legend class="legend">Datos Del Vehiculo</legend>
                        <table>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Placa:</td>
                                <td><input name="numPlaca" id="numPlaca" type="text" class="inputInicial" readonly="readonly"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Marca:</td>
                                <td><input name="nombreMarca" id="nombreMarca" type="text" class="inputInicial" readonly="readonly"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Modelo:</td>
                                <td><input name="nombreModelo" id="nombreModelo" type="text" class="inputInicial" readonly="readonly"/></td>
                            </tr>
                            <tr align="left">
                                <td align="right" class="tituloCampo">Ultimo Km:</td>
                                <td><input name="textUltimoKm" id="textUltimoKm" type="text" class="inputInicial" readonly="readonly"/></td>	
                            </tr>                    
                        </table>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td>
                    <fieldset>
                        <legend class="legend">Rango de Km</legend>
                        <table width="100%">
                            <tr align="center" class="tituloColumna">
                                <td>Rango Km</td>
                                <td>Ofrecer Servicio</td>
                                <td>Servicio Realizado Aqui</td>
                                <td>Enviar Correo</td>
                            </tr>
                            <tr id="trRangoKm"></tr>
                        </table>
                    </fieldset>
                </td>
            </tr>
            <tr align="left">
                <td>Continuar con el Envio Automatico de Correos:
                SI<input id="radioCorreo" name="radioCorreo" type="radio" value="si" checked="checked"/> 
                NO<input id="radioCorreo2" name="radioCorreo" type="radio" value="no" />
                </td>	
             </tr>
            <tr>
                <td align="right"><hr>
                <input name="idRegistroPLaca" id="idRegistroPLaca" type="hidden" readonly="readonly"/>
                <input name="idClienteCotacto" id="idClienteCotacto" type="hidden" readonly="readonly"/>
                    <a class="modalImg" id="VerHistorico" rel="#divFlotante4" onclick="openImg(byId(this.id))">
                        <button type="button" id="butVerHistorico" name="butVerHistorico" onclick="xajax_historicoServicio(0,'','',document.getElementById('idRegistroPLaca').value);">Ver</button>
                    </a>
                    <button type="button" id="btnGuardar" name="btnGuardar" onclick="xajax_guardarKm(xajax.getFormValues(formCargarKm));">Guardar</button>
                    <button type="button" id="butCancelar" name="butCancelar" class="close" onclick="xajax_eliminarTrRango(xajax.getFormValues('formCargarKm'));">Cancelar </button>
                </td>
            </tr>
        </table>
    </form>
</div>
<!-- MUESTRA LOS MOTIVOS DEL VEHICULO POR EL CUAL ENTRO A SERVICIO -->
<div id="divFlotante4" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo4" class="handle">
    	<table border="0" width="460"><tr align="left"><td> Historial de servicio realizados</td></tr></table>
	</div>
    <table width="100%" border="0">
      <tr>
        <td id="tdHistorialServicio"></td>
      </tr>
      <tr>
        <td align="right">
        	<hr />
            <button type="button" id="butCerrar" name="butCerrar" class="close">Cerrar</button>
        </td>
      </tr>
    </table>
</div>
<!-- MUESTRA EL SEGUIMIENTO DE LAS ACTIVIDADES -->
<div id="divFlotante5" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo5" class="handle" align="left">
        <table border="0"><tr align="left"><td>Seguimiento de Actividades</td></tr></table>
    </div>
    <form id="formAsignarActividad2"  name="formAsignarActividad2">
    <table width="760" border="0">
        <tr>
            <td class="tituloCampo" align="right">Tipo de Actividad:</td>
            <td align="left">
            <input type="text" id="txtTipoActividadS" name="txtTipoActividadS" size="30" readonly="readonly" />
        </tr>
         <tr>
            <td class="tituloCampo" align="right"><span class="textoRojoNegrita">*</span>Actividad:</td>
            <td align="left" id="tdListActividadServicio"></td>
        </tr>
        <tr>
            <td class="tituloCampo" align="right"><span class="textoRojoNegrita">*</span>Equipo:</td>
            <td align="left" id="tdEquipoServicio"><select class="inputHabilitado"><option>[ Seleccione ]</option></select></td>
        </tr>
        <tr>
            <td class="tituloCampo" align="right"><span class="textoRojoNegrita">*</span>Integrante:</td>
            <td align="left" id="tdListIntegrantesServicio"><select class="inputHabilitado"><option>[ Seleccione ]</option></select></td>
        </tr>        
        <tr>
            <td class="tituloCampo" align="right">Nombre del Cliente:</td>
            <td align="left">
            <input type="text" id="textIdNombreClienteS" name="textIdNombreClienteS" size="6" readonly="readonly" />
            <input type="text" id="textNombreClienteS" name="textNombreClienteS" size="45" readonly="readonly" /></td>
        </tr>
        <tr>
            <td class="tituloCampo" align="right"><span class="textoRojoNegrita">*</span>Fecha de Asignacion:</td>
            <td align="left">
            	<input type="text" id="fechaAsignacion2" name="fechaAsignacion2" readonly="readonly" class="inputHabilitado" size="20"/>
            </td>
        </tr>
        <tr>
            <td class="tituloCampo" align="right"><span class="textoRojoNegrita">*</span>Hora de Asignacion:</td>
            <td id="tdHoraAsignacion" align="left"><select class="inputHabilitado"><option>[ Seleccione ]</option></select></td>
        <tr>
            <td class="tituloCampo" align="right">Nota:</td>
            <td align="left"><textarea id="notaServicio" name="notaServicio" cols="40" rows="2" class="inputHabilitado"></textarea></td>
        </tr>
        <tr>
            <td colspan="2" align="right">
            <hr />	
          <a class="modalImg" id="VerHistoricoActividad" rel="#divFlotante6" onclick="openImg(byId(this.id))">
            	<button type="button" id="butVerHistorico2" name="butVerHistorico2" onclick="xajax_historicoActividad(0,'','',document.getElementById('textIdNombreClienteS').value);">Ver</button>
           </a>
                <button type="button" id="butGuardar2" name="butGuardar2" onclick="validarFormActPostVenta()">Guardar</button>
                <button type="button" id="butCerrar2" name="butCerrar2" class="close">Cancelar</button>
            </td>
        </tr>
    </table>
    </form>
</div>

<div id="divFlotante6" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo6" class="handle" align="left">
        <table border="0" width="460"><tr align="left"><td>Historial de Actividades</td></tr></table>
    </div>
    <table width="760" border="0">
        <tr>
            <td id="tdHitoricoActividades"></td>
        </tr>
        <tr>
            <td>
                <table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
                <tr>
                    <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                    <td align="center">
                        <table>
                        <tr>
                            <td><img src="../img/iconos/cita_entrada.png"/></td><td>Pendiente</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cita_programada.png"/></td><td>Finalizada</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/cita_entrada_retrazada.png"/></td><td>Finalizada Tarde</td>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/arrow_rotate_clockwise.png"/></td><td>Finalizada Automáticamente</td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right">
            	<hr />
                 <button type="button" id="butCerrar3" name="butCerrar3" class="close">Cerrar</button>
            </td>
        </tr>
    </table>
</div>

<script>
xajax_asignarEmpresa('<?php echo $_SESSION['idEmpresaUsuarioSysGts']; ?>');
xajax_cargarLstTipoEquipo();
<?php finalizaActividadAutomatica(); ?> 
llamarXajax(2, null);
$("ul.tabs").tabs("> .pane", {initialIndex: 1});

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

new JsDatePick({
	useMode:2,
	target:"textFechAsignacion",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});
new JsDatePick({
	useMode:2,
	target:"fechaSelectEquipo",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});

new JsDatePick({
	useMode:2,
	target:"fechaInicio",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});

new JsDatePick({
	useMode:2,
	target:"fechaFin",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});

new JsDatePick({
	useMode:2,
	target:"fechaInicio2",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});

new JsDatePick({
	useMode:2,
	target:"fechaFin2",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});

a = new JsDatePick({
	useMode:2,
	target:"fechaAsignacion2",
	cellColorScheme :"bananasplit",
	dateFormat:"%d-%m-%Y"
});
a.addOnSelectedDelegate(function(){
	xajax_cargarlistHora(xajax.getFormValues('formAsignarActividad2'));
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

var theHandle = document.getElementById("divFlotanteTitulo4");
var theRoot   = document.getElementById("divFlotante4");
Drag.init(theHandle, theRoot); 

var theHandle = document.getElementById("divFlotanteTitulo5");
var theRoot   = document.getElementById("divFlotante5");
Drag.init(theHandle, theRoot); 

var theHandle = document.getElementById("divFlotanteTitulo6");
var theRoot   = document.getElementById("divFlotante6");
Drag.init(theHandle, theRoot); 
</script>