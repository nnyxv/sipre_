<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAplicaLibro'],
		$frmBuscar['cbxNoCancelado'],
		$frmBuscar['cbxCancelado'],
		$frmBuscar['cbxParcialCancelado'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaVentaCliente(0, "numeroControl", "DESC", $valBusq));
	
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
	WHERE fact_vent.idDepartamentoOrigenFactura IN (0,1,2)
	ORDER BY nombre_empleado");
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

function exportarVentaCliente($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAplicaLibro'],
		$frmBuscar['cbxNoCancelado'],
		$frmBuscar['cbxCancelado'],
		$frmBuscar['cbxParcialCancelado'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_venta_cliente_articulo_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaVentaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0,1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.aplicaLibros = %s",
			valTpDato($valCadBusq[1], "boolean"));
	}
	
	if (($valCadBusq[2] != "-1" && $valCadBusq[2] != "")
	|| ($valCadBusq[3] != "-1" && $valCadBusq[3] != "")
	|| ($valCadBusq[4] != "-1" && $valCadBusq[4] != "")) {
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") $array[] = $valCadBusq[2];
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") $array[] = $valCadBusq[3];
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") $array[] = $valCadBusq[4];
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.estadoFactura IN (%s)",
			valTpDato(implode(",",$array), "campo"));
	}
	
	if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_vent.id_empleado_preparador = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
			INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
		WHERE vw_pg_clave_movimiento.tipo IN (3)
			AND mov.id_documento = fact_vent.idFactura) = %s",
			valTpDato($valCadBusq[8], "int"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_vent.numeroFactura LIKE %s
		OR fact_vent.numeroControl LIKE %s
		OR ped_vent.id_pedido_venta_propio LIKE %s
		OR ped_vent.id_pedido_venta_referencia LIKE %s
		OR pres_vent.numero_siniestro LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		fact_vent.idCliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido_venta)
			AND (fact_vent.idDepartamentoOrigenFactura IN (0))
		LEFT JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)
			AND (fact_vent.idDepartamentoOrigenFactura IN (1))
		LEFT JOIN iv_presupuesto_venta pres_vent ON (ped_vent.id_presupuesto_venta = pres_vent.id_presupuesto_venta) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("fact_vent.idCliente = %s",
			valTpDato($row['idCliente'], "int"));
		
		$queryFacturaVenta = sprintf("SELECT fact_vent.*,
			(CASE idDepartamentoOrigenFactura
				WHEN 0 THEN
					ped_vent.id_pedido_venta_propio
				WHEN 1 THEN
					orden.numero_orden
			END) AS numero_pedido,
			ped_vent.id_pedido_venta_referencia,
			vw_pg_empleado.nombre_empleado,
			art.codigo_articulo,
			art.descripcion,
			fact_vent_det.cantidad,
			fact_vent_det.precio_unitario
		FROM cj_cc_encabezadofactura fact_vent
			INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
			LEFT JOIN iv_pedido_venta ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido_venta)
				AND (fact_vent.idDepartamentoOrigenFactura IN (0))
			LEFT JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)
				AND (fact_vent.idDepartamentoOrigenFactura IN (1))
			LEFT JOIN iv_presupuesto_venta pres_vent ON (ped_vent.id_presupuesto_venta = pres_vent.id_presupuesto_venta)
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON ((ped_vent.id_empleado_preparador = vw_pg_empleado.id_empleado AND fact_vent.idDepartamentoOrigenFactura IN (0))
				OR (orden.id_empleado = vw_pg_empleado.id_empleado AND fact_vent.idDepartamentoOrigenFactura IN (1)))
			INNER JOIN cj_cc_factura_detalle fact_vent_det ON (fact_vent.idFactura = fact_vent_det.id_factura)
			INNER JOIN iv_articulos art ON (fact_vent_det.id_articulo = art.id_articulo) %s %s", $sqlBusq, $sqlBusq2);
		
		$queryFacturaVenta .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsFacturaVenta = mysql_query($queryFacturaVenta);
		if (!$rsFacturaVenta) return $objResponse->alert(mysql_error()."\nLine: ".__LINE__);
		$totalRowsFacturaVenta = mysql_num_rows($rsFacturaVenta);
		
		if ($totalRowsFacturaVenta > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">"."Cliente:"."</td>";
				$htmlTb .= "<td colspan=\"11\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".$spanClienteCxC.":</td>";
				$htmlTb .= "<td colspan=\"11\">".utf8_encode($row['ci_cliente'])."</td>";
			$htmlTb .= "</tr>";
			
			$htmlTb .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"10%\">"."Código"."</td>";
				$htmlTb .= "<td width=\"20%\">"."Descripción"."</td>";
				$htmlTb .= "<td width=\"6%\">"."Cantidad"."</td>";
				$htmlTb .= "<td width=\"8%\">"."Precio"."</td>";
				$htmlTb .= "<td width=\"6%\">"."Fecha"."</td>";
				$htmlTb .= "<td width=\"6%\">"."Nro. Factura"."</td>";
				$htmlTb .= "<td width=\"6%\">"."Nro. Control"."</td>";
				$htmlTb .= "<td width=\"6%\">"."Nro. Pedido"."</td>";
				$htmlTb .= "<td width=\"6%\">"."Nro. Referencia"."</td>";
				$htmlTb .= "<td width=\"6%\">"."Estatus"."</td>";
				$htmlTb .= "<td width=\"6%\">"."Tipo Pago"."</td>";
				$htmlTb .= "<td width=\"14%\">"."Vendedor"."</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal = NULL;
		$contFila2 = 0;
		while ($rowFacturaVenta = mysql_fetch_assoc($rsFacturaVenta)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			switch($rowFacturaVenta['idDepartamentoOrigenFactura']) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Factura Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Factura Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Factura Vehículos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Factura Administración\"/>"; break;
				default : $imgDctoModulo = $row['idDepartamentoOrigenFactura'];
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>".elimCaracter(utf8_encode($rowFacturaVenta['codigo_articulo']),";")."</td>";
				$htmlTb .= "<td>".utf8_encode($rowFacturaVenta['descripcion'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowFacturaVenta['cantidad'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowFacturaVenta['precio_unitario'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($rowFacturaVenta['fechaRegistroFactura']))."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".$imgDctoModulo."</td>";
						$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($rowFacturaVenta['numeroFactura'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($rowFacturaVenta['numeroControl'])."</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($rowFacturaVenta['numero_pedido'])."</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($rowFacturaVenta['id_pedido_venta_referencia'])."</td>";
				$htmlTb .= ($rowFacturaVenta['anulada'] == "NO") ? "<td align=\"center\">" : "<td align=\"center\" class=\"divMsjError\">";
					$htmlTb .= ($rowFacturaVenta['anulada'] == "NO") ? "": "ANULADA";
				$htmlTb .= "</td>";
				$htmlTb .= ($rowFacturaVenta['condicion_pago'] == 0) ? "<td align=\"center\" class=\"divMsjAlerta\">" : "<td align=\"center\" class=\"divMsjInfo\">";
					$htmlTb .= ($rowFacturaVenta['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowFacturaVenta['nombre_empleado'])."</td>";
			$htmlTb .= "</tr>";
			
			if ($rowFacturaVenta['anulada'] == "NO") {
				$arrayTotal[3] += $rowFacturaVenta['cantidad'];
			}
		}
		
		if ($totalRowsFacturaVenta > 0) {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"2\">Total:</td>
							<td>".number_format($arrayTotal[3], 2, ".", ",")."</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>";
			$htmlTb .= "</tr>";
			
			if ($contFila < $maxRows && (($maxRows * $pageNum) + $contFila) < $totalRows)
				$htmlTb .= "<tr><td colspan=\"12\">&nbsp;</td></tr>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVentaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVentaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaVentaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVentaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaVentaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaVentaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"exportarVentaCliente");
$xajax->register(XAJAX_FUNCTION,"listaVentaCliente");
?>