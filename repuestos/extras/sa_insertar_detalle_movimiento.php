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


echo "<h2>MOVIMIENTO SIN DETALLE</h2>";

// BUSCA LOS MOVIMIENTOS QUE NO TIENEN DETALLE
$query = sprintf("SELECT * FROM iv_movimiento WHERE id_movimiento NOT IN (SELECT id_movimiento FROM iv_movimiento_detalle)");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while($row = mysql_fetch_assoc($rs)) {
	$idMovimiento = $row['id_movimiento'];
	$idTipoMovimiento = $row['id_tipo_movimiento'];
	$idClaveMovimiento = $row['id_clave_movimiento'];
	$fechaMovimiento = $row['fecha_captura'];
	
	if ($row['id_tipo_movimiento'] == 3) { // Venta
		$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s",
			valTpDato($row['id_documento'], "int"));
		$rsFact = mysql_query($queryFact);
		if (!$rsFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFact = mysql_num_rows($rsFact);
		$rowFact = mysql_fetch_assoc($rsFact);
		
		$idOrden = $rowFact['numeroPedido'];
		$idModulo = $rowFact['idDepartamentoOrigenFactura'];
		$idFactura = $rowFact['idFactura'];
		$txtDescuento = $rowFact['porcentaje_descuento'];
		$txtObservacion = $rowFact['observacionFactura'];
		
		$queryFactDet = sprintf("SELECT * FROM cj_cc_factura_detalle WHERE id_factura = %s",
			valTpDato($idFactura, "int"));
		$rsFactDet = mysql_query($queryFactDet);
		if (!$rsFactDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFactDet = mysql_num_rows($rsFactDet);
		while($rowFactDet = mysql_fetch_assoc($rsFactDet)) {
			$idArticulo = $rowFactDet['id_articulo'];
			$hddIdArticuloAlmacenCosto = $rowFactDet['id_articulo_almacen_costo'];
			$hddIdArticuloCosto = $rowFactDet['id_articulo_costo'];
			$cantDespachada = $rowFactDet['cantidad'];
			$precioUnitario = $rowFactDet['precio_unitario'];
			$costoUnitario = $rowFactDet['costo_compra'];
			
			if ($idModulo == 0) {
				// BUSCA LA CASILLA DEL DETALLE
				$queryOrdenDet = sprintf("SELECT * FROM iv_pedido_venta_detalle ped_vent_det
				WHERE ped_vent_det.id_pedido_venta = %s
					AND ped_vent_det.id_articulo = %s
					AND ped_vent_det.id_articulo_costo = %s;",
					valTpDato($idOrden, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($hddIdArticuloCosto, "int"));
				$rsOrdenDet = mysql_query($queryOrdenDet);
				if (!$rsOrdenDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsOrdenDet = mysql_num_rows($rsOrdenDet);
				$rowOrdenDet = mysql_fetch_assoc($rsOrdenDet);
				
				$idCasilla = $rowOrdenDet['id_casilla'];
			} else if ($idModulo == 1) {
				// BUSCA LA CASILLA DEL DETALLE
				$queryOrdenDet = sprintf("SELECT *
				FROM sa_det_orden_articulo det_orden_art
					INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
				WHERE det_orden_art.id_orden = %s
					AND det_orden_art.id_articulo = %s
					AND det_orden_art.id_articulo_costo = %s
					AND det_sol_rep.id_estado_solicitud = 5;",
					valTpDato($idOrden, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($hddIdArticuloCosto, "int"));
				$rsOrdenDet = mysql_query($queryOrdenDet);
				if (!$rsOrdenDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsOrdenDet = mysql_num_rows($rsOrdenDet);
				$rowOrdenDet = mysql_fetch_assoc($rsOrdenDet);
				
				$idCasilla = $rowOrdenDet['id_casilla'];
			}
			
			$txtObservacion = ($hddIdArticuloCosto > 0) ? $txtObservacion : "REGISTRADO AUTOMATICO SIPRE ".date("Y-m-d H:i:s");
			
			// REGISTRA EL MOVIMIENTO DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, TIME(%s));",
				valTpDato($idModulo, "int"),
				valTpDato($idFactura, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($cantDespachada, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato($txtDescuento, "real_inglesa"),
				valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
				valTpDato(1, "int"), // 0 = Entrada, 1 = Salida,
				valTpDato($fechaMovimiento, "date"),
				valTpDato($txtObservacion, "text"),
				valTpDato($fechaMovimiento, "date"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			echo "<pre>".($insertSQL)."</pre>";
			
			// INSERTA EL DETALLE DEL MOVIMIENTO
			$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idMovimiento, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idKardex, "int"),
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($cantDespachada, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato($txtDescuento, "real_inglesa"),
				valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
				valTpDato(0, "int"), // 0 = Unitario, 1 = Import
				valTpDato(0, "boolean"), // 0 = No, 1 = Si
				valTpDato("", "int"),
				valTpDato("", "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			echo "<pre>".($insertSQL)."</pre>";
			
			echo "<br><br>";
		}
	} else if ($row['id_tipo_movimiento'] == 4) { // Salida
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s",
			valTpDato($idClaveMovimiento, "int"));
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsClaveMov = mysql_num_rows($rsClaveMov);
		$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
		
		if ($rowClaveMov['id_modulo'] == 1) {
			$queryValeSal = sprintf("SELECT * FROM sa_vale_salida WHERE id_vale_salida = %s",
				valTpDato($row['id_documento'], "int"));
			$rsValeSal = mysql_query($queryValeSal);
			if (!$rsValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSal = mysql_num_rows($rsValeSal);
			$rowValeSal = mysql_fetch_assoc($rsValeSal);
			
			$idOrden = $rowValeSal['id_orden'];
			$idModulo = $rowClaveMov['id_modulo'];
			$idValeSalida = $rowValeSal['id_vale_salida'];
			$txtDescuento = $rowValeSal['descuento'];
			$txtObservacion = $rowValeSal['observacionFactura'];
		
			$queryValeSalDet = sprintf("SELECT * FROM sa_det_vale_salida_articulo WHERE id_vale_salida = %s",
				valTpDato($idValeSalida, "int"));
			$rsValeSalDet = mysql_query($queryValeSalDet);
			if (!$rsValeSalDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSalDet = mysql_num_rows($rsValeSalDet);
			while($rowValeSalDet = mysql_fetch_assoc($rsValeSalDet)) {
				$idArticulo = $rowValeSalDet['id_articulo'];
				$hddIdArticuloAlmacenCosto = $rowValeSalDet['id_articulo_almacen_costo'];
				$hddIdArticuloCosto = $rowValeSalDet['id_articulo_costo'];
				$cantDespachada = $rowValeSalDet['cantidad'];
				$precioUnitario = $rowValeSalDet['precio_unitario'];
				$costoUnitario = $rowValeSalDet['costo'];
				
				if ($idModulo == 1) {
					if ($hddIdArticuloCosto > 0) {
						// BUSCA LA CASILLA DEL DETALLE
						$queryOrdenDet = sprintf("SELECT *
						FROM sa_det_orden_articulo det_orden_art
							INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
						WHERE det_orden_art.id_orden = %s
							AND det_orden_art.id_articulo = %s
							AND det_orden_art.id_articulo_costo = %s
							AND det_sol_rep.id_estado_solicitud = 5;",
							valTpDato($idOrden, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($hddIdArticuloCosto, "int"));
					} else {
						// BUSCA LA CASILLA DEL DETALLE
						$queryOrdenDet = sprintf("SELECT *
						FROM sa_det_orden_articulo det_orden_art
							INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
						WHERE det_orden_art.id_orden = %s
							AND det_orden_art.id_articulo = %s
							AND det_orden_art.id_articulo_costo IS NULL
							AND det_sol_rep.id_estado_solicitud = 5;",
							valTpDato($idOrden, "int"),
							valTpDato($idArticulo, "int"));
					}
					$rsOrdenDet = mysql_query($queryOrdenDet);
					if (!$rsOrdenDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsOrdenDet = mysql_num_rows($rsOrdenDet);
					$rowOrdenDet = mysql_fetch_assoc($rsOrdenDet);
					
					$idCasilla = $rowOrdenDet['id_casilla'];
				}
				
				$txtObservacion = ($hddIdArticuloCosto > 0) ? $txtObservacion : "REGISTRADO AUTOMATICO SIPRE ".date("Y-m-d H:i:s");
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, TIME(%s));",
					valTpDato($idModulo, "int"),
					valTpDato($idValeSalida, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($idClaveMovimiento, "int"),
					valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Crédito
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($txtDescuento, "real_inglesa"),
					valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
					valTpDato(1, "int"), // 0 = Entrada, 1 = Salida,
					valTpDato($fechaMovimiento, "date"),
					valTpDato($txtObservacion, "text"),
					valTpDato($fechaMovimiento, "date"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				echo "<pre>".($insertSQL)."</pre>";
				
				// INSERTA EL DETALLE DEL MOVIMIENTO
				$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idMovimiento, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idKardex, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($txtDescuento, "real_inglesa"),
					valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
					valTpDato(0, "int"), // 0 = Unitario, 1 = Import
					valTpDato(0, "boolean"), // 0 = No, 1 = Si
					valTpDato("", "int"),
					valTpDato("", "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				echo "<pre>".($insertSQL)."</pre>";
				
				echo "<br><br>";
			}
		}
	}
}




/*echo "<h2>DETALLE DE MOVIMIENTO SIN ID KARDEX</h2>";

// BUSCA EL DETALLE DE LOS MOVIMIENTOS QUE NO TENGAN ID KARDEX
$query = sprintf("SELECT *
FROM iv_movimiento mov
	INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
WHERE id_kardex IS NULL;");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while($row = mysql_fetch_assoc($rs)) {
	
	if ($row['id_articulo_almacen_costo'] != "" && $row['id_articulo_costo'] != "" && $row['tipo_documento_movimiento'] != "") {
		$queryKardex = sprintf("SELECT * FROM iv_kardex
		WHERE id_documento = %s
			AND id_articulo = %s
			AND id_articulo_almacen_costo = %s
			AND id_articulo_costo = %s
			AND tipo_movimiento = %s
			AND id_clave_movimiento = %s
			AND tipo_documento_movimiento = %s
			AND cantidad = %s
			AND precio = %s
			AND costo = %s
			AND costo_cargo = %s
			AND costo_diferencia = %s
			AND porcentaje_descuento = %s
			AND subtotal_descuento = %s;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_articulo_almacen_costo'], "int"),
			valTpDato($row['id_articulo_costo'], "int"),
			valTpDato($row['id_tipo_movimiento'], "int"),
			valTpDato($row['id_clave_movimiento'], "int"),
			valTpDato($row['tipo_documento_movimiento'], "int"),
			valTpDato($row['cantidad'], "real_inglesa"),
			valTpDato((($row['precio'] > 0) ? $row['precio'] : 0), "campo"),
			valTpDato((($row['costo'] > 0) ? $row['costo'] : 0), "campo"),
			valTpDato((($row['costo_cargo'] > 0) ? $row['costo_cargo'] : 0), "campo"),
			valTpDato((($row['costo_diferencia'] > 0) ? $row['costo_diferencia'] : 0), "campo"),
			valTpDato((($row['porcentaje_descuento'] > 0) ? $row['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row['subtotal_descuento'] > 0) ? $row['subtotal_descuento'] : 0), "campo"));
	} else if ($row['id_articulo_almacen_costo'] != "" && $row['id_articulo_costo'] != "" && $row['tipo_documento_movimiento'] == "") {
		$queryKardex = sprintf("SELECT * FROM iv_kardex
		WHERE id_documento = %s
			AND id_articulo = %s
			AND id_articulo_almacen_costo = %s
			AND id_articulo_costo = %s
			AND tipo_movimiento = %s
			AND id_clave_movimiento = %s
			AND tipo_documento_movimiento IS %s
			AND cantidad = %s
			AND precio = %s
			AND costo = %s
			AND costo_cargo = %s
			AND costo_diferencia = %s
			AND porcentaje_descuento = %s
			AND subtotal_descuento = %s;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_articulo_almacen_costo'], "int"),
			valTpDato($row['id_articulo_costo'], "int"),
			valTpDato($row['id_tipo_movimiento'], "int"),
			valTpDato($row['id_clave_movimiento'], "int"),
			valTpDato($row['tipo_documento_movimiento'], "int"),
			valTpDato((($row['cantidad'] > 0) ? $row['cantidad'] : 0), "campo"),
			valTpDato((($row['precio'] > 0) ? $row['precio'] : 0), "campo"),
			valTpDato((($row['costo'] > 0) ? $row['costo'] : 0), "campo"),
			valTpDato((($row['costo_cargo'] > 0) ? $row['costo_cargo'] : 0), "campo"),
			valTpDato((($row['costo_diferencia'] > 0) ? $row['costo_diferencia'] : 0), "campo"),
			valTpDato((($row['porcentaje_descuento'] > 0) ? $row['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row['subtotal_descuento'] > 0) ? $row['subtotal_descuento'] : 0), "campo"));
	} else if ($row['id_articulo_almacen_costo'] == "" && $row['id_articulo_costo'] == "" && $row['tipo_documento_movimiento'] != "") {
		$queryKardex = sprintf("SELECT * FROM iv_kardex
		WHERE id_documento = %s
			AND id_articulo = %s
			AND id_articulo_almacen_costo IS %s
			AND id_articulo_costo IS %s
			AND tipo_movimiento = %s
			AND id_clave_movimiento = %s
			AND tipo_documento_movimiento = %s
			AND cantidad = %s
			AND precio = %s
			AND costo = %s
			AND costo_cargo = %s
			AND costo_diferencia = %s
			AND porcentaje_descuento = %s
			AND subtotal_descuento = %s;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_articulo_almacen_costo'], "int"),
			valTpDato($row['id_articulo_costo'], "int"),
			valTpDato($row['id_tipo_movimiento'], "int"),
			valTpDato($row['id_clave_movimiento'], "int"),
			valTpDato($row['tipo_documento_movimiento'], "int"),
			valTpDato((($row['cantidad'] > 0) ? $row['cantidad'] : 0), "campo"),
			valTpDato((($row['precio'] > 0) ? $row['precio'] : 0), "campo"),
			valTpDato((($row['costo'] > 0) ? $row['costo'] : 0), "campo"),
			valTpDato((($row['costo_cargo'] > 0) ? $row['costo_cargo'] : 0), "campo"),
			valTpDato((($row['costo_diferencia'] > 0) ? $row['costo_diferencia'] : 0), "campo"),
			valTpDato((($row['porcentaje_descuento'] > 0) ? $row['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row['subtotal_descuento'] > 0) ? $row['subtotal_descuento'] : 0), "campo"));
	} else if ($row['id_articulo_almacen_costo'] == "" && $row['id_articulo_costo'] == "" && $row['tipo_documento_movimiento'] == "") {
		$queryKardex = sprintf("SELECT * FROM iv_kardex
		WHERE id_documento = %s
			AND id_articulo = %s
			AND id_articulo_almacen_costo IS %s
			AND id_articulo_costo IS %s
			AND tipo_movimiento = %s
			AND id_clave_movimiento = %s
			AND tipo_documento_movimiento IS %s
			AND cantidad = %s
			AND precio = %s
			AND costo = %s
			AND costo_cargo = %s
			AND costo_diferencia = %s
			AND porcentaje_descuento = %s
			AND subtotal_descuento = %s;",
			valTpDato($row['id_documento'], "int"),
			valTpDato($row['id_articulo'], "int"),
			valTpDato($row['id_articulo_almacen_costo'], "int"),
			valTpDato($row['id_articulo_costo'], "int"),
			valTpDato($row['id_tipo_movimiento'], "int"),
			valTpDato($row['id_clave_movimiento'], "int"),
			valTpDato($row['tipo_documento_movimiento'], "int"),
			valTpDato((($row['cantidad'] > 0) ? $row['cantidad'] : 0), "campo"),
			valTpDato((($row['precio'] > 0) ? $row['precio'] : 0), "campo"),
			valTpDato((($row['costo'] > 0) ? $row['costo'] : 0), "campo"),
			valTpDato((($row['costo_cargo'] > 0) ? $row['costo_cargo'] : 0), "campo"),
			valTpDato((($row['costo_diferencia'] > 0) ? $row['costo_diferencia'] : 0), "campo"),
			valTpDato((($row['porcentaje_descuento'] > 0) ? $row['porcentaje_descuento'] : 0), "campo"),
			valTpDato((($row['subtotal_descuento'] > 0) ? $row['subtotal_descuento'] : 0), "campo"));
	}
	
	if (strlen($queryKardex) > 0) {
		$rsKardex = mysql_query($queryKardex);
		if (!$rsKardex) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsKardex = mysql_num_rows($rsKardex);
		$rowKardex = mysql_fetch_assoc($rsKardex);
		
		echo "<pre>".($queryKardex)."</pre>: ".__LINE__;
		
		if ($rowKardex['id_kardex'] > 0) {
			$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
				id_kardex = %s
			WHERE id_movimiento_detalle = %s;",
				valTpDato($rowKardex['id_kardex'], "int"),
				valTpDato($row['id_movimiento_detalle'], "date"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			echo "<pre>".($updateSQL)."</pre>: ".__LINE__;
		}
		
		echo "<br><br>";
	}
}




echo "<h2>DETALLE DE MOVIMIENTO SIN KARDEX</h2>";

// BUSCA LOS MOVIMIENTOS QUE TIENEN DETALLE Y NO KARDEX
$query = sprintf("SELECT * FROM iv_movimiento WHERE id_movimiento IN (SELECT id_movimiento FROM iv_movimiento_detalle WHERE id_kardex IS NULL)");
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while($row = mysql_fetch_assoc($rs)) {
	$idMovimiento = $row['id_movimiento'];
	$idTipoMovimiento = $row['id_tipo_movimiento'];
	$idClaveMovimiento = $row['id_clave_movimiento'];
	$fechaMovimiento = $row['fecha_captura'];
	$txtDescuento = 0;
	
	$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s",
		valTpDato($idClaveMovimiento, "int"));
	$rsClaveMov = mysql_query($queryClaveMov);
	if (!$rsClaveMov) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsClaveMov = mysql_num_rows($rsClaveMov);
	$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
	
	if ($row['id_tipo_movimiento'] == 1) { // Compra
		$queryFact = sprintf("SELECT * FROM cp_factura WHERE id_factura = %s",
			valTpDato($row['id_documento'], "int"));
		$rsFact = mysql_query($queryFact);
		if (!$rsFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFact = mysql_num_rows($rsFact);
		$rowFact = mysql_fetch_assoc($rsFact);
		
		$idModulo = $rowClaveMov['id_modulo'];
		$idFactura = $rowFact['id_factura'];
		$txtDescuento = ($rowFact['porcentaje_descuento'] > 0) ? $rowFact['porcentaje_descuento'] : 0;
		$txtObservacion = $rowFact['observacionFactura'];
		
		$queryFactDet = sprintf("SELECT * FROM cp_factura_detalle WHERE id_factura = %s",
			valTpDato($idFactura, "int"));
		$rsFactDet = mysql_query($queryFactDet);
		if (!$rsFactDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFactDet = mysql_num_rows($rsFactDet);
		while($rowFactDet = mysql_fetch_assoc($rsFactDet)) {
			$idArticulo = $rowFactDet['id_articulo'];
			$hddIdArticuloAlmacenCosto = $rowFactDet['id_articulo_almacen_costo'];
			$hddIdArticuloCosto = $rowFactDet['id_articulo_costo'];
			$cantDespachada = $rowFactDet['cantidad'];
			$precioUnitario = $rowFactDet['precio_unitario'];
			$costoUnitario = $rowFactDet['precio_unitario'];
			$idCasilla = $rowFactDet['id_casilla'];
			
			if ($hddIdArticuloAlmacenCosto == "" && $hddIdArticuloCosto == "") {
				// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
				$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
				WHERE id_movimiento = %s
					AND id_articulo = %s
					AND id_kardex IS NULL
					AND id_articulo_almacen_costo IS NULL
					AND id_articulo_costo IS NULL
					AND cantidad = %s
					AND precio = %s
					AND costo = %s
					AND tipo_costo = %s
					AND promocion = %s;",
					valTpDato($idMovimiento, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "campo"),
					valTpDato($costoUnitario, "campo"),
					valTpDato(0, "int"), // 0 = Unitario, 1 = Import
					valTpDato(0, "boolean")); // 0 = No, 1 = Si
			} else {
				// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
				$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
				WHERE id_movimiento = %s
					AND id_articulo = %s
					AND id_kardex IS NULL
					AND (id_articulo_almacen_costo = %s
						OR id_articulo_almacen_costo IS NULL)
					AND id_articulo_costo = %s
					AND cantidad = %s
					AND precio = %s
					AND costo = %s
					AND tipo_costo = %s
					AND promocion = %s;",
					valTpDato($idMovimiento, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "campo"),
					valTpDato($costoUnitario, "campo"),
					valTpDato(0, "int"), // 0 = Unitario, 1 = Import
					valTpDato(0, "boolean")); // 0 = No, 1 = Si
			}
			$rsMovDet = mysql_query($queryMovDet);
			if (!$rsMovDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsMovDet = mysql_num_rows($rsMovDet);
			$rowMovDet = mysql_fetch_assoc($rsMovDet);
			
			echo "<h1>".$queryMovDet."</h1> : ".__LINE__;
			
			if ($totalRowsMovDet > 0) {
				$txtObservacion = "REGISTRADO AUTOMATICO SIPRE ".date("Y-m-d H:i:s");
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, TIME(%s));",
					valTpDato($idModulo, "int"),
					valTpDato($idValeSalida, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($idClaveMovimiento, "int"),
					valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Crédito
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($txtDescuento, "real_inglesa"),
					valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
					valTpDato(1, "int"), // 0 = Entrada, 1 = Salida,
					valTpDato($fechaMovimiento, "date"),
					valTpDato($txtObservacion, "text"),
					valTpDato($fechaMovimiento, "date"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				echo "<pre>".($insertSQL)."</pre>";
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
					id_kardex = %s
				WHERE id_movimiento_detalle = %s;",
					valTpDato($idKardex, "int"),
					valTpDato($rowMovDet['id_movimiento_detalle'], "date"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				echo "<pre>".($updateSQL)."</pre>";
			}
			
			echo "<br><br>";
		}
	} else if ($row['id_tipo_movimiento'] == 2) { // Entrada
		if ($rowClaveMov['id_modulo'] == 0 && $row['tipo_documento_movimiento'] == 1) {
			$queryValeSal = sprintf("SELECT * FROM iv_vale_entrada WHERE id_vale_entrada = %s",
				valTpDato($row['id_documento'], "int"));
			$rsValeSal = mysql_query($queryValeSal);
			if (!$rsValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSal = mysql_num_rows($rsValeSal);
			$rowValeSal = mysql_fetch_assoc($rsValeSal);
			
			$idModulo = $rowClaveMov['id_modulo'];
			$idValeSalida = $rowValeSal['id_vale_entrada'];
			$txtObservacion = $rowValeSal['observacion'];
		
			$queryValeSalDet = sprintf("SELECT * FROM iv_vale_entrada_detalle WHERE id_vale_entrada = %s",
				valTpDato($idValeSalida, "int"));
			$rsValeSalDet = mysql_query($queryValeSalDet);
			if (!$rsValeSalDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSalDet = mysql_num_rows($rsValeSalDet);
			while($rowValeSalDet = mysql_fetch_assoc($rsValeSalDet)) {
				$idArticulo = $rowValeSalDet['id_articulo'];
				$hddIdArticuloAlmacenCosto = $rowValeSalDet['id_articulo_almacen_costo'];
				$hddIdArticuloCosto = $rowValeSalDet['id_articulo_costo'];
				$cantDespachada = $rowValeSalDet['cantidad'];
				$precioUnitario = $rowValeSalDet['precio_venta'];
				$costoUnitario = $rowValeSalDet['costo_compra'];
				$idCasilla = $rowValeSalDet['id_casilla'];
				
				if ($hddIdArticuloAlmacenCosto == "" && $hddIdArticuloCosto == "") {
					// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
					$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
					WHERE id_movimiento = %s
						AND id_articulo = %s
						AND id_kardex IS NULL
						AND id_articulo_almacen_costo IS NULL
						AND id_articulo_costo IS NULL
						AND cantidad = %s
						AND precio = %s
						AND costo = %s
						AND tipo_costo = %s
						AND promocion = %s;",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "campo"),
						valTpDato($costoUnitario, "campo"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean")); // 0 = No, 1 = Si
				} else {
					// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
					$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
					WHERE id_movimiento = %s
						AND id_articulo = %s
						AND id_kardex IS NULL
						AND (id_articulo_almacen_costo = %s
							OR id_articulo_almacen_costo IS NULL)
						AND id_articulo_costo = %s
						AND cantidad = %s
						AND precio = %s
						AND costo = %s
						AND tipo_costo = %s
						AND promocion = %s;",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "campo"),
						valTpDato($costoUnitario, "campo"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean")); // 0 = No, 1 = Si
				}
				$rsMovDet = mysql_query($queryMovDet);
				if (!$rsMovDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsMovDet = mysql_num_rows($rsMovDet);
				$rowMovDet = mysql_fetch_assoc($rsMovDet);
				
				echo "<h1>".$queryMovDet."</h1> : ".__LINE__;
				
				if ($totalRowsMovDet > 0) {
					$txtObservacion = "REGISTRADO AUTOMATICO SIPRE ".date("Y-m-d H:i:s");
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, TIME(%s));",
						valTpDato($idModulo, "int"),
						valTpDato($idValeSalida, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimiento, "int"),
						valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Crédito
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato($txtDescuento, "real_inglesa"),
						valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
						valTpDato(1, "int"), // 0 = Entrada, 1 = Salida,
						valTpDato($fechaMovimiento, "date"),
						valTpDato($txtObservacion, "text"),
						valTpDato($fechaMovimiento, "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($insertSQL)."</pre>";
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
						id_kardex = %s
					WHERE id_movimiento_detalle = %s;",
						valTpDato($idKardex, "int"),
						valTpDato($rowMovDet['id_movimiento_detalle'], "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($updateSQL)."</pre>";
				}
				
				echo "<br><br>";
			}
		} else if ($row['tipo_documento_movimiento'] == 2) {
			$queryNotaCred = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = %s",
				valTpDato($row['id_documento'], "int"));
			$rsNotaCred = mysql_query($queryNotaCred);
			if (!$rsNotaCred) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsNotaCred = mysql_num_rows($rsNotaCred);
			$rowNotaCred = mysql_fetch_assoc($rsNotaCred);
		
			$idModulo = $rowNotaCred['idDepartamentoNotaCredito'];
			$idNotaCredito = $rowNotaCred['idNotaCredito'];
			$txtDescuento = $rowNotaCred['porcentajeIvaNotaCredito'];
			$txtObservacion = $rowNotaCred['observacionesNotaCredito'];
			
			$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s",
				valTpDato($rowNotaCred['idDocumento'], "int"));
			$rsFact = mysql_query($queryFact);
			if (!$rsFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsFact = mysql_num_rows($rsFact);
			$rowFact = mysql_fetch_assoc($rsFact);
			
			$idOrden = $rowFact['numeroPedido'];
			$idFactura = $rowFact['idFactura'];
			
			$queryNotaCredDet = sprintf("SELECT * FROM cj_cc_nota_credito_detalle WHERE id_nota_credito = %s",
				valTpDato($idNotaCredito, "int"));
			$rsNotaCredDet = mysql_query($queryNotaCredDet);
			if (!$rsNotaCredDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsNotaCredDet = mysql_num_rows($rsNotaCredDet);
			while($rowNotaCredDet = mysql_fetch_assoc($rsNotaCredDet)) {
				$idArticulo = $rowNotaCredDet['id_articulo'];
				$hddIdArticuloAlmacenCosto = $rowNotaCredDet['id_articulo_almacen_costo'];
				$hddIdArticuloCosto = $rowNotaCredDet['id_articulo_costo'];
				$cantDespachada = $rowNotaCredDet['cantidad'];
				$precioUnitario = $rowNotaCredDet['precio_unitario'];
				$costoUnitario = $rowNotaCredDet['costo_compra'];
				
				if ($idModulo == 0) {
					if ($hddIdArticuloCosto > 0) {
						// BUSCA LA CASILLA DEL DETALLE
						$queryOrdenDet = sprintf("SELECT * FROM iv_pedido_venta_detalle ped_vent_det
						WHERE ped_vent_det.id_pedido_venta = %s
							AND ped_vent_det.id_articulo = %s
							AND ped_vent_det.id_articulo_costo = %s;",
							valTpDato($idOrden, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($hddIdArticuloCosto, "int"));
					} else {
						// BUSCA LA CASILLA DEL DETALLE
						$queryOrdenDet = sprintf("SELECT * FROM iv_pedido_venta_detalle ped_vent_det
						WHERE ped_vent_det.id_pedido_venta = %s
							AND ped_vent_det.id_articulo = %s
							AND ped_vent_det.id_articulo_costo IS NULL;",
							valTpDato($idOrden, "int"),
							valTpDato($idArticulo, "int"));
					}
					$rsOrdenDet = mysql_query($queryOrdenDet);
					if (!$rsOrdenDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsOrdenDet = mysql_num_rows($rsOrdenDet);
					$rowOrdenDet = mysql_fetch_assoc($rsOrdenDet);
					
					$idCasilla = $rowOrdenDet['id_casilla'];
				}
				
				if ($hddIdArticuloAlmacenCosto == "" && $hddIdArticuloCosto == "") {
					// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
					$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
					WHERE id_movimiento = %s
						AND id_articulo = %s
						AND id_kardex IS NULL
						AND id_articulo_almacen_costo IS NULL
						AND id_articulo_costo IS NULL
						AND cantidad = %s
						AND precio = %s
						AND costo = %s
						AND tipo_costo = %s
						AND promocion = %s;",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "campo"),
						valTpDato($costoUnitario, "campo"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean")); // 0 = No, 1 = Si
					
					echo "<pre>".($queryMovDet)."</pre>";
				} else {
					// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
					$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
					WHERE id_movimiento = %s
						AND id_articulo = %s
						AND id_kardex IS NULL
						AND (id_articulo_almacen_costo = %s
							OR id_articulo_almacen_costo IS NULL)
						AND id_articulo_costo = %s
						AND cantidad = %s
						AND precio = %s
						AND costo = %s
						AND tipo_costo = %s
						AND promocion = %s;",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "campo"),
						valTpDato($costoUnitario, "campo"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean")); // 0 = No, 1 = Si
				}
				$rsMovDet = mysql_query($queryMovDet);
				if (!$rsMovDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsMovDet = mysql_num_rows($rsMovDet);
				$rowMovDet = mysql_fetch_assoc($rsMovDet);
				
				if ($totalRowsOrdenDet > 0 && $totalRowsMovDet > 0) {
					$txtObservacion = "REGISTRADO AUTOMATICO SIPRE ".date("Y-m-d H:i:s");
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, TIME(%s));",
						valTpDato($idModulo, "int"),
						valTpDato($idFactura, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimiento, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato($txtDescuento, "real_inglesa"),
						valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
						valTpDato(1, "int"), // 0 = Entrada, 1 = Salida,
						valTpDato($fechaMovimiento, "date"),
						valTpDato($txtObservacion, "text"),
						valTpDato($fechaMovimiento, "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($insertSQL)."</pre>";
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
						id_kardex = %s
					WHERE id_movimiento_detalle = %s;",
						valTpDato($idKardex, "int"),
						valTpDato($rowMovDet['id_movimiento_detalle'], "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($updateSQL)."</pre>";
				}
				
				echo "<br><br>";
			}
		}
	} else if ($row['id_tipo_movimiento'] == 3) { // Venta
		$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s",
			valTpDato($row['id_documento'], "int"));
		$rsFact = mysql_query($queryFact);
		if (!$rsFact) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFact = mysql_num_rows($rsFact);
		$rowFact = mysql_fetch_assoc($rsFact);
		
		$idOrden = $rowFact['numeroPedido'];
		$idModulo = $rowFact['idDepartamentoOrigenFactura'];
		$idFactura = $rowFact['idFactura'];
		$txtDescuento = $rowFact['porcentaje_descuento'];
		$txtObservacion = $rowFact['observacionFactura'];
		
		$queryFactDet = sprintf("SELECT * FROM cj_cc_factura_detalle WHERE id_factura = %s",
			valTpDato($idFactura, "int"));
		$rsFactDet = mysql_query($queryFactDet);
		if (!$rsFactDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFactDet = mysql_num_rows($rsFactDet);
		
		if (!($totalRowsFactDet > 0)) {
			$queryFactDet = sprintf("SELECT
				id_articulo,
				id_articulo_almacen_costo,
				id_articulo_costo,
				cantidad,
				precio_unitario,
				costo AS costo_compra
			FROM sa_det_fact_articulo WHERE idFactura = %s",
				valTpDato($idFactura, "int"));
			$rsFactDet = mysql_query($queryFactDet);
			if (!$rsFactDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsFactDet = mysql_num_rows($rsFactDet);
		}
		
		while($rowFactDet = mysql_fetch_assoc($rsFactDet)) {
			$idArticulo = $rowFactDet['id_articulo'];
			$hddIdArticuloAlmacenCosto = $rowFactDet['id_articulo_almacen_costo'];
			$hddIdArticuloCosto = $rowFactDet['id_articulo_costo'];
			$cantDespachada = $rowFactDet['cantidad'];
			$precioUnitario = $rowFactDet['precio_unitario'];
			$costoUnitario = $rowFactDet['costo_compra'];
			
			if ($idModulo == 0) {
				if ($hddIdArticuloCosto > 0) {
					// BUSCA LA CASILLA DEL DETALLE
					$queryOrdenDet = sprintf("SELECT * FROM iv_pedido_venta_detalle ped_vent_det
					WHERE ped_vent_det.id_pedido_venta = %s
						AND ped_vent_det.id_articulo = %s
						AND ped_vent_det.id_articulo_costo = %s;",
						valTpDato($idOrden, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($hddIdArticuloCosto, "int"));
				} else {
					// BUSCA LA CASILLA DEL DETALLE
					$queryOrdenDet = sprintf("SELECT * FROM iv_pedido_venta_detalle ped_vent_det
					WHERE ped_vent_det.id_pedido_venta = %s
						AND ped_vent_det.id_articulo = %s
						AND ped_vent_det.id_articulo_costo IS NULL;",
						valTpDato($idOrden, "int"),
						valTpDato($idArticulo, "int"));
				}
				$rsOrdenDet = mysql_query($queryOrdenDet);
				if (!$rsOrdenDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsOrdenDet = mysql_num_rows($rsOrdenDet);
				$rowOrdenDet = mysql_fetch_assoc($rsOrdenDet);
				
				$idCasilla = $rowOrdenDet['id_casilla'];
			} else if ($idModulo == 1) {
				if ($hddIdArticuloCosto > 0) {
					// BUSCA LA CASILLA DEL DETALLE
					$queryOrdenDet = sprintf("SELECT *
					FROM sa_det_orden_articulo det_orden_art
						INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
					WHERE det_orden_art.id_orden = %s
						AND det_orden_art.id_articulo = %s
						AND det_orden_art.id_articulo_costo = %s
						AND det_sol_rep.id_estado_solicitud = 5;",
						valTpDato($idOrden, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($hddIdArticuloCosto, "int"));
				} else {
					// BUSCA LA CASILLA DEL DETALLE
					$queryOrdenDet = sprintf("SELECT *
					FROM sa_det_orden_articulo det_orden_art
						INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
					WHERE det_orden_art.id_orden = %s
						AND det_orden_art.id_articulo = %s
						AND det_orden_art.id_articulo_costo IS NULL
						AND det_sol_rep.id_estado_solicitud = 5;",
						valTpDato($idOrden, "int"),
						valTpDato($idArticulo, "int"));
				}
				$rsOrdenDet = mysql_query($queryOrdenDet);
				if (!$rsOrdenDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsOrdenDet = mysql_num_rows($rsOrdenDet);
				$rowOrdenDet = mysql_fetch_assoc($rsOrdenDet);
				
				$idCasilla = $rowOrdenDet['id_casilla'];
				
				echo "<pre>".($queryOrdenDet)."</pre>";
			}
			
			if ($hddIdArticuloAlmacenCosto == "" && $hddIdArticuloCosto == "") {
				// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
				$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
				WHERE id_movimiento = %s
					AND id_articulo = %s
					AND id_kardex IS NULL
					AND id_articulo_almacen_costo IS NULL
					AND id_articulo_costo IS NULL
					AND cantidad = %s
					AND precio = %s
					AND costo = %s
					AND tipo_costo = %s
					AND promocion = %s;",
					valTpDato($idMovimiento, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "campo"),
					valTpDato($costoUnitario, "campo"),
					valTpDato(0, "int"), // 0 = Unitario, 1 = Import
					valTpDato(0, "boolean")); // 0 = No, 1 = Si
				
				echo "<pre>".($queryMovDet)."</pre>";
			} else {
				// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
				$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
				WHERE id_movimiento = %s
					AND id_articulo = %s
					AND id_kardex IS NULL
					AND (id_articulo_almacen_costo = %s
						OR id_articulo_almacen_costo IS NULL)
					AND id_articulo_costo = %s
					AND cantidad = %s
					AND precio = %s
					AND costo = %s
					AND tipo_costo = %s
					AND promocion = %s;",
					valTpDato($idMovimiento, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "campo"),
					valTpDato($costoUnitario, "campo"),
					valTpDato(0, "int"), // 0 = Unitario, 1 = Import
					valTpDato(0, "boolean")); // 0 = No, 1 = Si
			}
			$rsMovDet = mysql_query($queryMovDet);
			if (!$rsMovDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsMovDet = mysql_num_rows($rsMovDet);
			$rowMovDet = mysql_fetch_assoc($rsMovDet);
			
			if ($totalRowsOrdenDet > 0 && $totalRowsMovDet > 0) {
				$txtObservacion = "REGISTRADO AUTOMATICO SIPRE ".date("Y-m-d H:i:s");
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, TIME(%s));",
					valTpDato($idModulo, "int"),
					valTpDato($idFactura, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($idClaveMovimiento, "int"),
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($txtDescuento, "real_inglesa"),
					valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
					valTpDato(1, "int"), // 0 = Entrada, 1 = Salida,
					valTpDato($fechaMovimiento, "date"),
					valTpDato($txtObservacion, "text"),
					valTpDato($fechaMovimiento, "date"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				echo "<pre>".($insertSQL)."</pre>";
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
					id_kardex = %s
				WHERE id_movimiento_detalle = %s;",
					valTpDato($idKardex, "int"),
					valTpDato($rowMovDet['id_movimiento_detalle'], "date"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				echo "<pre>".($updateSQL)."</pre>";
			}
			
			echo "<br><br>";
		}
	} else if ($row['id_tipo_movimiento'] == 4) { // Salida
		if ($rowClaveMov['id_modulo'] == 0 && $row['tipo_documento_movimiento'] == 1) {
			$queryValeSal = sprintf("SELECT * FROM iv_vale_salida WHERE id_vale_salida = %s",
				valTpDato($row['id_documento'], "int"));
			$rsValeSal = mysql_query($queryValeSal);
			if (!$rsValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSal = mysql_num_rows($rsValeSal);
			$rowValeSal = mysql_fetch_assoc($rsValeSal);
			
			$idModulo = $rowClaveMov['id_modulo'];
			$idValeSalida = $rowValeSal['id_vale_salida'];
			$txtObservacion = $rowValeSal['observacion'];
		
			$queryValeSalDet = sprintf("SELECT * FROM iv_vale_salida_detalle WHERE id_vale_salida = %s",
				valTpDato($idValeSalida, "int"));
			$rsValeSalDet = mysql_query($queryValeSalDet);
			if (!$rsValeSalDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSalDet = mysql_num_rows($rsValeSalDet);
			while($rowValeSalDet = mysql_fetch_assoc($rsValeSalDet)) {
				$idArticulo = $rowValeSalDet['id_articulo'];
				$hddIdArticuloAlmacenCosto = $rowValeSalDet['id_articulo_almacen_costo'];
				$hddIdArticuloCosto = $rowValeSalDet['id_articulo_costo'];
				$cantDespachada = $rowValeSalDet['cantidad'];
				$precioUnitario = $rowValeSalDet['precio_venta'];
				$costoUnitario = $rowValeSalDet['costo_compra'];
				$idCasilla = $rowValeSalDet['id_casilla'];
				
				if ($hddIdArticuloAlmacenCosto == "" && $hddIdArticuloCosto == "") {
					// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
					$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
					WHERE id_movimiento = %s
						AND id_articulo = %s
						AND id_kardex IS NULL
						AND id_articulo_almacen_costo IS NULL
						AND id_articulo_costo IS NULL
						AND cantidad = %s
						AND precio = %s
						AND costo = %s
						AND tipo_costo = %s
						AND promocion = %s;",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "campo"),
						valTpDato($costoUnitario, "campo"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean")); // 0 = No, 1 = Si
				} else {
					// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
					$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
					WHERE id_movimiento = %s
						AND id_articulo = %s
						AND id_kardex IS NULL
						AND (id_articulo_almacen_costo = %s
							OR id_articulo_almacen_costo IS NULL)
						AND id_articulo_costo = %s
						AND cantidad = %s
						AND precio = %s
						AND costo = %s
						AND tipo_costo = %s
						AND promocion = %s;",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "campo"),
						valTpDato($costoUnitario, "campo"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean")); // 0 = No, 1 = Si
				}
				$rsMovDet = mysql_query($queryMovDet);
				if (!$rsMovDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsMovDet = mysql_num_rows($rsMovDet);
				$rowMovDet = mysql_fetch_assoc($rsMovDet);
				
				echo "<h1>".$queryMovDet."</h1> : ".__LINE__;
				
				if ($totalRowsMovDet > 0) {
					$txtObservacion = "REGISTRADO AUTOMATICO SIPRE ".date("Y-m-d H:i:s");
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, TIME(%s));",
						valTpDato($idModulo, "int"),
						valTpDato($idValeSalida, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimiento, "int"),
						valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Crédito
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato($txtDescuento, "real_inglesa"),
						valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
						valTpDato(1, "int"), // 0 = Entrada, 1 = Salida,
						valTpDato($fechaMovimiento, "date"),
						valTpDato($txtObservacion, "text"),
						valTpDato($fechaMovimiento, "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($insertSQL)."</pre>";
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
						id_kardex = %s
					WHERE id_movimiento_detalle = %s;",
						valTpDato($idKardex, "int"),
						valTpDato($rowMovDet['id_movimiento_detalle'], "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($updateSQL)."</pre>";
				}
				
				echo "<br><br>";
			}
		} else if ($rowClaveMov['id_modulo'] == 1 && $row['tipo_documento_movimiento'] == 1) {
			$queryValeSal = sprintf("SELECT * FROM sa_vale_salida WHERE id_vale_salida = %s",
				valTpDato($row['id_documento'], "int"));
			$rsValeSal = mysql_query($queryValeSal);
			if (!$rsValeSal) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSal = mysql_num_rows($rsValeSal);
			$rowValeSal = mysql_fetch_assoc($rsValeSal);
			
			$idOrden = $rowValeSal['id_orden'];
			$idModulo = $rowClaveMov['id_modulo'];
			$idValeSalida = $rowValeSal['id_vale_salida'];
			$txtDescuento = $rowValeSal['descuento'];
			$txtObservacion = $rowValeSal['observacionFactura'];
		
			$queryValeSalDet = sprintf("SELECT * FROM sa_det_vale_salida_articulo WHERE id_vale_salida = %s",
				valTpDato($idValeSalida, "int"));
			$rsValeSalDet = mysql_query($queryValeSalDet);
			if (!$rsValeSalDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSalDet = mysql_num_rows($rsValeSalDet);
			while($rowValeSalDet = mysql_fetch_assoc($rsValeSalDet)) {
				$idArticulo = $rowValeSalDet['id_articulo'];
				$hddIdArticuloAlmacenCosto = $rowValeSalDet['id_articulo_almacen_costo'];
				$hddIdArticuloCosto = $rowValeSalDet['id_articulo_costo'];
				$cantDespachada = $rowValeSalDet['cantidad'];
				$precioUnitario = $rowValeSalDet['precio_unitario'];
				$costoUnitario = $rowValeSalDet['costo'];
				
				if ($hddIdArticuloCosto > 0) {
					// BUSCA LA CASILLA DEL DETALLE
					$queryOrdenDet = sprintf("SELECT *
					FROM sa_det_orden_articulo det_orden_art
						INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
					WHERE det_orden_art.id_orden = %s
						AND det_orden_art.id_articulo = %s
						AND det_orden_art.id_articulo_costo = %s
						AND det_sol_rep.id_estado_solicitud = 5;",
						valTpDato($idOrden, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($hddIdArticuloCosto, "int"));
				} else {
					// BUSCA LA CASILLA DEL DETALLE
					$queryOrdenDet = sprintf("SELECT *
					FROM sa_det_orden_articulo det_orden_art
						INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
					WHERE det_orden_art.id_orden = %s
						AND det_orden_art.id_articulo = %s
						AND det_orden_art.id_articulo_costo IS NULL
						AND det_sol_rep.id_estado_solicitud = 5;",
						valTpDato($idOrden, "int"),
						valTpDato($idArticulo, "int"));
				}
				$rsOrdenDet = mysql_query($queryOrdenDet);
				if (!$rsOrdenDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsOrdenDet = mysql_num_rows($rsOrdenDet);
				$rowOrdenDet = mysql_fetch_assoc($rsOrdenDet);
				
				$idCasilla = $rowOrdenDet['id_casilla'];
				
				echo "<pre>".($queryOrdenDet)."</pre>";
				
				if ($hddIdArticuloAlmacenCosto == "" && $hddIdArticuloCosto == "") {
					// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
					$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
					WHERE id_movimiento = %s
						AND id_articulo = %s
						AND id_kardex IS NULL
						AND id_articulo_almacen_costo IS NULL
						AND id_articulo_costo IS NULL
						AND cantidad = %s
						AND precio = %s
						AND costo = %s
						AND tipo_costo = %s
						AND promocion = %s;",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "campo"),
						valTpDato($costoUnitario, "campo"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean")); // 0 = No, 1 = Si
				} else {
					// BUSCA QUE EL DETALLE DEL MOVIMIENTO NO TENGA KARDEX
					$queryMovDet = sprintf("SELECT * FROM iv_movimiento_detalle
					WHERE id_movimiento = %s
						AND id_articulo = %s
						AND id_kardex IS NULL
						AND (id_articulo_almacen_costo = %s
							OR id_articulo_almacen_costo IS NULL)
						AND id_articulo_costo = %s
						AND cantidad = %s
						AND precio = %s
						AND costo = %s
						AND tipo_costo = %s
						AND promocion = %s;",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "campo"),
						valTpDato($costoUnitario, "campo"),
						valTpDato(0, "int"), // 0 = Unitario, 1 = Import
						valTpDato(0, "boolean")); // 0 = No, 1 = Si
				}
				$rsMovDet = mysql_query($queryMovDet);
				if (!$rsMovDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsMovDet = mysql_num_rows($rsMovDet);
				$rowMovDet = mysql_fetch_assoc($rsMovDet);
				
				echo "<h1>".$queryMovDet."</h1> : ".__LINE__;
				
				if ($totalRowsOrdenDet > 0 && $totalRowsMovDet > 0) {
					$txtObservacion = "REGISTRADO AUTOMATICO SIPRE ".date("Y-m-d H:i:s");
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, TIME(%s));",
						valTpDato($idModulo, "int"),
						valTpDato($idValeSalida, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($hddIdArticuloAlmacenCosto, "int"),
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($idTipoMovimiento, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimiento, "int"),
						valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Crédito
						valTpDato($cantDespachada, "real_inglesa"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($costoUnitario, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato($txtDescuento, "real_inglesa"),
						valTpDato(((str_replace(",", "", $txtDescuento) * $precioUnitario) / 100), "real_inglesa"),
						valTpDato(1, "int"), // 0 = Entrada, 1 = Salida,
						valTpDato($fechaMovimiento, "date"),
						valTpDato($txtObservacion, "text"),
						valTpDato($fechaMovimiento, "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($insertSQL)."</pre>";
					
					// REGISTRA EL MOVIMIENTO DEL ARTICULO
					$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
						id_kardex = %s
					WHERE id_movimiento_detalle = %s;",
						valTpDato($idKardex, "int"),
						valTpDato($rowMovDet['id_movimiento_detalle'], "date"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
					$idKardex = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					echo "<pre>".($updateSQL)."</pre>";
				}
				
				echo "<br><br>";
			}
		}
	}
}*/

$Result1 = actualizarSaldos();
if ($Result1[0] != true && strlen($Result1[1]) > 0) { $arrayLoteInvalido[] = ($Result1[1]); }

$Result1 = actualizarMovimientoTotal();
if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }

mysql_query("COMMIT;");

echo "<h1>DETALLES GENERADOS CON EXITO</h1>";

echo "<pre>".((isset($arrayLoteInvalido)) ? implode(", ", $arrayLoteInvalido) : "")."</pre>";
?>