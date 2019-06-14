<?php


function buscarImpuesto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['lstTipoImpuestoBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaImpuesto(0, "idIva", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstTipoImpuesto($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT id_tipo_impuesto, tipo_impuesto FROM pg_tipo_impuesto ORDER BY tipo_impuesto");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoImpuesto\" name=\"lstTipoImpuesto\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_impuesto']) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$row['id_tipo_impuesto']."\">".utf8_encode($row['id_tipo_impuesto'].".- ".$row['tipo_impuesto'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdSelTipoImpuesto","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoImpuestoBuscar($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT id_tipo_impuesto, tipo_impuesto FROM pg_tipo_impuesto ORDER BY tipo_impuesto");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoImpuestoBuscar\" name=\"lstTipoImpuestoBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_impuesto']) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$row['id_tipo_impuesto']."\">".utf8_encode($row['id_tipo_impuesto'].".- ".$row['tipo_impuesto'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoImpuestoBuscar","innerHTML",$html);
	
	return $objResponse;
}

function eliminarImpuesto($idIva, $frmListaImpuesto) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_impuesto_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM pg_iva WHERE idIva = %s;",
		valTpDato($idIva, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaImpuesto(
		$frmListaImpuesto['pageNum'],
		$frmListaImpuesto['campOrd'],
		$frmListaImpuesto['tpOrd'],
		$frmListaImpuesto['valBusq']));

	return $objResponse;
}

function formImpuesto($idImpuesto, $frmImpuesto) {
	$objResponse = new xajaxResponse();
	
	if ($idImpuesto > 0) {
		if (!xvalidaAcceso($objResponse, "pg_impuesto_list", "editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarImpuesto').click();"); return $objResponse; }
		
		$query = sprintf("SELECT
			idIva,
			iva,
			observacion,
			tipo,
			activo,
			estado
		FROM pg_iva
		WHERE idIva = %s;",
			valTpDato($idImpuesto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
	
		$objResponse->assign("hddIdImpuesto","value",$row['idIva']);
		$objResponse->assign("txtImpuesto","value",$row['iva']);
		$objResponse->assign("txtObservacion","value",utf8_encode($row['observacion']));
		$objResponse->loadCommands(cargaLstTipoImpuesto());
		$objResponse->assign("lstTipoImpuesto","value",utf8_encode($row['tipo']));
		if ($row['activo'] == 1) {
			$objResponse->script("
			byId('lstEstado').className = 'inputInicial';
			byId('lstEstado').onchange = function () { selectedOption(this.id,'".$row['estado']."'); }");
		} else {
			$objResponse->script("byId('lstEstado').onchange = '';");
		}
		$objResponse->assign("lstEstado","value",$row['estado']);
	} else {
		if (!xvalidaAcceso($objResponse, "pg_impuesto_list", "insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarImpuesto').click();"); return $objResponse; }
	
		$objResponse->loadCommands(cargaLstTipoImpuesto());
	}

	return $objResponse;
}

function guardarImpuesto($frmImpuesto, $frmListaImpuesto) {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");
	
	$idImpuesto = $frmImpuesto['hddIdImpuesto'];

	if ($idImpuesto > 0){
		if (!xvalidaAcceso($objResponse,"pg_impuesto_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_iva SET
			iva = %s,
			observacion = %s,
			tipo = %s,
			estado = %s
		WHERE idIva = %s;",
			valTpDato($frmImpuesto['txtImpuesto'], "real_inglesa"),
			valTpDato($frmImpuesto['txtObservacion'], "text"),
			valTpDato($frmImpuesto['lstTipoImpuesto'], "int"),
			valTpDato($frmImpuesto['lstEstado'], "boolean"),
			valTpDato($idImpuesto, "int"));
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
		if (!xvalidaAcceso($objResponse,"pg_impuesto_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_iva (iva, observacion, tipo, estado)
		VALUES (%s, %s, %s, %s);",
			valTpDato($frmImpuesto['txtImpuesto'], "real_inglesa"),
			valTpDato($frmImpuesto['txtObservacion'], "text"),
			valTpDato($frmImpuesto['lstTipoImpuesto'], "int"),
			valTpDato($frmImpuesto['lstEstado'], "boolean"));
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
	
	$objResponse->alert("Impuesto Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarImpuesto').click();");
	
	$objResponse->loadCommands(listaImpuesto(
		$frmListaImpuesto['pageNum'],
		$frmListaImpuesto['campOrd'],
		$frmListaImpuesto['tpOrd'],
		$frmListaImpuesto['valBusq']));

	return $objResponse;
}

function listaImpuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iva.estado = %s",
			valTpDato($valCadBusq[0], "boolean"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("iva.tipo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(observacion LIKE %s
		OR tipo_impuesto LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}

	$query = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto) %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "8%", $pageNum, "idIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "24%", $pageNum, "tipo_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "44%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "12%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "12%", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, "Predeterminado");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch ($row['estado']) {
			case 0 : $imgEstatusAlmacen = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatusAlmacen = "<img src=\"img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusAlmacen = "";
		}
		$activo = ($row['activo'] == 1) ? "SI" : "-";

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusAlmacen."</td>";
			$htmlTb .= "<td align=\"right\">".$row['idIva']."</td>";
			$htmlTb .= "<td>".($row['tipo_impuesto'])."</td>";
			$htmlTb .= "<td>".($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"right\">".($row['iva'])."</td>";
			$htmlTb .= "<td align=\"center\">".($activo)."</td>";
			$htmlTb .= "<td>";
			if ($row['activo'] == 0 && $row['estado'] == 1) {
				$htmlTb .= "<img src=\"img/iconos/accept.png\" class=\"puntero\" title=\"Predeterminar\" onclick=\"xajax_verificarPredeterminado(".$row['idIva'].", 'false')\"/>";
			} else if ($row['activo'] == 1 && $row['estado'] == 1) {
				$htmlTb .= "<img src=\"img/iconos/cancel.png\" class=\"puntero\" title=\"Quitar Predeterminado\" onclick=\"xajax_verificarPredeterminado(".$row['idIva'].", 'true')\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblImpuesto', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['idIva']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['activo'] != 1) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/>",
					$row['idIva']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}

						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";

							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	$htmlTableFin .= "</table>";

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

	$objResponse->assign("divListaImpuesto","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.utf8_encode($htmlTb).$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function predeterminarImpuesto($idIvaNuevo, $estatusPredet, $frmListaImpuesto) {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");
	
	$activo = ($estatusPredet == "false") ? 1 : 0;
	
	// ACTUALIZA EL IMPUESTO PREDETERMINADO
	$updateSQL = sprintf("UPDATE pg_iva SET
		activo = %s
	WHERE idIva = %s;",
		valTpDato($activo, "boolean"),
		valTpDato($idIvaNuevo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Impuesto Predeterminado Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarImpuesto').click();");
	
	$objResponse->loadCommands(listaImpuesto(
		$frmListaImpuesto['pageNum'],
		$frmListaImpuesto['campOrd'],
		$frmListaImpuesto['tpOrd'],
		$frmListaImpuesto['valBusq']));
	
	return $objResponse;
}

function verificarPredeterminado($idIva, $estatusPredet) {
	$objResponse = new xajaxResponse();
	
	// BUSCA EL TIPO DEL IMPUESTO
	$query = sprintf("SELECT tipo FROM pg_iva WHERE idIva = %s;",
		valTpDato($idIva, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nSQL: ".$sqlSelect);
	$row = mysql_fetch_array($rs);

	// VERIFICA SI EXISTE UN IMPUESTO DEL MISMO TIPO YA PREDETERMINADO
	$queryConfirmar = sprintf("SELECT idIva FROM pg_iva
	WHERE tipo = %s
		AND activo = 1;",
		valTpDato($row['tipo'], "int"));
	$rsConfirmar = mysql_query($queryConfirmar);
	if (!$rsConfirmar) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nSQL: ".$sqlConfirmar);
	$totalRowsConfirmar = mysql_num_rows($rsConfirmar);
	$rowConfirmar = mysql_fetch_array($rsConfirmar);
	
	if ($totalRowsConfirmar > 0 && $estatusPredet == "false") {
		$objResponse->script("validarPredeterminar(".$idIva.", '".$estatusPredet."');");
	} else if (!($totalRowsConfirmar > 0) && $estatusPredet == "false") {
		$objResponse->script("xajax_predeterminarImpuesto(".$idIva.", '".$estatusPredet."', xajax.getFormValues('frmListaImpuesto'));");
	} else if ($totalRowsConfirmar > 1 && $estatusPredet == "true") {
		$objResponse->script("xajax_predeterminarImpuesto(".$idIva.", '".$estatusPredet."', xajax.getFormValues('frmListaImpuesto'));");
	} else {
		$objResponse->alert("Debe dejar predeterminado al menos un impuesto de este tipo");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarImpuesto");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoImpuesto");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoImpuestoBuscar");
$xajax->register(XAJAX_FUNCTION,"eliminarImpuesto");
$xajax->register(XAJAX_FUNCTION,"formImpuesto");
$xajax->register(XAJAX_FUNCTION,"guardarImpuesto");
$xajax->register(XAJAX_FUNCTION,"listaImpuesto");
$xajax->register(XAJAX_FUNCTION,"predeterminarImpuesto");
$xajax->register(XAJAX_FUNCTION,"verificarPredeterminado");
?>
