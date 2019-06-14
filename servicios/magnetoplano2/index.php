<?php
session_start();
//($_SESSION);
 //error_reporting(E_ALL);
 //ini_set("display_errors", 1);

 //conexion
//require_once("conex.php");
include("../../connections/conex.php");//servidor

//Opcion por get para mostrar u ocultar si es latoneria o no o ambos.
if(!isset($_GET["mostrar"])){
    $mostrar_orden = "";
    $mostrar_orden2 = "";
    $mostrar_mecanico = "";
}else{
    $mostrar = $_GET["mostrar"];
    if($mostrar == "mecanica"){ //si es mecanico, NO MOSTRAR LATONERIA
        $mostrar_orden = "AND sa_orden.id_tipo_orden != 7 AND sa_orden.id_tipo_orden != 8";
        $mostrar_orden2 = "sa_orden.id_tipo_orden != 7 AND sa_orden.id_tipo_orden != 8 AND";
        $mostrar_mecanico = "AND tipo_equipo != 'LATONERIA'";
    }elseif($mostrar == "latoneria"){ //si es latoneria, NO MOSTRAR MECANICO
        $mostrar_orden = "AND (sa_orden.id_tipo_orden = 8 OR sa_orden.id_tipo_orden = 7)";
        $mostrar_orden2 = "(sa_orden.id_tipo_orden = 8 OR sa_orden.id_tipo_orden = 7) AND";
        $mostrar_mecanico = "AND tipo_equipo = 'LATONERIA'";
    }else{//si se pone a cambiar la url que cambie a normal
        $mostrar_orden = "";
        $mostrar_orden2 = "";
        $mostrar_mecanico = "";
    }
    
}

if(!isset($_GET["idEmpresa"])){
	
	if(isset($_SESSION['idEmpresaUsuarioSysGts'])){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	}else{
	
		$listadoEmpresas = listadoEmpresas();
		die("No se a ingresado empresa a mostrar: ".$listadoEmpresas."");
	
	}
}else{
	
	$idEmpresa = $_GET["idEmpresa"];
	if ($idEmpresa == NULL || $idEmpresa == "" || !is_numeric($idEmpresa)){
		
		$listadoEmpresas = listadoEmpresas();		
		die("No es correcto la empresa, seleccione: ".$listadoEmpresas."");
	} 
}

$query_seleccion_horario = "SELECT hora_inicio_jornada, hora_fin_jornada, hora_inicio_baja, hora_fin_baja, dias_semana FROM sa_intervalo WHERE fecha_fin IS NULL LIMIT 1";
$datos_horario = mysql_query($query_seleccion_horario,$conex) or die ("Error de Selección 'Horarios': ".mysql_error()."<br>Error Nº: ".mysql_errno());

   while (($row = mysql_fetch_array($datos_horario))!=NULL){
       $hora_inicio_dia = $row['hora_inicio_jornada'];
        $hora_fin_dia = $row['hora_fin_jornada'];
        $hora_inicio_almuerzo = $row['hora_inicio_baja'];
        $hora_fin_almuerzo = $row['hora_fin_baja'];
        $dias_semana = $row['dias_semana'];
   }
   
   include ("controladores/funciones_magnetoplano.php"); // DEBE IR AQUI PORQUE USA LAS VARIABLES DE ARRIBA COMO GLOBALES inicializacion
  

//PAUSADO AUTOMATICO
//Calcular si se paso la hora 18 6pm:
$hora_finalizacion = date('H');

if($hora_finalizacion == "18"){
   $query_buscar_activos = "SELECT id_mp, id_tempario, id_mecanico
                            FROM sa_magnetoplano
                            WHERE activo_mecanico = 1
                            GROUP BY tipo, id_mecanico";
   $seleccionar_activos = mysql_query($query_buscar_activos,$conex) or die ("Error de Selección 'Pausado Automatico': ".mysql_error()."<br>Error Nº: ".mysql_errno());
   $cantidad_activos = mysql_num_rows($seleccionar_activos);
   
   if ($cantidad_activos != 0){
        while($row = mysql_fetch_array($seleccionar_activos)){
            pausarMecanico($row["id_mp"],$row["id_tempario"], $row["id_mecanico"]);
        }
   }
   
}
                        
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <title>Sistema ERP - Servicios - Magnetoplano Edición</title>
            <link type="text/css" rel="stylesheet" media="all" href="css/bootstrap.css" />
            <script type="text/javascript" src="js/jquery-1.9.0.js"></script>
            <!-- IMPORTANTE para la manipulacion de fecha y tiempo en js -->
            <script type="text/javascript" src="js/date.format.js"></script>
            
            
            <script type="text/javascript" src="js/bootstrap.js"></script>
            <script src="jquery-modal-master/jquery.modal.min.js" type="text/javascript" charset="utf-8"></script>
            <link rel="stylesheet" href="jquery-modal-master/jquery.modal.css" type="text/css" media="screen" /> 
            <link rel="stylesheet" href="css/magnetoplano.css" type="text/css" media="screen" /> 
            
            <script src="bwsewell-tablecloth/assets/js/jquery.tablesorter.min.js"></script>
            <script src="bwsewell-tablecloth/assets/js/jquery.tablecloth.js"></script>
            
            <script src="js/funciones.js"></script>
            

    <link href="bwsewell-tablecloth/assets/css/tablecloth.css" rel="stylesheet">
    <link href="bwsewell-tablecloth/assets/css/prettify.css" rel="stylesheet"> 
			
			<?php
                         
				//$xajax->printJavascript("xajax/");
                                $xajax->printJavascript("../controladores/xajax/");//servidor
			   ?>
        </head>
        <body>

            <div class="container" style="width:96%;">
             <div class="titulo_caja" style="width: 100%; height: 100%; border-radius: 7px; background-color: #00B050; white-space:nowrap;">
                <center><h3 style="text-shadow: 2px 2px 2px #0f030f; color: white; "><b>Control de Taller</b> - Magnetoplano (Edición) </h3></center>                     
                </div>
                <div class="pull-right" style="/*font-weight: bold; font-size: 15px;*/">
                <i class="icon-calendar"></i><fecha id="tdFechaSistema"><?php echo date("d-m-Y"); ?></fecha>
                <i class="icon-time"></i><hora id="tdHoraSistema"></hora> 
                </div>
