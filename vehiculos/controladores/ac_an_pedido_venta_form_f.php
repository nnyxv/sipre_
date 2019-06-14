<?php


function asignarBanco($idBanco, $valores = "") {
	$objResponse = new xajaxResponse();
	
	$valores = (is_array($valores)) ? $valores : explode("|",$valores);
	if (isset($valores)) {
		foreach ($valores as $indice => $valor) {
			$valor = explode("*",$valor);
			$arrayFinal[$valor[0]] = $valor[1];
		}
	}
	
	$objResponse->script("
	byId('trCuotasFinanciar2').style.display = 'none';
	byId('trCuotasFinanciar3').style.display = 'none';
	byId('trCuotasFinanciar4').style.display = 'none';
			
	byId('tdFinanciamiento').rowSpan = '1';");
	
	$lstMesesFinanciar2 = "";
	$lstMesesFinanciar3 = "";
	$lstMesesFinanciar4 = "";
	
	// BUSCA LOS DATOS DEL BANCO
	$queryBanco = sprintf("SELECT nombreBanco, porcentaje_flat FROM bancos WHERE idBanco = %s;",
		valTpDato($idBanco, "int"));
	$rsBanco = mysql_query($queryBanco);
	if (!$rsBanco) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsBanco = mysql_num_rows($rsBanco);
	$rowBanco = mysql_fetch_array($rsBanco);
	
	$queryFactor = sprintf("SELECT
		mes,
		factor,
		CONCAT(mes, ' Meses / ', tasa, '%s') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = %s
	ORDER BY tasa;",
		valTpDato("%", "campo"),
		valTpDato($idBanco, "int"));
	$rsFactor = @mysql_query($queryFactor);
	if (!$rsFactor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactor = mysql_num_rows($rsFactor);
	if ($totalRowsBanco > 0) {
		if ($totalRowsFactor > 0) {
			$lstMesesFinanciar = "<select id=\"lstMesesFinanciar\" name=\"lstMesesFinanciar\" class=\"inputHabilitado\" onchange=\"xajax_asignarFactor(this.value, xajax.getFormValues('frmDcto'));\">";
				$lstMesesFinanciar .= "<option value=\"\">[ Seleccione ]</option>";
			while($rowFactor = @mysql_fetch_assoc($rsFactor)) {
				$selected = ($arrayFinal['lstMesesFinanciar'] == $rowFactor['mes']) ? "selected=\"selected\"" : "";
				
				$lstMesesFinanciar .= "<option ".$selected." value=\"".$rowFactor['mes']."\">".$rowFactor['financiamento']."</option>";
			}
			$lstMesesFinanciar .= "</select>";
			
			$objResponse->assign("tdtxtCuotasFinanciar","innerHTML",
				"<input type=\"text\" id=\"txtCuotasFinanciar\" name=\"txtCuotasFinanciar\" class=\"inputCompleto\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" readonly=\"readonly\" style=\"border:0px; text-align:right;\"/>".
				"<input type=\"hidden\" id=\"hddFactorFinanciar\" name=\"hddFactorFinanciar\"/>");
		} else {
			$objResponse->loadCommands(asignarFactor(-1,array()));
			$objResponse->script("
			byId('trCuotasFinanciar2').style.display = '';
			byId('trCuotasFinanciar3').style.display = '';
			byId('trCuotasFinanciar4').style.display = '';
			
			byId('tdFinanciamiento').rowSpan = '4';");
			
			for ($cont = 1; $cont <= 4; $cont++) {
				$contAux = ($cont == 1) ? "" : $cont;
				
				$htmlLstMesesFinanciar = "<table border=\"0\">".
				"<tr>".
					"<td><input type=\"text\" id=\"lstMesesFinanciar".$contAux."\" name=\"lstMesesFinanciar".$contAux."\" class=\"inputHabilitado\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" style=\"text-align:right; width:40px;\" value=\"".($arrayFinal['lstMesesFinanciar'.$contAux])."\"/></td>".
					"<td>"." Meses"."</td>".
					"<td>"."&nbsp;/&nbsp;"."</td>".
					"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar".$contAux."\" name=\"txtInteresCuotaFinanciar".$contAux."\" class=\"inputHabilitado\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" style=\"text-align:right; width:60px;\" value=\"".($arrayFinal['txtInteresCuotaFinanciar'.$contAux])."\"/></td>".
					"<td>"."%"."</td>".
				"</tr>".
				"<tr align=\"right\">".
					"<td colspan=\"2\">Fecha Pago:</td>".
					"<td colspan=\"3\">"."<input type=\"text\" id=\"txtFechaCuotaFinanciar".$contAux."\" name=\"txtFechaCuotaFinanciar".$contAux."\" autocomplete=\"off\" class=\"inputHabilitado\" size=\"10\" style=\"text-align:center\" value=\"".(($arrayFinal['txtFechaCuotaFinanciar'.$contAux] != "") ? date(spanDateFormat, strtotime($arrayFinal['txtFechaCuotaFinanciar'.$contAux])) : "")."\"/>"."</td>".
				"</tr>".
				"</table>";
				
				$objResponse->assign("tdtxtCuotasFinanciar".$contAux,"innerHTML",
					"<input type=\"text\" id=\"txtCuotasFinanciar".$contAux."\" name=\"txtCuotasFinanciar".$contAux."\" class=\"inputCompletoHabilitado\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" style=\"text-align:right;\" value=\"".($arrayFinal['txtCuotasFinanciar'.$contAux])."\"/>");
				
				$lstMesesFinanciar = ($cont == 1) ? $htmlLstMesesFinanciar : $lstMesesFinanciar;
				$lstMesesFinanciar2 = ($cont == 2) ? $htmlLstMesesFinanciar : $lstMesesFinanciar2;
				$lstMesesFinanciar3 = ($cont == 3) ? $htmlLstMesesFinanciar : $lstMesesFinanciar3;
				$lstMesesFinanciar4 = ($cont == 4) ? $htmlLstMesesFinanciar : $lstMesesFinanciar4;
			}
		}
	} else {
		for ($cont = 1; $cont <= 4; $cont++) {
			$contAux = ($cont == 1) ? "" : $cont;
			
			$htmlLstMesesFinanciar = "<table border=\"0\">".
			"<tr>".
				"<td><input type=\"text\" id=\"lstMesesFinanciar".$contAux."\" name=\"lstMesesFinanciar".$contAux."\" class=\"inputSinFondo\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" readonly=\"readonly\" style=\"text-align:right; width:40px;\" value=\"0.00\"/></td>".
				"<td>"." Meses"."</td>".
				"<td>"."&nbsp;/&nbsp;"."</td>".
				"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar".$contAux."\" name=\"txtInteresCuotaFinanciar".$contAux."\" class=\"inputSinFondo\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" readonly=\"readonly\" style=\"text-align:right; width:60px;\" value=\"0.00\"/></td>".
				"<td>"."%"."</td>".
			"</tr>".
			"<tr align=\"right\">".
				"<td colspan=\"2\">Fecha Pago:</td>".
				"<td colspan=\"3\">"."<input type=\"text\" id=\"txtFechaCuotaFinanciar".$contAux."\" name=\"txtFechaCuotaFinanciar".$contAux."\" autocomplete=\"off\" class=\"inputSinFondo\" readonly=\"readonly\" size=\"10\" style=\"text-align:center\" value=\"\"/>"."</td>".
			"</tr>".
			"</table>";
			
			$objResponse->assign("tdtxtCuotasFinanciar".$contAux,"innerHTML",
				"<input type=\"text\" id=\"txtCuotasFinanciar".$contAux."\" name=\"txtCuotasFinanciar".$contAux."\" class=\"inputSinFondo\" onblur=\"setFormatoRafk(this,2); xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" onkeypress=\"return validarSoloNumerosReales(e);\" readonly=\"readonly\" style=\"text-align:right;\" value=\"0.00\"/>");
			
			$lstMesesFinanciar = ($cont == 1) ? $htmlLstMesesFinanciar : $lstMesesFinanciar;
			$lstMesesFinanciar2 = ($cont == 2) ? $htmlLstMesesFinanciar : $lstMesesFinanciar2;
			$lstMesesFinanciar3 = ($cont == 3) ? $htmlLstMesesFinanciar : $lstMesesFinanciar3;
			$lstMesesFinanciar4 = ($cont == 4) ? $htmlLstMesesFinanciar : $lstMesesFinanciar4;
		}
	}
	
	$objResponse->assign("txtPorcFLAT","value",number_format($rowBanco['porcentaje_flat'], 2, ".", ","));
	
	$objResponse->assign("capameses_financiar","innerHTML",$lstMesesFinanciar);
	$objResponse->assign("capameses_financiar2","innerHTML",$lstMesesFinanciar2);
	$objResponse->assign("capameses_financiar3","innerHTML",$lstMesesFinanciar3);
	$objResponse->assign("capameses_financiar4","innerHTML",$lstMesesFinanciar4);
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	if ($totalRowsBanco > 0 || $totalRowsFactor > 0) {
		$objResponse->script("
		jQuery(function($){
			$(\"#txtFechaCuotaFinanciar\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			$(\"#txtFechaCuotaFinanciar2\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			$(\"#txtFechaCuotaFinanciar3\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
			$(\"#txtFechaCuotaFinanciar4\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		});
	
		new JsDatePick({
			useMode:2,
			target:\"txtFechaCuotaFinanciar\",
			dateFormat:\"".spanDatePick."\", 
			cellColorScheme:\"orange\"
		});
	
		new JsDatePick({
			useMode:2,
			target:\"txtFechaCuotaFinanciar2\",
			dateFormat:\"".spanDatePick."\", 
			cellColorScheme:\"orange\"
		});
	
		new JsDatePick({
			useMode:2,
			target:\"txtFechaCuotaFinanciar3\",
			dateFormat:\"".spanDatePick."\", 
			cellColorScheme:\"orange\"
		});
	
		new JsDatePick({
			useMode:2,
			target:\"txtFechaCuotaFinanciar4\",
			dateFormat:\"".spanDatePick."\", 
			cellColorScheme:\"orange\"
		});");
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$idModulo = 2;
	
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
		cliente.status
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
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		
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
	
	if ($rowCliente['id_cliente'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$tipoCuentaCliente = $rowCliente['tipo_cuenta_cliente'];
	if ($tipoCuentaCliente == 1) {
		$tdMsjCliente .= "<div class=\"divMsjError\" style=\"padding:2px;\">".$rowCliente['reputacionCliente']."</div>";
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

function asignarEmpleado($idEmpleado, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
	
	// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	$queryEmpleado = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarFactor($lstMesesFinanciar, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$queryFactor = sprintf("SELECT
		mes,
		factor,
		CONCAT(mes, ' Meses / ', tasa, '%s') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = %s
		AND mes = %s
	ORDER BY tasa;",
		valTpDato("%", "campo"),
		valTpDato($frmDcto['lstBancoFinanciar'], "int"),
		valTpDato($lstMesesFinanciar, "double"));
	$rsFactor = @mysql_query($queryFactor);
	if (!$rsFactor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactor = mysql_num_rows($rsFactor);
	$rowFactor = @mysql_fetch_assoc($rsFactor);
	
	$objResponse->assign("hddFactorFinanciar","value",$rowFactor['factor']);
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function asignarMoneda($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('txtTasaCambio').className = 'inputHabilitado';
	byId('txtTasaCambio').readOnly = false;
	byId('txtFechaTasaCambio').className = 'inputHabilitado';
	byId('txtFechaTasaCambio').readOnly = false;
	
	byId('txtTasaCambio').onblur = function() {
		setFormatoRafk(this,3);
	}
	
	jQuery(function($){
		$(\"#txtFechaTasaCambio\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaTasaCambio\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	byId('trTasaCambio').style.display = 'none';");
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Extrangero
	
	if ($idModoCompra == 1) { // 1 = Nacional, 2 = Importacion
		$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
	} else {
		$objResponse->script("
		byId('trTasaCambio').style.display = '';");
		
		$queryTasaCambio = sprintf("SELECT * FROM pg_tasa_cambio
		WHERE id_moneda_extranjera = %s
			AND id_moneda_nacional = %s
			AND id_tasa_cambio = %s;",
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['hddIdMoneda'], "int"),
			valTpDato($frmDcto['lstTasaCambio'], "int"));
		$rsTasaCambio = mysql_query($queryTasaCambio);
		if (!$rsTasaCambio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTasaCambio = mysql_fetch_assoc($rsTasaCambio);
		
		$objResponse->assign("txtTasaCambio", "value", number_format($rowTasaCambio['monto_tasa_cambio'], 3, ".", ","));
	}
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	
	$objResponse->assign("hddIncluirImpuestos", "value", $rowMonedaOrigen['incluir_impuestos']);
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function asignarPoliza($idPoliza) {
	$objResponse = new xajaxResponse();
	
	$queryPoliza = sprintf("SELECT * FROM an_poliza WHERE id_poliza = %s;",
		valTpDato($idPoliza, "int"));
	$rsPoliza = mysql_query($queryPoliza);
	if (!$rsPoliza) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPoliza = mysql_fetch_assoc($rsPoliza);
	
	$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPoliza['nom_comp_seguro']);
	$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPoliza['dir_agencia']);
	$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPoliza['ciudad_agencia']);
	$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPoliza['pais_agencia']);
	$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPoliza['telf_agencia']);
	$objResponse->assign("txtMontoSeguro","value",number_format($rowPoliza['contado_poliza'], 2, ".", ","));
	$objResponse->assign("txtInicialPoliza","value",number_format($rowPoliza['inicial_poliza'], 2, ".", ","));
	$objResponse->assign("txtMesesPoliza","value",$rowPoliza['meses_poliza']);
	$objResponse->assign("txtCuotasPoliza","value",number_format($rowPoliza['cuotas_poliza'], 2, ".", ","));
	
	$objResponse->assign("cheque_poliza","value",$rowPoliza['cheque_poliza']);
	$objResponse->assign("financiada","value",$rowPoliza['financiada']);
	
	return $objResponse;
}

function asignarSinBancoFinanciar($frmDcto) {
	$objResponse = new xajaxResponse();
	
	if ($frmDcto['cbxSinBancoFinanciar'] == 1) {
		if ($frmDcto['hddSinBancoFinanciar'] == 1) {
			$objResponse->script("
			selectedOption('lstBancoFinanciar','');
			byId('lstBancoFinanciar').onchange();");
		} else {
			$objResponse->script("
			byId('cbxSinBancoFinanciar').checked = false;
			byId('aDesbloquearSinBancoFinanciar').click();");
		}
	}
	
	return $objResponse;
}

function asignarUnidadBasica($idUnidadBasica, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_modelos vw_iv_modelo WHERE vw_iv_modelo.id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdUnidadBasica","value",$idUnidadBasica);
	$objResponse->assign("txtNombreUnidadBasica","value",utf8_encode($row['nom_uni_bas']));
	$objResponse->assign("txtMarca","value",utf8_encode($row['nom_marca']));
	$objResponse->assign("txtModelo","value",utf8_encode($row['nom_modelo']));
	$objResponse->assign("txtVersion","value",utf8_encode($row['nom_version']));
	
	// BUSCA LOS IMPUESTOS DE VENTA
	$query = sprintf("SELECT
		iva.iva,
		iva.observacion
	FROM an_unidad_basica_impuesto uni_bas_impuesto
		INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
	WHERE uni_bas_impuesto.id_unidad_basica = %s
		AND iva.tipo IN (6);",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$txtPorcIva += $row['iva'];
		$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
		$spanPorcIva .= $cond.$row['observacion'];
	}
	$spanPorcIva .= ($totalRows > 0) ? "" : "Exento";
	
	// BUSCA LOS IMPUESTOS DE VENTA DE LUJO
	$query = sprintf("SELECT
		iva.iva,
		iva.observacion
	FROM an_unidad_basica_impuesto uni_bas_impuesto
		INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
	WHERE uni_bas_impuesto.id_unidad_basica = %s
		AND iva.tipo IN (2);",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$txtPorcIvaLujo += $row['iva'];
		$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
		$spanPorcIva .= $cond.$row['observacion'];
	}
	
	$objResponse->assign("txtPorcIva","value",number_format($txtPorcIva, 2, ".", ","));
	$objResponse->assign("txtPorcIvaLujo","value",number_format($txtPorcIvaLujo, 2, ".", ","));
	$objResponse->assign("spanPorcIva","innerHTML",((strlen($spanPorcIva) > 0) ? "(".$spanPorcIva.")" : ""));
	
	$objResponse->loadCommands(asignarUnidadFisica(""));
	
	$objResponse->script("byId('txtCantidadAsignada').focus();");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

function asignarUnidadFisica($idUnidadFisica, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.id_activo_fijo,
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		vw_iv_modelo.nom_marca,
		vw_iv_modelo.nom_modelo,
		vw_iv_modelo.nom_version,
		vw_iv_modelo.nom_ano,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		uni_fis.tipo_placa,
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
		cxp_fact.id_modulo,
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
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE uni_fis.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['id_uni_bas'] > 0) {
		$objResponse->loadCommands(asignarUnidadBasica($row['id_uni_bas']));
	}
	
	$objResponse->assign("txtIdUnidadFisica","value",$idUnidadFisica);
	$objResponse->assign("txtNombreUnidadFisica","value",$idUnidadFisica);
	$objResponse->assign("txtAno","value",utf8_encode($row['nom_ano']));
	$objResponse->assign("txtColorExterno1","value",utf8_encode($row['color_externo1']));
	$objResponse->assign("txtSerialCarroceria","value",utf8_encode($row['serial_carroceria']));
	$objResponse->assign("txtSerialMotor","value",utf8_encode($row['serial_motor']));
	$objResponse->assign("txtKilometraje","value",utf8_encode($row['kilometraje']));
	$objResponse->assign("txtPlaca","value",utf8_encode($row['placa']));
	$objResponse->assign("txtCondicion","value",utf8_encode($row['condicion_unidad']));
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'));");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

function bloquearLstClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s;",
		valTpDato($idClaveMovimiento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['pago_contado'] == 1 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 1 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	}
	
	$objResponse->script($accion);

	return $objResponse;
}

function buscarAdicional($frmBuscarAdicional) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarAdicional['txtCriterioBuscarAdicional']);
	
	$objResponse->loadCommands(listaAdicional(0, "nom_accesorio", "ASC", $valBusq));
	$objResponse->loadCommands(listaPaquete(0, "nom_paquete", "ASC", $valBusq));
		
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

function buscarEmpleado($frmBuscarEmpleado, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);
	
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
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

function buscarUnidadBasica($frmBuscarUnidadBasica, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarUnidadBasica['txtCriterioBuscarUnidadBasica']);
	
	$objResponse->loadCommands(listaUnidadBasica(0, "id_uni_bas", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarUnidadFisica($frmBuscarUnidadFisica, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmDcto['txtIdUnidadBasica'],
		$frmBuscarUnidadFisica['txtCriterioBuscarUnidadFisica']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "id_unidad_fisica", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmDcto['cbx1'];
	if (isset($arrayObj1)) {
		$i = 0;
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmAdicional:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmAdicional:".$valor,"innerHTML",$i);
		}
	}
	
	$objResponse->script("
	byId('fieldsetVehiculoUsado').style.display = 'none';
	byId('fieldsetFormaPago').style.display = 'none';
	byId('fieldsetVentaUnidad').style.display = 'none';
	
	byId('aAgregarFormaPago').style.display = 'none';
	byId('btnQuitarFormaPago').style.display = 'none';");
	
	if (in_array(idArrayPais,array(1,2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$objResponse->script("
		byId('trPagoContado').style.display = 'none';
		byId('trPND').style.display = 'none';
		byId('trTotalPND').style.display = 'none';
		byId('trOtrosPagos').style.display = 'none';
		byId('trTotalOtrosPagos').style.display = 'none';
		
		byId('fieldsetOtros').style.display = 'none';");
	}
	
	$hddPagaImpuesto = ($frmDcto['hddPagaImpuesto'] != "") ? $frmDcto['hddPagaImpuesto'] : 1;
	
	$txtPrecioBase = ($frmDcto['txtIdUnidadBasica'] > 0 || $frmDcto['txtIdUnidadFisica'] > 0) ? doubleval(str_replace(",","",$frmDcto['txtPrecioBase'])) : 0;
	$txtDescuento = doubleval(str_replace(",","",$frmDcto['txtDescuento']));
	$txtPorcIva = doubleval((($hddPagaImpuesto == 1) ? str_replace(",","",$frmDcto['txtPorcIva']) : 0));
	$txtPorcIvaLujo = doubleval((($hddPagaImpuesto == 1) ? str_replace(",","",$frmDcto['txtPorcIvaLujo']) : 0));
	
	$txtPrecioVenta = $txtPrecioBase - $txtDescuento;
	
	$txtSubTotalIva += ($txtPorcIva != 0) ? ($txtPrecioVenta * $txtPorcIva) / 100 : 0;
	$txtSubTotalIva += ($txtPorcIvaLujo != 0) ? ($txtPrecioVenta * $txtPorcIvaLujo) / 100 : 0;
	
	$txtPrecioVenta += $txtSubTotalIva;
	
	if ($frmDcto['txtIdUnidadBasica'] > 0 || $frmDcto['txtIdUnidadFisica'] > 0) {
		$objResponse->script("
		byId('fieldsetFormaPago').style.display = '';
		byId('fieldsetVentaUnidad').style.display = '';");
	}
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$objResponse->assign("hddTipoAdicionalItm".$valor1,"value",$frmDcto['lstTipoAdicionalItm'.$valor1]);
			
			$objResponse->script("
			byId('tblCondicionItm".$valor1."').style.display = '';
			byId('trPrecioPagadoItm".$valor1."').style.display = 'none';
			byId('lstMostrarItm".$valor1."').style.display = 'none';");
			
			if ($frmDcto['lstTipoAdicionalItm'.$valor1] == 1) { // 1 = Adicional
				$txtTotalAdicionalFinanciar += ($frmDcto['cbxCondicionItm'.$valor1] == 1) ? 0 : str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valor1]);
				$txtTotalAdicional += str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valor1]);
				
				if ($frmDcto['lstMostrarItm'.$valor1] == 2) { // 2 = Incluir en el Costo
					$objResponse->script("
					byId('txtPrecioConIvaItm".$valor1."').className = 'inputInicial';
					byId('txtPrecioConIvaItm".$valor1."').readOnly = true;");
					$objResponse->assign("txtPrecioConIvaItm".$valor1,"value",$frmDcto['hddCostoUnitarioItm'.$valor1]);
					$objResponse->assign("txtPrecioPagadoItm".$valor1,"value",$frmDcto['hddCostoUnitarioItm'.$valor1]);
				} else {
					$objResponse->script("
					byId('txtPrecioConIvaItm".$valor1."').className = 'inputCompletoHabilitado';
					byId('txtPrecioConIvaItm".$valor1."').readOnly = false;");
					if (!($frmDcto['cbxCondicionItm'.$valor1] == 1)) {
						$objResponse->assign("txtPrecioPagadoItm".$valor1,"value",number_format(0, 2, ".", ","));
					}
				}
				
				$objResponse->script("
				byId('trPrecioPagadoItm".$valor1."').style.display = 'none';
				byId('lstMostrarItm".$valor1."').style.display = '';
				if (byId('cbxCondicionItm".$valor1."').checked == true) {
					byId('trPrecioPagadoItm".$valor1."').style.display = '';
					byId('lstMostrarItm".$valor1."').style.display = 'none';
					selectedOption('lstMostrarItm".$valor1."','');
				}
				byId('tblCondicionItm".$valor1."').style.display = '';
				if (byId('lstMostrarItm".$valor1."').value > 0) {
					byId('tblCondicionItm".$valor1."').style.display = 'none';
					byId('cbxCondicionItm".$valor1."').checked = false;
				}");
				
			} else if ($frmDcto['lstTipoAdicionalItm'.$valor1] == 3) { // 3 = Contrato
				$txtTotalAdicionalContrato += str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valor1]);
				
				$objResponse->script("
				byId('tblCondicionItm".$valor1."').style.display = 'none';
				byId('cbxCondicionItm".$valor1."').checked = false;
				selectedOption('lstMostrarItm".$valor1."','');");
			}
			
			if (in_array(idArrayPais,array(1,2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
				$objResponse->script("
				byId('trPrecioPagadoItm".$valor1."').style.display = 'none';
				byId('lstMostrarItm".$valor1."').style.display = 'none';");
			}
		}
	}
	
	if ($frmDcto['rbtInicial'] == 1) {
		$txtPorcInicial = doubleval(str_replace(",","",$frmDcto['txtPorcInicial']));
		$txtMontoInicial = ($txtPorcInicial * $txtPrecioVenta) / 100;
	} else {
		$txtMontoInicial = doubleval(str_replace(",","",$frmDcto['txtMontoInicial']));
		$txtPorcInicial = ($txtMontoInicial * 100) / $txtPrecioVenta;
	}
	
	$txtMontoAnticipo = ($txtTotalAdicional - $txtTotalAdicionalFinanciar);
	$txtSaldoFinanciar = ($txtPrecioVenta + $txtTotalAdicionalFinanciar) - $txtMontoInicial;
	
	$hddFactorFinanciar = doubleval(str_replace(",","",$frmDcto['hddFactorFinanciar']));
	$txtCuotasFinanciar = ($hddFactorFinanciar != 0) ? ($txtSaldoFinanciar * $hddFactorFinanciar) : doubleval(str_replace(",","",$frmDcto['txtCuotasFinanciar']));
	
	if ($txtSaldoFinanciar == 0) {
		$txtMontoFLAT = 0;
		$objResponse->script("
		byId('trBancoFinanciar').style.display = 'none';
		byId('trMontoFLAT').style.display = 'none';");
		
		if ($frmDcto['lstBancoFinanciar'] > 0) {
			$objResponse->script("
			selectedOption('lstBancoFinanciar','');
			byId('lstBancoFinanciar').onchange();");
		}
	} else {
		$objResponse->script("
		byId('trBancoFinanciar').style.display = '';
		byId('trMontoFLAT').style.display = '';
		
		byId('fieldsetFormaPago').style.display = '';");
		$txtPorcFLAT =  str_replace(",","",$frmDcto['txtPorcFLAT']);
		$txtMontoFLAT = round((($txtSaldoFinanciar * $txtPorcFLAT) / 100),2); 
	}
	
	$txtMontoComplementoInicial = $txtMontoInicial + $txtMontoAnticipo;
	
	$txtTotalInicialAdicionales = $txtMontoInicial + $txtTotalAdicional;
	$txtMontoComplementoInicial = $txtMontoInicial + $txtMontoAnticipo;
	$txtPrecioTotal = $txtMontoComplementoInicial + $txtMontoFLAT;
	$txtTotalPedido = $txtPrecioVenta + $txtTotalAdicional;
	
	$objResponse->assign("txtPrecioBase","value",number_format($txtPrecioBase, 2, ".", ","));
	$objResponse->assign("txtPrecioVenta","value",number_format($txtPrecioVenta, 2, ".", ","));
	$objResponse->assign("txtTotalAdicional","value",number_format($txtTotalAdicional, 2, ".", ","));
	$objResponse->assign("txtTotalAdicionalContrato","value",number_format($txtTotalAdicionalContrato, 2, ".", ","));
	
	$objResponse->assign("txtPorcInicial","value",number_format($txtPorcInicial, 2, ".", ","));
	$objResponse->assign("txtMontoInicial","value",number_format($txtMontoInicial, 2, ".", ","));
	$objResponse->assign("txtSaldoFinanciar","value",number_format($txtSaldoFinanciar, 2, ".", ","));
	$objResponse->assign("txtCuotasFinanciar","value",number_format($txtCuotasFinanciar, 2, ".", ","));
	$objResponse->assign("txtMontoFLAT","value",number_format($txtMontoFLAT, 2, ".", ","));
	
	$objResponse->assign("txtMontoAnticipo","value",number_format($txtMontoAnticipo, 2, ".", ","));
	$objResponse->assign("txtMontoComplementoInicial","value",number_format($txtMontoComplementoInicial, 2, ".", ","));
	$objResponse->assign("txtTotalInicialAdicionales","value",number_format($txtTotalInicialAdicionales, 2, ".", ","));
	$objResponse->assign("txtPrecioTotal","value",number_format($txtPrecioTotal, 2, ".", ","));
	$objResponse->assign("txtTotalPedido","value",number_format($txtTotalPedido, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstBancoFinanciar($nombreObjeto = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE nombreBanco <> '-' ORDER BY nombreBanco;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_asignarBanco(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
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
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
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
		$totalRowsClaveMov = mysql_num_rows($rsClaveMov);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = "";
			if ($selId == $rowClaveMov['id_clave_movimiento'] || $totalRowsClaveMov == 1) {
				$selected = "selected=\"selected\"";
				
				$nombreObjeto2 = (substr($nombreObjeto,strlen($nombreObjeto)-4,strlen($nombreObjeto)) == "Pres") ? "Pres": "";
				
				$objResponse->loadCommands(bloquearLstClaveMovimiento($rowClaveMov['id_clave_movimiento'], $nombreObjeto2));
			}
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCredito($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$arrayCredito = array(
		array(1, "Contado"),
		array(0, "Crédito"));
	
	if ($selId == "0") { // 0 = Crédito
		$onChange = sprintf("selectedOption('lstCredito', 0);");
	} else if ($selId == "1") { // 1 = Contado
		$onChange = sprintf("selectedOption('lstCredito', 1);");
	}
	$onChange .= sprintf("xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '3', this.value, '1', '%s');",
		"19"); // 19 = Mostrador Público Contado
	
	$html = "<select id=\"lstCredito\" name=\"lstCredito\" onchange=\"".$onChange."\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($arrayCredito as $indice => $valor) {
		$selected = ($selId == $arrayCredito[$indice][0]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$arrayCredito[$indice][0]."\">".$arrayCredito[$indice][1]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCredito","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpleado($nombreObjeto = "", $claveFiltro = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$claveFiltro = (is_array($claveFiltro)) ? implode(",",$claveFiltro) : $claveFiltro;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(empleado.activo = 1
	OR empleado.id_empleado = %s)",
		valTpDato($selId, "int"));
	
	if ($claveFiltro != "-1" && $claveFiltro != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("empleado.clave_filtro IN (%s)",
			valTpDato($claveFiltro, "campo"));
	}
	
	$query = sprintf("SELECT
		empleado.id_empleado,
		empleado.nombre_empleado,
		empleado.nombre_departamento,
		empleado.nombre_cargo,
		empleado.clave_filtro,
		empleado.nombre_filtro
	FROM vw_pg_empleados empleado %s
	ORDER BY empleado.nombre_empleado;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMesesFinanciar($idBanco, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		mes,
		factor,
		CONCAT(mes, ' Meses / ', tasa, '%s') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = %s
	ORDER BY tasa;",
		valTpDato("%", "campo"),
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMesesFinanciar\" name=\"lstMesesFinanciar\" class=\"inputHabilitado\" onchange=\"xajax_calcularDcto(xajax.getFormValues('frmDcto'));\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($arrayFinal['lstMesesFinanciar'] == $rowFactor['mes']) ? "selected=\"selected\"" : "";
		
		$lstMesesFinanciar .= "<option ".$selected." value=\"".$rowFactor['mes']."\">".$rowFactor['financiamento']."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMesesFinanciar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"xajax_cargaLstTasaCambio(this.value); xajax_asignarMoneda(xajax.getFormValues('frmDcto')); ".$onChange."\"";

	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" ".$class." ".$onChange." style=\"width:150px\">";
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

function cargaLstPoliza($nombreObjeto = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_poliza WHERE estatus = 1 ORDER BY nombre_poliza;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_asignarPoliza(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_poliza']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_poliza']."\">".utf8_encode($row['nombre_poliza'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTasaCambio($idMoneda, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"xajax_asignarMoneda(xajax.getFormValues('frmDcto')); ".$onChange."\"";
	
	$query = sprintf("SELECT *
	FROM pg_tasa_cambio tasa_cambio
		INNER JOIN pg_monedas moneda_local ON (tasa_cambio.id_moneda_nacional = moneda_local.idmoneda)
	WHERE tasa_cambio.id_moneda_extranjera = %s;",
		valTpDato($idMoneda, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstTasaCambio\" name=\"lstTasaCambio\" ".$class." ".$onChange." style=\"width:150px\">";
	if ($totalRows > 0) {
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['id_tasa_cambio']) ? "selected=\"selected\"" : "";
			
			$html .= "<optgroup label=\"".$row['abreviacion']." ".$row['monto_tasa_cambio']."\">";
				$html .= "<option ".$selected." value=\"".$row['id_tasa_cambio']."\">".utf8_encode($row['nombre_tasa_cambio'])."</option>";
			$html .= "</optgroup>";
		}
	} else {
		$html .= "<option value=\"\"></option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTasaCambio","innerHTML",$html);
	
	$objResponse->script((($totalRows > 0) ? "byId('lstTasaCambio').style.display = ''" : "byId('lstTasaCambio').style.display = 'none'"));
	
	return $objResponse;
}

function cargarDcto($idPedido, $idFactura = "", $idEmpresa = "", $idPresupuesto = "", $numeroPresupuesto = "", $hddTipoPedido = "") {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('trBuscarPresupuesto').style.display = 'none';
	byId('trFieldsetCliente').style.display = '';
	byId('trFieldsetUnidadFisica').style.display = '';
	byId('trFieldsetVehiculoUsado').style.display = '';
	byId('trBtnGuardar').style.display = '';
	
	byId('fielsetPresupuestoAccesorios').style.display = 'none';");
	
	if ($idFactura > 0) {
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFactura = sprintf("SELECT cxc_fact.*,
			(CASE cxc_fact.estadoFactura
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_fact_vent
		FROM cj_cc_encabezadofactura cxc_fact
		WHERE cxc_fact.idFactura = %s
			AND cxc_fact.anulada LIKE 'NO';",
			valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_array($rsFactura);
		
		$idPedido = $rowFactura['numeroPedido'];
		$idEmpresa = $rowFactura['id_empresa'];
		
		$objResponse->assign("txtIdFactura","value",$idFactura);
		
		// VERIFICA VALORES DE CONFIGURACION (Editar Factura de Venta de Vehículos)
		$queryConfig209 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 209 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($idEmpresa,"int"));
		$rsConfig209 = mysql_query($queryConfig209);
		if (!$rsConfig209) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsConfig209 = mysql_num_rows($rsConfig209);
		$rowConfig209 = mysql_fetch_assoc($rsConfig209);
		
		$valor = explode("|",$rowConfig209['valor']);
		$estatus209 = $valor[0];
		$cantDiasMaximo = $valor[1];
		$cantMesesAnteriores = $valor[2];
		
		if ($estatus209 == 1) {
			$txtFechaProveedor = date(str_replace("d","01",spanDateFormat), strtotime($rowFactura['fechaRegistroFactura']));
			if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$cantMesesAnteriores." month", strtotime(date(spanDateFormat))))
				&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$cantMesesAnteriores." month", strtotime(date(spanDateFormat)))))
			|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $cantMesesAnteriores) { // VERIFICA SI ES DE MESES ANTERIORES
				if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $cantDiasMaximo
				|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI LA EDICION DE LA VENTA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
					if (!($rowFactura['id_pedido_reemplazo'] > 0)) {
						$permitirEditarFactura = true;
					}
				}
			}
		}
		
		if (!($permitirEditarFactura == true)) {
			$objResponse->alert("Esta factura no puede ser editada");
			return $objResponse->script("if (top.history.back()) { top.history.back(); } else { window.location.href='an_factura_venta_historico_list.php'; }");
		}
	}
	
	if (($idPresupuesto > 0 || ($idEmpresa > 0 && $numeroPresupuesto > 0)) && !($idPedido > 0)) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdUnidadBasica').className = 'inputHabilitado';
		byId('txtIdUnidadFisica').className = 'inputHabilitado';
		byId('txtFechaReserva').className = 'inputHabilitado';
		byId('txtFechaEntrega').className = 'inputHabilitado';
		byId('txtFechaVenta').className = 'inputHabilitado';
		byId('txtFechaAdministracion').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$idMoneda = $rowMoneda['idmoneda'];
		
		// BUSCA LOS DATOS DEL PRESUPUESTO
		$queryPresupuesto = sprintf("SELECT
			pres_vent.id_presupuesto,
			pres_vent.numeracion_presupuesto,
			pres_vent.id_empresa,
			pres_vent.id_cliente,
			pres_vent.id_uni_bas,
			pres_vent.asesor_ventas,
			pres_vent.estado,
			pres_vent.fecha,
			pres_vent.precio_venta,
			pres_vent.monto_descuento,
			pres_vent.porcentaje_iva,
			pres_vent.porcentaje_impuesto_lujo,
			pres_vent.tipo_inicial,
			pres_vent.porcentaje_inicial,
			pres_vent.monto_inicial,
			pres_vent.id_banco_financiar,
			pres_vent.saldo_financiar,
			pres_vent.meses_financiar,
			pres_vent.interes_cuota_financiar,
			pres_vent.cuotas_financiar,
			pres_vent.meses_financiar2,
			pres_vent.interes_cuota_financiar2,
			pres_vent.cuotas_financiar2,
			pres_vent.total_accesorio,
			pres_vent.total_inicial_gastos,
			pres_vent.total_adicional_contrato,
			pres_vent.total_general,
			pres_vent.porcentaje_flat,
			pres_vent.monto_flat,
			pres_vent.empresa_accesorio,
			pres_vent.exacc1,
			pres_vent.exacc2,
			pres_vent.exacc3,
			pres_vent.exacc4,
			pres_vent.vexacc1,
			pres_vent.vexacc2,
			pres_vent.vexacc3,
			pres_vent.vexacc4,
			pres_vent.id_poliza,
			pres_vent.monto_seguro,
			pres_vent.periodo_poliza,
			pres_vent.contado_poliza,
			pres_vent.inicial_poliza,
			pres_vent.meses_poliza,
			pres_vent.cuotas_poliza,
			pres_vent.observacion
		FROM an_presupuesto pres_vent
		WHERE pres_vent.id_presupuesto = %s
			OR (pres_vent.id_empresa = %s
				AND pres_vent.numeracion_presupuesto LIKE %s);",
			valTpDato($idPresupuesto, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($numeroPresupuesto, "text"));
		$rsPresupuesto = mysql_query($queryPresupuesto);
		if (!$rsPresupuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPresupuesto = mysql_fetch_assoc($rsPresupuesto);
		
		$idEmpresa = $rowPresupuesto['id_empresa'];
		$idCliente = $rowPresupuesto['id_cliente'];
		$idPresupuesto = $rowPresupuesto['id_presupuesto'];
		$idUnidadBasica = $rowPresupuesto['id_uni_bas'];
		
		if ($rowPresupuesto['estado'] == 3) {
			$objResponse->alert("El presupuesto ".$rowPresupuesto['numeracion_presupuesto']." está desautorizado");
			return $objResponse->script("if (top.history.back()) { top.history.back(); } else { window.location.href='an_presupuesto_venta_list.php'; }");
		}
		
		if (getmysql(sprintf("SELECT COUNT(*) FROM an_unidad_fisica uni_fis
							WHERE uni_fis.id_uni_bas = %s
								AND uni_fis.estado_venta IN ('POR REGISTRAR','DISPONIBLE')
								AND uni_fis.propiedad = 'PROPIO';",
								valTpDato($idUnidadBasica, "int"))) == 0) {
			$objResponse->alert("No existen unidades físicas disponibles para el presupuesto: ".$rowPresupuesto['numeracion_presupuesto']);
			return $objResponse->script("if (top.history.back()) { top.history.back(); } else { window.location.href='an_presupuesto_venta_list.php'; }");
		}
		
		if (getmysql(sprintf("SELECT id_pedido FROM an_pedido WHERE id_presupuesto = %s;", valTpDato($idPresupuesto, "int"))) > 0) {
			$objResponse->alert("El pedido del presupuesto ".$rowPresupuesto['numeracion_presupuesto']." ya ha sido generado");
			return $objResponse->script("window.location = 'an_ventas_pedido_editar.php?view=import&id=".valTpDato($idPresupuesto, "int")."';");
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->loadCommands(asignarEmpleado($rowPresupuesto['asesor_ventas'], "false"));
		$objResponse->loadCommands(asignarCliente($idCliente, $idEmpresa, $estatusCliente, $condicionPago, $rowPresupuesto['id_clave_movimiento']));
		
		// DATOS PEDIDO
		$rowPresupuesto['id_moneda'] = ($rowPresupuesto['id_moneda'] > 0) ? $rowPresupuesto['id_moneda'] : $idMoneda;
		$idMonedaLocal = $rowPresupuesto['id_moneda'];
		$idMonedaOrigen = ($rowPresupuesto['id_moneda_tasa_cambio'] > 0) ? $rowPresupuesto['id_moneda_tasa_cambio'] : $rowPresupuesto['id_moneda'];
		$txtTasaCambio = ($rowPresupuesto['monto_tasa_cambio'] >= 0) ? $rowPresupuesto['monto_tasa_cambio'] : 0;
		
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
		$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
		$objResponse->assign("txtFechaTasaCambio","value",(($rowPresupuesto['fecha_tasa_cambio'] != "") ? date(spanDateFormat, strtotime($rowPresupuesto['fecha_tasa_cambio'])) : ""));
		$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);
		$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $rowPresupuesto['id_tasa_cambio']));
		$objResponse->assign("hddIdPresupuestoVenta","value",$rowPresupuesto['id_presupuesto']);
		$objResponse->assign("txtNumeroPresupuestoVenta","value",$rowPresupuesto['numeracion_presupuesto']);
		$objResponse->script(sprintf("
		selectedOption('lstTipoClave',3);
		
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','2','3','','1','%s','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'-1\');\"');
		}",
			$rowPresupuesto['id_clave_movimiento']));
		
		// UNIDAD FISICA
		$objResponse->loadCommands(asignarUnidadBasica($idUnidadBasica));
		
		// VENTA DE LA UNIDAD
		$txtPorcIva = $rowPresupuesto['porcentaje_iva'];
		$txtPorcIvaLujo = $rowPresupuesto['porcentaje_impuesto_lujo'];
		
		$objResponse->assign("txtPrecioBase","value",number_format($rowPresupuesto['precio_venta'], 2, "." , ","));
		$objResponse->assign("txtDescuento","value",number_format($rowPresupuesto['monto_descuento'], 2, "." , ","));
		
		// VERIFICA SI TIENE IMPUESTO
		if (getmysql(sprintf("SELECT UPPER(isan_uni_bas) FROM an_uni_bas WHERE id_uni_bas = %s;", valTpDato($idUnidadBasica,"int"))) == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (6)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($row = mysql_fetch_assoc($rs)) {
				$txtNuevoPorcIva += $row['iva'];
				$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
				$spanPorcIva .= $cond.$row['observacion'];
			}
		} else {
			$txtNuevoPorcIva = 0;
			$spanPorcIva .= "Exento";
		}
		
		if (getmysql(sprintf("SELECT impuesto_lujo FROM an_uni_bas WHERE id_uni_bas = %s;", valTpDato($idUnidadBasica,"int"))) == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (2)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($row = mysql_fetch_assoc($rs)) {
				$txtNuevoPorcIvaLujo += $row['iva'];
				$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
				$spanPorcIva .= $cond.$row['observacion'];
			}
		} else {
			$txtNuevoPorcIvaLujo = 0;
		}
		
		if ($idUnidadFisica > 0) {
			if ($txtPorcIva != 0 && $txtPorcIva != $txtNuevoPorcIva) {
				$txtPorcIva = $txtNuevoPorcIva;
				$arrayMsg[] = "Se ha actualizado el Impuesto en: ".$txtPorcIva."%";
			}
			if ($txtPorcIvaLujo != 0 && $txtPorcIvaLujo != $txtNuevoPorcIvaLujo) {
				$txtPorcIvaLujo = $txtNuevoPorcIvaLujo;
				$arrayMsg[] = "Se ha actualizado el Impuesto al lujo en: ".$txtPorcIvaLujo."%";
			}
			(count($arrayMsg) > 0) ? $objResponse->alert(implode($arrayMsg,"\n")) : "";
		}
		
		$objResponse->assign("txtPorcIva","value",number_format($txtPorcIva, 2, ".", ","));
		$objResponse->assign("txtPorcIvaLujo","value",number_format($txtPorcIvaLujo, 2, ".", ","));
		$objResponse->assign("spanPorcIva","innerHTML",((strlen($spanPorcIva) > 0) ? "(".$spanPorcIva.")" : ""));
		
		// ADICIONALES
		$queryPedidoDet = sprintf("SELECT
			acc_pres.id_accesorio_presupuesto,
			acc_pres.id_presupuesto,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_pres.id_tipo_accesorio,
			(CASE acc_pres.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			acc_pres.precio_accesorio,
			acc_pres.costo_accesorio,
			acc_pres.porcentaje_iva_accesorio,
			(acc_pres.precio_accesorio + (acc_pres.precio_accesorio * acc_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_pres.iva_accesorio
		FROM an_accesorio_presupuesto acc_pres
			INNER JOIN an_accesorio acc ON (acc_pres.id_accesorio = acc.id_accesorio)
		WHERE acc_pres.id_presupuesto = %s
		ORDER BY acc_pres.id_accesorio_presupuesto ASC;",
			valTpDato($idPresupuesto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$Result1 = insertarItemAdicional($contFila, "", $rowPedidoDet['id_accesorio'], "", $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], "", "", $rowPedidoDet['id_tipo_accesorio']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		$queryPedidoDet = sprintf("SELECT
			paq_pres.id_paquete_presupuesto,
			paq_pres.id_presupuesto,
			paq_pres.id_acc_paq,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (paq_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			paq_pres.id_tipo_accesorio,
			(CASE paq_pres.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			paq_pres.precio_accesorio,
			paq_pres.costo_accesorio,
			paq_pres.porcentaje_iva_accesorio,
			(paq_pres.precio_accesorio + (paq_pres.precio_accesorio * paq_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			paq_pres.iva_accesorio
		FROM an_paquete_presupuesto paq_pres
			INNER JOIN an_acc_paq acc_paq ON (paq_pres.id_acc_paq = acc_paq.id_acc_paq)
			INNER JOIN an_accesorio acc on (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE paq_pres.id_presupuesto = %s
		ORDER BY paq_pres.id_paquete_presupuesto ASC;",
			valTpDato($idPresupuesto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$Result1 = insertarItemAdicional($contFila, "", $rowPedidoDet['id_accesorio'], $rowPedidoDet['id_acc_paq'], $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_tipo_accesorio']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		// FORMA DE PAGO
		if ($rowPresupuesto['tipo_inicial'] == 0) {
			$objResponse->script("
			byId('rbtInicialPorc').checked = true;
			byId('rbtInicialPorc').click();");
		} else {
			$objResponse->script("
			byId('rbtInicialMonto').checked = true;
			byId('rbtInicialMonto').click();");
		}
		$objResponse->assign("hddTipoInicial","value",$rowPresupuesto['tipo_inicial']);
		$objResponse->assign("txtPorcInicial","value",number_format($rowPresupuesto['porcentaje_inicial'], 2, "." , ","));
		$objResponse->assign("txtMontoInicial","value",number_format($rowPresupuesto['inicial'], 2, "." , ","));
		
		// CONTRATO A PAGARSE
		if ($rowPresupuesto['porcentaje_inicial'] < 100) {
			if ($rowPresupuesto['id_banco_financiar'] > 0) {
			} else {
				$objResponse->script("
				byId('cbxSinBancoFinanciar').checked = true;
				byId('aDesbloquearSinBancoFinanciar').style.display = 'none';
				byId('hddSinBancoFinanciar').value = 1;");
			}
		}
		$objResponse->loadCommands(cargaLstBancoFinanciar("lstBancoFinanciar", $rowPresupuesto['id_banco_financiar']));
		$lstMesesFinanciar = $rowPresupuesto['meses_financiar'];
		$txtInteresCuotaFinanciar = $rowPresupuesto['interes_cuota_financiar'];
		$txtCuotasFinanciar = numformat($rowPresupuesto['cuotas_financiar'],2);
		$lstMesesFinanciar2 = $rowPresupuesto['meses_financiar2'];
		$txtInteresCuotaFinanciar2 = $rowPresupuesto['interes_cuota_financiar2'];
		$txtCuotasFinanciar2 = numformat($rowPresupuesto['cuotas_financiar2'],2);
		$lstMesesFinanciar3 = $rowPresupuesto['meses_financiar3'];
		$txtInteresCuotaFinanciar3 = $rowPresupuesto['interes_cuota_financiar3'];
		$txtCuotasFinanciar3 = numformat($rowPresupuesto['cuotas_financiar3'],3);
		$lstMesesFinanciar4 = $rowPresupuesto['meses_financiar4'];
		$txtInteresCuotaFinanciar4 = $rowPresupuesto['interes_cuota_financiar4'];
		$txtCuotasFinanciar4 = numformat($rowPresupuesto['cuotas_financiar4'],4);
		$valores = array(
			"lstMesesFinanciar*".$rowPresupuesto['meses_financiar'],
			"txtInteresCuotaFinanciar*".$rowPresupuesto['interes_cuota_financiar'],
			"txtCuotasFinanciar*".$rowPresupuesto['cuotas_financiar'],
			"txtFechaCuotaFinanciar*".$rowPresupuesto['fecha_pago_cuota'],
			"lstMesesFinanciar2*".$rowPresupuesto['meses_financiar2'],
			"txtInteresCuotaFinanciar2*".$rowPresupuesto['interes_cuota_financiar2'],
			"txtCuotasFinanciar2*".$rowPresupuesto['cuotas_financiar2'],
			"txtFechaCuotaFinanciar2*".$rowPresupuesto['fecha_pago_cuota2'],
			"lstMesesFinanciar3*".$rowPresupuesto['meses_financiar3'],
			"txtInteresCuotaFinanciar3*".$rowPresupuesto['interes_cuota_financiar3'],
			"txtCuotasFinanciar3*".$rowPresupuesto['cuotas_financiar3'],
			"txtFechaCuotaFinanciar3*".$rowPresupuesto['fecha_pago_cuota3'],
			"lstMesesFinanciar4*".$rowPresupuesto['meses_financiar4'],
			"txtInteresCuotaFinanciar4*".$rowPresupuesto['interes_cuota_financiar4'],
			"txtCuotasFinanciar4*".$rowPresupuesto['cuotas_financiar4'],
			"txtFechaCuotaFinanciar4*".$rowPresupuesto['fecha_pago_cuota4']);
		$objResponse->loadCommands(asignarBanco($rowPresupuesto['id_banco_financiar'], $valores));
		$objResponse->loadCommands(asignarFactor($rowPresupuesto['meses_financiar'], array("lstBancoFinanciar" => $rowPresupuesto['id_banco_financiar'])));
		$objResponse->assign("txtPorcFLAT","value",number_format($rowPresupuesto['porcentaje_flat'], 2, "." , ","));
		$objResponse->assign("txtMontoFLAT","value",number_format($rowPresupuesto['monto_flat'], 2, "." , ","));
		
		// SEGURO
		$objResponse->loadCommands(cargaLstPoliza("lstPoliza", $rowPresupuesto['id_poliza']));
		$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPresupuesto['nombre_agencia_seguro']);
		$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPresupuesto['direccion_agencia_seguro']);
		$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPresupuesto['ciudad_agencia_seguro']);
		$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPresupuesto['pais_agencia_seguro']);
		$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPresupuesto['telefono_agencia_seguro']);
		$objResponse->assign("txtNumPoliza","value",$rowPresupuesto['num_poliza']);
		$objResponse->assign("txtMontoSeguro","value",number_format($rowPresupuesto['monto_seguro'], 2, "." , ","));
		$objResponse->assign("txtPeriodoPoliza","value",$rowPresupuesto['periodo_poliza']);
		$objResponse->assign("txtDeduciblePoliza","value",$rowPresupuesto['ded_poliza']);
		$objResponse->assign("txtFechaEfect","value",(($rowPresupuesto['fech_efect'] != "") ? date(spanDateFormat, strtotime($rowPresupuesto['fech_efect'])) : ""));
		$objResponse->assign("txtFechaExpi","value",(($rowPresupuesto['fech_expira'] != "") ? date(spanDateFormat, strtotime($rowPresupuesto['fech_expira'])) : ""));
		$objResponse->assign("txtInicialPoliza","value",$rowPresupuesto['inicial_poliza']);
		$objResponse->assign("txtMesesPoliza","value",$rowPresupuesto['meses_poliza']);
		$objResponse->assign("txtCuotasPoliza","value",$rowPresupuesto['cuotas_poliza']);
		
		// OTROS
		$objResponse->assign("txtMontoAnticipo","value",number_format($rowPresupuesto['anticipo'], 2, "." , ","));
		$objResponse->assign("txtMontoComplementoInicial","value",number_format($rowPresupuesto['complemento_inicial'], 2, "." , ","));
		
		// OBSERVACIONES
		$objResponse->assign("txtObservacion","innerHTML",$rowPresupuesto['observaciones']);
		
		// COMPROBACION
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300"), $rowPresupuesto['gerente_ventas']));
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteAdministracion", "3", $rowPresupuesto['administracion']));
		
		
		// BUSCA LOS DATOS DEL PRESUPUESTO DE ACCESORIOS
		$queryPresupuestoAccesorio = sprintf("SELECT * FROM an_presupuesto_accesorio pres_acc WHERE pres_acc.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$rsPresupuestoAccesorio = mysql_query($queryPresupuestoAccesorio);
		if (!$rsPresupuestoAccesorio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPresupuestoAccesorio = mysql_num_rows($rsPresupuestoAccesorio);
		$rowPresupuestoAccesorio = mysql_fetch_assoc($rsPresupuestoAccesorio);
		
		// PRESUPUESTO ACCESORIOS
		if ($totalRowsPresupuestoAccesorio > 0) {
			$objResponse->script("
			byId('fielsetPresupuestoAccesorios').style.display = '';");
			
			$objResponse->assign("hddIdPresupuestoAcc","value",$rowPresupuestoAccesorio['id_presupuesto_accesorio']);
			$objResponse->assign("txtNumeroPresupuestoAcc","value",$rowPresupuestoAccesorio['id_presupuesto_accesorio']);
			$objResponse->assign("txtSubTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'], 2, ".", ","));
			$objResponse->assign("txtTotalImpuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
			$objResponse->assign("txtTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'] + $rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
			$objResponse->script(sprintf("byId('aEditarPresupuestoAcc').href = 'an_combo_presupuesto_list.php?view=1&id=%s';",
				$idPresupuesto));
			$objResponse->script(sprintf("byId('aPresupuestoAccPDF').href = 'javascript:verVentana(\'reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s\', 960, 550);';",
				$rowPresupuestoAccesorio['id_presupuesto_accesorio']));
		}
		
		$objResponse->script("cerrarVentana = false;");
		
	} else if ($idPedido > 0) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdUnidadBasica').className = 'inputHabilitado';
		byId('txtIdUnidadFisica').className = 'inputHabilitado';
		byId('txtFechaReserva').className = 'inputHabilitado';
		byId('txtFechaEntrega').className = 'inputHabilitado';
		byId('txtFechaVenta').className = 'inputHabilitado';
		byId('txtFechaAdministracion').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		// BUSCA LOS DATOS DEL PEDIDO
		$queryPedido = sprintf("SELECT
			ped_vent.id_pedido,
			ped_vent.id_empresa,
			ped_vent.id_cliente,
			ped_vent.id_moneda,
			ped_vent.id_moneda_tasa_cambio,
			ped_vent.id_tasa_cambio,
			ped_vent.monto_tasa_cambio,
			ped_vent.fecha_tasa_cambio,
			ped_vent.id_clave_movimiento,
			ped_vent.id_presupuesto,
			pres_vent.numeracion_presupuesto,
			ped_vent.id_factura_cxc,
			ped_vent.id_unidad_fisica,
			uni_fis.id_uni_bas,
			ped_vent.numeracion_pedido,
			ped_vent.fecha,
			ped_vent.estado_pedido,
			ped_vent.asesor_ventas,
			ped_vent.gerente_ventas,
			ped_vent.fecha_gerente_ventas,
			ped_vent.administracion,
			ped_vent.fecha_administracion,
			ped_vent.precio_retoma,
			ped_vent.fecha_retoma,
			ped_vent.precio_venta,
			ped_vent.monto_descuento,
			ped_vent.porcentaje_iva,
			ped_vent.porcentaje_impuesto_lujo,
			ped_vent.tipo_inicial,
			ped_vent.porcentaje_inicial,
			ped_vent.inicial,
			ped_vent.saldo_financiar,
			ped_vent.meses_financiar,
			ped_vent.interes_cuota_financiar,
			ped_vent.cuotas_financiar,
			ped_vent.fecha_pago_cuota,
			ped_vent.meses_financiar2,
			ped_vent.interes_cuota_financiar2,
			ped_vent.cuotas_financiar2,
			ped_vent.fecha_pago_cuota2,
			ped_vent.meses_financiar3,
			ped_vent.interes_cuota_financiar3,
			ped_vent.cuotas_financiar3,
			ped_vent.fecha_pago_cuota3,
			ped_vent.meses_financiar4,
			ped_vent.interes_cuota_financiar4,
			ped_vent.cuotas_financiar4,
			ped_vent.fecha_pago_cuota4,
			ped_vent.id_banco_financiar,
			ped_vent.total_accesorio,
			ped_vent.total_adicional_contrato,
			ped_vent.total_inicial_gastos,
			ped_vent.porcentaje_flat,
			ped_vent.monto_flat,
			ped_vent.observaciones,
			ped_vent.anticipo,
			ped_vent.complemento_inicial,
			ped_vent.id_poliza,
			ped_vent.num_poliza,
			ped_vent.monto_seguro,
			ped_vent.periodo_poliza,
			ped_vent.ded_poliza,
			ped_vent.fech_efect,
			ped_vent.fech_expira,
			ped_vent.inicial_poliza,
			ped_vent.meses_poliza,
			ped_vent.cuotas_poliza,
			ped_vent.fecha_reserva_venta,
			ped_vent.fecha_entrega,
			ped_vent.forma_pago_precio_total,
			ped_vent.total_pedido,
			ped_vent.exacc1,
			ped_vent.exacc2,
			ped_vent.exacc3,
			ped_vent.exacc4,
			ped_vent.vexacc1,
			ped_vent.vexacc2,
			ped_vent.vexacc3,
			ped_vent.vexacc4,
			ped_vent.empresa_accesorio,
			adicional_contrato.nombre_agencia_seguro,
			adicional_contrato.direccion_agencia_seguro,
			adicional_contrato.ciudad_agencia_seguro,
			adicional_contrato.pais_agencia_seguro,
			adicional_contrato.telefono_agencia_seguro
		FROM an_pedido ped_vent
			LEFT JOIN an_adicionales_contrato adicional_contrato ON (ped_vent.id_pedido = adicional_contrato.id_pedido)
			LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
		WHERE ped_vent.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		if ($rowPedido['estado_pedido'] == 3) {
			$objResponse->alert("El pedido ".$rowPedido['numeracion_pedido']." está desautorizado");
		}
		
		$idEmpresa = $rowPedido['id_empresa'];
		$idCliente = $rowPedido['id_cliente'];
		$idPresupuesto = $rowPedido['id_presupuesto'];
		$idFactura = ($idFactura > 0) ? $idFactura : $rowPedido['id_factura_cxc'];
		$idUnidadFisica = $rowPedido['id_unidad_fisica'];
		$idUnidadBasica = $rowPedido['id_uni_bas'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->loadCommands(asignarEmpleado($rowPedido['asesor_ventas'], "false"));
		$objResponse->loadCommands(asignarCliente($idCliente, $idEmpresa, $estatusCliente, $condicionPago, $rowPedido['id_clave_movimiento']));
		
		// DATOS PEDIDO
		$idMonedaLocal = $rowPedido['id_moneda'];
		$idMonedaOrigen = ($rowPedido['id_moneda_tasa_cambio'] > 0) ? $rowPedido['id_moneda_tasa_cambio'] : $rowPedido['id_moneda'];
		$txtTasaCambio = ($rowPedido['monto_tasa_cambio'] >= 0) ? $rowPedido['monto_tasa_cambio'] : 0;
		
		$objResponse->assign("txtIdPedido","value",$rowPedido['id_pedido']);
		$objResponse->assign("txtNumeroPedidoPropio","value",$rowPedido['numeracion_pedido']);
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat, strtotime($rowPedido['fecha'])));
		$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
		$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
		$objResponse->assign("txtFechaTasaCambio","value",(($rowPedido['fecha_tasa_cambio'] != "") ? date(spanDateFormat, strtotime($rowPedido['fecha_tasa_cambio'])) : ""));
		$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);
		$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $rowPedido['id_tasa_cambio']));
		$objResponse->assign("hddIdPresupuestoVenta","value",$rowPedido['id_presupuesto']);
		$objResponse->assign("txtNumeroPresupuestoVenta","value",$rowPedido['numeracion_presupuesto']);
		$objResponse->script(sprintf("
		selectedOption('lstTipoClave',3);
		
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','2','3','','1','%s','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'-1\');\"');
		}",
			$rowPedido['id_clave_movimiento']));
		
		if ($idFactura > 0) {
			// BUSCA LOS DATOS DE LA FACTURA
			$queryFactura = sprintf("SELECT cxc_fact.*,
				(CASE cxc_fact.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS estado_fact_vent
			FROM cj_cc_encabezadofactura cxc_fact
			WHERE cxc_fact.idFactura = %s
				AND cxc_fact.anulada LIKE 'NO';",
				valTpDato($idFactura, "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowFactura = mysql_fetch_array($rsFactura);
			
			$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
				$rowFactura['idFactura']);
			$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>", $rowFactura['idFactura']);
			
			$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo4\" width=\"100%\">".
			"<tr align=\"center\">".
				"<td height=\"25\" width=\"25\"><img src=\"../img/iconos/exclamation.png\"/></td>".
				"<td>".
					"<table>".
					"<tr align=\"right\">".
						"<td nowrap=\"nowrap\">"."Edición de la Factura Nro. "."</td>".
						"<td nowrap=\"nowrap\">".$aVerDcto."</td>".
						"<td>".$rowFactura['numeroFactura']."</td>".
					"</tr>".
					"</table>".
				"</td>".
			"</tr>".
			"</table>";
			$objResponse->assign("tdMsjPedido","innerHTML",$html);
		}
		
		// UNIDAD FISICA
		$objResponse->loadCommands(asignarUnidadFisica($idUnidadFisica));
		$objResponse->assign("hddIdUnidadFisicaAnterior","value",$idUnidadFisica);
		
		// VENTA DE LA UNIDAD
		$txtPorcIva = $rowPedido['porcentaje_iva'];
		$txtPorcIvaLujo = $rowPedido['porcentaje_impuesto_lujo'];
		
		$objResponse->assign("txtPrecioBase","value",number_format($rowPedido['precio_venta'], 2, "." , ","));
		$objResponse->assign("txtDescuento","value",number_format($rowPedido['monto_descuento'], 2, "." , ","));
		
		// VERIFICA SI TIENE IMPUESTO
		if (getmysql(sprintf("SELECT UPPER(isan_uni_bas) FROM an_uni_bas WHERE id_uni_bas = %s;", valTpDato($idUnidadBasica,"int"))) == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (6)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($row = mysql_fetch_assoc($rs)) {
				$txtNuevoPorcIva += $row['iva'];
				$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
				$spanPorcIva .= $cond.$row['observacion'];
			}
		} else {
			$txtNuevoPorcIva = 0;
			$spanPorcIva .= "Exento";
		}
		
		if (getmysql(sprintf("SELECT impuesto_lujo FROM an_uni_bas WHERE id_uni_bas = %s;", valTpDato($idUnidadBasica,"int"))) == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (2)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			while ($row = mysql_fetch_assoc($rs)) {
				$txtNuevoPorcIvaLujo += $row['iva'];
				$cond = (strlen($spanPorcIva) > 0) ? " e " : "";
				$spanPorcIva .= $cond.$row['observacion'];
			}
		} else {
			$txtNuevoPorcIvaLujo = 0;
		}
		
		if ($idUnidadFisica > 0) {
			if ($txtPorcIva != 0 && $txtPorcIva != $txtNuevoPorcIva) {
				$txtPorcIva = $txtNuevoPorcIva;
				$arrayMsg[] = "Se ha actualizado el Impuesto en: ".$txtPorcIva."%";
			}
			if ($txtPorcIvaLujo != 0 && $txtPorcIvaLujo != $txtNuevoPorcIvaLujo) {
				$txtPorcIvaLujo = $txtNuevoPorcIvaLujo;
				$arrayMsg[] = "Se ha actualizado el Impuesto al lujo en: ".$txtPorcIvaLujo."%";
			}
			(count($arrayMsg) > 0) ? $objResponse->alert(implode($arrayMsg,"\n")) : "";
		}
		
		$objResponse->assign("txtPorcIva","value",number_format($txtPorcIva, 2, ".", ","));
		$objResponse->assign("txtPorcIvaLujo","value",number_format($txtPorcIvaLujo, 2, ".", ","));
		$objResponse->assign("spanPorcIva","innerHTML",((strlen($spanPorcIva) > 0) ? "(".$spanPorcIva.")" : ""));
		
		// ADICIONALES
		$queryPedidoDet = sprintf("SELECT
			acc_ped.id_accesorio_pedido,
			acc_ped.id_pedido,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_ped.id_tipo_accesorio,
			(CASE acc_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			acc_ped.precio_accesorio,
			acc_ped.costo_accesorio,
			acc_ped.porcentaje_iva_accesorio,
			(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_ped.iva_accesorio,
			acc_ped.monto_pagado,
			acc_ped.id_condicion_pago,
			acc_ped.id_condicion_mostrar,
			acc_ped.estatus_accesorio_pedido
		FROM an_accesorio_pedido acc_ped
			INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
		WHERE acc_ped.id_pedido = %s
		ORDER BY acc_ped.id_accesorio_pedido ASC;",
			valTpDato($idPedido, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$idPedidoAdicional = ($permitirEditarFactura == true && $idFactura > 0) ? "" : $rowPedidoDet['id_accesorio_pedido'];
			
			$Result1 = insertarItemAdicional($contFila, $idPedidoAdicional, $rowPedidoDet['id_accesorio'], "", $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_tipo_accesorio']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		$queryPedidoDet = sprintf("SELECT
			paq_ped.id_paquete_pedido,
			paq_ped.id_pedido,
			paq_ped.id_acc_paq,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (paq_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			paq_ped.id_tipo_accesorio,
			(CASE paq_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			paq_ped.precio_accesorio,
			paq_ped.costo_accesorio,
			paq_ped.porcentaje_iva_accesorio,
			(paq_ped.precio_accesorio + (paq_ped.precio_accesorio * paq_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			paq_ped.iva_accesorio,
			paq_ped.monto_pagado,
			paq_ped.id_condicion_pago,
			paq_ped.id_condicion_mostrar
		FROM an_paquete_pedido paq_ped
			INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.id_acc_paq)
			INNER JOIN an_accesorio acc on (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE paq_ped.id_pedido = %s
		ORDER BY paq_ped.id_paquete_pedido ASC;",
			valTpDato($idPedido, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj1 = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$idPedidoAdicional = ($permitirEditarFactura == true && $idFactura > 0) ? "" : $rowPedidoDet['id_paquete_pedido'];
			
			$Result1 = insertarItemAdicional($contFila, $idPedidoAdicional, $rowPedidoDet['id_accesorio'], $rowPedidoDet['id_acc_paq'], $rowPedidoDet['precio_con_iva'], $rowPedidoDet['costo_accesorio'], $rowPedidoDet['monto_pagado'], $rowPedidoDet['porcentaje_iva_accesorio'], $rowPedidoDet['iva_accesorio'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_tipo_accesorio']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
		
		// FORMA DE PAGO
		if ($rowPedido['tipo_inicial'] == 0) {
			$objResponse->script("
			byId('rbtInicialPorc').checked = true;
			byId('rbtInicialPorc').click();");
		} else {
			$objResponse->script("
			byId('rbtInicialMonto').checked = true;
			byId('rbtInicialMonto').click();");
		}
		$objResponse->assign("hddTipoInicial","value",$rowPedido['tipo_inicial']);
		$objResponse->assign("txtPorcInicial","value",number_format($rowPedido['porcentaje_inicial'], 2, "." , ","));
		$objResponse->assign("txtMontoInicial","value",number_format($rowPedido['inicial'], 2, "." , ","));
		
		// CONTRATO A PAGARSE
		if ($rowPedido['porcentaje_inicial'] < 100) {
			if ($rowPedido['id_banco_financiar'] > 0) {
			} else {
				$objResponse->script("
				byId('cbxSinBancoFinanciar').checked = true;
				byId('aDesbloquearSinBancoFinanciar').style.display = 'none';
				byId('hddSinBancoFinanciar').value = 1;");
			}
		}
		$objResponse->loadCommands(cargaLstBancoFinanciar("lstBancoFinanciar", $rowPedido['id_banco_financiar']));
		$lstMesesFinanciar = $rowPedido['meses_financiar'];
		$txtInteresCuotaFinanciar = $rowPedido['interes_cuota_financiar'];
		$txtCuotasFinanciar = numformat($rowPedido['cuotas_financiar'],2);
		$lstMesesFinanciar2 = $rowPedido['meses_financiar2'];
		$txtInteresCuotaFinanciar2 = $rowPedido['interes_cuota_financiar2'];
		$txtCuotasFinanciar2 = numformat($rowPedido['cuotas_financiar2'],2);
		$lstMesesFinanciar3 = $rowPedido['meses_financiar3'];
		$txtInteresCuotaFinanciar3 = $rowPedido['interes_cuota_financiar3'];
		$txtCuotasFinanciar3 = numformat($rowPedido['cuotas_financiar3'],3);
		$lstMesesFinanciar4 = $rowPedido['meses_financiar4'];
		$txtInteresCuotaFinanciar4 = $rowPedido['interes_cuota_financiar4'];
		$txtCuotasFinanciar4 = numformat($rowPedido['cuotas_financiar4'],4);
		$valores = array(
			"lstMesesFinanciar*".$rowPedido['meses_financiar'],
			"txtInteresCuotaFinanciar*".$rowPedido['interes_cuota_financiar'],
			"txtCuotasFinanciar*".$rowPedido['cuotas_financiar'],
			"txtFechaCuotaFinanciar*".$rowPedido['fecha_pago_cuota'],
			"lstMesesFinanciar2*".$rowPedido['meses_financiar2'],
			"txtInteresCuotaFinanciar2*".$rowPedido['interes_cuota_financiar2'],
			"txtCuotasFinanciar2*".$rowPedido['cuotas_financiar2'],
			"txtFechaCuotaFinanciar2*".$rowPedido['fecha_pago_cuota2'],
			"lstMesesFinanciar3*".$rowPedido['meses_financiar3'],
			"txtInteresCuotaFinanciar3*".$rowPedido['interes_cuota_financiar3'],
			"txtCuotasFinanciar3*".$rowPedido['cuotas_financiar3'],
			"txtFechaCuotaFinanciar3*".$rowPedido['fecha_pago_cuota3'],
			"lstMesesFinanciar4*".$rowPedido['meses_financiar4'],
			"txtInteresCuotaFinanciar4*".$rowPedido['interes_cuota_financiar4'],
			"txtCuotasFinanciar4*".$rowPedido['cuotas_financiar4'],
			"txtFechaCuotaFinanciar4*".$rowPedido['fecha_pago_cuota4']);
		$objResponse->loadCommands(asignarBanco($rowPedido['id_banco_financiar'], $valores));
		$objResponse->loadCommands(asignarFactor($rowPedido['meses_financiar'], array("lstBancoFinanciar" => $rowPedido['id_banco_financiar'])));
		$objResponse->assign("txtPorcFLAT","value",number_format($rowPedido['porcentaje_flat'], 2, "." , ","));
		$objResponse->assign("txtMontoFLAT","value",number_format($rowPedido['monto_flat'], 2, "." , ","));
		
		// SEGURO
		$objResponse->loadCommands(cargaLstPoliza("lstPoliza", $rowPedido['id_poliza']));
		$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPedido['nombre_agencia_seguro']);
		$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPedido['direccion_agencia_seguro']);
		$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPedido['ciudad_agencia_seguro']);
		$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPedido['pais_agencia_seguro']);
		$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPedido['telefono_agencia_seguro']);
		$objResponse->assign("txtNumPoliza","value",$rowPedido['num_poliza']);
		$objResponse->assign("txtMontoSeguro","value",number_format($rowPedido['monto_seguro'], 2, "." , ","));
		$objResponse->assign("txtPeriodoPoliza","value",$rowPedido['periodo_poliza']);
		$objResponse->assign("txtDeduciblePoliza","value",$rowPedido['ded_poliza']);
		$objResponse->assign("txtFechaEfect","value",(($rowPedido['fech_efect'] != "") ? date(spanDateFormat, strtotime($rowPedido['fech_efect'])) : ""));
		$objResponse->assign("txtFechaExpi","value",(($rowPedido['fech_expira'] != "") ? date(spanDateFormat, strtotime($rowPedido['fech_expira'])) : ""));
		$objResponse->assign("txtInicialPoliza","value",$rowPedido['inicial_poliza']);
		$objResponse->assign("txtMesesPoliza","value",$rowPedido['meses_poliza']);
		$objResponse->assign("txtCuotasPoliza","value",$rowPedido['cuotas_poliza']);
		
		// OTROS
		$objResponse->assign("txtMontoAnticipo","value",number_format($rowPedido['anticipo'], 2, "." , ","));
		$objResponse->assign("txtMontoComplementoInicial","value",number_format($rowPedido['complemento_inicial'], 2, "." , ","));
		
		// OBSERVACIONES
		$objResponse->assign("txtObservacion","innerHTML",$rowPedido['observaciones']);
		
		// COMPROBACION
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300"), $rowPedido['gerente_ventas']));
		$objResponse->assign("txtFechaVenta","value",date(spanDateFormat, strtotime($rowPedido['fecha_gerente_ventas'])));
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteAdministracion", "3", $rowPedido['administracion']));
		$objResponse->assign("txtFechaAdministracion","value",date(spanDateFormat, strtotime($rowPedido['fecha_administracion'])));
		
		$objResponse->assign("txtFechaReserva","value",date(spanDateFormat, strtotime($rowPedido['fecha_reserva_venta'])));
		$objResponse->assign("txtFechaEntrega","value",date(spanDateFormat, strtotime($rowPedido['fecha_entrega'])));
		
		
		// BUSCA LOS DATOS DEL PRESUPUESTO DE ACCESORIOS
		$queryPresupuestoAccesorio = sprintf("SELECT * FROM an_presupuesto_accesorio pres_acc WHERE pres_acc.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$rsPresupuestoAccesorio = mysql_query($queryPresupuestoAccesorio);
		if (!$rsPresupuestoAccesorio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPresupuestoAccesorio = mysql_num_rows($rsPresupuestoAccesorio);
		$rowPresupuestoAccesorio = mysql_fetch_assoc($rsPresupuestoAccesorio);
		
		// PRESUPUESTO ACCESORIOS
		if ($totalRowsPresupuestoAccesorio > 0) {
			$objResponse->script("
			byId('fielsetPresupuestoAccesorios').style.display = '';");
			
			$objResponse->assign("hddIdPresupuestoAcc","value",$rowPresupuestoAccesorio['id_presupuesto_accesorio']);
			$objResponse->assign("txtNumeroPresupuestoAcc","value",$rowPresupuestoAccesorio['id_presupuesto_accesorio']);
			$objResponse->assign("txtSubTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'], 2, ".", ","));
			$objResponse->assign("txtTotalImpuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
			$objResponse->assign("txtTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'] + $rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
			$objResponse->script(sprintf("byId('aEditarPresupuestoAcc').href = 'an_combo_presupuesto_list.php?view=1&id=%s';",
				$idPresupuesto));
			$objResponse->script(sprintf("byId('aPresupuestoAccPDF').href = 'javascript:verVentana(\'reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s\', 960, 550);';",
				$rowPresupuestoAccesorio['id_presupuesto_accesorio']));
		}
		
		$objResponse->script("cerrarVentana = false;");
	} else {
		if ($hddTipoPedido == "i") {
			$objResponse->script("
			byId('trBuscarPresupuesto').style.display = '';
			byId('trFieldsetCliente').style.display = 'none';
			byId('trFieldsetUnidadFisica').style.display = 'none';
			byId('trFieldsetVehiculoUsado').style.display = 'none';
			byId('trBtnGuardar').style.display = 'none';
			
			byId('txtBuscarPresupuesto').className = 'inputHabilitado';
			byId('txtBuscarPresupuesto').focus();");
		}
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdUnidadBasica').className = 'inputHabilitado';
		byId('txtIdUnidadFisica').className = 'inputHabilitado';
		byId('txtFechaReserva').className = 'inputHabilitado';
		byId('txtFechaEntrega').className = 'inputHabilitado';
		byId('txtFechaVenta').className = 'inputHabilitado';
		byId('txtFechaAdministracion').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("
		byId('rbtTipoPagoCredito').disabled = true;
		
		byId('rbtInicialPorc').checked = true;
		byId('rbtInicialPorc').click();
		
		selectedOption('lstTipoClave',3);
		
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','2','3','1','1','-1','onchange=\"byId(\'aDesbloquearClaveMovimiento\').click(); selectedOption(this.id, \'-1\');\"');
		}");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$idMoneda = $rowMoneda['idmoneda'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts'], "false"));
		
		// DATOS PEDIDO
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstMoneda($idMoneda));
		$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
		$objResponse->assign("hddIdMoneda","value",$idMoneda);
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", "3", "", "1"));
		
		// CONTRATO A PAGARSE
		$objResponse->loadCommands(cargaLstBancoFinanciar("lstBancoFinanciar"));
		
		// SEGURO
		$objResponse->loadCommands(cargaLstPoliza("lstPoliza"));
		
		// COMPROBACION
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300")));
		$objResponse->loadCommands(cargaLstEmpleado("lstGerenteAdministracion", "3"));
	}
	
	$objResponse->script("
	jQuery(function($){
		$(\"#txtFechaReserva\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaEntrega\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaEfect\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaExpi\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaVenta\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaAdministracion\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaReserva\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	new JsDatePick({
		useMode:2,
		target:\"txtFechaEntrega\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaEfect\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	new JsDatePick({
		useMode:2,
		target:\"txtFechaExpi\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	new JsDatePick({
		useMode:2,
		target:\"txtFechaVenta\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});
	
	new JsDatePick({
		useMode:2,
		target:\"txtFechaAdministracion\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});");
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function eliminarAdicionalLote($frmListaMotivo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaMotivo['cbxItmAdicional'])) {
		foreach ($frmListaMotivo['cbxItmAdicional'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmAdicional:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function formCliente($idCliente, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCliente['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjImpuesto = $frmCliente['cbxImpuesto'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObjImpuesto)) {
		foreach($arrayObjImpuesto as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmImpuesto:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($idCliente > 0) {
		if (!xvalidaAcceso($objResponse,"cc_cliente_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarCliente').click();"); return $objResponse; }
		
		// BUSCA LOS DATOS DEL CLIENTE
		$query = sprintf("SELECT *,
			(CASE
				WHEN lci IS NOT NULL THEN
					CONCAT_WS('-',lci,ci)
				ELSE 
					ci
			END) AS ci_cliente,
			
			(CASE
				WHEN lci2 IS NOT NULL THEN
					CONCAT_WS('-',lci2,cicontacto)
				ELSE 
					cicontacto
			END) AS ci_contacto
		FROM cj_cc_cliente
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdCliente","value",$row['id']);
		
		$tipoPago = ($row['credito'] == "si") ? "0" : "1";
		$objResponse->loadCommands(cargaLstCredito($tipoPago));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", $tipoPago, "1", $row['id_clave_movimiento_predeterminado']));
		
		$objResponse->script("selectedOption('lstTipo', '".$row['tipo']."')");
		$objResponse->script("byId('lstTipo').onchange = function() { selectedOption(this.id, '".$row['tipo']."'); }");
		$objResponse->assign("txtCedula","value",$row['ci_cliente']);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre']));
		$objResponse->assign("txtNit","value",$row['nit']);
		$objResponse->assign("txtApellido","value",utf8_encode($row['apellido']));
		$objResponse->assign("txtLicencia","value",utf8_encode($row['licencia']));
		$objResponse->script("selectedOption('lstContribuyente', '".$row['contribuyente']."')");
		
		$arrayDireccion = explode(";",utf8_encode($row['direccion']));
		$objResponse->assign("txtUrbanizacion","value",trim($arrayDireccion[0]));
		$objResponse->assign("txtCalle","value",trim($arrayDireccion[1]));
		$objResponse->assign("txtCasa","value",trim($arrayDireccion[2]));
		$objResponse->assign("txtMunicipio","value",trim($arrayDireccion[3]));
		$objResponse->assign("txtCiudad","value",utf8_encode($row['ciudad']));
		$objResponse->assign("txtEstado","value",utf8_encode($row['estado']));
		$objResponse->assign("txtTelefono","value",$row['telf']);
		$objResponse->assign("txtOtroTelefono","value",$row['otrotelf']);
		$objResponse->assign("txtCorreo","value",$row['correo']);
		
		$objResponse->assign("txtUrbanizacionPostalCliente","value",utf8_encode($row['urbanizacion_postal']));
		$objResponse->assign("txtCallePostalCliente","value",utf8_encode($row['calle_postal']));
		$objResponse->assign("txtCasaPostalCliente","value",utf8_encode($row['casa_postal']));
		$objResponse->assign("txtMunicipioPostalCliente","value",utf8_encode($row['municipio_postal']));
		$objResponse->assign("txtCiudadPostalCliente","value",utf8_encode($row['ciudad_postal']));
		$objResponse->assign("txtEstadoPostalCliente","value",utf8_encode($row['estado_postal']));
		
		
		$objResponse->script("selectedOption('lstReputacionCliente', '".((strlen($row['reputacionCliente']) > 0) ? $row['reputacionCliente'] : "CLIENTE B")."');");
		$objResponse->script("selectedOption('lstTipoCliente', '".((strlen($row['tipocliente']) > 0) ? $row['tipocliente'] : "Repuestos")."');");
		$objResponse->script("selectedOption('lstDescuento', '".$row['descuento']."');");
		$objResponse->script("
		byId('lstDescuento').onchange = function() {
			selectedOption(this.id, ".$row['descuento'].");
		}");
		$objResponse->script("selectedOption('lstEstatus', '".$row['status']."');");
		$objResponse->script("byId('cbxPagaImpuesto').checked = ".(($row['paga_impuesto'] == "1") ? 'true' : 'false'));
		$objResponse->script("byId('cbxBloquearVenta').checked = ".(($row['bloquea_venta'] == "1") ? 'true' : 'false'));
		
		$objResponse->assign("txtFechaCreacion","value",date(spanDateFormat, strtotime($row['fcreacion'])));
		$objResponse->assign("txtFechaDesincorporar","value",date(spanDateFormat, strtotime($row['fdesincorporar'])));
		
		$objResponse->assign("txtCedulaContacto","value",$row['ci_contacto']);
		$objResponse->assign("txtNombreContacto","value",utf8_encode($row['contacto']));
		$objResponse->assign("txtTelefonoContacto","value",$row['telfcontacto']);
		$objResponse->assign("txtCorreoContacto","value",$row['correocontacto']);
		
		$queryClienteEmpresa = sprintf("SELECT * FROM cj_cc_cliente_empresa cliente_emp
		WHERE cliente_emp.id_cliente = %s
		ORDER BY cliente_emp.id_empresa ASC;",
			valTpDato($idCliente, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClienteEmpresa = mysql_fetch_array($rsClienteEmpresa)) {
			$Result1 = insertarItemClienteEmpresa($contFila, $rowClienteEmpresa['id_cliente_empresa']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		$queryClienteImpuesto = sprintf("SELECT * FROM cj_cc_cliente_impuesto_exento cliente_impuesto_exento WHERE cliente_impuesto_exento.id_cliente = %s;",
			valTpDato($idCliente, "int"));
		$rsClienteImpuesto = mysql_query($queryClienteImpuesto);
		if (!$rsClienteImpuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = NULL;
		while ($rowClienteImpuesto = mysql_fetch_assoc($rsClienteImpuesto)) {
			$Result1 = insertarItemImpuesto($contFila, $rowClienteImpuesto['id_cliente_impuesto_exento'], $rowClienteImpuesto['id_impuesto']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObjImpuesto[] = $contFila;
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"cc_cliente_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarCliente').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstCredito("1"));
		$objResponse->call("selectedOption","lstContribuyente","No");
		$objResponse->call("selectedOption","lstEstatus","Activo");
		$objResponse->script("byId('lstTipo').onchange = function() { }");
		$objResponse->assign("txtFechaCreacion","value",date(spanDateFormat));
		$objResponse->assign("txtFechaDesincorporar","value",date(spanDateFormat,dateAddLab(strtotime(date(spanDateFormat)),364,false)));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", "19"));
		$objResponse->call("selectedOption","lstDescuento",0);
		$objResponse->script("
		byId('lstDescuento').onchange = function() {
			selectedOption(this.id, ".(0).");
		}");
		$objResponse->call("selectedOption","lstTipoCliente","Repuestos");
		$objResponse->call("selectedOption","lstReputacionCliente","CLIENTE B");
		
		$objResponse->script("xajax_insertarClienteEmpresa(".$idEmpresa.", xajax.getFormValues('frmCliente'));");
	}
	
	$objResponse->script("
	byId('aNuevoImpuesto').style.display = 'none';
	byId('btnEliminarImpuesto').style.display = 'none';");
	
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

function guardarCliente($frmCliente, $frmListaCliente) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;
	global $spanEstado;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCliente['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjImpuesto = $frmCliente['cbxImpuesto'];
	
	mysql_query("START TRANSACTION;");
	
	$idCliente = $frmCliente['hddIdCliente'];
	
	switch($frmCliente['lstTipo']) {
		case 1 :
			$lstTipo = "Natural";
			$arrayValidar = $arrayValidarCI;
			break;
		case 2 :
			$lstTipo = "Juridico";
			$arrayValidar = $arrayValidarRIF;
			break;
	}
	
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmCliente['txtCedula'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtCedula').className = 'inputErrado'");
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$arrayValidar = $arrayValidarNIT;
	if (isset($arrayValidar)) {
		if (strlen($frmCliente['txtNit']) > 0) {
			$valido = false;
			foreach ($arrayValidar as $indice => $valor) {
				if (preg_match($valor, $frmCliente['txtNit'])) {
					$valido = true;
				}
			}
			
			if ($valido == false) {
				$objResponse->script("byId('txtNit').className = 'inputErrado'");
				return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
			}
		}
	}
	
	$arrayValidar = array_merge($arrayValidarCI, $arrayValidarRIF);
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmCliente['txtCedulaContacto'])) {
				$valido = true;
			}
		}
		
		if ($valido == false && strlen($frmCliente['txtCedulaContacto']) > 0) {
			$objResponse->script("byId('txtCedulaContacto').className = 'inputErrado'");
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$txtCiCliente = explode("-", $frmCliente['txtCedula']);
	if (is_numeric($txtCiCliente[0]) == true) {
		$txtCiCliente = implode("-",$txtCiCliente);
	} else {
		$txtLciCliente = $txtCiCliente[0];
		array_shift($txtCiCliente);
		$txtCiCliente = implode("-",$txtCiCliente);
	}
	
	$txtCiContacto = explode("-", $frmCliente['txtCedulaContacto']);
	if (is_numeric($txtCiContacto[0]) == true) {
		$txtCiContacto = implode("-",$txtCiContacto);
	} else {
		$txtLciContacto = $txtCiContacto[0];
		array_shift($txtCiContacto);
		$txtCiContacto = implode("-",$txtCiContacto);
	}
	
	// VERIFICA QUE NO EXISTA LA CEDULA
	$query = sprintf("SELECT * FROM cj_cc_cliente
	WHERE ((lci IS NULL AND %s IS NULL AND ci LIKE %s)
			OR (lci IS NOT NULL AND lci LIKE %s AND ci LIKE %s))
		AND (id <> %s OR %s IS NULL);",
		valTpDato($txtLciCliente, "text"),
		valTpDato($txtCiCliente, "text"),
		valTpDato($txtLciCliente, "text"),
		valTpDato($txtCiCliente, "text"),
		valTpDato($idCliente, "int"),
		valTpDato($idCliente, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		return $objResponse->alert("Ya existe la ".$spanClienteCxC." ingresada");
	}
	
	$frmCliente['txtUrbanizacion'] = trim(str_replace(",", "", $frmCliente['txtUrbanizacion']));
	$frmCliente['txtCalle'] = trim(str_replace(",", "", $frmCliente['txtCalle']));
	$frmCliente['txtCasa'] = trim(str_replace(",", "", $frmCliente['txtCasa']));
	$frmCliente['txtMunicipio'] = trim(str_replace(",", "", $frmCliente['txtMunicipio']));
	$frmCliente['txtCiudad'] = trim(str_replace(",", "", $frmCliente['txtCiudad']));
	$frmCliente['txtEstado'] = trim(str_replace(",", "", $frmCliente['txtEstado']));
	
	$txtDireccion = implode("; ", array(
		$frmCliente['txtUrbanizacion'],
		$frmCliente['txtCalle'],
		$frmCliente['txtCasa'],
		$frmCliente['txtMunicipio'],
		$frmCliente['txtCiudad'],
		((strlen($frmCliente['txtEstado']) > 0) ? $spanEstado : "")." ".$frmCliente['txtEstado']));
	
	$lstCredito = ($frmCliente['lstCredito'] == "0") ? "si" : "no";
	$cbxPagaImpuesto = (isset($frmCliente['cbxPagaImpuesto'])) ? 1 : 0;
	$cbxBloquearVenta = (isset($frmCliente['cbxBloquearVenta'])) ? 1 : 0;

	if ($idCliente > 0) {
		if (!((($frmCliente['lstCredito'] == "0" || $frmCliente['lstCredito'] == "Si") && xvalidaAcceso($objResponse,"cc_clientes_credito","editar"))
		|| (($frmCliente['lstCredito'] != "0" || $frmCliente['lstCredito'] != "Si") && xvalidaAcceso($objResponse,"cc_clientes_contado","editar")))) {
			return $objResponse;
		}
		
		$updateSQL = sprintf("UPDATE cj_cc_cliente SET
			tipo = %s,
			nombre = %s,
			apellido = %s,
			lci = %s,
			ci = %s,
			nit = %s,
			contribuyente = %s,
			urbanizacion = %s,
			calle = %s,
			casa = %s,
			municipio = %s,
			ciudad = %s,
			estado = %s,
			direccion = %s,
			telf = %s,
			otrotelf = %s,
			correo = %s,
			urbanizacion_postal = %s,
			calle_postal = %s,
			casa_postal = %s,
			municipio_postal = %s,
			ciudad_postal = %s,
			estado_postal = %s,
			contacto = %s,
			lci2 = %s,
			cicontacto = %s,
			telfcontacto = %s,
			correocontacto = %s,
			reputacionCliente = %s,
			descuento = %s,
			fcreacion = %s,
			status = %s,
			credito = %s,
			tipocliente = %s,
			fdesincorporar = %s,
			id_clave_movimiento_predeterminado = %s,
			licencia = %s,
			paga_impuesto = %s,
			bloquea_venta = %s,
			tipo_cuenta_cliente = %s
		WHERE id = %s;",
			valTpDato($lstTipo, "text"),
			valTpDato($frmCliente['txtNombre'], "text"),
			valTpDato($frmCliente['txtApellido'], "text"),
			valTpDato($txtLciCliente, "text"),
			valTpDato($txtCiCliente, "text"),
			valTpDato($frmCliente['txtNit'], "text"),
			valTpDato($frmCliente['lstContribuyente'], "text"),
			valTpDato($frmCliente['txtUrbanizacion'], "text"),
			valTpDato($frmCliente['txtCalle'], "text"),
			valTpDato($frmCliente['txtCasa'], "text"),
			valTpDato($frmCliente['txtMunicipio'], "text"),
			valTpDato($frmCliente['txtCiudad'], "text"),
			valTpDato($frmCliente['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmCliente['txtTelefono'], "text"),
			valTpDato($frmCliente['txtOtroTelefono'], "text"),
			valTpDato($frmCliente['txtCorreo'], "text"),
			valTpDato($frmCliente['txtUrbanizacionPostalCliente'], "text"),
			valTpDato($frmCliente['txtCallePostalCliente'], "text"),
			valTpDato($frmCliente['txtCasaPostalCliente'], "text"),
			valTpDato($frmCliente['txtMunicipioPostalCliente'], "text"),
			valTpDato($frmCliente['txtCiudadPostalCliente'], "text"),
			valTpDato($frmCliente['txtEstadoPostalCliente'], "text"),
			valTpDato($frmCliente['txtNombreContacto'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtTelefonoContacto'], "text"),
			valTpDato($frmCliente['txtCorreoContacto'], "text"),
			valTpDato($frmCliente['lstReputacionCliente'], "text"),
			valTpDato($frmCliente['lstDescuento'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaCreacion'])),"date"),
			valTpDato($frmCliente['lstEstatus'], "text"),
			valTpDato($lstCredito, "text"),
			valTpDato($frmCliente['lstTipoCliente'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaDesincorporar'])),"date"),
			valTpDato($frmCliente['lstClaveMovimiento'], "int"),
			valTpDato($frmCliente['txtLicencia'], "text"),
			valTpDato($cbxPagaImpuesto, "int"),
			valTpDato($cbxBloquearVenta, "int"),
			valTpDato(2, "int"), // 1 = Prospecto, 2 = Cliente
			valTpDato($idCliente, "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!((($frmCliente['lstCredito'] == "0" || $frmCliente['lstCredito'] == "Si") && xvalidaAcceso($objResponse,"cc_clientes_credito","insertar"))
		|| (($frmCliente['lstCredito'] != "0" || $frmCliente['lstCredito'] != "Si") && xvalidaAcceso($objResponse,"cc_clientes_contado","insertar")))) {
			return $objResponse;
		}
		
		$insertSQL = sprintf("INSERT INTO cj_cc_cliente (tipo, nombre, apellido, lci, ci, nit, contribuyente, urbanizacion, calle, casa, municipio, ciudad, estado, direccion, telf, otrotelf, correo, urbanizacion_postal, calle_postal, casa_postal, municipio_postal, ciudad_postal, estado_postal, contacto, lci2, cicontacto, telfcontacto, correocontacto, reputacionCliente, descuento, fcreacion, status, credito, tipocliente, fdesincorporar, id_clave_movimiento_predeterminado, licencia, paga_impuesto, bloquea_venta, tipo_cuenta_cliente)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($lstTipo, "text"),
			valTpDato($frmCliente['txtNombre'], "text"),
			valTpDato($frmCliente['txtApellido'], "text"),
			valTpDato($txtLciCliente, "text"),
			valTpDato($txtCiCliente, "text"),
			valTpDato($frmCliente['txtNit'], "text"),
			valTpDato($frmCliente['lstContribuyente'], "text"),
			valTpDato($frmCliente['txtUrbanizacion'], "text"),
			valTpDato($frmCliente['txtCalle'], "text"),
			valTpDato($frmCliente['txtCasa'], "text"),
			valTpDato($frmCliente['txtMunicipio'], "text"),
			valTpDato($frmCliente['txtCiudad'], "text"),
			valTpDato($frmCliente['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmCliente['txtTelefono'], "text"),
			valTpDato($frmCliente['txtOtroTelefono'], "text"),
			valTpDato($frmCliente['txtCorreo'], "text"),
			valTpDato($frmCliente['txtUrbanizacionPostalCliente'], "text"),
			valTpDato($frmCliente['txtCallePostalCliente'], "text"),
			valTpDato($frmCliente['txtCasaPostalCliente'], "text"),
			valTpDato($frmCliente['txtMunicipioPostalCliente'], "text"),
			valTpDato($frmCliente['txtCiudadPostalCliente'], "text"),
			valTpDato($frmCliente['txtEstadoPostalCliente'], "text"),
			valTpDato($frmCliente['txtNombreContacto'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtTelefonoContacto'], "text"),
			valTpDato($frmCliente['txtCorreoContacto'], "text"),
			valTpDato($frmCliente['lstReputacionCliente'], "text"),
			valTpDato($frmCliente['lstDescuento'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaCreacion'])),"date"),
			valTpDato($frmCliente['lstEstatus'], "text"),
			valTpDato($lstCredito, "text"),
			valTpDato($frmCliente['lstTipoCliente'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaDesincorporar'])),"date"),
			valTpDato($frmCliente['lstClaveMovimiento'], "int"),
			valTpDato($frmCliente['txtLicencia'], "text"),
			valTpDato($cbxPagaImpuesto, "int"),
			valTpDato($cbxBloquearVenta, "int"),
			valTpDato(2, "int")); // 1 = Prospecto, 2 = Cliente
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idCliente = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICA SI LAS EMPRESAS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryClienteEmpresa = sprintf("SELECT * FROM cj_cc_cliente_empresa cliente_emp
	WHERE id_cliente = %s;",
		valTpDato($idCliente, "int"));
	$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
	if (!$rsClienteEmpresa) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowClienteEmpresa = mysql_fetch_assoc($rsClienteEmpresa)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if ($rowClienteEmpresa['id_cliente_empresa'] == $frmCliente['hddIdClienteEmpresa'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM cj_cc_credito
			WHERE id_cliente_empresa = %s
				AND creditoreservado = 0;",
				valTpDato($rowClienteEmpresa['id_cliente_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$deleteSQL = sprintf("DELETE FROM cj_cc_cliente_empresa WHERE id_cliente_empresa = %s;",
				valTpDato($rowClienteEmpresa['id_cliente_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA LAS EMPRESAS PARA EL CLIENTE
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idClienteEmpresa = $frmCliente['hddIdClienteEmpresa'.$valor];
			$idEmpresa = $frmCliente['hddIdEmpresa'.$valor];
			$idCredito = $frmCliente['hddIdCredito'.$valor];
			
			if ($idClienteEmpresa > 0) {
			} else {
				$insertSQL = sprintf("INSERT INTO cj_cc_cliente_empresa (id_cliente, id_empresa)
				VALUE (%s, %s);",
					valTpDato($idCliente, "int"),
					valTpDato($idEmpresa, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idClienteEmpresa = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			if (in_array($frmCliente['lstCredito'], array("0","Si"))) {
				if ($idCredito > 0) {
					if (!xvalidaAcceso($objResponse,"cc_clientes_credito","editar")) { return $objResponse; }
					
					if ($frmCliente['txtDiasCredito'.$valor] == 0 && $frmCliente['txtLimiteCredito'.$valor] == 0) {
						$deleteSQL = sprintf("DELETE FROM cj_cc_credito
						WHERE id = %s
							AND creditoreservado = 0;",
							valTpDato($idCredito, "int"));
						$Result1 = mysql_query($deleteSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					} else {
						$updateSQL = sprintf("UPDATE cj_cc_credito SET
							diascredito = %s,
							limitecredito = %s,
							fpago = %s
						WHERE id = %s;",
							valTpDato($frmCliente['txtDiasCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtLimiteCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtFormaPago'.$valor], "text"),
							valTpDato($idCredito, "int"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				} else {
					if (str_replace(",","",$frmCliente['txtLimiteCredito'.$valor]) > 0) {
						if (!xvalidaAcceso($objResponse,"cc_clientes_credito","insertar")) { return $objResponse; }
						
						$insertSQL = sprintf("INSERT INTO cj_cc_credito (id_cliente_empresa, diascredito, limitecredito, fpago)
						VALUE (%s, %s, %s, %s);",
							valTpDato($idClienteEmpresa, "int"),
							valTpDato($frmCliente['txtDiasCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtLimiteCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtFormaPago'.$valor], "text"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}	
				}
			}
			
			// ACTUALIZA EL CREDITO DISPONIBLE
			$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
															WHERE anticip.idCliente = cliente_emp.id_cliente
																AND anticip.id_empresa = cliente_emp.id_empresa
																AND anticip.estadoAnticipo IN (1,2)
																AND anticip.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
											WHERE anticip.idCliente = cliente_emp.id_cliente
												AND anticip.id_empresa = cliente_emp.id_empresa
												AND anticip.estadoAnticipo IN (1,2)
												AND anticip.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	// ELIMINA TODOS LOS IMPUESTOS DEL GASTO
	$deleteSQL = sprintf("DELETE FROM cj_cc_cliente_impuesto_exento WHERE id_cliente = %s;",
		valTpDato($idCliente, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// INSERTA LOS IMPUESTOS NUEVOS
	if (isset($arrayObjImpuesto)) {
		foreach($arrayObjImpuesto as $indice => $valor) {
			$idImpuesto = $frmCliente['hddIdImpuesto'.$valor];
			
			if ($idImpuesto > 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_cliente_impuesto_exento (id_cliente, id_impuesto)
				VALUE (%s, %s);",
					valTpDato($idCliente, "int"),
					valTpDato($idImpuesto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Cliente guardado con éxito.");
	
	$objResponse->script("byId('btnCancelarCliente').click();");
	
	$objResponse->loadCommands(listaCliente(
		$frmListaCliente['pageNum'],
		$frmListaCliente['campOrd'],
		$frmListaCliente['tpOrd'],
		$frmListaCliente['valBusq']));
	
	return $objResponse;
}

function guardarDcto($frmDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmDcto['cbx1'];
	
	$idPedido = $frmDcto['txtIdPedido'];
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$idPresupuesto = $frmDcto['hddIdPresupuestoVenta'];
	$idFactura = $frmDcto['txtIdFactura'];
	$idUnidadFisica = $frmDcto['txtIdUnidadFisica'];
	$idUnidadFisicaAnterior = $frmDcto['hddIdUnidadFisicaAnterior'];
	
	// VERIFICA QUE EL CLIENTE DEL PEDIDO ESTE CREADO COMO CLIENTE (1 = Prospecto, 2 = Cliente)
	$tipoCuentaCliente = getmysql(sprintf("SELECT tipo_cuenta_cliente FROM cj_cc_cliente WHERE id = %s;", valTpDato($idCliente, "int")));
	$estadoPedido = ($tipoCuentaCliente == 1) ? 3 : 1; // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	
	$txtFechaTasaCambio = ($frmDcto['txtFechaTasaCambio'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaTasaCambio'])) : "";
	
	$txtFechaRetoma = ($frmDcto['txtFechaRetoma'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaRetoma'])) : "";
	
	$txtFechaCuotaFinanciar = ($frmDcto['txtFechaCuotaFinanciar'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar'])) : "";
	$txtFechaCuotaFinanciar2 = ($frmDcto['txtFechaCuotaFinanciar2'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar2'])) : "";
	$txtFechaCuotaFinanciar3 = ($frmDcto['txtFechaCuotaFinanciar3'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar3'])) : "";
	$txtFechaCuotaFinanciar4 = ($frmDcto['txtFechaCuotaFinanciar4'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaCuotaFinanciar4'])) : "";
	
	$txtFechaEfect = ($frmDcto['txtFechaEfect'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaEfect'])) : "";
	$txtFechaExpi =  ($frmDcto['txtFechaExpi'] != "") ? date("Y-m-d", strtotime($frmDcto['txtFechaExpi'])) : "";
	
	mysql_query("START TRANSACTION;");
	
	if ($idFactura > 0) {
		if (!xvalidaAcceso($objResponse,"cj_factura_venta_list","insertar")) { return $objResponse; }
		
		$insertSQL = "INSERT INTO an_pedido (numeracion_pedido, id_empresa, id_cliente, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, monto_tasa_cambio, fecha_tasa_cambio, id_clave_movimiento, id_presupuesto, id_factura_cxc, id_unidad_fisica, fecha, estado_pedido, asesor_ventas, gerente_ventas, fecha_gerente_ventas, administracion, fecha_administracion, precio_retoma, fecha_retoma, id_uni_bas_retoma, id_color_retorma, placa_retoma, certificado_origen_retoma, precio_venta, monto_descuento, porcentaje_iva, porcentaje_impuesto_lujo, tipo_inicial, porcentaje_inicial, inicial, saldo_financiar, meses_financiar, interes_cuota_financiar, cuotas_financiar, fecha_pago_cuota, meses_financiar2, interes_cuota_financiar2, cuotas_financiar2, fecha_pago_cuota2, meses_financiar3, interes_cuota_financiar3, cuotas_financiar3, fecha_pago_cuota3, meses_financiar4, interes_cuota_financiar4, cuotas_financiar4, fecha_pago_cuota4, id_banco_financiar, total_accesorio, total_adicional_contrato, total_inicial_gastos, porcentaje_flat, monto_flat, observaciones, anticipo, complemento_inicial, id_poliza, num_poliza, monto_seguro, periodo_poliza, ded_poliza, fech_efect, fech_expira, inicial_poliza, meses_poliza, cuotas_poliza, fecha_reserva_venta, fecha_entrega, forma_pago_precio_total, total_pedido, exacc1, exacc2, exacc3, exacc4, vexacc1, vexacc2, vexacc3, vexacc4, empresa_accesorio)
		SELECT
			CONCAT(SUBSTRING_INDEX(numeracion_pedido, '(', 1), '(', ((SELECT COUNT(an_ped_vent.id_factura_cxc) FROM an_pedido an_ped_vent
																	WHERE an_ped_vent.id_empresa = an_pedido.id_empresa
																		AND an_ped_vent.numeracion_pedido LIKE CONCAT(SUBSTRING_INDEX(an_pedido.numeracion_pedido, '(', 1), '%')) + 1),')'), ".					
			valTpDato($idEmpresa, "int").", ".
			valTpDato($idCliente, "int").", ".
			valTpDato($frmDcto['hddIdMoneda'], "int").", ".
			valTpDato($frmDcto['lstMoneda'], "int").", ".
			valTpDato($frmDcto['lstTasaCambio'], "int").", ".
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa").", ".
			valTpDato($txtFechaTasaCambio, "date").", ".
			valTpDato($frmDcto['lstClaveMovimiento'], "int").", ".
			valTpDato($frmDcto['hddIdPresupuestoVenta'], "int").", ".
			valTpDato($frmDcto['txtIdFactura'], "int").", ".
			valTpDato($idUnidadFisica, "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaPedido'])), "date").", ".
			valTpDato($estadoPedido, "int").", ". // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			valTpDato($frmDcto['txtIdEmpleado'], "int").", ".
			valTpDato($frmDcto['lstGerenteVenta'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVenta'])), "date").", ".
			valTpDato($frmDcto['lstGerenteAdministracion'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaAdministracion'])), "date").", ".
			valTpDato($txtPrecioRetoma, "real_inglesa").", ".
			valTpDato($txtFechaRetoma, "date").", ".
			valTpDato($txtIdUnidadBasicaRetoma, "int").", ".
			valTpDato($txtIdColorRetoma, "int").", ".
			valTpDato($txtPlacaRetoma, "text").", ".
			valTpDato($txtCertificadoOrigenRetoma, "text").", ".
			valTpDato($frmDcto['txtPrecioBase'], "real_inglesa").", ".
			valTpDato($frmDcto['txtDescuento'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcIva'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcIvaLujo'], "real_inglesa").", ".
			valTpDato($frmDcto['hddTipoInicial'], "int").", ".
			valTpDato($frmDcto['txtPorcInicial'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoInicial'], "real_inglesa").", ".
			
			valTpDato($frmDcto['txtSaldoFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['lstMesesFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar2'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar2'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar2'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar2, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar3'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar3'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar3'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar3, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar4'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar4'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar4'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar4, "date").", ".
			valTpDato($frmDcto['lstBancoFinanciar'], "int").", ".
			
			valTpDato($frmDcto['txtTotalAdicional'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalAdicionalContrato'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalInicialAdicionales'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtObservacion'], "text").", ".
			valTpDato($frmDcto['txtMontoAnticipo'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoComplementoInicial'], "real_inglesa").", ".
			
			valTpDato($frmDcto['lstPoliza'], "int").", ".
			valTpDato($frmDcto['txtNumPoliza'], "text").", ".
			valTpDato($frmDcto['txtMontoSeguro'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPeriodoPoliza'], "text").", ".
			valTpDato($frmDcto['txtDeduciblePoliza'], "real_inglesa").", ".
			valTpDato($txtFechaEfect, "date").", ".
			valTpDato($txtFechaExpi, "date").", ".
			valTpDato($frmDcto['txtInicialPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMesesPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasPoliza'], "real_inglesa").", ".
			
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaReserva'])), "date").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaEntrega'])), "date").", ".
			valTpDato($frmDcto['txtPrecioTotal'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalPedido'], "real_inglesa").", ".
			valTpDato($frmDcto['txtNumeroPresupuestoAcc'], "text").", ".
			valTpDato($exacc2, "text").", ".
			valTpDato($exacc3, "text").", ".
			valTpDato($exacc4, "text").", ".
			valTpDato($frmDcto['txtSubTotalPresupuestoAccesorio'], "real_inglesa").", ".
			valTpDato($vexacc2, "real_inglesa").", ".
			valTpDato($vexacc3, "real_inglesa").", ".
			valTpDato($vexacc4, "real_inglesa").", ".
			valTpDato($empresa_accesorio, "text")."
		FROM an_pedido
		WHERE id_pedido = ".valTpDato($idPedido, "int").";";
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idPedido = mysql_insert_id();
	} else if ($idPedido > 0) {
		if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","editar")) { return $objResponse; }
		
		$updateSQL = "UPDATE an_pedido SET
			id_empresa = ".valTpDato($idEmpresa, "int").",
			id_cliente = ".valTpDato($idCliente, "int").",
			id_moneda = ".valTpDato($frmDcto['hddIdMoneda'], "int").",
			id_moneda_tasa_cambio = ".valTpDato($frmDcto['lstMoneda'], "int").",
			id_tasa_cambio = ".valTpDato($frmDcto['lstTasaCambio'], "int").",
			monto_tasa_cambio = ".valTpDato($frmDcto['txtTasaCambio'], "real_inglesa").",
			fecha_tasa_cambio = ".valTpDato($txtFechaTasaCambio, "date").",
			id_clave_movimiento = ".valTpDato($frmDcto['lstClaveMovimiento'], "int").",
			id_unidad_fisica = ".valTpDato($idUnidadFisica, "int").",
			estado_pedido = (CASE 
								WHEN (an_pedido.estado_pedido IN (2,4,5)) THEN
									an_pedido.estado_pedido
								ELSE
									".valTpDato($estadoPedido, "int")."
							END),
			asesor_ventas = ".valTpDato($frmDcto['txtIdEmpleado'], "int").",
			gerente_ventas = ".valTpDato($frmDcto['lstGerenteVenta'], "int").",
			fecha_gerente_ventas = ".valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVenta'])), "date").",
			administracion = ".valTpDato($frmDcto['lstGerenteAdministracion'], "int").",
			fecha_administracion = ".valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaAdministracion'])), "date").",
			precio_retoma = ".valTpDato($txtPrecioRetoma, "real_inglesa").",
			fecha_retoma = ".valTpDato($txtFechaRetoma, "date").",
			id_uni_bas_retoma = ".valTpDato($txtIdUnidadBasicaRetoma, "int").",
			id_color_retorma = ".valTpDato($txtIdColorRetoma, "int").",
			placa_retoma = ".valTpDato($txtPlacaRetoma, "text").",
			certificado_origen_retoma = ".valTpDato($txtCertificadoOrigenRetoma, "text").",
			precio_venta = ".valTpDato($frmDcto['txtPrecioBase'], "real_inglesa").",
			monto_descuento = ".valTpDato($frmDcto['txtDescuento'], "real_inglesa").",
			porcentaje_iva = ".valTpDato($frmDcto['txtPorcIva'], "real_inglesa").",
			porcentaje_impuesto_lujo = ".valTpDato($frmDcto['txtPorcIvaLujo'], "real_inglesa").",
			tipo_inicial = ".valTpDato($frmDcto['hddTipoInicial'], "int").",
			porcentaje_inicial = ".valTpDato($frmDcto['txtPorcInicial'], "real_inglesa").",
			inicial = ".valTpDato($frmDcto['txtMontoInicial'], "real_inglesa").",
			
			saldo_financiar = ".valTpDato($frmDcto['txtSaldoFinanciar'], "real_inglesa").",
			meses_financiar = ".valTpDato($frmDcto['lstMesesFinanciar'], "real_inglesa").",
			interes_cuota_financiar = ".valTpDato($frmDcto['txtInteresCuotaFinanciar'], "real_inglesa").",
			cuotas_financiar = ".valTpDato($frmDcto['txtCuotasFinanciar'], "real_inglesa").",
			fecha_pago_cuota = ".valTpDato($txtFechaCuotaFinanciar, "date").",
			meses_financiar2 = ".valTpDato($frmDcto['lstMesesFinanciar2'], "real_inglesa").",
			interes_cuota_financiar2 = ".valTpDato($frmDcto['txtInteresCuotaFinanciar2'], "real_inglesa").",
			cuotas_financiar2 = ".valTpDato($frmDcto['txtCuotasFinanciar2'], "real_inglesa").",
			fecha_pago_cuota2 = ".valTpDato($txtFechaCuotaFinanciar2, "date").",
			meses_financiar3 = ".valTpDato($frmDcto['lstMesesFinanciar3'], "real_inglesa").",
			interes_cuota_financiar3 = ".valTpDato($frmDcto['txtInteresCuotaFinanciar3'], "real_inglesa").",
			cuotas_financiar3 = ".valTpDato($frmDcto['txtCuotasFinanciar3'], "real_inglesa").",
			fecha_pago_cuota3 = ".valTpDato($txtFechaCuotaFinanciar3, "date").",
			meses_financiar4 = ".valTpDato($frmDcto['lstMesesFinanciar4'], "real_inglesa").",
			interes_cuota_financiar4 = ".valTpDato($frmDcto['txtInteresCuotaFinanciar4'], "real_inglesa").",
			cuotas_financiar4 = ".valTpDato($frmDcto['txtCuotasFinanciar4'], "real_inglesa").",
			fecha_pago_cuota4 = ".valTpDato($txtFechaCuotaFinanciar4, "date").",
			id_banco_financiar = ".valTpDato($frmDcto['lstBancoFinanciar'], "int").",
			
			total_accesorio = ".valTpDato($frmDcto['txtTotalAdicional'], "real_inglesa").",
			total_adicional_contrato = ".valTpDato($frmDcto['txtTotalAdicionalContrato'], "real_inglesa").",
			total_inicial_gastos = ".valTpDato($frmDcto['txtTotalInicialAdicionales'], "real_inglesa").",
			porcentaje_flat = ".valTpDato($frmDcto['txtPorcFLAT'], "real_inglesa").",
			monto_flat = ".valTpDato($frmDcto['txtMontoFLAT'], "real_inglesa").",
			observaciones = ".valTpDato($frmDcto['txtObservacion'], "text").",
			anticipo = ".valTpDato($frmDcto['txtMontoAnticipo'], "real_inglesa").",
			complemento_inicial = ".valTpDato($frmDcto['txtMontoComplementoInicial'], "real_inglesa").",
			
			id_poliza = ".valTpDato($frmDcto['lstPoliza'], "int").",
			num_poliza = ".valTpDato($frmDcto['txtNumPoliza'], "text").",
			monto_seguro = ".valTpDato($frmDcto['txtMontoSeguro'], "real_inglesa").",
			periodo_poliza = ".valTpDato($frmDcto['txtPeriodoPoliza'], "int").",
			ded_poliza = ".valTpDato($frmDcto['txtDeduciblePoliza'], "real_inglesa").",
			fech_efect = ".valTpDato($txtFechaEfect, "date").",
			fech_expira = ".valTpDato($txtFechaExpi, "date").",
			inicial_poliza = ".valTpDato($frmDcto['txtInicialPoliza'], "real_inglesa").",
			meses_poliza = ".valTpDato($frmDcto['txtMesesPoliza'], "real_inglesa").",
			cuotas_poliza = ".valTpDato($frmDcto['txtCuotasPoliza'], "real_inglesa").",
			
			fecha_reserva_venta = ".valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaReserva'])), "date").",
			fecha_entrega = ".	valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaEntrega'])), "date").",
			forma_pago_precio_total = ".valTpDato($frmDcto['txtPrecioTotal'], "real_inglesa").",
			total_pedido = ".valTpDato($frmDcto['txtTotalPedido'], "real_inglesa").",
			exacc1 = ".valTpDato($frmDcto['txtNumeroPresupuestoAcc'], "text").",
			exacc2 = ".valTpDato($exacc2, "text").",
			exacc3 = ".valTpDato($exacc3, "text").",
			exacc4 = ".valTpDato($exacc4, "text").",
			vexacc1 = ".valTpDato($frmDcto['txtSubTotalPresupuestoAccesorio'], "real_inglesa").",
			vexacc2 = ".valTpDato($vexacc2, "real_inglesa").",
			vexacc3 = ".valTpDato($vexacc3, "real_inglesa").",
			vexacc4 = ".valTpDato($vexacc4, "real_inglesa").",
			empresa_accesorio = '".$empresa_accesorio."'
		WHERE id_pedido = ".valTpDato($idPedido, "int").";";
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	} else {
		if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","insertar")) { return $objResponse; }
		
		// VERIFICA QUE LA UNIDAD FISICA NO HAYA SIDO RESERVADA ANTES
		$queryUnidadReservada = sprintf("SELECT estado_venta FROM an_unidad_fisica
		WHERE id_unidad_fisica = %s
			AND estado_venta IN ('RESERVADO');",
			valTpDato($idUnidadFisica, "int"));
		$rsUnidadReservada = mysql_query($queryUnidadReservada);
		if (!$rsUnidadReservada) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsUnidadReservada = mysql_num_rows($rsUnidadReservada);
		if ($totalRowsUnidadReservada > 0) {
			return $objResponse->script("
			alert('La unidad seleccionada ya se ha reservado hace pocos instantes');
			history.go(-1);");
		}
		
		// VERIFICA QUE EL PRESUPUESTO NO HAYA SIDO GENERADO ANTERIORMENTE
		if ($idPresupuesto > 0) {
			$pedidoc = getmysql("SELECT COUNT(*) FROM an_pedido WHERE id_presupuesto = ".valTpDato($idPresupuesto, "int").";");
			if ($pedidoc > 0) {
				$objResponse->alert("El Pedido del Presupesto ".$idPresupuesto." ya fu&eacute; Generado");
				return $objResponse->script("
				window.location = 'an_pedido_venta_list.php';");
			}
		}
		
		if ($tipoCuentaCliente == 1) {
			$objResponse->alert("El Prospecto perteneciente a este Pedido, no está Aprobado como Cliente. Recomendamos lo apruebe en la pantalla de Prospectación, para así generar dicho Presupuesto");
		}
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(39, "int"), // 39 = Pedido Venta Vehículos
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// INSERTA LOS DATOS DEL PEDIDO
		$insertSQL = "INSERT INTO an_pedido (numeracion_pedido, id_empresa, id_cliente, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, monto_tasa_cambio, fecha_tasa_cambio, id_clave_movimiento, id_presupuesto, id_factura_cxc, id_unidad_fisica, fecha, estado_pedido, asesor_ventas, gerente_ventas, fecha_gerente_ventas, administracion, fecha_administracion, precio_retoma, fecha_retoma, id_uni_bas_retoma, id_color_retorma, placa_retoma, certificado_origen_retoma, precio_venta, monto_descuento, porcentaje_iva, porcentaje_impuesto_lujo, tipo_inicial, porcentaje_inicial, inicial, saldo_financiar, meses_financiar, interes_cuota_financiar, cuotas_financiar, fecha_pago_cuota, meses_financiar2, interes_cuota_financiar2, cuotas_financiar2, fecha_pago_cuota2, meses_financiar3, interes_cuota_financiar3, cuotas_financiar3, fecha_pago_cuota3, meses_financiar4, interes_cuota_financiar4, cuotas_financiar4, fecha_pago_cuota4, id_banco_financiar, total_accesorio, total_adicional_contrato, total_inicial_gastos, porcentaje_flat, monto_flat, observaciones, anticipo, complemento_inicial, id_poliza, num_poliza, monto_seguro, periodo_poliza, ded_poliza, fech_efect, fech_expira, inicial_poliza, meses_poliza, cuotas_poliza, fecha_reserva_venta, fecha_entrega, forma_pago_precio_total, total_pedido, exacc1, exacc2, exacc3, exacc4, vexacc1, vexacc2, vexacc3, vexacc4, empresa_accesorio)
		VALUES (".valTpDato($numeroActual, "text").", ".
			valTpDato($idEmpresa, "int").", ".
			valTpDato($idCliente, "int").", ".
			valTpDato($frmDcto['hddIdMoneda'], "int").", ".
			valTpDato($frmDcto['lstMoneda'], "int").", ".
			valTpDato($frmDcto['lstTasaCambio'], "int").", ".
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa").", ".
			valTpDato($txtFechaTasaCambio, "date").", ".
			valTpDato($frmDcto['lstClaveMovimiento'], "int").", ".
			valTpDato($frmDcto['hddIdPresupuestoVenta'], "int").", ".
			valTpDato($frmDcto['txtIdFactura'], "int").", ".
			valTpDato($idUnidadFisica, "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaPedido'])), "date").", ".
			valTpDato($estadoPedido, "int").", ". // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			valTpDato($frmDcto['txtIdEmpleado'], "int").", ".
			valTpDato($frmDcto['lstGerenteVenta'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVenta'])), "date").", ".
			valTpDato($frmDcto['lstGerenteAdministracion'], "int").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaAdministracion'])), "date").", ".
			valTpDato($txtPrecioRetoma, "real_inglesa").", ".
			valTpDato($txtFechaRetoma, "date").", ".
			valTpDato($txtIdUnidadBasicaRetoma, "int").", ".
			valTpDato($txtIdColorRetoma, "int").", ".
			valTpDato($txtPlacaRetoma, "text").", ".
			valTpDato($txtCertificadoOrigenRetoma, "text").", ".
			valTpDato($frmDcto['txtPrecioBase'], "real_inglesa").", ".
			valTpDato($frmDcto['txtDescuento'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcIva'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcIvaLujo'], "real_inglesa").", ".
			valTpDato($frmDcto['hddTipoInicial'], "int").", ".
			valTpDato($frmDcto['txtPorcInicial'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoInicial'], "real_inglesa").", ".
			
			valTpDato($frmDcto['txtSaldoFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['lstMesesFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar2'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar2'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar2'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar2, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar3'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar3'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar3'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar3, "date").", ".
			valTpDato($frmDcto['lstMesesFinanciar4'], "real_inglesa").", ".
			valTpDato($frmDcto['txtInteresCuotaFinanciar4'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasFinanciar4'], "real_inglesa").", ".
			valTpDato($txtFechaCuotaFinanciar4, "date").", ".
			valTpDato($frmDcto['lstBancoFinanciar'], "int").", ".
			
			valTpDato($frmDcto['txtTotalAdicional'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalAdicionalContrato'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalInicialAdicionales'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPorcFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoFLAT'], "real_inglesa").", ".
			valTpDato($frmDcto['txtObservacion'], "text").", ".
			valTpDato($frmDcto['txtMontoAnticipo'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMontoComplementoInicial'], "real_inglesa").", ".
			
			valTpDato($frmDcto['lstPoliza'], "int").", ".
			valTpDato($frmDcto['txtNumPoliza'], "text").", ".
			valTpDato($frmDcto['txtMontoSeguro'], "real_inglesa").", ".
			valTpDato($frmDcto['txtPeriodoPoliza'], "text").", ".
			valTpDato($frmDcto['txtDeduciblePoliza'], "real_inglesa").", ".
			valTpDato($txtFechaEfect, "date").", ".
			valTpDato($txtFechaExpi, "date").", ".
			valTpDato($frmDcto['txtInicialPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtMesesPoliza'], "real_inglesa").", ".
			valTpDato($frmDcto['txtCuotasPoliza'], "real_inglesa").", ".
			
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaReserva'])), "date").", ".
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaEntrega'])), "date").", ".
			valTpDato($frmDcto['txtPrecioTotal'], "real_inglesa").", ".
			valTpDato($frmDcto['txtTotalPedido'], "real_inglesa").", ".
			valTpDato($frmDcto['txtNumeroPresupuestoAcc'], "text").", ".
			valTpDato($exacc2, "text").", ".
			valTpDato($exacc3, "text").", ".
			valTpDato($exacc4, "text").", ".
			valTpDato($frmDcto['txtSubTotalPresupuestoAccesorio'], "real_inglesa").", ".
			valTpDato($vexacc2, "real_inglesa").", ".
			valTpDato($vexacc3, "real_inglesa").", ".
			valTpDato($vexacc4, "real_inglesa").", ".
			valTpDato($empresa_accesorio, "text").");";
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idPedido = mysql_insert_id();
	}

	// VERIFICA SI TIENE CONTRATO DE FINANCIAMIENTO
	$queryContrato = sprintf("SELECT * FROM an_adicionales_contrato adicional_contrato WHERE adicional_contrato.id_pedido = %s;",
		valTpDato($idPedido, "int"));
	$rsContrato = mysql_query($queryContrato);
	if (!$rsContrato) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsContrato = mysql_num_rows($rsContrato);
	$rowContrato = mysql_fetch_assoc($rsContrato);
	
	if ($idPoliza > 0) {
		// INSERTA LOS DATOS DEL CONTRATO
		if ($totalRowsContrato > 0) {
			$idContrato = $rowContrato['id_adi_contrato'];
			
			$updateSQL = "UPDATE an_adicionales_contrato SET
				nombre_agencia_seguro = ".valTpDato($frmDcto['txtNombreAgenciaSeguro'], "text").",
				direccion_agencia_seguro = ".valTpDato($frmDcto['txtDireccionAgenciaSeguro'], "text").",
				ciudad_agencia_seguro = ".valTpDato($frmDcto['txtCiudadAgenciaSeguro'], "text").",
				pais_agencia_seguro = ".valTpDato($frmDcto['txtPaisAgenciaSeguro'], "text").",
				telefono_agencia_seguro = ".valTpDato($frmDcto['txtTelefonoAgenciaSeguro'], "text")."
			WHERE id_pedido = ".valTpDato($idPedido, "int").";";
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else if (strlen($txtNombreAgenciaSeguro) > 0) {
			$insertSQL = "INSERT INTO an_adicionales_contrato (id_pedido, nombre_agencia_seguro, direccion_agencia_seguro, ciudad_agencia_seguro, pais_agencia_seguro, telefono_agencia_seguro)
			VALUES (".valTpDato($idPedido, "int").", ".
				valTpDato($frmDcto['txtNombreAgenciaSeguro'], "text").", ".
				valTpDato($frmDcto['txtDireccionAgenciaSeguro'], "text").", ".
				valTpDato($frmDcto['txtCiudadAgenciaSeguro'], "text").", ".
				valTpDato($frmDcto['txtPaisAgenciaSeguro'], "text").", ".
				valTpDato($frmDcto['txtTelefonoAgenciaSeguro'], "text").");";
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idContrato = mysql_insert_id();
		}
	}
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$hddIdDetItm = $frmDcto['hddIdDetItm'.$valor1];
			$hddIdAdicionalItm = $frmDcto['hddIdAdicionalItm'.$valor1];
			$hddIdAdicionalPaqueteItm = $frmDcto['hddIdAdicionalPaqueteItm'.$valor1];
			$txtPrecioConIvaItm = str_replace(",","",$frmDcto['txtPrecioConIvaItm'.$valor1]);
			$hddCostoUnitarioItm = str_replace(",","",$frmDcto['hddCostoUnitarioItm'.$valor1]);
			$hddPorcIvaItm = str_replace(",","",$frmDcto['hddPorcIvaItm'.$valor1]);
			$txtPrecioPagadoItm = str_replace(",","",$frmDcto['txtPrecioPagadoItm'.$valor1]);
			$hddAplicaIvaItm = $frmDcto['hddAplicaIvaItm'.$valor1];
			$cbxCondicion = $frmDcto['cbxCondicionItm'.$valor1];
			$cbxMostrar = $frmDcto['lstMostrarItm'.$valor1];
			$lstTipoAdicionalItm = $frmDcto['lstTipoAdicionalItm'.$valor1];
			
			$txtPrecioUnitarioItm = $txtPrecioConIvaItm - (($hddPorcIvaItm != 0) ? ($txtPrecioConIvaItm * $hddPorcIvaItm / (100 + $hddPorcIvaItm)) : 0);
			
			if ($hddIdAdicionalPaqueteItm > 0) {
				if ($hddIdDetItm > 0) {
					$updateSQL = sprintf("UPDATE an_paquete_pedido SET
						id_tipo_accesorio = %s,
						precio_accesorio = %s,
						costo_accesorio = %s,
						porcentaje_iva_accesorio = %s,
						iva_accesorio = %s,
						monto_pagado = %s,
						id_condicion_pago = %s,
						id_condicion_mostrar = %s
					WHERE id_paquete_pedido = %s;", 
						valTpDato($lstTipoAdicionalItm, "int"),
						valTpDato($txtPrecioUnitarioItm, "real_inglesa"),
						valTpDato($hddCostoUnitarioItm, "real_inglesa"),
						valTpDato($hddPorcIvaItm, "real_inglesa"),
						valTpDato($hddAplicaIvaItm, "int"),
						valTpDato($txtPrecioPagadoItm, "real_inglesa"),
						valTpDato($cbxCondicion, "int"),
						valTpDato($cbxMostrar, "int"),
						valTpDato($hddIdDetItm, "int"));		
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					$arrayIdPaquetePedido[] = $hddIdDetItm;
				} else {
					$insertSQL = sprintf("INSERT INTO an_paquete_pedido (id_pedido, id_acc_paq, id_tipo_accesorio, precio_accesorio, costo_accesorio, porcentaje_iva_accesorio, iva_accesorio, monto_pagado, id_condicion_pago, id_condicion_mostrar)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idPedido, "int"),
						valTpDato($hddIdAdicionalPaqueteItm, "int"),
						valTpDato($lstTipoAdicionalItm, "int"),
						valTpDato($txtPrecioUnitarioItm, "real_inglesa"),
						valTpDato($hddCostoUnitarioItm, "real_inglesa"),
						valTpDato($hddPorcIvaItm, "real_inglesa"),
						valTpDato($hddAplicaIvaItm, "int"),
						valTpDato($txtPrecioPagadoItm, "real_inglesa"),
						valTpDato($cbxCondicion, "int"),
						valTpDato($cbxMostrar, "int"));		
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$hddIdDetItm = mysql_insert_id();
					
					$arrayIdPaquetePedido[] = $hddIdDetItm;
				}
			} else {
				if ($hddIdDetItm > 0) {
					$updateSQL = sprintf("UPDATE an_accesorio_pedido SET
						id_tipo_accesorio = %s,
						precio_accesorio = %s,
						costo_accesorio = %s,
						porcentaje_iva_accesorio = %s,
						iva_accesorio = %s,
						monto_pagado = %s,
						id_condicion_pago = %s,
						id_condicion_mostrar = %s
					WHERE id_accesorio_pedido = %s;",
						valTpDato($lstTipoAdicionalItm, "int"),
						valTpDato($txtPrecioUnitarioItm, "real_inglesa"),
						valTpDato($hddCostoUnitarioItm, "real_inglesa"),
						valTpDato($hddPorcIvaItm, "real_inglesa"),
						valTpDato($hddAplicaIvaItm, "int"),
						valTpDato($txtPrecioPagadoItm, "real_inglesa"),
						valTpDato($cbxCondicion, "int"),
						valTpDato($cbxMostrar, "int"),
						valTpDato($hddIdDetItm, "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					$arrayIdAdicionalPedido[] = $hddIdDetItm;
				} else {
					$insertSQL = sprintf("INSERT INTO an_accesorio_pedido (id_pedido, id_accesorio, id_tipo_accesorio, precio_accesorio, costo_accesorio, porcentaje_iva_accesorio, iva_accesorio, monto_pagado, id_condicion_pago, id_condicion_mostrar)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idPedido, "int"),
						valTpDato($hddIdAdicionalItm, "int"),
						valTpDato($lstTipoAdicionalItm, "int"),
						valTpDato($txtPrecioUnitarioItm, "real_inglesa"),
						valTpDato($hddCostoUnitarioItm, "real_inglesa"),
						valTpDato($hddPorcIvaItm, "real_inglesa"),
						valTpDato($hddAplicaIvaItm, "int"),
						valTpDato($txtPrecioPagadoItm, "real_inglesa"),
						valTpDato($cbxCondicion, "int"),
						valTpDato($cbxMostrar, "int"));	
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$hddIdDetItm = mysql_insert_id();
					
					$arrayIdAdicionalPedido[] = $hddIdDetItm;
				}
			}
		}
	}
	
	if (is_array($arrayIdAdicionalPedido)) {
		$deleteSQL = sprintf("DELETE FROM an_accesorio_pedido
		WHERE id_pedido = %s
			AND id_accesorio_pedido NOT IN (%s);",
			valTpDato($idPedido, "int"),
			valTpDato(implode(",",$arrayIdAdicionalPedido), "campo"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	if (is_array($arrayIdPaquetePedido)) {
		$deleteSQL = sprintf("DELETE FROM an_paquete_pedido
		WHERE id_pedido = %s
			AND id_paquete_pedido NOT IN (%s);",
			valTpDato($idPedido, "int"),
			valTpDato(implode(",",$arrayIdPaquetePedido), "campo"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	if ($idUnidadFisica > 0 || $idUnidadFisicaAnterior > 0) {
		// SI LA UNIDAD FISICA ES DISTINTA, LIBERA LA ANTERIOR
		if ($idUnidadFisica != $idUnidadFisicaAnterior) {
			// LIBERA LA UNIDAD FISICA
			$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
				kilometraje = %s,
				placa = %s,
				estado_venta = (CASE uni_fis.estado_compra
									WHEN 'COMPRADO' THEN 'POR REGISTRAR'
									WHEN 'REGISTRADO' THEN 'DISPONIBLE'
								END)
			WHERE id_unidad_fisica = %s
				AND estado_venta IN ('RESERVADO');",
				valTpDato($frmDcto['txtKilometraje'], "text"),
				valTpDato($frmDcto['txtPlaca'], "text"),
				valTpDato($idUnidadFisicaAnterior, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		// RESERVA LA UNIDAD FISICA
		$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
			kilometraje = %s,
			placa = %s,
			estado_venta = 'RESERVADO'
		WHERE id_unidad_fisica = %s
			AND estado_venta IN ('POR REGISTRAR','DISPONIBLE');",
			valTpDato($frmDcto['txtKilometraje'], "text"),
			valTpDato($frmDcto['txtPlaca'], "text"),
			valTpDato($idUnidadFisica, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	if (!($idFactura > 0)) {
		// ACTUALIZA EL ESTADO DEL PRESUPUESTO
		$updateSQL = sprintf("UPDATE an_presupuesto SET
			estado = 1
		WHERE id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	// ACTUALIZA EL ESTATUS DEL PEDIDO SI LA UNIDAD NO HA SIDO TOTALMMENTE REGISTRADA O SI PERTENECE AL ALMACEN DE OTRA EMPRESA
	// (0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada)
	$updateSQL = sprintf("UPDATE an_pedido ped_vent SET
		ped_vent.estado_pedido = (CASE 
									WHEN (ped_vent.estado_pedido IN (2,4,5)) THEN
										ped_vent.estado_pedido
									ELSE
										3
								END)
	WHERE ped_vent.id_pedido = %s
		AND ((SELECT uni_fis.estado_compra FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_unidad_fisica = %s) IN ('COMPRADO')
			OR (SELECT alm.id_empresa
				FROM an_almacen alm
					INNER JOIN an_unidad_fisica uni_fis ON (alm.id_almacen = uni_fis.id_almacen)
				WHERE uni_fis.id_unidad_fisica = %s) <> ped_vent.id_empresa);",
		valTpDato($idPedido, "int"),
		valTpDato($idUnidadFisica, "int"),
		valTpDato($idUnidadFisica, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(("Pedido Guardado con Éxito"));
	
	$objResponse->script("
	cerrarVentana = true;
	window.location.href='an_ventas_pedido_editar.php?view=import&id=".$idPedido."';");
	
	return $objResponse;
}

function insertarAdicional($idAdicional, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmDcto['cbx1'];
	$contFila1 = $arrayObj1[count($arrayObj1)-1];
	
	foreach ($arrayObj1 as $indice1 => $valor1){
		if ($frmDcto['hddIdAdicionalItm'.$valor1] == $idAdicional) {
			return $objResponse->alert("El adicional seleccionado ya se encuentra agregado");
		}
	}
	
	$Result1 = insertarItemAdicional($contFila1, "", $idAdicional);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila1 = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj1[] = $contFila1;
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function insertarPaquete($idPaquete, $frmListaAdicional, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmDcto['cbx1'];
	$contFila1 = $arrayObj1[count($arrayObj1)-1];
	
	foreach ($frmListaAdicional['cbxPaqueteAcc'] as $indicePaqueteAcc => $valorPaqueteAcc){
		$queryPaqueteAcc = sprintf("SELECT
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio
		FROM an_acc_paq acc_paq
			INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE acc_paq.Id_acc_paq = %s
			AND acc_paq.id_paquete = %s;",
			valTpDato($valorPaqueteAcc, "int"),
			valTpDato($idPaquete, "int"));
		$rsPaqueteAcc = mysql_query($queryPaqueteAcc);
		if (!$rsPaqueteAcc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPaqueteAcc = mysql_num_rows($rsPaqueteAcc);
		
		if ($totalRowsPaqueteAcc > 0) {
			$rowPaqueteAcc = mysql_fetch_assoc($rsPaqueteAcc);
			
			$idAdicionalPaquete = $rowPaqueteAcc['Id_acc_paq'];
			$idAdicional = $rowPaqueteAcc['id_accesorio'];
			foreach ($arrayObj1 as $indice1 => $valor1){
				if ($frmDcto['hddIdAdicionalItm'.$valor1] == $idAdicional) {
					return $objResponse->alert("El adicional seleccionado (".$rowPaqueteAcc['nom_accesorio'].") ya se encuentra agregado");
				}
			}
			
			$Result1 = insertarItemAdicional($contFila1, "", $idAdicional, $idAdicionalPaquete);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila1 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila1;
			}
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'));");
	
	return $objResponse;
}

function listaAdicional($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio IN (1,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_accesorio LIKE %s
		OR des_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		acc.id_accesorio,
		acc.id_modulo,
		acc.id_tipo_accesorio,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN	'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
		END) AS descripcion_tipo_accesorio,
		CONCAT(acc.nom_accesorio, IF (acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		acc.iva_accesorio,
		acc.precio_accesorio,
		acc.costo_accesorio,
		acc.genera_comision,
		acc.incluir_costo_compra_unidad,
		acc.id_tipo_comision,
		acc.porcentaje_comision,
		acc.monto_comision,
		acc.id_filtro_factura,
		acc.activo,
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "28%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "40%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "12%", $pageNum, "descripcion_tipo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Adicional");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "10%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "10%", $pageNum, "costo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_insertarAdicional('".$row['id_accesorio']."', xajax.getFormValues('frmDcto'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_accesorio'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_tipo_accesorio'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo_accesorio'], 2, ".", ",")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAdicional(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAdicional","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
	
	// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanCI));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleado('".$row['id_empleado']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
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

function listaPaquete($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(*) FROM an_acc_paq acc_paq
	WHERE acc_paq.id_paquete = paq.id_paquete) > 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(TRIM(nom_paquete) LIKE TRIM(%s)
		OR des_paquete LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_paquete paq %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$query2 = sprintf("SELECT
			acc_paq.Id_acc_paq,
			acc.id_accesorio,
			acc.id_modulo,
			acc.id_tipo_accesorio,
			(CASE acc.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc.iva_accesorio,
			acc.precio_accesorio,
			acc.costo_accesorio,
			acc.genera_comision,
			acc.incluir_costo_compra_unidad,
			acc.id_tipo_comision,
			acc.porcentaje_comision,
			acc.monto_comision,
			acc.id_filtro_factura,
			acc.activo,
			motivo.id_motivo,
			motivo.descripcion AS descripcion_motivo,
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM an_acc_paq acc_paq
			INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
			LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
			LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
		WHERE acc_paq.id_paquete = %s;",
			valTpDato($row['id_paquete'], "int"));
		$rs2 = mysql_query($query2);
		if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows2 = mysql_num_rows($rs2);
		
		$htmlTb .= (fmod($contFila, 1) == 1) ? "<tr align=\"left\" height=\"24\">" : "";
		
			$htmlTb .= "<td valign=\"top\">";
				$htmlTb .= "<fieldset><legend class=\"legend\">".utf8_encode($row['nom_paquete'])." (".utf8_encode($row['des_paquete']).")</legend>";
				if ($totalRows2 > 0) {
					$htmlTb .= "<table border=\"0\" width=\"100%\">";
					$contFila2 = 0;
					while ($row2 = mysql_fetch_array($rs2)) {
						$clase2 = (fmod($contFila2, 4) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila2++;
						
						$htmlTb .= (fmod($contFila2, 2) == 1) ? "<tr align=\"left\" class=\"".$clase2."\" height=\"24\">" : "";
						
							$htmlTb .= "<td width=\"50%\">";
							$htmlTb .= "<label>";
								$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
								$htmlTb .= "<tr>";
									$htmlTb .= "<td>"."<input type=\"checkbox\" id=\"cbxPaqueteAcc\" name=\"cbxPaqueteAcc[]\" type=\"checkbox\" checked=\"checked\" value=\"".$row2['Id_acc_paq']."\"/>"."</td>";
									$htmlTb .= "<td>".utf8_encode($row2['nom_accesorio'])."</td>";
								$htmlTb .= "</tr>";
								$htmlTb .= "</table>";
							$htmlTb .= "</label>";
							$htmlTb .= "</td>";
						
						$htmlTb .= (fmod($contFila2, 2) == 0) ? "</tr>" : "";
					}
					$htmlTb .= "<tr>";
						$htmlTb .= "<td align=\"center\" colspan=\"2\">";
							$htmlTb .= "<button type=\"button\" onclick=\"xajax_insertarPaquete(".$row['id_paquete'].", xajax.getFormValues('frmListaAdicional'), xajax.getFormValues('frmDcto'));\" style=\"cursor:default\" value=\"Agregar Paquete\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td>&nbsp;</td><td><img src=\"../img/iconos/plus.png\"/></td><td>&nbsp;</td><td>Agregar Paquete</td></tr></table></button>";
						$htmlTb .= "</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				}
				$htmlTb .= "</fieldset>";
			$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 1) == 0) ? "</tr>" : "";
	}
	
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaPaquete","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}
	
function listaUnidadBasica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelos.catalogo = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_uni_bas LIKE %s
		OR nom_modelo LIKE %s
		OR nom_version LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT *
	FROM vw_iv_modelos
		INNER JOIN sa_unidad_empresa unid_emp ON (vw_iv_modelos.id_uni_bas = unid_emp.id_unidad_basica) %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";

		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td rowspan=\"5\">"."<button type=\"button\" onclick=\"xajax_asignarUnidadBasica('".$row['id_uni_bas']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
				$htmlTb .= sprintf("<td rowspan=\"5\" style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "100%",
					utf8_encode($row['nom_uni_bas']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_marca']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_modelo']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_version']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>Año %s</td>", utf8_encode($row['nom_ano']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
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

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO')");
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		// UNIDADES QUE ESTAN EN LA MISMA EMPRESA DE DONDE SE CREA EL PEDIDO
		// UNIDADES QUE ESTEN EN SUCURSALES DE LA EMPRESA PRINCIPAL DE DONDE SE CREA EL PEDIDO
		// UNIDADES QUE ESTEN EN LA EMPRESA PRINCIPAL DE LAS SUCURSALES DE DONDE SE CREA EL PEDIDO
		// UNIDADES QUE ESTEN EN LAS SUCURSALES QUE PERTENEZCAN A LA EMPRESA PRINCIPAL DE LA SURCURSAL DE DONDE SE CREA EL PEDIDO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = alm.id_empresa)
		OR alm.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = %s)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
														WHERE suc.id_empresa = %s))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_uni_bas = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
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
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
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
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "uni_fis.id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id Unidad Física"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, ("Color"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Ingreso"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estado Venta"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "14%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, ("Almacén"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Asignación"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Fact. Compra"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "precio_compra", $campOrd, $tpOrd, $valBusq, $maxRows, ("Costo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"30\">".($row['vehiculo'].((strlen($row['mostrar_catalogo']) > 0) ? " <b>[".$row['mostrar_catalogo']."]</b>" : ""))."</td>";
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
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
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
			cxp_fact.id_modulo,
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
			
			$aVerDcto = "";
			if ($rowUnidadFisica['id_factura'] > 0) {
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_factura_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Registro Compra")."\"/><a>",
					$rowUnidadFisica['id_factura']);
				switch ($rowUnidadFisica['id_modulo']) {
					case 0: $aVerDctoAux = sprintf("../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=%s", $rowUnidadFisica['id_factura']); break;
					case 2: $aVerDctoAux = sprintf("../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=%s", $rowUnidadFisica['id_factura']); break;
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Registro Compra PDF")."\"/></a>" : "";
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>";
				if (!in_array($rowUnidadFisica['estado_venta'],array("RESERVADO"))) {
					$htmlTb .= "<button type=\"button\" onclick=\"xajax_asignarUnidadFisica('".$rowUnidadFisica['id_unidad_fisica']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>";
				}
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</div>";
					$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode($rowUnidadFisica['condicion_unidad'])."</div>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= (($rowUnidadFisica['fecha_origen'] != "") ? "<div>".date(spanDateFormat, strtotime($rowUnidadFisica['fecha_origen']))."</div>" : "");
					$htmlTb .= (($rowUnidadFisica['dias_inventario'] > 0) ? "<div class=\"textoNegrita_9px\">".($rowUnidadFisica['dias_inventario']." días")."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['estado_venta'])."</div>";
					$htmlTb .= (($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<div class=\"textoNegrita_9px\">(".utf8_encode($rowUnidadFisica['estado_compra']).")</div>" : "");
					$htmlTb .= (($rowUnidadFisica['id_activo_fijo'] > 0) ? "<div class=\"textoNegrita_9px\">Código: ".$rowUnidadFisica['id_activo_fijo']."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
						$htmlTb .= "<td>".$imgModuloDcto."</td>";
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
			$htmlTb .= "</tr>";
			
			$arrayTotal['cant_unidades'] = $contFila2;
			$arrayTotal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Subtotal:<br>".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_unidades'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['precio_compra'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	if ($pageNum == $totalPages) {
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
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
			cxp_fact.id_modulo,
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
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$contFila2++;
			
			$arrayTotalFinal['cant_unidades'] = $contFila2;
			$arrayTotalFinal['precio_compra'] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total de Totales:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal['cant_unidades'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal['precio_compra'], 2, ".", ",")."</td>";
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
		$htmlTb .= "<td colspan=\"30\">";
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
		if ($frmPermiso['hddModulo'] == "an_pedido_venta_form_entidad_bancaria") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
			$objResponse->assign("hddSinBancoFinanciar","value","1");
			$objResponse->script("byId('aDesbloquearSinBancoFinanciar').style.display = 'none';");
		} else if ($frmPermiso['hddModulo'] == "an_pedido_venta_form_unidad_fisica") {
			$objResponse->script("
			byId('txtKilometraje').className = 'inputHabilitado';
			byId('txtKilometraje').readOnly = false;
			byId('txtPlaca').className = 'inputHabilitado';
			byId('txtPlaca').readOnly = false;");
			
			$objResponse->script("
			byId('aDesbloquearKilometraje').style.display = 'none';
			byId('aDesbloquearPlaca').style.display = 'none';");
			
			$objResponse->script("byId('btnCancelarPermiso').click();");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarFactor");
$xajax->register(XAJAX_FUNCTION,"asignarMoneda");
$xajax->register(XAJAX_FUNCTION,"asignarPoliza");
$xajax->register(XAJAX_FUNCTION,"asignarSinBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"bloquearLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"buscarAdicional");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstMesesFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstPoliza");
$xajax->register(XAJAX_FUNCTION,"cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"eliminarAdicionalLote");
$xajax->register(XAJAX_FUNCTION,"formCliente");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarCliente");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarAdicional");
$xajax->register(XAJAX_FUNCTION,"insertarPaquete");
$xajax->register(XAJAX_FUNCTION,"listaAdicional");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaPaquete");
$xajax->register(XAJAX_FUNCTION,"listaUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function insertarItemAdicional($contFila, $idPedidoAdicional = "", $hddIdAdicionalItm = "", $hddIdAdicionalPaqueteItm = "", $txtPrecioConIvaItm = "", $hddCostoUnitarioItm = "", $txtPrecioPagadoItm = "", $hddPorcIvaItm = "", $hddAplicaIvaItm = "", $cbxCondicion = "", $cbxMostrar = "", $hddTipoAdicional = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPedidoAdicional > 0) {
		// BUSCA EL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT
			acc_ped.id_accesorio_pedido,
			acc_ped.id_pedido,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_ped.id_tipo_accesorio,
			(CASE acc_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			acc_ped.precio_accesorio,
			acc_ped.costo_accesorio,
			acc_ped.porcentaje_iva_accesorio,
			(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_ped.iva_accesorio,
			acc_ped.monto_pagado,
			acc_ped.id_condicion_pago,
			acc_ped.id_condicion_mostrar,
			acc_ped.estatus_accesorio_pedido
		FROM an_accesorio_pedido acc_ped
			INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
		WHERE acc_ped.id_accesorio_pedido = %s
		ORDER BY acc_ped.id_accesorio_pedido ASC;",
			valTpDato($idPedidoAdicional, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	}
	
	$estatusPedidoDet = ($estatusPedidoDet == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['estatus_accesorio_pedido'] : 0;
	
	// BUSCA LOS DATOS DEL ADICIONAL
	$queryAdicional = sprintf("SELECT acc.*,
		(CASE acc.id_tipo_accesorio
			WHEN 1 THEN	'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
		END) AS descripcion_tipo_accesorio,
		IF(iva_accesorio = 1, (SELECT SUM(iva) FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva), 0) AS porcentaje_iva_accesorio,
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM an_accesorio acc
		LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
		LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
	WHERE acc.id_accesorio = %s;",
		valTpDato($hddIdAdicionalItm, "int"));
	$rsAdicional = mysql_query($queryAdicional);
	if (!$rsAdicional) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsAdicional = mysql_num_rows($rsAdicional);
	$rowAdicional = mysql_fetch_assoc($rsAdicional);
	
	$txtPrecioConIvaItm = ($txtPrecioConIvaItm == "" && !($idPedidoAdicional > 0)) ? $rowAdicional['precio_accesorio'] : $txtPrecioConIvaItm;
	$hddCostoUnitarioItm = ($hddCostoUnitarioItm == "" && !($idPedidoAdicional > 0)) ? $rowAdicional['costo_accesorio'] : $hddCostoUnitarioItm;
	$txtPrecioPagadoItm = ($txtPrecioPagadoItm == "" && !($idPedidoAdicional > 0)) ? $rowAdicional['monto_pagado'] : $txtPrecioPagadoItm;
	$hddPorcIvaItm = ($hddPorcIvaItm == "" && !($idPedidoAdicional > 0)) ? $rowAdicional['porcentaje_iva_accesorio'] : $hddPorcIvaItm;
	$hddAplicaIvaItm = ($hddAplicaIvaItm == "" && !($idPedidoAdicional > 0)) ? $rowAdicional['iva_accesorio'] : $hddAplicaIvaItm;
	$hddTipoAdicional = ($hddTipoAdicional == "" && !($idPedidoAdicional > 0)) ? $rowAdicional['id_tipo_accesorio'] : $hddTipoAdicional;
	$nombreAdicional = $rowAdicional['nom_accesorio']. (($hddAplicaIvaItm == 1) ? " (Incluye Impuesto)" : "(E)");
	
	$cbxItmAdicional = (in_array($estatusPedidoDet,array(0))) ?
		sprintf("<input id=\"cbxItmAdicional\" name=\"cbxItmAdicional[]\" type=\"checkbox\" value=\"%s\"/>",
			$contFila) : "";
	$checkedCondicionItm = ($cbxCondicion == 1 || in_array(idArrayPais,array(1,2))) ? "checked=\"checked\"" : "";
	$selectedSeleccione = (!in_array($cbxMostrar,array(1,2))) ? "selected=\"selected\"" : "";
	$selectedMostrarPrecio = ($cbxMostrar == 1) ? "selected=\"selected\"" : "";
	$selectedMostrarCosto = ($cbxMostrar == 2) ? "selected=\"selected\"" : "";
	$selectedTipoAdicional = ($hddTipoAdicional == 1) ? "selected=\"selected\"" : "";
	$selectedTipoContrato = ($hddTipoAdicional == 3) ? "selected=\"selected\"" : "";
	$className = (in_array($estatusPedidoDet,array(0))) ? "class=\"inputCompletoHabilitado\"" : "class=\"inputCompleto\"";
	$readOnly = (in_array($estatusPedidoDet,array(0))) ? "" : "readonly=\"readonly\"";
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieAdicional').before('".
		"<tr id=\"trItmAdicional:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmAdicional:%s\">%s".
				"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>".
				"<table border=\"0\" width=\"%s\">".
				"<tr>".
					"<td width=\"%s\">".				
						"<table id=\"tblCondicionItm%s\" cellpadding=\"0\" cellspacing=\"0\">".
						"<tr>".
							"<td><input type=\"checkbox\" id=\"cbxCondicionItm%s\" name=\"cbxCondicionItm%s\" %s value=\"1\"/></td>".
							"<td><label for=\"cbxCondicionItm%s\">Pagado</label></td>".
						"</tr>".
						"</table>".
					"</td>".
					"<td width=\"%s\">".
						"<select id=\"lstTipoAdicionalItm%s\" name=\"lstTipoAdicionalItm%s\" %s>".
							"<option %s value=\"1\">Adicional</option>".
							"<option %s value=\"3\">Contrato</option>".
						"</select>".
					"</td>".
				"</tr>".
				"<tr id=\"trPrecioPagadoItm%s\">".
					"<td colspan=\"2\">".
						"<table width=\"%s\">".
						"<tr>".
							"<td align=\"right\" width=\"%s\">Monto Pagado:</td>".
							"<td width=\"%s\">"."<input type=\"text\" id=\"txtPrecioPagadoItm%s\" name=\"txtPrecioPagadoItm%s\" class=\"inputCompletoHabilitado\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right;\" value=\"%s\"/>"."</td>".
						"</tr>".
						"</table>".
					"</td>".
				"</tr>".
				"<tr>".
					"<td colspan=\"2\">".
						"<select id=\"lstMostrarItm%s\" name=\"lstMostrarItm%s\" %s>".
							"<option %s value=\"\">"."[ Seleccione ]"."</option>".
							"<option %s value=\"1\">"."Incluir en el Precio"."</option>".
							"<option %s value=\"2\">"."Incluir en el Costo"."</option>".
						"</select>".
					"</td>".
				"</tr>".
				"</table>".
			"</td>".
			"<td><input type=\"text\" id=\"txtPrecioConIvaItm%s\" name=\"txtPrecioConIvaItm%s\" %s %s onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right;\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdDetItm%s\" name=\"hddIdDetItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAdicionalItm%s\" name=\"hddIdAdicionalItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAdicionalPaqueteItm%s\" name=\"hddIdAdicionalPaqueteItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoUnitarioItm%s\" name=\"hddCostoUnitarioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPorcIvaItm%s\" name=\"hddPorcIvaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddAplicaIvaItm%s\" name=\"hddAplicaIvaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoAdicionalItm%s\" name=\"hddTipoAdicionalItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('cbxCondicionItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		
		byId('lstMostrarItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		
		byId('lstTipoAdicionalItm%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
			%s
		}
		
		byId('txtPrecioPagadoItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}
		
		byId('txtPrecioConIvaItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'));
		}",
		$contFila, $clase,
			$contFila, $cbxItmAdicional,
				$contFila,
			utf8_encode($nombreAdicional),
				"100%",
					"50%",
						$contFila,
							$contFila, $contFila, $checkedCondicionItm,
							$contFila,
					"50%",
						$contFila, $contFila, $className,
							$selectedTipoAdicional,
							$selectedTipoContrato,
				$contFila,
					"100%",
						"42%",
						"58%", $contFila, $contFila, number_format($txtPrecioPagadoItm, 2, ".", ","),
						$contFila, $contFila, $className,
							$selectedSeleccione,
							$selectedMostrarPrecio,
							$selectedMostrarCosto,
			$contFila, $contFila, $className, $readOnly, number_format($txtPrecioConIvaItm, 2, ".", ","),
				$contFila, $contFila, $idPedidoAdicional,
				$contFila, $contFila, $hddIdAdicionalItm,
				$contFila, $contFila, $hddIdAdicionalPaqueteItm,
				$contFila, $contFila, $hddCostoUnitarioItm,
				$contFila, $contFila, $hddPorcIvaItm,
				$contFila, $contFila, $hddAplicaIvaItm,
				$contFila, $contFila, $hddTipoAdicionalItm,
		
		$contFila,
		
		$contFila,
		
		$contFila,
			((in_array($estatusPedidoDet,array(0))) ? "" : "selectedOption(this.id,'".$hddTipoAdicional."');"),
		
		$contFila,
		
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemClienteEmpresa($contFila, $idClienteEmpresa = "", $idEmpresa = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idClienteEmpresa > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryClienteEmpresa = sprintf("SELECT
			cliente_emp.id_cliente_empresa,
			cliente_emp.id_empresa,
			cred.id AS id_credito,
			cred.diascredito,
			cred.fpago,
			cred.limitecredito,
			cred.creditoreservado,
			cred.creditodisponible,
			cred.intereses
		FROM cj_cc_credito cred
			RIGHT JOIN cj_cc_cliente_empresa cliente_emp ON (cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
		WHERE cliente_emp.id_cliente_empresa = %s;",
			valTpDato($idClienteEmpresa, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsClienteEmpresa = mysql_num_rows($rsClienteEmpresa);
		$rowClienteEmpresa = mysql_fetch_assoc($rsClienteEmpresa);
	}
	
	$idEmpresa = ($idEmpresa == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_empresa'] : $idEmpresa;
	$idCredito = ($idCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_credito'] : $idCredito;
	$txtDiasCredito = ($txtDiasCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['diascredito'] : $txtDiasCredito;
	$txtFormaPago = ($txtFormaPago == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['fpago'] : $txtFormaPago;
	$txtLimiteCredito = ($txtLimiteCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['limitecredito'] : $txtLimiteCredito;
	$txtCreditoReservado = ($txtCreditoReservado == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditoreservado'] : $txtCreditoReservado;
	$txtCreditoDisponible = ($txtCreditoDisponible == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditodisponible'] : $txtCreditoDisponible;
	$txtIntereses = ($txtIntereses == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['intereses'] : $txtIntereses;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDiasCredito%s\" name=\"txtDiasCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtFormaPago%s\" name=\"txtFormaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtLimiteCredito%s\" name=\"txtLimiteCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoReservado%s\" name=\"txtCreditoReservado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoDisponible%s\" name=\"txtCreditoDisponible%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><a id=\"aEditarCredito%s\" class=\"modalImg\" rel=\"#divFlotante2\"><img class=\"puntero\" src=\"../img/iconos/edit_privilegios.png\" title=\"Editar Crédito\"/></a>".
				"<input type=\"hidden\" id=\"hddIdClienteEmpresa%s\" name=\"hddIdClienteEmpresa%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCredito%s\" name=\"hddIdCredito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarCredito%s').onclick = function() {
			abrirDivFlotante2(this, 'tblCredito', '%s');
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['nombre_empresa']),
			$contFila, $contFila, number_format($txtDiasCredito, 0, ".", ","),
			$contFila, $contFila, $txtFormaPago,
			$contFila, $contFila, number_format($txtLimiteCredito, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoReservado, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoDisponible, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $idClienteEmpresa,
				$contFila, $contFila, $idCredito,
				$contFila, $contFila, $idEmpresa,
			
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>