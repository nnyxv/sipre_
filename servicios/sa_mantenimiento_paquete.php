<?php //error_reporting(E_ALL);
    session_start();
    require_once ("../connections/conex.php");
    
    define('PAGE_PRIV','sa_mantenimiento_paquete');//nuevo gregor
	//define('PAGE_PRIV','sa_paquete');//anterior
	
    require_once("../inc_sesion.php");
    require_once("control/main_control.inc.php");
    require_once("control/iforms.inc.php");
    require_once("control/funciones.inc.php");
    require_once("controladores/ac_sa_mantenimiento_paq.php");

	//include("../connections/conex.php");//usa el vldtipodato que necesita ac_iv_general
	include("controladores/ac_iv_general.php"); //tiene el cargaLstEmpresaFinal

    function load_page($page,$maxrows,$order,$ordertype,$capa,$args=''){
        $r= getResponse();
        if (!xvalidaAcceso($r,PAGE_PRIV)){
                $r->assign($capa,inner,'Acceso denegado');
                return $r;
        }

        $c = new connection();
        $c->open();

        $argumentos=paginator::getExplodeArgs($args);

        $sa_paquetes = $c->sa_v_paquetes;

        $query = new query($c);
        $query->add($sa_paquetes);

        if($argumentos['busca']!=''){
            $query->where(
                    new criteria(
                            sqlOR, array(
                                    new criteria(' like ',$sa_paquetes->descripcion_paquete,"'%".$argumentos['busca']."%'"),
                                    new criteria(' like ',$sa_paquetes->codigo_paquete,"'%".$argumentos['busca']."%'"),
                                    )
                                )
                        );			

            $filtro='<span>Filtado por: '.$argumentos['busca'].'</span>';
        }
		
		$query->where(new criteria(sqlEQUAL,$sa_paquetes->id_empresa,$_SESSION['idEmpresaUsuarioSysGts']));

        $paginador = new paginator('xajax_load_page',$capa,$query,$maxrows);
        $rec=$paginador->run($page,$order,$ordertype,$args);

        if($rec){
            if($rec->getNumRows()==0){
                $html.='<div class="order_empty">No se han registrado registros</div>';
            }else{
                $html.='<table class="order_table">
                            <thead>
                                <tr class="xajax_order_title">
                                    <td>'.$paginador->get($sa_paquetes->id_paquete,'ID').'</td>
                                    <td>'.$paginador->get($sa_paquetes->codigo_paquete,'C&oacute;digo').'</td>
                                    <td>'.$paginador->get($sa_paquetes->descripcion_paquete,'Descripci&oacute;n').'</td>
                                    <td>'.$paginador->get($sa_paquetes->nombre_empresa_sucursal,'Empresa').'</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
									<td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                            </thead>
                            <tbody>';
                $class='';
                foreach($rec as $v){
                    if ($rec->parcial == '1'){
                        $parcial = 'Si';
                    }else{
                        $parcial = 'No';
                    }
					
                    $html.='    <tr class="'.$class.'">
                                    <td align="center">'.$rec->id_paquete.'</td>
                                    <td align="center">'.$rec->codigo_paquete.'</td>
                                    <td align="center">'.$rec->descripcion_paquete.'</td>
                                    <td align="center">'.$rec->nombre_empresa_sucursal.'</td>
                                    <td align="center"><img src="'.getUrl('img/iconos/view.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_paquete.',\'view\');"></td>
                                    <td align="center"><img src="'.getUrl('img/iconos/edit.png').'" width="16" border="0" onClick="xajax_cargar('.$rec->id_paquete.',\'edit\');"></td>
                                    <td align="center"><img src="'.getUrl('img/iconos/delete.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Eliminar?\')) xajax_cargar('.$rec->id_paquete.',\'delete\');""></td>
									
									 <td align="center" title="Actualizar Repuestos"><img src="'.getUrl('img/iconos/ico_cambio_gris.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Actualizar el Precio de los Repuestos?\')) actualizarRpto(true,'.$rec->id_paquete.');""></td>
									 <td align="center" title="Actualizar Tempario"><img src="'.getUrl('img/iconos/ico_cambio_gris.png').'" border="0" onClick=" if(_confirm(\'&iquest;Desea Actualizar Mano De Obra del Paquete?\')) xajax_actualizar_mo('.$rec->id_paquete.');""></td>
                                </tr>';
                    if($class==''){
                        $class='impar';
                    }else{
                        $class='';
                    }
                }
                $html.='    </tbody>
                        </table>';
            }
        }

        $r->assign($capa,inner,$html);
        $r->assign('paginador',inner,'<hr>Mostrando '.$paginador->count.' resultados de un total de '.$paginador->totalrows.' '.$paginador->getPages().'&nbsp;'.$filtro);

        $r->assign('campoFecha','value',$fec);
        $r->script("datos.page=".$page.";
                    datos.maxrows=".$maxrows.";
                    datos.order='".$order."';
                    datos.ordertype='".$ordertype."';
                    datos.busca='".$argumentos['busca']."';
                    ");
        $c->close();
        return $r;
    }

    function cargar($id, $mode='view'){
        $r=getResponse();
        if (!xvalidaAcceso($r,PAGE_PRIV)){
            return $r;
        }

        $view=array('add'=>'','view'=>'true');

        if($mode=='view'){
            $r->script('$("#edit_window_2 #subtitle").html("Ver");');
        }else{
            $r->script('$("#edit_window_2 #subtitle").html("Editar");');			
        }

        $c=new connection();
        $c->open();

        $q=new query($c);
        $q->add($c->sa_v_paquetes);
        $q->where(new criteria(sqlEQUAL,$c->sa_v_paquetes->id_paquete,$id));

        if($mode!='delete'){
            $rec=$q->doSelect();
            if($rec){
                $r->assign('id_paquete','value',$rec->id_paquete);
                $r->assign('id_empresa','value',$rec->id_empresa);
                $r->assign('codigo_paquete','value',$rec->codigo_paquete);
                $r->assign('descripcion_paquete','value',$rec->descripcion_paquete);

                $sa_v_paq_repuestos=$c->sa_paquete_repuestos;

                $iv_articulos= new table('iv_articulos','',$c);

                $join= $iv_articulos->join($sa_v_paq_repuestos,$iv_articulos->id_articulo,$sa_v_paq_repuestos->id_articulo);

                $qdet=new query($c);
                $qdet->add($join);
                $qdet->where(new criteria(sqlEQUAL,$sa_v_paq_repuestos->id_paquete,$rec->id_paquete));

                $recdet=$qdet->doSelect();
                if($recdet){
                    foreach($recdet as $det){
                        $scriptdet.="articulo_add({
                                    id_articulo:".$det->id_articulo.",
                                    codigo:'".$det->codigo_articulo."',
                                    descripcion:'".htmlentities($det->descripcion)."',
                                    iva:'".tieneIva($det->id_articulo)."',
                                    cantidad:'"._formato($det->cantidad,0)."',
									precio:'".$det->precio."',
                                    action:'add',
                                    id_paq_repuesto:".$det->id_paq_repuesto."
                                    });";
                    }
                }
                $sa_v_paq_tempario=$c->sa_paq_tempario;

                $sa_tempario= new table('sa_tempario','',$c);
                $join= $sa_tempario->join($sa_v_paq_tempario,$sa_tempario->id_tempario,$sa_v_paq_tempario->id_tempario);

                $qdet=new query($c);
                $qdet->add($join);
                $qdet->where(new criteria(sqlEQUAL,$sa_v_paq_tempario->id_paquete,$rec->id_paquete));

                $recdet=$qdet->doSelect();
                if($recdet){
                    foreach($recdet as $det){
                        $scriptdet.="tempario_add({
                                    id_tempario:".$det->id_tempario.",
                                    unidad:'".$det->codigo_tempario."',
                                    descripcion:'".$det->descripcion_tempario."',
									ut:'".$det->ut."',
									costo:'".$det->costo."',
                                    action:'add',
                                    id_paq_tempario:".$det->id_paq_tempario."
                                    });";
                    }
                }

                $sa_v_paq_unidad=$c->sa_paq_unidad;

                $sa_v_unidad_basica= new table('sa_v_unidad_basica','',$c);
                $join= $sa_v_unidad_basica->join($sa_v_paq_unidad,$sa_v_unidad_basica->id_unidad_basica,$sa_v_paq_unidad->id_unidad_basica);

                $qdet=new query($c);
                $qdet->add($join);
                $qdet->where(new criteria(sqlEQUAL,$sa_v_paq_unidad->id_paquete,$rec->id_paquete));

                $recdet=$qdet->doSelect();
                if($recdet){
                    foreach($recdet as $det){
                        $scriptdet.="unidad_add({
                                    id_unidad_basica:".$det->id_unidad_basica.",
                                    unidad:'".$det->nombre_unidad_basica."',
                                    nom_modelo:'".$det->nom_modelo."',
                                    unidad_completa:'".$det->unidad_completa."',
                                    action:'add',
                                    id_paq_unidad:".$det->id_paq_unidad."
                                    });";
                        }
                }
            }
            $r->script('agregar2(false);');
            $r->script($scriptdet);
            $r->script('$("#edit_window_2 input").attr("readonly","'.$view[$mode].'");
                        $("#edit_window_2 select").attr("disabled","'.$view[$mode].'");
                        $("#edit_window_2 button").attr("disabled","'.$view[$mode].'");
                        ');
        }else{
            if (!xvalidaAcceso($r,PAGE_PRIV,eliminar)){
                return $r;
            }

            $c->begin();
            $rec=$c->sa_paquetes->doDelete($c,new criteria(sqlEQUAL,$c->sa_paquetes->id_paquete,$id));

            if($rec===true){
                $c->commit();
                $r->script('_alert("Registro eliminado con &eacute;xito");cargar();');
            }else{
                $r->script('_alert("No se puede eliminar el registro, elimine primero las unidades asociadas o es posible que el mismo ya est&aacute; siendo utilizado");');
            }
        }
        $c->close();
        return $r;
    }

    function guardar($form){
		
        $r=getResponse();
        
        $r->script('$(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");');

        $c= new connection();
        $c->open();

        $sa_paquetes = new table("sa_paquetes");
        $sa_paquetes->add(new field('id_paquete','',field::tInt,$form['id_paquete']));
        $sa_paquetes->add(new field('id_empresa','',field::tInt,$form['id_empresa']));
        $sa_paquetes->add(new field('codigo_paquete','',field::tString,$form['codigo_paquete']));
        $sa_paquetes->add(new field('descripcion_paquete','',field::tString,$form['descripcion_paquete']));
        $sa_paquetes->insert('fecha_rev','NOW()',field::tFunction);

        $c->begin();

        $id_paquete=$form['id_paquete'];

        if($form['id_paquete']==''){
            if (!xvalidaAcceso($r,PAGE_PRIV,insertar)){
                $c->rollback();
                return $r;
            }

            $result=$sa_paquetes->doInsert($c,$sa_paquetes->id_paquete);
            $id_paquete=$c->soLastInsertId();
        }else{
            if (!xvalidaAcceso($r,PAGE_PRIV,editar)){
                $c->rollback();
                return $r;
            }
            $result=$sa_paquetes->doUpdate($c,$sa_paquetes->id_paquete);
        }

        if($result===true){
            if(isset($form['id_paq_repuesto'])){
                foreach($form['id_paq_repuesto'] as $k => $v){
                    $sql='';
                    if($form['action'][$k]=='add'){
                        if($form['cantidad'][$k]=='0' || intval($form['cantidad'][$k])<=0){
                            $r->script('$("#row'.($k+1).'").addClass("inputNOTNULL");');
                            $error=true;
                            continue;
                        }
						
						
						
						
                        if($form['id_paq_repuesto'][$k]==''){
                            $sql=sprintf("INSERT INTO sa_paquete_repuestos
                                            (id_paq_repuesto,id_paquete,id_articulo,cantidad, precio)
                                            VALUES (NULL , %s, %s, %s, %s);",
                                            $id_paquete,
                                            $form['id_articulo'][$k],
                                            field::getTransformType($form['cantidad'][$k],field::tFloat),
											field::getTransformType($form['precio'][$k],field::tFloat)
                                        );
										
                        } 
						
						else{
                            $sql=sprintf("UPDATE sa_paquete_repuestos SET cantidad=%s, precio=%s where id_paq_repuesto=%s;",
                                            field::getTransformType($form['cantidad'][$k],field::tFloat),
											field::getTransformType($form['precio'][$k],field::tFloat),
                                            $form['id_paq_repuesto'][$k]
                                        );
                        }
                    }else{
                        if($form['id_paq_repuesto'][$k]!=''){
                            $sql=sprintf("DELETE FROM sa_paquete_repuestos where id_paq_repuesto=%s;",
                                            $form['id_paq_repuesto'][$k]
                                        );
                        }
                    }
                    if($sql!='' && !$error){
                        $resultd = $c->soQuery($sql);
                        if(!$resultd){
                            $error=true;
                        }
                    }
                }
            } 
			
			
			
			
            if(isset($form['id_paq_tempario'])){
                foreach($form['id_paq_tempario'] as $k => $v){
                    $sql='';
                    if($form['actiont'][$k]=='add'){					
						
                        if($form['id_paq_tempario'][$k]==''){
                            $sql=sprintf("INSERT INTO sa_paq_tempario(id_paq_tempario,id_paquete,id_tempario, ut, costo) VALUES (NULL , %s, %s, %s, %s);",
                                            $id_paquete,
                                            $form['id_tempario'][$k],
											field::getTransformType($form['ut'][$k],field::tFloat),
											field::getTransformType($form['costo'][$k],field::tFloat)
                                        ); 
										
                        }
						else{
                            $sql=sprintf("UPDATE sa_paq_tempario SET ut=%s, costo=%s where id_paq_tempario=%s;",
                                            field::getTransformType($form['ut'][$k],field::tFloat),
											field::getTransformType($form['costo'][$k],field::tFloat),
                                            $form['id_paq_tempario'][$k]
                                        );
							}
                    }else{
                        if($form['id_paq_tempario'][$k]!=''){
                            $sql=sprintf("DELETE FROM sa_paq_tempario where id_paq_tempario=%s;",
                                            $form['id_paq_tempario'][$k]
                                        );
                        }
                    }
                    if($sql!='' && !$error){
                        $resultd = $c->soQuery($sql);
						
                        if(!$resultd){
                            $error=true;
                        }
                    }
                }
            }
            if(isset($form['id_paq_unidad'])){
                foreach($form['id_paq_unidad'] as $k => $v){
                    $sql='';
                    if($form['actionm'][$k]=='add'){
                        if($form['id_paq_unidad'][$k]==''){
                            $sql=sprintf("INSERT INTO sa_paq_unidad(id_paq_unidad,id_paquete,id_unidad_basica) VALUES (NULL , %s, %s);",
                                            $id_paquete,
                                            $form['id_unidad_basica'][$k]
                                        );
                        }
                    }else{
                        if($form['id_paq_unidad'][$k]!=''){
                            $sql=sprintf("DELETE FROM sa_paq_unidad where id_paq_unidad=%s;",
                                            $form['id_paq_unidad'][$k]
                                        );
                        }
                    }
                    if($sql!='' && !$error){
                        $resultd = $c->soQuery($sql);
						
                        if(!$resultd){
                            $error=true;
                        }
                    }
                }
            }
            if(!$error){
                $r->alert('Guardado con exito');
                $r->script('cargar();close_window("edit_window_2");');
                $c->commit();
            }else{
                $r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
            }
        }else{
            $c->rollback();
            foreach ($result as $ex){
                if($ex->type==errorMessage::errorNOTNULL){
                    $r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputNOTNULL");');
                }elseif($ex->type==errorMessage::errorType){
                    $r->script('$("#field_'.$ex->getObject()->getName().'").addClass("inputERROR");');
                }else{
                    if($ex->numero==connection::errorUnikeKey){
                        $r->script('_alert("duplicado");');
                        return $r;
                    }
                }
            }
            $r->alert('Verifique los datos ingresados: Rojo: Requerido / Azul: Datos Incorrecto');
        }
        $c->close();
        return $r;
    }

    function agregar_articulo($id,$form,$add=true){
        $r=getResponse();

        $c= new connection();
        $c->open();

        $rec= $c->sa_v_articulos->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_articulos->id_articulo,$id));
        //return var_dump($rec->con->tables['sa_v_articulos']->getSelect($c));
        if($rec){
            $existencia=true;
            if(isset($form['id_unidad_basica'])){
                $reco=$c->sa_v_articulo_modelo_compatible->
                            doSelect($c, new criteria(sqlEQUAL,$c->sa_v_articulo_modelo_compatible->id_articulo,$id));
                if($reco->getNumRows()!=0){
                    foreach($reco as $reg){
                        $unidades[]=$reg->id_unidad_basica;
                    }
                    foreach($form['id_unidad_basica'] as $k => $v){
                        $exits=true;
                        if($form['actionm'][$k]=='add' ){
                            $exits=in_array($v,$unidades);
                            if($exits!=true){
                                $unidadesIncompatibles[$v] = nombreUnidad($v); 
                                break;
                            }
                        }
                    }
                    if($exits==false){
                        $existencia=false;
                    }
                }
            }
            if(!$existencia){
                $unidadesNombre = implode(",",$unidadesIncompatibles);
                $r->alert("Existe un Modelo que no es compatible con el Artículo seleccionado, nombres: \n ".$unidadesNombre."");
                
            }else{
                if($add===true){
                    $r->script("articulo_add({
                                id_articulo:".$rec->id_articulo.",
                                codigo:'".$rec->codigo_articulo."',
                                descripcion:'".htmlentities($rec->descripcion)."',
                                cantidad:'',
                                iva:'".tieneIva($rec->id_articulo)."',
								precio:'".precio($rec->id_articulo)."',
                                action:'add',
                                id_paq_repuesto:''
                                });");
                }else{
                     $r->script("var row= obj('row".$add."');
                                row.style.display='';
                                var action=obj('action".$add."');
                                action.value='add';
                                ");
                }

            }
        }
        
       
        $c->close();
        
        return $r;
    }

    function agregar_tempario($id,$form,$add=true){
        //var_dump($form);
        $r=getResponse();        
        $c= new connection();
        $c->open();

        $rec= $c->sa_v_tempario->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_tempario->id_tempario,$id));
		
		
		
        if($rec){
            $existencia=true;
            if(isset($form['id_unidad_basica'])){
                $reco=$c->sa_v_tempario_ut_modelo_compatible->
                                    doSelect($c, new criteria(sqlEQUAL,$c->sa_v_tempario_ut_modelo_compatible->id_tempario,$id));
                if($reco->getNumRows()!=0){
                    foreach($reco as $reg){
                        $unidades[]=$reg->id_unidad_basica;
                    }
                    foreach($form['id_unidad_basica'] as $k => $v){
                        $exits=true;
                        if($form['actionm'][$k]=='add' ){
                            $exits=in_array($v,$unidades);
                            if($exits!=true){
                                $unidadesIncompatibles[] = nombreUnidad($v);
                                break;
                            }
                        }
                    }
                    if($exits==false){
                        $existencia=false;
                    }
                }if($exits==false){
                    $existencia=false;
                }
            }
            if(!$existencia){
                $unidadesNombre = implode(",",$unidadesIncompatibles);
                $r->alert("Existe un Modelo que no es compatible con la Posición seleccionada, nombres: \n ".$unidadesNombre."");
            }else{
                if($add===true){
			
		      $recUni= $c->sa_tempario_det->doSelect($c,new criteria(sqlEQUAL,$c->sa_tempario_det->id_tempario,$id));
                    $r->script("tempario_add({
                                id_tempario:".$rec->id_tempario.",
                                unidad:'".$rec->codigo_tempario."',                                
                                descripcion:'".$rec->descripcion_tempario."',
                                ut:'".$recUni->ut."',
				    costo:'".$rec->costo."',
                                action:'add',
                                id_paq_tempario:''
                                });
                                ");
                }else{
                    $r->script("var row= obj('rowt".$add."');
                                row.style.display='';
                                var action=obj('actiont".$add."');
                                action.value='add';
                                ");
                }
            }
        }
        $c->close();
        return $r;
    }

    function agregar_unidad($id,$form,$add=true){
        $r=getResponse();
        $c= new connection();
        $c->open();

        $rec= $c->sa_v_unidad_basica->doSelect($c,new criteria(sqlEQUAL,$c->sa_v_unidad_basica->id_unidad_basica,$id));
        if($rec){
            $existencia=true;
            if(isset($form['id_articulo'])){
                foreach($form['id_articulo'] as $k => $v){
                    $exits=true;
                    if($form['action'][$k]=='add'){
                        $reco=$c->sa_v_articulo_modelo_compatible->
                                        doSelect($c, new criteria(sqlEQUAL,$c->sa_v_articulo_modelo_compatible->id_articulo,$v));
                        if($reco->getNumRows()!=0){
                            $exits=false;
                            foreach($reco as $reg){
                                $exits=($id==$reg->id_unidad_basica);
                                if($exits==true){
                                    break;
                                }
                            }
                            if($exits==false){  
                                $articulosIncompatibles[] = nombreArticulo($v);
                                break;
                            }
                        }
                    }
                }
                if($exits==false){
                    $existencia=false;
                }
            }
            if(!$existencia){
                $codigoArticulo = implode(",",$articulosIncompatibles);
                $r->alert("Existe un artículo que no es compatible con el Unidad seleccionada, Códigos: \n ".$codigoArticulo."");                
            }
            if(isset($form['id_tempario'])){
                foreach($form['id_tempario'] as $k => $v){
                    $exits=true;
                    if($form['actiont'][$k]=='add'){
                        $reco=$c->sa_tempario_det->doSelect($c, new criteria(sqlEQUAL,$c->sa_tempario_det->id_tempario,$v));
                        if($reco->getNumRows()!=0){
                            $exits=false;
                            foreach($reco as $reg){
                                $exits=($id==$reg->id_unidad_basica);
                                if($exits==true){
                                    break;
                                }
                            }
                            if($exits==false){ 
                                $posicionIncompatible[] = nombreTempario($v);
                                break;
                            }
                        }
                    }
                }
                if($exits==false){
                    $existencia=false;
                }
            }
        }
        if(!$existencia){
            $temparioCodigo = implode(",",$posicionIncompatible);
             $r->alert("Existe una posición que no es compatible con el Unidad seleccionada, Códigos: \n ".$temparioCodigo."");
            
        }else{
            if($add===true){
                $r->script("unidad_add({
                            id_unidad_basica:".$rec->id_unidad_basica.",
                            unidad:'".$rec->nombre_unidad_basica."',
                            nom_modelo:'".$rec->nom_modelo."',
                            unidad_completa:'".$rec->unidad_completa."',
                            action:'add',
                            id_paq_unidad:''
                            });
                            ");
            }else{
                $r->script("var row= obj('rowm".$add."');
                            row.style.display='';
                            var action=obj('actionm".$add."');
                            action.value='add';
                            ");
            }
        }

        $c->close();
        return $r;
    }

