<?php
require_once("../connections/conex.php");
	
session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("crm_asignacion_actividad_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('controladores/xajax/xajax_core/xajax.inc.php');//clase xajax
$xajax = new xajax();//Instanciando el objeto xajax
/*include("../PHPMailer/class.phpmailer.php");
$mail = new PHPMailer();
*/
$xajax->configure('javascript URI', 'controladores/xajax/');//Configuranto la ruta del manejador de script
   $xajax->configure( 'defaultMode', 'synchronous' ); 

include("controladores/ac_crm_asignacion_actividad_list.php"); //contiene todas las funciones xajax

$xajax->processRequest();


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: Sistema ERP :. .: Módulo de CRM :. - Asignar actividad</title>
    <?php $xajax->printJavascript('controladores/xajax/'); ?>
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
    /*FUNCIONES PARA MOSTRAR*/
    //ABRE EL FORMULARIO DE LA ASIGNACION DE ACTIVIDADES
    function abrirNuevaAsignacion(idIntegrante, nombreIntegrante, horaAsignacion, tiene, idActividaEjecucion){
		xajax_listTipoActividad("");
		var tipoEquipo = $('#comboxTipoEquipo').val();
		document.getElementById('formAsignarActividad').reset();
		byId('listTipoActividad').className = 'inputHabilitado';
		byId('tipoFinalizado').className = 'inputHabilitado';
		byId('idClienteHidd').className = 'inputInicial';
		byId('textNombreCliente').className = 'inputInicial';
		byId('textNotaCliente').className = 'inputHabilitado';		
        $('#asignarActividad').show();

		xajax_cargarDatos(idIntegrante, nombreIntegrante, horaAsignacion, tiene, idActividaEjecucion);
	}
    
    //PARA MOSTAR EL LISTADO DE CLIENTE
    function abrirListCliente(){
        $('#divListCleinte').show();
        var tipoActividad = document.getElementById('listTipoActividad').value;
        document.getElementById('textTipoActividad').value = tipoActividad;
	
		xajax_listaCliente(0, "id", "DESC", "||" + tipoActividad);
	}
    	
	//MUESTRA EL ICOMO DE CUMPLEAÑO SEGUN EL CLIENTE
	function mostrarCumpleaño(idCliente){
		//alert(idCliente);
		$('#cumpleaño'  + idCliente).show();
		}	
	
    //EFECTO MOSTRAR Y OCULTAR ACTIVIDADES
    function mostrarOcultatActividad(idEquipo){
			var tipoEquipo = $('#comboxTipoEquipo').val();
			//return alert(tipoEquipo);
        if($('#equipooculto' + idEquipo).is(':visible')){
		//ocultar
			$('#equipooculto' + idEquipo).slideUp();
			//$('#imgMostrar').slideUp();
			$('#imgMostrar'  + idEquipo).show();
			$('#imgOcultar'  + idEquipo).hide();
		}else{
			//mostrar
			$('#equipooculto' + idEquipo).slideDown();
			//$('#imgOcultar').slideDown();
			xajax_integrantesActividad(idEquipo, tipoEquipo)
				//alert(idEquipo);
			$('#imgOcultar'  + idEquipo).show();
			$('#imgMostrar'  + idEquipo).hide();
		}
	}
	
    //PARA MOSTRAR Y OCULTAR LOS DATOS DE LOS VEHICULOS DEL CLIENTE
	 function mostrarOcultarVehiculo(idClienteContacto){
		 var meses = $('#LisMeses').val();
		if($('#clienteContacto' + idClienteContacto).is(':visible')){
		//ocultar
			//$('#clienteContacto' + idClienteContacto).slideUp();
			$('#clienteContacto' + idClienteContacto).hide();
    
			//$('#imgMostrar').slideUp();
			$('#butMostraVehiculo'  + idClienteContacto).show();
			$('#butOcultarVehiculo'  + idClienteContacto).hide();
		}else{
			//mostrar
			//$('#clienteContacto' + idClienteContacto).slideDown();
			$('#clienteContacto' + idClienteContacto).show();
			
			//$('#imgOcultar').slideDown();
			xajax_listMostrarVehiculos('0','','',idClienteContacto+"|"+meses)
				//alert(idEquipo);
			$('#butOcultarVehiculo'  + idClienteContacto).show();
			$('#butMostraVehiculo'  + idClienteContacto).hide();
			}
		}
		
    /*FUNCIONES PARA OPTENER VALORES DE LOS CAMPOS*/
    //OPTENER EL VALOR DEL INPUT
    function tomarFecha(){
		var fecha = $("#mydate").val();
		//alert(fecha);
		$("#fechaAsignacion").val(fecha);

	} 
	
	//CAPTURA EL ID DE LA PLACA
    function tomarIdPlacaRegistro(){
		var idRegistroPlaca = $('#idRegistroPLaca').val();
			//alert(idRegistroPlaca);
			xajax_historicoServicio(0,'','',idRegistroPlaca)
		}
		
	//TOMA EL ID DEL CLIENTE PARA CONSULTAR TODAS LAS ACTIVIDADES DE ESE CLIENTE
	function tomarIdCliente(){
		var IdNombreClienteS = $('#textIdNombreClienteS').val();
			//alert(IdNombreClienteS);
			xajax_historicoActividad(0,'','',IdNombreClienteS);
		}
		
    //SELECCIONA EL IDEQUIPO SELECIONADO	
    function selectEquipo(){
        var equipo = $('#idEquipo').val();
		//alert(equipo);
		return equipo;
	}
	
    //SELECCIONA EL TIPO DE EQUIPO 
	function selectTipoEquipo(){
        var tipoEquipo = $('#comboxTipoEquipo').val();	
		$('#tablaHora').empty();

		xajax_listCombEquipo(4, tipoEquipo);
		xajax_resumen(tipoEquipo);
		xajax_listCombActividad(1,tipoEquipo);
		xajax_listCombActividad(2,tipoEquipo);
		
	}
	
	//SELECCIONA EL TIPO DE ACTIVODA 
    function selectTipoActividad(){
        var tipoActividad = $('#listTipoActividad').val();
		xajax_listActivida(" ",tipoActividad+'|'+1);
		$('#listCliente').attr('disabled', false);
		
		var existeCliente = $('#idClienteHidd').val();
			if(existeCliente != ''){
				$('#idClienteHidd').val('');
				$('#textNombreCliente').val('');
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
	function llamarXajax(numTabs){
		var tipoEquipo = document.getElementById('comboxTipoEquipo').value; 
		if(document.fromTipoEquipo.listEquipo){ //SI EXISTE EL CAMPO
			var idEquipo = document.getElementById('listEquipo').value; 
			}
		var fechaAsignacion = (document.getElementById('mydate').value);
		
		switch(numTabs){
			case 1: //ASIGNAR ACTIVIDAD
			if(tipoEquipo == ""){
				$("ul.tabs").tabs("> .pane", {initialIndex: 1});
					alert('Debe Seleccionar un Tipo Equipo ');
			} else{
				if(idEquipo == ""){
					alert('Debe Seleccionar un Equipo');
						$("ul.tabs").tabs("> .pane", {initialIndex: 1});
				} else {
					xajax_selectIntegrante(fechaAsignacion,idEquipo); 
						$('ul.tabs').tabs('> .pane', {initialIndex: 0});// ACTUALIZA LAS ASIGNACIONES
					}
			}
			  break;
			 	
			  case 2: //RESUMEN
			   xajax_resumen(tipoEquipo);
		  	break;
			
			case 3: //RESUMEN DETALLADO
				 xajax_resumen(tipoEquipo);
				break;
				
			case 4: //ESTADISTICA
					if(tipoEquipo == ""){
						alert('Debe Seleccioanr Una Tipo de Equipo');
							$('ul.tabs').tabs('> .pane', {initialIndex: 1});
						} else {
							xajax_listCombEquipo(1, tipoEquipo);//GENERA EL GRAFICO
							xajax_listCombEquipo(2, tipoEquipo);//GENERA EL GRAFICO 2
							xajax_listCombActividad(1,tipoEquipo);
							xajax_listCombActividad(2,tipoEquipo);
							}
			 	break;
			  		  
			case 5: //SERVICIO ,'|'+tipoEquipo
				xajax_ListServicioPendiente(0,'',''); 
				xajax_combListMeses();
			  break;
			}
		}
		
	//LIMPIA EL INPUT NOMBRE CLIENTE SI CAMBIA DE IPO DE ACTIVDAD
	function limpiarCliente(){
		document.getElementById("idClienteHidd").value = "";
		document.getElementById("textNombreCliente").value = "";
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

	//OCULTA EL BOTON DE LISTA CLIENTE
    function deshabilitarBotCliente(){
		$('#listCliente').attr('disabled', true);
		/*$('#listCliente').hide();*/
		}
		
	function ocultarTabs(tipo){
		//alert(tipo);	
	if(tipo == 'Postventa' || tipo == ''){
			//$('#butOcultarVehiculo'  + idClienteContacto).();hide
			$('#tabsServicio').show();

			}
	}
		
    //VALIDAR CAMPO ACTIVIDAD
    function validarForm(numBut) {
		
		switch(numBut){
			case 1: //PESTAÑA ASIGNAR ACTIVIDAD
						if (validarCampo('listTipoActividad','t','listaExceptCero') == true
						&& validarCampo('listActividad','t','listaExceptCero') == true
						&& validarCampo('idClienteHidd','t','listaExceptCero') == true
						&& validarCampo('textNombreCliente','t','') == true) {
						xajax_guardaActividad(1+'|', xajax.getFormValues('formAsignarActividad'));
							} else {
						validarCampo('listTipoActividad','t','listaExceptCero');
						validarCampo('idClienteHidd','t','listaExceptCero');
						validarCampo('textNombreCliente','t','');
						if(document.formAsignarActividad.listActividad){ //SIE LE CAMPO EXISTE EN EL FORMULARIO
						validarCampo('listActividad','t','listaExceptCero');
							} 
				alert("Los campos en color rojos son obligatorios");
				return false;
						}
					break;
			case 2: //PESTAÑA SERVICIO
			if (validarCampo('textIdNombreClienteS','t','') == true
				&& validarCampo('textNombreClienteS','t','') == true
				&& validarCampo('listEquipoServicio','t','listaExceptCero') == true
				&& validarCampo('listIntegrantesServicio','t','listaExceptCero') == true
				&& validarCampo('listActividadServicio','t','listaExceptCero') == true
				&& validarCampo('fechaAsignacion2','t','') == true
				&& validarCampo('horaSelect','t','listaExceptCero') == true) {
				xajax_guardaActividad(2+'|', xajax.getFormValues('formAsignarActividad2'));
			} else {
				validarCampo('textIdNombreClienteS','t','');
				validarCampo('textNombreClienteS','t','');  
				validarCampo('listEquipoServicio','t','listaExceptCero');
					if(document.formAsignarActividad2.listIntegrantesServicio && document.formAsignarActividad2.horaSelect){ //si existe el campo en el formulario
						validarCampo('listIntegrantesServicio','t','listaExceptCero');
						validarCampo('horaSelect','t','listaExceptCero');
						}
				validarCampo('listActividadServicio','t','listaExceptCero');
				validarCampo('fechaAsignacion2','t','');
				alert("Los campos en color rojos son obligatorios");
				return false;
			}
					break;
					
			case 3:
				break;
			
			case 4://VALIDAR LOS CAMPO PARA GENERAR EL GRAFICO
				if(validarCampo('fechaInicio','t','') == true && validarCampo('fechaFin','t','') == true){ 
					xajax_generarGrafico(xajax.getFormValues(formGenerarGrafico));
				} else {
					validarCampo('fechaInicio','t','');
					validarCampo('fechaFin','t','');
						alert("Los campos en color rojos son obligatorios");
				return false;
					}
				break;
				
			case 5: //VALIDAR LOS CAMPO PARA GENERAR EL GRAFICO 2
				if(validarCampo('fechaInicio2','t','') == true && validarCampo('fechaFin2','t','') == true){ 
					xajax_generarGrafico2(xajax.getFormValues(formGenerarGrafico2));
				} else {
					validarCampo('fechaInicio2','t','');
					validarCampo('fechaFin2','t','');
					alert("Los campos en color rojos son obligatorios");
				return false;
					}
				break;
				
			case 6://VALIDA EL CAMPO DE TIPO DE FINALIZACION
				var tipoFinalizacion =	$('#tipoFinalizado').val();
					if(tipoFinalizacion == ""){
						validarCampo('tipoFinalizado','t','listaExceptCero');
							alert("De seleccionar un valor en el campo señalado");
					} else {
						//alert(tipoFinalizacion);
						xajax_modificarEstatus(xajax.getFormValues('formAsignarActividad')); 
						}
				break;
			}
    }
	
	    //ELIMINAR ACTIVIDAD EN EJECUCION
    function validarEliminarActividad(){
        if(confirm("¿Estas seguro que desea eliminar la actividad?") == true)
			xajax_eliminarActivida(xajax.getFormValues(formAsignarActividad));
	}
	
		//COMPRAR LOS TIPOS DE ACTIVIDA Y EQUIPO
	function validarTipos(){
		var tipoEquipo = $('#comboxTipoEquipo').val();
		var tipoActividad = $('#listTipoActividad').val();
			if(tipoEquipo != tipoActividad){
				alert('No se puede asignar una Atividad de tipo '+tipoActividad+' A un Equipo de tipo '+tipoEquipo);
				} else {
					//alert("Son iguales");
				validarForm(1);
					}
		}

    </script>
	
    <!-- dateinput styling -->
    <link rel="stylesheet" type="text/css" href="../js/jquerytools/large.css"/>
	<style>
    #calendar {
        height:250px;
        width:200px;
        float:left;
    }
     
    #theday {
        -moz-border-radius:5px;
        background-color:#36387B;
        color:#FFFFFF;
        float:left;
        font-size:90px;
        height:80px;
        line-height:50px;
        margin-top:30px;
        padding:60px;
        text-shadow:0 0 5px #DDDDDD;
        width:117px;
    }
     
    #theday span {
        display:block;
        font-size:16px;
        text-align:center;
    }
    
    /* ESTILOS DE LAS TABLAS DE RESULTADO */
    .tabla_resultado td{
    }
    
    .tabla_resultado tr:last-child>td:last-child{
        border-left:hidden;
    }
    
    .tabla_resultado tr:last-child>td {
    padding: 4px;
    }
    .thSinRegistro{ /* ROJO */
        background-color:#FFEEEE;
        border:1px solid #FF0000;
        color:#000000;
    }
    </style>

</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php include("banner_crm.php"); ?></div>
    
    <div id="divInfo" class="print">
        <table width="100%" border="0"> <!--tabla principa-->
        <tr>
            <td class="tituloPaginaCrm">Asignacion de actividades</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
        	<td>
            </td>
        </tr>
        </table>
        
        <table width="100%" border="1" class="tabla" >
		<tr>
            <td valign="top" width="10%">
                <table width="100%" border="0">
                <tr>
                    <td colspan="2">
                        <div id="calendar">
                        <input type="date" id="mydate" name="mydate" value="<?php echo date("d-m-Y") ?>"/>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="tituloArea" colspan="2">Leyenda</td>
                </tr>
                <tr align="left">
                    <td align="center"><img src="../img/iconos/ico_aceptar_azul.png"/></td>
                    <td width="100%">Asignada</td>
                </tr>
                <tr align="left">
                    <td align="center"><img src='../img/cita_programada_incumplida.png'/></td>
                    <td>Atrasado</td>
                </tr>
                <tr align="left">
                    <td align="center"><img src='../img/iconos/ico_aceptar.gif'/></td>
                    <td>Finalizada</td>
                </tr>
                <tr align="left">
                    <td align="center"><img src="../img/cita_entrada_retrazada.png"/></td>
                    <td>Finalizado tarde</td>
                </tr>
                <tr align="left">
                    <td align="center"><img src="../img/iconos/arrow_rotate_clockwise.png"/></td>
                    <td>Finalizado Auto</td>
                </tr>
                </table>
            </td>
            <td valign="top">
            <form id="fromTipoEquipo" name="fromTipoEquipo">
                <table width="60%" border="0">
                <tr align="left">
                	<td  align="right" class="tituloCampo" width="10%">Tipo de Equipo:</td>
                    <td id="tdTipoEquipo" width="5%"></td>
                    <td align="right" class="tituloCampo" width="10%">Equipos:</td>
                    <td id="tdListEquipo" width="5%">
                        <select class="inputHabilitado">
                            <option value="">[ Seleccione ]</option>
                        </select>
                    </td>
                </tr>
                </table>
             </form>
                <br /><br />
                <div id="tabla"></div>
                 <br /><br />
                <div class="wrap">
                    <!-- the tabs -->
                    <ul class="tabs">
                        <li><a href="#" onclick="llamarXajax(1);">Asignar Actividad</a></li>
                        <li><a href="#" onclick="llamarXajax(2);">Resumen</a></li><!--xajax_resumen("");-->
                        <li><a href="#" onclick="llamarXajax(3);">Resumen Detallado</a></li><!--xajax_resumen("");-->
                        <li><a href="#" onclick="llamarXajax(4);">Estadisticas</a></li>
                        <li><a href="#" id="tabsServicio" onclick="llamarXajax(5);" style="display:none" >Servicio</a></li>
                    </ul>
                    
                    <div id="tablaHora" class="pane"> <!--Asignar Actividad-->
                    </div>
                    
                    <!-- tab "panes" Resume -->
                    <div class="pane"> <!--Resumen-->
                        <table width="100%" border="0" align="center">
                          <tr>
                            <td id="tdActividadesDiaActual" valign="top"></td>
                            <td id="tdActividadesAtrazadasDia" valign="top"></td>
                            <td id="tdActividadesDiaSiguiente" valign="top"></td>
                          </tr>
                        </table>                        
                    </div>
                    
                    <!-- tab "panes" Resumen detallado-->
                    <div class="pane"> 
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
                    <form id="formGenerarGrafico" name="formGenerarGrafico">
                        <table border="0" align="center">
                        	<caption> <h3 class="tituloCampo">Gráfico de Actividades</h3> </caption>
                          <tr>
                            <td id="" class="tituloCampo" align="right">Equipos:</td>
                            <td id="tdListEquipoGrafico" align="left"> 
                                <select class="inputHabilitado">
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td id="" class="tituloCampo" align="right">Integrantes:</td>
                            <td id="tdListIntegrantesGrafico" align="left"  colspan="3">
                                <select class="inputHabilitado">
                                    <option value="">[ Seleccione ]</option>
                                </select>

                            </td>
                          </tr>
                          <tr>
                          	<td id="" class="tituloCampo" align="right">Actividades:</td>
                            <td id="tdListTipoActividaGrafico" align="left"></td>
                            <td id="" class="tituloCampo" align="right">Desde la fecha:</td>
                            <td id=""><input type="text" id="fechaInicio" name="fechaInicio" readonly="readonly" class="inputHabilitado" size="20"/></td>
                            <td id="" class="tituloCampo" align="right">Hasta la fecha:</td>
                            <td id=""><input type="text" id="fechaFin" name="fechaFin" readonly="readonly" class="inputHabilitado" size="20"/></td>
                            </tr>
                            <tr>
                            <td id="" align="right" colspan="6">
                                <button type="button" id="butBuscar" name="butBuscar" onclick="validarForm(4);">Buscar</button>
                                <button type="button" id="butLimpiar" name="butLimpiar" onclick="this.form.reset(); limpiarGrafico('divGraficos1');">Limpiar</button>
                            </td>
                            </tr>
                        </table> 
                       </form> 
                        <div id="divGraficos1"></div>
                      </div>
                      <br />
                        <br />
                        <br />
                        <br />
                      <div id="divGeneralGrafico2">
                    <form id="formGenerarGrafico2" name="formGenerarGrafico2">
                        <table border="0" align="center">
                        	<caption> <h3 class="tituloCampo">Gráfico de Desempeño</h3> </caption>
                          <tr>
                          	<td id="" class="tituloCampo" align="right">Equipos:</td>
                            <td id="tdListEquipoGrafico2" align="left">
                                <select class="inputHabilitado">
                                    <option value="">[ Seleccione ]</option>
                                </select>
                            </td>
                            <td class="tituloCampo" align="right">Desde la fecha:</td>
                            <td><input type="text" id="fechaInicio2" name="fechaInicio2" readonly="readonly" class="inputHabilitado" size="20"/></td>
                            <td class="tituloCampo" align="right">Hasta la fecha:</td>
                            <td><input type="text" id="fechaFin2" name="fechaFin2" readonly="readonly" class="inputHabilitado" size="20"/></td>
                            </tr>
                            <tr>
                            <td class="tituloCampo" align="right">Actividades:</td>
                            <td id="tdListTipoActividaGrafico2" ></td>                            
                            <td align="right" colspan="6">
                                <button type="button" id="butBuscar2" name="butBuscar2" onclick="validarForm(5);">Buscar</button>
                                <button type="button" id="butLimpiar2" name="butBuscar2" onclick="this.form.reset(); limpiarGrafico('divGraficos2');">Limpiar</button>
                            </td>
                            </tr>
                        </table> 
                       </form> 
                        <div id="divGraficos2"></div>
                      </div>                           
                    </div>
                    
                    <div class="pane"> <!--servicio--->
                    	<form  id="formBuscar" name="formBuscar" onsubmit="retunr false" style="margin:0">
                        <table align="right" border="0">
                            <tr align="left">
                                <td class="tituloCampo" align="right"  width="120">Entrada a Taller Hace:</td>
                                <td id="tdConboListMese"></td>
                                <td colspan="2">
          							<!--<a class="modalImg" id="SeleccionarActividad" rel="#divActividades" onclick="openImg(byId(this.id))">-->
                                      	<button type="button" id="butAsignarAutomatico" name="butAsignarAutomatico" onclick="
                                        xajax_asignarAutomatico(xajax.getFormValues('formListServicioPendiente'));"> <!---->
                                        Asignar Automatico
                                    	</button>
                                <!--    </a>-->
                                </td>
                            </tr>
                            <tr align="left">
                                <td class="tituloCampo" align="right"  width="120">Criterio:</td>
                                <td ><input type="text" id="textCriterio" name="textCriterio" class="inputHabilitado" size="20"/></td>
                                <td>
                                    <button type="button" id="butBuscarServicio" name="butBuscarServicio" onclick="xajax_buscarClienteServicio(xajax.getFormValues('formBuscar'));">
                                        Buscar
                                    </button>
                                </td>
                                <td>
                                    <button type="button" id="butLimpiaServicior" name="<strong>butLimpiar</strong>" onclick="this.form.reset(); byId('butBuscarServicio').click();">
                                        Limpiar
                                    </button>
                                </td>
                            </tr>
                        </table>
                        </form>
                        <br /><br /><br /><br /><br />

                        <div id="divServicio">
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        </table> <!--fin tabla principal-->
    </div> <!-- fin contenedor interno-->
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div> <!--fin del contenedor general-->
</body>
</html>
<!--ASIGNACION DE ACTIVIDAD-->
<div id="asignarActividad" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="tituloAsignarActividad" class="handle">
    	<table>
            <tr>
                <td id="tdFlotanteTitulo2" width="100%">Asignacion de actividad</td>
            </tr>
        </table>
    </div>
    
<form id="formAsignarActividad"  name="formAsignarActividad">
    <table border="0" width="560">
    <tr align="left">
        <td align="right" class="tituloCampo">Tipo de Actividad:</td>
        <td id="tdListTipoActividad"> </td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Actividad:</td>
        <td id="tdListActividad"></td>
    </tr>
    <tr id="trEstadoActividad" align="left" style="display:none">
        <td align="right" class="tituloCampo">Estado de actividad:</td>
        <td id="tdcomboxEstado"></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Nombre del vendedor:</td>
        <td><input name="nombreVendedor" id="nombreVendedor" type="text" readonly="readonly"/></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Fechas de asignacion:</td>
        <td><input name="fechaAsignacion" id="fechaAsignacion" type="text" readonly="readonly"/></td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Hora de asignacion:</td>
        <td><input name="horaSeleccionada" id="horaSeleccionada" type="text" readonly="readonly"/></td>
    </tr>
    <tr id="tdNombreCliente" align="left">
        <td align="right" class="tituloCampo">Nombre del cliente:</td>
        <td>
        	<table cellpadding="0" cellspacing="0">
            <tr>
                <td><input type="text" id="idClienteHidd" name="idClienteHidd" readonly="readonly" size="6" style="text-align:right;"/></td>
                <td>
                <a class="modalImg" rel="#divListCleinte" onclick="openImg(this);">
                   <button type="button" id="listCliente" name="listCliente" onclick="abrirListCliente();" title="Seleccionar cliente" style="display:none" >
                   		<img src="../img/cita_add.png"/>  
                   </button>
                </a>
                </td>
                <td><input type="text" id="textNombreCliente" name="textNombreCliente" readonly="readonly" size="45"/></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr id="trTipoFinalizacion" align="left" style="display:none">
        <td  align="right" class="tituloCampo" >Tipo de finalizacion</td>
        <td>
        <select id='tipoFinalizado' name='tipoFinalizado' onchange=''> 
                <option value=''>[ Seleccione ]</option>
                <option value='0'>No Efectiva</option>
                <option value='1'>Efectiva</option>
        	</select>
        </td>
    </tr>
    <tr align="left">
        <td align="right" class="tituloCampo">Nota:</td>
        <td>
        <!--<input name="" id="textNotaCliente" type="text" size="20" maxlength="45"/>-->
        <textarea id="textNotaCliente" name="textNotaCliente" cols="40" rows="2" class="inputHabilitado"></textarea>
        </td>
         </tr>
        
    <tr>
        <td align="right" colspan="2"><hr>
            <input name="exiteActivida" id="exiteActivida" type="hidden"/>
            <input name="idIntegrante" id="idIntegrante" type="hidden"/>
            <input name="horaAsignacion" id="horaAsignacion" type="hidden"/>
            <input name="actividaEjecucion" id="actividaEjecucion" type="hidden"/>
            <button type="button" id="buttFinalizar" name="buttFinalizar" onclick="validarForm(6);" style="display:none">
            <table align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td>&nbsp;</td>
                    <td><img src="../img/iconos/accept.png"/></td>
                    <td>&nbsp;</td>
                    <td>Finalizar Actividad</td>
                </tr>
            </table>
            </button>
            <button type="button" id="botEliminar" name="botEliminar" onclick="validarEliminarActividad()" style="display:none">
            <table align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td>&nbsp;</td>
                    <td><img src="../img/iconos/cross.png"/></td>
                    <td>&nbsp;</td>
                    <td>Eliminar Actividad</td>
                </tr>
            </table>
            </button>
            <button type="button" id="btnGuardar" name="btnGuardar" onclick="validarTipos();">
            <table align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td>&nbsp;</td>
                    <td><img src="../img/iconos/ico_save.png"/></td>
                    <td>&nbsp;</td>
                    <td>Guardar</td>
                </tr>
            </table>
            </button>
            <button type="button" id="butCancelarAsignacion" name="butCancelarAsignacion" class="close" onclick="deshabilitarBotCliente()" >
            <table align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td>&nbsp;</td>
                    <td><img src="../img/iconos/ico_error.gif"/></td>
                    <td>&nbsp;</td>
                    <td>Cancelar</td>
                </tr>
            </table>
            </button>
		</td>
    </tr>
    </table>
</form>
</div>
<!--LISTA DE CLIENTE OCULTOS-->
<div id="divListCleinte" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="tituloDivListCleinte" class="handle"><table><tr><td id="tituloTdListCleinte" width="100%">Clientes</td></tr></table></div>
    
    <table border="0" width="760">
    <tr>	
        <td>
        <form id="frmBuscarCliente" name="frmBuscarCliente">
            <table align="right" border="0">
            <tr>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="textCriterio" name="textCriterio"/></td>
                <td>
                	<input type="hidden" id="textTipoActividad" name="textTipoActividad"/>
                    <button type="button" id="buttBuscar"  name="buttBuscar" onclick="xajax_buscarCliente(xajax.getFormValues('frmBuscarCliente'));">Buscar</button>
                    <button type="button" id="buttLimpiar" name="buttLimpiar" onclick="this.form.reset(); byId('buttBuscar').click();">Limpiar</button>
				</td>
            </tr>
            </table>
        </form>
        </td>
    </tr>
    <tr>
        <td id="tdListCliente"></td>
    </tr>
    <tr>
        <td align="right"><hr>
            <button type="button" class="close" id="butCerraListCliente">Cancelar</button>
        </td>
    </tr>
    </table>
</div>
<!-- MUESTRA LOS DATOS DEL VEHICULO Y EL RANGO QUE SE VA OFRECER -->
<div id="cargarKm" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="" class="handle">
        <table border="0" width="460">
            <tr align="left">
            <td id="tdFlotanteCargarKm"></td>
            </tr>
        </table>
    </div>
<form id="formCargarKm"  name="formAsignarActividad">
    <table border="0">
        <tr align="left">
            <td align="right" class="tituloCampo">Placa:</td>
            <td><input name="numPlaca" id="numPlaca" type="text" readonly="readonly"/></td>
        </tr>
        <tr align="left">
            <td align="right" class="tituloCampo">Marca:</td>
            <td><input name="nombreMarca" id="nombreMarca" type="text" readonly="readonly"/></td>
        </tr>
        <tr align="left">
            <td align="right" class="tituloCampo">Modelo:</td>
            <td><input name="nombreModelo" id="nombreModelo" type="text" readonly="readonly"/></td>
        </tr>
        <tr align="left">
            <td align="right" class="tituloCampo">Ultimo Km:</td>
            <td><input name="textUltimoKm" id="textUltimoKm" type="text" readonly="readonly"/></td>	
        </tr>
        <tr align="left">
            <td align="center" class="tituloCampo" colspan="2">Ofrecer Servicio</td>
        </tr>
        <tr>
            <td align="center" id="" colspan="2">
            	<div id="divRangoKm" style="max-height:150px; overflow:auto;"> </div>
            </td>	
        </tr>
        <tr>
            <td align="right" id="" colspan="2">Continuar con el Envio Automatico de Correos:
            SI<input id="radioCorreo" name="radioCorreo" type="radio" value="si" checked="checked"/> 
            NO<input id="radioCorreo2" name="radioCorreo" type="radio" value="no" />
            </td>	
         </tr>
        <tr>
            <td align="right" colspan="2"><hr>
            <input name="idRegistroPLaca" id="idRegistroPLaca" type="hidden" readonly="readonly"/>
            <input name="idClienteCotacto" id="idClienteCotacto" type="hidden" readonly="readonly"/>
                <a class="modalImg" id="VerHistorico" rel="#hitoricoServicio" onclick="openImg(byId(this.id))">
                    <button type="button" id="butVerHistorico" name="butVerHistorico" onclick="tomarIdPlacaRegistro();"><!---->
                        <table align="center" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>&nbsp;</td>
                                <td><img src="../img/iconos/ico_view.png"/></td>
                                <td>&nbsp;</td>
                                <td>Ver</td>
                            </tr>
                        </table>
                    </button>
                </a>
                <button type="button" id="btnGuardar" name="btnGuardar" onclick="xajax_guardarKm(xajax.getFormValues(formCargarKm));">
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_save.png"/></td>
                            <td>&nbsp;</td>
                            <td>Guardar</td>
                        </tr>
                    </table>
                </button>
                <button type="button" id="butCancelar" name="butCancelar" class="close">
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_error.gif"/></td>
                            <td>&nbsp;</td>
                            <td>Cancelar</td>
                        </tr>
                    </table>
                </button>
            </td>
        </tr>
    </table>
</form>
</div>
<!-- MUESTRA LOS MOTIVOS DEL VEHICULO POR EL CUAL ENTRO A SERVICIO -->
<div id="hitoricoServicio" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="titHistoricoServicio" class="handle">
    	<table border="0" width="460">
            <tr align="left">
            <td> Historial de servicio realizados</td>
            <td align="right"> <!--<img id="cerrar" class="puntero close" title="Cerrar" src="../img/iconos/cross.png">--> </td>
            </tr>
        </table>
	</div>
    
    <table width="100%" border="0">
      <tr>
        <td id="tdHistorialServicio"></td>
      </tr>
      <tr>
        <td align="right">
        	<hr />
            <button type="button" id="butCerrar" name="butCerrar" class="close">
                <table border="0" align="center" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td align="right"><img src="../img/iconos/ico_error.gif"/></td>
                        <td>&nbsp;</td>
                        <td align="left">Cerrar</td>
                    </tr>
                </table>
            </button>
        </td>
      </tr>
    </table>
</div>
<!-- MUESTRA EL SEGUIMIENTO DE LAS ACTIVIDADES -->
<div id="divSeguimientoActividades" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="titDivSeguimientoActividades" class="handle" align="left"></div>
    <form id="formAsignarActividad2"  name="formAsignarActividad2">
    <table width="460" border="0">
        <tr>
            <td class="tituloCampo" align="right">Nombre del Cliente:</td>
            <td align="left">
            <input type="text" id="textIdNombreClienteS" name="textIdNombreClienteS" size="5" readonly="readonly" />
            <input type="text" id="textNombreClienteS" name="textNombreClienteS" size="35" readonly="readonly" /></td>
        </tr>
        <tr>
            <td class="tituloCampo" align="right">Equipo:</td>
            <td align="left" id="tdEquipoServicio"></td>
        </tr>
        <tr>
            <td class="tituloCampo" align="right">Integrante:</td>
            <td align="left" id="tdListIntegrantesServicio"></td>
        </tr>        
        <tr>
            <td class="tituloCampo" align="right">Actividad:</td>
            <td align="left" id="tdListActividadServicio"></td>
        </tr>
        <tr>
            <td class="tituloCampo" align="right">Fecha de Asignacion:</td>
            <td align="left">
            	<input type="text" id="fechaAsignacion2" name="fechaAsignacion2" readonly="readonly" class="inputHabilitado" size="20"/> <!--tomarFecha(2)-->
            </td>
        </tr>
        <tr>
            <td class="tituloCampo" align="right">Hora de Asignacion:</td>
            <td id="tdHoraAsignacion" align="left"> </td>
        <tr>
            <td class="tituloCampo" align="right">Nota:</td>
            <td align="left"><textarea id="notaServicio" name="notaServicio" cols="40" rows="2" class="inputHabilitado"></textarea></td>
        </tr>
        <tr>
            <td colspan="2" align="right">
            <hr />	
          <a class="modalImg" id="VerHistoricoActividad" rel="#divHitoricoActividades" onclick="openImg(byId(this.id))">
            	<button type="button" id="butVerHistorico2" name="butVerHistorico2" onclick="tomarIdCliente();">
                    <table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                            <td align="right"><img src="../img/iconos/ico_view.png"/></td>
                            <td>&nbsp;</td>
                            <td align="left">Ver</td>
                        </tr>
                    </table>
                </button>
           </a>
                <button type="button" id="butGuardar2" name="butGuardar2" onclick="validarForm(2)">
                    <table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                            <td align="right"><img src="../img/iconos/ico_save.png"/></td>
                            <td>&nbsp;</td>
                            <td align="left">Guardar</td>
                        </tr>
                    </table>
                </button>
                <button type="button" id="butCerrar2" name="butCerrar2" class="close">
                    <table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                            <td align="right"><img src="../img/iconos/ico_error.gif"/></td>
                            <td>&nbsp;</td>
                            <td align="left">Cancelar</td>
                        </tr>
                    </table>
                </button>
            </td>
        </tr>
    </table>
    </form>
</div>

<div id="divHitoricoActividades" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="titDivHitoricoActividades" class="handle" align="left">
        	<table border="0" width="460">
            <tr align="left">
            <td> Historial de Actividades</td>
            <td align="right"> <!--<img id="cerrar" class="puntero close" title="Cerrar" src="../img/iconos/cross.png">--> </td>
            </tr>
        </table>
    </div>
    <table width="760" border="0">
        <tr>
            <td id="tdHitoricoActividades"></td>
        </tr>
        <tr>
            <td>
<table width="100%" class="divMsjInfo2">
  <tr>
    <td align="left"><img width="25" src="../img/iconos/ico_info.gif"></td>
    <td align="center">
        <table align="center">
            <tr>
                <td><img src="../img/iconos/ico_aceptar_azul.png"/>Asignada</td>
                <td>&nbsp;</td>
                <td><img src='../img/cita_programada_incumplida.png'/>Atrasado</td>
                <td>&nbsp;</td>
                <td><img src='../img/iconos/ico_aceptar.gif'/>Finalizada</td>
                <td>&nbsp;</td>
                <td><img src="../img/cita_entrada_retrazada.png"/>Finalizado tarde</td>
                <td>&nbsp;</td>
                <td><img src="../img/iconos/arrow_rotate_clockwise.png"/>Finalizado Auto</td>
                <td>&nbsp;</td>
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
                 <button type="button" id="butCerrar3" name="butCerrar3" class="close">
                    <table border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                            <td align="right"><img src="../img/iconos/ico_error.gif"/></td>
                            <td>&nbsp;</td>
                            <td align="left">Cancelar</td>
                        </tr>
                    </table>
                </button>

            </td>
        </tr>
    </table>
</div>

<div id="divActividades" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="titDivActividades" class="handle" align="left">Seleccione actividad</div>
    
    <table width="100%" border="1">
        <tr>
            <td>ESTAS</td>
        </tr>
        <tr>
            <td align="right">
                <button type="button" id="butCancelar" name="butCancelar" class="close">
                    <table align="center" cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                            <td><img src="../img/iconos/ico_error.gif"/></td>
                            <td>&nbsp;</td>
                            <td>Cancelar</td>
                        </tr>
                    </table>
                </button>
            </td>
        </tr>
    </table>
</div>

<script>
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

//IMPUT CALENDARIO FECHA INICIO
	window.onload = function(){
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
   xajax_listCombHora(xajax.getFormValues(formAsignarActividad2));
});
	};

