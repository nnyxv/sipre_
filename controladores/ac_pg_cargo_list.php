<?php


function buscarCargo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCargo(0, "id_cargo", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarCargo($idCargo, $frmListaCargo) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_cargo_list","eliminar")){ return $objResponse; }
	
	$deleteSQL = sprintf("DELETE FROM pg_cargo WHERE id_cargo = %s;",
		valTpDato($idCargo, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaCargo(
		$frmListaCargo['pageNum'],
		$frmListaCargo['campOrd'],
		$frmListaCargo['tpOrd'],
		$frmListaCargo['valBusq']));
	
	return $objResponse;
}

function formCargo($idCargo, $frmCargo) {
	$objResponse = new xajaxResponse();

	if ($idCargo > 0) {
		if (!xvalidaAcceso($objResponse,"pg_cargo_list","editar")) { $objResponse->script("byId('btnCancelarCargo').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM pg_cargo WHERE id_cargo = %s;", valTpDato($idCargo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$objResponse->assign("hddIdCargo","value",$row['id_cargo']);
		$objResponse->assign("txtCargo","value",$row['nombre_cargo']);
		$objResponse->assign("txtCodigo","value",$row['codigo_cargo']);
		$objResponse->assign("lstUnipersonal","value",$row['unipersonal']);
	} else {
		if (!xvalidaAcceso($objResponse,"pg_cargo_list","insertar")) { $objResponse->script("byId('btnCancelarCargo').click();"); return $objResponse; }
	}
	
	return $objResponse;
}

function guardarCargo($frmCargo, $frmListaCargo) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");

	if ($frmCargo['hddIdCargo'] > 0) {
		if (!xvalidaAcceso($objResponse,"pg_cargo_list","editar")) { errorGuardarCargo($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_cargo SET
			nombre_cargo = %s,
			codigo_cargo = %s,
			unipersonal = %s
		WHERE id_cargo = %s;",
			valTpDato($frmCargo['txtCargo'],"text"),
			valTpDato($frmCargo['txtCodigo'],"text"),
			valTpDato($frmCargo['lstUnipersonal'],"boolean"),
			valTpDato($frmCargo['hddIdCargo'],"int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			errorGuardarCargo($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_cargo_list","insertar")) { errorGuardarCargo($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_cargo (nombre_cargo, codigo_cargo, unipersonal)
		VALUES (%s, %s, %s);",
			valTpDato($frmCargo['txtCargo'],"text"),
			valTpDato($frmCargo['txtCodigo'],"text"),
			valTpDato($frmCargo['lstUnipersonal'],"boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			errorGuardarCargo($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarCargo($objResponse);
	$objResponse->alert("Cargo Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarCargo').click();");
	
	$objResponse->loadCommands(listaCargo(
		$frmListaCargo['pageNum'],
		$frmListaCargo['campOrd'],
		$frmListaCargo['tpOrd'],
		$frmListaCargo['valBusq']));
	
	return $objResponse;
}

function listaCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_cargo LIKE %s
		OR codigo_cargo LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM pg_cargo %s",$sqlBusq);

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

	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaCargo", "8%", $pageNum, "id_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaCargo", "10%", $pageNum, "codigo_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listaCargo", "58%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Cargo");
		$htmlTh .= ordenarCampo("xajax_listaCargo", "24%", $pageNum, "unipersonal", $campOrd, $tpOrd, $valBusq, $maxRows, "Unipersonal");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$unipersonal = ($row['unipersonal'] == 1) ? "SI" : "NO";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['id_cargo'])."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['codigo_cargo'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_cargo'])."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities($unipersonal)."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCargo', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_cargo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/>",
					$row['id_cargo']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"6\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}

						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";

							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCargo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaCargo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarCargo");
$xajax->register(XAJAX_FUNCTION,"eliminarCargo");
$xajax->register(XAJAX_FUNCTION,"formCargo");
$xajax->register(XAJAX_FUNCTION,"guardarCargo");
$xajax->register(XAJAX_FUNCTION,"listaCargo");

function errorGuardarCargo($objResponse) {
	$objResponse->script("
	byId('btnGuardarCargo').disabled = false;
	byId('btnCancelarCargo').disabled = false;");
}
?>