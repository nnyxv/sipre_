<?php 
function enviar($id,$acc){
	$objResponse = new xajaxResponse();	
	
	$sqlActualiza = sprintf("UPDATE te_cheques SET impresion = '1' WHERE id_cheque = '%s'",$id);
	$reActualiza = mysql_query($sqlActualiza) or die(mysql_error());
	
	$sqlChequera = sprintf("SELECT te_cheques.id_cheque, te_cheques.id_chequera, te_chequeras.id_chq, te_chequeras.impresos FROM te_cheques INNER JOIN te_chequeras ON ( te_cheques.id_chequera = te_chequeras.id_chq ) WHERE te_cheques.id_cheque = '%s'",$id);
	$rsChequera = mysql_query($sqlChequera) or die(mysql_error());
	$rowChequera = mysql_fetch_assoc($rsChequera);
	
	$sqlActChequera = sprintf("UPDATE te_chequeras SET impresos = '%s' WHERE id_chq = '%s'",$rowChequera['impresos']+1,$rowChequera['id_chequera']);
	$rsActChequera = mysql_query($sqlActChequera) or die(mysql_error());
	
	$objResponse ->script("window.open('te_generar_cheque.php?acc=".$acc."','_self');");

	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"enviar");

?>