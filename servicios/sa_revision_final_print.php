<?php
@session_start();
define('PAGE_PRIV','sa_revision_final');
require_once("../inc_sesion.php");

	require_once("control/main_control.inc.php");
	require_once("control/funciones.inc.php");
	
	if(isset($_GET['id_revision_final'])){
		$id_revision_final=intval($_GET['id_revision_final']);
	}else{
		echo 'no se ha especificado orden';
		exit;
	}
	
	$c= new connection();
	$c->open();
	$recrev=$c->sa_revision_final->doSelect($c, new criteria(sqlEQUAL,'id_revision_final',$id_revision_final));
	if($recrev->getNumRows()==0){
		echo 'no existe';
		exit;		
	}
	
	$rec= $c->sa_v_revision_periodica->doSelect($c, new criteria(sqlEQUAL,'id_orden',$recrev->id_orden));
	/*if($recrev->getNumRows()!=0){
		$id_revision_final=$recrev->id_revision_final;
	}else{
		$id_revision_final='';
		//$r->alert("no");
	}*/
	$rango_def=$recrev->rango;
	$rec2= $c->sa_rev_info->doSelect($c,new criteria(sqlEQUAL,'titulo',-1));	
	$part= explode(':',$rec2->rango);	
	$ac=$part[0];
	$i=$part[1];
	$dr=$part[3];
	$t=$part[2];
	$res=$ac;
	$arr[$res]=_formato(intval($res),0);
	for($i+=$dr;$i<=$t;$i+=$dr){
		$res=($i*$ac);
		if($rec->kilometraje>$res){
			$rango_def=$res;
		}
		$arr[$res]=_formato($res,0);
	}
	includeDoctype();
	
	$t = $c->sa_rev_info;
	$q = $t->doQuery($c);
	//var_dump($arr);
	/*$cond=new criteria(sqlOR,
		new criteria(sqlEQUAL,'id_revision_final',$id_revision_final),
		new criteria(sqlIS,'id_revision_final',sqlNULL)
	);*/
	//agregando las subconsultas
		$sc1= new table('sa_det_revision_final','sa_det_revision_final1');
		$sc1->insert('id_det_revision_final','',field::tInt);
		$q1=new query($c,"id_det_revision_final");
		$q1->add($sc1);
		$q1->where(new criteria(sqlEQUAL,$c->sa_rev_info->id_rev_info,'sa_det_revision_final1.id_rev_info'));
		$q1->where(new criteria(sqlEQUAL,$id_revision_final,'sa_det_revision_final1.id_revision_final'));
		$q1->setLimit(1);
		$t->add($q1);		
		
		$sc2= new table('sa_det_revision_final','sa_det_revision_final2');
		$sc2->insert('valor_revision','',field::tInt);
		$q2=new query($c,"valor_revision");
		$q2->add($sc2);
		$q2->where(new criteria(sqlEQUAL,$c->sa_rev_info->id_rev_info,'sa_det_revision_final2.id_rev_info'));
		$q2->where(new criteria(sqlEQUAL,$id_revision_final,'sa_det_revision_final2.id_revision_final'));
		$q2->setLimit(1);
		$t->add($q2);
	
	
	$q->where(new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']));	
	$q->where(new criteria(sqlNOTEQUAL,'titulo','-1'));	
	$irec= $q->doSelect();
	$matrix=array();
	
	if($_GET['print_info']!='false'){
		foreach($irec as $i){
			$rango=$irec->rango;
			//echo $recrev->rango.' - '.$i->titulo.' '.$i->valor_revision.'<br />';
			//recorriendo el vector:
			$stock='';
			foreach($arr as $k=>$v){
				if(($k % $rango) == 0){
					$stock.='i0f';
					//$matrix[$mi][$mj]=0;
				}else{
					$stock.='i&nbsp;f';
					//$matrix[$mi][$mj]='';
				}
				//$mj++;
			}
			$matrix[$i->id_rev_info]=$stock;
			//echo $stock.'<br />';
			
		}		
		//verifica la versin del navegador para adaptar el listado:
		$navegador = getenv("HTTP_USER_AGENT");
		if (preg_match("/MSIE/i", $navegador))
		{
			$isIE = true;
		}
		else
		{
			$isIE = false;
		}
	}
	
	$checkimg=getUrl('img/iconos/check2.gif');
	//$nocheckimg=getUrl('img/nocheck2.gif');//<img src="'.$nocheckimg.'" alt="no aplica"/>
	$vv=array(
		0=>'<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>',
		1=>'<td><img src="'.$checkimg.'" alt="bueno"/></td><td>&nbsp;</td><td>&nbsp;</td>',
		2=>'<td>&nbsp;</td><td><img src="'.$checkimg.'" alt="ajuste"/><td>&nbsp;</td>',
		3=>'<td>&nbsp;</td><td>&nbsp;</td><td><img src="'.$checkimg.'" alt="reemplazo"/>'
	);
	
	$recempresa=$c->sa_v_empresa_sucursal->doSelect($c, new criteria(sqlEQUAL,'id_empresa',$_SESSION['idEmpresaUsuarioSysGts']));
	
	$mecanico=$c->pg_v_empleado->doSelect($c, new criteria(sqlEQUAL,'id_empleado',$recrev->id_empleado_mecanico));
	$jefe_taller=$c->pg_v_empleado->doSelect($c, new criteria(sqlEQUAL,'id_empleado',$recrev->id_empleado_jefe_taller));
	
?>

<html>
	<head>
		<?php 
			includeMeta();
			//includeScripts();
			//getXajaxJavascript();
			//includeModalBox();
		?>
		<link rel="stylesheet" type="text/css" href="css/sa_general.css" />
		
                <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Revisión Final</title>
                <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
                
		<script type="text/javascript">
			
		</script>
		<style type="text/css">
			.order_table tr.titulo td{
				text-align:center;
				font-weight:bold;
			}
			.order_table tr td{
				font-size:8px;
				text-align:center;
				/*padding-bottom:0px;*/
				border-color:black;
				border-style:solid;
			}
			table tr td:hover{
				font-size:12px;
			}
			#titles tr td{
				border:1px solid #000000;
			}
			.min_label{
				font-size:8px;
			}
			.firma{
				height:45px;
			}
		</style>
	</head>
	<body <?php if($_GET['view']=='print') echo 'onload="print();"'; ?> >
		<table class="hidden_table">
			<tr>
				<td rowspan="2" style="width:20%;"><img style="height:25px;" alt="Empresa" src="<?php echo $recempresa->logo_empresa; ?>" /></td>
				<td style="text-align:center;"><?php echo $recempresa->nombre_empresa_sucursal; ?></td>
				<td rowspan="2" style="width:20%;">&nbsp;</td>
			</tr>
			<tr>
				<td class="min_label" style="text-align:center;">INSPECCIONES PERI&Oacute;DICAS</td>
			</tr>
		</table>
		
				
			
		<table class="hidden_table">
			<tr>
				<td rowspan="2">
		<table class="order_table" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
		<tbody>
			<tr class="titulo">				
				<td colspan="4" class="min_label" style="text-align:left;">
				OR: <?php echo $rec->numero_orden; ?>&nbsp;|&nbsp;
				Fecha: <?php echo parseTiempo(str_tiempo($recrev->tiempo_revision)); ?>&nbsp;|&nbsp;
				Placa: <?php echo $rec->placa; ?>&nbsp;|&nbsp;
				<?php echo $spanKilometraje; ?>: <?php echo $rec->kilometraje; ?>&nbsp;|&nbsp;
				
				<?php
				if($_GET['print_info']!='false'){
					echo '</td><td colspan="'.count($arr).'" style="text-align:right;">';
				}
				echo 'N. '.$recrev->id_revision_final;
				?>
				</td>
			</tr>
			<tr class="titulo">
				<td width="10px;">X</td><td width="10px;">A</td><td width="10px;">R</td><td>Puntos<?php if($_GET['print_info']!='false'){echo '/'.$spanKilometraje; } ?>:</td>
				
