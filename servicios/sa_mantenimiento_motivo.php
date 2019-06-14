<?php
@session_start();
define('PAGE_PRIV','sa_mantenimiento_motivo'); //nuevo gregor
//define('PAGE_PRIV','sa_motivo');//anterior
require_once("../inc_sesion.php");
//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
	include("../connections/conex.php");//usa el vldtipodato que necesita ac_iv_general
	include("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal
	
	$empresas_usuario;
	 $conex;
			$query_empresa_usuario = sprintf("SELECT id_empresa_reg
										FROM vw_iv_usuario_empresa
										WHERE id_usuario = %s",valTpDato($_SESSION["idUsuarioSysGts"],"int"));
			$busqueda_empresa_usuario = mysql_query($query_empresa_usuario,$conex);
			while ($row = mysql_fetch_array($busqueda_empresa_usuario)){
				$empresas_usuario[]=$row["id_empresa_reg"];
				
				}
	
	function load_page($page,$maxrows,$order,$ordertype,$capa,$args='',$id_empresa_busqueda = ""){
		$r= getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			$r->assign($capa,inner,'Acceso denegado');
			return $r;
		}
		//$r->alert(utf8_encode($args));
		$c = new connection();
		$c->open();
		
		//procesando argumentos:
		$argumentos=paginator::getExplodeArgs($args);
		$sa_motivo_cita = $c->sa_v_motivo_cita;
		
		
		
		$query = new query($c);
		$query->add($sa_motivo_cita);
		
		/** Busqueda de empresa por usuario **/
		global $empresas_usuario;			
			
			
			//svar_dump($empresas_usuario);
		/* Fin busqueda de empresa por usuario */
		
		if($argumentos['busca']!=''){
			
			
			$query->where(
				new criteria(sqlOR, array(
					new criteria(' like ',$sa_motivo_cita->motivo,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$sa_motivo_cita->nombre_empresa_sucursal,"'%".$argumentos['busca']."%'"),/*,
					new criteria(' like ',$sa_motivo_cita->color,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$c->pg_empresa->nombre_empresa,"'%".$argumentos['busca']."%'")*/
					//new criteria(sqlEQUAL, $sa_motivo_cita->id_empresa,$_SESSION["idEmpresaUsuarioSysGts"]),
					
					)
				)
			);//->addCriteria(new criteria(sqlEQUAL, $sa_motivo_cita->id_empresa,$_SESSION["idEmpresaUsuarioSysGts"]));
			//busqueda empresa seleccionado, se puede con add o como abajo
			//$query->where(new criteria(sqlEQUAL, $sa_motivo_cita->id_empresa,$_SESSION["idEmpresaUsuarioSysGts"]));
			/*if($id_empresa_busqueda != ""){
				$query->where(new criteria(sqlEQUAL, $sa_motivo_cita->id_empresa,$argumentos['id_empresa_enviada']));
			}*/
			
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
		
		//Que muestre ya filtrado por empresa, sino la "busqueda" es vacia = espacio " " que traiga todo sobre la empresa por defecto
		/*if($id_empresa_busqueda != ""){
			$query->where(new criteria(sqlEQUAL, $sa_motivo_cita->id_empresa,$argumentos['id_empresa_enviada']));
		}elseif($id_empresa_busqueda =="" && $argumentos['busca']=='' || $argumentos['busca']==' '){
			$query->where(new criteria(sqlEQUAL, $sa_motivo_cita->id_empresa,$argumentos['id_empresa_enviada']));			
			}*/
			
		$query->where(new criteria(sqlEQUAL, $sa_motivo_cita->id_empresa,$argumentos['id_empresa_enviada']));
		
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($sa_motivo_cita->id_motivo_cita,'ID').'</td>
				<td>'.$paginador->get($sa_motivo_cita->nombre_empresa_sucursal,'Empresa').'</td>
				<td>'.$paginador->get($sa_motivo_cita->motivo,'Motivo').'</td>
				<td>'.$paginador->get($sa_motivo_cita->max_diario,'Max. Diario').'</td>
				<td>'.$paginador->get($sa_motivo_cita->max_semanal,'Max. Semanal').'</td>
				<td>'.$paginador->get($sa_motivo_cita->max_mensual,'Max. Mensual').'</td>
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
					
					<td align="center">'.$rec->id_motivo_cita.'</td>
					<td align="center">'.$rec->nombre_empresa_sucursal.'</td>
					<td align="center">'.$rec->motivo.'</td>
					<td align="center">'.$rec->max_diario.'</td>
					<td align="center">'.$rec->max_semanal.'</td>
					<td align="center">'.$rec->max_mensual.'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_motivo_cita.',\'view\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_motivo_cita.',\'edit\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/delete.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar('.$rec->id_motivo_cita.',\'delete\');""></td>
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
		datos.js_empresa ='".$argumentos['id_empresa_enviada']."';
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
			$r->assign('field_id_motivo_cita',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_unidad_basica($c,$version);
		$select=inputSelect('id_motivo_cita',$lassoc,$default);
		$r->assign('field_id_motivo_cita',inner,$select);
		//$c->close();
		return $r;
	}*/
	
	function cargar($id, $mode='view'){
		$r=getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			//$r->alert('acceso denegado');
			return $r;
		}
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
		$q->add($c->sa_v_motivo_cita);
		
		$q->where(new criteria(sqlEQUAL,$c->sa_v_motivo_cita->id_motivo_cita,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
		//$r->alert($q->getSelect());return $r;
			//cargnado la lista de mepresas
			//$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			if($rec){
				$r->assign('id_motivo_cita','value',$rec->id_motivo_cita);
				$r->assign('id_empresa','value',$rec->id_empresa);
				$r->assign('motivo','value',$rec->motivo);
				$r->assign('max_diariop','value',$rec->max_diario);
				$r->assign('max_semanalp','value',$rec->max_semanal);
				$r->assign('max_mensualp','value',$rec->max_mensual);
				$r->assign('id_empresa','value',$rec->id_empresa);
				//$r->script('xajax_cargaLstEmpresaFinal('.$rec->id_empresa.',"","id_empresa","field_id_empresa","")');//gregor
				
				//cargando los detalles:
				
				$recdet=$c->sa_submotivo->doSelect($c,new criteria(sqlEQUAL,$c->sa_submotivo->id_motivo_cita,$id));
				if($recdet){
					foreach($recdet as $det){
						$scriptdet.="
unidad_add({
	id_motivo_cita:".$det->id_motivo_cita.",
	descripcion_submotivo:'".addslashes($det->descripcion_submotivo)."',
	max_diario:'"._formato($det->max_diario,0)."',
	max_mensual:'"._formato($det->max_mensual,0)."',
	max_semanal:'"._formato($det->max_semanal,0)."',
	action:'add',
	id_submotivo:".$det->id_submotivo.",
	ut_diagnostico:".$det->ut_diagnostico."
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
			if (!xvalidaAcceso($r,PAGE_PRIV,eliminar)){
				return $r;
			}
			$c->begin();
			$rec=$c->sa_motivo_cita->doDelete($c,new criteria(sqlEQUAL,$c->sa_motivo_cita->id_motivo_cita,$id));
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
		$sa_motivo_cita = new table("sa_motivo_cita");
		
		$sa_motivo_cita->add(new field('id_motivo_cita','',field::tInt,$form['id_motivo_cita']));
		$sa_motivo_cita->add(new field('id_empresa','',field::tInt,$form['id_empresa']));
		$sa_motivo_cita->add(new field('motivo','',field::tString,$form['motivo']));
		$sa_motivo_cita->add(new field('max_diario','',field::tInt,$form['max_diariop'],false));
		$sa_motivo_cita->add(new field('max_semanal','',field::tInt,$form['max_semanalp'],false));
		$sa_motivo_cita->add(new field('max_mensual','',field::tInt,$form['max_mensualp'],false));
		
		
		$c->begin();
		$id_motivo_cita=$form['id_motivo_cita'];
		if($form['id_motivo_cita']==''){
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_motivo_cita->doInsert($c,$sa_motivo_cita->id_motivo_cita);
			$id_motivo_cita=$c->soLastInsertId();
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_motivo_cita->doUpdate($c,$sa_motivo_cita->id_motivo_cita);
		}
		if($result===true){
			//recorriendo los detalles:
			//$r->alert(utf_export($form));
			if(isset($form['id_submotivo'])){
				foreach($form['id_submotivo'] as $k => $v){
					$sql='';
					//verificando por accion
					//$r->alert(utf_export($form));
					if($form['action'][$k]=='add'){
						if($form['descripcion_submotivo'][$k]==''){
							$r->script('$("#row'.($k+1).'").addClass("inputNOTNULL");');
							$error=true;
							continue;
						}
						if($form['max_diario'][$k]=='' || $form['max_diario'][$k]=='0'){
							$r->script('$("#row'.($k+1).'").addClass("inputNOTNULL");');
							$error=true;
							continue;
						}
						if($form['max_semanal'][$k]=='' || $form['max_semanal'][$k]=='0'){
							$r->script('$("#row'.($k+1).'").addClass("inputNOTNULL");');
							$error=true;
							continue;
						}
						if($form['max_mensual'][$k]=='' || $form['max_mensual'][$k]=='0'){
							$r->script('$("#row'.($k+1).'").addClass("inputNOTNULL");');
							$error=true;
							continue;
						}
						if($form['ut_diagnostico'][$k]=='' || $form['ut_diagnostico'][$k]=='0'){
							$r->script('$("#row'.($k+1).'").addClass("inputNOTNULL");');
							$error=true;
							continue;
						}
						if($form['id_submotivo'][$k]==''){
							$sql=sprintf("INSERT INTO sa_submotivo(id_submotivo,id_motivo_cita,max_diario,max_semanal,max_mensual,ut_diagnostico,descripcion_submotivo) VALUES (NULL , '%s', '%s', '%s','%s', '%s', '%s');",
							$id_motivo_cita,
							field::getTransformType($form['max_diario'][$k],field::tFloat),
							field::getTransformType($form['max_semanal'][$k],field::tFloat),
							field::getTransformType($form['max_mensual'][$k],field::tFloat),
							field::getTransformType($form['ut_diagnostico'][$k],field::tInt),
							$c->parseUTF8(addslashes($form['descripcion_submotivo'][$k]))
							);
						}else{
							$sql=sprintf("UPDATE sa_submotivo SET max_diario='%s',max_semanal='%s',max_mensual='%s',ut_diagnostico='%s',descripcion_submotivo='%s' where id_submotivo=%s;",
							field::getTransformType($form['max_diario'][$k],field::tFloat),
							field::getTransformType($form['max_semanal'][$k],field::tFloat),
							field::getTransformType($form['max_mensual'][$k],field::tFloat),
							field::getTransformType($form['ut_diagnostico'][$k],field::tInt),
							$c->parseUTF8(addslashes($form['descripcion_submotivo'][$k])),
							$form['id_submotivo'][$k]
							);//field::getTransformType($form['descripcion_submotivo'][$k],field::tString)
						}
					}else{
						if($form['id_submotivo'][$k]!=''){
							$sql=sprintf("DELETE FROM sa_submotivo where id_submotivo=%s;",
							$form['id_submotivo'][$k]
							);
						}
					}
					if($sql!='' && !$error){
						//$r->alert(utf_export($sql));
						//echo $sql;
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
						$r->script('_alert("El Motivo ya existe");');
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
			$rec= $c->sa_v_unidad_basica->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_motivo_cita,$id));
			if($rec){
				$r->script("
					unidad_add({
						id_motivo_cita:".$rec->id_motivo_cita.",
						unidad:'".$rec->nombre_unidad_basica.' ('.$rec->nom_marca.' '.$rec->nom_modelo.")',
						max_diario:'',
						action:'add',
						id_submotivo:'',
						ut_diagnostico:''
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
	$empresas=getEmpresaList($c,false);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	
	//$modos= $c->sa_modo->doSelect($c)->getAssoc($c->sa_modo->id_modo,$c->sa_modo->descripcion_modo);
	
	//$ubasica= new table('sa_v_unidad_basica');
	//$ubasica->id_motivo_cita;
	//$ubasica->unidad_completa;
	//$ubasica->add(new field("concat_ws(' ',nom_marca,nom_modelo,nom_version,nombre_unidad_basica)","nombre_completo"));
	
	//$unidades= $ubasica->doSelect($c)->getAssoc('id_motivo_cita','unidad_completa');
	
	/*$operadores=array(
		'M'=>'Mano de Obra',
		'L'=>'Latoner&iacute;a',	
		'P'=>'Pintura'
	);*/
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
                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Motivos y Restricciones de Citas</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
		<style type="text/css">
			button img{
				padding-right:1px;
				padding-left:1px;
				padding-bottom:1px;
				vertical-align:middle;
			}
			.order_table tbody tr:hover,
			.order_table tbody tr.impar
			{
				cursor:default;
			}
			.order_table tbody tr:hover img,
			.order_table tbody tr.impar img
			{
				cursor:pointer;
			}
		</style>
		
		<script type="text/javascript">
			detectEditWindows({edit_window:'guardar'});
			var counter=0;
			var tablag=new Array();
			
			/*function agregar_unidad(valor){
				//recorrer las unidades
				if(valor==0){
					//alert(valor);
					return;
				}
				for (var i=1; i<=counter;i++){
					var ob= obj('id_motivo_cita'+i);
					if(ob.value==valor){
						//verifica que no esté coulto
						var row= obj('row'+i);
						if(row.style.display=='none'){
							if(_confirm('La Restrici&oacute;n fue anteriormente eliminada, &iquest;Desea agregarla de nuevo?')){
								row.style.display='';
								var action=obj('action'+i);
								action.value='add';
							}
						}else{
							_alert('Ya existe la Restrici&oacute;n');
						}
						return;
					}
				}
				xajax_agregar_unidad(valor);
			}*/
			
			function agregar_restriccion(){
				unidad_add({descripcion_submotivo:'',max_diario:1,max_semanal:1,max_mensual:1,id_submotivo:'',ut_diagnostico:'',action:'add'});
				obj('descripcion_submotivo'+counter).focus();
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
					c1.$.innerHTML='<input type="text" id="descripcion_submotivo'+counter+'" name="descripcion_submotivo[]" value="'+data.descripcion_submotivo+'" style="width:96%;" />';
				var c2= nt.addCell();
					//c1.$.className='field';
					//c2.setAttribute('style','width:30%;');
					c2.$.innerHTML='<input type="text" id="max_diario'+counter+'" name="max_diario[]" value="'+data.max_diario+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" /><input type="hidden" id="id_motivo_cita'+counter+'" name="id_motivo_cita[]" value="'+data.id_motivo_cita+'" /><input type="hidden" id="id_submotivo'+counter+'" name="id_submotivo[]" value="'+data.id_submotivo+'" /><input type="hidden" id="action'+counter+'" name="action[]" value="'+data.action+'" />';
				var c4 = nt.addCell();
					c4.$.innerHTML='<input type="text" id="max_semanal'+counter+'" name="max_semanal[]" value="'+data.max_semanal+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" />';
				var c5 = nt.addCell();
					c5.$.innerHTML='<input type="text" id="max_mensual'+counter+'" name="max_mensual[]" value="'+data.max_mensual+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" />';
				var c6 = nt.addCell();
					c6.$.innerHTML='<input type="text" id="ut_diagnostico'+counter+'" name="ut_diagnostico[]" value="'+data.ut_diagnostico+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" />';
				var c3= nt.addCell();
					//c1.$.className='field';
					//c3.setAttribute('style','width:20%;');
					c3.$.innerHTML='<button type="button" onclick="quitar_unidad('+counter+')"><img src="<?php echo getUrl('img/iconos/minus.png'); ?>" border="0" alt="Quitar" /></button>';
			}
			
			function quitar_unidad(cont){
				if(_confirm("&iquest;Desea eliminar la Restrici&oacute;n de la Lista?")){
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
				busca:'',
				js_empresa:'<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>'
				
			}
			
			function cargar(){
			//alert('fd');
			
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','busca='+datos.busca + ',id_empresa_enviada='+datos.js_empresa, datos.js_empresa);
				close_window("edit_window");
				//close_window("unidad_window");
			}
			
			function agregar(add){
				//close_window('unidad_window');
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
					//xajax_cargaLstEmpresaFinal('<?php //echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','',"id_empresa","field_id_empresa","");
					
				}
				//removiendo las clases que indican error:
				vaciar_unidades();
				$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");
			}
			
			function buscar(){
				var b = obj('busca');
				/*if(b.value==''){
					_alert('No ha especificado nada que buscar');
					b.focus();
					return;
				}*/
				datos.page=0;
				datos.busca=b.value;
				datos.js_empresa = document.getElementById('lstEmpresa').value;
				cargar();
			}
			function restablecer(){
				var b = obj('busca');
				b.value='';
				datos.page=0;
				datos.order=null;
				datos.ordertype=null;
				datos.busca='';
				datos.js_empresa = '<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>';
				document.getElementById('lstEmpresa').value = '<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>';
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
				
				//agregar_unidad({max_diario:20,unidad:'A4V',id_submotivo:0,id_motivo_cita:1});
			}*/
			
			
		</script>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Mantenimiento de Servicios</span><br />
			<span class="subtitulo_pagina" >(Motivos y Restricciones de Citas)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div id="principal">
	<div>
		<button type="button" value="Nuevo" onClick="agregar(true);" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
		<input type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
		
        <tag_empresa id="tdlstEmpresa"> 
        </tag_empresa>
       
		
		<button type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
		<button type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
		
		<hr />
	</div>
	<div id='capaTabla'></div>
	<div align="center" id='paginador'></div>
</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="edit_window" style="top:0px;left:0px;min-width:510px;max-width:510px;visibility:hidden;">
	<div class="title" id="title_window">
		<span id="subtitle"></span>&nbsp;Motivos y Restricciones de Citas
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onsubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_motivo_cita" name="id_motivo_cita" />
			<table style="width:100%;">
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
									//echo inputSelect('id_empresa',$empresas_usuario);//Ya no va la hice yo, pero ahora usara la de xajax javascript
								?>
							</td>
						</tr>
						<tr>
							<td class="label">
								Motivo
							</td>
							<td class="field" id="field_motivo">
								<input type="text" name="motivo" id="motivo" />
							</td>
						</tr>
						<tr>
							<td class="label">
								M&aacute;ximo Diario
							</td>
							<td class="field" id="field_max_diario">
								<input type="text" name="max_diariop" id="max_diariop" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" />
							</td>
						</tr>
						<tr>
							<td class="label">
								M&aacute;ximo Semanal
							</td>
							<td class="field" id="field_max_semanal">
								<input type="text" name="max_semanalp" id="max_semanalp" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" />
							</td>
						</tr>
						<tr>
							<td class="label">
								M&aacute;ximo Mensual
							</td>
							<td class="field" id="field_max_mensual">
								<input type="text" name="max_mensualp" id="max_mensualp" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" />
							</td>
						</tr>
						
					</tbody>
				</table>
			</td>
			</tr>
			<tr>
			<td>
				<table style="width:100%">
					<tr>
						<td>Restricciones:</td>
						
						<td>Entradas M&aacute;ximas:</td>
						<td style="width:16px;text-align:right;"><button type="button" onclick="agregar_restriccion();"><img src="<?php echo getUrl('img/iconos/plus.png'); ?>" border="0" alt="Agregar Unidad" /></button></td>
						
					</tr>
				</table>
				<div style="overflow:auto;height:200px;width:98%;">
					<table class="order_table" style="width:95%;">
						<col  style="" />
						<col  style="width:10%;" />
						<col  style="width:10%;" />
						<col  style="width:10%;" />
						<col  style="width:10%;" />
						<col  style="width:10%;text-align:center;" />
						<thead>
							<tr>
								<td>Restricci&oacute;n</td>
								<td>D&iacute;a</td>
								<td>Sem.</td>
								<td>Mes</td>
								<td>UT</td>
								<td>&nbsp;</td>
							</tr>
						</thead>
						<tbody id="tbody_unidades">
						
						</tbody>
					</table>
				</div>
			</td>
			</tr>
			<tr>
				<td>
				
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
								<button type="submit" id="guardar" onclick="xajax_guardar(xajax.getFormValues('formulario'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Guardar</button>
							</td>
						</tr>
					</tbody>
				</table>
					
				</td>
			</tr>
			</table>
			
		</form>		
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('edit_window');" border="0" />
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
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('unidad_window');" border="0" />
</div>	

</div>

<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
	//cargaLstEmpresaFinal($selId = "", $accion = "onchange" , $id_etiqueta = false, $id_objetivo = false,$todos=false)
		xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','onchange="buscar();"');
		xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','',"id_empresa","field_id_empresa","");
		//xajax_cargaLstEmpresaFinal('<?php //echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','',"id_empresa","field_id_empresa","");
		cargar();
		
	
	</script>
    
	</body>
</html>

