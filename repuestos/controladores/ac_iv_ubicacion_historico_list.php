<?php


function buscarUbicacion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";	
	for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
		$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
		$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
	}
	
	if ($codArticuloAux != "") {
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = codArticuloExpReg($codArticulo);
	} else {
		$codArticulo = "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['cbxVerArtUnaUbic'],
		$frmBuscar['cbxVerArtMultUbic'],
		$frmBuscar['cbxVerUbicLibre'],
		$frmBuscar['cbxVerUbicOcup'],
		$frmBuscar['cbxVerUbicDisponible'],
		$frmBuscar['cbxVerUbicSinDisponible'],
		$frmBuscar['lstEstatus'],
		$frmBuscar['lstAlmacenBusqueda'],
		$frmBuscar['lstCalleBusqueda'],
		$frmBuscar['lstEstanteBusqueda'],
		$frmBuscar['lstTramoBusqueda'],
		$frmBuscar['lstCasillaBusqueda'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUbicacion(0, "CONCAT(descripcion_almacen, ubicacion)", "ASC", $valBusq));
	
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
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
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

function exportarUbicacion($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";	
	for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
		$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
		$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
	}
	
	if ($codArticuloAux != "") {
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = codArticuloExpReg($codArticulo);
	} else {
		$codArticulo = "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['cbxVerArtUnaUbic'],
		$frmBuscar['cbxVerArtMultUbic'],
		$frmBuscar['cbxVerUbicLibre'],
		$frmBuscar['cbxVerUbicOcup'],
		$frmBuscar['cbxVerUbicDisponible'],
		$frmBuscar['cbxVerUbicSinDisponible'],
		$frmBuscar['lstEstatus'],
		$frmBuscar['lstAlmacenBusqueda'],
		$frmBuscar['lstCalleBusqueda'],
		$frmBuscar['lstEstanteBusqueda'],
		$frmBuscar['lstTramoBusqueda'],
		$frmBuscar['lstCasillaBusqueda'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_ubicacion_historico_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirUbicacion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";	
	for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
		$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
		$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
	}
	
	if ($codArticuloAux != "") {
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = codArticuloExpReg($codArticulo);
	} else {
		$codArticulo = "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['cbxVerArtUnaUbic'],
		$frmBuscar['cbxVerArtMultUbic'],
		$frmBuscar['cbxVerUbicLibre'],
		$frmBuscar['cbxVerUbicOcup'],
		$frmBuscar['cbxVerUbicDisponible'],
		$frmBuscar['cbxVerUbicSinDisponible'],
		$frmBuscar['lstEstatus'],
		$frmBuscar['lstAlmacenBusqueda'],
		$frmBuscar['lstCalleBusqueda'],
		$frmBuscar['lstEstanteBusqueda'],
		$frmBuscar['lstTramoBusqueda'],
		$frmBuscar['lstCasillaBusqueda'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/iv_ubicacion_historico_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaUbicacion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 40, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art_alm.estatus IS NULL");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != ""
	&& ($valCadBusq[2] == "-1" || $valCadBusq[2] == "")) {
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
				valTpDato($valCadBusq[7], "int"));
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT
			COUNT(art_alm2.id_articulo)
		FROM iv_articulos_almacen art_alm2
			INNER JOIN iv_casillas casilla2 ON (art_alm2.id_casilla = casilla2.id_casilla)
		WHERE art_alm2.id_articulo = art.id_articulo 
			AND art_alm2.estatus IS NULL %s) = 1", $sqlBusqEstatus);
	}
	
	if (($valCadBusq[1] == "-1" || $valCadBusq[1] == "")
	&& $valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
				valTpDato($valCadBusq[7], "int"));
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT
			COUNT(art_alm2.id_articulo)
		FROM iv_articulos_almacen art_alm2
			INNER JOIN iv_casillas casilla2 ON (art_alm2.id_casilla = casilla2.id_casilla)
		WHERE art_alm2.id_articulo = art.id_articulo
			AND art_alm2.estatus IS NULL %s) > 1", $sqlBusqEstatus);
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != ""
	&& ($valCadBusq[4] == "-1" || $valCadBusq[4] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(art_alm2.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm2
		WHERE art_alm2.id_casilla = casilla.id_casilla
			AND art_alm2.estatus = 1) = 0",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if (($valCadBusq[3] == "-1" || $valCadBusq[3] == "")
	&& $valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(art_alm2.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm2
		WHERE art_alm2.id_casilla = casilla.id_casilla
			AND art_alm2.estatus = 1) > 0",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != ""
	&& ($valCadBusq[6] == "-1" || $valCadBusq[6] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) > 0");
	}
	
	if (($valCadBusq[5] == "-1" || $valCadBusq[5] == "")
	&& $valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) <= 0");
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("casilla.estatus = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_almacen = %s",
			valTpDato($valCadBusq[8], "int"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("calle.id_calle = %s",
			valTpDato($valCadBusq[9], "int"));
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estante.id_estante = %s",
			valTpDato($valCadBusq[10], "int"));
	}
	
	if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tramo.id_tramo = %s",
			valTpDato($valCadBusq[11], "int"));
	}
	
	if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("casilla.id_casilla = %s",
			valTpDato($valCadBusq[12], "int"));
	}
	
	if ($valCadBusq[13] != "-1" && $valCadBusq[13] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[13], "text"));
	}
	
	if ($valCadBusq[14] != "-1" && $valCadBusq[14] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s)",
			valTpDato($valCadBusq[14], "int"),
			valTpDato("%".$valCadBusq[14]."%", "text"));
	}
	
	$query = sprintf("SELECT
		alm.id_empresa,
		alm.id_almacen,
		alm.descripcion AS descripcion_almacen,
		alm.estatus,
		calle.id_calle,
		estante.id_estante ,
		tramo.id_tramo,
		casilla.id_casilla,
		CONCAT_WS('-', calle.descripcion_calle, estante.descripcion_estante, tramo.descripcion_tramo, casilla.descripcion_casilla) AS ubicacion,
		casilla.estatus AS estatus_casilla,
		
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		
		(SELECT art_emp.clasificacion
		FROM iv_articulos_empresa art_emp
		WHERE art_emp.id_articulo = art.id_articulo
			AND art_emp.id_empresa = alm.id_empresa) AS clasificacion,
		
		(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) AS cantidad_disponible_logica
	FROM iv_estantes estante
		INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
		INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
		INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
		INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
		INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
		INNER JOIN iv_articulos art ON (art_alm.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "14%", $pageNum, "descripcion_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almacén");
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "10%", $pageNum, "CONCAT(descripcion_almacen, ubicacion)", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicación");
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "51%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "7%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Disponible");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus_casilla']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Ubicación Inactiva\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Ubicación Activa\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$queryCasilla = sprintf("SELECT
			casilla.*,
			
			(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
			WHERE art_alm.id_casilla = casilla.id_casilla
				AND art_alm.estatus = 1) AS cantidad_ocupada
		FROM iv_casillas casilla
		WHERE casilla.id_casilla = %s;",
			valTpDato($row['id_casilla'], "int"));
		$rsCasilla = mysql_query($queryCasilla);
		if (!$rsCasilla) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRowsCasilla = mysql_num_rows($rsCasilla);
		$rowCasilla = mysql_fetch_assoc($rsCasilla);
		
		//$imgEstatusArticuloAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "" : "<span class=\"textoRojoNegrita_10px\">".htmlentities("Relacion Inactiva")."</span>";
		
		$classUbic = "";
		$classUbic = ($rowCasilla['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($rowCasilla['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($rowCasilla['cantidad_ocupada'] > 0) ? "*" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_almacen'])."</td>";
			$htmlTb .= "<td class=\"".$classUbic."\" nowrap=\"nowrap\">";
				$htmlTb .= utf8_encode(str_replace("-[]", "", $row['ubicacion'].$ocupada));
				$htmlTb .= "<br>".$imgEstatusArticuloAlmacen;
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode(substr($row['descripcion'],0,80))."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= ($row['cantidad_disponible_logica'] > 0) ? "<td align=\"right\" class=\"divMsjInfo\">" : (($row['cantidad_disponible_logica'] < 0) ? "<td align=\"right\" class=\"divMsjError\">" : "<td align=\"right\">");
				$htmlTb .= valTpDato(number_format($row['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio");
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
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
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
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
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUbicacion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUbicacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstBusqueda");
$xajax->register(XAJAX_FUNCTION,"exportarUbicacion");
$xajax->register(XAJAX_FUNCTION,"imprimirUbicacion");
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