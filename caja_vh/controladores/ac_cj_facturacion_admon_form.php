<?php

function asignarAnticipoBono($idAnticipo) {
	$objResponse = new xajaxResponse();
	
	// BUSCA EL TIPO DEL ANTICIPO
	$queryAnticipo = sprintf("SELECT cxc_ant.*,
		concepto_forma_pago.id_concepto,
		concepto_forma_pago.descripcion
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
	WHERE cxc_ant.idAnticipo = %s
		AND cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT';",
		valTpDato($idAnticipo, "int"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	$rowAnticipo = mysql_fetch_array($rsAnticipo);
	
	$objResponse->assign("txtMontoDescuentoBono","value",number_format($rowAnticipo['montoNetoAnticipo'], 2, ".", ","));
	
	return $objResponse;
}

function asignarClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento
	WHERE id_clave_movimiento = %s;",
		valTpDato($idClaveMovimiento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
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

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false"){
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
	
	//$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarDepartamento($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
		
	if ($idCliente > 0) {
		if (!($frmDcto['lstModulo'] == 3)) {
			// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
			$arrayObj = $frmListaArticulo['cbx'];
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					$objResponse->script("
					fila = document.getElementById('trItmArticulo:".$valor."');
					padre = fila.parentNode;
					padre.removeChild(fila);");
				}
			}
		}
		
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObjIva = $frmTotalDcto['cbxIva'];
		if (isset($arrayObjIva)) {
			foreach ($arrayObjIva as $indice => $valor) {
				$objResponse->script("
				fila = document.getElementById('trIva:".$valor."');
				padre = fila.parentNode;
				padre.removeChild(fila);");
			}
		}
		
		$objResponse->script("byId('frmTotalDcto').reset();");
		$objResponse->assign("txtObservacion","value",$frmTotalDcto['txtObservacion']);
	}
	
	$objResponse->loadCommands(cargaLstVendedor($idEmpresa));
	
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $frmDcto['lstModulo'], $frmDcto['lstTipoMovimiento'], $frmDcto['hddTipoPagoCliente'], "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));
	
	$objResponse->script("	
	byId('txtSubTotal').onblur = function() {
		setFormatoRafk(this,2);
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
	}
	byId('txtTotalExento').onblur = function() {
		setFormatoRafk(this,2);
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
	}
	byId('txtTotalExonerado').onblur = function() {
		setFormatoRafk(this,2);
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
	}");
	
	if ($frmDcto['lstModulo'] == 3) {
		$objResponse->script("		
		byId('txtSubTotal').className = 'inputSinFondo';
		byId('txtSubTotal').readOnly = true;
		byId('txtTotalExento').className = 'inputSinFondo';
		byId('txtTotalExento').readOnly = true;
		byId('txtTotalExonerado').className = 'inputSinFondo';
		byId('txtTotalExonerado').readOnly = true;");
				
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat));
		
		$objResponse->script("byId('txtFechaPedido').className = 'inputInicial';");
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));");
	} else {
		$objResponse->script("		
		byId('txtSubTotal').className = 'inputHabilitado';
		byId('txtSubTotal').readOnly = false;
		byId('txtTotalExento').className = 'inputHabilitado';
		byId('txtTotalExento').readOnly = false;
		byId('txtTotalExonerado').className = 'inputHabilitado';
		byId('txtTotalExonerado').readOnly = false;");
		
		// CARGA LOS IMPUESTOS 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1 ORDER BY iva");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$indiceIva = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$indiceIva++;
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputHabilitado\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trGastosSinIva').before(elemento);
				
				byId('txtBaseImpIva%s').onblur = function() {
					setFormatoRafk(this,2);
					xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
				}
				byId('txtBaseImpIva%s').onkeypress = function(e) {
					return validarSoloNumerosReales(e);
				}",
				$indiceIva,
					$indiceIva, utf8_encode($rowIva['observacion']),
						$indiceIva, $indiceIva, $rowIva['idIva'],
						$indiceIva, $indiceIva, $rowIva['lujo'],
						$indiceIva,
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
					$indiceIva, $indiceIva, $rowIva['iva'], "%",
					$indiceIva, $indiceIva, number_format(round(0,2), 2, ".", ","),
				
				$indiceIva,
				
				$indiceIva,
				
				$indiceIva));
		}
		
		if ($frmDcto['txtIdCliente'] > 0 || !($frmDcto['lstModulo'] >= 0)) {
			$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));");
		}
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

function asignarPorcentajeTarjetaCredito($idCuenta, $idTarjeta) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto
	WHERE id_cuenta = %s
		AND id_tipo_tarjeta = %s",
		valTpDato($idCuenta, "int"),
		valTpDato($idTarjeta, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("porcentajeRetencion","value",$row['porcentaje_islr']);
	$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	
	$objResponse->script("calcularPorcentajeTarjetaCredito();");
	
	return $objResponse;
}

function buscarAnticipoNotaCreditoChequeTransferencia($frmBuscarAnticipoNotaCreditoChequeTransferencia, $frmDcto, $frmDetallePago, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indicePago => $valorPago) {
			if ($frmListaPagos['txtIdFormaPago'.$valorPago] == $frmDetallePago['selTipoPago']
			&& $frmListaPagos['hddEstatusPago'.$valorPago] == 1) {
				$arrayIdDocumento[] = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
			}
		}
	}
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarAnticipoNotaCreditoChequeTransferencia['txtCriterioAnticipoNotaCreditoChequeTransferencia'],
		$frmDcto['txtIdCliente'],
		$frmDetallePago['selTipoPago'],
		(($arrayIdDocumento) ? implode(",",$arrayIdDocumento) : ""));
		
	$objResponse->loadCommands(listaAnticipoNotaCreditoChequeTransferencia(0,"","",$valBusq));
	
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $frmListaPagos){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmArticulo:".$valor,"className",$clase);
			$objResponse->assign("tdNumItmArticulo:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjItmArticulo","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	if (isset($arrayObjIva) && isset($arrayObj)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trIva:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $frmListaArticulo['txtIdEmpresa'];
	$txtDescuento = round(str_replace(",", "", $frmTotalDcto['txtDescuento']),2);
	$txtSubTotalDescuento = round(str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']),2);
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]) * str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valor]);
			
			$txtSubTotal += $txtTotalItm;
		}
	} else {
		$txtSubTotal = round(str_replace(",", "", $frmTotalDcto['txtSubTotal']),2);
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IMPUESTO
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) { 
			$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]) * str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valor]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
			
			$subTotalItm = $txtTotalItm;
			$totalDescuentoItm = ($hddTotalDescuentoItm > 0 || !($txtSubTotal > 0)) ? $hddTotalDescuentoItm : ($subTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
			$subTotalItm = $subTotalItm - $totalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$arrayPosIvaItm[$frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = $frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]];
						$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;",
							valTpDato($frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]], "int"));
						$rsIva = mysql_query($queryIva);
						if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$rowIva = mysql_fetch_assoc($rsIva);
						$arrayIvaItm[] = $rowIva['iva'];
					}
				}
			}
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);", 
				valTpDato(implode(",", $arrayIdIvaItm), "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$idIva = $rowIva['idIva'];
				$porcIva = $rowIva['iva'];
				$lujoIva = $rowIva['lujo'];
				$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if ($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
					$txtTotalExento += $subTotalItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
					$subTotalIvaItm = ($subTotalItm * $porcIva) / 100;
					
					$existIva = false;
					if (isset($arrayIva)) {
						foreach ($arrayIva as $indiceIva => $valorIva) {
							if ($arrayIva[$indiceIva][0] == $idIva) {
								$arrayIva[$indiceIva][1] += $subTotalItm;
								$arrayIva[$indiceIva][2] += $subTotalIvaItm;
								$existIva = true;
							}
						}
					}
					
					if ($idIva > 0 && $existIva == false
					&& ($txtTotalItm - $hddTotalDescuentoItm) > 0) {
						$arrayIva[] = array(
							$idIva,
							$subTotalItm,
							$subTotalIvaItm,
							$porcIva,
							$lujoIva,
							$rowIva['observacion']);
					}
				}
			}
			
			if ($totalRowsIva == 0) {
				$txtTotalExento += $subTotalItm;
			}
			
			$objResponse->assign("txtTotalItm".$valor, "value", number_format($txtTotalItm, 2, ".", ","));
			
			$subTotalDescuentoItm += $txtCantItm * str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
		}
	}
	
	// CREA LOS ELEMENTOS DE IVA
	if (isset($arrayIva)) {
		foreach ($arrayIva as $indiceIva => $valorIva) {
			if ($arrayIva[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIva:%s');
					if (obj == undefined)
						$('#trGastosSinIva').before(elemento);", 
					$indiceIva, 
						$indiceIva, utf8_encode($arrayIva[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][1], 2), 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIva[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][2], 2), 2, ".", ","), 
						
					$indiceIva));
			}
			
			$subTotalIva += round(doubleval($arrayIva[$indiceIva][2]), 2);
		}
	}
	
	if (count($arrayObj) > 0 && $frmDcto['txtIdCliente'] > 0) { 
		$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
		$totalFactura = $txtSubTotal - $txtSubTotalDescuento + $subTotalIva;
		
		$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($txtDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
		$objResponse->assign("txtTotalOrden","value",number_format($totalFactura, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
		
		$objResponse->assign("txtTotalFactura","value",number_format($totalFactura, 2, ".", ","));
		$objResponse->assign("txtMontoPorPagar","value",number_format($totalFactura, 2, ".", ","));
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;");
	} else { 
		if (isset($frmTotalDcto['cbxIva'])) {
			foreach ($frmTotalDcto['cbxIva'] as $indice => $valor) {
				// BUSCA EL IVA DE VENTA POR DEFECTO PARA CALCULAR EL EXENTO 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
				$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 AND iva.idIva = %s ORDER BY iva",
					valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"));
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRows = mysql_num_rows($rs);
				$row = mysql_fetch_assoc($rs);
				
				$txtBaseImpIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
				
				$txtIva = str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
				$txtSubTotalIva = $txtBaseImpIva * $txtIva / 100;
				
				$objResponse->assign("txtSubTotalIva".$valor,"value",number_format($txtSubTotalIva, 2, ".", ","));
				
				$totalSubtotalIva += $txtSubTotalIva;
				
				// BUSCA LA BASE IMPONIBLE MAYOR
				if ($totalRows > 0 && $txtBaseImpIva > 0) {
					$txtBaseImpIvaVenta = $txtBaseImpIva;
				}
			}
		}
		
		$txtTotalExento = round(str_replace(",", "", $frmTotalDcto['txtTotalExento']),2);
		$txtTotalExonerado = round(str_replace(",", "", $frmTotalDcto['txtTotalExonerado']),2);
	
		$totalDcto = $txtSubTotal - $txtSubTotalDescuento + $totalSubtotalIva + $txtGastosConIva + $txtGastosSinIva;
				
		if (!($frmDcto['hddIdPedido'] > 0)) {
			$txtTotalExento = $txtSubTotal - $txtSubTotalDescuento - $txtBaseImpIvaVenta + $txtGastosConIva;
		}
		
		$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($txtDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
		$objResponse->assign("txtTotalOrden","value",number_format($totalDcto, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
		
		$objResponse->assign("txtTotalFactura","value",number_format($totalDcto, 2, ".", ","));
		$objResponse->assign("txtMontoPorPagar","value",number_format($totalDcto, 2, ".", ","));
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpresa').readOnly = false;
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdCliente').readOnly = false;");
	}
	
	if ($frmDcto['lstModulo'] >= 0) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		
		byId('txtIdCliente').onblur = function() { }");
	} else {
		$objResponse->script("
		byId('txtIdCliente').onblur = function() { xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false'); }");
	}
	
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice2 => $valor2) {
			$txtIdConcepto = $frmListaPagos['txtIdConcepto'.$valor2];
			
			$objResponse->script(sprintf("byId('btnEliminarPago%s').style.display = '';",
				$valor2));
			
			if ($txtIdConcepto > 0) {
				if (isset($arrayObj4)) {
					foreach ($arrayObj4 as $indice4 => $valor4) {
						$hddNumeroItmBono = $frmListaArticulo['hddNumeroItmBono'.$valor4];
						if ($frmListaArticulo['hddIdConceptoBono'.$hddNumeroItmBono] == $txtIdConcepto) {
							$objResponse->script(sprintf("byId('btnEliminarPago%s').style.display = 'none';",
								$valor2));
						}
					}
				}
			}
		}
	}
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'))");
	
	return $objResponse;
}

function calcularPagos($frmListaPagos, $frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	if (isset($arrayObj2)) {
		$i = 0;
		foreach ($arrayObj2 as $indicePago => $valorPago) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmPago:".$valorPago,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmPago:".$valorPago,"innerHTML",$i);
			
			$txtMontoPagadoFactura += str_replace(",", "", $frmListaPagos['txtMonto'.$valorPago]);
		}
	}
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	$objResponse->assign("txtMontoPagadoFactura","value",number_format($txtMontoPagadoFactura, 2, ".", ","));
	$objResponse->assign("txtMontoPorPagar","value",number_format(str_replace(",", "", $frmTotalDcto['txtTotalOrden']) - $txtMontoPagadoFactura, 2, ".", ","));
	
	return $objResponse;
}

function calcularPagosDeposito($frmDeposito, $frmDetallePago) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
	$arrayObj3 = $frmDeposito['cbx3'];
	if (isset($arrayObj3)) {
		$i = 0;
		foreach ($arrayObj3 as $indice3 => $valor3) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDetalle:".$valor3,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDetalle:".$valor3,"innerHTML",$i);
			
			$txtMontoPagadoDeposito += str_replace(",", "", $frmDeposito['txtMontoDetalleDeposito'.$valor3]);
		}
	}
	$objResponse->assign("hddObjDetallePagoDeposito","value",((count($arrayObj3) > 0) ? implode("|",$arrayObj3) : ""));
	
	$objResponse->assign("txtTotalDeposito","value",number_format($txtMontoPagadoDeposito, 2, ".", ","));
	$objResponse->assign("txtSaldoDepositoBancario","value",number_format(str_replace(",", "", $frmDetallePago['txtMontoPago']) - $txtMontoPagadoDeposito, 2, ".", ","));
	
	return $objResponse;
}

