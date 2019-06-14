<?php
class ModeloMOR {
	
	function queryFacturadoLabor($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		$estatusExpressService = $valCadBusq['estatusExpressService'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_temp.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_temp.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_temp_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_temp_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_temp.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_temp_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		if ($estatusExpressService != "-1" && $estatusExpressService != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.serviexp = %s",
				valTpDato($estatusExpressService, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.serviexp = %s",
				valTpDato($estatusExpressService, "int"));
		}
		
		$query = sprintf("SELECT
			q.id_tempario,
			q.descripcion_tempario,
			SUM(q.ut_horas) AS total_ut,
			SUM(q.total_mo) AS total_mo,
			SUM(q.total_mo_costo) AS total_mo_costo
		FROM (
			SELECT 
				temp.id_tempario,
				temp.descripcion_tempario,
				
				SUM(IFNULL((CASE sa_v_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						sa_v_inf_final_temp.ut / sa_v_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_inf_final_temp.ut / sa_v_inf_final_temp.base_ut_precio
				END), 0)) AS ut_horas,
				
				SUM(IFNULL((CASE sa_v_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						(sa_v_inf_final_temp.ut * sa_v_inf_final_temp.precio_tempario_tipo_orden) / sa_v_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_inf_final_temp.precio
				END), 0)) AS total_mo,
				
				SUM(IFNULL((CASE sa_v_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						(sa_v_inf_final_temp.ut * sa_v_inf_final_temp.costo) / sa_v_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_inf_final_temp.costo
				END), 0)) AS total_mo_costo
			FROM sa_v_informe_final_tempario sa_v_inf_final_temp
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario
			
			UNION
			
			SELECT
				temp.id_tempario,
				temp.descripcion_tempario,
				
				(-1) * SUM(IFNULL((CASE sa_v_inf_final_temp_dev.id_modo
							WHEN 1 THEN -- UT
								sa_v_inf_final_temp_dev.ut / sa_v_inf_final_temp_dev.base_ut_precio
							WHEN 2 THEN -- PRECIO
								sa_v_inf_final_temp_dev.ut / sa_v_inf_final_temp_dev.base_ut_precio
						END), 0)) AS ut_horas,
				
				(-1) * SUM(IFNULL((CASE sa_v_inf_final_temp_dev.id_modo
							WHEN 1 THEN -- UT
								(sa_v_inf_final_temp_dev.ut * sa_v_inf_final_temp_dev.precio_tempario_tipo_orden) / sa_v_inf_final_temp_dev.base_ut_precio
							WHEN 2 THEN -- PRECIO
								sa_v_inf_final_temp_dev.precio
						END), 0)) AS total_mo,
				
				(-1) * SUM(IFNULL((CASE sa_v_inf_final_temp_dev.id_modo
							WHEN 1 THEN -- UT
								(sa_v_inf_final_temp_dev.ut * sa_v_inf_final_temp_dev.costo) / sa_v_inf_final_temp_dev.base_ut_precio
							WHEN 2 THEN -- PRECIO
								sa_v_inf_final_temp_dev.costo
						END), 0)) AS total_mo_costo
			FROM sa_v_informe_final_tempario_dev sa_v_inf_final_temp_dev
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp_dev.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario
			
			UNION
			
			SELECT 
				temp.id_tempario,
				temp.descripcion_tempario,
				
				SUM(IFNULL((CASE sa_v_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						sa_v_inf_final_temp.ut / sa_v_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_inf_final_temp.ut / sa_v_inf_final_temp.base_ut_precio
				END), 0)) AS ut_horas,
				
				SUM(IFNULL((CASE sa_v_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						(sa_v_inf_final_temp.ut * sa_v_inf_final_temp.precio_tempario_tipo_orden) / sa_v_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_inf_final_temp.precio
				END), 0)) AS total_mo,
				
				SUM(IFNULL((CASE sa_v_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						(sa_v_inf_final_temp.ut * sa_v_inf_final_temp.costo) / sa_v_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_inf_final_temp.costo
				END), 0)) AS total_mo_costo
			FROM sa_v_vale_informe_final_tempario sa_v_inf_final_temp
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario
			
			UNION
			
			SELECT
				temp.id_tempario,
				temp.descripcion_tempario,
				
				(-1) * SUM(IFNULL((CASE sa_v_inf_final_temp_dev.id_modo
							WHEN 1 THEN -- UT
								sa_v_inf_final_temp_dev.ut / sa_v_inf_final_temp_dev.base_ut_precio
							WHEN 2 THEN -- PRECIO
								sa_v_inf_final_temp_dev.ut / sa_v_inf_final_temp_dev.base_ut_precio
						END), 0)) AS ut_horas,
				
				(-1) * SUM(IFNULL((CASE sa_v_inf_final_temp_dev.id_modo
							WHEN 1 THEN -- UT
								(sa_v_inf_final_temp_dev.ut * sa_v_inf_final_temp_dev.precio_tempario_tipo_orden) / sa_v_inf_final_temp_dev.base_ut_precio
							WHEN 2 THEN -- PRECIO
								sa_v_inf_final_temp_dev.precio
						END), 0)) AS total_mo,
				
				(-1) * SUM(IFNULL((CASE sa_v_inf_final_temp_dev.id_modo
							WHEN 1 THEN -- UT
								(sa_v_inf_final_temp_dev.ut * sa_v_inf_final_temp_dev.costo) / sa_v_inf_final_temp_dev.base_ut_precio
							WHEN 2 THEN -- PRECIO
								sa_v_inf_final_temp_dev.costo
						END), 0)) AS total_mo_costo
			FROM sa_v_vale_informe_final_tempario_dev sa_v_inf_final_temp_dev
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp_dev.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario) AS q
		GROUP BY q.id_tempario", $sqlBusq, $sqlBusq2, $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function queryFacturadoPiezas($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		$estatusExpressService = $valCadBusq['estatusExpressService'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_rep.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_rep.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_rep_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_rep_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_rep.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_rep_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_rep.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_rep_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		if ($estatusExpressService != "-1" && $estatusExpressService != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_rep.serviexp = %s",
				valTpDato($estatusExpressService, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_rep_dev.serviexp = %s",
				valTpDato($estatusExpressService, "int"));
		}
		
		$query = sprintf("SELECT
			q.id_tipo_articulo,
			q.descripcion AS descripcion_tipo_articulo,
			SUM(q.total_repuestos) AS total_repuestos,
			SUM(q.total_repuestos_costo) AS total_repuestos_costo
		FROM (
			SELECT
				tipo_art.id_tipo_articulo,
				tipo_art.descripcion,
				SUM((sa_v_inf_final_rep.cantidad * sa_v_inf_final_rep.precio_unitario)
					- ((sa_v_inf_final_rep.cantidad * sa_v_inf_final_rep.precio_unitario) * sa_v_inf_final_rep.porcentaje_descuento_orden / 100)) AS total_repuestos,
				SUM((sa_v_inf_final_rep.cantidad * sa_v_inf_final_rep.costo_unitario)) AS total_repuestos_costo
			FROM sa_v_informe_final_repuesto sa_v_inf_final_rep
				INNER JOIN iv_tipos_articulos tipo_art ON (sa_v_inf_final_rep.id_tipo_articulo = tipo_art.id_tipo_articulo) %s
			GROUP BY tipo_art.id_tipo_articulo
			
			UNION
			
			SELECT
				tipo_art.id_tipo_articulo,
				tipo_art.descripcion,
				(-1) * SUM((sa_v_inf_final_rep_dev.cantidad * sa_v_inf_final_rep_dev.precio_unitario)
					- ((sa_v_inf_final_rep_dev.cantidad * sa_v_inf_final_rep_dev.precio_unitario) * sa_v_inf_final_rep_dev.porcentaje_descuento_orden / 100)) AS total_repuestos,
				(-1) * SUM((sa_v_inf_final_rep_dev.cantidad * sa_v_inf_final_rep_dev.costo_unitario)) AS total_repuestos_costo
			FROM sa_v_informe_final_repuesto_dev sa_v_inf_final_rep_dev
				INNER JOIN iv_tipos_articulos tipo_art ON (sa_v_inf_final_rep_dev.id_tipo_articulo = tipo_art.id_tipo_articulo) %s
			GROUP BY tipo_art.id_tipo_articulo
			
			UNION
			
			SELECT
				tipo_art.id_tipo_articulo,
				tipo_art.descripcion,
				SUM((sa_v_inf_final_rep.cantidad * sa_v_inf_final_rep.precio_unitario)
					- ((sa_v_inf_final_rep.cantidad * sa_v_inf_final_rep.precio_unitario) * sa_v_inf_final_rep.porcentaje_descuento_orden / 100)) AS total_repuestos,
				SUM((sa_v_inf_final_rep.cantidad * sa_v_inf_final_rep.costo_unitario)) AS total_repuestos_costo
			FROM sa_v_vale_informe_final_repuesto sa_v_inf_final_rep
				INNER JOIN iv_tipos_articulos tipo_art ON (sa_v_inf_final_rep.id_tipo_articulo = tipo_art.id_tipo_articulo) %s
			GROUP BY tipo_art.id_tipo_articulo
			
			UNION
			
			SELECT
				tipo_art.id_tipo_articulo,
				tipo_art.descripcion,
				(-1) * SUM((sa_v_inf_final_rep_dev.cantidad * sa_v_inf_final_rep_dev.precio_unitario)
					- ((sa_v_inf_final_rep_dev.cantidad * sa_v_inf_final_rep_dev.precio_unitario) * sa_v_inf_final_rep_dev.porcentaje_descuento_orden / 100)) AS total_repuestos,
				(-1) * SUM((sa_v_inf_final_rep_dev.cantidad * sa_v_inf_final_rep_dev.costo_unitario)) AS total_repuestos_costo
			FROM sa_v_vale_informe_final_repuesto_dev sa_v_inf_final_rep_dev
				INNER JOIN iv_tipos_articulos tipo_art ON (sa_v_inf_final_rep_dev.id_tipo_articulo = tipo_art.id_tipo_articulo) %s
			GROUP BY tipo_art.id_tipo_articulo) AS q
		GROUP BY q.id_tipo_articulo", $sqlBusq, $sqlBusq2, $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function queryFiltroOrden($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(tipo_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = tipo_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("filtro_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		$query = sprintf("SELECT filtro_orden.*
		FROM sa_filtro_orden filtro_orden
			INNER JOIN sa_tipo_orden tipo_orden ON (filtro_orden.id_filtro_orden = tipo_orden.id_filtro_orden) %s", $sqlBusq);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	// FUNCION QUE DEVUELVE LOS MODELOS EXISTENTES EN LAS ORDENES DE SERVICIO REGISTRADOS Y FACTURADOS
	function queryModeloPorOrdenServicio($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		$query = sprintf("SELECT
			q.id_modelo,
			q.nom_modelo,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT 
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				COUNT(sa_v_inf_final_orden.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_modelo
			
			UNION
			
			SELECT
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				(-1) * COUNT(sa_v_inf_final_orden_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_modelo) AS q
		GROUP BY q.id_modelo", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	// FUNCION QUE DEVUELVE LOS MODELOS EXISTENTES EN LOS VALES DE RECEPCION REGISTRADOS Y FACTURADOS
	function queryModeloPorRecepcionServicio($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		$query = sprintf("SELECT
			q.id_modelo,
			q.nom_modelo,
			SUM(q.cantidad_recepcion) AS cantidad_ordenes
		FROM (
			SELECT 
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				COUNT(DISTINCT sa_v_inf_final_orden.id_recepcion) AS cantidad_recepcion
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_modelo
			
			UNION
			
			SELECT
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				(-1) * COUNT(DISTINCT sa_v_inf_final_orden_dev.id_recepcion) AS cantidad_recepcion
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_modelo) AS q
		GROUP BY q.id_modelo", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function queryMotivoPorModeloOrden($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		$idSubMotivo = $valCadBusq['idSubMotivo'];
		$idModelo = $valCadBusq['idModelo'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		if ($idSubMotivo != "-1" && $idSubMotivo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("submotivo.id_submotivo = %s",
				valTpDato($idSubMotivo, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("submotivo.id_submotivo = %s",
				valTpDato($idSubMotivo, "int"));
		}
		
		if ($idModelo != "-1" && $idModelo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
				valTpDato($idModelo, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
				valTpDato($idModelo, "int"));
		}
		
		$query = sprintf("SELECT
			q.id_motivo_cita,
			q.motivo,
			q.id_submotivo,
			q.descripcion_submotivo,
			q.id_modelo,
			q.nom_modelo,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT 
				motivo_cita.id_motivo_cita,
				motivo_cita.motivo,
				submotivo.id_submotivo,
				submotivo.descripcion_submotivo,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				COUNT(sa_v_inf_final_orden.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_submotivo submotivo ON (sa_v_inf_final_orden.id_submotivo = submotivo.id_submotivo)
				INNER JOIN sa_motivo_cita motivo_cita ON (submotivo.id_motivo_cita = motivo_cita.id_motivo_cita) %s
			GROUP BY submotivo.id_submotivo, vw_iv_modelo.id_modelo
			
			UNION
			
			SELECT
				motivo_cita.id_motivo_cita,
				motivo_cita.motivo,
				submotivo.id_submotivo,
				submotivo.descripcion_submotivo,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				(-1) * COUNT(sa_v_inf_final_orden_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_submotivo submotivo ON (sa_v_inf_final_orden_dev.id_submotivo = submotivo.id_submotivo)
				INNER JOIN sa_motivo_cita motivo_cita ON (submotivo.id_motivo_cita = motivo_cita.id_motivo_cita) %s
			GROUP BY submotivo.id_submotivo, vw_iv_modelo.id_modelo) AS q
		GROUP BY q.id_submotivo, q.id_modelo", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function queryMotivoPorModeloRecepcion($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		$idSubMotivo = $valCadBusq['idSubMotivo'];
		$idModelo = $valCadBusq['idModelo'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		if ($idSubMotivo != "-1" && $idSubMotivo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("submotivo.id_submotivo = %s",
				valTpDato($idSubMotivo, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("submotivo.id_submotivo = %s",
				valTpDato($idSubMotivo, "int"));
		}
		
		if ($idModelo != "-1" && $idModelo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
				valTpDato($idModelo, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
				valTpDato($idModelo, "int"));
		}
		
		$query = sprintf("SELECT
			q.id_motivo_cita,
			q.motivo,
			q.id_submotivo,
			q.descripcion_submotivo,
			q.id_modelo,
			q.nom_modelo,
			SUM(q.cantidad_recepcion) AS cantidad_ordenes
		FROM (
			SELECT 
				motivo_cita.id_motivo_cita,
				motivo_cita.motivo,
				submotivo.id_submotivo,
				submotivo.descripcion_submotivo,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				COUNT(DISTINCT sa_v_inf_final_orden.id_recepcion) AS cantidad_recepcion
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_submotivo submotivo ON (sa_v_inf_final_orden.id_submotivo = submotivo.id_submotivo)
				INNER JOIN sa_motivo_cita motivo_cita ON (submotivo.id_motivo_cita = motivo_cita.id_motivo_cita) %s
			GROUP BY submotivo.id_submotivo, vw_iv_modelo.id_modelo
			
			UNION
			
			SELECT
				motivo_cita.id_motivo_cita,
				motivo_cita.motivo,
				submotivo.id_submotivo,
				submotivo.descripcion_submotivo,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				(-1) * COUNT(DISTINCT sa_v_inf_final_orden_dev.id_recepcion) AS cantidad_recepcion
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_submotivo submotivo ON (sa_v_inf_final_orden_dev.id_submotivo = submotivo.id_submotivo)
				INNER JOIN sa_motivo_cita motivo_cita ON (submotivo.id_motivo_cita = motivo_cita.id_motivo_cita) %s
			GROUP BY submotivo.id_submotivo, vw_iv_modelo.id_modelo) AS q
		GROUP BY q.id_submotivo, q.id_modelo", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function queryMotivoPorOrdenServicio($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		$query = sprintf("SELECT
			q.id_motivo_cita,
			q.motivo,
			q.id_submotivo,
			q.descripcion_submotivo,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT
				motivo_cita.id_motivo_cita,
				motivo_cita.motivo,
				submotivo.id_submotivo,
				submotivo.descripcion_submotivo,
				COUNT(sa_v_inf_final_orden.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN sa_submotivo submotivo ON (sa_v_inf_final_orden.id_submotivo = submotivo.id_submotivo)
				INNER JOIN sa_motivo_cita motivo_cita ON (submotivo.id_motivo_cita = motivo_cita.id_motivo_cita) %s
			GROUP BY submotivo.id_submotivo
			
			UNION
			
			SELECT
				motivo_cita.id_motivo_cita,
				motivo_cita.motivo,
				submotivo.id_submotivo,
				submotivo.descripcion_submotivo,
				(-1) * COUNT(sa_v_inf_final_orden_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN sa_submotivo submotivo ON (sa_v_inf_final_orden_dev.id_submotivo = submotivo.id_submotivo)
				INNER JOIN sa_motivo_cita motivo_cita ON (submotivo.id_motivo_cita = motivo_cita.id_motivo_cita) %s
			GROUP BY submotivo.id_submotivo) AS q
		GROUP BY q.id_submotivo", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function queryMotivoPorRecepcionServicio($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		$query = sprintf("SELECT
			q.id_motivo_cita,
			q.motivo,
			q.id_submotivo,
			q.descripcion_submotivo,
			SUM(q.cantidad_recepcion) AS cantidad_ordenes
		FROM (
			SELECT
				motivo_cita.id_motivo_cita,
				motivo_cita.motivo,
				submotivo.id_submotivo,
				submotivo.descripcion_submotivo,
				COUNT(DISTINCT sa_v_inf_final_orden.id_recepcion) AS cantidad_recepcion
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN sa_submotivo submotivo ON (sa_v_inf_final_orden.id_submotivo = submotivo.id_submotivo)
				INNER JOIN sa_motivo_cita motivo_cita ON (submotivo.id_motivo_cita = motivo_cita.id_motivo_cita) %s
			GROUP BY submotivo.id_submotivo
			
			UNION
			
			SELECT
				motivo_cita.id_motivo_cita,
				motivo_cita.motivo,
				submotivo.id_submotivo,
				submotivo.descripcion_submotivo,
				(-1) * COUNT(DISTINCT sa_v_inf_final_orden_dev.id_recepcion) AS cantidad_recepcion
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN sa_submotivo submotivo ON (sa_v_inf_final_orden_dev.id_submotivo = submotivo.id_submotivo)
				INNER JOIN sa_motivo_cita motivo_cita ON (submotivo.id_motivo_cita = motivo_cita.id_motivo_cita) %s
			GROUP BY submotivo.id_submotivo) AS q
		GROUP BY q.id_submotivo", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	// FUNCION QUE DEVUELVE LA CANTIDAD DE ORDENES DE SERVICIO REGISTRADOS Y FACTURADOS DEPENDIENDO DEL AÑO DE LA UNIDAD
    function queryOrdenPorAno($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		$query = sprintf("SELECT
			q.id_ano,
			q.nom_ano,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT 
				vw_iv_modelo.id_ano,
				vw_iv_modelo.nom_ano,
				COUNT(sa_v_inf_final_orden.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_ano
			
			UNION
			
			SELECT
				vw_iv_modelo.id_ano,
				vw_iv_modelo.nom_ano,
				(-1) * COUNT(sa_v_inf_final_orden_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_ano) AS q
		GROUP BY q.id_ano", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
    }
	
	// FUNCION QUE DEVUELVE LA CANTIDAD DE VALES DE RECEPCION REGISTRADOS Y FACTURADOS DEPENDIENDO DEL AÑO DE LA UNIDAD
    function queryRecepcionPorAno($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		$query = sprintf("SELECT
			q.id_ano,
			q.nom_ano,
			SUM(q.cantidad_recepcion) AS cantidad_ordenes
		FROM (
			SELECT 
				vw_iv_modelo.id_ano,
				vw_iv_modelo.nom_ano,
				COUNT(DISTINCT sa_v_inf_final_orden.id_recepcion) AS cantidad_recepcion
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_ano
			
			UNION
			
			SELECT
				vw_iv_modelo.id_ano,
				vw_iv_modelo.nom_ano,
				(-1) * COUNT(DISTINCT sa_v_inf_final_orden_dev.id_recepcion) AS cantidad_recepcion
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas) %s
			GROUP BY vw_iv_modelo.id_ano) AS q
		GROUP BY q.id_ano", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
    }
	
	function queryTemparioPorModelo($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		$idTempario = $valCadBusq['idTempario'];
		$idModelo = $valCadBusq['idModelo'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_temp.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_temp.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_temp_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_temp_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_temp.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_temp_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		if ($idTempario != "-1" && $idTempario != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("temp.id_tempario = %s",
				valTpDato($idTempario, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("temp.id_tempario = %s",
				valTpDato($idTempario, "int"));
		}
		
		if ($idModelo != "-1" && $idModelo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
				valTpDato($idModelo, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
				valTpDato($idModelo, "int"));
		}
		
		$query = sprintf("SELECT
			q.id_tempario,
			q.descripcion_tempario,
			q.id_modelo,
			q.nom_modelo,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT 
				temp.id_tempario,
				temp.descripcion_tempario,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				COUNT(sa_v_inf_final_temp.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_tempario sa_v_inf_final_temp
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_temp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario, vw_iv_modelo.id_modelo
			
			UNION
			
			SELECT
				temp.id_tempario,
				temp.descripcion_tempario,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				(-1) * COUNT(sa_v_inf_final_temp_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_tempario_dev sa_v_inf_final_temp_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_temp_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp_dev.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario, vw_iv_modelo.id_modelo
			
			UNION
			
			SELECT 
				temp.id_tempario,
				temp.descripcion_tempario,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				COUNT(sa_v_inf_final_temp.id_orden) AS cantidad_ordenes
			FROM sa_v_vale_informe_final_tempario sa_v_inf_final_temp
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_temp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario, vw_iv_modelo.id_modelo
			
			UNION
			
			SELECT
				temp.id_tempario,
				temp.descripcion_tempario,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				(-1) * COUNT(sa_v_inf_final_temp_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_vale_informe_final_tempario_dev sa_v_inf_final_temp_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_temp_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp_dev.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario, vw_iv_modelo.id_modelo) AS q
		GROUP BY q.id_tempario, q.id_modelo", $sqlBusq, $sqlBusq2, $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function queryTemparioServicio($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_temp.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_temp.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_temp_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_temp_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_temp.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_temp_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		$query = sprintf("SELECT
			q.id_tempario,
			q.descripcion_tempario,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT 
				temp.id_tempario,
				temp.descripcion_tempario,
				COUNT(sa_v_inf_final_temp.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_tempario sa_v_inf_final_temp
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario
			
			UNION
			
			SELECT
				temp.id_tempario,
				temp.descripcion_tempario,
				(-1) * COUNT(sa_v_inf_final_temp_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_tempario_dev sa_v_inf_final_temp_dev
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp_dev.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario
			
			UNION
			
			SELECT 
				temp.id_tempario,
				temp.descripcion_tempario,
				COUNT(sa_v_inf_final_temp.id_orden) AS cantidad_ordenes
			FROM sa_v_vale_informe_final_tempario sa_v_inf_final_temp
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario
			
			UNION
			
			SELECT
				temp.id_tempario,
				temp.descripcion_tempario,
				(-1) * COUNT(sa_v_inf_final_temp_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_vale_informe_final_tempario_dev sa_v_inf_final_temp_dev
				INNER JOIN sa_tempario temp ON (sa_v_inf_final_temp_dev.id_tempario = temp.id_tempario) %s
			GROUP BY temp.id_tempario) AS q
		GROUP BY q.id_tempario", $sqlBusq, $sqlBusq2, $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function queryTipoOrdenPorModelo($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		$idModelo = $valCadBusq['idModelo'];
		$estatusExpressService = $valCadBusq['estatusExpressService'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		if ($idModelo != "-1" && $idModelo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
				valTpDato($idModelo, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
				valTpDato($idModelo, "int"));
		}
		
		if ($estatusExpressService != "-1" && $estatusExpressService != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.serviexp = %s",
				valTpDato($estatusExpressService, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.serviexp = %s",
				valTpDato($estatusExpressService, "int"));
		}
		
		$query = sprintf("SELECT
			q.id_filtro_orden,
			q.nombre_tipo_orden,
			q.id_modelo,
			q.nom_modelo,
			q.serviexp,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT 
				filtro_orden.id_filtro_orden,
				tipo_orden.id_tipo_orden,
				IF (sa_v_inf_final_orden.serviexp = 1,
					CONCAT(tipo_orden.nombre_tipo_orden, ' (SERVIEXPRESS)'),
					tipo_orden.nombre_tipo_orden) AS nombre_tipo_orden,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				sa_v_inf_final_orden.serviexp,
				COUNT(sa_v_inf_final_orden.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_tipo_orden tipo_orden ON (sa_v_inf_final_orden.id_tipo_orden = tipo_orden.id_tipo_orden)
				INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
			GROUP BY tipo_orden.id_tipo_orden, vw_iv_modelo.id_modelo, sa_v_inf_final_orden.serviexp
			
			UNION
			
			SELECT
				filtro_orden.id_filtro_orden,
				tipo_orden.id_tipo_orden,
				IF (sa_v_inf_final_orden_dev.serviexp = 1,
					CONCAT(tipo_orden.nombre_tipo_orden, ' (SERVIEXPRESS)'),
					tipo_orden.nombre_tipo_orden) AS nombre_tipo_orden,
				vw_iv_modelo.id_modelo,
				vw_iv_modelo.nom_modelo,
				sa_v_inf_final_orden_dev.serviexp,
				(-1) * COUNT(sa_v_inf_final_orden_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_tipo_orden tipo_orden ON (sa_v_inf_final_orden_dev.id_tipo_orden = tipo_orden.id_tipo_orden)
				INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
			GROUP BY tipo_orden.id_tipo_orden, vw_iv_modelo.id_modelo, sa_v_inf_final_orden_dev.serviexp) AS q
		GROUP BY q.id_filtro_orden, q.id_modelo, q.serviexp", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	// FUNCION QUE DEVUELVE LA CANTIDAD DE ORDENES
	function queryTipoOrdenServicio($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		$estatusExpressService = $valCadBusq['estatusExpressService'];
		$estatusRealizadaATiempo = $valCadBusq['estatusRealizadaATiempo'];
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(sa_v_inf_final_orden.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(sa_v_inf_final_orden_dev.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_v_inf_final_orden_dev.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE_FORMAT(sa_v_inf_final_orden_dev.fecha_filtro, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		if ($estatusExpressService != "-1" && $estatusExpressService != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.serviexp = %s",
				valTpDato($estatusExpressService, "boolean"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.serviexp = %s",
				valTpDato($estatusExpressService, "boolean"));
		}
		
		if ($estatusRealizadaATiempo != "-1" && $estatusRealizadaATiempo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_orden.tiempo_entrega <= sa_v_inf_final_orden.fecha_estimada_entrega");
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_orden_dev.tiempo_entrega <= sa_v_inf_final_orden_dev.fecha_estimada_entrega");
		}
		
		$query = sprintf("SELECT
			q.id_filtro_orden,
			q.nombre_tipo_orden,
			q.serviexp,
			SUM(q.tiempo_estadia) AS tiempo_estadia,
			SUM(q.cantidad_ordenes) AS cantidad_ordenes
		FROM (
			SELECT 
				filtro_orden.id_filtro_orden,
				tipo_orden.id_tipo_orden,
				IF (sa_v_inf_final_orden.serviexp = 1,
					CONCAT(tipo_orden.nombre_tipo_orden, ' (SERVIEXPRESS)'),
					tipo_orden.nombre_tipo_orden) AS nombre_tipo_orden,
				sa_v_inf_final_orden.serviexp,
				AVG((DATEDIFF(sa_v_inf_final_orden.tiempo_finalizado, sa_v_inf_final_orden.tiempo_orden) + 1)) AS tiempo_estadia,
				COUNT(sa_v_inf_final_orden.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes sa_v_inf_final_orden
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_tipo_orden tipo_orden ON (sa_v_inf_final_orden.id_tipo_orden = tipo_orden.id_tipo_orden)
				INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
			GROUP BY tipo_orden.id_tipo_orden, sa_v_inf_final_orden.serviexp
			
			UNION
			
			SELECT
				filtro_orden.id_filtro_orden,
				tipo_orden.id_tipo_orden,
				IF (sa_v_inf_final_orden_dev.serviexp = 1,
					CONCAT(tipo_orden.nombre_tipo_orden, ' (SERVIEXPRESS)'),
					tipo_orden.nombre_tipo_orden) AS nombre_tipo_orden,
				sa_v_inf_final_orden_dev.serviexp,
				(-1) * AVG((DATEDIFF(sa_v_inf_final_orden_dev.tiempo_finalizado, sa_v_inf_final_orden_dev.tiempo_orden) + 1)) AS tiempo_estadia,
				(-1) * COUNT(sa_v_inf_final_orden_dev.id_orden) AS cantidad_ordenes
			FROM sa_v_informe_final_ordenes_dev sa_v_inf_final_orden_dev
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (sa_v_inf_final_orden_dev.id_unidad_basica = vw_iv_modelo.id_uni_bas)
				INNER JOIN sa_tipo_orden tipo_orden ON (sa_v_inf_final_orden_dev.id_tipo_orden = tipo_orden.id_tipo_orden)
				INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
			GROUP BY tipo_orden.id_tipo_orden, sa_v_inf_final_orden_dev.serviexp) AS q
		GROUP BY q.id_filtro_orden, q.serviexp", $sqlBusq, $sqlBusq2);
		
		$query .= ($this->campOrd != "") ? sprintf(" ORDER BY %s %s", $this->campOrd, $this->tpOrd) : "";
		
        return $query;
	}
	
	function CapacityWorkshop($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		//////////////////// Días Promedio de Estadía ////////////////////
		$this->campOrd = "nombre_tipo_orden";
		$this->tpOrd = "ASC";
		$query = $this->queryTipoOrdenServicio(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $fechaCierre,
			"idFiltroOrden" => $idFiltroOrden,
			"estatusExpressService" => $estatusExpressService));
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayCapacityWorkshop['average_days_of_stay'] += $row['tiempo_estadia'];
		}
		$arrayCapacityWorkshop['average_days_of_stay'] = $arrayCapacityWorkshop['average_days_of_stay'] / $totalRows;
		
		//////////////////// Número Total de Asesores de Servicio ////////////////////
		$arrayFacturacionAsesorAux = $this->FacturacionAsesor(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha));//return $arrayExpressServiceAux;
		if (!is_array($arrayFacturacionAsesorAux)) return $arrayFacturacionAsesorAux;
		$arrayOrdenesAsesor = $arrayFacturacionAsesorAux[1];
		if (isset($arrayOrdenesAsesor)) {
			foreach ($arrayOrdenesAsesor as $indiceOrdenesAsesor => $valorOrdenesAsesor) {
				$arrayTotalAsesor[$valorOrdenesAsesor['id_empleado']] = array(
					"id_empleado" => $valorOrdenesAsesor['id_empleado'],
					"cantidad_ordenes" => $arrayTotalAsesor[$valorOrdenesAsesor['id_empleado']]['cantidad_ordenes'] + $valorOrdenesAsesor['cantidad_ordenes'],
					"total_ut" => $arrayTotalAsesor[$valorOrdenesAsesor['id_empleado']]['total_ut'] + $valorOrdenesAsesor['total_ut'],
					"total_mo" => $arrayTotalAsesor[$valorOrdenesAsesor['id_empleado']]['total_mo'] + $valorOrdenesAsesor['total_mo'],
					"total_repuestos" => $arrayTotalAsesor[$valorOrdenesAsesor['id_empleado']]['total_repuestos'] + $valorOrdenesAsesor['total_repuestos'],
					"total_tot" => $arrayTotalAsesor[$valorOrdenesAsesor['id_empleado']]['total_tot'] + $valorOrdenesAsesor['total_tot'],
					"total_asesor" => $arrayTotalAsesor[$valorOrdenesAsesor['id_empleado']]['total_asesor'] + $valorOrdenesAsesor['total_asesor']);
			}
		}
		if (isset($arrayTotalAsesor)) {
			foreach ($arrayTotalAsesor as $indiceTotalAsesor => $valorTotalAsesor) {
				$arrayCapacityWorkshop['total_number_of_service_advisor'] += 1;
			}
		}
		
		
		//////////////////// Número Total de Técnicos ////////////////////
		$arrayFacturacionTecnicoAux = $this->FacturacionTecnico(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha));//return $arrayExpressServiceAux;
		if (!is_array($arrayFacturacionTecnicoAux)) return $arrayFacturacionTecnicoAux;
		$arrayEquipo = $arrayFacturacionTecnicoAux[1];
		if (isset($arrayEquipo)) {
			foreach ($arrayEquipo as $indiceEquipo => $valorEquipo) {
				$arrayCapacityWorkshop['total_number_of_technical'] += $valorEquipo['cantidad_tecnicos'] + $valorEquipo['cantidad_tecnicos_formacion'];
			}
		}
		
		
		//////////////////// Número Total de Pinos ////////////////////
		$sqlBusq = "";
		// 6 = PUENTE SERVI-EXPRESS, 7 = PUENTE
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("puesto.activo = 1 AND tipo_puesto.id_tipo_puesto IN (6,7)");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(puesto.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = puesto.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		$query = sprintf("SELECT *
		FROM sa_puestos puesto
			INNER JOIN sa_tipo_puestos tipo_puesto ON (puesto.id_tipo = tipo_puesto.id_tipo_puesto) %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		$arrayCapacityWorkshop['total_number_of_bays'] = $totalRows;
		
		
		//////////////////// Número de Lavadoras de Coche ////////////////////
		$sqlBusq = "";
		// 5 = LAVADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("puesto.activo = 1 AND tipo_puesto.id_tipo_puesto IN (5)");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(puesto.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = puesto.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		$query = sprintf("SELECT *
		FROM sa_puestos puesto
			INNER JOIN sa_tipo_puestos tipo_puesto ON (puesto.id_tipo = tipo_puesto.id_tipo_puesto) %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		$arrayCapacityWorkshop['number_of_car_washers'] = $totalRows;
		
		return $arrayCapacityWorkshop;
	}
	
	function FacturacionAsesor($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		
		$queryEmpleado = "SELECT
			id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
		FROM pg_empleado empleado
		ORDER BY id_empleado";
		$rsEmpleado = mysql_query($queryEmpleado);
		if (!$rsEmpleado) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
		
			if ($fechaCierre != "-1" && $fechaCierre != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("DATE_FORMAT(a.fecha_filtro, %s) = %s",
					valTpDato("%m-%Y", "date"),
					valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
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
			if (!$rsMOFact) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
			if (!$rsMONotaCred) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
			if (!$rsMOValeSal) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
			if (!$rsMOValeEnt) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
			if (!$rsRepFact) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
			if (!$rsRepNotaCred) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
			if (!$rsRepValeSal) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
			if (!$rsRepValeEnt) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
			$totalRowsRepValeEnt = mysql_num_rows($rsRepValeEnt);
			
			
			// TOT FACTURAS DE SERVICIOS
			$queryTotFact = sprintf("SELECT
				a.id_empleado,
				a.id_tipo_orden,
				SUM(a.monto_total + ((a.porcentaje_tot * a.monto_total) / 100)) AS total_tot_orden
			FROM sa_v_informe_final_tot a %s
			GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
			$rsTotFact = mysql_query($queryTotFact);
			if (!$rsTotFact) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
			$totalRowsTotFact = mysql_num_rows($rsTotFact);
			
			// TOT NOTAS DE CREDITO DE SERVICIOS
			$queryTotNotaCred = sprintf("SELECT
				a.id_empleado,
				a.id_tipo_orden,
				SUM(a.monto_total + ((a.porcentaje_tot * a.monto_total) / 100)) AS total_tot_orden
			FROM sa_v_informe_final_tot_dev a %s
			GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
			$rsTotNotaCred = mysql_query($queryTotNotaCred);
			if (!$rsTotNotaCred) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
			$totalRowsTotNotaCred = mysql_num_rows($rsTotNotaCred);
			
			// TOT VALE DE SALIDA DE SERVICIOS
			$queryTotValeSal = sprintf("SELECT
				a.id_empleado,
				a.id_tipo_orden,
				SUM(a.monto_total + ((a.porcentaje_tot * a.monto_total) / 100)) AS total_tot_orden
			FROM sa_v_vale_informe_final_tot a %s
			GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
			$rsTotValeSal = mysql_query($queryTotValeSal);
			if (!$rsTotValeSal) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
			$totalRowsTotValeSal = mysql_num_rows($rsTotValeSal);
			
			// TOT VALE DE ENTRADA DE SERVICIOS
			$queryTotValeEnt = sprintf("SELECT
				a.id_empleado,
				a.id_tipo_orden,
				SUM(a.monto_total + ((a.porcentaje_tot * a.monto_total) / 100)) AS total_tot_orden
			FROM sa_v_vale_informe_final_tot_dev a %s
			GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq);
			$rsTotValeEnt = mysql_query($queryTotValeEnt);
			if (!$rsTotValeEnt) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
		
			if ($fechaCierre != "-1" && $fechaCierre != "") {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 .= $cond.sprintf("DATE_FORMAT(a.fecha_filtro, %s) = %s",
					valTpDato("%m-%Y", "date"),
					valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
			}
			
			// ORDENES CERRADAS
			$queryOrdenCerrada = sprintf("SELECT
				a.id_empleado,
				a.id_tipo_orden,
				COUNT(a.id_orden) AS cant_ordenes
			FROM sa_v_informe_final_ordenes a %s
			GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq3);
			$rsOrdenCerrada = mysql_query($queryOrdenCerrada);
			if (!$rsOrdenCerrada) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
			$totalRowsOrdenCerrada = mysql_num_rows($rsOrdenCerrada);
			
			// DEVOLUCIONES DE ORDENES
			$queryOrdenDevuelta = sprintf("SELECT
				a.id_empleado,
				a.id_tipo_orden,
				COUNT(a.id_orden) AS cant_ordenes
			FROM sa_v_informe_final_ordenes_dev a %s
			GROUP BY a.id_empleado, a.id_tipo_orden", $sqlBusq3);
			$rsOrdenDevuelta = mysql_query($queryOrdenDevuelta);
			if (!$rsOrdenDevuelta) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
									
									$arrayVentaAsesor[$indice2]['cantidad_ordenes'] += $signo * $rowServ['cant_ordenes'];
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
								'cantidad_ordenes' => $signo * $rowServ['cant_ordenes'],
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
	
	function FacturacionTecnico($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
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
		if (!$rsEquipo) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
			if (!$rsEmpleado) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
			$totalRowsMercanicos = mysql_num_rows($rsEmpleado);
			
			$arrayTecnico = NULL;
			$cantidadTecnicosFormacion = 0;
			$cantidadTecnicos = 0;
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
				
				if ($fechaCierre != "-1" && $fechaCierre != "") {
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("DATE_FORMAT(a.fecha_filtro, %s) = %s",
						valTpDato("%m-%Y", "date"),
						valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
				}
		
				if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
						valTpDato($idFiltroOrden, "campo"));
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
				if (!$rsFact) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
				if (!$rsNotaCred) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
				if (!$rsValeSal) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
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
				if (!$rsValeEnt) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
				$totalRowsValeEnt = mysql_num_rows($rsValeEnt);
				
				
				$sqlBusq3 = "";
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 .= $cond.sprintf("a.id_orden IN (SELECT det_orden_temp.id_orden
															FROM sa_det_orden_tempario det_orden_temp
																INNER JOIN sa_mecanicos mecanico ON (det_orden_temp.id_mecanico = mecanico.id_mecanico)
															WHERE mecanico.id_empleado = %s)",
					valTpDato($rowEmpleado['id_empleado'], "int"));
				
				if ($idEmpresa != "-1" && $idEmpresa != "") {
					$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
					$sqlBusq3 .= $cond.sprintf("(a.id_empresa = %s
					OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
							WHERE suc.id_empresa = a.id_empresa))",
						valTpDato($idEmpresa, "int"),
						valTpDato($idEmpresa, "int"));
				}
			
				if ($fechaCierre != "-1" && $fechaCierre != "") {
					$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
					$sqlBusq3 .= $cond.sprintf("DATE_FORMAT(a.fecha_filtro, %s) = %s",
						valTpDato("%m-%Y", "date"),
						valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
				}
				
				// ORDENES CERRADAS
				$queryOrdenCerrada = sprintf("SELECT
					a.id_tipo_orden,
					COUNT(a.id_orden) AS cant_ordenes
				FROM sa_v_informe_final_ordenes a %s
				GROUP BY a.id_tipo_orden", $sqlBusq3);
				$rsOrdenCerrada = mysql_query($queryOrdenCerrada);
				if (!$rsOrdenCerrada) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
				$totalRowsOrdenCerrada = mysql_num_rows($rsOrdenCerrada);
				
				// DEVOLUCIONES DE ORDENES
				$queryOrdenDevuelta = sprintf("SELECT
					a.id_tipo_orden,
					COUNT(a.id_orden) AS cant_ordenes
				FROM sa_v_informe_final_ordenes_dev a %s
				GROUP BY a.id_tipo_orden", $sqlBusq3);
				$rsOrdenDevuelta = mysql_query($queryOrdenDevuelta);
				if (!$rsOrdenDevuelta) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
				$totalRowsOrdenDevuelta = mysql_num_rows($rsOrdenDevuelta);
				
				if ($rowEmpleado['activo'] == 1
				|| $totalRowsFact > 0 || $totalRowsNotaCred > 0 || $totalRowsValeSal > 0 || $totalRowsValeEnt > 0
				|| $totalRowsOrdenCerrada > 0 || $totalRowsOrdenDevuelta > 0) {
					$arrayServ = array(
						$rsFact, $rsNotaCred, $rsValeSal, $rsValeEnt,
						$rsOrdenCerrada, $rsOrdenDevuelta);
					
					$totalMecanicoUts = 0;
					$totalVentaTecnicos = 0;
					foreach ($arrayServ as $indice => $valor) {
						while ($rowServ = mysql_fetch_assoc($valor)) {
							$signo = (in_array($indice, array(1,3,5,7,9,11,13))) ? (-1) : 1;
							
							$existe = false;
							if (isset($arrayTecnico)) {
								foreach ($arrayTecnico as $indice2 => $valor2) {
									if ($arrayTecnico[$indice2]['id_tipo_orden'] == $rowServ['id_tipo_orden']
									&& $arrayTecnico[$indice2]['id_empleado'] == $rowServ['id_empleado']) {
										$existe = true;
										
										$arrayTecnico[$indice2]['cantidad_ordenes'] += $signo * $rowServ['cant_ordenes'];
										$arrayTecnico[$indice2]['total_ut'] += $signo * $rowServ['ut_horas'];
										$arrayTecnico[$indice2]['total_mo'] += $signo * $rowServ['valor_uts'];
										$arrayTecnico[$indice2]['total_mecanico'] = $arrayTecnico[$indice2]['total_mo'];
									}
								}
							}
							
							if ($existe == false) {
								$arrayTecnico[] = array(
									'id_empleado' => $rowEmpleado['id_empleado'],
									'nombre_mecanico' => $rowEmpleado['nombre_empleado'],
									'activo' => $rowEmpleado['activo'],
									'id_equipo_mecanico' => $rowEquipo['id_equipo_mecanico'],
									'id_tipo_orden'=> $rowServ['id_tipo_orden'],
									'cantidad_ordenes' => $signo * $rowServ['cant_ordenes'],
									'total_ut' => $signo * $rowServ['ut_horas'],
									'total_mo' => $signo * $rowServ['valor_uts'],
									'total_mecanico' => $signo * $rowServ['valor_uts']);
							}
							
							$totalMecanicoUts += ($signo * $rowServ['ut_horas']);
							$totalVentaTecnicos += ($signo * $rowServ['valor_uts']);
						}
					}
					
					switch($rowEmpleado['nivel']) {
						case 'AYUDANTE' : $cantidadTecnicosFormacion++; break;
						case 'PRINCIPIANTE' : $cantidadTecnicos++; break;
						case 'NORMAL' : $cantidadTecnicos++; break;
						case 'EXPERTO' : $cantidadTecnicos++; break;
					}
				}
			}
			
			$cantidadOrdenesEquipo = 0;
			$totalUtEquipo = 0;
			$totalMOEquipo = 0;
			if (isset($arrayTecnico)) {
				foreach ($arrayTecnico as $indice => $valor) {
					$cantidadOrdenesEquipo += $arrayTecnico[$indice]['cantidad_ordenes'];
					$totalUtEquipo += $arrayTecnico[$indice]['total_ut'];
					$totalMOEquipo += $arrayTecnico[$indice]['total_mo'];
				}
			}
			
			$totalTotalOrdenesEquipos += $cantidadOrdenesEquipo;
			$totalTotalUtEquipos += $totalUtEquipo;
			$totalTotalMOEquipos += $totalMOEquipo;
			
			$arrayEquipo[$rowEquipo['id_equipo_mecanico']] = array(
				"nombre_equipo" => $rowEquipo['nombre_equipo'],
				"tecnicos" => $arrayTecnico,
				'cantidad_ordenes' => $cantidadOrdenesEquipo,
				"total_ut" => $totalUtEquipo,
				"total_mo" => $totalMOEquipo,
				"cantidad_tecnicos" => $cantidadTecnicos,
				"cantidad_tecnicos_formacion" => $cantidadTecnicosFormacion);
		}
		
		return array(true, $arrayEquipo, $totalTotalUtEquipos, $totalTotalMOEquipos);
	}
	
	function PublicMechanics($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		
		//////////////////// Venta de Mano de Obra Mecánica ////////////////////
		$this->campOrd = "";
		$this->tpOrd = "";
		$query = $this->queryFacturadoLabor(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $fechaCierre,
			"idFiltroOrden" => $idFiltroOrden));
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayPublicMechanics['hours_billed_to_public'] += $row['total_ut'];
			$arrayPublicMechanics['sale_of_mechanical_labor'] += $row['total_mo'];
			$arrayPublicMechanics['cost_of_mechanical_labor'] += $row['total_mo_costo'];
		}
		
		
		//////////////////// Venta de Piezas Mecánicas ////////////////////
		$this->campOrd = "";
		$this->tpOrd = "";
		$query = $this->queryFacturadoPiezas(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $fechaCierre,
			"idFiltroOrden" => $idFiltroOrden));
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayPublicMechanics['sale_of_mechanical_part'] += $row['total_repuestos'];
			$arrayPublicMechanics['cost_of_mechanical_part'] += $row['total_repuestos_costo'];
		}
		
		
		// BUSCA LOS DIAS FERIADOS
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo NOT IN ('FERIADO')");
			
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$sqlBusq .= $cond.sprintf("(DATE_FORMAT(pg_fecha_baja.fecha_baja, %s) = %s
			OR (MONTH(pg_fecha_baja.fecha_baja) = %s AND YEAR(pg_fecha_baja.fecha_baja) = '0000'))",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"),
				valTpDato("%m", "text"));
		}
		
		$queryDiasFeriados = sprintf("SELECT * FROM pg_fecha_baja %s;", $sqlBusq);
		$rsDiasFeriados = mysql_query($queryDiasFeriados);
		if (!$rsDiasFeriados) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRowsDiasFeriados = mysql_num_rows($rsDiasFeriados);
		$diaHabiles = evaluaFecha(diasHabiles(
			date("d-m-Y", strtotime("01-".$fechaCierre)),
			date("d-m-Y", strtotime(ultimoDia(date("m", strtotime("01-".$fechaCierre)),date("Y", strtotime("01-".$fechaCierre)))."-".$fechaCierre))))
			- $totalRowsDiasFeriados;
		
		//////////////////// Número de Horas Disponibles ////////////////////
		$arrayFacturacionTecnicoAux = $this->FacturacionTecnico(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha));//return $arrayExpressServiceAux;
		if (!is_array($arrayFacturacionTecnicoAux)) return $arrayFacturacionTecnicoAux;
		$arrayEquipo = $arrayFacturacionTecnicoAux[1];
		if (isset($arrayEquipo)) {
			foreach ($arrayEquipo as $indiceEquipo => $valorEquipo) {
				$arrayPublicMechanics['number_of_availabre_hours'] += (($valorEquipo['cantidad_tecnicos'] * 7.1) + ($valorEquipo['cantidad_tecnicos_formacion'] * 3.57)) * $diaHabiles;
			}
		}
		
        return $arrayPublicMechanics;
	}
	
	function ServicioExpreso($valBusq) {
		$valCadBusq = $valBusq;
		
		$idEmpresa = $valCadBusq['idEmpresa'];
		$fechaCierre = $valCadBusq['fechaCierre'];
		$idFiltroOrden = $valCadBusq['idFiltroOrden'];
		$estatusExpressService = $valCadBusq['estatusExpressService'];
		
		//////////////////// Numero de Puentes ////////////////////
		$sqlBusq = "";
		// 6 = PUENTE SERVI-EXPRESS
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("puesto.activo = 1 AND tipo_puesto.id_tipo_puesto IN (6)");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(puesto.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = puesto.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		$query = sprintf("SELECT *
		FROM sa_puestos puesto
			INNER JOIN sa_tipo_puestos tipo_puesto ON (puesto.id_tipo = tipo_puesto.id_tipo_puesto) %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		$arrayExpressService['numero_puentes'] = $totalRows;
		
		
		//////////////////// Horas Facturadas al Público, Venta de Mano de Obra ////////////////////
		$this->campOrd = "";
		$this->tpOrd = "";
		$query = $this->queryFacturadoLabor(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $fechaCierre,
			"idFiltroOrden" => $idFiltroOrden,
			"estatusExpressService" => $estatusExpressService));
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayExpressService['horas_facturadas_servicio_expreso'] += $row['total_ut'];
			$arrayExpressService['dinero_facturado_labor'] += $row['total_mo'];
		}
				
		
		//////////////////// Venta de Pieza ////////////////////
		$this->campOrd = "";
		$this->tpOrd = "";
		$query = $this->queryFacturadoPiezas(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $fechaCierre,
			"idFiltroOrden" => $idFiltroOrden,
			"estatusExpressService" => $estatusExpressService));
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayExpressService['dinero_facturado_piezas'] += $row['total_repuestos'];
		}
		
		
		//////////////////// Número de Órdenes de Reparación Programadas ////////////////////
		$sqlBusq = "";
		// PROGRAMADA, ENTRADA
		// PENDIENTE, CONFIRMADA, CANCELADA, RETRAZADA, INCUMPLIDA, PROCESADA, POSPUESTA
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_sa_consulta_cita.origen_cita IN ('PROGRAMADA')
		AND vw_sa_consulta_cita.estado_cita IN ('PENDIENTE','CONFIRMADA','CANCELADA','RETRAZADA','INCUMPLIDA','PROCESADA'))");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(vw_sa_consulta_cita.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = vw_sa_consulta_cita.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($fechaCierre != "-1" && $fechaCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("DATE_FORMAT(vw_sa_consulta_cita.fecha_cita, %s) = %s",
				valTpDato("%m-%Y", "date"),
				valTpDato(date("m-Y", strtotime("01-".$fechaCierre)), "date"));
		}
		
		/*if ($idFiltroOrden != "-1" && $idFiltroOrden != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf(".id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf(".id_filtro_orden IN (%s)",
				valTpDato($idFiltroOrden, "campo"));
		}
		
		if ($estatusExpressService != "-1" && $estatusExpressService != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_rep.serviexp = %s",
				valTpDato($estatusExpressService, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_rep_dev.serviexp = %s",
				valTpDato($estatusExpressService, "int"));
		}*/
		
		$query = sprintf("SELECT * FROM vw_sa_consulta_citas vw_sa_consulta_cita %s", $sqlBusq);//return $query;
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		$arrayExpressService['numero_ordenes_citadas'] = $totalRows;
		
			
		//////////////////// Número de Órdenes de Reparación Realizadas ////////////////////
		$this->campOrd = "nombre_tipo_orden";
		$this->tpOrd = "ASC";
		$query = $this->queryTipoOrdenServicio(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $fechaCierre,
			"idFiltroOrden" => $idFiltroOrden,
			"estatusExpressService" => $estatusExpressService));
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayExpressService['numero_ordenes_realizadas'] += $row['cantidad_ordenes'];
		}
		
		
		//////////////////// Número de Órdenes de Reparación Realizadas a Tiempo ////////////////////
		$this->campOrd = "nombre_tipo_orden";
		$this->tpOrd = "ASC";
		$query = $this->queryTipoOrdenServicio(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $fechaCierre,
			"idFiltroOrden" => $idFiltroOrden,
			"estatusExpressService" => $estatusExpressService,
			"estatusRealizadaATiempo" => 1));//return $query;
		$rs = mysql_query($query);
		if (!$rs) return mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__;
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayExpressService['numero_ordenes_a_tiempo'] += $row['cantidad_ordenes'];
		}
		
        return $arrayExpressService;
	}
}
?>