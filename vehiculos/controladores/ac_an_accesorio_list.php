<?php


function asignarCliente($idCliente, $idEmpresa = "", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false"){
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id = %s
	AND status = 'Activo'",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$idClaveMovimiento = ($idClaveMovimiento == "") ? $rowCliente['id_clave_movimiento_predeterminado'] : $idClaveMovimiento;
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoCliente);
		
		$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		$objResponse->assign("hddTipoPagoCliente","value",0);
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "0", "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));*/
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','0','1','".$idClaveMovimiento."','onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"');
		}");
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		$objResponse->assign("hddTipoPagoCliente","value",1);
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));*/
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','1','1','".$idClaveMovimiento."','onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"');
		}");
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0 && $rowCliente['id'] > 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
	
	//$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarMotivo($idMotivo, $nombreObjeto, $cxPcxC = NULL, $ingresoEgreso = NULL, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	if ($cxPcxC != "-1" && $cxPcxC != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("modulo LIKE %s",
			valTpDato($cxPcxC, "text"));
	}
	
	if ($ingresoEgreso != "-1" && $ingresoEgreso != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ingreso_egreso LIKE %s",
			valTpDato($ingresoEgreso, "text"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_motivo = %s",
		valTpDato($idMotivo, "int"));
	
	$query = sprintf("SELECT * FROM pg_motivo %s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$row['id_motivo']);
	$objResponse->assign("txt".$nombreObjeto,"value",utf8_encode($row['descripcion']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaMotivo').click();");
	}
	
	return $objResponse;
}

function asignarTipoAdicional($idTipoAdicional) {
	$objResponse = new xajaxResponse();
	
	/*$objResponse->script("byId('trParametroAdicionalContrato').style.display = 'none';");
	$objResponse->script("byId('trParametroAdicionalCargo').style.display = 'none';");*/
	switch ($idTipoAdicional) {
		case 1 :
			$objResponse->script("txtIdCliente","value","");
			$objResponse->script("txtIdMotivo","value","");
			$objResponse->script("txtPorcentajeComision","value",number_format(0, 2, ".", ","));
			$objResponse->loadCommands(asignarMotivo("", "Motivo", "CC", "I"));
			$objResponse->loadCommands(asignarCliente(""));
			break;
		case 3 :
			//$objResponse->script("byId('trParametroAdicionalContrato').style.display = '';");
			break;
		case 4 :
			//$objResponse->script("byId('trParametroAdicionalCargo').style.display = '';");
			break;
	}
	
	return $objResponse;
}

function asignarTipoComision($idTipoComision) {
	$objResponse = new xajaxResponse();
	
	switch ($idTipoComision) {
		case 1 : // 1 = Porcentaje
			$objResponse->script("
			byId('txtPorcentajeComision').className = 'inputHabilitado';
			byId('txtPorcentajeComision').readOnly = false;
			byId('txtMontoComision').className = 'inputInicial';
			byId('txtMontoComision').readOnly = true;
			
			byId('txtPorcentajeComision').focus()
			byId('txtPorcentajeComision').select();");
			break;
		case 2 : // 2 = Monto
			$objResponse->script("
			byId('txtPorcentajeComision').className = 'inputInicial';
			byId('txtPorcentajeComision').readOnly = true;
			byId('txtMontoComision').className = 'inputHabilitado';
			byId('txtMontoComision').readOnly = false;
			
			byId('txtMontoComision').focus();
			byId('txtMontoComision').select();");
			break;
		case 3 : // 3 = Utilidad
			$objResponse->script("
			byId('txtPorcentajeComision').className = 'inputInicial';
			byId('txtPorcentajeComision').readOnly = true;
			byId('txtMontoComision').className = 'inputInicial';
			byId('txtMontoComision').readOnly = true;");
			
			$objResponse->assign("txtPorcentajeComision","value",number_format(0, 2, ".", ","));
			$objResponse->assign("txtMontoComision","value",number_format(0, 2, ".", ","));
			break;
	}
	
	return $objResponse;
}

function buscarAccesorio($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		(is_array($frmBuscar['lstTipoAdicionalBuscar']) ? implode(",",$frmBuscar['lstTipoAdicionalBuscar']) : $frmBuscar['lstTipoAdicionalBuscar']),
		(is_array($frmBuscar['lstPoseeIvaBuscar']) ? implode(",",$frmBuscar['lstPoseeIvaBuscar']) : $frmBuscar['lstPoseeIvaBuscar']),
		(is_array($frmBuscar['lstMostrarPredeterminadoBuscar']) ? implode(",",$frmBuscar['lstMostrarPredeterminadoBuscar']) : $frmBuscar['lstMostrarPredeterminadoBuscar']),
		(is_array($frmBuscar['lstGeneraComisionBuscar']) ? implode(",",$frmBuscar['lstGeneraComisionBuscar']) : $frmBuscar['lstGeneraComisionBuscar']),
		(is_array($frmBuscar['lstIncluirCostoCompraUnidadBuscar']) ? implode(",",$frmBuscar['lstIncluirCostoCompraUnidadBuscar']) : $frmBuscar['lstIncluirCostoCompraUnidadBuscar']),
		(is_array($frmBuscar['lstActivoBuscar']) ? implode(",",$frmBuscar['lstActivoBuscar']) : $frmBuscar['lstActivoBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaAccesorio(0, "nom_accesorio", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarMotivo($frmBuscarMotivo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarMotivo['hddObjDestinoMotivo'],
		$frmBuscarMotivo['hddPagarCobrarMotivo'],
		$frmBuscarMotivo['hddIngresoEgresoMotivo'],
		$frmBuscarMotivo['txtCriterioBuscarMotivo']);
	
	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstSiNo($nombreObjeto, $objetoBuscar = "false", $selId = "", $onChange = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"".$onChange."\"";
	
	$array = array(0 => "No", 1 => "Si");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2 && $objetoBuscar == "true") ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML", $html);
	
	return $objResponse;
}

function cargaLstTipoAdicional($nombreObjeto, $objetoBuscar = "false", $selId = "", $onChange = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"".$onChange."\"";
	
	$array = array(1 => "Adicional", 3 => "Contrato", 4 => "Cargo");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2 && $objetoBuscar == "true") ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML", $html);
	
	return $objResponse;
}

function eliminarAccesorio($idAccesorio, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_accesorio_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_accesorio WHERE id_accesorio = %s",
		valTpDato($idAccesorio, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaAccesorio(
		$frmListaAccesorio['pageNum'],
		$frmListaAccesorio['campOrd'],
		$frmListaAccesorio['tpOrd'],
		$frmListaAccesorio['valBusq']));
	
	return $objResponse;
}

function formAccesorio($idAccesorio) {
	$objResponse = new xajaxResponse();
	
	if ($idAccesorio > 0) {
		if (!xvalidaAcceso($objResponse,"an_accesorio_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAccesorio').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM an_accesorio WHERE id_accesorio = %s;",
			valTpDato($idAccesorio, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdAccesorio","value",$idAccesorio);
		$objResponse->loadCommands(cargaLstTipoAdicional("lstTipoAdicional", "false", $row['id_tipo_accesorio'], "xajax_asignarTipoAdicional(this.value);"));
		$objResponse->loadCommands(asignarTipoAdicional($row['id_tipo_accesorio']));
		$objResponse->assign("lstActivo","value",$row['activo']);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nom_accesorio']));
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['des_accesorio']));
		$objResponse->assign("txtPrecio","value",number_format($row['precio_accesorio'], 2, ".", ","));
		$objResponse->assign("txtCosto","value",number_format($row['costo_accesorio'], 2, ".", ","));
		$objResponse->loadCommands(cargaLstSiNo("lstPoseeIva", "false", $row['iva_accesorio']));
		$objResponse->loadCommands(cargaLstSiNo("lstMostrarPredeterminado", "false", $row['id_mostrar_predeterminado']));
		$objResponse->loadCommands(cargaLstSiNo("lstGeneraComision", "false", $row['genera_comision'], "selectedOption(this.id,'".$row['genera_comision']."');"));
		$objResponse->script("byId('aGeneraComision').style.display = '';");
		$objResponse->loadCommands(cargaLstSiNo("lstIncluirCostoCompraUnidad", "false", $row['incluir_costo_compra_unidad'], "selectedOption(this.id,'".$row['incluir_costo_compra_unidad']."');"));
		$objResponse->script("byId('aIncluirCostoCompraUnidad').style.display = '';");
		
		$objResponse->loadCommands(asignarCliente($row['id_cliente']));
		$objResponse->loadCommands(asignarMotivo($row['id_motivo'], "Motivo"));
		$objResponse->script("byId('lstTipoComision').onchange = function(){ xajax_asignarTipoComision(this.value); };");
		$objResponse->call("selectedOption","lstTipoComision",$row['id_tipo_comision']);
		$objResponse->assign("txtPorcentajeComision","value",number_format($row['porcentaje_comision'], 2, ".", ","));
		$objResponse->assign("txtMontoComision","value",number_format($row['monto_comision'], 2, ".", ","));
		$objResponse->loadCommands(asignarTipoComision($row['id_tipo_comision']));
		
		$objResponse->loadCommands(asignarMotivo($row['id_motivo_cargo'], "MotivoCargo"));
	} else {
		if (!xvalidaAcceso($objResponse,"an_accesorio_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAccesorio').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstTipoAdicional("lstTipoAdicional", "false", "", "xajax_asignarTipoAdicional(this.value);"));
		$objResponse->loadCommands(asignarTipoAdicional(""));
		$objResponse->assign("lstActivo","value",1);
		$objResponse->assign("txtPrecio","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtCosto","value",number_format(0, 2, ".", ","));
		$objResponse->loadCommands(cargaLstSiNo("lstPoseeIva", "false", "1"));
		$objResponse->loadCommands(cargaLstSiNo("lstMostrarPredeterminado", "false", "0"));
		$objResponse->loadCommands(cargaLstSiNo("lstGeneraComision", "false", "0", "selectedOption(this.id,'0');"));
		$objResponse->script("byId('aGeneraComision').style.display = '';");
		$objResponse->loadCommands(cargaLstSiNo("lstIncluirCostoCompraUnidad", "false", "1", "selectedOption(this.id,'1');"));
		$objResponse->script("byId('aIncluirCostoCompraUnidad').style.display = '';");
		
		$objResponse->script("byId('lstTipoComision').onchange = function(){ xajax_asignarTipoComision(this.value); };");
		$objResponse->assign("txtPorcentajeComision","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtMontoComision","value",number_format(0, 2, ".", ","));
	}
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	return $objResponse;
}

function guardarAccesorio($frmAccesorio, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idAccesorio = $frmAccesorio['hddIdAccesorio'];
	
	if (!($frmAccesorio['txtIdMotivo'] > 0)) {
		$frmAccesorio['txtIdCliente'] = "";
		$frmAccesorio['lstTipoComision'] = "";
		$frmAccesorio['txtPorcentajeComision'] = 0;
		$frmAccesorio['txtMontoComision'] = 0;
	}
	
	if ($idAccesorio > 0) {
		if (!xvalidaAcceso($objResponse,"an_accesorio_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_accesorio SET
			id_tipo_accesorio = %s,
			nom_accesorio = %s,
			des_accesorio = %s,
			precio_accesorio = %s,
			costo_accesorio = %s,
			iva_accesorio = %s,
			genera_comision = %s,
			incluir_costo_compra_unidad = %s,
			id_cliente = %s,
			id_motivo = %s,
			id_tipo_comision = %s,
			porcentaje_comision = %s,
			monto_comision = %s,
			id_motivo_cargo = %s,
			id_mostrar_predeterminado = %s,
			activo = %s
		WHERE id_accesorio = %s;",
			valTpDato($frmAccesorio['lstTipoAdicional'], "int"), // 1 = Adicional, 2 = Accesorio, 3 = Contrato, 4 = Cargo
			valTpDato($frmAccesorio['txtNombre'], "text"),
			valTpDato($frmAccesorio['txtDescripcion'], "text"),
			valTpDato($frmAccesorio['txtPrecio'], "real_inglesa"),
			valTpDato($frmAccesorio['txtCosto'], "real_inglesa"),
			valTpDato($frmAccesorio['lstPoseeIva'], "boolean"),
			valTpDato($frmAccesorio['lstGeneraComision'], "boolean"),
			valTpDato($frmAccesorio['lstIncluirCostoCompraUnidad'], "boolean"),
			valTpDato($frmAccesorio['txtIdCliente'], "int"),
			valTpDato($frmAccesorio['txtIdMotivo'], "int"),
			valTpDato($frmAccesorio['lstTipoComision'], "int"),
			valTpDato($frmAccesorio['txtPorcentajeComision'], "real_inglesa"),
			valTpDato($frmAccesorio['txtMontoComision'], "real_inglesa"),
			valTpDato($frmAccesorio['txtIdMotivoCargo'], "int"),
			valTpDato($frmAccesorio['lstMostrarPredeterminado'], "int"),
			valTpDato($frmAccesorio['lstActivo'], "boolean"),
			valTpDato($idAccesorio, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"an_accesorio_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO an_accesorio (id_modulo, id_tipo_accesorio, nom_accesorio, des_accesorio, precio_accesorio, costo_accesorio, iva_accesorio, genera_comision, incluir_costo_compra_unidad, id_cliente, id_motivo, id_tipo_comision, porcentaje_comision, monto_comision, id_motivo_cargo, id_mostrar_predeterminado, activo)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato(2, "int"), // 2 = Vehiculos
			valTpDato($frmAccesorio['lstTipoAdicional'], "int"), // 1 = Adicional, 2 = Accesorio, 3 = Contrato, 4 = Cargo
			valTpDato($frmAccesorio['txtNombre'], "text"),
			valTpDato($frmAccesorio['txtDescripcion'], "text"),
			valTpDato($frmAccesorio['txtPrecio'], "real_inglesa"),
			valTpDato($frmAccesorio['txtCosto'], "real_inglesa"),
			valTpDato($frmAccesorio['lstPoseeIva'], "boolean"),
			valTpDato($frmAccesorio['lstGeneraComision'], "boolean"),
			valTpDato($frmAccesorio['lstIncluirCostoCompraUnidad'], "boolean"),
			valTpDato($frmAccesorio['txtIdCliente'], "int"),
			valTpDato($frmAccesorio['txtIdMotivo'], "int"),
			valTpDato($frmAccesorio['lstTipoComision'], "int"),
			valTpDato($frmAccesorio['txtPorcentajeComision'], "real_inglesa"),
			valTpDato($frmAccesorio['txtMontoComision'], "real_inglesa"),
			valTpDato($frmAccesorio['txtIdMotivoCargo'], "int"),
			valTpDato($frmAccesorio['lstMostrarPredeterminado'], "int"),
			valTpDato($frmAccesorio['lstActivo'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idAccesorio = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Adicional guardado con éxito.");
	
	$objResponse->script("
	byId('btnCancelarAccesorio').click();");
	
	$objResponse->loadCommands(listaAccesorio(
		$frmListaAccesorio['pageNum'],
		$frmListaAccesorio['campOrd'],
		$frmListaAccesorio['tpOrd'],
		$frmListaAccesorio['valBusq']));
	
	return $objResponse;
}

function listaAccesorio($pageNum = 0, $campOrd = "nom_accesorio", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("acc.id_tipo_accesorio IN (1,3,4)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("acc.id_modulo IN (2)");
        
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("acc.id_tipo_accesorio IN (%s)",
			valTpDato($valCadBusq[0], "campo"));
	}
        
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("acc.iva_accesorio IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
        
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("acc.id_mostrar_predeterminado IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
        
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("acc.genera_comision IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
        
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("acc.incluir_costo_compra_unidad IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
        
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("acc.activo IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
		
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(acc.nom_accesorio LIKE %s
		OR acc.des_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT
		acc.id_accesorio,
		acc.id_modulo,
		acc.id_tipo_accesorio,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN	'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
			WHEN 4 THEN 'Cargo'
		END) AS descripcion_tipo_accesorio,
		CONCAT(acc.nom_accesorio, IF (acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		acc.precio_accesorio,
		acc.costo_accesorio,
		acc.iva_accesorio,
		acc.genera_comision,
		acc.incluir_costo_compra_unidad,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		acc.id_tipo_comision,
		acc.porcentaje_comision,
		acc.monto_comision,
		acc.id_motivo_cargo,
		acc.id_mostrar_predeterminado,
		acc.id_filtro_factura,
		acc.activo
	FROM an_accesorio acc
		LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
		LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "22%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "26%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "8%", $pageNum, "descripcion_tipo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Adicional");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "7%", $pageNum, "iva_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Aplica Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "7%", $pageNum, "id_mostrar_predeterminado", $campOrd, $tpOrd, $valBusq, $maxRows, "Mostrar Predeterminado");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "7%", $pageNum, "genera_comision", $campOrd, $tpOrd, $valBusq, $maxRows, "Genera Comisión");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "7%", $pageNum, "incluir_costo_compra_unidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Incluir en Costo de la Unidad");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "8%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "8%", $pageNum, "costo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$imgActivo = "<img src=\"../img/iconos/ico_verde.gif\">";
		
		if($row['activo'] == "0"){
			$imgActivo = "<img src=\"../img/iconos/ico_rojo.gif\">";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgActivo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['des_accesorio'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['nombre_cliente']) > 0) ? "<tr><td><span class=\"textoNegrita_10px\">".utf8_encode($row['nombre_cliente'])." ".(($row['id_motivo'] > 0) ? "(Comisión: ".(($row['id_tipo_comision'] == 1) ? number_format($row['porcentaje_comision'], 2, ".", ",")."%" : cAbrevMoneda.number_format($row['monto_comision'], 2, ".", ",")).")" : "")."</span></td></tr>" : "";
				$htmlTb .= ($row['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_tipo_accesorio'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(($row['iva_accesorio'] == 1) ? "Si" : "No")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(($row['id_mostrar_predeterminado'] == 1) ? "Si" : "No")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(($row['genera_comision'] == 1) ? "Si" : "No")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(($row['incluir_costo_compra_unidad'] == 1) ? "Si" : "No")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAccesorio', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Adicional\"/></a>",
					$contFila,
					$row['id_accesorio']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if(!($row['id_filtro_factura'] > 0)){
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Adicional\"/></a>",
					$row['id_accesorio']);
			}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaAccesorio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_Ws(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cliente.telf LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente_emp.id_empresa,
		cliente.id,
		cliente.tipo,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.nit AS nit_cliente,
		cliente.licencia AS licencia_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		cliente.bloquea_venta,
		cliente.paga_impuesto,
		perfil_prospecto.compania,
		(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				1
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0, 2, NULL)
		END) AS tipo_cuenta_cliente,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				'Prospecto'
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0,
					'Prospecto Aprobado (Cliente Venta)',
					'Sin Prospectación (Cliente Post-Venta)')
		END) AS descripcion_tipo_cuenta_cliente,
		vw_pg_empleado.nombre_empleado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"".("Prospecto")."\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"".("Prospecto Aprobado (Cliente Venta)")."\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"".("Sin Prospectación (Cliente Post-Venta)")."\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("modulo LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ingreso_egreso LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT motivo.*,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion,
		
		(CASE motivo.ingreso_egreso
			WHEN 'I' THEN	'Ingreso'
			WHEN 'E' THEN	'Egreso'
		END) AS descripcion_tipo_transaccion
	FROM pg_motivo motivo %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "10%", $pageNum, "id_motivo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "56%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "14%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transacción");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['modulo']) {
			case "CC" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>"; break;
			case "CP" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>"; break;
			case "CJ" : break;
			case "TE" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>"; break;
			default : $imgPedidoModulo = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($row['descripcion_modulo_transaccion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".($row['descripcion_tipo_transaccion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMotivo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarPermiso($frmPermiso) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] > 0) {
		if ($frmPermiso['hddModulo'] == "an_accesorio_list_genera_comision") {
			$objResponse->script("byId('lstGeneraComision').onchange = function(){};");
			$objResponse->script("byId('aGeneraComision').style.display = 'none';");
			
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
		} else if ($frmPermiso['hddModulo'] == "an_accesorio_list_incluir_costo_unidad") {
			$objResponse->script("byId('lstIncluirCostoCompraUnidad').onchange = function(){};");
			$objResponse->script("byId('aIncluirCostoCompraUnidad').style.display = 'none';");
			
			$objResponse->script("byId('btnCancelarPermiso').click();");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarTipoAdicional");
$xajax->register(XAJAX_FUNCTION,"asignarTipoComision");
$xajax->register(XAJAX_FUNCTION,"buscarAccesorio");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstSiNo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoAdicional");
$xajax->register(XAJAX_FUNCTION,"eliminarAccesorio");
$xajax->register(XAJAX_FUNCTION,"formAccesorio");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarAccesorio");
$xajax->register(XAJAX_FUNCTION,"listaAccesorio");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
?>