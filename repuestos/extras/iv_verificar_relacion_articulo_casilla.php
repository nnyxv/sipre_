<?php
// PROGRAMA QUE MEDIANTE EL KARDEX VERIFICA SI TIENE LA UBICACION AGREGADO EN SU HISTORIAL AUNQUE ESTE INACTIVA

set_time_limit(0);
ini_set('memory_limit', '-1');
$raiz = "../";
require_once("../../connections/conex.php");

session_start();

require ('../../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../../controladores/xajax/');

include("../../controladores/ac_iv_general.php");

$xajax->processRequest();

$xajax->printJavascript('../../controladores/xajax/');

mysql_query("START TRANSACTION;");

$query = sprintf("SELECT * FROM iv_kardex;");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	$idArticulo = $row['id_articulo'];
	$idCasilla = $row['id_casilla'];
	
	$query2 = sprintf("SELECT * FROM iv_articulos_almacen
	WHERE id_articulo = %s
		AND id_casilla = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idCasilla, "int"));
	$rs2 = mysql_query($query2);
	if (!$rs2) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs2);
	$row2 = mysql_fetch_assoc($rs2);
	
	echo ($totalRows > 0) ? "" : "No Existe idArticulo: ".$idArticulo.", idCasilla: ".$idCasilla."<br>";
	
}

mysql_query("COMMIT;");
	
echo "<h1>FINALIZADO</h1>";
?>