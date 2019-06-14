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

/*$query = sprintf("SELECT * FROM iv_articulos_empresa");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	// BUSCA EL PRIMER COSTO QUE NO ESTE NULL
	$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
	WHERE art_costo.id_articulo = %s
		AND art_costo.id_empresa IS NULL 
	ORDER BY art_costo.id_articulo_costo DESC LIMIT 1;",
		valTpDato($row['id_articulo'], "int"));
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
	
	// ACTUALIZA EL ID DE LA EMPRESA
	$updateSQL = sprintf("UPDATE iv_articulos_costos SET
		id_empresa = %s
	WHERE id_articulo_costo = %s;",
		valTpDato($row['id_empresa'], "int"),
		valTpDato($rowArtCosto['id_articulo_costo'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
}


$query = sprintf("SELECT * FROM iv_articulos_empresa");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	// BUSCA EL PRIMER COSTO QUE NO ESTE NULL
	$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
	WHERE art_costo.id_articulo = %s
		AND art_costo.id_empresa = %s
	ORDER BY art_costo.id_articulo_costo DESC LIMIT 1;",
		valTpDato($row['id_articulo'], "int"),
		valTpDato($row['id_empresa'], "int"));
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
	
	if ($totalRowsArtCosto == 0) {
		$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, fecha_registro)
		SELECT %s, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, fecha_registro FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = %s
		ORDER BY art_costo.id_articulo_costo DESC LIMIT 1;",
			valTpDato($row['id_empresa'], "int"),
			valTpDato($row['id_articulo'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		echo "<pre>".$insertSQL."</pre>";
	}
}*/

$query = sprintf("SELECT * FROM iv_articulos_empresa WHERE id_empresa IN (1,2,3,4)");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	$idArticulo = $row['id_articulo'];
	$idEmpresa = $row['id_empresa'];
	
	////////////////////////////////////////////////////////////////////////////////////////////////////
	// INSERTA EL ULTIMO COSTO DEL ARTICULO DEL LA EMPRESA PRINCIPAL SI ESTA NO TIENE NINGUNO
	$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, fecha_registro)
	SELECT %s, art_costo.id_proveedor, art_costo.id_articulo, art_costo.fecha, art_costo.costo, art_costo.costo_promedio, art_costo.id_moneda, art_costo.fecha_registro
	FROM iv_articulos_costos art_costo
		INNER JOIN iv_articulos art ON (art_costo.id_articulo = art.id_articulo)
	WHERE art_costo.id_articulo = %s
		AND art_costo.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s)
		AND art_costo.id_articulo NOT IN (SELECT art_costo2.id_articulo FROM iv_articulos_costos art_costo2
											WHERE art_costo2.id_empresa IN (%s))
		AND art_costo.id_articulo_costo = (SELECT MAX(art_costo3.id_articulo_costo) FROM iv_articulos_costos art_costo3
											WHERE art_costo3.id_articulo = art_costo.id_articulo
												AND art_costo3.id_empresa = art_costo.id_empresa)
	ORDER BY art_costo.id_articulo ASC",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	mysql_query("SET NAMES 'latin1';");
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	echo "<pre>".$insertSQL."</pre>";
}

$query = sprintf("SELECT * FROM iv_articulos_empresa WHERE id_empresa IN (1,2,3,4)");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($row = mysql_fetch_assoc($rs)) {
	// ACTUALIZA TOTALES
	$Result1 = actualizarMovimientoTotal($row['id_articulo'], $row['id_empresa']);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }

	// ACTUALIZA EL COSTO PROMEDIO
	$Result1 = actualizarCostoPromedio($row['id_articulo'], $row['id_empresa']);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }
	
	// ACTUALIZA EL COSTO PROMEDIO
	$Result1 = actualizarPrecioVenta($row['id_articulo'], $row['id_empresa']);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }
}

mysql_query("COMMIT;");

echo (utf8_encode("Costo Guardado con Éxito"));

echo (utf8_encode("Los Precios Han Sido Actualizados Con Éxito"));
?>