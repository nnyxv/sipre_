<?php


function buscarAnticipo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['cbxNoCancelado'],
		$frmBuscar['cbxCancelado'],
		$frmBuscar['cbxParcialCancelado'],
		$frmBuscar['cbxAsignado'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstModulo'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaAnticipo(0, "id_anticipo", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstModulo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".htmlentities($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function listaAnticipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("anticipo.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if (($valCadBusq[1] != "-1" && $valCadBusq[1] != "")
	|| ($valCadBusq[2] != "-1" && $valCadBusq[2] != "")
	|| ($valCadBusq[3] != "-1" && $valCadBusq[3] != "")
	|| ($valCadBusq[4] != "-1" && $valCadBusq[4] != "")) {
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") $array[] = $valCadBusq[1];
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") $array[] = $valCadBusq[2];
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") $array[] = $valCadBusq[3];
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") $array[] = $valCadBusq[4];
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("anticipo.estado IN (%s)",
			valTpDato(implode(",",$array), "campo"));
	}
	
	if ($valCadBusq[5] != "" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("anticipo.fechaanticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[6])),"date"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("anticipo.idDepartamento = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR anticipo.numeroAnticipo LIKE %s)",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT anticipo.*,
		(CASE anticipo.estado
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Asignado Parcial'
			WHEN 3 THEN 'Asignado'
		END) AS estado_anticipo,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		
		anticipo.montoanticipo,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_proveedor prov
		INNER JOIN cp_anticipo anticipo ON (prov.id_proveedor = anticipo.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (anticipo.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "8%", $pageNum, "fechaanticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "8%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "40%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "estado_anticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "saldoanticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "montoanticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Anticipo");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['idDepartamento']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaanticipo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroAnticipo']."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['estado_anticipo']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoanticipo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoanticipo'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"window.open('cp_anticipo_form.php?id=%s&vw=v','_self');\" src=\"../img/iconos/ico_view.png\" /></td>",
				$row['id_anticipo']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarAnticipo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"listaAnticipo");
?>