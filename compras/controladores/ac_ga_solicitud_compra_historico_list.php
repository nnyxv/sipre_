<?php 
function BuscarSolicituComp($valorFrom){//HACE LA BUSQUEDA
	$objResponse = new xajaxResponse();
	
	$numSolicitud = (strpos($valorFrom["txtNroSolicitud"],"-")) ? substr($valorFrom["txtNroSolicitud"], 4) : $valorFrom["txtNroSolicitud"] ;
	
	$valBus = sprintf("%s|%s|%s|%s|%s|%s",
		$valorFrom["lstEmpresa"],
		$numSolicitud,
		$valorFrom["lisTipCompras"],
		$valorFrom["txtCriterio"],
		$valorFrom["txtFechaDesde"],
		$valorFrom["txtFechaHasta"]);
//$objResponse->alert($valorFrom["txtNroSolicitud"]);
	$objResponse->loadCommands(listadoSolicitudCompra(0,'','', $valBus));
		
	return $objResponse;
}

function combLstTipCompra() { //TIPO COMPRAS
	$objResponse = new xajaxResponse();
	
	$html .= "<select id=\"lisTipCompras\" name=\"lisTipCompras\" class=\"inputHabilitado\" onChange=\"byId('btnBuscar').click();\" >";
	$html .= "<option value=\"\">[ Seleccione ]</option>";
		
	$query ="SELECT * FROM ga_tipo_seccion where id_tipo_seccion IN (2,3,4);";
	
	mysql_query("SET NAMES 'utf8'");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	while($rows = mysql_fetch_array($rs)){
		$html .= "<option value=".$rows["id_tipo_seccion"].">".$rows["tipo_seccion"]."</option>";
	}
	
	$html .= "</select>";
		
	$objResponse->assign("tdlsttipCompra","innerHTML",$html);

	return $objResponse;
}

function listadoSolicitudCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {//LISTA TODAS LAS SOLICITUDES
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_ga_solicitudes.id_estado_solicitud_compras IN (5)");
	
	if($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
	}
	
	if($valCadBusq[1] != "-1" && $valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("numero_solicitud = %s",
			valTpDato($valCadBusq[1],"int"));
	}
	
	if($valCadBusq[2] != "-1" && $valCadBusq[2] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_compra = %s",
			valTpDato($valCadBusq[2],"int"));
	}
	
	if($valCadBusq[3] != "-1" && $valCadBusq[3] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" (numero_solicitud LIKE %s
			OR nombre_empresa LIKE %s
			OR nombre_departamento LIKE %s
			OR nombre_unidad_centro_costo LIKE %s
			OR cp_proveedor.id_proveedor LIKE %s
			OR cp_proveedor.nombre LIKE %s
			OR tipo_seccion LIKE %s
			OR estado_solicitud_compras LIKE %s
			OR observaciones_proveedor LIKE %s)",
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"));
	}
	
	if($valCadBusq[4] != "" && $valCadBusq[5] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_solicitud BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[4])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[5])),"date"));
	}

	$query = sprintf("SELECT 
		vw_ga_solicitudes.*,
		SUM(cantidad * precio_sugerido) AS total_precio_cantidad, 
		cp_proveedor.nombre AS nombre_proveedor, 
		CONCAT_WS('-', codigo_empresa, numero_solicitud) AS numero_solicitud,
		ga_estado_solicitud_compra.estado_solicitud_compras,
		ga_tipo_seccion.tipo_seccion,
		
		(SELECT COUNT(id_detalle_solicitud_compra) 
			FROM ga_detalle_solicitud_compra 
			WHERE id_solicitud_compra = vw_ga_solicitudes.id_solicitud_compra 
			GROUP BY id_solicitud_compra) AS items
			
		FROM vw_ga_solicitudes
		LEFT JOIN cp_proveedor ON (cp_proveedor.id_proveedor = vw_ga_solicitudes.id_proveedor)
		INNER JOIN ga_estado_solicitud_compra ON (vw_ga_solicitudes.id_estado_solicitud_compras = ga_estado_solicitud_compra.id_estado_solicitud_compras)
		INNER JOIN ga_tipo_seccion ON (vw_ga_solicitudes.tipo_compra = ga_tipo_seccion.id_tipo_seccion)
		LEFT JOIN ga_detalle_solicitud_compra ON (ga_detalle_solicitud_compra.id_solicitud_compra = vw_ga_solicitudes.id_solicitud_compra)
		%s GROUP BY ga_detalle_solicitud_compra.id_solicitud_compra ",$sqlBusq);

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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "15%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "5%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "5%", $pageNum, "fecha_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Solicitud");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "10%", $pageNum, "tipo_seccion", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Compra");		
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "25%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor ");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "25%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaciones ");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "5%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");		
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "25%", $pageNum, "total_precio_cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
		
		//$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "10%", $pageNum, "estado_solicitud_compras", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
		$htmlTh .= "<td class=\"noprint\" colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch ($row['id_estado_solicitud_compras']) {
			case 5: $imgEstatus = "<img src=\"../img/iconos/aprob_control_calidad.png\" title=\"Solicitud Ordenada\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\"  height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			
			$htmlTb .= "<td >".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"left\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_solicitud']))."</td>";
			$htmlTb .= "<td align=\"\">".utf8_encode($row['tipo_seccion'])."</td>";			
			$htmlTb .= "<td align=\"\" >".utf8_encode($row["id_proveedor"].".- ".$row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"\">".utf8_encode($row['observaciones_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['items'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_precio_cantidad'],2,".",",")."</td>";
						
			//$htmlTb .= "<td align=\"\">".utf8_encode($row['estado_solicitud_compras'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgSolicitudPDF%s\" onclick=\"abrePdf(%s,%s)\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Solicitud PDF\"/></td>",
				$contFila,
				$row['id_solicitud_compra'],
				$_SESSION['idEmpresaUsuarioSysGts']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("tdListSolictComp","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"BuscarSolicituComp");
$xajax->register(XAJAX_FUNCTION,"listadoSolicitudCompra");
$xajax->register(XAJAX_FUNCTION,"combLstTipCompra");

?>