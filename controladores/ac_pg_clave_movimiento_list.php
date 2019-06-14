<?php


function asignarNumeracion($idNumeracion, $objDestino, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_numeracion WHERE id_numeracion = %s;", valTpDato($idNumeracion, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtId".$objDestino,"value",$row['id_numeracion']);
	$objResponse->assign("txt".$objDestino,"value",utf8_encode($row['nombreNumeraciones']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("
		byId('btnCancelarListaNumeracion').click();");
	}
	
	return $objResponse;
}

function asignarTipoMovimiento($frmClaveMovimiento, $selId = "") {
	$objResponse = new xajaxResponse();
	
	// 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
	switch($frmClaveMovimiento['lstTipoClave']) {
		case 1 : // 1 = Compra
			$lstTipoClave = 4;
			switch($frmClaveMovimiento['lstDctoGenerado']) {
				case 1 : $lstTipoDcto = 3; break;
				default : $lstTipoDcto = -2;
			}
			break;
		case 2 : // 2 = Entrada
			switch($frmClaveMovimiento['lstDctoGenerado']) {
				case 0 : $lstTipoClave = 4; $lstTipoDcto = 0; break;
				case 3 : $lstTipoClave = 3; $lstTipoDcto = 1; break;
				case 6 : $lstTipoClave = 4; $lstTipoDcto = 5; break;
				default : $lstTipoClave = -2; $lstTipoDcto = -2;
			}
			break;
		case 3 : // 3 = Venta
			$lstTipoClave = 2;
			switch($frmClaveMovimiento['lstDctoGenerado']) {
				case 1 : $lstTipoDcto = 3; break;
				default : $lstTipoDcto = -2;
			}
			break;
		case 4 : // 4 = Salida
			switch($frmClaveMovimiento['lstDctoGenerado']) {
				case 0 : $lstTipoClave = 2; $lstTipoDcto = 0; break;
				case 3 : $lstTipoClave = 1; $lstTipoDcto = 1; break;
				case 5 : $lstTipoClave = 2; $lstTipoDcto = 6; break;
				default : $lstTipoClave = -2; $lstTipoDcto = -2;
			}
			break;
		default : $lstTipoClave = -2; $lstTipoDcto = -2;
	}
	
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $frmClaveMovimiento['lstModulo'], $lstTipoClave, "", $lstTipoDcto, $selId));
	
	return $objResponse;
}

