<?php


function buscarNotaDebito($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstEstadoNotaCargo']) ? implode(",",$frmBuscar['lstEstadoNotaCargo']) : $frmBuscar['lstEstadoNotaCargo']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaNotaDebito(0, "idNotaCargo", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMotivo($nombreObjeto, $moduloMotivo, $transaccionMotivo, $selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("motivo.id_motivo IN (SELECT cxc_nd_det_motivo.id_motivo
													FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo)");
	
	if ($moduloMotivo != "-1" && $moduloMotivo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("motivo.modulo LIKE %s",
			valTpDato($moduloMotivo, "text"));
	}
	
	if ($transaccionMotivo != "-1" && $transaccionMotivo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("motivo.ingreso_egreso LIKE %s",
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

function cargaLstOrientacionPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("V" => "Vertical", "H" => "Horizontal");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirNotaDebito(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstOrientacionPDF\" name=\"lstOrientacionPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstOrientacionPDF","innerHTML",$html);
	
	return $objResponse;
}

function editarNotaDebito($idNotaCargo){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_captura_nota_cargo_list","editar")) { return $objResponse; }
	
	$objResponse->script("window.location.href = 'cc_nota_debito_form.php?id=".$idNotaCargo."&acc=1'");
	
	return $objResponse;
}

function exportarNotaDebito($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstEstadoNotaCargo']) ? implode(",",$frmBuscar['lstEstadoNotaCargo']) : $frmBuscar['lstEstadoNotaCargo']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cc_nota_debito_historico_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirNotaDebito($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstEstadoNotaCargo']) ? implode(",",$frmBuscar['lstEstadoNotaCargo']) : $frmBuscar['lstEstadoNotaCargo']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cc_nota_debito_historico_pdf.php?valBusq=%s&lstOrientacionPDF=%s',890,550)", $valBusq, $frmBuscar['lstOrientacionPDF']));
	
	$objResponse->assign("tdlstOrientacionPDF","innerHTML","");
	
	return $objResponse;
}

