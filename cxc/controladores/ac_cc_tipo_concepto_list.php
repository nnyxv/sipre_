<?php


function buscarTipoConcepto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaTipoConcepto(0, "id_tipo_concepto", "DESC", $valBusq));
	
	return $objResponse;
}

function eliminarTipoConcepto($idTipoConcepto, $frmListaTipoConcepto, $eliminarLote = "false") {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_tipo_concepto_list","eliminar")) { return $objResponse; }
	
	if (isset($idTipoConcepto)) {
		if ($eliminarLote == "false") {
			mysql_query("START TRANSACTION;");
		}
		
		$deleteSQL = sprintf("DELETE FROM cj_cc_tipo_concepto WHERE id_tipo_concepto = %s;",
			valTpDato($idTipoConcepto, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return (($eliminarLote == "false") ? $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__) : array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__));
		
		if ($eliminarLote == "false") {
			mysql_query("COMMIT;") ;
		
			$objResponse->alert("Eliminacion realizada con éxito");
		
			$objResponse->loadCommands(listaTipoConcepto(
				$frmListaTipoConcepto['pageNum'],
				$frmListaTipoConcepto['campOrd'],
				$frmListaTipoConcepto['tpOrd'],
				$frmListaTipoConcepto['valBusq']));
		}
	}
		
	return (($eliminarLote == "false") ? $objResponse : array(true, ""));
}

function eliminarTipoConceptoLote($frmListaTipoConcepto, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_tipo_concepto_list","eliminar")) { return $objResponse; }
		
	if (isset($frmListaTipoConcepto['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach ($frmListaTipoConcepto['cbxItm'] as $indiceItm => $valorItm) {
			$idTipoConcepto = $valorItm;
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
			$Result1 = eliminarTipoConcepto($idTipoConcepto, $frmListaTipoConcepto, "true");
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->loadCommands(listaTipoConcepto(
			$frmListaTipoConcepto['pageNum'],
			$frmListaTipoConcepto['campOrd'],
			$frmListaTipoConcepto['tpOrd'],
			$frmListaTipoConcepto['valBusq']));
	}
		
	return $objResponse;
}

function formTipoConcepto($idTipoConcepto, $frmTipoConcepto, $bloquearObj = "false") {
	$objResponse = new xajaxResponse();
	
	if ($idTipoConcepto > 0) {
		if (!xvalidaAcceso($objResponse,"cc_tipo_concepto_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTipoConcepto').click();"); return $objResponse; }
		
		// BUSCA LOS DATOS DEL CONCEPTO
		$query = sprintf("SELECT * FROM cj_cc_tipo_concepto concep
		WHERE concep.id_tipo_concepto = %s;",
			valTpDato($idTipoConcepto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdTipoConcepto", "value", $row['id_tipo_concepto']);
		$objResponse->assign("txtDescripcionTipoConcepto", "value", utf8_encode($row['descripcion']));
	} else {
		if (!xvalidaAcceso($objResponse,"cc_tipo_concepto_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarTipoConcepto').click();"); return $objResponse; }
	}
	
	$readOnly = ($bloquearObj == "true") ? "true" : "false";
	$className = ($bloquearObj == "true") ? "inputInicial" : "inputHabilitado";
	$disabled = ($bloquearObj == "true") ? "true" : "false";
	$display = ($bloquearObj == "true") ? "none" : "";
	
	$objResponse->script(sprintf("
	byId('txtDescripcionTipoConcepto').readOnly = %s;
	byId('txtDescripcionTipoConcepto').className = '%s';
	
	byId('btnGuardarTipoConcepto').style.display = '%s';",
		$readOnly,
		$className,
		
		$display));
	
	return $objResponse;
}

function guardarTipoConcepto($frmTipoConcepto, $frmListaTipoConcepto) {
	$objResponse = new xajaxResponse();
	
	$idTipoConcepto = $frmTipoConcepto['hddIdTipoConcepto'];
	
	if ($idTipoConcepto > 0) {
		if (!xvalidaAcceso($objResponse,"cc_tipo_concepto_list","editar")) { errorGuardarTipoConcepto($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE cj_cc_tipo_concepto SET
			descripcion = %s
		WHERE id_tipo_concepto = %s;",
			valTpDato($frmTipoConcepto['txtDescripcionTipoConcepto'], "text"),
			valTpDato($idTipoConcepto, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarTipoConcepto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"cc_tipo_concepto_list","insertar")) { errorGuardarTipoConcepto($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO cj_cc_tipo_concepto (descripcion)
		VALUE (%s);",
			valTpDato($frmTipoConcepto['txtDescripcionTipoConcepto'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarTipoConcepto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idTipoConcepto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarTipoConcepto($objResponse);
	$objResponse->alert("Registro Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarTipoConcepto').click();");
	
	$objResponse->loadCommands(listaTipoConcepto(
		$frmListaTipoConcepto['pageNum'],
		$frmListaTipoConcepto['campOrd'],
		$frmListaTipoConcepto['tpOrd'],
		$frmListaTipoConcepto['valBusq']));
	
	return $objResponse;
}

function listaTipoConcepto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(tipo_concep.id_tipo_concepto = %s
		OR tipo_concep.descripcion LIKE %s)",
			valTpDato($valCadBusq[0], "int"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT tipo_concep.* FROM cj_cc_tipo_concepto tipo_concep %s", $sqlBusq);
	
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
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,'frmListaTipoConcepto');\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listaTipoConcepto", "100%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_tipo_concepto']);
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblTipoConcepto', '%s', 'true');\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Tipo Concepto\"/></a>",
					$contFila,
					$row['id_tipo_concepto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblTipoConcepto', '%s', 'false');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Tipo Concepto\"/></a>",
					$contFila,
					$row['id_tipo_concepto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante2\" onclick=\"validarEliminar(%s);\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Tipo Concepto\"/></a>",
					$row['id_tipo_concepto']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTipoConcepto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaTipoConcepto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarTipoConcepto");
$xajax->register(XAJAX_FUNCTION,"eliminarTipoConcepto");
$xajax->register(XAJAX_FUNCTION,"eliminarTipoConceptoLote");
$xajax->register(XAJAX_FUNCTION,"formTipoConcepto");
$xajax->register(XAJAX_FUNCTION,"guardarTipoConcepto");
$xajax->register(XAJAX_FUNCTION,"listaTipoConcepto");

function errorGuardarTipoConcepto($objResponse) {
	$objResponse->script("
	byId('btnGuardarTipoConcepto').disabled = false;
	byId('btnCancelarTipoConcepto').disabled = false;");
}
?>