<?php

/*  TABLA MAGNETO PLANO */


$resta = restarHoras($hora_inicio_dia, $hora_fin_dia);
    
$numero_columnas = numeroColumnas($resta,"hora");
$numero_columnas = $numero_columnas; //Numero de columnas 1 ya que no necesitare que se haga dinamica =/
    //// tabla
  // echo "<br>Antes".$hora_inicio_dia;
    $hora_inicio_date = stringTiempo($hora_inicio_dia);
   // echo "<br>Despues".$hora_inicio_date;
  // ($hora_inicio_date);
   
    $mecanicos = mecanicos('solo');
    $auxiliar = true;
    $auxiliar2 = true;
    $mediahora = false;
    $mecanicos2 = array_keys(mecanicos('idmecanico'));
    $aux_mecanicos = 0;
    
    
    
    //Valor intermedio
//    if($mediahora===false){
//        echo "<div class='contenedor-intervalo'>";
//    for($i=1; $i<=$numero_columnas; $i++){
//    $b = date("g:i a",strtotime(" + $i hour -30 minutes",strtotime(stringTiempo($hora_inicio_dia))));
//                   echo "<div class='intervalo'>$b <br>|</div>";
//    }
//    echo "</div>";
//    }
    
   echo "<table border=0 class='table table-condensed table-striped tabla-magnetoplano'>";
    
    
    foreach ($mecanicos as $id => $nombre){
        
        echo "<tr id='$id' style='/*white-space:nowrap;*/'>";
        if ($auxiliar){
            echo "<td style='text-align:center'>Mecánicos</td>"; //<td class='lupa' onClick = 'xajax_verMecanico(".$row['id_empleado'].");'>
        }else{                  //agregado width 300px para el ancho de mecanico
        echo "<td class='lupa' style='width:300px;' onClick = 'xajax_verMecanico(".$id.");'>$nombre</td>"; //$id - $nombre</td>";
        }
            //$a = stringTiempo($hora_inicio_dia,1);
            for($i=1; $i<=$numero_columnas; $i++){
                if ($auxiliar2){
                    echo "<td etiqueta='".date("g:i a",strtotime(stringTiempo($hora_inicio_dia)))."'>";
                        if($id==1){
                            echo date("g:i a",strtotime(stringTiempo($hora_inicio_dia)));//comentada para que no aparezca hora y agregue lo de abajo
                            //echo "Trabajos Asignados";
                        }else{
                        //aqui esta donde deben ir los div, agregado incluyendo el else
                        $id_mecanico = $mecanicos2[$aux_mecanicos];
                        $aux_mecanicos++;
                        
                                                                       
                        $query_seleccion_mp = "SELECT id_mp, sa_magnetoplano.id_orden, id_estado_orden, id_tempario, id_mecanico, sa_magnetoplano.tiempo_inicio, sa_magnetoplano.tiempo_fin, detenida_mecanico, SUM(duracion) as duracion, tipo, activo_mecanico, sa_v_orden_recepcion.placa, sa_v_orden_recepcion.color_estado 
                                                FROM sa_magnetoplano 
                                                LEFT JOIN sa_v_orden_recepcion ON sa_magnetoplano.id_orden = sa_v_orden_recepcion.id_orden 
                                                WHERE id_mecanico = '$id_mecanico' AND sa_magnetoplano.activo = '1' GROUP BY sa_magnetoplano.id_orden, tipo ORDER BY id_mp";
                        
                        $seleccion_mp = mysql_query($query_seleccion_mp, $conex) or die ("Error seleccionando magnetoplano" .mysql_error());
                                            $aux_margen = 0;
                                            while($row = mysql_fetch_array($seleccion_mp)){
                                                $id_mp = $row["id_mp"];
                                                $id_orden = $row["id_orden"];
                                                $orden = "# ".$row["id_orden"]." ";
                                                $placa = $row["placa"]." ";
                                                $color = $row["color_estado"]." ";
                                                $tiempo_inicio = $row["tiempo_inicio"];
                                                $tipo_tiempo_fin = $row["tipo"];//Nuevo agregado para ver el ultimo tiempo fin por tipo
                                                /*********** Buscar ultima fecha ultimo tempario activo ************/
                                                //seleccionando ultima fecha del ultimo tempario agregado para la duracion total:
                        
                                                            $query_seleccion_mp_ult_fecha = "SELECT tiempo_fin
                                                                                    FROM sa_magnetoplano 
                                                                                    WHERE id_mecanico = '$id_mecanico' AND sa_magnetoplano.activo = '1' AND id_orden = '$id_orden' AND tipo = '$tipo_tiempo_fin' ORDER BY id_mp";

                                                            $seleccion_mp_ult_fecha = mysql_query($query_seleccion_mp_ult_fecha,$conex) or die ("Error seleccionando ultima fecha mp tabla ".mysql_error());
                                                            $cantidad_filas_ult_fecha = mysql_num_rows($seleccion_mp_ult_fecha);

                                                            if($cantidad_filas_ult_fecha != 0){
                                                                while ($row2 = mysql_fetch_array($seleccion_mp_ult_fecha)){
                                                                    $ultima_fecha_final2 = $row2["tiempo_fin"];
                                                                }

                                                            }
//                                                            
                                                            if(!isset($ultima_fecha_final2)){
                                                                $ultima_fecha_final = $row["tiempo_fin"];
                                                            }else{
                                                                $ultima_fecha_final = $ultima_fecha_final2;
                                                            }
                                                            /*****************************************************/
                                                
                                                if($row["tipo"]=="0"){
                                                    $tipo_letra = "Diagnóstico";
                                                }elseif($row["tipo"]=="1"){
                                                    $tipo_letra = "Mano de Obra";
                                                }elseif($row["tipo"]=="2"){
                                                    $tipo_letra = "Prueba de Carretera";
                                                }elseif($row["tipo"]=="3"){
                                                    $tipo_letra = "Control de Calidad";
                                                }elseif($row["tipo"]=="4"){
                                                    $tipo_letra = "Lavado";
                                                }
                                                
                                                //Tiempo fin, si existe toma el primero sino el ultimo
                                                
                                                
                                                $simbolo = "";
                                                //icono mas barra con movimiento
                                                if($row["activo_mecanico"] == 3){
                                                    $barra = "noactive";
                                                    $simbolo = "icon-ok";
                                                }else{
                                                    //$barra = "active";
													$barra = "noactive";
                                                }
                                                //solo iconos
                                                if($row["activo_mecanico"] == 1){
                                                    $simbolo = "icon-play";
                                                }elseif($row["activo_mecanico"] == 2){
                                                    $simbolo = "icon-pause";
                                                }elseif($row["activo_mecanico"] == 0){
                                                    $simbolo = "icon-flag";
                                                }
                                                $vencido = "";
                                                $porcentaje = 100;
                                                //calculo del % del div
                                                if (date("Y-m-d H:i:s") > $ultima_fecha_final && $row["activo_mecanico"] != 3 ){
                                                    $porcentaje = 100;
                                                    //Rojo quitado ultima hora
                                                    //$color = "CC3300"; //$color = "990000";
                                                    $vencido = "<i class='icon-time'></i>";
                                                }elseif($row["activo_mecanico"] == 3){//Si esta detenida, se paso o no se paso de la hora fin
                                                    if($ultima_fecha_final > $row["detenida_mecanico"]){
                                                         $porcentaje = 100;
                                                         //$color = "CFCFCF";
                                                    }else{
                                                        $porcentaje = 100;
                                                        //rojo tiempo de operacion vencido:
                                                        //$color = "CC3300"; //$color = "990000";
                                                        //trabajo finalizado:
                                                        //$color = "CFCFCF";
                                                        $vencido = "<i class='icon-time'></i>";
                                                    }
                                                             
                                                } else {
                                                    $valor1 = $row["duracion"];
                                                    $valor2 = diferenciaEntreFechas(date("Y-m-d H:i:s"),$ultima_fecha_final,"MINUTOS",true);
                                                    //echo "$valor1 aaa: $valor2";
                                                    if ($row["duracion"]!= NULL){
                                                    $porcentaje = (($valor1-$valor2)/$valor1 * 100); //variacion positiva
                                                    $porcentaje = ceil($porcentaje);
                                                    }
                                                    
                                                }
                                                
												//si esta detenida y no esta en 1(play) no mostrara, descomentar 3 para que no muestre las finalizadas
												if(/*$row["activo_mecanico"] == 3 ||*/$row["activo_mecanico"] != 1 && ($row["id_estado_orden"] == 9 || $row["id_estado_orden"] == 15 || $row["id_estado_orden"] == 16 || $row["id_estado_orden"] == 17 || $row["id_estado_orden"] == 20 || $row["id_estado_orden"] == 23 )){
													
												}else{//se calcula el margen y se imprime el trabajo
                                                    $margen = $aux_margen*19;
                                                    //tiempos de los ids
                                                    $id_tiempo_inicio = date("d-m-Y g:i a",strtotime($tiempo_inicio));
                                                    $id_tiempo_fin = date("d-m-Y g:i a",strtotime($ultima_fecha_final));
												
                                                     echo "<div id='$id_tiempo_inicio $id_tiempo_fin' class='progress progress-striped $barra nuevo lupa2' onClick='xajax_verMagnetoplano($id_mp);' style='width: 18%; position:absolute; margin-left:$margen%'><div class='bar' style='width: $porcentaje%; color:#000000; background-color:#$color;'> <b style='white-space:nowrap;'>&nbsp;$vencido$orden$placa$tipo_letra<i class='$simbolo'></i></b>   </div></div>";
													 
                                                     $aux_margen++;
												}
                                                
                                                
                                              }
                                           
                        
                        
                        } //hasta aqui incluyendo el else de arriba, agregado div
                    echo "</td>";
                    $auxiliar2 = false;
                }else{    
                    
                    if($mediahora===true){
                        $a=($i-1)*30;
                        echo "<td etiqueta='".date("g:i a",strtotime(" + $a minutes",strtotime(stringTiempo($hora_inicio_dia))))."'>";
                        if($id==1){
                            echo date("g:i a",strtotime(" + $a minutes",strtotime(stringTiempo($hora_inicio_dia))));
                        }
                        echo "</td>";
                    }else{
                        $a=$i-1;
                        echo "<td etiqueta='".date("g:i a",strtotime(" + $a hour",strtotime(stringTiempo($hora_inicio_dia))))."'>";//comentado para que no imprima la hora
                           
                        if ($id==1){
                            echo date("g:i a",strtotime(" + $a hour",strtotime(stringTiempo($hora_inicio_dia)))); //comentado para que no imprima la hora
                            }
                        echo "</td>";
                        //$a = stringTiempo($a,1);
                    }
                }
                
            }
        $auxiliar = false;
        $auxiliar2 = true;
        
        echo "</tr>";
                    }
                   
    echo "</table>";
    
    
    //Menu buscar / filtrar latoneria y mecanica
    if(isset($mostrar)){
        if($mostrar == "latoneria"){
            $seleccionado1 = "";
            $seleccionado2 = "";
            $seleccionado3 = "SELECTED = selected";
        }elseif($mostrar == "mecanica"){
            $seleccionado1 = "";
            $seleccionado3 = "";
            $seleccionado2 = "SELECTED = selected";
        }else{
            $seleccionado1 = "SELECTED = selected";
            $seleccionado2 = "";
            $seleccionado3 = "";
        }
    }else{
        $seleccionado1 = "";
        $seleccionado2 = "";
        $seleccionado3 = "";
    }
     echo"<form method='GET' action='' class='form-search'>
    <select name='mostrar'>
    <option $seleccionado1 value='ambos'>Ambos Mecanica y Latoneria</option>
    <option $seleccionado2 value='mecanica'>MECANICA</option>
    <option $seleccionado3 value='latoneria'>LATONERIA</option>
    </select>
	<input name='idEmpresa' type='hidden' value='$idEmpresa'></input>
    <button type='submit' class='btn btn-custom' value='Mostrar' id='boton_submit'><i class='icon-search icon-white'></i> Mostrar</button>
    &nbsp;<button type='button' class='btn btn-custom' onClick='xajax_verAsistencia();' id='boton_asistencia'><i class='icon-th-list icon-white'></i> Listado de Asistencia</button>
    &nbsp;<button type='button' class='btn btn-custom' onClick='location.reload();' id='boton_asistencia'><i class='icon-retweet icon-white'></i> Recargar</button>
    &nbsp;<button type='button' class='btn btn-custom' onClick='sincronizarTiempo();' id='boton_sincronizartiempo'><i class='icon-time icon-white'></i> Sincronizar</button>
    <div class='btn-group'>
