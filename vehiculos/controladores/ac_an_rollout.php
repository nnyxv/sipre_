<?php
include('raResponse.php');
include('../../connections/conex.php');
/******************************************************/

function crearUR($form){
	$objResponse = new raResponse();
	
	if(!$form["hdn_cod_p"]){
		$query=sprintf("insert into an_rollout_autos (enero, febrero, marzo, abril, mayo, junio, julio, agosto, septiembre, octubre, noviembre, diciembre) values (%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d)",
			$form['txt_presu'][0],
			$form['txt_presu'][1],
			$form['txt_presu'][2],
			$form['txt_presu'][3],
			$form['txt_presu'][4],
			$form['txt_presu'][5],
			$form['txt_presu'][6],
			$form['txt_presu'][7],
			$form['txt_presu'][8],
			$form['txt_presu'][9],
			$form['txt_presu'][10],
			$form['txt_presu'][11]);
		//$objResponse->asignar("tst","innerHTML", $query);
		$rs = mysql_query($query);
		$presupuesto_id = mysql_insert_id();
		$query=sprintf("insert into an_rollout_autos (enero, febrero, marzo, abril, mayo, junio, julio, agosto, septiembre, octubre, noviembre, diciembre) values (%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d)",
			$form['txt_fact'][0],
			$form['txt_fact'][1],
			$form['txt_fact'][2],
			$form['txt_fact'][3],
			$form['txt_fact'][4],
			$form['txt_fact'][5],
			$form['txt_fact'][6],
			$form['txt_fact'][7],
			$form['txt_fact'][8],
			$form['txt_fact'][9],
			$form['txt_fact'][10],
			$form['txt_fact'][11]);
		$rs = mysql_query($query);
		$factura_id = mysql_insert_id();
		$query = sprintf("insert into an_unidades_retiro (presupuesto, factura, vehiculo, anio) values (%d,%d,%d,%d)",
			$presupuesto_id,
			$factura_id,
			$form['lst_vehiculos'],
			$form['lst_anio']);
		$rs = mysql_query($query);
		
		$objResponse->alert("los datos se han procesado con éxito.");
	}else{
		$query=sprintf("update an_rollout_autos set enero = %d, febrero = %d, marzo = %d, abril = %d, mayo = %d, junio = %d, julio = %d, agosto = %d, septiembre = %d, octubre = %d, noviembre = %d, diciembre = %d where id=%d",
			$form['txt_presu'][0],
			$form['txt_presu'][1],
			$form['txt_presu'][2],
			$form['txt_presu'][3],
			$form['txt_presu'][4],
			$form['txt_presu'][5],
			$form['txt_presu'][6],
			$form['txt_presu'][7],
			$form['txt_presu'][8],
			$form['txt_presu'][9],
			$form['txt_presu'][10],
			$form['txt_presu'][11],
			$form["hdn_cod_p"]);
		//$objResponse->asignar("tst","innerHTML", $query);
		$rs = mysql_query($query);
		$query=sprintf("update an_rollout_autos set enero = %d, febrero = %d, marzo = %d, abril = %d, mayo = %d, junio = %d, julio = %d, agosto = %d, septiembre = %d, octubre = %d, noviembre = %d, diciembre = %d where id=%d",
			$form['txt_fact'][0],
			$form['txt_fact'][1],
			$form['txt_fact'][2],
			$form['txt_fact'][3],
			$form['txt_fact'][4],
			$form['txt_fact'][5],
			$form['txt_fact'][6],
			$form['txt_fact'][7],
			$form['txt_fact'][8],
			$form['txt_fact'][9],
			$form['txt_fact'][10],
			$form['txt_fact'][11],
			$form["hdn_cod_f"]);
		$rs = mysql_query($query);
	}
	$objResponse->alert('los datos se han sido guardados con éxito.');
	$objResponse->script("location.href='an_rollout.php'");
	$objResponse->enviar(); 
}

