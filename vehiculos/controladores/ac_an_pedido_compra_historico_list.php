<?php


function buscarPedidoCompra($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedidoCompra(0, "idPedidoCompra", "DESC", $valBusq));
	
	return $objResponse;
}

function eliminarPedidoCompra($idAno, $frmListaPedidoCompra) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_asignar_plan_pago_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_asignacion WHERE idAsignacion = %s",
		valTpDato($idAno, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPedidoCompra(
		$frmListaPedidoCompra['pageNum'],
		$frmListaPedidoCompra['campOrd'],
		$frmListaPedidoCompra['tpOrd'],
		$frmListaPedidoCompra['valBusq']));
	
	return $objResponse;
}

function formSolicitudCompra($idPedido) {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(listaSolicitudCompra(0,'','',$idPedido));
	
	$objResponse->script("
	byId('tblUnidadPedido').style.display = 'none';
	byId('tblSolicitudCompra').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Cartas de Solicitud Pedido Nro. ".$idPedido);
	$objResponse->script("
	if (byId('divFlotante').style.display == 'none') {
		byId('divFlotante').style.display='';
		centrarDiv(byId('divFlotante'));
	}");
	
	return $objResponse;
}

function formUnidadPedido($idPedido) {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(listaPedidoDetalle(0, 'idSolicitud', 'ASC', $idPedido));
	
	$objResponse->script("
	byId('tblUnidadPedido').style.display = '';
	byId('tblSolicitudCompra').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Unidades del Pedido Nro. ".$idPedido);
	$objResponse->script("
	if (byId('divFlotante').style.display == 'none') {
		byId('divFlotante').style.display='';
		centrarDiv(byId('divFlotante'));
	}");
	
	return $objResponse;
}

function listaPedidoCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_comp.id_empresa = %s",
			valTpDato($valCadBusq[0], "text"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_comp.fecha_pedido BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(idPedidoCompra LIKE %s
		OR asig.idAsignacion LIKE %s
		OR referencia_asignacion LIKE %s
		OR asunto_asignacion LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		ped_comp.idPedidoCompra,
		ped_comp.fecha_pedido,
		ped_comp.estatus_pedido,
		asig.idAsignacion,
		asig.referencia_asignacion,
		asig.asunto_asignacion,
		prov.nombre AS nombre_proveedor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_asignacion asig
		INNER JOIN an_pedido_compra ped_comp ON (asig.idAsignacion = ped_comp.idAsignacion)
		INNER JOIN cp_proveedor prov ON (asig.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "fecha_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "idPedidoCompra", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Asignacion");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "14%", $pageNum, "referencia_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Referencia");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "22%", $pageNum, "asunto_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Asignación");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "26%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch ($row['estatus_pedido']) {
			case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Forma de Pago Sin Asignar\"/>"; break;
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Forma de Pago Parcialmente Asignado\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Forma de Pago Asignado\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
			/*case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;*/
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_pedido']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['idPedidoCompra']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['idAsignacion']."</td>";
			$htmlTb .= "<td>".$row['referencia_asignacion']."</td>";
			$htmlTb .= "<td>".$row['asunto_asignacion']."</td>";
			$htmlTb .= "<td>".$row['nombre_proveedor']."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formUnidadPedido('%s');\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Unidades\"/></td>",
				$row['idPedidoCompra']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formSolicitudCompra('%s');\" src=\"../img/iconos/page_green.png\" title=\"Ver Cartas de Solicitud\"/></td>",
				$row['idPedidoCompra']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/an_pedido_compra_pdf.php?valBusq=%s', 900, 700);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Pedido Compra PDF\"/></td>",
				$row['idPedidoCompra']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"11\">";
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

function listaSolicitudCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_comp.idPedidoCompra = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT
		carta_sol.idCartaSolicitud,
		carta_sol.fechaCartaSolicitud,
		ped_comp.idPedidoCompra,
		pago_asig.idFormaPagoAsignacion,
		
		(CASE
			WHEN (pago_asig.descripcionFormaPagoAsignacion IS NULL OR pago_asig.descripcionFormaPagoAsignacion = '') THEN
				prov.nombre
			WHEN (pago_asig.descripcionFormaPagoAsignacion IS NOT NULL AND pago_asig.descripcionFormaPagoAsignacion <> '') THEN
				pago_asig.descripcionFormaPagoAsignacion
		END) AS descripcionFormaPagoAsignacion,
		
		COUNT(*) AS cant_unidades
	FROM an_pedido_compra ped_comp
		INNER JOIN an_encabezadocartasolicitud carta_sol ON (ped_comp.idPedidoCompra = carta_sol.idPedidoCompra)
		INNER JOIN an_solicitud_factura det_ped_comp ON (ped_comp.idPedidoCompra = det_ped_comp.idPedidoCompra)
		INNER JOIN an_detallecartasolicitud det_carta_sol ON (carta_sol.idCartaSolicitud = det_carta_sol.idCartaSolicitud)
		AND (det_carta_sol.idSolicitud = det_ped_comp.idSolicitud)
		INNER JOIN formapagoasignacion pago_asig ON (det_ped_comp.idFormaPagoAsignacion = pago_asig.idFormaPagoAsignacion)
		LEFT JOIN cp_proveedor prov ON (pago_asig.idProveedor = prov.id_proveedor) %s
	GROUP BY idCartaSolicitud, idPedidoCompra, descripcionFormaPagoAsignacion", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "16%", $pageNum, "fechaCartaSolicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "20%", $pageNum, "idCartaSolicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Carta");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "48%", $pageNum, "descripcionFormaPagoAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "16%", $pageNum, "cant_unidades", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidades");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".htmlentities(date(spanDateFormat, strtotime($row['fechaCartaSolicitud'])))."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['idCartaSolicitud'])."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities($row['descripcionFormaPagoAsignacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities($row['cant_unidades'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_solicitud_compra_pdf.php?valBusq=%s|%s|%s', 900, 700);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Carta PDF\"/>",
					$row['idPedidoCompra'],
					$row['idCartaSolicitud'],
					$row['idFormaPagoAsignacion']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaSolicitudCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaPedidoDetalle($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_comp.idPedidoCompra = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("forma_pago_asig.idFormaPagoAsignacion NOT IN (4)");
	
	$query = sprintf("SELECT
		ped_comp.idPedidoCompra,
		
		ped_comp_det.idSolicitud,
		uni_fis.id_unidad_fisica,
		uni_bas.id_uni_bas,
		uni_bas.nom_uni_bas,
		modelo.nom_modelo,
		vers.nom_version,
		ped_comp_det.flotilla,
		
		forma_pago_asig.idFormaPagoAsignacion,
		prov.id_proveedor,
		prov.nombre,
		(CASE
			WHEN (descripcionFormaPagoAsignacion IS NULL OR descripcionFormaPagoAsignacion = '') THEN
				prov.nombre
			WHEN (descripcionFormaPagoAsignacion IS NOT NULL AND descripcionFormaPagoAsignacion <> '') THEN
				forma_pago_asig.descripcionFormaPagoAsignacion
		END) AS descripcionFormaPagoAsignacion,
		
		ped_comp_det.estado,
		
		(uni_fis.estado_venta + 0) AS estado_venta,
		uni_fis.estado_compra,
		uni_fis.serial_carroceria,
		uni_fis.placa,
		
		(CASE estado_compra
			WHEN 'COMPRADO' THEN
				(SELECT fact_comp_det_unidad.id_factura_compra FROM an_factura_compra_detalle_unidad fact_comp_det_unidad
				WHERE fact_comp_det_unidad.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			WHEN 'REGISTRADO' THEN
				(SELECT fact_det_unidad.id_factura FROM cp_factura_detalle_unidad fact_det_unidad
				WHERE fact_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad)
		END) AS id_factura_compra,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_factura_detalle_unidad fact_comp_det_unidad
			INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
			INNER JOIN cp_retenciondetalle retencion_det ON (fact_comp_det_unidad.id_factura = retencion_det.idFactura)
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT id_notacargo FROM cp_notadecargo
		WHERE id_detalles_pedido_compra = ped_comp_det.idSolicitud) AS id_nota_cargo,
		
		(SELECT numero_notacargo FROM cp_notadecargo
		WHERE id_detalles_pedido_compra = ped_comp_det.idSolicitud) AS numero_nota_cargo
			
	FROM cp_proveedor prov
		RIGHT JOIN formapagoasignacion forma_pago_asig ON (prov.id_proveedor = forma_pago_asig.idProveedor)
		INNER JOIN an_solicitud_factura ped_comp_det ON (forma_pago_asig.idFormaPagoAsignacion = ped_comp_det.idFormaPagoAsignacion)
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN an_uni_bas uni_bas ON (ped_comp_det.idUnidadBasica = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "8%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad Básica");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "14%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "20%", $pageNum, "nom_version", $campOrd, $tpOrd, $valBusq, $maxRows, "Versión");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "24%", $pageNum, "descripcionFormaPagoAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "8%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Unidad Física");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "16%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "10%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		if ($row['estado'] <= 5) {
			switch ($row['estado_venta']) {
				case "" : $imgEstatusVehiculo = "<img src=\"../img/iconos/error.png\" title=\"No Registrado\"/>"; break;
				case 1 : $imgEstatusVehiculo = "<img src=\"../img/iconos/transito.png\" title=\"En Transito\"/>"; break;
				case 2 : $imgEstatusVehiculo = "<img src=\"../img/iconos/almacen_buen_estado.png\" title=\"Inspeccionado\"/>"; break;
				case 3 : $imgEstatusVehiculo = "<img src=\"../img/iconos/siniestrado.png\" title=\"Siniestrado\"/>"; break;
				case 4 : $imgEstatusVehiculo = "<img src=\"../img/iconos/accept.png\" title=\"Disponible\"/>"; break;
				case 5 : $imgEstatusVehiculo = "<img src=\"../img/iconos/car_error.png\" title=\"Reservado\"/>"; break;
				case 6 : $imgEstatusVehiculo = "<img src=\"../img/iconos/car_go.png\" title=\"Vendido\"/>"; break;
				default : $imgEstatusVehiculo = $row['estado_venta'];
			}
		} else {
			if ($row['estado'] == 6)
				$imgEstatusVehiculo = "<img src=\"../img/iconos/cancel.png\" title=\"Anulado\"/>";
			
			$queryNotaCred = sprintf("SELECT nota_cred.numero_nota_credito FROM cp_pagos_documentos pagos_doc
				INNER JOIN cp_notacredito nota_cred ON (pagos_doc.id_documento = nota_cred.id_notacredito)
			WHERE (pagos_doc.id_documento_pago = %s
				AND pagos_doc.tipo_documento_pago = 'ND')
				AND pagos_doc.tipo_pago = 'NC';",
				valTpDato($row['id_nota_cargo'], "int"));
			$rsNotaCred = mysql_query($queryNotaCred);
			if (!$rsNotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		switch ($row['flotilla']) {
			case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
			case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
			default : $imgEstatusUnidadAsignacion = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusVehiculo."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_modelo'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_version'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$row['descripcionFormaPagoAsignacion']."</td>";
				$htmlTb .= "</tr>";
				if ($row['idFormaPagoAsignacion'] > 4) {
					$htmlTb .= "<tr>";
						$htmlTb .= "<td class=\"textoNegrita_10px\">Nota Cargo Nro.: ".$row['numero_nota_cargo']."</td>";
					$htmlTb .= "</tr>";
					if ($rsNotaCred) {
						while ($rowNotaCred = mysql_fetch_assoc($rsNotaCred)) {
							$htmlTb .= "<tr>";
								$htmlTb .= "<td class=\"textoNegrita_10px textoRojoNegrita\">Nota Cred Nro.: ".$rowNotaCred['numero_nota_credito']."</td>";
							$htmlTb .= "</tr>";
						}
					}
				}
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_unidad_fisica']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['serial_carroceria']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$row['placa']."</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 4 || $row['estado'] == 5) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_checklist_compra.php?id_unidad_fisica=%s&view=print', 960, 550);\" src=\"../img/iconos/chk_list_act.png\" title=\"Imprimir Inspección\"/>",
					$row['id_unidad_fisica']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 5 && $row['estado_compra'] == "REGISTRADO") {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro de Compra PDF\"/>",
					$row['id_factura_compra']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 5 && $row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir Comprobante de Retención\"/>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"15\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadesPedido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"eliminarPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"formSolicitudCompra");
$xajax->register(XAJAX_FUNCTION,"formUnidadPedido");
$xajax->register(XAJAX_FUNCTION,"listaPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"listaSolicitudCompra");
$xajax->register(XAJAX_FUNCTION,"listaPedidoDetalle");
?>