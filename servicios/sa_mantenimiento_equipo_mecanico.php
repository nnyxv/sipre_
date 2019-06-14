<?php
@session_start();
define('PAGE_PRIV','sa_mantenimiento_equipo_mecanico');//nuevo gregor
//define('PAGE_PRIV','sa_equipo_mecanico');//nuevo gregor
require_once("../inc_sesion.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	
	
	include("../connections/conex.php");//usa el vldtipodato que necesita ac_iv_general
	include("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal
	
	function load_page($page,$maxrows,$order,$ordertype,$capa,$args=''){
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
		
		$sa_equipos_mecanicos = $c->sa_v_equipos_mecanicos;
		$query = new query($c);
		$query->add($sa_equipos_mecanicos);
		
		if($argumentos['busca']!=''){
			$query->where(new criteria(sqlOR,array(
				new criteria(' like ',$sa_equipos_mecanicos->nombre_equipo,"'%".$argumentos['busca']."%'"),			
				new criteria(' like ',$sa_equipos_mecanicos->nombre_empleado,"'%".$argumentos['busca']."%'")
				)));
				
			//busqueda
			$query->where(new criteria(sqlEQUAL, $sa_equipos_mecanicos->id_empresa,$argumentos['id_empresa_enviada']));
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
	
		//normal
		$query->where(new criteria(sqlEQUAL, $sa_equipos_mecanicos->id_empresa,$argumentos['id_empresa_enviada']));
		
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($sa_equipos_mecanicos->nombre_empresa_sucursal,'Empresa').'</td>
				<td>'.$paginador->get($sa_equipos_mecanicos->nombre_empleado,'Jefe de Taller').'</td>
				<td>'.$paginador->get($sa_equipos_mecanicos->nombre_equipo,'Nombre Equipo').'</td>
				<td>'.$paginador->get($sa_equipos_mecanicos->tipo_equipo,'Tipo Equipo').'</td>
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
					
					<td align="center">'.$rec->nombre_empresa_sucursal.'</td>
					<td align="center">'.$rec->nombre_empleado.'</td>
					<td align="center">'.$rec->nombre_equipo.'</td>
					<td align="center">'.$rec->tipo_equipo.'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_equipo_mecanico.',\'view\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_equipo_mecanico.',\'edit\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/delete.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar('.$rec->id_equipo_mecanico.',\'delete\');""></td>
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
	
	function cargar_empresas(){
		$r=getResponse();
		$c=new connection();
		$c->open();
		//cargnado la lista de mepresas
		$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
		$select_empresa=inputSelect('id_empresa',$empresas,null);
		$r->assign('field_id_empresa',inner,$select_empresa);
		$c->close();
		return $r;
	}
	
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
		$q->add($c->sa_equipos_mecanicos);
		
		$q->where(new criteria(sqlEQUAL,$c->sa_equipos_mecanicos->id_equipo_mecanico,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
			//cargnado la lista de mepresas//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			if($rec){
			
				$r->assign('id_equipo_mecanico','value',$rec->id_equipo_mecanico);
				$r->assign('nombre_equipo','value',$rec->nombre_equipo);
				$r->assign('descripcion_equipo','value',$rec->descripcion_equipo);				
				$r->assign('tipo_equipo','value',$rec->tipo_equipo);
				$r->assign('id_empresa','value',$rec->id_empresa);
				$r->loadCommands(cargar_jefes($rec->id_empresa,$rec->id_empleado_jefe_taller,$c));
			}
			$r->script('agregar(false);');
			
			$r->script('
				$("#edit_window input").attr("readonly","'.$view[$mode].'");
				$("#edit_window select").attr("disabled","'.$view[$mode].'");
				$("#edit_window #guardar").attr("disabled","'.$view[$mode].'");
				');
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,eliminar)){
				return $r;
			}
			$c->begin();
			$rec=$q->doDelete($c);
			if($rec===true){
				$c->commit();
				$r->script('_alert("Registro eliminado con &eacute;xito");cargar();');
			}else{
				$r->script('_alert("No se puede eliminar el registro, es posible que el mismo ya est&aacute; siendo utilizado en inventario");');
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
		$sa_equipos_mecanicos = new table("sa_equipos_mecanicos");
		
		$sa_equipos_mecanicos->add(new field('id_equipo_mecanico','',field::tInt,$form['id_equipo_mecanico']));
		$sa_equipos_mecanicos->add(new field('id_empresa','',field::tInt,$form['id_empresa']));
		$sa_equipos_mecanicos->add(new field('nombre_equipo','',field::tString,$form['nombre_equipo']));
		$sa_equipos_mecanicos->add(new field('descripcion_equipo','',field::tString,$form['descripcion_equipo']));
		$sa_equipos_mecanicos->add(new field('id_empleado_jefe_taller','',field::tInt,$form['id_empleado_jefe_taller']));
		//$r->alert($form['tipo_equipo']);
		$sa_equipos_mecanicos->insert('tipo_equipo',$form['tipo_equipo'],field::tString);
		$c->begin();
		if($form['id_equipo_mecanico']==''){
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_equipos_mecanicos->doInsert($c,$sa_equipos_mecanicos->id_equipo_mecanico);
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_equipos_mecanicos->doUpdate($c,$sa_equipos_mecanicos->id_equipo_mecanico);
		}
		if($result===true){
			$r->alert('Guardado con exito');
			$r->script('cargar();close_window("edit_window");');
			$c->commit();
			
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
			$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
		}
		
		$c->close();
		return $r;
	}
	
	function cargar_jefes($id_empresa,$default=null,$c=null){
		$r=getResponse();
		if($c==null){
			$c=new connection();
			$c->open();
		}
		
		
		$jefes= $c->pg_v_empleado->doSelect($c,new criteria(sqlAND,array(
			new criteria(sqlEQUAL,$c->pg_v_empleado->id_empresa,$id_empresa),
			new criteria(sqlEQUAL,$c->pg_v_empleado->clave_filtro,6),
			new criteria(sqlEQUAL,$c->pg_v_empleado->activo,1)
		)));
		
		//$join= $jefes->join($c->pg_usuario,$jefes->id_usuario,$c->pg_usuario->id_usuario);//gregor alerta2
		
		if($jefes){
			$select=inputSelect('id_empleado_jefe_taller',$jefes->getAssocPlus('id_empleado',array('cedula','nombre_empleado')),$default);
		}else{
			
		}
		$r->assign('field_id_empleado_jefe_taller',inner,$select);
		return $r;
	}
	
	$c= new connection();
	$c->open();
	
	$empresas=getEmpresaList($c);
	
	
	
	xajaxRegister('guardar');
	xajaxRegister('cargar_empresas');
	xajaxRegister('cargar');
	xajaxRegister('load_page');
	xajaxRegister('cargar_jefes');
		
	xajaxProcess();
	
	includeDoctype();
	
	$tipos_equipos=array('MECANICA'=>'MECANICA','LATONERIA'=>'LATONERIA');
		
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
		                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Equipos de Mecánicos</title>
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
		<script>
		
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
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','busca='+datos.busca + ',id_empresa_enviada='+datos.js_empresa);
				close_window("edit_window");
			}
			
			function agregar(add){
				setDivWindow('edit_window','title_window',true);
				if(add){
					$('#edit_window input,#edit_window').val('');
					$('#edit_window #capa_id_equipo_mecanico').html('');
					$("#edit_window #subtitle").html("Agregar");
					$("#edit_window input").attr("readonly","");
					$("#edit_window select").attr("disabled","");
					$("#edit_window #guardar").attr("disabled","");
					
					var empresa_seleccionada = document.getElementById("lstEmpresa").value;
					
					 document.getElementById("id_empresa").value = empresa_seleccionada;					
					 xajax_cargar_jefes(empresa_seleccionada);
					//xajax_cargar_empresas();
					
				}
				//removiendo las clases que indican error:
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
				datos.js_empresa = '<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>';
				$("#lstEmpresa").val('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>');
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
			
		</script>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Mantenimiento de Servicios</span><br />
			<span class="subtitulo_pagina" >(Equipos de Mec&aacute;nicos)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		<button type="button" value="Nuevo" onClick="agregar(true);" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
		<input type="text" id="busca" onKeyPress="keyEvent(event,buscar);" />
		
        <tag_empresa id="tdlstEmpresa"> </tag_empresa>
		
		<button type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
		<button type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
		
		<hr />
	</div>
	<div id='capaTabla'></div>
	<div align="center" id='paginador'></div>
</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="edit_window" style="visibility:hidden;min-width:200px;">
	<div class="title" id="title_window">
		<span id="subtitle"></span>&nbsp;Equipo de Mec&aacute;nicos	
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onSubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_equipo_mecanico" name="id_equipo_mecanico" />
			<table class="insert_table" style="width:auto;">
				<tbody>
					<tr>
						<td class="label">
							Empresa
						</td>
						<td class="field" id="field_id_empresa">
							<?php //echo 	inputSelect('id_empresa',$empresas,null,array('onchange'=>'xajax_cargar_jefes(this.value);')); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Nombre Equipo
						</td>
						<td class="field" id="field_nombre_equipo">
							<input type="text" name="nombre_equipo" id="nombre_equipo" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Descripcion
						</td>
						<td class="field" id="field_descripcion_equipo">
							<input type="text" name="descripcion_equipo" id="descripcion_equipo" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Tipo
						</td>
						<td class="field" id="field_tipo_equipo">
							<?php echo inputSelect('tipo_equipo',$tipos_equipos); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Jefe de taller:
						</td>
						<td class="field" id="field_id_empleado_jefe_taller">
							
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2" align="right">
							<button type="submit" id="guardar" onClick="xajax_guardar(xajax.getFormValues('formulario'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Guardar</button>
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
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="buscar();"'); //buscador
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="xajax_cargar_jefes(this.value);"',"id_empresa","field_id_empresa");//resto
		cargar();
	</script>
	</body>
</html>

