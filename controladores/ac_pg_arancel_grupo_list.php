<?php


function buscarArancelGrupo($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaArancelGrupo(0, "id_arancel_grupo", "ASC", $valBusq));
	
	return $objResponse;
}

function cargarArancelGrupo($nomObjeto, $idArancelGrupo) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"pg_arancel_grupo_list","editar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmArancelGrupo'].reset();
		byId('hddIdArancelGrupo').value = '';
		
		byId('txtCodigoGrupo').className = 'inputHabilitado';
		byId('txtPorcGrupo').className = 'inputHabilitado';");
	
		$query = sprintf("SELECT * FROM pg_arancel_grupo
		WHERE id_arancel_grupo = %s;",
			valTpDato($idArancelGrupo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdArancelGrupo","value",$row['id_arancel_grupo']);
		$objResponse->assign("txtCodigoGrupo","value",$row['codigo_grupo']);
		$objResponse->assign("txtPorcGrupo","value",number_format($row['porcentaje_grupo'],2,".",","));
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Grupo Arancelario");
		$objResponse->script("
		byId('txtCodigoGrupo').focus();
		byId('txtCodigoGrupo').select();");
	}
	
	return $objResponse;
}

function eliminarArancelGrupo($idArancelGrupo, $valFormListaArancelGrupo) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"pg_arancel_grupo_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM pg_arancel_grupo WHERE id_arancel_grupo = %s;",
			valTpDato($idArancelGrupo, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	
		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->loadCommands(listaArancelGrupo(
			$valFormListaArancelGrupo['pageNum'],
			$valFormListaArancelGrupo['campOrd'],
			$valFormListaArancelGrupo['tpOrd'],
			$valFormListaArancelGrupo['valBusq']));
	}
	
	return $objResponse;
}

function formArancelGrupo($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"pg_arancel_grupo_list","insertar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmArancelGrupo'].reset();
		byId('hddIdArancelGrupo').value = '';
		
		byId('txtCodigoGrupo').className = 'inputHabilitado';
		byId('txtPorcGrupo').className = 'inputHabilitado';");
		
		$objResponse->assign("txtPorcGrupo","value",number_format(0,2,".",","));
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Grupo Arancelario");
		$objResponse->script("
		byId('txtCodigoGrupo').focus();
		byId('txtCodigoGrupo').select();");
	}
	
	return $objResponse;
}

function guardarArancelGrupo($valForm, $valFormListaArancelGrupo) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdArancelGrupo'] > 0) {
		if (xvalidaAcceso($objResponse,"pg_arancel_grupo_list","editar")) {
			$updateSQL = sprintf("UPDATE pg_arancel_grupo SET
				codigo_grupo = %s,
				porcentaje_grupo = %s
			WHERE id_arancel_grupo = %s;",
				valTpDato($valForm['txtCodigoGrupo'], "text"),
				valTpDato($valForm['txtPorcGrupo'], "real_inglesa"),
				valTpDato($valForm['hddIdArancelGrupo'], "int"));
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
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"pg_arancel_grupo_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO pg_arancel_grupo (codigo_grupo, porcentaje_grupo)
			VALUE (%s, %s);",
				valTpDato($valForm['txtCodigoGrupo'], "text"),
				valTpDato($valForm['txtPorcGrupo'], "real_inglesa"));
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
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Registro Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listaArancelGrupo(
		$valFormListaArancelGrupo['pageNum'],
		$valFormListaArancelGrupo['campOrd'],
		$valFormListaArancelGrupo['tpOrd'],
		$valFormListaArancelGrupo['valBusq']));
	
	return $objResponse;
}

function listaArancelGrupo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaArancelGrupo", "8%", $pageNum, "id_arancel_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaArancelGrupo", "62%", $pageNum, "codigo_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Grupo Arancelario");
		$htmlTh .= ordenarCampo("xajax_listaArancelGrupo", "30%", $pageNum, "porcentaje_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "% Arancelario");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_arancel_grupo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_grupo'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['porcentaje_grupo'],2,".",",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarArancelGrupo(this.id,'%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_arancel_grupo']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/></td>",
				$row['id_arancel_grupo']);
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("divListaArancelGrupo","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarArancelGrupo");
$xajax->register(XAJAX_FUNCTION,"cargarArancelGrupo");
$xajax->register(XAJAX_FUNCTION,"eliminarArancelGrupo");
$xajax->register(XAJAX_FUNCTION,"formArancelGrupo");
$xajax->register(XAJAX_FUNCTION,"guardarArancelGrupo");
$xajax->register(XAJAX_FUNCTION,"listaArancelGrupo");
?>