<button class='btn btn-custom dropdown-toggle' data-toggle='dropdown'>
<i class='icon-random icon-white'></i>
Filtro
<span class='caret'></span>
</button>
<ul class='dropdown-menu'>
   <li><a onClick='filtroMagnetoplano(\"hoy\");'><i class='icon-share-alt'></i> Hoy</a></li>
   <li><a onClick='filtroMagnetoplano(&#39;anteriores&#39;);'><i class='icon-arrow-down'></i> Anteriores</a></li>
   <li><a onClick='filtroMagnetoplano(&#39;posteriores&#39;);'><i class='icon-arrow-up'></i> Posteriores</a></li>
   <li><a onClick='filtroMagnetoplano(&#39;todos&#39;);'><i class='icon-align-center'></i> Todos</a></li>
</ul>
</div>
    <minileyenda class='pull-right titulo'>&nbsp;Asignado: <i class='icon-flag'></i>&nbsp;&nbsp; Iniciado: <i class='icon-play'></i>&nbsp;&nbsp; Pausado: <i class='icon-pause'></i>&nbsp;&nbsp; Finalizado: <i class='icon-ok'></i>&nbsp;&nbsp; Retrasado: <i class='icon-time'></i>&nbsp;</minileyenda>
    </form>";
    
    //echo "<button type='button' class='btn btn-custom' onClick='xajax_verAsistencia();' id='boton_asistencia'><i class='icon-search icon-white'></i> Listado de Asistencia</button>";
    
    
    //ORDENES ABIERTAS
    
