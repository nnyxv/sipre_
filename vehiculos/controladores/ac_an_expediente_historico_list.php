<?php


function buscarExpediente($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaExpediente(0, "id_expediente", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarExpediente($idExpediente, $frmExpediente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmExpediente['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmExpediente['cbx1'];
	$contFila1 = $arrayObj1[count($arrayObj)-1];
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmFactura:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valor));
		}
	}
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmCargo:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valor1));
		}
	}
	
	$arrayObj = NULL;
	$contFila = 0;
	
	$arrayObj1 = NULL;
	$contFila1 = 0;
	
	// BUSCA LOS DATOS DEL EXPEDIENTE
	$query = sprintf("SELECT * FROM an_expediente
	WHERE id_expediente = %s;",
		valTpDato($idExpediente, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdExpediente","value",$idExpediente);
	$objResponse->assign("txtNumeroExpediente","value",$row['numero_expediente']);
	$objResponse->assign("txtNumeroEmbarque","value",$row['numero_embarque']);
	
	$objResponse->loadCommands(listaFacturaCompra(0, "id_factura", "DESC", $idExpediente));
	$objResponse->loadCommands(listaOtrosCargos(0, "gasto.nombre", "ASC", $idExpediente));
	
	return $objResponse;
}

function listaExpediente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus = 1");
	
	/*if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_comp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}*/
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_expediente LIKE %s
		OR numero_embarque LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_expediente %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "8%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "46%", $pageNum, "numero_expediente", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Expediente");
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "46%", $pageNum, "numero_embarque", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Embarque / BL");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Abierto\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Cerrado\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['numero_expediente'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['numero_embarque'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblExpediente', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Detalle\"/></a>",
					$contFila,
					$row['id_expediente']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaExpediente","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaFacturaCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("expediente_det_fact.id_expediente = %s",
		valTpDato($valCadBusq[0], "text"));
	
	$query = sprintf("SELECT 
		fact_comp.id_factura,
		fact_comp.id_modo_compra,
		fact_comp.fecha_origen,
		fact_comp.fecha_factura_proveedor,
		fact_comp.numero_factura_proveedor,
		ped_comp.idPedidoCompra,
		asig.referencia_asignacion,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(SELECT COUNT(fact_compra_det_unidad.id_factura) AS items
		FROM cp_factura_detalle_unidad fact_compra_det_unidad
		WHERE fact_compra_det_unidad.id_factura = fact_comp.id_factura) AS items,
			
		(
			(IFNULL(fact_comp.subtotal_factura, 0) - IFNULL(fact_comp.subtotal_descuento, 0))
			+
			IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
						AND fact_compra_gasto.id_modo_gasto IN (1,3)), 0)
			+
			IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva fact_compra_iva
					WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)
		) AS total,
			
		moneda_local.abreviacion AS abreviacion_moneda_local,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = fact_comp.id_factura
			AND fact_comp.id_modulo = 0
		LIMIT 1) AS idRetencionCabezera,
		
		ped_comp.estatus_pedido AS estatus_pedido_compra
	FROM cp_factura fact_comp
		INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
		INNER JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda)
		INNER JOIN an_expediente_detalle_factura expediente_det_fact ON (fact_comp.id_factura = expediente_det_fact.id_factura)
		INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (fact_comp.id_factura = fact_comp_det_unidad.id_factura)
		INNER JOIN an_solicitud_factura ped_comp_det ON (fact_comp_det_unidad.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion) %s
	
	UNION
	
	SELECT 
		fact_comp.id_factura_compra,
		fact_comp.id_modo_compra,
		fact_comp.fecha_origen,
		fact_comp.fecha_factura_proveedor,
		fact_comp.numero_factura_proveedor,
		ped_comp.idPedidoCompra,
		asig.referencia_asignacion,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(SELECT COUNT(fact_compra_det_unidad.id_factura_compra) AS items
		FROM an_factura_compra_detalle_unidad fact_compra_det_unidad
		WHERE fact_compra_det_unidad.id_factura_compra = fact_comp.id_factura_compra) AS items,
			
		(
			(IFNULL(fact_comp.subtotal_factura, 0) - IFNULL(fact_comp.subtotal_descuento, 0))
			+
			IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM an_factura_compra_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura_compra = fact_comp.id_factura_compra
						AND fact_compra_gasto.id_modo_gasto IN (1,3)), 0)
			+
			IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM an_factura_compra_iva fact_compra_iva
					WHERE fact_compra_iva.id_factura_compra = fact_comp.id_factura_compra), 0)
		) AS total,
			
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
		
		NULL AS idRetencionCabezera,
		
		ped_comp.estatus_pedido AS estatus_pedido_compra
	FROM an_factura_compra fact_comp
		INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
		INNER JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (fact_comp.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN an_expediente_detalle_factura expediente_det_fact ON (fact_comp.id_factura_compra = expediente_det_fact.id_factura)
		INNER JOIN an_factura_compra_detalle_unidad fact_comp_det_unidad ON (fact_comp.id_factura_compra = fact_comp_det_unidad.id_factura_compra)
		INNER JOIN an_solicitud_factura ped_comp_det ON (fact_comp_det_unidad.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion) %s", $sqlBusq, $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= "<td width=\"26%\">Tipo de Pedido";
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "idPedidoCompra", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "referencia_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "16%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "6%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "12%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td>";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						switch($arrayFactDet[$indice][0]) {
							case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Pendiente por Terminar\"/>"; break;
							case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Pedido Cerrado\"/>"; break;
							case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Orden Aprobada\"/>"; break;
							case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
							case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
							case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Pedido Anulado\"/>"; break;
							default : $imgEstatusPedido = "";
						}
						
						$htmlTb .= "<tr>";
							$htmlTb .= "<td>".$imgEstatusPedido."</td>";
							$htmlTb .= "<td align=\"left\">".htmlentities($arrayFactDet[$indice][1])."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['idPedidoCompra']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['referencia_asignacion']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda_local']."</td>";
			$htmlTb .= "<td>";
			if ($row['id_factura'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro Compra PDF\"/>",
					$row['id_factura']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir Comprobante de Retención\"/>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaFacturaCompra","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaOtrosCargos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("expediente_det_cargo.id_expediente = %s",
		valTpDato($valCadBusq[0], "text"));
	
	$query = sprintf("SELECT 
		fact_comp_cargo.id_factura,
		gasto.id_gasto,
		gasto.nombre,
		fact_comp_cargo.fecha_origen,
		fact_comp_cargo.numero_factura_proveedor,
		fact_comp_cargo.numero_control_factura,
		prov.id_proveedor,
		prov.nombre AS nombre_proveedor,
		fact_comp_cargo.subtotal_factura,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = fact_comp_cargo.id_factura
			AND fact_comp_cargo.id_modulo = 3
		LIMIT 1) AS idRetencionCabezera
		
	FROM an_expediente_detalle_cargos expediente_det_cargo
		INNER JOIN cp_factura fact_comp_cargo ON (expediente_det_cargo.id_factura_compra_cargo = fact_comp_cargo.id_factura)
		INNER JOIN pg_gastos gasto ON (expediente_det_cargo.id_gasto = gasto.id_gasto)
		INNER JOIN cp_proveedor prov ON (fact_comp_cargo.id_proveedor = prov.id_proveedor) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargos", "32%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Gasto");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargos", "11%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Reg. Compra");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargos", "10%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargos", "10%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargos", "25%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargos", "12%", $pageNum, "subtotal_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Subtotal");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$documentoAsociado = ($row['asocia_documento'] == 1) ? "Si" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_factura_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_control_factura'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['subtotal_factura'], 2, ".", ",")." ".$row['abreviacion_moneda_local']."</td>";
			$htmlTb .= "<td>";
			if ($row['id_factura'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../compras/reportes/ga_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro Compra PDF\"/>",
					$row['id_factura']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir Comprobante de Retención\"/>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaOtrosCargos","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarExpediente");
$xajax->register(XAJAX_FUNCTION,"cargarExpediente");
$xajax->register(XAJAX_FUNCTION,"formExpediente");
$xajax->register(XAJAX_FUNCTION,"listaExpediente");
$xajax->register(XAJAX_FUNCTION,"listaFacturaCompra");
$xajax->register(XAJAX_FUNCTION,"listaOtrosCargos");
?>