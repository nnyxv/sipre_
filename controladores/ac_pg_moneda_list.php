<?php


function buscarMoneda($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaMoneda(0, "idmoneda", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarMoneda($idMoneda, $frmListaMoneda) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"pg_moneda_list","eliminar")) { return $objResponse; }

	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMoneda, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaMoneda(
		$frmListaMoneda['pageNum'],
		$frmListaMoneda['campOrd'],
		$frmListaMoneda['tpOrd'],
		$frmListaMoneda['valBusq']));

	return $objResponse;
}

function formMoneda($idMoneda, $frmMoneda) {
	$objResponse = new xajaxResponse();
	
	if ($idMoneda > 0) {
		if (!xvalidaAcceso($objResponse,"pg_moneda_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarMoneda').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
			valTpDato($idMoneda, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdMoneda","value",$row['idmoneda']);
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['descripcion']));
		$objResponse->assign("txtAbreviacion","value",utf8_encode($row['abreviacion']));
		$objResponse->call("selectedOption","lstIncluirImpuestos",$row['incluir_impuestos']);
		$objResponse->call("selectedOption","lstPredeterminada",$row['predeterminada']);
		$objResponse->call("selectedOption","lstEstatus",$row['estatus']);
	} else {
		if (!xvalidaAcceso($objResponse,"pg_moneda_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarMoneda').click();"); return $objResponse; }
		
		$objResponse->assign("txtMontoTasaCambio","value",number_format(0,2,".",","));
	}
	
	return $objResponse;
}

function guardarMoneda($frmMoneda, $frmListaMoneda) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmMoneda['hddIdMoneda'] > 0) {
		if (!xvalidaAcceso($objResponse,"pg_moneda_list","editar")) { errorGuardarMoneda($objResponse); return $objResponse; }
		
		if ($frmMoneda['lstPredeterminada'] == 1) {
			$updateSQL = sprintf("UPDATE pg_monedas SET 
				predeterminada = 0;");
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarMoneda($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		$updateSQL = sprintf("UPDATE pg_monedas SET
			descripcion = %s,
			abreviacion = %s,
			incluir_impuestos = %s,
			predeterminada = %s,
			estatus = %s
		WHERE idmoneda = %s;",
			valTpDato($frmMoneda['txtDescripcion'], "text"),
			valTpDato($frmMoneda['txtAbreviacion'], "text"),
			valTpDato($frmMoneda['lstIncluirImpuestos'], "boolean"),
			valTpDato($frmMoneda['lstPredeterminada'], "boolean"),
			valTpDato($frmMoneda['lstEstatus'], "boolean"),
			valTpDato($frmMoneda['hddIdMoneda'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			errorGuardarMoneda($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_moneda_list","insertar")) { errorGuardarMoneda($objResponse); return $objResponse; }
		
		if ($frmMoneda['lstPredeterminada'] == 1) {
			$updateSQL = sprintf("UPDATE pg_monedas SET 
				predeterminada = 0;");
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarMoneda($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		$insertSQL = sprintf("INSERT INTO pg_monedas (descripcion, abreviacion, incluir_impuestos, predeterminada, estatus)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($frmMoneda['txtDescripcion'], "text"),
			valTpDato($frmMoneda['txtAbreviacion'], "text"),
			valTpDato($frmMoneda['lstIncluirImpuestos'], "boolean"),
			valTpDato($frmMoneda['lstPredeterminada'], "boolean"),
			valTpDato($frmMoneda['lstEstatus'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			errorGuardarMoneda($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarMoneda($objResponse);
	$objResponse->alert("Moneda Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarMoneda').click();");
	
	$objResponse->loadCommands(listaMoneda(
		$frmListaMoneda['pageNum'],
		$frmListaMoneda['campOrd'],
		$frmListaMoneda['tpOrd'],
		$frmListaMoneda['valBusq']));
	
	return $objResponse;
}

function listaMoneda($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = %s",
			valTpDato($valCadBusq[0], "boolean"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(descripcion LIKE %s
		OR abreviacion LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM pg_monedas %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaMoneda", "8%", $pageNum, "idmoneda", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaMoneda", "50%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Moneda");
		$htmlTh .= ordenarCampo("xajax_listaMoneda", "14%", $pageNum, "abreviacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Abreviación");
		$htmlTh .= ordenarCampo("xajax_listaMoneda", "14%", $pageNum, "incluir_impuestos", $campOrd, $tpOrd, $valBusq, $maxRows, "Incluir Impuestos");
		$htmlTh .= ordenarCampo("xajax_listaMoneda", "14%", $pageNum, "predeterminada", $campOrd, $tpOrd, $valBusq, $maxRows, "Predeterminado");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = ""; break;
		}
		
		$incluirImp = ($row['incluir_impuestos'] == 1) ? "SI" : "-";	
		$predet = ($row['predeterminada'] == 1) ? "SI" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['idmoneda'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['abreviacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($incluirImp)."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($predet)."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblMoneda', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['idmoneda']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/></td>",
				$row['idmoneda']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMoneda(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMoneda(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMoneda(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMoneda(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMoneda(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMoneda","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarMoneda");
$xajax->register(XAJAX_FUNCTION,"eliminarMoneda");
$xajax->register(XAJAX_FUNCTION,"formMoneda");
$xajax->register(XAJAX_FUNCTION,"guardarMoneda");
$xajax->register(XAJAX_FUNCTION,"listaMoneda");

function errorGuardarMoneda($objResponse) {
	$objResponse->script("
	byId('btnGuardarMoneda').disabled = false;
	byId('btnCancelarMoneda').disabled = false;");
}
?>