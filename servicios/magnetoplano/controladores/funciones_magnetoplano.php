<?php


/* FUNCIONES XAJAX  */
//las funciones comunes estan abajo
//require ('xajax/xajax_core/xajax.inc.php');
@require ('../controladores/xajax/xajax_core/xajax.inc.php'); //servidor
$xajax = new xajax();
$xajax->configure( 'defaultMode', 'synchronous' ); 
$xajax->registerFunction("verMecanico");
$xajax->registerFunction("verOrdenAbierta");
$xajax->registerFunction("verDiagnostico");
$xajax->registerFunction("guardarDiagnostico");
$xajax->registerFunction("guardarPuestoPiramide");
$xajax->registerFunction("estimarDiagnostico");
$xajax->registerFunction("asignarDiagnostico");
$xajax->registerFunction("verTempario");
$xajax->registerFunction("verMagnetoplano");

$xajax->registerFunction("eliminarJefe");
$xajax->registerFunction("finalizarJefe");
$xajax->registerFunction("comenzarMecanico");
$xajax->registerFunction("pausarMecanico");
$xajax->registerFunction("finalizarMecanico");
$xajax->registerFunction("acceso");
$xajax->registerFunction("verEstadoOrden");
$xajax->registerFunction("cambiarEstado");
$xajax->registerFunction("operacionFinalizada");
$xajax->registerFunction("finalizarOrden");      
$xajax->registerFunction("guardarGrupoTempario");
$xajax->registerFunction("verificarAcceso");

$xajax->registerFunction("entradaMecanico");
$xajax->registerFunction("salidaMecanico");
$xajax->registerFunction("verAsistencia");

$xajax->registerFunction("eliminarManodeobra");

$xajax->registerFunction("verFinalizar");
$xajax->registerFunction("mueveReloj");

$xajax->processRequest();

function mueveReloj(){
	$objResponse = new xajaxResponse();

	return $objResponse->assign("tdHoraSistema","innerHTML",date("h:i a"));

}

function verMecanico($idempleado){

	global $conex;
	
	$query_unico_mecanico = "SELECT nombre_cargo, 
									nombre_departamento, 
									cedula, 
									vw_pg_empleados.nombre_empleado AS nombre_mecanico, 
									telefono, 
									celular, 
									email, 
									sa_v_equipos_mecanicos.nombre_equipo, 
									sa_v_equipos_mecanicos.nombre_empleado AS nombre_jefe_taller 
							FROM vw_pg_empleados 
							LEFT JOIN sa_mecanicos ON vw_pg_empleados.id_empleado = sa_mecanicos.id_empleado 
							LEFT JOIN sa_v_equipos_mecanicos ON sa_mecanicos.id_equipo_mecanico = sa_v_equipos_mecanicos.id_equipo_mecanico 
							WHERE vw_pg_empleados.id_empleado = ".$idempleado." LIMIT 1";
	
	$dato_unico_mecanico = mysql_query($query_unico_mecanico,$conex) or die ("Error de Selección 'Ver Unico Mecanico': ".mysql_error()."<br>Error Nº: ".mysql_errno());
	
	$tabla_mecanico = "<table>";
	
	while (($row = mysql_fetch_array($dato_unico_mecanico)) != NULL){
		
		$tabla_mecanico .= "<tr><th colspan='2'><h4>Información de Mecánico</h4></td></tr>";
		$tabla_mecanico .= "<tr><th>Cargo:</th><td>".$row["nombre_cargo"]."</td></tr>";
		$tabla_mecanico .= "<tr><th>Departamento:</th><td>".$row["nombre_departamento"]."</td></tr>";
		$tabla_mecanico .= "<tr><th>Cédula:</th><td>".$row["cedula"]."</td></tr>";
		$tabla_mecanico .= "<tr><th>Nombre:</th><td>".$row["nombre_mecanico"]."</td></tr>";
		$tabla_mecanico .= "<tr><th>Teléfono:</th><td>".$row["telefono"]."</td></tr>";
		$tabla_mecanico .= "<tr><th>Celular:</th><td>".$row["celular"]."</td></tr>";
		$tabla_mecanico .= "<tr><th>Email:</th><td>".$row["email"]."</td></tr>";
		$tabla_mecanico .= "<tr><th colspan='2' style='border-top:solid; border-width:thin;'></th></tr>";
		$tabla_mecanico .= "<tr><th>Equipo:</th><td>".$row["nombre_equipo"]."</td></tr>";
		$tabla_mecanico .= "<tr><th>Jefe Taller:</th><td>".$row["nombre_jefe_taller"]."</td></tr>";
	}
	
	$tabla_mecanico .= "</table>";
	
	$respuesta = new xajaxResponse();
	$respuesta->clear("modal2", "innerHTML");
	$respuesta->assign("modal2","innerHTML",$tabla_mecanico);
	$respuesta->script("$('#modal').modal({escapeClose: true, clickClose: true, spinnerHtml: null})");
	   //.css('width','600')
	return $respuesta;
}
                        
function verOrdenAbierta($id_orden_abierta, $finalizada=0){
	
	global $conex;
	
	$query_unico_orden_abierta = "SELECT nombre_tipo_orden, 
										 fecha_entrada, 
										 nombre_estado, 
										 color_estado, 
										 nom_marca, 
										 nom_modelo, 
										 ano, 
										 color, 
										 nom_transmision, 
										 id_recepcion, 
										 nombre, 
										 apellido, 
										 cedula_cliente, 
										 motivo, 
										 motivo_detalle, 
										 placa, 
										 nombre_unidad_basica, 
										 id_puesto, 
										 id_piramide 
									FROM sa_v_orden_recepcion 
									WHERE id_orden = ".$id_orden_abierta." LIMIT 1";
	$dato_unico_orden_abierta = mysql_query($query_unico_orden_abierta,$conex) or die ("Error de Selección 'Ver Unico Orden Abierta': ".mysql_error()."<br>Error Nº: ".mysql_errno());
	
  
	$tabla_orden_abierta = "";
				
	while (($row = mysql_fetch_array($dato_unico_orden_abierta)) != NULL){
					 
		$fecha_entrada = implode('-',array_reverse(explode('-',$row["fecha_entrada"])));//Cambiar formato año mes dia a dia mes año. Tambien funciona alrevez
		$informacion_auto = $row["nombre_unidad_basica"]." ".$row["nom_marca"]." ".$row["nom_modelo"]." ".$row["ano"]." ".$row["color"]." ".$row["nom_transmision"];
		 
		$id_recepcion=$row['id_recepcion']; //para buscar luego el diagnostico, boton dignostico
		 
		$id_puesto = $row['id_puesto'];

		$query_puesto = "SELECT id_puesto, codigo_puesto FROM sa_puestos WHERE activo = 1";
		$datos_puestos = mysql_query($query_puesto,$conex) or die ("Error de Seleccion 'Datos puestos': ".mysql_error()."<br>Error Nº: ".mysql_errno());

		$listado = "<select id='puesto' class='span2 listamini'>";
		if ($id_puesto == ""){
			$listado .= '<option value="null" selected="selected">-</option>';
		}else{
			$listado .= '<option value="null">-</option>';
		}

		while (($row_puesto = mysql_fetch_array($datos_puestos)) != NULL){

			$listado .= '<option value="'.$row_puesto[0].'"';
		    if ($id_puesto == $row_puesto[0]) {
				$listado .= ' selected="selected"';
		    }
			$listado .= '>'.htmlentities($row_puesto[1]).'</option>';

		}
		$listado .="</select>";
		 
		$piramides=array(1=>"azul",'verde','roja','negra','amarillo');
		$id_piramide = $row["id_piramide"];
		
		$listado_piramide = "<select id='piramide' class='span2 listamini'>";
		if ($id_piramide == ""){
			$listado_piramide .= '<option value="null" selected="selected">-</option>';
		}else{
			$listado_piramide .= '<option value="null">-</option>';
		}
		
		foreach ($piramides as $key => $valor){
			$listado_piramide .= '<option value="'.$key.'"';
			if ($id_piramide == $key) {
			   $listado_piramide .= ' selected="selected"';
			}
			$listado_piramide .= '>'.htmlentities($valor).'</option>';
		}
		$listado_piramide .="</select>";
		 
		$tabla_orden_abierta .= "<div  class='container' style='overflow-x:hidden;'>";
		
		$tabla_orden_abierta .= "<div id='orden'><h4 class='span12 text-center'>Información de Orden Abierta <b># ".$id_orden_abierta."</b></h4>";
		
		$nombre_estado = utf8_encode($row['nombre_estado']);
		$color_estado = $row['color_estado'];
				
		$tabla_orden_abierta .= "<div class='contenedor-orden'><div  class='span4.5 informacion-orden'><h4>Orden</h4><table>";
		$tabla_orden_abierta .= "<tr><th>Tipo Orden:</th><td>".$row['nombre_tipo_orden']."</td></tr>";
		$tabla_orden_abierta .= "<tr><th>Estado:</th><td><color style='background-color: #".$row['color_estado']."'>&nbsp&nbsp&nbsp&nbsp</color> ".utf8_encode($row['nombre_estado'])."</td></tr>";
		$tabla_orden_abierta .= "<tr><th>Piramide:</th><td>".$listado_piramide."</td></tr>";
		$tabla_orden_abierta .= "<tr><th>Puesto:</th><td>".$listado."</td></tr>";
		$tabla_orden_abierta .= "<tr><th>Fecha:</th><td>".$fecha_entrada."</td></tr>";
		
		$valor_puesto= 'document.getElementById("puesto").value';
		//si el getElementById da problemas usar jquery $("#piramide").val()
		$valor_piramide='document.getElementById("piramide").value';
		
		$tabla_orden_abierta .= "<tr><th></th><td><button class='btn  btn-mini btn-custom' onClick='xajax_guardarPuestoPiramide(".$id_orden_abierta.",".$id_recepcion.",".$valor_puesto.",".$valor_piramide.");'><i class='icon-ok icon-white'></i> Cambiar Datos</button></td></tr></table></div>";
		
		$tabla_orden_abierta .= "<div class='span4 informacion-orden'><h4>Cliente</h4><table  >";
		$tabla_orden_abierta .= "<tr><th>Recepción:</th><td>".utf8_encode($row["id_recepcion"])."</td></tr>";
		$tabla_orden_abierta .= "<tr><th>Nombre:</th><td>".utf8_encode($row["nombre"])." ".utf8_encode($row["apellido"])."</td></tr>";
		$tabla_orden_abierta .= "<tr><th>Cédula/Rif:</th><td>".$row["cedula_cliente"]."</td></tr></table></div>";
		
		$tabla_orden_abierta .= "<div class='span4 informacion-orden'><h4>Vehículo</h4><table  >";
		$tabla_orden_abierta .= "<tr><th>Motivo:</th><td>".utf8_encode($row["motivo"])."</td></tr>";
		$tabla_orden_abierta .= "<tr><th>Detalle:</th><td>".utf8_encode($row["motivo_detalle"])."</td></tr>";                                        
		$tabla_orden_abierta .= "<tr><th>Placa:</th><td>".$row["placa"]."</td></tr>";
		$tabla_orden_abierta .= "<tr><th>Unidad:</th><td>".utf8_encode($informacion_auto)."</td></tr></table></div></div>";
                                        
                                        
        $tabla_orden_abierta .= revisionDiagnostico($id_orden_abierta);

		$tabla_orden_abierta .= "<div class='span11'><button class='btn btn-primary' onClick=xajax_verDiagnostico(".$id_recepcion."); ><i class='icon-wrench'></i> Ver Diagnóstico</button></div>";
		$tabla_orden_abierta .= temparios($id_orden_abierta);
                                       
		if($finalizada !=0){
			
			$query_seleccion_fechas = "SELECT tiempo_prueba_carretera, 
											  tiempo_prueba_carretera_fin, 
											  tiempo_control_calidad, 
											  tiempo_control_calidad_fin, 
											  tiempo_lavado, 
											  tiempo_lavado_fin 
										FROM sa_orden 
										WHERE id_orden = ".$id_orden_abierta." LIMIT 1";
			$seleccion_fechas = mysql_query($query_seleccion_fechas,$conex) or die ("Error seleccionando fechas PdC CdC lavado" .  mysql_error());
			
			while ($row = mysql_fetch_array($seleccion_fechas)){
				
				if($row["tiempo_prueba_carretera"] != null){
					$tiempo_prueba_carretera = date("d-m-Y h:i:s a", strtotime($row["tiempo_prueba_carretera"]));
					$estado_inicial1 = 1;
				}else{
					$tiempo_prueba_carretera = $row["tiempo_prueba_carretera"];
					$estado_inicial1 = 0;
				}
				
				if ($row["tiempo_prueba_carretera_fin"] != null){
					$tiempo_prueba_carretera_fin = date("d-m-Y h:i:s a", strtotime($row["tiempo_prueba_carretera_fin"]));
					$estado_final1 = 1;
				}else{
					$tiempo_prueba_carretera_fin = $row["tiempo_prueba_carretera_fin"];
					$estado_final1 = 0;
				}                                                    
				
				if ($row["tiempo_control_calidad"] != null){
					$tiempo_control_calidad = date("d-m-Y h:i:s a", strtotime($row["tiempo_control_calidad"]));
					$estado_inicial2 = 1;
				}else{
					$tiempo_control_calidad = $row["tiempo_control_calidad"];
					$estado_inicial2 = 0;
				}
				if ($row["tiempo_control_calidad_fin"] != null){
					$tiempo_control_calidad_fin = date("d-m-Y h:i:s a", strtotime($row["tiempo_control_calidad_fin"]));
					$estado_final2 = 1;
				}else{
					$tiempo_control_calidad_fin = $row["tiempo_control_calidad_fin"];
					$estado_final2 = 0;
				}
				
				if($row["tiempo_lavado"] !=null){
					$tiempo_lavado = date("d-m-Y h:i:s a", strtotime($row["tiempo_lavado"]));
					$estado_inicial3 = 1;
				}else{
					$tiempo_lavado = $row["tiempo_lavado"];
					$estado_inicial3 = 0;
				}
				
				if($row["tiempo_lavado_fin"] != null){
					$tiempo_lavado_fin = date("d-m-Y h:i:s a", strtotime($row["tiempo_lavado_fin"]));
					$estado_final3 = 1;
				}else{
					 $tiempo_lavado_fin = $row["tiempo_lavado_fin"];
					 $estado_final3 = 0;
				}
			}	
			
			$tabla_orden_abierta .= "<div class='span11' style='margin:0px;width:95%;' > <h4 class='titulo'>Operaciones de Orden Finalizada</h4>";
			$tabla_orden_abierta .= "<table class='table' >";
			$tabla_orden_abierta .= "<tr><th>Prueba de Carretera</th>
										 <td>Inicio: ".$tiempo_prueba_carretera."</td>
										 <td>Fin: ".$tiempo_prueba_carretera_fin."</td>
										 <td>
											<button class='btn btn-custom btn-small' onClick='xajax_operacionFinalizada(".$id_orden_abierta.",1,1,".$estado_inicial1.",".$estado_final1.");'>
												<i class='icon-play icon-white'></i> Iniciar
											</button>&nbsp;&nbsp;
											<button class='btn btn-custom btn-small' onClick='xajax_operacionFinalizada(".$id_orden_abierta.",1,2,".$estado_inicial1.",".$estado_final1.");'>
												<i class='icon-stop icon-white'></i> Terminar
											</button>
										 </td>
									</tr>";
			$tabla_orden_abierta .= "<tr><th>Control de Calidad</th>
										 <td>Inicio: ".$tiempo_control_calidad."</td>
										 <td>Fin: ".$tiempo_control_calidad_fin."</td>
										 <td>
											<button class='btn btn-custom btn-small' onClick='xajax_operacionFinalizada(".$id_orden_abierta.",2,1,".$estado_inicial2.",".$estado_final2.");'>
												<i class='icon-play icon-white'></i> Iniciar
											</button>&nbsp;&nbsp;
											<button class='btn btn-custom btn-small' onClick='xajax_operacionFinalizada(".$id_orden_abierta.",2,2,".$estado_inicial2.",".$estado_final2.");'>
												<i class='icon-stop icon-white'></i> Terminar
											</button>
										 </td>
									</tr>";
			$tabla_orden_abierta .= "<tr><th>Lavado</th>
										 <td>Inicio: ".$tiempo_lavado."</td>
										 <td>Fin: ".$tiempo_lavado_fin."</td>
										 <td>
											<button class='btn btn-custom btn-small' onClick='xajax_operacionFinalizada(".$id_orden_abierta.",3,1,".$estado_inicial3.",".$estado_final3.");'>
												<i class='icon-play icon-white'></i> Iniciar
											</button>&nbsp;&nbsp;
											<button class='btn btn-custom btn-small' onClick='xajax_operacionFinalizada(".$id_orden_abierta.",3,2,".$estado_inicial3.",".$estado_final3.");'>
												<i class='icon-stop icon-white'></i> Terminar
											</button>
										</td>
									</tr>";
			$tabla_orden_abierta .= "</table>";
			$tabla_orden_abierta .= "</div>";
		}
		
		$tabla_orden_abierta .= "<div class='span11'>";//inicio botones finales
		$tabla_orden_abierta .= "<button class='btn btn-primary' onClick='verEstadoOrden(".$id_orden_abierta.",&#39;".$nombre_estado."&#39;,&#39;".$color_estado."&#39;)' ><i class='icon-tags'></i> Cambiar Estado</button>";
		if($finalizada ==0){
			$tabla_orden_abierta .= "&nbsp;&nbsp;&nbsp;<button class='btn btn-custom' onClick='xajax_verFinalizar(".$id_orden_abierta.");' ><i class='icon-check icon-white'></i> Finalizar Orden</button> 
									 <i class='icon-info-sign example5' data-original-title='Finalizar Orden' 
											data-content='Al terminar todas las manos de obra puede 
											proceder a finalizar la orden, el estado de la orden cambiará y 
											se moverá al recuadro derecho para proceder con las operaciones finales 
											(Prueba de Carretera, Control de Calidad, Lavado)' data-toggle='popover' data-placement='top' style='cursor: pointer;'>
									</i>";
		}
		$tabla_orden_abierta .= "</div>"; //fin botones finales
		
		$tabla_orden_abierta .= "</div></div>";
                                        
                                        
                                        
	}//FIN WHILE ORDEN ABIERTA
	
	mysql_free_result($dato_unico_orden_abierta);
	mysql_close($conex);
				
    $respuesta = new xajaxResponse();
	//$respuesta->clear("modal", "innerHTML");
	$respuesta->assign("modal2","innerHTML",$tabla_orden_abierta);
	$respuesta->script("$('#modal').modal({escapeClose: true, clickClose: true, spinnerHtml: true});");
	$respuesta->script("$('#myTab a').click(function (e) {
								e.preventDefault();
								$(this).tab('show');
						});
						alturacajas();
						$('.example').tooltip();  
						$('.example5').popover({trigger:'hover'}).css( 'cursor', 'pointer' );
	"); 
                                
	return $respuesta;

}
                        
                        
function verDiagnostico($id_recepcion){
                            
	global $conex;
	
	$query_diagnostico = "SELECT id_recepcion_falla, 
								 descripcion_falla, 
								 diagnostico_falla, 
								 id_empleado_diagnostico, 
								 DATE_FORMAT(tiempo_diagnostico,'%d-%m-%Y %r') AS tiempo_diagnostico, 
								 nombre_empleado, 
								 cedula
							FROM sa_recepcion_falla 
							LEFT JOIN vw_pg_empleados ON id_empleado_diagnostico = id_empleado 
							WHERE id_recepcion = ".$id_recepcion."";
	
	$datos_diagnostico = mysql_query($query_diagnostico,$conex) or die ("Error de Selección 'Seleccion de diagnostico': ".mysql_error()."<br>Error Nº: ".mysql_errno());
	
	$tabla_diagnostico="<div id=diagnostico class=span11>";
	$tabla_diagnostico .= '<h4 class="text-center">Fallas y Dianósticos</h4>';
		
	$tabla_diagnostico .= '<table class="table table-condensed table-bordered table-striped">';                                
	$tabla_diagnostico .= "<tr><th>Nº</th><th>Fallas / Diagnósticos:</th><th>Última Modificación</th></tr>";   
		
	$aux = 1;
	while (($row = mysql_fetch_array($datos_diagnostico)) != NULL){
		$tabla_diagnostico .= "<tr>";
		
		if ($row['diagnostico_falla']=="") {
			$diagnostico = "";
		}else{
		  $diagnostico = "<br><b>D:</b> <d class=diagnostico_escrito >".$row['diagnostico_falla']."</d>";
		}
		
		
		if ($row['tiempo_diagnostico']==""){
			$tiempo_diagnostico = "";
		}else{
		   $tiempo_diagnostico= $row['cedula']." ".utf8_encode($row['nombre_empleado'])."<br>Fecha: ".$row['tiempo_diagnostico'];
		}
		
		$texto =  'document.getElementById('.$row['id_recepcion_falla'].').value';
		
		$tabla_diagnostico .= "<td><b>".$aux."</b></td>";
		$tabla_diagnostico .= '<td><b>F:</b> '.utf8_encode($row['descripcion_falla']).' '.  utf8_encode(preg_replace('/\s+/', ' ', $diagnostico)).'<div style="display:none" name="id_recepcion">'.$row['id_recepcion_falla'].'</div><div class="area_diagnostico" style="display:none"><textarea id='.$row['id_recepcion_falla'].' name=diagnostico class="span6">'.utf8_encode(preg_replace('/\s+/', ' ',$row['diagnostico_falla'])).'</textarea>';
		//$tabla_diagnostico .= '<br><button class="btn btn-small btn-primary" onClick=xajax_guardarDiagnostico('.$row["id_recepcion_falla"].','.$id_recepcion.','.$texto.');>Guardar</button></div> </td>';
		$tabla_diagnostico .= '<td class="span4">'.$tiempo_diagnostico.'</td>';
		
		$tabla_diagnostico .= "</tr>";  
		
		$aux++;
	}
		
	$todos_id_falla = 'document.getElementsByName('."\'id_recepcion\'".')';
	$todos_textos = 'document.getElementsByName('."\'diagnostico\'".')';
		

	$tabla_diagnostico .= '<tr class="area_diagnostico" style="display:none;">';
		$tabla_diagnostico .= '<td></td>';
		$tabla_diagnostico .= '<td colspan="2">';
			$tabla_diagnostico .= '<button class="btn btn-primary" onClick="guardarTodosDiagnostico('.$id_recepcion.','.$todos_id_falla.','.$todos_textos.');">';
			$tabla_diagnostico .= 'Guardar Diagnóstico';
			$tabla_diagnostico .= '</button>';
		$tabla_diagnostico .= '</td>';
	$tabla_diagnostico .= '</tr>';
	$tabla_diagnostico .= "</table>";
	$tabla_diagnostico .= '<button class="btn btn-primary btn-custom" onClick="regresar();"><i class="icon-arrow-left icon-white"></i> Regresar</button>&nbsp';
	$tabla_diagnostico .= '<button class="btn btn-primary" onClick="editarDiagnostico();"><i class="icon-edit"></i> Editar Diagnóstico</button>';

	$tabla_diagnostico .= "</div>";
	
		
	mysql_free_result($datos_diagnostico);
	mysql_close($conex);
		
	$respuesta = new xajaxResponse();
	//$respuesta->clear("modal", "innerHTML");
	//$respuesta->append("modal3","innerHTML",$tabla_orden_abierta);
	//$respuesta->script("$('.container').append('$tabla_orden_abierta')");
	$respuesta->script("$('#orden').slideUp(function(){ 
				$('#diagnostico').remove();
				$('#modal2').append('".$tabla_diagnostico."'); 
				$('#diagnostico').hide().slideDown(2000); 
			 });");
		 
		 
	// $respuesta->script("$('#diagnostico').hide().slideDown(2000)");
	//$respuesta->script("$('#modal2').modal({escapeClose: true, clickClose: true, spinnerHtml: true, zIndex: 2,})");
		
    return $respuesta;

}
			