function precio($idArticulo){
	
		$query = sprintf("SELECT precio FROM iv_articulos_precios WHERE id_articulo = %s AND id_precio = 1",$idArticulo); 
		$rs = mysql_query($query) or die(mysql_error());
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		return $row['precio'];
	
	
	}
        
function nombreUnidad($idUnidad){
    $query = sprintf("SELECT nombre_unidad_basica FROM sa_v_unidad_basica WHERE id_unidad_basica = %s LIMIT 1",$idUnidad); 
    $rs = mysql_query($query) or die(mysql_error());
    if (!$rs) die(mysql_error()."\n\nLine: ".__LINE__);
    $row = mysql_fetch_array($rs);
    
    return $row["nombre_unidad_basica"];
}

function nombreTempario($idTempario){
    $query = sprintf("SELECT descripcion_tempario, codigo_tempario FROM sa_tempario WHERE id_tempario = %s LIMIT 1",$idTempario); 
    $rs = mysql_query($query) or die(mysql_error());
    if (!$rs) die(mysql_error()."\n\nLine: ".__LINE__);
    $row = mysql_fetch_array($rs);
    
    return $row["codigo_tempario"];
}


function nombreArticulo($idArticulo){
    $query = sprintf("SELECT codigo_articulo FROM iv_articulos WHERE id_articulo = %s LIMIT 1",$idArticulo); 
    $rs = mysql_query($query) or die(mysql_error());
    if (!$rs) die(mysql_error()."\n\nLine: ".__LINE__);
    $row = mysql_fetch_array($rs);
    
    return $row["codigo_articulo"];
}

