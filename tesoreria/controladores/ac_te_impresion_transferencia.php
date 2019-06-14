<?php 
function enviar($id,$acc){
	$objResponse = new xajaxResponse();	

	$sqlActualiza = sprintf("UPDATE te_transferencia SET impreso = '1' WHERE id_transferencia = '%s'",$id);
	$reActualiza = mysql_query($sqlActualiza) or die(mysql_error());
	
	$objResponse ->script("window.open('te_transferencia.php?acc=".$acc."','_self');");

	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"enviar");

?>