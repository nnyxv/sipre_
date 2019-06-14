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

function asignarEmpleado($idEmpleado, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	$queryEmpleado = sprintf("SELECT vw_pg_empleado.* FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
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

function calcularPagos($frmListaDctoPagado, $frmDcto){
    $objResponse = new xajaxResponse();

    $txtMontoPago = str_replace(",", "", $frmDcto['txtTotalTransferencia']); // Viene con formato 0,000.00	
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmListaDctoPagado['cbx4'];
	if (isset($arrayObj4)) {
		$i = 0;
		foreach ($arrayObj4 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDctoPagado:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDctoPago:".$valor,"innerHTML",$i);
			
			$arrayAnticipo = explode("|",$frmListaDctoPagado['cbxDctoAgregado'][$indice]);
			
			$txtTotalDctoPagadosAnticipo += ($frmListaDctoPagado['hddEstatusPago'.$valor] == 1) ? str_replace(",", "", $frmListaDctoPagado['txtMontoPagado'.$valor]) : 0;
		}
	}
	
    $totalFaltaPorPagar = $txtMontoPago - $txtTotalDctoPagadosAnticipo;
	
    $objResponse->assign("txtTotalDctoPagado","value",number_format($txtTotalDctoPagadosAnticipo, 2, ".", ","));
    $objResponse->assign("txtMontoRestante","value",number_format($totalFaltaPorPagar, 2, ".", ","));
	
	if (count($arrayObj4) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtMontoPago').readOnly = true;
		byId('txtMontoPago').className = 'inputInicial';");
    } else if (!($frmDcto['hddIdTransferencia'] > 0)) {
		$objResponse->script("
		byId('txtMontoPago').readOnly = false;
		byId('txtMontoPago').className = 'inputHabilitado';");
    }

    return $objResponse;
}

function cargaLstBancoCliente($nombreObjeto, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected."  value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstBancoCompania($tipoPago = "", $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_cargaLstCuentaCompania(this.value,".$tipoPago.");\"";
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select name=\"selBancoCompania\" id=\"selBancoCompania\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstCuentaCompania($row["idBanco"], $tipoPago)); }
		
		$html .= "<option ".$selected."  value=\"".$row["idBanco"]."\">".utf8_encode($row["banco"])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselBancoCompania","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCuentaCompania($idBanco, $tipoPago, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_cargaLstTarjetaCuenta(this.value,".$tipoPago.");\"";
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas
	WHERE idBanco = %s
		AND estatus = 1",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select name=\"selNumeroCuenta\" id=\"selNumeroCuenta\" ".$class." ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idCuentas'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstTarjetaCuenta($row["idCuentas"], $tipoPago)); }
		
		$html .= "<option ".$selected." value=\"".$row["idCuentas"]."\">".utf8_encode($row["numeroCuentaCompania"])."</option>";	
	}
	$html .= "</select>";
	$objResponse->assign("divselNumeroCuenta","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTarjetaCuenta($idCuenta, $tipoPago, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 5) { // Tarjeta de Crédito
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito FROM tipotarjetacredito 
		WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta FROM te_retencion_punto
										WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN (6))
		ORDER BY descripcionTipoTarjetaCredito",
			valTpDato($idCuenta, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select id=\"tarjeta\" name=\"tarjeta\" class=\"inputHabilitado\" onchange=\"xajax_asignarPorcentajeTarjetaCredito(".$idCuenta.",this.value)\" style=\"width:200px\">";
			$html .= "<option value=\"\">[ Seleccione ]</option>";
		while($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row['idTipoTarjetaCredito'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row["idTipoTarjetaCredito"])); }
			
			$html .= "<option ".$selected." value=\"".$row['idTipoTarjetaCredito']."\">".$row['descripcionTipoTarjetaCredito']."</option>";
		}
		$html .= "</select>";
		$objResponse->assign("tdtarjeta","innerHTML",$html);
	} else if ($tipoPago == 6) { // Tarjeta de Debito
		$query = sprintf("SELECT porcentaje_comision FROM te_retencion_punto WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta IN (6);",
			valTpDato($idCuenta,'int'));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	}
	
	return $objResponse;
}

function cargaLstTipoTransferencia($selId = "", $bloquearObj = false){//si es puerto rico, permitir cambio y uso de tipo de transferencia suplidor
    $objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
    $queryConfig403 = sprintf("SELECT *
	FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
    valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
    $rsConfig403 = mysql_query($queryConfig403);
    if (!$rsConfig403) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
    $rowConfig403 = mysql_fetch_assoc($rsConfig403);
    
	$html = "";
    if (in_array($rowConfig403['valor'],array(3))) { // 3 = Puerto Rico
		$array = array("1" => "Cliente", "2" => "Bono Suplidor", "3" => "PND");
		$totalRows = count($array);
		
		$html .= "<select id=\"lstTipoTransferencia\" name=\"lstTipoTransferencia\" ".$class." ".$onChange." style=\"width:99%\">";
        	$html .= "<option value=\"\">[ Seleccione ]</option>";
		foreach ($array as $indice => $valor) {
			$selected = ($selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
		}
        $html .= "</select>";
    } else {
		$array = array("1" => "Cliente");
		$totalRows = count($array);
		
		$html .= "<select id=\"lstTipoTransferencia\" name=\"lstTipoTransferencia\" ".$class." ".$onChange." style=\"width:99%\">";
		foreach ($array as $indice => $valor) {
			$selected = ($selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
		}
        $html .= "</select>";
    }
	
	$objResponse->assign("tdlstTipoTransferencia","innerHTML", $html);

    return $objResponse;
}

function cargaLstTipoDcto($selId = ""){
	$objResponse = new xajaxResponse();
	
	$array = array("FACTURA" => "Factura", "NOTA DEBITO" => "Nota de Débito", "ANTICIPO" => "Anticipo", "ANTICIPO_OTRO" => "Anticipo (Bono, PND)");
	
	$html = "<select id=\"lstTipoDcto\" name=\"lstTipoDcto\" class=\"inputHabilitado\" onchange=\"byId('btnBuscarAnticipo').click();\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoDcto","innerHTML",$html);
	
	return $objResponse;
}

function cargarDcto($idTransferencia){
    $objResponse = new xajaxResponse();
	
	global $raiz;
    
	if ($idTransferencia > 0) {
		$objResponse->script("
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		
		byId('txtObservacionTransferencia').readOnly = true;
		
		byId('divselNumeroCuenta').style.display = '';
		byId('txtNumeroCuenta').style.display = 'none';
		
		byId('txtNumeroCuenta').readOnly = true;
		byId('txtNumeroDctoPago').readOnly = true;
		byId('txtMontoPago').readOnly = true;");
		
		// BUSCA LOS DATOS DEL ANTICIPO
		$queryTransferencia = sprintf("SELECT *,
			IF (tb.estatus = 1, tb.saldo_transferencia, 0) AS saldo_transferencia,
			IF (tb.estatus = 1, tb.estado_transferencia, NULL) AS estado_transferencia,
			(CASE tb.estatus
				WHEN 1 THEN
					(CASE tb.estado_transferencia
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado (No Asignado)'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
					END)
				ELSE
					'Anulado'
			END) AS descripcion_estado_transferencia
		FROM cj_cc_transferencia tb WHERE id_transferencia = %s;",
			valTpDato($idTransferencia, "int"));
		$rsTransferencia = mysql_query($queryTransferencia);
		if (!$rsTransferencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTransferencia = mysql_num_rows($rsTransferencia);
		$rowTransferencia = mysql_fetch_assoc($rsTransferencia);
		
		switch($rowTransferencia['estado_transferencia']) {
			case "" : $classEstatus = "divMsjInfo5"; break;
			case 0 : $classEstatus = "divMsjError"; $imgEstatus = "<img src=\"../img/iconos/no_cancelado.png\">"; break;
			case 1 : $classEstatus = "divMsjInfo"; $imgEstatus = "<img src=\"../img/iconos/cancelado.png\">"; break;
			case 2 : $classEstatus = "divMsjAlerta"; $imgEstatus = "<img src=\"../img/iconos/cancelado_parcial.png\">"; break;
			case 3 : $classEstatus = "divMsjInfo3"; break;
			case 4 : $classEstatus = "divMsjInfo4"; break;
		}
		
		$idEmpresa = $rowTransferencia['id_empresa'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "", false));
		$objResponse->loadCommands(asignarEmpleado($rowTransferencia['id_empleado_registro'], false));
		
		$objResponse->assign("hddIdTransferencia","value",$idTransferencia);
		$objResponse->loadCommands(asignarCliente($rowTransferencia['id_cliente']));
		$objResponse->loadCommands(cargaLstTipoTransferencia($rowTransferencia['tipo_transferencia'], true));
		$objResponse->loadCommands(cargaLstModulo($rowTransferencia['id_departamento'], "", true));
		$objResponse->assign("txtFecha","value",date(spanDateFormat, strtotime($rowTransferencia['fecha_transferencia'])));
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowTransferencia['descripcion_estado_transferencia']);
		$objResponse->assign("txtObservacionTransferencia","value",$rowTransferencia['observacion_transferencia']);
		
		$objResponse->loadCommands(cargaLstBancoCliente("selBancoCliente", $rowTransferencia['id_banco_cliente'], true));
		$objResponse->loadCommands(cargaLstBancoCompania(4, $rowTransferencia['id_banco_compania'], true));
		$objResponse->loadCommands(cargaLstCuentaCompania($rowTransferencia['id_banco_compania'], 4, $rowTransferencia['id_cuenta_compania'], true));
		$objResponse->assign("txtNumeroCuenta","value",$rowTransferencia['cuenta_compania']);
		$objResponse->assign("txtNumeroDctoPago","value",$rowTransferencia['numero_transferencia']);
		$objResponse->assign("txtMontoPago","value",number_format($rowTransferencia['monto_neto_transferencia'], 2, ".", ","));
		
		$objResponse->loadCommands(cargaLstTipoDcto());
		
		if ($totalRowsTransferencia > 0) {
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array("TB",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
			$objDcto->tipoDocumento = "TB";
			$objDcto->tipoDocumentoMovimiento = (in_array("TB",array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $rowTransferencia['id_departamento'];
			$objDcto->idDocumento = $idTransferencia;
			$objDcto->mostrarDocumento = "verVentanaPDF";
			$aVerDcto = $objDcto->verDocumento();
			
			$objResponse->script("
			byId('btnReciboPagoPDF').style.display = '';
			byId('btnReciboPagoPDF').onclick = function() { ".$aVerDcto." }");
		}
		
		$objResponse->assign("txtTotalTransferencia","value",number_format($rowTransferencia['monto_neto_transferencia'], 2, ".", ","));
		$objResponse->assign("txtTotalSaldo","value",number_format($rowTransferencia['saldo_transferencia'], 2, ".", ","));
		
		// BUSCA LOS DOCUMENTOS PAGADOS
		$query = sprintf("SELECT
			cxc_pago.idPago AS id_pago,
			cxc_pago.id_factura,
			NULL AS id_nota_cargo,
			NULL AS id_anticipo,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.estatus
		FROM an_pagos cxc_pago
		WHERE cxc_pago.formaPago IN (4)
			AND cxc_pago.id_transferencia = %s
		
		UNION
		
		SELECT
			cxc_pago.idPago,
			cxc_pago.id_factura,
			NULL AS id_nota_cargo,
			NULL AS id_anticipo,
			cxc_pago.idCaja,
			cxc_pago.montoPagado,
			cxc_pago.estatus
		FROM sa_iv_pagos cxc_pago
		WHERE cxc_pago.formaPago IN (4)
			AND cxc_pago.id_transferencia = %s
		
		UNION
		
		SELECT
			cxc_pago.id_det_nota_cargo,
			NULL AS id_factura,
			cxc_pago.idNotaCargo,
			NULL AS id_anticipo,
			cxc_pago.idCaja,
			cxc_pago.monto_pago,
			cxc_pago.estatus
		FROM cj_det_nota_cargo cxc_pago
		WHERE cxc_pago.idFormaPago IN (4)
			AND cxc_pago.id_transferencia = %s
		
		UNION
		
		SELECT
			cxc_pago.idDetalleAnticipo,
			NULL AS id_factura,
			NULL AS idNotaCargo,
			cxc_pago.idAnticipo,
			cxc_pago.idCaja,
			cxc_pago.montoDetalleAnticipo,
			cxc_pago.estatus
		FROM cj_cc_detalleanticipo cxc_pago
		WHERE cxc_pago.id_forma_pago IN (4)
			AND cxc_pago.id_transferencia = %s;",
			valTpDato($idTransferencia, "int"),
			valTpDato($idTransferencia, "int"),
			valTpDato($idTransferencia, "int"),
			valTpDato($idTransferencia, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemDctoPagado($contFila, $row['id_pago'], $row['id_factura'], $row['id_nota_cargo'], $row['id_anticipo'], $row['idCaja']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj4[] = $contFila;
			}
			
			// SUMA LOS PAGOS
			$txtTotalDctoPagado += (in_array($row['estatus'],array(1))) ? $row['montoPagado'] : 0;
		}
		
	    $objResponse->script("calcularPagos();");
	} 
	
	return $objResponse;
}

function insertarDctoPagado($frmAnticipoNotaCreditoChequeTransferencia, $frmListaDctoPagado, $frmListaAnticipo){
    $objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmListaDctoPagado['cbx4'];
	$contFila = $arrayObj4[count($arrayObj4)-1];
	
    if (str_replace(",", "", $frmListaDctoPagado['txtMontoRestante']) < str_replace(",", "", $frmAnticipoNotaCreditoChequeTransferencia['txtMontoDocumento'])){
        return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la transferencia");
    }

    foreach ($frmListaDctoPagado['cbxDctoAgregado'] as $key => $valor){
		$arrayAnticipo = explode("|",$valor);
		$hddIdPago = $arrayAnticipo[0];
		$tipoDcto = $arrayAnticipo[1];
		$idAnticipo = $arrayAnticipo[2];
		
        if (!($hddIdPago > 0) && $tipoDcto == $frmAnticipoNotaCreditoChequeTransferencia['hddTipoDocumento'] && $idAnticipo == $frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento']) {
			return $objResponse->alert("El documento seleccionado ya se encuentra agregado");
        }
    }
	
	$hddTipoDocumento = $frmAnticipoNotaCreditoChequeTransferencia['hddTipoDocumento'];
	
	switch ($hddTipoDocumento) {
		case "FACTURA" :
			$queryDocumento = sprintf("SELECT DISTINCT
				cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
				cxc_ec.tipoDocumentoN,
				cxc_ec.tipoDocumento,
				cxc_fact.idFactura,
				cxc_fact.fechaRegistroFactura,
				cxc_fact.fechaVencimientoFactura,
				cxc_fact.fecha_pagada,
				cxc_fact.fecha_cierre,
				cxc_fact.numeroFactura,
				cxc_fact.numeroControl,
				cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
				cxc_fact.condicionDePago,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_ped_vent.id_pedido_venta
					WHEN 2 THEN	ped_vent.id_pedido
				END) AS id_pedido,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_ped_vent.id_pedido_venta_propio
					WHEN 2 THEN	ped_vent.numeracion_pedido
				END) AS numeracion_pedido,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_pres_vent.id_presupuesto_venta
					WHEN 2 THEN	pres_vent.id_presupuesto
				END) AS id_presupuesto,
				(CASE cxc_fact.idDepartamentoOrigenFactura
					WHEN 0 THEN	iv_pres_vent.numeracion_presupuesto
					WHEN 2 THEN	pres_vent.numeracion_presupuesto
				END) AS numeracion_presupuesto,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				uni_fis.placa,
				ped_comp_det.flotilla,
				cxc_fact.estadoFactura,
				(CASE cxc_fact.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS descripcion_estado_factura,
				cxc_fact.aplicaLibros,
				cxc_fact.observacionFactura,
				cxc_fact.anulada,
				cxc_fact.saldoFactura,
				cxc_fact.montoTotalFactura,
				
				(IFNULL(cxc_fact.subtotalFactura, 0)
					- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
				IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
						WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva,
				(IFNULL(cxc_fact.subtotalFactura, 0)
					- IFNULL(cxc_fact.descuentoFactura, 0)
					+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
								WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
								WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0)) AS total,
				
				(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios FROM cj_cc_factura_detalle_accesorios fact_det_acc2
				WHERE fact_det_acc2.id_factura = cxc_fact.idFactura) AS cantidad_accesorios,
				cxc_fact.anulada,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_encabezadofactura cxc_fact
				LEFT JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
				LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
				INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
				LEFT JOIN iv_pedido_venta iv_ped_vent ON (cxc_fact.numeroPedido = iv_ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
				LEFT JOIN iv_presupuesto_venta iv_pres_vent ON (iv_ped_vent.id_presupuesto_venta = iv_pres_vent.id_presupuesto_venta)
				LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
				LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
				LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
				LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_fact.idFactura = %s;", 
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
		case "NOTA DEBITO" : 
			$queryDocumento = sprintf("SELECT
				cxc_nd.idNotaCargo AS idFactura,
				cxc_nd.numeroNotaCargo AS numeroFactura,
				cxc_nd.numeroControlNotaCargo,
				cxc_nd.fechaRegistroNotaCargo AS fechaRegistroFactura,
				cxc_nd.fechaVencimientoNotaCargo,
				cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cxc_nd.estadoNotaCargo AS estadoFactura,
				(CASE cxc_nd.estadoNotaCargo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS descripcion_estado_factura,
				cxc_nd.montoTotalNotaCargo,
				cxc_nd.saldoNotaCargo,
				cxc_nd.observacionNotaCargo AS observacionFactura,
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
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
		case "ANTICIPO" : 
			$queryDocumento = sprintf("SELECT
				cxc_ant.idAnticipo AS idFactura,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cxc_ant.montoNetoAnticipo,
				cxc_ant.totalPagadoAnticipo,
				cxc_ant.saldoAnticipo,
				cxc_ant.fechaAnticipo AS fechaRegistroFactura,
				cxc_ant.numeroAnticipo AS numeroFactura,
				cxc_ant.idDepartamento AS id_modulo,
				cxc_ant.estadoAnticipo AS estadoFactura,
				(CASE cxc_ant.estadoAnticipo
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
					WHEN 4 THEN 'No Cancelado (Asignado)'
				END) AS descripcion_estado_factura,
				cxc_ant.observacionesAnticipo AS observacionFactura,
				
				(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
				FROM cj_cc_detalleanticipo cxc_pago
					INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				WHERE cxc_pago.idAnticipo = cxc_ant.idAnticipo
					AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
				
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
				cxc_ant.estatus
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_ant.idAnticipo = %s;", 
				valTpDato($frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'], "int"));
			break;
	}
    $rsDocumento = mysql_query($queryDocumento);
    if (!$rsDocumento) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
    $rowDocumento = mysql_fetch_array($rsDocumento);
	
    $idDocumento = $frmAnticipoNotaCreditoChequeTransferencia['hddIdDocumento'];	
    $txtMontoPagado = str_replace(",", "", $frmAnticipoNotaCreditoChequeTransferencia['txtMontoDocumento']);
	
	$Result1 = insertarItemDctoPagado($contFila, "", "", $hddTipoDocumento, $idDocumento, $rowDocumento['id_modulo'], date("Y-m-d"), $rowDocumento['nombre_empresa'], $rowDocumento['fechaRegistroFactura'], $rowDocumento['numeroFactura'], $rowDocumento['nombre_cliente'], $rowDocumento['estadoFactura'], $rowDocumento['descripcion_concepto_forma_pago'], $rowDocumento['observacionFactura'], $rowDocumento['descripcion_estado_factura'], $txtMontoPagado);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj4[] = $contFila;
	}
	
    $objResponse->script("calcularPagos();");
	
	$objResponse->script("byId('imgCerrarDivFlotante2').click();");

    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoTransferencia");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoDcto");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarDctoPagado");

function asignarBanco($idBanco) {
	$query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s;", valTpDato($idBanco, "int"));
	$rs = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	return utf8_encode($row['nombreBanco']);
}

function asignarNumeroCuenta($idCuenta) {
	$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s;", valTpDato($idCuenta, "int"));
	$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	
	return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}

function insertarItemDctoPagado($contFila, $idPago = "", $idFactura = "", $idNotaCargo = "", $idAnticipo = "", $idCaja = "", $txtFechaPago = "", $txtMetodoPago = "", $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtBancoCompaniaPago = "", $txtCuentaCompaniaPago = "", $txtBancoClientePago = "", $txtCuentaClientePago = "", $txtMontoPagado = "") {
	global $raiz;
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idPago > 0) {
		// BUSCA LOS DATOS DEL PAGO
		$query = sprintf("
		SELECT 
			cxc_pago.idPago,
			cxc_pago.id_factura,
			NULL AS idNotaCargo,
			NULL AS idAnticipo,
			cxc_pago.fechaPago,
			cxc_pago.numeroDocumento AS id_documento,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.formaPago
				WHEN 8 THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			cxc_pago.id_factura AS id_documento_pagado,
			'FA' AS tipo_documento_pagado,
			(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS numero_documento_pagado,
			(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS id_modulo_documento_pagado,
			NULL AS descripcion_motivo_pagado,
			(SELECT cxc_fact.observacionFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS observacion_documento_pagado,
			
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
		WHERE cxc_pago.idPago = %s
			AND cxc_pago.id_factura = %s
			AND cxc_pago.idCaja = %s
			
		UNION
		
		SELECT 
			cxc_pago.idPago,
			cxc_pago.id_factura,
			NULL AS idNotaCargo,
			NULL AS idAnticipo,
			cxc_pago.fechaPago,
			cxc_pago.numeroDocumento AS id_documento,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.formaPago
				WHEN 8 THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.formaPago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			cxc_pago.id_factura AS id_documento_pagado,
			'FA' AS tipo_documento_pagado,
			(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS numero_documento_pagado,
			(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS id_modulo_documento_pagado,
			NULL AS descripcion_motivo_pagado,
			(SELECT cxc_fact.observacionFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = cxc_pago.id_factura) AS observacion_documento_pagado,
			
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.montoPagado,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
		WHERE cxc_pago.idPago = %s
			AND cxc_pago.id_factura = %s
			AND cxc_pago.idCaja = %s
			
		UNION
		
		SELECT
			cxc_pago.id_det_nota_cargo,
			NULL AS id_factura,
			cxc_pago.idNotaCargo,
			NULL AS idAnticipo,
			cxc_pago.fechaPago,
			cxc_pago.numeroDocumento AS id_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS id_modulo,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
				ELSE
					cxc_pago.numeroDocumento
			END) AS numero_documento,
			
			(CASE cxc_pago.idFormaPago
				WHEN 8 THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.idFormaPago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroDocumento)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			cxc_pago.idNotaCargo AS id_documento_pagado,
			'ND' AS tipo_documento_pagado,
			(SELECT cxc_nd.numeroNotaCargo FROM cj_cc_notadecargo cxc_nd WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS numero_documento_pagado,
			(SELECT cxc_nd.idDepartamentoOrigenNotaCargo FROM cj_cc_notadecargo cxc_nd WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS id_modulo_documento_pagado,
			
			(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
			FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_pago.idNotaCargo) AS descripcion_motivo_pagado,
			
			(SELECT cxc_nd.observacionNotaCargo FROM cj_cc_notadecargo cxc_nd WHERE cxc_nd.idNotaCargo = cxc_pago.idNotaCargo) AS observacion_documento_pagado,
			
			cxc_pago.bancoOrigen,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numero_cuenta_cliente,
			cxc_pago.bancoDestino,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.cuentaEmpresa,
			cxc_pago.monto_pago,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idComprobante AS id_recibo_pago,
			recibo.numeroComprobante
		FROM cj_cc_notadecargo cxc_nd
			INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoOrigen = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoDestino = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
			INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND recibo.idTipoDeDocumento = 2)
		WHERE cxc_pago.id_det_nota_cargo = %s
			AND cxc_pago.idNotaCargo = %s
			AND cxc_pago.idCaja = %s
		
		UNION
		
		SELECT
			cxc_pago.idDetalleAnticipo,
			NULL AS id_factura,
			NULL AS idNotaCargo,
			cxc_pago.idAnticipo,
			cxc_pago.fechaPagoAnticipo,
			cxc_pago.numeroControlDetalleAnticipo AS id_documento,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			END) AS id_modulo,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
				ELSE
					cxc_pago.numeroControlDetalleAnticipo
			END) AS numero_documento,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 8 THEN
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroControlDetalleAnticipo)
			END) AS descripcion_motivo,
			
			(CASE cxc_pago.id_forma_pago
				WHEN 7 THEN
					(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.numeroControlDetalleAnticipo)
				WHEN 8 THEN
					(SELECT cxc_nc.observacionesNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = cxc_pago.numeroControlDetalleAnticipo)
			END) AS observacion_documento,
			
			forma_pago.idFormaPago,
			forma_pago.nombreFormaPago,
			
			cxc_pago.idAnticipo AS id_documento_pagado,
			'AN' AS tipo_documento_pagado,
			(SELECT cxc_ant.numeroAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.idAnticipo) AS numero_documento_pagado,
			(SELECT cxc_ant.idDepartamento FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.idAnticipo) AS id_modulo_documento_pagado,
			NULL AS descripcion_motivo_pagado,
			(SELECT cxc_ant.observacionesAnticipo FROM cj_cc_anticipo cxc_ant WHERE cxc_ant.idAnticipo = cxc_pago.idAnticipo) AS observacion_documento_pagado,
			
			cxc_pago.bancoClienteDetalleAnticipo,
			banco_cliente.nombreBanco AS nombre_banco_cliente,
			cxc_pago.numeroCuentaCliente AS numero_cuenta_cliente,
			cxc_pago.bancoCompaniaDetalleAnticipo,
			banco_emp.nombreBanco AS nombre_banco_empresa,
			cxc_pago.numeroCuentaCompania,
			cxc_pago.montoDetalleAnticipo,
			cxc_pago.estatus,
			cxc_pago.fecha_anulado,
			cxc_pago.id_empleado_anulado,
			cxc_pago.tiempo_registro,
			cxc_pago.idCaja,
			caja.descripcion AS nombre_caja,
			vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
			recibo.idReporteImpresion AS id_recibo_pago,
			recibo.numeroReporteImpresion AS numeroComprobante
		FROM cj_cc_anticipo cxc_ant
			INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			INNER JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
			INNER JOIN caja ON (cxc_pago.idCaja = caja.idCaja)
			LEFT JOIN bancos banco_cliente ON (cxc_pago.bancoClienteDetalleAnticipo = banco_cliente.idBanco)
			LEFT JOIN bancos banco_emp ON (cxc_pago.bancoCompaniaDetalleAnticipo = banco_emp.idBanco)
			LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
			INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion)
		WHERE cxc_pago.idDetalleAnticipo = %s
			AND cxc_pago.idAnticipo = %s
			AND cxc_pago.idCaja;",
			valTpDato($idPago, "int"),
			valTpDato($idFactura, "int"),
			valTpDato($idCaja, "int"),
			valTpDato($idPago, "int"),
			valTpDato($idFactura, "int"),
			valTpDato($idCaja, "int"),
			valTpDato($idPago, "int"),
			valTpDato($idNotaCargo, "int"),
			valTpDato($idCaja, "int"),
			valTpDato($idPago, "int"),
			valTpDato($idAnticipo, "int"),
			valTpDato($idCaja, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	} else {
		$cbxItm = sprintf("<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>",
			$contFila);
	}
	
	$classMontoPago = "";
	if ($totalRows > 0 && $row['estatus'] == 2) {
		$classMontoPago = "class=\"divMsjAlerta\"";
	} else if ($totalRows > 0 && $row['estatus'] != 1) {
		$classMontoPago = "class=\"divMsjError\"";
	}
	
	$txtFechaPago = ($txtFechaPago == "" && $totalRows > 0) ? $row['fechaPago'] : $txtFechaPago;
	$txtNumeroRecibo = ($txtNumeroRecibo == "" && $totalRows > 0) ? $row['numeroComprobante'] : $txtNumeroRecibo;
	$txtMetodoPago = ($txtMetodoPago == "" && $totalRows > 0) ? $row['tipo_documento_pagado'] : $txtMetodoPago;
	$txtIdNumeroDctoPago = ($txtIdNumeroDctoPago == "" && $totalRows > 0) ? $row['id_documento_pagado'] : $txtIdNumeroDctoPago;
	$txtNumeroDctoPago = ($txtNumeroDctoPago == "" && $totalRows > 0) ? $row['numero_documento_pagado'] : $txtNumeroDctoPago;
	$txtBancoCompaniaPago = ($txtBancoCompaniaPago == "" && $totalRows > 0) ? $row['nombre_banco_empresa'] : $txtBancoCompaniaPago;
	$txtCuentaCompaniaPago = ($txtCuentaCompaniaPago == "" && $totalRows > 0) ?  $row['cuentaEmpresa'] : $txtCuentaCompaniaPago;
	$txtBancoClientePago = ($txtBancoClientePago == "" && $totalRows > 0) ? $row['nombre_banco_cliente'] : $txtBancoClientePago;
	$txtCuentaClientePago = ($txtCuentaClientePago == "" && $totalRows > 0) ?  $row['numero_cuenta_cliente'] : $txtCuentaClientePago;
	$txtCajaPago = ($txtCajaPago == "" && $totalRows > 0) ? $row['nombre_caja'] : $txtCajaPago;
	$txtMontoPagado = ($txtMontoPagado == "" && $totalRows > 0) ? $row['montoPagado'] : $txtMontoPagado;
	$hddEstatusPago = ($hddEstatusPago == "" && $totalRows > 0) ? $row['estatus'] : 1;
	$descripcionMotivo = (strlen($row['descripcion_motivo_pagado']) > 0) ? "<div align=\"left\"><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_motivo_pagado'])."</span></div>" : "";
	$observacionDctoPago = (strlen($row['observacion_documento_pagado']) > 0) ? "<div align=\"left\"><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_documento_pagado'])."</span></div>" : "";
	$estatusPago = "";
	if ($totalRows > 0 && $row['estatus'] == 2) {
		$estatusPago = "<div align=\"center\">PAGO PENDIENTE</div>";
	} else if ($totalRows > 0 && $row['estatus'] != 1) {
		$estatusPago = "<div align=\"center\">PAGO ANULADO</div>";
	}
	$empleadoCreadorPago = (strlen($row['nombre_empleado']) > 0) ? "<span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".$row['nombre_empleado']."</span>" : "";
	$empleadoAnuladoPago = (strlen($row['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$row['nombre_empleado_anulado']."<br>(".date(spanDateFormat, strtotime($row['fecha_anulado'])).")</span></div>" : "";
	
	switch($row['id_modulo_documento_pagado']) {
		case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
		case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
		case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
		case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
		case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
		case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
		default : $imgDctoModulo = $row['id_modulo_documento_pagado'];
	}
	
	if ($idFactura > 0) {
		$tipoDocumento = "FA";
	} else if ($idNotaCargo > 0) {
		$tipoDocumento = "ND";
	} else if ($idAnticipo > 0) {
		$tipoDocumento = "AN";
	}
	
	$objDcto = new Documento;
	$objDcto->raizDir = $raiz;
	$objDcto->tipoDocumento = $tipoDocumento;
	$objDcto->idModulo = $row['id_modulo_documento_pagado'];
	$objDcto->idDocumento = $row['id_recibo_pago'];
	$aVerRecibo = str_replace("'","\'",$objDcto->verRecibo());
	
	$objDcto = new Documento;
	$objDcto->raizDir = $raiz;
	$objDcto->tipoMovimiento = (in_array($tipoDocumento,array("FA","ND","AN","CH","TB"))) ? 3 : 2;
	$objDcto->tipoDocumento = $tipoDocumento;
	$objDcto->tipoDocumentoMovimiento = (in_array($tipoDocumento,array("NC"))) ? 2 : 1;
	$objDcto->idModulo = $row['id_modulo_documento_pagado'];
	$objDcto->idDocumento = $txtIdNumeroDctoPago;
	$aVerDcto = str_replace("'","\'",$objDcto->verDocumento());
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieDctoPagado').before('".
		"<tr id=\"trItmDctoPagado:%s\" align=\"left\" class=\"textoGris_11px %s\" height=\"24\">".
			"<td align=\"center\" title=\"trItmDctoPagado:%s\">%s".
				"<input type=\"checkbox\" id=\"cbx4\" name=\"cbx4[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmDctoPago:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtFechaPago%s\" name=\"txtFechaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr align=\"right\"><td>%s</td><td width=\"%s\">%s</td></tr></table></td>".
			"<td align=\"center\" %s><input type=\"text\" id=\"txtMetodoPago%s\" name=\"txtMetodoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
				"%s".
				"%s</td>".
			"<td %s><table width=\"%s\"><tr><td nowrap=\"nowrap\">%s</td><td>%s</td><td><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr></table>".
				"<div>%s</div>".
				"<div>%s</div>".
				"<div>%s</div></td>".
			"<td %s><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtCajaPago%s\" name=\"txtCajaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td %s><input type=\"text\" id=\"txtMontoPagado%s\" name=\"txtMontoPagado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusPago%s\" name=\"hddEstatusPago%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $cbxItm,
				$contFila,
			$contFila, $contFila,
			$classMontoPago, $contFila, $contFila, utf8_encode(date(spanDateFormat, strtotime($txtFechaPago))),
				$empleadoCreadorPago,
			$classMontoPago, "100%", $aVerRecibo, "100%", $txtNumeroRecibo,
			$classMontoPago, $contFila, $contFila, ($txtMetodoPago),
				$txtMetodoPagoConcepto,
				$estatusPago,
			$classMontoPago, "100%", $aVerDcto, $imgDctoModulo, $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
				$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				$descripcionMotivo,
				preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$observacionDctoPago)))),
				$empleadoAnuladoPago,
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoClientePago),
				$contFila, $contFila, utf8_encode($txtCuentaClientePago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
				$contFila, $contFila, utf8_encode($txtCuentaCompaniaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode($txtCajaPago),
			$classMontoPago, $contFila, $contFila, utf8_encode(number_format($txtMontoPagado, 2, ".", ",")),
				$contFila, $contFila, $idPago,
				$contFila, $contFila, $hddEstatusPago);
	
	return array(true, $htmlItmPie, $contFila);
}
?>