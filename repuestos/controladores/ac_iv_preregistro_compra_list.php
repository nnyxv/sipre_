<?php


function anularArticulo($idPedidoCompraDetalle, $frmListaArticuloPedido, $frmListaPedidoVenta){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_preregistro_compra_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$queryPedDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
	WHERE id_pedido_compra_detalle = %s;",
		valTpDato($idPedidoCompraDetalle, "int"));
	$rsPedDet = mysql_query($queryPedDet);
	if (!$rsPedDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedDet = mysql_fetch_assoc($rsPedDet);
	
	$idPedido = $rowPedDet['id_pedido_compra'];
	$idArticulo = $rowPedDet['id_articulo'];
	
	// ACTUALIZA EL ESTATUS DEL DETALLE DEL PEDIDO
	$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
		estatus = %s
	WHERE id_pedido_compra_detalle = %s;",
		valTpDato(2, "int"), // 0 = En Espera, 1 = Despachado, 2 = Anulado
		valTpDato($idPedidoCompraDetalle, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DEL PEDIDO DE COMPRA (0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelto, 5 = Anulado)
	$updateSQL = sprintf("UPDATE iv_pedido_compra SET 
		estatus_pedido_compra = (CASE
									WHEN ((SELECT COUNT(ped_comp_det.id_pedido_compra) FROM iv_pedido_compra_detalle ped_comp_det
											WHERE ped_comp_det.estatus IN (2)
												AND ped_comp_det.id_pedido_compra = iv_pedido_compra.id_pedido_compra) > 0
										AND (SELECT COUNT(ped_comp_det.id_pedido_compra) FROM iv_pedido_compra_detalle ped_comp_det
											WHERE ped_comp_det.estatus IN (0,1)
												AND ped_comp_det.id_pedido_compra = iv_pedido_compra.id_pedido_compra) = 0) THEN
										5
									WHEN ((SELECT SUM(pendiente) FROM iv_pedido_compra_detalle ped_comp_det
											WHERE ped_comp_det.estatus IN (0,1)
												AND ped_comp_det.id_pedido_compra = iv_pedido_compra.id_pedido_compra) = 0) THEN
										3
									WHEN ((SELECT SUM(pendiente) FROM iv_pedido_compra_detalle ped_comp_det
											WHERE ped_comp_det.estatus IN (0,1)
												AND ped_comp_det.id_pedido_compra = iv_pedido_compra.id_pedido_compra) > 0) THEN
										2
								END)
	WHERE id_pedido_compra IN (%s);",
		valTpDato($idPedido, "int")); 
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
		
	// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
	$Result1 = actualizarPedidas($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaArticuloPedido(
		$frmListaArticuloPedido['pageNum'],
		$frmListaArticuloPedido['campOrd'],
		$frmListaArticuloPedido['tpOrd'],
		$frmListaArticuloPedido['valBusq']));
	
	$objResponse->loadCommands(listaPedidoCompra(
		$frmListaPedidoVenta['pageNum'],
		$frmListaPedidoVenta['campOrd'],
		$frmListaPedidoVenta['tpOrd'],
		$frmListaPedidoVenta['valBusq']));
	
	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedidoCompra(0, "id_pedido_compra", "DESC", $valBusq));
		
	return $objResponse;
}

