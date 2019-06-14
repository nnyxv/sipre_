function verLeyenda() { //Funcion para mostrar la leyenda en grande completa al hacerle click JQUERY

    //$('#leyenda').removeClass('leyenda');
    $('#modal2').empty();//Limpiamos la tabla

    $('#leyenda').clone().appendTo('#modal2').removeClass('leyenda caja');//Clono el div #leyenda y quito su clase css
    $("#modal").find('td').removeClass('hide');//Le quito la invisibilidad al td leyenda copiado dentro de modal
    $('#modal h5').remove();
    $("#modal div").removeClass('caja-dentro');
    $('#modal').modal(//Configuracion del modal
            {escapeClose: true,
                clickClose: true}
    );
}

function regresar() {
    $('#diagnostico').slideUp(function()
    {

        $('#orden').hide().slideDown(2000);
        $('#diagnostico').remove();
    }
    );
}
function regresar2() {
    $('#tempario_unico').slideUp(function()
    {

        $('#orden').hide().slideDown(2000);
        $('#tempario_unico').remove();
    }
    );
}

function regresar3() {
    $('#estado_orden').slideUp(function()
    {

        $('#orden').hide().slideDown(2000);
        $('#estado_orden').remove();
    }
    );
}

//el boton regresar de "verFinalizar"
function regresar4() {
    $('#control_jefetaller').slideUp(function()
    {

        $('#orden').hide().slideDown(2000);
        $('#control_jefetaller').remove();
    }
    );
}

var aux = false;
function editarDiagnostico() {

    if (aux === false) {
        $('.diagnostico_escrito').hide();
        $('.area_diagnostico').show();
        aux = true;
    } else {
        $('.area_diagnostico').hide();
        $('.diagnostico_escrito').show();
        aux = false;



    }
}

function alturacajas() {
    //Funcion busca la altura max de las 3 cajas y luego las pone las 3 a la misma altura max+10
    boxes = $('.informacion-orden');
    maxHeight = Math.max.apply(
            Math, boxes.map(function() {
        return $(this).height();
    }).get());
    boxes.height(maxHeight + 10);
}

function letras(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla == 0 || tecla == 8)
        return true;
    patron = /[A-Za-z\s ]/;
    te = String.fromCharCode(tecla);
    return patron.test(te);
}

function numeros(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla == 0 || tecla == 8)
        return true;
    patron = /[0-9]/;
    te = String.fromCharCode(tecla);
    return patron.test(te);
}

function habilitar() {
    if ($("#selectmecanico").val() === "null") {
        $("#botonasignardiagnostico").attr("disabled", "disabled");
    } else {
        $("#botonasignardiagnostico").removeAttr("disabled");
    }
}

function asignarDiagnostico(id_orden, tipo, id_tempario) {
    password = $("#password_diagnostico").val();

    if (password == "") {
        alert("Debes introducir tu contraseña");
        $("#password_diagnostico").focus();
    } else {
        var id_mecanico = $("#selectmecanico").val();
        var inicio = $("#fechahorainicio").val();
        var final = $("#fechahoraestimada").val();

        if (id_mecanico === "" || inicio === "" || final === "") {
            alert("Error falta llenar algún campo");
            //  alert(id_orden +" "+ id_mecanico +" "+ inicio + " " + final );
        } else {
            verificacion = xajax_verificarAcceso(password);
            //console.log(verificacion);
            if (verificacion == "no") {
                alert("Contraseña Invalida");
            } else if (verificacion == "si") {
                // alert("correcto");
                // alert(id_orden +" "+ id_mecanico +" "+ inicio + " " + final );
                xajax_asignarDiagnostico(id_orden, id_mecanico, inicio, final, tipo, id_tempario);
            }
        }
    }
}

function asignarGrupoTempario(id_orden) {
    var ids_temparios = new Array();
    var uts_temparios = new Array();

    var id_mecanico = $('.mecanicos_grupo #selectmecanico').val();//primero la clase para que no se confunda con el primer #select mecanico de arriba

    $.each($("input[name='box_grupo']:checked"), function() {
        ids_temparios.push($(this).val());
        uts_temparios.push($(this).attr('ut'));
    });

    if (ids_temparios.length < 2) {
        alert("Debes seleccionar almenos 2 mano de obra");
    } else if (id_mecanico === "null") {
        alert("Debes seleccionar un mecánico");
    }
    else {

        password = prompt('Contraseña Jefe de Taller');
        verificacion = xajax_verificarAcceso(password);
        
        if (verificacion == "no") {
            alert("Contraseña Invalida");
        } else if (verificacion == "si") {

            for (x = 0; x < ids_temparios.length; x++) {
                xajax_estimarDiagnostico(uts_temparios[x], 'ut', true, id_mecanico, id_orden, ids_temparios[x], 1);
            }
        }
        alert('Asignado');
        location.reload();
    }

}