$query_seleccion_orden_abierta = "SELECT sa_orden.id_orden, sa_estado_orden.color_estado, sa_estado_orden.nombre_estado, sa_v_orden_recepcion.placa, sa_v_orden_recepcion.nombre_unidad_basica, nombre_tipo_orden
FROM sa_orden
LEFT JOIN sa_estado_orden ON sa_estado_orden.id_estado_orden = sa_orden.id_estado_orden 
LEFT JOIN sa_v_orden_recepcion ON sa_orden.id_orden = sa_v_orden_recepcion.id_orden 
WHERE sa_estado_orden.tipo_estado != 'finalizado' AND sa_orden.id_empresa = $idEmpresa $mostrar_orden";

$datos_orden_abierta = mysql_query($query_seleccion_orden_abierta,$conex) or die ("Error de Selección 'Orden Abierta': ".mysql_error()."<br>Error Nº: ".mysql_errno());
$cantidad_filas = mysql_num_rows($datos_orden_abierta);


echo "<div class='span7 caja offset1'><h5 class='titulo_caja'><i class='icon-pencil icon-white'></i> Ordenes Pendientes (".$cantidad_filas.")</h5>";
echo "<div class='caja-dentro'><table class='tabla-ordenes' >";
echo "<thead><tr style='white-space:nowrap;'>";
echo "<th>Nº Orden</th>";
echo "<th>Unidad</th>";
echo "<th>Placa</th>";
echo "<th>Estado</th>";
echo "<th>Tipo</th>";

