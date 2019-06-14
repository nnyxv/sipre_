<?php
 //error_reporting(E_ALL);
 //ini_set("display_errors", 1); 
 @session_start();
 
include("../connections/conex.php");

include ("controladores/ac_sa_sistemamedidas.php");
include ("controladores/ac_iv_general.php");

$xajax->processRequest(); 
/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("sa_sistemamedidas"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
}
/* Fin Validación del Módulo */
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
    <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Sistema de Medidas</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
    
    <link rel="stylesheet" type="text/css" href="../js/domDrag.css">
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
	<!--<script type="text/javascript" language="javascript" src="../js/mootools.v1.11.js"></script>-->
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
		

<!--		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>-->
		<script type="text/javascript" src="../js/jquery.js"></script>
		<script type="text/javascript">

    $(document).ready(function() {
//        grafico1();
//        grafico2();
    }       
     );
         
         //var chart;
    
    /* FUNCIONES PARA TABLA MECANICO */
    
    function crearGrafico(id_mecanico,nombre_mecanico,fechas, ocupacion, eficiencia, productividad) {
        //var arrayRapido = [parseInt("49.9"), 71.5, 106.4, 129.2, 144.0, 176.0, 135.6, 148.5, 216.4, 194.1, 95.6, 54.4];
       // console.debug(arrayRapido);
       //el window se refiere a variable global que se crea dinamicamente string + id_mecanico = "chart3" unico para cada grafico
        window["chart"+id_mecanico] = new Highcharts.Chart({
            chart: {
                renderTo: 'container'+id_mecanico,
                type: 'column',
                zoomType: 'xy'
                
            },
            credits: {
                enabled: false
              },
            lang: {
                contextButtonTitle: "Chart context menu",
                decimalPoint: ",",
                downloadJPEG: "Descarga JPEG imagen",
                downloadPDF: "Descarga PDF documento",
                downloadPNG: "Descarga PNG imagen",
                downloadSVG: "Descarga SVG vector imagen",
                loading: "Cargando...",
                printChart: "Imprimir Gráfico"
                },
            title: {
                text: "Control de Taller"
            },
            subtitle: {
                text: nombre_mecanico
            },
            xAxis: {
                categories: fechas
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Porcentaje (%)'
                }
            },
            legend: {
                layout: 'horizontal',
                backgroundColor: '#FFFFFF',
                align: 'center',
                verticalAlign: 'bottom',
                //x: 150,
                //y: 0,
                floating: false,
                shadow: true,
                adjustChartSize: true
                
            },
            tooltip: {
                formatter: function() {
                    return ''+
                        this.x +': '+ this.y +' %';
                }
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0,
                    lineWidth: 1
                    //, pointWidth: 40
                }
            },
                series: [{
                name: '% Ocupacion',
                data: ocupacion                
    
            }, {
                name: '% Eficiencia',
                data: eficiencia
    
            }, {
                name: '% Productividad',
                data: productividad
    
            }]
        });
        //console.log(window["chart"+id_mecanico]);
        
        // Botones creados por javascript para cambiar tamaños
         var $container = $('#container'+id_mecanico);
            //var chart = $container.highcharts(),
            legenda = $('.highcharts-legend');
                origChartWidth = $('#container'+id_mecanico).width();
                origChartHeight = $('#container'+id_mecanico).height();
                chartWidth = origChartWidth;
                chartHeight = origChartHeight;
            $('<button class="boton_'+id_mecanico+' noprint">+</button>').insertBefore($container).click(function() {
                window["chart"+id_mecanico].setSize(chartWidth *= 1.1, chartHeight *= 1.1);
            });
            $('<button class="boton_'+id_mecanico+' noprint">-</button>').insertBefore($container).click(function() {
                window["chart"+id_mecanico].setSize(chartWidth *= 0.9, chartHeight *= 0.9);
            });
            $('<button class="boton_'+id_mecanico+' noprint">1:1</button>').insertBefore($container).click(function() {
                chartWidth = origChartWidth;
                chartHeight = origChartHeight;
                window["chart"+id_mecanico].setSize(origChartWidth, origChartHeight);
            });
            $('<button class="boton_'+id_mecanico+' noprint">Pequeño</button>').insertBefore($container).click(function() {               
                window["chart"+id_mecanico].setSize(400, 300);
                
            });
            $('<button class="boton_'+id_mecanico+' noprint">Mediano</button>').insertBefore($container).click(function() {               
                window["chart"+id_mecanico].setSize(600, 400);
                
            });
            $('<button class="boton_'+id_mecanico+' noprint">Grande</button><div id="espaciado"><br><br></div>').insertBefore($container).click(function() {               
                window["chart"+id_mecanico].setSize(900, 500);
                
            });
             
    }
    
    /**
     * Funcion que se encarga de ocultar el grafico despues de que se muestra
     * @param {int} id_mecanico //identificador unico
     * @returns {undefined} //no devuelve nada
     */
    
    function ocultar(id_mecanico){
        
        $("#container"+id_mecanico).hide();
        $(".boton_"+id_mecanico).remove();
        $("#ocultar"+id_mecanico).hide();
        $("#ver"+id_mecanico).show();
        $("#espaciado").remove();
    
    }
    
    /**
     * Esta funcion se encarga de tomar todos los valores necesarios para generar el grafico
     * @param {string} nombre_mecanico //Es el nombre del mecanico que luego se mostrara en el grafico
     * @param {int} id_mecanico //identificador unico del mecanico
     * @returns {undefined} //no devuelve nada, solo llama a la funcion generadora de grafico
     */
    
    function tomarValores(nombre_mecanico,id_mecanico){

                        var fechas = document.getElementsByName('fechas_grafico'+id_mecanico);//toma las fechas (input oculto)
                        var ocupacion = document.getElementsByName('porcentaje_ocupacion'+id_mecanico);//toma los valores html de ocupacion(input oculto)
                        var eficiencia = document.getElementsByName('porcentaje_eficiencia'+id_mecanico);//toma los valores html de eficienci(input oculto)
                        var productividad = document.getElementsByName('porcentaje_productividad'+id_mecanico);// toma los valores html de productividad(input oculto)

                        var fechas2 = new Array();//inicializacion de variables array donde se guardaran lo "value" de los valores tomados arriba
                        var ocupacion2 = new Array();
                        var eficiencia2 = new Array();
                        var productividad2 = new Array();

                            for (var i = 0, n = fechas.length; i < n; i++) {//como getElementsByName devuelve el input completo hay que buscar los "value" uno a uno
                               fechas2.push(fechas[i].value);
                               ocupacion2.push(parseFloat(ocupacion[i].value));
                               eficiencia2.push(parseFloat(eficiencia[i].value));
                               productividad2.push(parseFloat(productividad[i].value));

                           }

                         $("#container"+id_mecanico).show(1000,function(){//hace una animacion y llama a creargrafico luego lo muestra
                             crearGrafico(id_mecanico,nombre_mecanico,fechas2, ocupacion2, eficiencia2, productividad2);
                             $("#ocultar"+id_mecanico).show();
                             $("#ver"+id_mecanico).hide();
                         });
                 }
   
    /* FIN FUNCIONES PARA TABLA MECANICO */
   
   
    /* FUNCIONES PARA TABLA MENSUAL */
    
    /**
     * Esta funcion se encarga de buscar cada valor en fila y comparar con el 
     * porcentaje requerido para luego ponerle un color de background
     * @returns {undefined} //no devuelve nada
     */
    function mostrarColores(){
        
        //cita previa
        //citas
        $('#cita_previa tr:nth-child(7) > td:not(:first-child)').each(function(){//recorro cada td para tomar los valores
            numero = parseFloat($(this).text());//como devuelve un string hay que convertirlo a float 12.5
            if(numero >60){
                $(this).css("background-color","#E6FFE6");//verde claro
            }else if(numero <=60 && numero >=43){
                $(this).css("background-color","yellow");
            }else if(numero <43 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //citas realizadas
        $('#cita_previa tr:nth-child(8) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >70){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=70 && numero >= 51){
                $(this).css("background-color","yellow");
            }else if(numero <51 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //proceso de recepcion
        //vehiculos inspeccionados
        $('#proceso_recepcion tr:nth-child(5) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >60){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=60 && numero >= 44){
                $(this).css("background-color","yellow");
            }else if(numero <44 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //presupuestos entregados
        $('#proceso_recepcion tr:nth-child(6) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >70){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=70 && numero >= 60){
                $(this).css("background-color","yellow");
            }else if(numero <60 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //reparaciones repetidas
        $('#proceso_recepcion tr:nth-child(7) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >3){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=5 && numero >= 3){
                $(this).css("background-color","yellow");
            }else if(numero <3 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //reserva de recambios
        //fill rate taller mecanico
        $('#reserva_recambios tr:nth-child(5) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >90){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=90 && numero >= 85){
                $(this).css("background-color","yellow");
            }else if(numero <85 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //reservas
        $('#reserva_recambios tr:nth-child(6) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >70){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=70 && numero >= 60){
                $(this).css("background-color","yellow");
            }else if(numero <60 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //entrega del vehiculo
        //cumplimiento hora de entrega
        $('#entrega_vehiculo tr:nth-child(4) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >90){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=90 && numero >= 80){
                $(this).css("background-color","yellow");
            }else if(numero <80 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //explicacion del trabajo
        $('#entrega_vehiculo tr:nth-child(5) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >90){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=90 && numero >= 80){
                $(this).css("background-color","yellow");
            }else if(numero <80 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        
        //seguimiento
        //clientes contactados
        $('#seguimiento tr:nth-child(5) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >80){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=80 && numero >= 50){
                $(this).css("background-color","yellow");
            }else if(numero <50 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //clientes satisfechos
        $('#seguimiento tr:nth-child(6) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >90){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=90 && numero >= 80){
                $(this).css("background-color","yellow");
            }else if(numero <80 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        
        //control taller
        //ocupacion
        $('#control_taller tr:nth-child(8) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >85){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=85 && numero >= 80){
                $(this).css("background-color","yellow");
            }else if(numero <80 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //eficiencia
        $('#control_taller tr:nth-child(9) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >110){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=110 && numero >= 100){
                $(this).css("background-color","yellow");
            }else if(numero <100 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //productividad
        $('#control_taller tr:nth-child(10) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >100){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=100 && numero >= 80){
                $(this).css("background-color","yellow");
            }else if(numero <80 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //horas facturadas / vehiculo
        $('#control_taller tr:nth-child(11) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >2.5){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=2.5 && numero >= 2){
                $(this).css("background-color","yellow");
            }else if(numero <2 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //repuestos facturados / vehiculo
        $('#control_taller tr:nth-child(12) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >90){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=90 && numero >= 80){
                $(this).css("background-color","yellow");
            }else if(numero <80 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        
        //datos capacidad de servicio
        //total de entrada diaria (incluye rapid service)
        $('#capacidad_servicio tr:nth-child(9) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >100){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=100 && numero >= 90){
                $(this).css("background-color","yellow");
            }else if(numero <90 && numero>0){
                $(this).css("background-color","red");
            }
        });
        
        //dias de cita (promedio)
        $('#capacidad_servicio tr:nth-child(10) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            if(numero >3){
                $(this).css("background-color","#E6FFE6");
            }else if(numero <=3 && numero >= 2){
                $(this).css("background-color","yellow");
            }else if(numero <2 && numero>0){
                $(this).css("background-color","red");
            }
        });
                algo = "algo12345 "
              algo = algo.substring(0,algo.length-4);              
    }
    
    
    /**
     * Funcion que se encarga de buscar cada valor de la tabla para luego enviarlo a
     * otra funcion como parametro que creara el grafico
     * @returns {undefined}
     */
    
    function crearTodosGraficos(){
        
         //esto arregla lo que la funcion "dosColumnas" hace, poner float:left
         $('.container_todos').css({   
                'float': 'none',
                'width': '95%'
          });
          //quito los botones duplicados
          for (i=1; i<=18; i++){
            $('.boton_'+i).remove();     
        }
        
        //$("#load_animate").show();//animacion load
        
        //variables array donde se guardara la info buscada
        var citas = new Array();
        var citas_realizadas = new Array();
        var vehiculos_inspeccionados = new Array();
        var presupuestos_entregados = new Array();
        var reparaciones_repetidas = new Array();
        var fill_rate = new Array();
        var reservas = new Array();
        var cumplimiento_entrega = new Array();
        var explicacion_trabajo = new Array();
        var clientes_contactados = new Array();
        var clientes_satisfechos = new Array();
        var ocupacion = new Array();
        var eficiencia = new Array();
        var productividad = new Array();
        var horas_facturadas = new Array();
        var repuestos_facturados = new Array();
        var entrada_diaria = new Array();
        var dias_cita = new Array();
        
        var fechas = new Array();//fechas de la primera fila
        
        //variables con el titulo de cada fila (% cita previa (80%) 2/1)
        //compuesto por el id de la tabla y la fila a buscar, solo el primer td
        
        //titulo_tabla1 =  $('#cita_previa tr:first-child > td:first-child').text();//titulo tabla
        titulo_fila1 = $('#cita_previa tr:nth-child(7) > td:first-child').text();//titulo fila
        titulo_fila2 = $('#cita_previa tr:nth-child(8) > td:first-child').text();
        titulo_fila3 = $('#proceso_recepcion tr:nth-child(5) > td:first-child').text();
        titulo_fila4 = $('#proceso_recepcion tr:nth-child(6) > td:first-child').text();
        titulo_fila5 = $('#proceso_recepcion tr:nth-child(7) > td:first-child').text();
        titulo_fila6 = $('#reserva_recambios tr:nth-child(5) > td:first-child').text();
        titulo_fila7 = $('#reserva_recambios tr:nth-child(6) > td:first-child').text();
        titulo_fila8 = $('#entrega_vehiculo tr:nth-child(4) > td:first-child').text();
        titulo_fila9 = $('#entrega_vehiculo tr:nth-child(5) > td:first-child').text();
        titulo_fila10 = $('#seguimiento tr:nth-child(5) > td:first-child').text();
        titulo_fila11 = $('#seguimiento tr:nth-child(6) > td:first-child').text();
        titulo_fila12 = $('#control_taller tr:nth-child(8) > td:first-child').text();
        titulo_fila13 = $('#control_taller tr:nth-child(9) > td:first-child').text();
        titulo_fila14 = $('#control_taller tr:nth-child(10) > td:first-child').text();
        titulo_fila15 = $('#control_taller tr:nth-child(11) > td:first-child').text();
        titulo_fila16 = $('#control_taller tr:nth-child(12) > td:first-child').text();
        titulo_fila17 = $('#capacidad_servicio tr:nth-child(9) > td:first-child').text();
        titulo_fila18 = $('#capacidad_servicio tr:nth-child(10) > td:first-child').text();
        
        
        //recorrido de cada informacion y se guarda en las variables de arriba, sin incluir el primer td
        
        //fechas
        $('#cita_previa tr:first-child > td:not(:first-child)').each(function(){
            fecha = $(this).text();
            fechas.push(fecha);
        });
        
        //cita previa
        //citas
        $('#cita_previa tr:nth-child(7) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            citas.push(numero);
        });
        
        //citas realizadas
        $('#cita_previa tr:nth-child(8) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            citas_realizadas.push(numero);
        });
        
        //proceso de recepcion
        //vehiculos inspeccionados
        $('#proceso_recepcion tr:nth-child(5) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            vehiculos_inspeccionados.push(numero);
        });
        
        //presupuestos entregados
        $('#proceso_recepcion tr:nth-child(6) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            presupuestos_entregados.push(numero);
        });
        
        //reparaciones repetidas
        $('#proceso_recepcion tr:nth-child(7) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            reparaciones_repetidas.push(numero);
        });
        
        //reserva de recambios
        //fill rate taller mecanico
        $('#reserva_recambios tr:nth-child(5) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            fill_rate.push(numero);
        });
        
        //reservas
        $('#reserva_recambios tr:nth-child(6) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            reservas.push(numero);
        });
        
        //entrega del vehiculo
        //cumplimiento hora de entrega
        $('#entrega_vehiculo tr:nth-child(4) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            cumplimiento_entrega.push(numero);
        });
        
        //explicacion del trabajo
        $('#entrega_vehiculo tr:nth-child(5) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            explicacion_trabajo.push(numero);
        });
        
        
        //seguimiento
        //clientes contactados
        $('#seguimiento tr:nth-child(5) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            clientes_contactados.push(numero);
        });
        
        //clientes satisfechos
        $('#seguimiento tr:nth-child(6) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            clientes_satisfechos.push(numero);
        });
        
        
        //control taller
        //ocupacion
        $('#control_taller tr:nth-child(8) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            ocupacion.push(numero);
        });
        
        //eficiencia
        $('#control_taller tr:nth-child(9) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            eficiencia.push(numero);
        });
        
        //productividad
        $('#control_taller tr:nth-child(10) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            productividad.push(numero);
        });
        
        //horas facturadas / vehiculo
        $('#control_taller tr:nth-child(11) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            horas_facturadas.push(numero);
        });
        
        //repuestos facturados / vehiculo
        $('#control_taller tr:nth-child(12) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            repuestos_facturados.push(numero);
        });
        
        
        //datos capacidad de servicio
        //total de entrada diaria (incluye rapid service)
        $('#capacidad_servicio tr:nth-child(9) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            entrada_diaria.push(numero);
        });
        
        //dias de cita (promedio)
        $('#capacidad_servicio tr:nth-child(10) > td:not(:first-child)').each(function(){
            numero = parseFloat($(this).text());
            dias_cita.push(numero);
        });
        
        //funcion que crea el grafico se le pasa el id unico, el titulo, las fechas, los datos, y el numero de caracteres a quitar del final del titulo
        
        crearGraficoMensual(1,titulo_fila1,fechas,citas,4);//(id unico cualquiera,titulo de la fila, rango de fechas, los datos, numero de caractres)
        crearGraficoMensual(2,titulo_fila2,fechas,citas_realizadas,6);
        crearGraficoMensual(3,titulo_fila3,fechas,vehiculos_inspeccionados,4);
        crearGraficoMensual(4,titulo_fila4,fechas,presupuestos_entregados,4);
        crearGraficoMensual(5,titulo_fila5,fechas,reparaciones_repetidas,4);
        crearGraficoMensual(6,titulo_fila6,fechas,fill_rate,4);
        crearGraficoMensual(7,titulo_fila7,fechas,reservas,4);
        crearGraficoMensual(8,titulo_fila8,fechas,cumplimiento_entrega,4);
        crearGraficoMensual(9,titulo_fila9,fechas,explicacion_trabajo,5);
        crearGraficoMensual(10,titulo_fila10,fechas,clientes_contactados,6);
        crearGraficoMensual(11,titulo_fila11,fechas,clientes_satisfechos,6);
        crearGraficoMensual(12,titulo_fila12,fechas,ocupacion,6);
        crearGraficoMensual(13,titulo_fila13,fechas,eficiencia,6);
        crearGraficoMensual(14,titulo_fila14,fechas,productividad,6);
        crearGraficoMensual(15,titulo_fila15,fechas,horas_facturadas,11);
        crearGraficoMensual(16,titulo_fila16,fechas,repuestos_facturados,6);
        crearGraficoMensual(17,titulo_fila17,fechas,entrada_diaria,11);
        crearGraficoMensual(18,titulo_fila18,fechas,dias_cita,6);
               
          
          //baja el scroll con una animacion
          $('html,body').animate({
              scrollTop: $("#container1").offset().top
              
           }, 2000
//           ,function(){
//               $("#load_animate").hide();
//           }
           );
        
        
    }
    
    /**
     * Funcion que se encarga de construir un grafico con los valores pasados
     * @param {int-string} id //id unico manualmente colocado
     * @param {string} titulo //el titulo para el grafico (es el titulo de la fila) ejem: "% citas (80%) 2/1"
     * @param {array} fechas //eje(x) rango de fechas tomados de la primera tabla y fila, sin primer td. (incluye el total) ejem: "ene2013 feb2013 total"
     * @param {array} valores //eje(y) los valores a mostrar en el grafico numeros en float "12.5"
     * @param {int} caracteres // Numero de caracteres a elminar del titulo '4' "% citas (80%) 2/1" = "% citas (80%)"
     * @returns {undefined} //No devuelve nada
     */
    function crearGraficoMensual(id,titulo,fechas,valores,caracteres){
        titulo = titulo.substring(0,titulo.length-caracteres);//quito los ultimso caracteres "2/1"
        primera_letra= titulo.substring(titulo,1,1);//busco la segunda letra, la primera es espacio, la segunda "%" si lleva o no porcentaje
        
        //Linea Objetivo Horizontal
        separar = titulo.split("(");
        separar2 = separar[1].split(")");
        numero = separar2[0];
        
        if(id == 5){//vuelvo a cortar tiene "<" % Reparaciones Repetidas (<3%) 5/1
            separar_denuevo = numero.split("<");
            numero = parseFloat(separar_denuevo[1]);
        }else if (id == 15){// Horas Facturadas / Vehículo (>2,5 Hr) (16-17)/19
            separar_denuevo = numero.split(">");
            numero = parseFloat(separar_denuevo[1].replace(",","."));
        }else if (id == 17){//Total de Entrada Diaria (Incluye R.Service) (100) (1+1.1)/25
            separar = titulo.split("(");
            numero = separar[2].split(")");
            numero = parseFloat(numero[0]);
        }else if (id == 18){//Días de Citas (Promedio) (3) 1.2/2
            separar = titulo.split("(");
            numero = separar[2].split(")");
            numero = parseFloat(numero[0]);
        }
        else{
            numero = parseFloat(numero);
        }
            
        
        notacion = "Cantidad";//para el eje(y) por defecto "Cantidad", sino "Porcentaje"
        var simbolo = ""; //para el tooltip de la barra, si lleva o no "%"
        
        if(primera_letra == '%'){//compruebo si tiene o no "%"
            notacion = "Porcentaje (%)";
            simbolo = "%";
        }        
        
        //creacion del highcharts
        window["chart"+id] = new Highcharts.Chart({
            chart: {
                renderTo: 'container'+id,
                type: 'column',
                zoomType: 'xy'
                
            },
            credits: {
                enabled: false
              },
            lang: {
                contextButtonTitle: "Chart context menu",
                decimalPoint: ",",
                downloadJPEG: "Descarga JPEG imagen",
                downloadPDF: "Descarga PDF documento",
                downloadPNG: "Descarga PNG imagen",
                downloadSVG: "Descarga SVG vector imagen",
                loading: "Cargando...",
                printChart: "Imprimir Gráfico"
                },
            title: {
                text: titulo
            },
            subtitle: {
                text: ""
            },
            xAxis: {
                categories: fechas                
            },
            yAxis: {
                min: 0,
                title: {
                    text: notacion
                },
                plotLines:[{
                    value:numero,
                    color: '#ff0000',
                    width:2,
                    zIndex:4,                    
                    label:{text:'Objetivo '+numero,style: {
                        fontWeight:'bold'
                    }}
                }]
            },
            legend: {
                enabled:false,
                layout: 'horizontal',
                backgroundColor: '#FFFFFF',
                align: 'center',
                verticalAlign: 'bottom',
                //x: 150,
                //y: 0,
                floating: false,
                shadow: true,
                adjustChartSize: true
                
            },
            tooltip: {
                formatter: function() {
                    
                    devolver = ''+ this.x +': '+ this.y + ' '+simbolo;
                    return devolver;
                }
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0,
                    lineWidth: 1
                    //, pointWidth: 40
                }
            },
                series: [{
                name: 'AQUI',
                data: valores                
    
            }]
        });
        
         var $container = $('#container'+id);
         
            legenda = $('.highcharts-legend');
                origChartWidth = $('#container'+id).width();
                origChartHeight = $('#container'+id).height();
                chartWidth = origChartWidth;
                chartHeight = origChartHeight;
            $('<button class="boton_'+id+' noprint">+</button>').insertBefore($container).click(function() {
                window["chart"+id].setSize(chartWidth *= 1.1, chartHeight *= 1.1);
            });
            $('<button class="boton_'+id+' noprint">-</button>').insertBefore($container).click(function() {
                window["chart"+id].setSize(chartWidth *= 0.9, chartHeight *= 0.9);
            });
            $('<button class="boton_'+id+' noprint">1:1</button>').insertBefore($container).click(function() {
                chartWidth = origChartWidth;
                chartHeight = origChartHeight;
                window["chart"+id].setSize(origChartWidth, origChartHeight);
            });
            $('<button class="boton_'+id+' noprint">Pequeño</button>').insertBefore($container).click(function() {               
                window["chart"+id].setSize(400, 300);
                
            });
            $('<button class="boton_'+id+' noprint">Mediano</button>').insertBefore($container).click(function() {               
                window["chart"+id].setSize(600, 400);
                
            });
            $('<button class="boton_'+id+' noprint">Grande</button><div id="espaciado"><br><br></div>').insertBefore($container).click(function() {               
                window["chart"+id].setSize(900, 500);
                
            });
        
        //$container.hide();
        $container.show();
        
        $("#boton_doscolumnas").show();
        
    }
    
    
    //funcion para dos columnas
    function dosColumnas(){
        
        for (i=1; i<=18; i++){
            $('.boton_'+i).hide();
            
            window["chart"+i].setSize(600, 400);
        }
        
        
        $('.container_todos').css({              
                'width' : '600px',
                'height' : '400px',
                'float': 'left'
          });
          
          
          $('html,body').animate({
              scrollTop: $("#container1").offset().top
           }, 2000);
          
             
    }
    
   /* FIN FUNCIONES PARA LA TABLA MENSUAL */ 

		</script>
                <style type="text/css">
                    
                    .tabla-mecanicos{                            
                        border-style:solid;
                        border-collapse: collapse;
                        border-color: black;
                    }
                    .tabla-mecanicos td:first-child{
                        text-align: left;
                    }
                    .tabla-mecanicos td{
                        padding: 3px;
                        text-align: center;
                    }
                    .tabla-mecanicos tr:first-child td{
                       background-color: #CCCCCC;
                    }
                    .tabla-mecanicos tr:first-child + tr td+td {
                       background-color: #CCCCCC;
                    }
                    .tabla-mecanicos tr+tr+tr+tr+tr+tr{
                       background-color: #CCCCCC;
                    }
                    
                    .tabla-mecanicos tr:first-child + tr + tr + tr + tr{
                        border-bottom-style: solid;
                        border-bottom-color: black;
                        border-bottom-width: 2px;                        
                    }
                    .tabla-mecanicos tr:first-child + tr{
                        border-bottom-style: solid;
                        border-bottom-color: black;
                        border-bottom-width: 2px;                        
                    }
                    
                    .tabla-mecanicos td:first-child{
                        background-color: #CCCCCC;
                    }
                    
                    
                    
                    button{
                       
                        border: 1px solid #666666;
                        border-radius: 6px 6px 6px 6px;
                        font-family: Verdana,Arial,Helvetica,sans-serif;
                        font-size: 11px;

                    }
                    
                    /* CSS PARA TABLA MENSUAL */
                    
                    .tabla-mensual{                       
                        border-style:solid;
                        border-collapse: collapse;
                        border-color: black;
                    }
                    
                    .tabla-mensual td:first-child{
                        text-align: left;
                        width: 400px;
                    }
                    
                    .tabla-mensual td{
                        width: 70px;
                        text-align: center;
                        padding: 3px;
                    }
                    
                    .tabla-mensual tr:first-child td{
                        border-bottom-style: solid;
                        border-bottom-color: black;
                        border-bottom-width: 2px; 
                        font-weight: bold;
                        text-align: center;
                    }
                    
                    .tabla-mensual td:last-child{
                        border-left-style: solid;
                        border-left-color: black;
                        border-left-width: 2px; 
                        font-weight: bold;
                        background-color: #CCCCCC;
                    }
                    
                    .tabla-mensual tr:nth-child(n+2) td:first-child{
                        
                    }
                    
                     /* Especificos de tabla mensual */
                     
                     #cita_previa tr:nth-child(n+7){
                         background-color: #CCCCCC;
                     }
                     
                     #cita_previa tr:nth-child(7){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                    
                     #proceso_recepcion tr:nth-child(n+5){
                         background-color: #CCCCCC;
                     }
                     
                     #proceso_recepcion tr:nth-child(5){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #reserva_recambios tr:nth-child(n+5){
                         background-color: #CCCCCC;
                     }
                     
                     #reserva_recambios tr:nth-child(5){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #entrega_vehiculo tr:nth-child(n+4){
                         background-color: #CCCCCC;
                     }
                     
                     #entrega_vehiculo tr:nth-child(4){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #seguimiento tr:nth-child(n+5){
                         background-color: #CCCCCC;
                     }
                     
                     #seguimiento tr:nth-child(5){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #control_taller tr:nth-child(n+8){
                         background-color: #CCCCCC;
                     }
                     
                     #control_taller tr:nth-child(8){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #capacidad_servicio tr:nth-child(n+9){
                         background-color: #CCCCCC;
                     }
                     
                     #capacidad_servicio tr:nth-child(9){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                    
                     
                     /****************************************************************/
                     
                     /* CSS para tabla semanal */
                     
                     .tabla-semanal{                       
                        border-style:solid;
                        border-collapse: collapse;
                        border-color: black;
                    }
                    
                    .tabla-semanal td:first-child{
                        text-align: left;
                        min-width: 400px;
                    }
                    
                    .tabla-semanal td{
                        min-width: 70px;
                        text-align: center;
                        padding: 3px;
                    }
                    
                    .tabla-semanal tr:first-child td{
                        white-space: nowrap;
/*                        border-bottom-style: solid;
                        border-bottom-color: black;
                        border-bottom-width: 2px; */
                        font-weight: bold;
                        text-align: center;
                    }
                    
                    .tabla-semanal tr:first-child + tr td{
                        white-space: nowrap;
                        border-bottom-style: solid;
                        border-bottom-color: black;
                        border-bottom-width: 2px; 
                        font-weight: bold;
                        text-align: center;
                    }
                    
