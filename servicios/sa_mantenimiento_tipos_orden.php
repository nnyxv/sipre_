<?php
@session_start();
define('PAGE_PRIV','sa_mantenimiento_tipos_orden');//nuevo gregor
//define('PAGE_PRIV','sa_tipo_orden');//anterior
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
		
		$cquery = $c->sa_tipo_orden;
		$cempresas = $c->sa_v_empresa_sucursal->join($cquery,$c->sa_v_empresa_sucursal->id_empresa,$cquery->id_empresa);
		$query = new query($c);
		$query->add($cempresas);
		
		if($argumentos['busca']!=''){$query->where(new criteria(sqlOR,
			array(
				new criteria(' like ',$cquery->nombre_tipo_orden,"'%".$argumentos['busca']."%'"),
				new criteria(' like ',$cquery-> modo_factura,"'%".$argumentos['busca']."%'"),
				new criteria(' like ',$cquery->modo_cliente_factura,"'%".$argumentos['busca']."%'"),
				new criteria(' like ',$c->sa_v_empresa_sucursal->nombre_empresa_sucursal,"'%".$argumentos['busca']."%'")
				)
			));
			//$query->where(new criteria(' like ',$cquery->nombre_tipo_orden,"'%".$argumentos['busca']."%'"));//original solo
			
			//busqueda
			//$query->where(new criteria(sqlEQUAL, $cquery->id_empresa,$argumentos['id_empresa_enviada']));
			
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
//IMPEDIR�?

		//normal
		$query->where(new criteria(sqlEQUAL, $cquery->id_empresa,$argumentos['id_empresa_enviada']));
		
		$query->where(new criteria(sqlEQUAL,'orden_generica',0));
	
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get('id_tipo_orden','Id').'</td>
				<td>'.$paginador->get('id_empresa','Empresa').'</td>
				<td>'.$paginador->get('nombre_tipo_orden','Nombre').'</td>
				<td>'.$paginador->get('modo_factura','Modo Factura').'</td>
				<td>'.$paginador->get('modo_cliente_factura','Modo Cliente').'</td>
				<td>'.$paginador->get('id_filtro_orden','Filtro Orden').'</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				
				</tr></thead><tbody>';
				$class='';
				foreach($rec as $v){
                                    
                                    $rs = mysql_query("SELECT descripcion FROM sa_filtro_orden WHERE id_filtro_orden = ".$rec->id_filtro_orden." LIMIT 1");
                                    $row = mysql_fetch_assoc($rs);
					if ($rec->parcial == '1')
						$parcial = 'Si';
					else
						$parcial = 'No';
					$html.='<tr class="'.$class.'">
					
					<td align="center">'.$rec->id_tipo_orden.'</td>
					<td align="center">'.$rec->nombre_empresa_sucursal.'</td>
					<td align="center">'.$rec->nombre_tipo_orden.'</td>
					<td align="center">'.$rec->modo_factura.'</td>
					<td align="center">'.$rec->modo_cliente_factura.'</td>
					<td align="center">'.$rec->id_filtro_orden.'.- '.$row['descripcion'].'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="limpiar(); xajax_cargar_articulo('.$rec->id_tipo_orden.',\'view\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="limpiar(); xajax_cargar_articulo('.$rec->id_tipo_orden.',\'edit\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/delete.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar_articulo('.$rec->id_tipo_orden.',\'delete\');""></td>
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
		//$select_empresa=inputSelect('id_empresa',$ret,null);
		//$r->assign('field_id_empresa',inner,$select_empresa);
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
		$q->add($c->sa_tipo_orden);
		
		$q->where(new criteria(sqlEQUAL,$c->sa_tipo_orden->id_tipo_orden,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
			//cargnado la lista de mepresas
			$empresas=getEmpresaList($c);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
			if($rec){
				//$select_empresa=inputSelect('id_empresa',$empresas,$rec->id_empresa);
				//$r->assign('field_id_empresa',inner,$select_empresa);				 
				//$r->assign('capa_id_art_inventario',inner,$rec->id_art_inventario);
				$r->assign('id_tipo_orden','value',$rec->id_tipo_orden);
				$r->assign('nombre_tipo_orden','value',$rec->nombre_tipo_orden);
				$r->assign('id_filtro_orden','value',$rec->id_filtro_orden);
				$r->assign('id_clave_movimiento','value',$rec->id_clave_movimiento);
				$r->assign('id_clave_movimiento_dev','value',$rec->id_clave_movimiento_dev);
				$r->assign('id_precio_repuesto','value',$rec->id_precio_repuesto);
				//$r->assign('id_precio_tot_porcentaje','value',$rec->id_precio_tot_porcentaje);
				$r->assign('id_precio_tot_porcentaje','value',$rec->porcentaje_tot);
				$r->assign('modo_factura','value',$rec->modo_factura);				
				$r->assign('modo_cliente_factura','value',$rec->modo_cliente_factura);
				$r->assign('precio_tempario','value',_formato($rec->precio_tempario,2));
				$r->assign('id_empresa','value',$rec->id_empresa);
				$r->assign('costo_tempario','value',$rec->costo_tempario);
                                
                                $r->script("xajax_cargarClaveMovimiento('".$rec->modo_factura."',".$rec->id_clave_movimiento.", ".$rec->id_clave_movimiento_dev.");");
                                //
                               
				
				if($rec->orden_costo_tempario == 1){
					$r->script("document.formulario.costo_tempario_general.checked = true;");
				}else{
					$r->script("document.formulario.costo_tempario_general.checked = false;");
				}

                                if($rec->pago_comision == 1){
                                    $r->script("document.formulario.pago_comision.checked= true;");
                                }else{
                                    $r->script("document.formulario.pago_comision.checked= false;");
								}

                                if($rec->posee_iva == 1){
                                    $r->script("document.formulario.posee_iva.checked= true;");
                                }else{
                                    $r->script("document.formulario.posee_iva.checked= false;");
                                }
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
		$c=new connection();
		$c->open();


		//$q=new query($c);
		//$q->add($c->pg_precios);
		//if($form['id_precio_tot_porcentaje'] == "") { $form['id_precio_tot_porcentaje'] = NULL; }//valido que no sea blanco sino null = error rojo
		//$q->where(new criteria(sqlEQUAL,$c->pg_precios->id_precio,$form['id_precio_tot_porcentaje']));
                //$rec=$q->doSelect();
               
                $r=getResponse();

		//removiendo las clases que indican error:
		$r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');
		
		$save = new table("sa_tipo_orden");
		$save->insert('id_tipo_orden',$form['id_tipo_orden']);
		$save->insert('id_empresa',$form['id_empresa']);
		$save->insert('nombre_tipo_orden',$form['nombre_tipo_orden'],field::tString);
		$save->insert('id_filtro_orden',$form['id_filtro_orden']);
		$save->insert('id_clave_movimiento',$form['id_clave_movimiento']);
		$save->insert('id_clave_movimiento_dev',$form['id_clave_movimiento_dev']);
		$save->insert('id_precio_repuesto',$form['id_precio_repuesto']);
		//$save->insert('id_precio_tot_porcentaje',$form['id_precio_tot_porcentaje']);
		$save->insert('id_precio_tot_porcentaje',0);
		$save->insert('modo_factura',$form['modo_factura'],field::tString);
		$save->insert('modo_cliente_factura',$form['modo_cliente_factura'],field::tString);
		$save->insert('precio_tempario',$form['precio_tempario'],field::tFloat);
		$save->insert('costo_tempario',$form['costo_tempario'],field::tFloat);
		
		if(isset($form['costo_tempario_general'])){
                    $form['costo_tempario_general'] = 1;	
		}else{
                    $form['costo_tempario_general'] = 0;
                }		
		$save->insert('orden_costo_tempario',$form['costo_tempario_general']);
		
                //$save->insert('porcentaje_tot',$rec->porcentaje_tot,field::tFloat);
                $save->insert('porcentaje_tot',$form['id_precio_tot_porcentaje'],field::tFloat);
                if(isset($form['pago_comision'])){
                    $form['pago_comision']= 1;
                }else{
                    $form['pago_comision']= 0;
                }
                
                if(isset($form['posee_iva'])){
                    $form['posee_iva']= 1;
                }else{
                    $form['posee_iva']= 0;
                }
                
                
                $save->insert('pago_comision',$form['pago_comision']);
                $save->insert('posee_iva',$form['posee_iva']);
                //$r->script('cargar();close_window("edit_window");');
		$c->begin();
		if($form['id_tipo_orden']==''){
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				$c->rollback();
				return $r;
			}
			$result=$save->doInsert($c,$save->id_tipo_orden);
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
				$c->rollback();
				return $r;
			}
			$result=$save->doUpdate($c,$save->id_tipo_orden);
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
	
	
	
        function cargarClaveMovimiento($modo,$id_clave_movimiento = NULL,$id_clave_movimiento_dev = NULL){//carga tanto claves de mov como de devolucion dependiendo de si es factura o vale salida
            $objResponse = new xajaxResponse();
            
            if($modo == "FACTURA"){
               $filtroTipo = "1";
               $filtroTipoDev = "3";
            }elseif($modo == "VALE SALIDA"){
               $filtroTipo = "5";
               $filtroTipoDev = "6";
            }else{
                $filtroTipo = "1,5";//que muestre todos los de ingreso
                $filtroTipoDev = "3,6";//que muestre todos los de devolucion
            }
            
            if($filtroTipo){
                $filtroTipo = "AND documento_genera IN (".$filtroTipo.")";
            }
            
            if($filtroTipoDev){
                $filtroTipoDev = "AND documento_genera IN (".$filtroTipoDev.")";
            }
            
            $sqlClave = "SELECT id_clave_movimiento, descripcion FROM pg_clave_movimiento WHERE id_modulo = 1 ".$filtroTipo;
            $sqlClaveDev = "SELECT id_clave_movimiento, descripcion FROM pg_clave_movimiento WHERE id_modulo = 1 ".$filtroTipoDev;
            
            $rsClave = mysql_query($sqlClave);
            $rsClaveDev = mysql_query($sqlClaveDev);
            
            if(!$rsClave) { $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
            if(!$rsClaveDev) { $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
            
            while ($row = mysql_fetch_assoc($rsClave)){
                $claveMov[$row['id_clave_movimiento']]= utf8_encode($row['descripcion']);
            }
            
            while ($row2 = mysql_fetch_assoc($rsClaveDev)){
                $claveMovDev[$row2['id_clave_movimiento']]= utf8_encode($row2['descripcion']);
            }
            
            //compruebo que la guardada este en el listado, sino es porque se encuentra mal asignada y debo mostrar completo el listado para que se vea
            if($id_clave_movimiento != NULL && $id_clave_movimiento_dev != NULL){
                if(!array_key_exists("$id_clave_movimiento", $claveMov) || !array_key_exists("$id_clave_movimiento_dev", $claveMovDev)){
                    $objResponse->alert("Este tipo de orden tiene una clave de movimiento que no pertene al modo de factura asigando \"".$modo."\" ");
                    $objResponse->script("xajax_cargarClaveMovimiento('OTROS',".$id_clave_movimiento.",".$id_clave_movimiento_dev.");");
                    return $objResponse;
                }
            }
            $clave = inputSelect('id_clave_movimiento',$claveMov);
            $claveDev = inputSelect('id_clave_movimiento_dev',$claveMovDev);
            
            $objResponse->assign("field_id_clave_movimiento","innerHTML",$clave);
            $objResponse->assign("field_id_clave_movimiento_dev","innerHTML",$claveDev);
            
            $objResponse->assign('id_clave_movimiento','value',$id_clave_movimiento);
	    $objResponse->assign('id_clave_movimiento_dev','value',$id_clave_movimiento_dev);
            return $objResponse;
            
        }
        
        
        
        
	xajaxRegister('cargarClaveMovimiento');
	xajaxRegister('guardar');
	xajaxRegister('cargar_empresas');
	xajaxRegister('cargar_articulo');
	xajaxRegister('load_page');
		
	xajaxProcess();
	
	$c= new connection();
	$c->open();

        
        $campos1= "";
        $condicion1= "";
        $sql1= "";

        $campos1= "id_clave_movimiento";
        $campos1.= ", descripcion";
        $condicion1= "id_modulo= 1";

        $sql1= "SELECT ".$campos1." FROM pg_clave_movimiento WHERE ".$condicion1.";";
        $rs1 = mysql_query($sql1) or die(mysql_error());
        while ($row1 = mysql_fetch_assoc($rs1)){
            $movimientos[$row1['id_clave_movimiento']]= utf8_encode($row1['descripcion']);
        }

	$query = new query($c);
	$query->add($c->pg_precios);
	
	$prec = $query->where(new criteria(sqlEQUAL,$c->pg_precios->estatus,1))->doSelect($c);
	//$prec=$c->pg_precios->doSelect($c);
	$precios = $prec->getAssoc('id_precio','descripcion_precio');
	$preciostot = $prec->getAssocPlus('id_precio',array('porcentaje_tot','descripcion_precio'),'% ');
	        
	$modos_factura=array('VALE SALIDA'=>'VALE SALIDA','FACTURA'=>'FACTURA','OTROS'=>'OTROS');
	$modos_cliente_factura=array('CLIENTE'=>'CLIENTE','CLIENTE PAGO/SEGURO'=>'CLIENTE PAGO/SEGURO','PLANTA'=>'PLANTA','ACTIVO'=>'ACTIVO','OTROS'=>'OTROS');
	
	//filtro tipos de orden
	
	$sqlFiltro = "SELECT * FROM sa_filtro_orden";
	$queryFiltro = mysql_query($sqlFiltro);
	if(!$queryFiltro) die (mysql_error());
	
	$arrayFiltro = "";
	
	while($rowFiltro = mysql_fetch_assoc($queryFiltro)){
		$arrayFiltro[$rowFiltro['id_filtro_orden']] = utf8_encode($rowFiltro['descripcion']);
	}
	
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
		
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Tipos de Orden</title>
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
					document.getElementById("formulario").reset();
					$('#edit_window input, #edit_window').val('');
					$('#edit_window #capa_id_art_inventario').html('');
					$("#edit_window #subtitle").html("Agregar");
					$("#edit_window input").attr("readonly","");
					$("#edit_window select").attr("disabled","");
					$("#edit_window #guardar").attr("disabled","");
					//xajax_cargar_empresas();
					//$("#edit_window select").val(datos.js_empresa);
					
					document.getElementById("id_empresa").value = document.getElementById("lstEmpresa").value;
					
					
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
                        
                        function limpiar(){
                            document.getElementById("formulario").reset();
                            document.getElementById("field_id_clave_movimiento").innerHTML = "";
                            document.getElementById("field_id_clave_movimiento_dev").innerHTML = "";
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
			<span class="subtitulo_pagina" >(Tipos de Orden)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div>
	<div>
		<button type="button" value="Nuevo" onClick="limpiar(); agregar(true);" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
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
		<span id="subtitle"></span>&nbsp;Tipo de Orden		
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onsubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_tipo_orden" name="id_tipo_orden" />
			<table class="insert_table" style="width:auto;">
				<tbody>
					<tr>
						<td class="label">
							Empresa
						</td>
						<td class="field" id="field_id_empresa">
							
						</td>
					</tr>
					<tr>
						<td class="label">
							Nombre
						</td>
						<td class="field" id="field_nombre_tipo_orden">
							<input type="text" name="nombre_tipo_orden" id="nombre_tipo_orden" />
						</td>
					</tr>
                    <tr>
						<td class="label">
							Filtro tipo Orden
						</td>
						<td class="field" id="field_id_filtro_orden">
							<?php echo inputSelect('id_filtro_orden',$arrayFiltro); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Modo Factura
						</td>
						<td class="field" id="field_modo_factura">
							<?php echo inputSelect('modo_factura',$modos_factura,"",'onChange=xajax_cargarClaveMovimiento(this.value);'); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Modo Cliente Factura
						</td>
						<td class="field" id="field_modo_cliente_factura">
							<?php echo inputSelect('modo_cliente_factura',$modos_cliente_factura); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Clave Movimiento
						</td>
						<td class="field" id="field_id_clave_movimiento">
							<?php //echo inputSelect('id_clave_movimiento',$movimientos); ?>
						</td>
					</tr>
                    <tr>
						<td class="label">
							Clave Movimiento Dev
						</td>
						<td class="field" id="field_id_clave_movimiento_dev">
							<?php //echo inputSelect('id_clave_movimiento_dev',$movimientos); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Precio Repuestos
						</td>
						<td class="field" id="field_id_precio_repuesto">
							<?php echo inputSelect('id_precio_repuesto',$precios); ?>
						</td>
					</tr>
					<tr>
						<td class="label">
							Porcentaje TOT
						</td>
						<td class="field" id="field_id_precio_tot_porcentaje">
							<?php // echo inputSelect('id_precio_tot_porcentaje',$preciostot); ?>
                                                    <input type="text" id="id_precio_tot_porcentaje" name="id_precio_tot_porcentaje" onchange="set_toNumber(this,2);" onkeypress="return inputFloat(event);" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Precio Posici&oacute;n Trabajo:
						</td>
						<td class="field" id="field_precio_tempario">
							<input type="text" name="precio_tempario" id="precio_tempario" onkeypress="return inputFloat(event);" onchange="set_toNumber(this,2);" />
						</td>
					</tr>
                    <tr>
						<td class="label">
							Costo Posici&oacute;n Trabajo:
						</td>
						<td class="field" id="field_costo_tempario">
							<input type="text" name="costo_tempario" id="costo_tempario" onkeypress="return inputFloat(event);" onchange="set_toNumber(this,2);" />
						</td>
					</tr>
                    <tr>
						<td class="label">
							Tempario por Costo General
						</td>
						<td class="field" id="field_costo_tempario_general">
                                                    <input name="costo_tempario_general" id="costo_tempario_general" type="checkbox" value="1" />
						</td>
					</tr>
                     <tr>
						<td class="label">
							Pago Comisi&oacute;n
						</td>
						<td class="field" id="field_pago_comision">
                                                    <input name="pago_comision" id="pago_comision" type="checkbox" value="1" />
						</td>
					</tr>
                                        <tr>
						<td class="label">
							Posee I.V.A.
						</td>
						<td class="field" id="field_posee_iva">
                                                    <input name="posee_iva" id="posee_iva" type="checkbox" value="1" />
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

<script>
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange = "buscar();"'); //buscador
	xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','',"id_empresa","field_id_empresa");//resto
	
		cargar();
	</script>
	
	</body>
</html>

