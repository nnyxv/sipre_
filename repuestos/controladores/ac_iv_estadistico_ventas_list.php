<?php


function buscarEstadisticoVenta($frmBuscar) {
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
		$frmBuscar['lstAno'],
		implode(",",$frmBuscar['lstSaldos']),
		implode(",",$frmBuscar['lstVerClasificacion']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaEstadisticoVentas(0, "art.clasificacion", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstAno($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['nom_ano']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['nom_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);
	
	return $objResponse;
}

function exportarEstadisticoVentas($frmBuscar) {
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
		$frmBuscar['lstAno'],
		implode(",",$frmBuscar['lstSaldos']),
		implode(",",$frmBuscar['lstVerClasificacion']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_estadistico_ventas_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaEstadisticoVentas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("query.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_anual.ano = %s",
			valTpDato($valCadBusq[1], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cierre_anual_p.ano = (%s - 1)",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("art_emp.clasificacion IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[5], "int"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT
		query.id_cierre_anual,
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		art_emp.clasificacion,
		query.id_empresa,
		query.id_articulo,
		query.ano,
		query.ano_p,
		SUM(query.enero) AS enero,
		SUM(query.enero_p) AS enero_p,
		SUM(query.febrero) AS febrero,
		SUM(query.febrero_p) AS febrero_p,
		SUM(query.marzo) AS marzo,
		SUM(query.marzo_p) AS marzo_p,
		SUM(query.abril) AS abril,
		SUM(query.abril_p) AS abril_p,
		SUM(query.mayo) AS mayo,
		SUM(query.mayo_p) AS mayo_p,
		SUM(query.junio) AS junio,
		SUM(query.junio_p) AS junio_p,
		SUM(query.julio) AS julio,
		SUM(query.julio_p) AS julio_p,
		SUM(query.agosto) AS agosto,
		SUM(query.agosto_p) AS agosto_p,
		SUM(query.septiembre) AS septiembre,
		SUM(query.septiembre_p) AS septiembre_p,
		SUM(query.octubre) AS octubre,
		SUM(query.octubre_p) AS octubre_p,
		SUM(query.noviembre) AS noviembre,
		SUM(query.noviembre_p) AS noviembre_p,
		SUM(query.diciembre) AS diciembre,
		SUM(query.diciembre_p) AS diciembre_p,
		SUM(query.cantidad_saldo) AS cantidad_saldo,
		SUM(query.cantidad_saldo_p) AS cantidad_saldo_p,
		SUM(query.total) AS total,
		SUM(query.total_p) AS total_p,
		SUM(query.promedio) AS promedio,
		SUM(query.promedio_p) AS promedio_p,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_articulos art
		INNER JOIN (SELECT
			cierre_anual.id_cierre_anual,
			cierre_anual.id_empresa,
			cierre_anual.id_articulo,
			cierre_anual.ano,
			(cierre_anual.ano - 1) AS ano_p,
			cierre_anual.enero,
			0 AS enero_p,
			cierre_anual.febrero,
			0 AS febrero_p,
			cierre_anual.marzo,
			0 AS marzo_p,
			cierre_anual.abril,
			0 AS abril_p,
			cierre_anual.mayo,
			0 AS mayo_p,
			cierre_anual.junio,
			0 AS junio_p,
			cierre_anual.julio,
			0 AS julio_p,
			cierre_anual.agosto,
			0 AS agosto_p,
			cierre_anual.septiembre,
			0 AS septiembre_p,
			cierre_anual.octubre,
			0 AS octubre_p,
			cierre_anual.noviembre,
			0 AS noviembre_p,
			cierre_anual.diciembre,
			0 AS diciembre_p,
			cierre_anual.cantidad_saldo,
			0 AS cantidad_saldo_p,
			
			(IFNULL(cierre_anual.enero, 0) + IFNULL(cierre_anual.febrero, 0) + IFNULL(cierre_anual.marzo, 0) + IFNULL(cierre_anual.abril, 0) + IFNULL(cierre_anual.mayo, 0) + IFNULL(cierre_anual.junio, 0) + IFNULL(cierre_anual.julio, 0) + IFNULL(cierre_anual.agosto, 0) + IFNULL(cierre_anual.septiembre, 0) + IFNULL(cierre_anual.octubre, 0) + IFNULL(cierre_anual.noviembre, 0) + IFNULL(cierre_anual.diciembre, 0)) AS total,
			0 AS total_p,
			
			((IFNULL(cierre_anual.enero, 0) + IFNULL(cierre_anual.febrero, 0) + IFNULL(cierre_anual.marzo, 0) + IFNULL(cierre_anual.abril, 0) + IFNULL(cierre_anual.mayo, 0) + IFNULL(cierre_anual.junio, 0) + IFNULL(cierre_anual.julio, 0) + IFNULL(cierre_anual.agosto, 0) + IFNULL(cierre_anual.septiembre, 0) + IFNULL(cierre_anual.octubre, 0) + IFNULL(cierre_anual.noviembre, 0) + IFNULL(cierre_anual.diciembre, 0)) / 12) AS promedio,
			0 AS promedio_p
		FROM iv_cierre_anual cierre_anual %s
			
		UNION
		
		SELECT 
			cierre_anual_p.id_cierre_anual,
			cierre_anual_p.id_empresa,
			cierre_anual_p.id_articulo,
			(cierre_anual_p.ano + 1) AS ano,
			cierre_anual_p.ano AS ano_p,
			0 AS enero,
			cierre_anual_p.enero,
			0 AS febrero,
			cierre_anual_p.febrero,
			0 AS marzo,
			cierre_anual_p.marzo,
			0 AS abril,
			cierre_anual_p.abril,
			0 AS mayo,
			cierre_anual_p.mayo,
			0 AS junio,
			cierre_anual_p.junio,
			0 AS julio,
			cierre_anual_p.julio,
			0 AS agosto,
			cierre_anual_p.agosto,
			0 AS septiembre,
			cierre_anual_p.septiembre,
			0 AS octubre,
			cierre_anual_p.octubre,
			0 AS noviembre,
			cierre_anual_p.noviembre,
			0 AS diciembre,
			cierre_anual_p.diciembre,
			0 AS cantidad_saldo,
			cierre_anual_p.cantidad_saldo,
			
			0 AS total,
			(IFNULL(cierre_anual_p.enero, 0) + IFNULL(cierre_anual_p.febrero, 0) + IFNULL(cierre_anual_p.marzo, 0) + IFNULL(cierre_anual_p.abril, 0) + IFNULL(cierre_anual_p.mayo, 0) + IFNULL(cierre_anual_p.junio, 0) + IFNULL(cierre_anual_p.julio, 0) + IFNULL(cierre_anual_p.agosto, 0) + IFNULL(cierre_anual_p.septiembre, 0) + IFNULL(cierre_anual_p.octubre, 0) + IFNULL(cierre_anual_p.noviembre, 0) + IFNULL(cierre_anual_p.diciembre, 0)) AS total,
			
			0 AS promedio,
			((IFNULL(cierre_anual_p.enero, 0) + IFNULL(cierre_anual_p.febrero, 0) + IFNULL(cierre_anual_p.marzo, 0) + IFNULL(cierre_anual_p.abril, 0) + IFNULL(cierre_anual_p.mayo, 0) + IFNULL(cierre_anual_p.junio, 0) + IFNULL(cierre_anual_p.julio, 0) + IFNULL(cierre_anual_p.agosto, 0) + IFNULL(cierre_anual_p.septiembre, 0) + IFNULL(cierre_anual_p.octubre, 0) + IFNULL(cierre_anual_p.noviembre, 0) + IFNULL(cierre_anual_p.diciembre, 0)) / 12) AS promedio
		FROM iv_cierre_anual cierre_anual_p %s) AS query ON (art.id_articulo = query.id_articulo)
		LEFT JOIN iv_articulos_empresa art_emp ON (query.id_empresa = art_emp.id_empresa AND query.id_articulo = art_emp.id_articulo)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (query.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		art.clasificacion,
		query.id_empresa,
		query.id_articulo,
		query.ano,
		query.ano_p", $sqlBusq, $sqlBusq2, $sqlBusq3);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "8%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "10%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "12%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "4%", $pageNum, "ano", $campOrd, $tpOrd, $valBusq, $maxRows, "Año");
		for ($i = 1; $i <= 12; $i++) {
			$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", (48 / 12)."%", $pageNum, strtolower($mes[$i]), $campOrd, $tpOrd, $valBusq, $maxRows, $mes[$i]);
		}
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "4%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "4%", $pageNum, "promedio", $campOrd, $tpOrd, $valBusq, $maxRows, "Prom.");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "4%", $pageNum, "cantidad_saldo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$arrayTotal = NULL;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$rowspan = (in_array(2,explode(",",$valCadBusq[2]))) ? "rowspan=\"".(count(explode(",",$valCadBusq[2])))."\"" : "";
		
		if (in_array(1,explode(",",$valCadBusq[2]))) {
			$arrayAnoActual = array(
				"id_cierre_anual" => $row['id_cierre_anual'],
				"ano" => $row['ano'],
				"enero" => $row['enero'],
				"febrero" => $row['febrero'],
				"marzo" => $row['marzo'],
				"abril" => $row['abril'],
				"mayo" => $row['mayo'],
				"junio" => $row['junio'],
				"julio" => $row['julio'],
				"agosto" => $row['agosto'],
				"septiembre" => $row['septiembre'],
				"octubre" => $row['octubre'],
				"noviembre" => $row['noviembre'],
				"diciembre" => $row['diciembre'],
				"total" => $row['total'],
				"promedio" => $row['promedio'],
				"cantidad_saldo" => $row['cantidad_saldo']);
		}
		if (in_array(2,explode(",",$valCadBusq[2]))) {
			$arrayAnoAnterior = array(
				"id_cierre_anual" => $row['id_cierre_anual'],
				"ano" => $row['ano_p'],
				"enero" => $row['enero_p'],
				"febrero" => $row['febrero_p'],
				"marzo" => $row['marzo_p'],
				"abril" => $row['abril_p'],
				"mayo" => $row['mayo_p'],
				"junio" => $row['junio_p'],
				"julio" => $row['julio_p'],
				"agosto" => $row['agosto_p'],
				"septiembre" => $row['septiembre_p'],
				"octubre" => $row['octubre_p'],
				"noviembre" => $row['noviembre_p'],
				"diciembre" => $row['diciembre_p'],
				"total" => $row['total_p'],
				"promedio" => $row['promedio_p'],
				"cantidad_saldo" => $row['cantidad_saldo_p']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"left\" ".$rowspan.">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"left\" ".$rowspan.">".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td align=\"left\" ".$rowspan.">".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\" ".$rowspan.">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
				}
			$htmlTb .= "</td>";
		if (count($arrayAnoActual) > 0) {
			$htmlTb .= "<td align=\"center\" title=\"".$arrayAnoActual['id_cierre_anual']."\">".$arrayAnoActual['ano']."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['enero'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['febrero'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['marzo'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['abril'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['mayo'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['junio'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['julio'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['agosto'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['septiembre'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['octubre'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['noviembre'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['diciembre'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['total'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['promedio'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoActual['cantidad_saldo'], 2, ".", ","),"cero_por_vacio")."</td>";
		}
		$htmlTb .= (count(explode(",",$valCadBusq[2])) > 1) ? "</tr>" : "";
		
		$htmlTb .= (count(explode(",",$valCadBusq[2])) > 1) ? "<tr align=\"right\" class=\"".$clase."\" height=\"24\">" : "";
		if (count($arrayAnoAnterior) > 0) {
			$htmlTb .= "<td align=\"center\" title=\"".$arrayAnoAnterior['id_cierre_anual']."\">".$arrayAnoAnterior['ano']."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['enero'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['febrero'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['marzo'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['abril'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['mayo'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['junio'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['julio'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['agosto'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['septiembre'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['octubre'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['noviembre'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['diciembre'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['total'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['promedio'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayAnoAnterior['cantidad_saldo'], 2, ".", ","),"cero_por_vacio")."</td>";
		}
		$htmlTb .= "</tr>";
		
		if (count($arrayAnoActual) > 0) {
			$arrayTotal[4] = $arrayAnoActual['ano'];
			$arrayTotal[5] += $arrayAnoActual['enero'];
			$arrayTotal[6] += $arrayAnoActual['febrero'];
			$arrayTotal[7] += $arrayAnoActual['marzo'];
			$arrayTotal[8] += $arrayAnoActual['abril'];
			$arrayTotal[9] += $arrayAnoActual['mayo'];
			$arrayTotal[10] += $arrayAnoActual['junio'];
			$arrayTotal[11] += $arrayAnoActual['julio'];
			$arrayTotal[12] += $arrayAnoActual['agosto'];
			$arrayTotal[13] += $arrayAnoActual['septiembre'];
			$arrayTotal[14] += $arrayAnoActual['octubre'];
			$arrayTotal[15] += $arrayAnoActual['noviembre'];
			$arrayTotal[16] += $arrayAnoActual['diciembre'];
			$arrayTotal[17] += $arrayAnoActual['total'];
			$arrayTotal[19] += $arrayAnoActual['cantidad_saldo'];
		}
	
		if (count($arrayAnoAnterior) > 0) {
			$arrayTotal2[4] = $arrayAnoAnterior['ano'];
			$arrayTotal2[5] += $arrayAnoAnterior['enero'];
			$arrayTotal2[6] += $arrayAnoAnterior['febrero'];
			$arrayTotal2[7] += $arrayAnoAnterior['marzo'];
			$arrayTotal2[8] += $arrayAnoAnterior['abril'];
			$arrayTotal2[9] += $arrayAnoAnterior['mayo'];
			$arrayTotal2[10] += $arrayAnoAnterior['junio'];
			$arrayTotal2[11] += $arrayAnoAnterior['julio'];
			$arrayTotal2[12] += $arrayAnoAnterior['agosto'];
			$arrayTotal2[13] += $arrayAnoAnterior['septiembre'];
			$arrayTotal2[14] += $arrayAnoAnterior['octubre'];
			$arrayTotal2[15] += $arrayAnoAnterior['noviembre'];
			$arrayTotal2[16] += $arrayAnoAnterior['diciembre'];
			$arrayTotal2[17] += $arrayAnoAnterior['total'];
			$arrayTotal2[19] += $arrayAnoAnterior['cantidad_saldo'];
		}
	}
	if ($contFila > 0) {
		if (count($arrayTotal) > 0) {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"6\">"."Total Página:<br>(Año ".($arrayTotal[4]).")"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[15], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[16], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[17], 2, ".", ",")."</td>";
				$htmlTb .= "<td>"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[19], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		if (count($arrayTotal2) > 0) {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"6\">"."Total Página:<br>(Año ".($arrayTotal2[4]).")"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[9], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[10], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[12], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[15], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[16], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[17], 2, ".", ",")."</td>";
				$htmlTb .= "<td>"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal2[19], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				if (in_array(1,explode(",",$valCadBusq[2]))) {
					$arrayAnoActual = array(
						"ano" => $row['ano'],
						"enero" => $row['enero'],
						"febrero" => $row['febrero'],
						"marzo" => $row['marzo'],
						"abril" => $row['abril'],
						"mayo" => $row['mayo'],
						"junio" => $row['junio'],
						"julio" => $row['julio'],
						"agosto" => $row['agosto'],
						"septiembre" => $row['septiembre'],
						"octubre" => $row['octubre'],
						"noviembre" => $row['noviembre'],
						"diciembre" => $row['diciembre'],
						"total" => $row['total'],
						"promedio" => $row['promedio'],
						"cantidad_saldo" => $row['cantidad_saldo']);
				}
				if (in_array(2,explode(",",$valCadBusq[2]))) {
					$arrayAnoAnterior = array(
						"ano" => $row['ano_p'],
						"enero" => $row['enero_p'],
						"febrero" => $row['febrero_p'],
						"marzo" => $row['marzo_p'],
						"abril" => $row['abril_p'],
						"mayo" => $row['mayo_p'],
						"junio" => $row['junio_p'],
						"julio" => $row['julio_p'],
						"agosto" => $row['agosto_p'],
						"septiembre" => $row['septiembre_p'],
						"octubre" => $row['octubre_p'],
						"noviembre" => $row['noviembre_p'],
						"diciembre" => $row['diciembre_p'],
						"total" => $row['total_p'],
						"promedio" => $row['promedio_p'],
						"cantidad_saldo" => $row['cantidad_saldo_p']);
				}
				
				if (count($arrayAnoActual) > 0) {
					$arrayTotalFinal[4] = $arrayAnoActual['ano'];
					$arrayTotalFinal[5] += $arrayAnoActual['enero'];
					$arrayTotalFinal[6] += $arrayAnoActual['febrero'];
					$arrayTotalFinal[7] += $arrayAnoActual['marzo'];
					$arrayTotalFinal[8] += $arrayAnoActual['abril'];
					$arrayTotalFinal[9] += $arrayAnoActual['mayo'];
					$arrayTotalFinal[10] += $arrayAnoActual['junio'];
					$arrayTotalFinal[11] += $arrayAnoActual['julio'];
					$arrayTotalFinal[12] += $arrayAnoActual['agosto'];
					$arrayTotalFinal[13] += $arrayAnoActual['septiembre'];
					$arrayTotalFinal[14] += $arrayAnoActual['octubre'];
					$arrayTotalFinal[15] += $arrayAnoActual['noviembre'];
					$arrayTotalFinal[16] += $arrayAnoActual['diciembre'];
					$arrayTotalFinal[17] += $arrayAnoActual['total'];
					$arrayTotalFinal[19] += $arrayAnoActual['cantidad_saldo'];
				}
			
				if (count($arrayAnoAnterior) > 0) {
					$arrayTotalFinal2[4] = $arrayAnoAnterior['ano'];
					$arrayTotalFinal2[5] += $arrayAnoAnterior['enero'];
					$arrayTotalFinal2[6] += $arrayAnoAnterior['febrero'];
					$arrayTotalFinal2[7] += $arrayAnoAnterior['marzo'];
					$arrayTotalFinal2[8] += $arrayAnoAnterior['abril'];
					$arrayTotalFinal2[9] += $arrayAnoAnterior['mayo'];
					$arrayTotalFinal2[10] += $arrayAnoAnterior['junio'];
					$arrayTotalFinal2[11] += $arrayAnoAnterior['julio'];
					$arrayTotalFinal2[12] += $arrayAnoAnterior['agosto'];
					$arrayTotalFinal2[13] += $arrayAnoAnterior['septiembre'];
					$arrayTotalFinal2[14] += $arrayAnoAnterior['octubre'];
					$arrayTotalFinal2[15] += $arrayAnoAnterior['noviembre'];
					$arrayTotalFinal2[16] += $arrayAnoAnterior['diciembre'];
					$arrayTotalFinal2[17] += $arrayAnoAnterior['total'];
					$arrayTotalFinal2[19] += $arrayAnoAnterior['cantidad_saldo'];
				}
			}
			
			if (count($arrayTotalFinal) > 0) {
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"6\">"."Total de Totales:<br>(Año ".($arrayTotalFinal[4]).")"."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[5],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[6],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[7],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[8],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[9],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[10],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[11],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[12],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[13],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[14],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[15],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[16],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[17],2)."</td>";
					$htmlTb .= "<td>"."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal[19],2)."</td>";
				$htmlTb .= "</tr>";
			}
			
			if (count($arrayTotalFinal2) > 0) {
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"6\">"."Total de Totales:<br>(Año ".($arrayTotalFinal2[4]).")"."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[5],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[6],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[7],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[8],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[9],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[10],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[11],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[12],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[13],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[14],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[15],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[16],2)."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[17],2)."</td>";
					$htmlTb .= "<td>"."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal2[19],2)."</td>";
				$htmlTb .= "</tr>";
			}
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"21\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadisticoVentas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadisticoVentas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEstadisticoVentas(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadisticoVentas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadisticoVentas(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"21\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoEstadisticoVentas","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEstadisticoVenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"exportarEstadisticoVentas");
$xajax->register(XAJAX_FUNCTION,"listaEstadisticoVentas");
?>