/*                    .tabla-semanal td:last-child{
                        border-left-style: solid;
                        border-left-color: black;
                        border-left-width: 2px; 
                        font-weight: bold;
                        background-color: #CCCCCC;
                    }*/
                    
                    .tabla-semanal tr:nth-child(n+2) td:first-child{
                        
                    }
                    
                    
                    /* Especificos de tabla semanal */
                     
                     #cita_previa2 tr:nth-child(n+8){
                         background-color: #CCCCCC;
                     }
                     
                     #cita_previa2 tr:nth-child(8){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                    
                     #proceso_recepcion2 tr:nth-child(n+6){
                         background-color: #CCCCCC;
                     }
                     
                     #proceso_recepcion2 tr:nth-child(6){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #reserva_recambios2 tr:nth-child(n+6){
                         background-color: #CCCCCC;
                     }
                     
                     #reserva_recambios2 tr:nth-child(6){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #entrega_vehiculo2 tr:nth-child(n+5){
                         background-color: #CCCCCC;
                     }
                     
                     #entrega_vehiculo2 tr:nth-child(5){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #seguimiento2 tr:nth-child(n+6){
                         background-color: #CCCCCC;
                     }
                     
                     #seguimiento2 tr:nth-child(6){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #control_taller2 tr:nth-child(n+9){
                         background-color: #CCCCCC;
                     }
                     
                     #control_taller2 tr:nth-child(9){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                     
                     #capacidad_servicio2 tr:nth-child(n+10){
                         background-color: #CCCCCC;
                     }
                     
                     #capacidad_servicio2 tr:nth-child(10){
                         border-top-style: solid;
                        border-top-color: black;
                        border-top-width: 2px; 
                     }
                    
					
					
					
					/* Asistencia mecanicos */				
									
					.datagrid table { 
						border-collapse: collapse; text-align: left; width:100%; 
					} 
								
					
					.datagrid {
						font: normal 12px/150% Arial, Helvetica, sans-serif; 
						background: #fff; 
						overflow: hidden; 
						border: 1px solid #8C8C8C; 
						-webkit-border-radius: 3px; 
						-moz-border-radius: 3px; border-radius: 3px; 
						width:900px;
						
					}
						
					.datagrid table td, .datagrid table th { 
						padding: 3px 10px; 						
					}
					
					.datagrid tr:first-child th:first-child{
						font-size: 16px; 
						text-shadow: 0 -1px 3 rgba(0, 0, 0, 0.33);
					}
					
					.datagrid table thead th {
						
						background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #8C8C8C), color-stop(1, #7D7D7D) );
						background:-moz-linear-gradient( center top, #8C8C8C 5%, #7D7D7D 100% );
						filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#8C8C8C', endColorstr='#7D7D7D');
						background-color:#8C8C8C; 
						color:#FFFFFF; 
						font-size: 13px; 
						font-weight: bold; 
						/*border-left: 1px solid #A3A3A3; */
					} 						
													
					.datagrid table tbody td { 
						color: #333333; border-left: 1px solid #DBDBDB;
						font-size: 12px;font-weight: normal; 
						border-bottom: 1px solid #DBDBDB;
					}							
							
		
					
                </style>
                
                
                <?php  $xajax->printJavascript("../controladores/xajax/"); ?>
                
	</head>
	<body>
            <?php 
            //$_SESSION['idEmpresaUsuarioSysGts']="1";
            //$_SESSION['nombreUsuarioSysGts']="gregor xD";
           
            ?>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje" style="text-align:left; ">
	<div> <?php include("banner_servicios.php"); ?> </div>        
<div id="divInfo" class="print">
    <table border="0" width="100%">
        <tr>
            <td class="tituloPaginaServicios" id="titulopag">Sistema de Medidas</td>
        </tr>
        <tr>
            <td class="noprint">
                <table align="left">
                    <tr>
                        <td>
                            <button type="button" class="noprint" onclick="xajax_imprimir(xajax.getFormValues('frmBuscarS'));" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                        </td>

                    </tr>
                </table>

                <form id="frmBuscarS" name="frmBuscarS" onsubmit="return false;" style="margin:0">
                    <table align="right">
                        <tr align="left">
                        
                        <td align="right" class="tituloCampo">Empresa:</td>
                            <td id="tdlstEmpresa">
                            <!-- Se llena por xajax -->
                            </td>
                        
                        
                            <td align="right" class="tituloCampo">Tipo medida:</td>
                            
                            <td id="td_tipo_medida">
                                <select id="tipo_medida" name="tipo_medida">
                                    <option value="1">Control de taller - Técnicos</option>                                    
                                    <option value="2">Control de taller - Magnetoplano Diario</option>
                                    <option value="3">Control de taller - Asistencia</option>
                                    <option value="4">Sistema de medidas - Semanal</option>
                                    <option value="5">Sistema de medidas - Mensual</option>
                                </select>
                            </td>
<!--                            <td align="right" class="tituloCampo">Empresa:</td>
                            <td id="tdlstEmpresa">
                                <select id="lstEmpresa" name="lstEmpresa">
                                    <option value="-1">[ Todos ]</option>
                                </select>
                            </td>-->
                            <td align="right" class="tituloCampo">Fecha:</td>
                            <td>
                                <div style="float:left">
                                    <input type="text" id="txtFechaDesde" name="txtFechaDesde" readonly="readonly" style="text-align:center" size="15" value="<?php echo date("d-m-Y"); ?>"/>
                                </div>
                                <div style="float:left">
                                    <img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint"/>
                                    <script type="text/javascript">
                                    Calendar.setup({
                                    inputField : "txtFechaDesde",
                                    ifFormat : "%d-%m-%Y",
                                    button : "imgFechaDesde"
                                    });
                                    </script>
                                </div>
                            </td>
                            <td>
                                <div style="float:left">
                                    <input type="text" id="txtFechaHasta" name="txtFechaHasta" readonly="readonly" style="text-align:center" size="15" value="<?php echo date("d-m-Y"); ?>"/>
                                </div>
                                <div style="float:left">
                                    <img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint"/>
                                    <script type="text/javascript">
                                    Calendar.setup({
                                    inputField : "txtFechaHasta",
                                    ifFormat : "%d-%m-%Y",
                                    button : "imgFechaHasta"
                                    });
                                    </script>
                                </div>
                            </td>
                            <td>
                                <input type="button" id="btnBuscar" class="noprint" onclick="xajax_buscar(xajax.getFormValues('frmBuscarS'));" value="Buscar" />
                                <input type="button" class="noprint" onclick="document.forms['frmBuscarS'].reset(); $('btnBuscar').click();" value="Limpiar" />
                            </td>
                        </tr>
                    </table>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <table border="0" width="100%">
                    <tr>
                        <td colspan="2" style="padding-right:4px">
                            <div id="divListaResumenServ" style="width:100%">
                                <table cellpadding="0" cellspacing="0" class="divMsjInfo noprint" width="100%">
                                    <tr >
                                        <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                        <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                                    </tr>
                                </table>                               
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="80%" align="left">
<!--                            <div id="divListaResumen" style="width:100%"></div>-->
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div id="contenedor_estadistico"> 
    
       
    </div>
</div>
               
<!--<script src="../../js/highcharts.js"></script>
<script src="../../js/modules/exporting.js"></script>-->

<script src="../js/highcharts/js/highcharts.js"></script>
<script src="../js/highcharts/js/modules/exporting.js"></script>


 <div class="noprint">
	<?php include("pie_pagina.php"); ?>
    </div>

</div>
	</body>
</html>

<div id="divFlotante" class="root" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0;">
	<div id="divFlotanteTitulo" class="handle"><table><tr><td id="tdFlotanteTitulo" width="100%"></td></tr></table></div>
    
    <table border="0" width="1000">
    <tr>
    	<td id="tdListadoResumenDetalle"></td>
    </tr>
    <tr>
    	<td align="right">
	        <hr>
            <input type="button" id="btnCancelar" name="btnCancelar" class="close" value="Cerrar">
        </td>
    </tr>
    </table>
</div>

<script>
//$('#menu_servicios3').click(function(){
//        
//  $(this).trigger('mouseover');
//});

$("#load_animate").hide();

xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>',''); //buscador

</script>