function cargarUR($vehiculo, $anio){
	$objResponse = new raResponse();
	//$objResponse->asignar("div_img_ur","style.display","");
	if($vehiculo!="no_select"){
		$query = sprintf("select * from vw_unidades_retiro where idVehiculo = %d and anio = %d", $vehiculo, $anio);
		$rs = mysql_query($query);
		$row = mysql_fetch_object($rs);
		if($row){
			//$objResponse->alert('se trajo algo');
			$objResponse->asignar("txt_presu_ene","value",$row->p_enero);
			$objResponse->asignar("txt_presu_feb","value",$row->p_febrero);
			$objResponse->asignar("txt_presu_mar","value",$row->p_marzo);
			$objResponse->asignar("txt_presu_abr","value",$row->p_abril);
			$objResponse->asignar("txt_presu_may","value",$row->p_mayo);
			$objResponse->asignar("txt_presu_jun","value",$row->p_junio);
			$objResponse->asignar("txt_presu_jul","value",$row->p_julio);
			$objResponse->asignar("txt_presu_ago","value",$row->p_agosto);
			$objResponse->asignar("txt_presu_sep","value",$row->p_septiembre);
			$objResponse->asignar("txt_presu_oct","value",$row->p_octubre);
			$objResponse->asignar("txt_presu_nov","value",$row->p_noviembre);
			$objResponse->asignar("txt_presu_dic","value",$row->p_diciembre);
			$objResponse->asignar("hdn_cod_p","value",$row->presupuesto);
			$objResponse->asignar("hdn_cod_u","value",$row->id);
			/*if($row->factura){
				$objResponse->asignar("txt_fact_ene","value",$row->f_enero);
				$objResponse->asignar("txt_fact_feb","value",$row->f_febrero);
				$objResponse->asignar("txt_fact_mar","value",$row->f_marzo);
				$objResponse->asignar("txt_fact_abr","value",$row->f_abril);
				$objResponse->asignar("txt_fact_may","value",$row->f_mayo);
				$objResponse->asignar("txt_fact_jun","value",$row->f_junio);
				$objResponse->asignar("txt_fact_jul","value",$row->f_julio);
				$objResponse->asignar("txt_fact_ago","value",$row->f_agosto);
				$objResponse->asignar("txt_fact_sep","value",$row->f_septiembre);
				$objResponse->asignar("txt_fact_oct","value",$row->f_octubre);
				$objResponse->asignar("txt_fact_nov","value",$row->f_noviembre);
				$objResponse->asignar("txt_fact_dic","value",$row->f_diciembre);
				$objResponse->asignar("hdn_cod_f","value",$row->factura);
			}else{
			
			}*/
			//$objResponse->script("alert(document.getElementById('hdn_cod_p').value)");
			$objResponse->asignar("div_img_ur","style.display","none");
			$objResponse->asignar("div_tabla_ur","style.display","");
		}else{
			$objResponse->asignar("div_img_ur","style.display","none");
			$objResponse->asignar("div_no_data","style.display","");
		}
	}else{
		$objResponse->asignar("div_img_ur","style.display","none");
	}
	$objResponse->enviar();
}

function cargarPresupuestoUnidad($vehiculo, $anio){
	$objResponse = new raResponse();
	if($vehiculo!="no_select"){
		$query = sprintf("select * from vw_unidades_retiro where idVehiculo = %d and anio = %d", $vehiculo, $anio);
		$rs = mysql_query($query);
		$row = mysql_fetch_object($rs);
		if($row){
			//$objResponse->alert('se trajo algo');
			/*$objResponse->asignar("txt_presu_obj_ene","value",$row->p_enero);
			$objResponse->asignar("txt_presu_obj_feb","value",$row->p_febrero);
			$objResponse->asignar("txt_presu_obj_mar","value",$row->p_marzo);
			$objResponse->asignar("txt_presu_obj_abr","value",$row->p_abril);
			$objResponse->asignar("txt_presu_obj_may","value",$row->p_mayo);
			$objResponse->asignar("txt_presu_obj_jun","value",$row->p_junio);
			$objResponse->asignar("txt_presu_obj_jul","value",$row->p_julio);
			$objResponse->asignar("txt_presu_obj_ago","value",$row->p_agosto);
			$objResponse->asignar("txt_presu_obj_sep","value",$row->p_septiembre);
			$objResponse->asignar("txt_presu_obj_oct","value",$row->p_octubre);
			$objResponse->asignar("txt_presu_obj_nov","value",$row->p_noviembre);
			$objResponse->asignar("txt_presu_obj_dic","value",$row->p_diciembre);*/
			$objResponse->asignar("hdn_cod_uni","value",$row->id);
			$query2 = mysql_query("select * from an_objetivos_unidades where idUR =".$row->id);
			$row2 = mysql_fetch_object($query2);
			if($row2){
				$objResponse->asignar("div_no_asignado","style.display","none");
				$objResponse->asignar("div_info","style.display","none");
				$objResponse->asignar("div_lista_vendedores","style.display","");
				$objResponse->asignar("div_obj","style.display","");
			}else{
				$objResponse->asignar("div_info","style.display","none");
				$objResponse->asignar("div_no_asignado","style.display","");
				$objResponse->asignar("div_obj","style.display","none");
			}
		}else{
			$objResponse->asignar("div_no_data2","style.display","");
			$objResponse->asignar("div_obj","style.display","none");
		}
	}else{
		$objResponse->asignar("div_obj","style.display","none");
	}
	//$objResponse->asignar("div_tabla_ou","style.display","");
	$objResponse->enviar();
}

