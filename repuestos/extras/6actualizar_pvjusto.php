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

// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
//$Result1 = actualizarMovimientoTotal();
//if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }

/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("id_articulo = 589");*/

// BUSCA LOS DATOS DEL CIERRE MENSUAL
$query = sprintf("SELECT *,
	(art_emp.cantidad_compra + art_emp.cantidad_entrada - art_emp.cantidad_venta - art_emp.cantidad_salida) AS cantidad_existencia
FROM iv_articulos_empresa art_emp %s", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$idArticulo = $row['id_articulo'];
	$idEmpresa = $row['id_empresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		errorInsertarArticulo($objResponse); return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	echo $contFila.") Id Articulo: ".$idArticulo.", Id Empresa: ".$idEmpresa.", Existencia: ".($row['cantidad_existencia'])."<br>";
	
	// BUSCA EL ULTIMO COSTO
	$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
	WHERE art_costo.id_articulo = %s
	ORDER BY fecha_registro DESC;",
		valTpDato($idArticulo, "int"));
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
	
	if (in_array($ResultConfig12, array(1,2))) {
		$costoUnitario = (in_array($ResultConfig12, array(1))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
	} else {
		$costoUnitario = round($rowArtCosto['costo'],3);
	}
	
	echo "&nbsp;&nbsp;&nbsp;&nbsp;Id Articulo Costo: ".$rowArtCosto['id_articulo_costo'].", Id Empresa: ".$rowArtCosto['id_empresa'].", Costo: ".$rowArtCosto['costo'].", Costo Promedio: ".($rowArtCosto['costo_promedio'])."<br>";
	
	if ($rowArtCosto['id_articulo_costo'] > 0) {
		// INSERTA EL LOTE DE ARRANQUE
		$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, cantidad_inicio, fecha_registro, estatus)
		SELECT %s, id_proveedor, id_articulo, %s, %s, %s, id_moneda, %s, %s, 1 FROM iv_articulos_costos
		WHERE id_articulo_costo = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato("NOW()", "campo"),
			valTpDato($costoUnitario, "real_inglesa"),
			valTpDato($costoUnitario, "real_inglesa"),
			valTpDato($row['cantidad_existencia'], "real_inglesa"),
			valTpDato("NOW()", "campo"),
			valTpDato($rowArtCosto['id_articulo_costo'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idArticuloCosto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$insertSQL."<br>";
	}
	
	if ($idArticuloCosto > 0) {
		// INSERTA LA RELACION DEL LOTE Y SU UBICACION
		$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, cantidad_inicio, estatus)
		SELECT art_almacen.id_articulo_almacen, %s, (art_almacen.cantidad_inicio + art_almacen.cantidad_entrada - art_almacen.cantidad_salida), 1
		FROM iv_articulos_almacen art_almacen
			INNER JOIN iv_casillas casilla ON (art_almacen.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
			INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
			INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
			INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
		WHERE art_almacen.id_articulo = %s
			AND almacen.id_empresa = %s;",
			valTpDato($idArticuloCosto, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$insertSQL."<br>";
	}
	
	/*if ($rowArtCosto['id_empresa'] > 0) {
		$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, cantidad_inicio, fecha_registro, estatus)
		SELECT %s, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, %s, fecha_registro, 1 FROM iv_articulos_costos
		WHERE id_articulo_costo = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($row['cantidad_existencia'], "real_inglesa"),
			valTpDato($rowArtCosto['id_articulo_costo'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$insertSQL."<br>";
	} else {
		$updateSQL = sprintf("UPDATE iv_articulos_costos SET
			id_empresa = %s,
			cantidad_inicio = %s,
			estatus = 1
		WHERE id_articulo_costo = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($row['cantidad_existencia'], "real_inglesa"),
			valTpDato($rowArtCosto['id_articulo_costo'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$updateSQL."<br>";
	}*/
	
	// ACTUALIZA EL PRECIO DE VENTA
	$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }
	
	$Result1 = actualizarSaldos($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }
	
	echo "<br><br><br>";
}

// ACTUALIZA EL LOTE DE LOS PRESUPUESTOS PENDIENTES
$updateSQL = sprintf("UPDATE iv_presupuesto_venta_detalle pres_vent_det SET
	pres_vent_det.id_articulo_costo = (SELECT art_almacen_costo.id_articulo_costo
									FROM iv_articulos_almacen art_almacen
										INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
									WHERE art_almacen.id_articulo = pres_vent_det.id_articulo
									ORDER BY art_almacen_costo.id_articulo_costo DESC LIMIT 1)
WHERE pres_vent_det.id_presupuesto_venta IN (SELECT id_presupuesto_venta FROM iv_presupuesto_venta
										WHERE estatus_presupuesto_venta IN (0))
	AND pres_vent_det.id_articulo_costo IS NULL;");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

// ACTUALIZA LA UBICACION DEL Y EL LOTE DE LOS PEDIDOS PENDIENTES
$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle ped_vent_det SET
	ped_vent_det.id_articulo_almacen_costo = (SELECT art_almacen_costo.id_articulo_almacen_costo
											FROM iv_articulos_almacen art_almacen
												INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
											WHERE art_almacen.id_articulo = ped_vent_det.id_articulo
												AND art_almacen.id_casilla = ped_vent_det.id_casilla),
	ped_vent_det.id_articulo_costo = (SELECT art_almacen_costo.id_articulo_costo
									FROM iv_articulos_almacen art_almacen
										INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
									WHERE art_almacen.id_articulo = ped_vent_det.id_articulo
										AND art_almacen.id_casilla = ped_vent_det.id_casilla)
WHERE ped_vent_det.id_pedido_venta IN (SELECT id_pedido_venta FROM iv_pedido_venta
										WHERE estatus_pedido_venta IN (0,1,2))
	AND (ped_vent_det.id_articulo_almacen_costo IS NULL OR ped_vent_det.id_articulo_costo IS NULL);");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$updateSQL."<br>";

// ACTUALIZA LA UBICACION DEL Y EL LOTE DE LAS ORDENES PENDIENTES
$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos det_sol_rep, sa_det_orden_articulo det_orden_art SET
	det_orden_art.id_articulo_almacen_costo = (SELECT art_almacen_costo.id_articulo_almacen_costo
											FROM iv_articulos_almacen art_almacen
												INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
											WHERE art_almacen.id_articulo = det_orden_art.id_articulo
												AND art_almacen.id_casilla = det_sol_rep.id_casilla),
	det_orden_art.id_articulo_costo = (SELECT art_almacen_costo.id_articulo_costo
									FROM iv_articulos_almacen art_almacen
										INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
									WHERE art_almacen.id_articulo = det_orden_art.id_articulo
										AND art_almacen.id_casilla = det_sol_rep.id_casilla)
WHERE det_sol_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo
	AND det_sol_rep.id_estado_solicitud NOT IN (6)
	AND det_orden_art.id_orden IN (SELECT id_orden FROM sa_orden
									WHERE id_estado_orden NOT IN (18,24))
	AND (det_orden_art.id_articulo_almacen_costo IS NULL OR det_orden_art.id_articulo_costo IS NULL);");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$updateSQL."<br>";

$Result1 = actualizarSaldos();
if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }

mysql_query("COMMIT;");

echo "<h1>PVJUSTO GENERADOS CON EXITO</h1>";
?>