function guardarDiagnostico($id_recepcion_falla, $id_recepcion, $comentario){
	
	global $conex;
	
	$comentario =  addslashes(mysql_real_escape_string(utf8_decode($comentario)));
	
	$query_guardar_diagnostico = "UPDATE sa_recepcion_falla SET diagnostico_falla = '".$comentario."' WHERE id_recepcion_falla = '".$id_recepcion_falla."' ";
   
    $actualizacion = mysql_query($query_guardar_diagnostico,$conex) or die ("Error de actualizacion 'Diagnostico': ".mysql_error()."<br>Error Nº: ".mysql_errno());
	   
//  $respuesta = new xajaxResponse();
//  
//  if ($actualizacion){
//  	$respuesta->script("alert('Guardado Correctamente');");
//  } else {
//  	$respuesta->script("alert('Error al guardar, intentelo nuevamente');");
//  }
//  
//  //$respuesta->script("xajax_verDiagnostico('$id_recepcion');");
//  
//	return $respuesta;
				   
}
                        
                        
function guardarPuestoPiramide($id_orden_abierta,$id_recepcion,$valor_puesto,$valor_piramide) {
                             
	global $conex;
	
    $query_guardar_piramide = "UPDATE sa_recepcion SET id_piramide = ".$valor_piramide." WHERE id_recepcion = '".$id_recepcion."' ";
    $actualizacion = mysql_query($query_guardar_piramide,$conex) or die ("Error de actualizacion 'Piramide': ".mysql_error()."<br>Error Nº: ".mysql_errno());
   
    $query_guardar_piramide_puesto = "UPDATE sa_orden SET id_piramide = ".$valor_piramide.", id_puesto = ".$valor_puesto." WHERE id_orden = '".$id_orden_abierta."'";
    $actualizacion2 = mysql_query($query_guardar_piramide_puesto,$conex) or die ("Error de actualizacion 'Piramide2': ".mysql_error()."<br>Erro Nº: ".mysql_errno());
   
   
    $respuesta = new xajaxResponse();
   
    if ($actualizacion && $actualizacion2){
		$respuesta->script("alert('Guardado Correctamente');");
	} else {
		$respuesta->script("alert('Error al guardar, intentelo nuevamente');");
	}
	  return $respuesta;
}
                        
function revisionDiagnostico($id_orden){
	$tiempo="document.getElementsByName('tiempo')[0].value";
	$unidadtiempo="document.getElementById('unidadtiempo').value";
	$calcular_almuerzo = "document.getElementById('almuerzo').checked";
	$id_mecanico = "$('#selectmecanico').val()";
	//$id_mecanico = 'document.getElementById(selectmecanico).val()';
	
	$tabla_diagnostico = '<div class="span11" style="margin:0px;width:95%;"><h4 class="titulo">Control Revisiones de diagnóstico:</h4>';
	$tabla_diagnostico .= '<table class="tabla-revisiondiagnostico"><tr><th>Mecánico asignado</th><td>'.mecanicos("lista").'&nbsp;&nbsp;<i class="icon-info-sign example5" data-placement="right" data-toggle="popover" data-content="Debe seleccionar el mecánico que hará el diagnóstico" data-original-title="Mecánico"></i> </td></tr>';
	$tabla_diagnostico .= '<tr><th>Fecha/Hora inicio*</th><td><input class="input" id="fechahorainicio" name="fechahorainicio" type="text" disabled="disabled" value="">&nbsp;&nbsp;<i class="icon-info-sign example5" data-placement="right" data-toggle="popover" data-content="La hora de inicio será la hora actual en que se asigna el trabajo, de ya haber algún trabajo antes (en cola) el próximo que se agregue comenzará al finalizar el otro." data-original-title="Hora de Inicio"></i> </td><tr/>';
	$tabla_diagnostico .= '<tr><th>Fecha/Hora Estimado</th><td><input class="input" id="fechahoraestimada" name="fechahoraestimada" type="text" disabled="disabled" value=""> &nbsp;&nbsp;<input type="checkbox" name="almuerzo" id="almuerzo"> Calcular Almuerzo';
	$tabla_diagnostico .= '<i class="icon-info-sign example5" data-html="true" data-placement="right" data-toggle="popover" data-content="Si está activa se añadirá 90 minutos a los calculos si entre la hora inicio-fin llega a pasar por el horario de almuerzo. <br><small> A partir de las 12:00pm.</small>" data-original-title="Calcular horas de almuerzo"></i></td></tr>';
	$tabla_diagnostico .= '<tr><th>Tiempo</th><td><input type="text" class="example span1" maxlength="4" name = "tiempo" id="tiempo" data-placement="right" data-toggle="tooltip"  data-original-title="Indique en Minutos o Unidades de Tiempo" onkeypress="return numeros(event)" >';
	$tabla_diagnostico .= '<select id="unidadtiempo" class="listamini span1"><option>min</option><option>ut</option></select>';
	$tabla_diagnostico .= '<button onClick="xajax_estimarDiagnostico('.$tiempo.','.$unidadtiempo.','.$calcular_almuerzo.','.$id_mecanico.');" class="btn-custom btn btn-mini"><i class="icon-time icon-white"></i> Estimar</button>&nbsp;';
	$tabla_diagnostico .= '<button onClick="borrarDiagnostico();" class="btn-custom btn btn-mini"><i class="icon-remove-circle icon-white"></i> Borrar</button>&nbsp;';
	$tabla_diagnostico .= '<button onClick="asignarDiagnostico('.$id_orden.',0);" class="btn-custom btn btn-small" id="botonasignardiagnostico" disabled=disabled><i class="icon-user icon-white"></i> Asignar</button>';
	$tabla_diagnostico .= '<center>Contraseña: <input type="password" id="password_diagnostico"  style="height:10px; margin:0px;"/></center>';
	$tabla_diagnostico .= "</td></table>";                                
	$tabla_diagnostico .= "</div>";
	
	return $tabla_diagnostico;
} 
                        
function temparios($id_orden){
	
	global $conex;
	$query_buscar_temparios = "SELECT id_det_orden_tempario, 
									  codigo_tempario, 
									  descripcion_tempario, 
									  descripcion_modo, 
									  operador, 
									  precio, 
									  costo, 
									  nombre_completo, 
									  estado_tempario, 
									  ut
								FROM sa_v_mo_orden 
								WHERE id_orden = '".$id_orden."' AND estado_tempario != 'DEVUELTO'";
	
   $datos_temparios = mysql_query($query_buscar_temparios,$conex) or die ("Error de seleccion 'Temparios': ".mysql_error()."<br>Error Nº: ".mysql_errno());
   
   $tabla_temparios = "<div class='span11 temparios' style='margin:0px;width:95%;' > <h4 class='titulo'>Posiciones de trabajo sin asignar</h4>";
   $tabla_temparios .= "<table class='table table-condensed table-striped' >";
   $tabla_temparios .= "<thead>";
   $tabla_temparios .= "<tr>";
   //$tabla_temparios .= "<th></th>";
   $tabla_temparios .= "
	   <th></th>
	   <th>Código</th>
	   <th>Descripción</th>
	   <th>Modo</th>
	   <th>Op.</th>
	   <th>Precio</th>
	   <th>Costo</th>
	   <th>Mecánico</th>
	   <th>Estado</th>
	   <th>Acción</th>
	   </tr></thead><tbody>";
	   $iconos = array(
			'PENDIENTE'=>'icon-pause',
			'PROCESO'=>'icon-play',
			'DETENIDO'=>'icon-stop',
			'TERMINADO'=>'icon-ok-sign',
			'DEVUELTO'=>'icon-remove',
			'ASIGNADO'=>'icon-user',
			'FACTURADO'=>'icon-list-alt'
		 );
	   
	while (($row = mysql_fetch_array($datos_temparios)) != NULL){
		$icono = $iconos[$row['estado_tempario']];
		$id_det_orden_tempario = $row["id_det_orden_tempario"];
		
		if ($row['estado_tempario'] !="PENDIENTE"){
			$disabled = "DISABLED = 'disabled'";
		}else{
			$disabled = "";
		}
					
		$ut_original = floor($row["ut"]);
	   
		$tabla_temparios .= "<tr>";
		$tabla_temparios .= "<td><input type='checkbox' ".$disabled." name='box_grupo' class='box_grupo' value='".$id_det_orden_tempario."' ut='".$ut_original."'  /></td>";
		$tabla_temparios .= "<td>".$row['codigo_tempario']."</td>
			<td>".utf8_encode($row['descripcion_tempario'])."</td>
			<td>".$row['descripcion_modo']."<br>".floor($row['ut'])."</td>
			<td>".$row['operador']."</td>
			<td>".$row['precio']."</td>
			<td>".$row['costo']."</td>
			<td>".utf8_encode($row['nombre_completo'])."</td>
			<td style='white-space: nowrap;'><i class='$icono'></i>".$row['estado_tempario']."</td>
			<td> <button class='btn-custom btn btn-small' ".$disabled." onClick='xajax_verTempario(".$id_det_orden_tempario.",".$id_orden.")' style='white-space: nowrap; '><i class='icon-user icon-white'></i> Asignar</button></td>
			</tr>";
	}
	
	$tabla_temparios .= "<tr><td colspan='10'><div class='mecanicos_grupo text-center' style='display:none;'><button class='btn btn-mini btn-custom pull-left' onClick='seleccionarTodos();'><i class='icon-check icon-white'></i> Selccionar Todos</button>&nbsp;&nbsp;".mecanicos('lista')."&nbsp;&nbsp;";
	$tabla_temparios .= "<button class='btn-custom btn btn-small ' style='white-space: nowrap;' id='boton_asignar_grupo' onClick='asignarGrupoTempario(".$id_orden.");'><i class='icon-user icon-white'></i> Asignar Grupo</button>";
	$tabla_temparios .= '<br><pass style="margin-right:85px;">Contraseña: <input type="password" id="password_grupo"  style="height:10px; margin:0px; </pass>"/>';
	$tabla_temparios .= "</div>";                            
	$tabla_temparios .= "<div><button class='btn-custom btn btn-small pull-right' id='boton_ver_grupo' style='white-space: nowrap;' onClick='verGrupoTempario();'><i class='icon-tasks icon-white'></i> Grupo</button></div></td></tr>";
	
	$tabla_temparios .= "</tbody></table></div>";
		 
	return $tabla_temparios;

}
                        
