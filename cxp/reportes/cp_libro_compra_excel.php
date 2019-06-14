<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

header('Content-type: application/vnd.ms-excel; charset=utf-8');
header("Content-Disposition: attachment; filename=LibroDeCompras.xls");
header("Pragma: no-cache");
header("Expires: 0");

/*header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=LibroDeCompras.xls");  //File name extension was wrong
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);*/

require_once ("../../connections/conex.php");

session_start();

//include("../../controladores/ac_iv_general.php");
//include("../controladores/ac_cc_libro_venta_imp.php");

function listaLibro($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15000, $totalRows = NULL) {
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	mysql_query("START TRANSACTION;");
	
	$lstFormatoNumero = $valCadBusq[4];
	$lstFormatoTotalDia = $valCadBusq[5];
	
	$sqlBusq = "";
	$sqlBusq2 = "";
	$sqlBusq3 = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxp_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
		$sqlBusq2 .= $cond.sprintf("(cxp_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxp_nc.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
		$sqlBusq3 .= $cond.sprintf("(cxp_nd.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxp_nd.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[3] != -1 && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq2 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq3 .= $cond.sprintf("cxp_nd.id_modulo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	mysql_query("START TRANSACTION;");
	
	// CONSULTA PARA ESTABLECER LA AGRUPACION EN EL LIBRO
	$idCampoOrden = 1; // 1 = Fecha Documento, 2 = Fecha Registro
	$query = sprintf("SELECT
		%s AS fecha_grupo,
		cxp_fact.aplica_libros
	FROM cp_factura cxp_fact
	WHERE DATE(cxp_fact.fecha_origen) BETWEEN %s AND %s
		AND cxp_fact.aplica_libros = 1 %s
		
	UNION
	
	SELECT
		%s AS fecha_grupo,
		cxp_nc.aplica_libros_notacredito
	FROM cp_notacredito cxp_nc
	WHERE DATE(cxp_nc.fecha_registro_notacredito) BETWEEN %s AND %s
		AND cxp_nc.aplica_libros_notacredito = 1 %s
		
	UNION
	
	SELECT
		%s AS fecha_grupo,
		cxp_nd.aplica_libros_notacargo
	FROM cp_notadecargo cxp_nd
	WHERE DATE(cxp_nd.fecha_origen_notacargo) BETWEEN %s AND %s
		AND cxp_nd.aplica_libros_notacargo = 1 %s
	ORDER BY 1 ASC",
		(($idCampoOrden == 1) ? "cxp_fact.fecha_factura_proveedor" : "cxp_fact.fecha_origen"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq,
		(($idCampoOrden == 1) ? "cxp_nc.fecha_notacredito" : "cxp_nc.fecha_registro_notacredito"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq2,
		(($idCampoOrden == 1) ? "cxp_nd.fecha_notacargo" : "cxp_nd.fecha_origen_notacargo"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq3);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$queryIvaNac = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.id_tipo_impuesto,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto)
	WHERE (iva.tipo IN (1,8,3) AND iva.estado = 1)
		OR iva.idIva IN (SELECT cxp_fact_iva.id_iva
						FROM cp_factura cxp_fact
							INNER JOIN cp_factura_iva cxp_fact_iva ON (cxp_fact.id_factura = cxp_fact_iva.id_factura)
						WHERE DATE(cxp_fact.fecha_origen) BETWEEN %s AND %s
						
						UNION
						
						SELECT cxp_nc_iva.id_iva_notacredito
						FROM cp_notacredito cxp_nc
							INNER JOIN cp_notacredito_iva cxp_nc_iva ON (cxp_nc.id_notacredito = cxp_nc_iva.id_notacredito)
						WHERE DATE(cxp_nc.fecha_registro_notacredito) BETWEEN %s AND %s
						
						UNION
						
						SELECT cxp_nd_iva.id_iva
						FROM cp_notadecargo cxp_nd
							INNER JOIN cp_notacargo_iva cxp_nd_iva ON (cxp_nd.id_notacargo = cxp_nd_iva.id_notacargo)
						WHERE DATE(cxp_nd.fecha_origen_notacargo) BETWEEN %s AND %s)
	ORDER BY tipo_imp.tipo_impuesto DESC, iva.iva DESC, iva.activo DESC",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	$rsIvaNac = mysql_query($queryIvaNac);
	if (!$rsIvaNac) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsIvaNac = mysql_num_rows($rsIvaNac);
	
	$queryIvaImp = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto)
	WHERE (iva.tipo IN (1,8,3) AND iva.estado = 1 AND iva.activo = 1)
		OR iva.idIva IN (SELECT cxp_fact_iva.id_iva
						FROM cp_factura cxp_fact
							INNER JOIN cp_factura_iva cxp_fact_iva ON (cxp_fact.id_factura = cxp_fact_iva.id_factura)
						WHERE DATE(cxp_fact.fecha_origen) BETWEEN %s AND %s
						
						UNION
						
						SELECT cxp_nc_iva.id_iva_notacredito
						FROM cp_notacredito cxp_nc
							INNER JOIN cp_notacredito_iva cxp_nc_iva ON (cxp_nc.id_notacredito = cxp_nc_iva.id_notacredito)
						WHERE DATE(cxp_nc.fecha_registro_notacredito) BETWEEN %s AND %s
						
						UNION
						
						SELECT cxp_nd_iva.id_iva
						FROM cp_notadecargo cxp_nd
							INNER JOIN cp_notacargo_iva cxp_nd_iva ON (cxp_nd.id_notacargo = cxp_nd_iva.id_notacargo)
						WHERE DATE(cxp_nd.fecha_origen_notacargo) BETWEEN %s AND %s)
	ORDER BY tipo_imp.tipo_impuesto DESC, iva.iva DESC, iva.activo DESC",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	$rsIvaImp = mysql_query($queryIvaImp);
	if (!$rsIvaImp) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsIvaImp = mysql_num_rows($rsIvaImp);
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" style=\"font-size:9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td rowspan=\"3\">".utf8_decode("Fecha del Documento")."</td>
					<td rowspan=\"3\">".utf8_decode("Fecha de Registro")."</td>
					<td rowspan=\"3\">".utf8_decode("Tipo de Documento")."</td>
					<td rowspan=\"3\">".utf8_decode("Nro. de Documento")."</td>
					<td rowspan=\"3\">".utf8_decode("Nro. de Control")."</td>
					<td rowspan=\"3\">".utf8_decode("Codigo Proveedor")."</td>
					<td rowspan=\"3\">".utf8_decode($spanProvCxP)."</td>
					<td rowspan=\"3\">".utf8_decode("Proveedor")."</td>
					<td rowspan=\"3\">".utf8_decode("Nro. Nota de Crédito / Débito")."</td>
					<td rowspan=\"3\">".utf8_decode("Numero de Documento Afectado")."</td>
					<td rowspan=\"3\">".utf8_decode("Fecha Comprobante de Retencion")."</td>
					<td rowspan=\"3\">".utf8_decode("Numero de Comprobante de Retencion")."</td>
					<td rowspan=\"3\">".utf8_decode("Total de Compras Incluyendo el Impuesto")."</td>
					<td rowspan=\"3\">".utf8_decode("Compras Exentas")."</td>
					<td rowspan=\"3\">".utf8_decode("Compras Exoneradas")."</td>
					<td colspan=\"".($totalRowsIvaNac * 2)."\">".utf8_decode("Compras Internas Nacionales")."</td>
					<td colspan=\"".(2 + ($totalRowsIvaImp * 2))."\">".utf8_decode("Compras Internas Importadas")."</td>
					<td rowspan=\"3\">".utf8_decode("Impuesto Retenido")."</td>";
	$htmlTh .= "</tr>";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$contPosNac = 0;
	while ($rowIvaNac = mysql_fetch_array($rsIvaNac)) {
		$contPosNac += 2;
		
		$htmlTh .= "<td rowspan=\"2\" title=\"".($contPosNac-2)."\">"."Base Imponible"."</td>
					<td>".($rowIvaNac['tipo_impuesto'])."</td>";
	}
		$htmlTh .= "<td rowspan=\"2\">"."Nro. de Planilla de Importacion"."</td>
					<td rowspan=\"2\">"."Nro. de Expediente"."</td>";
	$contPosImp = 0;
	while ($rowIvaImp = mysql_fetch_array($rsIvaImp)) {
		$contPosImp += 2;
		
		$htmlTh .= "<td rowspan=\"2\" title=\"".($contPosImp-2)."\">"."Base Imponible"."</td>
					<td>".($rowIvaImp['tipo_impuesto'])."</td>";
	}
	$htmlTh .= "</tr>";
	
	// COLUMNAS DE IVA DE COMPRAS INTERNAS NACIONALES
	$arrayIvaNac = NULL;
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$rsIvaNac = mysql_query($queryIvaNac);
	if (!$rsIvaNac) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$contPosNac = 0;
	while ($rowIvaNac = mysql_fetch_array($rsIvaNac)) {
		$contPosNac += 2;
			
		$arrayIvaNac[$rowIvaNac['idIva']] = array(
			"id_iva" => $rowIvaNac['idIva'],				// ID IVA
			"porc_iva" => $rowIvaNac['iva'],				// IVA
			"tipo_iva" => $rowIvaNac['id_tipo_impuesto'],
			"predeterminado_iva" => $rowIvaNac['activo'],	// PREDETERMINADO
			"pos_total_iva" => $contPosNac-1,				// POSICION DEL MONTO DEL IVA
			"pos_base_iva" => $contPosNac-2);				// POSICION DE LA BASE IMPONIBLE DEL IVA
		
		$htmlTh .= "<td title=\"".($contPosNac-1)."\">".$rowIvaNac['iva']."%</td>";
	}
	
	// COLUMNAS DE IVA DE COMPRAS INTERNAS IMPORTADAS
	$arrayIvaImp = NULL;
	$rsIvaImp = mysql_query($queryIvaImp);
	if (!$rsIvaImp) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$contPosImp = 0;
	while ($rowIvaImp = mysql_fetch_array($rsIvaImp)) {	
		$contPosImp += 2;
		
		$arrayIvaImp[$rowIvaImp['idIva']] = array(
			"id_iva" => $rowIvaImp['idIva'],				// ID IVA
			"porc_iva" => $rowIvaImp['iva'],				// IVA
			"tipo_iva" => $rowIvaImp['id_tipo_impuesto'],
			"predeterminado_iva" => $rowIvaImp['activo'],	// PREDETERMINADO
			"pos_total_iva" => $contPosImp-1,				// POSICION DEL MONTO DEL IVA
			"pos_base_iva" => $contPosImp-2);				// POSICION DE LA BASE IMPONIBLE DEL IVA
		
		$htmlTh .= "<td title=\"".($contPosImp-1)."\">".$rowIvaImp['iva']."%</td>";
	}
	$htmlTh .= "</tr>";
	
	if ($totalRows > 0) {
		while ($row = mysql_fetch_assoc($rsLimit)) {
			$sqlBusq = "";
			$sqlBusq2 = "";
			$sqlBusq3 = "";
			if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
				$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxp_fact.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
				
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
				$sqlBusq2 .= $cond.sprintf("(cxp_nc.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxp_nc.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
				
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
				$sqlBusq3 .= $cond.sprintf("(cxp_nd.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxp_nd.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
			}
			
			if ($valCadBusq[3] != -1 && $valCadBusq[3] != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
				$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
					valTpDato($valCadBusq[3], "campo"));
				
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
				$sqlBusq2 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
					valTpDato($valCadBusq[3], "campo"));
				
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
				$sqlBusq3 .= $cond.sprintf("cxp_nd.id_modulo IN (%s)",
					valTpDato($valCadBusq[3], "campo"));
			}
			
			$queryEstadoCuenta = sprintf("
			SELECT
				cxp_fact.fecha_factura_proveedor,
				cxp_fact.fecha_origen,
				cxp_fact.id_modulo,
				modulo.descripcionModulo,
				cxp_ec.*
			FROM cp_estado_cuenta cxp_ec
				INNER JOIN cp_factura cxp_fact ON (cxp_ec.idDocumento = cxp_fact.id_factura)
				INNER JOIN pg_modulos modulo ON (cxp_fact.id_modulo = modulo.id_modulo)
			WHERE cxp_ec.tipoDocumento LIKE 'FA'
				AND %s = %s
				AND DATE(cxp_fact.fecha_origen) BETWEEN %s AND %s %s
			
			UNION
			
			SELECT
				cxp_nc.fecha_notacredito,
				cxp_nc.fecha_registro_notacredito,
				cxp_nc.id_departamento_notacredito AS id_modulo,
				modulo.descripcionModulo,
				cxp_ec.*
			FROM cp_estado_cuenta cxp_ec
				INNER JOIN cp_notacredito cxp_nc ON (cxp_ec.idDocumento = cxp_nc.id_notacredito)
				INNER JOIN pg_modulos modulo ON (cxp_nc.id_departamento_notacredito = modulo.id_modulo)
			WHERE cxp_ec.tipoDocumento LIKE 'NC'
				AND %s = %s
				AND DATE(cxp_nc.fecha_registro_notacredito) BETWEEN %s AND %s %s
			
			UNION
			
			SELECT
				cxp_nd.fecha_notacargo,
				cxp_nd.fecha_origen_notacargo,
				cxp_nd.id_modulo,
				modulo.descripcionModulo,
				cxp_ec.*
			FROM cp_estado_cuenta cxp_ec
				INNER JOIN cp_notadecargo cxp_nd ON (cxp_ec.idDocumento = cxp_nd.id_notacargo)
				INNER JOIN pg_modulos modulo ON (cxp_nd.id_modulo = modulo.id_modulo)
			WHERE cxp_ec.tipoDocumento LIKE 'ND'
				AND %s = %s
				AND DATE(cxp_nd.fecha_origen_notacargo) BETWEEN %s AND %s %s
			ORDER BY 1 ASC, 8 ASC",
				(($idCampoOrden == 1) ? "cxp_fact.fecha_factura_proveedor" : "cxp_fact.fecha_origen"),
					valTpDato(date("Y-m-d", strtotime($row['fecha_grupo'])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				$sqlBusq,
				(($idCampoOrden == 1) ? "cxp_nc.fecha_notacredito" : "cxp_nc.fecha_registro_notacredito"),
					valTpDato(date("Y-m-d", strtotime($row['fecha_grupo'])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				$sqlBusq2,
				(($idCampoOrden == 1) ? "cxp_nd.fecha_notacargo" : "cxp_nd.fecha_origen_notacargo"),
					valTpDato(date("Y-m-d", strtotime($row['fecha_grupo'])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				$sqlBusq3);
			$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
			if (!$rsEstadoCuenta) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsEstadoCuenta = mysql_num_rows($rsEstadoCuenta);
			
			$arrayTotalDia = NULL;
			$arrayTotalIvaDocNac = NULL;
			$arrayTotalIvaDocImp = NULL;
			if ($totalRowsEstadoCuenta > 0) {
				while($rowEstadoCuenta = mysql_fetch_array($rsEstadoCuenta)) {
					
					for ($i = 0; $i < $contPosNac; $i++) {
						$arrayIvaDocNac[$i] = "";
					}
					for ($i = 0; $i < $contPosImp; $i++) {
						$arrayIvaDocImp[$i] = "";
					}
					
					
					$sqlBusq = "";
					$sqlBusq2 = "";
					$sqlBusq3 = "";
					if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
						$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
						$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = cxp_fact.id_empresa))",
							valTpDato($valCadBusq[0], "int"),
							valTpDato($valCadBusq[0], "int"));
						
						$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
						$sqlBusq2 .= $cond.sprintf("(cxp_nc.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = cxp_nc.id_empresa))",
							valTpDato($valCadBusq[0], "int"),
							valTpDato($valCadBusq[0], "int"));
						
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
						$sqlBusq3 .= $cond.sprintf("(cxp_nd.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = cxp_nd.id_empresa))",
							valTpDato($valCadBusq[0], "int"),
							valTpDato($valCadBusq[0], "int"));
					}
					
					if ($valCadBusq[3] != -1 && $valCadBusq[3] != "") {
						$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
						$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
							valTpDato($valCadBusq[3], "campo"));
						
						$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
						$sqlBusq2 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
							valTpDato($valCadBusq[3], "campo"));
						
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
						$sqlBusq3 .= $cond.sprintf("cxp_nd.id_modulo IN (%s)",
							valTpDato($valCadBusq[3], "campo"));
					}
					
					if ($rowEstadoCuenta['tipoDocumento'] == "FA") {
						$queryDocumento = sprintf("SELECT cxp_fact.*,
							prov.id_proveedor,
							CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
							CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
							prov.nombre AS nombre_proveedor
						FROM cp_factura cxp_fact
							INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
						WHERE cxp_fact.id_factura = %s
							AND cxp_fact.aplica_libros = 1 %s",
							valTpDato($rowEstadoCuenta['idDocumento'], "int"),
							$sqlBusq);
						
					} else if ($rowEstadoCuenta['tipoDocumento'] == "NC") {
						$queryDocumento = sprintf("SELECT cxp_nc.*,
							prov.id_proveedor,
							CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
							CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
							prov.nombre AS nombre_proveedor
						FROM cp_notacredito cxp_nc
							INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)
						WHERE cxp_nc.id_notacredito = %s
							AND cxp_nc.aplica_libros_notacredito = 1 %s",
							valTpDato($rowEstadoCuenta['idDocumento'], "int"),
							$sqlBusq2);
						
					} else if ($rowEstadoCuenta['tipoDocumento'] == "ND") {
						$queryDocumento = sprintf("SELECT cxp_nd.*,
							prov.id_proveedor,
							CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
							CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
							prov.nombre AS nombre_proveedor
						FROM cp_notadecargo cxp_nd
							INNER JOIN cp_proveedor prov ON (cxp_nd.id_proveedor = prov.id_proveedor)
						WHERE cxp_nd.id_notacargo = %s
							AND cxp_nd.aplica_libros_notacargo = 1 %s",
							valTpDato($rowEstadoCuenta['idDocumento'], "int"),
							$sqlBusq3);
					}
					$rsDocumento = mysql_query($queryDocumento);
					if (!$rsDocumento) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsDocumento = mysql_num_rows($rsDocumento);
					$rowDocumento = mysql_fetch_array($rsDocumento);
					
					$idNotaCredito = "";
					$nroNotaCredito = "-";
					$nroControlNotaCredito = "-";
					$idModuloNotaCredito = "";
					$descripcionModuloNotaCredito = "";
					$idDocumentoAfectado = "";
					$nroDocumentoAfectado = "-";
					$nroControlDocumentoAfectado = "-";
					$idModuloDocumentoAfectado = "";
					$descripcionModuloDocumentoAfectado = "";
					$fechaComprobanteRetencion75 = "-";
					$nroComprobanteRetencion75 = "-";
					$nroComprobanteRetencion3 = "-";
					$sumaRetenciones = 0;
					$numeroPlanillaImportacion = "-";
					$numeroExpediente = "-";
					$auxImportacion = false;
					if ($rowEstadoCuenta['tipoDocumento'] == "FA" && $totalRowsDocumento > 0) {
						$fechaRegistro = date(spanDateFormat, strtotime($rowDocumento['fecha_origen']));
						$fechaDocumento = date(spanDateFormat, strtotime($rowDocumento['fecha_factura_proveedor']));
						$idDocumento = $rowDocumento['id_factura'];
						$tipoDocumento = "FA";
						$nroFactura = $rowDocumento['numero_factura_proveedor'];
						$nroControl = $rowDocumento['numero_control_factura'];
						
						// VERIFICA SI LA FACTURA ES DE IMPORTACION
						$queryFacturaImportacion = sprintf("SELECT * FROM cp_factura_importacion WHERE id_factura = %s;",
							valTpDato($rowDocumento['id_factura'], "int"));
						$rsFacturaImportacion = mysql_query($queryFacturaImportacion);
						if (!$rsFacturaImportacion) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsFacturaImportacion = mysql_num_rows($rsFacturaImportacion);
						$rowFacturaImportacion = mysql_fetch_array($rsFacturaImportacion);
						$auxImportacion = false;
						if ($totalRowsFacturaImportacion > 0) {
							$numeroPlanillaImportacion = $rowFacturaImportacion['numero_planilla_importacion'];
							$numeroExpediente = $rowFacturaImportacion['numero_expediente'];
							
							$auxImportacion = true;
						}
						
						$queryRetencionCabecera = sprintf("SELECT *,
							COUNT(retencion_det.idFactura) AS cant_retenciones,
							SUM(retencion_det.IvaRetenido) AS total_iva_retenido
						FROM cp_retencioncabezera retencion
							INNER JOIN cp_proveedor prov ON (retencion.idProveedor = prov.id_proveedor)
							INNER JOIN cp_retenciondetalle retencion_det ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera)
							INNER JOIN cp_factura cxp_fact2 ON (retencion_det.idFactura = cxp_fact2.id_factura)
						WHERE retencion_det.idFactura = %s
							AND retencion_det.id_nota_credito IS NULL
						GROUP by retencion.idRetencionCabezera;",
							valTpDato($rowDocumento['id_factura'], "int"));
						$rsRetencionCabecera = mysql_query($queryRetencionCabecera);
						if (!$rsRetencionCabecera) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsRetenciones = mysql_num_rows($rsRetencionCabecera);
						$rowRetencionCabecera = mysql_fetch_array($rsRetencionCabecera);
						if ($rowDocumento['id_factura'] == $rowRetencionCabecera['idFactura']) {
							$fechaComprobanteRetencion75 = date(spanDateFormat, strtotime($rowRetencionCabecera['fechaComprobante']));
							$nroComprobanteRetencion75 = $rowRetencionCabecera['numeroComprobante'];
							$sumaRetenciones = $rowRetencionCabecera['total_iva_retenido'];
						}
						
						
						// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
						$queryFAGasto = sprintf("SELECT cxp_fact_gasto.*,
							(SELECT SUM(cxp_fact_gasto_impsto.impuesto) FROM cp_factura_gasto_impuesto cxp_fact_gasto_impsto
							WHERE cxp_fact_gasto_impsto.id_factura_gasto = cxp_fact_gasto.id_factura_gasto) AS impuesto
						FROM cp_factura_gasto cxp_fact_gasto
						WHERE cxp_fact_gasto.id_factura = %s
							AND cxp_fact_gasto.id_modo_gasto IN (1,3);",
							valTpDato($rowDocumento['id_factura'], "int"));
						$rsFAGasto = mysql_query($queryFAGasto);
						if (!$rsFAGasto) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalGastoAplicaLibro = 0;
						$totalGastoNoAplicaLibro = 0;
						$totalGastoExentoNoAplicaLibro = 0;
						while($rowFAGasto = mysql_fetch_array($rsFAGasto)) {
							if ($rowFAGasto['aplica_libro'] == 1) {
								$totalGastoAplicaLibro += $rowFAGasto['monto'];
							} else {
								$totalGastoNoAplicaLibro += $rowFAGasto['monto'];
								if (!($rowFAGasto['impuesto'] > 0)) {
									$totalGastoExentoNoAplicaLibro += $rowFAGasto['monto'];
								}
							}
						}
						
						// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
						$queryFAIva = sprintf("SELECT * FROM cp_factura_iva WHERE id_factura = %s;",
							valTpDato($rowDocumento['id_factura'], "int"));
						$rsFAIva = mysql_query($queryFAIva);
						if (!$rsFAIva) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$montoIva = 0;
						$montoBaseImpIvas = 0;
						$montoIvaLujo = 0;
						while($rowFAIva = mysql_fetch_array($rsFAIva)) {
							$idIva = $rowFAIva['id_iva'];
							$montoIva += $rowFAIva['subtotal_iva'];
							if ($rowFAIva['lujo'] == 1) {
								$montoIvaLujo = $rowFAIva['subtotal_iva'];
							} else {
								$montoBaseImpIvas += $rowFAIva['base_imponible'];
							}
							
							if ($auxImportacion == false){
								foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
									if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_total_iva']] += doubleval($rowFAIva['subtotal_iva']);
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_base_iva']] += doubleval($rowFAIva['base_imponible']);
									}
								}
							} else {
								foreach ($arrayIvaImp as $indiceIvaImp => $valorIvaImp) {
									if ($idIva == $arrayIvaImp[$indiceIvaImp]['id_iva']) {
										$arrayIvaDocImp[$arrayIvaImp[$indiceIvaImp]['pos_total_iva']] += doubleval($rowFAIva['subtotal_iva']);
										$arrayIvaDocImp[($arrayIvaImp[$indiceIvaImp]['pos_base_iva'])] += doubleval($rowFAIva['base_imponible']);
									}
								}
							}
							
							switch($rowDocumento['id_modulo']) {
								case 0 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaRepuestos[$idIva]['subtotal_iva'] += doubleval($rowFAIva['subtotal_iva']);
											$arrayTotalIvaRepuestos[$idIva]['base_imponible'] += doubleval($rowFAIva['base_imponible']);
										}
									}
									break;
								case 1 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaServicios[$idIva]['subtotal_iva'] += doubleval($rowFAIva['subtotal_iva']);
											$arrayTotalIvaServicios[$idIva]['base_imponible'] += doubleval($rowFAIva['base_imponible']);
										}
									}
									break;
								case 2 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaVehiculos[$idIva]['subtotal_iva'] += doubleval($rowFAIva['subtotal_iva']);
											$arrayTotalIvaVehiculos[$idIva]['base_imponible'] += doubleval($rowFAIva['base_imponible']);
										}
									}
									break;
								default :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaAdmon[$idIva]['subtotal_iva'] += doubleval($rowFAIva['subtotal_iva']);
											$arrayTotalIvaAdmon[$idIva]['base_imponible'] += doubleval($rowFAIva['base_imponible']);
										}
									}
							}
						}
						
						$totalCompraConIva = ($rowDocumento['subtotal_factura'] - $rowDocumento['subtotal_descuento']) + $totalGastoAplicaLibro + $montoIva +$rowFacturaImportacion['total_advalorem_diferencia'];
						
						$comprasExentas = ($rowDocumento['monto_exento'] - $totalGastoExentoNoAplicaLibro);
						$comprasExonerado = $rowDocumento['monto_exonerado'];
						
						switch($rowDocumento['id_modulo']) {
							case 0 :
								$totalBaseIvaRepuestos += $montoBaseImpIvas;
								$totalSubtotalIvaRepuestos += $montoIva;
								$totalExentoRepuestos += $comprasExentas;
								$totalExoneradoRepuestos += $comprasExonerado;
								$totalRetenidoRepuestos += $sumaRetenciones;
								
								if ($nroFactura < $primerNumeroFacturaRepuestos || $primerNumeroFacturaRepuestos == '-')
									$primerNumeroFacturaRepuestos = $nroFactura;
								if ($nroFactura > $ultimoNumeroFacturaRepuesto || $ultimoNumeroFacturaRepuesto == '-')
									$ultimoNumeroFacturaRepuesto = $nroFactura;
								break;
							case 1 :
								$totalBaseIvaServicios += $montoBaseImpIvas;
								$totalSubtotalIvaServicios += $montoIva;
								$totalExentoServicios += $comprasExentas;
								$totalExoneradoServicios += $comprasExonerado;
								$totalRetenidoServicios += $sumaRetenciones;
								
								if ($nroFactura < $primerNumeroFacturaServicios || $primerNumeroFacturaServicios == '-')
									$primerNumeroFacturaServicios = $nroFactura;
								if ($nroFactura > $ultimoNumeroFacturaServicio || $ultimoNumeroFacturaServicio == '-')
									$ultimoNumeroFacturaServicio = $nroFactura;
								break;
							case 2 :
								$totalBaseIvaVehiculos += $montoBaseImpIvas;
								$totalIvaVehiculos += $montoIva;
								$totalExentoVehiculos += $comprasExentas;
								$totalExoneradoVehiculos += $comprasExonerado;
								$totalFacturaVehiculos += $totalCompraConIva;
								
								if ($montoIvaLujo > 0) {
									$totalBaseIvaVehiculosGralMasAdic += $montoBaseImpIvas;
									$totalSubtotalIvaVehiculosGralMasAdic += $montoIva;
									$totalExentoIvaVehiculosGralMasAdic += $comprasExentas;
									$totalExoneradoIvaVehiculosGralMasAdic += $comprasExonerado;
									$totalRetenidoIvaVehiculosGralMasAdic += $sumaRetenciones;
								
									if ($nroFactura < $primerNroFacturaIvaVehiculosGralMasAdic || $primerNroFacturaIvaVehiculosGralMasAdic == '-')
										$primerNroFacturaIvaVehiculosGralMasAdic = $nroFactura;
									if ($nroFactura > $ultimoNroFacturaIvaVehiculosGralMasAdic || $ultimoNroFacturaIvaVehiculosGralMasAdic == '-')
										$ultimoNroFacturaIvaVehiculosGralMasAdic = $nroFactura;
								} else {
									$totalBaseIvaVehiculosGralSolo += $montoBaseImpIvas;
									$totalSubtotalIvaVehiculosGralSolo += $montoIva;
									$totalExentoIvaVehiculosGralSolo += $comprasExentas;
									$totalExoneradoIvaVehiculosGralSolo += $comprasExonerado;
									$totalRetenidoIvaVehiculosGralSolo += $sumaRetenciones;
								
									if ($nroFactura < $primerNroFacturaIvaVehiculosGralSolo || $primerNroFacturaIvaVehiculosGralSolo == '-')
										$primerNroFacturaIvaVehiculosGralSolo = $nroFactura;
									if ($nroFactura > $ultimoNroFacturaIvaVehiculosGralSolo || $ultimoNroFacturaIvaVehiculosGralSolo == '-')
										$ultimoNroFacturaIvaVehiculosGralSolo = $nroFactura;
								}
								break;
							default :
								$totalBaseIvaAdmon += $montoBaseImpIvas;
								$totalSubtotalIvaAdmon += $montoIva;
								$totalExentoAdmon += $comprasExentas;
								$totalExoneradoAdmon += $comprasExonerado;
								$totalRetenidoAdmon += $sumaRetenciones;
								
								if ($nroFactura < $primerNumeroFacturaAdmon || $primerNumeroFacturaAdmon == '-')
									$primerNumeroFacturaAdmon = $nroFactura;
								if ($nroFactura > $ultimoNumeroFacturaAdmon || $ultimoNumeroFacturaAdmon == '-')
									$ultimoNumeroFacturaAdmon = $nroFactura;
						}
					} else if ($rowEstadoCuenta['tipoDocumento'] == "NC" && $totalRowsDocumento > 0) {
						$fechaRegistro = date(spanDateFormat, strtotime($rowDocumento['fecha_registro_notacredito']));
						$fechaDocumento = date(spanDateFormat, strtotime($rowDocumento['fecha_notacredito']));
						$idDocumento = $rowDocumento['id_notacredito'];
						$tipoDocumento = "NC";
						$nroFactura = "-";
						$nroControl = $rowDocumento['numero_control_notacredito'];
						
						$idNotaCredito = $rowDocumento['id_notacredito'];
						$nroNotaCredito = $rowDocumento['numero_nota_credito'];
						$nroControlNotaCredito = $rowDocumento['numero_control_notacredito'];
						$idModuloNotaCredito = $rowDocumento['id_departamento_notacredito'];
						$descripcionModuloNotaCredito = $rowEstadoCuenta['descripcionModulo'];
						
						if ($rowDocumento['tipo_documento'] == "ND") {
							// BUSCA LOS DATOS DE LA NOTA DE CARGO QUE DEVOLVIO
							$queryND = sprintf("SELECT * FROM cp_notadecargo WHERE id_notacargo = %s;",
								valTpDato($rowDocumento['id_documento'], "int"));
							$rsND = mysql_query($queryND);
							if (!$rsND) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$rowND = mysql_fetch_array($rsND);
						} else if ($rowDocumento['tipo_documento'] == "FA") {
							// BUSCA LOS DATOS DE LA FACTURA QUE DEVOLVIO
							$queryFA = sprintf("SELECT *
							FROM cp_factura cxp_fact
								INNER JOIN pg_modulos modulo ON (cxp_fact.id_modulo = modulo.id_modulo)
							WHERE id_factura = %s;",
								valTpDato($rowDocumento['id_documento'], "int"));
							$rsFA = mysql_query($queryFA);
							if (!$rsFA) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$rowFA = mysql_fetch_array($rsFA);
							
							$idDocumentoAfectado = $rowFA['id_factura'];
							$nroDocumentoAfectado = $rowFA['numero_factura_proveedor'];
							$nroControlDocumentoAfectado = $rowFA['numero_control_factura'];
							$idModuloDocumentoAfectado = $rowFA['id_modulo'];
							$descripcionModuloDocumentoAfectado = $rowFA['descripcionModulo'];
							$tipoDocumentoAfectado = "FA";
						
							// VERIFICA SI LA DEVOLUCION ES DE UNA FACTURA ES DE IMPORTACION
							$queryFacturaImportacion = sprintf("SELECT * FROM cp_factura_importacion WHERE id_factura = %s;",
								valTpDato($rowDocumento['id_documento'], "int"));
							$rsFacturaImportacion = mysql_query($queryFacturaImportacion);
							if (!$rsFacturaImportacion) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRowsFacturaImportacion = mysql_num_rows($rsFacturaImportacion);
							$rowFacturaImportacion = mysql_fetch_array($rsFacturaImportacion);
							if ($totalRowsFacturaImportacion > 0) {
								$numeroPlanillaImportacion = $rowFacturaImportacion['numero_planilla_importacion'];
								$numeroExpediente = $rowFacturaImportacion['numero_expediente'];
								
								$auxImportacion = true;
							}
						}
						
						$queryRetencionCabecera = sprintf("SELECT *,
							COUNT(retencion_det.idFactura) AS cant_retenciones,
							SUM(retencion_det.IvaRetenido) AS total_iva_retenido
						FROM cp_retencioncabezera retencion
							INNER JOIN cp_proveedor prov ON (retencion.idProveedor = prov.id_proveedor)
							INNER JOIN cp_retenciondetalle retencion_det ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera)
							INNER JOIN cp_factura cxp_fact2 ON (retencion_det.idFactura = cxp_fact2.id_factura)
						WHERE retencion_det.id_nota_credito = %s
							AND retencion_det.id_nota_credito IS NOT NULL
						GROUP BY retencion.idRetencionCabezera;",
							valTpDato($rowDocumento['id_notacredito'], "int"));
						$rsRetencionCabecera = mysql_query($queryRetencionCabecera);
						if (!$rsRetencionCabecera) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsRetenciones = mysql_num_rows($rsRetencionCabecera);
						$rowRetencionCabecera = mysql_fetch_array($rsRetencionCabecera);
						if ($rowDocumento['id_notacredito'] == $rowRetencionCabecera['id_nota_credito']) {
							$fechaComprobanteRetencion75 = date(spanDateFormat, strtotime($rowRetencionCabecera['fechaComprobante']));
							$nroComprobanteRetencion75 = $rowRetencionCabecera['numeroComprobante'];
							$sumaRetenciones = $rowRetencionCabecera['total_iva_retenido'];
						}
						
						
						// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
						$queryNCGasto = sprintf("SELECT cxp_nc_gasto.*,
							(SELECT SUM(cxp_nc_gasto_impsto.impuesto) FROM cp_notacredito_gasto_impuesto cxp_nc_gasto_impsto
							WHERE cxp_nc_gasto_impsto.id_notacredito_gasto = cxp_nc_gasto.id_notacredito_gastos) AS impuesto
						FROM cp_notacredito_gastos cxp_nc_gasto
						WHERE cxp_nc_gasto.id_notacredito = %s
							AND cxp_nc_gasto.id_modo_gasto IN (1,3);",
							valTpDato($rowDocumento['id_notacredito'], "int"));
						$rsNCGasto = mysql_query($queryNCGasto);
						if (!$rsNCGasto) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalGastoAplicaLibro = 0;
						$totalGastoNoAplicaLibro = 0;
						$totalGastoExentoNoAplicaLibro = 0;
						while($rowNCGasto = mysql_fetch_array($rsNCGasto)) {
							if ($rowNCGasto['aplica_libro'] == 1) {
								$totalGastoAplicaLibro += $rowNCGasto['monto_gasto_notacredito'];
							} else {
								$totalGastoNoAplicaLibro += $rowNCGasto['monto_gasto_notacredito'];
								if (!($rowNCGasto['impuesto'] > 0)) {
									$totalGastoExentoNoAplicaLibro += $rowNCGasto['monto_gasto_notacredito'];
								}
							}
						}
						
						// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
						$queryNCIva = sprintf("SELECT * FROM cp_notacredito_iva WHERE id_notacredito = %s;",
							valTpDato($rowDocumento['id_notacredito'], "int"));
						$rsNCIva = mysql_query($queryNCIva);
						if (!$rsNCIva) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$montoIva = 0;
						$montoBaseImpIvas = 0;
						$montoIvaLujo = 0;
						while($rowNCIva = mysql_fetch_array($rsNCIva)){
							$idIva = $rowNCIva['id_iva_notacredito'];
							$montoIva += $rowNCIva['subtotal_iva_notacredito'];
							if ($rowNCIva['lujo'] == 1) {
								$montoIvaLujo = $rowNCIva['subtotal_iva_notacredito'];
							} else {
								$montoBaseImpIvas += $rowNCIva['baseimponible_notacredito'];
							}
							
							if ($auxImportacion == false){
								foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
									if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_total_iva']] += doubleval($rowNCIva['subtotal_iva_notacredito']);
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_base_iva']] += doubleval($rowNCIva['baseimponible_notacredito']);
									}
								}
							} else {
								foreach ($arrayIvaImp as $indiceIvaImp => $valorIvaImp) {
									if ($idIva == $arrayIvaImp[$indiceIvaImp]['id_iva']) {
										$arrayIvaDocImp[$arrayIvaImp[$indiceIvaImp]['pos_total_iva']] += doubleval($rowNCIva['subtotal_iva_notacredito']);
										$arrayIvaDocImp[($arrayIvaImp[$indiceIvaImp]['pos_base_iva'])] += doubleval($rowNCIva['baseimponible_notacredito']);
									}
								}
							}
							
							switch($rowDocumento['id_departamento_notacredito']) {
								case 0 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaRepuestos[$idIva]['subtotal_iva'] += (-1) * doubleval($rowNCIva['subtotal_iva_notacredito']);
											$arrayTotalIvaRepuestos[$idIva]['base_imponible'] += (-1) * doubleval($rowNCIva['baseimponible_notacredito']);
										}
									}
									break;
								case 1 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaServicios[$idIva]['subtotal_iva'] += (-1) * doubleval($rowNCIva['subtotal_iva_notacredito']);
											$arrayTotalIvaServicios[$idIva]['base_imponible'] += (-1) * doubleval($rowNCIva['baseimponible_notacredito']);
										}
									}
									break;
								case 2 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaVehiculos[$idIva]['subtotal_iva'] += (-1) * doubleval($rowNCIva['subtotal_iva_notacredito']);
											$arrayTotalIvaVehiculos[$idIva]['base_imponible'] += (-1) * doubleval($rowNCIva['baseimponible_notacredito']);
										}
									}
									break;
								default :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaAdmon[$idIva]['subtotal_iva'] += (-1) * doubleval($rowNCIva['subtotal_iva_notacredito']);
											$arrayTotalIvaAdmon[$idIva]['base_imponible'] += (-1) * doubleval($rowNCIva['baseimponible_notacredito']);
										}
									}
							}
						}
						
						$totalCompraConIva = ($rowDocumento['subtotal_notacredito'] - $rowDocumento['subtotal_descuento']) + $totalGastoAplicaLibro + $montoIva;
						
						$comprasExentas = ($rowDocumento['monto_exento_notacredito'] - $totalGastoExentoNoAplicaLibro);
						$comprasExonerado = $rowDocumento['monto_exonerado_notacredito'];
						
						switch($rowDocumento['id_departamento_notacredito']) {
							case 0 :
								$totalBaseIvaRepuestos += (-1) * $montoBaseImpIvas;
								$totalSubtotalIvaRepuestos += (-1) * $montoIva;
								$totalExentoRepuestos += (-1) * $comprasExentas;
								$totalExoneradoRepuestos += (-1) * $comprasExonerado;
								$totalRetenidoRepuestos += $sumaRetenciones;
								break;
							case 1 :
								$totalBaseIvaServicios += (-1) * $montoBaseImpIvas;
								$totalSubtotalIvaServicios += (-1) * $montoIva;
								$totalExentoServicios += (-1) * $comprasExentas;
								$totalExoneradoServicios += (-1) * $comprasExonerado;
								$totalRetenidoServicios += $sumaRetenciones;
								break;
							case 2 :
								$totalBaseIvaVehiculos += (-1) * $montoBaseImpIvas;
								$totalIvaVehiculos += (-1) * $montoIva;
								$totalExentoVehiculos += (-1) * $comprasExentas;
								$totalExoneradoVehiculos += (-1) * $comprasExonerado;
								$totalFacturaVehiculos += (-1) * $totalCompraConIva;
								
								if ($montoIvaLujo > 0) {
									$totalBaseIvaVehiculosGralMasAdic += (-1) * $montoBaseImpIvas;
									$totalSubtotalIvaVehiculosGralMasAdic += (-1) * $montoIva;
									$totalExentoIvaVehiculosGralMasAdic += (-1) * $comprasExentas;
									$totalExoneradoIvaVehiculosGralMasAdic += (-1) * $comprasExonerado;
									$totalRetenidoIvaVehiculosGralMasAdic += $sumaRetenciones;
								} else {
									$totalBaseIvaVehiculosGralSolo += (-1) * $montoBaseImpIvas;
									$totalSubtotalIvaVehiculosGralSolo += (-1) * $montoIva;
									$totalExentoIvaVehiculosGralSolo += (-1) * $comprasExentas;
									$totalExoneradoIvaVehiculosGralSolo += (-1) * $comprasExonerado;
									$totalRetenidoIvaVehiculosGralSolo += $sumaRetenciones;
								}
								break;
							default :
								$totalBaseIvaAdmon += (-1) * $montoBaseImpIvas;
								$totalSubtotalIvaAdmon += (-1) * $montoIva;
								$totalExentoAdmon += (-1) * $comprasExentas;
								$totalExoneradoAdmon += (-1) * $comprasExonerado;
								$totalRetenidoAdmon += $sumaRetenciones;
						}
					} else if ($rowEstadoCuenta['tipoDocumento'] == "ND" && $totalRowsDocumento > 0) {
						$fechaRegistro = date(spanDateFormat, strtotime($rowDocumento['fecha_origen_notacargo']));
						$fechaDocumento = date(spanDateFormat, strtotime($rowDocumento['fecha_notacargo']));
						$idDocumento = $rowDocumento['id_notacargo'];
						$tipoDocumento = "ND";
						$nroFactura = $rowDocumento['numero_notacargo'];
						$nroControl = $rowDocumento['numero_control_notacargo'];
						
						
						// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
						$queryNDGasto = sprintf("SELECT cxp_nd_gasto.*,
							(SELECT SUM(cxp_nd_gasto_impsto.impuesto) FROM cp_notacargo_gasto_impuesto cxp_nd_gasto_impsto
							WHERE cxp_nd_gasto_impsto.id_notacargo_gasto = cxp_nd_gasto.id_notacargo_gastos) AS impuesto
						FROM cp_notacargo_gastos cxp_nd_gasto
						WHERE cxp_nd_gasto.id_notacargo = %s
							AND cxp_nd_gasto.id_modo_gasto IN (1,3);",
							valTpDato($rowDocumento['id_notacargo'], "int"));
						$rsNDGasto = mysql_query($queryNDGasto);
						if (!$rsNDGasto) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalGastoAplicaLibro = 0;
						$totalGastoNoAplicaLibro = 0;
						$totalGastoExentoNoAplicaLibro = 0;
						while ($rowNDGasto = mysql_fetch_array($rsNDGasto)) {
							if ($rowNDGasto['aplica_libro'] == 1) {
								$totalGastoAplicaLibro += $rowNDGasto['monto'];
							} else {
								$totalGastoNoAplicaLibro += $rowNDGasto['monto'];
								if (!($rowNDGasto['impuesto'] > 0)) {
									$totalGastoExentoNoAplicaLibro += $rowNDGasto['monto'];
								}
							}
						}
						
						// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
						$queryNDIva = sprintf("SELECT * FROM cp_notacargo_iva WHERE id_notacargo = %s;",
							valTpDato($rowDocumento['idNotaCargo'], "int"));
						$rsNDIva = mysql_query($queryNDIva);
						if (!$rsNDIva) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$montoIva = 0;
						$montoBaseImpIvas = 0;
						$montoIvaLujo = 0;
						while($rowNDIva = mysql_fetch_array($rsNDIva)){
							$idIva = $rowNDIva['id_iva'];
							$montoIva += $rowNDIva['subtotal_iva'];
							if ($rowNDIva['lujo'] == 1) {
								$montoIvaLujo = $rowNDIva['subtotal_iva'];
							} else {
								$montoBaseImpIvas += $rowNDIva['baseimponible'];
							}
							
							if ($auxImportacion == false){
								foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
									if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_total_iva']] += doubleval($rowNDIva['subtotal_iva']);
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_base_iva']] += doubleval($rowNDIva['baseimponible']);
									}
								}
							} else {
								foreach ($arrayIvaImp as $indiceIvaImp => $valorIvaImp) {
									if ($idIva == $arrayIvaImp[$indiceIvaImp]['id_iva']) {
										$arrayIvaDocImp[$arrayIvaImp[$indiceIvaImp]['pos_total_iva']] += doubleval($rowNDIva['subtotal_iva']);
										$arrayIvaDocImp[($arrayIvaImp[$indiceIvaImp]['pos_base_iva'])] += doubleval($rowNDIva['baseimponible']);
									}
								}
							}
							
							switch($rowDocumento['idDepartamentoOrigenNotaCargo']) {
								case 0 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaRepuestos[$idIva]['subtotal_iva'] += doubleval($rowNDIva['subtotal_iva']);
											$arrayTotalIvaRepuestos[$idIva]['base_imponible'] += doubleval($rowNDIva['baseimponible']);
										}
									}
									break;
								case 1 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaServicios[$idIva]['subtotal_iva'] += doubleval($rowNDIva['subtotal_iva']);
											$arrayTotalIvaServicios[$idIva]['base_imponible'] += doubleval($rowNDIva['baseimponible']);
										}
									}
									break;
								case 2 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaVehiculos[$idIva]['subtotal_iva'] += doubleval($rowNDIva['subtotal_iva']);
											$arrayTotalIvaVehiculos[$idIva]['base_imponible'] += doubleval($rowNDIva['baseimponible']);
										}
									}
									break;
								default :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaAdmon[$idIva]['subtotal_iva'] += doubleval($rowNDIva['subtotal_iva']);
											$arrayTotalIvaAdmon[$idIva]['base_imponible'] += doubleval($rowNDIva['baseimponible']);
										}
									}
							}
						}
						
						$totalCompraConIva = ($rowDocumento['subtotal_notacargo'] - $rowDocumento['subtotal_descuento_notacargo']) + $totalGastoAplicaLibro + $montoIva;
						
						$comprasExentas = ($rowDocumento['monto_exento_notacargo'] - $totalGastoExentoNoAplicaLibro);
						$comprasExonerado = $rowDocumento['monto_exonerado_notacargo'];
						
						switch($rowDocumento['idDepartamentoOrigenNotaCargo']) {
							case 0 :
								$totalBaseIvaRepuestos += $montoBaseImpIvas;
								$totalSubtotalIvaRepuestos += $montoIva;
								$totalExentoRepuestos += $comprasExentas;
								$totalExoneradoRepuestos += $comprasExonerado;
								$totalRetenidoRepuestos += $sumaRetenciones;
								break;
							case 1 :
								$totalBaseIvaServicios += $montoBaseImpIvas;
								$totalSubtotalIvaServicios += $montoIva;
								$totalExentoServicios += $comprasExentas;
								$totalExoneradoServicios += $comprasExonerado;
								$totalRetenidoServicios += $sumaRetenciones;
								break;
							case 2 :
								$totalBaseIvaVehiculos += $montoBaseImpIvas;
								$totalIvaVehiculos += $montoIva;
								$totalExentoVehiculos += $comprasExentas;
								$totalExoneradoVehiculos += $comprasExonerado;
								$totalFacturaVehiculos += $totalCompraConIva;
								
								if ($montoIvaLujo > 0) {
									$totalBaseIvaVehiculosGralMasAdic += $montoBaseImpIvas;
									$totalSubtotalIvaVehiculosGralMasAdic += $montoIva;
									$totalExentoIvaVehiculosGralMasAdic += $comprasExentas;
									$totalExoneradoIvaVehiculosGralMasAdic += $comprasExonerado;
									$totalRetenidoIvaVehiculosGralMasAdic += $sumaRetenciones;
								} else {
									$totalBaseIvaVehiculosGralSolo += $montoBaseImpIvas;
									$totalSubtotalIvaVehiculosGralSolo += $montoIva;
									$totalExentoIvaVehiculosGralSolo += $comprasExentas;
									$totalExoneradoIvaVehiculosGralSolo += $comprasExonerado;
									$totalRetenidoIvaVehiculosGralSolo += $sumaRetenciones;
								}
								break;
							default :
								$totalBaseIvaAdmon += $montoBaseImpIvas;
								$totalSubtotalIvaAdmon += $montoIva;
								$totalExentoAdmon += $comprasExentas;
								$totalExoneradoAdmon += $comprasExonerado;
								$totalRetenidoAdmon += $sumaRetenciones;
						}
					}
					
					if ($rowDocumento['id_factura'] > 0 || $rowDocumento['id_notacredito'] > 0 || $rowDocumento['id_notacargo'] > 0) {
						$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila++;
						
						if ($rowDocumento['id_factura'] > 0) {
							$signo = 1;
						} else if ($rowDocumento['id_notacredito'] > 0) {
							$signo = (-1);
						} else if ($rowDocumento['id_notacargo'] > 0) {
							$signo = 1;
						}
						
						$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$fechaDocumento."</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$fechaRegistro."</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$tipoDocumento."</td>";
							$htmlTb .= "<td nowrap=\"nowrap\" title=\"".
								"Id Dcto.: ".$idDocumento.
								"\nNro. de Documento: ".$nroFactura.
								"\nNro. de Control: ".$nroControl.
								"\nMódulo: ".$rowEstadoCuenta['descripcionModulo']."\">";
								$htmlTb .= (strlen($aVerDcto) > 0 && $nroFactura != "-") ? "<a class=\"linkAzulUnderline\" href=\"".$aVerDcto."\" target=\"_blank\">".$nroFactura."</a>" : $nroFactura;
							$htmlTb .= "</td>";
							$htmlTb .= "<td nowrap=\"nowrap\">".$nroControl."</td>";
							$htmlTb .= "<td nowrap=\"nowrap\">".$rowDocumento['id_proveedor']."</td>";
							$htmlTb .= "<td nowrap=\"nowrap\">".$rowDocumento['rif_proveedor']."</td>";
							$htmlTb .= "<td align=\"left\" nowrap=\"nowrap\">".utf8_encode($rowDocumento['nombre_proveedor'])."</td>";
							$htmlTb .= "<td nowrap=\"nowrap\" title=\"".
								"Id Dcto.: ".$idNotaCredito.
								"\nNro. Nota de Crédito / Débito: ".$nroNotaCredito.
								"\nNro. de Control Nota de Crédito / Débito: ".$nroControlNotaCredito.
								"\nMódulo Nota de Crédito / Débito: ".$descripcionModuloNotaCredito."\">";
								$htmlTb .= (strlen($aVerDcto) > 0 && $idNotaCredito > 0) ? "<a class=\"linkAzulUnderline\" href=\"".$aVerDcto."\" target=\"_blank\">".$nroNotaCredito."</a>" : $nroNotaCredito;
							$htmlTb .= "</td>";
							$htmlTb .= "<td nowrap=\"nowrap\" title=\"".
								"Id Dcto.: ".$idDocumentoAfectado.
								"\nNro. de Documento Afectado: ".$nroDocumentoAfectado.
								"\nNro. de Control de Documento Afectado: ".$nroControlDocumentoAfectado.
								"\nMódulo Documento Afectado: ".$descripcionModuloDocumentoAfectado."\">";
								$htmlTb .= (strlen($aVerDcto) > 0 && $idDocumentoAfectado > 0) ? "<a class=\"linkAzulUnderline\" href=\"".$aVerDcto."\" target=\"_blank\">".$nroDocumentoAfectado."</a>" : $nroDocumentoAfectado;
							$htmlTb .= "</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$fechaComprobanteRetencion75."</td>";
							$htmlTb .= "<td nowrap=\"nowrap\">".$nroComprobanteRetencion75."</td>";
							$htmlTb .= "<td>".formatoNumero($signo * $totalCompraConIva, $lstFormatoNumero)."</td>";
							$htmlTb .= "<td>".formatoNumero($signo * $comprasExentas, $lstFormatoNumero)."</td>";
							$htmlTb .= "<td>".formatoNumero($signo * $comprasExonerado, $lstFormatoNumero)."</td>";
							
							if (isset($arrayIvaDocNac)) {
								foreach ($arrayIvaDocNac as $indiceIvaDocNac => $valorIvaDocNac) {
									$htmlTb .= "<td title=\"".$indiceIvaDocNac."\">".(($arrayIvaDocNac[$indiceIvaDocNac] != 0) ? formatoNumero(doubleval($signo * $arrayIvaDocNac[$indiceIvaDocNac]), $lstFormatoNumero) : "-")."</td>";
									
									$arrayTotalIvaDocNac[$indiceIvaDocNac] += $signo * $arrayIvaDocNac[$indiceIvaDocNac];
								}
							}
							
							$htmlTb .= "<td>".$numeroPlanillaImportacion."</td>";
							$htmlTb .= "<td>".$numeroExpediente."</td>";
							
							if (isset($arrayIvaDocImp)) {
								foreach ($arrayIvaDocImp as $indiceIvaDocImp => $valorIvaDocImp) {
									$htmlTb .= "<td title=\"".$indiceIvaDocImp."\">".(($arrayIvaDocImp[$indiceIvaDocImp] != 0) ? formatoNumero(doubleval($signo * $arrayIvaDocImp[$indiceIvaDocImp]), $lstFormatoNumero) : "-")."</td>";
									
									$arrayTotalIvaDocImp[$indiceIvaDocImp] += $signo * $arrayIvaDocImp[$indiceIvaDocImp];
								}
							}
							
							$htmlTb .= "<td>".formatoNumero($sumaRetenciones, $lstFormatoNumero)."</td>";
						$htmlTb .= "</tr>";
						
						$arrayTotalDia['total_incluyendo_impuesto'] += $signo * $totalCompraConIva;
						$arrayTotalDia['total_exento'] += $signo * $comprasExentas;
						$arrayTotalDia['total_exonerado'] += $signo * $comprasExonerado;
						$arrayTotalDia['total_impuesto_retenido'] += $sumaRetenciones;
					}
				}
			} else {
				for ($i = 0; $i < $contPosNac; $i++) {
					$arrayTotalIvaDocNac[$i] = "";
				}
				for ($i = 0; $i < $contPosImp; $i++) {
					$arrayTotalIvaDocImp[$i] = "";
				}
			}
			
			// TOTAL POR DIAS
			$htmlTb .= ($lstFormatoTotalDia == 1) ? "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">" : "";
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td align=\"center\" colspan=\"12\">"."Total Dia: ".date(spanDateFormat, strtotime($row['fecha_grupo']))."</td>" : "";
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".formatoNumero($arrayTotalDia['total_incluyendo_impuesto'], $lstFormatoNumero)."</td>" : "";
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".formatoNumero($arrayTotalDia['total_exento'], $lstFormatoNumero)."</td>" : "";
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".formatoNumero($arrayTotalDia['total_exonerado'], $lstFormatoNumero)."</td>" : "";
				
				if (isset($arrayTotalIvaDocNac)) {
					foreach ($arrayTotalIvaDocNac as $indice => $valor) {
						$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".(($arrayTotalIvaDocNac[$indice] != 0) ? formatoNumero($arrayTotalIvaDocNac[$indice], $lstFormatoNumero) : "-")."</td>" : "";
						
						$arrayTotalesIvaDocNac[$indice] += $arrayTotalIvaDocNac[$indice];
					}
				}
				
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>"."</td>" : "";
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>"."</td>" : "";
				
				if (isset($arrayTotalIvaDocImp)) {
					foreach ($arrayTotalIvaDocImp as $indice => $valor) {
						$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".(($arrayTotalIvaDocImp[$indice] != 0) ? formatoNumero($arrayTotalIvaDocImp[$indice], $lstFormatoNumero) : "-")."</td>" : "";
						
						$arrayTotalesIvaDocImp[$indice] += $arrayTotalIvaDocImp[$indice];
					}
				}
				
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".formatoNumero($arrayTotalDia['total_impuesto_retenido'], $lstFormatoNumero)."</td>" : "";
			$htmlTb .= ($lstFormatoTotalDia == 1) ? "</tr>" : "";
			
			$arrayTotalLibro['total_incluyendo_impuesto'] += $arrayTotalDia['total_incluyendo_impuesto'];
			$arrayTotalLibro['total_exento'] += $arrayTotalDia['total_exento'];
			$arrayTotalLibro['total_exonerado'] += $arrayTotalDia['total_exonerado'];
			$arrayTotalLibro['total_impuesto_retenido'] += $arrayTotalDia['total_impuesto_retenido'];
		}
	} else {
		for ($i = 0; $i < $contPosNac; $i++) {
			$arrayTotalesIvaDocNac[$i] = "";
		}
		for ($i = 0; $i < $contPosImp; $i++) {
			$arrayTotalesIvaDocImp[$i] = "";
		}
		
		$htmlTb .= "<td colspan=\"100\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$arrayTotalLibro['total_impuesto_retenido'] += $totalRetencionOtroPeriodo;
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"center\" colspan=\"12\">"."Total General del ".date(spanDateFormat, strtotime($valCadBusq[1]))." al ".date(spanDateFormat, strtotime($valCadBusq[2]))."</td>";
		$htmlTb .= "<td>".formatoNumero($arrayTotalLibro['total_incluyendo_impuesto'], $lstFormatoNumero)."</td>";
		$htmlTb .= "<td>".formatoNumero($arrayTotalLibro['total_exento'], $lstFormatoNumero)."</td>";
		$htmlTb .= "<td>".formatoNumero($arrayTotalLibro['total_exonerado'], $lstFormatoNumero)."</td>";
		
		if (isset($arrayTotalesIvaDocNac)) {
			foreach ($arrayTotalesIvaDocNac as $indice => $valor) {
				$htmlTb .= "<td title=\"arrayTotalesIvaDocNac[".$indice."]\">".(($arrayTotalesIvaDocNac[$indice] != 0) ? formatoNumero($arrayTotalesIvaDocNac[$indice], $lstFormatoNumero) : "-")."</td>";
			}
		}
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td></td>";
		if (isset($arrayTotalesIvaDocImp)) {
			foreach ($arrayTotalesIvaDocImp as $indice => $valor) {
				$htmlTb .= "<td title=\"arrayTotalesIvaDocImp[".$indice."]\">".(($arrayTotalesIvaDocImp[$indice] != 0) ? formatoNumero($arrayTotalesIvaDocImp[$indice], $lstFormatoNumero) : "-")."</td>";
			}
		}
		
		$htmlTb .= "<td>".formatoNumero($arrayTotalLibro['total_impuesto_retenido'], $lstFormatoNumero)."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"100\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibro(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibro(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaLibro(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibro(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibro(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	mysql_query("COMMIT;");
	
	
	// ALICUOTAS REPUESTOS
	$porcIvaRepuestosGralPredet = NULL;
	$totalBaseIvaRepuestosGralPredet = 0;
	$totalSubtotalIvaRepuestosGralPredet = 0;
	$porcIvaRepuestosGral = NULL;
	$totalBaseIvaRepuestosGral = 0;
	$totalSubtotalIvaRepuestosGral = 0;
	$porcIvaRepuestosRed = NULL;
	$totalBaseIvaRepuestosRed = 0;
	$totalSubtotalIvaRepuestosRed = 0;
	$porcIvaRepuestosAdic = NULL;
	$totalBaseIvaRepuestosAdic = 0;
	$totalSubtotalIvaRepuestosAdic = 0;
	foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
		if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(1,6))) {
			// 1 = Alicuota General Compra, 6 = Alicuota General Venta
			if ($arrayIvaNac[$indiceIvaNac]['predeterminado_iva'] == 1) {
				$porcIvaRepuestosGralPredet[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
				$totalBaseIvaRepuestosGralPredet += $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaRepuestosGralPredet += $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
			} else {
				$porcIvaRepuestosGral[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
				$totalBaseIvaRepuestosGral += $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaRepuestosGral += $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
				
				// Separacion ivas multiples
				$totalBaseIvaSimpleRepuestos = $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'] + $arrayIvaGeneralRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaSimpleRepuestos = $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'] + $arrayIvaGeneralRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
				$totalTotalIvaSimpleRepuestos = $totalBaseIvaSimpleRepuestos + $totalSubtotalIvaSimpleRepuestos;
				
				$arrayIvaGeneralRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']] = array(
					'id_iva' => $arrayIvaNac[$indiceIvaNac]['id_iva'],
					'porc_iva' => $arrayIvaNac[$indiceIvaNac]['porc_iva'],
					'base_imponible' => $totalBaseIvaSimpleRepuestos,
					'subtotal_iva' => $totalSubtotalIvaSimpleRepuestos,
					'total' => $totalTotalIvaSimpleRepuestos
				);
			}
		} else if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(8,9))) {
			// 8 = Alicuota Reducida Compra, 9 = Alicuota Reducida Venta
			$porcIvaRepuestosRed[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
			$totalBaseIvaRepuestosRed += $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
			$totalSubtotalIvaRepuestosRed += $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
		} else if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(3,2))) {
			// 3 = Alicuota Adicional Compra, 2 = Adicional Adicional Venta
			$porcIvaRepuestosAdic[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
			$totalBaseIvaRepuestosAdic += $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
			$totalSubtotalIvaRepuestosAdic += $arrayTotalIvaRepuestos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
		}
	}
	$totalFacturaRepuestosGralPredet = $totalBaseIvaRepuestosGralPredet + $totalSubtotalIvaRepuestosGralPredet + $totalExentoRepuestos + $totalExoneradoRepuestos;
	$totalFacturaRepuestosGral = $totalBaseIvaRepuestosGral + $totalSubtotalIvaRepuestosGral;
	$totalFacturaRepuestosRed = $totalBaseIvaRepuestosRed + $totalSubtotalIvaRepuestosRed;
	$totalFacturaRepuestosAdic = $totalBaseIvaRepuestosAdic + $totalSubtotalIvaRepuestosAdic;
	
	// ALICUOTAS SERVICIOS
	$porcIvaServiciosGralPredet = NULL;
	$totalBaseIvaServiciosGralPredet = 0;
	$totalSubtotalIvaServiciosGralPredet = 0;
	$porcIvaServiciosGral = NULL;
	$totalBaseIvaServiciosGral = 0;
	$totalSubtotalIvaServiciosGral = 0;
	$porcIvaServiciosRed = NULL;
	$totalBaseIvaServiciosRed = 0;
	$totalSubtotalIvaServiciosRed = 0;
	$porcIvaServiciosAdic = NULL;
	$totalBaseIvaServiciosAdic = 0;
	$totalSubtotalIvaServiciosAdic = 0;
	foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
		if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(1,6))) {
			// 1 = Alicuota General Compra, 6 = Alicuota General Venta
			if ($arrayIvaNac[$indiceIvaNac]['predeterminado_iva'] == 1) {
				$porcIvaServiciosGralPredet[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
				$totalBaseIvaServiciosGralPredet += $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaServiciosGralPredet += $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
			} else {
				$porcIvaServiciosGral[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
				$totalBaseIvaServiciosGral += $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaServiciosGral += $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
				
				// Separacion ivas multiples
				$totalBaseIvaSimpleServicios = $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'] + $arrayIvaGeneralServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaSimpleServicios = $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'] + $arrayIvaGeneralServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
				$totalTotalIvaSimpleServicios = $totalBaseIvaSimpleServicios + $totalSubtotalIvaSimpleServicios;
				
				$arrayIvaGeneralServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']] = array(
					'id_iva' => $arrayIvaNac[$indiceIvaNac]['id_iva'],
					'porc_iva' => $arrayIvaNac[$indiceIvaNac]['porc_iva'],
					'base_imponible' => $totalBaseIvaSimpleServicios,
					'subtotal_iva' => $totalSubtotalIvaSimpleServicios,
					'total' => $totalTotalIvaSimpleServicios
				);
			}
		} else if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(8,9))) {
			// 8 = Alicuota Reducida Compra, 9 = Alicuota Reducida Venta
			$porcIvaServiciosRed[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
			$totalBaseIvaServiciosRed += $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
			$totalSubtotalIvaServiciosRed += $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
		} else if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(3,2))) {
			// 3 = Alicuota Adicional Compra, 2 = Adicional Adicional Venta
			$porcIvaServiciosAdic[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
			$totalBaseIvaServiciosAdic += $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
			$totalSubtotalIvaServiciosAdic += $arrayTotalIvaServicios[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
		}
	}
	$totalFacturaServiciosGralPredet = $totalBaseIvaServiciosGralPredet + $totalSubtotalIvaServiciosGralPredet + $totalExentoServicios + $totalExoneradoServicios;
	$totalFacturaServiciosGral = $totalBaseIvaServiciosGral + $totalSubtotalIvaServiciosGral;
	$totalFacturaServiciosRed = $totalBaseIvaServiciosRed + $totalSubtotalIvaServiciosRed;
	$totalFacturaServiciosAdic = $totalBaseIvaServiciosAdic + $totalSubtotalIvaServiciosAdic;
	
	// ALICUOTAS VEHICULOS
	$porcIvaVehiculosGralPredet = NULL;
	$totalBaseIvaVehiculosGralPredet = 0;
	$totalSubtotalIvaVehiculosGralPredet = 0;
	$porcIvaVehiculosGral = NULL;
	$totalBaseIvaVehiculosGral = 0;
	$totalSubtotalIvaVehiculosGral = 0;
	$porcIvaVehiculosRed = NULL;
	$totalBaseIvaVehiculosRed = 0;
	$totalSubtotalIvaVehiculosRed = 0;
	$porcIvaVehiculosAdic = NULL;
	$totalBaseIvaVehiculosAdic = 0;
	$totalSubtotalIvaVehiculosAdic = 0;
	foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
		if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(1,6))) {
			// 1 = Alicuota General Compra, 6 = Alicuota General Venta
			if ($arrayIvaNac[$indiceIvaNac]['predeterminado_iva'] == 1) {
				$porcIvaVehiculosGralPredet[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
				$totalBaseIvaVehiculosGralPredet += $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaVehiculosGralPredet += $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
			} else {
				$porcIvaVehiculosGral[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
				$totalBaseIvaVehiculosGral += $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaVehiculosGral += $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
				
				// Separacion ivas multiples
				$totalBaseIvaSimpleVehiculos = $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'] + $arrayIvaGeneralVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaSimpleVehiculos = $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'] + $arrayIvaGeneralVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
				$totalTotalIvaSimpleVehiculos = $totalBaseIvaSimpleVehiculos + $totalSubtotalIvaSimpleVehiculos;
				
				$arrayIvaGeneralVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']] = array(
					'id_iva' => $arrayIvaNac[$indiceIvaNac]['id_iva'],
					'porc_iva' => $arrayIvaNac[$indiceIvaNac]['porc_iva'],
					'base_imponible' => $totalBaseIvaSimpleVehiculos,
					'subtotal_iva' => $totalSubtotalIvaSimpleVehiculos,
					'total' => $totalTotalIvaSimpleVehiculos
				);
			}
		} else if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(8,9))) {
			// 8 = Alicuota Reducida Compra, 9 = Alicuota Reducida Venta
			$porcIvaVehiculosRed[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
			$totalBaseIvaVehiculosRed += $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
			$totalSubtotalIvaVehiculosRed += $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
		} else if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(3,2))) {
			// 3 = Alicuota Adicional Compra, 2 = Adicional Adicional Venta
			$porcIvaVehiculosAdic[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
			$totalBaseIvaVehiculosAdic += $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
			$totalSubtotalIvaVehiculosAdic += $arrayTotalIvaVehiculos[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
		}
	}
	$totalFacturaVehiculosGralPredet = $totalBaseIvaVehiculosGralPredet + $totalSubtotalIvaVehiculosGralPredet + $totalExentoVehiculos + $totalExoneradoVehiculos;
	$totalFacturaVehiculosGral = $totalBaseIvaVehiculosGral + $totalSubtotalIvaVehiculosGral;
	$totalFacturaVehiculosRed = $totalBaseIvaVehiculosRed + $totalSubtotalIvaVehiculosRed;
	$totalFacturaVehiculosAdic = $totalBaseIvaVehiculosAdic + $totalSubtotalIvaVehiculosAdic;
	
	// ALICUOTAS ADMON
	$porcIvaAdmonGralPredet = NULL;
	$totalBaseIvaAdmonGralPredet = 0;
	$totalSubtotalIvaAdmonGralPredet = 0;
	$porcIvaAdmonGral = NULL;
	$totalBaseIvaAdmonGral = 0;
	$totalSubtotalIvaAdmonGral = 0;
	$porcIvaAdmonRed = NULL;
	$totalBaseIvaAdmonRed = 0;
	$totalSubtotalIvaAdmonRed = 0;
	$porcIvaAdmonAdic = NULL;
	$totalBaseIvaAdmonAdic = 0;
	$totalSubtotalIvaAdmonAdic = 0;
	foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
		if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(1,6))) {
			// 1 = Alicuota General Compra, 6 = Alicuota General Venta
			if ($arrayIvaNac[$indiceIvaNac]['predeterminado_iva'] == 1) {
				$porcIvaAdmonGralPredet[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
				$totalBaseIvaAdmonGralPredet += $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaAdmonGralPredet += $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
			} else {
				$porcIvaAdmonGral[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
				$totalBaseIvaAdmonGral += $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaAdmonGral += $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
				
				// Separacion ivas multiples
				$totalBaseIvaSimpleAdmon = $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'] + $arrayIvaGeneralAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
				$totalSubtotalIvaSimpleAdmon = $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'] + $arrayIvaGeneralAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
				$totalTotalIvaSimpleAdmon = $totalBaseIvaSimpleAdmon + $totalSubtotalIvaSimpleAdmon;
				
				$arrayIvaGeneralAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']] = array(
					'id_iva' => $arrayIvaNac[$indiceIvaNac]['id_iva'],
					'porc_iva' => $arrayIvaNac[$indiceIvaNac]['porc_iva'],
					'base_imponible' => $totalBaseIvaSimpleAdmon,
					'subtotal_iva' => $totalSubtotalIvaSimpleAdmon,
					'total' => $totalTotalIvaSimpleAdmon
				);				
			}
		} else if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(8,9))) {
			// 8 = Alicuota Reducida Compra, 9 = Alicuota Reducida Venta
			$porcIvaAdmonRed[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
			$totalBaseIvaAdmonRed += $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
			$totalSubtotalIvaAdmonRed += $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
		} else if (in_array($arrayIvaNac[$indiceIvaNac]['tipo_iva'],array(3,2))) {
			// 3 = Alicuota Adicional Compra, 2 = Adicional Adicional Venta
			$porcIvaAdmonAdic[] = $arrayIvaNac[$indiceIvaNac]['porc_iva']."%";
			$totalBaseIvaAdmonAdic += $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['base_imponible'];
			$totalSubtotalIvaAdmonAdic += $arrayTotalIvaAdmon[$arrayIvaNac[$indiceIvaNac]['id_iva']]['subtotal_iva'];
		}
	}
	$totalFacturaAdmonGralPredet = $totalBaseIvaAdmonGralPredet + $totalSubtotalIvaAdmonGralPredet + $totalExentoAdmon + $totalExoneradoAdmon;
	$totalFacturaAdmonGral = $totalBaseIvaAdmonGral + $totalSubtotalIvaAdmonGral;
	$totalFacturaAdmonRed = $totalBaseIvaAdmonRed + $totalSubtotalIvaAdmonRed;
	$totalFacturaAdmonAdic = $totalBaseIvaAdmonAdic + $totalSubtotalIvaAdmonAdic;
	