echo "</tr></thead><tbody>";

while (($row = mysql_fetch_array($datos_orden_abierta))!=NULL){
echo "<tr class='lupa' onClick='xajax_verOrdenAbierta(".$row['id_orden'].")' style='/*white-space:nowrap;*/ background-color: #".$row['color_estado']." '>";
echo "<td># ".$row['id_orden']."</td>";

echo "<td>".utf8_encode($row['nombre_unidad_basica'])."</td>";
echo "<td>".$row['placa']."</td>";

echo "<td >".utf8_encode($row['nombre_estado'])."</td>";
echo "<td>".$row['nombre_tipo_orden']."</td>";
echo "</tr>";

}
echo "</tbody></table></div></div>";

mysql_free_result($datos_orden_abierta);

//ORDENES FINALIZADAS

$query_seleccion_orden_finalizada = "SELECT sa_orden.id_orden, sa_orden.id_estado_orden, sa_estado_orden.color_estado, sa_estado_orden.nombre_estado, sa_estado_orden.tipo_estado, sa_v_orden_recepcion.placa, sa_v_orden_recepcion.nombre_unidad_basica, nombre_tipo_orden
FROM sa_orden 
LEFT JOIN sa_estado_orden ON sa_estado_orden.id_estado_orden = sa_orden.id_estado_orden 
LEFT JOIN sa_v_orden_recepcion ON sa_orden.id_orden = sa_v_orden_recepcion.id_orden
WHERE $mostrar_orden2 (sa_estado_orden.id_estado_orden = 10 OR sa_estado_orden.id_estado_orden = 11 OR sa_estado_orden.id_estado_orden = 12 OR sa_estado_orden.id_estado_orden = 21) AND sa_orden.id_empresa = '".$idEmpresa."'";
// OR sa_estado_orden.id_estado_orden = 13 OR sa_estado_orden.id_estado_orden = 24


$datos_orden_finalizada = mysql_query($query_seleccion_orden_finalizada,$conex) or die ("Error de Selección 'Orden Finalizada': ".mysql_error()."<br>Error Nº: ".mysql_errno());
$cantidad_filas = mysql_num_rows($datos_orden_finalizada);

echo "<div class='span7 caja'><h5 class='titulo_caja'><i class='icon-edit icon-white'></i> Ordenes CdC/PdC/Lavado/Terminado (".$cantidad_filas.")</h5>";
echo "<div class='caja-dentro'><table class='tabla-ordenes'>";
echo "<thead><tr style='white-space:nowrap;'>";
echo "<th>Nº Orden</th>";
echo "<th>Unidad</th>";
echo "<th>Placa</th>";
echo "<th>Estado</th>";
echo "<th>Tipo</th>";

echo "</tr></thead><tbody>";
while (($row = mysql_fetch_array($datos_orden_finalizada))!=NULL){
echo "<tr class='lupa' onClick='xajax_verOrdenAbierta(".$row['id_orden'].",1)' style='/*white-space:nowrap;*/ background-color: #".$row['color_estado']."'>";
echo "<td># ".$row['id_orden']."</td>";
echo "<td>".utf8_encode($row['nombre_unidad_basica'])."</td>";
echo "<td>".$row['placa']."</td>";
echo "<td>".utf8_encode($row['nombre_estado'])."</td>";
echo "<td>".$row['nombre_tipo_orden']."</td>";
echo "</tr>";

}
echo "</tbody></table></div></div>";

mysql_free_result($datos_orden_finalizada);

    
    //LEYENDA2