function verTempario($id_tempario,$id_orden){
                            
	global $conex;
	
	$query_tempario = "SELECT id_det_orden_tempario, 
							  codigo_tempario, 
							  descripcion_tempario, 
							  descripcion_modo, 
							  operador, 
							  precio, 
							  costo, 
							  nombre_completo, 
							  estado_tempario, 
							  ut
					    FROM sa_v_mo_orden 
						WHERE id_det_orden_tempario = '".$id_tempario."' ";
	
	$datos_tempario = mysql_query($query_tempario,$conex) or die ("Error de Selección 'Seleccion de tempario unico': ".mysql_error()."<br>Error Nº: ".mysql_errno());
	   
	$tabla_tempario = '<div id="tempario_unico" style="margin:0px;width:95%;width:900px;" > <h4 class="titulo">Asignar mano de obra</h4>';
	$tabla_tempario .= '<table class="table table-condensed table-striped"  >';
	$tabla_tempario .= '<thead>';
	$tabla_tempario .= '<tr>';
	$tabla_tempario .= '<th>Código</th>';
	$tabla_tempario .= '<th>Descripción</th>';
	$tabla_tempario .= '<th>Modo</th>';
	$tabla_tempario .= '<th>Op.</th>';
	$tabla_tempario .= '<th>Precio</th>';
	$tabla_tempario .= '<th>Costo</th>';
	$tabla_tempario .= '<th>Mecánico</th>';
	$tabla_tempario .= '<th>Estado</th>';
	// $tabla_tempario .= '<th>Acción</th>';
	$tabla_tempario .= '</tr></thead><tbody>';
    $iconos = array(
		'PENDIENTE'=>'icon-pause',
		'PROCESO'=>'icon-play',
		'DETENIDO'=>'icon-stop',
		'TERMINADO'=>'icon-ok-sign',
		'DEVUELTO'=>'icon-remove',
		'ASIGNADO'=>'icon-user',
		'FACTURADO'=>'icon-list-alt'
	);
	   
	while (($row = mysql_fetch_array($datos_tempario)) != NULL){
		$icono = $iconos[$row['estado_tempario']];
		$id_det_orden_tempario = $row["id_det_orden_tempario"];
		$ut = floor($row["ut"]);
		//$tabla_tempario .="";
 	    $tabla_tempario .='<tr >';
	    $tabla_tempario .='<td>'.$row["codigo_tempario"].'</td>';
	    $tabla_tempario .='<td>'.utf8_encode($row["descripcion_tempario"]).'</td>';
	    $tabla_tempario .='<td>'.$row["descripcion_modo"].'<br>'.$ut.'</td>';
	    $tabla_tempario .='<td>'.$row["operador"].'</td>';
	    $tabla_tempario .='<td>'.$row["precio"].'</td>';
	    $tabla_tempario .='<td>'.$row["costo"].'</td>';
	    $tabla_tempario .='<td>'.utf8_encode($row["nombre_completo"]).'</td>';
	    $tabla_tempario .='<td style="white-space: nowrap;"><i class="'.$icono.'"></i>'.$row["estado_tempario"].'</td>';	  
	    $tabla_tempario .='</tr>';
	  
	}
	  
	$tabla_tempario .= "</tbody></table>";
                            
	$tiempo='document.getElementsByName(&#39;tiempo&#39;)[0].value';
	$unidadtiempo='document.getElementById(&#39;unidadtiempo&#39;).value';
	$calcular_almuerzo = 'document.getElementById(&#39;almuerzo&#39;).checked';
	$id_mecanico = '$(&#39;#selectmecanico&#39;).val()';
	
	$tabla_tempario .= '<div class="span11" style="margin:0px;width:95%;">';//<h4 class="titulo">Control Revisiones de diagnóstico:</h4>
		$tabla_tempario .= '<table class="tabla-revisiondiagnostico">';
			$tabla_tempario .= '<tr>';
				$tabla_tempario .= '<th style="text-align: right">Mecánico asignado</th>';
				$tabla_tempario .= '<td style="text-align: left">'.mecanicos("lista").'&nbsp;&nbsp; ';
					$tabla_tempario .= '<i class="icon-info-sign example2" data-placement="right" data-toggle="popover" ';
						$tabla_tempario .= 'data-content="Debe seleccionar el mecánico que hará el diagnóstico" ';
						$tabla_tempario .= 'data-original-title="Mecánico">';
					$tabla_tempario .= '</i>';
				$tabla_tempario .= '</td>';
			$tabla_tempario .= '</tr>';
			$tabla_tempario .= '<tr>';
				$tabla_tempario .= '<th style="text-align: right">Fecha/Hora inicio*</th>';
				$tabla_tempario .= '<td style="text-align: left">';
					$tabla_tempario .= '<input class="input" id="fechahorainicio" name="fechahorainicio" type="text" disabled="disabled" value="">&nbsp;&nbsp;';
						$tabla_tempario .= '<i class="icon-info-sign example1" data-placement="right" data-toggle="popover" ';
							$tabla_tempario .= 'data-content="La hora de inicio será la hora actual en que se asigna el ';
											$tabla_tempario .= 'trabajo, de ya haber algún trabajo antes (en cola) el ';
											$tabla_tempario .= 'próximo que se agregue comenzará al finalizar el otro." ';
							$tabla_tempario .= 'data-original-title="Hora de Inicio">';
						$tabla_tempario .= '</i>';
				$tabla_tempario .= '</td>';
			$tabla_tempario .= '<tr/>';
			$tabla_tempario .= '<tr>';
				$tabla_tempario .= '<th style="text-align: right">Fecha/Hora Estimado</th>';
				$tabla_tempario .= '<td style="text-align: left">';
					$tabla_tempario .= '<input class="input" id="fechahoraestimada" name="fechahoraestimada" type="text" disabled="disabled" value=""> &nbsp;&nbsp;';
					$tabla_tempario .= '<input type="checkbox" name="almuerzo" id="almuerzo"> Calcular Almuerzo';
						$tabla_tempario .= '<i class="icon-info-sign example3" data-html="true" data-placement="right" data-toggle="popover" ';
							$tabla_tempario .= 'data-content="Si está activa se añadirá 90 minutos a ';
											$tabla_tempario .= 'los calculos si entre la hora inicio-fin llega a ';
											$tabla_tempario .= 'pasar por el horario de almuerzo. <br><small> A partir ';
											$tabla_tempario .= 'de las 12:00pm.</small>" ';
							$tabla_tempario .= 'data-original-title="Calcular horas de almuerzo">';
						$tabla_tempario .= '</i>';
				$tabla_tempario .= '</td>';
			$tabla_tempario .= '</tr>';
			$tabla_tempario .= '<tr>';
				$tabla_tempario .= '<th style="text-align: right">Tiempo</th>';
				$tabla_tempario .= '<td style="text-align: left">';
					$tabla_tempario .= '<input type="text" disabled="disabled" class="example span1" maxlength="4" ';
							$tabla_tempario .= 'name = "tiempo" id="tiempo" data-placement="right" data-toggle="tooltip"  ';
							$tabla_tempario .= 'data-original-title="Indique en Minutos o Unidades de Tiempo" ';
							$tabla_tempario .= 'onkeypress="return numeros(event)" value="'.$ut.'" ';
					$tabla_tempario .= '>';
					$tabla_tempario .= '<select id="unidadtiempo" disabled="disabled" class="listamini span1">';
						$tabla_tempario .= '<option>min</option>';
						$tabla_tempario .= '<option selected="selected" >ut</option>';
					$tabla_tempario .= '</select>';
					$tabla_tempario .= '<button onClick="xajax_estimarDiagnostico('.$tiempo.','.$unidadtiempo.','.$calcular_almuerzo.','.$id_mecanico.');" class="btn-custom btn btn-mini">';
						$tabla_tempario .= '<i class="icon-time icon-white"></i> Estimar';
					$tabla_tempario .= '</button>&nbsp;';
					$tabla_tempario .= '<button onClick="borrarDiagnostico2();" class="btn-custom btn btn-mini">';
						$tabla_tempario .= '<i class="icon-remove-circle icon-white"></i> Borrar';
					$tabla_tempario .= '</button>&nbsp;';
					$tabla_tempario .= '<button onClick="asignarDiagnostico('.$id_orden.',1,'.$id_det_orden_tempario.');" class="btn-custom btn btn-small" id="botonasignardiagnostico" disabled=disabled>';
						$tabla_tempario .= '<i class="icon-user icon-white"></i> Asignar';
					$tabla_tempario .= '</button>';
					$tabla_tempario .= '<center>Contraseña: <input type="password" id="password_diagnostico"  style="height:10px; margin:0px;"/></center>';
			$tabla_tempario .= '</td></tr></table>';
		
	$tabla_tempario .= '</div>';
    
	$tabla_tempario .= '<div class="span11"><button class="btn btn-primary btn-custom" onClick="regresar2();"><i class="icon-arrow-left icon-white"></i> Regresar</button></div>';
	$tabla_tempario .= '</div>';
		
	mysql_free_result($datos_tempario);
	mysql_close($conex);
		
	$respuesta = new xajaxResponse();
	$respuesta->script("$('#orden').slideUp(function(){ 
			$('#tempario_unico').remove();
			$('#modal2').prepend('".$tabla_tempario."'); 
			$('#tempario_unico').hide().slideDown(2000); 
			$('.example2').hover(function (){
					$('.example2').popover('show');
				}, function(){
					$('.example2').popover('hide');
  			    });
							   
				$('.example3').hover(function (){
					$('.example3').popover('show');
				}, function(){
					$('.example3').popover('hide');
				});
			$('.example1').popover({trigger:'hover'}).css( 'cursor', 'pointer' );
				
			});");
                                 
                                 
	// $respuesta->script("$('#diagnostico').hide().slideDown(2000)");
	//$respuesta->script("$('#modal2').modal({escapeClose: true, clickClose: true, spinnerHtml: true, zIndex: 2,})");
                                
	return $respuesta;
	
}
                                             
                       
function estimarDiagnostico($tiempo, $unidadtiempo, $calcular_almuerzo, $id_mecanico, $id_orden = 0, $id_tempario = 0, $modo = 0){

	global $conex;
	$respuesta = new xajaxResponse();
	
	if(empty($tiempo) || empty($unidadtiempo)){
		$respuesta->script("alert('Error dejo el tiempo vacío')");
	}elseif($id_mecanico =="null"){
		$respuesta->script("alert('Debe seleccionar mecánico')");
	}else{
		if($unidadtiempo=="min"){
			$minutos = $tiempo;
		}else{
			$minutos = $tiempo/100*60;
			$minutos = ceil($minutos);
		}
                                
		$fecha = date("d-m-Y H:i:s");
		
		//COMPROBAR SI YA HAY UN TRABAJO EJECUTANDOSE para agregar la hora de inicio al final de dicho trabajo
		$query_comprobar = "SELECT tiempo_fin FROM sa_magnetoplano WHERE id_mecanico = '".$id_mecanico."' ORDER BY id_mp DESC LIMIT 1";
		$consulta_comprobar = mysql_query($query_comprobar, $conex) or die("Error comprobacion de trabajo".mysql_error());
		
		while($row = mysql_fetch_array($consulta_comprobar)){
			$fecha_ultimo_trabajo = $row["tiempo_fin"];
			$fecha_ultimo_trabajo = date("d-m-Y H:i:s",strtotime($fecha_ultimo_trabajo));
													
			//si fecha_ultimo_trabajo es mayor que date ahora, se añadira el tiempo final como inicio del siguiente trabajo, sino el inicio sera ahora date()
			if (strtotime($fecha_ultimo_trabajo) > strtotime(date("d-m-Y H:i:s"))){
				$fecha = $fecha_ultimo_trabajo;
				//$prueba = "SI ES MAYOR";
			}else{
				$fecha = date("d-m-Y H:i:s");
				//$prueba = "NO ES";
			}
			
		}//FIN WHILE COMPROBACION
			//$respuesta->alert($fecha_ultimo_trabajo. " XX " .date("d-m-Y H:i:s")." => ".$fecha." PRU ".$prueba. date("d-m-Y H:i:s"));
		
		$sumar = date("d-m-Y H:i:s",strtotime("$fecha +$minutos minutes"));

	    global $hora_inicio_almuerzo;
	    global $hora_fin_almuerzo;
	    global $hora_inicio_dia;
	    global $hora_fin_dia;
	    global $dias_semana;
			
	    $comparar1=date("d-m-Y H:i:s",strtotime("$sumar"));                                 
	    $comparar2=date("d-m-Y H:i:s",strtotime("$hora_inicio_almuerzo"));                                      
	    $comparar3=date("d-m-Y H:i:s",strtotime("$hora_fin_almuerzo"));                                  
	    $comparar4=date("d-m-Y H:i:s",strtotime("$fecha"));
	   
	    $inicio_dia_cambio = date("d-m-Y",strtotime("$fecha"));
	    $hora_inicio_dia = date("d-m-Y H:i:s",strtotime("$inicio_dia_cambio $hora_inicio_dia"));
	    $fin_dia_cambio = date("d-m-Y",strtotime("$fecha"));
	    $hora_fin_dia = date("d-m-Y H:i:s",strtotime("$fin_dia_cambio $hora_fin_dia"));
                                   
        //compara si la sumatoria se pasa del la hora fin
                                           
		if((strtotime("$comparar1") > strtotime("$hora_fin_dia")) ){

			$final = restarHoras(date("H:i:s",strtotime("$hora_fin_dia")), date("H:i:s",strtotime("$comparar1")));

			$final2 = diferenciaEntreFechas($hora_fin_dia, $comparar1, "MINUTOS", TRUE);
						
			if (date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime($comparar4))) {
				$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 1 days"));

			}elseif(date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 +1 days"))) {//calculando el de mañana
				$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 1 days"));
				//anidacion de comprobacion 2do nivel
				$hora_fin_dia = date("d-m-Y H:i:s", strtotime("$hora_fin_dia + 1 days"));
				$final2 = diferenciaEntreFechas($hora_fin_dia, $comparar1, "MINUTOS", TRUE);
						   
				if ((strtotime("$comparar1") > strtotime("$hora_fin_dia"))) {//tercer dia

					if (date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 + 1 days"))) {
						$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 2 days"));

					}elseif(date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 + 2 days"))) {
						$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 2 days"));

					}
													
				}
			//finaliza anidacion de comprobacion de 2do nivel							
			}

			$hora_fin_dia = date("d-m-Y H:i:s", strtotime("$hora_fin_dia + 1 days"));
			$final2 = diferenciaEntreFechas($hora_fin_dia, $comparar1, "MINUTOS", TRUE);
			if ((strtotime("$comparar1") > strtotime("$hora_fin_dia"))) {//CUARTO DIA
				
				if (date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 + 2 days"))) {
					$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 3 days"));
					
				} elseif (date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 + 3 days"))) {
					$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 3 days"));
				}

			}
						
			////////dentro QUINTO DIA////////
			$hora_fin_dia = date("d-m-Y H:i:s", strtotime("$hora_fin_dia + 2 days"));
			$final2 = diferenciaEntreFechas($hora_fin_dia, $comparar1, "MINUTOS", TRUE);
			if((strtotime("$comparar1") > strtotime("$hora_fin_dia"))) {//CUARTO DIA
				
				if(date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 + 3 days"))) {
					$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 4 days"));
												
				}elseif(date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 + 4 days"))) {
					$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 4 days"));
								
				}													
			}
			
			//////fin dentro QUINTO DIA//////////
			//sexto dia por si acaso no rinde el quinto, extra valor
			$hora_fin_dia = date("d-m-Y H:i:s", strtotime("$hora_fin_dia + 1 days"));
			$final2 = diferenciaEntreFechas($hora_fin_dia, $comparar1, "MINUTOS", TRUE);
			if ((strtotime("$comparar1") > strtotime("$hora_fin_dia"))) {//CUARTO DIA
				if (date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 + 4 days"))) {
					
					$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 5 days"));
					
				} elseif (date("d-m-Y", strtotime($comparar1)) == date("d-m-Y", strtotime("$comparar4 + 5 days"))) {
					$comparar1 = date("d-m-Y H:i:s", strtotime("$hora_inicio_dia + $final2 minutes 5 days"));
					
				}
										
			}
																												
		}//FIN IF HORA FIN
		
		//Recorremos la cantidad de dias transcurrido desde inicio y fin calculado.
		//Al recorrerlo se le busca el "N" de dia, si es 6 significa sabado y si es 7 significa domingo
		$cantidad_dias = diferenciaEntreFechas($hora_inicio_dia,$comparar1,"DIAS",true);
															
		for($i=1; $i<=$cantidad_dias; $i++){
			$numero_dia = date("N", strtotime("$hora_inicio_dia + $i DAYS"));
			//si es dia 5 suma 1dia por cada sabado y cada domingo
			if($dias_semana == "5") {
				if ($numero_dia == "6") {
					$comparar1 = date("d-m-Y H:i:s", strtotime("$comparar1 + 2 days"));
				} 
			}elseif($dias_semana == "6") {
				if ($numero_dia == "7") {
					$comparar1 = date("d-m-Y H:i:s", strtotime("$comparar1 + 1 days"));
				}
			}
		}
                                            
                                            
        //CALCULAR HORA DE ALMUERZO
		if($calcular_almuerzo === true){
			$almuerzo = 0;
	   
			//primera version completa, se puede hacer alrevez calcular primero el dia, pero el resultado es exactamnte el mismo
		    if(date("H:i:s",strtotime("$comparar4")) >= date("H:i:s",strtotime("$comparar2")) && date("H:i:s",strtotime("$comparar4")) <= date("H:i:s",strtotime("$comparar3"))){//Compruebo que la hora de inicio cae entre el horario de almuerzo
				
				if($cantidad_dias == 0){//sino se pasa de dias, ya le sumo el almuerzo porque esta dentro de interseccion
					$almuerzo++;
				}
				
				if($cantidad_dias == 1){//si a pasado 1 dia, y ya comprobe que hay interseccion le sumo1 y luego busco la hora final si pasa por la hora de almuerzo
					$almuerzo++;
					if(date("H:i:s",strtotime("$comparar1")) >= date("H:i:s",strtotime("$comparar2"))){//como paso 1 dia, solo queda verificar que la hora final paso o no de almuerzo
						$almuerzo++;
					}
				}
				
				if($cantidad_dias > 1){//si pasa de 2 dias y ya se comprueba que hay interseccion se suma1, cantidad de dias pasados -1 que es el final, y final que es si se paso del almuerzo la hora final
					$almuerzo ++;
					if(date("H:i:s",strtotime("$comparar1")) >= date("H:i:s",strtotime("$comparar2"))){//verifico que la hora final paso de almuerzo
						$almuerzo++;
					}
					$almuerzo = $almuerzo+$cantidad_dias-1;
				}
			   
			}elseif(date("H:i:s",strtotime("$comparar4")) < date("H:i:s",strtotime("$comparar2"))){ //compruebo si la hora inicio esta arriba de la de almuerzo
				
				if($cantidad_dias == 0){ //compruebo si no paso de dias
					if(date("H:i:s",strtotime("$comparar1")) >= date("H:i:s",strtotime("$comparar2"))){//como es el mismo dia compruebo si la hora final paso del almuerzo, y como arriba comprobe que la de inicio es menor que almuerzo
						$almuerzo++;
					}
				}
				
				if($cantidad_dias == 1){//si a pasado 1 dia, y como arriba comprobe que la hora de inicio fue en la mañana autmaticamente pasa por la hora de almuerzo
					$almuerzo++;
					if(date("H:i:s",strtotime("$comparar1")) >= date("H:i:s",strtotime("$comparar2"))){//como paso 1 dia, solo queda verificar que la hora final paso o no de almuerzo
						$almuerzo++;
					}
				}
				
				if($cantidad_dias > 1){//si pasa de 2 dias ya se calcula el primer dia ya es +1 porque lo comprobe arriba que es de mañana, cantidad de dias pasados -1 que es el final, y final que es si se paso del almuerzo la hora final
					$almuerzo ++;
					if(date("H:i:s",strtotime("$comparar1")) >= date("H:i:s",strtotime("$comparar2"))){//verifico que la hora final paso de almuerzo
						$almuerzo++;
					}
					$almuerzo = $almuerzo+$cantidad_dias-1;
				}
			   
			}else{//como no ocurrio interseccion 1, y no es antes de la hora de almuerzo 2, signifca que es despues en la tarde 3
				//no tiene == 0 porque si no pasa de dia, y es en la tarde, no tengo que calcular la hora de almuerzo
				if($cantidad_dias == 1){//si a pasado 1 dia, compruebo si la hora que se paso llega al almuerzo
					if(date("H:i:s",strtotime("$comparar1")) >= date("H:i:s",strtotime("$comparar2"))){//como paso 1 dia, solo queda verificar que la hora final paso o no de almuerzo
						$almuerzo++;
					}
				}
				
				if($cantidad_dias > 1){//si pasa de 2 dias, cantidad de dias pasados -1 que es el final, y final que es si se paso del almuerzo la hora final                                                   
					if(date("H:i:s",strtotime("$comparar1")) >= date("H:i:s",strtotime("$comparar2"))){//verifico que la hora final paso de almuerzo
						$almuerzo++;
					}
					$almuerzo = $almuerzo+$cantidad_dias-1;
				}
		   }
		   
		   $horas_almuerzo = $almuerzo*90;
		   $mas_almuerzo = date("d-m-Y H:i:s",strtotime("$comparar1 + $horas_almuerzo minutes"));
		   
		   
		   //SI EL ALMUERZO SE PASA DE LA NOCHE
			if (date("H:i:s", strtotime($mas_almuerzo)) >= date("H:i:s", strtotime($hora_fin_dia))){
			  
				$minutos_demas = restarHoras(date("H:i:s", strtotime("$hora_fin_dia")), date("H:i:s", strtotime("$mas_almuerzo")));
				$minutos_demas = date("i",strtotime($minutos_demas));
				$aux_almuerzo = date("d-m-Y",strtotime($mas_almuerzo));
				$aux_inicio_dia = date("H:i:s",strtotime($hora_inicio_dia));
				$mas_almuerzo = date("d-m-Y H:i:s", strtotime("$aux_almuerzo $aux_inicio_dia + $minutos_demas minutes 1 days"));
			   
			}
			$comparar1 = $mas_almuerzo;
			   
			//$respuesta->script("alert('almuerzo $almuerzo, horas $horas_almuerzo, final $mas_almuerzo')");
												  
		}//FIN IF CALCULAR ALMUERZO
                                            
		$valor_individual = array(
			"inicio" => date("d-m-Y h:i:s a", strtotime($comparar4)),
			"fin" => date("d-m-Y h:i:s a", strtotime($comparar1))
		);
                                
		$respuesta->assign("fechahorainicio", "value", date("d-m-Y h:i:s a",strtotime($comparar4)));
		$respuesta->assign("fechahoraestimada", "value", date("d-m-Y h:i:s a",strtotime($comparar1)));
		$respuesta->assign("tiempo","disabled","true");
		$respuesta->assign("unidadtiempo","disabled","true");
		$respuesta->assign("almuerzo","disabled","true");
		$respuesta->assign("selectmecanico","disabled","true");
                                    
	}//FIN ELSE                            
                            
	if ($modo == 1) {
		$inicio_grupo = $valor_individual["inicio"];
		$fin_grupo = $valor_individual["fin"];
		$respuesta2 = new xajaxResponse();
		$respuesta2->script("grupo_asignarDiagnostico(".$id_orden.",".$id_mecanico.",'".$inicio_grupo."','".$fin_grupo."',1,".$id_tempario.");");
		
		return $respuesta2;
		
	} else {
		return $respuesta;
	}
                            
}
                        
