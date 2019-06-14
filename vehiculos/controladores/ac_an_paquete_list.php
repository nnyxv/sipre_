<?php


function buscarPaquete($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaPaquete(0, "nom_paquete", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstAccesorio($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_accesorio WHERE id_tipo_accesorio = 1 ORDER BY nom_accesorio");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstAccesorio\" name=\"lstAccesorio\" ".$disabled.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_accesorio']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_accesorio']."\">".htmlentities($row['nom_accesorio'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAccesorio","innerHTML",$html);
	
	return $objResponse;
}

function cargarPaquete($idPaquete) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_paquete_list","editar")) { return $objResponse; }
	
	$objResponse->script("
	document.forms['frmPaquete'].reset();
	byId('hddIdPaquete').value = '';
	
	byId('txtNombre').className = 'inputInicial';
	byId('txtDescripcion').className = 'inputInicial';");
	
	$query = sprintf("SELECT * FROM an_paquete
	WHERE id_paquete = %s",
		valTpDato($idPaquete, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdPaquete","value",$idPaquete);
	$objResponse->assign("txtNombre","value",utf8_encode($row['nom_paquete']));
	$objResponse->assign("txtDescripcion","value",utf8_encode($row['des_paquete']));
	
	$objResponse->script("
	byId('tblPaquete').style.display = '';
	byId('tblListaAccesorio').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Editar Paquete");
	$objResponse->script("
	if (byId('divFlotante1').style.display == 'none') {
		byId('divFlotante1').style.display = '';
		centrarDiv(byId('divFlotante1'));
		
		byId('txtNombre').focus();
	}");
	
	return $objResponse;
}

function eliminarPaquete($idPaquete, $frmListaPaquete) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_paquete_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_paquete WHERE id_paquete = %s;",
		valTpDato($idPaquete, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPaquete(
		$frmListaPaquete['pageNum'],
		$frmListaPaquete['campOrd'],
		$frmListaPaquete['tpOrd'],
		$frmListaPaquete['valBusq']));
	
	return $objResponse;
}

function eliminarAccesorio($idAccesorio, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_acc_paq","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_acc_paq WHERE Id_acc_paq = %s;",
		valTpDato($idAccesorio, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaAccesorio(
		$frmListaAccesorio['pageNum'],
		$frmListaAccesorio['campOrd'],
		$frmListaAccesorio['tpOrd'],
		$frmListaAccesorio['valBusq']));
	
	return $objResponse;
}

function formPaquete() {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_paquete_list","insertar")) { return $objResponse; }
	
	$objResponse->script("
	document.forms['frmPaquete'].reset();
	byId('hddIdPaquete').value = '';
	
	byId('txtNombre').className = 'inputInicial';
	byId('txtDescripcion').className = 'inputInicial';");
	
	$objResponse->script("
	byId('tblPaquete').style.display = '';
	byId('tblListaAccesorio').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Agregar Paquete");
	$objResponse->script("
	if (byId('divFlotante1').style.display == 'none') {
		byId('divFlotante1').style.display = '';
		centrarDiv(byId('divFlotante1'));
		
		byId('txtNombre').focus();
	}");
	
	return $objResponse;
}

function formAccesorio($valFormAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_acc_paq","insertar")) { return $objResponse; }
	
	$idPaquete = $valFormAccesorio['hddIdPaqueteListaAccesorio'];
	
	$objResponse->script("
	document.forms['frmAccesorioPaquete'].reset();
	byId('hddIdAccesorio').value = '';
	byId('hddIdPaqueteAccesorio').value = '';
	
	byId('txtNombrePaqueteAccesorio').className = 'inputInicial';
	byId('txtTasa').className = 'inputInicial';
	byId('txtMesesAccesorio').className = 'inputInicial';
	byId('txtAccesorio').className = 'inputInicial';");
	
	$query = sprintf("SELECT * FROM an_paquete
	WHERE id_paquete = %s",
		valTpDato($idPaquete, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdPaqueteAccesorio","value",$row['id_paquete']);
	$objResponse->assign("txtNombrePaqueteAccesorio","value",utf8_encode($row['nom_paquete']));
	
	$objResponse->loadCommands(cargaLstAccesorio());
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Agregar Accesorio ");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display = '';
		centrarDiv(byId('divFlotante2'));
		
		byId('txtTasa').focus();
	}");
	
	return $objResponse;
}

function formListaAccesorio($idPaquete) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_acc_paq")) { return $objResponse; }
	
	$query = sprintf("SELECT * FROM an_paquete
	WHERE id_paquete = %s",
		valTpDato($idPaquete, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdPaqueteListaAccesorio","value",$idPaquete);
	$objResponse->assign("txtNombrePaqueteListaAccesorio","value",$row['nom_paquete']);
	
	$objResponse->loadCommands(listaAccesorio(0, 'nom_accesorio', 'ASC', $idPaquete));
	
	$objResponse->script("
	byId('tblPaquete').style.display = 'none';
	byId('tblListaAccesorio').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Accesorios del Paquete");
	$objResponse->script("
	if (byId('divFlotante1').style.display == 'none') {
		byId('divFlotante1').style.display = '';
		centrarDiv(byId('divFlotante1'));
		
		byId('txtNombre').focus();
	}");
	
	return $objResponse;
}

function guardarPaquete($valForm, $frmListaPaquete) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdPaquete'] > 0) {
		if (!xvalidaAcceso($objResponse,"an_paquete_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_paquete SET
			nom_paquete = %s,
			des_paquete = %s
		WHERE id_paquete = %s;",
			valTpDato($valForm['txtNombre'], "text"),
			valTpDato($valForm['txtDescripcion'], "text"),
			valTpDato($valForm['hddIdPaquete'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"an_paquete_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO an_paquete (nom_paquete, des_paquete)
		VALUE (%s, %s);",
			valTpDato($valForm['txtNombre'], "text"),
			valTpDato($valForm['txtDescripcion'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Paquete guardado con éxito.");
	
	$objResponse->script("byId('divFlotante1').style.display = 'none';");
	
	$objResponse->loadCommands(listaPaquete(
		$frmListaPaquete['pageNum'],
		$frmListaPaquete['campOrd'],
		$frmListaPaquete['tpOrd'],
		$frmListaPaquete['valBusq']));
	
	return $objResponse;
}

function guardarAccesorio($valForm, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdAccesorio'] > 0) {
		if (!xvalidaAcceso($objResponse,"an_acc_paq","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_acc_paq SET
			id_paquete = %s,
			id_accesorio = %s
		WHERE Id_acc_paq = %s;",
			valTpDato($valForm['hddIdPaqueteAccesorio'], "double"),
			valTpDato($valForm['lstAccesorio'], "int"),
			valTpDato($valForm['hddIdAccesorio'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"an_acc_paq","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO an_acc_paq (id_paquete, id_accesorio)
		VALUE (%s, %s);",
			valTpDato($valForm['hddIdPaqueteAccesorio'], "int"),
			valTpDato($valForm['lstAccesorio'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Accesorio guardado con éxito.");
	
	$objResponse->script("byId('divFlotante2').style.display = 'none';");
	
	$objResponse->loadCommands(listaAccesorio(
		$frmListaAccesorio['pageNum'],
		$frmListaAccesorio['campOrd'],
		$frmListaAccesorio['tpOrd'],
		$frmListaAccesorio['valBusq']));
	
	return $objResponse;
}

function listaPaquete($pageNum = 0, $campOrd = "nom_paquete", $tpOrd = "ASC", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("nom_paquete LIKE %s
			OR des_paquete LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_paquete %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaPaquete", "25%", $pageNum, "nom_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaPaquete", "75%", $pageNum, "des_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".htmlentities($row['nom_paquete'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['des_paquete'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formListaAccesorio('%s');\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Accesorios del Paquete\"/></td>",
				$row['id_paquete']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_cargarPaquete('%s');\" src=\"../img/iconos/pencil.png\" title=\"Editar Paquete\"/></td>",
				$row['id_paquete']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/cross.png\" title=\"Eliminar Paquete\"/></td>",
				$row['id_paquete']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPaquete(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPaquete","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaAccesorio($pageNum = 0, $campOrd = "nom_accesorio", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_paquete = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT 
		accesorio_paq.Id_acc_paq,
		accesorio.id_accesorio,
		accesorio.nom_accesorio,
		accesorio.des_accesorio,
		accesorio.iva_accesorio,
		accesorio.precio_accesorio,
		accesorio.costo_accesorio
	FROM an_accesorio accesorio
		INNER JOIN an_acc_paq accesorio_paq ON (accesorio.id_accesorio = accesorio_paq.id_accesorio) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "24%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "48%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "8%", $pageNum, "iva_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "10%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "10%", $pageNum, "costo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".htmlentities($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['des_accesorio'])."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities(($row['iva_accesorio'] == 1) ? "Si" : "No")."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities(number_format($row['precio_accesorio'], 2, ".", ","))."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities(number_format($row['costo_accesorio'], 2, ".", ","))."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminarAccesorio('%s')\" src=\"../img/iconos/cross.png\" title=\"Eliminar Accesorio\"/></td>",
				$row['Id_acc_paq']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAccesorio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPaquete");
$xajax->register(XAJAX_FUNCTION,"cargaLstAccesorio");
$xajax->register(XAJAX_FUNCTION,"cargarPaquete");
$xajax->register(XAJAX_FUNCTION,"eliminarPaquete");
$xajax->register(XAJAX_FUNCTION,"eliminarAccesorio");
$xajax->register(XAJAX_FUNCTION,"formPaquete");
$xajax->register(XAJAX_FUNCTION,"formAccesorio");
$xajax->register(XAJAX_FUNCTION,"formListaAccesorio");
$xajax->register(XAJAX_FUNCTION,"guardarPaquete");
$xajax->register(XAJAX_FUNCTION,"guardarAccesorio");
$xajax->register(XAJAX_FUNCTION,"listaPaquete");
$xajax->register(XAJAX_FUNCTION,"listaAccesorio");
?>