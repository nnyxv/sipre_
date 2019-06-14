<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstEstatusPedido'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedidoCompra(0, "id_pedido_compra", "DESC", $valBusq));
		
	return $objResponse;
}

function desaprobarPedido($idPedidoCompra, $frmListaPedidoCompra) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$queryPedDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
	WHERE id_pedido_compra = %s;",
		valTpDato($idPedidoCompra, "int"));
	$rsPedDet = mysql_query($queryPedDet);
	if (!$rsPedDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowPedDet = mysql_fetch_assoc($rsPedDet)) {
		$idPedDet = $rowPedDet['id_pedido_compra_detalle'];
		$idArticulo = $rowPedDet['id_articulo'];
		
		$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
			estatus = %s
		WHERE id_pedido_compra_detalle = %s;",
			valTpDato(2, "int"), // 0 = En Espera, 1 = Recibido, 2 = Anulado
			valTpDato($idPedDet, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
		$Result1 = actualizarPedidas($idArticulo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	// ACTUALIZA EL ESTATUS DEL PEDIDO DE VENTA
	$updateSQL = sprintf("UPDATE iv_pedido_compra SET
		estatus_pedido_compra = %s
	WHERE id_pedido_compra = %s
		AND estatus_pedido_compra = 0;",
		valTpDato(5, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelto, 5 = Anulado
		valTpDato($idPedidoCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(("Pedido de Compra Desaprobado con Éxito"));
		
	$objResponse->loadCommands(listaPedidoCompra(
		$frmListaPedidoCompra['pageNum'],
		$frmListaPedidoCompra['campOrd'],
		$frmListaPedidoCompra['tpOrd'],
		$frmListaPedidoCompra['valBusq']));
	
	return $objResponse;
}

function listaPedidoCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_pedido_compra NOT IN (3)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ped_comp.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = ped_comp.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus_pedido_compra = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_pedido_compra_propio LIKE %s
		OR id_pedido_compra_referencia LIKE %s
		OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT
		ped_comp.id_pedido_compra,
		ped_comp.fecha,
		ped_comp.id_pedido_compra_propio,
		tipo_ped_comp.id_tipo_pedido_compra,
		tipo_ped_comp.tipo_pedido_compra,
		ped_comp.id_pedido_compra_referencia,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre,
		
		(SELECT COUNT(ped_det.id_pedido_compra) AS items
		FROM iv_pedido_compra_detalle ped_det
		WHERE ped_det.id_pedido_compra = ped_comp.id_pedido_compra) AS items,
		
		(SELECT SUM(ped_det.cantidad) AS pedidos
		FROM iv_pedido_compra_detalle ped_det
		WHERE ped_det.id_pedido_compra = ped_comp.id_pedido_compra) AS pedidos,
		
		(SELECT SUM(ped_det.pendiente) AS pendientes
		FROM iv_pedido_compra_detalle ped_det
		WHERE ped_det.id_pedido_compra = ped_comp.id_pedido_compra
			AND ped_det.estatus <> 2) AS pendientes,
		
		(IFNULL(ped_comp.subtotal, 0)
			- IFNULL(ped_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(ped_gasto.monto) AS total_gasto
					FROM iv_pedido_compra_gasto ped_gasto
					WHERE (ped_gasto.id_pedido_compra = ped_comp.id_pedido_compra)), 0)
			+ IFNULL((SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM iv_pedido_compra_iva ped_iva
					WHERE (ped_iva.id_pedido_compra = ped_comp.id_pedido_compra)), 0)) AS total,
		
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
		
		ped_comp.estatus_pedido_compra,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_pedido_compra ped_comp
		INNER JOIN iv_tipo_pedido_compra tipo_ped_comp ON (ped_comp.id_tipo_pedido_compra = tipo_ped_comp.id_tipo_pedido_compra)
		INNER JOIN cp_proveedor prov ON (ped_comp.id_proveedor = prov.id_proveedor)
		INNER JOIN pg_monedas moneda_local ON (ped_comp.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (ped_comp.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "14%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "6%", $pageNum, "id_pedido_compra_propio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "18%", $pageNum, "tipo_pedido_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "14%", $pageNum, "id_pedido_compra_referencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "24%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pedido");
		$htmlTh .= "<td class=\"noprint\" colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		
		switch($row['estatus_pedido_compra']) {
			case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Pendiente por Terminar\"/>"; break;
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Pedido Cerrado\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Orden Aprobada\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Pedido Anulado\"/>"; break;
			default : $imgEstatusPedido = ""; break;
		}	
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_compra_propio']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo_pedido_compra'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_pedido_compra_referencia'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda'].number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_pedido_compra'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('iv_pedido_compra_form.php?id=%s','_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar Pedido\"/>",
					$row['id_pedido_compra']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_pedido_compra'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAnularPedido%s\" onclick=\"validarPedidoDesaprobado('%s')\" src=\"../img/iconos/cancel.png\" title=\"Anular Pedido\"/>",
					$contFila,
					$row['id_pedido_compra']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"reportes/iv_pedido_compra_excel.php?idPedido=%s\"><img class=\"puntero\" src=\"../img/iconos/page_excel.png\" title=\"Exportar\"/></a>",
				$row['id_pedido_compra']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/iv_pedido_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Pedido Compra PDF\"/></td>",
				$row['id_pedido_compra']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaPedidoCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"desaprobarPedido");
$xajax->register(XAJAX_FUNCTION,"listaPedidoCompra");
?>