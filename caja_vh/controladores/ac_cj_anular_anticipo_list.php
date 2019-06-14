<?php


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

function buscarAnticipo($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstTipoFecha']) ? implode(",",$frmBuscar['lstTipoFecha']) : $frmBuscar['lstTipoFecha']),
		(is_array($frmBuscar['lstEstatus']) ? implode(",",$frmBuscar['lstEstatus']) : $frmBuscar['lstEstatus']),
		(is_array($frmBuscar['lstEstadoAnticipo']) ? implode(",",$frmBuscar['lstEstadoAnticipo']) : $frmBuscar['lstEstadoAnticipo']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaAnticipo(0, "idAnticipo", "DESC", $valBusq));
	
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

function cargaLstTipoFecha($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$array = array(1 => "De Registro", 2 => "De Anulación");
	$totalRows = count($array);
	
	$html .= "<select id=\"lstTipoFecha\" name=\"lstTipoFecha\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoFecha","innerHTML", $html);
	
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
	
	$objResponse->script("xajax_buscarAnticipo(xajax.getFormValues('frmBuscar'));");
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	return $objResponse;
}

function eliminarAnticipo($frmAnticipo, $frmListaAnticipo) {
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if ((!xvalidaAcceso($objResponse,"cj_anular_anticipo_list","eliminar") && in_array($idCajaPpal, array(1)))
	|| (!xvalidaAcceso($objResponse,"cjrs_anular_anticipo_list","eliminar") && in_array($idCajaPpal, array(2)))) { return $objResponse; }
	
	$idAnticipo = $frmAnticipo['hddIdAnticipo'];
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL ANTICIPO
	$queryAnticipo = sprintf("SELECT *,
		IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, 0) AS saldoAnticipo,
		IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, NULL) AS estadoAnticipo,
		(CASE cxc_ant.estatus
			WHEN 1 THEN
				(CASE cxc_ant.estadoAnticipo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END)
			ELSE
				'Anulado'
		END) AS descripcion_estado_anticipo
	FROM cj_cc_anticipo cxc_ant WHERE idAnticipo = %s;",
		valTpDato($idAnticipo, "int"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
	
	$idEmpresa = $rowAnticipo['id_empresa'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
	$queryAperturaCaja = sprintf("SELECT *,
		(CASE ape.statusAperturaCaja
			WHEN 0 THEN 'CERRADA TOTALMENTE'
			WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
			WHEN 2 THEN 'CERRADA PARCIALMENTE'
			ELSE 'CERRADA TOTALMENTE'
		END) AS estatus_apertura_caja
	FROM ".$apertCajaPpal." ape
		INNER JOIN caja ON (ape.idCaja = caja.idCaja)
		LEFT JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
	WHERE caja.idCaja = %s
		AND ape.statusAperturaCaja IN (1,2)
		AND (ape.id_empresa = %s
			OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s));",
		valTpDato(spanDatePick, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsAperturaCaja = mysql_query($queryAperturaCaja);
	if (!$rsAperturaCaja) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
	
	// ACTUALIZO EL ESTATUS
	$updateSQL = sprintf("UPDATE cj_cc_anticipo SET 
		estatus = 0,
		fecha_anulado = %s,
		id_empleado_anulado = %s,
		motivo_anulacion = %s
	WHERE idAnticipo = %s;",
		valTpDato("NOW()", "campo"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($frmAnticipo['txtMotivoAnulacion'], "text"),
		valTpDato($idAnticipo, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if ($frmAnticipo['txtIdMotivo'] > 0) {
		$frmAjusteInventario = array(
			"txtIdEmpresa" => $rowAnticipo['id_empresa'],
			"txtIdCliente" => $rowAnticipo['idCliente'],
			"hddIdAnticipo" => $idAnticipo,
			"txtNumeroAnticipo" => $rowAnticipo['numeroAnticipo'],
			"txtIdMotivoCxC" => $frmAnticipo['txtIdMotivo'],
			"txtAcv" => $rowAnticipo['totalPagadoAnticipo']);
		
		$Result1 = guardarNotaCargoCxC($frmAjusteInventario);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->script($Result1[3]);
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"NOTA_CARGO_CXC");
			$idNotaCargoCxC = $Result1[1];
		}
	} else {
		// ELIMINO DE ESTADO DE CUENTA (Para que no aparezca en el estado de cuenta de CXC)
		$deleteSQL = sprintf("DELETE FROM cj_cc_estadocuenta
		WHERE tipoDocumento LIKE %s
			AND idDocumento = %s;",
			valTpDato("AN", "text"),
			valTpDato($idAnticipo, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// CONSULTO DETALLE DEL ANTICIPO
		$queryPago = sprintf("SELECT
			cxc_pago.idDetalleAnticipo AS idPago,
			cxc_ant.idAnticipo AS id_documento_pagado,
			cxc_ant.id_empresa,
			cxc_ant.idCliente,
			cxc_pago.id_forma_pago,
			cxc_pago.id_concepto,
			(CASE
				WHEN (id_forma_pago = 2) THEN
					IFNULL(cxc_pago.id_cheque, cxc_pago.numeroControlDetalleAnticipo)
				WHEN (id_forma_pago = 4) THEN
					IFNULL(cxc_pago.id_transferencia, cxc_pago.numeroControlDetalleAnticipo)
				ELSE 
					cxc_pago.numeroControlDetalleAnticipo
			END) AS id_documento_pago,
			cxc_pago.id_cheque,
			cxc_pago.id_transferencia,
			cxc_pago.idCaja,
			cxc_pago.montoDetalleAnticipo AS monto_pago,
			'cj_cc_detalleanticipo' AS tabla,
			'idDetalleAnticipo' AS campo_id_pago
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		WHERE cxc_ant.idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsPago = mysql_query($queryPago);
		if (!$rsPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowPago = mysql_fetch_assoc($rsPago)) {
			$idPago = $rowPago['idPago'];
			$tablaPago = $rowPago['tabla'];
			$campoIdPago = $rowPago['campo_id_pago'];
			
			// ANULA EL PAGO
			$udpateSQL = sprintf("UPDATE %s SET
				estatus = NULL,
				fecha_anulado = %s,
				id_empleado_anulado = %s
			WHERE %s = %s;",
				valTpDato($tablaPago, "campo"),
				valTpDato("NOW()", "campo"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($campoIdPago, "campo"),
				valTpDato($idPago, "int"));
			$Result1 = mysql_query($udpateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			switch ($rowPago['id_forma_pago']) {
				case 1 : // 1 = Efectivo
					$campo = "saldoEfectivo";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = $txtMonto;
					break;
				case 2 : // 2 = Cheque
					if ($rowPago['id_cheque'] > 0) {
						$campo = "";
						$txtMonto = 0;
						$txtMontoSaldoCaja = 0;
					} else {
						$campo = "saldoCheques";
						$txtMonto = $rowPago['monto_pago'];
						$txtMontoSaldoCaja = $txtMonto;
					} break;
				case 3 : // 3 = Deposito
					$campo = "saldoDepositos";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = $txtMonto;
					break;
				case 4 : // 4 = Transferencia
					if ($rowPago['id_transferencia'] > 0) {
						$campo = "";
						$txtMonto = 0;
						$txtMontoSaldoCaja = 0;
					} else {
						$campo = "saldoTransferencia";
						$txtMonto = $rowPago['monto_pago'];
						$txtMontoSaldoCaja = $txtMonto;
					}
					break;
				case 5 : // 5 = Tarjeta de Crédito
					$campo = "saldoTarjetaCredito";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = $txtMonto;
					break;
				case 6 : // 6 = Tarjeta de Debito
					$campo = "saldoTarjetaDebito";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = $txtMonto;
					break;
				case 7 : // 7 = Anticipo
					$campo = "saldoAnticipo";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = 0;
					break;
				case 8 : // 8 = Nota de Crédito
					$campo = "saldoNotaCredito";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = $txtMonto;
					break;
				case 9 : // 9 = Retencion
					$campo = "saldoRetencion";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = $txtMonto;
					break;
				case 10 : // 10 = Retencion ISLR
					$campo = "saldoRetencionISLR";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = $txtMonto;
					break;
				case 11 : // 11 = Otro
					$campo = "saldoOtro";
					$txtMonto = $rowPago['monto_pago'];
					$txtMontoSaldoCaja = (in_array($rowPago['id_concepto'], array(7,8,9))) ? 0 : $txtMonto;
					break;
			}
			
			// ACTUALIZA LOS SALDOS EN LA APERTURA (NO TOMA EN CUENTA 7 = Anticipo EN EL SALDO DE LA CAJA)
			if (strlen($campo) > 0) {
				$updateSQL = sprintf("UPDATE ".$apertCajaPpal." SET
					%s = %s - %s,
					saldoCaja = saldoCaja - %s
				WHERE id = %s;",
					$campo, $campo, valTpDato($txtMonto, "real_inglesa"),
					valTpDato($txtMontoSaldoCaja, "real_inglesa"),
					valTpDato($rowAperturaCaja['id'], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
			
			if ($rowPago['id_forma_pago'] == 2) { // 2 = Cheque
				$objDcto = new Documento;
				$Result1 = $objDcto->actualizarCheque($rowPago['id_documento_pago']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
			} else if ($rowPago['id_forma_pago'] == 3) { // 3 = Deposito
				if (in_array($tablaPago, array("an_pagos","sa_iv_pagos"))) {
					$deleteSQL = sprintf("DELETE FROM an_det_pagos_deposito_factura
					WHERE idPago = %s
						AND idCaja = %s;",
						valTpDato($idPago, "int"),
						valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				} else if (in_array($tablaPago, array("cj_det_nota_cargo"))) {
					$deleteSQL = sprintf("DELETE FROM cj_det_pagos_deposito_nota_cargo
					WHERE id_det_nota_cargo = %s
						AND idCaja = %s;",
						valTpDato($idPago, "int"),
						valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				} else if (in_array($tablaPago, array("cj_cc_detalleanticipo"))) {
					$deleteSQL = sprintf("DELETE FROM cj_cc_det_pagos_deposito_anticipos
					WHERE idDetalleAnticipo = %s
						AND idCaja = %s;",
						valTpDato($idPago, "int"),
						valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			} else if ($rowPago['id_forma_pago'] == 4) { // 4 = Transferencia
				$objDcto = new Documento;
				$Result1 = $objDcto->actualizarTransferencia($rowPago['id_documento_pago']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
			} else if (in_array($rowPago['id_forma_pago'], array(5,6))) { // 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito
				if (in_array($tablaPago, array("an_pagos","sa_iv_pagos","cj_det_nota_cargo","cj_cc_detalleanticipo"))) {
					$deleteSQL = sprintf("DELETE FROM cj_cc_retencion_punto_pago
					WHERE id_pago = %s
						AND id_caja = %s;",
						valTpDato($idPago, "int"),
						valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			} else if ($rowPago['id_forma_pago'] == 8) { // 8 = Nota de Crédito
				$objDcto = new Documento;
				$Result1 = $objDcto->actualizarNotaCredito($rowPago['id_documento_pago']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			} else if ($rowPago['id_forma_pago'] == 9) { // 9 = Retencion
				if (in_array($tablaPago, array("an_pagos","sa_iv_pagos"))) {
					$deleteSQL = sprintf("DELETE FROM cj_cc_retencioncabezera
					WHERE numeroComprobante = %s
						AND idCliente = %s;",
						valTpDato($rowPago['id_documento_pago'], "int"),
						valTpDato($rowPago['idCliente'], "int"));
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
		}
		
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarAnticipo($idAnticipo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Anticipo anulado exitosamente");
	
	$objResponse->script("byId('btnCancelarAnticipo').click();");
	
	$objResponse->loadCommands(listaAnticipo(
		$frmListaAnticipo['pageNum'],
		$frmListaAnticipo['campOrd'],
		$frmListaAnticipo['tpOrd'],
		$frmListaAnticipo['valBusq']));
	
	return $objResponse;
}

function formAnticipo($idAnticipo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddIdAnticipo","value",$idAnticipo);
	
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

function listaAnticipo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $idCajaPpal;
	global $idModuloPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
	$queryAperturaCaja = sprintf("SELECT *,
		(CASE ape.statusAperturaCaja
			WHEN 0 THEN 'CERRADA TOTALMENTE'
			WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
			WHEN 2 THEN 'CERRADA PARCIALMENTE'
			ELSE 'CERRADA TOTALMENTE'
		END) AS estatus_apertura_caja
	FROM ".$apertCajaPpal." ape
		INNER JOIN caja ON (ape.idCaja = caja.idCaja)
		LEFT JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
	WHERE caja.idCaja = %s
		AND ape.statusAperturaCaja IN (1,2)
		AND (ape.id_empresa = %s
			OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
									WHERE suc.id_empresa = %s));",
		valTpDato(spanDatePick, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	$rsAperturaCaja = mysql_query($queryAperturaCaja);
	if (!$rsAperturaCaja) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
	
	$fechaApertura = $rowAperturaCaja['fechaAperturaCaja'];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((cxc_ant.fechaAnticipo = %s AND cxc_ant.estadoAnticipo IN (0,1))
	OR (cxc_ant.estadoAnticipo IN (0) AND cxc_ant.totalPagadoAnticipo = 0)
	OR (cxc_ant.estadoAnticipo IN (1) AND 2 IN (SELECT cxc_pago.id_concepto FROM cj_cc_detalleanticipo cxc_pago
												WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo)))",
		valTpDato($fechaApertura, "date"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
		valTpDato($idModuloPpal, "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_ant.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_ant.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[3] == "2") {
			$sqlBusq .= $cond.sprintf("DATE(cxc_ant.fecha_anulado) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		} else {
			$sqlBusq .= $cond.sprintf("cxc_ant.fechaAnticipo BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_ant.estatus = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_ant.estadoAnticipo IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_ant.idDepartamento IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_ant.numeroAnticipo LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
				AND cxc_pago.id_forma_pago IN (11)) LIKE %s
		OR cxc_ant.observacionesAnticipo LIKE %s
		OR cxc_ant.motivo_anulacion LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_ant.idAnticipo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_ant.montoNetoAnticipo,
		cxc_ant.totalPagadoAnticipo,
		IF (cxc_ant.estatus = 1, cxc_ant.saldoAnticipo, 0) AS saldoAnticipo,
		cxc_ant.fechaAnticipo,
		cxc_ant.numeroAnticipo,
		cxc_ant.idDepartamento,
		IF (cxc_ant.estatus = 1, cxc_ant.estadoAnticipo, NULL) AS estadoAnticipo,
		(CASE cxc_ant.estatus
			WHEN 1 THEN
				(CASE cxc_ant.estadoAnticipo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END)
			ELSE
				'Anulado'
		END) AS descripcion_estado_anticipo,
		cxc_ant.observacionesAnticipo,
		
		cxc_ant.id_empleado_creador,
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		cxc_ant.fecha_anulado,
		cxc_ant.id_empleado_anulado,
		vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
		cxc_ant.motivo_anulacion,
		
		(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
		FROM cj_cc_detalleanticipo cxc_pago
			INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
			AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		cxc_ant.estatus
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_ant.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_ant.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "6%", $pageNum, "fechaAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro / Fecha de Anulación");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "8%", $pageNum, "numeroAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "36%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "estadoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "saldoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Anticipo");
		$htmlTh .= ordenarCampo("xajax_listaAnticipo", "10%", $pageNum, "montoNetoAnticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Anticipo");
		$htmlTh .= "<td colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['idDepartamento']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['idDepartamento'];
		}
		
		switch($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anticipo Anulado\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Anticipo Activo\"/>"; break;
			default : $imgEstatus = $row['estatus'];
		}
		
		switch($row['estadoAnticipo']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"center\" ".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Nro. Anticipo: ".$row['numeroAnticipo'].". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "")." width=\"100%\">".date(spanDateFormat, strtotime($row['fechaAnticipo']))."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['fecha_anulado']) > 0) ? "<tr><td align=\"center\" class=\"textoNegritaCursiva_9px textoRojoNegrita\" ".((strlen($row['nombre_empleado_anulado']) > 0) ? "title=\"Nro. Anticipo: ".$row['numeroAnticipo'].". Anulado por: ".utf8_encode($row['nombre_empleado_anulado'])."\"" : "").">".date(spanDateFormat, strtotime($row['fecha_anulado']))."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroAnticipo']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<div class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</div>" : "";
				$htmlTb .= (strlen($row['observacionesAnticipo']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionesAnticipo'])."</div>" : "";
				$htmlTb .= (strlen($row['motivo_anulacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px textoRojoNegrita\" title=\"Motivo de la Anulación\">".utf8_encode($row['motivo_anulacion'])."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($row['descripcion_estado_anticipo'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoAnticipo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td colspan=\"2\">".number_format($row['montoNetoAnticipo'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				if ($row['totalPagadoAnticipo'] != $row['montoNetoAnticipo'] && $row['totalPagadoAnticipo'] > 0) {
					$htmlTb .= "<tr align=\"right\" class=\"textoNegrita_9px\">";
						$htmlTb .= "<td>Pagado:</td>";
						$htmlTb .= "<td width=\"100%\">".number_format($row['totalPagadoAnticipo'], 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
				}
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (in_array($row['estatus'], array(1))) {
				if (in_array($row['idDepartamento'], array(2,4,5))){ // 2 = Vehiculos, 4 = Alquiler, 5 = Financiamiento
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDesbloquearAnticipo%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPermiso', 'cj_anular_anticipo');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>",
						$contFila);
				} else if (in_array($row['idDepartamento'], array(0,1,3))){ // 0 = Repuestos, 1 = Servicios, 3 = Administración
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDesbloquearAnticipo%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPermiso', 'cjrs_anular_anticipo');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>",
						$contFila);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (in_array($row['estatus'], array(1))) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAnularAnticipo%s\" rel=\"#divFlotante1\" style=\"display:none\" onclick=\"abrirDivFlotante1(this, 'tblAnticipo', '%s');\"><img src=\"../img/iconos/delete.png\" style=\"cursor:pointer\" title=\"Anular Anticipo\"/></a>",
					$contFila,
					$row['idAnticipo']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("AN",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "AN";
				$objDcto->tipoDocumentoMovimiento = (in_array("AN",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['idDepartamento'];
				$objDcto->idDocumento = $row['idAnticipo'];
				$objDcto->mostrarDocumento = "verDetalle";
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= $aVerDcto;
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("AN",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "AN";
				$objDcto->tipoDocumentoMovimiento = (in_array("AN",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['idDepartamento'];
				$objDcto->idDocumento = $row['idAnticipo'];
				$objDcto->mostrarDocumento = "verPDF";
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= $aVerDcto;
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[9] += $row['saldoAnticipo'];
		$arrayTotal[10] += $row['montoNetoAnticipo'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"5\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[9] += $row['saldoAnticipo'];
				$arrayTotalFinal[10] += $row['montoNetoAnticipo'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[9], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaAnticipo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalAnticipos += $row['montoNetoAnticipo'];
		$totalSaldo += $row['saldoAnticipo'];
	}
	
	$objResponse->assign("spnTotalAnticipos","innerHTML",number_format($totalAnticipos, 2, ".", ","));
	$objResponse->assign("spnSaldoAnticipos","innerHTML",number_format($totalSaldo, 2, ".", ","));
	
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
	
	$query = sprintf("SELECT * FROM pg_motivo %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "54%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "16%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transacción");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['modulo']) {
			case "CC" :
				$imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>";
				$descripcionModulo = "Cuentas por Cobrar";
				break;
			case "CP" :
				$imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>";
				$descripcionModulo = "Cuentas por Pagar";
				break;
			case "CJ" :
				$descripcionModulo = "Caja"; break;
			case "TE" :
				$imgPedidoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>";
				$descripcionModulo = "Tesoreria";
				break;
			default : $imgPedidoModulo = ""; $descripcionModulo = $row['modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($descripcionModulo)."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".(($row['ingreso_egreso'] == "I") ? "Ingreso" : "Egreso")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarPermiso($frmPermiso, $frmDatosArticulo) {
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPermiso = mysql_num_rows($rsPermiso);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($totalRowsPermiso > 0) {
		if (($frmPermiso['hddModulo'] == "cj_anular_anticipo" && in_array($idCajaPpal, array(1)))
		|| ($frmPermiso['hddModulo'] == "cjrs_anular_anticipo" && in_array($idCajaPpal, array(2)))) {
			for ($cont = 1; $cont <= 20; $cont++) {
				$objResponse->script("
				byId('aDesbloquearAnticipo".$cont."').style.display = 'none';
				byId('aAnularAnticipo".$cont."').style.display = '';");
			}
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
	return $objResponse;
}

function validarPermisoViejo($frmBuscar){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	mysql_query("START TRANSACTION;");
	
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($idUsuario, "int"),
		valTpDato($frmBuscar['txtContrasena'], "text"),
		valTpDato($frmBuscar['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		$arrayValores = explode("|",$frmBuscar['hddValores']);
		
		$idAnticipo = $arrayValores[0];
		
		// CONSULTO EL ANTICIPO
		$queryAnticipo = sprintf("SELECT * FROM cj_cc_anticipo
		WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAnticipo = mysql_fetch_assoc($rsAnticipo);
		
		$idEmpresa = $rowAnticipo['id_empresa'];
		
		// CONSULTO EL EMPLEADO
		$queryEmpleado = sprintf("SELECT id_empleado FROM pg_usuario
		WHERE id_usuario = %s;",
			valTpDato($idUsuario, "int"));
		$rsEmpleado = mysql_query($queryEmpleado);
		if (!$rsEmpleado) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
		
		$idEmpleado = $rowEmpleado['id_empleado'];
		
		// ACTUALIZO EL ESTATUS A: 0 = INACTIVO
		$updateSQL = sprintf("UPDATE cj_cc_anticipo SET 
			estatus = %s,
			fecha_anulado = NOW(),
			id_empleado_anulado = %s,
			motivo_anulacion = %s
		WHERE idAnticipo = %s;",
			valTpDato(0, "int"),
			valTpDato($idEmpleado, "int"),
			valTpDato($frmBuscar['txtMotivoAnulacion'], "text"),
			valTpDato($idAnticipo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ELIMINO DE ESTADO DE CUENTA (Para que no aparezca en el estado de cuenta de CXC)
		$sqlDeleteEstadoCuenta = sprintf("DELETE FROM cj_cc_estadocuenta
		WHERE tipoDocumento = %s
			AND idDocumento = %s
			AND fecha = '%s'
			AND tipoDocumentoN = %s",
			valTpDato('AN', "text"),
			valTpDato($idAnticipo, "int"),
			date("Y-m-d"),
			valTpDato(3, "int"));
		$rsDeleteEstadoCuenta = mysql_query($sqlDeleteEstadoCuenta);
		if (!$rsDeleteEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlDeleteEstadoCuenta);
		
		// CONSULTO DETALLE DEL ANTICIPO
		$queryDetAnticipo = sprintf("SELECT * FROM cj_cc_detalleanticipo
		WHERE idAnticipo = %s;",
			valTpDato($idAnticipo, "int"));
		$rsDetAnticipo = mysql_query($queryDetAnticipo);
		if (!$rsDetAnticipo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($rowDetAnticipo = mysql_fetch_assoc($rsDetAnticipo)) {
			
			$idDetalleAnticipo = $rowDetAnticipo['idDetalleAnticipo'];
			$formaPago = $rowDetAnticipo['tipoPagoDetalleAnticipo'];
			
			if ($formaPago == 'EF'){//1 EFECTIVO
				$campo = "saldoEfectivo";
			}else if ($formaPago == 'CH'){//2 CHEQUE
				$campo = "saldoCheques";
			}else if ($formaPago == 'DP'){//3 DEPOSITO
				$campo = "saldoDepositos";
			}else if ($formaPago == 'TB'){//4 TRANSFERENCIA BANCARIA
				$campo = "saldoTransferencia";
			}else if ($formaPago == 'TC'){//5 TARJETA DE CREDITO
				$campo = "saldoTarjetaCredito";
			}else if ($formaPago == 'TD'){//6 TARJETA DE DEBITO
				$campo = "saldoTarjetaDebito";
			}else if ($formaPago == 'OT'){//11 OTRO
				$campo = "saldoOtro";
			}
			
			// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
			$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato(1, "int")); // 1 = Empresa cabecera
			$rsConfig400 = mysql_query($queryConfig400);
			if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsConfig400 = mysql_num_rows($rsConfig400);
			$rowConfig400 = mysql_fetch_assoc($rsConfig400);
			
			if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
				$andEmpresa = sprintf(" AND id_empresa = %s",
					valTpDato($idEmpresa, "int"));
					
			} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
				$andEmpresa = '';
			}
		
			// CONSULTO LA CAJA PERTENECIENTE AL ANTICIPO
			$sqlSelectDatosAperturaCaja = sprintf("SELECT saldoCaja, id, %s FROM ".$apertCajaPpal."
			WHERE idCaja = %s
				AND statusAperturaCaja IN (1,2) %s",
				$campo,
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				$andEmpresa);
			$rsSelectDatosAperturaCaja = mysql_query($sqlSelectDatosAperturaCaja);
			if (!$rsSelectDatosAperturaCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectDatosAperturaCaja);
			$rowSelectDatosAperturaCaja = mysql_fetch_array($rsSelectDatosAperturaCaja);
			
			// ACTUALIZA LOS SALDOS EN LA APERTURA (NO TOMA EN CUENTA 7 = Anticipo EN EL SALDO DE LA CAJA)
			$sqlUpdateDatosAperturaCaja = sprintf("UPDATE ".$apertCajaPpal." SET
				%s = %s,
				saldoCaja = saldoCaja - %s
			WHERE id = %s",
				$campo,
				valTpDato($rowSelectDatosAperturaCaja[$campo] - $rowDetAnticipo['montoDetalleAnticipo'],"double"),
				valTpDato($rowDetAnticipo['montoDetalleAnticipo'],"double"),
				valTpDato($rowSelectDatosAperturaCaja['id'], "int"));
			$rsUpdateDatosAperturaCaja = mysql_query($sqlUpdateDatosAperturaCaja);
			if (!$rsUpdateDatosAperturaCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateDatosAperturaCaja);
			
			//NO ELIMINO DETALLE DE ANTICIPO PARA TENER UN HISTORICO
			
			// ELIMINO EL DETALLE DE LA FORMA DE PAGO SI:
			if ($formaPago == 'DP'){//DEPOSITO
				$sqlDeleteDepositoAnt = sprintf("DELETE FROM cj_cc_det_pagos_deposito_anticipos
				WHERE idDetalleAnticipo = %s
					AND idFormaPago = %s
					AND id_tipo_documento = %s
					AND idCaja = %s",
					valTpDato($idDetalleAnticipo, "int"),
					valTpDato(3, "int"), // 3 = DEPOSITO
					valTpDato(4, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
					valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				$rsDeleteDepositoAnt = mysql_query($sqlDeleteDepositoAnt);
				if (!$rsDeleteDepositoAnt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$DeleteDepositoAnt);
				
			}else if ($formaPago == 'TC' || $formaPago == 'TD'){//TARJETA DE CREDITO //TARJETA DE DEBITO
				
				// ELIMINO LA RETENCION GENERADA POR TARJETA DE DEBITO Y/O CREDITO
				$sqlDeleteEstadoCuenta = sprintf("DELETE FROM cj_cc_retencion_punto_pago
				WHERE id_caja = %s
					AND id_pago = %s
					AND id_tipo_documento = %s",
					valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					valTpDato($idDetalleAnticipo, "int"),
					valTpDato(4, "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
				$rsDeleteEstadoCuenta = mysql_query($sqlDeleteEstadoCuenta);
				if (!$rsDeleteEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."SQL: ".$sqlDeleteEstadoCuenta);
			}
		}
		
		$objResponse->alert("Anticipo anulado exitosamente.");
		
		$objResponse->script("byId('btnBuscar').click();
								byId('btnCancelarPermiso').click();");
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipo");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoFecha");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"eliminarAnticipo");
$xajax->register(XAJAX_FUNCTION,"formAnticipo");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"listaAnticipo");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function guardarNotaCargoCxC($frmAjusteInventario) {
	global $idCajaPpal;
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$idEmpresa = $frmAjusteInventario['txtIdEmpresa'];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE (emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
			OR emp_num.id_numeracion = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(24, "int"), // 24 = Nota Cargo CxC
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$numeroActualControl = $numeroActual;
	
	$idCliente = $frmAjusteInventario['txtIdCliente'];
	$idAnticipo = $frmAjusteInventario['hddIdAnticipo'];
	$idMotivo = $frmAjusteInventario['txtIdMotivoCxC'];
	$precioUnitario = (-1) * str_replace(",", "", $frmAjusteInventario['txtAcv']);
	$txtFechaRegistro = date(spanDateFormat);
	if (in_array($idCajaPpal, array(1))) {
		$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	} else if (in_array($idCajaPpal, array(2))) {
		$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	}
	$lstTipoPago = 0; // 0 = Credito, 1 = Contado
	$txtFechaVencimiento = ($lstTipoPago == 0) ? date(spanDateFormat, strtotime($txtFechaRegistro) + 2592000) : $txtFechaRegistro;
	$txtDiasCreditoCliente = (strtotime($txtFechaVencimiento) - strtotime($txtFechaRegistro)) / 86400;
	$txtSubTotalNotaCargo = $precioUnitario;
	$txtSubTotalDescuento = 0;
	$txtFlete = 0;
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	$txtTotalNotaCargo = $txtSubTotalNotaCargo;
	$txtMontoExento = $txtSubTotalNotaCargo;
	$txtMontoExonerado = 0;
	$txtObservacion = "NOTA DE CARGO PARA ANULACION DEL ANTICIPO NRO. ".$frmAjusteInventario['txtNumeroAnticipo'];
	
	// INSERTA LA NOTA DE DEBITO
	$insertSQL = sprintf("INSERT INTO cj_cc_notadecargo (id_empresa, idCliente, numeroNotaCargo, numeroControlNotaCargo, fechaRegistroNotaCargo, fechaVencimientoNotaCargo, idDepartamentoOrigenNotaCargo, referencia_nota_cargo, id_anticipo_anulado, tipoNotaCargo, diasDeCreditoNotaCargo, estadoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, observacionNotaCargo, subtotalNotaCargo, fletesNotaCargo, interesesNotaCargo, descuentoNotaCargo, baseImponibleNotaCargo, porcentajeIvaNotaCargo, calculoIvaNotaCargo, base_imponible_iva_lujo, porcentaje_iva_lujo, ivaLujoNotaCargo, montoExentoNotaCargo, montoExoneradoNotaCargo, aplicaLibros, id_empleado_creador)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idCliente, "int"),
		valTpDato($numeroActual, "text"),
		valTpDato($numeroActualControl, "text"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistro)), "date"),
		valTpDato(date("Y-m-d", strtotime($txtFechaVencimiento)), "date"),
		valTpDato($idModulo, "int"),
		valTpDato(0, "int"), // 0 = Cheque Devuelto, 1 = Otros
		valTpDato($idAnticipo, "int"),
		valTpDato($lstTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($txtDiasCreditoCliente, "int"),
		valTpDato("0", "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato($txtObservacion, "text"),
		valTpDato($txtSubTotalNotaCargo, "real_inglesa"),
		valTpDato($txtFlete, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtBaseImponibleIva, "real_inglesa"),
		valTpDato($txtIva, "real_inglesa"),
		valTpDato($txtSubTotalIva, "real_inglesa"),
		valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
		valTpDato($txtIvaLujo, "real_inglesa"),
		valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
		valTpDato($txtMontoExento, "real_inglesa"),
		valTpDato($txtMontoExonerado, "real_inglesa"),
		valTpDato(0, "boolean"), // 0 = No, 1 = Si
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCargo = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DEL DOCUMENTO
	$insertSQL = sprintf("INSERT INTO cj_cc_nota_cargo_detalle_motivo (id_nota_cargo, id_motivo, precio_unitario)
	VALUE (%s, %s, %s);",
		valTpDato($idNotaCargo, "int"),
		valTpDato($idMotivo, "int"),
		valTpDato($precioUnitario, "real_inglesa"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaDebitoDetalle = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL MOTIVO DE LA NOTA DE DEBITO
	$updateSQL = sprintf("UPDATE cj_cc_notadecargo SET
		id_motivo = %s
	WHERE idNotaCargo = %s;",
		valTpDato($idMotivo, "int"),
		valTpDato($idNotaCargo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("ND", "text"),
		valTpDato($idNotaCargo, "int"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistro)), "date"),
		valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$script = sprintf("verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s',960,550)", $idNotaCargo);
	
	return array(true, $idNotaCargo, $idModulo, $script);
}

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