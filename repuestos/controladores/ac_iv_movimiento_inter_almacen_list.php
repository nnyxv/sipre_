<?php


function buscarMovInterAlmacen($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
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
	
	$objResponse->loadCommands(listaMovInterAlmacen(0, "CONCAT(descripcion_almacen, ubicacion, id_articulo_costo)", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLst($tpLst, $idLstOrigen, $adjLst, $padreId, $nivReg = "", $selId = ""){
	$objResponse = new xajaxResponse();
	
	switch ($tpLst) {
		case "almacenes" : 	$arraySelec = array("lstPadre","lstAlmacen","lstCalle","lstEstante","lstTramo","lstCasilla"); break;
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	if (($posList+1) != count($arraySelec)-1)
		$onChange = "onchange=\"xajax_cargaLst('".$tpLst."', '".$arraySelec[$posList+1]."', '".$adjLst."', this.value, 'null', 'null');\"";
	else if (($posList+1) == count($arraySelec)-1)
		$onChange = "onchange=\"xajax_cargarArticuloUbicacion(this.value);\"";
	
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
					AND calle.estatus = 1
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
					AND estante.estatus = 1
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
					AND tramo.estatus = 1
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
					AND casilla.estatus = 1
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

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargarArticuloUbicacion($idCasilla) {
	$objResponse = new xajaxResponse();
	
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_casilla = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idCasilla, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	if ($totalRowsArticulo > 0) {
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($rowArticulo['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $rowArticulo['foto'];
		
		$html = "<table border=\"1\" class=\"tabla divMsjInfo2\" cellpadding=\"2\" width=\"100%\">";
		$html .= "<tr>";
			$html .= "<td>"."<img src=\"".utf8_encode($imgFoto)."\" height=\"100\"/>"."</td>";
			$html .= "<td valign=\"top\" width=\"100%\">";
				$html .= "<table width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"20%\">".utf8_encode("Código:")."</td>";
					$html .= "<td width=\"80%\">".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Descripción:")."</td>";
					$html .= "<td>".utf8_encode($rowArticulo['descripcion'])."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Disponibilidad:")."</td>";
					$html .= "<td>".utf8_encode($rowArticulo['cantidad_disponible_logica'])."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "</table>";
	} else {
		$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo\" width=\"100%\">";
		$html .= "<tr>";
			$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"/></td>";
			$html .= "<td align=\"center\">".utf8_encode("Ubicación Disponible")."</td>";
		$html .= "</tr>";
		$html .= "</table>";
	}
	
	$objResponse->assign("tdMsj","innerHTML",$html);
	
	return $objResponse;
}

function formDatosVale($frmVale, $frmAlmacen, $frmImportarArchivo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmImportarArchivo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmVale['cbx1'];
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$objResponse->script("
			fila = document.getElementById('trItmVale:".$valor1."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if ($frmAlmacen['hddIdArticulo'] > 0) {
		$frmImportarArchivo = NULL;
		$frmImportarArchivo['hddIdArticulo0'] = $frmAlmacen['hddIdArticulo'];
		$frmImportarArchivo['hddIdCasillaOrigen0'] = $frmAlmacen['hddIdCasilla'];
		$frmImportarArchivo['hddIdArticuloCosto0'] = $frmAlmacen['hddIdArticuloCosto'];
		$frmImportarArchivo['hddIdCasillaDestino0'] = $frmAlmacen['lstCasillaAct'];
		$frmImportarArchivo['txtCantidadTransferir0'] = $frmAlmacen['txtCantidadArt'];
		
		$arrayObj = array(0);
	}
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idArticulo = $frmImportarArchivo['hddIdArticulo'.$valor];
			$idCasilla = $frmImportarArchivo['hddIdCasillaOrigen'.$valor];
			$hddIdArticuloCosto = $frmImportarArchivo['hddIdArticuloCosto'.$valor];
			$idCasillaDestino = $frmImportarArchivo['hddIdCasillaDestino'.$valor];
			$cantTransferir = str_replace(",","",$frmImportarArchivo['txtCantidadTransferir'.$valor]);
			
			if ($idArticulo > 0 && $idCasilla > 0 && $idCasillaDestino > 0) {
				// BUSCA EL ARTICULO DE LA UBICACION A MODIFICAR
				$queryAlmacenOrigen = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
				WHERE vw_iv_art_almacen_costo.id_articulo = %s
					AND vw_iv_art_almacen_costo.id_casilla = %s
					AND vw_iv_art_almacen_costo.id_articulo_costo = %s
					AND (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
						OR (vw_iv_art_almacen_costo.estatus_articulo_almacen IS NULL AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0));",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloCosto, "int"));
				$rsAlmacenOrigen = mysql_query($queryAlmacenOrigen);
				if (!$rsAlmacenOrigen) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowAlmacenOrigen = mysql_fetch_assoc($rsAlmacenOrigen);
				
				// BUSCA LOS DATOS DE LA CASILLA DE DESTINO
				$queryAlmacenDestino = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
					valTpDato($idCasillaDestino, "int"));
				$rsAlmacenDestino = mysql_query($queryAlmacenDestino);
				if (!$rsAlmacenDestino) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowAlmacenDestino = mysql_fetch_assoc($rsAlmacenDestino);
				
				// BUSCA SI HAY ALGUN ARTICULO EN LA UBICACION DE DESTINO
				$queryArticulo = sprintf("SELECT * FROM iv_articulos_almacen art_alm
				WHERE art_alm.id_articulo <> %s
					AND art_alm.id_casilla = %s
					AND art_alm.estatus = 1;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasillaDestino, "int"));
				$rsArticulo = mysql_query($queryArticulo);
				if (!$rsArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsArticulo = mysql_num_rows($rsArticulo);
				$rowArticulo = mysql_fetch_assoc($rsArticulo);
				
				if ($idEmpresaOrigen > 0) {
					if ($idEmpresaOrigen != $rowAlmacenOrigen['id_empresa'] && $rowAlmacenOrigen['id_empresa'] > 0) {
						usleep(0.5 * 1000000);
						$objResponse->alert(utf8_encode("No puede realizar un movimiento de almacen de dos o más empresas simultáneamente"));
						return $objResponse->script("byId('btnCancelarVale').click();");
					}
				} else {
					$idEmpresaOrigen = $rowAlmacenOrigen['id_empresa'];
					
					// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
					$ResultConfig12 = valorConfiguracion(12, $idEmpresaOrigen);
					if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
						return $objResponse->alert($ResultConfig12[1]);
					} else if ($ResultConfig12[0] == true) {
						$ResultConfig12 = $ResultConfig12[1];
					}
				}
				
				if ($idEmpresaDestino > 0) {
					if ($idEmpresaDestino != $rowAlmacenDestino['id_empresa'] && $rowAlmacenDestino['id_empresa'] > 0) {
						usleep(0.5 * 1000000);
						$objResponse->alert(utf8_encode("No puede realizar un movimiento de almacen de dos o más empresas simultáneamente"));
						return $objResponse->script("byId('btnCancelarVale').click();");
					}
				} else {
					$idEmpresaDestino = $rowAlmacenDestino['id_empresa'];
				}
				
				$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
				WHERE vw_iv_art_almacen_costo.id_articulo = %s
					AND vw_iv_art_almacen_costo.id_empresa = %s
					AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
					AND vw_iv_art_almacen_costo.id_casilla = %s
					AND vw_iv_art_almacen_costo.id_articulo_costo = %s
				ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC
				LIMIT 1;",
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresaOrigen, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloCosto, "int"));
				$rsArtCosto = mysql_query($queryArtCosto);
				if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
				
				$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
				
				if ($totalRowsArticulo == 0 && $cantTransferir <= $rowAlmacenOrigen['cantidad_disponible_logica']) {
					if ($idCasillaDestino != $idCasilla) {
						$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila++;
						
						$txtSubTotal += $cantTransferir * $costoUnitario;
						
						// INSERTA EL ARTICULO SIN INJECT
						$objResponse->script(sprintf("$('#trItmPieVale').before('".
							"<tr id=\"trItmVale:%s\" align=\"left\" class=\"%s\">".
								"<td align=\"center\" class=\"textoNegrita_9px\">%s</td>".
								"<td>%s".
									"%s</td>".
								"<td align=\"center\">%s</td>".
								"<td align=\"center\">%s</td>".
								"<td><input type=\"text\" id=\"txtCantidadTransferir%s\" name=\"txtCantidadTransferir%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
								"<td><input type=\"text\" id=\"txtCostoTransferir%s\" name=\"txtCostoTransferir%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
								"<td><input type=\"text\" id=\"txtTotalTransferir%s\" name=\"txtTotalTransferir%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">".
									"<input type=\"checkbox\" id=\"cbx1\" name=\"cbx1[]\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
									"<input type=\"hidden\" id=\"hddIdArticulo%s\" name=\"hddIdArticulo%s\" title=\"Articulo\" value=\"%s\">".
									"<input type=\"hidden\" id=\"hddIdCasillaOrigen%s\" name=\"hddIdCasillaOrigen%s\" title=\"Casilla Origen\" value=\"%s\">".
									"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" title=\"Lote\" value=\"%s\">".
									"<input type=\"hidden\" id=\"hddIdCasillaDestino%s\" name=\"hddIdCasillaDestino%s\" title=\"Casilla Destino\" value=\"%s\"></td>".
							"</tr>');",
							$contFila, $clase,
								$contFila,
								$rowAlmacenOrigen['codigo_articulo'],
									(in_array($ResultConfig12, array(1,2)) ? "" : "<br><span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</span>"),
								$rowAlmacenOrigen['descripcion_almacen']."<br>".utf8_encode(str_replace("-[]", "", $rowAlmacenOrigen['ubicacion'])),
								$rowAlmacenDestino['descripcion_almacen']."<br>".utf8_encode(str_replace("-[]", "", $rowAlmacenDestino['ubicacion'])),
								$contFila, $contFila, number_format($cantTransferir, 2, ".", ","),
								$contFila, $contFila, number_format($costoUnitario, 2, ".", ","),
								$contFila, $contFila, number_format(($cantTransferir * $costoUnitario), 2, ".", ","),
									$contFila,
									$contFila, $contFila, $idArticulo,
									$contFila, $contFila, $idCasilla,
									$contFila, $contFila, $hddIdArticuloCosto,
									$contFila, $contFila, $idCasillaDestino));
					} else if ($idArticulo == $rowArticulo['id_articulo'] && $idCasillaDestino == $idCasilla) {
						$arrayObjIgual[] = $rowAlmacenOrigen['codigo_articulo'];
					} else {
						$arrayObjOcupada[] = $rowAlmacenOrigen['codigo_articulo'];
					}
				} else {
					$arrayObjSuperaDisponible[] = $rowAlmacenOrigen['codigo_articulo'];
				}
			} else if ($idArticulo > 0) {
				if ($idCasillaDestino > 0) {
					$arrayObjOrigenInvalido[] = $frmImportarArchivo['txtCodigoArticulo'.$valor];
				}
				
				if ($idCasilla > 0) {
					$arrayObjDestinoInvalido[] = $frmImportarArchivo['txtCodigoArticulo'.$valor];
				}
			}
		}
	}
	
	// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	$objResponse->assign("hddIdEmpleado","value",$_SESSION['idEmpleadoSysGts']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
	
	$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresaOrigen, "EmpresaValeSalida", "ListaEmpresaValeSalida"));
	$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresaDestino, "EmpresaValeEntrada", "ListaEmpresaValeEntrada"));
	
	$objResponse->script("
	selectedOption('lstTipoMovimientoSalida',4);
	byId('lstTipoMovimientoSalida').onchange = function() {
		selectedOption(this.id,4);
		xajax_cargaLstClaveMovimiento('lstClaveMovimientoSalida', '0', this.value, '', '5,6');
	}
	selectedOption('lstTipoMovimientoEntrada',2);
	byId('lstTipoMovimientoEntrada').onchange = function() {
		selectedOption(this.id,2);
		xajax_cargaLstClaveMovimiento('lstClaveMovimientoEntrada', '0', this.value, '', '5,6');
	}");
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimientoSalida", "0", "4", "", "5,6"));
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimientoEntrada", "0", "2", "", "5,6"));
						
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	
	if (count($arrayObjIgual) > 0) {
		$objResponse->alert(utf8_encode(count($arrayObjIgual)." items está(n) en la misma ubicación:\n\n".implode(", ",$arrayObjIgual)."\n\ndebe seleccionar una distinta"));
	}
	
	if (count($arrayObjOcupada) > 0) {
		$objResponse->alert(utf8_encode("A ".count($arrayObjOcupada)." items le intentó asignar una ubicación ya ocupada por otro artículo:\n\n".implode(", ",$arrayObjOcupada)));
	}
	
	if (count($arrayObjSuperaDisponible) > 0) {
		$objResponse->alert(utf8_encode(count($arrayObjSuperaDisponible)." items supera(n) la cantidad disponible:\n\n".implode(", ",$arrayObjSuperaDisponible)));
	}
	
	if (count($arrayObjOrigenInvalido) > 0) {
		$objResponse->alert(utf8_encode(count($arrayObjOrigenInvalido)." items tiene(n) la casilla de origen inválida:\n\n".implode(", ",$arrayObjOrigenInvalido)));
	}
	
	if (count($arrayObjDestinoInvalido) > 0) {
		$objResponse->alert(utf8_encode(count($arrayObjDestinoInvalido)." items tiene(n) la casilla de destino inválida:\n\n".implode(", ",$arrayObjDestinoInvalido)));
	}
	
	return $objResponse;
}

