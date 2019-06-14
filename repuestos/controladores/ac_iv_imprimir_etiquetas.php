<?php


function buscarUbicacion($frmBuscar){
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		implode(",",$frmBuscar['lstSaldos']),
		$frmBuscar['lstAlmacenBusqueda'],
		$frmBuscar['lstCalleBusqueda'],
		$frmBuscar['lstEstanteBusqueda'],
		$frmBuscar['lstTramoBusqueda'],
		$frmBuscar['lstCasillaBusqueda'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUbicacion(0, "CONCAT(descripcion_almacen, ubicacion, id_articulo_costo)", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstBusqueda($tpLst, $idLstOrigen, $adjLst, $padreId, $nivReg, $selId){
	$objResponse = new xajaxResponse();
	
	switch ($tpLst) {
		case "almacenes" : 	$arraySelec = array("lstPadre","lstAlmacen","lstCalle","lstEstante","lstTramo","lstCasilla"); break;
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	if (($posList+1) != count($arraySelec)-1)
		$onChange = "onchange=\"xajax_cargaLstBusqueda('".$tpLst."', '".$arraySelec[$posList+1]."', '".$adjLst."', this.value, 'null', 'null');\"";
	
	$html = "<select id=\"".$arraySelec[$posList+1].$adjLst."\" name=\"".$arraySelec[$posList+1].$adjLst."\" class=\"inputHabilitado\" ".$onChange.">";
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1') {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\" class=\"inputHabilitado\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html);
			}
		}
		
		return $objResponse;
	} else {
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\" class=\"inputHabilitado\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 :
				$query = sprintf("SELECT
					almacen.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
						INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
						INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
					WHERE calle.id_almacen = almacen.id_almacen
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_almacenes almacen
				WHERE almacen.id_empresa = %s
					AND almacen.estatus = 1
				ORDER BY almacen.descripcion;",
					valTpDato($padreId, "int"));
				$campoId = "id_almacen";
				$campoDesc = "descripcion"; break;
			case 1 :
				$query = sprintf("SELECT
					calle.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
						INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
					WHERE estante.id_calle = calle.id_calle
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_calles calle
				WHERE calle.id_almacen = %s
				ORDER BY calle.descripcion_calle;",
					valTpDato($padreId, "int"));
				$campoId = "id_calle";
				$campoDesc = "descripcion_calle"; break;
			case 2 :
				$query = sprintf("SELECT
					estante.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
					WHERE tramo.id_estante = estante.id_estante
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_estantes estante
				WHERE estante.id_calle = %s
				ORDER BY estante.descripcion_estante;",
					valTpDato($padreId, "int"));
				$campoId = "id_estante";
				$campoDesc = "descripcion_estante"; break;
			case 3 :
				$query = sprintf("SELECT
					tramo.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
					WHERE casilla.id_tramo = tramo.id_tramo
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_tramos tramo
				WHERE tramo.id_estante = %s
				ORDER BY tramo.descripcion_tramo;",
					valTpDato($padreId, "int"));
				$campoId = "id_tramo";
				$campoDesc = "descripcion_tramo"; break;
			case 4 :
				$query = sprintf("SELECT
					casilla.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = casilla.id_casilla
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_casillas casilla
				WHERE casilla.id_tramo = %s
				ORDER BY casilla.descripcion_casilla;",
					valTpDato($padreId, "int"));
				$campoId = "id_casilla";
				$campoDesc = "descripcion_casilla"; break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
			$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
			$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
			
			$html .= "<option value=\"".$row[$campoId]."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row[$campoDesc].$ocupada)."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->assign("td".$arraySelec[$posList+1].$adjLst, 'innerHTML', $html);
	
	$objResponse->assign("tdMsj","innerHTML","");
	
	return $objResponse;
}

function cargaLstTasaCambioItm($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT *
	FROM pg_tasa_cambio tasa_cambio
		INNER JOIN pg_monedas moneda_local ON (tasa_cambio.id_moneda_nacional = moneda_local.idmoneda)
	WHERE tasa_cambio.id_moneda_nacional IN (SELECT moneda.idmoneda FROM pg_monedas moneda
												WHERE moneda.estatus = 1
													AND moneda.predeterminada = 1);");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputCompleto\" style=\"min-width:60px\">";
	if ($totalRows > 0) {
			$html .= "<option value=\"\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['id_tasa_cambio']) ? "selected=\"selected\"" : "";
			
			$html .= "<optgroup label=\"".$row['abreviacion']." ".$row['monto_tasa_cambio']."\">";
				$html .= "<option ".$selected." value=\"".utf8_encode($row['nombre_tasa_cambio'])."\">".utf8_encode($row['nombre_tasa_cambio'])."</option>";
			$html .= "</optgroup>";
		}
	} else {
		$html .= "<option value=\"\"></option>";
	}
	$html .= "</select>";
	
	return $html;
}

function eliminarArticulo($frmArticulos){
	$objResponse = new xajaxResponse();
	
	if (isset($frmArticulos['cbxItm'])) {
		foreach ($frmArticulos['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarArticulo(xajax.getFormValues('frmArticulos'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmArticulos['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
		
	return $objResponse;
}

function imprimirEtiqueta($frmArticulos){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmArticulos['cbx'];
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$arrayEtiqueta[] = implode(",", array(
				$frmArticulos['hddIdArticulo'.$valor],
				$frmArticulos['hddIdCasilla'.$valor],
				$frmArticulos['hddIdArticuloCosto'.$valor],
				$frmArticulos['txtCantidad'.$valor],
				str_replace(",","",$frmArticulos['lstTasaCambio'.$valor])));
		}
	}
	
	$objResponse->script("verVentana('reportes/iv_articulo_etiqueta_pdf.php?valBusq=".implode("|", $arrayEtiqueta)."', 400, 300);");
	
	return $objResponse;
}

function insertarArticulo($frmListaUbicacion, $frmArticulos){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmArticulos['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if (isset($frmListaUbicacion['cbxArticulo'])) {
		foreach ($frmListaUbicacion['cbxArticulo'] as $indice => $valor) {
			$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
			WHERE vw_iv_art_almacen_costo.id_articulo_almacen_costo = %s;",
				valTpDato($valor, "int"));
			$rsArtCosto = mysql_query($queryArtCosto);
			if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtCosto = mysql_fetch_array($rsArtCosto);
			
			$existe = false;
			if (isset($arrayObj)){
				foreach ($arrayObj as $indice2 => $valor2){
					if ($frmArticulos['hddIdArticuloAlmacenCosto'.$valor2] == $valor){
						$existe = true;
					}
				}
			}
			
			if ($existe == false){
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$objResponse->script(sprintf("$('#trItmPie').before('".
					"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px\">".
						"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
							"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\"></td>".
						"<td align=\"center\"><input type=\"text\" id=\"txtCantidad%s\" name=\"txtCantidad%s\" class=\"inputCompleto\" style=\"text-align:center\" value=\"1\" size=\"5\"/></td>".
						"<td align=\"center\">%s</td>".
						"<td>%s</td>".
						"<td>%s</td>".
						"<td>%s".
							"<input type=\"hidden\" id=\"hddIdArticulo%s\" name=\"hddIdArticulo%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdArticuloAlmacenCosto%s\" name=\"hddIdArticuloAlmacenCosto%s\" readonly=\"readonly\" title=\"Ubicacion del Lote\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" title=\"Lote\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/></td>".
					"</tr>');",
					$contFila,
						$contFila, $contFila,
							$contFila,
						$contFila,
						$contFila, $contFila,
						"<span class=\"textoNegrita_10px\">".utf8_encode(strtoupper($rowArtCosto['descripcion_almacen']))."</span><br>".
							utf8_encode(str_replace("-[]", "", $rowArtCosto['ubicacion'])).
							(($rowArtCosto['estatus_articulo_almacen'] == 1) ? "" : "<br>(Inactiva)"),
						elimCaracter(utf8_encode($rowArtCosto['codigo_articulo']),";"),
						preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($rowArtCosto['descripcion']))),
						cargaLstTasaCambioItm("lstTasaCambio".$contFila),
							$contFila, $contFila, $rowArtCosto['id_articulo'],
							$contFila, $contFila, $rowArtCosto['id_articulo_almacen_costo'],
							$contFila, $contFila, $rowArtCosto['id_articulo_costo'],
							$contFila, $contFila, $rowArtCosto['id_casilla']));
				
				$arrayObj[] = $contFila;
			} else {
				$arrayYaExiste[] = elimCaracter($rowArtCosto['codigo_articulo'],";");
			}
		}
	}
	
	if (count($arrayYaExiste)) {
		if (count($arrayYaExiste) > 1) {
			$mensajeArticulos = $arrayYaExiste[0];
			for ($i = 1; $i < count($arrayYaExiste); $i++) {
				$mensajeArticulos .= ", ".$arrayYaExiste[$i];
			}
			$mensaje = "Los Artículos ".$mensajeArticulos." ya se encuentran incluidos";
		} else {
			$mensaje = "El Artículo ".$arrayYaExiste[0]." ya se encuentra incluido";
		}
		$objResponse->alert(utf8_encode($mensaje));
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	
	return $objResponse;
}

