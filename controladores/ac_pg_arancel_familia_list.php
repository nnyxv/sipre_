<?php


function asignarArancelGrupo($idArancelGrupo) {
	$objResponse = new xajaxResponse();
	
	$queryArancelGrupo = sprintf("SELECT * FROM pg_arancel_grupo WHERE id_arancel_grupo = %s;", valTpDato($idArancelGrupo, "int"));
	$rsArancelGrupo = mysql_query($queryArancelGrupo);
	if (!$rsArancelGrupo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArancelGrupo = mysql_fetch_assoc($rsArancelGrupo);
	
	$objResponse->assign("txtIdArancelGrupo","value",$rowArancelGrupo['id_arancel_grupo']);
	$objResponse->assign("txtDescripcionGrupoArancel","value",utf8_encode($rowArancelGrupo['codigo_grupo']));
	
	$objResponse->script("
	byId('btnCancelar2').click();");
	
	return $objResponse;
}

function buscarArancelGrupo($frmBuscarArancelGrupo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarArancelGrupo['txtCriterioBuscarArancelGrupo']);
	
	$objResponse->loadCommands(listaArancelGrupo(0, "id_arancel_grupo", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarArancelFamilia($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaArancelFamilia(0, "codigo_arancel", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarArancelFamilia($idArancelFamilia, $frmListaArancelFamilia) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"pg_arancel_familia_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM pg_arancel_familia WHERE id_arancel_familia = %s;",
		valTpDato($idArancelFamilia, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaArancelFamilia(
		$frmListaArancelFamilia['pageNum'],
		$frmListaArancelFamilia['campOrd'],
		$frmListaArancelFamilia['tpOrd'],
		$frmListaArancelFamilia['valBusq']));
	
	return $objResponse;
}

function formArancelFamilia($idArancelFamilia) {
	$objResponse = new xajaxResponse();
	
	if ($idArancelFamilia > 0) {
		if (!xvalidaAcceso($objResponse,"pg_arancel_familia_list","editar")) { return $objResponse; }
		
		$query = sprintf("SELECT * FROM pg_arancel_familia
		WHERE id_arancel_familia = %s;",
			valTpDato($idArancelFamilia, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdArancelFamilia","value",$row['id_arancel_familia']);
		$objResponse->loadCommands(asignarArancelGrupo($row['id_arancel_grupo']));
		$objResponse->assign("txtCodigoFamilia","value",utf8_encode($row['codigo_familia']));
		$objResponse->assign("txtCodigoArancel","value",utf8_encode($row['codigo_arancel']));
		$objResponse->assign("txtDescripcionArancel","value",utf8_encode($row['descripcion_arancel']));
		$objResponse->call("selectedOption","lstEstatus",$row['estatus']);
	} else {
		if (!xvalidaAcceso($objResponse,"pg_arancel_familia_list","insertar")) { return $objResponse; }
	}
	
	return $objResponse;
}

function guardarArancelFamilia($frmArancelFamilia, $frmListaArancelFamilia) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmArancelFamilia['hddIdArancelFamilia'] > 0) {
		if (!xvalidaAcceso($objResponse,"pg_arancel_familia_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_arancel_familia SET
			id_arancel_grupo = %s,
			codigo_familia = %s,
			codigo_arancel = %s,
			descripcion_arancel = %s,
			estatus = %s
		WHERE id_arancel_familia = %s;",
			valTpDato($frmArancelFamilia['txtIdArancelGrupo'], "int"),
			valTpDato($frmArancelFamilia['txtCodigoFamilia'], "text"),
			valTpDato($frmArancelFamilia['txtCodigoArancel'], "text"),
			valTpDato($frmArancelFamilia['txtDescripcionArancel'], "text"),
			valTpDato($frmArancelFamilia['lstEstatus'], "boolean"),
			valTpDato($frmArancelFamilia['hddIdArancelFamilia'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_arancel_familia_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_arancel_familia (id_arancel_grupo, codigo_familia, codigo_arancel, descripcion_arancel, estatus)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($frmArancelFamilia['txtIdArancelGrupo'], "int"),
			valTpDato($frmArancelFamilia['txtCodigoFamilia'], "text"),
			valTpDato($frmArancelFamilia['txtCodigoArancel'], "text"),
			valTpDato($frmArancelFamilia['txtDescripcionArancel'], "text"),
			valTpDato($frmArancelFamilia['lstEstatus'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Registro Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listaArancelFamilia(
		$frmListaArancelFamilia['pageNum'],
		$frmListaArancelFamilia['campOrd'],
		$frmListaArancelFamilia['tpOrd'],
		$frmListaArancelFamilia['valBusq']));
	
	return $objResponse;
}

function listaArancelFamilia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("arancel_familia.estatus = %s",
			valTpDato($valCadBusq[0], "boolean"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(arancel_familia.codigo_familia LIKE %s
		OR arancel_familia.codigo_arancel LIKE %s
		OR arancel_familia.descripcion_arancel LIKE %s
		OR arancel_grupo.codigo_grupo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		arancel_familia.id_arancel_familia,
		arancel_familia.id_arancel_grupo,
		arancel_familia.codigo_familia,
		arancel_familia.codigo_arancel,
		arancel_familia.descripcion_arancel,
		arancel_grupo.codigo_grupo,
		arancel_grupo.porcentaje_grupo,
		arancel_familia.estatus
	FROM pg_arancel_familia arancel_familia
		INNER JOIN pg_arancel_grupo arancel_grupo ON (arancel_familia.id_arancel_grupo = arancel_grupo.id_arancel_grupo) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "10%", $pageNum, "codigo_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Grupo");
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "10%", $pageNum, "codigo_familia", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Familia");
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "14%", $pageNum, "codigo_arancel", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Arancelario");
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "56%", $pageNum, "descripcion_arancel", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "10%", $pageNum, "monto_tasa_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "% Arancelario");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus']) {
			case 0 : $imgEstatusAlmacen = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatusAlmacen = "<img src=\"img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusAlmacen = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusAlmacen."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_grupo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_familia'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_arancel'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_arancel'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['porcentaje_grupo'],2,".",",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblArancelFamilia', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_arancel_familia']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/></td>",
				$row['id_arancel_familia']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaArancelFamilia","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaArancelGrupo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(codigo_grupo LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM pg_arancel_grupo %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaArancelGrupo", "8%", $pageNum, "id_arancel_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaArancelGrupo", "62%", $pageNum, "codigo_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Grupo Arancelario");
		$htmlTh .= ordenarCampo("xajax_listaArancelGrupo", "30%", $pageNum, "porcentaje_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "% Arancelario");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarArancelGrupo('".$row['id_arancel_grupo']."');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_arancel_grupo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_grupo'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['porcentaje_grupo'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelGrupo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelGrupo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArancelGrupo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelGrupo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelGrupo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaArancelGrupo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArancelGrupo");
$xajax->register(XAJAX_FUNCTION,"buscarArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"buscarArancelGrupo");
$xajax->register(XAJAX_FUNCTION,"eliminarArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"formArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"guardarArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"listaArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"listaArancelGrupo");
?>