<?php


function buscarUnidadBasica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstCatalogoBuscar'],
		$frmBuscar['lstModelo']);
	
	$objResponse->loadCommands(listaEstadoUnidadRsm(0, "nom_uni_bas", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstModelo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_modelo ORDER BY nom_modelo");
	$rs = mysql_query($query) or die(mysql_error());
	$html = "<select id=\"lstModelo\" name=\"lstModelo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modelo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modelo']."\">".utf8_encode($row['nom_modelo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModelo","innerHTML",$html);
	
	return $objResponse;
}

function listaEstadoUnidadRsm($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_an_estado_unidad_fisica_rsm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_an_estado_unidad_fisica_rsm.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("catalogo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modelo = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	$query = sprintf("SELECT *,
		(cant_asignada + cant_aceptada + cant_confirmada + cant_pedido + cant_transito + cant_siniestrado + cant_por_registrar + cant_disponible + cant_reservado) AS cant_total
	FROM vw_an_estado_unidad_fisica_rsm %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "10%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unidad Básica"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "10%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Marca"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "10%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Modelo"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "10%", $pageNum, "nom_version", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Versión"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_asignada", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Asignación Planta"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_aceptada", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Respuesta Asignación"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_confirmada", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Confirmación Planta"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Pedido"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_transito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Transitorio"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_siniestrado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Siniestrado"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_por_registrar", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Por Registrar"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_disponible", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Disponible"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_reservado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Reservado"));
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidadRsm", "6%", $pageNum, "cant_total", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total"));
	$htmlTh .= "</tr>";
	
	$arrayTotal = NULL;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_marca'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_modelo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_version'])."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_asignada'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_aceptada'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_confirmada'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_pedido'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_transito'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_siniestrado'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_por_registrar'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_disponible'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\">".valTpDato(number_format($row['cant_reservado'], 2, ".", ","), "cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"trResaltarTotal\">".number_format($row['cant_total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[5] += $row['cant_asignada'];
		$arrayTotal[6] += $row['cant_aceptada'];
		$arrayTotal[7] += $row['cant_confirmada'];
		$arrayTotal[8] += $row['cant_pedido'];
		$arrayTotal[9] += $row['cant_transito'];
		$arrayTotal[10] += $row['cant_siniestrado'];
		$arrayTotal[11] += $row['cant_por_registrar'];
		$arrayTotal[12] += $row['cant_disponible'];
		$arrayTotal[13] += $row['cant_reservado'];
		$arrayTotal[14] += $row['cant_total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">".utf8_encode("Total Página:")."</td>";
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
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[5] += $row['cant_asignada'];
				$arrayTotalFinal[6] += $row['cant_aceptada'];
				$arrayTotalFinal[7] += $row['cant_confirmada'];
				$arrayTotalFinal[8] += $row['cant_pedido'];
				$arrayTotalFinal[9] += $row['cant_transito'];
				$arrayTotalFinal[10] += $row['cant_siniestrado'];
				$arrayTotalFinal[11] += $row['cant_por_registrar'];
				$arrayTotalFinal[12] += $row['cant_disponible'];
				$arrayTotalFinal[13] += $row['cant_reservado'];
				$arrayTotalFinal[14] += $row['cant_total'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[9], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoUnidadRsm(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoUnidadRsm(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEstadoUnidadRsm(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoUnidadRsm(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoUnidadRsm(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"14\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoUnidadResumen","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"cargaLstModelo");
$xajax->register(XAJAX_FUNCTION,"listaEstadoUnidadRsm");
?>