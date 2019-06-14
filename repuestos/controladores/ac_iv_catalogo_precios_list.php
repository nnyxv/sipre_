<?php


function buscarArticulo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstAplicaIva']) ? implode(",",$frmBuscar['lstAplicaIva']) : $frmBuscar['lstAplicaIva']),
		(is_array($frmBuscar['lstTipoArticulo']) ? implode(",",$frmBuscar['lstTipoArticulo']) : $frmBuscar['lstTipoArticulo']),
		(is_array($frmBuscar['lstVerClasificacion']) ? implode(",",$frmBuscar['lstVerClasificacion']) : $frmBuscar['lstVerClasificacion']),
		(is_array($frmBuscar['lstSaldos']) ? implode(",",$frmBuscar['lstSaldos']) : $frmBuscar['lstSaldos']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCatalogoPrecio(0, "art.codigo_articulo", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda']) {
			$selected = "selected=\"selected\"";
		} else if ($row['predeterminada'] == 1 && $selId == "") {
			$selected = "selected=\"selected\"";
		}
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".utf8_encode($row['descripcion']." (".$row['abreviacion'].")")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarCatalogo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstAplicaIva']) ? implode(",",$frmBuscar['lstAplicaIva']) : $frmBuscar['lstAplicaIva']),
		(is_array($frmBuscar['lstTipoArticulo']) ? implode(",",$frmBuscar['lstTipoArticulo']) : $frmBuscar['lstTipoArticulo']),
		(is_array($frmBuscar['lstVerClasificacion']) ? implode(",",$frmBuscar['lstVerClasificacion']) : $frmBuscar['lstVerClasificacion']),
		(is_array($frmBuscar['lstSaldos']) ? implode(",",$frmBuscar['lstSaldos']) : $frmBuscar['lstSaldos']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_catalogo_precios_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formArticuloPrecio($idArticuloCosto, $idArticuloPrecio, $idArticulo, $idPrecio){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_catalogo_precios_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarArticuloPrecio').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM iv_articulos art
	WHERE art.id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DEL PRECIO
	$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.id_precio = %s;",
		valTpDato($idPrecio, "int"));
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPrecio = mysql_num_rows($rsPrecio);
	$rowPrecio = mysql_fetch_assoc($rsPrecio);
	
	// BUSCA LOS DATOS DEL PRECIO DEL ARTICULO
	$query = sprintf("SELECT *
	FROM iv_articulos_precios art_precio
		LEFT JOIN iv_articulos_costos art_costo ON (art_precio.id_articulo_costo = art_costo.id_articulo_costo)
	WHERE art_precio.id_articulo_precio = %s;",
		valTpDato($idArticuloPrecio, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdArticuloPrecio","value",$row['id_articulo_precio']);
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	$objResponse->assign("hddIdArticuloCosto","value",$idArticuloCosto);
	$objResponse->assign("hddIdPrecio","value",$idPrecio);
	$objResponse->assign("txtDescripcion","value",$rowPrecio['descripcion_precio']);
	$objResponse->assign("txtPrecioArt","value",number_format($row['precio'], 2, ".", ","));
	$objResponse->loadCommands(cargaLstMoneda($row['id_moneda']));
	
	if ($row['id_moneda'] > 0) {
		$objResponse->script("
		byId('lstMoneda').onchange = function () {
			selectedOption(this.id,".$row['id_moneda'].");
		}");
	}
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML",utf8_encode("Editar Precio (Artículo Código: ".elimCaracter($rowArticulo['codigo_articulo'],";")." - ".$rowArticulo['descripcion']).") "."Lote: ".$idArticuloCosto);
	
	return $objResponse;
}

function formImportarPrecio($frmImportarArchivo) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","insertar")) { $objResponse->script("byId('btnCancelarImportarArticulo').click();"); return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmImportarArchivo['cbx'];
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "EmpresaImportarArchivo", "ListaEmpresa"));
	
	return $objResponse;
}

function guardarArticuloPrecio($frmArticuloPrecio, $frmBuscar, $frmListaArticulos){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$idArticuloPrecio = $frmArticuloPrecio['hddIdArticuloPrecio'];
	
	if ($idArticuloPrecio > 0) {
		if (!xvalidaAcceso($objResponse,"iv_catalogo_precios_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE iv_articulos_precios SET
			precio = %s,
			id_moneda = %s
		WHERE id_articulo_precio = %s;",
			valTpDato($frmArticuloPrecio['txtPrecioArt'], "real_inglesa"),
			valTpDato($frmArticuloPrecio['lstMoneda'], "int"),
			valTpDato($idArticuloPrecio, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_catalogo_precios_list","editar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO iv_articulos_precios (id_empresa, id_articulo, id_articulo_costo, id_precio, precio, id_moneda)
		VALUE (%s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($frmArticuloPrecio['hddIdArticulo'], "int"),
			valTpDato($frmArticuloPrecio['hddIdArticuloCosto'], "int"),
			valTpDato($frmArticuloPrecio['hddIdPrecio'], "int"),
			valTpDato($frmArticuloPrecio['txtPrecioArt'], "real_inglesa"),
			valTpDato($frmArticuloPrecio['lstMoneda'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idArticuloPrecio = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Precio de Venta Guardado con Éxito"));
	
	$objResponse->script("
	byId('btnCancelarArticuloPrecio').click();");
	
	$objResponse->loadCommands(listaCatalogoPrecio(
		$frmListaArticulos['pageNum'],
		$frmListaArticulos['campOrd'],
		$frmListaArticulos['tpOrd'],
		$frmListaArticulos['valBusq']));
	
	return $objResponse;
}

function importarPrecio($frmImportarArchivo, $frmListaArticulos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmImportarArchivo['cbx'];
	
	if (!xvalidaAcceso($objResponse,"iv_catalogo_precios_list","editar")) { return $objResponse; }
	
	$idEmpresa = $frmImportarArchivo['txtIdEmpresaImportarArchivo'];
	
	if (isset($arrayObj)) {
		mysql_query("START TRANSACTION;");
		
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$idArticulo = $frmImportarArchivo['hddIdArticuloItm'.$valorItm];
			$idPrecio = $frmImportarArchivo['hddIdPrecioItm'.$valorItm];
			$idMoneda = $frmImportarArchivo['hddIdMonedaItm'.$valorItm];
			$precioUnitario = str_replace(",", "", $frmImportarArchivo['hddPrecioItm'.$valorItm]);
			
			if ($idArticulo > 0 && $idPrecio > 0) {
				// BUSCA LOS DATOS DE LA CASILLA
				$queryArticuloPrecio = sprintf("SELECT * FROM iv_articulos_precios
				WHERE id_empresa = %s
					AND id_articulo = %s
					AND id_precio = %s
					AND id_moneda = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idPrecio, "int"),
					valTpDato($idMoneda, "int"));
				$rsArticuloPrecio = mysql_query($queryArticuloPrecio);
				if (!$rsArticuloPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsArticuloPrecio = mysql_num_rows($rsArticuloPrecio);
				$rowArticuloPrecio = mysql_fetch_assoc($rsArticuloPrecio);
				
				if ($totalRowsArticuloPrecio > 0) {
					$updateSQL = sprintf("UPDATE iv_articulos_precios SET
						precio = %s
					WHERE id_articulo_precio = %s;",
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($rowArticuloPrecio['id_articulo_precio'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idArticuloPrecio = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				} else {
					$insertSQL = sprintf("INSERT INTO iv_articulos_precios (id_empresa, id_articulo, id_precio, precio, id_moneda)
					VALUE (%s, %s, %s, %s, %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idPrecio, "int"),
						valTpDato($precioUnitario, "real_inglesa"),
						valTpDato($idMoneda, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
					$idArticuloPrecio = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert(utf8_encode("Registro guardado con éxito"));
		
		$objResponse->script("
		byId('btnCancelarImportarArchivo').click();");
		
		$objResponse->loadCommands(listaCatalogoPrecio(
			$frmListaArticulos['pageNum'],
			$frmListaArticulos['campOrd'],
			$frmListaArticulos['tpOrd'],
			$frmListaArticulos['valBusq']));
	} else {
		$objResponse->alert("Verifique el contenido del Archivo");
	}
	
	return $objResponse;
}

function listaCatalogoPrecio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("precio.porcentaje NOT IN (0) AND precio.estatus IN (1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
		
	$queryPrecio = sprintf("SELECT *
	FROM pg_empresa_precios emp_precio
		INNER JOIN pg_precios precio ON (emp_precio.id_precio = precio.id_precio) %s
	ORDER BY precio.id_precio ASC;", $sqlBusq);
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPrecio = mysql_num_rows($rsPrecio);
	
	$sqlBusq = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.posee_iva = %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.clasificacion LIKE %s",
			valTpDato($valCadBusq[3], "text"));
	}
	
	if (in_array(1,explode(",",$valCadBusq[4]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica > 0");
	}
	
	if (in_array(2,explode(",",$valCadBusq[4]))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica <= 0");
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[5], "text"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s
		OR (SELECT COUNT(art_costo.id_articulo_costo) FROM iv_articulos_costos art_costo
			WHERE art_costo.id_articulo = vw_iv_art_emp.id_articulo
				AND art_costo.id_empresa = vw_iv_art_emp.id_empresa
				AND art_costo.id_articulo_costo LIKE %s) > 0)",
			valTpDato($valCadBusq[6], "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_iv_art_emp.*,
		art.posee_iva
	FROM vw_iv_articulos_empresa vw_iv_art_emp
		INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitArticulo = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimitArticulo);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCatalogoPrecio", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaCatalogoPrecio", "20%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaCatalogoPrecio", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaCatalogoPrecio", "6%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unid. Disponible"));
		$htmlTh .= "<td width=\"6%\">".htmlentities("Lote")."</td>";
		$htmlTh .= "<td width=\"8%\">".htmlentities("Unid. Disponible")."</td>";
		while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
			$htmlTh .= "<td width=\"".(42 / $totalRowsPrecio)."%\">".utf8_encode($rowPrecio['descripcion_precio'])."</td>";
			$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
		}
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$idEmpresa = $row['id_empresa'];
		$idArticulo = $row['id_articulo'];
		
		$queryArtCosto = sprintf("SELECT
			vw_iv_art_almacen_costo.id_articulo_costo,
			SUM(cantidad_disponible_logica) AS cantidad_disponible_logica
		FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		WHERE vw_iv_art_almacen_costo.id_articulo = %s
			AND vw_iv_art_almacen_costo.id_empresa = %s
			AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
			AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0
		GROUP BY vw_iv_art_almacen_costo.id_articulo_costo
		ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", 
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
		
		$rowspan = ($totalRowsArtCosto > 0) ? "rowspan=\"".$totalRowsArtCosto."\"" : "";
		
		$imgAplicaIva = ($row['posee_iva'] == 1) ? "<img src=\"../img/iconos/accept.png\" title=\"Si Aplica Impuesto\"/>" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgAplicaIva."</td>";
			$htmlTb .= "<td ".$rowspan.">".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td ".$rowspan.">".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\" ".$rowspan.">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".htmlentities("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".htmlentities("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".htmlentities("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".htmlentities("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".htmlentities("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".htmlentities("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".(($row['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"")." ".$rowspan.">";
				$htmlTb .= number_format($row['cantidad_disponible_logica'], 2, ".", ",");
			$htmlTb .= "</td>";
			if ($arrayIdPrecio) {
				$contFila2 = 0;
				if ($totalRowsArtCosto > 0) {
					while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
						$contFila2++;
						
						$classDisponible = ($rowArtCosto['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
						//$classDisponible = ($rowArtCosto['estatus_almacen_venta'] == 1) ? $classDisponible : "class=\"divMsjInfo4\"";
						
						$htmlTb .= "<td align=\"right\">".utf8_encode($rowArtCosto['id_articulo_costo'])."</td>";
						$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($rowArtCosto['cantidad_disponible_logica'], 2, ".", ",")."</td>";
						
						$contFila3 = 0;
						foreach ($arrayIdPrecio as $indice => $valor) {
							$style = (fmod($contFila3, 2) == 0) ? "" : "style=\"font-weight:bold\"";
							$contFila3++;
							
							$queryPrecio = sprintf("SELECT
								art_precio.id_articulo_precio,
								art_precio.id_precio,
								art_precio.precio AS precio_unitario,
								
								(SELECT iva.observacion
								FROM iv_articulos_impuesto art_impsto
									INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
								WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
									AND art_impsto.id_articulo = art_precio.id_articulo
								LIMIT 1) AS descripcion_impuesto,
								
								(SELECT SUM(iva.iva)
								FROM iv_articulos_impuesto art_impsto
									INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
								WHERE iva.tipo IN (6,9,2)
									AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
								
								(art_precio.precio * (SELECT SUM(iva.iva)
													FROM iv_articulos_impuesto art_impsto
														INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
													WHERE iva.tipo IN (6,9,2)
														AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
								
								moneda.abreviacion AS abreviacion_moneda
							FROM iv_articulos_precios art_precio
								INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
							WHERE art_precio.id_articulo = %s
								AND art_precio.id_articulo_costo = %s
								AND art_precio.id_precio = %s;",
								valTpDato($row['id_articulo'], "int"),
								valTpDato($rowArtCosto['id_articulo_costo'], "int"),
								valTpDato($arrayIdPrecio[$indice][0], "int"));
							$rsPrecio = mysql_query($queryPrecio);
							if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$rowPrecio = mysql_fetch_assoc($rsPrecio);
							
							$htmlTb .= "<td align=\"right\">";
								$htmlTb .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
								$htmlTb .= "<tr>";
									$htmlTb .= "<td>";
										$htmlTb .= ($arrayIdPrecio[$indice][1] == 1 && $row['cantidad_disponible_logica'] > 0 && $rowPrecio['precio_unitario'] > 0) ? "" : sprintf("<a class=\"modalImg\" id=\"aEditarArticuloPrecio:%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblArticuloPrecio', '%s', '%s', '%s', '%s')\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\"/></a>",
											$contFila,
											$rowArtCosto['id_articulo_costo'],
											$rowPrecio['id_articulo_precio'],
											$row['id_articulo'],
											$arrayIdPrecio[$indice][0]);
									$htmlTb .= "</td>";
									$htmlTb .= "<td align=\"right\" width=\"100%\">".$rowPrecio['abreviacion_moneda'].number_format($rowPrecio['precio_unitario'], 2, ".", ",")."</td>";
								$htmlTb .= "</tr>";
								$htmlTb .= "</table>";
							$htmlTb .= "</td>";
						}
						$htmlTb .= ($totalRowsArtCosto > 1) ? "</tr>" : "";
						$htmlTb .= ($totalRowsArtCosto > 1 && $contFila2 < $totalRowsArtCosto) ? "<tr align=\"left\" class=\"".$clase."\" height=\"24\">" : "";
						
						$arrayTotal[8] += $rowArtCosto['cantidad_disponible_logica'];
					}
				} else {
					$htmlTb .= "<td align=\"right\"></td>";
					$htmlTb .= "<td align=\"right\"></td>";
					
					$contFila3 = 0;
					foreach ($arrayIdPrecio as $indice => $valor) {
						$style = (fmod($contFila3, 2) == 0) ? "" : "style=\"font-weight:bold\"";
						$contFila3++;
						
						$queryPrecio = sprintf("SELECT
							art_precio.id_articulo_precio,
							art_precio.id_precio,
							art_precio.precio AS precio_unitario,
							
							(SELECT iva.observacion
							FROM iv_articulos_impuesto art_impsto
								INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
							WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
								AND art_impsto.id_articulo = art_precio.id_articulo
							LIMIT 1) AS descripcion_impuesto,
							
							(SELECT SUM(iva.iva)
							FROM iv_articulos_impuesto art_impsto
								INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
							WHERE iva.tipo IN (6,9,2)
								AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
							
							(art_precio.precio * (SELECT SUM(iva.iva)
												FROM iv_articulos_impuesto art_impsto
													INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
												WHERE iva.tipo IN (6,9,2)
													AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
							
							moneda.abreviacion AS abreviacion_moneda
						FROM iv_articulos_precios art_precio
							INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
						WHERE art_precio.id_articulo = %s
							AND art_precio.id_articulo_costo IS NULL
							AND art_precio.id_precio = %s
						ORDER BY art_precio.id_articulo_precio DESC
						LIMIT 1;",
							valTpDato($row['id_articulo'], "int"),
							valTpDato($arrayIdPrecio[$indice][0], "int"));
						$rsPrecio = mysql_query($queryPrecio);
						if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$rowPrecio = mysql_fetch_assoc($rsPrecio);
							
						$htmlTb .= "<td align=\"right\">";
							$htmlTb .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
							$htmlTb .= "<tr>";
								$htmlTb .= "<td>";
									$htmlTb .= ($arrayIdPrecio[$indice][1] == 1 && $row['cantidad_disponible_logica'] > 0 && $rowPrecio['precio_unitario'] > 0) ? "" : sprintf("<a class=\"modalImg\" id=\"aEditarArticuloPrecio:%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblArticuloPrecio', '%s', '%s', '%s', '%s')\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\"/></a>",
										$contFila,
										"",
										$rowPrecio['id_articulo_precio'],
										$row['id_articulo'],
										$arrayIdPrecio[$indice][0]);
								$htmlTb .= "</td>";
								$htmlTb .= "<td align=\"right\" width=\"100%\">".$rowPrecio['abreviacion_moneda'].number_format($rowPrecio['precio_unitario'], 2, ".", ",")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
					}
				}
			}
		$htmlTb .= (!($totalRowsArtCosto > 1)) ? "</tr>" : "";
		
		$arrayTotal[6] += $row['cantidad_disponible_logica'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"5\">".htmlentities("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td></td>";
		foreach ($arrayIdPrecio as $indice => $valor) {
			$htmlTb .= "<td></td>";
		}
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[6] += $row['cantidad_disponible_logica'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"5\">".htmlentities("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td></td>";
			foreach ($arrayIdPrecio as $indice => $valor) {
				$htmlTb .= "<td></td>";
			}
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"".(8 + $totalRowsPrecio)."\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogoPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"".(8 + $totalRowsPrecio)."\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

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
			
			$excelCodigoArticulo = $arrayAlmacen[$indice][0];
			$excelIdPrecio = $arrayAlmacen[$indice][1];
			$excelPrecioUnitario = $arrayAlmacen[$indice][2];
			
			// BUSCA SI EXISTE EL CODIGO DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE codigo_articulo LIKE %s;",
				valTpDato($excelCodigoArticulo, "text"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArticulo = mysql_num_rows($rsArticulo);
			$rowArticulo = mysql_fetch_array($rsArticulo);
			
			$idArticulo = $rowArticulo['id_articulo'];
			($totalRowsArticulo > 0) ? "" : $arrayObjArticuloNoExiste[] = $excelCodigoArticulo;
			
			// BUSCA SI EXISTE EL PRECIO
			$queryPrecio = sprintf("SELECT * FROM pg_precios WHERE id_precio = %s;",
				valTpDato($excelIdPrecio, "text"));
			$rsPrecio = mysql_query($queryPrecio);
			if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsPrecio = mysql_num_rows($rsPrecio);
			$rowPrecio = mysql_fetch_array($rsPrecio);
			
			$idPrecio = $rowPrecio['id_precio'];
			$nombrePrecio = $rowPrecio['descripcion_precio'];
			($totalRowsPrecio > 0) ? "" : $arrayObjPrecioNoExiste[] = $excelIdPrecio;
			
			// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
			$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
			$rsMoneda = mysql_query($queryMoneda);
			if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsMoneda = mysql_num_rows($rsMoneda);
			$rowMoneda = mysql_fetch_assoc($rsMoneda);
			
			$idMoneda = $rowMoneda['idmoneda'];
			
			$claseArticulo = ($totalRowsArticulo > 0 && $idArticulo > 0) ? "" : "divMsjError";
			$clasePrecio = ($totalRowsPrecio > 0 && $idPrecio > 0) ? "" : "divMsjError";
			
			(in_array("divMsjError",array($claseArticulo, $clasePrecio))) ? $arrayObjLineaError[] = $contFila : "";
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("$('#trItmPieArchivo').before('".
				"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
					"<td align=\"center\">%s</td>".
					"<td class=\"%s\"><table width=\"%s\">".
						"<tr>".
							"<td width=\"%s\">%s</td>".
							"<td width=\"%s\"><input type=\"text\" id=\"txtCodigoArtItm%s\" name=\"txtCodigoArtItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left;\" value=\"%s\"></td>".
						"</tr>".
						"</table>".
					"<td class=\"%s\"><table width=\"%s\">".
						"<tr>".
							"<td width=\"%s\">%s</td>".
							"<td width=\"%s\"><input type=\"text\" id=\"hddNombrePrecioItm%s\" name=\"hddNombrePrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left;\" value=\"%s\"></td>".
						"</tr>".
						"</table>".
					"<td><table width=\"%s\">".
						"<tr>".
							"<td width=\"%s\">%s</td>".
							"<td width=\"%s\"><input type=\"text\" id=\"hddPrecioItm%s\" name=\"hddPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
						"</tr>".
						"</table>".
						"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\">".
						"<input type=\"hidden\" id=\"hddIdPrecioItm%s\" name=\"hddIdPrecioItm%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdMonedaItm%s\" name=\"hddIdMonedaItm%s\" value=\"%s\"/></td>".
				"</tr>');",
				$contFila, $clase,
					$contFila,
					$claseArticulo, "100%",
							"15%", $idArticulo,
							"85%", $contFila, $contFila, $excelCodigoArticulo,
					$clasePrecio, "100%",
							"15%", $excelIdPrecio,
							"85%", $contFila, $contFila, $nombrePrecio,
					"100%",
							"15%", $rowMoneda['abreviacion'],
							"85%", $contFila, $contFila, number_format($excelPrecioUnitario, 2, ".", ","),
						$contFila,
						$contFila, $contFila, $idArticulo,
						$contFila, $contFila, $idPrecio,
						$contFila, $contFila, $idMoneda));
		}
		
		if (count($arrayObjLineaError) > 0) {
			$objResponse->alert(utf8_encode("Revise las líneas: ".implode(", ",$arrayObjLineaError)));
		}
		
		if (count($arrayObjArticuloNoExiste) > 0) {
			$objResponse->alert(utf8_encode("No existe(n) en el sistema ".count($arrayObjArticuloNoExiste)." códigos: ".implode(", ",$arrayObjArticuloNoExiste)));
		}
		
		if (count($arrayObjPrecioNoExiste) > 0) {
			$objResponse->alert(utf8_encode("No existe(n) en el sistema ".count($arrayObjPrecioNoExiste)." precios: ".implode(", ",$arrayObjPrecioNoExiste)));
		}
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarCatalogo");
$xajax->register(XAJAX_FUNCTION,"formArticuloPrecio");
$xajax->register(XAJAX_FUNCTION,"formImportarPrecio");
$xajax->register(XAJAX_FUNCTION,"guardarArticuloPrecio");
$xajax->register(XAJAX_FUNCTION,"importarPrecio");
$xajax->register(XAJAX_FUNCTION,"listaCatalogoPrecio");
$xajax->register(XAJAX_FUNCTION,"vistaPreviaImportar");
?>