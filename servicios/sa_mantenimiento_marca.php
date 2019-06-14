<?php
//require("sa_mantenimiento_marca_xajax.php");
@session_start();

	require_once("../inc_sesion.php");
//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	
        define('PAGE_PRIV','sa_mantenimiento_marca');
        
	function load_page($page,$maxrows,$order,$ordertype,$capa,$args=''){
		global $from_vehiculos;
		$r= getResponse();
		//if($from_vehiculos){
			//$r->alert('filtrar por vehiculos');
		//}
		//$r->alert(utf8_encode($args));
		if (!validaAcceso(PAGE_PRIV)){
			$r->alert('acceso denegado');
			$r->assign($capa,inner,'Acceso denegado');
			return $r;
		}
		$c = new connection();
		$c->open();
		
		//procesando argumentos:
		$argumentos=paginator::getExplodeArgs($args);
		
		//if($from_vehiculos){		
			$an_marca = $c->an_marca;
		//}else{
		//	$an_marca = $c->an_marca;
		//}
		$query = new query($c);
		$query->add($an_marca);
		
		if($argumentos['busca']!=''){
			$query->where(new criteria(sqlOR,array(
				new criteria(' like ',$an_marca->nom_marca,"'%".$argumentos['busca']."%'"),
				new criteria(' like ',$an_marca->des_marca,"'%".$argumentos['busca']."%'")				
			)));
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
	
		/*if($from_vehiculos){
			//$r->alert('filtrar por vehiculos');
			$query->where(new criteria(sqlEQUAL,$an_marca->empresa_catalogo,$_SESSION['session_empresa']));
		}*/
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($an_marca->nom_marca,'Marca').'</td>
				<td>'.$paginador->get($an_marca->des_marca,'Descripci&oacute;n').'</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				</tr></thead><tbody>';
				$class='';
				foreach($rec as $v){
					
					$html.='<tr class="'.$class.'">
					
					<td align="center">'.$rec->nom_marca.'</td>
					<td align="center">'.$rec->des_marca.'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_marca.',\'view\');"></td>
					
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_marca.',\'edit\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/delete.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar('.$rec->id_marca.',\'delete\');""></td>
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
	
	function cargar_empresas($id_marca=null,$c=null){
		$r=getResponse();
		if($c==null){
			$c= new connection();
			$c->open();
		}
		//$r->alert($id_marca);
		//cargando la lista de empresas:
			
			
			$t = new table('sa_v_empresa_sucursal');
			$t->id_empresa;
			$t->nombre_empresa_sucursal;
			if($id_marca!=''){
				$tsq= new table('sa_unidad_empresa');
				$tsq->id_unidad_empresa;
				$subquery= new query($c,'id_unidad_empresa');
				$subquery->add($tsq);
				$subquery->where(new criteria(sqlEQUAL,$c->sa_v_empresa_sucursal->id_empresa,$c->sa_unidad_empresa->id_empresa));				
				$subquery->where(new criteria(sqlEQUAL,$c->sa_unidad_empresa->id_marca,$id_marca));
				$t->add($subquery);
			}
			
			$sq = new query($c);
			$sq->add($t);
			/*$sq->where(new criteria(sqlOR,array(
				new criteria(sqlEQUAL,$c->sa_v_unidad_empresa->id_marca,$rec->id_marca),
				new criteria(sqlIS,$c->sa_v_unidad_empresa->id_marca,sqlNULL)
			)));*/
			//$r->alert($sq->getSelect());
			$reqemp= $sq->doSelect();
			if($reqemp){
				foreach($reqemp as $v){
					if($v->id_unidad_empresa!=''){
						$check='checked="checked"';
					}else{
						$check='';					
					}
					$html.='<label><input type="checkbox" name="select_empresa['.$v->id_empresa.']" value="1" '.$check.' />'.$v->nombre_empresa_sucursal.'</label><input type="hidden" name="id_unidad_empresa['.$v->id_empresa.']" value="'.$v->id_unidad_empresa.'" />
					<input type="hidden" name="id_empresa[]" value="'.$v->id_empresa.'" /><br/>
					';
				}
			}
			$r->assign('lista_empresas',inner,$html);
		return $r;
	}
	
	function cargar_marca($c,$id_marca){
		$r=getResponse();
		if($c==null){
			$c= new connection();
			$c->open();
		}
		//buscando las marcas
		$recmarca=$c->an_marca->doSelect($c)->getAssoc('id_marca','nom_marca');
		$select = inputSelect('mar_uni_bas',$recmarca,$id_marca,array('onchange'=>'xajax_cargar_modelo(null,null,this.value);xajax_cargar_version(null,null,null);'));
		$r->assign('field_mar_uni_bas',inner,($select));
		return $r;
	}
	
	function cargar_modelo($c,$id_modelo,$id_marca){
		$r=getResponse();
		if ($id_marca==''){
			$r->assign('field_mod_uni_bas',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c= new connection();
			$c->open();
		}
		//buscando las marcas
		$recmodelo=$c->an_modelo->doSelect($c,new criteria(sqlEQUAL,$c->an_modelo->id_marca,$id_marca))->getAssoc('id_modelo','nom_modelo');
		$select = inputSelect('mod_uni_bas',$recmodelo,$id_modelo,array('onchange'=>'xajax_cargar_version(null,null,this.value);'));
		$r->assign('field_mod_uni_bas',inner,($select));
		return $r;
	}
	function cargar_version($c,$id_version,$id_modelo){
		$r=getResponse();
		if ($id_modelo==''){
			$r->assign('field_ver_uni_bas',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c= new connection();
			$c->open();
		}
		//buscando las marcas
		$recver=$c->an_version->doSelect($c,new criteria(sqlEQUAL,$c->an_version->id_modelo,$id_modelo))->getAssoc('id_version','nom_version');
		$select = inputSelect('ver_uni_bas',$recver,$id_version);
		$r->assign('field_ver_uni_bas',inner,($select));
		return $r;
	}
	
	
	function cargar($id, $mode='view'){
		$r=getResponse();
		if (!validaAcceso(PAGE_PRIV)){
			$r->alert('No tiene permisos para ver Unidades');
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
		$q->add($c->an_marca);
		
		$q->where(new criteria(sqlEQUAL,$c->an_marca->id_marca,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
			//cargnado la lista de mepresas
			//$empresas=getEmpresaList($c);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			if($rec){
				$r->assign('id_marca','value',$rec->id_marca);
				$r->assign('nom_marca','value',$rec->nom_marca);
				$r->assign('des_marca','value',$rec->des_marca);
		
			}
			
			//$r->loadCommands(cargar_empresas($rec->id_marca,$c));
			$r->script('agregar(false);');
			
			$r->script('
				$("#edit_window input").not(".dater").attr("readonly","'.$view[$mode].'");
				$("#edit_window select, #edit_window input[type=checkbox]").attr("disabled","'.$view[$mode].'");
				$("#edit_window #guardar, #edit_window button, #edit_window .date input").attr("disabled","'.$view[$mode].'");
				');
		}else{
		
			if (!validaAcceso(PAGE_PRIV,eliminar)){
				$r->alert('No tiene permisos para Eliminar Unidades');
				
				return $r;
			}
			$c->begin();
			$rec=$c->an_marca->doDelete($c,new criteria(sqlEQUAL,$c->an_marca->id_marca,$id));
			if($rec===true){
				$c->commit();
				$r->script('_alert("Registro eliminado con &eacute;xito");cargar();');
			}else{
				$r->script('_alert("No se puede eliminar el registro, es posible que el mismo ya est&aacute; siendo utilizado");');
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
		
		$an_marca = new table("an_marca");
		
		$an_marca->add(new field('id_marca','',field::tInt,$form['id_marca']));
		$an_marca->add(new field('nom_marca','',field::tString,$form['nom_marca']));
		$an_marca->add(new field('des_marca','',field::tString,$form['des_marca']));
		
		$c->begin();
		if($form['id_marca']==''){
			if (!validaAcceso(PAGE_PRIV,insertar)){
				$r->alert('No tiene permisos para Agregar Marcas');
				$c->rollback();
				return $r;
			}
			$result=$an_marca->doInsert($c,$an_marca->id_marca);
			//$id_marca=$c->soLastInsertId();
		}else{
			if (!validaAcceso(PAGE_PRIV,editar)){
				$r->alert('No tiene permisos para Editar Marcas');
				$c->rollback();
				return $r;
			}
			$result=$an_marca->doUpdate($c,$an_marca->id_marca);
			//$id_marca=$form['id_marca'];
		}
		if($result===true){
			
			//if(!$errors){
				$r->alert('Guardado con exito');
				$r->script('cargar();close_window("edit_window");');
				$c->commit();
			//}else{
			//	$r->alert('Error al asignar las empresas de catalogo, consulte al administrador del sistema.');
			//}
			
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
						$r->script("_alert('C&oacute;digo Duplicado');");
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
	
	
	
	xajaxRegister('guardar');
	xajaxRegister('cargar_marca');
	xajaxRegister('cargar_modelo');
	xajaxRegister('cargar_version');
	xajaxRegister('cargar_empresas');
	xajaxRegister('cargar');
	xajaxRegister('load_page');
		
	xajaxProcess();
	
	includeDoctype();
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
		
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Marcas</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
		<?php //include("sa_mantenimiento_marca_js.php"); ?>
        
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
<script>

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
	}
	
	function agregar(add){
		setDivWindow('edit_window','title_window',true);
		if(add){
			$('#edit_window input').not('input[type=checkbox]').val('');
			//$('#edit_window #capa_id_uni_bas').html('');
			$("#edit_window #subtitle").html("Agregar");
			$("#edit_window input").not('.dater').attr("readonly","");
			$("#edit_window select,#edit_window input[type=checkbox]").attr("disabled","");
			$("#edit_window #guardar,#edit_window button").attr("disabled","");
			$("#edit_window .date input").attr("disabled","");
			$('#edit_window select').val('');
			$('#capa_imagen_auto').html('');
			$('#capa_imagen_auto input[type=checkbox]').attr('checked',false);
			//xajax_cargar_empresas();
			//xajax_cargar_marca(null,null);
			//xajax_cargar_modelo(null,null,null);
			//xajax_cargar_version(null,null,null);
			//xajax_cargar_empresas(null, null);
			obj('opciones_copia_p').style.display='none';
		}else{
			obj('opciones_copia_p').style.display='';
		}
		$("#edit_window .dater").attr("readonly","true");
		//removiendo las clases que indican error:
		$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");
		obj('opciones_copia').style.display='none';
		obj('unidad_copy').checked=false;
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
	
	
		function _vpopupex(url,marco){
			var x = (screen.width - 320) / 2;
			var y = (screen.height - 400) / 2;
			var r= window.open(url,marco,"toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=320,height=400,left="+x+",top="+y+"");
			r.focus();
			return r;
		}
		
		function mensaje_copia(cobj){
			if(cobj.checked){
				cobj.checked = _confirm("&iquest;Desea guardar esta unidad como una nueva unidad b&aacute;sica?\n\nDebe de especificar un nombre de cat&aacute;logo nuevo, de lo contrario no se efectuar&aacute; la copia.\n\nPuede especificar la copia todos los temparios, repuestos y paquetes de servicio compatibles, adem&aacute;s de los repuestos alternos y sustitutos.\n\nTenga en cuenta que esta es una operaci&oacute;n delicada que implica copiar cierta cantidad de registros, por lo que puede tardar varios minutos.\n\nPuede cambiar los otros datos pero en el caso de la Marca, Modelo y Versi&oacute;n debe conservarlos por la compatibilidad de los paquetes de servicio, luego de la copia puede dirigirse al m&oacute;dulo de servicios y modificar individualmente cada opci&oacute;n.\n\n----- AVISO IMPORTANTE -----\n Esta operaci&oacute;n no tiene retroceso y no se resuelve eliminando la unidad.");
				obj('opciones_copia').style.display='';
				$('#opciones_copia input[type=checkbox]').attr('checked','checked');
			}
			if(!cobj.checked){
				obj('opciones_copia').style.display='none';
			}
		}
	
</script>
        
	</head>


	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Mantenimiento de Servicios</span><br />
			<span class="subtitulo_pagina" >(Marcas)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		<button type="button" value="Nuevo" onClick="agregar(true);" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
		<input type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
		
		
		<button type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
		<button type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
		
		<hr />
	</div>
	<div id='capaTabla'></div>
	<div align="center" id='paginador'></div>
</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="edit_window" style="width:300px;min-width:300px;max-width:300px;top:0px;left:0px;visibility:hidden;">
	<div class="title" id="title_window">
		<span id="subtitle"></span>&nbsp;Marcas
	</div>
	<div class="content">
		
<?php
	//include("sa_mantenimiento_marca_form.php");
?>		

<form id="formulario" name="formulario" onsubmit="return false;" style="margin:0px;padding:0px;">
	<input type="hidden" id="id_marca" name="id_marca" />
	<table class="insert_table">
		<tbody>
			<tr>
				<td class="label">
					Nombre*
				</td>
				<td class="field" id="field_nom_marca">
					<input type="text" name="nom_marca" id="nom_marca" maxlength="30" />
				</td>
			</tr>
			<tr>
				<td class="label">
					Descripci&oacute;n*
				</td>
				<td class="field" id="field_des_marca">
					<input type="text" name="des_marca" id="des_marca" maxlength="30" />
				</td>
			</tr>
			
		</tbody>
		<tfoot>
			<tr>
				<td colspan="2" align="right">
					<button type="submit" id="guardar" onclick="xajax_guardar(xajax.getFormValues('formulario'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Guardar</button>
				</td>
			</tr>
		</tfoot>
	</table>
</form>

	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('edit_window');" border="0" />
</div>	
</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
		cargar();
	</script>
	</body>
</html>

