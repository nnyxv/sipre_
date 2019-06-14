<?php


function asignarMoneda($idMoneda, $objDestino) {
	$objResponse = new xajaxResponse();
	
	$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", valTpDato($idMoneda, "int"));
	$rsMoneda = mysql_query($queryMoneda);
	if (!$rsMoneda) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowMoneda = mysql_fetch_assoc($rsMoneda);
	
	$objResponse->assign("txtId".$objDestino,"value",$rowMoneda['idmoneda']);
	$objResponse->assign("txt".$objDestino,"value",utf8_encode($rowMoneda['descripcion']." (".$rowMoneda['abreviacion'].")"));
	
	$objResponse->script("
	byId('btnCancelarListaMoneda').click();");
	
	return $objResponse;
}

function buscarMoneda($frmBuscarMoneda) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarMoneda['txtCriterioBuscarMoneda'],
		$frmBuscarMoneda['hddObjDestino']);
	
	$objResponse->loadCommands(listaMoneda(0, "idmoneda", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarTasaCambio($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaTasaCambio(0, "id_tasa_cambio", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarTasaCambio($idTasaCambio, $frmListaTasaCambio) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"pg_tasa_cambio_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM pg_tasa_cambio WHERE id_tasa_cambio = %s;",
			valTpDato($idTasaCambio, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	
		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->loadCommands(listaTasaCambio(
			$frmListaTasaCambio['pageNum'],
			$frmListaTasaCambio['campOrd'],
			$frmListaTasaCambio['tpOrd'],
			$frmListaTasaCambio['valBusq']));
	}
	
	return $objResponse;
}

function formTasaCambio($idTasaCambio) {
	$objResponse = new xajaxResponse();
	
	if ($idTasaCambio > 0) {
		if (!xvalidaAcceso($objResponse,"pg_tasa_cambio_list","editar")) { $objResponse->script("byId('btnCancelarTasaCambio').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM pg_tasa_cambio
		WHERE id_tasa_cambio = %s;",
			valTpDato($idTasaCambio, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdTasaCambio","value",$row['id_tasa_cambio']);
		$objResponse->assign("txtNombreTasaCambio","value",utf8_encode($row['nombre_tasa_cambio']));
		$objResponse->loadCommands(asignarMoneda($row['id_moneda_extranjera'], "MonedaExtranjera"));
		$objResponse->loadCommands(asignarMoneda($row['id_moneda_nacional'], "MonedaNacional"));
		$objResponse->assign("txtMontoTasaCambio","value",number_format($row['monto_tasa_cambio'], 3, ".", ","));
		$objResponse->call("selectedOption","lstEstatus",$row['estatus']);
	} else {
		if (!xvalidaAcceso($objResponse,"pg_tasa_cambio_list","insertar")) { $objResponse->script("byId('btnCancelarTasaCambio').click();"); return $objResponse; }
			
		$objResponse->assign("txtMontoTasaCambio","value",number_format(0, 3, ".", ","));
	}
	
	return $objResponse;
}

function guardarTasaCambio($frmTasaCambio, $frmListaTasaCambio) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idTasaCambio = $frmTasaCambio['hddIdTasaCambio'];
	
	if ($idTasaCambio > 0) {
		if (!xvalidaAcceso($objResponse,"pg_tasa_cambio_list","editar")) { errorGuardarTasaCambio($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_tasa_cambio SET
			nombre_tasa_cambio = %s,
			id_moneda_extranjera = %s,
			id_moneda_nacional = %s,
			monto_tasa_cambio = %s,
			estatus = %s
		WHERE id_tasa_cambio = %s;",
			valTpDato($frmTasaCambio['txtNombreTasaCambio'], "text"),
			valTpDato($frmTasaCambio['txtIdMonedaExtranjera'], "int"),
			valTpDato($frmTasaCambio['txtIdMonedaNacional'], "int"),
			valTpDato($frmTasaCambio['txtMontoTasaCambio'], "real_inglesa"),
			valTpDato($frmTasaCambio['lstEstatus'], "boolean"),
			valTpDato($idTasaCambio, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			errorGuardarTasaCambio($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_tasa_cambio_list","insertar")) { errorGuardarTasaCambio($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_tasa_cambio (nombre_tasa_cambio, id_moneda_extranjera, id_moneda_nacional, monto_tasa_cambio, estatus)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($frmTasaCambio['txtNombreTasaCambio'], "text"),
			valTpDato($frmTasaCambio['txtIdMonedaExtranjera'], "int"),
			valTpDato($frmTasaCambio['txtIdMonedaNacional'], "int"),
			valTpDato($frmTasaCambio['txtMontoTasaCambio'], "real_inglesa"),
			valTpDato($frmTasaCambio['lstEstatus'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			errorGuardarTasaCambio($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarTasaCambio($objResponse);
	$objResponse->alert("Tasa de Cambio Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarTasaCambio').click();");
	
	$objResponse->loadCommands(listaTasaCambio(
		$frmListaTasaCambio['pageNum'],
		$frmListaTasaCambio['campOrd'],
		$frmListaTasaCambio['tpOrd'],
		$frmListaTasaCambio['valBusq']));
	
	return $objResponse;
}

function listaTasaCambio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(moneda.descripcion LIKE %s
		OR moneda_2.descripcion LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		tasa_cambio.id_tasa_cambio,
		tasa_cambio.nombre_tasa_cambio,
		CONCAT(moneda_2.abreviacion, '1.000 = ', moneda.abreviacion, tasa_cambio.monto_tasa_cambio) AS descripcion_tasa_cambio,
		tasa_cambio.id_moneda_nacional,
		moneda.descripcion AS descripcion_moneda_nacional,
		moneda.abreviacion AS abreviacion_moneda_nacional,
		tasa_cambio.id_moneda_extranjera,
		moneda_2.descripcion AS descripcion_moneda_extranjera,
		moneda_2.abreviacion AS abreviacion_moneda_extranjera,
		tasa_cambio.monto_tasa_cambio,
		tasa_cambio.estatus
	FROM pg_tasa_cambio tasa_cambio
		INNER JOIN pg_monedas moneda ON (tasa_cambio.id_moneda_nacional = moneda.idmoneda)
		INNER JOIN pg_monedas moneda_2 ON (tasa_cambio.id_moneda_extranjera = moneda_2.idmoneda) %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaTasaCambio", "35%", $pageNum, "nombre_tasa_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaTasaCambio", "15%", $pageNum, "descripcion_moneda_extranjera", $campOrd, $tpOrd, $valBusq, $maxRows, "Moneda Extranjera");
		$htmlTh .= ordenarCampo("xajax_listaTasaCambio", "15%", $pageNum, "descripcion_moneda_nacional", $campOrd, $tpOrd, $valBusq, $maxRows, "Moneda Nacional");
		$htmlTh .= ordenarCampo("xajax_listaTasaCambio", "15%", $pageNum, "monto_tasa_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "Tasa de Cambio");
		$htmlTh .= ordenarCampo("xajax_listaTasaCambio", "20%", $pageNum, "monto_tasa_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default: $imgEstatus = "";
		}
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_tasa_cambio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_moneda_extranjera']." (".$row['abreviacion_moneda_extranjera'].")")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_moneda_nacional']." (".$row['abreviacion_moneda_nacional'].")")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_tasa_cambio'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_tasa_cambio'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblTasaCambio', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_tasa_cambio']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/></td>",
				$row['id_tasa_cambio']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTasaCambio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTasaCambio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTasaCambio(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTasaCambio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTasaCambio(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaTasaCambio","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaMoneda($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(idmoneda LIKE %s
		OR descripcion LIKE %s
		OR abreviacion LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM pg_monedas %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaMoneda", "10%", $pageNum, "idmoneda", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaMoneda", "60%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Moneda");
		$htmlTh .= ordenarCampo("xajax_listaMoneda", "30%", $pageNum, "abreviacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Abreviación");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMoneda('".$row['idmoneda']."','".$valCadBusq[1]."');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['idmoneda'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['abreviacion'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMoneda","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarMoneda");
$xajax->register(XAJAX_FUNCTION,"buscarTasaCambio");
$xajax->register(XAJAX_FUNCTION,"buscarMoneda");
$xajax->register(XAJAX_FUNCTION,"eliminarTasaCambio");
$xajax->register(XAJAX_FUNCTION,"formTasaCambio");
$xajax->register(XAJAX_FUNCTION,"guardarTasaCambio");
$xajax->register(XAJAX_FUNCTION,"listaTasaCambio");
$xajax->register(XAJAX_FUNCTION,"listaMoneda");

function errorGuardarTasaCambio($objResponse) {
	$objResponse->script("
	byId('btnGuardarTasaCambio').disabled = false;
	byId('btnCancelarTasaCambio').disabled = false;");
}
?>