function cargaLstAnticipoBono($idConcepto = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_asignarAnticipoBono(this.value);\"";
	
	$idConcepto = (is_array($idConcepto)) ? implode(",",$idConcepto) : $idConcepto;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT'");
	
	// 1 = Cash Back / Bono Dealer, 6 = Bono Suplidor
	if ($idConcepto != "-1" && $idConcepto != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_ant.idAnticipo IN (%s)",
			valTpDato($idConcepto, "campo"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_ant.idAnticipo IN (-1)");
	}
	
	$query = sprintf("SELECT cxc_ant.*,
		concepto_forma_pago.id_concepto,
		concepto_forma_pago.descripcion
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago) %s
	ORDER BY concepto_forma_pago.descripcion ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstAnticipoBono\" name=\"lstAnticipoBono\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idAnticipo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(asignarAnticipoBono($row["idAnticipo"])); }
		
		$html .= "<optgroup label=\""."Anticipo Nro. ".$row['numeroAnticipo']."\">";
			$html .= "<option ".$selected." value=\"".$row['idAnticipo']."\">".$row['descripcion']."</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAnticipoBono","innerHTML",$html);
	
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
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstCuentaCompania($row['idBanco'], $tipoPago)); }
		
		$html .= "<option ".$selected."  value=\"".$row['idBanco']."\">".utf8_encode($row['banco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselBancoCompania","innerHTML",$html);
	
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
	
	if ($selId != "-1" && $selId != "") {
		$cond = (strlen($sqlBusq) > 0) ? " OR " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_clave_movimiento = %s",
			valTpDato($selId, "int"));
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
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
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
			
			$selected = "";
			if ($selId == $rowClaveMov['id_clave_movimiento']) {
				$selected = "selected=\"selected\"";
				
				$objResponse->loadCommands(asignarClaveMovimiento($rowClaveMov['id_clave_movimiento'], ""));
			}
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
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
		if ($totalRows == 1) { $objResponse->loadCommands(cargaLstTarjetaCuenta($row['idCuentas'], $tipoPago)); }
		
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".utf8_encode($row['numeroCuentaCompania'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("divselNumeroCuenta","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstIva($nombreObjeto, $selId = "", $selVal = "") {
	$objResponse = new xajaxResponse();
	
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1 ORDER BY iva;");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	$selected = "";
	if ((in_array(0,explode(",",$selId)) || in_array(-1,explode(",",$selId))) && !(count(explode(",",$selId)) > 1) && $selId != "") {
		$selected = "selected=\"selected\"";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if (in_array($rowIva['iva'],explode(",",$selVal)) && in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['iva'],explode(",",$selVal))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if ($selId == "" && $rowIva['tipo'] == 6 && $rowIva['activo'] == 1) { // IMPUESTO PREDETERMINADO
				$selected = "selected=\"selected\"";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:150px\">";
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
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:150px\" onchange=\"byId('hddIdMoneda').value = this.value;\">";
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

function cargaLstSumarPagoItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$array = array(
		"" => array("abrev" => "-", "descripcion" => "-"),
		"1" => array("abrev" => "C", "descripcion" => "Pago de Contado"),
		"2" => array("abrev" => "T", "descripcion" => "Trade In"));
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:40px\">";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		$html .= "<optgroup label=\"".utf8_encode($valor['descripcion'])."\">";
			$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$indice."\">".($valor['abrev'])."</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
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
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row['idTipoTarjetaCredito'])); }
			
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

function cargaLstTipoPago($idFormaPago = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$idFormaPago = (is_array($idFormaPago)) ? implode(",",$idFormaPago) : $idFormaPago;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia Bancaria, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito
	// 9 = Retención, 10 = Retencion I.S.L.R., 11 = Otro
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idFormaPago NOT IN (11)");
	
	if ($idFormaPago != "-1" && $idFormaPago != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idFormaPago IN (%s)",
			valTpDato($idFormaPago, "campo"));
	}
	
	$query = sprintf("SELECT * FROM formapagos %s ORDER BY nombreFormaPago ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"selTipoPago\" name=\"selTipoPago\" class=\"inputHabilitado\" onchange=\"asignarTipoPago(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPago($row['idFormaPago'])); }
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPago']."\">".$row['nombreFormaPago']."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdselTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoPagoDetalleDeposito($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM formapagos where idFormaPago <= 2");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select name=\"lstTipoPago\" id=\"lstTipoPago\" class=\"inputHabilitado\" onchange=\"asignarTipoPagoDetalleDeposito(this.value)\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($selId == $row['idFormaPago'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		if ($totalRows == 1) { $objResponse->loadCommands(asignarTipoPagoDetalleDeposito($row['idFormaPago'])); }
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPago']."\">".$row['nombreFormaPago']."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstVendedor($idEmpresa = "", $selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s;",
		valTpDato($selId, "int"));
	$rs = mysql_query($query);
	if (!$rs) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	
	$html = "<select id=\"lstVendedor\" name=\"lstVendedor\" style=\"width:99%\" onchange=\"selectedOption(this.id, '".$selId."');\">";
	$html .= "<option value=\"0\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {                   
		$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
		
	$objResponse->assign("tdlstVendedor","innerHTML",$html);
		
	return $objResponse;
}

function cargarSaldoDocumento($formaPago, $idDocumento, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	if ($formaPago == 2) { // CHEQUES
		$documento = "Cheque";
		
		$query = sprintf("SELECT saldo_cheque AS saldoDocumento, numero_cheque AS numeroDocumento
		FROM cj_cc_cheque WHERE id_cheque = %s", $idDocumento);
	} else if ($formaPago == 4) { // TRANSFERENCIAS
		$documento = "Transferencia";
		
		$query = sprintf("SELECT saldo_transferencia AS saldoDocumento, numero_transferencia AS numeroDocumento
		FROM cj_cc_transferencia WHERE id_transferencia = %s", $idDocumento);
	} else if ($formaPago == 7) { // ANTICIPOS
		$documento = "Anticipo";
		
		$query = sprintf("SELECT
			saldoAnticipo AS saldoDocumento,
			numeroAnticipo AS numeroDocumento
		FROM cj_cc_anticipo
		WHERE idAnticipo = %s;",
			valTpDato($idDocumento, "int"));
	} else if ($formaPago == 8) { // NOTAS DE CREDITO
		$documento = "Nota de Crédito";
		
		$query = sprintf("SELECT
			saldoNotaCredito AS saldoDocumento,
			numeracion_nota_credito AS numeroDocumento
		FROM cj_cc_notacredito
		WHERE idNotaCredito = %s;",
			valTpDato($idDocumento, "int"));
	}
	$rsSelectDocumento = mysql_query($query);
	if (!$rsSelectDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowSelectDocumento = mysql_fetch_array($rsSelectDocumento);
	
	$objResponse->assign("hddIdDocumento","value",$idDocumento);
	$objResponse->assign("txtNroDocumento","value",$rowSelectDocumento['numeroDocumento']);
	$objResponse->assign("txtSaldoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'], 2, ".", ","));
	$objResponse->assign("txtMontoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'], 2, ".", ","));
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML",$documento);
	
	$objResponse->script("
	byId('txtMontoDocumento').focus();
	byId('txtMontoDocumento').select();");
		
	return $objResponse;
}

function eliminarDetalleDeposito($pos, $frmDetallePago) {
	$objResponse = new xajaxResponse();
	
	$arrayPosiciones = explode("|",$frmDetallePago['hddObjDetalleDeposito']);
	$arrayFormaPago = explode("|",$frmDetallePago['hddObjDetalleDepositoFormaPago']);
	$arrayBanco = explode("|",$frmDetallePago['hddObjDetalleDepositoBanco']);
	$arrayNroCuenta = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCuenta']);
	$arrayNroCheque = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCheque']);
	$arrayMonto = explode("|",$frmDetallePago['hddObjDetalleDepositoMonto']);
	
	$cadenaPosiciones = "";
	$cadenaFormaPago = "";
	$cadenaBanco = "";
	$cadenaNroCuenta = "";
	$cadenaNroCheque = "";
	$cadenaMonto = "";
	
	foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
		if ($valorDeposito != $pos && $valorDeposito != '') {
			$cadenaPosiciones .= $valorDeposito."|";
			$cadenaFormaPago .= $arrayFormaPago[$indiceDeposito]."|";
			$cadenaBanco .= $arrayBanco[$indiceDeposito]."|";
			$cadenaNroCuenta .= $arrayNroCuenta[$indiceDeposito]."|";
			$cadenaNroCheque .= $arrayNroCheque[$indiceDeposito]."|";
			$cadenaMonto .= $arrayMonto[$indiceDeposito]."|";
		}
	}
	
	$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosiciones);
	$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPago);
	$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBanco);
	$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuenta);
	$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroCheque);
	$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMonto);
	
	return $objResponse;
}

