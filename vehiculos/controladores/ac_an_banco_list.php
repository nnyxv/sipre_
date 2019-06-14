<?php
function buscarBanco($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listadoBanco(0, "nombreBanco", "ASC", $valBusq));
	
	return $objResponse;

}


function cargarBanco($idBanco) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_banco_list","editar")) {
		$objResponse->script("
			document.forms['frmBanco'].reset();
			byId('hddIdBanco').value = '';
			
			byId('txtPorcentajeComisionFlat').className = 'inputHabilitado';
			byId('txtDiasBuenCobroLocal').className = 'inputHabilitado';
			byId('txtDiasBuenCobroForaneo').className = 'inputHabilitado';
		");
		
		$query = sprintf("SELECT * FROM bancos
		WHERE idBanco = %s",
			valTpDato($idBanco, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdBanco","value",$idBanco);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombreBanco']));
		$objResponse->assign("txtPorcentajeComisionFlat","value",utf8_encode($row['porcentaje_flat']));
		$objResponse->assign("txtDiasBuenCobroLocal","value",utf8_encode($row['diasSalvoBuenCobroLocales']));
		$objResponse->assign("txtDiasBuenCobroForaneo","value",utf8_encode($row['diasSalvoBuenCobroForaneos']));
		
		$objResponse->script("
			byId('tblBanco').style.display = '';
			byId('tblListaFactorFinanciero').style.display = 'none';
		");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Banco");
		$objResponse->script("
			if (byId('divFlotante').style.display == 'none') {
				byId('divFlotante').style.display = '';
				centrarDiv(byId('divFlotante'));
				byId('txtPorcentajeComisionFlat').focus();
			}
		");
	}
	
	return $objResponse;
}


function cargarFactor($idFactor) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_factor","editar")) {
		$objResponse->script("
			document.forms['frmFactorFinanciero'].reset();
			byId('hddIdFactor').value = '';
			byId('hddIdBancoFactor').value = '';
						
			byId('txtTasa').className = 'inputHabilitado';
			byId('txtMesesFactor').className = 'inputHabilitado';
			byId('txtFactor').className = 'inputHabilitado';
		");
		
		$query = sprintf("SELECT * FROM an_banco_factor
		WHERE id_factor = %s",
			valTpDato($idFactor, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$idBanco = $row['id_banco'];
		
		$queryBanco = sprintf("SELECT * FROM bancos
		WHERE idBanco = %s",
			valTpDato($idBanco, "int"));
		$rsBanco = mysql_query($queryBanco);
		if (!$rsBanco) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowBanco = mysql_fetch_assoc($rsBanco);
		
		$objResponse->assign("hddIdFactor","value",$idFactor);
		$objResponse->assign("hddIdBancoFactor","value",$idBanco);
		$objResponse->assign("txtNombreBancoFactor","value",utf8_encode($rowBanco['nombreBanco']));
		$objResponse->assign("txtTasa","value",utf8_encode($row['tasa']));
		$objResponse->assign("txtMesesFactor","value",utf8_encode($row['mes']));
		$objResponse->assign("txtFactor","value",utf8_encode($row['factor']));
		
		$objResponse->assign("tdFlotanteTitulo1","innerHTML","Editar Factor Financiero");
		$objResponse->script("
			if (byId('divFlotante1').style.display == 'none') {
				byId('divFlotante1').style.display = '';
				centrarDiv(byId('divFlotante1'));
				
				byId('txtTasa').focus();
			}
		");
	}
	
	return $objResponse;
}


function eliminarBanco($idBanco, $valFormListaBanco) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_banco_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM bancos WHERE idBanco = %s;",
			valTpDato($idBanco, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->loadCommands(listadoBanco(
			$valFormListaBanco['pageNum'],
			$valFormListaBanco['campOrd'],
			$valFormListaBanco['tpOrd'],
			$valFormListaBanco['valBusq']));
	}
	
	return $objResponse;
}


function eliminarFactor($idFactor, $valFormListaFactor) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_factor","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM an_banco_factor WHERE id_factor = %s;",
			valTpDato($idFactor, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->loadCommands(listadoFactorFinanciero(
			$valFormListaFactor['pageNum'],
			$valFormListaFactor['campOrd'],
			$valFormListaFactor['tpOrd'],
			$valFormListaFactor['valBusq']));
	}
	
	return $objResponse;
}


function formBanco() {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_banco_list","insertar")) {
		$objResponse->script("
			document.forms['frmBanco'].reset();
			byId('hddIdBanco').value = '';
			
			byId('txtPorcentajeComisionFlat').className = 'inputHabilitado';
			byId('txtDiasBuenCobroLocal').className = 'inputHabilitado';
			byId('txtDiasBuenCobroForaneo').className = 'inputHabilitado';			
		");
		
		$objResponse->script("
			byId('tblBanco').style.display = '';
			byId('tblListaFactorFinanciero').style.display = 'none';
		");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Banco");
		$objResponse->script("
			if (byId('divFlotante').style.display == 'none') {
				byId('divFlotante').style.display = '';
				centrarDiv(byId('divFlotante'));
			}
		");
	}
	
	return $objResponse;
}


function formFactor($valFormFactor) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_factor","insertar")) {
		$idBanco = $valFormFactor['hddIdBancoListaFactor'];
		
		$objResponse->script("
			document.forms['frmFactorFinanciero'].reset();
			byId('hddIdFactor').value = '';
			byId('hddIdBancoFactor').value = '';
						
			byId('txtTasa').className = 'inputHabilitado';
			byId('txtMesesFactor').className = 'inputHabilitado';
			byId('txtFactor').className = 'inputHabilitado';
		");
		
		$query = sprintf("SELECT * FROM bancos
		WHERE idBanco = %s",
			valTpDato($idBanco, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdBancoFactor","value",$row['idBanco']);
		$objResponse->assign("txtNombreBancoFactor","value",utf8_encode($row['nombreBanco']));
		
		$objResponse->assign("tdFlotanteTitulo1","innerHTML","Agregar Factor Financiero");
		$objResponse->script("
			if (byId('divFlotante1').style.display == 'none') {
				byId('divFlotante1').style.display = '';
				centrarDiv(byId('divFlotante1'));
				
				byId('txtTasa').focus();
			}
		");
	}
	
	return $objResponse;
}


function formListaFactor($idBanco) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_factor")) {
		$query = sprintf("SELECT * FROM bancos
		WHERE idBanco = %s",
			valTpDato($idBanco, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdBancoListaFactor","value",$row['idBanco']);
		$objResponse->assign("txtNombreBancoListaFactor","value",$row['nombreBanco']);
		
		$objResponse->loadCommands(listadoFactorFinanciero(0, 'mes', 'ASC', $idBanco));
		
		$objResponse->script("
			byId('tblBanco').style.display = 'none';
			byId('tblListaFactorFinanciero').style.display = '';
		");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Factores Financieros");
		$objResponse->script("
			if (byId('divFlotante').style.display == 'none') {
				byId('divFlotante').style.display = '';
				centrarDiv(byId('divFlotante'));
			}
		");
	}
	
	return $objResponse;
}


function guardarBanco($valForm, $valFormListaBanco) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdBanco'] > 0) {
		if (xvalidaAcceso($objResponse,"an_banco_list","editar")) {
			$updateSQL = sprintf("UPDATE bancos SET
				nombreBanco = %s,
				porcentaje_flat = %s,
				diasSalvoBuenCobroLocales = %s,
				diasSalvoBuenCobroForaneos = %s
			WHERE idBanco = %s;",
				valTpDato($valForm['txtNombre'], "text"),
				valTpDato($valForm['txtPorcentajeComisionFlat'], "double"),
				valTpDato($valForm['txtDiasBuenCobroLocal'], "int"),
				valTpDato($valForm['txtDiasBuenCobroForaneo'], "int"),
				valTpDato($valForm['hddIdBanco'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"an_banco_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO bancos (nombreBanco, porcentaje_flat, diasSalvoBuenCobroLocales, diasSalvoBuenCobroForaneos)
			VALUE (%s, %s, %s, %s);",
				valTpDato($valForm['txtNombre'], "text"),
				valTpDato($valForm['txtPorcentajeComisionFlat'], "double"),
				valTpDato($valForm['txtDiasBuenCobroLocal'], "int"),
				valTpDato($valForm['txtDiasBuenCobroForaneo'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Banco guardado con éxito.");
	
	$objResponse->script("byId('divFlotante').style.display = 'none';");
	
	$objResponse->loadCommands(listadoBanco(
		$valFormListaBanco['pageNum'],
		$valFormListaBanco['campOrd'],
		$valFormListaBanco['tpOrd'],
		$valFormListaBanco['valBusq']));
	
	return $objResponse;
}


function guardarFactor($valForm, $valFormListaFactor) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdFactor'] > 0) {
		if (xvalidaAcceso($objResponse,"an_factor","editar")) {
			$updateSQL = sprintf("UPDATE an_banco_factor SET
				tasa = %s,
				mes = %s,
				factor = %s
			WHERE id_factor = %s;",
				valTpDato($valForm['txtTasa'], "double"),
				valTpDato($valForm['txtMesesFactor'], "int"),
				valTpDato($valForm['txtFactor'], "double"),
				valTpDato($valForm['hddIdFactor'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"an_factor","insertar")) {
			$insertSQL = sprintf("INSERT INTO an_banco_factor (id_banco, tasa, mes, factor)
			VALUE (%s, %s, %s, %s);",
				valTpDato($valForm['hddIdBancoFactor'], "int"),
				valTpDato($valForm['txtTasa'], "double"),
				valTpDato($valForm['txtMesesFactor'], "int"),
				valTpDato($valForm['txtFactor'], "double"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Factor Financiero guardado con éxito.");
	
	$objResponse->script("byId('divFlotante1').style.display = 'none';");
	
	$objResponse->loadCommands(listadoFactorFinanciero(
		$valFormListaFactor['pageNum'],
		$valFormListaFactor['campOrd'],
		$valFormListaFactor['tpOrd'],
		$valFormListaFactor['valBusq']));
	
	return $objResponse;
}


function listadoBanco($pageNum = 0, $campOrd = "nombreBanco", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idBanco <> %s",
		valTpDato(1, "int"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("nombreBanco LIKE %s
			OR porcentaje_flat LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM bancos %s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoBanco", "50%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listadoBanco", "20%", $pageNum, "porcentaje_flat", $campOrd, $tpOrd, $valBusq, $maxRows, "% Comisión FLAT");
		$htmlTh .= ordenarCampo("xajax_listadoBanco", "15%", $pageNum, "diasSalvoBuenCobroLocales", $campOrd, $tpOrd, $valBusq, $maxRows, "DSBC Locales");
		$htmlTh .= ordenarCampo("xajax_listadoBanco", "15%", $pageNum, "diasSalvoBuenCobroForaneos", $campOrd, $tpOrd, $valBusq, $maxRows, "DSBC Foráneos");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities(number_format($row['porcentaje_flat'], 2, ".", ","))."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['diasSalvoBuenCobroLocales'])."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['diasSalvoBuenCobroForaneos'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formListaFactor('%s');\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Factores Financieros\"/></td>",
				$row['idBanco']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_cargarBanco('%s');\" src=\"../img/iconos/pencil.png\" title=\"Editar Banco\"/></td>",
				$row['idBanco']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoBanco(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoBanco(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaBanco","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}


function listadoFactorFinanciero($pageNum = 0, $campOrd = "mes", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_banco = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT * FROM an_banco_factor %s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoFactorFinanciero", "40%", $pageNum, "tasa", $campOrd, $tpOrd, $valBusq, $maxRows, "Tasa");
		$htmlTh .= ordenarCampo("xajax_listadoFactorFinanciero", "30%", $pageNum, "mes", $campOrd, $tpOrd, $valBusq, $maxRows, "Meses");
		$htmlTh .= ordenarCampo("xajax_listadoFactorFinanciero", "30%", $pageNum, "factor", $campOrd, $tpOrd, $valBusq, $maxRows, "Factor");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".htmlentities(number_format($row['tasa'], 2, ".", ","))."%"."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities($row['mes'])."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['factor'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_cargarFactor('%s');\" src=\"../img/iconos/pencil.png\" title=\"Editar Factor\"/></td>",
				$row['id_factor']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminarFactor('%s')\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar Factor\"/></td>",
				$row['id_factor']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFactorFinanciero(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFactorFinanciero(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoFactorFinanciero(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFactorFinanciero(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFactorFinanciero(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}	
	
	$objResponse->assign("tdListaFactorFinanciero","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"cargarBanco");
$xajax->register(XAJAX_FUNCTION,"cargarFactor");
$xajax->register(XAJAX_FUNCTION,"eliminarBanco");
$xajax->register(XAJAX_FUNCTION,"eliminarFactor");
$xajax->register(XAJAX_FUNCTION,"formBanco");
$xajax->register(XAJAX_FUNCTION,"formFactor");
$xajax->register(XAJAX_FUNCTION,"formListaFactor");
$xajax->register(XAJAX_FUNCTION,"guardarBanco");
$xajax->register(XAJAX_FUNCTION,"guardarFactor");
$xajax->register(XAJAX_FUNCTION,"listadoBanco");
$xajax->register(XAJAX_FUNCTION,"listadoFactorFinanciero");
?>