function borrarDiagnostico() {
    $("#selectmecanico").val("");
    $("#tiempo").val("");
    $("#fechahorainicio").val("");
    $("#fechahoraestimada").val("");

    $("#botonasignardiagnostico").attr("disabled", "disabled");

    $("#selectmecanico").removeAttr("disabled", "disabled");
    $("#tiempo").removeAttr("disabled", "disabled");
    $("#unidadtiempo").removeAttr("disabled", "disabled");
    $("#almuerzo").removeAttr("disabled", "disabled");

}
function borrarDiagnostico2() {
    $("#selectmecanico").val("");
    $("#fechahorainicio").val("");
    $("#fechahoraestimada").val("");

    $("#botonasignardiagnostico").attr("disabled", "disabled");

    $("#selectmecanico").removeAttr("disabled", "disabled");
    $("#almuerzo").removeAttr("disabled", "disabled");

}

function eliminarJefe(id_mp, id_tempario) {
    var x = confirm("¿Estás seguro que deseas eliminar?");
    if (x) {
        xajax_eliminarJefe(id_mp, id_tempario);
    }

}

function finalizarMecanico(id_mp, id_tempario, id_mecanico) {
    //alert(id_mp +" "+ id_tempario +" "+ id_mecanico);
    var x = confirm("¿Estás seguro que deseas finalizar?");
    if (x) {
        xajax_finalizarMecanico(id_mp, id_tempario, id_mecanico);
    }

}

function finalizarOrden(id_orden) {

    password = $("#password_finalizar").val();
    if (password == "") {
        alert("Debes introducir tu contraseña");
        $("#password_finalizar").focus();
    } else {
        verificacion = xajax_verificarAcceso(password);
        if (verificacion == "no") {
            alert("Contraseña Invalida");
        } else if (verificacion == "si") {
            var x = confirm("¿Estás seguro que deseas finalizar?");
            if (x) {
                xajax_finalizarOrden(id_orden);
            }
        }
    }

}
function verGrupoTempario() {
    $("#boton_ver_grupo").hide("slow", function() {
        $(".mecanicos_grupo").show("slow");
    });
}

function asignarGrupoTempario(id_orden) {
    password = $("#password_grupo").val();

    if (password == "") {
        alert("Debes introducir tu contraseña");
        $("#password_grupo").focus();
    } else {
        var ids_temparios = new Array();
        var uts_temparios = new Array();

        var id_mecanico = $('.mecanicos_grupo #selectmecanico').val();//primero la clase para que no se confunda con el primer #select mecanico de arriba

        $.each($("input[name='box_grupo']:checked"), function() {
            ids_temparios.push($(this).val());
            uts_temparios.push($(this).attr('ut'));
        });

        if (ids_temparios.length < 2) {
            alert("Debes seleccionar almenos 2 mano de obra");
        } else if (id_mecanico === "null") {
            alert("Debes seleccionar un mecánico");
        } else {

            //acceso

            verificacion = xajax_verificarAcceso(password);
            if (verificacion == "no") {
                alert("Contraseña Invalida");
            } else if (verificacion == "si") {


                for (x = 0; x < ids_temparios.length; x++) {
                    xajax_estimarDiagnostico(uts_temparios[x], 'ut', true, id_mecanico, id_orden, ids_temparios[x], 1);//cualquiercosa

                }
                alert('Asignado');
                location.reload();
            }

        }
    }
}


auxiliar_box = false;

function seleccionarTodos() {

    checkboxes = document.getElementsByName('box_grupo');

    if (auxiliar_box == false) {

        for (var i = 0, n = checkboxes.length; i < n; i++) {
            if (checkboxes[i].disabled == false) {
                checkboxes[i].checked = true;
            }
        }
        auxiliar_box = true;
    } else {
        for (var i = 0, n = checkboxes.length; i < n; i++) {
            checkboxes[i].checked = false;
        }
        auxiliar_box = false;
    }
}

