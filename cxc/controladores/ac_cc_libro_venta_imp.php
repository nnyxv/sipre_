<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function listaLibro($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $raiz;
	global $spanClienteCxC;
	
	mysql_query("START TRANSACTION;");
	
	$lstFormatoNumero = $valCadBusq[4];
	$lstFormatoTotalDia = $valCadBusq[5];
	
	$sqlBusq = "";
	$sqlBusq2 = "";
	$sqlBusq3 = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
		$sqlBusq3 .= $cond.sprintf("(cxc_nd.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nd.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[3] != -1 && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
		$sqlBusq2 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
		$sqlBusq3 .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	mysql_query("START TRANSACTION;");
	
	$query = sprintf("SELECT
		cxc_fact.fechaRegistroFactura AS fecha_origen,
		cxc_fact.aplicaLibros AS aplica_libros
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE DATE(cxc_fact.fechaRegistroFactura) BETWEEN %s AND %s
		AND cxc_fact.aplicaLibros = 1 %s
	GROUP BY cxc_fact.fechaRegistroFactura
		
	UNION
	
	SELECT
		cxc_nc.fechaNotaCredito AS fecha_origen,
		cxc_nc.aplicaLibros AS aplica_libros
	FROM cj_cc_notacredito cxc_nc
	WHERE DATE(cxc_nc.fechaNotaCredito) BETWEEN %s AND %s
		AND cxc_nc.aplicaLibros = 1 %s
	GROUP BY cxc_nc.fechaNotaCredito
		
	UNION
	
	SELECT
		cxc_nd.fechaRegistroNotaCargo AS fecha_origen,
		cxc_nd.aplicaLibros AS aplica_libros
	FROM cj_cc_notadecargo cxc_nd
	WHERE DATE(cxc_nd.fechaRegistroNotaCargo) BETWEEN %s AND %s
		AND cxc_nd.aplicaLibros = 1 %s
	GROUP BY cxc_nd.fechaRegistroNotaCargo
	ORDER BY 1 ASC",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq,
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq2,
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq3);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$queryIva = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.id_tipo_impuesto,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto)
	WHERE (iva.tipo IN (6,9,2) AND iva.estado = 1)
		OR iva.idIva IN (SELECT cxc_fact_iva.id_iva
						FROM cj_cc_encabezadofactura cxc_fact
							INNER JOIN cj_cc_factura_iva cxc_fact_iva ON (cxc_fact.idFactura = cxc_fact_iva.id_factura)
						WHERE DATE(cxc_fact.fechaRegistroFactura) BETWEEN %s AND %s
						
						UNION
						
						SELECT cxc_nc_iva.id_iva
						FROM cj_cc_notacredito cxc_nc
							INNER JOIN cj_cc_nota_credito_iva cxc_nc_iva ON (cxc_nc.idNotaCredito = cxc_nc_iva.id_nota_credito)
						WHERE DATE(cxc_nc.fechaNotaCredito) BETWEEN %s AND %s
						
						UNION
						
						SELECT cxc_nd_iva.id_iva
						FROM cj_cc_notadecargo cxc_nd
							INNER JOIN cj_cc_nota_cargo_iva cxc_nd_iva ON (cxc_nd.idNotaCargo = cxc_nd_iva.id_nota_cargo)
						WHERE DATE(cxc_nd.fechaRegistroNotaCargo) BETWEEN %s AND %s)
	ORDER BY tipo_imp.tipo_impuesto DESC, iva.iva DESC, iva.activo DESC",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	$rsIvaNac = mysql_query($queryIva);
	if (!$rsIvaNac) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsIvaNac = mysql_num_rows($rsIvaNac);
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" style=\"font-size:9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td rowspan=\"3\">"."Fecha del Documento"."</td>
				<td rowspan=\"3\">"."Tipo de Documento"."</td>
				<td rowspan=\"3\">"."Nro. de Documento"."</td>
				<td rowspan=\"3\">"."Nro. de Control"."</td>";
	
	$colspan = 11;
	if (in_array(idArrayPais,array(2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$colspan++;
		
		$htmlTh .= "<td rowspan=\"3\">"."Nro. Fiscal"."</td>";
	}
				
	$htmlTh .= "<td rowspan=\"3\">"."Codigo Cliente"."</td>
				<td rowspan=\"3\">".$spanClienteCxC."</td>
				<td rowspan=\"3\">"."Cliente"."</td>
				<td rowspan=\"3\">"."Nro. Nota de Crédito / Débito"."</td>
				<td rowspan=\"3\">"."Numero de Factura Afectada"."</td>
				<td rowspan=\"3\">"."Fecha de Comprobante de Retencion"."</td>
				<td rowspan=\"3\">"."Numero de Comprobante de Retencion"."</td>
				<td rowspan=\"3\">"."Total de Ventas Incluyendo el IVA"."</td>
				<td rowspan=\"3\">"."Ventas Exentas"."</td>
				<td rowspan=\"3\">"."Ventas Exoneradas"."</td>
				<td colspan=\"".($totalRowsIvaNac * 2)."\">"."No Contribuyente"."</td>
				<td colspan=\"".($totalRowsIvaNac * 2)."\">"."Contribuyente"."</td>
				<td rowspan=\"3\">"."Impuesto Retenido"."</td>
			</tr>";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$rsIvaNac = mysql_query($queryIva);
	if (!$rsIvaNac) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$contPosNac = 0;
	while ($rowIvaNac = mysql_fetch_array($rsIvaNac)) {
		$contPosNac += 2;
		
		$htmlTh .= "<td rowspan=\"2\" title=\"".($contPosNac-2)."\">"."Base Imponible"."</td>
					<td>".utf8_encode($rowIvaNac['tipo_impuesto'])."</td>";
	}
	
	$rsIvaImp = mysql_query($queryIva);
	if (!$rsIvaImp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$contPosImp = 0;
	while ($rowIvaImp = mysql_fetch_array($rsIvaImp)) {
		$contPosImp += 2;
		
		$htmlTh .= "<td rowspan=\"2\" title=\"".($contPosImp-2)."\">"."Base Imponible"."</td>
					<td>".utf8_encode($rowIvaImp['tipo_impuesto'])."</td>";
	}
	$htmlTh .= "</tr>";
	
	// COLUMNAS DE IVA DE COMPRAS INTERNAS NACIONALES
	$arrayIvaNac = NULL;
	$htmlTh .= "<tr class=\"tituloColumna\">";
	$rsIvaNac = mysql_query($queryIva);
	if (!$rsIvaNac) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
	$rsIvaImp = mysql_query($queryIva);
	if (!$rsIvaImp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
	
	
	$sqlBusq = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[3] != -1 && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	// CONSULTA DE LAS RETENCIONES DE FACTURAS DE OTROS PERIODOS
	$queryRetencionOtroPeriodo = sprintf("SELECT
		cxc_fact.idFactura AS idFactura,
		cxc_pago.fechaPago, 
		cxc_fact.fechaRegistroFactura AS fecha_factura,
		cxc_pago.numeroFactura AS numero_factura,
		cxc_fact.numeroControl AS numero_control_factura,
		cxc_fact.consecutivo_fiscal AS consecutivo_fiscal,
		cxc_fact.idCliente AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS cedula_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		retencion.fechaComprobante AS fecha_comprobante,
		retencion.numeroComprobante AS numero_comprobante,
		retencion_det.IvaRetenido AS iva_retenido,
		retencion_det.idRetencionDetalle
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		INNER JOIN cj_cc_retenciondetalle retencion_det ON (cxc_fact.idFactura = retencion_det.idFactura)
		INNER JOIN cj_cc_retencioncabezera retencion ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera AND cxc_pago.fechaPago = retencion.fechaComprobante)
	WHERE cxc_pago.formaPago = 9
		AND cxc_pago.fechaPago BETWEEN %s AND %s
		AND cxc_fact.fechaRegistroFactura < %s
		AND cxc_pago.estatus IN (1) %s
	GROUP BY retencion_det.idRetencionDetalle
	
	UNION
	
	SELECT
		cxc_fact.idFactura AS idFactura,
		cxc_pago.fechaPago, 
		cxc_fact.fechaRegistroFactura AS fecha_factura,
		cxc_pago.numeroFactura AS numero_factura,
		cxc_fact.numeroControl AS numero_control_factura,
		cxc_fact.consecutivo_fiscal AS consecutivo_fiscal,
		cxc_fact.idCliente AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS cedula_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		retencion.fechaComprobante AS fecha_comprobante,
		retencion.numeroComprobante AS numero_comprobante,
		retencion_det.IvaRetenido AS iva_retenido,
		retencion_det.idRetencionDetalle
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		INNER JOIN cj_cc_retenciondetalle retencion_det ON (cxc_fact.idFactura = retencion_det.idFactura)
		INNER JOIN cj_cc_retencioncabezera retencion ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera AND cxc_pago.fechaPago = retencion.fechaComprobante)
	WHERE cxc_pago.formaPago = 9
		AND cxc_pago.fechaPago BETWEEN %s AND %s
		AND cxc_fact.fechaRegistroFactura < %s
		AND cxc_pago.estatus IN (1) %s
	GROUP BY retencion_det.idRetencionDetalle
	ORDER BY 3 ASC",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		$sqlBusq,
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
		$sqlBusq);
	$rsRetencionOtroPeriodo = mysql_query($queryRetencionOtroPeriodo);
	if (!$rsRetencionOtroPeriodo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRetencionOtroPeriodo = mysql_num_rows($rsRetencionOtroPeriodo);
	if ($totalRowsRetencionOtroPeriodo > 0) {
		while ($rowRetencionOtroPeriodo = mysql_fetch_array($rsRetencionOtroPeriodo)){
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			for ($i = 0; $i < $contPosNac; $i++) {
				$arrayIvaDocNac[$i] = "";
			}
			for ($i = 0; $i < $contPosImp; $i++) {
				$arrayIvaDocImp[$i] = "";
			}
				
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($rowRetencionOtroPeriodo['fecha_factura']))."</td>";
				$htmlTb .= "<td align=\"center\">CR</td>";
				$htmlTb .= "<td nowrap=\"nowrap\">".$rowRetencionOtroPeriodo['numero_factura']."</td>";
				$htmlTb .= "<td nowrap=\"nowrap\">".$rowRetencionOtroPeriodo['numero_control_factura']."</td>";
				if (in_array(idArrayPais,array(2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$htmlTb .= "<td>".$rowRetencionOtroPeriodo['consecutivo_fiscal']."</td>";
				}
				$htmlTb .= "<td>".$rowRetencionOtroPeriodo['id_cliente']."</td>";
				$htmlTb .= "<td nowrap=\"nowrap\">".$rowRetencionOtroPeriodo['cedula_cliente']."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($rowRetencionOtroPeriodo['nombre_cliente'])."</td>";
				$htmlTb .= "<td>-</td>";
				$htmlTb .= "<td>-</td>";
				$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($rowRetencionOtroPeriodo['fecha_comprobante']))."</td>";
				$htmlTb .= "<td>".$rowRetencionOtroPeriodo['numero_comprobante']."</td>";
				$htmlTb .= "<td>-</td>";
				$htmlTb .= "<td>-</td>";
				$htmlTb .= "<td>-</td>";
				
				if (isset($arrayIvaDocNac)) {
					foreach ($arrayIvaDocNac as $indiceIvaDocNac => $valorIvaDocNac) {
						$htmlTb .= "<td>"."</td>";
					}
				}
				
				if (isset($arrayIvaDocImp)) {
					foreach ($arrayIvaDocImp as $indiceIvaDocImp => $valorIvaDocImp) {
						$htmlTb .= "<td>"."</td>";
					}
				}
				
				$htmlTb .= "<td>".formatoNumero($rowRetencionOtroPeriodo['iva_retenido'], $lstFormatoNumero)."</td>";
			$htmlTb .= "</tr>";
			
			$totalRetencionOtroPeriodo += $rowRetencionOtroPeriodo['iva_retenido'];
		}
	} else {
		for ($i = 0; $i < $contPosNac; $i++) {
			$arrayIvaDocNac[$i] = "";
		}
		for ($i = 0; $i < $contPosImp; $i++) {
			$arrayIvaDocImp[$i] = "";
		}
	}
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"center\" colspan=\"".$colspan."\">Total Retenciones de Otros Periodos</td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td></td>";
		
		if (isset($arrayIvaDocNac)) {
			foreach ($arrayIvaDocNac as $indiceIvaDocNac => $valorIvaDocNac) {
				$htmlTb .= "<td>"."</td>";
			}
		}
		
		if (isset($arrayIvaDocImp)) {
			foreach ($arrayIvaDocImp as $indiceIvaDocImp => $valorIvaDocImp) {
				$htmlTb .= "<td>"."</td>";
			}
		}
		
		$htmlTb .= "<td>".formatoNumero($totalRetencionOtroPeriodo, $lstFormatoNumero)."</td>";
	$htmlTb .= "</tr>";
	
	$primerNumeroFacturaRepuestos = '-';
	$ultimoNumeroFacturaRepuesto = '-';
	$primerNumeroFacturaServicios = '-';
	$ultimoNumeroFacturaServicio = '-';
	$primerNroFacturaIvaVehiculosGralMasAdic = '-';
	$ultimoNroFacturaIvaVehiculosGralMasAdic = '-';
	$primerNroFacturaIvaVehiculosGralSolo = '-';
	$ultimoNroFacturaIvaVehiculosGralSolo = '-';
	$primerNumeroFacturaAdmon = '-';
	$ultimoNumeroFacturaAdmon = '-';
	
	if ($totalRows > 0) {
		while ($row = mysql_fetch_assoc($rsLimit)) {
			$sqlBusq = "";
			$sqlBusq2 = "";
			$sqlBusq3 = "";
			if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
				$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxc_fact.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
				
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
				$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxc_nc.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
				
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
				$sqlBusq3 .= $cond.sprintf("(cxc_nd.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cxc_nd.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
			}
			
			if ($valCadBusq[3] != -1 && $valCadBusq[3] != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
				$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
					valTpDato($valCadBusq[3], "campo"));
				
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
				$sqlBusq2 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
					valTpDato($valCadBusq[3], "campo"));
				
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
				$sqlBusq3 .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
					valTpDato($valCadBusq[3], "campo"));
			}
			
			$queryEstadoCuenta = sprintf("
			SELECT
				cxc_fact.numeroControl,
				cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
				modulo.descripcionModulo,
				cxc_ec.*
			FROM cj_cc_estadocuenta cxc_ec
				INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_ec.idDocumento = cxc_fact.idFactura)
				INNER JOIN pg_modulos modulo ON (cxc_fact.idDepartamentoOrigenFactura = modulo.id_modulo)
			WHERE cxc_ec.tipoDocumento LIKE 'FA'
				AND DATE(cxc_fact.fechaRegistroFactura) BETWEEN %s AND %s %s
			
			UNION
			
			SELECT
				cxc_nc.numeroControl,
				cxc_nc.idDepartamentoNotaCredito AS id_modulo,
				modulo.descripcionModulo,
				cxc_ec.*
			FROM cj_cc_estadocuenta cxc_ec
				INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_ec.idDocumento = cxc_nc.idNotaCredito)
				INNER JOIN pg_modulos modulo ON (cxc_nc.idDepartamentoNotaCredito = modulo.id_modulo)
			WHERE cxc_ec.tipoDocumento LIKE 'NC'
				AND DATE(cxc_nc.fechaNotaCredito) BETWEEN %s AND %s %s
			
			UNION
			
			SELECT
				cxc_nd.numeroControlNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				modulo.descripcionModulo,
				cxc_ec.*
			FROM cj_cc_estadocuenta cxc_ec
				INNER JOIN cj_cc_notadecargo cxc_nd ON (cxc_ec.idDocumento = cxc_nd.idNotaCargo)
				INNER JOIN pg_modulos modulo ON (cxc_nd.idDepartamentoOrigenNotaCargo = modulo.id_modulo)
			WHERE cxc_ec.tipoDocumento LIKE 'ND'
				AND DATE(cxc_nd.fechaRegistroNotaCargo) BETWEEN %s AND %s %s
			ORDER BY 7 ASC, 1 ASC",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				$sqlBusq,
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				$sqlBusq2,
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				$sqlBusq3);
			$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
			if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
						$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = cxc_fact.id_empresa))",
							valTpDato($valCadBusq[0], "int"),
							valTpDato($valCadBusq[0], "int"));
						
						$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
						$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = cxc_nc.id_empresa))",
							valTpDato($valCadBusq[0], "int"),
							valTpDato($valCadBusq[0], "int"));
						
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
						$sqlBusq3 .= $cond.sprintf("(cxc_nd.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = cxc_nd.id_empresa))",
							valTpDato($valCadBusq[0], "int"),
							valTpDato($valCadBusq[0], "int"));
					}
					
					if ($valCadBusq[3] != -1 && $valCadBusq[3] != "") {
						$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
						$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
							valTpDato($valCadBusq[3], "campo"));
						
						$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
						$sqlBusq2 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
							valTpDato($valCadBusq[3], "campo"));
						
						$cond = (strlen($sqlBusq3) > 0) ? " AND " : " AND ";
						$sqlBusq3 .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
							valTpDato($valCadBusq[3], "campo"));
					}
					
					if ($rowEstadoCuenta['tipoDocumento'] == "FA") {
						$queryDocumento = sprintf("SELECT cxc_fact.*,
							cliente.id AS id_cliente,
							CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
							cliente.contribuyente
						FROM cj_cc_encabezadofactura cxc_fact
							INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
						WHERE cxc_fact.idFactura = %s
							AND cxc_fact.fechaRegistroFactura = %s
							AND cxc_fact.aplicaLibros = 1 %s",
							valTpDato($rowEstadoCuenta['idDocumento'], "int"),
							valTpDato($row['fecha_origen'], "date"),
							$sqlBusq);
						
					} else if ($rowEstadoCuenta['tipoDocumento'] == "NC") {
						$queryDocumento = sprintf("SELECT cxc_nc.*,
							cliente.id AS id_cliente,
							CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
							cliente.contribuyente
						FROM cj_cc_notacredito cxc_nc
							INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
						WHERE cxc_nc.idNotaCredito = %s
							AND cxc_nc.fechaNotaCredito = %s
							AND cxc_nc.aplicaLibros = 1 %s",
							valTpDato($rowEstadoCuenta['idDocumento'], "int"),
							valTpDato($row['fecha_origen'], "date"),
							$sqlBusq2);
						
					} else if ($rowEstadoCuenta['tipoDocumento'] == "ND") {
						$queryDocumento = sprintf("SELECT cxc_nd.*,
							cliente.id AS id_cliente,
							CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
							CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
							cliente.contribuyente
						FROM cj_cc_notadecargo cxc_nd
							INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
						WHERE cxc_nd.idNotaCargo = %s
							AND cxc_nd.fechaRegistroNotaCargo = %s
							AND cxc_nd.aplicaLibros = 1 %s",
							valTpDato($rowEstadoCuenta['idDocumento'], "int"),
							valTpDato($row['fecha_origen'], "date"),
							$sqlBusq3);
					}
					$rsDocumento = mysql_query($queryDocumento);
					if (!$rsDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
					$nroFiscal = "-";
					$serialImpresora = "-";
					$sumaRetenciones = 0;
					if ($rowEstadoCuenta['tipoDocumento'] == "FA" && $totalRowsDocumento > 0) {
						$fechaDocumento = date(spanDateFormat, strtotime($rowDocumento['fechaRegistroFactura']));
						$idDocumento = $rowDocumento['idFactura'];
						$origenDocumento = $rowDocumento['idDepartamentoOrigenFactura'];
						$tipoDocumento = "FA";
						$nroFactura = $rowDocumento['numeroFactura'];
						$nroControl = $rowDocumento['numeroControl'];
						$nroFiscal = $rowDocumento['consecutivo_fiscal'];
						$serialImpresora = $rowDocumento['serial_impresora'];
						
						$queryRetencionCabecera = sprintf("SELECT *,
							COUNT(retencion_det.idFactura) AS cant_retenciones,
							SUM(retencion_det.IvaRetenido) AS total_iva_retenido
						FROM cj_cc_retencioncabezera retencion
							INNER JOIN cj_cc_retenciondetalle retencion_det ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera)
						WHERE retencion_det.idFactura = %s
							AND retencion.fechaComprobante BETWEEN %s AND %s
						GROUP BY retencion_det.idFactura;",
							valTpDato($rowEstadoCuenta['idDocumento'], "int"),
							valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
							valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
						$rsRetencionCabecera = mysql_query($queryRetencionCabecera);
						if (!$rsRetencionCabecera) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsRetenciones = mysql_num_rows($rsRetencionCabecera);
						$rowRetencionCabecera = mysql_fetch_array($rsRetencionCabecera);
						
						if ($totalRowsRetenciones > 0
						&& $rowRetencionCabecera['mesPeriodoFiscal'] == date("m", strtotime($valCadBusq[1]))) {
							$fechaComprobanteRetencion75 = date(spanDateFormat, strtotime($rowRetencionCabecera['fechaComprobante']));
							$nroComprobanteRetencion75 = $rowRetencionCabecera['numeroComprobante'];
							$sumaRetenciones = $rowRetencionCabecera['total_iva_retenido'];
						}
						
						
						// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
						$queryFAGasto = sprintf("SELECT * FROM cj_cc_factura_gasto WHERE id_factura = %s;",
							valTpDato($rowDocumento['idFactura'], "int"));
						$rsFAGasto = mysql_query($queryFAGasto);
						if (!$rsFAGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$montoGastos = 0;
						while($rowFAGasto = mysql_fetch_array($rsFAGasto)) {
							$montoGastos += $rowFAGasto['monto'];
						}
						
						// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
						$queryFAIva = sprintf("SELECT * FROM cj_cc_factura_iva WHERE id_factura = %s;",
							valTpDato($rowDocumento['idFactura'], "int"));
						$rsFAIva = mysql_query($queryFAIva);
						if (!$rsFAIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
							
							if ($rowDocumento['contribuyente'] == 'No'){
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
							
							switch($rowDocumento['idDepartamentoOrigenFactura']) {
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
						
						$totalCompraConIva = ($rowDocumento['subtotalFactura'] - $rowDocumento['descuentoFactura']) + $montoGastos + $montoIva;
						
						$comprasExentas = $rowDocumento['montoExento'];
						$comprasExonerado = $rowDocumento['montoExonerado'];
						
						switch($rowDocumento['idDepartamentoOrigenFactura']) {
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
						$fechaDocumento = date(spanDateFormat, strtotime($rowDocumento['fechaNotaCredito']));
						$idDocumento = $rowDocumento['idNotaCredito'];
						$tipoDocumento = "NC";
						$nroFactura = "-";
						$nroControl = $rowDocumento['numeroControl'];
						$nroFiscal = $rowDocumento['consecutivo_fiscal'];
						$serialImpresora = $rowDocumento['serial_impresora'];
						
						$idNotaCredito = $rowDocumento['idNotaCredito'];
						$nroNotaCredito = $rowDocumento['numeracion_nota_credito'];
						$nroControlNotaCredito = $rowDocumento['numeroControl'];
						$idModuloNotaCredito = $rowDocumento['idDepartamentoNotaCredito'];
						$descripcionModuloNotaCredito = $rowEstadoCuenta['descripcionModulo'];
						
						if ($rowDocumento['tipoDocumento'] == "ND") {
							$queryND = sprintf("SELECT * FROM cj_cc_notadecargo WHERE idNotaCargo = %s;",
								valTpDato($rowDocumento['idDocumento'], "int"));
							$rsND = mysql_query($queryND);
							if (!$rsND) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$rowND = mysql_fetch_array($rsND);
						} else if ($rowDocumento['tipoDocumento'] == "FA") {
							$queryFA = sprintf("SELECT *
							FROM cj_cc_encabezadofactura cxc_fact
								INNER JOIN pg_modulos modulo ON (cxc_fact.idDepartamentoOrigenFactura = modulo.id_modulo)
							WHERE idFactura = %s;",
								valTpDato($rowDocumento['idDocumento'], "int"));
							$rsFA = mysql_query($queryFA);
							if (!$rsFA) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$rowFA = mysql_fetch_array($rsFA);
							
							$idDocumentoAfectado = $rowFA['idFactura'];
							$nroDocumentoAfectado = $rowFA['numeroFactura'];
							$nroControlDocumentoAfectado = $rowFA['numeroControl'];
							$idModuloDocumentoAfectado = $rowFA['idDepartamentoOrigenFactura'];
							$descripcionModuloDocumentoAfectado = $rowFA['descripcionModulo'];
							$tipoDocumentoAfectado = "FA";
						}
						
						
						// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
						$queryNCGasto = sprintf("SELECT * FROM cj_cc_nota_credito_gasto WHERE id_nota_credito = %s;",
							valTpDato($rowDocumento['idNotaCredito'], "int"));
						$rsNCGasto = mysql_query($queryNCGasto);
						if (!$rsNCGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$montoGastos = 0;
						while($rowNCGasto = mysql_fetch_array($rsNCGasto)) {
							$montoGastos += $rowNCGasto['monto'];
						}
						
						// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
						$queryNCIva = sprintf("SELECT * FROM cj_cc_nota_credito_iva WHERE id_nota_credito = %s;",
							valTpDato($rowDocumento['idNotaCredito'], "int"));
						$rsNCIva = mysql_query($queryNCIva);
						if (!$rsNCIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$montoIva = 0;
						$montoBaseImpIvas = 0;
						$montoIvaLujo = 0;
						while($rowNCIva = mysql_fetch_array($rsNCIva)){
							$idIva = $rowNCIva['id_iva'];
							$montoIva += $rowNCIva['subtotal_iva'];
							if ($rowNCIva['lujo'] == 1) {
								$montoIvaLujo = $rowNCIva['subtotal_iva'];
							} else {
								$montoBaseImpIvas += $rowNCIva['base_imponible'];
							}
							
							if ($rowDocumento['contribuyente'] == 'No'){
								foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
									if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_total_iva']] += doubleval($rowNCIva['subtotal_iva']);
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_base_iva']] += doubleval($rowNCIva['base_imponible']);
									}
								}
							} else {
								foreach ($arrayIvaImp as $indiceIvaImp => $valorIvaImp) {
									if ($idIva == $arrayIvaImp[$indiceIvaImp]['id_iva']) {
										$arrayIvaDocImp[$arrayIvaImp[$indiceIvaImp]['pos_total_iva']] += doubleval($rowNCIva['subtotal_iva']);
										$arrayIvaDocImp[($arrayIvaImp[$indiceIvaImp]['pos_base_iva'])] += doubleval($rowNCIva['base_imponible']);
									}
								}
							}
							
							switch($rowDocumento['idDepartamentoNotaCredito']) {
								case 0 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaRepuestos[$idIva]['subtotal_iva'] += (-1) * doubleval($rowNCIva['subtotal_iva']);
											$arrayTotalIvaRepuestos[$idIva]['base_imponible'] += (-1) * doubleval($rowNCIva['base_imponible']);
										}
									}
									break;
								case 1 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaServicios[$idIva]['subtotal_iva'] += (-1) * doubleval($rowNCIva['subtotal_iva']);
											$arrayTotalIvaServicios[$idIva]['base_imponible'] += (-1) * doubleval($rowNCIva['base_imponible']);
										}
									}
									break;
								case 2 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaVehiculos[$idIva]['subtotal_iva'] += (-1) * doubleval($rowNCIva['subtotal_iva']);
											$arrayTotalIvaVehiculos[$idIva]['base_imponible'] += (-1) * doubleval($rowNCIva['base_imponible']);
										}
									}
									break;
								default :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaAdmon[$idIva]['subtotal_iva'] += (-1) * doubleval($rowNCIva['subtotal_iva']);
											$arrayTotalIvaAdmon[$idIva]['base_imponible'] += (-1) * doubleval($rowNCIva['base_imponible']);
										}
									}
							}
						}
						
						$totalCompraConIva = ($rowDocumento['subtotalNotaCredito'] - $rowDocumento['subtotal_descuento']) + $montoGastos + $montoIva;
						
						$comprasExentas = $rowDocumento['montoExentoCredito'];
						$comprasExonerado = $rowDocumento['montoExoneradoCredito'];
						
						switch($rowDocumento['idDepartamentoNotaCredito']) {
							case 0 :
								$totalBaseIvaRepuestos += (-1) * $montoBaseImpIvas;
								$totalSubtotalIvaRepuestos += (-1) * $montoIva;
								$totalExentoRepuestos += (-1) * $comprasExentas;
								$totalExoneradoRepuestos += (-1) * $comprasExonerado;
								$totalRetenidoRepuestos += (-1) * $sumaRetenciones;
								break;
							case 1 :
								$totalBaseIvaServicios += (-1) * $montoBaseImpIvas;
								$totalSubtotalIvaServicios += (-1) * $montoIva;
								$totalExentoServicios += (-1) * $comprasExentas;
								$totalExoneradoServicios += (-1) * $comprasExonerado;
								$totalRetenidoServicios += (-1) * $sumaRetenciones;
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
									$totalRetenidoIvaVehiculosGralMasAdic += (-1) * $sumaRetenciones;
								} else {
									$totalBaseIvaVehiculosGralSolo += (-1) * $montoBaseImpIvas;
									$totalSubtotalIvaVehiculosGralSolo += (-1) * $montoIva;
									$totalExentoIvaVehiculosGralSolo += (-1) * $comprasExentas;
									$totalExoneradoIvaVehiculosGralSolo += (-1) * $comprasExonerado;
									$totalRetenidoIvaVehiculosGralSolo += (-1) * $sumaRetenciones;
								}
								break;
							default :
								$totalBaseIvaAdmon += (-1) * $montoBaseImpIvas;
								$totalSubtotalIvaAdmon += (-1) * $montoIva;
								$totalExentoAdmon += (-1) * $comprasExentas;
								$totalExoneradoAdmon += (-1) * $comprasExonerado;
								$totalRetenidoAdmon += (-1) * $sumaRetenciones;
						}
					} else if ($rowEstadoCuenta['tipoDocumento'] == "ND" && $totalRowsDocumento > 0) {
						$fechaDocumento = date(spanDateFormat, strtotime($rowDocumento['fechaRegistroNotaCargo']));
						$idDocumento = $rowDocumento['idNotaCargo'];
						$tipoDocumento = "ND";
						$nroFactura = $rowDocumento['numeroNotaCargo'];
						$nroControl = $rowDocumento['numeroControlNotaCargo'];
						
						
						// CONSULTA PARA SUMAR LOS GASTOS DEL DOCUMENTO
						$queryNDGasto = sprintf("SELECT * FROM cj_cc_nota_cargo_gasto WHERE id_nota_cargo = %s;",
							valTpDato($rowDocumento['idNotaCargo'], "int"));
						$rsNDGasto = mysql_query($queryNDGasto);
						if (!$rsNDGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$montoGastos = 0;
						$montoBaseImpIvas = 0;
						while ($rowNDGasto = mysql_fetch_array($rsNDGasto)) {
							$montoGastos += $rowNDGasto['monto'];
						}
						
						// CONSULTA PARA SUMAR LOS IVA DEL DOCUMENTO
						$queryNDIva = sprintf("SELECT * FROM cj_cc_nota_cargo_iva WHERE id_nota_cargo = %s;",
							valTpDato($rowDocumento['idNotaCargo'], "int"));
						$rsNDIva = mysql_query($queryNDIva);
						if (!$rsNDIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$montoIva = 0;
						$montoBaseImpIvas = 0;
						$montoIvaLujo = 0;
						while($rowNDIva = mysql_fetch_array($rsNDIva)){
							$idIva = $rowNDIva['id_iva'];
							$montoIva += $rowNDIva['subtotal_iva'];
							if ($rowNDIva['lujo'] == 1) {
								$montoIvaLujo = $rowNDIva['subtotal_iva'];
							} else {
								$montoBaseImpIvas += $rowNDIva['base_imponible'];
							}
							
							if ($rowDocumento['contribuyente'] == 'No'){
								foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
									if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_total_iva']] += doubleval($rowNDIva['subtotal_iva']);
										$arrayIvaDocNac[$arrayIvaNac[$indiceIvaNac]['pos_base_iva']] += doubleval($rowNDIva['base_imponible']);
									}
								}
							} else {
								foreach ($arrayIvaImp as $indiceIvaImp => $valorIvaImp) {
									if ($idIva == $arrayIvaImp[$indiceIvaImp]['id_iva']) {
										$arrayIvaDocImp[$arrayIvaImp[$indiceIvaImp]['pos_total_iva']] += doubleval($rowNDIva['subtotal_iva']);
										$arrayIvaDocImp[($arrayIvaImp[$indiceIvaImp]['pos_base_iva'])] += doubleval($rowNDIva['base_imponible']);
									}
								}
							}
							
							switch($rowDocumento['idDepartamentoOrigenNotaCargo']) {
								case 0 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaRepuestos[$idIva]['subtotal_iva'] += doubleval($rowNDIva['subtotal_iva']);
											$arrayTotalIvaRepuestos[$idIva]['base_imponible'] += doubleval($rowNDIva['base_imponible']);
										}
									}
									break;
								case 1 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaServicios[$idIva]['subtotal_iva'] += doubleval($rowNDIva['subtotal_iva']);
											$arrayTotalIvaServicios[$idIva]['base_imponible'] += doubleval($rowNDIva['base_imponible']);
										}
									}
									break;
								case 2 :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaVehiculos[$idIva]['subtotal_iva'] += doubleval($rowNDIva['subtotal_iva']);
											$arrayTotalIvaVehiculos[$idIva]['base_imponible'] += doubleval($rowNDIva['base_imponible']);
										}
									}
									break;
								default :
									foreach ($arrayIvaNac as $indiceIvaNac => $valorIvaNac) {
										if ($idIva == $arrayIvaNac[$indiceIvaNac]['id_iva']) {
											$arrayTotalIvaAdmon[$idIva]['subtotal_iva'] += doubleval($rowNDIva['subtotal_iva']);
											$arrayTotalIvaAdmon[$idIva]['base_imponible'] += doubleval($rowNDIva['base_imponible']);
										}
									}
							}
						}
						
						$totalCompraConIva = ($rowDocumento['subtotalNotaCargo'] - $rowDocumento['descuentoNotaCargo']) + $montoGastos + $montoIva;
						
						$comprasExentas = $rowDocumento['montoExentoNotaCargo'];
						$comprasExonerado = $rowDocumento['montoExoneradoNotaCargo'];
						
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
					$totalGlobalBaseImp = $totalBaseIvaRepuestos + $totalBaseIvaServicios + $totalBaseIvaVehiculos + $totalBaseIvaAdmon;
					$totalGlobalIva = $totalSubtotalIvaRepuestos + $totalSubtotalIvaServicios + $totalIvaVehiculos + $totalSubtotalIvaAdmon;
					$totalGlobalDocumentos = $totalGlobalBaseImp + $totalGlobalIva + $totalGlobalExento + $totalGlobalExonerado;
					
					if ($rowDocumento['idFactura'] > 0 || $rowDocumento['idNotaCredito'] > 0 || $rowDocumento['idNotaCargo'] > 0) {
						$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila++;
						
						if ($rowDocumento['idFactura'] > 0) {
							$signo = 1;
						} else if ($rowDocumento['idNotaCredito'] > 0) {
							$signo = (-1);
						} else if ($rowDocumento['idNotaCargo'] > 0) {
							$signo = 1;
						}
						
						$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$fechaDocumento."</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$tipoDocumento."</td>";
							$htmlTb .= "<td nowrap=\"nowrap\" title=\"".
								"Id Dcto.: ".$idDocumento.
								"\nNro. de Documento: ".$nroFactura.
								"\nNro. de Control: ".$nroControl.
								"\nMódulo: ".$rowEstadoCuenta['descripcionModulo']."\">";
								$objDcto = new Documento;
								$objDcto->raizDir = $raiz;
								$objDcto->tipoMovimiento = (in_array($rowEstadoCuenta['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
								$objDcto->tipoDocumento = $rowEstadoCuenta['tipoDocumento'];
								$objDcto->tipoDocumentoMovimiento = (in_array($rowEstadoCuenta['tipoDocumento'],array("NC"))) ? 2 : 1;
								$objDcto->idModulo = $rowEstadoCuenta['id_modulo'];
								$objDcto->idDocumento = $rowEstadoCuenta['idDocumento'];
								$objDcto->mostrarDocumento = "verRutaDetalle";
								$aVerDcto = $objDcto->verDocumento();
								$htmlTb .= (strlen($aVerDcto) > 0 && $idDocumento > 0) ? "<a class=\"linkAzulUnderline\" href=\"".$aVerDcto."\" target=\"_blank\">".$nroFactura."</a>" : $nroFactura;
							$htmlTb .= "</td>";
							$htmlTb .= "<td nowrap=\"nowrap\">".$nroControl."</td>";
							if (in_array(idArrayPais,array(2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
								$htmlTb .= "<td>".$nroFiscal."<br>".$serialImpresora."</td>";
							}
							$htmlTb .= "<td nowrap=\"nowrap\">".$rowDocumento['id_cliente']."</td>";
							$htmlTb .= "<td nowrap=\"nowrap\">".$rowDocumento['ci_cliente']."</td>";
							$htmlTb .= "<td align=\"left\" nowrap=\"nowrap\">".utf8_encode($rowDocumento['nombre_cliente'])."</td>";
							$htmlTb .= "<td nowrap=\"nowrap\" title=\"".
								"Id Dcto.: ".$idNotaCredito.
								"\nNro. Nota de Crédito / Débito: ".$nroNotaCredito.
								"\nNro. de Control Nota de Crédito / Débito: ".$nroControlNotaCredito.
								"\nMódulo Nota de Crédito / Débito: ".$descripcionModuloNotaCredito."\">";
								$objDcto = new Documento;
								$objDcto->raizDir = $raiz;
								$objDcto->tipoMovimiento = (in_array($rowEstadoCuenta['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
								$objDcto->tipoDocumento = $rowEstadoCuenta['tipoDocumento'];
								$objDcto->tipoDocumentoMovimiento = (in_array($rowEstadoCuenta['tipoDocumento'],array("NC"))) ? 2 : 1;
								$objDcto->idModulo = $idModuloNotaCredito;
								$objDcto->idDocumento = $idNotaCredito;
								$objDcto->mostrarDocumento = "verRutaDetalle";
								$aVerDcto = $objDcto->verDocumento();
								$htmlTb .= (strlen($aVerDcto) > 0 && $idNotaCredito > 0) ? "<a class=\"linkAzulUnderline\" href=\"".$aVerDcto."\" target=\"_blank\">".$nroNotaCredito."</a>" : $nroNotaCredito;
							$htmlTb .= "</td>";
							$htmlTb .= "<td nowrap=\"nowrap\" title=\"".
								"Id Dcto.: ".$idDocumentoAfectado.
								"\nNro. de Documento Afectado: ".$nroDocumentoAfectado.
								"\nNro. de Control de Documento Afectado: ".$nroControlDocumentoAfectado.
								"\nMódulo Documento Afectado: ".$descripcionModuloDocumentoAfectado."\">";
								$objDcto = new Documento;
								$objDcto->raizDir = $raiz;
								$objDcto->tipoMovimiento = (in_array($tipoDocumentoAfectado,array("FA","ND","AN","CH","TB"))) ? 3 : 2;
								$objDcto->tipoDocumento = $tipoDocumentoAfectado;
								$objDcto->tipoDocumentoMovimiento = (in_array($tipoDocumentoAfectado,array("NC"))) ? 2 : 1;
								$objDcto->idModulo = $idModuloDocumentoAfectado;
								$objDcto->idDocumento = $idDocumentoAfectado;
								$objDcto->mostrarDocumento = "verRutaDetalle";
								$aVerDcto = $objDcto->verDocumento();
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
						$arrayTotalDia['total_impuesto_retenido'] += $signo * $sumaRetenciones;
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
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td align=\"center\" colspan=\"".$colspan."\">Total Dia: ".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>" : "";
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".formatoNumero($arrayTotalDia['total_incluyendo_impuesto'], $lstFormatoNumero)."</td>" : "";
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".formatoNumero($arrayTotalDia['total_exento'], $lstFormatoNumero)."</td>" : "";
				$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".formatoNumero($arrayTotalDia['total_exonerado'], $lstFormatoNumero)."</td>" : "";
				
				if (isset($arrayTotalIvaDocNac)) {
					foreach ($arrayTotalIvaDocNac as $indice => $valor) {
						$htmlTb .= ($lstFormatoTotalDia == 1) ? "<td>".(($arrayTotalIvaDocNac[$indice] != 0) ? formatoNumero($arrayTotalIvaDocNac[$indice], $lstFormatoNumero) : "-")."</td>" : "";
						
						$arrayTotalesIvaDocNac[$indice] += $arrayTotalIvaDocNac[$indice];
					}
				}
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
		$htmlTb .= "<td align=\"center\" colspan=\"".$colspan."\">Total General del ".date(spanDateFormat, strtotime($valCadBusq[1]))." al ".date(spanDateFormat, strtotime($valCadBusq[2]))."</td>";
		$htmlTb .= "<td>".formatoNumero($arrayTotalLibro['total_incluyendo_impuesto'], $lstFormatoNumero)."</td>";
		$htmlTb .= "<td>".formatoNumero($arrayTotalLibro['total_exento'], $lstFormatoNumero)."</td>";
		$htmlTb .= "<td>".formatoNumero($arrayTotalLibro['total_exonerado'], $lstFormatoNumero)."</td>";
		
		if (isset($arrayTotalesIvaDocNac)) {
			foreach ($arrayTotalesIvaDocNac as $indice => $valor) {
				$htmlTb .= "<td title=\"arrayTotalesIvaDocNac[".$indice."]\">".(($arrayTotalesIvaDocNac[$indice] != 0) ? formatoNumero($arrayTotalesIvaDocNac[$indice], $lstFormatoNumero) : "-")."</td>";
			}
		}
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibro(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaLibro(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
	
	$objResponse->assign("tdLibro","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	
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
		$htmlCuadro .= "<td width=\"5%\">Desde</td>";
		$htmlCuadro .= "<td width=\"5%\">Hasta</td>";
		$htmlCuadro .= "<td width=\"10%\">Base Imponible</td>";
		$htmlCuadro .= "<td width=\"10%\">I.V.A.</td>";
		$htmlCuadro .= "<td width=\"10%\">Exentas</td>";
		$htmlCuadro .= "<td width=\"10%\">Exoneradas</td>";
		$htmlCuadro .= "<td width=\"10%\">Total</td>";
		$htmlCuadro .= "<td width=\"10%\">I.V.A. Retenido</td>";
	$htmlCuadro .= "</tr>";
	
	$htmlCuadro .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadro .= "<td>Retenciones Vencidas Otros Meses</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>".formatoNumero($totalRetencionOtroPeriodo, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo2SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Venta de Repuestos Alicuota General (".implode(", ",$porcIvaRepuestosGralPredet).")</td>";
		if ($totalFacturaRepuestosGralPredet >  0) {

					$primero= $primerNumeroFacturaRepuestos;
					$segundo= $ultimoNumeroFacturaRepuesto;
					$totalprimero= formatoNumero($totalRetenidoRepuestos, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoRepuestos, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoRepuestos, $lstFormatoNumero);
					$totalRetenidoRepuestos = '';
					$totalExentoRepuestos = '';
					$totalExoneradoRepuestos = '';
				# code...
			}else{
					$primero= '-';
					$segundo= '-';
					$totalprimero=  '-';
					$totaltercero = '-';
					$totalsegundo = '-';

			}
		$htmlCuadro .= "<td>".$primero."</td>";
		$htmlCuadro .= "<td>".$segundo."</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaRepuestosGralPredet\">".formatoNumero($totalBaseIvaRepuestosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaRepuestosGralPredet\">".formatoNumero($totalSubtotalIvaRepuestosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoRepuestos\">".$totalsegundo."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoRepuestos\">".$totaltercero."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaRepuestosGralPredet\">".formatoNumero($totalFacturaRepuestosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoRepuestos\">".$totalprimero."</td>";
	$htmlCuadro .= "</tr>";
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo2SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Venta de Repuestos Alicuota General (".implode(", ",$porcIvaRepuestosGral).")</td>";
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
		$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo2SinBorde\" height=\"24\">";
			$htmlCuadro .= "<td>Libro Venta de Repuestos Alicuota General (".$arrayIva['porc_iva']."%)</td>";
			//javier  y alexander listado iva
			if ($arrayIva['total'] >  0) {

					$primero= $primerNumeroFacturaRepuestos;
					$segundo= $ultimoNumeroFacturaRepuesto;
					$totalprimero= formatoNumero($totalRetenidoRepuestos, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoRepuestos, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoRepuestos, $lstFormatoNumero);


				# code...
			}else{
					$primero= '-';
					$segundo= '-';
					$totalprimero='-';
					$totalsegundo='-';
					$totaltercero='-';

			}
			$htmlCuadro .= "<td>".$primero."</td>";
			$htmlCuadro .= "<td>".$segundo."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['base_imponible'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['subtotal_iva'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalsegundo."</td>";
			$htmlCuadro .= "<td>".$totaltercero."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['total'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalprimero."</td>";
		$htmlCuadro .= "</tr>";
	}
	
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfoSinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Venta de Servicios Alicuota General (".implode(", ",$porcIvaServiciosGralPredet).")</td>";

			if ($totalFacturaServiciosGralPredet >  0) {

					$primero= $primerNumeroFacturaServicios;
					$segundo= $ultimoNumeroFacturaServicio;
					$totalprimero= formatoNumero($totalRetenidoServicios, $lstFormatoNumero);
					$totalRetenidoServicios ='';
					$totalsegundo= formatoNumero($totalExentoServicios, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoServicios, $lstFormatoNumero);
					$totalRetenidoServicios = '';
					$totalExentoServicios = '';
					$totalExoneradoServicios = '';
				# code...
			}else{
					$primero= '-';
					$segundo= '-';
					$totalprimero=  '-';
					$totalsegundo=  '-';
					$totaltercero=  '-';

			}
		$htmlCuadro .= "<td>".$primero."</td>";
		$htmlCuadro .= "<td>".$segundo."</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaServiciosGralPredet\">".formatoNumero($totalBaseIvaServiciosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaServiciosGralPredet\">".formatoNumero($totalSubtotalIvaServiciosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoServicios\">".$totalsegundo."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoServicios\">".$totaltercero."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaServiciosGralPredet\">".formatoNumero($totalFacturaServiciosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoServicios\">".$totalprimero."</td>";
	$htmlCuadro .= "</tr>";
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfoSinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Venta de Servicios Alicuota General (".implode(", ",$porcIvaServiciosGral).")</td>";
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

					$primero= $primerNumeroFacturaServicios;
					$segundo= $ultimoNumeroFacturaServicio;
					$totalprimero= formatoNumero($totalRetenidoServicios, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoServicios, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoServicios, $lstFormatoNumero);

				# code...
			}else{
					$primero= '-';
					$segundo= '-';
					$totalprimero=  '-';
					$totalsegundo=  '-';
					$totaltercero=  '-';

			}
		$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfoSinBorde\" height=\"24\">";
			$htmlCuadro .= "<td>Libro Venta de Servicios Alicuota General (".$arrayIva['porc_iva']."%)</td>";
			$htmlCuadro .= "<td>".$primero."</td>";
			$htmlCuadro .= "<td>".$segundo."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['base_imponible'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['subtotal_iva'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalsegundo."</td>";
			$htmlCuadro .= "<td>".$totaltercero."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['total'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalprimero."</td>";
		$htmlCuadro .= "</tr>";
	}
	
	$totalFacturaVehiculosGralSolo = $totalBaseIvaVehiculosGralSolo + $totalSubtotalIvaVehiculosGralSolo + $totalExentoIvaVehiculosGralSolo + $totalExoneradoIvaVehiculosGralSolo;
	/*$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Venta de Vehiculos Alicuota General</td>";
		$htmlCuadro .= "<td>".$primerNroFacturaIvaVehiculosGralSolo."</td>";
		$htmlCuadro .= "<td>".$ultimoNroFacturaIvaVehiculosGralSolo."</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaVehiculosGralSolo\">".formatoNumero($totalBaseIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaVehiculosGralSolo\">".formatoNumero($totalSubtotalIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoIvaVehiculosGralSolo\">".formatoNumero($totalExentoIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoIvaVehiculosGralSolo\">".formatoNumero($totalExoneradoIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaVehiculosGralSolo\">".formatoNumero($totalFacturaVehiculosGralSolo, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoIvaVehiculosGralSolo\">".formatoNumero($totalRetenidoIvaVehiculosGralSolo, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";*/
	
	//GENERAL predeterminado 12% gregor	
	if ($totalFacturaVehiculosGralPredet >  0) {

					$primero= $primerNroFacturaIvaVehiculosGralSolo;
					$segundo= $ultimoNroFacturaIvaVehiculosGralSolo;
					$totalprimero= formatoNumero($totalRetenidoVehiculos, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoVehiculos, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoVehiculos, $lstFormatoNumero);
					$totalRetenidoVehiculos = '';
					$totalExentoVehiculos = '';
					$totalExoneradoVehiculos = '';
				# code...
				# code...
			}else{
					$primero= '-';
					$segundo= '-';
					$totalprimero=  '-';
					$totalsegundo=  '-';
					$totaltercero=  '-';


			}
	$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Venta de Vehiculos Alicuota General (".implode(", ",$porcIvaVehiculosGralPredet).")</td>";
		$htmlCuadro .= "<td>".$primero."</td>";
		$htmlCuadro .= "<td>".$segundo."</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaVehiculosGralPredet\">".formatoNumero($totalBaseIvaVehiculosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaVehiculosGralPredet\">".formatoNumero($totalSubtotalIvaVehiculosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoVehiculos\">".$totalsegundo."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoVehiculos\">".$totaltercero."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaVehiculosGralPredet\">".formatoNumero($totalFacturaVehiculosGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoServicios\">".$totalprimero."</td>";
	$htmlCuadro .= "</tr>";
	
	foreach($arrayIvaGeneralVehiculos as $arrayIva){ // Separacion ivas multiples
		if ($arrayIva['total'] >  0) {

					$primero= $primerNroFacturaIvaVehiculosGralSolo;
					$segundo= $ultimoNroFacturaIvaVehiculosGralSolo;
					$totalprimero= formatoNumero($totalRetenidoVehiculos, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoVehiculos, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoVehiculos, $lstFormatoNumero);

				# code...
			}else{
					$primero= '-';
					$segundo= '-';
					$totalprimero=  '-';
					$totalsegundo=  '-';
					$totaltercero=  '-';

			}
		$htmlCuadro .= "<tr align=\"center\" bgcolor=\"#CCCCCC\" height=\"24\">";
			$htmlCuadro .= "<td>Libro Venta de Vehiculos Alicuota General (".$arrayIva['porc_iva']."%)</td>";
			$htmlCuadro .= "<td>".$primero."</td>";
			$htmlCuadro .= "<td>".$segundo."</td>";
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
		$htmlCuadro .= "<td>Ventas Internas de Vehiculos Gravadas por Alicuota General más Alicuota Adicional</td>";
		$htmlCuadro .= "<td>".$primerNroFacturaIvaVehiculosGralMasAdic."</td>";
		$htmlCuadro .= "<td>".$ultimoNroFacturaIvaVehiculosGralMasAdic."</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaVehiculosGralMasAdic\">".formatoNumero($totalBaseIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaVehiculosGralMasAdic\">".formatoNumero($totalSubtotalIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoIvaVehiculosGralMasAdic\">".formatoNumero($totalExentoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoIvaVehiculosGralMasAdic\">".formatoNumero($totalExoneradoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaVehiculosGralMasAdic\">".formatoNumero($totalFacturaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoIvaVehiculosGralMasAdic\">".formatoNumero($totalRetenidoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
	$htmlCuadro .= "</tr>";
	
		if ($totalFacturaVehiculosGralPredet >  0) {

					$primero= $totalBaseIvaAdmonGralPredet;
					$segundo= $totalBaseIvaAdmonGralPredet;
					$totalprimero= formatoNumero($totalRetenidoAdmon, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoAdmon, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoAdmon, $lstFormatoNumero);
					$totalRetenidoAdmon = '';
					$totalExentoAdmon = '';
					$totalExoneradoAdmon = '';
				# code...
			}else{
						$primero= '-';
					$segundo= '-';
					$totalprimero=  '-';
					$totalsegundo=  '-';
					$totaltercero=  '-';

			}
	$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo4SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Venta de Administración Alicuota General (".implode(", ",$porcIvaAdmonGralPredet).")</td>";
		$htmlCuadro .= "<td>".$primero."</td>";
		$htmlCuadro .= "<td>".$segundo."</td>";
		$htmlCuadro .= "<td title=\"totalBaseIvaAdmonGralPredet\">".formatoNumero($totalBaseIvaAdmonGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalSubtotalIvaAdmonGralPredet\">".formatoNumero($totalSubtotalIvaAdmonGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalExentoAdmon\">".$totalsegundo."</td>";
		$htmlCuadro .= "<td title=\"totalExoneradoAdmon\">".$totaltercero."</td>";
		$htmlCuadro .= "<td title=\"totalFacturaAdmonGralPredet\">".formatoNumero($totalFacturaAdmonGralPredet, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalRetenidoAdmon\">".$totalprimero."</td>";
	$htmlCuadro .= "</tr>";
	/*$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo4SinBorde\" height=\"24\">";
		$htmlCuadro .= "<td>Libro Venta de Administración Alicuota General (".implode(", ",$porcIvaAdmonGral).")</td>";
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

					$primero= $primerNumeroFacturaAdmon;
					$segundo= $ultimoNumeroFacturaAdmon;
					$totalprimero= formatoNumero($totalRetenidoAdmon, $lstFormatoNumero);
					$totalsegundo= formatoNumero($totalExentoAdmon, $lstFormatoNumero);
					$totaltercero= formatoNumero($totalExoneradoAdmon, $lstFormatoNumero);

				# code...
			}else{
						$primero= '-';
					$segundo= '-';
					$totalprimero=  '-';
					$totalsegundo=  '-';
					$totaltercero=  '-';

			}
		$htmlCuadro .= "<tr align=\"center\" class=\"divMsjInfo4SinBorde\" height=\"24\">";
			$htmlCuadro .= "<td>Libro Venta de Administración Alicuota General (".$arrayIva['porc_iva']."%)</td>";
			$htmlCuadro .= "<td>".$primero."</td>";
			$htmlCuadro .= "<td>".$segundo."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['base_imponible'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['subtotal_iva'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalsegundo."</td>";
			$htmlCuadro .= "<td>".$totaltercero."</td>";
			$htmlCuadro .= "<td>".formatoNumero($arrayIva['total'], $lstFormatoNumero)."</td>";
			$htmlCuadro .= "<td>".$totalprimero."</td>";
		$htmlCuadro .= "</tr>";
	}
	
	$totalGlobalExento = $arrayTotalLibro['total_exento'];
	$totalGlobalExonerado = $arrayTotalLibro['total_exonerado'];
	$totalGlobalExentoExonerado = $totalGlobalExento + $totalGlobalExonerado;
	$htmlCuadro .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadro .= "<td>Ventas No Grabadas y/o Sin Derecho a Credito Fiscal</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExentoExonerado\">".formatoNumero($totalGlobalExentoExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td>-</td>";
	$htmlCuadro .= "</tr>";
	
	$totalGlobalVentaConIva = ($totalFacturaRepuestosGralPredet + $totalFacturaRepuestosGral) + ($totalFacturaServiciosGralPredet + $totalFacturaServiciosGral) + $totalFacturaVehiculos + ($totalFacturaAdmonGralPredet + $totalFacturaAdmonGral);
	$totalGlobalRetenido = $arrayTotalLibro['total_impuesto_retenido'];
	$htmlCuadro .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadro .= "<td>Total de Ventas y Debitos Fiscales</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td>-</td>";
		$htmlCuadro .= "<td title=\"totalGlobalBaseImp\">".formatoNumero($totalGlobalBaseImp, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalIva\">".formatoNumero($totalGlobalIva, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadro .= "<td title=\"totalGlobalVentaConIva\">".formatoNumero($totalGlobalVentaConIva, $lstFormatoNumero)."</td>";
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
		$htmlCuadroResumen .= "<td>Ventas No Grabadas y/o Sin Derecho a Credito Fiscal</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExentoExonerado\">".formatoNumero($totalGlobalExentoExonerado, $lstFormatoNumero)."</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Ventas Internas Gravadas por Alicuota General</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalBaseImp\">".formatoNumero($totalGlobalBaseImp, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalIva\">".formatoNumero($totalGlobalIva, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalVentaConIva\">".formatoNumero($totalGlobalVentaConIva, $lstFormatoNumero)."</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Ventas Internas Gravadas por Alicuota General más Alicuota Adicional</td>";
		$htmlCuadroResumen .= "<td title=\"totalBaseIvaVehiculosGralMasAdic\">".formatoNumero($totalBaseIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalSubtotalIvaVehiculosGralMasAdic\">".formatoNumero($totalSubtotalIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalExentoIvaVehiculosGralMasAdic\">".formatoNumero($totalExentoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalExoneradoIvaVehiculosGralMasAdic\">".formatoNumero($totalExoneradoIvaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalFacturaVehiculosGralMasAdic\">".formatoNumero($totalFacturaVehiculosGralMasAdic, $lstFormatoNumero)."</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Ventas Internas Gravadas por Alícuota Reducida</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Ajustes a Debitos Fiscales</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
		$htmlCuadroResumen .= "<td>-</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "<tr align=\"center\" height=\"24\">";
		$htmlCuadroResumen .= "<td>Total de Ventas y Debitos Fiscales</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalBaseImp\">".formatoNumero($totalGlobalBaseImp, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalIva\">".formatoNumero($totalGlobalIva, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExento\">".formatoNumero($totalGlobalExento, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalExonerado\">".formatoNumero($totalGlobalExonerado, $lstFormatoNumero)."</td>";
		$htmlCuadroResumen .= "<td title=\"totalGlobalVentaConIva\">".formatoNumero($totalGlobalVentaConIva, $lstFormatoNumero)."</td>";
	$htmlCuadroResumen .= "</tr>";
	
	$htmlCuadroResumen .= "</table>";
	
	$objResponse->assign("tdCuadroLibro","innerHTML",$htmlCuadro);
	$objResponse->assign("tdCuadroLibroResumen","innerHTML",$htmlCuadroResumen);
	
	$objResponse->script("xajax_encabezadoEmpresa(".((count(explode(",",$valCadBusq[0])) > 1) ? $_SESSION['idEmpresaUsuarioSysGts'] : $valCadBusq[0]).")");
	
	$objResponse->script("$('.tituloColumna td').css({ 'border-radius' : '0px'});");
	
	return $objResponse;
}

function volver(){
	$objResponse = new xajaxResponse();
	
	$objResponse -> script(sprintf("window.open('cc_libro_venta.php','_self');"));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"listaLibro");
$xajax->register(XAJAX_FUNCTION,"volver");

function formatoNumero($monto, $idFormatoNumero = 1){
	switch($idFormatoNumero) {
		case 1 : return number_format($monto, 2, ".", ","); break;
		case 2 : return number_format($monto, 2, ",", "."); break;
		case 3 : return number_format($monto, 2, ".", ""); break;
		case 4 : return number_format($monto, 2, ",", ""); break;
		default : return number_format($monto, 2, ".", ",");
	}
}
?>