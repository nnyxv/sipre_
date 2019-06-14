<?php
@session_start();
define('PAGE_PRIV','sa_registro_vehiculos');//nuevo gregor
//define('PAGE_PRIV','sa_placa');//anterior
require_once("../inc_sesion.php");
require_once("../connections/conex.php"); // lo requiere el ci rif de xajax_dialogo_cliente.inc.php
//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente
	
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
		
		$sa_v_placa = $c->sa_v_placa;
		
		$cliente_placa=$sa_v_placa->join($c->cj_cc_cliente,$c->cj_cc_cliente->id,$sa_v_placa->id_cliente_registro,' left join ');
		$cliente_placa_empresa=$cliente_placa->join($c->sa_v_empresa_sucursal,$c->sa_v_empresa_sucursal->id_empresa,$sa_v_placa->id_empresa);
		
		$query = new query($c);
		$query->add($cliente_placa_empresa);
		
		if($argumentos['busca']!=''){
			$query->where(
				new criteria(sqlOR, array(
					new criteria(' like ',$sa_v_placa->placa,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$sa_v_placa->chasis,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$sa_v_placa->color,"'%".$argumentos['busca']."%'"),
					new criteria(' like ',$c->sa_v_empresa_sucursal->nombre_empresa_sucursal,"'%".$argumentos['busca']."%'")
					)
				)
			);
			
			$filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
		}
		
		//$query->where(new criteria(sqlEQUAL,$sa_v_placa->id_empresa, $_SESSION['idEmpresaUsuarioSysGts']));
	
		$paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
		//$r->alert($query->getSelect());
		$rec=$paginador->run($page,$order,$ordertype,$args);
		
		if($rec){
			if($rec->getNumRows()==0){
				$html.='<div class="order_empty">No se han registrado registros</div>';
			}else{
				$html.='<table class="order_table"><thead><tr class="xajax_order_title">
				
				<td>'.$paginador->get($sa_v_placa->placa,'Placa').'</td>
				<td>'.$paginador->get($sa_v_placa->chasis,'Chasis').'</td>
				<td>'.$paginador->get($c->cj_cc_cliente->apellido,'Cliente').'</td>
				<td>'.$paginador->get($c->sa_v_empresa_sucursal->nombre_empresa_sucursal,'Empresa').'</td>
				<td colspan="4">&nbsp;</td>
				
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
					<td align="center">'.$rec->apellido.' '.$rec->nombre.'</td>
					<td align="center">'.$rec->nombre_empresa_sucursal.'</td>
					<td align="center"><img src="'.getUrl('img/iconos/view.png').'" alt="Ver" width="16" border="0" onClick="xajax_cargar_vehiculo('.$rec->id_registro_placas.',\'view\');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/print.png').'" alt="Imprimir" width="16" border="0" onClick="imprimir_vehiculo('.$rec->id_registro_placas.','.$rec->id_empresa.');"></td>
					<td align="center"><img src="'.getUrl('img/iconos/edit.png').'" alt="Editar" width="16" border="0" onClick="xajax_cargar_vehiculo('.$rec->id_registro_placas.',\'edit\');"></td>
					<!--<td align="center"><img src="'.getUrl('img/iconos/delete.png').'" alt="Eliminar" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar_vehiculo('.$rec->id_registro_placas.',\'delete\');""></td>-->
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
	
	function lista_marcas($c){
		return $c->an_marca->doSelect($c)->getAssoc($c->an_marca->id_marca,$c->an_marca->nom_marca);
	}
	function lista_modelo($c,$marca){
		return $c->an_modelo->doSelect($c,new criteria(sqlEQUAL,$c->an_modelo->id_marca,$marca))->getAssoc($c->an_modelo->id_modelo,$c->an_modelo->nom_modelo);
	}
	function lista_version($c,$modelo){
		return $c->an_version->doSelect($c,new criteria(sqlEQUAL,$c->an_version->id_modelo,$modelo))->getAssoc($c->an_version->id_version,$c->an_version->nom_version);
	}
	function lista_unidad_basica($c,$version){
		
		//solo muestra unidades basicas creadas en al empresa aunque tengan mismo modelo placa version
		$query = $c->an_uni_bas;
		$union = $c->sa_unidad_empresa->join($query,$c->sa_unidad_empresa->id_unidad_basica, $query->id_uni_bas);
		$query2 = new query($c);
		$query2->add($union);
		$query2->where(new criteria(sqlEQUAL,$c->an_uni_bas->ver_uni_bas,$version));
		$query2->where(new criteria(sqlEQUAL,$c->sa_unidad_empresa->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']));
		$listado = $query2->doSelect($c,new criteria(sqlEQUAL,$c->an_uni_bas->ver_uni_bas,$version))->getAssoc($c->an_uni_bas->id_uni_bas,$c->an_uni_bas->nom_uni_bas);
		
		return $listado;
		//return $c->an_uni_bas->doSelect($c,new criteria(sqlEQUAL,$c->an_uni_bas->ver_uni_bas,$version))->getAssoc($c->an_uni_bas->id_uni_bas,$c->an_uni_bas->nom_uni_bas); //antes
	}
	
	function cargar_listas(){
		$r=getResponse();
		$r->loadCommands(cargar_lista_marca());
		$r->loadCommands(cargar_lista_modelo());
		$r->loadCommands(cargar_lista_version());
		$r->loadCommands(cargar_lista_unidad_basica());
		return $r;
	}
	
	function cargar_lista_marca($default=null,$c=null){
		$r=getResponse();
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_marcas($c);
		$select_marca=inputSelect('id_marca',$lassoc,$default,array('onchange'=>'xajax_cargar_lista_modelo(this.value);xajax_cargar_lista_version();xajax_cargar_lista_unidad_basica();'));
		$r->assign('field_id_marca',inner,$select_marca);
		//$c->close();
		return $r;
	}
	
	function cargar_lista_modelo($marca='',$default=null,$c=null){
		$r=getResponse();
		if($marca==''){
			$r->assign('field_id_modelo',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_modelo($c,$marca);
		$select=inputSelect('id_modelo',$lassoc,$default,array('onchange'=>'xajax_cargar_lista_version(this.value);xajax_cargar_lista_unidad_basica();'));
		$r->assign('field_id_modelo',inner,$select);
		//$c->close();
		return $r;
	}
	
	function cargar_lista_version($modelo='',$default=null,$c=null){
		$r=getResponse();
		
		if($modelo==''){
			$r->assign('field_id_version',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_version($c,$modelo);
		$select=inputSelect('id_version',$lassoc,$default,array('onchange'=>'xajax_cargar_lista_unidad_basica(this.value);'));
		$r->assign('field_id_version',inner,$select);
		//$c->close();
		return $r;
	}
	
	function cargar_lista_unidad_basica($version='',$default=null,$c=null){
		$r=getResponse();
		if($version==''){
			$r->assign('field_id_unidad_basica',inner,'&nbsp;');
			return $r;
		}
		if($c==null){
			$c=new connection();
			$c->open();
		}
		//cargnado la lista de mepresas
		$lassoc=lista_unidad_basica($c,$version);
		$select=inputSelect('id_unidad_basica',$lassoc,$default);
		$r->assign('field_id_unidad_basica',inner,$select);
		//$c->close();
		return $r;
	}
	
	function cargar_vehiculo($id, $mode='view'){
		$r=getResponse();
		if (!xvalidaAcceso($r,PAGE_PRIV)){
			//$r->alert('acceso denegado');
			return $r;
		}
		$view=array('add'=>'','view'=>'true');
		
		if($mode=='view'){
			$r->script('
			$("#edit_window #subtitle").html("Ver");
			$("#edit_window #imprimir").attr("disabled","");
			');
		}else{
			$r->script('
			$("#edit_window #subtitle").html("Editar");
			$("#edit_window #imprimir").attr("disabled","true");
			');
		}
		$c=new connection();
		$c->open();
		
		
		$q=new query($c);
		$q->add($c->sa_v_placa);
		$q->where(new criteria(sqlEQUAL,$c->sa_v_placa->id_registro_placas,$id));
		if($mode!='delete'){
			$rec=$q->doSelect();
			
			//cargado las listas:
			
			if($rec){
				$r->assign('id_registro_placas','value',$rec->id_registro_placas);
				//carga informacion basica del cliente
				//$r->alert($rec->id_cliente_registro);
				$r->loadCommands(cargar_cliente_pago($rec->id_cliente_registro,$c));
				
				$r->assign('id_cliente_registro','value',$rec->id_cliente_registro);
				
				$r->assign('codigo_barras_vehiculo',inner,'<img border="0" alt="codigo" src="'.('clases/barcode128.php?pc=1&bw=2&codigo='.$rec->id_registro_placas).'" />');
				
				$r->assign('chasis','value',$rec->chasis);
				$r->assign('placa','value',$rec->placa);
				$r->assign('kilometraje','value',$rec->kilometraje);
				$r->assign('color','value',$rec->color);
				$r->assign('id_empresa','value',$rec->id_empresa);
				$r->assign('fecha_venta','value',$rec->fecha_venta_formato);
				
				//listas
				$r->loadCommands(cargar_lista_marca($rec->id_marca,$c));
				$r->loadCommands(cargar_lista_modelo($rec->id_marca,$rec->id_modelo,$c));
				$r->loadCommands(cargar_lista_version($rec->id_modelo,$rec->id_version,$c));
				$r->loadCommands(cargar_lista_unidad_basica($rec->id_version,$rec->id_unidad_basica,$c));
				
				
			}
			$r->script('agregar(false);');
			
			$r->script('
				$("#edit_window input").not("#fecha_venta").attr("readonly","'.$view[$mode].'");
				$("#edit_window select").attr("disabled","'.$view[$mode].'");
				$("#edit_window #guardar").attr("disabled","'.$view[$mode].'");
				$("#edit_window #buscar_cliente").attr("disabled","'.$view[$mode].'");
				');
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,eliminar)){
				return $r;
			}
			$c->begin();
			$q= new query($c);
			$en_registro_placas=new table('en_registro_placas');
			//$en_registro_placas->id_registro_placas;
			$q->add($en_registro_placas);
			$q->where(new criteria(sqlEQUAL,$en_registro_placas->id_registro_placas,$id));
			$rec=$q->doDelete($c);
			if($rec===true){
				$c->commit();
				$r->script('_alert("Registro eliminado con &eacute;xito");cargar();');
			}else{
				//$r->alert(utf_export($rec).' '.$q->getDelete());
				$r->script('_alert("No se puede eliminar el registro, es posible que el mismo ya est&aacute; siendo utilizado en una cita");');
			}
		}
		$c->close();
		if($mode=='view'){
			$r->script('obj("modificar_km").disabled= true;obj("verifica_km").disabled=true;obj("fecha_venta").disabled=true;');
		}else{
			$r->script('obj("fecha_venta").disabled=false;');
		}
		return $r;
	}
	function validate_key($key,$module,$c=null,$id_user=null,$id_empresa=null){
		if($c==null){
			$c = new connection();
			$c->open();
		}
		if($id_user==null){
			$id_user=$_SESSION['idUsuarioSysGts'];
		}
		//$r->alert("select id_empleado from pg_usuario where id_usuario=".$id_user.";");
		$recemp=$c->execute("select id_empleado from pg_usuario where id_usuario=".$id_user.";");
		$id_empleado=$recemp['id_empleado'];
		
		if($id_empresa==null){
			$id_empresa=$_SESSION['idEmpresaUsuarioSysGts'];
		}
		$key=md5($key);
		$reckeyq=$c->sa_claves->doQuery($c,new criteria(sqlEQUAL,'modulo',"'".$module."'"));
		$reckeyq->where(new criteria(sqlEQUAL,'id_empleado',$id_empleado));
		$reckeyq->where(new criteria(sqlEQUAL,'id_empresa',$id_empresa));
		//$reckeyq->where(new criteria(" < ",'CURRENT_DATE()','f_expiracion'));
		$rec=$reckeyq->doSelect();
	//	$r->alert($reckeyq->getSelect());
		if($rec){
			if($rec->getNumRows()!=0){
				//$r->alert($key.' '.$rec->clave);
				if($key==$rec->clave){
					return true;
				}
			}
		}
		return false;
	}
	function guardar($form){
            
                global $spanKilometraje;
            
		$r=getResponse();
		//validando las fechas
		$fecha=defined_str_date($form['fecha_venta']);
		$hoy=adodb_mktime(0,0,0,adodb_date('m'),adodb_date('d'),adodb_date('Y'));
	//	$r->alert(date(DEFINEDphp_DATE,$fecha).' '.date(DEFINEDphp_DATE,$hoy));
		if ($fecha>$hoy){
			$r->script('
				_alert("La fecha de Venta no puede ser superior a la fecha actual.");
				$("#field_fecha_venta").addClass("inputERROR");
			');
			return $r;
		}
		
		//removiendo las clases que indican error:
		$r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');
		$c= new connection();
		$c->open();
		
		//verificando la existencia del chasis:
		$recchasis=$c->en_registro_placas->doSelect($c,new criteria(sqlEQUAL,$c->en_registro_placas->chasis,"'".$form['chasis']."'"));
		if($recchasis && $recchasis->getNumRows()==1){
			if($form['id_registro_placas']=='' || $form['id_registro_placas']!=$recchasis->id_registro_placas){
				$r->alert('Chasis duplicado');
				$r->script('$("#field_chasis").addClass("inputNOTNULL");');
			return $r;
			}
			
		}
		$en_registro_placas = new table("en_registro_placas");
		if($form['id_registro_placas']==''){
			$id_empresa=$_SESSION['idEmpresaUsuarioSysGts'];
			$en_registro_placas->add(new field('kilometraje','',field::tInt,$form['kilometraje']));
		}else{
			$id_empresa=$form['id_empresa'];
			
			//actualizando el kilometraje:
			//$r->alert($form['verifica_km']);
			if($form['verifica_km']=='1'){
				$kilometraje=$form['kilometraje'];
				if($kilometraje==''){
					$r->script('$("#field_kilometraje").addClass("inputNOTNULL");_alert("Especifique '.$spanKilometraje.'");');
					return $r;
				}else{
					$recp=$c->en_registro_placas->doSelect($c,new criteria(sqlEQUAL,$c->en_registro_placas->id_registro_placas,$form['id_registro_placas']));
					$kilometraje=field::getTransformType($kilometraje,field::tInt);
					if($recp){
						if($kilometraje < $recp->kilometraje){
							$r->script('$("#field_kilometraje").addClass("inputERROR");_alert("No puede especificar un '.$spanKilometraje.' inferior al &uacute;ltimo establecido");');
							return $r;
						}
					}else{
						$r->alert("Error 001");
						return $r;
					}
				}
			}
			//verifica si existe clave para editar el kilometraje
			if(isset($form['kilometraje'])){
				if(!validate_key($form['clave_km'],'gpv',$c)){
					$r->script('_alert("Clave inv&aacute;lida");');
					return $r;
				}else{
					$en_registro_placas->add(new field('kilometraje','',field::tInt,$form['kilometraje']));
				}
			}
			
			
		}
		
		$en_registro_placas->add(new field('id_registro_placas','',field::tInt,$form['id_registro_placas']));
		$en_registro_placas->add(new field('id_empresa','',field::tInt,$id_empresa));//$_SESSION['idEmpresaUsuarioSysGts']
		$en_registro_placas->add(new field('id_cliente_registro','',field::tInt,$form['id_cliente_registro']));
		$en_registro_placas->add(new field('chasis','',field::tString,$form['chasis']));
		$en_registro_placas->add(new field('placa','',field::tString,$form['placa']));
		
		$en_registro_placas->add(new field('color','',field::tString,$form['color']));
		$en_registro_placas->add(new field('id_unidad_basica','',field::tString,$form['id_unidad_basica']));
		$en_registro_placas->add(new field('parcial','',field::tInt,'0'));
		$en_registro_placas->add(new field('fecha_venta','',field::tDate,$form['fecha_venta']));
		
		$c->begin();
		if($form['id_registro_placas']==''){
			//$r->alert($en_registro_placas->getInsert($c,$en_registro_placas->id_registro_placas));return $r;
			if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
				$c->rollback();
				return $r;
			}
			$result=$en_registro_placas->doInsert($c,$en_registro_placas->id_registro_placas);
		}else{
			if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
				$c->rollback();
				return $r;
			}
			$result=$en_registro_placas->doUpdate($c,$en_registro_placas->id_registro_placas);
		}
		if($result===true){
			$r->alert('Guardado con exito');
			$r->script('cargar();close_window("edit_window");');
			$c->commit();
			
		}else{
			$c->rollback();
			//$r->alert(utf_export($result));
			foreach ($result as $ex){
				if($ex->type==errorMessage::errorNOTNULL){					
						//$r->script('obj("'.$ex->getObject()->getName().'").className="inputNOTNULL";');
					$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputNOTNULL");');					
				}elseif($ex->type==errorMessage::errorType){
					//$r->script('obj("'.$ex->getObject()->getName().'").className="inputERROR";');
					$r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputERROR");');
				}else{
					if($ex->numero==connection::errorUnikeKey){
						$r->script('_alert("No se puede ingresar el veh&iacute;culo debido a que el chasis o la placa ya existen registrados");');
						return $r;
					}/*else{
						$r->alert($ex);
					}*/
				}
					//$r->alert(utf_export($ex->getObject()->getName()));
			}
			$r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
		}
		
		$c->close();
		return $r;
	}
	
	function cargar_cliente_pago($idc,$c=null){
		$r=getResponse();
		//$r->alert($idc);return $r;
		if($c==null){
			$c= new connection();
			$c->open();
		}
		$q = new query($c);
		$q->add($c->cj_cc_cliente);
		$q->where(new criteria(sqlEQUAL,$c->cj_cc_cliente->id,"'".$idc."'"));
		$rec=$q->doSelect();
		if($rec){
			if($rec->status=='Inactivo'){
				$r->script('_alert("Debe completar los datos del registro del cliente para poder crear el Vale de recepci&oacute;n");');
				//return $r;
			}
			$info=$rec->lci.'-'.$rec->ci.': '.$rec->apellido.' '.$rec->nombre;
			$r->assign('id_cliente_registro','value',$rec->id);
			$r->assign('info_cliente',inner,$info);
			$r->script('
				close_window("xajax_dialogo_cliente");
				//obj("cedula_cliente_pago").readOnly=true;
				//window.frames["inventario"].probando();
			');
		}
		//$c->close();
		return $r;
	}
        
        function comprobarClaveAntes($clave){
            $r = getResponse();
            
            $c = new connection();
            $c->open();

            $id_user=$_SESSION['idUsuarioSysGts'];

            $recemp=$c->execute("select id_empleado from pg_usuario where id_usuario=".$id_user.";");
            $id_empleado=$recemp['id_empleado'];
		
            $id_empresa=$_SESSION['idEmpresaUsuarioSysGts'];
		
            $clave=md5($clave);
            $reckeyq=$c->sa_claves->doQuery($c,new criteria(sqlEQUAL,'modulo',"'gpv'"));
            $reckeyq->where(new criteria(sqlEQUAL,'id_empleado',$id_empleado));
            $reckeyq->where(new criteria(sqlEQUAL,'id_empresa',$id_empresa));
            
            $rec=$reckeyq->doSelect();
            
            if($rec){
                    if($rec->getNumRows()!=0){                       
                            if($clave==$rec->clave){
                                    return $r->script('send_key();');
                            }else{
                                return $r->alert('Clave Incorrecta');
                            }
                    }else{
                        return $r->alert('Usted no posee acceso');
                    }
            }
            
        }
        
	
	xajaxRegister('guardar');
	xajaxRegister('cargar_listas');
	xajaxRegister('cargar_cliente_pago');
	xajaxRegister('cargar_lista_marca');
	xajaxRegister('cargar_lista_modelo');
	xajaxRegister('cargar_lista_version');
	xajaxRegister('cargar_lista_unidad_basica');
	xajaxRegister('cargar_vehiculo');
	xajaxRegister('load_page');
        
        xajaxRegister('comprobarClaveAntes');
        
	xajaxProcess();
	
	includeDoctype();
	
	$c= new connection();
	$c->open();
	$list_color=inputSelect('color',$c->an_color->doSelect($c)->getAssoc('nom_color','nom_color'));
		
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
		
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Registro de Veh&iacute;culo</title>
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
		.key_pass{
			background-image:url(<?php echo getUrl('img/iconos/key.png'); ?>);
			background-repeat:no-repeat;
			background-position: 1% 50%;
			/*padding-right:18px;*/
			padding-left:18px;
			min-height:16px;
		}
		</style>
		
		<script type="text/javascript">
			//detectEditWindows({edit_window:'guardar'});
		
			var datos = {
				fecha: 'null',
				date:new Date(),
				page:0,
				maxrows:15,
				order:null,
				ordertype:null,
				busca:''
			}
			
			function cargar(){
			//alert('fd');
				xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','busca='+datos.busca);
				close_window("edit_window");
			}
			
			function agregar(add){
				setDivWindow('edit_window','title_window',true);
				
				if(add){
					obj('color').value="";
					$('#edit_window input').val('');
					$('#edit_window #capa_id_art_inventario').html('');
					$("#edit_window #subtitle").html("Agregar");
					$("#edit_window input").not("#fecha_venta").attr("readonly","");
					$("#edit_window select").attr("disabled","");
					$("#edit_window #imprimir").attr("disabled","true");
					$("#edit_window #guardar").attr("disabled","");
					$("#edit_window #fecha_venta").attr("disabled","");
					$("#info_cliente").html("");
					$("#edit_window #buscar_cliente").attr("disabled","");
					xajax_cargar_listas();
					obj("placa").focus();
					$('#codigo_barras_vehiculo').html('');
					obj("kilometraje").disabled=false;
					obj('verifica_km').checked=false;
				}else{
					$("#edit_window #kilometraje").attr("disabled","true");
					obj('verifica_km').checked=true;
				}
				obj('modificar_km').disabled= !obj('kilometraje').disabled;
				obj('verifica_km').disabled=true;
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
			
			function buscar_cliente_pago(){
				xajax_dialogo_cliente(0,10,'cj_cc_cliente.ci','','cliente_dc',
						'busqueda=,callback=xajax_cargar_cliente_pago,parent=principal');
			}
			
			function imprimir_vehiculo(id,id_empresa){
				var id_empresa= id_empresa || false;
				/*var f = obj('formulario').innerHTML;
				alert(f);
				var w=window.open('xajax_print.php','print');
				w.document.getElementById('print_data').innerHTML=f;*/
				if(id==null){
					var id=obj("id_registro_placas").value;
					id_empresa=obj("id_empresa").value;
				}
				
				//'sa_etiqueta_pdf.php?id='+id+'&ide='+id_empresa
				//'sa_etiqueta_pdf.php?id='+id+'&ide='+id_empresa
				var window=setPopup('sa_etiqueta_pdf.php?id='+id+'&ide='+id_empresa,'print',
					{
						center:'both',
						dialog:true,
						width:800,
						height:600
					}
				);
				//window.focus();
			}
			var funct=function(){};
		function get_key(func,title){
			var title= title || false;
			if(title==false){
				title='Introduzca su Clave de operaciones:';
			}
			var dk=obj('key');
			funct=func;
			obj('key_title').innerHTML=title;
			setWindow('key_window','title_key_window',true);
			dk.value="";
			dk.focus();
		}
		function send_key(){
			var dk=obj('key');
			if(dk.value!=''){
				funct(dk.value);
				funct=function(){};
				close_window('key_window');
			}else{
				_alert('Introduzca la clave');
				dk.focus();
			}
		}
		
		function guardar_clave(clave){
			var km=obj('kilometraje');
			//var checke= obj('modificar_km');
			if(clave!=''){
				km.disabled=false;
				obj('verifica_km').disabled=false;
				obj('clave_km').value=clave;
			}/*else{
				cheke.checked=false;
			}
			km.disabled= !checke.checked;*/
		}
                
                
                function numeros(e) {
                    tecla = (document.all) ? e.keyCode : e.which;
                    if (tecla == 0 || tecla == 8)
                        return true;
                    patron = /[0-9]/;
                    te = String.fromCharCode(tecla);
                    return patron.test(te);
                }
			
		</script>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
	<div style="width:960px; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Mantenimiento de Servicios</span><br />
			<span class="subtitulo_pagina" >(Registro de Veh&iacute;culo)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
<div id="principal">
	<div>
		<button type="button" value="Nuevo" onClick="agregar(true);" ><img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" />Nuevo</button>
		<input type="text" id="busca" onkeypress="keyEvent(event,buscar);" />
		
		
		<button type="button" value="buscar" title="Buscar" onClick="buscar();" ><img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
		<button type="button" value="reset" title="Restablecer" onClick="restablecer();" ><img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
		
		<hr />
	</div>
	<div id='capaTabla'></div>
	<div align="center" id='paginador'></div>
</div>
<!--MARCO PRINCIPAL-->
<div class="window" id="edit_window" style="min-width:700px;max-width:700px;visibility:hidden;">
	<div class="title" id="title_window">
		<span id="subtitle"></span>&nbsp;veh&iacute;culo		
	</div>
	<div class="content">
		
		<form id="formulario" name="formulario" onsubmit="return false;" style="margin:0px;padding:0px;">
			<input type="hidden" id="id_registro_placas" name="id_registro_placas" />
			<input type="hidden" id="id_cliente_registro" name="id_cliente_registro" />
			<input type="hidden" id="id_empresa" name="id_empresa" />
			<table id="datos_vehiculo" class="insert_table" style="width:100%;">
				<tbody>
					<tr>
						<td id="codigo_barras_vehiculo" class="label" colspan="4">
													
						</td>
					</tr>
					<tr>
						<td class="label">
							Placa
						</td>
						<td class="field" id="field_placa">
							<input type="text" name="placa" id="placa" maxlength="8" />
						</td>
						<td class="label">
							Chasis
						</td>
						<td class="field" id="field_chasis">
							<input type="text" name="chasis" id="chasis" maxlength="19" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Marca
						</td>
						<td class="field" id="field_id_marca">
							
						</td>
						<td class="label">
							Modelo
						</td>
						<td class="field" id="field_id_modelo">
						
						</td>
					</tr>
					<tr>
						<td class="label">
							Versi&oacute;n
						</td>
						<td class="field" id="field_id_version">
							
						</td>
						<td class="label">
							Unidad
						</td>
						<td class="field" id="field_id_unidad_basica">
						
						</td>
					</tr>
					<tr>
						<td class="label">
							Color
						</td>
						<td class="field" id="field_color">
							<!--<input type="text" name="color" id="color" />-->
							<?php echo $list_color ?>
						</td>
						<td class="label">
							<?php echo $spanKilometraje; ?>
						</td>
						<td class="field" id="field_kilometraje">
							<input type="text" name="kilometraje" id="kilometraje" onkeypress="return numeros(event);" readonly />
							
								<button onclick="get_key(guardar_clave,'Introduzca la clave del gerente de Postventa:');" type="checkbox" id="modificar_km" name="modificar_km" ><img src="<?php echo getUrl('img/iconos/edit.png'); ?>" alt="editar" /> Editar</button><br />
							<label>
								<input type="checkbox" id="verifica_km" name="verifica_km" value="1" /> Verificar que no sea Menor al &Uacute;ltimo establecido.
							</label>
							<input style="display:none;" type="password" id="clave_km" name="clave_km" />
						</td>
					</tr>
					<tr>
						<td class="label">
							Fecha de Venta
						</td>
						<td class="field" id="field_fecha_venta">
							<input type="text" name="fecha_venta" id="fecha_venta" readonly />
							<script type="text/javascript">
							Calendar.setup({
							inputField : "fecha_venta", // id del campo de texto
							ifFormat : "%d-%m-%Y", // formato de la fecha que se escriba en el campo de texto
							button : "fecha_venta" // el id del bot�n que lanzar� el calendario
							});
							</script>
						</td>
						<td class="label">
							Cliente
						</td>
						<td class="field" id="field_id_cliente_registro">
							<button title="Buscar cliente" id="buscar_cliente" name="buscar_cliente" type="button" onclick="buscar_cliente_pago();"><img border="" src="<?php echo getUrl('img/iconos/find.png'); ?>"/></button>
							<span id="info_cliente"></span>
						</td>
					</tr>
				</tbody>
				<tfoot class="noprint">
					<tr>
						<td colspan="3">
							<div class="leyend">
								<span class="inputNOTNULL"></span> Valor Requerido
							</div>
							<div class="leyend">
								<span class="inputERROR"></span> Valor Incorrecto
							</div>
						</td>
						<td  align="right">
						<button type="submit" id="imprimir" onclick="imprimir_vehiculo();" ><img border="0" src="<?php echo getUrl('img/iconos/print.png'); ?>" />Imprimir</button>
							<button type="submit" id="guardar" onclick="xajax_guardar(xajax.getFormValues('formulario'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" />Guardar</button>
						</td>
					</tr>
				</tfoot>
			</table>
		</form>		
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('edit_window');" border="0" />
</div>	

<div class="window" id="key_window" style="z-index:100;top:-1000px;left:0px;max-width:400px;min-width:400px;visibility:hidden;border-color:#FEB300;">
	<div class="title" id="title_key_window" style="background:#FEE8B3;color:#000000;">
		<div class="key_pass" id="key_title" style="padding-left:24px;"></div>
	</div>
	<div class="content">
		<div class="nohover">
			<table class="insert_table">
			<tbody>
				<tr>
					<td width="30%"  class="label">Clave:</td>
					<td class="field" style="text-align:center;">
						<input style="width:95%;border:0px;" type="password" name="key" id="key" maxlength="30" onkeypress="keyEvent(event,send_key);" />
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:right;padding:2px;">
						<span style="padding:2px;">
							<button onclick="close_window('key_window');funct=function(){};obj('key').value='';"><img alt="Cancelar" src="<?php echo getUrl('img/iconos/delete.png'); ?>" class="image_button" />Cancelar</button>
						</span>
						<span style="padding:2px;">
							<button onclick="xajax_comprobarClaveAntes(document.getElementById('key').value);"><img alt="Aceptar" src="<?php echo getUrl('img/iconos/select.png'); ?>" class="image_button" />Aceptar</button>
						</span>
					</td>
				</tr>
			</tbody>
			</table>
		</div>
	</div>
	<img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('key_window');funct=function(){};obj('key').value='';" border="0" />
</div>
<div id="cliente_dc"></div>

</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">
		cargar();
	</script>
	</body>
</html>