function asignarDiagnostico($id_orden,$id_mecanico,$inicio,$final,$tipo,$id_tempario, $modo = 0){
   
	$respuesta = new xajaxResponse();
	global $conex;
	
	if(empty($id_tempario)){
	   $id_tempario = 0;
    }
   
	$id_empleado = 1105;
	
	$inicio = date("Y-m-d H:i:s",strtotime($inicio));
	$final = date("Y-m-d H:i:s",strtotime($final));
	
	$duracion = diferenciaEntreFechas($inicio,$final,"MINUTOS",true);
	
	//Guardar en Magnetoplano
	$query_guardar_mp = "INSERT INTO sa_magnetoplano (id_orden, id_mecanico, tiempo_inicio, tiempo_fin, duracion, tipo, activo, fecha_creada, id_empleado, id_tempario)
						VALUES ('".$id_orden."','".$id_mecanico."','".$inicio."','".$final."', '".$duracion."' ,'".$tipo."','1', now(),'".$id_empleado."', '".$id_tempario."')";
	$guardar_mp = mysql_query($query_guardar_mp, $conex) or die("Error al guardar en magnetoplano ".  mysql_error());
	
	//Cambiar estado del tempario
	if($id_tempario != 0){
	
		$query_estado_tempario = "UPDATE sa_det_orden_tempario SET estado_tempario = 'ASIGNADO', id_mecanico = ".$id_mecanico." 
								  WHERE id_det_orden_tempario = ".$id_tempario." 
								  AND estado_tempario != 'DEVUELTO'";
		$actualizar_tempario = mysql_query($query_estado_tempario, $conex) or die ("Error al actualizar el estado del tempario".  mysql_error());
		   
		if ($guardar_mp && $actualizar_tempario){
			$respuesta->script("alert('Asignado correctamente')");
			$respuesta->script("location.reload();");
		}else{
			$respuesta->script("alert('No se pudo guardar')");
		}
	
	}else{
	
		$query_actualizar_orden_diagnostico = "UPDATE sa_orden SET id_estado_orden = 2 WHERE id_orden = ".$id_orden."";
		$actualizar_orden_diagnostico = mysql_query($query_actualizar_orden_diagnostico, $conex) or die ("Error al actualizar orden diagnostico ".  mysql_error());
		
			if ($guardar_mp){
				$respuesta->script("alert('Asignado correctamente')");
				$respuesta->script("location.reload();");
			}else{
				$respuesta->script("alert('No se pudo guardar')");
			}
	}                           
	
	if($modo != 0) {
		//si es por grupo que no retorne nada
	} else {
		return $respuesta;
	}
                                    
}
                      