function listaUbicacion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 40, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla IS NOT NULL");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
	OR (vw_iv_art_almacen_costo.estatus_articulo_almacen IS NULL AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0))");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_art_almacen_costo.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if (in_array(1,explode(",",$valCadBusq[1]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
	}
	
	if (in_array(2,explode(",",$valCadBusq[1]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica <= 0");
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_almacen = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_calle = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_estante = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_tramo = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla = %s",
			valTpDato($valCadBusq[6], "int"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[7], "text"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.id_articulo = %s
		OR vw_iv_art_almacen_costo.id_articulo_costo LIKE %s
		OR vw_iv_art_almacen_costo.descripcion LIKE %s
		OR vw_iv_art_almacen_costo.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[8], "int"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_iv_art_almacen_costo.*,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
		
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
			WHEN 1 THEN	vw_iv_art_almacen_costo.costo
			WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
			WHEN 3 THEN	vw_iv_art_almacen_costo.costo
		END) AS costo_unitario
	FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL){
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td class=\"noprint\"><input type=\"checkbox\" id=\"cbxArticulo\" onclick=\"selecAllChecks(this.checked,this.id,'frmListaUbicacion');\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "10%", $pageNum, "CONCAT(descripcion_almacen, ubicacion)", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ubicación"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "50%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Lote"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "8%", $pageNum, "costo_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, ("Costo"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "8%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, ("Unid. Disponible"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td class=\"noprint\"><input type=\"checkbox\" id=\"cbxArticulo\" name=\"cbxArticulo[]\" value=\"%s\"/></td>",
				$row['id_articulo_almacen_costo']);
			$htmlTb .= "<td align=\"center\" ".$classUbic." nowrap=\"nowrap\">";
				$htmlTb .= "<span class=\"textoNegrita_10px\">".utf8_encode(strtoupper($row['descripcion_almacen']))."</span><br>";
				$htmlTb .= utf8_encode(str_replace("-[]", "", $row['ubicacion']));
				$htmlTb .= ($row['estatus_articulo_almacen'] == 1) ? "" : "<br><span class=\"textoRojoNegrita_10px\">".utf8_encode("Relacion Inactiva")."</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_articulo_costo']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= ($row['cantidad_disponible_logica'] > 0) ? "<td align=\"right\" class=\"divMsjInfo\">" : (($row['cantidad_disponible_logica'] < 0) ? "<td align=\"right\" class=\"divMsjError\">" : "<td align=\"right\">");
				$htmlTb .= valTpDato(number_format($row['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio");
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
						if ($pageNum > 0){
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0){
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUbicacion(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages){
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages){
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacion(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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
	
	if (!($totalRows > 0)){
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUbicacion","innerHTML",utf8_encode($htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUbicacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstBusqueda");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"imprimirEtiqueta");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"listaUbicacion");


function buscarEnArray($arrays, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x=0;
	foreach ($arrays as $indice=>$valor) {
		if($valor == $dato)
			return $x;
		
		$x++;
	}
	return null;
}
?>