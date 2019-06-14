<?php

include("../connections/conex.php");
@session_start();
define('PAGE_PRIV','sa_informe_placa');//nuevo gregor 
//define('PAGE_PRIV','sa_informe_placas');//antes

require_once("../inc_sesion.php");

//implementando xajax;	
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	//include("../controladores/ac_iv_general.php");
	include("controladores/ac_iv_general.php");
	
	//include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	function load_page($args=''){//$page,$maxrows,$order,$ordertype,$capa,
            
                global $spanKilometraje;
            
		$r= getResponse();
		setLocaleMode();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			$r->assign($capa,inner,'Acceso denegado');
			return $r;
		}
		//$r->alert($args);
		$c = new connection();
		$c->open();		
		$listado = $c->sa_v_placa;		
		$cliente_placa=$listado->join($c->cj_cc_cliente,$c->cj_cc_cliente->id,$listado->id_cliente_registro);
		$cliente_placa_empresa=$cliente_placa->join($c->sa_v_empresa_sucursal,$c->sa_v_empresa_sucursal->id_empresa,$listado->id_empresa);
		
		$query = new query($c);
		$query->add($cliente_placa_empresa);
		//$r->alert($query->getSelect());
		$paginador = new fastpaginator('xajax_load_page',$args,$query);
		$arrayFiltros=array(
			'Empresa'=>array(
				'change'=>'h_empresa',
				'addevent'=>"obj('empresax').value='';"
			),
			'busca'=>array(
				'title'=>'B&uacute;squeda',
				'event'=>'restablecer();'
			),
			'fecha'=>array(
				'title'=>'Fecha'
			),
			'fecha_rank'=>array(
				'hidden'=>''
			),
			'fecha'=>array(
				'hidden'=>''
			)
		);
		$argumentos = $paginador->getArrayArgs();
		
		$fecha=$argumentos['fecha'];
		
		if($fecha!=''){
			//'<span class="'.$this->class_filter.'" onclick="'.$event.'" title="Eliminar filtro '.$kt.'">Filtrado por '.$kt.': '.$v.'</span>';
			$rank=$argumentos['fecha_rank'];
			$meses=array(1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Ocutubre','Noviembre','Diciembre');
			$diaar=explode('-',$fecha);
			if($rank==1){
				//todo el d�a
				$fechac=new criteria(sqlEQUAL,'fecha_venta',field::getTransformType($fecha,field::tDate));
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha=\'\';cargar();" title="Eliminar filtro Fecha Diario"><strong>Filtrado por D&iacute;a: '.$fecha.'</strong></span>';
			}elseif($rank==2){
				//$r->alert(utf_export(intval($diaar[1])));
				$fechac=new criteria(sqlAND,
					new criteria(sqlEQUAL,"DATE_FORMAT(fecha_venta,'%c')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%c')"),
					new criteria(sqlEQUAL,"DATE_FORMAT(fecha_venta,'%Y')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%Y')")
				);
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha=\'\';cargar();" title="Eliminar filtro Fecha Mensual"><strong>Filtrado por Mes: '.$meses[intval($diaar[1])].' del '.$diaar[2].'</strong></span>';
			}else{
				$fechac=new criteria(sqlEQUAL,"DATE_FORMAT(fecha_venta,'%Y')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%Y')");
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha=\'\';cargar();" title="Eliminar filtro Fecha Anual"><strong>Filtrado por A&ntilde;o: '.$diaar[2].'</strong></span>';
			}
			//$r->alert($fechac->__toString());
			$query->where($fechac);	
		}
		
		if($argumentos['busca']!=''){
			$query->where(
				new criteria(sqlOR, array(
					new criteria(' like ',$listado->descripcion_paquete,"'%".$argumentos['busca']."%'")/*,
					new criteria(' like ',$listado->chasis,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$listado->color,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$c->pg_empresa->nombre_empresa,"'%".$argumentos['busca']."%'")*/
					)
				)
			);			
		}
		
		if($argumentos['h_empresa']!=''){
			$query->where(new criteria(sqlEQUAL,"sa_v_empresa_sucursal.id_empresa","'".$argumentos['h_empresa']."'"));			
		}else{
			$arrayFiltros['Empresa']['hidden']=1;
		}
		//$r->alert($argumentos['h_empresa']);
		
		$rec=$paginador->run();
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han encontrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($listado->placa,'Placa').'</td>
				<td>'.$paginador->get($listado->chasis,'Chasis').'</td>
				<td>'.$paginador->get($listado->kilometraje,$spanKilometraje).'</td>
				<td>'.$paginador->get($listado->Color,'Color').'</td>
				<td>'.$paginador->get($listado->fecha_venta,'Fecha Venta').'</td>
				<td>'.$paginador->get('nombre','Cliente').'</td>
				<td>'.$paginador->get($c->sa_v_empresa_sucursal->nombre_empresa_sucursal,'Empresa').'</td>
				</tr></thead><tbody>';
				$class='';
				foreach($rec as $v){
					if ($rec->parcial == '1')
						$parcial = 'Si';
					else
						$parcial = 'No';
					$html.='<tr class="'.$class.'">
					
					<td align="center">'.$rec->placa.'</td>
					<td align="center">'.$rec->chasis.'</td>
					<td align="center">'.$rec->kilometraje.'</td>
					<td align="center">'.$rec->color.'</td>
					<td align="center">'.$rec->fecha_venta_formato.'</td>
					<td align="center">'.$rec->nombre.' '.$rec->apellido.'</td>
					<td align="center">'.$rec->nombre_empresa_sucursal.'</td>
					
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
		$html.='<br />Informe Generado el '.adodb_date(DEFINEDphp_DATETIME12);
		$r->assign($paginador->layer,inner,$html);
		
		
		$r->assign('paginador',inner,'<hr><div class="ifilter">Mostrando '.$paginador->count.' resultados de un total de '.$paginador->totalrows.' '.$paginador->getPages(false).'</div><div class="ifilter">'.$paginador->getRemoveFilters('datos',$arrayFiltros).$removefilterfecha.'</div>');

		$r->assign('campoFecha','value',$fec);
		
		$r->script($paginador->fillJS('datos'));
	
		$c->close();
		return $r;
	}
	
	xajaxRegister('load_page');
		
	xajaxProcess();
	
	includeDoctype();
	$c= new connection();
	$c->open();
	//llenando lo necesario
	$empresas=getEmpresaList($c,false);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	
	//$empresas = getEmpresaList($c);//$c->sa_v_empresa_sucursal->doSelect($c)->getAssoc('id_empresa','nombre_empresa_sucursal');
	
	$c->close();
	$registros=array(10=>10,20=>20,30=>30,40=>40,60=>60,80=>80,100=>100,200=>200,10000=>10000);
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
	
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Reporte de Placas</title>
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
			#table_articulos td{
				border: 1px solid #ccc;
			}
			#table_articulos thead td{
				background:#BFBFBF;
			}
		</style>
		<script>
		
			var datos = {
				fecha: 'null',
				page:0,
				maxrows:30,
				order:'',
				ordertype:null,
				busca:'',
				layer:'capaTabla',
				fecha_rank:1,
				h_empresa:'<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>'
			}
			var argumentos = {
				busca:''
			}
			
			function cargar(){
			//alert('fd');
				
				//xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','busca='+argumentos.busca);
				xajax_load_page(toPaginator(datos));
				close_window("edit_window");
				close_window("unidad_window");
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
			function cargar_fecha(datee){
				datos.fecha=datee;
				obj('filtrofecha').innerHTML=datee;
				cargar();
			}
			function cambiar_fecharank(val){
				//verifica si la fecha se establece:
				datos.fecha_rank=val.value;
				
				if(datos.fecha != ''){
					cargar();
				}else{
					fecha_filtro.showDateDialog(val);
				}
			}
			function cambio_seccion(obj){
				datos.Empresa=obj.options[obj.selectedIndex].text;
				//alert(datos.Empresa);
				datos.h_empresa=obj.value;
				cargar();
			}
			
			function cambio_maxcols(obj){
				datos.maxrows=obj.value;
				//if(datos.h_empresa!=''){
					cargar();
				//}
			}
			var fecha_filtro = new dateDialog(cargar_fecha);
			/*for(var i in dd){
				alert(i);
			}*/
		</script>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Mantenimiento de Servicios</span><br />
			<span class="subtitulo_pagina" >(Reporte de Placas)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
	<div id="principal">
		<div class="noprint">
			
			<input style="display:none;" type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
			<fieldset style="display:inline;">
				<legend>Filtro por Fecha</legend>
				<button type="button"  value="reset" title="Fecha" onclick="fecha_filtro.showDateDialog(this);" ><img border="0" src="<?php echo getUrl('img/iconos/select_date.png') ?>" /> <span id="filtrofecha">Filtrar Fecha</span></button>
				<label class="opfecha"><input type="radio" name="fecha_rank" value="1" onclick="cambiar_fecharank(this);" checked="checked" />Dia</label>
				<label class="opfecha"><input type="radio" name="fecha_rank" onclick="cambiar_fecharank(this);" value="2" />Mes</label>
				<label class="opfecha"><input type="radio" name="fecha_rank" onclick="cambiar_fecharank(this);" value="3" />A&ntilde;o</label>
			</fieldset>
			<button style="display:none;" type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
			
			<button style="display:none;" type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
			<?php echo ' Registros:'.inputSelect('max',$registros,30,'onchange=cambio_maxcols(this);',false); ?>
			
			 <empresa id="listado_empresas">
             </empresa>
			
			<?php //echo inputSelect('empresax',$empresas,0,'onchange=cambio_seccion(this)',null,'Filtro Empresa:'); ?>
			<hr />
		</div>
		<div id='capaTabla'></div>
		<div id="paginador" align="center" style="overflow:hidden; height:1%;" ></div>
	</div>
<!--MARCO PRINCIPAL-->

</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
	
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="cambio_seccion(this);"','empresax','listado_empresas'); //buscador
	
		cargar();
	</script>
	</body>
</html>

