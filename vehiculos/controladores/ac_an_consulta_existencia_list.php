<?php


function buscarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstMarcaBuscar']) ? implode(",",$frmBuscar['lstMarcaBuscar']) : $frmBuscar['lstMarcaBuscar']),
		(is_array($frmBuscar['lstModeloBuscar']) ? implode(",",$frmBuscar['lstModeloBuscar']) : $frmBuscar['lstModeloBuscar']),
		(is_array($frmBuscar['lstVersionBuscar']) ? implode(",",$frmBuscar['lstVersionBuscar']) : $frmBuscar['lstVersionBuscar']),
		(is_array($frmBuscar['lstAnoBuscar']) ? implode(",",$frmBuscar['lstAnoBuscar']) : $frmBuscar['lstAnoBuscar']),
		(is_array($frmBuscar['lstEstadoCompraBuscar']) ? implode(",",$frmBuscar['lstEstadoCompraBuscar']) : $frmBuscar['lstEstadoCompraBuscar']),
		(is_array($frmBuscar['lstEstadoVentaBuscar']) ? implode(",",$frmBuscar['lstEstadoVentaBuscar']) : $frmBuscar['lstEstadoVentaBuscar']),
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		(is_array($frmBuscar['lstAlmacenBuscar']) ? implode(",",$frmBuscar['lstAlmacenBuscar']) : $frmBuscar['lstAlmacenBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)", "ASC", $valBusq));
	
	return $objResponse;
}

function calcularAgregado($frmUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadFisica['cbx'];
	
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
			
			$txtTotalAgregado += str_replace(",", "", $frmUnidadFisica['txtTotalDcto'.$valor]);
		}
	}
	
	$objResponse->assign("txtTotalAgregado","value",number_format($txtTotalAgregado, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstAlmacenBuscar($nombreObjeto, $idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT * FROM an_almacen alm %s ORDER BY alm.nom_almacen", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".htmlentities($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstAnoBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstAnoBuscar\" name=\"lstAnoBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_ano'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAnoBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCondicionBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstCondicionBuscar\" name=\"lstCondicionBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_condicion_unidad'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_condicion_unidad']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicionBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoCompraBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste") ? $array[] = "ALTA" : "";
	($accion != "Ajuste") ? $array[] = "IMPRESO" : "";
	$array[] = "COMPRADO";
	$array[] = "REGISTRADO";
	($accion != "Ajuste") ? $array[] = "CANCELADO" : "";
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoVentaBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "TRANSITO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "POR REGISTRAR" : "";
	($accion != "Ajuste") ? $array[] = "SINIESTRADO" : "";
	$array[] = "DISPONIBLE";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "RESERVADO" : "";
	($accion != "Ajuste" && $accion != "Venta" && $accion != "Existencia") ? $array[] = "VENDIDO" : "";
	($accion != "Ajuste" && $accion != "Venta" && $accion != "Existencia") ? $array[] = "ENTREGADO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "PRESTADO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "ACTIVO FIJO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "INTERCAMBIO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "DEVUELTO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "ERROR EN TRASPASO" : "";
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMarcaModeloVersion($tpLst, $idLstOrigen, $nombreObjeto, $objetoBuscar = "false", $padreId = "", $selId = "", $onChange = "") {
	$objResponse = new xajaxResponse();
	
	$padreId = is_array($padreId) ? implode(",",$padreId) : $padreId;
	
	switch ($tpLst) {
		case "unidad_basica" : $arraySelec = array("lstPadre","lstMarca","lstModelo","lstVersion");
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	// SI EL OBJETO NO ES EL ULTIMO ASIGNA LA FUNCION PARA CARGAR EL SIGUIENTE OBJETO
	if (($posList + 1) != count($arraySelec) - 1) {
		$onChange = "onchange=\"".$onChange." xajax_cargaLstMarcaModeloVersion('".$tpLst."', '".$arraySelec[$posList+1]."', '".$nombreObjeto."', '".$objetoBuscar."', getSelectValues(byId(this.id)), '', '".str_replace("'","\'",$onChange)."');\"";
	}
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1' && $nombreObjeto != "Buscar") {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html);
			}
		}
		
		return $objResponse;
	} else {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 :
				$query = sprintf("SELECT * FROM an_marca marca
				ORDER BY marca.nom_marca;");
				$campoId = "id_marca";
				$campoDesc = "nom_marca";
				break;
			case 1 :
				$query = sprintf("SELECT * FROM an_modelo modelo
				WHERE modelo.id_marca IN (%s)
				ORDER BY modelo.nom_modelo;",
					valTpDato($padreId, "campo"));
				$campoId = "id_modelo";
				$campoDesc = "nom_modelo";
				break;
			case 2 :
				$query = sprintf("SELECT * FROM an_version vers
				WHERE vers.id_modelo IN (%s)
				ORDER BY vers.nom_version;",
					valTpDato($padreId, "campo"));
				$campoId = "id_version";
				$campoDesc = "nom_version";
				break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select ".(($totalRows > 2 && $objetoBuscar == "true") ? "multiple=\"multiple\"": "")." id=\"".$arraySelec[$posList+1].$nombreObjeto."\" name=\"".$arraySelec[$posList+1].$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row[$campoId]."\">".utf8_encode($row[$campoDesc])."</option>";
		}
		$html .= "</select>";
	}
	
	$objResponse->assign("td".$arraySelec[$posList+1].$nombreObjeto, 'innerHTML', $html);
	
	return $objResponse;
}

function exportarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstMarcaBuscar']) ? implode(",",$frmBuscar['lstMarcaBuscar']) : $frmBuscar['lstMarcaBuscar']),
		(is_array($frmBuscar['lstModeloBuscar']) ? implode(",",$frmBuscar['lstModeloBuscar']) : $frmBuscar['lstModeloBuscar']),
		(is_array($frmBuscar['lstVersionBuscar']) ? implode(",",$frmBuscar['lstVersionBuscar']) : $frmBuscar['lstVersionBuscar']),
		(is_array($frmBuscar['lstAnoBuscar']) ? implode(",",$frmBuscar['lstAnoBuscar']) : $frmBuscar['lstAnoBuscar']),
		(is_array($frmBuscar['lstEstadoCompraBuscar']) ? implode(",",$frmBuscar['lstEstadoCompraBuscar']) : $frmBuscar['lstEstadoCompraBuscar']),
		(is_array($frmBuscar['lstEstadoVentaBuscar']) ? implode(",",$frmBuscar['lstEstadoVentaBuscar']) : $frmBuscar['lstEstadoVentaBuscar']),
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		(is_array($frmBuscar['lstAlmacenBuscar']) ? implode(",",$frmBuscar['lstAlmacenBuscar']) : $frmBuscar['lstAlmacenBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_consulta_existencia_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formUnidadFisica($idUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	if ($idUnidadFisica > 0) {
		if (!xvalidaAcceso($objResponse,"an_consulta_existencia_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarUnidadFisica').click();"); return $objResponse; }
		
		$query = sprintf("SELECT 
			uni_fis.id_unidad_fisica,
			uni_bas.id_uni_bas,
			uni_bas.nom_uni_bas,
			uni_bas.clv_uni_bas,
			uni_bas.des_uni_bas,
			marca.nom_marca,
			modelo.nom_modelo,
			vers.nom_version,
			ano.id_ano,
			ano.nom_ano,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.titulo_vehiculo,
			uni_fis.placa,
			uni_fis.tipo_placa,
			uni_fis.id_condicion_unidad,
			cond_unidad.descripcion AS condicion_unidad,
			uni_fis.kilometraje,
			uni_fis.fecha_fabricacion,
			uni_fis.fecha_expiracion_marbete,
			uni_bas.imagen_auto,
			alm.id_empresa,
			alm.id_almacen,
			alm.nom_almacen,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			color_ext1.id_color AS id_color_externo,
			color_ext1.nom_color AS color_externo,
			color_int1.id_color AS id_color_interno,
			color_int1.nom_color AS color_interno,
			color_ext2.id_color AS id_color_externo2,
			color_ext2.nom_color AS color_externo2,
			color_int2.id_color AS id_color_interno2,
			color_int2.nom_color AS color_interno2,
			uni_fis.registro_legalizacion,
			uni_fis.registro_federal,
			pais_origen.id_origen,
			pais_origen.nom_origen,
			clase.nom_clase,
			uso.id_uso,
			uso.nom_uso,
			uni_bas.pto_uni_bas,
			uni_bas.cil_uni_bas,
			uni_bas.ccc_uni_bas,
			uni_bas.cab_uni_bas,
			trans.nom_transmision,
			comb.nom_combustible,
			uni_bas.cap_uni_bas,
			uni_bas.uni_uni_bas,
			uni_bas.anos_de_garantia,
			uni_bas.kilometraje AS kilometraje_garantia,
			uni_fis.serial1,
			uni_fis.codigo_unico_conversion,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
				INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
				INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
				INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
				INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
			LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
			LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
			LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
			INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
			INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
			INNER JOIN an_combustible comb ON (uni_bas.com_uni_bas = comb.id_combustible)
		WHERE uni_fis.id_unidad_fisica = %s;",
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("txtIdUnidadFisica", "value", $row['id_unidad_fisica']);
		$objResponse->assign("hddIdUnidadBasica", "value", $row['id_uni_bas']);
		$objResponse->assign("txtNombreUnidadBasica", "value", utf8_encode($row['nom_uni_bas']));
		$objResponse->assign("txtClaveUnidadBasica", "value", utf8_encode($row['clv_uni_bas']));
		$objResponse->assign("txtDescripcion", "innerHTML", utf8_encode($row['des_uni_bas']));
		$objResponse->assign("txtMarcaUnidadBasica", "value", utf8_encode($row['nom_marca']));
		$objResponse->assign("txtModeloUnidadBasica", "value", utf8_encode($row['nom_modelo']));
		$objResponse->assign("txtVersionUnidadBasica", "value", utf8_encode($row['nom_version']));
		$objResponse->assign("txtAno", "value", utf8_encode($row['nom_ano']));
		$objResponse->assign("txtPlaca", "value", utf8_encode($row['placa']));
		$objResponse->assign("txtCondicion", "value", utf8_encode($row['condicion_unidad']));
		$objResponse->assign("txtFechaFabricacion", "value", (($row['fecha_fabricacion'] != "") ? date(spanDateFormat, strtotime($row['fecha_fabricacion'])) : ""));
		$objResponse->assign("txtFechaExpiracionMarbete", "value", (($row['fecha_expiracion_marbete'] != "") ? date(spanDateFormat, strtotime($row['fecha_expiracion_marbete'])) : ""));
		$objResponse->assign("txtAlmacen", "value", utf8_encode($row['nom_almacen']));
		$objResponse->assign("txtEstadoCompra", "value", utf8_encode($row['estado_compra']));
		$objResponse->assign("txtEstadoVenta", "value", utf8_encode($row['estado_venta']));
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		$objResponse->assign("imgArticulo","src",$imgFoto);
		$objResponse->assign("hddUrlImagen","value",$row['imagen_auto']);
		
		$objResponse->assign("txtSerialCarroceria", "value", utf8_encode($row['serial_carroceria']));
		$objResponse->assign("txtSerialMotor", "value", utf8_encode($row['serial_motor']));
		$objResponse->assign("txtNumeroVehiculo", "value", utf8_encode($row['serial_chasis']));
		$objResponse->assign("txtTituloVehiculo", "value", utf8_encode($row['titulo_vehiculo']));
		$objResponse->assign("txtRegistroLegalizacion", "value", utf8_encode($row['registro_legalizacion']));
		$objResponse->assign("txtRegistroFederal", "value", utf8_encode($row['registro_federal']));
		
		$objResponse->assign("txtPaisOrigen", "value", utf8_encode($row['nom_origen']));
		$objResponse->assign("txtClase", "value", utf8_encode($row['nom_clase']));
		$objResponse->assign("txtUso", "value", utf8_encode($row['nom_uso']));
		$objResponse->assign("txtNumeroPuertas", "value", utf8_encode($row['pto_uni_bas']));
		$objResponse->assign("txtNumeroCilindros", "value", utf8_encode($row['cil_uni_bas']));
		$objResponse->assign("txtCilindrada", "value", utf8_encode($row['ccc_uni_bas']));
		$objResponse->assign("txtCaballosFuerza", "value", utf8_encode($row['cab_uni_bas']));
		$objResponse->assign("txtTransmision", "value", utf8_encode($row['nom_transmision']));
		$objResponse->assign("txtCombustible", "value", utf8_encode($row['nom_combustible']));
		$objResponse->assign("txtCapacidad", "value", $row['cap_uni_bas']);
		$objResponse->assign("txtUnidad", "value", $row['uni_uni_bas']);
		$objResponse->assign("txtAnoGarantia", "value", $row['anos_de_garantia']);
		$objResponse->assign("txtKmGarantia", "value", number_format($row['kilometraje_garantia'], 2, ".", ","));
		
		if (strlen($row['serial1']) > 0) {
			$objResponse->script("byId('trSistemaGNV').style.display = '';");
		}
		$objResponse->assign("txtSerial1", "value", utf8_encode($row['serial1']));
		$objResponse->assign("txtCodigoUnico", "value", utf8_encode($row['codigo_unico_conversion']));
		$objResponse->assign("txtMarcaKit", "value", utf8_encode($row['marca_kit']));
		$objResponse->assign("txtModeloRegulador", "value", utf8_encode($row['modelo_regulador']));
		$objResponse->assign("txtSerialRegulador", "value", utf8_encode($row['serial_regulador']));
		$objResponse->assign("txtMarcaCilindro", "value", utf8_encode($row['marca_cilindro']));
		$objResponse->assign("txtCapacidadCilindro", "value", utf8_encode($row['capacidad_cilindro']));
		$txtFechaCilindro = ($row['fecha_elaboracion_cilindro'] != "") ? date(spanDateFormat, strtotime($row['fecha_elaboracion_cilindro'])) : "";
		$objResponse->assign("txtFechaCilindro", "value", $txtFechaCilindro);
		
		$objResponse->assign("txtKilometraje", "value", number_format($row['kilometraje'],0));
		
		$objResponse->assign("txtColorExterno1", "value", utf8_encode($row['color_externo']));
		$objResponse->assign("txtColorInterno1", "value", utf8_encode($row['color_interno']));
		$objResponse->assign("txtColorExterno2", "value", utf8_encode($row['color_externo2']));
		$objResponse->assign("txtColorInterno2", "value", utf8_encode($row['color_interno2']));
		
		
		$objResponse->script("
		byId('trtxtAllowanceAnt').style.display = 'none';
		byId('trtxtAcvAnt').style.display = 'none';
		byId('trtxtPayoffAnt').style.display = 'none';
		byId('trtxtCreditoNetoAnt').style.display = 'none';
		
		byId('txtAllowance').className = 'inputInicial';
		byId('txtAllowance').readOnly = true;
		byId('txtAcv').className = 'inputInicial';
		byId('txtAcv').readOnly = true;
		byId('txtPayoff').className = 'inputInicial';
		byId('txtPayoff').readOnly = true;");
		
		$query = sprintf("SELECT 
			tradein.id_tradein,
			tradein.id_unidad_fisica,
			tradein.allowance,
			tradein.payoff,
			tradein.acv,
			tradein.total_credito,
			tradein.id_proveedor,
			cxc_ant.montoNetoAnticipo,
			cxc_ant.numeroAnticipo,
			cxc_ant.idDepartamento,
			cxc_ant.id_empresa,
			cxc_ant.observacionesAnticipo,
			pg_modulos.descripcionModulo,
			cliente.id,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
			cj_cc_detalleanticipo.id_concepto,
			cj_conceptos_formapago.descripcion,
			formapagos.idFormaPago,
			formapagos.nombreFormaPago,
			(SELECT cp_proveedor.nombre FROM cp_proveedor WHERE cp_proveedor.id_proveedor = tradein.id_proveedor) as nombre_cliente_adeudado
		FROM an_tradein tradein
			INNER JOIN cj_cc_anticipo cxc_ant ON (tradein.id_anticipo = cxc_ant.idAnticipo)
			INNER JOIN pg_modulos ON (cxc_ant.idDepartamento = pg_modulos.id_modulo)
			INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
			INNER JOIN cj_cc_detalleanticipo ON (cxc_ant.idAnticipo = cj_cc_detalleanticipo.idAnticipo)
			INNER JOIN cj_conceptos_formapago ON (cj_cc_detalleanticipo.id_concepto = cj_conceptos_formapago.id_concepto)
			INNER JOIN formapagos ON (cj_conceptos_formapago.id_formapago = formapagos.idFormaPago)
		WHERE tradein.id_unidad_fisica = %s
		LIMIT 1;",
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("txtIdTradeIn", "value", $row['id_tradein']);
		$objResponse->assign("txtAllowance", "value", number_format($row['allowance'], 2, ".", ","));
		$objResponse->assign("txtPayoff", "value", number_format($row['payoff'], 2, ".", ","));
		$objResponse->assign("txtAcv", "value", number_format($row['acv'], 2, ".", ","));
		$objResponse->assign("txtCreditoNeto", "value", number_format($row['total_credito'], 2, ".", ","));
		
		$objResponse->loadCommands(listaTradeInAuditoria(0, "tradein_audit.tiempo_registro", "DESC", $row['id_tradein']));
		$objResponse->loadCommands(listaKardex(0, "nom_uni_bas", "ASC", $idUnidadFisica));
	}
	
	return $objResponse;
}

function formUnidadFisicaAgregado($idUnidadFisica, $frmUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadFisica['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// BUSCA LOS DATOS DE LA UNIDAD FISICA
	$queryUniFis = sprintf("SELECT uni_fis.*,
		alm.id_empresa
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		LEFT JOIN an_tradein tradein ON (uni_fis.id_unidad_fisica = tradein.id_unidad_fisica)
	WHERE uni_fis.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rsUniFis = mysql_query($queryUniFis);
	if (!$rsUniFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUniFis = mysql_fetch_assoc($rsUniFis);
	
	$objResponse->assign("hddIdEmpresaUnidadFisicaAgregado","value",$rowUniFis['id_empresa']);
	$objResponse->assign("hddIdUnidadFisicaAgregado","value",$idUnidadFisica);
	
	$queryUniFisAgregado = sprintf("SELECT uni_fis_agregado.*,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			1
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		2
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	4
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			3
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		4
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	2
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			4
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		2
		END) AS id_tipo_movimiento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			'1.- Compra'
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		'2.- Entrada'
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	'4.- Salida'
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			'3.- Venta'
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		'4.- Salida'
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	'2.- Entrada'
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			'4.- Salida'
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		'2.- Entrada'
		END) AS descripcion_tipo_movimiento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			'FA'
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		'ND'
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	'NC'
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			'FA'
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		'ND'
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	'NC'
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			'VS'
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		'VE'
		END) AS tipoDocumento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.id_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.id_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.id_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.idNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.idNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.id_vale_salida
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.id_vale_entrada
		END) AS id_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.numero_factura_proveedor
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.numero_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.numero_nota_credito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.numeroNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.numeracion_nota_credito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.numero_vale
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.numero_vale_entrada
		END) AS numero_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.fecha_origen
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.fecha_origen_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.fecha_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.fechaRegistroNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.fechaNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			DATE(sa_vs.fecha_vale)
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		DATE(sa_ve.fecha_creada)
		END) AS fecha_origen,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.id_modulo
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.id_modulo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.id_departamento_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.idDepartamentoOrigenNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.idDepartamentoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			1
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		1
		END) AS id_modulo,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.estatus_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.estatus_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.estado_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.estadoNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.estadoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.estado_vale
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.estado_vale
		END) AS estatus_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL
			OR uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL
			OR uni_fis_agregado.id_factura_cxc IS NOT NULL
			OR uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN
				(CASE (CASE 
							WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.estatus_factura
							WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.estatus_notacargo
							WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
							WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.estadoNotaCargo
						END)
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL
			OR uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN
				(CASE (CASE 
							WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.estado_notacredito
							WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.estadoNotaCredito
						END)
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado No Asignado'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
				END)
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL
			OR uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN
				(CASE (CASE 
							WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.estado_vale
							WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.estado_vale
						END)
					WHEN 0 THEN 'Generado'
					WHEN 1 THEN 'Devuelto'
				END)
		END) AS descripcion_estatus_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.observacion_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.observacion_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.observacion_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.observacionNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.observacionesNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.motivo_vale
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.motivo_vale
		END) AS observacion_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.saldo_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.saldo_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.saldo_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.saldoNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.saldoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS saldo_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			prov.id_proveedor
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		prov.id_proveedor
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	prov.id_proveedor
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			cliente.id
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cliente.id
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cliente.id
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			cliente.id
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		cliente.id
		END) AS id_cliente,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		CONCAT_WS('-', cliente.lci, cliente.ci)
		END) AS ci_cliente,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			prov.nombre
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		prov.nombre
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	prov.nombre
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		END) AS nombre_cliente,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.id_motivo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.id_motivo
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS id_motivo,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		motivo.descripcion
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	motivo.descripcion
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo)
				
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito)
				
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS descripcion_motivo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		uni_fis.tipo_placa,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN		0
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN
				(IFNULL(cxp_nd.subtotal_notacargo, 0)
				- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
				+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) AS total_gasto FROM cp_notacargo_gastos cxp_nd_gasto
						WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
							AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)
				+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) AS total_iva FROM cp_notacargo_iva cxp_nd_iva
						WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0))
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN
				(IFNULL(cxp_nc.subtotal_notacredito, 0)
				- IFNULL(cxp_nc.subtotal_descuento, 0)
				+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
						WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
							AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
				+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
						WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN		0
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN
				(IFNULL(cxc_nd.subtotalNotaCargo, 0)
				- IFNULL(cxc_nd.descuentoNotaCargo, 0)
				+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
					+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)))
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN
				cxc_nc.montoNetoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN
				sa_vs.monto_total
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN
				sa_ve.monto_total
		END) AS total_documento,
		vw_pg_empleado_registro.nombre_empleado AS nombre_empleado_registro,
		vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_unidad_fisica_agregado uni_fis_agregado ON (uni_fis.id_unidad_fisica = uni_fis_agregado.id_unidad_fisica)
		LEFT JOIN cp_factura cxp_fact ON (uni_fis_agregado.id_factura_cxp = cxp_fact.id_factura AND uni_fis_agregado.id_factura_cxp IS NOT NULL)
		LEFT JOIN cp_notadecargo cxp_nd ON (uni_fis_agregado.id_nota_cargo_cxp = cxp_nd.id_notacargo AND uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL)
		LEFT JOIN cp_notacredito cxp_nc ON (uni_fis_agregado.id_nota_credito_cxp = cxp_nc.id_notacredito AND uni_fis_agregado.id_nota_credito_cxp IS NOT NULL)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (uni_fis_agregado.id_factura_cxc = cxc_fact.idFactura AND uni_fis_agregado.id_factura_cxc IS NOT NULL)
		LEFT JOIN cj_cc_notadecargo cxc_nd ON (uni_fis_agregado.id_nota_cargo_cxc = cxc_nd.idNotaCargo AND uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL)
		LEFT JOIN cj_cc_notacredito cxc_nc ON (uni_fis_agregado.id_nota_credito_cxc = cxc_nc.idNotaCredito AND uni_fis_agregado.id_nota_credito_cxc IS NOT NULL)
		LEFT JOIN sa_vale_salida sa_vs ON (uni_fis_agregado.id_vale_salida = sa_vs.id_vale_salida AND uni_fis_agregado.id_vale_salida IS NOT NULL)
		LEFT JOIN sa_vale_entrada sa_ve ON (uni_fis_agregado.id_vale_entrada = sa_ve.id_vale_entrada AND uni_fis_agregado.id_vale_entrada IS NOT NULL)
		LEFT JOIN sa_orden orden ON ((sa_vs.id_orden = orden.id_orden AND uni_fis_agregado.id_vale_salida IS NOT NULL)
			OR (sa_ve.id_orden = orden.id_orden AND uni_fis_agregado.id_vale_entrada IS NOT NULL))
		LEFT JOIN cp_proveedor prov ON ((cxp_fact.id_proveedor = prov.id_proveedor AND uni_fis_agregado.id_factura_cxp IS NOT NULL)
			OR (cxp_nd.id_proveedor = prov.id_proveedor AND uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL)
			OR (cxp_nc.id_proveedor = prov.id_proveedor AND uni_fis_agregado.id_nota_credito_cxp IS NOT NULL))
		LEFT JOIN cj_cc_cliente cliente ON ((cxc_fact.idCliente = uni_fis_agregado.id_factura_cxc IS NOT NULL)
			OR (cxc_nd.idCliente = cliente.id AND uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL)
			OR (cxc_nc.idCliente = cliente.id AND uni_fis_agregado.id_nota_credito_cxc IS NOT NULL)
			OR (orden.id_cliente = cliente.id AND uni_fis_agregado.id_vale_salida IS NOT NULL))
		LEFT JOIN pg_motivo motivo ON ((cxp_nd.id_motivo = motivo.id_motivo AND uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL)
			OR (cxp_nc.id_motivo = motivo.id_motivo AND uni_fis_agregado.id_nota_credito_cxp IS NOT NULL))
		LEFT JOIN vw_pg_empleados vw_pg_empleado_registro ON (uni_fis_agregado.id_empleado_registro = vw_pg_empleado_registro.id_empleado)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (uni_fis_agregado.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	WHERE uni_fis_agregado.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rsUniFisAgregado = mysql_query($queryUniFisAgregado);
	if (!$rsUniFisAgregado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsUniFisAgregado = mysql_num_rows($rsUniFisAgregado);
	while ($rowUniFisAgregado = mysql_fetch_array($rsUniFisAgregado)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$hddIdUnidadFisicaAgregado = $rowUniFisAgregado['id_unidad_fisica_agregado'];
		$idTipoMovimientoAgregado = $rowUniFisAgregado['id_tipo_movimiento'];
		$tipoDctoAgregado = $rowUniFisAgregado['tipoDocumento'];
		$idDocumento = $rowUniFisAgregado['id_documento'];
		
		$fechaDcto = $rowUniFisAgregado['fecha_origen'];
		$nroDcto = $rowUniFisAgregado['numero_documento'];
		$idModulo = $rowUniFisAgregado['id_modulo'];
		$nombreCliente = $rowUniFisAgregado['nombre_cliente'];
		$serialChasis = $rowUniFisAgregado['serial_carroceria'];
		$numeroPlaca = $rowUniFisAgregado['placa'];
		$estadoDcto = $rowUniFisAgregado['estatus_documento'];
		$descripcionEstadoDcto = $rowUniFisAgregado['descripcion_estatus_documento'];
		$observacionDcto = $rowUniFisAgregado['observacion_documento'];
		$saldoDcto = $rowUniFisAgregado['saldo_documento'];
		$txtTotalDcto = $rowUniFisAgregado['total_documento'];
		
		if (in_array($idModulo,array(1)) && in_array($tipoDctoAgregado,array('VS','VE'))) {
			switch($estadoDcto) {
				case 0 : $class = "class=\"divMsjInfo\""; break;
				case 1 : $class = "class=\"divMsjAlerta\""; break;
			}
		} else {
			switch($estadoDcto) {
				case 0 : $class = "class=\"divMsjError\""; break;
				case 1 : $class = "class=\"divMsjInfo\""; break;
				case 2 : $class = "class=\"divMsjAlerta\""; break;
				case 3 : $class = "class=\"divMsjInfo3\""; break;
				case 4 : $class = "class=\"divMsjInfo4\""; break;
			}
		}
		
		switch($idModulo) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgDctoModulo = $idModulo;
		}
		
		if ($totalRowsUniFisAgregado > 0) {
			$classUniFisAgregado = ($rowUniFisAgregado['estatus'] != 1) ? "class=\"divMsjError\"" : "";
			$estatusUniFisAgregado = ($rowUniFisAgregado['estatus'] != 1) ? "<div align=\"center\">RELACION ANULADA</div>" : "";
			$empleadoAnuladoUniFisAgregado = (strlen($rowUniFisAgregado['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$rowUniFisAgregado['nombre_empleado_anulado']."<br>(".date(spanDateFormat, strtotime($rowUniFisAgregado['fecha_anulado'])).")</span></div>" : "";
		}
		
		switch ($idTipoMovimientoAgregado) {
			case 1 : // 1 = COMPRA
				switch ($idModulo) {
					case 0 : $aVerDctoAux = "../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=".$idDocumento.""; break;
					case 2 : $aVerDctoAux = "../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=".$idDocumento.""; break;
					default : $aVerDctoAux = "";
				}
				$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Registro Compra PDF\"/><a>" : "";
				break;
			case 2 : // 2 = ENTRADA
				switch ($tipoDctoAgregado) {
					case "ND" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana(\'../cxp/reportes/cp_nota_cargo_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
							$idDocumento);
						break;
					case "NC" :
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/><a>",
							$idDocumento);
						switch ($idModulo) {
							case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
						break;
					default : $aVerDcto = "";
				}
				break;
			case 3 : // 3 = VENTA
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
					$idDocumento);
				break;
			case 4 : // 4 = SALIDA
				switch ($tipoDctoAgregado) {
					case "ND" :
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_debito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/><a>",
							$idDocumento);
						$aVerDcto .= sprintf("<a href=\"javascript:verVentana(\'../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
							$idDocumento);
						break;
					case "NC" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana(\'../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>",
							$idDocumento);
						break;
					case "VS" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana(\'../servicios/reportes/sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|3\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Vale de Salida PDF")."\"/></a>",
							$idDocumento);
						break;
					default : $aVerDcto = "";
				}
				break;
			default : $aVerDcto = "";
		}
		
		$cbxItm = ($totalRowsUniFisAgregado > 0) ? "" : sprintf("<input id=\"cbxItm%s\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>",
			$contFila, $contFila);
		
		// INSERTA EL ARTICULO SIN INJECT
		$objResponse->script(sprintf("$('#trItmPie').before('".
			"<tr align=\"left\" id=\"trItm:%s\" class=\"%s\">".
				"<td title=\"trItm:%s\">%s".
					"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
				"<td align=\"center\">%s".
					"<input type=\"text\" id=\"txtTipoDcto%s\" name=\"txtTipoDcto%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
				"<td align=\"center\" title=\"%s\">%s</td>".
				"<td %s>".
					"<table width=\"%s\">".
					"<tr align=\"right\">".
						"<td nowrap=\"nowrap\">%s</td>".
						"<td>%s</td>".
						"<td width=\"%s\">%s</td>".
					"</tr>".
					"</table>".
					"%s".
					"%s</td>".
				"<td>".
					"<table width=\"%s\"><tr><td colspan=\"2\">%s</td></tr>".
					"<tr class=\"textoNegrita_10px\">".
						"%s".
						"%s".
					"</tr>".
					"%s".
					"%s".
					"</table>".
				"</td>".
				"<td align=\"center\" %s>%s</td>".
				"<td align=\"right\">%s</td>".
				"<td><input type=\"text\" id=\"txtTotalDcto%s\" name=\"txtTotalDcto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdUnidadFisicaAgregado%s\" name=\"hddIdUnidadFisicaAgregado%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdDctoAgregado%s\" name=\"hddIdDctoAgregado%s\" value=\"%s\"/></td>".
			"</tr>');",
			$contFila, $clase,
				$contFila, $cbxItm,
					$contFila,
				$contFila, $contFila,
				utf8_encode($rowUniFisAgregado['descripcion_tipo_movimiento']),
					$contFila, $contFila, utf8_encode($tipoDctoAgregado),
				"Registrado por: ".$rowUniFisAgregado['nombre_empleado_registro'], date(spanDateFormat, strtotime($fechaDcto)),
				$classUniFisAgregado,
					"100%",
						$aVerDcto,
						$imgDctoModulo,
						"100%", $nroDcto,
					$estatusUniFisAgregado,
					$empleadoAnuladoUniFisAgregado,
				"100%", $nombreCliente,
					((strlen($serialChasis) > 0) ? "<td width=\"50%\">".$serialChasis."</td>" : ""),
					((strlen($numeroPlaca) > 0) ? "<td width=\"50%\">".$numeroPlaca."</td>" : ""),
					((strlen($rowUniFisAgregado['descripcion_motivo']) > 0) ? "<tr><td colspan=\"2\"><span class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($rowUniFisAgregado['descripcion_motivo'])."</span></td></tr>" : ""),
					((strlen($observacionDcto) > 0) ? "<tr><td colspan=\"2\"><span class=\"textoNegritaCursiva_9px\">".utf8_encode($observacionDcto)."</span></td></tr>" : ""),
				$class, $descripcionEstadoDcto,
				number_format($saldoDcto, 2, ".", ","),
				$contFila, $contFila, number_format(((
						(in_array($idTipoMovimientoAgregado,array(1)) && in_array($tipoDctoAgregado,array("FA")))
						|| (in_array($idTipoMovimientoAgregado,array(2)) && in_array($tipoDctoAgregado,array("ND")))
						|| (in_array($idTipoMovimientoAgregado,array(2)) && in_array($tipoDctoAgregado,array("NC")))
						|| (in_array($idTipoMovimientoAgregado,array(4)) && in_array($tipoDctoAgregado,array("VS")))
					) ? 1 : (-1)) * $txtTotalDcto, 2, ".", ","),
					$contFila, $contFila, $hddIdUnidadFisicaAgregado,
					$contFila, $contFila, $idDocumento));
	}
	
	$objResponse->script("xajax_calcularAgregado(xajax.getFormValues('frmUnidadFisica'));");
	
	//$objResponse->assign("divListaUniFisAgregado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function imprimirUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstMarcaBuscar']) ? implode(",",$frmBuscar['lstMarcaBuscar']) : $frmBuscar['lstMarcaBuscar']),
		(is_array($frmBuscar['lstModeloBuscar']) ? implode(",",$frmBuscar['lstModeloBuscar']) : $frmBuscar['lstModeloBuscar']),
		(is_array($frmBuscar['lstVersionBuscar']) ? implode(",",$frmBuscar['lstVersionBuscar']) : $frmBuscar['lstVersionBuscar']),
		(is_array($frmBuscar['lstAnoBuscar']) ? implode(",",$frmBuscar['lstAnoBuscar']) : $frmBuscar['lstAnoBuscar']),
		(is_array($frmBuscar['lstEstadoCompraBuscar']) ? implode(",",$frmBuscar['lstEstadoCompraBuscar']) : $frmBuscar['lstEstadoCompraBuscar']),
		(is_array($frmBuscar['lstEstadoVentaBuscar']) ? implode(",",$frmBuscar['lstEstadoVentaBuscar']) : $frmBuscar['lstEstadoVentaBuscar']),
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		(is_array($frmBuscar['lstAlmacenBuscar']) ? implode(",",$frmBuscar['lstAlmacenBuscar']) : $frmBuscar['lstAlmacenBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/an_consulta_existencia_pdf.php?valBusq=%s', 960, 550)", $valBusq));
	
	return $objResponse;
}

function listaKardex($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	global $spanSerialCarroceria;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_unidad_fisica = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT 
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		CONCAT(vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
	FROM an_kardex kardex
		INNER JOIN an_unidad_fisica uni_fis ON (kardex.idUnidadFisica = uni_fis.id_unidad_fisica)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas) %s
	GROUP BY 1,2,3", $sqlBusq);
	
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
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$idUnidadBasica = $row['id_uni_bas'];
		
		$queryArticuloSaldoAnt = sprintf("SELECT
			(IFNULL((SELECT SUM(k.cantidad) FROM an_kardex k
			WHERE k.idUnidadBasica = %s
				AND DATE(k.fechaMovimiento) < %s
				AND k.tipoMovimiento IN (1,2)),0)
			-
			IFNULL((SELECT SUM(k.cantidad) FROM an_kardex k
			WHERE k.idUnidadBasica = %s
				AND DATE(k.fechaMovimiento) < %s
				AND k.tipoMovimiento IN (3,4)),0)) AS saldo_anterior",
			valTpDato($idUnidadBasica, "int"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato($idUnidadBasica, "int"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"));
		$rsArticuloSaldoAnt = mysql_query($queryArticuloSaldoAnt);
		if (!$rsArticuloSaldoAnt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticuloSaldoAnt = mysql_fetch_assoc($rsArticuloSaldoAnt);
		
		$htmlTh = "<tr align=\"left\" height=\"24\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"3\" title=\"".$idUnidadBasica."\">"."Unidad Básica:"."</a></td>";
			$htmlTh .= "<td colspan=\"9\">".htmlentities($row['nom_uni_bas'])."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"left\" height=\"24\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"3\">"."Descripción:"."</td>";
			$htmlTh .= "<td colspan=\"9\">".htmlentities($row['vehiculo'])."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"2\"></td>";
			$htmlTh .= "<td width=\"8%\">Fecha</td>";
			$htmlTh .= "<td width=\"14%\">Empresa</td>";
			$htmlTh .= "<td width=\"12%\">".$spanSerialCarroceria."</td>";
			$htmlTh .= "<td width=\"4%\">T</td>";
			$htmlTh .= "<td width=\"8%\">Nro. Documento</td>";
			$htmlTh .= "<td colspan=\"3\" width=\"26%\">C/P/M</td>";
			$htmlTh .= "<td width=\"6%\">E/S</td>";
			$htmlTh .= "<td width=\"6%\">Saldo</td>";
			$htmlTh .= "<td width=\"8%\">".$spanPrecioUnitario."</td>";
			$htmlTh .= "<td width=\"8%\">Costo Unit.</td>";
		$htmlTh .= "</tr>";
		
		$cantSaldoAnterior = $rowArticuloSaldoAnt['saldo_anterior'];
		
		if ($cantSaldoAnterior != 0) {
			$clase = "trResaltar5";
			
			$totalEntrada = $cantSaldoAnterior;
			$entradaSalida = $cantSaldoAnterior;
			
			$htmlTh .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTh .= "<td colspan=\"2\"></td>";
				$htmlTh .= "<td class=\"divMsjInfo\" colspan=\"5\">Saldo Anterior al Intervalo de Fecha Seleccionado:</td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td align=\"right\">".number_format($cantSaldoAnterior, 2, ".", ",")."</td>";
				$htmlTh .= "<td></td>";
				$htmlTh .= "<td></td>";
			$htmlTh .= "</tr>";
		}
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_bas.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'],"int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_unidad_fisica = %s",
			valTpDato($valCadBusq[0], "int"));
		
		$queryDetalle = sprintf("SELECT
			kardex.idKardex,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS id_empresa,
			
			(SELECT
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM vw_iv_empresas_sucursales vw_iv_emp_suc
			WHERE vw_iv_emp_suc.id_empresa_reg = (CASE kardex.tipoMovimiento
													WHEN 1 THEN -- COMPRA
														(SELECT cxp_fact.id_empresa FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
													WHEN 2 THEN -- ENTRADA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- ENTRADA CON VALE
																(SELECT an_ve.id_empresa FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
															WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
																(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
															WHEN 3 THEN -- ENTRADA CON CONTRATO
																(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
														END)
													WHEN 3 THEN -- VENTA
														(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
													WHEN 4 THEN -- SALIDA
														(CASE kardex.tipo_documento_movimiento
															WHEN 1 THEN -- SALIDA CON VALE
																(SELECT an_vs.id_empresa FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
															WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
																(SELECT cxp_nc.id_empresa FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
															WHEN 3 THEN -- SALIDA CON CONTRATO
																(SELECT al_contrato.id_empresa FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
														END)
												END)) AS nombre_empresa,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			uni_fis.tipo_placa,
			cond_unidad.descripcion AS condicion_unidad,
			kardex.id_documento,
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT cxp_fact.numero_factura_proveedor FROM cp_factura cxp_fact WHERE cxp_fact.id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(SELECT an_ve.numeracion_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = kardex.id_documento)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							(SELECT al_contrato.numero_contrato_venta FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT an_vs.numeracion_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT al_contrato.numero_contrato_venta FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS numero_documento,
			
			2 AS id_modulo,
			(CASE 2
				WHEN 0 THEN		'R'
				WHEN 1 THEN		'S'
				WHEN 2 THEN		'V'
				WHEN 3 THEN		'C'
				WHEN 4 THEN		'AL'
			END) AS nombre_modulo,
			
			kardex.tipoMovimiento,
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN	'C'
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE (SELECT an_ve.tipo_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
								WHEN 4 THEN		'E-TRNS.ALM'
								ELSE			'E'
							END)
						WHEN 2 THEN
							'E-NC'
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							'E-ALQUILER'
					END)
				WHEN 3 THEN 'V'
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(CASE (SELECT an_vs.tipo_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
								WHEN 4 THEN		'S-TRNS.ALM'
								ELSE			'S'
							END)
						WHEN 2 THEN
							'S-NC'
						WHEN 3 THEN -- SALIDA CON CONTRATO
							'S-ALQUILER'
					END)
			END) AS nombre_tipo_movimiento,
			
			kardex.claveKardex,
			kardex.tipo_documento_movimiento,
			kardex.estadoKardex,
			kardex.fechaMovimiento,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT prov.id_proveedor AS idPCE
					FROM cp_factura cxp_fact
						INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
					WHERE cxp_fact.id_factura = kardex.id_documento
						AND cxp_fact.id_modulo IN (2))
				WHEN 2 THEN -- ENTRADA
					(SELECT cliente.id AS idPCE FROM cj_cc_cliente cliente
					WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
											WHEN 1 THEN -- ENTRADA CON VALE
												(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
												(SELECT cxc_nc.idCliente
												FROM cj_cc_notacredito cxc_nc
													INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
												WHERE cxc_nc.idNotaCredito = kardex.id_documento
													AND cxc_nc.idDepartamentoNotaCredito IN (2)
													AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
											WHEN 3 THEN -- ENTRADA CON CONTRATO
												(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
										END))
				WHEN 3 THEN -- VENTA
					(SELECT cliente.id AS idPCE
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
					WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT cliente.id AS idPCE
							FROM an_vale_salida vale_sal
								INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
							WHERE vale_sal.id_vale_salida = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT cliente.id AS idPCE
							FROM al_contrato_venta al_contrato
								INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
							WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS idPCE,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN -- COMPRA
					(SELECT CONCAT_WS('-', prov.lrif, prov.rif) AS ciPCE
					FROM cp_factura cxp_fact
						INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
					WHERE cxp_fact.id_factura = kardex.id_documento
						AND cxp_fact.id_modulo IN (2))
				WHEN 2 THEN -- ENTRADA
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE FROM cj_cc_cliente cliente
					WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
											WHEN 1 THEN -- ENTRADA CON VALE
												(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
												(SELECT cxc_nc.idCliente
												FROM cj_cc_notacredito cxc_nc
													INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
												WHERE cxc_nc.idNotaCredito = kardex.id_documento
													AND cxc_nc.idDepartamentoNotaCredito IN (2)
													AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
											WHEN 3 THEN -- ENTRADA CON CONTRATO
												(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
										END))
				WHEN 3 THEN -- VENTA
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
					WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
							FROM an_vale_salida vale_sal
								INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
							WHERE vale_sal.id_vale_salida = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ciPCE
							FROM al_contrato_venta al_contrato
								INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
							WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS ciPCE,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN
					(SELECT prov.nombre
					FROM cp_factura cxp_fact
						INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
					WHERE cxp_fact.id_factura = kardex.id_documento
						AND cxp_fact.id_modulo IN (2))
				WHEN 2 THEN
					(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE FROM cj_cc_cliente cliente
					WHERE cliente.id = (CASE kardex.tipo_documento_movimiento
											WHEN 1 THEN -- ENTRADA CON VALE
												(SELECT an_ve.id_cliente FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
											WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
												(SELECT cxc_nc.idCliente
												FROM cj_cc_notacredito cxc_nc
													INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
												WHERE cxc_nc.idNotaCredito = kardex.id_documento
													AND cxc_nc.idDepartamentoNotaCredito IN (2)
													AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
											WHEN 3 THEN -- ENTRADA CON CONTRATO
												(SELECT al_contrato.id_cliente FROM al_contrato_venta al_contrato WHERE al_contrato.id_contrato_venta = kardex.id_documento)
										END))
				WHEN 3 THEN
					(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
					FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
					WHERE cxc_fact.idFactura = kardex.id_documento)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
							FROM an_vale_salida vale_sal
								INNER JOIN cj_cc_cliente cliente ON (vale_sal.id_cliente = cliente.id)
							WHERE vale_sal.id_vale_salida = kardex.id_documento)
						WHEN 3 THEN -- SALIDA CON CONTRATO
							(SELECT CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombrePCE
							FROM al_contrato_venta al_contrato
								INNER JOIN cj_cc_cliente cliente ON (al_contrato.id_cliente = cliente.id)
							WHERE al_contrato.id_contrato_venta = kardex.id_documento)
					END)
			END) AS nombrePCE,
			
			(CASE kardex.tipoMovimiento
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT an_ve.tipo_vale_entrada FROM an_vale_entrada an_ve WHERE an_ve.id_vale_entrada = kardex.id_documento)
					END)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							(SELECT an_vs.tipo_vale_salida FROM an_vale_salida an_vs WHERE an_vs.id_vale_salida = kardex.id_documento)
					END)
			END) AS tipo_vale,
			
			kardex.cantidad,
			kardex.precio,
			kardex.costo,
			kardex.costo_cargo,
			kardex.porcentaje_descuento,
			kardex.subtotal_descuento,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN
					uni_fis.precio_compra
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							(SELECT cxc_fact_det_vehic.precio_unitario
							FROM cj_cc_notacredito cxc_nc
								INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
							WHERE cxc_nc.idNotaCredito = kardex.id_documento
								AND cxc_nc.idDepartamentoNotaCredito IN (2)
								AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							uni_fis.precio_compra
					END)
				WHEN 3 THEN
					(SELECT cxc_fact_det_vehic.precio_unitario FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					WHERE cxc_fact_det_vehic.id_factura = kardex.id_documento
						AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							uni_fis.precio_compra
						WHEN 3 THEN -- SALIDA CON CONTRATO
							uni_fis.precio_compra
					END)
			END) AS precio_unidad_dcto,
			
			(CASE kardex.tipoMovimiento
				WHEN 1 THEN
					uni_fis.precio_compra
				WHEN 2 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							(SELECT cxc_fact_det_vehic.costo_compra
							FROM cj_cc_notacredito cxc_nc
								INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_nc.idDocumento = cxc_fact_det_vehic.id_factura)
							WHERE cxc_nc.idNotaCredito =kardex.id_documento
								AND cxc_nc.idDepartamentoNotaCredito IN (2)
								AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
						WHEN 3 THEN -- ENTRADA CON CONTRATO
							uni_fis.precio_compra
					END)
				WHEN 3 THEN
					(SELECT cxc_fact_det_vehic.costo_compra FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					WHERE cxc_fact_det_vehic.id_factura = kardex.id_documento
						AND cxc_fact_det_vehic.id_unidad_fisica = kardex.idUnidadFisica)
				WHEN 4 THEN
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN
							uni_fis.precio_compra
						WHEN 2 THEN
							uni_fis.precio_compra
						WHEN 3 THEN -- SALIDA CON CONTRATO
							uni_fis.precio_compra
					END)
			END) AS costo_unidad_dcto
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_kardex kardex ON (uni_fis.id_unidad_fisica = kardex.idUnidadFisica)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas) %s
		ORDER BY kardex.fechaMovimiento ASC, kardex.idKardex ASC", $sqlBusq2);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$contFila2 = 0;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			$updateSQL = sprintf("UPDATE an_kardex SET
				precio = %s
			WHERE idKardex = %s
				AND precio = 0;",
				valTpDato($rowDetalle['precio_unidad_dcto'], "real_inglesa"),
				valTpDato($rowDetalle['idKardex'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$updateSQL = sprintf("UPDATE an_kardex SET
				costo = %s
			WHERE idKardex = %s
				AND costo = 0;",
				valTpDato($rowDetalle['costo_unidad_dcto'], "real_inglesa"),
				valTpDato($rowDetalle['idKardex'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$idModulo = $rowDetalle['id_modulo'];
			
			switch ($rowDetalle['tipoMovimiento']) {
				case 1 : // COMPRA
					$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
					$precioUnitario = $costoUnitario; break;
				case 2 : // ENTRADA
					switch($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE
							$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
							$precioUnitario = ($idModulo == 1) ? $rowDetalle['precio'] : $costoUnitario;
							break;
						case 2 : // NOTA CREDITO
							$costoUnitario = $rowDetalle['costo'];
							$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
							break;
						case 3 : // CONTRATO
							$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
							$precioUnitario = ($idModulo == 1) ? $rowDetalle['precio'] : $costoUnitario;
							break;
					}
					break;
				case 3 : // VENTA
					$costoUnitario = $rowDetalle['costo'];
					$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
				case 4 : // SALIDA
					switch($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE
							$costoUnitario = $rowDetalle['costo'];
							$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
							break;
						case 2 : // NOTA CREDITO
							$costoUnitario = $rowDetalle['costo'] + $rowDetalle['costo_cargo'] - $rowDetalle['subtotal_descuento']; 
							$precioUnitario = $costoUnitario;
							break;
						case 3 : // CONTRATO
							$costoUnitario = $rowDetalle['costo'];
							$precioUnitario = $rowDetalle['precio'] - $rowDetalle['subtotal_descuento']; break;
							break;
					}
					break;
			}
			
			if ($rowDetalle['estadoKardex'] == 0) {
				$totalEntrada += $rowDetalle['cantidad'];
				$totalValorEntradaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
				$totalValorEntradaCosto += $rowDetalle['cantidad'] * $costoUnitario;
				$entradaSalida += $rowDetalle['cantidad'];
			} else if ($rowDetalle['estadoKardex'] == 1) {
				$totalSalida += $rowDetalle['cantidad'];
				$totalValorSalidaPrecio += $rowDetalle['cantidad'] * $precioUnitario;
				$totalValorSalidaCosto += $rowDetalle['cantidad'] * $costoUnitario;
				$entradaSalida -= $rowDetalle['cantidad'];
			}
			
			$imgInterAlmacen = ($rowDetalle['nombre_tipo_movimiento'] == "E-TRNS.ALM" || $rowDetalle['nombre_tipo_movimiento'] == "S-TRNS.ALM") ? "<img src=\"../img/iconos/ico_cambio.png\"/>" : "";
			
			switch ($idModulo) {
				case 0 : $imgModuloDcto = "<img src=\"../img/iconos/ico_repuestos.gif\"/ title=\"Repuestos\">"; break;
				case 1 : $imgModuloDcto = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgModuloDcto = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
				case 3 : $imgModuloDcto = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
				case 4 : $imgModuloDcto = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				default : $imgModuloDcto = "";
			}
			
			switch ($rowDetalle['tipoMovimiento']) {
				case 1 : // COMPRA
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_factura_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Registro Compra")."\"/><a>",
						$rowDetalle['id_documento']);
					switch ($idModulo) {
						case 0: $aVerDctoAux = sprintf("../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 2: $aVerDctoAux = sprintf("../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Registro Compra PDF")."\"/></a>" : "";
					break;
				case 2 : // ENTRADA
					switch ($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE
							switch ($idModulo) {
								case 0 : $aVerDctoAux = "../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowDetalle['id_documento']."|2"; break;
								case 1 : $aVerDctoAux = "../servicios/sa_devolucion_vale_salida_pdf.php?valBusq=1|".$rowDetalle['id_documento']; break;
								case 2 : $aVerDctoAux = "../vehiculos/reportes/an_ajuste_inventario_vale_entrada_imp.php?id=".$rowDetalle['id_documento']; break;
								default : $aVerDctoAux = "";
							}
							$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Vale Entrada PDF")."\"/></a>" : "";
							break;
						case 2 : // NOTA DE CREDITO
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota Crédito")."\"/><a>",
								$rowDetalle['id_documento']);
							switch ($idModulo) {
								case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
								default : $aVerDctoAux = "";
							}
							$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota Crédito PDF")."\"/></a>" : "";
							break;
						case 3 : // CONTRATO
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('../alquiler/reportes/al_contrato_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Contrato PDF")."\"/></a>",
								$rowDetalle['id_documento']);
							break;
					}
					break;
				case 3 : // VENTA
					$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
						$rowDetalle['id_documento']);
					switch ($idModulo) {
						case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_factura_venta_pdf.php?valBusq=%s", $rowDetalle['id_documento']); break;
						default : $aVerDctoAux = "";
					}
					$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>" : "";
					break;
				case 4 : // SALIDA
					switch ($rowDetalle['tipo_documento_movimiento']) {
						case 1 : // VALE
							switch ($idModulo) {
								case 0 : $aVerDctoAux = "../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowDetalle['id_documento']."|4"; break;
								case 1 : $aVerDctoAux = "../servicios/reportes/sa_imprimir_presupuesto_pdf.php?valBusq=".$rowDetalle['id_documento']."|2|3"; break;
								case 2 : $aVerDctoAux = "../vehiculos/reportes/an_ajuste_inventario_vale_salida_imp.php?id=".$rowDetalle['id_documento']; break;
								default : $aVerDctoAux = "";
							}
							$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Vale Salida PDF")."\"/></a>" : "";
							break;
						case 2 : // NOTA DE CREDITO
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_nota_credito_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota Crédito")."\"/><a>",
								$rowDetalle['id_documento']);
							$aVerDcto .= sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota Crédito PDF")."\"/></a>",
								$rowDetalle['id_documento']);
							break;
						case 3 : // CONTRATO
							$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('../alquiler/reportes/al_contrato_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Contrato PDF")."\"/></a>",
								$rowDetalle['id_documento']);
							break;
					}
					break;
				default : $aVerDcto = "";
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" title=\"Id. Kardex: ".$rowDetalle['idKardex']."\">".$contFila2."</td>";
				$htmlTb .= "<td>".$imgInterAlmacen."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\" title=\"".date("h:i:s a", strtotime($rowDetalle['fechaMovimiento']))."\">".date(spanDateFormat, strtotime($rowDetalle['fechaMovimiento']))."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombre_empresa'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".utf8_encode($rowDetalle['serial_carroceria'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr class=\"textoNegrita_10px\">";
						$htmlTb .= "<td>".utf8_encode($rowDetalle['condicion_unidad'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowDetalle['nombre_tipo_movimiento'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
						$htmlTb .= "<td>".$imgModuloDcto."</td>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowDetalle['numero_documento'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($rowDetalle['idPCE'])."</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($rowDetalle['ciPCE'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombrePCE'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowDetalle['cantidad'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($entradaSalida, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($precioUnitario, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".cAbrevMoneda.number_format($costoUnitario, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\">";
			$htmlTb .= "<td colspan=\"9\" class=\"tituloCampo\">Totales:<br>".htmlentities($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td class=\"tituloColumna\" colspan=\"2\">E #:<br>S #:</td>";
			$htmlTb .= "<td>".number_format($totalEntrada, 2, ".", ",")."<br>".number_format($totalSalida, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".cAbrevMoneda.number_format($totalValorEntradaPrecio, 2, ".", ",")."<br>".cAbrevMoneda.number_format($totalValorSalidaPrecio, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".cAbrevMoneda.number_format($totalValorEntradaCosto, 2, ".", ",")."<br>".cAbrevMoneda.number_format($totalValorSalidaCosto, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaKardex(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardex(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaKardex","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaTradeInAuditoria($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tradein.id_tradein = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT *
	FROM an_tradein tradein
		INNER JOIN an_tradein_auditoria tradein_audit ON (tradein.id_tradein = tradein_audit.id_tradein)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (tradein_audit.id_empleado_registro = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaTradeInAuditoria", "16%", $pageNum, "tiempo_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaTradeInAuditoria", "24%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Registrado por");
		$htmlTh .= ordenarCampo("xajax_listaTradeInAuditoria", "14%", $pageNum, "allowance", $campOrd, $tpOrd, $valBusq, $maxRows, "Allowance");
		$htmlTh .= ordenarCampo("xajax_listaTradeInAuditoria", "14%", $pageNum, "acv", $campOrd, $tpOrd, $valBusq, $maxRows, "ACV");
		$htmlTh .= ordenarCampo("xajax_listaTradeInAuditoria", "14%", $pageNum, "payoff", $campOrd, $tpOrd, $valBusq, $maxRows, "Payoff");
		$htmlTh .= ordenarCampo("xajax_listaTradeInAuditoria", "14%", $pageNum, "total_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Crédito Neto");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado']) > 0) ? "title=\"Registrado por: ".$row['nombre_empleado']."\"" : "").">".date(spanDateFormat." h:i:s a", strtotime($row['tiempo_registro']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['allowance'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['acv'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['payoff'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_credito'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTradeInAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTradeInAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTradeInAuditoria(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTradeInAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTradeInAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaTradeInAuditoria","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	global $spanKilometraje;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO')");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_ano IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[5])."'", "defined", "'".str_replace(",","','",$valCadBusq[5])."'"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
			valTpDato($valCadBusq[7], "campo"));
	}
		
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
			valTpDato($valCadBusq[8], "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s
		OR cxp_fact.numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		(CASE vw_iv_modelo.catalogo
			WHEN 0 THEN ''
			WHEN 1 THEN 'En Catálogo'
		END) AS mostrar_catalogo
	FROM an_unidad_fisica uni_fis
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
		
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "uni_fis.id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id Unidad Física"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, ("Color"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "kilometraje", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanKilometraje));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "fecha_expiracion_marbete", $campOrd, $tpOrd, $valBusq, $maxRows, ("Expiración Marbete"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Ingreso"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estado Venta"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, ("Almacén"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Asignación"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Fact. Compra"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "precio_compra", $campOrd, $tpOrd, $valBusq, $maxRows, ("Costo"));
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"30\">".($row['vehiculo'].((strlen($row['mostrar_catalogo']) > 0) ? " <b>[".$row['mostrar_catalogo']."]</b>" : ""))."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'], "int"));
		
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.titulo_vehiculo,
			uni_fis.placa,
			uni_fis.tipo_placa,
			uni_fis.id_condicion_unidad,
			cond_unidad.descripcion AS condicion_unidad,
			uni_fis.kilometraje,
			uni_fis.fecha_expiracion_marbete,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.id_factura,
			cxp_fact.numero_factura_proveedor,
			cxp_fact.id_modulo AS id_modulo_cxp,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			
			(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
			WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
				LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
				LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
				LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND an_ve.fecha IS NOT NULL
				AND an_ve.tipo_vale_entrada = 1
				AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
		$queryUnidadFisica .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayTotal = NULL;
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			switch($rowUnidadFisica['estado_venta']) {
				case "SINIESTRADO" : $class = "class=\"divMsjError\""; break;
				case "DISPONIBLE" : $class = "class=\"divMsjInfo\""; break;
				case "RESERVADO" : $class = "class=\"divMsjAlerta\""; break;
				case "VENDIDO" : $class = "class=\"divMsjInfo3\""; break;
				case "ENTREGADO" : $class = "class=\"divMsjInfo4\""; break;
				case "PRESTADO" : $class = "class=\"divMsjInfo2\""; break;
				case "ACTIVO FIJO" : $class = "class=\"divMsjInfo5\""; break;
				case "DEVUELTO" : $class = "class=\"divMsjInfo6\""; break;
				default : $class = ""; break;
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</div>";
					$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode($rowUnidadFisica['condicion_unidad'])."</div>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowUnidadFisica['kilometraje'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($rowUnidadFisica['fecha_expiracion_marbete'] != "") ? date(spanDateFormat, strtotime($rowUnidadFisica['fecha_expiracion_marbete'])) : "")."</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= (($rowUnidadFisica['fecha_origen'] != "") ? "<div>".date(spanDateFormat, strtotime($rowUnidadFisica['fecha_origen']))."</div>" : "");
					$htmlTb .= (($rowUnidadFisica['dias_inventario'] > 0) ? "<div class=\"textoNegrita_9px\">".($rowUnidadFisica['dias_inventario']." días")."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['estado_venta'])."</div>";
					$htmlTb .= (($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<div class=\"textoNegrita_9px\">(".utf8_encode($rowUnidadFisica['estado_compra']).")</div>" : "");
					$htmlTb .= (($rowUnidadFisica['id_activo_fijo'] > 0) ? "<div class=\"textoNegrita_9px\">".("Código: ".$rowUnidadFisica['id_activo_fijo'])."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td align=\"right\">";
					$objDcto = new Documento;
					$objDcto->raizDir = $raiz;
					$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
					$objDcto->tipoDocumento = "FA";
					$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
					$objDcto->idModulo = $rowUnidadFisica['id_modulo_cxp'];
					$objDcto->idDocumento = $rowUnidadFisica['id_factura'];
					$aVerDcto = $objDcto->verDocumento();
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowUnidadFisica['numero_factura_proveedor'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">";
					$htmlTb .= "<div>".number_format($rowUnidadFisica['precio_compra'], 2, ".", ",")."</div>";
					$htmlTb .= (($rowUnidadFisica['costo_agregado'] != 0) ? "<div class=\"".(($rowUnidadFisica['costo_agregado'] > 0) ? "textoVerdeNegrita_10px" : "textoRojoNegrita_10px")."\" title=\"".htmlentities("Total Agregados")."\">[".number_format($rowUnidadFisica['costo_agregado'], 2, ".", ",")."]</div>" : "");
					$htmlTb .= (($rowUnidadFisica['costo_depreciado'] != 0) ? "<div class=\"textoRojoNegrita_10px\" title=\"".htmlentities("Total Depreciación")."\">[-".number_format($rowUnidadFisica['costo_depreciado'], 2, ".", ",")."]</div>" : "");
					$htmlTb .= (($rowUnidadFisica['costo_trade_in'] != 0) ? "<div class=\"".(($rowUnidadFisica['costo_trade_in'] > 0) ? "textoRojoNegrita_10px" : "textoVerdeNegrita_10px")."\" title=\"".htmlentities("Total Depreciación Ingreso por Trade In")."\">[".number_format(((-1) * $rowUnidadFisica['costo_trade_in']), 2, ".", ",")."]</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadFisica', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Unidad Física\"/></a>",
						$contFila,
						$rowUnidadFisica['id_unidad_fisica']);
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			
			$arrayCondicionUnidad[$rowUnidadFisica['condicion_unidad']] += 1;
			
			$arrayTotal['cant_unidades'] += 1;
			$arrayTotal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">"."Subtotal:<br>".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_unidades'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['precio_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalFinal['cant_unidades'] += $arrayTotal['cant_unidades'];
		$arrayTotalFinal['precio_compra'] += $arrayTotal['precio_compra'];
	}
	if ($pageNum == $totalPages || isset($arrayCondicionUnidad)) {
		if ($totalPages > 0) {
			$queryUnidadFisica = sprintf("SELECT
				uni_fis.id_unidad_fisica,
				uni_fis.id_activo_fijo,
				uni_fis.serial_carroceria,
				uni_fis.serial_motor,
				uni_fis.serial_chasis,
				uni_fis.titulo_vehiculo,
				uni_fis.placa,
				uni_fis.tipo_placa,
				uni_fis.id_condicion_unidad,
				cond_unidad.descripcion AS condicion_unidad,
				uni_fis.kilometraje,
				uni_fis.fecha_expiracion_marbete,
				color_ext.nom_color AS color_externo1,
				color_int.nom_color AS color_interno1,
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
					WHEN (an_ve.fecha IS NOT NULL) THEN
						an_ve.fecha
				END) AS fecha_origen,
				IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
					(CASE
						WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
							TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
						WHEN (an_ve.fecha IS NOT NULL) THEN
							TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
					END),
				0) AS dias_inventario,
				uni_fis.estado_compra,
				uni_fis.estado_venta,
				asig.idAsignacion,
				alm.nom_almacen,
				cxp_fact.id_factura,
				cxp_fact.numero_factura_proveedor,
				cxp_fact.id_modulo AS id_modulo_cxp,
				uni_fis.costo_compra,
				uni_fis.precio_compra,
				uni_fis.costo_agregado,
				uni_fis.costo_depreciado,
				uni_fis.costo_trade_in,
				
				(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
				WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
				
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM an_unidad_fisica uni_fis
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
				INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
				INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
				INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
				INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
				LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
					LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
				LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
					LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
					LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
				LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
					AND an_ve.fecha IS NOT NULL
					AND an_ve.tipo_vale_entrada = 1
					AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s;", $sqlBusq);
			$rsUnidadFisica = mysql_query($queryUnidadFisica);
			$arrayCondicionUnidad = array();
			$arrayTotalFinal = array();
			while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
				$arrayCondicionUnidad[$rowUnidadFisica['condicion_unidad']] += 1;
				
				$arrayTotalFinal['cant_unidades'] += 1;
				$arrayTotalFinal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
			}
		}
		
		if ($pageNum == $totalPages) {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['cant_unidades'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['precio_compra'], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"3\"></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	if (isset($arrayCondicionUnidad)) {
		$htmlTblIni .= "<tr>";
			$htmlTblIni .= "<td colspan=\"50\">";
			$htmlTblIni .= "<fieldset><legend class=\"legend\">Resúmen de Condición</legend>";
				$htmlTblIni .= "<table width=\"100%\">";
		$contFila = 0;
		foreach ($arrayCondicionUnidad as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTblIni .= (fmod($contFila, 5) == 1) ? "<tr align=\"right\" class=\"".$clase."\" height=\"22\">" : "";
				
				$htmlTblIni .= "<td class=\"tituloCampo\" width=\"14%\">".$indice.":</td>";
				$htmlTblIni .= "<td width=\"6%\">".$valor."</td>";
					
			$htmlTblIni .= (fmod($contFila, 5) == 0) ? "</tr>" : "";
			
			$arrayTotal['cantidad_ordenes'] += $row['cantidad_ordenes'];
			
			if ($contFila == count($arrayCondicionUnidad)) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$htmlTblIni .= (fmod($contFila, 5) == 1) ? "<tr align=\"right\" class=\"".$clase."\" height=\"22\">" : "";
					$htmlTblIni .= "<td class=\"tituloCampo\" width=\"14%\">Total:</td>";
					$htmlTblIni .= "<td class=\"trResaltarTotal\" width=\"6%\">".array_sum($arrayCondicionUnidad)."</td>";
				$htmlTblIni .= (fmod($contFila, 5) == 0) ? "</tr>" : "";
			}
		}
				$htmlTblIni .= "</table>";
			$htmlTblIni .= "</fieldset>";
			$htmlTblIni .= "</td>";
		$htmlTblIni .= "</tr>";
		$htmlTblIni .= "<tr><td colspan=\"50\">&nbsp;</td></tr>";
	}
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"calcularAgregado");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacenBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCompraBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVentaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"exportarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"formUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"formUnidadFisicaAgregado");
$xajax->register(XAJAX_FUNCTION,"imprimirUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"listaKardex");
$xajax->register(XAJAX_FUNCTION,"listaTradeInAuditoria");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice => $valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return NULL;
}
?>