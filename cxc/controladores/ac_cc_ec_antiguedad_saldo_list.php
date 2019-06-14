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

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEstadoCuenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdCliente'],
		$frmBuscar['txtFecha'],
		$frmBuscar['radioOpcion'],
		(is_array($frmBuscar['lstTipoDetalle']) ? implode(",",$frmBuscar['lstTipoDetalle']) : $frmBuscar['lstTipoDetalle']),
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
		(is_array($frmBuscar['lstConceptoPago']) ? implode(",",$frmBuscar['lstConceptoPago']) : $frmBuscar['lstConceptoPago']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		((count($frmBuscar['cbxTipoFinanciamiento']) > 0) ? implode(",",$frmBuscar['cbxTipoFinanciamiento']) : "-1"),
		$frmBuscar['txtCriterio']);
	
	switch ($frmBuscar['radioOpcion']) {
		case 1 : $objResponse->loadCommands(listaECIndividual(0, "CONCAT(vw_cxc_as.fechaRegistroFactura, vw_cxc_as.idEstadoCuenta)", "DESC", $valBusq)); break;
		case 2 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
		case 3 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
		case 4 : $objResponse->loadCommands(listaECGeneral(0, "CONCAT_WS(' ', cliente.nombre, cliente.apellido)", "ASC", $valBusq)); break;
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

function cargaCbxTipoFinanciamiento($idTipoFinanciamiento = ""){
	$objResponse = new xajaxResponse();
	
	if ($idTipoFinanciamiento != "-1" && $idTipoFinanciamiento != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tipo IN (%s)",
			valTpDato($idTipoFinanciamiento, "campo"));
	}
	
	$query = sprintf("SELECT * FROM fi_tipo %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><div class=\"checkbox-label\"><label><input type=\"checkbox\" id=\"cbxTipoFinanciamiento\" name=\"cbxTipoFinanciamiento[]\" checked=\"checked\" value=\"".$row['id_tipo']."\"/> ".$row['nombre_tipo']."</label></div></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdTipoFinanciamiento","innerHTML",$html);
	
	($totalRows > 0) ? $objResponse->script("byId('trTipoFinanciamiento').style.display = '';") : "";
	
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

function cargarDiasVencidos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM gruposestadocuenta");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<table border=\"0\" width=\"100%\">";
	$html .= "<tr align=\"left\" height=\"22\">";
		$html .= "<td colspan=\"4\" nowrap=\"nowrap\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"corriente\"/> Cta. Corriente</label></td>";
	$html .= "</tr>";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td nowrap=\"nowrap\" width=\"25%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde1\"/> De ".$row['desde1']." a ".$row['hasta1']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"25%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde2\"/> De ".$row['desde2']." a ".$row['hasta2']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"25%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde3\"/> De ".$row['desde3']." a ".$row['hasta3']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"25%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"masDe\"/> Mas de ".$row['masDe']."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdDiasVencidos","innerHTML",$html);
	
	return $objResponse;
}

