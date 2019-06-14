<?php
/*
	$c= new connection();
	$c->open();	
	$rec= $c->sa_rev_info->doSelect($c,new criteria(sqlEQUAL,'titulo',-1));	
	$part= explode(':',$rec->rango);	
	$ac=$part[0];
	$i=$part[1];
	$r=$part[3];
	$t=$part[2];
	$res=$ac;
	//echo $res.'<br />';
	$arr[]=intval($res);
	for($i+=$r;$i<=$t;$i+=$r){
		$res=($i*$ac);
		//echo $res.'<br />';
		$arr[]=$res;
	}


*/
@session_start();
define('PAGE_PRIV','sa_revision_final');
require_once("../inc_sesion.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	
	include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
	function listar_citas($page,$maxrows,$order,$ordertype,$capa,$args=''){
		
		global $spanCI;
		global $spanRIF;
		
		$r= getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			$r->assign($capa,inner,'Acceso denegado');
			return $r;
		}
		//@session_start();
		$r=getResponse();

		//procesando argumentos:
		$argumentos=paginator::getExplodeArgs($args);
		/*if($argumentos['fecha_cita']=='null'){
			$argumentos['fecha_cita']=adodb_date(DEFINEDphp_DATE);
		}*/
		$c=new connection();
		$c->open();

		
		$fecha=$argumentos['fecha_cita'];
		//$r->alert($fecha);
		if($fecha!=''){
			//'<span class="'.$this->class_filter.'" onclick="'.$event.'" title="Eliminar filtro '.$kt.'">Filtrado por '.$kt.': '.$v.'</span>';
			$rank=$argumentos['fecha_rank'];
			$meses=array(1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Ocutubre','Noviembre','Diciembre');
			$diaar=explode('-',$fecha);
			if($rank==1){
				//todo el d�a
				$fechac=new criteria(sqlEQUAL,'fecha_factura',field::getTransformType($fecha,field::tDate));
				$removefilterfecha='<span class="fast_filter" onclick="cita_date.fecha=\'\';r_dialogo_citas();" title="Eliminar filtro Fecha Diario"><strong>Filtrado por D&iacute;a (Factura): '.$fecha.'</strong></span>';
			}elseif($rank==2){
				//$r->alert(utf_export(intval($diaar[1])));
				$fechac=new criteria(sqlAND,
					new criteria(sqlEQUAL,"DATE_FORMAT(fecha_factura,'%c')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%c')"),
					new criteria(sqlEQUAL,"DATE_FORMAT(fecha_factura,'%Y')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%Y')")
				);
				$removefilterfecha='<span class="fast_filter" onclick="cita_date.fecha=\'\';r_dialogo_citas();" title="Eliminar filtro Fecha Mensual"><strong>Filtrado por Mes (Factura): '.$meses[intval($diaar[1])].' del '.$diaar[2].'</strong></span>';
			}else{
				$fechac=new criteria(sqlEQUAL,"DATE_FORMAT(fecha_factura,'%Y')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%Y')");
				$removefilterfecha='<span class="fast_filter" onclick="cita_date.fecha=\'\';r_dialogo_citas();" title="Eliminar filtro Fecha Anual"><strong>Filtrado por A&ntilde;o (Factura): '.$diaar[2].'</strong></span>';
			}
			//$r->alert($fechac->__toString());
		}
		
		$sa_v_citas_fin=$c->sa_v_revision_periodica;		
		
		$q = new query($c);		
		$q->add($sa_v_citas_fin);
		
		$q->where(new criteria(sqlAND,array(
			//new criteria(sqlEQUAL,$sa_v_citas_fin->fecha_cita,field::getTransformType($argumentos['fecha_cita'],field::tDate)),
			new criteria(sqlEQUAL,$sa_v_citas_fin->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']),			
			//new criteria(sqlNOTEQUAL,$sa_v_citas_fin->estado_cita,"'CANCELADA'"),
			//new criteria(sqlNOTEQUAL,$sa_v_citas_fin->estado_cita,"'POSPUESTA'"),
			//new criteria(sqlNOTEQUAL,$sa_v_citas_fin->estado_cita,"'PENDIENTE'"),
			//new criteria(sqlNOTEQUAL,$sa_v_citas_fin->estado_cita,"'CONFIRMADA'"),
			new criteria(sqlOR,array(
			  new criteria(sqlEQUAL,$sa_v_citas_fin->estado_cita,"'RETRAZADA'"),
			  new criteria(sqlEQUAL,$sa_v_citas_fin->estado_cita,"'PROCESADA'")
			))
		)));
		if($fechac!=null){
			$q->where($fechac);
		}
		
		
		if($argumentos["numero_orden"]!="" && $argumentos["numero_orden"] != "null"){//texto conversion javascript a php		
			$q->where(new criteria(sqlAND, array( 
				new criteria(" LIKE ",$sa_v_citas_fin->numero_orden, "'".$argumentos["numero_orden"]."'")
			)));
			$page = 0;
		}
	
			
		if($argumentos['filtro_cliente']!='null'){
			$q->where(new criteria(sqlEQUAL,$sa_v_citas_fin->id_cliente_contacto,"'".$argumentos['filtro_cliente']."'"));
			$rq=$c->cj_cc_cliente->doSelect($c,new criteria(sqlEQUAL,$c->cj_cc_cliente->id,$argumentos['filtro_cliente']));
			$cliente=$rq->lci.'-'.$rq->ci.', '.$rq->apellido.' '.$rq->nombre;
			$filtros.='<span style="color:red;" class="filter" title="Eliminar filtro" onclick="cita_date.filtro_cliente=null;r_dialogo_citas();">Filtrado por Cliente: '.$cliente.'</span>';
		}
		/*if($argumentos['origen_cita']!='null'){
			$q->where(new criteria(sqlEQUAL,$sa_v_citas_fin->origen_cita,"'".$argumentos['origen_cita']."'"));
			$filtros.='<span class="filter" title="Eliminar filtro" onclick="cita_date.origen_cita=null;r_dialogo_citas();">Filtrado por Origen: '.$argumentos['origen_cita'].'</span>';
		}
		if($argumentos['estado_cita']!='null'){
		
			$q->where(new criteria(sqlEQUAL,$sa_v_citas_fin->estado_cita,"'".$argumentos['estado_cita']."'"));
			$filtros.='<span class="filter" title="Eliminar filtro" onclick="cita_date.estado_cita=null;r_dialogo_citas();obj(\'filtro_estados\').value=\'null\';">Filtrado por Estado: '.$argumentos['estado_cita'].'</span>';
		}*/
		//$q->where(new criteria(' IS NOT ',$sa_v_citas_fin->tiempo_llegada_cliente,sqlNULL));
		$paginator = new paginator('xajax_listar_citas',$capa,$q,$maxrows);
		
		//var_dump($q->__toString());
		$rec=$paginator->run($page,$order,$ordertype,$args);
		//$r->alert($q->getSelect());
		$origen=array('PROGRAMADA'=>getUrl('img/iconos/cita_programada.png'),'ENTRADA'=>getUrl('img/iconos/cita_entrada.png'));
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han encontrado registros</div>';
			}else{
			//<td style="text-align:center;">'.$paginator->get($sa_v_citas_fin->numero_entrada,'N Llegada').'</td>
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				<td>&nbsp;</td>
				<td style="text-align:center;">'.$paginator->get($sa_v_citas_fin->numero_orden,'Orden').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->placa,'Placa').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->cedula_cliente,$spanCI."/".$spanRIF).'</td>
				<td>'.$paginator->get($sa_v_citas_fin->nombre,'Nombre').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->apellido,'Apellido').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->asesor,'Asesor').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->fecha_factura,'Fecha').'</td>
				
				
				</tr></thead><tbody>';
				$class='';
				$color_estado=array(
					0=>'yellow',
					'#00FF00',
					'#FF0000'
				);
				$texto_estado=array(
					0=>'Pendiente',
					'A tiempo',
					'Retardada'
				);
				$resp=$c->sa_respuestas_cliente->doSelect($c, new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']))->getAssoc('n_respuesta','respuesta');
				
				foreach($rec as $v){
					//evaluando
					/*if($rec->fecha_llamada_fin==null){
						$hoy = adodb_mktime(0,0,0);
						$fecha= str_date($rec->fecha_factura);
						$diferencia= ($hoy-$fecha)/60/60/24;
						if($diferencia>3){
							$estado_llamada=2;
						}else{
							$estado_llamada=0;
						}
						$fecha_llamada='<button onclick="registrar_llamada('.$rec->id_cita.','.$estado_llamada.');"><img alt="Realizar Llamada" src="'.getUrl('img/iconos/call.png').'" /> Registrar</button>';
						$respuesta=' - ';
					}else{
						$fecha_llamada= adodb_date(DEFINEDphp_DATETIME12,str_tiempo($rec->fecha_llamada_fin));
						$estado_llamada=$rec->estado_llamada;
						$respuesta=$resp[$rec->n_respuesta];
					}*/
				//<td style="text-align:center;color:#FF0000;font-weight:bold;">'.$rec->numero_entrada.'</td>
					$html.='<tr class="'.$class.'" >
					<td><button onclick="xajax_revisar('.$rec->id_orden.');"><img alt="revisar" src="'.getUrl('img/iconos/select.png').'" /></button><button onclick="xajax_revisar('.$rec->id_orden.',true);"><img alt="Imprimir" src="'.getUrl('img/iconos/print.png').'" /></button></td>
					<td style="text-align:center;" idordenoculta="'.$rec->id_orden.'">'.$rec->numero_orden.'</td>
					<td>'.$rec->placa.'</td>
					<td>'.$rec->cedula_cliente.'</td>
					<td>'.$rec->nombre.'</td>
					<td>'.$rec->apellido.'</td>
					<td>'.$rec->asesor.'</td>
					<td>'.parseDateToSql($rec->fecha_factura).'</td>
					
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
		$r->assign('filtros',inner,ifnull($filtros).ifnull($removefilterfecha));
		
		$r->script("//setDivWindow('cuadro_citas','titulo_citas',true);
		cita_date.page=".$paginator->page.";
		cita_date.maxrows=".$paginator->maxrows.";
		cita_date.order='".$paginator->order."';
		cita_date.ordertype='".$paginator->ordertype."';
		cita_date.origen_cita='".$argumentos['origen_cita']."';
		cita_date.fecha='".$argumentos['fecha_cita']."';
		cita_date.fecha_rank='".$argumentos['fecha_rank']."';
		cita_date.estado_cita='".$argumentos['estado_cita']."';
		cita_date.filtro_cliente='".$argumentos['filtro_cliente']."';
		cita_date.numero_orden='".$argumentos['numero_orden']."';
		");
		
		$c->close();
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
				//return $r;
			}
			$r->script('cita_date.filtro_cliente='.$rec->id.';r_dialogo_citas();');
		}
		$c->close();
		return $r;
	}
	

  function cargar_datos_cliente($id_cita){
    $r= getResponse();
    $c = new connection();
    $c->open();
	$rec= $c->sa_v_datos_cita->doSelect($c, new criteria(sqlEQUAL,'id_cita',$id_cita));
	if($rec){
	  $r->assign('capa_cliente',inner,$rec->cedula_cliente.', '.$rec->apellido.' '.$rec->nombre);
	  $r->assign('capa_telefonos',inner,$rec->telf.' / '.$rec->otrotelf);
	  $r->script("setWindow('cuadro_llamadas','titulo_cuadro_llamadas',true);");
	}
    return $r;
  }

  function guardar($form){
    $r= getResponse();
	$c = new connection();
	$c->open();
	$c->begin();
	//$r->alert(utf_export($form));	
	$table = new table('sa_revision_final');
	//$r->alert($form['id_revision_final']);
	$table->insert('id_revision_final',$form['id_revision_final']);
	$table->insert('tiempo_revision','NOW()',field::tFunction);
	$table->insert('correlativo',$form['correlativo'],field::tString);
	$table->insert('observaciones',$form['observaciones'],field::tString);
	$table->insert('id_empleado_jefe_taller',$form['id_empleado_jefe_taller']);
	$table->insert('id_empleado_mecanico',$form['id_empleado_mecanico']);
	$table->insert('rango',$form['rango'],field::tString,false);
	$table->insert('id_orden',$form['id_orden']);
	
	if($form['id_revision_final']==''){
		$result=$table->doInsert($c,$table->id_revision_final);
	//$r->alert($table->getInsert($c,$table->id_revision_final));
	}else{
		$result=$table->doUpdate($c,$table->id_revision_final);
	//$r->alert($table->getUpdate($c,$table->id_revision_final));
	}
	
	//$r->alert($table->getUpdate($c,$table->id_cita));
	
	$r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');
	if($result===true){
		//obtiene el ultimo id
		if($form['id_revision_final']!=''){
		  $vid_revision_final=$form['id_revision_final'];
		}else{
		  $vid_revision_final=$c->soLastInsertId();
		}
		$dt= new table('sa_det_revision_final');
		$id_det_revision_final= $dt->add(new field('id_det_revision_final','',field::tInt));
		$id_revision_final=  	$dt->add(new field('id_revision_final','',field::tInt));
		$valor_revision=  		$dt->add(new field('valor_revision','',field::tInt));
		$id_rev_info=			$dt->add(new field('id_rev_info','',field::tInt));
		$id_revision_final->setValue($vid_revision_final);
		$errors=false;
		foreach($form['id_det_revision_final'] as $_id_rev_info => $_id_det_revision_final){
			$id_rev_info->setValue($_id_rev_info);
			$id_det_revision_final->setValue($_id_det_revision_final);
			$valor_revision->setValue($form['valor_revision'][$_id_rev_info]);
			if($_id_det_revision_final==''){
				$s=($dt->getInsert($c,$dt->id_det_revision_final));
				$rdet=$dt->doInsert($c,$dt->id_det_revision_final);
			}else{
				$rdet=$dt->doUpdate($c,$dt->id_det_revision_final);
				$s=($dt->getUpdate($c,$dt->id_det_revision_final));
			}
			if($rdet!==true){
				$errors=true;
				$c->rollback();
				$r->alert('Error to: '.$_id_det_revision_final.' - '.$s);
				break;
			}
		}
		if(!$errors){
			$c->commit();
			$r->script('
			_alert("Revisi&oacute;n realizada con &Eacute;xito");
			close_window("cuadro_inspeccion");
			//r_dialogo_citas();
			');
		}
	}else{
		$c->rollback();
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
			//$r->alert($ex->getObject()->getName().' '.$ex->getMessage());
		}
		$r->alert('Faltan datos');
	}
	return $r;
  }

  function revisar($id_orden,$print=false){
	$r= getResponse();
	$c = new connection();
	$c->open();
	$rec= $c->sa_v_revision_periodica->doSelect($c, new criteria(sqlEQUAL,'id_orden',$id_orden));
	if($rec){
	  $r->assign('capa_cliente',inner,$rec->cedula_cliente.', '.$rec->apellido.' '.$rec->nombre);
	  $r->assign('id_orden','value',$id_orden);
	  $r->assign('capa_orden',inner,$id_orden);
	  $km=$rec->kilometraje;
	  $r->assign('capa_km',inner,$km);
	  $r->assign('capa_placa',inner,'Placa: '.$rec->placa.' Chasis: '.$rec->chasis);
	  $r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');
	}
	//revisando si existe revision periodica anterior:
	$recrev=$c->sa_revision_final->doSelect($c, new criteria(sqlEQUAL,'id_orden',$id_orden));
	if($recrev->getNumRows()!=0){
		$id_revision_final=$recrev->id_revision_final;
		if($print){
			$r->script('setPopup("sa_revision_final_print.php?view=print&id_revision_final='.$id_revision_final.'","pop",{dialog:null,center:"both",height:600,width:900});');			
			return $r;
		}
	}else{
		if($print){
			$r->script('_alert("No se ha registrado revisi&oacute;n");');
			return $r;
		}
		$id_revision_final='';
		//$r->alert("no");
	}
	  $r->script("setDivWindow('cuadro_inspeccion','titulo_cuadro_inspeccion',true);");
	
	$r->assign('id_revision_final','value',ifnull($recrev->id_revision_final));
	$r->assign('id_empleado_jefe_taller','value',$recrev->id_empleado_jefe_taller);
	$r->assign('id_empleado_mecanico','value',$recrev->id_empleado_mecanico);
	$r->assign('observaciones','value',ifnull($recrev->observaciones));
	$r->assign('correlativo','value',ifnull($recrev->correlativo));
	$rango_def=$recrev->rango;
	$rec2= $c->sa_rev_info->doSelect($c,new criteria(sqlEQUAL,'titulo',-1));	
	$part= explode(':',$rec2->rango);	
	$ac=$part[0];
	$i=$part[1];
	$dr=$part[3];
	$t=$part[2];
	$res=$ac;
	$arr[$res]=_formato(intval($res),0);
	for($i+=$dr;$i<=$t;$i+=$dr){
		$res=($i*$ac);
		if($id_revision_final=='' && $km>$res){
			//$r->alert(utf_export($rango_def).' - '.$km.' / '.$res);
			$rango_def=$res;
		}
		$arr[$res]=_formato($res,0);
	}
	$t_rangos=inputSelect('rango',$arr,$rango_def,array('onchange'=>'xajax_fillRev(obj(\'id_revision_final\').value,null,this.value);'));
	//$r->alert(utf_export($arr));
	$r->assign('capa_rango',inner,$t_rangos);
	$r->loadCommands(fillRev($id_revision_final,$c,$rango_def));
	return $r;
  }
  
  function fillRev($id_revision_final,$c=null,$crit=0,$layer='capa_revisiones'){
    $r= getResponse();
	//$r->alert($id_revision_final.' '.$crit);
	if($c==null){
		$c= new connection();
		$c->open();
	}
	//$id_revision_final=1;
	$t = $c->sa_rev_info;
	$q = $t->doQuery($c);
	$cond=new criteria(sqlNOTEQUAL,'titulo','-1');
	if($id_revision_final==''){
		$id_revision_final=sqlNULL;
		//$t = $c->sa_rev_info; 
	}else{
		//$t = $c->sa_rev_info;
		//$cond=new criteria(sqlNOTEQUAL,'titulo','-1');
		
		//agregando las subconsultas
		$sc1= new table('sa_det_revision_final','sa_det_revision_final1');
		$sc1->insert('id_det_revision_final','',field::tInt);
		$q1=new query($c,"id_det_revision_final");
		$q1->add($sc1);
		$q1->where(new criteria(sqlEQUAL,$c->sa_rev_info->id_rev_info,'sa_det_revision_final1.id_rev_info'));
		$q1->where(new criteria(sqlEQUAL,$id_revision_final,'sa_det_revision_final1.id_revision_final'));
		$q1->setLimit(1);
		$t->add($q1);		
		
		$sc2= new table('sa_det_revision_final','sa_det_revision_final2');
		$sc2->insert('valor_revision','',field::tInt);
		$q2=new query($c,"valor_revision");
		$q2->add($sc2);
		$q2->where(new criteria(sqlEQUAL,$c->sa_rev_info->id_rev_info,'sa_det_revision_final2.id_rev_info'));
		$q2->where(new criteria(sqlEQUAL,$id_revision_final,'sa_det_revision_final2.id_revision_final'));
		$q2->setLimit(1);
		$t->add($q2);
		/*$cond=new criteria(sqlOR,
			new criteria(sqlEQUAL,'id_revision_final',$id_revision_final),
			new criteria(sqlIS,'id_revision_final',sqlNULL)
		);*/
	}
	
	if($crit!=0){
      $q->where(new criteria(sqlEQUAL,"mod(".$crit.",rango)" ,0));
	}
	$q->where(new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']));	
	$q->where($cond);
	//$r->alert($q->getSelect());
	$rec= $q->doSelect();
	//contruyendo la tabla
	
	$html='<table class="insert_table" style="width:700px;"><thead><tr><td class="caption" style="width:5%;">NA</td><td class="caption" style="width:5%;">X</td><td class="caption" style="width:5%;">A</td><td class="caption" style="width:5%;">R</td><td class="caption" style="width:80%;">Puntos</td></tr></thead><tbody style="text-align:center;">';
	
	foreach($rec as $v){
		$html.='<tr>';
		$val= ifnull($v->valor_revision,0);
		//$r->alert($v->valor_revision.' '.$val);
		//$r->alert($val);
		$att[$val]=array('checked'=>'checked');
		$nameinput='valor_revision['.$v->id_rev_info.']';
		$nl=radioInputTag($nameinput,0,NULL,$att[0]);
		$vx=radioInputTag($nameinput,1,NULL,$att[1]);
		$va=radioInputTag($nameinput,2,NULL,$att[2]);
		$vr=radioInputTag($nameinput,3,NULL,$att[3]);
		unset($att[$val]);
		$id_det='<input type="hidden" name="id_det_revision_final['.$v->id_rev_info.']" value="'.$v->id_det_revision_final.'" />';
		$html.='<td class="field">'.$id_det.$nl.'</td><td class="field">'.$vx.'</td><td class="field">'.$va.'</td><td class="field">'.$vr.'</td>';		
		
		$html.='<td class="field" style="text-align:left;">'.$v->titulo.'</td>';
		$html.='</tr>';
	}
	
	$html.='</tbody></table>';
	//$r->alert($html);
	$r->assign($layer,inner,$html);
	return $r;
  }

	function imprimir($id_orden){
		$r= getResponse();
		$c= new connection();
		$c->open();
		//revisando si existe revision periodica anterior:
		$recrev=$c->sa_revision_final->doSelect($c, new criteria(sqlEQUAL,'id_orden',$id_orden));
		if($recrev->getNumRows()!=0){
			$id_revision_final=$recrev->id_revision_final;
		}else{
			$r->script("_alert('No se ha registrado la revisi&oacute;n de la orden');");
		}
		return $r;
	}
  
	//xajaxRegister('registrar');

	xajaxRegister('listar_citas');
	xajaxRegister('fillRev');
	//xajaxRegister('cargar_cita');
	xajaxRegister('revisar');
	xajaxRegister('imprimir');
	xajaxRegister('cargar_cliente_pago');
//	xajaxRegister('cargar_datos_cliente');
	xajaxRegister('guardar');
	
	xajaxProcess();
	
	$c= new connection();
	$c->open();
	//$resp=$c->sa_respuestas_cliente->doQuery($c, new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']))->OrderBy('n_respuesta')->doSelect()->getAssoc('n_respuesta','respuesta');
//	$input_nivel=inputSelect('n_respuesta',$resp,0,null,false);
	/*$tipos_orden=$c->sa_tipo_orden->doSelect($c)->getAssoc('id_tipo_orden','descripcion_tipo_orden');
	$prioridades=array(
		1=>'ALTA',
		2=>'MEDIA',
		3=>'BAJA'
	);*/
	
	$cempresa= new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']);
	
	$mecanicos=$c->pg_v_empleado->doQuery($c,new criteria(sqlEQUAL,'clave_filtro',501))->where($cempresa)->doSelect()->getAssoc('id_empleado','cedula_nombre_empleado');
	$jefe_taller=$c->pg_v_empleado->doQuery($c,new criteria(sqlEQUAL,'clave_filtro',6))->where($cempresa)->doSelect()->getAssoc('id_empleado','cedula_nombre_empleado');
	$mecanicos_select=inputSelect('id_empleado_mecanico',$mecanicos);
	$jefe_mecanicos_select=inputSelect('id_empleado_jefe_taller',$jefe_taller);
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
		                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Revisión</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
		<script>
			detectEditWindows({cuadro_inspeccion:'guardar'});
			var cita_date = {
				fecha: '<?php $idate=adodb_date(DEFINEDphp_DATE,adodb_mktime(0,0,0));echo $idate; ?>',
				fecha_rank:2,
				date:new Date(),
				page:0,
				maxrows:10,
				order:'',//'sa_v_citas_fin.numero_entrada',
				ordertype:null,
				estado_cita:null,
				origen_cita:null,
				filtro_cliente:null,
				numero_orden:null
			}
			function r_dialogo_citas(){
				xajax_listar_citas(cita_date.page,cita_date.maxrows,cita_date.order,cita_date.ordertype,'lista_citas','fecha_cita='+cita_date.fecha+',origen_cita='+cita_date.origen_cita+',estado_cita='+cita_date.estado_cita+',filtro_cliente='+cita_date.filtro_cliente+',fecha_rank='+cita_date.fecha_rank+',numero_orden='+cita_date.numero_orden);
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
						'callback=xajax_cargar_cliente_pago');
			}
			
			function cargar_cita(id_cita){
				window.location="sa_vale_recepcion.php?id_cita="+id_cita;
			}
			
			function cargar_fecha(datee){
				cita_date.fecha=datee;
				obj('filtrofecha').innerHTML=datee;
				r_dialogo_citas();
			}
			function cambiar_fecharank(val){
				//verifica si la fecha se establece:
				cita_date.fecha_rank=val.value;
				
				if(cita_date.fecha != ''){
					r_dialogo_citas();
				}else{
					fecha_filtro.showDateDialog(val);
				}
			}
			
			var fecha_filtro = new dateDialog(cargar_fecha);
			
			function registrar_llamada(id_cita,estado_guardar){
				xajax_cargar_datos_cliente(id_cita);
				obj('id_cita').value=id_cita;
				obj('estado_llamada').value=estado_guardar;
			}
			
			function buscar_numero_orden(nro_orden){				
				cita_date.numero_orden = nro_orden; 
				r_dialogo_citas();
			}
		</script>
		<style type="text/css">
			
			button img{
				vertical-align:middle;
			}
			
		</style>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Control de Taller</span><br />
			<span class="subtitulo_pagina" >(Revisi&oacute;n)</span></td>
			
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		
	</div>
	
	<div class="area" >
		
		<table class="hidden_table" id="padre_cita">
			<tr class="noprint">
				<td width="30%">
					<fieldset style="display:inline;">
					<legend>Filtro por Fecha factura</legend>
					<button type="button"  value="reset" title="Fecha" onClick="fecha_filtro.showDateDialog(this);" ><img border="0" src="<?php echo getUrl('img/iconos/select_date.png') ?>" /> <span id="filtrofecha">Filtrar Fecha</span></button>
					<label class="opfecha"><input type="radio" name="fecha_rank" value="1" onClick="cambiar_fecharank(this);" checked="checked" />Dia</label>
					<label class="opfecha"><input type="radio" name="fecha_rank" onClick="cambiar_fecharank(this);" value="2" checked="checked" />Mes</label>
					<label class="opfecha"><input type="radio" name="fecha_rank" onClick="cambiar_fecharank(this);" value="3" />A&ntilde;o</label>
					</fieldset>
				</td>
				<td colspan="2">
                    <fieldset style="display:inline;">
					<legend>Nro Orden</legend>
                    <input type="text" name="txtNroOrden" id="txtNroOrden" onKeyUp="buscar_numero_orden(this.value);" />
                    </fieldset>
                    
                    <button type="button" title="Clientes" onClick="buscar_cliente_pago();"><img border="0" src="<?php echo geturl('img/iconos/find.png'); ?>" />&nbsp;Clientes</button>
				</td>
			</tr>

			<tr>
				<td colspan="3">
				<hr />
				<div id="lista_citas" style="width:100%;" ></div>
				<hr />
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

</div>
<!--MARCO PRINCIPAL-->

<div class="window" id="cuadro_inspeccion" style="visibility:hidden; min-width:700px;">
	<div class="title" id="titulo_cuadro_inspeccion">
		Inspecciones		
	</div>
	<div class="content">
		<form id="f_inspeccion" name="f_inspeccion" onSubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_orden" name="id_orden" />
			<input type="hidden" id="id_revision_final" name="id_revision_final" />
			
			<table class="insert_table" style="width:100%;">
				<tbody>
					<tr>
						<td class="label">
							Cliente
						</td>
						<td class="field" id="capa_cliente" style="font-weight:bold;">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Orden N:
						</td>
						<td class="field" id="capa_orden" style="font-weight:bold;">
							
						</td>
					</tr>
					<tr>
						<td class="label">Correlativo</td>
						<td class="field" id="field_correlativo">
							<?php setInputArea('correlativo','',NULL,500); ?>
						</td>
					</tr>
					<tr>
						<td class="label">Observaciones</td>
						<td class="field" id="field_observaciones">
							<?php setInputArea('observaciones','',NULL,500); ?>
						</td>
					</tr>
					<tr>
						<td class="label">Jefe taller:</td>
						<td class="field" id="field_id_empleado_jefe_taller"><?php echo $jefe_mecanicos_select; ?></td>
					</tr>
					<tr>
						<td class="label">Mec&aacute;nico:</td>
						<td class="field" id="field_id_empleado_mecanico"><?php echo $mecanicos_select; ?></td>
					</tr>
					
					<tr>
						<td class="label">Auto:</td>
						<td class="field"><span id="capa_placa"></span></td>
					</tr>
					
					<tr>
						<td class="label">
							Filtrar Seg&uacute;n:
						</td>
						<td class="field" >
							<span id="capa_rango"></span> <span><?php echo $spanKilometraje; ?> del Veh&iacute;culo:</span> <span id="capa_km"></span>
						</td>
					</tr>
					<tr>
						<td class="label"  style="text-align:left;">Revisiones:</td>
						<td class="field">
						NA=No Aplica / X= Bueno / A=Requiere Ajuste / R=Requiere Reemplazo
						</td>
					</tr>
				</tbody>
			</table>
			<div style="width:100%;max-height:300px;overflow-y:auto;overflow-x:hidden;">
				<div id="capa_revisiones" style="magin:auto;margin-right:20px;" ></div>
			</div>
			<table class="insert_table" style="width:100%;">
				<tfoot>
					<tr>
						<td colspan="2" align="right">
							<button type="submit" id="guardar" onClick="xajax_guardar(xajax.getFormValues('f_inspeccion'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Guardar</button>
						</td>
					</tr>
				</tfoot>
			</table>
		</form>
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('cuadro_inspeccion');" border="0" />
</div>
<div id="cliente_dc"></div>

</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
		//xajax_load_page('calendario');
		r_dialogo_citas();
	</script>
	</body>
</html>