function tieneIva($idArticulo){
    $query = sprintf("SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = %s LIMIT 1",$idArticulo); 
    $rs = mysql_query($query) or die(mysql_error());
    if (!$rs) die(mysql_error()."\n\nLine: ".__LINE__);
    $tiene = mysql_num_rows($rs);
    
    if($tiene){
        $texto = "SI";
    }else{
        $texto = "<img src=\"../img/iconos/e_icon.png\" />";
    }
    
    return $texto;
}


    xajaxRegister('guardar');
    xajaxRegister('cargar_listas');
    xajaxRegister('cargar');
    xajaxRegister('load_page');
    xajaxRegister('agregar_articulo');
    xajaxRegister('agregar_tempario');
    xajaxRegister('agregar_unidad');

    xajaxProcess();

    includeDoctype();
    $c= new connection();
    $c->open();

    $empresas=getEmpresaList($c,false);

    $articulo= $c->sa_v_articulos;

    $articulos= $articulo->doSelect($c)->getAssoc('id_articulo','articulo_completo');
    $temparios= $c->sa_v_tempario->doSelect($c)->getAssoc('id_tempario','tempario_completo');
    $unidades= $c->sa_v_unidad_basica->doSelect($c)->getAssoc('id_unidad_basica','nombre_unidad_basica');

    $c->close();
