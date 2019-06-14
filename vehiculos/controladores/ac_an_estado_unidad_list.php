<?php


function buscarUnidadBasica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAlmacen'],
		$frmBuscar['lstEstadoVenta']);
	
	$objResponse->loadCommands(listaEstadoUnidad(0, "nom_uni_bas", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstAlmacen($idEmpresa = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT * FROM an_almacen %s ORDER BY nom_almacen", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstAlmacen\" name=\"lstAlmacen\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".htmlentities($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAlmacen","innerHTML",$html);
	
	return $objResponse;
}

function listaEstadoUnidad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_an_estado_unidad.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_an_estado_unidad.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_almacen = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		
		if ($valCadBusq[2] == 1 || $valCadBusq[2] == 2) {
			$sqlBusq .= ($valCadBusq[2] == 1) ? $cond."estatus_asignacion = 1" : $cond."estatus_asignacion = 3";
		} else {
			$sqlBusq .= $cond.sprintf("estado_venta LIKE %s",
				valTpDato($valCadBusq[2], "text"));
		}
	}
	
	if ($valCadBusq[2] == 1 || $valCadBusq[2] == 2) {
		$query = sprintf("SELECT * FROM vw_an_estado_unidad_asignacion vw_an_estado_unidad %s", $sqlBusq);
		
		$tituloCampoDocumento = "Nro. Ref. Asignación";
		$campoReferencia = "referencia_asignacion";
		switch ($valCadBusq[2]) {
			case 1 : $campoCantidad = "cantidadAsignada"; break;
			case 2 : $campoCantidad = "cantidadConfirmada"; break;
		}
	} else {
		$query = sprintf("SELECT *, 1 AS cantidad FROM vw_an_estado_unidad_fisica vw_an_estado_unidad %s", $sqlBusq);
		
		$tituloCampoDocumento = "Nro. Factura Compra";
		$campoReferencia = "numero_factura_proveedor";
		$campoCantidad = "cantidad";
	}
	
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
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "8%", $pageNum, $campoReferencia, $campOrd, $tpOrd, $valBusq, $maxRows, $tituloCampoDocumento);
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "12%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad Básica");
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "12%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "13%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo");
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "13%", $pageNum, "nom_version", $campOrd, $tpOrd, $valBusq, $maxRows, "Versión");
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "12%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "12%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "12%", $pageNum, "pvp_venta1", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");
		$htmlTh .= ordenarCampo("xajax_listaEstadoUnidad", "8%", $pageNum, $campoCantidad, $campOrd, $tpOrd, $valBusq, $maxRows, "Unidades");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".$row[$campoReferencia]."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_marca'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_modelo'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_version'])."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities($row['serial_carroceria'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".htmlentities($row['placa'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['pvp_venta1'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row[$campoCantidad], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoUnidad(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoUnidad(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEstadoUnidad(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoUnidad(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoUnidad(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoUnidad","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacen");
$xajax->register(XAJAX_FUNCTION,"listaEstadoUnidad");
?>