<?php
@session_start();

//implementando xajax;
	require_once("../control/main_control.inc.php");
	require_once("../control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	include("../control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
	function load_page($page,$maxrows,$order,$ordertype,$capa,$args=''){
		$r= getResponse();
		//$r->alert(utf8_encode($args));
		$c = new connection();
		$c->open();
		
		//procesando argumentos:
		$argumentos=paginator::getExplodeArgs($args);
		
		$sa_tempario = $c->sa_v_tempario;
		
		
		
		$query = new query($c);
		$query->add($sa_tempario);
		
		if($argumentos['busca']!=''){
			$query->where(
				new criteria(sqlOR, array(
					new criteria(' like ',$sa_tempario->descripcion_tempario,"'%".$argumentos['busca']."%'")/*,
					new criteria(' like ',$sa_tempario->chasis,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$sa_tempario->color,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$c->pg_empresa->nombre_empresa,"'%".$argumentos['busca']."%'")*/
					)
				)
			);
			
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
	
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($sa_tempario->codigo_tempario,'C&oacute;digo').'</td>
				<td>'.$paginador->get($sa_tempario->descripcion_tempario,'Descripci&oacute;n').'</td>
				<td>'.$paginador->get($sa_tempario->descripcion_modo,'Modo').'</td>
				<td>'.$paginador->get($sa_tempario->operador,'Operador').'</td>
				<td>'.$paginador->get($sa_tempario->precio,'Precio').'</td>
				<td>'.$paginador->get($sa_tempario->costo,'Costo').'</td>
				<td>'.$paginador->get($sa_tempario->nombre_empresa,'Empresa').'</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				
				</tr></thead><tbody>';
				$class='';
				foreach($rec as $v){
					if ($rec->parcial == '1')
						$parcial = 'Si';
					else
						$parcial = 'No';
					$html.='<tr class="'.$class.'">
					
					<td align="center">'.$rec->codigo_tempario.'</td>
					<td align="center">'.$rec->descripcion_tempario.'</td>
					<td align="center">'.$rec->descripcion_modo.'</td>
					<td align="center">'.$rec->operador.'</td>
					<td align="center">'._formato($rec->precio).'</td>
					<td align="center">'._formato($rec->costo).'</td>
					<td align="center">'.$rec->nombre_empresa.'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_tempario.',\'view\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_tempario.',\'edit\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/delete.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar('.$rec->id_tempario.',\'delete\');""></td>
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
		//$r->assign('fecha',inner,$argumentos['fecha_cita']);
		$r->assign('paginador',inner,'<hr>Mostrando '.$paginador->count.' resultados de un total de '.$paginador->totalrows.' '.$paginador->getPages().'&nbsp;'.$filtro);
		/*if (ifnull($argumentos['fecha']) == 'null'){
			$fec = "";
			}
		else
			$fec = ifnull($argumentos['fecha']);*/
		$r->assign('campoFecha','value',$fec);
		$r->script("
		datos.page=".$page.";
		datos.maxrows=".$maxrows.";
		datos.order='".$order."';
		datos.ordertype='".$ordertype."';
		datos.busca='".$argumentos['busca']."';
		");
		//$r->alert($paginator->page);
		//$r->script('alert(datos.page);');
		$c->close();
		return $r;
	}
	
	/*function lista_marcas($c){
		return $c->an_marca->doSelect($c)->getAssoc($c->an_marca->id_marca,$c->an_marca->nom_marca);
	}
	function lista_modelo($c,$marca){
		return $c->an_modelo->doSelect($c,new criteria(sqlEQUAL,$c->an_modelo->id_marca,$marca))->getAssoc($c->an_modelo->id_modelo,$c->an_modelo->nom_modelo);
	}
	function lista_version($c,$modelo){
		return $c->an_version->doSelect($c,new criteria(sqlEQUAL,$c->an_version->id_modelo,$modelo))->getAssoc($c->an_version->id_version,$c->an_version->nom_version);
	}
	function lista_unidad_basica($c,$version){
		return $c->an_uni_bas->doSelect($c,new criteria(sqlEQUAL,$c->an_uni_bas->ver_uni_bas,$version))->getAssoc($c->an_uni_bas->id_uni_bas,$c->an_uni_bas->nom_uni_bas);
	}
	
	function cargar_listas(){
		$r=getResponse();
		$r->loadCommands(cargar_lista_marca());
		$r->loadCommands(cargar_lista_modelo());
		$r->loadCommands(cargar_lista_version());
		$r->loadCommands(cargar_lista_unidad_basica());
		return $r;
	}
	
	function cargar_lista_marca($default=null,$c=null){
		$r=getResponse();
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_marcas($c);
		$select_marca=inputSelect('id_marca',$lassoc,$default,array('onchange'=>'xajax_cargar_lista_modelo(this.value);xajax_cargar_lista_version();xajax_cargar_lista_unidad_basica();'));
		$r->assign('field_id_marca',inner,$select_marca);
		//$c->close();
		return $r;
	}
	
	function cargar_lista_modelo($marca='',$default=null,$c=null){
		$r=getResponse();
		if($marca==''){
			$r->assign('field_id_modelo',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_modelo($c,$marca);
		$select=inputSelect('id_modelo',$lassoc,$default,array('onchange'=>'xajax_cargar_lista_version(this.value);xajax_cargar_lista_unidad_basica();'));
		$r->assign('field_id_modelo',inner,$select);
		//$c->close();
		return $r;
	}
	
	function cargar_lista_version($modelo='',$default=null,$c=null){
		$r=getResponse();
		
		if($modelo==''){
			$r->assign('field_id_version',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_version($c,$modelo);
		$select=inputSelect('id_version',$lassoc,$default,array('onchange'=>'xajax_cargar_lista_unidad_basica(this.value);'));
		$r->assign('field_id_version',inner,$select);
		//$c->close();
		return $r;
	}
	
	function cargar_lista_unidad_basica($version='',$default=null,$c=null){
		$r=getResponse();
		if($version==''){
			$r->assign('field_id_unidad_basica',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_unidad_basica($c,$version);
		$select=inputSelect('id_unidad_basica',$lassoc,$default);
		$r->assign('field_id_unidad_basica',inner,$select);
		//$c->close();
		return $r;
	}*/
	
	function cargar($id, $mode='view'){
		$r=getResponse();
		$view=array('add'=>'','view'=>'true');
		if($mode=='view'){
			$r->script('
			$("#edit_window #subtitle").html("Ver");
			');
		}else{
			$r->script('
			$("#edit_window #subtitle").html("Editar");
			');
		}
		$c=new connection();
		$c->open();
		
		
		$q=new query($c);
		$q->add($c->sa_v_tempario);
		
		$q->where(new criteria(sqlEQUAL,$c->sa_v_tempario->id_tempario,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
		//$r->alert($q->getSelect());return $r;
			//cargnado la lista de mepresas
			//$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			if($rec){
				$r->assign('id_tempario','value',$rec->id_tempario);
				$r->assign('id_empresa','value',$rec->id_empresa);
				$r->assign('codigo_tempario','value',$rec->codigo_tempario);
				$r->assign('descripcion_tempario','value',$rec->descripcion_tempario);
				$r->assign('id_modo','value',$rec->id_modo);
				$r->assign('operador','value',$rec->operador);
				$r->assign('precio','value',_formato($rec->precio));
				$r->assign('costo','value',_formato($rec->costo));
				
				//cargando los detalles:
				$sa_v_unidad_basica=$c->sa_v_Unidad_basica;
				$sa_tempario_det= new table('sa_tempario_det','',$c);
				$join= $sa_tempario_det->join($sa_v_unidad_basica,$sa_tempario_det->id_unidad_basica,$sa_v_unidad_basica->id_unidad_basica);
				$qdet=new query($c);
				$qdet->add($join);
				$qdet->where(new criteria(sqlEQUAL,$sa_tempario_det->id_tempario,$rec->id_tempario));
				$recdet=$qdet->doSelect();
				if($recdet){
					foreach($recdet as $det){
						$scriptdet.="
unidad_add({
	id_unidad_basica:".$det->id_unidad_basica.",
	unidad:'".$det->nombre_unidad_basica.' ('.$det->nom_marca.' '.$det->nom_modelo.")',
	ut:'"._formato($det->ut,0)."',
	action:'add',
	id_tempario_det:".$det->id_tempario_det."
});";
					}
				}
				
				
				//$select_empresa=inputSelect('id_empresa',$empresas,$rec->id_empresa);
				//$r->assign('field_id_empresa',inner,$select_empresa);
			}
			$r->script('
				agregar(false);
			');
			
			$r->script($scriptdet);
			$r->script('
				$("#edit_window input").attr("readonly","'.$view[$mode].'");
				$("#edit_window select").attr("disabled","'.$view[$mode].'");
				$("#edit_window button").attr("disabled","'.$view[$mode].'");
				');
			
		}else{
			$c->begin();
			$rec=$c->sa_tempario->doDelete($c,new criteria(sqlEQUAL,$c->sa_tempario->id_tempario,$id));
			if($rec===true){
				$c->commit();
				$r->script('_alert("Registro eliminado con &eacute;xito");cargar();');
			}else{
				$r->script('_alert("No se puede eliminar el registro, elimine primero las unidades asociadas o es posible que el mismo ya est&aacute; siendo utilizado");');
			}
		}
		$c->close();
		return $r;
	}
	
	function guardar($form){
		$r=getResponse();
		
		
		//removiendo las clases que indican error:
		$r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');
		$c= new connection();
		$c->open();
		$sa_tempario = new table("sa_tempario");
		
		$sa_tempario->add(new field('id_tempario','',field::tInt,$form['id_tempario']));
		$sa_tempario->add(new field('id_empresa','',field::tInt,$form['id_empresa']));
		$sa_tempario->add(new field('codigo_tempario','',field::tString,$form['codigo_tempario']));
		$sa_tempario->add(new field('descripcion_tempario','',field::tString,$form['descripcion_tempario']));
		$sa_tempario->add(new field('id_modo','',field::tInt,$form['id_modo']));
		$sa_tempario->add(new field('operador','',field::tString,$form['operador']));
		$sa_tempario->add(new field('costo','',field::tFloat,$form['costo']));
		$sa_tempario->add(new field('precio','',field::tFloat,$form['precio']));
		
		$c->begin();
		$id_tempario=$form['id_tempario'];
		if($form['id_tempario']==''){
			$result=$sa_tempario->doInsert($c,$sa_tempario->id_tempario);
			$id_tempario=$c->soLastInsertId();
		}else{
			$result=$sa_tempario->doUpdate($c,$sa_tempario->id_tempario);
		}
		if($result===true){
			//recorriendo los detalles:
			
			if(isset($form['id_tempario_det'])){
				foreach($form['id_tempario_det'] as $k => $v){
					$sql='';
					//verificando por accion
					if($form['action'][$k]=='add'){
						if($form['ut'][$k]==''){
							$r->script('$("#row'.($k+1).'").addClass("inputNOTNULL");');
							$error=true;
							continue;
						}
						if($form['id_tempario_det'][$k]==''){
							$sql=sprintf("INSERT INTO sa_tempario_det(id_tempario_det,id_tempario,id_unidad_basica,ut) VALUES (NULL , %s, %s, %s);",
							$id_tempario,
							$form['id_unidad_basica'][$k],
							field::getTransformType($form['ut'][$k],field::tFloat)
							);
						}else{
							$sql=sprintf("UPDATE sa_tempario_det SET ut=%s where id_tempario_det=%s;",
							field::getTransformType($form['ut'][$k],field::tFloat),
							$form['id_tempario_det'][$k]
							);
						}
					}else{
						if($form['id_tempario_det'][$k]!=''){
							$sql=sprintf("DELETE FROM sa_tempario_det where id_tempario_det=%s;",
							$form['id_tempario_det'][$k]
							);
						}
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
			}
			if(!$error){
				$r->alert('Guardado con exito');
				$r->script('cargar();close_window("edit_window");');
				$c->commit();
			}else{
				$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');	
			}
		}else{
			$c->rollback();
			//$r->alert(utf_export($result));
			foreach ($result as $ex){
				if($ex->type==errorMessage::errorNOTNULL){					
						//$r->script('obj("'.$ex->getObject()->getName().'").className="inputNOTNULL";');
					$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputNOTNULL");');					
				}elseif($ex->type==errorMessage::errorType){
					//$r->script('obj("'.$ex->getObject()->getName().'").className="inputERROR";');
					$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputERROR");');
				}else{
					if($ex->numero==connection::errorUnikeKey){
						$r->script('_alert("No se puede ingresar el veh&iacute;culo debido a que el chasis o la placa ya existen registrados");');
						return $r;
					}/*else{
						$r->alert($ex);
					}*/
				}
			}
			$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
		}
		
		$c->close();
		return $r;
	}
	
	function cargar_cliente_pago($idc,$c=null){
		$r=getResponse();
		//$r->alert($idc);return $r;
		if($c==null){
			$c= new connection();
			$c->open();
		}
		$q = new query($c);
		$q->add($c->cj_cc_cliente);
		$q->where(new criteria(sqlEQUAL,$c->cj_cc_cliente->id,"'".$idc."'"));
		$rec=$q->doSelect();
		if($rec){
			if($rec->status=='Inactivo'){
				$r->script('_alert("Debe completar los datos del registro del cliente para poder crear el Vale de recepci&oacute;n");');
				//return $r;
			}
			$info=$rec->lci.'-'.$rec->ci.': '.$rec->apellido.' '.$rec->nombre;
			$r->assign('id_cliente_registro','value',$rec->id);
			$r->assign('info_cliente',inner,$info);
			$r->script('
				close_window("xajax_dialogo_cliente");
				//obj("cedula_cliente_pago").readOnly=true;
				//window.frames["inventario"].probando();
			');
		}
		//$c->close();
		return $r;
	}
	
	function agregar_unidad($id){
		$r=getResponse();
		$c= new connection();
		$c->open();
		//obteniendo loos datos:
			$rec= $c->sa_v_unidad_basica->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_unidad_basica,$id));
			if($rec){
				$r->script("
					unidad_add({
						id_unidad_basica:".$rec->id_unidad_basica.",
						unidad:'".$rec->nombre_unidad_basica.' ('.$rec->nom_marca.' '.$rec->nom_modelo.")',
						ut:'',
						action:'add',
						id_tempario_det:''
					});
				");
			}
		$c->close();
		return $r;
	}
	
	xajaxRegister('guardar');
	xajaxRegister('cargar_listas');
	/*xajaxRegister('cargar_cliente_pago');
	xajaxRegister('cargar_lista_marca');
	xajaxRegister('cargar_lista_modelo');
	xajaxRegister('cargar_lista_version');
	xajaxRegister('cargar_lista_unidad_basica');*/
	xajaxRegister('cargar');
	xajaxRegister('load_page');
	xajaxRegister('agregar_unidad');
		
	xajaxProcess();
	
	includeDoctype();
	$c= new connection();
	$c->open();
	//llenando lo necesario
	$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	
	$modos= $c->sa_modo->doSelect($c)->getAssoc($c->sa_modo->id_modo,$c->sa_modo->descripcion_modo);
	
	$ubasica= new table('sa_v_unidad_basica');
	$ubasica->id_unidad_basica;
	$ubasica->unidad_completa;
	//$ubasica->add(new field("concat_ws(' ',nom_marca,nom_modelo,nom_version,nombre_unidad_basica)","nombre_completo"));
	
	$unidades= $ubasica->doSelect($c)->getAssoc('id_unidad_basica','unidad_completa');
	
	$operadores=array(
		'M'=>'Mano de Obra',
		'L'=>'Latoner&iacute;a',	
		'P'=>'Pintura'
	);
	$c->close();
?>

<html>
	<head>
		<?php 
			includeMeta();
			includeScripts();
			getXajaxJavascript();
			//includeModalBox();
			
		?>
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
		<title>Sistema ERP - Servicios - Posiciones de Trabajo</title>
		<style type="text/css">
			button img{
				padding-right:1px;
				padding-left:1px;
				padding-bottom:1px;
				vertical-align:middle;
			}
			.order_table tbody tr:hover,
			.order_table tbody tr.impar,
			{
				cursor:default;
			}
			.order_table tbody tr:hover img,
			.order_table tbody tr.impar img,
			{
				cursor:pointer;
			}
		</style>
		<script>
			var counter=0;
			var tablag=new Array();
			
			function agregar_unidad(valor){
				//recorrer las unidades
				if(valor==0){
					//alert(valor);
					return;
				}
				for (var i=1; i<=counter;i++){
					var ob= obj('id_unidad_basica'+i);
					if(ob.value==valor){
						//verifica que no esté coulto
						var row= obj('row'+i);
						if(row.style.display=='none'){
							if(_confirm('La unidad fue anteriormente eliminada, &iquest;Desea agregarla de nuevo?')){
								row.style.display='';
								var action=obj('action'+i);
								action.value='add';
							}
						}else{
							_alert('Ya existe la unidad');
						}
						return;
					}
				}
				xajax_agregar_unidad(valor);
			}
			
			function unidad_add(data){
				var tabla=obj('tbody_unidades');
				var nt = new tableRow("tbody_unidades");
				tablag[counter]=nt;
				counter++;
				nt.setAttribute('id','row'+counter);
				nt.$.className='field';
				var c1= nt.addCell();
					//c1.$.className='field';
					//c1.setAttribute('style','width:30%;');
					c1.$.innerHTML=data.unidad;
				var c2= nt.addCell();
					//c1.$.className='field';
					//c2.setAttribute('style','width:30%;');
					c2.$.innerHTML='<input type="text" id="ut'+counter+'" name="ut[]" value="'+data.ut+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" /><input type="hidden" id="id_unidad_basica'+counter+'" name="id_unidad_basica[]" value="'+data.id_unidad_basica+'" /><input type="hidden" id="id_tempario_det'+counter+'" name="id_tempario_det[]" value="'+data.id_tempario_det+'" /><input type="hidden" id="action'+counter+'" name="action[]" value="'+data.action+'" />';
				var c3= nt.addCell();
					//c1.$.className='field';
					//c3.setAttribute('style','width:20%;');
					c3.$.innerHTML='<button type="button" onclick="quitar_unidad('+counter+')"><img src="<?php echo getUrl('img/iconos/minus.png'); ?>" border="0" alt="Quitar" /></button>';
			}
			
			function quitar_unidad(cont){
				if(_confirm("&iquest;Desea eliminar la Unidad de la Lista?")){
					var fila=obj('row'+cont);
					fila.style.display='none';
					var action=obj('action'+cont);
					action.value='delete';
				}
			}
			
			function vaciar_unidades(){
				//alert('s');
				var tabla=obj('tbody_unidades');
				for(var t in tablag){
					//alert(tablag[t]);
					tabla.removeChild(tablag[t].$);
				}
				counter=0;
				tablag=new Array();
			}
		
			var datos = {
				fecha: 'null',
				date:new Date(),
				page:0,
				maxrows:15,
				order:null,
				ordertype:null,
				busca:''
			}
			
			function cargar(){
			//alert('fd');
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','busca='+datos.busca);
				close_window("edit_window");
				close_window("unidad_window");
			}
			
			function agregar(add){
				close_window('unidad_window');
				setDivWindow('edit_window','title_window',true);
				if(add){
					$('#edit_window input').val('');
					$('#edit_window select').val('');
					$('#edit_window #capa_id_art_inventario').html('');
					$("#edit_window #subtitle").html("Agregar");
					$("#edit_window input").not("#fecha_venta").attr("readonly","");
					$("#edit_window select").attr("disabled","");
					$("#edit_window button").attr("disabled","");
					$("#info_cliente").html("");
					//$("#edit_window #buscar_cliente").attr("disabled","");
					//xajax_cargar_listas();
					//obj("placa").focus();
					
				}
				//removiendo las clases que indican error:
				vaciar_unidades();
				$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");
			}
			
			function buscar(){
				var b = obj('busca');
				if(b.value==''){
					_alert('No ha especificado nada que buscar');
					b.focus();
					return;
				}
				datos.page=0;
				datos.busca=b.value;
				cargar();
			}
			function restablecer(){
				var b = obj('busca');
				b.value='';
				datos.page=0;
				datos.order=null;
				datos.ordertype=null;
				datos.busca='';
				cargar();
			}
			
			/*function  calendar_onselect (calendar,date){//DD-MM-AAAA
				if (calendar.dateClicked){
				var dia=date.substr(0,2);
				var mes=parseInt(date.substr(3,2))-1;
				var ano=date.substr(6,4);
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','fecha='+date);
				datos.date=new Date(ano,mes,dia);
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
				cita_calendar.setDate(datos.date);
				cita_calendar.showAtElement(_obj);
			}*/
			
			function buscar_cliente_pago(){
				xajax_dialogo_cliente(0,10,'cj_cc_cliente.ci','','',
						'busqueda=,callback=xajax_cargar_cliente_pago,parent=principal');
			}
			
			/*function listaunidad(){
				
				//agregar_unidad({ut:20,unidad:'A4V',id_tempario_det:0,id_unidad_basica:1});
			}*/
			
		</script>
	</head>
	<body>
<?php include("menu_servicios.inc.php"); ?>
	
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Mantenimiento de Servicios</span><br />
			<span class="subtitulo_pagina" >(Posiciones de Trabajo)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div id="principal">
	<div>
		<button type="button" value="Nuevo" onClick="agregar(true);" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
		<input type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
		
		
		<button type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
		<button type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/cc.png') ?>" /></button>
		
		<hr />
	</div>
	<div id='capaTabla'></div>
	<div align="center" id='paginador'></div>
</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="edit_window" style="min-width:510px;visibility:hidden;">
	<div class="title" id="title_window">
		<span id="subtitle"></span>&nbsp;Posici&oacute;n de trabajo		
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onsubmit="return null;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_tempario" name="id_tempario" />
			<table>
			<tr>
			<td>		
				<table class="insert_table" style="width:auto;">
					<tbody>
						<tr>
							<td class="label">
								Empresa
							</td>
							<td class="field" id="field_id_empresa">
								<?php
									echo inputSelect('id_empresa',$empresas);
								?>
							</td>
						</tr>
						<tr>
							<td class="label">
								Codigo
							</td>
							<td class="field" id="field_codigo_tempario">
								<input type="text" name="codigo_tempario" id="codigo_tempario" />
							</td>
						</tr>
						<tr>
							<td class="label">
								Descripci&oacute;n
							</td>
							<td class="field" id="field_descripcion_tempario">
								<input type="text" name="descripcion_tempario" id="descripcion_tempario" />
							</td>
						</tr>
						<tr>
							<td class="label">
								Modo
							</td>
							<td class="field" id="field_id_modo">
								<?php
									echo inputSelect('id_modo',$modos);
								?>
							</td>
						</tr>
						<tr>
							<td class="label">
								Operador
							</td>
							<td class="field" id="field_operador">
								<?php
									echo inputSelect('operador',$operadores);
								?>
							</td>
						</tr>
						<tr>
							<td class="label">
								Precio
							</td>
							<td class="field" id="field_precio">
								<input type="text" name="precio" id="precio" onchange="set_toNumber(this);" onkeypress="return inputFloat(event);" />
							</td>
						</tr>
						<tr>
							<td class="label">
								Costo
							</td>
							<td class="field" id="field_costo">
								<input type="text" name="costo" id="costo" onchange="set_toNumber(this);" onkeypress="return inputFloat(event);" />
							</td>
						</tr>
					</tbody>
				</table>
			</td>
			<td>
				<table style="width:100%">
					<tr>
						<td>Unidad</td>
						
						<td>UT</td>
						<td style="width:16px;text-align:right;"><button type="button" onclick="setDivWindow('unidad_window','title_unidad_window',true);"><img src="<?php echo getUrl('img/iconos/plus.png'); ?>" border="0" alt="Agregar Unidad" /></button></td>
					</tr>
				</table>
				<div style="overflow:auto;height:200px;width:300px;">
					<table class="insert_table" style="width:90%;">
						<col  style="width:*;" />
						<col  style="width:10%;" />
						<col  style="width:20%;" />
						<tbody id="tbody_unidades">
						
						</tbody>
					</table>
				</div>
			</td>
			</tr>
			<tr>
				<td colspan="2">
				
				<table style="width:100%;" >
					<tbody>
						<tr>
							<td  nowrap="nowrap">
								<div class="leyend">
									<span class="inputNOTNULL"></span> Valor Requerido
								</div>
							</td>
							<td>
								<div class="leyend">
									<span class="inputERROR"></span> Valor Incorrecto
								</div>
							</td>
							<td  align="right">
								<button type="button" id="guardar" onclick="xajax_guardar(xajax.getFormValues('formulario'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Guardar</button>
							</td>
						</tr>
					</tbody>
				</table>
					
				</td>
			</tr>
			</table>
			
		</form>		
	</div>
	<img class="close_window" src="<?php echo getUrl('img/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('unidad_window');close_window('edit_window');" border="0" />
</div>

<div class="window" id="unidad_window" style="min-width:210px;visibility:hidden;">
	<div class="title" id="title_unidad_window">
		Agregar Unidad	
	</div>
	<div class="content">
		Seleccione una unidad de la lista para agregar:
		<div id="lista_unidades">
		
		<?php
			echo inputSelect('unidad_basica',$unidades,0,array('onchange'=>'agregar_unidad(this.value);'),0);
		?>
		
		</div>
	</div>
	<img class="close_window" src="<?php echo getUrl('img/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('unidad_window');" border="0" />
</div>	


<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
		cargar();
	</script>
	</body>
</html>

