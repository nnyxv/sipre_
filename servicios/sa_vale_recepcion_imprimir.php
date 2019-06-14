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
		$fallas = $c->sa_recepcion_falla->doSelect($c,new criteria(sqlEQUAL,$c->sa_recepcion_falla->id_recepcion,$id_recepcion));
		
		//FIJOS:
		$sql = sprintf("SELECT * FROM sa_art_inventario 
						WHERE activo = 1 AND fijo = 1 AND id_art_inventario NOT IN (SELECT sa_re.id_art_inventario
																					FROM sa_recepcion_inventario sa_re 
																					WHERE sa_re.id_cita = %s)",
                        valTpDato($rec->id_cita,"int"));    
		$rs = mysql_query($sql);
		if (!$rs) { die(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
		
		$arrayInventarioFijo = array();
		while($row = mysql_fetch_assoc($rs)){
			$arrayInventarioFijo[] = strtoupper($row["descripcion"]);
		}
		
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
		                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Vale de Recepción</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    
		<script>
			
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
		</style>
	</head>
	<body <?php echo $loadpage; ?> >
		<div>
			<table style="width:100%">
				<tbody>
					<tr>
						<td class="maxtitle">VALE DE RECEPCI&Oacute;N</td>
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
									<td><?php echo $recempresa->telefono1.' / '.$recempresa->telefono2.' / '.$recempresa->telefono3.' / '.$recempresa->telefono4.' / '.$recempresa->telefono_taller1.' / '.$recempresa->telefono_taller2.' / '.$recempresa->telefono_taller3.' / '.$recempresa->telefono_taller4; ?></td>
								</tr>
                                <tr><td><?php echo $recempresa->contactos_taller; ?></td></tr>
                                <tr><td><?php echo 'Fax:'.$recempresa->fax; ?></td></tr>
								<tr>
									<td><?php echo $recempresa->direccion; ?></td>
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
									<td class="tddata" ><?php echo $nivel_combustible[strval(doubleval($rec->nivel_combustible))]; ?></td>
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
			<table style="width:100%;" cellpadding=0 cellspacing=0>
				<caption>
					Inventario al Momento de la Recepci&oacute;n
				</caption>
				<tbody>
					<tr>
						<td valign="top">
							<table cellpadding=0 cellspacing=0>
								<col style="text-align:center;" />
								<col style="text-align:center;" />
								<col />
								<thead>
									<tr >
										<td valign="top" colspan="3" align="left">
											<strong>El cliente se queda a la reparaci&oacute;n:</strong> <?php echo $boleano[$rec->permanece_cliente_reparacion]; ?><br />
											<strong>El cliente desea Conservar piezas:</strong> <?php echo $boleano[$rec->permanece_cliente_partes]; ?>
										</td>
									</tr>
									<tr style="font-weight:bold;">
                                    <?php if ($recinventario->getNumRows() > 0) { ?>
										<td>Cant.</td>
										<td>Est.</td>
										<td>Descripci&oacute;n</td>
                                    <?php } ?>
									</tr>
								</thead>
								<tbody>
							<?php
							
								if($recinventario){
									foreach($recinventario as $inv){
										$totalIventaroNormal++;
										echo sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td></td></tr>',
											$inv->cantidad,
											$inv->estado,
											$inv->descripcion
										);
									}									
								}else{
									echo 'error';
								}
								
								//nueva tabla para fijos
								echo "<tr><td colspan=\"4\">";//base tabla anterior
								
									echo "<table >";//tabña nueva de fijos
									if((count($arrayInventarioFijo) + $totalIventaroNormal) <= 12){//si es menor solo imprimo
										foreach($arrayInventarioFijo as $invFijo){
											echo sprintf('<tr><td>______</td><td>%s</td></tr>',
												$invFijo);
										}
									}else{
										$multiArray = array_chunk($arrayInventarioFijo, ceil(count($arrayInventarioFijo) / 2));//si se pasa divido en 2
										$primera = $multiArray[0];
										$segunda = $multiArray[1];
										
										foreach($primera as $key => $value){
	
											$html = "<tr>";
											$html .= "<td>______</td><td>".$primera[$key]."</td>";
											if(array_key_exists($key,$segunda)){
												$html .= "<td>&nbsp;&nbsp;&nbsp;______</td><td>".$segunda[$key]."</td>";
											}
											$html .= "</tr>";
											echo $html;
										}
									}
									echo "</table>";
								echo "</td></tr>";//base tabla anterior
							?>
								</tbody>
							</table>
						</td>
						<td style="width:100px;text-align:right;" >
							<img border="0" style="height:250px;" alt="inventario_image" src="sa_inventario_vehiculo_image.php?rotate=90&id_cita=<?php echo $rec->id_cita; ?>" />
						</td>
					</tr>
					<tr>
						<td colspan="2" style="text-align:right;">
							 <img border="0" style="margin-right:1px;" src="<?php echo getUrl('img/iconos/golpe.png'); ?>" />Golpe / <img border="0" style="margin-right:1px;" src="<?php echo getUrl('img/iconos/raya.png'); ?>" />Raya
						</td>
					</tr>
				</tbody>
			</table>
			<table style="width:100%;" cellpadding=0 cellspacing=0>
				<col width="2%" />
				<tbody>
					<tr>
						<td style="text-align:justify;" colspan="2">
							<strong>Observaciones:</strong> <?php echo $rec->observaciones; ?> 
						</td>
					</tr>
					
					<tr>
						<td style="text-align:justify;" colspan="2">
							<strong>Fallas:</strong>
							
						</td>
					</tr>
					<?php
					if($fallas){
						$count=1;
						foreach($fallas as $f){
							echo '<tr><td>'.$count.'</td><td>'.$f->descripcion_falla.'</td></tr>';
							$count++;
						}
					}
					?>
				</tbody>
			</table>
			<hr />
			<table style="width:100%;" cellpadding=0 cellspacing=0>
				<tbody>
					<tr>
						<td colspan="3" style="text-align:justify;">
                        <?php 
						$c=new connection();
						$c->open();
						$configEmpresa = $c->pg_configuracion_empresa;
						$recEmp=$configEmpresa->doSelect($c,new criteria(sqlAND,
															new criteria(sqlEQUAL,$configEmpresa->id_empresa,$rec->id_empresa),
															new criteria(sqlEQUAL,$configEmpresa->id_configuracion,103)));		
						if(!$recEmp){ echo "error: ".mysql_error()."<br>Line:".__LINE__; }						
						$c->close();
						echo str_replace("\n","<br>",$recEmp->valor);
						?>
						<!--Por medio de la presente autorizo los trabajos de reparaci&oacute;n mencionados junto con la instalaci&oacute;n de repuestos y materiales necesarios. Estoy de acuerdo que la empresa no es responsable por demora originada por indisponibilidad de piezas o retrasos de proveedor de las mismas. Estoy en conocimiento y as&iacute; lo acepto, que la empresa <?php // echo $recempresa->nombre_empresa; ?> (SER. Y REP.) no posee p&oacute; liza de seguro que cubra los eventuales da&ntilde;os que pudieran produc&iacute;rsele a mi veh&iacute;culo o a terceros, en dichas pruebas de carretera, ni tampoco que cubra la eventualidad de atracos y/o asaltos, ni los da&ntilde;os que pudieran causarle al veh&iacute;culo por disturbios callejeros, motines o similares, por lo cual estoy conforme con que dichos eventuales da&ntilde;os ser&aacute;n cubiertos en caso de producirse, por la p&oacute;liza de seguro que yo mantengo vigente sobre mi antes indicado veh&iacute;culo o en todo caso por s&iacute; mismo si es que no tuviere p&oacute;liza que cubriere tales da&ntilde;os o eventualidades. En consecuencia de lo anterior, eximo expresamente y de la manera m&aacute;s amplia a <?php // echo $recempresa->nombre_empresa; ?> (SER. Y REP.) por concepto de tales eventuales da&ntilde;os en caso que se produjeran, en el entendido, que estoy conforme con que se realicen tales pruebas de carretera por parte de la empresa y los eventuales da&ntilde;os que se le puedan causar al veh&iacute;culo corren bajo mi &uacute;nica y exclusiva responsabilidad.
                                                La empresa no se hace responsable por los trabajos realizados en los talleres, cuando los repuestos son suministrados por los clientes. -->
						</td>
					</tr>
					<tr>
						<td style="width:35%;" valign="bottom">
							<div align="center">Analista T&eacute;cnico</div>							
							<hr style="margin-top:30px;" />
							<div align="center"><?php echo $rec->asesor; ?></div>
						</td>
						<td style="" valign="top" class="style1">
							<p>ESTIMADO CLIENTE:</p>
							<p align="justify">Mucho agradeceremos se sirva declarar cualquier equipo, accesorio o aparato que tenga la unidad y que no sea equipo de f&aacute;brica. De no recibir ning&uacute;n tipo de notificaci&oacute;n de lo anterior, no nos hacemos responsables por deterioro, p&eacute;rdida total o parcial de dichos accesorios.</p>
						</td>
						<td style="width:35%;text-align:right;" class="style1">
							<div>HORARIO:</div>
							<div>Lun/Jue 08:00 a 12:00 y 01:30 a 05:30</div>
							<div>Viernes 08: a 12:00 </div>
							<div align="center">CLIENTE</div>
							<hr style="margin-top:30px;" />
							<div align="center"><?php echo $rec->apellido.' '.$rec->nombre; ?></div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</body>
</html>