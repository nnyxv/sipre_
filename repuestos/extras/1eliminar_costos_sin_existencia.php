<?php
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

$query = sprintf("SELECT * FROM iv_articulos_empresa;");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	$query2 = sprintf("SELECT
		kardex.id_kardex,
		IF(kardex.estado = 0, kardex.cantidad, (-1) * kardex.cantidad) AS cantidad,
		CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) AS fecha_movimiento
	FROM iv_articulos_empresa art_emp
		INNER JOIN iv_kardex kardex ON (art_emp.id_articulo = kardex.id_articulo)
	WHERE art_emp.id_articulo = %s
	ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) ASC",
		valTpDato($row['id_articulo'], "int"));
	$rs2 = mysql_query($query2);
	if (!$rs2) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$sumCantidad = 0;
	$arrayFechaCorte = "";
	while ($row2 = mysql_fetch_array($rs2)) {
		$sumCantidad += $row2['cantidad'];
		
		$arrayFechaCorte = ($sumCantidad == 0) ? $row2['fecha_movimiento'] : "";
	}
	
	if (strlen($arrayFechaCorte) > 0) {
		$deleteSQL = sprintf("DELETE FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = %s
			AND art_costo.id_empresa = %s
			AND art_costo.fecha_registro > %s;",
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_empresa'], "int"),
			valTpDato($arrayFechaCorte, "date"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
}

mysql_query("COMMIT;");

echo (utf8_encode("Costo sobrantes eliminados con Éxito"));
?>