function cargarFacturaVendedor($vendedor, $idUR){
	$objResponse = new raResponse();
	//$objResponse->alert("hola:".$vendedor." ".$idUR);
	
	$query = sprintf("select * from vw_objetivos_unidades where vendedor = '%s' and idUR = %d", $vendedor, $idUR);
	$rs = mysql_query($query);
	$row = mysql_fetch_object($rs);
	if($row){
		$objResponse->asignar("txt_fact_obj_ene","value",$row->enero);
		$objResponse->asignar("txt_fact_obj_feb","value",$row->febrero);
		$objResponse->asignar("txt_fact_obj_mar","value",$row->marzo);
		$objResponse->asignar("txt_fact_obj_abr","value",$row->abril);
		$objResponse->asignar("txt_fact_obj_may","value",$row->mayo);
		$objResponse->asignar("txt_fact_obj_jun","value",$row->junio);
		$objResponse->asignar("txt_fact_obj_jul","value",$row->julio);
		$objResponse->asignar("txt_fact_obj_ago","value",$row->agosto);
		$objResponse->asignar("txt_fact_obj_sep","value",$row->septiembre);
		$objResponse->asignar("txt_fact_obj_oct","value",$row->octubre);
		$objResponse->asignar("txt_fact_obj_nov","value",$row->noviembre);
		$objResponse->asignar("txt_fact_obj_dic","value",$row->diciembre);
		$objResponse->asignar("hdn_cod_obj","value",$row->id);
		$objResponse->asignar("hdn_cod_fac","value",$row->factura);
	}
	$objResponse->enviar();
}

function guardarOU($form){
	$objResponse = new raResponse();
	//$objResponse->alert($form["hdn_cod_uni"]);
	if($form["hdn_cod_obj"]){
		//$objResponse->alert("editar");
		$query=sprintf("update an_rollout_autos set enero = %d, febrero = %d, marzo = %d, abril = %d, mayo = %d, junio = %d, julio = %d, agosto = %d, septiembre = %d, octubre = %d, noviembre = %d, diciembre = %d where id=%d",
		$form['txt_fact_obj'][0],
		$form['txt_fact_obj'][1],
		$form['txt_fact_obj'][2],
		$form['txt_fact_obj'][3],
		$form['txt_fact_obj'][4],
		$form['txt_fact_obj'][5],
		$form['txt_fact_obj'][6],
		$form['txt_fact_obj'][7],
		$form['txt_fact_obj'][8],
		$form['txt_fact_obj'][9],
		$form['txt_fact_obj'][10],
		$form['txt_fact_obj'][11],
		$form['hdn_cod_fac']);
		$rs = mysql_query($query);
	}else{
		$objResponse->alert("crear");
		$query=sprintf("insert into an_rollout_autos (enero, febrero, marzo, abril, mayo, junio, julio, agosto, septiembre, octubre, noviembre, diciembre) values (%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d)",
		$form['txt_fact_obj'][0],
		$form['txt_fact_obj'][1],
		$form['txt_fact_obj'][2],
		$form['txt_fact_obj'][3],
		$form['txt_fact_obj'][4],
		$form['txt_fact_obj'][5],
		$form['txt_fact_obj'][6],
		$form['txt_fact_obj'][7],
		$form['txt_fact_obj'][8],
		$form['txt_fact_obj'][9],
		$form['txt_fact_obj'][10],
		$form['txt_fact_obj'][11]);
		$rs = mysql_query($query);
		$factura_id = mysql_insert_id();
		if($form["lst_vendedores"] == "no_select"){
			$vendedor = $form["txt_vendedor"];
		}else{
			$vendedor = $form["lst_vendedores"];
		}
		$query= sprintf("INSERT INTO an_objetivos_unidades (factura, vendedor, idUR) values (%d, '%s', %d)",
		$factura_id,
		$vendedor,
		$form["hdn_cod_uni"]);
		$rs = mysql_query($query);
	}
	$objResponse->enviar();
}

/*function editarUE($id, $form){

}*/


/******************************************************/
receptor_raRequest();
?>