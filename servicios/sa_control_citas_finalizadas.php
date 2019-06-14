<?php
/*����
ALTER TABLE `sa_cita` ADD `fecha_llamada_fin` DATETIME  NULL ,
ADD `estado_llamada` INT NULL DEFAULT '0',
ADD `n_respuesta` INT NULL ;

CREATE TABLE IF NOT EXISTS `sa_respuestas_cliente` (
  `id_respuesta_cliente` int(11) NOT NULL auto_increment,
  `n_respuesta` int(11) NOT NULL,
  `respuesta` varchar(30) NOT NULL,
  `id_empresa` int(11) NOT NULL,
  PRIMARY KEY  (`id_respuesta_cliente`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;


*/
@session_start();
define('PAGE_PRIV','sa_control_citas_finalizadas');
require_once("../inc_sesion.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	
	include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
	function listar_citas($page,$maxrows,$order,$ordertype,$capa,$args=''){
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
				//todo el dia
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
		
		$sa_v_citas_fin=$c->sa_v_citas_fin;
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
		
	
			
		if($argumentos['filtro_cliente']!='null'){
			$q->where(new criteria(sqlEQUAL,$sa_v_citas_fin->id_cliente_contacto,"'".$argumentos['filtro_cliente']."'"));
			$rq=$c->cj_cc_cliente->doSelect($c,new criteria(sqlEQUAL,$c->cj_cc_cliente->id,$argumentos['filtro_cliente']));
			$cliente=$rq->lci.'-'.$rq->ci.', '.$rq->apellido.' '.$rq->nombre;
			$filtros.='<span style="color:red;" class="filter" title="Eliminar filtro" onclick="cita_date.filtro_cliente=null;r_dialogo_citas();">Filtrado por Cliente: '.$cliente.'</span>';
		}
		if($argumentos['origen_cita']!='null'){
			$q->where(new criteria(sqlEQUAL,$sa_v_citas_fin->origen_cita,"'".$argumentos['origen_cita']."'"));
			$filtros.='<span class="filter" title="Eliminar filtro" onclick="cita_date.origen_cita=null;r_dialogo_citas();">Filtrado por Origen: '.$argumentos['origen_cita'].'</span>';
		}
		if($argumentos['estado_cita']!='null'){
		
			$q->where(new criteria(sqlEQUAL,$sa_v_citas_fin->estado_cita,"'".$argumentos['estado_cita']."'"));
			$filtros.='<span class="filter" title="Eliminar filtro" onclick="cita_date.estado_cita=null;r_dialogo_citas();obj(\'filtro_estados\').value=\'null\';">Filtrado por Estado: '.$argumentos['estado_cita'].'</span>';
		}
		$q->where(new criteria(' IS NOT ',$sa_v_citas_fin->tiempo_llegada_cliente,sqlNULL));
		$paginator = new paginator('xajax_listar_citas',$capa,$q,$maxrows);
		
		$rec=$paginator->run($page,$order,$ordertype,$args);
		//$r->alert($q->getSelect());
		$origen=array('PROGRAMADA'=>getUrl('img/iconos/cita_programada.png'),'ENTRADA'=>getUrl('img/iconos/cita_entrada.png'));
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han encontrado registros</div>';
			}else{
			//<td style="text-align:center;">'.$paginator->get($sa_v_citas_fin->numero_entrada,'N Llegada').'</td>
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
			
				<td style="text-align:center;">'.$paginator->get($sa_v_citas_fin->numero_cita,'N').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->placa,'Placa').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->cedula_cliente,'CI/RIF').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->nombre,'Nombre').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->apellido,'Apellido').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->asesor,'Asesor').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->fecha_factura,'Fecha Factura').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->fecha_llamada_fin,'Fecha llamada').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->estado_llamada,'Estado llamada').'</td>
				<td>'.$paginator->get($sa_v_citas_fin->n_respuesta,'Nivel ').'</td>
				
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
				$resp=$c->sa_respuestas_cliente->doSelect($c/* , new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts'])*/)->getAssoc('n_respuesta','respuesta');
                                
				foreach($rec as $v){
					//evaluando
					if($rec->fecha_llamada_fin==null){
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
					}
				//<td style="text-align:center;color:#FF0000;font-weight:bold;">'.$rec->numero_entrada.'</td>
					$html.='<tr class="'.$class.'" >
					
					<td style="text-align:center;">'.$rec->numero_cita.'</td>
					<td>'.$rec->placa.'</td>
					<td>'.$rec->cedula_cliente.'</td>
					<td>'.$rec->nombre.'</td>
					<td>'.$rec->apellido.'</td>
					<td>'.$rec->asesor.'</td>
					<td>'.parseDateToSql($rec->fecha_factura).'</td>
					<td nowrap="nowrap">'.$fecha_llamada.'</td>
					<td nowrap="nowrap" style="text-align:center;background:'.$color_estado[$estado_llamada].'">'.$texto_estado[$estado_llamada].'</td>
					<td nowrap="nowrap" style="text-align:center;">'.$respuesta.'</td>
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
	
	$table = new table('sa_cita');
	$table->insert('id_cita',$form['id_cita']);
	$table->insert('fecha_llamada_fin','NOW()',field::tFunction);
	$estado=$form['estado_llamada'];
	if($estado==0){
	  $estado=1;
	}
	$table->insert('estado_llamada',$estado);
	$table->insert('n_respuesta',$form['n_respuesta']);
	//$r->alert($table->getUpdate($c,$table->id_cita));
	$result=$table->doUpdate($c,$table->id_cita);
	if($result===true){
	  $c->commit();
      $r->script('
	    _alert("Llamada registrada con &Eacute;xito");
		close_window("cuadro_llamadas");
		r_dialogo_citas();
	  ');
	}else{
		$c->rollback();
		$r->alert('error');
	}
	return $r;
  }

	//xajaxRegister('registrar');

	xajaxRegister('listar_citas');
	//xajaxRegister('cargar_cita');
	xajaxRegister('cargar_cliente_pago');
	xajaxRegister('cargar_datos_cliente');
	xajaxRegister('guardar');
	
	xajaxProcess();
	
	$c= new connection();
	$c->open();
	$resp=$c->sa_respuestas_cliente->doQuery($c/*, new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts'])*/)->OrderBy('n_respuesta')->doSelect()->getAssoc('n_respuesta','respuesta');
	$input_nivel=inputSelect('n_respuesta',$resp,0,null,false);
	/*$tipos_orden=$c->sa_tipo_orden->doSelect($c)->getAssoc('id_tipo_orden','descripcion_tipo_orden');
	$prioridades=array(
		1=>'ALTA',
		2=>'MEDIA',
		3=>'BAJA'
	);*/
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
                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Atenci&oacute;n Cliente</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
		<script>
			detectEditWindows({cuadro_llamadas:'guardar'});
			var cita_date = {
				fecha: '<?php $idate=adodb_date(DEFINEDphp_DATE,adodb_mktime(0,0,0));echo $idate; ?>',
				fecha_rank:2,
				date:new Date(),
				page:0,
				maxrows:10,
				order:'sa_v_citas_fin.hora_inicio_cita',//'sa_v_citas_fin.numero_entrada',
				ordertype:null,
				estado_cita:null,
				origen_cita:null,
				filtro_cliente:null
			}
			function r_dialogo_citas(){
				xajax_listar_citas(cita_date.page,cita_date.maxrows,cita_date.order,cita_date.ordertype,'lista_citas','fecha_cita='+cita_date.fecha+',origen_cita='+cita_date.origen_cita+',estado_cita='+cita_date.estado_cita+',filtro_cliente='+cita_date.filtro_cliente+',fecha_rank='+cita_date.fecha_rank);
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
					c2.$.innerHTML='<input type="text" style="width:99%" name="descripcion_falla[]" id="descripcion_falla+'+counter_fallas+'" value="'+datos.descripcion_falla+'" /><input  id="actionf'+counter_fallas+'" type="hidden" name="actionf[]" value="add" /><input  id="id_recepcion_falla'+counter_fallas+'" type="hidden" name="id_recepcion_falla[]" value="'+datos.id_recepcion_falla+'" />';
				var c3 = nt.addCell();
					c3.$.innerHTML='<button type="button" onclick="fallas_quit('+counter_fallas+')"><img border="0" alt="quitar" src="<?php echo getUrl('img/iconos/minus.png');?>" /></button>';
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
		</script>
		<style type="text/css">
			
			button img{
				vertical-align:middle;
			}
			
		</style>
		 <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Atenci&oacute;n Cliente</span><br />
			<span class="subtitulo_pagina" >(Citas Culminadas)</span></td>
			
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
					<button type="button"  value="reset" title="Fecha" onclick="fecha_filtro.showDateDialog(this);" ><img border="0" src="<?php echo getUrl('img/iconos/select_date.png') ?>" /> <span id="filtrofecha">Filtrar Fecha</span></button>
					<label class="opfecha"><input type="radio" name="fecha_rank" value="1" onclick="cambiar_fecharank(this);" checked="checked" />Dia</label>
					<label class="opfecha"><input type="radio" name="fecha_rank" onclick="cambiar_fecharank(this);" value="2" checked="checked" />Mes</label>
					<label class="opfecha"><input type="radio" name="fecha_rank" onclick="cambiar_fecharank(this);" value="3" />A&ntilde;o</label>
					</fieldset>
				</td>
				<td colspan="2">
					<button type="button" title="Clientes" onclick="buscar_cliente_pago();"><img border="0" src="<?php echo geturl('img/iconos/find.png'); ?>" />&nbsp;Clientes</button>
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
<div class="window" id="cuadro_llamadas" style="visibility:hidden; min-width:400px;">
	<div class="title" id="titulo_cuadro_llamadas">
		Registrar Llamada		
	</div>
	<div class="content">
		<form id="f_llamada" name="f_llamada" onsubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_cita" name="id_cita" />
			<input type="hidden" id="estado_llamada" name="estado_llamada" />
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
							Tel&eacute;fonos
						</td>
						<td class="field" id="capa_telefonos" style="font-weight:bold;color:#FF0000;">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Nivel de Satisfacci&oacute;n:
						</td>
						<td class="field" id="capa_nivel">
							<?php echo $input_nivel; ?>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2" align="right">
							<button type="submit" id="guardar" onclick="xajax_guardar(xajax.getFormValues('f_llamada'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Registrar</button>
						</td>
					</tr>
				</tfoot>
			</table>
		</form>
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onclick="close_window('cuadro_llamadas');" border="0" />
</div>
<div style="display:none;" id="cliente_dc"></div>
</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
		//xajax_load_page('calendario');
		r_dialogo_citas();
	</script>
	</body>
</html>