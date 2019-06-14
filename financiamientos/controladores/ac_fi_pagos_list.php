<?php 
function buscarPedido($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s",
			$frmBuscar['lstEmpresa'],
			$frmBuscar['txtFechaDesde'],
			$frmBuscar['txtFechaHasta'],
			$frmBuscar['lstEstatusPedido'],
			$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listaPedido(0, "id_pedido_financiamiento", "DESC", $valBusq));

	return $objResponse;
}


function listaPedido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pedido.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = pedido.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pedido.fecha_financiamiento BETWEEN %s AND %s)",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}


	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pedido.estatus_pedido = %s)",
				valTpDato($valCadBusq[3], "int"));
	}else{
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(pedido.estatus_pedido IN (1,3,4))";
	}

	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pedido.numeracion_pedido LIKE %s
		OR pedido.id_pedido_financiamiento LIKE %s
		OR pedido.id_cliente LIKE %s
		OR pedido.id_empresa LIKE %s
		OR cliente.ci LIKE %s
		OR cliente.telf LIKE %s
		OR cliente.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"));
	}

	$query = sprintf("SELECT 
						pedido.id_pedido_financiamiento,
						CONCAT_WS(' ',emp.nombre_empleado,emp.apellido) AS empleado,
						pedido.estatus_pedido,
						pedido.id_cliente,
						pedido.id_empresa,
						pedido.id_notadecargo_cxc AS id_documento_pago,
						cliente.id,
						CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombre_cliente,
						CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
						cliente.telf,
						cliente.nit AS nit_cliente,
						cliente.direccion,
						cliente.telf,
						cliente.otrotelf,
						pedido.numeracion_pedido AS numeracion,
						empresa.nombre_empresa AS empresa,
						pedido.fecha_financiamiento AS fecha_inicial,
						pedido.fecha_fin_financiamiento AS fecha_final,
						(CASE pedido.tipo_interes
							WHEN 1 THEN 'SIMPLE'
							WHEN 2 THEN 'COMPUESTO'
						END) AS tipo_interes,
						pedido.tipo_interes AS id_tipo_interes,
						pedido.interes_financiamiento AS interes,
						CONCAT_WS(' ',pedido.cuotas_duracion, 
							(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_duracion_plazo)) AS duracion,
						(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_frecuencia_plazo) AS frecuencia,
						pedido.numero_pagos,
						pedido.monto_financiamiento_documentos AS total_inicial,
						pedido.total_intereses AS total_intereses,
						pedido.interes_efectivo,
						pedido.id_frecuencia_plazo,
						pedido.total_monto_financiar AS total_cuotas
					FROM fi_pedido pedido
						INNER JOIN cj_cc_cliente cliente ON (pedido.id_cliente = cliente.id)
						INNER JOIN pg_empresa empresa ON (pedido.id_empresa = empresa.id_empresa)
						INNER JOIN fi_plazos plazo ON (pedido.id_duracion_plazo = plazo.id_plazo)
						INNER JOIN pg_empleado emp ON (pedido.id_empleado = emp.id_empleado)
					%s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf("ORDER BY %s %s", $campOrd, $tpOrd) : "";
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
	$htmlTh .= "<td width=\"4%\">Estatus</td>";
	$htmlTh .= ordenarCampo("xajax_listaPedido", "5%", $pageNum, "numeracion_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Numeracion");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "fecha_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Inicial");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "fecha_final", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Final");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "5%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuotas Pagadas");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "5%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuotas Pendientes");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pagado");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Total");
	$htmlTh .= "<td class=\"noprint\" colspan=\"4\"></td>";
	$htmlTh .= "</tr>";

	while ($rowPedido = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		//buscando amortizaciones
		
		$queryAmortizacion = sprintf("SELECT * FROM fi_amortizacion WHERE id_pedido_financiamiento = %s",
				valTpDato($rowPedido['id_pedido_financiamiento'], "int"));
		$rs = mysql_query($queryAmortizacion);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		$totalRows = mysql_num_rows($rs);
			
		$cuotaPendiente = 0; $cuotaPagada = 0; $saldoActual = 0; $montoAmort = 0;  $interesesPagados = 0;
			
		while($rowAmort = mysql_fetch_assoc($rs)){
			$amortizaciones[] = $rowAmort;
			if($rowAmort['estado_cuota'] == 0){
				$cuotaPendiente++;
			}else{
				$cuotaPagada++;
				$montoAmort += $rowAmort['amortizacion_cuota'];
				$interesesPagados += $rowAmort['interes_cuota'];
			}
		}
		
		$montoPagado = number_format($montoAmort + $interesesPagados, 2,".",",");
		$saldoActual = number_format($rowPedido['total_cuotas'] - str_replace(",", "", $montoPagado),2,".",",");
		
		//validando el estatus del pedido
		
		switch ($rowPedido['estatus_pedido']){
			case 0 : $classEstatus = "divResaltarRojo"; $estatusPedido = "NO APROBADO"; break;
			case 1 : $classEstatus = "divResaltarAmarillo"; $estatusPedido = "PARCIALMENTE PAGADO"; break;
			case 2 : $classEstatus = "divResaltarVerde"; $estatusPedido = "PAGADO"; break;
			case 3 : $classEstatus = "divResaltarAzul"; $estatusPedido = "APROBADO"; break;
			case 4 : $classEstatus = "divResaltarNaranja"; $estatusPedido = "ATRASADO"; break;
		}
		
		//Eligiendo el tipo de interes
		
		switch ($rowPedido['tipo_interes']){
			case 1 : $tipoInteres = "SIMPLE";  break;
			case 2 : $tipoInteres = "COMPUESTO"; break;
		}
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= "<td align=\"center\" class=\"$classEstatus\">$estatusPedido</td>";
				$htmlTb .= "<td align=\"center\">".$rowPedido['numeracion']."</td>";
				$htmlTb .= "<td>".utf8_encode($rowPedido['empresa'])."</td>";
				$htmlTb .= "<td align=\"left\">".strtoupper($rowPedido['nombre_cliente'])."</td>";
				$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($rowPedido['fecha_inicial']))."</td>";
				$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($rowPedido['fecha_final']))."</td>";
				$htmlTb .= "<td align=\"center\">$cuotaPagada</td>";
				$htmlTb .= "<td align=\"center\">$cuotaPendiente</td>";
				$htmlTb .= "<td align=\"right\" class=\"textoNegrita_9px\">$montoPagado</td>";
				$htmlTb .= "<td align=\"right\" class=\"textoNegrita_9px\">$saldoActual</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= sprintf("<button type=\"button\" class=\"divResaltarVerde\"  title=\"Pagar Pedido\" onclick=\"window.location='fi_pago_pedido.php?id=%s';\" > 
									<img src=\"../img/iconos/money_add.png\" align=\"right\" title=\"Ver\" class=\"puntero\"  id=\"imgPedido\"/>
								</button>",$rowPedido['id_pedido_financiamiento']);
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= sprintf("<button type=\"button\" class=\"divResaltarAzul\"  title=\"Ver Recibos de Pagos\" onclick=\"verVentana('../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idTpDcto=2&id=%s', 960, 550);\" > 
									<img src=\"../img/iconos/find.png\" align=\"right\" title=\"Ver\" class=\"puntero\"  id=\"imgPedido\"/>
								</button>",$rowPedido['id_documento_pago']);
				$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
}

	$htmlTf = "<tr>";
	$htmlTf .= "<td align=\"center\" colspan=\"16\">";
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
		$htmlTb .= "<td colspan=\"16\">";
		$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
		$htmlTb .= "<tr>";
		$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
		$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaPedido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);


	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowPedido = mysql_fetch_assoc($rs)) {
		$totalDocumentos += $rowPedido['total_inicial'];
		$totalInteres += $rowPedido['total_intereses'];
		$totalMontoFinanciar += $rowPedido['total_cuotas'];
	}

	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalDocumentos, 2, ".", ","));
	$objResponse->assign("spnTotalInteres","innerHTML",number_format($totalInteres, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalMontoFinanciar, 2, ".", ","));

	return $objResponse;
}



$xajax->register(XAJAX_FUNCTION,"buscarPedido");
$xajax->register(XAJAX_FUNCTION,"listaPedido");

?>