<?php
@session_start();
define("bajaNORMAL",1);
define("bajaPARCIAL",2);
define("bajaCOMPLETA",3);
define("bajaFERIADO",4);
define('PAGE_PRIV','sa_registro_cita');
require_once("../inc_sesion.php");
require_once ("../connections/conex.php");

//implementando xajax;
        	
	require_once("control/main_control.inc.php");
	require_once("client_service.php");//imprescindible qyu sea despues
	require_once("control/funciones.inc.php");
	
	function load_page($id_calendario){
		$r= getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			$r->assign('tabla_asesores',inner,'Acceso denegado');
			return $r;
		}
		$calendario=setCalendar($id_calendario,'xajax_cargar_dia');
		$r->loadCommands($calendario);
		//	$r->alert($_SESSION['idEmpresaUsuarioSysGts']);
		return $r;
	}
	
	function cargar_dia($dia,$mes=null,$ano=null){
		global $semanas;
		$r= getResponse();
		if($mes == null && $ano==null){
			//convierte la fecha
			$ano=substr($dia,-4,4);
			$mes=substr($dia,3,2);
			$dia=substr($dia,0,2);
		}
		$ffecha=sprintf("%02s",$dia).'-'.sprintf("%02s",$mes).'-'.sprintf("%04s",$ano);
		$tfecha=adodb_mktime(0,0,0,$mes,$dia,$ano);
		$c=new connection();
		$c->open();
		$encabezado='';
		
		$tferiada=fecha_baja($c,$dia,$mes,$ano);
		//$r->alert($tferiada);
		//return $r;
		if($tferiada->getNumRows()!=0){
			//buscando fechas relevantes:
			foreach($tferiada as $v){
				//verificando si es un dia parcial o feriado
				if($v->tipo!="EMPLEADO"){
					$mensaje.=$v->tipo.' ('.$v->descripcion.')';
					$bajas=$v->getRow();
					
					//$r->alert(utf_export($bajas));
					
					$make_hora_fbaja=phpTime($v->hora_inicio_baja);
					$make_hora_fin_fbaja=phpTime($v->hora_fin_baja);
					//$r->alert(utf_export($make_hora_fin_fbaja));
				//empleados
				}elseif($v->tipo=='EMPLEADO'){
					//copia toda la data del horario de baja del empleado
					/*$baja_empleados[$v->id_empleado]=$v->getRow();
					$baja_empleados[$v->id_empleado]['make_hora_baja']=phpTime($v->hora_inicio_baja);
					$baja_empleados[$v->id_empleado]['make_hora_fin_baja']=phpTime($v->hora_fin_baja);     */
					
					$baja_empleados[$v->id_empleado][]=array(
						'row'=>$v->getRow(),
						'make_hora_baja'=>phpTime($v->hora_inicio_baja),
						'make_hora_fin_baja'=>phpTime($v->hora_fin_baja)
					);
				}
			}
		}
		
		//extrayendo el intervalo a la fecha:
		$sa_v_intervalo=$c->sa_v_intervalo;
		$qintervalo=new query($c);
		$qintervalo->add($sa_v_intervalo);
		$fechasql=field::getTransformType($ffecha,field::tDate);
		//$r->alert($fechasql);
		//return $r;
		$qintervalo
			->where(new criteria(sqlEQUAL,$sa_v_intervalo->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']))
			->where(
			new criteria(sqlOR,
				new betweenCriteria($fechasql,$sa_v_intervalo->fecha_inicio,$sa_v_intervalo->fecha_fin),
				new criteria(sqlAND,
						array(
							new criteria(" >= ",$fechasql,$sa_v_intervalo->fecha_inicio),
							new criteria(sqlIS,$sa_v_intervalo->fecha_fin,sqlNULL)
						)
					)
				)
			)->setLimit(1);
		$interval=$qintervalo->doSelect();
		
		//leer los acumuladores:
		$rec_ac=$c->sa_v_acumulador_cita->doSelect($c, new criteria(sqlAND,array(
			new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']),
			new criteria(sqlEQUAL,'fecha_cita',$fechasql)
		)));
		if($rec_ac){
			//$r->alert($rec_ac->getNumRows());
			foreach($rec_ac as $acum){
				//$r->alert($acum->id_empleado_servicio.' '.$acum->acumulado);
				$acumulado[$acum->id_empleado_servicio]=$acum->acumulado;
				$acumulado_total+=$acum->acumulado;
			}
		}
		
		$semana=adodb_date('N',$tfecha);
		/*if($semana==7){//domingo
			$r->alert("AVISO: ha seleccionado un DOMINGO");
			$domingo=true;
			
		}*/
		if($semana>$interval->dias_semana){//domingo
			$r->alert("AVISO: ha seleccionado un periodo No Disponible");
			$domingo=true;
			
		}
		if($interval->getNumRows()==0){
			$r->assign("tabla_asesores",'innerHTML','No se ha definido un intervalo para el periodo');
		}else{
			
			$asesores=$c->sa_v_asesores_servicio->doSelect($c,
				new criteria(sqlAND,
					new criteria(sqlEQUAL,$c->sa_v_asesores_servicio->activo,1),
					new criteria(sqlEQUAL,$c->sa_v_asesores_servicio->id_empresa,$_SESSION['idEmpresaUsuarioSysGts'])
				));

			if(!$asesores->getNumRows()==0){
				$html='<table  class="asesor_list" cellpadding="0" cellspacing="0"><thead><tr><td >Horas</td>';
				foreach($asesores as $asesor){
					$html.='<td nowrap="nowrap"><a href="#" style="color:blue" onclick="mostrar_asesor('.$asesor->id_empleado.')" >'.$asesor->nombre_completo.'</a></td>';
				}
				$html.='</tr></thead><tbody>';
					
				//imprimiendo los intervalos:
				$hora_inicio=$interval->hora_inicio_jornada_h;
				$minuto_inicio=$interval->hora_inicio_jornada_m;
				$make_hora=adodb_mktime($hora_inicio,$minuto_inicio);
				//$make_hora=strtotime($interval->hora_inicio_jornada);//adodb_mktime($hora_inicio_baja,$minuto_inicio_baja);
				
				//reserva
				$creservar=$interval->reservar_cada_intervalo;
				$ireserva=0;
				
				$duracion_jornada=$interval->duracion_jornada;
				$intervalo=$interval->intervalo;
				$lapsos = $duracion_jornada / $intervalo;
				
				$hora_inicio_baja=$interval->hora_inicio_baja_h;
				$minuto_inicio_baja=$interval->minuto_inicio_baja_m;
				
				$make_hora_baja=adodb_mktime($hora_inicio_baja,$minuto_inicio_baja);				
				$duracion_baja=$interval->duracion_baja;
				$make_hora_fin_baja=adodb_mktime($hora_inicio_baja,intval($minuto_inicio_baja)+$duracion_baja);
				
				//VOLCANDO LAS CITAS:
				$qcita=$c->getQuery($c->sa_v_datos_cita);
				$rcita=$qcita->
					where(new criteria(sqlEQUAL,$c->sa_v_datos_cita->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']))->
					where(new criteria(sqlEQUAL,$c->sa_v_datos_cita->fecha_cita,$fechasql))->
					where(new criteria(sqlNOTEQUAL,$c->sa_v_datos_cita->estado_cita,"'POSPUESTA'"))->
					where(new criteria(sqlNOTEQUAL,$c->sa_v_datos_cita->estado_cita,"'CANCELADA'"))->
					orderBy($c->sa_v_datos_cita->hora_inicio_cita)->
					doSelect();
					
				
				if($rcita->getNumRows()!=0){
					foreach($rcita as $cita){
						$data_cita=$cita->getRow();
						$data_cita['hora_inicio_cita']=phpTime($cita->hora_inicio_cita);
						$data_cita['hora_fin_cita']=phpTime($cita->hora_fin_cita);
						$cita_marcada[$cita->id_cita]=false;
						$cita_empleado[$cita->id_empleado_servicio][]=$data_cita;
					}
				}
				
				//INICIANDO EL CICLO
				$par="";
				for($i=0;$i<=$lapsos;$i++){
					$classrow="tr";
					if($par==""){
						$par="_impar";
					}else{
						$par="";
					}
					$reservar=false;
					if($creservar!=0){
						if($ireserva==$creservar){
							$ireserva=0;
							$reservar=true;
							$classreserva="_reserva";
						}else{
							$ireserva++;
							$classreserva="";
						}
					}
					$row_baja=false;
					
					//horas de baja
					if($make_hora>=$make_hora_baja && $make_hora<$make_hora_fin_baja){
						$classrow="tr_cita_baja";
						$row_baja=bajaNORMAL;
					}
					
					//fechas de baja
					if($bajas!=null || $domingo){
					
					//$r->alert(utf_export($bajas));
					
						if($bajas['tipo']=='FERIADO' || $domingo){
							$row_baja=bajaFERIADO;
							$classrow="tr_cita_baja_feriado";
						}else{
							if($bajas['parcial']==0){
								$row_baja=bajaFERIADO;
								$classrow="tr_cita_baja_feriado";
							//05 de agosto de 2009
							}else{
								if($make_hora>=$make_hora_fbaja && $make_hora<$make_hora_fin_fbaja ){
									$classrow="tr_cita_baja_parcial";
									$row_baja=bajaPARCIAL;
								}
							}
						}
					}
					//$r->alert($classrow);
					
					$html.='<tr class="'.$classrow.$classreserva.$par.'">';
						$html.='<td nowrap="nowrap">'.adodb_date(DEFINEDphp_TIME,$make_hora).'</td>';
						//cargar citas asesores:
							foreach($asesores as $asesor){
								$classtd="";
								/*$baja = $baja_empleados[$asesor->id_empleado];
								$td_baja='';
								if($baja!=null){
									if($make_hora>=$baja_empleados[$asesor->id_empleado]['make_hora_baja'] && $make_hora<$baja_empleados[$asesor->id_empleado]['make_hora_fin_baja']){
										$classtd="td_cita_baja";
										$td_baja=bajaPARCIAL;
									}elseif($baja_empleados[$asesor->id_empleado]['hora_inicio_baja']==""){
										$classtd="td_cita_baja";
										$td_baja=bajaCOMPLETA;
									}
								}*/
								$baja = $baja_empleados[$asesor->id_empleado];
								$td_baja='';
								if($baja!=null){
									
									foreach($baja as $b){
										
										if($make_hora>=$b['make_hora_baja'] && $make_hora<$b['make_hora_fin_baja']){
											$classtd="td_cita_baja";
											$td_baja=bajaPARCIAL;
										}elseif($b['row']['hora_inicio_baja']==""){
											$classtd="td_cita_baja";
											$td_baja=bajaCOMPLETA;
											//$r->alert(utf8_encode(var_export($b ,true)));
										}
										
									}
								
								}
								$data_cita='';
								$event='';
								$lapzo_cita=1;
								$marcar=true;
								//VERIFICANDO CITAS:
								$citas=$cita_empleado[$asesor->id_empleado];
								//$estado_cita='';
								if($citas!=null){
									foreach($citas as $cita){
										//$marcar=!$cita_marcada[$cita['id_cita']];								
										if($make_hora>=$cita['hora_inicio_cita'] && $make_hora<$cita['hora_fin_cita']){
										//$r->alert(adodb_date(DEFINEDphp_TIME,$make_hora));
											if(!$cita_marcada[$cita['id_cita']]){
												$duracion_cita=intval((adodb_date('H',$cita['hora_fin_cita']-$cita['hora_inicio_cita'])*60)+adodb_date('i',$cita['hora_fin_cita']-$cita['hora_inicio_cita']));
												//$r->alert((adodb_date('H',$cita['hora_fin_cita']-$cita['hora_inicio_cita'])*60));
												//$lapzo_cita = $duracion_cita / $intervalo;//original:evaluar
												$lapzo_cita=1;
												$cita_marcada[$cita['id_cita']]=true;
												//$estado_cita=$cita[];
												$marcar=true;
												//$data_cita='<div class="cita_'.$cita['estado_cita'].'">'.$asesor->id_empleado.' idc:'.$cita['id_cita'].' '.$duracion_cita.' l:'.$lapzo_cita.'</div>';
												$event='onclick="cargar_cita(\''.$dia.'\',\''.$mes.'\',\''.$ano.'\',\''.$make_hora.'\',\''.$asesor->id_empleado.'\',\''.$reservar.'\',\''.$row_baja.'\',\''.$td_baja.'\',\''.$intervalo.'\',\''.$cita['id_cita'].'\');"';
												
												$imgCargadaAgenda = "";
												if($cita['carga_agenda']){
													$imgCargadaAgenda = " <img style='margin-bottom:-4px;' src='../img/iconos/application_view_columns.png' />";
												}
												
												if($cita['origen_cita'] == "ENTRADA"){
                                                                                                    $data_cita='<div class="info_edit" ><div class="cita_ENTRADA_SOLA">#'.$cita['numero_cita'].' '.$cita['apellido'].' '.$cita['nombre'].': '.$cita['placa'].$imgCargadaAgenda.'</div></div>';
						 //.$cita['id_cita'].
                                                                                                }else{
                                                                                                    $data_cita='<div class="info_edit" ><div class="cita_'.$cita['origen_cita'].'_'.$cita['estado_cita'].'">#'.$cita['numero_cita'].' '.$cita['apellido'].' '.$cita['nombre'].': '.$cita['placa'].$imgCargadaAgenda.'</div></div>';												   //.$cita['id_cita'].
                                                                                                }
                                                                                                //$data_cita='<div class="info_edit" ><div class="cita_'.$cita['origen_cita'].'_'.$cita['estado_cita'].'">#'.$cita['id_cita'].' '.$cita['apellido'].' '.$cita['nombre'].': '.$cita['placa'].'</div></div>';
												break; //sale para que no se registre otra vez
											}else{
												$marcar=false;												
											}
										}//else{
										//	$data_cita='<div class="info"><div class="a_cita_new" onclick="cargar_cita();"></div></div>';	
										//}
									}
								}//else{
								if($data_cita==''){
									$event='onclick="cargar_cita(\''.$dia.'\',\''.$mes.'\',\''.$ano.'\',\''.$make_hora.'\',\''.$asesor->id_empleado.'\',\''.$reservar.'\',\''.$row_baja.'\',\''.$td_baja.'\',\''.$intervalo.'\');"';
									$data_cita='<div class="info"  ><div class="a_cita_new" ></div></div>';
								}
								
								//$data_cita=$classrow.$classreserva.$par;
								if($marcar){
									$html.='<td rowspan="'.$lapzo_cita.'" class="'.$classtd.$par.'" '.$event.'>'.$data_cita.'</td>';
								}
							}
						//incrementa la hora:
						$make_hora=adodb_mktime(adodb_date('G',$make_hora),intval(adodb_date('i',$make_hora))+$intervalo);
					$html.='</tr>';
				}
				//acumulados
				$base_ut=getBaseUt($_SESSION['idEmpresaUsuarioSysGts'],$c);//&id_empresa�?
				foreach($asesores as $asesor){
					//$r->alert($acumulado[$asesor->id_empleado]);
					$minutos=$acumulado[$asesor->id_empleado]*60/$base_ut;
					$acum_as.='<td>('.ifnull(minToHours($minutos),'0').' hr.)</td>';
				}
				//leyendo los tiempos seg�n mec�nicos:
				$rec_ut=$c->sa_v_acumulador_mecanico->doSelect($c,new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']));
				
				if($rec_ut->getNumRows()!=1){
					//$r->script("_alert('No se han especificado mec&aacute;nicos para calcular las horas Comprometidas');");
					$r->assign('ut_concesionario',inner,'No se han especificado mec&aacute;nicos para calcular las horas Comprometidas');
				}else{	
					$minutos_planta=$rec_ut->total_ut_planta*60/$base_ut;
					$minutos_concesionario=$rec_ut->total_ut_concesionario*60/$base_ut;
					$r->assign('ut_planta',inner,'('.ifnull(minToHours($minutos_planta),'0').' hr.)');
					$r->assign('ut_concesionario',inner,'('.ifnull(minToHours($minutos_concesionario),'0').' hr.)');
				}
				if($acumulado_total>$rec_ut->total_ut_planta || $acumulado_total>$rec_ut->total_ut_concesionario){
					$color='#FF0000';
				}else{
					$color='#000000';
				}
				$r->script("obj('capa_total_dia').style.color='".$color."';");
				/*$r->script("obj('capa_ut_planta').style.color='".$color."';");
				if($acumulado_total>$rec_ut->total_ut_concesionario){
					$color='#FF0000';
				}else{
					$color='#000000';
				}
				$r->script("obj('capa_ut_concesionario').style.color='".$color."';");*/
				
				//$html.='<tr></tr>';
				$total_acum='('.ifnull(minToHours(($acumulado_total*60/$base_ut)),'0').' hr.)';
				$r->assign('capa_total_dia',inner,$total_acum);
				$html.='<tr><td nowrap="nowrap">Total:</td>'.$acum_as.'</tr></tbody></table>';
			}else{
				$html='No se han definido asesores de servicio activos para la empresa';
			}
			$r->assign("tabla_asesores",'innerHTML',$html);
			$c->close();
		}
		//if($mensaje!=null){
			$r->assign("mensajes","innerHTML",$semanas[$semana].', <strong>'.$ffecha.'</strong> '.ifnull($mensaje));
		//}else{
		//	$r->assign("mensajes","innerHTML",$semanas[$semana].', <strong>'.$ffecha.'</strong> ');
		//}
		
		$r->assign('intervalo','innerHTML',ifnull($intervalo));
		$r->assign('fecha_inicio','innerHTML',parseDate(phpDate($interval->fecha_inicio)));
		$r->assign('fecha_fin','innerHTML',parseDate(phpDate($interval->fecha_fin)));
		//global $cadenas;
		//$r->alert($cadenas);
		return $r;
	}
	
	function mostrar_asesor($id_empleado,$id_tag='ref_asesor',$id_tag_asesor='vista_asesor'){
		$response= getResponse();
		$c = new connection();
		$c->open();
		$q=$c->getQuery($c->pg_empleado,new criteria(sqlEQUAL,$c->pg_empleado->id_empleado,$id_empleado));
		$r=$q->doSelect();
		
		$html='
			<a style="position:absolute;top:-11px;right:-11px;display:none;" id="close_asesor_b" class="'.getClassModalButtonClose().'" href="#"><img src="'.getUrl('img/iconos/multibox_close.png').'" border="0" /></a>
			<center><img id="asesor_image" border="0" src="%s" style="max-height:350px;max-width:350px;" /></center>
			
			
			<table border="0" class="table_max" style="margin-top:10px;" id="datos_asesor" >
			<tr>
				<td>Apellido:</td>
				<td>%s</td>
			</tr>
			<tr>
				<td>Nombre:</td>
				<td>%s</td>
			</tr>
			<tr>
				<td>Telefono:</td>
				<td>%s</td>
			</tr>
			<tr>
				<td>Celular:</td>
				<td>%s</td>
			</tr>
			<tr>
				<td>Email:</td>
				<td>%s</td>
			</tr>
		</table>';
		if($r->foto == NULL || $r->foto == ""){//foto del asesor si se usara, sino por defecto envio una gregor
			$foto = "../img/iconos/people1.png";
		}else{
			$foto = $r->foto;	
		}
		$html=sprintf($html,$foto,$r->apellido,$r->nombre_empleado,$r->telefono,$r->celular,$r->email);
		
		
		$response->assign($id_tag_asesor,'innerHTML',$html);
		//$response->script("");
		$response->script("modalAsesor('#".$id_tag_asesor."');");
		$c->close();
		return  $response;
	}	
	
	function cargar_cliente($rec){
		$r=getResponse();
                
                if($rec->lci == NULL || $rec->lci == ""){
                    $cedulaMostrarInput = $rec->ci;
                }else{
                    $cedulaMostrarInput = $rec->lci."-".$rec->ci;
                }
		//$r->aleert('d');
		$r->assign('id_cliente_contacto','value',$rec->id);
		$r->assign('cedula_cliente','value',$cedulaMostrarInput);
		$r->assign('nombre_cliente','innerHTML',$rec->nombre.' '.$rec->apellido);
		//$r->assign('apellido_cliente','innerHTML',$rec->apellido);
		$r->assign('telefono_cliente','innerHTML',$rec->telf);
		$r->assign('celular_cliente','innerHTML',$rec->otrotelf);
		$r->assign('email_cliente','innerHTML',$rec->correo);
		$c = new connection();
		$c->open();
		//buscar registro de unidades del cliente:
		$recp= $c->sa_v_placa->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_placa->id_cliente_registro,$rec->id));
		if($recp){
			if($recp->getNumRows()!=0){				
				foreach($recp as $v){
					$html.=sprintf('<tr class="%s" onclick="xajax_buscarPlaca(\'%s\',\'cita\',\'cargar_placa\','.ifnull($recp->id_registro_placas).');"><td>%s</td><td>%s</td><td>%s</td></tr>',
						$par,$recp->placa, $recp->placa,
						$recp->nombre_unidad_basica,
						$recp->nom_marca.' '.$recp->nom_modelo.' '.$recp->nom_version
					);
					if($par==''){
						$par='impar';
					}else{
						$par='';
					}
				}
				$r->assign("lista_unidad_cliente",inner,'<table class="order_table"><thead><tr><td>Placa</td><td>Unidad</td><td>Descripci&oacute;n</td></tr></thead><tbody>'.$html.'</tbody></table>');
				$r->script('setCenter("unidad_cliente_window",true);
				setWindow("unidad_cliente_window","title_unidad_cliente_window");
				//obj("unidad_cliente_window").style.zIndex=0;
				');
			}
		}
		$c->close();
		return $r;
	}
	
	function cargar_placa($rec){
		$r=getResponse();
		if($rec instanceof recordset){
			/*xajaxValue($r,$rec,'id_modelo');
			xajaxValue($r,$rec,'id_unidad_fisica');
			xajaxValue($r,$rec,'chasis');
			xajaxValue($r,$rec,'placa');
			xajaxValue($r,$rec,'kilometraje');
			xajaxValue($r,$rec,'color');
			xajaxValue($r,$rec,'id_transmision');
			xajaxValue($r,$rec,'id_combustible');
			xajaxValue($r,$rec,'ano');*/
			
			xajaxValue($r,$rec,'id_registro_placas');
			xajaxValue($r,$rec,'placa','placa_vehiculo');
			xajaxInner($r,$rec,'nom_modelo','modelo_vehiculo');
			xajaxInner($r,$rec,'chasis','chasis_vehiculo');
			xajaxInner($r,$rec,'nom_combustible','combustible_vehiculo');
			xajaxInner($r,$rec,'kilometraje','kilometraje_vehiculo');
			xajaxInner($r,$rec,'color','color_vehiculo');
			xajaxInner($r,$rec,'nom_transmision','transmision_vehiculo');
			xajaxInner($r,$rec,'ano','ano_vehiculo');
			$r->script("close_window('unidad_cliente_window');");
			
		}
		return $r;
	}
	
	function cargar_cita($d,$m,$a,$make_hora,$id_empleado,$entrada,$estado_dia,$estado_asesor,$intervalo,$id_cita=null,$modo=0){
		$resp = getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			//$r->alert('acceso denegado');
			return $r;
		}
		$ffecha=sprintf("%02s",$d).'-'.sprintf("%02s",$m).'-'.sprintf("%04s",$a);
		$tfecha=adodb_mktime(0,0,0,$m,$d,$a);
		$hoy= adodb_mktime(0,0,0,adodb_date('m'),adodb_date('d'),adodb_date('Y'));
		
		//calcular diferencias		
		$daydiff= ($tfecha-$hoy)/60/60/24;
		
		
		$make_hora_fin=adodb_mktime(adodb_date('G',$make_hora),intval(adodb_date('i',$make_hora))+$intervalo);
		
		//verificando si no es la fecha de hoy para no permitir entradas:
		if($tfecha>$hoy){
			$entrada=false;
			$resp->script("obj('origen_citaENTRADA').disabled=true;");
		}else{
			$resp->script("obj('origen_citaENTRADA').disabled=false;");			
		}
	
		$c=new connection();
		$c->open();
		//datos basicos del empleado
		$empl=$c->pg_empleado->doSelect($c,new criteria(sqlEQUAL,$c->pg_empleado->id_empleado,$id_empleado));
		//para crear las citas:
		if ($id_cita==''){
			if($modo==0){
				//abriendo el dialogo persistente:
				$resp->script("modalPersistWindow('#cita',600,440);");
				$resp->script('$("#f_cita input,#f_cita select").removeClass("inputNOTNULL");');
				
				if($entrada){
					$resp->script("obj('origen_citaENTRADA').checked=true;");
				}else{
					$resp->script("obj('origen_citaPROGRAMADA').checked=true;");
				}
				$resp->script("obj('cedula_cliente').focus();");
				
				$resp->assign('fecha_cita','value',$ffecha);
				$resp->assign('id_empleado_servicio','value',$id_empleado);
				
				$resp->assign('asesor','innerHTML',$empl->apellido.' '.$empl->nombre_empleado);
				
				
				$resp->assign('hora_inicio_cita','value',adodb_date(DEFINEDphp_TIME,$make_hora));
				$resp->assign('hora_fin_cita','value',adodb_date(DEFINEDphp_TIME,$make_hora_fin));
				$resp->assign('form_fecha_solicitud',inner,adodb_date(DEFINEDphp_DATE,$hoy).' ('.$daydiff.' d&iacute;as)');
				
				$resp->assign('clienteEligioFechaSI','checked',false);
				$resp->assign('clienteEligioFechaNO','checked',false);
				
				if($entrada == 1){
					$resp->script("$('#tr_cliente_selecciono').hide();");
				}else{
					$resp->script("$('#tr_cliente_selecciono').show();");	
				}
				
			}elseif($modo==1){
				
				$resp->assign('cita_post_id_empleado_servicio','value',$id_empleado);
				$resp->assign('cita_post_asesor','innerHTML',$empl->apellido.' '.$empl->nombre_empleado);
				$resp->assign('cita_post_fecha_cita','value',$ffecha);
				$resp->assign('cita_post_dias',inner,'('.$daydiff.' d&iacute;as)');
				$resp->assign('cita_post_hora_inicio_cita','value',adodb_date(DEFINEDphp_TIME,$make_hora));
				$resp->assign('cita_post_hora_fin_cita','value',adodb_date(DEFINEDphp_TIME,$make_hora_fin));
				$resp->script('obj("button_posponer").style.display="inline";');

			}
		}else{
			//cargando los datos de la cita:
			
			
			$rec=$c->sa_v_datos_cita->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_datos_cita->id_cita,$id_cita));
			
				$resp->assign('cita_post_id_empleado_servicio','value','');
				$resp->assign('cita_post_asesor','innerHTML','');
				$resp->assign('cita_post_fecha_cita','value','');
				$resp->assign('cita_post_hora_inicio_cita','value','');
				$resp->assign('cita_post_hora_fin_cita','value','');
				$resp->script('obj("button_posponer").style.display="none";');
			
			//cita procesada, no se puede posponer:
			
			/*if($rec->estado_cita=='PROCESADA' && $modo!=2){
				$resp->script('$("#cita_edit_posponer").hide();');
			}else{
				$resp->script('$("#cita_edit_posponer").show();');			
			}*/
			
			$daydiff= (str_date($rec->fecha_cita)-str_date($rec->fecha_solicitud))/60/60/24;
			$fsolicitud=parseDateToSql($rec->fecha_solicitud).' ('.$daydiff.' d&iacute;as)';
			$resp->assign('cita_edit_cliente','innerHTML',$rec->apellido.' '.$rec->nombre);
			$resp->assign('cita_edit_ci','innerHTML',$rec->cedula_cliente);
			$resp->assign('cita_edit_cliente_telefonos','innerHTML','Telf: '.$rec->telf.' / '.$rec->otrotelf);
			$resp->assign('cita_edit_vehiculo','innerHTML',$rec->placa);
			$resp->assign('cita_edit_modelo','innerHTML',ModeloUnidad($rec->placa,0));
			$resp->assign('cita_edit_uniBas','innerHTML',ModeloUnidad($rec->placa,1));
			$resp->assign('ecita_edit_fecha_cita','innerHTML',parseDateToSql($rec->fecha_cita));
			$resp->assign('cita_edit_fecha_solicitud','innerHTML',$fsolicitud);
			$resp->assign('cita_edit_hora_inicio_cita','innerHTML',$rec->hora_inicio_cita_12);
			
			$botonLlegada = "<img style='float:right; padding-right:4px; cursor:pointer;' title='Establecer Hora de Llegada' onClick='xajax_horaLlegadaCliente(".$rec->id_cita.")'  src='../img/iconos/cita_programada.png' >";
																		//antes tiempo_cliente_12
			$horaLlegadacliente = ($rec->tiempo_llegada_cliente) ? date("h:i A",strtotime($rec->tiempo_llegada_cliente)) : NULL;
			$resp->assign('cita_edit_hora_llegada_cliente','innerHTML', $horaLlegadacliente." &nbsp;".$botonLlegada);
			
			$resp->assign('cita_edit_estado','innerHTML',$rec->estado_cita);
			$resp->assign('cita_edit_origen','innerHTML',$rec->origen_cita);
			//$resp->assign('cita_edit_id','innerHTML',$rec->id_cita);
			$resp->assign('cita_edit_id','innerHTML',$rec->numero_cita);
			$resp->assign('cita_edit_asesor','innerHTML',$rec->asesor);
			if($rec->descripcion_submotivo!=''){
				$submotivot='<br />'.$rec->descripcion_submotivo;
			}
			$resp->assign('cita_edit_motivo','innerHTML',ifnull($rec->motivo).$submotivot);
			$resp->assign('cita_edit_motivo_detalle','innerHTML',ifnull($rec->motivo_detalle));
			
			$resp->assign('cita_edit_id_cita','value',$rec->id_cita);
			$resp->assign('cita_edit_fecha_cita','value',parseDateToSql($rec->fecha_cita));
			if($modo!=2){
				$confirmar=($rec->estado_cita=='PENDIENTE') ? 'inline' : 'none';
				$cancelada=($rec->estado_cita=='CANCELADA' || $rec->estado_cita=='RETRAZADA' || $rec->estado_cita=='PROCESADA') ? 'none' : 'inline';
				$posponer=($rec->estado_cita=='PROCESADA' || $rec->estado_cita=='RETRAZADA') ? 'none' : 'inline';
			}else{
				$confirmar='none';
				$cancelada='none';
				$posponer='none';
			}
			
			$resp->script('obj("button_confirmar").style.display="'.$confirmar.'";');			
			$resp->script('obj("button_cancelar").style.display="'.$cancelada.'";');
			$resp->script('obj("cita_edit_posponer").style.display="'.$posponer.'";');
			
			
		
			
			
			//verificando el tipo de cita, para activar la confirmaci�n o postergacion
			$resp->script('setWindow("cita_editar","cita_edit_title",false);setCenter("cita_editar",true,true);//setOriginalCenter("cita_editar",true);
			modo_cita=1;');
		}
		$c->close();
		$resp->script("$('#simplemodal-container').css('height', 'auto'); $(window).trigger('resize.simplemodal');");
		return $resp;
	}
	
	
	function ModeloUnidad($placa,$ModeloUnidad) {
	
	$sqlDetOrden = sprintf("SELECT *
							FROM sa_v_placa
							WHERE placa LIKE '".$placa."';");
	$rsDetOrden = mysql_query($sqlDetOrden);
	if (!$rsDetOrden) return array(false, mysql_error()."\n\nLine: ".__LINE__);
	$rowDetOrden = mysql_fetch_array($rsDetOrden);
	if($ModeloUnidad==0){
	$valor = $rowDetOrden['nom_modelo'];}
	else{$valor = $rowDetOrden['nombre_unidad_basica'];}
	
	return($valor);
}

	
	function editar_cita($form, $estate='CANCELADA',$recall=false){
		$r = getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
			//$c->rollback();
			return $r;
		}
		$c=new connection();
		$c->open();
		if($estate=='CONFIRMADA' || $estate=='CANCELADA' || $estate=='POSPUESTA'){
		//lee la cita
			$reco=$c->sa_cita->doSelect($c,new criteria(sqlEQUAL,$c->sa_cita->id_cita,$form['cita_edit_id_cita']));
			if($estate!='CANCELADA' || $estate!='CONFIRMADA'){
				//verifica disponibilidad
				if($recall==false){
					$callback="xajax_editar_cita(xajax.getFormValues('editar_cita'),'".$estate."',true);";
					if(!validar_submotivo($callback,$c,$reco->id_submotivo,$reco->id_motivo_cita,$form['cita_post_fecha_cita'],$r,$reco->id_cita)){
						//$r->alert('true');
						return $r;
					}
				}
			}
			$sa_cita=new table("sa_cita");
			$sa_cita->add(new field('id_cita','',field::tInt,$form['cita_edit_id_cita']));
			$sa_cita->add(new field('estado_cita','',field::tString,$estate));
			if($estate=='CONFIRMADA'){				
				$sa_cita->add(new field('tiempo_confirmacion','',field::tFunction,'NOW()'));
			}
			$c->begin();
			$result=$sa_cita->doUpdate($c,$sa_cita->id_cita);
			$act='cita_edit_fecha_cita';
			if($result===true){
				if($estate=='POSPUESTA'){
					
					
					
					
					//copia la cita en un nuevo registro
					
					$sa_ncita=new table('sa_cita');			
					
					//numeracion citas gregor
					/*
					$sql_numero_cita = sprintf("SELECT IFNULL(MAX(numero_cita)+1, 1) as numero_cita from sa_cita WHERE
												id_empresa = %s LIMIT 1", 
													valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
					$rs_sql = mysql_query($sql_numero_cita);
					if (!$rs_sql) return $r->alert(mysql_error()."Error generando numero de cita pospuesta \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
					$dt_sql = mysql_fetch_assoc($rs_sql);				
					$numero_cita = $dt_sql["numero_cita"];*/
					
					$sqlNumeroCita= sprintf("SELECT * FROM pg_empresa_numeracion
					WHERE id_numeracion = 31
						AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
					ORDER BY aplica_sucursales DESC
					LIMIT 1",
					valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"),
					valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
					$rsSql = mysql_query($sqlNumeroCita);
					if (!$rsSql) return $r->alert(mysql_error()."\n Error buscando numero de cita editar \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
					$dtSql = mysql_fetch_assoc($rsSql);
					
					$idEmpresaNumeroCita = $dtSql["id_empresa_numeracion"];
					$numeroCita = $dtSql["numero_actual"];	
					
					if($numeroCita == NULL){ return $r->alert("No se pudo crear el numero de cita, compruebe que la empresa tenga numeracion de citas");}				
					
					//CUANDO POSPONE CREA UNA NUEVA CITA
					
					$sa_ncita->add(new field('numero_cita','',field::tInt,$numeroCita,true));
					
					$sa_ncita->add(new field('id_cita','',field::tInt,null,true));
					$sa_ncita->add(new field('hora_inicio_cita','',field::tTime,$form['cita_post_hora_inicio_cita'],true));
					$sa_ncita->add(new field('hora_fin_cita','',field::tTime,$form['cita_post_hora_fin_cita'],true));
					$sa_ncita->add(new field('fecha_cita','',field::tDate,$form['cita_post_fecha_cita'],true));
					$sa_ncita->add(new field('motivo_detalle','motivo_detalle',field::tString,$reco->motivo_detalle,false));
					$sa_ncita->add(new field('id_motivo_cita','',field::tInt,$reco->id_motivo_cita,false));
					if($reco->id_submotivo!=''){
						$sa_ncita->add(new field('id_submotivo','',field::tInt,$reco->id_submotivo,false));
					}
					$sa_ncita->add(new field('fecha_solicitud','',null,new _function('CURRENT_DATE',null),true));
					$sa_ncita->add(new field('id_registro_placas','placa_vehiculo',field::tInt,$reco->id_registro_placas,true));
					$sa_ncita->add(new field('id_cliente_contacto','cedula_cliente',field::tInt,$reco->id_cliente_contacto,true));
					$sa_ncita->add(new field('id_empresa','',field::tInt,$reco->id_empresa,true));
					$sa_ncita->add(new field('acumulador','',field::tFloat,$reco->acumulador,true));
					$sa_ncita->add(new field('origen_cita','',field::tString,'PROGRAMADA',true));
					$sa_ncita->add(new field('estado_cita','',field::tString,'PENDIENTE',true));
					$sa_ncita->add(new field('id_empleado_servicio','',field::tInt,$form['cita_post_id_empleado_servicio'],true));
					if($reco->tiempo_llegada_cliente != NULL || $reco->tiempo_llegada_cliente != ""){
						$sa_ncita->add(new field('tiempo_llegada_cliente','',null,$reco->tiempo_llegada_cliente,false));
					}
					
					$sa_ncita->add(new field('selecciono_fecha','',field::tInt,$reco->selecciono_fecha,true));
					
					$resulti=$sa_ncita->doInsert($c,$sa_ncita->id_cita);
					if($resulti===true){
						
						
						// ACTUALIZA numero cita
						$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
						WHERE id_empresa_numeracion = %s;",
							valTpDato($idEmpresaNumeroCita, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $r->alert(mysql_error()."\n\nLine: ".__LINE__); }
						
						
						$r->alert('La cita ha sido '.$estate.' Correctamente');
						$act='cita_post_fecha_cita';
					}else{
						foreach($resulti as $ex){
							//var_dump($ex);
							return $r->alert($ex->getObject()->getName()); //no funciona dice que no es objeto? usa es msj de error
							return $r->alert("Error ".$ex->errorMsq."\n Error Nº".$ex->numero."\n Linea: ".__LINE__);//gregor
							
						}
					}					
				}else{
					$r->alert('La cita ha sido '.$estate);
				}
				$c->commit();
				
				if($estate=='CONFIRMADA'){
					//enviando correo
					error_reporting(0);
					$ret=sendMailCita($form['cita_edit_id_cita']);
					
					if($ret){
						$r->alert("Se ha enviado el correo al cliente perfectamente");
					}else{
						$r->alert("No se pudo enviar el correo al cliente.");
					}
					
					/*if($ret == "SMTP Error: Could not authenticate."){
						$r->alert("No se pudo enviar el correo al cliente por autentiaccion del servidor");	
					}else{
						$r->alert("Se ha enviado el correo al cliente perfectamente");	
					}*/
					
				}
				$r->loadCommands(cargar_dia($form[$act]));
				$r->script('cerrar("cita_editar");');
			}else{
				foreach($result as $ex){
					$r->alert($ex->getObject()->getName());
				}
			}
		}
		return $r;
	}
	
	function guardar_cita($form,$form_adicional,$idm,$recall=false){
		$r=getResponse();
		//sleep(5);
		$r->script('ajaxd=false;');
		
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				return $r;
			}
			//preparando la data:
			$c = new connection;
			$c->open();
			$crit=new criteria(sqlOR,
					new criteria(sqlEQUAL,$c->sa_cita->estado_cita,"'PENDIENTE'")
					/*,
					new criteria(sqlEQUAL,$c->sa_cita->estado_cita,"'POSPUESTA'")*/
				);
			//buscando citas previas del cliente:
			if($form['id_cliente_contacto']!=''){
				$reccli=$c->sa_cita->doSelect($c,new criteria(sqlAND,array(
					new criteria(sqlEQUAL,$c->sa_cita->id_cliente_contacto,$form['id_cliente_contacto']),
					$crit,
					new criteria(sqlEQUAL,$c->sa_cita->fecha_cita,field::getTransformType($form['fecha_cita'],field::tDate))
				)));
				//Clientes como juridicos (garantias) se deben dejar de registrar la cantidad de veces que quieran, por eso se quita la validacion			
				/**
				if($reccli->getNumRows()>=3){
					$r->script('_alert("El Cliente ya tiene registrada una cita para ese d&iacute;a")');
					return $r;
				} **/
			}
			if($form['id_registro_placas']!=''){
				//buscando citas previas del carro			
				$reccli=$c->sa_cita->doSelect($c,new criteria(sqlAND,array(
					new criteria(sqlEQUAL,$c->sa_cita->id_registro_placas,$form['id_registro_placas']),
					$crit,
					new criteria(sqlEQUAL,$c->sa_cita->fecha_cita,field::getTransformType($form['fecha_cita'],field::tDate))
				)));			
				if($reccli->getNumRows()>=1){
					$r->script('_alert("El Veh&iacute;culo ya tiene registrada una cita para ese d&iacute;a.")');
					return $r;
				}
			}
			
			$sa_cita=new table('sa_cita');
			
			if($idm==''){
				$idm=$form['id_motivo_cita'];
			}
					
					//numeracion citas gregor NUEVA CITA
					/*
					$sql_numero_cita = sprintf("SELECT IFNULL(MAX(numero_cita)+1, 1) as numero_cita from sa_cita WHERE
												id_empresa = %s LIMIT 1", 
													valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
					$rs_sql = mysql_query($sql_numero_cita);
					if (!$rs_sql) return $r->alert(mysql_error()."Error generando numero de cita \n\nLine: ".__LINE__."\n\n File: ".__FILE__);			
					$dt_sql = mysql_fetch_assoc($rs_sql);				
					$numero_cita = $dt_sql["numero_cita"];*/
					
					$sqlNumeroCita= sprintf("SELECT * FROM pg_empresa_numeracion
					WHERE id_numeracion = 31
						AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
					ORDER BY aplica_sucursales DESC
					LIMIT 1",
					valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"),
					valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
					$rsSql = mysql_query($sqlNumeroCita);
					if (!$rsSql) return $r->alert(mysql_error()."\n Error buscando numero de cita guardar \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
					$dtSql = mysql_fetch_assoc($rsSql);
					
					$idEmpresaNumeroCita = $dtSql["id_empresa_numeracion"];
					$numeroCita = $dtSql["numero_actual"];		
					if($numeroCita == NULL){ return $r->alert("No se pudo crear el numero de cita, compruebe que la empresa tenga numeracion de citas");}
					//AQUI SE CREA LA CITA
		
			$sa_cita->add(new field('numero_cita','',field::tInt,$numeroCita,true));
			
			$sa_cita->add(new field('id_cita','',field::tInt,null,true));
			$sa_cita->add(new field('hora_inicio_cita','',field::tTime,$form['hora_inicio_cita'],true));
			$sa_cita->add(new field('hora_fin_cita','',field::tTime,$form['hora_fin_cita'],true));
						
			$sa_cita->add(new field('fecha_cita','',field::tDate,$form['fecha_cita'],true));
			$sa_cita->add(new field('motivo_detalle','motivo_detalle',field::tString,$form['motivo_detalle'],false));//siempre guarde motivo detalle
			
			if($idm=='0'){//SINO SE ENVIA LA CITA pide el detalle
				return $r->alert("Se debe seleccionar un motivo de cita");
				//$sa_cita->add(new field('motivo_detalle','motivo_detalle',field::tString,$form['motivo_detalle'],true));//comentado gregor
			}/*else{
				if($form['id_submotivo']=='' || $form['id_submotivo']==''-1){
					return $r;
				}
			}*/
			$sa_cita->add(new field('id_motivo_cita','id_motivo_cita',field::tInt,$idm,true));
			
			$sa_cita->add(new field('fecha_solicitud','',null,new _function('CURRENT_DATE',null),true));
			$sa_cita->add(new field('id_registro_placas','placa_vehiculo',field::tInt,$form['id_registro_placas'],true));
			$sa_cita->add(new field('id_cliente_contacto','cedula_cliente',field::tInt,$form['id_cliente_contacto'],true));
			$sa_cita->add(new field('id_empresa','',field::tInt,$_SESSION['idEmpresaUsuarioSysGts'],true));
			$sa_cita->add(new field('origen_cita','',field::tString,$form['origen_cita'],true));
			if($form['origen_cita']=='ENTRADA'){
				$estado='PENDIENTE';//'PROCESADA';
				$reccitaq=$c->sa_cita->doQuery($c);				
				$reccitaq->where(new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']));
				$reccitaq->where(new criteria(sqlEQUAL,'fecha_cita','CURRENT_DATE()'));
				//$r->alert("select ifnull(max(numero_entrada)+1,1) as num from sa_cita ".$reccitaq->getCriteria().";");
				$reccount=$c->execute("select ifnull(max(numero_entrada)+1,1) as num from sa_cita ".$reccitaq->getCriteria().";");
				$sa_cita->insert('numero_entrada',$reccount['num']);
				$sa_cita->insert('tiempo_llegada_cliente','NOW()',field::tFunction);
				
			}else{
				$estado='PENDIENTE';
				if($form['clienteEligioFecha'] == NULL){
					return $r->alert("Debe seleccionar si el cliente eligió su propia cita");
				}
				$sa_cita->add(new field('selecciono_fecha','',field::tInt,$form['clienteEligioFecha'],true));
			}
		
			
			$sa_cita->add(new field('estado_cita','',field::tString,$estado,true));
			$sa_cita->add(new field('id_empleado_servicio','',field::tInt,$form['id_empleado_servicio'],true));
			
			//$r->script('obj("cedula_cliente").className="";obj("placa_vehiculo").className="";obj("motivo_detalle").className="";obj("id_submotivo").className="";');
			$r->script('$("#f_cita input,#f_cita select").removeClass("inputNOTNULL");');
			
			/*$q=new query($c);
			$q->add($sa_cita);
			$r->alert($q->getInsert());*/
			//$r->alert($form['id_submotivo']);
			//validando si existen submotivos:
			//if($form['id_submotivo']!=""){
			if($recall==false){
				$callback="xajax_guardar_cita(xajax.getFormValues('f_cita'),xajax.getFormValues('motivos_adicionales'),obj('id_motivo_cita').value,true);";
				if(validar_submotivo($callback,$c,$form['id_submotivo'],$form['id_motivo_cita'],$form['fecha_cita'],$r)){
					$sa_cita->add(new field('id_submotivo','',field::tInt,$form['id_submotivo'],false));
				}else{
					return $r;
				}
			}elseif($form['id_submotivo']!=''){
				$sa_cita->add(new field('id_submotivo','',field::tInt,$form['id_submotivo'],false));
			}
			//verifica si se aplica el acumulador:
			if(intval($form['id_submotivo'])>0){	
				$acumulador=floatval($c->sa_submotivo->doSelect($c, new criteria(sqlEQUAL,'id_submotivo',$form['id_submotivo']))->ut_diagnostico);
				
			}			
			//sumando el acumulador:
			if(isset($form_adicional['id_submotivo_adicional'])){				
				foreach($form_adicional['id_submotivo_adicional'] as $k => $v){
					if($form_adicional['action'][$k]=='add'){						
						$acumulador+=floatval($form_adicional['ut_diagnostico'][$k]);
					}
				}
			}
			if($acumulador!=null){
				$sa_cita->insert('acumulador',$acumulador,field::tFloat);
			}			
			$c->begin();
			$result=$sa_cita->doInsert($c,$sa_cita->id_cita);
			
			//$r->alert($acumulador);
			//$r->alert($sa_cita->getInsert($c,$sa_cita->id_cita));
			if($result===true){
				
				// ACTUALIZA numero cita
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
			WHERE id_empresa_numeracion = %s;",
				valTpDato($idEmpresaNumeroCita, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $r->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
				$lastinsert=$c->soLastInsertId();
				//guardando los motivos adicionales
				if(isset($form_adicional['id_submotivo_adicional'])){
					$error=false;
					foreach($form_adicional['id_submotivo_adicional'] as $k => $v){
						if($form_adicional['action'][$k]=='add'){
							$sql=sprintf("INSERT INTO sa_motivo_adicional(id_motivo_adicional,id_submotivo,id_motivo_cita,ut,id_cita) VALUES (NULL , %s, %s, %s,%s);",
							$form_adicional['id_submotivo_adicional'][$k],
							$form_adicional['id_motivo_adicional'][$k],
							$form_adicional['ut_diagnostico'][$k],
							$lastinsert
							);
						}
						
						if($sql!='' && !$error){
							//$r->alert($sql);
							$resultd = $c->soQuery($sql);
							if($resultd!==true){
								$r->alert('error '.$sql);
								$error=true;
							}
						}
					}
					if($error){
						$c->rollback();
						return $r;
					}
				}
				$r->alert('Se ha creado la cita');
				
				if($form['origen_cita']=='ENTRADA'){
					$r->script("_alert('N&uacute;mero de Llegada: ".$reccount['num']."');");
				}
				//verifica si la placa es parcial y acualiza el cliente de registro:
				$cplaca=new criteria(sqlEQUAL,$c->en_registro_placas->id_registro_placas,$form['id_registro_placas']);
				$recplaca=$c->en_registro_placas->doSelect($c,$cplaca);
				if($recplaca){
					if($recplaca->id_cliente_registro==''){
						//actualiza
						$updateplaca= new table('en_registro_placas');
						$updateplaca->id_cliente_registro=$form['id_cliente_contacto'];
						$resultp=$updateplaca->doUpdate($c,$cplaca);
						if($resultp!==true){
							$r->alert('Error al actualizar cliente en vehiculo');
							//utf8_encode(var_export($resultp,true))							
						}
					}
				}
				$c->commit();
				//cierra el modal
				$r->script('limpiar_cita(obj("f_cita"));');
				//cargando el d�a de nuevo
				$r->loadCommands(cargar_dia($form['fecha_cita']));
			}else{//ERROR SINO INSERTA LA CITA
				foreach($result as $ex){
					$obj=$ex->getObject();
					if($obj instanceof field){
						$alias=$obj->getAlias();
						if($alias!=''){
							//$r->script('obj("'.$alias.'").className="inputNOTNULL";');
							$r->script('$("#'.$alias.'").addClass("inputNOTNULL");');
						}else{
							$r->alert($obj->getName().' '.$ex->getMessage());
						}
					}
					//$r->alert($ex->getObject()->getName().' '.$ex->getMessage());
					$r->alert($ex->getMessage());
				}
				$r->script("_alert('Revise los datos introducidos para completar la operaci&oacute;n');");
			}
			//$r->script('$.modal.close();$.modal.close();');
		return $r;
	}
	
	function validar_submotivo($callback,$c,$id_submotivo,$motivo_cita,$fecha_cita,$r,$id_cita=null){
	
		//ahora limitando los motivos:
		//$motivo_cita=$submotivo->id_motivo_cita;
		//obteniendo datos del submotivo
		if($id_cita!=null){
			$filter= ' and id_cita!='.$id_cita.' ';
		}
		$motivo= $c->sa_motivo_cita->doSelect($c, new criteria(sqlEQUAL,$c->sa_motivo_cita->id_motivo_cita,$motivo_cita));
		//validando el total de entradas diarias por el submotivo:
		//obtener el total de entradas diarias, por submotivo:
		$sql="select count(*) as r from sa_cita where id_motivo_cita= ".$motivo_cita." and fecha_cita=str_to_date('".$fecha_cita."',".DEFINED_DATE.") and estado_cita!='CANCELADA' and estado_cita!='POSPUESTA'  and id_empresa=".$_SESSION['idEmpresaUsuarioSysGts']." ".$filter." ;";
		$sqls="select count(*) as r from sa_cita where id_motivo_cita= ".$motivo_cita." and date_format(fecha_cita,'%U')=date_format(str_to_date('".$fecha_cita."',".DEFINED_DATE."),'%U') and estado_cita!='CANCELADA' and estado_cita!='POSPUESTA' and id_empresa=".$_SESSION['idEmpresaUsuarioSysGts']."".$filter.";";
		$sqlm="select count(*) as r from sa_cita where id_motivo_cita= ".$motivo_cita." and date_format(fecha_cita,'%m-%Y')=date_format(str_to_date('".$fecha_cita."',".DEFINED_DATE."),'%m-%Y') and estado_cita!='CANCELADA' and estado_cita!='POSPUESTA' and id_empresa=".$_SESSION['idEmpresaUsuarioSysGts']."".$filter.";";
		$cantidad_diariom=$c->execute($sql);
		$cantidad_semanalm=$c->execute($sqls);
		$cantidad_mensualm=$c->execute($sqlm);
		//$r->alert($sqls);
		if(($motivo->max_mensual>0) && ($cantidad_mensualm['r']>=$motivo->max_mensual)){
			$r->script('if(_confirm("Se ha alcanzado el tope Mensual del motivo elegido, &iquest;Desea continuar?")){'.$callback.'}');
			
			return false;
		}
		if(($motivo->max_semanal>0) && ($cantidad_semanalm['r']>=$motivo->max_semanal)){
			$r->script('if(_confirm("Se ha alcanzado el tope Semanal del motivo elegido, &iquest;Desea continuar?")){'.$callback.'}');
			return false;
		}
		if(($motivo->max_diario>0) && ($cantidad_diariom['r']>=$motivo->max_diario)){
			$r->script('if(_confirm("Se ha alcanzado el tope Diario del motivo elegido, &iquest;Desea continuar?")){'.$callback.'}');
			return false;
		}
		
		
		//$r= getResponse();
				//validando que sea un submotivo v�lido
		if($id_submotivo=='-1'){
			$r->script('$("#id_submotivo").addClass("inputNOTNULL");_alert("Revise los datos introducidos para completar la operaci&oacute;n");');
			return false;
		}
		if($id_submotivo!=0){
			//obteniendo datos del submotivo
			$submotivo= $c->sa_submotivo->doSelect($c, new criteria(sqlEQUAL,$c->sa_submotivo->id_submotivo,$id_submotivo));
			//validando el total de entradas diarias por el submotivo:
			//obtener el total de entradas diarias, por submotivo:
			$sql="select count(*) as r from sa_cita where id_submotivo= ".$id_submotivo." and fecha_cita=str_to_date('".$fecha_cita."',".DEFINED_DATE.") and estado_cita!='CANCELADA' and estado_cita!='POSPUESTA' and id_empresa=".$_SESSION['idEmpresaUsuarioSysGts']."".$filter.";";
			$sqls="select count(*) as r from sa_cita where id_submotivo= ".$id_submotivo." and date_format(fecha_cita,'%U')=date_format(str_to_date('".$fecha_cita."',".DEFINED_DATE."),'%U') and estado_cita!='CANCELADA' and estado_cita!='POSPUESTA' and id_empresa=".$_SESSION['idEmpresaUsuarioSysGts']."".$filter.";";
			$sqlm="select count(*) as r from sa_cita where id_submotivo= ".$id_submotivo." and date_format(fecha_cita,'%m-%Y')=date_format(str_to_date('".$fecha_cita."',".DEFINED_DATE."),'%m-%Y') and estado_cita!='CANCELADA' and estado_cita!='POSPUESTA' and id_empresa=".$_SESSION['idEmpresaUsuarioSysGts']."".$filter.";";
			$cantidad_diario=$c->execute($sql);
			$cantidad_semanal=$c->execute($sqls);
			$cantidad_mensual=$c->execute($sqlm);
			//$r->alert($sqls);
			if($cantidad_mensual['r']>=$submotivo->max_mensual){
			$r->script('if(_confirm("Se ha alcanzado el tope Mensual del motivo elegido, &iquest;Desea continuar?")){'.$callback.'}');
				//$r->alert($sqlm);
				return false;
			}
			if($cantidad_semanal['r']>=$submotivo->max_semanal){
			$r->script('if(_confirm("Se ha alcanzado el tope Semanal del motivo elegido, &iquest;Desea continuar?")){'.$callback.'}');
				return false;
			}
			if($cantidad_diario['r']>=$submotivo->max_diario){
			$r->script('if(_confirm("Se ha alcanzado el tope Diario del motivo elegido, &iquest;Desea continuar?")){'.$callback.'}');
				return false;
			}
		}
		
		
		
		return true;
	}
	
	function cargar_submotivos($id_motivo_cita,$capa='capa_submotivo',$campo='id_submotivo'){
		$r= getResponse();
		//capa_submotivo
		//obteniendo los datos:
		$c= new connection();
		$c->open();
		
		$submotivo = $c->sa_submotivo->doSelect($c, new criteria(sqlEQUAL,$c->sa_submotivo->id_motivo_cita,$id_motivo_cita));
		if($submotivo){
			if($submotivo->getNumRows()!=0){
				$subm=$submotivo->getAssoc('id_submotivo','descripcion_submotivo');
				$r->assign($capa,inner,inputSelect($campo,$subm,'-1',array('onchange'=>'comprobar_submotivo(this);'),'-1'));
			}else{
				$r->assign($capa,inner,'&nbsp;');
			}
		}
		
		$c->close();
		return $r;
	}
	
	function agregar_motivo($id_motivo,$id_submotivo=null,$fecha_cita='',$recall=true){
		$r = getResponse();
		$c= new connection();
		$c->open();
		if($id_submotivo=='-1'){
			$r->script('_alert("No puede agregar motivos independientes, especifique submotivo");');
			return $r;
		}
		$callback="xajax_agregar_motivo(".$id_motivo.",".$id_submotivo.",obj('fecha_cita').value,false);";
		//validadando
		if($recall){
			if(!validar_submotivo($callback,$c,$id_submotivo,$id_motivo,$fecha_cita,$r)){
				return $r;
			}
		}
		
		$texto=$c->sa_motivo_cita->doSelect($c, new criteria(sqlEQUAL,'id_motivo_cita',$id_motivo))->motivo;
		//$r->alert($id_submotivo);
			$reco=$c->sa_submotivo->doSelect($c, new criteria(sqlEQUAL,'id_submotivo',$id_submotivo));
			$texto.=' '.$reco->descripcion_submotivo;
			$ut_diagnostico=$reco->ut_diagnostico;
		$r->script("
			adiccional_add({
				unidad:'".$texto."',
				id_motivo_adicional:".$id_motivo.",
				id_submotivo_adicional:".$id_submotivo.",
				ut_diagnostico:".$ut_diagnostico.",
				action:'add'
			});
		");
		return $r;
	}
	
	function horaLlegadaCliente($idCita){
		$objResponse = new xajaxResponse();
			$sql = sprintf("UPDATE sa_cita SET tiempo_llegada_cliente = NOW() WHERE id_cita = %s",
				$idCita);
			$query = mysql_query($sql);
			if(!$query){ return $objResponse->alert("Error: ".mysql_error()."\n Nro Error: ".mysql_errno(). "\ Linea: ".__LINE__); }
			$objResponse->assign("cita_edit_hora_llegada_cliente", "innerHTML", date("h:i a"));
			return $objResponse->alert("Hora actualziada correctamente");
	}
	
	
	function buscarListadoClientes($txtCriterio){	
		$objResponse = new xajaxResponse();
	
		$objResponse->script(sprintf("xajax_listadoClientes(0,'','','%s');",
				$txtCriterio));
		
		return $objResponse;		
	}
	
	
	function listadoClientes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
		$objResponse = new xajaxResponse();
	
		$valCadBusq = explode("|", $valBusq);
		$startRow = $pageNum * $maxRows;
	
		//$valCadBusq[0] criterio
		global $spanCI;
		global $spanRIF;
		
		$span_ci_rif = $spanCI." / ".$spanRIF;
	
//		$sqlBusq = "WHERE status = 'Activo'";
		
		if($valCadBusq[0] != ""){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ',nombre,apellido) LIKE %s
										   OR CONCAT_WS('-',lci, ci) LIKE %s)",
									valTpDato("%".$valCadBusq[0]."%","text"),
									valTpDato("%".$valCadBusq[0]."%","text")
					);
		}
	
		$query = sprintf("SELECT id, status, CONCAT_WS(' ',nombre, apellido) as nombre_cliente, CONCAT_WS('-',lci, ci) as lci_ci
						  FROM cj_cc_cliente  %s", $sqlBusq); 
	
		$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
		$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
		$rsLimit = mysql_query($queryLimit);
	
		if (!$rsLimit) { return $objResponse->alert($queryLimit."\n".mysql_error()."\n\nLine: ".__LINE__); }
		if ($totalRows == NULL) {
				$rs = mysql_query($query);
				if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$totalRows = mysql_num_rows($rs);
		}
		$totalPages = ceil($totalRows/$maxRows)-1;
	
		$htmlTblIni = "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";            
			$htmlTh .= "<td width=\"1%\"></td>";
			$htmlTh .= ordenarCampo("xajax_listadoClientes", "4%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			$htmlTh .= ordenarCampo("xajax_listadoClientes", "35%", $pageNum, "lci_ci", $campOrd, $tpOrd, $valBusq, $maxRows, $span_ci_rif);                
			$htmlTh .= ordenarCampo("xajax_listadoClientes", "35%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listadoClientes", "10%", $pageNum, "status", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus");                
		$htmlTh .= "</tr>";
	
		$contFila = 0;
		while ($row = mysql_fetch_assoc($rsLimit)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
						
			$botonAgregarCliente = "";
			//if($row['status'] == "Activo"){//no necesario el buscar cliente ya tiene validado el activo y la empresa
				$botonAgregarCliente = "<button onclick=\"xajax_buscarCliente('".$row['lci_ci']."','cita','cargar_cliente',".$row['id']."); $('#xajax_client_div').fadeOut(); document.getElementById('divFlotante3').style.display='none';\" class=\"puntero\" type=\"button\"><img border=\"0\" src=\"../img/iconos/select.png\"></button>";
			//}
	
			$htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
	
			$htmlTb .= "<td align=\"center\">".$botonAgregarCliente."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['lci_ci']."</td>";        
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_cliente'])."</td>";        
			$htmlTb .= "<td align=\"center\">".$row['status']."</td>";        
	
			$htmlTb.= "</tr>";
		}
	
		$htmlTf = "<tr>";
				$htmlTf .= "<td align=\"center\" colspan=\"18\">";
						$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
						$htmlTf .= "<tr class=\"tituloCampo\">";
								$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
										$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
												$contFila,
												$totalRows);
										$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
										$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
										$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
								$htmlTf .= "</td>";
								$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
										$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
										$htmlTf .= "<tr align=\"center\">";
												$htmlTf .= "<td width=\"25\">";
												if ($pageNum > 0) {
														$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
																0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
												}
												$htmlTf .= "</td>";
												$htmlTf .= "<td width=\"25\">";
												if ($pageNum > 0) { 
														$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
																max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
												}
												$htmlTf .= "</td>";
												$htmlTf .= "<td width=\"100\">";
	
														$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClientes(%s,'%s','%s','%s',%s)\">",
																"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
														for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
																		$htmlTf.="<option value=\"".$nroPag."\"";
																		if ($pageNum == $nroPag) {
																				$htmlTf.="selected=\"selected\"";
																		}
																		$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
														}
														$htmlTf .= "</select>";
	
												$htmlTf .= "</td>";
												$htmlTf .= "<td width=\"25\">";
												if ($pageNum < $totalPages) {
														$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
																min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
												}
												$htmlTf .= "</td>";
												$htmlTf .= "<td width=\"25\">";
												if ($pageNum < $totalPages) {
														$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
																$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
												}
												$htmlTf .= "</td>";
										$htmlTf .= "</tr>";
										$htmlTf .= "</table>";
								$htmlTf .= "</td>";
						$htmlTf .= "</tr>";
						$htmlTf .= "</table>";
				$htmlTf .= "</td>";
		$htmlTf .= "</tr>";
	
		$htmlTblFin .= "</table>";
	
		if (!($totalRows > 0)) {
				$htmlTb .= "<td colspan=\"18\">";
						$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
						$htmlTb .= "<tr>";
								$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
								$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
				$htmlTb .= "</td>";
		}
	
		$objResponse->assign("divListadoClientes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		$objResponse->script("document.getElementById('divFlotante3').style.display='';");
		$objResponse->script("centrarDiv(document.getElementById('divFlotante3'));");
	
		return $objResponse;
    
	}


	include("control/xajax_calendar.inc.php"); //incluir el calendario	
	include("control/xajax_buscar_cliente.inc.php"); //incluir la busqueda/registro de cliente
	include("control/xajax_buscar_placa.inc.php"); //incluir la busqueda/registro de placa
	
	xajaxRegister('load_page');
	xajaxRegister('cargar_dia');
	xajaxRegister('mostrar_asesor');
	xajaxRegister('cargar_cita');
	xajaxRegister('guardar_cita');
	xajaxRegister('editar_cita');
	xajaxRegister('cargar_submotivos');
	xajaxRegister('agregar_motivo');
	xajaxRegister('horaLlegadaCliente');
	xajaxRegister('listadoClientes');
	xajaxRegister('buscarListadoClientes');
	
	
//	xajaxRegister('limpiar_cita');
	//xajaxRegister('f');
	xajaxProcess();
	
	
	includeDoctype();
	
	
?>

<html>
	<head>
		<?php 
			includeMeta();
			includeScripts();
			includeXajaxCalendarCss();
			getXajaxJavascript();
			includeModalBox();
			includeXajaxBuscarCliente();
		?>
            
		<link rel="stylesheet" type="text/css" href="css/sa_registro_cita.css" />
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
        <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
        <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
      
        <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
        <script type="text/javascript" language="javascript" src="../js/maskedinput/jquery.maskedinput.js"></script>
		
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Registro de Citas</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
		
                <style>
                    .inputErrado{
                         background: none repeat scroll 0 0 #b0b1ff;
                    }
                </style>
		<script type="text/javascript">
		
                
                function validarTelfono(){
                    error = false;
                    if (!(validarCampo('xajax_client_div_telf','t','telefono') == true)) {
                            validarCampo('xajax_client_div_telf','t','telefono');    
                            //$("#xajax_client_div_telf").addClass('inputERROR');
                            error = true;
                    }
                    
                    /*else{
                        $("#xajax_client_div_telf").removeClass('inputERROR');
                    }*/
                    
                    if(!(validarCampo('xajax_client_div_otrotelf','t','telefono') == true)){
                            validarCampo('xajax_client_div_otrotelf','t','telefono');
                           //  $("#xajax_client_div_otrotelf").addClass('inputERROR');
                            error = true;
                    }
                    
                    if (error == true) {
                        alert("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido");
                        return false;
                    }else{
                        return true;
                    }
                }
                
		window.onbeforeunload=function(){

			var modal= obj('simplemodal-container');
			var otra = obj('cita_editar');
			if(modal!=null){
				if(modal.style.display!='none'){
					return "No se guardar&aacute;n los datos Ingresados";
				}
			}
			if(otra.style.display!='none'){
				return "No se guardar&aacute;n los datos Ingresados";				
			}
		}
		
		
		var ajaxd=false;
		//if(ajaxdemand())return;
		function ajaxdemand(){
			//alert(ajaxd);
			if(ajaxd==true){
				alert("en espera de la respuesta");
				return true;
			}else{
				ajaxd=true;
				return false;
			}
		}
		/*	function modalOpen (dialog) {
				dialog.overlay.fadeIn('slow');
			
				dialog.container.fadeIn('medium', function () {
					dialog.data.hide().slideDown('medium');	 
				});
			
			}

			function simplemodalclose (dialog) {
			dialog.data.fadeOut('medium', function () {
				dialog.container.fadeOut('medium');
					dialog.overlay.fadeOut('slow', function () {
						$.modal.close();
					});
				});
			} 
*/
			var asesor_height=0;
			//var c_height=0;
			//var	c_width=0;
			function asesoropen (dialog) {
				var data_asesor =$('#datos_asesor');
				data_asesor.hide();
				//dialog.containet.attr({'height':c_height,'width':c_width});				
				
				dialog.overlay.fadeIn('medium', function () {
					dialog.data.hide().fadeIn('slow');
					dialog.container.fadeIn('medium', function () {
					
						dialog.container.animate(
							{
								height: asesor_height
							},
							'slow',null,function(){
								$('#close_asesor_b').fadeIn('fast');
							}
						);
						data_asesor.slideDown('fast');
					});
				});
				
			}

			function asesorclose (dialog) {				
				var image= obj('asesor_image');
				var data_asesor =$('#datos_asesor');
				$('#close_asesor_b').fadeOut('fast');
				data_asesor.slideUp('medium');
				//dialog.data.fadeOut('medium', function () {
					dialog.container.animate({height:image.offsetHeight+40},'madium',null,function(){
						dialog.data.fadeOut('medium', function () {
							dialog.container.hide('medium', function () {
								dialog.overlay.fadeOut('medium', function () {
									$.modal.close();
								});
							});
						});
					});
				//});
			}
			
			function modalAsesor(idwin,_width,_height){
				var _height = _height || 400;
				var _width = _width || 200;
			
				var v= obj('vista_asesor');
				var image= obj('asesor_image');
				//var cb= onj('close_asesor_b');
				//alert(v.offsetWidth+' '+v.offsetHeight);
				
				var  _width= v.offsetWidth;
				asesor_height=v.offsetHeight;
				var _height = image.offsetHeight+40;
				
				//c_height=_height;
				//c_width=_width;
				
				//var o = obj('modalasesor');
				//o.style.width=v.offsetWidth+'px';
				//o.style.height=v.offsetHeight+'px';
				
				$(idwin).modal({
				  overlayCss: {
					backgroundColor: '#000',
					cursor: 'wait'
				  },
				  containerId: 'modalasesor',
				  containerCss: {
					height: _height,
					width: _width,
					backgroundColor: 'white',
					border: '2px solid silver',
					color: '#000000',
					'font-weight': 'bold',
					'border-radius': '6px'
				  },
				  onOpen: asesoropen,
				  onClose: asesorclose

				});
			}
			
			function mostrar_asesor(id){
				xajax_mostrar_asesor(id);//modalWindow('#ref_asesor');
			}
			var modo_cita=0; //indica si esta en modo ediccion
			function cargar_cita(d,m,a,makehora,id_empleado,entrada,estado_dia,estado_asesor,intervalo,id_cita){
				var id_cita= id_cita || 0;
				var mes = parseFloat(m)-1;//parseInt da error con 08
				var dia=(d);
				var ano = (a);
				//confitrmaciones via javascript
				var reservada=(entrada == '1');
				var now = new Date();
				var factual= new Date(now.getFullYear(),now.getMonth(),now.getDate());
				var fecha= new Date(ano,mes,dia);
				//alert(fecha+' '+factual+' '+mes+' '+d+' '+ano);
				
					//verifica si es reservada con la fecha de hoy
					//alert (fecha+' '+factual);
					//SIVA:
					if(id_cita==0){
						/*if(fecha<factual){
							_alert('No se pueden crear citas posteriores a la fecha actual');
							return;
						}else{*/
							//confirmacion de citas en bajas del empleado:
							if(estado_asesor!=''){
								if(_confirm('Ha seleccionado un lapzo NO DISPONIBLE para el ASESOR SELECCIONADO\n&iquest;desea Continuar?')==false){
									return;
								}
							}
							//confirmacion de citas en bajas:
							if(estado_dia!=''){
								if(_confirm('Ha seleccionado un lapzo NO DISPONIBLE\n&iquest;desea Continuar?')==false){
									return;
								}
							}
							//confirmacion de citas en reservas:
							if(fecha.getTime()!=factual.getTime() && reservada){
								if(_confirm('Ha seleccionado un lapzo reservado para ENTRADAS\n&iquest;desea Continuar?')==false){
									return;
								}
							}
						//}
					}else{
						if(fecha<factual){
							modo_cita=2;
						}
					}
				/*if(id_cita==''){	
					modo_cita=0;
				}else{
					modo_cita=1;
				}*/
				xajax_cargar_cita(d,m,a,makehora,id_empleado,entrada,estado_dia,estado_asesor,intervalo,id_cita,modo_cita);
				
								
			}
			
			function cambiar_cita(){
				$.modal.close();
				$.modal.close();
			}
			
			function kp_buscarcliente(e,obj,parent){
				if(e==null){
					e=event;
				}
				if(e==null){
					e=window.event;
				}
				var tecla = (document.all) ? e.keyCode : e.which;
				if(tecla==13){
					xajax_buscarCliente(obj.value,parent);
				}
			}
			function kp_buscarplaca(e,obj,parent){
				if(e==null){
					e=event;
				}
				if(e==null){
					e=window.event;
				}
				var tecla = (document.all) ? e.keyCode : e.which;
				if(tecla==13){
					xajax_buscarPlaca(obj.value,parent);
				}
			}
			
			function on_motivo_cita(obj){//ocultacion del detalle, lo comente para que motivo_detalle sea permanente
				/*if(obj.value!='0'){
					$('#capa_motivo_detalle').fadeOut();
				}else{
					$('#capa_motivo_detalle').fadeIn();
				}*/
			}
			
			function guardar_cita(form){
				//xajax_guardar_cita(form);
			}
			
			function limpiar_cita(form){
				cambiar_cita();
				var c = form.elements.length;
				for(var i =0; i<c;i++){
					if(form.elements[i].type!='button' && form.elements[i].type!='radio'){
						form.elements[i].value='';
					}
				}
				$('.td_inner').html('&nbsp;');
				$('#capa_motivo_detalle').show();
				obj('id_motivo_cita').value='0';
				obj('capa_submotivo').innerHTML='';
				vaciar_adicionales();
				close_window('adicionales_window');
			}
			
			function cerrar(c){
				//obj(c).style.display='none';
				$('#'+c).fadeOut();
				modo_cita=0;
			}
			
			function posponer_cita(){
				if(_confirm('&iquest;Desea POSPONER la Cita?')){
					xajax_editar_cita(xajax.getFormValues('editar_cita'),'POSPUESTA');
				}
				
			}
			
			function confirmar_cita(){
				if(_confirm('&iquest;Desea CONFIRMAR la Cita?')){
					xajax_editar_cita(xajax.getFormValues('editar_cita'),'CONFIRMADA');
				}
				cerrar("cita_editar");
			}
			
			function cancelar_cita(){
				if(_confirm('&iquest;Desea CANCELAR la Cita?')){
					xajax_editar_cita(xajax.getFormValues('editar_cita'),'CANCELADA');
				}
				cerrar("cita_editar");
			}
			
			
			var counter=0;
			var tablag=new Array();
			
			function agregar_adicional(){
				var oid_motivo=obj('id_motivo_cita_adicional');
				var oid_submotivo=obj('id_submotivo_cita_adicional');
				var id_motivo=0;
				var id_submotivo=null;
				
				if(oid_motivo.value == 0){
					_alert('No ha especificado Motivo');
					return;
				}
				if(oid_submotivo != null){
					id_submotivo=oid_submotivo.value;
				}else{
					_alert('No han especificado Sub-motivos, no se puede agregar un motivo independiente');
					return;					
				}
				id_motivo=oid_motivo.value;
				
				var oidsubmotivo_principal=obj('id_submotivo');
				if(oidsubmotivo_principal!=null){
					if(oidsubmotivo_principal.value==id_submotivo){
						_alert('Este motivo ya se agreg&oacute; como principal de la cita');
						return;
					}
				}
				//recorrer las unidades
				/*if(id_motivo==0){
					//alert(id_motivo);
					return;
				}*/
				for (var i=1; i<=counter;i++){
					var ob_motivo= obj('id_motivo_adicional'+i);
					var ob_submotivo= obj('id_submotivo_adicional'+i);
					if(ob_motivo.value==id_motivo && ob_submotivo.value==id_submotivo){
						//verifica que no est� coulto
						var row= obj('row'+i);
						if(row.style.display=='none'){
							if(_confirm('El Motivo fue anteriormente eliminado, &iquest;Desea agregarlo de nuevo?')){
								row.style.display='';
								var action=obj('action'+i);
								action.value='add';
							}
						}else{
							_alert('Ya existe el Motivo');
						}
						return;
					}
				}
				xajax_agregar_motivo(id_motivo,id_submotivo,obj('fecha_cita').value);
			}
			
			function comprobar_submotivo(sobj){
				var id_submotivo=sobj.value;
				for (var i=1; i<=counter;i++){
					//var ob_motivo= obj('id_motivo_adicional'+i);
					var ob_submotivo= obj('id_submotivo_adicional'+i);
					if(ob_submotivo.value==id_submotivo){
						//verifica que no est� coulto
						var row= obj('row'+i);
						if(row.style.display=='none'){
							if(_confirm('El Motivo fue anteriormente eliminado de la lista de Motivos adicionales, &iquest;Desea agregarlo de nuevo?')){
								row.style.display='';
								var action=obj('action'+i);
								action.value='add';
								setWindow('adicionales_window','title_adicionales_window',true);
							}
						}else{
							_alert('Ya existe el Motivo en la lista de Adicionales');
						}
						sobj.value='-1';
						return;
					}
				}
			}
			
			function adiccional_add(data){
				//alert('f');
				var tabla=obj('tbody_adicionales');
				//alert(tabla);
				var nt = new tableRow("tbody_adicionales");
				tablag[counter]=nt;
				counter++;
				nt.setAttribute('id','row'+counter);
				nt.$.className='field';
				var c1= nt.addCell();
					//c1.$.className='field';
					//c1.setAttribute('style','width:30%;');
					c1.$.innerHTML=data.unidad+'<input type="hidden" id="id_motivo_adicional'+counter+'" name="id_motivo_adicional[]" value="'+data.id_motivo_adicional+'"  /><input type="hidden" id="id_submotivo_adicional'+counter+'" name="id_submotivo_adicional[]" value="'+data.id_submotivo_adicional+'"  /><input type="hidden" id="ut_diagnostico'+counter+'" name="ut_diagnostico[]" value="'+data.ut_diagnostico+'"  /><input type="hidden" id="action'+counter+'" name="action[]" value="'+data.action+'" />';
				//var c2= nt.addCell();
					//c1.$.className='field';
					//c2.setAttribute('style','width:30%;');
					//c2.$.innerHTML=
				var c3= nt.addCell();
					//c1.$.className='field';
					c3.$.setAttribute('align','right');
					c3.$.innerHTML='<button type="button" onclick="quitar_adiccional('+counter+')"><img src="<?php echo getUrl('img/iconos/minus.png'); ?>" border="0" alt="Quitar" /></button>';
				//limpiando selects:
				var lista= obj('id_motivo_cita_adicional');
				obj('capa_submotivos_adicionales').innerHTML='&nbsp;';
				lista.value=0;
				lista.focus();
			}
			
			function quitar_adiccional(cont){
				if(_confirm("&iquest;Desea eliminar el Motivo de la Lista?")){
					var fila=obj('row'+cont);
					fila.style.display='none';
					var action=obj('action'+cont);
					action.value='delete';
				}
			}
			
			function vaciar_adicionales(){
				//alert('s');
				var tabla=obj('tbody_adicionales');
				for(var t in tablag){
					//alert(tablag[t]);
					tabla.removeChild(tablag[t].$);
				}
				counter=0;
				tablag=new Array();
			}
			function clean_motivos(){
				if(_confirm('&iquest;Desea Vaciar los Motivos Adicionales definitivamente?')){vaciar_adicionales();}
			}
		
		</script>
		<style type="text/css">
			#horas_comprometidas{
				background:#EEFFEE;/*#DDFFBB;*/
			}
		</style>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Recepci&oacute;n de veh&iacute;culos</span><br />
			<span class="subtitulo_pagina" >(Registro de Citas)</span></td>
		</tr>
	</table>
	
	<br />
	
	<!--  tabla de tiempos:  -->
	<div>
	<table id="tabla_tiempos" class="tabla_tiempos" border="0" cellspacing="0" >
		<tbody>
			<!--<tr>
				<td class="td_calendario">
					<span>Fecha Seleccionada</span>
				</td>
				<td>f
				</td>
			</tr>-->
			<tr>
				<!--<td class="td_calendario">
					<span><input type="text" id="fecha_seleccionada" /></span>
				</td>-->
				<td class="" id="encabezado_asesores">
					<strong>Calendario de citas de Servicio</strong>
				</td>
				<td>
					 <strong>Asesores:</strong>
				</td>
				<td align="right">
					<em>Fecha seleccionada: </em> <span id="mensajes"></span>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="td_calendario">
					<div id="calendario">
						<!-- carga del calendario via XAJAX-->
					</div>
					<div align="left">
						<div class="leyenda"><strong>Leyenda:</strong></div>
						<div class="leyenda"><div class="tr_cita_baja_feriado" id="leyend"></div>Feriados</div>
						<div class="leyenda"><div class="tr_cita_baja_parcial" id="leyend"></div>Parciales</div>
						<div class="leyenda"><div class="td_cita_baja" id="leyend"></div>Asesor no disponible</div>
						<div class="leyenda"><div class="tr_cita_baja" id="leyend"></div>Almuerzo</div>
						<div class="leyenda"><div class="tr_reserva" id="leyend"></div>Reservado Entradas</div>
						<div class="leyenda"><strong>Origen Citas:</strong></div>
						<div class="leyenda"><div class="cita_PROGRAMADA" id="leyend_cita"></div>Programada</div>
						<div class="leyenda"><div class="cita_ENTRADA" id="leyend_cita"></div>Entrada Taller</div>
                        <div class="leyenda"><div id="leyend_cita"><img src='../img/iconos/application_view_columns.png' /></div>Cargada Agenda</div>                        
						<div class="leyenda"><strong>Estados Citas:</strong></div>
						<div class="leyenda"><div class="cita_PROCESADA" id="leyend_cita"></div>Procesada</div>
						<!--<div class="leyenda"><div class="cita_CANCELADA" id="leyend_cita"></div>Cancelada</div>-->
						<div class="leyenda"><div class="cita_RETRAZADA" id="leyend_cita"></div>Retrasada</div>
						<div class="leyenda"><div class="cita_PENDIENTE" id="leyend_cita"></div>Pendiente</div>
						<div class="leyenda"><div class="cita_CONFIRMADA" id="leyend_cita"></div>Confirmada</div>
						<div class="leyenda"><div class="cita_INCUMPLIDA" id="leyend_cita"></div>Incumplida</div>
						
						<div style="display:none;"><div class="leyenda"><strong>Detalles Periodo:</strong></div>
						<div class="leyenda">Intervalo:&nbsp;<span id="intervalo"></span>&nbsp;Minutos</div>
						<div class="leyenda">Fecha Inicio:&nbsp;<span id="fecha_inicio"></span></div>
						<div class="leyenda">Fecha Fin:&nbsp;<span id="fecha_fin"></span></div></div>
						<div id="horas_comprometidas">
						<div class="leyenda" id=""><strong>Horas Comprometidas:&nbsp;</strong></div>
						<div class="leyenda" id=""><em>D&iacute;a:</em><span id="capa_total_dia"></span></div>
						<div class="leyenda" id=""><strong>Horas Concesionaria:&nbsp;</strong></div>
						<div class="leyenda" id="capa_ut_concesionario"><span id="ut_concesionario"></span></div>
						<div class="leyenda" id=""><strong>Horas Planta:&nbsp;</strong></div>
						<div class="leyenda" id="capa_ut_planta"><span id="ut_planta"></span></div>
						</div>
					</div>
				</td>
				<td colspan="2" class="td_asesores" >
					
						<div id="tabla_asesores" >
							<!-- carga de los asesores via XAJAX-->
						</div>
					
				</td>
			</tr>
		</tbody>
	</table>
	<div id="ref_asesor" style="visibility:hidden;position:absolute;left:0px;top:0px;">
		<div id="vista_asesor" style="left:0px;top:0px;position:absolute;padding:20px;">
		</div>
	</div>
	<div id="cita" style="display:none;">	
		<div id="cita_add">
		<?php echo startForm("f_cita",'','onsubmit=return false;'); 
			setInputTag('hidden','id_cliente_contacto');
			setInputTag('hidden','id_registro_placas');
			setInputTag('hidden','hora_fin_cita');
			setInputTag('hidden','id_empleado_servicio');
			
			$estilo_citas=array('readonly'=>'readonly','class'=>'campo_cita');
			$c=new connection();
			$c->open();
			$id_empresa=$_SESSION['idEmpresaUsuarioSysGts'];
			$motivos=$c->sa_motivo_cita->doSelect($c,new criteria(sqlEQUAL,$c->sa_motivo_cita->id_empresa,$id_empresa))->getAssoc($c->sa_motivo_cita->id_motivo_cita,$c->sa_motivo_cita->motivo);
			/*setInputTag('hidden','id_modelo');
			setInputTag('hidden','id_unidad_fisica');
			setInputTag('hidden','chasis');
			setInputTag('hidden','placa');
			setInputTag('hidden','kilometraje');
			setInputTag('hidden','color');
			setInputTag('hidden','id_transmision');
			setInputTag('hidden','id_combustible');
			setInputTag('hidden','ano');*/ ?>
		<div>
			<table class="tabla_form">
				<caption id="cita_title">Crear Cita</caption>
				<tr>
					<td><div class="cita_PROGRAMADA" id="leyend_cita" ></div>
						<?php
						//onclick="xajax_f(xajax.getFormValues('form1'));"
							setRadioInputTag('origen_cita','PROGRAMADA','PROGRAMADA',"onclick=$('#tr_cliente_selecciono').show();");
						?>
					</td>
					<td><div class="cita_ENTRADA" id="leyend_cita"></div>
						<?php
							setRadioInputTag('origen_cita','ENTRADA','ENTRADA',"onclick=$('#tr_cliente_selecciono').hide();");
						?>
					</td>
				</tr>
			</table>
		</div>
		<div>
			<table class="tabla_form">
				<caption>Datos del Cliente</caption>
				<tr>
					<td class="form_label"><?php echo $spanCI."/".$spanRIF ?>:</td>
					<td class=""><?php 
					//setInputTag('text','cedula_cliente','',array('onkeypress'=>'kp_buscarcliente(event,this,\'cita\'); return validar_numero_guion(event);','maxlength'=>'10')); //gregor agregue validacion y limite  
					//lo cambie porque panama permite pasaportes con varios caracteres y numeros
					setInputTag('text','cedula_cliente','',array('onkeypress'=>'kp_buscarcliente(event,this,\'cita\');','maxlength'=>'18'));
							?></td>
					<td colspan="2">
					<?php setButton('button',imageTag(getUrl('img/iconos/find.png')).'Buscar..',array('onclick'=>'xajax_buscarCliente(obj(\'cedula_cliente\').value,\'cita\');')); ?>
                    
                    <img style="vertical-align:middle" src="../img/iconos/ico_pregunta.gif" title="Formato para busquedas exactas Ej.: <?php echo $titleFormatoCI; ?>"/>
                    
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <button onclick="xajax_listadoClientes(0);" type="button"><img border="0" alt="image" src="../img/iconos/people1.png" id="">Listado Clientes</button>
                    
					</td>
				</tr>
				<tr>
					<td class="form_label">Nombres:</td>
					<td class="form_field" ><span id="nombre_cliente" class="td_inner">&nbsp;</span></td>
					<td class="form_label">Email:</td>
					<td class="form_field" ><span id="email_cliente" class="td_inner">&nbsp;</span></td>
				</tr>
				<tr>
					<td class="form_label">Tel&eacute;fono:</td>
					<td class="form_field" ><span id="telefono_cliente" class="td_inner">&nbsp;</span></td>
					<td class="form_label">Celular:</td>
					<td class="form_field" ><span id="celular_cliente" class="td_inner">&nbsp;</span></td>
				</tr>
			</table>
			
			
			<table class="tabla_form">
				<caption>Datos del veh&iacute;culo</caption>
				<tr>
					<td class="form_label">Placa:</td>
					<td class=""><?php setInputTag('text','placa_vehiculo','',array('onkeypress'=>'kp_buscarplaca(event,this,\'cita\');','maxlength'=>'8')); ?></td>
					<td colspan="2">
					<?php setButton('button',imageTag(getUrl('img/iconos/find.png')).'Buscar..',array('onclick'=>'xajax_buscarPlaca(obj(\'placa_vehiculo\').value,\'cita\');')); ?>
					</td>
				</tr>
				<tr>
					<td class="form_label">Chasis:</td>
					<td class="form_field"><span id="chasis_vehiculo" class="td_inner">&nbsp;</span></td>
					<td class="form_label">Modelo:</td>
					<td class="form_field" ><span id="modelo_vehiculo" class="td_inner">&nbsp;</span></td>
				</tr>
				<tr>
					<td class="form_label">Transmisi&oacute;n:</td>
					<td class="form_field" ><span id="transmision_vehiculo" class="td_inner">&nbsp;</span></td>
					<td class="form_label">Combustible:</td>
					<td class="form_field" ><span id="combustible_vehiculo" class="td_inner">&nbsp;</span></td>
				</tr>
				<tr>
					<td class="form_label"><?php echo $spanKilometraje; ?>:</td>
					<td class="form_field" ><span id="kilometraje_vehiculo" class="td_inner">&nbsp;</span></td>
					<td class="form_label">Color:</td>
					<td class="form_field" ><span id="color_vehiculo" class="td_inner">&nbsp;</span></td>
				</tr>
				<tr>
					<td class="form_label">A&ntilde;o:</td>
					<td class="form_field" ><span id="ano_vehiculo" class="td_inner">&nbsp;</span></td>
				</tr>
			</table>
			
			<table class="tabla_form">
				<caption>Datos de la Cita</caption>
				
				<tr>
					<td class="form_label">Fecha:</td>
					<td class="form_field" id=""><?php setInputTag('text','fecha_cita','',$estilo_citas); ?></td>
					<td class="form_label">Hora:</td>
					<td class="form_field" id=""><?php setInputTag('text','hora_inicio_cita','',$estilo_citas); ?></td>
				</tr>
				<tr>
					<td class="form_label">Motivo:</td>
					<td class="form_field" ><?php echo inputSelect('id_motivo_cita',$motivos,'0',array('onchange'=>'on_motivo_cita(this);xajax_cargar_submotivos(this.value);'),0); ?>
					</td>
					<td class="form_label">Detalle:</td>
					<td class="form_field" id="capa_motivo_detalle"><?php setInputTag('text','motivo_detalle','',array('class'=>'campo_cita')); ?></td>
				</tr>
				<tr>
					<td class="form_label">Sub-motivo:</td>
					<td class="form_field"><span id="capa_submotivo">&nbsp;</span></td>
					<td class="form_label">Motivos Adicionales:</td>
					<td class="form_field">
					<button type="button" class="image_button" onClick="setWindow('adicionales_window','title_adicionales_window',true);"><img src="<?php echo getUrl('img/iconos/plus.png'); ?>" border="0" alt="Agregar Motivos Adiccionales" />Agregar</button>
					<button title="Vaciar Motivos Adiccionales" type="button" class="image_button" onClick="clean_motivos();"><img src="<?php echo getUrl('img/iconos/delete.png'); ?>" border="0" alt="Vaciar Motivos Adiccionales" /></button>
					
					</td>
				</tr>
				<tr>
					<td class="form_label">Asesor:</td>
					<td class="form_field" id="asesor">&nbsp;</td>
					<td class="form_label">Fecha Solicitud:</td>
					<td class="form_field" id="form_fecha_solicitud"><?php echo adodb_date(DEFINEDphp_DATE); ?></td>
				</tr>
                <tr id="tr_cliente_selecciono">
					<td class="form_label" >¿Cliente eligi&oacute; fecha?:</td>
					<td class="form_field"  id="cliente_selecciono"  style="vertical-align:middle"><input type="radio" name="clienteEligioFecha" id="clienteEligioFechaSI" value="1" /> SI <input type="radio" name="clienteEligioFecha" id="clienteEligioFechaNO" value="0" /> NO 
                    <img style="float:right" title="Seleccione 'SI', si el cliente seleccion&oacute; su propia fecha para la cita. 
Seleccione 'NO', si usted tuvo que otorgar la cita con fecha pr&oacute;xima disponible" src="../img/iconos/ico_pregunta.gif" style="vertical-align:middle"> </td>
				</tr>
			
			</table>
			<br />
			<center>
			<?php 
setButton('button',imageTag(getUrl('img/iconos/save.png')).'Guardar',array("onclick"=>"if(!ajaxdemand()){xajax_guardar_cita(xajax.getFormValues('f_cita'),xajax.getFormValues('motivos_adicionales'),obj('id_motivo_cita').value);}"));


setButton('button',imageTag(getUrl('img/iconos/edit.png')).'Cambiar Fecha',array("onclick"=>"cambiar_cita();"));
			
setButton('button',imageTag(getUrl('img/iconos/delete.png')).'Cancelar',array("onclick"=>"limpiar_cita(obj('f_cita'));"));
?>
			</center>
		</div>
		<?php echo endForm(); ?>
		</div>
		
		
	<div class="window" id="unidad_cliente_window" style="min-width:400px;max-width:400px;;visibility:hidden;">
	<div class="title" id="title_unidad_cliente_window">
		Veh&iacute;culos registrados por el cliente	
	</div>
	<div class="content">
		Seleccione una unidad de la lista para agregar:<br /><br />
		<div id="lista_unidad_cliente">		
		</div>
		<div style="text-align:center;padding:2px;"><button type="button" onClick="close_window('unidad_cliente_window');"><img alt="Cacelar" src="<?php echo getUrl('img/iconos/delete.png'); ?>" /> Cancelar</button></div>
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('unidad_cliente_window');" border="0" />
	</div>
	
	<div class="window" id="adicionales_window" style="width:auto;min-width:450px;max-width:470px;visibility:hidden;">
		<div class="title" id="title_adicionales_window">
			Agregar Motivos Adicionales	
		</div>
		<div class="content" style="overflow:hidden;">
			<table class="tabla_form" style="width:100%">
				<tr>
					<td class="form_label">Motivo:</td>
					<td class="form_field">
					<?php echo inputSelect('id_motivo_cita_adicional',$motivos,'0',array('onchange'=>'xajax_cargar_submotivos(this.value,\'capa_submotivos_adicionales\',\'id_submotivo_cita_adicional\');'),0); ?>
					
					</td>
					
					<td style="text-align:right;">
						<button type="button" onClick="agregar_adicional()"><img src="<?php echo getUrl('img/iconos/plus.png'); ?>" border="0" alt="Agregar Motivo" /></button>
					</td>
					<td rowspan="2">
						<button title="Cerrar" type="button" class="image_button" onClick="close_window('adicionales_window');"><img src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" border="0" alt="Cerrar ventana" />Cerrar</button>
					</td>
				</tr>
				<tr>
					<td class="form_label">Submotivo:</td>
					<td class="form_field"><div id="capa_submotivos_adicionales">&nbsp;</div></td>
					<td style="text-align:right;">					
						<button title="Vaciar Motivos Adiccionales" type="button" class="image_button" onClick="clean_motivos();"><img src="<?php echo getUrl('img/iconos/delete.png'); ?>" border="0" alt="Vaciar Motivos Adiccionales" /></button>
					</td>
				</tr>
			</table>
			<form style="margin:0px;padding:0px;" id="motivos_adicionales" name="motivos_adicionales" onSubmit="return false;">
			<div style="overflow:auto;height:200px;width:390px;">
				<table class="insert_table" style="width:95%;">
					<col  style="" />
					<col  style="width:15%;text-align:right;" />
					<tbody id="tbody_adicionales">
					
					</tbody>
					
				</table>
			</div>
			</form>
		</div>
		<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('adicionales_window');" border="0" />
	</div>
		
		
	</div>
	</div>
	<div id="cita_editar" style="width:280px;left:20px;display:none;position:absolute;background:white;" class="xajax_client_div">
		<?php 
		echo startForm("editar_cita",'','onsubmit=return false;'); 
		setInputTag('hidden','cita_edit_id_empleado_servicio');
		setInputTag('hidden','cita_edit_id_cita');
		setInputTag('hidden','cita_edit_hora_fin_cita');
		setInputTag('hidden','cita_edit_fecha_cita');
		setInputTag('hidden','cita_post_hora_fin_cita');
		setInputTag('hidden','cita_post_id_empleado_servicio');
		?>
		<table class="tabla_form">
			<caption id="cita_edit_title" class="xajax_client_div_bar">Editar Cita #<span class="td_inner" id="cita_edit_id"></span></caption>
			<tr>
				<td class="form_label">Tipo:</td>
				<td class="form_field" ><span id="cita_edit_origen" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Estado Cita:</td>
				<td class="form_field" ><span style="color:red;" id="cita_edit_estado" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Cliente:</td>
				<td class="form_field" ><span id="cita_edit_cliente" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label"><?php echo $spanCI." / ".$spanRIF ?></td>
				<td class="form_field" ><span id="cita_edit_ci" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td colspan="2" class="form_field"><span style="color:red;" id="cita_edit_cliente_telefonos" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Veh&iacute;culo:</td>
				<td class="form_field" ><span id="cita_edit_vehiculo" class="td_inner">&nbsp;</span></td>
			</tr>
             <tr>
				<td class="form_label">Modelo:</td>
				<td class="form_field" ><span id="cita_edit_modelo" class="td_inner">&nbsp;</span></td>
			</tr>
            
             <tr>
				<td class="form_label">Unidad Basica:</td>
				<td class="form_field" ><span id="cita_edit_uniBas" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Fecha Cita:</td>
				<td class="form_field" ><span id="ecita_edit_fecha_cita" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Fecha Solicitud:</td>
				<td class="form_field" ><span id="cita_edit_fecha_solicitud" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Hora cita:</td>
				<td class="form_field" ><span id="cita_edit_hora_inicio_cita" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Llegada Cliente:</td>
				<td class="form_field" ><span id="cita_edit_hora_llegada_cliente" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Motivo:</td>
				<td class="form_field" ><span id="cita_edit_motivo" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td class="form_label">Detalle:</td>
				<td class="form_field" ><div style="max-height:40px;width:150px;overflow:auto;" id="cita_edit_motivo_detalle" class="td_inner">&nbsp;</div></td>
			</tr>
			<tr>
				<td class="form_label">Asesor:</td>
				<td class="form_field" ><span id="cita_edit_asesor" class="td_inner">&nbsp;</span></td>
			</tr>
		</table>
		<table class="tabla_form" id="cita_edit_posponer">
			<caption id="cita_edit_title" >Posponer Cita</caption>
			<tr >
				<td class="form_label">Fecha Cita:</td>
				<td class="form_field" ><?php setInputTag('text','cita_post_fecha_cita','',array('readonly'=>'readonly','style'=>'width:75px;')); ?><span id="cita_post_dias"></span></td>
			</tr>
			<tr>
				<td class="form_label">Hora Cita:</td>
				<td class="form_field" ><?php setInputTag('text','cita_post_hora_inicio_cita','',array('readonly'=>'readonly','style'=>'width:75px;')); ?></td>
			</tr>
			<tr id="cita_edit_postergar">
				<td class="form_label">Asesor:</td>
				<td class="form_field" ><span id="cita_post_asesor" class="td_inner">&nbsp;</span></td>
			</tr>
			<tr>
				<td colspan="2" align="center"><?php setButton('button',imageTag(getUrl('img/iconos/save.png')).'Posponer',array("onclick"=>"posponer_cita();"),'button_posponer'); ?></td>
			</tr>
			<tr id="cita_edit_postergar">
				<td colspan="2">Nota: Para Posponer la cita seleccione otra fecha y hora disponible</td>
			</tr>
		</table>
		<br />
		<center>
		
			<?php 

//no es necesario, se puede cambiar la cita
/*setButton('button',imageTag(getUrl('img/iconos/people1.png')).'Cambiar Asesor',array("onclick"=>"cambiarAsesor();"),'button_cambiar_asesor');
echo "<br>";*/



setButton('button',imageTag(getUrl('img/iconos/cita_programada_confirmada.png')).'Confirmar',array("onclick"=>"confirmar_cita();"),'button_confirmar');

setButton('button',imageTag(getUrl('img/iconos/delete.png')).'Cancelar Cita',array("onclick"=>"cancelar_cita();"),'button_cancelar');
			
setImageTag(getUrl('img/iconos/close_dialog.png'),'',array('style'=>'cursor:pointer;position:absolute;top:-1px;right:3px;','onclick'=>"cerrar('cita_editar');","title"=>'Cerrar'));
			
setButton('button','Cerrar',array("onclick"=>"cerrar('cita_editar');"));


?>
		</center>
		<?php echo endForm(); ?>
	
	</div>
	</div>
    
    
<div style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:1005;" class="root" id="divFlotante3">
    <div class="handle" id="divFlotanteTitulo3"><table><tbody><tr><td width="100%" id="tdFlotanteTitulo3">Clientes</td></tr></tbody></table></div>
    <div style="width:500px;">
        <table>
            <tr>
                <td width="33%" align="right" class="tituloCampo">Criterio:</td>
                <td>
                    <input type="text" id="criterioCliente" onKeyUp="document.getElementById('botonBuscarCliente').click();" />
                    <button type="button" id="botonBuscarCliente" class="puntero" onclick="xajax_buscarListadoClientes(document.getElementById('criterioCliente').value);"><img border="0" src="../img/iconos/find.png"></button>
                    <button type="button" onclick="document.getElementById('criterioCliente').value = '';  document.getElementById('botonBuscarCliente').click(); ">Limpiar</button>
                </td>
            </tr>
        </table>
    </div>
    
    <div id = "divListadoClientes"></div>
    
    <div align="right">
        <hr/>
        <button type="button" class="puntero" onclick="document.getElementById('divFlotante3').style.display='none';">Cancelar</button>
    </div>
</div>
    
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
		xajax_load_page('calendario');			

		
		//No permitir letras en CI  Gregor 23/09/2013:
				function validar_numero_guion(e) {
					tecla = (document.all)?e.keyCode:e.which;
					if (tecla==8 || tecla==0){
						return true;
					}
					patron = /([0-9\-])/;
					te = String.fromCharCode(tecla);
					return patron.test(te);
				}
		
	</script>
    
    <script type="text/javascript" language="javascript">	
		var theHandle = document.getElementById("divFlotanteTitulo3");
		var theRoot   = document.getElementById("divFlotante3");
		Drag.init(theHandle, theRoot);
	</script>
	

	
	</body>
</html>