function exportarAntiguedadSaldo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdCliente'],
		$frmBuscar['txtFecha'],
		$frmBuscar['radioOpcion'],
		(is_array($frmBuscar['lstTipoDetalle']) ? implode(",",$frmBuscar['lstTipoDetalle']) : $frmBuscar['lstTipoDetalle']),
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
		(is_array($frmBuscar['lstConceptoPago']) ? implode(",",$frmBuscar['lstConceptoPago']) : $frmBuscar['lstConceptoPago']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		((count($frmBuscar['cbxTipoFinanciamiento']) > 0) ? implode(",",$frmBuscar['cbxTipoFinanciamiento']) : "-1"),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cc_antiguedad_saldo_excel.php?valBusq=".$valBusq."','_self');");
	
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
	
	$queryGrupoEstado = "SELECT * FROM gruposestadocuenta";
	$rsGrupoEstado = mysql_query($queryGrupoEstado);
	if (!$rsGrupoEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);
				
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_as.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxc_as.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxc_as.fechaRegistroFactura <= %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	
	if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				(((SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
					WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
						AND cxc_ant.estatus IN (1)) = 1
					AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
				
				OR
				
				(SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
				WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
					AND cxc_ant.montoNetoAnticipo > cxc_ant.totalPagadoAnticipo
					AND cxc_ant.estadoAnticipo IN (4)
					AND cxc_ant.estatus IN (1)) = 1)
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				((SELECT cxc_ch.estatus FROM cj_cc_cheque cxc_ch
					WHERE cxc_ch.id_cheque = vw_cxc_as.idFactura
						AND cxc_ch.estatus IN (1)) = 1
					AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				((SELECT cxc_tb.estatus FROM cj_cc_transferencia cxc_tb
					WHERE cxc_tb.id_transferencia = vw_cxc_as.idFactura
						AND cxc_tb.estatus IN (1)) = 1
					AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
			ELSE
				ROUND(vw_cxc_as.saldoFactura, 2) > 0
		END))");
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(CASE
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
						(vw_cxc_as.montoTotal
							- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
						(vw_cxc_as.montoTotal
							- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				END)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (8)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
			
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (2)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (4)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
		END) > 0
			AND NOT ((CASE
					WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
						(CASE
							WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
								(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
							WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
								(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
						END)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
						(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
						
					WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
						WHERE q.numeroDocumento = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (8)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
						WHERE q.numeroDocumento = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
						WHERE q.id_cheque = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
						WHERE q.id_transferencia = vw_cxc_as.idFactura)
				END) < %s
					AND ((vw_cxc_as.tipoDocumento IN ('FA','ND') AND vw_cxc_as.estadoFactura IN (1))
						OR (vw_cxc_as.tipoDocumento IN ('AN','NC') AND vw_cxc_as.estadoFactura IN (3)))))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		// 1 = Detallado por Empresa, 2 = Consolidado
		$groupBy = ($valCadBusq[4] == 1) ? "GROUP BY vw_cxc_as.id_empresa, vw_cxc_as.idCliente" : "GROUP BY vw_cxc_as.idCliente";
	} else {
		$groupBy = "GROUP BY vw_cxc_as.id_empresa, vw_cxc_as.idCliente";
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde1",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde2",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde3",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("masDe",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		IF (vw_cxc_as.tipoDocumento IN ('AN'),
			(SELECT cxc_pago.id_concepto
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) IN (%s)",
			valTpDato($valCadBusq[8], "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(SELECT motivo.id_motivo
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura
					AND motivo.id_motivo IN (%s))
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(SELECT motivo.id_motivo
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura
					AND motivo.id_motivo IN (%s))
		END) IN (%s)",
			valTpDato($valCadBusq[9], "campo"),
			valTpDato($valCadBusq[9], "campo"),
			valTpDato($valCadBusq[9], "campo"));
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(SELECT uni_fis.id_condicion_unidad
				FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE cxc_fact_det_vehic.id_factura = vw_cxc_as.idFactura)
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(SELECT uni_fis.id_condicion_unidad
				FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE cxc_nc_det_vehic.id_nota_credito = vw_cxc_as.idFactura)
		END) IN (%s)",
			valTpDato($valCadBusq[10], "campo"));
	}
	
	if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (5)) THEN
				(CASE
					WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
						(SELECT COUNT(ped_financ.id_tipo) FROM fi_pedido ped_financ
						WHERE ped_financ.id_notadecargo_cxc = vw_cxc_as.idFactura
							AND ped_financ.id_tipo IN (%s)) > 0
					ELSE
						1
				END)
			ELSE 
				1
		END)",
			valTpDato($valCadBusq[11], "campo"));
	}
	
	if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_as.numeroFactura LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR (CASE vw_cxc_as.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) LIKE %s
		OR IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
				(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
				WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
														WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																							WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
				, NULL) LIKE %s
		OR IF (vw_cxc_as.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) LIKE %s
		OR (CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
			END) LIKE %s
		OR vw_cxc_as.observacionFactura LIKE %s)",
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_cxc_as.*,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cc_antiguedad_saldo vw_cxc_as
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (vw_cxc_as.numeroPedido = ped_vent.id_pedido_venta AND vw_cxc_as.idDepartamentoOrigenFactura = 0)
		LEFT JOIN sa_orden orden ON (vw_cxc_as.numeroPedido = orden.id_orden AND vw_cxc_as.idDepartamentoOrigenFactura = 1)
		LEFT JOIN an_pedido an_ped_vent ON (vw_cxc_as.numeroPedido = an_ped_vent.id_pedido AND vw_cxc_as.idDepartamentoOrigenFactura = 2)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $groupBy);
	
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
	if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"11\"></td>";
			$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "1%", $pageNum, "idCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			} else {
				$htmlTh .= "<td width=\"1%\"></td>";
			}
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "LPAD(CONVERT(numeroFactura, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "LPAD(CONVERT(numero_pedido, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido / Orden");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
			$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
			$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
			$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
			$htmlTh .= "<td width=\"7%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
		$htmlTh .= "</tr>";
	} else {
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"".(($valCadBusq[4] == 1) ? 6 : 5)."\"></td>";
			$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			$htmlTh .= ($valCadBusq[4] == 1) ? ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa") : "";
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "idCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "11%", $pageNum, "SUM(saldoFactura)", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "11%", $pageNum, "SUM(saldoFactura)", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
			$htmlTh .= "<td width=\"8%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
		$htmlTh .= "</tr>";
	}
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$totalSaldoCliente = 0;
		$totalCorrienteCliente = 0;
		$totalEntre1Cliente = 0;
		$totalEntre2Cliente = 0;
		$totalEntre3Cliente = 0;
		$totalMasDeCliente = 0;
		
		$sqlBusq2 = "";
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			if ($valCadBusq[4] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s)",
					valTpDato($row['id_empresa'], "int"));
			} else {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = vw_cxc_as.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
			}
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.id_empresa = %s)",
				valTpDato($row['id_empresa'], "int"));
		}
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxc_as.idCliente = %s",
			valTpDato($row['idCliente'], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxc_as.fechaRegistroFactura <= %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
		if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					(((SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
						WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
							AND cxc_ant.estatus IN (1)) = 1
						AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
					
					OR
					
					(SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
					WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
						AND cxc_ant.montoNetoAnticipo > cxc_ant.totalPagadoAnticipo
						AND cxc_ant.estadoAnticipo IN (4)
						AND cxc_ant.estatus IN (1)) = 1)
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					((SELECT cxc_ch.estatus FROM cj_cc_cheque cxc_ch
						WHERE cxc_ch.id_cheque = vw_cxc_as.idFactura
							AND cxc_ch.estatus IN (1)) = 1
						AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					((SELECT cxc_tb.estatus FROM cj_cc_transferencia cxc_tb
						WHERE cxc_tb.id_transferencia = vw_cxc_as.idFactura
							AND cxc_tb.estatus IN (1)) = 1
						AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
				ELSE
					ROUND(vw_cxc_as.saldoFactura, 2) > 0
			END))");
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("((CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(CASE
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
							(vw_cxc_as.montoTotal
								- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
										WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
											AND cxc_pago.fechaPago <= %s
											AND (cxc_pago.estatus IN (1)
												OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
							(vw_cxc_as.montoTotal
								- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
										WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
											AND cxc_pago.fechaPago <= %s
											AND (cxc_pago.estatus IN (1)
												OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					END)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
						- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (8)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago IN (2)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (2)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					(vw_cxc_as.montoTotal
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.formaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago IN (4)
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (4)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
			END) > 0
				AND NOT ((CASE
						WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
							(CASE
								WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
									(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
								WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
									(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
							END)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
							(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) 
							
						WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago = 7
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
							WHERE q.numeroDocumento = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago = 8
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_forma_pago IN (8)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
							WHERE q.numeroDocumento = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_forma_pago IN (2)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
							WHERE q.id_cheque = vw_cxc_as.idFactura)
							
						WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
							(SELECT MAX(q.fechaPago)
							FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM an_pagos cxc_pago
									WHERE cxc_pago.formaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
									WHERE cxc_pago.idFormaPago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
									UNION
									
									SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
									WHERE cxc_pago.id_forma_pago IN (4)
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
							WHERE q.id_transferencia = vw_cxc_as.idFactura)
					END) < %s
						AND ((vw_cxc_as.tipoDocumento IN ('FA','ND') AND vw_cxc_as.estadoFactura IN (1))
							OR (vw_cxc_as.tipoDocumento IN ('AN','NC') AND vw_cxc_as.estadoFactura IN (3)))))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
			
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxc_as.idDepartamentoOrigenFactura IN (%s)",
				valTpDato($valCadBusq[5], "campo"));
		}
		
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxc_as.tipoDocumento IN (%s)",
				valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
		}
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$arrayDiasVencidos = NULL;
			if (in_array("corriente",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde1",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde2",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde3",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("masDe",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																									WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			IF (vw_cxc_as.tipoDocumento IN ('AN'),
				(SELECT cxc_pago.id_concepto
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
					AND cxc_pago.id_forma_pago IN (11))
				, NULL) IN (%s)",
				valTpDato($valCadBusq[8], "campo"));
		}
		
		if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(SELECT motivo.id_motivo
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura
						AND motivo.id_motivo IN (%s))
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT motivo.id_motivo
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura
						AND motivo.id_motivo IN (%s))
			END) IN (%s)",
				valTpDato($valCadBusq[9], "campo"),
				valTpDato($valCadBusq[9], "campo"),
				valTpDato($valCadBusq[9], "campo"));
		}
		
		if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(SELECT uni_fis.id_condicion_unidad
					FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					WHERE cxc_fact_det_vehic.id_factura = vw_cxc_as.idFactura)
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT uni_fis.id_condicion_unidad
					FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					WHERE cxc_nc_det_vehic.id_nota_credito = vw_cxc_as.idFactura)
			END) IN (%s)",
				valTpDato($valCadBusq[10], "campo"));
		}
		
		if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (5)) THEN
					(CASE
						WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
							(SELECT COUNT(ped_financ.id_tipo) FROM fi_pedido ped_financ
							WHERE ped_financ.id_notadecargo_cxc = vw_cxc_as.idFactura
								AND ped_financ.id_tipo IN (%s)) > 0
						ELSE
							1
					END)
				ELSE 
					1
			END)",
				valTpDato($valCadBusq[11], "campo"));
		}
		
		if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxc_as.numeroFactura LIKE %s
			OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
			OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
			OR (CASE vw_cxc_as.idDepartamentoOrigenFactura
				WHEN 0 THEN		ped_vent.id_pedido_venta_propio
				WHEN 1 THEN		orden.numero_orden
				WHEN 2 THEN		an_ped_vent.numeracion_pedido
				ELSE			NULL
			END) LIKE %s
			OR IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
					(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
					WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																								WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
					, NULL) LIKE %s
			OR IF (vw_cxc_as.tipoDocumento IN ('AN'),
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
					AND cxc_pago.id_forma_pago IN (11))
				, NULL) LIKE %s
			OR (CASE
					WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
						(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
						FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
							INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
						WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
					WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
						(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
						FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
							INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
						WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
				END) LIKE %s
			OR vw_cxc_as.observacionFactura LIKE %s)",
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"),
				valTpDato("%".$valCadBusq[12]."%", "text"));
		}
		
		$queryEstado = sprintf("SELECT
			vw_cxc_as.*,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			
			(CASE vw_cxc_as.idDepartamentoOrigenFactura
				WHEN 0 THEN		ped_vent.id_pedido_venta_propio
				WHEN 1 THEN		orden.numero_orden
				WHEN 2 THEN		an_ped_vent.numeracion_pedido
				ELSE			NULL
			END) AS numero_pedido,
			
			IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
				(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
				WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
														WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																							WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
				, NULL) AS numero_siniestro,
			
			IF (vw_cxc_as.tipoDocumento IN ('AN'),
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
					AND cxc_pago.id_forma_pago IN (11))
				, NULL) AS descripcion_concepto_forma_pago,
			
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
			END) AS descripcion_motivo,
			
			(CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
					(CASE
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
							IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
							IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					END)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 7
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.formaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
									AND cxc_pago.idFormaPago = 8
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
						+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
									AND cxc_pago.id_forma_pago IN (8)
									AND cxc_pago.fechaPagoAnticipo <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (2)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					
				WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
					IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (4)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
			END) AS total_pagos,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cc_antiguedad_saldo vw_cxc_as
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
			LEFT JOIN iv_pedido_venta ped_vent ON (vw_cxc_as.numeroPedido = ped_vent.id_pedido_venta AND vw_cxc_as.idDepartamentoOrigenFactura = 0)
			LEFT JOIN sa_orden orden ON (vw_cxc_as.numeroPedido = orden.id_orden AND vw_cxc_as.idDepartamentoOrigenFactura = 1)
			LEFT JOIN an_pedido an_ped_vent ON (vw_cxc_as.numeroPedido = an_ped_vent.id_pedido AND vw_cxc_as.idDepartamentoOrigenFactura = 2)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			$sqlBusq2);
		$rsEstado = mysql_query($queryEstado);
		if (!$rsEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsEstado = mysql_num_rows($rsEstado);
		
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= ($contFila > 1) ? "<tr height=\"24\"><td>&nbsp;</td></tr>" : "";
			
			$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px tituloCampo\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".$row['idCliente']."</td>";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "</tr>";
			
			$contFila2 = 0;
		}
		
		while ($rowEstado = mysql_fetch_array($rsEstado)) {
			$totalSaldo = 0;
			$totalCorriente = 0;
			$totalEntre1 = 0;
			$totalEntre2 = 0;
			$totalEntre3 = 0;
			$totalMasDe = 0;
			
			$fecha1 = strtotime($valCadBusq[2]);
			$fecha2 = strtotime($rowEstado['fechaVencimientoFactura']);
			
			$dias = ($rowEstado['fechaVencimientoFactura'] != "") ? ($fecha1 - $fecha2) / 86400 : "";
			
			switch($rowEstado['idDepartamentoOrigenFactura']) {
				case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
				case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
				case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
				case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
				case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
				case 5 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"".utf8_encode("Financiamiento")."\"/>"; break;
				default : $imgPedidoModulo = $rowEstado['idDepartamentoOrigenFactura'];
			}
			
			if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalSaldo += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
			} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalSaldo -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
			}
			
			if ($dias < $rowGrupoEstado['desde1']){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalCorriente += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalCorriente -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde1']) && ($dias <= $rowGrupoEstado['hasta1'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre1 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre1 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde2']) && ($dias <= $rowGrupoEstado['hasta2'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre2 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre2 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde3']) && ($dias <= $rowGrupoEstado['hasta3'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre3 += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre3 -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			} else {
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalMasDe += $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalMasDe -= $rowEstado['montoTotal'] - $rowEstado['total_pagos'];
				}
			}
			
			if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Cliente, 4 = General por Dcto.
				$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila2++;
				
				$rowspan = (strlen($rowEstado['descripcion_motivo']) > 0 || strlen($rowEstado['observacionFactura']) > 0 || strlen($rowEstado['motivo_anulacion']) > 0) ? "rowspan=\"2\"" : "";
				
				$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila2) + (($pageNum) * $maxRows))."</td>";
					$htmlTb .= "<td ".$rowspan.">"."</td>";
					$htmlTb .= "<td>".utf8_encode($rowEstado['nombre_empresa'])."</td>";
					$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($rowEstado['fechaRegistroFactura']))."</td>";
					$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">";
						$htmlTb .= (($rowEstado['fechaVencimientoFactura'] != "") ? date(spanDateFormat, strtotime($rowEstado['fechaVencimientoFactura'])) : "-");
						$htmlTb .= (($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "");
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$rowEstado['idEstadoCuenta']."\">".utf8_encode($rowEstado['tipoDocumento']).(($rowEstado['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
					$htmlTb .= "<td>";
						$objDcto = new Documento;
						$objDcto->raizDir = $raiz;
						$objDcto->tipoMovimiento = (in_array($rowEstado['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
						$objDcto->tipoDocumento = $rowEstado['tipoDocumento'];
						$objDcto->tipoDocumentoMovimiento = (in_array($rowEstado['tipoDocumento'],array("NC"))) ? 2 : 1;
						$objDcto->idModulo = $rowEstado['idDepartamentoOrigenFactura'];
						$objDcto->idDocumento = $rowEstado['idFactura'];
						$aVerDcto = $objDcto->verDocumento();
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
							$htmlTb .= "<td>".$imgPedidoModulo."</td>";
							$htmlTb .= "<td width=\"100%\">".utf8_encode($rowEstado['numeroFactura'])."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"right\">";
						$objDcto = new Documento;
						$objDcto->raizDir = $raiz;
						$objDcto->tipoMovimiento = (in_array($rowEstado['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
						$objDcto->tipoDocumento = "PD";
						$objDcto->tipoDocumentoMovimiento = (in_array($rowEstado['tipoDocumento'],array("NC"))) ? 2 : 1;
						$objDcto->idModulo = $rowEstado['idDepartamentoOrigenFactura'];
						$objDcto->idDocumento = $rowEstado['numeroPedido'];
						$objDcto->mostrarDocumento = "verPDF";
						$aVerDcto = $objDcto->verPedido();
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
							$htmlTb .= "<td width=\"100%\">".utf8_encode($rowEstado['numero_pedido'])."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td>";
						$htmlTb .= "<div>".utf8_encode($rowEstado['id_cliente'].".- ".$rowEstado['nombre_cliente'])."</div>";
						$htmlTb .= (($rowEstado['numero_siniestro']) ? "<div align=\"right\">NRO. SINIESTRO: ".$rowEstado['numero_siniestro']."</div>" : "");
						$htmlTb .= ((strlen($rowEstado['descripcion_concepto_forma_pago']) > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($rowEstado['descripcion_concepto_forma_pago'])."</div>" : "");
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalSaldo, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalCorriente, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalEntre1, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalEntre2, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalEntre3, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalMasDe, 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				
				if (strlen($rowspan) > 0) {
					$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
						$htmlTb .= "<td colspan=\"7\">";
							$htmlTb .= (strlen($rowEstado['descripcion_motivo']) > 0) ? "<div class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($rowEstado['descripcion_motivo'])."</div>" : "";
							$htmlTb .= ((strlen($rowEstado['observacionFactura']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($rowEstado['observacionFactura'])."</div>" : "");
							$htmlTb .= ((strlen($rowEstado['motivo_anulacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px textoRojoNegrita\" title=\"Motivo de la Anulación\">".utf8_encode($rowEstado['motivo_anulacion'])."</div>" : "");
						$htmlTb .= "</td>";
					$htmlTb .= "</tr>";
				}
			}
			
			$totalSaldoCliente += $totalSaldo;
			$totalCorrienteCliente += $totalCorriente;
			$totalEntre1Cliente += $totalEntre1;
			$totalEntre2Cliente += $totalEntre2;
			$totalEntre3Cliente += $totalEntre3;
			$totalMasDeCliente += $totalMasDe;
		}
		
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"9\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente']).":</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalSaldoCliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalCorrienteCliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre1Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre2Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre3Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalMasDeCliente, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Cliente, 4 = General por Dcto.
		} else {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= ($valCadBusq[4] == 1) ? "<td>".utf8_encode($row['nombre_empresa'])."</td>" : "";
				$htmlTb .= "<td align=\"right\">".$row['idCliente']."</td>";
				$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalSaldoCliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalCorrienteCliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre1Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre2Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre3Cliente, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalMasDeCliente, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal['total_saldo'] += $totalSaldoCliente;
		$arrayTotal['total_corriente'] += $totalCorrienteCliente;
		$arrayTotal['total_entre1'] += $totalEntre1Cliente;
		$arrayTotal['total_entre2'] += $totalEntre2Cliente;
		$arrayTotal['total_entre3'] += $totalEntre3Cliente;
		$arrayTotal['total_mas_de'] += $totalMasDeCliente;
	}
	if ($contFila > 0) {
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_saldo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_corriente'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre1'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre2'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre3'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_mas_de'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Cliente, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_saldo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_corriente'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre1'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre2'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre3'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_mas_de'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"".(($valCadBusq[4] == 1) ? 4 : 3)."\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_saldo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_corriente'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre1'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre2'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_entre3'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['total_mas_de'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
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
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaECIndividual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$queryGrupoEstado = "SELECT * FROM gruposestadocuenta";
	$rsGrupoEstado = mysql_query($queryGrupoEstado);
	if (!$rsGrupoEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_as.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxc_as.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.idCliente = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxc_as.fechaRegistroFactura <= %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	
	if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				(((SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
					WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
						AND cxc_ant.estatus IN (1)) = 1
					AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
				
				OR
				
				(SELECT cxc_ant.estatus FROM cj_cc_anticipo cxc_ant
				WHERE cxc_ant.idAnticipo = vw_cxc_as.idFactura
					AND cxc_ant.montoNetoAnticipo > cxc_ant.totalPagadoAnticipo
					AND cxc_ant.estadoAnticipo IN (4)
					AND cxc_ant.estatus IN (1)) = 1)
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				((SELECT cxc_ch.estatus FROM cj_cc_cheque cxc_ch
					WHERE cxc_ch.id_cheque = vw_cxc_as.idFactura
						AND cxc_ch.estatus IN (1)) = 1
					AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				((SELECT cxc_tb.estatus FROM cj_cc_transferencia cxc_tb
					WHERE cxc_tb.id_transferencia = vw_cxc_as.idFactura
						AND cxc_tb.estatus IN (1)) = 1
					AND ROUND(vw_cxc_as.saldoFactura, 2) > 0)
			ELSE
				ROUND(vw_cxc_as.saldoFactura, 2) > 0
		END))");
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(CASE
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
						(vw_cxc_as.montoTotal
							- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
						(vw_cxc_as.montoTotal
							- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
									WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
										AND cxc_pago.fechaPago <= %s
										AND (cxc_pago.estatus IN (1)
											OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				END)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 7
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.formaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago = 8
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (8)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (2)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (2)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
				
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				(vw_cxc_as.montoTotal
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.formaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.idFormaPago IN (4)
								AND cxc_pago.fechaPago <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					- IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
							WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
								AND cxc_pago.id_forma_pago IN (4)
								AND cxc_pago.fechaPagoAnticipo <= %s
								AND (cxc_pago.estatus IN (1)
									OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0))
		END) > 0
			AND NOT ((CASE
					WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
						(CASE
							WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
								(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
							WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
								(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
						END)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
						(SELECT MAX(cxc_pago.fechaPago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s)))
						
					WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago = 7
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
						WHERE q.numeroDocumento = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago = 8
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (8)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
						WHERE q.numeroDocumento = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_cheque FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_cheque FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (2)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
						WHERE q.id_cheque = vw_cxc_as.idFactura)
						
					WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
						(SELECT MAX(q.fechaPago)
						FROM (SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM an_pagos cxc_pago
								WHERE cxc_pago.formaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPago, cxc_pago.id_transferencia FROM cj_det_nota_cargo cxc_pago
								WHERE cxc_pago.idFormaPago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))
								UNION
								
								SELECT cxc_pago.fechaPagoAnticipo, cxc_pago.id_transferencia FROM cj_cc_detalleanticipo cxc_pago
								WHERE cxc_pago.id_forma_pago IN (4)
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))) AS q
						WHERE q.id_transferencia = vw_cxc_as.idFactura)
				END) < %s
					AND ((vw_cxc_as.tipoDocumento IN ('FA','ND') AND vw_cxc_as.estadoFactura IN (1))
						OR (vw_cxc_as.tipoDocumento IN ('AN','NC') AND vw_cxc_as.estadoFactura IN (3)))))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxc_as.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde1",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde2",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde3",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("masDe",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxc_as.fechaVencimientoFactura) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		IF (vw_cxc_as.tipoDocumento IN ('AN'),
			(SELECT cxc_pago.id_concepto
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) IN (%s)",
			valTpDato($valCadBusq[8], "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(SELECT motivo.id_motivo
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura
					AND motivo.id_motivo IN (%s))
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(SELECT motivo.id_motivo
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura
					AND motivo.id_motivo IN (%s))
		END) IN (%s)",
			valTpDato($valCadBusq[9], "campo"),
			valTpDato($valCadBusq[9], "campo"),
			valTpDato($valCadBusq[9], "campo"));
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(SELECT uni_fis.id_condicion_unidad
				FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE cxc_fact_det_vehic.id_factura = vw_cxc_as.idFactura)
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(SELECT uni_fis.id_condicion_unidad
				FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
					INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				WHERE cxc_nc_det_vehic.id_nota_credito = vw_cxc_as.idFactura)
		END) IN (%s)",
			valTpDato($valCadBusq[10], "campo"));
	}
	
	if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (5)) THEN
				(CASE
					WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
						(SELECT COUNT(ped_financ.id_tipo) FROM fi_pedido ped_financ
						WHERE ped_financ.id_notadecargo_cxc = vw_cxc_as.idFactura
							AND ped_financ.id_tipo IN (%s)) > 0
					ELSE
						1
				END)
			ELSE 
				1
		END)",
			valTpDato($valCadBusq[11], "campo"));
	}
	
	if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_as.numeroFactura LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR (CASE vw_cxc_as.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) LIKE %s
		OR IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
				(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
				WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
														WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																							WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
				, NULL) LIKE %s
		OR IF (vw_cxc_as.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) LIKE %s
		OR (CASE
				WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
				WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
			END) LIKE %s
		OR vw_cxc_as.observacionFactura LIKE %s)",
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"),
			valTpDato("%".$valCadBusq[12]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_cxc_as.*,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		(CASE vw_cxc_as.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) AS numero_pedido,
		
		IF (vw_cxc_as.tipoDocumento IN ('FA') AND vw_cxc_as.idDepartamentoOrigenFactura IN (0),
			(SELECT pres_vent.numero_siniestro FROM iv_presupuesto_venta pres_vent
			WHERE pres_vent.id_presupuesto_venta = (SELECT ped_vent.id_presupuesto_venta FROM iv_pedido_venta ped_vent
													WHERE ped_vent.id_pedido_venta = (SELECT cxc_fact.numeroPedido FROM cj_cc_encabezadofactura cxc_fact
																						WHERE cxc_fact.idFactura = vw_cxc_as.idFactura))) 
			, NULL) AS numero_siniestro,
		
		IF (vw_cxc_as.tipoDocumento IN ('AN'),
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = vw_cxc_as.idFactura
				AND cxc_pago.id_forma_pago IN (11))
			, NULL) AS descripcion_concepto_forma_pago,
		
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = vw_cxc_as.idFactura)
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = vw_cxc_as.idFactura)
		END) AS descripcion_motivo,
		
		(CASE
			WHEN (vw_cxc_as.tipoDocumento IN ('FA')) THEN
				(CASE
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (0,1,3)) THEN
						IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
					WHEN (vw_cxc_as.idDepartamentoOrigenFactura IN (2,4,5)) THEN
						IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
								WHERE cxc_pago.id_factura = vw_cxc_as.idFactura
									AND cxc_pago.fechaPago <= %s
									AND (cxc_pago.estatus IN (1)
										OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				END)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('ND')) THEN
				IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.idNotaCargo = vw_cxc_as.idFactura
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('AN')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago = 7
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('NC')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.formaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.numeroDocumento = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago = 8
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.numeroControlDetalleAnticipo = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (8)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('CH')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago IN (2)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.id_cheque = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (2)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				
			WHEN (vw_cxc_as.tipoDocumento IN ('TB')) THEN
				IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.formaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.idFormaPago IN (4)
							AND cxc_pago.fechaPago <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
				+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
						WHERE cxc_pago.id_transferencia = vw_cxc_as.idFactura
							AND cxc_pago.id_forma_pago IN (4)
							AND cxc_pago.fechaPagoAnticipo <= %s
							AND (cxc_pago.estatus IN (1)
								OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado) AND DATE(cxc_pago.fecha_anulado) > %s))),0)
		END) AS total_pagos,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cc_antiguedad_saldo vw_cxc_as
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_as.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (vw_cxc_as.numeroPedido = ped_vent.id_pedido_venta AND vw_cxc_as.idDepartamentoOrigenFactura = 0)
		LEFT JOIN sa_orden orden ON (vw_cxc_as.numeroPedido = orden.id_orden AND vw_cxc_as.idDepartamentoOrigenFactura = 1)
		LEFT JOIN an_pedido an_ped_vent ON (vw_cxc_as.numeroPedido = an_ped_vent.id_pedido AND vw_cxc_as.idDepartamentoOrigenFactura = 2)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"11\"></td>";
		$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "4%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "4%", $pageNum, "LPAD(CONVERT(numeroFactura, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "4%", $pageNum, "LPAD(CONVERT(numero_pedido, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido / Orden");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "14%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
		$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
		$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
		$htmlTh .= "<td width=\"7%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
		$htmlTh .= "<td width=\"7%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$totalSaldo = 0;
		$totalCorriente = 0;
		$totalEntre1 = 0;
		$totalEntre2 = 0;
		$totalEntre3 = 0;
		$totalMasDe = 0;
		
		$fecha1 = strtotime($valCadBusq[2]);
		$fecha2 = strtotime($row['fechaVencimientoFactura']);
		
		$dias = ($row['fechaVencimientoFactura'] != "") ? ($fecha1 - $fecha2) / 86400 : "";
		
		switch($row['idDepartamentoOrigenFactura']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			case 5 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"".utf8_encode("Financiamiento")."\"/>"; break;
			default : $imgPedidoModulo = $row['idDepartamentoOrigenFactura'];
		}
		
		if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
			$totalSaldo += $row['montoTotal'] - $row['total_pagos'];
		} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
			$totalSaldo -= $row['montoTotal'] - $row['total_pagos'];
		}
		
		if ($dias < $rowGrupoEstado['desde1']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalCorriente += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalCorriente -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde1'] && $dias <= $rowGrupoEstado['hasta1']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre1 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre1 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde2'] && $dias <= $rowGrupoEstado['hasta2']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre2 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre2 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde3'] && $dias <= $rowGrupoEstado['hasta3']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre3 += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre3 -= $row['montoTotal'] - $row['total_pagos'];
			}
		} else {
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalMasDe += $row['montoTotal'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalMasDe -= $row['montoTotal'] - $row['total_pagos'];
			}
		}
		
		$rowspan = (strlen($row['descripcion_motivo']) > 0 || strlen($row['observacionFactura']) > 0 || strlen($row['motivo_anulacion']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td ".$rowspan.">"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">";
				$htmlTb .= date(spanDateFormat, strtotime($row['fechaVencimientoFactura']));
				$htmlTb .= (($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$row['idEstadoCuenta']."\">".utf8_encode($row['tipoDocumento']).(($row['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
			$htmlTb .= "<td>";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = $row['tipoDocumento'];
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['idDepartamentoOrigenFactura'];
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
				$objDcto->idModulo = $row['idDepartamentoOrigenFactura'];
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
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalSaldo, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalCorriente, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalEntre1, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalEntre2, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalEntre3, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($totalMasDe, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\">";
					$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<div class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</div>" : "";
					$htmlTb .= ((strlen($row['observacionFactura']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionFactura'])."</div>" : "");
					$htmlTb .= ((strlen($row['motivo_anulacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px textoRojoNegrita\" title=\"Motivo de la Anulación\">".utf8_encode($row['motivo_anulacion'])."</div>" : "");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal['total_saldo'] += $totalSaldo;
		$arrayTotal['total_corriente'] += $totalCorriente;
		$arrayTotal['total_entre1'] += $totalEntre1;
		$arrayTotal['total_entre2'] += $totalEntre2;
		$arrayTotal['total_entre3'] += $totalEntre3;
		$arrayTotal['total_mas_de'] += $totalMasDe;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">Totales:</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_saldo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_corriente'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_entre1'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_entre2'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_entre3'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['total_mas_de'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
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
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"cargaCbxModulos");
$xajax->register(XAJAX_FUNCTION,"cargaCbxTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"cargaCbxTipoFinanciamiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstConceptoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivo");
$xajax->register(XAJAX_FUNCTION,"cargarDiasVencidos");
$xajax->register(XAJAX_FUNCTION,"exportarAntiguedadSaldo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaECGeneral");
$xajax->register(XAJAX_FUNCTION,"listaECIndividual");
?>