function buscarClaveMovimiento($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		(is_array($frmBuscar['lstTipoMovimiento']) ? implode(",",$frmBuscar['lstTipoMovimiento']) : $frmBuscar['lstTipoMovimiento']),
		(is_array($frmBuscar['lstDctoGeneradoBuscar']) ? implode(",",$frmBuscar['lstDctoGeneradoBuscar']) : $frmBuscar['lstDctoGeneradoBuscar']),
		(is_array($frmBuscar['lstModuloBuscar']) ? implode(",",$frmBuscar['lstModuloBuscar']) : $frmBuscar['lstModuloBuscar']),
		(is_array($frmBuscar['lstTipoPago']) ? implode(",",$frmBuscar['lstTipoPago']) : $frmBuscar['lstTipoPago']),
		(is_array($frmBuscar['lstEstatusBuscar']) ? implode(",",$frmBuscar['lstEstatusBuscar']) : $frmBuscar['lstEstatusBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaClaveMovimiento(0, "vw_pg_clave_mov.descripcion_modulo", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarNumeracion($frmBuscarNumeracion) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarNumeracion['txtCriterioBuscarNumeracion'],
		$frmBuscarNumeracion['hddObjDestinoNumeracion']);
	
	$objResponse->loadCommands(listaNumeracion(0, "id_numeracion", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	$idModulo = (is_array($idModulo)) ? implode(",",$idModulo) : $idModulo;
	$idTipoClave = (is_array($idTipoClave)) ? implode(",",$idTipoClave) : $idTipoClave;
	
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
	
	if (($tipoDcto != "-1" && $tipoDcto != "") || $tipoDcto == 0) { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
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
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
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

function cargaLstDctoGenerado($selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[] = "Nada";			$array[] = "Factura";			$array[] = "Remisión";		$array[] = "Nota de Crédito";
	$array[] = "Nota de Cargo";	$array[] = "Vale de Salida";	$array[] = "Vale de Entrada";
	
	$html = "<select id=\"lstDctoGenerado\" name=\"lstDctoGenerado\" class=\"inputHabilitado\" onchange=\"xajax_asignarTipoMovimiento(xajax.getFormValues('frmClaveMovimiento'))\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".utf8_encode($indice.".- ".$array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstDctoGenerado","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstDctoGeneradoBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[] = "Nada";			$array[] = "Factura";			$array[] = "Remisión";		$array[] = "Nota de Crédito";
	$array[] = "Nota de Cargo";	$array[] = "Vale de Salida";	$array[] = "Vale de Entrada";
	
	$html = "<select ".((count($array) > 2) ? "multiple": "")." id=\"lstDctoGeneradoBuscar\" name=\"lstDctoGeneradoBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (in_array($indice,explode(",",$selId))) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".utf8_encode($indice.".- ".$array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstDctoGeneradoBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstatus($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$array = array("1" => "Activo", "0" => "Inactivo");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstEstatus\" name=\"lstEstatus\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".utf8_encode($indice.".- ".$array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstatus","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstatusBuscar($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$array = array("1" => "Activo", "0" => "Inactivo");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstEstatusBuscar\" name=\"lstEstatusBuscar\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".utf8_encode($indice.".- ".$array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstatusBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM pg_modulos ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_enlace_concepto']) ? "selected='selected'" : "";

		$html .= "<option ".$selected." value=\"".$row['id_enlace_concepto']."\">".utf8_encode($row['descripcionModulo'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstModulo","innerHTML",$html);

	return $objResponse;
}

function cargaLstModuloBuscar($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$query = sprintf("SELECT * FROM pg_modulos ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple": "")." id=\"lstModuloBuscar\" name=\"lstModuloBuscar\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = (in_array($row['id_modulo'],explode(",",$selId))) ? "selected='selected'" : "";

		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstModuloBuscar","innerHTML",$html);

	return $objResponse;
}

function cargaLstTipoMovimiento($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$array = array("1" => "Compra", "2" => "Entrada", "3" => "Venta", "4" => "Salida");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstTipoMovimiento\" name=\"lstTipoMovimiento\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".utf8_encode($indice.".- ".$array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoMovimiento","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoPago($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$array = array("1" => "Contado", "0" => "Crédito");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstTipoPago\" name=\"lstTipoPago\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".utf8_encode($indice.".- ".$array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function eliminarClaveMovimiento($frmListaClaveMovimiento, $frmListaClaveMovimiento) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"pg_clave_movimiento_list","eliminar")) { return $objResponse; }
	
	if (isset($frmListaClaveMovimiento['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach($frmListaClaveMovimiento['cbxItm'] as $indiceItm => $valorItm) {
			$deleteSQL = sprintf("DELETE FROM pg_clave_movimiento WHERE id_clave_movimiento = %s",
				valTpDato($valorItm, "int"));
			
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		mysql_query("COMMIT;");

		$objResponse->loadCommands(listaClaveMovimiento(
			$frmListaClaveMovimiento['pageNum'],
			$frmListaClaveMovimiento['campOrd'],
			$frmListaClaveMovimiento['tpOrd'],
			$frmListaClaveMovimiento['valBusq']));
	}
		
	return $objResponse;
}

function formClaveMovimiento($idClaveMovimiento) {
	$objResponse = new xajaxResponse();
	
	if ($idClaveMovimiento > 0) {
		if (!xvalidaAcceso($objResponse, "pg_clave_movimiento_list", "editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarClaveMovimiento').click();"); return $objResponse; }
		
		$queryClaveMovimiento = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s",
			valTpDato($idClaveMovimiento, "int"));
		$rsClaveMovimiento = mysql_query($queryClaveMovimiento);
		if (!$rsClaveMovimiento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClaveMovimiento = mysql_fetch_assoc($rsClaveMovimiento);
		
		$objResponse->assign("hddIdClaveMovimiento","value",$idClaveMovimiento);
		$objResponse->loadCommands(cargaLstModulo($rowClaveMovimiento['id_modulo']));
		$objResponse->loadCommands(cargaLstEstatus($rowClaveMovimiento['estatus']));
		$objResponse->assign("txtClave","value",$rowClaveMovimiento['clave']);
		$objResponse->assign("txtDescripcion","value",utf8_encode($rowClaveMovimiento['descripcion']));
		
		$objResponse->loadCommands(asignarTipoMovimiento(array("lstModulo" => $rowClaveMovimiento['id_modulo'], "lstTipoClave" => $rowClaveMovimiento['tipo'], "lstDctoGenerado" => $rowClaveMovimiento['documento_genera']), $rowClaveMovimiento['id_clave_movimiento_contra']));
		
		if ($rowClaveMovimiento['afecta_consumo'] == 1)
			$objResponse->assign("rbtAfectaConsumoSi","checked","true");
		else
			$objResponse->assign("rbtAfectaConsumoNo","checked","true");
		
		if ($rowClaveMovimiento['proveedor_individual'] == 1)
			$objResponse->assign("rbtProvIndividualSi","checked","true");
		else
			$objResponse->assign("rbtProvIndividualNo","checked","true");
		
		$objResponse->call("selectedOption","lstTipoClave",$rowClaveMovimiento['tipo']);
		$objResponse->loadCommands(cargaLstDctoGenerado($rowClaveMovimiento['documento_genera']));
		$objResponse->loadCommands(asignarNumeracion($rowClaveMovimiento['id_numeracion_documento'], "NumeracionDocumento"));
		$objResponse->loadCommands(asignarNumeracion($rowClaveMovimiento['id_numeracion_control'], "NumeracionControl"));
		
		$objResponse->assign("txtContraCuenta","value",$rowClaveMovimiento['contra_cuenta']);
		
		if ($rowClaveMovimiento['estadistica'] == 1)
			$objResponse->assign("rbtEstadisticaSi","checked","true");
		else
			$objResponse->assign("rbtEstadisticaNo","checked","true");
		
		$objResponse->assign("txtPrefijoFolioMultiple","value",$rowClaveMovimiento['prefijo_folio_multiple']);
		$objResponse->assign("txtArea","value",$rowClaveMovimiento['area']);
		
		if ($rowClaveMovimiento['pago_contado'] == 1)
			$objResponse->assign("rbtPagoContado","checked","true");
		else
			$objResponse->assign("rbtPagoContado","checked","");
		
		if ($rowClaveMovimiento['pago_credito'] == 1)
			$objResponse->assign("rbtPagoCredito","checked","true");
		else
			$objResponse->assign("rbtPagoCredito","checked","");
	} else {
		if (!xvalidaAcceso($objResponse, "pg_clave_movimiento_list", "insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarClaveMovimiento').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstEstatus());
		$objResponse->loadCommands(cargaLstModulo());
		$objResponse->loadCommands(cargaLstDctoGenerado());
	}
	
	return $objResponse;
}

function guardarClaveMovimiento($frmClaveMovimiento, $frmListaClaveMovimiento) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idClaveMovimiento = $frmClaveMovimiento['hddIdClaveMovimiento'];
	
	$pagoContado = ($frmClaveMovimiento['rbtPagoContado']) ? 1 : 0;
	$pagoCredito = ($frmClaveMovimiento['rbtPagoCredito']) ? 1 : 0;
	
	if ($idClaveMovimiento > 0) {
		if (!xvalidaAcceso($objResponse,"pg_clave_movimiento_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_clave_movimiento SET
			clave = %s,
			descripcion = %s,
			id_modulo = %s,
			tipo = %s,
			documento_genera = %s,
			id_clave_movimiento_contra = %s,
			pago_contado = %s,
			pago_credito = %s,
			id_numeracion_documento = %s,
			id_numeracion_control = %s,
			afecta_consumo = %s,
			proveedor_individual = %s,
			contra_cuenta = %s,
			estadistica = %s,
			prefijo_folio_multiple = %s,
			area = %s,
			estatus = %s
		WHERE id_clave_movimiento = %s",
			valTpDato($frmClaveMovimiento['txtClave'], "text"),
			valTpDato($frmClaveMovimiento['txtDescripcion'], "text"),
			valTpDato($frmClaveMovimiento['lstModulo'], "int"),
			valTpDato($frmClaveMovimiento['lstTipoClave'], "int"),
			valTpDato($frmClaveMovimiento['lstDctoGenerado'], "int"),
			valTpDato($frmClaveMovimiento['lstClaveMovimiento'], "int"),
			valTpDato($pagoContado, "boolean"),
			valTpDato($pagoCredito, "boolean"),
			valTpDato($frmClaveMovimiento['txtIdNumeracionDocumento'], "int"),
			valTpDato($frmClaveMovimiento['txtIdNumeracionControl'], "int"),
			valTpDato($frmClaveMovimiento['rbtAfectaConsumo'], "boolean"),
			valTpDato($frmClaveMovimiento['rbtProvIndividual'], "boolean"),
			valTpDato($frmClaveMovimiento['txtContraCuenta'], "text"),
			valTpDato($frmClaveMovimiento['rbtEstadistica'], "boolean"),
			valTpDato($frmClaveMovimiento['txtPrefijoFolioMultiple'], "text"),
			valTpDato($frmClaveMovimiento['txtArea'], "int"),
			valTpDato($frmClaveMovimiento['lstEstatus'], "boolean"),
			valTpDato($frmClaveMovimiento['hddIdClaveMovimiento'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_clave_movimiento_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_clave_movimiento (clave, descripcion, id_modulo, tipo, documento_genera, id_clave_movimiento_contra, pago_contado, pago_credito, id_numeracion_documento, id_numeracion_control, afecta_consumo, proveedor_individual, contra_cuenta, estadistica, prefijo_folio_multiple, area, estatus)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmClaveMovimiento['txtClave'], "text"),
			valTpDato($frmClaveMovimiento['txtDescripcion'], "text"),
			valTpDato($frmClaveMovimiento['lstModulo'], "int"),
			valTpDato($frmClaveMovimiento['lstTipoClave'], "int"),
			valTpDato($frmClaveMovimiento['lstDctoGenerado'], "int"),
			valTpDato($frmClaveMovimiento['lstClaveMovimiento'], "int"),
			valTpDato($pagoContado, "boolean"),
			valTpDato($pagoCredito, "boolean"),
			valTpDato($frmClaveMovimiento['txtIdNumeracionDocumento'], "int"),
			valTpDato($frmClaveMovimiento['txtIdNumeracionControl'], "int"),
			valTpDato($frmClaveMovimiento['rbtAfectaConsumo'], "boolean"),
			valTpDato($frmClaveMovimiento['rbtProvIndividual'], "boolean"),
			valTpDato($frmClaveMovimiento['txtContraCuenta'], "text"),
			valTpDato($frmClaveMovimiento['rbtEstadistica'], "boolean"),
			valTpDato($frmClaveMovimiento['txtPrefijoFolioMultiple'], "text"),
			valTpDato($frmClaveMovimiento['txtArea'], "int"),
			valTpDato($frmClaveMovimiento['lstEstatus'], "boolean"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idClaveMovimiento = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
		
	$updateSQL = sprintf("UPDATE pg_clave_movimiento SET
		id_clave_movimiento_contra = %s
	WHERE id_clave_movimiento = %s
		AND id_clave_movimiento_contra IS NULL;",
		valTpDato($frmClaveMovimiento['hddIdClaveMovimiento'], "int"),
		valTpDato($frmClaveMovimiento['lstClaveMovimiento'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Clave de Movimiento Guardado con Exito");
	
	$objResponse->script("byId('btnCancelarClaveMovimiento').click();");
	
	$objResponse->loadCommands(listaClaveMovimiento(
		$frmListaClaveMovimiento['pageNum'],
		$frmListaClaveMovimiento['campOrd'],
		$frmListaClaveMovimiento['tpOrd'],
		$frmListaClaveMovimiento['valBusq']));
		
	return $objResponse;
}

function listaClaveMovimiento($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_clave_mov.tipo IN (%s)",
			valTpDato($valCadBusq[0], "campo"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_clave_mov.documento_genera IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_clave_mov.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$arrayBusq = "";
		if (in_array(1, explode(",",$valCadBusq[3]))) {
			$arrayBusq[] = sprintf("vw_pg_clave_mov.pago_contado = 1");
		}
		if (in_array(0, explode(",",$valCadBusq[3]))) {
			$arrayBusq[] = sprintf("vw_pg_clave_mov.pago_credito = 1");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_clave_mov.estatus = %s",
			valTpDato($valCadBusq[4], "boolean"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_clave_mov.clave LIKE %s
		OR vw_pg_clave_mov.descripcion LIKE %s
		OR vw_pg_clave_mov.clave_contra LIKE %s
		OR vw_pg_clave_mov.descripcion_clave_movimiento_contra LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_clave_mov.*,
		numeracion_dcto.nombreNumeraciones AS nombre_numeracion_documento,
		numeracion_control.nombreNumeraciones AS nombre_numeracion_control
	FROM vw_pg_clave_movimiento vw_pg_clave_mov
		LEFT JOIN pg_numeracion numeracion_dcto ON (vw_pg_clave_mov.id_numeracion_documento = numeracion_dcto.id_numeracion)
		LEFT JOIN pg_numeracion numeracion_control ON (vw_pg_clave_mov.id_numeracion_control = numeracion_control.id_numeracion) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s, tipo, clave %s", $campOrd, $tpOrd) : "";
	
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
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaClaveMovimiento", "4%", $pageNum, "id_clave_movimiento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaClaveMovimiento", "20%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaClaveMovimiento", "8%", $pageNum, "tipo_movimiento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo Mov."));
		$htmlTh .= ordenarCampo("xajax_listaClaveMovimiento", "10%", $pageNum, "descripcion_documento_genera", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Dcto. Generado"));
		$htmlTh .= ordenarCampo("xajax_listaClaveMovimiento", "14%", $pageNum, "descripcion_modulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Módulo"));
		$htmlTh .= ordenarCampo("xajax_listaClaveMovimiento", "20%", $pageNum, "nombre_numeracion_documento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Numeración Dcto"));
		$htmlTh .= ordenarCampo("xajax_listaClaveMovimiento", "20%", $pageNum, "nombre_numeracion_control", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Numeración Control"));
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "";
		}
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_clave_movimiento']);
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_clave_movimiento']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= $row['clave'].") ".utf8_encode($row['descripcion']);
				$htmlTb .= (strlen($row['clave_contra']) > 0) ? "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['clave_contra'].") ".utf8_encode($row['descripcion_clave_movimiento_contra']) : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo'].".- ".$row['tipo_movimiento'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['documento_genera'].".- ".$row['descripcion_documento_genera'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($row['id_modulo'].".- ".$row['descripcion_modulo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_numeracion_documento'].".- ".$row['nombre_numeracion_documento'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_numeracion_control'].".- ".$row['nombre_numeracion_control'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblClaveMovimiento', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_clave_movimiento']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClaveMovimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClaveMovimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaClaveMovimiento(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClaveMovimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClaveMovimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
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
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaClaveMovimiento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNumeracion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombreNumeraciones LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM pg_numeracion %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaNumeracion", "8%", $pageNum, "id_numeracion", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Id"));
		$htmlTh .= ordenarCampo("xajax_listaNumeracion", "92%", $pageNum, "nombreNumeraciones", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Descripción"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNumeracion('".$row['id_numeracion']."','".$valCadBusq[1]."');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_numeracion']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombreNumeraciones'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNumeracion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarNumeracion");
$xajax->register(XAJAX_FUNCTION,"asignarTipoMovimiento");
$xajax->register(XAJAX_FUNCTION,"buscarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"buscarNumeracion");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstDctoGenerado");
$xajax->register(XAJAX_FUNCTION,"cargaLstDctoGeneradoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstatus");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstatusBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModuloBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"eliminarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"formClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"guardarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"listaClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"listaNumeracion");
?>