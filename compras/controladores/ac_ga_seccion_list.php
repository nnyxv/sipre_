<?php

function activarSeccion($idSeccion){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_seccion_list","editar")) { return $objResponse; }
	
	if (isset($idSeccion)) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("UPDATE ga_secciones SET estatu = 1 WHERE id_seccion = %s",
			valTpDato($idSeccion, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Activada con éxito");
		$objResponse->script("byId('btnBuscar').click();");
	}
		
	return $objResponse;
}

function activarSeccionBloque($valForm) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_seccion_list","editar")) { return $objResponse; }
	
	if (isset($valForm['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
			$deleteSQL = sprintf("UPDATE ga_secciones SET estatu = 1 WHERE id_seccion = %s",
				valTpDato($valorItm, "int"));
			
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		}
		
		mysql_query("COMMIT;");
		
		$num = count($valForm['cbxItm']);
		
		$objResponse->alert("Se ha activado: ".$num." con éxito");
		$objResponse->script("byId('btnBuscar').click();");
	}
		
	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstTipoSeccionBus'],
		$frmBuscar['lstEstatusBusq'],
		$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listadoSecciones(0, "descripcion", "ASC", $valBusq));

	return $objResponse;
}

function formSeccion() {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"ga_seccion_list","insertar")) { return $objResponse; }
	
	$objResponse->script("
		document.forms['frmSeccion'].reset();
		byId('hddIdSeccion').value = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Sección");
	$objResponse->script("		
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}");	
	
	return $objResponse;
}

