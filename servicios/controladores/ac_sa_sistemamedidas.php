<?php
/*
 * Archivo - Controlador
 * 
 * Aqui se encuentran los controladores y funciones para las paginas:
 * 
 * - sa_sistemamedidas_tecnicos.php
 * - sa_sistemamedidas_semanal.php
 * - sa_sistemamedidas_mensual.php
 */


@require('../controladores/xajax/xajax_core/xajax.inc.php'); //servidor
			   $xajax = new xajax();
                           $xajax->configure( 'defaultMode', 'synchronous' ); 
                           $xajax->registerFunction("verEstadistico");
                           $xajax->registerFunction("buscar");
			//$xajax->processRequest();  //movido al principal sa_sistemamedidas.php para procesar tamb el listado de empresas de ac_iv_general

function buscar($valForm) {
    $objResponse = new xajaxResponse();
	
	$idEmpresa = $valForm['lstEmpresa'];
	
    $fecha1 = $valForm['txtFechaDesde'];
    $fecha2 = $valForm['txtFechaHasta'];
    
    $anio1 = date("Y",strtotime($fecha1));
    $anio2 = date("Y",strtotime($fecha2));
    
    $fecha_desde = date("Y/m/d",strtotime($fecha1));
    $fecha_hasta = date("Y/m/d",strtotime($fecha2));
    
    $tipo_medida = $valForm["tipo_medida"];//1 = tecnicos, 2 = semanal, 3 = mensual
    
    
    if ($fecha_desde > $fecha_hasta){
        $objResponse->script('alert("La primera fecha no debe ser mayor a la segunda")');
    }elseif($anio1 != $anio2){
        $objResponse->script('alert("Debe estar en el mismo periodo de año")');
    }else{
        
        if($tipo_medida == "1"){
            $objResponse->loadCommands(verEstadistico($fecha_desde,$fecha_hasta,$idEmpresa));
            $objResponse->assign("titulopag","innerHTML", "Control de taller - Técnicos");
        }elseif($tipo_medida == "2"){
            $objResponse->loadCommands(verMagnetoplanoDiario($fecha_desde,$fecha_hasta,$idEmpresa));
            $objResponse->assign("titulopag","innerHTML", "Control de taller - Magnetoplano Diario");
        }elseif($tipo_medida == "3"){
            $objResponse->loadCommands(verAsistencia($fecha_desde,$fecha_hasta,$idEmpresa)); 
            $objResponse->assign("titulopag","innerHTML", "Control de taller - Asistencia");
		}elseif($tipo_medida == "4"){
            $objResponse->loadCommands(verSemanal($fecha_desde,$fecha_hasta));
            $objResponse->assign("titulopag","innerHTML", "Sistema de medidas - Semanal");
        }elseif($tipo_medida == "5"){
            $objResponse->loadCommands(verMensual($fecha_desde,$fecha_hasta));
            $objResponse->assign("titulopag","innerHTML", "Sistema de medidas - Mensual");
        }else{
            $objResponse->script("alert('Error de selección de medidas');");
        }
        
    }
    
    //$objResponse->script("alert('$fecha1 => $fecha2');");
    return $objResponse;
}





/*  SISTEMA DE MEDIDAS TECNICOS  */

   /**
    * Esta funcion se encarga de buscar cada mecanico, buscar los dias que vino, y formar el cuadro estadistico.
    * Depende totamente de los dias que vino, sino hay registro ese dia nisiquiera se formara la columna de dicho dia
    * @global type $conex Link de conexion
    * @param date $fecha_desde Fecha de inicio proveniente del formulario
    * @param date $fecha_hasta Fecha de fin proveniente del formulario
    * @return \xajaxResponse Devuelve tabla con toda la informacion de cada mecanico
    */

