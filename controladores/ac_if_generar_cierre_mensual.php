<?php


function cierreOrdenesServicio($idEmpresa, $mesCierre, $anoCierre) {
	global $conex;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (1)
	AND cxc_fact.aplicaLibros = 1
	AND cxc_fact.numeroPedido IS NOT NULL");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("vale_salida.id_orden IS NOT NULL");
	
	$sqlBusq3 = "";
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (1)
	AND cxc_fact.aplicaLibros = 1
	AND cxc_fact.numeroPedido IS NOT NULL
	AND cxc_nc.tipoDocumento = 'FA'");
	
	$sqlBusq4 = "";
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("vale_salida.id_orden IS NOT NULL");
	
	$sqlBusq5 = "";
	
	$sqlBusq6 = "";
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden
	AND cxc_fact.idDepartamentoOrigenFactura IN (1)
	AND cxc_fact.aplicaLibros = 1");
	
	$sqlBusq7 = "";
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden");
	
	$sqlBusq8 = "";
	$cond = (strlen($sqlBusq8) > 0) ? " AND " : " WHERE ";
	$sqlBusq8 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden
	AND cxc_nc.idDepartamentoNotaCredito IN (1)
	AND cxc_fact.aplicaLibros = 1
	AND cxc_nc.tipoDocumento = 'FA'");
	
	$sqlBusq9 = "";
	$cond = (strlen($sqlBusq9) > 0) ? " AND " : " WHERE ";
	$sqlBusq9 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden");
	
	$sqlBusq10 = "";
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(vale_salida.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vale_salida.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("(vale_entrada.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vale_entrada.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("(vale_salida.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vale_salida.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq8) > 0) ? " AND " : " WHERE ";
		$sqlBusq8 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq9) > 0) ? " AND " : " WHERE ";
		$sqlBusq9 .= $cond.sprintf("(vale_entrada.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vale_entrada.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq10) > 0) ? " AND " : " WHERE ";
		$sqlBusq10 .= $cond.sprintf("(tipo_orden.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = tipo_orden.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($mesCierre != "-1" && $mesCierre != ""
	&& $anoCierre != "-1" && $anoCierre != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((MONTH(cxc_fact.fechaRegistroFactura) <= %s
			AND YEAR(cxc_fact.fechaRegistroFactura) = %s)
		OR YEAR(cxc_fact.fechaRegistroFactura) < %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("((MONTH(vale_salida.fecha_vale) <= %s
			AND YEAR(vale_salida.fecha_vale) = %s)
		OR YEAR(vale_salida.fecha_vale) < %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("((MONTH(cxc_nc.fechaNotaCredito) <= %s
			AND YEAR(cxc_nc.fechaNotaCredito) = %s)
		OR YEAR(cxc_nc.fechaNotaCredito) < %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("((MONTH(vale_entrada.fecha_creada) <= %s
			AND YEAR(vale_entrada.fecha_creada) = %s)
		OR YEAR(vale_entrada.fecha_creada) < %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " AND ";
		$sqlBusq5 .= $cond.sprintf("((MONTH(orden.tiempo_orden) <= %s
			AND YEAR(orden.tiempo_orden) = %s)
		OR YEAR(orden.tiempo_orden) < %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(MONTH(cxc_fact.fechaRegistroFactura) = %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("(MONTH(vale_salida.fecha_vale) = %s
		AND YEAR(vale_salida.fecha_vale) = %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq8) > 0) ? " AND " : " WHERE ";
		$sqlBusq8 .= $cond.sprintf("(MONTH(cxc_nc.fechaNotaCredito) = %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
		
		$cond = (strlen($sqlBusq9) > 0) ? " AND " : " WHERE ";
		$sqlBusq9 .= $cond.sprintf("(MONTH(vale_entrada.fecha_creada) = %s
		AND YEAR(vale_entrada.fecha_creada) <= %s)",
			valTpDato($mesCierre, "date"),
			valTpDato($anoCierre, "date"));
	}
	
	/*
	UNION
	
	SELECT cxc_fact.numeroPedido
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento) ".$sqlBusq3."
	
	UNION
	
	SELECT vale_salida.id_orden
	FROM sa_vale_entrada vale_entrada
		INNER JOIN sa_vale_salida vale_salida ON (vale_entrada.id_vale_salida = vale_salida.id_vale_salida) ".$sqlBusq4."*/
	
	// ORDENES DE SERVICIOS ABIERTAS Y CERRADAS
	$queryOrdenServ = sprintf("SELECT
		filtro_orden.id_filtro_orden,
		tipo_orden.id_tipo_orden,
		tipo_orden.nombre_tipo_orden,
		
		(IFNULL((SELECT COUNT(orden.id_orden) FROM sa_orden orden
				WHERE orden.id_tipo_orden = tipo_orden.id_tipo_orden
					AND orden.id_orden NOT IN (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact ".$sqlBusq."
												
												UNION
												
												SELECT vale_salida.id_orden FROM sa_vale_salida vale_salida ".$sqlBusq2."
													
												ORDER BY 1) ".$sqlBusq5."), 0)) AS cantidad_abiertas,
		
		(IFNULL((SELECT COUNT(recep_falla.id_recepcion)
				FROM sa_orden orden
					INNER JOIN sa_recepcion recep ON (orden.id_recepcion = recep.id_recepcion)
					INNER JOIN sa_recepcion_falla recep_falla ON (recep_falla.id_recepcion = recep.id_recepcion)
				WHERE orden.id_tipo_orden = tipo_orden.id_tipo_orden
					AND orden.id_orden NOT IN (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact ".$sqlBusq."
												
												UNION
												
												SELECT vale_salida.id_orden FROM sa_vale_salida vale_salida ".$sqlBusq2."
													
												ORDER BY 1) ".$sqlBusq5."), 0)) AS cantidad_fallas_abiertas,
		
		(IFNULL((SELECT COUNT(orden.id_orden)
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden) ".$sqlBusq6."), 0)
			+ IFNULL((SELECT COUNT(orden.id_orden)
					FROM sa_orden orden
						INNER JOIN sa_vale_salida vale_salida ON (orden.id_orden = vale_salida.id_orden) ".$sqlBusq7."), 0)
			- IFNULL((SELECT COUNT(orden.id_orden)
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden)
						INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento) ".$sqlBusq8."), 0)
			- IFNULL((SELECT COUNT(orden.id_orden)
					FROM sa_vale_entrada vale_entrada
						INNER JOIN sa_vale_salida vale_salida ON (vale_entrada.id_vale_salida = vale_salida.id_vale_salida)
						INNER JOIN sa_orden orden ON (vale_salida.id_orden = orden.id_orden) ".$sqlBusq9."), 0)) AS cantidad_cerradas,
		
		(IFNULL((SELECT COUNT(recep_falla.id_recepcion)
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden)
					INNER JOIN sa_recepcion recep ON (orden.id_recepcion = recep.id_recepcion)
					INNER JOIN sa_recepcion_falla recep_falla ON (recep_falla.id_recepcion = recep.id_recepcion) ".$sqlBusq6."), 0)
			+ IFNULL((SELECT COUNT(recep_falla.id_recepcion)
					FROM sa_orden orden
						INNER JOIN sa_vale_salida vale_salida ON (orden.id_orden = vale_salida.id_orden)
						INNER JOIN sa_recepcion recep ON (orden.id_recepcion = recep.id_recepcion)
						INNER JOIN sa_recepcion_falla recep_falla ON (recep_falla.id_recepcion = recep.id_recepcion) ".$sqlBusq7."), 0)
			- IFNULL((SELECT COUNT(recep_falla.id_recepcion)
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden)
						INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
						INNER JOIN sa_recepcion recep ON (orden.id_recepcion = recep.id_recepcion)
						INNER JOIN sa_recepcion_falla recep_falla ON (recep_falla.id_recepcion = recep.id_recepcion) ".$sqlBusq8."), 0)
			- IFNULL((SELECT COUNT(recep_falla.id_recepcion)
					FROM sa_vale_entrada vale_entrada
						INNER JOIN sa_vale_salida vale_salida ON (vale_entrada.id_vale_salida = vale_salida.id_vale_salida)
						INNER JOIN sa_orden orden ON (vale_salida.id_orden = orden.id_orden)
						INNER JOIN sa_recepcion recep ON (orden.id_recepcion = recep.id_recepcion)
						INNER JOIN sa_recepcion_falla recep_falla ON (recep_falla.id_recepcion = recep.id_recepcion) ".$sqlBusq9."), 0)) AS cantidad_fallas_cerradas,
		
		(IFNULL((SELECT
					SUM(IFNULL((CASE a.id_modo
						WHEN 1 THEN -- UT
							a.ut / a.base_ut_precio
						WHEN 2 THEN -- PRECIO
							a.ut / a.base_ut_precio
					END), 0)) AS ut_horas
				FROM cj_cc_encabezadofactura cxc_fact
					INNER JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden)
					INNER JOIN sa_v_informe_final_tempario a ON (orden.id_orden = a.id_orden) ".$sqlBusq6."), 0)
			+ IFNULL((SELECT
						SUM(IFNULL((CASE a.id_modo
							WHEN 1 THEN -- UT
								a.ut / a.base_ut_precio
							WHEN 2 THEN -- PRECIO
								a.ut / a.base_ut_precio
						END), 0)) AS ut_horas
					FROM sa_orden orden
						INNER JOIN sa_vale_salida vale_salida ON (orden.id_orden = vale_salida.id_orden)
						INNER JOIN sa_v_vale_informe_final_tempario a ON (orden.id_orden = a.id_orden) ".$sqlBusq7."), 0)
			- IFNULL((SELECT
						SUM(IFNULL((CASE a.id_modo
							WHEN 1 THEN -- UT
								a.ut / a.base_ut_precio
							WHEN 2 THEN -- PRECIO
								a.ut / a.base_ut_precio
						END), 0)) AS ut_horas
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden)
						INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
						INNER JOIN sa_v_informe_final_tempario_dev a ON (orden.id_orden = a.id_orden) ".$sqlBusq8."), 0)
			- IFNULL((SELECT
						SUM(IFNULL((CASE a.id_modo
							WHEN 1 THEN -- UT
								a.ut / a.base_ut_precio
							WHEN 2 THEN -- PRECIO
								a.ut / a.base_ut_precio
						END), 0)) AS ut_horas
					FROM sa_vale_entrada vale_entrada
						INNER JOIN sa_vale_salida vale_salida ON (vale_entrada.id_vale_salida = vale_salida.id_vale_salida)
						INNER JOIN sa_orden orden ON (vale_salida.id_orden = orden.id_orden)
						INNER JOIN sa_v_vale_informe_final_tempario_dev a ON (orden.id_orden = a.id_orden) ".$sqlBusq9."), 0)) AS cantidad_uts_cerradas
	FROM sa_tipo_orden tipo_orden
		INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
	ORDER BY tipo_orden.nombre_tipo_orden ASC", $sqlBusq10);
	$rsOrdenServ = mysql_query($queryOrdenServ);
	if (!$rsOrdenServ) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalTipoOrdenAbierta, $totalTipoOrdenCerrada);
	while ($rowOrdenServ = mysql_fetch_assoc($rsOrdenServ)) {
		$arrayTipoOrden[$rowOrdenServ['id_tipo_orden']] = array(
			"id_filtro_orden" => $rowOrdenServ['id_filtro_orden'],
			"id_tipo_orden" => $rowOrdenServ['id_tipo_orden'],
			"nombre_tipo_orden" => $rowOrdenServ['nombre_tipo_orden'],
			"cantidad_abiertas" => $rowOrdenServ['cantidad_abiertas'],
			"cantidad_cerradas" => $rowOrdenServ['cantidad_cerradas'],
			"cantidad_fallas_abiertas" => $rowOrdenServ['cantidad_fallas_abiertas'],
			"cantidad_fallas_cerradas" => $rowOrdenServ['cantidad_fallas_cerradas'],
			"cantidad_uts_cerradas" => $rowOrdenServ['cantidad_uts_cerradas']);
		
		$totalTipoOrdenAbierta += $rowOrdenServ['cantidad_abiertas'];
		$totalTipoOrdenCerrada += $rowOrdenServ['cantidad_cerradas'];
		$totalFallaTipoOrdenAbierta += $rowOrdenServ['cantidad_fallas_abiertas'];
		$totalFallaTipoOrdenCerrada += $rowOrdenServ['cantidad_fallas_cerradas'];
		$totalUtsTipoOrdenCerrada += $rowOrdenServ['cantidad_uts_cerradas'];
	}
	
	return array(true, $arrayTipoOrden, $totalTipoOrdenAbierta, $totalTipoOrdenCerrada, $totalFallaTipoOrdenAbierta, $totalFallaTipoOrdenCerrada, $totalUtsTipoOrdenCerrada);
}