$htmlCuadro .= "<table width=\"70%\" align=\"center\" border=\"1\" class=\"tabla\" cellpadding=\"2\" style=\"font-size:9px\">";
	$htmlCuadro .= "<tr align=\"center\" class=\"tituloColumna\" height=\"24\">";
		$htmlCuadro .= "<td width=\"30%\"></td>";
		$htmlCuadro .= "<td width=\"5%\"></td>";
		$htmlCuadro .= "<td width=\"5%\"></td>";
		$htmlCuadro .= "<td width=\"10%\">Base Imponible</td>";
		$htmlCuadro .= "<td width=\"10%\">I.V.A.</td>";
		$htmlCuadro .= "<td width=\"10%\">Exentas</td>";
		$htmlCuadro .= "<td width=\"10%\">Exoneradas</td>";
		$htmlCuadro .= "<td width=\"10%\">Total</td>";
		$htmlCuadro .= "<td width=\"10%\">I.V.A. Retenido</td>";
	$htmlCuadro .= "</tr>";
	
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo2SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Repuestos Alicuota General (".implode(", ",$porcIvaRepuestosGralPredet).")</td>";
if ($totalFacturaRepuestosGralPredet >  0) {

					$totalprimero= formatoNumero($totalRetenidoRepuestos, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoRepuestos, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoRepuestos, $lstFormatoNumero);
					$totalRetenidoRepuestos = '';
					$totalExentoRepuestos = '';
					$totalExoneradoRepuestos = '';
				# code...
			}else{
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}



		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaRepuestosGralPredet\">".formatoNumero($totalBaseIvaRepuestosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaRepuestosGralPredet\">".formatoNumero($totalSubtotalIvaRepuestosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoRepuestos\">".$totalsegundo."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoRepuestos\">".$totaltercero."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaRepuestosGralPredet\">".formatoNumero($totalFacturaRepuestosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoRepuestos\">".$totalprimero."</td>";
	$htmlCuadro .= "</tr>";
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo2SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Repuestos Alicuota General (".implode(", ",$porcIvaRepuestosGral).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaRepuestosGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaRepuestosGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaRepuestosGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";*/
	
	foreach($arrayIvaGeneralRepuestos as $arrayIva){ // Separacion ivas multiples

		if ($arrayIva['total'] >  0) {

					$totalprimero= formatoNumero($totalRetenidoRepuestos, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoRepuestos, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoRepuestos, $lstFormatoNumero);
				# code...
			}else{
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}

		$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo2SinBorde\" height=\"24\">";
			$htmlCuadro .= "<td>Libro Compra de Repuestos Alicuota General (".$arrayIva['porc_iva']."%)</td>";
			$htmlCuadro .= "<td></td>";
			$htmlCuadro .= "<td>-</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['base_imponible'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['subtotal_iva'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalsegundo."</td>";
			$htmlCuadro .= "<td>".$totaltercero."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['total'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalprimero."</td>";
		$htmlCuadro .= "</tr>";
	}
	
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo2SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Repuestos Alicuota Reducida (".implode(", ",$porcIvaRepuestosRed).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaRepuestosRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaRepuestosRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaRepuestosRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo2SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Repuestos Alicuota Adicional (".implode(", ",$porcIvaRepuestosAdic).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaRepuestosAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaRepuestosAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaRepuestosAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";*/
	
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfoSinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Servicios Alicuota General (".implode(", ",$porcIvaServiciosGralPredet).")</td>";
		if ($totalFacturaServiciosGralPredet >  0) {

					$totalprimero= formatoNumero($totalRetenidoServicios, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoServicios, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoServicios, $lstFormatoNumero);
					$totalRetenidoServicios = '';
					$totalExentoServicios = '';
					$totalExoneradoServicios = '';
				# code...
			}else{
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}


		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaServiciosGralPredet\">".formatoNumero($totalBaseIvaServiciosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaServiciosGralPredet\">".formatoNumero($totalSubtotalIvaServiciosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoServicios\">".$totalsegundo."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoServicios\">".$totaltercero."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaServiciosGralPredet\">".formatoNumero($totalFacturaServiciosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoServicios\">".$totalprimero."</td>";
	$htmlCuadro .= "</tr>";
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfoSinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Servicios Alicuota General (".implode(", ",$porcIvaServiciosGral).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaServiciosGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaServiciosGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaServiciosGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";*/
	
	foreach($arrayIvaGeneralServicios as $arrayIva){ // Separacion ivas multiples
			if ($arrayIva['total'] >  0) {

					$totalprimero= formatoNumero($totalRetenidoServicios, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoServicios, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoServicios, $lstFormatoNumero);
				# code...
			}else{
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}
		$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfoSinBorde\" height=\"24\">";
			$htmlCuadro .= "<td>Libro Compra de Servicios Alicuota General (".$arrayIva['porc_iva']."%)</td>";
			$htmlCuadro .= "<td>-</td>";
			$htmlCuadro .= "<td>-</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['base_imponible'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['subtotal_iva'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalsegundo."</td>";
			$htmlCuadro .= "<td>".$totaltercero."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['total'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalprimero."</td>";
		$htmlCuadro .= "</tr>";
	}
	
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfoSinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Servicios Alicuota Reducida (".implode(", ",$porcIvaServiciosRed).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaServiciosRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaServiciosRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaServiciosRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfoSinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Servicios Alicuota Adicional (".implode(", ",$porcIvaServiciosAdic).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaServiciosAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaServiciosAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaServiciosAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";*/
	
	$totalBaseIvaReducida = $totalBaseIvaRepuestosRed + $totalBaseIvaServiciosRed + $totalBaseIvaVehiculosRed + $totalBaseIvaAdmonRed;
	$totalSubtotalIvaReducida = $totalSubtotalIvaRepuestosRed + $totalSubtotalIvaServiciosRed + $totalSubtotalIvaVehiculosRed + $totalSubtotalIvaAdmonRed;
	$totalFacturaIvaReducida = $totalBaseIvaReducida + $totalSubtotalIvaReducida;
	$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
		$htmlCuadro .= "<td>Compras Internas Gravadas Alicuotas Reducidas</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaReducida\">".formatoNumero($totalBaseIvaReducida, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaReducida\">".formatoNumero($totalSubtotalIvaReducida, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalFacturaIvaReducida\">".formatoNumero($totalFacturaIvaReducida, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";
	
	$totalFacturaVehiculosGralSolo = $totalBaseIvaVehiculosGralSolo + $totalSubtotalIvaVehiculosGralSolo + $totalExentoIvaVehiculosGralSolo + $totalExoneradoIvaVehiculosGralSolo;
	$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
		$htmlCuadro .= "<td>Libro de Compras de Vehiculos Alicuota General</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaVehiculosGralSolo\">".formatoNumero($totalBaseIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaVehiculosGralSolo\">".formatoNumero($totalSubtotalIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoIvaVehiculosGralSolo\">".formatoNumero($totalExentoIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoIvaVehiculosGralSolo\">".formatoNumero($totalExoneradoIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaVehiculosGralSolo\">".formatoNumero($totalFacturaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoIvaVehiculosGralSolo\">".formatoNumero($totalRetenidoIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	
	//GENERAL predeterminado 12% gregor	
			if ($totalFacturaVehiculosGralPredet >  0) {

					$totalprimero= formatoNumero($totalRetenidoVehiculos, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoVehiculos, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoVehiculos, $lstFormatoNumero);
					$totalRetenidoVehiculos = '';
					$totalExentoVehiculos = '';
					$totalExoneradoVehiculos = '';
				# code...
			}else{
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}
	$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Vehiculos Alicuota General (".implode(", ",$porcIvaVehiculosGralPredet).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaVehiculosGralPredet\">".formatoNumero($totalBaseIvaVehiculosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaVehiculosGralPredet\">".formatoNumero($totalSubtotalIvaVehiculosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoVehiculos\">".$totalsegundo."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoVehiculos\">".$totaltercero."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaVehiculosGralPredet\">".formatoNumero($totalFacturaVehiculosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoServicios\">".$totalprimero."</td>";
	$htmlCuadro .= "</tr>";
	
	foreach($arrayIvaGeneralVehiculos as $arrayIva){ // Separacion ivas multiples
		if ($arrayIva['total'] >  0) {

					$totalprimero= formatoNumero($totalRetenidoServicios, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoServicios, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoServicios, $lstFormatoNumero);
				# code...
			}else{
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}
		$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
			$htmlCuadro .= "<td>Libro Compra de Vehiculos Alicuota General (".$arrayIva['porc_iva']."%)</td>";
			$htmlCuadro .= "<td>".$totalprimero."</td>";
			$htmlCuadro .= "<td>-</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['base_imponible'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['subtotal_iva'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalsegundo."</td>";
			$htmlCuadro .= "<td>".$totaltercero."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['total'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalprimero."</td>";
		$htmlCuadro .= "</tr>";
	}
	
	$totalFacturaVehiculosGralMasAdic = $totalBaseIvaVehiculosGralMasAdic + $totalSubtotalIvaVehiculosGralMasAdic + $totalExentoIvaVehiculosGralMasAdic + $totalExoneradoIvaVehiculosGralMasAdic;
	$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
		$htmlCuadro .= "<td>Compras Internas Gravadas por Alicuota General más Alicuota Adicional</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaVehiculosGralMasAdic\">".formatoNumero($totalBaseIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaVehiculosGralMasAdic\">".formatoNumero($totalSubtotalIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoIvaVehiculosGralMasAdic\">".formatoNumero($totalExentoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoIvaVehiculosGralMasAdic\">".formatoNumero($totalExoneradoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaVehiculosGralMasAdic\">".formatoNumero($totalFacturaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoVehiculos\">".formatoNumero($totalRetenidoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo4SinBorde\" height=\"24\">";
	//RAL predeterminado 12% gregor	
			if ($totalFacturaAdmonGralPredet >  0) {

					$totalprimero= formatoNumero($totalRetenidoAdmon, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoAdmon, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoAdmon, $lstFormatoNumero);
					$totalRetenidoAdmon = '';
					$totalExentoAdmon = '';
					$totalExoneradoAdmon = '';
				# code...
			}else{
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}

		$htmlCuadro .= "<td>Libro Compra de Administración Alicuota General (".implode(", ",$porcIvaAdmonGralPredet).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaAdmonGralPredet\">".formatoNumero($totalBaseIvaAdmonGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaAdmonGralPredet\">".formatoNumero($totalSubtotalIvaAdmonGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoAdmon\">".$totalsegundo."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoAdmon\">".$totaltercero."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaAdmonGralPredet\">".formatoNumero($totalFacturaAdmonGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoAdmon\">".$totalprimero."</td>";
	$htmlCuadro .= "</tr>";
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo4SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Administración Alicuota General (".implode(", ",$porcIvaAdmonGral).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaAdmonGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaAdmonGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaAdmonGral, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";*/
	
	foreach($arrayIvaGeneralAdmon as $arrayIva){ // Separacion ivas multiples
		if ($arrayIva['total'] >  0) {

					$totalprimero= formatoNumero($totalRetenidoAdmon, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoAdmon, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoAdmon, $lstFormatoNumero);
				# code...
			}else{
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}
		$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo4SinBorde\" height=\"24\">";
			$htmlCuadro .= "<td>Libro Compra de Administración Alicuota General (".$arrayIva['porc_iva']."%)</td>";
			$htmlCuadro .= "<td>-</td>";
			$htmlCuadro .= "<td>-</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['base_imponible'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['subtotal_iva'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalsegundo."</td>";
			$htmlCuadro .= "<td>".$totaltercero."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['total'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalprimero."</td>";
		$htmlCuadro .= "</tr>";
	}
	
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo4SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Admon Alicuota Reducida (".implode(", ",$porcIvaAdmonRed).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaAdmonRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaAdmonRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaAdmonRed, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo4SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Compra de Admon Alicuota Adicional (".implode(", ",$porcIvaAdmonAdic).")</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalBaseIvaAdmonAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalSubtotalIvaAdmonAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalFacturaAdmonAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";*/
	
	$totalGlobalExento = $arrayTotalLibro['total_exento'];
	$totalGlobalExonerado = $arrayTotalLibro['total_exonerado'];
	$totalGlobalExentoExonerado = $totalGlobalExento + $totalGlobalExonerado;
	$htmlCuadro .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadro .= "<td>Compra No Grabadas y/o Sin Derecho a Crédito Fiscal</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExentoExonerado\">".formatoNumero($totalGlobalExentoExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";
	
	
	$totalGlobalBaseImp = ($totalBaseIvaRepuestosGralPredet + $totalBaseIvaRepuestosGral + $totalBaseIvaRepuestosRed)
		+ ($totalBaseIvaServiciosGralPredet + $totalBaseIvaServiciosGral + $totalBaseIvaServiciosRed)
		+ ($totalBaseIvaVehiculosGralSolo + $totalBaseIvaVehiculosGralMasAdic)
		+ ($totalBaseIvaAdmonGralPredet + $totalBaseIvaAdmonGral + $totalBaseIvaAdmonRed);
	$totalGlobalIva = ($totalSubtotalIvaRepuestosGralPredet + $totalSubtotalIvaRepuestosGral + $totalSubtotalIvaRepuestosRed)
		+ ($totalSubtotalIvaServiciosGralPredet + $totalSubtotalIvaServiciosGral + $totalSubtotalIvaServiciosRed)
		+ ($totalSubtotalIvaVehiculosGralSolo + $totalSubtotalIvaVehiculosGralMasAdic)
		+ ($totalSubtotalIvaAdmonGralPredet + $totalSubtotalIvaAdmonGral + $totalSubtotalIvaAdmonRed);
	$totalGlobalCompraConIva = $totalGlobalBaseImp + $totalGlobalIva + $totalGlobalExento + $totalGlobalExonerado;
	$totalGlobalRetenido = $arrayTotalLibro['total_impuesto_retenido'];
	$htmlCuadro .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadro .= "<td>Total de Compras y Créditos Fiscales</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalGlobalBaseImp\">".formatoNumero($totalGlobalBaseImp, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalIva\">".formatoNumero($totalGlobalIva, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalCompraConIva\">".formatoNumero($totalGlobalCompraConIva, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalRetenido\">".formatoNumero($totalGlobalRetenido, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	$htmlCuadro .= "</table>";
	
	
	// CUADRO RESUMEN
	$htmlCuadroResumen .= "<table width=\"70%\" align=\"center\" border=\"1\" class=\"tabla\" cellpadding=\"2\" style=\"font-size:9px\">";
	$htmlCuadroResumen .= "<tr align=\"center\" class=\"tituloColumna\" height=\"24\">";
		$htmlCuadroResumen .= "<td width=\"30%\"></td>";
		$htmlCuadroResumen .= "<td width=\"10%\">Base Imponible</td>";
		$htmlCuadroResumen .= "<td width=\"10%\">I.V.A.</td>";
		$htmlCuadroResumen .= "<td width=\"10%\">Exentas</td>";
		$htmlCuadroResumen .= "<td width=\"10%\">Exoneradas</td>";
		$htmlCuadroResumen .= "<td width=\"10%\">Total</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Compra No Grabadas y/o Sin Derecho a Crédito Fiscal</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExentoExonerado\">".formatoNumero($totalGlobalExentoExonerado, $lstFormatoNumero)."</td>";
	$htmlCuadroResumen .= "</tr>";
	
	//REVISAR
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Compras Internas Gravadas por Alicuota General</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalBaseImp\">".formatoNumero($totalGlobalBaseImp, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalIva\">".formatoNumero($totalGlobalIva, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalVentaConIva\">".formatoNumero($totalGlobalCompraConIva, $lstFormatoNumero)."</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Compras Internas Gravadas por Alicuota General más Alicuota Adicional</td>";
		$htmlCuadroResumen .= "<td title=\"totalBaseIvaVehiculosGralMasAdic\">".formatoNumero($totalBaseIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalSubtotalIvaVehiculosGralMasAdic\">".formatoNumero($totalSubtotalIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalExentoIvaVehiculosGralMasAdic\">".formatoNumero($totalExentoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalExoneradoIvaVehiculosGralMasAdic\">".formatoNumero($totalExoneradoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalFacturaVehiculosGralMasAdic\">".formatoNumero($totalFacturaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Compras Internas Gravadas por Alícuota Reducida</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
	$htmlCuadroResumen .= "</tr>";
	//REVISAR
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Ajustes a Debitos Fiscales</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Total de Compras y Créditos Fiscales</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalBaseImp\">".formatoNumero($totalGlobalBaseImp, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalIva\">".formatoNumero($totalGlobalIva, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalCompraConIva\">".formatoNumero($totalGlobalCompraConIva, $lstFormatoNumero)."</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "</table>";
	
	return $htmlTableIni.$htmlTh.$htmlTb.$htmlTableFin."<br>".$htmlCuadro."<br>".$htmlCuadroResumen;
}

function formatoNumero($monto, $idFormatoNumero = 1){
	switch($idFormatoNumero) {
		case 1 : return number_format($monto, 2, ".", ","); break;
		case 2 : return number_format($monto, 2, ",", "."); break;
		case 3 : return number_format($monto, 2, ".", ""); break;
		case 4 : return number_format($monto, 2, ",", ""); break;
		default : return number_format($monto, 2, ".", ",");
	}
}

echo listaLibro(0,'','', $_GET['idEmpresa']."|".$_GET['f1']."|".$_GET['f2']."|".$_GET['idModulo']."|".$_GET['lstFormatoNumero']."|".$_GET['lstFormatoTotalDia']);
?>