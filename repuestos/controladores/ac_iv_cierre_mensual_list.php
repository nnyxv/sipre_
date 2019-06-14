<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function aprobarCierreMensual($idCierreMensual, $frmListaCierreMensual) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE iv_cierre_mensual SET
		estatus = %s
	WHERE id_cierre_mensual = %s",
		valTpDato(1, "text"), // 0 = Pendiente, 1 = Aprobado
		valTpDato($idCierreMensual, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Cierre Aprobado con Éxito"));
	
	$objResponse->loadCommands(listaCierreMensual(
		$frmListaCierreMensual['pageNum'],
		$frmListaCierreMensual['campOrd'],
		$frmListaCierreMensual['tpOrd'],
		$frmListaCierreMensual['valBusq']));
	
	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstMes'],
		$frmBuscar['lstAno']);
	
	$objResponse->loadCommands(listaCierreMensual(0, "id_cierre_mensual", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarClasificacionInv($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['hddIdCierreMensualClasificacionInv'],
		(is_array($frmBuscar['lstVerClasificacionAnt']) ? implode(",",$frmBuscar['lstVerClasificacionAnt']) : $frmBuscar['lstVerClasificacionAnt']),
		(is_array($frmBuscar['lstVerClasificacionAct']) ? implode(",",$frmBuscar['lstVerClasificacionAct']) : $frmBuscar['lstVerClasificacionAct']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaClasificacionInv(0, "art.id_articulo", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstAno($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['nom_ano']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['nom_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstClasificacion($nombreObjeto, $selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscarClasificacionInv').click();\"";
	
	$array = array("A" => "A", "B" => "B", "C" => "C", "D" => "D", "E" => "E", "F" => "F");
	$totalRows = count($array);
	
	$html .= "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML", $html);
	
	return $objResponse;
}

function cargaLstMes($selId = "") {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$html = "<select id=\"lstMes\" name=\"lstMes\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	for ($contMes = 1; $contMes <= 12; $contMes++) {
		$selected = ($selId == $contMes) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$disabled." value=\"".$contMes."\">".str_pad($contMes, 2, "0", STR_PAD_LEFT).".- ".$mes[$contMes]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMes","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMesAno($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (date("m") == 1) {
		$anoInicio = date("Y")-1;
		$anoLimite = date("Y");
	} else {
		$anoInicio = date("Y");
		$anoLimite = date("Y");
	}
	
	$html = "<select id=\"lstMesAno\" name=\"lstMesAno\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	for ($ano = $anoInicio; $ano <= $anoLimite; $ano++) {
		for ($mes = 1; (($mes <= 12 && $anoInicio != $anoLimite && $ano == $anoInicio) || ($mes <= date("m") && $ano == $anoLimite)); $mes++) {
			$query = sprintf("SELECT * FROM iv_cierre_mensual cierre_mens
			WHERE cierre_mens.mes = %s
				AND cierre_mens.ano = %s
				AND id_empresa = %s;",
				valTpDato(intval($mes), "int"),
				valTpDato($ano, "int"),
				valTpDato($idEmpresa, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$disabled = ($totalRows > 0) ? "disabled=\"disabled\"" : "";
			
			$html .= "<option ".$disabled." value=\"".str_pad($mes, 2, "0", STR_PAD_LEFT)."/".$ano."\">".str_pad($mes, 2, "0", STR_PAD_LEFT)."/".$ano."</option>";
		}
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMesAno","innerHTML",$html);
	
	return $objResponse;
}

function formAnalisisInv($idCierreMensual) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddIdCierreMensualAnalisisInv","value",$idCierreMensual);
	
	$query = sprintf("SELECT * FROM iv_cierre_mensual WHERE id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "EmpresaAnalisisInv", "ListaEmpresa"));
	$objResponse->assign("txtMesAno","value",utf8_encode(str_pad($row['mes'], 2, "0", STR_PAD_LEFT)."-".$row['ano']));
	
	$objResponse->loadCommands(objetoCodigoDinamico('tdCodigoArt', $row['id_empresa']));
	
	return $objResponse;
}

function formCierreMensual() {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
	$objResponse->loadCommands(cargaLstMesAno($_SESSION['idEmpresaUsuarioSysGts']));
	
	return $objResponse;
}

function formClasificacionInv($idCierreMensual) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_cierre_mensual WHERE id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdCierreMensualClasificacionInv","value",$row['id_cierre_mensual']);
	$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "EmpresaClasificacionInv", "ListaEmpresa"));
	$objResponse->assign("txtMesAnoClasificacionInv","value",utf8_encode(str_pad($row['mes'], 2, "0", STR_PAD_LEFT)."-".$row['ano']));
	
	$objResponse->loadCommands(cargaLstClasificacion("lstVerClasificacionAnt"));
	$objResponse->loadCommands(cargaLstClasificacion("lstVerClasificacionAct"));
	$objResponse->loadCommands(objetoCodigoDinamico('tdCodigoArtClasif', $row['id_empresa']));
	$objResponse->loadCommands(listaClasificacionInv(0, "art.id_articulo", "DESC", $idCierreMensual));
	
	return $objResponse;
}

function formMaxMin($idCierreMensual) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_cierre_mensual WHERE id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "EmpresaMaxMin", "ListaEmpresa"));
	$objResponse->assign("txtMesAnoMaxMin","value",utf8_encode(str_pad($row['mes'], 2, "0", STR_PAD_LEFT)."-".$row['ano']));
	
	$objResponse->loadCommands(listaMaxMin(0, "art.id_articulo", "DESC", $idCierreMensual));
	
	return $objResponse;
}

function generarAnalisisInv($idCierreMensual, $frmListaCierreMensual) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL CIERRE
	$query = sprintf("SELECT * FROM iv_cierre_mensual cierre_mensual WHERE cierre_mensual.id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	$mesCierre = $row['mes'];
	$anoCierre = $row['ano'];
	$mesesConsumoPromedio = $row['meses_consumo_promedio'];
	$diasHabilesPorMes = $row['dias_habiles'];
	
	// ELIMINA EL CIERRE QUE SE PUDO HABER CREADO ANTERIORMENTE PARA EL MES A CERRAR
	$deleteSQL = sprintf("DELETE FROM iv_analisis_inventario
	WHERE id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// INSERTA EL NUEVO ANALISIS DE INVENTARIO
	$insertSQL = sprintf("INSERT INTO iv_analisis_inventario (id_cierre_mensual)
	VALUE (%s);",
		valTpDato($idCierreMensual, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idAnalisisInv = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	/*$query = sprintf("SELECT
		art_emp.id_empresa,
		art.id_articulo,
		art.codigo_articulo_prov,
		
		SUM((IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0))) AS existencia,
		
		SUM(art_alm.cantidad_reservada) AS cantidad_reservada,
		
		SUM(((IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_reservada, 0)) - IFNULL(art_alm.cantidad_salida, 0))) AS cantidad_disponible_fisica,
		
		SUM(art_alm.cantidad_espera) AS cantidad_espera,
		SUM(art_alm.cantidad_bloqueada) AS cantidad_bloqueada,
		
		SUM((((IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0)) - IFNULL(art_alm.cantidad_reservada, 0)) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0))) AS cantidad_disponible_logica,
		
		SUM(IFNULL((CASE
			WHEN (ISNULL(casilla.id_casilla) or ISNULL(art_alm.estatus)) THEN
				art_emp.cantidad_pedida
			WHEN ((casilla.id_casilla IS NOT NULL) AND (art_alm.estatus IS NOT NULL)) THEN
				art_alm.cantidad_pedida
		END), 0)) AS cantidad_pedida,
		
		SUM((IFNULL((CASE
			WHEN (ISNULL(casilla.id_casilla) or ISNULL(art_alm.estatus)) THEN
				art_emp.cantidad_pedida
			WHEN ((casilla.id_casilla IS NOT NULL) AND (art_alm.estatus IS NOT NULL)) THEN
				art_alm.cantidad_pedida
		END), 0) + (((IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0)) - IFNULL(art_alm.cantidad_reservada, 0)) - IFNULL(art_alm.cantidad_espera, 0)))) AS cantidad_futura,
		
		art_emp.clasificacion,
		
		(SELECT cierre_mens_clasif.clasificacion_anterior
		FROM iv_cierre_mensual_clasificacion cierre_mens_clasif
		WHERE cierre_mens_clasif.id_cierre_mensual = %s
			AND cierre_mens_clasif.id_articulo = art.id_articulo
		LIMIT 1) AS clasificacion_anterior
	FROM iv_articulos art
		LEFT JOIN iv_articulos_almacen art_alm on (art_alm.id_articulo = art.id_articulo)
		LEFT JOIN iv_casillas casilla on (art_alm.id_casilla = casilla.id_casilla)
		LEFT JOIN iv_tramos tramo on (casilla.id_tramo = tramo.id_tramo)
		LEFT JOIN iv_estantes estante on (tramo.id_estante = estante.id_estante)
		INNER JOIN iv_articulos_empresa art_emp on (art.id_articulo = art_emp.id_articulo)
		LEFT JOIN iv_calles calle on (estante.id_calle = calle.id_calle)
		LEFT JOIN iv_almacenes alm on (calle.id_almacen = alm.id_almacen)
	WHERE art_emp.id_empresa = %s
	GROUP BY
		art_emp.id_empresa,
		art.id_articulo,
		art.codigo_articulo_prov
	ORDER BY
		CONCAT_WS('-', alm.descripcion, estante.descripcion_estante, tramo.descripcion_tramo, calle.descripcion_calle, casilla.descripcion_casilla);",
		valTpDato($idCierreMensual, "int"),
		valTpDato($idEmpresa, "int"));*/
	$query = sprintf("SELECT 
		vw_iv_art_emp.id_empresa,
		vw_iv_art_emp.id_articulo,
		vw_iv_art_emp.codigo_articulo,
		vw_iv_art_emp.codigo_articulo_prov,
		SUM(IFNULL(vw_iv_art_emp.existencia, 0)) AS existencia,
		SUM(IFNULL(vw_iv_art_emp.cantidad_reservada, 0)) AS cantidad_reservada,
		SUM(IFNULL(vw_iv_art_emp.cantidad_disponible_fisica, 0)) AS cantidad_disponible_fisica,
		SUM(IFNULL(vw_iv_art_emp.cantidad_espera, 0)) AS cantidad_espera,
		SUM(IFNULL(vw_iv_art_emp.cantidad_bloqueada, 0)) AS cantidad_bloqueada,
		SUM(IFNULL(vw_iv_art_emp.cantidad_disponible_logica, 0)) AS cantidad_disponible_logica,
		SUM(IFNULL(vw_iv_art_emp.cantidad_pedida, 0)) AS cantidad_pedida,
		SUM(IFNULL(vw_iv_art_emp.cantidad_futura, 0)) AS cantidad_futura,
		vw_iv_art_emp.clasificacion,
		
		(SELECT cierre_mens_clasif.clasificacion_anterior
		FROM iv_cierre_mensual_clasificacion cierre_mens_clasif
		WHERE cierre_mens_clasif.id_cierre_mensual = %s
			AND cierre_mens_clasif.id_articulo = vw_iv_art_emp.id_articulo
		LIMIT 1) AS clasificacion_anterior
	FROM vw_iv_articulos_empresa vw_iv_art_emp
	WHERE vw_iv_art_emp.id_empresa = %s
	GROUP BY vw_iv_art_emp.id_articulo
	ORDER BY vw_iv_art_emp.id_articulo DESC;",
		valTpDato($idCierreMensual, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$idArticulo = $row['id_articulo'];
		
		// BUSCA EL ULTIMO COSTO DEL ARTICULO
		$queryArtCosto = sprintf("SELECT
			(CASE (SELECT valor FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = art_costo.id_empresa)
				WHEN 1 THEN	art_costo.costo
				WHEN 2 THEN	art_costo.costo_promedio
				WHEN 3 THEN
					IF((SELECT
							(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
								+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
								- SUM(art_costo2.cantidad_salida * art_costo2.costo))
							/ (SUM(art_costo2.cantidad_inicio)
								+ SUM(art_costo2.cantidad_entrada)
								- SUM(art_costo2.cantidad_salida))
						FROM iv_articulos_costos art_costo2
						WHERE art_costo2.id_articulo = art_costo.id_articulo
							AND art_costo2.id_empresa = art_costo.id_empresa
							AND art_costo2.estatus = 1
						ORDER BY art_costo2.fecha_registro DESC), (SELECT
																		(SUM(art_costo2.cantidad_inicio * art_costo2.costo)
																			+ SUM(art_costo2.cantidad_entrada * art_costo2.costo)
																			- SUM(art_costo2.cantidad_salida * art_costo2.costo))
																		/ (SUM(art_costo2.cantidad_inicio)
																			+ SUM(art_costo2.cantidad_entrada)
																			- SUM(art_costo2.cantidad_salida))
																	FROM iv_articulos_costos art_costo2
																	WHERE art_costo2.id_articulo = art_costo.id_articulo
																		AND art_costo2.id_empresa = art_costo.id_empresa
																		AND art_costo2.estatus = 1
																	ORDER BY art_costo2.fecha_registro DESC), art_costo.costo_promedio)
			END) AS costo_unitario
		FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = %s
			AND art_costo.id_empresa = %s
		ORDER BY art_costo.id_articulo_costo DESC LIMIT 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
		
		$costoUnitario = ($rowArtCosto['costo_unitario'] != "") ? $rowArtCosto['costo_unitario'] : 0;
		
		if ($mesesConsumoPromedio > 0) {
			$arrayPromedioVentas = calcularConsumoPromedioVentas($idEmpresa, $idArticulo, $anoCierre, $mesesConsumoPromedio, $diasHabilesPorMes);
			$promedioMensual = doubleval($arrayPromedioVentas[1]);
			$promedioDiario = doubleval($arrayPromedioVentas[2]);
		} else {
			$objResponse->script("byId('imgAnalisisInv".$idCierreMensual."').style.display = '';");
			return $objResponse->alert("No tiene Asignado el Valor para la Cantidad de Meses de Consumo Promedio");
		}
		
		$insertSQL = sprintf("INSERT INTO iv_analisis_inventario_detalle (id_analisis_inventario, id_articulo, cantidad_existencia, cantidad_disponible_logica, cantidad_disponible_fisica, costo, promedio_mensual, promedio_diario, clasificacion_anterior, clasificacion)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idAnalisisInv, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($row['existencia'], "real_inglesa"),
			valTpDato(($row['existencia'] - $row['cantidad_reservada'] - $row['cantidad_espera'] - $row['cantidad_bloqueada']), "real_inglesa"),
			valTpDato(($row['existencia'] - $row['cantidad_reservada']), "real_inglesa"),
			valTpDato($costoUnitario, "real_inglesa"),
			valTpDato($promedioMensual, "real_inglesa"),
			valTpDato($promedioDiario, "real_inglesa"),
			valTpDato($row['clasificacion_anterior'], "text"),
			valTpDato($row['clasificacion'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL);
		mysql_query("SET NAMES 'latin1';");
	}
		
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Análisis de Inventario Generado con Éxito"));
	
	$objResponse->loadCommands(listaCierreMensual(
		$frmListaCierreMensual['pageNum'],
		$frmListaCierreMensual['campOrd'],
		$frmListaCierreMensual['tpOrd'],
		$frmListaCierreMensual['valBusq']));
	
	return $objResponse;
}

function generarCierreGral($idCierreMensual, $frmListaCierreMensual) {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(generarClasificacionInv($idCierreMensual, $frmListaCierreMensual));
	$objResponse->loadCommands(generarMaximoMinimo($idCierreMensual, $frmListaCierreMensual));
	$objResponse->loadCommands(generarAnalisisInv($idCierreMensual, $frmListaCierreMensual));
	
	return $objResponse;
}

function generarClasificacionInv($idCierreMensual, $frmListaCierreMensual) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL CIERRE
	$query = sprintf("SELECT * FROM iv_cierre_mensual cierre_mensual WHERE cierre_mensual.id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	$anoCierre = $row['ano'];
	$mesCierre = $row['mes'];
	/*-----------------------------------BUSCO EL VALOR DE LA CONFIGURACION 20------------------*/
	$queryConfig20 = sprintf("SELECT valor 
		FROM pg_configuracion_empresa 		
		WHERE id_configuracion = 20 AND status = 1 AND id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$rs20=mysql_query($queryConfig20);
	if (!$rs20) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig20 = mysql_fetch_assoc($rs20);
	
	if ($rowConfig20['valor'] == ""){ return $objResponse->alert("No se ha configurado el valor para la clasificacion de inventario. Config20"); }	
	/*------------------------------------------------------------------------------------------*/
	$queryArtEmp = sprintf("SELECT
		art_emp.id_articulo,
		art_emp.id_articulo_empresa,
		art_emp.id_empresa,
		(IFNULL(art_emp.cantidad_compra,0)
			+ IFNULL(art_emp.cantidad_entrada,0)
			- IFNULL(art_emp.cantidad_venta,0)
			- IFNULL(art_emp.cantidad_salida,0)) AS cantidad_saldo,
		art_emp.clasificacion,
		(SELECT art.fecha_registro FROM iv_articulos art
		WHERE art.id_articulo = art_emp.id_articulo) AS fecha_registro
	FROM iv_articulos_empresa art_emp
	WHERE art_emp.id_empresa = %s
	ORDER BY id_articulo DESC;",
		valTpDato($idEmpresa, "int"));
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowArtEmp = mysql_fetch_assoc($rsArtEmp)) {
		$idArticulo = $rowArtEmp['id_articulo'];
		$idEmpresa = $rowArtEmp['id_empresa'];
		
		// VERIFICA VALORES DE CONFIGURACION (Cálculo de Costo de Repuesto)
		$queryConfig18 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 18
			AND config_emp.status = 1
			AND config_emp.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsConfig18 = mysql_query($queryConfig18);
		if (!$rsConfig18) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowConfig18 = mysql_fetch_assoc($rsConfig18);
		
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_anual.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		/*if ($rowConfig18['valor'] == 1) { // 1 = Costo Independiente, 2 = Costo Único por la Empresa Principal
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cierre_anual.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		} else {
			// PARA TOMAR EN CUENTA LA EMPRESA PRINCIPAL DE ESTA
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("((SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = cierre_anual.id_empresa) = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s LIMIT 1))",
				valTpDato($idEmpresa, "int"));
		}*/
		
		$queryArtPasado = sprintf("SELECT * FROM iv_cierre_anual cierre_anual %s
			AND id_articulo = %s
			AND ano = %s;",
			$sqlBusq,
			valTpDato($idArticulo, "int"),
			valTpDato(($anoCierre - 1), "int"));
		$rsArtPasado = mysql_query($queryArtPasado);
		if (!$rsArtPasado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$detalleVenta = NULL;
		while ($rowArtPasado = mysql_fetch_assoc($rsArtPasado)) {
			$detalleVenta[1] += $rowArtPasado['enero_pasado'];
			$detalleVenta[2] += $rowArtPasado['febrero_pasado'];
			$detalleVenta[3] += $rowArtPasado['marzo_pasado'];
			$detalleVenta[4] += $rowArtPasado['abril_pasado'];
			$detalleVenta[5] += $rowArtPasado['mayo_pasado'];
			$detalleVenta[6] += $rowArtPasado['junio_pasado'];
			$detalleVenta[7] += $rowArtPasado['julio_pasado'];
			$detalleVenta[8] += $rowArtPasado['agosto_pasado'];
			$detalleVenta[9] += $rowArtPasado['septiembre_pasado'];
			$detalleVenta[10] += $rowArtPasado['octubre_pasado'];
			$detalleVenta[11] += $rowArtPasado['noviembre_pasado'];
			$detalleVenta[12] += $rowArtPasado['diciembre_pasado'];
			
			$detalleVenta[13] += $rowArtPasado['enero'];
			$detalleVenta[14] += $rowArtPasado['febrero'];
			$detalleVenta[15] += $rowArtPasado['marzo'];
			$detalleVenta[16] += $rowArtPasado['abril'];
			$detalleVenta[17] += $rowArtPasado['mayo'];
			$detalleVenta[18] += $rowArtPasado['junio'];
			$detalleVenta[19] += $rowArtPasado['julio'];
			$detalleVenta[20] += $rowArtPasado['agosto'];
			$detalleVenta[21] += $rowArtPasado['septiembre'];
			$detalleVenta[22] += $rowArtPasado['octubre'];
			$detalleVenta[23] += $rowArtPasado['noviembre'];
			$detalleVenta[24] += $rowArtPasado['diciembre'];
		}
		
		$queryArt = sprintf("SELECT * FROM iv_cierre_anual cierre_anual %s
			AND id_articulo = %s
			AND ano = %s;",
			$sqlBusq,
			valTpDato($idArticulo, "int"),
			valTpDato($anoCierre, "int"));
		$rsArt = mysql_query($queryArt);
		if (!$rsArt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowArt = mysql_fetch_assoc($rsArt)) {
			$detalleVenta[25] += $rowArt['enero'];
			$detalleVenta[26] += $rowArt['febrero'];
			$detalleVenta[27] += $rowArt['marzo'];
			$detalleVenta[28] += $rowArt['abril'];
			$detalleVenta[29] += $rowArt['mayo'];
			$detalleVenta[30] += $rowArt['junio'];
			$detalleVenta[31] += $rowArt['julio'];
			$detalleVenta[32] += $rowArt['agosto'];
			$detalleVenta[33] += $rowArt['septiembre'];
			$detalleVenta[34] += $rowArt['octubre'];
			$detalleVenta[35] += $rowArt['noviembre'];
			$detalleVenta[36] += $rowArt['diciembre'];
			$detalleVenta['cantidad_saldo_total'] += $rowArt['cantidad_saldo_total'];
			
			$detalleVenta['cantidad_saldo'] = $rowArt['cantidad_saldo'];
		}
		
		$detalleVenta['id_articulo_empresa'] = $rowArtEmp['id_articulo_empresa'];
		$detalleVenta['id_articulo'] = $idArticulo;
		$detalleVenta['clasificacion'] = $rowArtEmp['clasificacion'];
		$detalleVenta['fecha_registro'] = $rowArtEmp['fecha_registro'];
		
		$arrayArticuloVenta[] = $detalleVenta;
	}
	
	if (isset($arrayArticuloVenta)) {
		foreach ($arrayArticuloVenta as $indice => $valor) {
			$idArticulo = $arrayArticuloVenta[$indice]['id_articulo'];
			$cantidadSaldo = $arrayArticuloVenta[$indice]['cantidad_saldo'];
			$claseAsignada = false;
			
			/*CAMBIO PARA QUE DETECTE COMO DEBE CLASIFICAR EL INVENTARIO POR CONFIGURACION*/
			/*if (in_array(idArrayPais,array(3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico*/
			if ($rowConfig20['valor'] == 3) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
				$ano = $anoCierre;
				$mes = $mesCierre + 24;
				$mesesLlenos = 0;
				for ($cont = 0; $cont < 4; $cont++) { // COMPARACION CON LOS LOS ULTIMOS 4 MESES
					$arrayClase[$cont] = $arrayArticuloVenta[$indice][$mes];
					
					if ($arrayClase[$cont] > 0) {
						$mesesLlenos++;
					}
					
					$mes--;
				}
				
				if ($claseAsignada == false) {
					if ($mesesLlenos >= 4) { // VENDIDAS EN TODOS LOS ULTIMOS 4 MESES
						$claseAsignada = "A";
					} else if (in_array($mesesLlenos,array(2,3))) { // VENDIDAS EN 2 o 3 DE LOS ULTIMOS 4 MESES
						$claseAsignada = "C";
					}
				}
				
				
				$ano = $anoCierre;
				$mes = $mesCierre + 24;
				$mesesLlenos = 0;
				for ($cont = 0; $cont < 12; $cont++) { // COMPARACION CON LOS LOS ULTIMOS 12 MESES
					$arrayClase[$cont] = $arrayArticuloVenta[$indice][$mes];
					
					if ($arrayClase[$cont] > 0) {
						$mesesLlenos++;
					}
					
					$mes--;
				}
					
				if ($claseAsignada == false) {
					if ($mesesLlenos >= 1) { // VENDIDAS EN CUALQUIER MES DEL ULTIMO AÑO
						$claseAsignada = "D";
					} else {
						if (restaFechas("Y-m-d", date("Y-m-d",strtotime($arrayArticuloVenta[$indice]['fecha_registro'])), date("Y-m-d")) <= 12) { // SI LA PIEZA TIENE IGUAL O MENOS DE 12 MESES DE HABER SIDO CREADA
							$claseAsignada = "B";
						} else {
							$claseAsignada = "E";
						}
					}
				}
			} else if ($rowConfig20['valor'] == 2/*in_array($idEmpresa,array(2))*/) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
				$ano = $anoCierre;
				$mes = $mesCierre + 24;
				$mesesLlenos = 0;
				for ($cont = 0; $cont < 12; $cont++) { // COMPARACION CON LOS LOS ULTIMOS 12 MESES
					$arrayClase[$cont] = $arrayArticuloVenta[$indice][$mes];
					
					if ($arrayClase[$cont] > 0) {
						$mesesLlenos++;
					}
					
					$mes--;
				}
				
				if ($claseAsignada == false) {
					if (restaFechas("Y-m-d", date("Y-m-d",strtotime($arrayArticuloVenta[$indice]['fecha_registro'])), date("Y-m-d")) <= 11){ // SI LA PIEZA TIENE IGUAL O MENOS DE 11 MESES DE HABER SIDO CREADA
					$claseAsignada = "F";
				}  else if ($mesesLlenos >= 9) { // VENDIDAS EN TODOS LOS ULTIMOS 9 MESES
						$claseAsignada = "A";
					} else if (in_array($mesesLlenos,array(3,3))) { // VENDIDAS EN 3 DE LOS ULTIMOS 12 MESES
						$claseAsignada = "C";
					}
				}
				
				
				$ano = $anoCierre;
				$mes = $mesCierre + 24;
				$mesesLlenos = 0;
				for ($cont = 0; $cont < 12; $cont++) { // COMPARACION CON LOS LOS ULTIMOS 12 MESES
					$arrayClase[$cont] = $arrayArticuloVenta[$indice][$mes];
					
					if ($arrayClase[$cont] > 0) {
						$mesesLlenos++;
					}
					
					$mes--;
				}
					
				if ($claseAsignada == false) {
					if ($mesesLlenos >= 6) { // VENDIDAS EN 6 MESES DEL ULTIMO AÑO
						$claseAsignada = "B";
					} else {
						if($mesesLlenos >= 1){
							$claseAsignada = "D";
						} else {
							$claseAsignada = "E";
						}
					}
				}
			} else {
				if (restaFechas("Y-m-d", date("Y-m-d",strtotime($arrayArticuloVenta[$indice]['fecha_registro'])), date("Y-m-d")) <= 5) { // SI LA PIEZA TIENE IGUAL O MENOS DE 5 MESES DE HABER SIDO CREADA
					$claseAsignada = "F";
				} else {
					$ano = $anoCierre;
					$mes = $mesCierre + 24;
					$mesesLlenos = 0;
					for ($cont = 0; $cont < 6; $cont++) { // COMPARACION CON LOS LOS ULTIMOS 6 MESES
						$arrayClase[$cont] = $arrayArticuloVenta[$indice][$mes];
						
						if ($arrayClase[$cont] > 0) {
							$mesesLlenos++;
						}
						
						$mes--;
					}
					
					if ($claseAsignada == false) {
						if ($mesesLlenos == 6) { // VENDIDAS EN TODOS LOS ULTIMOS 6 MESES
							$claseAsignada = "A";
						} else if ($mesesLlenos == 5) { // VENDIDAS EN 5 DE LOS ULTIMOS 6 MESES
							$claseAsignada = "B";
						} else if (in_array($mesesLlenos,array(3,4))) { // VENDIDAS EN 3 o 4 DE LOS ULTIMOS 6 MESES
							$claseAsignada = "C";
						} else if (in_array($mesesLlenos,array(1,2))) { // VENDIDAS EN 1 o 2 DE LOS ULTIMOS 6 MESES
							$claseAsignada = "D";
						} else if ($arrayArticuloVenta[$indice]['cantidad_saldo'] > 0 && $mesesLlenos == 0) { // CON DISPONIBILIDAD Y NO VENDIDAS EN LOS ULTIMOS 6 MESES
							$claseAsignada = "E";
						}
					}
					
					
					$ano = $anoCierre;
					$mes = $mesCierre + 24;
					$mesesLlenos = 0;
					for ($cont = 0; $cont < 12; $cont++) { // COMPARACION CON LOS ULTIMOS 12 MESES
						$arrayClase[$cont] = $arrayArticuloVenta[$indice][$mes];
						
						if ($arrayClase[$cont] > 0) {
							$mesesLlenos++;
						}
						
						$mes--;
					}
					
					if ($claseAsignada == false) {
						if ($mesesLlenos >= 1) { // VENDIDAS EN CUALQUIER MES DEL ULTIMO AÑO
							$claseAsignada = "D";
						} else {
							$claseAsignada = "E";
						}
					}
				}
				/*CAMBIO PARA QUE DETECTE COMO DEBE CLASIFICAR EL INVENTARIO POR CONFIGURACION*/
				if ($rowConfig20['valor'] == 2) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					// APLICA SOLO PARA PANAMA PARA RECLASIFICAR EL INVENTARIO DEBIDO A QUE REALIZABAN DE ULTIMO EL CIERRE DE TUMBA MUERTO EL CUAL NO VENDE PIEZAS
					$arrayArticuloVenta[$indice]['clasificacion'] = ($cantidadSaldo == 0 && $arrayArticuloVenta[$indice]['clasificacion'] == 'F') ? $claseAsignada : $arrayArticuloVenta[$indice]['clasificacion'];
				}
				
				// SI TIENE DISPONIBILIDAD O SI NO TIENE DISPONIBILIDAD Y SE VENDIO EN EL MES A CERRAR
				$claseAsignada = ($cantidadSaldo > 0 || ($cantidadSaldo == 0 && $arrayArticuloVenta[$indice][$mesCierre + 24] > 0)) ? $claseAsignada : $arrayArticuloVenta[$indice]['clasificacion'];
			}
			
			// ACTUALIZA LA CLASIFICACION
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				clasificacion = %s
			WHERE id_articulo_empresa = %s;",
				valTpDato($claseAsignada, "text"),
				valTpDato($arrayArticuloVenta[$indice]['id_articulo_empresa'], "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$updateSQL = sprintf("UPDATE iv_articulos SET
				clasificacion = %s
			WHERE id_articulo = %s;",
				valTpDato($claseAsignada, "text"),
				valTpDato($idArticulo, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// GUARDA PARA EL HISTORICO DEL CIERRE
			$query = sprintf("SELECT * FROM iv_cierre_mensual_clasificacion cierre_mensual_clasif
			WHERE cierre_mensual_clasif.id_cierre_mensual = %s
				AND cierre_mensual_clasif.id_articulo = %s;",
				valTpDato($idCierreMensual, "int"),
				valTpDato($idArticulo, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			if ($totalRows > 0) {
				$updateSQL = sprintf("UPDATE iv_cierre_mensual_clasificacion SET
					clasificacion_actual = %s
				WHERE id_cierre_mensual_clasificacion = %s;",
					valTpDato($claseAsignada, "text"),
					valTpDato($row['id_cierre_mensual_clasificacion'], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			} else {
				$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_clasificacion (id_cierre_mensual, id_articulo, clasificacion_anterior, clasificacion_actual)
				VALUE (%s, %s, %s, %s);",
					valTpDato($idCierreMensual, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($arrayArticuloVenta[$indice]['clasificacion'], "text"),
					valTpDato($claseAsignada, "text"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Clasificación de Inventario Generado con Éxito"));
	
	$objResponse->loadCommands(listaCierreMensual(
		$frmListaCierreMensual['pageNum'],
		$frmListaCierreMensual['campOrd'],
		$frmListaCierreMensual['tpOrd'],
		$frmListaCierreMensual['valBusq']));
	
	return $objResponse;
}

function generarMaximoMinimo($idCierreMensual, $frmListaCierreMensual) {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL CIERRE
	$query = sprintf("SELECT * FROM iv_cierre_mensual cierre_mensual WHERE cierre_mensual.id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	$anoCierre = $row['ano'];
	$mesCierre = $row['mes'];
	$mesesInventario = $row['meses_inventario'];
	$mesesMinimo = $row['meses_minimo'];
	$mesesProteccion = $row['meses_proteccion'];
	
	if ($mesesInventario > 0 && $mesesMinimo > 0 && $mesesProteccion > 0) {
		
		$nroMesInicioCalculo = $mesCierre-$mesesInventario+1;
		$nroAnoInicioCalculo = $anoCierre;
		
		if ($nroMesInicioCalculo <= 0) {
			$nroMesInicioCalculo += 12;
			$nroAnoInicioCalculo = $anoCierre-1;
		}
		
		$mesCont = $nroMesInicioCalculo;
		$anoCont = $nroAnoInicioCalculo;
		$cont = 0;
		for ($cont = 0; $cont <= $mesesInventario-1; $cont++) {
			$arrayFechasCalculos[$cont][0] = $mesCont;
			$arrayFechasCalculos[$cont][1] = $anoCont;
			
			$mesCont++;
			
			if ($mesCont > 12) {
				$mesCont = 1;
				$anoCont++;
			}
		}
		
		$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa ORDER BY id_articulo DESC;");
		$rsArtEmp = mysql_query($queryArtEmp);
		if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowArtEmp = mysql_fetch_assoc($rsArtEmp)) {
			$idArticulo = $rowArtEmp['id_articulo'];
			
			$query = sprintf("SELECT * FROM iv_cierre_anual
				WHERE ano = %s
					AND id_empresa = %s
					AND id_articulo = %s",
				valTpDato($anoCierre, "date"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			if ($row['id_cierre_anual'] != "") {
				$cantTotArt = 0;
				if (isset($arrayFechasCalculos)) {
					foreach ($arrayFechasCalculos as $indice => $valor) {
						if ($row['ano_pasado'] == $arrayFechasCalculos[$indice][1]) {
							$nroMes = $arrayFechasCalculos[$indice][0];
							$cantidad = $row[strtolower($mes[$nroMes])."_pasado"];
						} else if ($row['ano'] == $arrayFechasCalculos[$indice][1]) {
							$nroMes = $arrayFechasCalculos[$indice][0];
							$cantidad = $row[strtolower($mes[$nroMes])];
						}
						$cantTotArt += $cantidad;
					}
				}
				$promedioVentaMensual = round($cantTotArt/$mesesInventario,2);
				$promedioVentaDiario = round($promedioVentaMensual/30,2);
				
				$diasMinimo = $mesesMinimo * 30;
				$stockMinimo = round($diasMinimo*$promedioVentaDiario);
				
				$mesesMaximo = $mesesMinimo+$mesesProteccion;
				$diasMaximo = $mesesMaximo * 30;
				$stockMaximo = round($diasMaximo*$promedioVentaDiario);
				
				$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
					stock_minimo = %s,
					stock_maximo = %s
				WHERE id_articulo_empresa = %s;",
					valTpDato($stockMinimo, "int"),
					valTpDato($stockMaximo, "int"),
					valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				$updateSQL = sprintf("UPDATE iv_articulos SET
					stock_minimo = %s,
					stock_maximo = %s
				WHERE id_articulo = %s;",
					valTpDato($stockMinimo, "int"),
					valTpDato($stockMaximo, "int"),
					valTpDato($idArticulo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				
				// GUARDA PARA EL HISTORICO DEL CIERRE
				$query = sprintf("SELECT * FROM iv_cierre_mensual_max_min cierre_mensual_max_min
				WHERE cierre_mensual_max_min.id_cierre_mensual = %s
					AND cierre_mensual_max_min.id_articulo = %s;",
					valTpDato($idCierreMensual, "int"),
					valTpDato($idArticulo, "int"));
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRows = mysql_num_rows($rs);
				$row = mysql_fetch_assoc($rs);
				
				if ($totalRows > 0) {
					$updateSQL = sprintf("UPDATE iv_cierre_mensual_max_min SET
						maximo = %s,
						minimo = %s
					WHERE id_cierre_mensual_max_min = %s;",
						valTpDato($stockMaximo, "text"),
						valTpDato($stockMinimo, "text"),
						valTpDato($row['id_cierre_mensual_max_min'], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				} else {
					$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_max_min (id_cierre_mensual, id_articulo, maximo, minimo)
					VALUE (%s, %s, %s, %s);",
						valTpDato($idCierreMensual, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($stockMaximo, "text"),
						valTpDato($stockMinimo, "text"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert(utf8_encode("Máximos y Minimos Generado con Éxito"));
	} else {
		$objResponse->alert(utf8_encode("No se puede Realizar el Cálculo de Max. y Min, Debido a que Existe(n) Parámetro(s) de Configuración que aún no han sido Establecido(s)"));
	}
	
	$objResponse->loadCommands(listaCierreMensual(
		$frmListaCierreMensual['pageNum'],
		$frmListaCierreMensual['campOrd'],
		$frmListaCierreMensual['tpOrd'],
		$frmListaCierreMensual['valBusq']));
	
	return $objResponse;
}

function guardarCierreMensual($frmCierreMensual, $frmListaCierreMensual) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmCierreMensual['txtIdEmpresa'];
	$arrayFecha = explode("/",$frmCierreMensual['lstMesAno']);
	$mesCierre = $arrayFecha[0];
	$anoCierre = $arrayFecha[1];
	
	// BUSCA LOS DOCUMENTOS DE VENTAS PENDIENTES POR FACTURAR O ANULAR
	/*$query = sprintf("SELECT id_pedido_venta FROM vw_iv_pedidos_venta
	WHERE id_empresa = %s
		AND (estatus_pedido_venta = 0
			OR (estatus_pedido_venta = 1 OR (estatus_pedido_venta = 2 AND id_empleado_aprobador IS NULL))
			OR (estatus_pedido_venta = 2 AND id_empleado_aprobador IS NOT NULL))",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);*/
	
	if ($totalRows == 0) {
		if (!xvalidaAcceso($objResponse,"iv_cierre_mensual_list","insertar")) { return $objResponse; }
		
		mysql_query("START TRANSACTION;");
		
		// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
		$Result1 = actualizarMovimientoTotal("", $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
		$Result1 = actualizarSaldos();
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// INSERTA LOS DATOS DEL CIERRE MENSUAL
		$insertSQL = sprintf("INSERT INTO iv_cierre_mensual (id_empresa, mes, ano, meses_inventario, meses_minimo, meses_proteccion, meses_consumo_promedio, dias_habiles, id_empleado_creador, id_tipo_costo)
		SELECT
			config_emp.id_empresa,
			%s,
			%s,
			(SELECT config_emp2.valor FROM pg_configuracion_empresa config_emp2
			WHERE config_emp2.id_configuracion = 1 AND config_emp2.status = 1 AND config_emp2.id_empresa = config_emp.id_empresa) AS meses_inventario,
			(SELECT config_emp3.valor FROM pg_configuracion_empresa config_emp3
			WHERE config_emp3.id_configuracion = 2 AND config_emp3.status = 1 AND config_emp3.id_empresa = config_emp.id_empresa) AS meses_minimo,
			(SELECT config_emp4.valor FROM pg_configuracion_empresa config_emp4
			WHERE config_emp4.id_configuracion = 3 AND config_emp4.status = 1 AND config_emp4.id_empresa = config_emp.id_empresa) AS meses_proteccion,
			(SELECT config_emp5.valor FROM pg_configuracion_empresa config_emp5
			WHERE config_emp5.id_configuracion = 9 AND config_emp5.status = 1 AND config_emp5.id_empresa = config_emp.id_empresa) AS meses_consumo_promedio,
			20 AS dias_habiles,
			%s,
			config_emp.valor AS id_tipo_costo
		FROM pg_configuracion_empresa config_emp
		WHERE config_emp.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($mesCierre, "int"),
			valTpDato($anoCierre, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($idEmpresa, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idCierreMensual = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		if (!($idCierreMensual > 0)) {
			return $objResponse->alert(utf8_encode("Faltan parámetros de configuración para realizar el cierre de la empresa seleccionada"));
		}
		
		$Result1 = calcularCierreAnual($idEmpresa, $mesCierre, $anoCierre);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// INSERTA LOS DATOS DE LA FACTURACION POR MOSTRADOR
		$Result1 = facturacionMostrador($idEmpresa, $mesCierre, $anoCierre);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayVentaVendedor = $Result1[1];
			$totalVentaVendedores = $Result1[2];
		}
		if (isset($arrayVentaVendedor)) {
			foreach ($arrayVentaVendedor as $indice => $valor) {
				$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_facturacion (id_cierre_mensual, id_empleado, id_modulo, total_repuesto, total_facturacion_contado, total_facturacion_credito, total_devolucion_contado, total_devolucion_credito)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idCierreMensual, "int"),
					valTpDato($arrayVentaVendedor[$indice][0], "int"),
					valTpDato(0, "int"), // 0 = Repuestos, 1 = Servicios
					valTpDato($arrayVentaVendedor[$indice][9], "real_inglesa"),
					valTpDato($arrayVentaVendedor[$indice][2], "real_inglesa"),
					valTpDato($arrayVentaVendedor[$indice][3], "real_inglesa"),
					valTpDato($arrayVentaVendedor[$indice][4], "real_inglesa"),
					valTpDato($arrayVentaVendedor[$indice][5], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		// INSERTA LOS DATOS DE LA FACTURACION DE ASESORES
		$Result1 = facturacionAsesores($idEmpresa, $mesCierre, $anoCierre);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayVentaAsesor = $Result1[1];
			$totalVentaAsesores = $Result1[2];
		}
		if (isset($arrayVentaAsesor)) {
			foreach ($arrayVentaAsesor as $indice => $valor) {
				$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_facturacion (id_cierre_mensual, id_empleado, id_modulo, id_tipo_orden, cantidad_ordenes, total_ut, total_mano_obra, total_tot, total_repuesto)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idCierreMensual, "int"),
					valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"),
					valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
					valTpDato($arrayVentaAsesor[$indice]['id_tipo_orden'], "int"),
					valTpDato($arrayVentaAsesor[$indice]['cantidad_ordenes'], "real_inglesa"),
					valTpDato($arrayVentaAsesor[$indice]['total_ut'], "real_inglesa"),
					valTpDato($arrayVentaAsesor[$indice]['total_mo'], "real_inglesa"),
					valTpDato($arrayVentaAsesor[$indice]['total_tot'], "real_inglesa"),
					valTpDato($arrayVentaAsesor[$indice]['total_repuestos'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		// INSERTA LAS ORDENES DE SERVICIOS ABIERTAS Y CERRADAS
		$Result1 = cierreOrdenesServicio($idEmpresa, $mesCierre, $anoCierre);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayTipoOrden = $Result1[1];
			$totalTipoOrdenAbierta = $Result1[2];
			$totalTipoOrdenCerrada = $Result1[3];
			$totalFallaTipoOrdenAbierta = $Result1[4];
			$totalFallaTipoOrdenCerrada = $Result1[5];
			$totalUtsTipoOrdenCerrada = $Result1[6];
		}
		if (isset($arrayTipoOrden)) {
			foreach ($arrayTipoOrden as $indice => $valor) {
				$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_orden (id_cierre_mensual, id_tipo_orden, cantidad_abiertas, cantidad_cerradas, cantidad_fallas_abiertas, cantidad_fallas_cerradas, cantidad_uts_cerradas)
				VALUE (%s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idCierreMensual, "int"),
					valTpDato($indice, "int"),
					valTpDato($arrayTipoOrden[$indice]['cantidad_abiertas'], "real_inglesa"),
					valTpDato($arrayTipoOrden[$indice]['cantidad_cerradas'], "real_inglesa"),
					valTpDato($arrayTipoOrden[$indice]['cantidad_fallas_abiertas'], "real_inglesa"),
					valTpDato($arrayTipoOrden[$indice]['cantidad_fallas_cerradas'], "real_inglesa"),
					valTpDato($arrayTipoOrden[$indice]['cantidad_uts_cerradas'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		// INSERTA LOS DATOS DE LA FACTURACION DE TECNICOS
		$Result1 = facturacionTecnicos($idEmpresa, $mesCierre, $anoCierre);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]); 
		} else {
			$arrayEquipo = $Result1[1];
			$totalTotalUtsEquipos = $Result1[2];
			$totalTotalBsEquipos = $Result1[3];
		}
		if (isset($arrayEquipo)) {
			foreach ($arrayEquipo as $indiceEquipo => $valorEquipo) {
				$arrayTecnico = $arrayEquipo[$indiceEquipo]['tecnicos'];
				$porcTotalEquipo = 0;
				$arrayMec = NULL;
				if (isset($arrayTecnico)) {
					foreach ($arrayTecnico as $indiceTecnico => $valorTecnico) {
						$arrayTotalTecnico[$valorTecnico['id_empleado']] = array(
							"id_empleado" => $valorTecnico['id_empleado'],
							"id_equipo_mecanico" => $valorTecnico['id_equipo_mecanico'],
							"total_ut" => $arrayTotalTecnico[$valorTecnico['id_empleado']]['total_ut'] + $valorTecnico['total_ut'],
							"total_mo" => $arrayTotalTecnico[$valorTecnico['id_empleado']]['total_mo'] + $valorTecnico['total_mo']);
					}
				}
			}
		}
		if (isset($arrayTotalTecnico)) {
			foreach ($arrayTotalTecnico as $indiceTotalTecnico => $valorTotalTecnico) {
				$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_facturacion (id_cierre_mensual, id_empleado, id_modulo, id_equipo_mecanico, total_ut, total_mano_obra)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idCierreMensual, "int"),
					valTpDato($valorTotalTecnico['id_empleado'], "int"),
					valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
					valTpDato($valorTotalTecnico['id_equipo_mecanico'], "int"),
					valTpDato($valorTotalTecnico['total_ut'], "real_inglesa"),
					valTpDato($valorTotalTecnico['total_mo'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Cierre Creado Satisfactoriamente");
		
		$objResponse->script("
		byId('btnCancelarCierreMensual').click();");
		
		$objResponse->loadCommands(listaCierreMensual(
			$frmListaCierreMensual['pageNum'],
			$frmListaCierreMensual['campOrd'],
			$frmListaCierreMensual['tpOrd'],
			$frmListaCierreMensual['valBusq']));
	} else {
		$objResponse->alert(utf8_encode("Aún existen Pedidos que deben ser Facturados o Anulados"));
	}
	
	return $objResponse;
}

function imprimirAnalisisInventario($valForm) {
	$objResponse = new xajaxResponse();
	
	$idCierreMensual = $valForm['hddIdCierreMensualAnalisisInv'];
	
	$query = sprintf("SELECT * FROM iv_cierre_mensual cierre_mens
	WHERE id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $valForm['hddCantCodigo']; $cont++) {
		$codArticulo .= $valForm['txtCodigoArticulo'.$cont].";";
	}
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$idEmpresa,
		$idCierreMensual,
		$valForm['cbxVerUbicDisponible'],
		$valForm['cbxVerUbicSinDisponible'],
		$valForm['lstVerClasificacion'],
		$codArticulo,
		$valForm['txtPalabra']);
	
	$objResponse->script(sprintf("verVentana('reportes/iv_analisis_inventario_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaCierreMensual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $mes;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((SELECT COUNT(analisis_inv.id_cierre_mensual) FROM iv_analisis_inventario analisis_inv
	WHERE analisis_inv.id_cierre_mensual = cierre_mensual.id_cierre_mensual) > 0
		OR (SELECT COUNT(cierre_mensual_clasif.id_cierre_mensual) FROM iv_cierre_mensual_clasificacion cierre_mensual_clasif
	WHERE cierre_mensual_clasif.id_cierre_mensual = cierre_mensual.id_cierre_mensual) > 0
		OR (SELECT COUNT(cierre_mensual_max_min.id_cierre_mensual) FROM iv_cierre_mensual_max_min cierre_mensual_max_min
	WHERE cierre_mensual_max_min.id_cierre_mensual = cierre_mensual.id_cierre_mensual) > 0
		OR (estatus = 0 OR estatus IS NULL))",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cierre_mensual.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cierre_mensual.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("mes LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ano LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	$query = sprintf("SELECT
		cierre_mensual.*,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		
		(SELECT COUNT(analisis_inv.id_cierre_mensual) FROM iv_analisis_inventario analisis_inv
		WHERE analisis_inv.id_cierre_mensual = cierre_mensual.id_cierre_mensual) AS cant_analisis_inv,
		
		(SELECT COUNT(cierre_mensual_clasif.id_cierre_mensual) FROM iv_cierre_mensual_clasificacion cierre_mensual_clasif
		WHERE cierre_mensual_clasif.id_cierre_mensual = cierre_mensual.id_cierre_mensual) AS cant_clasif_inv,
		
		(SELECT COUNT(cierre_mensual_max_min.id_cierre_mensual) FROM iv_cierre_mensual_max_min cierre_mensual_max_min
		WHERE cierre_mensual_max_min.id_cierre_mensual = cierre_mensual.id_cierre_mensual) AS cant_max_min_inv,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		INNER JOIN iv_cierre_mensual cierre_mensual ON (vw_iv_emp_suc.id_empresa_reg = cierre_mensual.id_empresa)
		INNER JOIN pg_empleado empleado ON (cierre_mensual.id_empleado_creador = empleado.id_empleado) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "8%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "32%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "30%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado Creador"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "10%", $pageNum, "mes", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Mes"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "10%", $pageNum, "ano", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Año"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "10%", $pageNum, "id_tipo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo Costo"));
		$htmlTh .= "<td colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_creacion']))."<br>".date("h:i:s a", strtotime($row['fecha_creacion']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"center\">".str_pad($row['mes'], 2, "0", STR_PAD_LEFT)."<br>(".$mes[$row['mes']].")</td>";
			$htmlTb .= "<td align=\"center\">".$row['ano']."</td>";
			$htmlTb .= "<td>";
				switch ($row['id_tipo_costo']) {
					case 1 : $htmlTb .= utf8_encode("Reposición"); break;
					case 2 : $htmlTb .= utf8_encode("Promedio"); break;
					case 3 : $htmlTb .= utf8_encode("FIFO"); break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">";
			if ($row['estatus'] == 0) {
				$imgClasificacionInv = ($row['cant_clasif_inv'] > 0) ? "../img/iconos/chart_organisation_edit.png" : "../img/iconos/chart_organisation_add.png";
				
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgClasificacionInv%s\" src=\"%s\" onclick=\"validarFrmClasificacionInventario('%s');\" title=\"%s\">",
					$contFila,
					$imgClasificacionInv,
					$row['id_cierre_mensual'],
					utf8_encode("Clasificación de Inv."));
				
				$htmlTb .= ($row['cant_clasif_inv'] > 0) ? sprintf("<input type=\"hidden\" id=\"hddClasificacionInv%s\" name=\"hddClasificacionInv%s\" value=\"1\">",
					$row['id_cierre_mensual'],
					$row['id_cierre_mensual']) : "";
			} else {
				if ($row['cant_clasif_inv'] > 0) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblListaClasificacionInv', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_organisation.png\" title=\"%s\"/></a>",
						$contFila,
						$row['id_cierre_mensual'],
						utf8_encode("Ver Clasificación de Inv."));
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus'] == 0) {
				$imgMaximoMinimo = ($row['cant_max_min_inv'] > 0) ? "../img/iconos/chart_bar_edit.png" : "../img/iconos/chart_bar_add.png";
				
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgMaximoMinimo%s\" src=\"%s\" onclick=\"validarFrmMaximoMinimo('%s');\" title=\"%s\">",
					$contFila,
					$imgMaximoMinimo,
					$row['id_cierre_mensual'],
					utf8_encode("Max. y Min."));
				
				$htmlTb .= ($row['cant_max_min_inv'] > 0) ? sprintf("<input type=\"hidden\" id=\"hddMaximoMinimo%s\" name=\"hddMaximoMinimo%s\" value=\"1\">",
					$row['id_cierre_mensual'],
					$row['id_cierre_mensual']) : "";
			} else {
				if ($row['cant_max_min_inv'] > 0) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblListaMaxMin', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_bar.png\" title=\"%s\"/></a>",
						$contFila,
						$row['id_cierre_mensual'],
						utf8_encode("Ver Max. y Min."));
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus'] == 0) {
				$imgAnalisisInv = ($row['cant_analisis_inv'] > 0) ? "../img/iconos/chart_pie_edit.png" : "../img/iconos/chart_pie_add.png";
				
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAnalisisInv%s\" src=\"%s\" onclick=\"validarFrmAnalisisInventario('%s');\" title=\"%s\">",
					$contFila,
					$imgAnalisisInv,
					$row['id_cierre_mensual'],
					utf8_encode("Análisis de Inv."));
				
				$htmlTb .= ($row['cant_analisis_inv'] > 0) ? sprintf("<input type=\"hidden\" id=\"hddAnalisisInv%s\" name=\"hddAnalisisInv%s\" value=\"1\">",
					$row['id_cierre_mensual'],
					$row['id_cierre_mensual']) : "";
			} else {
				if ($row['cant_analisis_inv'] > 0) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAnalisisInv', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"%s\"/></a>",
						$contFila,
						$row['id_cierre_mensual'],
						utf8_encode("Ver Análisis de Inv."));
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus'] == 0) {
				$imgCierreGral = ($row['cant_clasif_inv'] > 0 && $row['cant_max_min_inv'] > 0 && $row['cant_analisis_inv'] > 0) ? "" : "../img/iconos/aprobar_presup.png";
				
				$htmlTb .= (strlen($imgCierreGral) > 0) ? sprintf("<img class=\"puntero\" id=\"imgCierreGral%s\" src=\"%s\" onclick=\"validarFrmCierreGral('%s');\" title=\"%s\">",
					$contFila,
					$imgCierreGral,
					$row['id_cierre_mensual'],
					utf8_encode("Cierre General")) : "";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAprobarCierre%s\" src=\"../img/iconos/accept.png\" onclick=\"validarFrmAprobarCierre('%s');\" title=\"".utf8_encode("Aprobar Cierre")."\">",
					$contFila,
					$row['id_cierre_mensual']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCierreMensual","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaClasificacionInv($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_cierre_mensual = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_mensual_clasif.clasificacion_anterior IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[1])."'", "defined", "'".str_replace(",","','",$valCadBusq[1])."'"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_mensual_clasif.clasificacion_actual IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[2])."'", "defined", "'".str_replace(",","','",$valCadBusq[2])."'"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[3], "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(codigo_articulo LIKE %s
		OR descripcion LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT *
	FROM iv_cierre_mensual_clasificacion cierre_mensual_clasif
		INNER JOIN iv_articulos art ON (cierre_mensual_clasif.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaClasificacionInv", "16%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaClasificacionInv", "70%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaClasificacionInv", "7%", $pageNum, "clasificacion_anterior", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Anterior"));
		$htmlTh .= ordenarCampo("xajax_listaClasificacionInv", "7%", $pageNum, "clasificacion_actual", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Actual"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion_anterior']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion_actual']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"4\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClasificacionInv(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClasificacionInv(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaClasificacionInv(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClasificacionInv(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClasificacionInv(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaClasificacionInv","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMaxMin($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_cierre_mensual = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	$query = sprintf("SELECT *
	FROM iv_cierre_mensual_max_min cierre_mensual_max_min
		INNER JOIN iv_articulos art ON (cierre_mensual_max_min.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMaxMin", "16%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaMaxMin", "70%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaMaxMin", "7%", $pageNum, "maximo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Máximo"));
		$htmlTh .= ordenarCampo("xajax_listaMaxMin", "7%", $pageNum, "minimo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Mínimo"));
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".valTpDato($row['maximo'],"cero_por_vacio")."</td>";
			$htmlTb .= "<td align=\"center\">".valTpDato($row['minimo'],"cero_por_vacio")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMaxMin(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMaxMin(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMaxMin(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMaxMin(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMaxMin(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMaxMin","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"aprobarCierreMensual");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"buscarClasificacionInv");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstClasificacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstMes");
$xajax->register(XAJAX_FUNCTION,"cargaLstMesAno");
$xajax->register(XAJAX_FUNCTION,"formAnalisisInv");
$xajax->register(XAJAX_FUNCTION,"formCierreMensual");
$xajax->register(XAJAX_FUNCTION,"formClasificacionInv");
$xajax->register(XAJAX_FUNCTION,"formMaxMin");
$xajax->register(XAJAX_FUNCTION,"generarAnalisisInv");
$xajax->register(XAJAX_FUNCTION,"generarCierreGral");
$xajax->register(XAJAX_FUNCTION,"generarClasificacionInv");
$xajax->register(XAJAX_FUNCTION,"generarMaximoMinimo");
$xajax->register(XAJAX_FUNCTION,"guardarCierreMensual");
$xajax->register(XAJAX_FUNCTION,"imprimirAnalisisInventario");
$xajax->register(XAJAX_FUNCTION,"listaCierreMensual");
$xajax->register(XAJAX_FUNCTION,"listaClasificacionInv");
$xajax->register(XAJAX_FUNCTION,"listaMaxMin");


function calcularCierreAnual($idEmpresa, $mesCierre = "", $anoCierre = "") {
	global $conex;
	global $mes;
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS ARTICULOS DE LA EMPRESA QUE ESTEN REGISTRADO EN EL KARDEX
	$queryArt = sprintf("SELECT
		art_emp.id_articulo,
		IFNULL((art_emp.cantidad_compra + art_emp.cantidad_entrada) - (art_emp.cantidad_venta + art_emp.cantidad_salida), 0) AS existencia,
		
		(SELECT COUNT(id_articulo) FROM iv_cierre_anual
		WHERE id_empresa = art_emp.id_empresa
			AND id_articulo = art_emp.id_articulo
			AND ano = %s) AS cant_cierre
	FROM iv_articulos_empresa art_emp
	WHERE id_empresa = %s;",
		valTpDato($anoCierre, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArt = mysql_query($queryArt);
	if (!$rsArt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowArt = mysql_fetch_assoc($rsArt)) {
		$idArticulo = $rowArt['id_articulo'];
		
		if ($rowArt['cant_cierre'] > 0) {
			$updateSQL = sprintf("UPDATE iv_cierre_anual SET
				%s = 0,
				cantidad_saldo = %s
			WHERE id_empresa = %s
				AND id_articulo = %s
				AND ano = %s;",
				valTpDato(strtolower($mes[intval($mesCierre)]),"campo"),
				valTpDato($rowArt['existencia'], "real_inglesa"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($anoCierre, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else {
			$insertSQL = sprintf("INSERT INTO iv_cierre_anual (id_empresa, id_articulo, ano, cantidad_saldo)
			VALUE (%s, %s, %s, %s);",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($anoCierre, "int"),
				valTpDato($rowArt['existencia'], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// BUSCA LOS MESES DEL AÑO QUE TENGAN REGISTRADO VENTAS DEL ARTICULO
	$queryMes = sprintf("SELECT
		kardex.id_articulo,
		SUM(kardex.cantidad) AS cantidad,
		MONTH(kardex.fecha_movimiento) AS mes_movimiento
	FROM iv_kardex kardex
	WHERE kardex.tipo_movimiento IN (3)
		AND ((CASE kardex.tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								WHEN 1 THEN
									(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							END)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN -- REPUESTOS
									(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
								WHEN 1 THEN -- SERVICIOS
									(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
							END)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
					END)
			END) = %s
			OR %s IN (SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla))
		AND MONTH(kardex.fecha_movimiento) = %s
		AND YEAR(kardex.fecha_movimiento) = %s
	GROUP BY kardex.id_articulo, MONTH(kardex.fecha_movimiento)
	ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) ASC;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato(intval($mesCierre), "int"),
		valTpDato(intval($anoCierre), "int"));
	$rsMes = mysql_query($queryMes);
	if (!$rsMes) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowMes = mysql_fetch_assoc($rsMes)) {
		$idArticulo = $rowMes['id_articulo'];
		
		$updateSQL = sprintf("UPDATE iv_cierre_anual SET
			%s = %s
		WHERE id_empresa = %s
			AND id_articulo = %s
			AND ano = %s;",
			valTpDato(strtolower($mes[intval($mesCierre)]),"campo"),
			valTpDato($rowMes['cantidad'], "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($anoCierre, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	if (intval($mesCierre) == 1) {
		$updateSQL = sprintf("UPDATE iv_cierre_anual a, iv_cierre_anual b SET
			a.ano_pasado = a.ano - 1,
			a.enero_pasado = b.enero,
			a.febrero_pasado = b.febrero,
			a.marzo_pasado = b.marzo,
			a.abril_pasado = b.abril,
			a.mayo_pasado = b.mayo,
			a.junio_pasado = b.junio,
			a.julio_pasado = b.julio,
			a.agosto_pasado = b.agosto,
			a.septiembre_pasado = b.septiembre,
			a.octubre_pasado = b.octubre,
			a.noviembre_pasado = b.noviembre,
			a.diciembre_pasado = b.diciembre
		WHERE a.id_articulo = b.id_articulo
			AND a.id_empresa = b.id_empresa
			AND b.ano = a.ano - 1
			AND a.ano = %s
			AND a.id_empresa = %s;",
			valTpDato($anoCierre, "int"),
			valTpDato($idEmpresa, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	mysql_query("COMMIT;");
	
	return array(true, "");
}

function calcularConsumoPromedioVentas($idEmpresa, $idArticulo, $anoCierre, $mesesPromedio, $diasHabiles) {
	global $mes;
	
	$nroMesInicioCalculo = date("m") - $mesesPromedio;
	$nroAnoInicioCalculo = $anoCierre;
	
	if ($nroMesInicioCalculo <= 0) {
		$nroMesInicioCalculo += 12;
		$nroAnoInicioCalculo = $anoCierre - 1;
	}
	
	$mesCont = $nroMesInicioCalculo;
	$anoCont = $nroAnoInicioCalculo;
	$cont = 0;
	for ($cont = 0; $cont <= $mesesPromedio - 1; $cont++) {
		$arrayFechasCalculos[$cont][0] = $mesCont;
		$arrayFechasCalculos[$cont][1] = $anoCont;
		
		$mesCont++;
		
		if ($mesCont > 12) {
			$mesCont = 1;
			$anoCont++;
		}
	}
	
	$query = sprintf("SELECT * FROM iv_cierre_anual
	WHERE ano = %s
		AND id_empresa = %s
		AND id_articulo = %s;",
		valTpDato($anoCierre, "date"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while($row = mysql_fetch_assoc($rs)) {
		$cantTotArt = 0;
		if (isset($arrayFechasCalculos)) {
			foreach ($arrayFechasCalculos as $indice => $valor) {
				if ($row['ano_pasado'] == $arrayFechasCalculos[$indice][1]) {
					$nroMes = $arrayFechasCalculos[$indice][0];
					$cantidad = $row[strtolower($mes[$nroMes])."_pasado"];
				} else if ($row['ano'] == $arrayFechasCalculos[$indice][1]) {
					$nroMes = $arrayFechasCalculos[$indice][0];
					$cantidad = $row[strtolower($mes[$nroMes])];
				} else {
					$cantidad = 0;
				}
				$cantTotArt += $cantidad;
			}
		}
	}
	
	$promedioDiario = $cantTotArt / ($mesesPromedio * $diasHabiles);
	$promedioMensual = $promedioDiario * $diasHabiles;
	
	$array[] = $cantTotArt;
	$array[] = $promedioMensual;
	$array[] = $promedioDiario;
	
	return $array;
}
?>