?>

<html>
    <head>
        <?php
                includeMeta();
                includeScripts();
                getXajaxJavascript();
        ?>
        <link rel="stylesheet" type="text/css" href="css/sa_general.css" />
        <link rel="stylesheet" type="text/css" href="../style/styleRafk.css">
        <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css">
        
        <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Paquetes</title>        
        <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
        
        <style type="text/css">
            button img{
                padding-right:1px;
                padding-left:1px;
                padding-bottom:1px;
                vertical-align:middle;
            }
            .order_table tbody tr:hover,.order_table tbody tr.impar{
                cursor:default;
            }
            .order_table tbody tr:hover img,.order_table tbody tr.impar img{
                cursor:pointer;
            }
            #table_articulos td{
                border: 1px solid #ccc;
            }
            #table_articulos thead td{
                background:#BFBFBF;
            }
            .impar{
                background:#DFDFDF;
            }
            
        </style>
        <script type="text/javascript">
            detectEditWindows({edit_window:'guardar'});
            var counter_articulo=0;
            var tabla_articulo=new Array();

            function agregar_articulo(valor){
                if(valor==0){
                    _alert('Elija un art&iacute;culo de la lista para agregar')
                    return;
                }
                for(var i=1; i<=counter_articulo;i++){
                    var ob= obj('id_articulo'+i);
                    if(ob.value==valor){
                        var row= obj('row'+i);
                        if(row.style.display=='none'){
                            if(_confirm('El Art&iacute;culo fue anteriormente eliminado, &iquest;Desea agregarlo de nuevo?')){
                                xajax_agregar_articulo(valor,xajax.getFormValues('formulario2'));
                                //xajax_agregar_articulo(obj('id_articulo'+i).value,xajax.getFormValues('formulario2'),i);
                            }
                        }else{
                            _alert('Ya existe el Art&iacute;culo');
                            //_confirm('Ya existe el Art&iacute;culo &iquest;Desea agregarlo?');
                            //xajax_agregar_articulo(valor,xajax.getFormValues('formulario2'));
                        }
                        return;
                    }
                }
                xajax_agregar_articulo(valor,xajax.getFormValues('formulario2'));
            }

            function articulo_add(data){
                var tabla=obj('tbody_articulos');
                var nt = new tableRow("tbody_articulos");
                tabla_articulo[counter_articulo]=nt;
                counter_articulo++;
                nt.setAttribute('id','row'+counter_articulo);
                if(counter_articulo%2){
                    nt.$.className='';
                }else{
                    nt.$.className='impar';
                }
                //nt.$.className='field';
                var c1= nt.addCell();
                    c1.$.innerHTML=data.codigo;
                var c2= nt.addCell();
                    c2.$.innerHTML=data.descripcion;
                var impuesto= nt.addCell();
                    impuesto.$.innerHTML = "<center>"+data.iva+"</center>";
                var c3= nt.addCell();
                    c3.$.innerHTML='<center><input type="text" id="cantidad'+counter_articulo+'" name="cantidad[]" value="'+data.cantidad+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" /></center><input type="hidden" id="id_articulo'+counter_articulo+'" name="id_articulo[]" value="'+data.id_articulo+'" /><input type="hidden" id="id_paq_repuesto'+counter_articulo+'" name="id_paq_repuesto[]" value="'+data.id_paq_repuesto+'" /><input type="hidden" id="action'+counter_articulo+'" name="action[]" value="'+data.action+'" />';
					
					//////////////
			var c3= nt.addCell();
                    c3.$.innerHTML='<center><input type="text" id="precio'+counter_articulo+'" name="precio[]" value="'+data.precio+'"  style="width:70px;" /></center>';
					
					
					
					////////////////
					
					
                var c4= nt.addCell();
                    c4.$.innerHTML='<center><img src="<?php echo getUrl('img/iconos/delete.png'); ?>" border="0" alt="Quitar" style="cursor:pointer;" onclick="articulo_quit('+counter_articulo+')"/></center>';
            }

            function articulo_quit(cont){
                if(_confirm("&iquest;Desea eliminar el Art&iacute;culo de la Lista?")){
                    var fila=obj('row'+cont);
                    fila.style.display='none';
                    var action=obj('action'+cont);
                    action.value='delete';
                }
            }

            function articulo_vaciar(){
                var tabla=obj('tbody_articulos');
                for(var t in tabla_articulo){
                    tabla.removeChild(tabla_articulo[t].$);
                }
                counter_articulo=0;
                tabla_articulo=new Array();
            }

            var counter_tempario=0;
            var tabla_tempario=new Array();

            function agregar_tempario(valor){
                if(valor==0){
                    _alert('Elija una Posici&oacute;n de Trabajo de la lista para agregar')
                    return;
                }
                for (var i=1; i<=counter_tempario;i++){
                    var ob= obj('id_tempario'+i);
                    if(ob.value==valor){
                        var row= obj('rowt'+i);
                        if(row.style.display=='none'){
                            if(_confirm('La Posici&oacute;n de Trabajo fue anteriormente eliminada, &iquest;Desea agregarla de nuevo?')){
                               
                                xajax_agregar_tempario(obj('id_tempario'+i).value,xajax.getFormValues('formulario2'),i);
                            }
                        }else{
                            _alert('Ya existe la Posici&oacute;n de Trabajo');
                        }
                        return;
                    }
                }
                           
                xajax_agregar_tempario(valor,xajax.getFormValues('formulario2'));
            }

            function tempario_add(data){
                var tabla=obj('tbody_tempario');
                var nt = new tableRow("tbody_tempario");
                tabla_tempario[counter_tempario]=nt;
                counter_tempario++;
                nt.setAttribute('id','rowt'+counter_tempario);
                nt.$.className='field';
                var c1= nt.addCell();
                c1.$.innerHTML=data.unidad;
                var c2= nt.addCell();
                c2.$.innerHTML=data.descripcion;
               
				
				var c3= nt.addCell();
                    c3.$.innerHTML='<center><input type="text" id="ut'+counter_tempario+'" name="ut[]" value="'+data.ut+'" onchange="set_toNumber(this,0);" onkeypress="return inputInt(event);" style="width:40px;" /></center>';
					
					
				var c3= nt.addCell();
                    c3.$.innerHTML='<center><input type="text" id="costo'+counter_tempario+'" name="costo[]" value="'+data.costo+'"  style="width:70px;" /></center>';
					
				var c3= nt.addCell();
                c3.$.innerHTML='<center><img src="<?php echo getUrl('img/iconos/delete.png'); ?>" border="0" style="cursor:pointer;" alt="Quitar" onclick="tempario_quit('+counter_tempario+')"></center><input type="hidden" id="id_tempario'+counter_tempario+'" name="id_tempario[]" value="'+data.id_tempario+'" /><input type="hidden" id="id_paq_tempario'+counter_tempario+'" name="id_paq_tempario[]" value="'+data.id_paq_tempario+'" /><input type="hidden" id="actiont'+counter_tempario+'" name="actiont[]" value="'+data.action+'" />';
            }

            function tempario_quit(cont){
                if(_confirm("&iquest;Desea eliminar la Posici&oacute;n de Trabajo de la Lista?")){
                    var fila=obj('rowt'+cont);
                    fila.style.display='none';
                    var action=obj('actiont'+cont);
                    action.value='delete';
                }
            }

            function tempario_vaciar(){
                var tabla=obj('tbody_tempario');
                for(var t in tabla_tempario){
                    tabla.removeChild(tabla_tempario[t].$);
                }
                counter_tempario=0;
                tabla_tempario=new Array();
            }

            var counter_modelo=0;
            var tabla_modelo=new Array();

            function agregar_unidad(valor){
                if(valor==0){
                    _alert('Elija un Modelo de la lista para agregar')
                    return;
                }
                for(var i=1; i<=counter_modelo;i++){
                    var ob= obj('id_unidad_basica'+i);
                    if(ob.value==valor){
                        var row= obj('rowm'+i);
                        if(row.style.display=='none'){
                            if(_confirm('El Modelo fue anteriormente eliminado, &iquest;Desea agregarlo de nuevo?')){
                                xajax_agregar_unidad(obj('id_unidad_basica'+i).value,xajax.getFormValues('formulario2'),i);
                            }
                        }else{
                            _alert('Ya existe el Modelo');
                        }
                        return;
                    }
                }
                xajax_agregar_unidad(valor,xajax.getFormValues('formulario2'));
            }

            function unidad_add(data){
                    var tabla=obj('tbody_modelo');
                    var nt = new tableRow("tbody_modelo");
                    tabla_modelo[counter_modelo]=nt;
                    counter_modelo++;
                    nt.setAttribute('id','rowm'+counter_modelo);
                    if(counter_modelo%2){
                        nt.$.className='';
                    }else{
                        nt.$.className='impar';
                    }

                    //nt.$.className='field';
                    var c1= nt.addCell();
                    c1.$.innerHTML=data.unidad;
                    var c2= nt.addCell();
                    c2.$.innerHTML=data.nom_modelo;
                    var c3= nt.addCell();
                    c3.$.innerHTML=data.unidad_completa;
                    var c4= nt.addCell();
                    c4.$.innerHTML='<center><img src="<?php echo getUrl('img/iconos/delete.png'); ?>" border="0" style="cursor:pointer;" alt="Quitar" onclick="unidad_quit('+counter_modelo+')"/></center><input type="hidden" id="id_unidad_basica'+counter_modelo+'" name="id_unidad_basica[]" value="'+data.id_unidad_basica+'" /><input type="hidden" id="id_paq_unidad'+counter_modelo+'" name="id_paq_unidad[]" value="'+data.id_paq_unidad+'" /><input type="hidden" id="actionm'+counter_modelo+'" name="actionm[]" value="'+data.action+'" />';
            }

            function unidad_quit(cont){
                if(_confirm("&iquest;Desea eliminar el Modelo de la Lista?")){
                    var fila=obj('rowm'+cont);
                    fila.style.display='none';
                    var action=obj('actionm'+cont);
                    action.value='delete';
                }
            }

            function unidad_vaciar(){
                var tabla=obj('tbody_modelo');
                for(var t in tabla_modelo){
                    tabla.removeChild(tabla_modelo[t].$);
                }
                counter_modelo=0;
                tabla_modelo=new Array();
            }

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
                xajax_load_page(datos.page,datos.maxrows,datos.order,datos.ordertype,'capaTabla','busca='+datos.busca);
                close_window("edit_window");
                close_window("unidad_window");
            }

            function agregar(add){
                close_window('unidad_window');
                setDivWindow('edit_window','title_window',true);
                if(add){
                    $('#edit_window input').val('');
                    //$('#edit_window select').val('');
                    $('#edit_window #capa_id_art_inventario').html('');
                    $("#edit_window #subtitle").html("Agregar");
                    $("#edit_window input").not("#fecha_venta").attr("readonly","");
                    //$("#edit_window select").attr("disabled","");
                    $("#edit_window button").attr("disabled","");
                    $("#info_cliente").html("");
                }
                articulo_vaciar();
                unidad_vaciar();
                tempario_vaciar();
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

            function buscar_cliente_pago(){
                xajax_dialogo_cliente(0,10,'cj_cc_cliente.ci','','', 'busqueda=,callback=xajax_cargar_cliente_pago,parent=principal');
            }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		function actualizarRpto(add,paq){
				setDivWindow('tipo_precio_window','title_window_precio',true);
				xajax_comboPreciosRpto(paq);   
					}




            function agregar2(add){
                setDivWindow('edit_window_2','title_window_2',true);
                if(add){
                    $('#edit_window_2 input').val('');
                    //$('#edit_window_2 select').val('');//Este ponia la empresa 1
                    $('#edit_window_2 #capa_id_art_inventario').html('');
                    $("#edit_window_2 #subtitle").html("Agregar");
                    $("#edit_window_2 input").not("#fecha_venta").attr("readonly","");
                    $("#edit_window_2 select").attr("disabled",""); //el segundo parametro estaba vacio ""
                    $("#edit_window_2 button").attr("disabled","");
                    $("#info_cliente").html("");
                }
				limpiar_select();//borra el resto de las empresas
				
                articulo_vaciar();
                unidad_vaciar();
                tempario_vaciar();
                $(".field").removeClass("inputNOTNULL");$(".field").removeClass("inputERROR");
            }
            function windowUnidad(){
                setDivWindow('bus_unidad','title_window_unidad',true);
                xajax_buscarUnidad(xajax.getFormValues('frmBusUnidad'));
            }
            function windowArt(){
                if(counter_modelo == 0){
                    alert("Debe elegir por lo menos una unidad");
                    return false;
                }
                xajax_cargaLstBusq();
                xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));
                setDivWindow('bus_art','title_window_art',true);
            }
            function windowMo(){
                if(counter_modelo == 0){
                    alert("Debe elegir por lo menos una unidad");
                    return false;
                }
                xajax_cargaLstBusqMo();
                xajax_buscarTempario(xajax.getFormValues('frmBusMo'), xajax.getFormValues('formulario2'));
                setDivWindow('bus_mo','title_window_mo',true);
            }
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        </script>
    </head>
    <body>
        <?php include("banner_servicios.php"); ?>
	<div style="width:1000px; background:#FFFFFF; margin: auto;">
            <table align="center" border="0" width="100%">
                <tr>
                    <td align="right" class="titulo_pagina">
                        <span>Mantenimiento de Servicios</span>
                        <br/>
			<span class="subtitulo_pagina" >(Paquetes)</span>
                    </td>
		</tr>
            </table>
            <br />
            <div id="principal">
                <div>
                <table width="100%">
                	<tr>
                    	<td width="25%" align="left">
                        <button type="button" value="Nuevo 2" onClick="agregar2(true);">
                            <img border="0" src="<?php echo getUrl('img/iconos/plus.png') ?>" alt="plus"/>Nuevo
                        </button>
                        <input type="text" id="busca" onKeyPress="keyEvent(event,buscar);" />
                        </td>
                        <td width="7%" align="left">
                        <button type="button" value="buscar" title="Buscar" onClick="buscar();" >
                            <img border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" alt="find"/>
                        </button>
                        </td>
                        <td width="7%" align="left">
                        <button type="button" value="reset" title="Restablecer" onClick="restablecer();" >
                            <img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" alt="cc"/>
                        </button>
                        </td>
                        <td width="41%" align="right">
                        <button type="button" value="reset" title="Actualizar" onClick="if(_confirm('Desea Actualizar Repuestos?')) actualizarRpto(true,0);" >
                            <img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>"/>Actualizar Todos Los Repuestos
                        </button>
                        </td>
                        <td width="20%" align="right">
                        <button type="button" value="reset" title="Actualizar" onClick="if(_confirm('Desea Actualizar Mano De Obra?')) xajax_actualizar_mo('0');" >
                            <img border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>"/>Actualizar Todas Las MO
                        </button>
                        </td>
                    </tr>
                </table> 
                    <hr />
                </div>
                <div id='capaTabla'></div>
                <div align="center" id='paginador'></div>
            </div>

            <div class="window" id="edit_window" style="min-width:510px;visibility:hidden;">
                <div class="title" id="title_window">
                    <span id="subtitle"></span>&nbsp;Paquetes de servicio
                </div>
                
                <!-- ESTE FORMULARIO ESTABA DE MAS O.o -->
                <!--
                <div class="content">
                    <form id="formulario" name="formulario" onsubmit="return false;" style="margin:0px;padding:0px;" action="#">
                        <input type="hidden" id="id_paquete2" name="id_paquete2" />
                        <table>
                            <tr>
                                <td valign="top">
                                    <table class="insert_table" style="width:auto;">
                                        <tbody>
                                            <tr>
                                                <td class="label">Empresa</td>
                                                <td class="field" id="field_id_empresa1"> PRIMERO
                                                    <?php
                                                        //echo inputSelect('id_empresa1',$empresas);
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label">C&oacute;digo</td>
                                                <td class="field" id="field_codigo_paquete1">
                                                    <input type="text" name="codigo_paquete1" id="codigo_paquete1" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="label">Descripci&oacute;n</td>
                                                <td class="field" id="field_descripcion_paquete1">
                                                    <input type="text" name="descripcion_paquete1" id="descripcion_paquete1" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <div style="width:300px;">
                                                        Para agregar At&iacute;culos, unidades y Posiciones de Trabajo, seleccionelas de sus respectivas listas y haga click en el bot&oacute;n (+) situado al lado de la lista.
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                                <td>
                                    Agregar Unidad:
                                    <br />
                                    <?php
                                        //echo inputSelect('unidades',$unidades,0,null,0);
                                    ?>
                                    <button type="button" onclick="agregar_unidad(obj('unidades').value);">
                                        <img src="<?php //echo getUrl('img/iconos/plus.png'); ?>" border="0" alt="Agregar Modelo" />
                                    </button>
                                    <div style="overflow:auto;height:200px;width:300px;">
                                        <table id="table_articulos" class="insert_table" style="width:90%;">
                                            <col style="width:*;" />
                                            <col style="width:10%;" />
                                            <col style="width:5%;" />
                                            <thead>
                                                <tr>
                                                    <td style="text-align:center;">Modelo</td>
                                                    <td style="width:16px;text-align:right;"></td>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_modelo2"></tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Agregar Art&iacute;culos:
                                    <br />
                                    <?php
                                        //echo inputSelect('articulos',$articulos,0,null,0);
                                    ?>
                                    <button type="button" onclick="agregar_articulo(obj('articulos').value);">
                                        <img src="<?php //echo getUrl('img/iconos/plus.png'); ?>" border="0" alt="Agregar Unidad" />
                                    </button>
                                    <div style="overflow:auto;height:200px;width:300px;">
                                        <table id="table_articulos" class="insert_table" style="width:90%;">
                                            <col  style="width:*;" />
                                            <col  style="width:10%;" />
                                            <col  style="width:5%;" />
                                            <thead>
                                                <tr>
                                                    <td style="text-align:center;">Art&iacute;culo</td>
                                                    <td style="text-align:center;">Cant.</td>
                                                    <td style="width:16px;text-align:right;"></td>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_articulos2"></tbody>
                                        </table>
                                    </div>
                                </td>
                                <td>
                                    Agregar Posiciones de Trabajo:
                                    <br />
                                    <?php
                                       // echo inputSelect('temparios',$temparios,0,null,0);
                                    ?>
                                    <button type="button" onclick="agregar_tempario(obj('temparios').value);">
                                        <img src="<?php //echo getUrl('img/iconos/plus.png'); ?>" border="0" alt="Agregar t" />
                                    </button>
                                    <div style="overflow:auto;height:200px;width:300px;">
                                        <table id="table_articulos" class="insert_table" style="width:90%;">
                                            <col  style="width:*;" />
                                            <col  style="width:10%;" />
                                            <col  style="width:5%;" />
                                            <thead>
                                                <tr>
                                                    <td style="text-align:center;">Tempario</td>
                                                    <td style="width:16px;text-align:right;">&nbsp;</td>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_tempario2"></tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <table style="width:100%;" >
                                        <tbody>
                                            <tr>
                                                <td nowrap="nowrap">
                                                    <div class="leyend">
                                                        <span class="inputNOTNULL"></span> Valor Requerido
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="leyend">
                                                        <span class="inputERROR"></span> Valor Incorrecto
                                                    </div>
                                                </td>
                                                <td  align="right">
                                                    <button type="submit" id="guardar" onclick="xajax_guardar(xajax.getFormValues('formulario'));" ><img border="0" src="<?php //echo getUrl('img/iconos/save.png'); ?>" alt="save"/>Guardar</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div> 
                
                -->
                <!-- FIN DE FORMULARIO QUE ESTABA DE MAS -->
                <img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('unidad_window');close_window('edit_window');" border="0" />
            </div>

            <div class="window" id="unidad_window" style="min-width:210px;visibility:hidden;">
                <div class="title" id="title_unidad_window">
                    Agregar Articulo
                </div>
                <div class="content">
                    Seleccione una unidad de la lista para agregar:
                    <div id="lista_unidades"></div>
                </div>
                <img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('unidad_window');" border="0" />
            </div>

