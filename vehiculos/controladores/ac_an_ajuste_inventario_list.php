<?php


function asignarCliente($idCliente, $idEmpresa, $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	WHERE id = %s
		AND id_empresa = %s
		AND status = 'Activo';",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
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
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefonosCliente","value",utf8_encode($rowCliente['telf']));
	$objResponse->assign("txtRifCliente","value",utf8_encode($rowCliente['ci_cliente']));
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	}
	
	return $objResponse;
}

function asignarDcto($idDcto) {
	$objResponse = new xajaxResponse();
	
	$queryDcto = sprintf("SELECT cxc_nc.*,
		cliente.id AS id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		(cxc_nc.subtotalNotaCredito - subtotal_descuento + ivaLujoNotaCredito + ivaNotaCredito) AS total_nota_credito
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
	WHERE idNotaCredito = %s",
		valTpDato($idDcto, "int"));
	$rsDcto = mysql_query($queryDcto);
	if (!$rsDcto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	$objResponse->assign("txtIdCliente","value",$rowDcto['id_cliente']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowDcto['nombre_cliente']));
	
	$objResponse->assign("hddIdDcto","value",$idDcto);
	$objResponse->assign("txtNroDcto","value",$rowDcto['numeracion_nota_credito']);
	
	$objResponse->script("
	byId('btnCancelarLista').click();");
	
	return $objResponse;
}

function asignarDctoAgregado($idTipoMovimientoAgregado, $tipoDctoAgregado, $idDocumento, $frmUniFisAgregado) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUniFisAgregado['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$Result1 = insertarItemDctoAgregado($contFila, "", $idTipoMovimientoAgregado, $tipoDctoAgregado, $idDocumento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj[] = $contFila;
	}
	
	$objResponse->script("xajax_calcularAgregado(xajax.getFormValues('frmUniFisAgregado'));");
	
	$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	
	return $objResponse;
}

function asignarTipoVale($idTipoVale) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("txtIdCliente","value","");
	$objResponse->assign("txtNombreCliente","value","");
	$objResponse->assign("hddIdDcto","value","");
	$objResponse->assign("txtNroDcto","value","");
	
	if ($idTipoVale == 1) { // DE ENTRADA O SALIDA
		$objResponse->script("
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		byId('lstTipoMovimiento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = false;
		byId('btnListarCliente').style.display = '';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value, '', '5,6');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
	} else if ($idTipoVale == 3) { // DE NOTA DE CREDITO DE CxC
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputHabilitado';
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = '';
		byId('lstTipoMovimiento').disabled = false;
		
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id,2);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value, '', '3');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",2);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", 2, "", "3"));
	} else {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('lstTipoVale').className = 'inputHabilitado';
		byId('txtNroDcto').className = 'inputInicial';
		byId('lstTipoMovimiento').className = 'inputInicial';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('txtIdCliente').readOnly = true;
		byId('btnListarCliente').style.display = 'none';
		byId('trNroDcto').style.display = 'none';
		byId('lstTipoMovimiento').disabled = true;
		
		byId('lstTipoMovimiento').onchange = function() {
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '2', this.value);
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",-1);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", -2));
	}
	
	return $objResponse;
}

function asignarUnidadBasica($nombreObjeto, $idUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
	FROM an_uni_bas uni_bas
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtClaveUnidadBasica".$nombreObjeto, "value", utf8_encode($row['clv_uni_bas']));
	$objResponse->assign("txtDescripcion".$nombreObjeto, "value", utf8_encode($row['des_uni_bas']));
	$objResponse->assign("hddIdMarcaUnidadBasica".$nombreObjeto,"value",$row['id_marca']);
	$objResponse->assign("txtMarcaUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_marca']));
	$objResponse->assign("hddIdModeloUnidadBasica".$nombreObjeto,"value",$row['id_modelo']);
	$objResponse->assign("txtModeloUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_modelo']));
	$objResponse->assign("hddIdVersionUnidadBasica".$nombreObjeto,"value",$row['id_version']);
	$objResponse->assign("txtVersionUnidadBasica".$nombreObjeto,"value",utf8_encode($row['nom_version']));
	$objResponse->loadCommands(cargaLstAno($row['ano_uni_bas'], true));
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmAjusteInventario['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarDocumento($frmBuscarDocumento, $frmUniFisAgregado) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmUniFisAgregado['hddIdEmpresaUnidadFisicaAgregado'],
		$frmBuscarDocumento['txtFechaDesdeBuscarDocumento'],
		$frmBuscarDocumento['txtFechaHastaBuscarDocumento'],
		implode(",",$frmBuscarDocumento['lstModulo']),
		$frmBuscarDocumento['txtCriterioBuscarDocumento'],
		$frmBuscarDocumento['hddObjDestinoDocumento']);
	
	$objResponse->loadCommands(listaValeSalida(0, "numero_vale", "DESC", $valBusq, 10, NULL, $frmUniFisAgregado));
	$objResponse->loadCommands(listaNotaCreditoCxC(0, "idNotaCredito", "DESC", $valBusq, 10, NULL, $frmUniFisAgregado));
	$objResponse->loadCommands(listaNotaDebitoCxC(0, "idNotaCargo", "DESC", $valBusq, 10, NULL, $frmUniFisAgregado));
		
	return $objResponse;
}

function buscarNotaCreditoValeEnt($frmBuscarLista, $frmAjusteInventario) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmAjusteInventario['txtIdEmpresa'],
		$frmBuscarLista['txtCriterioBuscarLista']);
	
	$objResponse->loadCommands(listaNotaCreditoValeEnt(0, "numeracion_nota_credito", "DESC", $valBusq));
	
	return $objResponse;
}

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

