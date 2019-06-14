<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaRegistroCompra(0, "id_factura_compra", "DESC", $valBusq));
		
	return $objResponse;
}

function eliminarPreregistro($idFacturaCompra, $frmListaRegistroCompra) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_preregistro_compra_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL PREREGISTRO
	$queryFacturaCompra = sprintf("SELECT * FROM iv_factura_compra
	WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaCompra = mysql_query($queryFacturaCompra);
	if (!$rsFacturaCompra) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFacturaCompra = mysql_num_rows($rsFacturaCompra);
	$rowFacturaCompra = mysql_fetch_assoc($rsFacturaCompra);
	
	// BUSCA EL DETALLE DEL PREREGISTRO
	$queryFacturaCompraDet = sprintf("SELECT DISTINCT id_pedido_compra FROM iv_factura_compra_detalle
	WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaCompraDet = mysql_query($queryFacturaCompraDet);
	if (!$rsFacturaCompraDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFacturaCompraDet = mysql_num_rows($rsFacturaCompraDet);
	while($rowFacturaCompraDet = mysql_fetch_assoc($rsFacturaCompraDet)) {
		$arrayIdPedido[] = $rowFacturaCompraDet['id_pedido_compra'];
	}
	
	// VERIFICA QUE EL PREREGISTRO NO ESTE EN ALGUN EXPEDIENTE
	$queryExpedienteDetFact = sprintf("SELECT * FROM iv_expediente_detalle_factura expediente_det_fact
	WHERE expediente_det_fact.id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsExpedienteDetFact = mysql_query($queryExpedienteDetFact);
	if (!$rsExpedienteDetFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsExpedienteDetFact = mysql_num_rows($rsExpedienteDetFact);
	$rowExpedienteDetFact = mysql_fetch_assoc($rsExpedienteDetFact);
	
	if ($totalRowsExpedienteDetFact > 0) {
		return $objResponse->alert("No puede eliminar este registro de compra debido a que esta incluido en un expediente");
	}
	
	// ELIMINA EL PREREGISTRO DE COMPRA
	$deleteSQL = sprintf("DELETE FROM iv_factura_compra WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ACTUALIZA LA CANTIDAD DE ARTICULOS PENDIENTES EN EL DETALLE DEL PEDIDO
	$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
			pendiente = cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
											FROM iv_factura_compra_detalle fact_comp_det
											WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
									+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
											FROM cp_factura_detalle fact_comp_det
											WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)),
			estatus = (CASE 
						WHEN (cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
												FROM iv_factura_compra_detalle fact_comp_det
												WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
													AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
										+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
												FROM cp_factura_detalle fact_comp_det
												WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
													AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) = 0 THEN
							1
						WHEN (cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
												FROM iv_factura_compra_detalle fact_comp_det
												WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
													AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
										+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
												FROM cp_factura_detalle fact_comp_det
												WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
													AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) > 0 THEN
							0
					END)
	WHERE estatus IN (0,1)
		AND id_pedido_compra IN (%s);",
		valTpDato(implode(",",$arrayIdPedido), "campo"));// $objResponse->alert($updateSQL);
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
		valTpDato(implode(",",$arrayIdPedido), "campo")); 
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
			
	$objResponse->alert("Eliminacion realizada con éxito");
	
	$objResponse->loadCommands(listaRegistroCompra(
		$frmListaRegistroCompra['pageNum'],
		$frmListaRegistroCompra['campOrd'],
		$frmListaRegistroCompra['tpOrd'],
		$frmListaRegistroCompra['valBusq']));
	
	return $objResponse;
}

function listaRegistroCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_comp.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = fact_comp.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_factura_proveedor LIKE %s
		OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		fact_comp.id_factura_compra,
		fact_comp.fecha_factura_proveedor,
		fact_comp.numero_factura_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(SELECT COUNT(fact_compra_det.id_factura_compra) AS items
		FROM iv_factura_compra_detalle fact_compra_det
		WHERE (fact_compra_det.id_factura_compra = fact_comp.id_factura_compra)) AS items,
			
		(IFNULL(fact_comp.subtotal_factura, 0)
			- IFNULL(fact_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM iv_factura_compra_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura_compra = fact_comp.id_factura_compra
						AND fact_compra_gasto.id_modo_gasto = 1
						AND fact_compra_gasto.afecta_documento = 1), 0)
			+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM iv_factura_compra_iva fact_compra_iva
					WHERE fact_compra_iva.id_factura_compra = fact_comp.id_factura_compra), 0)
		) AS total,
			
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_factura_compra fact_comp
		INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
		INNER JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (fact_comp.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (fact_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "14%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura Compra");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= "<td width=\"20%\">"."Tipo de Pedido"."</td>";
		$htmlTh .= "<td width=\"8%\">"."Nro. Pedido"."</td>";
		$htmlTh .= "<td width=\"8%\">"."Nro. Referencia"."</td>";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "22%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$queryFactDet = sprintf("SELECT DISTINCT
			vw_iv_ped_comp.estatus_pedido_compra,
			vw_iv_ped_comp.tipo_pedido_compra,
			vw_iv_ped_comp.id_pedido_compra_propio,
			vw_iv_ped_comp.id_pedido_compra_referencia
		FROM vw_iv_pedidos_compra vw_iv_ped_comp
			INNER JOIN iv_factura_compra_detalle fact_comp_det ON (vw_iv_ped_comp.id_pedido_compra = fact_comp_det.id_pedido_compra)
		WHERE fact_comp_det.id_factura_compra = %s;",
			valTpDato($row['id_factura_compra'], "int"));
		$rsFactDet = mysql_query($queryFactDet);
		if (!$rsFactDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$arrayFactDet = NULL;
		while ($rowFactDet = mysql_fetch_assoc($rsFactDet)) {
			$arrayDet[0] = $rowFactDet['estatus_pedido_compra'];
			$arrayDet[1] = $rowFactDet['tipo_pedido_compra'];
			$arrayDet[2] = $rowFactDet['id_pedido_compra_propio'];
			$arrayDet[3] = $rowFactDet['id_pedido_compra_referencia'];
			$arrayFactDet[] = $arrayDet;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
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
						
						$htmlTb .= "<tr align=\"left\">";
							$htmlTb .= "<td>".$imgEstatusPedido."</td>";
							$htmlTb .= "<td>".htmlentities($arrayFactDet[$indice][1])."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>".$arrayFactDet[$indice][2]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>".$arrayFactDet[$indice][3]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda'].number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('iv_registro_compra_form.php?id=%s&v=aprob','_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/>",
					$row['id_factura_compra']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante2\" onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Preregistro\"/></a>",
					$row['id_factura_compra']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("divListaRegistroCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"eliminarPreregistro");
$xajax->register(XAJAX_FUNCTION,"listaRegistroCompra");
?>