<?php
set_time_limit(0);

function generarComision($idDocumento, $generarDirecto = true, $mesCierre = "", $anoCierre = "", $fechaDefinida = "") {
	global $conex;
	
	$fechaDefinida = ($fechaDefinida != "") ? valTpDato(date("Y-m-d H:i:s", strtotime($fechaDefinida)), "date") : valTpDato("NOW()", "campo");
	$fechaComision = ($generarDirecto == true) ? $fechaDefinida : valTpDato($anoCierre."-".$mesCierre."-".ultimoDia($mesCierre,$anoCierre)." ".date("H:i:s"), "date");
	
	$queryFact = sprintf("SELECT
		cxc_fact.idFactura,
		cxc_fact.id_empresa,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.idVendedor,
		cxc_fact.idDepartamentoOrigenFactura,
		cxc_fact.subtotalFactura,
		cxc_fact.porcentaje_descuento,
		cxc_fact.descuentoFactura,
		cxc_fact.estatus_factura,
		cxc_fact.estadoFactura,
		
		(IFNULL((SELECT SUM((cantidad * costo_compra)) FROM cj_cc_factura_detalle cxc_fact_det
				WHERE cxc_fact_det.id_factura = cxc_fact.idFactura), 0)
			+ IFNULL((SELECT SUM(costo_compra) FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_veh
					WHERE cxc_fact_det_veh.id_factura = cxc_fact.idFactura), 0)
			+ IFNULL((SELECT SUM(costo_compra) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
					WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura), 0)) AS total_costo,
		
		(SELECT SUM(cxc_fact_iva.iva) FROM cj_cc_factura_iva cxc_fact_iva
		WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura) AS porcentajeIvaFactura,
		
		(SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
		WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura) AS total_impuesto,
		
		(SELECT COUNT(id_factura) FROM cj_cc_factura_detalle cxc_fact_det
			INNER JOIN iv_articulos art ON (cxc_fact_det.id_articulo = art.id_articulo)
		WHERE cxc_fact_det.id_factura = cxc_fact.idFactura) AS items_repuestos,
		
		(SELECT COUNT(id_factura) FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
		WHERE cxc_fact_det_vehic.id_factura = cxc_fact.idFactura) AS items_vehiculos,
		
		(SELECT COUNT(id_factura) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura) AS items_accesorios
		
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE cxc_fact.idFactura = %s;",
		valTpDato($idDocumento, "int"));
	$rsFact = mysql_query($queryFact, $conex);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$porcDescuentoFact = $rowFact['porcentaje_descuento'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura'];
	$idVendedor = $rowFact['idVendedor'];
	$porcIva = $rowFact['porcentajeIvaFactura'];
	
	if ($rowFact['items_repuestos'] > 0 || $rowFact['items_vehiculos'] > 0 || $rowFact['items_accesorios'] > 0) {
		// 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario; 8 = Facturado
		($rowFact['items_repuestos'] > 0) ? $arrayTipoComision[] = 4 : "";
		($rowFact['items_vehiculos'] > 0) ? $arrayTipoComision[] = 5 : "";
		($rowFact['items_accesorios'] > 0) ? $arrayTipoComision[] = 6 : "";
		$arrayTipoComision[] = 8;
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_comision IN (%s)",
			valTpDato(implode(",",$arrayTipoComision), "campo"));
	} else {
		if ($rowFact['items_repuestos'] == 0 && $rowFact['items_vehiculos'] == 0 && $rowFact['items_accesorios'] == 0 && $idModulo == 0) { // CxC
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision IN (4,8)");
		} else {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision = -1");
		}
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo = %s",
		valTpDato($idModulo, "int"));
	
	// 1 = Por Venta Propia, 2 = Por Venta General, 3 = Por Venta Subordinada
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((modo_comision = 2 AND id_empleado <> %s)
	OR (modo_comision = 1 AND id_empleado = %s))",
		valTpDato($idVendedor, "int"),
		valTpDato($idVendedor, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	if ($generarDirecto == false) {
		switch ($idModulo) {
			// 1 = Simple, 2 = Por Productividad, 3 = Por Rango, 4 = Por Item
			case 1 :	$tipo_porcentaje = "2"; break;
			case 2 :	$tipo_porcentaje = "3,4"; break;
			default :	$tipo_porcentaje = "-1";
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje IN (%s)",
				valTpDato($tipo_porcentaje, "campo"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado = %s",
				valTpDato($idVendedor, "int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje IN (1)");
	}
	
	$queryEmpleado = sprintf("SELECT DISTINCT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cargo_dep.id_cargo_departamento
	FROM pg_empleado empleado
		INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
		LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento) %s
	ORDER BY comision.porcentaje_comision", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado, $conex);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRowsEmpleado = mysql_num_rows($rsEmpleado);
	while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
		$idEmpleado = $rowEmpleado['id_empleado'];
		
		$queryComision = sprintf("SELECT * FROM pg_comision_empleado
		WHERE id_empleado = %s
			AND id_cargo_departamento = %s
			AND id_factura = %s;",
			valTpDato($idEmpleado, "int"),
			valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
			valTpDato($idDocumento, "int"));
		$rsComision = mysql_query($queryComision);
		if (!$rsComision) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		$totalRowsComision = mysql_num_rows($rsComision);
		$rowComision = mysql_fetch_array($rsComision);
		
		$idComisionEmpleado = $rowComision['id_comision_empleado'];
		
		if (!($idComisionEmpleado > 0)) {
			$insertSQL = sprintf("INSERT INTO pg_comision_empleado (id_empleado, id_cargo_departamento, id_factura, fecha_comision)
			VALUE (%s, %s, %s, %s);",
				valTpDato($idEmpleado, "int"),
				valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
				valTpDato($idDocumento, "int"),
				$fechaComision);
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__."\nSQL: ".$insertSQL);
			$idComisionEmpleado = mysql_insert_id();
		}
		
		if ($idComisionEmpleado > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 = $cond.sprintf("id_empleado = %s",
				valTpDato($idEmpleado, "int"));
			
			$query = sprintf("SELECT
				comision.id_comision,
				empleado.id_empleado,
				CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
				porcentaje_comision,
				tipo_porcentaje,
				tipo_importe,
				aplica_iva,
				tipo_comision,
				modo_comision
			FROM pg_empleado empleado
				INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
				LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision) %s %s
			ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
			$rs = mysql_query($query, $conex);
			if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$arrayComision = NULL;
			while ($row = mysql_fetch_assoc($rs)) {
				$idTipoPorcentaje = $row['tipo_porcentaje'];
				
				if (($generarDirecto == true && $idTipoPorcentaje != 2)
				|| ($generarDirecto == false && in_array($idTipoPorcentaje,array(2,3)))) { // 1 = Simple, 2 = Por Productividad, 3 = Por Rango, 4 = Por Item
					$totalRowsFactDet = NULL;
					
					if ($idTipoPorcentaje == 2) { // 1 = Simple, 2 = Por Productividad, 3 = Por Rango, 4 = Por Item
						// TOTAL UT MENSUAL
						$queryMensualUT = sprintf("SELECT
							iv_cierre_mensual_facturacion.id_cierre_mensual,
							id_cierre_mensual_facturacion,
							id_empleado,
							total_ut_fisica,
							total_ut
						FROM iv_cierre_mensual_facturacion
							INNER JOIN iv_cierre_mensual ON (iv_cierre_mensual_facturacion.id_cierre_mensual = iv_cierre_mensual.id_cierre_mensual)
						WHERE id_empleado = %s AND mes = %s AND ano = %s AND id_empresa = %s AND total_ut_fisica > %s",
							valTpDato($idEmpleado, "int"),
							valTpDato(date("m",strtotime($rowFact['fechaRegistroFactura'])), "text"),
							valTpDato(date("Y",strtotime($rowFact['fechaRegistroFactura'])), "text"),
							valTpDato($idEmpresa, "int"),
							valTpDato(0, "int"));
						$ResultMensualUT = mysql_query($queryMensualUT); 
						if (!$ResultMensualUT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$rowMensualUT = mysql_fetch_array($ResultMensualUT);
						
						$horasFacturadas = $rowMensualUT['total_ut'];
						$horasFisicas = $rowMensualUT['total_ut_fisica'];
						
						$porcentajeProductividad = round(($horasFacturadas / $horasFisicas) * 100, 0); // PORCENTAJE DE PRODUCTIVIDAD
						
						//SQL NIVEL DE PRODUCTIVIDAD
						$queryNivel = sprintf("SELECT * FROM pg_comision_productividad
						WHERE id_comision = %s
							AND %s BETWEEN mayor AND menor OR (%s > mayor AND menor = 0);", 
							valTpDato($row['id_comision'], "int"), 
							valTpDato($porcentajeProductividad, "real_inglesa"),
							valTpDato($porcentajeProductividad, "real_inglesa"));
						$ResultNivel = mysql_query($queryNivel);
						if (!$ResultNivel) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$rowNivelProd = mysql_fetch_array($ResultNivel);
						
						$porcComision = $rowNivelProd['porcentaje'];
					} else {
						$porcComision = $row['porcentaje_comision'];
					}
					
					if ($rowFact['items_repuestos'] == 0 && $rowFact['items_vehiculos'] == 0 && $rowFact['items_accesorios'] == 0) {
						$cantidad = 1;
						$precioUnitario = $rowFact['subtotalFactura'];
						$costoUnitario = 0;
						$porcIva = $rowFact['porcentajeIvaFactura'];
						
						$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
						$baseComision = floatval($precioUnitario - $descuento);
						$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						
						$montoComision = floatval(($porcComision * $baseComision) / 100);
						
						$arrayComision[0] += $cantidad * $precioUnitario;
						$arrayComision[1] += $cantidad * $descuento;
						$arrayComision[2] += $cantidad * $costoUnitario;
						$arrayComision[3] += $porcComision;
						$arrayComision[4] += $cantidad * $montoComision;
					}
					
					if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
						$queryDet = sprintf("SELECT *,
							(SELECT SUM(cxc_fact_det_imp.impuesto) FROM cj_cc_factura_detalle_impuesto cxc_fact_det_imp
							WHERE cxc_fact_det_imp.id_factura_detalle = cxc_fact_det.id_factura_detalle) AS porcentaje_impuesto
						FROM cj_cc_factura_detalle cxc_fact_det
							INNER JOIN iv_articulos art ON (cxc_fact_det.id_articulo = art.id_articulo)
						WHERE art.genera_comision = 1
							AND cxc_fact_det.id_factura = %s;",
							valTpDato($idDocumento, "int"));
						$rsDet = mysql_query($queryDet, $conex);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$totalRowsDet = mysql_num_rows($rsDet);
						while ($rowDet = mysql_fetch_assoc($rsDet)) {
							$cantidad = $rowDet['cantidad'];
							$precioUnitario = $rowDet['precio_unitario'];
							$costoUnitario = $rowDet['costo_compra'];
							$porcIva = $rowDet['porcentaje_impuesto'];
							
							$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
							$montoComision = floatval(($porcComision * $baseComision) / 100);
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_articulo, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($rowDet['id_articulo'], "int"),
								valTpDato($rowDet['cantidad'], "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += $cantidad * $descuento;
							$arrayComision[2] += $cantidad * $costoUnitario;
							$arrayComision[3] += $porcComision;
							$arrayComision[4] += $cantidad * $montoComision;
						}
					}
					
					if ($row['tipo_comision'] == 5 && $rowFact['items_vehiculos'] > 0) {
						$queryDet = sprintf("SELECT *,
							(SELECT SUM(cxc_fact_det_veh_imp.impuesto) FROM cj_cc_factura_detalle_vehiculo_impuesto cxc_fact_det_veh_imp
							WHERE cxc_fact_det_veh_imp.id_factura_detalle_vehiculo = cxc_fact_det_veh.id_factura_detalle_vehiculo) AS porcentaje_impuesto
						FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_veh
						WHERE cxc_fact_det_veh.id_factura = %s;",
							valTpDato($idDocumento, "int"));
						$rsDet = mysql_query($queryDet, $conex);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$totalRowsDet = mysql_num_rows($rsDet);
						while ($rowDet = mysql_fetch_assoc($rsDet)) {
							$cantidad = 1;
							$precioUnitario = $rowDet['precio_unitario'];
							$costoUnitario = $rowDet['costo_compra'];
							$porcIva = $rowDet['porcentaje_impuesto'];
							
							$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
							$montoComision = floatval(($porcComision * $baseComision) / 100);
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_unidad_fisica, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($rowDet['id_unidad_fisica'], "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += $cantidad * $descuento;
							$arrayComision[2] += $cantidad * $costoUnitario;
							$arrayComision[3] += $porcComision;
							$arrayComision[4] += $cantidad * $montoComision;
						}
					}
					
					if ($row['tipo_comision'] == 6 && $rowFact['items_accesorios'] > 0) {
						$queryDet = sprintf("SELECT * FROM an_accesorio acc
							INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (acc.id_accesorio = cxc_fact_det_acc.id_accesorio)
						WHERE acc.genera_comision = 1
							AND cxc_fact_det_acc.id_factura = %s;",
							valTpDato($idDocumento, "int"));
						$rsDet = mysql_query($queryDet, $conex);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$totalRowsDet = mysql_num_rows($rsDet);
						while ($rowDet = mysql_fetch_assoc($rsDet)) {
							$cantidad = 1;
							$precioUnitario = $rowDet['precio_unitario'];
							$costoUnitario = $rowDet['costo_compra'];
							$porcIva = $rowFact['porcentajeIvaFactura'];
							
							$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
							$montoComision = floatval(($porcComision * $baseComision) / 100);
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_accesorio, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($rowDet['id_accesorio'], "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += $cantidad * $descuento;
							$arrayComision[2] += $cantidad * $costoUnitario;
							$arrayComision[3] += $porcComision;
							$arrayComision[4] += $cantidad * $montoComision;
						}
					}
					
					$updateSQL = sprintf("UPDATE pg_comision_empleado SET
						id_tipo_porcentaje = %s,
						venta_bruta = %s,
						monto_descuento = %s,
						costo_compra = %s,
						porcentaje_comision = (%s / (SELECT COUNT(id_comision_empleado) FROM pg_comision_empleado_detalle
													WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado)),
						monto_comision = %s
					WHERE id_comision_empleado = %s;",
						valTpDato($idTipoPorcentaje, "int"),
						valTpDato($arrayComision[0], "real_inglesa"),
						valTpDato($arrayComision[1], "real_inglesa"),
						valTpDato($arrayComision[2], "real_inglesa"),
						valTpDato($arrayComision[3], "real_inglesa"),
						valTpDato($arrayComision[4], "real_inglesa"),
						valTpDato($idComisionEmpleado, "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				}
			}
		}
	}
	
	return array(true, "");
}

function devolverComision($idDocumento, $generarDirecto = true, $mesCierre = "", $anoCierre = "", $fechaDefinida = "") {
	global $conex;
	
	$fechaDefinida = ($fechaDefinida != "") ? valTpDato(date("Y-m-d H:i:s", strtotime($fechaDefinida)), "date") : valTpDato("NOW()", "campo");
	$fechaComision = ($generarDirecto == true) ? $fechaDefinida : valTpDato($anoCierre."-".$mesCierre."-".ultimoDia($mesCierre,$anoCierre)." ".date("H:i:s"), "date");
	
	$queryFact = sprintf("SELECT
		cxc_nc.idNotaCredito,
		cxc_nc.id_empresa,
		cxc_nc.idDocumento,
		cxc_nc.fechaNotaCredito,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.idVendedor,
		cxc_nc.idDepartamentoNotaCredito,
		cxc_nc.subtotalNotaCredito,
		cxc_nc.porcentaje_descuento,
		cxc_nc.subtotal_descuento,
		cxc_nc.estatus_nota_credito,
		
		(IFNULL((SELECT SUM((cantidad * costo_compra)) FROM cj_cc_nota_credito_detalle cxc_nc_det
				WHERE ((SELECT COUNT(com_empleado_det.id_articulo)
						FROM pg_comision_empleado_detalle com_empleado_det
							INNER JOIN pg_comision_empleado com_empleado ON (com_empleado_det.id_comision_empleado = com_empleado.id_comision_empleado)
						WHERE com_empleado.id_factura = cxc_nc.idDocumento
							AND com_empleado_det.id_articulo = cxc_nc_det.id_articulo) > 0)
					AND cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
			+ IFNULL((SELECT SUM(costo_compra) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_veh
					WHERE cxc_nc_det_veh.id_nota_credito = cxc_nc.idNotaCredito), 0)
			+ IFNULL((SELECT SUM(costo_compra) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
					WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)) AS total_costo,
		
		(SELECT SUM(cxc_nc_iva.iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
		WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito) AS porcentajeIvaNotaCredito,
		
		(SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
		WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito) AS total_impuesto,
		
		(SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
			INNER JOIN iv_articulos art ON (cxc_nc_det.id_articulo = art.id_articulo)
		WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito) AS items_repuestos,
		
		(SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito) AS items_vehiculos,
		
		(SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
			INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
		WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito) AS items_accesorios
		
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
	WHERE cxc_nc.tipoDocumento = 'FA'
		AND cxc_nc.idNotaCredito = %s;",
		valTpDato($idDocumento, "int"));
	$rsFact = mysql_query($queryFact, $conex);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	$totalRowsFact = mysql_num_rows($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idFactura = $rowFact['idDocumento'];
	$porcDescuentoFact = $rowFact['porcentaje_descuento'];
	$idModulo = $rowFact['idDepartamentoNotaCredito'];
	$idVendedor = $rowFact['idVendedor'];
	$porcIva = $rowFact['porcentajeIvaNotaCredito'];
	
	if ($rowFact['items_repuestos'] > 0 || $rowFact['items_vehiculos'] > 0 || $rowFact['items_accesorios'] > 0) {
		// 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario; 8 = Facturado
		($rowFact['items_repuestos'] > 0) ? $arrayTipoComision[] = 4 : "";
		($rowFact['items_vehiculos'] > 0) ? $arrayTipoComision[] = 5 : "";
		($rowFact['items_accesorios'] > 0) ? $arrayTipoComision[] = 6 : "";
		$arrayTipoComision[] = 8;
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_comision IN (%s)",
			valTpDato(implode(",",$arrayTipoComision), "campo"));
	} else {
		if ($rowFact['items_repuestos'] == 0 && $rowFact['items_vehiculos'] == 0 && $rowFact['items_accesorios'] == 0 && $idModulo == 0) { // CxC
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision IN (4,8)");
		} else {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision = -1");
		}
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo = %s",
		valTpDato($idModulo, "int"));
	
	// 1 = Por Venta Propia, 2 = Por Venta General, 3 = Por Venta Subordinada
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((modo_comision = 2 AND id_empleado <> %s)
	OR (modo_comision = 1 AND id_empleado = %s))",
		valTpDato($idVendedor, "int"),
		valTpDato($idVendedor, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	if ($generarDirecto == false) {
		switch ($idModulo) {
			// 1 = Simple, 2 = Por Productividad, 3 = Por Rango, 4 = Por Item
			case 1 :	$tipo_porcentaje = "2"; break;
			case 2 :	$tipo_porcentaje = "3,4"; break;
			default :	$tipo_porcentaje = "-1";
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje IN (%s)",
				valTpDato($tipo_porcentaje, "campo"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado = %s",
				valTpDato($idVendedor, "int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje IN (1)");
	}
	
	$queryEmpleado = sprintf("SELECT DISTINCT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cargo_dep.id_cargo_departamento
	FROM pg_empleado empleado
		INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
		LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento) %s
	ORDER BY comision.porcentaje_comision", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado, $conex);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
		$idEmpleado = $rowEmpleado['id_empleado'];
		
		$queryComision = sprintf("SELECT * FROM pg_comision_empleado
		WHERE id_empleado = %s
			AND id_cargo_departamento = %s
			AND id_factura = %s
			AND id_nota_credito = %s;",
			valTpDato($idEmpleado, "int"),
			valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
			valTpDato($idFactura, "int"),
			valTpDato($idDocumento, "int"));
		$rsComision = mysql_query($queryComision);
		if (!$rsComision) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		$totalRowsComision = mysql_num_rows($rsComision);
		$rowComision = mysql_fetch_array($rsComision);
		
		$idComisionEmpleado = $rowComision['id_comision_empleado'];
		
		if (!($idComisionEmpleado > 0)) {
			$insertSQL = sprintf("INSERT INTO pg_comision_empleado (id_empleado, id_cargo_departamento, id_factura, id_nota_credito, fecha_comision)
			VALUE (%s, %s, %s, %s, %s);",
				valTpDato($idEmpleado, "int"),
				valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
				valTpDato($idFactura, "int"),
				valTpDato($idDocumento, "int"),
				$fechaComision);
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$idComisionEmpleado = mysql_insert_id();
		}
		
		if ($idComisionEmpleado > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 = $cond.sprintf("id_empleado = %s",
				valTpDato($idEmpleado, "int"));
			
			$query = sprintf("SELECT
				comision.id_comision,
				empleado.id_empleado,
				CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
				porcentaje_comision,
				tipo_porcentaje,
				tipo_importe,
				aplica_iva,
				tipo_comision,
				modo_comision
			FROM pg_empleado empleado
				INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
				LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision) %s %s
			ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
			$rs = mysql_query($query, $conex);
			if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$arrayComision = NULL;
			while ($row = mysql_fetch_assoc($rs)) {
				$idTipoPorcentaje = $row['tipo_porcentaje'];
				
				if (($generarDirecto == true && $idTipoPorcentaje != 2)
				|| ($generarDirecto == false && in_array($idTipoPorcentaje,array(2,3)))) { // 1 = Simple, 2 = Por Productividad, 3 = Por Rango, 4 = Por Item
					$totalRowsFactDet = NULL;
					
					if ($idTipoPorcentaje == 2) { // 2 = Productividad
						// TOTAL UT MENSUAL
						$queryMensualUT = sprintf("SELECT
							iv_cierre_mensual_facturacion.id_cierre_mensual,
							id_cierre_mensual_facturacion,
							id_empleado,
							total_ut_fisica,
							total_ut
						FROM iv_cierre_mensual_facturacion
							INNER JOIN iv_cierre_mensual ON (iv_cierre_mensual_facturacion.id_cierre_mensual = iv_cierre_mensual.id_cierre_mensual)
						WHERE id_empleado = %s AND mes = %s AND ano = %s AND id_empresa = %s AND total_ut_fisica > %s",
							valTpDato($idEmpleado, "int"),
							valTpDato(date("m",strtotime($rowFact['fechaRegistroFactura'])), "text"),
							valTpDato(date("Y",strtotime($rowFact['fechaRegistroFactura'])), "text"),
							valTpDato($idEmpresa, "int"),
							valTpDato(0, "int"));
						$ResultMensualUT = mysql_query($queryMensualUT); 
						if (!$ResultMensualUT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$rowMensualUT = mysql_fetch_array($ResultMensualUT);
						
						$horasFacturadas = $rowMensualUT['total_ut'];
						$horasFisicas = $rowMensualUT['total_ut_fisica'];
						
						$porcentajeProductividad = round(($horasFacturadas / $horasFisicas) * 100, 0); // PORCENTAJE DE PRODUCTIVIDAD
						
						//SQL NIVEL DE PRODUCTIVIDAD
						$queryNivel = sprintf("SELECT * FROM pg_comision_productividad
						WHERE id_comision = %s
							AND %s BETWEEN mayor AND menor OR (%s > mayor AND menor = 0);", 
							valTpDato($row['id_comision'], "int"), 
							valTpDato($porcentajeProductividad, "real_inglesa"),
							valTpDato($porcentajeProductividad, "real_inglesa"));
						$ResultNivel = mysql_query($queryNivel);
						if (!$ResultNivel) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$rowNivelProd = mysql_fetch_array($ResultNivel);
						
						$porcComision = $rowNivelProd['porcentaje'];
					} else {
						$porcComision = $row['porcentaje_comision'];
					}
					
					if ($rowFact['items_repuestos'] == 0 && $rowFact['items_vehiculos'] == 0 && $rowFact['items_accesorios'] == 0) {
						$cantidad = 1;
						$precioUnitario = $rowFact['subtotalNotaCredito'];
						$costoUnitario = 0;
						$porcIva = $rowFact['porcentajeIvaNotaCredito'];
						
						$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
						$baseComision = floatval($precioUnitario - $descuento);
						$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						
						$montoComision = floatval(($porcComision * $baseComision) / 100);
						
						$arrayComision[0] += $cantidad * $precioUnitario;
						$arrayComision[1] += $cantidad * $descuento;
						$arrayComision[2] += $cantidad * $costoUnitario;
						$arrayComision[3] += $porcComision;
						$arrayComision[4] += $cantidad * $montoComision;
					}
					
					if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
						$queryDet = sprintf("SELECT *,
							(SELECT SUM(cxc_nc_det_imp.impuesto) FROM cj_cc_nota_credito_detalle_impuesto cxc_nc_det_imp
							WHERE cxc_nc_det_imp.id_nota_credito_detalle = cxc_nc_det.id_nota_credito_detalle) AS porcentaje_impuesto
						FROM cj_cc_nota_credito_detalle cxc_nc_det
							INNER JOIN iv_articulos art ON (cxc_nc_det.id_articulo = art.id_articulo)
						WHERE (art.genera_comision = 1
							OR (SELECT COUNT(com_empleado_det.id_articulo)
								FROM pg_comision_empleado_detalle com_empleado_det
									INNER JOIN pg_comision_empleado com_empleado ON (com_empleado_det.id_comision_empleado = com_empleado.id_comision_empleado)
								WHERE com_empleado.id_factura = %s
									AND com_empleado_det.id_articulo = cxc_nc_det.id_articulo) > 0)
							AND cxc_nc_det.id_nota_credito = %s;",
							valTpDato($idFactura, "int"),
							valTpDato($idDocumento, "int"));
						$rsDet = mysql_query($queryDet, $conex);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$totalRowsDet = mysql_num_rows($rsDet);
						while ($rowDet = mysql_fetch_assoc($rsDet)) {
							$cantidad = $rowDet['cantidad'];
							$precioUnitario = $rowDet['precio_unitario'];
							$costoUnitario = $rowDet['costo_compra'];
							$porcIva = $rowDet['porcentaje_impuesto'];
							
							$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
							$montoComision = floatval(($porcComision * $baseComision) / 100);
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_articulo, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($rowDet['id_articulo'], "int"),
								valTpDato($rowDet['cantidad'], "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += $cantidad * $descuento;
							$arrayComision[2] += $cantidad * $costoUnitario;
							$arrayComision[3] += $porcComision;
							$arrayComision[4] += $cantidad * $montoComision;
						}
					}
					
					if ($row['tipo_comision'] == 5 && $rowFact['items_vehiculos'] > 0) {
						$queryDet = sprintf("SELECT *,
							(SELECT SUM(cxc_nc_det_veh_imp.impuesto) FROM cj_cc_nota_credito_detalle_vehiculo_impuesto cxc_nc_det_veh_imp
							WHERE cxc_nc_det_veh_imp.id_nota_credito_detalle_vehiculo = cxc_nc_det_veh.id_nota_credito_detalle_vehiculo) AS porcentaje_impuesto
						FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_veh
						WHERE cxc_nc_det_veh.id_nota_credito = %s;",
							valTpDato($idDocumento, "int"));
						$rsDet = mysql_query($queryDet, $conex);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$totalRowsDet = mysql_num_rows($rsDet);
						while ($rowDet = mysql_fetch_assoc($rsDet)) {
							$cantidad = 1;
							$precioUnitario = $rowDet['precio_unitario'];
							$costoUnitario = $rowDet['costo_compra'];
							$porcIva = $rowDet['porcentaje_impuesto'];
							
							$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
							$montoComision = floatval(($porcComision * $baseComision) / 100);
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_unidad_fisica, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($rowDet['id_unidad_fisica'], "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += $cantidad * $descuento;
							$arrayComision[2] += $cantidad * $costoUnitario;
							$arrayComision[3] += $porcComision;
							$arrayComision[4] += $cantidad * $montoComision;
						}
					}
					
					if ($row['tipo_comision'] == 6 && $rowFact['items_accesorios'] > 0) {
						$queryDet = sprintf("SELECT * FROM an_accesorio acc
							INNER JOIN cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc ON (acc.id_accesorio = cxc_nc_det_acc.id_accesorio)
						WHERE acc.genera_comision = 1
							AND cxc_nc_det_acc.id_nota_credito = %s;",
							valTpDato($idDocumento, "int"));
						$rsDet = mysql_query($queryDet, $conex);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$totalRowsDet = mysql_num_rows($rsDet);
						while ($rowDet = mysql_fetch_assoc($rsDet)) {
							$cantidad = 1;
							$precioUnitario = $rowDet['precio_unitario'];
							$costoUnitario = $rowDet['costo_compra'];
							$porcIva = $rowFact['porcentajeIvaFactura'];
							
							$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
							$montoComision = floatval(($porcComision * $baseComision) / 100);
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_accesorio, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($rowDet['id_accesorio'], "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += $cantidad * $descuento;
							$arrayComision[2] += $cantidad * $costoUnitario;
							$arrayComision[3] += $porcComision;
							$arrayComision[4] += $cantidad * $montoComision;
						}
					}
					
					$updateSQL = sprintf("UPDATE pg_comision_empleado SET
						id_tipo_porcentaje = %s,
						venta_bruta = %s,
						monto_descuento = %s,
						costo_compra = %s,
						porcentaje_comision = (%s / (SELECT COUNT(id_comision_empleado) FROM pg_comision_empleado_detalle
													WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado)),
						monto_comision = %s
					WHERE id_comision_empleado = %s;",
						valTpDato($idTipoPorcentaje, "int"),
						valTpDato($arrayComision[0], "real_inglesa"),
						valTpDato($arrayComision[1], "real_inglesa"),
						valTpDato($arrayComision[2], "real_inglesa"),
						valTpDato($arrayComision[3], "real_inglesa"),
						valTpDato($arrayComision[4], "real_inglesa"),
						valTpDato($idComisionEmpleado, "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				}
			}
		}
	}
	
	return array(true, "");
}
?>