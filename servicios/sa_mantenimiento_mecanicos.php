<?php
@session_start();
define('PAGE_PRIV','sa_mantenimiento_mecanicos');//nuevo gregor
//define('PAGE_PRIV','sa_mecanico');//anterior
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
		
		$sa_mecanicos = $c->sa_v_mecanicos;
		$query = new query($c);
		$query->add($sa_mecanicos);
		
		if($argumentos['busca']!=''){$query->where(new criteria(sqlOR,
			array(
				new criteria(' like ',$sa_mecanicos->nombre_empleado,"'%".$argumentos['busca']."%'"),
				new criteria(' like ',$sa_mecanicos->apellido,"'%".$argumentos['busca']."%'"),
				new criteria(' like ',$sa_mecanicos->nombre_empresa_sucursal,"'%".$argumentos['busca']."%'")
				)
			));
			
			//busqueda
			//$query->where(new criteria(sqlEQUAL, $sa_mecanicos->id_empresa,$argumentos['id_empresa_enviada']));
			
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
		
		//normal
		$query->where(new criteria(sqlEQUAL, $sa_mecanicos->id_empresa_departamento,$argumentos['id_empresa_enviada']));
		$query->where(new criteria(sqlEQUAL, $sa_mecanicos->clave_filtro,501));//501 solo mecanicos

		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				<td>'.$paginador->get($sa_mecanicos->id_mecanico,'Nro Mecanico').'</td>
				<td>'.$paginador->get($sa_mecanicos->nombre_empresa_sucursal,'Empresa').'</td>
				<td>'.$paginador->get($sa_mecanicos->apellido,'Apellido').'</td>
				<td>'.$paginador->get($sa_mecanicos->nombre_empleado,'Nombre').'</td>
				<td>'.$paginador->get($sa_mecanicos->nombre_cargo,'Cargo').'</td>
				<td>'.$paginador->get($sa_mecanicos->nivel,'Nivel').'</td>
				<td>'.$paginador->get($sa_mecanicos->nombre_equipo,'Equipo').'</td>
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
					<td align="center">'.sprintf("%04s",$rec->id_mecanico).'</td>
					<td align="center">'.$rec->nombre_empresa_sucursal.'</td>
					<td align="center">'.$rec->apellido.'</td>
					<td align="center">'.$rec->nombre_empleado.'</td>
					<td align="center">'.$rec->nombre_cargo.'</td>
					<td align="center">'.$rec->nivel.'</td>
					<td align="center">'.$rec->nombre_equipo.'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_empleado.',\'view\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_empleado.',\'edit\');"></td>
					
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
		$q->add($c->sa_v_mecanicos);
		
		$q->where(new criteria(sqlEQUAL,$c->sa_v_mecanicos->id_empleado,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
			//cargnado la lista de mepresas
			//$empresas=getEmpresaList($c);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			
			
			if($rec){
				$r->assign('id_mecanico','value',ifnull($rec->id_mecanico));
				$r->assign('id_empleado','value',$rec->id_empleado);
				$r->assign('nivel','value',$rec->nivel);
				$r->assign('precio_ut','value',_formato($rec->precio_ut));
				$r->assign('capa_empleado',inner,$rec->apellido.' '.$rec->nombre_empleado);
				$r->assign('capa_cargo',inner,$rec->nombre_cargo);
				$r->assign('capa_empresa',inner,$rec->nombre_empresa_sucursal);
				$r->assign('ut_planta','value',$rec->ut_planta);
				$r->assign('ut_concesionario','value',$rec->ut_concesionario);
				//cargando lalista de equipos:
				$equipos=$c->sa_equipos_mecanicos->doSelect($c,new criteria(sqlEQUAL,$c->sa_equipos_mecanicos->id_empresa,$rec->id_empresa))->getAssoc('id_equipo_mecanico','nombre_equipo');
				$r->assign('field_id_equipo_mecanico',inner,inputSelect('id_equipo_mecanico',$equipos,$rec->id_equipo_mecanico));
				if($rec->comision_activa==1){
					$r->script("obj('comision_activa').checked=true;");
				}else{
					$r->script("obj('comision_activa').checked=false;");					
				}
				$r->assign('porcentaje_comision','value',_formato($rec->porcentaje_comision,6));
			}
			$r->script('agregar(false);');
			
			$r->script('
				$("#edit_window input").attr("readonly","'.$view[$mode].'");
				$("#edit_window select, #edit_window input[type=checkbox]").attr("disabled","'.$view[$mode].'");
				$("#edit_window #guardar, #edit_window .date input").attr("disabled","'.$view[$mode].'");
				');
		}else{
			/*$c->begin();
			$rec=$c->sa_mecanicos->doDelete($c,new criteria(sqlEQUAL,$c->sa_mecanicos->id_mecanico,$id));
			if($rec===true){
				$c->commit();
				$r->script('_alert("Registro eliminado con &eacute;xito");cargar();');
			}else{
				$r->script('_alert("No se puede eliminar el registro, es posible que el mismo ya est&aacute; siendo utilizado");');
			}*/
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
		$sa_mecanicos = new table("sa_mecanicos");
		
		$sa_mecanicos->add(new field('id_mecanico','',field::tInt,$form['id_mecanico']));
		$sa_mecanicos->add(new field('id_empleado','',field::tInt,$form['id_empleado']));
		$sa_mecanicos->add(new field('id_equipo_mecanico','',field::tInt,$form['id_equipo_mecanico']));
		$sa_mecanicos->add(new field('nivel','',field::tString,$form['nivel']));
		$sa_mecanicos->add(new field('precio_ut','',field::tFloat,$form['precio_ut']));
		$sa_mecanicos->insert('ut_concesionario',$form['ut_concesionario'],field::tInt);
		$sa_mecanicos->insert('ut_planta',$form['ut_planta'],field::tInt);
		$sa_mecanicos->insert('porcentaje_comision',$form['porcentaje_comision'],field::tFloat);
		$sa_mecanicos->insert('comision_activa',$form['comision_activa'],field::tBool);
		$c->begin();
		//$r->alert($form['id_mecanico']);
		if($form['id_mecanico']==''){
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_mecanicos->doInsert($c,$sa_mecanicos->id_mecanico);
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_mecanicos->doUpdate($c,$sa_mecanicos->id_mecanico);
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
						$r->alert($ex->getMessage());
					}*/
				}
				
						//$r->alert($ex->getObject()->getName());
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
	//$tipos=$c->sa_tipo_puestos->doSelect($c)->getAssoc('id_tipo_puesto','nombre_tipo_puesto');
	$niveles=array(
	'NORMAL'=>'NORMAL',
	'EXPERTO'=>'EXPERTO',
	'PRINCIPIANTE'=>'PRINCIPIANTE'
	);
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
		                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Mecánicos</title>
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
					$('#edit_window input').not('input[type=checkbox]').val('');
					//$('#edit_window #capa_id_mecanico').html('');
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
			<span class="subtitulo_pagina" >(Mec&aacute;nicos)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		
		<input type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
		
		<tag_empresa id="tdlstEmpresa"> </tag_empresa>
        
		<button type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
		<button type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
		
		<hr />
	</div>
	<div id='capaTabla'></div>
	<div align="center" id='paginador'></div>
	<hr />
	<div style="text-align:center;">Los Mec&aacute;nicos son alimentados desde los par&aacute;metros principales (Tabla Empleados) al especificar un cargo como "MECANICO SERVICIOS"</div>
	<hr />
</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="edit_window" style="visibility:hidden;min-width:400px;max-width:400px;">
	<div class="title" id="title_window">
		<span id="subtitle"></span>&nbsp;Mec&aacute;nicos	
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onsubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_mecanico" name="id_mecanico" />
			<input type="hidden" id="id_empleado" name="id_empleado" />
			<table class="insert_table" style="width:auto;">
				<tbody>
					<tr>
						<td class="label">
							Empresa
						</td>
						<td class="field" id="capa_empresa">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Empleado
						</td>
						<td class="field" id="capa_empleado">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Cargo
						</td>
						<td class="field" id="capa_cargo">
							
						</td>
					</tr>					
					<tr>
						<td class="label">
							Nivel
						</td>
						<td class="field" id="field_nivel">
							
							<?php echo inputSelect('nivel',$niveles); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Precio por UT
						</td>
						<td class="field" id="field_precio_ut">
							<input type="text" name="precio_ut" id="precio_ut" onkeypress="return inputFloat(event);" onchange="set_toNumber(this);" />
						</td>
					</tr>
					<tr>
						<td class="label">
							UT Planta
						</td>
						<td class="field" id="field_ut_planta">
							<input type="text" name="ut_planta" id="ut_planta" onkeypress="return inputInt(event);" onchange="set_toNumber(this,0);" />
						</td>
					</tr>
					<tr>
						<td class="label">
							UT Concesionaria
						</td>
						<td class="field" id="field_ut_concesionario">
							<input type="text" name="ut_concesionario" id="ut_concesionario" onkeypress="return inputInt(event);" onchange="set_toNumber(this,0);" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Cobra Comisi&oacute;n
						</td>
						<td class="field" id="field_comision_activa">
							<input type="checkbox" value="1" name="comision_activa" id="comision_activa" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Porcentaje Comisi&oacute;n
						</td>
						<td class="field" id="field_porcentaje_comision">
							<input type="text" name="porcentaje_comision" id="porcentaje_comision" onkeypress="return inputFloat(event);" onchange="set_toNumber(this,6);" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Equipo de Mec&aacute;nicos
						</td>
						<td class="field" id="field_id_equipo_mecanico">
							
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2" align="right">
							<button type="submit" id="guardar" onclick="xajax_guardar(xajax.getFormValues('formulario'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Guardar</button>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							Los Mec&aacute;nicos son alimentados desde los par&aacute;metros principales
 (Tabla Empleados) al especificar un cargo como "MECANICO SERVICIOS"
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
		cargar();
	</script>
	</body>
</html>

