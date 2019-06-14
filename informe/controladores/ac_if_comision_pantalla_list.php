<?php

function calcular_comision_factura($idDocumento, $idEmpleado = "") {
	global $conex;
	
	$queryFact = sprintf("SELECT
		fact_vent.id_empresa,
		fact_vent.idVendedor,
		fact_vent.fechaRegistroFactura,
		fact_vent.condicionDePago,
		fact_vent.idDepartamentoOrigenFactura,
		fact_vent.subtotalFactura,
		fact_vent.porcentaje_descuento,
		fact_vent.porcentajeIvaFactura,
		orden.id_tipo_orden,
		
		(SELECT COUNT(idFactura) FROM sa_det_fact_articulo det_fact_art
		WHERE det_fact_art.idFactura = fact_vent.idFactura) AS items_repuestos,
		
		(SELECT COUNT(idFactura) FROM sa_det_fact_tempario det_fact_mo
		WHERE det_fact_mo.idFactura = fact_vent.idFactura) AS items_mo,
		
		(SELECT COUNT(idFactura) FROM sa_det_fact_tot det_fact_tot
		WHERE det_fact_tot.idFactura = fact_vent.idFactura) AS items_tot,
		
		(SELECT COUNT(idFactura) FROM sa_det_fact_notas det_fact_nota
		WHERE det_fact_nota.idFactura = fact_vent.idFactura) AS items_nota
		
	FROM cj_cc_encabezadofactura fact_vent
		LEFT OUTER JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)
	WHERE idFactura = %s;",
		valTpDato($idDocumento, "int"));
	$rsFact = mysql_query($queryFact, $conex);
	if (!$rsFact) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$porcDescuentoFact = $rowFact['porcentaje_descuento'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura'];
	$idVendedor = $rowFact['idVendedor'];
	$porcIva = $rowFact['porcentajeIvaFactura'];
	$idTipoOrden = $rowFact['id_tipo_orden'];
	
	
	if ($rowFact['items_repuestos'] > 0 || $rowFact['items_mo'] > 0 || $rowFact['items_tot'] > 0 || $rowFact['items_nota'] > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		
		if ($rowFact['items_repuestos'] > 0) { // 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario
			$sqlBusq .= $cond.sprintf("tipo_comision = 4");
		}
		
		if ($rowFact['items_mo'] > 0) { // 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario
			$cond = (strlen($sqlBusq) > 0) ? " OR " : $cond;
			$sqlBusq .= $cond.sprintf("tipo_comision = 1");
		}
		
		if ($rowFact['items_tot'] > 0) { // 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario
			$cond = (strlen($sqlBusq) > 0) ? " OR " : $cond;
			$sqlBusq .= $cond.sprintf("tipo_comision = 2");
		}
		
		if ($rowFact['items_nota'] > 0) { // 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario
			$cond = (strlen($sqlBusq) > 0) ? " OR " : $cond;
			$sqlBusq .= $cond.sprintf("tipo_comision = 3");
		}
		$sqlBusq .= ")";
	} else {
		if ($rowFact['items_repuestos'] == 0 && $rowFact['items_mo'] == 0 && $rowFact['items_tot'] == 0 && $rowFact['items_nota'] == 0 && $idModulo == 1) { // CxC
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision = 1");
		} else {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision = -1");
		}
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("comision_emp.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("comision.id_modulo = %s",
		valTpDato($idModulo, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("comision_tipo_orden.id_tipo_orden = %s",
		valTpDato($idTipoOrden, "int"));
	
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((modo_comision = 2 AND id_empleado <> %s)
	OR (modo_comision = 1 AND id_empleado = %s))",
		valTpDato($idVendedor, "int"),
		valTpDato($idVendedor, "int"));*/
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT pago_comision FROM sa_tipo_orden WHERE id_tipo_orden = %s) = 1",
		valTpDato($idTipoOrden, "int"));
	
	if ($idEmpleado != "-1" && $idEmpleado != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("empleado.id_empleado = %s",
			valTpDato($idEmpleado, "int"));
	}
	
	
	$queryEmpleado = sprintf("SELECT DISTINCT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
        cargo_dep.clave_filtro,
		cargo_filtro.nombre_filtro
	FROM pg_empleado empleado
		INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
		LEFT OUTER JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
		LEFT OUTER JOIN pg_cargo_filtro cargo_filtro ON (cargo_dep.clave_filtro = cargo_filtro.filtro)
		LEFT OUTER JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s
	ORDER BY comision.porcentaje_comision", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowEmpleado = mysql_fetch_array($rsEmpleado)) {
		$idEmpleado = $rowEmpleado['id_empleado'];
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 = $cond.sprintf("id_empleado = %s",
			valTpDato($idEmpleado, "int"));
		
		$query = sprintf("SELECT DISTINCT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			porcentaje_comision,
			tipo_importe,
			aplica_iva,
			tipo_comision,
			modo_comision
		FROM pg_empleado empleado
			INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
			LEFT OUTER JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
			LEFT OUTER JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s %s
		ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
		$rs = mysql_query($query, $conex);
		if (!$rs) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$arrayComision = NULL;
		while ($row = mysql_fetch_assoc($rs)) {
			$porcComision = $row['porcentaje_comision'];
			
			$sqlBusq3 = "";
			
			if ($row['tipo_comision'] == 1 && $rowFact['items_mo'] > 0) {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 = $cond.sprintf("fact_vent.idFactura = %s",
					valTpDato($idDocumento, "int"));
				
				switch ($rowEmpleado['clave_filtro']) {
					case 5 : // ASESOR
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("fact_vent.idVendedor = %s",
								valTpDato($idEmpleado, "int"));
						} break;
					case 6 : // JEFE DE TALLER
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																					WHERE mecanico.id_empleado = %s)",
								valTpDato($idEmpleado, "int"));
						} else if ($row['modo_comision'] == 3) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("equipo_mecanico.id_empleado_jefe_taller = %s
							AND det_fact_temp.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																WHERE mecanico.id_empleado = %s)",
								valTpDato($idEmpleado, "int"),
								valTpDato($idEmpleado, "int"));
						} break;
					case 501 : // MECANICO SERVICIOS
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																					WHERE mecanico.id_empleado = %s)",
								valTpDato($idEmpleado, "int"));
						} break;
				}
				
				$queryDet = sprintf("SELECT *
				FROM sa_det_fact_tempario det_fact_temp
					INNER JOIN sa_mecanicos mecanico ON (det_fact_temp.id_mecanico = mecanico.id_mecanico)
					INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
					INNER JOIN cj_cc_encabezadofactura fact_vent ON (det_fact_temp.idFactura = fact_vent.idFactura) %s;", $sqlBusq3);
				$rsDet = mysql_query($queryDet, $conex);
				if (!$rsDet) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				$totalRowsDet = mysql_num_rows($rsDet);
				while ($rowDet = mysql_fetch_assoc($rsDet)) {
					$idTempario = $rowDet['id_tempario'];
					$cantidad = 1;
					$costoUnitario = $rowDet['costo'];
					$precioUnitario = $rowDet['precio'];
					$ut = $rowDet['ut'];
					$precioTempario = $rowDet['precio_tempario_tipo_orden'];
					$baseUtPrecio = $rowDet['base_ut_precio'];
					
					if ($row['tipo_importe'] == 1 || $row['tipo_importe'] == 4) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						switch ($rowDet['id_modo']) { // 1 = UT, 2 = Precio
							case 1 : $precioUnitario = $precioTempario * $ut / $baseUtPrecio; break;
							case 2 : $precioUnitario = $precioUnitario; break;
						}
						$montoIva = ($row['aplica_iva'] == 1) ? $precioUnitario * $porcIva / 100 : 0;
						$precioUnitario = $precioUnitario + $montoIva;
						
						$montoComision = $porcComision * $precioUnitario / 100;
					} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$precioUnitario = $costoUnitario;
						$montoIva = ($row['aplica_iva'] == 1) ? $precioUnitario * $porcIva / 100 : 0;
						$precioUnitario = $precioUnitario + $montoIva;
						
						$montoComision = $porcComision * $precioUnitario / 100;
					} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$precioUnitario = $porcComision;
						$montoComision = $precioUnitario;
					}
					
					$arrayComision[0] += $cantidad * $precioUnitario;
					$arrayComision[1] += 0;
					$arrayComision[2] += $cantidad * $costoUnitario;
					$arrayComision[3] += $montoComision;
				}
			}
			
			if ($row['tipo_comision'] == 2 && $rowFact['items_tot'] > 0) {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 = $cond.sprintf("fact_vent.idFactura = %s",
					valTpDato($idDocumento, "int"));
				
				switch ($rowEmpleado['clave_filtro']) {
					case 5 : // ASESOR
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("fact_vent.idVendedor = %s",
								valTpDato($idEmpleado, "int"));
						} break;
					case 6 : // JEFE DE TALLER
						if ($row['modo_comision'] == 3) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("(equipo_mecanico.id_empleado_jefe_taller = %s
							OR equipo_mecanico.id_empleado_jefe_taller IS NULL)",
								valTpDato($idEmpleado, "int"));
						} break;
				}
						
				$queryDet = sprintf("SELECT DISTINCT
					det_fact_tot.id_det_fact_tot,
					orden_tot.monto_subtotal,
					det_fact_tot.porcentaje_tot,
					equipo_mecanico.id_empleado_jefe_taller
				FROM sa_det_fact_tempario det_fact_temp
					INNER JOIN sa_mecanicos mecanico ON (det_fact_temp.id_mecanico = mecanico.id_mecanico)
					INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
					RIGHT OUTER JOIN cj_cc_encabezadofactura fact_vent ON (det_fact_temp.idFactura = fact_vent.idFactura)
					INNER JOIN sa_det_fact_tot det_fact_tot ON (fact_vent.idFactura = det_fact_tot.idFactura)
					INNER JOIN sa_orden_tot orden_tot ON (det_fact_tot.id_orden_tot = orden_tot.id_orden_tot) %s;", $sqlBusq3);
				$rsDet = mysql_query($queryDet);
				if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowDet = mysql_fetch_array($rsDet)) {
					$idDetFacTOT = $rowDet['id_det_fact_tot'];
					$cantidad = 1;
					$costoUnitario = $rowDet['monto_subtotal'];
					$precioUnitario = $rowDet['monto_subtotal'] + ($rowDet['monto_subtotal'] * $rowDet['porcentaje_tot'] / 100);
					
					if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$descuento = 0;
						$baseComision = floatval($precioUnitario - $descuento);
						$montoIva = ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						$baseComision = $baseComision + $montoIva;
					
						$montoComision = $porcComision * $baseComision / 100;
					} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$descuento = 0;
						$baseComision = floatval($costoUnitario - $descuento);
						$montoIva = ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						$baseComision = $baseComision + $montoIva;
					
						$montoComision = $porcComision * $baseComision / 100;
					} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$montoComision = $porcComision;
					}
					
					$arrayComision[0] += $cantidad * $precioUnitario;
					$arrayComision[1] += 0;
					$arrayComision[2] += $cantidad * $costoUnitario;
					$arrayComision[3] += $montoComision;
				}
			}
			
			if ($row['tipo_comision'] == 3 && $rowFact['items_nota'] > 0) {}
			
			if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 = $cond.sprintf("fact_vent.idFactura = %s",
					valTpDato($idDocumento, "int"));
				
				switch ($rowEmpleado['clave_filtro']) {
					case 5 : // ASESOR
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("fact_vent.idVendedor = %s",
								valTpDato($idEmpleado, "int"));
						} break;
					case 6 : // JEFE DE TALLER
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("sol_rep.id_jefe_taller = %s",
								valTpDato($idEmpleado, "int"));
						} break;
					case 8 : // JEFE ALMACEN
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("sol_rep.id_empleado_entrega = %s",
								valTpDato($idEmpleado, "int"));
						} break;
				}
				
				// BUSCA LOS REPUESTOS
				$queryDet = sprintf("SELECT DISTINCT
					det_fact_art.id_articulo,
					det_fact_art.cantidad,
					det_fact_art.precio_unitario,
					det_fact_art.costo,
					fact_vent.porcentaje_descuento
				FROM sa_det_fact_articulo det_fact_art
					INNER JOIN cj_cc_encabezadofactura fact_vent ON (det_fact_art.idFactura = fact_vent.idFactura)
					INNER JOIN sa_solicitud_repuestos sol_rep ON (fact_vent.numeroPedido = sol_rep.id_orden) %s;", $sqlBusq3);
				$rsDet = mysql_query($queryDet);
				if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowDet = mysql_fetch_array($rsDet)) {
					$idArticulo = $rowDet['id_articulo'];
					$cantidad = $rowDet['cantidad'];
					$costoUnitario = $rowDet['costo'];
					$precioUnitario = $rowDet['precio_unitario'];
					$porcDescuentoFact = $rowDet['porcentaje_descuento'];
					
					if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
						$baseComision = floatval($precioUnitario - $descuento);
						$montoIva = ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						$baseComision = $baseComision + $montoIva;
					
						$montoComision = $porcComision * $baseComision / 100;
					} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$descuento = 0;
						$baseComision = floatval($costoUnitario - $descuento);
						$montoIva = ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						$baseComision = $baseComision + $montoIva;
					
						$montoComision = $porcComision * $baseComision / 100;
					} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$montoComision = $porcComision;
					}
					
					$arrayComision[0] += $cantidad * $precioUnitario;
					$arrayComision[1] += $cantidad * $descuento;
					$arrayComision[2] += $cantidad * $costoUnitario;
					$arrayComision[3] += $montoComision;
				}
			}
		}
		
		$arrayComisionDet[0] = $idModulo;
		$arrayComisionDet[1] = "FA";
		$arrayComisionDet[3] = $rowFact['condicionDePago'];
		$arrayComisionDet[4] = $idEmpleado;
		$arrayComisionDet[5] = "NOMBRE CLIENTE";
		$arrayComisionDet[6] = $arrayComision[0]; // VENTA BRUTA
		$arrayComisionDet[7] = $arrayComision[1]; // DESCUENTO
		$arrayComisionDet[8] = $arrayComision[0] - $arrayComision[1]; // VENTA NETA
		$arrayComisionDet[9] = $arrayComision[2]; // COSTO
		$arrayComisionDet[10] = 0; // UTILIDAD
		$arrayComisionDet[11] = 0; // % UTILIDAD
		$arrayComisionDet[13] = "-"; // DIAS DE INV.
		$arrayComisionDet[14] = "0"; // % COMISION
		$arrayComisionDet[15] = $arrayComision[3]; // COMISION
		
		$arrayComisionPantalla = $arrayComisionDet;
	}
	
	return array(true, $arrayComisionPantalla);
}


