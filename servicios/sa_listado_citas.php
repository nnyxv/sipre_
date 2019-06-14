<?php
@session_start();
define('PAGE_PRIV','sa_listado_citas');//nuevo gregor
//define('PAGE_PRIV','sa_recepcion');//anterior
require_once("../inc_sesion.php");
require_once("../connections/conex.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	
	function load_page($id_calendario){
		//$calendario=setCalendar($id_calendario,'xajax_cargar_dia');
		//$r->loadCommands($calendario);
		return $r;
	}
	include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
	function listar_citas($page,$maxrows,$order,$ordertype,$capa,$args=''){
		$r= getResponse();
		
		global $spanCI;
		global $spanRIF;
		
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			$r->assign($capa,inner,'Acceso denegado');
			return $r;
		}
		//@session_start();
		$r=getResponse();

		//procesando argumentos:
		$argumentos=paginator::getExplodeArgs($args);
		if($argumentos['fecha_cita']=='null'){
			$argumentos['fecha_cita']=adodb_date(DEFINEDphp_DATE);
		}
		$c=new connection();
		$c->open();
		$sa_v_datos_cita=$c->sa_v_datos_cita;
		$q = new query($c);
		$q->add($sa_v_datos_cita);
		$q->where(new criteria(sqlEQUAL,$sa_v_datos_cita->fecha_cita,field::getTransformType($argumentos['fecha_cita'],field::tDate)))->
			where(new criteria(sqlEQUAL,$sa_v_datos_cita->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']))->
			where(new criteria(sqlNOTEQUAL,$sa_v_datos_cita->estado_cita,"'CANCELADA'"))->
			where(new criteria(sqlNOTEQUAL,$sa_v_datos_cita->estado_cita,"'POSPUESTA'"))->
			where(new criteria(sqlNOTEQUAL,$sa_v_datos_cita->estado_cita,"'PROCESADA'"))->
			where(new criteria(sqlNOTEQUAL,$sa_v_datos_cita->estado_cita,"'RETRAZADA'"))
			;
	
			
		if($argumentos['filtro_cliente']!='null'){
			$q->where(new criteria(sqlEQUAL,$sa_v_datos_cita->id_cliente_contacto,"'".$argumentos['filtro_cliente']."'"));
			$rq=$c->cj_cc_cliente->doSelect($c,new criteria(sqlEQUAL,$c->cj_cc_cliente->id,$argumentos['filtro_cliente']));
			$cliente=$rq->lci.'-'.$rq->ci.', '.$rq->apellido.' '.$rq->nombre;
			$filtros.='<span style="color:red;" class="filter" title="Eliminar filtro" onclick="cita_date.filtro_cliente=null;r_dialogo_citas();">Filtrado por Cliente: '.$cliente.'</span>';
		}
		if($argumentos['origen_cita']!='null'){
			$q->where(new criteria(sqlEQUAL,$sa_v_datos_cita->origen_cita,"'".$argumentos['origen_cita']."'"));
			$filtros.='<span class="filter" title="Eliminar filtro" onclick="cita_date.origen_cita=null;r_dialogo_citas();">Filtrado por Origen: '.$argumentos['origen_cita'].'</span>';
		}
		if($argumentos['estado_cita']!='null'){
		
			$q->where(new criteria(sqlEQUAL,$sa_v_datos_cita->estado_cita,"'".$argumentos['estado_cita']."'"));
			$filtros.='<span class="filter" title="Eliminar filtro" onclick="cita_date.estado_cita=null;r_dialogo_citas();obj(\'filtro_estados\').value=\'null\';">Filtrado por Estado: '.$argumentos['estado_cita'].'</span>';
		}
		
//CAMBIO 08-03-2010: ELIMINAR VALIDACION DE ENTRADA CLIENTES
		//$q->where(new criteria(' IS NOT ',$sa_v_datos_cita->tiempo_llegada_cliente,sqlNULL));
		$paginator = new paginator('xajax_listar_citas',$capa,$q,$maxrows);
		
		$rec=$paginator->run($page,$order,$ordertype,$args);
		//$r->alert($q->getSelect());
		$origen=array('PROGRAMADA'=>getUrl('img/iconos/cita_programada.png'),'ENTRADA'=>getUrl('img/iconos/cita_entrada.png'));
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han encontrado registros</div>';
			}else{
			//<td style="text-align:center;">'.$paginator->get($sa_v_datos_cita->numero_entrada,'N Llegada').'</td>
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				<td>&nbsp;</td>
				<td style="text-align:center;">'.$paginator->get($sa_v_datos_cita->numero_cita,'N').'</td>
				
				<td nowrap="nowrap">'.$paginator->get($sa_v_datos_cita->hora_inicio_cita,'Hora').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->origen_cita,'Origen').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->estado_cita,'Estado').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->placa,'Placa').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->cedula_cliente, $spanCI." / ".$spanRIF).'</td>
				<td>'.$paginator->get($sa_v_datos_cita->nombre,'Nombre').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->apellido,'Apellido').'</td>
				<td>'.$paginator->get($sa_v_datos_cita->asesor,'Asesor').'</td>
				
				</tr></thead><tbody>';
				$class='';
				foreach($rec as $v){
				//<td style="text-align:center;color:#FF0000;font-weight:bold;">'.$rec->numero_entrada.'</td>
					$html.='<tr class="'.$class.'">
					<td width="1%"><button style="cursor:pointer;" type="button" onclick="cargar_cita('.$rec->id_cita.');"><img border="0" alt="cargar cita" src="'.getUrl('img/iconos/minselect.png').'" /></button></td>
					<td style="text-align:center;" idCitaOculta="'.$rec->id_cita.'">'.$rec->numero_cita.'</td>
					<td style="text-align:center;" nowrap="nowrap">'.$rec->hora_inicio_cita_12.'</td>
					<td align="center"><img border="0" src="'.$origen[$rec->origen_cita].'" /></td>
					<td>'.$rec->estado_cita.'</td>
					<td>'.$rec->placa.'</td>
					<td>'.$rec->cedula_cliente.'</td>
					<td>'.$rec->nombre.'</td>
					<td>'.$rec->apellido.'</td>
					<td>'.$rec->asesor.'</td>
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
		$r->assign('filtros',inner,ifnull($filtros));
		
		$r->script("//setDivWindow('cuadro_citas','titulo_citas',true);
		cita_date.page=".$paginator->page.";
		cita_date.maxrows=".$paginator->maxrows.";
		cita_date.order='".$paginator->order."';
		cita_date.ordertype='".$paginator->ordertype."';
		cita_date.origen_cita='".$argumentos['origen_cita']."';
		cita_date.fecha='".$argumentos['fecha_cita']."';
		cita_date.estado_cita='".$argumentos['estado_cita']."';
		cita_date.filtro_cliente='".$argumentos['filtro_cliente']."';
		");
		
		$c->close();
		return $r;
	}
	
	function cargar_cita($id_cita){
         
		$r= getResponse();
		//cargando la cita:
		$c=new connection();
		$c->open();
		$rec=$c->sa_v_datos_cita->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_datos_cita->id_cita,$id_cita));
                
		if($rec){
			//verificando el registro del cliente
			if($rec->status=='Inactivo'){
				$r->script('_alert("No se ha completado el registro del Cliente");');
				return $r;
			}
			//vehiculo
			$placa=$c->getQuery($c->sa_v_placa);
			$placa->where(new criteria(sqlEQUAL,$c->sa_v_placa->id_registro_placas,$rec->id_registro_placas));
			$recplaca=$placa->doSelect();
                        
			if($recplaca){
                            
				//verificando si el vehiculo se ha registrado completamente:
				if($recplaca->chasis==''){
					$r->script('_alert("No se ha completado el registro del Veh&iacute;culo");');
					return $r;
				}
				$r->loadCommands(cargar_vehiculo($recplaca));
			}
			//cita
			$r->loadCommands(cargar_datos_cita($rec));
			//cliente
			$r->loadCommands(cargar_cliente($rec));
			//llamando al inventario
			$r->assign('inventario','src','sa_inventario_frame.php?from=recepcion&id_cita='.$rec->id_cita);
			
			//cargando la hora exacta de haber cargado la cita:
			
			setLocaleMode();
			$hora=time();
			$r->assign('hora_entrada','value',$hora);
			$r->alert('Hora de inicio capturada: '.adodb_date(DEFINEDphp_TIME,$hora));
		}
		$c->close();
		//$r->script('close_window("cuadro_citas");');
		return $r;
	}
	
	function cargar_cliente($rec){
		$r=getResponse();
		$r->assign('capa_cedula_cliente',inner,ifnull($rec->cedula_cliente));
		$r->assign('capa_nombre_cliente',inner,ifnull($rec->nombre));
		$r->assign('capa_apellido_cliente',inner,ifnull($rec->apellido));
		$r->assign('capa_telefono_cliente',inner,ifnull($rec->telf));
		$r->assign('capa_celular_cliente',inner,ifnull($rec->otrotelf));
		$r->assign('capa_email_cliente',inner,ifnull($rec->correo));
		return $r;
	}
	
	function cargar_datos_cita($rec){
		$r=getResponse();
		$r->assign('id_cita','value',$rec->id_cita);
		$r->assign('capa_id_cita',inner,$rec->id_cita);
		$r->assign('capa_fecha_cita',inner,$rec->fecha_cita);
		$r->assign('capa_hora_inicio_cita',inner,$rec->hora_inicio_cita_12);
		$r->assign('capa_asesor',inner,$rec->asesor);
		$r->assign('capa_origen_cita',inner,$rec->origen_cita);
		$r->assign('capa_estado_cita',inner,$rec->estado_cita);
		$r->assign('capa_motivo_cita',inner,ifnull($rec->motivo));
		$r->assign('capa_motivo_detalle',inner,ifnull($rec->motivo_detalle));
		return $r;
	}
	
	function cargar_vehiculo($recplaca){
		$r=getResponse();
		$r->assign('capa_marca',inner,$recplaca->nom_marca);
		$r->assign('capa_modelo',inner,$recplaca->nom_modelo);
		$r->assign('capa_version',inner,$recplaca->nom_version);
		$r->assign('capa_unidad',inner,$recplaca->nombre_unidad_basica);
		
		$r->assign('capa_placa',inner,$recplaca->placa);
		$r->assign('capa_chasis',inner,$recplaca->chasis);
		$r->assign('capa_color',inner,$recplaca->color);
		$r->assign('capa_kilometraje',inner,$recplaca->kilometraje);
		
		$r->assign('capa_ano',inner,$recplaca->ano);
		$r->assign('capa_fecha_venta',inner,parseDate(str_date($recplaca->fecha_venta)));
		$r->assign('capa_combustible',inner,$recplaca->nom_combustible);
		$r->assign('capa_transmision',inner,$recplaca->nom_transmision);
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
			//buscando citas del cliente pendientes o confirmadas:
			/*$r->assign('id_cliente_pago','value',$rec->id);
			$r->assign('cedula_cliente_pago','value',ifnull($rec->lci.'-'.$rec->ci));
			$r->assign('capa_nombre_cliente_pago',inner,ifnull($rec->nombre));
			$r->assign('capa_apellido_cliente_pago',inner,ifnull($rec->apellido));
			$r->assign('capa_telefono_cliente_pago',inner,ifnull($rec->telf));
			$r->assign('capa_celular_cliente_pago',inner,ifnull($rec->otrotelf));
			$r->assign('capa_email_cliente_pago',inner,ifnull($rec->correo));
			$r->script('
				close_window("xajax_dialogo_cliente");
				obj("cedula_cliente_pago").readOnly=true;
				//window.frames["inventario"].probando();
			');*/
		}
		$c->close();
		return $r;
	}
	
	function registrar($form){
		$r=getResponse();
		//$r->alert(utf_export($form));
		setLocaleMode();
		if($form['id_cita']==''){
			$r->script('_alert("No se ha seleccionado una cita para registrar");');
			return $r;
		}else{
			$c= new connection();
			$c->open();
			$c->begin();
			$tiempo_llegada_cliente=adodb_date(DEFINEDphp_DATETIME,$form['hora_entrada']);
			//$r->alert($tiempo_llegada_cliente);
			
			$sa_cita = new table('sa_cita');
			$sa_cita->add(new field('id_cita','',field::tInt,$form['id_cita'],true));
			$sa_cita->add(new field('tiempo_llegada_cliente','',field::tDatetime,$tiempo_llegada_cliente,true));
			$result=$sa_cita->doUpdate($c,$sa_cita->id_cita);
			if($result===true){
				$r->script('_alert("Se ha registrado la hora de llegada del cliente con &Eacute;xito");
				$("#form_cita .field .data").html("&nbsp;");
				$("#form_cita input").val("");
				');
				$c->commit();
			}else{
				$c->rollback();
				$r->alert(( $result[0]->getMessage()));
			}
			$c->close();
		}		
		return $r;
	}


	xajaxRegister('registrar');
	xajaxRegister('load_page');
	xajaxRegister('listar_citas');
	xajaxRegister('cargar_cita');
	xajaxRegister('cargar_cliente_pago');
	
	xajaxProcess();
	
	$c= new connection();
	$c->open();
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
		
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Recepci&oacute;n de Veh&iacute;culos</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
		<script>
			var cita_date = {
				fecha: null,
				date:new Date(),
				page:0,
				maxrows:10,
				order:'sa_v_datos_cita.hora_inicio_cita',//'sa_v_datos_cita.numero_entrada',
				ordertype:null,
				estado_cita:null,
				origen_cita:null,
				filtro_cliente:null
			}
			function r_dialogo_citas(){
				xajax_listar_citas(cita_date.page,cita_date.maxrows,cita_date.order,cita_date.ordertype,'lista_citas','fecha_cita='+cita_date.fecha+',origen_cita='+cita_date.origen_cita+',estado_cita='+cita_date.estado_cita+',filtro_cliente='+cita_date.filtro_cliente);
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
			<td align="right" class="titulo_pagina" ><span >Recepci&oacute;n</span><br />
			<span class="subtitulo_pagina" >(Recepci&oacute;n de Veh&iacute;culos)</span></td>
			
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		
	</div>
	
	<div class="area" >
		
		<table class="hidden_table" id="padre_cita">
			<tr>
				<td width="20%" >Fecha: <span id="fecha_cita"></span></td>
				<td width="20%" >
					<img src="../img/iconos/select_date.png" alt="cambiar fecha" title="Cambiar fecha" style="cursor:pointer;" id="fecha_cita_boton" onclick="cargar_cita_fecha(this);" border="0" />
				</td>
				<td width="*" align="right">
					<form class="ipaginator_form" onsubmit="return false;">
						<!--<select id="filtro_asesores" onchange="//cita_date.asesor=this.value;r_dialogo_citas();">
							<option value="null"> - </option>
							<?php
							//$c= new connection();
							?>
						</select>-->
						<button type="button" title="Clientes" onclick="buscar_cliente_pago();"><img border="0" src="<?php echo geturl('img/iconos/find.png'); ?>" />&nbsp;Clientes</button>
						<select id="filtro_estados" onchange="cita_date.estado_cita=this.value;r_dialogo_citas();">
							<option value="null"> - </option>
							<option value="PENDIENTE">Pendiente</option>
							<option value="CONFIRMADA">Confirmada</option>
						</select>
					</form>
					<a id="filtro_programada" class="filter" href="#" onclick="cita_date.origen_cita='PROGRAMADA';r_dialogo_citas();" title="Filtrar programadas"><img border="0" src="../img/iconos/cita_programada.png" />Programada</a>
					<a id="filtro_programada" class="filter" href="#" onclick="cita_date.origen_cita='ENTRADA';r_dialogo_citas();" title="Filtrar entradas"><img border="0" src="../img/iconos/cita_entrada.png" />Entrada</a>
					
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
<div class="window" id="cuadro_citas" style="visibility:hidden;">
	<div class="title" id="titulo_citas">
		Citas		
	</div>
	<div class="content">
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onclick="close_window('cuadro_citas');" border="0" />
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