function facturacionAsesores($idEmpresa, $mesCierre, $anoCierre) {
	global $conex;
	
	$queryEmpleado = "SELECT
		id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
	FROM pg_empleado empleado
	ORDER BY id_empleado";
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
	while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_empleado = %s
		AND a.aprobado = 1",
			valTpDato($rowEmpleado['id_empleado'], "int"));
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		// SOLO APLICA PARA LAS MANO DE OBRA
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("a.estado_tempario IN ('FACTURADO','TERMINADO')");
		
		// MANO DE OBRAS FACTURAS DE SERVICIOS
		$queryMOFact = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			
			SUM(IFNULL((CASE a.id_modo
				WHEN 1 THEN -- UT
					a.ut / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.ut / a.base_ut_precio
			END), 0)) AS ut_horas,
			
			SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.precio
			END)) AS total_tempario_orden
			
		FROM sa_v_informe_final_tempario a %s %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq, $sqlBusq2);
		$rsMOFact = mysql_query($queryMOFact);
		if (!$rsMOFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsMOFact = mysql_num_rows($rsMOFact);
		
		// MANO DE OBRAS NOTAS DE CREDITO DE SERVICIOS
		$queryMONotaCred = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			
			SUM(IFNULL((CASE a.id_modo
				WHEN 1 THEN -- UT
					a.ut / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.ut / a.base_ut_precio
			END), 0)) AS ut_horas,
			
			SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.precio
			END)) AS total_tempario_orden
			
		FROM sa_v_informe_final_tempario_dev a %s %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq, $sqlBusq2);
		$rsMONotaCred = mysql_query($queryMONotaCred);
		if (!$rsMONotaCred) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsMONotaCred = mysql_num_rows($rsMONotaCred);
		
		// MANO DE OBRAS VALE DE SALIDA DE SERVICIOS
		$queryMOValeSal = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			
			SUM(IFNULL((CASE a.id_modo
				WHEN 1 THEN -- UT
					a.ut / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.ut / a.base_ut_precio
			END), 0)) AS ut_horas,
			
			SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.precio
			END)) AS total_tempario_orden
			
		FROM sa_v_vale_informe_final_tempario a %s %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq, $sqlBusq2);
		$rsMOValeSal = mysql_query($queryMOValeSal);
		if (!$rsMOValeSal) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsMOValeSal = mysql_num_rows($rsMOValeSal);
		
		// MANO DE OBRAS VALE DE ENTRADA DE SERVICIOS
		$queryMOValeEnt = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			
			SUM(IFNULL((CASE a.id_modo
				WHEN 1 THEN -- UT
					a.ut / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.ut / a.base_ut_precio
			END), 0)) AS ut_horas,
			
			SUM((CASE a.id_modo
				WHEN 1 THEN -- UT
					(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
				WHEN 2 THEN -- PRECIO
					a.precio
			END)) AS total_tempario_orden
			
		FROM sa_v_vale_informe_final_tempario_dev a %s %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq, $sqlBusq2);
		$rsMOValeEnt = mysql_query($queryMOValeEnt);
		if (!$rsMOValeEnt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsMOValeEnt = mysql_num_rows($rsMOValeEnt);
		
		
		// REPUESTOS FACTURAS DE SERVICIOS
		$queryRepFact = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			SUM((a.precio_unitario * a.cantidad)) AS total_repuesto_orden,
			SUM(((a.precio_unitario * a.cantidad) * a.porcentaje_descuento_orden) / 100) AS total_descuento_orden
		FROM sa_v_informe_final_repuesto a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
		$rsRepFact = mysql_query($queryRepFact);
		if (!$rsRepFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsRepFact = mysql_num_rows($rsRepFact);
		
		// REPUESTOS NOTAS DE CREDITO DE SERVICIOS
		$queryRepNotaCred = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			SUM((a.precio_unitario * a.cantidad)) AS total_repuesto_orden,
			SUM(((a.precio_unitario * a.cantidad) * a.porcentaje_descuento_orden) / 100) AS total_descuento_orden
		FROM sa_v_informe_final_repuesto_dev a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
		$rsRepNotaCred = mysql_query($queryRepNotaCred);
		if (!$rsRepNotaCred) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsRepNotaCred = mysql_num_rows($rsRepNotaCred);
		
		// REPUESTOS VALE DE SALIDA DE SERVICIOS
		$queryRepValeSal = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			SUM((a.precio_unitario * a.cantidad)) AS total_repuesto_orden,
			SUM(((a.precio_unitario * a.cantidad) * a.porcentaje_descuento_orden) / 100) AS total_descuento_orden
		FROM sa_v_vale_informe_final_repuesto a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
		$rsRepValeSal = mysql_query($queryRepValeSal);
		if (!$rsRepValeSal) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsRepValeSal = mysql_num_rows($rsRepValeSal);
		
		// REPUESTOS VALE DE ENTRADA DE SERVICIOS
		$queryRepValeEnt = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			SUM((a.precio_unitario * a.cantidad)) AS total_repuesto_orden,
			SUM(((a.precio_unitario * a.cantidad) * a.porcentaje_descuento_orden) / 100) AS total_descuento_orden
		FROM sa_v_vale_informe_final_repuesto_dev a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
		$rsRepValeEnt = mysql_query($queryRepValeEnt);
		if (!$rsRepValeEnt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsRepValeEnt = mysql_num_rows($rsRepValeEnt);
		
		
		// TOT FACTURAS DE SERVICIOS
		$queryTotFact = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			SUM(a.monto_total + ((a.porcentaje_tot * a.monto_total) / 100)) AS total_tot_orden
		FROM sa_v_informe_final_tot a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
		$rsTotFact = mysql_query($queryTotFact);
		if (!$rsTotFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsTotFact = mysql_num_rows($rsTotFact);
		
		// TOT NOTAS DE CREDITO DE SERVICIOS
		$queryTotNotaCred = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			SUM(a.monto_total + ((a.porcentaje_tot * a.monto_total) / 100)) AS total_tot_orden
		FROM sa_v_informe_final_tot_dev a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
		$rsTotNotaCred = mysql_query($queryTotNotaCred);
		if (!$rsTotNotaCred) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsTotNotaCred = mysql_num_rows($rsTotNotaCred);
		
		// TOT VALE DE SALIDA DE SERVICIOS
		$queryTotValeSal = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			SUM(a.monto_total + ((a.porcentaje_tot * a.monto_total) / 100)) AS total_tot_orden
		FROM sa_v_vale_informe_final_tot a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
		$rsTotValeSal = mysql_query($queryTotValeSal);
		if (!$rsTotValeSal) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsTotValeSal = mysql_num_rows($rsTotValeSal);
		
		// TOT VALE DE ENTRADA DE SERVICIOS
		$queryTotValeEnt = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			SUM(a.monto_total + ((a.porcentaje_tot * a.monto_total) / 100)) AS total_tot_orden
		FROM sa_v_vale_informe_final_tot_dev a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
		$rsTotValeEnt = mysql_query($queryTotValeEnt);
		if (!$rsTotValeEnt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsTotValeEnt = mysql_num_rows($rsTotValeEnt);
		
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("a.id_empleado = %s",
			valTpDato($rowEmpleado['id_empleado'], "int"));
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		// ORDENES CERRADAS
		$queryOrdenCerrada = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			COUNT(a.id_orden) AS cantidad_ordenes
		FROM sa_v_informe_final_ordenes a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq3);
		$rsOrdenCerrada = mysql_query($queryOrdenCerrada);
		if (!$rsOrdenCerrada) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsOrdenCerrada = mysql_num_rows($rsOrdenCerrada);
		
		// DEVOLUCIONES DE ORDENES
		$queryOrdenDevuelta = sprintf("SELECT
			a.id_empleado,
			a.id_tipo_orden,
			COUNT(a.id_orden) AS cantidad_ordenes
		FROM sa_v_informe_final_ordenes_dev a %s
		GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq3);
		$rsOrdenDevuelta = mysql_query($queryOrdenDevuelta);
		if (!$rsOrdenDevuelta) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
		$totalRowsOrdenDevuelta = mysql_num_rows($rsOrdenDevuelta);
		
		
		if ($totalRowsMOFact > 0 || $totalRowsMONotaCred > 0 || $totalRowsMOValeSal > 0 || $totalRowsMOValeEnt > 0
		|| $totalRowsRepFact > 0 || $totalRowsRepNotaCred > 0 || $totalRowsRepValeSal > 0 || $totalRowsRepValeEnt > 0
		|| $totalRowsTotFact > 0 || $totalRowsTotNotaCred > 0 || $totalRowsTotValeSal > 0 || $totalRowsTotValeEnt > 0
		|| $totalRowsOrdenCerrada > 0 || $totalRowsOrdenDevuelta > 0) {
			$arrayServ = array(
				$rsMOFact, $rsMONotaCred, $rsMOValeSal, $rsMOValeEnt,
				$rsRepFact, $rsRepNotaCred, $rsRepValeSal, $rsRepValeEnt,
				$rsTotFact, $rsTotNotaCred, $rsTotValeSal, $rsTotValeEnt,
				$rsOrdenCerrada, $rsOrdenDevuelta);
			
			foreach ($arrayServ as $indice => $valor) {
				while ($rowServ = mysql_fetch_assoc($valor)) {
					$signo = (in_array($indice, array(1,3,5,7,9,11,13))) ? (-1) : 1;
					
					$existe = false;
					if (isset($arrayVentaAsesor)) {
						foreach ($arrayVentaAsesor as $indice2 => $valor2) {
							if ($arrayVentaAsesor[$indice2]['id_tipo_orden'] == $rowServ['id_tipo_orden']
							&& $arrayVentaAsesor[$indice2]['id_empleado'] == $rowServ['id_empleado']) {
								$existe = true;
								
								$arrayVentaAsesor[$indice2]['cantidad_ordenes'] += $signo * $rowServ['cantidad_ordenes'];
								$arrayVentaAsesor[$indice2]['total_ut'] += $signo * $rowServ['ut_horas'];
								$arrayVentaAsesor[$indice2]['total_mo'] += $signo * $rowServ['total_tempario_orden'];
								$arrayVentaAsesor[$indice2]['total_repuestos'] += $signo * ($rowServ['total_repuesto_orden'] - $rowServ['total_descuento_orden']);
								$arrayVentaAsesor[$indice2]['total_tot'] += $signo * $rowServ['total_tot_orden'];
								$arrayVentaAsesor[$indice2]['total_asesor'] = $arrayVentaAsesor[$indice2]['total_mo']
									+ $arrayVentaAsesor[$indice2]['total_repuestos']
									+ $arrayVentaAsesor[$indice2]['total_tot'];
							}
						}
					}
					
					if ($existe == false) {
						$arrayVentaAsesor[] = array(
							'id_empleado' => $rowEmpleado['id_empleado'],
							'nombre_asesor'=> $rowEmpleado['nombre_empleado'],
							'id_tipo_orden'=> $rowServ['id_tipo_orden'],
							'cantidad_ordenes' => $signo * $rowServ['cantidad_ordenes'],
							'total_ut' => $signo * $rowServ['ut_horas'],
							'total_mo' => $signo * $rowServ['total_tempario_orden'],
							'total_repuestos' => $signo * ($rowServ['total_repuesto_orden'] - $rowServ['total_descuento_orden']),
							'total_tot' => $signo * $rowServ['total_tot_orden'],
							'total_asesor' => ($signo * $rowServ['total_tempario_orden']) + ($signo * $rowServ['total_repuesto_orden']) + ($signo * $rowServ['total_tot_orden']));
					}
					$totalVentaAsesores += ($signo * $rowServ['total_tempario_orden']) +
						($signo * ($rowServ['total_repuesto_orden'] - $rowServ['total_descuento_orden'])) +
						($signo * $rowServ['total_tot_orden']);
				}
			}
		}
	}
	
	return array(true, $arrayVentaAsesor, $totalVentaAsesores);
}

function facturacionMostrador($idEmpresa, $mesCierre, $anoCierre) {
	global $conex;
	
	$queryEmpleado = "SELECT
		id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
	FROM pg_empleado empleado
	ORDER BY nombre_empleado;";
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaVendedores);
	while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor = %s
		AND cxc_fact.idDepartamentoOrigenFactura IN (0)
		AND cxc_fact.aplicaLibros = 1",
			valTpDato($rowEmpleado['id_empleado'], "int"));
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxc_fact.idVendedor = %s
		AND cxc_nc.idDepartamentoNotaCredito IN (0)
		AND cxc_nc.tipoDocumento = 'FA'
		AND cxc_nc.aplicaLibros = 1
		AND cxc_nc.estatus_nota_credito = 2",
			valTpDato($rowEmpleado['id_empleado'], "int"));
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_fact.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_nc.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) = %s
			AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) = %s
			AND YEAR(cxc_nc.fechaNotaCredito) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		// FACTURA DE VENTA
		$query = sprintf("SELECT 
			cxc_fact.idVendedor,
			condicionDePago AS condicion_pago,
			(cxc_fact.subtotalFactura - IFNULL(cxc_fact.descuentoFactura, 0)) AS neto
		FROM cj_cc_encabezadofactura cxc_fact %s;", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaVendedores);
		$totalRowsFact = mysql_num_rows($rs);
		$totalFacturacionContado = 0;
		$totalFacturacionCredito = 0;
		while($row = mysql_fetch_array($rs)) {
			switch ($row['condicion_pago']) {
				case 1 : $totalFacturacionContado += round($row['neto'],2); break;
				case 0 : $totalFacturacionCredito += round($row['neto'],2); break;
			}
		}
	
		// NOTA DE CREDITO
		$query = sprintf("SELECT
			cxc_fact.idVendedor,
			cxc_fact.condicionDePago AS condicion_pago,
			(cxc_nc.subtotalNotaCredito - IFNULL(cxc_nc.subtotal_descuento, 0)) AS neto
		FROM cj_cc_notacredito cxc_nc
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA') %s;", $sqlBusq2);
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaVendedores);
		$totalRowsNotaCred = mysql_num_rows($rs);
		$totalDevolucionContado = 0;
		$totalDevolucionCredito = 0;
		while ($row = mysql_fetch_array($rs)) {
			switch ($row['condicion_pago']) {
				case 1 : $totalDevolucionContado += round($row['neto'],2); break;
				case 0 : $totalDevolucionCredito += round($row['neto'],2); break;
			}
		}
		
		if ($totalRowsFact > 0 || $totalRowsNotaCred > 0) {
			$arrayVentaVendedor[] = array(
				$rowEmpleado['id_empleado'],
				$rowEmpleado['nombre_empleado'],
				$totalFacturacionContado,
				$totalFacturacionCredito,
				$totalDevolucionContado,
				$totalDevolucionCredito,
				$totalFacturacionContado - $totalDevolucionContado, // TOTAL FACTURACION CONTADO
				$totalFacturacionCredito - $totalDevolucionCredito, // TOTAL FACTURACION CREDITO
				($totalFacturacionContado - $totalDevolucionContado) + ($totalFacturacionCredito - $totalDevolucionCredito), // TOTAL FACTURACION CONTADO Y CREDITO
				($totalFacturacionContado - $totalDevolucionContado) + ($totalFacturacionCredito - $totalDevolucionCredito)); // TOTAL FACTURACION REPUESTOS
			
			$totalVentaVendedores += ($totalFacturacionContado - $totalDevolucionContado) + ($totalFacturacionCredito - $totalDevolucionCredito);
		}
	}
	
	return array(true, $arrayVentaVendedor, $totalVentaVendedores);
}