echo "<div class='span12'><br></div>";
$query_seleccion_leyenda = "SELECT nombre_estado, color_estado, tipo_estado FROM sa_estado_orden WHERE id_estado_orden !=5 AND id_estado_orden !=8 AND id_estado_orden !=14 AND id_estado_orden !=16 ORDER BY tipo_estado";
$datos_leyenda = mysql_query($query_seleccion_leyenda,$conex) or die ("Error de Selección 'Leyenda': ".mysql_error()."<br>Error Nº: ".mysql_errno());

echo "<div class='leyenda caja span14 offset2' id='leyenda' style='height:130px;' ><h5 class='titulo_caja'><i class='icon-tag icon-white'>&nbsp;&nbsp;&nbsp;&nbsp;</i> Leyenda</h5>";

echo "<div class='caja-dentro'><table style='white-space:nowrap;' >";//ver leyenda jquery para mostrar por modal la leyenda completa
while (($row = mysql_fetch_array($datos_leyenda)) != NULL){
    
$nombre_estado = utf8_encode($row['nombre_estado']);
if($nombre_estado == "Control de Calidad"){
    $nombre_estado = $nombre_estado." <b>(CdC)</b>";
}elseif($nombre_estado == "Prueba de Carretera"){
    $nombre_estado = $nombre_estado." <b>(PdC)</b>";
}
//echo "<tr style='cursor:pointer; white-space:nowrap;'>";
echo "<td style='background-color: #".$row['color_estado']."; border-style:solid;border-width:thin; width:30px;'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
echo "<td>".$nombre_estado."</td>";
echo "<td class='hide'>".$row['tipo_estado']."</td>";
if($nombre_estado=="Por Aprobacion de Presupuesto" || $nombre_estado=="Detenida por Herramientas" || $nombre_estado=="Control de Calidad <b>(CdC)</b>" ){
    echo "<tr></tr>";
}
//echo "</tr>";
}

echo "</table></div>";
echo "</div>";
 mysql_free_result($datos_leyenda);
    
 
echo "<div class='span12'><br></div>";

//TRABAJOS FINALIZADOS DEL MP
    $query_seleccion_finalizadas = "SELECT id_estado_orden,tipo_estado, color_estado, sa_magnetoplano.id_orden, sa_magnetoplano.tiempo_inicio, sa_magnetoplano.tiempo_fin, tipo, placa, nombre_empleado
                                    FROM sa_magnetoplano
                                    LEFT JOIN sa_v_orden_recepcion ON sa_magnetoplano.id_orden = sa_v_orden_recepcion.id_orden
                                    LEFT JOIN sa_mecanicos ON sa_magnetoplano.id_mecanico = sa_mecanicos.id_mecanico
                                    LEFT JOIN vw_pg_empleados ON sa_mecanicos.id_empleado = vw_pg_empleados.id_empleado
                                    WHERE DATE(fecha_detenida) = CURDATE() GROUP BY id_orden, tipo, nombre_empleado ORDER BY id_mp";
    $seleccion_finalizadas = mysql_query($query_seleccion_finalizadas, $conex) or die ("Error seleccionando finalizadas MP ".mysql_error());
    $cantidad_filas = mysql_num_rows($seleccion_finalizadas);
    
    echo "<div class='span7 offset1 caja'><h5 class='titulo_caja'><i class='icon-ok icon-white'></i> Trabajos Finalizados hoy Magnetoplano (".$cantidad_filas.")</h5>";
    echo "<div class='caja-dentro'><table class='tabla-ordenes' style='background-color:#F9F9F9'>";
    echo "<thead><tr style='white-space:nowrap;'>";
    echo "<th>Nº Orden</th>";
    echo "<th>Placa</th>";
    echo "<th>Tipo</th>";
    echo "<th>Mecánico</th>";
    echo "</tr></thead><tbody>";
   
    while ($row = mysql_fetch_array($seleccion_finalizadas)){
       if($row["tipo"]=="0"){
            $tipo_letra = "Diagnóstico";
        }elseif($row["tipo"]=="1"){
            $tipo_letra = "Mano de Obra";
        }elseif($row["tipo"]=="2"){
            $tipo_letra = "Prueba de Carretera";
        }elseif($row["tipo"]=="3"){
            $tipo_letra = "Control de Calidad";
        }elseif($row["tipo"]=="4"){
            $tipo_letra = "Lavado";
        }
        
        $id_orden_trabajos_finalizados = $row["id_orden"];
        $color = $row["color_estado"];
        if ($row["id_estado_orden"] == 18 || $row["id_estado_orden"] ==24){
            $ver_orden = "";
        }else{
            $ver_orden = "onClick='xajax_verOrdenAbierta($id_orden_trabajos_finalizados,1);'";
        }
        echo "<tr style='white-space:nowrap; background-color:#$color;' class='lupa' $ver_orden>";
        
         echo "<td># ".$row["id_orden"]."</td>";
         echo "<td>".$row["placa"]."</td>";
         echo "<td>".$tipo_letra."</td>";
         echo "<td>".$row["nombre_empleado"]."</td>";
         
         
        echo "</tr>";
    }
    
echo "</tbody></table></div></div>";


