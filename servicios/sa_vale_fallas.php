<?php
@session_start();

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	require_once("../connections/conex.php");
	
	/*function load_page($id_calendario){
		$r= getResponse();
		//$calendario=setCalendar($id_calendario,'xajax_cargar_dia');
		//$r->loadCommands($calendario);
		return $r;
	}
	
	xajaxRegister('load_page');
	
	
	xajaxProcess();*/
	
	$id_recepcion=$_GET['id'];
	
	if($id_recepcion==''){
		echo 'No ha especificado recepcion';
		exit;
	}
        
        $query = sprintf("SELECT CONCAT_WS(' ', nombre_empleado, apellido) as nombre_empleado
                FROM sa_orden
                INNER JOIN pg_empleado ON sa_orden.id_empleado = pg_empleado.id_empleado
                WHERE id_recepcion = %s LIMIT 1",
                    $id_recepcion);
        $rs = mysql_query($query);
        if(!$rs){ die(mysql_error()."<br>Linea: ".__LINE__."<br>Query: ".$query); }
 
        $row = mysql_fetch_assoc($rs);
        $nombreEmpleadoOrden = $row["nombre_empleado"];
	
	//obteniendo los datos de la recepcion:
	$c=new connection();
	$c->open();
	
	$sa_v_datos_vale_recepcion=$c->sa_v_datos_vale_recepcion;
	
	$rec=$sa_v_datos_vale_recepcion->doSelect($c,new criteria(sqlEQUAL,$sa_v_datos_vale_recepcion->id_recepcion,$id_recepcion));
	
	if(!$rec){
		echo 'error';
		exit;
	}else{
		if($rec->getNumRows()==0){
			echo 'No exite';
			exit;
		}
		$fecha_estimada_entrega=str_tiempo($rec->fecha_estimada_entrega);
		$recempresa=$c->pg_empresa->doSelect($c,new criteria(sqlEQUAL,$c->pg_empresa->id_empresa,$rec->id_empresa));
		$reccliente=$c->cj_cc_cliente->doSelect($c,new criteria(sqlEQUAL,$c->cj_cc_cliente->id,$rec->id_cliente_contacto));
		$recunidad=$c->sa_v_unidad_basica->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_unidad_basica,$rec->id_unidad_basica));
		$recinventario = $c->sa_v_recepcion_inventario->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_recepcion_inventario->id_cita,$rec->id_cita));
		//extrayendo las fallas:
		
		$fallas = $c->sa_v_recepcion_falla->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_recepcion_falla->id_recepcion,$id_recepcion));
	}
	
	$c->close();
	
	$nivel_combustible=array(
		'0'=>'Vac&iacute;o',
		'0.25'=>'1/4',
		'0.5'=>'1/2',
		'0.75'=>'3/4',
		'1'=>'Lleno'
	);
	
	$boleano=array('1'=>'S&Iacute;','0'=>'NO');
	includeDoctype();
	
	if($_GET['view']=='print'){
		$loadscript.='print();';
	}
	
	if($loadscript!=''){
		$loadpage=' onload="'.$loadscript.'" ';
	}
        
        
		
?>