function eliminarPago($frmListaPagos, $pos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$idDocumento = $frmListaPagos['txtIdNumeroDctoPago'.$pos];
	
	if ($frmListaPagos['txtIdFormaPago'.$pos] == 3) { // 3 = Deposito
		$objResponse->script("xajax_eliminarDetalleDeposito(".$pos.",xajax.getFormValues('frmDetallePago'))");
	} else if ($frmListaPagos['txtIdFormaPago'.$pos] == 7) { // 7 = Anticipo
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_anticipo = %s;",
			valTpDato($idDocumento, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if (isset($arrayObj2)) {
				foreach ($arrayObj2 as $indicePago => $valorPago) {
					if ($frmListaPagos['txtIdFormaPago'.$valorPago] == 8 && $frmListaPagos['txtIdNumeroDctoPago'.$valorPago] == $rowTradeInNotaCredito['id_nota_credito_cxc']) {
						$objResponse->script("xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),'".$valorPago."');");
					}
				}
			}
		}
	} else if ($frmListaPagos['txtIdFormaPago'.$pos] == 8) { // 8 = Nota de Crédito
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_nota_credito_cxc = %s;",
			valTpDato($idDocumento, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if (isset($arrayObj2)) {
				foreach ($arrayObj2 as $indicePago => $valorPago) {
					if ($frmListaPagos['txtIdFormaPago'.$valorPago] == 7 && $frmListaPagos['txtIdNumeroDctoPago'.$valorPago] == $rowTradeInNotaCredito['id_anticipo']) {
						$objResponse->script("xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),'".$valorPago."');");
					}
				}
			}
		}
	}
	
	$objResponse->script("
	fila = document.getElementById('trItmPago:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'))");
	
	return $objResponse;
}

function eliminarPagoDetalleDeposito($frmDeposito, $pos) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	fila = document.getElementById('trItmDetalle:".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
			
	$montoEliminado = $frmDeposito['txtMontoDetalleDeposito'.$pos];
	
	$objResponse->script("xajax_calcularPagosDeposito(xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDetallePago'))");
	
	return $objResponse;
}

function formArticuloImpuesto() {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(cargaLstIva("lstIvaCbx"));
	
	return $objResponse;
}

