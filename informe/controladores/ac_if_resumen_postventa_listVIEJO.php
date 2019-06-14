<?php
set_time_limit(0);

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();

	$objResponse->loadCommands(facturacionVendedores($frmBuscar));

	return $objResponse;
}

function exportarResumen($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha']);
	
	$objResponse->script("window.open('reportes/if_resumen_postventa_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function comprasRepuestos($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// COMPRAS DE REPUESTOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (0)
	AND (id_tipo_movimiento IN (1)
		OR (id_tipo_movimiento IN (4) AND (tipo_vale IS NULL OR tipo_vale IN (3))))");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fecha_movimiento) = %s
		AND YEAR(fecha_movimiento) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}

	// Agrupa los tipos de movimiento por clave de movimiento para luego calcular el total de ese movimiento
	$queryTipoMov = sprintf("SELECT
		id_clave_movimiento,
			
		(SELECT clave_mov.clave
		FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave,
		
		descripcion,
		id_tipo_movimiento,
		(CASE id_tipo_movimiento
			WHEN 1 THEN 'Compra'
			WHEN 2 THEN 'Entrada'
			WHEN 3 THEN 'Venta'
			WHEN 4 THEN 'Salida'
		END) AS tipo_movimiento,
		id_modulo
	FROM vw_iv_movimiento %s
	GROUP BY id_clave_movimiento, descripcion, id_tipo_movimiento
	ORDER BY clave ASC;", $sqlBusq);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$arrayDet = NULL;
	$array = NULL;
	while ($rowMovDet = mysql_fetch_array($rsTipoMov)) {
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 = $cond.sprintf("mov.id_clave_movimiento = %s",
			valTpDato($rowMovDet['id_clave_movimiento'], "int"));
	
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(mov.fecha_movimiento) = %s
			AND YEAR(mov.fecha_movimiento) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
		}
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 = $cond.sprintf("mov_det.id_movimiento IN (SELECT
				mov.id_movimiento
			FROM iv_movimiento mov
				INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);
		
		$queryDetalle = sprintf("SELECT
			mov_det.cantidad,
			mov_det.precio,
			mov_det.porcentaje_descuento,
			mov_det.costo,
			(IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) AS importePv,
			((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100) AS descuento,
			((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) - ((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100)) AS neto,
			(IFNULL(mov_det.costo, 0) * IFNULL(mov_det.cantidad, 0)) AS importeCosto
		FROM iv_movimiento_detalle mov_det
			INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
		ORDER BY id_movimiento_detalle;", $sqlBusq3);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalImportePv = 0;
		$totalDescuento = 0;
		$totalUtilidad = 0;
		$totalNeto = 0;
		$totalImporteC = 0;
		$porcentajeUtilidad = 0;
		$porcentajeDescuento = 0;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$importePv = $rowDetalle['importePv'];
			$descuento = $rowDetalle['descuento'];
			$neto = $rowDetalle['neto'];
			$importeCosto = $rowDetalle['importeCosto'];
	
			$importeC = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
	
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
		
		if ($rowMovDet['id_tipo_movimiento'] == 1 || $rowMovDet['id_tipo_movimiento'] == 3) {
			$totalNetoClaveMovCompras += $totalNeto;
		} else if ($rowMovDet['id_tipo_movimiento'] == 2 || $rowMovDet['id_tipo_movimiento'] == 4) {
			$totalNetoClaveMovCompras -= $totalNeto;
		}
		
		$arrayMovDet[0] = $rowMovDet['id_tipo_movimiento'];
		$arrayMovDet[1] = $rowMovDet['clave'].") ".$rowMovDet['descripcion'];
		$arrayMovDet[2] = $totalNeto;
		$arrayMovCompras[] = $arrayMovDet;
	}

	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"52%\">Clave de Movimiento</td>
					<td width=\"36%\">Importe ".cAbrevMoneda."</td>
					<td width=\"12%\">%</td>";
	$htmlTh .= "</tr>";

	$contFila = 0;
	$arrayMec = NULL;
	if (isset($arrayMovCompras)) {
		foreach ($arrayMovCompras as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			if ($arrayMovCompras[$indice][0] == 1 || $arrayMovCompras[$indice][0] == 3) {
				$arrayMovCompras[$indice][2] = $arrayMovCompras[$indice][2];
			} else if ($arrayMovCompras[$indice][0] == 2 || $arrayMovCompras[$indice][0] == 4) {
				$arrayMovCompras[$indice][2] = (-1) * $arrayMovCompras[$indice][2];
			}

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayMovCompras[$indice][1])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format($arrayMovCompras[$indice][2],2,".",",");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format((($arrayMovCompras[$indice][2] * 100) / $totalNetoClaveMovCompras),2,".",",")."%";
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			
			$arrayMes = NULL;
			$arrayDet2[0] = $mes[intval($valFecha[0])]." ".$valFecha[1];
			$arrayDet2[1] = $arrayMovCompras[$indice][2];
			$arrayMes[] = implode("+*+",$arrayDet2);
			
			$arrayDet[0] = str_replace(","," ",utf8_encode($arrayMovCompras[$indice][1]));
			$arrayDet[1] = implode("-*-",$arrayMes);
			$arrayClave[] = implode("=*=",$arrayDet);
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".utf8_encode("Total Compras Repuestos:")."</td>";
		$htmlTb .= "<td>".number_format($totalNetoClaveMovCompras,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">COMPRAS DE REPUESTOS</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						1,
						"Column with negative values",
						"COMPRAS DE REPUESTOS",
						str_replace("'","|*|",array("")),
						"Monto",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaComprasRepuestos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
}

function analisisInventario($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ANÁLISIS DE INVENTARIO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$sqlBusq = "";	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_mens.mes = %s
		AND cierre_mens.ano = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}

	// AGRUPA LAS CLASIFICACIONES PARA CALCULAR SUS TOTALES
	$queryTipoMov = sprintf("SELECT
		analisis_inv.id_analisis_inventario,
		analisis_inv_det.clasificacion
	FROM iv_analisis_inventario_detalle analisis_inv_det
		INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
		INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
		INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s
	GROUP BY analisis_inv_det.clasificacion", $sqlBusq);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while($rowMovDet = mysql_fetch_array($rsTipoMov)){
		$idAnalisisInv = $rowMovDet['id_analisis_inventario'];

		$queryDetalle = sprintf("SELECT
			analisis_inv_det.id_analisis_inventario,
			analisis_inv_det.cantidad_existencia,
			analisis_inv_det.cantidad_disponible_logica,
			analisis_inv_det.cantidad_disponible_fisica,
			analisis_inv_det.costo,
			(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia) AS costo_total,
			(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
			analisis_inv_det.promedio_diario,
			analisis_inv_det.promedio_mensual,
			(analisis_inv_det.promedio_mensual * 2) AS inventario_recomendado,
			(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * 2)) AS sobre_stock,
			((analisis_inv_det.promedio_mensual * 2) - analisis_inv_det.cantidad_existencia) AS sugerido,
			analisis_inv_det.clasificacion
		FROM iv_analisis_inventario_detalle analisis_inv_det
			INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
			INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
			INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual)
		WHERE analisis_inv.id_analisis_inventario = %s
			AND ((analisis_inv_det.clasificacion = %s AND %s IS NOT NULL)
				OR analisis_inv_det.clasificacion IS NULL AND %s IS NULL)",
			valTpDato($idAnalisisInv, "int"),
			valTpDato($rowMovDet['clasificacion'], "text"), valTpDato($rowMovDet['clasificacion'], "text"),
			valTpDato($rowMovDet['clasificacion'], "text"));
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$cantArt = 0;
		$exist = 0;
		$costoInv = 0;
		$promVenta = 0;
		$mesesExist = 0;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$cantArt++;
			$exist += round($rowDetalle['cantidad_existencia'],2);
			$costoInv += round($rowDetalle['costo_total'],2);
			$promVenta += round($rowDetalle['promedio_mensual'] * $rowDetalle['costo'],2);
		}


		$arrayDet[0] = $rowMovDet['clasificacion'];
		$arrayDet[1] = $cantArt;
		$arrayDet[2] = $exist;
		$arrayDet[3] = $costoInv;
		$arrayDet[4] = $promVenta;
		$arrayDet[5] = ($promVenta > 0) ? ($costoInv / $promVenta) : 0;
		
		$arrayAnalisisInv[] = $arrayDet;

		$totalCantArt += $cantArt;
		$totalExistArt += $exist;
		$totalCostoInv += $costoInv;
		$totalPromVentas += $promVenta;
		$totalExistNroArt += $exist / $cantArt;
	}
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">".("Clasif.")."</td>
					<td width=\"10%\">".("Nro. Items")."</td>
					<td width=\"10%\">".("% Items")."</td>
					<td width=\"10%\">".("Existencia")."</td>
					<td width=\"10%\">".("% Existencia")."</td>
					<td width=\"10%\">".("Importe ".cAbrevMoneda)."</td>
					<td width=\"10%\">".("% Importe ".cAbrevMoneda)."</td>
					<td width=\"10%\">".("Prom. Ventas ".cAbrevMoneda)."</td>
					<td width=\"10%\">".("Meses Exist.")."</td>
					<td width=\"10%\">".("Exist. / Nro. Items")."</td>";
	$htmlTh .= "</tr>";

	$contFila = 0;
	if (isset($arrayAnalisisInv)) {
		foreach ($arrayAnalisisInv as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"center\">".$arrayAnalisisInv[$indice][0]."</td>";
				$htmlTb .= "<td>".number_format($arrayAnalisisInv[$indice][1],2,".",",")."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= ($totalCantArt > 0) ? number_format(($arrayAnalisisInv[$indice][1] * 100 / $totalCantArt),2,".",",") : 0;
				$htmlTb .= "%</td>";
				$htmlTb .= "<td>".number_format($arrayAnalisisInv[$indice][2],2,".",",")."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= ($totalExistArt > 0) ? number_format(($arrayAnalisisInv[$indice][2] * 100 / $totalExistArt),2,".",",") : 0;
				$htmlTb .= "%</td>";
				$htmlTb .= "<td>".number_format($arrayAnalisisInv[$indice][3],2,".",",")."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= ($totalCostoInv > 0) ? number_format(($arrayAnalisisInv[$indice][3] * 100 / $totalCostoInv),2,".",",") : 0;
				$htmlTb .= "%</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format($arrayAnalisisInv[$indice][4],2,".",",");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format($arrayAnalisisInv[$indice][5],2,".",",");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".number_format(($arrayAnalisisInv[$indice][2] / $arrayAnalisisInv[$indice][1]),2,".",",")."</td>";
			$htmlTb .= "</tr>";

			$data1 .= (strlen($data1) > 0) ? "['".utf8_encode($arrayAnalisisInv[$indice][0])."', ".$arrayAnalisisInv[$indice][1]."]," : "{ name: '".utf8_encode($arrayAnalisisInv[$indice][0])."', y: ".$arrayAnalisisInv[$indice][1].", sliced: true, selected: true },";
		}
	}
	$data1 = substr($data1, 0, (strlen($data1)-1));
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total:</td>";
		$htmlTb .= "<td>".number_format($totalCantArt,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
		$htmlTb .= "<td>".number_format($totalExistArt,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
		$htmlTb .= "<td>".number_format($totalCostoInv,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
		$htmlTb .= "<td>".number_format($totalPromVentas,2,".",",")."</td>";
		$htmlTb .= "<td>";
			$htmlTb .= ($totalPromVentas > 0) ? number_format($totalCostoInv / $totalPromVentas,2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "</td>";
		$htmlTb .= "<td>".number_format($totalExistNroArt,2,".",",")."</td>";
	$htmlTb .= "<tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">ANÁLISIS DE INVENTARIO</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						2,
						"Pie with legend",
						"ANÁLISIS DE INVENTARIO",
						str_replace("'","|*|",array("")),
						"Monto",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaAnalisisInv","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
}

function cantidadItemsVendidos($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$sqlBusq = "";	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("cierre_mens.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_mens.mes = %s
		AND cierre_mens.ano = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// AGRUPA LAS CLASIFICACIONES PARA CALCULAR SUS TOTALES
	$queryTipoMov = sprintf("SELECT
		analisis_inv.id_analisis_inventario,
		cierre_mens.id_empresa,
		cierre_mens.ano,
		analisis_inv_det.clasificacion_anterior
	FROM iv_analisis_inventario_detalle analisis_inv_det
		INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
		INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
		INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s
	GROUP BY clasificacion_anterior", $sqlBusq);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTipoMov = mysql_fetch_assoc($rsTipoMov)) {
		$sqlBusq2 = "";	
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " AND ";
			$sqlBusq2 .= $cond.sprintf("cierre_anual.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		}
		
		$queryNroVend = sprintf("SELECT
			cierre_anual.%s AS numero_vendido
		FROM iv_cierre_anual cierre_anual
		WHERE cierre_anual.id_articulo IN (SELECT id_articulo FROM iv_analisis_inventario_detalle analisis_inv_det
				WHERE analisis_inv_det.id_analisis_inventario = %s AND analisis_inv_det.clasificacion_anterior = %s)
			AND cierre_anual.ano = %s
			AND cierre_anual.%s IS NOT NULL
			AND cierre_anual.%s > 0 %s",
			valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
			valTpDato($rowTipoMov['id_analisis_inventario'], "int"), valTpDato($rowTipoMov['clasificacion_anterior'], "text"),
			valTpDato($rowTipoMov['ano'], "int"),
			valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
			valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
			$sqlBusq2);
		$rsNroVend = mysql_query($queryNroVend);
		if (!$rsNroVend) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNroVend = mysql_num_rows($rsNroVend);

		$queryCantVend = sprintf("SELECT SUM(IFNULL(cierre_anual.%s, 0)) AS cantidad_vendida
		FROM iv_cierre_anual cierre_anual
		WHERE cierre_anual.id_articulo IN (SELECT id_articulo FROM iv_analisis_inventario_detalle analisis_inv_det
				WHERE analisis_inv_det.id_analisis_inventario = %s AND analisis_inv_det.clasificacion_anterior = %s)
			AND cierre_anual.ano = %s %s",
			valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
			valTpDato($rowTipoMov['id_analisis_inventario'], "int"), valTpDato($rowTipoMov['clasificacion_anterior'], "text"),
			valTpDato($rowTipoMov['ano'], "int"),
			$sqlBusq2);
		$rsCantVend = mysql_query($queryCantVend);
		if (!$rsCantVend) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCantVend = mysql_fetch_assoc($rsCantVend);

		$arrayDet[0] = $rowTipoMov['clasificacion_anterior'];
		$arrayDet[1] = $totalRowsNroVend;
		$arrayDet[2] = $rowCantVend['cantidad_vendida'];

		$arrayCantArtVend[] = $arrayDet;

		$totalNroArt += $totalRowsNroVend;
		$totalCantArtVend += $rowCantVend['cantidad_vendida'];
	}

	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<thead>";
		$htmlTh .= "<td colspan=\"14\">"."<p class=\"textoAzul\">CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS</p>"."</td>";
	$htmlTh .= "</thead>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">".("Clasif.")."</td>
					<td width=\"30%\">".("Nro. Items Vendidos")."</td>
					<td width=\"30%\">".("% Items Vendidos")."</td>
					<td width=\"20%\">".("Cant. Art. Vendidos")."</td>
					<td width=\"10%\">".("% Art. Vendidos")."</td>";
	$htmlTh .= "</tr>";

	$contFila = 0;
	if (isset($arrayCantArtVend)) {
		foreach ($arrayCantArtVend as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;

			$htmlTb .= "<tr class=\"".$clase."\">";
				$htmlTb .= "<td align=\"center\">".$arrayCantArtVend[$indice][0]."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayCantArtVend[$indice][1],2,".",",")."</td>";
				$htmlTb .= "<td align=\"right\">";
					$htmlTb .= ($totalNroArt > 0) ? number_format(($arrayCantArtVend[$indice][1] * 100 / $totalNroArt),2,".",",") : 0;
				$htmlTb .= "%</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayCantArtVend[$indice][2],2,".",",")."</td>";
				$htmlTb .= "<td align=\"right\">";
					$htmlTb .= ($totalCantArtVend > 0) ? number_format(($arrayCantArtVend[$indice][2] * 100 / $totalCantArtVend),2,".",",") : 0;
				$htmlTb .= "%</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total:</td>";
		$htmlTb .= "<td>".number_format($totalNroArt,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
		$htmlTb .= "<td>".number_format($totalCantArtVend,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
	$htmlTb .= "<tr>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaCantidadVendida","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
}

function ventasRepuestosMostrador($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTAS DE REPUESTOS POR MOSTRADOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (0)
	AND (id_tipo_movimiento IN (3)
		OR (id_tipo_movimiento IN (2) AND (tipo_vale IS NULL OR tipo_vale IN (3))))");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fecha_movimiento) = %s
		AND YEAR(fecha_movimiento) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}

	// Agrupa los tipos de movimiento por clave de movimiento para luego calcular el total de ese movimiento
	$queryTipoMov = sprintf("SELECT
		id_clave_movimiento,
			
		(SELECT clave_mov.clave
		FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave,
		
		descripcion,
		id_tipo_movimiento,
		(CASE id_tipo_movimiento
			WHEN 1 THEN 'Compra'
			WHEN 2 THEN 'Entrada'
			WHEN 3 THEN 'Venta'
			WHEN 4 THEN 'Salida'
		END) AS tipo_movimiento,
		id_modulo
	FROM vw_iv_movimiento %s
	GROUP BY id_clave_movimiento, descripcion, id_tipo_movimiento
	ORDER BY clave ASC;", $sqlBusq);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$arrayDet = NULL;
	$array = NULL;
	while($rowMovDet = mysql_fetch_array($rsTipoMov)){
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 = $cond.sprintf("mov.id_clave_movimiento = %s",
			valTpDato($rowMovDet['id_clave_movimiento'], "int"));
		
		/*if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(SELECT vw_iv_mov.id_empresa FROM vw_iv_movimiento vw_iv_mov
			WHERE vw_iv_mov.id_movimiento = mov_det.id_movimiento) = %s",
				valTpDato($idEmpresa, "date"));
		}*/
	
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(mov.fecha_movimiento) = %s
			AND YEAR(mov.fecha_movimiento) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
		}
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 = $cond.sprintf("mov_det.id_movimiento IN (SELECT
				mov.id_movimiento
			FROM iv_movimiento mov
				INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);
		
		$queryDetalle = sprintf("SELECT
			mov_det.cantidad,
			mov_det.precio,
			mov_det.porcentaje_descuento,
			mov_det.costo,
			(IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) AS importePv,
			((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100) AS descuento,
			((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) - ((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100)) AS neto,
			(IFNULL(mov_det.costo, 0) * IFNULL(mov_det.cantidad, 0)) AS importeCosto
		FROM iv_movimiento_detalle mov_det
			INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
		ORDER BY id_movimiento_detalle;", $sqlBusq3);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalImportePv = 0;
		$totalDescuento = 0;
		$totalUtilidad = 0;
		$totalNeto = 0;
		$totalImporteC = 0;
		$porcentajeUtilidad = 0;
		$porcentajeDescuento = 0;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$importePv = $rowDetalle['importePv'];
			$descuento = $rowDetalle['descuento'];
			$neto = $rowDetalle['neto'];
			$importeCosto = $rowDetalle['importeCosto'];
	
			$importeC = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
	
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
	
		if ($rowMovDet['id_tipo_movimiento'] == 1 || $rowMovDet['id_tipo_movimiento'] == 3) {
			$totalNetoClaveMovVentas += $totalNeto;
		} else if ($rowMovDet['id_tipo_movimiento'] == 2 || $rowMovDet['id_tipo_movimiento'] == 4) {
			$totalNetoClaveMovVentas -= $totalNeto;
		}
	
		$arrayMovDet[0] = $rowMovDet['id_tipo_movimiento'];
		$arrayMovDet[1] = $rowMovDet['clave'].") ".$rowMovDet['descripcion'];
		$arrayMovDet[2] = $totalNeto;
		$arrayMovVentas[] = $arrayMovDet;
	}
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"52%\">Clave de Movimiento</td>
					<td width=\"36%\">Importe ".cAbrevMoneda."</td>
					<td width=\"12%\">%</td>";
	$htmlTh .= "</tr>";

	$contFila = 0;
	if (isset($arrayMovVentas)) {
		foreach ($arrayMovVentas as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			if ($arrayMovVentas[$indice][0] == 1 || $arrayMovVentas[$indice][0] == 3) {
				$arrayMovVentas[$indice][2] = $arrayMovVentas[$indice][2];
			} else if ($arrayMovVentas[$indice][0] == 2 || $arrayMovVentas[$indice][0] == 4) {
				$arrayMovVentas[$indice][2] = (-1) * $arrayMovVentas[$indice][2];
			}

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayMovVentas[$indice][1])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format($arrayMovVentas[$indice][2],2,".",",");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format((($arrayMovVentas[$indice][2] * 100) / $totalNetoClaveMovVentas),2,".",",")."%";
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			
			$arrayMes = NULL;
			$arrayDet2[0] = $mes[intval($valFecha[0])]." ".$valFecha[1];
			$arrayDet2[1] = $arrayMovVentas[$indice][2];
			$arrayMes[] = implode("+*+",$arrayDet2);
			
			$arrayDet[0] = str_replace(","," ",utf8_encode($arrayMovVentas[$indice][1]));
			$arrayDet[1] = implode("-*-",$arrayMes);
			$arrayClave[] = implode("=*=",$arrayDet);
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".utf8_encode("Total Ventas Repuestos y Accesorios:")."</td>";
		$htmlTb .= "<td>".number_format($totalNetoClaveMovVentas,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTAS DE REPUESTOS POR MOSTRADOR</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						3,
						"Column with negative values",
						"VENTAS DE REPUESTOS POR MOSTRADOR",
						str_replace("'","|*|",array("")),
						"Monto",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaVentasRepuestos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
}

function ventasRepuestosServicios($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTAS DE REPUESTOS POR SERVICIOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (1)
	AND (id_tipo_movimiento IN (3,4)
		OR (id_tipo_movimiento IN (2) AND tipo_vale IS NULL))");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fecha_movimiento) = %s
		AND YEAR(fecha_movimiento) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// Agrupa los tipos de movimiento por clave de movimiento para luego calcular el total de ese movimiento
	$queryTipoMov = sprintf("SELECT
		id_clave_movimiento,
			
		(SELECT clave_mov.clave
		FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.id_clave_movimiento = vw_iv_movimiento.id_clave_movimiento) AS clave,
		
		descripcion,
		id_tipo_movimiento,
		(CASE id_tipo_movimiento
			WHEN 1 THEN 'Compra'
			WHEN 2 THEN 'Entrada'
			WHEN 3 THEN 'Venta'
			WHEN 4 THEN 'Salida'
		END) AS tipo_movimiento,
		id_modulo
	FROM vw_iv_movimiento %s
	GROUP BY id_clave_movimiento, descripcion, id_tipo_movimiento
	ORDER BY descripcion", $sqlBusq);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$arrayDet = NULL;
	$array = NULL;
	while($rowMovDet = mysql_fetch_array($rsTipoMov)){
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 = $cond.sprintf("mov.id_clave_movimiento = %s",
			valTpDato($rowMovDet['id_clave_movimiento'], "int"));
	
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(fecha_movimiento) = %s
			AND YEAR(fecha_movimiento) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
		}
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 = $cond.sprintf("id_movimiento IN (SELECT
				mov.id_movimiento
			FROM iv_movimiento mov
				INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);
		
		$queryDetalle = sprintf("SELECT
			mov_det.cantidad,
			mov_det.precio,
			mov_det.porcentaje_descuento,
			mov_det.costo,
			(IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) AS importePv,
			((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100) AS descuento,
			((IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0)) - ((IFNULL(mov_det.porcentaje_descuento, 0) * (IFNULL(mov_det.cantidad, 0) * IFNULL(mov_det.precio, 0))) / 100)) AS neto,
			(IFNULL(mov_det.costo, 0) * IFNULL(mov_det.cantidad, 0)) AS importeCosto
		FROM iv_movimiento_detalle mov_det
			INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
		ORDER BY id_movimiento_detalle;", $sqlBusq3);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalImportePv = 0;
		$totalDescuento = 0;
		$totalUtilidad = 0;
		$totalNeto = 0;
		$totalImporteC = 0;
		$porcentajeUtilidad = 0;
		$porcentajeDescuento = 0;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$importePv = $rowDetalle['importePv'];
			$descuento = $rowDetalle['descuento'];
			$neto = $rowDetalle['neto'];
			$importeCosto = $rowDetalle['importeCosto'];
	
			$importeC = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
	
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
	
		if ($rowMovDet['id_tipo_movimiento'] == 1 || $rowMovDet['id_tipo_movimiento'] == 3) {
			$totalNetoClaveMovVentasServ += $totalNeto;
		} else if ($rowMovDet['id_tipo_movimiento'] == 2) {
			$totalNetoClaveMovVentasServ -= $totalNeto;
		} else if ($rowMovDet['id_tipo_movimiento'] == 4) {
			$totalNetoClaveMovVentasServ += $totalNeto;
		}
	
		$arrayMovDet[0] = $rowMovDet['id_tipo_movimiento'];
		$arrayMovDet[1] = $rowMovDet['clave'].") ".$rowMovDet['descripcion'];
		$arrayMovDet[2] = $totalNeto;
		$arrayMovVentasServ[] = $arrayMovDet;
	}

	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"52%\">Clave de Movimiento</td>
					<td width=\"36%\">Importe ".cAbrevMoneda."</td>
					<td width=\"12%\">%</td>";
	$htmlTh .= "</tr>";

	$contFila = 0;
	if (isset($arrayMovVentasServ)) {
		foreach ($arrayMovVentasServ as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			if ($arrayMovVentasServ[$indice][0] == 1 || $arrayMovVentasServ[$indice][0] == 3) {
				$arrayMovVentasServ[$indice][2] = $arrayMovVentasServ[$indice][2];
			} else if ($arrayMovVentasServ[$indice][0] == 2 || $arrayMovVentasServ[$indice][0] == 4) {
				$arrayMovVentasServ[$indice][2] = (-1) * $arrayMovVentasServ[$indice][2];
			}

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayMovVentasServ[$indice][1])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format($arrayMovVentasServ[$indice][2],2,".",",");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format((($arrayMovVentasServ[$indice][2] * 100) / $totalNetoClaveMovVentasServ),2,".",",")."%";
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			
			$arrayMes = NULL;
			$arrayDet2[0] = $mes[intval($valFecha[0])]." ".$valFecha[1];
			$arrayDet2[1] = $arrayMovVentasServ[$indice][2];
			$arrayMes[] = implode("+*+",$arrayDet2);
			
			$arrayDet[0] = str_replace(","," ",utf8_encode($arrayMovVentasServ[$indice][1]));
			$arrayDet[1] = implode("-*-",$arrayMes);
			$arrayClave[] = implode("=*=",$arrayDet);
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".utf8_encode("Total Ventas Repuestos y Accesorios por Servicios:")."</td>";
		$htmlTb .= "<td>".number_format($totalNetoClaveMovVentasServ,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTAS DE REPUESTOS POR SERVICIOS</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						4,
						"Column with negative values",
						"VENTAS DE REPUESTOS POR SERVICIOS",
						str_replace("'","|*|",array("")),
						"Monto",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaVentasServicios","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
}

function facturacionAsesoresServicios($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// FACTURACIÓN ASESORES DE SERVICIOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$total1 = 0;
	$total2 = 0;
	$total3 = 0;
	$i = 0;

	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"28%\">Asesor</td>";
		$htmlTh .= "<td width=\"14%\">M/Obra</td>";
		$htmlTh .= "<td width=\"14%\">Rptos.</td>";
		$htmlTh .= "<td width=\"14%\">T.O.T.</td>";
		$htmlTh .= "<td width=\"18%\">Total</td>";
		$htmlTh .= "<td width=\"12%\">%</td>";
	$htmlTh .= "</tr>";

	$sql1 = "SELECT
		id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
	FROM pg_empleado empleado
	ORDER BY id_empleado";
	$rs1 = mysql_query($sql1);
	if (!$rs1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row1 = mysql_fetch_assoc($rs1)) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado = %s
		AND aprobado = 1",
			valTpDato($row1['id_empleado'], "int"));
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		}
	
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(fecha_filtro) = %s
			AND YEAR(fecha_filtro) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
		}
		
		// SOLO APLICA PARA LAS MANO DE OBRA
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp.estado_tempario IN ('FACTURADO','TERMINADO')");
		
		// MANO DE OBRAS FACTURAS DE SERVICIOS
		$sql2 = sprintf("SELECT
			sa_v_inf_final_temp.id_empleado,
			
			SUM((CASE sa_v_inf_final_temp.id_modo
				WHEN 1 THEN -- UT
					(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
				WHEN 2 THEN -- PRECIO
					sa_v_inf_final_temp.precio
			END)) AS total_tempario_orden
			
		FROM sa_v_informe_final_tempario sa_v_inf_final_temp %s %s
		GROUP BY sa_v_inf_final_temp.id_empleado", $sqlBusq, $sqlBusq2);
		$rs2 = mysql_query($sql2);
		if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row2 = mysql_fetch_assoc($rs2);
		
		// MANO DE OBRAS NOTAS DE CREDITO DE SERVICIOS
		$sql3 = sprintf("SELECT
			sa_v_inf_final_temp.id_empleado,
			
			SUM((CASE sa_v_inf_final_temp.id_modo
				WHEN 1 THEN -- UT
					(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
				WHEN 2 THEN -- PRECIO
					sa_v_inf_final_temp.precio
			END)) AS total_tempario_dev_orden
			
		FROM sa_v_informe_final_tempario_dev sa_v_inf_final_temp %s %s
		GROUP BY sa_v_inf_final_temp.id_empleado", $sqlBusq, $sqlBusq2);
		$rs3 = mysql_query($sql3);
		if (!$rs3) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row3 = mysql_fetch_assoc($rs3);
		
		// MANO DE OBRAS VALE DE SALIDA DE SERVICIOS
		$sql4 = sprintf("SELECT
			sa_v_inf_final_temp.id_empleado,
			
			SUM((CASE sa_v_inf_final_temp.id_modo
				WHEN 1 THEN -- UT
					(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
				WHEN 2 THEN -- PRECIO
					sa_v_inf_final_temp.precio
			END)) AS total_tempario_vale
			
		FROM sa_v_vale_informe_final_tempario sa_v_inf_final_temp %s %s
		GROUP BY sa_v_inf_final_temp.id_empleado", $sqlBusq, $sqlBusq2);
		$rs4 = mysql_query($sql4);
		if (!$rs4) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row4 = mysql_fetch_assoc($rs4);
		
		
		// TOT FACTURAS DE SERVICIOS
		$sql5 = sprintf("SELECT
			sa_v_inf_final_tot.id_empleado,
			SUM(sa_v_inf_final_tot.monto_total + ((sa_v_inf_final_tot.porcentaje_tot * sa_v_inf_final_tot.monto_total) / 100)) AS total_tot_orden
		FROM sa_v_informe_final_tot sa_v_inf_final_tot %s
		GROUP BY sa_v_inf_final_tot.id_empleado", $sqlBusq);
		$rs5 = mysql_query($sql5);
		if (!$rs5) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFacturaTot = mysql_fetch_assoc($rs5);
		
		// TOT NOTAS DE CREDITO DE SERVICIOS
		$queryNotaCreditoTot = sprintf("SELECT
			sa_v_inf_final_tot.id_empleado,
			SUM(sa_v_inf_final_tot.monto_total + ((sa_v_inf_final_tot.porcentaje_tot * sa_v_inf_final_tot.monto_total) / 100)) AS total_tot_dev_orden
		FROM sa_v_informe_final_tot_dev sa_v_inf_final_tot %s
		GROUP BY sa_v_inf_final_tot.id_empleado", $sqlBusq);
		$rsNotaCreditoTot = mysql_query($queryNotaCreditoTot);
		if (!$rsNotaCreditoTot) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCreditoTot = mysql_fetch_assoc($rsNotaCreditoTot);
		
		// TOT VALE DE SALIDA DE SERVICIOS
		$sql6 = sprintf("SELECT
			sa_v_inf_final_tot.id_empleado,
			SUM(sa_v_inf_final_tot.monto_total + ((sa_v_inf_final_tot.porcentaje_tot * sa_v_inf_final_tot.monto_total) / 100)) AS total_tot_vale
		FROM sa_v_vale_informe_final_tot sa_v_inf_final_tot %s
		GROUP BY sa_v_inf_final_tot.id_empleado", $sqlBusq);
		$rs6 = mysql_query($sql6);
		if (!$rs6) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowValeSalidaTot = mysql_fetch_assoc($rs6);
		
		
		// REPUESTOS FACTURAS DE SERVICIOS
		$sql7 = sprintf("SELECT
			sa_v_inf_final_repuesto.id_empleado,
			SUM((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad)) AS total_repuesto_orden,
			SUM(((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad) * sa_v_inf_final_repuesto.porcentaje_descuento_orden) / 100) AS total_descuento_orden
		FROM sa_v_informe_final_repuesto  sa_v_inf_final_repuesto %s
		GROUP BY sa_v_inf_final_repuesto.id_empleado", $sqlBusq);
		$rs7 = mysql_query($sql7);
		if (!$rs7) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row7 = mysql_fetch_assoc($rs7);
		
		// REPUESTOS NOTAS DE CREDITO DE SERVICIOS
		$sql8 = sprintf("SELECT
			sa_v_inf_final_repuesto.id_empleado,
			SUM((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad)) AS total_repuesto_dev_orden,
			SUM(((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad) * sa_v_inf_final_repuesto.porcentaje_descuento_orden) / 100) AS total_descuento_dev_orden
		FROM sa_v_informe_final_repuesto_dev sa_v_inf_final_repuesto %s
		GROUP BY sa_v_inf_final_repuesto.id_empleado", $sqlBusq);
		$rs8 = mysql_query($sql8);
		if (!$rs8) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row8 = mysql_fetch_assoc($rs8);
		
		// REPUESTOS VALE DE SALIDA DE SERVICIOS
		$sql9 = sprintf("SELECT
			sa_v_inf_final_repuesto.id_empleado,
			SUM((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad)) AS total_repuesto_vale,
			SUM(((sa_v_inf_final_repuesto.precio_unitario * sa_v_inf_final_repuesto.cantidad) * sa_v_inf_final_repuesto.porcentaje_descuento_orden) / 100) AS total_descuento_vale
		FROM sa_v_vale_informe_final_repuesto sa_v_inf_final_repuesto %s
		GROUP BY sa_v_inf_final_repuesto.id_empleado", $sqlBusq);
		$rs9 = mysql_query($sql9);
		if (!$rs9) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row9 = mysql_fetch_assoc($rs9);
		
		if ($row2 || $row3 || $row4
		|| $rowFacturaTot || $rowNotaCreditoTot || $rowValeSalidaTot
		|| $row7 || $row8 || $row9) {
			$totalMoAsesor = ($row2['total_tempario_orden']) + (-1 * $row3['total_tempario_dev_orden']) + $row4['total_tempario_vale'];
			
			$totalRepuetosAsesor = ($row7['total_repuesto_orden'] - $row7['total_descuento_orden']) + (-1 * ($row8['total_repuesto_dev_orden'] - $row8['total_descuento_dev_orden'])) + ($row9['total_repuesto_vale'] - $row9['total_descuento_vale']);
			
			$totalTotAsesor = $rowFacturaTot['total_tot_orden'] + (-1 * $rowNotaCreditoTot['total_tot_dev_orden']) + $rowValeSalidaTot['total_tot_vale'];

			$total1 += $totalMoAsesor;
			$total2 += $totalRepuetosAsesor;
			$total3 += $totalTotAsesor;
			$total4 += $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor;

			$arrayVentaAsesor[] = array('nombre_asesor'=> $row1['nombre_empleado'],
				'total_mo'=> $totalMoAsesor,
				'total_repuestos'=> $totalRepuetosAsesor,
				'total_tot'=> $totalTotAsesor,
				'total_asesor'=> $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor);
		}
	}

	for ($i = 0; $i < count($arrayVentaAsesor); $i++) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$porcAsesor = ($arrayVentaAsesor[$i]['total_asesor'] * 100) / $total4;

		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">".utf8_encode($arrayVentaAsesor[$i]['nombre_asesor'])."</td>";
			$htmlTb .= "<td>".number_format($arrayVentaAsesor[$i]['total_mo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayVentaAsesor[$i]['total_repuestos'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayVentaAsesor[$i]['total_tot'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayVentaAsesor[$i]['total_asesor'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($porcAsesor, 2, ".", ",")."%</td>";
		$htmlTb .= "</tr>";
		
		$data1 .= (strlen($data1) > 0) ? "['".utf8_encode($arrayVentaAsesor[$i]['nombre_asesor'])."', ".$arrayVentaAsesor[$i]['total_asesor']."]," : "{ name: '".utf8_encode($arrayVentaAsesor[$i]['nombre_asesor'])."', y: ".$arrayVentaAsesor[$i]['total_asesor'].", sliced: true, selected: true },";
		
		$porcTotalAsesor += $porcAsesor;
	}
	$data1 = substr($data1, 0, (strlen($data1)-1));
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total Facturación Asesores:</td>";
		$htmlTb .= "<td>".number_format($total1, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($total2, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($total3, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($total4, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($porcTotalAsesor, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">FACTURACIÓN ASESORES DE SERVICIOS</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						5,
						"Pie with legend",
						"FACTURACIÓN ASESORES DE SERVICIOS",
						str_replace("'","|*|",array("")),
						"Monto",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaFacturacionAsesoresServicios","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
}

function facturacionTecnicosServicios($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// FACTURACIÓN TÉCNICOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$sql0 = "SELECT
		id_equipo_mecanico,
		nombre_equipo
	FROM sa_equipos_mecanicos
	ORDER BY nombre_equipo";
	$rs0 = mysql_query($sql0);
	if (!$rs0) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row0 = mysql_fetch_assoc($rs0)) {
		$totalMecanicoBs = 0;
		$totalMecanicoUts = 0;
		$i = 0;
		
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.aprobado = 1
		AND sa_v_inf_final_temp.estado_tempario IN ('FACTURADO')");
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.aprobado = 1
		AND sa_v_inf_final_temp_dev.estado_tempario IN ('FACTURADO')");
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("sa_v_vale_inf_final_temp.aprobado = 1
		AND sa_v_vale_inf_final_temp.estado_tempario IN ('TERMINADO')");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("sa_v_vale_inf_final_temp.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		}
		
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(sa_v_inf_final_temp.fecha_filtro) = %s
			AND YEAR(sa_v_inf_final_temp.fecha_filtro) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(sa_v_inf_final_temp_dev.fecha_filtro) = %s
			AND YEAR(sa_v_inf_final_temp_dev.fecha_filtro) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
			
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("MONTH(sa_v_vale_inf_final_temp.fecha_filtro) = %s
			AND YEAR(sa_v_vale_inf_final_temp.fecha_filtro) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
		}

		$sql1 = sprintf("SELECT
			vw_pg_empleado.id_empleado,
			vw_pg_empleado.nombre_empleado AS nombre_completo,
			mec.id_mecanico,
			mec.nivel
		FROM sa_mecanicos mec
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (mec.id_empleado = vw_pg_empleado.id_empleado)
		WHERE id_equipo_mecanico = %s
			AND (mec.id_mecanico IN (SELECT id_mecanico
									FROM sa_det_orden_tempario det_orden_temp
										INNER JOIN sa_v_informe_final_tempario sa_v_inf_final_temp
											ON (det_orden_temp.id_det_orden_tempario = sa_v_inf_final_temp.id_det_orden_tempario) %s)
				OR mec.id_mecanico IN (SELECT id_mecanico
									FROM sa_det_orden_tempario det_orden_temp
										INNER JOIN sa_v_informe_final_tempario_dev sa_v_inf_final_temp_dev
											ON (det_orden_temp.id_det_orden_tempario = sa_v_inf_final_temp_dev.id_det_orden_tempario) %s)
				OR mec.id_mecanico IN (SELECT id_mecanico
									FROM sa_det_vale_salida_tempario det_vale_temp
										INNER JOIN sa_v_vale_informe_final_tempario sa_v_vale_inf_final_temp
											ON (det_vale_temp.id_det_vale_salida_tempario = sa_v_vale_inf_final_temp.id_det_vale_salida_tempario) %s))
		ORDER BY nombre_completo",
			valTpDato($row0['id_equipo_mecanico'], "int"),
			$sqlBusq,
			$sqlBusq2,
			$sqlBusq3);
		$rsMercanicos = mysql_query($sql1);
		if (!$rsMercanicos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsMercanicos = mysql_num_rows($rsMercanicos);

		$arrayTecnico = NULL;
		while ($row1 = mysql_fetch_assoc($rsMercanicos)) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("det_orden_temp.id_mecanico = %s
			AND sa_v_inf_final_temp.aprobado = 1
			AND sa_v_inf_final_temp.estado_tempario IN ('FACTURADO')",
				valTpDato($row1['id_mecanico'], "int"));
			
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("det_orden_temp.id_mecanico = %s
			AND sa_v_inf_final_temp_dev.aprobado = 1
			AND sa_v_inf_final_temp_dev.estado_tempario IN ('FACTURADO')",
				valTpDato($row1['id_mecanico'], "int"));
			
			$sqlBusq3 = "";
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("det_vale_temp.id_mecanico = %s
			AND sa_v_vale_inf_final_temp.aprobado = 1
			AND sa_v_vale_inf_final_temp.estado_tempario IN ('TERMINADO')",
				valTpDato($row1['id_mecanico'], "int"));
			
			if ($idEmpresa != "-1" && $idEmpresa != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_empresa = %s",
					valTpDato($idEmpresa, "int"));
				
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("sa_v_inf_final_temp_dev.id_empresa = %s",
					valTpDato($idEmpresa, "int"));
				
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 .= $cond.sprintf("sa_v_vale_inf_final_temp.id_empresa = %s",
					valTpDato($idEmpresa, "int"));
			}
			
			if ($valFecha[0] != "-1" && $valFecha[0] != ""
			&& $valFecha[1] != "-1" && $valFecha[1] != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("MONTH(sa_v_inf_final_temp.fecha_filtro) = %s
				AND YEAR(sa_v_inf_final_temp.fecha_filtro) = %s",
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"));
				
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("MONTH(sa_v_inf_final_temp_dev.fecha_filtro) = %s
				AND YEAR(sa_v_inf_final_temp_dev.fecha_filtro) = %s",
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"));
				
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 .= $cond.sprintf("MONTH(sa_v_vale_inf_final_temp.fecha_filtro) = %s
				AND YEAR(sa_v_vale_inf_final_temp.fecha_filtro) = %s",
					valTpDato($valFecha[0], "date"),
					valTpDato($valFecha[1], "date"));
			}
			
			// FACTURAS
			$sql2 = sprintf("SELECT
				SUM((CASE sa_v_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						(sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						0
				END)) AS uts,
				
				SUM((CASE sa_v_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						(sa_v_inf_final_temp.ut * sa_v_inf_final_temp.precio_tempario_tipo_orden) / sa_v_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_inf_final_temp.precio
				END)) AS valor_uts
				
			FROM sa_det_orden_tempario det_orden_temp
				INNER JOIN sa_v_informe_final_tempario sa_v_inf_final_temp ON (det_orden_temp.id_det_orden_tempario = sa_v_inf_final_temp.id_det_orden_tempario) %s;", $sqlBusq);
			$rs2 = mysql_query($sql2);
			if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row2 = mysql_fetch_assoc($rs2);

			// NOTAS DE CREDITO
			$sql3 = sprintf("SELECT
				SUM((CASE sa_v_inf_final_temp_dev.id_modo
					WHEN 1 THEN -- UT
						(sa_v_inf_final_temp_dev.ut) / sa_v_inf_final_temp_dev.base_ut_precio
					WHEN 2 THEN -- PRECIO
						0
				END)) AS uts_dev,
				
				SUM((CASE sa_v_inf_final_temp_dev.id_modo
					WHEN 1 THEN -- UT
						(sa_v_inf_final_temp_dev.ut * sa_v_inf_final_temp_dev.precio_tempario_tipo_orden) / sa_v_inf_final_temp_dev.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_inf_final_temp_dev.precio
				END)) AS valor_uts_dev
				
			FROM sa_det_orden_tempario det_orden_temp
				INNER JOIN sa_v_informe_final_tempario_dev sa_v_inf_final_temp_dev ON (det_orden_temp.id_det_orden_tempario = sa_v_inf_final_temp_dev.id_det_orden_tempario) %s;", $sqlBusq2);
			$rs3 = mysql_query($sql3);
			if (!$rs3) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row3 = mysql_fetch_assoc($rs3);

			// VALES DE SALIDA
			$sql4 = sprintf("SELECT
				SUM((CASE sa_v_vale_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						(sa_v_vale_inf_final_temp.ut) / sa_v_vale_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						0
				END)) AS uts_vale,
				
				SUM((CASE sa_v_vale_inf_final_temp.id_modo
					WHEN 1 THEN -- UT
						(sa_v_vale_inf_final_temp.ut * sa_v_vale_inf_final_temp.precio_tempario_tipo_orden) / sa_v_vale_inf_final_temp.base_ut_precio
					WHEN 2 THEN -- PRECIO
						sa_v_vale_inf_final_temp.precio
				END)) AS valor_uts_vale
				
			FROM sa_det_vale_salida_tempario det_vale_temp
				INNER JOIN sa_v_vale_informe_final_tempario sa_v_vale_inf_final_temp ON (det_vale_temp.id_det_vale_salida_tempario = sa_v_vale_inf_final_temp.id_det_vale_salida_tempario) %s;", $sqlBusq3);
			$rs4 = mysql_query($sql4);
			if (!$rs4) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row4 = mysql_fetch_assoc($rs4);
			
			if ($row1 || $row2 || $row3 || $row4) {
				$totalMecanicoUts = $row2['uts'] - $row3['uts_dev'] + $row4['uts_vale'];
				$totalMecanicoBs = $row2['valor_uts'] - $row3['valor_uts_dev'] + $row4['valor_uts_vale'];
				
				$arrayDetTecnico[0] = $row1['nombre_completo'];
				$arrayDetTecnico[1] = $totalMecanicoUts;
				$arrayDetTecnico[2] = $totalMecanicoBs;
				$arrayTecnico[] = $arrayDetTecnico;
			}
			
			switch($row1['nivel']) {
				case 'AYUDANTE' : $arrayMecanico[0] += 1; break;
				case 'PRINCIPIANTE' : $arrayMecanico[1] += 1; break;
				case 'NORMAL' : $arrayMecanico[1] += 1; break;
				case 'EXPERTO' : $arrayMecanico[1] += 1; break;
			}
		}
		
		$totalUtsEquipo = 0;
		$totalBsEquipo = 0;
		if (isset($arrayTecnico)) {
			foreach ($arrayTecnico as $indice => $valor) {
				$totalUtsEquipo += $arrayTecnico[$indice][1];
				$totalBsEquipo += $arrayTecnico[$indice][2];
			}
		}
		
		$totalTotalUtsEquipos += $totalUtsEquipo;
		$totalTotalBsEquipos += $totalBsEquipo;
		
		$arrayDetEquipo[0] = $row0['nombre_equipo'];
		$arrayDetEquipo[1] = $totalUtsEquipo;
		$arrayDetEquipo[2] = $totalBsEquipo;
		$arrayDetEquipo[3] = $arrayTecnico;
		$arrayEquipo[] = $arrayDetEquipo;
	}
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	if (isset($arrayEquipo)) {
		foreach ($arrayEquipo as $indice => $valor) {
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"5\"><b>".ucfirst(strtolower(utf8_encode($arrayEquipo[$indice][0])))."</b></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"52%\">T&eacute;cnicos ".ucfirst(strtolower(utf8_encode($arrayEquipo[$indice][0])))."</td>";
				$htmlTb .= "<td width=\"18%\">UT'S</td>";
				$htmlTb .= "<td width=\"18%\">".cAbrevMoneda."</td>";
				$htmlTb .= "<td width=\"12%\">%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTecnico = $arrayEquipo[$indice][3];
			$porcTotalEquipo = 0;
			$arrayMec = NULL;
			if (isset($arrayTecnico)) {
				foreach ($arrayTecnico as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcMecanico = ($totalTotalBsEquipos > 0) ? ($arrayTecnico[$indice2][2] * 100) / $totalTotalBsEquipos : 0;
		
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\">".utf8_encode($arrayTecnico[$indice2][0])."</td>";
						$htmlTb .= "<td>".number_format($arrayTecnico[$indice2][1], 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($arrayTecnico[$indice2][2], 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($porcMecanico, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$porcTotalEquipo += round($porcMecanico,2);
					
					$arrayDet2[0] = utf8_encode($arrayTecnico[$indice2][0]);
					$arrayDet2[1] = round($porcMecanico,2);
					$arrayMec[] = implode("+*+",$arrayDet2);
				}
			}
		
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\">Total Facturación Técnicos:</td>";
				$htmlTb .= "<td>".number_format($arrayEquipo[$indice][1], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayEquipo[$indice][2], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($porcTotalEquipo, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$categoria .= "'".utf8_encode($arrayEquipo[$indice][0])."',";
			$arrayDet[0] = utf8_encode($arrayEquipo[$indice][0]);
			$arrayDet[1] = round($arrayEquipo[$indice][2] * 100 / $totalTotalBsEquipos,2);
			$arrayDet[2] = implode("-*-",$arrayMec);
			$arrayEquipos[] = implode("=*=",$arrayDet);
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">FACTURACIÓN TÉCNICOS</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						6,
						"Donut chart",
						"FACTURACIÓN TÉCNICOS",
						str_replace("'","|*|",array("")),
						"Facturación Equipo",
						str_replace("'","|*|",$data1),
						"Facturación Mecánico",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaFacturacionTecnicosServicios","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return array($arrayMecanico, $totalTotalUtsEquipos);
}

function facturacionVendedoresRepuestos($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// FACTURACIÓN VENDEDORES DE REPUESTOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
	AND fact_vent.aplicaLibros = 1");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
	AND nota_cred.tipoDocumento LIKE 'FA'
	AND nota_cred.aplicaLibros = 1
	AND nota_cred.estatus_nota_credito = 2");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
		AND YEAR(fact_vent.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
		AND YEAR(nota_cred.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	$queryTipoMov = sprintf("SELECT
		empleado.id_empleado,
		CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado
	FROM pg_empleado empleado
	WHERE empleado.id_empleado IN (
		SELECT DISTINCT
			fact_vent.idVendedor
		FROM cj_cc_encabezadofactura fact_vent %s
		
		UNION ALL
		
		SELECT DISTINCT
			fact_vent2.idVendedor
		FROM cj_cc_notacredito nota_cred
			INNER JOIN cj_cc_encabezadofactura fact_vent2 ON (nota_cred.idDocumento = fact_vent2.idFactura) %s)
	ORDER BY nombre_empleado",
		$sqlBusq,
		$sqlBusq2);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while($rowMovDet = mysql_fetch_array($rsTipoMov)) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.idVendedor = %s
		AND fact_vent.idDepartamentoOrigenFactura IN (0)
		AND fact_vent.aplicaLibros = 1",
			valTpDato($rowMovDet['id_empleado'], "int"));
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("fact_vent.idVendedor = %s
		AND nota_cred.idDepartamentoNotaCredito IN (0)
		AND nota_cred.tipoDocumento LIKE 'FA'
		AND nota_cred.aplicaLibros = 1
		AND nota_cred.estatus_nota_credito = 2",
			valTpDato($rowMovDet['id_empleado'], "int"));
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		}
		
		if ($valFecha[0] != "-1" && $valFecha[0] != ""
		&& $valFecha[1] != "-1" && $valFecha[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
			AND YEAR(fact_vent.fechaRegistroFactura) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
			AND YEAR(nota_cred.fechaNotaCredito) = %s",
				valTpDato($valFecha[0], "date"),
				valTpDato($valFecha[1], "date"));
		}
		
		// FACTURA DE VENTA
		$query = sprintf("SELECT 
			condicionDePago AS condicion_pago,
			(fact_vent.subtotalFactura - IFNULL(fact_vent.descuentoFactura, 0)) AS neto
		FROM cj_cc_encabezadofactura fact_vent %s;", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalContado = 0;
		$totalCredito = 0;
		while($row = mysql_fetch_array($rs)) {
			switch ($row['condicion_pago']) {
				case 0 : $totalCredito += round($row['neto'],2); break;
				case 1 : $totalContado += round($row['neto'],2); break;
			}
		}

		// NOTA DE CREDITO
		$query = sprintf("SELECT
			fact_vent.condicionDePago AS condicion_pago,
			(nota_cred.subtotalNotaCredito - IFNULL(nota_cred.subtotal_descuento, 0)) AS neto
		FROM cj_cc_notacredito nota_cred
			INNER JOIN cj_cc_encabezadofactura fact_vent ON (nota_cred.idDocumento = fact_vent.idFactura) %s;", $sqlBusq2);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_array($rs)) {
			switch ($row['condicion_pago']) {
				case 0 : $totalCredito -= round($row['neto'],2); break;
				case 1 : $totalContado -= round($row['neto'],2); break;
			}
		}

		$arrayDet[0] = $rowMovDet['nombre_empleado'];
		$arrayDet[1] = $totalContado;
		$arrayDet[2] = $totalCredito;
		$arrayDet[3] = $totalContado + $totalCredito;
		$arrayVentaVendedor[] = $arrayDet;

		$totalVentaVendedores += $arrayDet[3];
	}

	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh.= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"34%\">"."Vendedor"."</td>";
		$htmlTh .= "<td width=\"18%\">"."Contado"."</td>";
		$htmlTh .= "<td width=\"18%\">"."Crédito"."</td>";
		$htmlTh .= "<td width=\"18%\">"."Total"."</td>";
		$htmlTh .= "<td width=\"12%\">"."%"."</td>";
	$htmlTh .= "</tr>";

	$contFila = 0;
	if (isset($arrayVentaVendedor)) {
		foreach ($arrayVentaVendedor as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcVendedor = ($arrayVentaVendedor[$indice][3] * 100) / $totalVentaVendedores;

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayVentaVendedor[$indice][0])."</td>";
				$htmlTb .= "<td>".number_format($arrayVentaVendedor[$indice][1],2,".",",")."</td>";
				$htmlTb .= "<td>".number_format($arrayVentaVendedor[$indice][2],2,".",",")."</td>";
				$htmlTb .= "<td>".number_format($arrayVentaVendedor[$indice][3],2,".",",")."</td>";
				$htmlTb .= "<td>".number_format($porcVendedor,2,".",",")."%</td>";
			$htmlTb .= "</tr>";

			$data1 .= (strlen($data1) > 0) ? "['".utf8_encode($arrayVentaVendedor[$indice][0])."', ".$arrayVentaVendedor[$indice][3]."]," : "{ name: '".utf8_encode($arrayVentaVendedor[$indice][0])."', y: ".$arrayVentaVendedor[$indice][3].", sliced: true, selected: true },";
			
			$totalVentaContado += $arrayVentaVendedor[$indice][1];
			$totalVentaCredito += $arrayVentaVendedor[$indice][2];
			$porcTotalVendedor += $porcVendedor;
		}
	}
	$data1 = substr($data1, 0, (strlen($data1)-1));
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Total Facturación Vendedores:")."</td>";
		$htmlTb .= "<td>".number_format($totalVentaContado,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format($totalVentaCredito,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format($totalVentaVendedores,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format($porcTotalVendedor,2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">FACTURACIÓN VENDEDORES DE REPUESTOS</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						7,
						"Pie with legend",
						"FACTURACIÓN VENDEDORES DE REPUESTOS",
						str_replace("'","|*|",array("")),
						"Monto",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaFacturacionVendedorRepuestos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
}

function facturacionVendedores($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$valFecha[0] = date("m", strtotime($frmBuscar['txtFecha']));
	$valFecha[1] = date("Y", strtotime($frmBuscar['txtFecha']));
	
	// VERIFICA VALORES DE CONFIGURACION (Incluir Notas en el Informe Gerencial)
	$queryConfig300 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 300
		AND config_emp.status = 1
		AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig300 = mysql_query($queryConfig300);
	if (!$rsConfig300) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig300 = mysql_num_rows($rsConfig300);
	$rowConfig300 = mysql_fetch_assoc($rsConfig300);
	
	analisisInventario($objResponse, $idEmpresa, $valFecha);
	cantidadItemsVendidos($objResponse, $idEmpresa, $valFecha);
	comprasRepuestos($objResponse, $idEmpresa, $valFecha);
	ventasRepuestosMostrador($objResponse, $idEmpresa, $valFecha);
	ventasRepuestosServicios($objResponse, $idEmpresa, $valFecha);
	facturacionAsesoresServicios($objResponse, $idEmpresa, $valFecha);
	$Result1 = facturacionTecnicosServicios($objResponse, $idEmpresa, $valFecha);
	$arrayMecanico = $Result1[0];
	$totalTotalUtsEquipos = $Result1[1];
	facturacionVendedoresRepuestos($objResponse, $idEmpresa, $valFecha);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// PRODUCCION TALLER
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$array = NULL;
	$arrayMov = NULL;
	$data1 = "";
	
	$dataMo = "";
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipo_orden.orden_generica = 0");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_orden.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	// TIPOS DE ORDENES
	$sqlTiposOrden = sprintf("SELECT * FROM sa_tipo_orden tipo_orden %s ORDER BY id_tipo_orden", $sqlBusq);
	$rsTiposOrden = mysql_query($sqlTiposOrden);
	if (!$rsTiposOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTipoOrden = mysql_fetch_assoc($rsTiposOrden)) {
		$tipoOrden[$rowTipoOrden['id_tipo_orden']] = array('nombre' => $rowTipoOrden['nombre_tipo_orden']);
	}
	
	// TIPOS DE MANO DE OBRA
	$sqlOperadores = "SELECT * FROM sa_operadores
	ORDER BY id_operador";
	$rsOperadores = mysql_query($sqlOperadores);
	if (!$rsOperadores) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowOperadores = mysql_fetch_assoc($rsOperadores)) {
		$operadores[$rowOperadores['id_operador']] = $rowOperadores['descripcion_operador'];
	}
	$itot = count($operadores) + 1;
	$operadores[$itot] = "Trabajos Otros Talleres";
	
	// TIPOS DE ARTICULOS
	$sqlTiposArticulos = "SELECT * FROM iv_tipos_articulos
	ORDER BY id_tipo_articulo";
	$rsTiposArticulos = mysql_query($sqlTiposArticulos);
	if (!$rsTiposArticulos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTipoArticulo = mysql_fetch_assoc($rsTiposArticulos)) {
		$tipoArticulos[$rowTipoArticulo['id_tipo_articulo']] = $rowTipoArticulo['descripcion'];
	}
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.aprobado = 1
	AND sa_v_inf_final_temp.estado_tempario IN ('FACTURADO', 'TERMINADO')");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sa_v_inf_final_temp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(sa_v_inf_final_temp.fecha_filtro) = %s
		AND YEAR(sa_v_inf_final_temp.fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// BUSCA LAS MANO DE OBRA DE FACTURAS
	$sqlTempario = sprintf("SELECT
		sa_v_inf_final_temp.id_tipo_orden,
		sa_v_inf_final_temp.operador,
		
		(CASE sa_v_inf_final_temp.id_modo
			WHEN 1 THEN -- UT
				(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
			WHEN 2 THEN -- PRECIO
				sa_v_inf_final_temp.precio
		END) AS total_tempario_orden
		
	FROM sa_v_informe_final_tempario sa_v_inf_final_temp %s;", $sqlBusq);
	$rsTempario = mysql_query($sqlTempario);
	if (!$rsTempario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayTotalMOTipoOrden[0] = 0;
	while ($rowTempario = mysql_fetch_assoc($rsTempario)) {
		$valor = $rowTempario['total_tempario_orden'];
		
		$dataMo[$rowTempario['operador']][$rowTempario['id_tipo_orden']] += $valor;

		$arrayTotalMOTipoOrden[$rowTempario['id_tipo_orden']] += $valor;
		$arrayTotalMOOperador[$rowTempario['operador']] += $valor;
	}
	
	// BUSCA LAS MANO DE OBRA DE FACTURAS DEVUELTAS
	$sqlTemparioDev = sprintf("SELECT
		sa_v_inf_final_temp.id_tipo_orden,
		sa_v_inf_final_temp.operador,
		
		(CASE sa_v_inf_final_temp.id_modo
			WHEN 1 THEN -- UT
				(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
			WHEN 2 THEN -- PRECIO
				sa_v_inf_final_temp.precio
		END) AS total_tempario_dev_orden
		
	FROM sa_v_informe_final_tempario_dev sa_v_inf_final_temp %s;", $sqlBusq);
	$rsTemparioDev = mysql_query($sqlTemparioDev);
	if (!$rsTemparioDev) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTemparioDev = mysql_fetch_assoc($rsTemparioDev)) {
		$valor = $rowTemparioDev['total_tempario_dev_orden'];
		
		$dataMo[$rowTemparioDev['operador']][$rowTemparioDev['id_tipo_orden']] -= $valor;

		$arrayTotalMOTipoOrden[$rowTemparioDev['id_tipo_orden']] -= $valor;
		$arrayTotalMOOperador[$rowTemparioDev['operador']] -= $valor;
	}
	
	// BUSCA LAS MANO DE OBRA POR VALES DE SALIDA
	$sqlTemparioVale = sprintf("SELECT
		sa_v_inf_final_temp.id_tipo_orden,
		sa_v_inf_final_temp.operador,
		
		(CASE sa_v_inf_final_temp.id_modo
			WHEN 1 THEN -- UT
				(sa_v_inf_final_temp.precio_tempario_tipo_orden * sa_v_inf_final_temp.ut) / sa_v_inf_final_temp.base_ut_precio
			WHEN 2 THEN -- PRECIO
				sa_v_inf_final_temp.precio
		END) AS total_tempario_vale
		
	FROM sa_v_vale_informe_final_tempario sa_v_inf_final_temp %s;", $sqlBusq);
	$rsTemparioVale = mysql_query($sqlTemparioVale);
	if (!$rsTemparioVale) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTemparioVale = mysql_fetch_assoc($rsTemparioVale)) {
		$valor = $rowTemparioVale['total_tempario_vale'];
		
		$dataMo[$rowTemparioVale['operador']][$rowTemparioVale['id_tipo_orden']] += $valor;

		$arrayTotalMOTipoOrden[$rowTemparioVale['id_tipo_orden']] += $valor;
		$arrayTotalMOOperador[$rowTemparioVale['operador']] += $valor;
	}
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("aprobado = 1");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fecha_filtro) = %s
		AND YEAR(fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// TRABAJOS OTROS TALLERES DE FACTURAS
	$sqlTot = sprintf("SELECT * FROM sa_v_informe_final_tot %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsTot = mysql_query($sqlTot);
	if (!$rsTot) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTot = mysql_fetch_assoc($rsTot)) {
		$valor = $rowTot['monto_total'] + (($rowTot['porcentaje_tot'] * $rowTot['monto_total']) / 100);

		$dataMo[$itot][$rowTot['id_tipo_orden']] += $valor;

		$arrayTotalMOTipoOrden[$rowTot['id_tipo_orden']] += $valor;
		$arrayTotalMOOperador[$itot] += $valor;
	}
	
	// TRABAJOS OTROS TALLERES DE FACTURAS DEVUELTAS
	$sqlTotDev = sprintf("SELECT * FROM sa_v_informe_final_tot_dev %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsTotDev = mysql_query($sqlTotDev);
	if (!$rsTotDev) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotDev = mysql_fetch_assoc($rsTotDev)) {
		$valor = $rowTotDev['monto_total'] + (($rowTotDev['porcentaje_tot'] * $rowTotDev['monto_total']) / 100);

		$dataMo[$itot][$rowTotDev['id_tipo_orden']] -= $valor;

		$arrayTotalMOTipoOrden[$rowTotDev['id_tipo_orden']] -= $valor;
		$arrayTotalMOOperador[$itot] -= $valor;
	}
	
	// TRABAJOS OTROS TALLERES DE VALES DE SALIDA
	$sqlTotVale = sprintf("SELECT * FROM sa_v_vale_informe_final_tot %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsTotVale = mysql_query($sqlTotVale);
	if (!$rsTotVale) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotVale = mysql_fetch_assoc($rsTotVale)) {
		$valor = $rowTotVale['monto_total']+(($rowTotVale['porcentaje_tot'] * $rowTotVale['monto_total']) / 100);

		$dataMo[$itot][$rowTotVale['id_tipo_orden']] += $valor;
		
		$arrayTotalMOTipoOrden[$rowTotVale['id_tipo_orden']] += $valor;
		$arrayTotalMOOperador[$itot] += $valor;
	}
	
	// REPUESTOS DE FACTURAS
	$sqlRepuestos = sprintf("SELECT * FROM sa_v_informe_final_repuesto %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsRepuestos = mysql_query($sqlRepuestos);
	if (!$rsRepuestos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayTotalRepuestoTipoOrden[0] = 0;
	$arrayTotalRepuestoDescuentoTipoOrden[0] = 0;
	while ($rowRepuestos = mysql_fetch_assoc($rsRepuestos)) {
		$valor = $rowRepuestos['precio_unitario'] * $rowRepuestos['cantidad'];

		$desc = round((($valor * $rowRepuestos['porcentaje_descuento_orden']) / 100),2);

		$dataRepuesto[$rowRepuestos['id_tipo_articulo']][$rowRepuestos['id_tipo_orden']] += $valor;

		$arrayTotalRepuestoTipoOrden[$rowRepuestos['id_tipo_orden']] += $valor;
		$totalRepuestoTipoRepuesto[$rowRepuestos['id_tipo_articulo']] += $valor;
		$arrayTotalRepuestoDescuentoTipoOrden[$rowRepuestos['id_tipo_orden']] += $desc;
		$totalDescuentoRepServ += $desc;
	}
	
	// REPUESTOS DE FACTURAS DEVUELTAS
	$sqlRepuestosDev = sprintf("SELECT * FROM sa_v_informe_final_repuesto_dev %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsRepuestosDev = mysql_query($sqlRepuestosDev);
	if (!$rsRepuestosDev) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowRepuestosDev = mysql_fetch_assoc($rsRepuestosDev)) {
		$valor = $rowRepuestosDev['precio_unitario'] * $rowRepuestosDev['cantidad'];

		$desc = round((($valor * $rowRepuestosDev['porcentaje_descuento_orden']) / 100),2);

		$dataRepuesto[$rowRepuestosDev['id_tipo_articulo']][$rowRepuestosDev['id_tipo_orden']] -= $valor;

		$arrayTotalRepuestoTipoOrden[$rowRepuestosDev['id_tipo_orden']] -= $valor;
		$totalRepuestoTipoRepuesto[$rowRepuestosDev['id_tipo_articulo']] -= $valor;
		$arrayTotalRepuestoDescuentoTipoOrden[$rowRepuestosDev['id_tipo_orden']] -= $desc;
		$totalDescuentoRepServ -= $desc;
	}
	
	// REPUESTOS DE VALES DE SALIDA
	$sqlRepuestosVale = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsRepuestosVale = mysql_query($sqlRepuestosVale);
	if (!$rsRepuestosVale) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowRepuestosVale = mysql_fetch_assoc($rsRepuestosVale)) {
		$valor = $rowRepuestosVale['precio_unitario'] * $rowRepuestosVale['cantidad'];

		$desc = round((($valor * $rowRepuestosVale['porcentaje_descuento_orden']) / 100),2);

		$dataRepuesto[$rowRepuestosVale['id_tipo_articulo']][$rowRepuestosVale['id_tipo_orden']] += $valor;

		$arrayTotalRepuestoTipoOrden[$rowRepuestosVale['id_tipo_orden']] += $valor;
		$totalRepuestoTipoRepuesto[$rowRepuestosVale['id_tipo_articulo']] += $valor;
		$arrayTotalRepuestoDescuentoTipoOrden[$rowRepuestosVale['id_tipo_orden']] += $desc;
		$totalDescuentoRepServ += $desc;
	}
	
	// NOTAS DE FACTURAS
	$sqlNotas = sprintf("SELECT * FROM sa_v_informe_final_notas %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsNotas = mysql_query($sqlNotas);
	if (!$rsNotas) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayTotalNotaTipoOrden[0] = 0;
	while ($rowNotas = mysql_fetch_assoc($rsNotas)) {
		$valor = $rowNotas['precio'];
		
		$arrayTotalNotaTipoOrden[$rowNotas['id_tipo_orden']] += $valor;
		$totalNota += $valor;
	}
	
	// NOTAS DE FACTURAS DEVUELTAS
	$sqlNotasDev = sprintf("SELECT * FROM sa_v_informe_final_notas_dev %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsNotasDev = mysql_query($sqlNotasDev);
	if (!$rsNotasDev) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowNotasDev = mysql_fetch_assoc($rsNotasDev)) {
		$valor = $rowNotasDev['precio'];

		$arrayTotalNotaTipoOrden[$rowNotasDev['id_tipo_orden']] -= $valor;
		$totalNota -= $valor;
	}
	
	// NOTAS DE VALES DE SALIDA
	$sqlNotasVale = sprintf("SELECT * FROM sa_v_vale_informe_final_notas %s
	ORDER BY id_tipo_orden;", $sqlBusq);
	$rsNotasVale = mysql_query($sqlNotasVale);
	if (!$rsNotasVale) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowNotasVale = mysql_fetch_assoc($rsNotasVale)) {
		$valor = $rowNotasVale['precio'];

		$arrayTotalNotaTipoOrden[$rowNotasVale['id_tipo_orden']] += $valor;
		$totalNota += $valor;
	}

	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"20%\">Conceptos</td>";
	foreach ($tipoOrden as $tipo) {
		$htmlTh .= "<td width=\"10%\">".$tipo['nombre']."</td>";
	}
		$htmlTh .= "<td>Total</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// CALCULO DEL TOTAL
	$totalProdTaller = array_sum($arrayTotalMOTipoOrden) + array_sum($arrayTotalRepuestoTipoOrden) - array_sum($arrayTotalRepuestoDescuentoTipoOrden);
	$totalProdTaller += ($rowConfig300['valor'] == 1) ? array_sum($arrayTotalNotaTipoOrden) : 0;
	
	// MANO DE OBRA
	if (isset($operadores)) {
		foreach ($operadores as $idOperador => $operador) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcOperador = ($totalProdTaller > 0) ? ($arrayTotalMOOperador[$idOperador] * 100) / $totalProdTaller : 0;
			$porcMO += $porcOperador;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($operador)."</td>";
			if (isset($tipoOrden)) {
				foreach ($tipoOrden as $idTipo => $tipo) {
					$htmlTb .= "<td>";
						$htmlTb .= (isset($dataMo[$idOperador][$idTipo])) ? number_format($dataMo[$idOperador][$idTipo], 2, ".", ",") : number_format(0, 2, ".", ",");
					$htmlTb .= "</td>";
				}
			}
				$htmlTb .= "<td>".number_format($arrayTotalMOOperador[$idOperador], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($porcOperador, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
		}
	}

	// TOTAL DE LA MANO DE OBRA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Subtotal Mano de Obra:</td>";
	if (isset($tipoOrden)) {
		foreach ($tipoOrden as $idTipo => $tipo) {
			$subTotalMO += $arrayTotalMOTipoOrden[$idTipo];
			
			$htmlTb .= "<td>".number_format($arrayTotalMOTipoOrden[$idTipo], 2, ".", ",")."</td>";
		}
	}
		$htmlTb .= "<td>".number_format($subTotalMO, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($porcMO, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// REPUESTOS
	if (isset($tipoArticulos)) {
		foreach ($tipoArticulos as $idTipoArticulo => $tipoArticulo) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcTipoRepuestos = ($totalProdTaller > 0) ? ($totalRepuestoTipoRepuesto[$idTipoArticulo] * 100) / $totalProdTaller : 0;
			$porcRepuestos += $porcTipoRepuestos;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($tipoArticulo)."</td>";
			if (isset($tipoOrden)) {
				foreach ($tipoOrden as $idTipo => $tipo) {
					$htmlTb .= "<td>".number_format($dataRepuesto[$idTipoArticulo][$idTipo], 2, ".", ",")."</td>";
				}
			}
				$htmlTb .= "<td>".number_format($totalRepuestoTipoRepuesto[$idTipoArticulo], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($porcTipoRepuestos, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
		}
	}

	// TOTAL DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Subtotal Repuestos:</td>";
	if (isset($tipoOrden)) {
		foreach ($tipoOrden as $idTipo => $tipo) {
			$subTotalRepServ += $arrayTotalRepuestoTipoOrden[$idTipo];
			
			$htmlTb .= "<td>".number_format($arrayTotalRepuestoTipoOrden[$idTipo], 2, ".", ",")."</td>";
		}
	}
		$htmlTb .= "<td>".number_format($subTotalRepServ, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($porcRepuestos, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// TOTAL DE DESCUENTO DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Subtotal Descuento Repuestos:</td>";
	if (isset($tipoOrden)) {
		foreach ($tipoOrden as $idTipo => $tipo) {
			$htmlTb .= "<td>".number_format((-1)*$arrayTotalRepuestoDescuentoTipoOrden[$idTipo], 2, ".", ",")."</td>";
		}
	}
	$porcDescuentoRepServ = ($totalProdTaller > 0) ? ($totalDescuentoRepServ * 100) / $totalProdTaller : 0;
		$htmlTb .= "<td>".number_format((-1)*$totalDescuentoRepServ, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format((-1)*$porcDescuentoRepServ, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// TOTAL DE NOTAS
	if ($rowConfig300['valor'] == 1) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">Subtotal Notas:</td>";
		if (isset($tipoOrden)) {
			foreach ($tipoOrden as $idTipo => $tipo) {
				$htmlTb .= "<td>".number_format($arrayTotalNotaTipoOrden[$idTipo], 2, ".", ",")."</td>";
			}
		}
		$porcNota = ($totalProdTaller > 0) ? ($totalNota * 100) / $totalProdTaller : 0;
			$htmlTb .= "<td>".number_format($totalNota, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($porcNota, 2, ".", ",")."%</td>";
		$htmlTb .= "</tr>";
	}

	// TOTAL SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total Producción Taller:</td>";
	if (isset($tipoOrden)) {
		foreach ($tipoOrden as $idTipo => $tipo) {
			$totalTipoOrden = $arrayTotalMOTipoOrden[$idTipo] + $arrayTotalRepuestoTipoOrden[$idTipo] - $arrayTotalRepuestoDescuentoTipoOrden[$idTipo];
			$totalTipoOrden += ($rowConfig300['valor'] == 1) ? $arrayTotalNotaTipoOrden[$idTipo] : 0;
			$porcTotalTipoOrden[$idTipo] = ($totalProdTaller > 0) ? ($totalTipoOrden * 100) / $totalProdTaller : 0;
	
			$htmlTb .= "<td>".number_format($totalTipoOrden, 2, ".", ",")."</td>";
			
			$data1 .= (strlen($data1) > 0) ? "['".utf8_encode($tipo['nombre'])."', ".$totalTipoOrden."]," : "{ name: '".utf8_encode($tipo['nombre'])."', y: ".$totalTipoOrden.", sliced: true, selected: true },";
		}
	}
	$porcTotalProdTaller = $porcMO + $porcRepuestos - $porcDescuentoRepServ;
	$porcTotalProdTaller += ($rowConfig300['valor'] == 1) ? $porcNota : 0;
		$htmlTb .= "<td>".number_format($totalProdTaller, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($porcTotalProdTaller, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	$data1 = substr($data1, 0, (strlen($data1)-1));

	// PARTICIPACION
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">% Participación</td>";
	$porcentajeTotal = 0;
	if (isset($tipoOrden)) {
		foreach ($tipoOrden as $idTipo => $tipo) {
			$porcentajeTotal += $porcTotalTipoOrden[$idTipo];
			$htmlTb .= "<td>".number_format($porcTotalTipoOrden[$idTipo], 2, ".", ",")."%</td>";
		}
	}
		$htmlTb .= "<td>".number_format($porcentajeTotal, 2, ".", ",")."%</td>";
		$htmlTb .= "<td></td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"".(count($tipoOrden) + 3)."\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">PRODUCCIÓN TALLER</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						8,
						"Pie with legend",
						"PRODUCCIÓN TALLER",
						str_replace("'","|*|",array("")),
						"Monto",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaProduccionTaller","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// PRODUCCION REPUESTOS MOSTRADOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$array = NULL;
	$data1 = "";
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
	AND fact_vent.aplicaLibros = 1");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
	AND nota_cred.tipoDocumento LIKE 'FA'
	AND nota_cred.aplicaLibros = 1
	AND nota_cred.estatus_nota_credito = 2");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
		AND YEAR(fact_vent.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
		AND YEAR(nota_cred.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// PRODUCCIÓN REPUESTOS MOSTRADOR
	$query = sprintf("SELECT
		fact_vent.condicionDePago,
		(fact_vent.subtotalFactura - IFNULL(fact_vent.descuentoFactura, 0)) AS neto
	FROM cj_cc_encabezadofactura fact_vent %s
		
	UNION ALL
	
	SELECT
		fact_vent2.condicionDePago,
		((-1)*(nota_cred.subtotalNotaCredito - IFNULL(nota_cred.subtotal_descuento, 0))) AS neto
	FROM cj_cc_notacredito nota_cred
		INNER JOIN cj_cc_encabezadofactura fact_vent2 ON (nota_cred.idDocumento = fact_vent2.idFactura) %s;",
		$sqlBusq,
		$sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayVentaMost = array();
	while ($row = mysql_fetch_assoc($rs)) {
		switch($row['condicionDePago']) {
			case 0 : $arrayVentaMost[1] += round($row['neto'],2); break;
			case 1 : $arrayVentaMost[0] += round($row['neto'],2); break;
		}
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<thead>";
		$htmlTh .= "<td colspan=\"14\">"."<p class=\"textoAzul\">PRODUCCIÓN REPUESTOS MOSTRADOR</p>"."</td>";
	$htmlTh .= "</thead>";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">Conceptos</td>
					<td width=\"20%\">Contado</td>
					<td width=\"20%\">Crédito</td>
					<td width=\"20%\">Total</td>
					<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// VENTAS ITINERANTES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Ventas Itinerantes"."</td>";
		$htmlTb .= "<td>".number_format(0,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(0,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(0,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(0,2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	// MOSTRADOR PUBLICO
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Mostrador Público"."</td>";
		$htmlTb .= "<td>".number_format($arrayVentaMost[0],2,".",",")."</td>";
		$htmlTb .= "<td>".number_format($arrayVentaMost[1],2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(array_sum($arrayVentaMost),2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(((array_sum($arrayVentaMost) > 0) ? array_sum($arrayVentaMost) * 100 / (array_sum($arrayVentaMost)) : 0),2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	// TOTAL REPUESTOS MOSTRADOR
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."Total Producción Repuestos Mostrador:"."</td>";
		$htmlTb .= "<td>".number_format($arrayVentaMost[0],2,".",",")."</td>";
		$htmlTb .= "<td>".number_format($arrayVentaMost[1],2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(array_sum($arrayVentaMost),2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
	$htmlTb .= "</tr>";

	// PARTICIPACION
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">% Participación</td>";
		$htmlTb .= "<td>".number_format(((array_sum($arrayVentaMost) > 0) ? $arrayVentaMost[0] * 100 / array_sum($arrayVentaMost) : 0), 2, ".", ",")."%</td>";
		$htmlTb .= "<td>".number_format(((array_sum($arrayVentaMost) > 0) ? $arrayVentaMost[1] * 100 / array_sum($arrayVentaMost) : 0), 2, ".", ",")."%</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
		$htmlTb .= "<td></td>";
	$htmlTb .= "</tr>";

	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaProduccionRepuestos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$array = NULL;
	$data1 = "";
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_orden IN (1,2,3,4,7,8)
	AND aprobado = 1");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fecha_filtro) = %s
		AND YEAR(fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// COSTO DE VENTAS REPUESTOS POR SERVICIOS Y LATONERIA Y PINTURA
	$query2 = sprintf("SELECT
		SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
	FROM (
		SELECT (costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_informe_final_repuesto %s
		
		UNION ALL
		
		SELECT (-1)*(costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_informe_final_repuesto_dev %s
		
		UNION ALL
		
		SELECT (costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_vale_informe_final_repuesto %s) AS query",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rs2 = mysql_query($query2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row2 = mysql_fetch_assoc($rs2);
	$arrayCostoRepServ[0] = round($row2['total_costo_repuesto_orden'],2);
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
	AND fact_vent.aplicaLibros = 1");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
	AND nota_cred.tipoDocumento LIKE 'FA'
	AND nota_cred.aplicaLibros = 1
	AND nota_cred.estatus_nota_credito = 2");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
		AND YEAR(fact_vent.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
		AND YEAR(nota_cred.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// COSTO DE VENTAS REPUESTOS
	$query = sprintf("SELECT
		(SELECT SUM((fact_vent_det.cantidad * fact_vent_det.costo_compra)) AS costo_total
		FROM cj_cc_factura_detalle fact_vent_det
		WHERE fact_vent_det.id_factura = fact_vent.idFactura) AS neto
	FROM cj_cc_encabezadofactura fact_vent %s
		
	UNION ALL
	
	SELECT
		((-1)*(SELECT SUM((nota_cred_det.cantidad * nota_cred_det.costo_compra)) AS costo_total
		FROM cj_cc_nota_credito_detalle nota_cred_det
		WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito)) AS neto
	FROM cj_cc_notacredito nota_cred %s;",
		$sqlBusq,
		$sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalCostoRepMost = 0;
	while ($rowDetalle = mysql_fetch_assoc($rs)) {
		$totalCostoRepMost += round($rowDetalle['neto'],2);
	}
	$arrayCostoRepMost[0] = $totalCostoRepMost;
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<thead>";
		$htmlTh .= "<td colspan=\"14\">"."<p class=\"textoAzul\">MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR</p>"."</td>";
	$htmlTh .= "</thead>";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"40%\">Conceptos</td>";
		$htmlTh .= "<td width=\"20%\">Costo</td>";
		$htmlTh .= "<td width=\"20%\">Utl. Bruta</td>";
		$htmlTh .= "<td width=\"20%\">%Utl. Bruta</td>";
	$htmlTh .= "</tr>";
	
	// REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Repuestos por Servicios"."</td>";
		$htmlTb .= "<td>".number_format($arrayCostoRepServ[0],2,".",",")."</td>";
		$htmlTb .= "<td>".number_format((($subTotalRepServ - $totalDescuentoRepServ) - $arrayCostoRepServ[0]),2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(((($subTotalRepServ - $totalDescuentoRepServ) > 0) ? ((($subTotalRepServ - $totalDescuentoRepServ) - $arrayCostoRepServ[0]) * 100) / ($subTotalRepServ - $totalDescuentoRepServ) : 0),2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	// REPUESTOS POR MOSTRADOR
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Repuestos por Mostrador"."</td>";
		$htmlTb .= "<td>".number_format($arrayCostoRepMost[0],2,".",",")."</td>";
		$htmlTb .= "<td>".number_format((array_sum($arrayVentaMost) - $arrayCostoRepMost[0]),2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(((array_sum($arrayVentaMost) > 0) ? ((array_sum($arrayVentaMost) - $arrayCostoRepMost[0]) * 100) / array_sum($arrayVentaMost) : 0),2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaMargenRepuestosServiciosMostrador","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// TOTAL FACTURACION
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$array = NULL;
	$data1 = "";
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"60%\">Conceptos</td>
					<td width=\"30%\">Facturado</td>
					<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// TOTAL SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Total Servicios"."</td>";
		$htmlTb .= "<td>".number_format($totalProdTaller,2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(((($totalProdTaller + array_sum($arrayVentaMost)) > 0) ? $totalProdTaller * 100 / ($totalProdTaller + array_sum($arrayVentaMost)) : 0),2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	$data1 .= (strlen($data1) > 0) ? "['".utf8_encode("Total Servicios")."', ".$totalProdTaller."]," : "{ name: '".utf8_encode("Total Servicios")."', y: ".$totalProdTaller.", sliced: true, selected: true },";
	
	// TOTAL REPUESTOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Total Repuestos"."</td>";
		$htmlTb .= "<td>".number_format(array_sum($arrayVentaMost),2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(((($totalProdTaller + array_sum($arrayVentaMost)) > 0) ? array_sum($arrayVentaMost) * 100 / ($totalProdTaller + array_sum($arrayVentaMost)) : 0),2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	$data1 .= (strlen($data1) > 0) ? "['".utf8_encode("Total Repuestos")."', ".array_sum($arrayVentaMost)."]," : "{ name: '".utf8_encode("Total Repuestos")."', y: ".array_sum($arrayVentaMost).", sliced: true, selected: true },";
	
	$data1 = substr($data1, 0, (strlen($data1)-1));
	
	// TOTAL
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."Total Facturación:"."</td>";
		$htmlTb .= "<td>".number_format(($totalProdTaller + array_sum($arrayVentaMost)),2,".",",")."</td>";
		$htmlTb .= "<td>".number_format(100,2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">TOTAL FACTURACIÓN</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						9,
						"Pie with legend",
						"TOTAL FACTURACIÓN",
						str_replace("'","|*|",array("")),
						"Monto",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaTotalFacturacion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// INDICADORES DE TALLER
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$array = NULL;
	$arrayMov = NULL;
	$data1 = "";
	
	$sqlBusq = "";
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("recepcion.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(recepcion.fecha_entrada) = %s
		AND YEAR(recepcion.fecha_entrada) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// ENTRADA DE VEHICULOS
	$queryValeRecepcion = sprintf("SELECT recepcion.id_recepcion FROM sa_recepcion recepcion %s", $sqlBusq);
	$rsValeRecepcion = mysql_query($queryValeRecepcion);
	if (!$rsValeRecepcion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalTipoOrdenAbierta = 0;
	while ($rowValeRecepcion = mysql_fetch_assoc($rsValeRecepcion)) {
		$totalValeRecepcion += 1;
	}
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (1)
	AND fact_vent.aplicaLibros = 1
	AND fact_vent.numeroPedido IS NOT NULL");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("vale_salida.id_orden IS NOT NULL");
	
	$sqlBusq3 = "";
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (1)
	AND fact_vent.aplicaLibros = 1
	AND nota_cred.tipoDocumento LIKE 'FA'
	AND fact_vent.numeroPedido IS NOT NULL");
	
	$sqlBusq4 = "";
	
	$sqlBusq5 = "";
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden
	AND fact_vent.idDepartamentoOrigenFactura IN (1)
	AND fact_vent.aplicaLibros = 1");
	
	$sqlBusq6 = "";
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden");
	
	$sqlBusq7 = "";
	$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
	$sqlBusq7 .= $cond.sprintf("orden.id_tipo_orden = tipo_orden.id_tipo_orden
	AND nota_cred.idDepartamentoNotaCredito IN (1)
	AND fact_vent.aplicaLibros = 1
	AND nota_cred.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vale_salida.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("nota_cred.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("fact_vent.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("vale_salida.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("nota_cred.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq8) > 0) ? " AND " : " WHERE ";
		$sqlBusq8 .= $cond.sprintf("tipo_orden.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) <= %s
		AND YEAR(fact_vent.fechaRegistroFactura) <= %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(vale_salida.fecha_vale) <= %s
		AND YEAR(vale_salida.fecha_vale) <= %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) <= %s
		AND YEAR(nota_cred.fechaNotaCredito) <= %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " AND ";
		$sqlBusq4 .= $cond.sprintf("MONTH(orden.tiempo_orden) <= %s
		AND YEAR(orden.tiempo_orden) <= %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
		AND YEAR(fact_vent.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("MONTH(vale_salida.fecha_vale) = %s
		AND YEAR(vale_salida.fecha_vale) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
		AND YEAR(nota_cred.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// ORDENES DE SERVICIOS ABIERTAS Y CERRADAS
	$queryOrdenServ = sprintf("SELECT
		tipo_orden.id_tipo_orden,
		tipo_orden.nombre_tipo_orden,
		
		(IFNULL((SELECT COUNT(orden.id_orden) FROM sa_orden orden
		WHERE orden.id_tipo_orden = tipo_orden.id_tipo_orden
			AND orden.id_orden NOT IN (SELECT fact_vent.numeroPedido
											FROM cj_cc_encabezadofactura fact_vent %s
									
									UNION
									
									SELECT vale_salida.id_orden
									FROM sa_vale_salida vale_salida %s
									
									UNION
									
									SELECT fact_vent.numeroPedido
									FROM cj_cc_encabezadofactura fact_vent
										INNER JOIN cj_cc_notacredito nota_cred ON (fact_vent.idFactura = nota_cred.idDocumento) %s
										
									ORDER BY 1) %s), 0)) AS cantidad_ordenes_abiertas,
		
		(IFNULL((SELECT COUNT(orden.id_orden)
		FROM cj_cc_encabezadofactura fact_vent
			INNER JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden) %s), 0)
		+
		IFNULL((SELECT COUNT(orden.id_orden)
		FROM sa_orden orden
			INNER JOIN sa_vale_salida vale_salida ON (orden.id_orden = vale_salida.id_orden) %s), 0)
		-	
		IFNULL((SELECT COUNT(orden.id_orden)
		FROM cj_cc_encabezadofactura fact_vent
			INNER JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)
			INNER JOIN cj_cc_notacredito nota_cred ON (fact_vent.idFactura = nota_cred.idDocumento) %s), 0)) AS cantidad_ordenes_cerradas
	FROM sa_tipo_orden tipo_orden %s
	ORDER BY tipo_orden.nombre_tipo_orden ASC", $sqlBusq, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6, $sqlBusq7, $sqlBusq8);
	$rsOrdenServ = mysql_query($queryOrdenServ);
	if (!$rsOrdenServ) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowOrdenServ = mysql_fetch_assoc($rsOrdenServ)) {
		$arrayDet[0] = $rowOrdenServ['nombre_tipo_orden'];
		$arrayDet[1] = $rowOrdenServ['cantidad_ordenes_abiertas'];
		$arrayDet[2] = $rowOrdenServ['cantidad_ordenes_cerradas'];
		
		$arrayTipoOrden[$rowOrdenServ['id_tipo_orden']] = $arrayDet;
		
		$totalTipoOrdenAbierta += $rowOrdenServ['cantidad_ordenes_abiertas'];
		$totalTipoOrdenCerrada += $rowOrdenServ['cantidad_ordenes_cerradas'];
	}
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipo <> 'FERIADO'");
		
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fecha_baja) = %s
		AND (YEAR(fecha_baja) = %s OR YEAR(fecha_baja) = '0000')",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// BUSCA LOS DIAS FERIADOS
	$query = sprintf("SELECT *
	FROM pg_fecha_baja %s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsDiasFeriados = mysql_num_rows($rs);

	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"40%\">Indicador</td>";
		$htmlTh .= "<td width=\"60%\">Unidad</td>";
	$htmlTh .= "</tr>";
	
	// MANO DE OBRA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Mano de Obra"."</td>";
		$htmlTb .= "<td>".number_format($subTotalMO, 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// DIAS HABILES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Días Hábiles Mes"."</td>";
		$htmlTb .= "<td>";
			$diaHabiles = evaluaFecha(diasHabiles('01-'.$valFecha[0].'-'.$valFecha[1], ultimoDia($valFecha[0],$valFecha[1]).'-'.$valFecha[0].'-'.$valFecha[1])) - $totalRowsDiasFeriados;
			$htmlTb .= number_format($diaHabiles, 2, ".", ",");
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	// NUMERO DE TECNICOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltar2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Nro. Técnicos"."</td>";
		$htmlTb .= "<td>".number_format($arrayMecanico[1], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// HORAS DISPONIBLE VENTA TECNICOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltar2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Hrs. Disp. Venta Técnicos"."</td>";
		$htmlTb .= "<td>".number_format($arrayMecanico[1] * 7.1 * ($diaHabiles - $totalRowsDiasFeriados), 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// HORAS PROMEDIO TECNICOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltar2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Hrs. Prom / Técnicos"."</td>";
		$htmlTb .= "<td>";
			$htmlTb .= number_format((($arrayMecanico[1] > 0) ? ($arrayMecanico[1] * 7.1 * ($diaHabiles - $totalRowsDiasFeriados)) / $arrayMecanico[1] : 0), 2, ".", ",");
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	// NUMERO DE TECNICOS EN FORMACION
	$htmlTb .= "<tr align=\"right\" class=\"trResaltar\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Nro. Técnicos en Formación"."</td>";
		$htmlTb .= "<td>".number_format($arrayMecanico[0], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// HORAS DISPONIBLE VENTA TECNICOS EN FORMACION
	$htmlTb .= "<tr align=\"right\" class=\"trResaltar\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Hrs. Disp. Venta Técnicos en Formación"."</td>";
		$htmlTb .= "<td>".number_format($arrayMecanico[0] * 3.57 * ($diaHabiles - $totalRowsDiasFeriados), 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// HORAS PROMEDIO TECNICOS EN FORMACION
	$htmlTb .= "<tr align=\"right\" class=\"trResaltar\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Hrs. Prom / Técnicos en Formación"."</td>";
		$htmlTb .= "<td>";
			$htmlTb .= number_format((($arrayMecanico[0] > 0) ? ($arrayMecanico[0] * 3.57 * ($diaHabiles - $totalRowsDiasFeriados)) / $arrayMecanico[0] : 0), 2, ".", ",");
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	// ENTRADA DE VEHICULOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Entrada de Vehículos"."</td>";
		$htmlTb .= "<td>";
			$htmlTb .= number_format($totalValeRecepcion, 2, ".", ",");
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	// ORDENES DE SERVICIOS ABIERTAS
	if (isset($arrayTipoOrden)) {
		foreach ($arrayTipoOrden as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">O/R Abiertas ".$arrayTipoOrden[$indice][0]."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format($arrayTipoOrden[$indice][1], 2, ".", ",");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// TOTAL DE ORDENES ABIERTAS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total O/R Abiertas"."</td>";
		$htmlTb .= "<td>".number_format($totalTipoOrdenAbierta, 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// ORDENES DE SERVICIOS CERRADAS
	if (isset($arrayTipoOrden)) {
		foreach ($arrayTipoOrden as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">O/R Cerradas ".$arrayTipoOrden[$indice][0]."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= number_format($arrayTipoOrden[$indice][2], 2, ".", ",");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// TOTAL DE ORDENES CERRADAS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total O/R Cerradas"."</td>";
		$htmlTb .= "<td>".number_format($totalTipoOrdenCerrada, 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Rptos. Servicios"."</td>";
		$htmlTb .= "<td>".number_format($subTotalRepServ - $totalDescuentoRepServ, 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// BS REPUESTOS ENTRE ORDENES
	$totalTipoOrdenCerrada = ($totalTipoOrdenCerrada > 0) ? $totalTipoOrdenCerrada : 1;
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">".cAbrevMoneda." Rptos / OR"."</td>";
		$htmlTb .= "<td>".number_format(($subTotalRepServ - $totalDescuentoRepServ) / $totalTipoOrdenCerrada, 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	// HORAS ENTRE ORDENES
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Hrs. / OR"."</td>";
		$htmlTb .= "<td>".number_format($totalTotalUtsEquipos / $totalTipoOrdenCerrada, 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">"."<p class=\"textoAzul\">INDICADORES DE TALLER</p>"."</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaIndicadoresTaller","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	$objResponse->script("
	byId('tblMsj').style.display = 'none';
	byId('tblInforme').style.display = '';");

	return $objResponse;
}

function formGrafico($tipoGrafico, $tituloVentana, $categoria, $titulo1, $data1, $titulo2 = "", $data2 = "", $abrevMonedaLocal = "Bs.") {
	$objResponse = new xajaxResponse();
	
	if ($tipoGrafico == "Pie with legend") {
		// GRAFICO
		$data1 = "
		highchartsDarkBlue();
		
		$(function () {
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: '"."tdGrafico"."',
						plotBackgroundColor: null,
						plotBorderWidth: null,
						plotShadow: false,
						width: 780
					},
					title: {
						text: '".$tituloVentana."'
					},
					tooltip: {
						valueDecimals: 2,
						valueSuffix: '".$abrevMonedaLocal."'
					},
					plotOptions: {
						pie: {
							allowPointSelect: true,
							cursor: 'pointer',
							dataLabels: {
								enabled: true,
								color: '#FFFFFF',
								connectorColor: '#FFFFFF',
								formatter: function() {
									return this.percentage + '%';
								}
							},
							showInLegend: true
						}
					},
					series: [{
						type: 'pie',
						name: '".$titulo1."',
						data: [".str_replace("|*|","'",$data1)."]
					}]
				});
			});
		});";
	} else if ($tipoGrafico == "Donut chart") {
		$arrayEquipo = explode(",",$data1);
		foreach ($arrayEquipo as $indice => $valor) {
			$arrayDetEquipo = NULL;
			$arrayDetEquipo = explode("=*=", $arrayEquipo[$indice]);
			
			foreach ($arrayDetEquipo as $indice2 => $valor2) {
				$arrayMec = explode("-*-",$arrayDetEquipo[2]);
				
				$arrayCategories = NULL;
				$arrayData = NULL;
				foreach ($arrayMec as $indice3 => $valor3) {
					$arrayDetMec = explode("+*+",$arrayMec[$indice3]);
					
					$arrayCategories[] = $arrayDetMec[0];
					$arrayData[] = $arrayDetMec[1];
				}
			}
			
			$arrayDataEquipo[] = "{
				y: ".$arrayDetEquipo[1].",
				color: colors[".$indice."],
				drilldown: {
					name: '".$arrayDetEquipo[0]."',
					categories: ['".implode("','",$arrayCategories)."'],
					data: [".implode(",",$arrayData)."],
					color: colors[".$indice."]
				}
			}";
			
			$arrayCategoriesEquipo[] = $arrayDetEquipo[0];
		}
		
		$data1 = "
		highchartsDarkBlue();
		
		$(function () {
			var colors = Highcharts.getOptions().colors,
				categories = ['".implode("','", $arrayCategoriesEquipo)."'],
				name = '".$tituloVentana."',
				data = [".implode(",", $arrayDataEquipo)."];
		
		
			// Build the data arrays
			var browserData = [];
			var versionsData = [];
			for (var i = 0; i < data.length; i++) {
		
				// add browser data
				browserData.push({
					name: categories[i],
					y: data[i].y,
					color: data[i].color
				});
		
				// add version data
				for (var j = 0; j < data[i].drilldown.data.length; j++) {
					var brightness = 0.2 - (j / data[i].drilldown.data.length) / 5 ;
					versionsData.push({
						name: data[i].drilldown.categories[j],
						y: data[i].drilldown.data[j],
						color: Highcharts.Color(data[i].color).brighten(brightness).get()
					});
				}
			}
		
			// Create the chart
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: 'tdGrafico',
						type: 'pie'
					},
					title: {
						text: '".$tituloVentana."'
					},
					yAxis: {
						title: {
							text: 'Total percent market share'
						}
					},
					plotOptions: {
						pie: {
							shadow: false,
							center: ['50%', '50%']
						}
					},
					tooltip: {
						valueSuffix: '%'
					},
					series: [{
						name: '".$titulo1."',
						data: browserData,
						size: '60%',
						dataLabels: {
							formatter: function() {
								return this.y > 5 ? this.point.name : null;
							},
							color: 'white',
							distance: -30
						}
					}, {
						name: '".$titulo2."',
						data: versionsData,
						size: '80%',
						innerSize: '60%',
						dataLabels: {
							formatter: function() {
								// display only if larger than 1
								return this.y > 1 ? '<b>'+ this.point.name +':</b> '+ this.y +'%'  : null;
							}
						}
					}]
				});
			});
		});";
	} else if ($tipoGrafico == "Column with negative values") {
		$arrayClave = explode(",",$data1);
		foreach ($arrayClave as $indice => $valor) {
			$arrayDetClave = NULL;
			$arrayDetClave = explode("=*=", $arrayClave[$indice]);
			
			$arrayMes = explode("-*-", $arrayDetClave[1]);
			$arrayCategories = NULL;
			$arrayData = NULL;
			foreach ($arrayMes as $indice2 => $valor2) {
				$arrayDetMes = NULL;
				$arrayDetMes = explode("+*+", $arrayMes[$indice2]);
				
				$arrayCategories[] = $arrayDetMes[0];
				$arrayData[] = $arrayDetMes[1];
			}
			
			$arrayDataEquipo[] = "{
				name: '".$arrayDetClave[0]."',
				data: [".implode(",",$arrayData)."]
			}";
		}
		
		$data1 = "
		highchartsDarkBlue();
		
		$(function () {
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: 'tdGrafico',
						type: 'column'
					},
					title: {
						text: '".$tituloVentana."'
					},
					xAxis: {
						categories: ['".implode("','",$arrayCategories)."']
					},
					credits: {
						enabled: false
					},
					series: [".implode(",", $arrayDataEquipo)."]
				});
			});
		});";
	} else if ($tipoGrafico == "Basic column") {
		$arrayClave = explode(",",$data1);
		foreach ($arrayClave as $indice => $valor) {
			$arrayDetClave = NULL;
			$arrayDetClave = explode("=*=", $arrayClave[$indice]);
			
			$arrayMes = explode("-*-", $arrayDetClave[1]);
			$arrayCategories = NULL;
			$arrayData = NULL;
			foreach ($arrayMes as $indice2 => $valor2) {
				$arrayDetMes = NULL;
				$arrayDetMes = explode("+*+", $arrayMes[$indice2]);
				
				$arrayCategories[] = $arrayDetMes[0];
				$arrayData[] = $arrayDetMes[1];
			}
			
			$arrayDataEquipo[] = "{
				name: '".$arrayDetClave[0]."',
				data: [".implode(",",$arrayData)."]
			}";
		}
		
		$data1 = "
		highchartsDarkBlue();
		
		$(function () {
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: 'tdGrafico',
						type: 'column'
					},
					title: {
						text: '".$tituloVentana."'
					},
					xAxis: {
						categories: ['".implode("','",$arrayCategories)."']
					},
					yAxis: {
						min: 0,
						title: {
							text: '".$abrevMonedaLocal."'
						}
					},
					legend: {
						layout: 'vertical',
						backgroundColor: '#FFFFFF',
						align: 'left',
						verticalAlign: 'top',
						x: 100,
						y: 70,
						floating: true,
						shadow: true
					},
					tooltip: {
						formatter: function() {
							var num = this.y;
							num += '';
							var splitStr = num.split('.');
							var splitLeft = splitStr[0];
							var splitRight = splitStr.length > 1 ? '.' + splitStr[1] : '';
							var regx = /(\d+)(\d{3})/;
							while (regx.test(splitLeft)) {
								splitLeft = splitLeft.replace(regx, '$1' + ',' + '$2');
							}
							return '' + this.x + ': ' + splitLeft + splitRight + ' ".$abrevMonedaLocal."';
						}
					},
					plotOptions: {
						column: {
							pointPadding: 0.2,
							borderWidth: 0
						}
					},
					series: [".implode(",", $arrayDataEquipo)."]
				});
			});
		});";
	}
	
	$objResponse->script($data1);
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML",$tituloVentana);
	
	return $objResponse;
}

function imprimirResumen($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha']);
	
	$objResponse->script(sprintf("verVentana('reportes/if_resumen_postventa_pdf.php?valBusq=%s', 1000, 500);",
		$valBusq));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"exportarResumen");
$xajax->register(XAJAX_FUNCTION,"facturacionVendedores");
$xajax->register(XAJAX_FUNCTION,"formGrafico");
$xajax->register(XAJAX_FUNCTION,"imprimirResumen");
?>