//PARA EL CALENDARIO				
$.tools.dateinput.localize("es", {
	months: 'Enero,Febrero,Marzo,Abril,Mayo,Junio,Julio,Agosto,Septiembre,Octubre,Noviembre,Deciembre',
	shortMonths:  'Ene,Feb,Mzo,Abr,May,Jun,Jul,Ago,Set,Oct,Nov,Dic',
	days:         'Domingo,Lunes,Martes,Miercoles,Jueves,Viernes,Sabado',
	shortDays:    'Dom,Lun,Mar,Mie,Jue,Vie,Sab'
});

// initialize dateinput
$(":date").dateinput( {
	value: new Date(),
	
	lang: 'es',
	format: 'dd-mm-yyyy',
	// closing is not possible
	onHide: function()  {
		return false;
	},
	// when date changes update the day display
	/*change: function(e, date)  {
		//$("#theday").html(this.getValue("dd<span>mmmm yyyy</span>"));
		dia = date.getDate();
		 mes = date.getMonth()+1;
		 anio = date.getFullYear();
			idEquipo = document.getElementById('idEquipo').value;
			fecha = dia+"/"+mes+"/"+anio;
			if (idEquipo == " "){
				alert("Debes seleccionar el equipo primero");
			}else{
		  xajax_selectIntegrante(fecha, idEquipo);
			}
	}*/
// set initial value and show dateinput when page loads
}).data("dateinput").show();

