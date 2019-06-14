<?php
@session_start();
define('PAGE_PRIV','sa_mantenimiento_puestos');//nuevo gregor
//define('PAGE_PRIV','sa_puesto');//anterior
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
		
		$sa_puestos = $c->sa_v_puestos;
		$query = new query($c);
		$query->add($sa_puestos);
		
		if($argumentos['busca']!=''){
			//$query->where(new criteria(' like ',$sa_puestos->codigo_puesto,"'%".$argumentos['busca']."%'"));
			
			$query->where(new criteria(sqlOR, new criteria(' like ',$sa_puestos->descripcion_puesto,"'%".$argumentos['busca']."%'"), new criteria(' like ',$sa_puestos->codigo_puesto,"'%".$argumentos['busca']."%'")));
			//busqueda
			//$query->where(new criteria(sqlEQUAL, $sa_puestos->id_empresa,$argumentos['id_empresa_enviada']));
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
		
		//normal
		$query->where(new criteria(sqlEQUAL, $sa_puestos->id_empresa,$argumentos['id_empresa_enviada']));
	
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($sa_puestos->nombre_empresa_sucursal,'Empresa').'</td>
				<td>'.$paginador->get($sa_puestos->codigo_puesto,'C&oacute;digo').'</td>
				<td>'.$paginador->get($sa_puestos->fecha_creacion,'Fecha').'</td>
				<td>'.$paginador->get($sa_puestos->capacidad,'Capacidad(autos).').'</td>
				<td>'.$paginador->get($sa_puestos->nombre_tipo_puesto,'Tipo').'</td>
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
					<td align="center">'.$rec->codigo_puesto.'</td>
					<td align="center">'.$rec->fecha_creacion_formato.'</td>
					<td align="center">'.$rec->capacidad.'</td>
					<td align="center">'.$rec->nombre_tipo_puesto.'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_puesto.',\'view\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_puesto.',\'edit\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/delete.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar('.$rec->id_puesto.',\'delete\');""></td>
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
		$q->add($c->sa_v_puestos);
		
		$q->where(new criteria(sqlEQUAL,$c->sa_v_puestos->id_puesto,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
			//cargnado la lista de mepresas
			//$empresas=getEmpresaList($c);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			if($rec){
				$r->assign('id_puesto','value',$rec->id_puesto);
				$r->assign('descripcion_puesto','value',$rec->descripcion_puesto);
				$r->assign('direccion','value',$rec->direccion);
				$r->assign('codigo_puesto','value',$rec->codigo_puesto);
				$r->assign('capacidad','value',$rec->capacidad);
				$r->assign('fecha_creacion','value',$rec->fecha_creacion_formato);
				$r->assign('id_tipo','value',$rec->id_tipo);
				$r->assign('id_empresa','value',$rec->id_empresa);
				
				if($rec->activo==1){
					$r->script('obj("activo").checked=true;');
				}else{
					$r->script('obj("activo").checked=false;');
				}
				//$select_empresa=inputSelect('id_empresa',$empresas,$rec->id_empresa);
				//$r->assign('field_id_empresa',inner,$select_empresa);
			}
			$r->script('agregar(false);');
			
			$r->script('
				$("#edit_window input").not(".dater").attr("readonly","'.$view[$mode].'");
				$("#edit_window select, #edit_window input[type=checkbox]").attr("disabled","'.$view[$mode].'");
				$("#edit_window #guardar, #edit_window .date input").attr("disabled","'.$view[$mode].'");
				');
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,eliminar)){
				return $r;
			}
			$c->begin();
			$rec=$c->sa_puestos->doDelete($c,new criteria(sqlEQUAL,$c->sa_puestos->id_puesto,$id));
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
		$sa_puestos = new table("sa_puestos");
		
		$sa_puestos->add(new field('id_puesto','',field::tInt,$form['id_puesto']));
		$sa_puestos->add(new field('id_empresa','',field::tInt,$form['id_empresa']));
		$sa_puestos->add(new field('descripcion_puesto','',field::tString,$form['descripcion_puesto']));
		$sa_puestos->add(new field('capacidad','',field::tInt,$form['capacidad']));
		$sa_puestos->add(new field('codigo_puesto','',field::tString,$form['codigo_puesto']));
		$sa_puestos->add(new field('fecha_creacion','',field::tDate,$form['fecha_creacion']));
		$sa_puestos->add(new field('activo','',field::tBool,$form['activo']));
		$sa_puestos->add(new field('id_tipo','',field::tInt,$form['id_tipo']));
		$sa_puestos->add(new field('direccion','',field::tString,$form['direccion']));
		$c->begin();
		if($form['id_puesto']==''){
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_puestos->doInsert($c,$sa_puestos->id_puesto);
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_puestos->doUpdate($c,$sa_puestos->id_puesto);
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
	xajaxRegister('cargar_empresas');
	xajaxRegister('cargar');
	xajaxRegister('load_page');
		
	xajaxProcess();
	
	$c = new connection();
	$c->open();
	$empresas=getEmpresaList($c);
	$tipos=$c->sa_tipo_puestos->doSelect($c)->getAssoc('id_tipo_puesto','nombre_tipo_puesto');
	//var_dump($tipos);
	//$c->close();
	
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
	
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Puestos de Taller</title>
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
					document.getElementById('id_empresa').value = document.getElementById('lstEmpresa').value;
					$('#edit_window input').not('input[type=checkbox]').val('');
					//$('#edit_window #capa_id_puesto').html('');
					$("#edit_window #subtitle").html("Agregar");
					$("#edit_window input").attr("readonly","");
					$("#edit_window select,#edit_window input[type=checkbox]").attr("disabled","");
					$("#edit_window #guardar").attr("disabled","");
					$("#edit_window .date input").attr("disabled","");
					//$('#edit_window select').val('');
					//xajax_cargar_empresas();
					
				}
				$("#edit_window .date input").attr("readonly","true");
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
				datos.js_empresa = '<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>';
				$("#lstEmpresa").val('<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>');
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
			<span class="subtitulo_pagina" >(Puestos de Taller)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		<button type="button" value="Nuevo" onClick="agregar(true);" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
		<input type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
		
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
		<span id="subtitle"></span>&nbsp;Puesto de taller		
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onsubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_puesto" name="id_puesto" />
			<table class="insert_table" style="width:auto;">
				<tbody>
					<tr>
						<td class="label">
							Empresa
						</td>
						<td class="field" id="field_id_empresa">
							<?php //echo inputSelect('id_empresa',$empresas,null); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							C&oacute;digo
						</td>
						<td class="field" id="field_codigo_puesto">
							<input type="text" name="codigo_puesto" id="codigo_puesto" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Descripci&oacute;n
						</td>
						<td class="field" id="field_descripcion_puesto">
							<input type="text" name="descripcion_puesto" id="descripcion_puesto" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Fecha
						</td>
						<td class="field" id="field_fecha_creacion">
							<span class="date">
							<input class="dater" type="text" name="fecha_creacion" id="fecha_creacion" />
							<script type="text/javascript">
								Calendar.setup({
								inputField : "fecha_creacion", // id del campo de texto
								ifFormat : "%d-%m-%Y", // formato de la fecha que se escriba en el campo de texto
								button : "fecha_creacion" // el id del botón que lanzará el calendario
								});
							</script></span>
						</td>
					</tr>
					<tr>
						<label for="activo">
							<td class="label">
								Activo
							</td>
							<td class="field" id="field_activo">
								<input type="checkbox" name="activo" id="activo" value="1" />
							</td>
						</label>
					</tr>
					<tr>
						<td class="label">
							Tipo
						</td>
						<td class="field" id="field_id_tipo">
							
							<?php echo inputSelect('id_tipo',$tipos); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Direcci&oacute;n
						</td>
						<td class="field" id="field_direccion">
							<input type="text" name="direccion" id="direccion" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Capacidad
						</td>
						<td class="field" id="field_capacidad">
							<input type="text" name="capacidad" id="capacidad" onkeypress="return inputInt(event);" />
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
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','onchange="buscar();"');//buscador
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','',"id_empresa","field_id_empresa");//resto
		cargar();
	</script>
	</body>
</html>

