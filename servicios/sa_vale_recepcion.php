<?php
//ALTER TABLE `sa_recepcion` CHANGE `fecha_estimada_entrega` `fecha_estimada_entrega` DATETIME NOT NULL ;
//UPDATE `sysgts_altautos_integracion`.`pg_elemento_menu` SET `edicion` = '1' WHERE `pg_elemento_menu`.`id_elemento_menu` =4024 LIMIT 1 ;
@session_start();
require_once ("../connections/conex.php");
define('PAGE_PRIV','sa_listado_citas');// nuevo gregor
//define('PAGE_PRIV','sa_recepcion');//antes
require_once("../inc_sesion.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
		
	function load_page($id_calendario){
		$r= getResponse();
		//$calendario=setCalendar($id_calendario,'xajax_cargar_dia');
		//$r->loadCommands($calendario);
		return $r;
	}
	include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
	function listar_citas($page,$maxrows,$order,$ordertype,$capa,$args=''){
		global $spanCI;
		global $spanRIF;
		//@session_start();
		$r=getResponse();

		//procesando argumentos:
		$argumentos=paginator::getExplodeArgs($args);
		if($argumentos['fecha_cita']=='null'){
			$argumentos['fecha_cita']=adodb_date(DEFINEDphp_DATE);
		}
		$c=new connection();
		$c->open();
		$sa_v_datos_cita=$c->sa_v_datos_cita;
		$q = new query($c);
		$q->add($sa_v_datos_cita);
		
		$q->where(new criteria(sqlEQUAL,$sa_v_datos_cita->fecha_cita,field::getTransformType($argumentos['fecha_cita'],field::tDate)))->
			where(new criteria(sqlEQUAL,$sa_v_datos_cita->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']))->
			where(new criteria(sqlNOTEQUAL,$sa_v_datos_cita->estado_cita,"'CANCELADA'"))->
			where(new criteria(sqlNOTEQUAL,$sa_v_datos_cita->estado_cita,"'POSPUESTA'"))->
			where(new criteria(sqlNOTEQUAL,$sa_v_datos_cita->estado_cita,"'PROCESADA'"))->
			where(new criteria(sqlNOTEQUAL,$sa_v_datos_cita->estado_cita,"'RETRAZADA'"))
			;
	
			
		if($argumentos['origen_cita']!='null'){
			$q->where(new criteria(sqlEQUAL,$sa_v_datos_cita->origen_cita,"'".$argumentos['origen_cita']."'"));
			$filtros='<span class="filter" title="Eliminar filtro" onclick="cita_date.origen_cita=null;r_dialogo_citas();">Filtrado por Origen: '.$argumentos['origen_cita'].'</span>';
		}
		if($argumentos['estado_cita']!='null'){
		
			$q->where(new criteria(sqlEQUAL,$sa_v_datos_cita->estado_cita,"'".$argumentos['estado_cita']."'"));
			$filtros.='<span class="filter" title="Eliminar filtro" onclick="cita_date.estado_cita=null;r_dialogo_citas();obj(\'filtro_estados\').value=\'null\';">Filtrado por Estado: '.$argumentos['estado_cita'].'</span>';
		}
		$paginator = new paginator('xajax_listar_citas',$capa,$q,$maxrows);
		
		$rec=$paginator->run($page,$order,$ordertype,$args);
		
		$origen=array('PROGRAMADA'=>getUrl('img/iconos/cita_programada.png'),'ENTRADA'=>getUrl('img/iconos/cita_entrada.png'));
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han encontrado registros</div>';
			}else{
			
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				<td>&nbsp;</td>
				<td>'.$paginator->get($sa_v_datos_cita->id_cita,'N').'</td>
				<td nowrap="nowrap">'.$paginator->get($sa_v_datos_cita->hora_inicio_cita,'Hora').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->origen_cita,'Origen').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->estado_cita,'Estado').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->placa,'Placa').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->cedula_cliente,$spanCI."/".$spanRIF).'</td>
				<td>'.$paginator->get($sa_v_datos_cita->nombre,'Nombre').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->apellido,'Apellido').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->asesor,'Asesor').'</td>
				
				</tr></thead><tbody>';
				$class='';
				foreach($rec as $v){
					$html.='<tr class="'.$class.'" onclick="xajax_cargar_cita('.$rec->id_cita.');">
					<td width="1%"><button type="button" onclick="xajax_cargar_cita('.$rec->id_cita.');"><img border="0" alt="cargar cita" src="'.getUrl('img/iconos/minselect.png').'" /></button></td>
					<td>'.$rec->id_cita.'</td>
					<td nowrap="nowrap">'.$rec->hora_inicio_cita_12.'</td>
					<td align="center"><img border="0" src="'.$origen[$rec->origen_cita].'" /></td>
					<td>'.$rec->estado_cita.'</td>
					<td>'.$rec->placa.'</td>
					<td>'.$rec->cedula_cliente.'</td>
					<td>'.$rec->nombre.'</td>
					<td>'.$rec->apellido.'</td>
					<td>'.$rec->asesor.'</td>
					</tr>';
					if($class==''){
						$class='impar';
					}else{
						$class='';
					}
				}
				$html.='</tbody></table>';
			}
			
		}
		
		$r->assign($capa,inner,$html);
		$r->assign('fecha_cita',inner,$argumentos['fecha_cita']);
		$r->assign('paginador',inner,'Mostrando '.$paginator->count.' resultados de un total de '.$paginator->totalrows.' '.$paginator->getPages());
		$r->assign('filtros',inner,ifnull($filtros));
		
		$r->script("setDivWindow('cuadro_citas','titulo_citas',true);
		cita_date.page=".$paginator->page.";
		cita_date.maxrows=".$paginator->maxrows.";
		cita_date.order='".$paginator->order."';
		cita_date.ordertype='".$paginator->ordertype."';
		cita_date.origen_cita='".$argumentos['origen_cita']."';
		cita_date.fecha='".$argumentos['fecha_cita']."';
		cita_date.estado_cita='".$argumentos['estado_cita']."';
		");
		
		$c->close();
		return $r;
	}
	
	function getIntervalz($defined_date,$c){
		//$defined_date=adodb_date(DEFINEDphp_DATE);
		$sa_v_intervalo=$c->sa_v_intervalo;
		$qintervalo=new query($c);
		$qintervalo->add($sa_v_intervalo);
		$fechasql=field::getTransformType($defined_date,field::tDate);
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
		return $interval;
	}
	
	function cargar_cita($id_cita){
		$r= getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			//$r->assign($capa,inner,'Acceso denegado');
			$r->script('window.location="sa_listado_citas.php";');
			return $r;
		}
		//cargando la cita:
		$c=new connection();
		$c->open();
		$rec=$c->sa_v_datos_cita->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_datos_cita->id_cita,$id_cita));
		if($rec){
			
			//verificando el registro del cliente
			if($rec->status=='Inactivo'){
				$r->script('_alert("Debe Activar el prospecto del cliente");window.location="sa_listado_citas.php";');
				return $r;
			}
			//vehiculo
			$placa=$c->getQuery($c->sa_v_placa);
			$placa->where(new criteria(sqlEQUAL,$c->sa_v_placa->id_registro_placas,$rec->id_registro_placas));
			$recplaca=$placa->doSelect();
			if($recplaca){
				//verificando si el vehiculo se ha registrado completamente:
				if($recplaca->chasis=='' || $recplaca->id_unidad_basica ==0 || $recplaca->parcial == 1){
					$r->script('_alert("No se ha completado el registro del Veh&iacute;culo");window.location="sa_listado_citas.php";');
					return $r;
				}
				$r->loadCommands(cargar_vehiculo($recplaca));
			}
			//cita
			$r->loadCommands(cargar_datos_cita($rec));
			//cliente
			$r->loadCommands(cargar_cliente($rec));
			//llamando al inventario
			$r->assign('inventario','src','sa_inventario_frame.php?from=recepcion&id_cita='.$rec->id_cita);
			
			//cargando la hora exacta de haber cargado la cita:
			
			setLocaleMode();
			$hora=time();
			$r->assign('hora_entrada','value',$hora);
			$r->alert('Hora de inicio capturada: '.adodb_date(DEFINEDphp_TIME,$hora));
			
			//calculando la hora prometida en base el acumulado de la cita:
			
			
			//extrae el intervalo actual para efectuar los calculos:
			$intervalo=getIntervalz(adodb_date(DEFINEDphp_DATE,$hora),$c);
			//extrae la base_ut correspondiente para convertir a minutos las ut acumuladas de las citas
			$base_ut=getBaseUt($_SESSION['idEmpresaUsuarioSysGts'],$c);
			$minutos=$rec->acumulador*60/$base_ut;
			//las horas del dia son 24, en esta caso se evalua seg�n la jornada laboral:
			$dias=($minutos/60)/($intervalo->duracion_jornada/60);
			
			if($rec->acumulador==0){
				$r->script('_alert("NOTA IMPORTANTE\n\nLa Cita no posee motivos que permitan determinar la fecha estimada de Promesa, por el cual debe cambiar la fecha manualmente.\n\nSi la cita fue asignada seg&uacute;n algunos Motivos, es posible que los UT correspondientes a los mismos sean establecidos a 0 al momento de la asignaci&oacute;n de la cita, los cuales no pueden ser restablecidos al cambiarse por el Mantenimiento \"Motivos de Cita\".");
				obj("capa_datos_prometida").style.color="red";');
				
			}
			
			//evalua si la tarea requiere de m�s de un d�a
			if($dias>1){
				$fecha_proxima=dateAddLab($hora,$dias,($intervalo->dias_semana!=6));
				//adelanta hasta dicho d�a y establece la hora actual ya que es poco factible determinar una hora precisa:
				$fecha_proxima=adodb_mktime(
					intval(adodb_date('H',$hora)),
					intval(adodb_date('i',$hora)),
					intval(adodb_date('s',$hora)),
					intval(adodb_date('m',$fecha_proxima)),
					intval(adodb_date('d',$fecha_proxima)),
					intval(adodb_date('Y',$fecha_proxima))
				);
			}else{
				//suma el acumulado a la hora actual:
				$fecha_proxima=$hora+($minutos*60);
				$horafin= adodb_mktime(
					intval($intervalo->hora_fin_jornada_h),
					intval($intervalo->hora_fin_jornada_m),
					0,
					intval(adodb_date('m',$hora)),
					intval(adodb_date('d',$hora)),
					intval(adodb_date('Y',$hora))
				);
				//verifica si la hora excede la final del periodo:
				if($fecha_proxima>=$horafin){
					//calcula la diferencia en minutos
					$diff=$fecha_proxima-$horafin;
					//saca la hora de inicio laboral proxima:
					$fecha_proxima=dateAddLab($hora,1,($intervalo->dias_semana!=6));
					$fecha_proxima=adodb_mktime(
						intval($intervalo->hora_inicio_jornada_h),
						intval($intervalo->hora_inicio_jornada_m),
						0,
						intval(adodb_date('m',$fecha_proxima)),
						intval(adodb_date('d',$fecha_proxima)),
						intval(adodb_date('Y',$fecha_proxima))
					);
					//suma la diferencia
					$fecha_proxima+=$diff;
				}
				//$r->alert(adodb_date(DEFINEDphp_DATETIME12,$fecha_proxima));
			}			
			//verificando si la hora coincide con la fecha de baja
			$fecha_ibaja=adodb_mktime(
				intval($intervalo->hora_inicio_baja_h),
				intval($intervalo->hora_inicio_baja_m),
				0,
				intval(adodb_date('m',$fecha_proxima)),
				intval(adodb_date('d',$fecha_proxima)),
				intval(adodb_date('Y',$fecha_proxima))
			);
			$fecha_fbaja=adodb_mktime(
				intval($intervalo->hora_fin_baja_h),
				intval($intervalo->hora_fin_baja_m),
				0,
				intval(adodb_date('m',$fecha_proxima)),
				intval(adodb_date('d',$fecha_proxima)),
				intval(adodb_date('Y',$fecha_proxima))
			);
			if($fecha_proxima>$fecha_ibaja && $fecha_proxima<$fecha_fbaja){
				//de ser cierto establece la fecha de fin de la baja:
				$fecha_proxima=$fecha_fbaja;
			}
			
			$fdef=adodb_date("d-m-Y h:i A",$fecha_proxima);
			$r->assign('fecha_prometida','value',$fdef);
			//$r->alert();
			$r->assign('capa_datos_prometida',inner,'('.$fdef.' derivado de '.$minutos.' minutos aprox. seg&uacute;n cita)');
			$reccitaq=$c->sa_recepcion->doQuery($c);
			$reccitaq->where(new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']));
			$reccitaq->where(new criteria(sqlEQUAL,'fecha_entrada','CURRENT_DATE()'));
			$reccount=$c->execute("select ifnull(max(numero_recepcion)+1,1) as num from sa_recepcion ".$reccitaq->getCriteria().";");
			//$r->alert();
			$r->assign('numero_recepcion','value',$reccount['num']);
		}
		$c->close();
		$r->script('close_window("cuadro_citas");');
		return $r;
	}
	
	function cargar_cliente($rec){
		$r=getResponse();
		$r->assign('capa_cedula_cliente',inner,ifnull($rec->cedula_cliente));
		$r->assign('capa_nombre_cliente',inner,ifnull($rec->nombre));
		$r->assign('capa_apellido_cliente',inner,ifnull($rec->apellido));
		$r->assign('capa_telefono_cliente',inner,ifnull($rec->telf));
		$r->assign('capa_celular_cliente',inner,ifnull($rec->otrotelf));
		$r->assign('capa_email_cliente',inner,ifnull($rec->correo));
		return $r;
	}
	
	function cargar_datos_cita($rec){
		$r=getResponse();
		$r->assign('id_cita','value',$rec->id_cita);
		$r->assign('capa_id_cita',inner,$rec->numero_cita);
		$r->assign('capa_fecha_cita',inner,$rec->fecha_cita);
		$r->assign('capa_hora_inicio_cita',inner,$rec->hora_inicio_cita_12);
		$r->assign('capa_asesor',inner,$rec->asesor);
		$r->assign('capa_origen_cita',inner,$rec->origen_cita);
		$r->assign('capa_estado_cita',inner,$rec->estado_cita);
		$r->assign('capa_motivo_cita',inner,ifnull($rec->motivo." / ".$rec->descripcion_submotivo));
		$r->assign('capa_motivo_detalle',inner,ifnull($rec->motivo_detalle));
		return $r;
	}
	
	function cargar_vehiculo($recplaca){
		$r=getResponse();
		$r->assign('capa_marca',inner,$recplaca->nom_marca);
		$r->assign('capa_modelo',inner,$recplaca->nom_modelo);
		$r->assign('capa_version',inner,$recplaca->nom_version);
		$r->assign('capa_unidad',inner,$recplaca->nombre_unidad_basica);
		
		$r->assign('capa_placa',inner,$recplaca->placa);
		$r->assign('capa_chasis',inner,$recplaca->chasis);
		$r->assign('capa_color',inner,$recplaca->color);
		//$r->assign('capa_kilometraje',inner,$recplaca->kilometraje);
		if($recplaca->kilometraje>0)
		$r->assign('kilometraje','value',$recplaca->kilometraje);
		$r->assign('id_registro_placas','value',$recplaca->id_registro_placas);
		
		$r->assign('capa_ano',inner,$recplaca->ano);
		$r->assign('capa_fecha_venta',inner,parseDate(str_date($recplaca->fecha_venta)));
		$r->assign('capa_combustible',inner,$recplaca->nom_combustible);
		$r->assign('capa_transmision',inner,$recplaca->nom_transmision);
		return $r;
	}
	
	function cargar_cliente_pago($idc){
		$r=getResponse();
		//$r->alert($idc);
		$c= new connection();
		$c->open();
		$q = new query($c);
		$q->add($c->cj_cc_cliente);
		$q->where(new criteria(sqlEQUAL,$c->cj_cc_cliente->id,"'".$idc."'"));
		$rec=$q->doSelect();
		if($rec){
			if($rec->status=='Inactivo'){
				$r->alert('No se ha completado del registro del cliente');
				return $r;
			}
			$r->assign('id_cliente_pago','value',$rec->id);
			$r->assign('cedula_cliente_pago','value',ifnull($rec->lci.'-'.$rec->ci));
			$r->assign('capa_nombre_cliente_pago',inner,ifnull($rec->nombre));
			$r->assign('capa_apellido_cliente_pago',inner,ifnull($rec->apellido));
			$r->assign('capa_telefono_cliente_pago',inner,ifnull($rec->telf));
			$r->assign('capa_celular_cliente_pago',inner,ifnull($rec->otrotelf));
			$r->assign('capa_email_cliente_pago',inner,ifnull($rec->correo));
			$r->script('
				close_window("xajax_dialogo_cliente");
				obj("cedula_cliente_pago").readOnly=true;
				//window.frames["inventario"].probando();
			');
		}
		$c->close();
		return $r;
	}
	
	function guardar_vale($form){
            
                global $spanKilometraje;
            
		$r=getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
			//$c->rollback();
                        $r->script("byId('btnGuardar').disabled = false;");
			return $r;
		}
		$c= new connection();
		$c->open();
		$c->begin();
		$r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');
		
		//actualizando el kilometraje:
		
		$kilometraje=$form['kilometraje'];
		if($kilometraje==''){
			$r->script('$("#field_kilometraje").addClass("inputNOTNULL");_alert("Especifique '.$spanKilometraje.'");');
			$r->script("byId('btnGuardar').disabled = false;");
			return $r;
		}else{
			$recp=$c->en_registro_placas->doSelect($c,new criteria(sqlEQUAL,$c->en_registro_placas->id_registro_placas,$form['id_registro_placas']));
			$kilometraje=field::getTransformType($kilometraje,field::tInt);
			if($recp){
				if($kilometraje < $recp->kilometraje){
					$r->script("byId('btnGuardar').disabled = false;");
					$r->script('$("#field_kilometraje").addClass("inputERROR");_alert("No puede especificar un '.$spanKilometraje.' inferior al &uacute;ltimo establecido");');
					return $r;
				}else{
					if(!$c->execute("update en_registro_placas set kilometraje='".$kilometraje."' where id_registro_placas=".intval($form['id_registro_placas']).";")){
						
					}
				}
			}else{
				$r->alert("Error 001");
				return $r;
			}
		}
		
		//extrayendo el empleado de carga
		$id_empleado_carga=$c->pg_usuario->doSelect($c,new criteria(sqlEQUAL,$c->pg_usuario->id_usuario,$_SESSION['idUsuarioSysGts']))->id_empleado;
		setLocaleMode();
		
		
		$recepcion = new table("sa_recepcion");
		
		//agregando campos:
		$recepcion->add(new field('id_recepcion','',field::tInt,null,true));
		$recepcion->add(new field('id_cita','',field::tInt,$form['id_cita'],true));
		
		if($form['check_mismo_cliente']!=''){
			$recepcion->add(new field('id_cliente_pago','',field::tInt,dbNull($form['id_cliente_pago']),true));
		}		
		$recepcion->add(new field('numero_recepcion','',field::tString,$form['numero_recepcion'],true));
		$recepcion->add(new field('numero_entrada','',field::tInt,1,true));
		
		//gregor numeracion recepcion
		/*
		$sql_numeracion_recepcion = sprintf("SELECT IFNULL(MAX(numeracion_recepcion)+1, 1) as numeracion_recepcion from sa_recepcion WHERE
		id_empresa = %s LIMIT 1", 
			valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
		$rs_sql = mysql_query($sql_numeracion_recepcion);
		if (!$rs_sql) return $r->alert(mysql_error()."\n Error generando numero de recepcion \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
		$dt_sql = mysql_fetch_assoc($rs_sql);				
		$numeracion_recepcion = $dt_sql["numeracion_recepcion"];
		*/
		
		$sqlNumeracionRecepcion = sprintf("SELECT * FROM pg_empresa_numeracion
				WHERE id_numeracion = 32
					AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																					WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC
				LIMIT 1",
				valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"),
				valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
		$rsSql = mysql_query($sqlNumeracionRecepcion);
		if (!$rsSql) return $r->alert(mysql_error()."\n Error buscando numero de recepcion \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
		$dtSql = mysql_fetch_assoc($rsSql);
		
		$idEmpresaNumeracionRecepcion = $dtSql["id_empresa_numeracion"];
		$numeracionRecepcion = $dtSql["numero_actual"];
		
		if($numeracionRecepcion == NULL){ $r->script("byId('btnGuardar').disabled = false;"); return $r->alert("No se pudo crear el numero de recepcion, compruebe que la empresa tenga numeracion de recepciones");}
		
		$recepcion->add(new field('numeracion_recepcion','',field::tInt,$numeracionRecepcion,true));
		//
		
		$recepcion->add(new field('id_empresa','',field::tInt,$_SESSION['idEmpresaUsuarioSysGts'],true));
		//ORIGINAL
		//$recepcion->add(new field('fecha_entrada','',field::tFunction,'current_date()',true));
//CAMBIO 08 marzo 2010 SOLICITAR FECHA DE LA CITA
		//$reccitap = $c->sa_v_datos_cita->doSelect($c, new criteria(sqlEQUAL,'id_cita',$form['id_cita']));
		//$r->alert("NOTA se esta tomando la fecha de la cita: ".parseDate(str_date($reccitap->fecha_cita)));
		$recepcion->add(new field('fecha_entrada','',field::tString,date("Y-m-d"),true));
		
		
		$fecha_p="STR_TO_DATE('".$form['fecha_prometida']."',".DEFINED_DATETIME12.")";
		$recepcion->add(new field('fecha_estimada_entrega','',field::tFunction,$fecha_p));
		
		//tomar la hora al entrar en la pantalla
		//$recepcion->add(new field('hora_entrada','',field::tFunction,'NOW()',true));
		$recepcion->add(new field('hora_entrada','',field::tTime,adodb_date(DEFINEDphp_TIME,intval($form['hora_entrada'])),true));
		
		
		$recepcion->add(new field('permanece_cliente_reparacion','',field::tBool,$form['permanece_cliente_reparacion'],true));
		$recepcion->add(new field('permanece_cliente_partes','',field::tBool,$form['permanece_cliente_partes'],true));
		$recepcion->add(new field('observaciones','',field::tString,$form['observaciones'],false));
		$recepcion->add(new field('nivel_combustible','',field::tFloat,$form['nivel_combustible'],true));
		$recepcion->add(new field('nombre_poliza','',field::tString,dbnull($form['nombre_poliza']),false));
		$recepcion->add(new field('kilometraje','',field::tFloat,$kilometraje,false));
		$recepcion->add(new field('id_tipo_vale','',field::tInt,$form['id_tipo_vale'],true));

		if($form['serviexpress']==""){                    
		}else{
			$recepcion->add(new field('serviexp','',field::tInt,$form['serviexpress'],false));
		}

		if($form['puente']==""){                    
		}else{
			$recepcion->add(new field('puente','',field::tInt,$form['puente'],false));
		}           
		
		if(esPuerto()){
			$recepcion->add(new field('nro_llaves','',field::tString,$form['nro_llaves'],true));
		}else{
			$recepcion->add(new field('nro_llaves','',field::tString,$form['nro_llaves'],false));
		}
		
		$subq=new query($c);
		$tablar= new table('sa_recepcion');
		$campo=new field('id_recepcion','',field::tString,'');
		$campo->setFunction(new _function('count'));
		$tablar->add($campo);
		
		$subq->add($tablar);
		$subq->where(new criteria(sqlEQUAL,$c->sa_recepcion->fecha_entrada,'CURRENT_DATE()'));
		$recq=$subq->doSelect();
	
		$recepcion->add(new field('numero_entrada','',field::tInt,$recq->id_recepcion+1));
		
		/*$q= new query($c);
		$q->add($recepcion);		
		$r->alert(utf8_encode($q->getInsert()));		
		$rr=$recepcion->validate();
		foreach ($rr as $v){
			$rm.=$v->getObject()->getName().' '.$v->getMessage().'     ';
		}
		$r->alert(utf8_encode($rm));*/
		
		/*$r->alert($recepcion->getInsert($c,$recepcion->id_recepcion));
		return $r;*/
	

		$result=$recepcion->doInsert($c,$recepcion->id_recepcion);
		
		//return $r;
		if($result===true){
			
			//guardando las fallas:
			$lastid=$c->soLastInsertId(); //IMPORTANTE QUE SEA PRIMERO SINO SE DAÑA
			
			//si guardo la recepcion
			
			// ACTUALIZA numeracion recepcion
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracionRecepcion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $r->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			
			//$r->alert($lastid);
			$count_record=0;
			if(isset($form['id_recepcion_falla'])){
				foreach($form['id_recepcion_falla'] as $k => $v){
					$sql='';
					//verificando por accion
					if($form['actionf'][$k]=='add'){
						//$r->alert($form['descripcion_falla'][$k]);
						if($form['descripcion_falla'][$k]==''){
							$r->script('$("#row_fallas'.($k+1).'").addClass("inputNOTNULL");');
							$error=true;
							continue;
						}
						if($form['id_recepcion_falla'][$k]==''){
							$sql=sprintf("INSERT INTO sa_recepcion_falla(id_recepcion_falla,id_recepcion,descripcion_falla) VALUES (NULL , '%s', '%s');",
							$lastid,
							$c->parseUTF8(addslashes($form['descripcion_falla'][$k]))
							);
							$count_record++;
						}else{
							/*$sql=sprintf("UPDATE sa_recepcion_falla SET ut=%s where id_recepcion_falla=%s;",
							field::getTransformType($form['ut'][$k],field::tFloat),
							$form['id_recepcion_falla'][$k]
							);*/
						}
					}else{
						/*if($form['id_recepcion_falla'][$k]!=''){
							$sql=sprintf("DELETE FROM sa_recepcion_falla where id_recepcion_falla=%s;",
							$form['id_recepcion_falla'][$k]
							);
						}*/
					}
					if($sql!='' && !$error){
						//$r->alert($sql);
						$resultd = $c->soQuery($sql);
						if(!$resultd){
							//$r->alert('error');
							$error=true;
						}
					}
				}
			}else{
				$r->alert('Especifique al menos 1 falla');
				$r->script("byId('btnGuardar').disabled = false;");
				$c->rollback();
				return $r;
			}
			if($count_record==0){
				$r->alert('Especifique al menos 1 falla');
				$r->script("byId('btnGuardar').disabled = false;");
				$c->rollback();
				return $r;
			}
			$maximo=getParam($_SESSION['idEmpresaUsuarioSysGts'],"'MAXIMO FALLAS'",$c);
			if($maximo!=''){
				if($count_record>intval($maximo)){
					$c->rollback();
					$r->script('_alert("El m&aacute;ximo de Fallas permitido es de: '.$maximo.'");');
					$r->script("byId('btnGuardar').disabled = false;");
					return $r;
				}
			}
		
			if ($error){
			
				$r->alert('Faltan datos');
                $r->script("byId('btnGuardar').disabled = false;");
				$c->rollback();
				return $r;
			}
		
			//guardando los datos de la orden
			if($form['id_tipo_orden']==''){
				$r->script('$("#field_id_tipo_orden").addClass("inputNOTNULL");');
				$c->rollback();
				$r->alert('Faltan datos');
                                $r->script("byId('btnGuardar').disabled = false;");
				return $r;
			}

			$sqlCliente = sprintf("SELECT id_cliente_contacto FROM sa_cita WHERE id_cita = %s LIMIT 1",
								$form["id_cita"]);
			$rsCliente = mysql_query($sqlCliente);
			if(!$rsCliente){ return $r->alert(mysql_error()."\n\nLine: ".__LINE__); }

			$rowCliente = mysql_fetch_assoc($rsCliente);

			$sa_orden = new table("sa_orden");
			$sa_orden->add(new field('id_orden','',field::tInt,null,true));
			$sa_orden->add(new field('id_tipo_orden','',field::tInt,$form['id_tipo_orden'],true));
			$sa_orden->add(new field('id_recepcion','',field::tInt,$lastid,true));
			$sa_orden->add(new field('id_empresa','',field::tInt,$_SESSION['idEmpresaUsuarioSysGts'],true));
			$sa_orden->add(new field('id_estado_orden','',field::tInt,1,true));
			$sa_orden->add(new field('id_empleado','',field::tInt,$id_empleado_carga,true));
			$sa_orden->add(new field('id_cliente','',field::tInt,$rowCliente["id_cliente_contacto"],true));
			$sa_orden->add(new field('tiempo_orden','',field::tFunction,'NOW()',true));
			$sa_orden->add(new field('prioridad','',field::tInt,$form['prioridad'],true));
			
			//numeraciones orden gregor
			/*$sql_numero_orden = sprintf("SELECT IFNULL(MAX(numero_orden)+1, 1) as numero_orden from sa_orden WHERE
			id_empresa = %s LIMIT 1", 
				valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
			$rs_sql = mysql_query($sql_numero_orden);
			if (!$rs_sql) return $r->alert(mysql_error()."Error generando numero de orden \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
			$dt_sql = mysql_fetch_assoc($rs_sql);				
			$numero_orden = $dt_sql["numero_orden"];*/
			
			$sqlNumeroOrden= sprintf("SELECT * FROM pg_empresa_numeracion
				WHERE id_numeracion = 33
					AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																					WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC
				LIMIT 1",
				valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"),
				valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
			$rsSql = mysql_query($sqlNumeroOrden);
			if (!$rsSql) return $r->alert(mysql_error()."\n Error buscando numero de orden \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
			$dtSql = mysql_fetch_assoc($rsSql);
			
			$idEmpresaNumeroOrden = $dtSql["id_empresa_numeracion"];
			$numeroOrden = $dtSql["numero_actual"];
			
			if($numeroOrden == NULL){ $r->script("byId('btnGuardar').disabled = false;"); return $r->alert("No se pudo crear el numero de orden, compruebe que la empresa tenga numeracion de ordenes");}
			
			$sa_orden->add(new field('numero_orden','',field::tInt,$numeroOrden,true));
				
			$resultorden=$sa_orden->doInsert($c,$sa_orden->id_orden);
			if($resultorden!==true){
				$c->rollback();
				$r->alert("ERROR AL GENERAR LA ORDEN, consulte con el administrador del sistema \n".mysql_error(). "\n\nOtro error".$resultorden[0]->errorMsq."\n\nLinea: ".__LINE__);
				$r->script("byId('btnGuardar').disabled = false;");
				return $r;
			}else{
				
				$r->script('_alert("Orden Generada satisfactoriamente.");');
				$r->script("byId('btnGuardar').disabled = false;");
				$id_orden= $c->soLastInsertId();//IMPORTANTE QUE SEA PRIMERO
				
				// ACTUALIZA numeracion orden
				$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeroOrden, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $r->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			//estimando la primera fase de la orden:
			/*$sa_mp= new table("sa_mp");
			$sa_mp->add(new field('id_mp','',field::tInt,null,true));
			$sa_mp->add(new field('id_mp','',field::tInt,null,true));*/
			
			//registrando la orden en el puesto predeterminado
			//obteniendo datos del puesto predeterminado:
			$recpuesto=$c->pg_parametros_empresas->doSelect($c, new criteria(sqlAND,array(
				new criteria(sqlEQUAL,$c->pg_parametros_empresas->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']),
				new criteria(sqlEQUAL,$c->pg_parametros_empresas->descripcion_parametro,3)
			)));
					
			$sa_registro_unidad_puesto = new table("sa_registro_unidad_puesto");
			$sa_registro_unidad_puesto->add(new field('id_registro_unidad_puesto','',field::tInt,null,true));
			$sa_registro_unidad_puesto->add(new field('id_orden','',field::tInt,$id_orden,true));
			$sa_registro_unidad_puesto->add(new field('id_puesto','',field::tInt,1,true));////puesto definido, siempre es 1, antes estaba $recpuesto->id_parametro buscando por id 3, siempre va a dar error en multiempresa
			$sa_registro_unidad_puesto->add(new field('tiempo_inicio','',field::tFunction,'NOW()',true));
			$sa_registro_unidad_puesto->add(new field('id_empleado_aprobacion','',field::tInt,$id_empleado_carga,true));
		
			$resultp=$sa_registro_unidad_puesto->doInsert($c,$sa_registro_unidad_puesto->id_registro_unidad_puesto);
			if($resultp!==true){
				$c->rollback();
				$r->alert('ERROR AL GENERAR LA ORDEN, registro de puesto, consulte con el administrador del sistema');
				$r->alert(utf_export($resultp[0]->getMessage()));
				return $r;
			}else{
				//$r->script('_alert("Orden Generada satisfactoriamente.");');
			}
		
		
			$r->script('
			_alert("Se ha registrado el Vale de Recepci&oacute;n");			
			$(".field div").html("&nbsp;");
			$(".field input,.field select").val("");
			$("input[type=hidden]").val("");
			$(".label input[type=checkbox]").attr("checked",false);
			');
			//obteniendo los datos de la cita:
			$qb = new query($c);
			$qb->add($c->sa_v_datos_cita);
			$qb->where(new criteria(sqlEQUAL,$c->sa_v_datos_cita->id_cita,$form['id_cita']));
			$rec=$qb->doSelect();
			
			$hora_cita=str_datetime($rec->fecha_cita,$rec->hora_fin_cita);
			//hay que tomar el time anterior:
			//$hora=time();
			$hora=intval($form['hora_entrada']);
			if($hora >= $hora_cita){
				$estado='RETRAZADA';
			}else{
				$estado='PROCESADA';
			}
			//$r->alert($estado);
			
			//modificando el estatus de la cita
			$cita = new table("sa_cita");
			$cita->add(new field('id_cita','',field::tInt,$form['id_cita']));
			
			$cita->add(new field('estado_cita','',field::tString,$estado));
			$result2= $cita->doUpdate($c,$cita->id_cita);
			
			if($result2===true){
				$r->script('
				window.frames["inventario"].guardar_inventario(true,'.$lastid.','.$id_orden.');
				//window.location="sa_listado_citas.php";
				//window.location="sa_listado_citas.php";
				//window.location="sa_orden_form.php?idv='.$lastid.'&doc_type=2&acc=3&ide='.$_SESSION['idEmpresaUsuarioSysGts'].'&id='.$id_orden.'";');				
				$c->commit();
			}
			
			
		}else{
			foreach ($result as $ex){
				if($ex->type==errorMessage::errorNOTNULL){					
						//$r->script('obj("'.$ex->getObject()->getName().'").className="inputNOTNULL";');
						$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputNOTNULL");');					                                                
				}elseif($ex->type==errorMessage::errorType){
					//$r->script('obj("'.$ex->getObject()->getName().'").className="inputERROR";');
					$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputERROR");');                                        
				}else{
					if($ex->numero==connection::errorUnikeKey){
						$r->alert($ex->numero);
						return $r;
					}/*else{
						$r->alert($ex);
					}*/
				}
			}
	
			$r->alert('Faltan datos');
            $r->script("byId('btnGuardar').disabled = false;");
		}
		
		
		
		$c->close();
		return $r;
	}
        
	function tipoVale(){//combo list de tipos de vale
		$objResponse = new xajaxResponse();
		
		$query = "SELECT id_tipo_vale, descripcion FROM sa_tipo_vale WHERE activo = 1";
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__); }
		
		$html = "<select  name='id_tipo_vale'>";
		$html .= "<option value=''>SELECCIONE</option>";
		while($row = mysql_fetch_assoc($rs)){
				  $html .= "<option value='".$row['id_tipo_vale']."'>".utf8_encode($row['descripcion'])."</option>";                                        
		}
		$html .= "</select>";
		
		$objResponse->assign("field_id_tipo_vale","innerHTML",$html);
		
		return $objResponse;            
	}
	
	/**
	*
	*
	*/
	function esPuerto(){
		$objResponse = new xajaxResponse();
		
		// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
		$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($_SESSION["idEmpresaUsuarioSysGts"], "int"));
		$rsConfig403 = mysql_query($queryConfig403);
		if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsConfig403 = mysql_num_rows($rsConfig403);
		$rowConfig403 = mysql_fetch_assoc($rsConfig403);
		if($rowConfig403['valor'] == NULL){
			//$rowConfig403['valor'] = 1; //por defecto venezuela 1
			die("No se ha configurado formato de cheque. 403");
		}
		
		if($rowConfig403['valor'] == "3"){
			return true;
		}
		
		return false;
	}

	
	xajaxRegister('load_page');
	xajaxRegister('listar_citas');
	xajaxRegister('cargar_cita');
	xajaxRegister('cargar_cliente_pago');
	xajaxRegister('guardar_vale');
	xajaxRegister('tipoVale');
	
	xajaxProcess();
	
	$c= new connection();
	$c->open();
	//echo $c->sa_tipo_orden->getSelect($c,new criteria(sqlEQUAL,$c->sa_tipo_orden->orden_generica,1))."|||||";
	
	//busca el id del tipo de orden "generica" por empresa, es decir la unica generica es la "SIN ASIGNAR" (la requiere para que salga "selected" en la funcion de crear listado
	$generic=$c->sa_tipo_orden->doSelect($c,new criteria(sqlAND,new criteria(sqlEQUAL,$c->sa_tipo_orden->orden_generica,1),new criteria(sqlEQUAL,$c->sa_tipo_orden->id_empresa,$_SESSION['idEmpresaUsuarioSysGts'])))->id_tipo_orden;
	
	//BUSCA LA ASOCIACION id_tipo_orden => nombre_tipo_orden para mostrar en la funcion crear listado
	$tipos_orden=$c->sa_tipo_orden->doSelect($c,new criteria(sqlAND,new criteria(sqlEQUAL,$c->sa_tipo_orden->orden_generica,1),new criteria(sqlEQUAL,$c->sa_tipo_orden->id_empresa,$_SESSION['idEmpresaUsuarioSysGts'])))->getAssoc('id_tipo_orden','nombre_tipo_orden');
	

	
	$prioridades=array(
		1=>'ALTA',
		2=>'MEDIA',
		3=>'BAJA'
	);
	includeDoctype();
		
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
    
                <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
                
		<style>
			.textoRojoNegrita{
				color:red;
			}
        </style>
                
		<script>
			var cita_date = {
				fecha: null,
				date:new Date(),
				page:0,
				maxrows:5,
				order:'sa_v_datos_cita.hora_inicio_cita',
				ordertype:null,
				estado_cita:null,
				origen_cita:null
			}
			function r_dialogo_citas(){
				xajax_listar_citas(cita_date.page,cita_date.maxrows,cita_date.order,cita_date.ordertype,'lista_citas','fecha_cita='+cita_date.fecha+',origen_cita='+cita_date.origen_cita+',estado_cita='+cita_date.estado_cita);
			}
			/*function dialogo_citas(origen,estado){
				if(getIsNull(origen)){
					if(cita_date.origen_cita!=origen){
						cita_date.page=0;
					}
					cita_date.origen_cita=origen;
				}else{
					cita_date.origen_cita=null;
				}
				if(getIsNull(estado)){
					if(cita_date.estado_cita!=estado){
						cita_date.page=0;
					}
					cita_date.estado_cita=estado;
				}else{
					cita_date.estado_cita=null;
				}
				r_dialogo_citas();
			}*/
			
			function  calendar_onselect (calendar,date){//DD-MM-AAAA
				if (calendar.dateClicked) {
					var dia=date.substr(0,2);
					var mes=parseFloat(date.substr(3,2))-1;
					var ano=date.substr(6,4);
					cita_date.fecha=date;
					r_dialogo_citas();
					//xajax_listar_citas(cita_date.page,cita_date.maxrows,cita_date.order,cita_date.ordertype,'lista_citas','fecha_cita='+date+',origen_cita='+cita_date.origen_cita);
					cita_date.date=new Date(ano,mes,dia);
					calendar.hide();
				}
			}			
			function  calendar_onclose (calendar){
				calendar.hide();
			}
			
			var cita_calendar = new Calendar(1,null,calendar_onselect,calendar_onclose);
			cita_calendar.setDateFormat("%d-%m-%Y");
			function cargar_cita_fecha(_obj){
				cita_calendar.create();
				cita_calendar.setDate(cita_date.date);
				cita_calendar.showAtElement(_obj);
			}
			
			function cliente_pago(_obj){
				if(_obj.checked){
					$('#cliente_pago').show();
				}else{
					
					$('#cliente_pago').hide();
				}
				
			}
			
			function buscar_cliente_pago(){
				xajax_dialogo_cliente(0,10,'cj_cc_cliente.ci','','cliente_dc',
						'busqueda='+obj('cedula_cliente_pago').value+',callback=xajax_cargar_cliente_pago,parent=form_cita');
			}
			var tabla_fallas= new Array();
			var counter_fallas=0;
			
			function fallas_add(datos){
				if(datos==null){
					datos={
						falla:'',
						id_recepcion_falla:'',
						descripcion_falla:''
					}
				}
				var tabla=obj('tbody_fallas');
				var nt = new tableRow("tbody_fallas");
				tabla_fallas[counter_fallas]=nt;
				counter_fallas++;
				nt.setAttribute('id','row_fallas'+counter_fallas);
				nt.$.className='field';
				//var c1= nt.addCell();
					//c1.$.className='field';
					//c1.setAttribute('style','width:30%;');
					//c1.$.innerHTML=counter_fallas;
				var c2 = nt.addCell();
					c2.$.innerHTML='<input type="text" style="width:99%" name="descripcion_falla[]" id="descripcion_falla'+counter_fallas+'" value="'+datos.descripcion_falla+'" /><input  id="actionf'+counter_fallas+'" type="hidden" name="actionf[]" value="add" /><input  id="id_recepcion_falla'+counter_fallas+'" type="hidden" name="id_recepcion_falla[]" value="'+datos.id_recepcion_falla+'" />';
				var c3 = nt.addCell();
					c3.$.innerHTML='<button type="button" onclick="fallas_quit('+counter_fallas+')"><img border="0" alt="quitar" src="<?php echo getUrl('img/iconos/minus.png');?>" /></button>';
				obj('descripcion_falla'+counter_fallas).focus();
			}
			
			function fallas_quit(cont){
				if(_confirm("&iquest;Desea eliminar la falla?")){
					var fila=obj('row_fallas'+cont);
					fila.style.display='none';
					var action=obj('actionf'+cont);
					//alert(action);
					action.value='delete';
				}
			}
		</script>
		<style type="text/css">
		
		</style>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Recepci&oacute;n</span><br />
			<span class="subtitulo_pagina" >(Vale de Recepci&oacute;n)</span></td>
			
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		
	</div>
	
	<div class="area">
		<form id="form_cita" onSubmit="return false;" >
		<input type="hidden" name="id_cita" id="id_cita" />
		<input type="hidden" name="id_cliente_pago" id="id_cliente_pago" />
		<input type="hidden" name="hora_entrada" id="hora_entrada" />
		<table class="insert_table">
			<thead><tr><td class="caption" colspan="8" >Datos de la Cita</td></tr></thead>
			<tbody>
				<tr>
					<td class="label">Numero:</td>
					<td class="field" id="field_id_cita">
						<div id="capa_id_cita" style="float:left; width:60px;">&nbsp;</div><button type="button" title="Citas pendientes" onClick="r_dialogo_citas();" style="display:none;"><img border="0" src="<?php echo geturl('img/iconos/cita_programada.png'); ?>" />&nbsp;Cargar Cita</button>
					</td>
					<td class="label">Fecha:</td>
					<td class="field">
						<div id="capa_fecha_cita">&nbsp;</div>
					</td>
					<td class="label">Hora:</td>
					<td class="field">
						<div id="capa_hora_inicio_cita">&nbsp;</div>
					</td>
					<td class="label">Asesor:</td>
					<td class="field">
						<div id="capa_asesor">&nbsp;</div>
					</td>
				</tr>
				<tr>
					<td class="label">Origen:</td>
					<td class="field">
						<div id="capa_origen_cita">&nbsp;</div>
					</td>
					<td class="label">Estado:</td>
					<td class="field">
						<div id="capa_estado_cita">&nbsp;</div>
					</td>
					<td class="label">Motivo/Submotivo:</td>
					<td class="field" colspan="3">
						<div id="capa_motivo_cita">&nbsp;</div>
					</td>
				</tr>
                <tr>
                	<td class="label">Detalle:</td>
					<td class="field" colspan="7">
						<div id="capa_motivo_detalle">&nbsp;</div>
					</td>
                </tr>
			</tbody>
		
		</table>
		<table class="insert_table">
			<thead><tr><td class="caption" colspan="8">Datos del Veh&iacute;culo</td></tr></thead>
			<tbody>
				<tr>
					<td class="label">Marca:</td>
					<td class="field">
						<div id="capa_marca">&nbsp;</div>
					</td>
					<td class="label">Modelo:</td>
					<td class="field">
						<div id="capa_modelo">&nbsp;</div>
					</td>
					<td class="label">Versi&oacute;n:</td>
					<td class="field">
						<div id="capa_version">&nbsp;</div>
					</td>
					<td class="label">Unidad:</td>
					<td class="field">
						<div id="capa_unidad">&nbsp;</div>
					</td>
				</tr>
				<tr>
					<td class="label">Placa:</td>
					<td class="field">
						<div id="capa_placa">&nbsp;</div>
					</td>
					<td class="label">Chasis:</td>
					<td class="field">
						<div id="capa_chasis">&nbsp;</div>
					</td>
					<td class="label">Color:</td>
					<td class="field">
						<div id="capa_color">&nbsp;</div>
					</td>
					<td class="label"><?php echo $spanKilometraje; ?>:</td>
					<td class="field" id="field_kilometraje">
						<div id="capa_kilometraje">
							<input type="text" name="kilometraje" id="kilometraje" onKeyPress="return inputFloat(event);" />
							<input type="hidden" name="id_registro_placas" id="id_registro_placas"  />
						</div>
					</td>
				</tr>
				<tr>
					<td class="label">A&ntilde;o:</td>
					<td class="field">
						<div id="capa_ano">&nbsp;</div>
					</td>
					<td class="label">Fecha de Venta:</td>
					<td class="field">
						<div id="capa_fecha_venta">&nbsp;</div>
					</td>
					<td class="label">Transmisi&oacute;n:</td>
					<td class="field">
						<div id="capa_transmision">&nbsp;</div>
					</td>
					<td class="label">Combustible:</td>
					<td class="field">
						<div id="capa_combustible">&nbsp;</div>
					</td>
				</tr>

			</tbody>
		
		</table>
		<div >
			
			<table class="insert_table">
			<thead><tr><td class="caption" colspan="6">Datos del Cliente</td></tr></thead>
			<tbody>
				<tr>
					<td class="label"><?php echo $spanCI."/".$spanRIF; ?></td>
					<td class="field">
						<div id="capa_cedula_cliente">&nbsp;</div>
					</td>
					<td class="label">Apellido:</td>
					<td class="field">
						<div id="capa_apellido_cliente">&nbsp;</div>
					</td>
					<td class="label">Nombre:</td>
					<td class="field">
						<div id="capa_nombre_cliente">&nbsp;</div>
					</td>
				</tr>
				<tr>
					<td class="label">Tel&eacute;fono:</td>
					<td class="field">
						<div id="capa_telefono_cliente">&nbsp;</div>
					</td>
					<td class="label">Celular:</td>
					<td class="field">
						<div id="capa_celular_cliente">&nbsp;</div>
					</td>
					<td class="label">Correo:</td>
					<td class="field">
						<div id="capa_email_cliente">&nbsp;</div>
					</td>
				</tr>
			</tbody>					
			</table>
			
			<label for="check_mismo_cliente" title="Definir otro cliente de Pago">
				<input id="check_mismo_cliente" name="check_mismo_cliente" onClick="cliente_pago(this);" type="checkbox" title="Definir cliente de Pago" />
				Definir otro Cliente de Pago / Seguro</label>
			
			<table id="cliente_pago" style="display:none;" class="insert_table">
			<thead><tr>
				<td class="caption" colspan="6">Datos del Cliente de Pago</td></tr></thead>
			<tbody>
				<tr>
					<td width="15%" class="label"><?php echo $spanCI."/".$spanRIF; ?></td>
					<td class="field" id="field_id_cliente_pago">
						<input type="text" name="cedula_cliente_pago" id="cedula_cliente_pago"  />
						<button type="button" onClick="buscar_cliente_pago();"><img border="0" src="<?php echo geturl('img/iconos/find.png'); ?>" /></button>
						
					</td>
					<td class="label">Apellido:</td>
					<td class="field">
						<div id="capa_apellido_cliente_pago">&nbsp;</div>
					</td>
					<td class="label">Nombre:</td>
					<td class="field">
						<div id="capa_nombre_cliente_pago">&nbsp;</div>
					</td>
				</tr>
				<tr>
					<td class="label">Tel&eacute;fono:</td>
					<td class="field">
						<div id="capa_telefono_cliente_pago">&nbsp;</div>
					</td>
					<td class="label">Celular:</td>
					<td class="field">
						<div id="capa_celular_cliente_pago">&nbsp;</div>
					</td>
					<td class="label">Correo:</td>
					<td class="field">
						<div id="capa_email_cliente_pago">&nbsp;</div>
					</td>
				</tr>
				<tr>
					<td class="label">Nombre de la P&oacute;liza:</td>
					<td class="" colspan="5">
						<input type="text" name="nombre_poliza" id="nombre_poliza" />&nbsp;(aplica para orden tipo Seguros)
					</td>
				</tr>
			</tbody>					
			</table>
			
			<table class="insert_table">
				<thead><tr><td class="caption" colspan="6">Datos del Vale de Recepci&oacute;n</td></tr></thead>
				<tbody>
					<tr>
						<td nowrap="nowrap" class="label" colspan="2" style="text-align:left;">
							<label for="permanece_cliente_reparacion">
								<input id="permanece_cliente_reparacion" name="permanece_cliente_reparacion" type="checkbox" />
								Permance el Cliente a la Reparaci&oacute;n
							</label>
						</td>
						<td class="label" width="70%" style="text-align:left;">Observaciones:</td>
						<td class="label" width="70%" style="text-align:left;">N&uacute;mero de Recepci&oacute;n</td>
						<td class="field" id="field_numero_recepcion">
							<input type="text" readonly name="numero_recepcion" id="numero_recepcion" onKeyPress="return inputInt(event);" />
						</td>
					</tr>
					<tr>
						<td class="label" colspan="2" style="text-align:left;">
							<label for="permanece_cliente_partes">
								<input id="permanece_cliente_partes" name="permanece_cliente_partes" type="checkbox" />
								El Cliente desea conservar partes
							</label>
						</td>
						<td class="field" rowspan="2" colspan="3">
							<div id="capa_celular_cliente" style="vertical-align:top;padding:0;text-align:right;">
							<textarea id="observaciones" name="observaciones" style="width:99%; height:50px; margin:0px; border:0px;"></textarea>
							</div>
						</td>
					</tr>
					<tr>
						<td class="label"><span class="textoRojoNegrita">*</span>Nivel de Combustible:</td>
						<td class="field" id="field_nivel_combustible">
							<select  name="nivel_combustible"  >
								<option value="">-</option>
								<option value="0">0</option>
								<option value="0.25">1/4</option>
								<option value="0.5">1/2</option>
								<option value="0.75">3/4</option>
								<option value="1">1</option>
							</select>
						</td>
					</tr>
					<tr>
					</tr>
				</tbody>					
				</table>
                
                <table class="insert_table">				
				<tbody>
	
            
                    <tr>
                    	<td class="label" width="10%">ServiExpress:</td>
						<td class="field" width="5%" id="field_serviexpress">
							<select  name="serviexpress"  >
								<option value="">-</option>
								<option value="0">SI</option>
								<option value="1">NO</option>
							</select>
						</td>
                        
                        <td class="label" width="10%">Inspeccionado En Puente:</td>
						<td class="field" width="5%" id="field_puente">
							<select  name="puente"  >
								<option value="">-</option>
								<option value="0">SI</option>
								<option value="1">NO</option>
							</select>
						</td>
                        <td class="label" width="10%"><span class="textoRojoNegrita">*</span>Tipo de Vale:</td>
                            <td class="field" width="10%" id="field_id_tipo_vale">
                            </td>
					</tr>
                    
                    <tr>
                    	<td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    	<td class="label" width="10%"><?php if(esPuerto()){?> <span class="textoRojoNegrita">*</span><?php } ?>Nro Llaves:</td>
						<td class="field" width="5%" id="field_nro_llaves">
							<input type="text" id="nro_llaves"  name="nro_llaves" />
						</td>
                    </tr>
                    
             
					<tr>
					</tr>
				</tbody>					
				</table>
				
				
				<table class="insert_table">
				<thead><tr><td class="caption" colspan="4">Datos de la Orden</td></tr></thead>
				<tbody>
					<tr>
						<td class="label" width="20%">
							Tipo de orden a Generar:
						</td>
						<td class="field" id="field_id_tipo_orden">
							<?php setInputSelect('id_tipo_orden',$tipos_orden,$generic,null,false); ?>
						</td>
						<td class="label" width="20%">
							Prioridad:
						</td>
						<td class="field">
							<?php setInputSelect('prioridad',$prioridades,2,null,false); ?>
						</td>
					</tr>
					<tr>
						<td class="label" width="20%">
							Fecha prometida (estimado):
						</td>
						<td colspan="3" class="field" id="field_fecha_prometida">
							<input type="text" name="fecha_prometida" id="fecha_prometida" readonly  />
							<img id="b_fecha_prometida" alt="Modificar_fecha_prometida (estimado)" src="<?php echo getUrl('img/iconos/select_date.png')?>" />
							<script type="text/javascript">
								Calendar.setup({
								inputField : "fecha_prometida", // id del campo de texto
								ifFormat : "%d-%m-%Y %I:%M %p", // formato de la fecha que se escriba en el campo de texto
								button : "fecha_prometida", // el id del bot�n que lanzar� el calendario
								showsTime: true,
								timeFormat: '12'
								});
								Calendar.setup({
								inputField : "fecha_prometida", // id del campo de texto
								ifFormat : "%d-%m-%Y %I:%M %p", // formato de la fecha que se escriba en el campo de texto
								button : "b_fecha_prometida", // el id del bot�n que lanzar� el calendario
								showsTime: true,
								timeFormat: '12'
								});
							</script>
							<span id="capa_datos_prometida"></span>
						</td>
						
					</tr>
				</tbody>					
				</table>
				
				<table class="insert_table">
				<thead><tr><td class="caption" >Inventario de Recepci&oacute;n</td></tr></thead>
				<tbody>
					<tr>
						<td class="label" colspan="2" style="text-align:left;background:#FFFFFF;">
						<iframe id="inventario" name="inventario" src="" style="width:100%;height:400px;border:0px;" scrolling="no"  frameborder="0"></iframe>	
						</td>
					</tr>
				</tbody>
				</table>
				
				
				<table style="width:100%;">
				<thead><tr><td class="caption_insertable" >Registro de Fallas</td></tr></thead>
				<tbody>
					<tr>
						<td>
						<table class="insert_table">
							<col style="" />
							<col style="width:5px;" />
							<thead>
								<tr>
									<td><span class="textoRojoNegrita">*</span>Descripci&oacute;n: </td>
									<td><button type="button"  onclick="fallas_add();"><img alt="agregar" border="0" src="<?php echo getUrl('img/iconos/plus.png'); ?>" /></button></td>
								</tr>
							</thead>
							<tbody id="tbody_fallas">
								<!--<tr>
									<td style="width:15px;">1</td>
									<td width="*">
										<input type="text" style="width:99%" />
									</td>
									<td style="text-align:center;width:10px;">								
										<button>
											<img border="0" alt="quitar" src="" />
										</button>
									</td>
								</tr>-->
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
				</table>
				
			
				
				
				<div>
					<button type="button" id="btnGuardar" name="btnGuardar" onClick="
                    byId('btnGuardar').disabled = true;
                    xajax_guardar_vale(xajax.getFormValues('form_cita'));" class="icon_button" ><img border="0" alt="Guardar" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Guardar</button>
					<button onClick="window.location='sa_listado_citas.php';" type="button" class="icon_button" ><img border="0" alt="cancelar" src="<?php echo getUrl('img/iconos/delete.png'); ?>" />Cancelar</button>
				</div>
		</div>
		</form>
	</div>

</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="cuadro_citas" style="visibility:hidden;">
	<div class="title" id="titulo_citas">
		Citas		
	</div>
	<div class="content">
		<table class="hidden_table">
			<tr>
				<td width="20%" >Fecha: <span id="fecha_cita"></span></td>
				<td width="20%" >
					<img src="../img/iconos/select_date.png" alt="cambiar fecha" title="Cambiar fecha" style="cursor:pointer;" id="fecha_cita_boton" onClick="cargar_cita_fecha(this);" border="0" />
				</td>
				<td width="*" align="right">
					<form class="ipaginator_form" onSubmit="return false;">
						<!--<select id="filtro_asesores" onchange="//cita_date.asesor=this.value;r_dialogo_citas();">
							<option value="null"> - </option>
							<?php
							//$c= new connection();
							?>
						</select>-->
						<select id="filtro_estados" onChange="cita_date.estado_cita=this.value;r_dialogo_citas();">
							<option value="null"> - </option>
							<option value="PENDIENTE">Pendiente</option>
							<option value="CONFIRMADA">Confirmada</option>
						</select>
					</form>
					<a id="filtro_programada" class="filter" href="#" onClick="cita_date.origen_cita='PROGRAMADA';r_dialogo_citas();" title="Filtrar programadas"><img border="0" src="../img/iconos/cita_programada.png" />Programada</a>
					<a id="filtro_programada" class="filter" href="#" onClick="cita_date.origen_cita='ENTRADA';r_dialogo_citas();" title="Filtrar entradas"><img border="0" src="../img/iconos/cita_entrada.png" />Entrada</a>
					
				</td>
			</tr>
			<tr>
				<td colspan="3">
				<div id="lista_citas" style="width:100%;" ></div>		
				<div style="text-align:center;">
					<div style="padding:1px;">
						<span id="paginador"></span>
						<span id="result_cita">&nbsp;Seleccione una cita para cargar</span>
					</div>					
				</div>
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center"><div id="filtros" style="padding:2px;"></div></td>
			</tr>
		</table>
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('cuadro_citas');" border="0" />
</div>
<div style="display:none;" id="cliente_dc"></div>	
</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
		//xajax_load_page('calendario');	
                xajax_tipoVale();
		<?php 
		if(isset($_GET['id_cita'])){
			$cita=intval($_GET['id_cita']);
			if($cita!=''){
				echo 'xajax_cargar_cita('.$cita.');';
			}
		}
		?>
	</script>
	</body>
</html>