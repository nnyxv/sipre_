<?php


function buscarAno($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaAno(0, "nom_ano", "DESC", $valBusq));
	
	return $objResponse;
}

function eliminarAno($idAno, $frmListaAno) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_ano_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_ano WHERE id_ano = %s;",
		valTpDato($idAno, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) {
		if (mysql_errno() == 1451) {
			return $objResponse->alert("No se puede eliminar el registro debido a que tiene otros relacionados a él"."\nLine: ".__LINE__);
		} else {
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaAno(
		$frmListaAno['pageNum'],
		$frmListaAno['campOrd'],
		$frmListaAno['tpOrd'],
		$frmListaAno['valBusq']));
	
	return $objResponse;
}

function formAno($idAno, $frmAno) {
	$objResponse = new xajaxResponse();
	
	if ($idAno > 0) {
		if (!xvalidaAcceso($objResponse,"an_ano_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAno').click();"); return $objResponse; }
	
		$queryAno = sprintf("SELECT * FROM an_ano WHERE id_ano = %s;",
			valTpDato($idAno, "int"));
		$rsAno = mysql_query($queryAno);
		if (!$rsAno) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
		$rowAno = mysql_fetch_array($rsAno);
		
		$objResponse->assign("hddIdAno","value",$rowAno['id_ano']);
		$objResponse->assign("txtNombreAno","value",$rowAno['nom_ano']);
		$objResponse->assign("txtDescripcionAno","value",$rowAno['des_ano']);
	} else {
		if (!xvalidaAcceso($objResponse,"an_ano_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarMoneda').click();"); return $objResponse; }
	}
	
	return $objResponse;
}

function guardarAno($frmAno, $frmListaAno) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmAno['hddIdAno'] > 0) {
		if (!xvalidaAcceso($objResponse,"an_ano_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_ano SET
			nom_ano = %s,
			des_ano = %s
		WHERE id_ano = %s;",
			valTpDato($frmAno['txtNombreAno'], "text"),
			valTpDato($frmAno['txtDescripcionAno'], "text"),
			valTpDato($frmAno['hddIdAno'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"an_ano_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO an_ano (nom_ano, des_ano)
		VALUES (%s, %s);",
			valTpDato($frmAno['txtNombreAno'], "text"),
			valTpDato($frmAno['txtDescripcionAno'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Año Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarAno').click();");
	
	$objResponse->loadCommands(listaAno(
		$frmListaAno['pageNum'],
		$frmListaAno['campOrd'],
		$frmListaAno['tpOrd'],
		$frmListaAno['valBusq']));
	
	return $objResponse;
}

function listaAno($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_ano LIKE %s
		OR des_ano LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_ano color %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaAno", "6%", $pageNum, "id_ano", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaAno", "45%", $pageNum, "nom_ano", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaAno", "45%", $pageNum, "des_ano", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_ano']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_ano'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_ano'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAno', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_ano']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar\"/></a>",
					$row['id_ano']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAno(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAno(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAno(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAno(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAno(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("divListaAno","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarAno");
$xajax->register(XAJAX_FUNCTION,"eliminarAno");
$xajax->register(XAJAX_FUNCTION,"formAno");
$xajax->register(XAJAX_FUNCTION,"guardarAno");
$xajax->register(XAJAX_FUNCTION,"listaAno");
?>