function verEstadistico($fecha_desde,$fecha_hasta, $idEmpresa) {//Es una funcion xajax
    
    $tabla_estadistico_mecanicos = "";
    global $conex;
    
    //Variables auxiliares para construir la tabla con sus totales
    $aux_header = true; //usado el mostrar el "dia inicio" - "dia fin" y "totales

    //$respuesta = new xajaxResponse();

    $query_seleccion_mecanicos = "SELECT sa_mecanicos.id_empleado, id_mecanico, nombre_empleado, nombre_cargo 
                            FROM sa_mecanicos 
                            LEFT JOIN vw_pg_empleados ON sa_mecanicos.id_empleado = vw_pg_empleados.id_empleado
                            LEFT JOIN sa_equipos_mecanicos ON sa_mecanicos.id_equipo_mecanico = sa_equipos_mecanicos.id_equipo_mecanico
                            WHERE activo = 1 AND sa_equipos_mecanicos.id_empresa = ".$idEmpresa."  AND (id_cargo = 13 OR id_cargo = 14 OR id_cargo = 15) ORDER BY nombre_empleado";
    $datos_mecanicos = mysql_query($query_seleccion_mecanicos, $conex) or die("Error de Selección 'Mecanicos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());

    $mecanicos = array();
    while (($row = mysql_fetch_array($datos_mecanicos)) != NULL) {
        $mecanicos[$row['id_mecanico']] = $row['nombre_empleado'];
    }

    foreach ($mecanicos as $id_mecanico => $nombre_mecanico) {//recorro el listado de mecanicos
        //variables ARRAY para guardar la informacion que se genera por casilla y usarla anteriormente
        $fecha_busqueda = array(); //guarda los dias de la busqueda "03jun - 17jun", incluyenco casilla "total", en formato "2013-05-03x2013-05-07" y "total" (para su previo uso)
        $total_mes_presencia = array(); //Es el numero de cada casilla (columna) devuelta por los dias de presencia, y sus totales. una a una
        $total_mes_trabajadas = array(); //Es el numero de cada casilla devuelta por los dias trabajados y sus totales.
        $total_mes_facturadas = array(); //Es el numero de cada casilla devuelta por los dias facturados y sus totales.
        //Consulta, busca los dias de asistencia de un mecanico, y a partir de esa informacion es que se crea el recuadro
        $query_registros_presencia = "SELECT  
            WEEKOFYEAR(fecha_creada) AS semana,
            MONTH(fecha_creada) as mes,
            YEAR(fecha_creada) as anio,
            id_mecanico, 
            COUNT(fecha_creada) as registro_semana,
            DATE_SUB(fecha_creada, INTERVAL WEEKDAY(fecha_creada) DAY) as lunes_inicio,
            DATE_ADD(DATE_SUB(fecha_creada, INTERVAL WEEKDAY(fecha_creada) DAY), INTERVAL 4 DAY) as viernes_final,
            DATE_SUB(LAST_DAY(fecha_creada),INTERVAL DAY(LAST_DAY(fecha_creada))-1 DAY) as primerdia_mes,
            LAST_DAY(fecha_creada) as ultimodia_mes,
            SUM(minutos_presencia) AS total_minutos, 
            fecha_creada
            FROM sa_presencia_mecanicos
            WHERE DATE(fecha_creada) BETWEEN '".$fecha_desde."' AND '".$fecha_hasta."' AND id_mecanico = ".$id_mecanico."
            GROUP BY mes, semana";

        $registros_presencia = mysql_query($query_registros_presencia, $conex) or die("Error de Selección 'Registro individual hoy': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
        $cantidad_registros = mysql_num_rows($registros_presencia);
        
        if ($cantidad_registros == 0) {
            $tabla_estadistico_mecanicos .= "<h4>".$nombre_mecanico."</h4>";
            $tabla_estadistico_mecanicos .= "No contiene registros";
        } else {
            //nombre del mecanico tecnico
            $tabla_estadistico_mecanicos .= "<h4>$nombre_mecanico</h4>";

            //tabla de informacion resumen sistema de medidas. 
            //Cada fila "<tr>" se crea por separado para evitar conflictos y tener mejor manipulacion (es dificil trabajar con tablas tan complejas)
            $tabla_estadistico_mecanicos .= "<table class='tabla-mecanicos' border=1>";

            //Fila fechas de mes
            $tabla_estadistico_mecanicos .= "<tr> <td>Mes</td>";

            $valores = array(); //auxiliar, guardo el mes de cada registro "row[mes]" para realizar la escritura de fecha y contar cuantos registros por mes y su "colspan"
            while ($row = mysql_fetch_array($registros_presencia)) {
                array_push($valores, $row["mes"]);
            }
            $valores = elementosrepetidos($valores, true); //funcion para contar cuantos registros "columnas" por mes hay

            if ($cantidad_registros) {//compruebo que no devuelve vacio, y luego recorro otra vez el mysql_array
                mysql_data_seek($registros_presencia, 0); //reestablece el puntero del result en el principio "0"
            }

            //variables auxiliares para la formacion de las columnas en mes
            $puntero = 0;
            $dato = 1;
            while ($row = mysql_fetch_array($registros_presencia)) {

                if ($valores[$puntero]["count"] == $dato) {
                    $colspan = $valores[$puntero]["count"] + 1;
                    $tabla_estadistico_mecanicos .= "<td colspan='$colspan'><b>" . mostrarmes($row["mes"]) . " - " . (date("Y", strtotime($row["fecha_creada"]))) . "</b></td>";

                    $puntero++;
                    $dato = 0;
                }
                $dato++;
            }

            $tabla_estadistico_mecanicos .= "</tr>";

            //Fila fechas por semanas en periodos de dias   
            $tabla_estadistico_mecanicos .= "<tr> <td>Semanas</td>";

            if ($cantidad_registros) {//volver a recorrer el array
                mysql_data_seek($registros_presencia, 0);
            }

            //auxiliares para formar las columnas de semanas y total
            $puntero = 0;
            $dato = 1;
            //recorro las fechas de los dias de semana lunes - viernes, fin de mes, inicio de mes, y las convierto en letras
            //el if es para verificar si debe imprimir la semana completa o si se pasa hasta el inicio-fin de mes
            while ($row = mysql_fetch_array($registros_presencia)) {

                if ((date("m", strtotime($row["lunes_inicio"])) < date("m", strtotime($row["viernes_final"])) && date("m", strtotime($row["lunes_inicio"])) == date("m", strtotime($row["fecha_creada"])))) {
                    $dia_inicio = date("d", strtotime($row["lunes_inicio"])) . traducefecha($row["lunes_inicio"]);
                    $dia_fin = date("d", strtotime($row["ultimodia_mes"])) . traducefecha($row["ultimodia_mes"]);
                } elseif ((date("m", strtotime($row["lunes_inicio"])) < date("m", strtotime($row["viernes_final"])) && date("m", strtotime($row["lunes_inicio"])) < date("m", strtotime($row["fecha_creada"])))) {
                    $dia_inicio = date("d", strtotime($row["primerdia_mes"])) . traducefecha($row["primerdia_mes"]);
                    $dia_fin = date("d", strtotime($row["viernes_final"])) . traducefecha($row["viernes_final"]);
                } else {
                    $dia_inicio = date("d", strtotime($row["lunes_inicio"])) . traducefecha($row["lunes_inicio"]);
                    $dia_fin = date("d", strtotime($row["viernes_final"])) . traducefecha($row["viernes_final"]);
                }
                if ($aux_header) {

                    //guardo fechas para realizar busquedas de trabajo exactas, las convierto a fechas completas y la guardo en array
                    $dia_busqueda1 = date("Y", strtotime($row["primerdia_mes"])) . "-" . date("m", strtotime($row["primerdia_mes"])) . "-" . preg_replace('/[a-zA-Z]/', '', $dia_inicio);
                    $dia_busqueda2 = date("Y", strtotime($row["primerdia_mes"])) . "-" . date("m", strtotime($row["primerdia_mes"])) . "-" . preg_replace('/[a-zA-Z]/', '', $dia_fin);

                    array_push($fecha_busqueda, $dia_busqueda1 . "x" . $dia_busqueda2);

                    $tabla_estadistico_mecanicos .= "<td>" . $dia_inicio . "-" . $dia_fin . "</td>";
                }

                if ($valores[$puntero]["count"] == $dato) {
                    array_push($fecha_busqueda, "total");
                    $tabla_estadistico_mecanicos .= "<td><b>Total</b></td>";
                    $puntero++;
                    $dato = 0;
                }
                $dato++;
            }
            $tabla_estadistico_mecanicos .= "</tr>";

            //Horas de presencia calculada:                 
            $tabla_estadistico_mecanicos .= "<tr><td style='white-space:nowrap;'>14.- Horas Presencia</td>";

            if ($cantidad_registros) {//recorrer el array nuevamente
                mysql_data_seek($registros_presencia, 0);
            }

            //Auxiliares que indicaran cuando se imprime el total semanal y cuando el total mensual
            $calcular_mes = 0;
            $total = 0;
            $puntero = 0;
            $dato = 1;

            //recorro el dia y los registros de dia se multiplican para dar las 44 horas semanales, sino se vino un dia ese dia no se cuenta 5*8.7 = 43,5 = 44!
            while ($row = mysql_fetch_array($registros_presencia)) {
                array_push($total_mes_presencia, round($row["registro_semana"] * 8.7));
                $tabla_estadistico_mecanicos .= "<td>" . round($row["registro_semana"] * 8.7) . "</td>";

                $total2 = round($row["registro_semana"] * 8.7);
                if ($calcular_mes == $row["mes"]) {
                    $total2 = $total + $total2;
                }

                if ($valores[$puntero]["count"] == $dato) {

                    array_push($total_mes_presencia, $total2);
                    $tabla_estadistico_mecanicos .= "<td><b>" . $total2 . "</b></td>";
                    $puntero++;
                    $dato = 0;
                }
                $dato++;

                $total = $total2;
                $calcular_mes = $row["mes"];
            }
            $tabla_estadistico_mecanicos .= "</tr>";

            //Horas de presencia - trabajadas
            $tabla_estadistico_mecanicos .= "<tr><td style='white-space:nowrap;'>15.- Horas Trabajadas</td>";

            if ($cantidad_registros) {
                mysql_data_seek($registros_presencia, 0);
            }

            //mismos auxiliares para la construccion de la tabla
            $calcular_mes = 0;
            $total = 0;
            $puntero = 0;
            $dato = 1;

            while ($row = mysql_fetch_array($registros_presencia)) {
                array_push($total_mes_trabajadas, minutosHoras($row["total_minutos"]));
                $tabla_estadistico_mecanicos .= "<td>" . minutosHoras($row["total_minutos"]) . "</td>";

                $total2 = minutosHoras($row["total_minutos"]);
                if ($calcular_mes == $row["mes"]) {
                    $total2 = $total + $total2;
                }

                if ($valores[$puntero]["count"] == $dato) {

                    array_push($total_mes_trabajadas, $total2);
                    $tabla_estadistico_mecanicos .= "<td><b>" . $total2 . "</b></td>";
                    $puntero++;
                    $dato = 0;
                }
                $dato++;

                $total = $total2;
                $calcular_mes = $row["mes"];
            }
            $tabla_estadistico_mecanicos .= "</tr>";

            $tabla_estadistico_mecanicos .= "<tr>";

            //Horas facturadas - provienen de los temparios facturados por la seccion de dia fecha por semana
            $tabla_estadistico_mecanicos .= "<td style='white-space:nowrap;'>16.- Horas Facturadas</td>";
            $totales_facturadas = 0; //auxiliar para llevar la suma para el total de mes
            //var_dump($fecha_busqueda);
            for ($i = 0; $i < count($fecha_busqueda); $i++)//recorro la cantidad de casillas (columnas) por cada rango de fecha semana (contiene "2013-05-03x2013-05-07" y "total")
                if ($fecha_busqueda[$i] != "total") {//si el valor del array no contiene "total" imprime la busqueda comun
                    $separar_fechas = explode("x", $fecha_busqueda[$i]); //separa las fechas para usarlo en el query
                    $fecha1 = $separar_fechas[0];
                    $fecha2 = $separar_fechas[1];
                    //Valores de ejemplo que si funciona = BETWEEN '2013-02-28' AND '2013-02-28'
                    
                    //Este query busca las ordenes facturadas (por rango de fecha establecido en los dias que estuvo presentes) 
                    //y luego suma los ut de las mano de obra de cada tempario asociado, total por cada mecanico, cada rango de fecha
                    //Este metodo puede poner lento ya que se hace consulta por cada columna, pero debe hacerse asi por el rango de fecha delimitado! no quice hacerlo asi pero simplemnte no halle otra opcion, no hay registro diario
                    $query_horas_facturadas = "SELECT  SUM(ut) as total_horas_facturadas
                                               FROM sa_orden
                                               LEFT JOIN sa_det_orden_tempario ON sa_orden.id_orden = sa_det_orden_tempario.id_orden
                                               WHERE fecha_factura BETWEEN '$fecha1' AND '$fecha2' AND id_mecanico = $id_mecanico GROUP BY id_mecanico";
                    $seleccion_horas_facturadas = mysql_query($query_horas_facturadas) or die("Error de Selección 'horas facturadas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
                    $cantidad_horas_facturadas = mysql_num_rows($seleccion_horas_facturadas);

                    if ($cantidad_horas_facturadas == 0) {//estas filas tienen posiblidad de que no tengan registros, por lo tanto se reemplaza con 0
                        $tabla_estadistico_mecanicos .= "<td>0</td>";
                        array_push($total_mes_facturadas, 0);
                    } else {
                        while ($row = mysql_fetch_array($seleccion_horas_facturadas)) {//si contiene informacion, imprime el total de horas
                            $total_horas_facturadas = round($row["total_horas_facturadas"] / 100);
                            $tabla_estadistico_mecanicos .= "<td>$total_horas_facturadas</td>";
                            array_push($total_mes_facturadas, $total_horas_facturadas);
                            $totales_facturadas = $totales_facturadas + $total_horas_facturadas;
                        }
                    }
                } else {//si contiene "total"
                    $tabla_estadistico_mecanicos .= "<td><b>$totales_facturadas</b></td>";
                    array_push($total_mes_facturadas, $totales_facturadas);
                    $totales_facturadas = 0;
                }

            $tabla_estadistico_mecanicos .= "</tr>";

            //PORCENTAJES -usando los array con la informacion guardada
            //Porcentaje de ocupacion horas_trabajadas/horas_presencia * 100 y lo redondeo con 1 decimal
            $tabla_estadistico_mecanicos .= "<td style='white-space:nowrap;'><b>%Ocupación (85%) 15/14</b></td>";

            for ($i = 0; $i < count($total_mes_presencia); $i++) {
                $tabla_estadistico_mecanicos .= "<td>";
                if ($total_mes_presencia[$i] != 0) {
                    $porcentaje_ocupacion = round(($total_mes_trabajadas[$i] / $total_mes_presencia[$i]) * 100, 1);
                } else {
                    $porcentaje_ocupacion = 0;
                }
                $tabla_estadistico_mecanicos .= "<b>$porcentaje_ocupacion%</b>";
                $tabla_estadistico_mecanicos .= "</td>";



                //separo fechas y creo un input hidden para las fechas del grafico individual por mecanico
                if ($fecha_busqueda[$i] == "total") {
                    $separar_fechas = explode("x", $fecha_busqueda[$i - 1]);
                    $sacar_mes = mostrarmes(date("m", strtotime($separar_fechas[0])));
                    $sacar_anio = date("Y", strtotime($separar_fechas[0]));
                    $fecha_para_grafico = $sacar_mes . "-" . $sacar_anio;

                    $tabla_estadistico_mecanicos .= "<input type='hidden' name='fechas_grafico$id_mecanico' value='" . $fecha_para_grafico . "' />";

                    //Input hidden para tomar valores para graficos individuales por mecanico, porcentaje de ocupacion total
                    $tabla_estadistico_mecanicos .= "<input type='hidden' name='porcentaje_ocupacion$id_mecanico' value='$porcentaje_ocupacion' />";
                }
            }

            $tabla_estadistico_mecanicos .= "</tr>";

            //Porcentaje de Eficiencia horas_facturadas/horas_trabajadas * 100 y lo redondeo con 1 decimal
            $tabla_estadistico_mecanicos .= "<td style='white-space:nowrap;'><b>%Eficiencia (110%) 16/15</b></td>";

            for ($i = 0; $i < count($total_mes_presencia); $i++) {
                $tabla_estadistico_mecanicos .= "<td>";

                if ($total_mes_trabajadas[$i] != 0) {
                    $porcentaje_eficiencia = round(($total_mes_facturadas[$i] / $total_mes_trabajadas[$i]) * 100, 1);
                } else {
                    $porcentaje_eficiencia = 0;
                }
                $tabla_estadistico_mecanicos .= "<b>$porcentaje_eficiencia%</b>";
                $tabla_estadistico_mecanicos .= "</td>";

                if ($fecha_busqueda[$i] == "total") {
                    //Input hidden para tomar valores para graficos individuales por mecanico, porcentaje de eficiencia total
                    $tabla_estadistico_mecanicos .= "<input type='hidden' name='porcentaje_eficiencia$id_mecanico' value='$porcentaje_eficiencia' />";
                }
            }

            $tabla_estadistico_mecanicos .= "</tr>";

            //Porcentaje de Productividad horas_facturadas/horas_presencia *100 y lo redondeo con 1 decimal
            $tabla_estadistico_mecanicos .= "<td style='white-space:nowrap;'><b>%Productividad (100%) 16/14</b></td>";

            for ($i = 0; $i < count($total_mes_presencia); $i++) {
                $tabla_estadistico_mecanicos .= "<td>";
                if ($total_mes_presencia[$i] != 0) {
                    $porcentaje_productividad = round(($total_mes_facturadas[$i] / $total_mes_presencia[$i]) * 100, 1);
                } else {
                    $porcentaje_productividad = 0;
                }
                $tabla_estadistico_mecanicos .= "<b>$porcentaje_productividad%</b>";
                $tabla_estadistico_mecanicos .= "</td>";

                if ($fecha_busqueda[$i] == "total") {
                    //Input hidden para tomar valores para graficos individuales por mecanico, porcentaje de productividad total
                    $tabla_estadistico_mecanicos .= "<input type='hidden' name='porcentaje_productividad$id_mecanico' value='$porcentaje_productividad' />";
                }
            }

            $tabla_estadistico_mecanicos .= "</tr>";

            $tabla_estadistico_mecanicos .= "</table>";
            $tabla_estadistico_mecanicos .= "<br><button id='ver$id_mecanico' class='noprint' onClick='tomarValores(\"$nombre_mecanico\",$id_mecanico);'>Ver Gráfico</button>";
            $tabla_estadistico_mecanicos .= "<button id='ocultar$id_mecanico' class='noprint' style='display:none;' onClick='ocultar($id_mecanico);'>Ocultar Gráfico</button>";
            $tabla_estadistico_mecanicos .= '<div id="container' . $id_mecanico . '" style="min-width: 400px; height: 400px; margin: 0 auto; display: none;"></div>';
        }
					
		
    }
    
    $respuesta = new xajaxResponse();
    
    $respuesta->clear("contenedor_estadistico", "innerHTML");    
    $respuesta->assign("contenedor_estadistico","innerHTML",$tabla_estadistico_mecanicos);
    return $respuesta;
}



//HORAS EN MAGNETOPLANO DIARIO

function verMagnetoplanoDiario($fecha_desde,$fecha_hasta, $idEmpresa){//Es una funcion xajax
    
    $tabla_estadistico_mecanicos = "";
    global $conex;
    
    //Variables auxiliares para construir la tabla con sus totales
    $aux_header = true; //usado el mostrar el "dia inicio" - "dia fin" y "totales

    //$respuesta = new xajaxResponse();

    $query_seleccion_mecanicos = "SELECT sa_mecanicos.id_empleado, id_mecanico, nombre_empleado, nombre_cargo 
                            FROM sa_mecanicos 
                            LEFT JOIN vw_pg_empleados ON sa_mecanicos.id_empleado = vw_pg_empleados.id_empleado
                            LEFT JOIN sa_equipos_mecanicos ON sa_mecanicos.id_equipo_mecanico = sa_equipos_mecanicos.id_equipo_mecanico
                            WHERE activo = 1 AND sa_equipos_mecanicos.id_empresa = ".$idEmpresa."  AND (id_cargo = 13 OR id_cargo = 14 OR id_cargo = 15) ORDER BY nombre_empleado";
    $datos_mecanicos = mysql_query($query_seleccion_mecanicos, $conex) or die("Error de Selección 'Mecanicos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());

    $mecanicos = array();
    while (($row = mysql_fetch_array($datos_mecanicos)) != NULL) {
        $mecanicos[$row['id_mecanico']] = $row['nombre_empleado'];
    }

    foreach ($mecanicos as $id_mecanico => $nombre_mecanico) {//recorro el listado de mecanicos
        //variables ARRAY para guardar la informacion que se genera por casilla y usarla anteriormente
        $fecha_busqueda = array(); //guarda los dias de la busqueda "03jun - 17jun", incluyenco casilla "total", en formato "2013-05-03x2013-05-07" y "total" (para su previo uso)
        $total_mes_presencia = array(); //Es el numero de cada casilla (columna) devuelta por los dias de presencia, y sus totales. una a una
        $total_mes_trabajadas = array(); //Es el numero de cada casilla devuelta por los dias trabajados y sus totales.
        $total_mes_facturadas = array(); //Es el numero de cada casilla devuelta por los dias facturados y sus totales.
        //Consulta, busca los dias de asistencia de un mecanico, y a partir de esa informacion es que se crea el recuadro
        $query_registros_presencia = "SELECT  
            WEEKOFYEAR(fecha_creada) AS semana,
            MONTH(fecha_creada) as mes,
            YEAR(fecha_creada) as anio,
            id_mecanico, 
            COUNT(DISTINCT WEEK(fecha_creada)) as registro_semana,
            DATE_SUB(fecha_creada, INTERVAL WEEKDAY(fecha_creada) DAY) as lunes_inicio,
            DATE_ADD(DATE_SUB(fecha_creada, INTERVAL WEEKDAY(fecha_creada) DAY), INTERVAL 4 DAY) as viernes_final,
            DATE_SUB(LAST_DAY(fecha_creada),INTERVAL DAY(LAST_DAY(fecha_creada))-1 DAY) as primerdia_mes,
            LAST_DAY(fecha_creada) as ultimodia_mes,
            SUM(tiempo_transcurrido) AS total_minutos, 
            fecha_creada
            FROM sa_magnetoplano_diario
            WHERE DATE(fecha_creada) BETWEEN '".$fecha_desde."' AND '".$fecha_hasta."' AND id_mecanico = ".$id_mecanico."
            GROUP BY mes, semana";

        $registros_presencia = mysql_query($query_registros_presencia, $conex) or die("Error de Selección 'Registro individual hoy': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
        $cantidad_registros = mysql_num_rows($registros_presencia);
        
        if ($cantidad_registros == 0) {
            $tabla_estadistico_mecanicos .= "<h4>".$nombre_mecanico."</h4>";
            $tabla_estadistico_mecanicos .= "No contiene registros";
        } else {
            //nombre del mecanico tecnico
            $tabla_estadistico_mecanicos .= "<h4>$nombre_mecanico</h4>";

            //tabla de informacion resumen sistema de medidas. 
            //Cada fila "<tr>" se crea por separado para evitar conflictos y tener mejor manipulacion (es dificil trabajar con tablas tan complejas)
            $tabla_estadistico_mecanicos .= "<table class='tabla-mecanicos' border=1>";

            //Fila fechas de mes
            $tabla_estadistico_mecanicos .= "<tr> <td>Mes</td>";

            $valores = array(); //auxiliar, guardo el mes de cada registro "row[mes]" para realizar la escritura de fecha y contar cuantos registros por mes y su "colspan"
            while ($row = mysql_fetch_array($registros_presencia)) {
                array_push($valores, $row["mes"]);
            }
            $valores = elementosrepetidos($valores, true); //funcion para contar cuantos registros "columnas" por mes hay

            if ($cantidad_registros) {//compruebo que no devuelve vacio, y luego recorro otra vez el mysql_array
                mysql_data_seek($registros_presencia, 0); //reestablece el puntero del result en el principio "0"
            }

            //variables auxiliares para la formacion de las columnas en mes
            $puntero = 0;
            $dato = 1;
            while ($row = mysql_fetch_array($registros_presencia)) {

                if ($valores[$puntero]["count"] == $dato) {
                    $colspan = $valores[$puntero]["count"] + 1;
                    $tabla_estadistico_mecanicos .= "<td colspan='$colspan'><b>" . mostrarmes($row["mes"]) . " - " . (date("Y", strtotime($row["fecha_creada"]))) . "</b></td>";

                    $puntero++;
                    $dato = 0;
                }
                $dato++;
            }

            $tabla_estadistico_mecanicos .= "</tr>";

            //Fila fechas por semanas en periodos de dias   
            $tabla_estadistico_mecanicos .= "<tr> <td>Semanas</td>";

            if ($cantidad_registros) {//volver a recorrer el array
                mysql_data_seek($registros_presencia, 0);
            }

            //auxiliares para formar las columnas de semanas y total
            $puntero = 0;
            $dato = 1;
			
			$arraySemanas = array(); //nuevo para periodos entre semana contar los dias y sacar las horas de presencia
			
            //recorro las fechas de los dias de semana lunes - viernes, fin de mes, inicio de mes, y las convierto en letras
            //el if es para verificar si debe imprimir la semana completa o si se pasa hasta el inicio-fin de mes
            while ($row = mysql_fetch_array($registros_presencia)) {

                if ((date("m", strtotime($row["lunes_inicio"])) < date("m", strtotime($row["viernes_final"])) && date("m", strtotime($row["lunes_inicio"])) == date("m", strtotime($row["fecha_creada"])))) {
                    $dia_inicio = date("d", strtotime($row["lunes_inicio"])) . traducefecha($row["lunes_inicio"]);
                    $dia_fin = date("d", strtotime($row["ultimodia_mes"])) . traducefecha($row["ultimodia_mes"]);
					array_push($arraySemanas,$row["lunes_inicio"].'X'.$row["ultimodia_mes"]);//nuevo
                } elseif ((date("m", strtotime($row["lunes_inicio"])) < date("m", strtotime($row["viernes_final"])) && date("m", strtotime($row["lunes_inicio"])) < date("m", strtotime($row["fecha_creada"])))) {
                    $dia_inicio = date("d", strtotime($row["primerdia_mes"])) . traducefecha($row["primerdia_mes"]);
                    $dia_fin = date("d", strtotime($row["viernes_final"])) . traducefecha($row["viernes_final"]);
					array_push($arraySemanas,$row["primerdia_mes"].'X'.$row["viernes_final"]);//nuevo
                } else {
                    $dia_inicio = date("d", strtotime($row["lunes_inicio"])) . traducefecha($row["lunes_inicio"]);
                    $dia_fin = date("d", strtotime($row["viernes_final"])) . traducefecha($row["viernes_final"]);
					array_push($arraySemanas,$row["lunes_inicio"].'X'.$row["viernes_final"]);//nuevo					
                }
                if ($aux_header) {

                    //guardo fechas para realizar busquedas de trabajo exactas, las convierto a fechas completas y la guardo en array
                    $dia_busqueda1 = date("Y", strtotime($row["primerdia_mes"])) . "-" . date("m", strtotime($row["primerdia_mes"])) . "-" . preg_replace('/[a-zA-Z]/', '', $dia_inicio);
                    $dia_busqueda2 = date("Y", strtotime($row["primerdia_mes"])) . "-" . date("m", strtotime($row["primerdia_mes"])) . "-" . preg_replace('/[a-zA-Z]/', '', $dia_fin);

                    array_push($fecha_busqueda, $dia_busqueda1 . "x" . $dia_busqueda2);

                    $tabla_estadistico_mecanicos .= "<td>" . $dia_inicio . "-" . $dia_fin . "</td>";
                }

                if ($valores[$puntero]["count"] == $dato) {
                    array_push($fecha_busqueda, "total");
                    $tabla_estadistico_mecanicos .= "<td><b>Total</b></td>";
                    $puntero++;
                    $dato = 0;
                }
                $dato++;
            }
            $tabla_estadistico_mecanicos .= "</tr>";

            //Horas de presencia calculada:                 
            $tabla_estadistico_mecanicos .= "<tr><td style='white-space:nowrap;'>14.- Horas Presencia Magnetoplano</td>";

            if ($cantidad_registros) {//recorrer el array nuevamente
                mysql_data_seek($registros_presencia, 0);
            }

            //Auxiliares que indicaran cuando se imprime el total semanal y cuando el total mensual
            $calcular_mes = 0;
            $total = 0;
            $puntero = 0;
            $dato = 1;

            //recorro el dia y los registros de dia se multiplican para dar las 44 horas semanales, sino se vino un dia ese dia no se cuenta 5*8.7 = 43,5 = 44!
            foreach ($arraySemanas as $key => $valor) {
				$fechasSemana = explode("X",$valor);
				$periodoInicio = date("Y-m-d",strtotime($fechasSemana[0]));
				$periodoFin = date("Y-m-d",strtotime($fechasSemana[1]));
				
				$datetime1 = new DateTime($periodoInicio);
				$datetime2 = new DateTime($periodoFin);
				$interval = $datetime1->diff($datetime2);
				$diasEntreSemana = $interval->days;
				
                array_push($total_mes_presencia, round($diasEntreSemana * 8.7));
                $tabla_estadistico_mecanicos .= "<td>" . round($diasEntreSemana * 8.7) . "</td>";

                $total2 = round($diasEntreSemana * 8.7);
                if ($calcular_mes == $row["mes"]) {
                    $total2 = $total + $total2;
                }

                if ($valores[$puntero]["count"] == $dato) {

                    array_push($total_mes_presencia, $total2);
                    $tabla_estadistico_mecanicos .= "<td><b>" . $total2 . "</b></td>";
                    $puntero++;
                    $dato = 0;
                }
                $dato++;

                $total = $total2;
                $calcular_mes = $row["mes"];
            }
            $tabla_estadistico_mecanicos .= "</tr>";

            //Horas de presencia - trabajadas
            $tabla_estadistico_mecanicos .= "<tr><td style='white-space:nowrap;'>15.- Horas Trabajadas Magnetoplano</td>";

            if ($cantidad_registros) {
                mysql_data_seek($registros_presencia, 0);
            }

            //mismos auxiliares para la construccion de la tabla
            $calcular_mes = 0;
            $total = 0;
            $puntero = 0;
            $dato = 1;

            while ($row = mysql_fetch_array($registros_presencia)) {
                array_push($total_mes_trabajadas, minutosHoras($row["total_minutos"]));
                $tabla_estadistico_mecanicos .= "<td>" . minutosHoras($row["total_minutos"]) . "</td>";

                $total2 = minutosHoras($row["total_minutos"]);
                if ($calcular_mes == $row["mes"]) {
                    $total2 = $total + $total2;
                }

                if ($valores[$puntero]["count"] == $dato) {

                    array_push($total_mes_trabajadas, $total2);
                    $tabla_estadistico_mecanicos .= "<td><b>" . $total2 . "</b></td>";
                    $puntero++;
                    $dato = 0;
                }
                $dato++;

                $total = $total2;
                $calcular_mes = $row["mes"];
            }
            $tabla_estadistico_mecanicos .= "</tr>";
			
			
			
			
			//PORCENTAJES -usando los array con la informacion guardada
            //Porcentaje de ocupacion horas_trabajadas/horas_presencia * 100 y lo redondeo con 1 decimal
            $tabla_estadistico_mecanicos .= "<td style='white-space:nowrap;'><b>%Ocupación (85%) 15/14</b></td>";

            for ($i = 0; $i < count($total_mes_presencia); $i++) {
                $tabla_estadistico_mecanicos .= "<td>";
                if ($total_mes_presencia[$i] != 0) {
                    $porcentaje_ocupacion = round(($total_mes_trabajadas[$i] / $total_mes_presencia[$i]) * 100, 1);
                } else {
                    $porcentaje_ocupacion = 0;
                }
                $tabla_estadistico_mecanicos .= "<b>$porcentaje_ocupacion%</b>";
                $tabla_estadistico_mecanicos .= "</td>";



                //separo fechas y creo un input hidden para las fechas del grafico individual por mecanico
                if ($fecha_busqueda[$i] == "total") {
                    $separar_fechas = explode("x", $fecha_busqueda[$i - 1]);
                    $sacar_mes = mostrarmes(date("m", strtotime($separar_fechas[0])));
                    $sacar_anio = date("Y", strtotime($separar_fechas[0]));
                    $fecha_para_grafico = $sacar_mes . "-" . $sacar_anio;

                    $tabla_estadistico_mecanicos .= "<input type='hidden' name='fechas_grafico$id_mecanico' value='" . $fecha_para_grafico . "' />";

                    //Input hidden para tomar valores para graficos individuales por mecanico, porcentaje de ocupacion total
                    $tabla_estadistico_mecanicos .= "<input type='hidden' name='porcentaje_ocupacion$id_mecanico' value='$porcentaje_ocupacion' />";
                }
            }

            $tabla_estadistico_mecanicos .= "</tr>";
       

            $tabla_estadistico_mecanicos .= "</table>";
        }
					
    }
    
    $respuesta = new xajaxResponse();
    
    $respuesta->clear("contenedor_estadistico", "innerHTML");    
    $respuesta->assign("contenedor_estadistico","innerHTML",$tabla_estadistico_mecanicos);
    return $respuesta;
	
}


//ASISTENCIA DIARIA

function verAsistencia($fecha_desde,$fecha_hasta, $idEmpresa){//Es una funcion xajax
    $objResponse = new xajaxResponse();
	
    $tablaAsistenciaMecanicos = "";
    global $conex;

    $query_seleccion_mecanicos = "SELECT sa_mecanicos.id_empleado, id_mecanico, nombre_empleado, nombre_cargo 
                            FROM sa_mecanicos 
                            LEFT JOIN vw_pg_empleados ON sa_mecanicos.id_empleado = vw_pg_empleados.id_empleado
                            LEFT JOIN sa_equipos_mecanicos ON sa_mecanicos.id_equipo_mecanico = sa_equipos_mecanicos.id_equipo_mecanico
                            WHERE activo = 1 AND sa_equipos_mecanicos.id_empresa = ".$idEmpresa."  AND (id_cargo = 13 OR id_cargo = 14 OR id_cargo = 15) ORDER BY nombre_empleado";
    $datos_mecanicos = mysql_query($query_seleccion_mecanicos, $conex);
	if(!$datos_mecanicos) { return  $objResponse->alert("Error de Selección 'Mecanicos': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea: ".__LINE__); }

    $mecanicos = array();
    while (($row = mysql_fetch_array($datos_mecanicos)) != NULL) {
        $mecanicos[$row['id_mecanico']] = $row['nombre_empleado'];
    }

    foreach ($mecanicos as $id_mecanico => $nombre_mecanico) {//recorro el listado de mecanicos
       
        $queryRegistrosAsistencia = "SELECT  
            id_mecanico,
            estado,
            tiempo_entrada,
            tiempo_salida,
            minutos_presencia,
            entradas,
            salidas,
            fecha_creada
            FROM sa_presencia_mecanicos
            WHERE DATE(fecha_creada) BETWEEN '".$fecha_desde."' AND '".$fecha_hasta."' AND id_mecanico = ".$id_mecanico." ";

        $registrosAsistencia = mysql_query($queryRegistrosAsistencia, $conex);
		if (!$registrosAsistencia) { return $objResponse->alert("Error de Selección 'Registro individual hoy': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea: ".__LINE__); }
        $cantidadRegistros = mysql_num_rows($registrosAsistencia);
        
        if ($cantidadRegistros == 0) {
            $tablaAsistenciaMecanicos .= "<h4 class='nombre-mecanico'>".$nombre_mecanico." <small> -  No contiene registros</small></h4>";
           // $tablaAsistenciaMecanicos .= "";
        } else {
            //nombre del mecanico tecnico
            //$tablaAsistenciaMecanicos .= "<h4 class='nombre-mecanico'>$nombre_mecanico</h4>";
            $tablaAsistenciaMecanicos .= "<div class='datagrid'><table class='tabla-mecanicos-asistencia' >";
			
			$tablaAsistenciaMecanicos .= "<thead>";
			$tablaAsistenciaMecanicos .="<tr><th colspan='8'>".$nombre_mecanico."</th></tr>";
			$tablaAsistenciaMecanicos .="<tr>";			
			$tablaAsistenciaMecanicos .="<th>Fecha Dia</th>";
			$tablaAsistenciaMecanicos .="<th>Estado</th>";
			$tablaAsistenciaMecanicos .="<th>Tiempo Presencia</th>";
			$tablaAsistenciaMecanicos .="<th>Hora Entrada</th>";
			$tablaAsistenciaMecanicos .="<th>Ult. Entrada</th>";
			$tablaAsistenciaMecanicos .="<th>Ult. Salida</th>";
			$tablaAsistenciaMecanicos .="<th>Nro Ent.</th>";
			$tablaAsistenciaMecanicos .="<th>Nro Sal.</th>";  
			$tablaAsistenciaMecanicos .="</tr>";
			$tablaAsistenciaMecanicos .= "</thead>";
			
			$tablaAsistenciaMecanicos .= "<tbody>";
			while ($row = mysql_fetch_assoc($registrosAsistencia)){
				$tablaAsistenciaMecanicos .= "<tr>";
				$tablaAsistenciaMecanicos .= "<td>".date("d-m-Y",strtotime($row['fecha_creada']))."</td>";
				if($row['estado'] == 1) { $estado = "En taller"; } else { $estado = "Fuera Taller" ;}
				$tablaAsistenciaMecanicos .= "<td>".$estado."</td>";				
				$tablaAsistenciaMecanicos .= "<td>".m2h($row['minutos_presencia'])."</td>";
				$tablaAsistenciaMecanicos .= "<td>".date("h:m a",strtotime($row['fecha_creada']))."</td>";
				$tablaAsistenciaMecanicos .= "<td>".date("h:m a",strtotime($row['tiempo_entrada']))."</td>";
				if($row['tiempo_salida'] != NULL && $row['tiempo_salida'] != "" && $row['tiempo_salida'] != " ") { $tiempoSalida = date("h:m a",strtotime($row['tiempo_salida'])); } else { $tiempoSalida = $row['tiempo_salida']; }
				$tablaAsistenciaMecanicos .= "<td>".$tiempoSalida."</td>";
				$tablaAsistenciaMecanicos .= "<td>".$row['entradas']."</td>";
				$tablaAsistenciaMecanicos .= "<td>".$row['salidas']."</td>";				
				$tablaAsistenciaMecanicos .= "</tr>";
			}
			
			$tablaAsistenciaMecanicos .= "</tbody>";
			$tablaAsistenciaMecanicos .= "</table> </div> <br><br>";
			
			//$tablaAsistenciaMecanicos .= "<hr class='hr-asistencia'>";

		}
		
	}
	
    $objResponse->clear("contenedor_estadistico", "innerHTML");    
    $objResponse->assign("contenedor_estadistico","innerHTML",$tablaAsistenciaMecanicos);
    return $objResponse;
	
}






/*  SISTEMA DE MEDIDAS MENSUAL  */

function verMensual($fecha_desde,$fecha_hasta){
    $anio = date("Y", strtotime($fecha_desde));
    
    global $conex;
    $respuesta = new xajaxResponse();
    
    $mes_inicio = date("m",strtotime($fecha_desde)); //mes donde indica el inicio de la tabla 5-6 1-12 etc 
    $mes_fin = date("m",strtotime($fecha_hasta)); //mes donde indica el fin de la tabla
    $cantidad_mes = $mes_inicio - $mes_fin;//resta de meses para saber cuantos hay 3-6 = 3 y luego sumo +1 para que de correcto 4
    $cantidad_mes = (abs($cantidad_mes))+1;//sumo 1 para que de correcto 4
    $cantidad_meses = array_fill($mes_inicio,$cantidad_mes,"0");//Lo lleno con la cantidad de keys como meses 6-7-8-9-10 etc, y el value a 0 para luego copiar el array y usarlo como base para llenarlo de datos provenientes de los query
    
    $tabla_estadistico_mensual = "";//Inicializando variable que contendra la tabla a devolver mediante assign() del xajax
    $tabla_estadistico_mensual .= "<button onClick='mostrarColores();' class='noprint'>Mostrar Colores</button>";
    $tabla_estadistico_mensual .= "<button onClick='crearTodosGraficos();' class='noprint'>Mostrar Gráficos</button>";
    $tabla_estadistico_mensual .= "<button onClick='dosColumnas();' style='display:none' id='boton_doscolumnas' class='noprint'>Gráficos Dos Columnas</button><br><br>";
    
    $numero_meses = array(1 => "Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");//Auxiliar de meses, sirve tanto el indice para saber en que mes va, como para traducir el mes
    $numero_meses_corto = array(1 => "Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic");//Auxiliar de meses, sirve tanto el indice para saber en que mes va, como para traducir el mes
    
    // CITA PREVIA    
    
    //// Primera fila "Titulos" mes, año, total ////
    $tabla_estadistico_mensual .= "<table class='tabla-mensual' id='cita_previa' border=1><tr>";
    $tabla_estadistico_mensual .= "<td style='white-space:nowrap;'>Cita Previa</td>";
    
    foreach ($cantidad_meses as $indice => $mes){
        if(array_key_exists($indice,$numero_meses_corto)){
            $tabla_estadistico_mensual .= "<td>".$numero_meses_corto[$indice]."-".$anio." </td>";
        }        
    }
    $tabla_estadistico_mensual .= "<td>Total</td></tr>";
    
    //// Segunda fila "Entrada de vehiculos" ////    
    $query_entrada_de_vehiculos = "SELECT COUNT(id_recepcion) as entrada_vehiculo, MONTH(fecha_entrada) as mes 
                                   FROM sa_recepcion 
                                   WHERE fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                   
    $entrada_de_vehiculos = mysql_query($query_entrada_de_vehiculos, $conex) or die("Error de Selección 'Entrada de vehiculos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>1.- Entrada de Vehículos</td>";       
          
    $array_datos_entrada_vehiculos = $cantidad_meses;//copio cantidad de meses para crear otro array no dañar el original que necesito para cada dato luego
    
    foreach ($array_datos_entrada_vehiculos as $indice => $valor) {//Lleno el array con lo encontrado en la bd para asi tener meses llenos y los que no salen rellenarlo con 0
        while ($row = mysql_fetch_array($entrada_de_vehiculos)) {
            if (array_key_exists($row["mes"], $array_datos_entrada_vehiculos)) {
                $array_datos_entrada_vehiculos[$row["mes"]] = $row["entrada_vehiculo"];
            }
        }
    }
    
    foreach ($array_datos_entrada_vehiculos as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_entrada_vehiculos)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Tercera fila "Entrada por Rapid Service"////    
    $query_entrada_rapid_service = "SELECT COUNT(id_recepcion) as rapid_service, MONTH(fecha_entrada) as mes FROM sa_recepcion WHERE serviexp = 0 AND fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
    $entrada_rapid_service = mysql_query($query_entrada_rapid_service, $conex) or die("Error de Selección 'Entrada por Rapid Service': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
        
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>1.1- Entrada por Rapid Service</td>"; 
    
    $array_datos_rapid_service = $cantidad_meses;
    foreach($array_datos_rapid_service as $indice => $valor){
        while ($row = mysql_fetch_array($entrada_rapid_service)) {
            if (array_key_exists($row["mes"], $array_datos_rapid_service)) {
                $array_datos_rapid_service[$row["mes"]] = $row["rapid_service"];
            }
        }
    }
    
    foreach ($array_datos_rapid_service as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_rapid_service)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Cuarta fila "Total dias solicitud y citas" ////    
    $query_dias_solicitud_citas = "SELECT ABS(SUM(DATEDIFF(sa_cita.fecha_solicitud,sa_recepcion.fecha_entrada))) as dias, MONTH(fecha_entrada) as mes 
                                   FROM sa_recepcion 
                                   LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita 
                                   WHERE sa_recepcion.fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
    $dias_solicitud_citas = mysql_query($query_dias_solicitud_citas, $conex) or die("Error de Selección 'Total dias solicitud y citas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>1.2- Total de días entre solicitud y las citas (Todos los clientes)</td>"; 
    
    $array_datos_solicitud_citas = $cantidad_meses;
    foreach($array_datos_solicitud_citas as $indice => $valor){
        while ($row = mysql_fetch_array($dias_solicitud_citas)) {
            if (array_key_exists($row["mes"], $array_datos_solicitud_citas)) {
                $array_datos_solicitud_citas[$row["mes"]] = $row["dias"];
            }
        }
    }
    
    foreach ($array_datos_solicitud_citas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_solicitud_citas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Quinta fila "Numero de citas" /////
    $query_numero_citas = "SELECT COUNT(DISTINCT sa_recepcion.id_cita) AS citas, MONTH(fecha_entrada) AS mes
                           FROM sa_recepcion
                           LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita
                           WHERE fecha_solicitud BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
    $numero_citas =  mysql_query($query_numero_citas, $conex) or die("Error de Selección 'Número de citas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>2- Número de Citas</td>"; 
    
    $array_datos_numero_citas = $cantidad_meses;
    foreach($array_datos_numero_citas as $indice => $valor){
        while ($row = mysql_fetch_array($numero_citas)) {
            if (array_key_exists($row["mes"], $array_datos_numero_citas)) {
                $array_datos_numero_citas[$row["mes"]] = $row["citas"];
            }
        }
    }
    
    foreach ($array_datos_numero_citas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_numero_citas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Sexta fila "Citas Realizadas" ////
    $query_citas_realizadas = "SELECT COUNT(sa_recepcion.id_cita) AS citas, MONTH(fecha_entrada) AS mes
                               FROM sa_recepcion
                               LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita
                               WHERE origen_cita = 'PROGRAMADA' AND fecha_solicitud BETWEEN '$fecha_desde' AND '$fecha_hasta'
                               GROUP BY mes";
    $citas_realizadas =  mysql_query($query_citas_realizadas, $conex) or die("Error de Selección 'Citas Realizadas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>2.1- Citas Realizadas</td>"; 
    
    $array_datos_citas_realizadas = $cantidad_meses;
    foreach($array_datos_citas_realizadas as $indice => $valor){
        while ($row = mysql_fetch_array($citas_realizadas)) {
            if (array_key_exists($row["mes"], $array_datos_citas_realizadas)) {
                $array_datos_citas_realizadas[$row["mes"]] = $row["citas"];
            }
        }
    }
    
    foreach ($array_datos_citas_realizadas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_citas_realizadas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Septima fila "Porcentaje de Citas"  ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Citas (60%) 2/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_numero_citas[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }
    
    if(array_sum($array_datos_entrada_vehiculos) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_numero_citas)/array_sum($array_datos_entrada_vehiculos))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Octava fila "Porcentaje de Citas Realizadas" ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Citas Realizadas (70%) 2.1/2</b></td>";
    
    foreach($array_datos_numero_citas as $indice => $valor){
        if($array_datos_numero_citas[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_citas_realizadas[$indice]/$array_datos_numero_citas[$indice])*100,1)."%</b></td>";
        }
    }    
    
    if(array_sum($array_datos_numero_citas) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_citas_realizadas)/array_sum($array_datos_numero_citas))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    $tabla_estadistico_mensual .= "</table>";
    // FIN CITA PREVIA
    
    // PROCESO DE RECEPCION
    
    //// Primera fila "Titulos" mes, año, total ////
    $tabla_estadistico_mensual .= "<br><table class='tabla-mensual' id='proceso_recepcion' border=1><tr>";
    $tabla_estadistico_mensual .= "<td style='white-space:nowrap;'>Proceso de Recepción</td>";
    
    foreach ($cantidad_meses as $indice => $mes){
        if(array_key_exists($indice,$numero_meses_corto)){
            $tabla_estadistico_mensual .= "<td>".$numero_meses_corto[$indice]."-".$anio." </td>";
        }        
    }
    $tabla_estadistico_mensual .= "<td>Total</td></tr>";
    
    //// Segunda fila "Vehiculos inspeccionados" ////
    $query_vehiculos_inspeccionados = "SELECT COUNT(id_recepcion) as puente, MONTH(fecha_entrada) as mes FROM sa_recepcion 
                                       WHERE puente = 0 AND fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
    $vehiculos_inspeccionados = mysql_query($query_vehiculos_inspeccionados, $conex) or die("Error de Selección 'Vehículos Inspeccionados': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>3.- Vehículos Inspeccionados</td>"; 
    
    $array_datos_vehiculos_inspeccionados = $cantidad_meses;
    foreach($array_datos_vehiculos_inspeccionados as $indice => $valor){
        while ($row = mysql_fetch_array($vehiculos_inspeccionados)) {
            if (array_key_exists($row["mes"], $array_datos_vehiculos_inspeccionados)) {
                $array_datos_vehiculos_inspeccionados[$row["mes"]] = $row["puente"];
            }
        }
    }
    
    foreach ($array_datos_vehiculos_inspeccionados as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_vehiculos_inspeccionados)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Tercera fila "Presupuestos entregados" ////
    $query_presupuestos_entregados = "SELECT COUNT(sa_presupuesto.id_presupuesto) AS presupuesto, 
											 MONTH(sa_presupuesto.fecha_presupuesto) AS mes 
									   FROM sa_presupuesto
                                       WHERE tipo_presupuesto = 1 
									   AND ((SELECT COUNT(*) AS total_tempario FROM sa_det_presup_tempario WHERE id_presupuesto = sa_presupuesto.id_presupuesto AND id_paquete IS NOT NULL) > 0 
									   		OR 
											(SELECT COUNT(*) AS total_articulo FROM sa_det_presup_articulo WHERE id_presupuesto = sa_presupuesto.id_presupuesto AND id_paquete IS NOT NULL) > 0)
									   AND DATE(fecha_presupuesto) BETWEEN '$fecha_desde' AND '$fecha_hasta' 
									   GROUP BY mes";
    $presupuestos_entregados = mysql_query($query_presupuestos_entregados, $conex) or die("Error de Selección 'Presupuestos entregados': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>4.- Presupuestos Entregados</td>"; 

    $array_datos_presupuestos_entregados = $cantidad_meses;
    foreach($array_datos_presupuestos_entregados as $indice => $valor){
        while ($row = mysql_fetch_array($presupuestos_entregados)) {
            if (array_key_exists($row["mes"], $array_datos_presupuestos_entregados)) {
                $array_datos_presupuestos_entregados[$row["mes"]] = $row["presupuesto"];
            }
        }
    }
    
    foreach ($array_datos_presupuestos_entregados as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_presupuestos_entregados)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";   
    
    //// Cuarta Fila "Reparaciones Repetidas" ////    
     $query_reparaciones_repetidas = "SELECT COUNT(id_orden) as retrabajo, MONTH(tiempo_orden) as mes FROM sa_orden 
                                       WHERE id_tipo_orden = 6 AND DATE(tiempo_orden) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
    $reparaciones_repetidas = mysql_query($query_reparaciones_repetidas, $conex) or die("Error de Selección 'Reparaciones Repetidas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>5.- Reparaciones Repetidas (Recurrencia)</td>"; 
    
    $array_datos_reparaciones_repetidas = $cantidad_meses;
    foreach($array_datos_reparaciones_repetidas as $indice => $valor){
        while ($row = mysql_fetch_array($reparaciones_repetidas)) {
            if (array_key_exists($row["mes"], $array_datos_reparaciones_repetidas)) {
                $array_datos_reparaciones_repetidas[$row["mes"]] = $row["retrabajo"];
            }
        }
    }
    
    foreach ($array_datos_reparaciones_repetidas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_reparaciones_repetidas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Quinta Fila  "Porcentaje vehiculos Inspeccionados"////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Vehículos Inspeccionados (60%) 3/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_vehiculos_inspeccionados[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }    
    
    if(array_sum($array_datos_entrada_vehiculos) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_vehiculos_inspeccionados)/array_sum($array_datos_entrada_vehiculos))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    
    //// Sexta Fila "Porcentaje Presupuestos Entregados" ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Presupuestos Entregados (70%) 4/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_presupuestos_entregados[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }    
    
    if(array_sum($array_datos_entrada_vehiculos) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_presupuestos_entregados)/array_sum($array_datos_entrada_vehiculos))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Septima Fila "Porcentaje Reparaciones Repetidas" ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Reparaciones Repetidas (<3%) 5/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_reparaciones_repetidas[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }    
    
    if(array_sum($array_datos_entrada_vehiculos) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_reparaciones_repetidas)/array_sum($array_datos_entrada_vehiculos))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    
    $tabla_estadistico_mensual .= "</table>";
    
    // FIN PROCESO DE RECEPCION 
    
    // RESERVA DE RECAMBIOS
    
    //// Primera fila "Titulos" mes, año, total ////
    $tabla_estadistico_mensual .= "<br><table class='tabla-mensual' id='reserva_recambios' border=1><tr>";
    $tabla_estadistico_mensual .= "<td style='white-space:nowrap;'>Reserva de Recambios</td>";
    
    foreach ($cantidad_meses as $indice => $mes){
        if(array_key_exists($indice,$numero_meses_corto)){
            $tabla_estadistico_mensual .= "<td>".$numero_meses_corto[$indice]."-".$anio." </td>";
        }        
    }
    $tabla_estadistico_mensual .= "<td>Total</td></tr>";
    
    //// Segunda fila "Items Requisitados" ////
    $query_items_requisitados = "SELECT COUNT(id_det_orden_articulo) as articulos, SUM(cantidad) as cantidad, MONTH(tiempo_asignacion) as mes FROM sa_det_orden_articulo 
                                       WHERE DATE(tiempo_asignacion) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                       //aprobado = 1 AND
    $items_requisitados = mysql_query($query_items_requisitados, $conex) or die("Error de Selección 'Items Requisitados': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>6.- Ítems Requisitados</td>"; 
    
    $array_datos_items_requisitados = $cantidad_meses;
    foreach($array_datos_items_requisitados as $indice => $valor){
        while ($row = mysql_fetch_array($items_requisitados)) {
            if (array_key_exists($row["mes"], $array_datos_items_requisitados)) {
                $array_datos_items_requisitados[$row["mes"]] = $row["cantidad"];
            }
        }
    }
    
    foreach ($array_datos_items_requisitados as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_items_requisitados)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Tercera fila "Items Disponibles en Stock" ////
    $query_items_disponibles = "SELECT COUNT(id_det_orden_articulo) as articulos, SUM(cantidad) as cantidad, MONTH(tiempo_asignacion) as mes FROM sa_det_orden_articulo 
                                       WHERE aprobado = 1 AND DATE(tiempo_asignacion) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                       
    $items_disponibles = mysql_query($query_items_disponibles, $conex) or die("Error de Selección 'Items Disponibles': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>7.- Ítems Disponibles en Stock</td>"; 
    
    $array_datos_items_disponibles = $cantidad_meses;
    foreach($array_datos_items_disponibles as $indice => $valor){
        while ($row = mysql_fetch_array($items_disponibles)) {
            if (array_key_exists($row["mes"], $array_datos_items_disponibles)) {
                $array_datos_items_disponibles[$row["mes"]] = $row["cantidad"];
            }
        }
    }
    
    foreach ($array_datos_items_disponibles as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_items_disponibles)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Cuarta fila "Reservas (Vehiculo)"  ////
    $query_reservas = "SELECT COUNT(id_det_orden_articulo) as articulos, SUM(cantidad) as cantidad, MONTH(tiempo_asignacion) as mes                       
                       FROM sa_orden
                       LEFT JOIN sa_det_orden_articulo ON sa_orden.id_orden = sa_det_orden_articulo.id_orden
                       WHERE (id_tipo_orden = 3 OR id_tipo_orden = 4 OR id_tipo_orden = 5)  AND DATE(tiempo_asignacion) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                       //AND aprobado = 1
    $reservas = mysql_query($query_reservas, $conex) or die("Error de Selección 'Reservas (vehiculo)': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>8.- Reservas Vehículos</td>"; 
    
    $array_datos_reservas = $cantidad_meses;
    foreach($array_datos_reservas as $indice => $valor){
        while ($row = mysql_fetch_array($reservas)) {
            if (array_key_exists($row["mes"], $array_datos_reservas)) {
                $array_datos_reservas[$row["mes"]] = $row["cantidad"];
            }
        }
    }
    
    foreach ($array_datos_reservas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_reservas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Quitan fila Porcentaje de "Fill rate taller mecanico"  ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Fill Rate Taller Mecánico (90%) 7/6</b></td>";
    
    foreach($array_datos_items_requisitados as $indice => $valor){
        if($array_datos_items_requisitados[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_items_disponibles[$indice]/$array_datos_items_requisitados[$indice])*100,1)."%</b></td>";
        }
    }    
    
    if(array_sum($array_datos_items_requisitados) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_items_disponibles)/array_sum($array_datos_items_requisitados))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Sexta fila Porcentaje de "Reservas" ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Reservas (70%) 8/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_reservas[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }    
    
    if(array_sum($array_datos_entrada_vehiculos) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_reservas)/array_sum($array_datos_entrada_vehiculos))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    $tabla_estadistico_mensual .= "</table>";
    // FIN RESERVA DE RECAMBIOS
    
    // ENTREGA DEL VEHICULO
    
    //// Primera fila "Titulos" mes, año, total ////
    $tabla_estadistico_mensual .= "<br><table class='tabla-mensual' id='entrega_vehiculo' border=1><tr>";
    $tabla_estadistico_mensual .= "<td style='white-space:nowrap;'>Entrega del Vehículo</td>";
    
    foreach ($cantidad_meses as $indice => $mes){
        if(array_key_exists($indice,$numero_meses_corto)){
            $tabla_estadistico_mensual .= "<td>".$numero_meses_corto[$indice]."-".$anio." </td>";
        }        
    }
    $tabla_estadistico_mensual .= "<td>Total</td></tr>";
    
    //// Segunda fila "OR y vehiculos revisados a tiempo" ////
    $query_vehiculos_revisados = "SELECT MONTH(fecha_entrada) AS mes, SUM(if(DATE(fecha_estimada_entrega) >= DATE(tiempo_entrega), 1,0)) AS antes_tiempo
                                  FROM sa_recepcion
                                  LEFT JOIN sa_orden ON sa_recepcion.id_recepcion = sa_orden.id_recepcion                                 
                                  WHERE fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                  
    $vehiculos_revisados = mysql_query($query_vehiculos_revisados, $conex) or die("Error de Selección 'Vehículos revisados a tiempo': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>9.- OR y Vehículo Revisados a Tiempo</td>"; 
    
    $array_datos_vehiculos_revisados = $cantidad_meses;
    foreach($array_datos_vehiculos_revisados as $indice => $valor){
        while ($row = mysql_fetch_array($vehiculos_revisados)) {
            if (array_key_exists($row["mes"], $array_datos_vehiculos_revisados)) {
                $array_datos_vehiculos_revisados[$row["mes"]] = $row["antes_tiempo"];
            }
        }
    }
    
    foreach ($array_datos_vehiculos_revisados as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_vehiculos_revisados)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Tercera fila "Explicacion del trabajo" //// no exsiste todavia, pero se puede contar si se llamo a la persona o algo
    $query_explicacion = "SELECT MONTH(fecha_entrada) AS mes, SUM(if(DATE(fecha_estimada_entrega) >= DATE(tiempo_entrega), 1,0)) AS antes_tiempo
                                  FROM sa_recepcion
                                  LEFT JOIN sa_orden ON sa_recepcion.id_recepcion = sa_orden.id_recepcion                                 
                                  WHERE fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                  
    $explicacion = mysql_query($query_explicacion, $conex) or die("Error de Selección 'Explicacion del trabajo': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>10.- Explicación del Trabajo</td>"; 
    
    $array_datos_explicacion = $cantidad_meses;
    foreach($array_datos_explicacion as $indice => $valor){
        while ($row = mysql_fetch_array($explicacion)) {
            if (array_key_exists($row["mes"], $array_datos_explicacion)) {
                $array_datos_explicacion[$row["mes"]] = $row["antes_tiempo"];
            }
        }
    }
    
    foreach ($array_datos_explicacion as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_explicacion)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Cuarta fila porcentaje de "Cumplimiento hora de entrega"  ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Cumplimiento Hora de Entrega (90%) 9/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_vehiculos_revisados[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }
    
    if(array_sum($array_datos_entrada_vehiculos) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_vehiculos_revisados)/array_sum($array_datos_entrada_vehiculos))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Quinta fila porcentaje de "Explicacion del trabajo"  ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Explicación del Trabajo (90%) 10/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_explicacion[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }
    
    if(array_sum($array_datos_entrada_vehiculos) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_explicacion)/array_sum($array_datos_entrada_vehiculos))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";    
    
    $tabla_estadistico_mensual .= "</table>";
    // FIN ENTREGA DEL VEHICULO
    
    // SEGUIMIENTO
    
     //// Primera fila "Titulos" mes, año, total ////
    $tabla_estadistico_mensual .= "<br><table class='tabla-mensual' id='seguimiento' border=1><tr>";
    $tabla_estadistico_mensual .= "<td style='white-space:nowrap;'>Seguimiento</td>";
    
    foreach ($cantidad_meses as $indice => $mes){
        if(array_key_exists($indice,$numero_meses_corto)){
            $tabla_estadistico_mensual .= "<td>".$numero_meses_corto[$indice]."-".$anio." </td>";
        }        
    }
    $tabla_estadistico_mensual .= "<td>Total</td></tr>";
    
    //// Segunda fila "Clientes a Contactar" ////
     $query_clientes_contactar = "SELECT MONTH(fecha_entrada) AS mes, COUNT(sa_cita.id_cita) as contactar
                                  FROM sa_recepcion
                                  LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                                 
                                  WHERE n_respuesta IS NULL AND fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                  
    $clientes_contactar = mysql_query($query_clientes_contactar, $conex) or die("Error de Selección 'Clientes a Contactar': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>11.- Clientes a Contactar</td>"; 
    
    $array_datos_clientes_contactar = $cantidad_meses;
    foreach($array_datos_clientes_contactar as $indice => $valor){
        while ($row = mysql_fetch_array($clientes_contactar)) {
            if (array_key_exists($row["mes"], $array_datos_clientes_contactar)) {
                $array_datos_clientes_contactar[$row["mes"]] = $row["contactar"];
            }
        }
    }
    
    foreach ($array_datos_clientes_contactar as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_clientes_contactar)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Tercera fila "Clientes Contactados" ////
     $query_clientes_contactados = "SELECT MONTH(fecha_entrada) AS mes, COUNT(sa_cita.id_cita) as contactados
                                  FROM sa_recepcion
                                  LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                                 
                                  WHERE n_respuesta IS NOT NULL AND fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                  
    $clientes_contactados = mysql_query($query_clientes_contactados, $conex) or die("Error de Selección 'Clientes Contactados': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>12.- Clientes Contactados</td>"; 
    
    $array_datos_clientes_contactados = $cantidad_meses;
    foreach($array_datos_clientes_contactados as $indice => $valor){
        while ($row = mysql_fetch_array($clientes_contactados)) {
            if (array_key_exists($row["mes"], $array_datos_clientes_contactados)) {
                $array_datos_clientes_contactados[$row["mes"]] = $row["contactados"];
            }
        }
    }
    
    foreach ($array_datos_clientes_contactados as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_clientes_contactados)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Cuarta fila "Clientes Completamente Satisfechos" ////
     $query_clientes_satisfechos = "SELECT MONTH(fecha_entrada) AS mes, COUNT(sa_cita.id_cita) as contactados
                                  FROM sa_recepcion
                                  LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                                 
                                  WHERE (n_respuesta = 1 OR n_respuesta = 2 OR n_respuesta = 3) AND fecha_entrada BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                  
    $clientes_satisfechos = mysql_query($query_clientes_satisfechos, $conex) or die("Error de Selección 'Clientes Satisfechos': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>13.- Clientes Completamente Satisfechos</td>"; 
    
    $array_datos_clientes_satisfechos = $cantidad_meses;
    foreach($array_datos_clientes_satisfechos as $indice => $valor){
        while ($row = mysql_fetch_array($clientes_satisfechos)) {
            if (array_key_exists($row["mes"], $array_datos_clientes_satisfechos)) {
                $array_datos_clientes_satisfechos[$row["mes"]] = $row["contactados"];
            }
        }
    }
    
    foreach ($array_datos_clientes_satisfechos as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_clientes_satisfechos)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Quinta fila porcentaje de "Clientes Contactados"  ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Clientes Contactados (80%) 12/11</b></td>";
    
    foreach($array_datos_clientes_contactar as $indice => $valor){
        if($array_datos_clientes_contactar[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_clientes_contactados[$indice]/$array_datos_clientes_contactar[$indice])*100,1)."%</b></td>";
        }
    }
    
    if(array_sum($array_datos_clientes_contactar) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_clientes_contactados)/array_sum($array_datos_clientes_contactar))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Quinta fila porcentaje de "Clientes Satisfechos"  ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Clientes Satisfechos (90%) 13/12</b></td>";
    
    foreach($array_datos_clientes_contactar as $indice => $valor){
        if($array_datos_clientes_contactar[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_clientes_satisfechos[$indice]/$array_datos_clientes_contactar[$indice])*100,1)."%</b></td>";
        }
    }
    
    if(array_sum($array_datos_clientes_contactar) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_clientes_satisfechos)/array_sum($array_datos_clientes_contactar))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    
    $tabla_estadistico_mensual .= "</table>";
    // FIN SEGUIMIENTO
    
    // CONTROL DE TALLER
    
     //// Primera fila "Titulos" mes, año, total ////
    $tabla_estadistico_mensual .= "<br><table class='tabla-mensual' id='control_taller' border=1><tr>";
    $tabla_estadistico_mensual .= "<td style='white-space:nowrap;'>Control de Taller</td>";
    
    foreach ($cantidad_meses as $indice => $mes){
        if(array_key_exists($indice,$numero_meses_corto)){
            $tabla_estadistico_mensual .= "<td>".$numero_meses_corto[$indice]."-".$anio." </td>";
        }        
    }
    $tabla_estadistico_mensual .= "<td>Total</td></tr>";
    
    //// Segunda fila "Horas Presencia" ////
    $query_horas_presencia = "SELECT COUNT(DISTINCT DATE(fecha_creada)) as registro_dia, MONTH(fecha_creada) as mes 
                              FROM sa_presencia_mecanicos 
                              WHERE DATE(fecha_creada) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                       
    $horas_presencia = mysql_query($query_horas_presencia, $conex) or die("Error de Selección 'Horas Presencia': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>14.- Horas Presencia</td>"; 
    
    $array_datos_horas_presencia = $cantidad_meses;
    foreach($array_datos_horas_presencia as $indice => $valor){
        while ($row = mysql_fetch_array($horas_presencia)) {
            if (array_key_exists($row["mes"], $array_datos_horas_presencia)) {
                $array_datos_horas_presencia[$row["mes"]] = $row["registro_dia"]*8;
            }
        }
    }
    
    foreach ($array_datos_horas_presencia as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_horas_presencia)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// tercera fila "Horas Trabajadas" ////
    $query_horas_trabajadas = "SELECT *, SUM(minutos_presencia) as minutos_presencia, MONTH(fecha_creada) as mes 
                              FROM sa_presencia_mecanicos 
                              WHERE DATE(fecha_creada) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                       
    $horas_trabajadas = mysql_query($query_horas_trabajadas, $conex) or die("Error de Selección 'Horas Trabajadas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>15.- Horas Trabajadas</td>"; 
    
    $array_datos_horas_trabajadas = $cantidad_meses;
    foreach($array_datos_horas_trabajadas as $indice => $valor){
        while ($row = mysql_fetch_array($horas_trabajadas)) {
            if (array_key_exists($row["mes"], $array_datos_horas_trabajadas)) {
                $array_datos_horas_trabajadas[$row["mes"]] = $row["minutos_presencia"]*8;
            }
        }
    }
    
    foreach ($array_datos_horas_trabajadas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_horas_trabajadas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Cuarta fila "Horas Facturadas en OT Cerradas (int., Ext., Gar.) " ////
    $query_horas_facturadas_todas = "SELECT SUM(ut) as total_horas_facturadas, MONTH(tiempo_orden) as mes
                                     FROM sa_orden
                                     LEFT JOIN sa_det_orden_tempario ON sa_orden.id_orden = sa_det_orden_tempario.id_orden
                                     WHERE (id_estado_orden = 18 OR id_estado_orden = 24) AND DATE(tiempo_orden) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                       
    $horas_facturadas_todas = mysql_query($query_horas_facturadas_todas, $conex) or die("Error de Selección 'Horas Facturadas todas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>16.- Horas Facturadas OT Cerradas (int., Ext., Gar.)</td>"; 
    
    $array_datos_horas_facturadas_todas = $cantidad_meses;
    foreach($array_datos_horas_facturadas_todas as $indice => $valor){
        while ($row = mysql_fetch_array($horas_facturadas_todas)) {
            if (array_key_exists($row["mes"], $array_datos_horas_facturadas_todas)) {
                $array_datos_horas_facturadas_todas[$row["mes"]] = round($row["total_horas_facturadas"]/100);
            }
        }
    }
    
    foreach ($array_datos_horas_facturadas_todas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_horas_facturadas_todas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    
    //// Quinta fila "Horas Facturadas en OT Cerradas (Internas) " ////
    $query_horas_facturadas_internas = "SELECT SUM(ut) as total_horas_facturadas_internas, MONTH(tiempo_asignacion) as mes
                                     FROM sa_orden
                                     LEFT JOIN sa_det_orden_tempario ON sa_orden.id_orden = sa_det_orden_tempario.id_orden
                                     WHERE (id_tipo_orden = 3 OR id_tipo_orden = 4 OR id_tipo_orden = 6) AND id_estado_orden = 24 AND DATE(tiempo_asignacion) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                       
    $horas_facturadas_internas = mysql_query($query_horas_facturadas_internas, $conex) or die("Error de Selección 'Horas Facturadas Internas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>17.- Horas Facturadas OT (Internas)</td>"; 
    
    $array_datos_horas_facturadas_internas = $cantidad_meses;
    foreach($array_datos_horas_facturadas_internas as $indice => $valor){
        while ($row = mysql_fetch_array($horas_facturadas_internas)) {
            if (array_key_exists($row["mes"], $array_datos_horas_facturadas_internas)) {
                $array_datos_horas_facturadas_internas[$row["mes"]] = round($row["total_horas_facturadas_internas"]/100);
            }
        }
    }
    
    foreach ($array_datos_horas_facturadas_internas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_horas_facturadas_internas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Sexta fila "Facturacion de Repuesto al taller OR cerradas bsX1000" ////
    $query_facturacion_repuesto = "SELECT ((precio_unitario * cantidad)*((
																		(SELECT SUM(sa_det_orden_articulo_iva.iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)																		
																		/100)+1)) AS total,
                                        SUM(((precio_unitario * cantidad)*((
																		(SELECT SUM(sa_det_orden_articulo_iva.iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
																		/100)+1))) as suma,
                                        MONTH(tiempo_asignacion) as mes                     
                                        FROM sa_orden
                                        LEFT JOIN sa_det_orden_articulo ON sa_orden.id_orden = sa_det_orden_articulo.id_orden
                                        WHERE (id_estado_orden = 18 OR id_estado_orden = 24) AND aprobado = 1 AND DATE(tiempo_asignacion) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";
                                       
    $horas_facturacion_repuesto = mysql_query($query_facturacion_repuesto, $conex) or die("Error de Selección 'Facturación de repuestos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>18.- Facturación de Repuesto al Taller OR Cerradas bsX1000</td>"; 
    
    $array_datos_facturacion_repuesto = $cantidad_meses;
    foreach($array_datos_facturacion_repuesto as $indice => $valor){
        while ($row = mysql_fetch_array($horas_facturacion_repuesto)) {
            if (array_key_exists($row["mes"], $array_datos_facturacion_repuesto)) {
                $array_datos_facturacion_repuesto[$row["mes"]] = round($row["suma"]);
            }
        }
    }
    
    foreach ($array_datos_facturacion_repuesto as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_facturacion_repuesto)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Septima fila "Numero de vehiculos con O/R Cerradas (Ext., Gar.)" ////
    $query_vehiculos_cerradas = "SELECT COUNT(DISTINCT sa_recepcion.id_recepcion) AS cantidad_vehiculos, MONTH(fecha_entrada) AS mes
                                FROM sa_orden 
                                LEFT JOIN sa_recepcion ON sa_orden.id_recepcion = sa_recepcion.id_recepcion
                                WHERE (id_estado_orden = 18 OR id_estado_orden = 24) AND id_tipo_orden != 4 
                                AND DATE(fecha_entrada) BETWEEN '$fecha_desde' AND '$fecha_hasta' GROUP BY mes";    
    
    $vehiculos_cerradas = mysql_query($query_vehiculos_cerradas, $conex) or die("Error de Selección 'Vehículos con ordenes cerradas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>19.- Número de vehículos con O/R Cerradas (Ext., Gar.)</td>"; 
    
    $array_datos_vehiculos_cerradas = $cantidad_meses;
    foreach($array_datos_vehiculos_cerradas as $indice => $valor){
        while ($row = mysql_fetch_array($vehiculos_cerradas)) {
            if (array_key_exists($row["mes"], $array_datos_vehiculos_cerradas)) {
                $array_datos_vehiculos_cerradas[$row["mes"]] = $row["cantidad_vehiculos"];
            }
        }
    }
    
    foreach ($array_datos_vehiculos_cerradas as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_vehiculos_cerradas)."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    
    //// Octava fila porcentaje "ocupacion" ////    
     $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Ocupación (85%) 15/14</b></td>";
    
    foreach($array_datos_horas_presencia as $indice => $valor){
        if($array_datos_horas_presencia[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_horas_trabajadas[$indice]/$array_datos_horas_presencia[$indice])*100,1)."%</b></td>";
        }
    }
    
    if(array_sum($array_datos_horas_presencia) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_horas_trabajadas)/array_sum($array_datos_horas_presencia))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// novena fila porcentaje "Eficiencia" ////    
     $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Eficiencia (110%) 16/15</b></td>";
    
    foreach($array_datos_horas_trabajadas as $indice => $valor){
        if($array_datos_horas_trabajadas[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_horas_facturadas_todas[$indice]/$array_datos_horas_trabajadas[$indice])*100,1)."%</b></td>";
        }
    }
    
    if(array_sum($array_datos_horas_trabajadas) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_horas_facturadas_todas)/array_sum($array_datos_horas_trabajadas))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// decima fila porcentaje "Productividad" ////    
     $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b>% Productividad (100%) 16/14</b></td>";
    
    foreach($array_datos_horas_presencia as $indice => $valor){
        if($array_datos_horas_presencia[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_horas_facturadas_todas[$indice]/$array_datos_horas_presencia[$indice])*100,1)."%</b></td>";
        }
    }
    
    if(array_sum($array_datos_horas_presencia) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0%</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_horas_facturadas_todas)/array_sum($array_datos_horas_presencia))*100,1)."%</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// onceava fila porcentaje "Horas Facturadas / vehiculos " ////    
     $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b> Horas Facturadas / Vehículo (>2,5 Hr) (16-17)/19</b></td>";
    
    foreach($array_datos_vehiculos_cerradas as $indice => $valor){
        if($array_datos_vehiculos_cerradas[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_horas_facturadas_todas[$indice]-$array_datos_horas_facturadas_internas[$indice])/$array_datos_vehiculos_cerradas[$indice],1)."</b></td>";
        }
    }
    
    if(array_sum($array_datos_vehiculos_cerradas) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_horas_facturadas_todas)-array_sum($array_datos_horas_facturadas_internas))/array_sum($array_datos_vehiculos_cerradas),1)."</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// doceava fila porcentaje "Repuestos facturados / vehiculos dolar" ////    
     $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b> Repuestos Facturados / Vehículo (90$) 18/19</b></td>";
    
     //// calculo valor del dolar ////
     $array_datos_dolar = $cantidad_meses;     
     foreach($array_datos_dolar as $indice => $valor){
         if (date("Y",strtotime($fecha_desde))<=2012){
             $array_datos_dolar[$indice] = "4.30";
         }
         if (date("Y",strtotime($fecha_desde))==2013){
             if($indice == 1){
                $array_datos_dolar[$indice] = "4.30";
             }else{
                $array_datos_dolar[$indice] = "6.30";
             }
         }
         if (date("Y",strtotime($fecha_desde))>2013){
             $array_datos_dolar[$indice] = "6.30";
         }
         
     }
     $ultimo_dolar = end($array_datos_dolar);
     
     
    foreach($array_datos_vehiculos_cerradas as $indice => $valor){
        if($array_datos_vehiculos_cerradas[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_facturacion_repuesto[$indice]/$array_datos_vehiculos_cerradas[$indice])/$array_datos_dolar[$indice],1)."</b></td>";
        }
    }
    
    if(array_sum($array_datos_vehiculos_cerradas) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_facturacion_repuesto)/array_sum($array_datos_vehiculos_cerradas))/$ultimo_dolar,1)."</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Fila valor del dolar  ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b> Valor del Dolar</b></td>";
    foreach ($array_datos_dolar as $indice => $valor){
        $tabla_estadistico_mensual .= "<td><b>$valor</b></td>";
    }
    $tabla_estadistico_mensual .= "<td><b>$ultimo_dolar</b></td></tr>";
    
    $tabla_estadistico_mensual .= "</table>";
    // FIN CONTROL TALLER    
    
    
    // CAPACIDAD DE SERVICIO
    
    //// Primera fila "Titulos" mes, año, total ////
    $tabla_estadistico_mensual .= "<br><table class='tabla-mensual' id='capacidad_servicio' border=1><tr>";
    $tabla_estadistico_mensual .= "<td style='white-space:nowrap;'>Datos sobre Capacidad de Servicio</td>";
    
    foreach ($cantidad_meses as $indice => $mes){
        if(array_key_exists($indice,$numero_meses_corto)){
            $tabla_estadistico_mensual .= "<td>".$numero_meses_corto[$indice]."-".$anio." </td>";
        }        
    }
    $tabla_estadistico_mensual .= "<td>Total</td></tr>";
    
    //// Segunda fila "Numero de tecnicos" ////
    $query_tecnicos = "SELECT COUNT(id_empleado) as cantidad_mecanicos
                        FROM pg_empleado
                        LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        LEFT JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
                        WHERE (pg_cargo.id_cargo =13 OR pg_cargo.id_cargo =14 OR pg_cargo.id_cargo =15) AND activo = 1";
                                       
    $tecnicos = mysql_query($query_tecnicos, $conex) or die("Error de Selección 'Número de Técnicos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    while ($row = mysql_fetch_array($tecnicos)) {
            $cantidad_tecnicos = $row["cantidad_mecanicos"];            
    }
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>20.- Número de Técnicos</td>"; 
    
    $array_datos_tecnicos = $cantidad_meses;
    foreach($array_datos_tecnicos as $indice => $valor){
        $array_datos_tecnicos[$indice] = $cantidad_tecnicos;
    }
    
    foreach ($array_datos_tecnicos as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".$cantidad_tecnicos."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Tercera fila "Número de Técnicos en rapid service" ////
    $query_tenicos_rapidservice = "SELECT COUNT(id_empleado) as cantidad_mecanicos_rapidservice
                        FROM pg_empleado
                        LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        LEFT JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
                        WHERE (pg_cargo.id_cargo =13 OR pg_cargo.id_cargo =14 OR pg_cargo.id_cargo =15) AND activo = 1";    
    
    $tecnicos_rapidservice = mysql_query($query_tenicos_rapidservice, $conex) or die("Error de Selección 'Númro de técnicos en rapid service': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    while ($row = mysql_fetch_array($tecnicos_rapidservice)) {
            $cantidad_tecnicos_rapidservice = $row["cantidad_mecanicos_rapidservice"];            
    }
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>21.- Número de Técnicos en Rapid Service</td>"; 
    
    $array_datos_tecnicos_rapidservice = $cantidad_meses;
    foreach($array_datos_tecnicos_rapidservice as $indice => $valor){
        $array_datos_tecnicos_rapidservice[$indice] = $cantidad_tecnicos_rapidservice;
    }
    
    foreach ($array_datos_tecnicos_rapidservice as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".$cantidad_tecnicos_rapidservice."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Cuarta fila "Número de Ayudantes Generales (Excluir Lavador)" ////
    $query_ayudantes = "SELECT COUNT(id_empleado) as cantidad_ayudantes
                        FROM pg_empleado
                        LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        LEFT JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
                        WHERE pg_cargo.id_cargo =16 AND activo = 1";    
    
    $ayudantes = mysql_query($query_ayudantes, $conex) or die("Error de Selección 'Número de Ayudantes': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    while ($row = mysql_fetch_array($ayudantes)) {
            $cantidad_ayudantes = $row["cantidad_ayudantes"];            
    }
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>22.- Número de Ayudantes Generales (Excluir Lavador)</td>"; 
    
    $array_datos_ayudantes = $cantidad_meses;
    foreach($array_datos_ayudantes as $indice => $valor){
        $array_datos_ayudantes[$indice] = $cantidad_ayudantes;
    }
    
    foreach ($array_datos_ayudantes as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".$cantidad_ayudantes."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Quinta fila "Número de Puestos de Trabajo" ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>23.- Número de Puestos de Trabajo</td>"; 
    
    $array_datos_puestos_trabajo = $cantidad_meses;
    
    foreach($array_datos_puestos_trabajo as $valor){
        $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_puestos_trabajo)."</td>";
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Sexta fila "Número de Puestos de Espera" ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>24.- Número de Puestos de Espera</td>"; 
    
    $array_datos_puestos_espera = $cantidad_meses;
    
    foreach($array_datos_puestos_espera as $valor){
        $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_puestos_espera)."</td>";
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Septima fila "Dias habiles en el mes" ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>25.- Número de Días Hábiles en el Mes</td>";
    
    $array_datos_dias_habiles = $cantidad_meses;
    
    foreach($array_datos_dias_habiles as $indice => $valor){
        $array_datos_dias_habiles[$indice] = filtrarDiasHabiles($indice,$anio);
    }
    
    foreach($array_datos_dias_habiles as $valor){
        $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    
    $tabla_estadistico_mensual .= "<td>".array_sum($array_datos_dias_habiles)."</td>";
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Octava fila "Número de Asesores" ////
    $query_asesores = "SELECT COUNT(id_empleado) as cantidad_asesores
                        FROM pg_empleado
                        LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        LEFT JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
                        WHERE pg_cargo.id_cargo =6 AND activo = 1";    
    
    $asesores = mysql_query($query_asesores, $conex) or die("Error de Selección 'Número de Asesores': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    while ($row = mysql_fetch_array($asesores)) {
            $cantidad_asesores = $row["cantidad_asesores"];            
    }
    
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'>26.- Número de Asesores</td>"; 
    
    $array_datos_asesores = $cantidad_meses;
    foreach($array_datos_asesores as $indice => $valor){
        $array_datos_asesores[$indice] = $cantidad_asesores;
    }
    
    foreach ($array_datos_asesores as $valor){
       $tabla_estadistico_mensual .= "<td>$valor</td>";
    }
    $tabla_estadistico_mensual .= "<td>".$cantidad_asesores."</td>";    
    $tabla_estadistico_mensual .= "</tr>";
    
    //// Novena fila "Total de Entrada Diaria (Incluye Rapid Service)"  ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b> Total de Entrada Diaria (Incluye R.Service) (100) (1+1.1)/25</b></td>";
    
    foreach($array_datos_dias_habiles as $indice => $valor){
        if($array_datos_dias_habiles[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round(($array_datos_entrada_vehiculos[$indice]+$array_datos_rapid_service[$indice])/$array_datos_dias_habiles[$indice],1)."</b></td>";
        }
    }
    
    if(array_sum($array_datos_dias_habiles) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round((array_sum($array_datos_entrada_vehiculos)+array_sum($array_datos_rapid_service))/array_sum($array_datos_dias_habiles),1)."</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";   
    
    //// Decima fila "Dias de Citas (Promedio)" ////
    $tabla_estadistico_mensual .= "<tr><td style='white-space:nowrap;'><b> Días de Citas (Promedio) (3) 1.2/2</b></td>";
    
    foreach($array_datos_numero_citas as $indice => $valor){
        if($array_datos_numero_citas[$indice] == "0"){
            $tabla_estadistico_mensual .= "<td><b>0</b></td>";
        }else{
            $tabla_estadistico_mensual .= "<td><b>".round($array_datos_solicitud_citas[$indice]/$array_datos_numero_citas[$indice],1)."</b></td>";
        }
    }
    
    if(array_sum($array_datos_numero_citas) == "0"){
        $tabla_estadistico_mensual .= "<td><b>0</b></td>";
    }else{
        $tabla_estadistico_mensual .= "<td><b>".round(array_sum($array_datos_solicitud_citas)/array_sum($array_datos_numero_citas),1)."</b></td>";
    }
    $tabla_estadistico_mensual .= "</tr>";   
    
    
    
    
    $tabla_estadistico_mensual .= "</table><br><br>";
    
    for($i=1; $i<=18; $i++){
        $tabla_estadistico_mensual.= "<div id='container$i' class='container_todos' style=' width: 95%;  display:none;'></div>";
    }
    
    $respuesta->clear("contenedor_estadistico", "innerHTML");    
    $respuesta->assign("contenedor_estadistico","innerHTML",$tabla_estadistico_mensual);
    return $respuesta;
}











/* SISTEMA DE MEDIDAS SEMANAL */


function verSemanal($fecha_desde, $fecha_hasta){
    
    $anio = date("Y", strtotime($fecha_desde));

    global $conex;
    $respuesta = new xajaxResponse();

    $tabla_estadistico_semanal = "";

    $numero_semana = date('W', strtotime($fecha_desde));
    $numero_semana2 = date('W', strtotime($fecha_hasta));


    $total_semanas = $numero_semana2 - $numero_semana;

    
    $fechas_semanal = array();

    if ($total_semanas != 0) {
        for ($i = $numero_semana; $i <= $numero_semana2; $i++) {

            $a=str_pad($i, 2, "0", STR_PAD_LEFT);//debe tener cero a la izq si es una sola cifra
            $lunes = date('d-m-Y', strtotime($anio.'W'.$a));
            $viernes = date('d-m-Y', strtotime('+4 days', strtotime($lunes)));
            //var_dump("L: $lunes V:$viernes");
            //var_dump($a);
            $segunda_fecha = "";

            if ((date('m', strtotime($lunes))) != (date('m', strtotime($viernes)))) {

                $segunda_fecha = date("01-m-Y", strtotime($viernes));
                
                if (date("w", strtotime($segunda_fecha)) == 0) {//es domingo
                    $segunda_fecha = date("d-m-Y", strtotime("+1 days", strtotime($segunda_fecha)));
                } elseif (date("w", strtotime($segunda_fecha)) == 6) {//es sabado
                    $segunda_fecha = date("d-m-Y", strtotime("+2 days", strtotime($segunda_fecha)));
                }
                $segunda_fecha = $segunda_fecha."x".$viernes;
                //cambio viernes
                $viernes = date('t-m-Y', strtotime($lunes));
            }
            
                //valida si el lunes 1 es del año pasado
               if(date("Y", strtotime($lunes)) != date("Y",strtotime($fecha_desde))){                
                }else{     
                array_push($fechas_semanal, "{$lunes}x{$viernes}");
                }
            
            if ($segunda_fecha !== "") {
                
               array_push($fechas_semanal, $segunda_fecha);                 
            }
        }
    } elseif ($total_semanas == 0) {
        return $respuesta->script("alert('Verifica el Nº de Semana Seleccionado');");
       // array_push($fechas_semanal, "{$fecha_desde}x{$fecha_hasta}");
    } elseif ($total_semanas <= -1){
        return $respuesta->script("alert('Verifica el Nº de Semana seleccionado');");
    }
    
    $datos_semanal = array_fill_keys(array_keys($fechas_semanal), "0");
    //var_dump($fechas_semanal);
    
    //meses
    $meses_repetidos = array();
    foreach ($fechas_semanal as $fecha){
        $separar = explode("x", $fecha);
        $fecha1 = date("m",strtotime($separar[0]));
        
        array_push($meses_repetidos,$fecha1);
    }
    $meses_repetidos = elementosrepetidos($meses_repetidos,true);
    
    
    
    //todos los titulo
    $total = array();
    $titulo = "<td style='white-space:nowrap;' rowspan='1'>Mes</td>";    
    foreach($meses_repetidos as $indice => $valor){
        $titulo .= "<td class='xd' colspan=".($valor['count']+1).">".mostrarmes($valor['value'])." - ".$anio."</td>";
        //$titulo .= "<td>Total</td>";
        array_push($total, $valor['count']);
    }    
    $titulo .= "</tr>";
    
    // CITA PREVIA    
    
    //// Primera fila "Titulos"  ////
    $tabla_estadistico_semanal .= "<table class='tabla-semanal' id='cita_previa2' border=1><tr>";
    
    
    $tabla_estadistico_semanal .= $titulo;
    
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>Cita Previa</td>";
    
    // fecha semanas
    $array_titulo_fechas = array();
    $titulo_fechas = "";
    $sumador = 0;
    $aux=0;
    foreach ($fechas_semanal as $indice => $fecha){
        $separar = explode("x", $fecha);
        $fecha1 = $separar[0];
        $fecha2 = $separar[1];          
        
        $titulo_fechas .= "<td style='white-space:nowrap;'>".traducefecha($fecha1,true)."-".traducefecha($fecha2,true)."</td>";
       array_push($array_titulo_fechas,$fecha1."x".$fecha2);
        
        $comprobar = $total[$aux]+$sumador;        
        if($comprobar == $indice+1){
          $titulo_fechas .= "<td style='background-color:#CCCCCC'>Total</td>";
          $aux++;
          $sumador = $comprobar;
          array_push($array_titulo_fechas,"Total");
        }       
        
    }    
    
    $tabla_estadistico_semanal .= $titulo_fechas;
    $tabla_estadistico_semanal .= "</tr>";
    
    //entrada de vehiculos
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>1.- Entrada de Vehículos</td>";    
  
    $datos_entrada_vehiculos = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
            $query_entrada_de_vehiculos = "SELECT COUNT(id_recepcion) as entrada_vehiculo, MONTH(fecha_entrada) as mes 
                                   FROM sa_recepcion 
                                   WHERE fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";

            $entrada_de_vehiculos = mysql_query($query_entrada_de_vehiculos, $conex) or die("Error de Selección 'Entrada de vehiculos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
            
            if (mysql_num_rows($entrada_de_vehiculos) != 0) {
                while ($row = mysql_fetch_array($entrada_de_vehiculos)) {                    
                       array_push($datos_entrada_vehiculos, $row["entrada_vehiculo"]);
                    
                }
            } else {
                //$tabla_estadistico_semanal .= "<td>0</td>";
                array_push($datos_entrada_vehiculos, "0");
            }
        } else {
            array_push($datos_entrada_vehiculos, "Total");
        }
    }
    
    $array_datos_entrada_vehiculos = array();
    $hay_total = 0;
    foreach ($datos_entrada_vehiculos as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_entrada_vehiculos,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            
            array_push($array_datos_entrada_vehiculos,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_entrada_vehiculos,$datos);
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>";
    
     //// Tercera fila "Entrada por Rapid Service"////  
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>1.1- Entrada por Rapid Service</td>"; 
    $datos_rapid_service = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
            $query_entrada_rapid_service = "SELECT COUNT(id_recepcion) as rapid_service, MONTH(fecha_entrada) as mes 
                                            FROM sa_recepcion 
                                            WHERE serviexp = 0 AND fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
            $entrada_rapid_service = mysql_query($query_entrada_rapid_service, $conex) or die("Error de Selección 'Entrada por Rapid Service': " . mysql_error() . "<br>Error Nº: " . mysql_errno());

            if (mysql_num_rows($entrada_rapid_service) != 0) {
                while ($row = mysql_fetch_array($entrada_rapid_service)) {                    
                       array_push($datos_rapid_service, $row["rapid_service"]);
                    
                }
            } else {
                array_push($datos_rapid_service, "0");
            }
        } else {
            array_push($datos_rapid_service, "Total");
        }
    }
    
    $array_datos_rapid_service = array();
    $hay_total = 0;
    foreach ($datos_rapid_service as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_rapid_service,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            
            array_push($array_datos_rapid_service, array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_rapid_service, $datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>";
    
     //// Cuarta fila "Total dias solicitud y citas" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>1.2- Total de días entre solicitud y las citas (Todos los clientes)</td>";  
    $datos_solicitud_citas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
           $query_dias_solicitud_citas = "SELECT ABS(SUM(DATEDIFF(sa_cita.fecha_solicitud,sa_recepcion.fecha_entrada))) as dias, MONTH(fecha_entrada) as mes 
                                   FROM sa_recepcion 
                                   LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita 
                                   WHERE sa_recepcion.fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
           $dias_solicitud_citas = mysql_query($query_dias_solicitud_citas, $conex) or die("Error de Selección 'Total dias solicitud y citas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());

            if (mysql_num_rows($dias_solicitud_citas) != 0) {
                while ($row = mysql_fetch_array($dias_solicitud_citas)) {                
                       array_push($datos_solicitud_citas, $row["dias"]);
                    
                }
            } else {
                array_push($datos_solicitud_citas, "0");
            }
        } else {
            array_push($datos_solicitud_citas, "Total");
        }
    }
    
    $array_datos_solicitud_citas = array();
    $hay_total = 0;
    foreach ($datos_solicitud_citas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_solicitud_citas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            
            array_push($array_datos_solicitud_citas, array_sum($array_dividido));
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_solicitud_citas, $datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Quinta fila "Numero de citas" /////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>2- Número de Citas</td>"; 
    $datos_numero_citas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
           $query_numero_citas = "SELECT COUNT(DISTINCT sa_recepcion.id_cita) AS citas, MONTH(fecha_entrada) AS mes
                           FROM sa_recepcion
                           WHERE fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
    $numero_citas =  mysql_query($query_numero_citas, $conex) or die("Error de Selección 'Número de citas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());

            if (mysql_num_rows($numero_citas) != 0) {
                while ($row = mysql_fetch_array($numero_citas)) {                 
                       array_push($datos_numero_citas, $row["citas"]);                    
                }
            } else {
                array_push($datos_numero_citas, "0");
            }
        } else {
            array_push($datos_numero_citas, "Total");
        }
    }
    
    $array_datos_numero_citas = array();
    $hay_total = 0;
    
    
    foreach ($datos_numero_citas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_numero_citas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            
            array_push($array_datos_numero_citas,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_numero_citas,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>";   
    
    
    //// Sexta fila "Citas Realizadas" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>2.1- Citas Realizadas</td>"; 
    
    $datos_citas_realizadas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
           $query_citas_realizadas = "SELECT COUNT(sa_recepcion.id_cita) AS citas, MONTH(fecha_entrada) AS mes
                               FROM sa_recepcion
                               LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita
                               WHERE origen_cita = 'PROGRAMADA' AND fecha_entrada BETWEEN '$fecha1' AND '$fecha2'
                               GROUP BY mes";//agregado limit 1 para que sea igual al mensual
    $citas_realizadas =  mysql_query($query_citas_realizadas, $conex) or die("Error de Selección 'Citas Realizadas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($citas_realizadas) != 0) {
                while ($row = mysql_fetch_array($citas_realizadas)) {                 
                       array_push($datos_citas_realizadas, $row["citas"]);                    
                }
            } else {
                array_push($datos_citas_realizadas, "0");
            }
        } else {
            array_push($datos_citas_realizadas, "Total");
        }
    }
    
    $array_datos_citas_realizadas = array();
    $hay_total = 0;
    foreach ($datos_citas_realizadas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_citas_realizadas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_citas_realizadas,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_citas_realizadas,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>";   
    
    
    //// Septima fila "Porcentaje de Citas"  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Citas (60%) 2/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_numero_citas[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }
        
    $tabla_estadistico_semanal .= "</tr>";
    
    
    //// Octava fila "Porcentaje de Citas Realizadas" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Citas Realizadas (70%) 2.1/2</b></td>";
    
    foreach($array_datos_numero_citas as $indice => $valor){
        if($array_datos_numero_citas[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_citas_realizadas[$indice]/$array_datos_numero_citas[$indice])*100,1)."%</b></td>";
        }
    }    
    
    
    $tabla_estadistico_semanal .= "</table>";
    // FIN CITA PREVIA
    
     // PROCESO DE RECEPCION
     
    //// Primera fila "Titulos"  ////
    $tabla_estadistico_semanal .= "<br><table class='tabla-semanal' id='proceso_recepcion2' border=1><tr>"; 
    
    $tabla_estadistico_semanal .= $titulo;
    $tabla_estadistico_semanal .= "<td style='white-space:nowrap;'>Proceso de Recepción</td>";
    $tabla_estadistico_semanal .= $titulo_fechas;
    $tabla_estadistico_semanal .= "</tr>";
    
    
    //// Segunda fila "Vehiculos inspeccionados" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>3.- Vehículos Inspeccionados</td>"; 
    
    $datos_vehiculos_inspeccionados = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
          $query_vehiculos_inspeccionados = "SELECT COUNT(id_recepcion) as puente, MONTH(fecha_entrada) as mes FROM sa_recepcion 
                                       WHERE puente = 0 AND fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
       
        $vehiculos_inspeccionados = mysql_query($query_vehiculos_inspeccionados, $conex) or die("Error de Selección 'Vehículos Inspeccionados': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($vehiculos_inspeccionados) != 0) {
                while ($row = mysql_fetch_array($vehiculos_inspeccionados)) {                 
                       array_push($datos_vehiculos_inspeccionados, $row["puente"]);                    
                }
            } else {
                array_push($datos_vehiculos_inspeccionados, "0");
            }
        } else {
            array_push($datos_vehiculos_inspeccionados, "Total");
        }
    }
    
    $array_datos_vehiculos_inspeccionados = array();
    $hay_total = 0;
    foreach ($datos_vehiculos_inspeccionados as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_vehiculos_inspeccionados,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_vehiculos_inspeccionados,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_vehiculos_inspeccionados,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>";   
    
    
    
    //// Tercera fila "Presupuestos entregados" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>4.- Presupuestos Entregados</td>";
    
    $datos_presupuestos_entregados = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
         $query_presupuestos_entregados = "SELECT COUNT(sa_presupuesto.id_presupuesto) as presupuesto, 
		 										  MONTH(sa_presupuesto.fecha_presupuesto) as mes 
											FROM sa_presupuesto
                                       		WHERE tipo_presupuesto = 1 
									   		AND ((SELECT COUNT(*) AS total_tempario FROM sa_det_presup_tempario WHERE id_presupuesto = sa_presupuesto.id_presupuesto AND id_paquete IS NOT NULL) > 0 
									   		OR 
											(SELECT COUNT(*) AS total_articulo FROM sa_det_presup_articulo WHERE id_presupuesto = sa_presupuesto.id_presupuesto AND id_paquete IS NOT NULL) > 0)									   
											AND DATE(fecha_presupuesto) BETWEEN '$fecha1' AND '$fecha2' 
											GROUP BY mes";
    $presupuestos_entregados = mysql_query($query_presupuestos_entregados, $conex) or die("Error de Selección 'Presupuestos entregados': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($presupuestos_entregados) != 0) {
                while ($row = mysql_fetch_array($presupuestos_entregados)) {                 
                       array_push($datos_presupuestos_entregados, $row["presupuesto"]);                    
                }
            } else {
                array_push($datos_presupuestos_entregados, "0");
            }
        } else {
            array_push($datos_presupuestos_entregados, "Total");
        }
    }
    
    $array_datos_presupuestos_entregados = array();
    $hay_total = 0;
    foreach ($datos_presupuestos_entregados as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_presupuestos_entregados,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_presupuestos_entregados,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_presupuestos_entregados,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>";   
    
     //// Cuarta Fila "Reparaciones Repetidas" ////  
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>5.- Reparaciones Repetidas (Recurrencia)</td>";
    
     $datos_reparaciones_repetidas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
         $query_reparaciones_repetidas = "SELECT COUNT(id_orden) as retrabajo, MONTH(tiempo_orden) as mes FROM sa_orden 
                                       WHERE id_tipo_orden = 6 AND DATE(tiempo_orden) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
    $reparaciones_repetidas = mysql_query($query_reparaciones_repetidas, $conex) or die("Error de Selección 'Reparaciones Repetidas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
            if (mysql_num_rows($reparaciones_repetidas) != 0) {
                while ($row = mysql_fetch_array($reparaciones_repetidas)) {                 
                       array_push($datos_reparaciones_repetidas, $row["retrabajo"]);                    
                }
            } else {
                array_push($datos_reparaciones_repetidas, "0");
            }
        } else {
            array_push($datos_reparaciones_repetidas, "Total");
        }
    }
    
    $array_datos_reparaciones_repetidas = array();
    $hay_total = 0;
    foreach ($datos_reparaciones_repetidas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_reparaciones_repetidas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_reparaciones_repetidas,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_reparaciones_repetidas,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>";   
    
    //// Quinta Fila  "Porcentaje vehiculos Inspeccionados"////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Vehículos Inspeccionados (60%) 3/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_vehiculos_inspeccionados[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }
        
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Sexta Fila "Porcentaje Presupuestos Entregados" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Presupuestos Entregados (70%) 4/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_presupuestos_entregados[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }    
    
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Septima Fila "Porcentaje Reparaciones Repetidas" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Reparaciones Repetidas (<3%) 5/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_reparaciones_repetidas[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }    
    
    $tabla_estadistico_semanal .= "</tr>";    
    
    $tabla_estadistico_semanal .= "</table>";
    
    // FIN PROCESO DE RECEPCION 
    
    // RESERVA DE RECAMBIOS
    
    //// Primera fila "Titulos"  ////
    $tabla_estadistico_semanal .= "<br><table class='tabla-semanal' id='reserva_recambios2' border=1><tr>"; 
    
    $tabla_estadistico_semanal .= $titulo;
    $tabla_estadistico_semanal .= "<td style='white-space:nowrap;'>Reserva de Recambios</td>";
    $tabla_estadistico_semanal .= $titulo_fechas;
    $tabla_estadistico_semanal .= "</tr>";
    
    
    //// Segunda fila "Items Requisitados" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>6.- Ítems Requisitados</td>"; 
    
    $datos_items_requisitados = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
           $query_items_requisitados = "SELECT COUNT(id_det_orden_articulo) as articulos, SUM(cantidad) as cantidad, MONTH(tiempo_asignacion) as mes FROM sa_det_orden_articulo 
                                       WHERE DATE(tiempo_asignacion) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                       //aprobado = 1 AND
    $items_requisitados = mysql_query($query_items_requisitados, $conex) or die("Error de Selección 'Items Requisitados': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    
            if (mysql_num_rows($items_requisitados) != 0) {
                while ($row = mysql_fetch_array($items_requisitados)) {                 
                       array_push($datos_items_requisitados, $row["cantidad"]);                    
                }
            } else {
                array_push($datos_items_requisitados, "0");
            }
        } else {
            array_push($datos_items_requisitados, "Total");
        }
    }
    
    $array_datos_items_requisitados = array();
    $hay_total = 0;
    foreach ($datos_items_requisitados as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_items_requisitados,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_items_requisitados,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_items_requisitados,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>";   
    
    //// Tercera fila "Items Disponibles en Stock" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>7.- Ítems Disponibles en Stock</td>";
    
    $datos_items_disponibles = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
          $query_items_disponibles = "SELECT COUNT(id_det_orden_articulo) as articulos, SUM(cantidad) as cantidad, MONTH(tiempo_asignacion) as mes FROM sa_det_orden_articulo 
                                       WHERE aprobado = 1 AND DATE(tiempo_asignacion) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                       
    $items_disponibles = mysql_query($query_items_disponibles, $conex) or die("Error de Selección 'Items Disponibles': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($items_disponibles) != 0) {
                while ($row = mysql_fetch_array($items_disponibles)) {                 
                       array_push($datos_items_disponibles, $row["cantidad"]);                    
                }
            } else {
                array_push($datos_items_disponibles, "0");
            }
        } else {
            array_push($datos_items_disponibles, "Total");
        }
    }
    
    $array_datos_items_disponibles = array();
    $hay_total = 0;
    foreach ($datos_items_disponibles as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_items_disponibles,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_items_disponibles,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_items_disponibles,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>"; 
    
    
    //// Cuarta fila "Reservas (Vehiculo)"  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>8.- Reservas Vehículos</td>"; 
    
    $datos_reservas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
           $query_reservas = "SELECT COUNT(id_det_orden_articulo) as articulos, SUM(cantidad) as cantidad, MONTH(tiempo_asignacion) as mes                       
                       FROM sa_orden
                       LEFT JOIN sa_det_orden_articulo ON sa_orden.id_orden = sa_det_orden_articulo.id_orden
                       WHERE (id_tipo_orden = 3 OR id_tipo_orden = 4 OR id_tipo_orden = 5)  AND DATE(tiempo_asignacion) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                       //AND aprobado = 1
    $reservas = mysql_query($query_reservas, $conex) or die("Error de Selección 'Reservas (vehiculo)': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($reservas) != 0) {
                while ($row = mysql_fetch_array($reservas)) {                 
                       array_push($datos_reservas, $row["cantidad"]);                    
                }
            } else {
                array_push($datos_reservas, "0");
            }
        } else {
            array_push($datos_reservas, "Total");
        }
    }
    
    $array_datos_reservas = array();
    $hay_total = 0;
    foreach ($datos_reservas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_reservas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_reservas,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_reservas,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Quitan fila Porcentaje de "Fill rate taller mecanico"  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Fill Rate Taller Mecánico (90%) 7/6</b></td>";    
    
    foreach($array_datos_items_requisitados as $indice => $valor){
        if($array_datos_items_requisitados[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_items_disponibles[$indice]/$array_datos_items_requisitados[$indice])*100,1)."%</b></td>";
        }
    }    
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Sexta fila Porcentaje de "Reservas" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Reservas (70%) 8/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_reservas[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }    
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    $tabla_estadistico_semanal .= "</table>";
    
    // FIN RESERVA DE RECAMBIOS
    
    // ENTREGA DEL VEHICULO
    
    //// Primera fila "Titulos"  ////
   $tabla_estadistico_semanal .= "<br><table class='tabla-semanal' id='entrega_vehiculo2' border=1><tr>"; 
    
    $tabla_estadistico_semanal .= $titulo;
    $tabla_estadistico_semanal .= "<td style='white-space:nowrap;'>Entrega del Vehículo</td>";
    $tabla_estadistico_semanal .= $titulo_fechas;
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Segunda fila "OR y vehiculos revisados a tiempo" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>9.- OR y Vehículo Revisados a Tiempo</td>"; 
    
    $datos_vehiculos_revisados = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
          $query_vehiculos_revisados = "SELECT MONTH(fecha_entrada) AS mes, SUM(if(DATE(fecha_estimada_entrega) >= DATE(tiempo_entrega), 1,0)) AS antes_tiempo
                                  FROM sa_recepcion
                                  LEFT JOIN sa_orden ON sa_recepcion.id_recepcion = sa_orden.id_recepcion                                 
                                  WHERE fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                  
    $vehiculos_revisados = mysql_query($query_vehiculos_revisados, $conex) or die("Error de Selección 'Vehículos revisados a tiempo': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
            if (mysql_num_rows($vehiculos_revisados) != 0) {
                while ($row = mysql_fetch_array($vehiculos_revisados)) {                 
                       array_push($datos_vehiculos_revisados, $row["antes_tiempo"]);                    
                }
            } else {
                array_push($datos_vehiculos_revisados, "0");
            }
        } else {
            array_push($datos_vehiculos_revisados, "Total");
        }
    }
    
    $array_datos_vehiculos_revisados = array();
    $hay_total = 0;
    foreach ($datos_vehiculos_revisados as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_vehiculos_revisados,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_vehiculos_revisados,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_vehiculos_revisados,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Tercera fila "Explicacion del trabajo" //// no exsiste todavia, pero se puede contar si se llamo a la persona o algo
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>10.- Explicación del Trabajo</td>"; 
    
    $datos_explicacion = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
          $query_explicacion = "SELECT MONTH(fecha_entrada) AS mes, SUM(if(DATE(fecha_estimada_entrega) >= DATE(tiempo_entrega), 1,0)) AS antes_tiempo
                                  FROM sa_recepcion
                                  LEFT JOIN sa_orden ON sa_recepcion.id_recepcion = sa_orden.id_recepcion                                 
                                  WHERE fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                  
    $explicacion = mysql_query($query_explicacion, $conex) or die("Error de Selección 'Explicacion del trabajo': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
            if (mysql_num_rows($explicacion) != 0) {
                while ($row = mysql_fetch_array($explicacion)) {                 
                       array_push($datos_explicacion, $row["antes_tiempo"]);                    
                }
            } else {
                array_push($datos_explicacion, "0");
            }
        } else {
            array_push($datos_explicacion, "Total");
        }
    }
    
    $array_datos_explicacion = array();
    $hay_total = 0;
    foreach ($datos_explicacion as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_explicacion,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_explicacion,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_explicacion,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Cuarta fila porcentaje de "Cumplimiento hora de entrega"  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Cumplimiento Hora de Entrega (90%) 9/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_vehiculos_revisados[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Quinta fila porcentaje de "Explicacion del trabajo"  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Explicación del Trabajo (90%) 10/1</b></td>";
    
    foreach($array_datos_entrada_vehiculos as $indice => $valor){
        if($array_datos_entrada_vehiculos[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_explicacion[$indice]/$array_datos_entrada_vehiculos[$indice])*100,1)."%</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</table>";
    
    // FIN ENTREGA DEL VEHICULO
    
    // SEGUIMIENTO
    // 
    //// Primera fila "Titulos"  ////
   $tabla_estadistico_semanal .= "<br><table class='tabla-semanal' id='seguimiento2' border=1><tr>"; 
    
    $tabla_estadistico_semanal .= $titulo;
    $tabla_estadistico_semanal .= "<td style='white-space:nowrap;'>Seguimiento</td>";
    $tabla_estadistico_semanal .= $titulo_fechas;
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Segunda fila "Clientes a Contactar" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>11.- Clientes a Contactar</td>"; 
    
    $datos_clientes_contactar = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_clientes_contactar = "SELECT MONTH(fecha_entrada) AS mes, COUNT(sa_cita.id_cita) as contactar
                                  FROM sa_recepcion
                                  LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                                 
                                  WHERE n_respuesta IS NULL AND fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                  
    $clientes_contactar = mysql_query($query_clientes_contactar, $conex) or die("Error de Selección 'Clientes a Contactar': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
            if (mysql_num_rows($clientes_contactar) != 0) {
                while ($row = mysql_fetch_array($clientes_contactar)) {                 
                       array_push($datos_clientes_contactar, $row["contactar"]);                    
                }
            } else {
                array_push($datos_clientes_contactar, "0");
            }
        } else {
            array_push($datos_clientes_contactar, "Total");
        }
    }
    
    $array_datos_clientes_contactar = array();
    $hay_total = 0;
    foreach ($datos_clientes_contactar as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_clientes_contactar,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_clientes_contactar,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_clientes_contactar,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>"; 
    
    
    
     //// Tercera fila "Clientes Contactados" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>12.- Clientes Contactados</td>";
    
    $datos_clientes_contactados = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_clientes_contactados = "SELECT MONTH(fecha_entrada) AS mes, COUNT(sa_cita.id_cita) as contactados
                                  FROM sa_recepcion
                                  LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                                 
                                  WHERE n_respuesta IS NOT NULL AND fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                  
    $clientes_contactados = mysql_query($query_clientes_contactados, $conex) or die("Error de Selección 'Clientes Contactados': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
            if (mysql_num_rows($clientes_contactados) != 0) {
                while ($row = mysql_fetch_array($clientes_contactados)) {                 
                       array_push($datos_clientes_contactados, $row["contactados"]);                    
                }
            } else {
                array_push($datos_clientes_contactados, "0");
            }
        } else {
            array_push($datos_clientes_contactados, "Total");
        }
    }
    
    $array_datos_clientes_contactados = array();
    $hay_total = 0;
    foreach ($datos_clientes_contactados as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_clientes_contactados,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_clientes_contactados,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_clientes_contactados,$datos);
        }
    }
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Cuarta fila "Clientes Completamente Satisfechos" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>13.- Clientes Completamente Satisfechos</td>";
    
    $datos_clientes_satisfechos = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_clientes_satisfechos = "SELECT MONTH(fecha_entrada) AS mes, COUNT(sa_cita.id_cita) as contactados
                                  FROM sa_recepcion
                                  LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                                 
                                  WHERE (n_respuesta = 1 OR n_respuesta = 2 OR n_respuesta = 3) AND fecha_entrada BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                  
    $clientes_satisfechos = mysql_query($query_clientes_satisfechos, $conex) or die("Error de Selección 'Clientes Satisfechos': " . mysql_error() . "\n Error Nº: " . mysql_errno()."\n Linea:".__LINE__);
    
            if (mysql_num_rows($clientes_satisfechos) != 0) {
                while ($row = mysql_fetch_array($clientes_satisfechos)) {                 
                       array_push($datos_clientes_satisfechos, $row["contactados"]);                    
                }
            } else {
                array_push($datos_clientes_satisfechos, "0");
            }
        } else {
            array_push($datos_clientes_satisfechos, "Total");
        }
    }
    
    $array_datos_clientes_satisfechos = array();
    $hay_total = 0;
    foreach ($datos_clientes_satisfechos as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_clientes_satisfechos,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_clientes_satisfechos,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_clientes_satisfechos,$datos);
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Quinta fila porcentaje de "Clientes Contactados"  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Clientes Contactados (80%) 12/11</b></td>";
    
    foreach($array_datos_clientes_contactar as $indice => $valor){
        if($array_datos_clientes_contactar[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_clientes_contactados[$indice]/$array_datos_clientes_contactar[$indice])*100,1)."%</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Quinta fila porcentaje de "Clientes Satisfechos"  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Clientes Satisfechos (90%) 13/12</b></td>";
    
    foreach($array_datos_clientes_contactar as $indice => $valor){
        if($array_datos_clientes_contactar[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_clientes_satisfechos[$indice]/$array_datos_clientes_contactar[$indice])*100,1)."%</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>";
    
    $tabla_estadistico_semanal .= "</table>";
    
    // FIN SEGUIMIENTO
    
    // CONTROL DE TALLER
    
    //// Primera fila "Titulos"  ////
   $tabla_estadistico_semanal .= "<br><table class='tabla-semanal' id='control_taller2' border=1><tr>"; 
    
    $tabla_estadistico_semanal .= $titulo;
    $tabla_estadistico_semanal .= "<td style='white-space:nowrap;'>Control de Taller</td>";
    $tabla_estadistico_semanal .= $titulo_fechas;
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Segunda fila "Horas Presencia" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>14.- Horas Presencia</td>";
    
    $datos_horas_presencia = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_horas_presencia = "SELECT COUNT(DISTINCT DATE(fecha_creada)) as registro_dia, MONTH(fecha_creada) as mes 
                              FROM sa_presencia_mecanicos 
                              WHERE DATE(fecha_creada) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                       
    $horas_presencia = mysql_query($query_horas_presencia, $conex) or die("Error de Selección 'Horas Presencia': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($horas_presencia) != 0) {
                while ($row = mysql_fetch_array($horas_presencia)) {                 
                       array_push($datos_horas_presencia, $row["registro_dia"]);                    
                }
            } else {
                array_push($datos_horas_presencia, "0");
            }
        } else {
            array_push($datos_horas_presencia, "Total");
        }
    }
    
    $array_datos_horas_presencia = array();
    $hay_total = 0;
    foreach ($datos_horas_presencia as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_horas_presencia,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_horas_presencia,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_horas_presencia,$datos);
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// tercera fila "Horas Trabajadas" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>15.- Horas Trabajadas</td>";
    
    $datos_horas_trabajadas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_horas_trabajadas = "SELECT *, SUM(minutos_presencia) as minutos_presencia, MONTH(fecha_creada) as mes 
                              FROM sa_presencia_mecanicos 
                              WHERE DATE(fecha_creada) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                       
    $horas_trabajadas = mysql_query($query_horas_trabajadas, $conex) or die("Error de Selección 'Horas Trabajadas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($horas_trabajadas) != 0) {
                while ($row = mysql_fetch_array($horas_trabajadas)) {                 
                       array_push($datos_horas_trabajadas, $row["minutos_presencia"]);                    
                }
            } else {
                array_push($datos_horas_trabajadas, "0");
            }
        } else {
            array_push($datos_horas_trabajadas, "Total");
        }
    }
    
    $array_datos_horas_trabajadas = array();
    $hay_total = 0;
    foreach ($datos_horas_trabajadas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_horas_trabajadas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_horas_trabajadas,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_horas_trabajadas,$datos);
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    
    //// Cuarta fila "Horas Facturadas en OT Cerradas (int., Ext., Gar.) " ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>16.- Horas Facturadas OT Cerradas (int., Ext., Gar.)</td>";
    
    $datos_horas_facturadas_todas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_horas_facturadas_todas = "SELECT SUM(ut) as total_horas_facturadas, MONTH(tiempo_orden) as mes
                                     FROM sa_orden
                                     LEFT JOIN sa_det_orden_tempario ON sa_orden.id_orden = sa_det_orden_tempario.id_orden
                                     WHERE (id_estado_orden = 18 OR id_estado_orden = 24) AND DATE(tiempo_orden) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                       
    $horas_facturadas_todas = mysql_query($query_horas_facturadas_todas, $conex) or die("Error de Selección 'Horas Facturadas todas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($horas_facturadas_todas) != 0) {
                while ($row = mysql_fetch_array($horas_facturadas_todas)) {                 
                       array_push($datos_horas_facturadas_todas, round($row["total_horas_facturadas"]/100));                    
                }
            } else {
                array_push($datos_horas_facturadas_todas, "0");
            }
        } else {
            array_push($datos_horas_facturadas_todas, "Total");
        }
    }
    
    $array_datos_horas_facturadas_todas = array();
    $hay_total = 0;
    foreach ($datos_horas_facturadas_todas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_horas_facturadas_todas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_horas_facturadas_todas,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_horas_facturadas_todas,$datos);
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Quinta fila "Horas Facturadas en OT Cerradas (Internas) " ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>17.- Horas Facturadas OT (Internas)</td>";
    
    $datos_horas_facturadas_internas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_horas_facturadas_internas = "SELECT SUM(ut) as total_horas_facturadas_internas, MONTH(tiempo_asignacion) as mes
                                     FROM sa_orden
                                     LEFT JOIN sa_det_orden_tempario ON sa_orden.id_orden = sa_det_orden_tempario.id_orden
                                     WHERE (id_tipo_orden = 3 OR id_tipo_orden = 4 OR id_tipo_orden = 6) AND id_estado_orden = 24 AND DATE(tiempo_asignacion) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                       
    $horas_facturadas_internas = mysql_query($query_horas_facturadas_internas, $conex) or die("Error de Selección 'Horas Facturadas Internas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($horas_facturadas_internas) != 0) {
                while ($row = mysql_fetch_array($horas_facturadas_internas)) {                 
                       array_push($datos_horas_facturadas_internas, round($row["total_horas_facturadas_internas"]/100));                    
                }
            } else {
                array_push($datos_horas_facturadas_internas, "0");
            }
        } else {
            array_push($datos_horas_facturadas_internas, "Total");
        }
    }
    
    $array_datos_horas_facturadas_internas = array();
    $hay_total = 0;
    foreach ($datos_horas_facturadas_internas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_horas_facturadas_internas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_horas_facturadas_internas,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_horas_facturadas_internas,$datos);
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
   
    //// Sexta fila "Facturacion de Repuesto al taller OR cerradas bsX1000" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>18.- Facturación de Repuesto al Taller OR Cerradas bsX1000</td>"; 
    
    $datos_facturacion_repuesto = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_facturacion_repuesto = "SELECT ((precio_unitario * cantidad)*((
																		(SELECT SUM(sa_det_orden_articulo_iva.iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
																		/100)+1)) AS total,
                                        SUM(((precio_unitario * cantidad)*((
																		(SELECT SUM(sa_det_orden_articulo_iva.iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
																		/100)+1))) as suma,
                                        MONTH(tiempo_asignacion) as mes                     
                                        FROM sa_orden
                                        LEFT JOIN sa_det_orden_articulo ON sa_orden.id_orden = sa_det_orden_articulo.id_orden
                                        WHERE (id_estado_orden = 18 OR id_estado_orden = 24) AND aprobado = 1 AND DATE(tiempo_asignacion) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";
                                       
    $horas_facturacion_repuesto = mysql_query($query_facturacion_repuesto, $conex) or die("Error de Selección 'Facturación de repuestos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($horas_facturacion_repuesto) != 0) {
                while ($row = mysql_fetch_array($horas_facturacion_repuesto)) {                 
                       array_push($datos_facturacion_repuesto, round($row["suma"]));                    
                }
            } else {
                array_push($datos_facturacion_repuesto, "0");
            }
        } else {
            array_push($datos_facturacion_repuesto, "Total");
        }
    }
    
    $array_datos_facturacion_repuesto = array();
    $hay_total = 0;
    foreach ($datos_facturacion_repuesto as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_facturacion_repuesto,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_facturacion_repuesto,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_facturacion_repuesto,$datos);
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    
    //// Septima fila "Numero de vehiculos con O/R Cerradas (Ext., Gar.)" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>19.- Número de vehículos con O/R Cerradas (Ext., Gar.)</td>";
    
    $datos_vehiculos_cerradas = array();
    
    foreach ($array_titulo_fechas as $fecha) {
        if ($fecha != "Total") {
            $separar = explode("x", $fecha);
            $fecha1 = implode("-", array_reverse(explode("-", $separar[0])));
            $fecha2 = implode("-", array_reverse(explode("-", $separar[1])));
            
    $query_vehiculos_cerradas = "SELECT COUNT(DISTINCT sa_recepcion.id_recepcion) AS cantidad_vehiculos, MONTH(fecha_entrada) AS mes
                                FROM sa_orden 
                                LEFT JOIN sa_recepcion ON sa_orden.id_recepcion = sa_recepcion.id_recepcion
                                WHERE (id_estado_orden = 18 OR id_estado_orden = 24) AND id_tipo_orden != 4 
                                AND DATE(fecha_entrada) BETWEEN '$fecha1' AND '$fecha2' GROUP BY mes";    
    
    $vehiculos_cerradas = mysql_query($query_vehiculos_cerradas, $conex) or die("Error de Selección 'Vehículos con ordenes cerradas': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
            if (mysql_num_rows($vehiculos_cerradas) != 0) {
                while ($row = mysql_fetch_array($vehiculos_cerradas)) {                 
                       array_push($datos_vehiculos_cerradas, $row["cantidad_vehiculos"]);                    
                }
            } else {
                array_push($datos_vehiculos_cerradas, "0");
            }
        } else {
            array_push($datos_vehiculos_cerradas, "Total");
        }
    }
    
    $array_datos_vehiculos_cerradas= array();
    $hay_total = 0;
    foreach ($datos_vehiculos_cerradas as $indice => $datos){
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_vehiculos_cerradas,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;
            array_push($array_datos_vehiculos_cerradas,array_sum($array_dividido));
            
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_vehiculos_cerradas,$datos);
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    
    //// Octava fila porcentaje "ocupacion" ////    
     $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Ocupación (85%) 15/14</b></td>";
    
    foreach($array_datos_horas_presencia as $indice => $valor){
        if($array_datos_horas_presencia[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_horas_trabajadas[$indice]/$array_datos_horas_presencia[$indice])*100,1)."%</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// novena fila porcentaje "Eficiencia" ////    
     $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Eficiencia (110%) 16/15</b></td>";
    
    foreach($array_datos_horas_trabajadas as $indice => $valor){
        if($array_datos_horas_trabajadas[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_horas_facturadas_todas[$indice]/$array_datos_horas_trabajadas[$indice])*100,1)."%</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// decima fila porcentaje "Productividad" ////    
     $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b>% Productividad (100%) 16/14</b></td>";
    
    foreach($array_datos_horas_presencia as $indice => $valor){
        if($array_datos_horas_presencia[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0%</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_horas_facturadas_todas[$indice]/$array_datos_horas_presencia[$indice])*100,1)."%</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// onceava fila porcentaje "Horas Facturadas / vehiculos " ////    
     $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b> Horas Facturadas / Vehículo (>2,5 Hr) (16-17)/19</b></td>";
    
    foreach($array_datos_vehiculos_cerradas as $indice => $valor){
        if($array_datos_vehiculos_cerradas[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_horas_facturadas_todas[$indice]-$array_datos_horas_facturadas_internas[$indice])/$array_datos_vehiculos_cerradas[$indice],1)."</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// doceava fila porcentaje "Repuestos facturados / vehiculos dolar" ////    
     $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b> Repuestos Facturados / Vehículo (90$) 18/19</b></td>";
    
     //// calculo valor del dolar ////
     $array_datos_dolar = $array_titulo_fechas;     
     foreach($array_datos_dolar as $indice => $valor){       
                  
        $separar = explode("x",$array_datos_dolar[$indice]);
        $mes = date("m", strtotime($separar[0]));
        $fecha = date("Y",strtotime($separar[0]));
         
         if ($fecha <= 2012){
             $array_datos_dolar[$indice] = "4.30";
         }
         if ($fecha == 2013){             
             if($mes == 1){
                $array_datos_dolar[$indice] = "4.30";
             }else{
                $array_datos_dolar[$indice] = "6.30";
             }
         }
         if ($fecha > 2013){
             $array_datos_dolar[$indice] = "6.30";
         }
         
     }
     
     foreach($array_datos_vehiculos_cerradas as $indice => $valor){
        if($array_datos_vehiculos_cerradas[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_facturacion_repuesto[$indice]/$array_datos_vehiculos_cerradas[$indice])/$array_datos_dolar[$indice],1)."</b></td>";
        }
    }
    
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    //// Fila valor del dolar  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b> Valor del Dolar</b></td>";
    foreach ($array_datos_dolar as $indice => $valor){
        $tabla_estadistico_semanal .= "<td><b>$valor</b></td>";
    }
    
    $tabla_estadistico_semanal .= "</tr>"; 
    
    $tabla_estadistico_semanal .= "</table>";
    // FIN CONTROL TALLER    
    
    
    // CAPACIDAD DE SERVICIO
    
    //// Primera fila "Titulos"  ////
   $tabla_estadistico_semanal .= "<br><table class='tabla-semanal' id='capacidad_servicio2' border=1><tr>";
    
    $tabla_estadistico_semanal .= $titulo;
    $tabla_estadistico_semanal .= "<td style='white-space:nowrap;'>Datos sobre Capacidad de Servicio</td>";
    $tabla_estadistico_semanal .= $titulo_fechas;
    $tabla_estadistico_semanal .= "</tr>";
    
    
    //// Segunda fila "Numero de tecnicos" ////
    $query_tecnicos = "SELECT COUNT(id_empleado) as cantidad_mecanicos
                        FROM pg_empleado
                        LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        LEFT JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
                        WHERE (pg_cargo.id_cargo =13 OR pg_cargo.id_cargo =14 OR pg_cargo.id_cargo =15) AND activo = 1";
                                       
    $tecnicos = mysql_query($query_tecnicos, $conex) or die("Error de Selección 'Número de Técnicos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    while ($row = mysql_fetch_array($tecnicos)) {
            $cantidad_tecnicos = $row["cantidad_mecanicos"];            
    }
    
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>20.- Número de Técnicos</td>"; 
    
    $array_datos_tecnicos = $array_titulo_fechas;
    foreach($array_datos_tecnicos as $indice => $valor){
        $array_datos_tecnicos[$indice] = $cantidad_tecnicos;
    }
    
    foreach ($array_datos_tecnicos as $indice => $valor){
        $fondo = "";
        if($array_titulo_fechas[$indice] == "Total"){
            $fondo = "style='background-color:#CCCCCC'";
        }
       $tabla_estadistico_semanal .= "<td $fondo>$valor</td>";
    }
    
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Tercera fila "Número de Técnicos en rapid service" ////
    $query_tenicos_rapidservice = "SELECT COUNT(id_empleado) as cantidad_mecanicos
                        FROM pg_empleado
                        LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        LEFT JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
                        WHERE (pg_cargo.id_cargo =13 OR pg_cargo.id_cargo =14 OR pg_cargo.id_cargo =15) AND activo = 1";
                                       
    $tecnicos_rapidservice = mysql_query($query_tenicos_rapidservice, $conex) or die("Error de Selección 'Númro de técnicos en rapid service': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    while ($row = mysql_fetch_array($tecnicos_rapidservice)) {
            $cantidad_tecnicos = $row["cantidad_mecanicos"];            
    }
    
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>21.- Número de Técnicos en Rapid Service</td>"; 
    
    $array_datos_tecnicos_rapidservice = $array_titulo_fechas;
    foreach($array_datos_tecnicos_rapidservice as $indice => $valor){
        $array_datos_tecnicos_rapidservice[$indice] = $cantidad_tecnicos;
    }
    
    foreach ($array_datos_tecnicos_rapidservice as $indice => $valor){
        $fondo = "";
        if($array_titulo_fechas[$indice] == "Total"){
            $fondo = "style='background-color:#CCCCCC'";
        }
       $tabla_estadistico_semanal .= "<td $fondo>$valor</td>";
    }
    
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Cuarta fila "Número de Ayudantes Generales (Excluir Lavador)" ////
    
    $tabla_estadistico_semanal .= "</tr>";
    
    $query_ayudantes = "SELECT COUNT(id_empleado) as cantidad_ayudantes
                        FROM pg_empleado
                        LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        LEFT JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
                        WHERE pg_cargo.id_cargo =16 AND activo = 1";    
    
    $ayudantes = mysql_query($query_ayudantes, $conex) or die("Error de Selección 'Número de Ayudantes': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    while ($row = mysql_fetch_array($ayudantes)) {
            $cantidad_ayudantes = $row["cantidad_ayudantes"];            
    }
    
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>22.- Número de Ayudantes Generales (Excluir Lavador)</td>"; 
    
    $array_datos_ayudantes = $array_titulo_fechas;
    foreach($array_datos_ayudantes as $indice => $valor){
        $array_datos_ayudantes[$indice] = $cantidad_ayudantes;
    }
    
    foreach ($array_datos_ayudantes as $indice => $valor){
        $fondo = "";
        if($array_titulo_fechas[$indice] == "Total"){
            $fondo = "style='background-color:#CCCCCC'";
        }
       $tabla_estadistico_semanal .= "<td $fondo>$valor</td>";
    }  
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Quinta fila "Número de Puestos de Trabajo" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>23.- Número de Puestos de Trabajo</td>"; 
    
    $array_datos_puestos_trabajo = $array_titulo_fechas;
    
    foreach($array_datos_puestos_trabajo as $indice => $valor){
        $fondo = "";
        if($array_titulo_fechas[$indice] == "Total"){
            $fondo = "style='background-color:#CCCCCC'";
        }
        $tabla_estadistico_semanal .= "<td $fondo>0</td>";
    }
    $tabla_estadistico_semanal .= "</tr>";
    
    
     //// Sexta fila "Número de Puestos de Espera" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>24.- Número de Puestos de Espera</td>"; 
    
    $array_datos_puestos_espera = $array_titulo_fechas;
    
    foreach($array_datos_puestos_espera as $indice => $valor){
        $fondo = "";
        if($array_titulo_fechas[$indice] == "Total"){
            $fondo = "style='background-color:#CCCCCC'";
        }
        $tabla_estadistico_semanal .= "<td $fondo>0</td>";
    }
    
    $tabla_estadistico_semanal .= "</tr>";
    
    
    //// Septima fila "Dias habiles en el mes" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>25.- Número de Días Hábiles en el Mes</td>";
    
    $datos_dias_habiles = $array_titulo_fechas;
    
    foreach($datos_dias_habiles as $indice => $valor){
        $datos_dias_habiles[$indice] = filtrarDiasHabiles($valor,$anio,true);
    }
    
    $array_datos_dias_habiles = array();
    $hay_total = 0;
    foreach($datos_dias_habiles as $indice => $datos){
        //$tabla_estadistico_semanal .= "<td>$valor</td>";       
    
        if($datos === "Total"){      
           $array_dividido = array_slice($datos_dias_habiles,$hay_total,$indice-$hay_total);
            $tabla_estadistico_semanal .= "<td style='background-color:#CCCCCC'>".  array_sum($array_dividido)."</td>";
            $hay_total = $indice+1;   
            array_push($array_datos_dias_habiles,array_sum($array_dividido));
        }else{
            $tabla_estadistico_semanal .= "<td>".$datos."</td>";
            array_push($array_datos_dias_habiles,$datos);
        }
    
    }
    
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Octava fila "Número de Asesores" ////
    $query_asesores = "SELECT COUNT(id_empleado) as cantidad_asesores
                        FROM pg_empleado
                        LEFT JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                        LEFT JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
                        WHERE pg_cargo.id_cargo =6 AND activo = 1";    
    
    $asesores = mysql_query($query_asesores, $conex) or die("Error de Selección 'Número de Asesores': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    
    while ($row = mysql_fetch_array($asesores)) {
            $cantidad_asesores = $row["cantidad_asesores"];            
    }
    
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'>26.- Número de Asesores</td>"; 
    
    $array_datos_asesores = $array_titulo_fechas;
    foreach($array_datos_asesores as $indice => $valor){
        $array_datos_asesores[$indice] = $cantidad_asesores;
    }
    
    foreach ($array_datos_asesores as $indice => $valor){
        $fondo = "";
        if($array_titulo_fechas[$indice] == "Total"){
            $fondo = "style='background-color:#CCCCCC'";
        }
       $tabla_estadistico_semanal .= "<td $fondo>$valor</td>";
    }   
    $tabla_estadistico_semanal .= "</tr>";
    
    
    //// Novena fila "Total de Entrada Diaria (Incluye Rapid Service)"  ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b> Total de Entrada Diaria (Incluye R.Service) (100) (1+1.1)/25</b></td>";
    
    foreach($array_datos_dias_habiles as $indice => $valor){
        if($array_datos_dias_habiles[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round(($array_datos_entrada_vehiculos[$indice]+$array_datos_rapid_service[$indice])/$array_datos_dias_habiles[$indice],1)."</b></td>";
        }
    }
    
    $tabla_estadistico_semanal .= "</tr>";
    
    //// Decima fila "Dias de Citas (Promedio)" ////
    $tabla_estadistico_semanal .= "<tr><td style='white-space:nowrap;'><b> Días de Citas (Promedio) (3) 1.2/2</b></td>";
    
    foreach($array_datos_numero_citas as $indice => $valor){
        if($array_datos_numero_citas[$indice] == "0"){
            $tabla_estadistico_semanal .= "<td><b>0</b></td>";
        }else{
            $tabla_estadistico_semanal .= "<td><b>".round($array_datos_solicitud_citas[$indice]/$array_datos_numero_citas[$indice],1)."</b></td>";
        }
    }
            
    $tabla_estadistico_semanal .= "</table>";
    
    
    $respuesta->clear("contenedor_estadistico", "innerHTML");    
    $respuesta->assign("contenedor_estadistico","innerHTML",$tabla_estadistico_semanal);
    return $respuesta;
    
}







/***********************************************************************************************************************/

/* FUNCIONES COMUNES PHP */

/**
 * Esta funcion se encarga de convertir una variable tipo date a su diminutivo de mes "Ene, Feb, Mar"
 * @param date $fecha //Fecha de entrada en cualquier formato formato date
 * @return string //Un string "Ene"
 */

function traducefecha($fecha,$dias = false){
     $fecha=strtotime($fecha);// convierte la fecha de formato mm/dd/yyyy o Y-m-d a marca de tiempo    
     
    $mes=date("m",$fecha); // numero del mes de 01 a 12
       switch($mes)
       {
       case "01":
          $mes="Ene";
          break;
       case "02":
          $mes="Feb";
          break;
       case "03":
          $mes="Mar";
          break;
       case "04":
          $mes="Abr";
          break;
       case "05":
          $mes="May";
          break;
       case "06":
          $mes="Jun";
          break;
       case "07":
          $mes="Jul";
          break;
       case "08":
          $mes="Ago";
          break;
       case "09":
          $mes="Sep";
          break;
       case "10":
          $mes="Oct";
          break;
       case "11":
          $mes="Nov";
          break;
       case "12":
          $mes="Dic";
          break;
       }
    
     if($dias == true){
        return date("d",$fecha).$mes;
    }
       
    $fecha=$mes;
    
        return $fecha; //enviamos la fecha
    } 
    
    /**
     * Esta funcion se encarga de convertir un numero a un mes completo 
     * @param string $numero_mes //numero de mes en string formato 01 02 03
     * @return string //Devuelve el mes completo "Enero"
     */
    
    function mostrarmes($numero_mes){
        switch($numero_mes)
       {
        case "01":
          $numero_mes="Enero";
          break;
       case "02":
          $numero_mes="Febrero";
          break;
       case "03":
          $numero_mes="Marzo";
          break;
       case "04":
          $numero_mes="Abril";
          break;
       case "05":
          $numero_mes="Mayo";
          break;
       case "06":
          $numero_mes="Junio";
          break;
       case "07":
          $numero_mes="Julio";
          break;
       case "08":
          $numero_mes="Agosto";
          break;
       case "09":
          $numero_mes="Septiembre";
          break;
       case "10":
          $numero_mes="Octubre";
          break;
       case "11":
          $numero_mes="Noviembre";
          break;
       case "12":
          $numero_mes="Diciembre";
          break;
       }
       
      return $numero_mes;
    }
    
    /**
     * Esta funcion cuenta cuantos elementos repetidos hay en un array y los devuelve indicando el numero de repeticiones
     * tambien tiene la opcion de devolverlo sin repetidos
     * @param String $array //Se introduce un array de cualquier cosa
     * @param Boolean $returnWithNonRepeatedItems //Indica si deseas repetido o no false comumente
     * @return Array //Devuelve un array con valores repetidos o no repetidos
     */
    
    function elementosrepetidos($array, $returnWithNonRepeatedItems = false) {
    $repeated = array();

    foreach ((array) $array as $value) {
        $inArray = false;

        foreach ($repeated as $i => $rItem) {
            if ($rItem['value'] === $value) {
                $inArray = true;
                ++$repeated[$i]['count'];
            }
        }

        if (false === $inArray) {
            $i = count($repeated);
            $repeated[$i] = array();
            $repeated[$i]['value'] = $value;
            $repeated[$i]['count'] = 1;
        }
    }

    if (!$returnWithNonRepeatedItems) {
        foreach ($repeated as $i => $rItem) {
            if ($rItem['count'] === 1) {
                unset($repeated[$i]);
            }
        }
    }

    sort($repeated);

    return $repeated;
}

/**
 * Funcion que se encarga de buscar cuantos dias habiles hay al mes 
 * se le pasa numero de dia del mes y devuelve el numero de dias habiles de ese mes
 * @param int-str $mes //Es el numero de mes Enero = 1 etc
 * @return string //Numero de dias habiles del mes 1 = 26
 */
function filtrarDiasHabiles($mes,$anio,$semanal = false){
    
    
$feriados = array(
    "01-01", //Enero - año nuevo
    "02-11","02-12",//Febrero - carnavales
    "03-28","03-29",//Marzo - semana santa
    "04-19",//Abril - movimiento precursor de la independencia
    "05-01",//Mayo - dia del trabajador
    "06-24",//Junio - batalla de carabobo
    "07-05","07-24",//Julio - dia de la independencia, natalicio del libertador
    "10-12",//Octubre - dia de la resistencia indigena   //"08-24",//Estado Zulia - natalicio de rafael urdaneta
    //"11-18",//Noviembre - Estado Zulia nuestra señora de chiquinquira
    "12-24","12-25","12-37"); //Diciembre - Navidad, Natividad de nuestro seño, fin de año 

foreach($feriados as $indice => $valor){
    $feriados[$indice] = $anio."-".$valor;
}

if($semanal === true){
    //if ($mes == )
    if($mes == "Total"){
        return $mes;
    }else{
        $separar = explode("x",$mes);
        $primer_dia_mes = $separar[0];
        $ultimo_dia_mes = $separar[1];    
    }   
    
}else{
    $primer_dia_mes = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $anio));
    $ultimo_dia_mes = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anio));
}
$cantidad_dias = getWorkingDays($primer_dia_mes,$ultimo_dia_mes,$feriados);
       
      return $cantidad_dias;
    
}


//The function returns the no. of business days between two dates and it skips the holidays
function getWorkingDays($startDate,$endDate,$holidays){
    // do strtotime calculations just once
    $endDate = strtotime($endDate);
    $startDate = strtotime($startDate);


    //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
    //We add one to inlude both dates in the interval.
    $days = ($endDate - $startDate) / 86400 + 1;

    $no_full_weeks = floor($days / 7);
    $no_remaining_days = fmod($days, 7);

    //It will return 1 if it's Monday,.. ,7 for Sunday
    $the_first_day_of_week = date("N", $startDate);
    $the_last_day_of_week = date("N", $endDate);

    //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
    //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
    if ($the_first_day_of_week <= $the_last_day_of_week) {
        if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) $no_remaining_days--;
        if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) $no_remaining_days--;
    }
    else {
        // (edit by Tokes to fix an edge case where the start day was a Sunday
        // and the end day was NOT a Saturday)

        // the day of the week for start is later than the day of the week for end
        if ($the_first_day_of_week == 7) {
            // if the start date is a Sunday, then we definitely subtract 1 day
            $no_remaining_days--;

            if ($the_last_day_of_week == 6) {
                // if the end date is a Saturday, then we subtract another day
                $no_remaining_days--;
            }
        }
        else {
            // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
            // so we skip an entire weekend and subtract 2 days
            $no_remaining_days -= 2;
        }
    }

    //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