function facturacionMovimiento($idEmpresa, $mesCierre, $anoCierre, $idModulo, $idTipoMovimiento) {
	global $conex;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (%s)
	AND tipo IN (%s)",
		valTpDato($idModulo, "campo"),
		valTpDato($idTipoMovimiento, "campo"));
	
	$queryTipoMov = sprintf("SELECT DISTINCT
		id_clave_movimiento,
		clave,
		descripcion,
		tipo AS id_tipo_movimiento,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento,
		id_modulo
	FROM pg_clave_movimiento %s
	ORDER BY clave, descripcion ASC;", $sqlBusq);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalNetoClaveMovVentas);

	$array = NULL;
	while($rowTipoMov = mysql_fetch_array($rsTipoMov)){
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("mov.id_clave_movimiento = %s",
			valTpDato($rowTipoMov['id_clave_movimiento'], "int"));
			
		if (in_array($rowTipoMov['id_tipo_movimiento'], array(2,4))) {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(
			(CASE
				WHEN (mov.id_tipo_movimiento = 2 AND clave_mov.id_modulo IN (0)) THEN
					(CASE
						WHEN (mov.tipo_documento_movimiento IN (1)) THEN
							(SELECT vale_ent.tipo_vale_entrada AS tipo_vale FROM iv_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = mov.id_documento)
					END)
				WHEN (mov.id_tipo_movimiento = 4 AND clave_mov.id_modulo IN (0)) THEN
					(CASE
						WHEN (mov.tipo_documento_movimiento IN (1)) THEN
							(SELECT vale_sal.tipo_vale_salida AS tipo_vale FROM iv_vale_salida vale_sal WHERE vale_sal.id_vale_salida = mov.id_documento)
					END)
			END) IS NULL
			OR (CASE
				WHEN (mov.id_tipo_movimiento = 2 AND clave_mov.id_modulo IN (0)) THEN
					(CASE
						WHEN (mov.tipo_documento_movimiento IN (1)) THEN
							(SELECT vale_ent.tipo_vale_entrada AS tipo_vale FROM iv_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = mov.id_documento)
					END)
				WHEN (mov.id_tipo_movimiento = 4 AND clave_mov.id_modulo IN (0)) THEN
					(CASE 
						WHEN (mov.tipo_documento_movimiento IN (1)) THEN
							(SELECT vale_sal.tipo_vale_salida AS tipo_vale FROM iv_vale_salida vale_sal WHERE vale_sal.id_vale_salida = mov.id_documento)
					END)
			END) = 3)");
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(
			(CASE
				WHEN (mov.id_tipo_movimiento = 1) THEN
					(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = mov.id_documento)
				WHEN (mov.id_tipo_movimiento = 2) THEN
					(CASE
						WHEN (mov.tipo_documento_movimiento = 1) THEN
							(CASE
								WHEN (clave_mov.id_modulo IN (0)) THEN
									(SELECT vale_ent.id_empresa FROM iv_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = mov.id_documento)
								WHEN (clave_mov.id_modulo IN (1)) THEN
									(SELECT vale_ent.id_empresa FROM sa_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = mov.id_documento)
							END)
						WHEN (mov.tipo_documento_movimiento = 2) THEN
							(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = mov.id_documento)
					END)
				WHEN (mov.id_tipo_movimiento = 3) THEN
					(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = mov.id_documento)
				WHEN (mov.id_tipo_movimiento = 4) THEN
					(CASE
						WHEN (mov.tipo_documento_movimiento = 1) THEN
							(CASE
								WHEN (clave_mov.id_modulo IN (0)) THEN
									(SELECT vale_sal.id_empresa FROM iv_vale_salida vale_sal WHERE vale_sal.id_vale_salida = mov.id_documento)
								WHEN (clave_mov.id_modulo IN (1)) THEN
									(SELECT vale_sal.id_empresa FROM sa_vale_salida vale_sal WHERE vale_sal.id_vale_salida = mov.id_documento)
							END)
						WHEN (mov.tipo_documento_movimiento = 2) THEN
							(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = mov.id_documento)
					END)
			END) = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = (CASE
												WHEN (mov.id_tipo_movimiento = 1) THEN
													(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = mov.id_documento)
												WHEN (mov.id_tipo_movimiento = 2) THEN
													(CASE
														WHEN (mov.tipo_documento_movimiento = 1) THEN
															(CASE
																WHEN (clave_mov.id_modulo IN (0)) THEN
																	(SELECT vale_ent.id_empresa FROM iv_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = mov.id_documento)
																WHEN (clave_mov.id_modulo IN (1)) THEN
																	(SELECT vale_ent.id_empresa FROM sa_vale_entrada vale_ent WHERE vale_ent.id_vale_entrada = mov.id_documento)
															END)
														WHEN (mov.tipo_documento_movimiento = 2) THEN
															(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = mov.id_documento)
													END)
												WHEN (mov.id_tipo_movimiento = 3) THEN
													(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = mov.id_documento)
												WHEN (mov.id_tipo_movimiento = 4) THEN
													(CASE
														WHEN (mov.tipo_documento_movimiento = 1) THEN
															(CASE
																WHEN (clave_mov.id_modulo IN (0)) THEN
																	(SELECT vale_sal.id_empresa FROM iv_vale_salida vale_sal WHERE vale_sal.id_vale_salida = mov.id_documento)
																WHEN (clave_mov.id_modulo IN (1)) THEN
																	(SELECT vale_sal.id_empresa FROM sa_vale_salida vale_sal WHERE vale_sal.id_vale_salida = mov.id_documento)
															END)
														WHEN (mov.tipo_documento_movimiento = 2) THEN
															(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = mov.id_documento)
													END)
											END)))",
				valTpDato($idEmpresa, "date"),
				valTpDato($idEmpresa, "date"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(mov.fecha_movimiento) = %s
			AND YEAR(mov.fecha_movimiento) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 = $cond.sprintf("mov_det.id_movimiento IN (SELECT
				mov.id_movimiento
			FROM iv_movimiento mov
				INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);
		
		$queryDetalle = sprintf("SELECT
			(SELECT mov.tipo_documento_movimiento FROM iv_movimiento mov
			WHERE mov.id_movimiento = mov_det.id_movimiento) AS tipo_documento_movimiento,
			mov_det.cantidad,
			mov_det.precio,
			mov_det.pmu_unitario,
			mov_det.porcentaje_descuento,
			mov_det.costo,
			((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))
				+ (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.pmu_unitario, 0))) AS importePv,
			((IFNULL(mov_det.porcentaje_descuento, 0) * ((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))
															+ (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.pmu_unitario, 0)))) / 100) AS descuento,
			((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))
				+ (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.pmu_unitario, 0))
				- ((IFNULL(mov_det.porcentaje_descuento, 0) * ((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))
																	+ (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.pmu_unitario, 0)))) / 100)) AS neto,
			(IFNULL(mov_det.costo, 0) * IFNULL(mov_det.cantidad, 0)) AS importeCosto
		FROM iv_movimiento_detalle mov_det
			INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
		ORDER BY id_movimiento_detalle;", $sqlBusq3);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalNetoClaveMovVentas);
		$totalRowsDetalle = mysql_num_rows($rsDetalle);
		$totalImportePv = 0;
		$totalDescuento = 0;
		$totalUtilidad = 0;
		$totalNeto = 0;
		$totalImporteC = 0;
		$porcentajeUtilidad = 0;
		$porcentajeDescuento = 0;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$tipoDocumentoMovimiento = $rowDetalle['tipo_documento_movimiento'];
			
			$importePv = $rowDetalle['importePv'];
			$descuento = $rowDetalle['descuento'];
			$neto = $rowDetalle['neto'];
			$importeCosto = $rowDetalle['importeCosto'];
	
			$importeC = ($rowTipoMov['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
	
			if ($importePv > 0) {
				$utilidad = $neto - $importeC;
				$utilidadPorcentaje = $utilidad * 100 / $importePv;
			}
	
			$totalImportePv += $importePv;
			$totalDescuento += $descuento;
			$totalUtilidad += $utilidad;
			$totalNeto += $neto;
			$totalImporteC += $importeC;
		}
	
		if ($totalImportePv > 0) {
			$porcentajeUtilidad = ($totalUtilidad > 0) ? (($totalUtilidad * 100) / $totalImportePv) : 0;
			$porcentajeDescuento = (($totalDescuento * 100) / $totalImportePv);
		} else {
			$porcentajeUtilidad = 0;
			$porcentajeDescuento = 0;
		}
	
		if (in_array($rowTipoMov['id_tipo_movimiento'], array(1,3))) {
			$totalNetoClaveMovVentas += $totalNeto;
		} else if ($rowTipoMov['id_tipo_movimiento'] == 2) {
			switch ($tipoDocumentoMovimiento) { // 1 = Vale Entrada / Salida, 2 = Nota Credito
				case 1 : $totalNetoClaveMovVentas += $totalNeto; break;
				case 2 : $totalNetoClaveMovVentas -= $totalNeto; break;
			}
		} else if ($rowTipoMov['id_tipo_movimiento'] == 4) {
			switch ($tipoDocumentoMovimiento) { // 1 = Vale Entrada / Salida, 2 = Nota Credito
				case 1 : $totalNetoClaveMovVentas += $totalNeto; break;
				case 2 : $totalNetoClaveMovVentas -= $totalNeto; break;
			}
		}
		
		if ($totalRowsDetalle > 0) {
			$arrayMovVentas[] = array(
				"id_tipo_movimiento" => $rowTipoMov['id_tipo_movimiento'],
				"clave_movimiento" => $rowTipoMov['clave'].") ".$rowTipoMov['descripcion'],
				"tipo_documento_movimiento" => $tipoDocumentoMovimiento,
				"total_neto" => $totalNeto);
		}
	}
	
	return array(true, $arrayMovVentas, $totalNetoClaveMovVentas);
}