<!-- OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO -->
<!-- OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO -->
<!-- OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO -->
<!-- OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO -->

            <div class="window" id="edit_window_2" style="min-width:510px;visibility:hidden;">
                <div class="title" id="title_window_2">
                    <span id="subtitle2"></span>&nbsp;Paquetes de servicio
                </div>
                <div class="content">
                    <form id="formulario2" name="formulario2" onsubmit="return false;" style="margin:0px;padding:0px;" action="#">
                        <input type="hidden" id="id_paquete" name="id_paquete" />
                        <table>
                            <tr>
                                <td valign="top">
                                    <table class="insert_table" style="width:auto;">
                                        <tbody>
                                            <tr>
                                                <td colspan="6">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td class="label">Empresa</td>
                                                <td class="field" id="field_id_empresa"> SEGUNDO
                                                    <?php
                                                       // echo inputSelect('id_empresa',$empresas);
                                                    ?>
                                                </td>

                                                <td class="label">C&oacute;digo</td>
                                                <td class="field" id="field_codigo_paquete">
                                                    <input type="text" name="codigo_paquete" id="codigo_paquete" />
                                                </td>

                                                <td class="label">Descripci&oacute;n</td>
                                                <td class="field" id="field_descripcion_paquete">
                                                    <input type="text" name="descripcion_paquete" id="descripcion_paquete" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="6">
                                                    <div>
                                                        Para agregar At&iacute;culos, unidades y Posiciones de Trabajo, seleccionelas de sus respectivas listas y haga click en el bot&oacute;n (+) situado al lado de la lista.
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="6">&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <table width="100%" border="0" cellpadding="0" >
                                        <tr>
                                            <td colspan="9">
                                                <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea">
                                                    <tr>
                                                        <td width="44%" height="22" align="left">
                                                            <button type="button" id="btnInsertarPaq" name="btnInsertarPaq" onclick="windowUnidad();" title="Agregar Paquete">
                                                                <img src="../img/iconos/ico_agregar.gif" alt="ico_agregar"/>
                                                            </button>
                                                        </td>
                                                        <td width="56%" align="left">UNIDADES</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr class="tituloColumna">
                                            <td width="20%" class="celda_punteada">Nombre</td>
                                            <td width="30%" class="celda_punteada">Modelo</td>
                                            <td width="42%" class="celda_punteada">Descripci&oacute;n</td>
                                            <td width="8%" class="celda_punteada">Acciones</td>
                                        </tr>
                                        <tbody id="tbody_modelo"></tbody>
                                    </table>
                                </td>
                            </tr>
                            
                            <tr>
                                <td colspan="6">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <table width="100%" border="0" cellpadding="0" >
                                        <tr>
                                            <td colspan="9">
                                                <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea">
                                                    <tr>
                                                        <td width="44%" height="22" align="left">
                                                            <button type="button" id="btnInsertarPaq" name="btnInsertarPaq" onclick="windowArt();" title="">
                                                                <img src="../img/iconos/ico_agregar.gif" alt="ico_agregar"/>
                                                            </button>
                                                        </td>
                                                        <td width="56%" align="left">ART&Iacute;CULOS</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr class="tituloColumna">
                                            <td width="15%" class="celda_punteada">C&oacute;digo</td>
                                            <td width="55%" class="celda_punteada">Descripci&oacute;n</td>
                                            <td width="8%" class="celda_punteada">Impuesto</td>
                                            <td width="8%" class="celda_punteada">Cantidad</td>
                                            <td width="14%" class="celda_punteada">Precio</td>
                                            <td width="8%" class="celda_punteada">Acciones</td>
                                        </tr>
                                        <tbody id="tbody_articulos"></tbody>
                                    </table>
                                </td>
                            </tr>
                            
                            <tr>
                                <td colspan="6">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <table width="100%" border="0" cellpadding="0" >
                                        <tr>
                                            <td colspan="9">
                                                <table width="100%" border="0" cellpadding="0" cellspacing="0" class="tituloArea">
                                                    <tr>
                                                        <td width="40%" height="22" align="left">
                                                            <button type="button" id="btnInsertarPaq" name="btnInsertarPaq" onclick="windowMo();" title="">
                                                                <img src="../img/iconos/ico_agregar.gif" alt="ico_agregar"/>
                                                            </button>
                                                        </td>
                                                        <td width="60%" align="left">POSICIONES DE TRABAJO</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr class="tituloColumna">
                                            <td width="20%" class="celda_punteada">C&oacute;digo</td>
                                            <td width="50%" class="celda_punteada">Tempario</td>
                                            <td width="10%" class="celda_punteada">UT</td>
                                            <td width="10%" class="celda_punteada">Costo</td>
                                            <td width="10%" class="celda_punteada">Acciones</td>
                                        </tr>
                                        <tbody id="tbody_tempario" ></tbody>
                                    </table>
                                </td>
                            </tr>
                            
                            <tr>
                                <td colspan="6">&nbsp;</td>
                            </tr>

                            <tr>
                                <td colspan="2">
                                    <table style="width:100%;" >
                                        <tbody>
                                            <tr>
                                                <td nowrap="nowrap">
                                                    <div class="leyend">
                                                        <span class="inputNOTNULL"></span> Valor Requerido
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="leyend">
                                                        <span class="inputERROR"></span> Valor Incorrecto
                                                    </div>
                                                </td>
                                                <td  align="right">
                                                    <button type="button" id="guardar" onclick="xajax_guardar(xajax.getFormValues('formulario2'));" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" alt="save"/>Guardar</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('edit_window_2');" border="0" />
            </div>

            <div class="window" id="bus_unidad" style="min-width:710px;visibility:hidden;">
                <div class="title" id="title_window_unidad">
                    Listado de Unidades
                </div>
                <div class="content">
                    <form id="frmBusUnidad"  name="frmBusUnidad" onsubmit="return false;" action="#">
                        <table>
                            <tr>
                                <td width="40%">&nbsp;</td>
                                <td width="20%">
                                    Código / Descripción:
                                </td>
                                <td width="20%">
                                    <input type="text" name="bus_unidad" id="bus_unidad" onkeyup="xajax_buscarUnidad(xajax.getFormValues('frmBusUnidad'));">
                                </td>
                                <td width="10%">
                                    <input type="button" value="Buscar" id="btnBuscarUni" name="btnBuscarUni" onclick="xajax_buscarUnidad(xajax.getFormValues('frmBusUnidad'));">
                                </td>
                                <td width="10%">
                                    <input type="button" value="Ver Todos" onclick="xajax_buscarUnidad(xajax.getFormValues('frmBusUnidad'));">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="10">
                                    <div id="divUnidad"></div>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('bus_unidad');" border="0" />
            </div>

            <div class="window" id="bus_art" style="min-width:1000px;visibility:hidden;">
                <div class="title" id="title_window_art">
                    Listado de Art&iacute;culo
                </div>
                <div class="content">
                    <form id="frmBusArt" name="frmBusArt" onsubmit="return false;" action="#">
                        <table border="0" id="tblArticulo" width="100%">
                            <tr>
                                <td>
                                    <table border="0" width="100%">
                                        <tr align="left">
                                            <td align="right" class="tituloCampo" width="8%">Marca:</td>
                                            <td id="tdlstMarcaBusq" width="24%">
                                                <select id="lstMarcaBusq" name="lstMarcaBusq">
                                                    <option value="-1">Todos...</option>
                                                </select>
                                            </td>
                                            <td align="right" class="tituloCampo" width="10%">Tipo de Articulo:</td>
                                            <td id="tdlstTipoArticuloBusq" width="24%">
                                                <select id="lstTipoArticuloBusq" name="lstTipoArticuloBusq">
                                                    <option value="-1">Todos...</option>
                                                </select>
                                            </td>
                                            <td align="right" class="tituloCampo" width="10%">Código:</td>
                                            <td id="tdCodigoArt" width="24%">
                                                 <input type="text" name="txtCodigoArticulo" id="txtCodigoArticulo" value="" onKeyUp="xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));" size="20">
                                             <!--   <input type="text" name="txtCodigoArticulo1" id="txtCodigoArticulo1" value="" size="8">
                                                <input type="text" name="txtCodigoArticulo2" id="txtCodigoArticulo2" value="" size="8">-->
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Secci&oacute;n:</td>
                                            <td colspan="3" id="tdlstSeccionBusq">
                                                <select id="lstSeccionBusq" name="lstSeccionBusq">
                                                    <option value="-1">Todos...</option>
                                                </select>
                                            </td>
                                            <td align="right" class="tituloCampo">Descripci&oacute;n:</td>
                                            <td>
                                                <input type="text" id="txtDescripcionBusq" name="txtDescripcionBusq" onkeyup="$('btnBuscarArti').click();" size="30"/>
                                            </td>
                                        </tr>
                                        <tr align="left">
                                            <td align="right" class="tituloCampo">Sub-Secci&oacute;n:</td>
                                            <td colspan="4" id="tdlstSubSeccionBusq">
                                                <select id="lstSubSeccionBusq" name="lstSubSeccionBusq">
                                                    <option value="-1">Todos...</option>
                                                </select>
                                            </td>
                                            <td align="right">
                                                <input type="submit" style="visibility:hidden" value="."/>
                                                <input type="button" id="btnBuscarArt" name="btnBuscarArt" onclick="xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));" value="Buscar..."/>
                                                <input type="button" onclick="document.forms['frmBusArt'].reset(); xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));" value="Ver Todo"/>
                                            </td>
                                        </tr>
                                    </table>
                                    <hr>
                                </td>
                            </tr>
                            <tr>
                                <td id="tdListadoArticulos">
                                    <table width="100%">
                                        <tr class="tituloColumna">
                                            <td>Código</td>
                                            <td>Descripción</td>
                                            <td>Marca</td>
                                            <td>Tipo</td>
                                            <td>Sección</td>
                                            <td>Sub-Sección</td>
                                            <td>Disponible</td>
                                            <td>Reservado</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('bus_art');" border="0" />
            </div>

            <div class="window" id="bus_mo" style="min-width:940px;visibility:hidden;">
                <div class="title" id="title_window_mo">
                    Listado de Posiciones de Trabajo
                </div>
                <div class="content">
                    <form id="frmBusMo" name="frmBusMo" onsubmit="return false;" action="#">
                        <table border="0" id="tblBusquedaTempario" width="100%">
                            <tr>
                                <td align="right" class="tituloCampo">Secci&oacute;n:</td>
                                <td id="tdListSeccionTemp">
                                    <select id="lstSeccionTemp" name="lstSeccionTemp">
                                        <option value="-1">Seleccione...</option>
                                    </select>
                                </td>
                                <td align="right" class="tituloCampo">Código / Descripci&oacute;n:</td>
                                <td>
                                    <input type="text" id="txtDescripcionBusqTemp" name="txtDescripcionBusqTemp" onkeyup="" size="30"/>
                                </td>
                            </tr>
                            <tr>
                                <td align="right" class="tituloCampo">Subsecci&oacute;n:</td>
                                <td id="tdListSubseccionTemp">
                                    <select id="lstSubseccionTemp" name="lstSubseccionTemp">
                                        <option value="-1">Todos...</option>
                                    </select>
                                </td>
                                <td>&nbsp;</td>
                                <td>
                                    <input type="button" id="btnBuscarTempario" name="btnBuscarTempario" onclick="xajax_buscarTempario(xajax.getFormValues('frmBusMo'),xajax.getFormValues('formulario2'));" value="Buscar..."/>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <div id="tdListadoTemparioPorUnidad">
                                        <table width="100%">
                                            <tr class="tituloColumna">
                                                <td width='8%'></td>
                                                <td width='10%'>C&oacute;digo</td>
                                                <td width='42%'>Descripci&oacute;n</td>
                                                <td width='20%'>Secci&oacute;n</td>
                                                <td width='20%'>Subsecci&oacute;n</td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('bus_mo');" border="0" />
            </div>