$(":input.date").change(function(event, date) {
	dia = date.getDate();
	mes = date.getMonth()+1;
	anio = date.getFullYear();
	fecha = dia+"-"+mes+"-"+anio;
	
	llamarXajax(1);
});

// perform JavaScript after the document is scriptable.
$(function() {
	$("ul.tabs").tabs("> .pane", {initialIndex: 1});
});

xajax_finalizaActividadAutomatica(); //ejecuto la funcion finalizarActividadAutomatica 
xajax_comboxTipoEquipo("");
xajax_resumen("");


var theHandle = document.getElementById("tituloAsignarActividad");
var theRoot   = document.getElementById("asignarActividad"); 
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("tituloDivListCleinte");
var theRoot   = document.getElementById("divListCleinte");
Drag.init(theHandle, theRoot);

var theHandle = document.getElementById("tdFlotanteCargarKm");
var theRoot   = document.getElementById("cargarKm");
Drag.init(theHandle, theRoot); 

var theHandle = document.getElementById("titHistoricoServicio");
var theRoot   = document.getElementById("hitoricoServicio");
Drag.init(theHandle, theRoot); 

var array_js = {};

function crearGrafico(datos,nombreActividad,nombreIntegrante){

var mesesLetras = new Array();//Meses a mostrar
var estatusFinalizado = new Array();//todos los datos finalizado
var estatusAsignado = new Array();// todos los datos asignados
var estatusFinalizadoTarde = new Array();//todos los datos finalizado tarde
var estatusFinalizadoAuto = new Array();//todos los finalizados auto

  for( var property in datos ){
	  mesesLetras.push(mesesDiminutivo(property));//llenado array meses
	  
	  var separacion = datos[property].split("|");
	
	  estatusFinalizado.push(parseFloat(separacion[0]));
	  estatusAsignado.push(parseFloat(separacion[1]));
	  estatusFinalizadoTarde.push(parseFloat(separacion[2]));
	  estatusFinalizadoAuto.push(parseFloat(separacion[3]));
	  
	 // console.log( property +" - "+datos[property] );
}

	       // $('#divGraficos').
		var grafico = new Highcharts.Chart({
             chart: {
                renderTo: 'divGraficos1',
                type: 'column'
            },
            title: {
                text: nombreActividad
            },
            subtitle: {
                text: nombreIntegrante
            },
            xAxis: {
                categories: mesesLetras
            },
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

array_js = {};

}

var array_js2 = {};
var array_jsIntegrantes = {};

function crearGrafico2(nombreEquipo,nombreActividad,rangoFecha){

var nombresIntegrantes = new Array();
var tipoFinalizacionEfectivo = new Array();
var tipoFinalizacionNoEfectivo = new Array();

for( var property in array_jsIntegrantes ){
	nombresIntegrantes.push(array_jsIntegrantes[property]);
}

if(nombreActividad != null && nombreActividad != ""){
	nombreEquipo = nombreEquipo+" - "+nombreActividad;
}

for( var property in array_js2 ){

   var separacion = array_js2[property].split("|");

   tipoFinalizacionEfectivo.push(parseFloat(separacion[0]));
   tipoFinalizacionNoEfectivo.push(parseFloat(separacion[1]));

}
		var grafico = new Highcharts.Chart({
             chart: {
                renderTo: 'divGraficos2',
                type: 'bar'
            },
            title: {
                text: nombreEquipo
            },
            subtitle: {
                text: rangoFecha
            },
            xAxis: {
                categories: nombresIntegrantes
            },
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
				color: '#AA4643',
				 name: 'No Efectivo',
                data: tipoFinalizacionNoEfectivo
            }, {
                color: '#4572A7',
                name: 'Efectivo',
                data: tipoFinalizacionEfectivo
    
            }]
        });

array_js2 = {};
array_jsIntegrantes = {};

}

function mesesDiminutivo(numeroMes){
	
	switch(parseInt(numeroMes)){
	case 1:
	  numeroMes = "Ene";
	  break;
	case 2:
	  numeroMes = "Feb";
	  break;
	case 3:
	  numeroMes = "Mar";
	  break;
	case 4:
	  numeroMes = "Abr";
	  break;
	case 5:
	  numeroMes = "May";
	  break;
	case 6:
	  numeroMes = "Jun";
	  break;
	case 7:
	  numeroMes = "Jul";
	  break;
	case 8:
	  numeroMes = "Ago";
	  break;
	case 9:
	  numeroMes = "Sep";
	  break;
	case 10:
	  numeroMes = "Oct";
	  break;
	case 11:
	  numeroMes = "Nov";
	  break;
	case 12:
	  numeroMes = "Dic";
	  break;
	default:
	 numeroMes = numeroMes;
	}
	
	return numeroMes;
}
</script>