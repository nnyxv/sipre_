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

include("../../controladores/ac_if_generar_cierre_mensual.php");
include("../../controladores/ac_iv_general.php"); 

$xajax->processRequest();

$xajax->printJavascript('../../controladores/xajax/');

mysql_query("START TRANSACTION;");

echo "<h2>ID KARDEX REPETIDOS EN DETALLE DE MOVIMIENTO</h2>";

// BUSCA LOS ID KARDEX REPETIDOS EN EL DETALLE DEL MOVIMIENTO
$query = sprintf("SELECT
	mov_det.id_kardex,
	COUNT(mov_det.id_kardex) AS veces_repetida
FROM iv_movimiento mov
	INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
GROUP BY id_kardex 
HAVING COUNT(id_kardex) > 1;");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while($row = mysql_fetch_assoc($rs)) {
	$arrayIdMovimiento = array(-1);
	
	$query2 = sprintf("SELECT
		mov_det.id_kardex,
		mov.id_documento,
		mov_det.id_articulo,
		mov.id_tipo_movimiento,
		mov.id_clave_movimiento,
		mov_det.cantidad,
		mov_det.precio,
		mov_det.costo,
		mov_det.costo_cargo,
		mov_det.costo_diferencia,
		mov_det.porcentaje_descuento,
		mov_det.subtotal_descuento,
		DATE(mov.fecha_movimiento) AS fecha_movimiento
	FROM iv_movimiento mov
		INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
	WHERE id_kardex = %s;",
		valTpDato($row['id_kardex'], "int"));
	$rs2 = mysql_query($query2);
	if (!$rs2) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$query2);
	$totalRows2 = mysql_num_rows($rs2);
	while($row2 = mysql_fetch_assoc($rs2)) {
		// BUSCA EL KARDEX QUE TENGA LAS COINCIDENCIAS Y NO ESTE EN EL DETALLE DE MOVIMIENTO
		$queryKardex = sprintf("SELECT * FROM iv_kardex
		WHERE id_documento = %s
			AND id_articulo = %s
			AND tipo_movimiento = %s
			AND id_clave_movimiento = %s
			AND cantidad = %s
			AND precio = %s
			AND costo = %s
			AND costo_cargo = %s
			AND porcentaje_descuento = %s
			AND subtotal_descuento = %s
			AND (SELECT COUNT(*) FROM iv_movimiento_detalle
				WHERE iv_movimiento_detalle.id_kardex = iv_kardex.id_kardex) = 0
		ORDER BY iv_kardex.id_kardex ASC;",
			valTpDato($row2['id_documento'], "int"),
			valTpDato($row2['id_articulo'], "int"),
			valTpDato($row2['id_tipo_movimiento'], "int"),
			valTpDato($row2['id_clave_movimiento'], "int"),
			valTpDato((($row2['cantidad'] > 0) ? $row2['cantidad'] : 0), "campo"),
			valTpDato((($row2['precio'] > 0) ? $row2['precio'] : 0), "campo"),
			valTpDato((($row2['costo'] > 0) ? $row2['costo'] : 0), "campo"),
			valTpDato((($row2['costo_cargo'] > 0) ? $row2['costo_cargo'] : 0), "campo"),
			valTpDato((($row2['porcentaje_descuento'] > 0) ? $row2['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row2['subtotal_descuento'] > 0) ? $row2['subtotal_descuento'] : 0), "campo"));
		$rsKardex = mysql_query($queryKardex);
		if (!$rsKardex) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$queryKardex);
		$totalRowsKardex = mysql_num_rows($rsKardex);
		$rowKardex = mysql_fetch_assoc($rsKardex);
				
		echo "<pre>".($queryKardex)."<br>".__LINE__."</pre>";
		
		// BUSCA LOS MOVIMIENTOS QUE TIENE DICHO KARDEX REPETIDO
		$queryMovimientoDetalle = sprintf("SELECT mov_det.*
		FROM iv_movimiento mov
			INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
		WHERE mov_det.id_kardex = %s
			AND mov.id_documento = %s
			AND mov_det.id_articulo = %s
			AND mov.id_tipo_movimiento = %s
			AND mov.id_clave_movimiento = %s
			AND mov_det.cantidad = %s
			AND mov_det.precio = %s
			AND mov_det.costo = %s
			AND mov_det.costo_cargo = %s
			AND mov_det.costo_diferencia = %s
			AND mov_det.porcentaje_descuento = %s
			AND mov_det.subtotal_descuento = %s
			AND DATE(mov.fecha_movimiento) = %s
			AND mov_det.id_movimiento_detalle NOT IN (%s);",
			valTpDato($row2['id_kardex'], "int"),
			valTpDato($row2['id_documento'], "int"),
			valTpDato($row2['id_articulo'], "int"),
			valTpDato($row2['id_tipo_movimiento'], "int"),
			valTpDato($row2['id_clave_movimiento'], "int"),
			valTpDato((($row2['cantidad'] > 0) ? $row2['cantidad'] : 0), "campo"),
			valTpDato((($row2['precio'] > 0) ? $row2['precio'] : 0), "campo"),
			valTpDato((($row2['costo'] > 0) ? $row2['costo'] : 0), "campo"),
			valTpDato((($row2['costo_cargo'] > 0) ? $row2['costo_cargo'] : 0), "campo"),
			valTpDato((($row2['costo_diferencia'] > 0) ? $row2['costo_diferencia'] : 0), "campo"),
			valTpDato((($row2['porcentaje_descuento'] > 0) ? $row2['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row2['subtotal_descuento'] > 0) ? $row2['subtotal_descuento'] : 0), "campo"),
			valTpDato($row2['fecha_movimiento'], "date"),
			valTpDato(implode(",", $arrayIdMovimiento), "campo"));
		$rsMovimientoDetalle = mysql_query($queryMovimientoDetalle);
		if (!$rsMovimientoDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$queryMovimientoDetalle);
		$totalRowsMovimientoDetalle = mysql_num_rows($rsMovimientoDetalle);
		$rowMovimientoDetalle = mysql_fetch_assoc($rsMovimientoDetalle);
				
		echo "<pre>".($queryMovimientoDetalle)."<br>".__LINE__."</pre>";
		
		($totalRowsMovimientoDetalle > 0) ? $arrayIdMovimiento[] = $rowMovimientoDetalle['id_movimiento_detalle'] : "";
		
		if ($totalRowsKardex > 0 && $totalRowsMovimientoDetalle > 0) {
			$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
				id_kardex = %s
			WHERE id_movimiento_detalle = %s;",
				valTpDato($rowKardex['id_kardex'], "int"),
				valTpDato($rowMovimientoDetalle['id_movimiento_detalle'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			echo "<pre>".($updateSQL)."<br>".__LINE__."</pre>";
		}
	}
}

$Result1 = actualizarSaldos();
if ($Result1[0] != true && strlen($Result1[1]) > 0) { $arrayLoteInvalido[] = ($Result1[1]); }

$Result1 = actualizarMovimientoTotal();
if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }

mysql_query("COMMIT;");

echo "<h1>DETALLES GENERADOS CON EXITO</h1>";

echo "<pre>".((isset($arrayLoteInvalido)) ? implode(", ", $arrayLoteInvalido) : "")."</pre>";
?>