<html>
	<head>
		<?php 
			includeMeta();
			includeScripts();
			getXajaxJavascript();
			includeModalBox();
		?>
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Diagnóstico del Vehículo</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    
		<script>
	    	    function ocultarDiagnostico(){
	                if($(".diagnosticoFalla").css('visibility') == "hidden"){
	                    $(".diagnosticoFalla").css("visibility","");
	                }else{
	                    $(".diagnosticoFalla").css("visibility","hidden");
	                }
                
	                if($(".respuestaFalla").css('visibility') == "hidden"){
	                    $(".respuestaFalla").css("visibility","");
	                }else{
	                    $(".respuestaFalla").css("visibility","hidden");                    
	                }
	            }
				
				function aumentarEspacio(){
					var obj = $(".trFallas");
					var arrayMax = [];
					var alturaMax;
					
					$.each( obj, function() {
					  var alturaActual = $(this).height();
					  arrayMax.push(alturaActual);
					});
					
					alturaMax = Math.max.apply(Math, arrayMax);
					
					$.each( obj, function() {
					  $(this).height(alturaMax + 5);
					});
				}
				
				function reducirEspacio(){
					var obj = $(".trFallas");
					var arrayMax = [];
					var alturaMax;
					
					$.each( obj, function() {
					  var alturaActual = $(this).height();
					  arrayMax.push(alturaActual);
					});
					
					alturaMax = Math.max.apply(Math, arrayMax);
					
					$.each( obj, function() {
					  $(this).height(alturaMax - 5);
					});
				}
				
		</script>
		
		<style type="text/css">
			body{
				font-family:Arial, Helvetica, sans-serif;
				font-size:8pt;
			}
			
			table caption{
				border:1px solid #000000;
				padding:2px;
			}
			
			.maxtitle{
				border:1px solid #000000;
				font-weight:bold;
				font-size:12pt;
				text-align:center;
			}
			
			.data{
			}
			
			.data td{
				padding:2px;
			}
			
			.data td.tddata{				
				border-bottom:1px solid #000000;
			}
			
			.coltitle{
				text-align:left;
				font-weight:bold;
			}
			.coldata{
			}
			
			#fallas thead td{
				font-weight:bold;
			}
		</style>
	</head>
	<body <?php echo $loadpage; ?> >
		<div>
			<table style="width:100%">
				<tbody>
					<tr>
						<td class="maxtitle">DIAGN&Oacute;STICO DEL VEH&Iacute;CULO</td>
						<td width="20px">
							<img border="0" src="<?php echo getUrl('servicios/clases/barcode128.php').'?pc=1&type=B&bw=2&codigo='.$rec->numeracion_recepcion; ?>" alt="barcode" />
						</td>
					</tr>
				</tbody>
			</table>
			<table style="width:100%">
				<tbody>
					<tr>
						<td valign="top" width="30%">
							<table style="width:100%">
								<tr>
									<td><?php echo $recempresa->nombre_empresa; ?></td>
								</tr>
								<tr>
									<td><?php echo $recempresa->rif; ?></td>
								</tr>
								<tr>
									<td><?php echo $recempresa->telefono1.' / Fax:'.$recempresa->fax; ?></td>
								</tr>
								<tr>
									<td><?php echo $recempresa->direccion; ?></td>
								</tr>
								<tr>
									<td><?php echo $recempresa->correo; ?></td>
								</tr>
							</table>
						
						</td>
						<td>
							<table style="width:100%;" class="data">
								<col class="coltitle" />
								<col class="coldata" />
								<col class="coltitle" />
								<col class="coldata" />
								<tr>
									<td>Cliente:</td>
									<td class="tddata"><?php echo $rec->apellido.' '.$rec->nombre; ?></td>
									<td>Tel&eacute;fono:</td>
									<td class="tddata" nowrap="nowrap"><?php echo $rec->telf; ?></td>
								</tr>
								<tr>
									<td><?php echo $spanCI."/".$spanRIF ?></td>
									<td class="tddata"><?php echo $rec->cedula_cliente; ?></td>
									<td>Tel&eacute;fono2:</td>
									<td class="tddata" nowrap="nowrap"><?php echo $rec->otrotelf; ?></td>
								</tr>
								<tr>
									<td>Direcci&oacute;n:</td>
									<td class="tddata" colspan="3"><?php echo $reccliente->direccion; ?></td>
								</tr>
								<tr>
									<td>Modelo:</td>
									<td class="tddata"><?php echo $recunidad->nom_modelo; ?></td>
									<td>Marca:</td>
									<td class="tddata"><?php echo $recunidad->nom_marca; ?></td>
								</tr>
								<tr>
									<td>Placa:</td>
									<td class="tddata"><?php echo $rec->placa; ?></td>
									<td><?php echo $spanKilometraje; ?>:</td>
									<td class="tddata"><?php echo $rec->kilometraje; ?></td>
								</tr>
								<tr>
									<td>Serial:</td>
									<td class="tddata" ><?php echo $rec->chasis; ?></td>
									<td>Nivel Combustible:</td>
									<td class="tddata" ><?php echo $nivel_combustible[strval($rec->nivel_combustible)]; ?></td>
								</tr>
								<tr>
									<td>Fecha Entrada:</td>
									<td class="tddata" ><?php echo parseDateToSql($rec->fecha_entrada); ?></td>
									<td>Hora Entrada:</td>
									<td class="tddata" ><?php echo parseTimeToSql($rec->hora_entrada); ?></td>
								</tr>
								<tr>
									<td>Fecha Estimada de Entrega:</td>
									<td class="tddata">
										<?php echo adodb_date(DEFINEDphp_DATE,$fecha_estimada_entrega); ?>
									</td>
									<td>Hora Estimada de Entrega:</td>
									<td class="tddata">
										<?php echo adodb_date(DEFINEDphp_TIME,$fecha_estimada_entrega); ?>
									</td>
								</tr>
							</table>							
						</td>
					</tr>
					<tr>				
					</tr>
				</tbody>
			</table>
			<!--
			<table style="width:100%;" >
				<caption>
					Inventario al Momento de la Recepci&oacute;n
				</caption>
				<tbody>
					<tr>
						<td valign="top">
							<table style="">
								<col style="text-align:center;" />
								<col style="text-align:center;" />
								<col />
								<thead>
									<tr >
										<td valign="top" colspan="3" align="left">
											<strong>El cliente se queda a la reparaci&oacute;n:</strong> <?php //echo $boleano[$rec->permanece_cliente_reparacion]; ?><br />
											<strong>El cliente desea Conservar piezas:</strong> <?php //echo $boleano[$rec->permanece_cliente_partes]; ?>
										</td>
									</tr>
									<tr style="font-weight:bold;">
										<td>Cant.</td>
										<td>Est.</td>
										<td>Descripci&oacute;n</td>
									</tr>
								</thead>
								<tbody>
							<?php
								/*if($recinventario){
									foreach($recinventario as $inv){
										echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
											$inv->cantidad,
											$inv->estado,
											$inv->descripcion
										);
									}
								}else{
									echo 'error';
								}*/
							?>
								</tbody>
							</table>
						</td>
						<td style="width:100px;text-align:right;" >
							<img border="0" style="height:300px;" alt="inventario_image" src="sa_inventario_vehiculo_image.php?rotate=90&id_cita=<?php //echo $rec->id_cita; ?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2" style="text-align:right;">
							 <img border="0" style="margin-right:1px;" src="<?php echo getUrl('img/iconos/golpe.png'); ?>" />Golpe / <img border="0" style="margin-right:1px;" src="<?php //echo getUrl('img/iconos/raya.png'); ?>" />Raya
						</td>
					</tr>
				</tbody>
			</table> -->
            
            <div class="noprint" style="float:left;">
            	<button onclick="aumentarEspacio();">
	                 <img title="Aumentar Espacios" src="<?php echo getUrl('img/iconos/add.png'); ?>" width="16" border="0">
                </button>
                 <button onclick="reducirEspacio();">
	                 <img title="Reducir Espacios" src="<?php echo getUrl('img/iconos/minus.png'); ?>" width="16" border="0">
                </button>
            </div>
            
			<table id="fallas" class="order_table" style="width:100%;">
				<col width="2%" />
				<tfoot>
					<tr>
						<td style="text-align:justify;" colspan="3">
							<hr />
							<strong>Observaciones:</strong> <?php echo _textprint($rec->observaciones); ?> 
						</td>
					</tr>
					<tr>
						<td style="text-align:justify;" colspan="3">
							<strong>El cliente se queda a la reparaci&oacute;n:</strong> <?php echo $boleano[$rec->permanece_cliente_reparacion]; ?><br />
											<strong>El cliente desea Conservar piezas:</strong> <?php echo $boleano[$rec->permanece_cliente_partes]; ?>
						</td>
					</tr>
				</tfoot>
				<thead>
					<tr>
						<td style="width:1%;">N</td>
						<td style="width:30%;">Falla</td>
						<td style="width:30%;">Diagn&oacute;stico</td>
						<td style="width:30%;">Respuesta</td>
					</tr>
				</thead>
				<tbody>
					<?php
					if($fallas){
						$count=1;
						foreach($fallas as $f){
							if($f->tiempo_diagnostico!=''){
								//$dt= "<strong>".ifnull($f->cedula_nombre_empleado).' Fecha: '.parseTiempo(str_tiempo($f->tiempo_diagnostico))."</strong><br />";
							}else{
								$dt='';
							}
							echo '<tr style="height: 80px;" class="trFallas"><td style="text-align:center;">'.$count.'</td><td>'._textprint($f->descripcion_falla).'</td><td class="diagnosticoFalla">'.$dt._textprint($f->diagnostico_falla).'</td><td class="respuestaFalla">'._textprint($f->respuesta_falla).'</td></tr>';
							$count++;
						}
					}
					?>
				</tbody>
			</table>
			<hr />
			<table style="width:100%;">
				<tbody>
					
					<tr>
						<td style="width:35%;" valign="bottom">
							<div align="center">Analista T&eacute;cnico: <?php echo $nombreEmpleadoOrden; ?></div>							
							
						</td>
						
						<td style="width:35%;text-align:right;" class="style1">
							
							<div align="center">CLIENTE: <?php echo $rec->apellido.' '.$rec->nombre; ?></div>
						</td>
					</tr>
				</tbody>
			</table>
            
			<div class="noprint" style="margin:auto;width:300px;text-align:center;">
            	<button onclick="window.print();">
                 <img title="Imprimir" alt="imprimir" src="<?php echo getUrl('img/iconos/print.png'); ?>" width="16" border="0">
                 &nbsp;Imprimir
               </button>
               <button onClick="ocultarDiagnostico();">
                 <img border="0" src="../img/iconos/ico_new.png" class="noprint ">
                 &nbsp;Blanco
               </button>            
			</div>
		</div>
	</body>
</html>