function calcular_comision_vale_salida($idDocumento, $idEmpleado = ""){
	global $conex;
	
	$queryFact = sprintf("SELECT
		vale_salida.id_empresa,
		vale_salida.id_empleado,
		1 AS id_modulo,
		vale_salida.subtotal,
		vale_salida.descuento AS porcentaje_descuento,
		vale_salida.porcentajeIva,
		orden.id_tipo_orden,
		
		(SELECT COUNT(id_vale_salida) FROM sa_det_vale_salida_articulo det_vale_salida_art
		WHERE det_vale_salida_art.id_vale_salida = vale_salida.id_vale_salida) AS items_repuestos,
		
		(SELECT COUNT(id_vale_salida) FROM sa_det_vale_salida_tempario det_vale_salida_mo
		WHERE det_vale_salida_mo.id_vale_salida = vale_salida.id_vale_salida) AS items_mo,
		
		(SELECT COUNT(id_vale_salida) FROM sa_det_vale_salida_tot det_vale_salida_tot
		WHERE det_vale_salida_tot.id_vale_salida = vale_salida.id_vale_salida) AS items_tot,
		
		(SELECT COUNT(id_vale_salida) FROM sa_det_vale_salida_notas det_vale_salida_nota
		WHERE det_vale_salida_nota.id_vale_salida = vale_salida.id_vale_salida) AS items_nota
		
	FROM sa_vale_salida vale_salida
		LEFT OUTER JOIN sa_orden orden ON (vale_salida.id_orden = orden.id_orden)
	WHERE id_vale_salida = %s;",
		valTpDato($idDocumento, "int"));
	$rsFact = mysql_query($queryFact, $conex);
	if (!$rsFact) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$porcDescuentoFact = $rowFact['porcentaje_descuento'];
	$idModulo = $rowFact['id_modulo'];
	$idVendedor = $rowFact['id_empleado'];
	$porcIva = $rowFact['porcentajeIva'];
	$idTipoOrden = $rowFact['id_tipo_orden'];
	
	
	if ($rowFact['items_repuestos'] > 0 || $rowFact['items_mo'] > 0 || $rowFact['items_tot'] > 0 || $rowFact['items_nota'] > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		
		if ($rowFact['items_repuestos'] > 0) { // 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario
			$sqlBusq .= $cond.sprintf("tipo_comision = 4");
		}
		
		if ($rowFact['items_mo'] > 0) { // 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario
			$cond = (strlen($sqlBusq) > 0) ? " OR " : $cond;
			$sqlBusq .= $cond.sprintf("tipo_comision = 1");
		}
		
		if ($rowFact['items_tot'] > 0) { // 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario
			$cond = (strlen($sqlBusq) > 0) ? " OR " : $cond;
			$sqlBusq .= $cond.sprintf("tipo_comision = 2");
		}
		
		if ($rowFact['items_nota'] > 0) { // 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario
			$cond = (strlen($sqlBusq) > 0) ? " OR " : $cond;
			$sqlBusq .= $cond.sprintf("tipo_comision = 3");
		}
		$sqlBusq .= ")";
	} else {
		if ($rowFact['items_repuestos'] == 0 && $rowFact['items_mo'] == 0 && $rowFact['items_tot'] == 0 && $rowFact['items_nota'] == 0 && $idModulo == 1) { // CxC
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision = 1");
		} else {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision = -1");
		}
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("comision_emp.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("comision.id_modulo = %s",
		valTpDato($idModulo, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("comision_tipo_orden.id_tipo_orden = %s",
		valTpDato($idTipoOrden, "int"));
	
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((modo_comision = 2 AND id_empleado <> %s)
	OR (modo_comision = 1 AND id_empleado = %s))",
		valTpDato($idVendedor, "int"),
		valTpDato($idVendedor, "int"));*/
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT pago_comision FROM sa_tipo_orden WHERE id_tipo_orden = %s) = 1",
		valTpDato($idTipoOrden, "int"));
	
	if ($idEmpleado != "-1" && $idEmpleado != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("empleado.id_empleado = %s",
			valTpDato($idEmpleado, "int"));
	}
	
	
	$queryEmpleado = sprintf("SELECT DISTINCT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
        cargo_dep.clave_filtro,
		cargo_filtro.nombre_filtro
	FROM pg_empleado empleado
		INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
		LEFT OUTER JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
		LEFT OUTER JOIN pg_cargo_filtro cargo_filtro ON (cargo_dep.clave_filtro = cargo_filtro.filtro)
		LEFT OUTER JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s
	ORDER BY comision.porcentaje_comision", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowEmpleado = mysql_fetch_array($rsEmpleado)) {
		$idEmpleado = $rowEmpleado['id_empleado'];
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 = $cond.sprintf("id_empleado = %s",
			valTpDato($idEmpleado, "int"));
		
		$query = sprintf("SELECT DISTINCT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			porcentaje_comision,
			tipo_importe,
			aplica_iva,
			tipo_comision,
			modo_comision
		FROM pg_empleado empleado
			INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
			LEFT OUTER JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
			LEFT OUTER JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s %s
		ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
		$rs = mysql_query($query, $conex);
		if (!$rs) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
		$arrayComision = NULL;
		while ($row = mysql_fetch_assoc($rs)) {
			$porcComision = $row['porcentaje_comision'];
			
			$sqlBusq3 = "";
			
			if ($row['tipo_comision'] == 1 && $rowFact['items_mo'] > 0) {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 = $cond.sprintf("vale_salida.id_vale_salida = %s",
					valTpDato($idDocumento, "int"));
				
				switch ($rowEmpleado['clave_filtro']) {
					case 5 : // ASESOR
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("orden.id_empleado = %s",
								valTpDato($idEmpleado, "int"));
						} break;
					case 6 : // JEFE DE TALLER
						case 6 : // JEFE DE TALLER
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("det_vale_salida_mo.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																							WHERE mecanico.id_empleado = %s)",
								valTpDato($idEmpleado, "int"));
						} else if ($row['modo_comision'] == 3) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("equipo_mecanico.id_empleado_jefe_taller = %s
							AND det_vale_salida_mo.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																WHERE mecanico.id_empleado = %s)",
								valTpDato($idEmpleado, "int"),
								valTpDato($idEmpleado, "int"));
						} break;
					case 501 : // MECANICO SERVICIOS
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("det_vale_salida_mo.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																							WHERE mecanico.id_empleado = %s)",
								valTpDato($idEmpleado, "int"));
						} break;
				}
				
				$queryDet = sprintf("SELECT *
				FROM sa_det_vale_salida_tempario det_vale_salida_mo
					INNER JOIN sa_mecanicos mecanico ON (det_vale_salida_mo.id_mecanico = mecanico.id_mecanico)
					INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
					INNER JOIN sa_vale_salida vale_salida ON (det_vale_salida_mo.id_vale_salida = vale_salida.id_vale_salida)
					LEFT OUTER JOIN sa_orden orden ON (vale_salida.id_orden = orden.id_orden) %s;", $sqlBusq3);
				$rsDet = mysql_query($queryDet, $conex);
				if (!$rsDet) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				$totalRowsDet = mysql_num_rows($rsDet);
				while ($rowDet = mysql_fetch_assoc($rsDet)) {
					$idTempario = $rowDet['id_tempario'];
					$cantidad = 1;
					$costoUnitario = $rowDet['costo'];
					$precioUnitario = $rowDet['precio'];
					$ut = $rowDet['ut'];
					$precioTempario = $rowDet['precio_tempario_tipo_orden'];
					$baseUtPrecio = $rowDet['base_ut_precio'];
					
					if ($row['tipo_importe'] == 1 || $row['tipo_importe'] == 4) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						switch ($rowDet['id_modo']) { // 1 = UT, 2 = Precio
							case 1 : $precioUnitario = $precioTempario * $ut / $baseUtPrecio; break;
							case 2 : $precioUnitario = $precioUnitario; break;
						}
						$montoIva = ($row['aplica_iva'] == 1) ? $precioUnitario * $porcIva / 100 : 0;
						$precioUnitario = $precioUnitario + $montoIva;
						
						$montoComision = $porcComision * $precioUnitario / 100;
					} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$precioUnitario = $costoUnitario;
						$montoIva = ($row['aplica_iva'] == 1) ? $precioUnitario * $porcIva / 100 : 0;
						$precioUnitario = $precioUnitario + $montoIva;
						
						$montoComision = $porcComision * $precioUnitario / 100;
					} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$precioUnitario = $porcComision;
						$montoComision = $precioUnitario;
					}
					
					$arrayComision[0] += $cantidad * $precioUnitario;
					$arrayComision[1] += 0;
					$arrayComision[2] += $cantidad * $costoUnitario;
					$arrayComision[3] += $montoComision;
				}
			}
			
			if ($row['tipo_comision'] == 2 && $rowFact['items_tot'] > 0) {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 = $cond.sprintf("vale_salida.id_vale_salida = %s",
					valTpDato($idDocumento, "int"));
				
				switch ($rowEmpleado['clave_filtro']) {
					case 5 : // ASESOR
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("orden.id_empleado = %s",
								valTpDato($idEmpleado, "int"));
						} break;
					case 6 : // JEFE DE TALLER
						if ($row['modo_comision'] == 3) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("(equipo_mecanico.id_empleado_jefe_taller = %s
							OR equipo_mecanico.id_empleado_jefe_taller IS NULL)",
								valTpDato($idEmpleado, "int"));
						} break;
				}
						
				$queryDet = sprintf("SELECT DISTINCT
					det_vale_salida_tot.id_det_vale_salida_tot,
					orden_tot.monto_subtotal,
					det_vale_salida_tot.porcentaje_tot,
					equipo_mecanico.id_empleado_jefe_taller
				FROM sa_det_vale_salida_tempario det_vale_salida_mo
					INNER JOIN sa_mecanicos mecanico ON (det_vale_salida_mo.id_mecanico = mecanico.id_mecanico)
					INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
					RIGHT OUTER JOIN sa_vale_salida vale_salida ON (det_vale_salida_mo.id_vale_salida = vale_salida.id_vale_salida)
					INNER JOIN sa_det_vale_salida_tot det_vale_salida_tot ON (vale_salida.id_vale_salida = det_vale_salida_tot.id_vale_salida)
					INNER JOIN sa_orden_tot orden_tot ON (det_vale_salida_tot.id_orden_tot = orden_tot.id_orden_tot)
					LEFT OUTER JOIN sa_orden orden ON (vale_salida.id_orden = orden.id_orden) %s;", $sqlBusq3);
				$rsDet = mysql_query($queryDet);
				if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowDet = mysql_fetch_array($rsDet)) {
					$idDetFacTOT = $rowDet['id_det_vale_salida_tot'];
					$cantidad = 1;
					$costoUnitario = $rowDet['monto_subtotal'];
					$precioUnitario = $rowDet['monto_subtotal'] + ($rowDet['monto_subtotal'] * $rowDet['porcentaje_tot'] / 100);
					
					if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$descuento = 0;
						$baseComision = floatval($precioUnitario - $descuento);
						$montoIva = ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						$baseComision = $baseComision + $montoIva;
					
						$montoComision = $porcComision * $baseComision / 100;
					} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$descuento = 0;
						$baseComision = floatval($costoUnitario - $descuento);
						$montoIva = ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						$baseComision = $baseComision + $montoIva;
					
						$montoComision = $porcComision * $baseComision / 100;
					} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$montoComision = $porcComision;
					}
					
					$arrayComision[0] += $cantidad * $precioUnitario;
					$arrayComision[1] += 0;
					$arrayComision[2] += $cantidad * $costoUnitario;
					$arrayComision[3] += $montoComision;
				}
			}
			
			if ($row['tipo_comision'] == 3 && $rowFact['items_nota'] > 0) {}
			
			if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 = $cond.sprintf("vale_salida.id_vale_salida = %s",
					valTpDato($idDocumento, "int"));
				
				switch ($rowEmpleado['clave_filtro']) {
					case 5 : // ASESOR
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("orden.id_empleado = %s",
								valTpDato($idEmpleado, "int"));
						} break;
					case 6 : // JEFE DE TALLER
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("sol_rep.id_jefe_taller = %s",
								valTpDato($idEmpleado, "int"));
						} break;
					case 8 : // JEFE ALMACEN
						if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 .= $cond.sprintf("sol_rep.id_empleado_entrega = %s",
								valTpDato($idEmpleado, "int"));
						} break;
				}
				
				// BUSCA LOS REPUESTOS
				$queryDet = sprintf("SELECT DISTINCT
					det_vale_salida_art.id_articulo,
					det_vale_salida_art.cantidad,
					det_vale_salida_art.precio_unitario,
					det_vale_salida_art.costo,
					vale_salida.descuento AS porcentaje_descuento
				FROM sa_det_vale_salida_articulo det_vale_salida_art
					INNER JOIN sa_vale_salida vale_salida ON (det_vale_salida_art.id_vale_salida = vale_salida.id_vale_salida)
					INNER JOIN sa_solicitud_repuestos sol_rep ON (vale_salida.id_orden = sol_rep.id_orden)
					LEFT OUTER JOIN sa_orden orden ON (vale_salida.id_orden = orden.id_orden) %s;", $sqlBusq3);
				$rsDet = mysql_query($queryDet);
				if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowDet = mysql_fetch_array($rsDet)) {
					$idArticulo = $rowDet['id_articulo'];
					$cantidad = $rowDet['cantidad'];
					$costoUnitario = $rowDet['costo'];
					$precioUnitario = $rowDet['precio_unitario'];
					$porcDescuentoFact = $rowDet['porcentaje_descuento'];
					
					if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
						$baseComision = floatval($precioUnitario - $descuento);
						$montoIva = ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						$baseComision = $baseComision + $montoIva;
					
						$montoComision = $porcComision * $baseComision / 100;
					} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$descuento = 0;
						$baseComision = floatval($costoUnitario - $descuento);
						$montoIva = ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
						$baseComision = $baseComision + $montoIva;
					
						$montoComision = $porcComision * $baseComision / 100;
					} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
						$montoComision = $porcComision;
					}
					
					$arrayComision[0] += $cantidad * $precioUnitario;
					$arrayComision[1] += $cantidad * $descuento;
					$arrayComision[2] += $cantidad * $costoUnitario;
					$arrayComision[3] += $montoComision;
				}
			}
		}
		
		$arrayComisionDet[0] = $idModulo;
		$arrayComisionDet[1] = "VS";
		$arrayComisionDet[3] = $rowFact['condicionDePago'];
		$arrayComisionDet[4] = $idEmpleado;
		$arrayComisionDet[5] = "NOMBRE CLIENTE";
		$arrayComisionDet[6] = $arrayComision[0]; // VENTA BRUTA
		$arrayComisionDet[7] = $arrayComision[1]; // DESCUENTO
		$arrayComisionDet[8] = $arrayComision[0] - $arrayComision[1]; // VENTA NETA
		$arrayComisionDet[9] = $arrayComision[2]; // COSTO
		$arrayComisionDet[10] = 0; // UTILIDAD
		$arrayComisionDet[11] = 0; // % UTILIDAD
		$arrayComisionDet[12] = $rowFact['fechaRegistroFactura']; // FECHA DCTO
		$arrayComisionDet[13] = "-"; // DIAS DE INV.
		$arrayComisionDet[14] = "0"; // % COMISION
		$arrayComisionDet[15] = $arrayComision[3]; // COMISION
		
		$arrayComisionPantalla = $arrayComisionDet;
	}
	
	return array(true, $arrayComisionPantalla);
}

