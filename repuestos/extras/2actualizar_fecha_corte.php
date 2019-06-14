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

$query = sprintf("SELECT * FROM iv_articulos_empresa art_emp WHERE id_empresa NOT IN (2,3,4)");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while ($row = mysql_fetch_array($rs)) {
	$idArticulo = $row['id_articulo'];
	
	// BUSCA EL HISTORIAL DEL KARDEX PARA PODER IDENTIFICAR LA ULTIMA VEZ EN QUE EL ARTICULO QUEDO EN CERO
	$queryKardex = sprintf("SELECT
		kardex.id_kardex,
		art_emp.id_empresa,
		art_emp.id_articulo,
		IF(kardex.estado = 0, kardex.cantidad, (-1) * kardex.cantidad) AS cantidad,
		CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) AS fecha_movimiento
	FROM iv_articulos_empresa art_emp
		INNER JOIN iv_kardex kardex ON (art_emp.id_articulo = kardex.id_articulo)
	WHERE art_emp.id_articulo = %s
		AND (art_emp.id_empresa = %s
			OR art_emp.id_empresa IN (SELECT suc.id_empresa FROM pg_empresa suc
										WHERE suc.id_empresa_padre = %s))
	ORDER BY art_emp.id_articulo ASC, CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) ASC, kardex.id_kardex ASC",
		valTpDato($idArticulo, "int"),
		valTpDato($row['id_empresa'], "int"),
		valTpDato($row['id_empresa'], "int"));
	$rsKardex = mysql_query($queryKardex);
	if (!$rsKardex) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsKardex = mysql_num_rows($rsKardex);
	$sumCantidad = 0;
	$arrayFechaCorte = NULL;
	while ($rowKardex = mysql_fetch_array($rsKardex)) {
		$sumCantidad += $rowKardex['cantidad'];
		if ($sumCantidad == 0) {
			$arrayFechaCorte[] = $rowKardex['id_kardex']."*".$rowKardex['fecha_movimiento']."*1";
		}
	}
	
	if (count($arrayFechaCorte) == 1) {
		$kardexCorte = explode("*", $arrayFechaCorte[0]);
	} else if (count($arrayFechaCorte) > 1) {
		$arrayFechaCorte = array_reverse($arrayFechaCorte);
		$kardexCorte = explode("*", $arrayFechaCorte[0]);
	} else {
		// BUSCA EL COSTO DE LA PRIMERA COMPRA O ENTRADA
		$queryMov = sprintf("SELECT
			kardex.id_kardex,
			kardex.fecha_movimiento,
			(mov_det.costo - mov_det.subtotal_descuento) AS costo
		FROM iv_movimiento_detalle mov_det
			INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
		WHERE mov_det.id_articulo = %s
			AND ((SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla) = %s
				OR (SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla) IN (SELECT suc.id_empresa FROM pg_empresa suc
																	WHERE suc.id_empresa_padre = %s))
			AND kardex.tipo_movimiento IN (1,2)
		ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) ASC;",
			valTpDato($idArticulo, "int"),
			valTpDato($row['id_empresa'], "int"),
			valTpDato($row['id_empresa'], "int"));
		$rsMov = mysql_query($queryMov);
		if (!$rsMov) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMov = mysql_fetch_assoc($rsMov);
		
		$kardexCorte[0] = $rowMov['id_kardex'];
		$kardexCorte[1] = $rowMov['fecha_movimiento'];
		$kardexCorte[2] = 2;
	}
	
	$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
		id_kardex_corte = %s,
		fecha_kardex_corte = %s,
		id_tipo_corte = %s
	WHERE id_articulo_empresa = %s;",
		valTpDato($kardexCorte[0], "int"),
		valTpDato($kardexCorte[1], "date"),
		valTpDato($kardexCorte[2], "int"), // 1 = Saldo en Cero, 2 = Unica Compra
		valTpDato($row['id_articulo_empresa'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
}

mysql_query("COMMIT;");

echo (utf8_encode("Fechas de Corte Guardado con Éxito"));

echo (utf8_encode("Los Precios Han Sido Actualizados Con Éxito"));
?>