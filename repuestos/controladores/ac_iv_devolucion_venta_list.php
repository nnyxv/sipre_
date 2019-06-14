<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaFacturaVenta(0, "numeroControl", "DESC", $valBusq));
		
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN vw_pg_empleados empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	WHERE fact_vent.idDepartamentoOrigenFactura IN (0)
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

function listaFacturaVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_pedido_venta NOT IN (0,1,2)
	AND vw_iv_fact_vent.idFactura NOT IN (SELECT nota_cred.idDocumento FROM cj_cc_notacredito nota_cred
										WHERE nota_cred.tipoDocumento LIKE 'FA'
											AND (nota_cred.estatus_nota_credito IS NULL OR nota_cred.estatus_nota_credito = 1))");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(estatus_pedido_venta = %s
	OR (estatus_pedido_venta = %s AND (SELECT SUM(fact_vent_det.cantidad) - SUM(fact_vent_det.devuelto) FROM cj_cc_factura_detalle fact_vent_det
		WHERE fact_vent_det.id_factura = vw_iv_fact_vent.idFactura) > 0))",
		valTpDato(3, "int"),
		valTpDato(4, "int"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_fact_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_fact_vent.id_empresa))",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fechaRegistroFactura BETWEEN %s AND %s",
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
		$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
			INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
		WHERE vw_pg_clave_movimiento.tipo = 3
			AND mov.id_documento = vw_iv_fact_vent.idFactura) = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numeroFactura LIKE %s
		OR id_pedido_venta_propio LIKE %s
		OR id_pedido_venta_referencia LIKE %s
		OR ci_cliente LIKE %s
		OR nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT *,
		(SELECT COUNT(ped_venta_det.id_pedido_venta) FROM iv_pedido_venta_detalle ped_venta_det
		WHERE (ped_venta_det.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta)) AS cant_items,
		
		(SELECT SUM(ped_venta_det.cantidad) FROM iv_pedido_venta_detalle ped_venta_det
		WHERE (ped_venta_det.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta)) AS pedidos,
		
		(SELECT SUM(ped_venta_det.pendiente) FROM iv_pedido_venta_detalle ped_venta_det
		WHERE (ped_venta_det.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta)) AS pendientes,
		
		(IFNULL(vw_iv_fact_vent.subtotal, 0)
			- IFNULL(vw_iv_fact_vent.subtotal_descuento, 0)) AS total_neto,
		
		(IFNULL((CASE
					WHEN (estatus_pedido_venta IS NULL) THEN
						calculoIvaFactura
					WHEN (estatus_pedido_venta IS NOT NULL) THEN
						(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
							FROM iv_pedido_venta_iva ped_iva
							WHERE (ped_iva.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta))
				END), 0)) AS total_iva,
		
		(IFNULL(vw_iv_fact_vent.subtotal, 0)
			- IFNULL(vw_iv_fact_vent.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(ped_gasto.monto) AS total_gasto
					FROM iv_pedido_venta_gasto ped_gasto
					WHERE (ped_gasto.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta)), 0)
			+ IFNULL((CASE
						WHEN (estatus_pedido_venta IS NULL) THEN
							calculoIvaFactura
						WHEN (estatus_pedido_venta IS NOT NULL) THEN
							(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
								FROM iv_pedido_venta_iva ped_iva
								WHERE (ped_iva.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta))
					END), 0)) AS total,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_facturas_venta vw_iv_fact_vent
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_fact_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "20%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "id_pedido_venta_propio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "8%", $pageNum, "id_pedido_venta_referencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "8%", $pageNum, "numero_siniestro", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Siniestro");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "16%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus_pedido_venta']) {
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Aprobado\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_propio']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_referencia']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_siniestro']."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_cliente'])."</td>";
			$htmlTb .= ($row['condicion_pago'] == 0) ? "<td align=\"center\" class=\"divMsjAlerta\">" : "<td align=\"center\" class=\"divMsjInfo\">";
				$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$row['cant_items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"window.open('iv_devolucion_venta_form.php?id=%s','_self');\" src=\"../img/iconos/arrow_rotate_clockwise.png\" title=\"Crear Nota de Crédito\"/></td>",
				$row['idFactura']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("divListaFacturaVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaFacturaVenta");
?>