function formImportarArchivo($frmImportarArchivo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmImportarArchivo['cbx'])) {
		foreach ($frmImportarArchivo['cbx'] as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	return $objResponse;
}

function formMovimientoInterAlmacen($idArticulo, $idCasilla, $idArticuloCosto, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	
	$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico inv_fis
	WHERE inv_fis.id_empresa = %s
		AND inv_fis.estatus = 0",
		valTpDato($idEmpresa , "int"));
	$rsInvFis = mysql_query($queryInvFis);
	if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsInvFis = mysql_num_rows($rsInvFis);
	
	if ($totalRowsInvFis == 0) {
		$queryArticulo = sprintf("SELECT
			art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
		
			(SELECT sec.descripcion
			FROM iv_subsecciones subsec
				INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
			WHERE subsec.id_subseccion = art.id_subseccion) AS descripcion_seccion,
			
			(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
			WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
			
			vw_iv_art_almacen_costo.cantidad_disponible_logica
		FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
			INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo)
		WHERE art.id_articulo = %s
			AND vw_iv_art_almacen_costo.id_casilla = %s
			AND vw_iv_art_almacen_costo.id_articulo_costo = %s
			AND (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
				OR (vw_iv_art_almacen_costo.estatus_articulo_almacen IS NULL AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0));",
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"),
			valTpDato($idArticuloCosto, "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$objResponse->assign("hddIdCasilla","value",$idCasilla);
		$objResponse->assign("hddIdArticulo","value",$idArticulo);
		$objResponse->assign("hddIdArticuloCosto","value",$idArticuloCosto);
		
		$objResponse->assign("txtCodigoArt","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
		$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
		$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
		$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
		$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
		$objResponse->assign("txtCantDisponible","value",$rowArticulo['cantidad_disponible_logica']);
		
		$objResponse->script("byId('txtCantDisponible').className = '".(($rowArticulo['cantidad_disponible_logica'] > 0) ? "inputCantidadDisponible" : "inputCantidadNoDisponible")."'");
		
		$onChange = sprintf("onchange=\"xajax_cargaLst('almacenes', 'lstPadre', 'Act', this.value);\"");
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, $onChange, "lstEmpresaUnidadFisica"));
		$objResponse->loadCommands(cargaLst("almacenes", "lstPadre", "Act", $idEmpresa));
	} else {
		$objResponse->script("
		alert('".utf8_encode("Usted no puede hacer Movimientos Inter-Almacen, debido a que está en Proceso un Inventario Físico")."');");
	}
	
	return $objResponse;
}

function guardarDcto($frmAlmacen, $frmVale, $frmListaMovInterAlmacen) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmVale['cbx1'];
	
	$idEmpresaOrigen = $frmVale['txtIdEmpresaValeSalida'];
	$idEmpresaDestino = $frmVale['txtIdEmpresaValeEntrada'];
	$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idClaveMovimientoSalida = $frmVale['lstClaveMovimientoSalida'];
	$idClaveMovimientoEntrada = $frmVale['lstClaveMovimientoEntrada'];
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresaOrigen);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	mysql_query("START TRANSACTION;");
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////// VALE DE SALIDA /////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimientoSalida, "int"),
		valTpDato($idEmpresaOrigen, "int"),
		valTpDato($idEmpresaOrigen, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// REGISTRA EL VALE DE SALIDA
	$insertSQL = sprintf("INSERT INTO iv_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_documento, id_cliente, subtotal_documento, tipo_vale_salida, observacion, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActual, "int"),
		valTpDato($idEmpresaOrigen, "int"),
		valTpDato(date("Y-m-d"),"date"),
		valTpDato("", "int"),
		valTpDato($frmVale['hddIdEmpleado'], "int"),
		valTpDato($frmVale['txtSubTotal'], "real_inglesa"),
		valTpDato(4, "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
		valTpDato($frmVale['txtObservacion'], "text"),
		valTpDato($frmVale['hddIdEmpleado'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idValeSalida = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idValeSalida,
		$idModulo,
		"SALIDA");
	
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
		valTpDato(4, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimientoSalida, "int"),
		valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idValeSalida, "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($frmVale['hddIdEmpleado'], "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(1, "int")); // 0 = Credito, 1 = Contado
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimientoSalida = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$querySleep = ("SELECT SLEEP(1)");
	$Result1 = mysql_query($querySleep);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////// VALE DE ENTRADA /////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimientoEntrada, "int"),
		valTpDato($idEmpresaDestino, "int"),
		valTpDato($idEmpresaDestino, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// REGISTRA EL VALE DE ENTRADA
	$insertSQL = sprintf("INSERT INTO iv_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_documento, id_cliente, subtotal_documento, tipo_vale_entrada, observacion, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActual, "int"),
		valTpDato($idEmpresaDestino, "int"),
		valTpDato(date("Y-m-d"),"date"),
		valTpDato("", "int"),
		valTpDato($frmVale['hddIdEmpleado'], "int"),
		valTpDato($frmVale['txtSubTotal'], "real_inglesa"),
		valTpDato(4, "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
		valTpDato($frmVale['txtObservacion'], "text"),
		valTpDato($frmVale['hddIdEmpleado'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idValeEntrada = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idValeEntrada,
		$idModulo,
		"ENTRADA");
	
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
		valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimientoEntrada, "int"),
		valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idValeEntrada, "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($frmVale['hddIdEmpleado'], "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(1, "int")); // 0 = Credito, 1 = Contado
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimientoEntrada = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice => $valor) {
			$idArticulo = $frmVale['hddIdArticulo'.$valor];
			$idCasilla = $frmVale['hddIdCasillaOrigen'.$valor];
			$hddIdArticuloCosto = $frmVale['hddIdArticuloCosto'.$valor];
			$idCasillaDestino = $frmVale['hddIdCasillaDestino'.$valor];
			$cantTransferir = str_replace(",","",$frmVale['txtCantidadTransferir'.$valor]);
			
			// BUSCA EL ARTICULO DE LA UBICACION A MODIFICAR
			$queryAlmacenOrigen = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
			WHERE vw_iv_art_almacen_costo.id_articulo = %s
				AND vw_iv_art_almacen_costo.id_casilla = %s
				AND vw_iv_art_almacen_costo.id_articulo_costo = %s
				AND (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
					OR (vw_iv_art_almacen_costo.estatus_articulo_almacen IS NULL AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0));",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($hddIdArticuloCosto, "int"));
			$rsAlmacenOrigen = mysql_query($queryAlmacenOrigen);
			if (!$rsAlmacenOrigen) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowAlmacenOrigen = mysql_fetch_assoc($rsAlmacenOrigen);
			
			// BUSCA SI HAY ALGUN ARTICULO EN LA UBICACION DE DESTINO
			$queryArticulo = sprintf("SELECT * FROM iv_articulos_almacen art_alm
			WHERE art_alm.id_articulo <> %s
				AND art_alm.id_casilla = %s
				AND art_alm.estatus = 1;",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasillaDestino, "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArticulo = mysql_num_rows($rsArticulo);
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
			
			if ($cantTransferir <= $rowAlmacenOrigen['cantidad_disponible_logica']) {
				if ($totalRowsArticulo == 0 && $idCasillaDestino != $idCasilla) {
					// BUSCA EL LOTE EN LA EMPRESA ORIGEN
					$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
					WHERE vw_iv_art_almacen_costo.id_articulo = %s
						AND vw_iv_art_almacen_costo.id_empresa = %s
						AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
						AND vw_iv_art_almacen_costo.id_casilla = %s
						AND vw_iv_art_almacen_costo.id_articulo_costo = %s
					ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC
					LIMIT 1;",
						valTpDato($idArticulo, "int"),
						valTpDato($idEmpresaOrigen, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($hddIdArticuloCosto, "int"));
					$rsArtCosto = mysql_query($queryArtCosto);
					if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
					$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
					
					$costoUnitario = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
					
					//////////////////////////////////////////////////////////////////////////////////////////////////////////
					///////////////////////////////////////////// VALE DE SALIDA /////////////////////////////////////////////
					//////////////////////////////////////////////////////////////////////////////////////////////////////////
					if ($idValeSalida > 0) {
						// REGISTRA EL DETALLE DEL VALE DE SALIDA
						$insertSQL = sprintf("INSERT INTO iv_vale_salida_detalle (id_vale_salida, id_articulo, id_casilla, id_articulo_costo, cantidad, precio_venta, costo_compra)
						VALUE (%s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idValeSalida, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idCasilla, "int"),
							valTpDato($hddIdArticuloCosto, "int"),
							valTpDato($cantTransferir, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idValeSalidaDetalle = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						
						// VERIFICA SI EL LOTE TIENE LA UBICACION ASIGNADA
						$queryArtAlmCosto = sprintf("SELECT *
						FROM iv_articulos_almacen art_almacen
							INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
						WHERE art_almacen.id_articulo = %s
							AND art_almacen.id_casilla = %s
							AND art_almacen_costo.id_articulo_costo = %s;",
							valTpDato($idArticulo, "int"),
							valTpDato($idCasilla, "int"),
							valTpDato($hddIdArticuloCosto, "int"));
						$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
						if (!$rsArtAlmCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtAlm = mysql_num_rows($rsArtAlmCosto);
						$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
						
						$hddIdArticuloAlmacenCosto = $rowArtAlmCosto['id_articulo_almacen_costo'];
						
						// ACTUALIZA EN EL DETALLE DEL VALE LA UBICACION DEL LOTE
						$updateSQL = sprintf("UPDATE iv_vale_salida_detalle SET
							id_articulo_almacen_costo = %s,
							id_articulo_costo = %s
						WHERE id_vale_salida_detalle = %s;",
							valTpDato($hddIdArticuloAlmacenCosto, "int"),
							valTpDato($hddIdArticuloCosto, "int"),
							valTpDato($idValeSalidaDetalle, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						if ($totalRowsArtAlm > 0) {
							// ACTUALIZA EL ESTATUS DE LA UBICACION DEL LOTE
							$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
								estatus = 1
							WHERE id_articulo_almacen_costo = %s;",
								valTpDato($hddIdArticuloAlmacenCosto, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						} else {
							// LE ASIGNA EL LOTE A LA UBICACION
							$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
							SELECT art_almacen.id_articulo_almacen, %s, 1 FROM iv_articulos_almacen art_almacen
							WHERE art_almacen.id_casilla = %s
								AND art_almacen.id_articulo = %s
								AND art_almacen.estatus = 1;",
									valTpDato($hddIdArticuloCosto, "int"),
									valTpDato($idCasilla, "int"),
									valTpDato($idArticulo, "int"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$hddIdArticuloAlmacenCosto = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");
						}
						
						// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
						$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idModulo, "int"),
							valTpDato($idValeSalida, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idCasilla, "int"),
							valTpDato($hddIdArticuloAlmacenCosto, "int"),
							valTpDato($hddIdArticuloCosto, "int"),
							valTpDato(4, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
							valTpDato($idClaveMovimientoSalida, "int"),
							valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
							valTpDato($cantTransferir, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
							valTpDato("NOW()", "campo"),
							valTpDato($frmVale['txtObservacion'], "text"),
							valTpDato("SYSDATE()", "campo"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idKardex = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						
						// INSERTA EL DETALLE DEL MOVIMIENTO
						$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idMovimientoSalida, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idKardex, "int"),
							valTpDato($hddIdArticuloAlmacenCosto, "int"),
							valTpDato($hddIdArticuloCosto, "int"),
							valTpDato($cantTransferir, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(((0 * $costoUnitario) / 100), "real_inglesa"),
							valTpDato(0, "int"), // 0 = Unitario, 1 = Import
							valTpDato(0, "boolean"), // 0 = No, 1 = Si
							valTpDato("", "int"),
							valTpDato("", "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idMovimientoDetalle = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
					}
					
					///////////////////////////////////////////////////////////////////////////////////////////////////////////
					///////////////////////////////////////////// VALE DE ENTRADA /////////////////////////////////////////////
					///////////////////////////////////////////////////////////////////////////////////////////////////////////
					if ($idValeEntrada > 0) {
						// REGISTRA EL DETALLE DEL VALE DE ENTRADA
						$insertSQL = sprintf("INSERT INTO iv_vale_entrada_detalle (id_vale_entrada, id_articulo, id_casilla, id_articulo_costo, cantidad, precio_venta, costo_compra)
						VALUE (%s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idValeEntrada, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idCasillaDestino, "int"),
							valTpDato($hddIdArticuloCosto, "int"),
							valTpDato($cantTransferir, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idValeEntradaDetalle = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						
						// VERIFICA SI EL ARTICULO ESTA LIGADO A LA EMPRESA
						$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
						WHERE id_empresa = %s
							AND id_articulo = %s;",
							valTpDato($idEmpresaDestino, "int"),
							valTpDato($idArticulo, "int"));
						$rsArtEmp = mysql_query($queryArtEmp);
						if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
					
						if ($totalRowsArtEmp > 0) { // SI EXISTE EL ARTICULO PARA LA EMRPESA
							if ($idArticuloSust > 0) { // SI SE AGREGO UN ARTICULO SUSTITUTO
								// SE LE QUITA LA UBICACION PREDETERMINADA AL ARTICULO QUE FUE SUSTITUIDO
								$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
									id_casilla_predeterminada = NULL,
									id_casilla_predeterminada_compra = NULL,
									sustituido = 1,
									estatus = NULL
								WHERE id_articulo = %s
									AND id_empresa = %s;",
									valTpDato($idArticuloOrg, "int"),
									valTpDato($idEmpresaDestino, "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							}
						} else { // SI NO EXISTE EL ARTICULO PARA LA EMPRESA
							if ($idArticuloSust > 0) { // SI SE AGREGO UN ARTICULO SUSTITUTO
								$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
									id_casilla_predeterminada = NULL,
									id_casilla_predeterminada_compra = NULL,
									sustituido = 1,
									estatus = NULL
								WHERE id_articulo = %s
									AND id_empresa = %s;",
									valTpDato($idArticuloOrg, "int"),
									valTpDato($idEmpresaDestino, "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
								
								// SE LIGA EL ARTICULO SUSTITUTO CON LA EMPRESA
								$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, id_casilla_predeterminada, id_casilla_predeterminada_compra, clasificacion, estatus)
								VALUE (%s, %s, %s, %s, %s, %s);",
									valTpDato($idEmpresaDestino, "int"),
									valTpDato($idArticulo, "int"),
									valTpDato($idCasillaDestino, "int"),
									valTpDato($idCasillaDestino, "int"),
									valTpDato("F", "text"),
									valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							} else {
								// COMO EL ARTICULO NO ESTA LIGADO CON LA EMPRESA, SE LIGARA
								$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, clasificacion, estatus)
								VALUE (%s, %s, %s, %s);",
									valTpDato($idEmpresaDestino, "int"),
									valTpDato($idArticulo, "int"),
									valTpDato("F", "text"),
									valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							}
						}
					
					
						// VERIFICA SI HAY RELACION ENTRE ARTICULO Y LA UBICACION SELECCIONADA
						$queryArtAlmacen = sprintf("SELECT * FROM iv_articulos_almacen art_almacen
						WHERE art_almacen.id_articulo = %s
							AND art_almacen.id_casilla = %s;",
							valTpDato($idArticulo, "int"),
							valTpDato($idCasillaDestino, "int"));
						$rsArtAlmacen = mysql_query($queryArtAlmacen);
						if (!$rsArtAlmacen) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtAlmacen = mysql_num_rows($rsArtAlmacen);
						$rowArtAlmacen = mysql_fetch_assoc($rsArtAlmacen);
						if ($totalRowsArtAlmacen > 0) {
							// ACTIVA LA UBICACION SELECCIONADA EN EL REGISTRO DE COMPRA
							$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
								estatus = 1
							WHERE id_articulo = %s
								AND id_casilla = %s;",
								valTpDato($idArticulo, "int"),
								valTpDato($idCasillaDestino, "int"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
						} else {
							 // SI EL ARTICULO NO TIENE UBICACION, SE LE ASIGNA LA SELECCIONADA
							if ($idArticuloSust > 0) {
								// DESACTIVA LA UBICACION Y PONE EL ESTATUS SUSTITUIDO
								$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
									sustituido = 1,
									estatus = NULL
								WHERE id_articulo = %s
									AND id_casilla = %s;",
									valTpDato($idArticuloOrg, "int"),
									valTpDato($idCasillaDestino, "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
								
								// LE AGREGA LA UBICACION AL ARTICULO SUSTITUTO
								$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
								VALUE (%s, %s, %s);",
									valTpDato($idCasillaDestino, "int"),
									valTpDato($idArticulo, "int"),
									valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							} else {
								$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
								VALUE (%s, %s, %s);",
									valTpDato($idCasillaDestino, "int"),
									valTpDato($idArticulo, "int"),
									valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nCod Art: ".$rowArt['codigo_articulo']); }
								mysql_query("SET NAMES 'latin1';");
							}
						}
					
					
						// VERIFICA SI EL ARTICULO TIENE UNA UBICACION PREDETERMINADA EN UN ALMACEN DE LA EMPRESA
						$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
						WHERE id_empresa = %s
							AND id_articulo = %s;",
							valTpDato($idEmpresaDestino, "int"),
							valTpDato($idArticulo, "int"));
						$rsArtEmp = mysql_query($queryArtEmp);
						if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
					
						// SI LA CASILLA SELECCIONADA ES DISTINTA A LA CASILLA PREDETERMINADA Y NO TIENE CASILLA PREDETERMINADA LE ASIGNA LA SELECCIONADA EN EL REGISTRO DE COMPRA
						$idCasillaPredetVenta = ($idCasillaDestino != $rowArtEmp['id_casilla_predeterminada'] && $rowArtEmp['id_casilla_predeterminada'] == "") ? $idCasillaDestino : $rowArtEmp['id_casilla_predeterminada'];
						$idCasillaPredetCompra = ($idCasillaDestino != $rowArtEmp['id_casilla_predeterminada_compra'] && $rowArtEmp['id_casilla_predeterminada_compra'] == "") ? $idCasillaDestino : $rowArtEmp['id_casilla_predeterminada_compra'];
						
						// MODIFICA LA CASILLA PREDETERMINADA DEL ARTICULO EN LA EMPRESA
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
							id_casilla_predeterminada = %s,
							id_casilla_predeterminada_compra = %s,
							cantidad_pedida = 0,
							estatus = 1
						WHERE id_articulo_empresa = %s;",
							valTpDato($idCasillaPredetVenta, "int"),
							valTpDato($idCasillaPredetCompra, "int"),
							valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					
						// VERIFICACION PARA SABER SI LA CASILLA PREDETERMINADA ES VÁLIDA
						$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen
						WHERE id_articulo = %s
							AND id_casilla = %s
							AND estatus = 1;",
							valTpDato($idArticulo, "int"),
							valTpDato($idCasillaPredetCompra, "int"));
						$rsArtAlm = mysql_query($queryArtAlm);
						if (!$rsArtAlm) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
						$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
						if (!($totalRowsArtAlm > 0)) {
							// BUSCA LA PRIMERA UBICACION ACTIVA DEL ARTICULO PARA PONERSELA COMO PREDETERMINADA
							$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
								id_casilla_predeterminada = (SELECT art_alm.id_casilla
															FROM iv_almacenes almacen
																INNER JOIN iv_calles calle ON (almacen.id_almacen = calle.id_almacen)
																INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
																INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
																INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
																INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
															WHERE almacen.id_empresa = art_emp.id_empresa
																AND art_alm.id_articulo = art_emp.id_articulo
																AND art_alm.estatus = 1
															LIMIT 1),
								id_casilla_predeterminada_compra = (SELECT art_alm.id_casilla
															FROM iv_almacenes almacen
																INNER JOIN iv_calles calle ON (almacen.id_almacen = calle.id_almacen)
																INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
																INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
																INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
																INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
															WHERE almacen.id_empresa = art_emp.id_empresa
																AND art_alm.id_articulo = art_emp.id_articulo
																AND art_alm.estatus = 1
															LIMIT 1)
							WHERE art_emp.id_empresa = %s
								AND art_emp.id_articulo = %s;",
								valTpDato($idEmpresaDestino, "int"),
								valTpDato($idArticulo, "int"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nId: ".$idCasillaDestino."\nCasilla: ".$rowCasillaError['ubicacion']); }
							mysql_query("SET NAMES 'latin1';");
						}
						
						
						if ($idEmpresaOrigen != $idEmpresaDestino) {
							$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
							WHERE art_costo.id_articulo = %s
								AND art_costo.id_empresa = %s
								AND art_costo.id_articulo_costo = %s;",
								valTpDato($idArticulo, "int"),
								valTpDato($idEmpresaDestino, "int"),
								valTpDato($hddIdArticuloCosto, "int"));
							$rsArtCosto = mysql_query($queryArtCosto);
							if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
							$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
							
							// VERIFICA SI EXISTE UN LOTE CON LAS CARACTERISTICAS PARA LA EMPRESA DESTINO
							$queryArtCosto = sprintf("SELECT * FROM iv_articulos_costos art_costo
							WHERE art_costo.id_empresa = %s
								AND art_costo.id_proveedor = %s
								AND art_costo.id_articulo = %s
								AND art_costo.fecha = %s
								AND art_costo.costo = %s
								AND art_costo.costo_promedio = %s
								AND art_costo.precio_justo = %s
								AND art_costo.id_moneda = %s
								AND art_costo.fecha_registro = %s;",
								valTpDato($idEmpresaDestino, "int"),
								valTpDato($rowArtCosto['id_proveedor'], "int"),
								valTpDato($rowArtCosto['id_articulo'], "int"),
								valTpDato($rowArtCosto['fecha'], "date"),
								valTpDato($rowArtCosto['costo'], "real_inglesa"),
								valTpDato($rowArtCosto['costo_promedio'], "real_inglesa"),
								valTpDato($rowArtCosto['precio_justo'], "real_inglesa"),
								valTpDato($rowArtCosto['id_moneda'], "int"),
								valTpDato($rowArtCosto['fecha_registro'], "date"));
							$rsArtCosto = mysql_query($queryArtCosto);
							if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
							$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
							
							if ($totalRowsArtCosto > 0) {
								// ACTUALIZA EL ESTATUS DEL LOTE
								$updateSQL = sprintf("UPDATE iv_articulos_costos SET
									estatus = 1
								WHERE id_articulo_costo = %s;",
									valTpDato($rowArtCosto['id_articulo_costo'], "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							} else {
								// REGISTRA EL COSTO DE COMPRA DEL ARTICULO
								$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, precio_justo, id_moneda, costo_origen, id_moneda_origen, fecha_registro)
								SELECT %s, id_proveedor, id_articulo, NOW(), costo, costo_promedio, precio_justo, id_moneda, costo_origen, id_moneda_origen, NOW() FROM iv_articulos_costos art_costo
								WHERE art_costo.id_articulo = %s
									AND art_costo.id_empresa = %s
									AND art_costo.id_articulo_costo = %s
								ORDER BY fecha_registro DESC LIMIT 1;",
									valTpDato($idEmpresaDestino, "int"),
									valTpDato($idArticulo, "int"),
									valTpDato($idEmpresaOrigen, "int"),
									valTpDato($hddIdArticuloCosto, "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								$hddIdArticuloCosto = mysql_insert_id();
								mysql_query("SET NAMES 'latin1';");
							}
						}
					
						// VERIFICA SI EL LOTE TIENE LA UBICACION ASIGNADA
						$queryArtAlmCosto = sprintf("SELECT *
						FROM iv_articulos_almacen art_almacen
							INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
						WHERE art_almacen.id_articulo = %s
							AND art_almacen.id_casilla = %s
							AND art_almacen_costo.id_articulo_costo = %s;",
							valTpDato($idArticulo, "int"),
							valTpDato($idCasillaDestino, "int"),
							valTpDato($hddIdArticuloCosto, "int"));
						$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
						if (!$rsArtAlmCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtAlm = mysql_num_rows($rsArtAlmCosto);
						$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
						
						$hddIdArticuloAlmacenCosto = $rowArtAlmCosto['id_articulo_almacen_costo'];
						
						// ACTUALIZA EN EL DETALLE DEL VALE LA UBICACION DEL LOTE
						$updateSQL = sprintf("UPDATE iv_vale_entrada_detalle SET
							id_articulo_almacen_costo = %s,
							id_articulo_costo = %s
						WHERE id_vale_entrada_detalle = %s;",
							valTpDato($hddIdArticuloAlmacenCosto, "int"),
							valTpDato($hddIdArticuloCosto, "int"),
							valTpDato($idValeEntradaDetalle, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
						if ($totalRowsArtAlm > 0) {
							// ACTUALIZA EL ESTATUS DE LA UBICACION DEL LOTE
							$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
								estatus = 1
							WHERE id_articulo_almacen_costo = %s;",
								valTpDato($hddIdArticuloAlmacenCosto, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						} else {
							// LE ASIGNA EL LOTE A LA UBICACION
							$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
							SELECT art_almacen.id_articulo_almacen, %s, 1 FROM iv_articulos_almacen art_almacen
							WHERE art_almacen.id_casilla = %s
								AND art_almacen.id_articulo = %s
								AND art_almacen.estatus = 1;",
									valTpDato($hddIdArticuloCosto, "int"),
									valTpDato($idCasillaDestino, "int"),
									valTpDato($idArticulo, "int"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$hddIdArticuloAlmacenCosto = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");
						}
						
						// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
						$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idModulo, "int"),
							valTpDato($idValeEntrada, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idCasillaDestino, "int"),
							valTpDato($hddIdArticuloAlmacenCosto, "int"),
							valTpDato($hddIdArticuloCosto, "int"),
							valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
							valTpDato($idClaveMovimientoEntrada, "int"),
							valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
							valTpDato($cantTransferir, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
							valTpDato("NOW()", "campo"),
							valTpDato($frmVale['txtObservacion'], "text"),
							valTpDato("SYSDATE()", "campo"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idKardex = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						
						// INSERTA EL DETALLE DEL MOVIMIENTO
						$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idMovimientoEntrada, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idKardex, "int"),
							valTpDato($hddIdArticuloAlmacenCosto, "int"),
							valTpDato($hddIdArticuloCosto, "int"),
							valTpDato($cantTransferir, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(0, "real_inglesa"),
							valTpDato(((0 * $costoUnitario) / 100), "real_inglesa"),
							valTpDato(0, "int"), // 0 = Unitario, 1 = Import
							valTpDato(0, "boolean"), // 0 = No, 1 = Si
							valTpDato("", "int"),
							valTpDato("", "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idMovimientoDetalle = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						
						// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
						$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresaOrigen);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						
						// ACTUALIZA EL LOTE DEL COSTO
						$Result1 = actualizarLote($idArticulo, $idEmpresaDestino);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						
						// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
						$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresaDestino);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						
						// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
						$Result1 = actualizarSaldos($idArticulo, $idCasilla, $idCasillaDestino);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						
						// ACTUALIZA EL COSTO PROMEDIO
						$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresaDestino);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
						
						// ACTUALIZA EL PRECIO DE VENTA
						$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresaDestino);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					}
				} else if ($idCasillaDestino == $idCasilla) {
					return $objResponse->alert(utf8_encode("Está en la misma ubicación, debe seleccionar una distinta"));
				} else {
					return $objResponse->alert(utf8_encode("La ubicación ya se encuentra ocupada por otro Artículo"));
				}
			} else {
				return $objResponse->alert(utf8_encode("La cantidad del Código ".$rowAlmacenOrigen['codigo_articulo']." supera a la disponible.\n"."Unid. Disponible: ".$rowAlmacenOrigen['cantidad_disponible_logica']."\nTransferir: ".$cantTransferir));
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Transferencia de ubicación guardada"));
	
	$objResponse->script("
	byId('btnCancelarVale').click();
	byId('btnCancelarAlmacen').click();");
	
	$objResponse->loadCommands(listaMovInterAlmacen(
		$frmListaMovInterAlmacen['pageNum'],
		$frmListaMovInterAlmacen['campOrd'],
		$frmListaMovInterAlmacen['tpOrd'],
		$frmListaMovInterAlmacen['valBusq']));
	
	$objResponse->script("verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=".$idValeSalida."|4', 960, 550);");
		
	$objResponse->script("verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=".$idValeEntrada."|2', 960, 550);");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "ENTRADA") {
				$idVale = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeEntradaRe")) { generarValeEntradaRe($idVale,"",""); } break;
					case 2 : if (function_exists("generarValeEntradaVe")) { generarValeEntradaVe($idVale,"",""); } break;
				}
			} else if ($tipoDcto == "SALIDA") {
				$idVale = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeSalidaRe")) { generarValeSalidaRe($idVale,"",""); } break;
					case 1 : if (function_exists("generarValeSe")) { generarValeSe($idVale,"",""); } break;
					case 2 : if (function_exists("generarValeSalidaVe")) { generarValeSalidaVe($idVale,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	return $objResponse;
}

function listaMovInterAlmacen($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
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
		FROM iv_estantes estante2
			INNER JOIN iv_calles calle2 ON (estante2.id_calle = calle2.id_calle)
			INNER JOIN iv_almacenes alm2 ON (calle2.id_almacen = alm2.id_almacen)
			INNER JOIN iv_tramos tramo2 ON (estante2.id_estante = tramo2.id_estante)
			INNER JOIN iv_casillas casilla2 ON (tramo2.id_tramo = casilla2.id_tramo)
			LEFT JOIN iv_articulos_almacen art_alm2 ON (art_alm2.id_casilla = casilla2.id_casilla)
			LEFT JOIN iv_articulos art2 ON (art_alm2.id_articulo = art2.id_articulo)
		WHERE art_alm2.id_articulo = (SELECT iv_articulos.id_articulo
							FROM iv_articulos
								INNER JOIN iv_articulos_almacen ON (iv_articulos.id_articulo = iv_articulos_almacen.id_articulo)
							WHERE iv_articulos_almacen.id_casilla = vw_iv_art_almacen_costo.id_casilla
								AND iv_articulos_almacen.estatus = 1)
			AND ((art_alm2.estatus = 1 AND art_alm2.id_articulo IS NOT NULL)
				OR (art_alm2.estatus IS NULL AND art_alm2.id_articulo IS NOT NULL AND (cantidad_entrada - cantidad_salida - cantidad_reservada) > 0))
			%s) = 1", $sqlBusqEstatus);
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
		FROM iv_estantes estante2
			INNER JOIN iv_calles calle2 ON (estante2.id_calle = calle2.id_calle)
			INNER JOIN iv_almacenes alm2 ON (calle2.id_almacen = alm2.id_almacen)
			INNER JOIN iv_tramos tramo2 ON (estante2.id_estante = tramo2.id_estante)
			INNER JOIN iv_casillas casilla2 ON (tramo2.id_tramo = casilla2.id_tramo)
			LEFT JOIN iv_articulos_almacen art_alm2 ON (art_alm2.id_casilla = casilla2.id_casilla)
			LEFT JOIN iv_articulos art2 ON (art_alm2.id_articulo = art2.id_articulo)
		WHERE art_alm2.id_articulo = (SELECT iv_articulos.id_articulo
							FROM iv_articulos
								INNER JOIN iv_articulos_almacen ON (iv_articulos.id_articulo = iv_articulos_almacen.id_articulo)
							WHERE iv_articulos_almacen.id_casilla = vw_iv_art_almacen_costo.id_casilla
								AND iv_articulos_almacen.estatus = 1)
			AND ((art_alm2.estatus = 1 AND art_alm2.id_articulo IS NOT NULL)
				OR (art_alm2.estatus IS NULL AND art_alm2.id_articulo IS NOT NULL AND (cantidad_entrada - cantidad_salida - cantidad_reservada) > 0))
			%s) > 1", $sqlBusqEstatus);
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != ""
	&& ($valCadBusq[6] == "-1" || $valCadBusq[6] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT (art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) AS cantidad_disponible_logica
		FROM iv_articulos art
			INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
		WHERE art_alm.id_casilla = vw_iv_art_almacen_costo.id_casilla
			AND art_alm.estatus = 1) > 0");
	}
	
	if (($valCadBusq[5] == "-1" || $valCadBusq[5] == "")
	&& $valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT (art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera - art_alm.cantidad_bloqueada) AS cantidad_disponible_logica
		FROM iv_articulos art
			INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
		WHERE art_alm.id_casilla = vw_iv_art_almacen_costo.id_casilla
			AND art_alm.estatus = 1) <= 0");
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.estatus_casilla = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_almacen = %s",
			valTpDato($valCadBusq[8], "int"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_calle = %s",
			valTpDato($valCadBusq[9], "int"));
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_estante = %s",
			valTpDato($valCadBusq[10], "int"));
	}
	
	if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tramo = %s",
			valTpDato($valCadBusq[11], "int"));
	}
	
	if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_casilla = %s",
			valTpDato($valCadBusq[12], "int"));
	}
	
	if ($valCadBusq[13] != "-1" && $valCadBusq[13] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[13], "text"));
	}
	
	if ($valCadBusq[14] != "-1" && $valCadBusq[14] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.id_articulo = %s
		OR vw_iv_art_almacen_costo.id_articulo_costo LIKE %s
		OR vw_iv_art_almacen_costo.descripcion LIKE %s
		OR vw_iv_art_almacen_costo.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[14], "int"),
			valTpDato("%".$valCadBusq[14]."%", "text"),
			valTpDato("%".$valCadBusq[14]."%", "text"),
			valTpDato("%".$valCadBusq[14]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_iv_art_almacen_costo.*,
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
			WHEN 1 THEN	vw_iv_art_almacen_costo.costo
			WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
			WHEN 3 THEN	vw_iv_art_almacen_costo.costo
		END) AS costo
	FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaMovInterAlmacen", "10%", $pageNum, "CONCAT(descripcion_almacen, ubicacion)", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Ubicación"));
		$htmlTh .= ordenarCampo("xajax_listaMovInterAlmacen", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaMovInterAlmacen", "50%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaMovInterAlmacen", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaMovInterAlmacen", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Lote");
		$htmlTh .= ordenarCampo("xajax_listaMovInterAlmacen", "8%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= ordenarCampo("xajax_listaMovInterAlmacen", "8%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unid. Disponible"));
		$htmlTh .= "<td class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus_casilla']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_gris.gif\" title=\"".utf8_encode("Ubicación Inactiva")."\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"".utf8_encode("Ubicación Activa")."\"/>"; break;
			default : $imgEstatus = "";
		}
		
		// VERIFICA SI ALGUN ARTICULO TIENE LA UBICACION OCUPADA
		$queryCasilla = sprintf("SELECT
			casilla.*,
			
			(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
			WHERE art_alm.id_casilla = casilla.id_casilla
				AND art_alm.estatus = 1) AS cantidad_ocupada
		FROM iv_casillas casilla
		WHERE casilla.id_casilla = %s;",
			valTpDato($row['id_casilla'], "int"));
		$rsCasilla = mysql_query($queryCasilla);
		if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsCasilla = mysql_num_rows($rsCasilla);
		$rowCasilla = mysql_fetch_assoc($rsCasilla);
		
		$classUbic = "";
		//$classUbic = ($rowCasilla['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($rowCasilla['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		//$ocupada = ($rowCasilla['cantidad_ocupada'] > 0) ? "*" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".$classUbic."\" nowrap=\"nowrap\">";
				$htmlTb .= utf8_encode($row['descripcion_almacen'])."<br><span class=\"textoNegrita_10px\">".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</span>";
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
			$htmlTb .= "<td align=\"right\">".number_format($row['costo'], 2, ".", ",")."</td>";
			$htmlTb .= ($row['cantidad_disponible_logica'] > 0) ? "<td align=\"right\" class=\"divMsjInfo\">" : (($row['cantidad_disponible_logica'] < 0) ? "<td align=\"right\" class=\"divMsjError\">" : "<td align=\"right\">");
				$htmlTb .= valTpDato(number_format($row['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio");
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cantidad_disponible_logica'] > 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aTransferencia%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAlmacen', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_movimiento_almacen.gif\" title=\"%s\"/></a>",
					$contFila,
					$row['id_articulo'],
					$row['id_casilla'],
					$row['id_articulo_costo'],
					utf8_encode("Transferencia de Almacén"));
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[8] += $row['cantidad_disponible_logica'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"7\">".utf8_encode("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
			$htmlTb .= "<td></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[8] += $row['cantidad_disponible_logica'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"7\">".utf8_encode("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovInterAlmacen(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0){
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovInterAlmacen(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMovInterAlmacen(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages){
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovInterAlmacen(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages){
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovInterAlmacen(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMovInterAlmacen","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function vistaPreviaImportar($frmImportarArchivo) {
	$objResponse = new xajaxResponse();
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$frmImportarArchivo['hddUrlArchivo'];
	
	$phpExcel = new PHPExcel_Reader_Excel2007();
	$archivoExcel = $phpExcel->load($inputFileName);
	
	$archivoExcel->setActiveSheetIndex(0);
	$i = 1;
	while ($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue() != '') {
		if ($itemExcel == true) {
			$arrayFilaImportar[] = array(
				$archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(), // Código
				$archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(), // Id Empresa
				$archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue(), // Almacén
				$archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue(), // Ubicación
				$archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue(), // Cantidad
				$archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue(), // Id Empresa Destino
				$archivoExcel->getActiveSheet()->getCell('G'.$i)->getValue(), // Almacén Destino
				$archivoExcel->getActiveSheet()->getCell('H'.$i)->getValue()); // Ubicación Destino
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Codigo"))) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	if (isset($arrayFilaImportar)) {
		foreach ($arrayFilaImportar as $indiceFila => $valor) {
			$contFila++;
			
			$codigoArticulo = trim($arrayFilaImportar[$indiceFila][0]);
			$idEmpresaOrigen = trim($arrayFilaImportar[$indiceFila][1]);
			$almacenOrigen = trim($arrayFilaImportar[$indiceFila][2]);
			$ubicacionOrigen = trim($arrayFilaImportar[$indiceFila][3]);
			$cantTransferir = trim($arrayFilaImportar[$indiceFila][4]);
			$idEmpresaDestino = trim($arrayFilaImportar[$indiceFila][5]);
			$almacenDestino = trim($arrayFilaImportar[$indiceFila][6]);
			$ubicacionDestino = trim($arrayFilaImportar[$indiceFila][7]);
			
			// BUSCA SI EXISTE EL CODIGO DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM iv_articulos
			WHERE codigo_articulo LIKE %s;",
				valTpDato($codigoArticulo, "text"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArticulo = mysql_num_rows($rsArticulo);
			$rowArticulo = mysql_fetch_array($rsArticulo);
			
			$idArticulo = $rowArticulo['id_articulo'];
			
			// BUSCA LOS DATOS DE LA EMPRESA
			$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
				valTpDato($idEmpresaOrigen, "int"));
			$rsEmp = mysql_query($queryEmp);
			if (!$rsEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsEmp = mysql_num_rows($rsEmp);
			$rowEmp = mysql_fetch_assoc($rsEmp);
			
			$nombreEmpresaOrigen = $rowEmp['nombre_empresa'];
			
			// BUSCA LOS DATOS DE LA UBICACION
			$queryUbic = sprintf("SELECT * FROM vw_iv_casillas
			WHERE id_empresa = %s
				AND descripcion_almacen LIKE %s
				AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', '');",
				valTpDato($idEmpresaOrigen, "text"),
				valTpDato($almacenOrigen, "text"),
				valTpDato($ubicacionOrigen, "text"));
			$rsUbic = mysql_query($queryUbic);
			if (!$rsUbic) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsUbic = mysql_num_rows($rsUbic);
			$rowUbic = mysql_fetch_assoc($rsUbic);
			
			$idCasilla = $rowUbic['id_casilla'];
			
			// BUSCA LOS DATOS DE LA EMPRESA
			$queryEmpDestino = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
				valTpDato($idEmpresaDestino, "int"));
			$rsEmpDestino = mysql_query($queryEmpDestino);
			if (!$rsEmpDestino) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsEmpDestino = mysql_num_rows($rsEmpDestino);
			$rowEmpDestino = mysql_fetch_assoc($rsEmpDestino);
			
			$nombreEmpresaDestino = $rowEmpDestino['nombre_empresa'];
			
			// BUSCA LOS DATOS DE LA UBICACION
			$queryUbicDestino = sprintf("SELECT * FROM vw_iv_casillas
			WHERE id_empresa = %s
				AND descripcion_almacen LIKE %s
				AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', '');",
				valTpDato($idEmpresaDestino, "text"),
				valTpDato($almacenDestino, "text"),
				valTpDato($ubicacionDestino, "text"));
			$rsUbicDestino = mysql_query($queryUbicDestino);
			if (!$rsUbicDestino) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsUbicDestino = mysql_num_rows($rsUbicDestino);
			$rowUbicDestino = mysql_fetch_assoc($rsUbicDestino);
			
			$idCasillaDestino = $rowUbicDestino['id_casilla'];
			
			
			$claseArticulo = ($totalRowsArticulo > 0 && $idArticulo > 0) ? "" : "divMsjError";
			$claseEmpresaOrigen = ($totalRowsEmp > 0 && $idEmpresaOrigen > 0) ? "" : "divMsjError";
			$claseUbicacionOrigen = ($totalRowsUbic > 0 && $idCasilla > 0) ? "" : "divMsjError";
			$claseEmpresaDestino = ($totalRowsEmpDestino > 0 && $idEmpresaDestino > 0) ? "" : "divMsjError";
			$claseUbicacionDestino = ($totalRowsUbicDestino > 0 && $idCasillaDestino > 0) ? "" : "divMsjError";
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("$('#trItmPie').before('".
				"<tr id=\"trItm:%s\" align=\"left\" class=\"textoNegrita_8px %s\">".
					"<td align=\"center\">%s</td>".
					"<td class=\"%s\"><input type=\"text\" id=\"txtCodigoArticulo%s\" name=\"txtCodigoArticulo%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"></td>".
					"<td class=\"%s\">%s</td>".
					"<td class=\"%s\">%s</td>".
					"<td class=\"%s\">%s</td>".
					"<td><input type=\"text\" id=\"txtCantidadTransferir%s\" name=\"txtCantidadTransferir%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
					"<td class=\"%s\">%s</td>".
					"<td class=\"%s\">%s</td>".
					"<td class=\"%s\">%s".
						"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdArticulo%s\" name=\"hddIdArticulo%s\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdCasillaOrigen%s\" name=\"hddIdCasillaOrigen%s\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdCasillaDestino%s\" name=\"hddIdCasillaDestino%s\" value=\"%s\"></td>".
				"</tr>');",
				$contFila, $clase,
					$contFila,
					$claseArticulo, $contFila, $contFila, $codigoArticulo,
					$claseEmpresaOrigen, $idEmpresaOrigen.") ".$nombreEmpresaOrigen,
					$claseUbicacionOrigen, $almacenOrigen,
					$claseUbicacionOrigen, $ubicacionOrigen,
					$contFila, $contFila, number_format($cantTransferir, 2, ".", ","),
					$claseEmpresaDestino, $idEmpresaDestino.") ".$nombreEmpresaDestino,
					$claseUbicacionDestino, $almacenDestino,
					$claseUbicacionDestino, $ubicacionDestino,
						$contFila,
						$contFila, $contFila, $idArticulo,
						$contFila, $contFila, $idCasilla,
						$contFila, $contFila, $idCasillaDestino));
		}
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarMovInterAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLst");
$xajax->register(XAJAX_FUNCTION,"cargaLstBusqueda");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargarArticuloUbicacion");
$xajax->register(XAJAX_FUNCTION,"formDatosVale");
$xajax->register(XAJAX_FUNCTION,"formImportarArchivo");
$xajax->register(XAJAX_FUNCTION,"formMovimientoInterAlmacen");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"listaMovInterAlmacen");
$xajax->register(XAJAX_FUNCTION,"vistaPreviaImportar");

function buscarEnArray($arrays, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($arrays as $indice=>$valor) {
		if ($valor == $dato)
			return $x;
		
		$x++;
	}
	return null;
}
?>