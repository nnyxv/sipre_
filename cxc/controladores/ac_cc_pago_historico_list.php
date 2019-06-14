<?php


function buscarPago($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstTipoDcto']),
		implode(",",$frmBuscar['lstModulo']),
		implode(",",$frmBuscar['lstFormaPago']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaPago(0, "CONCAT(query.fechapago, query.idPago)", "DESC", $valBusq));
		
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select multiple id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoDocumento($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM tipodedocumentos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select multiple id=\"lstTipoDcto\" name=\"lstTipoDcto\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idTipoDeDocumento'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idTipoDeDocumento']."\">".utf8_encode($row['descripcionTipoDeDocumento'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoDcto","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstFormaPago($idFormaPago = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$idFormaPago = (is_array($idFormaPago)) ? implode(",",$idFormaPago) : $idFormaPago;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia, 5 = Tarjeta de Credito, 6 = Tardeja de Debito, 7 = Anticipo, 8 = Nota de Crédito
	// 9 = Retencion, 10 = Retencion I.S.L.R., 11 = Otro
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idFormaPago NOT IN (7,8,9,10)");*/
	
	if ($idFormaPago != "-1" && $idFormaPago != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idFormaPago IN (%s)",
			valTpDato($idFormaPago, "campo"));
	}
	
	$query = sprintf("SELECT * FROM formapagos %s ORDER BY nombreFormaPago ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select multiple id=\"lstFormaPago\" name=\"lstFormaPago\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["idFormaPago"]."\">".$row["nombreFormaPago"]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstFormaPago","innerHTML",$html);
	
	return $objResponse;
}

function exportarPago($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstTipoDcto']),
		implode(",",$frmBuscar['lstModulo']),
		implode(",",$frmBuscar['lstFormaPago']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cc_pago_historico_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaPago($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (0,1,3)
	AND cxc_pago.estatus IN (1,2)");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2,4,5)
	AND cxc_pago.estatus IN (1,2)");
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("cxc_pago.estatus IN (1,2)");
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("cxc_pago.estatus IN (1,2)");
	
	$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
	$sqlBusq5 .= $cond.sprintf("cxc_ch.estatus IN (1,2)");
	
	$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
	$sqlBusq6 .= $cond.sprintf("cxc_tb.estatus IN (1,2)");

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(cxc_nd.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nd.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("(cxc_ant.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_ant.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("(cxc_ch.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_ch.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(cxc_tb.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_tb.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pago.fechapago BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxc_pago.fechapago BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_pago.fechapago BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_pago.fechaPagoAnticipo BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("cxc_ch.fecha_cheque BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("cxc_tb.fecha_transferencia BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}

	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("query.idTipoDeDocumento IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}

	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("cxc_ch.id_departamento IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("cxc_tb.id_departamento IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}

	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pago.formaPago IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxc_pago.formaPago IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxc_pago.idFormaPago ",
			valTpDato($valCadBusq[5], "campo"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxc_pago.id_forma_pago IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("2 IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("4 IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
		
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("query.idFormaPago IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s
		OR (CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) LIKE %s
		OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
				AND cxc_pago.id_forma_pago IN (11)) LIKE %s
		OR (SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idPago
				AND deposito_det.idTipoDocumento = 1
				AND deposito_det.idCaja = cxc_pago.idCaja
				AND deposito_det.anulada LIKE 'NO') LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s
		OR (CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) LIKE %s
		OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
				AND cxc_pago.id_forma_pago IN (11)) LIKE %s
		OR (SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idPago
				AND deposito_det.idTipoDocumento = 1
				AND deposito_det.idCaja = cxc_pago.idCaja
				AND deposito_det.anulada LIKE 'NO') LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
		
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("(cxc_nd.numeroNotaCargo LIKE %s
		OR cxc_nd.numeroControlNotaCargo LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s
		OR (CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) LIKE %s
		OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
				AND cxc_pago.id_forma_pago IN (11)) LIKE %s
		OR (SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.id_det_nota_cargo
				AND deposito_det.idTipoDocumento = 2
				AND deposito_det.idCaja = cxc_pago.idCaja
				AND deposito_det.anulada LIKE 'NO') LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
		
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("(cxc_ant.numeroAnticipo LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s
		OR (CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
				ELSE
					cxc_pago.numeroControlDetalleAnticipo
			END) LIKE %s
		OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = cxc_pago.numeroControlDetalleAnticipo
				AND cxc_pago.id_forma_pago IN (11)) LIKE %s
		OR (SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idDetalleAnticipo
				AND deposito_det.idTipoDocumento = 4
				AND deposito_det.idCaja = cxc_pago.idCaja
				AND deposito_det.anulada LIKE 'NO') LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
		
		$cond = (strlen($sqlBusq5) > 0) ? " AND " : " WHERE ";
		$sqlBusq5 .= $cond.sprintf("(cxc_ch.numero_cheque LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s
		OR (SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_ch.id_cheque
				AND deposito_det.idTipoDocumento = 5
				AND deposito_det.idCaja = cxc_ch.idCaja
				AND deposito_det.anulada LIKE 'NO') LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
		
		$cond = (strlen($sqlBusq6) > 0) ? " AND " : " WHERE ";
		$sqlBusq6 .= $cond.sprintf("(cxc_tb.numero_transferencia LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s
		OR (SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_tb.id_transferencia
				AND deposito_det.idTipoDocumento = 6
				AND deposito_det.idCaja = cxc_tb.idCaja
				AND deposito_det.anulada LIKE 'NO') LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
		
		$cond = (strlen($sqlBusq7) > 0) ? " AND " : " WHERE ";
		$sqlBusq7 .= $cond.sprintf("(query.numeroFactura LIKE %s
		OR query.numeroControl LIKE %s
		OR query.numero_documento LIKE %s
		OR query.ci_cliente LIKE %s
		OR query.nombre_cliente LIKE %s
		OR query.descripcion_concepto_forma_pago LIKE %s
		OR query.numero_deposito LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT query.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM (SELECT
			cxc_fact.idFactura AS id_documento_pagado,
			cxc_fact.id_empresa,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.numeroFactura,
			cxc_fact.numeroControl,
			cxc_fact.idCliente,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo_documento_pagado,
			cxc_fact.condicionDePago,
			cxc_fact.numeroPedido,
			1 AS idTipoDeDocumento,
			(SELECT tipo_dcto.abreviatura_tipo_documento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipo_documento_pagado,
			cxc_fact.saldoFactura,
			cxc_fact.montoTotalFactura,
			cxc_pago.idPago,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			NULL AS id_concepto,
			cxc_pago.montopagado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
					FROM cj_cc_detalleanticipo det_anticipo
						INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
						INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
						INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
					WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento
						AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
				ELSE
					NULL
			END) AS descripcion_concepto_forma_pago,
			
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechapago,
			
			(SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idPago
				AND deposito_det.idTipoDocumento = 1
				AND deposito_det.idCaja = cxc_pago.idCaja
				AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
			
			cxc_pago.idCaja,
			cxc_pago.id_apertura,
			cxc_pago.idCierre,
			cxc_pago.fecha_anulado,
			cxc_pago.estatus AS estatus_pago
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen ON (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino ON (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1) %s
		
		UNION
		
		SELECT
			cxc_fact.idFactura AS id_documento_pagado,
			cxc_fact.id_empresa,
			cxc_fact.fechaRegistroFactura,
			cxc_fact.numeroFactura,
			cxc_fact.numeroControl,
			cxc_fact.idCliente,
			cxc_fact.idDepartamentoOrigenFactura AS id_modulo_documento_pagado,
			cxc_fact.condicionDePago,
			cxc_fact.numeroPedido,
			1 AS idTipoDeDocumento,
			(SELECT tipo_dcto.abreviatura_tipo_documento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipo_documento_pagado,
			cxc_fact.saldoFactura,
			cxc_fact.montoTotalFactura,
			cxc_pago.idPago,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			NULL AS id_concepto,
			cxc_pago.montopagado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
					FROM cj_cc_detalleanticipo det_anticipo
						INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
						INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
						INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
					WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento
						AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
				ELSE
					NULL
			END) AS descripcion_concepto_forma_pago,
			
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechapago,
			
			(SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idPago
				AND deposito_det.idTipoDocumento = 1
				AND deposito_det.idCaja = cxc_pago.idCaja
				AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
			
			cxc_pago.idCaja,
			cxc_pago.id_apertura,
			cxc_pago.idCierre,
			cxc_pago.fecha_anulado,
			cxc_pago.estatus AS estatus_pago
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
			INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen ON (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino ON (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1) %s
		
		UNION
		
		SELECT 
			cxc_nd.idNotaCargo,
			cxc_nd.id_empresa,
			cxc_nd.fechaRegistroNotaCargo,
			cxc_nd.numeroNotaCargo,
			cxc_nd.numeroControlNotaCargo,
			cxc_nd.idCliente,
			cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo_documento_pagado,
			0 AS condicionDePago,
			NULL AS numeroPedido,
			2 AS idTipoDeDocumento,
			(SELECT tipo_dcto.abreviatura_tipo_documento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 2) AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 2) AS tipo_documento_pagado,
			cxc_nd.saldoNotaCargo,
			cxc_nd.montoTotalNotaCargo,
			cxc_pago.id_det_nota_cargo AS idPago,
					
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			NULL AS id_concepto,
			cxc_pago.monto_pago,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante AS nro_comprobante,
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
					FROM cj_cc_detalleanticipo det_anticipo
						INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
						INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
						INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
					WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento
						AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
				ELSE
					NULL
			END) AS descripcion_concepto_forma_pago,
			
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechapago,
			
			(SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.id_det_nota_cargo
				AND deposito_det.idTipoDocumento = 2
				AND deposito_det.idCaja = cxc_pago.idCaja
				AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
			
			cxc_pago.idCaja,
			cxc_pago.id_apertura,
			cxc_pago.idCierre,
			cxc_pago.fecha_anulado,
			cxc_pago.estatus AS estatus_pago
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
			INNER JOIN bancos banco_origen ON (cxc_pago.bancoOrigen = banco_origen.idBanco)
			INNER JOIN bancos banco_destino ON (cxc_pago.bancoDestino = banco_destino.idBanco)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND recibo.idTipoDeDocumento = 2) %s
		
		UNION
		
		SELECT
			cxc_ant.idAnticipo,
			cxc_ant.id_empresa,
			cxc_ant.fechaAnticipo,
			cxc_ant.numeroAnticipo,
			'-' AS numeroControl,
			cxc_ant.idCliente,
			cxc_ant.idDepartamento AS id_modulo_documento_pagado,
			0 AS condicionDePago,
			NULL AS numeroPedido,
			4 AS idTipoDeDocumento,
			(SELECT tipo_dcto.abreviatura_tipo_documento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 4) AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 4) AS tipo_documento_pagado,
			cxc_ant.saldoAnticipo,
			cxc_ant.montoNetoAnticipo,
			cxc_pago.idDetalleAnticipo AS idPago,
					
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
				ELSE
					cxc_pago.numeroControlDetalleAnticipo
			END) AS numero_documento,
			
			cxc_pago.id_concepto,
			cxc_pago.montoDetalleAnticipo,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
					FROM cj_cc_detalleanticipo det_anticipo
						INNER JOIN cj_cc_anticipo cxc_ant ON (det_anticipo.idAnticipo = cxc_ant.idAnticipo)
						INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (det_anticipo.id_concepto = concepto_forma_pago.id_concepto)
						INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
					WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo
						AND det_anticipo.tipoPagoDetalleAnticipo LIKE 'OT')
				ELSE
					concepto_forma_pago.descripcion
			END) AS descripcion_concepto_forma_pago,
			
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_pago.fechaPagoAnticipo,
			
			(SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_pago.idDetalleAnticipo
				AND deposito_det.idTipoDocumento = 4
				AND deposito_det.idCaja = cxc_pago.idCaja
				AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
			
			cxc_pago.idCaja,
			cxc_pago.id_apertura,
			cxc_pago.idCierre,
			cxc_pago.fecha_anulado,
			cxc_pago.estatus AS estatus_pago
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			INNER JOIN bancos banco_origen ON (cxc_pago.bancoClienteDetalleAnticipo = banco_origen.idBanco)
			INNER JOIN bancos banco_destino ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_destino.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion) %s
		
		UNION
		
		SELECT
			cxc_ch.id_cheque,
			cxc_ch.id_empresa,
			cxc_ch.fecha_cheque,
			cxc_ch.numero_cheque,
			'-' AS numeroControl,
			cxc_ch.id_cliente,
			cxc_ch.id_departamento AS id_modulo_documento_pagado,
			1 AS condicionDePago,
			NULL AS numeroPedido,
			5 AS idTipoDeDocumento,
			(SELECT tipo_dcto.abreviatura_tipo_documento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 5) AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 5) AS tipo_documento_pagado,
			cxc_ch.saldo_cheque,
			cxc_ch.monto_neto_cheque,
			cxc_ch.id_cheque AS idPago,
			'-' AS numero_documento,
			NULL AS id_concepto,
			cxc_ch.monto_neto_cheque,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			2 AS idFormaPago,
			(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 2) AS nombreFormaPago,
			NULL AS descripcion_concepto_forma_pago,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_ch.fecha_cheque,
			
			(SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_ch.id_cheque
				AND deposito_det.idTipoDocumento = 5
				AND deposito_det.idCaja = cxc_ch.idCaja
				AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
			
			cxc_ch.idCaja,
			cxc_ch.id_apertura,
			cxc_ch.idCierre,
			cxc_ch.fecha_anulado,
			cxc_ch.estatus AS estatus_pago
		FROM cj_cc_cheque cxc_ch
			INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
			INNER JOIN bancos banco_origen on (cxc_ch.id_banco_cliente = banco_origen.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_ch.id_cheque = recibo.idDocumento AND cxc_ch.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'CH') %s
		
		UNION
		
		SELECT
			cxc_tb.id_transferencia,
			cxc_tb.id_empresa,
			cxc_tb.fecha_transferencia,
			cxc_tb.numero_transferencia,
			'-' AS numeroControl,
			cxc_tb.id_cliente,
			cxc_tb.id_departamento AS id_modulo_documento_pagado,
			1 AS condicionDePago,
			NULL AS numeroPedido,
			6 AS idTipoDeDocumento,
			(SELECT tipo_dcto.abreviatura_tipo_documento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 6) AS tipoDocumento,
			(SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 6) AS tipo_documento_pagado,
			cxc_tb.saldo_transferencia,
			cxc_tb.monto_neto_transferencia,
			cxc_tb.id_transferencia AS idPago,
			'-' AS numero_documento,
			NULL AS id_concepto,
			cxc_tb.monto_neto_transferencia,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS nro_comprobante,
			4 AS idFormaPago,
			(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 4) AS nombreFormaPago,
			NULL AS descripcion_concepto_forma_pago,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cxc_tb.fecha_transferencia,
			
			(SELECT deposito_det.numeroDeposito
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito_det.idPagoRelacionadoConNroCheque = cxc_tb.id_transferencia
				AND deposito_det.idTipoDocumento = 6
				AND deposito_det.idCaja = cxc_tb.idCaja
				AND deposito_det.anulada LIKE 'NO') AS numero_deposito,
			
			cxc_tb.idCaja,
			cxc_tb.id_apertura,
			cxc_tb.idCierre,
			cxc_tb.fecha_anulado,
			cxc_tb.estatus AS estatus_pago
		FROM cj_cc_transferencia cxc_tb
			INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
			INNER JOIN bancos banco_origen on (cxc_tb.id_banco_cliente = banco_origen.idBanco)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_tb.id_transferencia = recibo.idDocumento AND cxc_tb.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'TB') %s
		) AS query
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (query.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq, $sqlBusq2, $sqlBusq3, $sqlBusq4, $sqlBusq5, $sqlBusq6, $sqlBusq7);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPago", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPago", "6%", $pageNum, "tipo_documento_pagado", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaPago", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaPago", "6%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto. Pagado");
		$htmlTh .= ordenarCampo("xajax_listaPago", "6%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaPago", "10%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPago", "6%", $pageNum, "condicionDePago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPago", "6%", $pageNum, "fechapago", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Pago");
		$htmlTh .= ordenarCampo("xajax_listaPago", "4%", $pageNum, "nro_comprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Recibo");
		$htmlTh .= ordenarCampo("xajax_listaPago", "8%", $pageNum, "nombreFormaPago", $campOrd, $tpOrd, $valBusq, $maxRows, "Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPago", "5%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Tranferencia / Cheque / Anticipo / Nota de Crédito");
		$htmlTh .= ordenarCampo("xajax_listaPago", "4%", $pageNum, "numero_deposito", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Planilla Depósito");
		$htmlTh .= ordenarCampo("xajax_listaPago", "5%", $pageNum, "montopagado", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Pagado");
		$htmlTh .= ordenarCampo("xajax_listaPago", "5%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaPago", "5%", $pageNum, "montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo_documento_pagado']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo_documento_pagado'];
		}
		
		$classPago = "";
		$estatusPago = "";
		if ($row['estatus_pago'] == NULL && $row['fechaPago'] == $row['fecha_anulado']){ // Null = Anulado, 1 = Activo, 2 = Pendiente
			$classPago = "divMsjError";
			$estatusPago = 'PAGO ANULADO';
		} else if ($row['estatus_pago'] == 2) {
			$classPago = "divMsjAlerta";
			$estatusPago = 'PAGO PENDIENTE';
		} else if (in_array($row['id_concepto'], array(6,7,8))) {
			$classPago = "divMsjAlerta";
		}
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
		$objDcto->tipoDocumento = $row['tipoDocumento'];
		$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $row['id_modulo_documento_pagado'];
		$objDcto->idDocumento = $row['id_documento_pagado'];
		$aVerDcto = $objDcto->verDocumento();
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoDocumento = $row['tipoDocumento'];
		$objDcto->idModulo = $row['id_modulo_documento_pagado'];
		$objDcto->idDocumento = $row['id_recibo_pago'];
		$aVerRecibo = $objDcto->verRecibo();
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".($row['tipo_documento_pagado'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$imgDctoModulo."</td>";
					$htmlTb .= "<td width=\"100%\">".$row['numeroFactura']."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicionDePago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fechapago']))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerRecibo."</td>";
					$htmlTb .= "<td width=\"100%\">".$row['nro_comprobante']."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td class=\"".$classPago."\">";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"center\">";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombreFormaPago'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<tr align=\"center\"><td><span class=\"textoNegrita_9px\">(".utf8_encode($row['descripcion_concepto_forma_pago']).")</span></td></tr>" : "";
				$htmlTb .= ((strlen($estatusPago) > 0) ? "<tr align=\"center\"><td><span class=\"textoNegritaCursiva_9px\">".$estatusPago."</span></td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_documento'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_deposito'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montopagado'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoTotalFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				switch ($row['idCaja']) {
					case 1 :
						if ($row['idCierre'] > 0) {
							$aVerDctoAux = sprintf("../caja_vh/cj_cierre_caja_historico.php?idApertura=%s&idCierre=%s&fecha=%s&idDcto=%s&idPago=%s",
								$row['id_apertura'], $row['idCierre'], $row['fechaPago'], $row['id_documento_pagado'], $row['idPago']);
						} else {
							$aVerDctoAux = sprintf("../caja_vh/cj_cierre_caja.php?idDcto=%s&idPago=%s",
								$row['id_documento_pagado'], $row['idPago']);
						}
						break;
					case 2 :
						if ($row['idCierre'] > 0) {
							$aVerDctoAux = sprintf("../caja_rs/cjrs_cierre_caja_historico.php?idApertura=%s&idCierre=%s&fecha=%s&idDcto=%s&idPago=%s",
								$row['id_apertura'], $row['idCierre'], $row['fechaPago'], $row['id_documento_pagado'], $row['idPago']);
						} else {
							$aVerDctoAux = sprintf("../caja_rs/cjrs_cierre_caja.php?idDcto=%s&idPago=%s",
								$row['id_documento_pagado'], $row['idPago']);
						}
						break;
					default : $aVerDctoAux = "";
				}
				$htmlTb .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/ico_examinar.png\" title=\"Portada de Caja\"/></a>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				switch ($row['idCaja']) {
					case 1 :
						if ($row['idCierre'] > 0) {
							$aVerDctoAux = sprintf("../caja_vh/cj_pagos_cargados_dia_historico.php?idApertura=%s&idCierre=%s&fecha=%s&idDcto=%s&idPago=%s",
								$row['id_apertura'], $row['idCierre'], $row['fechaPago'], $row['id_documento_pagado'], $row['idPago']);
						} else {
							$aVerDctoAux = sprintf("../caja_vh/cj_pagos_cargados_dia.php?idDcto=%s&idPago=%s",
								$row['id_documento_pagado'], $row['idPago']);
						}
						break;
					case 2 :
						if ($row['idCierre'] > 0) {
							$aVerDctoAux = sprintf("../caja_rs/cjrs_pagos_cargados_dia_historico.php?idApertura=%s&idCierre=%s&fecha=%s&idDcto=%s&idPago=%s",
								$row['id_apertura'], $row['idCierre'], $row['fechaPago'], $row['id_documento_pagado'], $row['idPago']);
						} else {
							$aVerDctoAux = sprintf("../caja_rs/cjrs_pagos_cargados_dia.php?idDcto=%s&idPago=%s",
								$row['id_documento_pagado'], $row['idPago']);
						}
						break;
					default : $aVerDctoAux = "";
				}
				$htmlTb .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/application_view_columns.png\" title=\"Recibos por Medio de Pago\"/></a>" : "";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[14] += $row['montopagado'];
		$arrayTotal[15] += $row['saldoFactura'];
		$arrayTotal[16] += $row['montoTotalFactura'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"5\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[14] += $row['montopagado'];
				$arrayTotalFinal[15] += $row['saldoFactura'];
				$arrayTotalFinal[16] += $row['montoTotalFactura'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"13\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"5\"></td>";
			$htmlTb .= "</tr>";
		}
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPago(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPago(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPago(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPago(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPago(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPago","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalFacturas += $row['montoTotalFactura'];
		$totalSaldo += $row['saldoFactura'];
		$totalPago += $row['montopagado'];
	}
	
	$objResponse->assign("spnTotalPagos","innerHTML",number_format($totalPago, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"cargaLstFormaPago");
$xajax->register(XAJAX_FUNCTION,"exportarPago");
$xajax->register(XAJAX_FUNCTION,"listaPago");
?>