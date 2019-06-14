<?php


function anularPresupuesto($idPresupuesto, $hddIdItm, $frmListaPedidoVenta) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	// ACTUALIZA EL ESTATUS DEL PRESUPUESTO DE VENTA
	$updateSQL = sprintf("UPDATE iv_presupuesto_venta SET
		estatus_presupuesto_venta = %s
	WHERE id_presupuesto_venta = %s;",
		valTpDato(2, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Anulado
		valTpDato($idPresupuesto, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Presupuesto de Venta Anulado con Éxito");
	
	$objResponse->loadCommands(listaPresupuestoVenta(
		$frmListaPedidoVenta['pageNum'],
		$frmListaPedidoVenta['campOrd'],
		$frmListaPedidoVenta['tpOrd'],
		$frmListaPedidoVenta['valBusq']));
	
	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstEstatusPedido'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPresupuestoVenta(0, "id_presupuesto_venta", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM iv_presupuesto_venta pres_vent
		INNER JOIN vw_pg_empleados empleado ON (pres_vent.id_empleado_preparador = empleado.id_empleado)
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function listaPresupuestoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(estatus_presupuesto_venta = 0
	OR (estatus_presupuesto_venta = 1
		AND estatus_pedido_venta NOT IN (2,3,4,5)))");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_pres_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_pres_vent.id_empresa))",
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
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		if ($valCadBusq[4] == 0) {
			$sqlBusq .= $cond.sprintf("estatus_presupuesto_venta = %s)",
				valTpDato($valCadBusq[4], "int"));
		} else {
			if ($valCadBusq[4] == 1)
				$valCadBusq[4] = 0;
			else if ($valCadBusq[4] == 2)
				$valCadBusq[4] = 1;
				
			$sqlBusq .= $cond.sprintf("estatus_presupuesto_venta = 1 AND estatus_pedido_venta = %s)",
				valTpDato($valCadBusq[4], "int"));
		}
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numeracion_presupuesto LIKE %s
		OR numero_siniestro LIKE %s
		OR ci_cliente LIKE %s
		OR nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT *,
		(SELECT COUNT(pres_venta_det.id_presupuesto_venta) FROM iv_presupuesto_venta_detalle pres_venta_det
		WHERE (pres_venta_det.id_presupuesto_venta = vw_iv_pres_vent.id_presupuesto_venta)) AS cant_items,
		
		(SELECT SUM(pres_venta_det.cantidad) FROM iv_presupuesto_venta_detalle pres_venta_det
		WHERE (pres_venta_det.id_presupuesto_venta = vw_iv_pres_vent.id_presupuesto_venta)) AS pedidos,
		
		(SELECT SUM(pres_venta_det.pendiente) FROM iv_presupuesto_venta_detalle pres_venta_det
		WHERE (pres_venta_det.id_presupuesto_venta = vw_iv_pres_vent.id_presupuesto_venta)) AS pendientes,
		
		(IFNULL(vw_iv_pres_vent.subtotal, 0)
			- IFNULL(vw_iv_pres_vent.subtotal_descuento, 0)) AS total_neto,
		
		(SELECT SUM(pres_vent_iva.subtotal_iva) FROM iv_presupuesto_venta_iva pres_vent_iva
		WHERE pres_vent_iva.id_presupuesto_venta = vw_iv_pres_vent.id_presupuesto_venta) AS total_iva,
		
		(IFNULL(vw_iv_pres_vent.subtotal, 0)
			- IFNULL(vw_iv_pres_vent.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(pres_vent_gasto.monto) FROM iv_presupuesto_venta_gasto pres_vent_gasto
					WHERE pres_vent_gasto.id_presupuesto_venta = vw_iv_pres_vent.id_presupuesto_venta), 0)
			+ IFNULL((SELECT SUM(pres_iva.subtotal_iva) AS total_iva
					FROM iv_presupuesto_venta_iva pres_iva
					WHERE pres_iva.id_presupuesto_venta = vw_iv_pres_vent.id_presupuesto_venta), 0)) AS total,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_presupuestos_venta vw_iv_pres_vent
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_pres_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc.");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "12%", $pageNum, "numero_siniestro", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Siniestro");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "36%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Presupuesto");		
		$htmlTh .= "<td class=\"noprint\" colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPedido = "";
		if ($row['estatus_presupuesto_venta'] == 0)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto\"/>";
		else if ($row['estatus_presupuesto_venta'] == 1) {
			if ($row['estatus_pedido_venta'] == 0)
				$imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Pendiente por Terminar\"/>";
			else if ($row['estatus_pedido_venta'] == 1)
				$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Pedido\"/>";
			else if ($row['estatus_pedido_venta'] == 2)
				$imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Aprobado\"/>";
			else if ($row['estatus_pedido_venta'] == 3)
				$imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
			else if ($row['estatus_pedido_venta'] == 4)
				$imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>";
			else if ($row['estatus_pedido_venta'] == 5)
				$imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>";
		} else if ($row['estatus_presupuesto_venta'] == 2) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Presupuesto Anulado\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_vencimiento']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_presupuesto']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_siniestro'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
				$htmlTb .= (strlen($row['observaciones']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observaciones'])."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$row['cant_items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_presupuesto_venta'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgEditarPresupuesto%s\" onclick=\"window.open('iv_presupuesto_venta_form.php?id=%s','_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar Presupuesto\"/>",
					$contFila,
					$row['id_presupuesto_venta']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_presupuesto_venta'] == 0) {
			$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgImportarPresupuesto%s\" onclick=\"window.open('iv_pedido_venta_form.php?id=%s&type=import','_self');\" src=\"../img/iconos/book_next.png\" title=\"Importar a Pedido\"/>",
				$contFila,
				$row['id_presupuesto_venta']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_presupuesto_venta'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAnularPresupuesto%s\" onclick=\"validarPresupuestoAnulado('%s', '%s')\" src=\"../img/iconos/cancel.png\" title=\"Anular Presupuesto\"/>",
					$contFila,
					$row['id_presupuesto_venta'],
					$contFila);
			}
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/iv_presupuesto_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Presupuesto Venta PDF\"/></td>",
				$row['id_presupuesto_venta']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"14\">";
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

$xajax->register(XAJAX_FUNCTION,"anularPresupuesto");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaPresupuestoVenta");
?>