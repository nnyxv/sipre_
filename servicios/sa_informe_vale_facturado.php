<?php
@session_start();
define('PAGE_PRIV','sa_informe_vale_facturado');//nuevo gregor
//define('PAGE_PRIV','sa_informe_vales_facturados');//antes
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
		/*$listado = $c->sa_v_placa;		
		$cliente_placa=$listado->join($c->cj_cc_cliente,$c->cj_cc_cliente->id,$listado->id_cliente_registro);
		$cliente_placa_empresa=$cliente_placa->join($c->sa_v_empresa_sucursal,$c->sa_v_empresa_sucursal->id_empresa,$listado->id_empresa);*/
		$listado=$c->sa_v_informe_orden;
		$query = new query($c);
		$query->add($listado);
		//$r->alert($query->getSelect());
		$paginador = new fastpaginator('xajax_load_page',$args,$query);
		$arrayFiltros=array(
			'Empresa'=>array(
				'hidden'=>''
			),
			'Estado'=>array(
				'change'=>'h_estado',
				'addevent'=>"obj('estadox').value='';"
			),
			'Asesor'=>array(
				'change'=>'h_asesor',
				'addevent'=>"obj('asesorx').value='';"
			),
                        'Tipo'=>array(
				'change'=>'h_tipo',
				'addevent'=>"obj('tipox').value='';"
			),
			'busca'=>array(
				'title'=>'B&uacute;squeda',
				'event'=>'restablecer();'
			),
			'fecha_alta'=>array(
				'hidden'=>''
			),
			'fecha_rank'=>array(
				'hidden'=>''
			),
			'fecha'=>array(
				'hidden'=>''
			)
		);
		$argumentos = $paginador->getArrayArgs();
		
		$fecha_alta=$argumentos['fecha_alta'];
		
		if($fecha_alta!=''){
			//'<span class="'.$this->class_filter.'" onclick="'.$event.'" title="Eliminar filtro '.$kt.'">Filtrado por '.$kt.': '.$v.'</span>';
			$rank=$argumentos['fecha_rank'];
			$meses=array(1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Ocutubre','Noviembre','Diciembre');
			$diaar=explode('-',$fecha_alta);
			if($rank==1){
				//todo el d�a
				$fechac=new criteria(sqlEQUAL,'DATE(fecha_alta)',field::getTransformType($fecha_alta,field::tDate));
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha_alta=\'\';cargar();" title="Eliminar filtro Fecha Diario"><strong>Filtrado por D&iacute;a: '.$fecha_alta.'</strong></span>';
			}elseif($rank==2){
				//$r->alert(utf_export(intval($diaar[1])));
				$fechac=new criteria(sqlAND,
					new criteria(sqlEQUAL,"DATE_FORMAT(fecha_alta,'%c')","DATE_FORMAT(".field::getTransformType($fecha_alta,field::tDate).",'%c')"),
					new criteria(sqlEQUAL,"DATE_FORMAT(fecha_alta,'%Y')","DATE_FORMAT(".field::getTransformType($fecha_alta,field::tDate).",'%Y')")
				);
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha_alta=\'\';cargar();" title="Eliminar filtro Fecha Mensual"><strong>Filtrado por Mes: '.$meses[intval($diaar[1])].' del '.$diaar[2].'</strong></span>';
			}else{
				$fechac=new criteria(sqlEQUAL,"DATE_FORMAT(fecha_alta,'%Y')","DATE_FORMAT(".field::getTransformType($fecha_alta,field::tDate).",'%Y')");
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha_alta=\'\';cargar();" title="Eliminar filtro Fecha Anual"><strong>Filtrado por A&ntilde;o: '.$diaar[2].'</strong></span>';
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
		
		//$r->alert(utf_export($argumentos));
		if($argumentos['h_empresa']!=''){
			$query->where(new criteria(sqlEQUAL,"id_empresa","'".$argumentos['h_empresa']."'"));			
		}else{
			$r->assign($paginador->layer,inner,'Seleccione una empresa');
			$r->assign('paginador',inner,'&nbsp;');
			return $r;
			$arrayFiltros['Empresa']['hidden']=1;
		}
		if($argumentos['h_estado']!=''){
			$query->where(new criteria(sqlEQUAL,"id_estado_orden",24));
		}
		if($argumentos['h_asesor']!=''){
			$query->where(new criteria(sqlEQUAL,"id_empleado_servicio","'".$argumentos['h_asesor']."'"));
		}
                if($argumentos['h_tipo']!=''){
			$query->where(new criteria(sqlEQUAL,"id_tipo_orden","'".$argumentos['h_tipo']."'"));
		}
		//$r->alert($argumentos['h_empresa']);
		$query->where(new criteria(sqlEQUAL,"id_estado_orden",24));
		
		$rec=$paginador->run();
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han encontrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($listado->numero_orden,'N Orden').'</td>
                                <td>'.$paginador->get($listado->nombre_tipo_orden,'Tipo Orden').'</td>
                                <td>'.$paginador->get($listado->fact,'Nro Vale salida').'</td>
				<td>'.$paginador->get($listado->apellido_cliente,'Cliente').'</td>
				<td>'.$paginador->get($listado->telefono_cliente,'Tel&eacute;fono').'</td>
				<td>'.$paginador->get($listado->tipo_auto,'T auto').'</td>
				<td>'.$paginador->get($listado->placa,'Placa').'</td>
				<td>'.$paginador->get($listado->color,'Color').'</td>
				<td nowrap="nowrap">'.$paginador->get($listado->fecha_alta,'Fecha Alta').'</td>
				<td>'.$paginador->get($listado->nombre_estado,'Estado').'</td>
				<td nowrap="nowrap">'.$paginador->get($listado->tiempo_entrega,'Fecha Entrega').'</td>
				<td>'.$paginador->get($listado->asesor,'Asesor').'</td>
				<td>'.$paginador->get($listado->importe,'Importe').'</td>
				<td class="noprint">&nbsp;</td>
				</tr></thead><tbody>';
				$class='';
				$total= 0;
				foreach($rec as $v){
                                        $campos1= "";
                                        $condicion1= "";
                                        $sql1= "";

                                        $campos1= "*";
                                        $condicion1= "numeroPedido= ".$rec->id_orden;
                                        $condicion1.= " AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1";

                                        $sql1= "SELECT ".$campos1." FROM cj_cc_encabezadofactura WHERE ".$condicion1.";";
                                        $rs1 = mysql_query($sql1) or die(mysql_error());
                                        $row1 = mysql_fetch_assoc($rs1);

					$total+= $rec->importe;
					if ($rec->parcial == '1')
						$parcial = 'Si';
					else
						$parcial = 'No';
                                        if($rec->tiempo_entrega){
                                            $entrega= parseTiempo(str_tiempo($rec->tiempo_entrega));
                                        }else{
                                            $entrega= "-";
                                        }
										
										
					//Numero vale de salida gregor:
					$queryNumeroValeSalida = sprintf("SELECT numero_vale FROM sa_vale_salida WHERE id_orden = %s LIMIT 1",
							valTpDato($rec->id_orden,"int"));
					$rsNumeroValeSalida = mysql_query($queryNumeroValeSalida);
					if(!$rsNumeroValeSalida) { return $r->alert(mysql_error()."\n Linea: ".__LINE__); }
					$rowNumeroValeSalida = mysql_fetch_assoc($rsNumeroValeSalida);

					$numeroValeSalida = $rowNumeroValeSalida["numero_vale"];
					
										
					$html.='<tr class="'.$class.'">
					
					<td align="center" idordenoculta="'.$rec->id_orden.'">'.$rec->numero_orden.'</td>
                                        <td align="center">'.$rec->nombre_tipo_orden.'</td>
                                        <td align="center">'.$numeroValeSalida.'</td>
					<td align="center">'.$rec->apellido_cliente.' '.$rec->nombre_cliente.'</td>
					<td align="center" nowrap="nowrap">'.$rec->telefono_cliente.'</td>
					<td align="center">'.$rec->tipo_auto.'</td>
					<td align="center">'.$rec->placa.'</td>
					<td align="center">'.$rec->color.'</td>
					<td align="center">'.parseTiempo(str_tiempo($rec->fecha_alta)).'</td>
					<td align="center">'.$rec->nombre_estado.'</td>
					<td align="center">'.$entrega.'</td>
					<td align="center">'.$rec->asesor.'</td>
					<td align="right">'._formato($rec->importe).'</td>
					
					<td class="noprint" align="center"><img title="Ver Diagn&oacute;stico" alt="Ver Diagn&oacute;stico" src="'.getUrl('img/iconos/diagnostico.png').'" width="16" border="0" onClick="setPopup(\'sa_vale_fallas.php?id='.$rec->id_recepcion.'\',\'recep\',{height:700,width:900,center:\'both\',resizable:1,resizeable:1,scrollbars:1});"></td>
					
					</tr>';
					if($class==''){
						$class='impar';
					}else{
						$class='';
					}
				}
				$html.='</tbody>
							<tfooter>
								<tr>
                                                <td colspan="12" align="right"><b>Total por P&aacute;gina:</b></td>
                                                <td align="right">'._formato($total).'</td>
									<td>&nbsp;</td>
								</tr>
                                        </tfooter>
                                    </table>';
			}
			
		}
		$html.='<br />Informe Generado el '.date(DEFINEDphp_DATETIME12).' - Empresa: '.$argumentos['Empresa'];
		$r->assign($paginador->layer,inner,$html);
		
		
		$r->assign('paginador',inner,'<hr><div class="ifilter">Mostrando '.$paginador->count.' resultados de un total de '.$paginador->totalrows.' '.$paginador->getPages(false).'</div><div class="ifilter">'.$paginador->getRemoveFilters('datos',$arrayFiltros).$removefilterfecha.'</div>');

		$r->assign('campoFecha','value',$fec);
		
		$r->script($paginador->fillJS('datos'));
		//$r->alert($paginador->fillJS('datos'));
	
		$c->close();
		return $r;
	}
	
	xajaxRegister('load_page');
	xajaxRegister('listado_asesores');
	xajaxRegister('listado_tipo_orden');
		
	xajaxProcess();
	
	includeDoctype();
	$c= new connection();
	$c->open();
	//llenando lo necesario
	//$empresas=getEmpresaList($c);
	
	//$estados=$c->sa_estado_orden->doQuery($c,new criteria(sqlEQUAL,'activo',1))->where(new criteria(sqlEQUAL,'id_estado_orden',13))->orderBy('orden')->doSelect()->getAssoc('id_estado_orden','nombre_estado');
	
	//$asesores=$c->sa_v_asesores_servicio->doQuery($c)->orderBy('nombre_completo')->doSelect()->getAssoc('id_empleado','nombre_completo');
	
        //$tipos=$c->sa_tipo_orden->doQuery($c,new criteria(sqlEQUAL,'modo_factura',1))->orderBy('nombre_tipo_orden')->doSelect()->getAssoc('id_tipo_orden','nombre_tipo_orden');
	//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	
	//$empresas = getEmpresaList($c);//$c->sa_v_empresa_sucursal->doSelect($c)->getAssoc('id_empresa','nombre_empresa_sucursal');
	
	$c->close();
	$columnas=array(10=>10,20=>20,30=>30,40=>40,60=>60,80=>80,100=>100,200=>200,500=>500);
	
	
			function listado_asesores($idEmpresa = ""){
		
		global $conex;
		$respuesta = new xajaxResponse();		
		
		if($idEmpresa == ""){
			$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];	
		}
		
		$queryAsesores = "SELECT id_empleado, nombre_completo FROM sa_v_asesores_servicio WHERE id_empresa = ".$idEmpresa." ORDER BY nombre_completo";
		
		$todosAsesores = mysql_query($queryAsesores,$conex);
		if(!$todosAsesores) { return $respuesta->alert("Error listado de asesores \n Error: ".mysql_error()." \n Linea: ".__LINE__."");}
		$arrayAsesores;
		while($row = mysql_fetch_array($todosAsesores)){
				$arrayAsesores[$row['id_empleado']] = utf8_encode($row['nombre_completo']);
		}
		
		$listadoAsesores = inputSelect('asesorx',$arrayAsesores,null,'onchange=cambio_asesor(this)',null,'Filtro Asesor:');
		
		$respuesta->assign("listado_asesores","innerHTML",$listadoAsesores);
		
		return $respuesta;
	}
	
	function listado_tipo_orden($idEmpresa = ""){
		
		global $conex;
		$respuesta = new xajaxResponse();		
		
		if($idEmpresa == ""){
			$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];	
		}
		
		$queryTipoOrden = "SELECT id_tipo_orden, nombre_tipo_orden FROM sa_tipo_orden WHERE id_empresa = ".$idEmpresa." AND modo_factura = 1";
		
		$todosTipoOrden = mysql_query($queryTipoOrden,$conex);
		if(!$todosTipoOrden) { return $respuesta->alert("Error listado de tipos de orden \n Error: ".mysql_error()." \n Linea: ".__LINE__."");}
		
		$arrayTipoOrden;
		while($row = mysql_fetch_array($todosTipoOrden)){
				$arrayTipoOrden[$row['id_tipo_orden']] = utf8_encode($row['nombre_tipo_orden']);
		}
		
		$listadoTipoOrden = inputSelect('tipox',$arrayTipoOrden,null,'onchange=cambio_tipo(this)',null,'Filtro Tipo:'); 
		
		$respuesta->assign("listado_tipo_orden","innerHTML",$listadoTipoOrden);
		
		return $respuesta;	
		
	}
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
                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Reporte de Ordenes</title>
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
				fecha_alta: '',
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
				datos.fecha_alta=datee;
				obj('filtrofecha').innerHTML=datee;
				cargar();
			}
			function cambiar_fecharank(val){
				//verifica si la fecha se establece:
				datos.fecha_rank=val.value;
				if(datos.fecha_alta != ''){
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
			function cambio_estado(obj){
				if(datos.h_empresa!=''){
					datos.Estado=obj.options[obj.selectedIndex].text;
					//alert(datos.Empresa);
					datos.h_estado=obj.value;
					cargar();
				}else{
					_alert("Seleccione Una empresa");
					obj.value='';
				}
			}
			function cambio_asesor(obj){
				if(datos.h_empresa!=''){
					datos.Asesor=obj.options[obj.selectedIndex].text;
					//alert(datos.Empresa);
					datos.h_asesor=obj.value;
					cargar();
				}else{
					_alert("Seleccione Una empresa");
					obj.value='';
				}
			}
			
                        function cambio_tipo(obj){
				if(datos.h_empresa!=''){
					datos.Tipo=obj.options[obj.selectedIndex].text;
					//alert(datos.Empresa);
					datos.h_tipo=obj.value;
					cargar();
				}else{
					_alert("Seleccione Una empresa");
					obj.value='';
				}
			}

			function cambio_maxcols(obj){				
				datos.maxrows=obj.value;
				if(datos.h_empresa!=''){
					cargar();
				}
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
			<span class="subtitulo_pagina" >(Reporte de Ordenes)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
	<div id="principal">
		<div class="noprint">
                    <table align="center">
                        <tr>
                            <td>
                                <input style="display:none;" type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
                                    <fieldset style="display:inline;">
                                            <legend>Filtro por Fecha</legend>
                                            <button type="button"  value="reset" title="Fecha" onclick="fecha_filtro.showDateDialog(this);" ><img border="0" src="<?php echo getUrl('img/iconos/select_date.png') ?>" /> <span id="filtrofecha">Filtrar Fecha</span></button>
                                            <label class="opfecha"><input type="radio" name="fecha_rank" value="1" onclick="cambiar_fecharank(this);" checked="checked" />Dia</label>
                                            <label class="opfecha"><input type="radio" name="fecha_rank" onclick="cambiar_fecharank(this);" value="2" />Mes</label>
                                            <label class="opfecha"><input type="radio" name="fecha_rank" onclick="cambiar_fecharank(this);" value="3" />A&ntilde;o</label>
                                    </fieldset>
                            </td>
                       
                            <td>
                                <fieldset style="display:inline;">
                                    <legend>Filtros</legend>                                    
                                    
                                    <empresa id="listado_empresas">
                                    </empresa>                                    
                                    
                                     <asesores id = "listado_asesores">
                                    </asesores>
                                    
                                    <tiposorden id = "listado_tipo_orden">
                                    </tiposorden>	
    
    		
                                    <?php //echo inputSelect('empresax',$empresas,null,'onchange=cambio_seccion(this)',null,'Filtro Empresa:'); ?>
                                    <?php //echo inputSelect('estadox',$estados,null,'onchange=cambio_estado(this)',null,'Filtro Estado:'); ?>
                                    <?php //echo inputSelect('asesorx',$asesores,null,'onchange=cambio_asesor(this)',null,'Filtro Asesor:'); ?>
                                    <?php //echo inputSelect('tipox',$tipos,null,'onchange=cambio_tipo(this)',null,'Filtro Tipo:'); ?>
                                </fieldset>
                            </td>
                            <td>
                                <button type="button" value="print" title="Imprimir" onClick="print();" ><img border="0" src="<?php echo getUrl('img/iconos/print.png') ?>" />Imprimir</button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" align="center">
                                <button type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
                                <?php echo ' Registros:'.inputSelect('max',$columnas,30,'onchange=cambio_maxcols(this);',false); ?>
                                <button style="display:none;" type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
                            </td>
                        </tr>
                    </table>
                    <hr />
		</div>
		<div id='capaTabla'></div>
		<div id="paginador" align="center" style="overflow:hidden; height:1%;" ></div>
	</div>
<!--MARCO PRINCIPAL-->

</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
	
		xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="cambio_seccion(this); xajax_listado_asesores(this.value); xajax_listado_tipo_orden(this.value); "','empresax','listado_empresas'); //buscador
	
	xajax_listado_asesores(); 
	xajax_listado_tipo_orden();
	
		cargar();
	</script>
	</body>
</html>