function formArticuloPedido($idPedidoCompra) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_preregistro_compra_list","eliminar")) { $objResponse->script("byId('btnCancelarListaArticuloPedido').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL REGISTRO DE COMPRA
	$query = sprintf("SELECT * FROM iv_pedido_compra
	WHERE estatus_pedido_compra = 2
		AND id_pedido_compra = %s",
		valTpDato($idPedidoCompra, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(listaArticuloPedido(0, "", "", $idPedidoCompra));
	
	$objResponse->assign("spanTituloArticuloPedido","innerHTML",$row['id_pedido_compra_referencia']);
	
	return $objResponse;
}

function listaArticuloPedido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT 
		ped_comp_det.*,
		art.codigo_articulo,
		art.descripcion
	FROM iv_pedido_compra_detalle ped_comp_det
		INNER JOIN iv_articulos art ON (ped_comp_det.id_articulo = art.id_articulo)
	WHERE id_pedido_compra = %s",
		valTpDato($valCadBusq[0], "int"));
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "52%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "6%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Ped.");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "6%", $pageNum, "pendiente", $campOrd, $tpOrd, $valBusq, $maxRows, "Pend.");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "8%", $pageNum, "precio_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPrecioUnitario);
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "4%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "10%", $pageNum, "(cantidad*precio_unitario)", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
		$htmlTh .= "<td></td>";
	$htmlTh .= " </tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus']) {
			case 0 : $imgEstatusArt = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"En Espera\"/>"; break;
			case 1 : $imgEstatusArt = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Recibido\"/>"; break;
			case 2 : $imgEstatusArt = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anulado\"/>"; break;
			default : $imgEstatusArt = "";
		}
		
		$caracterIva = ($row['id_iva'] != "" && $row['estatus_iva'] == 1) ? $row['iva'] : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusArt."</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".htmlentities($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cantidad']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['pendiente']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$caracterIva."</td>";
			$htmlTb .= "<td align=\"right\">".number_format(($row['cantidad']*$row['precio_unitario']), 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($row['pendiente'] > 0 && $row['estatus'] == 0) {
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnAnularArticulo%s\" onclick=\"validarAnularArticulo('%s');\" title=\"Anular Item\"><img src=\"../img/iconos/delete.png\"/></button>",
					$row['id_pedido_compra_detalle'],
					$row['id_pedido_compra_detalle']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticuloPedido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPedidoCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_pedido_compra IN (2)");
	
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
		$sqlBusq .= $cond.sprintf("(id_pedido_compra_propio LIKE %s
		OR id_pedido_compra_referencia LIKE %s
		OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		orden_comp.id_orden_compra,
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
		WHERE (ped_det.id_pedido_compra = ped_comp.id_pedido_compra)
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
		INNER JOIN iv_orden_compra orden_comp ON (ped_comp.id_pedido_compra = orden_comp.id_pedido_compra)
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
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
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
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_compra_propio']."</td>";
			$htmlTb .= "<td>".htmlentities($row['tipo_pedido_compra'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_compra_referencia']."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda'].number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblListaArticuloPedido', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Detalle\"/></a>",
					$contFila,
					$row['id_pedido_compra']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/iv_pedido_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Pedido Compra PDF\"/></td>",
					$row['id_pedido_compra']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"reportes/iv_pedido_compra_excel.php?idPedido=%s\"><img class=\"puntero\" src=\"../img/iconos/page_excel.png\" title=\"Exportar\"/></a>",
				$row['id_pedido_compra']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		// ACTUALIZA EL ESTATUS DE LOS DETALLES QUE ESTEN EN ESPERA Y NO TENGA PENDIENTES
		$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
			estatus = %s
		WHERE id_pedido_compra = %s
			AND pendiente = 0
			AND (estatus = 0 OR estatus IS NULL);",
			valTpDato(1, "int"), // 0 = En Espera, 1 = Recibido, 2 = Anulado
			valTpDato($row['id_pedido_compra'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL ESTATUS DE LOS PEDIDOS EN ANULADO
		$updateSQL = sprintf("UPDATE iv_pedido_compra SET 
			estatus_pedido_compra = %s
		WHERE id_pedido_compra = %s
			AND (SELECT COUNT(iv_pedido_compra_detalle.id_pedido_compra)
				FROM iv_pedido_compra_detalle
				WHERE iv_pedido_compra_detalle.id_pedido_compra = iv_pedido_compra.id_pedido_compra
					AND iv_pedido_compra_detalle.estatus = 0) = 0
			AND (SELECT COUNT(iv_pedido_compra_detalle.id_pedido_compra)
				FROM iv_pedido_compra_detalle
				WHERE iv_pedido_compra_detalle.id_pedido_compra = iv_pedido_compra.id_pedido_compra
					AND iv_pedido_compra_detalle.estatus = 1) = 0
			AND (SELECT COUNT(iv_pedido_compra_detalle.id_pedido_compra)
				FROM iv_pedido_compra_detalle
				WHERE iv_pedido_compra_detalle.id_pedido_compra = iv_pedido_compra.id_pedido_compra
					AND iv_pedido_compra_detalle.estatus = 2) > 0;", // 0 = En Espera, 1 = Recibido, 2 = Anulado
			valTpDato(5, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelta, 5 = Anulada
			valTpDato($row['id_pedido_compra'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL ESTATUS DE LOS PEDIDOS EN FACTURADA
		$updateSQL = sprintf("UPDATE iv_pedido_compra SET 
			estatus_pedido_compra = %s
		WHERE id_pedido_compra = %s
			AND (SELECT COUNT(iv_pedido_compra_detalle.id_pedido_compra)
				FROM iv_pedido_compra_detalle
				WHERE iv_pedido_compra_detalle.id_pedido_compra = iv_pedido_compra.id_pedido_compra
					AND iv_pedido_compra_detalle.estatus = 0) = 0
			AND (
				((SELECT COUNT(iv_pedido_compra_detalle.id_pedido_compra)
						FROM iv_pedido_compra_detalle
						WHERE iv_pedido_compra_detalle.id_pedido_compra = iv_pedido_compra.id_pedido_compra
							AND iv_pedido_compra_detalle.estatus = 1) > 0
					AND (SELECT COUNT(iv_pedido_compra_detalle.id_pedido_compra)
						FROM iv_pedido_compra_detalle
						WHERE iv_pedido_compra_detalle.id_pedido_compra = iv_pedido_compra.id_pedido_compra
							AND iv_pedido_compra_detalle.estatus = 2) = 0)
				OR ((SELECT COUNT(iv_pedido_compra_detalle.id_pedido_compra)
						FROM iv_pedido_compra_detalle
						WHERE iv_pedido_compra_detalle.id_pedido_compra = iv_pedido_compra.id_pedido_compra
							AND iv_pedido_compra_detalle.estatus = 1) > (SELECT COUNT(iv_pedido_compra_detalle.id_pedido_compra)
																		FROM iv_pedido_compra_detalle
																		WHERE iv_pedido_compra_detalle.id_pedido_compra = iv_pedido_compra.id_pedido_compra
																			AND iv_pedido_compra_detalle.estatus = 2)));", // 0 = En Espera, 1 = Recibido, 2 = Anulado
			valTpDato(3, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelta, 5 = Anulada
			valTpDato($row['id_pedido_compra'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
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
		$htmlTb .= "<td colspan=\"12\">";
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

$xajax->register(XAJAX_FUNCTION,"anularArticulo");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"formArticuloPedido");
$xajax->register(XAJAX_FUNCTION,"listaArticuloPedido");
$xajax->register(XAJAX_FUNCTION,"listaPedidoCompra");
?>