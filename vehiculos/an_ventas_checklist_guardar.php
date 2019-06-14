<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");
validaModulo("an_checklist",editar);
	
$redirect="nobody.htm";
conectar();
function check_value($val,$type){
	if($type==0){
		if($val!=""){
			return 1;
		}else{
			return 0;
		}
	}elseif($type==2){//date
		if($val!=""){
			return "STR_TO_DATE('".$val."','".spanDatePick."')";
		}else{
			return 'NULL';
		}
	}elseif($type==3){//datetime
		if($val!=""){
			return "STR_TO_DATE('".$val."','".spanDatePick." %h:%i %p')";
		}else{
			return 'NULL';
		}
	}elseif($type==4){//text
		if($val!=""){
			return "'".$val."'";
		}else{
			return "NULL";
		}
	}
}
//var_dump($_POST);
if(isset($_POST['checkid'])){
	$checkid=$_POST['checkid'];
	$checktype=$_POST['typeid'];
	$checkvalue=$_POST['check'];
	$id_pedido=$_POST['id_pedido'];
	iniciotransaccion();
	inputmysqlutf8();
	foreach ($checkid as $key=>$value){
		$valor=check_value($checkvalue[$key],$checktype[$key]);
		if($value==''){
			//genero un insert
			$sqldet="insert into an_pedido_checklist (id_pedido,id_dato_lista,valor,id_pedido_checklist) values 
			(%s,%s,%s,default);";
			$sqldet=sprintf($sqldet,
				$id_pedido,
				$key,
				$valor
			);
		} else {
			//genero un update
			$sqldet="update an_pedido_checklist set valor=%s where id_pedido_checklist=%s;";$sqldet=sprintf($sqldet,
				$valor,
				$value
			);
		}
		//echo $sqldet.'<br />';
		$result = mysql_query($sqldet,$conex);
		if(!$result) {
			echo 'error '.mysql_error($conex).' sql:'.$sqldet.'<br />';
			rollback();
			break;
		}
	}
	$sqlup = "update an_pedido set check_fecha_cita=CURRENT_DATE() where id_pedido=".$id_pedido.";";
	$resultp = mysql_query($sqlup,$conex);
	if(!$resultp) {
		rollback();
		echo 'error '.mysql_error($conex).' sql:'.$sqlup.'<br />';
	}
	fintransaccion();
	echo '<script language="javascript" type="text/javascript">
	alert("Se ha guardado el checklist correctamente");
	window.location.href="reportes/an_ventas_cartas_checklist.php?id='.$id_pedido.'&view=1";
	</script>';
}
?>
