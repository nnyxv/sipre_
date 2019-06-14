<?php


function buscarMarca($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaMarca(0, "id_marca", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarMarca($idMarca, $frmListaMarca) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_marca_list","eliminar")) { return $objResponse; }

	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_marca WHERE id_marca = %s;",
		valTpDato($idMarca, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con éxito.");
	
	$objResponse->loadCommands(listaMarca(
		$frmListaMarca['pageNum'],
		$frmListaMarca['campOrd'],
		$frmListaMarca['tpOrd'],
		$frmListaMarca['valBusq']));

	return $objResponse;
}

function formMarca($idMarca, $frmMarca) {
	$objResponse = new xajaxResponse();
	
	if ($idMarca > 0) {
		if (!xvalidaAcceso($objResponse,"an_marca_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarMarca').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM an_marca
		WHERE id_marca = %s;",
			valTpDato($idMarca, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdMarca","value",$row['id_marca']);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nom_marca']));
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['des_marca']));
	} else {
		if (!xvalidaAcceso($objResponse,"an_marca_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarMarca').click();"); return $objResponse; }
	}
	
	return $objResponse;
}

function guardarMarca($frmMarca, $frmListaMarca) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmMarca['hddIdMarca'] > 0) {
		if (!xvalidaAcceso($objResponse,"an_marca_list","editar")) { errorGuardarMarca($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_marca SET
			nom_marca = %s,
			des_marca = %s
		WHERE id_marca = %s;",
			valTpDato($frmMarca['txtNombre'], "text"),
			valTpDato($frmMarca['txtDescripcion'], "text"),
			valTpDato($frmMarca['hddIdMarca'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			errorGuardarMarca($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"an_marca_list","insertar")) { errorGuardarMarca($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO an_marca (nom_marca, des_marca)
		VALUE (%s, %s);",
			valTpDato($frmMarca['txtNombre'], "text"),
			valTpDato($frmMarca['txtDescripcion'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			errorGuardarMarca($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarMarca($objResponse);
	$objResponse->alert("Marca guardada con éxito.");
	
	$objResponse->script("
	byId('btnCancelarMarca').click();");
	
	$objResponse->loadCommands(listaMarca(
		$frmListaMarca['pageNum'],
		$frmListaMarca['campOrd'],
		$frmListaMarca['tpOrd'],
		$frmListaMarca['valBusq']));
	
	return $objResponse;
}

function listaMarca($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_marca LIKE %s
		OR des_marca LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_marca %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMarca", "8%", $pageNum, "id_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaMarca", "46%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
		$htmlTh .= ordenarCampo("xajax_listaMarca", "46%", $pageNum, "des_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_marca'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_marca'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_marca'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblMarca', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_marca']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_marca']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMarca(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMarca(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMarca(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMarca(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMarca(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMarca","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarMarca");
$xajax->register(XAJAX_FUNCTION,"eliminarMarca");
$xajax->register(XAJAX_FUNCTION,"formMarca");
$xajax->register(XAJAX_FUNCTION,"guardarMarca");
$xajax->register(XAJAX_FUNCTION,"listaMarca");

function errorGuardarMarca($objResponse) {
	$objResponse->script("
	byId('btnGuardarMarca').disabled = false;
	byId('btnCancelarMarca').disabled = false;");
}
?>