<!-- OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO -->
<!-- OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO -->
<!-- OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO -->
<!-- OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO OJO -->




  <div class="window" id="tipo_precio_window" style="min-width:510px;visibility:hidden;">
	<div class="title" id="title_window_precio">
		<span id="subtitle2"></span>&nbsp;Paquetes de servicio
	</div>
	<div class="content">
		<form id="formularioPrecio" name="formularioPrecio" onSubmit="return false;" style="margin:0px;padding:0px;" action="#">
			<input type="hidden" id="id_paquete" name="id_paquete" />
			<table>
				<tr>
					<td valign="top">
						<table class="insert_table" style="width:auto;">
							<tbody>
						
								<tr>
									<td class="label">Precio Repuesto</td>
									<td align="left" id="tdPrecioRpto"></td>
									<td>
                                    <input type="hidden" id="hddIdPrecio" name="hddIdPrecio" value='1'/>
                                    <input type="hidden" id="hddIdPaquete" name="hddIdPaquete" />
                                    </td>
                                   
                                    
                                    <td>
                                <button type="buttom" id="guardar" onClick="xajax_actualizar_repuestos(xajax.getFormValues('formularioPrecio')); close_window('tipo_precio_window');" ><img border="0" src="<?php echo getUrl('img/iconos/save.png'); ?>" alt="save"/>Actualizar</button>
                                </td>

                                </tr>
                                
								
							</tbody>
						</table>
					</td>
				</tr>
				
			</table>
		</form>
	</div>
                <img class="close_window" src="<?php echo getUrl('img/iconos/close_dialog.png'); ?>" alt="cerrar" title="Cerrar" style="cursor:pointer;" onClick="close_window('tipo_precio_window');" border="0" />
</div>
        <?php include("menu_serviciosend.inc.php"); ?>

        <script type="text/javascript" language="javascript">
		xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"] ?>','',"id_empresa","field_id_empresa",0,"unico");
		//$("#id_empresa").attr('disabled','disabled');
		//$input.disabled = "disabled";
            cargar();
			
			function limpiar_select(){
			/*	var select_option = $("#id_empresa").find("option:not([selected])").hide();
				$("#id_empresa").find("optgroup").removeAttr('label');				*/
			}
			
        </script>

    </body>
</html>

