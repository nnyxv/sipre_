<?php


function buscarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
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
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstMarcaBuscar']) ? implode(",",$frmBuscar['lstMarcaBuscar']) : $frmBuscar['lstMarcaBuscar']),
		(is_array($frmBuscar['lstModeloBuscar']) ? implode(",",$frmBuscar['lstModeloBuscar']) : $frmBuscar['lstModeloBuscar']),
		(is_array($frmBuscar['lstVersionBuscar']) ? implode(",",$frmBuscar['lstVersionBuscar']) : $frmBuscar['lstVersionBuscar']),
		(is_array($frmBuscar['lstAnoBuscar']) ? implode(",",$frmBuscar['lstAnoBuscar']) : $frmBuscar['lstAnoBuscar']),
		(is_array($frmBuscar['lstEstadoCompraBuscar']) ? implode(",",$frmBuscar['lstEstadoCompraBuscar']) : $frmBuscar['lstEstadoCompraBuscar']),
		(is_array($frmBuscar['lstEstadoVentaBuscar']) ? implode(",",$frmBuscar['lstEstadoVentaBuscar']) : $frmBuscar['lstEstadoVentaBuscar']),
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		(is_array($frmBuscar['lstAlmacenBuscar']) ? implode(",",$frmBuscar['lstAlmacenBuscar']) : $frmBuscar['lstAlmacenBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_existencia_asignacion_excel.php?valBusq=".$valBusq."','_self');");
	
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
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_fact.fecha_origen BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_ano IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[7])."'", "defined", "'".str_replace(",","','",$valCadBusq[7])."'"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[8])."'", "defined", "'".str_replace(",","','",$valCadBusq[8])."'"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
			valTpDato($valCadBusq[9], "campo"));
	}
		
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
			valTpDato($valCadBusq[10], "campo"));
	}
	
	if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
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
			valTpDato("%".$valCadBusq[11]."%", "text"),
			valTpDato("%".$valCadBusq[11]."%", "text"),
			valTpDato("%".$valCadBusq[11]."%", "text"),
			valTpDato("%".$valCadBusq[11]."%", "text"),
			valTpDato("%".$valCadBusq[11]."%", "text"),
			valTpDato("%".$valCadBusq[11]."%", "text"),
			valTpDato("%".$valCadBusq[11]."%", "text"),
			valTpDato("%".$valCadBusq[11]."%", "text"),
			valTpDato("%".$valCadBusq[11]."%", "text"));
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
		LEFT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica AND uni_fis.estado_venta NOT IN ('DISPONIBLE'))
			LEFT JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
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
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "uni_fis.id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Unidad Física");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialMotor);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, "Color");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "kilometraje", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanKilometraje));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ingreso");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Venta");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almacén");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "5%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Fact. Compra");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "5%", $pageNum, "precio_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "5%", $pageNum, "CONVERT(numeracion_pedido, SIGNED)", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Pedido / Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "5%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "5%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Vendedor");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"30\">".($row['vehiculo'].((strlen($row['mostrar_catalogo']) > 0) ? " <b>[".$row['mostrar_catalogo']."]</b>" : ""))."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE "; // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
		$sqlBusq2 .= $cond.sprintf("(ped_vent.estado_pedido IN (0,1,3)
		OR ped_vent.estado_pedido IS NULL)");
		
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
			
			ped_vent.id_pedido,
			ped_vent.numeracion_pedido,
			ped_vent.fecha AS fecha_pedido,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			vw_pg_empleado.nombre_empleado,
			
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
			LEFT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica AND uni_fis.estado_venta NOT IN ('DISPONIBLE'))
				LEFT JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
				LEFT JOIN vw_pg_empleados vw_pg_empleado ON (ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
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
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= (($rowUnidadFisica['fecha_origen'] != "") ? "<div>".date(spanDateFormat, strtotime($rowUnidadFisica['fecha_origen']))."</div>" : "");
					$htmlTb .= (($rowUnidadFisica['dias_inventario'] > 0) ? "<div class=\"textoNegrita_9px\">".($rowUnidadFisica['dias_inventario']." días")."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['estado_venta'])."</div>";
					$htmlTb .= (($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<div class=\"textoNegrita_9px\">(".utf8_encode($rowUnidadFisica['estado_compra']).")</div>" : "");
					$htmlTb .= (($rowUnidadFisica['id_activo_fijo'] > 0) ? "<div class=\"textoNegrita_9px\">".("Código: ".$rowUnidadFisica['id_activo_fijo'])."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
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
				$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">";
					$htmlTb .= "<div>".(($rowUnidadFisica['fecha_pedido'] != "") ? date(spanDateFormat, strtotime($rowUnidadFisica['fecha_pedido'])) : "")."</div>";
					$htmlTb .= utf8_encode($rowUnidadFisica['numeracion_pedido']);
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_cliente'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empleado'])."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotal['cant_unidades'] += 1;
			$arrayTotal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
			$arrayTotal['precio_unitario'] += $rowUnidadFisica['precio_unitario'];
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Subtotal:<br>".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_unidades'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['precio_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalFinal['cant_unidades'] += $arrayTotal['cant_unidades'];
		$arrayTotalFinal['precio_compra'] += $arrayTotal['precio_compra'];
		$arrayTotalFinal['precio_unitario'] += $arrayTotal['precio_unitario'];
	}
	if ($pageNum == $totalPages) {
		if ($totalPages > 0) {
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE "; // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			$sqlBusq2 .= $cond.sprintf("(ped_vent.estado_pedido IN (0,1,3)
			OR ped_vent.estado_pedido IS NULL)");
			
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
				
				ped_vent.id_pedido,
				ped_vent.numeracion_pedido,
				ped_vent.fecha AS fecha_pedido,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				vw_pg_empleado.nombre_empleado,
				
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
				LEFT JOIN an_pedido ped_vent ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica AND uni_fis.estado_venta NOT IN ('DISPONIBLE'))
					LEFT JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
					LEFT JOIN vw_pg_empleados vw_pg_empleado ON (ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
			$rsUnidadFisica = mysql_query($queryUnidadFisica);
			$arrayTotalFinal = array();
			while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
				$arrayTotalFinal['cant_unidades'] += 1;
				$arrayTotalFinal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
				$arrayTotalFinal['precio_unitario'] += $rowUnidadFisica['precio_unitario'];
			}
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Total de Totales:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal['cant_unidades'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal['precio_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
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
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacenBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCompraBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVentaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"exportarUnidadFisica");
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