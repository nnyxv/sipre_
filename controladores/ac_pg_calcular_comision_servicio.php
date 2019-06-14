<?php
set_time_limit(0);

function calcular_comision_factura($idDocumento, $generarDirecto = true, $mesCierre = "", $anoCierre = "", $fechaDefinida = "") {
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
		
		(IFNULL((SELECT SUM(costo) FROM sa_det_fact_tempario det_fact_temp
				WHERE det_fact_temp.idFactura = cxc_fact.idFactura), 0)
			+ IFNULL((SELECT DISTINCT SUM(orden_tot.monto_subtotal)
					FROM sa_det_fact_tot det_fact_tot
						INNER JOIN sa_orden_tot orden_tot ON (det_fact_tot.id_orden_tot = orden_tot.id_orden_tot)
					WHERE det_fact_tot.idFactura = cxc_fact.idFactura), 0)
			+ IFNULL((SELECT DISTINCT SUM((det_fact_art.cantidad * det_fact_art.costo)) FROM sa_det_fact_articulo det_fact_art
					WHERE det_fact_art.idFactura = cxc_fact.idFactura), 0)) AS total_costo,
		
		(SELECT SUM(cxc_fact_iva.iva) FROM cj_cc_factura_iva cxc_fact_iva
		WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura) AS porcentajeIvaFactura,
		
		(SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
		WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura) AS total_impuesto,
		
		orden.id_tipo_orden,
		
		(SELECT COUNT(idFactura) FROM sa_det_fact_tempario det_fact_mo
		WHERE det_fact_mo.idFactura = cxc_fact.idFactura) AS items_mo,
		
		(SELECT COUNT(idFactura) FROM sa_det_fact_tot det_fact_tot
		WHERE det_fact_tot.idFactura = cxc_fact.idFactura) AS items_tot,
		
		(SELECT COUNT(idFactura) FROM sa_det_fact_notas det_fact_nota
		WHERE det_fact_nota.idFactura = cxc_fact.idFactura) AS items_nota,
		
		(SELECT COUNT(idFactura) FROM sa_det_fact_articulo det_fact_art
		WHERE det_fact_art.idFactura = cxc_fact.idFactura) AS items_repuestos
		
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden)
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
	$idTipoOrden = $rowFact['id_tipo_orden'];
	
	if ($rowFact['items_mo'] > 0 || $rowFact['items_tot'] > 0 || $rowFact['items_nota'] > 0 || $rowFact['items_repuestos'] > 0) {
		// 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario; 8 = Facturado
		($rowFact['items_mo'] > 0) ? $arrayTipoComision[] = 1 : "";
		($rowFact['items_tot'] > 0) ? $arrayTipoComision[] = 2 : "";
		($rowFact['items_nota'] > 0) ? $arrayTipoComision[] = 3 : "";
		($rowFact['items_repuestos'] > 0) ? $arrayTipoComision[] = 4 : "";
		$arrayTipoComision[] = 8;
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_comision IN (%s)",
			valTpDato(implode(",",$arrayTipoComision), "campo"));
	} else {
		if ($rowFact['items_mo'] == 0 && $rowFact['items_tot'] == 0 && $rowFact['items_nota'] == 0 && $rowFact['items_repuestos'] == 0 && $idModulo == 1) { // CxC
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision IN (1,8)");
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
	$sqlBusq .= $cond.sprintf("comision_tipo_orden.id_tipo_orden IN (SELECT tipo_orden.id_filtro_orden FROM sa_tipo_orden tipo_orden
																	WHERE tipo_orden.id_tipo_orden = %s)",
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
		
	if ($generarDirecto == false){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje = %s",
			valTpDato(2, "int")); // 2 = Productividad
	}
	
	$queryEmpleado = sprintf("SELECT DISTINCT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cargo_dep.id_cargo_departamento,
        cargo_dep.clave_filtro,
		cargo_filtro.nombre_filtro
	FROM pg_empleado empleado
		INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
		LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
		LEFT JOIN pg_cargo_filtro cargo_filtro ON (cargo_dep.clave_filtro = cargo_filtro.filtro)
		LEFT JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s
	ORDER BY comision.porcentaje_comision", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	while ($rowEmpleado = mysql_fetch_array($rsEmpleado)) {
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
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$idComisionEmpleado = mysql_insert_id();
		}
		
		if ($idComisionEmpleado > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 = $cond.sprintf("id_empleado = %s",
				valTpDato($idEmpleado, "int"));
			
			$query = sprintf("SELECT DISTINCT
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
				LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
				LEFT JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s %s
			ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
			$rs = mysql_query($query, $conex);
			if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$arrayComision = NULL;
			while ($row = mysql_fetch_assoc($rs)) {
				$idTipoPorcentaje = $row['tipo_porcentaje'];
				
				if (($generarDirecto == true && $idTipoPorcentaje != 2)
				|| ($generarDirecto == false && $idTipoPorcentaje == 2)) { // 2 = Productividad
					if ($idTipoPorcentaje == 2) { // 2 = Productividad
						// TOTAL UT MENSUAL
						$queryMensualUT = sprintf("SELECT
							cierre_mensual_fact.id_cierre_mensual,
							cierre_mensual_fact.id_cierre_mensual_facturacion,
							cierre_mensual_fact.id_empleado,
							cierre_mensual_fact.total_ut_fisica,
							cierre_mensual_fact.total_ut
						FROM iv_cierre_mensual_facturacion cierre_mensual_fact
							INNER JOIN iv_cierre_mensual cierre_mensual ON (cierre_mensual_fact.id_cierre_mensual = cierre_mensual.id_cierre_mensual)
						WHERE cierre_mensual_fact.id_empleado = %s
							AND cierre_mensual.mes = %s
							AND cierre_mensual.ano = %s
							AND cierre_mensual.id_empresa = %s
							AND cierre_mensual_fact.total_ut_fisica > %s;",
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
						
						// SQL NIVEL DE PRODUCTIVIDAD
						$queryNivel = sprintf("SELECT * FROM pg_comision_productividad
						WHERE id_comision = %s
							AND (%s BETWEEN mayor AND menor
								OR (%s >= mayor AND menor = 0));",
							valTpDato($row['id_comision'], "int"), 
							valTpDato($porcentajeProductividad, "real_inglesa"),
							valTpDato($porcentajeProductividad, "real_inglesa"));
						$ResultNivel = mysql_query($queryNivel);
						if (!$ResultNivel) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$rowNivelProd = mysql_fetch_array($ResultNivel);
						
						$porcComision = $rowNivelProd['porcentaje'];
						
						// ACTUALIZA EL PORCENTAJE DE COMISION ASIGNADO POR PRODUCCION
						$updateSQL = sprintf("UPDATE iv_cierre_mensual_facturacion SET
							porcentaje_comision_productividad = %s
						WHERE id_cierre_mensual_facturacion = %s;",
							valTpDato($porcComision, "real_inglesa"),
							valTpDato($rowMensualUT['id_cierre_mensual_facturacion'], "int"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						mysql_query("SET NAMES 'latin1';");
					} else {
						$porcComision = $row['porcentaje_comision'];
					}
					$porcComision = ($porcComision != "") ? $porcComision : 0;
					
					$sqlBusq3 = "";
					
					if ($row['tipo_comision'] == 1 && $rowFact['items_mo'] > 0) {
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
						$sqlBusq3 = $cond.sprintf("cxc_fact.idFactura = %s",
							valTpDato($idDocumento, "int"));
						
						switch ($rowEmpleado['clave_filtro']) {
							case 5 : // ASESOR
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
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
							case 80 : // COORDINADOR TECNICO
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																							WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 2) {
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																								WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} break;
							case 501 : // MECANICO SERVICIOS
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																							WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 2) {
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																								WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} break;
						}
						
						$queryDet = sprintf("SELECT *
						FROM sa_det_fact_tempario det_fact_temp
							INNER JOIN sa_mecanicos mecanico ON (det_fact_temp.id_mecanico = mecanico.id_mecanico)
							INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
							INNER JOIN cj_cc_encabezadofactura cxc_fact ON (det_fact_temp.idFactura = cxc_fact.idFactura) %s;", $sqlBusq3);
						$rsDet = mysql_query($queryDet, $conex);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$totalRowsDet = mysql_num_rows($rsDet);
						while ($rowDet = mysql_fetch_assoc($rsDet)) {
							$idOrden = $rowDet['numeroPedido'];
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
								
								$montoComision = floatval($porcComision * $precioUnitario / 100);
							} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$precioUnitario = $costoUnitario;
								$montoIva = ($row['aplica_iva'] == 1) ? $precioUnitario * $porcIva / 100 : 0;
								$precioUnitario = $precioUnitario + $montoIva;
								
								$montoComision = floatval($porcComision * $precioUnitario / 100);
							} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$montoComision = floatval($precioUnitario);
							}
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_tempario, cantidad, costo_compra, precio_venta, ut, precio_tempario, base_ut_precio, porcentaje_comision, monto_comision)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($idTempario, "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($ut, "real_inglesa"),
								valTpDato($precioTempario, "real_inglesa"),
								valTpDato($baseUtPrecio, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += 0;
							$arrayComision[2] += $cantidad * $costoUnitario;
						}
					}
					
					if ($row['tipo_comision'] == 2 && $rowFact['items_tot'] > 0) {
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
						$sqlBusq3 = $cond.sprintf("cxc_fact.idFactura = %s",
							valTpDato($idDocumento, "int"));
						
						switch ($rowEmpleado['clave_filtro']) {
							case 1 : // ASESOR VENTAS VEHICULOS
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 2) {
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor <> %s",
										valTpDato($idEmpleado, "int"));
								} break;
							case 2 : // GERENTE VENTAS VEHICULOS
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 2) {
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor <> %s",
										valTpDato($idEmpleado, "int"));
								} break;
							case 5 : // ASESOR
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
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
							MAX(equipo_mecanico.id_empleado_jefe_taller) AS id_empleado_jefe_taller
						FROM sa_det_fact_tempario det_fact_temp
							INNER JOIN sa_mecanicos mecanico ON (det_fact_temp.id_mecanico = mecanico.id_mecanico)
							INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
							RIGHT JOIN cj_cc_encabezadofactura cxc_fact ON (det_fact_temp.idFactura = cxc_fact.idFactura)
							INNER JOIN sa_det_fact_tot det_fact_tot ON (cxc_fact.idFactura = det_fact_tot.idFactura)
							INNER JOIN sa_orden_tot orden_tot ON (det_fact_tot.id_orden_tot = orden_tot.id_orden_tot) %s
						GROUP BY
							det_fact_tot.id_det_fact_tot,
							orden_tot.monto_subtotal,
							det_fact_tot.porcentaje_tot
						#HAVING id_empleado_jefe_taller > 0;", $sqlBusq3);
						$rsDet = mysql_query($queryDet);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__."\n".$queryDet);
						while ($rowDet = mysql_fetch_array($rsDet)) {
							$idDetFacTOT = $rowDet['id_det_fact_tot'];
							$cantidad = 1;
							$costoUnitario = $rowDet['monto_subtotal'];
							$precioUnitario = $rowDet['monto_subtotal'] + ($rowDet['monto_subtotal'] * $rowDet['porcentaje_tot'] / 100);
							
							if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$descuento = 0;
								$baseComision = floatval($precioUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
								$montoComision = floatval($porcComision * $baseComision / 100);
							} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$descuento = 0;
								$baseComision = floatval($costoUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
								$montoComision = floatval($porcComision * $baseComision / 100);
							} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$montoComision = floatval($porcComision);
							}
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_det_fact_tot, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($idDetFacTOT, "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__."\nSQL: ".$insertSQL);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += 0;
							$arrayComision[2] += $cantidad * $costoUnitario;
						}
					}
					
					if ($row['tipo_comision'] == 3 && $rowFact['items_nota'] > 0) {}
					
					if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
						$sqlBusq3 = $cond.sprintf("cxc_fact.idFactura = %s",
							valTpDato($idDocumento, "int"));
						
						switch ($rowEmpleado['clave_filtro']) {
							case 5 : // ASESOR
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
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
							det_fact_art.id_factura_detalle,
							det_fact_art.id_articulo,
							det_fact_art.cantidad,
							(IFNULL(det_fact_art.precio_unitario, 0)
								+ IFNULL(det_fact_art.pmu_unitario, 0)) AS precio_unitario,
							det_fact_art.costo,
							cxc_fact.porcentaje_descuento
						FROM sa_det_fact_articulo det_fact_art
							INNER JOIN cj_cc_encabezadofactura cxc_fact ON (det_fact_art.idFactura = cxc_fact.idFactura)
							INNER JOIN sa_solicitud_repuestos sol_rep ON (cxc_fact.numeroPedido = sol_rep.id_orden) %s;", $sqlBusq3);
						$rsDet = mysql_query($queryDet);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						while ($rowDet = mysql_fetch_array($rsDet)) {
							$idArticulo = $rowDet['id_articulo'];
							$cantidad = $rowDet['cantidad'];
							$costoUnitario = $rowDet['costo'];
							$precioUnitario = $rowDet['precio_unitario'];
							$porcDescuentoFact = $rowDet['porcentaje_descuento'];
							
							if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
								$baseComision = floatval($precioUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
								$montoComision = floatval($porcComision * $baseComision / 100);
							} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$descuento = 0;
								$baseComision = floatval($costoUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
								$montoComision = floatval($porcComision * $baseComision / 100);
							} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$montoComision = floatval($porcComision);
							}
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_articulo, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($idArticulo, "int"),
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
						}
					}
					
					if ($row['tipo_comision'] == 5) { // 5 = Vehículo
					}
					
					if ($row['tipo_comision'] == 6) { // 6 = Accesorio
					}
					
					if ($row['tipo_comision'] == 7) { // 7 = Arbitrario
					}
					
					if ($row['tipo_comision'] == 8) { // 8 = Facturado
						$cantidad = 1;
						$costoUnitario = $rowFact['total_costo'];
						$precioUnitario = $rowFact['subtotalFactura'];
						
						$descuento = floatval($rowFact['descuentoFactura']);
						$baseComision = floatval($precioUnitario - $descuento);
						$baseComision += ($row['aplica_iva'] == 1) ? $rowFact['total_impuesto'] : 0;
						
						$montoComision = floatval($porcComision * $baseComision / 100);
							
						$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
						VALUES (%s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idComisionEmpleado, "int"),
							valTpDato($idTipoPorcentaje, "int"),
							valTpDato($cantidad, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($precioUnitario, "real_inglesa"),
							valTpDato($porcComision, "real_inglesa"),
							valTpDato($montoComision, "real_inglesa"));
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						
						$arrayComision[0] += $rowFact['subtotalFactura'];
						$arrayComision[1] += $rowFact['descuentoFactura'];
						$arrayComision[2] += $rowFact['subtotalFactura'];
					}
				}
			}
			
			$updateSQL = sprintf("UPDATE pg_comision_empleado SET
				id_tipo_porcentaje = %s,
				venta_bruta = %s,
				monto_descuento = %s,
				costo_compra = %s,
				porcentaje_comision = IFNULL((SELECT AVG(porcentaje_comision) FROM pg_comision_empleado_detalle
											WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado), %s),
				monto_comision = IFNULL((SELECT SUM(cantidad * monto_comision) FROM pg_comision_empleado_detalle
										WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado), %s)
			WHERE id_comision_empleado = %s;",
				valTpDato($idTipoPorcentaje, "int"),
				valTpDato($arrayComision[0], "real_inglesa"),
				valTpDato($arrayComision[1], "real_inglesa"),
				valTpDato($arrayComision[2], "real_inglesa"),
				valTpDato($porcComision, "real_inglesa"),
				valTpDato($montoComision, "real_inglesa"),
				valTpDato($idComisionEmpleado, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			
			$deleteSQL = sprintf("DELETE FROM pg_comision_empleado
			WHERE venta_bruta <= 0
				AND id_comision_empleado = %s;",
				valTpDato($idComisionEmpleado, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		}
	}
	
	return array(true, "");
}

function calcular_comision_vale_salida($idDocumento, $generarDirecto = true, $mesCierre = "", $anoCierre = "", $fechaDefinida = "") {
	global $conex;
	
	$fechaDefinida = ($fechaDefinida != "") ? valTpDato(date("Y-m-d H:i:s", strtotime($fechaDefinida)), "date") : valTpDato("NOW()", "campo");
	$fechaComision = ($generarDirecto == true) ? $fechaDefinida : valTpDato($anoCierre."-".$mesCierre."-".ultimoDia($mesCierre,$anoCierre)." ".date("H:i:s"), "date");
	
	$queryFact = sprintf("SELECT
		sa_vs.id_vale_salida,
		sa_vs.id_empresa,
		DATE(sa_vs.fecha_vale) AS fecha_vale,
		sa_vs.id_empleado,
		1 AS id_modulo,
		sa_vs.subtotal AS subtotalFactura,
		IFNULL(((IFNULL(sa_vs.descuento,0) * 100) / IFNULL(sa_vs.subtotal,0)),0) AS porcentaje_descuento,
		sa_vs.descuento AS descuentoFactura,
			
		(IFNULL((SELECT SUM(costo) FROM sa_det_vale_salida_tempario sa_vs_det_mo
				WHERE sa_vs_det_mo.id_vale_salida = sa_vs.id_vale_salida), 0)
			+ IFNULL((SELECT DISTINCT SUM(orden_tot.monto_subtotal)
					FROM sa_det_vale_salida_tot sa_vs_det_tot
						INNER JOIN sa_orden_tot orden_tot ON (sa_vs_det_tot.id_orden_tot = orden_tot.id_orden_tot)
					WHERE sa_vs_det_tot.id_vale_salida = sa_vs.id_vale_salida), 0)
			+ IFNULL((SELECT DISTINCT SUM((sa_vs_det_art.cantidad * sa_vs_det_art.costo)) FROM sa_det_vale_salida_articulo sa_vs_det_art
					WHERE sa_vs_det_art.id_vale_salida = sa_vs.id_vale_salida), 0)) AS total_costo,
		
		IFNULL((SELECT SUM(sa_vs_iva.iva) FROM sa_vale_salida_iva sa_vs_iva
				WHERE sa_vs_iva.id_vale_salida = sa_vs.id_vale_salida),0) AS porcentajeIvaFactura,
		
		IFNULL((SELECT SUM(sa_vs_iva.subtotal_iva) FROM sa_vale_salida_iva sa_vs_iva
				WHERE sa_vs_iva.id_vale_salida = sa_vs.id_vale_salida),0) AS total_impuesto,
		
		orden.id_tipo_orden,
		
		(SELECT COUNT(id_vale_salida) FROM sa_det_vale_salida_tempario sa_vs_det_mo
		WHERE sa_vs_det_mo.id_vale_salida = sa_vs.id_vale_salida) AS items_mo,
		
		(SELECT COUNT(id_vale_salida) FROM sa_det_vale_salida_tot sa_vs_det_tot
		WHERE sa_vs_det_tot.id_vale_salida = sa_vs.id_vale_salida) AS items_tot,
		
		(SELECT COUNT(id_vale_salida) FROM sa_det_vale_salida_notas sa_vs_det_nota
		WHERE sa_vs_det_nota.id_vale_salida = sa_vs.id_vale_salida) AS items_nota,
		
		(SELECT COUNT(id_vale_salida) FROM sa_det_vale_salida_articulo sa_vs_det_art
		WHERE sa_vs_det_art.id_vale_salida = sa_vs.id_vale_salida) AS items_repuestos
		
	FROM sa_vale_salida sa_vs
		LEFT JOIN sa_orden orden ON (sa_vs.id_orden = orden.id_orden)
	WHERE sa_vs.id_vale_salida = %s;",
		valTpDato($idDocumento, "int"));
	$rsFact = mysql_query($queryFact, $conex);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$porcDescuentoFact = $rowFact['porcentaje_descuento'];
	$idModulo = $rowFact['id_modulo'];
	$idVendedor = $rowFact['id_empleado'];
	$porcIva = $rowFact['porcentajeIvaFactura'];
	$idTipoOrden = $rowFact['id_tipo_orden'];
	
	if ($rowFact['items_mo'] > 0 || $rowFact['items_tot'] > 0 || $rowFact['items_nota'] > 0 || $rowFact['items_repuestos'] > 0) {
		// 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario; 8 = Facturado
		($rowFact['items_mo'] > 0) ? $arrayTipoComision[] = 1 : "";
		($rowFact['items_tot'] > 0) ? $arrayTipoComision[] = 2 : "";
		($rowFact['items_nota'] > 0) ? $arrayTipoComision[] = 3 : "";
		($rowFact['items_repuestos'] > 0) ? $arrayTipoComision[] = 4 : "";
		$arrayTipoComision[] = 8;
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_comision IN (%s)",
			valTpDato(implode(",",$arrayTipoComision), "campo"));
	} else {
		if ($rowFact['items_mo'] == 0 && $rowFact['items_tot'] == 0 && $rowFact['items_nota'] == 0 && $rowFact['items_repuestos'] == 0 && $idModulo == 1) { // CxC
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision IN (1,8)");
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
	$sqlBusq .= $cond.sprintf("comision_tipo_orden.id_tipo_orden IN (SELECT tipo_orden.id_filtro_orden FROM sa_tipo_orden tipo_orden
																	WHERE tipo_orden.id_tipo_orden = %s)",
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
		
	if ($generarDirecto == false){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje = %s",
			valTpDato(2, "int")); // 2 = Productividad
	}
	
	$queryEmpleado = sprintf("SELECT DISTINCT
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cargo_dep.id_cargo_departamento,
        cargo_dep.clave_filtro,
		cargo_filtro.nombre_filtro
	FROM pg_empleado empleado
		INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
		LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
		LEFT JOIN pg_cargo_filtro cargo_filtro ON (cargo_dep.clave_filtro = cargo_filtro.filtro)
		LEFT JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s
	ORDER BY comision.porcentaje_comision", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	while ($rowEmpleado = mysql_fetch_array($rsEmpleado)) {
		$idEmpleado = $rowEmpleado['id_empleado'];
		
		$queryComision = sprintf("SELECT * FROM pg_comision_empleado
		WHERE id_empleado = %s
			AND id_cargo_departamento = %s
			AND id_vale_salida = %s;",
			valTpDato($idEmpleado, "int"),
			valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
			valTpDato($idDocumento, "int"));
		$rsComision = mysql_query($queryComision);
		if (!$rsComision) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		$totalRowsComision = mysql_num_rows($rsComision);
		$rowComision = mysql_fetch_array($rsComision);
		
		$idComisionEmpleado = $rowComision['id_comision_empleado'];
		
		if (!($idComisionEmpleado > 0)) {
			$insertSQL = sprintf("INSERT INTO pg_comision_empleado (id_empleado, id_cargo_departamento, id_vale_salida, fecha_comision)
			VALUE (%s, %s, %s, %s);",
				valTpDato($idEmpleado, "int"),
				valTpDato($rowEmpleado['id_cargo_departamento'], "int"),
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
			
			$query = sprintf("SELECT DISTINCT
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
				LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
				LEFT JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s %s
			ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
			$rs = mysql_query($query, $conex);
			if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$arrayComision = NULL;
			while ($row = mysql_fetch_assoc($rs)) {
				$idTipoPorcentaje = $row['tipo_porcentaje'];
				
				if (($generarDirecto == true && $idTipoPorcentaje != 2)
				|| ($generarDirecto == false && $idTipoPorcentaje == 2)) { // 2 = Productividad
					if ($idTipoPorcentaje == 2) { // 2 = Productividad
						// TOTAL UT MENSUAL
						$queryMensualUT = sprintf("SELECT
							cierre_mensual_fact.id_cierre_mensual,
							cierre_mensual_fact.id_cierre_mensual_facturacion,
							cierre_mensual_fact.id_empleado,
							cierre_mensual_fact.total_ut_fisica,
							cierre_mensual_fact.total_ut
						FROM iv_cierre_mensual_facturacion cierre_mensual_fact
							INNER JOIN iv_cierre_mensual cierre_mensual ON (cierre_mensual_fact.id_cierre_mensual = cierre_mensual.id_cierre_mensual)
						WHERE cierre_mensual_fact.id_empleado = %s
							AND cierre_mensual.mes = %s
							AND cierre_mensual.ano = %s
							AND cierre_mensual.id_empresa = %s
							AND cierre_mensual_fact.total_ut_fisica > %s;",
							valTpDato($idEmpleado, "int"),
							valTpDato(date("m",strtotime($rowFact['fecha_vale'])), "text"),
							valTpDato(date("Y",strtotime($rowFact['fecha_vale'])), "text"),
							valTpDato($idEmpresa, "int"),
							valTpDato(0, "int"));
						$ResultMensualUT = mysql_query($queryMensualUT); 
						if (!$ResultMensualUT) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$rowMensualUT = mysql_fetch_array($ResultMensualUT);
						
						$horasFacturadas = $rowMensualUT['total_ut'];
						$horasFisicas = $rowMensualUT['total_ut_fisica'];
						
						$porcentajeProductividad = round(($horasFacturadas / $horasFisicas) * 100, 0); // PORCENTAJE DE PRODUCTIVIDAD
						
						// SQL NIVEL DE PRODUCTIVIDAD
						$queryNivel = sprintf("SELECT * FROM pg_comision_productividad
						WHERE id_comision = %s
							AND (%s BETWEEN mayor AND menor
								OR (%s >= mayor AND menor = 0));",
							valTpDato($row['id_comision'], "int"), 
							valTpDato($porcentajeProductividad, "real_inglesa"),
							valTpDato($porcentajeProductividad, "real_inglesa"));
						$ResultNivel = mysql_query($queryNivel);
						if (!$ResultNivel) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$rowNivelProd = mysql_fetch_array($ResultNivel);
						
						$porcComision = $rowNivelProd['porcentaje'];
						
						// ACTUALIZA EL PORCENTAJE DE COMISION ASIGNADO POR PRODUCCION
						$updateSQL = sprintf("UPDATE iv_cierre_mensual_facturacion SET
							porcentaje_comision_productividad = %s
						WHERE id_cierre_mensual_facturacion = %s;",
							valTpDato($porcComision, "real_inglesa"),
							valTpDato($rowMensualUT['id_cierre_mensual_facturacion'], "int"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						mysql_query("SET NAMES 'latin1';");
					} else {
						$porcComision = $row['porcentaje_comision'];
					}
					$porcComision = ($porcComision != "") ? $porcComision : 0;
					
					$sqlBusq3 = "";
					
					if ($row['tipo_comision'] == 1 && $rowFact['items_mo'] > 0) {
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
						$sqlBusq3 = $cond.sprintf("sa_vs.id_vale_salida = %s",
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
									$sqlBusq3 .= $cond.sprintf("sa_vs_det_mo.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																									WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 3) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("equipo_mecanico.id_empleado_jefe_taller = %s
									AND sa_vs_det_mo.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																		WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"),
										valTpDato($idEmpleado, "int"));
								} break;
							case 80 : // COORDINADOR TECNICO
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("sa_vs_det_mo.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																									WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 2) {
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("sa_vs_det_mo.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																										WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} break;
							case 501 : // MECANICO SERVICIOS
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("sa_vs_det_mo.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																									WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 2) {
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("sa_vs_det_mo.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																										WHERE mecanico.id_empleado = %s)",
										valTpDato($idEmpleado, "int"));
								} break;
						}
						
						$queryDet = sprintf("SELECT *
						FROM sa_det_vale_salida_tempario sa_vs_det_mo
							INNER JOIN sa_mecanicos mecanico ON (sa_vs_det_mo.id_mecanico = mecanico.id_mecanico)
							INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
							INNER JOIN sa_vale_salida sa_vs ON (sa_vs_det_mo.id_vale_salida = sa_vs.id_vale_salida)
							LEFT JOIN sa_orden orden ON (sa_vs.id_orden = orden.id_orden) %s;", $sqlBusq3);
						$rsDet = mysql_query($queryDet, $conex);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						$totalRowsDet = mysql_num_rows($rsDet);
						while ($rowDet = mysql_fetch_assoc($rsDet)) {
							$idOrden = $rowDet['numeroPedido'];
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
								
								$montoComision = floatval($porcComision * $precioUnitario / 100);
							} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$precioUnitario = $costoUnitario;
								$montoIva = ($row['aplica_iva'] == 1) ? $precioUnitario * $porcIva / 100 : 0;
								$precioUnitario = $precioUnitario + $montoIva;
								
								$montoComision = floatval($porcComision * $precioUnitario / 100);
							} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$montoComision = floatval($precioUnitario);
							}
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_tempario, cantidad, costo_compra, precio_venta, ut, precio_tempario, base_ut_precio, porcentaje_comision, monto_comision)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($idTempario, "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($ut, "real_inglesa"),
								valTpDato($precioTempario, "real_inglesa"),
								valTpDato($baseUtPrecio, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += 0;
							$arrayComision[2] += $cantidad * $costoUnitario;
						}
					}
					
					if ($row['tipo_comision'] == 2 && $rowFact['items_tot'] > 0) {
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
						$sqlBusq3 = $cond.sprintf("sa_vs.id_vale_salida = %s",
							valTpDato($idDocumento, "int"));
						
						switch ($rowEmpleado['clave_filtro']) {
							case 1 : // ASESOR VENTAS VEHICULOS
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("orden.id_empleado = %s",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 2) {
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("orden.id_empleado <> %s",
										valTpDato($idEmpleado, "int"));
								} break;
							case 2 : // GERENTE VENTAS VEHICULOS
								if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("orden.id_empleado = %s",
										valTpDato($idEmpleado, "int"));
								} else if ($row['modo_comision'] == 2) {
									$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
									$sqlBusq3 .= $cond.sprintf("orden.id_empleado <> %s",
										valTpDato($idEmpleado, "int"));
								} break;
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
							sa_vs_det_tot.id_det_vale_salida_tot,
							orden_tot.monto_subtotal,
							sa_vs_det_tot.porcentaje_tot,
							MAX(equipo_mecanico.id_empleado_jefe_taller) AS id_empleado_jefe_taller
						FROM sa_det_vale_salida_tempario sa_vs_det_mo
							INNER JOIN sa_mecanicos mecanico ON (sa_vs_det_mo.id_mecanico = mecanico.id_mecanico)
							INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
							RIGHT JOIN sa_vale_salida sa_vs ON (sa_vs_det_mo.id_vale_salida = sa_vs.id_vale_salida)
							INNER JOIN sa_det_vale_salida_tot sa_vs_det_tot ON (sa_vs.id_vale_salida = sa_vs_det_tot.id_vale_salida)
							INNER JOIN sa_orden_tot orden_tot ON (sa_vs_det_tot.id_orden_tot = orden_tot.id_orden_tot)
							LEFT JOIN sa_orden orden ON (sa_vs.id_orden = orden.id_orden) %s
						GROUP BY
							sa_vs_det_tot.id_det_vale_salida_tot,
							orden_tot.monto_subtotal,
							sa_vs_det_tot.porcentaje_tot
						HAVING id_empleado_jefe_taller > 0;", $sqlBusq3);
						$rsDet = mysql_query($queryDet);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						while ($rowDet = mysql_fetch_array($rsDet)) {
							$idDetFacTOT = $rowDet['id_det_vale_salida_tot'];
							$cantidad = 1;
							$costoUnitario = $rowDet['monto_subtotal'];
							$precioUnitario = $rowDet['monto_subtotal'] + ($rowDet['monto_subtotal'] * $rowDet['porcentaje_tot'] / 100);
							
							if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$descuento = 0;
								$baseComision = floatval($precioUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
								$montoComision = floatval($porcComision * $baseComision / 100);
							} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$descuento = 0;
								$baseComision = floatval($costoUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
								$montoComision = floatval($porcComision * $baseComision / 100);
							} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$montoComision = floatval($porcComision);
							}
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_det_fact_tot, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($idDetFacTOT, "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $cantidad * $precioUnitario;
							$arrayComision[1] += 0;
							$arrayComision[2] += $cantidad * $costoUnitario;
						}
					}
					
					if ($row['tipo_comision'] == 3 && $rowFact['items_nota'] > 0) {}
					
					if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
						$sqlBusq3 = $cond.sprintf("sa_vs.id_vale_salida = %s",
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
							sa_vs_det_art.id_det_vale_salida_articulo,
							sa_vs_det_art.id_articulo,
							sa_vs_det_art.cantidad,
							(IFNULL(sa_vs_det_art.precio_unitario, 0)
								+ IFNULL(sa_vs_det_art.pmu_unitario, 0)) AS precio_unitario,
							sa_vs_det_art.costo,
							sa_vs.descuento AS porcentaje_descuento
						FROM sa_det_vale_salida_articulo sa_vs_det_art
							INNER JOIN sa_vale_salida sa_vs ON (sa_vs_det_art.id_vale_salida = sa_vs.id_vale_salida)
							INNER JOIN sa_solicitud_repuestos sol_rep ON (sa_vs.id_orden = sol_rep.id_orden)
							LEFT JOIN sa_orden orden ON (sa_vs.id_orden = orden.id_orden) %s;", $sqlBusq3);
						$rsDet = mysql_query($queryDet);
						if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						while ($rowDet = mysql_fetch_array($rsDet)) {
							$idArticulo = $rowDet['id_articulo'];
							$cantidad = $rowDet['cantidad'];
							$costoUnitario = $rowDet['costo'];
							$precioUnitario = $rowDet['precio_unitario'];
							$porcDescuentoFact = $rowDet['porcentaje_descuento'];
							
							if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
								$baseComision = floatval($precioUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
								$montoComision = floatval($porcComision * $baseComision / 100);
							} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$descuento = 0;
								$baseComision = floatval($costoUnitario - $descuento);
								$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
							
								$montoComision = floatval($porcComision * $baseComision / 100);
							} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
								$montoComision = floatval($porcComision);
							}
							
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_articulo, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($idArticulo, "int"),
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
						}
					}
					
					if ($row['tipo_comision'] == 5) { // 5 = Vehículo
					}
					
					if ($row['tipo_comision'] == 6) { // 6 = Accesorio
					}
					
					if ($row['tipo_comision'] == 7) { // 7 = Arbitrario
					}
					
					if ($row['tipo_comision'] == 8) { // 8 = Facturado
						$cantidad = 1;
						$costoUnitario = $rowFact['total_costo'];
						$precioUnitario = $rowFact['subtotalFactura'];
						
						$descuento = floatval($rowFact['descuentoFactura']);
						$baseComision = floatval($precioUnitario - $descuento);
						$baseComision += ($row['aplica_iva'] == 1) ? $rowFact['total_impuesto'] : 0;
						
						$montoComision = floatval($porcComision * $baseComision / 100);
							
						$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
						VALUES (%s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idComisionEmpleado, "int"),
							valTpDato($idTipoPorcentaje, "int"),
							valTpDato($cantidad, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($precioUnitario, "real_inglesa"),
							valTpDato($porcComision, "real_inglesa"),
							valTpDato($montoComision, "real_inglesa"));
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
						
						$arrayComision[0] += $rowFact['subtotalFactura'];
						$arrayComision[1] += $rowFact['descuentoFactura'];
						$arrayComision[2] += $rowFact['subtotalFactura'];
					}
				}
			}
			
			$updateSQL = sprintf("UPDATE pg_comision_empleado SET
				id_tipo_porcentaje = %s,
				venta_bruta = %s,
				monto_descuento = %s,
				costo_compra = %s,
				porcentaje_comision = IFNULL((SELECT AVG(porcentaje_comision) FROM pg_comision_empleado_detalle
											WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado), %s),
				monto_comision = IFNULL((SELECT SUM(cantidad * monto_comision) FROM pg_comision_empleado_detalle
										WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado), %s)
			WHERE id_comision_empleado = %s;",
				valTpDato($idTipoPorcentaje, "int"),
				valTpDato($arrayComision[0], "real_inglesa"),
				valTpDato($arrayComision[1], "real_inglesa"),
				valTpDato($arrayComision[2], "real_inglesa"),
				valTpDato($porcComision, "real_inglesa"),
				valTpDato($montoComision, "real_inglesa"),
				valTpDato($idComisionEmpleado, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			
			$deleteSQL = sprintf("DELETE FROM pg_comision_empleado
			WHERE venta_bruta <= 0
				AND id_comision_empleado = %s;",
				valTpDato($idComisionEmpleado, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		}
	}
	
	return array(true, "");
}

function devolverComisionNC($idDocumento, $idFactura, $generarDirecto = true, $mesCierre = "", $anoCierre = "", $fechaDefinida = "") {
	global $conex;
	
	$fechaDefinida = ($fechaDefinida != "") ? valTpDato(date("Y-m-d H:i:s", strtotime($fechaDefinida)), "date") : valTpDato("NOW()", "campo");
	$fechaComision = ($generarDirecto == true) ? $fechaDefinida : valTpDato($anoCierre."-".$mesCierre."-".ultimoDia($mesCierre,$anoCierre)." ".date("H:i:s"), "date");
	
	// VERIFICA SI LA NOTA DE CREDITO APLICA A LIBROS
	$query = sprintf("SELECT * FROM cj_cc_notacredito nota_cred
	WHERE nota_cred.idNotaCredito = %s
		AND nota_cred.aplicaLibros = 1;",
		valTpDato($idDocumento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rs);
	
	if ($totalRows > 0) {
		$queryFact = sprintf("SELECT
			cxc_fact.idFactura,
			cxc_fact.id_empresa,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.idVendedor,
			cxc_fact.idDepartamentoOrigenFactura,
			cxc_fact.subtotalFactura,
			cxc_fact.porcentaje_descuento,
			cxc_fact.descuentoFactura,
			
			(IFNULL((SELECT SUM(costo) FROM sa_det_fact_tempario det_fact_temp
					WHERE det_fact_temp.idFactura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT DISTINCT SUM(orden_tot.monto_subtotal)
						FROM sa_det_fact_tot det_fact_tot
							INNER JOIN sa_orden_tot orden_tot ON (det_fact_tot.id_orden_tot = orden_tot.id_orden_tot)
						WHERE det_fact_tot.idFactura = cxc_fact.idFactura), 0)
				+ IFNULL((SELECT DISTINCT SUM((det_fact_art.cantidad * det_fact_art.costo)) FROM sa_det_fact_articulo det_fact_art
						WHERE det_fact_art.idFactura = cxc_fact.idFactura), 0)) AS total_costo,
			
			(SELECT SUM(cxc_fact_iva.iva) FROM cj_cc_factura_iva cxc_fact_iva
			WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura) AS porcentajeIvaFactura,
			
			(SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
			WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura) AS total_impuesto,
			
			orden.id_tipo_orden,
			
			(SELECT COUNT(idFactura) FROM sa_det_fact_tempario det_fact_mo
			WHERE det_fact_mo.idFactura = cxc_fact.idFactura) AS items_mo,
			
			(SELECT COUNT(idFactura) FROM sa_det_fact_tot det_fact_tot
			WHERE det_fact_tot.idFactura = cxc_fact.idFactura) AS items_tot,
			
			(SELECT COUNT(idFactura) FROM sa_det_fact_notas det_fact_nota
			WHERE det_fact_nota.idFactura = cxc_fact.idFactura) AS items_nota,
			
			(SELECT COUNT(idFactura) FROM sa_det_fact_articulo det_fact_art
			WHERE det_fact_art.idFactura = cxc_fact.idFactura) AS items_repuestos
			
		FROM cj_cc_encabezadofactura cxc_fact
			LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden)
		WHERE cxc_fact.idFactura = %s;",
			valTpDato($idFactura, "int"));
		$rsFact = mysql_query($queryFact, $conex);
		if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		$rowFact = mysql_fetch_assoc($rsFact);
		
		$idEmpresa = $rowFact['id_empresa'];
		$porcDescuentoFact = $rowFact['porcentaje_descuento'];
		$idModulo = $rowFact['idDepartamentoOrigenFactura'];
		$idVendedor = $rowFact['idVendedor'];
		$porcIva = $rowFact['porcentajeIvaFactura'];
		$idTipoOrden = $rowFact['id_tipo_orden'];
		
		if ($rowFact['items_mo'] > 0 || $rowFact['items_tot'] > 0 || $rowFact['items_nota'] > 0 || $rowFact['items_repuestos'] > 0) {
			// 1 = M.O; 2 = TOT; 3 = Notas; 4 = Repuestos; 5 = Vehiculo; 6 = Accesorio; 7 = Arbitrario; 8 = Facturado
			($rowFact['items_mo'] > 0) ? $arrayTipoComision[] = 1 : "";
			($rowFact['items_tot'] > 0) ? $arrayTipoComision[] = 2 : "";
			($rowFact['items_nota'] > 0) ? $arrayTipoComision[] = 3 : "";
			($rowFact['items_repuestos'] > 0) ? $arrayTipoComision[] = 4 : "";
			$arrayTipoComision[] = 8;
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_comision IN (%s)",
				valTpDato(implode(",",$arrayTipoComision), "campo"));
		} else {
			if ($rowFact['items_mo'] == 0 && $rowFact['items_tot'] == 0 && $rowFact['items_nota'] == 0 && $rowFact['items_repuestos'] == 0 && $idModulo == 1) { // CxC
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("tipo_comision IN (1,8)");
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
		$sqlBusq .= $cond.sprintf("comision_tipo_orden.id_tipo_orden IN (SELECT tipo_orden.id_filtro_orden FROM sa_tipo_orden tipo_orden
																		WHERE tipo_orden.id_tipo_orden = %s)",
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
		
		if ($generarDirecto == false){
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje = %s",
				valTpDato(2, "int")); // 2 = Productividad
		}
		
		
		$queryEmpleado = sprintf("SELECT DISTINCT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			cargo_dep.id_cargo_departamento,
			cargo_dep.clave_filtro,
			cargo_filtro.nombre_filtro
		FROM pg_empleado empleado
			INNER JOIN pg_comision comision ON (empleado.id_cargo_departamento = comision.id_cargo_departamento)
			LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
			INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
			LEFT JOIN pg_cargo_filtro cargo_filtro ON (cargo_dep.clave_filtro = cargo_filtro.filtro)
			LEFT JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s
		ORDER BY comision.porcentaje_comision", $sqlBusq);
		$rsEmpleado = mysql_query($queryEmpleado);
		if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		while ($rowEmpleado = mysql_fetch_array($rsEmpleado)) {
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
				
				$query = sprintf("SELECT DISTINCT
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
					LEFT JOIN pg_comision_empresa comision_emp ON (comision.id_comision = comision_emp.id_comision)
					LEFT JOIN pg_comision_tipo_orden comision_tipo_orden ON (comision.id_comision = comision_tipo_orden.id_comision) %s %s
				ORDER BY porcentaje_comision", $sqlBusq, $sqlBusq2);
				$rs = mysql_query($query, $conex);
				if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				$arrayComision = NULL;
				while ($row = mysql_fetch_assoc($rs)) {
					$idTipoPorcentaje = $row['tipo_porcentaje'];
					
					if (($generarDirecto == true && $idTipoPorcentaje != 2)
					|| ($generarDirecto == false && $idTipoPorcentaje == 2)) { // 2 = Productividad
						if ($idTipoPorcentaje == 2) { // 2 = Productividad
							// TOTAL UT MENSUAL
							$queryMensualUT = sprintf("SELECT
							cierre_mensual_fact.id_cierre_mensual,
							cierre_mensual_fact.id_cierre_mensual_facturacion,
							cierre_mensual_fact.id_empleado,
							cierre_mensual_fact.total_ut_fisica,
							cierre_mensual_fact.total_ut
						FROM iv_cierre_mensual_facturacion cierre_mensual_fact
							INNER JOIN iv_cierre_mensual cierre_mensual ON (cierre_mensual_fact.id_cierre_mensual = cierre_mensual.id_cierre_mensual)
						WHERE cierre_mensual_fact.id_empleado = %s
							AND cierre_mensual.mes = %s
							AND cierre_mensual.ano = %s
							AND cierre_mensual.id_empresa = %s
							AND cierre_mensual_fact.total_ut_fisica > %s;",
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
							
							// SQL NIVEL DE PRODUCTIVIDAD
							$queryNivel = sprintf("SELECT * FROM pg_comision_productividad
							WHERE id_comision = %s
								AND (%s BETWEEN mayor AND menor
									OR (%s >= mayor AND menor = 0));",
								valTpDato($row['id_comision'], "int"), 
								valTpDato($porcentajeProductividad, "real_inglesa"),
								valTpDato($porcentajeProductividad, "real_inglesa"));
							$ResultNivel = mysql_query($queryNivel);
							if (!$ResultNivel) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$rowNivelProd = mysql_fetch_array($ResultNivel);
							
							$porcComision = $rowNivelProd['porcentaje'];
						
							// ACTUALIZA EL PORCENTAJE DE COMISION ASIGNADO POR PRODUCCION
							$updateSQL = sprintf("UPDATE iv_cierre_mensual_facturacion SET
								porcentaje_comision_productividad = %s
							WHERE id_cierre_mensual_facturacion = %s;",
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($rowMensualUT['id_cierre_mensual_facturacion'], "int"));
							mysql_query("SET NAMES 'utf8'");
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							mysql_query("SET NAMES 'latin1';");
						} else {
							$porcComision = $row['porcentaje_comision'];
						}
						$porcComision = ($porcComision != "") ? $porcComision : 0;
						
						$sqlBusq3 = "";
						
						if ($row['tipo_comision'] == 1 && $rowFact['items_mo'] > 0) {
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 = $cond.sprintf("cxc_fact.idFactura = %s",
								valTpDato($idFactura, "int"));
							
							switch ($rowEmpleado['clave_filtro']) {
								case 5 : // ASESOR
									if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
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
								case 80 : // COORDINADOR TECNICO
									if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																								WHERE mecanico.id_empleado = %s)",
											valTpDato($idEmpleado, "int"));
									} else if ($row['modo_comision'] == 2) {
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																									WHERE mecanico.id_empleado = %s)",
											valTpDato($idEmpleado, "int"));
									} break;
								case 501 : // MECANICO SERVICIOS
									if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																								WHERE mecanico.id_empleado = %s)",
											valTpDato($idEmpleado, "int"));
									} else if ($row['modo_comision'] == 2) {
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("det_fact_temp.id_mecanico NOT IN (SELECT mecanico.id_mecanico FROM sa_mecanicos mecanico
																									WHERE mecanico.id_empleado = %s)",
											valTpDato($idEmpleado, "int"));
									} break;
							}
							
							$queryDet = sprintf("SELECT *
							FROM sa_det_fact_tempario det_fact_temp
								INNER JOIN sa_mecanicos mecanico ON (det_fact_temp.id_mecanico = mecanico.id_mecanico)
								INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
								INNER JOIN cj_cc_encabezadofactura cxc_fact ON (det_fact_temp.idFactura = cxc_fact.idFactura) %s;", $sqlBusq3);
							$rsDet = mysql_query($queryDet, $conex);
							if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							$totalRowsDet = mysql_num_rows($rsDet);
							while ($rowDet = mysql_fetch_assoc($rsDet)) {
								$idOrden = $rowDet['numeroPedido'];
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
									
									$montoComision = floatval($porcComision * $precioUnitario / 100);
								} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
									$precioUnitario = $costoUnitario;
									$montoIva = ($row['aplica_iva'] == 1) ? $precioUnitario * $porcIva / 100 : 0;
									$precioUnitario = $precioUnitario + $montoIva;
									
									$montoComision = floatval($porcComision * $precioUnitario / 100);
								} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
									$montoComision = floatval($precioUnitario);
								}
								
								$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_tempario, cantidad, costo_compra, precio_venta, ut, precio_tempario, base_ut_precio, porcentaje_comision, monto_comision)
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
									valTpDato($idComisionEmpleado, "int"),
									valTpDato($idTipoPorcentaje, "int"),
									valTpDato($idTempario, "int"),
									valTpDato($cantidad, "real_inglesa"),
									valTpDato($costoUnitario, "real_inglesa"),
									valTpDato($precioUnitario, "real_inglesa"),
									valTpDato($ut, "real_inglesa"),
									valTpDato($precioTempario, "real_inglesa"),
									valTpDato($baseUtPrecio, "real_inglesa"),
									valTpDato($porcComision, "real_inglesa"),
									valTpDato($montoComision, "real_inglesa"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
								
								$arrayComision[0] += $cantidad * $precioUnitario;
								$arrayComision[1] += 0;
								$arrayComision[2] += $cantidad * $costoUnitario;
							}
						}
						
						if ($row['tipo_comision'] == 2 && $rowFact['items_tot'] > 0) {
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 = $cond.sprintf("cxc_fact.idFactura = %s",
								valTpDato($idFactura, "int"));
							
							switch ($rowEmpleado['clave_filtro']) {
								case 1 : // ASESOR VENTAS VEHICULOS
									if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
											valTpDato($idEmpleado, "int"));
									} else if ($row['modo_comision'] == 2) {
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor <> %s",
											valTpDato($idEmpleado, "int"));
									} break;
								case 2 : // GERENTE VENTAS VEHICULOS
									if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
											valTpDato($idEmpleado, "int"));
									} else if ($row['modo_comision'] == 2) {
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor <> %s",
											valTpDato($idEmpleado, "int"));
									} break;
								case 5 : // ASESOR
									if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
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
								MAX(equipo_mecanico.id_empleado_jefe_taller) AS id_empleado_jefe_taller
							FROM sa_det_fact_tempario det_fact_temp
								INNER JOIN sa_mecanicos mecanico ON (det_fact_temp.id_mecanico = mecanico.id_mecanico)
								INNER JOIN sa_equipos_mecanicos equipo_mecanico ON (mecanico.id_equipo_mecanico = equipo_mecanico.id_equipo_mecanico)
								RIGHT JOIN cj_cc_encabezadofactura cxc_fact ON (det_fact_temp.idFactura = cxc_fact.idFactura)
								INNER JOIN sa_det_fact_tot det_fact_tot ON (cxc_fact.idFactura = det_fact_tot.idFactura)
								INNER JOIN sa_orden_tot orden_tot ON (det_fact_tot.id_orden_tot = orden_tot.id_orden_tot) %s
							GROUP BY
								det_fact_tot.id_det_fact_tot,
								orden_tot.monto_subtotal,
								det_fact_tot.porcentaje_tot
							HAVING id_empleado_jefe_taller > 0;", $sqlBusq3);
							$rsDet = mysql_query($queryDet);
							if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							while ($rowDet = mysql_fetch_array($rsDet)) {
								$idDetFacTOT = $rowDet['id_det_fact_tot'];
								$cantidad = 1;
								$costoUnitario = $rowDet['monto_subtotal'];
								$precioUnitario = $rowDet['monto_subtotal'] + ($rowDet['monto_subtotal'] * $rowDet['porcentaje_tot'] / 100);
								
								if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
									$descuento = 0;
									$baseComision = floatval($precioUnitario - $descuento);
									$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
								
									$montoComision = floatval($porcComision * $baseComision / 100);
								} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
									$descuento = 0;
									$baseComision = floatval($costoUnitario - $descuento);
									$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
								
									$montoComision = floatval($porcComision * $baseComision / 100);
								} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
									$montoComision = floatval($porcComision);
								}
								
								$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_det_fact_tot, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s);",
									valTpDato($idComisionEmpleado, "int"),
									valTpDato($idTipoPorcentaje, "int"),
									valTpDato($idDetFacTOT, "int"),
									valTpDato($cantidad, "real_inglesa"),
									valTpDato($costoUnitario, "real_inglesa"),
									valTpDato($precioUnitario, "real_inglesa"),
									valTpDato($porcComision, "real_inglesa"),
									valTpDato($montoComision, "real_inglesa"));
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
								
								$arrayComision[0] += $cantidad * $precioUnitario;
								$arrayComision[1] += 0;
								$arrayComision[2] += $cantidad * $costoUnitario;
							}
						}
						
						if ($row['tipo_comision'] == 3 && $rowFact['items_nota'] > 0) {}
						
						if ($row['tipo_comision'] == 4 && $rowFact['items_repuestos'] > 0) {
							$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
							$sqlBusq3 = $cond.sprintf("cxc_fact.idFactura = %s",
								valTpDato($idFactura, "int"));
							
							switch ($rowEmpleado['clave_filtro']) {
								case 5 : // ASESOR
									if ($row['modo_comision'] == 1) { // 1 = Por Venta Propia; 2 = Por Venta General; 3 = Por Venta Subordinada
										$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
										$sqlBusq3 .= $cond.sprintf("cxc_fact.idVendedor = %s",
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
								det_fact_art.id_factura_detalle,
								det_fact_art.id_articulo,
								det_fact_art.cantidad,
								(IFNULL(det_fact_art.precio_unitario, 0)
									+ IFNULL(det_fact_art.pmu_unitario, 0)) AS precio_unitario,
								det_fact_art.costo,
								cxc_fact.porcentaje_descuento
							FROM sa_det_fact_articulo det_fact_art
								INNER JOIN cj_cc_encabezadofactura cxc_fact ON (det_fact_art.idFactura = cxc_fact.idFactura)
								INNER JOIN sa_solicitud_repuestos sol_rep ON (cxc_fact.numeroPedido = sol_rep.id_orden) %s;", $sqlBusq3);
							$rsDet = mysql_query($queryDet);
							if (!$rsDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							while ($rowDet = mysql_fetch_array($rsDet)) {
								$idArticulo = $rowDet['id_articulo'];
								$cantidad = $rowDet['cantidad'];
								$costoUnitario = $rowDet['costo'];
								$precioUnitario = $rowDet['precio_unitario'];
								$porcDescuentoFact = $rowDet['porcentaje_descuento'];
								
								if ($row['tipo_importe'] == 1) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
									$descuento = floatval(($porcDescuentoFact * $precioUnitario) / 100);
									$baseComision = floatval($precioUnitario - $descuento);
									$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
								
									$montoComision = floatval($porcComision * $baseComision / 100);
								} else if ($row['tipo_importe'] == 2) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
									$descuento = 0;
									$baseComision = floatval($costoUnitario - $descuento);
									$baseComision += ($row['aplica_iva'] == 1) ? $baseComision * $porcIva / 100 : 0;
								
									$montoComision = floatval($porcComision * $baseComision / 100);
								} else if ($row['tipo_importe'] == 3) { // 1 = Precio, 2 = Costo, 3 = Monto Fijo, 4 = UT
									$montoComision = floatval($porcComision);
								}
								
								$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, id_articulo, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
								VALUES (%s, %s, %s, %s, %s, %s, %s, %s);",
									valTpDato($idComisionEmpleado, "int"),
									valTpDato($idTipoPorcentaje, "int"),
									valTpDato($idArticulo, "int"),
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
							}
						}
						
						if ($row['tipo_comision'] == 5) { // 5 = Vehículo
						}
						
						if ($row['tipo_comision'] == 6) { // 6 = Accesorio
						}
						
						if ($row['tipo_comision'] == 7) { // 7 = Arbitrario
						}
						
						if ($row['tipo_comision'] == 8) { // 8 = Facturado
							$cantidad = 1;
							$costoUnitario = $rowFact['total_costo'];
							$precioUnitario = $rowFact['subtotalFactura'];
							
							$descuento = floatval($rowFact['descuentoFactura']);
							$baseComision = floatval($precioUnitario - $descuento);
							$baseComision += ($row['aplica_iva'] == 1) ? $rowFact['total_impuesto'] : 0;
							
							$montoComision = floatval($porcComision * $baseComision / 100);
								
							$insertSQL = sprintf("INSERT INTO pg_comision_empleado_detalle (id_comision_empleado, id_tipo_porcentaje, cantidad, costo_compra, precio_venta, porcentaje_comision, monto_comision)
							VALUES (%s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idComisionEmpleado, "int"),
								valTpDato($idTipoPorcentaje, "int"),
								valTpDato($cantidad, "real_inglesa"),
								valTpDato($costoUnitario, "real_inglesa"),
								valTpDato($precioUnitario, "real_inglesa"),
								valTpDato($porcComision, "real_inglesa"),
								valTpDato($montoComision, "real_inglesa"));
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
							
							$arrayComision[0] += $rowFact['subtotalFactura'];
							$arrayComision[1] += $rowFact['descuentoFactura'];
							$arrayComision[2] += $rowFact['subtotalFactura'];
						}
					}
				}
				
				$updateSQL = sprintf("UPDATE pg_comision_empleado SET
					id_tipo_porcentaje = %s,
					venta_bruta = %s,
					monto_descuento = %s,
					costo_compra = %s,
					porcentaje_comision = IFNULL((SELECT AVG(porcentaje_comision) FROM pg_comision_empleado_detalle
												WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado), %s),
					monto_comision = IFNULL((SELECT SUM(cantidad * monto_comision) FROM pg_comision_empleado_detalle
											WHERE id_comision_empleado = pg_comision_empleado.id_comision_empleado), %s)
				WHERE id_comision_empleado = %s;",
					valTpDato($idTipoPorcentaje, "int"),
					valTpDato($arrayComision[0], "real_inglesa"),
					valTpDato($arrayComision[1], "real_inglesa"),
					valTpDato($arrayComision[2], "real_inglesa"),
					valTpDato($porcComision, "real_inglesa"),
					valTpDato($montoComision, "real_inglesa"),
					valTpDato($idComisionEmpleado, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
				
				$deleteSQL = sprintf("DELETE FROM pg_comision_empleado
				WHERE venta_bruta <= 0
					AND id_comision_empleado = %s;",
					valTpDato($idComisionEmpleado, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			}
		}
	}
	
	return array(true, "");
}
?>