//---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
   $workingDays = $no_full_weeks * 5;
    if ($no_remaining_days > 0 )
    {
      $workingDays += $no_remaining_days;
    }

    //We subtract the holidays
    foreach($holidays as $holiday){
        $time_stamp=strtotime($holiday);
        //If the holiday doesn't fall in weekend
        if ($startDate <= $time_stamp && $time_stamp <= $endDate && date("N",$time_stamp) != 6 && date("N",$time_stamp) != 7)
            $workingDays--;
    }

    return $workingDays;
}

function minutosHoras($minutos){
    if($minutos !=0){
        $minutos = round($minutos/60);
        return $minutos;
    }else{
        return $minutos;
    }
}

/**
 * Se encarga de convertir minutos a horas con minutos
 * @param entero,float $mins -Acepta cualquier numero y negativos los convierte a positivo, solo minutos
 * @return stringF -Devuelve un string formateado a 00h:00m ahora 0h:00m
 */
   function m2h($mins) {
		if ($mins < 0){
			$min = abs($mins);
		}else{
			$min = $mins;
		}
		
		$h = floor($min / 60);
		$m = ($min - ($h * 60)) / 100;
		$horas = $h + $m;

		if ($mins < 0){
			$horas *= -1;
		}
		
		$sep = explode('.', $horas);
		$h = $sep[0];
		if (empty($sep[1])){
			$sep[1] = 00;
		}

		$m = $sep[1]; //se le coloca un cero pero se imprime sin el
		if (strlen($m) < 2){
			$m = $m . 0;
		}
		return sprintf('Horas: %2d Minutos: %02d', $h, $m);
	}

?>