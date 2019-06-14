<?php
@session_start();

define('PAGE_PRIV','sa_informe_final_servicio');//nuevo gregor
//define('PAGE_PRIV','sa_resumen_servicio');//anterior

require_once("../inc_sesion.php");
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
//implementando xajax;
	require_once("control/main_control.inc.php");
	require_once("control/iforms.inc.php");
	require_once("control/funciones.inc.php");
	//include("control/xajax_dialogo_cliente.inc.php"); //incluir la busqueda/registro del cliente

require_once ("../connections/conex.php");//lo necesita ac_iv_general
include("controladores/ac_iv_general.php");//tiene la funcion listado de empresas final

$queryEmpresa = sprintf("SELECT nombre_empresa FROM pg_empresa WHERE id_empresa = %s",
                        valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
$rs = mysql_query($queryEmpresa);
if(!$rs){ die (mysql_error()."\n Linea: ".__LINE__); }
$row = mysql_fetch_assoc($rs);
$nombreEmpresa = $row["nombre_empresa"]; 

set_time_limit(0);


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
		
		//$query->where(new criteria(sqlEQUAL,$sa_paquetes->id_empresa,2));
		$paginador = new fastpaginator('xajax_load_page',$args,$query);
		/*$arrayFiltros=array(
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
		);*/
		$argumentos = $paginador->getArrayArgs();
		$aplica_iva=($argumentos['iva']==1);
		$id_empresa=$argumentos['h_empresa'];
                $taller = $argumentos['taller'];//1 solo taller, 2 todo
                
		if($id_empresa==null){
			$r->assign($paginador->layer,inner,'Seleccione una Empresa');
			return $r;
		}
		
		$fecha=$argumentos['fecha'];
		
		if($fecha!=''){
			//'<span class="'.$this->class_filter.'" onclick="'.$event.'" title="Eliminar filtro '.$kt.'">Filtrado por '.$kt.': '.$v.'</span>';
			$rank=$argumentos['fecha_rank'];
			$meses=array(1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Ocutubre','Noviembre','Diciembre');
			$diaar=explode('-',$fecha);
			if($rank==1){
				//todo el d�a
				$fechac=new criteria(sqlEQUAL,'fecha_filtro',field::getTransformType($fecha,field::tDate));
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha=\'\';cargar();" title="Eliminar filtro Fecha Diario"><strong>Filtrado por D&iacute;a: '.$fecha.'</strong></span>';
			}elseif($rank==2){
				//$r->alert(utf_export(intval($diaar[1])));
				$fechac=new criteria(sqlAND,
					new criteria(sqlEQUAL,"DATE_FORMAT(fecha_filtro,'%c')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%c')"),
					new criteria(sqlEQUAL,"DATE_FORMAT(fecha_filtro,'%Y')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%Y')")
				);
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha=\'\';cargar();" title="Eliminar filtro Fecha Mensual"><strong>Filtrado por Mes: '.$meses[intval($diaar[1])].' del '.$diaar[2].'</strong></span>';
			}else{
				$fechac=new criteria(sqlEQUAL,"DATE_FORMAT(fecha_filtro,'%Y')","DATE_FORMAT(".field::getTransformType($fecha,field::tDate).",'%Y')");
				$removefilterfecha='<span class="fast_filter" onclick="datos.fecha=\'\';cargar();" title="Eliminar filtro Fecha Anual"><strong>Filtrado por A&ntilde;o: '.$diaar[2].'</strong></span>';
			}
			//$r->alert($fechac->__toString());
		}
		
		//Iniciando los array principales para el listado
		//array por tipos de orden:		
		
//		$tipos_orden= $c->sa_tipo_orden->doSelect($c, new criteria(sqlAND,
//                                                            new criteria(sqlEQUAL,'orden_generica',0),
//                                                            new criteria(sqlEQUAL,'id_empresa',$id_empresa)
//                                                          )->getAssoc('id_tipo_orden','nombre_tipo_orden');
		
                if($taller == "1"){
                    $filtroTaller = "id_filtro_orden != 9 AND id_filtro_orden != 10 AND id_filtro_orden != 11 AND orden_generica = 0 AND ";
                }elseif($taller == "2"){
                    $filtroTaller="";
                }
                $queryTipoOrden = sprintf("SELECT id_tipo_orden, nombre_tipo_orden FROM sa_tipo_orden 
                        WHERE %s id_empresa = %s",
                        $filtroTaller,
                        valTpDato($id_empresa,"int"));
                
                $rsTipoOrden = mysql_query($queryTipoOrden);
                if(!$rsTipoOrden){ die (mysql_error()."\n Linea: ".__LINE__); }                
                
                $tipos_orden = array();
                while ($row = mysql_fetch_assoc($rsTipoOrden)){
                    $tipos_orden[$row["id_tipo_orden"]] = $row["nombre_tipo_orden"];
                }
                
		//array por operadores del tempario
		$operadores=$c->sa_operadores->doSelect($c)->getAssoc('id_operador','descripcion_operador');
		$repuestos=$c->iv_tipos_articulos->doSelect($c)->getAssoc('id_tipo_articulo','descripcion');

//PASO 1: ejecuntando la vista de temparios:
		$qrectemp= $c->sa_v_informe_final_tempario->doQuery($c);
                
                if($taller == "1"){                    
                    $qrectemp->where(new criteria(sqlAND, array(
                            new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                            new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                            new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
							new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
                            new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrectemp->where(new criteria(sqlAND, array(
                            new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                            new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		//$r->alert(($fechac->__tostring())); //fecha_filtro
		if($fechac!=null){
			$qrectemp->where($fechac);
		}
		$rectemp= $qrectemp->doSelect();
		foreach($rectemp as $temp){
			//$r->alert($temp->operador.' '.$temp->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			if ($temp->id_modo == 1) {
				$valor = ($temp->precio_tempario_tipo_orden*$temp->ut)/$temp->base_ut_precio;
			} else {
				$valor = $temp->precio;
			}
			
			if($aplica_iva){
				$valor=$valor+(($valor*$temp->iva)/100);
			}
			//llenando el vector por ordenes
			$data_orden[$temp->id_orden]['t']+=$valor;
			$data[$temp->operador][$temp->id_tipo_orden]+=$valor;
			$total_to[$temp->id_tipo_orden]+=$valor;
			$total_op[$temp->operador]+=$valor;
			
			//UT por cada orden
			$dataUT[$temp->id_orden] += $temp->ut;
			//UT por tipo orden
			$dataTipoUT[$temp->id_tipo_orden] += $temp->ut;
		}
                /////////////////////////////////OJO OJO OJO OJO OJO /////////////////////////////////////////////
                $qrectemp1= $c->sa_v_vale_informe_final_tempario->doQuery($c);
                
                if($taller == "1"){
                    $qrectemp1->where(new criteria(sqlAND, array(
			new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
						new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
						new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                     $qrectemp1->where(new criteria(sqlAND, array(
							new criteria(sqlEQUAL,'id_empresa',$id_empresa),
							new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		//$r->alert(($fechac->__tostring())); //fecha_filtro
		if($fechac!=null){
			$qrectemp1->where($fechac);
		}
		$rectemp1= $qrectemp1->doSelect();
		foreach($rectemp1 as $temp1){
			//$r->alert($temp->operador.' '.$temp->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			if ($temp1->id_modo == 1) {
				$valor1 = ($temp1->precio_tempario_tipo_orden*$temp1->ut)/$temp1->base_ut_precio;
			} else {
				$valor1 = $temp1->precio;
			}
			
			$data_orden[$temp1->id_orden]['t']+=$valor1;
			$data[$temp1->operador][$temp1->id_tipo_orden]+=$valor1;
			$total_to[$temp1->id_tipo_orden]+=$valor1;
			$total_op[$temp1->operador]+=$valor1;
			
			//UT por cada orden
			$dataUT[$temp1->id_orden] += $temp1->ut;
			//UT por tipo orden
			$dataTipoUT[$temp1->id_tipo_orden] += $temp1->ut;
		}
                /////////////////////////////////FIN OJO FIN OJO FIN OJO FIN OJO FIN OJO /////////////////////////////////////////////
		$itot=count($operadores)+1;
		$operadores[$itot]='Trabajos Otros Talleres';
//PASO 2: ejecuntando la vista de los TOT:
		$qrectot= $c->sa_v_informe_final_tot->doQuery($c);
                if($taller == "1"){
                    $qrectot->where(new criteria(sqlAND, array(
                            new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                            new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                            new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
							new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
                            new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrectot->where(new criteria(sqlAND, array(
                            new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                            new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		if($fechac!=null){
			$qrectot->where($fechac);
		}
		$rectot= $qrectot->doSelect();
		foreach($rectot as $tot){
			$valor=$tot->monto_total+(($tot->porcentaje_tot*$tot->monto_total)/100);
			if($aplica_iva){
				$valor=$valor+(($valor*$tot->iva)/100);
			}
			$data_orden[$tot->id_orden]['tot']+=$valor;
			$data[$itot][$tot->id_tipo_orden]+=$valor;
			$total_to[$tot->id_tipo_orden]+=$valor;
			$total_op[$itot]+=$valor;
		}
                /////////////////////////////////OJO OJO OJO OJO OJO /////////////////////////////////////////////
                $qrectot1= $c->sa_v_vale_informe_final_tot->doQuery($c);
                if($taller == "1"){
                    $qrectot1->where(new criteria(sqlAND, array(
                            new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                            new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                            new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
							new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
                            new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrectot1->where(new criteria(sqlAND, array(
                            new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                            new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		if($fechac!=null){
			$qrectot1->where($fechac);
		}
		$rectot1= $qrectot1->doSelect();
		foreach($rectot1 as $tot1){
			$valor1=$tot1->monto_total+(($tot1->porcentaje_tot*$tot1->monto_total)/100);
			if($aplica_iva){
				$valor1=$valor1+(($valor1*$tot1->iva)/100);
			}
			$data_orden[$tot1->id_orden]['tot']+=$valor1;
			$data[$itot][$tot1->id_tipo_orden]+=$valor1;
			$total_to[$tot1->id_tipo_orden]+=$valor1;
			$total_op[$itot]+=$valor1;
		}
                /////////////////////////////////FIN OJO FIN OJO FIN OJO FIN OJO FIN OJO /////////////////////////////////////////////
//PASO 3: ejecuntando la vista de repuestos:
		$qrecrep= $c->sa_v_informe_final_repuesto->doQuery($c);
                if($taller == "1"){
                    $qrecrep->where(new criteria(sqlAND, array(
			new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
						new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
						new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrecrep->where(new criteria(sqlAND, array(
								new criteria(sqlEQUAL,'id_empresa',$id_empresa),
								new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		if($fechac!=null){
			$qrecrep->where($fechac);
		}	
		$recrep= $qrecrep->doSelect();
		foreach($recrep as $rep){
			//$r->alert($rep->operador.' '.$rep->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			$valor=$rep->precio_unitario*$rep->cantidad;

                        $desc= ($valor*$rep->porcentaje_descuento_orden)/100;

			if($aplica_iva){
				$valor=$valor+(($valor*$rep->iva)/100);
			}
			$data_orden[$rep->id_orden]['r']+=$valor;
                        $data_orden[$rep->id_orden]['desc']+=$desc;
			$datar[$rep->id_tipo_articulo][$rep->id_tipo_orden]+=$valor;
			$rtotal_to[$rep->id_tipo_orden]+=$valor;
			$rtotal_op[$rep->id_tipo_articulo]+=$valor;
                        $rtotal_desc[$rep->id_tipo_orden]+=$desc;
                        $totalDesc+= $desc;
		}
                /////////////////////////////////OJO OJO OJO OJO OJO /////////////////////////////////////////////
                $qrecrep1= $c->sa_v_vale_informe_final_repuesto->doQuery($c);
                if($taller == "1"){
                    $qrecrep1->where(new criteria(sqlAND, array(
			new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
						new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
						new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrecrep1->where(new criteria(sqlAND, array(
								new criteria(sqlEQUAL,'id_empresa',$id_empresa),
								new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		if($fechac!=null){
			$qrecrep1->where($fechac);
		}
		$recrep1= $qrecrep1->doSelect();
		foreach($recrep1 as $rep1){
			//$r->alert($rep->operador.' '.$rep->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			$valor1=$rep1->precio_unitario*$rep1->cantidad;

                        $desc1= ($valor1*$rep1->porcentaje_descuento_orden)/100;

			if($aplica_iva){
				$valor1=$valor1+(($valor1*$rep1->iva)/100);
			}
			$data_orden[$rep1->id_orden]['r']+=$valor1;
                        $data_orden[$rep1->id_orden]['desc']+=$desc1;
			$datar[$rep1->id_tipo_articulo][$rep1->id_tipo_orden]+=$valor1;
			$rtotal_to[$rep1->id_tipo_orden]+=$valor1;
			$rtotal_op[$rep1->id_tipo_articulo]+=$valor1;
                        $rtotal_desc[$rep1->id_tipo_orden]+=$desc1;
                        $totalDesc+= $desc1;
		}
                /////////////////////////////////FIN OJO FIN OJO FIN OJO FIN OJO FIN OJO /////////////////////////////////////////////
//PASO 4: ejecuntando la vista de las NOTAS:
		$qrecnota= $c->sa_v_informe_final_notas->doQuery($c);
                if($taller == "1"){
                    $qrecnota->where(new criteria(sqlAND, array(
			new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
						new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
						new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrecnota->where(new criteria(sqlAND, array(
								new criteria(sqlEQUAL,'id_empresa',$id_empresa),
								new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		if($fechac!=null){
			$qrecnota->where($fechac);
		}
		$recnota= $qrecnota->doSelect();
		foreach($recnota as $nota){
                    if($taller == "2"){
			//$r->alert($rep->operador.' '.$rep->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			$valor=$nota->precio;
			if($aplica_iva){
				$valor=$valor+(($valor*$nota->iva)/100);
			}
			$data_orden[$nota->id_orden]['n']+=$valor;
			$datan[$nota->id_tipo_orden]+=$valor;
                        $totalnotas+=$valor;
                    }
		}
                /////////////////////////////////OJO OJO OJO OJO OJO /////////////////////////////////////////////
                $qrecnota1= $c->sa_v_vale_informe_final_notas->doQuery($c);
                if($taller == "1"){
                    $qrecnota1->where(new criteria(sqlAND, array(
                            new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                            new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                            new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
							new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
                            new criteria(sqlEQUAL,'aprobado',1)
                    )));    
                }elseif($taller == "2"){
                    $qrecnota1->where(new criteria(sqlAND, array(
								new criteria(sqlEQUAL,'id_empresa',$id_empresa),
								new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		if($fechac!=null){
			$qrecnota1->where($fechac);
		}
		$recnota1= $qrecnota1->doSelect();
                
		foreach($recnota1 as $nota1){
                    if($taller == "2"){
			//$r->alert($rep->operador.' '.$rep->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			$valor1=$nota1->precio;
			if($aplica_iva){
				$valor1=$valor1+(($valor1*$nota1->iva)/100);
			}
			$data_orden[$nota1->id_orden]['n']+=$valor1;
			$datan[$nota1->id_tipo_orden]+=$valor1;
                        $totalnotas+=$valor1;
                    }
		}
                /////////////////////////////////FIN OJO FIN OJO FIN OJO FIN OJO FIN OJO /////////////////////////////////////////////
//PASO 5: DEVOLVIENDO los TEMPARIOS Devueltos:
                //PASO 1: ejecuntando la vista de temparios devueltos
		$qrectempd= $c->sa_v_informe_final_tempario_dev->doQuery($c);
                if($taller == "1"){
                    $qrectempd->where(new criteria(sqlAND, array(
			new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
						new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
						new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrectempd->where(new criteria(sqlAND, array(
								new criteria(sqlEQUAL,'id_empresa',$id_empresa),
								new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		//$r->alert(($fechac->__tostring())); //fecha_filtro
		if($fechac!=null){
			$qrectempd->where($fechac);
		}
		$rectempd= $qrectempd->doSelect();
		foreach($rectempd as $tempd){
			//$r->alert($temp->operador.' '.$temp->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			if ($tempd->id_modo == 1) {
				$valor = ($tempd->precio_tempario_tipo_orden*$tempd->ut)/$tempd->base_ut_precio;
			} else {
				$valor = $tempd->precio;
			}
			
			if($aplica_iva){
				$valor=$valor+(($valor*$tempd->iva)/100);
			}
                        $valor=$valor*-1;//Multiplica por -1 para RESTAR
			//llenando el vector por ordenes
			$ndata_orden[$tempd->id_orden]['t']+=$valor;
			$data[$tempd->operador][$tempd->id_tipo_orden]+=$valor;
			$total_to[$tempd->id_tipo_orden]+=$valor;
			$total_op[$tempd->operador]+=$valor;
			
			//UT por cada orden
			$dataUT[$tempd->id_orden] += $tempd->ut;
			//UT por tipo orden
			$dataTipoUT[$tempd->id_tipo_orden] += $tempd->ut;
		}
                //$itot ya existe
		//$itot=count($operadores)+1; 
		//$operadores[$itot]='Trabajos Otros Talleres';
//PASO 6: ejecuntando la vista de los TOT devueltos:
		$qrectotd= $c->sa_v_informe_final_tot_dev->doQuery($c);
                if($taller == "1"){
                    $qrectotd->where(new criteria(sqlAND, array(
			new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
						new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
						new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrectotd->where(new criteria(sqlAND, array(
								new criteria(sqlEQUAL,'id_empresa',$id_empresa),
								new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		if($fechac!=null){
			$qrectotd->where($fechac);
		}
		$rectotd= $qrectotd->doSelect();
		foreach($rectotd as $totd){
			//$valor=$totd->monto_total;
			$valor=$totd->monto_total+(($totd->porcentaje_tot*$totd->monto_total)/100);
			if($aplica_iva){
				$valor=$valor+(($valor*$totd->iva)/100);
			}
                        $valor=$valor*-1;//Multiplica por -1 para RESTAR
			$ndata_orden[$totd->id_orden]['tot']+=$valor;
			$data[$itot][$totd->id_tipo_orden]+=$valor;
			$total_to[$totd->id_tipo_orden]+=$valor;
			$total_op[$itot]+=$valor;
		}
//PASO 7: ejecuntando la vista de repuestos devueltos:
		$qrecrepd= $c->sa_v_informe_final_repuesto_dev->doQuery($c);
                if($taller == "1"){
                    $qrecrepd->where(new criteria(sqlAND, array(
			new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
						new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
						new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrecrepd->where(new criteria(sqlAND, array(
								new criteria(sqlEQUAL,'id_empresa',$id_empresa),
								new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
		
		if($fechac!=null){
			$qrecrepd->where($fechac);
		}
		$recrepd= $qrecrepd->doSelect();
		foreach($recrepd as $repd){
			//$r->alert($rep->operador.' '.$rep->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			$valor=$repd->precio_unitario*$repd->cantidad;

                        $desc= ($valor*$repd->porcentaje_descuento_orden)/100;

			if($aplica_iva){
				$valor=$valor+(($valor*$repd->iva)/100);
			}
                        $valor=$valor*-1;//Multiplica por -1 para RESTAR
                        $desc=$desc*-1;//Multiplica por -1 para RESTAR
			$ndata_orden[$repd->id_orden]['r']+=$valor;
                        $ndata_orden[$repd->id_orden]['desc']+=$desc;
			$datar[$repd->id_tipo_articulo][$repd->id_tipo_orden]+=$valor;
			$rtotal_to[$repd->id_tipo_orden]+=$valor;
			$rtotal_op[$repd->id_tipo_articulo]+=$valor;
                        $rtotal_desc[$repd->id_tipo_orden]+=$desc;
                        $totalDesc+= $desc;
		}
//PASO 8: ejecuntando la vista de las NOTAS devueltas:
		$qrecnotad= $c->sa_v_informe_final_notas_dev->doQuery($c);
                if($taller == "1"){
                    $qrecnotad->where(new criteria(sqlAND, array(
			new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                        new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
						new criteria(sqlNOTEQUAL,'id_filtro_orden',11),
						new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }elseif($taller == "2"){
                    $qrecnotad->where(new criteria(sqlAND, array(
										new criteria(sqlEQUAL,'id_empresa',$id_empresa),
										new criteria(sqlEQUAL,'aprobado',1)
                    )));
                }
		
				
		if($fechac!=null){
			$qrecnotad->where($fechac);
		}
		$recnotad= $qrecnotad->doSelect();
		foreach($recnotad as $notad){
                    if($taller == "2"){
			//$r->alert($rep->operador.' '.$rep->id_tipo_orden);
			//llenenado la tabla con las intersecciones
			$valor=$notad->precio;
			if($aplica_iva){
				$valor=$valor+(($valor*$notad->iva)/100);
			}
                        $valor=$valor*-1;//Multiplica por -1 para RESTAR
			$ndata_orden[$notad->id_orden]['n']+=$valor;
			$datan[$notad->id_tipo_orden]+=$valor;
                        $totalnotas+=$valor;
                    }
		}
	//contruyendo la tabla:
		$html='<table class="order_table"><thead><tr class="xajax_order_title"><td>CONCEPTO</td>';
	//volcando los encabezados
		foreach($tipos_orden as $tipo){
			$html.='<td>'.$tipo.'</td>';
		}
		$html.='<td style="border-left:2px solid black;">Totales</td></thead><tbody>';//<td>Acumulado Mes</td><td>Acumulado Anual</td>
		
	//volcando los resultados:
		foreach($operadores as $kop=>$op){
			$html.='<tr><td>'.$op.'</td>';
			foreach($tipos_orden as $kto=>$to){
				$html.='<td style="text-align:right;">'._formato(ifnull($data[$kop][$kto],'0')).'</td>';
			}
			$html.='<td style="border-left:2px solid black;text-align:right;">'._formato(ifnull($total_op[$kop],'0')).'</td></tr>';//
			
		}
		
	//volcando los primeros Totales
		$html.='<tr><td style="font-weight:bold;">Total Mano de Obra: </td>';
//		var_dump($total_to);
		foreach($tipos_orden as $kto=>$tipo){
			$totalMO+=$total_to[$kto];
			$html.='<td style="text-align:right;font-weight:bold;">'._formato(ifnull($total_to[$kto],'0')).'</td>';
		}
		$html.='<td style="border-left:2px solid black;text-align:right;font-weight:bold;">'._formato(ifnull($totalMO,'0')).'</td></tr>';
			
		//TOTALES UT ARRIBA POR TIPO DE ORDEN
		$html.='<tr><td style="border-bottom:2px solid #bfbfbf;">Total UT: </td>';
		foreach($tipos_orden as $idTipoOrden => $tipo){
			$totalTipoUT += $dataTipoUT[$idTipoOrden];//
			$html.='<td style="text-align:right;  border-bottom:2px solid #bfbfbf;">'.ifnull($dataTipoUT[$idTipoOrden],'0').'</td>';
		}
		$html.='<td style="border-left:2px solid black; border-bottom:2px solid #bfbfbf; text-align:right;">'._formato(ifnull($totalTipoUT,'0')).'</td></tr>';
			
	//volcando los resultados de repuestos
		foreach($repuestos as $rkop=>$rop){
			$html.='<tr><td>'.$rop.'</td>';
			foreach($tipos_orden as $rkto=>$to){
				$html.='<td style="text-align:right;">'._formato(ifnull($datar[$rkop][$rkto],'0')).'</td>';
			}
			$html.='<td style="border-left:2px solid black;text-align:right;">'._formato(ifnull($rtotal_op[$rkop],'0')).'</td></tr>';//
			
		}
	//volcando los Totales repuestos
		$html.='<tr><td style="font-weight:bold;">Total de Repuestos: </td>';
		foreach($tipos_orden as $kto=>$tipo){
			$totalREP+=$rtotal_to[$kto];
			$html.='<td style="text-align:right;font-weight:bold;">'._formato(ifnull($rtotal_to[$kto],'0')).'</td>';
		}
		$html.='<td style="border-left:2px solid black;text-align:right;font-weight:bold;">'._formato(ifnull($totalREP,'0')).'</td></tr>';
		//$html.='</tbody>';
	//volcando los Totales Notas
		$html.='<tr><td style="font-weight:bold;">Total de Notas: </td>';
		foreach($tipos_orden as $kto=>$tipo){
			//$totalnotas+=$datan[$kto];
			$html.='<td style="text-align:right;font-weight:bold;">'._formato(ifnull($datan[$kto],'0')).'</td>';
		}
		$html.='<td style="border-left:2px solid black;text-align:right;font-weight:bold;">'._formato(ifnull($totalnotas,'0')).'</td></tr>';
        //volcando los Totales Descuento
		$html.='<tr><td style="font-weight:bold;">Total de Descuento: </td>';
		foreach($tipos_orden as $kto=>$tipo){
			//$totalnotas+=$datan[$kto];
			$html.='<td style="text-align:right;font-weight:bold;">'._formato(ifnull($rtotal_desc[$kto],'0')).'</td>';
		}
		$html.='<td style="border-left:2px solid black;text-align:right;font-weight:bold;">'._formato(ifnull($totalDesc,'0')).'</td></tr>';
		//$html.='</tbody>';
	//volcando los Totales TOTALES
		$html.='<tr><td style="border-top:2px solid black;font-weight:bold;">Total SERVICIOS: </td>';
		foreach($tipos_orden as $kto=>$tipo){
			$totalG+=$total_to[$kto]+$rtotal_to[$kto]+$datan[$kto]-$rtotal_desc[$kto];
			$html.='<td style="border-top:2px solid black;text-align:right;font-weight:bold;">'._formato(ifnull($total_to[$kto]+$rtotal_to[$kto]+$datan[$kto]-$rtotal_desc[$kto],'0')).'</td>';
		}
		$html.='<td style="border-left:2px solid black;border-top:2px solid black;text-align:right;font-weight:bold;">'._formato(ifnull($totalG,'0')).'</td></tr></tbody></table>';
		//aplicando los filtros y final:
                
                $spanIva = nombreIva(1);
                
		if($aplica_iva){
			$tiva="INCLUYENDO ".$spanIva;
		}else{
			$tiva="SIN ".$spanIva;			
		}
//22-02-2010:
//generando el listado de detalles de las Ordenes:
//PASO 9: ejecutando la  vista del resumen
                if($taller == "1"){
                    $q_ordenes= $c->sa_v_informe_final_ordenes->doQuery($c, new criteria(sqlAND, array(
                                                                                new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                                                                                new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                                                                                new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
																				new criteria(sqlNOTEQUAL,'id_filtro_orden',11)
																				)));
                }elseif($taller == "2"){
                    $q_ordenes= $c->sa_v_informe_final_ordenes->doQuery($c, new criteria(sqlAND, array(
                                                                                new criteria(sqlEQUAL,'id_empresa',$id_empresa)))
                                                                                );
                }
				
		
		if($fechac!=null){
                        //$q_ordenes->where($fechac)->orderBy($c->sa_v_informe_final_ordenes->f_fecha_factura);
			$q_ordenes->where($fechac);
		}
               // $r->alert($q_ordenes->getSelect($c,new criteria(sqlEQUAL,'id_empresa',$id_empresa)));
		$rec_ordenes= $q_ordenes->doSelect($c);
		//var_dump($q_ordenes->getSelect());

		if($rec_ordenes){
			
			 
                    if($rec_ordenes->getNumRows()!=0){
                      /* @var $orden recordset */
			foreach($rec_ordenes as $orden){
				
				if($orden->id_empresa != $id_empresa){
					
				}else{
                          //llenando el array con los datos de las ordenes //no aplica
                          //$info_orden[$orden->id_orden]['fecha']=$orden->f_fecha_factura;
                            $campos1= "";
                            $condicion1= "";
                            $sql1= "";

                            $campos1= "*";
                            $condicion1= "numeroPedido= ".$orden->id_orden;
                            $condicion1.= " AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1";

                            $sql1= "SELECT ".$campos1." FROM cj_cc_encabezadofactura WHERE id_empresa = ".$id_empresa." AND ".$condicion1.";";
                            $rs1 = mysql_query($sql1) or die(mysql_error());
                            $row1 = mysql_fetch_assoc($rs1);

                            $numeroFacturaVale= "";

                            if($row1['numeroFactura'] == ""){
                                $campos2= "";
                                $condicion2= "";
                                $sql2= "";

                                $campos2= "*";
                                $condicion2= "id_orden= ".$orden->id_orden;

                                $sql2= "SELECT ".$campos2." FROM sa_vale_salida WHERE id_empresa = ".$id_empresa." AND ".$condicion2.";";
                                $rs2 = mysql_query($sql2) or die(mysql_error());
                                $row2 = mysql_fetch_assoc($rs2);
                                $numeroFacturaVale= $row2['numero_vale'];
                            }else{
                                $numeroFacturaVale= $row1['numeroFactura'];
                            }

                            $totalesOrdenes1= "";
                            $totalesOrdenes1= $data_orden[$orden->id_orden]['t']+$data_orden[$orden->id_orden]['tot']
                                    +$data_orden[$orden->id_orden]['r']+$data_orden[$orden->id_orden]['n']-$data_orden[$orden->id_orden]['desc'];

                            $totalesTotal1+= $totalesOrdenes1;
                            if(_formato(ifnull($totalesOrdenes1,'0'))==0 && $taller == "1"){
                                continue;//si esta en cero y es produccion, tiene nota que no mostrara
                            }
							
                          $ohtml.='<tr>
						  			  <td style="text-align:center;" idordenoculta="'.$orden->id_orden.'">'.$orden->numero_orden.'</td>
									  <td style="text-align:center;">-</td><td style="text-align:center;">'.$numeroFacturaVale.'</td>
									  <td style="text-align:center;">'.$orden->nombre_tipo_orden.'</td>
									  <td style="text-align:center;">'.$orden->f_fecha_factura.'</td>
									  <td>'.ifnull($dataUT[$orden->id_orden],'0').'</td>
									  <td>'._formato(ifnull($data_orden[$orden->id_orden]['t'],'0')).'</td>
									  <td>'._formato(ifnull($data_orden[$orden->id_orden]['tot'],'0')).'</td>
									  <td>'._formato(ifnull($data_orden[$orden->id_orden]['r'],'0')).'</td>
									  <td>'._formato(ifnull($data_orden[$orden->id_orden]['n'],'0')).'</td>
									  <td>'._formato(ifnull($data_orden[$orden->id_orden]['desc'],'0')).'</td>
									  <td><b>'._formato(ifnull($totalesOrdenes1,'0')).'</b></td>
								  </tr>';
						  
			}
                    }
					
					
					}//fin if gregor
		}
               
//17-03-2010:
//PASO 10: ejecutando la  vista del resumen de devoluciones
                if($taller == "1"){
                    $q_ordenesd= $c->sa_v_informe_final_ordenes_dev->doQuery($c,new criteria(sqlAND, array(
                                                                                new criteria(sqlEQUAL,'id_empresa',$id_empresa),
                                                                                new criteria(sqlNOTEQUAL,'id_filtro_orden',9),
                                                                                new criteria(sqlNOTEQUAL,'id_filtro_orden',10),
																				new criteria(sqlNOTEQUAL,'id_filtro_orden',11)
																				)));	//antes estaba abajo y no funcionaba
                }elseif($taller == "2"){
                    $q_ordenesd= $c->sa_v_informe_final_ordenes_dev->doQuery($c,new criteria(sqlAND, array(
                                                                                new criteria(sqlEQUAL,'id_empresa',$id_empresa)))
                                                                                );	//antes estaba abajo y no funcionaba
                }
		
		$q_ordenesd->doSelect();	
		//var_dump($q_ordenesd->getSelect());
		if($fechac!=null){
			$q_ordenesd->where($fechac);
		}
		
               // $r->alert($q_ordenes->getSelect($c,new criteria(sqlEQUAL,'id_empresa',$id_empresa)));
		$rec_ordenesd= $q_ordenesd->doSelect($c,new criteria(sqlEQUAL,'id_empresa',$id_empresa));
		if($rec_ordenesd){
                    if($rec_ordenesd->getNumRows()!=0){
			foreach($rec_ordenesd as $ordend){
                          //llenando el array con los datos de las ordenes devueltas //no aplica
                          /*if(!isset($info_orden[$ordend->id_orden])){
                            $info_orden[$ordend->id_orden]['fecha']=$ordend->f_fecha_factura;
                          }*/
						  
						  
                            $campos2= "";
                            $condicion2= "";
                            $sql2= "";

                            $campos2= "*";
                            $condicion2= "numeroPedido= ".$ordend->id_orden;
                            $condicion2.= " AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1";

                            $sql2= "SELECT ".$campos2." FROM cj_cc_encabezadofactura WHERE id_empresa = ".$id_empresa." AND ".$condicion2.";";
                            $rs2 = mysql_query($sql2) or die(mysql_error());
                            $row2 = mysql_fetch_assoc($rs2);

                            $totalesOrdenes2= "";
                            $totalesOrdenes2= $ndata_orden[$ordend->id_orden]['t']+$ndata_orden[$ordend->id_orden]['tot']
                                    +$ndata_orden[$ordend->id_orden]['r']+$ndata_orden[$ordend->id_orden]['n']-$ndata_orden[$ordend->id_orden]['desc'];

                            $totalesTotal2+= $totalesOrdenes2;
                            
                          $ohtml.='<tr>
						  			 <td style="text-align:center;" idordenocultaDEV="'.$ordend->id_orden.'">'.$ordend->numero_orden.'</td>
									 <td style="text-align:center;">'.$ordend->nota_credito.'</td>
									 <td style="text-align:center;">'.$row2['numeroFactura'].'</td>
									 <td style="text-align:center;">'.$ordend->nombre_tipo_orden.'</td>
									 <td style="text-align:center;">'.$ordend->f_fecha_factura.'</td>
									 <td>'.ifnull($dataUT[$ordend->id_orden],'0').'</td>
									 <td>'._formato(ifnull($ndata_orden[$ordend->id_orden]['t'],'0')).'</td>
									 <td>'._formato(ifnull($ndata_orden[$ordend->id_orden]['tot'],'0')).'</td>
									 <td>'._formato(ifnull($ndata_orden[$ordend->id_orden]['r'],'0')).'</td>
									 <td>'._formato(ifnull($ndata_orden[$ordend->id_orden]['n'],'0')).'</td>
									 <td>'._formato(ifnull($ndata_orden[$ordend->id_orden]['desc'],'0')).'</td>
									 <td><b>'._formato(ifnull($totalesOrdenes2,'0')).'</b></td>
								</tr>';
			}
                    }
		}
		
		//ksort($dataUT);
		//var_dump($dataUT);
				
//NO APLICA PORQUE SE DEBE DISCRIMINAR LAS NC:
//recorriendo al final todas las ordenes, tanto Notas como ordenes
                /*if(count($info_orden)!=0){
                  foreach($info_orden as $kid_orden => $dorden){
                          //$r->alert($orden->f_fechaRegistroFactura.' '.$data_orden[$orden->id_orden]['r']);
                          $ohtml.='<tr><td style="text-align:center;">'.$kid_orden.'</td><td style="text-align:center;">'.$dorden['fecha'].'</td><td>'._formato(ifnull($data_orden[$kid_orden]['t'],'0')).'</td><td>'._formato(ifnull($data_orden[$kid_orden]['tot'],'0')).'</td><td>'._formato(ifnull($data_orden[$kid_orden]['r'],'0')).'</td><td>'._formato(ifnull($data_orden[$kid_orden]['n'],'0')).'</td></tr>';
                  }
                }*/
		$html.='<div>Resumen:</div>
					<table class="order_table" style="text-align:center;">
						<thead>
							<tr class="xajax_order_title">
								<td>Orden</td>
								<td>NC</td>
								<td>Factura/Vale</td>
								<td>Tipo</td>
								<td>Fecha</td>
								<td>UT</td>
								<td>Importe MO</td>
								<td>Importe TOT</td>
								<td>Importe REP</td>
								<td>Importe Notas</td>
								<td>Descuento</td>
								<td>Totales</td>
							</tr>
						</thead>
						<tbody style="text-align:right;">'.$ohtml.'</tbody>
							<tr class="order_table" style="text-align:right;background-color: #bfbfbf;">
								<td style="text-align:center;" colspan="5"><b>TOTALES</b></td>
								<td><b>'.ifnull(array_sum($dataUT),'0').'</b></td>
								<td><b>'._formato(ifnull($total_op[1],'0')).'</b></td>
								<td><b>'._formato(ifnull($total_op[4],'0')).'</b></td>
								<td><b>'._formato(ifnull($totalREP,'0')).'</b></td>
								<td><b>'._formato(ifnull($totalnotas,'0')).'</b></td>
								<td><b>'._formato(ifnull($totalDesc,'0')).'</b></td>
								<td><b>'._formato(ifnull($totalesTotal1+$totalesTotal2,'0')).'</b></td>
							</tr>
                	</table>';
                if($taller == "1"){
                    $filtroTallerLeyenda = " | Modo: Producci&oacute;n Taller";
                }elseif($taller == "2"){
                    $filtroTallerLeyenda = " | Modo: Total";
                }
		$html.='<br />Informe Generado el '.adodb_date(DEFINEDphp_DATETIME12).' - Empresa: '.$argumentos['Empresa'].' | <strong>'.$tiva.' '.$filtroTallerLeyenda.'</strong><br /> '.$removefilterfecha;

                //$rec=$paginador->run();

                $r->assign($paginador->layer,inner,$html);

                $r->assign('campoFecha','value',$fec);
		$r->script($paginador->fillJS('datos'));
		
                //$r->alert(utf_export($data_orden));

		$c->close();
		//$r->alert($id_empresa);
		return $r;
	}
	
	xajaxRegister('load_page');

	xajaxProcess();

	includeDoctype();

	$c= new connection();
	$c->open();
	//llenando lo necesario
	$empresas=getEmpresaList($c);//$c->pg_empresa->doSelect($c)->getAssoc($c->pg_empresa->id_empresa,$c->pg_empresa->nombre_empresa);
	
        $spanIva = nombreIva(1);
        $ivas=array(0=>'Sin '.$spanIva, 1=>'Con '.$spanIva);
	//$empresas = getEmpresaList($c);//$c->sa_v_empresa_sucursal->doSelect($c)->getAssoc('id_empresa','nombre_empresa_sucursal');
	$c->close();
        $dia_hoy=adodb_date(DEFINEDphp_DATE);
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
		
                
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Reporte Final de Servicios</title>
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
			
			
			.opfecha{
				vertical-align:middle;
			}
		</style>
		<script type="text/javascript">
		
			var datos = {
                                fecha: '<?php echo $dia_hoy; ?>', //predefiniendo el dia
				fecha_rank:2,//predefiniendo que sea mensual
				page:0,
				maxrows:15,
				order:null,
				ordertype:null,
				busca:'',
                  		layer:'capaTabla',
				iva:0,
                                taller:1, //1 solo taller, 2 todo
				h_empresa:'<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>', //para el primer cargar
                                Empresa:'<?php echo $nombreEmpresa; ?>'
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
				//cargar();//ya carga al cambiar la empresa por id
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
                        
                        function cambio_taller(val){
                                datos.taller=val;
                                cargar();
                        }
                        
			var fecha_filtro = new dateDialog(cargar_fecha);
			/*for(var i in dd){
				alert(i);
			}*/

                        function exportarExcel(){
				window.open('reportes/sa_informe_final_servicio_excel.php?valBusq='+toPaginator(datos),'_self');				
			}
		</script>
	</head>
	<body>
<?php include("banner_servicios.php"); ?>
<div style="width:90%; background:#FFFFFF; margin: auto;">
	<table align="center" border="0" width="100%">
		<tr>
			<td align="right" class="titulo_pagina" ><span >Servicios</span><br />
			<span class="subtitulo_pagina" >(Reporte Final de Servicios)</span></td>
		</tr>
	</table>
	
	<br />
	
<!--MARCO PRINCIPAL-->
	<div id="principal">
		<div class="noprint">
			
			<fieldset style="display:inline;">
				<legend>Filtro por Fecha</legend>
                                <button type="button"  value="reset" title="Fecha" onClick="fecha_filtro.showDateDialog(this);" ><img alt="seleccionar fecha" border="0" src="<?php echo getUrl('img/iconos/select_date.png') ?>" /> <span id="filtrofecha"><?php echo $dia_hoy; ?></span></button>
				<label class="opfecha"><input type="radio" name="fecha_rank" value="1" onClick="cambiar_fecharank(this);"  />Dia</label>
				<label class="opfecha"><input type="radio" name="fecha_rank" onClick="cambiar_fecharank(this);" checked="checked" value="2" />Mes</label>
				<label class="opfecha"><input type="radio" name="fecha_rank" onClick="cambiar_fecharank(this);" value="3" />A&ntilde;o</label>
			</fieldset>	
                    
                        <fieldset style="display:inline;">
				<legend>Filtro Taller</legend>                                
				<label class="opfecha" title="Incluir&aacute; solo lo producido, sin notas, sin tipos de orden extras, y sin facturas/vales en cero" ><input type="radio" name="taller" onClick="cambio_taller(this.value);" checked="checked" value="1" />Producci&oacute;n taller</label>
				<label class="opfecha"  title = "Incluir&aacute; toda informaci&oacute;n de servicios, todos los tipos de orden y facturas/vales"><input type="radio" name="taller" onClick="cambio_taller(this.value);" value="2" />Todo</label>
			</fieldset>
			<input style="display:none;" type="text" id="busca" onKeyPress="keyEvent(event,buscar);" />
			
			
                        <button style="display:none;" type="button" value="buscar" title="Buscar" onClick="buscar();" ><img alt="buscar" border="0" src="<?php echo getUrl('img/iconos/find.png') ?>" /></button>
                        <button type="button" value="reset" title="Recargar" onClick="restablecer(); cambio_empresa(document.getElementById('empresa'));" ><img alt="recargar" border="0" src="<?php echo getUrl('img/iconos/cc.png') ?>" /></button>
                        <button style="display:none;" type="button" value="reset" title="Restablecer" onClick="fecha_filtro.showDateDialog(this);" ><img alt="seleccionar fecha" border="0" src="<?php echo getUrl('img/iconos/select_date.png') ?>" /></button>
			
			<?php
			echo '<empresa id="listado_empresas">';
                              //echo inputSelect('empresa',$empresas,null,'onchange=cambio_empresa(this)',null,' -Seleccione Empresa- ');
			echo '</empresa>';
                              echo ' - '.inputSelect('iva',$ivas,0,'onchange=cambio_iva(this)',false);
                        ?>
                        <button type="button" value="print" title="Imprimir" onClick="print();" ><img alt="imprimir" border="0" src="<?php echo getUrl('img/iconos/print.png') ?>" />Imprimir</button>
                        
                    	<button style="cursor:default" class="noprint" onclick="exportarExcel();" type="button"><img src="../img/iconos/page_excel.png">Exportar</button>
					                        
			<hr />
		</div>
		<div id='capaTabla'></div>
		<div id="paginador" align="center" style="overflow:hidden; height:1%;" ></div>
	</div>
<!--MARCO PRINCIPAL-->

</div>
<?php include("menu_serviciosend.inc.php"); ?>
	
	<script type="text/javascript" language="javascript">		
		cargar();
		
		xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="cambio_empresa(this)";','empresa','listado_empresas'); //buscador
	</script>
	</body>
</html>

<?php

    function nombreIva($idIva){
        //cuando se crea no posee iva, por lo tanto deberia ser el primero id 1 itbms-iva
        if($idIva == NULL || $idIva == "0" || $idIva == "" || $idIva == " "){
            $idIva = 1;
        }    
        $query = "SELECT observacion FROM pg_iva WHERE idIva = ".$idIva."";
        $rs = mysql_query($query);
        if(!$rs){ return ("Error cargarDcto \n".mysql_error().$query."\n Linea: ".__LINE__); }

        $row = mysql_fetch_assoc($rs);

        return $row['observacion'];
    }
    
    
?>