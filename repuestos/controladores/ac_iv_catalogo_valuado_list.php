<?php


function buscarArticulo($frmBuscar) {
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
		$frmBuscar['lstTipoArticulo'],
		$frmBuscar['lstVerClasificacion'],
		implode(",",$frmBuscar['lstSaldos']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCatalogoValuado(0, "codigo_articulo", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarCatalogoValuado($frmBuscar) {
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
		$frmBuscar['lstTipoArticulo'],
		$frmBuscar['lstVerClasificacion'],
		implode(",",$frmBuscar['lstSaldos']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_catalogo_valuado_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaCatalogoValuado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 100, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art_emp.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = art_emp.id_empresa))",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(art_emp.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = art_emp.id_empresa))",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("art.id_tipo_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.clasificacion LIKE %s",
			valTpDato($valCadBusq[2], "text"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("art_emp.clasificacion LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if (in_array(1,explode(",",$valCadBusq[3]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(query.cantidad_entrada - query.cantidad_salida) > 0");
	}
	
	if (in_array(2,explode(",",$valCadBusq[3]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(query.cantidad_entrada - query.cantidad_salida) <= 0");
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[4], "text"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT art.*,
		
		query.cantidad_entrada,
		(query.cantidad_entrada * query.costo_unitario) AS valor_entrada,
		
		query.cantidad_salida,
		(query.cantidad_salida * query.costo_unitario) AS valor_salida,
		
		(query.cantidad_entrada - query.cantidad_salida) AS existencia,
		((query.cantidad_entrada - query.cantidad_salida) * query.costo_unitario) AS valor_existencia
			
	FROM iv_articulos_empresa art_emp
		INNER JOIN iv_articulos art ON (art_emp.id_articulo = art.id_articulo)
		INNER JOIN (SELECT
					alm.id_empresa,
					art_alm.id_articulo,
					SUM(art_alm.cantidad_entrada) AS cantidad_entrada,
					SUM(art_alm.cantidad_salida) AS cantidad_salida,
					(SELECT
						(CASE (SELECT valor FROM pg_configuracion_empresa config_emp
									INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
								WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = art_costo.id_empresa)
							WHEN 1 THEN	art_costo.costo
							WHEN 2 THEN	art_costo.costo_promedio
							WHEN 3 THEN
								IF((SELECT
										(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
											+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
											- SUM(art_costo2.cantidad_salida * art_costo2.costo))
										/ (SUM(art_costo2.cantidad_inicio)
											+ SUM(art_costo2.cantidad_entrada)
											- SUM(art_costo2.cantidad_salida))
									FROM iv_articulos_costos art_costo2
									WHERE art_costo2.id_articulo = art_costo.id_articulo
										AND art_costo2.id_empresa = art_costo.id_empresa
										AND art_costo2.estatus = 1
									ORDER BY art_costo2.fecha_registro DESC), (SELECT
																					(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
																						+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
																						- SUM(art_costo2.cantidad_salida * art_costo2.costo))
																					/ (SUM(art_costo2.cantidad_inicio)
																						+ SUM(art_costo2.cantidad_entrada)
																						- SUM(art_costo2.cantidad_salida))
																				FROM iv_articulos_costos art_costo2
																				WHERE art_costo2.id_articulo = art_costo.id_articulo
																					AND art_costo2.id_empresa = art_costo.id_empresa
																					AND art_costo2.estatus = 1
																				ORDER BY art_costo2.fecha_registro DESC), art_costo.costo_promedio)
							END)
					FROM iv_articulos_costos art_costo
					WHERE art_costo.id_articulo = art_alm.id_articulo
						AND art_costo.id_empresa = alm.id_empresa
					ORDER BY art_costo.id_articulo_costo DESC LIMIT 1) AS costo_unitario
				FROM iv_estantes estante
					INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
					INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
					INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
					INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
				GROUP BY alm.id_empresa, art_alm.id_articulo) AS query ON (query.id_empresa = art_emp.id_empresa)
					AND (query.id_articulo = art_emp.id_articulo) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"5\"></td>";
		$htmlTh .= "<td colspan=\"2\">ENTRADAS</td>";
		$htmlTh .= "<td colspan=\"2\">SALIDAS</td>";
		$htmlTh .= "<td colspan=\"2\">ACTUAL</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "28%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "10%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Prov.");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "6%", $pageNum, "cantidad_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidades");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "8%", $pageNum, "valor_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Importe");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "6%", $pageNum, "cantidad_salida", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidades");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "8%", $pageNum, "valor_salida", $campOrd, $tpOrd, $valBusq, $maxRows, "Importe");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "6%", $pageNum, "existencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidades");
		$htmlTh .= ordenarCampo("xajax_listaCatalogoValuado", "8%", $pageNum, "valor_existencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Importe");
	$htmlTh .= "</tr>";
	
	$arrayTotal = NULL;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".($contFila + (($pageNum) * $maxRows))."</td>"; // <----
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode(substr($row['descripcion'],0,80))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_articulo_prov'])."</td>";
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
			$htmlTb .= "<td align=\"right\">".number_format($row['cantidad_entrada'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($row['valor_entrada'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cantidad_salida'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($row['valor_salida'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".(($row['existencia'] > 0) ? "class=\"divMsjInfo\"" : (($row['existencia'] < 0) ? "class=\"divMsjError\"" : "")).">";
				$htmlTb .= number_format($row['existencia'], 2, ".", ",");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($row['valor_existencia'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal['cantidad_entrada'] += $row['cantidad_entrada'];
		$arrayTotal['valor_entrada'] += $row['valor_entrada'];
		$arrayTotal['cantidad_salida'] += $row['cantidad_salida'];
		$arrayTotal['valor_salida'] += $row['valor_salida'];
		$arrayTotal['existencia'] += $row['existencia'];
		$arrayTotal['valor_existencia'] += $row['valor_existencia'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"5\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cantidad_entrada'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".cAbrevMoneda.number_format($arrayTotal['valor_entrada'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cantidad_salida'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".cAbrevMoneda.number_format($arrayTotal['valor_salida'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['existencia'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".cAbrevMoneda.number_format($arrayTotal['valor_existencia'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal['cantidad_entrada'] += $row['cantidad_entrada'];
				$arrayTotalFinal['valor_entrada'] += $row['valor_entrada'];
				$arrayTotalFinal['cantidad_salida'] += $row['cantidad_salida'];
				$arrayTotalFinal['valor_salida'] += $row['valor_salida'];
				$arrayTotalFinal['existencia'] += $row['existencia'];
				$arrayTotalFinal['valor_existencia'] += $row['valor_existencia'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"5\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['cantidad_entrada'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".cAbrevMoneda.number_format($arrayTotalFinal['valor_entrada'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['cantidad_salida'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".cAbrevMoneda.number_format($arrayTotalFinal['valor_salida'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['existencia'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".cAbrevMoneda.number_format($arrayTotalFinal['valor_existencia'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoValuado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoValuado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCatalogoValuado(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoValuado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoValuado(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCatalogoValuado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	// AGRUPA LAS CLASIFICACIONES PARA CALCULAR SUS TOTALES
	$queryTipoMov = sprintf("SELECT art_emp.clasificacion
	FROM vw_iv_articulos_empresa vw_iv_art_emp
		INNER JOIN iv_articulos_empresa art_emp ON (vw_iv_art_emp.id_articulo_empresa = art_emp.id_articulo_empresa)
		INNER JOIN iv_articulos art ON (art_emp.id_articulo = art.id_articulo) %s
	GROUP BY art_emp.clasificacion", $sqlBusq2);//$objResponse->alert($queryTipoMov);
	$rsTipoMov = mysql_query($queryTipoMov);
	if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"36%\">Clasificación</td>
					<td width=\"18%\">Cant. Artículos</td>
					<td width=\"18%\">Existencia</td>
					<td width=\"18%\">Importe</td>
					<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	$htmlTb = "";
	while($rowMovDet = mysql_fetch_array($rsTipoMov)){
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("((art_emp.clasificacion = %s AND %s IS NOT NULL)
			OR art_emp.clasificacion IS NULL AND %s IS NULL)",
			valTpDato($rowMovDet['clasificacion'], "text"),
			valTpDato($rowMovDet['clasificacion'], "text"),
			valTpDato($rowMovDet['clasificacion'], "text"));
		
		$queryDetalle = sprintf("SELECT art.*,
			
			query.cantidad_entrada,
			(query.cantidad_entrada * query.costo_unitario) AS valor_entrada,
			
			query.cantidad_salida,
			(query.cantidad_salida * query.costo_unitario) AS valor_salida,
			
			(query.cantidad_entrada - query.cantidad_salida) AS existencia,
			((query.cantidad_entrada - query.cantidad_salida) * query.costo_unitario) AS valor_existencia
				
		FROM iv_articulos_empresa art_emp
			INNER JOIN iv_articulos art ON (art_emp.id_articulo = art.id_articulo)
			INNER JOIN (SELECT
						alm.id_empresa,
						art_alm.id_articulo,
						SUM(art_alm.cantidad_entrada) AS cantidad_entrada,
						SUM(art_alm.cantidad_salida) AS cantidad_salida,
						(SELECT
							(CASE (SELECT valor FROM pg_configuracion_empresa config_emp
										INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
									WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = art_costo.id_empresa)
								WHEN 1 THEN	art_costo.costo
								WHEN 2 THEN	art_costo.costo_promedio
								WHEN 3 THEN
									IF((SELECT
											(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
												+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
												- SUM(art_costo2.cantidad_salida * art_costo2.costo))
											/ (SUM(art_costo2.cantidad_inicio)
												+ SUM(art_costo2.cantidad_entrada)
												- SUM(art_costo2.cantidad_salida))
										FROM iv_articulos_costos art_costo2
										WHERE art_costo2.id_articulo = art_costo.id_articulo
											AND art_costo2.id_empresa = art_costo.id_empresa
											AND art_costo2.estatus = 1
										ORDER BY art_costo2.fecha_registro DESC), (SELECT
																						(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
																							+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
																							- SUM(art_costo2.cantidad_salida * art_costo2.costo))
																						/ (SUM(art_costo2.cantidad_inicio)
																							+ SUM(art_costo2.cantidad_entrada)
																							- SUM(art_costo2.cantidad_salida))
																					FROM iv_articulos_costos art_costo2
																					WHERE art_costo2.id_articulo = art_costo.id_articulo
																						AND art_costo2.id_empresa = art_costo.id_empresa
																						AND art_costo2.estatus = 1
																					ORDER BY art_costo2.fecha_registro DESC), art_costo.costo_promedio)
								END)
						FROM iv_articulos_costos art_costo
						WHERE art_costo.id_articulo = art_alm.id_articulo
							AND art_costo.id_empresa = alm.id_empresa
						ORDER BY art_costo.id_articulo_costo DESC LIMIT 1) AS costo_unitario
					FROM iv_estantes estante
						INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
						INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
						INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
					GROUP BY alm.id_empresa, art_alm.id_articulo) AS query ON (query.id_empresa = art_emp.id_empresa)
						AND (query.id_articulo = art_emp.id_articulo) %s %s", $sqlBusq, $sqlBusq2);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayTotal = NULL;
		while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
			$arrayTotal[2]++;
			$arrayTotal[3] += $rowDetalle['existencia'];
			$arrayTotal[4] += $rowDetalle['valor_existencia'];
		}
		
		$totalValorExist += $arrayTotal[4];
		
		$array[] = array(
			$rowMovDet['clasificacion'],
			$arrayTotal[2],
			$arrayTotal[3],
			$arrayTotal[4]);
	}
	
	$arrayTotal = NULL;
	$contFila = 0;
	if (isset($array)) {
		foreach ($array as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;

			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\">";
					switch($array[$indice][0]) {
						case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
						case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
						case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
						case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
						case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
						case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
					}
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".number_format($array[$indice][1], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($array[$indice][2], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".cAbrevMoneda.number_format($array[$indice][3], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format((($totalValorExist > 0) ? ($array[$indice][3] * 100) / $totalValorExist : 0), 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
		
			$arrayTotal[2] += $array[$indice][1];
			$arrayTotal[3] += $array[$indice][2];
			$arrayTotal[4] += $array[$indice][3];
			$arrayTotal[5] += ($totalValorExist > 0) ? ($array[$indice][3] * 100) / $totalValorExist : 0;
		}
	}
	
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
		$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Totales:</td>";
		$htmlTb .= "<td>".number_format($arrayTotal[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotal[3], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".cAbrevMoneda.number_format($arrayTotal[4], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."%</td>";
	$htmlTb .= "<tr>";
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaResumenCatalogoValuado","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarCatalogoValuado");
$xajax->register(XAJAX_FUNCTION,"listaCatalogoValuado");
?>