//ORDENES DETENIDAS
$query_seleccion_orden_detenida = "SELECT sa_orden.id_orden, sa_orden.id_estado_orden, sa_estado_orden.color_estado, sa_estado_orden.nombre_estado, sa_estado_orden.tipo_estado, sa_v_orden_recepcion.placa, sa_v_orden_recepcion.nombre_unidad_basica, nombre_tipo_orden
FROM sa_orden 
LEFT JOIN sa_estado_orden ON sa_estado_orden.id_estado_orden = sa_orden.id_estado_orden 
LEFT JOIN sa_v_orden_recepcion ON sa_orden.id_orden = sa_v_orden_recepcion.id_orden
WHERE $mostrar_orden2 (sa_estado_orden.id_estado_orden = 9 OR sa_estado_orden.id_estado_orden = 15 OR sa_estado_orden.id_estado_orden = 16 OR sa_estado_orden.id_estado_orden = 17 OR sa_estado_orden.id_estado_orden = 20 OR sa_estado_orden.id_estado_orden = 23) AND sa_orden.id_empresa = '".$idEmpresa."'";
// OR sa_estado_orden.id_estado_orden = 13 OR sa_estado_orden.id_estado_orden = 24


$datos_orden_detenida = mysql_query($query_seleccion_orden_detenida,$conex) or die ("Error de Selección 'Orden Finalizada': ".mysql_error()."<br>Error Nº: ".mysql_errno());
$cantidad_filas = mysql_num_rows($datos_orden_detenida);

echo "<div class='span7 caja'><h5 class='titulo_caja'><i class='icon-time icon-white'></i> Ordenes Detenidas (".$cantidad_filas.")</h5>";
echo "<div class='caja-dentro'><table class='tabla-ordenes'>";
echo "<thead><tr style='white-space:nowrap;'>";
echo "<th>Nº Orden</th>";
echo "<th>Unidad</th>";
echo "<th>Placa</th>";
echo "<th>Estado</th>";
echo "<th>Tipo</th>";

echo "</tr></thead><tbody>";
while (($row = mysql_fetch_array($datos_orden_detenida))!=NULL){
echo "<tr class='lupa' onClick='xajax_verOrdenAbierta(".$row['id_orden'].",1)' style='/*white-space:nowrap;*/ background-color: #".$row['color_estado']."'>";
echo "<td># ".$row['id_orden']."</td>";
echo "<td>".utf8_encode($row['nombre_unidad_basica'])."</td>";
echo "<td>".$row['placa']."</td>";
echo "<td>".utf8_encode($row['nombre_estado'])."</td>";
echo "<td>".$row['nombre_tipo_orden']."</td>";
echo "</tr>";

}
echo "</tbody></table></div></div>";

mysql_free_result($datos_orden_detenida);



/* MODAL */
echo "<div id='modal'  style='width:auto; max-height:500px; '>";

echo "<div id='modal2' style='overflow-x:hidden; width:auto; max-height:510px;'></div>";

echo "</div>";

