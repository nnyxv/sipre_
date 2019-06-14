<?php


function buscarConcepto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstTipoConceptoBuscar'],
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaConcepto(0, "id_concepto", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarImpuesto($frmBuscarImpuesto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarImpuesto['txtCriterioBuscarImpuesto']);
	
	$objResponse->loadCommands(listaImpuesto(0, "idIva", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstTipoConcepto($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cj_cc_tipo_concepto ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_concepto']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_concepto']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function eliminarConcepto($idConcepto, $frmListaConcepto, $eliminarLote = "false") {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_concepto_list","eliminar")) { return $objResponse; }
	
	if (isset($idConcepto)) {
		if ($eliminarLote == "false") {
			mysql_query("START TRANSACTION;");
		}
		
		$deleteSQL = sprintf("DELETE FROM cj_cc_concepto WHERE id_concepto = %s;",
			valTpDato($idConcepto, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return (($eliminarLote == "false") ? $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__) : array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__));
		
		if ($eliminarLote == "false") {
			mysql_query("COMMIT;") ;
		
			$objResponse->alert("Eliminacion realizada con éxito");
		
			$objResponse->loadCommands(listaConcepto(
				$frmListaConcepto['pageNum'],
				$frmListaConcepto['campOrd'],
				$frmListaConcepto['tpOrd'],
				$frmListaConcepto['valBusq']));
		}
	}
		
	return (($eliminarLote == "false") ? $objResponse : array(true, ""));
}

function eliminarConceptoLote($frmListaConcepto, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_concepto_list","eliminar")) { return $objResponse; }
		
	if (isset($frmListaConcepto['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach ($frmListaConcepto['cbxItm'] as $indiceItm => $valorItm) {
			$idConcepto = $valorItm;
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ESPERA POR FACTURAR)
			$Result1 = eliminarConcepto($idConcepto, $frmListaConcepto, "true");
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->loadCommands(listaConcepto(
			$frmListaConcepto['pageNum'],
			$frmListaConcepto['campOrd'],
			$frmListaConcepto['tpOrd'],
			$frmListaConcepto['valBusq']));
	}
		
	return $objResponse;
}

function formConcepto($idConcepto, $frmConcepto, $bloquearObj = "false") {
	$objResponse = new xajaxResponse();
	
	if ($idConcepto > 0) {
		if (!xvalidaAcceso($objResponse,"cc_concepto_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarConcepto').click();"); return $objResponse; }
		
		// BUSCA LOS DATOS DEL CONCEPTO
		$query = sprintf("SELECT * FROM cj_cc_concepto concep
		WHERE concep.id_concepto = %s;",
			valTpDato($idConcepto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		// BUSCA LOS IMPUESTOS DEL CONCEPTO
		$query = sprintf("SELECT * FROM cj_cc_concepto_impuesto concepto_impuesto
		WHERE concepto_impuesto.id_concepto = %s;",
			valTpDato($idConcepto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);		
		
		while($rowImpuesto = mysql_fetch_assoc($rs)){
			$objResponse->loadCommands(insertarImpuesto($rowImpuesto["id_impuesto"]));
		}
		
		$objResponse->assign("hddIdConcepto", "value", $row['id_concepto']);
		$objResponse->assign("txtCodigoConcepto", "value", utf8_encode($row['codigo_concepto']));
		$objResponse->assign("txtDescripcionConcepto", "value", utf8_encode($row['descripcion']));
		$objResponse->loadCommands(cargaLstTipoConcepto("lstTipoConcepto", $row['id_tipo_concepto']));
		$objResponse->call(selectedOption, "lstEstatus", $row['estatus']);
	} else {
		if (!xvalidaAcceso($objResponse,"cc_concepto_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarConcepto').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstTipoConcepto("lstTipoConcepto"));
	}
	
	$readOnly = ($bloquearObj == "true") ? "true" : "false";
	$className = ($bloquearObj == "true") ? "inputInicial" : "inputHabilitado";
	$disabled = ($bloquearObj == "true") ? "true" : "false";
	$display = ($bloquearObj == "true") ? "none" : "";
	
	$objResponse->script(sprintf("
	byId('txtCodigoConcepto').readOnly = %s;
	byId('txtCodigoConcepto').className = '%s';
	byId('txtDescripcionConcepto').readOnly = %s;
	byId('txtDescripcionConcepto').className = '%s';
	byId('lstTipoConcepto').disabled = %s;
	byId('lstTipoConcepto').className = '%s';
	byId('lstEstatus').disabled = %s;
	byId('lstEstatus').className = '%s';
	
	byId('btnGuardarConcepto').style.display = '%s';
	byId('btnAgregarImpuesto').style.display = '%s';
	byId('btnEliminarImpuesto').style.display = '%s';",
		$readOnly,
		$className,
		$readOnly,
		$className,
		$disabled,
		$className,
		$disabled,
		$className,
		
		$display,
		$display,
		$display));
	
	return $objResponse;
}

function guardarConcepto($frmConcepto, $frmListaConcepto) {
	$objResponse = new xajaxResponse();
	
	$idConcepto = $frmConcepto['hddIdConcepto'];
	
	if ($idConcepto > 0) {
		if (!xvalidaAcceso($objResponse,"cc_concepto_list","editar")) { errorGuardarConcepto($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE cj_cc_concepto SET
			id_tipo_concepto = %s,
			codigo_concepto = %s,
			descripcion = %s,
			estatus = %s
		WHERE id_concepto = %s;",
			valTpDato($frmConcepto['lstTipoConcepto'], "int"),
			valTpDato($frmConcepto['txtCodigoConcepto'], "text"),
			valTpDato($frmConcepto['txtDescripcionConcepto'], "text"),
			valTpDato($frmConcepto['lstEstatus'], "boolean"),
			valTpDato($idConcepto, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarConcepto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"cc_concepto_list","insertar")) { errorGuardarConcepto($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO cj_cc_concepto (id_tipo_concepto, codigo_concepto, descripcion, estatus)
		VALUE (%s, %s, %s, %s);",
			valTpDato($frmConcepto['lstTipoConcepto'], "int"),
			valTpDato($frmConcepto['txtCodigoConcepto'], "text"),
			valTpDato($frmConcepto['txtDescripcionConcepto'], "text"),
			valTpDato($frmConcepto['lstEstatus'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarConcepto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idConcepto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	$deleteSQL = sprintf("DELETE FROM cj_cc_concepto_impuesto WHERE id_concepto = %s",
		valTpDato($idConcepto, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { errorGuardarConcepto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	foreach($frmConcepto['cbxIdImpuesto'] as $idImpuesto){
		$insertSQL = sprintf("INSERT INTO cj_cc_concepto_impuesto (id_concepto, id_impuesto)
		VALUE (%s, %s);",
			valTpDato($idConcepto, "int"),
			valTpDato($idImpuesto, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarConcepto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarConcepto($objResponse);
	$objResponse->alert("Registro Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarConcepto').click();");
	
	$objResponse->loadCommands(listaConcepto(
		$frmListaConcepto['pageNum'],
		$frmListaConcepto['campOrd'],
		$frmListaConcepto['tpOrd'],
		$frmListaConcepto['valBusq']));
	
	return $objResponse;
}

function insertarImpuesto($idImpuesto, $frmConcepto) {
	$objResponse = new xajaxResponse();
	
	foreach($frmConcepto["cbxIdImpuesto"] as $idImpuestoAgregado){
		if ($idImpuesto == $idImpuestoAgregado) {
			return $objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	$query = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto)
	WHERE iva.idIva = %s;",
		valTpDato($idImpuesto, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieImpuesto').before('".
		"<tr id=\"trItmImpuesto\" align=\"left\" class=\"textoGris_11px\">".
			"<td><input id=\"cbxItmImpuesto\" name=\"cbxItmImpuesto[]\" type=\"checkbox\" class=\"cbxItmImpuesto\"/>".
				"<input id=\"cbxIdImpuesto\" name=\"cbxIdImpuesto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\">%s</td>".
		"</tr>');",
		$row['idIva'],
		utf8_encode($row['tipo_impuesto']),
		utf8_encode($row['observacion']),
		utf8_encode($row['iva']));
	
	$objResponse->script($htmlItmPie);
	$objResponse->script("
		$('.cbxItmImpuesto').closest('tr').each(function(index, element) {
			clase = ((index % 2) == 0) ? 'trResaltar4' : 'trResaltar5';
            element.className = clase + ' textoGris_11px';
        });
	");
	
	return $objResponse;
}

function listaConcepto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("concep.id_tipo_concepto = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("concep.estatus = %s",
			valTpDato($valCadBusq[1], "boolean"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(concep.id_concepto = %s
		OR concep.descripcion LIKE %s)",
			valTpDato($valCadBusq[2], "int"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT concep.*,
		tipo_concep.descripcion AS tipo_concepto
	FROM cj_cc_concepto concep
		INNER JOIN cj_cc_tipo_concepto tipo_concep ON (concep.id_tipo_concepto = tipo_concep.id_tipo_concepto) %s", $sqlBusq);
	
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
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,'frmListaConcepto');\"/></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "14%", $pageNum, "codigo_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "68%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "18%", $pageNum, "tipo_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Concepto");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_concepto']);
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_concepto'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo_concepto'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblConcepto', '%s', 'true');\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Concepto\"/></a>",
					$contFila,
					$row['id_concepto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblConcepto', '%s', 'false');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Concepto\"/></a>",
					$contFila,
					$row['id_concepto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante2\" onclick=\"validarEliminar(%s);\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Concepto\"/></a>",
					$row['id_concepto']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaConcepto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaConcepto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaImpuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iva.estado = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iva.tipo IN (6,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(observacion LIKE %s
		OR tipo_impuesto LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "8%", $pageNum, "idIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "24%", $pageNum, "tipo_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "44%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "12%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "12%", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, "Predeterminado");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$activo = ($row['activo'] == 1) ? "SI" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" onclick=\"xajax_insertarImpuesto(%s, xajax.getFormValues('frmConcepto'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$row['idIva']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['idIva']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo_impuesto'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['iva'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($activo)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"6\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaImpuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarConcepto");
$xajax->register(XAJAX_FUNCTION,"buscarImpuesto");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoConcepto");
$xajax->register(XAJAX_FUNCTION,"eliminarConcepto");
$xajax->register(XAJAX_FUNCTION,"eliminarConceptoLote");
$xajax->register(XAJAX_FUNCTION,"formConcepto");
$xajax->register(XAJAX_FUNCTION,"guardarConcepto");
$xajax->register(XAJAX_FUNCTION,"insertarImpuesto");
$xajax->register(XAJAX_FUNCTION,"listaConcepto");
$xajax->register(XAJAX_FUNCTION,"listaImpuesto");

function errorGuardarConcepto($objResponse) {
	$objResponse->script("
	byId('btnGuardarConcepto').disabled = false;
	byId('btnCancelarConcepto').disabled = false;");
}
?>