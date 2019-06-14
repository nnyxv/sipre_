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

echo "<h2>KARDEX QUE NO ESTAN EN DETALLE DE MOVIMIENTO</h2>";

// BUSCA LOS REGISTROS DE KARDEX QUE NO ESTEN EN EL DETALLE DE MOVIMIENTO
$query = sprintf("SELECT * FROM iv_kardex
WHERE (SELECT COUNT(*) FROM iv_movimiento_detalle
		WHERE iv_movimiento_detalle.id_kardex = iv_kardex.id_kardex) = 0;");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while($row = mysql_fetch_assoc($rs)) {
	
	if ($row['id_articulo_almacen_costo'] == "" && $row['id_articulo_costo'] == "" && $row['tipo_documento_movimiento'] != "") {
		$queryMovimientoDetalle = sprintf("SELECT *
		FROM iv_movimiento mov
			INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
		WHERE mov.id_documento = %s
			AND mov_det.id_articulo = %s
			AND mov_det.id_articulo_almacen_costo IS NULL
			AND mov_det.id_articulo_costo IS NULL
			AND mov.id_tipo_movimiento = %s
			AND mov.id_clave_movimiento = %s
			AND mov.tipo_documento_movimiento = %s
			AND mov_det.cantidad = %s
			AND mov_det.precio = %s
			AND mov_det.costo = %s
			AND mov_det.costo_cargo = %s
			AND mov_det.costo_diferencia = %s
			AND mov_det.porcentaje_descuento = %s
			AND mov_det.subtotal_descuento = %s
			AND DATE(mov.fecha_movimiento) = DATE(%s)
			AND mov_det.id_kardex IS NULL;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['tipo_movimiento'], "int"),
			valTpDato($row['id_clave_movimiento'], "int"),
			valTpDato($row['tipo_documento_movimiento'], "int"),
			valTpDato((($row['cantidad'] > 0) ? $row['cantidad'] : 0), "campo"),
			valTpDato((($row['precio'] > 0) ? $row['precio'] : 0), "campo"),
			valTpDato((($row['costo'] > 0) ? $row['costo'] : 0), "campo"),
			valTpDato((($row['costo_cargo'] > 0) ? $row['costo_cargo'] : 0), "campo"),
			valTpDato((($row['costo_diferencia'] > 0) ? $row['costo_diferencia'] : 0), "campo"),
			valTpDato((($row['porcentaje_descuento'] > 0) ? $row['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row['subtotal_descuento'] > 0) ? $row['subtotal_descuento'] : 0), "campo"),
			valTpDato($row['fecha_movimiento'], "date"));
	} else if ($row['id_articulo_almacen_costo'] == "" && $row['id_articulo_costo'] == "" && $row['tipo_documento_movimiento'] == "") {
		$queryMovimientoDetalle = sprintf("SELECT *
		FROM iv_movimiento mov
			INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
		WHERE mov.id_documento = %s
			AND mov_det.id_articulo = %s
			AND mov_det.id_articulo_almacen_costo IS NULL
			AND mov_det.id_articulo_costo IS NULL
			AND mov.id_tipo_movimiento = %s
			AND mov.id_clave_movimiento = %s
			AND mov.tipo_documento_movimiento IS NULL
			AND mov_det.cantidad = %s
			AND mov_det.precio = %s
			AND mov_det.costo = %s
			AND mov_det.costo_cargo = %s
			AND mov_det.costo_diferencia = %s
			AND mov_det.porcentaje_descuento = %s
			AND mov_det.subtotal_descuento = %s
			AND DATE(mov.fecha_movimiento) = DATE(%s)
			AND mov_det.id_kardex IS NULL;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['tipo_movimiento'], "int"),
			valTpDato($row['id_clave_movimiento'], "int"),
			valTpDato((($row['cantidad'] > 0) ? $row['cantidad'] : 0), "campo"),
			valTpDato((($row['precio'] > 0) ? $row['precio'] : 0), "campo"),
			valTpDato((($row['costo'] > 0) ? $row['costo'] : 0), "campo"),
			valTpDato((($row['costo_cargo'] > 0) ? $row['costo_cargo'] : 0), "campo"),
			valTpDato((($row['costo_diferencia'] > 0) ? $row['costo_diferencia'] : 0), "campo"),
			valTpDato((($row['porcentaje_descuento'] > 0) ? $row['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row['subtotal_descuento'] > 0) ? $row['subtotal_descuento'] : 0), "campo"),
			valTpDato($row['fecha_movimiento'], "date"));
	} else if ($row['id_articulo_almacen_costo'] != "" && $row['id_articulo_costo'] != "" && $row['tipo_documento_movimiento'] != "") {
		$queryMovimientoDetalle = sprintf("SELECT *
		FROM iv_movimiento mov
			INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
		WHERE mov.id_documento = %s
			AND mov_det.id_articulo = %s
			AND (mov_det.id_articulo_almacen_costo = %s
				OR mov_det.id_articulo_almacen_costo IS NULL)
			AND mov_det.id_articulo_costo = %s
			AND mov.id_tipo_movimiento = %s
			AND mov.id_clave_movimiento = %s
			AND mov.tipo_documento_movimiento = %s
			AND mov_det.cantidad = %s
			AND mov_det.precio = %s
			AND mov_det.costo = %s
			AND mov_det.costo_cargo = %s
			AND mov_det.costo_diferencia = %s
			AND mov_det.porcentaje_descuento = %s
			AND mov_det.subtotal_descuento = %s
			AND DATE(mov.fecha_movimiento) = DATE(%s)
			AND mov_det.id_kardex IS NULL;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_articulo_almacen_costo'], "int"),
			valTpDato($row['id_articulo_costo'], "int"),
			valTpDato($row['tipo_movimiento'], "int"),
			valTpDato($row['id_clave_movimiento'], "int"),
			valTpDato($row['tipo_documento_movimiento'], "int"),
			valTpDato((($row['cantidad'] > 0) ? $row['cantidad'] : 0), "campo"),
			valTpDato((($row['precio'] > 0) ? $row['precio'] : 0), "campo"),
			valTpDato((($row['costo'] > 0) ? $row['costo'] : 0), "campo"),
			valTpDato((($row['costo_cargo'] > 0) ? $row['costo_cargo'] : 0), "campo"),
			valTpDato((($row['costo_diferencia'] > 0) ? $row['costo_diferencia'] : 0), "campo"),
			valTpDato((($row['porcentaje_descuento'] > 0) ? $row['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row['subtotal_descuento'] > 0) ? $row['subtotal_descuento'] : 0), "campo"),
			valTpDato($row['fecha_movimiento'], "date"));
	} else if ($row['id_articulo_almacen_costo'] != "" && $row['id_articulo_costo'] != "" && $row['tipo_documento_movimiento'] == "") {
		$queryMovimientoDetalle = sprintf("SELECT *
		FROM iv_movimiento mov
			INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
		WHERE mov.id_documento = %s
			AND mov_det.id_articulo = %s
			AND (mov_det.id_articulo_almacen_costo = %s
				OR mov_det.id_articulo_almacen_costo IS NULL)
			AND mov_det.id_articulo_costo = %s
			AND mov.id_tipo_movimiento = %s
			AND mov.id_clave_movimiento = %s
			AND mov.tipo_documento_movimiento IS NULL
			AND mov_det.cantidad = %s
			AND mov_det.precio = %s
			AND mov_det.costo = %s
			AND mov_det.costo_cargo = %s
			AND mov_det.costo_diferencia = %s
			AND mov_det.porcentaje_descuento = %s
			AND mov_det.subtotal_descuento = %s
			AND DATE(mov.fecha_movimiento) = DATE(%s)
			AND mov_det.id_kardex IS NULL;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_articulo_almacen_costo'], "int"),
			valTpDato($row['id_articulo_costo'], "int"),
			valTpDato($row['tipo_movimiento'], "int"),
			valTpDato($row['id_clave_movimiento'], "int"),
			valTpDato((($row['cantidad'] > 0) ? $row['cantidad'] : 0), "campo"),
			valTpDato((($row['precio'] > 0) ? $row['precio'] : 0), "campo"),
			valTpDato((($row['costo'] > 0) ? $row['costo'] : 0), "campo"),
			valTpDato((($row['costo_cargo'] > 0) ? $row['costo_cargo'] : 0), "campo"),
			valTpDato((($row['costo_diferencia'] > 0) ? $row['costo_diferencia'] : 0), "campo"),
			valTpDato((($row['porcentaje_descuento'] > 0) ? $row['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row['subtotal_descuento'] > 0) ? $row['subtotal_descuento'] : 0), "campo"),
			valTpDato($row['fecha_movimiento'], "date"));
	}
	
	if (strlen($queryMovimientoDetalle) > 0) {
		$rsMovimientoDetalle = mysql_query($queryMovimientoDetalle);
		if (!$rsMovimientoDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsMovimientoDetalle = mysql_num_rows($rsMovimientoDetalle);
		$rowMovimientoDetalle = mysql_fetch_assoc($rsMovimientoDetalle);
		
		echo "<pre>".($queryMovimientoDetalle)."<br>".__LINE__."</pre>";
		
		if ($row['id_kardex'] > 0 && $rowMovimientoDetalle['id_movimiento_detalle'] > 0) {
			$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
				id_kardex = %s
			WHERE id_movimiento_detalle = %s;",
				valTpDato($row['id_kardex'], "int"),
				valTpDato($rowMovimientoDetalle['id_movimiento_detalle'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			echo "<pre>".($updateSQL)."<br>".__LINE__."</pre>";
		}
		
		echo "<br><br>";
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