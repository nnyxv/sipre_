<?php


function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($estatusCliente != "-1" && $estatusCliente != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.status = %s",
			valTpDato($estatusCliente, "text"));
	}
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.reputacionCliente,
		cliente.status,
		cliente.tipo_cuenta_cliente
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
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
		$objResponse->assign("txtCreditoCliente","value",number_format($rowClienteCredito['creditodisponible'], 2, ".", ","));
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		$objResponse->assign("hddTipoPagoCliente","value",0);
		
		if (function_exists("cargaLstClaveMovimiento")) { 
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "3", "", "1", $idClaveMovimiento,
			((in_array($idModulo,array(0))) ? "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""
				: "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\"")));
			
			if (in_array($idModulo,array(0))) {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = '';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
					xajax_cargaLstClaveMovimiento('lstClaveMovimiento','".$idModulo."','3','','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
				}");
			} else {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = 'none';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
				}");
			}
		}
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		$objResponse->assign("hddTipoPagoCliente","value",1);
		
		if (function_exists("cargaLstClaveMovimiento")) { 
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "3", "", "1", $idClaveMovimiento,
			((in_array($idModulo,array(0))) ? "onchange=\"byId('aDesbloquearClaveMovimiento').click(); selectedOption(this.id, '".$idClaveMovimiento."');\""
				: "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\"")));
			
			if (in_array($idModulo,array(0))) {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = '';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
					xajax_cargaLstClaveMovimiento('lstClaveMovimiento','".$idModulo."','3','','1','".$idClaveMovimiento."','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'".$idClaveMovimiento."\');\"');
				}");
			} else {
				$objResponse->script("
				byId('aDesbloquearClaveMovimiento').style.display = 'none';
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,3);
				}");
			}
		}
	}
	
	if ($rowCliente['id_cliente'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$tipoCuentaCliente = $rowCliente['tipo_cuenta_cliente'];
	if ($tipoCuentaCliente == 1) {
		$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">PROSPECTO [".$rowCliente['reputacionCliente']."]</div>";
		$backgroundReputacion = '#FFFFCC'; // AMARILLO
	} else {
		switch ($rowCliente['id_reputacion_cliente']) {
			case 1 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#FFEEEE'; // ROJO
				break;
			case 2 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#DDEEFF'; // AZUL
				break;
			case 3 :
				$tdMsjCliente .= "<div class=\"punteadoCelda\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
				$backgroundReputacion = '#E6FFE6'; // VERDE
				break;
		}
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id_cliente']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",$tdMsjCliente);
	$objResponse->assign("tblIdCliente","style.background",$backgroundReputacion);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
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

function buscarEstadoCuenta($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdCliente'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['radioOpcion'],
		(is_array($frmBuscar['lstTipoDetalle']) ? implode(",",$frmBuscar['lstTipoDetalle']) : $frmBuscar['lstTipoDetalle']),
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		(is_array($frmBuscar['lstEstadoFactura']) ? implode(",",$frmBuscar['lstEstadoFactura']) : $frmBuscar['lstEstadoFactura']),
		(is_array($frmBuscar['lstConceptoPago']) ? implode(",",$frmBuscar['lstConceptoPago']) : $frmBuscar['lstConceptoPago']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		$frmBuscar['txtCriterio']);
	
	switch ($frmBuscar['radioOpcion']) {
		case 1 : $objResponse->loadCommands(listaECIndividual(0, "CONCAT(q.fechaRegistroFactura, q.idEstadoCuenta)", "DESC", $valBusq)); break;
		case 2 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
		case 3 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
		case 4 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
	}
	
	return $objResponse;
}

function cargaCbxModulos($idModulo = ""){
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	$query = sprintf("SELECT * FROM pg_modulos %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><div class=\"checkbox-label\"><label><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"".$row['id_modulo']."\"/> ".$row['descripcionModulo']."</label></div></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function cargaCbxTipoDocumento($idTipoDocumento = ""){
	$objResponse = new xajaxResponse();
	
	if ($idTipoDocumento != "-1" && $idTipoDocumento != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idTipoDeDocumento IN (%s)",
			valTpDato($idTipoDocumento, "campo"));
	}
	
	$query = sprintf("SELECT * FROM tipodedocumentos %s ORDER BY idTipoDeDocumento", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><div class=\"checkbox-label\"><label><input type=\"checkbox\" id=\"cbxDcto\" name=\"cbxDcto[]\" checked=\"checked\" value=\"".utf8_encode($row['abreviatura_tipo_documento'])."\"/> ".utf8_encode($row['descripcionTipoDeDocumento'])."</label></div></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdTipoDocumento","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstConceptoPago($nombreObjeto, $selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_formapago = 11 AND estatus = 1");
	
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(($idCajaPpal == 1) ? "id_concepto NOT IN (2)" : "id_concepto NOT IN (1,2,6,7,8,9)");*/
	
	$query = sprintf("SELECT * FROM cj_conceptos_formapago %s ORDER BY descripcion ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['id_concepto'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["id_concepto"]."\">".utf8_encode($row["descripcion"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCondicionBuscar($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple": "")." id=\"lstCondicionBuscar\" name=\"lstCondicionBuscar\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_condicion_unidad'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_condicion_unidad']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicionBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoFactura($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$array = array("0" => "No Cancelado", "1" => "Cancelado (No Asignado)", "2" => "Asignado Parcial", "3" => "Asignado", "4" => "No Cancelado (Asignado)");
	$totalRows = count($array);
	
	$html .= "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstEstadoFactura\" name=\"lstEstadoFactura\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoFactura","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstMotivo($nombreObjeto, $moduloMotivo, $transaccionMotivo, $selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	if ($moduloMotivo != "-1" && $moduloMotivo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("modulo LIKE %s",
			valTpDato($moduloMotivo, "text"));
	}
	
	if ($transaccionMotivo != "-1" && $transaccionMotivo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ingreso_egreso LIKE %s",
			valTpDato($transaccionMotivo, "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		motivo.modulo,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion
	FROM pg_motivo motivo %s
	ORDER BY id_motivo DESC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select multiple id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$html .= "<optgroup label=\"".$row['descripcion_modulo_transaccion']."\">";
		
		$sqlBusq3 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("modulo IN (%s)",
			valTpDato("'".str_replace(",","','",$row['modulo'])."'", "defined", "'".str_replace(",","','",$row['modulo'])."'"));
		
		$queryMotivo = sprintf("SELECT motivo.*,
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
		FROM pg_motivo motivo %s %s ORDER BY id_motivo DESC", $sqlBusq, $sqlBusq3);
		$rsMotivo = mysql_query($queryMotivo);
		if (!$rsMotivo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$queryMotivo);
		$totalRowsMotivo = mysql_num_rows($rsMotivo);
		while ($rowMotivo = mysql_fetch_assoc($rsMotivo)) {
			$selected = ($selId == $rowMotivo['id_motivo'] || $totalRowsMotivo == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowMotivo['id_motivo']."\">".utf8_encode($rowMotivo['id_motivo'].".- ".$rowMotivo['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaECGeneral($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = q.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM an_pagos cxc_pago
				WHERE cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.estatus IN (1)) AS q2
			WHERE q2.id_factura = cxc_fact.idFactura) BETWEEN %s AND %s
		AND cxc_fact.saldoFactura = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq1) > 0) ? " AND " : " WHERE ";
		$sqlBusq1 .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.idNotaCargo, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.estatus IN (1)) AS q2
			WHERE q2.idNotaCargo = cxc_nd.idNotaCargo) BETWEEN %s AND %s
		AND cxc_nd.saldoNotaCargo = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("((SELECT MAX(q2.fechaPagoAnticipo)
			FROM (SELECT cxc_pago.idAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.estatus IN (1)) AS q2
			WHERE q2.idAnticipo = cxc_ant.idAnticipo) BETWEEN %s AND %s
		AND cxc_ant.saldoAnticipo = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (8)
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (8)
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (8)
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.numeroControlDetalleAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (8)
					AND cxc_pago.estatus IN (1)) AS q2
			WHERE q2.numeroDocumento = cxc_nc.idNotaCredito) BETWEEN %s AND %s
		AND cxc_nc.saldoNotaCredito = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (2)
					AND cxc_pago.id_cheque IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (2)
					AND cxc_pago.id_cheque IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (2)
					AND cxc_pago.id_cheque IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_cheque, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (2)
					AND cxc_pago.id_cheque IS NOT NULL
					AND cxc_pago.estatus IN (1)) AS q2
			WHERE q2.id_cheque = cxc_ch.id_cheque) BETWEEN %s AND %s
		AND cxc_ch.saldo_cheque = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (4)
					AND cxc_pago.id_transferencia IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (4)
					AND cxc_pago.id_transferencia IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (4)
					AND cxc_pago.id_transferencia IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_transferencia, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (4)
					AND cxc_pago.id_transferencia IS NOT NULL
					AND cxc_pago.estatus IN (1)) AS q2
			WHERE q2.id_transferencia = cxc_tb.id_transferencia) BETWEEN %s AND %s
		AND cxc_tb.saldo_transferencia = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.fechaRegistroFactura <= %s OR q.fechaRegistroFactura <= %s)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.id_modulo IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[7])."'", "defined", "'".str_replace(",","','",$valCadBusq[7])."'"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.estadoFactura IN (%s)",
			valTpDato($valCadBusq[8], "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("
		IF (q.tipoDocumento IN ('AN'),
			(SELECT cxc_pago.id_concepto
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) IN (%s)",
			valTpDato($valCadBusq[9], "campo"));
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("
		(CASE
			WHEN (q.tipoDocumento IN ('ND')) THEN
				(SELECT motivo.id_motivo
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = q.idFactura
					AND motivo.id_motivo IN (%s))
			WHEN (q.tipoDocumento IN ('NC')) THEN
				(SELECT motivo.id_motivo
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = q.idFactura
					AND motivo.id_motivo IN (%s))
		END) IN (%s)",
			valTpDato($valCadBusq[10], "campo"),
			valTpDato($valCadBusq[10], "campo"),
			valTpDato($valCadBusq[10], "campo"));
	}
	
	if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("
		(CASE
			WHEN (q.tipoDocumento IN ('FA')) THEN
				(SELECT uni_fis.id_condicion_unidad
				FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE cxc_fact_det_vehic.id_factura = q.idFactura)
			WHEN (q.tipoDocumento IN ('NC')) THEN
				(SELECT uni_fis.id_condicion_unidad
				FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE cxc_nc_det_vehic.id_nota_credito = q.idFactura)
		END) IN (%s)",
			valTpDato($valCadBusq[11], "campo"));
	}
	
	if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.numeroFactura LIKE %s
		OR CONCAT_WS(' ', cliente.lci,	cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR (CASE q.id_modulo
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) LIKE %s
		OR IF (q.tipoDocumento IN ('FA') AND q.id_modulo IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = q.idFactura))) 
			, NULL) LIKE %s
		OR IF (q.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) LIKE %s
		OR (CASE
				WHEN (q.tipoDocumento IN ('ND')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = q.idFactura)
				WHEN (q.tipoDocumento IN ('NC')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = q.idFactura)
			END) LIKE %s
		OR q.observacionFactura LIKE %s)",
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"));
	}
	
	$query = sprintf("SELECT q.*,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		(CASE q.id_modulo
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) AS numero_pedido,
		
		IF (q.tipoDocumento IN ('FA') AND q.id_modulo IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = q.idFactura))) 
			, NULL) AS numero_siniestro,
		
		IF (q.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) AS descripcion_concepto_forma_pago,
		
		(CASE
			WHEN (q.tipoDocumento IN ('ND')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = q.idFactura)
			WHEN (q.tipoDocumento IN ('NC')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = q.idFactura)
		END) AS descripcion_motivo,
		
		(CASE
			WHEN (q.tipoDocumento IN ('FA','ND')) THEN
				(CASE q.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (q.tipoDocumento IN ('AN','NC','CH','TB')) THEN
				(CASE q.estatus
					WHEN 1 THEN
						(CASE q.estadoFactura
							WHEN 0 THEN 'No Cancelado'
							WHEN 1 THEN 'Cancelado (No Asignado)'
							WHEN 2 THEN 'Asignado Parcial'
							WHEN 3 THEN 'Asignado'
							WHEN 4 THEN 'No Cancelado (Asignado)'
						END)
					ELSE
						'Anulado'
				END)
		END) AS estado_documento,
		
		(CASE q.tipoDocumento
			WHEN ('FA') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.estatus IN (1)
						
					UNION
		
					SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_factura = q.idFactura)
			WHEN ('ND') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.idNotaCargo, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.idNotaCargo = q.idFactura)
			WHEN ('AN') THEN
				(SELECT MAX(q2.fechaPagoAnticipo)
				FROM (SELECT cxc_pago.idAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.idAnticipo = q.idFactura)
			WHEN ('NC') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroControlDetalleAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (8)
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.numeroDocumento = q.idFactura)
			WHEN ('CH') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_cheque = q.idFactura)
			WHEN ('TB') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_transferencia = q.idFactura)
		END) AS fecha_ultimo_pago,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM (SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_fact.idFactura,
				cxc_fact.id_empresa,
				cxc_fact.fechaRegistroFactura,
				cxc_fact.fechaVencimientoFactura,
				cxc_fact.fecha_pagada,
				cxc_fact.fecha_cierre,
				cxc_fact.numeroFactura,
				cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
				cxc_fact.numeroPedido,
				cxc_fact.idCliente,
				1 AS estatus,
				cxc_fact.estadoFactura,
				cxc_fact.montoTotalFactura,
				(cxc_fact.montoTotalFactura - cxc_fact.saldoFactura) AS totalPagadoAnticipo,
				cxc_fact.saldoFactura,
				cxc_fact.observacionFactura
			FROM cj_cc_encabezadofactura cxc_fact
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_nd.idNotaCargo,
				cxc_nd.id_empresa,
				cxc_nd.fechaRegistroNotaCargo,
				cxc_nd.fechaVencimientoNotaCargo,
				cxc_nd.fecha_pagada,
				NULL AS fecha_cierre,
				cxc_nd.numeroNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				NULL AS numeroPedido,
				cxc_nd.idCliente,
				1 AS estatus,
				cxc_nd.estadoNotaCargo,
				cxc_nd.montoTotalNotaCargo,
				(cxc_nd.montoTotalNotaCargo - cxc_nd.saldoNotaCargo) AS totalPagadoAnticipo,
				cxc_nd.saldoNotaCargo,
				cxc_nd.observacionNotaCargo
			FROM cj_cc_notadecargo cxc_nd
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nd.idNotaCargo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'ND') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_ant.idAnticipo,
				cxc_ant.id_empresa,
				cxc_ant.fechaAnticipo,
				CURDATE() AS fechaVencimientoFactura,
				cxc_ant.fecha_pagada,
				NULL AS fecha_cierre,
				cxc_ant.numeroAnticipo,
				cxc_ant.idDepartamento AS id_modulo,
				NULL AS numeroPedido,
				cxc_ant.idCliente,
				cxc_ant.estatus,
				IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, 0) AS estadoAnticipo,
				cxc_ant.montoNetoAnticipo,
				cxc_ant.totalPagadoAnticipo,
				IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, NULL) AS saldoAnticipo,
				cxc_ant.observacionesAnticipo
			FROM cj_cc_anticipo cxc_ant
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ant.idAnticipo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'AN') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_nc.idNotaCredito,
				cxc_nc.id_empresa,
				cxc_nc.fechaNotaCredito,
				CURDATE() AS fechaVencimientoFactura,
				NULL AS fecha_pagada,
				NULL AS fecha_cierre,
				cxc_nc.numeracion_nota_credito,
				cxc_nc.idDepartamentoNotaCredito,
				cxc_fact.numeroPedido,
				cxc_nc.idCliente,
				1 AS estatus,
				cxc_nc.estadoNotaCredito,
				cxc_nc.montoNetoNotaCredito,
				cxc_nc.montoNetoNotaCredito AS totalPagadoAnticipo,
				cxc_nc.saldoNotaCredito,
				cxc_nc.observacionesNotaCredito
			FROM cj_cc_notacredito cxc_nc
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nc.idNotaCredito = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'NC')
				LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_ch.id_cheque,
				cxc_ch.id_empresa,
				cxc_ch.fecha_cheque,
				CURDATE() AS fechaVencimientoFactura,
				NULL AS fecha_pagada,
				NULL AS fecha_cierre,
				cxc_ch.numero_cheque,
				cxc_ch.id_departamento,
				NULL AS numeroPedido,
				cxc_ch.id_cliente,
				cxc_ch.estatus,
				IF (cxc_ch.estatus = 1, cxc_ch.estado_cheque, NULL) AS estado_cheque,
				cxc_ch.monto_neto_cheque,
				cxc_ch.monto_neto_cheque AS totalPagadoAnticipo,
				IF (cxc_ch.estatus = 1, cxc_ch.saldo_cheque, 0) AS saldo_cheque,
				cxc_ch.observacion_cheque
			FROM cj_cc_cheque cxc_ch
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ch.id_cheque = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'CH') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_tb.id_transferencia,
				cxc_tb.id_empresa,
				cxc_tb.fecha_transferencia,
				CURDATE() AS fechaVencimientoFactura,
				NULL AS fecha_pagada,
				NULL AS fecha_cierre,
				cxc_tb.numero_transferencia,
				cxc_tb.id_departamento,
				NULL AS numeroPedido,
				cxc_tb.id_cliente,
				cxc_tb.estatus,
				IF (cxc_tb.estatus = 1, cxc_tb.estado_transferencia, NULL) AS estado_transferencia,
				cxc_tb.monto_neto_transferencia,
				cxc_tb.monto_neto_transferencia AS totalPagadoAnticipo,
				IF (cxc_tb.estatus = 1, cxc_tb.saldo_transferencia, 0) AS saldo_transferencia,
				cxc_tb.observacion_transferencia
			FROM cj_cc_transferencia cxc_tb
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_tb.id_transferencia = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'TB') %s) AS q
		INNER JOIN cj_cc_cliente cliente ON (q.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (q.numeroPedido = ped_vent.id_pedido_venta AND q.id_modulo = 0)
		LEFT JOIN sa_orden orden ON (q.numeroPedido = orden.id_orden AND q.id_modulo = 1)
		LEFT JOIN an_pedido an_ped_vent ON (q.numeroPedido = an_ped_vent.id_pedido AND q.id_modulo = 2)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (q.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq, $sqlBusq1, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$query);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	if (in_array($valCadBusq[4],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			if (in_array($valCadBusq[4],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "1%", $pageNum, "idCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			} else {
				$htmlTh .= "<td width=\"1%\"></td>";
			}
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "q.fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "q.fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "q.fecha_ultimo_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ult. Pago");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "q.tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "LPAD(CONVERT(q.numeroFactura, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "LPAD(CONVERT(q.numero_pedido, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido / Orden");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
		$htmlTh .= "</tr>";
	} else {
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			$htmlTh .= ($valCadBusq[5] == 1) ? ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa") : "";
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "idCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "62%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "q.montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
		$htmlTh .= "</tr>";
	}
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$arrayDcto = array(
			"id_modulo" => $row['id_modulo'],
			"estadoFactura" => $row['estadoFactura'],
			"tipoDocumentoN" => $row['tipoDocumentoN'],
			"tipoDocumento" => $row['tipoDocumento'],
			"idFactura" => $row['idFactura'],
			"nombre_empresa" => $row['nombre_empresa'],
			"fechaRegistroFactura" => $row['fechaRegistroFactura'],
			"fechaVencimientoFactura" => $row['fechaVencimientoFactura'],
			"fecha_ultimo_pago" => $row['fecha_ultimo_pago'],
			"idEstadoCuenta" => $row['idEstadoCuenta'],
			"numeroFactura" => $row['numeroFactura'],
			"numeroPedido" => $row['numeroPedido'],
			"numero_pedido" => $row['numero_pedido'],
			"numero_siniestro" => $row['numero_siniestro'],
			"id_cliente" => $row['id_cliente'],
			"nombre_cliente" => $row['nombre_cliente'],
			"descripcion_concepto_forma_pago" => $row['descripcion_concepto_forma_pago'],
			"descripcion_motivo" => $row['descripcion_motivo'],
			"observacionFactura" => $row['observacionFactura'],
			"estado_documento" => $row['estado_documento'],
			"abreviacion_moneda_local" => $row['abreviacion_moneda_local'],
			"saldoFactura" => $row['saldoFactura'],
			"montoTotalFactura" => $row['montoTotalFactura'],
			"totalPagadoAnticipo" => $row['totalPagadoAnticipo']);
		
		$existe = false;
		if (isset($arrayECGeneral)) {
			foreach($arrayECGeneral as $indice => $valor) {
				if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
					//$groupBy = ($valCadBusq[5] == 1) ? "GROUP BY q.id_empresa, q.idCliente" : "GROUP BY q.idCliente";
					if ($valCadBusq[5] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
						if ($arrayECGeneral[$indice]['id_empresa'] == $row['id_empresa'] && $arrayECGeneral[$indice]['id_cliente'] == $row['idCliente']) {
							$lstTipoDetalle = 1;
							$existe = true;
							
							$arrayDctoAux = $arrayECGeneral[$indice]['arrayDcto'];
							array_push($arrayDctoAux, $arrayDcto);
							$arrayECGeneral[$indice]['arrayDcto'] = $arrayDctoAux;
						}
					} else {
						if ($arrayECGeneral[$indice]['id_cliente'] == $row['idCliente']) {
							$lstTipoDetalle = 2;
							$existe = true;
							
							$arrayDctoAux = $arrayECGeneral[$indice]['arrayDcto'];
							array_push($arrayDctoAux, $arrayDcto);
							$arrayECGeneral[$indice]['arrayDcto'] = $arrayDctoAux;
						}
					}
				} else {
					//$groupBy = "GROUP BY q.id_empresa, vw_cxc_as.idCliente";
					if ($arrayECGeneral[$indice]['id_empresa'] == $row['id_empresa'] && $arrayECGeneral[$indice]['id_cliente'] == $row['idCliente']) {
						$lstTipoDetalle = 1;
						$existe = true;
						
						$arrayDctoAux = $arrayECGeneral[$indice]['arrayDcto'];
						array_push($arrayDctoAux, $arrayDcto);
						$arrayECGeneral[$indice]['arrayDcto'] = $arrayDctoAux;
					}
				}
			}
		}
		
		if ($existe == false) {
			$arrayDctoAux = NULL;
			$arrayDctoAux[] = $arrayDcto;
			if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
				if ($valCadBusq[5] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
					$arrayECGeneral[] = array(
						"id_empresa" => $row['id_empresa'],
						"nombre_empresa" => $row['nombre_empresa'],
						"id_cliente" => $row['idCliente'],
						"nombre_cliente" => $row['nombre_cliente'],
						"arrayDcto" => $arrayDctoAux);
				} else {
					$arrayECGeneral[] = array(
						"id_cliente" => $row['idCliente'],
						"nombre_cliente" => $row['nombre_cliente'],
						"arrayDcto" => $arrayDctoAux);
				}
			} else {
				$arrayECGeneral[] = array(
					"id_empresa" => $row['id_empresa'],
					"nombre_empresa" => $row['nombre_empresa'],
					"id_cliente" => $row['idCliente'],
					"nombre_cliente" => $row['nombre_cliente'],
					"arrayDcto" => $arrayDctoAux);
			}
		}
	}
	if (isset($arrayECGeneral)) {
		foreach($arrayECGeneral as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$totalSaldoCliente = 0;
			$totalMontoCliente = 0;
			
			$idEmpresa = $valor['id_empresa'];
			$nombreEmpresa = $valor['nombre_empresa'];
			$idCliente = $valor['id_cliente'];
			$nombreCliente = $valor['nombre_cliente'];
			$arrayDcto = $valor['arrayDcto'];
			
			if (in_array($valCadBusq[4],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTb .= ($contFila > 1) ? "<tr height=\"24\"><td>&nbsp;</td></tr>" : "";
				
				$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
					$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px tituloCampo\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".$idCliente."</td>";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">".utf8_encode($nombreCliente)."</td>";
				$htmlTb .= "</tr>";
				
				$contFila2 = 0;
			}
			
			if (isset($arrayDcto)) {
				foreach($arrayDcto as $indiceDcto => $valorDcto) {
					// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
					$signo = (in_array($valorDcto['tipoDocumento'],array("FA","ND"))) ? 1 : (-1);
					
					$fecha1 = strtotime(date(spanDateFormat));
					$fecha2 = strtotime($valorDcto['fechaVencimientoFactura']);
					
					$dias = ($valorDcto['fechaVencimientoFactura'] != "" && in_array($valorDcto['estadoFactura'],array(0,2,4))) ? ($fecha1 - $fecha2) / 86400 : "";
					
					switch($valorDcto['id_modulo']) {
						case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
						case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
						case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
						case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
						case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
						case 5 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"".utf8_encode("Financiamiento")."\"/>"; break;
						default : $imgPedidoModulo = $valorDcto['id_modulo'];
					}
					
					switch($valorDcto['estadoFactura']) {
						case "" : $class = "class=\"divMsjInfo5\""; break;
						case 0 : $class = "class=\"divMsjError\""; break;
						case 1 : $class = "class=\"divMsjInfo\""; break;
						case 2 : $class = "class=\"divMsjAlerta\""; break;
						case 3 : $class = "class=\"divMsjInfo3\""; break;
						case 4 : $class = "class=\"divMsjInfo4\""; break;
					}
					
					if (in_array($valCadBusq[4],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
						$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila2++;
						
						$rowspan = (strlen($valorDcto['descripcion_motivo']) > 0 || strlen($valorDcto['observacionFactura']) > 0 || strlen($valorDcto['motivo_anulacion']) > 0) ? "rowspan=\"2\"" : "";
						
						$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
							$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila2) + (($pageNum) * $maxRows))."</td>";
							$htmlTb .= "<td ".$rowspan.">"."</td>";
							$htmlTb .= "<td>".utf8_encode($valorDcto['nombre_empresa'])."</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($valorDcto['fechaRegistroFactura']))."</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">";
								$htmlTb .= (($valorDcto['fechaVencimientoFactura'] != "") ? date(spanDateFormat, strtotime($valorDcto['fechaVencimientoFactura'])) : "-");
								$htmlTb .= (($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "");
							$htmlTb .= "</td>";
							$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($valorDcto['fecha_ultimo_pago'] != "") ? date(spanDateFormat, strtotime($valorDcto['fecha_ultimo_pago'])) : "-")."</td>";
							$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$valorDcto['idEstadoCuenta']."\">".utf8_encode($valorDcto['tipoDocumento']).(($valorDcto['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
							$htmlTb .= "<td align=\"right\">";
								$objDcto = new Documento;
								$objDcto->raizDir = $raiz;
								$objDcto->tipoMovimiento = (in_array($valorDcto['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
								$objDcto->tipoDocumento = $valorDcto['tipoDocumento'];
								$objDcto->tipoDocumentoMovimiento = (in_array($valorDcto['tipoDocumento'],array("NC"))) ? 2 : 1;
								$objDcto->idModulo = $valorDcto['id_modulo'];
								$objDcto->idDocumento = $valorDcto['idFactura'];
								$aVerDcto = $objDcto->verDocumento();
								$htmlTb .= "<table width=\"100%\">";
								$htmlTb .= "<tr align=\"right\">";
									$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
									$htmlTb .= "<td>".$imgPedidoModulo."</td>";
									$htmlTb .= "<td width=\"100%\">".utf8_encode($valorDcto['numeroFactura'])."</td>";
								$htmlTb .= "</tr>";
								$htmlTb .= "</table>";
							$htmlTb .= "</td>";
							$htmlTb .= "<td align=\"right\">";
								$objDcto = new Documento;
								$objDcto->raizDir = $raiz;
								$objDcto->tipoMovimiento = (in_array($valorDcto['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
								$objDcto->tipoDocumento = "PD";
								$objDcto->tipoDocumentoMovimiento = (in_array($valorDcto['tipoDocumento'],array("NC"))) ? 2 : 1;
								$objDcto->idModulo = $valorDcto['id_modulo'];
								$objDcto->idDocumento = $valorDcto['numeroPedido'];
								$objDcto->mostrarDocumento = "verPDF";
								$aVerDcto = $objDcto->verPedido();
								$htmlTb .= "<table width=\"100%\">";
								$htmlTb .= "<tr align=\"right\">";
									$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
									$htmlTb .= "<td width=\"100%\">".utf8_encode($valorDcto['numero_pedido'])."</td>";
								$htmlTb .= "</tr>";
								$htmlTb .= "</table>";
							$htmlTb .= "</td>";
							$htmlTb .= "<td>";
								$htmlTb .= "<div>".utf8_encode($valorDcto['id_cliente'].".- ".$valorDcto['nombre_cliente'])."</div>";
								$htmlTb .= (($valorDcto['numero_siniestro']) ? "<div align=\"right\">NRO. SINIESTRO: ".$valorDcto['numero_siniestro']."</div>" : "");
								$htmlTb .= ((strlen($valorDcto['descripcion_concepto_forma_pago']) > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($valorDcto['descripcion_concepto_forma_pago'])."</div>" : "");
							$htmlTb .= "</td>";
							$htmlTb .= "<td align=\"center\" ".$class." ".$rowspan.">".$valorDcto['estado_documento']."</td>";
							$htmlTb .= "<td align=\"right\" ".$rowspan.">".$valorDcto['abreviacion_moneda_local'].number_format($signo * $valorDcto['saldoFactura'], 2, ".", ",")."</td>";
							$htmlTb .= "<td ".$rowspan.">";
								$htmlTb .= "<table border=\"0\" width=\"100%\">";
								$htmlTb .= "<tr align=\"right\">";
									$htmlTb .= "<td colspan=\"2\">".$valorDcto['abreviacion_moneda_local'].number_format($signo * $valorDcto['montoTotalFactura'], 2, ".", ",")."</td>";
								$htmlTb .= "</tr>";
								if ($valorDcto['totalPagadoAnticipo'] != $valorDcto['montoTotalFactura'] && $valorDcto['totalPagadoAnticipo'] > 0) {
									$htmlTb .= "<tr align=\"right\" class=\"textoNegrita_9px\">";
										$htmlTb .= "<td>Pagado:</td>";
										$htmlTb .= "<td width=\"100%\">".$valorDcto['abreviacion_moneda_local'].number_format($signo * $valorDcto['totalPagadoAnticipo'], 2, ".", ",")."</td>";
									$htmlTb .= "</tr>";
								}
								$htmlTb .= "</table>";
							$htmlTb .= "</td>";
						$htmlTb .= "</tr>";
						
						if (strlen($rowspan) > 0) {
							$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
								$htmlTb .= "<td colspan=\"8\">";
									$htmlTb .= (strlen($valorDcto['descripcion_motivo']) > 0) ? "<div class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($valorDcto['descripcion_motivo'])."</div>" : "";
									$htmlTb .= ((strlen($valorDcto['observacionFactura']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($valorDcto['observacionFactura'])."</div>" : "");
									$htmlTb .= ((strlen($valorDcto['motivo_anulacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px textoRojoNegrita\" title=\"Motivo de la Anulación\">".utf8_encode($valorDcto['motivo_anulacion'])."</div>" : "");
								$htmlTb .= "</td>";
							$htmlTb .= "</tr>";
						}
					}
					
					$arrayTotalSaldos[$valorDcto['tipoDocumento']]['cant_dctos'] += 1;
					$arrayTotalSaldos[$valorDcto['tipoDocumento']]['saldo_dctos'] += ($signo * $valorDcto['saldoFactura']);
					$arrayTotalSaldos[$valorDcto['tipoDocumento']]['total_dctos'] += ($signo * $valorDcto['montoTotalFactura']);
					
					$totalSaldoCliente += $signo * $valorDcto['saldoFactura'];
					$totalMontoCliente += $signo * $valorDcto['montoTotalFactura'];
				}
				
				if (in_array($valCadBusq[4],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
					$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
						$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"11\">".utf8_encode($nombreCliente).":</td>";
						$htmlTb .= "<td align=\"right\">".number_format($totalSaldoCliente, 2, ".", ",")."</td>";
						$htmlTb .= "<td align=\"right\">".number_format($totalMontoCliente, 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
				} else if (in_array($valCadBusq[4],array(4))) { // 3 = General por Cliente, 4 = General por Dcto.
				} else {
					$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
						$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
						$htmlTb .= ($valCadBusq[5] == 1) ? "<td>".utf8_encode($nombreEmpresa)."</td>" : "";
						$htmlTb .= "<td align=\"right\">".$idCliente."</td>";
						$htmlTb .= "<td>".utf8_encode($nombreCliente)."</td>";
						$htmlTb .= "<td align=\"right\">".number_format($totalSaldoCliente, 2, ".", ",")."</td>";
						$htmlTb .= "<td align=\"right\">".number_format($totalMontoCliente, 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
				}
				
				$arrayTotal['cant_dctos'] += 1;
				$arrayTotal['saldo_dctos'] += $totalSaldoCliente;
				$arrayTotal['total_dctos'] += $totalMontoCliente;
			}
		}
	}
	if ($contFila > 0) {
		if (in_array($valCadBusq[4],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">".utf8_encode("Total Página:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_dctos'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"".(($valCadBusq[5] == 1) ? 4 : 3)."\">".utf8_encode("Total Página:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_dctos'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotalFinal['cant_dctos'] += $arrayTotal['cant_dctos'];
		$arrayTotalFinal['saldo_dctos'] += $arrayTotal['saldo_dctos'];
		$arrayTotalFinal['total_dctos'] += $arrayTotal['total_dctos'];
		
		if ($pageNum == $totalPages) {
			if ($totalPages > 0) {
				$rs = mysql_query($query);
				$arrayTotalFinal = array();
				while ($row = mysql_fetch_assoc($rs)) {
					// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
					$signo = (in_array($row['tipoDocumento'],array("FA","ND"))) ? 1 : (-1);
					
					$arrayTotalFinal['cant_dctos'] += 1;
					$arrayTotalFinal['saldo_dctos'] += $signo * $row['saldoFactura'];
					$arrayTotalFinal['total_dctos'] += $signo * $row['montoTotalFactura'];
				}
			}
			
			if (in_array($valCadBusq[4],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">".utf8_encode("Total de Totales:")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal['saldo_dctos'], 2, ".", ",")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal['total_dctos'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
			} else {
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"".(($valCadBusq[5] == 1) ? 4 : 3)."\">".utf8_encode("Total de Totales:")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal['saldo_dctos'], 2, ".", ",")."</td>";
					$htmlTb .= "<td>".number_format($arrayTotalFinal['total_dctos'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
			}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECGeneral(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	if ($totalPages > 0) {
		$rs = mysql_query($query);
		$arrayTotalSaldos = NULL;
		while ($row = mysql_fetch_assoc($rs)) {
			// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
			$signo = (in_array($row['tipoDocumento'],array("FA","ND"))) ? 1 : (-1);
			
			$arrayTotalSaldos[$row['tipoDocumento']]['cant_dctos'] += 1;
			$arrayTotalSaldos[$row['tipoDocumento']]['saldo_dctos'] += ($signo * $row['saldoFactura']);
			$arrayTotalSaldos[$row['tipoDocumento']]['total_dctos'] += ($signo * $row['montoTotalFactura']);
		}
	}
	
	$htmlTblIni .= "<tr>";
		$htmlTblIni .= "<td colspan=\"50\">";
			$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"12\">"."Saldos"."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Factura")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Nota de Débito")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Anticipo")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\" height=\"24\">";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos['FA']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['FA']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['FA']['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos['ND']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['ND']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['ND']['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos['AN']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['AN']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['AN']['total_dctos'], 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Nota de Crédito")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Cheque")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Transferencia")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\" height=\"24\">";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['NC']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['NC']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['NC']['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['CH']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['CH']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['CH']['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['TB']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['TB']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['TB']['total_dctos'], 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "</table>";
		$htmlTblIni .= "</td>";
	$htmlTblIni .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaECIndividual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = q.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM an_pagos cxc_pago
				WHERE cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.estatus IN (1)) AS q2
			WHERE q2.id_factura = cxc_fact.idFactura) BETWEEN %s AND %s
		AND cxc_fact.saldoFactura = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq1) > 0) ? " AND " : " WHERE ";
		$sqlBusq1 .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.idNotaCargo, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.estatus IN (1)) AS q2
			WHERE q2.idNotaCargo = cxc_nd.idNotaCargo) BETWEEN %s AND %s
		AND cxc_nd.saldoNotaCargo = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("((SELECT MAX(q2.fechaPagoAnticipo)
			FROM (SELECT cxc_pago.idAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.estatus IN (1)) AS q2
			WHERE q2.idAnticipo = cxc_ant.idAnticipo) BETWEEN %s AND %s
		AND cxc_ant.saldoAnticipo = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (8)
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (8)
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (8)
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.numeroControlDetalleAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (8)
					AND cxc_pago.estatus IN (1)) AS q2
			WHERE q2.numeroDocumento = cxc_nc.idNotaCredito) BETWEEN %s AND %s
		AND cxc_nc.saldoNotaCredito = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (2)
					AND cxc_pago.id_cheque IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (2)
					AND cxc_pago.id_cheque IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (2)
					AND cxc_pago.id_cheque IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_cheque, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (2)
					AND cxc_pago.id_cheque IS NOT NULL
					AND cxc_pago.estatus IN (1)) AS q2
			WHERE q2.id_cheque = cxc_ch.id_cheque) BETWEEN %s AND %s
		AND cxc_ch.saldo_cheque = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("((SELECT MAX(q2.fechaPago)
			FROM (SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM an_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (4)
					AND cxc_pago.id_transferencia IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
				WHERE cxc_pago.formaPago IN (4)
					AND cxc_pago.id_transferencia IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
				WHERE cxc_pago.idFormaPago IN (4)
					AND cxc_pago.id_transferencia IS NOT NULL
					AND cxc_pago.estatus IN (1)
				
				UNION
				
				SELECT cxc_pago.id_transferencia, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
				WHERE cxc_pago.id_forma_pago IN (4)
					AND cxc_pago.id_transferencia IS NOT NULL
					AND cxc_pago.estatus IN (1)) AS q2
			WHERE q2.id_transferencia = cxc_tb.id_transferencia) BETWEEN %s AND %s
		AND cxc_tb.saldo_transferencia = 0)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.fechaRegistroFactura <= %s OR q.fechaRegistroFactura <= %s)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.id_modulo IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[7])."'", "defined", "'".str_replace(",","','",$valCadBusq[7])."'"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("q.estadoFactura IN (%s)",
			valTpDato($valCadBusq[8], "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("
		IF (q.tipoDocumento IN ('AN'),
			(SELECT cxc_pago.id_concepto
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) IN (%s)",
			valTpDato($valCadBusq[9], "campo"));
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("
		(CASE
			WHEN (q.tipoDocumento IN ('ND')) THEN
				(SELECT motivo.id_motivo
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = q.idFactura
					AND motivo.id_motivo IN (%s))
			WHEN (q.tipoDocumento IN ('NC')) THEN
				(SELECT motivo.id_motivo
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = q.idFactura
					AND motivo.id_motivo IN (%s))
		END) IN (%s)",
			valTpDato($valCadBusq[10], "campo"),
			valTpDato($valCadBusq[10], "campo"),
			valTpDato($valCadBusq[10], "campo"));
	}
	
	if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("
		(CASE
			WHEN (q.tipoDocumento IN ('FA')) THEN
				(SELECT uni_fis.id_condicion_unidad
				FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE cxc_fact_det_vehic.id_factura = q.idFactura)
			WHEN (q.tipoDocumento IN ('NC')) THEN
				(SELECT uni_fis.id_condicion_unidad
				FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE cxc_nc_det_vehic.id_nota_credito = q.idFactura)
		END) IN (%s)",
			valTpDato($valCadBusq[11], "campo"));
	}
	
	if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(q.numeroFactura LIKE %s
		OR CONCAT_WS(' ', cliente.lci,	cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR (CASE q.id_modulo
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) LIKE %s
		OR IF (q.tipoDocumento IN ('FA') AND q.id_modulo IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = q.idFactura))) 
			, NULL) LIKE %s
		OR IF (q.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) LIKE %s
		OR (CASE
				WHEN (q.tipoDocumento IN ('ND')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = q.idFactura)
				WHEN (q.tipoDocumento IN ('NC')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = q.idFactura)
			END) LIKE %s
		OR q.observacionFactura LIKE %s)",
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"));
	}
	
	$query = sprintf("SELECT q.*,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		(CASE q.id_modulo
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) AS numero_pedido,
		
		IF (q.tipoDocumento IN ('FA') AND q.id_modulo IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = q.idFactura))) 
			, NULL) AS numero_siniestro,
		
		IF (q.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = q.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) AS descripcion_concepto_forma_pago,
		
		(CASE
			WHEN (q.tipoDocumento IN ('ND')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = q.idFactura)
			WHEN (q.tipoDocumento IN ('NC')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = q.idFactura)
		END) AS descripcion_motivo,
		
		(CASE
			WHEN (q.tipoDocumento IN ('FA','ND')) THEN
				(CASE q.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (q.tipoDocumento IN ('AN','NC','CH','TB')) THEN
				(CASE q.estatus
					WHEN 1 THEN
						(CASE q.estadoFactura
							WHEN 0 THEN 'No Cancelado'
							WHEN 1 THEN 'Cancelado (No Asignado)'
							WHEN 2 THEN 'Asignado Parcial'
							WHEN 3 THEN 'Asignado'
							WHEN 4 THEN 'No Cancelado (Asignado)'
						END)
					ELSE
						'Anulado'
				END)
		END) AS estado_documento,
		
		(CASE q.tipoDocumento
			WHEN ('FA') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.estatus IN (1)
						
					UNION
		
					SELECT cxc_pago.id_factura, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_factura = q.idFactura)
			WHEN ('ND') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.idNotaCargo, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.idNotaCargo = q.idFactura)
			WHEN ('AN') THEN
				(SELECT MAX(q2.fechaPagoAnticipo)
				FROM (SELECT cxc_pago.idAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.estatus IN (1)) AS q2
				WHERE q2.idAnticipo = q.idFactura)
			WHEN ('NC') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroDocumento, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (8)
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.numeroControlDetalleAnticipo, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (8)
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.numeroDocumento = q.idFactura)
			WHEN ('CH') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_cheque, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (2)
						AND cxc_pago.id_cheque IS NOT NULL
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_cheque = q.idFactura)
			WHEN ('TB') THEN
				(SELECT MAX(q2.fechaPago)
				FROM (SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM an_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM sa_iv_pagos cxc_pago
					WHERE cxc_pago.formaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPago FROM cj_det_nota_cargo cxc_pago
					WHERE cxc_pago.idFormaPago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)
					
					UNION
					
					SELECT cxc_pago.id_transferencia, cxc_pago.fechaPagoAnticipo FROM cj_cc_detalleanticipo cxc_pago
					WHERE cxc_pago.id_forma_pago IN (4)
						AND cxc_pago.id_transferencia IS NOT NULL
						AND cxc_pago.estatus IN (1)) AS q2
				WHERE q2.id_transferencia = q.idFactura)
		END) AS fecha_ultimo_pago,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM (SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_fact.idFactura,
				cxc_fact.id_empresa,
				cxc_fact.fechaRegistroFactura,
				cxc_fact.fechaVencimientoFactura,
				cxc_fact.fecha_pagada,
				cxc_fact.fecha_cierre,
				cxc_fact.numeroFactura,
				cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
				cxc_fact.numeroPedido,
				cxc_fact.idCliente,
				1 AS estatus,
				cxc_fact.estadoFactura,
				cxc_fact.montoTotalFactura,
				(cxc_fact.montoTotalFactura - cxc_fact.saldoFactura) AS totalPagadoAnticipo,
				cxc_fact.saldoFactura,
				cxc_fact.observacionFactura
			FROM cj_cc_encabezadofactura cxc_fact
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_nd.idNotaCargo,
				cxc_nd.id_empresa,
				cxc_nd.fechaRegistroNotaCargo,
				cxc_nd.fechaVencimientoNotaCargo,
				cxc_nd.fecha_pagada,
				NULL AS fecha_cierre,
				cxc_nd.numeroNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				NULL AS numeroPedido,
				cxc_nd.idCliente,
				1 AS estatus,
				cxc_nd.estadoNotaCargo,
				cxc_nd.montoTotalNotaCargo,
				(cxc_nd.montoTotalNotaCargo - cxc_nd.saldoNotaCargo) AS totalPagadoAnticipo,
				cxc_nd.saldoNotaCargo,
				cxc_nd.observacionNotaCargo
			FROM cj_cc_notadecargo cxc_nd
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nd.idNotaCargo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'ND') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_ant.idAnticipo,
				cxc_ant.id_empresa,
				cxc_ant.fechaAnticipo,
				CURDATE() AS fechaVencimientoFactura,
				cxc_ant.fecha_pagada,
				NULL AS fecha_cierre,
				cxc_ant.numeroAnticipo,
				cxc_ant.idDepartamento AS id_modulo,
				NULL AS numeroPedido,
				cxc_ant.idCliente,
				cxc_ant.estatus,
				IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, 0) AS estadoAnticipo,
				cxc_ant.montoNetoAnticipo,
				cxc_ant.totalPagadoAnticipo,
				IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, NULL) AS saldoAnticipo,
				cxc_ant.observacionesAnticipo
			FROM cj_cc_anticipo cxc_ant
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ant.idAnticipo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'AN') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_nc.idNotaCredito,
				cxc_nc.id_empresa,
				cxc_nc.fechaNotaCredito,
				CURDATE() AS fechaVencimientoFactura,
				NULL AS fecha_pagada,
				NULL AS fecha_cierre,
				cxc_nc.numeracion_nota_credito,
				cxc_nc.idDepartamentoNotaCredito,
				cxc_fact.numeroPedido,
				cxc_nc.idCliente,
				1 AS estatus,
				cxc_nc.estadoNotaCredito,
				cxc_nc.montoNetoNotaCredito,
				cxc_nc.montoNetoNotaCredito AS totalPagadoAnticipo,
				cxc_nc.saldoNotaCredito,
				cxc_nc.observacionesNotaCredito
			FROM cj_cc_notacredito cxc_nc
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nc.idNotaCredito = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'NC')
				LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_ch.id_cheque,
				cxc_ch.id_empresa,
				cxc_ch.fecha_cheque,
				CURDATE() AS fechaVencimientoFactura,
				NULL AS fecha_pagada,
				NULL AS fecha_cierre,
				cxc_ch.numero_cheque,
				cxc_ch.id_departamento,
				NULL AS numeroPedido,
				cxc_ch.id_cliente,
				cxc_ch.estatus,
				IF (cxc_ch.estatus = 1, cxc_ch.estado_cheque, NULL) AS estado_cheque,
				cxc_ch.monto_neto_cheque,
				cxc_ch.monto_neto_cheque AS totalPagadoAnticipo,
				IF (cxc_ch.estatus = 1, cxc_ch.saldo_cheque, 0) AS saldo_cheque,
				cxc_ch.observacion_cheque
			FROM cj_cc_cheque cxc_ch
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_ch.id_cheque = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'CH') %s
			
			UNION
			
			SELECT 
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_tb.id_transferencia,
				cxc_tb.id_empresa,
				cxc_tb.fecha_transferencia,
				CURDATE() AS fechaVencimientoFactura,
				NULL AS fecha_pagada,
				NULL AS fecha_cierre,
				cxc_tb.numero_transferencia,
				cxc_tb.id_departamento,
				NULL AS numeroPedido,
				cxc_tb.id_cliente,
				cxc_tb.estatus,
				IF (cxc_tb.estatus = 1, cxc_tb.estado_transferencia, NULL) AS estado_transferencia,
				cxc_tb.monto_neto_transferencia,
				cxc_tb.monto_neto_transferencia AS totalPagadoAnticipo,
				IF (cxc_tb.estatus = 1, cxc_tb.saldo_transferencia, 0) AS saldo_transferencia,
				cxc_tb.observacion_transferencia
			FROM cj_cc_transferencia cxc_tb
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_tb.id_transferencia = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'TB') %s) AS q
		INNER JOIN cj_cc_cliente cliente ON (q.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (q.numeroPedido = ped_vent.id_pedido_venta AND q.id_modulo = 0)
		LEFT JOIN sa_orden orden ON (q.numeroPedido = orden.id_orden AND q.id_modulo = 1)
		LEFT JOIN an_pedido an_ped_vent ON (q.numeroPedido = an_ped_vent.id_pedido AND q.id_modulo = 2)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (q.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq, $sqlBusq1, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "q.fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "q.fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "q.fecha_ultimo_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ult. Pago");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "q.tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "4%", $pageNum, "LPAD(CONVERT(q.numeroFactura, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "4%", $pageNum, "LPAD(CONVERT(q.numero_pedido, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido / Orden");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "26%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "q.saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "q.montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
		$signo = (in_array($row['tipoDocumento'],array("FA","ND"))) ? 1 : (-1);
		
		$fecha1 = strtotime(date(spanDateFormat));
		$fecha2 = strtotime($row['fechaVencimientoFactura']);
		
		$dias = ($row['fechaVencimientoFactura'] != "" && in_array($row['estadoFactura'],array(0,2,4))) ? ($fecha1 - $fecha2) / 86400 : "";
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			case 5 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"".utf8_encode("Financiamiento")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		switch($row['estadoFactura']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
			
		$rowspan = (strlen($row['descripcion_motivo']) > 0 || strlen($row['observacionFactura']) > 0 || strlen($row['motivo_anulacion']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td ".$rowspan.">"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">";
				$htmlTb .= (($row['fechaVencimientoFactura'] != "") ? date(spanDateFormat, strtotime($row['fechaVencimientoFactura'])) : "-");
				$htmlTb .= (($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($row['fecha_ultimo_pago'] != "") ? date(spanDateFormat, strtotime($row['fecha_ultimo_pago'])) : "-")."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$row['idEstadoCuenta']."\">".utf8_encode($row['tipoDocumento']).(($row['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = $row['tipoDocumento'];
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['idFactura'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroFactura'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "PD";
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['numeroPedido'];
				$objDcto->mostrarDocumento = "verPDF";
				$aVerDcto = $objDcto->verPedido();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numero_pedido'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
				$htmlTb .= (($row['numero_siniestro']) ? "<div align=\"right\">NRO. SINIESTRO: ".$row['numero_siniestro']."</div>" : "");
				$htmlTb .= ((strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</div>" : "");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class." ".$rowspan.">".$row['estado_documento']."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".$row['abreviacion_moneda_local'].number_format($signo * $row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td ".$rowspan.">";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td colspan=\"2\">".$row['abreviacion_moneda_local'].number_format($signo * $row['montoTotalFactura'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				if ($row['totalPagadoAnticipo'] != $row['montoTotalFactura'] && $row['totalPagadoAnticipo'] > 0) {
					$htmlTb .= "<tr align=\"right\" class=\"textoNegrita_9px\">";
						$htmlTb .= "<td>Pagado:</td>";
						$htmlTb .= "<td width=\"100%\">".$row['abreviacion_moneda_local'].number_format($signo * $row['totalPagadoAnticipo'], 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
				}
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"8\">";
					$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<div class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</div>" : "";
					$htmlTb .= ((strlen($row['observacionFactura']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionFactura'])."</div>" : "");
					$htmlTb .= ((strlen($row['motivo_anulacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px textoRojoNegrita\" title=\"Motivo de la Anulación\">".utf8_encode($row['motivo_anulacion'])."</div>" : "");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotalSaldos[$row['tipoDocumento']]['cant_dctos'] += 1;
		$arrayTotalSaldos[$row['tipoDocumento']]['saldo_dctos'] += ($signo * $row['saldoFactura']);
		$arrayTotalSaldos[$row['tipoDocumento']]['total_dctos'] += ($signo * $row['montoTotalFactura']);
		
		$arrayTotal['cant_dctos'] += 1;
		$arrayTotal['saldo_dctos'] += $signo * $row['saldoFactura'];
		$arrayTotal['total_dctos'] += $signo * $row['montoTotalFactura'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">".utf8_encode("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['saldo_dctos'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_dctos'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalFinal['cant_dctos'] += $arrayTotal['cant_dctos'];
		$arrayTotalFinal['saldo_dctos'] += $arrayTotal['saldo_dctos'];
		$arrayTotalFinal['total_dctos'] += $arrayTotal['total_dctos'];
		
		if ($pageNum == $totalPages) {
			if ($totalPages > 0) {
				$rs = mysql_query($query);
				$arrayTotalFinal = array();
				while ($row = mysql_fetch_assoc($rs)) {
					// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
					$signo = (in_array($row['tipoDocumento'],array("FA","ND"))) ? 1 : (-1);
					
					$arrayTotalFinal['saldo_dctos'] += 1;
					$arrayTotalFinal['saldo_dctos'] += $signo * $row['saldoFactura'];
					$arrayTotalFinal['total_dctos'] += $signo * $row['montoTotalFactura'];
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">".utf8_encode("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['total_dctos'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	if ($totalPages > 0) {
		$rs = mysql_query($query);
		$arrayTotalSaldos = NULL;
		while ($row = mysql_fetch_assoc($rs)) {
			// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
			$signo = (in_array($row['tipoDocumento'],array("FA","ND"))) ? 1 : (-1);
			
			$arrayTotalSaldos[$row['tipoDocumento']]['cant_dctos'] += 1;
			$arrayTotalSaldos[$row['tipoDocumento']]['saldo_dctos'] += ($signo * $row['saldoFactura']);
			$arrayTotalSaldos[$row['tipoDocumento']]['total_dctos'] += ($signo * $row['montoTotalFactura']);
		}
	}
	
	$htmlTblIni .= "<tr>";
		$htmlTblIni .= "<td colspan=\"50\">";
			$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"12\">"."Saldos"."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Factura")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Nota de Débito")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Anticipo")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\" height=\"24\">";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos['FA']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['FA']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['FA']['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos['ND']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['ND']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['ND']['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"9%\">".number_format($arrayTotalSaldos['AN']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['AN']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"12%\">".number_format($arrayTotalSaldos['AN']['total_dctos'], 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Nota de Crédito")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Cheque")."</td>";
				$htmlTblIni .= "<td colspan=\"3\">".utf8_encode("Transferencia")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Cant. Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Saldo Dctos.")."</td>";
				$htmlTblIni .= "<td>".utf8_encode("Total Dctos.")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\" height=\"24\">";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['NC']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['NC']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['NC']['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['CH']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['CH']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['CH']['total_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['TB']['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['TB']['saldo_dctos'], 2, ".", ",")."</td>";
				$htmlTblIni .= "<td>".number_format($arrayTotalSaldos['TB']['total_dctos'], 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "</table>";
		$htmlTblIni .= "</td>";
	$htmlTblIni .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaCbxModulos");
$xajax->register(XAJAX_FUNCTION,"cargaCbxTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"cargaLstConceptoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoFactura");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaECGeneral");
$xajax->register(XAJAX_FUNCTION,"listaECIndividual");
?>