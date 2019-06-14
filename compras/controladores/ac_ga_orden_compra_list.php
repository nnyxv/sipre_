<?php

function buscar($valForm) {
	$objResponse = new xajaxResponse();
	
	$numSolicitud = (strpos($valForm["txtNroSolicitud"],"-")) ? substr($valForm["txtNroSolicitud"], 4) : $valForm["txtNroSolicitud"];

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstTipoCompra'],
		$numSolicitud,
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaPedidoCompra(0, "", "", $valBusq));
		
	return $objResponse;
}

function listaPedidoCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	  $sqlBusq .= $cond.sprintf(" id_estado_solicitud_compras IN (4) ");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" vw_ga_sol_comp.id_empresa = %s ",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if($valCadBusq[1] != "-1" && $valCadBusq[1] != "" && $valCadBusq[2] != "" && $valCadBusq[2] != ""){
		$cond = (strlen($sqlBusq) > 0) ? "AND" : "WHERE";
		$sqlBusq .= $cond.sprintf(" fecha_solicitud BETWEEN %s AND %s ",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"));
	}
	
	if($valCadBusq[3] != "-1" && $valCadBusq[3] != ""){
		$cond = (strlen($sqlBusq) > 0) ? "AND" : "WHERE";
		$sqlBusq .= $cond.sprintf(" tipo_compra = %s ",
			valTpDato($valCadBusq[3], "int"));		
	}
	
	if($valCadBusq[4] != "" && $valCadBusq[4] != ""){
		$cond = (strlen($sqlBusq) > 0) ? "AND" : "WHEE";
		$sqlBusq .= $cond.sprintf(" numero_solicitud = %s ",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" (numero_solicitud LIKE %s
			OR id_proveedor LIKE %s
			OR nombre_porveedor LIKE %s
			OR fecha_solicitud LIKE %s
			OR observaciones LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		vw_ga_sol_comp.id_empresa,
		id_solicitud_compra,
		nombre_empresa,
		id_estado_solicitud_compras,
		fecha_solicitud,
		CONCAT_WS('-',(SELECT codigo_empresa 
						FROM pg_empresa WHERE pg_empresa.id_empresa = vw_ga_sol_comp.id_empresa),
						numero_solicitud) AS numero_solicitud,
		tipo_compra,
		id_proveedor,
		nombre_porveedor,
		observaciones,
		items,
		total,
		id_estado_solicitud_compras
		FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		INNER JOIN vw_ga_solicitudes_compra vw_ga_sol_comp ON (vw_iv_emp_suc.id_empresa_reg = vw_ga_sol_comp.id_empresa) %s", $sqlBusq);
	
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
		$htmlTh .= "<td class=\"noprint\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "5%", $pageNum, "fecha_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Solicitud");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "5%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "10%", $pageNum, "tipo_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Compra");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "30%", $pageNum, "observaciones", $campOrd, $tpOrd, $valBusq, $maxRows, "Observacion");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "5%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "5%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pedido");
		$htmlTh .= "<td class=\"noprint\" colspan=\"2\"></td>";
    $htmlTh .="</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['tipo_compra']){
			case 2: $tipoCompra = "Cargos (Activos Fijo)"; break;
			case 3: $tipoCompra = "Servicios"; break;
			case 4: $tipoCompra = "Gastos / Activos"; break;	
		}
		
		switch($row['id_estado_solicitud_compras']){
			case 4: $estatus ="<img title=\"Ordenado\" src=\"../img/iconos/ico_aceptar_naranja.png\"/>"; break;
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$estatus."</td>";
			$htmlTb .= "<td align=\"left\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_solicitud']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td align=\"left\">".$tipoCompra."</td>";
			$htmlTb .= "<td>".utf8_encode($row["id_proveedor"].".- ".$row['nombre_porveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['observaciones'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['total'],2,".",",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('ga_orden_compra_form.php?id=%s','_self');\" src=\"../img/iconos/ico_importar.gif\" title=\"Aprobar\"/>",
					$row['id_solicitud_compra']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('reportes/ga_solicitud_compra_pdf.php?idSolCom=%s&session=%s')\" src=\"../img/iconos/page_white_acrobat.png\" title=\"PDF\"/></td>",
					$row['id_solicitud_compra'],$_SESSION['idEmpresaUsuarioSysGts']);//verVentana('ga_solicitud_compras_editar.php?view=print&id=%s', 1000, 500);\
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
$xajax->register(XAJAX_FUNCTION,"listaPedidoCompra");

?>