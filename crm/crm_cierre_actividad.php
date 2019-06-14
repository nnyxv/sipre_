<?php 
	require_once("../connections/conex.php");

	$query = "UPDATE crm_actividades_ejecucion SET
					estatus = 3,
					tipo_finalizacion = 0
				WHERE
					DATE_FORMAT(fecha_asignacion,'%d-%m-%Y') = DATE_FORMAT(NOW(),'%d-%m-%Y');";
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
?>