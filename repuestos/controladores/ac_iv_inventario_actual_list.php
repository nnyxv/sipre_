<?php
set_time_limit(0);
ini_set('memory_limit', '-1');	
function buscarInventarioActual($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstTipoArticulo']) ? implode(",",$frmBuscar['lstTipoArticulo']) : $frmBuscar['lstTipoArticulo']),
		(is_array($frmBuscar['lstVerClasificacion']) ? implode(",",$frmBuscar['lstVerClasificacion']) : $frmBuscar['lstVerClasificacion']),
		(is_array($frmBuscar['lstSaldos']) ? implode(",",$frmBuscar['lstSaldos']) : $frmBuscar['lstSaldos']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaInventarioActual(0, "CONCAT(descripcion_almacen, ubicacion, id_articulo_costo)", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstClasificacion($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$array = array("A" => "A", "B" => "B", "C" => "C", "D" => "D", "E" => "E", "F" => "F");
	$totalRows = count($array);
	
	$html .= "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstVerClasificacion\" name=\"lstVerClasificacion\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstVerClasificacion","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarInventarioActual($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstTipoArticulo']) ? implode(",",$frmBuscar['lstTipoArticulo']) : $frmBuscar['lstTipoArticulo']),
		(is_array($frmBuscar['lstVerClasificacion']) ? implode(",",$frmBuscar['lstVerClasificacion']) : $frmBuscar['lstVerClasificacion']),
		(is_array($frmBuscar['lstSaldos']) ? implode(",",$frmBuscar['lstSaldos']) : $frmBuscar['lstSaldos']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_inventario_actual_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaInventarioActual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $valCadBusq[0];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla IS NOT NULL");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NOT NULL)
	OR (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo IS NULL
		AND vw_iv_art_almacen_costo.estatus_articulo_costo IS NULL
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NULL)
	OR (vw_iv_art_almacen_costo.cantidad_disponible_logica > 0)
	OR (vw_iv_art_almacen_costo.cantidad_reservada > 0))");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_art_almacen_costo.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.clasificacion IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[2])."'", "defined", "'".str_replace(",","','",$valCadBusq[2])."'"));
	}
	
	if (in_array(1,explode(",",$valCadBusq[3]))
	|| in_array(2,explode(",",$valCadBusq[3]))
	|| in_array(3,explode(",",$valCadBusq[3]))
	|| in_array(4,explode(",",$valCadBusq[3]))
	|| in_array(5,explode(",",$valCadBusq[3]))) {
		$arrayBusq = array();
		if (in_array(1,explode(",",$valCadBusq[3]))) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
		}
		
		if (in_array(2,explode(",",$valCadBusq[3]))) {
			$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica <= 0");
		}
		
		if (in_array(3,explode(",",$valCadBusq[3]))) {
			$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_reservada > 0");
		}
		
		if (in_array(4,explode(",",$valCadBusq[3]))) {
			$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_espera > 0");
		}
		
		if (in_array(5,explode(",",$valCadBusq[3]))) {
			$arrayBusq[] = sprintf("vw_iv_art_almacen_costo.cantidad_bloqueada > 0");
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".sprintf(implode(" OR ", $arrayBusq)).")";
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.id_articulo = %s
		OR vw_iv_art_almacen_costo.id_articulo_costo LIKE %s
		OR vw_iv_art_almacen_costo.descripcion LIKE %s
		OR vw_iv_art_almacen_costo.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[5], "int"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_iv_art_almacen_costo.*,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
		
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
			WHEN 1 THEN	vw_iv_art_almacen_costo.costo
			WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
			WHEN 3 THEN	vw_iv_art_almacen_costo.costo
		END) AS costo
	FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "10%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "8%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Prov.");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "4%", $pageNum, "unidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "10%", $pageNum, "CONCAT(descripcion_almacen, ubicacion, id_articulo_costo)", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicación");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Lote");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "4%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "4%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Disponible");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "6%", $pageNum, "(cantidad_disponible_logica * costo)", $campOrd, $tpOrd, $valBusq, $maxRows, "Valor Disponible");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "4%", $pageNum, "cantidad_reservada", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Reservada (Serv.)");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "6%", $pageNum, "(cantidad_reservada * costo)", $campOrd, $tpOrd, $valBusq, $maxRows, "Valor Reservada (Serv.)");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "4%", $pageNum, "cantidad_espera", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Espera por Facturar");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "6%", $pageNum, "(cantidad_espera * costo)", $campOrd, $tpOrd, $valBusq, $maxRows, "Valor Espera por Facturar");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "4%", $pageNum, "cantidad_bloqueada", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Bloqueada");
		$htmlTh .= ordenarCampo("xajax_listaInventarioActual", "6%", $pageNum, "(cantidad_bloqueada * costo)", $campOrd, $tpOrd, $valBusq, $maxRows, "Valor Bloqueada");

	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$costoUnitario = $row['costo'];
		
		$cantKardex = 0;
		$subTotalKardex = $cantKardex * $costoUnitario;
		
		$cantDisponible = $row['cantidad_disponible_logica']; // Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
		$subTotalDisponible = $cantDisponible * $costoUnitario;
		
		$cantReservada = $row['cantidad_reservada'];
		$subTotalReservada = $cantReservada * $costoUnitario;
		
		$cantDiferencia = $row['existencia'] - 0;
		$subTotalDiferencia = $cantDiferencia * $costoUnitario;
		
		$cantEspera = $row['cantidad_espera'];
		$subTotalEspera = $cantEspera * $costoUnitario;
		
		$cantBloqueada = $row['cantidad_bloqueada'];
		$subTotalBloqueada = $cantBloqueada * $costoUnitario;
		
		$classEstatusAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "class=\"texto_9px\"" : "class=\"divMsjError texto_9px\"";
		
		$classDisponible = ($cantDisponible > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$classReservada = ($cantReservada > 0) ? "class=\"divMsjAlerta\"" : "";
		
		$classEspera = ($cantEspera > 0) ? "class=\"divMsjInfo2\"" : "";
		
		$classBloqueada = ($cantBloqueada > 0) ? "class=\"divMsjInfo3\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".($contFila + (($pageNum) * $maxRows))."</td>"; // <----
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".htmlentities($row['descripcion'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['codigo_articulo_prov'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['unidad'])."</td>";
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
			$htmlTb .= "<td align=\"center\" ".$classEstatusAlmacen." nowrap=\"nowrap\">";
				$htmlTb .= utf8_encode($row['descripcion_almacen'])."<br><span class=\"textoNegrita_10px\">".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</span>";
				$htmlTb .= ($row['estatus_articulo_almacen'] == 1) ? "" : "<br><span class=\"textoRojoNegrita_10px\">".utf8_encode("Relacion Inactiva")."</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_articulo_costo'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['abreviacion_moneda_local']).number_format($costoUnitario, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($cantDisponible, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['abreviacion_moneda_local']).number_format($subTotalDisponible, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classReservada.">".number_format($cantReservada, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['abreviacion_moneda_local']).number_format($subTotalReservada, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classEspera.">".number_format($cantEspera, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['abreviacion_moneda_local']).number_format($subTotalEspera, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classBloqueada.">".number_format($cantBloqueada, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['abreviacion_moneda_local']).number_format($subTotalBloqueada, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[10] += $cantDisponible;
		$arrayTotal[11] += $subTotalDisponible;
		$arrayTotal[12] += $cantReservada;
		$arrayTotal[13] += $subTotalReservada;
		$arrayTotal[14] += $cantEspera;
		$arrayTotal[15] += $subTotalEspera;
		$arrayTotal[16] += $cantBloqueada;
		$arrayTotal[17] += $subTotalBloqueada;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[15], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[16], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[17], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$costoUnitario = $row['costo'];
				
				$cantKardex = 0;
				$subTotalKardex = $cantKardex * $costoUnitario;
				
				$cantDisponible = $row['cantidad_disponible_logica']; // Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
				$subTotalDisponible = $cantDisponible * $costoUnitario;
				
				$cantReservada = $row['cantidad_reservada'];
				$subTotalReservada = $cantReservada * $costoUnitario;
				
				$cantDiferencia = $row['existencia'] - 0;
				$subTotalDiferencia = $cantDiferencia * $costoUnitario;
				
				$cantEspera = $row['cantidad_espera'];
				$subTotalEspera = $cantEspera * $costoUnitario;
				
				$cantBloqueada = $row['cantidad_bloqueada'];
				$subTotalBloqueada = $cantBloqueada * $costoUnitario;
				
				$arrayTotalFinal[10] += $cantDisponible;
				$arrayTotalFinal[11] += $subTotalDisponible;
				$arrayTotalFinal[12] += $cantReservada;
				$arrayTotalFinal[13] += $subTotalReservada;
				$arrayTotalFinal[14] += $cantEspera;
				$arrayTotalFinal[15] += $subTotalEspera;
				$arrayTotalFinal[16] += $cantBloqueada;
				$arrayTotalFinal[17] += $subTotalBloqueada;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[15], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[16], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[17], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaInventarioActual(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaInventarioActual(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaInventarioActual(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaInventarioActual(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaInventarioActual(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaInventarioActual","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarInventarioActual");
$xajax->register(XAJAX_FUNCTION,"cargaLstClasificacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarInventarioActual");
$xajax->register(XAJAX_FUNCTION,"listaInventarioActual");
?>