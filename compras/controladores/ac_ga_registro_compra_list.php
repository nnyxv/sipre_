<?php
function buscar($valForm) {
	$objResponse = new xajaxResponse();
	
	$numSolicitud = (strpos($valForm["txtNroSolicitud"],"-")) ? substr($valForm["txtNroSolicitud"], 4) : $valForm["txtNroSolicitud"];
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$numSolicitud,
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listadoPedidosCompra(0, "fecha_orden", "DESC", $valBusq));
		
	return $objResponse;
}

function desaprobarOrden($idOrden, $hddIdItm, $valFormListaPedidoVenta) {
	$objResponse = new xajaxResponse();
	mysql_query("START TRANSACTION;");
	
	$queryPedDet = sprintf("SELECT * FROM ga_orden_compra_detalle
	WHERE id_orden_compra = %s;",
		valTpDato($idOrden, "int"));
	$rsPedDet = mysql_query($queryPedDet);
	if (!$rsPedDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	while ($rowPedDet = mysql_fetch_assoc($rsPedDet)) {
		$idArticulo = $rowPedDet['id_articulo'];
		$idCasilla = $rowPedDet['id_casilla'];
		$cantidadPedida = doubleval($rowPedDet['cantidad']);
		$cantidadPendiente = doubleval($rowPedDet['pendiente']);
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO EN LA UBICACION SELECCIONADA
		/*$updateSQL = sprintf("UPDATE ga_articulos_almacen SET
			cantidad_pedida = cantidad_pedida - (%s)
		WHERE id_articulo = %s
			AND id_casilla = %s;",
			valTpDato(doubleval($cantidadPendiente), "double"),
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");*/
		
		//Actualiza el estado de los Items de la orden de compra
		$updateSQL = sprintf("UPDATE ga_orden_compra_detalle SET
			estatus = %s
		WHERE id_orden_compra = %s;",
			valTpDato(2, "int"), // 0 = En Espera, 1 = Despachado, 2 = Anulado
			valTpDato($idOrden, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	// ACTUALIZA EL ESTATUS DE LA ORDEN DE COMPRA
	$updateSQL = sprintf("UPDATE ga_orden_compra SET
		estatus_orden_compra = %s
	WHERE id_orden_compra = %s
		AND estatus_orden_compra = 2;",
		valTpDato(5, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelto, 5 = Anulado
		valTpDato($idOrden, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Orden de Compra Anulado con Éxito");
	
	$objResponse->loadCommands(listadoPedidosCompra(
		$valFormListaPedidoVenta['pageNum'],
		$valFormListaPedidoVenta['campOrd'],
		$valFormListaPedidoVenta['tpOrd'],
		$valFormListaPedidoVenta['valBusq']));
	
	return $objResponse;
}

function listadoPedidosCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_orden_compra = %s",
		valTpDato(2, "int"));
			
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_solicitud = %s
									OR id_orden_compra = %s)",
			valTpDato($valCadBusq[1], "int"),
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_orden BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"));
	}
	
	if ($valCadBusq[4]  != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_orden_compra LIKE %s
		OR numero_solicitud LIKE %s
		OR rif_proveedor LIKE %s
		OR observaciones LIKE %s
		OR id_proveedor LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT *,
		CONCAT_WS('-',(SELECT codigo_empresa 
						FROM pg_empresa WHERE pg_empresa.id_empresa = vw_ga_facturas_compra.id_empresa),
						numero_solicitud) AS numero_solicitud,
						
		(SELECT COUNT(ord_comp_det.id_orden_compra) AS items
		FROM ga_orden_compra_detalle ord_comp_det
		WHERE (ord_comp_det.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)) AS items,
		
		(SELECT SUM(ord_comp_det.cantidad) AS pedidos
		FROM ga_orden_compra_detalle ord_comp_det
		WHERE (ord_comp_det.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)) AS pedidos,
		
		(SELECT SUM(ord_comp_det.pendiente) AS pendientes
		FROM ga_orden_compra_detalle ord_comp_det
		WHERE (ord_comp_det.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)) AS pendientes,
		
		(CASE
			WHEN (vw_ga_facturas_compra.id_factura IS NULL) THEN
				(((vw_ga_facturas_compra.subtotal_pedido - vw_ga_facturas_compra.subtotal_descuento_pedido)
				+
				IFNULL((SELECT SUM(ord_gasto.monto) AS total_gasto
					FROM ga_orden_compra_gasto ord_gasto
					WHERE (ord_gasto.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)), 0))
					
				+
				IFNULL((SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM ga_orden_compra_iva ped_iva
					WHERE (ped_iva.id_orden_compra = vw_ga_facturas_compra.id_orden_compra)), 0))
			WHEN (vw_ga_facturas_compra.id_factura IS NOT NULL) THEN
				(((vw_ga_facturas_compra.subtotal_factura - vw_ga_facturas_compra.subtotal_descuento)
				+
				IFNULL((SELECT SUM(fac_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fac_gasto
					WHERE (fac_gasto.id_factura = vw_ga_facturas_compra.id_factura)), 0))
				+
				IFNULL((SELECT SUM(fac_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva fac_iva
					WHERE (fac_iva.id_factura = vw_ga_facturas_compra.id_factura)), 0))
		END) AS total
		
	FROM vw_ga_facturas_compra %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		
		$htmlTh .= ordenarCampo("xajax_listadoPedidosCompra", "15%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoPedidosCompra", "5%", $pageNum, "fecha_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Orden");
		$htmlTh .= ordenarCampo("xajax_listadoPedidosCompra", "5%", $pageNum, "id_orden_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Orden");
		$htmlTh .= ordenarCampo("xajax_listadoPedidosCompra", "5%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listadoPedidosCompra", "30%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listadoPedidosCompra", "30%", $pageNum, "observacioes", $campOrd, $tpOrd, $valBusq, $maxRows, "Observación");
		$htmlTh .= ordenarCampo("xajax_listadoPedidosCompra", "5%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listadoPedidosCompra", "5%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Orden");
		$htmlTh .= "<td class=\"noprint\" colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPedido = "";
		switch($row['estatus_orden_compra']){
			case 2:	$imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Convertido a Orden\"/>"; break;
			case 3: $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Compra Registrada\"/>"; break;
		}
			
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_orden']))."</td>";
			$htmlTb .= "<td align=\"Center\">".$row['id_orden_compra']."</td>";
			$htmlTb .= "<td align=\"Center\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row["id_proveedor"].".- ".$row['nombre'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode(ucfirst(strtolower($row['observaciones'])))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgFacturarOrden%s\" onclick=\"window.open('ga_registro_compra_form.php?id=%s', '_self');\" src=\"../img/iconos/ico_importar.gif\" title=\"Facturar\"/>",
					$contFila,
					$row['id_orden_compra']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
			if ($row['estatus_orden_compra'] == 2) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAnularOrden%s\" onclick=\"validarDesaprobarOrden('%s', '%s')\" src=\"../img/iconos/ico_error.gif\" title=\"Anular Orden\"/>",
					$contFila,
					$row['id_orden_compra'],
					$contFila);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPedidosCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPedidosCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoPedidosCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPedidosCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPedidosCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaPedidoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"desaprobarOrden");
$xajax->register(XAJAX_FUNCTION,"listadoPedidosCompra");
?>