<?php



	if($_GET['print_info']!='false'){
		foreach($arr as $k=>$v){			
			/*if(!$isIE){*/
				//$kv=strval($k);
				//$vx='';
				//for($i=0;$i<strlen($kv);$i++){
				//	$vx.=$kv[$i].'<br />';
				//}
				$vx='<img alt="'.$v.'" src="../sa_vt.php?t='.urlencode($v).'" />';
				$vstyle='vertical-align:bottom;';
			/*}else{
				$vx=$v;
				$vstyle='writing-mode: tb-rl;filter: flipV flipH;';
			}*/
			echo '<td style="text-align:left;font-weight:100;'.$vstyle.'">'.$vx.'</td>';
		}
	}
?>
			</tr>		
<?php
	foreach($irec as $v){
		if($_GET['print_info']!='false'){
			$k=$matrix[$v->id_rev_info];
			$kt=str_replace('f','</td>',$k);
			$kt=str_replace('i','<td class="" align="center">',$kt);
		}else{
			$kt='';
		}
		echo '<tr>'.$vv[intval($v->valor_revision)].'<td class="" style="text-align:left;">'.$v->titulo.'</td>'.$kt.'</tr>';
	}
?>
		</tbody>
		</table>
				</td>
				<td style="width:20%;vertical-align:top;">
					<table class="hidden_table">
						<tr>
							<td class="min_label">
							Correlativo:<br />
							<?php echo $recrev->correlativo; ?>
							</td>
						</tr>
						<tr>
							<td class="min_label">
							<hr />
							<div class="firma">&nbsp;</div>
							Observaciones:<br />
							<?php echo $recrev->observaciones; ?>
							</td>
						</tr>
						<tr>
							<td class="min_label">
								<hr />
								<div class="firma">&nbsp;</div>
								Leyenda:<br />
								X = Bueno<br />
								A = Requiere Ajuste<br />
								R = Requiere Reemplazo
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="min_label" style="vertical-align:bottom;">
				<div class="firma">Firma del cliente:</div>
				<hr />
				<div style="text-align:center;"><?php echo $rec->cedula_cliente.'-'.$rec->apellido.' '.$rec->nombre; ?></div>
				<div class="firma">&nbsp;</div>
				<div class="firma">Firma del Jefe taller:</div>
				<hr />
				<div style="text-align:center;"><?php echo $jefe_taller->cedula_nombre_empleado; ?></div>
				<div class="firma">&nbsp;</div>
				<div class="firma">Firma del Mec&aacute;nico:</div>
				<hr />
				<div style="text-align:center;"><?php echo $mecanico->cedula_nombre_empleado; ?></div>
				</td>
			</tr>
		</table>
	</body>
</html>