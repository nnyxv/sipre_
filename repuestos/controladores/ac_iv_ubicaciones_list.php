<?php


function buscarUbicacion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		implode(",",$frmBuscar['lstVerArticuloUbic']),
		implode(",",$frmBuscar['lstVerUbic']),
		$frmBuscar['lstEstatus'],
		$frmBuscar['lstAlmacenBusqueda'],
		$frmBuscar['lstCalleBusqueda'],
		$frmBuscar['lstEstanteBusqueda'],
		$frmBuscar['lstTramoBusqueda'],
		$frmBuscar['lstCasillaBusqueda'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUbicacion(0, "CONCAT(descripcion_almacen, ubicacion, IFNULL(query.id_articulo_costo, 0))", "ASC", $valBusq));
	
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
					alm.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
						INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
						INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
					WHERE calle.id_almacen = alm.id_almacen
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_almacenes alm
				WHERE (alm.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = alm.id_empresa))
					AND alm.estatus = 1
				ORDER BY alm.descripcion;",
					valTpDato($padreId, "int"),
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

function exportarUbicacion($frmBuscar){
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		implode(",",$frmBuscar['lstVerArticuloUbic']),
		implode(",",$frmBuscar['lstVerUbic']),
		$frmBuscar['lstEstatus'],
		$frmBuscar['lstAlmacenBusqueda'],
		$frmBuscar['lstCalleBusqueda'],
		$frmBuscar['lstEstanteBusqueda'],
		$frmBuscar['lstTramoBusqueda'],
		$frmBuscar['lstCasillaBusqueda'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_ubicaciones_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formImportarAlmacen($nomObjeto, $frmImportarArchivo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmImportarArchivo['cbx'])) {
		foreach ($frmImportarArchivo['cbx'] as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if (xvalidaAcceso($objResponse,"iv_articulo_list","insertar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmImportarArchivo'].reset();
		byId('hddUrlArchivo').value = '';
		
		byId('fleUrlArchivo').className = 'inputHabilitado';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Importar Artículo y su Ubicación");
		
		$objResponse->script("
		byId('fleUrlArchivo').focus();
		byId('fleUrlArchivo').select();");
	}
	
	return $objResponse;
}

function importarAlmacen($frmImportarArchivo, $frmListaUbicacion) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmImportarArchivo['cbx'])) {
		mysql_query("START TRANSACTION;");
		
		foreach ($frmImportarArchivo['cbx'] as $indiceItm => $valorItm) {
			$idArticulo = $frmImportarArchivo['hddIdArticulo'.$valorItm];
			$idCasilla = $frmImportarArchivo['hddIdCasilla'.$valorItm];
			
			// BUSCA LOS DATOS DE LA CASILLA
			$queryCasilla = sprintf("SELECT * FROM vw_iv_casillas WHERE vw_iv_casillas.id_casilla = %s;",
				valTpDato($idCasilla, "int"));
			$rsCasilla = mysql_query($queryCasilla);
			if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowCasilla = mysql_fetch_assoc($rsCasilla);
			
			$idEmpresa = $rowCasilla['id_empresa'];
			
			// VERIFICA SI ALGUN ARTICULO TIENE LA UBICACION OCUPADA
			$queryArticuloAlmacen = sprintf("SELECT * FROM iv_articulos_almacen art_alm
			WHERE art_alm.id_casilla = %s
				AND art_alm.estatus = 1;",
				valTpDato($idCasilla, "int"));
			$rsArticuloAlmacen = mysql_query($queryArticuloAlmacen);
			if (!$rsArticuloAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArticuloAlmacen = mysql_num_rows($rsArticuloAlmacen);
			$rowArticuloAlmacen = mysql_fetch_assoc($rsArticuloAlmacen);
			
			if ($idArticulo > 0 && $idCasilla > 0) {
				if ($totalRowsArticuloAlmacen == 0) {
					// VERIFICA SI EL ARTICULO TENIA LA UBICACION ANTERIORMENTE
					$queryArticuloAlmacen = sprintf("SELECT * FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = %s
						AND art_alm.id_articulo = %s
						AND art_alm.estatus IS NULL;",
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"));
					$rsArticuloAlmacen = mysql_query($queryArticuloAlmacen);
					if (!$rsArticuloAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsArticuloAlmacen = mysql_num_rows($rsArticuloAlmacen);
					$rowArticuloAlmacen = mysql_fetch_assoc($rsArticuloAlmacen);
					
					if ($totalRowsArticuloAlmacen == 0) {
						$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
						VALUE (%s, %s, %s);",
							valTpDato($idCasilla, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato(1, "boolean")); // NULL = Inactivo, 1 = Activo
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$idArticuloAlmacen = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
					} else {
						$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
							estatus = %s
						WHERE id_articulo_almacen = %s;",
							valTpDato(1, "int"), // NULL = Inactivo, 1 = Activo
							valTpDato($rowArticuloAlmacen['id_articulo_almacen'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
					}
					
					// VERIFICA SI LA EMPRESA TIENE ASIGNADO EL ARTICULO
					$queryArticuloEmpresa = sprintf("SELECT * FROM iv_articulos_empresa art_emp
					WHERE art_emp.id_empresa = %s
						AND art_emp.id_articulo = %s;",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"));
					$rsArticuloEmpresa = mysql_query($queryArticuloEmpresa);
					if (!$rsArticuloEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsArticuloEmpresa = mysql_num_rows($rsArticuloEmpresa);
					$rowArticuloEmpresa = mysql_fetch_assoc($rsArticuloEmpresa);
					
					if ($totalRowsArticuloEmpresa == 0) {
						$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, estatus)
						VALUE (%s, %s, %s);",
							valTpDato($idEmpresa, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato(1, "boolean")); // NULL = Inactivo, 1 = Activo
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$idArticuloAlmacen = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
					} else {
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
							estatus = %s
						WHERE id_articulo_empresa = %s;",
							valTpDato(1, "int"), // 0 = Inactivo, 1= Activo
							valTpDato($rowArticuloEmpresa['id_articulo_empresa'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
					}
					
					// VERIFICA SI EL ARTICULO TIENE CASILLA PREDETERMINADA DE VENTA
					$queryArticuloEmpresa = sprintf("SELECT *
					FROM iv_articulos_empresa art_emp
						INNER JOIN iv_articulos_almacen art_alm ON (art_emp.id_casilla_predeterminada = art_alm.id_casilla)
							AND (art_emp.id_articulo = art_alm.id_articulo)
					WHERE art_alm.estatus = 1
						AND art_emp.id_articulo = %s
						AND art_emp.id_empresa = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idEmpresa, "int"));
					$rsArticuloEmpresa = mysql_query($queryArticuloEmpresa);
					if (!$rsArticuloEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsArticuloEmpresa = mysql_num_rows($rsArticuloEmpresa);
					$rowArticuloEmpresa = mysql_fetch_assoc($rsArticuloEmpresa);
					
					if ($totalRowsArticuloEmpresa == 0) {
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET 
							id_casilla_predeterminada = %s
						WHERE id_articulo = %s
							AND id_empresa = %s;",
							valTpDato($idCasilla, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
					}
					
					// VERIFICA SI EL ARTICULO TIENE CASILLA PREDETERMINADA DE COMPRA
					$queryArticuloEmpresa = sprintf("SELECT *
					FROM iv_articulos_empresa art_emp
						INNER JOIN iv_articulos_almacen art_alm ON (art_emp.id_casilla_predeterminada_compra = art_alm.id_casilla)
							AND (art_emp.id_articulo = art_alm.id_articulo)
					WHERE art_alm.estatus = 1
						AND art_emp.id_articulo = %s
						AND art_emp.id_empresa = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idEmpresa, "int"));
					$rsArticuloEmpresa = mysql_query($queryArticuloEmpresa);
					if (!$rsArticuloEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsArticuloEmpresa = mysql_num_rows($rsArticuloEmpresa);
					$rowArticuloEmpresa = mysql_fetch_assoc($rsArticuloEmpresa);
					
					if ($totalRowsArticuloEmpresa == 0) {
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET 
							id_casilla_predeterminada_compra = %s
						WHERE id_articulo = %s
							AND id_empresa = %s;",
							valTpDato($idCasilla, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idEmpresa, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
					}
					
					// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
					$Result1 = actualizarPedidas($idArticulo);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				} else {
					$arrayObjOcupada[] = $rowCasilla['descripcion_almacen']." ".str_replace("-[]", "", $rowCasilla['ubicacion']);
				}
			}
		}
		
		if (count($arrayObjOcupada) > 0) {
			$objResponse->alert(("Ya se encuentra(n) ocupadas(s) ".count($arrayObjOcupada)." ubicaciones por otro(s) Artículo(s): ".implode(", ",$arrayObjOcupada)));
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Relación Artículo y Ubicación Guardado con Éxito");
		
		$objResponse->script("
		byId('btnCancelarImportarArchivo').click();");
		
		$objResponse->loadCommands(listaUbicacion(
			$frmListaUbicacion['pageNum'],
			$frmListaUbicacion['campOrd'],
			$frmListaUbicacion['tpOrd'],
			$frmListaUbicacion['valBusq']));
	} else {
		errorImportarDcto($objResponse);
		$objResponse->alert("Verifique el contenido del Archivo");
	}
	
	return $objResponse;
}

function imprimirUbicacion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		implode(",",$frmBuscar['lstVerArticuloUbic']),
		implode(",",$frmBuscar['lstVerUbic']),
		$frmBuscar['lstEstatus'],
		$frmBuscar['lstAlmacenBusqueda'],
		$frmBuscar['lstCalleBusqueda'],
		$frmBuscar['lstEstanteBusqueda'],
		$frmBuscar['lstTramoBusqueda'],
		$frmBuscar['lstCasillaBusqueda'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/iv_ubicaciones_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaUbicacion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 40, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if (in_array(1,explode(",",$valCadBusq[1]))) {
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
				valTpDato($valCadBusq[3], "int"));
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
							WHERE iv_articulos_almacen.id_casilla = casilla.id_casilla
								AND iv_articulos_almacen.estatus = 1)
			AND ((art_alm2.estatus = 1 AND art_alm2.id_articulo IS NOT NULL)
				OR (art_alm2.estatus IS NULL AND art_alm2.id_articulo IS NOT NULL AND (cantidad_entrada - cantidad_salida - cantidad_reservada) > 0))
			%s) = 1", $sqlBusqEstatus);
	}
	
	if (in_array(2,explode(",",$valCadBusq[1]))) {
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
				valTpDato($valCadBusq[3], "int"));
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
							WHERE iv_articulos_almacen.id_casilla = casilla.id_casilla
								AND iv_articulos_almacen.estatus = 1)
			AND ((art_alm2.estatus = 1 AND art_alm2.id_articulo IS NOT NULL)
				OR (art_alm2.estatus IS NULL AND art_alm2.id_articulo IS NOT NULL AND (cantidad_entrada - cantidad_salida - cantidad_reservada) > 0))
			%s) > 1", $sqlBusqEstatus);
	}
	
	if (in_array(1,explode(",",$valCadBusq[2]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT art.id_articulo
		FROM iv_articulos art
			INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) IS NULL",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if (in_array(2,explode(",",$valCadBusq[2]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT art.id_articulo
		FROM iv_articulos art
			INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) IS NOT NULL",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if (in_array(3,explode(",",$valCadBusq[2]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT
			(IFNULL(art_alm.cantidad_inicio, 0) + IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_disponible_logica
		FROM iv_articulos art
			INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) > 0");
	}
	
	if (in_array(4,explode(",",$valCadBusq[2]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT (IFNULL(art_alm.cantidad_inicio, 0) + IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_disponible_logica
		FROM iv_articulos art
			INNER JOIN iv_articulos_almacen art_alm ON (art.id_articulo = art_alm.id_articulo)
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) <= 0");
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("casilla.estatus = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_almacen = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("calle.id_calle = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estante.id_estante = %s",
			valTpDato($valCadBusq[6], "int"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tramo.id_tramo = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("casilla.id_casilla = %s",
			valTpDato($valCadBusq[8], "int"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("query.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[9], "text"));
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(query.id_articulo = %s
		OR query.id_articulo_costo LIKE %s
		OR query.descripcion LIKE %s)",
			valTpDato($valCadBusq[10], "int"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"));
	}
	
	$query = sprintf("SELECT
		alm.id_empresa,
		alm.id_almacen,
		alm.descripcion AS descripcion_almacen,
		alm.estatus,
		calle.id_calle,
		estante.id_estante,
		tramo.id_tramo,
		casilla.id_casilla,
		CONCAT_WS('-', calle.descripcion_calle, estante.descripcion_estante, tramo.descripcion_tramo, casilla.descripcion_casilla) AS ubicacion,
		casilla.estatus AS estatus_casilla,
		query.id_articulo,
		query.codigo_articulo,
		query.descripcion,
		(SELECT art_emp.clasificacion FROM iv_articulos_empresa art_emp
		WHERE art_emp.id_articulo = query.id_articulo
			AND art_emp.id_empresa = alm.id_empresa) AS clasificacion,
		query.estatus_articulo_almacen,
		query.id_articulo_almacen_costo,
		query.id_articulo_costo,
		query.cantidad_disponible_logica,
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = alm.id_empresa)
			WHEN 1 THEN	query.costo
			WHEN 2 THEN	query.costo_promedio
			WHEN 3 THEN	query.costo
		END) AS costo_unitario,
		query.abreviacion_moneda_local AS abreviacion_moneda_local
	FROM iv_almacenes alm
		INNER JOIN iv_calles calle ON (alm.id_almacen = calle.id_almacen)
		INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
		INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
		INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
		LEFT JOIN (SELECT 
				art_almacen.id_articulo_almacen,
				art_almacen.id_casilla,
				art_almacen.id_articulo,
				art.codigo_articulo,
				art.descripcion,
				art_almacen.sustituido,
				art_almacen.estatus AS estatus_articulo_almacen,
				art_almacen_costo.id_articulo_almacen_costo,
				art_almacen_costo.id_articulo_costo,
				IFNULL(art_almacen_costo.cantidad_reservada, 0) AS cantidad_reservada,
				IFNULL(art_almacen_costo.cantidad_inicio, 0) + IFNULL(art_almacen_costo.cantidad_entrada, 0) - IFNULL(art_almacen_costo.cantidad_salida, 0) - IFNULL(art_almacen_costo.cantidad_reservada, 0) - IFNULL(art_almacen_costo.cantidad_espera, 0) - IFNULL(art_almacen_costo.cantidad_bloqueada, 0) AS cantidad_disponible_logica,
				art_costo.costo,
				art_costo.costo_promedio,
				art_costo.estatus AS estatus_articulo_costo,
				moneda_local.abreviacion AS abreviacion_moneda_local,
				moneda_origen.abreviacion AS abreviacion_moneda_origen
			FROM iv_articulos_almacen art_almacen
				INNER JOIN iv_articulos art ON (art_almacen.id_articulo = art.id_articulo)
				LEFT JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
				LEFT JOIN iv_articulos_costos art_costo ON (art_almacen_costo.id_articulo_costo = art_costo.id_articulo_costo)
				LEFT JOIN pg_monedas moneda_local ON (art_costo.id_moneda = moneda_local.idmoneda)
				LEFT JOIN pg_monedas moneda_origen ON (art_costo.id_moneda_origen = moneda_origen.idmoneda)) AS query ON (casilla.id_casilla = query.id_casilla)
					AND ((query.estatus_articulo_almacen = 1 AND query.estatus_articulo_costo = 1)
						OR (query.estatus_articulo_almacen IS NULL AND query.cantidad_disponible_logica > 0)) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "10%", $pageNum, "CONCAT(descripcion_almacen, ubicacion, IFNULL(query.id_articulo_costo, 0))", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ubicación"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "46%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Lote"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "8%", $pageNum, "costo_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, ("Costo"));
		$htmlTh .= ordenarCampo("xajax_listaUbicacion", "8%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, ("Unid. Disponible"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus_casilla']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Ubicación Inactiva\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Ubicación Activa\"/>"; break;
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
		
		$classUbic = ($rowCasilla['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($rowCasilla['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		
		$classDisponible = ($row['cantidad_disponible_logica'] > 0 && $row['estatus'] == 1) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		$classDisponible = ($row['cantidad_disponible_logica'] != "") ? $classDisponible : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".($contFila + (($pageNum) * $maxRows))."</td>"; // <----
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"center\" ".$classUbic." nowrap=\"nowrap\" title=\"".$row['id_casilla']."\">";
				$htmlTb .= utf8_encode(strtoupper($row['descripcion_almacen']))."<br>";
				$htmlTb .= "<span class=\"textoNegrita_10px\">".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</span>";
				$htmlTb .= ($row['estatus_articulo_almacen'] != 1 && $row['id_articulo'] > 0) ? "<br><span class=\"textoRojoNegrita_10px\">".utf8_encode("Relacion Inactiva")."</span>" : "";
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
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['costo_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">";
				$htmlTb .= ($row['cantidad_disponible_logica'] != "") ? number_format($row['cantidad_disponible_logica'], 2, ".", ",") : "";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[9] += $row['cantidad_disponible_logica'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[9] += $row['cantidad_disponible_logica'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[9], 2, ".", ",")."</td>";
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
		$htmlTb .= "<td colspan=\"9\">";
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
			$arrayAlmacenDetalle[0] = $archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue();
			$arrayAlmacenDetalle[1] = $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue();
			$arrayAlmacenDetalle[2] = $archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue();
		
			$arrayAlmacen[] = $arrayAlmacenDetalle;
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(htmlentities($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Codigo"))) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	if (isset($arrayAlmacen)) {
		$idEmpresa = $frmImportarArchivo['txtIdEmpresaImportarArchivo'];
			
		foreach ($arrayAlmacen as $indice => $valor) {
			$contFila++;
			
			$codigoArticulo = $arrayAlmacen[$indice][0];
			$almacen = $arrayAlmacen[$indice][1];
			$ubicacion = $arrayAlmacen[$indice][2];
			
			// BUSCA SI EXISTE EL CODIGO DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM iv_articulos
			WHERE codigo_articulo LIKE %s;",
				valTpDato($codigoArticulo, "text"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArticulo = mysql_num_rows($rsArticulo);
			$rowArticulo = mysql_fetch_array($rsArticulo);
			
			$idArticulo = $rowArticulo['id_articulo'];
			
			// BUSCA LOS DATOS DE LA UBICACION
			$queryUbic = sprintf("SELECT * FROM vw_iv_casillas
			WHERE descripcion_almacen LIKE %s
				AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', '');",
				valTpDato($almacen, "text"),
				valTpDato($ubicacion, "text"));
			$rsUbic = mysql_query($queryUbic);
			if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsUbic = mysql_num_rows($rsUbic);
			$rowUbic = mysql_fetch_assoc($rsUbic);
			
			$idCasilla = $rowUbic['id_casilla'];
			
			$claseArticulo = ($totalRowsArticulo > 0 && $idArticulo > 0) ? "" : "divMsjError";
			$claseCasilla = ($totalRowsUbic > 0 && $idCasilla > 0) ? "" : "divMsjError";
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("$('#trItmPie').before('".
				"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
					"<td align=\"right\">%s</td>".
					"<td class=\"%s\">%s</td>".
					"<td>%s</td>".
					"<td class=\"%s\">%s".
						"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdArticulo%s\" name=\"hddIdArticulo%s\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" value=\"%s\"></td>".
				"</tr>');",
				$contFila, $clase,
					$contFila,
					$claseArticulo, $codigoArticulo,
					$almacen,
					$claseCasilla, $ubicacion,
						$contFila,
						$contFila, $contFila, $idArticulo,
						$contFila, $contFila, $idCasilla));
		}
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUbicacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstBusqueda");
$xajax->register(XAJAX_FUNCTION,"exportarUbicacion");
$xajax->register(XAJAX_FUNCTION,"formImportarAlmacen");
$xajax->register(XAJAX_FUNCTION,"importarAlmacen");
$xajax->register(XAJAX_FUNCTION,"imprimirUbicacion");
$xajax->register(XAJAX_FUNCTION,"listaUbicacion");
$xajax->register(XAJAX_FUNCTION,"vistaPreviaImportar");


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