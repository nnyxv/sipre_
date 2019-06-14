<?php

function buscarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstMarcaBuscar'],
		$frmBuscar['lstModeloBuscar'],
		$frmBuscar['lstVersionBuscar'],
		implode(",",$frmBuscar['lstEstadoCompraBuscar']),
		implode(",",$frmBuscar['lstEstadoVentaBuscar']),
		implode(",",$frmBuscar['lstCondicionBuscar']),
		implode(",",$frmBuscar['lstAlmacen']),
		implode(",",$frmBuscar['lstEstadoAdicionalBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstAlmacen($idEmpresa, $selId = "") {
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
	$html = "<select ".((count($totalRows) > 2) ? "multiple": "")." id=\"lstAlmacen\" name=\"lstAlmacen\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".htmlentities($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAlmacen","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCondicionBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[1] = "Nuevo";			$array[2] = "Usado";		$array[3] = "Usado Particular";
	
	$html = "<select ".((count($array) > 2) ? "multiple": "")." id=\"lstCondicionBuscar\" name=\"lstCondicionBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice || count($array) == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicionBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoAdicional($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();

	$query = "SELECT * FROM an_unidad_estado_adicional";
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";	
	while($row = mysql_fetch_assoc($rs)){
		$selected = ($selId == $row["id_estado_adicional"]) ? "selected=\"selected\"" : "";		
		
		$html .= "<option ".$selected." value=\"".($row["id_estado_adicional"])."\">".($row["nombre_estado"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoAdicionalBuscar($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();

	$query = "SELECT * FROM an_unidad_estado_adicional";
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select ".((mysql_num_rows($rs) > 2) ? "multiple": "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";	
	while($row = mysql_fetch_assoc($rs)){
		$selected = ($selId == $row["id_estado_adicional"]) ? "selected=\"selected\"" : "";		
		
		$html .= "<option ".$selected." value=\"".($row["id_estado_adicional"])."\">".($row["nombre_estado"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoCompraBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste") ? $array[] = "ALTA" : "";
	($accion != "Ajuste") ? $array[] = "IMPRESO" : "";
	$array[] = "COMPRADO";
	$array[] = "REGISTRADO";
	($accion != "Ajuste") ? $array[] = "CANCELADO" : "";
	
	$html = "<select ".((count($array) > 2) ? "multiple": "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice] || count($array) == 1) ? "selected=\"selected\"" : "";
		
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
	($accion != "Ajuste") ? $array[] = "ACTIVO FIJO" : "";
	$array[] = "DISPONIBLE";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "RESERVADO" : "";
	($accion != "Ajuste" && $accion != "Venta" && $accion != "Existencia") ? $array[] = "VENDIDO" : "";
	($accion != "Ajuste" && $accion != "Venta" && $accion != "Existencia") ? $array[] = "ENTREGADO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "PRESTADO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "ACTIVO FIJO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "INTERCAMBIO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "DEVUELTO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "ERROR EN TRASPASO" : "";
	
	$html = "<select ".((count($array) > 2) ? "multiple": "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice] || count($array) == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMarcaModeloVersion($tpLst, $idLstOrigen, $nombreObjeto, $padreId = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	switch ($tpLst) {
		case "unidad_basica" : $arraySelec = array("lstPadre","lstMarca","lstModelo","lstVersion");
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	// SI EL OBJETO NO ES EL ULTIMO ASIGNA LA FUNCION PARA CARGAR EL SIGUIENTE OBJETO
	if (($posList + 1) != count($arraySelec) - 1)
		$onChange = "onchange=\"xajax_cargaLstMarcaModeloVersion('".$tpLst."', '".$arraySelec[$posList+1]."', '".$nombreObjeto."', this.value);\"";
	
	
	$html = "<select id=\"".$arraySelec[$posList+1].$nombreObjeto."\" name=\"".$arraySelec[$posList+1].$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:150px\">";
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1' && $nombreObjeto != "Buscar") {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:150px\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html);
			}
		}
		
		return $objResponse;
	} else {
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:150px\">";
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
				WHERE modelo.id_marca = %s
				ORDER BY modelo.nom_modelo;",
					valTpDato($padreId, "int"));
				$campoId = "id_modelo";
				$campoDesc = "nom_modelo";
				break;
			case 2 :
				$query = sprintf("SELECT * FROM an_version vers
				WHERE vers.id_modelo = %s
				ORDER BY vers.nom_version;",
					valTpDato($padreId, "int"));
				$campoId = "id_version";
				$campoDesc = "nom_version";
				break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row[$campoId]."\">".utf8_encode($row[$campoDesc])."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->assign("td".$arraySelec[$posList+1].$nombreObjeto, 'innerHTML', $html);
	
	return $objResponse;
}

function exportarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstMarcaBuscar'],
		$frmBuscar['lstModeloBuscar'],
		$frmBuscar['lstVersionBuscar'],
		implode(",",$frmBuscar['lstEstadoCompraBuscar']),
		implode(",",$frmBuscar['lstEstadoVentaBuscar']),
		implode(",",$frmBuscar['lstCondicionBuscar']),
		implode(",",$frmBuscar['lstAlmacen']),
		implode(",",$frmBuscar['lstEstadoAdicionalBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("window.open('reportes/al_estatus_unidad_fisica_excel.php?valBusq=%s','_self');", $valBusq));
	
	return $objResponse;
}

function formUnidadFisica($idUnidadFisica, $accion) {
	$objResponse = new xajaxResponse();
	
	if ($idUnidadFisica > 0) {
		/*if (!xvalidaAcceso($objResponse,"an_consulta_existencia_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarUnidadFisica').click();"); return $objResponse; }*/
		
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
			uni_fis.placa,
			uni_fis.id_condicion_unidad,
			(CASE uni_fis.id_condicion_unidad
				WHEN 1 THEN	'NUEVO'
				WHEN 2 THEN	'USADO'
				WHEN 3 THEN	'USADO PARTICULAR'
			END) AS condicion_unidad,
			uni_fis.kilometraje,
			uni_fis.fecha_fabricacion,
			uni_bas.imagen_auto,
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
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
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
			uni_fis.fecha_fabricacion,
			uni_fis.serial1,
			uni_fis.codigo_unico_conversion,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro,
			uni_fis.id_estado_adicional
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
			LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
			LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
			LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
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
		$txtFechaFabricacion = ($row['fecha_fabricacion'] != "") ? date(spanDateFormat, strtotime($row['fecha_fabricacion'])) : "";
		$objResponse->assign("txtFechaFabricacion", "value", $txtFechaFabricacion);
		$objResponse->assign("txtAlmacen", "value", utf8_encode($row['nom_almacen']));
		$objResponse->assign("txtEstadoCompra", "value", utf8_encode($row['estado_compra']));
		$objResponse->assign("txtEstadoVenta", "value", utf8_encode($row['estado_venta']));
	
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		$objResponse->assign("imgArticulo","src",$imgFoto);
		$objResponse->assign("hddUrlImagen","value",$row['imagen_auto']);
		
		$objResponse->assign("txtColorExterno1", "value", utf8_encode($row['color_externo']));
		$objResponse->assign("txtColorInterno1", "value", utf8_encode($row['color_interno']));
		$objResponse->assign("txtColorExterno2", "value", utf8_encode($row['color_externo2']));
		$objResponse->assign("txtColorInterno2", "value", utf8_encode($row['color_interno2']));
		
		$objResponse->assign("txtSerialCarroceria", "value", utf8_encode($row['serial_carroceria']));
		$objResponse->assign("txtSerialMotor", "value", utf8_encode($row['serial_motor']));
		$objResponse->assign("txtNumeroVehiculo", "value", utf8_encode($row['serial_chasis']));
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
		$txtFechaCilindro = ($row['fecha_elaboracion_cilindro'] != "") ? date(spanDateFormat, strtotime($row['fecha_elaboracion_cilindro'])) : "----------";
		$objResponse->assign("txtFechaCilindro", "value", $txtFechaCilindro);
		
		$objResponse->loadCommands(cargaLstEstadoAdicional('lstEstadoAdicional',$row['id_estado_adicional']));
		if($accion == "Ver"){
			$objResponse->script("
			byId('lstEstadoAdicional').className = 'inputInicial';
			byId('lstEstadoAdicional').onchange = function () {
				selectedOption(this.id,".$row["id_estado_adicional"].");
			}");
		}else if($accion == "Editar"){
			$objResponse->script("byId('lstEstadoAdicional').className = 'inputHabilitado';");
		}
	}
	
	return $objResponse;
}

function formUnidadFisicaAgregado($idUnidadFisica) {
	$objResponse = new xajaxResponse();
	
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
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		4
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	2
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			3
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		2
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	4
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			4
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		2
		END) AS id_tipo_movimiento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			'1.- Compra'
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		'4.- Salida'
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	'2.- Entrada'
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			'3.- Venta'
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		'2.- Entrada'
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	'4.- Salida'
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
		END) AS id_factura,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.numero_factura_proveedor
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.numero_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.numero_nota_credito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.numeroNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.numeracion_nota_credito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.numero_vale
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.numero_vale_entrada
		END) AS numero_factura_proveedor,
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
		END) AS estatus_factura,
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
					WHEN 1 THEN 'Sin Asignar'
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
		END) AS descripcion_estatus_factura,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.observacion_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.observacion_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.observacion_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.observacionNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.observacionesNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.motivo_vale
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.motivo_vale
		END) AS observacion_factura,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.saldo_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.saldo_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.saldo_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.saldoNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.saldoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS saldo_factura,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			prov.id_proveedor
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		prov.id_proveedor
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	prov.id_proveedor
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			cliente.id
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cliente.id
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cliente.id
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			cliente.id
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		cliente.id
		END) AS id_proveedor,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		CONCAT_WS('-', cliente.lci, cliente.ci)
		END) AS rif_proveedor,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			prov.nombre
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		prov.nombre
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	prov.nombre
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		END) AS nombre_proveedor,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.id_motivo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.id_motivo
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.id_motivo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.id_motivo
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS id_motivo,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		motivo.descripcion
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	motivo.descripcion
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		motivo.descripcion
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	motivo.descripcion
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS descripcion_motivo,
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
		END) AS total,
		vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
	FROM an_unidad_fisica_agregado uni_fis_agregado
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
			OR (cxp_nc.id_motivo = motivo.id_motivo AND uni_fis_agregado.id_nota_credito_cxp IS NOT NULL)
			OR (cxc_nd.id_motivo = motivo.id_motivo AND uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL)
			OR (cxc_nc.id_motivo = motivo.id_motivo AND uni_fis_agregado.id_nota_credito_cxc IS NOT NULL))
		LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (uni_fis_agregado.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	WHERE uni_fis_agregado.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rsUniFisAgregado = mysql_query($queryUniFisAgregado);
	if (!$rsUniFisAgregado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsUniFisAgregado = mysql_num_rows($rsUniFisAgregado);
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"8%\">Tipo de Dcto.</td>";
		$htmlTh .= "<td width=\"6%\">Fecha Registro</td>";
		$htmlTh .= "<td width=\"6%\">Nro. Dcto.</td>";
		$htmlTh .= "<td width=\"56%\">Cliente / Proveedor</td>";
		$htmlTh .= "<td width=\"8%\">Estado Dcto.</td>";
		$htmlTh .= "<td width=\"8%\">Saldo Dcto.</td>";
		$htmlTh .= "<td width=\"8%\">Total Dcto.</td>";
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	while ($rowUniFisAgregado = mysql_fetch_array($rsUniFisAgregado)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// PARA QUE SIRVA COMO REFERENCIA EN CASO DE QUE EL USUARIO SOLO DESEE CAMBIAR EL PROVEEDOR Y NO ALGUN DOCUMENTO
		$objResponse->assign("hddIdTradeInCxP","value",$rowUniFisAgregado['id_tradein_cxp']);
		
		if (in_array($rowUniFisAgregado['id_modulo'],array(1)) && in_array($rowUniFisAgregado['tipoDocumento'],array('VS','VE'))) {
			switch($rowUniFisAgregado['estatus_factura']) {
				case 0 : $class = "class=\"divMsjInfo\""; break;
				case 1 : $class = "class=\"divMsjAlerta\""; break;
			}
		} else {
			switch($rowUniFisAgregado['estatus_factura']) {
				case 0 : $class = "class=\"divMsjError\""; break;
				case 1 : $class = "class=\"divMsjInfo\""; break;
				case 2 : $class = "class=\"divMsjAlerta\""; break;
				case 3 : $class = "class=\"divMsjInfo3\""; break;
				case 4 : $class = "class=\"divMsjInfo4\""; break;
			}
		}
		
		$classUniFisAgregado = ($rowUniFisAgregado['estatus'] != 1) ? "class=\"divMsjError\"" : "";
		$estatusUniFisAgregado = ($rowUniFisAgregado['estatus'] != 1) ? "<div align=\"center\">RELACION ANULADA</div>" : "";
		$empleadoAnuladoUniFisAgregado = (strlen($rowUniFisAgregado['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$rowUniFisAgregado['nombre_empleado_anulado']."<br>(".date(spanDateFormat,strtotime($rowUniFisAgregado['fecha_anulado'])).")</span></div>" : "";
		
		switch ($rowUniFisAgregado['id_tipo_movimiento']) {
			case 1 : // 1 = COMPRA
				switch ($rowUniFisAgregado['id_modulo']) {
					case 0 : $aVerDctoAux = "javascript:verVentana('../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=".$rowUniFisAgregado['id_factura']."', 960, 550);"; break;
					case 2 : $aVerDctoAux = "javascript:verVentana('../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=".$rowUniFisAgregado['id_factura']."', 960, 550);"; break;
					default : $aVerDctoAux = "";
				}
				$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Registro Compra PDF\"/><a>" : "";
				break;
			case 2 : // 2 = ENTRADA
				switch ($rowUniFisAgregado['tipoDocumento']) {
					case "ND" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('../cxp/reportes/cp_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
							$rowUniFisAgregado['id_factura']);
						break;
					case "NC" :
						switch ($rowUniFisAgregado['id_modulo']) {
							case 0 : // REPUESTOS
								$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
									$rowUniFisAgregado['id_factura']);
								break;
							case 1 : // SERVICIOS
								//if ($rowUniFisAgregado['aplicaLibros'] == 1) {
									$aVerDctoAux = sprintf("javascript:verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
										$rowUniFisAgregado['id_factura']);
								//}
								break;
							case 2 : // VEHICULOS
								$aVerDctoAux = sprintf("javascript:verVentana('../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
									$rowUniFisAgregado['id_factura']);
								break;
							case 3 : // ADMINISTRACION
								$aVerDctoAux = sprintf("javascript:verVentana('../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
									$rowUniFisAgregado['id_factura']);
								break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"".$aVerDctoAux."\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
						break;
					default : $aVerDcto = "";
				}
				break;
			case 3 : // 3 = VENTA
				$aVerDcto = ""; break;
			case 4 : // 4 = SALIDA
				switch ($rowUniFisAgregado['tipoDocumento']) {
					case "ND" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
							$rowUniFisAgregado['id_factura']);
						break;
					case "NC" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>",
							$rowUniFisAgregado['id_factura']);
						break;
					case "VS" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana('../servicios/sa_imprimir_historico_vale.php?valBusq=%s|2|3', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Vale de Salida PDF")."\"/></a>",
							$rowUniFisAgregado['id_factura']);
						break;
					default : $aVerDcto = "";
				}
				break;
			default : $aVerDcto = "";
		}
		
		if (in_array($rowUniFisAgregado['tipoDocumento'],array('VS'))) {
			$hddIdDcto = sprintf("<input type=\"hidden\" id=\"hddIdValeSalida%s\" name=\"hddIdValeSalida%s\" value=\"%s\"/>",
				$contFila, $contFila, $rowUniFisAgregado['id_factura']);
		}
		
		$htmlTb .= sprintf("<tr align=\"left\" id=\"trItm:%s\" class=\"%s\" height=\"24\">",
			$contFila, $clase);
			$htmlTb .= sprintf("<td title=\"trItm:%s\"><input id=\"cbxItm%s\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>",
				$contFila, $contFila, $contFila,
				$contFila);
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= utf8_encode($rowUniFisAgregado['descripcion_tipo_movimiento']);
				$htmlTb .= "<br>".utf8_encode($rowUniFisAgregado['tipoDocumento']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($row['fecha_origen'] != "") ? date(spanDateFormat,strtotime($rowUniFisAgregado['fecha_origen'])) : "")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classUniFisAgregado.">".$rowUniFisAgregado['numero_factura_proveedor'].$estatusUniFisAgregado.$empleadoAnuladoUniFisAgregado."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($rowUniFisAgregado['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($rowUniFisAgregado['serial_carroceria']) > 0) ? "<tr><td><span class=\"textoNegrita_10px\">".utf8_encode($rowUniFisAgregado['serial_carroceria'])."</span></td></tr>" : "";
				$htmlTb .= ($rowUniFisAgregado['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$rowUniFisAgregado['id_motivo'].".- ".utf8_encode($rowUniFisAgregado['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($rowUniFisAgregado['observacion_factura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($rowUniFisAgregado['observacion_factura'])."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$rowUniFisAgregado['descripcion_estatus_factura']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format(((in_array($rowUniFisAgregado['id_tipo_movimiento'],array(2)) && in_array($rowUniFisAgregado['tipoDocumento'],array("ND"))) ? (-1) : 1) * $rowUniFisAgregado['saldo_factura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<input type=\"text\" id=\"txtTotalDcto%s\" name=\"txtTotalDcto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>
					<input type=\"hidden\" id=\"hddIdUnidadFisicaAgregado%s\" name=\"hddIdUnidadFisicaAgregado%s\" value=\"%s\"/>
					%s",
					$contFila, $contFila, number_format(((in_array($rowUniFisAgregado['id_tipo_movimiento'],array(2)) && in_array($rowUniFisAgregado['tipoDocumento'],array("ND"))) ? (-1) : 1) * $rowUniFisAgregado['total'], 2, ".", ","),
					$contFila, $contFila, $rowUniFisAgregado['id_unidad_fisica_agregado'],
					$hddIdDcto);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= $aVerDcto;
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[7] += ($rowUniFisAgregado['estatus'] == 1) ? ((in_array($rowUniFisAgregado['id_tipo_movimiento'],array(2)) && in_array($rowUniFisAgregado['tipoDocumento'],array("ND"))) ? (-1) : 1) * $rowUniFisAgregado['monto'] : 0;
	}
	
	$htmlTb .= "<tr id=\"trItmPie\" align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\" colspan=\"7\">"."Total Agregados:"."</td>";
		$htmlTb .= "<td>";
			$htmlTb .= sprintf("<input type=\"text\" id=\"txtTotalAgregado\" name=\"txtTotalAgregado\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>",
				number_format($arrayTotal[7], 2, ".", ","));
		$htmlTb .= "</td>";
		$htmlTb .= "<td></td>";
	$htmlTb .= "</tr>";
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaUniFisAgregado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function guardarUnidadFisica($frmUnidadFisica){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("UPDATE an_unidad_fisica 
					SET id_estado_adicional = %s 
					WHERE id_unidad_fisica = %s",
			valTpDato($frmUnidadFisica["lstEstadoAdicional"],"int"),
			valTpDato($frmUnidadFisica["txtIdUnidadFisica"],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$objResponse->script("byId('btnCancelarUnidadFisica').click();
		byId('btnBuscar').click();");
	
	$objResponse->alert("Estado Actualizado Correctamente");
	
	return $objResponse;
}

function imprimirUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstMarcaBuscar'],
		$frmBuscar['lstModeloBuscar'],
		$frmBuscar['lstVersionBuscar'],
		implode(",",$frmBuscar['lstEstadoCompraBuscar']),
		implode(",",$frmBuscar['lstEstadoVentaBuscar']),
		implode(",",$frmBuscar['lstCondicionBuscar']),
		implode(",",$frmBuscar['lstAlmacen']),
		implode(",",$frmBuscar['lstEstadoAdicionalBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/al_estatus_unidad_fisica_pdf.php?valBusq=%s', 960, 550)", $valBusq));
	
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO', 'ACTIVO FIJO')");
		
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
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version = %s",
			valTpDato($valCadBusq[3], "int"));
	}
		
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
	}
		
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[5])."'", "defined", "'".str_replace(",","','",$valCadBusq[5])."'"));
	}
		
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
			valTpDato($valCadBusq[7], "campo"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.id_estado_adicional IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[8])."'", "defined", "'".str_replace(",","','",$valCadBusq[8])."'"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s
		OR numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"),
			valTpDato("%".$valCadBusq[9]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		vw_iv_modelo.id_uni_bas,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo, estado_adicional.nombre_estado,
		
		(SELECT MAX(DATE(contrato.fecha_salida)) 
			FROM al_contrato_venta contrato 
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica) AS ultima_fecha_alquilado,
		
		(SELECT contrato.dias_contrato
			FROM al_contrato_venta contrato
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND contrato.estatus_contrato_venta = 1
			ORDER BY contrato.id_contrato_venta DESC LIMIT 1) AS dias_alquilado,
			
		(SELECT SUM(IF(contrato.estatus_contrato_venta = 1, contrato.dias_contrato, contrato.dias_total)) 
			FROM al_contrato_venta contrato
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica) AS total_dias_alquilado,
		
		(IF((SELECT COUNT(contrato.id_contrato_venta) 
				FROM al_contrato_venta contrato 
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica 
				AND contrato.estatus_contrato_venta = 1) = 0,					
			(SELECT ABS(DATEDIFF(CURDATE(), DATE(contrato.fecha_final)))
				FROM al_contrato_venta contrato
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica
				ORDER BY contrato.id_contrato_venta DESC LIMIT 1),
			0)) AS dias_sin_alquilar
		
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_unidad_estado_adicional estado_adicional ON (uni_fis.id_estado_adicional = estado_adicional.id_estado_adicional)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) 
		%s
		GROUP BY vw_iv_modelo.id_uni_bas", $sqlBusq);
		
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
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Unidad Física");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialMotor);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, "Color");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ingreso");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "4%", $pageNum, "(TO_DAYS(NOW()) - TO_DAYS(cxp_fact.fecha_origen))", $campOrd, $tpOrd, $valBusq, $maxRows, "Días");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Venta");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "nombre_estado", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Adicional");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Asignación");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "14%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almacén");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "ultima_fecha_alquilado", $campOrd, $tpOrd, $valBusq, $maxRows, "Última Fecha Alquilado");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Fact. Compra");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "precio_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");

		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "dias_alquilado", $campOrd, $tpOrd, $valBusq, $maxRows, "Días Alquilado");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "dias_sin_alquilar", $campOrd, $tpOrd, $valBusq, $maxRows, "Días Sin Alquilar");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"20\">".utf8_encode($row['vehiculo'])."</td>";
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
			uni_fis.placa,
			(CASE uni_fis.id_condicion_unidad
				WHEN 1 THEN	'NUEVO'
				WHEN 2 THEN	'USADO'
				WHEN 3 THEN	'USADO PARTICULAR'
			END) AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					cxp_fact.fecha_origen
				WHEN (cxp_fact.fecha_origen IS NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(cxp_fact.fecha_origen)
				WHEN (cxp_fact.fecha_origen IS NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
			END) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.numero_factura_proveedor,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			estado_adicional.id_estado_adicional,
			estado_adicional.nombre_estado,
			
			(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
			WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		
			(SELECT MAX(DATE(contrato.fecha_salida)) 
				FROM al_contrato_venta contrato 
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica) AS ultima_fecha_alquilado,
			
			(SELECT contrato.dias_contrato
				FROM al_contrato_venta contrato
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND contrato.estatus_contrato_venta = 1
				ORDER BY contrato.id_contrato_venta DESC LIMIT 1) AS dias_alquilado,
				
			(SELECT SUM(IF(contrato.estatus_contrato_venta = 1, contrato.dias_contrato, contrato.dias_total)) 
				FROM al_contrato_venta contrato
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica) AS total_dias_alquilado,
			
			(IF((SELECT COUNT(contrato.id_contrato_venta) 
					FROM al_contrato_venta contrato 
					WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica 
					AND contrato.estatus_contrato_venta = 1) = 0,					
				(SELECT ABS(DATEDIFF(CURDATE(), DATE(contrato.fecha_final)))
					FROM al_contrato_venta contrato
					WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica
					ORDER BY contrato.id_contrato_venta DESC LIMIT 1),
				0)) AS dias_sin_alquilar
			
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_unidad_estado_adicional estado_adicional ON (uni_fis.id_estado_adicional = estado_adicional.id_estado_adicional)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND cxp_fact.fecha_origen IS NULL
				AND an_ve.tipo_vale_entrada = 1)
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
				case "ACTIVO FIJO" : $class = "class=\"divMsjInfo5\""; break;
				default : $class = ""; break;
			}
			
			switch($rowUnidadFisica['id_estado_adicional']) {
				case 1 : $classA = "class=\"divMsjInfo\""; break;
				case 2 : $classA = "class=\"divMsjInfo6\""; break;
				default : $classA = ""; break;
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr class=\"textoNegrita_10px\">";
						$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['condicion_unidad'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				$htmlTb .= "<td align=\"center\">".(($rowUnidadFisica['fecha_origen']) ? date(spanDateFormat,strtotime($rowUnidadFisica['fecha_origen'])) : "")."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['dias_inventario'])."</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">";
					$htmlTb .= utf8_encode($rowUnidadFisica['estado_venta']);
					$htmlTb .= ($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<br><b>(".utf8_encode($rowUnidadFisica['estado_compra']).")</b>" : "";
					$htmlTb .= ($rowUnidadFisica['id_activo_fijo'] > 0) ? "<br><span class=\"textoNegrita_9px\">Código: ".$rowUnidadFisica['id_activo_fijo']."</span>" : "";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$classA.">".utf8_encode($rowUnidadFisica['nombre_estado'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"center\">".(($rowUnidadFisica['ultima_fecha_alquilado'] != "") ? date(spanDateFormat,strtotime($rowUnidadFisica['ultima_fecha_alquilado'])) : "")."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['numero_factura_proveedor'])."</td>";
				$htmlTb .= "<td align=\"right\">";
					$htmlTb .= number_format($rowUnidadFisica['precio_compra'], 2, ".", ",");
					$htmlTb .= ($rowUnidadFisica['costo_agregado'] != 0) ? "<br><label class=\"textoVerdeNegrita_10px\" title=\"".htmlentities("Total Agregados")."\">[".number_format($rowUnidadFisica['costo_agregado'], 2, ".", ",")."]</label>" : "";
					$htmlTb .= ($rowUnidadFisica['costo_depreciado'] > 0) ? "<br><label class=\"textoRojoNegrita_10px\" title=\"".htmlentities("Total Depreciación")."\">[-".number_format($rowUnidadFisica['costo_depreciado'], 2, ".", ",")."]</label>" : "";
					$htmlTb .= ($rowUnidadFisica['costo_trade_in'] > 0) ? "<br><label class=\"textoRojoNegrita_10px\" title=\"".htmlentities("Total Depreciación Ingreso por Trade In")."\">[-".number_format($rowUnidadFisica['costo_trade_in'], 2, ".", ",")."]</label>" : "";
				$htmlTb .= "</td>";				
				$htmlTb .= "<td align=\"right\">".number_format($rowUnidadFisica['dias_alquilado'], 2, ".", ",").
							"<br><span class=\"textoNegrita_9px\">(".number_format($rowUnidadFisica['total_dias_alquilado'], 2, ".", ",").")</span>"
							."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowUnidadFisica['dias_sin_alquilar'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadFisica', '%s', 'Ver');\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Unidad Física\"/></a>",
						$contFila,
						$rowUnidadFisica['id_unidad_fisica']);
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadFisica', '%s', 'Editar');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Unidad Física\"/></a>",
						$contFila,
						$rowUnidadFisica['id_unidad_fisica']);
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotal[13] += $rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in'];
			$arrayDiasAlquilado[0] += $rowUnidadFisica['dias_alquilado']; 
			$arrayDiasTotalAlquilado[0] += $rowUnidadFisica['total_dias_alquilado'];
			$arrayDiasSinAlquilar[0] += $rowUnidadFisica['dias_sin_alquilar']; 
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"26\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">"."Subtotal:<br>".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td>".number_format($contFila2, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayDiasAlquilado[0], 2, ".", ",").
							"<br><span class=\"textoNegrita_9px\">(".number_format($arrayDiasTotalAlquilado[0], 2, ".", ",").")</span>"
						."</td>";
			$htmlTb .= "<td>".number_format($arrayDiasSinAlquilar[0], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"2\"></td>";
		$htmlTb .= "</tr>";
		
		$arrayDiasAlquilado[0] = 0; 
		$arrayDiasTotalAlquilado[0] = 0;
		$arrayDiasSinAlquilar[0] = 0; 
	}
	if ($pageNum == $totalPages) {
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			(CASE uni_fis.id_condicion_unidad
				WHEN 1 THEN	'NUEVO'
				WHEN 2 THEN	'USADO'
				WHEN 3 THEN	'USADO PARTICULAR'
			END) AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					cxp_fact.fecha_origen
				WHEN (cxp_fact.fecha_origen IS NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(cxp_fact.fecha_origen)
				WHEN (cxp_fact.fecha_origen IS NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
			END) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.numero_factura_proveedor,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND cxp_fact.fecha_origen IS NULL
				AND an_ve.tipo_vale_entrada = 1)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s;", $sqlBusq);
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		$arrayTotal = NULL;
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$contFila2++;
			
			$arrayTotal[12] = $contFila2;
			$arrayTotal[13] += $rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in'];
		}
		
		$arrayTotalFinal[12] += $arrayTotal[12];
		$arrayTotalFinal[13] += $arrayTotal[13];
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">"."Total de Totales:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstColor");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicion");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoAdicionalBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCompraBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVentaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"exportarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"formUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"formUnidadFisicaAgregado");
$xajax->register(XAJAX_FUNCTION,"guardarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"imprimirUnidadFisica");
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