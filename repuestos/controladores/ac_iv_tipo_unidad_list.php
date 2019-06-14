<?php


function buscarTipoUnidad($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaTipoUnidad(0, "id_tipo_unidad", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarTipoUnidad($idTipoUnidad, $frmListaTipoUnidad) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_tipo_unidad_list","eliminar")) { return $objResponse; }

	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM iv_tipos_unidad WHERE id_tipo_unidad = %s;",
		valTpDato($idTipoUnidad, "int"));
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
	
	$objResponse->loadCommands(listaTipoUnidad(
		$frmListaTipoUnidad['pageNum'],
		$frmListaTipoUnidad['campOrd'],
		$frmListaTipoUnidad['tpOrd'],
		$frmListaTipoUnidad['valBusq']));

	return $objResponse;
}

function formTipoUnidad($idTipoUnidad, $frmTipoUnidad) {
	$objResponse = new xajaxResponse();
	
	if ($idTipoUnidad > 0) {
		if (!xvalidaAcceso($objResponse,"iv_tipo_unidad_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTipoUnidad').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM iv_tipos_unidad WHERE id_tipo_unidad = %s;",
			valTpDato($idTipoUnidad, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdTipoUnidad","value",$row['id_tipo_unidad']);
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['unidad']));
		$objResponse->call("selectedOption","lstDecimales",$row['decimales']);
	} else {
		if (!xvalidaAcceso($objResponse,"iv_tipo_unidad_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTipoUnidad').click();"); return $objResponse; }
	}
	
	return $objResponse;
}

function guardarTipoUnidad($frmTipoUnidad, $frmListaTipoUnidad) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmTipoUnidad['hddIdTipoUnidad'] > 0) {
		if (!xvalidaAcceso($objResponse,"iv_tipo_unidad_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE iv_tipos_unidad SET
			unidad = %s,
			decimales = %s
		WHERE id_tipo_unidad = %s;",
			valTpDato($frmTipoUnidad['txtDescripcion'], "text"),
			valTpDato($frmTipoUnidad['lstDecimales'], "booean"),
			valTpDato($frmTipoUnidad['hddIdTipoUnidad'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_tipo_unidad_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO iv_tipos_unidad (unidad, decimales)
		VALUE (%s, %s);",
			valTpDato($frmTipoUnidad['txtDescripcion'], "text"),
			valTpDato($frmTipoUnidad['lstDecimales'], "booean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Tipo de Unidad Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarTipoUnidad').click();");
	
	$objResponse->loadCommands(listaTipoUnidad(
		$frmListaTipoUnidad['pageNum'],
		$frmListaTipoUnidad['campOrd'],
		$frmListaTipoUnidad['tpOrd'],
		$frmListaTipoUnidad['valBusq']));
	
	return $objResponse;
}

function listaTipoUnidad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("unidad LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT *,
		(CASE decimales
			WHEN 0 THEN 'NO'
			WHEN 1 THEN 'SI'
		END) AS descripcion_decimales
	FROM iv_tipos_unidad %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaTipoUnidad", "8%", $pageNum, "id_tipo_unidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaTipoUnidad", "82%", $pageNum, "unidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Unidad");
		$htmlTh .= ordenarCampo("xajax_listaTipoUnidad", "10%", $pageNum, "descripcion_decimales", $campOrd, $tpOrd, $valBusq, $maxRows, "Permitir Decimales");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_tipo_unidad'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['unidad'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_decimales'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblTipoUnidad', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_tipo_unidad']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_tipo_unidad']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoUnidad(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoUnidad(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTipoUnidad(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoUnidad(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoUnidad(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaTipoUnidad","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarTipoUnidad");
$xajax->register(XAJAX_FUNCTION,"eliminarTipoUnidad");
$xajax->register(XAJAX_FUNCTION,"formTipoUnidad");
$xajax->register(XAJAX_FUNCTION,"guardarTipoUnidad");
$xajax->register(XAJAX_FUNCTION,"listaTipoUnidad");
?>