function grupo_asignarDiagnostico(id_orden, id_mecanico, inicio_grupo, fin_grupo, tipo, id_tempario) {
   
    xajax_asignarDiagnostico(id_orden, id_mecanico, inicio_grupo, fin_grupo, tipo, id_tempario, 1);//ultimo es el modo cualquiercosa

}

function verEstadoOrden(id_orden, nombre_estado, color_estado) {

//    password = prompt('Contraseña Jefe de Taller');
//    verificacion = xajax_verificarAcceso(password);
//
//    if (verificacion == "no") {
//        alert("Contraseña Invalida");
//    } else if (verificacion == "si") {
        xajax_verEstadoOrden(id_orden, nombre_estado, color_estado);
//    }

}

function mueveReloj() {
    // momentoActual = new Date();

    // hora = momentoActual.getHours();
    // minuto = momentoActual.getMinutes();
    // segundo = momentoActual.getSeconds();

    // tiempo = "a.m."
    // if (parseInt(hora) == 0) {
    //     hora = 12;
    // } else if (parseInt(hora) > 12) {
    //     hora = hora - 12;
    //     tiempo = "p.m."
    // }

    // if (parseInt(minuto) >= 0 && parseInt(minuto) <= 9)
    //     minuto = "0" + minuto;

    // if (parseInt(segundo) >= 0 && parseInt(segundo) <= 9)
    //     segundo = "0" + segundo;

    // horaImprimible = hora + ":" + minuto + ":" + segundo + " " + tiempo;

    // document.getElementById('tdHoraSistema').innerHTML = horaImprimible;
    xajax_mueveReloj();

    setTimeout("mueveReloj()", 30000);
}

function mostrarFecha() {
    var nuestraFecha = new Date();
    var nuevaFecha = ('0' + nuestraFecha.getDate()).slice(-2).toString()
            + '/' + ('0' + nuestraFecha.getMonth()).slice(-2).toString()
            + '/' + nuestraFecha.getFullYear().toString();
    document.getElementById('tdFechaSistema').innerHTML = nuevaFecha;
}

function recargarVista(){
    location.reload();   
}

function eliminarManodeobra(id_det_orden_tempario, id_orden, id_mp) {
    var pregunta_eliminar = confirm('¿Seguro deseas Eliminar?');
    if (pregunta_eliminar) {
        xajax_eliminarManodeobra(id_det_orden_tempario, id_orden, id_mp);
    }

}

function accesoCambio(){
    password = $("#password_cambio").val();
    if (password == ""){
        alert("Debes introducir tu contraseña");
        $("#password_cambio").focus();
    }else{
        verificacion = xajax_verificarAcceso(password);
        if(verificacion == "no"){
            alert("Contraseña Invalida");
        }else if(verificacion == "si"){
           $("#seccion_acceder_cambio").hide();
           $("#boton_cambio_estado").show();
        }
    }

}

