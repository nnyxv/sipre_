<?php


function buscarAnalisisInventario($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAnalisisInv'],
		$frmBuscar['cbxVerUbicDisponible'],
		$frmBuscar['cbxVerUbicSinDisponible'],
		$frmBuscar['lstVerClasificacion'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaAnalisisInventario(0, "clasificacion", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstAnalisisInventario($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_analisis_inventario
		INNER JOIN iv_cierre_mensual ON (iv_analisis_inventario.id_cierre_mensual = iv_cierre_mensual.id_cierre_mensual)
	WHERE iv_cierre_mensual.id_empresa = %s
	ORDER BY fecha",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAnalisisInv\" name=\"lstAnalisisInv\" class=\"inputHabilitado\" onchange=\"$('btnBuscar').click();\">";
		$html .="<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_analisis_inventario']) ? "selected=\"selected\"" : "";
	
		$html .= "<option ".$selected." value=\"".$row['id_cierre_mensual']."\">".str_pad($row['mes'], 2, "0", STR_PAD_LEFT)."-".$row['ano']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstAnalisisInv","innerHTML",$html);
	
	return $objResponse;
}

function exportarAnalisisInventario($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAnalisisInv'],
		$frmBuscar['cbxVerUbicDisponible'],
		$frmBuscar['cbxVerUbicSinDisponible'],
		$frmBuscar['lstVerClasificacion'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_analisis_inventario_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirAnalisisInventario($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAnalisisInv'],
		$frmBuscar['cbxVerUbicDisponible'],
		$frmBuscar['cbxVerUbicSinDisponible'],
		$frmBuscar['lstVerClasificacion'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/iv_analisis_inventario_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaAnalisisInventario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 50, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $valCadBusq[0];
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_mensual.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_mensual.id_cierre_mensual = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$queryAnalisisInv = sprintf("SELECT *
	FROM iv_analisis_inventario analisis_inv
		INNER JOIN iv_cierre_mensual cierre_mensual ON (analisis_inv.id_cierre_mensual = cierre_mensual.id_cierre_mensual) %s", $sqlBusq);
	$rsAnalisisInv = mysql_query($queryAnalisisInv);
	if (!$rsAnalisisInv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAnalisisInv = mysql_fetch_assoc($rsAnalisisInv);
	$idAnalisisInv = $rowAnalisisInv['id_analisis_inventario'];
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("analisis_inv.id_analisis_inventario = %s",
		valTpDato($idAnalisisInv, "int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != ""
	&& ($valCadBusq[3] == "-1" || $valCadBusq[3] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("analisis_inv_det.cantidad_existencia > 0");
	}
	
	if (($valCadBusq[2] == "-1" || $valCadBusq[2] == "")
	&& $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("analisis_inv_det.cantidad_existencia <= 0");
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("analisis_inv_det.clasificacion LIKE %s",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[5], "text"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(analisis_inv_det.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[6], "int"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT
		analisis_inv_det.id_analisis_inventario_detalle,
		analisis_inv_det.id_analisis_inventario,
		analisis_inv_det.id_articulo,
		analisis_inv_det.cantidad_existencia,
		analisis_inv_det.cantidad_disponible_logica,
		analisis_inv_det.cantidad_disponible_fisica,
		analisis_inv_det.costo,
		(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia) AS costo_total,
		(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
		analisis_inv_det.promedio_diario,
		analisis_inv_det.promedio_mensual,
		(analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) AS inventario_recomendado,
		(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario)) AS sobre_stock,
		((analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) - analisis_inv_det.cantidad_existencia) AS sugerido,
		analisis_inv_det.clasificacion,
		art.codigo_articulo,
		art.codigo_articulo_prov,
		art.descripcion
	FROM iv_cierre_mensual cierre_mensual
		INNER JOIN iv_analisis_inventario analisis_inv ON (cierre_mensual.id_cierre_mensual = analisis_inv.id_cierre_mensual)
		INNER JOIN iv_analisis_inventario_detalle analisis_inv_det ON (analisis_inv.id_analisis_inventario = analisis_inv_det.id_analisis_inventario)
		INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" height=\"22\">";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">Meses Inventario:</td>";
		$htmlTh .= "<td align=\"right\">".$rowAnalisisInv['meses_inventario']."</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" height=\"22\">";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">Meses Mínimo:</td>";
		$htmlTh .= "<td align=\"right\">".$rowAnalisisInv['meses_minimo']."</td>";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">Meses de Consumo Promedio:</td>";
		$htmlTh .= "<td align=\"right\" colspan=\"2\">".$rowAnalisisInv['meses_consumo_promedio']."</td>";
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" height=\"22\">";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">Meses Protección:</td>";
		$htmlTh .= "<td align=\"right\">".$rowAnalisisInv['meses_proteccion']."</td>";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">Días Hábiles:</td>";
		$htmlTh .= "<td align=\"right\" colspan=\"2\">".$rowAnalisisInv['dias_habiles']."</td>";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">"."Mes-Año:"."</td>";
		$htmlTh .= "<td colspan=\"3\">".str_pad($rowAnalisisInv['mes'], 2, "0", STR_PAD_LEFT)."-".$rowAnalisisInv['ano']."</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "20%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "8%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Prov.");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "6%", $pageNum, "cantidad_existencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Disponible");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "8%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unitario");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "8%", $pageNum, "costo_total", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Total");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "6%", $pageNum, "meses_existencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Meses Existencia", "Unid. Disponible / Venta Mensual");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "6%", $pageNum, "promedio_diario", $campOrd, $tpOrd, $valBusq, $maxRows, "Promedio Diario");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "6%", $pageNum, "promedio_mensual", $campOrd, $tpOrd, $valBusq, $maxRows, "Venta Mensual", "Promedio Diario * Días Hábiles");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "4%", $pageNum, "inventario_recomendado", $campOrd, $tpOrd, $valBusq, $maxRows, "Inventario Recomendado", "Venta Mensual * Meses de Inventario");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "4%", $pageNum, "sobre_stock", $campOrd, $tpOrd, $valBusq, $maxRows, "Sobre Stock", "Unid. Disponible - Inventario Recomendado");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "4%", $pageNum, "sugerido", $campOrd, $tpOrd, $valBusq, $maxRows, "Sugerido", "Inventario Recomendado - Unid. Disponible");
		$htmlTh .= ordenarCampo("xajax_listaAnalisisInventario", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$classDisponible = ($row['cantidad_existencia'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_articulo_prov'])."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($row['cantidad_existencia'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo_total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format((($row['promedio_mensual'] > 0) ? $row['meses_existencia'] : 0), 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['promedio_diario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['promedio_mensual'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['inventario_recomendado'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format((($row['cantidad_existencia'] > $row['inventario_recomendado']) ? $row['sobre_stock'] : 0), 2, ".", ",");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format((($row['cantidad_existencia'] < $row['inventario_recomendado']) ? $row['sugerido'] : 0), 2, ".", ",");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
				}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[5] += $row['cantidad_existencia'];
		$arrayTotal[7] += $row['costo_total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">".("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"7\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[5] += $row['cantidad_existencia'];
				$arrayTotalFinal[7] += $row['costo_total'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">".("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"7\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnalisisInventario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnalisisInventario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnalisisInventario(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnalisisInventario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnalisisInventario(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaAnalisisInventario","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	// AGRUPA LAS CLASIFICACIONES PARA CALCULAR SUS TOTALES
	$queryTipoMov = sprintf("SELECT analisis_inv_det.clasificacion
	FROM iv_cierre_mensual cierre_mensual
		INNER JOIN iv_analisis_inventario analisis_inv ON (cierre_mensual.id_cierre_mensual = analisis_inv.id_cierre_mensual)
		INNER JOIN iv_analisis_inventario_detalle analisis_inv_det ON (analisis_inv.id_analisis_inventario = analisis_inv_det.id_analisis_inventario)
		INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo) %s
	GROUP BY clasificacion", $sqlBusq);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">Clasificación</td>
					<td width=\"14%\">Cant. Artículos</td>
					<td width=\"14%\">Existencia</td>
					<td width=\"14%\">Costo Inv.</td>
					<td width=\"14%\">Prom. Venta</td>
					<td width=\"14%\">Meses Exist.</td>";
	$htmlTh .= "</tr>";
	$htmlTb = "";
	while($rowMovDet = mysql_fetch_array($rsTipoMov)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 = $cond.sprintf("analisis_inv.id_analisis_inventario = %s
		AND ((analisis_inv_det.clasificacion = %s AND %s IS NOT NULL)
			OR analisis_inv_det.clasificacion IS NULL AND %s IS NULL)",
			valTpDato($idAnalisisInv, "int"),
			valTpDato($rowMovDet['clasificacion'], "text"),
			valTpDato($rowMovDet['clasificacion'], "text"),
			valTpDato($rowMovDet['clasificacion'], "text"));
		
		$queryDetalle = sprintf("SELECT
			analisis_inv_det.id_analisis_inventario_detalle,
			analisis_inv_det.id_analisis_inventario,
			analisis_inv_det.id_articulo,
			analisis_inv_det.cantidad_existencia,
			analisis_inv_det.cantidad_disponible_logica,
			analisis_inv_det.cantidad_disponible_fisica,
			analisis_inv_det.costo,
			(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia) AS costo_total,
			(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
			analisis_inv_det.promedio_diario,
			analisis_inv_det.promedio_mensual,
			(analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) AS inventario_recomendado,
			(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario)) AS sobre_stock,
			((analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) - analisis_inv_det.cantidad_existencia) AS sugerido,
			analisis_inv_det.clasificacion,
			art.codigo_articulo,
			art.codigo_articulo_prov,
			art.descripcion
		FROM iv_cierre_mensual cierre_mensual
			INNER JOIN iv_analisis_inventario analisis_inv ON (cierre_mensual.id_cierre_mensual = analisis_inv.id_cierre_mensual)
			INNER JOIN iv_analisis_inventario_detalle analisis_inv_det ON (analisis_inv.id_analisis_inventario = analisis_inv_det.id_analisis_inventario)
			INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo) %s %s", $sqlBusq, $sqlBusq2);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$cantArt = 0;
		$exist = 0;
		$costoInv = 0;
		$promVenta = 0;
		$mesesExist = 0;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$cantArt++;
			$exist += $rowDetalle['cantidad_existencia'];
			$costoInv += $rowDetalle['costo_total'];
			$promVenta += $rowDetalle['promedio_mensual'] * $rowDetalle['costo'];
			$mesesExist += $rowDetalle['meses_existencia'];
		}
	
		$totalCantArt += $cantArt;
		$totalExistArt += $exist;
		$totalCostoInv += $costoInv;
		
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">";
				switch($rowMovDet['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$cantArt."</td>";
			$htmlTb .= "<td>".number_format($exist, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($costoInv, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($promVenta / $cantArt, 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= ($promVenta > 0) ? number_format(($mesesExist / $cantArt), 2, ".", ",") : number_format(0, 2, ".", ",");
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
		$htmlTb .= "<td class=\"tituloColumna\">Totales:</td>";
		$htmlTb .= "<td>".$totalCantArt."</td>";
		$htmlTb .= "<td>".number_format($totalExistArt, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($totalCostoInv, 2, ".", ",")."</td>";
		$htmlTb .= "<td>"."</td>";
		$htmlTb .= "<td>"."</td>";
	$htmlTb .= "<tr>";
	$htmlTblFin = "</table>";
	
	$objResponse->assign("divListaResumenAnalisisInventario","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarAnalisisInventario");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnalisisInventario");
$xajax->register(XAJAX_FUNCTION,"exportarAnalisisInventario");
$xajax->register(XAJAX_FUNCTION,"imprimirAnalisisInventario");
$xajax->register(XAJAX_FUNCTION,"listaAnalisisInventario");
?>