function verMagnetoplano($id_mp){
	
	$respuesta = new xajaxResponse();
	
	global $conex;
																																																																															//Agregado AND id_mecanico para mostrar la hora correcta
	$query_minutos_ult_fecha ="SELECT id_mp, 
									  id_orden, 
									  SUM(duracion) AS duracion_total, 
									  
									  (SELECT tiempo_fin 
											FROM sa_magnetoplano 
											WHERE id_orden = (SELECT id_orden 
																	FROM sa_magnetoplano 
																	WHERE id_mp = '".$id_mp."' LIMIT 1) 
																	AND activo = 1 
																	AND tipo = (SELECT tipo 
																					FROM sa_magnetoplano 
																					WHERE id_mp = '".$id_mp."' LIMIT 1) 
																					AND id_mecanico = (SELECT id_mecanico 
																											FROM sa_magnetoplano 
																											WHERE id_mp = '".$id_mp."' LIMIT 1) 
																											ORDER BY id_mp DESC LIMIT 1) 
									   AS tiempo_fin
								FROM sa_magnetoplano 
								WHERE id_orden = (SELECT id_orden 
													FROM sa_magnetoplano 
													WHERE id_mp = '".$id_mp."' LIMIT 1) 
								AND activo = 1 
								AND tipo = 1 
								GROUP BY id_orden";
	$seleccion_minutos_ult_fecha = mysql_query($query_minutos_ult_fecha,$conex) or die ("Error seleccionando Total y fin ".mysql_error());
	$cantidad_temparios = mysql_num_rows($seleccion_minutos_ult_fecha);
	
	if ($cantidad_temparios != 0){
		while ($row = mysql_fetch_array($seleccion_minutos_ult_fecha)){
			$duracion_total = $row["duracion_total"];
			$tiempo_fin = date("d-m-Y h:i a",strtotime($row["tiempo_fin"]));
		}
	}
	
	$query_select_magnetoplano = "SELECT id_mp, 
										 sa_magnetoplano.id_orden, 
										 id_tempario, 
										 duracion, 
										 sa_magnetoplano.id_mecanico, 
										 sa_magnetoplano.tiempo_inicio, 
										 sa_magnetoplano.tiempo_fin, 
										 sa_magnetoplano.tiempo_real,
										 tipo, 
										 activo_mecanico, 
										 en_registro_placas.placa, 
										 sa_estado_orden.color_estado, 
										 sa_v_mecanicos.nombre_completo, 
										 sa_v_mecanicos.id_equipo_mecanico, 
										 sa_v_equipos_mecanicos.nombre_empleado as nombre_jefe_taller
                                    FROM sa_magnetoplano 
										LEFT JOIN sa_orden ON sa_magnetoplano.id_orden = sa_orden.id_orden 
										LEFT JOIN sa_estado_orden ON sa_orden.id_estado_orden = sa_estado_orden.id_estado_orden
										LEFT JOIN sa_recepcion ON sa_orden.id_recepcion = sa_recepcion.id_recepcion
										LEFT JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita
										LEFT JOIN en_registro_placas ON sa_cita.id_registro_placas = en_registro_placas.id_registro_placas
										LEFT JOIN sa_v_mecanicos ON sa_magnetoplano.id_mecanico = sa_v_mecanicos.id_mecanico
										LEFT JOIN sa_v_equipos_mecanicos ON sa_v_mecanicos.id_equipo_mecanico = sa_v_equipos_mecanicos.id_equipo_mecanico
                                    WHERE id_mp = '".$id_mp."' LIMIT 1";
                            
	$seleccion_magnetoplano = mysql_query($query_select_magnetoplano, $conex) or die ("Error seleccionando mp".mysql_error());
                            
	$tabla_magnetoplano = '<div class="container" style="overflow-x:hidden;"><h4 class="titulo">Trabajo Asignado</h4>';
	$tabla_magnetoplano .= '<table class="table table-condensed table-striped trabajo_asignado"><thead><tr style=\'white-space:nowrap;\'>';
	$tabla_magnetoplano .= '<th>Orden</th>';
	$tabla_magnetoplano .= '<th>Placa</th>';
	$tabla_magnetoplano .= '<th>Tiempo Inicio</th>';
	$tabla_magnetoplano .= '<th>Tiempo Fin</th>';
	//$tabla_magnetoplano .= '<th>Duración</th>';
	$tabla_magnetoplano .= '<th>Tipo</th>';
	$tabla_magnetoplano .= '<th>Mecánico Asignado</th>';
	$tabla_magnetoplano .= '<th>Jefe Taller</th>';
	$tabla_magnetoplano .= '<th>Estado</th>';
	$tabla_magnetoplano .= '</tr></thead><tbody>';
	
	while ($row = mysql_fetch_array($seleccion_magnetoplano)){
		$id_orden = $row["id_orden"];
	    $id_tempario = $row["id_tempario"];
	    $id_mecanico = $row["id_mecanico"];
		
		$tiempo_real = $row["tiempo_real"];
		
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
						
		if($row["activo_mecanico"] == 0){
			$estado_mecanico = "INACTIVO";
		}elseif($row["activo_mecanico"] == 1){
			$estado_mecanico = "ACTIVO";
		}elseif($row["activo_mecanico"] ==2){
			$estado_mecanico = "PAUSADO";
		}elseif ($row["activo_mecanico"] ==3){
			$estado_mecanico = "FINALIZADO";
		}
		
		if(!isset($tiempo_fin)){
			$tiempo_fin = date("d-m-Y h:i a",strtotime($row["tiempo_fin"]));
		}
                                                
		$tabla_magnetoplano .= '<tr>';
		$tabla_magnetoplano .= '<td>#'.$row["id_orden"].'</td>';
		$tabla_magnetoplano .= '<td>'.$row["placa"].'</td>';
		$tabla_magnetoplano .= '<td style=\'white-space:nowrap;\'>'.date("d-m-Y h:i a",strtotime($row["tiempo_inicio"])).'</td>';
		$tabla_magnetoplano .= '<td style=\'white-space:nowrap;\'>'.$tiempo_fin.'</td>';
		//$tabla_magnetoplano .= '<td>'.$row["duracion"].'</td>';
		$tabla_magnetoplano .= '<td><b>'.$tipo_letra.'</b></td>';
		$tabla_magnetoplano .= '<td>'.$row["nombre_completo"].'</td>';
		$tabla_magnetoplano .= '<td>'.$row["nombre_jefe_taller"].'</td>';
		$tabla_magnetoplano .= '<td><b>'.$estado_mecanico.'</b></td>';
		$tabla_magnetoplano .= '<td style="white-space: nowrap;"><button class="btn btn-custom" onclick="xajax_verOrdenAbierta('.$id_orden.');"><i class="icon-eye-open icon-white"></i> Ver Orden</button></td>';
		$tabla_magnetoplano .= '</tr>';
		
		//if($row["tiempo_real"] != ""){
			$tabla_magnetoplano .= "<tr style='white-space:nowrap;'>";
				$tabla_magnetoplano .= "<td></td>";
				$tabla_magnetoplano .= "<td></td>";
				$tabla_magnetoplano .= "<th style='vertical-align:middle;' >Duración (estimado): </th>";
				$tabla_magnetoplano .= "<td style='vertical-align:middle;' >".m2h($duracion_total)."</td>";
				$tabla_magnetoplano .= "<th style='vertical-align:middle;' >Tiempo Real: </th>";
				$tabla_magnetoplano .= "<td style='vertical-align:middle;' >".m2h($row["tiempo_real"])."</td>";
				$tabla_magnetoplano .= "<td></td>";
				$tabla_magnetoplano .= "<td></td>";
				$tabla_magnetoplano .= "<td></td>";
			$tabla_magnetoplano .= "</tr>";
		//}
                            
	}//FIN WHILE
	
    $tabla_magnetoplano .= '</tbody></table>';
                            
    //si es mano de obra                           
	$query_tempario = "SELECT id_det_orden_tempario, 
							  codigo_tempario, 
							  descripcion_tempario, 
							  descripcion_modo, 
							  operador, precio, 
							  costo, 
							  nombre_completo, 
							  estado_tempario, 
							  ut
						FROM sa_v_mo_orden WHERE id_orden = ".$id_orden." ";

	$datos_tempario = mysql_query($query_tempario,$conex) or die ("Error de Selección 'Seleccion de tempario unico': ".mysql_error()."<br>Error Nº: ".mysql_errno());
	$tiene_temparios = mysql_num_rows($datos_tempario);
	
	if($tiene_temparios != 0){
	
		$tabla_magnetoplano .= '<div id="tempario_unico" style="margin:0px;" > <h4 class="titulo">Mano de Obra</h4>';
	    $tabla_magnetoplano .= '<table class="table table-condensed table-striped"  >';
	    $tabla_magnetoplano .= '<thead>';
	    $tabla_magnetoplano .= '<tr>';
	    $tabla_magnetoplano .= '<th>Código</th>';
	    $tabla_magnetoplano .= '<th>Descripción</th>';
	    $tabla_magnetoplano .= '<th>Modo</th>';
	    $tabla_magnetoplano .= '<th>Op.</th>';
	    $tabla_magnetoplano .= '<th>Precio</th>';
	    $tabla_magnetoplano .= '<th>Costo</th>';
	    $tabla_magnetoplano .= '<th>Mecánico</th>';
	    $tabla_magnetoplano .= '<th>Estado</th>';
	    $tabla_magnetoplano .= '<th style="display:none" class="boton_eliminar_manodeobra">Acción</th>';
	    $tabla_magnetoplano .= '</tr></thead><tbody>';
	    
		$iconos = array(
			'PENDIENTE'=>'icon-pause',
			'PROCESO'=>'icon-play',
			'DETENIDO'=>'icon-stop',
			'TERMINADO'=>'icon-ok-sign',
			'DEVUELTO'=>'icon-remove',
			'ASIGNADO'=>'icon-user',
			'FACTURADO'=>'icon-list-alt'
		 );

		while (($row = mysql_fetch_array($datos_tempario)) != NULL){
			$icono = $iconos[$row['estado_tempario']];
			$id_det_orden_tempario = $row["id_det_orden_tempario"];
			$ut = floor($row["ut"]);
			//$tabla_tempario .="";
			   $tabla_magnetoplano .='<tr >';
			   $tabla_magnetoplano .='<td>'.$row["codigo_tempario"].'</td>';
			   $tabla_magnetoplano .='<td>'.utf8_encode($row["descripcion_tempario"]).'</td>';
			   $tabla_magnetoplano .='<td>'.$row["descripcion_modo"].'<br>'.$ut.'</td>';
			   $tabla_magnetoplano .='<td>'.$row["operador"].'</td>';
			   $tabla_magnetoplano .='<td>'.$row["precio"].'</td>';
			   $tabla_magnetoplano .='<td>'.$row["costo"].'</td>';
			   $tabla_magnetoplano .='<td>'.utf8_encode($row["nombre_completo"]).'</td>';
			   $tabla_magnetoplano .='<td style="white-space: nowrap;"><i class="'.$icono.'"></i>'.$row["estado_tempario"].'</td>';                                              
			   $tabla_magnetoplano .='<td style="display:none" class="boton_eliminar_manodeobra"><button class="btn-custom btn" onClick="eliminarManodeobra('.$id_det_orden_tempario.','.$id_orden.','.$id_mp.');">Eliminar</button></td>';
			   $tabla_magnetoplano .='</tr>';
			   
		}
		  
		$tabla_magnetoplano .= "</tbody></table></div>"; 
	
	}//Final mano de obra
	
	//ACCESO
	$password="document.getElementById('password').value";

	$tabla_magnetoplano .= '<div id="acceso" class="text-center"><h4 class="titulo">Acceso</h4>';                            
	$tabla_magnetoplano .= 'Contraseña: <input class="input" style="height:16px; margin:0px;" type="password" name="password" id="password" /> 
										<button class="btn btn-custom" onClick="xajax_acceso('.$id_mecanico.','.$password.');">
											<i class="icon-lock icon-white"></i> 
											Acceder
										</button>';
	$tabla_magnetoplano .= '</div>';

	//CONTROL MECANICO
	$tabla_magnetoplano .= '<div id="control_mecanico" style="display:none;" class="text-center">';
	$tabla_magnetoplano .= '<h4 class="titulo">Control - Mecánico (Trabajo: '.$tipo_letra.')</h4>';
	$tabla_magnetoplano .= '<button class="btn btn-success" onClick="xajax_comenzarMecanico('.$id_mp.','.$id_tempario.','.$id_mecanico.');"><i class="icon-play icon-white"></i> Comenzar</button>&nbsp;';
	$tabla_magnetoplano .= '<button class="btn btn-success" onClick="xajax_pausarMecanico('.$id_mp.','.$id_tempario.','.$id_mecanico.');"><i class="icon-pause icon-white"></i> Pausar</button>&nbsp;&nbsp;';
	$tabla_magnetoplano .= '<button class="btn btn-inverse" onClick="finalizarMecanico('.$id_mp.','.$id_tempario.','.$id_mecanico.');"><i class="icon-stop icon-white"></i> Finalizar</button>&nbsp;';
	$tabla_magnetoplano .= '<i class="icon-info-sign example2" data-html="true" data-placement="right" data-toggle="popover" 
									data-content="<b>Comenzar:</b> Inicializa la hora en que se empezó los trabajos. 
											 <br> <b>Pausar:</b> Detiene momentaneamente los trabajos, para continuar presione comenzar nuevamente. 
											 <br> <b>Finalizar:</b> Finaliza todos los trabajos una vez culminado. 
											 <br><small><b>Nota:</b> Si es Diagnóstico solo se verá afectado el diagnóstico, si es Mano de Obra 
											 se verán afectados todas las manos de obra.</small>" 
									data-original-title="Acciones">
							</i>';
	$tabla_magnetoplano .= '</div>';

	//CONTROL JEFE DE TALLER
	$tabla_magnetoplano .= '<div id="control_jefetaller" style="display:none;" class="text-center">';
	$tabla_magnetoplano .= '<h4 class="titulo">Control - Jefe de Taller (Trabajo: '.$tipo_letra.')</h4>';
	//BOTON QUITADO, no se va a utilizar esa funcion, ahora sera directo por finalizar orden
	//$tabla_magnetoplano .= '<button class="btn btn-success" onClick="xajax_finalizarJefe('.$id_mp.','.$id_tempario.');"><i class="icon-ok-sign icon-white"></i> Finalizar</button>&nbsp;&nbsp;';
	$tabla_magnetoplano .= '<button class="btn btn-inverse" onClick="eliminarJefe('.$id_mp.','.$id_tempario.');"><i class="icon-remove-circle icon-white" ></i> Eliminar</button>&nbsp;';
	$tabla_magnetoplano .= '<i class="icon-info-sign example2" data-html="true" data-placement="right" data-toggle="popover" 
									data-content="<!--<b>Finalizar:</b> Finaliza todos los trabajos por motivo de que ya se 
														realizó con exito y se quitará de la visualización. <br> -->
													<b>Eliminar:</b> Eliminará todos los trabajos del magnetoplano incluyendo 
														horas trabajadas y asignación. 
													<br><small><b>Nota:</b> Si es Diagnóstico solo se verá afectado el diagnóstico, 
													si es Mano de Obra se verán afectados todas las manos de obra.</small>" 
									data-original-title="Acciones">
							</i>';
	$tabla_magnetoplano .= '</div>';
							   
	$tabla_magnetoplano .= '</div>';

	$respuesta->assign("modal2","innerHTML",$tabla_magnetoplano);
	$respuesta->script("$('#modal').modal({escapeClose: true, clickClose: true, spinnerHtml: true});
						$('.example2').popover({trigger:'hover'}).css( 'cursor', 'pointer' );
						$('#password').focus();
				");                           
                            
    return $respuesta; 
	
}

function eliminarJefe($id_mp,$id_tempario){
	
	global $conex;
	$respuesta = new xajaxResponse();
	
	//Comprobacion de estado de orden factura 19/09/2013
	$query_buscar_estado = "SELECT sa_orden.id_estado_orden 
							FROM sa_magnetoplano 
							LEFT JOIN sa_orden ON sa_magnetoplano.id_orden = sa_orden.id_orden
							WHERE id_mp = ".$id_mp."";
	$select_estado_orden = mysql_query($query_buscar_estado,$conex) or die ("Error buscando el estado".mysql_error());
	$cantidad_devuelta = mysql_num_rows($select_estado_orden);
	
	if($cantidad_devuelta){
		while($row= mysql_fetch_array($select_estado_orden)){
			$id_estado_orden = $row["id_estado_orden"];                                    
		}
		
		if($id_estado_orden == "13"){
			$respuesta->script("alert('La orden ya está Terminada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "18"){
			$respuesta->script("alert('La orden ya está Facturada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "21"){
			$respuesta->script("alert('La orden ya está Finalizada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "24"){
			$respuesta->script("alert('La orden ya está Facturada - Vale de salida');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}
	}
	
	
	if($id_tempario !=0){
		$tipo = 1;
	}else{
		$tipo = 0;
	}                       
	
	$query_buscar_orden = "SELECT id_orden FROM sa_magnetoplano WHERE id_mp = ".$id_mp." LIMIT 1";
	$buscar_orden = mysql_query($query_buscar_orden,$conex) or die ("Error buscando orden para eliminar ".mysql_error());
	while($row = mysql_fetch_array($buscar_orden)){
		$id_orden = $row["id_orden"];
	}
	
	$query_eliminar_mp = "DELETE FROM sa_magnetoplano WHERE id_orden = ".$id_orden." AND tipo = ".$tipo."";//antes WHERE id_mp = $id_mp
	$eliminar_mp = mysql_query($query_eliminar_mp, $conex) or die ("Error al eliminar MP".  mysql_error());   
	
	if($id_tempario !=0){
		$query_estado_tempario = "UPDATE sa_det_orden_tempario SET estado_tempario = 'PENDIENTE', id_mecanico = NULL WHERE id_orden = ".$id_orden." AND estado_tempario != 'DEVUELTO'";//antes WHERE id_det_orden_tempario = $id_tempario
		$actualizar_tempario = mysql_query($query_estado_tempario, $conex) or die ("Error al actualizar el estado del tempario PENDIENTE".  mysql_error());
		
		if($actualizar_tempario && $eliminar_mp){
			$respuesta->script("alert('Eliminado Correctamente')");  
			$respuesta->script("location.reload();");
		}else{
			$respuesta->script("alert('No se pudo eliminar')");
		}
	}else{
		if($eliminar_mp){
			$respuesta->script("alert('Eliminado Correctamente')"); 
			$respuesta->script("location.reload();");
		}else{
			$respuesta->script("alert('No se pudo eliminar')");
		}
	}
		
	return $respuesta;
	
}
                        
// ojo Funcion QUITADA ahora se ejecuta una similar al tratar de finalizar la orden
function finalizarJefe($id_mp,$id_tempario){
	
	global $conex;
	$respuesta = new xajaxResponse();
	
	if($id_tempario !=0){
		$tipo = 1;
	}else{
		$tipo = 0;
	}
	
	$query_select_finalizado = "SELECT activo_mecanico, id_orden FROM sa_magnetoplano WHERE id_mp = ".$id_mp." LIMIT 1";
	$select_finalizado = mysql_query($query_select_finalizado,$conex) or die ("Error seleccionando el finalizado".mysql_error());
	
	while($row = mysql_fetch_array($select_finalizado)){
		$activo_mecanico = $row["activo_mecanico"];
		$id_orden = $row["id_orden"];
	}
	
	if ($activo_mecanico == 0){
		$respuesta->script("alert('No puedes finalizar un trabajo que no se comenzó')");
	}elseif($activo_mecanico != 3){
		$respuesta->script("alert('El mecánico debe finalizar el trabajo primero')");
	}else{
		
			$query_finalizar_mp = "UPDATE sa_magnetoplano SET activo = 0, fecha_detenida = now() WHERE activo_mecanico = 3 AND id_orden = ".$id_orden." AND tipo = ".$tipo."";//antes WHERE id_mp
			$finalizar_mp = mysql_query($query_finalizar_mp, $conex) or die ("Error al finalizar MP".  mysql_error());   

			if($id_tempario !=0){
				$query_estado_tempario = "UPDATE sa_det_orden_tempario SET estado_tempario = 'TERMINADO' WHERE id_orden = ".$id_orden." AND estado_tempario != 'DEVUELTO'";
				$actualizar_tempario = mysql_query($query_estado_tempario, $conex) or die ("Error al actualizar el estado del tempario PENDIENTE".  mysql_error());
				if($actualizar_tempario && $finalizar_mp){
					$respuesta->script("alert('Finalizado Correctamente')");   
					$respuesta->script("location.reload();");
				}else{
					$respuesta->script("alert('No se pudo Finalizar')");
				}
			}else{
				if($finalizar_mp){
					$respuesta->script("alert('Finalizado Correctamente')");  
					$respuesta->script("location.reload();");
				}else{
					$respuesta->script("alert('No se pudo Finalizar')");
				}
			}
			 
	}
								
	return $respuesta;                           

}
                        
function comenzarMecanico($id_mp,$id_tempario,$id_mecanico){
	
	global $conex;
	$respuesta = new xajaxResponse();
	
	//Comprobacion de estado de orden factura 19/09/2013
	$query_buscar_estado = "SELECT sa_orden.id_estado_orden 
							FROM sa_magnetoplano 
							LEFT JOIN sa_orden ON sa_magnetoplano.id_orden = sa_orden.id_orden
							WHERE id_mp = ".$id_mp."";
	$select_estado_orden = mysql_query($query_buscar_estado,$conex) or die ("Error buscando el estado".mysql_error());
	$cantidad_devuelta = mysql_num_rows($select_estado_orden);
	
	if($cantidad_devuelta){
		while($row = mysql_fetch_array($select_estado_orden)){
			$id_estado_orden = $row["id_estado_orden"];                                    
		}
		
		if($id_estado_orden == "13"){
			$respuesta->script("alert('La orden ya está Terminada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "18"){
			$respuesta->script("alert('La orden ya está Facturada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "21"){
			$respuesta->script("alert('La orden ya está Finalizada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "24"){
			$respuesta->script("alert('La orden ya está Facturada - Vale de salida');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}
	}
	
	//seleccion si es diagnostico o tempario
	
	if ($id_tempario !=0){
		$tipo = 1;
	}else{
		$tipo = 0;
	}
	
	$query_buscar_activo = "SELECT activo_mecanico FROM sa_magnetoplano WHERE activo = 1 AND activo_mecanico = 1 AND id_mecanico = ".$id_mecanico."";
	$select_activo = mysql_query($query_buscar_activo,$conex) or die ("Error buscando el activo".mysql_error());
	$dato_devuelto = mysql_num_rows($select_activo);
	
	if($dato_devuelto){//Si hay alguno activo (PLAY) no puede comenzar otro trabajo hasta pausar o finalizar
		$respuesta->script("alert('No puedes comenzar otro trabajo hasta terminar o pausar el actualmente activo')");
	}else{
	
		$query_select_estado = "SELECT activo_mecanico, id_orden FROM sa_magnetoplano WHERE id_mp=$id_mp LIMIT 1";
		$select_estado = mysql_query($query_select_estado,$conex) or die ("Error seleccionando el finalizado".mysql_error());
		while($row = mysql_fetch_array($select_estado)){
			$id_orden = $row["id_orden"];
			$activo_mecanico = $row["activo_mecanico"];
		}
			if($id_tempario !=0){
				//Si tiene mano de obra asignada nueva y no se a activado, si tiene se cambia $activo_mecanico = "lo que sea" para que entre a funcionar el ELSE y active todo otra vez
				$query_select_manodeobra_asignada = "SELECT * FROM sa_magnetoplano WHERE id_orden = ".$id_orden." AND tipo = 1 AND id_mecanico = ".$id_mecanico." AND activo_mecanico = 0";
				$select_manodeobra_asignada = mysql_query($query_select_manodeobra_asignada, $conex) or die ("Error al finalizar MP". mysql_error());
				$cantidad_manodeobra_asiganada = mysql_num_rows($select_manodeobra_asignada);
				if ($cantidad_manodeobra_asiganada != 0){
					$activo_mecanico = "lo que sea";
				}
			}
		
		if ($activo_mecanico == 3){     
			$respuesta->script("alert('No puedes volver a comenzar un trabajo finalizado')");
		}else{                                                                                               

			$query_activar_mp = "UPDATE sa_magnetoplano SET activo_mecanico = 1, iniciada_mecanico = now() WHERE id_orden = ".$id_orden." AND tipo = ".$tipo." AND id_mecanico = ".$id_mecanico.""; //cambiando antes WHERE id_mp = $id_mp"
			$activar_mp = mysql_query($query_activar_mp, $conex) or die ("Error al finalizar MP".  mysql_error());   

			if($id_tempario !=0){
				
				$query_actualizar_estado_orden = "UPDATE sa_orden SET id_estado_orden = 6 WHERE id_orden = ".$id_orden."";
				$actualizar_estado_orden = mysql_query($query_actualizar_estado_orden,$conex);
				
				$query_estado_tempario = "UPDATE sa_det_orden_tempario SET estado_tempario = 'PROCESO' WHERE id_orden = ".$id_orden." AND id_mecanico = ".$id_mecanico." AND estado_tempario != 'DEVUELTO'"; //cambiado antes: WHERE id_det_orden_tempario = $id_tempario
				$actualizar_tempario = mysql_query($query_estado_tempario, $conex) or die ("Error al actualizar el estado del tempario PROCESO".  mysql_error());
				
				if($actualizar_tempario && $activar_mp){
					$respuesta->script("alert('Activado Correctamente')");   
					$respuesta->script("location.reload();");
				}else{
					$respuesta->script("alert('No se pudo activar')");
				}
				
			}else{
				
				if($activar_mp){
					$respuesta->script("alert('Activado Correctamente')");  
					$respuesta->script("location.reload();");
				}else{
					$respuesta->script("alert('No se pudo activar')");
				}
				
			}
		}
		
	}
								
	return $respuesta; 
	
}
                        
function pausarMecanico($id_mp,$id_tempario,$id_mecanico){
	global $conex;
	$respuesta = new xajaxResponse();
	
	//Comprobacion de estado de orden factura 19/09/2013
	$query_buscar_estado = "SELECT sa_orden.id_estado_orden 
							FROM sa_magnetoplano 
							LEFT JOIN sa_orden ON sa_magnetoplano.id_orden = sa_orden.id_orden
							WHERE id_mp = ".$id_mp."";
	$select_estado_orden = mysql_query($query_buscar_estado,$conex) or die ("Error buscando el estado".mysql_error());
	$cantidad_devuelta = mysql_num_rows($select_estado_orden);
	
	if($cantidad_devuelta){
		while($row= mysql_fetch_array($select_estado_orden)){
			$id_estado_orden = $row["id_estado_orden"];                                    
		}
		
		if($id_estado_orden == "13"){
			$respuesta->script("alert('La orden ya está Terminada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "18"){
			$respuesta->script("alert('La orden ya está Facturada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "21"){
			$respuesta->script("alert('La orden ya está Finalizada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "24"){
			$respuesta->script("alert('La orden ya está Facturada - Vale de salida');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}
	}
	
	
	//seleccion si es diagnostico o tempario                          
	if ($id_tempario !=0){
		$tipo = 1;
	}else{
		$tipo = 0;
	}
	
	$query_buscar_play = "SELECT activo_mecanico FROM sa_magnetoplano WHERE activo = 1 AND activo_mecanico = 1 AND id_mp = ".$id_mp." LIMIT 1";
	$select_play = mysql_query($query_buscar_play,$conex) or die ("Error buscando el activo (play)".mysql_error());
	$dato_devuelto = mysql_num_rows($select_play);
	
	if($dato_devuelto){//Si esta activo puedo pausar, de lo contrario no funcionara
		
		$query_select_estado = "SELECT id_orden,activo_mecanico, iniciada_mecanico, tiempo_real FROM sa_magnetoplano WHERE id_mp = ".$id_mp." LIMIT 1";
		$select_estado = mysql_query($query_select_estado,$conex) or die ("Error seleccionando el finalizado".mysql_error());
		
		while($row = mysql_fetch_array($select_estado)){
			$activo_mecanico = $row["activo_mecanico"];
			$iniciada_mecanico = $row["iniciada_mecanico"];                                                
			$tiempo_real = $row["tiempo_real"];   
			$id_orden = $row["id_orden"];
		}
		
		$minutos_transcurridos_pausa = diferenciaEntreFechas($iniciada_mecanico,date("Y-m-d H:i:s"),"MINUTOS",true);
		//INSERCION EN sa_magnetoplano_diario para saber horas trabajadas por dia
		$query_insertar_diario = "INSERT INTO sa_magnetoplano_diario (id_orden, id_tempario, id_mecanico, id_tipo, tiempo_transcurrido) 
										VALUES (".$id_orden.", ".$id_tempario.", ".$id_mecanico.", ".$tipo.", ".$minutos_transcurridos_pausa.")";
		$insertar_diario = mysql_query($query_insertar_diario, $conex) or die ("Error insertando en el diario pausar". mysql_error());
				
		if($tiempo_real !=NULL){
			$minutos = $tiempo_real + $minutos_transcurridos_pausa;
		}else{
			$minutos = $minutos_transcurridos_pausa;
		}                                            

		$query_pausar_mp = "UPDATE sa_magnetoplano SET activo_mecanico = 2, pausada_mecanico = now(), tiempo_real = ".$minutos." WHERE id_orden = ".$id_orden." AND tipo = ".$tipo." AND id_mecanico = ".$id_mecanico."";//cambiado antes WHERE id_mp = $id_mp
		$pausar_mp = mysql_query($query_pausar_mp, $conex) or die ("Error al pausar MP".  mysql_error());   

		if($id_tempario !=0){
			$query_estado_tempario = "UPDATE sa_det_orden_tempario SET estado_tempario = 'DETENIDO' WHERE id_orden = ".$id_orden." AND id_mecanico = ".$id_mecanico." AND estado_tempario != 'DEVUELTO'";//cambiado antes WHERE id_det_orden_tempario = $id_tempario
			$actualizar_tempario = mysql_query($query_estado_tempario, $conex) or die ("Error al actualizar el estado del tempario DETENIDO".  mysql_error());
			if($actualizar_tempario && $pausar_mp){
				$respuesta->script("alert('Pausado Correctamente')");   
				$respuesta->script("location.reload();");
			}else{
				$respuesta->script("alert('No se pudo pausar')");
			}
		}else{
			if($pausar_mp){
				$respuesta->script("alert('Pausado Correctamente')");  
				$respuesta->script("location.reload();");
			}else{
				$respuesta->script("alert('No se pudo pausar')");
			}
		}
											 
	}else{
		$respuesta->script("alert('No puedes pausar un trabajo que no está activo')");                                           		
	}
								
	return $respuesta; 
	
}
                        
function finalizarMecanico($id_mp,$id_tempario,$id_mecanico){
	
	global $conex;
	$respuesta = new xajaxResponse();
	
	//Comprobacion de estado de orden factura 19/09/2013
	$query_buscar_estado = "SELECT sa_orden.id_estado_orden 
							FROM sa_magnetoplano 
							LEFT JOIN sa_orden ON sa_magnetoplano.id_orden = sa_orden.id_orden
							WHERE id_mp = ".$id_mp."";
	$select_estado_orden = mysql_query($query_buscar_estado,$conex) or die ("Error buscando el estado".mysql_error());
	$cantidad_devuelta = mysql_num_rows($select_estado_orden);
	
	if($cantidad_devuelta){
		while($row= mysql_fetch_array($select_estado_orden)){
			$id_estado_orden = $row["id_estado_orden"];                                    
		}
		
		if($id_estado_orden == "13"){
			$respuesta->script("alert('La orden ya está Terminada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "18"){
			$respuesta->script("alert('La orden ya está Facturada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "21"){
			$respuesta->script("alert('La orden ya está Finalizada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "24"){
			$respuesta->script("alert('La orden ya está Facturada - Vale de salida');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}
	}
	
	//seleccion si es diagnostico o tempario                            
	if ($id_tempario !=0){
		$tipo = 1;
	}else{
		$tipo = 0;
	}
	
	$query_select_estado = "SELECT id_orden, activo_mecanico, iniciada_mecanico, pausada_mecanico, tiempo_real FROM sa_magnetoplano WHERE id_mp = ".$id_mp." LIMIT 1";
	$select_estado = mysql_query($query_select_estado,$conex) or die ("Error seleccionando el finalizado".mysql_error());
	
	while($row = mysql_fetch_array($select_estado)){
		$activo_mecanico = $row["activo_mecanico"];
		$iniciada_mecanico = $row["iniciada_mecanico"];                                                
		//$pausada_mecanico = $row["pausada_mecanico"];                                                
		$tiempo_real = $row["tiempo_real"];     
		$id_orden = $row["id_orden"];
	}
		
	if($activo_mecanico == 0){
		$respuesta->script("alert('No puedes finalizar un trabajo que no se comenzó')");
	}elseif($activo_mecanico == 3){
		$respuesta->script("alert('No puedes finalizar un trabajo dos veces, ya está finalizado')");
	}elseif ($activo_mecanico == 2){//SI ESTA PAUSADO Y LE DA FINALIZAR
				
		$query_actualizar_orden_diagnostico1 = "UPDATE sa_orden SET id_estado_orden = 22 WHERE id_orden = ".$id_orden."";
		$actualizar_orden_diagnostico1 = mysql_query($query_actualizar_orden_diagnostico1, $conex) or die ("Error al actualizar orden diagnostico1 pausar->detener ".  mysql_error());
		
				
		$query_detener1_mp = "UPDATE sa_magnetoplano SET activo_mecanico = 3, detenida_mecanico = now() WHERE id_orden = $id_orden AND tipo = $tipo AND id_mecanico = $id_mecanico";//Antes WHERE id_mp = $id_mp
		$detener1_mp = mysql_query($query_detener1_mp, $conex) or die ("Error al Detener 1 pausar->detener".  mysql_error());  
		
		if($detener1_mp){
			$respuesta->script("alert('Finalizado correctamente')");
			$respuesta->script("location.reload();");
		}else{
			$respuesta->script("alert('No se pudo finalizar')");
		}
					
		if($id_tempario == 0){//Si es 0 es diagnostico se pone la orden 22 = diagnostico finalizado

			$query_actualizar_orden_diagnostico2 = "UPDATE sa_orden SET id_estado_orden = 22 WHERE id_orden = ".$id_orden."";
			$actualizar_orden_diagnostico2 = mysql_query($query_actualizar_orden_diagnostico2, $conex) or die ("Error al actualizar orden diagnostico pausar->finalizar ".  mysql_error());

		}else{//Si no es 0, significa que es un tempario se pone la orden  21 = trabajo finalizado y se termina las manos de obras

			//finalizar temparios, finaliza todos los temparios
			$query_estado_tempario = "UPDATE sa_det_orden_tempario SET estado_tempario = 'TERMINADO' WHERE id_mecanico = ".$id_mecanico." AND id_orden = ".$id_orden." AND estado_tempario != 'DEVUELTO'";
			$actualizar_tempario = mysql_query($query_estado_tempario, $conex) or die ("Error al actualizar el estado del tempario Finzalizar mecanico mano de obra pausar->finalizar".  mysql_error());
			
			//SI LA ORDEN LA FINALIZA EL MECANICO YA NO HABRIA RAZON PARA EL BOTON "FINALIZAR ORDEN JEFE TALLER"
			//$query_actualizar_orden_diagnostico3 = "UPDATE sa_orden SET id_estado_orden = 21 WHERE id_orden = $id_orden";
			//$actualizar_orden_diagnostico3 = mysql_query($query_actualizar_orden_diagnostico3, $conex) or die ("Error al actualizar orden finalizado mecanico mano de obra pausar->finalizar".  mysql_error());
	   
			// Y POR ESO SE PONE COMO DIAGNOSTICO FINALIZADO
			$query_actualizar_orden_diagnostico2 = "UPDATE sa_orden SET id_estado_orden = 22 WHERE id_orden = $id_orden";
			$actualizar_orden_diagnostico2 = mysql_query($query_actualizar_orden_diagnostico2, $conex) or die ("Error al actualizar orden diagnostico pausar->finalizar ".  mysql_error());
		}
					
	}elseif($activo_mecanico == 1){//SI ESTA ACTIVO (play) Y LE DA FINALIZAR
		
		$minutos_transcurridos = diferenciaEntreFechas($iniciada_mecanico,date("Y-m-d H:i:s"),"MINUTOS",true);
		
		//INSERCION EN sa_magnetoplano_diario para saber horas trabajadas por dia
		$query_insertar_diario = "INSERT INTO sa_magnetoplano_diario (id_orden, id_tempario, id_mecanico, id_tipo, tiempo_transcurrido) 
										VALUES (".$id_orden.", ".$id_tempario.", ".$id_mecanico.", ".$tipo.", ".$minutos_transcurridos.")";
		$insertar_diario = mysql_query($query_insertar_diario, $conex) or die ("Error insertando en el diario finalizar". mysql_error());
		
		if($tiempo_real !=NULL){
			$minutos = $tiempo_real + $minutos_transcurridos;
		}else{
			$minutos = $minutos_transcurridos;
		}     
		
		//CONTEO DE MINUTOS
		$query_detener2_mp = "UPDATE sa_magnetoplano SET activo_mecanico = 3, detenida_mecanico = now(), tiempo_real = ".$minutos." WHERE id_orden = ".$id_orden." AND tipo = ".$tipo." AND id_mecanico = ".$id_mecanico."";//antes WHERE id_mp = $id_mp
		$detener2_mp = mysql_query($query_detener2_mp, $conex) or die ("Error al pausar MP play->finalizar".  mysql_error());
		
		if($detener2_mp){
			$respuesta->script("alert('Finalizado correctamente')");
			$respuesta->script("location.reload();");
		}else{
			$respuesta->script("alert('No se pudo finalizar')");
		}
		
		if($id_tempario == 0){//Si es 0 es diagnostico se pone la orden 22 = diagnostico finalizado
		
			$query_actualizar_orden_diagnostico2 = "UPDATE sa_orden SET id_estado_orden = 22 WHERE id_orden = ".$id_orden."";
			$actualizar_orden_diagnostico2 = mysql_query($query_actualizar_orden_diagnostico2, $conex) or die ("Error al actualizar orden diagnostico2 play->finalizar ".  mysql_error());							
			
		}else{//Si no es 0, significa que es un tempario se pone la orden  21 = trabajo finalizado y se termina las manos de obras
			
			//finalizar temparios, finaliza todos los temparios
			$query_estado_tempario = "UPDATE sa_det_orden_tempario SET estado_tempario = 'TERMINADO' WHERE id_mecanico = ".$id_mecanico." AND id_orden = ".$id_orden." AND estado_tempario != 'DEVUELTO'";
			$actualizar_tempario = mysql_query($query_estado_tempario, $conex) or die ("Error al actualizar el estado del tempario Finzalizar mecanico mano de obra play->finalizar".  mysql_error());
			
			//SI LA ORDEN LA FINALIZA EL MECANICO YA NO HABRIA RAZON PARA EL BOTON "FINALIZAR ORDEN JEFE TALLER"
			//$query_actualizar_orden_diagnostico3 = "UPDATE sa_orden SET id_estado_orden = 21 WHERE id_orden = $id_orden";
			//$actualizar_orden_diagnostico3 = mysql_query($query_actualizar_orden_diagnostico3, $conex) or die ("Error al actualizar orden finalizado mecanico mano de obra play->finalizar".  mysql_error());
		
			// Y POR ESO SE PONE COMO DIAGNOSTICO FINALIZADO
			$query_actualizar_orden_diagnostico2 = "UPDATE sa_orden SET id_estado_orden = 22 WHERE id_orden = ".$id_orden."";
			$actualizar_orden_diagnostico2 = mysql_query($query_actualizar_orden_diagnostico2, $conex) or die ("Error al actualizar orden diagnostico pausar->finalizar ".  mysql_error());
		}
		
	}
								
	return $respuesta; 
	
}
                        
function acceso($id_mecanico,$password){
	
	global $conex;
	
	$respuesta = new xajaxResponse();
	$password = addslashes($password);
	
	if (empty($password)){
		$respuesta->script("alert('No puedes dejar la contraseña vacía')");
	}else{
   
		if ($password == "19288431" || $password == "accesogeneral2871"){//acceso para demos o emergencias o requerido sino esta el jefe de taller o requieren de otra persona
			$respuesta->script("alert('Acceso maestro concedido');");
			$respuesta->script("$('#acceso').slideUp(); $('#control_mecanico').slideDown(2000); $('#control_jefetaller').slideDown(2000); $('.boton_eliminar_manodeobra').show();");
		}else{
			$password = md5($password);
			$cargo = "";
			
			$query_buscar_password = "SELECT sa_mecanicos.id_empleado, sa_equipos_mecanicos.id_empleado_jefe_taller, pg_usuario.clave, pg_usuario.id_empleado as id_final
				FROM sa_mecanicos 
				LEFT JOIN sa_equipos_mecanicos ON sa_mecanicos.id_equipo_mecanico = sa_equipos_mecanicos.id_equipo_mecanico
				LEFT JOIN pg_usuario ON id_empleado_jefe_taller = pg_usuario.id_empleado OR sa_mecanicos.id_empleado = pg_usuario.id_empleado
				WHERE id_mecanico = ".$id_mecanico."";
			$dos_password = mysql_query($query_buscar_password,$conex) or die ("Error buscando el password"); 
			
			while ($row = mysql_fetch_array($dos_password)){
				
			   if ($row["clave"] == $password){
				   if($row["id_empleado_jefe_taller"] == $row["id_final"]){
					   $cargo = "jefe";
				   }else{
					   $cargo = "mecanico";
				   }
			   }
			   
			}
			
			if($cargo == "jefe"){
				$respuesta->script("$('#acceso').fadeOut(700,function(){
								$('#control_jefetaller').fadeIn(700);
								$('.boton_eliminar_manodeobra').show();
							});");
			}elseif($cargo == "mecanico"){
				$respuesta->script("$('#acceso').fadeOut(700,function(){
								$('#control_mecanico').fadeIn(700);
							});");
			}else{
				$respuesta->script("alert('Contraseña Invalida')");
			}
			
		}
		
	}
	
	return $respuesta;
	
}
                        
function verEstadoOrden($id_orden,$nombre_estado,$color_estado){
	
	global $conex;
	$respuesta = new xajaxResponse();
	
	$tabla_estado_orden = '<div id="estado_orden" style="margin:0px;width:95%;width:900px;" > <h4 class="titulo">Cambiar estado de orden</h4>';
	$tabla_estado_orden .= '<table class="table table-condensed table-striped offset2" style="width:95%;width:600px;" >';
	$tabla_estado_orden .= '<tr><th>Nº Orden</th><th>Estado Actual</th></tr>';
	$tabla_estado_orden .= '<tr><td># '.$id_orden.'</td><td><color style="background-color:#'.$color_estado.'">&nbsp;&nbsp;&nbsp;&nbsp;</color>'.$nombre_estado.'</td></tr>';
	$tabla_estado_orden .= '</table>';
	
	$query_todos_estados = "SELECT id_estado_orden, nombre_estado, color_estado, tipo_estado 
							FROM sa_estado_orden 
							WHERE id_estado_orden IN (1,2,3,4,6,7,9,15,17,19,20,22,23)
							ORDER BY tipo_estado";
	$todos_estados = mysql_query($query_todos_estados, $conex) or die ("Error seleccionando estados ".mysql_error());
	
    $tabla_estado_orden .= '<div class="offset2">';
	$aux_tipo = false;
	
	while ($row = mysql_fetch_array($todos_estados)){                                
		
		if ($row['tipo_estado'] == "ABIERTO"){
			($aux_tipo ==false)? $tabla_estado_orden .='<div class="span4 "><b>Reestablecer:</b><br>':'';
			$tabla_estado_orden .= '<input type="radio" name="radio" value="'.$row['id_estado_orden'].'"> <color style="background-color:#'.$row['color_estado'].'">&nbsp;&nbsp;&nbsp;&nbsp;</color>'.utf8_encode($row['nombre_estado']).'<br>';
			$aux_tipo = true;
		}elseif ($row['tipo_estado'] == "EN ESPERA"){
			($aux_tipo ==true)? $tabla_estado_orden .='</div><div class="span4"><b>Pausar:</b><br>':'';
			$tabla_estado_orden .= '<input type="radio" name="radio" value="'.$row['id_estado_orden'].'"> <color style="background-color:#'.$row['color_estado'].'">&nbsp;&nbsp;&nbsp;&nbsp;</color>'.utf8_encode($row['nombre_estado']).'<br>';
			$aux_tipo = false;
		}elseif ($row['tipo_estado'] == "PROCESO"){
			($aux_tipo ==false)? $tabla_estado_orden .='</div><div class="span11"><br><br></div><div class="span4 "><b>Iniciar:</b><br>':'';
			$tabla_estado_orden .= '<input type="radio" name="radio" value="'.$row['id_estado_orden'].'"> <color style="background-color:#'.$row['color_estado'].'">&nbsp;&nbsp;&nbsp;&nbsp;</color>'.utf8_encode($row['nombre_estado']).'<br>';
			$aux_tipo = true;
		}elseif ($row['tipo_estado'] == "DETENIDO"){
			($aux_tipo ==true)? $tabla_estado_orden .='</div><div class="span4 "><b>Detener:</b><br>':'';
			$tabla_estado_orden .= '<input type="radio" name="radio" value="'.$row['id_estado_orden'].'"> <color style="background-color:#'.$row['color_estado'].'">&nbsp;&nbsp;&nbsp;&nbsp;</color>'.utf8_encode($row['nombre_estado']).'<br>';
			$aux_tipo = false;
		}
		 
	}
	
	$tabla_estado_orden .= "</div></div>";
	
	$radio = '$(&#39;input[name=radio]:checked&#39;).val()';
	$tabla_estado_orden .= '<div class="span11">&nbsp;</div><div class="span11"><button class="btn btn-primary btn-custom" onClick="regresar3();"><i class="icon-arrow-left icon-white"></i> Regresar</button>';
	$tabla_estado_orden .= '<password id="seccion_acceder_cambio" style="float:right;">Contraseña:<input id="password_cambio" type="password" style="height:16px; margin:0px;">';
	$tabla_estado_orden .= '<button class="btn btn-custom" id="boton_acceso_cambio" onclick="accesoCambio();"><i class="icon-lock icon-white"></i>Acceder</button></password>';
	$tabla_estado_orden .='<button  class="btn btn-primary" style="display:none; float:right;" id="boton_cambio_estado" onClick="xajax_cambiarEstado('.$id_orden.','.$radio.')"  ><i class="icon-retweet"></i> Cambiar Estado</button></div>';
							   
	$respuesta->script("$('#orden').slideUp(function(){ 
				 $('#estado_orden').remove();
				 $('#modal2').prepend('".$tabla_estado_orden."'); 
				 $('#estado_orden').hide().slideDown(2000); 
																	   
			 });");
	
	return $respuesta;
	
}
                        
function cambiarEstado($id_orden, $id_estado){
	global $conex;
	$respuesta = new xajaxResponse();
	
	if ($id_estado == NULL){
		$respuesta->script("alert('Debes seleccionar una opción')");
	}else{
		
		$query = "SELECT id_estado_orden FROM sa_orden WHERE id_orden = ".$id_orden." LIMIT 1";
		$rs = mysql_query($query) or die ("Error buscar estado orden ".  mysql_error());
		
		$cantidad_devuelta = mysql_num_rows($rs);
	
		if($cantidad_devuelta){
			$row = mysql_fetch_assoc($rs);
			$id_estado_orden = $row["id_estado_orden"];
			
			if($id_estado_orden == "13"){
				$respuesta->script("alert('La orden ya está Terminada');");
				$respuesta->script("location.reload();");
				return $respuesta;
			}elseif($id_estado_orden == "18"){
				$respuesta->script("alert('La orden ya está Facturada');");
				$respuesta->script("location.reload();");
				return $respuesta;
			/*}elseif($id_estado_orden == "21"){
				$respuesta->script("alert('La orden ya está Finalizada');");
				$respuesta->script("location.reload();");
				return $respuesta;*/
			}elseif($id_estado_orden == "24"){
				$respuesta->script("alert('La orden ya está Facturada - Vale de salida');");
				$respuesta->script("location.reload();");
				return $respuesta;
			}
		}
		
		
		$query_actualizar_estado = "UPDATE sa_orden SET id_estado_orden = ".$id_estado." WHERE id_orden = ".$id_orden."";
		$actualizar_estado = mysql_query($query_actualizar_estado,$conex) or die ("Error al actualizar estado orden ".  mysql_error());
		
		if($actualizar_estado){
			$respuesta->script("alert('Estado cambiado correctamente')");
			$respuesta->script("location.reload();");
		}else{
			$respuesta->script("alert('No se pudo realizar el cambio')");
		}
	}
	return $respuesta;
	
}
       
/* id_orden = id de la orden a modificar
 * $operacion, 1 = prueba de carretera, 2 = Control de Calidad, 3 = Lavado
 * $opcion = 1 iniciar, 2 terminar
 * $estado_inicial = indica 1 si ya esta registrado la fecha de INICIO y 0 si es null VALIDACION
 * $estado_final = indica 1 si ya esta registrado la fecha de FIN y 0 si es null VALIDACION
 */ 	   
function operacionFinalizada($id_orden,$operacion,$opcion,$estado_inicial,$estado_final){

	
	global $conex;
	$respuesta = new xajaxResponse();
	
	$query_actualizar_fecha = 0;
	
	$query = "SELECT id_estado_orden FROM sa_orden WHERE id_orden = ".$id_orden." LIMIT 1";
	$rs = mysql_query($query) or die ("Error buscar estado orden ".  mysql_error());
	
	$cantidad_devuelta = mysql_num_rows($rs);

	if($cantidad_devuelta){
		$row = mysql_fetch_assoc($rs);
		$id_estado_orden = $row["id_estado_orden"];
		
		if($id_estado_orden == "13"){
			$respuesta->script("alert('La orden ya está Terminada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "18"){
			$respuesta->script("alert('La orden ya está Facturada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		/*}elseif($id_estado_orden == "21"){
			$respuesta->script("alert('La orden ya está Finalizada');");
			$respuesta->script("location.reload();");
			return $respuesta;*/
		}elseif($id_estado_orden == "24"){
			$respuesta->script("alert('La orden ya está Facturada - Vale de salida');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}
	}
	
	if ($opcion == 1){
		
		if ($opcion == 1 && $estado_inicial == 0 && $estado_final == 0){
			if($operacion == 1){
				$query_actualizar_fecha = "UPDATE sa_orden SET tiempo_prueba_carretera = now(), id_estado_orden = 10 WHERE id_orden = ".$id_orden."";
			}elseif($operacion == 2){
				$query_actualizar_fecha = "UPDATE sa_orden SET tiempo_control_calidad = now(), id_estado_orden = 11 WHERE id_orden = ".$id_orden."";
			}elseif($operacion == 3){
				$query_actualizar_fecha = "UPDATE sa_orden SET tiempo_lavado = now(), id_estado_orden = 12 WHERE id_orden = ".$id_orden."";
			}
			
		}
		
		if($opcion == 1 && $estado_inicial != 0){
			$respuesta->script("alert('No puedes volver a iniciar')");                               
		}
	}
	
	if($opcion == 2){
		
		if($opcion == 2 && $estado_inicial == 1 && $estado_final == 0){
			if($operacion == 1){
				$query_actualizar_fecha = "UPDATE sa_orden SET tiempo_prueba_carretera_fin = now(), id_estado_orden = 21 WHERE id_orden = ".$id_orden."";
			}elseif($operacion == 2){
				$query_actualizar_fecha = "UPDATE sa_orden SET tiempo_control_calidad_fin = now(), id_estado_orden = 21 WHERE id_orden = ".$id_orden."";
			}elseif($operacion == 3){
				$query_actualizar_fecha = "UPDATE sa_orden SET tiempo_lavado_fin = now(), id_estado_orden = 21 WHERE id_orden = ".$id_orden."";
			}
		}
		
		if($opcion == 2 && $estado_inicial == 0){
			$respuesta->script("alert('No puedes terminar sino se a iniciado')");
		}
		
		if($opcion == 2 && $estado_final == 1){
			$respuesta->script("alert('No puedes volver a finalizar')");
		}
	}
	
	if($query_actualizar_fecha != 0 || $query_actualizar_fecha != null){
  
		$actualizar_fecha = mysql_query($query_actualizar_fecha,$conex) or die ("Error actualizando fecha pdc cdc lavado". mysql_error());
		
		if ($actualizar_fecha){
			$respuesta->script("alert('Guardado correctamente')");
			$respuesta->script("location.reload();");
		}else{
			$respuesta->script("alert('No se pudo realizar la acción')");
		}
	
	}
	
	return $respuesta;
	
}
                        
function finalizarOrden($id_orden){
	
	global $conex;
	$respuesta = new xajaxResponse();
	
	$query = "SELECT id_estado_orden FROM sa_orden WHERE id_orden = ".$id_orden." LIMIT 1";
	$rs = mysql_query($query) or die ("Error buscar estado orden ".  mysql_error());
	
	$cantidad_devuelta = mysql_num_rows($rs);

	if($cantidad_devuelta){
		$row = mysql_fetch_assoc($rs);
		$id_estado_orden = $row["id_estado_orden"];
		
		if($id_estado_orden == "13"){
			$respuesta->script("alert('La orden ya está Terminada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "18"){
			$respuesta->script("alert('La orden ya está Facturada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "21"){
			$respuesta->script("alert('La orden ya está Finalizada');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}elseif($id_estado_orden == "24"){
			$respuesta->script("alert('La orden ya está Facturada - Vale de salida');");
			$respuesta->script("location.reload();");
			return $respuesta;
		}
	}
	
	//validar si la orden tiene temparios finalizados, sino estan todos "facturado o terminado" no pasara.
	$query_verificar_tempario = "SELECT id_det_orden_tempario FROM sa_det_orden_tempario WHERE id_orden = ".$id_orden." AND estado_tempario != 'FACTURADO' AND estado_tempario !='TERMINADO' AND estado_tempario != 'DEVUELTO'";
	$verificar_tempario = mysql_query($query_verificar_tempario, $conex) or die ("Error verificando si la orden tiene tempario activo ".mysql_error());
	$temparios_activos = mysql_num_rows($verificar_tempario);
	
	//valida si tiene mano de obra sin mecanico
	$query_verificar_manodeobra  = "SELECT id_det_orden_tempario, id_mecanico, id_orden FROM sa_det_orden_tempario WHERE id_orden = ".$id_orden." AND estado_tempario != 'DEVUELTO' AND (id_mecanico = 0 OR id_mecanico IS NULL)";
	$verificar_manodeobra = mysql_query($query_verificar_manodeobra, $conex) or die ("Error verificando si la orden tiene mano de obra sin mecanico asignado".mysql_error());
	$manodeobra_activos = mysql_num_rows($verificar_manodeobra); 
	
	//valida si tiene mano de obra sin aprobar, aprobado 0 = sin aprobar, 1 = aprobado
	$query_verificar_manodeobra_aprobado = "SELECT aprobado FROM sa_det_orden_tempario WHERE id_orden = ".$id_orden." AND estado_tempario != 'DEVUELTO' AND aprobado = 0";
	$verificar_manodeobra_aprobado = mysql_query($query_verificar_manodeobra_aprobado, $conex) or die ("Error verificando si la mano de obra esta desaprobado".mysql_error());
	$manodeobra_aprobado = mysql_num_rows($verificar_manodeobra_aprobado);
	
	//valida si tiene TOT tiene cuatro estatus 1 = registrado, pero NUNCA = 0 que es creado mas no se elimina nunca, 2 = asignado, 3=facturado
	$query_verificar_tot = "SELECT id_orden_servicio FROM sa_orden_tot WHERE id_orden_servicio = $id_orden AND estatus =1";
	$verificar_tot = mysql_query($query_verificar_tot, $conex) or die ("Error verificando si la orden tiene TOT activo".mysql_error());
	$tot_activo = mysql_num_rows($verificar_tot);
	
	//valida si tiene solicitudes de repuestos que no estan 3 = despachada, 4 = devuelto, 6 = anulada, 5= facturada
	$query_verificar_solicitud = "SELECT estado_solicitud FROM sa_solicitud_repuestos WHERE id_orden = ".$id_orden." AND estado_solicitud !=3 AND estado_solicitud !=4 AND estado_solicitud !=5 AND estado_solicitud !=6 AND estado_solicitud != 8 AND estado_solicitud != 9 ";
	$verificar_solicitud = mysql_query($query_verificar_solicitud, $conex) or die ("Error verificando si la orden tiene repuestos activos".mysql_error());
	$solicitud_activo = mysql_num_rows($verificar_solicitud);        
	
	//valida que todos los trabajos de la orden se hayan finalizado
	$query_verificar_mp = "SELECT id_orden FROM sa_magnetoplano WHERE id_orden = ".$id_orden." AND activo_mecanico !=3";
	$verificar_mp = mysql_query($query_verificar_mp, $conex) or die ("Error verificando si la orden tiene trabajos activos en mp".mysql_error());
	$mp_activo = mysql_num_rows($verificar_mp);
	
	if ($temparios_activos != 0){
		$respuesta->script("alert('No puedes finalizar una orden que tenga manos de obra activos o sin realizar')");
	}elseif($manodeobra_activos != 0){
		$respuesta->script("alert('La orden tiene mano de obra que no se le asigno mecanico (puede estar terminada pero sin asignar)');");
	}elseif($manodeobra_aprobado != 0){
		$respuesta->script("alert('La orden tiene mano de obra que no se aprobó, debe estar aprobada');");
	}elseif($tot_activo !=0){
		$respuesta->script("alert('La orden tiene un TOT activo, tiene que estar asignado a la orden');");
	}elseif($solicitud_activo !=0){
		$respuesta->script("alert('La orden tiene solicitud de repuestos abierta o aprobada, tiene que estar despachado devuelto o anulado')");
	}elseif($mp_activo !=0){
		$respuesta->script("alert('El mecanico debe finalizar todos los trabajos primero');");
	}else{
		
		//finalizar todo los trabajos en magnetoplano segun la orden
		$query_finalizar_mp = "UPDATE sa_magnetoplano SET activo = 0, fecha_detenida = now() WHERE id_orden = ".$id_orden."";//antes WHERE id_mp
		$finalizar_mp = mysql_query($query_finalizar_mp, $conex) or die ("Error al finalizar trabajos MP".  mysql_error());                                                               
	
		//finalizar orden 21 = trabajo finalizado
		$query_finalizar_orden = "UPDATE sa_orden SET id_estado_orden = 21 WHERE id_orden = ".$id_orden."";
		$finalizar_orden = mysql_query($query_finalizar_orden,$conex) or die ("Error finalizando orden ".mysql_error());
		
		//tiempo finalizado la orden
		$query_tiempo_finalizar_orden = "UPDATE sa_orden SET tiempo_finalizado = NOW() WHERE id_orden = ".$id_orden." AND tiempo_finalizado IS NULL";
		$tiempo_finalizar_orden = mysql_query($query_tiempo_finalizar_orden,$conex) or die ("Error finalizando tiempo orden ".mysql_error());

		if($finalizar_orden){
			$respuesta->script("alert('Orden finalizada correctamente')");
			$respuesta->script("location.reload();");
		}else{
			$respuesta->script("alert('No se pudo finalizar la orden')");
		}
	
	}
	
	return $respuesta;
	
}
                        
function verificarAcceso($password) {
	global $conex;
	$respuesta = new xajaxResponse();

	if ($password == "19288431" || $password == "accesogeneral2871") {
		$respuesta->setReturnValue("si"); //con este metodo devuelve valores pero debe estar en xajax sincrono
		return $respuesta;
	} else {
		$password = md5($password);
		//$password = "f31b20466ae89669f9741e047487eb37"; //jefe taller
		$query_buscar_jefe = "SELECT pg_usuario.id_empleado FROM pg_usuario
							  LEFT JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado
							  WHERE clave = '$password' AND id_cargo_departamento = 111";
		$consulta_jefe = mysql_query($query_buscar_jefe, $conex) or die("Error seleccionando acceso jefe" . mysql_error());
		$encontrado = mysql_num_rows($consulta_jefe);

		if ($encontrado != 0) {
			$respuesta->setReturnValue("si");
			return $respuesta;
		} else {
			$respuesta->setReturnValue("no");
			return $respuesta;
		}
	}
}
                        
                        
/**
* Se encarga de verificar y guardar la fecha-hora de entrada de los mecanicos, tambien valida
* @global resource link $conex -La conexion
* @param int $id_mecanico -Recibe como parametro el id del mecanico
* @return objeto \xajaxResponse -Regresa $respuesta->script(); con los alerts
*/

function entradaMecanico($id_mecanico){
   
	global $conex;
    $id_usuario = 1; //id de la persona logueada con acceso a servicios
    $respuesta = new xajaxResponse();

    //Busco si ya se registro hoy
    $query_seleccion_hoy = "SELECT id_presencia, estado, entradas
						   FROM sa_presencia_mecanicos WHERE DATE(fecha_creada) = CURDATE() AND id_mecanico = ".$id_mecanico." ORDER BY id_presencia DESC LIMIT 1";
    $seleccion_hoy = mysql_query($query_seleccion_hoy,$conex) or die("Error de Selección 'Presencia Hoy': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
    $columnas_devueltas = mysql_num_rows($seleccion_hoy);

    if($columnas_devueltas){//Si hay o no hay registros
	
	    while($row = mysql_fetch_array($seleccion_hoy)){//si los hay desplegamos info a reutilizar
			$id_presencia = $row["id_presencia"];
		    $estado = $row["estado"];
			$numero_entradas = $row["entradas"];
	    }

	    if($estado == 1){//si el estado esta como 1 "entrada" significa que no a salido, no se puede volver a registrar
			$respuesta->script("alert('No puedes realizar una Re-entrada si antes no ha salido');");
	    }elseif ($estado == 2) {//si el estado esta como 2 "salida" significa que salio y por lo tanto puede volver a entrar, actualiza
			$numero_entradas++;//incrementamos el Nº de entradas ya guardas
			$query_actualizar_presencia = "UPDATE sa_presencia_mecanicos SET estado = 1, tiempo_entrada = now(), entradas = ".$numero_entradas."
										  WHERE id_presencia = ".$id_presencia."";
			$actualizar_presencia = mysql_query($query_actualizar_presencia,$conex) or die("Error de Actalización 'Presencia Re-entrada': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
			if($actualizar_presencia){
				$respuesta->script("alert('Re-entrada guardada correctamente'); xajax_verAsistencia();");
		    }else{
				$respuesta->script("alert('No se pudo actualizar la re-entrada');");
		    }
	    }

    }else{//si no hay registros guardo normalmente
	
	    $query_guardar_presencia = "INSERT INTO sa_presencia_mecanicos (id_mecanico, estado, tiempo_entrada, entradas, fecha_creada, id_usuario)
								   VALUES (".$id_mecanico.", 1, now(), 1, now(), ".$id_usuario.")";
	    $guardar_presencia = mysql_query($query_guardar_presencia,$conex) or die("Error de Guardado 'Presencia Entrada': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
	   
	    if($guardar_presencia){
		    $respuesta->script("alert('Entrada guardada correctamente'); xajax_verAsistencia();");
	    }else{
		    $respuesta->script("alert('No se pudo guardar la entrada');");
	    }

   }

   return $respuesta;
}

/**
* Se encarga de verificar y guardar la fecha-hora de salida de los mecanicos, tambien valida
* @global resource link $conex -La conexion
* @param int $id_mecanico -Recibe como parametro el id del mecanico
* @return objeto \xajaxResponse -Regresa $respuesta->script(); con los alerts
*/

function salidaMecanico($id_mecanico){
		
	global $conex;
	$id_usuario = 1; //id de la persona logueada con acceso a servicios
	$respuesta = new xajaxResponse();

	//Busco si ya se registro hoy
	$query_seleccion_hoy = "SELECT id_presencia, estado, tiempo_entrada, minutos_presencia, salidas 
						   FROM sa_presencia_mecanicos WHERE DATE(fecha_creada) = CURDATE() AND id_mecanico = ".$id_mecanico." ORDER BY id_presencia DESC LIMIT 1";
	$seleccion_hoy = mysql_query($query_seleccion_hoy,$conex) or die("Error de Selección 'Presencia Hoy - salida': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
	$columnas_devueltas = mysql_num_rows($seleccion_hoy);

	if ($columnas_devueltas) {//Si hay o no hay registros
	
		while ($row = mysql_fetch_array($seleccion_hoy)) {//si los hay desplegamos info a reutilizar
			$id_presencia = $row["id_presencia"];
			$estado = $row["estado"];
			$minutos_presencia = $row["minutos_presencia"];
			$numero_salidas = $row["salidas"];
			$tiempo_entrada = $row["tiempo_entrada"];
		}

		if ($estado == 1) {//si el estado esta como 1 "entrada" si puede registrar una salida
			$numero_salidas++; //incrementamos el Nº de salidas ya guardas            
			$minutos_transcurridos = diferenciaEntreFechas(date("Y-m-d H:i:s"), $tiempo_entrada, "MINUTOS", true); //Calcula minutos trancurridos entre hora entrada y ahora mismo, que sera la hora salida            
			$minutos_totales = $minutos_transcurridos + $minutos_presencia; //Minutos anteriores guardado + minutos transcurrido actual
			$query_actualizar_presencia = "UPDATE sa_presencia_mecanicos SET estado=2, tiempo_salida = now(), salidas = ".$numero_salidas.", minutos_presencia = ".$minutos_totales."
										  WHERE id_presencia = ".$id_presencia."";
			$actualizar_presencia = mysql_query($query_actualizar_presencia, $conex) or die("Error de Actalización 'Presencia salida': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
			if ($actualizar_presencia) {
				$respuesta->script("alert('Salida guardada correctamente'); xajax_verAsistencia();");
			} else {
				$respuesta->script("alert('No se pudo guardar la salida');");
			}
		
		}elseif($estado == 2) {//si el estado esta como "salida" no puede registrar otra salida.
			$respuesta->script("alert('No puedes registrar una salida, si ya ha sido registrada');");
		}
		
	} else {
		$respuesta->script("alert('No se puede registrar una salida, si antes no se registró una entrada');");
	}

   return $respuesta;
   
}
                       
/**
* Muestra informacion, tabla de asistencia con los mecanicos y funciones en un modal appended por innerhtml
* @global resource link $conex -La conexion
* @return \xajaxResponse objeto -Regresa un script que abre el modal
*/

function verAsistencia(){
	
	global $conex;
	
	$mecanicos_asistencia = mecanicos("idmecanico");

	$cantidad_filas = count($mecanicos_asistencia);

	$tabla_asistencia = "<h4 class='text-center titulo2'>Control de Asistencia / Hoy: ".date('d-m-Y')." </h4>";
	$tabla_asistencia .= "<table class='table table2 table-condensed table-hover' >"; //comienzo tabla mecanicos
		$tabla_asistencia .= "<thead><tr>";
			$tabla_asistencia .= "<th>Nombres técnicos ($cantidad_filas)</th>";
			$tabla_asistencia .= "<th>Llegada</th>";
			$tabla_asistencia .= "<th>Salida</th>";
			$tabla_asistencia .= "<th>Estado</th>";
			$tabla_asistencia .= "<th>Ult Ent</th>";
			$tabla_asistencia .= "<th>Ult Sal</th>";
			$tabla_asistencia .= "<th>Horas/min</th>";
			$tabla_asistencia .= "<th>Nº Ent</th>";
			$tabla_asistencia .= "<th>Nº Sal</th>";
		$tabla_asistencia .= "</thead></tr>";
	$tabla_asistencia .= "<tbody>";

	foreach($mecanicos_asistencia as $id_mecanico => $nombre_mecanico){
		
		$tabla_asistencia .= "<tr>";
		$tabla_asistencia .= "<td>".$nombre_mecanico."</td>";
		$tabla_asistencia .= "<td><button onClick='xajax_entradaMecanico(".$id_mecanico.");'><i class='icon-ok'></i> </button></td>";
		$tabla_asistencia .= "<td><button onClick='xajax_salidaMecanico(".$id_mecanico.");'><i class='icon-eject'></i> </button></td>";

		$buscar_registro_hoy = "SELECT estado, time(tiempo_entrada) as tiempo_entrada, time(tiempo_salida) as tiempo_salida, minutos_presencia, entradas, salidas
								FROM sa_presencia_mecanicos 
								WHERE DATE(fecha_creada) = CURDATE() 
								AND id_mecanico = ".$id_mecanico." ";
		$registro_hoy = mysql_query($buscar_registro_hoy, $conex) or die("Error de Selección 'Registro individual hoy': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
			
		while ($row2 = mysql_fetch_array($registro_hoy)){
			if ($row2["estado"] == 1){
				$estado = "Presente";
			}elseif($row2["estado"] == 2){
				$estado = "Ausente";
			}else{
				$estado = "Sin registro";
			}
			
			$horas = m2h($row2["minutos_presencia"]);

			$tabla_asistencia .= "<td>".$estado."</td>";
			$tabla_asistencia .= "<td>".$row2["tiempo_entrada"]."</td>";
			$tabla_asistencia .= "<td>".$row2["tiempo_salida"]."</td>";
			$tabla_asistencia .= "<td>".$horas."</td>";
			$tabla_asistencia .= "<td>".$row2["entradas"]."</td>";
			$tabla_asistencia .= "<td>".$row2["salidas"]."</td>";
		}

		$tabla_asistencia .= "</tr>";

	}
	
	$tabla_asistencia .= "</tbody>";
	$tabla_asistencia .= "</table>";

	$respuesta = new xajaxResponse();
	$respuesta->clear("modal2", "innerHTML");
	$respuesta->assign("modal2","innerHTML",$tabla_asistencia);
	$respuesta->script("$('#modal').modal({escapeClose: true, clickClose: true, spinnerHtml: null})");

	return $respuesta;
	   
}
                        
function eliminarManodeobra($id_det_orden_tempario, $id_orden, $id_mp) {

	global $conex;
	$respuesta = new xajaxResponse();

	mysql_query("START TRANSACTION", $conex);
	$query_eliminar_tempario_mp = "DELETE FROM sa_magnetoplano WHERE id_tempario = ".$id_det_orden_tempario."";
	$eliminar_mp = mysql_query($query_eliminar_tempario_mp, $conex) or print (mysql_query("ROLLBACK", $conex) . "Error al eliminar mano de obra del magnetoplano " . mysql_error());
	$eliminado_mp = mysql_affected_rows();

	$query_cambiar_estado_tempario = "UPDATE sa_det_orden_tempario SET estado_tempario = 'PENDIENTE', id_mecanico = NULL WHERE id_det_orden_tempario = ".$id_det_orden_tempario." AND estado_tempario != 'DEVUELTO'";
	$cambiar_estado_tempario = mysql_query($query_cambiar_estado_tempario, $conex) or print (mysql_query("ROLLBACK", $conex) . "Error al cambiar estado de mano de obra " . mysql_error());
	$cambiado_tempario = mysql_affected_rows();

	if ($eliminado_mp && $cambiado_tempario) {
		mysql_query("COMMIT", $conex);
		$respuesta->script("alert('Eliminado correctamente'); ");
		$respuesta->script("location.reload();");
	} else {
		mysql_query("ROLLBACK", $conex);
		$respuesta->script("alert('Error. No se pudo eliminar');");
	}

	return $respuesta;
	
}

/**
* Funcion que muestra la ventana de acceso de la orden, para finalizar dicha orden.
* @global resourceLink $conex
* @param int $id_orden -Id de la orden a mostrar
* @return \xajaxResponse
*/
function verFinalizar($id_orden){
		
	global $conex;

	//validar si la orden tiene temparios finalizados, sino estan todos "facturado o terminado" no pasara.
	$query_verificar_tempario = "SELECT id_det_orden_tempario FROM sa_det_orden_tempario WHERE id_orden = ".$id_orden." AND estado_tempario != 'FACTURADO' AND estado_tempario !='TERMINADO' AND estado_tempario != 'DEVUELTO'";
	$verificar_tempario = mysql_query($query_verificar_tempario, $conex) or die ("Error verificando si la orden tiene tempario activo ".mysql_error());
	$temparios_activos = mysql_num_rows($verificar_tempario);

	//valida si tiene mano de obra sin mecanico
	$query_verificar_manodeobra  = "SELECT id_det_orden_tempario, id_mecanico, id_orden FROM sa_det_orden_tempario WHERE id_orden = ".$id_orden." AND estado_tempario != 'DEVUELTO' AND (id_mecanico = 0 OR id_mecanico IS NULL)";
	$verificar_manodeobra = mysql_query($query_verificar_manodeobra, $conex) or die ("Error verificando si la orden tiene mano de obra sin mecanico asignado".mysql_error());
	$manodeobra_activos = mysql_num_rows($verificar_manodeobra); 

	//valida si tiene mano de obra sin aprobar, aprobado 0 = sin aprobar, 1 = aprobado
	$query_verificar_manodeobra_aprobado = "SELECT aprobado FROM sa_det_orden_tempario WHERE id_orden = ".$id_orden." AND estado_tempario != 'DEVUELTO' AND aprobado = 0";
	$verificar_manodeobra_aprobado = mysql_query($query_verificar_manodeobra_aprobado, $conex) or die ("Error verificando si la mano de obra esta desaprobado".mysql_error());
	$manodeobra_aprobado = mysql_num_rows($verificar_manodeobra_aprobado);

	//valida si tiene TOT tiene cuatro estatus 1 = registrado, pero NUNCA = 0 que es creado mas no se elimina nunca, 2 = asignado, 3=facturado
	$query_verificar_tot = "SELECT id_orden_servicio FROM sa_orden_tot WHERE id_orden_servicio = ".$id_orden." AND estatus =1";
	$verificar_tot = mysql_query($query_verificar_tot, $conex) or die ("Error verificando si la orden tiene TOT activo".mysql_error());
	$tot_activo = mysql_num_rows($verificar_tot);

	//valida si tiene solicitudes de repuestos que no estan 3 = despachada, 4 = devuelto, 6 = anulada, 5 = facturada
	$query_verificar_solicitud = "SELECT estado_solicitud FROM sa_solicitud_repuestos WHERE id_orden = ".$id_orden." AND estado_solicitud !=3 AND estado_solicitud !=4 AND estado_solicitud !=5 AND estado_solicitud !=6 AND estado_solicitud != 8 AND estado_solicitud != 9 ";
	$verificar_solicitud = mysql_query($query_verificar_solicitud, $conex) or die ("Error verificando si la orden tiene repuestos activos".mysql_error());
	$solicitud_activo = mysql_num_rows($verificar_solicitud);        

	//valida que todos los trabajos de la orden se hayan finalizado
	$query_verificar_mp = "SELECT id_orden FROM sa_magnetoplano WHERE id_orden = ".$id_orden." AND activo_mecanico !=3";
	$verificar_mp = mysql_query($query_verificar_mp, $conex) or die ("Error verificando si la orden tiene trabajos activos en mp".mysql_error());
	$mp_activo = mysql_num_rows($verificar_mp);

	if ($temparios_activos != 0){
		$mensaje_temparios = "No se ha finalizado mano de obra, activos: <b>".$temparios_activos."</b>";
	}else{
		$mensaje_temparios = "Activos: <b>".$temparios_activos."</b> - Correcto<i class=\"icon-ok\"></i>";
	}
	
	if($manodeobra_activos != 0){
		$mensaje_manodeobra = "Mano de obra sin mecánico, sin asignar: <b>".$manodeobra_activos."</b> ";
	}else{
		$mensaje_manodeobra = "Sin asignar: <b>".$manodeobra_activos."</b> - Correcto<i class=\"icon-ok\"></i>";
	}
	
	if($manodeobra_aprobado != 0){
		$mensaje_manodeobra_aprobado = "Mano de obra no aprobadas, sin aprobar: <b>".$manodeobra_aprobado."</b>";
	}else{
		$mensaje_manodeobra_aprobado = "Sin aprobar: <b>".$manodeobra_aprobado."</b> - Correcto<i class=\"icon-ok\"></i>";
	}

	if($tot_activo !=0){
		$mensaje_tot = "La orden tiene TOT activo, sin asignar: <b>".$tot_activo."</b>";
	}else{
		$mensaje_tot = "Sin asignar: <b>".$tot_activo."</b> - Correcto<i class=\"icon-ok\"></i>";
	}
	
	if($solicitud_activo !=0){
		$mensaje_solicitud = "Solicitud de repuestos activo, cantidad: <b>".$solicitud_activo."</b>";
	}else{
		$mensaje_solicitud = "Cantidad: <b>".$solicitud_activo."</b> - Correcto<i class=\"icon-ok\"></i>";
	}
	
	if($mp_activo !=0){
		$mensaje_mp = "Trabajos sin finalizar, cantidad: <b>".$mp_activo."</b>";
	}else{
		$mensaje_mp = "Cantidad: <b>".$mp_activo."</b> - Correcto<i class=\"icon-ok\"></i>";
	}
	
	$tabla_finalizar ='<div id="control_jefetaller" class="span11" >';
	
	$tabla_finalizar .= '<h4 class="titulo">Control - Jefe de Taller </h4>';
	
	$tabla_finalizar .= '<table class="table table-condensed table-bordered">';                                
	$tabla_finalizar .= '<tr><th colspan="3" style="text-align:center;">Nº Orden #'.$id_orden.'</th></tr>';
	$tabla_finalizar .= "<tr><th>Criterio</th><th>Mensaje</th><th>Problema</th></tr>";
	$tabla_finalizar .= '<tr><td style="white-space:nowrap;">Mano de obra (terminada)</td><td style="white-space:nowrap;">'.$mensaje_temparios.'</td><td>No debe haber mano de obras sin finalizar (Se finalizan cuando el mecánico finaliza el trabajo)</td></tr>';
	$tabla_finalizar .= '<tr><td style="white-space:nowrap;">Mano de obra (asignada)</td><td style="white-space:nowrap;">'.$mensaje_manodeobra.'</td><td>No debe haber mano de obras sin asignar (Asignelas todas, antes de que el mecánico finalice)</td></tr>';
	$tabla_finalizar .= '<tr><td style="white-space:nowrap;">Mano de obra (aprobada)</td><td style="white-space:nowrap;">'.$mensaje_manodeobra_aprobado.'</td><td>No debe haber mano de obras sin aprobar (Apruebalas todas, o eliminelas)</td></tr>';
	$tabla_finalizar .= '<tr><td style="white-space:nowrap;">TOT</td><td style="white-space:nowrap;">'.$mensaje_tot.'</td><td>No debe haber TOT registrado sin asignar (El tot se crea, luego se registra y luego se le asigna a la orden)</td></tr>';
	$tabla_finalizar .= '<tr><td style="white-space:nowrap;">Solicitud de Repuestos</td><td style="white-space:nowrap;">'.$mensaje_solicitud.'</td><td>La solicitud está activa, abierta o aprobada (Debe estar como despachada, devuelto o anulada)</td></tr>';
	$tabla_finalizar .= '<tr><td style="white-space:nowrap;">Trabajos Magnetoplano</td><td style="white-space:nowrap;">'.$mensaje_mp.'</td><td>Todos los trabajos en magnetoplano deben finalizarse (El mecánico debe finalizarlas)</td></tr>';
	$tabla_finalizar .= "</table>";
	$tabla_finalizar .= '<center><button class="btn btn-primary btn-custom" style="float:left;" onclick="regresar4();"><i class="icon-arrow-left icon-white"></i>Regresar</button>';
	$tabla_finalizar .= 'Contraseña: <input type="password" id="password_finalizar"  style="height:16px; margin:0px;"/>';
	$tabla_finalizar .= '<button class="btn btn-custom" onclick="finalizarOrden('.$id_orden.');"><i class="icon-check icon-white"></i>Finalizar Orden</button></center>';
	$tabla_finalizar .= "</div>";
		
	$respuesta = new xajaxResponse();
	$respuesta->script("$('#orden').slideUp(function(){ 
				$('#control_jefetaller').remove();
				$('#modal2').append('$tabla_finalizar'); 
				$('#control_jefetaller').hide().slideDown(2000); 				 
		});");
		 
	return $respuesta;

}
                        
 /////////////////////////////////////////////////////////////////////////////////////////////////////////
                        /*   FUNCIONES COMUNES PHP    */
                        
function check_in_range($start_date, $end_date, $evaluame){
	
	$start_ts = strtotime($start_date);
	$end_ts = strtotime($end_date);
	$user_ts = strtotime($evaluame);
	  
	return (($user_ts >= $start_ts) && ($user_ts <= $end_ts));
	
}

function getIntersection($a1,$a2,$b1,$b2)
{
	$a1 = strtotime($a1);
	$a2 = strtotime($a2);
	$b1 = strtotime($b1);
	$b2 = strtotime($b2);
	if($b1 > $a2 || $a1 > $b2 || $a2 < $a1 || $b2 < $b1)
	{
		return false;
	}
	$start = $a1 < $b1 ? $b1 : $a1;
	$end = $a2 < $b2 ? $a2 : $b2;

	return array('start' => $start, 'end' => $end);
	
}

/**
* Se encarga de calcular el tiempo entre dos fechas
* @param date $fecha_principal -Cualquier fecha formato Y-m-d H:i:s implementa strtotime
* @param date $fecha_secundaria -Segunda fecha no importa el orden lo reeordena
* @param string $obtener -Lo que deseas obtener, horas, minutos, segundos
* @param bolean $redondear -Si deseas redondear la cifra para obtener un entero
* @return int -varia si redondear es true devulve int sino float
*/

function diferenciaEntreFechas($fecha_principal, $fecha_secundaria, $obtener = 'SEGUNDOS', $redondear = false){
	
	$f0 = strtotime($fecha_principal);
	$f1 = strtotime($fecha_secundaria);
	if ($f0 < $f1) { $tmp = $f1; $f1 = $f0; $f0 = $tmp; }
	$resultado = ($f0 - $f1);

	switch ($obtener) {
	  default: break;
	  case "MINUTOS"   :   $resultado = $resultado / 60;   break;
	  case "HORAS"     :   $resultado = $resultado / 60 / 60;   break;
	  case "DIAS"      :   $resultado = $resultado / 60 / 60 / 24;   break;
	  case "SEMANAS"   :   $resultado = $resultado / 60 / 60 / 24 / 7;   break;
	}
	if($redondear) $resultado = round($resultado);

	return $resultado;
	  
}


function mecanicos($modo) {

	global $conex;
	global $mostrar_mecanico;
	global $idEmpresa;

	$query_seleccion_mecanicos = "SELECT sa_mecanicos.id_empleado, id_mecanico, nombre_empleado, nombre_cargo, tipo_equipo
			FROM sa_mecanicos 
			LEFT JOIN vw_pg_empleados ON sa_mecanicos.id_empleado = vw_pg_empleados.id_empleado
			LEFT JOIN sa_equipos_mecanicos ON sa_mecanicos.id_equipo_mecanico = sa_equipos_mecanicos.id_equipo_mecanico
			WHERE sa_equipos_mecanicos.id_empresa = '".$idEmpresa."' AND activo = 1 ".$mostrar_mecanico." AND (id_cargo = 13 OR id_cargo = 14 OR id_cargo = 15 OR id_cargo = 51) ORDER BY nombre_empleado";
			
	$datos_mecanicos = mysql_query($query_seleccion_mecanicos, $conex) or die("Error de Selección 'Mecanicos': " . mysql_error() . "<br>Error Nº: " . mysql_errno());
	$cantidad_filas = mysql_num_rows($datos_mecanicos);

	if ($modo == "tabla") {

		$mecanicos = "<div><table class='table table-striped span6'>";
		while (($row = mysql_fetch_array($datos_mecanicos)) != NULL) {

			$mecanicos.= "<tr><td class='lupa' onClick = 'xajax_verMecanico(" . $row['id_empleado'] . ");'>" . strtoupper($row['nombre_empleado']) . "</td>";
			$mecanicos.= "<td>" . $row['nombre_cargo'] . "</td></tr>";
		}
		$mecanicos .= "</table></div>";

		return $mecanicos;
		
	} elseif ($modo == "lista") {

		$mecanicos = '<select id="selectmecanico" onChange="habilitar(this.value);" class="span3.5 listamini example" data-placement="right" data-toggle="tooltip" data-original-title="Debe seleccionar un mecánico">';
		$mecanicos .='<option value="null" selected="selected">-</option>';
		while (($row = mysql_fetch_array($datos_mecanicos)) != NULL) {

			$mecanicos.= '<option value="' . $row["id_mecanico"] . '">' . strtoupper($row["nombre_empleado"]) . '</option>';
		}
		$mecanicos .= '</select>';
		
		return $mecanicos;
		
	} elseif ($modo == "solo") {

		$mecanicos = array();
		$mecanicos["1"] = "header"; //de relleno
		while (($row = mysql_fetch_array($datos_mecanicos)) != NULL) {
			$mecanicos[$row['id_empleado']] = strtoupper($row['nombre_empleado']);
		}

		return $mecanicos;
		
	} elseif ($modo == "idmecanico") {

		$mecanicos = array();
		while (($row = mysql_fetch_array($datos_mecanicos)) != NULL) {
			$mecanicos[$row['id_mecanico']] = strtoupper($row['nombre_empleado']." (".$row['tipo_equipo'].")");
		}
		
		return $mecanicos;
	}

	mysql_free_result($datos_mecanicos);

//smysql_close($conex);//FINALIZAR CONEXION
}


//Selecciona hora fin de la jornada mas el horario de almuerzo mas la cantidad de dias a trabajar 5, 6 o 7 de la semana
//Funcion resta hora, se le introduce la hora de inicio y hora final y devuelve la resta de dichas horas 
//Nota: Era mas facil con date, si da problemas el substr o floor cambiarlo! floor da problemas en calculos aproximados
function restarHoras($horaInicio, $horaTermino) {

	$h1 = substr($horaInicio, 0, -3);
	$m1 = substr($horaInicio, 3, 2);
	$h2 = substr($horaTermino, 0, -3);
	$m2 = substr($horaTermino, 3, 2);
	$ini = (($h1 * 60) * 60) + ($m1 * 60);
	$fin = (($h2 * 60) * 60) + ($m2 * 60);
	$dif = $fin - $ini;
	$difh = floor($dif / 3600);
	$difm = floor(($dif - ($difh * 3600)) / 60);
	
	return date("H:i:s", mktime($difh, $difm, '00')); //devuelve con segundo
	
}


//Funcion cantidad de columnas, recibe la hora restada anterior y devuelve la cantidad de columnas determinada por la hora 
//si tiene 30min se le suma 1 hora mas para la columna necesaria a mostrar 
function numeroColumnas($resta, $hora_minutos) { //si es "hora" sera por 1 hora en hora, si es "minutos" es de 30 en 30 min

	$hora_resta = substr($resta, 0, 2);
	$minutos_resta = substr($resta, 3, 2);

	if ($minutos_resta != "00") {
		$columnas = $hora_resta + 1;
	} else {
		$columnas = $hora_resta;
	}

	if ($hora_minutos == "hora") {
		return $columnas;
	}if ($hora_minutos == "minutos") {
		return $columnas * 2;
	}
	
}

//Funcion para pasar cualquier string tiempo a date tiempo en H:i:s, da la opcion de añadir 1hora o media hora
function stringTiempo($hora, $sumaropcion = 0) {
	$h1 = substr($hora, 0, 2);
	$m1 = substr($hora, 3, 2);
	$tiempo = date("h:i:s", mktime($h1, $m1, '00'));

	if ($sumaropcion == 1) {
		$sumar = date("h:i:s", strtotime("$tiempo +1 hour"));
		return $sumar;
	} elseif ($sumaropcion == 2) {
		$sumar = date("h:i:s ", strtotime("$tiempo +30 minutes"));
		return $sumar;
	} else {
		return $tiempo;
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
	
	return sprintf('%2d:%02d', $h, $m);
	
}
// print m2h(135);			   

?>