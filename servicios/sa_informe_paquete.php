<?php
@session_start();
define('PAGE_PRIV','sa_informe_paquete');//nuevo gregor
//define('PAGE_PRIV','sa_informe_paquetes');//antes
require_once("../inc_sesion.php");

//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	//include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
	require_once ("../connections/conex.php");//lo necesita ac_iv_general
    include("controladores/ac_iv_general.php");//tiene la funcion listado de empresas final

	function load_page($args=''){//$page,$maxrows,$order,$ordertype,$capa,
		$r= getResponse();		
		setLocaleMode();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			$r->assign($capa,inner,'Acceso denegado');
			return $r;
		}
		
		//$r->alert($args);
		$c = new connection();
		$c->open();
		
		
		$sa_paquetes = $c->sa_v_paquetes;
		$query = new query($c);
		$query->add($sa_paquetes);
		$paginador = new fastpaginator('xajax_load_page',$args,$query);
		$arrayFiltros=array(
			'Empresa'=>array(
				'change'=>'h_empresa',
				'addevent'=>"obj('empresa').value='';"
			),
			'busca'=>array(
				'title'=>'B&uacute;squeda',
				'event'=>'restablecer();'
			),
			'fecha'=>array(
				'title'=>'Fecha'
			)
		);
		$argumentos = $paginador->getArrayArgs();
		$aplica_iva=($argumentos['iva']==1);
		$id_empresa=$argumentos['h_empresa'];
		$query->where(new criteria(sqlEQUAL,$sa_paquetes->id_empresa,$id_empresa));
		
		if($id_empresa==null){
			$r->assign($paginador->layer,inner,'Seleccione una Empresa');
			return $r;
		}
		
		$rec=$paginador->run();
		if($rec){
			foreach($rec as $v){
				//buscando las unidades basicas del paquete
				$unidades=$c->sa_v_informe_tempario_unidades->doSelect($c, new criteria(sqlEQUAL,'id_paquete',$v->id_paquete))->getAssoc('id_paq_unidad','nombre_unidad_basica');
				$lista_unidades=implode(',',$unidades);
				$html.='<table class="order_table"><col width="20%" /><col width="40%" /><col width="40%" /><thead>';
				
				$html.='<tr><td>PAQUETE</td><td>Descripci&oacute;n</td><td>Unidades</td></tr></thead><tbody>';
				$html.='<tr><td>'.$v->codigo_paquete.'</td><td>'.$v->descripcion_paquete.'</td><td>'.$lista_unidades.'</td></tr></tbody></table>';
				
				
				$html.='<table class="order_table"><col width="20%" /><col width="40%" /><col width="20%" /><thead><tr><td>Codigo</td><td>Descripci&oacute;n</td><td>Unidad</td><td>Cantidad</td><td style="display:none;">Precio</td><td style="display:none;">Importe</td></tr></thead><tbody>';
				//cargando los detalles de tempario
				$sa_v_paq_tempario=$c->sa_paq_tempario;
				$sa_tempario= new table('sa_v_tempario','',$c);
				$join= $sa_tempario->join($sa_v_paq_tempario,$sa_tempario->id_tempario,$sa_v_paq_tempario->id_tempario);
				$qdet=new query($c);
				$qdet->add($join);
				$qdet->where(new criteria(sqlEQUAL,$sa_v_paq_tempario->id_paquete,$v->id_paquete));
				//$r->alert($qdet->getSelect());
				$recdet=$qdet->doSelect();
				if($recdet){
					foreach($recdet as $temp){
						$html.='<tr><td>'.$temp->codigo_tempario.'</td><td>'.$temp->descripcion_tempario.'</td><td>'.$temp->descripcion_modo.'</td><td>N/A</td><td style="display:none;">'.$temp->precio.'</td><td style="display:none;">'.$temp->precio.'</td></tr>';
					}
				}
				
				$sa_v_paq_repuestos=$c->sa_paquete_repuestos;
				$iv_articulos= new table('iv_articulos','',$c);
				$join= $iv_articulos->join($sa_v_paq_repuestos,$iv_articulos->id_articulo,$sa_v_paq_repuestos->id_articulo);
				$qdet=new query($c);
				$qdet->add($join);
				$qdet->where(new criteria(sqlEQUAL,$sa_v_paq_repuestos->id_paquete,$rec->id_paquete));
				//$r->alert($qdet->getSelect());
				$recdet=$qdet->doSelect();
				$recdet=$qdet->doSelect();
				if($recdet){
					foreach($recdet as $rep){
						$html.='<tr><td>'.$rep->codigo_articulo.'</td><td>'.$rep->descripcion.'</td><td>'.$rep->unidad.'</td><td>'.$rep->cantidad.'</td><td style="display:none;">'.$rep->precio.'</td><td style="display:none;">'.$rep->precio.'</td></tr>';
					}
				}
				
				$html.='</tbody></table><br />';
			}
		}
		$html.='<br />Informe Generado el '.date(DEFINEDphp_DATETIME12).' - Empresa: '.$argumentos['Empresa'].'<strong>'.$tiva.'</strong>';
		$r->assign('paginador',inner,'<hr><div class="ifilter">Mostrando '.$paginador->count.' resultados de un total de '.$paginador->totalrows.' '.$paginador->getPages(false).'</div>');
		
		/*if($argumentos['busca']!=''){
			$query->where(
				new criteria(sqlOR, array(
					new criteria(' like ',$sa_paquetes->descripcion_paquete,"'%".$argumentos['busca']."%'")//,
					//new criteria(' like ',$sa_paquetes->chasis,"'%".$argumentos['busca']."%'"),
					//new criteria(' like ',$sa_paquetes->color,"'%".$argumentos['busca']."%'"),
					//new criteria(' like ',$c->pg_empresa->nombre_empresa,"'%".$argumentos['busca']."%'")
					)
				)
			);			
		}
		if($argumentos['fecha']!=''){
			$query->where(new criteria(' = ',"DATE_FORMAT(".$sa_paquetes->fecha_rev.",'%d-%m-%Y')","'".$argumentos['fecha']."'"));			
		}
		if($argumentos['h_empresa']!=''){
			$query->where(new criteria(sqlEQUAL,"id_empresa","'".$argumentos['h_empresa']."'"));			
		}else{
			$arrayFiltros['Empresa']['hidden']=1;
		}*/
		//$r->alert($argumentos['h_empresa']);
		
		//$rec=$paginador->run();
		
		/*if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han encontrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($sa_paquetes->id_paquete,'ID').'</td>
				<td>'.$paginador->get($sa_paquetes->codigo_paquete,'C&oacute;digo').'</td>
				<td>'.$paginador->get($sa_paquetes->descripcion_paquete,'Descripci&oacute;n').'</td>
				<td>'.$paginador->get($sa_paquetes->nombre_empresa_sucursal,'Empresa').'</td>
				<td>'.$paginador->get($sa_paquetes->fecha_rev,'Fecha').'</td>
				</tr></thead><tbody>';
				$class='';
				foreach($rec as $v){
					if ($rec->parcial == '1')
						$parcial = 'Si';
					else
						$parcial = 'No';
					$html.='<tr class="'.$class.'">
					
					<td align="center">'.$rec->id_paquete.'</td>
					<td align="center">'.$rec->codigo_paquete.'</td>
					<td align="center">'.$rec->descripcion_paquete.'</td>
					<td align="center">'.$rec->nombre_empresa_sucursal.'</td>
					<td align="center">'.$rec->fecha_rev.'</td>
					</tr>';
					if($class==''){
						$class='impar';
					}else{
						$class='';
					}
				}
				$html.='</tbody></table>';
			}
			
		}*/
		
		$r->assign($paginador->layer,inner,$html);
		
		
		//$r->assign('paginador',inner,'<hr><div class="ifilter">Mostrando '.$paginador->count.' resultados de un total de '.$paginador->totalrows.' '.$paginador->getPages(false).'</div><div class="ifilter">'.$paginador->getRemoveFilters('datos',$arrayFiltros).'</div>');

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
	$empresas=getEmpresaList($c);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	$ivas=array(0=>'Sin I.V.A.', 1=>'Con I.V.A.');
	//$empresas = getEmpresaList($c);//$c->sa_v_empresa_sucursal->doSelect($c)->getAssoc('id_empresa','nombre_empresa_sucursal');
	
	$c->close();
	$columnas=array(5=>5,10=>10,20=>20,30=>30,40=>40,60=>60,80=>80,100=>100,200=>200);
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
                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Reporte Paquetes de Servicio</title>
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
			.order_table thead td{
				 font-weight:bold;
			}
		</style>
		<script>
		
			var datos = {
				fecha: 'null',
				page:0,
				maxrows:30,
				order:null,
				ordertype:null,
				busca:'',
				layer:'capaTabla',
				iva:0,
				h_empresa:'<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>'
			}
			var argumentos = {
				busca:''
			}
			
			function cambio_maxcols(obj){				
				datos.maxrows=obj.value;
				if(datos.h_empresa!=''){
					cargar();
				}
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
				cargar();
			}
			function cambio_empresa(obj){
				datos.Empresa=obj.options[obj.selectedIndex].text;
				//alert(datos.Empresa);
				datos.h_empresa=obj.value;
				cargar();
			}
			function cambio_iva(obj){
				datos.iva=obj.value;
				cargar();
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
			<td align="right" class="titulo_pagina" ><span >Servicios</span><br />
			<span class="subtitulo_pagina" >(Reporte Paquetes de Servicio)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
	<div id="principal">
		<div class="noprint">
			
			<input style="display:none;" type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
			
			
			<button style="display:none;" type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
			<button style="display:none;" type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
			<button style="display:none;" type="button" value="reset" title="Restablecer" onClick="fecha_filtro.showDateDialog(this);" ><img border="0" src="<?php echo getUrl('img/iconos/select_date.png') ?>" /></button>
			
             <empresa id="listado_empresas">
             </empresa>
            
			<?php //echo inputSelect('empresa',$empresas,null,'onchange=cambio_empresa(this)',null,' -Seleccione Empresa- ');
//echo ' - '.inputSelect('iva',$ivas,0,'onchange=cambio_iva(this)',false); ?>
			<?php echo ' Registros:'.inputSelect('max',$columnas,30,'onchange=cambio_maxcols(this);',false); ?>
			
			<button type="button" value="print" title="Imprimir" onClick="print();" ><img border="0" src="<?php echo getUrl('img/iconos/print.png') ?>" />Imprimir</button>
			<hr />
		</div>
		<div id='capaTabla'></div>
		<div id="paginador" align="center" style="overflow:hidden; height:1%;" ></div>
	</div>
<!--MARCO PRINCIPAL-->

</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
	
		xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="cambio_empresa(this);"','empresax','listado_empresas'); //buscador
	
		cargar();
	</script>
	</body>
</html>