?>


                
    
    
    <script type="text/javascript" charset="utf-8">
          function sombrearAlmuerzo(hora_inicio_almuerzo, hora_fin_almuerzo){
                var startDate = new Date("1/1/1900 " + hora_inicio_almuerzo);
                var endDate = new Date("1/1/1900 " + hora_fin_almuerzo);
                
                 a = dateFormat(startDate, 'h:MM tt');
                 b = dateFormat(endDate, 'h:MM tt');
                // alert('inicio1:' + a + 'fin1:' + b );
                if(!$("[etiqueta='"+a+"']").length){
                     startDate = startDate.setMinutes(startDate.getMinutes()+30);
                     //console.debug(startDate);
                     a = dateFormat(startDate, 'h:MM tt');
                 }
                 if (!$("[etiqueta='"+b+"']").length){
                     endDate = endDate.setMinutes(endDate.getMinutes()+30);
                     b = dateFormat(endDate, 'h:MM tt');
                 }                
                                
               $("[etiqueta='"+a+"']").nextUntil("[etiqueta='"+b+"']").andSelf().css("background-color", "#FDF5E6"); //,td:contains('1:30 pm')            .nextUntil("td:contains('1:30 pm')","td")
               $("[etiqueta='"+b+"']").css("background-color", "#FDF5E6");
            }
          
          
             function almuerzo(hora_inicio_dia, hora_fin_dia, hora_inicio_almuerzo, hora_fin_almuerzo){
                var id = 1014;
                   now = new Date();
                   
                   var date = dateFormat(new Date(now), 'dd/mm/yyyy');
                
                var startTime = "19:23:58";
                var endTime = "2010/06/04 07:30:00";

                var startDate = new Date("1/1/1900 " + hora_inicio_almuerzo);
                var endDate = new Date("1/1/1900 " + hora_fin_almuerzo);
                
                 a = dateFormat(startDate, 'h:MM tt');
                 b = dateFormat(endDate, 'h:MM tt');
                 alert('inicio1:' + a + 'fin1:' + b );
                if(!$("[etiqueta='"+a+"']").length){
                     startDate = startDate.setMinutes(startDate.getMinutes()+30);
                     //console.debug(startDate);
                     a = dateFormat(startDate, 'h:MM tt');
                 }
                 if (!$("[etiqueta='"+b+"']").length){
                     endDate = endDate.setMinutes(endDate.getMinutes()+30);
                     b = dateFormat(endDate, 'h:MM tt');
                 }
                                 
                   alert('inicio:' + a + 'fin:' + b );
                
               $("[etiqueta='"+a+"']").nextUntil("[etiqueta='"+b+"']").andSelf().css("background-color", "beige"); //,td:contains('1:30 pm')            .nextUntil("td:contains('1:30 pm')","td")
               $("[etiqueta='"+b+"']").css("background-color", "beige");
              

               
       $("tr:not(:first-child)>[etiqueta='"+ a +"'] ").append("<div class='progress progress-striped active nuevo lupa' style='position:absolute;'><div class='bar' style='width: 60%; color:#000000; /* background-color:#00B050 */   ;'><b>#Orden&nbsp;Placa</b></div></div>");   
                
            var x = $('.nuevo:last').parent().width();
                   // $('.nuevo').width(x + "px");//misma del padre
                    //$('.nuevo:last').width(x/2 + "px"); //mitad del td
                    $('.nuevo').width(x + x/2 + "px").hide().show(1000); //td completo + mitad td
                  //  $('.nuevo:last .bar').width(x + x/2 + "px").hide().show(3000);
        alert (x);
            //$('.nuevo:last').animate({width: "130px"}, { queue: false, duration: 3000 });
            
            //asignando a mecanico
          $("tr#1007>[etiqueta='"+ a +"'] ").append("<div class='progress progress-striped active nuevo lupa' style='position:absolute;'><div class='bar' style='width: 60%; color:#000000;  background-color:#00B050;'><b>#Orden&nbsp;Placa</b></div></div>");    
           var x = $('.nuevo').parent().width();
                   // $('.nuevo').width(x + "px");//misma del padre
                    //$('.nuevo:last').width(x/2 + "px"); //mitad del td
                    $('.nuevo:last').width(x + x/2 + "px").hide().show(1000);  
    
         $("#1007>td:nth-child(2)").append("<div class='progress progress-striped active nuevo lupa2' style='width: 20%; position:absolute; '><div class='bar' style='width: 80%; color:#000000; /* background-color:#00B050  */  ;'><b>#Orden&nbsp;Placa</b></div></div>");
           $("#1007>td:nth-child(2)").append("<div class='progress progress-striped active nuevo lupa2' style='width: 40%; position:absolute;  margin-left:20%;'><div class='bar' style='width: 80%; color:#000000; /* background-color:#00B050 */   ;'><b>#Orden&nbsp;Placa</b></div></div>");
            $("#1007>td:nth-child(2)").append("<div class='progress progress-striped active nuevo lupa2' style='width: 20%; position:absolute; margin-left:60%; '><div class='bar' style='width: 80%; color:#000000; /* background-color:#00B050 */   ;'><b>#Orden&nbsp;Placa</b></div></div>");
            
             $("#1005>td:nth-child(2)").append("<div class='progress progress-striped active nuevo lupa2' style='width: 20%; position:absolute;'><div class='bar' style='width: 10%; color:#000000;  background-color:#00B050;'><b>#Orden&nbsp;Placa</b></div></div>");  
               
            }
            
            
            
      $(document).ready(function() {
          
            mueveReloj();
         //mostrarFecha();
         //setTimeout("recargarVista()", 600000);//5min 300000/1000/60 = 5s  360000 *5 = 5min  1200000
          
$( "#go1" ).click(function(){
$( "#block1" ).animate( { width: "90%" }, { queue: false, duration: 3000 })
.animate({ fontSize: "24px" }, 3000 )
.animate({ borderRightWidth: "15px" }, 3000 );
});
                    $(".input").filter(function(){
                       var v = parseInt($(this).val());
                       return alert(v > 9 && v < 11);
                    });         
	  
        $(".table3").tablecloth({
          theme: "paper",
          striped: true,
          sortable: true,
          condensed: true,
		  clean:true
        });
        
        $(".tabla-ordenes").tablecloth({
         
          sortable: true
          
        });
        //$("table").tablesorter({/* custom tablesorter options */});
      });
      
      
      
     
    </script>
        </body>

</html>
<?php

/*
echo "<script> almuerzo('$hora_inicio_dia','$hora_fin_dia','$hora_inicio_almuerzo','$hora_fin_almuerzo')</script> "; 
echo "<script> //sombrearAlmuerzo('$hora_inicio_almuerzo','$hora_fin_almuerzo')</script> "; 
*/
function listadoEmpresas(){
		$Empresas = mysql_query("SELECT id_empresa, nombre_sucursal FROM sa_v_empresa_sucursal WHERE id_empresa != 100");
		$listadoEmpresas = "<form method='get'>";
		$listadoEmpresas .= "<select name='idEmpresa'>";
	while($row = mysql_fetch_array($Empresas)){
		$listadoEmpresas .= "<option value='".$row['id_empresa']."'>";
		$listadoEmpresas .= $row['nombre_sucursal'];
		$listadoEmpresas .= "</option>";
		}
		$listadoEmpresas .= "</select>";
		$listadoEmpresas .= "<button type='submit'>Enviar</button></form>";
		
		return $listadoEmpresas;
}
?>