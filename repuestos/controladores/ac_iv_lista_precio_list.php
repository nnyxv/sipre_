<?php


function buscarPrecioArt($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAplicaIva'],
		implode(",",$frmBuscar['lstMostrarColumna']),
		implode(",",$frmBuscar['lstTipoArticulo']),
		implode(",",$frmBuscar['lstVerClasificacion']),
		implode(",",$frmBuscar['lstSaldos']),
		implode(",",$frmBuscar['lstPrecio']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPrecioArticulo(0, "art.codigo_articulo", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstPrecios($selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.porcentaje <> 0 AND precio.estatus IN (1,2) AND precio.lista_precio = 1 ORDER BY precio.id_precio ASC;");
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"lstPrecio\" name=\"lstPrecio\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
		$selected = ($selId == $rowPrecio['id_precio']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$rowPrecio['id_precio']."\">".htmlentities($rowPrecio['descripcion_precio'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstPrecio","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select multiple id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($idTipoArticulo == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['id_tipo_articulo']."\" ".$selected.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarPreciosArt($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAplicaIva'],
		implode(",",$frmBuscar['lstMostrarColumna']),
		implode(",",$frmBuscar['lstTipoArticulo']),
		implode(",",$frmBuscar['lstVerClasificacion']),
		implode(",",$frmBuscar['lstSaldos']),
		implode(",",$frmBuscar['lstPrecio']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_lista_precio_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirPrecioArt($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAplicaIva'],
		implode(",",$frmBuscar['lstMostrarColumna']),
		implode(",",$frmBuscar['lstTipoArticulo']),
		implode(",",$frmBuscar['lstVerClasificacion']),
		implode(",",$frmBuscar['lstSaldos']),
		implode(",",$frmBuscar['lstPrecio']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/iv_lista_precio_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaPrecioArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("precio.lista_precio = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("emp_precio.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("precio.id_precio IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	$queryPrecio = sprintf("SELECT DISTINCT precio.*
	FROM pg_empresa_precios emp_precio
		INNER JOIN pg_precios precio ON (emp_precio.id_precio = precio.id_precio) %s
	ORDER BY precio.id_precio ASC;", $sqlBusq);
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPrecio = mysql_num_rows($rsPrecio);
	
	
	$sqlBusq = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.posee_iva = %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.clasificacion IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
	}
	
	if (in_array(1,explode(",",$valCadBusq[5]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica > 0");
	}
	
	if (in_array(2,explode(",",$valCadBusq[5]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica <= 0");
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[7], "text"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s
		OR (SELECT COUNT(art_costo.id_articulo_costo) FROM iv_articulos_costos art_costo
			WHERE art_costo.id_articulo = vw_iv_art_emp.id_articulo
				AND art_costo.id_empresa = vw_iv_art_emp.id_empresa
				AND art_costo.id_articulo_costo LIKE %s) > 0)",
			valTpDato($valCadBusq[8], "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_iv_art_emp.id_empresa,
		vw_iv_art_emp.id_articulo,
		vw_iv_art_emp.codigo_articulo,
		vw_iv_art_emp.descripcion,
		art.posee_iva,
		vw_iv_art_emp.cantidad_disponible_fisica,
		vw_iv_art_emp.cantidad_disponible_logica
	FROM vw_iv_articulos_empresa vw_iv_art_emp
		INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows,
utf8_encode((in_array(1,explode(",",$valCadBusq[2]))) ? "Código" : ""));
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "32%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaPrecioArticulo", "8%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode((in_array(2,explode(",",$valCadBusq[2]))) ? "Unid. Disponible" : ""));
		$htmlTh .= "<td width=\"6%\">".utf8_encode("Lote")."</td>";
		$htmlTh .= "<td width=\"8%\">".utf8_encode((in_array(2,explode(",",$valCadBusq[2]))) ? "Unid. Disponible" : "")."</td>";
		while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
			$htmlTh .= "<td width=\"".(10 / $totalRowsPrecio)."%\">".utf8_encode($rowPrecio['descripcion_precio'])."</td>";
			$htmlTh .= "<td width=\"".(10 / $totalRowsPrecio)."%\">".utf8_encode("Impuesto")."</td>";
			$htmlTh .= "<td width=\"".(10 / $totalRowsPrecio)."%\">".utf8_encode("Precio Total")."</td>";
			
			$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
		}
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$idEmpresa = $row['id_empresa'];
		$idArticulo = $row['id_articulo'];
		
		$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		WHERE vw_iv_art_almacen_costo.id_articulo = %s
			AND vw_iv_art_almacen_costo.id_empresa = %s
			AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
			AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0
		ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", 
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
		
		$rowspan = ($totalRowsArtCosto > 0) ? "rowspan=\"".$totalRowsArtCosto."\"" : "";
		
		$imgAplicaIva = ($row['posee_iva'] == 1) ? "<img src=\"../img/iconos/accept.png\" title=\"Si Aplica Impuesto\"/>" : "";
		$classDisponible = ($row['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		$classDisponible = (in_array(2,explode(",",$valCadBusq[2]))) ? $classDisponible : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgAplicaIva."</td>";
			$htmlTb .= "<td ".$rowspan.">".((in_array(1,explode(",",$valCadBusq[2]))) ? elimCaracter(utf8_encode($row['codigo_articulo']),";") : "")."</td>";
			$htmlTb .= "<td ".$rowspan.">".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible." ".$rowspan.">";
				$htmlTb .= (in_array(2,explode(",",$valCadBusq[2]))) ? number_format($row['cantidad_disponible_logica'], 2, ".", ",") : "";
			$htmlTb .= "</td>";
			if ($arrayIdPrecio) {
				$contFila2 = 0;
				if ($totalRowsArtCosto > 0) {
					while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
						$contFila2++;
						
						$classDisponible = ($rowArtCosto['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
						$classDisponible = ($rowArtCosto['estatus_almacen_venta'] == 1) ? $classDisponible : "class=\"divMsjInfo4\"";
						$classDisponible = (in_array(2,explode(",",$valCadBusq[2]))) ? $classDisponible : "";
						
						$htmlTb .= "<td align=\"right\">".utf8_encode($rowArtCosto['id_articulo_costo'])."</td>";
						$htmlTb .= "<td align=\"right\" ".$classDisponible.">";
							$htmlTb .= (in_array(2,explode(",",$valCadBusq[2]))) ? number_format($rowArtCosto['cantidad_disponible_logica'], 2, ".", ",") : "";
						$htmlTb .= "</td>";
						
						$contFila3 = 0;
						foreach ($arrayIdPrecio as $indice => $valor) {
							$style = (fmod($contFila3, 2) == 0) ? "" : "style=\"font-weight:bold\"";
							$contFila3++;
							
							$queryArtPrecio = sprintf("SELECT
								art_precio.id_articulo_precio,
								art_precio.id_precio,
								art_precio.precio AS precio_unitario,
								
								(SELECT iva.observacion
								FROM iv_articulos_impuesto art_impsto
									INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
								WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
									AND art_impsto.id_articulo = art_precio.id_articulo
								LIMIT 1) AS descripcion_impuesto,
								
								(SELECT SUM(iva.iva)
								FROM iv_articulos_impuesto art_impsto
									INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
								WHERE iva.tipo IN (6,9,2)
									AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
								
								(art_precio.precio * (SELECT SUM(iva.iva)
													FROM iv_articulos_impuesto art_impsto
														INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
													WHERE iva.tipo IN (6,9,2)
														AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
								
								moneda.abreviacion AS abreviacion_moneda
							FROM iv_articulos_precios art_precio
								INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
							WHERE art_precio.id_articulo = %s
								AND art_precio.id_articulo_costo = %s
								AND art_precio.id_precio = %s;",
								valTpDato($row['id_articulo'], "int"),
								valTpDato($rowArtCosto['id_articulo_costo'], "int"),
								valTpDato($arrayIdPrecio[$indice][0], "int"));
							$rsArtPrecio = mysql_query($queryArtPrecio);
							if (!$rsArtPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
							
							$htmlTb .= "<td align=\"right\" ".$style.">".$rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['precio_unitario'], 2, ".", ",")."</td>";
							$htmlTb .= "<td align=\"right\" ".$style.">".$rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['monto_impuesto'], 2, ".", ",")."</td>";
							$htmlTb .= "<td align=\"right\" ".$style.">".$rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['precio_unitario'] + $rowArtPrecio['monto_impuesto'], 2, ".", ",")."</td>";
						}
						$htmlTb .= ($totalRowsArtCosto > 1) ? "</tr>" : "";
						$htmlTb .= ($totalRowsArtCosto > 1 && $contFila2 < $totalRowsArtCosto) ? "<tr align=\"left\" class=\"".$clase."\" height=\"24\">" : "";
						
						$arrayTotal[6] += $rowArtCosto['cantidad_disponible_logica'];
					}
				} else {
					$htmlTb .= "<td align=\"right\"></td>";
					$htmlTb .= "<td align=\"right\"></td>";
					
					$contFila3 = 0;
					foreach ($arrayIdPrecio as $indice => $valor) {
						$style = (fmod($contFila3, 2) == 0) ? "" : "style=\"font-weight:bold\"";
						$contFila3++;
						
						$htmlTb .= "<td align=\"right\">"."</td>";
						$htmlTb .= "<td align=\"right\">"."</td>";
						$htmlTb .= "<td align=\"right\">"."</td>";
					}
				}
			}
		$htmlTb .= (!($totalRowsArtCosto > 1)) ? "</tr>" : "";
		
		$arrayTotal[5] += (in_array(2,explode(",",$valCadBusq[2]))) ? $row['cantidad_disponible_logica'] : 0;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">".utf8_encode("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td colspan=\"".($totalRowsPrecio * 3)."\">"."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[5] += (in_array(2,explode(",",$valCadBusq[2]))) ? $row['cantidad_disponible_logica'] : 0;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">".utf8_encode("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td colspan=\"".($totalRowsPrecio * 3)."\">"."</td>";
			$htmlTb .= "</tr>";
		}
	}
		
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"".(7 + ($totalRowsPrecio * 3))."\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"".(7 + ($totalRowsPrecio * 3))."\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPreciosArt","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPrecioArt");
$xajax->register(XAJAX_FUNCTION,"cargaLstPrecios");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarPreciosArt");
$xajax->register(XAJAX_FUNCTION,"imprimirPrecioArt");
$xajax->register(XAJAX_FUNCTION,"listaPrecioArticulo");
?>