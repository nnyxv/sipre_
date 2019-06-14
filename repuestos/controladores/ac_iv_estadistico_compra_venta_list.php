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
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaEstadisticoVentas(0, "vw_iv_art_emp_datos_basicos.clasificacion", "ASC", $valBusq));
	
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
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_estadistico_compra_venta_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaEstadisticoVentas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("kardex.tipo_movimiento IN (1)");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("kardex.tipo_movimiento IN (3)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("vw_iv_art_emp_datos_basicos.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(kardex.fecha_movimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("DATE(kardex.fecha_movimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("vw_iv_art_emp_datos_basicos.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[3], "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(vw_iv_art_emp_datos_basicos.id_articulo = %s
		OR vw_iv_art_emp_datos_basicos.descripcion LIKE %s
		OR vw_iv_art_emp_datos_basicos.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[4], "int"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_iv_art_emp_datos_basicos.id_articulo,
		vw_iv_art_emp_datos_basicos.codigo_articulo,
		vw_iv_art_emp_datos_basicos.descripcion,
		vw_iv_art_emp_datos_basicos.clasificacion,
		SUM(kardex_compra_venta.cantidad_compra) AS total_cantidad_compra,
		SUM(kardex_compra_venta.cantidad_compra * (kardex_compra_venta.costo + kardex_compra_venta.costo_cargo - kardex_compra_venta.subtotal_descuento)) AS total_costo_compra,
		SUM(kardex_compra_venta.cantidad_venta) AS total_cantidad_venta,
		SUM(kardex_compra_venta.cantidad_venta * (kardex_compra_venta.precio - kardex_compra_venta.subtotal_descuento)) AS total_precio_venta
	FROM vw_iv_articulos_empresa_datos_basicos vw_iv_art_emp_datos_basicos
		LEFT JOIN (SELECT 
				kardex.id_kardex,
				(CASE kardex.tipo_movimiento
					WHEN 1 THEN -- COMPRA
						(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN -- ENTRADA CON VALE
								(CASE kardex.id_modulo
									WHEN 0 THEN
										(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
									WHEN 1 THEN
										(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								END)
							WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
								(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
						END)
					WHEN 3 THEN -- VENTA
						(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
					WHEN 4 THEN -- SALIDA
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN -- SALIDA CON VALE
								(CASE kardex.id_modulo
									WHEN 0 THEN -- REPUESTOS
										(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
									WHEN 1 THEN -- SERVICIOS
										(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
								END)
							WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
								(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
						END)
				END) AS id_empresa,
				kardex.id_articulo,
				0 AS cantidad_venta,
				0 AS precio,
				kardex.cantidad AS cantidad_compra,
				kardex.costo,
				kardex.costo_cargo,
				kardex.subtotal_descuento
			FROM iv_kardex kardex %s
			
			UNION 
			
			SELECT 
				kardex.id_kardex,
				(CASE kardex.tipo_movimiento
					WHEN 1 THEN -- COMPRA
						(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
					WHEN 2 THEN -- ENTRADA
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN -- ENTRADA CON VALE
								(CASE kardex.id_modulo
									WHEN 0 THEN
										(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
									WHEN 1 THEN
										(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								END)
							WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
								(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
						END)
					WHEN 3 THEN -- VENTA
						(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
					WHEN 4 THEN -- SALIDA
						(CASE kardex.tipo_documento_movimiento
							WHEN 1 THEN -- SALIDA CON VALE
								(CASE kardex.id_modulo
									WHEN 0 THEN -- REPUESTOS
										(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
									WHEN 1 THEN -- SERVICIOS
										(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
								END)
							WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
								(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
						END)
				END) AS id_empresa,
				kardex.id_articulo,
				kardex.cantidad AS cantidad_venta,
				kardex.precio,
				0 AS cantidad_compra,
				0 AS costo,
				0 AS costo_cargo,
				kardex.subtotal_descuento
			FROM iv_kardex kardex %s) kardex_compra_venta ON (vw_iv_art_emp_datos_basicos.id_articulo = kardex_compra_venta.id_articulo)
				AND (vw_iv_art_emp_datos_basicos.id_empresa = kardex_compra_venta.id_empresa) %s
	GROUP BY vw_iv_art_emp_datos_basicos.id_articulo
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
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "44%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaEstadisticoVentas", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
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
			$htmlTb .= "<td align=\"left\">".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['descripcion'])."</td>";
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
			$htmlTb .= "<td>".number_format($row['total_cantidad_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($row['total_costo_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($row['total_cantidad_venta'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($row['total_precio_venta'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal['total_cantidad_compra'] += $row['total_cantidad_compra'];
		$arrayTotal['total_costo_compra'] += $row['total_costo_compra'];
		$arrayTotal['total_costo_compra'] += $row['total_cantidad_venta'];
		$arrayTotal['total_precio_venta'] += $row['total_precio_venta'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_cantidad_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_costo_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_costo_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_precio_venta'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal['total_cantidad_compra'] += $row['total_cantidad_compra'];
				$arrayTotalFinal['total_costo_compra'] += $row['total_costo_compra'];
				$arrayTotalFinal['total_cantidad_venta'] += $row['total_cantidad_venta'];
				$arrayTotalFinal['total_precio_venta'] += $row['total_precio_venta'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['total_cantidad_compra'],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['total_costo_compra'],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['total_cantidad_venta'],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['total_precio_venta'],2)."</td>";
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