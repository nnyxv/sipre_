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
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstProveedor'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstTipoArticulo'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCosto(0, "art.codigo_articulo", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstProveedor(){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT prov.id_proveedor, prov.nombre FROM cp_proveedor prov ORDER BY nombre";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstProveedor\" name=\"lstProveedor\" class=\"inputHabilitado\" onChange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_proveedor']."\">".utf8_encode($row['nombre'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstProveedor","innerHTML",$html);
	
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
		$selected = ($idTipoArticulo == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['id_tipo_articulo']."\" ".$selected.">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarCostos($frmBuscar) {
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
		$frmBuscar['lstProveedor'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstTipoArticulo'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_catalogo_costos_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaCosto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != ""
	&& $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[5], "text"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art_costo.id_articulo_costo LIKE %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[6], "int"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT art_costo.*,
		art_emp.id_empresa,
		prov.id_proveedor,
		prov.nombre AS nombre_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		art.codigo_articulo_prov,
		art.id_tipo_articulo,
		art_emp.clasificacion,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0)) AS existencia,
		IFNULL(art_costo.cantidad_reservada, 0) AS cantidad_reservada,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0) - IFNULL(art_costo.cantidad_reservada, 0)) AS cantidad_disponible_fisica,
		IFNULL(art_costo.cantidad_espera, 0) AS cantidad_espera,
		IFNULL(art_costo.cantidad_bloqueada, 0) AS cantidad_bloqueada,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0) - IFNULL(art_costo.cantidad_reservada, 0) - IFNULL(art_costo.cantidad_espera, 0) - IFNULL(art_costo.cantidad_bloqueada, 0)) AS cantidad_disponible_logica,
		moneda_local.abreviacion AS abreviacion_moneda_local,
		moneda_origen.abreviacion AS abreviacion_moneda_origen,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_articulos art
		INNER JOIN iv_articulos_empresa art_emp ON (art.id_articulo = art_emp.id_articulo)
		LEFT JOIN iv_articulos_costos art_costo ON (art_emp.id_empresa = art_costo.id_empresa)
			AND (art_emp.id_articulo = art_costo.id_articulo)
		LEFT JOIN cp_proveedor prov ON (art_costo.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (art_costo.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_origen ON (art_costo.id_moneda_origen = moneda_origen.idmoneda)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCosto", "12%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "14%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "10%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "14%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Decripción"));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "8%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código Prov."));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Lote");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "8%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "6%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unid. Disponible"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $row['id_empresa']);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			return $objResponse->alert($ResultConfig12[1]);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
		$classCosto = (in_array($ResultConfig12, array(1,3))) ? "divMsjInfo" : "";
		$classCostoProm = (!in_array($ResultConfig12, array(1,3))) ? "divMsjInfo" : "";
		
		$classDisponible = ($row['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($row['costo'],3) : round($row['costo_promedio'],3);
				
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['fecha'] != "") ? date(spanDateFormat,strtotime($row['fecha'])) : "xx-xx-xxxx")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_articulo_prov'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".htmlentities("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".htmlentities("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".htmlentities("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".htmlentities("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".htmlentities("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".htmlentities("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_articulo_costo']."</td>";
			$htmlTb .= "<td align=\"right\" class=\"".$classCosto."\">";
				$htmlTb .= $row['abreviacion_moneda_local'].number_format($row['costo'], 2, ".", ",");
				$htmlTb .= ($row['costo_origen'] != 0) ? "<br>".$row['abreviacion_moneda_origen'].number_format($costoUnitario, 2, ".", ",") : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($row['cantidad_disponible_logica'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[11] += $row['cantidad_disponible_logica'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">".htmlentities("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[11] += $row['cantidad_disponible_logica'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">".htmlentities("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
		
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCosto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstProveedor");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarCostos");
$xajax->register(XAJAX_FUNCTION,"listaCosto");
?>