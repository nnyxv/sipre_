<?php
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
//echo mysql_num_rows($datos_horario);
   while (($row = mysql_fetch_array($datos_horario))!=NULL){
       $hora_inicio_dia = $row['hora_inicio_jornada'];
        $hora_fin_dia = $row['hora_fin_jornada'];
        $hora_inicio_almuerzo = $row['hora_inicio_baja'];
        $hora_fin_almuerzo = $row['hora_fin_baja'];
        $dias_semana = $row['dias_semana'];
   }

include ("controladores/funciones_magnetoplano.php");
			   
                        
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <title>Sistema ERP - Servicios - Magnetoplano Vista</title>
            
            <link type="text/css" rel="stylesheet" media="all" href="css/bootstrap.css" />
                <link rel="stylesheet" href="css/magnetoplano.css" type="text/css" media="screen" /> 
                
            <script type="text/javascript" src="js/jquery-1.9.0.js"></script>

            <script type="text/javascript" src="js/date.format.js"></script>
            
            
            <script type="text/javascript" src="js/bootstrap.js"></script>
             <script src="js/funciones.js"></script>
            
            <script src="jquery-modal-master/jquery.modal.min.js" type="text/javascript" charset="utf-8"></script>
            <link rel="stylesheet" href="jquery-modal-master/jquery.modal.css" type="text/css" media="screen" /> 

            
			
			
			<script type="text/javascript">
			
			</script>

			
			<!-- TABLAS -->
			<!-- <link href="bwsewell-tablecloth/assets/css/bootstrap.css" rel="stylesheet">
    <link href="bwsewell-tablecloth/assets/css/bootstrap-responsive.css" rel="stylesheet"> -->
    <link href="bwsewell-tablecloth/assets/css/tablecloth.css" rel="stylesheet">
    <link href="bwsewell-tablecloth/assets/css/prettify.css" rel="stylesheet"> 
			
			<?php
				//$xajax->printJavascript("xajax/");
                                $xajax->printJavascript("../controladores/xajax/");//servidor
                                
			   ?>
        </head>
        <body>

<!--            <div class="container" style="width:96%;">-->

 <div class="titulo_caja" style="width: 100%; height: 100%; border-radius: 7px; background-color: #00B050; white-space:nowrap;">               
    <center><h3 style="text-shadow: 2px 2px 2px #0f030f; color: white; "><b>Control de Taller</b> - Magnetoplano (Vista) </h3></center>                     
      </div>
      <div class="pull-right" style="font-weight: bolds; font-size: 18px;">
      <i class="icon-calendar"></i><fecha id="tdFechaSistema"><?php echo date("d-m-Y"); ?></fecha>
      <i class="icon-time"></i><hora id="tdHoraSistema"></hora> 
      </div>

<style>
    .table tr + tr{
        height:50px;      
    }
    .table tr + tr td{
          line-height:12px;
    }
</style>
<?php

/*  TABLA MAGNETO PLANO */

$resta = restarHoras($hora_inicio_dia, $hora_fin_dia);
    
