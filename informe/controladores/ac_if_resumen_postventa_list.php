<?php
set_time_limit(0);

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();

	$objResponse->loadCommands(facturacionVendedores($frmBuscar));

	return $objResponse;
}

function cargaLstDecimalPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("0" => "Sin Decimales", "1" => "Con Decimales","2" => "Sin Decimales MILES", "3" => "Con Decimales MILES");
	$totalRows = count($array);
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirResumen(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstDecimalPDF\" name=\"lstDecimalPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstDecimalPDF","innerHTML",$html);
	
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
	// COMPRAS DE REPUESTOS Y ACCESORIOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$Result1 = facturacionMovimiento($idEmpresa, $valFecha[0], $valFecha[1], "0", "1,4");
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]); 
	} else {
		$arrayMovCompras = $Result1[1];
		$totalNetoClaveMovCompras = $Result1[2];
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
			
			if (in_array($arrayMovCompras[$indice]['id_tipo_movimiento'], array(1,3))) {
				$arrayMovCompras[$indice]['total_neto'] = $arrayMovCompras[$indice]['total_neto'];
			} else if (in_array($arrayMovCompras[$indice]['id_tipo_movimiento'], array(2,4))) {
				switch($arrayMovCompras[$indice]['tipo_documento_movimiento']) { // 1 = Vale Entrada / Salida, 2 = Nota Credito
					case 1 : $arrayMovCompras[$indice]['total_neto'] = $arrayMovCompras[$indice]['total_neto']; break;
					case 2 : $arrayMovCompras[$indice]['total_neto'] = (-1) * $arrayMovCompras[$indice]['total_neto']; break;
				}
			}

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayMovCompras[$indice]['clave_movimiento'])."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayMovCompras[$indice]['total_neto'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format((($arrayMovCompras[$indice]['total_neto'] * 100) / $totalNetoClaveMovCompras), 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMes = NULL;
			$arrayDet2[0] = $mes[intval($valFecha[0])]." ".$valFecha[1];
			$arrayDet2[1] = $arrayMovCompras[$indice]['total_neto'];
			$arrayMes[] = implode("+*+",$arrayDet2);
			
			$arrayDet[0] = str_replace(","," ",utf8_encode($arrayMovCompras[$indice]['clave_movimiento']));
			$arrayDet[1] = implode("-*-",$arrayMes);
			$arrayClave[] = implode("=*=",$arrayDet);
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".utf8_encode("Total Compras Repuestos:")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalNetoClaveMovCompras, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">COMPRAS DE REPUESTOS Y ACCESORIOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						1,
						"Column with negative values",
						"COMPRAS DE REPUESTOS Y ACCESORIOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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
		$sqlBusq .= $cond.sprintf("(cierre_mens.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cierre_mens.id_empresa))",
			valTpDato($idEmpresa, "int"),
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
		INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s
	ORDER BY clasificacion ASC;", $sqlBusq);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
		$cantExistencia = round($rowDetalle['cantidad_existencia'],2);
		$costoInv = round($rowDetalle['costo_total'],2);
		$promVenta = round($rowDetalle['promedio_mensual'] * $rowDetalle['costo'],2);
		
		$existeAnalisisInv = false;
		if (isset($arrayAnalisisInv)) {
			foreach ($arrayAnalisisInv as $indice2 => $valor2) {
				if ($rowDetalle['clasificacion'] == $arrayAnalisisInv[$indice2][0]) {
					$existeAnalisisInv = true;
					
					$arrayAnalisisInv[$indice2][1]++;
					$arrayAnalisisInv[$indice2][2] += $cantExistencia;
					$arrayAnalisisInv[$indice2][3] += $costoInv;
					$arrayAnalisisInv[$indice2][4] += $promVenta;
					$arrayAnalisisInv[$indice2][5] += (($arrayAnalisisInv[$indice2][4] > 0) ? ($arrayAnalisisInv[$indice2][3] / $arrayAnalisisInv[$indice2][4]) : 0);
				}
			}
		}
		
		if ($existeAnalisisInv == false) {
			$arrayAnalisisInv[] = array(
				$rowDetalle['clasificacion'],
				1,
				$cantExistencia,
				$costoInv,
				$promVenta,
				(($promVenta > 0) ? ($costoInv / $promVenta) : 0));
		}
		
		$totalCantArt++;
		$totalExistArt += $cantExistencia;
		$totalCostoInv += $costoInv;
		$totalPromVentas += $promVenta;
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
					<td title=\"Importe ".cAbrevMoneda." / Prom. Ventas ".cAbrevMoneda."\" width=\"10%\">".("Meses Exist.")."</td>
					<td width=\"10%\">".("Exist. / Nro. Items")."</td>";
	$htmlTh .= "</tr>";

	$contFila = 0;
	if (isset($arrayAnalisisInv)) {
		foreach ($arrayAnalisisInv as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"center\">".$arrayAnalisisInv[$indice][0]."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayAnalisisInv[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format((($totalCantArt > 0) ? ($arrayAnalisisInv[$indice][1] * 100 / $totalCantArt) : 0), 2, ".", ",")."%</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayAnalisisInv[$indice][2], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format((($totalExistArt > 0) ? ($arrayAnalisisInv[$indice][2] * 100 / $totalExistArt) : 0), 2, ".", ",")."%</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayAnalisisInv[$indice][3], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format((($totalCostoInv > 0) ? ($arrayAnalisisInv[$indice][3] * 100 / $totalCostoInv) : 0), 2, ".", ",")."%</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayAnalisisInv[$indice][4], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayAnalisisInv[$indice][3] / $arrayAnalisisInv[$indice][4], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format(($arrayAnalisisInv[$indice][2] / $arrayAnalisisInv[$indice][1]), 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";

			$data1 .= (strlen($data1) > 0) ? "['".utf8_encode($arrayAnalisisInv[$indice][0])."', ".$arrayAnalisisInv[$indice][1]."]," : "{ name: '".utf8_encode($arrayAnalisisInv[$indice][0])."', y: ".$arrayAnalisisInv[$indice][1].", sliced: true, selected: true },";
		}
	}
	$data1 = substr($data1, 0, (strlen($data1)-1));
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total:</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalCantArt, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalExistArt, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalCostoInv, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalPromVentas, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format((($totalPromVentas > 0) ? $totalCostoInv / $totalPromVentas : 0), 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format((($totalCantArt > 0) ? $totalExistArt / $totalCantArt : 0), 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "<tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">ANÁLISIS DE INVENTARIO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						2,
						"Pie with legend",
						"ANÁLISIS DE INVENTARIO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cierre_mens.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cierre_mens.id_empresa))",
			valTpDato($idEmpresa, "int"),
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
	GROUP BY analisis_inv.id_analisis_inventario, analisis_inv_det.clasificacion_anterior", $sqlBusq);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTipoMov = mysql_fetch_assoc($rsTipoMov)) {
		$queryNroVend = sprintf("SELECT
			cierre_anual.%s AS numero_vendido
		FROM iv_cierre_anual cierre_anual
		WHERE cierre_anual.id_articulo IN (SELECT id_articulo FROM iv_analisis_inventario_detalle analisis_inv_det
				WHERE analisis_inv_det.id_analisis_inventario = %s AND analisis_inv_det.clasificacion_anterior = %s)
			AND cierre_anual.ano = %s
			AND cierre_anual.%s IS NOT NULL
			AND cierre_anual.%s > 0
			AND cierre_anual.id_empresa = %s",
			valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
			valTpDato($rowTipoMov['id_analisis_inventario'], "int"), valTpDato($rowTipoMov['clasificacion_anterior'], "text"),
			valTpDato($rowTipoMov['ano'], "int"),
			valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
			valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
			valTpDato($rowTipoMov['id_empresa'], "int"));
		$rsNroVend = mysql_query($queryNroVend);
		if (!$rsNroVend) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNroVend = mysql_num_rows($rsNroVend);

		$queryCantVend = sprintf("SELECT SUM(IFNULL(cierre_anual.%s, 0)) AS cantidad_vendida
		FROM iv_cierre_anual cierre_anual
		WHERE cierre_anual.id_articulo IN (SELECT id_articulo FROM iv_analisis_inventario_detalle analisis_inv_det
				WHERE analisis_inv_det.id_analisis_inventario = %s AND analisis_inv_det.clasificacion_anterior = %s)
			AND cierre_anual.ano = %s
			AND cierre_anual.id_empresa = %s",
			valTpDato(strtolower($mes[intval($valFecha[0])]), "campo"),
			valTpDato($rowTipoMov['id_analisis_inventario'], "int"), valTpDato($rowTipoMov['clasificacion_anterior'], "text"),
			valTpDato($rowTipoMov['ano'], "int"),
			valTpDato($rowTipoMov['id_empresa'], "int"));
		$rsCantVend = mysql_query($queryCantVend);
		if (!$rsCantVend) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCantVend = mysql_fetch_assoc($rsCantVend);
		
		$existeCantArtVend = false;
		if (isset($arrayCantArtVend)) {
			foreach ($arrayCantArtVend as $indiceCantArtVend => $valorCantArtVend) {
				if ($rowTipoMov['clasificacion_anterior'] == $arrayCantArtVend[$indiceCantArtVend][0]) {
					$existeCantArtVend = true;
					
					$arrayCantArtVend[$indiceCantArtVend][1] += $totalRowsNroVend;
					$arrayCantArtVend[$indiceCantArtVend][2] += $rowCantVend['cantidad_vendida'];
				}
			}
		}
		
		if ($existeCantArtVend == false) {
			$arrayCantArtVend[] = array(
				$rowTipoMov['clasificacion_anterior'],
				$totalRowsNroVend,
				$rowCantVend['cantidad_vendida']);
		}
		
		$totalNroArt += $totalRowsNroVend;
		$totalCantArtVend += $rowCantVend['cantidad_vendida'];
	}

	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<thead>";
		$htmlTh .= "<td colspan=\"14\">"."<p class=\"textoAzul\">CANTIDAD DE ITEMS Y ARTÍCULOS VENDIDOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
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
				$htmlTb .= "<td align=\"right\">".valTpDato(number_format($arrayCantArtVend[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format((($totalNroArt > 0) ? ($arrayCantArtVend[$indice][1] * 100 / $totalNroArt) : 0), 2, ".", ",")."%</td>";
				$htmlTb .= "<td align=\"right\">".valTpDato(number_format($arrayCantArtVend[$indice][2], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format((($totalCantArtVend > 0) ? ($arrayCantArtVend[$indice][2] * 100 / $totalCantArtVend) : 0), 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total:</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalNroArt, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalCantArtVend, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
	$htmlTb .= "<tr>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaCantidadVendida","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
}

function ventasRepuestosMostrador($objResponse, $idEmpresa, $valFecha) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTAS DE REPUESTOS POR MOSTRADOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$Result1 = facturacionMovimiento($idEmpresa, $valFecha[0], $valFecha[1], "0", "2,3");
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]); 
	} else {
		$arrayMovVentas = $Result1[1];
		$totalNetoClaveMovVentas = $Result1[2];
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
			
			if (in_array($arrayMovVentas[$indice]['id_tipo_movimiento'], array(1,3))) {
				$arrayMovVentas[$indice]['total_neto'] = $arrayMovVentas[$indice]['total_neto'];
			} else if (in_array($arrayMovVentas[$indice]['id_tipo_movimiento'], array(2,4))) {
				switch($arrayMovVentas[$indice]['tipo_documento_movimiento']) { // 1 = Vale Entrada / Salida, 2 = Nota Credito
					case 1 : $arrayMovVentas[$indice]['total_neto'] = $arrayMovVentas[$indice]['total_neto']; break;
					case 2 : $arrayMovVentas[$indice]['total_neto'] = (-1) * $arrayMovVentas[$indice]['total_neto']; break;
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayMovVentas[$indice]['clave_movimiento'])."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayMovVentas[$indice]['total_neto'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format((($arrayMovVentas[$indice]['total_neto'] * 100) / $totalNetoClaveMovVentas), 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMes = NULL;
			$arrayDet2[0] = $mes[intval($valFecha[0])]." ".$valFecha[1];
			$arrayDet2[1] = $arrayMovVentas[$indice]['total_neto'];
			$arrayMes[] = implode("+*+",$arrayDet2);
			
			$arrayDet[0] = str_replace(","," ",utf8_encode($arrayMovVentas[$indice]['clave_movimiento']));
			$arrayDet[1] = implode("-*-",$arrayMes);
			$arrayClave[] = implode("=*=",$arrayDet);
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".utf8_encode("Total Ventas Repuestos y Accesorios:")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalNetoClaveMovVentas, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTAS DE REPUESTOS POR MOSTRADOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						3,
						"Column with negative values",
						"VENTAS DE REPUESTOS POR MOSTRADOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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
	$Result1 = facturacionMovimiento($idEmpresa, $valFecha[0], $valFecha[1], "1", "2,3,4");
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]); 
	} else {
		$arrayMovVentasServ = $Result1[1];
		$totalNetoClaveMovVentasServ = $Result1[2];
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
			
			if (in_array($arrayMovVentasServ[$indice]['id_tipo_movimiento'], array(1,3))) {
				$arrayMovVentasServ[$indice]['total_neto'] = $arrayMovVentasServ[$indice]['total_neto'];
			} else if (in_array($arrayMovVentasServ[$indice]['id_tipo_movimiento'], array(2,4))) {
				switch($arrayMovVentasServ[$indice]['tipo_documento_movimiento']) { // 1 = Vale Entrada / Salida, 2 = Nota Credito
					case 1 : $arrayMovVentasServ[$indice]['total_neto'] = $arrayMovVentasServ[$indice]['total_neto']; break;
					case 2 : $arrayMovVentasServ[$indice]['total_neto'] = (-1) * $arrayMovVentasServ[$indice]['total_neto']; break;
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayMovVentasServ[$indice]['clave_movimiento'])."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayMovVentasServ[$indice]['total_neto'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format((($arrayMovVentasServ[$indice]['total_neto'] * 100) / $totalNetoClaveMovVentasServ), 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMes = NULL;
			$arrayDet2[0] = $mes[intval($valFecha[0])]." ".$valFecha[1];
			$arrayDet2[1] = $arrayMovVentasServ[$indice]['total_neto'];
			$arrayMes[] = implode("+*+",$arrayDet2);
			
			$arrayDet[0] = str_replace(","," ",utf8_encode($arrayMovVentasServ[$indice]['clave_movimiento']));
			$arrayDet[1] = implode("-*-",$arrayMes);
			$arrayClave[] = implode("=*=",$arrayDet);
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".utf8_encode("Total Ventas Repuestos y Accesorios por Servicios:")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalNetoClaveMovVentasServ, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTAS DE REPUESTOS POR SERVICIOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						4,
						"Column with negative values",
						"VENTAS DE REPUESTOS POR SERVICIOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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

function facturacionAsesoresServicios($objResponse, $idEmpresa, $valFecha, $idCierreMensual) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// FACTURACIÓN ASESORES DE SERVICIOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	if (count($idCierreMensual) > 0 && $idCierreMensual != "-1") {
		$queryCierreFacturacion = sprintf("SELECT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			cierre_mensual_fact.id_tipo_orden,
			cierre_mensual_fact.cantidad_ordenes,
			cierre_mensual_fact.total_ut,
			cierre_mensual_fact.total_mano_obra,
			cierre_mensual_fact.total_tot,
			cierre_mensual_fact.total_repuesto
		FROM iv_cierre_mensual_facturacion cierre_mensual_fact
			INNER JOIN pg_empleado empleado ON (cierre_mensual_fact.id_empleado = empleado.id_empleado)
		WHERE cierre_mensual_fact.id_cierre_mensual IN (%s)
			AND cierre_mensual_fact.id_modulo IN (1)
			AND cierre_mensual_fact.id_tipo_orden IS NOT NULL;",
			valTpDato($idCierreMensual, "campo"));
		$rsCierreFacturacion = mysql_query($queryCierreFacturacion);
		if (!$rsCierreFacturacion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowCierreFacturacion = mysql_fetch_assoc($rsCierreFacturacion)) {
			$totalMoAsesor = $rowCierreFacturacion['total_mano_obra'];
			$totalTotAsesor = $rowCierreFacturacion['total_tot'];
			$totalRepuetosAsesor = $rowCierreFacturacion['total_repuesto'];
			
			$arrayVentaAsesor[] = array(
				"id_empleado" => $rowCierreFacturacion['id_empleado'],
				"nombre_asesor" => $rowCierreFacturacion['nombre_empleado'],
				"id_tipo_orden" => $rowCierreFacturacion['id_tipo_orden'],
				"cantidad_ordenes" => $rowCierreFacturacion['cantidad_ordenes'],
				"total_ut" => $rowCierreFacturacion['total_ut'],
				"total_mo" => $totalMoAsesor,
				"total_repuestos" => $totalRepuetosAsesor,
				"total_tot" => $totalTotAsesor,
				"total_asesor" => $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor);
			
			//$totalVentaAsesores += $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor;
		}
	} else {
		$Result1 = facturacionAsesores($idEmpresa, $valFecha[0], $valFecha[1]);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayVentaAsesor = $Result1[1];
			//$totalVentaAsesores = $Result1[2];
		}
	}
	
	// AGRUPA LOS TIPO DE ORDEN POR FILTRO DE ORDEN
	$arrayFiltroOrden = NULL;
	if (isset($arrayVentaAsesor)) {
		foreach ($arrayVentaAsesor as $indice => $valor) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(tipo_orden.id_tipo_orden = %s)",
				valTpDato($arrayVentaAsesor[$indice]['id_tipo_orden'], "int"));
			
			$queryFiltroOrden = sprintf("SELECT
				filtro_orden.id_filtro_orden,
				filtro_orden.descripcion
			FROM sa_tipo_orden tipo_orden
				INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s", $sqlBusq);
			$rsFiltroOrden = mysql_query($queryFiltroOrden);
			if (!$rsFiltroOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsFiltroOrden = mysql_num_rows($rsFiltroOrden);
			while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
				$existe = false;
				$arrayDetalleFiltroOrden = NULL;
				if (isset($arrayFiltroOrden)) {
					foreach ($arrayFiltroOrden as $indice2 => $valor2) {
						if ($arrayFiltroOrden[$indice2]['id_empleado'] == $arrayVentaAsesor[$indice]['id_empleado']) {
							$existe = true;
							
							$existeFiltroOrden = false;
							$arrayDetalleFiltroOrden = NULL;
							if (isset($arrayFiltroOrden[$indice2]['array_tipo_orden'])) {
								foreach ($arrayFiltroOrden[$indice2]['array_tipo_orden'] as $indice3 => $valor3) {
									$arrayDetalleFiltroOrden = $valor3;
									if ($arrayDetalleFiltroOrden['id_tipo_orden'] == $rowFiltroOrden['id_filtro_orden']) {
										$existeFiltroOrden = true;
										
										$arrayDetalleFiltroOrden['cantidad_ordenes'] += round($arrayVentaAsesor[$indice]['cantidad_ordenes'],2);
										$arrayDetalleFiltroOrden['total_ut'] += round($arrayVentaAsesor[$indice]['total_ut'],2);
										$arrayDetalleFiltroOrden['total_mo'] += round($arrayVentaAsesor[$indice]['total_mo'],2);
										$arrayDetalleFiltroOrden['total_repuestos'] += round($arrayVentaAsesor[$indice]['total_repuestos'],2);
										$arrayDetalleFiltroOrden['total_tot'] += round($arrayVentaAsesor[$indice]['total_tot'],2);
										$arrayDetalleFiltroOrden['total_asesor'] += round($arrayVentaAsesor[$indice]['total_asesor'],2);
									}
									
									$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['cantidad_ordenes'] = $arrayDetalleFiltroOrden['cantidad_ordenes'];
									$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_ut'] = $arrayDetalleFiltroOrden['total_ut'];
									$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_mo'] = $arrayDetalleFiltroOrden['total_mo'];
									$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_repuestos'] = $arrayDetalleFiltroOrden['total_repuestos'];
									$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_tot'] = $arrayDetalleFiltroOrden['total_tot'];
									$arrayFiltroOrden[$indice2]['array_tipo_orden'][$indice3]['total_asesor'] = $arrayDetalleFiltroOrden['total_asesor'];
								}
							}
							
							if ($existeFiltroOrden == false) {
								$arrayDetalleFiltroOrden = array(
									"id_tipo_orden" => $rowFiltroOrden['id_filtro_orden'],
									"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
									"cantidad_ordenes" => $arrayVentaAsesor[$indice]['cantidad_ordenes'],
									"total_ut" => $arrayVentaAsesor[$indice]['total_ut'],
									"total_mo" => $arrayVentaAsesor[$indice]['total_mo'],
									"total_repuestos" => $arrayVentaAsesor[$indice]['total_repuestos'],
									"total_tot" => $arrayVentaAsesor[$indice]['total_tot'],
									"total_asesor" => $arrayVentaAsesor[$indice]['total_asesor']);
								
								$arrayFiltroOrden[$indice2]['array_tipo_orden'][] = $arrayDetalleFiltroOrden;
							}
							
							$arrayFiltroOrden[$indice2]['cantidad_ordenes'] += $arrayVentaAsesor[$indice]['cantidad_ordenes'];
							$arrayFiltroOrden[$indice2]['total_ut'] += $arrayVentaAsesor[$indice]['total_ut'];
							$arrayFiltroOrden[$indice2]['total_mo'] += $arrayVentaAsesor[$indice]['total_mo'];
							$arrayFiltroOrden[$indice2]['total_repuestos'] += $arrayVentaAsesor[$indice]['total_repuestos'];
							$arrayFiltroOrden[$indice2]['total_tot'] += $arrayVentaAsesor[$indice]['total_tot'];
							$arrayFiltroOrden[$indice2]['total_asesor'] += $arrayVentaAsesor[$indice]['total_asesor'];
						}
					}
				}
				
				if ($existe == false) {
					$arrayDetalleFiltroOrden[] = array(
						"id_tipo_orden" => $rowFiltroOrden['id_filtro_orden'],
						"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
						"cantidad_ordenes" => $arrayVentaAsesor[$indice]['cantidad_ordenes'],
						"total_ut" => $arrayVentaAsesor[$indice]['total_ut'],
						"total_mo" => $arrayVentaAsesor[$indice]['total_mo'],
						"total_repuestos" => $arrayVentaAsesor[$indice]['total_repuestos'],
						"total_tot" => $arrayVentaAsesor[$indice]['total_tot'],
						"total_asesor" => $arrayVentaAsesor[$indice]['total_asesor']);
					
					$arrayFiltroOrden[] = array(
						"id_empleado" => $arrayVentaAsesor[$indice]['id_empleado'],
						"nombre_asesor" => $arrayVentaAsesor[$indice]['nombre_asesor'],
						//"id_tipo_orden" => $rowFiltroOrden['id_filtro_orden'],
						//"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
						"array_tipo_orden" => $arrayDetalleFiltroOrden,
						"cantidad_ordenes" => $arrayVentaAsesor[$indice]['cantidad_ordenes'],
						"total_ut" => $arrayVentaAsesor[$indice]['total_ut'],
						"total_mo" => $arrayVentaAsesor[$indice]['total_mo'],
						"total_repuestos" => $arrayVentaAsesor[$indice]['total_repuestos'],
						"total_tot" => $arrayVentaAsesor[$indice]['total_tot'],
						"total_asesor" => $arrayVentaAsesor[$indice]['total_asesor']);
				}
				
				$totalVentaAsesores += $arrayVentaAsesor[$indice]['total_asesor'];
			}
			
			if (!($totalRowsFiltroOrden > 0)
			&& !($arrayVentaAsesor[$indice]['id_tipo_orden'] > 0)
			&& ($arrayVentaAsesor[$indice]['cantidad_ordenes'] > 0 || $arrayVentaAsesor[$indice]['total_asesor'] > 0)) {
				$arrayFiltroOrden[] = array(
					"id_empleado" => $arrayVentaAsesor[$indice]['id_empleado'],
					"nombre_asesor" => $arrayVentaAsesor[$indice]['nombre_asesor'],
					//"id_tipo_orden" => $rowFiltroOrden['id_filtro_orden'],
					//"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
					"cantidad_ordenes" => $arrayVentaAsesor[$indice]['cantidad_ordenes'],
					"total_ut" => $arrayVentaAsesor[$indice]['total_ut'],
					"total_mo" => $arrayVentaAsesor[$indice]['total_mo'],
					"total_repuestos" => $arrayVentaAsesor[$indice]['total_repuestos'],
					"total_tot" => $arrayVentaAsesor[$indice]['total_tot'],
					"total_asesor" => $arrayVentaAsesor[$indice]['total_asesor']);
				$totalVentaAsesores += $arrayVentaAsesor[$indice]['total_asesor'];
			}
		}
	}
	$arrayVentaAsesor = $arrayFiltroOrden;
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";

	$contFila = 0;
	if (isset($arrayVentaAsesor)) {
		foreach ($arrayVentaAsesor as $indice => $valor) {
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"8\" title=\"Id Empleado: ".$arrayVentaAsesor[$indice]['id_empleado']."\">".utf8_encode($arrayVentaAsesor[$indice]['nombre_asesor'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"26%\">Tipo Orden</td>";
				$htmlTb .= "<td width=\"8%\">Cant. O/R Cerradas</td>";
				$htmlTb .= "<td width=\"10%\">UT'S</td>";
				$htmlTb .= "<td width=\"10%\">M/Obra</td>";
				$htmlTb .= "<td width=\"10%\">Rptos.</td>";
				$htmlTb .= "<td width=\"10%\">T.O.T.</td>";
				$htmlTb .= "<td width=\"16%\">Total</td>";
				$htmlTb .= "<td width=\"10%\">%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaAsesor[$indice]['array_tipo_orden'])) {
				foreach ($arrayVentaAsesor[$indice]['array_tipo_orden'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcAsesor = ($valor2['total_asesor'] * 100) / $totalVentaAsesores;
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" title=\"Id Filtro Orden: ".$valor2['id_tipo_orden']."\">".utf8_encode($valor2['descripcion_tipo_orden'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['total_ut'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['total_mo'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['total_repuestos'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['total_tot'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['total_asesor'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".number_format($porcAsesor, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						utf8_encode($valor2['descripcion_tipo_orden']),
						round($porcAsesor,2)));
				}
			}
			
			$porcAsesor = ($arrayVentaAsesor[$indice]['total_asesor'] * 100) / $totalVentaAsesores;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaAsesor[$indice]['nombre_asesor']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['total_ut'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['total_mo'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['total_repuestos'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['total_tot'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['total_asesor'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcAsesor, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaAsesor[$indice]['nombre_asesor']),
				($totalVentaAsesores > 0) ? round($arrayVentaAsesor[$indice]['total_asesor'] * 100 / $totalVentaAsesores,2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$totalVentaOrden += $arrayVentaAsesor[$indice]['cantidad_ordenes'];
			$totalVentaUT += $arrayVentaAsesor[$indice]['total_ut'];
			$totalVentaMO += $arrayVentaAsesor[$indice]['total_mo'];
			$totalVentaRepuestos += $arrayVentaAsesor[$indice]['total_repuestos'];
			$totalVentaTot += $arrayVentaAsesor[$indice]['total_tot'];
			$porcTotalAsesor += $porcAsesor;
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total Facturación Asesores:</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaOrden, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaUT, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaMO, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaRepuestos, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaTot, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaAsesores, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcTotalAsesor, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">FACTURACIÓN ASESORES DE SERVICIOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						5,
						"Donut chart",
						"FACTURACIÓN ASESORES DE SERVICIOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Asesor",
						str_replace("'","|*|",$data1),
						"Facturación Tipo Orden",
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
	/*if (count($idCierreMensual) > 0 && $idCierreMensual != "-1") {
		$queryCierreFacturacion = sprintf("SELECT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			cierre_mensual_fact.id_tipo_orden,
			cierre_mensual_fact.cantidad_ordenes,
			cierre_mensual_fact.total_ut,
			cierre_mensual_fact.total_mano_obra,
			cierre_mensual_fact.total_tot,
			cierre_mensual_fact.total_repuesto
		FROM iv_cierre_mensual_facturacion cierre_mensual_fact
			INNER JOIN pg_empleado empleado ON (cierre_mensual_fact.id_empleado = empleado.id_empleado)
		WHERE cierre_mensual_fact.id_cierre_mensual IN (%s)
			AND cierre_mensual_fact.id_modulo IN (1)
			AND cierre_mensual_fact.id_tipo_orden IS NOT NULL;",
			valTpDato($idCierreMensual, "campo"));
		$rsCierreFacturacion = mysql_query($queryCierreFacturacion);
		if (!$rsCierreFacturacion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowCierreFacturacion = mysql_fetch_assoc($rsCierreFacturacion)) {
			$totalMoAsesor = $rowCierreFacturacion['total_mano_obra'];
			$totalTotAsesor = $rowCierreFacturacion['total_tot'];
			$totalRepuetosAsesor = $rowCierreFacturacion['total_repuesto'];
			
			$arrayVentaAsesor[] = array(
				"id_empleado" => $rowCierreFacturacion['id_empleado'],
				"nombre_mecanico" => $rowCierreFacturacion['nombre_empleado'],
				"id_equipo_mecanico" => $rowCierreFacturacion['id_equipo_mecanico'],
				"id_tipo_orden" => $rowCierreFacturacion['id_tipo_orden'],
				"cantidad_ordenes" => $rowCierreFacturacion['cantidad_ordenes'],
				"total_ut" => $rowCierreFacturacion['total_ut'],
				"total_mo" => $totalMoAsesor,
				"total_repuestos" => $totalRepuetosAsesor,
				"total_tot" => $totalTotAsesor,
				"total_asesor" => $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor);
			
			//$totalVentaAsesores += $totalMoAsesor + $totalRepuetosAsesor + $totalTotAsesor;
		}
	} else {*/
		$Result1 = facturacionTecnicos($idEmpresa, $valFecha[0], $valFecha[1]);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			$objResponse->alert($Result1[1]); return array(NULL, NULL);
		} else {
			$arrayEquipo = $Result1[1];
			$arrayMecanico = $Result1[2];
			$totalTotalUtsEquipos = $Result1[3];
			$totalTotalBsEquipos = $Result1[4];
		}
	//}
	
	// AGRUPA LOS TIPO DE ORDEN POR FILTRO DE ORDEN
	if (isset($arrayEquipo)) {
		foreach ($arrayEquipo as $indiceEquipo => $valorEquipo) {
			$arrayTecnico = $arrayEquipo[$indiceEquipo]['tecnicos'];
			
			$arrayFiltroOrden = NULL;
			if (isset($arrayTecnico)) {
				foreach ($arrayTecnico as $indiceTecnico => $valorTecnico) {
					$sqlBusq = "";
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq .= $cond.sprintf("(tipo_orden.id_tipo_orden = %s)",
						valTpDato($arrayTecnico[$indiceTecnico]['id_tipo_orden'], "int"));
					
					$queryFiltroOrden = sprintf("SELECT
						filtro_orden.id_filtro_orden,
						filtro_orden.descripcion
					FROM sa_tipo_orden tipo_orden
						INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s", $sqlBusq);
					$rsFiltroOrden = mysql_query($queryFiltroOrden);
					if (!$rsFiltroOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsFiltroOrden = mysql_num_rows($rsFiltroOrden);
					while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
						$existeEmpleado = false;
						$arrayDetalleFiltroOrden = NULL;
						if (isset($arrayFiltroOrden)) {
							foreach ($arrayFiltroOrden as $indiceFiltroOrden => $valorFiltroOrden) {
								if ($arrayFiltroOrden[$indiceFiltroOrden]['id_mecanico'] == $arrayTecnico[$indiceTecnico]['id_mecanico']
								&& $arrayFiltroOrden[$indiceFiltroOrden]['id_equipo_mecanico'] == $arrayTecnico[$indiceTecnico]['id_equipo_mecanico']) {
									$existeEmpleado = true;
									
									$arrayOrdenTecnico = $arrayFiltroOrden[$indiceFiltroOrden]['array_tipo_orden'];
									
									$existeFiltroOrden = false;
									$arrayDetalleFiltroOrden = NULL;
									if (isset($arrayOrdenTecnico)) {
										foreach ($arrayOrdenTecnico as $indiceOrdenTecnico => $valorOrdenTecnico) {
											$arrayDetalleFiltroOrden = $valorOrdenTecnico;
											if ($arrayDetalleFiltroOrden['id_filtro_orden'] == $rowFiltroOrden['id_filtro_orden']) {
												$existeFiltroOrden = true;
												
												$arrayDetalleFiltroOrden['cantidad_ordenes'] += round($arrayTecnico[$indiceTecnico]['cantidad_ordenes'],2);
												$arrayDetalleFiltroOrden['total_ut'] += round($arrayTecnico[$indiceTecnico]['total_ut'],2);
												$arrayDetalleFiltroOrden['total_mo'] += round($arrayTecnico[$indiceTecnico]['total_mo'],2);
												$arrayDetalleFiltroOrden['total_mecanico'] += round($arrayTecnico[$indiceTecnico]['total_mecanico'],2);
											}
											
											$arrayFiltroOrden[$indiceFiltroOrden]['array_tipo_orden'][$indiceOrdenTecnico]['cantidad_ordenes'] = $arrayDetalleFiltroOrden['cantidad_ordenes'];
											$arrayFiltroOrden[$indiceFiltroOrden]['array_tipo_orden'][$indiceOrdenTecnico]['total_ut'] = $arrayDetalleFiltroOrden['total_ut'];
											$arrayFiltroOrden[$indiceFiltroOrden]['array_tipo_orden'][$indiceOrdenTecnico]['total_mo'] = $arrayDetalleFiltroOrden['total_mo'];
											$arrayFiltroOrden[$indiceFiltroOrden]['array_tipo_orden'][$indiceOrdenTecnico]['total_mecanico'] = $arrayDetalleFiltroOrden['total_mecanico'];
										}
									}
									
									if ($existeFiltroOrden == false) {
										$arrayDetalleFiltroOrden = array(
											"id_tipo_orden" => $arrayTecnico[$indiceTecnico]['id_tipo_orden'],
											"id_filtro_orden" => $rowFiltroOrden['id_filtro_orden'],
											"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
											"cantidad_ordenes" => $arrayTecnico[$indiceTecnico]['cantidad_ordenes'],
											"total_ut" => $arrayTecnico[$indiceTecnico]['total_ut'],
											"total_mo" => $arrayTecnico[$indiceTecnico]['total_mo'],
											"total_mecanico" => $arrayTecnico[$indiceTecnico]['total_mecanico']);
									
										$arrayFiltroOrden[$indiceFiltroOrden]['array_tipo_orden'][] = $arrayDetalleFiltroOrden;
									}
									
									$arrayFiltroOrden[$indiceFiltroOrden]['cantidad_ordenes'] += $arrayTecnico[$indiceTecnico]['cantidad_ordenes'];
									$arrayFiltroOrden[$indiceFiltroOrden]['total_ut'] += $arrayTecnico[$indiceTecnico]['total_ut'];
									$arrayFiltroOrden[$indiceFiltroOrden]['total_mo'] += $arrayTecnico[$indiceTecnico]['total_mo'];
									$arrayFiltroOrden[$indiceFiltroOrden]['total_mecanico'] += $arrayTecnico[$indiceTecnico]['total_mecanico'];
								}
							}
						}
						
						if ($existeEmpleado == false) {
							$arrayDetalleFiltroOrden[] = array(
								"id_tipo_orden" => $arrayTecnico[$indiceTecnico]['id_tipo_orden'],
								"id_filtro_orden" => $rowFiltroOrden['id_filtro_orden'],
								"descripcion_tipo_orden" => $rowFiltroOrden['descripcion'],
								"cantidad_ordenes" => $arrayTecnico[$indiceTecnico]['cantidad_ordenes'],
								"total_ut" => $arrayTecnico[$indiceTecnico]['total_ut'],
								"total_mo" => $arrayTecnico[$indiceTecnico]['total_mo'],
								"total_mecanico" => $arrayTecnico[$indiceTecnico]['total_mecanico']);
							
							$arrayFiltroOrden[] = array(
								"id_empleado" => $arrayTecnico[$indiceTecnico]['id_empleado'],
								"id_mecanico" => $arrayTecnico[$indiceTecnico]['id_mecanico'],
								"nombre_mecanico" => $arrayTecnico[$indiceTecnico]['nombre_mecanico'],
								"activo" => $arrayTecnico[$indiceTecnico]['activo'],
								"id_equipo_mecanico" => $arrayTecnico[$indiceTecnico]['id_equipo_mecanico'],
								"array_tipo_orden" => $arrayDetalleFiltroOrden,
								"cantidad_ordenes" => $arrayTecnico[$indiceTecnico]['cantidad_ordenes'],
								"total_ut" => $arrayTecnico[$indiceTecnico]['total_ut'],
								"total_mo" => $arrayTecnico[$indiceTecnico]['total_mo'],
								"total_mecanico" => $arrayTecnico[$indiceTecnico]['total_mecanico']);
						}
						
						$totalTecnicos += $arrayTecnico[$indiceTecnico]['total_mecanico'];/**/
					}
					
					if (!($totalRowsFiltroOrden > 0)
					&& !($arrayTecnico[$indiceTecnico]['id_filtro_orden'] > 0)
					&& ($arrayTecnico[$indiceTecnico]['cantidad_ordenes'] > 0 || $arrayTecnico[$indiceTecnico]['total_mecanico'] > 0)) {
						$arrayFiltroOrden[] = array(
							"id_empleado" => $arrayTecnico[$indiceTecnico]['id_empleado'],
							"id_mecanico" => $arrayTecnico[$indiceTecnico]['id_mecanico'],
							"nombre_mecanico" => $arrayTecnico[$indiceTecnico]['nombre_mecanico'],
							"activo" => $arrayTecnico[$indiceTecnico]['activo'],
							"id_equipo_mecanico" => $arrayTecnico[$indiceTecnico]['id_equipo_mecanico'],
							"cantidad_ordenes" => $arrayTecnico[$indiceTecnico]['cantidad_ordenes'],
							"total_ut" => $arrayTecnico[$indiceTecnico]['total_ut'],
							"total_mo" => $arrayTecnico[$indiceTecnico]['total_mo'],
							"total_mecanico" => $arrayTecnico[$indiceTecnico]['total_mecanico']);
						$totalTecnicos += $arrayTecnico[$indiceTecnico]['total_mecanico'];
					}
				}
			}
			$arrayTecnico = $arrayFiltroOrden;
			
			$arrayEquipo[$indiceEquipo]['tecnicos'] = $arrayTecnico;
		}
	}
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	if (isset($arrayEquipo)) {
		foreach ($arrayEquipo as $indice => $valor) {
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"5\"><b>".ucwords(strtolower(utf8_encode($arrayEquipo[$indice]['nombre_equipo'])))."</b></td>";
			$htmlTb .= "</tr>";
			
			$arrayTecnico = $arrayEquipo[$indice]['tecnicos'];
			$porcTotalEquipo = 0;
			$arrayMec = NULL;
			if (isset($arrayTecnico)) {
				foreach ($arrayTecnico as $indiceTecnico => $valorTecnico) {
					$classEmpleado = ($valorTecnico['activo'] == 1) ? "class=\"tituloColumna\"" : "class=\"textoRojoNegrita\"";
					
					$htmlTb .= "<tr ".$classEmpleado." height=\"24\">";
						$htmlTb .= "<td colspan=\"5\" title=\"Id Empleado: ".$valorTecnico['id_empleado']."\nId Mecánico: ".$valorTecnico['id_mecanico']."\">".utf8_encode($valorTecnico['nombre_mecanico'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr class=\"tituloColumna\">";
						$htmlTb .= "<td width=\"44%\">Tipo Orden</td>";
						$htmlTb .= "<td width=\"8%\">Cant. O/R Cerradas</td>";
						$htmlTb .= "<td width=\"18%\">UT'S</td>";
						$htmlTb .= "<td width=\"18%\">".cAbrevMoneda."</td>";
						$htmlTb .= "<td width=\"12%\">%</td>";
					$htmlTb .= "</tr>";
					
					$arrayTecnicoOrden = $valorTecnico['array_tipo_orden'];
					if (isset($arrayTecnicoOrden)) {
						foreach ($arrayTecnicoOrden as $indiceTecnicoOrden => $valorTecnicoOrden) {
							$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
							$contFila++;
					
							$porcTecnicoOrden = ($valorTecnicoOrden['total_mo'] * 100) / $totalTotalBsEquipos;
							
							$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
								$htmlTb .= "<td align=\"left\" title=\"Id Filtro Orden: ".$valorTecnicoOrden['id_filtro_orden']."\nId Tipo Orden: ".$valorTecnicoOrden['id_tipo_orden']."\">".utf8_encode($valorTecnicoOrden['descripcion_tipo_orden'])."</td>";
								$htmlTb .= "<td>".valTpDato(number_format($valorTecnicoOrden['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
								$htmlTb .= "<td>".valTpDato(number_format($valorTecnicoOrden['total_ut'], 2, ".", ","),"cero_por_vacio")."</td>";
								$htmlTb .= "<td>".valTpDato(number_format($valorTecnicoOrden['total_mo'], 2, ".", ","),"cero_por_vacio")."</td>";
								$htmlTb .= "<td>".number_format($porcTecnicoOrden, 2, ".", ",")."%</td>";
							$htmlTb .= "</tr>";
						}
					}
					
					$porcTecnico = ($valorTecnico['total_mecanico'] * 100) / $totalTotalBsEquipos;
					
					$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
						$htmlTb .= "<td>Total Facturación ".utf8_encode($valorTecnico['nombre_mecanico']).":</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valorTecnico['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valorTecnico['total_ut'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valorTecnico['total_mo'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".number_format($porcTecnico, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$porcTotalEquipo += round($porcTecnico,2);
					
					$arrayMec[] = implode("+*+",array(
						utf8_encode($valorTecnico['nombre_mecanico']),
						round($porcTecnico,2)));
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".ucwords(strtolower(utf8_encode($arrayEquipo[$indice]['nombre_equipo']))).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayEquipo[$indice]['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayEquipo[$indice]['total_ut'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayEquipo[$indice]['total_mo'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcTotalEquipo, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayEquipo[$indice]['nombre_equipo']),
				($totalTotalBsEquipos > 0) ? round($arrayEquipo[$indice]['total_mo'] * 100 / $totalTotalBsEquipos,2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
				
			$arrayTotalFactTecnicos['cantidad_ordenes'] += $arrayEquipo[$indice]['cantidad_ordenes'];
			$arrayTotalFactTecnicos['total_ut'] += $arrayEquipo[$indice]['total_ut'];
			$arrayTotalFactTecnicos['total_mo'] += $arrayEquipo[$indice]['total_mo'];
			$arrayTotalFactTecnicos[2] += $porcTotalEquipo;
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
		
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total Facturación Técnicos:</td>";
		$htmlTb .= "<td>".valTpDato(number_format($arrayTotalFactTecnicos['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($arrayTotalFactTecnicos['total_ut'], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($arrayTotalFactTecnicos['total_mo'], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalFactTecnicos[2], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">FACTURACIÓN TÉCNICOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						6,
						"Donut chart",
						"FACTURACIÓN TÉCNICOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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

function facturacionVendedoresRepuestos($objResponse, $idEmpresa, $valFecha, $idCierreMensual) {
	global $mes;
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// FACTURACIÓN VENDEDORES DE REPUESTOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	if (count($idCierreMensual) > 0 && $idCierreMensual != "-1") {
		$queryCierreFacturacion = sprintf("SELECT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			cierre_mensual_fact.total_facturacion_contado,
			cierre_mensual_fact.total_facturacion_credito,
			cierre_mensual_fact.total_devolucion_contado,
			cierre_mensual_fact.total_devolucion_credito
		FROM iv_cierre_mensual_facturacion cierre_mensual_fact
			INNER JOIN pg_empleado empleado ON (cierre_mensual_fact.id_empleado = empleado.id_empleado)
		WHERE cierre_mensual_fact.id_cierre_mensual IN (%s)
			AND cierre_mensual_fact.id_modulo IN (0);",
			valTpDato($idCierreMensual, "campo"));
		$rsCierreFacturacion = mysql_query($queryCierreFacturacion);
		if (!$rsCierreFacturacion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowCierreFacturacion = mysql_fetch_assoc($rsCierreFacturacion)) {
			$totalFacturacionContado = $rowCierreFacturacion['total_facturacion_contado'];
			$totalFacturacionCredito = $rowCierreFacturacion['total_facturacion_credito'];
			$totalDevolucionContado = $rowCierreFacturacion['total_devolucion_contado'];
			$totalDevolucionCredito = $rowCierreFacturacion['total_devolucion_credito'];
			
			$arrayVentaVendedor[] = array(
				$rowCierreFacturacion['id_empleado'],
				$rowCierreFacturacion['nombre_empleado'],
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
	} else {
		$Result1 = facturacionMostrador($idEmpresa, $valFecha[0], $valFecha[1]);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayVentaVendedor = $Result1[1];
			$totalVentaVendedores = $Result1[2];
		}
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
			
			$porcVendedor = ($arrayVentaVendedor[$indice][8] * 100) / $totalVentaVendedores;

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayVentaVendedor[$indice][1])."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaVendedor[$indice][6], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaVendedor[$indice][7], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaVendedor[$indice][8], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcVendedor, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";

			$data1 .= (strlen($data1) > 0) ? "['".utf8_encode($arrayVentaVendedor[$indice][1])."', ".$arrayVentaVendedor[$indice][8]."]," : "{ name: '".utf8_encode($arrayVentaVendedor[$indice][1])."', y: ".$arrayVentaVendedor[$indice][8].", sliced: true, selected: true },";
			
			$totalVentaContado += $arrayVentaVendedor[$indice][6];
			$totalVentaCredito += $arrayVentaVendedor[$indice][7];
			$porcTotalVendedor += $porcVendedor;
		}
	}
	$data1 = substr($data1, 0, (strlen($data1)-1));
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Total Facturación Vendedores:")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaContado, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaCredito, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalVentaVendedores, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcTotalVendedor, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">FACTURACIÓN VENDEDORES DE REPUESTOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						7,
						"Pie with legend",
						"FACTURACIÓN VENDEDORES DE REPUESTOS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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
	$valFecha[0] = date("m", strtotime("01-".$frmBuscar['txtFecha']));
	$valFecha[1] = date("Y", strtotime("01-".$frmBuscar['txtFecha']));
	
	mysql_query("SET GLOBAL innodb_stats_on_metadata = 0;");
	
	$sqlBusq = " ";
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = (SELECT emp.id_empresa
															FROM pg_empresa emp
																LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
															ORDER BY emp.id_empresa_padre ASC
															LIMIT 1)");
	}
	
	// VERIFICA VALORES DE CONFIGURACION (Incluir Notas en el Informe Gerencial)
	$queryConfig300 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 300 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig300 = mysql_query($queryConfig300);
	if (!$rsConfig300) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig300 = mysql_num_rows($rsConfig300);
	$rowConfig300 = mysql_fetch_assoc($rsConfig300);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Producción Taller))
	$queryConfig301 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 301 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig301 = mysql_query($queryConfig301);
	if (!$rsConfig301) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig301 = mysql_num_rows($rsConfig301);
	$rowConfig301 = mysql_fetch_assoc($rsConfig301);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Producción Otros))
	$queryConfig304 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 304 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig304 = mysql_query($queryConfig304);
	if (!$rsConfig304) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig304 = mysql_num_rows($rsConfig304);
	$rowConfig304 = mysql_fetch_assoc($rsConfig304);
	
	$htmlMsj = "<table width=\"100%\">";
	$htmlMsj .= "<tr>";
		$htmlMsj .= "<td>";
			$htmlMsj .= "<p style=\"font-size:24px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; text-shadow:3px 3px 0 rgba(51,51,51,0.8);\">";
				$htmlMsj .= "<span style=\"display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;\">".$mes[intval($valFecha[0])]." ".$valFecha[1]."</span>";
				/*$htmlMsj .= "<br>";
				$htmlMsj .= "<span style=\"font-size:18px; display:inline-block; text-transform:uppercase; color:#B7D154; padding-left:2px;\">Versión 3.0</span>";*/
			$htmlMsj .= "</p>";
		$htmlMsj .= "</td>";
	$htmlMsj .= "</tr>";
	
	// BUSCA LOS DATOS DEL CIERRE MENSUAL
	$query = sprintf("SELECT cierre_mensual.*,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
	FROM iv_cierre_mensual cierre_mensual
		INNER JOIN pg_empleado empleado ON (cierre_mensual.id_empleado_creador = empleado.id_empleado)
	WHERE (cierre_mensual.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cierre_mensual.id_empresa))
		AND cierre_mensual.mes = %s
		AND cierre_mensual.ano = %s;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($valFecha[0], "int"),
		valTpDato($valFecha[1], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$idCierreMensual[] = $row['id_cierre_mensual'];
		
		$htmlMsj .= "<tr>";
			$htmlMsj .= "<td>";
				$htmlMsj .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo\" width=\"100%\">";
				$htmlMsj .= "<tr>";
					$htmlMsj .= "<td height=\"25\" width=\"25\"><img src=\"../img/iconos/ico_info2.gif\" width=\"24\"/></td>";
					$htmlMsj .= "<td align=\"center\">";
						$htmlMsj .= utf8_encode("Cierre generado el ".date(spanDateFormat, strtotime($row['fecha_creacion']))." a las ".date("h:i:s a", strtotime($row['fecha_creacion']))."<br>por ".$row['nombre_empleado']);
					$htmlMsj .= "</td>";
				$htmlMsj .= "</tr>";
				$htmlMsj .= "</table>";
			$htmlMsj .= "</td>";
		$htmlMsj .= "</tr>";
	}
	$htmlMsj .= "</table>";
	
	$objResponse->assign("divMsjCierre","innerHTML",$htmlMsj);
	
	$idCierreMensual = (isset($idCierreMensual)) ? implode(",",$idCierreMensual) : "-1";
	$objResponse->assign("hddIdCierreMensual","value",$idCierreMensual);
	
	analisisInventario($objResponse, $idEmpresa, $valFecha);
	cantidadItemsVendidos($objResponse, $idEmpresa, $valFecha);
	comprasRepuestos($objResponse, $idEmpresa, $valFecha);
	ventasRepuestosMostrador($objResponse, $idEmpresa, $valFecha);
	ventasRepuestosServicios($objResponse, $idEmpresa, $valFecha);
	facturacionAsesoresServicios($objResponse, $idEmpresa, $valFecha, $idCierreMensual);
	$Result1 = facturacionTecnicosServicios($objResponse, $idEmpresa, $valFecha);
	$arrayMecanico = $Result1[0];
	$totalTotalUtsEquipos = $Result1[1];
	facturacionVendedoresRepuestos($objResponse, $idEmpresa, $valFecha, $idCierreMensual);
	
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
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipo_orden.orden_generica = 0");
	
	if (strlen($rowConfig301['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("filtro_orden.id_filtro_orden IN (%s)",
			valTpDato($rowConfig301['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(tipo_orden.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = tipo_orden.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	// TIPOS DE ORDENES
	$sqlTiposOrden = sprintf("SELECT filtro_orden.*
	FROM sa_tipo_orden tipo_orden
		INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
	GROUP BY filtro_orden.id_filtro_orden", $sqlBusq);
	$rsTiposOrden = mysql_query($sqlTiposOrden);
	if (!$rsTiposOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayProdTipoOrden = array();
	while ($rowTipoOrden = mysql_fetch_assoc($rsTiposOrden)) {
		$arrayProdTipoOrden[$rowTipoOrden['id_filtro_orden']] = array(
			"id_filtro_orden" => $rowTipoOrden['id_filtro_orden'],
			"nombre" => $rowTipoOrden['descripcion'],
			"mostrar_tipo_orden" => false);
	}
	
	// TIPOS DE MANO DE OBRA
	$sqlOperadores = "SELECT * FROM sa_operadores ORDER BY id_operador";
	$rsOperadores = mysql_query($sqlOperadores);
	if (!$rsOperadores) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayOperador = array();
	while ($rowOperadores = mysql_fetch_assoc($rsOperadores)) {
		$arrayOperador[$rowOperadores['id_operador']] = $rowOperadores['descripcion_operador'];
	}
	$idTot = count($arrayOperador) + 1;
	$arrayOperador[$idTot] = "Trabajos Otros Talleres";
	
	// TIPOS DE ARTICULOS
	$sqlTiposArticulos = "SELECT * FROM iv_tipos_articulos ORDER BY id_tipo_articulo";
	$rsTiposArticulos = mysql_query($sqlTiposArticulos);
	if (!$rsTiposArticulos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayTipoArticulo = array();
	while ($rowTipoArticulo = mysql_fetch_assoc($rsTiposArticulos)) {
		$arrayTipoArticulo[$rowTipoArticulo['id_tipo_articulo']] = $rowTipoArticulo['descripcion'];
	}
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig301['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig301['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// SOLO APLICA PARA LAS MANO DE OBRA
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("a.estado_tempario IN ('FACTURADO','TERMINADO')");
	
	// MANO DE OBRAS FACTURAS DE SERVICIOS
	$queryMOFact = sprintf("SELECT
		a.id_filtro_orden,
		a.id_tipo_orden,
		a.operador,
		
		(CASE a.id_modo
			WHEN 1 THEN -- UT
				(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
			WHEN 2 THEN -- PRECIO
				a.precio
		END) AS total_tempario_orden
		
	FROM sa_v_informe_final_tempario a %s %s;", $sqlBusq, $sqlBusq2);
	$rsMOFact = mysql_query($queryMOFact);
	if (!$rsMOFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayMO = array();
	$arrayTotalMOTipoOrden = array();
	$arrayTotalMOOperador = array();
	while ($rowMOFact = mysql_fetch_assoc($rsMOFact)) {
		$valor = $rowMOFact['total_tempario_orden'];
		
		$arrayMO[$rowMOFact['operador']][$rowMOFact['id_filtro_orden']] += $valor;

		$arrayTotalMOTipoOrden[$rowMOFact['id_filtro_orden']] += $valor;
		$arrayTotalMOOperador[$rowMOFact['operador']] += $valor;
	}
	
	// MANO DE OBRAS NOTAS DE CREDITO DE SERVICIOS
	$queryMONotaCred = sprintf("SELECT
		a.id_filtro_orden,
		a.id_tipo_orden,
		a.operador,
		
		(CASE a.id_modo
			WHEN 1 THEN -- UT
				(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
			WHEN 2 THEN -- PRECIO
				a.precio
		END) AS total_tempario_dev_orden
		
	FROM sa_v_informe_final_tempario_dev a %s %s;", $sqlBusq, $sqlBusq2);
	$rsMONotaCred = mysql_query($queryMONotaCred);
	if (!$rsMONotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowMONotaCred = mysql_fetch_assoc($rsMONotaCred)) {
		$valor = $rowMONotaCred['total_tempario_dev_orden'];
		
		$arrayMO[$rowMONotaCred['operador']][$rowMONotaCred['id_filtro_orden']] -= $valor;

		$arrayTotalMOTipoOrden[$rowMONotaCred['id_filtro_orden']] -= $valor;
		$arrayTotalMOOperador[$rowMONotaCred['operador']] -= $valor;
	}
	
	// MANO DE OBRAS VALE DE SALIDA DE SERVICIOS
	$queryMOValeSal = sprintf("SELECT
		a.id_filtro_orden,
		a.id_tipo_orden,
		a.operador,
		
		(CASE a.id_modo
			WHEN 1 THEN -- UT
				(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
			WHEN 2 THEN -- PRECIO
				a.precio
		END) AS total_tempario_vale
		
	FROM sa_v_vale_informe_final_tempario a %s %s;", $sqlBusq, $sqlBusq2);
	$rsMOValeSal = mysql_query($queryMOValeSal);
	if (!$rsMOValeSal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowMOValeSal = mysql_fetch_assoc($rsMOValeSal)) {
		$valor = $rowMOValeSal['total_tempario_vale'];
		
		$arrayMO[$rowMOValeSal['operador']][$rowMOValeSal['id_filtro_orden']] += $valor;

		$arrayTotalMOTipoOrden[$rowMOValeSal['id_filtro_orden']] += $valor;
		$arrayTotalMOOperador[$rowMOValeSal['operador']] += $valor;
	}
	
	// MANO DE OBRAS VALE DE ENTRADA DE SERVICIOS
	$queryMOValeEnt = sprintf("SELECT
		a.id_filtro_orden,
		a.id_tipo_orden,
		a.operador,
		
		(CASE a.id_modo
			WHEN 1 THEN -- UT
				(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
			WHEN 2 THEN -- PRECIO
				a.precio
		END) AS total_tempario_vale
		
	FROM sa_v_vale_informe_final_tempario_dev a %s %s;", $sqlBusq, $sqlBusq2);
	$rsMOValeEnt = mysql_query($queryMOValeEnt);
	if (!$rsMOValeEnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowMOValeEnt = mysql_fetch_assoc($rsMOValeEnt)) {
		$valor = $rowMOValeEnt['total_tempario_vale'];
		
		$arrayMO[$rowMOValeEnt['operador']][$rowMOValeEnt['id_filtro_orden']] -= $valor;

		$arrayTotalMOTipoOrden[$rowMOValeEnt['id_filtro_orden']] -= $valor;
		$arrayTotalMOOperador[$rowMOValeEnt['operador']] -= $valor;
	}
	
	
	// TOT FACTURAS DE SERVICIOS
	$queryTotFact = sprintf("SELECT * FROM sa_v_informe_final_tot a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsTotFact = mysql_query($queryTotFact);
	if (!$rsTotFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotFact = mysql_fetch_assoc($rsTotFact)) {
		$valor = $rowTotFact['monto_total'] + (($rowTotFact['porcentaje_tot'] * $rowTotFact['monto_total']) / 100);

		$arrayMO[$idTot][$rowTotFact['id_filtro_orden']] += $valor;

		$arrayTotalMOTipoOrden[$rowTotFact['id_filtro_orden']] += $valor;
		$arrayTotalMOOperador[$idTot] += $valor;
	}
	
	// TOT NOTAS DE CREDITO DE SERVICIOS
	$queryTotNotaCred = sprintf("SELECT * FROM sa_v_informe_final_tot_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsTotNotaCred = mysql_query($queryTotNotaCred);
	if (!$rsTotNotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotNotaCred = mysql_fetch_assoc($rsTotNotaCred)) {
		$valor = $rowTotNotaCred['monto_total'] + (($rowTotNotaCred['porcentaje_tot'] * $rowTotNotaCred['monto_total']) / 100);

		$arrayMO[$idTot][$rowTotNotaCred['id_filtro_orden']] -= $valor;

		$arrayTotalMOTipoOrden[$rowTotNotaCred['id_filtro_orden']] -= $valor;
		$arrayTotalMOOperador[$idTot] -= $valor;
	}
	
	// TOT VALE DE SALIDA DE SERVICIOS
	$queryTotValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_tot a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsTotValeSal = mysql_query($queryTotValeSal);
	if (!$rsTotValeSal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotValeSal = mysql_fetch_assoc($rsTotValeSal)) {
		$valor = $rowTotValeSal['monto_total'] + (($rowTotValeSal['porcentaje_tot'] * $rowTotValeSal['monto_total']) / 100);

		$arrayMO[$idTot][$rowTotValeSal['id_filtro_orden']] += $valor;
		
		$arrayTotalMOTipoOrden[$rowTotValeSal['id_filtro_orden']] += $valor;
		$arrayTotalMOOperador[$idTot] += $valor;
	}
	
	// TOT VALE DE ENTRADA DE SERVICIOS
	$queryTotValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_tot_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsTotValeEnt = mysql_query($queryTotValeEnt);
	if (!$rsTotValeEnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotValeEnt = mysql_fetch_assoc($rsTotValeEnt)) {
		$valor = $rowTotValeEnt['monto_total'] + (($rowTotValeEnt['porcentaje_tot'] * $rowTotValeEnt['monto_total']) / 100);

		$arrayMO[$idTot][$rowTotValeEnt['id_filtro_orden']] -= $valor;
		
		$arrayTotalMOTipoOrden[$rowTotValeEnt['id_filtro_orden']] -= $valor;
		$arrayTotalMOOperador[$idTot] -= $valor;
	}
	
	
	// REPUESTOS FACTURAS DE SERVICIOS
	$queryRepFA = sprintf("SELECT * FROM sa_v_informe_final_repuesto a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsRepFA = mysql_query($queryRepFA);
	if (!$rsRepFA) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayRepuesto = array();
	$arrayTotalRepuestoTipo = array();
	$arrayTotalRepuestoTipoOrden = array();
	$arrayTotalDescuentoRepuestoTipoOrden = array();
	$arrayTotalItemRepuestoTipoOrden = array();
	while ($rowRepFA = mysql_fetch_assoc($rsRepFA)) {
		$valor = $rowRepFA['precio_unitario'] * $rowRepFA['cantidad'];

		$desc = (($valor * $rowRepFA['porcentaje_descuento_orden']) / 100);

		$arrayRepuesto[$rowRepFA['id_tipo_articulo']][$rowRepFA['id_filtro_orden']] += $valor;

		$arrayTotalRepuestoTipo[$rowRepFA['id_tipo_articulo']] += $valor;
		$arrayTotalRepuestoTipoOrden[$rowRepFA['id_filtro_orden']] += $valor;
		$arrayTotalDescuentoRepuestoTipoOrden[$rowRepFA['id_filtro_orden']] += $desc;
		
		$arrayTotalItemRepuestoTipoOrden[$rowRepFA['id_filtro_orden']]['items_factura'] += 1;
		$arrayTotalItemRepuestoTipoOrden[$rowRepFA['id_filtro_orden']]['cantidad_articulo_factura'] += $rowRepFA['cantidad'];
	}
	
	// REPUESTOS NOTAS DE CREDITO DE SERVICIOS
	$queryRepNC = sprintf("SELECT * FROM sa_v_informe_final_repuesto_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsRepNC = mysql_query($queryRepNC);
	if (!$rsRepNC) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowRepNC = mysql_fetch_assoc($rsRepNC)) {
		$valor = $rowRepNC['precio_unitario'] * $rowRepNC['cantidad'];

		$desc = (($valor * $rowRepNC['porcentaje_descuento_orden']) / 100);

		$arrayRepuesto[$rowRepNC['id_tipo_articulo']][$rowRepNC['id_filtro_orden']] -= $valor;

		$arrayTotalRepuestoTipo[$rowRepNC['id_tipo_articulo']] -= $valor;
		$arrayTotalRepuestoTipoOrden[$rowRepNC['id_filtro_orden']] -= $valor;
		$arrayTotalDescuentoRepuestoTipoOrden[$rowRepNC['id_filtro_orden']] -= $desc;
		
		$arrayTotalItemRepuestoTipoOrden[$rowRepNC['id_filtro_orden']]['items_nota_credito'] += 1;
		$arrayTotalItemRepuestoTipoOrden[$rowRepNC['id_filtro_orden']]['cantidad_articulo_nota_credito'] += $rowRepNC['cantidad'];
	}
	
	// REPUESTOS VALE DE SALIDA DE SERVICIOS
	$queryRepVS = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsRepVS = mysql_query($queryRepVS);
	if (!$rsRepVS) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowRepVS = mysql_fetch_assoc($rsRepVS)) {
		$valor = $rowRepVS['precio_unitario'] * $rowRepVS['cantidad'];

		$desc = (($valor * $rowRepVS['porcentaje_descuento_orden']) / 100);

		$arrayRepuesto[$rowRepVS['id_tipo_articulo']][$rowRepVS['id_filtro_orden']] += $valor;

		$arrayTotalRepuestoTipo[$rowRepVS['id_tipo_articulo']] += $valor;
		$arrayTotalRepuestoTipoOrden[$rowRepVS['id_filtro_orden']] += $valor;
		$arrayTotalDescuentoRepuestoTipoOrden[$rowRepVS['id_filtro_orden']] += $desc;
		
		$arrayTotalItemRepuestoTipoOrden[$rowRepVS['id_filtro_orden']]['items_vale_salida'] += 1;
		$arrayTotalItemRepuestoTipoOrden[$rowRepVS['id_filtro_orden']]['cantidad_articulo_vale_salida'] += $rowRepVS['cantidad'];
	}
	
	// REPUESTOS VALE DE ENTRADA DE SERVICIOS
	$queryRepVE = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsRepVE = mysql_query($queryRepVE);
	if (!$rsRepVE) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowRepVE = mysql_fetch_assoc($rsRepVE)) {
		$valor = $rowRepVE['precio_unitario'] * $rowRepVE['cantidad'];
		
		$desc = (($valor * $rowRepVE['porcentaje_descuento_orden']) / 100);
		
		$arrayRepuesto[$rowRepVE['id_tipo_articulo']][$rowRepVE['id_filtro_orden']] -= $valor;
		
		$arrayTotalRepuestoTipo[$rowRepVE['id_tipo_articulo']] -= $valor;
		$arrayTotalRepuestoTipoOrden[$rowRepVE['id_filtro_orden']] -= $valor;
		$arrayTotalDescuentoRepuestoTipoOrden[$rowRepVE['id_filtro_orden']] -= $desc;
		
		$arrayTotalItemRepuestoTipoOrden[$rowRepVE['id_filtro_orden']]['items_vale_entrada'] += 1;
		$arrayTotalItemRepuestoTipoOrden[$rowRepVE['id_filtro_orden']]['cantidad_articulo_vale_entrada'] += $rowRepVE['cantidad'];
	}
	
	
	// NOTAS FACTURAS DE SERVICIOS
	$queryNotaFact = sprintf("SELECT * FROM sa_v_informe_final_notas a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsNotaFact = mysql_query($queryNotaFact);
	if (!$rsNotaFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayTotalNotaTipoOrden = array();
	$totalNota = 0;
	while ($rowNotaFact = mysql_fetch_assoc($rsNotaFact)) {
		$valor = $rowNotaFact['precio'];
		
		$arrayTotalNotaTipoOrden[$rowNotaFact['id_filtro_orden']] += $valor;
		$totalNota += $valor;
	}
	
	// NOTAS NOTAS DE CREDITO DE SERVICIOS
	$queryNotaNotaCred = sprintf("SELECT * FROM sa_v_informe_final_notas_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsNotaNotaCred = mysql_query($queryNotaNotaCred);
	if (!$rsNotaNotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowNotaNotaCred = mysql_fetch_assoc($rsNotaNotaCred)) {
		$valor = $rowNotaNotaCred['precio'];
		
		$arrayTotalNotaTipoOrden[$rowNotaNotaCred['id_filtro_orden']] -= $valor;
		$totalNota -= $valor;
	}
	
	// NOTAS VALE DE SALIDA DE SERVICIOS
	$queryNotaValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_notas a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsNotaValeSal = mysql_query($queryNotaValeSal);
	if (!$rsNotaValeSal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowNotaValeSal = mysql_fetch_assoc($rsNotaValeSal)) {
		$valor = $rowNotaValeSal['precio'];
		
		$arrayTotalNotaTipoOrden[$rowNotaValeSal['id_filtro_orden']] += $valor;
		$totalNota += $valor;
	}
	
	// NOTAS VALE DE ENTRADA DE SERVICIOS
	$queryNotaValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_notas_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsNotaValeEnt = mysql_query($queryNotaValeEnt);
	if (!$rsNotaValeEnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowNotaValeEnt = mysql_fetch_assoc($rsNotaValeEnt)) {
		$valor = $rowNotaValeEnt['precio'];
		
		$arrayTotalNotaTipoOrden[$rowNotaValeEnt['id_filtro_orden']] -= $valor;
		$totalNota -= $valor;
	}
	
	// CALCULO DEL TOTAL
	(count($arrayTotalMOTipoOrden) > 0) ? "" : $arrayTotalMOTipoOrden[0] = 0;
	(count($arrayTotalRepuestoTipoOrden) > 0) ? "" : $arrayTotalRepuestoTipoOrden[0] = 0;
	(count($arrayTotalNotaTipoOrden) > 0) ? "" : $arrayTotalNotaTipoOrden[0] = 0;
	(count($arrayTotalRepuestoDescuentoTipoOrden) > 0) ? "" : $arrayTotalRepuestoDescuentoTipoOrden[0] = 0;
	
	$totalProdTaller = array_sum($arrayTotalMOTipoOrden) + array_sum($arrayTotalRepuestoTipoOrden) - array_sum($arrayTotalDescuentoRepuestoTipoOrden);
	$totalProdTaller += ($rowConfig300['valor'] == 1) ? array_sum($arrayTotalNotaTipoOrden) : 0;
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"20%\">Conceptos</td>";
	if (isset($arrayProdTipoOrden)) {
		foreach ($arrayProdTipoOrden as $indiceProdTipoOrden => $valorProdTipoOrden) {
			$htmlTh .= "<td title=\"Id Filtro Orden: ".$valorProdTipoOrden['id_filtro_orden']."\" width=\"10%\">".$valorProdTipoOrden['nombre']."</td>";
		}
	}
		$htmlTh .= "<td>Total</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// MANO DE OBRA
	$porcMO = 0;
	if (isset($arrayOperador)) {
		foreach ($arrayOperador as $idOperador => $operador) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcOperador = ($totalProdTaller > 0) ? ($arrayTotalMOOperador[$idOperador] * 100) / $totalProdTaller : 0;
			$porcMO += $porcOperador;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($operador)."</td>";
			if (isset($arrayProdTipoOrden)) {
				foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
					$htmlTb .= "<td>".valTpDato(number_format(((isset($arrayMO[$idOperador][$idTipo])) ? $arrayMO[$idOperador][$idTipo] : 0), 2, ".", ","),"cero_por_vacio")."</td>";
				}
			}
				$htmlTb .= "<td>".valTpDato(number_format($arrayTotalMOOperador[$idOperador], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcOperador, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
		}
	}

	// TOTAL DE LA MANO DE OBRA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Subtotal Mano de Obra:</td>";
	$subTotalMo = 0;
	if (isset($arrayProdTipoOrden)) {
		foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
			$subTotalMo += $arrayTotalMOTipoOrden[$idTipo];
			
			$htmlTb .= "<td>".valTpDato(number_format($arrayTotalMOTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
		}
	}
		$htmlTb .= "<td>".valTpDato(number_format($subTotalMo, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcMO, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// REPUESTOS
	$porcRepuestos = 0;
	if (isset($arrayTipoArticulo)) {
		foreach ($arrayTipoArticulo as $idTipoArticulo => $tipoArticulo) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			if ($arrayTotalRepuestoTipo[$idTipoArticulo] != 0) {
				$porcTipoRepuestos = ($totalProdTaller > 0) ? ($arrayTotalRepuestoTipo[$idTipoArticulo] * 100) / $totalProdTaller : 0;
				$porcRepuestos += $porcTipoRepuestos;
			
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\">".utf8_encode($tipoArticulo)."</td>";
				if (isset($arrayProdTipoOrden)) {
					foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
						$htmlTb .= "<td>".valTpDato(number_format($arrayRepuesto[$idTipoArticulo][$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
					}
				}
					$htmlTb .= "<td>".valTpDato(number_format($arrayTotalRepuestoTipo[$idTipoArticulo], 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "<td>".number_format($porcTipoRepuestos, 2, ".", ",")."%</td>";
				$htmlTb .= "</tr>";
			}
		}
	}

	// TOTAL DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Repuestos:</td>";
	$subTotalRepServ = 0;
	if (isset($arrayProdTipoOrden)) {
		foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
			$subTotalRepServ += $arrayTotalRepuestoTipoOrden[$idTipo];
			
			$htmlTb .= "<td>".valTpDato(number_format($arrayTotalRepuestoTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
		}
	}
		$htmlTb .= "<td>".valTpDato(number_format($subTotalRepServ, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcRepuestos, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// TOTAL DE DESCUENTO DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Descuento Repuestos:</td>";
	$totalDescuentoRepServ = 0;
	if (isset($arrayProdTipoOrden)) {
		foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
			$totalDescuentoRepServ += $arrayTotalDescuentoRepuestoTipoOrden[$idTipo];
			
			$htmlTb .= "<td>".valTpDato(number_format((-1)*$arrayTotalDescuentoRepuestoTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
		}
	}
	$porcDescuentoRepServ = ($totalProdTaller > 0) ? ($totalDescuentoRepServ * 100) / $totalProdTaller : 0;
		$htmlTb .= "<td>".valTpDato(number_format((-1)*$totalDescuentoRepServ, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format((-1)*$porcDescuentoRepServ, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	// TOTAL FINAL DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Subtotal Repuestos:</td>";
	if (isset($arrayProdTipoOrden)) {
		foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
			$htmlTb .= "<td title=\"".
				"Total Articulos Factura: ".$arrayTotalItemRepuestoTipoOrden[$idTipo]['cantidad_articulo_factura']."\n".
				"Total Articulos Nota de Crédito: ".$arrayTotalItemRepuestoTipoOrden[$idTipo]['cantidad_articulo_nota_credito']."\n".
				"Total Articulos Vale de Salida: ".$arrayTotalItemRepuestoTipoOrden[$idTipo]['cantidad_articulo_vale_salida']."\n".
				"Total Articulos Vale de Entrada: ".$arrayTotalItemRepuestoTipoOrden[$idTipo]['cantidad_articulo_vale_entrada']."\n".
				"\n".
				"Total Items Factura: ".$arrayTotalItemRepuestoTipoOrden[$idTipo]['items_factura']."\n".
				"Total Items Nota de Crédito: ".$arrayTotalItemRepuestoTipoOrden[$idTipo]['items_nota_credito']."\n".
				"Total Items Vale de Salida: ".$arrayTotalItemRepuestoTipoOrden[$idTipo]['items_vale_salida']."\n".
				"Total Items Vale de Entrada: ".$arrayTotalItemRepuestoTipoOrden[$idTipo]['items_vale_entrada']."\n"."\">";
				$htmlTb .= valTpDato(number_format($arrayTotalRepuestoTipoOrden[$idTipo] - $arrayTotalDescuentoRepuestoTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio");
			$htmlTb .= "</td>";
		}
	}
		$htmlTb .= "<td>".valTpDato(number_format($subTotalRepServ - $totalDescuentoRepServ, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcRepuestos - $porcDescuentoRepServ, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// TOTAL DE NOTAS
	if ($rowConfig300['valor'] == 1) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">Subtotal Notas:</td>";
		if (isset($arrayProdTipoOrden)) {
			foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
				$htmlTb .= "<td>".valTpDato(number_format($arrayTotalNotaTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
			}
		}
		$porcNota = ($totalProdTaller > 0) ? ($totalNota * 100) / $totalProdTaller : 0;
			$htmlTb .= "<td>".valTpDato(number_format($totalNota, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".number_format($porcNota, 2, ".", ",")."%</td>";
		$htmlTb .= "</tr>";
	}

	// TOTAL SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total Producción Taller:</td>";
	$totalTipoOrden = 0;
	if (isset($arrayProdTipoOrden)) {
		foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
			$totalTipoOrden = $arrayTotalMOTipoOrden[$idTipo] + $arrayTotalRepuestoTipoOrden[$idTipo] - $arrayTotalDescuentoRepuestoTipoOrden[$idTipo];
			$totalTipoOrden += ($rowConfig300['valor'] == 1) ? $arrayTotalNotaTipoOrden[$idTipo] : 0;
			$porcTotalTipoOrden[$idTipo] = ($totalProdTaller > 0) ? ($totalTipoOrden * 100) / $totalProdTaller : 0;
	
			$htmlTb .= "<td>".valTpDato(number_format($totalTipoOrden, 2, ".", ","),"cero_por_vacio")."</td>";
			
			$data1 .= (strlen($data1) > 0) ? "['".utf8_encode($tipo['nombre'])."', ".$totalTipoOrden."]," : "{ name: '".utf8_encode($tipo['nombre'])."', y: ".$totalTipoOrden.", sliced: true, selected: true },";
		}
	}
	$porcTotalProdTaller = $porcMO + $porcRepuestos - $porcDescuentoRepServ;
	$porcTotalProdTaller += ($rowConfig300['valor'] == 1) ? $porcNota : 0;
		$htmlTb .= "<td>".valTpDato(number_format($totalProdTaller, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcTotalProdTaller, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	$data1 = substr($data1, 0, (strlen($data1)-1));

	// PARTICIPACION
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">% Participación</td>";
	$porcentajeTotal = 0;
	if (isset($arrayProdTipoOrden)) {
		foreach ($arrayProdTipoOrden as $idTipo => $tipo) {
			$porcentajeTotal += $porcTotalTipoOrden[$idTipo];
			$htmlTb .= "<td>".number_format($porcTotalTipoOrden[$idTipo], 2, ".", ",")."%</td>";
		}
	}
		$htmlTb .= "<td>".number_format($porcentajeTotal, 2, ".", ",")."%</td>";
		$htmlTb .= "<td></td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"".(count($arrayProdTipoOrden) + 3)."\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">PRODUCCIÓN TALLER (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						8,
						"Pie with legend",
						"PRODUCCIÓN TALLER (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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
	// PRODUCCION OTROS
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
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipo_orden.orden_generica = 0");
	
	if (strlen($rowConfig304['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("filtro_orden.id_filtro_orden IN (%s)",
			valTpDato($rowConfig304['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(tipo_orden.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = tipo_orden.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	// TIPOS DE ORDENES
	$sqlTiposOrden = sprintf("SELECT filtro_orden.*
	FROM sa_tipo_orden tipo_orden
		INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s
	GROUP BY filtro_orden.id_filtro_orden", $sqlBusq);
	$rsTiposOrden = mysql_query($sqlTiposOrden);
	if (!$rsTiposOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayOtroProdTipoOrden = array();
	while ($rowTipoOrden = mysql_fetch_assoc($rsTiposOrden)) {
		$arrayOtroProdTipoOrden[$rowTipoOrden['id_filtro_orden']] = array(
			"id_filtro_orden" => $rowTipoOrden['id_filtro_orden'],
			"nombre" => $rowTipoOrden['descripcion'],
			"mostrar_tipo_orden" => false);
	}
	
	// TIPOS DE MANO DE OBRA
	$sqlOperadores = "SELECT * FROM sa_operadores ORDER BY id_operador";
	$rsOperadores = mysql_query($sqlOperadores);
	if (!$rsOperadores) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayOtroOperador = array();
	while ($rowOperadores = mysql_fetch_assoc($rsOperadores)) {
		$arrayOtroOperador[$rowOperadores['id_operador']] = $rowOperadores['descripcion_operador'];
	}
	$idTot = count($arrayOtroOperador) + 1;
	$arrayOtroOperador[$idTot] = "Trabajos Otros Talleres";
	
	// TIPOS DE ARTICULOS
	$sqlTiposArticulos = "SELECT * FROM iv_tipos_articulos ORDER BY id_tipo_articulo";
	$rsTiposArticulos = mysql_query($sqlTiposArticulos);
	if (!$rsTiposArticulos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayOtroTipoArticulo = array();
	while ($rowTipoArticulo = mysql_fetch_assoc($rsTiposArticulos)) {
		$arrayOtroTipoArticulo[$rowTipoArticulo['id_tipo_articulo']] = $rowTipoArticulo['descripcion'];
	}
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig304['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig304['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}

	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// SOLO APLICA PARA LAS MANO DE OBRA
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("a.estado_tempario IN ('FACTURADO','TERMINADO')");
	
	// MANO DE OBRAS FACTURAS DE SERVICIOS
	$queryMOFact = sprintf("SELECT
		a.id_filtro_orden,
		a.id_tipo_orden,
		a.operador,
		
		(CASE a.id_modo
			WHEN 1 THEN -- UT
				(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
			WHEN 2 THEN -- PRECIO
				a.precio
		END) AS total_tempario_orden
		
	FROM sa_v_informe_final_tempario a %s %s;", $sqlBusq, $sqlBusq2);
	$rsMOFact = mysql_query($queryMOFact);
	if (!$rsMOFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayOtroMO = array();
	$arrayOtroTotalMOTipoOrden = array();
	$arrayOtroTotalMOOperador = array();
	while ($rowMOFact = mysql_fetch_assoc($rsMOFact)) {
		$valor = $rowMOFact['total_tempario_orden'];
		
		$arrayOtroMO[$rowMOFact['operador']][$rowMOFact['id_filtro_orden']] += $valor;

		$arrayOtroTotalMOTipoOrden[$rowMOFact['id_filtro_orden']] += $valor;
		$arrayOtroTotalMOOperador[$rowMOFact['operador']] += $valor;
	}
	
	// MANO DE OBRAS NOTAS DE CREDITO DE SERVICIOS
	$queryMONotaCred = sprintf("SELECT
		a.id_filtro_orden,
		a.id_tipo_orden,
		a.operador,
		
		(CASE a.id_modo
			WHEN 1 THEN -- UT
				(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
			WHEN 2 THEN -- PRECIO
				a.precio
		END) AS total_tempario_dev_orden
		
	FROM sa_v_informe_final_tempario_dev a %s %s;", $sqlBusq, $sqlBusq2);
	$rsMONotaCred = mysql_query($queryMONotaCred);
	if (!$rsMONotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowMONotaCred = mysql_fetch_assoc($rsMONotaCred)) {
		$valor = $rowMONotaCred['total_tempario_dev_orden'];
		
		$arrayOtroMO[$rowMONotaCred['operador']][$rowMONotaCred['id_filtro_orden']] -= $valor;

		$arrayOtroTotalMOTipoOrden[$rowMONotaCred['id_filtro_orden']] -= $valor;
		$arrayOtroTotalMOOperador[$rowMONotaCred['operador']] -= $valor;
	}
	
	// MANO DE OBRAS VALE DE SALIDA DE SERVICIOS
	$queryMOValeSal = sprintf("SELECT
		a.id_filtro_orden,
		a.id_tipo_orden,
		a.operador,
		
		(CASE a.id_modo
			WHEN 1 THEN -- UT
				(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
			WHEN 2 THEN -- PRECIO
				a.precio
		END) AS total_tempario_vale
		
	FROM sa_v_vale_informe_final_tempario a %s %s;", $sqlBusq, $sqlBusq2);
	$rsMOValeSal = mysql_query($queryMOValeSal);
	if (!$rsMOValeSal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowMOValeSal = mysql_fetch_assoc($rsMOValeSal)) {
		$valor = $rowMOValeSal['total_tempario_vale'];
		
		$arrayOtroMO[$rowMOValeSal['operador']][$rowMOValeSal['id_filtro_orden']] += $valor;

		$arrayOtroTotalMOTipoOrden[$rowMOValeSal['id_filtro_orden']] += $valor;
		$arrayOtroTotalMOOperador[$rowMOValeSal['operador']] += $valor;
	}
	
	// MANO DE OBRAS VALE DE ENTRADA DE SERVICIOS
	$queryMOValeEnt = sprintf("SELECT
		a.id_filtro_orden,
		a.id_tipo_orden,
		a.operador,
		
		(CASE a.id_modo
			WHEN 1 THEN -- UT
				(a.ut * a.precio_tempario_tipo_orden) / a.base_ut_precio
			WHEN 2 THEN -- PRECIO
				a.precio
		END) AS total_tempario_vale
		
	FROM sa_v_vale_informe_final_tempario_dev a %s %s;", $sqlBusq, $sqlBusq2);
	$rsMOValeEnt = mysql_query($queryMOValeEnt);
	if (!$rsMOValeEnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowMOValeEnt = mysql_fetch_assoc($rsMOValeEnt)) {
		$valor = $rowMOValeEnt['total_tempario_vale'];
		
		$arrayOtroMO[$rowMOValeEnt['operador']][$rowMOValeEnt['id_filtro_orden']] -= $valor;

		$arrayOtroTotalMOTipoOrden[$rowMOValeEnt['id_filtro_orden']] -= $valor;
		$arrayOtroTotalMOOperador[$rowMOValeEnt['operador']] -= $valor;
	}
	
	
	// TOT FACTURAS DE SERVICIOS
	$queryTotFact = sprintf("SELECT * FROM sa_v_informe_final_tot a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsTotFact = mysql_query($queryTotFact);
	if (!$rsTotFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotFact = mysql_fetch_assoc($rsTotFact)) {
		$valor = $rowTotFact['monto_total'] + (($rowTotFact['porcentaje_tot'] * $rowTotFact['monto_total']) / 100);

		$arrayOtroMO[$idTot][$rowTotFact['id_filtro_orden']] += $valor;

		$arrayOtroTotalMOTipoOrden[$rowTotFact['id_filtro_orden']] += $valor;
		$arrayOtroTotalMOOperador[$idTot] += $valor;
	}
	
	// TOT NOTAS DE CREDITO DE SERVICIOS
	$queryTotNotaCred = sprintf("SELECT * FROM sa_v_informe_final_tot_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsTotNotaCred = mysql_query($queryTotNotaCred);
	if (!$rsTotNotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotNotaCred = mysql_fetch_assoc($rsTotNotaCred)) {
		$valor = $rowTotNotaCred['monto_total'] + (($rowTotNotaCred['porcentaje_tot'] * $rowTotNotaCred['monto_total']) / 100);

		$arrayOtroMO[$idTot][$rowTotNotaCred['id_filtro_orden']] -= $valor;

		$arrayOtroTotalMOTipoOrden[$rowTotNotaCred['id_filtro_orden']] -= $valor;
		$arrayOtroTotalMOOperador[$idTot] -= $valor;
	}
	
	// TOT VALE DE SALIDA DE SERVICIOS
	$queryTotValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_tot a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsTotValeSal = mysql_query($queryTotValeSal);
	if (!$rsTotValeSal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotValeSal = mysql_fetch_assoc($rsTotValeSal)) {
		$valor = $rowTotValeSal['monto_total'] + (($rowTotValeSal['porcentaje_tot'] * $rowTotValeSal['monto_total']) / 100);

		$arrayOtroMO[$idTot][$rowTotValeSal['id_filtro_orden']] += $valor;
		
		$arrayOtroTotalMOTipoOrden[$rowTotValeSal['id_filtro_orden']] += $valor;
		$arrayOtroTotalMOOperador[$idTot] += $valor;
	}
	
	// TOT VALE DE ENTRADA DE SERVICIOS
	$queryTotValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_tot_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsTotValeEnt = mysql_query($queryTotValeEnt);
	if (!$rsTotValeEnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowTotValeEnt = mysql_fetch_assoc($rsTotValeEnt)) {
		$valor = $rowTotValeEnt['monto_total'] + (($rowTotValeEnt['porcentaje_tot'] * $rowTotValeEnt['monto_total']) / 100);

		$arrayOtroMO[$idTot][$rowTotValeEnt['id_filtro_orden']] -= $valor;
		
		$arrayOtroTotalMOTipoOrden[$rowTotValeEnt['id_filtro_orden']] -= $valor;
		$arrayOtroTotalMOOperador[$idTot] -= $valor;
	}
	
	
	// REPUESTOS FACTURAS DE SERVICIOS
	$queryRepFA = sprintf("SELECT * FROM sa_v_informe_final_repuesto a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsRepFA = mysql_query($queryRepFA);
	if (!$rsRepFA) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayOtroRepuesto = array();
	$arrayOtroTotalRepuestoTipo = array();
	$arrayOtroTotalRepuestoTipoOrden = array();
	$arrayOtroTotalDescuentoRepuestoTipoOrden = array();
	while ($rowRepFA = mysql_fetch_assoc($rsRepFA)) {
		$valor = $rowRepFA['precio_unitario'] * $rowRepFA['cantidad'];

		$desc = (($valor * $rowRepFA['porcentaje_descuento_orden']) / 100);

		$arrayOtroRepuesto[$rowRepFA['id_tipo_articulo']][$rowRepFA['id_filtro_orden']] += $valor;

		$arrayOtroTotalRepuestoTipo[$rowRepFA['id_tipo_articulo']] += $valor;
		$arrayOtroTotalRepuestoTipoOrden[$rowRepFA['id_filtro_orden']] += $valor;
		$arrayOtroTotalDescuentoRepuestoTipoOrden[$rowRepFA['id_filtro_orden']] += $desc;
	}
	
	// REPUESTOS NOTAS DE CREDITO DE SERVICIOS
	$queryRepNC = sprintf("SELECT * FROM sa_v_informe_final_repuesto_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsRepNC = mysql_query($queryRepNC);
	if (!$rsRepNC) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowRepNC = mysql_fetch_assoc($rsRepNC)) {
		$valor = $rowRepNC['precio_unitario'] * $rowRepNC['cantidad'];

		$desc = (($valor * $rowRepNC['porcentaje_descuento_orden']) / 100);

		$arrayOtroRepuesto[$rowRepNC['id_tipo_articulo']][$rowRepNC['id_filtro_orden']] -= $valor;

		$arrayOtroTotalRepuestoTipo[$rowRepNC['id_tipo_articulo']] -= $valor;
		$arrayOtroTotalRepuestoTipoOrden[$rowRepNC['id_filtro_orden']] -= $valor;
		$arrayOtroTotalDescuentoRepuestoTipoOrden[$rowRepNC['id_filtro_orden']] -= $desc;
	}
	
	// REPUESTOS VALE DE SALIDA DE SERVICIOS
	$queryRepVS = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsRepVS = mysql_query($queryRepVS);
	if (!$rsRepVS) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowRepVS = mysql_fetch_assoc($rsRepVS)) {
		$valor = $rowRepVS['precio_unitario'] * $rowRepVS['cantidad'];

		$desc = (($valor * $rowRepVS['porcentaje_descuento_orden']) / 100);

		$arrayOtroRepuesto[$rowRepVS['id_tipo_articulo']][$rowRepVS['id_filtro_orden']] += $valor;

		$arrayOtroTotalRepuestoTipo[$rowRepVS['id_tipo_articulo']] += $valor;
		$arrayOtroTotalRepuestoTipoOrden[$rowRepVS['id_filtro_orden']] += $valor;
		$arrayOtroTotalDescuentoRepuestoTipoOrden[$rowRepVS['id_filtro_orden']] += $desc;
	}
	
	// REPUESTOS VALE DE ENTRADA DE SERVICIOS
	$queryRepVE = sprintf("SELECT * FROM sa_v_vale_informe_final_repuesto_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsRepVE = mysql_query($queryRepVE);
	if (!$rsRepVE) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowRepVE = mysql_fetch_assoc($rsRepVE)) {
		$valor = $rowRepVE['precio_unitario'] * $rowRepVE['cantidad'];

		$desc = (($valor * $rowRepVE['porcentaje_descuento_orden']) / 100);

		$arrayOtroRepuesto[$rowRepVE['id_tipo_articulo']][$rowRepVE['id_filtro_orden']] -= $valor;

		$arrayOtroTotalRepuestoTipo[$rowRepVE['id_tipo_articulo']] -= $valor;
		$arrayOtroTotalRepuestoTipoOrden[$rowRepVE['id_filtro_orden']] -= $valor;
		$arrayOtroTotalDescuentoRepuestoTipoOrden[$rowRepVE['id_filtro_orden']] -= $desc;
	}
	
	
	// NOTAS FACTURAS DE SERVICIOS
	$queryNotaFact = sprintf("SELECT * FROM sa_v_informe_final_notas a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsNotaFact = mysql_query($queryNotaFact);
	if (!$rsNotaFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayOtroTotalNotaTipoOrden = array();
	$totalNota = 0;
	while ($rowNotaFact = mysql_fetch_assoc($rsNotaFact)) {
		$valor = $rowNotaFact['precio'];
		
		$arrayOtroTotalNotaTipoOrden[$rowNotaFact['id_filtro_orden']] += $valor;
		$totalNota += $valor;
	}
	
	// NOTAS NOTAS DE CREDITO DE SERVICIOS
	$queryNotaNotaCred = sprintf("SELECT * FROM sa_v_informe_final_notas_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsNotaNotaCred = mysql_query($queryNotaNotaCred);
	if (!$rsNotaNotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowNotaNotaCred = mysql_fetch_assoc($rsNotaNotaCred)) {
		$valor = $rowNotaNotaCred['precio'];
		
		$arrayOtroTotalNotaTipoOrden[$rowNotaNotaCred['id_filtro_orden']] -= $valor;
		$totalNota -= $valor;
	}
	
	// NOTAS VALE DE SALIDA DE SERVICIOS
	$queryNotaValeSal = sprintf("SELECT * FROM sa_v_vale_informe_final_notas a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsNotaValeSal = mysql_query($queryNotaValeSal);
	if (!$rsNotaValeSal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowNotaValeSal = mysql_fetch_assoc($rsNotaValeSal)) {
		$valor = $rowNotaValeSal['precio'];
		
		$arrayOtroTotalNotaTipoOrden[$rowNotaValeSal['id_filtro_orden']] += $valor;
		$totalNota += $valor;
	}
	
	// NOTAS VALE DE ENTRADA DE SERVICIOS
	$queryNotaValeEnt = sprintf("SELECT * FROM sa_v_vale_informe_final_notas_dev a %s ORDER BY id_filtro_orden;", $sqlBusq);
	$rsNotaValeEnt = mysql_query($queryNotaValeEnt);
	if (!$rsNotaValeEnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowNotaValeEnt = mysql_fetch_assoc($rsNotaValeEnt)) {
		$valor = $rowNotaValeEnt['precio'];
		
		$arrayOtroTotalNotaTipoOrden[$rowNotaValeEnt['id_filtro_orden']] -= $valor;
		$totalNota -= $valor;
	}
	
	// CALCULO DEL TOTAL
	(count($arrayOtroTotalMOTipoOrden) > 0) ? "" : $arrayOtroTotalMOTipoOrden[0] = 0;
	(count($arrayOtroTotalRepuestoTipoOrden) > 0) ? "" : $arrayOtroTotalRepuestoTipoOrden[0] = 0;
	(count($arrayOtroTotalNotaTipoOrden) > 0) ? "" : $arrayOtroTotalNotaTipoOrden[0] = 0;
	(count($arrayTotalRepuestoDescuentoTipoOrden) > 0) ? "" : $arrayTotalRepuestoDescuentoTipoOrden[0] = 0;
	
	$totalProdOtro = array_sum($arrayOtroTotalMOTipoOrden) + array_sum($arrayOtroTotalRepuestoTipoOrden) - array_sum($arrayTotalRepuestoDescuentoTipoOrden);
	$totalProdOtro += ($rowConfig300['valor'] == 1) ? array_sum($arrayOtroTotalNotaTipoOrden) : 0;
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"20%\">Conceptos</td>";
	if (isset($arrayOtroProdTipoOrden)) {
		foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
			$htmlTh .= "<td title=\"Id Filtro Orden: ".$idTipo."\" width=\"10%\">".$tipo['nombre']."</td>";
		}
	}
		$htmlTh .= "<td>Total</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// MANO DE OBRA
	$porcMO = 0;
	if (isset($arrayOtroOperador)) {
		foreach ($arrayOtroOperador as $idOperador => $operador) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcOperador = ($totalProdOtro > 0) ? ($arrayOtroTotalMOOperador[$idOperador] * 100) / $totalProdOtro : 0;
			$porcMO += $porcOperador;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($operador)."</td>";
			if (isset($arrayOtroProdTipoOrden)) {
				foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
					$htmlTb .= "<td>".valTpDato(number_format(((isset($arrayOtroMO[$idOperador][$idTipo])) ? $arrayOtroMO[$idOperador][$idTipo] : 0), 2, ".", ","),"cero_por_vacio")."</td>";
				}
			}
				$htmlTb .= "<td>".valTpDato(number_format($arrayOtroTotalMOOperador[$idOperador], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcOperador, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
		}
	}

	// TOTAL DE LA MANO DE OBRA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Subtotal Mano de Obra:</td>";
	$subTotalMoOtro = 0;
	if (isset($arrayOtroProdTipoOrden)) {
		foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
			$subTotalMoOtro += $arrayOtroTotalMOTipoOrden[$idTipo];
			
			$htmlTb .= "<td>".valTpDato(number_format($arrayOtroTotalMOTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
		}
	}
		$htmlTb .= "<td>".valTpDato(number_format($subTotalMoOtro, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcMO, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	// REPUESTOS
	$porcRepuestos = 0;
	if (isset($arrayOtroTipoArticulo)) {
		foreach ($arrayOtroTipoArticulo as $idTipoArticulo => $tipoArticulo) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			if ($arrayOtroTotalRepuestoTipo[$idTipoArticulo] != 0) {
				$porcTipoRepuestos = ($totalProdOtro > 0) ? ($arrayOtroTotalRepuestoTipo[$idTipoArticulo] * 100) / $totalProdOtro : 0;
				$porcRepuestos += $porcTipoRepuestos;
			
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\">".utf8_encode($tipoArticulo)."</td>";
				if (isset($arrayOtroProdTipoOrden)) {
					foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
						$htmlTb .= "<td>".valTpDato(number_format($arrayOtroRepuesto[$idTipoArticulo][$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
					}
				}
					$htmlTb .= "<td>".valTpDato(number_format($arrayOtroTotalRepuestoTipo[$idTipoArticulo], 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "<td>".number_format($porcTipoRepuestos, 2, ".", ",")."%</td>";
				$htmlTb .= "</tr>";
			}
		}
	}

	// TOTAL DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Repuestos:</td>";
	$subTotalOtroRepServ = 0;
	if (isset($arrayOtroProdTipoOrden)) {
		foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
			$subTotalOtroRepServ += $arrayOtroTotalRepuestoTipoOrden[$idTipo];
			
			$htmlTb .= "<td>".valTpDato(number_format($arrayOtroTotalRepuestoTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
		}
	}
		$htmlTb .= "<td>".valTpDato(number_format($subTotalOtroRepServ, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcRepuestos, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// TOTAL DE DESCUENTO DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Descuento Repuestos:</td>";
	$totalDescuentoOtroRepServ = 0;
	if (isset($arrayOtroProdTipoOrden)) {
		foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
			$totalDescuentoOtroRepServ = $arrayOtroTotalDescuentoRepuestoTipoOrden[$idTipo];
			
			$htmlTb .= "<td>".valTpDato(number_format((-1)*$arrayOtroTotalDescuentoRepuestoTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
		}
	}
	$porcDescuentoRepServ = ($totalProdOtro > 0) ? ($totalDescuentoOtroRepServ * 100) / $totalProdOtro : 0;
		$htmlTb .= "<td>".valTpDato(number_format((-1)*$totalDescuentoOtroRepServ, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format((-1)*$porcDescuentoRepServ, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// TOTAL FINAL DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Subtotal Repuestos:</td>";
	if (isset($arrayOtroProdTipoOrden)) {
		foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
			$htmlTb .= "<td>".valTpDato(number_format($arrayOtroTotalRepuestoTipoOrden[$idTipo] - $arrayOtroTotalDescuentoRepuestoTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
		}
	}
		$htmlTb .= "<td>".valTpDato(number_format($subTotalOtroRepServ - $totalDescuentoOtroRepServ, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcRepuestos - $porcDescuentoRepServ, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// TOTAL DE NOTAS
	if ($rowConfig300['valor'] == 1) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">Subtotal Notas:</td>";
		if (isset($arrayOtroProdTipoOrden)) {
			foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
				$htmlTb .= "<td>".valTpDato(number_format($arrayOtroTotalNotaTipoOrden[$idTipo], 2, ".", ","),"cero_por_vacio")."</td>";
			}
		}
		$porcNota = ($totalProdOtro > 0) ? ($totalNota * 100) / $totalProdOtro : 0;
			$htmlTb .= "<td>".valTpDato(number_format($totalNota, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".number_format($porcNota, 2, ".", ",")."%</td>";
		$htmlTb .= "</tr>";
	}

	// TOTAL SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">Total Producción Taller:</td>";
	$totalTipoOrden = 0;
	if (isset($arrayOtroProdTipoOrden)) {
		foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
			$totalTipoOrden = $arrayOtroTotalMOTipoOrden[$idTipo] + $arrayOtroTotalRepuestoTipoOrden[$idTipo] - $arrayOtroTotalDescuentoRepuestoTipoOrden[$idTipo];
			$totalTipoOrden += ($rowConfig300['valor'] == 1) ? $arrayOtroTotalNotaTipoOrden[$idTipo] : 0;
			$porcTotalTipoOrden[$idTipo] = ($totalProdOtro > 0) ? ($totalTipoOrden * 100) / $totalProdOtro : 0;
	
			$htmlTb .= "<td>".valTpDato(number_format($totalTipoOrden, 2, ".", ","),"cero_por_vacio")."</td>";
			
			$data1 .= (strlen($data1) > 0) ? "['".utf8_encode($tipo['nombre'])."', ".$totalTipoOrden."]," : "{ name: '".utf8_encode($tipo['nombre'])."', y: ".$totalTipoOrden.", sliced: true, selected: true },";
		}
	}
	$porcTotalProdTaller = $porcMO + $porcRepuestos - $porcDescuentoRepServ;
	$porcTotalProdTaller += ($rowConfig300['valor'] == 1) ? $porcNota : 0;
		$htmlTb .= "<td>".valTpDato(number_format($totalProdOtro, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format($porcTotalProdTaller, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	$data1 = substr($data1, 0, (strlen($data1)-1));

	// PARTICIPACION
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">% Participación</td>";
	$porcentajeTotal = 0;
	if (isset($arrayOtroProdTipoOrden)) {
		foreach ($arrayOtroProdTipoOrden as $idTipo => $tipo) {
			$porcentajeTotal += $porcTotalTipoOrden[$idTipo];
			$htmlTb .= "<td>".number_format($porcTotalTipoOrden[$idTipo], 2, ".", ",")."%</td>";
		}
	}
		$htmlTb .= "<td>".number_format($porcentajeTotal, 2, ".", ",")."%</td>";
		$htmlTb .= "<td></td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"".(count($arrayOtroProdTipoOrden) + 3)."\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">PRODUCCIÓN OTROS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						8,
						"Pie with legend",
						"PRODUCCIÓN OTROS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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
	
	$objResponse->assign("divListaProduccionOtros","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
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
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (0)
	AND cxc_fact.aplicaLibros = 1");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (0)
	AND cxc_nc.tipoDocumento LIKE 'FA'
	AND cxc_nc.aplicaLibros = 1
	AND cxc_nc.estatus_nota_credito = 2");
	
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
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) = %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) = %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// PRODUCCIÓN REPUESTOS MOSTRADOR
	$query = sprintf("SELECT
		cxc_fact.condicionDePago,
		(cxc_fact.subtotalFactura - IFNULL(cxc_fact.descuentoFactura, 0)) AS neto
	FROM cj_cc_encabezadofactura cxc_fact %s
		
	UNION ALL
	
	SELECT
		cxc_fact2.condicionDePago,
		((-1)*(cxc_nc.subtotalNotaCredito - IFNULL(cxc_nc.subtotal_descuento, 0))) AS neto
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_encabezadofactura cxc_fact2 ON (cxc_nc.idDocumento = cxc_fact2.idFactura) %s;",
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
		$htmlTh .= "<td colspan=\"14\">"."<p class=\"textoAzul\">PRODUCCIÓN REPUESTOS MOSTRADOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
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
		$htmlTb .= "<td>".valTpDato(number_format(0, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format(0, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format(0, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(0, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	// MOSTRADOR PUBLICO
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Mostrador Público"."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMost[0], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMost[1], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format(array_sum($arrayVentaMost), 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(((array_sum($arrayVentaMost) > 0) ? array_sum($arrayVentaMost) * 100 / (array_sum($arrayVentaMost)) : 0), 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	// TOTAL REPUESTOS MOSTRADOR
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."Total Producción Repuestos Mostrador:"."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMost[0], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMost[1], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format(array_sum($arrayVentaMost), 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";

	// PARTICIPACION
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">% Participación</td>";
		$htmlTb .= "<td>".number_format(((array_sum($arrayVentaMost) > 0) ? $arrayVentaMost[0] * 100 / array_sum($arrayVentaMost) : 0), 2, ".", ",")."%</td>";
		$htmlTb .= "<td>".number_format(((array_sum($arrayVentaMost) > 0) ? $arrayVentaMost[1] * 100 / array_sum($arrayVentaMost) : 0), 2, ".", ",")."%</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
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
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig301['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
			valTpDato($rowConfig301['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
		AND YEAR(a.fecha_filtro) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// COSTO DE VENTAS REPUESTOS POR SERVICIOS Y LATONERIA Y PINTURA
	$query2 = sprintf("SELECT
		SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
	FROM (
		SELECT (costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_informe_final_repuesto a %s
		
		UNION ALL
		
		SELECT (-1)*(costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_informe_final_repuesto_dev a %s
		
		UNION ALL
		
		SELECT (costo_unitario * cantidad) AS total_costo_repuesto_orden FROM sa_v_vale_informe_final_repuesto a %s) AS query",
		$sqlBusq,
		$sqlBusq,
		$sqlBusq);
	$rs2 = mysql_query($query2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row2 = mysql_fetch_assoc($rs2);
	$arrayCostoRepServ[0] = round($row2['total_costo_repuesto_orden'],2);
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (0)
	AND cxc_fact.aplicaLibros = 1");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (0)
	AND cxc_nc.tipoDocumento LIKE 'FA'
	AND cxc_nc.aplicaLibros = 1
	AND cxc_nc.estatus_nota_credito = 2");
	
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
	
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) = %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) = %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// COSTO DE VENTAS REPUESTOS
	$query = sprintf("SELECT
		(SELECT SUM((cxc_fact_det.cantidad * cxc_fact_det.costo_compra)) AS costo_total
		FROM cj_cc_factura_detalle cxc_fact_det
		WHERE cxc_fact_det.id_factura = cxc_fact.idFactura) AS neto
	FROM cj_cc_encabezadofactura cxc_fact %s
		
	UNION ALL
	
	SELECT
		((-1)*(SELECT SUM((cxc_nc_det.cantidad * cxc_nc_det.costo_compra)) AS costo_total
		FROM cj_cc_nota_credito_detalle cxc_nc_det
		WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito)) AS neto
	FROM cj_cc_notacredito cxc_nc %s;",
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
		$htmlTh .= "<td colspan=\"14\">"."<p class=\"textoAzul\">MARGEN DE REPUESTOS POR SERVICIOS Y MOSTRADOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
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
		$htmlTb .= "<td>".valTpDato(number_format($arrayCostoRepServ[0], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format((($subTotalRepServ - $totalDescuentoRepServ) - $arrayCostoRepServ[0]), 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(((($subTotalRepServ - $totalDescuentoRepServ) > 0) ? ((($subTotalRepServ - $totalDescuentoRepServ) - $arrayCostoRepServ[0]) * 100) / ($subTotalRepServ - $totalDescuentoRepServ) : 0), 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	// REPUESTOS POR MOSTRADOR
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Repuestos por Mostrador"."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($arrayCostoRepMost[0], 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format((array_sum($arrayVentaMost) - $arrayCostoRepMost[0]), 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(((array_sum($arrayVentaMost) > 0) ? ((array_sum($arrayVentaMost) - $arrayCostoRepMost[0]) * 100) / array_sum($arrayVentaMost) : 0), 2, ".", ",")."%</td>";
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
	
	$totalFacturacionPostVenta = $totalProdTaller + $totalProdOtro + array_sum($arrayVentaMost);
	
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
		$htmlTb .= "<td>".valTpDato(number_format($totalProdTaller, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format((($totalFacturacionPostVenta > 0) ? $totalProdTaller * 100 / $totalFacturacionPostVenta : 0), 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	$data1 .= (strlen($data1) > 0) ? "['".utf8_encode("Total Servicios")."', ".$totalProdTaller."]," : "{ name: '".utf8_encode("Total Servicios")."', y: ".$totalProdTaller.", sliced: true, selected: true },";
	
	// TOTAL REPUESTOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Total Repuestos"."</td>";
		$htmlTb .= "<td>".valTpDato(number_format(array_sum($arrayVentaMost), 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format((($totalFacturacionPostVenta > 0) ? array_sum($arrayVentaMost) * 100 / $totalFacturacionPostVenta : 0), 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	$data1 .= (strlen($data1) > 0) ? "['".utf8_encode("Total Repuestos")."', ".array_sum($arrayVentaMost)."]," : "{ name: '".utf8_encode("Total Repuestos")."', y: ".array_sum($arrayVentaMost).", sliced: true, selected: true },";
	
	// TOTAL OTROS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td align=\"left\">"."Total Otros"."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalProdOtro, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format((($totalFacturacionPostVenta > 0) ? $totalProdOtro * 100 / $totalFacturacionPostVenta : 0), 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	$data1 .= (strlen($data1) > 0) ? "['".utf8_encode("Total Servicios")."', ".$totalProdOtro."]," : "{ name: '".utf8_encode("Total Servicios")."', y: ".$totalProdOtro.", sliced: true, selected: true },";
	
	$data1 = substr($data1, 0, (strlen($data1)-1));
	
	// TOTAL
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."Total Facturación:"."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalFacturacionPostVenta, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".number_format(100, 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">TOTAL FACTURACIÓN (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						9,
						"Pie with legend",
						"TOTAL FACTURACIÓN (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
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
		$sqlBusq .= $cond.sprintf("(recepcion.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = recepcion.id_empresa))",
			valTpDato($idEmpresa, "int"),
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
	
	// TIPO VALE RECEPCION
	$queryTipoValeRecepcion = sprintf("SELECT tipo_vale.* FROM sa_tipo_vale tipo_vale");
	$rsTipoValeRecepcion = mysql_query($queryTipoValeRecepcion);
	if (!$rsTipoValeRecepcion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsTipoValeRecepcion = mysql_num_rows($rsTipoValeRecepcion);
	while($rowTipoValeRecepcion = mysql_fetch_assoc($rsTipoValeRecepcion)) {
		$arrayValeRecepcion[$rowTipoValeRecepcion['id_tipo_vale']][0] = $rowTipoValeRecepcion['descripcion'];
	}
	
	// ENTRADA DE VEHICULOS
	$queryValeRecepcion = sprintf("SELECT recepcion.* FROM sa_recepcion recepcion %s", $sqlBusq);
	$rsValeRecepcion = mysql_query($queryValeRecepcion);
	if (!$rsValeRecepcion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsValeRecepcion = mysql_num_rows($rsValeRecepcion);
	while($rowValeRecepcion = mysql_fetch_assoc($rsValeRecepcion)) {
		$arrayValeRecepcion[$rowValeRecepcion['id_tipo_vale']][1] += 1;
	}
	
	if (count($idCierreMensual) > 0 && $idCierreMensual != "-1") {
		$queryCierreMensualOrden = sprintf("SELECT
			filtro_orden.id_filtro_orden,
			tipo_orden.id_tipo_orden,
			tipo_orden.nombre_tipo_orden,
			cierre_mensual_orden.cantidad_abiertas,
			cierre_mensual_orden.cantidad_cerradas,
			cierre_mensual_orden.cantidad_fallas_abiertas,
			cierre_mensual_orden.cantidad_fallas_cerradas,
			cierre_mensual_orden.cantidad_uts_cerradas
		FROM iv_cierre_mensual_orden cierre_mensual_orden
			INNER JOIN sa_tipo_orden tipo_orden ON (cierre_mensual_orden.id_tipo_orden = tipo_orden.id_tipo_orden)
			INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden)
		WHERE cierre_mensual_orden.id_cierre_mensual IN (%s)
		ORDER BY filtro_orden.descripcion;",
			valTpDato($idCierreMensual, "campo"));
		$rsCierreMensualOrden = mysql_query($queryCierreMensualOrden);
		if (!$rsCierreMensualOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowCierreMensualOrden = mysql_fetch_assoc($rsCierreMensualOrden)) {
			$arrayTipoOrden[$rowCierreMensualOrden['id_tipo_orden']] = array(
				"id_filtro_orden" => $rowCierreMensualOrden['id_filtro_orden'],
				"id_tipo_orden" => $rowCierreMensualOrden['id_tipo_orden'],
				"nombre_tipo_orden" => $rowCierreMensualOrden['nombre_tipo_orden'],
				"cantidad_abiertas" => $rowCierreMensualOrden['cantidad_abiertas'],
				"cantidad_cerradas" => $rowCierreMensualOrden['cantidad_cerradas'],
				"cantidad_fallas_abiertas" => $rowCierreMensualOrden['cantidad_fallas_abiertas'],
				"cantidad_fallas_cerradas" => $rowCierreMensualOrden['cantidad_fallas_cerradas'],
				"cantidad_uts_cerradas" => $rowCierreMensualOrden['cantidad_uts_cerradas']);
		}
	} else {
		// ORDENES DE SERVICIOS ABIERTAS Y CERRADAS
		$Result1 = cierreOrdenesServicio($idEmpresa, $valFecha[0], $valFecha[1]);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayTipoOrden = $Result1[1];
		}
	}
	
	// AGRUPA LOS TIPO DE ORDEN POR FILTRO DE ORDEN
	if (isset($arrayTipoOrden)) {
		foreach ($arrayTipoOrden as $indiceTipoOrden => $valorTipoOrden) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo_orden.id_tipo_orden = %s",
				valTpDato($valorTipoOrden['id_tipo_orden'], "int"));
				
			if (strlen($rowConfig301['valor']) > 0) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("filtro_orden.id_filtro_orden IN (%s)",
					valTpDato($rowConfig301['valor'], "campo"));
			}
			
			$queryFiltroOrden = sprintf("SELECT
				filtro_orden.id_filtro_orden,
				filtro_orden.descripcion
			FROM sa_tipo_orden tipo_orden
				INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden) %s", $sqlBusq);
			$rsFiltroOrden = mysql_query($queryFiltroOrden);
			if (!$rsFiltroOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
				$existe = false;
				if (isset($arrayFiltroOrden)) {
					foreach ($arrayFiltroOrden as $indice2 => $valor2) {
						if ($indice2 == $rowFiltroOrden['id_filtro_orden']) {
							$existe = true;
							
							$arrayFiltroOrden[$indice2]['cantidad_abiertas'] += $arrayTipoOrden[$indiceTipoOrden]['cantidad_abiertas'];
							$arrayFiltroOrden[$indice2]['cantidad_cerradas'] += $arrayTipoOrden[$indiceTipoOrden]['cantidad_cerradas'];
							$arrayFiltroOrden[$indice2]['cantidad_fallas_abiertas'] += $arrayTipoOrden[$indiceTipoOrden]['cantidad_fallas_abiertas'];
							$arrayFiltroOrden[$indice2]['cantidad_fallas_cerradas'] += $arrayTipoOrden[$indiceTipoOrden]['cantidad_fallas_cerradas'];
							$arrayFiltroOrden[$indice2]['cantidad_uts_cerradas'] += $arrayTipoOrden[$indiceTipoOrden]['cantidad_uts_cerradas'];
						}
					}
				}
					
				if ($existe == false) {
					$arrayFiltroOrden[$rowFiltroOrden['id_filtro_orden']] = array(
						"nombre_tipo_orden" => $rowFiltroOrden['descripcion'],
						"cantidad_abiertas" => $arrayTipoOrden[$indiceTipoOrden]['cantidad_abiertas'],
						"cantidad_cerradas" => $arrayTipoOrden[$indiceTipoOrden]['cantidad_cerradas'],
						"cantidad_fallas_abiertas" => $arrayTipoOrden[$indiceTipoOrden]['cantidad_fallas_abiertas'],
						"cantidad_fallas_cerradas" => $arrayTipoOrden[$indiceTipoOrden]['cantidad_fallas_cerradas'],
						"cantidad_uts_cerradas" => $arrayTipoOrden[$indiceTipoOrden]['cantidad_uts_cerradas']);
				}
				
				$totalTipoOrdenAbierta += $arrayTipoOrden[$indiceTipoOrden]['cantidad_abiertas'];
				$totalTipoOrdenCerrada += $arrayTipoOrden[$indiceTipoOrden]['cantidad_cerradas'];
				$totalFallaTipoOrdenAbierta += $arrayTipoOrden[$indiceTipoOrden]['cantidad_fallas_abiertas'];
				$totalFallaTipoOrdenCerrada += $arrayTipoOrden[$indiceTipoOrden]['cantidad_fallas_cerradas'];
				$totalUtsTipoOrdenCerrada += $arrayTipoOrden[$indiceTipoOrden]['cantidad_uts_cerradas'];
			}
		}
	}
	$arrayTipoOrden = $arrayFiltroOrden;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipo NOT IN ('FERIADO')");
		
	if ($valFecha[0] != "-1" && $valFecha[0] != ""
	&& $valFecha[1] != "-1" && $valFecha[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(fecha_baja) = %s
		AND (YEAR(fecha_baja) = %s OR YEAR(fecha_baja) = '0000')",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// BUSCA LOS DIAS FERIADOS
	$queryDiasFeriados = sprintf("SELECT * FROM pg_fecha_baja %s;", $sqlBusq);
	$rsDiasFeriados = mysql_query($queryDiasFeriados);
	if (!$rsDiasFeriados) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsDiasFeriados = mysql_num_rows($rsDiasFeriados);

	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr>";
		$htmlTh .= "<td width=\"40%\"></td>";
		$htmlTh .= "<td width=\"20%\"></td>";
		$htmlTh .= "<td width=\"20%\"></td>";
		$htmlTh .= "<td width=\"20%\"></td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td>Indicador</td>";
		$htmlTh .= "<td colspan=\"3\">Unidad</td>";
	$htmlTh .= "</tr>";
	
	// MANO DE OBRA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Mano de Obra"."</td>";
		$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($subTotalMo, 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "</tr>";
	
	// DIAS HABILES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Días Hábiles Mes"."</td>";
		$htmlTb .= "<td colspan=\"3\">";
			$diaHabiles = evaluaFecha(diasHabiles('01-'.$valFecha[0].'-'.$valFecha[1], ultimoDia($valFecha[0],$valFecha[1]).'-'.$valFecha[0].'-'.$valFecha[1])) - $totalRowsDiasFeriados;
			$htmlTb .= valTpDato(number_format($diaHabiles, 2, ".", ","),"cero_por_vacio");
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	if (isset($arrayMecanico)) {
		foreach ($arrayMecanico as $indice => $valor) {
			// NUMERO DE TECNICOS
			$htmlTb .= "<tr align=\"right\" class=\"trResaltar2\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">"."Nro. Técnicos ".$arrayMecanico[$indice][2]."</td>";
				$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($arrayMecanico[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
			
			// HORAS DISPONIBLE VENTA TECNICOS
			$htmlTb .= "<tr align=\"right\" class=\"trResaltar2\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">"."Hrs. Disp. Venta Técnicos"."</td>";
				$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($arrayMecanico[$indice][1] * 7.1 * $diaHabiles, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
			
			// HORAS PROMEDIO TECNICOS
			$htmlTb .= "<tr align=\"right\" class=\"trResaltar2\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">"."Hrs. Prom / Técnicos"."</td>";
				$htmlTb .= "<td colspan=\"3\">";
					$htmlTb .= valTpDato(number_format((($arrayMecanico[$indice][1] > 0) ? ($arrayMecanico[$indice][1] * 7.1 * $diaHabiles) / $arrayMecanico[$indice][1] : 0), 2, ".", ","),"cero_por_vacio");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
	
			// NUMERO DE TECNICOS EN FORMACION
			$htmlTb .= "<tr align=\"right\" class=\"trResaltar\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">"."Nro. Técnicos en Formación ".$arrayMecanico[$indice][2]."</td>";
				$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($arrayMecanico[$indice][0], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
			
			// HORAS DISPONIBLE VENTA TECNICOS EN FORMACION
			$htmlTb .= "<tr align=\"right\" class=\"trResaltar\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">"."Hrs. Disp. Venta Técnicos en Formación"."</td>";
				$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($arrayMecanico[$indice][0] * 3.57 * $diaHabiles, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
			
			// HORAS PROMEDIO TECNICOS EN FORMACION
			$htmlTb .= "<tr align=\"right\" class=\"trResaltar\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">"."Hrs. Prom / Técnicos en Formación"."</td>";
				$htmlTb .= "<td colspan=\"3\">";
					$htmlTb .= valTpDato(number_format((($arrayMecanico[$indice][0] > 0) ? ($arrayMecanico[$indice][0] * 3.57 * $diaHabiles) / $arrayMecanico[$indice][0] : 0), 2, ".", ","),"cero_por_vacio");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// ENTRADA DE VEHICULOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Entrada de Vehículos ".$arrayValeRecepcion[1][0]."</td>";
		$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($arrayValeRecepcion[1][1], 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "</tr>";
	
	// ENTRADA DE VEHICULOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Entrada de Vehículos ".$arrayValeRecepcion[2][0]." y ".$arrayValeRecepcion[3][0]."</td>";
		$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($arrayValeRecepcion[2][1] + $arrayValeRecepcion[3][1], 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTb .= "<tr class=\"tituloColumna\">";
		$htmlTb .= "<td colspan=\"4\">O/R Abiertas</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr class=\"tituloColumna\">";
		$htmlTb .= "<td>O/R Abiertas</td>";
		$htmlTb .= "<td>Fallas</td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td>Cant. Ordenes</td>";
	$htmlTb .= "</tr>";
	
	// ORDENES DE SERVICIOS ABIERTAS
	if (isset($arrayTipoOrden)) {
		foreach ($arrayTipoOrden as $indice => $valor) {
			if ($arrayTipoOrden[$indice]['cantidad_abiertas'] != 0) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td align=\"left\">O/R Abiertas ".$arrayTipoOrden[$indice]['nombre_tipo_orden']."</td>";
					$htmlTb .= "<td>".valTpDato(number_format($arrayTipoOrden[$indice]['cantidad_fallas_abiertas'], 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "<td></td>";
					$htmlTb .= "<td>".valTpDato(number_format($arrayTipoOrden[$indice]['cantidad_abiertas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "</tr>";
			}
		}
	}
	// TOTAL DE ORDENES ABIERTAS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total O/R Abiertas"."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalFallaTipoOrdenAbierta, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td></td>";
		$htmlTb .= "<td colspan=\"2\">".valTpDato(number_format($totalTipoOrdenAbierta, 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTb .= "<tr class=\"tituloColumna\">";
		$htmlTb .= "<td colspan=\"4\">O/R Cerradas</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr class=\"tituloColumna\">";
		$htmlTb .= "<td>O/R Abiertas</td>";
		$htmlTb .= "<td>Fallas</td>";
		$htmlTb .= "<td>UT'S</td>";
		$htmlTb .= "<td>Cant. Ordenes</td>";
	$htmlTb .= "</tr>";
	
	// ORDENES DE SERVICIOS CERRADAS
	if (isset($arrayTipoOrden)) {
		foreach ($arrayTipoOrden as $indice => $valor) {
			if ($arrayTipoOrden[$indice]['cantidad_cerradas'] != 0) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td align=\"left\">O/R Cerradas ".$arrayTipoOrden[$indice]['nombre_tipo_orden']."</td>";
					$htmlTb .= "<td>".valTpDato(number_format($arrayTipoOrden[$indice]['cantidad_fallas_cerradas'], 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "<td>".valTpDato(number_format($arrayTipoOrden[$indice]['cantidad_uts_cerradas'], 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "<td>".valTpDato(number_format($arrayTipoOrden[$indice]['cantidad_cerradas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "</tr>";
			}
		}
	}
	// TOTAL DE ORDENES CERRADAS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total O/R Cerradas"."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalFallaTipoOrdenCerrada, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalUtsTipoOrdenCerrada, 2, ".", ","),"cero_por_vacio")."</td>";
		$htmlTb .= "<td>".valTpDato(number_format($totalTipoOrdenCerrada, 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "</tr>";
	
	// REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">".cAbrevMoneda." Rptos. Servicios"."</td>";
		$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($subTotalRepServ - $totalDescuentoRepServ, 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "</tr>";
	
	// BS REPUESTOS ENTRE ORDENES
	$totalTipoOrdenCerrada = ($totalTipoOrdenCerrada > 0) ? $totalTipoOrdenCerrada : 1;
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">".cAbrevMoneda." Rptos. Servicios / OR"."</td>";
		$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format(($subTotalRepServ - $totalDescuentoRepServ) / $totalTipoOrdenCerrada, 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "</tr>";
	
	// HORAS ENTRE ORDENES
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."UT'S / OR"."</td>";
		$htmlTb .= "<td colspan=\"3\">".valTpDato(number_format($totalTotalUtsEquipos / $totalTipoOrdenCerrada, 2, ".", ","),"cero_por_vacio")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">"."<p class=\"textoAzul\">INDICADORES DE TALLER (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaIndicadoresTaller","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	$objResponse->script("
	byId('tblMsj').style.display = 'none';
	byId('tblInforme').style.display = '';");
	
	mysql_close();

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
					var brightness = 0.2 - (j / data[i].drilldown.data.length) / 1 ;
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
							distance: -30,
							style: {
								fontWeight: 'bold',
							},
							enabled: true,
							borderRadius: 5,
							backgroundColor: 'gray',
							borderWidth: 1,
							borderColor: '#AAA'
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
							},
							color: 'white'
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
	
	$objResponse->script(sprintf("verVentana('reportes/if_resumen_postventa_pdf.php?valBusq=%s&lstDecimalPDF=%s', 1000, 500);", $valBusq, $frmBuscar['lstDecimalPDF']));
	
	$objResponse->assign("tdlstDecimalPDF","innerHTML","");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstDecimalPDF");
$xajax->register(XAJAX_FUNCTION,"exportarResumen");
$xajax->register(XAJAX_FUNCTION,"facturacionVendedores");
$xajax->register(XAJAX_FUNCTION,"formGrafico");
$xajax->register(XAJAX_FUNCTION,"imprimirResumen");
?>