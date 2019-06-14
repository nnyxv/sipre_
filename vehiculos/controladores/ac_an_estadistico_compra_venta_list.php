<?php


function buscarEstadisticoVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaEstadisticoVentas(0, "vw_iv_modelo.nom_uni_bas", "ASC", $valBusq));
	
	return $objResponse;
}

function exportarEstadisticoVentas($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_estadistico_compra_venta_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaEstadisticoVentas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("kardex.tipoMovimiento IN (1)");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("kardex.tipoMovimiento IN (3)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("unidad_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("kardex.fechaMovimiento BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("kardex.fechaMovimiento BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(vw_iv_modelo.id_uni_bas = %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_marca LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s)",
			valTpDato($valCadBusq[3], "int"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		vw_iv_modelo.nom_ano,
		SUM(kardex_compra_venta.cantidad_compra) AS total_cantidad_compra,
		SUM(kardex_compra_venta.cantidad_compra * kardex_compra_venta.costo) AS total_costo_compra,
		SUM(kardex_compra_venta.cantidad_venta) AS total_cantidad_venta,
		SUM(kardex_compra_venta.cantidad_venta * kardex_compra_venta.precio) AS total_precio_venta
	FROM sa_unidad_empresa unidad_emp
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
		LEFT JOIN (SELECT 
				kardex.idKardex,
				(CASE kardex.tipoMovimiento
					WHEN 1 THEN -- COMPRA
						(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN -- ENTRADA CON VALE
								(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
								(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
						END)
					WHEN 3 THEN -- VENTA
						(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
					WHEN 4 THEN -- SALIDA
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN -- SALIDA CON VALE
								(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
							WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
								(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
						END)
				END) AS id_empresa,
				kardex.idUnidadBasica,
				0 AS cantidad_venta,
				0 AS precio,
				kardex.cantidad AS cantidad_compra,
				(CASE kardex.tipoMovimiento
					WHEN 1 THEN -- COMPRA
						uni_fis.precio_compra
					WHEN 2 THEN -- ENTRADA
						(CASE tipo_documento_movimiento
							WHEN 1 THEN -- ENTRADA CON VALE
								uni_fis.precio_compra
							WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
								(SELECT fact_vent_det_vehic.costo_compra
								FROM cj_cc_notacredito nota_cred
									INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
								WHERE nota_cred.idNotaCredito =kardex.id_documento
									AND nota_cred.idDepartamentoNotaCredito = 2
									AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
						END)
					WHEN 3 THEN -- VENTA
						(SELECT fact_vent_det_vehic.costo_compra FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
						WHERE fact_vent_det_vehic.id_factura = kardex.id_documento
							AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					WHEN 4 THEN -- SALIDA
						(CASE tipo_documento_movimiento
							WHEN 1 THEN -- SALIDA CON VALE
								uni_fis.precio_compra
							WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
								uni_fis.precio_compra
						END)
				END) AS costo
			FROM an_kardex kardex
				INNER JOIN an_unidad_fisica uni_fis ON (kardex.idUnidadFisica = uni_fis.id_unidad_fisica) %s
			
			UNION 
			
			SELECT 
				kardex.idKardex,
				(CASE kardex.tipoMovimiento
					WHEN 1 THEN -- COMPRA
						(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN -- ENTRADA CON VALE
								(SELECT id_empresa FROM an_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
								(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
						END)
					WHEN 3 THEN -- VENTA
						(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
					WHEN 4 THEN -- SALIDA
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN -- SALIDA CON VALE
								(SELECT id_empresa FROM an_vale_salida WHERE id_vale_salida = kardex.id_documento)
							WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
								(SELECT id_empresa FROM cp_notacredito WHERE id_notacredito = kardex.id_documento)
						END)
				END) AS id_empresa,
				kardex.idUnidadBasica,
				kardex.cantidad AS cantidad_venta,
				(CASE tipoMovimiento
					WHEN 1 THEN -- COMPRA
						uni_fis.precio_compra
					WHEN 2 THEN -- ENTRADA
						(CASE tipo_documento_movimiento
							WHEN 1 THEN -- ENTRADA CON VALE
								uni_fis.precio_compra
							WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
								(SELECT fact_vent_det_vehic.precio_unitario
								FROM cj_cc_notacredito nota_cred
									INNER JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (nota_cred.idDocumento = fact_vent_det_vehic.id_factura)
								WHERE nota_cred.idNotaCredito = kardex.id_documento
									AND nota_cred.idDepartamentoNotaCredito = 2
									AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
						END)
					WHEN 3 THEN -- VENTA
						(SELECT fact_vent_det_vehic.precio_unitario FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
						WHERE fact_vent_det_vehic.id_factura = kardex.id_documento
							AND fact_vent_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
					WHEN 4 THEN -- SALIDA
						(CASE tipo_documento_movimiento
							WHEN 1 THEN -- SALIDA CON VALE
								uni_fis.precio_compra
							WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
								uni_fis.precio_compra
						END)
				END) AS precio,
				0 AS cantidad_compra,
				0 AS costo
			FROM an_kardex kardex
				INNER JOIN an_unidad_fisica uni_fis ON (kardex.idUnidadFisica = uni_fis.id_unidad_fisica) %s) kardex_compra_venta ON (vw_iv_modelo.id_uni_bas = kardex_compra_venta.idUnidadBasica)
				AND (unidad_emp.id_empresa = kardex_compra_venta.id_empresa) %s
	GROUP BY vw_iv_modelo.id_uni_bas
	HAVING SUM(kardex_compra_venta.cantidad_compra) > 0
		OR SUM(kardex_compra_venta.cantidad_venta) > 0", $sqlBusq, $sqlBusq2, $sqlBusq3);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "12%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad Básica");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "44%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "4%", $pageNum, "nom_ano", $campOrd, $tpOrd, $valBusq, $maxRows, "Año");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "8%", $pageNum, "total_cantidad_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Cantidad Compra");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "10%", $pageNum, "total_costo_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Compra");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "8%", $pageNum, "total_cantidad_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Cantidad Venta");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "10%", $pageNum, "total_precio_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Venta");
	$htmlTh .= "</tr>";
	
	$arrayTotal = NULL;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nom_ano'])."</td>";
			$htmlTb .= "<td>".number_format($row['total_cantidad_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($row['total_costo_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($row['total_cantidad_venta'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($row['total_precio_venta'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[5] += $row['total_cantidad_compra'];
		$arrayTotal[6] += $row['total_costo_compra'];
		$arrayTotal[7] += $row['total_cantidad_venta'];
		$arrayTotal[8] += $row['total_precio_venta'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[5] += $row['total_cantidad_compra'];
				$arrayTotalFinal[6] += $row['total_costo_compra'];
				$arrayTotalFinal[7] += $row['total_cantidad_venta'];
				$arrayTotalFinal[8] += $row['total_precio_venta'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[5],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[6],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[7],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[8],2)."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadisticoVentas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadisticoVentas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadisticoVentas(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"20\">";
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
$xajax->register(XAJAX_FUNCTION,"exportarEstadisticoVentas");
$xajax->register(XAJAX_FUNCTION,"listaEstadisticoVentas");
?>