function buscarComision($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$idEmpleado = $frmBuscar['lstEmpleado'];
	$fechaComision = explode("-",$frmBuscar['txtFecha']);
	$idModulo = 1;
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTb .= "<tr class=\"tituloColumna\">";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td colspan=\"3\">"."Folio Factura"."</td>";
		$htmlTb .= "<td>"."Tipo Pago"."</td>";
		$htmlTb .= "<td>"."C.I. / R.I.F."."</td>";
		$htmlTb .= "<td>"."Cliente"."</td>";
		$htmlTb .= "<td>"."Venta Bruta"."</td>";
		$htmlTb .= "<td>"."Descuento"."</td>";
		$htmlTb .= "<td>"."Venta Neta"."</td>";
		$htmlTb .= "<td>"."Costo"."</td>";
		$htmlTb .= "<td>"."Utl. Bruta"."</td>";
		$htmlTb .= "<td>"."%Utl. Bruta"."</td>";
		$htmlTb .= "<td>"."Fecha Dcto."."</td>";
		$htmlTb .= "<td>"."Dias de Inv."."</td>";
		$htmlTb .= "<td>"."% Comisión"."</td>";
		$htmlTb .= "<td>"."Comisión"."</td>";
		$htmlTb .= "<td colspan=\"2\"></td>";
	$htmlTb .= "</tr>";
	
	//// FACTURAS DE SERVICIO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fechaRegistroFactura) = %s
	AND YEAR(fechaRegistroFactura) = %s",
		valTpDato($fechaComision[0],"date"),
		valTpDato($fechaComision[1],"date"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idDepartamentoOrigenFactura = %s",
		valTpDato($idModulo,"int"));
	
	$query = sprintf("SELECT * FROM cj_cc_encabezadofactura %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) $objResponse->alert(mysql_error()."<br><br>Line: ".__LINE__);
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = calcular_comision_factura($row['idFactura'], $idEmpleado);
		if ($Result1[0] != true) $objResponse->alert($Result1[1]);
		
		$arrayComisionPantalla = $Result1[1];
		
		if ($arrayComisionPantalla[15] > 0) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			if ($arrayComisionPantalla[0] == 0) {
				$imgModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>";
			} else if ($arrayComisionPantalla[0] == 1) {
				$imgModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>";
			} else if ($arrayComisionPantalla[0] == 2) {
				$imgModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>";
			}
			
			switch ($arrayComisionPantalla[3]) {
				case "0" :	$tipoPago = "CRÉDITO"; break;
				case "1" :	$tipoPago = "CONTADO"; break;
				default :	$tipoPago = $arrayComisionPantalla[3]; break;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>".$contFila."</td>";
				$htmlTb .= "<td>".$imgModulo."</td>";
				$htmlTb .= "<td width=\"1%\">".$arrayComisionPantalla[1]."</td>";
				$htmlTb .= "<td width=\"5%\">".$row['numeroFactura']."</td>";
				$htmlTb .= "<td align=\"left\" width=\"6%\">".$tipoPago."</td>";
				$htmlTb .= "<td style=\"padding-right:2px\" width=\"7%\">".$arrayComisionPantalla[4]."</td>";
				$htmlTb .= "<td align=\"left\" width=\"20%\">".$arrayComisionPantalla[5]."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[6],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[7],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[8],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[9],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[10],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"6%\">".number_format($arrayComisionPantalla[11],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
				$htmlTb .= "<td width=\"3%\">".$arrayComisionPantalla[13]."</td>";
				$htmlTb .= "<td width=\"4%\">".number_format($arrayComisionPantalla[14],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"6%\">".number_format($arrayComisionPantalla[15],2,'.',',')."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalComision[6] += $arrayComisionPantalla[6];
			$arrayTotalComision[7] += $arrayComisionPantalla[7];
			$arrayTotalComision[8] += $arrayComisionPantalla[8];
			$arrayTotalComision[9] += $arrayComisionPantalla[9];
			$arrayTotalComision[15] += $arrayComisionPantalla[15];
		}
	}
	
	//// VALES DE SALIDA DE SERVICIO
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fecha_vale) = %s
	AND YEAR(fecha_vale) = %s",
		valTpDato($fechaComision[0],"date"),
		valTpDato($fechaComision[1],"date"));
	
	$query = sprintf("SELECT * FROM sa_vale_salida %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) $objResponse->alert(mysql_error()."<br><br>Line: ".__LINE__);
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = calcular_comision_vale_salida($row['id_vale_salida'], $idEmpleado);
		if ($Result1[0] != true) $objResponse->alert($Result1[1]);
		
		$arrayComisionPantalla = $Result1[1];
		
		if ($arrayComisionPantalla[15] > 0) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			if ($arrayComisionPantalla[0] == 0) {
				$imgModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>";
			} else if ($arrayComisionPantalla[0] == 1) {
				$imgModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>";
			} else if ($arrayComisionPantalla[0] == 2) {
				$imgModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>";
			}
			
			switch ($arrayComisionPantalla[3]) {
				case "0" :	$tipoPago = "CRÉDITO"; break;
				case "1" :	$tipoPago = "CONTADO"; break;
				default :	$tipoPago = $arrayComisionPantalla[3]; break;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>".$contFila."</td>";
				$htmlTb .= "<td>".$imgModulo."</td>";
				$htmlTb .= "<td width=\"1%\">".$arrayComisionPantalla[1]."</td>";
				$htmlTb .= "<td width=\"5%\">".$row['numero_vale']."</td>";
				$htmlTb .= "<td align=\"left\" width=\"6%\">".$tipoPago."</td>";
				$htmlTb .= "<td style=\"padding-right:2px\" width=\"7%\">".$arrayComisionPantalla[4]."</td>";
				$htmlTb .= "<td align=\"left\" width=\"20%\">".$arrayComisionPantalla[5]."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[6],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[7],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[8],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[9],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[10],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"6%\">".number_format($arrayComisionPantalla[11],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".date(spanDateFormat, strtotime($row['fecha_vale']))."</td>";
				$htmlTb .= "<td width=\"3%\">".$arrayComisionPantalla[13]."</td>";
				$htmlTb .= "<td width=\"4%\">".number_format($arrayComisionPantalla[14],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"6%\">".number_format($arrayComisionPantalla[15],2,'.',',')."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalComision[6] += $arrayComisionPantalla[6];
			$arrayTotalComision[7] += $arrayComisionPantalla[7];
			$arrayTotalComision[8] += $arrayComisionPantalla[8];
			$arrayTotalComision[9] += $arrayComisionPantalla[9];
			$arrayTotalComision[15] += $arrayComisionPantalla[15];
		}
	}
	
	//// NOTA DE CREDITO DE SERVICIO
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("MONTH(fechaNotaCredito) = %s
	AND YEAR(fechaNotaCredito) = %s",
		valTpDato($fechaComision[0],"date"),
		valTpDato($fechaComision[1],"date"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idDepartamentoNotaCredito = %s",
		valTpDato($idModulo,"int"));
	
	$query = sprintf("SELECT * FROM cj_cc_notacredito %s", $sqlBusq);//return $objResponse->alert($query);
	$rs = mysql_query($query);
	if (!$rs) return mysql_error()."<br><br>Line: ".__LINE__;
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = calcular_comision_factura($row['idDocumento'], $idEmpleado);
		if ($Result1[0] != true) $objResponse->alert($Result1[1]);
		
		$arrayComisionPantalla = $Result1[1];
		
		if ($arrayComisionPantalla[15] > 0) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$clase = "divMsjError";
			
			if ($arrayComisionPantalla[0] == 0) {
				$imgModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>";
			} else if ($arrayComisionPantalla[0] == 1) {
				$imgModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>";
			} else if ($arrayComisionPantalla[0] == 2) {
				$imgModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>";
			}
			
			switch ($arrayComisionPantalla[3]) {
				case "0" :	$tipoPago = "CRÉDITO"; break;
				case "1" :	$tipoPago = "CONTADO"; break;
				default :	$tipoPago = $arrayComisionPantalla[3]; break;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>".$contFila."</td>";
				$htmlTb .= "<td>".$imgModulo."</td>";
				$htmlTb .= "<td width=\"1%\">".$arrayComisionPantalla[1]."</td>";
				$htmlTb .= "<td width=\"5%\">".$row['numeracion_nota_credito']."</td>";
				$htmlTb .= "<td align=\"left\" width=\"6%\">".$tipoPago."</td>";
				$htmlTb .= "<td style=\"padding-right:2px\" width=\"7%\">".$arrayComisionPantalla[4]."</td>";
				$htmlTb .= "<td align=\"left\" width=\"20%\">".$arrayComisionPantalla[5]."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[6],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[7],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[8],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[9],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".number_format($arrayComisionPantalla[10],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"6%\">".number_format($arrayComisionPantalla[11],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"7%\">".date(spanDateFormat, strtotime($row['fechaNotaCredito']))."</td>";
				$htmlTb .= "<td width=\"3%\">".$arrayComisionPantalla[13]."</td>";
				$htmlTb .= "<td width=\"4%\">".number_format($arrayComisionPantalla[14],2,'.',',')."</td>";
				$htmlTb .= "<td width=\"6%\">".number_format($arrayComisionPantalla[15],2,'.',',')."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalComision[6] += $arrayComisionPantalla[6];
			$arrayTotalComision[7] += $arrayComisionPantalla[7];
			$arrayTotalComision[8] += $arrayComisionPantalla[8];
			$arrayTotalComision[9] += $arrayComisionPantalla[9];
			$arrayTotalComision[15] += $arrayComisionPantalla[15];
		}
	}
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
		$htmlTb .= "<td class=\"tituloCampo\" colspan=\"7\">"."Total de Totales:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalComision[6],2)."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalComision[7],2)."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalComision[8],2)."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalComision[9],2)."</td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td>".number_format($arrayTotalComision[15],2)."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaComision","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function cargaLstCargo($idEmpresa = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
		
	$query = sprintf("SELECT DISTINCT
		id_cargo,
		nombre_cargo
	FROM pg_comision comision
		INNER JOIN vw_pg_cargos ON (comision.id_cargo_departamento = vw_pg_cargos.id_cargo_departamento) %s
	ORDER BY nombre_cargo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstCargo\" name=\"lstCargo\" onchange=\"xajax_cargaLstVendedor(this.value)\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_cargo']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_cargo']."\">".utf8_encode($row['nombre_cargo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCargo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstVendedor($idCargo, $selId = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM vw_pg_empleados
	WHERE id_cargo = %s
		AND id_empleado <> 1
	ORDER BY nombre_empleado",
		valTpDato($idCargo,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpleado\" name=\"lstEmpleado\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpleado","innerHTML",$html);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarComision");
$xajax->register(XAJAX_FUNCTION,"cargaLstCargo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
?>