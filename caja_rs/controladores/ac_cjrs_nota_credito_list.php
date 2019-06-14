<?php


function buscarNotaCredito($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstEmpleadoVendedor']) ? implode(",",$frmBuscar['lstEmpleadoVendedor']) : $frmBuscar['lstEmpleadoVendedor']),
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstEstadoNotaCredito']) ? implode(",",$frmBuscar['lstEstadoNotaCredito']) : $frmBuscar['lstEstadoNotaCredito']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaNotaCredito(0, "idNotaCredito", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
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
	$sqlBusq .= $cond.sprintf("motivo.id_motivo IN (SELECT cxc_nc_det_motivo.id_motivo
													FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo)");
	
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

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_notacredito nota_cred
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (nota_cred.id_empleado_vendedor = vw_pg_empleado.id_empleado)
	WHERE nota_cred.idDepartamentoNotaCredito IN (%s)
	ORDER BY nombre_empleado",
		valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstOrientacionPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("V" => "Vertical", "H" => "Horizontal");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirNotaCredito(xajax.getFormValues('frmBuscar'));\"";
	
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

function cargarPagina($idEmpresa){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	if ($rowConfig400['valor'] == 0) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"selectedOption(this.id,'".$idEmpresa."');\""));
	} else {
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa));
	}
	
	$objResponse->script("xajax_buscarNotaCredito(xajax.getFormValues('frmBuscar'));");
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	return $objResponse;
}