function calcularAgregado($frmUniFisAgregado) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUniFisAgregado['cbx'];
	
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
			
			$txtTotalAgregado += str_replace(",", "", $frmUniFisAgregado['txtTotalDcto'.$valor]);
		}
	}
	
	$objResponse->assign("txtTotalAgregado","value",number_format($txtTotalAgregado, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstAlmacen($nombreObjeto, $idEmpresa, $selId = "") {
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
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".htmlentities($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
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

function cargaLstAno($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = "SELECT id_ano, nom_ano FROM an_ano ORDER BY nom_ano DESC";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['id_ano']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=".$row['id_ano'].">".htmlentities($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);
	
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
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
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

function cargaLstColor($nombreObjeto, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM an_color ORDER BY nom_color");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_color']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_color']."\">".htmlentities($row['nom_color'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCondicion($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstCondicion\" name=\"lstCondicion\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_condicion_unidad'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_condicion_unidad']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicion","innerHTML",$html);
	
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

function cargaLstEstadoVenta($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "TRANSITO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "POR REGISTRAR" : "";
	($accion != "Ajuste") ? $array[] = "SINIESTRADO" : "";
	$array[] = "DISPONIBLE";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "RESERVADO" : "";
	(($accion != "Ajuste" && $accion != "Venta" && $accion != "Existencia") || $selId == "VENDIDO") ? $array[] = "VENDIDO" : "";
	($accion != "Ajuste" && $accion != "Venta" && $accion != "Existencia") ? $array[] = "ENTREGADO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "PRESTADO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "ACTIVO FIJO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "INTERCAMBIO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "DEVUELTO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "ERROR EN TRASPASO" : "";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
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
	
	if ($accion == "Ajuste") {
		$array = array("DISPONIBLE","PRESTADO","ACTIVO FIJO","INTERCAMBIO","DEVUELTO","ERROR EN TRASPASO");
	} else if ($accion == "Existencia") {
		$array = array("TRANSITO","POR REGISTRAR","SINIESTRADO","DISPONIBLE","RESERVADO");
	} else if ($accion == "Venta") {
		$array = array("SINIESTRADO","DISPONIBLE");
	} else {
		$array = array("TRANSITO","POR REGISTRAR","SINIESTRADO","DISPONIBLE","RESERVADO","VENDIDO","ENTREGADO","PRESTADO","ACTIVO FIJO","INTERCAMBIO","DEVUELTO","ERROR EN TRASPASO");
	}
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

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscarDocumento').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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

function cargaLstPaisOrigen($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_origen ORDER BY nom_origen");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstPaisOrigen\" name=\"lstPaisOrigen\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_origen']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_origen']."\">".htmlentities($row['nom_origen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstPaisOrigen","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoActivo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = "SELECT Codigo, Descripcion FROM ".DBASE_CONTAB.".tipoactivo tipo_activo ORDER BY Codigo ASC";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoActivo\" name=\"lstTipoActivo\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['Codigo']) ? "selected=\"selected\"" : "";
		
		$html .= "<optgroup label=\"".$row['Codigo']."\">";
			$html .= "<option ".$selected." value=".$row['Codigo'].">".htmlentities($row['Descripcion'])."</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoActivo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoMovimiento($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$array = array(
		1 => "COMPRA",
		2 => "ENTRADA",
		3 => "VENTA",
		4 => "SALIDA");
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".$indice.".- ".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstUnidadBasica($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_uni_bas ORDER BY nom_uni_bas");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstUnidadBasica\" name=\"lstUnidadBasica\" class=\"inputHabilitado\" onchange=\"xajax_asignarUnidadBasica('Ajuste', this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uni_bas']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_uni_bas']."\">".htmlentities($row['nom_uni_bas'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUnidadBasica","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstUso($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_uso ORDER BY nom_uso");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstUso\" name=\"lstUso\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uso']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['id_uso']."\" ".$selected.">".htmlentities($row['nom_uso'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUso","innerHTML",$html);
	
	return $objResponse;
}

function eliminarAgregado($frmUniFisAgregado) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmUniFisAgregado['cbxItm'])) {
		foreach ($frmUniFisAgregado['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarAgregado(xajax.getFormValues('frmUniFisAgregado'));");
	}
	
	$objResponse->script("xajax_calcularAgregado(xajax.getFormValues('frmUniFisAgregado'));");
	
	return $objResponse;
}

function formAjusteInventario($frmUnidadFisica, $existeUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_ajuste_inventario_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAjusteInventario').click();"); return $objResponse; }
	
	$objResponse->script("
	byId('tdTipoActivo').style.display = 'none';
	byId('tdlstTipoActivo').style.display = 'none';
	byId('trCostoUnidad').style.display = 'none';
	byId('trMontoDepreciado').style.display = 'none';");
	
	if ($existeUnidadFisica == 1) {
		$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
		$objResponse->script("byId('trUnidadFisica').style.display = 'none';");
	} else {
		$idUnidadFisica = "";
		$objResponse->script("byId('trUnidadFisica').style.display = '';");
	}
	
	// BUSCA LOS DATOS DE LA UNIDAD
	$query = sprintf("SELECT 
		alm.id_empresa,
		uni_fis.costo_compra,
		uni_fis.precio_compra,
		uni_fis.costo_agregado,
		uni_fis.costo_depreciado,
		uni_fis.costo_trade_in,
		uni_fis.id_activo_fijo,
		uni_fis.estado_venta
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
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
	
	if ($frmUnidadFisica['hddEstadoVenta'] == "DISPONIBLE" && $frmUnidadFisica['lstEstadoVenta'] != "DISPONIBLE") {
		$lstTipoMovimiento = 4;
		$documentoGenera = 5; // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
	} else if ($frmUnidadFisica['hddEstadoVenta'] != "DISPONIBLE" && $frmUnidadFisica['lstEstadoVenta'] == "DISPONIBLE") {
		$lstTipoMovimiento = 2;
		$documentoGenera = 6; // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
	}
	
	if ($existeUnidadFisica == 1) {
		$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "Empresa", "ListaEmpresa", "", false));
		
		if ($row['estado_venta'] == "ACTIVO FIJO") {
			$objResponse->script("
			byId('trCostoUnidad').style.display = '';
			byId('trMontoDepreciado').style.display = '';");
			
			$queryDepreciacionActivo = sprintf("SELECT
				depreciacion.*,
				activo_fijo.CompAdquisicion
			FROM ".DBASE_CONTAB.".deprecactivos activo_fijo
				INNER JOIN ".DBASE_CONTAB.".con_depreciacion depreciacion ON (activo_fijo.Codigo = depreciacion.codigoactivos)
			WHERE activo_fijo.Codigo = %s
				AND depreciacion.anomes LIKE %s;",
				valTpDato($row['id_activo_fijo'], "int"),
				valTpDato(date("Ym"), "text"));
			$rsDepreciacionActivo = mysql_query($queryDepreciacionActivo);
			if (!$rsDepreciacionActivo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsDepreciacionActivo = mysql_num_rows($rsDepreciacionActivo);
			$rowDepreciacionActivo = mysql_fetch_assoc($rsDepreciacionActivo);
			
			$objResponse->assign("txtCostoCompra","value",number_format(($row['precio_compra'] + $row['costo_agregado'] - $row['costo_depreciado'] - $row['costo_trade_in']), 2, ".", ","));
			$objResponse->assign("txtMontoDepreciado","value",number_format($rowDepreciacionActivo['depreciacionacumulada'], 2, ".", ","));
			$txtSubTotal = ($row['precio_compra'] + $row['costo_agregado'] - $row['costo_depreciado'] - $row['costo_trade_in']) - $rowDepreciacionActivo['depreciacionacumulada'];
		} else {
			$txtSubTotal = ($row['precio_compra'] + $row['costo_agregado'] - $row['costo_depreciado'] - $row['costo_trade_in']);
		}
		
		$objResponse->call("selectedOption","lstTipoVale",1);
		$objResponse->loadCommands(asignarTipoVale(1));
		$objResponse->loadCommands(cargaLstTipoMovimiento("lstTipoMovimiento", $lstTipoMovimiento));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", 2, $lstTipoMovimiento, "", $documentoGenera));
		$objResponse->assign("txtIdUnidadFisicaAjuste","value",$idUnidadFisica);
		$objResponse->assign("txtEstadoCompraAjuste","value",$frmUnidadFisica['txtEstadoCompra']);
		$objResponse->assign("txtEstadoVentaAjuste","value",$frmUnidadFisica['lstEstadoVenta']);
		$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
		
		if ($frmUnidadFisica['lstEstadoVenta'] == "ACTIVO FIJO") {
			$objResponse->script("
			byId('tdTipoActivo').style.display = '';
			byId('tdlstTipoActivo').style.display = '';");
			$objResponse->loadCommands(cargaLstTipoActivo());
		}
		
		$objResponse->script("
		byId('lstTipoVale').onchange = function() {
			selectedOption(this.id, ".(1).");
		}");
		
		if (in_array($row['estado_venta'],array("VENDIDO"))) {
			$objResponse->script("
			byId('txtSubTotal').readOnly = false;
			byId('txtSubTotal').className = 'inputCompletoHabilitado';");
		} else {
			$objResponse->script("
			byId('txtSubTotal').readOnly = true;
			byId('txtSubTotal').className = 'inputSinFondo';");
		}
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id, ".$lstTipoMovimiento.");
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento', 2, this.value, '', ".$documentoGenera.");
		}");
	} else {
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));", false));
		$objResponse->assign("txtFecha","value",date(spanDateFormat));
		$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
		
		$objResponse->loadCommands(asignarTipoVale(""));
		$objResponse->loadCommands(cargaLstTipoMovimiento("lstTipoMovimiento", $lstTipoMovimiento));
		
		$objResponse->loadCommands(cargaLstUnidadBasica());
		
		$objResponse->loadCommands(cargaLstAno());
		$objResponse->loadCommands(cargaLstCondicion());
		
		$objResponse->loadCommands(cargaLstColor("lstColorExterno1"));
		$objResponse->loadCommands(cargaLstColor("lstColorInterno1"));
		$objResponse->loadCommands(cargaLstColor("lstColorExterno2"));
		$objResponse->loadCommands(cargaLstColor("lstColorInterno2"));
		
		$objResponse->loadCommands(cargaLstPaisOrigen());
		$objResponse->loadCommands(cargaLstUso());
		
		$objResponse->loadCommands(cargaLstAlmacen('lstAlmacenAjuste', $_SESSION['idEmpresaUsuarioSysGts']));
		$objResponse->assign("txtEstadoCompraAjuste","value","REGISTRADO");
		$objResponse->loadCommands(cargaLstEstadoVenta("lstEstadoVentaAjuste", "Ajuste"));
		$objResponse->loadCommands(cargaLstMoneda());
		
		$objResponse->script("
		byId('lstTipoVale').onchange = function() {
			xajax_asignarTipoVale(this.value);
		}
		byId('txtSubTotal').readOnly = false;
		byId('txtSubTotal').className = 'inputCompletoHabilitado';");
	}
	
	return $objResponse;
}

function formListaDocumento() {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_ajuste_inventario_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante2').click();"); return $objResponse; }
	
	$objResponse->assign("txtFechaDesdeBuscarDocumento","value",date(str_replace("d","01",spanDateFormat)));
	$objResponse->assign("txtFechaHastaBuscarDocumento","value",date(spanDateFormat));
	$objResponse->loadCommands(cargaLstModulo());
	
	$objResponse->script("byId('btnBuscarDocumento').click();");
	
	return $objResponse;
}

function formUnidadFisica($idUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	if ($idUnidadFisica > 0) {
		if (!xvalidaAcceso($objResponse,"an_ajuste_inventario_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarUnidadFisica').click();"); return $objResponse; }
		
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
		$objResponse->assign("txtAlmacen", "value", utf8_encode($row['nom_almacen']));
		$objResponse->assign("txtEstadoCompra", "value", utf8_encode($row['estado_compra']));
		$objResponse->loadCommands(cargaLstEstadoVenta("lstEstadoVenta", "Ajuste", utf8_encode($row['estado_venta'])));
		$objResponse->assign("hddEstadoVenta", "value", utf8_encode($row['estado_venta']));
	
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
	}
	
	return $objResponse;
}

function formUnidadFisicaAgregado($idUnidadFisica, $frmUniFisAgregado) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUniFisAgregado['cbx'];
	
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
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			uni_fis_agregado.id_factura_cxp
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		uni_fis_agregado.id_nota_cargo_cxp
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	uni_fis_agregado.id_nota_credito_cxp
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			uni_fis_agregado.id_factura_cxc
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		uni_fis_agregado.id_nota_cargo_cxc
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	uni_fis_agregado.id_nota_credito_cxc
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			uni_fis_agregado.id_vale_salida
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		uni_fis_agregado.id_vale_entrada
		END) AS id_factura,
		vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
	FROM an_unidad_fisica_agregado uni_fis_agregado
		LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (uni_fis_agregado.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	WHERE uni_fis_agregado.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rsUniFisAgregado = mysql_query($queryUniFisAgregado);
	if (!$rsUniFisAgregado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsUniFisAgregado = mysql_num_rows($rsUniFisAgregado);
	while ($rowUniFisAgregado = mysql_fetch_array($rsUniFisAgregado)) {
		$Result1 = insertarItemDctoAgregado($contFila, $rowUniFisAgregado['id_unidad_fisica_agregado'], $rowUniFisAgregado['id_tipo_movimiento'], $rowUniFisAgregado['tipoDocumento'], $rowUniFisAgregado['id_factura']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	$objResponse->script("xajax_calcularAgregado(xajax.getFormValues('frmUniFisAgregado'));");
	
	//$objResponse->assign("divListaUniFisAgregado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function guardarAjusteInventario($frmAjusteInventario, $frmUnidadFisica, $frmListaUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	global $arrayValidarCarroceria;
	global $spanSerialCarroceria;
	
	if (!xvalidaAcceso($objResponse,"an_ajuste_inventario_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$txtEstadoCompraAjuste = $frmAjusteInventario['txtEstadoCompraAjuste'];
	
	if ($frmAjusteInventario['lstTipoVale'] == 1) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		$idClaveMovimiento = $frmAjusteInventario['lstClaveMovimiento'];
	} else if ($frmAjusteInventario['lstTipoVale'] == 3) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		switch ($frmAjusteInventario['lstTipoMovimiento']) { // 2 = ENTRADA, 4 = SALIDA
			case 2 : $documentoGenera = 6; break;
			case 4 : $documentoGenera = 5; break;
		}
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.tipo = %s
			AND clave_mov.documento_genera = %s
			AND clave_mov.id_modulo IN (2)
		ORDER BY clave DESC 
		LIMIT 1;",
			valTpDato($frmAjusteInventario['lstTipoMovimiento'], "int"),
			valTpDato($documentoGenera, "int"));
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
		
		$idClaveMovimiento = $rowClaveMov['id_clave_movimiento'];
	}
	
	if ($frmAjusteInventario['txtIdUnidadFisicaAjuste'] > 0) {
		$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
		$idUnidadBasica = $frmUnidadFisica['hddIdUnidadBasica'];
		$txtEstadoVentaAjuste = $frmAjusteInventario['txtEstadoVentaAjuste'];
		
		// BUSCA LOS DATOS DE LA UNIDAD
		$queryUniFis = sprintf("SELECT 
			alm.id_empresa,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			uni_fis.id_activo_fijo,
			uni_fis.estado_venta
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
				INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
				INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
				INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
				INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
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
		$rsUniFis = mysql_query($queryUniFis);
		if (!$rsUniFis) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowUniFis = mysql_fetch_assoc($rsUniFis);
		
	} else {
		$idUnidadBasica = $frmAjusteInventario['lstUnidadBasica'];
		$txtEstadoVentaAjuste = $frmAjusteInventario['lstEstadoVentaAjuste'];
		
		$arrayValidar = $arrayValidarCarroceria;
		if (isset($arrayValidar)) {
			$valido = false;
			foreach ($arrayValidar as $indice => $valor) {
				if (preg_match($valor, $frmAjusteInventario['txtSerialCarroceriaAjuste'])) {
					$valido = true;
				}
			}
			
			if ($valido == false) {
				$objResponse->script("byId('txtSerialCarroceriaAjuste').className = 'inputErrado'");
				return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
			}
		}
		
		// BUSCA LOS DATOS DE LA UNIDAD BASICA
		$queryUnidadBasica = sprintf("SELECT * FROM an_uni_bas
		WHERE id_uni_bas = %s;",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidadBasica = mysql_query($queryUnidadBasica);
		if (!$rsUnidadBasica) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
		
		$insertSQL = sprintf("INSERT INTO an_unidad_fisica (id_uni_bas, ano, id_uso, id_clase, capacidad, id_condicion_unidad, id_color_externo1, id_color_externo2, id_color_interno1, id_color_interno2, id_origen, id_almacen, serial_carroceria, serial_motor, serial_chasis, titulo_vehiculo, placa, fecha_fabricacion, registro_legalizacion, registro_federal, descuento_compra, porcentaje_iva_compra, iva_compra, porcentaje_impuesto_lujo_compra, impuesto_lujo_compra, costo_compra, moneda_costo_compra, tasa_cambio_costo_compra, precio_compra, moneda_precio_compra, tasa_cambio_precio_compra, marca_cilindro, capacidad_cilindro, fecha_elaboracion_cilindro, marca_kit, modelo_regulador, serial_regulador, codigo_unico_conversion, serial1, descripcion_siniestro, fecha_pago_venta, estado_compra, estado_venta, estatus, fecha_ingreso, propiedad, tipo_placa)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idUnidadBasica, "int"),
			valTpDato($frmAjusteInventario['lstAno'], "int"),
			valTpDato($rowUnidadBasica['tip_uni_bas'], "int"),
			valTpDato($rowUnidadBasica['cla_uni_bas'], "int"),
			valTpDato($rowUnidadBasica['cap_uni_bas'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstCondicion'], "int"),
			valTpDato($frmAjusteInventario['lstColorExterno1'], "int"),
			valTpDato($frmAjusteInventario['lstColorExterno2'], "int"),
			valTpDato($frmAjusteInventario['lstColorInterno1'], "int"),
			valTpDato($frmAjusteInventario['lstColorInterno2'], "int"),
			valTpDato($rowUnidadBasica['ori_uni_bas'], "int"),
			valTpDato($frmAjusteInventario['lstAlmacenAjuste'], "int"),
			valTpDato($frmAjusteInventario['txtSerialCarroceriaAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtSerialMotorAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtNumeroVehiculoAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtTituloVehiculoAjuste'], "int"),
			valTpDato($frmAjusteInventario['txtPlacaAjuste'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmAjusteInventario['txtFechaFabricacionAjuste'])), "date"),
			valTpDato($frmAjusteInventario['txtRegistroLegalizacionAjuste'], "text"),
			valTpDato($frmAjusteInventario['txtRegistroFederalAjuste'], "text"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstMoneda'], "int"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['lstMoneda'], "int"),
			valTpDato(0, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtMarcaCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtCapacidadCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtFechaCilindro'], "text"),
			valTpDato($frmAjusteInventario['txtMarcaKit'], "text"),
			valTpDato($frmAjusteInventario['txtModeloRegulador'], "text"),
			valTpDato($frmAjusteInventario['txtSerialRegulador'], "text"),
			valTpDato($frmAjusteInventario['txtCodigoUnico'], "text"),
			valTpDato($frmAjusteInventario['txtSerial1'], "text"),
			valTpDato("", "text"),
			valTpDato("", "date"),
			valTpDato($txtEstadoCompraAjuste, "text"),
			valTpDato($txtEstadoVentaAjuste, "text"),
			valTpDato(1, "boolean"), // Null = Anulada, 1 = Activa
			valTpDato("NOW()", "campo"),
			valTpDato("PROPIO", "text"),
			valTpDato($frmAjusteInventario['lstTipoTablilla'], "text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idUnidadFisica = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	if ($frmAjusteInventario['lstTipoVale'] == 3) { // 1 = Entrada / Salida, 3 = Nota de Crédito de CxC
		// INSERTA LA UNIDAD EN EL DETALLE DE LA NOTA DE CREDITO
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo (id_nota_credito, id_unidad_fisica, costo_compra, precio_unitario)
		VALUE (%s, %s, %s, %s);",
			valTpDato($frmAjusteInventario['hddIdDcto'], "int"),
			valTpDato($idUnidadFisica, "int"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idNotaCreditoDetalleVehiculo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// BUSCA LOS DATOS DE LOS IMPUESTOS DE LA UNIDAD
		$queryUnidadBasicaImpuesto = sprintf("SELECT
			iva.idIva AS id_iva,
			iva.iva,
			iva.observacion
		FROM an_unidad_basica_impuesto uni_bas_impuesto
			INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo IN (6,9,2);",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidadBasicaImpuesto = mysql_query($queryUnidadBasicaImpuesto);
		if (!$rsUnidadBasicaImpuesto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($rowUnidadBasicaImpuesto = mysql_fetch_assoc($rsUnidadBasicaImpuesto)) {
			$hddIdIvaItm = $rowUnidadBasicaImpuesto['id_iva'];
			$hddIvaItm = $rowUnidadBasicaImpuesto['iva'];
			
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo_impuesto (id_nota_credito_detalle_vehiculo, id_impuesto, impuesto) 
			VALUE (%s, %s, %s);",
				valTpDato($idNotaCreditoDetalleVehiculo, "int"),
				valTpDato($hddIdIvaItm, "int"),
				valTpDato($hddIvaItm, "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		$arrayIdDctoContabilidad[] = array(
			$frmAjusteInventario['hddIdDcto'],
			$idModulo,
			"NOTA_CREDITO");
	}
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
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
	
	switch ($frmAjusteInventario['lstTipoMovimiento']) {
		case 2 : // ENTRADA
			// REGISTRA EL VALE DE ENTRADA
			$insertSQL = sprintf("INSERT INTO an_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_documento, id_unidad_fisica, id_cliente, id_clave_movimiento, subtotal_factura, tipo_vale_entrada, observacion)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($numeroActual, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato($frmAjusteInventario['hddIdDcto'], "int"),
				valTpDato($idUnidadFisica, "int"),
				valTpDato($frmAjusteInventario['txtIdCliente'], "int"),
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
				valTpDato($frmAjusteInventario['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
				valTpDato($frmAjusteInventario['txtObservacion'], "text"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idVale = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$arrayIdDctoContabilidad[] = array(
				$idVale,
				$idModulo,
				"ENTRADA");
			
			$estadoKardex = 0;
			
			if ($txtEstadoCompraAjuste == "REGISTRADO" && $txtEstadoVentaAjuste == "DISPONIBLE" && $rowUniFis['estado_venta'] == "ACTIVO FIJO") {
				// ACTUALIZA EL MONTO DEPRECIADO DEL ACTIVO FIJO
				$updateSQL = sprintf("UPDATE an_unidad_fisica SET
					costo_depreciado = %s,
					fecha_ingreso = %s
				WHERE id_unidad_fisica = %s;",
					valTpDato($frmAjusteInventario['txtMontoDepreciado'], "real_inglesa"),
					valTpDato("NOW()", "campo"),
					valTpDato($idUnidadFisica, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
			
			if ($rowUniFis['estado_venta'] == "VENDIDO") {
				// ACTUALIZA EL COSTO NUEVO DE LA UNIDAD
				$updateSQL = sprintf("UPDATE an_unidad_fisica SET
					costo_trade_in = (precio_compra - costo_depreciado - costo_trade_in - %s),
					fecha_ingreso = %s
				WHERE id_unidad_fisica = %s;",
					valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
					valTpDato("NOW()", "campo"),
					valTpDato($idUnidadFisica, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
			
			break;
		case 4 : // SALIDA
			// REGISTRA EL VALE DE SALIDA
			$insertSQL = sprintf("INSERT INTO an_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_unidad_fisica, id_cliente, id_clave_movimiento, subtotal_factura, tipo_vale_salida, observacion)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($numeroActual, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato($idUnidadFisica, "int"),
				valTpDato($frmAjusteInventario['txtIdCliente'], "int"),
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
				valTpDato($frmAjusteInventario['lstTipoVale'], "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
				valTpDato($frmAjusteInventario['txtObservacion'], "text"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idVale = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$arrayIdDctoContabilidad[] = array(
				$idVale,
				$idModulo,
				"SALIDA");
			
			$estadoKardex = 1;
			
			if ($txtEstadoCompraAjuste == "REGISTRADO" && $txtEstadoVentaAjuste == "ACTIVO FIJO") {
				// BUSCA LOS DATOS DE LA FACTURA DE LA UNIDAD
				$queryFactCompra = sprintf("SELECT
					cxp_fact.id_factura,
					cxp_fact.id_modo_compra,
					cxp_fact.numero_factura_proveedor,
					cxp_fact.fecha_origen,
					CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
					uni_fis.id_activo_fijo,
					uni_fis.serial_carroceria,
					uni_fis.precio_compra,
					uni_fis.costo_agregado,
					uni_fis.costo_depreciado,
					uni_fis.costo_trade_in,
					prov.id_proveedor AS id_proveedor,
					prov.nombre AS nombre_proveedor
				FROM an_unidad_fisica uni_fis
					INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
					INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
					INNER JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
					INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
				WHERE uni_fis.id_unidad_fisica = %s;",
					valTpDato($idUnidadFisica, "int"));
				$rsFactCompra = mysql_query($queryFactCompra);
				if (!$rsFactCompra) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsFactCompra = mysql_num_rows($rsFactCompra);
				
				// BUSCA LOS DATOS DEL VALE DE ENTRADA DE LA UNIDAD
				$queryValeEntrada = sprintf("SELECT
					vale_entrada.id_vale_entrada,
					1 AS id_modo_compra,
					vale_entrada.numeracion_vale_entrada,
					vale_entrada.fecha,
					CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
					uni_fis.id_activo_fijo,
					uni_fis.serial_carroceria,
					uni_fis.precio_compra,
					uni_fis.costo_agregado,
					uni_fis.costo_depreciado,
					uni_fis.costo_trade_in,
					cliente.id AS id_cliente,
					CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
				FROM an_unidad_fisica uni_fis
					INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
					INNER JOIN an_vale_entrada vale_entrada ON (uni_fis.id_unidad_fisica = vale_entrada.id_unidad_fisica)
					INNER JOIN cj_cc_cliente cliente ON (vale_entrada.id_cliente = cliente.id)
				WHERE uni_fis.id_unidad_fisica = %s
				ORDER BY vale_entrada.id_vale_entrada ASC;",
					valTpDato($idUnidadFisica, "int"));
				$rsValeEntrada = mysql_query($queryValeEntrada);
				if (!$rsValeEntrada) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsValeEntrada = mysql_num_rows($rsValeEntrada);
				
				if ($totalRowsFactCompra > 0) {
					$rowFactCompra = mysql_fetch_assoc($rsFactCompra);
					
					$txtNumeroFacturaProveedor = $rowFactCompra['numero_factura_proveedor'];
					$txtFechaRegistroCompra = $rowFactCompra['fecha_origen'];
					$costoUnitarioAcumulado = $rowFactCompra['precio_compra'];
					$txtNombreProv = $rowFactCompra['nombre_proveedor'];
					$txtSerial = $rowFactCompra['serial_carroceria'];
					$txtModelo = $rowFactCompra['vehiculo'];
					$idActivoFijo = $rowFactCompra['id_activo_fijo'];
				} else if ($totalRowsValeEntrada > 0) {
					$rowValeEntrada = mysql_fetch_assoc($rsValeEntrada);
					
					$txtNumeroFacturaProveedor = $rowValeEntrada['numeracion_vale_entrada'];
					$txtFechaRegistroCompra = $rowValeEntrada['fecha'];
					$costoUnitarioAcumulado = $rowValeEntrada['precio_compra'];
					$txtNombreProv = $rowValeEntrada['nombre_cliente'];
					$txtSerial = $rowValeEntrada['serial_carroceria'];
					$txtModelo = $rowValeEntrada['vehiculo'];
					$idActivoFijo = $rowValeEntrada['id_activo_fijo'];
				} else {
					return $objResponse->alert("No puede registrar esta unidad como Activo Fijo debido a que no posee ningun documento que registre su entrada");
				}
				
				// BUSCA LOS DATOS DE LA CUENTA DE ACTIVOS
				$queryActivo = sprintf("SELECT * FROM ".DBASE_CONTAB.".tipoactivo tipo_activo WHERE Codigo LIKE %s;",
					valTpDato($frmAjusteInventario['lstTipoActivo'], "text"));
				$rsActivo = mysql_query($queryActivo);
				if (!$rsActivo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsActivo = mysql_num_rows($rsActivo);
				$rowActivo = mysql_fetch_assoc($rsActivo);
				
				$txtCodigoActivo = $rowActivo['Codigo'];
				$txtDescripcionActivo = $rowActivo['Descripcion']." ".$spanSerialCarroceria.": ".$txtSerial;
				
				if (!($idActivoFijo > 0)) {
					$insertSQL = sprintf("INSERT INTO ".DBASE_CONTAB.".deprecactivos (Fecha, Tipo, CompAdquisicion, Comprobante, Descripcion, serial, modelo, Proveedor, estatus)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato(date("Y-m-d"), "date"),
						valTpDato($txtCodigoActivo, "text"),
						valTpDato($costoUnitarioAcumulado, "real_inglesa"),
						valTpDato($txtNumeroFacturaProveedor, "text"),
						valTpDato($txtDescripcionActivo, "text"),
						valTpDato($txtSerial, "text"),
						valTpDato($txtModelo, "text"),
						valTpDato($txtNombreProv, "text"),
						valTpDato(1 , "int")); // 1 = Terminar de agregar los datos en Activos
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idActivoFijo = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				
					// ACTUALIZA EL IDENTIFICADOR DEL ACTIVO FIJO EN LA UNIDAD FISICA
					$updateSQL = sprintf("UPDATE an_unidad_fisica SET
						id_activo_fijo = %s
					WHERE id_unidad_fisica = %s;",
						valTpDato($idActivoFijo, "int"),
						valTpDato($idUnidadFisica, "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
			
			break;
	}
	
	if ($txtEstadoCompraAjuste == "REGISTRADO") {
		// REGISTRA EL MOVIMIENTO DEL ARTICULO
		$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idVale, "int"),
			valTpDato($idUnidadBasica, "int"),
			valTpDato($idUnidadFisica, "int"),
			valTpDato($frmAjusteInventario['lstTipoMovimiento'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
			valTpDato($idClaveMovimiento, "int"),
			valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
			valTpDato(1, "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato($frmAjusteInventario['txtSubTotal'], "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato($estadoKardex, "int"), // 0 = Entrada, 1 = Salida
			valTpDato("NOW()", "campo"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	// ACTUALIZA EL ESTADO DE VENTA DE LA UNIDAD FÍSICA
	$updateSQL = sprintf("UPDATE an_unidad_fisica SET
		estado_venta = %s
	WHERE id_unidad_fisica = %s;",
		valTpDato($txtEstadoVentaAjuste, "text"),
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTADO DE LA UNIDAD FÍSICA
	$updateSQL = sprintf("UPDATE an_unidad_fisica SET
		estatus = %s
	WHERE id_unidad_fisica = %s
		AND estado_venta IN ('DEVUELTO');",
		valTpDato(NULL, "int"), // NULL = Anulada, 1 = Activa
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");

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
			} else if ($tipoDcto == "NOTA_CREDITO") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 2 : if (function_exists("generarNotasVentasVe")) { generarNotasVentasVe($idNotaCredito,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert(utf8_encode("Vale guardado con éxito."));
		
	switch ($frmAjusteInventario['lstTipoMovimiento']) {
		case 2 : $objResponse->script(sprintf("verVentana('reportes/an_ajuste_inventario_vale_entrada_imp.php?id=%s', 960, 550);", $idVale)); break;
		case 4 : $objResponse->script(sprintf("verVentana('reportes/an_ajuste_inventario_vale_salida_imp.php?id=%s', 960, 550);", $idVale)); break;
	}
	
	$objResponse->script("byId('btnCancelarAjusteInventario').click();");
	$objResponse->script("byId('btnCancelarUnidadFisica').click();");
	
	$objResponse->loadCommands(listaUnidadFisica(
		$frmListaUnidadFisica['pageNum'],
		$frmListaUnidadFisica['campOrd'],
		$frmListaUnidadFisica['tpOrd'],
		$frmListaUnidadFisica['valBusq']));
	
	return $objResponse;
}

function guardarUnidadFisicaCargo($frmUniFisAgregado, $frmListaUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_ajuste_inventario_list","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUniFisAgregado['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$idUnidadFisica = $frmUniFisAgregado['hddIdUnidadFisicaAgregado'];
	
	mysql_query("START TRANSACTION;");
	
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
	
	foreach ($arrayObj as $indice => $valor) {
		$txtTipoDcto = $frmUniFisAgregado['txtTipoDcto'.$valor];
		$idDctoAgregado = $frmUniFisAgregado['hddIdDctoAgregado'.$valor];
		
		// BUSCA LOS DATOS DEL VALE DE SALIDA
		$query = sprintf("SELECT 
			sa_vs.id_vale_salida,
			sa_vs.fecha_vale,
			sa_vs.numero_vale,
			orden.tiempo_orden,
			orden.id_orden,
			orden.numero_orden,
			recep.numero_recepcion,
			tipo_orden.nombre_tipo_orden,
			filtro_orden.descripcion,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			uni_bas.nom_uni_bas,
			reg_placa.id_registro_placas,
			reg_placa.placa,
			reg_placa.chasis,
			sa_vs.monto_total,
			IF(vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM sa_vale_salida sa_vs
			INNER JOIN sa_orden orden ON (sa_vs.id_orden = orden.id_orden)
			INNER JOIN sa_recepcion recep ON (orden.id_recepcion = recep.id_recepcion)
			INNER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
			INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden)
			INNER JOIN cj_cc_cliente cliente ON (orden.id_cliente = cliente.id)
			INNER JOIN sa_cita cita ON (recep.id_cita = cita.id_cita)
			INNER JOIN en_registro_placas reg_placa ON (cita.id_registro_placas = reg_placa.id_registro_placas)
			INNER JOIN an_uni_bas uni_bas ON (reg_placa.id_unidad_basica = uni_bas.id_uni_bas)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (sa_vs.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE sa_vs.id_vale_salida = %s;",
			valTpDato($idDctoAgregado, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		if ($txtTipoDcto == "VS" && $row['placa'] != $rowUniFis['placa'] && $row['chasis'] != $rowUniFis['serial_carroceria']) {
			return $objResponse->alert(utf8_encode("El Vale de Salida Nro. ".$row['numero_vale']. " no puede ser asociado a esta Unidad Física."));
		}
		
		if (!($frmUniFisAgregado['hddIdUnidadFisicaAgregado'.$valor] > 0)) {
			if (in_array($txtTipoDcto,array("VS"))) {
				// INSERTA EL DETALLE DEL AGREGADO
				if ($idDctoAgregado > 0) {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_vale_salida, id_empleado_registro, monto)
					VALUE (%s, %s, %s, %s);",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($idDctoAgregado, "int"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($frmUniFisAgregado['txtTotalDcto'.$valor], "real_inglesa"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idUnidadFisicaAgregado = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
				
				// ACTUALIZA EL REGISTRO DE PLACA PARA SERVICIOS
				$updateSQL = sprintf("UPDATE en_registro_placas SET
					id_unidad_fisica = %s,
					color = (SELECT color_ext1.nom_color
							FROM an_unidad_fisica uni_fis
								INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
							WHERE uni_fis.id_unidad_fisica = %s)
				WHERE id_registro_placas = %s;",
					valTpDato($idUnidadFisica, "int"),
					valTpDato($idUnidadFisica, "int"),
					valTpDato($row['id_registro_placas'], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			} else if (in_array($txtTipoDcto,array("ND"))) {
				// INSERTA EL DETALLE DEL AGREGADO
				if ($idDctoAgregado > 0) {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_cargo_cxc, id_empleado_registro, monto)
					SELECT %s, cxc_nd.idNotaCargo, %s, cxc_nd.montoTotalNotaCargo FROM cj_cc_notadecargo cxc_nd
					WHERE cxc_nd.idNotaCargo = %s;",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($idDctoAgregado, "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idUnidadFisicaAgregado = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
			} else if (in_array($txtTipoDcto,array("NC"))) {
				// INSERTA EL DETALLE DEL AGREGADO
				if ($idDctoAgregado > 0) {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_credito_cxc, id_empleado_registro, monto)
					SELECT %s, cxc_nc.idNotaCredito, %s, cxc_nc.montoNetoNotaCredito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = %s;",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($idDctoAgregado, "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idUnidadFisicaAgregado = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	// ACTUALIZA EL COSTO DE LOS AGREGADOS
	$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
		costo_agregado = (SELECT SUM(IF((id_factura_cxp IS NOT NULL
										OR id_nota_cargo_cxp IS NOT NULL
										OR id_nota_credito_cxc IS NOT NULL
										OR id_vale_salida IS NOT NULL), 1, (-1)) * monto) FROM an_unidad_fisica_agregado uni_fis_agregado
							WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica
								AND uni_fis_agregado.estatus = 1)
	WHERE id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Cargos guardados con éxito."));
	
	$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	
	$objResponse->loadCommands(listaUnidadFisica(
		$frmListaUnidadFisica['pageNum'],
		$frmListaUnidadFisica['campOrd'],
		$frmListaUnidadFisica['tpOrd'],
		$frmListaUnidadFisica['valBusq']));
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"".utf8_encode("Prospecto")."\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"".utf8_encode("Prospecto Aprobado (Cliente Venta)")."\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"".utf8_encode("Sin Prospectación (Cliente Post-Venta)")."\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
	
	$objResponse->assign("divListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaCreditoCxC($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL, $frmUniFisAgregado){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	foreach($frmUniFisAgregado['cbx'] as $indice => $valor) {
		if ($frmUniFisAgregado['txtTipoDcto'.$valor] == "NC") {
			$arrayIdNotaCredito[] = $frmUniFisAgregado['hddIdDctoAgregado'.$valor];
		}
	}
	
	if (count($arrayIdNotaCredito) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nc.idNotaCredito NOT IN (%s))",
			valTpDato(implode(",",$arrayIdNotaCredito), "campo"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nc.montoNetoNotaCredito > 0
	AND cxc_nc.idNotaCredito NOT IN (SELECT uni_fis_agregado.id_nota_credito_cxc FROM an_unidad_fisica_agregado uni_fis_agregado
									WHERE uni_fis_agregado.id_nota_credito_cxc IS NOT NULL
										AND uni_fis_agregado.estatus = 1))");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_nota_credito IN (2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fechaNotaCredito BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nc.numeracion_nota_credito LIKE %s
		OR cxc_nc.numeroControl LIKE %s
		OR cxc_fact.numeroFactura LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cxc_nc.observacionesNotaCredito LIKE %s
		OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
			FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_fact.fechaRegistroFactura,
		cxc_fact.numeroFactura,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_nc.idNotaCredito,
		cxc_nc.numeracion_nota_credito,
		cxc_nc.numeroControl,
		cxc_nc.fechaNotaCredito,
		cxc_nc.idDepartamentoNotaCredito AS id_modulo,
		cxc_nc.observacionesNotaCredito,
		cxc_nc.subtotalNotaCredito,
		cxc_nc.subtotal_descuento,
		
		(IFNULL(cxc_nc.subtotalNotaCredito, 0)
			- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
		
		IFNULL((SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
				WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito), 0) AS total_impuestos,
		
		cxc_nc.montoNetoNotaCredito AS total,
		cxc_nc.saldoNotaCredito,
		cxc_nc.estadoNotaCredito,
		(CASE cxc_nc.estadoNotaCredito
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado No Asignado'
			WHEN 2 THEN 'Asignado Parcial'
			WHEN 3 THEN 'Asignado'
		END) AS descripcion_estado_nota_credito,
		cxc_nc.aplicaLibros,
		
		(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) AS descripcion_motivo,
		
		(CASE cxc_nc.idDepartamentoNotaCredito
			WHEN 0 THEN
				IFNULL((SELECT COUNT(cxc_nc_det.id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
						WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
			WHEN 1 THEN
				(IFNULL((SELECT COUNT(sa_fact_det_art.idFactura) FROM sa_det_fact_articulo sa_fact_det_art
						WHERE sa_fact_det_art.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_nota.idFactura) FROM sa_det_fact_notas sa_fact_det_nota
							WHERE sa_fact_det_nota.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_temp.idFactura) FROM sa_det_fact_tempario sa_fact_det_temp
							WHERE sa_fact_det_temp.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_tot.idFactura) FROM sa_det_fact_tot sa_fact_det_tot
							WHERE sa_fact_det_tot.idFactura = cxc_fact.idFactura), 0))
			WHEN 2 THEN
				(IFNULL((SELECT COUNT(cxc_nc_det_acc.id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
						WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)
					+ IFNULL((SELECT COUNT(cxc_nc_det_vehic.id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
							WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito), 0))
			WHEN 3 THEN
				IFNULL((SELECT COUNT(cxc_nc_det_adm.id_nota_credito) FROM cj_cc_nota_credito_detalle_adm cxc_nc_det_adm
						WHERE cxc_nc_det_adm.id_nota_credito = cxc_nc.idNotaCredito), 0)
		END) AS cant_items,
		
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_notacredito cxc_nc
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "6%", $pageNum, "fechaNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "6%", $pageNum, "numeracion_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "6%", $pageNum, "cxc_nc.numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "6%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "16%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "6%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "8%", $pageNum, "descripcion_estado_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Estado Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Items"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "8%", $pageNum, "saldoNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo Nota de Crédito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoCxC", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total Nota de Crédito"));
		$htmlTh .= "<td colspan=\"4\" class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		switch($row['estadoNotaCredito']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarDctoAgregado('2', 'NC','".$row['idNotaCredito']."', xajax.getFormValues('frmUniFisAgregado'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"".utf8_encode("Nota de Crédito Nro: ").utf8_encode($row['numeracion_nota_credito']).". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fechaNotaCredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_nota_credito']."</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"center\">".((strtotime($row['fechaRegistroFactura'])) ? date(spanDateFormat, strtotime($row['fechaRegistroFactura'])) : "")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<tr><td><span class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacionesNotaCredito']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionesNotaCredito'])."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".(($row['condicion_pago'] == 0) ? "class=\"divMsjAlerta\"" : "class=\"divMsjInfo\"").">";
				$htmlTb .= utf8_encode(($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_nota_credito']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoNotaCredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			switch ($row['id_modulo']) {
				case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $row['idNotaCredito']); break;
				case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $row['idNotaCredito']); break;
				case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $row['idNotaCredito']); break;
				case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $row['idNotaCredito']); break;
				case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $row['idNotaCredito']); break;
				default : $aVerDctoAux = "";
			}
				$htmlTb .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[12] += $row['cant_items'];
		$arrayTotal[13] += $row['saldoNotaCredito'];
		$arrayTotal[14] += $row['total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">".utf8_encode("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[12] += $row['cant_items'];
				$arrayTotalFinal[13] += $row['saldoNotaCredito'];
				$arrayTotalFinal[14] += $row['total'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">".utf8_encode("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"4\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"19\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCreditoCxC(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCreditoCxC(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCreditoCxC(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCreditoCxC(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCreditoCxC(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"19\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNotaCreditoCxC","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaCreditoValeEnt($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (2)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.subtotalNotaCredito > 0");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((cxc_nc.idDepartamentoNotaCredito IN (2)
		AND (SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios
			WHERE id_nota_credito = cxc_nc.idNotaCredito) = 0
		AND (SELECT COUNT(id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo
			WHERE id_nota_credito = cxc_nc.idNotaCredito) = 0))");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_vale_entrada) FROM an_vale_entrada
	WHERE id_documento = idNotaCredito AND tipo_vale_entrada = 3) = 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nc.numeracion_nota_credito LIKE %s
		OR cxc_nc.numeroControl LIKE %s
		OR cxc_fact.numeroFactura LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cxc_nc.observacionesNotaCredito LIKE %s
		OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
			FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_fact.fechaRegistroFactura,
		cxc_fact.numeroFactura,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_nc.idNotaCredito,
		cxc_nc.numeracion_nota_credito,
		cxc_nc.numeroControl,
		cxc_nc.fechaNotaCredito,
		cxc_nc.idDepartamentoNotaCredito AS id_modulo,
		cxc_nc.observacionesNotaCredito,
		cxc_nc.subtotalNotaCredito,
		cxc_nc.subtotal_descuento,
		
		(IFNULL(cxc_nc.subtotalNotaCredito, 0)
			- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
		
		IFNULL((SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
				WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito), 0) AS total_impuestos,
		
		cxc_nc.montoNetoNotaCredito AS total,
		cxc_nc.saldoNotaCredito,
		cxc_nc.estadoNotaCredito,
		(CASE cxc_nc.estadoNotaCredito
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado No Asignado'
			WHEN 2 THEN 'Asignado Parcial'
			WHEN 3 THEN 'Asignado'
		END) AS descripcion_estado_nota_credito,
		cxc_nc.aplicaLibros,
		
		(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) AS descripcion_motivo,
		
		(CASE cxc_nc.idDepartamentoNotaCredito
			WHEN 0 THEN
				IFNULL((SELECT COUNT(cxc_nc_det.id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
						WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
			WHEN 1 THEN
				(IFNULL((SELECT COUNT(sa_fact_det_art.idFactura) FROM sa_det_fact_articulo sa_fact_det_art
						WHERE sa_fact_det_art.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_nota.idFactura) FROM sa_det_fact_notas sa_fact_det_nota
							WHERE sa_fact_det_nota.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_temp.idFactura) FROM sa_det_fact_tempario sa_fact_det_temp
							WHERE sa_fact_det_temp.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_tot.idFactura) FROM sa_det_fact_tot sa_fact_det_tot
							WHERE sa_fact_det_tot.idFactura = cxc_fact.idFactura), 0))
			WHEN 2 THEN
				(IFNULL((SELECT COUNT(cxc_nc_det_acc.id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
						WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)
					+ IFNULL((SELECT COUNT(cxc_nc_det_vehic.id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
							WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito), 0))
			WHEN 3 THEN
				IFNULL((SELECT COUNT(cxc_nc_det_adm.id_nota_credito) FROM cj_cc_nota_credito_detalle_adm cxc_nc_det_adm
						WHERE cxc_nc_det_adm.id_nota_credito = cxc_nc.idNotaCredito), 0)
		END) AS cant_items,
		
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_notacredito cxc_nc
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoValeEnt", "10%", $pageNum, "fechaNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoValeEnt", "10%", $pageNum, "numeracion_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Nota Créd."));
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoValeEnt", "58%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoValeEnt", "6%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaNotaCreditoValeEnt", "16%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		switch($row['estadoNotaCredito']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button type=\"button\" onclick=\"xajax_asignarDcto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>",
				$row['idNotaCredito']);//
			$htmlTb .= "<td>".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"".utf8_encode("Nota de Crédito Nro: ").utf8_encode($row['numeracion_nota_credito']).". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fechaNotaCredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_nota_credito']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<tr><td><span class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= (strlen($row['observacionesNotaCredito']) > 0) ? "<tr><td class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionesNotaCredito'])."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".(($row['condicion_pago'] == 0) ? "class=\"divMsjAlerta\"" : "class=\"divMsjInfo\"").">";
				$htmlTb .= utf8_encode(($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCreditoValeEnt(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCreditoValeEnt(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCreditoValeEnt(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCreditoValeEnt(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCreditoValeEnt(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaNotaDebitoCxC($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL, $frmUniFisAgregado){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	foreach($frmUniFisAgregado['cbx'] as $indice => $valor) {
		if ($frmUniFisAgregado['txtTipoDcto'.$valor] == "ND") {
			$arrayIdNotaDebito[] = $frmUniFisAgregado['hddIdDctoAgregado'.$valor];
		}
	}
	
	if (count($arrayIdNotaDebito) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nd.idNotaCargo NOT IN (%s))",
			valTpDato(implode(",",$arrayIdNotaDebito), "campo"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxc_nd.montoTotalNotaCargo > 0
	AND cxc_nd.idNotaCargo NOT IN (SELECT uni_fis_agregado.id_nota_cargo_cxc FROM an_unidad_fisica_agregado uni_fis_agregado
									WHERE uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL
										AND uni_fis_agregado.estatus = 1))");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nd.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nd.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.fechaRegistroNotaCargo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nd.numeroNotaCargo LIKE %s
		OR cxc_nd.numeroControlNotaCargo LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cxc_nd.observacionNotaCargo LIKE %s
		OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
			FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_nd.idNotaCargo,
		cxc_nd.numeroNotaCargo,
		cxc_nd.numeroControlNotaCargo,
		cxc_nd.fechaRegistroNotaCargo,
		cxc_nd.fechaVencimientoNotaCargo,
		cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_nd.estadoNotaCargo,
		(CASE cxc_nd.estadoNotaCargo
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_nota_cargo,
		cxc_nd.montoTotalNotaCargo,
		cxc_nd.saldoNotaCargo,
		cxc_nd.observacionNotaCargo,
		cxc_nd.aplicaLibros,
		
		(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
		
		(IFNULL(cxc_nd.subtotalNotaCargo, 0)
			- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
		
		(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
			+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_iva,
		
		(IFNULL(cxc_nd.subtotalNotaCargo, 0)
			- IFNULL(cxc_nd.descuentoNotaCargo, 0)
			+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
				+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0))) AS total,
		
		uni_fis.id_unidad_fisica,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nd.id_unidad_fisica_bono = uni_fis.id_unidad_fisica)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "6%", $pageNum, "fechaRegistroNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "6%", $pageNum, "fechaVencimientoNotaCargo",$campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Venc. Nota de Débito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "6%", $pageNum, "LPAD(numeroNotaCargo, 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Nota de Débito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "6%", $pageNum, "LPAD(numeroControlNotaCargo, 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "32%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "8%", $pageNum, "descripcion_estado_nota_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Estado Nota de Débito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "8%", $pageNum, "saldoNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo Nota de Débito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaDebitoCxC", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total Nota de Débito"));
		$htmlTh .= "<td colspan=\"4\" class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		switch($row['estadoNotaCargo']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarDctoAgregado('4', 'ND','".$row['idNotaCargo']."', xajax.getFormValues('frmUniFisAgregado'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fechaRegistroNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fechaVencimientoNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroNotaCargo']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroControlNotaCargo']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td nowrap=\"nowrap\" width=\"100%\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<tr><td><span class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= (strlen($row['serial_carroceria']) > 0) ? "<tr><td nowrap=\"nowrap\"><span class=\"textoNegrita_9px\">".utf8_encode($row['serial_carroceria'])."</span></td></tr>" : "";
				$htmlTb .= (strlen($row['observacionNotaCargo']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionNotaCargo'])."</span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_nota_cargo']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
					$row['idNotaCargo']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal['cant_items'] += $row['cant_items'];
		$arrayTotal['saldoNotaCargo'] += $row['saldoNotaCargo'];
		$arrayTotal['total'] += $row['total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">".utf8_encode("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['saldoNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal['cant_items'] += $row['cant_items'];
				$arrayTotalFinal['saldoNotaCargo'] += $row['saldoNotaCargo'];
				$arrayTotalFinal['total'] += $row['total'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">".utf8_encode("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['cant_items'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['saldoNotaCargo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['total'], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"4\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"19\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebitoCxC(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebitoCxC(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaDebitoCxC(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebitoCxC(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebitoCxC(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"19\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNotaDebitoCxC","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('DISPONIBLE', 'VENDIDO', 'PRESTADO', 'ACTIVO FIJO', 'INTERCAMBIO', 'DEVUELTO', 'ERROR EN TRASPASO')");
		
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
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "uni_fis.id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id Unidad Física"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Color"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "kilometraje", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanKilometraje));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Ingreso"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Estado Venta"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Almacén"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Asignación"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Fact. Compra"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "precio_compra", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Costo"));
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"30\">".utf8_encode($row['vehiculo'].((strlen($row['mostrar_catalogo']) > 0) ? " <b>[".$row['mostrar_catalogo']."]</b>" : ""))."</td>";
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
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= (($rowUnidadFisica['fecha_origen'] != "") ? "<div>".date(spanDateFormat, strtotime($rowUnidadFisica['fecha_origen']))."</div>" : "");
					$htmlTb .= (($rowUnidadFisica['dias_inventario'] > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($rowUnidadFisica['dias_inventario']." días")."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['estado_venta'])."</div>";
					$htmlTb .= (($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<div class=\"textoNegrita_9px\">(".utf8_encode($rowUnidadFisica['estado_compra']).")</div>" : "");
					$htmlTb .= (($rowUnidadFisica['id_activo_fijo'] > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode("Código: ".$rowUnidadFisica['id_activo_fijo'])."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
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
				//if ($rowUnidadFisica['id_nota_cargo_cxp']) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDetalle%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUniFisAgregado', '%s');\"><img class=\"puntero\" src=\"../img/iconos/application_view_columns.png\" title=\"Ver Agregados\"/></a>",
						$contFila,
						$rowUnidadFisica['id_unidad_fisica']);
				//}
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
				//if (!in_array($rowUnidadFisica['estado_venta'],array('VENDIDO'))) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadFisica', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"%s\"/></a>",
						$contFila,
						$rowUnidadFisica['id_unidad_fisica'],
						utf8_encode("Editar Unidad Física"));
				//}
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotal['cant_unidades'] += 1;
			$arrayTotal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Subtotal:<br>".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_unidades'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['precio_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalFinal['cant_unidades'] += $arrayTotal['cant_unidades'];
		$arrayTotalFinal['precio_compra'] += $arrayTotal['precio_compra'];
	}
	if ($pageNum == $totalPages) {
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
			$arrayTotalFinal = array();
			while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
				$arrayTotalFinal['cant_unidades'] += 1;
				$arrayTotalFinal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
			}
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total de Totales:"."</td>";
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

function listaValeSalida($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL, $frmUniFisAgregado) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	foreach($frmUniFisAgregado['cbx'] as $indice => $valor) {
		if ($frmUniFisAgregado['txtTipoDcto'.$valor] == "VS") {
			$arrayIdValeSalida[] = $frmUniFisAgregado['hddIdDctoAgregado'.$valor];
		}
	}
	
	// 12 = VEHICULOS USADOS
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(filtro_orden.id_filtro_orden IN (4,12,13,15)
	AND reg_placa.chasis LIKE (SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
								WHERE uni_fis.id_unidad_fisica = %s))",
		valTpDato($frmUniFisAgregado['hddIdUnidadFisicaAgregado'], "int"));
	
	if (count($arrayIdValeSalida) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(sa_vs.id_vale_salida NOT IN (%s))",
			valTpDato(implode(",",$arrayIdValeSalida), "campo"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(sa_vs.monto_total > 0
	AND sa_vs.id_vale_salida NOT IN (SELECT uni_fis_agregado.id_vale_salida FROM an_unidad_fisica_agregado uni_fis_agregado
									WHERE uni_fis_agregado.id_vale_salida IS NOT NULL
										AND uni_fis_agregado.estatus = 1))");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(sa_vs.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
			WHERE suc.id_empresa_padre = sa_vs.id_empresa)
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = sa_vs.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(sa_vs.fecha_vale) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("1 IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR sa_vs.numero_vale LIKE %s
		OR orden.numero_orden LIKE %s
		OR recep.numero_recepcion LIKE %s
		OR tipo_orden.nombre_tipo_orden LIKE %s
		OR filtro_orden.descripcion LIKE %s
		OR uni_bas.nom_uni_bas LIKE %s
		OR reg_placa.placa LIKE %s
		OR reg_placa.chasis LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		sa_vs.id_vale_salida,
		sa_vs.fecha_vale,
		sa_vs.numero_vale,
		orden.tiempo_orden,
		orden.id_orden,
		orden.numero_orden,
		recep.numero_recepcion,
		tipo_orden.nombre_tipo_orden,
		filtro_orden.descripcion,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		uni_bas.nom_uni_bas,
		reg_placa.id_registro_placas,
		reg_placa.placa,
		reg_placa.chasis,
		sa_vs.monto_total,
		IF(vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM sa_vale_salida sa_vs
		INNER JOIN sa_orden orden ON (sa_vs.id_orden = orden.id_orden)
		INNER JOIN sa_recepcion recep ON (orden.id_recepcion = recep.id_recepcion)
		INNER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
		INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden)
		INNER JOIN cj_cc_cliente cliente ON (orden.id_cliente = cliente.id)
		INNER JOIN sa_cita cita ON (recep.id_cita = cita.id_cita)
		INNER JOIN en_registro_placas reg_placa ON (cita.id_registro_placas = reg_placa.id_registro_placas)
		INNER JOIN an_uni_bas uni_bas ON (reg_placa.id_unidad_basica = uni_bas.id_uni_bas)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (sa_vs.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "18%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "8%", $pageNum, "fecha_vale", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Registro"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "6%", $pageNum, "numero_vale", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Vale de Salida"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "8%", $pageNum, "tiempo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Orden"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "6%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Orden"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "6%", $pageNum, "numero_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Recepción"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "10%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Orden"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "8%", $pageNum, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "2%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Catálogo"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "8%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "8%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Chasis"));
		$htmlTh .= ordenarCampo("xajax_listaValeSalida", "8%", $pageNum, "monto_total", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total"));
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarDctoAgregado('4', 'VS','".$row['id_vale_salida']."', xajax.getFormValues('frmUniFisAgregado'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_vale']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_vale']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['tiempo_orden']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_recepcion']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nom_uni_bas']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$row['placa']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['chasis']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../servicios/sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|0', 960, 550);\"><img src=\"../img/iconos/page_red.png\" title=\"Orden PDF\"/></a>",
					$row['id_orden']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../servicios/reportes/sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|3', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Vale de Salida PDF\"/></a>",
					$row['id_vale_salida']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaValeSalida(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaValeSalida(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaValeSalida(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaValeSalida(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaValeSalida(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"16\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaValeSalida","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarDcto");
$xajax->register(XAJAX_FUNCTION,"asignarDctoAgregado");
$xajax->register(XAJAX_FUNCTION,"asignarTipoVale");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarDocumento");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCreditoValeEnt");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"calcularAgregado");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacenBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstColor");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicion");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCompraBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVentaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstPaisOrigen");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoActivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"cargaLstUso");
$xajax->register(XAJAX_FUNCTION,"eliminarAgregado");
$xajax->register(XAJAX_FUNCTION,"formAjusteInventario");
$xajax->register(XAJAX_FUNCTION,"formListaDocumento");
$xajax->register(XAJAX_FUNCTION,"formUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"formUnidadFisicaAgregado");
$xajax->register(XAJAX_FUNCTION,"guardarAjusteInventario");
$xajax->register(XAJAX_FUNCTION,"guardarUnidadFisicaCargo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaNotaCreditoCxC");
$xajax->register(XAJAX_FUNCTION,"listaNotaCreditoValeEnt");
$xajax->register(XAJAX_FUNCTION,"listaNotaDebitoCxC");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"listaValeSalida");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice => $valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return NULL;
}

function insertarItemDctoAgregado($contFila, $hddIdUnidadFisicaAgregado = "", $idTipoMovimientoAgregado = "", $tipoDctoAgregado = "", $idDocumento = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($hddIdUnidadFisicaAgregado > 0) {
		// BUSCA EL DOCUMENTO AGREGADO
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
				WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			uni_fis_agregado.id_factura_cxp
				WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		uni_fis_agregado.id_nota_cargo_cxp
				WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	uni_fis_agregado.id_nota_credito_cxp
				WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			uni_fis_agregado.id_factura_cxc
				WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		uni_fis_agregado.id_nota_cargo_cxc
				WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	uni_fis_agregado.id_nota_credito_cxc
				WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			uni_fis_agregado.id_vale_salida
				WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		uni_fis_agregado.id_vale_entrada
			END) AS id_documento
		FROM an_unidad_fisica_agregado uni_fis_agregado
		WHERE id_unidad_fisica_agregado = %s;",
			valTpDato($hddIdUnidadFisicaAgregado, "int"));
		$rsUniFisAgregado = mysql_query($queryUniFisAgregado);
		if (!$rsUniFisAgregado) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsUniFisAgregado = mysql_num_rows($rsUniFisAgregado);
		$rowUniFisAgregado = mysql_fetch_array($rsUniFisAgregado);
	}
	
	$idTipoMovimientoAgregado = ($totalRowsUniFisAgregado > 0) ? $rowUniFisAgregado['id_tipo_movimiento'] : $idTipoMovimientoAgregado;
	$tipoDctoAgregado = ($totalRowsUniFisAgregado > 0) ? $rowUniFisAgregado['tipoDocumento'] : $tipoDctoAgregado;
	$idDocumento = ($totalRowsUniFisAgregado > 0) ? $rowUniFisAgregado['id_documento'] : $idDocumento;
	
	switch ($tipoDctoAgregado) {
		case "VS" :
			// BUSCA LOS DATOS DEL VALE DE SALIDA
			$query = sprintf("SELECT 
				sa_vs.id_vale_salida,
				sa_vs.fecha_vale,
				sa_vs.numero_vale,
				orden.tiempo_orden,
				orden.id_orden,
				orden.numero_orden,
				recep.numero_recepcion,
				tipo_orden.nombre_tipo_orden,
				filtro_orden.descripcion,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				uni_bas.nom_uni_bas,
				reg_placa.id_registro_placas,
				reg_placa.placa,
				reg_placa.chasis,
				sa_vs.estado_vale,
				(CASE sa_vs.estado_vale
					WHEN 0 THEN 'Generado'
					WHEN 1 THEN 'Devuelto'
				END) AS descripcion_estado_vale,
				sa_vs.monto_total,
				IF(vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM sa_vale_salida sa_vs
				INNER JOIN sa_orden orden ON (sa_vs.id_orden = orden.id_orden)
				INNER JOIN sa_recepcion recep ON (orden.id_recepcion = recep.id_recepcion)
				INNER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
				INNER JOIN sa_filtro_orden filtro_orden ON (tipo_orden.id_filtro_orden = filtro_orden.id_filtro_orden)
				INNER JOIN cj_cc_cliente cliente ON (orden.id_cliente = cliente.id)
				INNER JOIN sa_cita cita ON (recep.id_cita = cita.id_cita)
				INNER JOIN en_registro_placas reg_placa ON (cita.id_registro_placas = reg_placa.id_registro_placas)
				INNER JOIN an_uni_bas uni_bas ON (reg_placa.id_unidad_basica = uni_bas.id_uni_bas)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (sa_vs.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE sa_vs.id_vale_salida = %s;",
				valTpDato($idDocumento, "int"));
			$rs = mysql_query($query);
			if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$row = mysql_fetch_array($rs);
			
			$fechaDcto = $row['fecha_vale'];
			$nroDcto = $row['numero_vale'];
			$idModulo = 1;
			$nombreCliente = $row['nombre_cliente'];
			$serialChasis = $row['chasis'];
			$numeroPlaca = $row['placa'];
			$estadoDcto = $row['estado_vale'];
			$descripcionEstadoDcto = $row['descripcion_estado_vale'];
			$observacionDcto = "";
			$saldoDcto = 0;
			$txtTotalDcto = $row['monto_total'];
			break;
		case "ND" :
			if ($idTipoMovimientoAgregado == 4) { // 2 = SALIDA
				// BUSCA LOS DATOS DE LA NOTA DE DEBITO
				$query = sprintf("SELECT
					cxc_nd.idNotaCargo,
					cxc_nd.numeroNotaCargo,
					cxc_nd.numeroControlNotaCargo,
					cxc_nd.fechaRegistroNotaCargo,
					cxc_nd.fechaVencimientoNotaCargo,
					cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
					cliente.id AS id_cliente,
					CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
					CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
					cxc_nd.estadoNotaCargo,
					(CASE cxc_nd.estadoNotaCargo
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado'
						WHEN 2 THEN 'Cancelado Parcial'
					END) AS descripcion_estado_nota_cargo,
					cxc_nd.montoTotalNotaCargo,
					cxc_nd.saldoNotaCargo,
					cxc_nd.observacionNotaCargo,
					cxc_nd.aplicaLibros,
					
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
					(IFNULL(cxc_nd.subtotalNotaCargo, 0)
						- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
					
					(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
						+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_iva,
					
					(IFNULL(cxc_nd.subtotalNotaCargo, 0)
						- IFNULL(cxc_nd.descuentoNotaCargo, 0)
						+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
							+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0))) AS total,
					
					uni_fis.id_unidad_fisica,
					uni_fis.serial_carroceria,
					uni_fis.serial_motor,
					uni_fis.serial_chasis,
					uni_fis.placa,
					
					IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
				FROM cj_cc_notadecargo cxc_nd
					INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
					LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nd.id_unidad_fisica_bono = uni_fis.id_unidad_fisica)
					INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
				WHERE cxc_nd.idNotaCargo = %s;",
					valTpDato($idDocumento, "int"));
				$rs = mysql_query($query);
				if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
				$row = mysql_fetch_array($rs);
				
				$fechaDcto = $row['fechaRegistroNotaCargo'];
				$nroDcto = $row['numeroNotaCargo'];
				$idModulo = $row['id_modulo'];
				$nombreCliente = $row['nombre_cliente'];
				$idMotivo = $row['id_motivo'];
				$descripcionMotivo = $row['descripcion_motivo'];
				$estadoDcto = $row['estadoNotaCargo'];
				$descripcionEstadoDcto = $row['descripcion_estado_nota_cargo'];
				$observacionDcto = $row['observacionNotaCargo'];
				$saldoDcto = $row['saldoNotaCargo'];
				$txtTotalDcto = $row['total'];
			}
			break;
		case "NC" :
			if ($idTipoMovimientoAgregado == 2) { // 2 = ENTRADA
				// BUSCA LOS DATOS DE LA NOTA DE CREDITO
				$query = sprintf("SELECT
					cxc_fact.fechaRegistroFactura,
					cxc_fact.numeroFactura,
					cliente.id AS id_cliente,
					CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
					CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
					cxc_nc.idNotaCredito,
					cxc_nc.numeracion_nota_credito,
					cxc_nc.numeroControl,
					cxc_nc.fechaNotaCredito,
					cxc_nc.idDepartamentoNotaCredito AS id_modulo,
					cxc_nc.observacionesNotaCredito,
					cxc_nc.subtotalNotaCredito,
					cxc_nc.subtotal_descuento,
					
					(IFNULL(cxc_nc.subtotalNotaCredito, 0)
						- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
					
					IFNULL((SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
							WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito), 0) AS total_impuestos,
					
					cxc_nc.montoNetoNotaCredito AS total,
					cxc_nc.saldoNotaCredito,
					cxc_nc.estadoNotaCredito,
					(CASE cxc_nc.estadoNotaCredito
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado No Asignado'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
					END) AS descripcion_estado_nota_credito,
					cxc_nc.aplicaLibros,
					
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) AS descripcion_motivo,
					
					(CASE cxc_nc.idDepartamentoNotaCredito
						WHEN 0 THEN
							IFNULL((SELECT COUNT(cxc_nc_det.id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
									WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
						WHEN 1 THEN
							(IFNULL((SELECT COUNT(sa_fact_det_art.idFactura) FROM sa_det_fact_articulo sa_fact_det_art
									WHERE sa_fact_det_art.idFactura = cxc_fact.idFactura), 0)
								+ IFNULL((SELECT COUNT(sa_fact_det_nota.idFactura) FROM sa_det_fact_notas sa_fact_det_nota
										WHERE sa_fact_det_nota.idFactura = cxc_fact.idFactura), 0)
								+ IFNULL((SELECT COUNT(sa_fact_det_temp.idFactura) FROM sa_det_fact_tempario sa_fact_det_temp
										WHERE sa_fact_det_temp.idFactura = cxc_fact.idFactura), 0)
								+ IFNULL((SELECT COUNT(sa_fact_det_tot.idFactura) FROM sa_det_fact_tot sa_fact_det_tot
										WHERE sa_fact_det_tot.idFactura = cxc_fact.idFactura), 0))
						WHEN 2 THEN
							(IFNULL((SELECT COUNT(cxc_nc_det_acc.id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
									WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)
								+ IFNULL((SELECT COUNT(cxc_nc_det_vehic.id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
										WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito), 0))
						WHEN 3 THEN
							IFNULL((SELECT COUNT(cxc_nc_det_adm.id_nota_credito) FROM cj_cc_nota_credito_detalle_adm cxc_nc_det_adm
									WHERE cxc_nc_det_adm.id_nota_credito = cxc_nc.idNotaCredito), 0)
					END) AS cant_items,
					
					vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
					IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
				FROM cj_cc_notacredito cxc_nc
					LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
					INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
					LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
					INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
				WHERE cxc_nc.idNotaCredito = %s;",
					valTpDato($idDocumento, "int"));
				$rs = mysql_query($query);
				if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
				$row = mysql_fetch_array($rs);
				
				$fechaDcto = $row['fechaNotaCredito'];
				$nroDcto = $row['numeracion_nota_credito'];
				$idModulo = $row['id_modulo'];
				$nombreCliente = $row['nombre_cliente'];
				$idMotivo = $row['id_motivo'];
				$descripcionMotivo = $row['descripcion_motivo'];
				$estadoDcto = $row['estadoNotaCredito'];
				$descripcionEstadoDcto = $row['descripcion_estado_nota_credito'];
				$observacionDcto = $row['observacionesNotaCredito'];
				$saldoDcto = $row['saldoNotaCredito'];
				$txtTotalDcto = $row['total'];
			}
			break;
	}
	
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
		$empleadoAnuladoUniFisAgregado = (strlen($rowUniFisAgregado['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".utf8_encode($rowUniFisAgregado['nombre_empleado_anulado'])."<br>(".date(spanDateFormat, strtotime($rowUniFisAgregado['fecha_anulado'])).")</span></div>" : "";
	}
	
	switch ($idTipoMovimientoAgregado) {
		case 1 : // 1 = COMPRA
			switch ($idModulo) {
				case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=%s", $idDocumento); break;
				case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=%s", $idDocumento); break;
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
					switch ($idModulo) {
						case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
						case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
						case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
						case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
						case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
						default : $aVerDctoAux = "";
					}
					$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
					break;
				default : $aVerDcto = "";
			}
			break;
		case 3 : // 3 = VENTA
			$aVerDcto = ""; break;
		case 4 : // 4 = SALIDA
			switch ($tipoDctoAgregado) {
				case "ND" :
					$aVerDcto = sprintf("<a href=\"javascript:verVentana(\'../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
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
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItm:%s\" class=\"%s\">".
			"<td title=\"trItm:%s\">%s".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td align=\"center\">%s".
				"<input type=\"text\" id=\"txtTipoDcto%s\" name=\"txtTipoDcto%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td align=\"center\">%s</td>".
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
				"<div>%s</div>".
				"<table width=\"%s\">".
				"<tr class=\"textoNegrita_10px\">".
					"<td width=\"%s\">%s</td>".
					"<td width=\"%s\">%s</td>".
				"</tr>".
				"</table>".
				"%s".
				"%s".
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
			date(spanDateFormat, strtotime($fechaDcto)),
			$classUniFisAgregado,
				"100%",
					$aVerDcto,
					$imgDctoModulo,
					"100%", $nroDcto,
				$estatusUniFisAgregado,
				$empleadoAnuladoUniFisAgregado,
			utf8_encode($nombreCliente),
			"100%",
				"50%", ((strlen($serialChasis) > 0) ? $serialChasis : ""),
				"50%", ((strlen($numeroPlaca) > 0) ? $numeroPlaca : ""),
				(($idMotivo > 0) ? "<div class=\"textoNegrita_9px\">".$idMotivo.".- ".utf8_encode($descripcionMotivo)."</div>" : ""),
				((strlen($observacionDcto) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$observacionDcto))))."</div>" : ""),
			$class, $descripcionEstadoDcto,
			number_format($saldoDcto, 2, ".", ","),
			$contFila, $contFila, number_format(((
					(in_array($idTipoMovimientoAgregado,array(1)) && in_array($tipoDctoAgregado,array("FA")))
					|| (in_array($idTipoMovimientoAgregado,array(2)) && in_array($tipoDctoAgregado,array("ND")))
					|| (in_array($idTipoMovimientoAgregado,array(2)) && in_array($tipoDctoAgregado,array("NC")))
					|| (in_array($idTipoMovimientoAgregado,array(4)) && in_array($tipoDctoAgregado,array("VS")))
				) ? 1 : (-1)) * $txtTotalDcto, 2, ".", ","),
				$contFila, $contFila, $hddIdUnidadFisicaAgregado,
				$contFila, $contFila, $idDocumento);
	
	return array(true, $htmlItmPie, $contFila);
}
?>