function cargarSeccion($idSeccion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_seccion_list","editar")) { return $objResponse; }
	
	$querySeccion = sprintf("SELECT * FROM ga_secciones WHERE id_seccion = %s", $idSeccion);
	$rsSeccion = mysql_query($querySeccion);
	if (!$rsSeccion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowSeccion = mysql_fetch_assoc($rsSeccion);
	
	$objResponse->assign("hddIdSeccion","value",$idSeccion);
	$objResponse->assign("txtSeccion","value",$rowSeccion['descripcion']);
	$objResponse->assign("txtAbreviatura","value",$rowSeccion['corta']);
	$objResponse->script(sprintf("xajax_cargaLstTipoSeccion('%s', 'nuevo');", $rowSeccion['id_tipo_seccion']));
	$objResponse->assign("lstEstatus","value",$rowSeccion['estatu']);
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Sección");
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
	");
	
	return $objResponse;
}

function cargaLstTipoSeccion($selId = "", $tipo) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_tipo_seccion WHERE id_tipo_seccion <> 1 ORDER BY tipo_seccion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
		switch($tipo){
			case "nuevo";
				$id = "lstTipoSeccionNew";
				$name ="lstTipoSeccionNew";
				$option = "Seleccione";
				$ubicacion ="tdlstTipoSeccion";
				$onchange = "";
					break;
				
			case "buscar";
				$id = "lstTipoSeccionBus";
				$name ="lstTipoSeccionBus";
				$option = "Todos";
				$ubicacion ="tdlstTipoSeccionBus";
				$onchange = "onchange=\"byId('btnBuscar').click()\"";
					break;
			}
			
	$html = "<select id=".$id." name=".$name." class=\"inputHabilitado\" ".$onchange.">";
		$html .= "<option value=\"-1\">[".$option."]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_tipo_seccion'])
			$seleccion = "selected='selected'";
		$html .= "<option value=".$row['id_tipo_seccion']." ".$seleccion.">".htmlentities($row['tipo_seccion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($ubicacion,"innerHTML",$html);
	
	return $objResponse;
}

function guardarSeccion($valForm) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdSeccion'] > 0) {
		if (!xvalidaAcceso($objResponse,"ga_seccion_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE ga_secciones SET
			descripcion = %s,
			corta = %s,
			id_tipo_seccion = %s,
			estatu = %s
		WHERE id_seccion = %s",
			valTpDato($valForm['txtSeccion'], "text"),
			valTpDato($valForm['txtAbreviatura'], "text"),
			valTpDato($valForm['lstTipoSeccionNew'], "int"),
			valTpDato($valForm['lstEstatus'], "int"),
			valTpDato($valForm['hddIdSeccion'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"ga_seccion_list","insertar")) { return $objResponse; }
	
		$insertSQL = sprintf("INSERT INTO ga_secciones (descripcion, corta, id_tipo_seccion, estatu) VALUE (%s, %s, %s, %s)",
			valTpDato($valForm['txtSeccion'], "text"),
			valTpDato($valForm['txtAbreviatura'], "text"),
			valTpDato($valForm['lstTipoSeccionNew'], "int"), 
			valTpDato($valForm['lstEstatus'], "int"));		
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idSeccion = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Sección Guardada con éxito");
	$objResponse->script("byId('btnCancelar').click();
						byId('btnBuscar').click();");
		
	return $objResponse;
}

function desactivarSeccion($idSeccion){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_seccion_list","eliminar")) { return $objResponse; }
	
	if (isset($idSeccion)) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("UPDATE ga_secciones SET estatu = 0 WHERE id_seccion = %s",
			valTpDato($idSeccion, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Desactivada con éxito");
		$objResponse->script("byId('btnBuscar').click();");
	}
		
	return $objResponse;
}

function desactivarSeccionBloque($valForm) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_seccion_list","eliminar")) { return $objResponse; }
	
	if (isset($valForm['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
			$deleteSQL = sprintf("UPDATE ga_secciones SET estatu = 0 WHERE id_seccion = %s",
				valTpDato($valorItm, "int"));
			
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		}
		
		mysql_query("COMMIT;");
		
		$num = count($valForm['cbxItm']);
		
		$objResponse->alert("Se ha desactivado: ".$num." con éxito");
		$objResponse->script("byId('btnBuscar').click();");
	}
		
	return $objResponse;
}

function listadoSecciones($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
				
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ga_secciones.id_tipo_seccion = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ga_secciones.estatu = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(descripcion LIKE %s 
									OR corta LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}		
	
	$query = sprintf("SELECT id_seccion, descripcion, corta, ga_secciones.id_tipo_seccion, tipo_seccion, estatu
		FROM ga_secciones
		LEFT JOIN ga_tipo_seccion ON ga_tipo_seccion.id_tipo_seccion = ga_secciones.id_tipo_seccion %s",
		$sqlBusq);
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
		//$objResponse->alert($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listadoSecciones", "40%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion");
		$htmlTh .= ordenarCampo("xajax_listadoSecciones", "20%", $pageNum, "corta", $campOrd, $tpOrd, $valBusq, $maxRows, "Abreviatura ");
		$htmlTh .= ordenarCampo("xajax_listadoSecciones", "40%", $pageNum, "tipo_seccion", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Seccion");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatu']) {
			case 0 : $imgEstatusIva = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatusIva = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusIva = "";
		}	
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusIva."</td>";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",$row['id_seccion']);
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"left\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"left\" width=\"100%\">".utf8_encode($row['corta'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_seccion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante(this, 'tblSeccion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_seccion']);
			$htmlTb .= "</td>";
			if ($row['estatu'] == 1) {
				$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarDesactivar('%s')\" src=\"../img/iconos/ico_error.gif\" title=\"Desactivar\"/></td>",
					$row['id_seccion']);
			} else {
				$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarActivar('%s')\" src=\"../img/iconos/ico_aceptar.gif\" title=\"Activar\"/></td>",
					$row['id_seccion']);
			}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoSecciones(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divSecciones","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"activarSeccionBloque");
$xajax->register(XAJAX_FUNCTION,"activarSeccion");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"formSeccion");
$xajax->register(XAJAX_FUNCTION,"cargarSeccion");
$xajax->register(XAJAX_FUNCTION,"guardarSeccion");
$xajax->register(XAJAX_FUNCTION,"desactivarSeccionBloque");
$xajax->register(XAJAX_FUNCTION,"desactivarSeccion");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoSeccion");
$xajax->register(XAJAX_FUNCTION,"listadoSecciones");

?>