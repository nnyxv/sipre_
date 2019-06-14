<?php


function buscar($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEmpleadoVendedor'],
		$valForm['lstEstatusPedido'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaPedidoVenta(0, "id_pedido_venta", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT empleado.id_empleado, empleado.nombre_empleado
	FROM vw_iv_pedidos_venta ped_vent
		INNER JOIN vw_pg_empleados empleado ON (ped_vent.id_empleado_preparador = empleado.id_empleado)
	ORDER BY empleado.nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function listaPedidoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((estatus_pedido_venta = 2 AND id_empleado_aprobador > 0)
	OR estatus_pedido_venta = 3
	OR estatus_pedido_venta = 4)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_ped_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_ped_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado_preparador = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus_pedido_venta = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_pedido_venta_propio LIKE %s
		OR id_pedido_venta_referencia LIKE %s
		OR numeracion_presupuesto LIKE %s
		OR numero_siniestro LIKE %s
		OR ci_cliente LIKE %s
		OR nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_iv_ped_vent.*,
		vw_pg_empleado.nombre_empleado AS nombre_empleado_aprobador,
	
		(SELECT COUNT(bulto_vent.id_pedido_venta) FROM iv_bulto_venta bulto_vent
		WHERE bulto_vent.id_pedido_venta = vw_iv_ped_vent.id_pedido_venta) AS cant_bultos,
	
		(SELECT COUNT(ped_venta_det.id_pedido_venta) FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_ped_vent.id_pedido_venta) AS cant_items,
		
		(SELECT SUM(ped_venta_det.cantidad) FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_ped_vent.id_pedido_venta) AS pedidos,
		
		(SELECT SUM(ped_venta_det.pendiente) FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_ped_vent.id_pedido_venta) AS pendientes,
		
		(IFNULL(vw_iv_ped_vent.subtotal, 0)
			- IFNULL(vw_iv_ped_vent.subtotal_descuento, 0)) AS total_neto,
		
		(SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
		WHERE ped_vent_iva.id_pedido_venta = vw_iv_ped_vent.id_pedido_venta) AS total_iva,
		
		(IFNULL(vw_iv_ped_vent.subtotal, 0)
			- IFNULL(vw_iv_ped_vent.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
					WHERE ped_vent_gasto.id_pedido_venta = vw_iv_ped_vent.id_pedido_venta), 0)
			+ IFNULL((SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM iv_pedido_venta_iva ped_iva
					WHERE ped_iva.id_pedido_venta = vw_iv_ped_vent.id_pedido_venta), 0)) AS total,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_pedidos_venta vw_iv_ped_vent
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (vw_iv_ped_vent.id_empleado_aprobador = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "id_pedido_venta_propio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "id_pedido_venta_referencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "12%", $pageNum, "numero_siniestro", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Siniestro / Empleado Aprobador");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "24%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pedido");
		$htmlTh .= "<td class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus_pedido_venta']) {
			case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Convertido a Pedido Pendiente por Terminar\"/>"; break;
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Convertido a Pedido\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Aprobado\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Factura\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_propio']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_referencia']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_presupuesto']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= (strlen($row['numero_siniestro']) > 0) ? "<tr><td align=\"right\">".$row['numero_siniestro']."</td></tr>" : "";
				$htmlTb .= (strlen($row['nombre_empleado_aprobador']) > 0) ? "<tr><td align=\"center\"><span class=\"texto_9px\">Aprobado por:</span><br><span class=\"textoNegrita_9px\">".$row['nombre_empleado_aprobador']."</span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicion_pago'] == 0) ? "divMsjAlerta" : "divMsjInfo")."\">";
				$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/iv_orden_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Orden Venta PDF\"/></td>",
				$row['id_pedido_venta']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("divListadoPedidoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVenta");
?>