function exportarNotaCredito($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstEmpleadoVendedor']) ? implode(",",$frmBuscar['lstEmpleadoVendedor']) : $frmBuscar['lstEmpleadoVendedor']),
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstEstadoNotaCredito']) ? implode(",",$frmBuscar['lstEstadoNotaCredito']) : $frmBuscar['lstEstadoNotaCredito']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cjrs_nota_credito_historico_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirNotaCredito($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstEmpleadoVendedor']) ? implode(",",$frmBuscar['lstEmpleadoVendedor']) : $frmBuscar['lstEmpleadoVendedor']),
		(is_array($frmBuscar['lstAplicaLibro']) ? implode(",",$frmBuscar['lstAplicaLibro']) : $frmBuscar['lstAplicaLibro']),
		(is_array($frmBuscar['lstEstadoNotaCredito']) ? implode(",",$frmBuscar['lstEstadoNotaCredito']) : $frmBuscar['lstEstadoNotaCredito']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstMotivoBuscar']) ? implode(",",$frmBuscar['lstMotivoBuscar']) : $frmBuscar['lstMotivoBuscar']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjrs_nota_credito_historico_pdf.php?valBusq=%s&lstOrientacionPDF=%s',890,550)", $valBusq, $frmBuscar['lstOrientacionPDF']));
	
	$objResponse->assign("tdlstOrientacionPDF","innerHTML","");
	
	return $objResponse;
}

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.estatus_nota_credito IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (%s)",
		valTpDato($idModuloPpal, "campo"));
	
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
		$sqlBusq .= $cond.sprintf("cxc_nc.fechaNotaCredito BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.id_empleado_vendedor IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.aplicaLibros = %s",
			valTpDato($valCadBusq[4], "boolean"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.estadoNotaCredito IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(SELECT motivo.id_motivo
		FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito
			AND motivo.id_motivo IN (%s)) IN (%s)",
			valTpDato($valCadBusq[7], "campo"),
			valTpDato($valCadBusq[7], "campo"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
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
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_ec.idEstadoDeCuenta AS id_estado_cuenta,
		cxc_ec.tipoDocumentoN,
		cxc_ec.tipoDocumento,
		cxc_nc.idNotaCredito,
		cxc_nc.id_empresa,
		cxc_nc.fechaNotaCredito,
		cxc_nc.numeracion_nota_credito,
		cxc_nc.numeroControl,
		cxc_nc.idDepartamentoNotaCredito AS id_modulo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_nc.estadoNotaCredito,
		(CASE cxc_nc.estadoNotaCredito
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado No Asignado'
			WHEN 2 THEN 'Asignado Parcial'
			WHEN 3 THEN 'Asignado'
		END) AS descripcion_estado_nota_credito,
		cxc_nc.aplicaLibros,
		
		cxc_ec2.idEstadoDeCuenta AS id_estado_cuenta_factura,
		cxc_ec2.tipoDocumentoN AS tipo_documento_n_factura,
		cxc_ec2.tipoDocumento AS tipo_documento_factura,
		cxc_fact.idFactura,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl AS numero_control_factura,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo_factura,
		
		cxc_nc.subtotalNotaCredito,
		cxc_nc.subtotal_descuento,
		(IFNULL(cxc_nc.subtotalNotaCredito, 0)
			- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
		IFNULL((SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
				WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito), 0) AS total_impuestos,
		cxc_nc.montoNetoNotaCredito,
		cxc_nc.saldoNotaCredito,
		cxc_nc.observacionesNotaCredito,
		
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
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
		LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_nc.idNotaCredito = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'NC')
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
			LEFT JOIN cj_cc_estadocuenta cxc_ec2 ON (cxc_fact.idFactura = cxc_ec2.idDocumento AND cxc_ec2.tipoDocumento LIKE 'FA')
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "fechaNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "LPAD(CONVERT(numeracion_nota_credito, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de Crédito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "LPAD(CONVERT(numeroControl, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "LPAD(CONVERT(numeroFactura, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "16%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "descripcion_estado_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Nota de Crédito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "saldoNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Nota de Crédito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "montoNetoNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Nota de Crédito");
		$htmlTh .= "<td colspan=\"4\" class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)){
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
		
		switch($row['estadoNotaCredito']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
		
		$rowspan = (strlen($row['descripcion_motivo']) > 0 || strlen($row['observacionesNotaCredito']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\" ".$rowspan.">".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Nota de Crédito Nro: ".utf8_encode($row['numeracion_nota_credito']).". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fechaNotaCredito']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = $row['tipoDocumento'];
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['idNotaCredito'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td nowrap=\"nowrap\">".$imgDctoModulo."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeracion_nota_credito'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"center\">".((strtotime($row['fechaRegistroFactura'])) ? date(spanDateFormat, strtotime($row['fechaRegistroFactura'])) : "")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array($row['tipo_documento_factura'],array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = $row['tipo_documento_factura'];
				$objDcto->tipoDocumentoMovimiento = (in_array($row['tipo_documento_factura'],array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo_factura'];
				$objDcto->idDocumento = $row['idFactura'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroFactura'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicion_pago'] == 0) ? "divMsjAlerta" : "divMsjInfo")."\" ".$rowspan.">";
				$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class." ".$rowspan.">".$row['descripcion_estado_nota_credito']."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['saldoNotaCredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['montoNetoNotaCredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if ($row['saldoNotaCredito'] > 0) {
				if (in_array($row['id_modulo'], array(2,4,5))){ // 2 = Vehiculos, 4 = Alquiler, 5 = Financiamiento
					$htmlTb .= sprintf("<a href=\"cj_nota_credito_form.php?id=%s&vw=a\" target=\"_self\"><img src=\"../img/iconos/application_view_columns_add.png\" title=\"Pagar Dctos.\"/></a>",
						$row['idNotaCredito']);
				} else if (in_array($row['id_modulo'], array(0,1,3))){ // 0 = Repuestos, 1 = Servicios, 3 = Administración
					$htmlTb .= sprintf("<a href=\"cjrs_nota_credito_form.php?id=%s&vw=a\" target=\"_self\"><img src=\"../img/iconos/application_view_columns_add.png\" title=\"Pagar Dctos.\"/></a>",
						$row['idNotaCredito']);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			// MODIFICADO ERNESTO
			$sPar = "idobject=".$row['idNotaCredito'];
			if ($row['id_modulo'] == 0) {
				$sPar .= "&ct=02";
				$sPar .= "&dt=01";
				$sPar .= "&cc=04";
			} else if ($row['id_modulo'] == 1) {
				$sPar .= "&ct=02";
				$sPar .= "&dt=01";
				$sPar .= "&cc=03";
			} else if ($row['id_modulo'] == 2){
				$sPar .= "&ct=02";
				$sPar .= "&dt=01";
				$sPar .= "&cc=02";
			} else if ($row['id_modulo'] == 3){
				$sPar .= "&ct=02";
				$sPar .= "&dt=01";
				$sPar .= "&cc=01";
			}
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?".$sPar."', 960, 550);\"><img src=\"../img/iconos/new_window.png\" title=\"Movimiento Contable\"/></a>");
			// MODIFICADO ERNESTO
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"7\">";
					$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<div class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</div>" : "";
					$htmlTb .= (strlen($row['observacionesNotaCredito']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionesNotaCredito'])."</div>" : "";
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal['cant_items'] += $row['cant_items'];
		$arrayTotal['saldoNotaCredito'] += $row['saldoNotaCredito'];
		$arrayTotal['montoNetoNotaCredito'] += $row['montoNetoNotaCredito'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['saldoNotaCredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['montoNetoNotaCredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalFinal['cant_items'] += $arrayTotal['cant_items'];
		$arrayTotalFinal['saldoNotaCredito'] += $arrayTotal['saldoNotaCredito'];
		$arrayTotalFinal['montoNetoNotaCredito'] += $arrayTotal['montoNetoNotaCredito'];
		
		if ($pageNum == $totalPages) {
			if ($totalPages > 0) {
				$rs = mysql_query($query);
				$arrayTotalFinal = array();
				while ($row = mysql_fetch_assoc($rs)) {
					$arrayTotalFinal['cant_items'] += $row['cant_items'];
					$arrayTotalFinal['saldoNotaCredito'] += $row['saldoNotaCredito'];
					$arrayTotalFinal['montoNetoNotaCredito'] += $row['montoNetoNotaCredito'];
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['cant_items'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['saldoNotaCredito'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['montoNetoNotaCredito'], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"4\"></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
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
	
	$objResponse->assign("divListaNotaCreditoCxC","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_impuestos'];
		$totalNotasCredito += $row['montoNetoNotaCredito'];
		$totalSaldo += $row['saldoNotaCredito'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalNotasCredito","innerHTML",number_format($totalNotasCredito, 2, ".", ","));
	$objResponse->assign("spnSaldoNotasCredito","innerHTML",number_format($totalSaldo, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstOrientacionPDF");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"exportarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"imprimirNotaCredito");
$xajax->register(XAJAX_FUNCTION,"listaNotaCredito");

function validarAperturaCaja($idEmpresa, $fecha) {
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal." ape
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>