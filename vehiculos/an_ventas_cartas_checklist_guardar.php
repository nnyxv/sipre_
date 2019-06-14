<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");
validaModulo("an_checklist",editar);

$redirect = "nobody.htm";
$id_pedido = $_POST['id_pedido'];
$c = $_POST['c'];
$cv = $_POST['cv'];
conectar();
iniciotransaccion();

foreach($cv as $i => $value){//$i=1;$i<=count($cv);$i++
	if (($cv[$i]=="") && ($c[$i]!="")){
		$sql="insert into an_pedido_checklist(id_pedido_checklist,id_pedido,id_dato_lista,valor) values (default,".$id_pedido.",".$i.",".$c[$i].");";
	}elseif($cv[$i]!=""){
		$sql="update an_pedido_checklist set valor=".$c[$i]." where id_pedido_checklist=".$cv[$i].";";
	}else{
		$sql="";
	}
	if($sql!=""){
		$r=mysql_query($sql,$conex);
		if(!$r){
			echo "ERROR: ".mysql_error();
			rollback();
		}
	}
}

//guarda la fecha de cita:

$check_fecha_cita = getempty($_POST['check_fecha_cita'],'');
if($check_fecha_cita!=""){
	$sql2 = sprintf("update an_pedido set check_fecha_cita=%s where id_pedido=%s;",setmysqlfecha($check_fecha_cita),$id_pedido);
	$r2=mysql_query($sql2,$conex);
	if(!$r2){
		echo "ERROR: ".mysql_error();
		rollback();
	}
}
fintransaccion();
echo '<script language="javascript" type="text/javascript">
alert("Se almaceno con éxito.");
window.location.href="reportes/an_ventas_cartas_checklist.php?view=1&id='.$id_pedido.'";
</script>';
?>