$numero_columnas = numeroColumnas($resta,"hora");
$numero_columnas = $numero_columnas; //Numero de columnas 1 ya que no necesitare que se haga dinamica =/
   
   
 
   
    $mecanicos = mecanicos('solo');
    $auxiliar = true;
    $auxiliar2 = true;
    $mediahora = false;
    $mecanicos2 = array_keys(mecanicos('idmecanico'));
    $aux_mecanicos = 0;
    
   $altura_tabla = 300;
    //style='height:".$altura_tabla."px;

    echo "<table style='/* position:absolute; margin-top:20px;*/ ' border=0 class='table table-condensed table-striped tabla-magnetoplano'>";
    
    
 foreach ($mecanicos as $id => $nombre){
        
        echo "<tr id='$id'>";
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
                            //echo date("g:i a",strtotime(stringTiempo($hora_inicio_dia)));//comentada para que no aparezca hora y agregue lo de abajo
                            echo "Trabajos Asignados";
                        }else{
                        //aqui esta donde deben ir los div, agregado incluyendo el else
                        $id_mecanico = $mecanicos2[$aux_mecanicos];
                        $aux_mecanicos++;
                        
                                                                       
                        $query_seleccion_mp = "SELECT id_mp, sa_magnetoplano.id_orden, id_tempario, id_mecanico, sa_magnetoplano.tiempo_inicio, sa_magnetoplano.tiempo_fin, detenida_mecanico, SUM(duracion) as duracion, tipo, activo_mecanico, sa_v_orden_recepcion.placa, sa_v_orden_recepcion.color_estado 
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
                                                
                                                /*********** Buscar ultima fecha ultimo tempario activo ************/
                                                //seleccionando ultima fecha del ultimo tempario agregado para la duracion total:
                        
                                                            $query_seleccion_mp_ult_fecha = "SELECT tiempo_fin
                                                                                    FROM sa_magnetoplano 
                                                                                    WHERE id_mecanico = '$id_mecanico' AND sa_magnetoplano.activo = '1' AND id_orden = '$id_orden' ORDER BY id_mp";

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
                                                    $barra = "active";
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
                                                    //$color = "CC3300"; //$color = "990000"; 
                                                    $vencido = "<i class='icon-time'></i>";
                                                }elseif($row["activo_mecanico"] == 3){//Si esta detenida, se paso o no se paso de la hora fin
                                                    if($ultima_fecha_final > $row["detenida_mecanico"]){
                                                         $porcentaje = 100;                                                    
                                                    }else{
                                                        $porcentaje = 100;
                                                        //$color = "CC3300"; //$color = "990000"; 
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
                                                
                                                    $tiempo_inicio = date("d-m-Y h:i a",strtotime($row["tiempo_inicio"]));
                                                
                                                    $margen = $aux_margen*21; //width 20% y margen siempre 21. widht y margen = width + 1; dividir
                                                     echo "<div class='progress progress-striped $barra nuevo lupa2' onClick='xajax_verMagnetoplanoNO($id_mp);' style='width: 20%; position:absolute; margin-left:$margen%'><div class='bar' style='width: $porcentaje%; color:#000000; background-color:#$color; vertical-align:middle;'> <b style='white-space:nowrap;'>&nbsp;$vencido$orden$placa$tipo_letra <br><tiempo>$tiempo_inicio</tiempo> <i class='$simbolo'></i></b>   </div></div>";
                                                     $aux_margen++;
                                                
                                                
                                              }
                                           
                        
                        
                        } //hasta aqui incluyendo el else de arriba, agregado div
                    echo "</td>";
                    $auxiliar2 = false;
                }else{    
                    
                    if($mediahora===true){
                        $a=($i-1)*30;
                        echo "<td>".date("g:i a",strtotime(" + $a minutes",strtotime(stringTiempo($hora_inicio_dia))))."</td>";
                    }else{
                        $a=$i-1;
                       // echo "<td etiqueta='".date("g:i a",strtotime(" + $a hour",strtotime(stringTiempo($hora_inicio_dia))))."'>";
                           
                        if ($id==1){
                            //echo date("g:i a",strtotime(" + $a hour",strtotime(stringTiempo($hora_inicio_dia))));
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
                   //echo "<tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr><tr><td>asdf</td></tr>";
    echo "</table>";

    
    
 
    
    
echo "<div id='modal'  style='width:auto; max-height:500px; '>";

echo "<div id='modal2' style='overflow-x:hidden; width:auto; max-height:510px;'></div>";

echo "</div>";




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
    <button type='submit' class='btn btn-custom' value='Mostrar'><i class='icon-search icon-white'></i> Mostrar</button>
    </form>";




echo "<div class='span12'></div>";
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
echo "</div><div class='span12'><br><br></div>";
 mysql_free_result($datos_leyenda);
 

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
echo "<tr class='lupa' onClick='xajax_verOrdenAbiertaNO(".$row['id_orden'].")' style='/*white-space:nowrap;*/ background-color: #".$row['color_estado']." '>";
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
//OR sa_estado_orden.id_estado_orden = 13 OR sa_estado_orden.id_estado_orden = 24


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
echo "<tr class='lupa' onClick='xajax_verOrdenAbiertaNO(".$row['id_orden'].",1)' style='/*white-space:nowrap;*/ background-color: #".$row['color_estado']."'>";
echo "<td># ".$row['id_orden']."</td>";
echo "<td>".utf8_encode($row['nombre_unidad_basica'])."</td>";
echo "<td>".$row['placa']."</td>";
echo "<td>".utf8_encode($row['nombre_estado'])."</td>";
echo "<td>".$row['nombre_tipo_orden']."</td>";
echo "</tr>";

}
echo "</tbody></table></div></div>";

mysql_free_result($datos_orden_finalizada);




?>

    <script src="bwsewell-tablecloth/assets/js/jquery.tablesorter.min.js"></script>
    <script src="bwsewell-tablecloth/assets/js/jquery.tablecloth.js"></script>
    
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
                                
               $("[etiqueta='"+a+"']").nextUntil("[etiqueta='"+b+"']").andSelf().css("background-color", "beige"); //,td:contains('1:30 pm')            .nextUntil("td:contains('1:30 pm')","td")
               $("[etiqueta='"+b+"']").css("background-color", "beige");
            }
          
          
            
      $(document).ready(function() {
          
         mueveReloj();
         //mostrarFecha();
        setTimeout("recargarVista()", 300000);//5min 300000/1000/60 = 5s
         
        var altura = $(".progress").parent().height();
	  $(".progress").css("height",altura);
       // $(".progress").children().css("font-size","14px");
        $(".progress b").css("font-size","16px");
        $(".progress b").css("padding-top","10px");
       // $(".progress b").css({height:altura, "font-size":"14px", "vertical-align":"middle"});
        
        $(".progress").children().children().css("vertical-align","middle");
        $(".bar").css("height","100%");
        
        //$(".tabla-magnetoplano").css("height","100%");
        $(".tabla-magnetoplano").css("font-size","16px");
        $(".tabla-magnetoplano td:first-child").css("vertical-align","middle");
        
        $(".tabla-ordenes").tablecloth({
         
          sortable: true
          
        });
    
      });
      
      
      
     
    </script>
    
         </body>

</html>
<?php

/*echo "<script> sombrearAlmuerzo('$hora_inicio_almuerzo','$hora_fin_almuerzo')</script> "; */

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