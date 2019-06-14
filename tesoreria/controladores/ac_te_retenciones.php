<?php

function buscarRetencion($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['lstEstado'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaRetenciones(0, "id", "ASC", $valBusq));
	
	return $objResponse;
}

function formRetencion($idRetencion){
	$objResponse = new xajaxResponse();
	
	$queryRetencion = sprintf("SELECT * FROM te_retenciones WHERE id = %s",
		valTpDato($idRetencion, "int"));
	$rsRetencion = mysql_query($queryRetencion);
	if(!$rsRetencion){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowRetencion = mysql_fetch_assoc($rsRetencion);
	
	$objResponse->assign("txtDescripcion","value",utf8_encode($rowRetencion['descripcion']));
	$objResponse->assign("hddIdRetencion","value",$rowRetencion['id']);
	$objResponse->assign("txtImporte","value",$rowRetencion['importe']);
	$objResponse->assign("txtRetencion","value",$rowRetencion['porcentaje']);
	$objResponse->assign("txtUnidadTributaria","value",$rowRetencion['unidadtributaria']);
	$objResponse->assign("txtSustraendo","value",$rowRetencion['sustraendo']);
	$objResponse->assign("txtCodigo","value",$rowRetencion['codigo']);
	$objResponse->assign("lstActivo","value",$rowRetencion['activo']);
	
	if($idRetencion == ""){
		$objResponse->assign("lstActivo","value",1);
	}
	
	$objResponse->script("
		byId('txtDescripcion').className = 'inputHabilitado';
		byId('txtImporte').className = 'inputHabilitado';
		byId('txtUnidadTributaria').className = 'inputHabilitado';
		byId('txtRetencion').className = 'inputHabilitado';
		byId('txtSustraendo').className = 'inputHabilitado';
		byId('txtCodigo').className = 'inputHabilitado';
		byId('lstActivo').className = 'inputHabilitado';
	");
	
	return $objResponse;
}

function guardarRetencion($frmRetencion){
	$objResponse = new xajaxResponse();
	
	if ($frmRetencion['hddIdRetencion'] > 0) {
		if (!xvalidaAcceso($objResponse,"te_retenciones","editar")){ return $objResponse; }
		
		$queryRetencion = sprintf("UPDATE te_retenciones SET 
			descripcion = %s,
			importe = %s,
			porcentaje = %s,
			unidadtributaria = %s,
			sustraendo = %s,
			codigo = %s,
			activo = %s
		WHERE id = %s",
		valTpDato($frmRetencion['txtDescripcion'], "text"),
		valTpDato($frmRetencion['txtImporte'], "real_inglesa"),
		valTpDato($frmRetencion['txtRetencion'], "real_inglesa"),
		valTpDato($frmRetencion['txtUnidadTributaria'], "real_inglesa"),
		valTpDato($frmRetencion['txtSustraendo'], "real_inglesa"),
		valTpDato($frmRetencion['txtCodigo'], "int"),
		valTpDato($frmRetencion['lstActivo'], "int"),
		valTpDato($frmRetencion['hddIdRetencion'], "int"));
		
		mysql_query("SET NAMES 'utf8';");
		$rsRetencion = mysql_query($queryRetencion);
		if(!$rsRetencion){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"te_retenciones","insertar")){ return $objResponse; }

		$queryRetencion = sprintf("INSERT INTO te_retenciones (descripcion, importe, porcentaje, unidadtributaria, sustraendo, codigo, activo) VALUES (%s, %s, %s, %s, %s, %s, %s)",
		valTpDato($frmRetencion['txtDescripcion'], "text"),
		valTpDato($frmRetencion['txtImporte'], "real_inglesa"),
		valTpDato($frmRetencion['txtRetencion'], "real_inglesa"),
		valTpDato($frmRetencion['txtUnidadTributaria'], "real_inglesa"),
		valTpDato($frmRetencion['txtSustraendo'], "real_inglesa"),
		valTpDato($frmRetencion['txtCodigo'], "int"),
		valTpDato($frmRetencion['lstActivo'], "int"));
		
		mysql_query("SET NAMES 'utf8';");
		$rsRetencion = mysql_query($queryRetencion);
		if(!$rsRetencion){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	$objResponse->script("byId('btnBuscar').click();
	byId('btnCancelar').click();");
	$objResponse->alert("Retención guardada exitosamente");
	
	return $objResponse;
}

function listaRetenciones($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(descripcion LIKE %s
			OR codigo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
		
	$query = sprintf("SELECT * FROM te_retenciones %s", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaRetenciones", "35%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaRetenciones", "10%", $pageNum, "codigo", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo Concepto");
		$htmlTh .= ordenarCampo("xajax_listaRetenciones", "15%", $pageNum, "importe", $campOrd, $tpOrd, $valBusq, $maxRows, "Importe >= Para Aplicar");
		$htmlTh .= ordenarCampo("xajax_listaRetenciones", "10%", $pageNum, "unidadtributaria", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad Tributaria");
		$htmlTh .= ordenarCampo("xajax_listaRetenciones", "10%", $pageNum, "porcentaje", $campOrd, $tpOrd, $valBusq, $maxRows, "% Retencion");
		$htmlTh .= ordenarCampo("xajax_listaRetenciones", "10%", $pageNum, "sustraendo", $campOrd, $tpOrd, $valBusq, $maxRows, "Sustraendo");
		$htmlTh .= "<td width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowRetencion = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgActivo = ($rowRetencion["activo"]) ? "<img src=\"../img/iconos/ico_verde.gif\">" : "<img src=\"../img/iconos/ico_rojo.gif\">";
		
		$htmlTb .= "<tr class=\"".$clase."\">";            
			$htmlTb .= "<td align='center'>".$imgActivo."</td>";
			$htmlTb .= "<td align='left'>".utf8_encode($rowRetencion['descripcion'])."</td>";
			$htmlTb .= "<td align='center'>".$rowRetencion['codigo']."</td>";
			$htmlTb .= "<td align='right'>".$rowRetencion['importe']."</td>";
			$htmlTb .= "<td align='right'>".$rowRetencion['unidadtributaria']."</td>";
			$htmlTb .= "<td align='right'>".$rowRetencion['porcentaje']."</td>";
			$htmlTb .= "<td align='right'>".$rowRetencion['sustraendo']."</td>";
			$htmlTb .= "<td>";
			if($rowRetencion["id"] != 1){// 1 = SIN RETENCION
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante1(this, 'tblRetencion', %s);\"><img src=\"../img/iconos/ico_edit.png\" class=\"puntero\" /></a>",
					$rowRetencion['id']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRetenciones(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetenciones(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
        
	$objResponse->assign("tdListaRetenciones","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarRetencion");
$xajax->register(XAJAX_FUNCTION,"formRetencion");
$xajax->register(XAJAX_FUNCTION,"guardarRetencion");
$xajax->register(XAJAX_FUNCTION,"listaRetenciones");

?>