function guardarTodosDiagnostico(id_recepcion,todos_id_recepcion_fallas,todos_textos){
                        
        //alert (todos_textos[0].value);
    for (var i=0, n=todos_id_recepcion_fallas.length; i<n; i++){
        xajax_guardarDiagnostico(todos_id_recepcion_fallas[i].innerHTML,id_recepcion, todos_textos[i].value);
        //alert("id recepcion:"+ id_recepcion +" id falla"+ todos_id_recepcion_fallas[i].innerHTML + " texto:" + todos_textos[i].value);                        
    }
    alert("Guardado Correctamente");
    xajax_verDiagnostico(id_recepcion);
}



 ////////////////////////////////////////////////////////////////////////////////////////////
        
    /**
     * Funcion que se encarga de sincronizar los trabajos con respecto a las 
     * horas del magnetoplano tomando como referencia el dia de hoy, totalmente manipulado por jquery
     * cambia el tamaño de celdas busca cada uno de los trabajos separa las fechas y calcula el ancho para
     * que se asemeje de acuerdo a las horas que le toca
     * @returns {undefined}
     */
        
     function sincronizarTiempo(){
        sincro = true; //Variable que indica si se hizo click en "sincronizar" para determinar el margin-left cuando se filtra sin sincro usada en => funcion filtroMagnetoplano 
        
        //Tomando el tamaño de cada celda
        var ancho_celda = $(".tabla-magnetoplano tr>td:not(:first-child)").width();
        //alert(ancho_celda);
        
        //Contar cada columna        
        var total_columnas = 0;
        $('.tabla-magnetoplano tr:first-child>td:not(:first-child)').each(function () {  
              // $(this).css("background-color","blue");
               $(this).width(ancho_celda);
                total_columnas++;
        });
        //alert(total_columnas);
        
        //mover a la derecha con margen
        $(".progress").each(function(){
                var margen_anterior = $(this).css("margin-left").replace('px', '');                
                    margen_anterior = parseInt(margen_anterior)+50;
                //$(this).css("margin-left", margen_anterior);
        }
        );
        
        //cambia el hover
        $(".progress").hover(
            function(){
                anterior = $(this).width();
                padre = $(this).offsetParent().width();
                porcentaje = 100*anterior/padre;
                //zindex_anterior = $(this).css("z-index");
                //alert(anterior +" - " + padre +" y "+ porcentaje+"%");
                $(this).css('z-index', 1);
                if(porcentaje < 18){
                $(this).width("18%");
                
                }
            },
            function (){
                $(this).width(anterior);
                $(this).css('z-index', 0);
            }
        );

        
        //Ultima columna tr:last td
        ultima_hora = $('.tabla-magnetoplano tr:first-child>td:last').html();//devuelve string "6:30 pm"
        //Primera Columna td:nth-child(2) la primera es los nombres, la segunda es la hora primera
        primera_hora = $('.tabla-magnetoplano tr:first-child>td:nth-child(2)').html();// devuelve string "7:30 am"
        //Intervalo con media hora o sin media hora:
        separar_minutos = primera_hora.split(':');
        separar_minutos2 = separar_minutos[1].split(' ');
        intervalo = separar_minutos2[0];        

        encontrar = $("tr#1>[etiqueta='7:30 pm']").length;//sino existe el elemento devuelve 0
        //Si hay media hora se redondea los numeros a 6:45 => 6:30, sino 6:45 => 7:00
        media_hora = false;
        if(intervalo == "30"){
            media_hora = true;
        }        
        //alert(media_hora);
        
        //cada barra div
        
        $(".progress").each(function(){
            
            var id_fila = $(this).parent().parent().attr("id");//obtengo el id tr de los mecanicos para saber cada fila (asi saber que barra le pertenece a que mecanico)
            var id_barra_contenido = this.id; // contenido del "id" en barra .progress formato: 08-07-2013 9:23 am 08-07-2013 2:23 pm
            var separacion = id_barra_contenido.split(' ');//array que funciona igual que explode() de php
            inicio_fecha = separacion[0];
            inicio_hora = separacion[1];
            inicio_dia_tarde = separacion[2];
            
            fin_fecha = separacion[3];
            fin_hora = separacion[4];
            fin_dia_tarde = separacion[5];
            
            hora_ini_mp = inicio_hora+" "+inicio_dia_tarde;
            hora_fin_mp = fin_hora+" "+fin_dia_tarde;
            
            hora_ini_mp = redondearTiempo(media_hora, hora_ini_mp);
            hora_fin_mp = redondearTiempo(media_hora, hora_fin_mp);
            
            cuando_inicio = compararFecha(inicio_fecha); //devuelve: menor, mayor, igual con respecto a fecha de hoy
            cuando_fin = compararFecha(fin_fecha);
            
            if(cuando_inicio == "menor" ){
            hora_ini_mp = primera_hora;
          }
          if(cuando_inicio == "mayor"){
              hora_ini_mp = ultima_hora;
          }
          
           if(cuando_fin == "mayor"){
               hora_fin_mp = ultima_hora;
           }
           if(cuando_fin == "menor"){
                hora_fin_mp = primera_hora;
            }
            
            
            
            columnas = calcularColumna(hora_ini_mp,hora_fin_mp);
            if (columnas == 0){
                columnas = 1;
            }
            
            //Margen entre cada div, si tiene varios div el mecanico
            margen_anterior = $(this).css("margin-left");
            if(margen_anterior != "0px"){
                $(this).css("margin-left","3px");
            }else{
                $(this).css("margin-left","0px");
            }
            //alert("Hora ini "+inicio_hora+" => "+hora_ini_mp+"hora fin "+ fin_hora +" => "+hora_fin_mp+ "Columnas "+columnas);
            //alert(inicio_fecha + inicio_hora + inicio_dia_tarde + fin_fecha + fin_hora + fin_dia_tarde);
            
            $(this).width((ancho_celda+12)*columnas);//varia            
            
                    ///******************calculo exacto de la hora ********************///
//                    mitad_margen = ancho_celda/2;
//                    if (media_hora){
//                        
//                    }else{
//                        
//                        $(this).css("margin-left",mitad_margen+"px");//Cambia el margen a la izq que proviene del php 0px para ninguno
//                        //nuevo = $(this).width();
//                        //$(this).width((nuevo-(mitad_margen*aux_margen))-12);
//                    }
                    
                    
                   
                   ///******************calculo exacto de la hora ********************///
           
           $(this).appendTo("tr#"+id_fila+">[etiqueta='"+hora_ini_mp+"']").hide().show(2000);
              
        });
        
     }
        
       /**
         * Funcion que redondea la hora para que muestre mas exacto en el mp
         * @param {Boolean} media_hora //Indica si existe o no media hora en la tabla
         * @param {string} hora //Hora que va a ser redondeada Formato: "6:30 am" o "6:00 am"
         * @returns {String} //Devuelve un string redondeando la hora para que muestre
         */
        function redondearTiempo(media_hora,hora){
            
            //Divido la hora entrante para luego redondear minutos y la hora si se pasa D:
            hora_entrante = hora.split(':');
            solo_hora = parseInt(hora_entrante[0]);  
            dividir_minutos = hora_entrante[1].split(' ');
            minutos = parseInt(dividir_minutos[0]);
            am_pm = dividir_minutos[1];
            
            if(media_hora === true){
                if(minutos <= 15){
                    minutos = 30;//Antes 00, pero no existen 7:00 en intervalos de media hora D:
                }else if(minutos >=16 && minutos <=30){
                    minutos = 30;
                }else if(minutos >= 31 && minutos <=45){
                    minutos = 30;
                }else if(minutos >= 46 && minutos <=59){
                    minutos = 30;
//                    if(solo_hora == 11){//no es necesario, por el redondeo a media hora
//                        am_pm = "pm";
//                    }
//                    solo_hora++;
                }
            }else{
                if(minutos < 30){
                    minutos = "00";
                }else if(minutos >=30){
                    minutos = "00";
                    if(solo_hora == 11){
                        am_pm = "pm";
                    }
                    solo_hora++;
                }
            }
            
            hora_redondeada = solo_hora+':'+minutos+' '+am_pm;
            
            return hora_redondeada;
            
        }
        
        /**
         * Funcion que calcula la cantidad de columnas para el ancho a tomar
         * @param {string} hora_inicio //Hora completa de inicio, se tomara solo la hora formato 9:30 am
         * @param {string} hora_fin //Hora completa de fin, se tomara solo la hora formato 9:30 am
         * @returns {Number} //Son el numero de columnas entre una hora y otra
         */
        function calcularColumna(hora_inicio, hora_fin){//formato 9:30 am
            separar_hora_inicio = hora_inicio.split(':');
            solo_hora_inicio = separar_hora_inicio[0];
            separar_minutos_inicio = separar_hora_inicio[1].split(' ');
            solo_minutos_inicio = separar_minutos_inicio[0];
            solo_ampm_inicio = separar_minutos_inicio[1];
            
            if(solo_ampm_inicio == "pm"){
                solo_hora_inicio = cambiar24horas(solo_hora_inicio);
            }
            
            separar_hora_fin = hora_fin.split(':');
            solo_hora_fin = separar_hora_fin[0];
            separar_minutos_fin = separar_hora_fin[1].split(' ');
            solo_minutos_fin = separar_minutos_fin[0];
            solo_ampm_fin = separar_minutos_fin[1];
            
            if(solo_ampm_fin == "pm"){
                solo_hora_fin = cambiar24horas(solo_hora_fin);
            }
            
            cantidad_columnas = solo_hora_fin - solo_hora_inicio;
            return cantidad_columnas;
        }
        
        /**
         * Funcion para cambiar un numero (12 horas) a otro numero (24 horas)
         * @param {int} hora //Es la hora entrante en formato 12horas solo numero 1
         * @returns {int} hora //Es la hora saliente en formato 24horas solo numero 13
         */
        
        function cambiar24horas(hora){
            hora = parseInt(hora);
            
            switch(hora){
            case 1:
              hora = 13;
              break;
            case 2:
              hora = 14;
              break;
            case 3:
              hora = 15;
              break;            
            case 4:
              hora = 16;
              break;              
            case 5:
              hora = 17;
              break;              
            case 6:
              hora = 18;              
              break;
            case 7:
              hora = 19;
              break;              
            case 8:
              hora = 20;
              break;              
            case 9:
              hora = 21;
              break;              
            case 10:
              hora = 22;
              break;              
            case 11:
              hora = 23;
              break;              
//            case 12: //12 debe quedar igual, si usa 12pm debe quedar en 12, si es 12am deberia ser 24
//              hora = 24;
//              break;              
            default:
              hora = hora;
            }
            
            return hora;
        }
        
        //funcion para sumar tiempo a una fecha
        
       	function addTimeToDate(time,unit,objDate,dateReference){//Ej suma = addTimeToDate("15","m",hoy);
	    var dateTemp=(dateReference)?objDate:new Date(objDate);
	    switch(unit){
	        case 'y': dateTemp.setFullYear(objDate.getFullYear()+time); break;
	        case 'M': dateTemp.setMonth(objDate.getMonth()+time); break;
	        case 'w': dateTemp.setTime(dateTemp.getTime()+(time*7*24*60*60*1000)); break;
	        case 'd': dateTemp.setTime(dateTemp.getTime()+(time*24*60*60*1000)); break;
	        case 'h': dateTemp.setTime(dateTemp.getTime()+(time*60*60*1000)); break;
	        case 'm': dateTemp.setTime(dateTemp.getTime()+(time*60*1000)); break;
	        case 's': dateTemp.setTime(dateTemp.getTime()+(time*1000)); break;
	        default : dateTemp.setTime(dateTemp.getTime()+time); break;
	    }
	    return dateTemp;
	}
        
        /**
         * Funcion que compara la fecha pasada con respecto a la de hoy y asi determinar si es de hoy ayer mañana
         * @param {string} fecha_comparar //Es la fecha a comparar con la de hoy formato 15-07-2013
         * @returns {String} //menor, mayor, igual
         */
        
        function compararFecha(fecha_comparar){//ingrese la fecha en formato 15-07-2013
        
            hoy = new Date();
            hoy.setHours(0,0,0,0);
            
            fecha_comparar = new Date(fecha_comparar.split('-').reverse().join(' '));

            if(fecha_comparar.getTime() < hoy.getTime()){
                return "menor";
            }else if(fecha_comparar.getTime() > hoy.getTime()){
                return "mayor";
            }else if (fecha_comparar.getTime() === hoy.getTime()){
                return "igual";
            }
        
        }
        
        /**
         * Funcion que se encarga de filtrar los trabajos en magnetoplano, ocultando los que estan demas
         * @param {string} tipo //Es el tipo de filtro que aplica puede ser "hoy", "anteriores", "posteriores"
         * @returns {undefined}
         */
        
        var sincro = false; //variable global inicializada en false, cambia a true cuando le dan click a "sincronizar" funcion sincronizarTiempo y usada en funcion filtroMagnetoplano
        function filtroMagnetoplano(tipo){
            
            $(".progress").each(function(){            
                
                if (sincro === false){
                    $(this).css('margin-left','0px');
                }
                
                $(this).css('display','block');//Muestro todo por si acaso ya habian ocultado algo con el filtro.
            
                var id_barra_contenido = this.id; // contenido del "id" en barra .progress formato: 08-07-2013 9:23 am 08-07-2013 2:23 pm
                var separacion = id_barra_contenido.split(' ');//array que funciona igual que explode() de php
                inicio_fecha = separacion[0];
                fin_fecha = separacion[3];
                
               cuando_inicio = compararFecha(inicio_fecha);
               cuando_fin = compararFecha(fin_fecha);
               
                if (tipo == "hoy"){
                    if(cuando_fin == "menor" || cuando_inicio == "mayor"){
                        $(this).css('display','none');
                    }
                }
                
                if (tipo == "anteriores"){
                    if(cuando_inicio != "menor" || cuando_fin != "menor"){
                        $(this).css('display','none');
                    }
                }
                
                if (tipo == "posteriores"){
                    if(cuando_inicio != "mayor" && cuando_fin != "mayor"){
                        $(this).css('display','none');
                    }
                }
                
                if (tipo == "todos"){
                    $(this).css('display','block');
                    
                    if(sincro === false){
                        location.reload();
                    }
                }
               
            });
            
        }
        
        //////////////////////////////////////////////////////////////////////////////