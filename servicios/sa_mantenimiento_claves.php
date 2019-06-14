<?php
@session_start();
define('PAGE_PRIV','sa_mantenimiento_claves');//nuevo gregor
//define('PAGE_PRIV','sa_claves');//anterior
require_once("../inc_sesion.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	
	include("../connections/conex.php");//usa el vldtipodato que necesita ac_iv_general
	include("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal
	
		$c= new connection();
	$c->open();
	
	$ret=getEmpresaList($c);
		//cargnado la lista de mepresas
		//$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	$select_empresa=inputSelect('id_empresa',$ret,null,array('onchange'=>"xajax_cargar_empleados(this.value);"));
	
	/*$modulos=array(
		'mp_jt'=>'Magnetoplano Jefe de Taller',
		'mp_as'=>'Magnetoplano Asesor Servicio'
	);*/
	$vmodulos=$c->pg_claves_modulos->doSelect($c, new criteria(sqlEQUAL, 'id_modulo', 1))->getAssoc('modulo','descripcion');
	asort($vmodulos);
	
	function load_page($page,$maxrows,$order,$ordertype,$capa,$args=''){
		global $vmodulos;
		
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
		
		$cquery = $c->sa_claves;
		$cempresas = $c->sa_v_empresa_sucursal->join($cquery,$c->sa_v_empresa_sucursal->id_empresa,$cquery->id_empresa);
		$cemp= $c->pg_v_empleado->join($cempresas,$cquery->id_empleado,$c->pg_v_empleado->id_empleado);
		$query = new query($c);
		$query->add($cemp);
		
		if($argumentos['busca']!=''){$query->where(new criteria(sqlOR,
			array(
				new criteria(' like ',$cquery->descripcion,"'%".$argumentos['busca']."%'"),
				new criteria(' like ',$c->pg_v_empleado->nombre_empleado,"'%".$argumentos['busca']."%'"),
				new criteria(' like ',$c->sa_v_empresa_sucursal->nombre_empresa_sucursal,"'%".$argumentos['busca']."%'")
				
				//new criteria(' like ',$cemp->nombre_empresa_sucursal,"'%".$argumentos['busca']."%'")
				)
			));
			
			//$query->where(new criteria(' like ',$cquery->descripcion,"'%".$argumentos['busca']."%'"));//original
			
				//busqueda
			$query->where(new criteria(sqlEQUAL, $cquery->id_empresa,$argumentos['id_empresa_enviada']));
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
		
		//normal
		$query->where(new criteria(sqlEQUAL, $cquery->id_empresa,$argumentos['id_empresa_enviada']));
	
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get('id_empresa','Empresa').'</td>
				<td>'.$paginador->get('nombre_empleado','Empleado').'</td>
				<td>'.$paginador->get('modulo','Acci&oacute;n').'</td>
				<td>'.$paginador->get('descripcion','Descripci&oacute;n').'</td>
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
					<td align="center">'.$vmodulos[$rec->modulo].'</td>
					<td align="center">'.$rec->descripcion.'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar_articulo('.$rec->id_clave.',\'view\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar_articulo('.$rec->id_clave.',\'edit\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/delete.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar_articulo('.$rec->id_clave.',\'delete\');""></td>
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
		$ret=getEmpresaList($c);
		//cargnado la lista de mepresas
		//$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
		$select_empresa=inputSelect('id_empresa',$ret,null,array('onchange'=>'alert("s");'));
		$r->assign('field_id_empresa',inner,$select_empresa);
		$c->close();
		return $r;
	}
	
	function cargar_articulo($id, $mode='view'){
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
		$q->add($c->sa_claves);
		
		$q->where(new criteria(sqlEQUAL,$c->sa_claves->id_clave,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
			//cargnado la lista de mepresas
			//$empresas=getEmpresaList($c);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			if($rec){
				/*$select_empresa=inputSelect('id_empresa',$empresas,$rec->id_empresa);
				$r->assign('field_id_empresa',inner,$select_empresa);*/
				$r->assign('id_empresa','value',$rec->id_empresa);
				$r->loadCommands(cargar_empleados($rec->id_empresa,$rec->id_empleado,$c));
				//var_dump($rec->id_empresa." - ".$rec->id_empleado);
				
				//$r->assign('capa_id_art_inventario',inner,$rec->id_art_inventario);
				$r->assign('id_clave','value',$id);
				$r->assign('descripcion','value',$rec->descripcion);
				$r->assign('modulo','value',$rec->modulo);
				$r->assign('clave','value',$rec->clave);
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
		if($form['clave']==''){
			$r->alert('Ingrese la clave');
			return $r;
		}
		//removiendo las clases que indican error:
		$r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');
		$c= new connection();
		$c->open();
		$save = new table("sa_claves");
		$save->insert('id_clave',$form['id_clave']);
		$save->insert('descripcion',$form['descripcion'],field::tString, false);
		$save->insert('id_empresa',$form['id_empresa']);
		$save->insert('id_empleado',$form['id_empleado']);
		$save->insert('modulo',$form['modulo'],field::tString);
		$c->begin();
		if($form['id_clave']==''){
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				$c->rollback();
				return $r;
			}
			$save->insert('clave',md5($form['clave']),field::tString);
			$result=$save->doInsert($c,$save->id_clave);
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
				$c->rollback();
				return $r;
			}
			$recmd5= $c->sa_claves->doSelect($c,new criteria(sqlEQUAL,'id_clave',$form['id_clave']))->clave;
			if($clave!=$form['clave']){
				$save->insert('clave',md5($form['clave']),field::tString);
			}
			$result=$save->doUpdate($c,$save->id_clave);
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
						$r->alert('La Clave ya existe');
						return $r;
					}/*else{
						$r->alert($ex);
					}*/
				}
				//$r->alert($ex->getobject()->getName());
			}
			$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
		}
		
		$c->close();
		return $r;
	}
	
	function cargar_empleados($id_empresa,$id_empleado=null,$c=null){
		$r= getResponse();
		if($c==null){
			$c=new connection();
			$c->open();
		}
		$rece=$c->pg_v_empleado->doQuery($c,new criteria(sqlEQUAL,'id_empresa',$id_empresa))->orderBy('nombre_empleado')->doSelect()->getAssoc('id_empleado','cedula_nombre_empleado');
		
				//TODOS LOS EMPLEADOS QUE TENGAN PERMISO A DICHA EMPRESA GREGOR
		$arrayEmpleados;
		$queryEmpleados = sprintf("SELECT pg_v_empleado.id_empleado, cedula_nombre_empleado, CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) as nombre_completo
						   FROM pg_v_empleado 
						   LEFT JOIN pg_empleado ON pg_v_empleado.id_empleado = pg_empleado.id_empleado
						   LEFT JOIN pg_usuario ON pg_v_empleado.id_empleado = pg_usuario.id_empleado
						   LEFT JOIN pg_usuario_empresa ON pg_usuario.id_usuario = pg_usuario_empresa.id_usuario
						   WHERE pg_usuario_empresa.id_empresa = %s ORDER BY nombre_completo ASC
							", valTpDato($id_empresa,"int"));
		$busquedaEmpleados = mysql_query($queryEmpleados);
		if(!$busquedaEmpleados){ return $r->alert("No se pudo buscar el empleado por empresa \n Error: ".mysql_error()." \n Linea: ".__LINE__);}
		
		while($row = mysql_fetch_array($busquedaEmpleados)){
			$arrayEmpleados[$row["id_empleado"]] = utf8_encode($row["nombre_completo"]);
		}
		
		$rece = $arrayEmpleados;

		
		//$busquedaEmpleado = "SELECT id_empleado, CONCAT_WS(' ', apellido, nombre_empleado) as nombre_completo FROM pg_empleado LEFT JOIN  WHERE id_empleado = ".$_SESSION.""; 
		
		$r->assign('field_id_empleado',inner,inputSelect('id_empleado',$rece,$id_empleado));
		return $r;
	}
	
	
	
	xajaxRegister('guardar');
//	xajaxRegister('cargar_empresas');
	xajaxRegister('cargar_articulo');
	xajaxRegister('load_page');
	xajaxRegister('cargar_empleados');
		
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
	                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Claves de Servicios</title>
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
				
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','busca='+datos.busca + ',id_empresa_enviada='+datos.js_empresa);
				close_window("edit_window");
			}
			
			function agregar(add){
				setDivWindow('edit_window','title_window',true);
				if(add){
					$('#edit_window input, #edit_window').val('');
				
					$("#edit_window #subtitle").html("Agregar");
					$("#edit_window input").attr("readonly","");
					$("#edit_window select").attr("disabled","");
					$("#edit_window #guardar").attr("disabled","");
					
					//$("#edit_window select").val('<?php //echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>');
					
					var empresa_seleccionada = document.getElementById("lstEmpresa").value;
					
					document.getElementById("id_empresa").value = empresa_seleccionada;	
					xajax_cargar_empleados(empresa_seleccionada);
					
					//xajax_cargar_empresas();
					obj('field_id_empleado').innerHTML='&nbsp;';
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
			<span class="subtitulo_pagina" >(Claves de Servicios)</span></td>
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
		<span id="subtitle"></span>&nbsp;Clave de servicio		
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onSubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_clave" name="id_clave" />
			<table class="insert_table" style="width:auto;">
				<tbody>
					<tr>
						<td class="label">
							Empresa
						</td>
						<td class="field" id="field_id_empresa">
							<?php //echo $select_empresa."AQUIIIIIIIIIIIIIIIIIi"; ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Empleado
						</td>
						<td class="field" id="field_id_empleado">
						</td>
					</tr>
					<tr>
						<td class="label">
							Descripci&oacute;n:
						</td>
						<td class="field" id="field_descripcion">
							<input type="text" name="descripcion" id="descripcion" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Acci&oacute;n
						</td>
						<td class="field" id="field_modulo">
							<?php echo inputSelect('modulo',$vmodulos); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Clave (md5):
						</td>
						<td class="field" id="field_clave">
							<input type="password" name="clave" id="clave" />
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

	<script>
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="buscar();"'); //buscador
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="xajax_cargar_empleados(this.value);"',"id_empresa","field_id_empresa");//resto
	
		cargar();
		
	</script>
	
	</body>
</html>

