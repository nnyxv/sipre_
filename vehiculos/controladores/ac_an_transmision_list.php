<?php

function buscarTransmision($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listadoTransmision(0, "", "", $valBusq));
	
	return $objResponse;

}

function cargarTransmision($idTransmision) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_transmision_list","editar")) {
		$query = sprintf("SELECT * FROM an_transmision
		WHERE id_transmision = %s",
			valTpDato($idTransmision, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdTransmision","value",$idTransmision);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nom_transmision']));
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['des_transmision']));
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Transmisión");
		$objResponse->script("
			if ($('divFlotante').style.display == 'none') {
				$('divFlotante').style.display='';
				centrarDiv($('divFlotante'));
				
				$('txtNombre').focus();
			}
		");
	}
	
	return $objResponse;
}

function eliminarTransmision($idTransmision, $valFormListaTransmision) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_transmision_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM an_transmision WHERE id_transmision = %s",
			valTpDato($idTransmision, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->loadCommands(listadoTransmision(
			$valFormListaTransmision['pageNum'],
			$valFormListaTransmision['campOrd'],
			$valFormListaTransmision['tpOrd'],
			$valFormListaTransmision['valBusq']));
	}
	
	return $objResponse;
}

function formTransmision() {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_transmision_list","insertar")) {
		$objResponse->script("
			document.forms['frmTransmision'].reset();
			$('hddIdTransmision').value = '';
			
			$('txtNombre').className = 'inputInicial';
			$('txtDescripcion').className = 'inputInicial';
		");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Transmisión");
		$objResponse->script("
			if ($('divFlotante').style.display == 'none') {
				$('divFlotante').style.display='';
				centrarDiv($('divFlotante'));
				
				$('txtNombre').focus();
			}
		");
	}
	
	return $objResponse;
}


function guardarTransmision($valForm, $valFormListaTransmision) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdTransmision'] > 0) {
		if (xvalidaAcceso($objResponse,"an_transmision_list","editar")) {
			$updateSQL = sprintf("UPDATE an_transmision SET
				nom_transmision = %s,
				des_transmision = %s
			WHERE id_transmision = %s;",
				valTpDato($valForm['txtNombre'], "text"),
				valTpDato($valForm['txtDescripcion'], "text"),
				valTpDato($valForm['hddIdTransmision'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"an_transmision_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO an_transmision (nom_transmision, des_transmision)
			VALUE (%s, %s);",
				valTpDato($valForm['txtNombre'], "text"),
				valTpDato($valForm['txtDescripcion'], "text"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Transmisión guardada con éxito.");
	
	$objResponse->script("$('divFlotante').style.display = 'none';");
	
	$objResponse->loadCommands(listadoTransmision(
		$valFormListaTransmision['pageNum'],
		$valFormListaTransmision['campOrd'],
		$valFormListaTransmision['tpOrd'],
		$valFormListaTransmision['valBusq']));
	
	return $objResponse;
}


function listadoTransmision($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("nom_transmision LIKE %s
			OR des_transmision LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_transmision %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoTransmision", "20%", $pageNum, "nom_transmision", $campOrd, $tpOrd, $valBusq, $maxRows, "Transmisión");
		$htmlTh .= ordenarCampo("xajax_listadoTransmision", "80%", $pageNum, "des_transmision", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nom_transmision'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_transmision'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_cargarTransmision('%s');\" src=\"../img/iconos/pencil.png\"/></td>",
				$row['id_transmision']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_transmision']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"4\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTransmision(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTransmision(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTransmision(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTransmision(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTransmision(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaTransmision","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarTransmision");
$xajax->register(XAJAX_FUNCTION,"cargarTransmision");
$xajax->register(XAJAX_FUNCTION,"eliminarTransmision");
$xajax->register(XAJAX_FUNCTION,"formTransmision");
$xajax->register(XAJAX_FUNCTION,"guardarTransmision");
$xajax->register(XAJAX_FUNCTION,"listadoTransmision");
?>