function facturacionTecnicos($idEmpresa, $mesCierre, $anoCierre) {
	global $conex;
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(equipo_mec.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = equipo_mec.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$queryEquipo = sprintf("SELECT
		equipo_mec.id_equipo_mecanico,
		CONCAT(equipo_mec.nombre_equipo, ' ( ', vw_pg_empleado.nombre_empleado, ' )') AS nombre_equipo,
		vw_pg_empleado.activo
	FROM sa_equipos_mecanicos equipo_mec
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (equipo_mec.id_empleado_jefe_taller = vw_pg_empleado.id_empleado) %s
	ORDER BY nombre_equipo", $sqlBusq);
	$rsEquipo = mysql_query($queryEquipo);
	if (!$rsEquipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowEquipo = mysql_fetch_assoc($rsEquipo)) {
		$queryEmpleado = sprintf("SELECT
			vw_pg_empleado.id_empleado,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado.activo,
			mec.id_mecanico,
			mec.nivel
		FROM sa_mecanicos mec
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (mec.id_empleado = vw_pg_empleado.id_empleado)
		WHERE mec.id_equipo_mecanico = %s
		ORDER BY vw_pg_empleado.nombre_empleado;",
			valTpDato($rowEquipo['id_equipo_mecanico'], "int"));
		$rsEmpleado = mysql_query($queryEmpleado);
		if (!$rsEmpleado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsMercanicos = mysql_num_rows($rsEmpleado);
		
		$arrayTecnico = NULL;
		while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("det_orden_temp.id_mecanico = %s
			AND a.aprobado = 1
			AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
				valTpDato($rowEmpleado['id_mecanico'], "int"));
	
			if ($idEmpresa != "-1" && $idEmpresa != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = a.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
			}
			
			if ($mesCierre != "-1" && $mesCierre != ""
			&& $anoCierre != "-1" && $anoCierre != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
				AND YEAR(a.fecha_filtro) = %s",
					valTpDato($mesCierre, "date"),
					valTpDato($anoCierre, "date"));
			}
			
			// FACTURAS
			$queryFact = sprintf("SELECT
				det_orden_temp.id_mecanico,
				a.id_tipo_orden,
				
				SUM(IFNULL((CASE a.id_modo
					WHEN 1 THEN -- UT
						a.ut / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.ut / a.base_ut_precio
				END), 0)) AS ut_horas,
				
				SUM(IFNULL((CASE a.id_modo
					WHEN 1 THEN -- UT
						(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.precio
				END), 0)) AS valor_uts
				
			FROM sa_det_orden_tempario det_orden_temp
				INNER JOIN sa_v_informe_final_tempario a ON (det_orden_temp.id_det_orden_tempario = a.id_det_orden_tempario) %s
			GROUP BY a.id_mecanico, a.id_tipo_orden;", $sqlBusq);
			$rsFact = mysql_query($queryFact);
			if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsFact = mysql_num_rows($rsFact);
			
			// NOTAS DE CREDITO
			$queryNotaCred = sprintf("SELECT
				det_orden_temp.id_mecanico,
				a.id_tipo_orden,
				
				SUM(IFNULL((CASE a.id_modo
					WHEN 1 THEN -- UT
						a.ut / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.ut / a.base_ut_precio
				END), 0)) AS ut_horas,
				
				SUM(IFNULL((CASE a.id_modo
					WHEN 1 THEN -- UT
						(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.precio
				END), 0)) AS valor_uts
				
			FROM sa_det_orden_tempario det_orden_temp
				INNER JOIN sa_v_informe_final_tempario_dev a ON (det_orden_temp.id_det_orden_tempario = a.id_det_orden_tempario) %s
			GROUP BY a.id_mecanico, a.id_tipo_orden;", $sqlBusq);
			$rsNotaCred = mysql_query($queryNotaCred);
			if (!$rsNotaCred) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsNotaCred = mysql_num_rows($rsNotaCred);
			
			// VALES DE SALIDA
			$queryValeSal = sprintf("SELECT
				det_orden_temp.id_mecanico,
				a.id_tipo_orden,
				
				SUM(IFNULL((CASE a.id_modo
					WHEN 1 THEN -- UT
						a.ut / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.ut / a.base_ut_precio
				END), 0)) AS ut_horas,
				
				SUM(IFNULL((CASE a.id_modo
					WHEN 1 THEN -- UT
						(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.precio
				END), 0)) AS valor_uts
				
			FROM sa_det_vale_salida_tempario det_orden_temp
				INNER JOIN sa_v_vale_informe_final_tempario a ON (det_orden_temp.id_det_vale_salida_tempario = a.id_det_vale_salida_tempario) %s
			GROUP BY a.id_mecanico, a.id_tipo_orden;", $sqlBusq);
			$rsValeSal = mysql_query($queryValeSal);
			if (!$rsValeSal) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeSal = mysql_num_rows($rsValeSal);
			
			// VALES DE ENTRADA
			$queryValeEnt = sprintf("SELECT
				det_orden_temp.id_mecanico,
				a.id_tipo_orden,
				
				SUM(IFNULL((CASE a.id_modo
					WHEN 1 THEN -- UT
						a.ut / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.ut / a.base_ut_precio
				END), 0)) AS ut_horas,
				
				SUM(IFNULL((CASE a.id_modo
					WHEN 1 THEN -- UT
						(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
					WHEN 2 THEN -- PRECIO
						a.precio
				END), 0)) AS valor_uts
				
			FROM sa_det_vale_salida_tempario det_orden_temp
				INNER JOIN sa_v_vale_informe_final_tempario_dev a ON (det_orden_temp.id_det_vale_salida_tempario = a.id_det_vale_salida_tempario) %s
			GROUP BY a.id_mecanico, a.id_tipo_orden;", $sqlBusq);
			$rsValeEnt = mysql_query($queryValeEnt);
			if (!$rsValeEnt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsValeEnt = mysql_num_rows($rsValeEnt);
			
			
			$sqlBusq3 = "";
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("a.id_orden IN (SELECT det_orden_temp.id_orden
														FROM sa_det_orden_tempario det_orden_temp
															INNER JOIN sa_mecanicos mecanico ON (det_orden_temp.id_mecanico = mecanico.id_mecanico)
														WHERE mecanico.id_mecanico = %s)",
				valTpDato($rowEmpleado['id_mecanico'], "int"));
	
			if ($idEmpresa != "-1" && $idEmpresa != "") {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 .= $cond.sprintf("(a.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = a.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
			}
		
			if ($mesCierre != "-1" && $mesCierre != ""
			&& $anoCierre != "-1" && $anoCierre != "") {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
				AND YEAR(a.fecha_filtro) = %s",
					valTpDato($mesCierre, "date"),
					valTpDato($anoCierre, "date"));
			}
			
			// ORDENES CERRADAS
			$queryOrdenCerrada = sprintf("SELECT
				a.id_tipo_orden,
				COUNT(a.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes a %s
			GROUP BY a.id_tipo_orden", $sqlBusq3);
			$rsOrdenCerrada = mysql_query($queryOrdenCerrada);
			if (!$rsOrdenCerrada) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
			$totalRowsOrdenCerrada = mysql_num_rows($rsOrdenCerrada);
			
			// DEVOLUCIONES DE ORDENES
			$queryOrdenDevuelta = sprintf("SELECT
				a.id_tipo_orden,
				COUNT(a.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes_dev a %s
			GROUP BY a.id_tipo_orden", $sqlBusq3);
			$rsOrdenDevuelta = mysql_query($queryOrdenDevuelta);
			if (!$rsOrdenDevuelta) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $totalVentaAsesores);
			$totalRowsOrdenDevuelta = mysql_num_rows($rsOrdenDevuelta);
			
			if ($rowEmpleado['activo'] == 1
			|| $totalRowsFact > 0 || $totalRowsNotaCred > 0 || $totalRowsValeSal > 0 || $totalRowsValeEnt > 0
			|| $totalRowsOrdenCerrada > 0 || $totalRowsOrdenDevuelta > 0) {
				$arrayServ = array(
					$rsFact, $rsNotaCred, $rsValeSal, $rsValeEnt,
					$rsOrdenCerrada, $rsOrdenDevuelta);
				
				$totalMecanicoUts = 0;
				$totalVentaTecnicos = 0;
				foreach ($arrayServ as $indiceServ => $valorServ) {
					while ($rowServ = mysql_fetch_assoc($valorServ)) {
						$signo = (in_array($indiceServ, array(1,3,5,7,9,11,13))) ? (-1) : 1;
						
						$existe = false;
						if (isset($arrayTecnico)) {
							foreach ($arrayTecnico as $indiceTecnico => $valorTecnico) {
								if ($arrayTecnico[$indiceTecnico]['id_tipo_orden'] == $rowServ['id_tipo_orden']
								&& $arrayTecnico[$indiceTecnico]['id_mecanico'] == $rowEmpleado['id_mecanico']
								&& $arrayTecnico[$indiceTecnico]['id_equipo_mecanico'] == $rowEquipo['id_equipo_mecanico']) {
									$existe = true;
									
									$arrayTecnico[$indiceTecnico]['cantidad_ordenes'] += $signo * $rowServ['cantidad_ordenes'];
									$arrayTecnico[$indiceTecnico]['total_ut'] += $signo * $rowServ['ut_horas'];
									$arrayTecnico[$indiceTecnico]['total_mo'] += $signo * $rowServ['valor_uts'];
									$arrayTecnico[$indiceTecnico]['total_mecanico'] = $arrayTecnico[$indiceTecnico]['total_mo'];
								}
							}
						}
						
						if ($existe == false) {
							$arrayTecnico[] = array(
								'id_empleado' => $rowEmpleado['id_empleado'],
								'id_mecanico' => $rowEmpleado['id_mecanico'],
								'nombre_mecanico' => $rowEmpleado['nombre_empleado'],
								'activo' => $rowEmpleado['activo'],
								'id_equipo_mecanico' => $rowEquipo['id_equipo_mecanico'],
								'id_tipo_orden'=> $rowServ['id_tipo_orden'],
								'cantidad_ordenes' => $signo * $rowServ['cantidad_ordenes'],
								'total_ut' => $signo * $rowServ['ut_horas'],
								'total_mo' => $signo * $rowServ['valor_uts'],
								'total_mecanico' => $signo * $rowServ['valor_uts']);
						}
						
						$totalMecanicoUts += ($signo * $rowServ['ut_horas']);
						$totalVentaTecnicos += ($signo * $rowServ['valor_uts']);
					}
				}
				
				switch($rowEmpleado['nivel']) {
					case 'AYUDANTE' : $arrayMecanico[$rowEquipo['id_equipo_mecanico']][0] += 1; break;
					case 'PRINCIPIANTE' : $arrayMecanico[$rowEquipo['id_equipo_mecanico']][1] += 1; break;
					case 'NORMAL' : $arrayMecanico[$rowEquipo['id_equipo_mecanico']][1] += 1; break;
					case 'EXPERTO' : $arrayMecanico[$rowEquipo['id_equipo_mecanico']][1] += 1; break;
				}
				$arrayMecanico[$rowEquipo['id_equipo_mecanico']][2] = $rowEquipo['nombre_equipo'];
			}
		}
		
		$cantidadOrdenes = 0;
		$totalUtsEquipo = 0;
		$totalBsEquipo = 0;
		if (isset($arrayTecnico)) {
			foreach ($arrayTecnico as $indice => $valor) {
				$cantidadOrdenes += $arrayTecnico[$indice]['cantidad_ordenes'];
				$totalUtsEquipo += $arrayTecnico[$indice]['total_ut'];
				$totalBsEquipo += $arrayTecnico[$indice]['total_mo'];
			}
		}
		
		$totalTotalUtsEquipos += $totalUtsEquipo;
		$totalTotalBsEquipos += $totalBsEquipo;
		
		$arrayEquipo[$rowEquipo['id_equipo_mecanico']] = array(
			"nombre_equipo" => $rowEquipo['nombre_equipo'],
			"tecnicos" => $arrayTecnico,
			'cantidad_ordenes' => $cantidadOrdenes,
			"total_ut" => $totalUtsEquipo,
			"total_mo" => $totalBsEquipo);
	}
	
	return array(true, $arrayEquipo, $arrayMecanico, $totalTotalUtsEquipos, $totalTotalBsEquipos);
}
?>