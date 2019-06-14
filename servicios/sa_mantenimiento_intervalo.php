<?php
@session_start();
define('PAGE_PRIV','sa_mantenimiento_intervalo');//nuevo gregor
//define('PAGE_PRIV','sa_intervalo'); //anterior
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
		
		$sa_v_intervalo_formato = $c->sa_v_intervalo_formato;
		$query = new query($c);
		$query->add($sa_v_intervalo_formato);
		
		if($argumentos['busca']!=''){
			$query->where(new criteria(' like ',$sa_v_intervalo_formato->nombre_empresa,"'%".$argumentos['busca']."%'"));
			
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
		$query->where(new criteria(' like ',$sa_v_intervalo_formato->id_empresa,"'%".$argumentos['id_empresa']."%'"));
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		$semanas=array('Domingo','Lunes','Martes','Mi&eacute;rcoles','Jueves','Viernes','S&aacute;bado');
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				
				<td>'.$paginador->get($sa_v_intervalo_formato->nombre_empresa_sucursal,'Empresa').'</td>
				<td>'.$paginador->get($sa_v_intervalo_formato->fecha_inicio_formato,'Fecha Inicio').'</td>
				<td>'.$paginador->get($sa_v_intervalo_formato->fecha_fin_formato,'Fecha Fin').'</td>
				<td>'.$paginador->get($sa_v_intervalo_formato->hora_inicio_jornada_formato,'Inicio Jornada').'</td>
				<td>'.$paginador->get($sa_v_intervalo_formato->hora_fin_jornada_formato,'Fin Jornada').'</td>
				<td>'.$paginador->get($sa_v_intervalo_formato->reservar_cada_intervalo,'Reservar cada').'</td>
				<td>'.$paginador->get($sa_v_intervalo_formato->intervalo,'Duraci&oacute;n').'</td>
				<td title="Ultimo d&iacute;a laboral">'.$paginador->get($sa_v_intervalo_formato->dias_semana,'UDL').'</td>
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
					<td align="center">'.$rec->fecha_inicio_formato.'</td>
					<td align="center">'.$rec->fecha_fin_formato.'</td>
					<td align="center">'.$rec->hora_inicio_jornada_formato.'</td>
					<td align="center">'.$rec->hora_fin_jornada_formato.'</td>
					<td align="center">'.$rec->reservar_cada_intervalo.' d&iacute;as</td>
					<td align="center">'.$rec->intervalo.' min.</td>
					<td align="center">'.$semanas[$rec->dias_semana].'</td>
					';
					if($rec->fecha_fin_formato==''){
						$html.='
						<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar_intervalo('.$rec->id_intervalo.',\'view\');"></td>
						<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar_intervalo('.$rec->id_intervalo.',\'edit\');"></td>
						';
					}else{
						$html.='<td colspan="3" style="text-align:center;">Cerrado</td>';
					}
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
	
	function cargar_empresas($id_empresa){
		$r=getResponse();
		$c=new connection();
		$c->open();
		//cargnado la lista de mepresas
		$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
		$select_empresa=inputSelect('select_id_empresa',$empresas,$id_empresa);
		$r->assign('capa_id_empresa',inner,$select_empresa);
		$r->assign('id_empresa',inner,$id_empresa);
		$c->close();
		return $r;
	}
	
	function cargar_intervalo($id, $mode='view'){
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
		$q->add($c->sa_v_intervalo_formato);
		
		$q->where(new criteria(sqlEQUAL,$c->sa_v_intervalo_formato->id_intervalo,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
			//cargnado la lista de mepresas
			//$empresas=$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			if($rec){
				$r->assign('fecha_inicio','value',$rec->fecha_inicio_formato);
				//$r->script('obj("fecha_inicio").disabled=true;');
				$r->assign('id_intervalo','value',$id);
				$r->assign('intervalo','value',$rec->intervalo);
				$r->assign('id_empresa','value',$rec->id_empresa);
				$r->assign('reservar_cada_intervalo','value',$rec->reservar_cada_intervalo);
				$r->loadCommands(generar_combos($rec->intervalo));
				$r->assign('hora_inicio_jornada','value',str_time($rec->hora_inicio_jornada));
				$r->assign('hora_fin_jornada','value',str_time($rec->hora_fin_jornada));
				$r->assign('hora_inicio_baja','value',str_time($rec->hora_inicio_baja));
				$r->assign('hora_fin_baja','value',str_time($rec->hora_fin_baja));
				$r->assign('dias_semana','value',$rec->dias_semana);
			}
			$r->script('agregar(false);');
			
			$r->script('
				$("#edit_window input").not("#fecha_inicio").attr("readonly","'.$view[$mode].'");
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
		
		if($form['hora_inicio_jornada']==''){
			$r->script('$("#field_hora_inicio_jornada").addClass("inputNOTNULL");');
			$exit=true;
		}
		if($form['hora_fin_jornada']==''){
			$r->script('$("#field_hora_fin_jornada").addClass("inputNOTNULL");');
			$exit=true;
		}
		if($form['hora_inicio_baja']==''){
			$r->script('$("#field_hora_inicio_baja").addClass("inputNOTNULL");');
			$exit=true;
		}
		if($form['hora_fin_baja']==''){
			$r->script('$("#field_hora_fin_baja").addClass("inputNOTNULL");');
			$exit=true;
		}
		if($exit){
			$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
			return $r;	
		}
		
		//validando los horarios
		$hi=intval($form['hora_inicio_jornada']);
		$hf=intval($form['hora_fin_jornada']);
		$hib=intval($form['hora_inicio_baja']);
		$hfb=intval($form['hora_fin_baja']);
		//$r->alert($hi.' '.$hf);
		if($hi>=$hf){
			$r->alert("La hora final no puede ser menor o igual a la de inicio");
			return $r;
		}
		if($hib>=$hfb){
			$r->alert("La hora final de descanzo no puede ser menor o igual a la de inicio de descanzo");
			return $r;
		}
		if($hib<$hi){
			$r->alert('La hora de inicio descanzo no puede ser antes de la hora de inicio');
			return $r;
		}
		if($hfb>$hf){
			$r->alert('La hora final de descanzo no puede ser despues de la hora de fin');
			return $r;
		}
		
		
		$sa_v_intervalo_formato = new table("sa_intervalo");
		
		$sa_v_intervalo_formato->add(new field('id_intervalo','',field::tInt,$form['id_intervalo']));
		$sa_v_intervalo_formato->add(new field('dias_semana','',field::tInt,$form['dias_semana']));
		$sa_v_intervalo_formato->add(new field('id_empresa','',field::tString,$form['id_empresa']));
		$sa_v_intervalo_formato->add(new field('reservar_cada_intervalo','',field::tInt,$form['reservar_cada_intervalo']));
		$sa_v_intervalo_formato->add(new field('intervalo','',field::tInt,$form['intervalo']));
		$sa_v_intervalo_formato->add(new field('fecha_inicio','',field::tDate,$form['fecha_inicio']));
		$sa_v_intervalo_formato->add(new field('hora_inicio_jornada','',field::tTime,adodb_date(DEFINEDphp_TIME,$hi)));
		$sa_v_intervalo_formato->add(new field('hora_fin_jornada','',field::tTime,adodb_date(DEFINEDphp_TIME,$hf)));
		$sa_v_intervalo_formato->add(new field('hora_inicio_baja','',field::tTime,adodb_date(DEFINEDphp_TIME,$hib)));
		$sa_v_intervalo_formato->add(new field('hora_fin_baja','',field::tTime,adodb_date(DEFINEDphp_TIME,$hfb)));
		$c->begin();
		if($form['id_intervalo']==''){
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				$c->rollback();
				return $r;
			}
			//obteniendo el último intervalo para cerrarlo:		
			$last_interval=getLastInterval($c,$form['id_empresa']);		
			if($last_interval->getNumRows()!=0){
				//comparando si la fecha es menor a la fecha de inicio del ultimo intervalo:
				$make_inicio=defined_str_date($form['fecha_inicio']);
				$make_inicio_last=str_date($last_interval->fecha_inicio);
				if($form['id_intervalo']!=$last_interval->id_intervalo){
					if($make_inicio <= $make_inicio_last){
						$r->alert('No puede establecer otro intervalo antes del inicio del activo');				
					}else{
						$close_last=true;
						$close_date=$make_inicio-86400;//(24*60*60);
					}
				}
			}
			$result=$sa_v_intervalo_formato->doInsert($c,$sa_v_intervalo_formato->id_intervalo);
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
				$c->rollback();
				return $r;
			}
			$result=$sa_v_intervalo_formato->doUpdate($c,$sa_v_intervalo_formato->id_intervalo);
		}
		if($result===true){
			//cerrando el intervalo anterior:
			if($close_last){
				$sa_intervalo_anterior= new table("sa_intervalo");
				
				$sa_intervalo_anterior->add(new field('id_intervalo','',field::tInt,$last_interval->id_intervalo));
				$sa_intervalo_anterior->add(new field('fecha_fin','',field::tDate,adodb_date(DEFINEDphp_DATE,$close_date)));
				$resultlast=$sa_intervalo_anterior->doUpdate($c,$sa_intervalo_anterior->id_intervalo);
				
				if($resultlast!==true){
					$r->alert("sin acceso a intervalo previo");
				}
			}
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
				//$r->alert($ex->getObject()->getName());
			}
			$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
		}
		
		$c->close();
		return $r;
	}
	
	function generar_combos($value){
		$r= getResponse();
		
		//generando el maketime para la construiccion de los combos:
		$intervalo=intval($value);
		if($intervalo<=0){
			return $r;
		}else{
			$marca = adodb_mktime(0,0,0);
			//$r->alert(adodb_date('h:i:s A d m Y',$marca));
			//$r->alert(adodb_date('H i s d m Y',$inicio));
			$acumulado=0;
			do{
				//$r->alert(adodb_date('H i s d m Y',$marca));
				$t=adodb_date('h:i A',$marca);
				$lista[$marca]=$t;
				$marca=adodb_mktime(adodb_date('G',$marca),intval(adodb_date('i',$marca))+$intervalo,0);
				$acumulado+=$intervalo;
			}while($acumulado<1440);
			$r->assign('field_hora_inicio_jornada',inner,inputSelect('hora_inicio_jornada',$lista,null,array('class'=>'lista_horas')));//,"onchange"=>'alert(this.value);'
			$r->assign('field_hora_fin_jornada',inner,inputSelect('hora_fin_jornada',$lista,null,array('class'=>'lista_horas')));
			$r->assign('field_hora_inicio_baja',inner,inputSelect('hora_inicio_baja',$lista,null,array('class'=>'lista_horas')));
			$r->assign('field_hora_fin_baja',inner,inputSelect('hora_fin_baja',$lista,null,array('class'=>'lista_horas')));
		
		}
		
		
		return $r;
	}
	
	function preparar_intervalo($id_empresa){
		$r= getResponse();
		//buscar el últmo intervalo de la empresa:
		$c= new connection();
		$c->open();
		//$interval=getLastInterval($c,$id_empresa);
		$fecha_inicio=adodb_date(DEFINEDphp_DATE);
		/*if($interval->getNumRows()!=0){
		}*/
		$r->assign('fecha_inicio','value',$fecha_inicio);
		//$r->assign('capa_fecha_inicio',inner,$fecha_inicio);
		$c->close();
		return $r;
	}
	
	xajaxRegister("preparar_intervalo");
	xajaxRegister("generar_combos");
	xajaxRegister('guardar');
	xajaxRegister('cargar_empresas');
	xajaxRegister('cargar_intervalo');
	xajaxRegister('load_page');
		
	xajaxProcess();
	
	
	$intervalos = array(
		10=>'10 minutos',
		15=>'15 minutos',
		20=>'20 minutos',
		30=>'30 minutos',
		60=>'1 hora',
		90=>'1 hora y media',
		120=>'2 horas'
	);
	$id_empresa=$_SESSION['idEmpresaUsuarioSysGts'];
	if($id_empresa==""){
		echo "no se ha iniciado la sesi&oacute;n";
		exit;
	}
	$reservar=array(0,1,2,3,4,5,6,7,8,9,10);
	$c= new connection();
	$c->open();
	$empresas=getEmpresaList($c);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	$select_empresa=inputSelect('select_id_empresa',$empresas,$id_empresa,array("onchange"=>"datos.id_empresa=this.value;cargar();"),false);
	$c->close();
	
	$list_dias_semana=array(
		6=>'S&aacute;bado',
		5=>'Viernes',
		4=>'Jueves',
		3=>'Mi&eacute;rcoles',
		2=>'Martes',
		1=>'Lunes'
	);
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
	                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Intervalos</title>
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
			
			.lista_horas option.impar{
				background:#DFDFDF;
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
				id_empresa:'<?php echo $id_empresa; ?>',
				busca:''
			}
			
			function cargar(){
			//alert(datos.id_empresa);
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','busca='+datos.busca+',id_empresa='+datos.id_empresa);
				close_window("edit_window");
			}
			
			function agregar(add){
				setDivWindow('edit_window','title_window',true);
				if(add){
					xajax_preparar_intervalo(datos.id_empresa);
					$('#edit_window input, #edit_window select').val('');
					//alert('f');
					$('#edit_window #capa_id_art_inventario').html('');
					$("#edit_window #subtitle").html("Definir");
					$("#edit_window input").not('#fecha_inicio').attr("readonly","");
					$("#edit_window select").attr("disabled","");
					$("#edit_window #guardar").attr("disabled","");
					obj('id_empresa').value=datos.id_empresa;
					//xajax_cargar_empresas();
					//obj("fecha_inicio").disabled=false;
					
				}
				//removiendo las clases que indican error:
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
			
		</script>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Mantenimiento de Servicios</span><br />
			<span class="subtitulo_pagina" >(Intervalos)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		<button type="button" value="Nuevo" onClick="agregar(true);" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
         
		<label>Intervalos de la Empresa: <empresa id="field_id_empresa"></empresa><?php // echo $select_empresa; ?></label>
		<span style="display:none;"><input type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
		
		
		<button type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
		<button type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button></span>
		
		<hr />
	</div>
	<div id='capaTabla'></div>
	<div align="center" id='paginador'></div>
</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="edit_window" style="visibility:hidden;min-width:200px;">
	<div class="title" id="title_window">
		<span id="subtitle"></span>&nbsp;Intervalo		
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onsubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" name="id_intervalo" id="id_intervalo" />
			<input type="hidden" name="id_empresa" id="id_empresa" />
						
			<table class="insert_table" style="width:auto;">
				<tbody>
					<tr>
						<td class="label">
							Fecha Inicio
						</td>
						<td class="field" id="field_fecha_inicio">
							<input type="text" name="fecha_inicio" id="fecha_inicio" readonly />
							<script type="text/javascript">
								Calendar.setup({
								inputField : "fecha_inicio", // id del campo de texto
								ifFormat : "%d-%m-%Y", // formato de la fecha que se escriba en el campo de texto
								button : "fecha_inicio" // el id del botón que lanzará el calendario
								});
							</script>
						</td>
					</tr>
					<tr>
						<td class="label">
							Duraci&oacute;n Cita
						</td>
						<td class="field" id="field_intervalo">
							<?php setInputSelect('intervalo',$intervalos,'',array('onkeypress'=>'return inputInt(event);','onchange'=>"xajax_generar_combos(this.value);")); ?>
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Reservar cada <em>n</em> citas
						</td>
						<td class="field" id="field_reservar_cada_intervalo">
							<?php setInputSelect('reservar_cada_intervalo',$reservar); ?>							
						</td>
					</tr>
					<tr>
						<td class="label">
							Hora Inicio
						</td>
						<td class="field" id="field_hora_inicio_jornada">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Hora Fin
						</td>
						<td class="field" id="field_hora_fin_jornada">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Hora Inicio Descanso
						</td>
						<td class="field" id="field_hora_inicio_baja">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Hora Fin Descanso
						</td>
						<td class="field" id="field_hora_fin_baja">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							UDL (&Uacute;ltimo d&iacute;a laboral):
						</td>
						<td class="field" id="field_dias_semana">
							<?php echo inputSelect('dias_semana',$list_dias_semana); ?>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2">
							<div style="width:300px;">Nota: S&oacute;lo pueden modificarse los horarios a aquellos intervalos que no tengan citas registradas.</div>
						</td>
					</tr>
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
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','onchange="datos.id_empresa=this.value;cargar();"',"select_id_empresa","field_id_empresa","");
		cargar();
	</script>
	</body>
</html>

