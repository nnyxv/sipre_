<?php

header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=sa_informe_tempario.xls");
header("Pragma: no-cache");
header("Expires: 0");

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

//NO NECESARIOS PORQUE NO SE USA XAJAX
//require_once("../inc_sesion.php");
//
////implementando xajax;
	require_once("../control/main_control.inc.php");
	require_once("../control/iforms.inc.php");
	require_once("../control/funciones.inc.php");
//
//require_once ("../../connections/conex.php");//lo necesita ac_iv_general
//include("../controladores/ac_iv_general.php");//tiene la funcion listado de empresas final

	function load_page($args=''){//$page,$maxrows,$order,$ordertype,$capa,
				
		setLocaleMode();
		
		//$r->alert($args);
		$c = new connection();
		$c->open();
		
		$sa_paquetes = $c->sa_v_tempario;
				
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
		
		
		if($id_empresa==null){			
			//return die('Seleccione una Empresa');
                }else{
                    $query->where(new criteria(sqlEQUAL,$sa_paquetes->id_empresa,$id_empresa));
                }
		
		$rec=$paginador->run();
		
		if($rec){
			foreach($rec as $v){
				
				$html.='<table class="order_table"><col width="15%" /><col width="20%" /><col width="10%" /><col width="5%" /><col width="10%" /><col width="10%" /><col width="15%" /><col width="15%" /><thead>';
				
				$html.='<tr><td style="background-color: #bfbfbf; font-weight:bold;">Posici&oacute;n</td><td style="background-color: #bfbfbf; font-weight:bold;">Descripci&oacute;n</td><td style="background-color: #bfbfbf; font-weight:bold;">Modo</td><td style="background-color: #bfbfbf; font-weight:bold;">Operador</td><td style="background-color: #bfbfbf; font-weight:bold;">Precio</td><td style="background-color: #bfbfbf; font-weight:bold;">Costo</td><td style="background-color: #bfbfbf; font-weight:bold;">Secci&oacute;n</td><td style="background-color: #bfbfbf; font-weight:bold;">Subsecci&oacute;n</td></tr></thead><tbody>';
				$html.='<tr><td>'.$v->codigo_tempario.'</td><td>'.$v->descripcion_tempario.'</td><td align="center">'.$v->descripcion_modo.'</td><td align="center">'.$v->operador.'</td><td>'.$v->precio.'</td><td>'.$v->costo.'</td><td>'.$v->descripcion_seccion.'</td><td>'.$v->descripcion_subseccion.'</td></tr></tbody></table>';
				
				$max_col=intval($argumentos['max_col']);
				$html.='<table class="order_table"><thead><tr>';
				for($i=0;$i<$max_col;$i++){
					$html.='<td width="'.(50/$max_col).'%" style="background-color: #bfbfbf; font-weight:bold;">Unidad</td><td width="'.(50/$max_col).'%"  style="background-color: #bfbfbf; font-weight:bold;">UT</td>';
				}				
				$html.='</tr></thead><tbody>';
				//cargando los detalles de tempario
				$sa_v_unidad_basica=$c->sa_v_unidad_basica;
				$sa_tempario_det= new table('sa_tempario_det','',$c);
				$join= $sa_tempario_det->join($sa_v_unidad_basica,$sa_tempario_det->id_unidad_basica,$sa_v_unidad_basica->id_unidad_basica);
				$qdet=new query($c);
				$qdet->add($join);
				$qdet->where(new criteria(sqlEQUAL,$sa_tempario_det->id_tempario,$rec->id_tempario));
				
				
				$recdet=$qdet->doSelect();
				if($recdet){
					$it=0;
					$html.='<tr>';
					foreach($recdet as $temp){
						$it++;
						$html.='<td align="right">'.$temp->nombre_unidad_basica.'&nbsp;</td><td>&nbsp;'.$temp->ut.'</td>';
						if($it==$max_col){
							$it=0;
							$html.='</tr><tr>';
						}
					}
					if($it!=0){
						$html.='<td colspan="'.(($max_col-$it)*2).'">&nbsp;</td>';
					}
					$html.='</tr>';
				}
				

				
				$html.='</tbody></table><br />';
			}
		}
		$html.='<br />Informe Generado el '.date(DEFINEDphp_DATETIME12).' - Empresa: '.$argumentos['Empresa'].'<strong>'.$tiva.'</strong>';
		
		
		$c->close();
		return $html;		
	}
    

echo "<b>SERVICIOS</b><br>";
echo "<table><tr><td colspan='2'>Reporte posiciones de trabajo</td></tr></table><br>";
    
echo load_page($_GET['valBusq']);