function listaNotaDebito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
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
		$sqlBusq .= $cond.sprintf("cxc_nd.aplicaLibros = %s",
			valTpDato($valCadBusq[3], "boolean"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.estadoNotaCargo IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nd.idDepartamentoOrigenNotaCargo IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(SELECT motivo.id_motivo
		FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo
			AND motivo.id_motivo IN (%s)) IN (%s)",
			valTpDato($valCadBusq[6], "campo"),
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
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
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
		cxc_ec.tipoDocumentoN,
		cxc_ec.tipoDocumento,
		cxc_nd.idNotaCargo,
		cxc_nd.id_empresa,
		cxc_nd.fechaRegistroNotaCargo,
		cxc_nd.fechaVencimientoNotaCargo,
		cxc_nd.numeroNotaCargo,
		cxc_nd.numeroControlNotaCargo,
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
		cxc_nd.aplicaLibros,
		cxc_nd.subtotalNotaCargo,
		cxc_nd.descuentoNotaCargo,
		(IFNULL(cxc_nd.subtotalNotaCargo, 0)
			- IFNULL(cxc_nd.descuentoNotaCargo, 0)) AS total_neto,
		(IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
			+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)) AS total_impuestos,
		cxc_nd.montoTotalNotaCargo,
		cxc_nd.saldoNotaCargo,
		cxc_nd.observacionNotaCargo,
		
		(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo,
		
		uni_fis.id_unidad_fisica,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
		LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nd.idNotaCargo = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'ND')
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nd.id_unidad_fisica_bono = uni_fis.id_unidad_fisica)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nd.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "fechaRegistroNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "fechaVencimientoNotaCargo",$campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "LPAD(CONVERT(numeroNotaCargo, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "6%", $pageNum, "LPAD(CONVERT(numeroControlNotaCargo, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "32%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "descripcion_estado_nota_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "saldoNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Nota de Débito");
		$htmlTh .= ordenarCampo("xajax_listaNotaDebito", "8%", $pageNum, "montoTotalNotaCargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Nota de Débito");
		$htmlTh .= "<td colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
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
		
		$rowspan = (strlen($row['descripcion_motivo']) > 0 || strlen($row['observacionNotaCargo']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Nota de Débito Nro: ".utf8_encode($row['numeroNotaCargo']).". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fechaRegistroNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaVencimientoNotaCargo']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = $row['tipoDocumento'];
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['idNotaCargo'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td nowrap=\"nowrap\">".$imgDctoModulo."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroNotaCargo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroControlNotaCargo']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
				$htmlTb .= (strlen($row['serial_carroceria']) > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($row['serial_carroceria'])."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class." ".$rowspan.">".$row['descripcion_estado_nota_cargo']."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['saldoNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['montoTotalNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td ".$rowspan.">";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_editarNotaDebito(%s);\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/>",
					$row['idNotaCargo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if ($row['saldoNotaCargo'] < $row['montoTotalNotaCargo']) {
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = $row['tipoDocumento'];
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['idNotaCargo'];
				$objDcto->mostrarDocumento = "verReciboPDF";
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= $aVerDcto;
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			// MODIFICADO ERNESTO
			$sPar = "idobject=".$row['idNotaCargo'];
			if (in_array($row['id_modulo'], array(0,1))){ // RESPUESTOS O SERVICIOS
				$sPar .= "&ct=15";
				$sPar .= "&dt=08";
				$sPar .= "&cc=04";
			} else if ($row['id_modulo'] == 2){ // VEHICULOS
				$sPar .= "&ct=15";
				$sPar .= "&dt=08";
				$sPar .= "&cc=02";			
			}
			if (in_array($row['id_modulo'],array(0,1,2))){
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?".$sPar."', 960, 550);\"><img src=\"../img/iconos/new_window.png\" title=\"Movimiento Contable\"/></a>");
			}
			// MODIFICADO ERNESTO
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\">";
					$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<div class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</div>" : "";
					$htmlTb .= (strlen($row['observacionNotaCargo']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionNotaCargo'])."</div>" : "";
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal['saldoNotaCargo'] += $row['saldoNotaCargo'];
		$arrayTotal['montoTotalNotaCargo'] += $row['montoTotalNotaCargo'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['saldoNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['montoTotalNotaCargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"5\"></td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalFinal['saldoNotaCargo'] += $arrayTotal['saldoNotaCargo'];
		$arrayTotalFinal['montoTotalNotaCargo'] += $arrayTotal['montoTotalNotaCargo'];
		
		if ($pageNum == $totalPages) {
			if ($totalPages > 0) {
				$rs = mysql_query($query);
				$arrayTotalFinal = array();
				while ($row = mysql_fetch_assoc($rs)) {
					$arrayTotalFinal['saldoNotaCargo'] += $row['saldoNotaCargo'];
					$arrayTotalFinal['montoTotalNotaCargo'] += $row['montoTotalNotaCargo'];
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['saldoNotaCargo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['montoTotalNotaCargo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"5\"></td>";
			$htmlTb .= "</tr>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaNotaCargo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_impuestos'];
		$totaNotasCargo += $row['montoTotalNotaCargo'];
		$totalSaldo += $row['saldoNotaCargo'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalNotasCargo","innerHTML",number_format($totaNotasCargo, 2, ".", ","));
	$objResponse->assign("spnSaldoNotasCargo","innerHTML",number_format($totalSaldo, 2, ".", ","));
	
	return $objResponse;
}

function nuevaNotaDebito(){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_captura_nota_cargo_list",'insertar')) { return $objResponse; }
	
	$objResponse->script("window.location.href='cc_nota_debito_form.php?acc=0'");
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarNotaDebito");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstOrientacionPDF");
$xajax->register(XAJAX_FUNCTION,"editarNotaDebito");
$xajax->register(XAJAX_FUNCTION,"exportarNotaDebito");
$xajax->register(XAJAX_FUNCTION,"imprimirNotaDebito");
$xajax->register(XAJAX_FUNCTION,"listaNotaDebito");
$xajax->register(XAJAX_FUNCTION,"nuevaNotaDebito");
?>