<?php


function buscarTaller($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaTaller(0, "id_taller", "DESC", $valBusq));
	
	return $objResponse;
}

function eliminarTallerBloque($frmListaTaller) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_taller_list","eliminar")) { return $objResponse; }
	
	if (isset($frmListaTaller['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach ($frmListaTaller['cbxItm'] as $indiceItm => $valorItm) {
			$deleteSQL = sprintf("DELETE FROM iv_talleres WHERE id_taller = %s",
				valTpDato($valorItm, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert(utf8_encode("Eliminación realizada con éxito"));
		
		$objResponse->loadCommands(listaTaller(
			$frmListaTaller['pageNum'],
			$frmListaTaller['campOrd'],
			$frmListaTaller['tpOrd'],
			$frmListaTaller['valBusq']));
	}
		
	return $objResponse;
}

function formTaller($idTaller) {
	$objResponse = new xajaxResponse();
	
	if ($idTaller > 0) {
		if (!xvalidaAcceso($objResponse,"iv_taller_list","editar")) { return $objResponse; }
		
		$queryTaller = sprintf("SELECT *,
			(CASE
				WHEN lrif IS NOT NULL THEN
					CONCAT_WS('-',lrif,rif)
				ELSE 
					rif
			END) AS rif_taller
		FROM iv_talleres
		WHERE id_taller = %s",
			valTpDato($idTaller, "int"));
		$rsTaller = mysql_query($queryTaller);
		if (!$rsTaller) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTaller = mysql_fetch_assoc($rsTaller);
		
		$objResponse->assign("hddIdTaller","value",$idTaller);
		$objResponse->assign("txtNombre","value",utf8_encode($rowTaller['nombre']));
		$objResponse->assign("txtCedula","value",$rowTaller['rif_taller']);
		$objResponse->assign("txtDireccion","value",utf8_encode($rowTaller['direccion']));
		$objResponse->assign("txtTelefono","value",$rowTaller['telefono']);
		$objResponse->assign("txtContacto","value",utf8_encode($rowTaller['contacto']));
	} else {
		if (!xvalidaAcceso($objResponse,"iv_taller_list","insertar")) { return $objResponse; }
	}
	
	return $objResponse;
}

function guardarTaller($frmTaller, $frmListaTaller) {
	$objResponse = new xajaxResponse();
	
	global $arrayValidarCI;
	global $arrayValidarRIF;
	
	mysql_query("START TRANSACTION;");
	
	$arrayValidar = array_merge($arrayValidarCI, $arrayValidarRIF);
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmTaller['txtCedula'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtCedula').className = 'inputErrado'");
			return $objResponse->alert(utf8_encode("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$txtRifTaller = explode("-", $frmTaller['txtCedula']);
	if (is_numeric($txtRifTaller[0]) == true) {
		$txtRifTaller = implode("-",$txtRifTaller);
	} else {
		$txtLrifTaller = $txtRifTaller[0];
		array_shift($txtRifTaller);
		$txtRifTaller = implode("-",$txtRifTaller);
	}
	
	if ($frmTaller['hddIdTaller'] > 0) {
		if (!xvalidaAcceso($objResponse,"iv_taller_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE iv_talleres SET
			lrif = %s,
			rif = %s,
			nombre = %s,
			direccion = %s,
			telefono = %s,
			contacto = %s
		WHERE id_taller = %s",
			valTpDato($txtLrifTaller, "text"),
			valTpDato($txtRifTaller, "text"),
			valTpDato($frmTaller['txtNombre'], "text"),
			valTpDato($frmTaller['txtDireccion'], "text"),
			valTpDato($frmTaller['txtTelefono'], "text"),
			valTpDato($frmTaller['txtContacto'], "text"),
			valTpDato($frmTaller['hddIdTaller'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_taller_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO iv_talleres (lrif, rif, nombre, direccion, telefono, contacto)
		VALUE (%s, %s, %s, %s, %s, %s)",
			valTpDato($txtLrifTaller, "text"),
			valTpDato($txtRifTaller, "text"),
			valTpDato($frmTaller['txtNombre'], "text"),
			valTpDato($frmTaller['txtDireccion'], "text"),
			valTpDato($frmTaller['txtTelefono'], "text"),
			valTpDato($frmTaller['txtContacto'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Taller Guardado con Éxito"));
	
	$objResponse->script("byId('btnCancelarTaller').click();");
	
	$objResponse->loadCommands(listaTaller(
		$frmListaTaller['pageNum'],
		$frmListaTaller['campOrd'],
		$frmListaTaller['tpOrd'],
		$frmListaTaller['valBusq']));
		
	return $objResponse;
}

function listaTaller($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("CONCAT_WS('-', lrif, rif) LIKE %s
			OR nombre LIKE %s
			OR contacto LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_taller,
		CONCAT_WS('-', lrif, rif) AS rif_taller,
		nombre,
		telefono,
		contacto,
		status
	FROM iv_talleres %s", $sqlBusq);
	
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
		$htmlTh .= "<td class=\"noprint\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaTaller", "11%", $pageNum, "rif_taller", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaTaller", "44%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaTaller", "20%", $pageNum, "telefono", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Teléfono(s)"));
		$htmlTh .= ordenarCampo("xajax_listaTaller", "25%", $pageNum, "contacto", $campOrd, $tpOrd, $valBusq, $maxRows, "Contacto");
		$htmlTh .= "<td class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['status']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td class=\"noprint\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_taller']);
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_taller']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['telefono'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['contacto'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblTaller', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Taller\"/></a>",
					$contFila,
					$row['id_taller']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTaller(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTaller(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTaller(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTaller(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTaller(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaTalleres","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarTaller");
$xajax->register(XAJAX_FUNCTION,"eliminarTallerBloque");
$xajax->register(XAJAX_FUNCTION,"formTaller");
$xajax->register(XAJAX_FUNCTION,"guardarTaller");
$xajax->register(XAJAX_FUNCTION,"listaTaller");
?>