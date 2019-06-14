<?php

function activarSubSeccion($idSubseccion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_subseccion_list","editar")) { return $objResponse; }
	
	if (isset($idSubseccion)) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("UPDATE ga_subsecciones SET estatu = 1 WHERE id_subseccion = %s",
			valTpDato($idSubseccion, "int"));				
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Activada con éxito");
		$objResponse->script("byId('btnBuscar').click();");
	}
		
	return $objResponse;
}

function activarSubSeccionBloque($valForm) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_subseccion_list","editar")) { return $objResponse; }
	
	if (isset($valForm['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
			$deleteSQL = sprintf("UPDATE ga_subsecciones SET estatu = 1 WHERE id_subseccion = %s",
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

function asignarTipActivo($codigo){
	$objResponse = new xajaxResponse();
	
	$sql =sprintf("SELECT * FROM ".DBASE_CONTAB.".tipoactivo WHERE id = %s",valTpDato($codigo, "text"));
	$query = mysql_query($sql);
	if (!$query) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($query);
	
	$objResponse->assign("idTipoActivo","value",$row['id']);
	$objResponse->assign("codTipoActivo","value",$row['Codigo']);	
	$objResponse->assign("desTipoActivo","value",utf8_encode($row['Descripcion']));	
	$objResponse->script("byId('btnCerrarLstTpoAct').click();");
	
	return $objResponse;
}

function buscar($frmBuscar, $tipoBus){
	$objResponse = new xajaxResponse();
	
	switch($tipoBus){
		case "subSeccion";
			$valBus= sprintf("%s|%s|%s",
			$frmBuscar["lstSeccionBus"],
			$frmBuscar["lstEstatusBusq"],
			$frmBuscar["txtCriterio"]);
			$objResponse->loadCommands(listadoSubSecciones(0,'descripcion_subseccion','ASC', $valBus));
		break;
					
		case "lisTipoAct":
			$valBus = sprintf("%s",
			$frmBuscar['texTipAct']);
			$objResponse->loadCommands(listadoTipoActivo(0,'Descripcion','ASC', $valBus));
		break;
	}
	
	return $objResponse;
}

function formSubSeccion() {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_subseccion_list","insertar")) { return $objResponse; }
	
	$objResponse->script("
		document.forms['frmSubSeccion'].reset();
		$('hddIdSubSeccion').value = '';
		byId('txtSubSeccion').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputHabilitado';");

	$objResponse->loadCommands(cargaLstSeccion('','nuevo')); 
		
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
	");
	
	return $objResponse;
}

function cargarSubSeccion($idSubSeccion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_subseccion_list","editar")) { return $objResponse; }
	
	$querySubSeccion = sprintf("SELECT * FROM ga_subsecciones WHERE id_subseccion = %s", $idSubSeccion);
	$rsSubSeccion = mysql_query($querySubSeccion);
	if (!$rsSubSeccion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowSubSeccion = mysql_fetch_assoc($rsSubSeccion);
	
	$objResponse->assign("hddIdSubSeccion","value",$idSubSeccion);
	$objResponse->assign("txtSubSeccion","value",$rowSubSeccion['descripcion']);


	$objResponse->loadCommands(cargaLstSeccion($rowSubSeccion['id_seccion'], 'nuevo'));
	
	if($rowSubSeccion['id_seccion'] == 5) {
		$objResponse->script("activaTr(5)");
		$objResponse->loadCommands(asignarTipActivo($rowSubSeccion['tipo_activo']));
	}
	
	$objResponse->assign("lstEstatus","value",$rowSubSeccion['estatu']);
	
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {	
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}");
	
	
	return $objResponse;
}

function cargaLstSeccion($selId = "", $tipo) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_secciones ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	switch($tipo){
		case "nuevo":
			$id="lstSeccionNuv";
			$name ="lstSeccionNuv";
			$optiom ="Seleccione";
			$ubicacion="tdlstSeccion";
			$onchange="onchange=\"activaTr(this.value);\"";	
			$class = "class=\"inputHabilitado\"";
		break;
		case "buscar":
			$id="lstSeccionBus";
			$name ="lstSeccionBus";
			$optiom ="Todos";
			$ubicacion="tdlstTipoSubSeccionBus";
			$onchange="onchange=\"byId('btnBuscar').click()\"";	
			$class = "class=\"inputHabilitado\"";			
		break;
	}
	
	$html = "<select id=".$id." name=".$name." ".$class."  ".$onchange.">";
		$html .= "<option value=\"-1\">[ ".$optiom." ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_seccion'])
			$seleccion = "selected='selected'";
		$html .= "<option value=\"".$row['id_seccion']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($ubicacion,"innerHTML",$html);
	
	return $objResponse;
}

function guardarSubSeccion($valForm) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdSubSeccion'] > 0) {		
		if (!xvalidaAcceso($objResponse,"ga_subseccion_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE ga_subsecciones SET
			id_seccion = %s,
			descripcion = %s,
			tipo_activo = %s,
			estatu = %s
		WHERE id_subseccion = %s",
			valTpDato($valForm['lstSeccionNuv'], "int"),
			valTpDato($valForm['txtSubSeccion'], "text"),
			valTpDato($valForm['idTipoActivo'], "int"),
			valTpDato($valForm['lstEstatus'], "text"),
			valTpDato($valForm['hddIdSubSeccion'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"ga_subseccion_list","insertar")) { return $objResponse; }

		if($valForm['lstSeccionNuv'] == 5 && $valForm['codTipoActivo'] == ""){
			return $objResponse->alert("Debe seleccionar una tipo de activo");
		}
								
		$insertSQL = sprintf("INSERT INTO ga_subsecciones (id_seccion, descripcion, tipo_activo, estatu) VALUE (%s, %s, %s, %s)",
			valTpDato($valForm['lstSeccionNuv'], "int"),
			valTpDato($valForm['txtSubSeccion'], "text"),
			valTpDato($valForm['idTipoActivo'], "int"),
			valTpDato($valForm['lstEstatus'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idSubSeccion = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Sub-Sección Guardada con éxito");	
	$objResponse->script("byId('btnCancelar').click();
						byId('btnBuscar').click();");
		
	return $objResponse;
}

function desactivarSubSeccion($idSubseccion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_subseccion_list","eliminar")) { return $objResponse; }
	
	if (isset($idSubseccion)) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("UPDATE ga_subsecciones SET estatu = 0 WHERE id_subseccion = %s",
			valTpDato($idSubseccion, "int"));				
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Desactivada con éxito");
		$objResponse->script("byId('btnBuscar').click();");
	}
		
	return $objResponse;
}

function desactivarSubSeccionBloque($valForm) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_subseccion_list","eliminar")) { return $objResponse; }
	
	if (isset($valForm['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
			$deleteSQL = sprintf("UPDATE ga_subsecciones SET estatu = 0 WHERE id_subseccion = %s",
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

function listadoSubSecciones($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
				
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ga_secciones.id_seccion = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ga_subsecciones.estatu = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ga_subsecciones.descripcion LIKE %s
		OR ga_secciones.corta LIKE %s 
		OR ga_tipo_seccion.tipo_seccion LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}		
	
	$query = sprintf("SELECT 
					ga_subsecciones.id_subseccion,
					ga_subsecciones.descripcion AS descripcion_subseccion,
					ga_secciones.id_seccion,
					ga_secciones.descripcion,
					ga_secciones.corta,
					ga_tipo_seccion.id_tipo_seccion,
					ga_tipo_seccion.tipo_seccion,
					ga_subsecciones.estatu
				FROM ga_subsecciones
					INNER JOIN ga_secciones ON (ga_subsecciones.id_seccion = ga_secciones.id_seccion)
					INNER JOIN ga_tipo_seccion ON (ga_secciones.id_tipo_seccion = ga_tipo_seccion.id_tipo_seccion) %s",
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
		$htmlTh .= ordenarCampo("xajax_listadoSubSecciones", "20%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Seccion");
		$htmlTh .= ordenarCampo("xajax_listadoSubSecciones", "40%", $pageNum, "descripcion_subseccion",$campOrd,$tpOrd,$valBusq,$maxRows,"Descripcion Sub-seccion");
		$htmlTh .= ordenarCampo("xajax_listadoSubSecciones", "10%", $pageNum, "corta", $campOrd, $tpOrd, $valBusq, $maxRows, "Abreviatura Seccion");
		$htmlTh .= ordenarCampo("xajax_listadoSubSecciones", "30%", $pageNum, "tipo_seccion", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Compra");
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
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",$row['id_subseccion']);
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"left\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"left\" width=\"100%\">".utf8_encode($row['descripcion_subseccion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['corta'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_seccion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante(this, 'tblSubSeccion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_subseccion']);
			$htmlTb .= "</td>";
			if ($row['estatu'] == 1) {
				$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarDesactivar('%s')\" src=\"../img/iconos/ico_error.gif\" title=\"Desactivar\"/></td>",
					$row['id_subseccion']);
			} else {
				$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarActivar('%s')\" src=\"../img/iconos/ico_aceptar.gif\" title=\"Activar\"/></td>",
					$row['id_subseccion']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divSubSecciones","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listadoTipoActivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(Codigo LIKE %s) OR (Descripcion LIKE %s) OR (CodDebe LIKE %s) OR (CodHaber LIKE %s)", 
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
		
	$query = sprintf("SELECT * FROM ".DBASE_CONTAB.".tipoactivo %s",
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
		$htmlTh .= ordenarCampo("xajax_listadoTipoActivo", "15%", $pageNum, "Codigo", $campOrd, $tpOrd, $valBusq, $maxRows, "Codigo");
		$htmlTh .= ordenarCampo("xajax_listadoTipoActivo", "45%", $pageNum, "Descripcion",$campOrd,$tpOrd,$valBusq,$maxRows,"Descripcion");
		$htmlTh .= ordenarCampo("xajax_listadoTipoActivo", "20%", $pageNum, "CodDebe", $campOrd, $tpOrd, $valBusq, $maxRows, "Codigo Debe");
		$htmlTh .= ordenarCampo("xajax_listadoTipoActivo", "20%", $pageNum, "CodHaber", $campOrd, $tpOrd, $valBusq, $maxRows, "Codigo Haber");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
				
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			//$htmlTb .= "<td>".$imgEstatusIva."</td>";
			$htmlTb .= "<td><button type=\"button\" onclick=\"xajax_asignarTipActivo('".$row['id']."');\" title=\"Asignar Tipo de Activo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button></td>";
			$htmlTb .= "<td>".$row['Codigo']."</td>";
			$htmlTb .= "<td align=\"left\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"left\" width=\"100%\">".utf8_encode($row['Descripcion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$row['CodDebe']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['CodHaber']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoActivo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoActivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTipoActivo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoActivo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoActivo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("lstTipoActivo","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"activarSubSeccionBloque");
$xajax->register(XAJAX_FUNCTION,"activarSubSeccion");
$xajax->register(XAJAX_FUNCTION,"asignarTipActivo");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"formSubSeccion");
$xajax->register(XAJAX_FUNCTION,"cargarSubSeccion");
$xajax->register(XAJAX_FUNCTION,"cargaLstSeccion");
$xajax->register(XAJAX_FUNCTION,"guardarSubSeccion");
$xajax->register(XAJAX_FUNCTION,"desactivarSubSeccion");
$xajax->register(XAJAX_FUNCTION,"desactivarSubSeccionBloque");
$xajax->register(XAJAX_FUNCTION,"listadoSubSecciones");
$xajax->register(XAJAX_FUNCTION,"listadoTipoActivo");

?>