function formDcto($idPedido, $acc){
	$objResponse = new xajaxResponse();
	
	global $raiz;
		
	if ($idPedido > 0) {
		$objResponse->script("		
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdCliente').readOnly = true;
		byId('txtObservacion').className = 'inputHabilitado';
		byId('txtObservacion').readOnly = false;
		
		byId('btnPedidoVentaPDF').style.display = 'none';
		
		byId('txtSubTotalDescuento').className = 'inputSinFondo';
		byId('txtSubTotalDescuento').readOnly = true;");
		
		// BUSCA LOS DATOS DE DEL PEDIDO
		$queryPedido = sprintf("SELECT *,
			(CASE cxc_pedido.estado_pedido
				WHEN 0 THEN 'Pendiente'
				WHEN 1 THEN 'Autorizado'
				WHEN 2 THEN 'Facturado'
				WHEN 3 THEN 'Desautorizado'
				WHEN 4 THEN 'Devuelta'
				WHEN 5 THEN 'Anulada'
			END) AS descripcion_estado_pedido
		FROM cj_cc_pedido cxc_pedido
		WHERE cxc_pedido.id_pedido = %s", valTpDato($idPedido, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPedido = mysql_fetch_array($rsPedido);
		
		if($rowPedido['estado_pedido'] != "1"){
			$objResponse->alert(utf8_encode("El pedido tiene un estado inválido"));
			return $objResponse->script("byId('btnCancelar').click();");
		}
		
		$idCliente = $rowPedido['id_cliente'];
		$condicionPago = $rowPedido['condicion_pago'];
		$idEmpresa = $rowPedido['id_empresa'];
		
		$Result1 = validarAperturaCaja($rowPedido['id_empresa'], date("Y-m-d"));
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
		
		$Result1 = buscarNumeroControl($rowPedido['id_empresa'], $rowPedido['id_clave_movimiento']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$objResponse->assign("txtNumeroControlFactura","value",($Result1[1]));
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($rowPedido['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarCliente($rowPedido['id_cliente'], $rowPedido['id_empresa'], "", $rowPedido['condicion_pago'], $rowPedido['id_clave_movimiento'], "false", "false", "false"));
		$objResponse->loadCommands(asignarEmpleado($rowPedido['id_empleado_creador']));
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $rowPedido['id_modulo'], "3", $rowPedido['condicion_pago'], "1", $rowPedido['id_clave_movimiento'], "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"")); 
		
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat, strtotime($rowPedido['fecha_registro'])));
		$objResponse->assign("hddIdPedido","value",$idPedido);
		$objResponse->assign("txtNumeroPedido","value",$rowPedido['numero_pedido']);
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat, strtotime($rowPedido['fecha_registro'])));
		$objResponse->assign("txtFechaFactura","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstModulo($rowPedido['id_modulo'], "selectedOption(this.id,'".$rowPedido['id_modulo']."');", true));
		$objResponse->loadCommands(cargaLstMoneda($rowPedido['id_moneda']));
		$objResponse->assign('hddIdMoneda','value', $rowPedido['id_moneda']);
		$objResponse->loadCommands(cargaLstVendedor($rowPedido['id_empresa'], $rowPedido['id_vendedor']));
		$objResponse->script(sprintf("byId('tdtxtEstatus').className = '%s';", $classEstatus));
		$objResponse->assign("txtEstatus","value",$rowPedido['descripcion_estado_pedido']);
		$objResponse->call("selectedOption","lstTipoMovimiento",3);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowPedido['observacion']));
		$objResponse->assign("tdTipoPago","innerHTML","<input type=\"hidden\" id=\"hddTipoPago\" name=\"hddTipoPago\" value=\"".$rowPedido['condicion_pago']."\"/><input type=\"text\" id=\"txtTipoPago\" name=\"txtTipoPago\" class=\"divMsjInfo2\" readonly=\"readonly\" size=\"20\" style=\"text-align:center\" value=\"".(($rowPedido['condicion_pago'] == 0) ? "CRÉDITO" : "CONTADO")."\"/>");		
		
		$objResponse->script("
		byId('lstMoneda').className = 'inputInicial';
		byId('lstMoneda').onchange = function() {
			selectedOption(this.id,'".($rowPedido['id_moneda'])."');
		}");
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption(this.id,'".(3)."');
		}");
		
		$objResponse->script("
		byId('lstClaveMovimiento').className = 'inputInicial';
		byId('lstClaveMovimiento').onchange = function() {
			selectedOption(this.id,'".($rowPedido['id_clave_movimiento'])."');
		}");
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = 3;
		$objDcto->tipoDocumento = "PD";
		$objDcto->idModulo = $rowPedido['id_modulo'];
		$objDcto->idDocumento = $idPedido;
		$objDcto->mostrarDocumento = "verVentanaPDF";
		$aVerDcto = $objDcto->verPedido();
		
		$objResponse->script("
		byId('btnPedidoVentaPDF').style.display = '';
		byId('btnPedidoVentaPDF').onclick = function() { ".$aVerDcto." }");
		
		// CARGA LOS IMPUESTOS 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
		$queryIva = sprintf("SELECT 
			cxc_pedido_imp.id_pedido_impuesto,
			cxc_pedido_imp.id_pedido,
			cxc_pedido_imp.base_imponible,
			cxc_pedido_imp.subtotal_impuesto,
			cxc_pedido_imp.id_impuesto,
			cxc_pedido_imp.impuesto,
			iva.observacion,
			cxc_pedido_imp.lujo
		FROM cj_cc_pedido_impuesto cxc_pedido_imp
			INNER JOIN pg_iva iva ON (cxc_pedido_imp.id_impuesto = iva.idIva)
		WHERE cxc_pedido_imp.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$indice = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$indice++;
			
			// INSERTA EL ITEM SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
						"<input id=\"cbxIva\" name=\"cbxIva[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
					"<td></td>".
					"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"</tr>';
				
				obj = byId('trIva:%s');
				if(obj == undefined)
					$('#trGastosSinIva').before(elemento);",
				$indice,
					$indice, utf8_encode($rowIva['observacion']),
						$indice, $indice, $rowIva['id_impuesto'],
						$indice, $indice, $rowIva['lujo'],
						$indice,
					$indice, $indice, number_format(round($rowIva['base_imponible'],2), 2, ".", ","),
					$indice, $indice, $rowIva['impuesto'], "%",
					$indice, $indice, number_format(round($rowIva['subtotal_impuesto'],2), 2, ".", ","),
				
				$indice));
		}
		
		// CARGA LOS CONCEPTOS DEL PEDIDO
		$queryDet = sprintf("SELECT 
			cxc_pedido_det.id_pedido_detalle,
			cxc_pedido_det.id_concepto,
			cxc_pedido_det.descripcion,
			cxc_pedido_det.cantidad,
			cxc_pedido_det.precio_unitario,
			cxc_pedido_det.costo_unitario,
			(SELECT GROUP_CONCAT(id_impuesto) FROM cj_cc_pedido_detalle_impuesto cxc_pedido_det_imp
				WHERE cxc_pedido_det_imp.id_pedido_detalle = cxc_pedido_det.id_pedido_detalle
			) ids_impuestos
		FROM cj_cc_pedido_detalle cxc_pedido_det
		WHERE cxc_pedido_det.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$rsDet = mysql_query($queryDet);
		if (!$rsDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$contFila = -1;
		while ($rowDet = mysql_fetch_assoc($rsDet)) {
			$contFila++;
			
			$Result1 = insertarItemArticulo($contFila, $rowDet['id_pedido_detalle'], $rowPedido['id_cliente'], $rowDet['id_concepto'], $rowDet['descripcion'], $rowDet['cantidad'], $rowDet['precio_unitario'], $rowDet['costo_unitario'], '', $rowDet['ids_impuestos']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[1]);
			}	
		}
		
		// CARGA LOS DATOS DEL CLIENTE
		$queryCliente = sprintf("SELECT
			cliente.id,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			cliente.direccion,
			cliente.telf,
			cliente.otrotelf,
			cliente.descuento,
			cliente.credito,
			cliente.id_clave_movimiento_predeterminado,
			cliente.paga_impuesto
		FROM cj_cc_cliente cliente
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCliente = mysql_fetch_assoc($rsCliente);
	
		if ($condicionPago == 0) { // 0 = Credito, 1 = Contado
			$objResponse->assign("hddTipoPago","value",$condicionPago);
			$objResponse->assign("txtTipoPago","value","CREDITO");
		
			if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
				$queryClienteCredito = sprintf("SELECT cliente_cred.*
				FROM cj_cc_credito cliente_cred
					INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente_cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
				WHERE cliente_emp.id_cliente = %s
					AND cliente_emp.id_empresa = %s;",
					valTpDato($idCliente, "int"),
					valTpDato($idEmpresa, "int"));
				$rsClienteCredito = mysql_query($queryClienteCredito);
				if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsClienteCredito = mysql_num_rows($rsClienteCredito);
				$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
			
				$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
			
				$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoCliente);
			
				$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
			} else {
				$fechaVencimiento = date(spanDateFormat);
			
				$objResponse->assign("txtDiasCreditoCliente","value","0");
			}
		
			$objResponse->script("
			byId('trFormaDePago').style.display = 'none';");
		
		} else if ($condicionPago == 1) { // 0 = Credito, 1 = Contado
			$objResponse->assign("hddTipoPago","value",$condicionPago);
			$objResponse->assign("txtTipoPago","value","CONTADO");
		
			$fechaVencimiento = date(spanDateFormat);
		
			$objResponse->assign("txtDiasCreditoCliente","value","0");
		
			$objResponse->script("
			byId('trFormaDePago').style.display = '';");
		}

		$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($fechaVencimiento)));
		
		$objResponse->assign("txtSubTotal","value",number_format($rowPedido['subtotal'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($rowPedido['porcentaje_descuento'], 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowPedido['subtotal_descuento'], 2, ".", ","));
		//$objResponse->assign("txtFlete","value",number_format($rowPedido['fletesFactura'], 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
		$objResponse->assign("txtTotalOrden","value",number_format($rowPedido['monto_total'], 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($rowPedido['monto_exento'], 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowPedido['monto_exonerado'], 2, ".", ","));
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));");
	}
	
	return $objResponse;
}

function formDeposito($frmDeposito) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
	$arrayObj3 = $frmDeposito['cbx3'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj3)) {
		foreach ($arrayObj3 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmDetalle:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
				
	$objResponse->loadCommands(cargaLstTipoPagoDetalleDeposito());
	$objResponse->loadCommands(cargaLstBancoCliente("lstBancoDeposito"));
	
	$objResponse->script("
	byId('txtSaldoDepositoBancario').value = byId('txtMontoPago').value;
	byId('txtTotalDeposito').value = '0.00';");
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $frmDetallePago, $frmListaPagos){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_factura_venta_list","insertar")) { return $objResponse; }
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaPagos['cbx2'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idPedido = $frmDcto['hddIdPedido'];
	$idModulo = 5; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion, 4 = Alquiler, 5 = Financiamiento
	$idEmpleadoAsesor = $frmDcto['lstVendedor'];
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	$idTipoPago = $frmDcto['hddTipoPago'];
	
	// COMPRUEBA EL ESTADO DEL PEDIDO
	$query = sprintf("SELECT * FROM cj_cc_pedido WHERE id_pedido = %s AND estado_pedido IN (1)",
		valTpDato($idPedido, "int"));// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if(mysql_num_rows($rs) == 0){
		return $objResponse->alert("El pedido no posee el status correcto para ser facturado");
	}
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// VERIFICA SI EL DOCUMENTO YA HA SIDO FACTURADO
	$queryVerif = sprintf("SELECT * FROM cj_cc_encabezadofactura
	WHERE numeroPedido = %s
		AND idDepartamentoOrigenFactura IN (%s)",
		valTpDato($idPedido, "int"),
		valTpDato($idModulo, "int"));
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if (mysql_num_rows($rsVerif) > 0) {
		return $objResponse->alert('Este documento ya ha sido facturado');
	}
	
	// VERIFICA QUE EL DOOCUMENTO A CONTADO ESTE CANCELADO EN TU TOTALIDAD
	if ($idTipoPago == 1) { // 0 = Credito, 1 = Contado
		if ($frmListaPagos['txtMontoPorPagar'] != 0) {
			return $objResponse->alert('Debe cancelar el monto total de la factura');
		}
	}
	
	mysql_query("START TRANSACTION;");

	// ACTUALIZA ESTADO DEL PEDIDO
	$updateSQL = sprintf("UPDATE cj_cc_pedido SET estado_pedido = 2 WHERE id_pedido = %s;",
		valTpDato($idPedido, "int"));// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }	
	
	// ACTUALIZA LA CUOTA SI PROVIENE DE UNA CUOTA
	$updateSQL = sprintf("UPDATE fi_amortizacion fi_amort SET
		fi_amort.estado_cuota = %s
	WHERE fi_amort.id_amortizacion IN (SELECT cxc_ped.id_amortizacion 
								FROM cj_cc_pedido cxc_ped
								WHERE cxc_ped.id_pedido = %s);",
		valTpDato(1, "int"), // 1 = PAGADA
		valTpDato($idPedido, "int"));
	$rsUpdateSQL = mysql_query($updateSQL);
	if (!$rsUpdateSQL) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ACTUALIZA EL FINANCIAMIENTO SI PROVIENE DE FINANCIAMIENTO Y SI TODAS LAS CUOTAS ESTAN PAGADAS
	$query = sprintf("SELECT id_pedido_financiamiento FROM cj_cc_pedido WHERE id_pedido = %s",
		valTpDato($idPedido, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedidoFinanciamiento = mysql_fetch_assoc($rs);
	
	if ($rowPedidoFinanciamiento['id_pedido_financiamiento'] > 0) {
		// SINO TIENE CUOTAS PENDIENTES NI ATRASADAS
		$updateSQL = sprintf("UPDATE fi_pedido fi_ped SET
			fi_ped.estatus_pedido = %s
		WHERE fi_ped.id_pedido_financiamiento = %s
			AND (SELECT COUNT(fi_amort.id_amortizacion) AS total_amort FROM fi_amortizacion fi_amort
					WHERE fi_amort.estado_cuota IN (%s)
					AND fi_amort.id_pedido_financiamiento = %s) = 0
			AND (SELECT COUNT(fi_mora.id_interes_mora) AS total_mora FROM fi_amortizacion_interesmora fi_mora
					WHERE fi_mora.estado_pago IN (%s)
					AND fi_mora.id_pedido_financiamiento = %s) = 0;", 
			valTpDato(2, "int"), // 2 = PAGADO
			valTpDato($rowPedidoFinanciamiento['id_pedido_financiamiento'], "int"),
			valTpDato("0,2", "campo"), // 0 Pendiente, 1 = Pagada, 2 = Atrasada 
			valTpDato($rowPedidoFinanciamiento['id_pedido_financiamiento'], "int"),
			valTpDato("0", "campo"), // 0 Pendiente, 1 = Pagada
			valTpDato($rowPedidoFinanciamiento['id_pedido_financiamiento'], "int")); 
		$rsUpdateSQL = mysql_query($updateSQL);
		if (!$rsUpdateSQL) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			switch ($frmTotalDcto['hddLujoIva'.$valor]) {
				case 0 :
					$txtBaseImponibleIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
					$txtIva += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
					$txtSubTotalIva += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
					break;
				case 1 :
					$txtBaseImponibleIvaLujo = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valor]);
					$txtIvaLujo += str_replace(",", "", $frmTotalDcto['txtIva'.$valor]);
					$txtSubTotalIvaLujo += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor]);
					break;
			}
		}
	}
	
	if (!isset($arrayObj)) { return $objResponse->alert("Debe agregar items al pedido"); }

	foreach($arrayObj as $indice => $valor) {
		$objResponse->script("byId('txtDescItm".$valor."').className = 'inputCompleto'");
		$objResponse->script("byId('txtCantItm".$valor."').className = 'inputCompleto'");
		$objResponse->script("byId('txtPrecioItm".$valor."').className = 'inputCompleto'");
		
		if (!(strlen($frmListaArticulo['txtDescItm'.$valor]) > 0)) { $arrayCantidadInvalida[] = "txtDescItm".$valor; }
		if (!($frmListaArticulo['txtCantItm'.$valor] > 0)) { $arrayCantidadInvalida[] = "txtCantItm".$valor; }
		if (!($frmListaArticulo['txtPrecioItm'.$valor] > 0)) { $arrayCantidadInvalida[] = "txtPrecioItm".$valor; }
	}
	
	// SI HAY CANTIDADES INVALIDAS O NO DISPONIBLES SE PINTARAN DICHOS OBJETOS, CONTENIDOS EN LAS MATRICES
	if (count($arrayCantidadInvalida) > 0) {
		if (count($arrayCantidadInvalida) > 0) {
			foreach ($arrayCantidadInvalida as $indice => $valor) {
				$objResponse->script("byId('".$valor."').className = 'inputCompletoErrado'");
			}
		}
		
		return $objResponse->alert(("Los campos señalados en rojo son invalidos"));
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
	
	if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado la numeracion de facturas"); }
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$numeroActualFactura = $numeroActual;
	
	// INSERTA LOS DATOS DE LA FACTURA
	$insertSQL = sprintf("INSERT INTO cj_cc_encabezadofactura (id_empresa, idCliente, numeroFactura, numeroControl, fechaRegistroFactura, fechaVencimientoFactura, idDepartamentoOrigenFactura, idVendedor, id_clave_movimiento, numeroPedido, condicionDePago, diasDeCredito, estadoFactura, montoTotalFactura, saldoFactura, observacionFactura, subtotalFactura, porcentaje_descuento, descuentoFactura, baseImponible, porcentajeIvaFactura, calculoIvaFactura, base_imponible_iva_lujo, porcentajeIvaDeLujoFactura, calculoIvaDeLujoFactura, montoExento, montoExonerado, estatus_factura, anulada, aplicaLibros, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idCliente, "int"),
		valTpDato($numeroActualFactura, "text"),
		valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaVencimientoFactura'])), "date"),
		valTpDato($idModulo, "int"),
		valTpDato($idEmpleadoAsesor, "int"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idPedido, "int"),
		valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($frmDcto['txtDiasCreditoCliente'], "int"),
		valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada , 2 = Parcialmente Cancelada
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($txtBaseImponibleIva, "real_inglesa"),
		valTpDato($txtIva, "real_inglesa"),
		valTpDato($txtSubTotalIva, "real_inglesa"),
		valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
		valTpDato($txtIvaLujo, "real_inglesa"),
		valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
		valTpDato(((in_array(idArrayPais,array(3))) ? 1 : 2), "int"), // Null o 1 = Aprobada, 2 = Aplicada / Cerrada
		valTpDato("NO", "text"),
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idFactura = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idFactura,
		$idModulo,
		"VENTA");
	
	// INSERTA EL DETALLE DEL DOCUMENTO
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$idConcepto = $frmListaArticulo['hddIdArticuloItm'.$valor];
			$descripcionConcepto = $frmListaArticulo['txtDescItm'.$valor];
			$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$cantDespachada = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$cantPendiente = doubleval($cantPedida) - doubleval($cantDespachada);
			$precioUnitario = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valor]);
			$costoUnitario = str_replace(",", "", $frmListaArticulo['hddCostoItm'.$valor]);
			$hddIdIvaItm = "";
			$hddIvaItm = 0;
			if (isset($arrayObjIvaItm)) {// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]];
						$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;",
							valTpDato($hddIdIvaItm, "int"));
						$rsIva = mysql_query($queryIva);
						if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowIva = mysql_fetch_assoc($rsIva);
						$hddIvaItm = $rowIva['iva'];
					}
				}
			}
			$totalArticulo = $cantDespachada * $precioUnitario;
			
			$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_adm (id_factura, id_concepto, descripcion, cantidad, devuelto, precio_unitario, costo_unitario, id_iva, iva, estatus)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato($idConcepto, "int"),
				valTpDato($frmListaArticulo['txtDescItm'.$valor], "text"),
				valTpDato($cantPedida, "real_inglesa"),
				valTpDato($cantPendiente, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato($hddIdIvaItm, "int"),
				valTpDato($hddIvaItm, "real_inglesa"),
				valTpDato(1, "text")); // 0 = Pendiente, 1 = Entregado, 2 = Devuelto
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idFacturaDetalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['lstIvaItm'.$valor.':'.$valor1[1]];
						$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;",
							valTpDato($hddIdIvaItm, "int"));
						$rsIva = mysql_query($queryIva);
						if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowIva = mysql_fetch_assoc($rsIva);
						$hddIvaItm = $rowIva['iva'];
						
						if ($hddIdIvaItm > 0) {
							$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_adm_impuesto (id_factura_detalle_adm, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idFacturaDetalle, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) {
								if (mysql_errno() == 1062) {
									return $objResponse->alert("Existe algún item con el impuesto repetido");
								} else {
									return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								}
							}
							mysql_query("SET NAMES 'latin1';");
						}
					}
				}
			}
		}
	}
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			if (str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valor]) > 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
					valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['hddLujoIva'.$valor], "boolean"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("FA", "text"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	if (isset($arrayObj)) {
		$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	// INSERTA EL PAGO DEL DOCUMENTO (PAGO DE FACTURAS) SOLO SI ES DE CONTADO
	if ($idTipoPago == 1 || count($arrayObj2) > 0) { // 0 = Credito, 1 = Contado
		// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$queryAperturaCaja = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE idCaja = %s
			AND statusAperturaCaja IN (1,2)
			AND (ape.id_empresa = %s
				OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = %s));",
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsAperturaCaja = mysql_query($queryAperturaCaja);
		if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$idApertura = $rowAperturaCaja['id'];
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
		$arrayObjPago = array();
		foreach($arrayObj2 as $indice2 => $valor2) {
			$txtIdFormaPago = $frmListaPagos['txtIdFormaPago'.$valor2];
			$hddIdPago = $frmListaPagos['hddIdPago'.$valor2];
			
			if (!($hddIdPago > 0)) {
				if (isset($txtIdFormaPago)) {
					$arrayDetallePago = array(
						"idCajaPpal" => $idCajaPpal,
						"apertCajaPpal" => $apertCajaPpal,
						"idApertura" => $idApertura,
						"numeroActualFactura" => $numeroActualFactura,
						"fechaRegistroPago" => $fechaRegistroPago,
							//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
							//"idEncabezadoPago" => $idEncabezadoPago,
						"cbxPosicionPago" => $valor2,
						"hddIdPago" => $hddIdPago,
						"txtIdFormaPago" => $txtIdFormaPago,
						"txtIdNumeroDctoPago" => $frmListaPagos['txtIdNumeroDctoPago'.$valor2],
						"txtNumeroDctoPago" => $frmListaPagos['txtNumeroDctoPago'.$valor2],
						"txtIdBancoCliente" => $frmListaPagos['txtIdBancoCliente'.$valor2],
						"txtCuentaClientePago" => $frmListaPagos['txtCuentaClientePago'.$valor2],
						"txtIdBancoCompania" => $frmListaPagos['txtIdBancoCompania'.$valor2],
						"txtIdCuentaCompaniaPago" => $frmListaPagos['txtIdCuentaCompaniaPago'.$valor2],
						"txtCuentaCompaniaPago" => asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valor2]),
						"txtFechaDeposito" => $frmListaPagos['txtFechaDeposito'.$valor2],
						"txtTipoTarjeta" => $frmListaPagos['txtTipoTarjeta'.$valor2],
						"hddObjDetalleDeposito" => $frmDetallePago['hddObjDetalleDeposito'],
						"hddObjDetalleDepositoFormaPago" => $frmDetallePago['hddObjDetalleDepositoFormaPago'],
						"hddObjDetalleDepositoBanco" => $frmDetallePago['hddObjDetalleDepositoBanco'],
						"hddObjDetalleDepositoNroCuenta" => $frmDetallePago['hddObjDetalleDepositoNroCuenta'],
						"hddObjDetalleDepositoNroCheque" => $frmDetallePago['hddObjDetalleDepositoNroCheque'],
						"hddObjDetalleDepositoMonto" => $frmDetallePago['hddObjDetalleDepositoMonto'],
						"txtMonto" => $frmListaPagos['txtMonto'.$valor2],
						"cbxCondicionMostrar" => $frmListaPagos['cbxCondicionMostrar'.$valor2],
						"lstSumarA" => $frmListaPagos['lstSumarA'.$valor2]
					);
					
					$arrayObjPago[] = $arrayDetallePago;
				}
			}
		}
		
		$objDcto = new Documento;
		$objDcto->idModulo = $idModulo;
		$objDcto->idDocumento = $idFactura;
		$objDcto->idEmpresa = $idEmpresa;
		$objDcto->idCliente = $idCliente;
		$Result1 = $objDcto->guardarReciboPagoCxCFA(
			$idCajaPpal,
			$apertCajaPpal,
			$idApertura,
			$fechaRegistroPago,
			$arrayObjPago);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		$idEncabezadoReciboPago = $Result1['idEncabezadoReciboPago'];
	}
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
		creditodisponible = limitecredito - (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idCliente = cliente_emp.id_cliente
														AND cxc_fact.id_empresa = cliente_emp.id_empresa
														AND cxc_fact.estadoFactura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
													WHERE cxc_nd.idCliente = cliente_emp.id_cliente
														AND cxc_nd.id_empresa = cliente_emp.id_empresa
														AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
													WHERE cxc_ant.idCliente = cliente_emp.id_cliente
														AND cxc_ant.id_empresa = cliente_emp.id_empresa
														AND cxc_ant.estadoAnticipo IN (1,2)
														AND cxc_ant.estatus = 1), 0)
											- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idCliente = cliente_emp.id_cliente
														AND cxc_nc.id_empresa = cliente_emp.id_empresa
														AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
											+ IFNULL((SELECT
														SUM(IFNULL(an_ped_vent.subtotal, 0)
															- IFNULL(an_ped_vent.subtotal_descuento, 0)
															+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																	WHERE ped_vent_gasto.id_pedido_venta = an_ped_vent.id_pedido_venta), 0)
															+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																	WHERE ped_vent_iva.id_pedido_venta = an_ped_vent.id_pedido_venta), 0))
													FROM iv_pedido_venta an_ped_vent
													WHERE an_ped_vent.id_cliente = cliente_emp.id_cliente
														AND an_ped_vent.id_empresa = cliente_emp.id_empresa
														AND an_ped_vent.estatus_pedido_venta IN (2)), 0)),
		creditoreservado = (IFNULL((SELECT SUM(cxc_fact.saldoFactura) FROM cj_cc_encabezadofactura cxc_fact
									WHERE cxc_fact.idCliente = cliente_emp.id_cliente
										AND cxc_fact.id_empresa = cliente_emp.id_empresa
										AND cxc_fact.estadoFactura IN (0,2)), 0)
							+ IFNULL((SELECT SUM(cxc_nd.saldoNotaCargo) FROM cj_cc_notadecargo cxc_nd
									WHERE cxc_nd.idCliente = cliente_emp.id_cliente
										AND cxc_nd.id_empresa = cliente_emp.id_empresa
										AND cxc_nd.estadoNotaCargo IN (0,2)), 0)
							- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
									WHERE cxc_ant.idCliente = cliente_emp.id_cliente
										AND cxc_ant.id_empresa = cliente_emp.id_empresa
										AND cxc_ant.estadoAnticipo IN (1,2)
										AND cxc_ant.estatus = 1), 0)
							- IFNULL((SELECT SUM(cxc_nc.saldoNotaCredito) FROM cj_cc_notacredito cxc_nc
									WHERE cxc_nc.idCliente = cliente_emp.id_cliente
										AND cxc_nc.id_empresa = cliente_emp.id_empresa
										AND cxc_nc.estadoNotaCredito IN (1,2)), 0)
							+ IFNULL((SELECT
										SUM(IFNULL(an_ped_vent.subtotal, 0)
											- IFNULL(an_ped_vent.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
													WHERE ped_vent_gasto.id_pedido_venta = an_ped_vent.id_pedido_venta), 0)
											+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
													WHERE ped_vent_iva.id_pedido_venta = an_ped_vent.id_pedido_venta), 0))
									FROM iv_pedido_venta an_ped_vent
									WHERE an_ped_vent.id_cliente = cliente_emp.id_cliente
										AND an_ped_vent.id_empresa = cliente_emp.id_empresa
										AND an_ped_vent.estatus_pedido_venta IN (2)
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
	
	mysql_query("COMMIT;");		
	
	//CONTABILIZA DOCUMENTO
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "VENTA") {
				$idFactura = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarVentasRe")) { generarVentasRe($idFactura,"",""); } break;
					case 1 : if (function_exists("generarVentasSe")) { generarVentasSe($idFactura,"",""); } break;
					case 2 : if (function_exists("generarVentasVe")) { generarVentasVe($idFactura,"",""); } break;
					case 3 : if (function_exists("generarVentasAd")) { generarVentasAd($idFactura,"",""); } break;
					case 4 : if (function_exists("generarVentasAl")) { generarVentasAl($idFactura,"",""); } break;
					case 5 : if (function_exists("generarVentasFi")) { generarVentasFi($idFactura,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert("Factura Guardada con Exito");
	$objResponse->script(sprintf("window.location.href='cj_factura_venta_list.php';"));	
	$objResponse->script("verVentana('../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=".$idFactura."', 960, 550);");
	
	if ($idEncabezadoReciboPago > 0) {
		$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s',960,550)", $idEncabezadoReciboPago));
	}
	
	return $objResponse;
}

function insertarArticulo($idConcepto, $frmDcto, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	
	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Venta)
	$queryConfig5 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 5 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig5 = mysql_query($queryConfig5);
	if (!$rsConfig5) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowConfig5 = mysql_fetch_assoc($rsConfig5);
	
	if ($hddNumeroArt == "") {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = $frmListaArticulo['cbx'];
		$contFila = $arrayObj[count($arrayObj)-1];
		
		if (count($arrayObj) < $rowConfig5['valor']) {
			$Result1 = insertarItemArticulo($contFila, "", $idCliente, $idConcepto, $descripcionConcepto, $cantPedida, $precioUnitario, $costoUnitario, $abrevMonedaCostoUnitario, $idIva);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert(utf8_encode("Solo puede agregar un máximo de ".$rowConfig5['valor']." items por Pedido"));
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));");
	
	return $objResponse;
}

function insertarPago($frmListaPagos, $frmDetallePago, $frmDeposito, $frmLista, $frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObj2 = $frmListaPagos['cbx2'];
	$contFila = $arrayObj2[count($arrayObj2)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
	$arrayObj3 = $frmDeposito['cbx3'];
	
	if (str_replace(",", "", $frmTotalDcto['txtTotalOrden']) < str_replace(",", "", $frmDetallePago['txtMontoPago'])) {
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
	}
	
    foreach ($arrayObj2 as $indicePago => $valorPago) {
		$hddIdPago = $frmListaPagos['hddIdPago'.$valorPago];
		$txtIdFormaPago = $frmListaPagos['txtIdFormaPago'.$valorPago];
		$txtIdNumeroDctoPago = $frmListaPagos['txtIdNumeroDctoPago'.$valorPago];
		
        if (!($hddIdPago > 0)
		&& $txtIdFormaPago == $frmDetallePago['selTipoPago']
		&& $txtIdNumeroDctoPago > 0 && $txtIdNumeroDctoPago == $frmDetallePago['hddIdAnticipoNotaCreditoChequeTransferencia']) {
			return $objResponse->alert("El documento seleccionado ya se encuentra agregado");
        }
    }
	
	$idFormaPago = $frmDetallePago['selTipoPago'];
	$txtIdNumeroDctoPago = $frmDetallePago['hddIdAnticipoNotaCreditoChequeTransferencia'];
	$txtNumeroDctoPago = $frmDetallePago['txtNumeroDctoPago'];
	$txtIdBancoCliente = $frmDetallePago['selBancoCliente'];
	$txtCuentaClientePago = $frmDetallePago['txtNumeroCuenta'];
	$txtIdBancoCompania = $frmDetallePago['selBancoCompania'];
	$txtIdCuentaCompaniaPago = $frmDetallePago['selNumeroCuenta'];
	$txtFechaDeposito = $frmDetallePago['txtFechaDeposito'];
	$lstTipoTarjeta = $frmDetallePago['tarjeta'];
	$porcRetencion = $frmDetallePago['porcentajeRetencion'];
	$montoRetencion = $frmDetallePago['montoTotalRetencion'];
	$porcComision = $frmDetallePago['porcentajeComision'];
	$montoComision = $frmDetallePago['montoTotalComision'];
	$txtMontoPago = str_replace(",", "", $frmDetallePago['txtMontoPago']);
	
	$Result1 = insertarItemMetodoPago($contFila, $idFormaPago, $txtIdNumeroDctoPago, $txtNumeroDctoPago, $txtIdBancoCliente, $txtCuentaClientePago, $txtIdBancoCompania, $txtIdCuentaCompaniaPago, $txtFechaDeposito, $lstTipoTarjeta, $porcRetencion, $montoRetencion, $porcComision, $montoComision, $txtMontoPago);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj2[] = $contFila;
	}
	
	if ($idFormaPago == 3) { // 3 = Deposito
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = explode("|", $frmDeposito['hddObjDetallePagoDeposito']);
		
		$cadenaFormaPagoDeposito = "";
		$cadenaNroDocumentoDeposito = "";
		$cadenaBancoClienteDeposito = "";
		$cadenaNroCuentaDeposito = "";
		$cadenaMontoDeposito = "";
		foreach ($arrayObj as $indice => $valor) {
			if (isset($frmDeposito['txtIdFormaPagoDetalleDeposito'.$valor])) {
				$cadenaPosicionDeposito .= $contFila."|";
				$cadenaFormaPagoDeposito .= $frmDeposito['txtIdFormaPagoDetalleDeposito'.$valor]."|";		
				$cadenaNroDocumentoDeposito .= $frmDeposito['txtNumeroDocumentoDetalleDeposito'.$valor]."|";
				$cadenaBancoClienteDeposito .= $frmDeposito['txtIdBancoClienteDetalleDeposito'.$valor]."|";
				$cadenaNroCuentaDeposito .= $frmDeposito['txtNumeroCuentaDetalleDeposito'.$valor]."|";
				$cadenaMontoDeposito .= $frmDeposito['txtMontoDetalleDeposito'.$valor]."|";
			}
		}
		$cadenaPosicionDeposito = $frmDetallePago['hddObjDetalleDeposito'].$cadenaPosicionDeposito;
		$cadenaFormaPagoDeposito = $frmDetallePago['hddObjDetalleDepositoFormaPago'].$cadenaFormaPagoDeposito;
		$cadenaBancoClienteDeposito = $frmDetallePago['hddObjDetalleDepositoBanco'].$cadenaBancoClienteDeposito;
		$cadenaNroCuentaDeposito = $frmDetallePago['hddObjDetalleDepositoNroCuenta'].$cadenaNroCuentaDeposito;
		$cadenaNroDocumentoDeposito = $frmDetallePago['hddObjDetalleDepositoNroCheque'].$cadenaNroDocumentoDeposito;
		$cadenaMontoDeposito = $frmDetallePago['hddObjDetalleDepositoMonto'].$cadenaMontoDeposito;
		
		$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosicionDeposito);
		$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPagoDeposito);
		$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBancoClienteDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuentaDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroDocumentoDeposito);
		$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMontoDeposito);
	} else if ($idFormaPago == 7) { // 7 = Anticipo
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT cxc_nc.*
		FROM an_tradein_cxc tradein_cxc
			INNER JOIN cj_cc_notacredito cxc_nc ON (tradein_cxc.id_nota_credito_cxc = cxc_nc.idNotaCredito)
		WHERE tradein_cxc.id_anticipo = %s;",
			valTpDato($txtIdNumeroDctoPago, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		while ($rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito)) {
			if ($rowTradeInNotaCredito['saldoNotaCredito'] > 0) {
				$Result1 = insertarItemMetodoPago($contFila, 8, $rowTradeInNotaCredito['idNotaCredito'], $rowTradeInNotaCredito['numeracion_nota_credito'], "", "", "", "", "", "", "", "", "", "", $rowTradeInNotaCredito['saldoNotaCredito']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj2[] = $contFila;
				}
			}
		}
	} else if ($idFormaPago == 8) { // 8 = Nota de Crédito
		// BUSCA SI EL ANTICIPO DEL TRADE IN TIENE UNA NOTA DE CREDITO ASOCIADA
		$queryTradeInNotaCredito = sprintf("SELECT * FROM an_tradein_cxc tradein_cxc WHERE tradein_cxc.id_nota_credito_cxc = %s;",
			valTpDato($txtIdNumeroDctoPago, "int"));
		$rsTradeInNotaCredito = mysql_query($queryTradeInNotaCredito);
		if (!$rsTradeInNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTradeInNotaCredito = mysql_num_rows($rsTradeInNotaCredito);
		$rowTradeInNotaCredito = mysql_fetch_array($rsTradeInNotaCredito);
		
		if ($totalRowsTradeInNotaCredito > 0) {
			$idFormaPago = 7; // // 7 = Anticipo
		}
	}
	
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'))");
	
	switch ($idFormaPago) {
		case 2 : // 2 = CHEQUE
			if ($txtIdNumeroDctoPago > 0) {
				$objResponse->loadCommands(cargaLstTipoPago("","2"));
				$objResponse->call(asignarTipoPago,"2");
				$objResponse->script("
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
				byId('imgCerrarDivFlotante2').click();");
			} else {
				$objResponse->loadCommands(cargaLstTipoPago("","1"));
				$objResponse->call(asignarTipoPago,"1");
			}
			break;
		case 3 : // 3 = DEPOSITO
			$objResponse->loadCommands(cargaLstTipoPago("","1"));
			$objResponse->call(asignarTipoPago,"1");
			$objResponse->script("
			byId('imgCerrarDivFlotante1').click();"); break;
		case 4 : // 4 = TRANSFERENCIA
			if ($txtIdNumeroDctoPago > 0) {
				$objResponse->loadCommands(cargaLstTipoPago("","4"));
				$objResponse->call(asignarTipoPago,"4");
				$objResponse->script("
				byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
				byId('imgCerrarDivFlotante2').click();");
			} else {
				$objResponse->loadCommands(cargaLstTipoPago("","1"));
				$objResponse->call(asignarTipoPago,"1");
			}
			break;
		case 7 : // 7 = ANTICIPO
			$objResponse->loadCommands(cargaLstTipoPago("","7"));
			$objResponse->call(asignarTipoPago,"7");
			/*$objResponse->loadCommands(listaAnticipoNotaCreditoChequeTransferencia(
				$frmLista['pageNum'],
				$frmLista['campOrd'],
				$frmLista['tpOrd'],
				$frmLista['valBusq']));*/
			$objResponse->script("
			byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
			byId('imgCerrarDivFlotante2').click();"); break;
		case 8 : // 8 = NOTA CREDITO
			$objResponse->loadCommands(cargaLstTipoPago("","8"));
			$objResponse->call(asignarTipoPago,"8");
			$objResponse->script("
			byId('btnAgregarDetAnticipoNotaCreditoChequeTransferencia').click();
			byId('imgCerrarDivFlotante2').click();"); break;
		default:
			$objResponse->loadCommands(cargaLstTipoPago("","1"));
			$objResponse->call(asignarTipoPago,"1");
	}
	
	return $objResponse;
}

function insertarPagoDeposito($frmDeposito) {
	$objResponse = new xajaxResponse();
		
	if (str_replace(",", "", $frmDeposito['txtMontoDeposito']) > str_replace(",", "", $frmDeposito['txtSaldoDepositoBancario'])) {
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo del Deposito.");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
	$arrayObj3 = $frmDeposito['cbx3'];
	$contFila = $arrayObj3[count($arrayObj3)-1] + 1;
	
	if ($frmDeposito['lstTipoPago'] == 1) {
		$tipoPago = "Efectivo";
		$bancoCliente = "-";
		$numeroCuenta = "-";
		$numeroControl = "-";
		$montoPagado = str_replace(",", "", $frmDeposito['txtMontoDeposito']);
		$bancoClienteOculto = "-";
	} else if ($frmDeposito['lstTipoPago'] == 2) {
		$tipoPago = "Cheque";
		$bancoCliente = asignarBanco($frmDeposito['lstBancoDeposito']);
		$numeroCuenta = $frmDeposito['txtNroCuentaDeposito'];
		$numeroControl = $frmDeposito['txtNroChequeDeposito'];
		$montoPagado = str_replace(",", "", $frmDeposito['txtMontoDeposito']);
		$bancoClienteOculto = $frmDeposito['lstBancoDeposito'];
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$objResponse->script(sprintf("$('#trItmPieDeposito').before('".
		"<tr align=\"left\" id=\"trItmDetalle:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmDetalle:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx3\" name=\"cbx3[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMontoDetalleDeposito%s\" name=\"txtMontoDetalleDeposito%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><button type=\"button\" onclick=\"confirmarEliminarPagoDetalleDeposito(%s);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"/></button>".
				"<input type=\"hidden\" id=\"txtIdFormaPagoDetalleDeposito%s\" name=\"txtIdFormaPagoDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtNumeroDocumentoDetalleDeposito%s\" name=\"txtNumeroDocumentoDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoClienteDetalleDeposito%s\" name=\"txtIdBancoClienteDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtNumeroCuentaDetalleDeposito%s\" name=\"txtNumeroCuentaDetalleDeposito%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$tipoPago,
			$bancoCliente,
			$numeroCuenta,
			$numeroControl,
			$contFila, $contFila, number_format($montoPagado, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $frmDeposito['lstTipoPago'],
				$contFila, $contFila, $numeroControl,
				$contFila, $contFila, $bancoClienteOculto,
				$contFila, $contFila, $numeroCuenta,
				$contFila, $contFila, $montoPagado));
	
	$objResponse->script("
	xajax_cargaLstTipoPagoDetalleDeposito('1');
	asignarTipoPagoDetalleDeposito('1');");
	
	$objResponse->script("xajax_calcularPagosDeposito(xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmDetallePago'))");
	
	return $objResponse;
}

function listaAnticipoNotaCreditoChequeTransferencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($valCadBusq[2] == 2) { // CHEQUES
		$campoIdCliente = "id_cliente";
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$campoIdCliente = "id_cliente";
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$campoIdCliente = "idCliente";
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$campoIdCliente = "idCliente";
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(dcto.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = dcto.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = dcto.id_empresa)
	OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = dcto.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[2] == 2) { // CHEQUES
		// 1 = Normal, 2 = Bono Suplidor, 3 = PND
		$sqlBusq .= $cond.sprintf("(id_departamento IN (%s)
		AND ((%s = %s AND dcto.tipo_cheque = 1) OR dcto.tipo_cheque IN (2,3)))",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		// 1 = Normal, 2 = Bono Suplidor, 3 = PND
		$sqlBusq .= $cond.sprintf("(id_departamento IN (%s)
		AND ((%s = %s AND dcto.tipo_transferencia = 1) OR dcto.tipo_transferencia IN (2,3)))",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$sqlBusq .= $cond.sprintf("(idDepartamento IN (%s)
		AND %s = %s)",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$sqlBusq .= $cond.sprintf("(idDepartamentoNotaCredito IN (%s)
		AND %s = %s)",
			valTpDato($idModuloPpal, "campo"),
			$campoIdCliente,
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if ($valCadBusq[2] == 2) { // CHEQUES
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado
		$sqlBusq .= $cond.sprintf("estatus IN (1,2) AND saldo_cheque > 0 AND estatus = 1"); // 1 = tipo cliente
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$sqlBusq .= $cond.sprintf("estatus IN (1,2) AND saldo_transferencia > 0 AND estatus = 1");//1 = tipo cliente
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
		$sqlBusq .= $cond.sprintf("estadoAnticipo IN (0,1,2) AND estatus = 1");
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado
		$sqlBusq .= $cond.sprintf("estadoNotaCredito IN (1,2)");
	}
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 2) { // CHEQUES
			$sqlBusq .= $cond.sprintf("(dcto.numero_cheque LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
			$sqlBusq .= $cond.sprintf("(dcto.numero_transferencia LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 7) { // ANTICIPOS
			$sqlBusq .= $cond.sprintf("(numeroAnticipo LIKE %s
			OR cxc_ant.observacionesAnticipo LIKE %s)",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s)",
				valTpDato($valCadBusq[0], "int"));
		}
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[2] == 2) { // CHEQUES
			$sqlBusq .= $cond.sprintf("dcto.id_cheque NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 4) { // TRANSFERENCIA
			$sqlBusq .= $cond.sprintf("dcto.id_transferencia NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 7) { // ANTICIPOS
			$sqlBusq .= $cond.sprintf("idAnticipo NOT IN (%s)",
				valTpDato($valCadBusq[3], "campo"));
		} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
			$sqlBusq .= $cond.sprintf("idNotaCredito NOT IN (%s) ",
				valTpDato($valCadBusq[3], "campo"));
		}
	}
	
	if ($valCadBusq[2] == 2) { // CHEQUES
		$query = sprintf("SELECT
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.id_departamento AS id_modulo,
			dcto.id_cheque AS idDocumento,
			dcto.saldo_cheque AS saldoDocumento,
			dcto.numero_cheque AS numeroDocumento,
			dcto.fecha_cheque AS fechaDocumento,
			dcto.observacion_cheque AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_cheque dcto 
			INNER JOIN cj_cc_cliente cliente ON (dcto.id_cliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 4) { // TRANSFERENCIAS
		$query = sprintf("SELECT
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.id_departamento AS id_modulo,
			dcto.id_transferencia AS idDocumento,
			dcto.saldo_transferencia AS saldoDocumento,
			dcto.numero_transferencia AS numeroDocumento,
			dcto.fecha_transferencia AS fechaDocumento,
			dcto.observacion_transferencia AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_transferencia dcto 
			INNER JOIN cj_cc_cliente cliente ON (dcto.id_cliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 7) { // ANTICIPOS
		$query = sprintf("SELECT
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.idDepartamento AS id_modulo,
			dcto.idAnticipo AS idDocumento,
			dcto.saldoAnticipo AS saldoDocumento,
			dcto.numeroAnticipo AS numeroDocumento,
			dcto.fechaAnticipo AS fechaDocumento,
			dcto.observacionesAnticipo AS observacionDocumento,
		
			(SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
			FROM cj_cc_detalleanticipo cxc_pago
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.idAnticipo = dcto.idAnticipo
				AND cxc_pago.id_forma_pago IN (11)) AS descripcion_concepto_forma_pago,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_anticipo dcto
			INNER JOIN cj_cc_cliente cliente ON (dcto.idCliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	} else if ($valCadBusq[2] == 8) { // NOTAS DE CREDITO
		$query = sprintf("SELECT
			cliente.id AS id_cliente,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			dcto.idDepartamentoNotaCredito AS id_modulo,
			dcto.idNotaCredito AS idDocumento,
			dcto.saldoNotaCredito AS saldoDocumento,
			dcto.numeracion_nota_credito AS numeroDocumento,
			dcto.fechaNotaCredito AS fechaDocumento,
			dcto.observacionesNotaCredito AS observacionDocumento,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM cj_cc_notacredito dcto
			INNER JOIN cj_cc_cliente cliente ON (dcto.idCliente = cliente.id)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	}
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "10%", $pageNum, "fechaDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "14%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Documento"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "42%", $pageNum, "observacionDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listaAnticipoNotaCreditoChequeTransferencia", "20%", $pageNum, "saldoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo"));
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
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"".utf8_encode("Financiamiento")."\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$onClick = sprintf("abrirDivFlotante2(this, 'tblAnticipoNotaCreditoChequeTransferencia', '%s', '%s');",
			$valCadBusq[2],
			$row['idDocumento']);
		
		if ($valCadBusq[2] == 7) { // 7 = Anticipo
			$idAnticipo = $row['idDocumento'];
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT *,
				(CASE
					WHEN (cxc_pago.id_concepto = 2) THEN
						IF (cxc_ant.saldoAnticipo > (SELECT tradein.total_credito FROM an_tradein tradein
													WHERE tradein.id_anticipo = cxc_ant.idAnticipo
														AND tradein.anulado IS NULL) AND cxc_ant.saldoAnticipo > 0,
							(SELECT tradein.total_credito FROM an_tradein tradein
							WHERE tradein.id_anticipo = cxc_ant.idAnticipo
								AND tradein.anulado IS NULL),
							cxc_ant.saldoAnticipo)
					ELSE
						cxc_ant.saldoAnticipo
				END) AS saldo_anticipo
			FROM cj_cc_anticipo cxc_ant
				LEFT JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				LEFT JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND (cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT'
					OR cxc_ant.estadoAnticipo IN (0));",
				valTpDato($idAnticipo, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while ($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
				if ((in_array($rowAnticipo['id_concepto'],array(2))
					&& in_array(idArrayPais,array(3))
					&& ($rowAnticipo['saldo_anticipo'] > 0 || ($rowAnticipo['saldo_anticipo'] == 0 && $rowAnticipo['estadoAnticipo'] == 1)))
				|| ((in_array($rowAnticipo['id_concepto'],array(1,6,7,8,9)) || in_array($rowAnticipo['estadoAnticipo'],array(0))) && $rowAnticipo['saldo_anticipo'] > 0)) {
					$onClick = sprintf("
					byId('hddIdAnticipoNotaCreditoChequeTransferencia').value = '%s';
					byId('txtNumeroDctoPago').value = '%s';
					byId('txtMontoPago').value = '%s';
					
					xajax_insertarPago(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDetallePago'), xajax.getFormValues('frmDeposito'), xajax.getFormValues('frmLista'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'));",
						$idAnticipo,
						$rowAnticipo['numeroAnticipo'],
						$rowAnticipo['saldo_anticipo']);
				}
			}
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDcto%s\" rel=\"#divFlotante2\" onclick=\"%s\"><button type=\"button\" title=\"Seleccionar\"><img class=\"puntero\" src=\"../img/iconos/tick.png\"/></button></a>",
					$contFila,
					$onClick);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaDocumento']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroDocumento']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_concepto_forma_pago']) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($row['descripcion_concepto_forma_pago'])."</span></td></tr>" : "";
				$htmlTb .= (strlen($row['observacionDocumento']) > 0) ? "<tr><td class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionDocumento'])."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoDocumento'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAnticipoNotaCreditoChequeTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\">";
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

$xajax->register(XAJAX_FUNCTION,"asignarAnticipoBono");
$xajax->register(XAJAX_FUNCTION,"asignarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"calcularPagosDeposito");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnticipoBono");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnticipoBono");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstIva");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPagoDetalleDeposito");

$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");

$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"formArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"formDcto");
$xajax->register(XAJAX_FUNCTION,"formDeposito");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarPago");
$xajax->register(XAJAX_FUNCTION,"insertarPagoDeposito");
$xajax->register(XAJAX_FUNCTION,"listaAnticipoNotaCreditoChequeTransferencia");

function actualizarNumeroControl($idEmpresa, $idClaveMovimiento){
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	return array(true, "");
}

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

function buscarNumeroControl($idEmpresa, $idClaveMovimiento){
	// VERIFICA VALORES DE CONFIGURACION (Formato Nro. Control)
	$queryConfig401 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 401 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig401 = mysql_query($queryConfig401);
	if (!$rsConfig401) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig401 = mysql_num_rows($rsConfig401);
	$rowConfig401 = mysql_fetch_assoc($rsConfig401);
	
	if (!($totalRowsConfig401 > 0)) return array(false, "No existe un formato de numero de control establecido");
		
	$valor = explode("|",$rowConfig401['valor']);
	$separador = $valor[0];
	$formato = (strlen($separador) > 0) ? explode($separador,$valor[1]) : $valor[1];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	if (strlen($separador) > 0 && isset($formato)) {
		foreach($formato as $indice => $valor) {
			$numeroActualFormato[] = ($indice == count($formato)-1) ? str_pad($numeroActual,strlen($valor),"0",STR_PAD_LEFT) : str_pad(0,strlen($valor),"0",STR_PAD_LEFT);
		}
		$numeroActualFormato = implode($separador, $numeroActualFormato);
	} else {
		$numeroActualFormato = str_pad($numeroActual,strlen($formato),"0",STR_PAD_LEFT);
	}

	return array(true, $numeroActualFormato);
}

function cargaLstIvaItm($nombreObjeto, $selId = "", $selVal = "") {
	//BLOQUEAR AL VISUALIZAR
	$class = "inputHabilitado";
	$onchange = "onchange=\"xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));\"";
	if ($_GET['id'] > 0 && $_GET['acc'] == 0) {
		$class = "";
		$onchange = "onchange=\"selectedOption('".$nombreObjeto."', ".$selId.");\"";
	}
	
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1 ORDER BY iva");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"".$class."\" ".$onchange." style=\"width:99%\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	$selected = "";
	if ((in_array(0,explode(",",$selId)) || in_array(-1,explode(",",$selId))) && !(count(explode(",",$selId)) > 1) && $selId != "") {
		$selected = "selected=\"selected\"";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if (in_array($rowIva['iva'],explode(",",$selVal)) && in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['iva'],explode(",",$selVal))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if ($selId == "" && $rowIva['tipo'] == 6 && $rowIva['activo'] == 1) { // IMPUESTO PREDETERMINADO
				$selected = "selected=\"selected\"";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
			
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
}

function informacionCheque($idCheque){
	$query = sprintf("SELECT 
		cj_cc_cheque.id_banco_cliente,
		cj_cc_cheque.cuenta_cliente AS numero_cuenta_cliente,
		bancos.nombreBanco AS nombre_banco_cliente
	FROM cj_cc_cheque 
		INNER JOIN bancos ON cj_cc_cheque.id_banco_cliente = bancos.idBanco
	WHERE cj_cc_cheque.id_cheque = %s LIMIT 1",
		valTpDato($idCheque, "int"));
	$rsQuery = mysql_query($query) or die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowQuery = mysql_fetch_assoc($rsQuery);
	if(mysql_num_rows($rsQuery) == 0) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$query); }
	
	return $rowQuery;
}

function informacionTransferencia($idTransferencia){
	$query = sprintf("SELECT
		cj_cc_transferencia.cuenta_compania AS numero_cuenta_compania,
		cj_cc_transferencia.id_banco_compania,
		cj_cc_transferencia.id_banco_cliente,
		cj_cc_transferencia.id_cuenta_compania,						   
		bancos.nombreBanco AS nombre_banco_cliente,
		bancos2.nombreBanco AS nombre_banco_compania
	FROM cj_cc_transferencia 
		INNER JOIN bancos ON cj_cc_transferencia.id_banco_cliente = bancos.idBanco
		INNER JOIN bancos bancos2 ON cj_cc_transferencia.id_banco_compania = bancos2.idBanco
	WHERE cj_cc_transferencia.id_transferencia = %s LIMIT 1",
		$idTransferencia);
	$rsQuery = mysql_query($query) or die(mysql_error()." Linea: ".__LINE__." Query: ".$query);
	$rowQuery = mysql_fetch_assoc($rsQuery);
	if(mysql_num_rows($rsQuery) == 0){ die(mysql_error()." Linea: ".__LINE__." Query: ".$query); }
	
	return $rowQuery;
}

function insertarItemArticulo($contFila, $hddIdPedidoVentaDetalle = "", $idCliente = "", $idConcepto = "", $descripcionConcepto = "", $cantPedida = "", $precioUnitario = "", $costoUnitario = "", $abrevMonedaCostoUnitario = "", $idsIvas = "") {
	$contFila++;
	
	if ($hddIdPedidoVentaDetalle > 0) {
		
	}
	
	$idConcepto = ($idConcepto == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_concepto'] : $idConcepto;
	$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
	$precioUnitario = ($precioUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $precioUnitario;
	$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['costo_unitario'] : $costoUnitario;
	//$idIva = ($idIva == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_iva'] : $idIva;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryConcepto = sprintf("SELECT * FROM cj_cc_concepto WHERE id_concepto = %s;",
		valTpDato($idConcepto, "int"));
	$rsConcepto = mysql_query($queryConcepto);
	if (!$rsConcepto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsConcepto = mysql_num_rows($rsConcepto);
	$rowConcepto = mysql_fetch_assoc($rsConcepto);
	
	$descripcionConcepto = ($hddIdPedidoVentaDetalle > 0) ? $descripcionConcepto : $rowConcepto['descripcion'];
	
	if ($hddIdPedidoVentaDetalle > 0) {
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
		WHERE iva.idIva IN (%s);", 
			valTpDato($idsIvas, "campo"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);		
	} else {
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
		WHERE iva.estado = 1
			AND iva.idIva IN (SELECT concep_imp.id_impuesto FROM cj_cc_concepto_impuesto concep_imp 
								WHERE concep_imp.id_concepto = %s)
			AND iva.idIva NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
								WHERE cliente_imp_exento.id_cliente = %s);", 
			valTpDato($idConcepto, "int"), 
			valTpDato($idCliente, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	}
	
	$contIva = 0;
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= cargaLstIvaItm("lstIvaItm".$contFila.":".$contIva, $rowIva['idIva']);
		$ivaUnidad .= sprintf("<input type=\"hidden\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
		"<input type=\"checkbox\" id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	//BLOQUEAR AL VISUALIZAR
	if ($_GET['id'] > 0 && $_GET['acc'] == 0) {
		$display = "style=\"display:none;\"";
		$readonly = "readonly=\"readonly\"";
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItmArticulo:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmPago:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmArticulo:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDescItm%s\" name=\"txtDescItm%s\" class=\"inputSinFondo\" maxlength=\"255\" style=\"text-align:left\" %s value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" %s value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" %s value=\"%s\"></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td title=\"trItmArticulo:%s\"><a id=\"aEliminar:%s\" %s><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" readonly=\"readonly\" value=\"%s\"></td>".
		"</tr>');
		
		//byId('txtCantItm%s').onblur = function() {
		//	setFormatoRafk(this,2);
		//	xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		//}
		//byId('txtPrecioItm%s').onblur = function() {
		//	setFormatoRafk(this,2);
		//	xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		//}
		//byId('aEliminar:%s').onclick = function() {
		//	xajax_eliminarArticulo('%s', xajax.getFormValues('frmListaArticulo'));
		//}",
		$contFila, $clase,
			$contFila, $contFila,
				 $contFila,
			$contFila, $contFila,
			utf8_encode($rowConcepto['codigo_concepto']),
			$contFila, $contFila, $readonly, utf8_encode($descripcionConcepto),
			$contFila, $contFila, $readonly, number_format($cantPedida, 2, ".", ","),
			$contFila, $contFila, $readonly, number_format($precioUnitario, 2, ".", ","),
			$contFila, str_replace("'","\'",$ivaUnidad),
			$contFila, $contFila, number_format($cantPedida * $precioUnitario, 2, ".", ","),
			$contFila, $contFila, $display,
				$contFila, $contFila, $idConcepto,
		
		$contFila,
		$contFila,
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemMetodoPago($contFila, $idFormaPago, $txtIdNumeroDctoPago = "", $txtNumeroDctoPago = "", $txtIdBancoCliente = "", $txtCuentaClientePago = "", $txtIdBancoCompania = "", $txtIdCuentaCompaniaPago = "", $txtFechaDeposito = "", $lstTipoTarjeta = "", $porcRetencion = "", $montoRetencion = "", $porcComision = "", $montoComision = "", $txtMontoPago = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// 1 = Efectivo, 2 = Cheque, 3 = Deposito, 4 = Transferencia Bancaria, 5 = Tarjeta de Crédito, 6 = Tarjeta de Debito, 7 = Anticipo, 8 = Nota de Crédito, 9 = Retención, 10 = Retencion I.S.L.R., 11 = Otro
	if (in_array($idFormaPago,array(3,5,6)) || (in_array($idFormaPago,array(4)) && !($txtIdNumeroDctoPago > 0))) {
		$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",
			valTpDato($txtIdCuentaCompaniaPago, "int"));
		$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta);
		if (!$rsBuscarNumeroCuenta) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	}
	
	$queryFormaPago = sprintf("SELECT * FROM formapagos WHERE idFormaPago = %s;", valTpDato($idFormaPago, "int"));
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsFormaPago = mysql_num_rows($rsFormaPago);
	$rowFormaPago = mysql_fetch_array($rsFormaPago);
	
	$nombreFormaPago = $rowFormaPago['nombreFormaPago'];
	
	$txtBancoClientePago = "-";
	$txtBancoCompaniaPago = "-";
	$txtCuentaCompaniaPago = "-";
	switch ($idFormaPago) {
		case 1 : // 1 = Efectivo
			break;
		case 2 : // 2 = Cheque
			if ($txtIdNumeroDctoPago > 0) {
				$arrayInformacionCheque = informacionCheque($txtIdNumeroDctoPago);
				$txtIdBancoCliente = $arrayInformacionCheque['id_banco_cliente'];
				$txtBancoClientePago = $arrayInformacionCheque['nombre_banco_cliente'];
				$txtCuentaClientePago = $arrayInformacionCheque['numero_cuenta_cliente'];
			} else {
				$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			}
			break;
		case 3 : // 3 = Deposito
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			break;
		case 4 : // 4 = Transferencia Bancaria
			if ($txtIdNumeroDctoPago > 0) {
				$arrayInformacionTransferencia = informacionTransferencia($txtIdNumeroDctoPago);
				$txtIdBancoCliente = $arrayInformacionTransferencia['id_banco_cliente'];
				$txtBancoClientePago = $arrayInformacionTransferencia['nombre_banco_cliente'];
				
				$txtIdBancoCompania = $arrayInformacionTransferencia['id_banco_compania'];
				$txtBancoCompaniaPago = $arrayInformacionTransferencia['nombre_banco_compania'];
				$txtIdCuentaCompaniaPago = $arrayInformacionTransferencia['id_cuenta_compania'];
				$txtCuentaCompaniaPago = $arrayInformacionTransferencia['numero_cuenta_compania'];
			} else {
				$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
				$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
				$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			}
			break;
		case 5 : // 5 = Tarjeta de Crédito
			$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			break;
		case 6 : // 6 = Tarjeta de Debito
			$txtBancoClientePago = asignarBanco($txtIdBancoCliente);
			$txtBancoCompaniaPago = asignarBanco($txtIdBancoCompania);
			$txtCuentaCompaniaPago = (strlen($rowBuscarNumeroCuenta['numeroCuentaCompania']) > 0) ? $rowBuscarNumeroCuenta['numeroCuentaCompania'] : $txtIdCuentaCompaniaPago;
			
			$lstTipoTarjeta = 6;
			break;
		case 7 : // 7 = Anticipo
			// BUSCA EL TIPO DEL ANTICIPO
			$queryAnticipo = sprintf("SELECT cxc_ant.*,
				concepto_forma_pago.id_concepto,
				concepto_forma_pago.descripcion
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND cxc_pago.tipoPagoDetalleAnticipo LIKE 'OT';",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsAnticipo = mysql_query($queryAnticipo);
			if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
			while($rowAnticipo = mysql_fetch_array($rsAnticipo)) {
				$hddIdConcepto = $rowAnticipo['id_concepto'];
				$arrayConceptoAnticipo[] = $rowAnticipo['descripcion'];
				$observacionDcto = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowAnticipo['observacionesAnticipo']))));
			}
			
			$nombreFormaPago .= (($totalRowsAnticipo > 0) ? "<br><span class=\"textoNegrita_10px\">(".implode(", ", $arrayConceptoAnticipo).")</span>" : "");
			break;
		case 8 : // 8 = Nota de Crédito
			// BUSCA EL TIPO DEL ANTICIPO
			$queryNotaCredito = sprintf("SELECT cxc_nc.*,
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) AS descripcion_motivo
			FROM cj_cc_notacredito cxc_nc
				LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
				INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			WHERE cxc_nc.idNotaCredito = %s;",
				valTpDato($txtIdNumeroDctoPago, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRowsNotaCredito = mysql_num_rows($rsNotaCredito);
			$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
			
			$descripcionMotivo = $rowNotaCredito['descripcion_motivo'];
			$observacionDcto = preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowNotaCredito['observacionesNotaCredito']))));
			break;
		case 9 : // 9 = Retención
			break;
		case 10 : // 10 = Retencion I.S.L.R.
			break;
		case 11 : // 11 = Otro
			break;
	}
	
	$checkedCondicionMostrar = "checked=\"checked\"";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPiePago').before('".
		"<tr align=\"left\" id=\"trItmPago:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmPago:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"divMsjInfo2\">%s</td>".
			"<td class=\"divMsjInfo\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><table width=\"%s\">".
				"<tr><td>%s</td><td><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr>".
				"%s".
				"%s".
				"</table></td>".
			"<td><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMonto%s\" name=\"txtMonto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><button type=\"button\" id=\"btnEliminarPago%s\" onclick=\"confirmarEliminarPago(%s);\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"/></button>".
				"<input type=\"hidden\" id=\"hddIdPago%s\" name=\"hddIdPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtFechaDeposito%s\" name=\"txtFechaDeposito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdFormaPago%s\" name=\"txtIdFormaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdConcepto%s\" name=\"txtIdConcepto%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoCompania%s\" name=\"txtIdBancoCompania%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdCuentaCompaniaPago%s\" name=\"txtIdCuentaCompaniaPago%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtIdBancoCliente%s\" name=\"txtIdBancoCliente%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"txtTipoTarjeta%s\" name=\"txtTipoTarjeta%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			(in_array(idArrayPais,array(3))) ? "<input type=\"checkbox\" id=\"cbxCondicionMostrar\" name=\"cbxCondicionMostrar".$contFila."\" ".$checkedCondicionMostrar." value=\"1\">" : "",
			(in_array(idArrayPais,array(3))) ? cargaLstSumarPagoItm("lstSumarA".$contFila, $checkedMostrarContado) : "",
			utf8_encode($nombreFormaPago),
			"100%",
				$aVerDcto, $contFila, $contFila, utf8_encode($txtNumeroDctoPago),
					$contFila, $contFila, utf8_encode($txtIdNumeroDctoPago),
				((strlen($descripcionMotivo) > 0) ? "<tr><td><span class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($descripcionMotivo)."</span></td></tr>" : ""),
				((strlen($observacionDcto) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".($observacionDcto)."</span></td></tr>" : ""),
			$contFila, $contFila, utf8_encode($txtBancoClientePago),
				$contFila, $contFila, $txtCuentaClientePago,
			$contFila, $contFila, utf8_encode($txtBancoCompaniaPago),
				$contFila, $contFila, $txtCuentaCompaniaPago,
			$contFila, $contFila, number_format($txtMontoPago, 2, ".", ","),
			$contFila, $contFila,
				$contFila, $contFila, $hddIdPago,
				$contFila, $contFila, $txtFechaDeposito,
				$contFila, $contFila, $idFormaPago,
				$contFila, $contFila, $hddIdConcepto,
				$contFila, $contFila, $txtIdBancoCompania,
				$contFila, $contFila, $txtIdCuentaCompaniaPago,
				$contFila, $contFila, $txtIdBancoCliente,
				$contFila, $contFila, $lstTipoTarjeta);
	
	return array(true, $htmlItmPie, $contFila);
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