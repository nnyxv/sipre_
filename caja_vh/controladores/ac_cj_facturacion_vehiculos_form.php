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

function asignarArticuloImpuesto($frmArticuloImpuesto, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$hddIdIvaItm = implode(",",$frmArticuloImpuesto['lstIvaCbx']);
	
	if (isset($frmArticuloImpuesto['lstIvaCbxCambio'])) {
		$hddIdIvaItm = $frmArticuloImpuesto['lstIvaCbxCambio'];
	}
	
	if (in_array(idArrayPais,array(1)) && in_array($hddIdIvaItm, array(18,20))) {
		$objResponse->assign("txtObservacion","value","Se aplica rebaja de la alicuota impositiva general del IVA segun Gaceta Oficial Nro. 41.239 de fecha 19-09-2017, Decreto Nro. 3.085");
	}
	
	if (isset($frmListaArticulo['cbxItmAdicional'])) {
		foreach ($frmListaArticulo['cbxItmAdicional'] as $indiceItm => $valorItm) {
			$contFila = $valorItm;
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (6,9,2)
				AND iva.idIva IN (%s);",
				valTpDato($hddIdIvaItm, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$contIva = 0;
			$ivaUnidad = "";
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
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
			
			$objResponse->assign("divIvaItm".$contFila,"innerHTML",$ivaUnidad);
		}
	}
	
	$hddIdIvaGasto = implode(",",$frmArticuloImpuesto['lstIvaCbx']);
	
	if (isset($frmArticuloImpuesto['lstIvaCbxCambio'])) {
		$hddIdIvaGasto = $frmArticuloImpuesto['lstIvaCbxCambio'];
	}
	
	if (isset($frmTotalDcto['cbxItmGasto'])) {
		foreach ($frmTotalDcto['cbxItmGasto'] as $indiceItm => $valorItm) {
			$contFila = $valorItm;
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (6,9,2)
				AND iva.idIva IN (%s);",
				valTpDato($hddIdIvaGasto, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$contIva = 0;
			$ivaUnidad = "";
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td><img id=\"imgIvaGasto%s:%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td><td width=\"%s\">".
				"<input type=\"text\" id=\"hddIvaGasto%s:%s\" name=\"hddIvaGasto%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaGasto%s:%s\" name=\"hddIdIvaGasto%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIvaGasto%s:%s\" name=\"hddLujoIvaGasto%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s:%s\" name=\"hddEstatusIvaGasto%s:%s\" value=\"%s\">".
				"<input type=\"checkbox\" id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
					"100%", $contFila, $contIva, "100%",
					$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
					$contFila.":".$contIva);
			}
			
			$objResponse->assign("divIvaGasto".$contFila,"innerHTML",$ivaUnidad);
		}
	}
	
	$objResponse->script("
	
	byId('btnCancelarArticuloImpuesto').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function asignarCliente($nombreObjeto, $idCliente, $idEmpresa = "", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false"){
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id = %s
	AND status = 'Activo'",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
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
		$objResponse->assign("hddTipoPagoCliente","value",0);
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "0", "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));*/
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','0','1','".$idClaveMovimiento."','onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"');
		}");
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		$objResponse->assign("hddTipoPagoCliente","value",1);
		
		/*$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", $idClaveMovimiento, "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\""));*/
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function () {
			selectedOption(this.id,3);
			xajax_cargaLstClaveMovimiento('lstClaveMovimiento','0','3','1','1','".$idClaveMovimiento."','onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"');
		}");
	}
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowCliente['id']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefono".$nombreObjeto,"value",$rowCliente['telf']);
	$objResponse->assign("txtRif".$nombreObjeto,"value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNIT".$nombreObjeto,"value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0 && $rowCliente['id'] > 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
	
	//$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante2').click();");
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
	$objResponse->assign("txt".$nombreObjeto,"value",htmlentities($row['descripcion']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	}
	
	return $objResponse;
}

function buscarAnticipoNotaCreditoChequeTransferencia($frmBuscarAnticipoNotaCreditoChequeTransferencia, $frmDcto, $frmDetallePago, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	
	if (isset($arrayObjPiePago)) {
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			if ($frmListaPagos['txtIdFormaPago'.$valorPiePago] == $frmDetallePago['selTipoPago']
			&& $frmListaPagos['hddEstatusPago'.$valorPiePago] == 1) {
				$arrayIdDocumento[] = $frmListaPagos['txtIdNumeroDctoPago'.$valorPiePago];
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

function buscarCliente($frmBuscarCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente'],
		$frmBuscarCliente['hddObjDestinoCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
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

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $frmListaPagos){
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; isset($frmTotalDcto['txtIva'.$cont]); $cont++) {
		$objResponse->script("
		fila = document.getElementById('trIva:".$cont."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DE LA FACTURA)
	if (isset($frmListaArticulo['cbxPieDetalle']) && isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = array_merge($frmListaArticulo['cbxPieDetalle'], $frmTotalDcto['cbxPieDetalle']);
	} else if (isset($frmListaArticulo['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	} else if (isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmTotalDcto['cbxPieDetalle'];
	}
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDetalle_".$valorPieDetalle,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDetalle_".$valorPieDetalle,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObjPieDetalle) > 0) ? implode("|",$arrayObjPieDetalle) : ""));
			
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (IMPUESTO DEL DETALLE)
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (BONO DEL DETALLE)
	$arrayObj4 = $frmListaArticulo['cbxPieBono'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdFactura'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas moneda
	WHERE moneda.estatus = 1
		AND moneda.predeterminada = 1;");
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			if (isset($frmListaArticulo['cbxPieDetalle']) && in_array($valorPieDetalle, $frmListaArticulo['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
				$frmListaArticuloAux = $frmListaArticulo;
			} else if (isset($frmTotalDcto['cbxPieDetalle']) && in_array($valorPieDetalle, $frmTotalDcto['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
				$frmListaArticuloAux = $frmTotalDcto;
			}
			
			$txtCantRecibItm = str_replace(",", "", $frmListaArticuloAux['txtCantItm'.$valorPieDetalle]);
			$txtPrecioItm = str_replace(",", "", $frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle]);
			$hddCostoItm = str_replace(",", "", $frmListaArticuloAux['hddCostoItm'.$valorPieDetalle]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticuloAux['hddMontoDescuentoItm'.$valorPieDetalle]);
			$txtTotalItm = $txtCantRecibItm * $txtPrecioItm;
			
			if ((in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle], array(1)))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(3))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(4))) {
				$txtSubTotal += $txtTotalItm;
				$subTotalDescuentoItm += $txtCantRecibItm * $hddMontoDescuentoItm;
			} else if (in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle], array(2,3,4))) {
				$txtTotalAdicionalOtro += $txtTotalItm;
			}
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IVA Y EL SUBTOTAL
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			if (isset($frmListaArticulo['cbxPieDetalle']) && in_array($valorPieDetalle, $frmListaArticulo['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
				$frmListaArticuloAux = $frmListaArticulo;
			} else if (isset($frmTotalDcto['cbxPieDetalle']) && in_array($valorPieDetalle, $frmTotalDcto['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
				$frmListaArticuloAux = $frmTotalDcto;
			}
			
			$txtCantRecibItm = str_replace(",", "", $frmListaArticuloAux['txtCantItm'.$valorPieDetalle]);
			$txtPrecioItm = str_replace(",", "", $frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle]);
			$hddCostoItm = str_replace(",", "", $frmListaArticuloAux['hddCostoItm'.$valorPieDetalle]);
			$txtTotalItm = $txtCantRecibItm * $txtPrecioItm;
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticuloAux['hddTotalDescuentoItm'.$valorPieDetalle]);
			
			$hddTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
					$valorIvaItm = explode(":", $valorIvaItm);
					
					if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
						$arrayPosIvaItm[$frmListaArticuloAux['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]]] = $valorIvaItm[1];
						$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
					}
				}
			}
			
			if ((in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(1,2)) && in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle], array(1)))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(3))
			|| in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(4))) {
				// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);", 
					valTpDato(implode(",", $arrayIdIvaItm), "campo"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$queryIva);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticuloAux['hddEstatusIvaItm'.$valorPieDetalle.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if ($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
						$txtTotalExento += $txtTotalNetoItm;
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ?  str_replace(",", "", $frmListaArticuloAux['hddIvaItm'.$valorPieDetalle.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
						$subTotalIvaItm = ($txtTotalNetoItm * $porcIva) / 100;
						
						$existIva = false;
						if (isset($arrayIva)) {
							foreach ($arrayIva as $indiceIva => $valorIva) {
								if ($arrayIva[$indiceIva][0] == $idIva) {
									$arrayIva[$indiceIva][1] += $txtTotalNetoItm;
									$arrayIva[$indiceIva][2] += $subTotalIvaItm;
									$existIva = true;
								}
							}
						}
						
						if ($idIva > 0 && $existIva == false
						&& ($txtTotalItm - $hddTotalDescuentoItm) > 0) {
							$arrayIva[] = array(
								$idIva,
								$txtTotalNetoItm,
								$subTotalIvaItm,
								$porcIva,
								$lujoIva,
								$rowIva['observacion']);
						}
					}
				}
				
				if ($totalRowsIva == 0) {
					$txtTotalExento += $txtTotalNetoItm;
				}
			}
			
			$objResponse->assign("txtTotalItm".$valorPieDetalle, "value", number_format($txtTotalItm, 2, ".", ","));
			$objResponse->assign("txtTotalConImpuestoItm".$valorPieDetalle, "value", number_format(($txtTotalItm + $subTotalIvaItm), 2, ".", ","));
			
			$objResponse->script("
			byId('divPrecioPagadoItm".$valorPieDetalle."').style.display = 'none';
			byId('lstMostrarItm".$valorPieDetalle."').style.display = 'none';
			byId('divMostrarPendienteItm".$valorPieDetalle."').style.display = 'none';");
			
			if (in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle], array(1))) { // 1 = Adicional
				if ($frmListaArticuloAux['lstMostrarItm'.$valorPieDetalle] == 2) { // 2 = Incluir en el Costo
					/*$objResponse->script("
					byId('txtPrecioConIvaItm".$valorPieDetalle."').className = 'inputInicial';
					byId('txtPrecioConIvaItm".$valorPieDetalle."').readOnly = true;");
					$objResponse->assign("txtPrecioConIvaItm".$valorPieDetalle,"value",$frmListaArticuloAux['hddCostoUnitarioItm'.$valorPieDetalle]);
					$objResponse->assign("txtPrecioPagadoItm".$valorPieDetalle,"value",$frmListaArticuloAux['hddCostoUnitarioItm'.$valorPieDetalle]);*/
				} else {
					/*$objResponse->script("
					byId('txtPrecioConIvaItm".$valorPieDetalle."').className = 'inputCompletoHabilitado';
					byId('txtPrecioConIvaItm".$valorPieDetalle."').readOnly = false;");*/
					if ($frmListaArticuloAux['cbxCondicionItm'.$valorPieDetalle] == 1) { // 1 = Pagado, 2 = Financiado
						if (str_replace(",","",$frmListaArticuloAux['txtPrecioPagadoItm'.$valorPieDetalle]) > 0
						&& str_replace(",","",$frmListaArticuloAux['txtPrecioConIvaItm'.$valorPieDetalle]) != str_replace(",","",$frmListaArticuloAux['txtPrecioPagadoItm'.$valorPieDetalle])
						&& in_array(idArrayPais,array(3))) {
							$objResponse->script("
							byId('divMostrarPendienteItm".$valorPieDetalle."').style.display = '';");
						}
					} else {
						$objResponse->assign("txtPrecioPagadoItm".$valorPieDetalle,"value",number_format(0, 2, ".", ","));
					}
				}
				
				$objResponse->script("
				byId('lstMostrarItm".$valorPieDetalle."').style.display = '';
				if (byId('cbxCondicionItm".$valorPieDetalle."').checked == true) {
					byId('divPrecioPagadoItm".$valorPieDetalle."').style.display = '';
					byId('lstMostrarItm".$valorPieDetalle."').style.display = 'none';
					selectedOption('lstMostrarItm".$valorPieDetalle."','');
				} else {
					selectedOption('lstMostrarPendienteItm".$valorPieDetalle."','');
				}
				byId('divCondicionItm".$valorPieDetalle."').style.display = '';
				if (byId('lstMostrarItm".$valorPieDetalle."').value > 0) {
					byId('divCondicionItm".$valorPieDetalle."').style.display = 'none';
					byId('cbxCondicionItm".$valorPieDetalle."').checked = false;
				}");
				
				if (in_array(idArrayPais,array(1,2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$objResponse->script("
					byId('lstMostrarItm".$valorPieDetalle."').style.display = 'none';");
				}
				
				if (in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$objResponse->script("
					byId('divPrecioPagadoItm".$valorPieDetalle."').style.display = 'none';");
				}
			} else if (in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle], array(3))) { // 3 = Contrato
				$objResponse->script("
				byId('divCondicionItm".$valorPieDetalle."').style.display = 'none';
				byId('cbxCondicionItm".$valorPieDetalle."').checked = false;
				selectedOption('lstMostrarItm".$valorPieDetalle."','');
				selectedOption('lstMostrarPendienteItm".$valorPieDetalle."','');");
				
				if (in_array(idArrayPais,array(1,2))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$objResponse->script("
					byId('lstMostrarItm".$valorPieDetalle."').style.display = 'none';");
				}
				
				if (in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
					$objResponse->script("
					byId('divPrecioPagadoItm".$valorPieDetalle."').style.display = 'none';");
				}
			} else if (in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle], array(4))) { // 4 = Cargo
				
			} else if (in_array($frmListaArticuloAux['hddTpItm'.$valorPieDetalle], array(4))) { // 4 = Repuesto
				$objResponse->script("
				byId('divPrecioPagadoItmArticulo".$valorPieDetalle."').style.display = 'none';");
				
				if ($frmListaArticuloAux['cbxCondicionItmArticulo'.$valorPieDetalle] == 1) { // 1 = Pagado, 2 = Financiado
				} else {
					$objResponse->assign("txtPrecioPagadoItmArticulo".$valorPieDetalle,"value",number_format(0, 2, ".", ","));
				}
				
				$objResponse->script("
				if (byId('cbxCondicionItmArticulo".$valorPieDetalle."').checked == true) {
					byId('divPrecioPagadoItmArticulo".$valorPieDetalle."').style.display = '';
				}");
			}
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIva)) {
		foreach($arrayIva as $indiceIva => $valorIva) {
			if ($arrayIva[$indiceIva][2] > 0) {				
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
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
			
			$subTotalIva += doubleval($arrayIva[$indiceIva][2]);
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIvaLocal)) {
		foreach ($arrayIvaLocal as $indiceIva => $valorIva) {
			if ($arrayIvaLocal[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIvaLocal:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIvaLocal:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIvaLocal%s\" name=\"hddIdIvaLocal%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIvaLocal%s\" name=\"hddLujoIvaLocal%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIvaLocal\" name=\"cbxIvaLocal[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIvaLocal%s\" name=\"txtBaseImpIvaLocal%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIvaLocal%s\" name=\"txtIvaLocal%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIvaLocal%s\" name=\"txtSubTotalIvaLocal%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIvaLocal:%s');
					if (obj == undefined)
						$('#trRetencionIva').before(elemento);", 
					$indiceIva, 
						$indiceIva, utf8_encode($arrayIvaLocal[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format(round($arrayIvaLocal[$indiceIva][1], 2), 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format(round($arrayIvaLocal[$indiceIva][2], 2), 2, ".", ","), 
						
					$indiceIva));
			}
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = true;
			byId('txtDescuento').className = 'inputInicial';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = true;
			byId('txtSubTotalDescuento').className = 'inputInicial';");
		}
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = false;
			byId('txtDescuento').className = 'inputHabilitado';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = false;
			byId('txtSubTotalDescuento').className = 'inputHabilitado';");
		}
	}
	$txtDescuento = ($txtDescuento > 0) ? $txtDescuento : 0;
	$txtSubTotalDescuento = ($txtSubTotalDescuento > 0) ? $txtSubTotalDescuento : 0;
	
	$txtTotalOrden = doubleval($txtSubTotal) - doubleval($txtSubTotalDescuento);
	$txtTotalOrden += doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva);
	
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento", "value", number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtTotalAdicionalOtro", "value", number_format($txtTotalAdicionalOtro, 2, ".", ","));
	
	$objResponse->assign("txtGastosConIva", "value", number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign("txtGastosSinIva", "value", number_format($txtGastosSinIva, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalFactura","value",number_format($txtTotalOrden, 2, ".", ","));
	$objResponse->assign("txtMontoPorPagar","value",number_format($txtTotalOrden, 2, ".", ","));
	
	if (isset($arrayObjPiePago)) {
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			$txtIdConcepto = $frmListaPagos['txtIdConcepto'.$valorPiePago];
			
			$objResponse->script(sprintf("byId('btnEliminarPago%s').style.display = '';",
				$valorPiePago));
			
			if ($txtIdConcepto > 0) {
				if (isset($arrayObj4)) {
					foreach ($arrayObj4 as $indice4 => $valor4) {
						$hddNumeroItmBono = $frmListaArticulo['hddNumeroItmBono'.$valor4];
						if ($frmListaArticulo['hddIdConceptoBono'.$hddNumeroItmBono] == $txtIdConcepto) {
							$objResponse->script(sprintf("byId('btnEliminarPago%s').style.display = 'none';",
								$valorPiePago));
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
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	if (isset($arrayObjPiePago)) {
		$i = 0;
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmPago_".$valorPiePago,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmPago_".$valorPiePago,"innerHTML",$i);
			
			$txtMontoPagadoFactura += str_replace(",", "", $frmListaPagos['txtMonto'.$valorPiePago]);
		}
	}
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObjPiePago) > 0) ? implode("|",$arrayObjPiePago) : ""));
	
	$objResponse->assign("txtMontoPagadoFactura","value",number_format($txtMontoPagadoFactura, 2, ".", ","));
	$objResponse->assign("txtMontoPorPagar","value",number_format(str_replace(",", "", $frmTotalDcto['txtTotalOrden']) - $txtMontoPagadoFactura, 2, ".", ","));
	
	if (count($arrayObjPiePago) == 1 && $txtMontoPagadoFactura > str_replace(",", "", $frmTotalDcto['txtTotalOrden'])) {
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			$objResponse->assign("txtMonto".$valorPiePago,"value",number_format(str_replace(",", "", $frmTotalDcto['txtTotalOrden']), 2, ".", ","));
		}
		
		$objResponse->script("xajax_calcularPagos(xajax.getFormValues('frmListaPagos'), xajax.getFormValues('frmDcto'), xajax.getFormValues('frmTotalDcto'))");
	}
	
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

function cargaLstCreditoTradeIn($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$array = array("0" => "Crédito Negativo", "1" => "Crédito Positivo");
	$totalRows = count($array);
	
	$html .= "<select id=\"lstCreditoTradeIn\" name=\"lstCreditoTradeIn\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCreditoTradeIn","innerHTML", $html);
	
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

function cargaLstIva($nombreObjeto, $selId = "", $selVal = "", $bloquearObj = false, $alturaObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	$style = ($alturaObj == true) ? "style=\"height:200px; width:99%\"" : " style=\"width:99%\"";
	
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1 ORDER BY iva;");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." ".$style.">";
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
			} else if ($selId == "" && in_array($rowIva['tipo'],array(1,6)) && $rowIva['activo'] == 1) { // IMPUESTO PREDETERMINADO
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

function eliminarBono($pos) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	fila = document.getElementById('trItmBono".$pos."');
	padre = fila.parentNode;
	padre.removeChild(fila);");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));");
	
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
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	
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
			if (isset($arrayObjPiePago)) {
				foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
					if ($frmListaPagos['txtIdFormaPago'.$valorPiePago] == 8 && $frmListaPagos['txtIdNumeroDctoPago'.$valorPiePago] == $rowTradeInNotaCredito['id_nota_credito_cxc']) {
						$objResponse->script("xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),'".$valorPiePago."');");
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
			if (isset($arrayObjPiePago)) {
				foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
					if ($frmListaPagos['txtIdFormaPago'.$valorPiePago] == 7 && $frmListaPagos['txtIdNumeroDctoPago'.$valorPiePago] == $rowTradeInNotaCredito['id_anticipo']) {
						$objResponse->script("xajax_eliminarPago(xajax.getFormValues('frmListaPagos'),'".$valorPiePago."');");
					}
				}
			}
		}
	}
	
	$objResponse->script("
	fila = document.getElementById('trItmPago_".$pos."');
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
	
	$objResponse->loadCommands(cargaLstIva("lstIvaCbx", "", "", false, true));
	
	return $objResponse;
}

function formBono($frmBono, $frmListaArticulo, $frmListaPagos) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DE LA FACTURA)
	if (isset($frmListaArticulo['cbxPieDetalle']) && isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = array_merge($frmListaArticulo['cbxPieDetalle'], $frmTotalDcto['cbxPieDetalle']);
	} else if (isset($frmListaArticulo['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	} else if (isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmTotalDcto['cbxPieDetalle'];
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (BONO DEL DETALLE)
	$arrayObj4 = $frmListaArticulo['cbxPieBono'];
	
	if (isset($arrayObj4)) {
		foreach ($arrayObj4 as $indice4 => $valor4) {
			$hddNumeroItmBono = $frmListaArticulo['hddNumeroItmBono'.$valor4];
			
			$arrayIdAnticipoPagoBono[] = $frmListaArticulo['hddIdAnticipoPagoBono'.$hddNumeroItmBono];
		}
	}
	
	if (isset($arrayObjPiePago)) {
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			// 1 = Cash Back / Bono Dealer, 6 = Bono Suplidor
			if (in_array($frmListaPagos['txtIdConcepto'.$valorPiePago],array(1,6))
			&& !in_array($frmListaPagos['txtIdNumeroDctoPago'.$valorPiePago],$arrayIdAnticipoPagoBono)) {
				$objResponse->script("
				byId('trlstAnticipoBono').style.display = '';");
				$existeAnticipoPagoBono = true;
				
				$arrayIdAnticipoBono[] = $frmListaPagos['txtIdNumeroDctoPago'.$valorPiePago];
			}
		}
		if ($existeAnticipoPagoBono == true) {
			$objResponse->loadCommands(cargaLstAnticipoBono($arrayIdAnticipoBono));
		}
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

function formItemsPedido($idPedido){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT an_ped_vent.*,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cxc_fact.idFactura AS id_factura_reemplazo,
		cxc_fact.numeroFactura AS numero_factura_reemplazo,
		pres_vent_acc.id_presupuesto_accesorio,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
		CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
		cliente.tipo,
		cliente.ciudad,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.tipo_cuenta_cliente,
		clave_mov.id_clave_movimiento,
		clave_mov.clave,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		IFNULL(uni_fis.id_uni_bas, pres_vent.id_uni_bas) AS id_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		uni_fis.estado_venta,
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo,
		vw_pg_empleado.telefono,
		vw_pg_empleado.celular,
		vw_pg_empleado.email,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.idmoneda, moneda_local.idmoneda) AS id_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.descripcion, moneda_local.descripcion) AS descripcion_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
		pres_vent.id_banco_financiar,
		banco.nombreBanco,
		pres_vent.estado AS estado_presupuesto,
		an_ped_vent.estado_pedido,
		ped_financ.id_pedido_financiamiento,
		ped_financ.numeracion_pedido AS numeracion_pedido_financiamiento,
		ped_financ.estatus_pedido AS estatus_pedido_financiamiento,
		adicional_contrato.nombre_agencia_seguro,
		adicional_contrato.direccion_agencia_seguro,
		adicional_contrato.ciudad_agencia_seguro,
		adicional_contrato.pais_agencia_seguro,
		adicional_contrato.telefono_agencia_seguro,
		
		IF(vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido an_ped_vent
		INNER JOIN cj_cc_cliente cliente ON (an_ped_vent.id_cliente = cliente.id)
		INNER JOIN pg_monedas moneda_local ON (an_ped_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (an_ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN pg_clave_movimiento clave_mov ON (an_ped_vent.id_clave_movimiento = clave_mov.id_clave_movimiento)
		LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN an_adicionales_contrato adicional_contrato ON (an_ped_vent.id_pedido = adicional_contrato.id_pedido)
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
			LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
				LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (an_ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (an_ped_vent.id_factura_cxc = cxc_fact.idFactura)
	WHERE an_ped_vent.id_pedido = %s
		AND ((an_ped_vent.estado_pedido IN (1,2,4)
				AND ((SELECT COUNT(acc_ped.id_pedido) FROM an_accesorio_pedido acc_ped
						WHERE acc_ped.id_pedido = an_ped_vent.id_pedido
							AND acc_ped.estatus_accesorio_pedido = 0) > 0
					OR (SELECT COUNT(paq_ped.id_pedido) FROM an_paquete_pedido paq_ped
						WHERE paq_ped.id_pedido = an_ped_vent.id_pedido
							AND paq_ped.estatus_paquete_pedido = 0) > 0
					OR (SELECT COUNT(uni_fis.id_unidad_fisica) FROM an_unidad_fisica uni_fis
						WHERE uni_fis.id_unidad_fisica = an_ped_vent.id_unidad_fisica
							AND uni_fis.estado_venta = 'RESERVADO') > 0))
			OR (an_ped_vent.estado_pedido IN (1)
				AND an_ped_vent.id_factura_cxc IS NOT NULL))",
		valTpDato($idPedido, "int"));
	$rsPedido = mysql_query($query);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPedido = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_array($rsPedido);
	
	$idEmpresa = $rowPedido['id_empresa'];
	
	// 0 = No Aprobado, 1 = Parcialmente Pagado, 2 = Pagado, 3 = Aprobado, 4 = Atrasado
	if (!((!($rowPedido['id_pedido_financiamiento'] > 0) && $totalRowsPedido > 0)
	|| ($rowPedido['id_pedido_financiamiento'] > 0 && in_array($rowPedido['estatus_pedido_financiamiento'],array(3))))) {
		return $objResponse->script("
		alert('Este Pedido no puede ser Facturado');
		window.location.href='cj_factura_venta_list.php';");
	}
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
	
	$objResponse->assign("txtIdPedidoItems", "value", $rowPedido['id_pedido']);
	$objResponse->assign("txtNumeroPedidoItems", "value", $rowPedido['numeracion_pedido']);
	
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"16%\">Código</td>";
		$htmlTh .= "<td width=\"70%\">Descripción</td>";
		$htmlTh .= "<td width=\"14%\">Total</td>";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,'frmListaItemPedido');\"/></td>";
	$htmlTh .= "</tr>";
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	$checked = ($rowPedido['id_factura_cxc'] > 0) ? "checked=\"checked\" readonly=\"readonly\" style=\"display:none\"" : "";
	$onchange = ($rowPedido['id_factura_cxc'] > 0) ? "onclick=\"this.checked=true\"" : "";
	
	$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td>"."</td>";
		$htmlTb .= "<td>".utf8_encode($rowPedido['vehiculo'].((strlen($rowPedido['placa']) > 0) ? "(".$rowPedido['placa'].")" : ""))."</td>";
		$htmlTb .= "<td align=\"right\">".$rowPedido['abreviacion_moneda'].number_format($rowPedido['precio_venta'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>";
		if (in_array($rowPedido['estado_venta'],array("RESERVADO")) || $rowPedido['id_factura_cxc'] > 0) {
			$htmlTb .= sprintf("
			<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" ".$checked." ".$onchange." value=\"%s\"/>
			<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>
			<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/>",
				$contFila,
				$contFila, $contFila, $rowPedido['id_unidad_fisica'],
				$contFila, $contFila, 3); // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
		}
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
		
	// OPCIONALES
	$queryPedidoDet = sprintf("SELECT *
	FROM an_pedido_venta_detalle an_ped_vent_det
		INNER JOIN iv_articulos art ON (an_ped_vent_det.id_articulo = art.id_articulo)
	WHERE an_ped_vent_det.id_pedido_venta = %s
	ORDER BY an_ped_vent_det.id_pedido_venta_detalle ASC;",
		valTpDato($idPedido, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$checked = ($rowPedido['id_factura_cxc'] > 0) ? "checked=\"checked\" readonly=\"readonly\" style=\"display:none\"" : "";
		$onchange = ($rowPedido['id_factura_cxc'] > 0) ? "onclick=\"this.checked=true\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($rowPedidoDet['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowPedidoDet['descripcion']))));
				$htmlTb .= ($rowPedidoDet['id_condicion_pago'] == 1 && $rowPedidoDet['monto_pagado'] > 0) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$rowPedido['abreviacion_moneda'].number_format(($rowPedidoDet['cantidad'] * $rowPedidoDet['precio_unitario']), 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("
				<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" ".$checked." ".$onchange." value=\"%s\"/>
				<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>
				<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila, $contFila, $rowPedidoDet['id_pedido_venta_detalle'],
					$contFila, $contFila, 4); // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	// ADICIONALES DE PAQUETE
	$queryPedidoDet = sprintf("SELECT
		paq_ped.id_paquete_pedido,
		paq_ped.id_pedido,
		paq_ped.id_acc_paq,
		acc.id_accesorio,
		CONCAT(acc.nom_accesorio, IF (paq_ped.iva_accesorio = 1, ' ', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		paq_ped.id_tipo_accesorio,
		(CASE paq_ped.id_tipo_accesorio
			WHEN 1 THEN 'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
			WHEN 4 THEN 'Cargo'
		END) AS descripcion_tipo_accesorio,
		paq_ped.precio_accesorio,
		paq_ped.costo_accesorio,
		paq_ped.porcentaje_iva_accesorio,
		(paq_ped.precio_accesorio + (paq_ped.precio_accesorio * paq_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
		paq_ped.iva_accesorio,
		paq_ped.monto_pagado,
		paq_ped.id_condicion_pago,
		paq_ped.id_condicion_mostrar,
		paq_ped.monto_pendiente,
		paq_ped.id_condicion_mostrar_pendiente,
		paq_ped.estatus_paquete_pedido
	FROM an_paquete_pedido paq_ped
		INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.id_acc_paq)
		INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
	WHERE paq_ped.id_pedido = %s
	ORDER BY paq_ped.id_tipo_accesorio, paq_ped.id_paquete_pedido",
		valTpDato($idPedido, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while($rowPedidoDet = mysql_fetch_array($rsPedidoDet)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$checked = (in_array($rowPedidoDet['id_tipo_accesorio'],array(3)) || $rowPedido['id_factura_cxc'] > 0) ? "checked=\"checked\" readonly=\"readonly\" style=\"display:none\"" : "";
		$onchange = (in_array($rowPedidoDet['id_tipo_accesorio'],array(3)) || $rowPedido['id_factura_cxc'] > 0) ? "onclick=\"this.checked=true\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= utf8_encode($rowPedidoDet['nom_accesorio']);
				$htmlTb .= ($rowPedidoDet['id_condicion_pago'] == 1 && $rowPedidoDet['monto_pagado'] > 0) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$rowPedido['abreviacion_moneda'].number_format($rowPedidoDet['precio_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($rowPedidoDet['estatus_paquete_pedido'] == 0) { // 0 = Pendiente, 1 = Facturado, 2 = Anulado
				$htmlTb .= sprintf("
				<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" ".$checked." ".$onchange." value=\"%s\"/>
				<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>
				<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila, $contFila, $rowPedidoDet['id_paquete_pedido'],
					$contFila, $contFila, 1); // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	// ADICIONALES
	$queryPedidoDet = sprintf("SELECT
		acc_ped.id_accesorio_pedido,
		acc_ped.id_pedido,
		acc.id_accesorio,
		CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' ', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		acc_ped.id_tipo_accesorio,
		(CASE acc_ped.id_tipo_accesorio
			WHEN 1 THEN 'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
			WHEN 4 THEN 'Cargo'
		END) AS descripcion_tipo_accesorio,
		acc_ped.precio_accesorio,
		acc_ped.costo_accesorio,
		acc_ped.porcentaje_iva_accesorio,
		(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
		acc_ped.iva_accesorio,
		acc_ped.monto_pagado,
		acc_ped.id_condicion_pago,
		acc_ped.id_condicion_mostrar,
		acc_ped.monto_pendiente,
		acc_ped.id_condicion_mostrar_pendiente,
		acc_ped.estatus_accesorio_pedido
	FROM an_accesorio_pedido acc_ped
		INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
	WHERE acc_ped.id_pedido = %s
	ORDER BY acc_ped.id_tipo_accesorio, acc_ped.id_accesorio_pedido",
		valTpDato($idPedido, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowPedidoDet = mysql_fetch_array($rsPedidoDet)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$checked = (in_array($rowPedidoDet['id_tipo_accesorio'],array(3)) || $rowPedido['id_factura_cxc'] > 0) ? "checked=\"checked\" readonly=\"readonly\" style=\"display:none\"" : "";
		$onchange = (in_array($rowPedidoDet['id_tipo_accesorio'],array(3)) || $rowPedido['id_factura_cxc'] > 0) ? "onclick=\"this.checked=true\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"80%\">";
						$htmlTb .= utf8_encode($rowPedidoDet['nom_accesorio']);
						$htmlTb .= ($rowPedidoDet['id_condicion_pago'] == 1 && $rowPedidoDet['monto_pagado'] > 0) ? " <span class=\"textoVerdeNegrita\">[ Pagado ]</span>": "";
					$htmlTb .= "</td>";
					$htmlTb .= "<td width=\"20%\">".utf8_encode($rowPedidoDet['descripcion_tipo_accesorio'])."</td>";
					$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$rowPedido['abreviacion_moneda'].number_format($rowPedidoDet['precio_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($rowPedidoDet['estatus_accesorio_pedido'] == 0) { // 0 = Pendiente, 1 = Facturado, 2 = Anulado
				$htmlTb .= sprintf("
				<input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" ".$checked." ".$onchange." value=\"%s\"/>
				<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" readonly=\"readonly\" value=\"%s\"/>
				<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila, $contFila, $rowPedidoDet['id_accesorio_pedido'],
					$contFila, $contFila, 2); // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaItemsPedido","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $frmDetallePago, $frmListaPagos){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_factura_venta_list","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaLocal = $frmTotalDcto['cbxIvaLocal'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DE LA FACTURA)
	if (isset($frmListaArticulo['cbxPieDetalle']) && isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = array_merge($frmListaArticulo['cbxPieDetalle'], $frmTotalDcto['cbxPieDetalle']);
	} else if (isset($frmListaArticulo['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	} else if (isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmTotalDcto['cbxPieDetalle'];
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (IMPUESTO DEL DETALLE)
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (BONO DEL DETALLE)
	$arrayObjPieBono = $frmListaArticulo['cbxPieBono'];
	
	$queryPedido = sprintf("SELECT 
		an_ped_vent.id_empresa,
		uni_fis.id_unidad_fisica,
		vw_iv_modelo.id_uni_bas
	FROM an_pedido an_ped_vent
		LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	WHERE an_ped_vent.id_pedido = %s;",
		valTpDato($frmDcto['txtIdPedido'], "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsPedido = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_array($rsPedido);
	
	$idEmpresa = $rowPedido['id_empresa'];
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idUnidadFisica = $rowPedido['id_unidad_fisica'];
	$idUnidadBasica = $rowPedido['id_uni_bas'];
	$idEmpleadoAsesor = $frmDcto['hddIdEmpleado'];
	$idClaveMovimiento = $frmDcto['hddIdClaveMovimiento'];
	$idTipoPago = $frmDcto['hddTipoPago'];
	$idFacturaEditada = $frmDcto['txtIdFacturaEditada'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("START TRANSACTION;");
	
	// VERIFICA SI EL DOCUMENTO YA HA SIDO FACTURADO
	$queryVerif = sprintf("SELECT * FROM cj_cc_encabezadofactura
	WHERE numeroPedido = %s
		AND idDepartamentoOrigenFactura IN (%s)
		AND subtotalFactura = %s;",
		valTpDato($idPedido, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"));
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
	
	$baseImponibleIva = 0;
	$porcIva = 0;
	$subTotalIva = 0;
	$baseImponibleIvaLujo = 0;
	$porcIvaLujo = 0;
	$subTotalIvaLujo = 0;
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indiceIva => $valorIva) {
			switch ($frmTotalDcto['hddLujoIva'.$valorIva]) {
				case 0 :
					$baseImponibleIva = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valorIva]);
					$porcIva += str_replace(",", "", $frmTotalDcto['txtIva'.$valorIva]);
					$subTotalIva += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valorIva]);
					break;
				case 1 :
					$baseImponibleIvaLujo = str_replace(",", "", $frmTotalDcto['txtBaseImpIva'.$valorIva]);
					$porcIvaLujo += str_replace(",", "", $frmTotalDcto['txtIva'.$valorIva]);
					$subTotalIvaLujo += str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valorIva]);
					break;
			}
		}
	}
	
	if ($idFacturaEditada > 0) {
		// BUSCA LOS DATOS DE LA FACTURA A EDITAR
		$queryFact = sprintf("SELECT cxc_fact.*,
			(SELECT clave_mov.id_clave_movimiento_contra FROM pg_clave_movimiento clave_mov
			WHERE clave_mov.id_clave_movimiento = cxc_fact.id_clave_movimiento) AS id_clave_movimiento_contra
		FROM cj_cc_encabezadofactura cxc_fact
		WHERE idFactura = %s;",
			valTpDato($idFacturaEditada, "int"));
		$rsFact = mysql_query($queryFact);
		if (!$rsFact) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowFact = mysql_fetch_array($rsFact);
		
		$numeroActual = $rowFact['numeroFactura'];
		$frmDcto['txtNumeroControlFactura'] = $rowFact['numeroControl'];
		$frmDcto['txtFechaFactura'] = $rowFact['fechaRegistroFactura'];
		$frmDcto['txtFechaVencimientoFactura'] = $rowFact['fechaVencimientoFactura'];
		
		// BUSCA EL MOVIMIENTO DE LA UNIDAD
		$queryKardex = sprintf("SELECT DATE_ADD(fechaMovimiento, INTERVAL 2 SECOND) AS fechaMovimiento FROM an_kardex kardex
		WHERE kardex.id_documento = %s
			AND kardex.tipoMovimiento = 3;",
			valTpDato($idFacturaEditada, "int"));
		$rsKardex = mysql_query($queryKardex);
		if (!$rsKardex) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowKardex = mysql_fetch_array($rsKardex);
		
		$fechaMovimiento = $rowKardex['fechaMovimiento'];
		
		// ACTUALIZA LA NUMERACIÓN DE LA FACTURA A EDITAR
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			numeroFactura = CONCAT(numeroFactura,'(',((SELECT COUNT(an_fact.idFactura) FROM an_factura_venta an_fact
														WHERE an_fact.id_empresa = cxc_fact.id_empresa
															AND an_fact.numeroFactura LIKE CONCAT(cxc_fact.numeroFactura,'%s'))),')'),
			numeroControl = CONCAT(numeroControl,'(',((SELECT COUNT(an_fact.idFactura) FROM an_factura_venta an_fact
														WHERE an_fact.id_empresa = cxc_fact.id_empresa
															AND an_fact.numeroControl LIKE CONCAT(cxc_fact.numeroControl,'%s'))),')'),
			anulada = 'SI',
			aplicaLibros = 0,
			id_empleado_anulacion = %s,
			fecha_anulacion = %s
		WHERE cxc_fact.idFactura = %s;",
			valTpDato("%", "campo"),
			valTpDato("%", "campo"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato("NOW()", "campo"),
			valTpDato($idFacturaEditada, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA LOS DATOS DE LA FACTURA A EDITAR
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
			estatus_factura = %s,
			id_empleado_cierre = %s,
			fecha_cierre = %s,
			observacion_cierre = %s
		WHERE idFactura = %s;",
			valTpDato(2, "int"), // Null o 1 = Aprobada, 2 = Aplicada / Cerrada
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato("NOW()", "campo"),
			valTpDato("CERRADA POR EDICION DE LA FACTURA", "text"),
			valTpDato($idFacturaEditada, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA LOS DATOS DEL DETALLE DE LA FACTURA
		$updateSQL = sprintf("UPDATE cj_cc_factura_detalle_accesorios SET
			costo_compra = precio_unitario
		WHERE id_factura = %s;",
			valTpDato($idFacturaEditada, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA LOS DATOS DE LA PARTIDA
		$updateSQL = sprintf("UPDATE an_partida SET
			costo_partida = precio_partida
		WHERE id_factura_venta = %s;",
			valTpDato($idFacturaEditada, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTADO DE LOS ACCESORIOS DE PAQUETE DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_paquete_pedido SET
			costo_accesorio = precio_accesorio
		WHERE id_pedido = (SELECT fact_vent.numeroPedido FROM cj_cc_encabezadofactura fact_vent
							WHERE fact_vent.idFactura = %s);",
			valTpDato($idFacturaEditada, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL); 
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL ESTADO DE LOS ACCESORIOS DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_accesorio_pedido SET
			costo_accesorio = precio_accesorio
		WHERE id_pedido = (SELECT fact_vent.numeroPedido FROM cj_cc_encabezadofactura fact_vent
							WHERE fact_vent.idFactura = %s);",
			valTpDato($idFacturaEditada, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL); 
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE an_factura_venta an_fact SET
			numeroFactura = (SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idFactura = an_fact.idFactura),
			numeroControl = (SELECT cxc_fact.numeroControl FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idFactura = an_fact.idFactura),
			anulada = 'SI'
		WHERE an_fact.idFactura = %s;",
			valTpDato($idFacturaEditada, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$Result1 = guardarNotaCreditoCxC($idFacturaEditada);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"NOTA_CREDITO_CXC");
		}
	} else {
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
	}
	
	$numeroActualFactura = $numeroActual;
	
	// INSERTA LOS DATOS DE LA FACTURA
	$insertSQL = sprintf("INSERT INTO cj_cc_encabezadofactura (id_empresa, idCliente, numeroFactura, numeroControl, fechaRegistroFactura, fechaVencimientoFactura, idDepartamentoOrigenFactura, idVendedor, id_clave_movimiento, numeroPedido, numeroSiniestro, condicionDePago, diasDeCredito, estadoFactura, montoTotalFactura, saldoFactura, observacionFactura, subtotalFactura, interesesFactura, fletesFactura, porcentaje_descuento, descuentoFactura, baseImponible, porcentajeIvaFactura, calculoIvaFactura, base_imponible_iva_lujo, porcentajeIvaDeLujoFactura, calculoIvaDeLujoFactura, montoExento, montoExonerado, estatus_factura, anulada, aplicaLibros, id_empleado_creador, id_credito_tradein) 
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idCliente, "int"),
		valTpDato($numeroActual, "text"),
		valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVencimientoFactura'])), "date"),
		valTpDato($idModulo, "int"),
		valTpDato($idEmpleadoAsesor, "int"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($frmDcto['txtIdPedido'], "int"),
		valTpDato(" ", "text"),
		valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($frmDcto['txtDiasCreditoCliente'], "int"),
		valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada , 2 = Parcialmente Cancelada
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($baseImponibleIva, "real_inglesa"),
		valTpDato($porcIva, "real_inglesa"),
		valTpDato($subTotalIva, "real_inglesa"),
		valTpDato($baseImponibleIvaLujo, "real_inglesa"),
		valTpDato($porcIvaLujo, "real_inglesa"),
		valTpDato($subTotalIvaLujo, "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
		valTpDato(((in_array(idArrayPais,array(3))) ? 1 : 2), "int"), // Null o 1 = Aprobada, 2 = Aplicada / Cerrada
		valTpDato("NO", "text"),
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($frmDcto['lstCreditoTradeIn'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idFactura = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idFactura,
		$idModulo,
		"VENTA");
	
	// INSERTA LOS DATOS DE LA FACTURA EN VEHICULOS
	$insertSQL = sprintf("INSERT INTO an_factura_venta (idFactura, id_empresa, numeroControl, numeroPedido, numeroFactura, estadoFactura, fechaRegistroFactura, fechaVencimientoFactura, subtotalFactura, observacionFactura, baseImponible, porcentajeIvaFactura, calculoIvaFactura, montoExonerado, montoNoGravado, porcentajeIvaDeLujoFactura, calculoIvaDeLujoFactura, montoTotalFactura, saldoFactura, idVendedor, idDepartamentoOrigenFactura, condicionDePago, diasDeCredito, anulada, tipo_factura)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($idFactura, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($frmDcto['txtNumeroControlFactura'], "text"),
		valTpDato($frmDcto['txtIdPedido'], "int"),
		valTpDato($numeroActual, "text"),
		valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaVencimientoFactura'])), "date"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($baseImponibleIva, "real_inglesa"),
		valTpDato($porcIva, "real_inglesa"),
		valTpDato($subTotalIva, "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato($porcIvaLujo, "real_inglesa"),
		valTpDato($subTotalIvaLujo, "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($idEmpleadoAsesor, "int"),
		valTpDato($idModulo, "int"),
		valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($frmDcto['txtDiasCreditoCliente'], "int"),
		valTpDato(0, "int"), // 0 = No, 1 = Si
		valTpDato("", "int")); // NULL = Todo, 1 = Vehiculo, 2 = Gastos
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			if (isset($frmListaArticulo['cbxPieDetalle']) && in_array($valorPieDetalle, $frmListaArticulo['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN EL DETALLE
				$frmListaArticuloAux = $frmListaArticulo;
			} else if (isset($frmTotalDcto['cbxPieDetalle']) && in_array($valorPieDetalle, $frmTotalDcto['cbxPieDetalle'])) { // VERIFICA SI EL ITEM ESTA EN LOS OTROS ADICIONALES
				$frmListaArticuloAux = $frmTotalDcto;
			}
			
			$hddTpItm = $frmListaArticuloAux['hddTpItm'.$valorPieDetalle]; // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
			
			if (in_array($hddTpItm, array(1,2))) { // 1 = Por Paquete, 2 = Individual
				$idAccesorio = $frmListaArticuloAux['hddIdAccesorioItm'.$valorPieDetalle];
				
				$hddIdIvaItm = "";
				$hddIvaItm = 0;
				if (isset($arrayObjIvaItm)) {// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
						$valorIvaItm = explode(":", $valorIvaItm);
						if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticuloAux['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
							$hddIvaItm = $frmListaArticuloAux['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
						}
					}
				}
				
				if (!in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle],array(4))) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato, 4 = Cargo
					// INSERTA EL DETALLE DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_accesorios (id_factura, id_accesorio, id_tipo_accesorio, cantidad, precio_unitario, costo_compra, id_iva, iva, tipo_accesorio, monto_pagado, id_condicion_pago, id_condicion_mostrar, monto_pendiente, id_condicion_mostrar_pendiente)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($idAccesorio, "int"),
						valTpDato($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle], "int"),
						valTpDato(1, "real_inglesa"),
						valTpDato($frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle], "real_inglesa"),
						valTpDato($frmListaArticuloAux['hddCostoItm'.$valorPieDetalle], "real_inglesa"),
						valTpDato($hddIdIvaItm, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($hddTpItm, "int"),
						valTpDato($frmListaArticuloAux['txtPrecioPagadoItm'.$valorPieDetalle], "real_inglesa"),
						valTpDato($frmListaArticuloAux['cbxCondicionItm'.$valorPieDetalle], "int"),
						valTpDato($frmListaArticuloAux['lstMostrarItm'.$valorPieDetalle], "int"),
						valTpDato((str_replace(",","",$frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle]) - str_replace(",","",$frmListaArticuloAux['txtPrecioPagadoItm'.$valorPieDetalle])), "real_inglesa"),
						valTpDato($frmListaArticuloAux['lstMostrarPendienteItm'.$valorPieDetalle], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL); 
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idFacturaDetAccesorio = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
				
				switch ($hddTpItm) { // 1 = Por Paquete, 2 = Individual
					case 1 :
						$idPaquetePedido = $frmListaArticuloAux['hddIdItm'.$valorPieDetalle];
						
						$insertSQL = sprintf("INSERT INTO an_partida (id_unidad_fisica, id_factura_venta, tipo_partida, tipo_registro, operador, id_tabla_tipo_partida, id_accesorio, precio_partida, costo_partida, iva_partida, clave_iva_partida, porcentaje_iva_partida, cantidad)
						SELECT DISTINCT
							%s,
							%s,
							'VENTA',
							'PAQUETE',
							'NORMAL',
							an_acc_paq.id_acc_paq,
							an_accesorio.id_accesorio,
							an_paquete_pedido.precio_accesorio,
							an_paquete_pedido.costo_accesorio,
							an_paquete_pedido.iva_accesorio,
							0,
							an_paquete_pedido.porcentaje_iva_accesorio,
							1
						FROM an_paquete_pedido
							INNER JOIN an_acc_paq ON (an_acc_paq.id_acc_paq = an_paquete_pedido.id_acc_paq)
							INNER JOIN an_accesorio ON (an_accesorio.id_accesorio = an_acc_paq.id_accesorio)
						WHERE id_paquete_pedido = %s;",
							valTpDato($idUnidadFisica, "int"),
							valTpDato($idFactura, "int"),
							valTpDato($idPaquetePedido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						// ACTUALIZA EL ESTADO DE LOS ACCESORIOS DEL PEDIDO
						$updateSQL = sprintf("UPDATE an_paquete_pedido SET
							monto_pagado = %s,
							id_condicion_pago = %s,
							id_condicion_mostrar = %s,
							monto_pendiente = %s,
							id_condicion_mostrar_pendiente = %s,
							estatus_paquete_pedido = %s
						WHERE id_paquete_pedido = %s;",
							valTpDato($frmListaArticuloAux['txtPrecioPagadoItm'.$valorPieDetalle], "real_inglesa"),
							valTpDato($frmListaArticuloAux['cbxCondicionItm'.$valorPieDetalle], "int"),
							valTpDato($frmListaArticuloAux['lstMostrarItm'.$valorPieDetalle], "int"),
							valTpDato((str_replace(",","",$frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle]) - str_replace(",","",$frmListaArticuloAux['txtPrecioPagadoItm'.$valorPieDetalle])), "real_inglesa"),
							valTpDato($frmListaArticuloAux['lstMostrarPendienteItm'.$valorPieDetalle], "int"),
							valTpDato(1, "int"), // 0 = Pendiente, 1 = Facturado
							valTpDato($idPaquetePedido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL); 
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						break;
					case 2 :
						$idAccesorioPedido = $frmListaArticuloAux['hddIdItm'.$valorPieDetalle];
						
						$insertSQL = sprintf("INSERT INTO an_partida (id_unidad_fisica, id_factura_venta, tipo_partida, tipo_registro, operador, id_tabla_tipo_partida, id_accesorio, precio_partida, costo_partida, iva_partida, clave_iva_partida, porcentaje_iva_partida, cantidad) 
						SELECT DISTINCT
							%s,
							%s,
							'VENTA',
							'ACCESORIO',
							'NORMAL',
							an_accesorio.id_accesorio,
							an_accesorio.id_accesorio,
							an_accesorio_pedido.precio_accesorio,
							an_accesorio_pedido.costo_accesorio,
							an_accesorio_pedido.iva_accesorio,
							0,
							an_accesorio_pedido.porcentaje_iva_accesorio,
							1
						FROM an_accesorio_pedido
							INNER JOIN an_accesorio ON (an_accesorio.id_accesorio = an_accesorio_pedido.id_accesorio)
						WHERE id_accesorio_pedido = %s;",
							valTpDato($idUnidadFisica, "int"),
							valTpDato($idFactura, "int"),
							valTpDato($idAccesorioPedido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL); 
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						$updateSQL = sprintf("UPDATE an_accesorio_pedido SET
							monto_pagado = %s,
							id_condicion_pago = %s,
							id_condicion_mostrar = %s,
							monto_pendiente = %s,
							id_condicion_mostrar_pendiente = %s,
							estatus_accesorio_pedido = %s
						WHERE id_accesorio_pedido = %s;",
							valTpDato($frmListaArticuloAux['txtPrecioPagadoItm'.$valorPieDetalle], "real_inglesa"),
							valTpDato($frmListaArticuloAux['cbxCondicionItm'.$valorPieDetalle], "int"),
							valTpDato($frmListaArticuloAux['lstMostrarItm'.$valorPieDetalle], "int"),
							valTpDato((str_replace(",","",$frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle]) - str_replace(",","",$frmListaArticuloAux['txtPrecioPagadoItm'.$valorPieDetalle])), "real_inglesa"),
							valTpDato($frmListaArticuloAux['lstMostrarPendienteItm'.$valorPieDetalle], "int"),
							valTpDato(1, "int"), // 0 = Pendiente, 1 = Facturado
							valTpDato($idAccesorioPedido, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL); 
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						break;
				}
				
				$hddIdClienteItm = $frmListaArticuloAux['hddIdClienteItm'.$valorPieDetalle];
				$hddIdMotivoItm = $frmListaArticuloAux['hddIdMotivoItm'.$valorPieDetalle];
				$hddIdTipoComisionItm = str_replace(",", "", $frmListaArticuloAux['hddIdTipoComisionItm'.$valorPieDetalle]);
				$hddPorcentajeComisionItm = str_replace(",", "", $frmListaArticuloAux['hddPorcentajeComisionItm'.$valorPieDetalle]);
				$hddMontoComisionItm = str_replace(",", "", $frmListaArticuloAux['hddMontoComisionItm'.$valorPieDetalle]);
				
				$hddIdMotivoCargoItm = $frmListaArticuloAux['hddIdMotivoCargoItm'.$valorPieDetalle];
				$hddMontoCargoItm = $frmListaArticuloAux['txtTotalConImpuestoItm'.$valorPieDetalle];
				
				if (in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle],array(3)) && $hddPorcentajeComisionItm > 0) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato, 4 = Cargo
					$Result1 = guardarNotaCargoCxC(array(
						"txtIdEmpresa" => $idEmpresa,
						"txtIdCliente" => $hddIdClienteItm,
						"idModulo" => $idModulo,
						"txtIdMotivoCxC" => $hddIdMotivoItm,
						"idFactura" => $idFactura,
						"idFacturaDetAccesorio" => $idFacturaDetAccesorio,
						"idAccesorio" => $idAccesorio,
						"hddIdTipoComisionItm" => $hddIdTipoComisionItm, // 1 = Porcentaje, 2 = Monto, 3 = Utilidad
						"hddPorcentajeComisionItm" => $hddPorcentajeComisionItm,
						"hddMontoComisionItm" => $hddMontoComisionItm));
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$arrayIdDctoContabilidad[] = array(
							$Result1[1],
							$Result1[2],
							"NOTA_DEBITO_CXC");
					}
				} else if (in_array($frmListaArticuloAux['hddTipoAdicionalItm'.$valorPieDetalle],array(4))) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato, 4 = Cargo
					$Result1 = guardarNotaCargoCxC(array(
						"txtIdEmpresa" => $idEmpresa,
						"txtIdCliente" => $idCliente,
						"idModulo" => $idModulo,
						"txtIdMotivoCxC" => $hddIdMotivoCargoItm,
						"idFactura" => $idFactura,
						"idFacturaDetAccesorio" => $idFacturaDetAccesorio,
						"idAccesorio" => $idAccesorio,
						"hddMontoComisionItm" => $hddMontoCargoItm));
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$arrayIdDctoContabilidad[] = array(
							$Result1[1],
							$Result1[2],
							"NOTA_DEBITO_CXC");
					}
				}
			} else if ($hddTpItm == 3) { // 3 = Unidad Física
				$facturarUnidadFisica = true;
				
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_vehiculo (id_factura, id_unidad_fisica, id_condicion_unidad, precio_unitario, costo_compra, precio_compra, costo_agregado, costo_depreciado, costo_trade_in)
				SELECT
					%s,
					uni_fis.id_unidad_fisica,
					uni_fis.id_condicion_unidad,
					%s,
					%s,
					uni_fis.precio_compra,
					uni_fis.costo_agregado,
					uni_fis.costo_depreciado,
					uni_fis.costo_trade_in
				FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_unidad_fisica = %s;",
					valTpDato($idFactura, "int"),
					valTpDato($frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle], "real_inglesa"),
					valTpDato($frmListaArticuloAux['hddCostoItm'.$valorPieDetalle], "real_inglesa"),
					valTpDato($idUnidadFisica, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idFacturaDetalleVehiculo = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
						$valorIvaItm = explode(":", $valorIvaItm);
						if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticuloAux['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
							$hddIvaItm = $frmListaArticuloAux['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
							
							$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_vehiculo_impuesto (id_factura_detalle_vehiculo, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idFacturaDetalleVehiculo, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
						}
					}
				}
				
				$updateSQL = sprintf("UPDATE an_unidad_fisica SET
					estado_venta = %s,
					fecha_pago_venta = %s
				WHERE id_unidad_fisica = %s;",
					valTpDato("VENDIDO", "text"),
					valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
					valTpDato($idUnidadFisica, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL); 
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idUnidadBasica, "int"),
					valTpDato($idUnidadFisica, "int"),
					valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($idClaveMovimiento, "int"),
					valTpDato(1, "real_inglesa"),
					valTpDato($frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle], "real_inglesa"),
					valTpDato($frmListaArticuloAux['hddCostoItm'.$valorPieDetalle], "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
					valTpDato(((str_replace(",", "", $frmTotalDcto['txtDescuento']) * $frmListaArticuloAux['txtPrecioItm'.$valorPieDetalle]) / 100), "real_inglesa"),
					valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
					((isset($fechaMovimiento)) ? valTpDato($fechaMovimiento, "date") : valTpDato("NOW()", "campo")));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL); 
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// MODIFICA EL ESTATUS DE LA UNIDAD FISICA
				$updateSQL = sprintf("UPDATE an_pedido SET
					estatus_unidad_fisica = %s
				WHERE id_pedido = %s;",
					valTpDato(1, "int"), // 0 = Pendiente, 1 = Facturado
					valTpDato($frmDcto['txtIdPedido'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			} else if ($hddTpItm == 4) { // 4 = Repuesto
				$frmListaArticulo = $frmListaArticuloAux;
				
				if ($idMovimiento == 0) {
					// INSERTA EL MOVIMIENTO
					$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
					VALUE (%s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
						valTpDato(3, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimiento, "int"),
						valTpDato($idFactura, "int"),
						valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaFactura'])), "date"),
						valTpDato($idCliente, "int"),
						valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
						valTpDato($_SESSION['idUsuarioSysGts'], "int"),
						valTpDato($idTipoPago, "int")); // 0 = Credito, 1 = Contado
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idMovimiento = mysql_insert_id();
				}
				
				$idPedidoDet = $frmListaArticulo['hddIdItm'.$valorPieDetalle];
				$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$valorPieDetalle];
				$idCasilla = $frmListaArticulo['hddIdCasilla'.$valorPieDetalle];
				$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]);
				$cantDespachada = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]);
				$cantPendiente = doubleval($cantPedida) - doubleval($cantDespachada);
				$gastoUnitario = str_replace(",", "", $frmListaArticulo['hddGastoItm'.$valorPieDetalle]) / $cantDespachada;
				$precioUnitario = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valorPieDetalle]) + $gastoUnitario;
				$precioSugerido = str_replace(",", "", $frmListaArticulo['hddPrecioSugeridoItm'.$valorPieDetalle]);
				$costoUnitario = str_replace(",", "", $frmListaArticulo['hddCostoItm'.$valorPieDetalle]);
				$hddIdIvaItm = "";
				$hddIvaItm = 0;
				if (isset($arrayObjIvaItm)) {// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
						$valorIvaItm = explode(":", $valorIvaItm);
						if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
						}
					}
				}
				$totalArticulo = $cantDespachada * $precioUnitario;
				
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle (id_factura, id_articulo, cantidad, pendiente, costo_compra, precio_unitario, precio_sugerido, id_iva, iva, estatus)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($cantPedida, "real_inglesa"),
					valTpDato($cantPendiente, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($precioSugerido, "real_inglesa"),
					valTpDato($hddIdIvaItm, "int"),
					valTpDato($hddIvaItm, "real_inglesa"),
					valTpDato(1, "text")); // 0 = Pendiente, 1 = Entregado, 2 = Devuelto
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idFacturaDetalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// ELIMINA LOS IMPUESTOS DEL DETALLE DEL PEDIDO
				$deleteSQL = sprintf("DELETE FROM an_pedido_venta_detalle_impuesto WHERE id_pedido_venta_detalle = %s;",
					valTpDato($idPedidoDet, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
						$valorIvaItm = explode(":", $valorIvaItm);
						if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
							
							$insertSQL = sprintf("INSERT INTO an_pedido_venta_detalle_impuesto (id_pedido_venta_detalle, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idPedidoDet, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
							$insertSQL = sprintf("INSERT INTO cj_cc_factura_detalle_impuesto (id_factura_detalle, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idFacturaDetalle, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
						}
					}
				}
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, id_clave_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
					valTpDato($idModulo, "int"),
					valTpDato($idFactura, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato(3, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($idClaveMovimiento, "int"),
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
					valTpDato(((str_replace(",", "", $frmTotalDcto['txtDescuento']) * $precioUnitario) / 100), "real_inglesa"),
					valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
					valTpDato($frmTotalDcto['txtObservacion'], "text"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA EL DETALLE DEL MOVIMIENTO
				$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idMovimiento, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idKardex, "int"),
					valTpDato($cantDespachada, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
					valTpDato(((str_replace(",", "", $frmTotalDcto['txtDescuento']) * $precioUnitario) / 100), "real_inglesa"),
					valTpDato(0, "int"), // 0 = Unitario, 1 = Import
					valTpDato(0, "boolean"), // 0 = No, 1 = Si
					valTpDato("", "int"),
					valTpDato("", "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				$updateSQL = sprintf("UPDATE an_pedido_venta_detalle SET
					pendiente = %s,
					estatus = IF(%s = 0, 1, estatus)
				WHERE id_pedido_venta_detalle = %s;",
					valTpDato($cantPendiente, "int"),
					valTpDato($cantPendiente, "int"),
					valTpDato($idPedidoDet, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
				$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				$objResponse->assign("hddIdFactDet".$valorPieDetalle,"value",$idFacturaDetalle);
			}
		}
	}
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indiceIva => $valorIva) {
			if ($frmTotalDcto['txtSubTotalIva'.$valorIva] > 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['txtBaseImpIva'.$valorIva], "real_inglesa"),
					valTpDato($frmTotalDcto['txtSubTotalIva'.$valorIva], "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdIva'.$valorIva], "int"),
					valTpDato($frmTotalDcto['txtIva'.$valorIva], "real_inglesa"),
					valTpDato($frmTotalDcto['hddLujoIva'.$valorIva], "boolean"));
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
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// MODIFICA EL ESTATUS DEL PEDIDO DE VENTA
	$updateSQL = sprintf("UPDATE an_pedido SET
		estado_pedido = %s
	WHERE id_pedido = %s;",
		valTpDato(2, "int"), // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
		valTpDato($frmDcto['txtIdPedido'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// CALCULO DE LAS COMISIONES
	$Result1 = generarComision($idFactura);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	// INSERTA EL PAGO DEL DOCUMENTO (PAGO DE FACTURAS) SOLO SI ES DE CONTADO
	if ($idTipoPago == 1 || count($arrayObjPiePago) > 0) { // 0 = Credito, 1 = Contado
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
		foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
			$txtIdFormaPago = $frmListaPagos['txtIdFormaPago'.$valorPiePago];
			$hddIdPago = $frmListaPagos['hddIdPago'.$valorPiePago];
			
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
						"cbxPosicionPago" => $valorPiePago,
						"hddIdPago" => $hddIdPago,
						"txtIdFormaPago" => $txtIdFormaPago,
						"txtIdNumeroDctoPago" => $frmListaPagos['txtIdNumeroDctoPago'.$valorPiePago],
						"txtNumeroDctoPago" => $frmListaPagos['txtNumeroDctoPago'.$valorPiePago],
						"txtIdBancoCliente" => $frmListaPagos['txtIdBancoCliente'.$valorPiePago],
						"txtCuentaClientePago" => $frmListaPagos['txtCuentaClientePago'.$valorPiePago],
						"txtIdBancoCompania" => $frmListaPagos['txtIdBancoCompania'.$valorPiePago],
						"txtIdCuentaCompaniaPago" => $frmListaPagos['txtIdCuentaCompaniaPago'.$valorPiePago],
						"txtCuentaCompaniaPago" => asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valorPiePago]),
						"txtFechaDeposito" => $frmListaPagos['txtFechaDeposito'.$valorPiePago],
						"txtTipoTarjeta" => $frmListaPagos['txtTipoTarjeta'.$valorPiePago],
						"hddObjDetalleDeposito" => $frmDetallePago['hddObjDetalleDeposito'],
						"hddObjDetalleDepositoFormaPago" => $frmDetallePago['hddObjDetalleDepositoFormaPago'],
						"hddObjDetalleDepositoBanco" => $frmDetallePago['hddObjDetalleDepositoBanco'],
						"hddObjDetalleDepositoNroCuenta" => $frmDetallePago['hddObjDetalleDepositoNroCuenta'],
						"hddObjDetalleDepositoNroCheque" => $frmDetallePago['hddObjDetalleDepositoNroCheque'],
						"hddObjDetalleDepositoMonto" => $frmDetallePago['hddObjDetalleDepositoMonto'],
						"txtMonto" => $frmListaPagos['txtMonto'.$valorPiePago],
						"cbxCondicionMostrar" => $frmListaPagos['cbxCondicionMostrar'.$valorPiePago],
						"lstSumarA" => $frmListaPagos['lstSumarA'.$valorPiePago]
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
	
	// CUENTAS POR COBRAR DE LOS BONOS
	if (isset($arrayObjPieBono)) {
		foreach ($arrayObjPieBono as $indicePieBono => $valorPieBono) {
			$hddNumeroItmBono = $frmListaArticulo['hddNumeroItmBono'.$valorPieBono];
			$hddIdNotaDebitoBono = $frmListaArticulo['hddIdNotaDebitoBono'.$hddNumeroItmBono];
			$hddIdUnidadFisicaBono = $frmListaArticulo['hddIdUnidadFisicaBono'.$hddNumeroItmBono];
			$hddIdClienteBono = $frmListaArticulo['hddIdClienteBono'.$hddNumeroItmBono];
			$hddIdMotivoBono = $frmListaArticulo['hddIdMotivoBono'.$hddNumeroItmBono];
			$hddIdAnticipoPagoBono = $frmListaArticulo['hddIdAnticipoPagoBono'.$hddNumeroItmBono];
			$txtMontoBono = $frmListaArticulo['txtMontoBono'.$hddNumeroItmBono];
			
			if (!($hddIdNotaDebitoBono > 0)) {
				$Result1 = guardarNotaCargoCxC(array(
					"txtIdEmpresa" => $idEmpresa,
					"txtIdCliente" => $hddIdClienteBono,
					"idModulo" => $idModulo,
					"txtIdMotivoCxC" => $hddIdMotivoBono,
					"idAnticipoBono" => $hddIdAnticipoPagoBono,
					"idUnidadFisicaBono" => $hddIdUnidadFisicaBono,
					"txtSubTotalNotaCargo" => $txtMontoBono));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$arrayIdDctoContabilidad[] = array(
						$Result1[1],
						$Result1[2],
						"NOTA_DEBITO_CXC");
				}
			}
		}
	}
	
	// BUSCA LOS PAGOS DE ANTICIPO
	$queryDctoPago = sprintf("SELECT *
	FROM (SELECT 
			cxc_pago.id_factura,
			cxc_pago.fechaPago,
			cxc_pago.formaPago,
			cxc_pago.numeroDocumento AS id_documento_pago,
			cxc_pago.montoPagado
		FROM sa_iv_pagos cxc_pago
		
		UNION
		
		SELECT 
			cxc_pago.id_factura,
			cxc_pago.fechaPago,
			cxc_pago.formaPago,
			cxc_pago.numeroDocumento AS id_documento_pago,
			cxc_pago.montoPagado
		FROM an_pagos cxc_pago) AS query
	WHERE query.id_factura = %s
		AND query.formaPago IN (7);",
		valTpDato($idFactura, "int"));
	$rsDctoPago = mysql_query($queryDctoPago);
	if (!$rsDctoPago) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsDctoPago = mysql_num_rows($rsDctoPago);
	while ($rowDctoPago = mysql_fetch_assoc($rsDctoPago)) {
		$idAnticipo = $rowDctoPago['id_documento_pago'];
		
		// VERIFICA SI ALGUN ANTICIPO DE TRADE IN TIENE ALGUN DOCUMENTO ASOCIADO QUE AFECTE AL COSTO DE LA UNIDAD VENDIDA
		$queryTradeInCxC = sprintf("SELECT
			(CASE 
				WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
					'ND_CXC'
				WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
					'NC_CXC'
			END) AS tipo_documento,
			(CASE 
				WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
					cxc_nd.idNotaCargo
				WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
					cxc_nc.idNotaCredito
			END) AS id_documento,
			(CASE 
				WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
					cxc_nd.montoTotalNotaCargo
				WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
					cxc_nc.montoNetoNotaCredito
			END) AS monto_total
		FROM an_tradein_cxc tradein_cxc
			LEFT JOIN cj_cc_notadecargo cxc_nd ON (tradein_cxc.id_nota_cargo_cxc = cxc_nd.idNotaCargo AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL)
			LEFT JOIN cj_cc_notacredito cxc_nc ON (tradein_cxc.id_nota_credito_cxc = cxc_nc.idNotaCredito AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)
		WHERE tradein_cxc.id_anticipo = %s
			AND tradein_cxc.estatus = 1;",
			valTpDato($idAnticipo, "int"));
		$rsTradeInCxC = mysql_query($queryTradeInCxC);
		if (!$rsTradeInCxC) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsTradeInCxC = mysql_num_rows($rsTradeInCxC);
		while ($rowTradeInCxC = mysql_fetch_assoc($rsTradeInCxC)) {
			$tipoDocumento = $rowTradeInCxC['tipo_documento'];
			$idDocumento = $rowTradeInCxC['id_documento'];
			
			// INSERTA EL DETALLE DEL AGREGADO
			if ($idDocumento > 0) {
				$contAgregado++;
				
				if ($tipoDocumento == 'ND_CXC') {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_cargo_cxc, monto)
					VALUE (%s, %s, %s);",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($idDocumento, "int"),
						valTpDato($rowTradeInCxC['monto_total'], "real_inglesa"));
				} else if ($tipoDocumento == 'NC_CXC') {
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica_agregado (id_unidad_fisica, id_nota_credito_cxc, monto)
					VALUE (%s, %s, %s);",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($idDocumento, "int"),
						valTpDato($rowTradeInCxC['monto_total'], "real_inglesa"));
				}
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idUnidadFisicaAgregado = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	if ($contAgregado > 0) {
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
	}
	
	// ACTUALIZA EL COSTO DE LA VENTA DE LA UNIDAD EN LA FACTURA
	$updateSQL = sprintf("UPDATE cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic SET
		costo_compra = (SELECT 
							IFNULL(uni_fis.precio_compra, 0)
								+ IFNULL(uni_fis.costo_agregado, 0)
								- IFNULL(uni_fis.costo_depreciado, 0)
								- IFNULL(uni_fis.costo_trade_in, 0)
						FROM an_unidad_fisica uni_fis
						WHERE uni_fis.id_unidad_fisica = cxc_fact_det_vehic.id_unidad_fisica)
	WHERE cxc_fact_det_vehic.id_factura = %s
		AND cxc_fact_det_vehic.id_unidad_fisica = %s;",
		valTpDato($idFactura, "int"),
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL COSTO DE LA VENTA DE LA UNIDAD EN EL KARDEX
	$updateSQL = sprintf("UPDATE an_kardex kardex SET
		costo = (SELECT 
					IFNULL(uni_fis.precio_compra, 0)
						+ IFNULL(uni_fis.costo_agregado, 0)
						- IFNULL(uni_fis.costo_depreciado, 0)
						- IFNULL(uni_fis.costo_trade_in, 0)
				FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_unidad_fisica = kardex.idUnidadFisica)
	WHERE kardex.id_documento = %s
		AND kardex.idUnidadFisica = %s
		AND kardex.tipoMovimiento IN (3);",
		valTpDato($idFactura, "int"),
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
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
		
	$objResponse->assign("txtIdFactura","value",$idFactura);
	$objResponse->assign("txtNumeroFactura","value",$numeroActualFactura);
	
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
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert("Factura Guardada con Exito");
	
	if ($idFacturaEditada > 0) {
		$objResponse->script(sprintf("window.location.href='cj_facturas_por_pagar_form.php?id=%s';", $idFactura));
	} else {
		$objResponse->script("verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=".$idFactura."', 960, 550);");
		
		if ($idEncabezadoReciboPago > 0) {
			$objResponse->script(sprintf("verVentana('reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s',960,550)", $idEncabezadoReciboPago));
		}
		
		switch ($idTipoPago) { // 0 = Credito, 1 = Contado
			case 0 : $objResponse->script(sprintf("window.location.href='cj_factura_venta_list.php';")); break;
			case 1 : $objResponse->script(sprintf("window.location.href='cj_factura_venta_list.php';")); break;
		}
	}
	
	return $objResponse;
}

function insertarBono($frmBono, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (BONO DEL DETALLE)
	$arrayObj4 = $frmListaArticulo['cbxPieBono'];
	$contFila4 = $arrayObj4[count($arrayObj4)-1];
	
	$hddNumeroItmBono = $frmBono['hddNumeroItmBono'];
	$hddIdUnidadFisicaBono = $frmListaArticulo['hddIdItm'.$hddNumeroItmBono];
	$hddIdAnticipoPagoBono = $frmBono['lstAnticipoBono'];
	$txtIdClienteBono = $frmBono['txtIdClienteBono'];
	$txtNombreClienteBono = $frmBono['txtNombreClienteBono'];
	$txtIdMotivoBono = $frmBono['txtIdMotivoBono'];
	$txtMotivoBono = $frmBono['txtMotivoBono'];
	$txtMontoBono = str_replace(",", "", $frmBono['txtMontoBono']);
	
	$Result1 = insertarItemBono($contFila4, $hddNumeroItmBono, $hddIdNotaDebitoBono, $hddIdUnidadFisicaBono, $hddIdAnticipoPagoBono, $txtIdClienteBono, $txtNombreClienteBono, $txtIdMotivoBono, $txtMotivoBono, $txtMontoBono);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila4 = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj4[] = $contFila4;
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));");
	
	$objResponse->script("
	byId('imgCerrarDivFlotante1').click();");
	
	return $objResponse;
}

function insertarItem($frmListaItemPedido, $frmListaArticulo, $frmTotalDcto, $frmListaPagos){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $idModuloPpal;
	
	$objResponse->script("byId('trCreditoTradeIn').style.display = 'none';");
	if (in_array(idArrayPais,array(3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$objResponse->script("byId('trCreditoTradeIn').style.display = '';");
	}
	
	//SE INCLUYO 1 PARA OPCION "SELECCIONA TIPO DE PAGO PARA CAMBIAR TIPO DE IMPUESTO"
	if (in_array(idArrayPais,array(1,2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$objResponse->script("
		byId('aImpuestoArticulo').style.display = 'none';");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DE LA FACTURA)
	if (isset($frmListaArticulo['cbxPieDetalle']) && isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = array_merge($frmListaArticulo['cbxPieDetalle'], $frmTotalDcto['cbxPieDetalle']);
	} else if (isset($frmListaArticulo['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	} else if (isset($frmTotalDcto['cbxPieDetalle'])) {
		$arrayObjPieDetalle = $frmTotalDcto['cbxPieDetalle'];
	}
	$contFila = $arrayObjPieDetalle[count($arrayObjPieDetalle)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	$contFila2 = $arrayObjPiePago[count($arrayObjPiePago)-1];
	
	if (!(count($frmListaItemPedido['cbxItm']) > 0)) {
		return $objResponse->alert("Debe seleccionar al menos un item");
	}
	
	$queryPedido = sprintf("SELECT an_ped_vent.*,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cxc_fact.idFactura AS id_factura_reemplazo,
		cxc_fact.numeroFactura AS numero_factura_reemplazo,
		pres_vent_acc.id_presupuesto_accesorio,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
		CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
		cliente.tipo,
		cliente.ciudad,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.tipo_cuenta_cliente,
		clave_mov.id_clave_movimiento,
		clave_mov.clave,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		IFNULL(uni_fis.id_uni_bas, pres_vent.id_uni_bas) AS id_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		uni_fis.estado_venta,
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo,
		vw_pg_empleado.telefono,
		vw_pg_empleado.celular,
		vw_pg_empleado.email,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.idmoneda, moneda_local.idmoneda) AS id_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.descripcion, moneda_local.descripcion) AS descripcion_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
		pres_vent.id_banco_financiar,
		banco.nombreBanco,
		pres_vent.estado AS estado_presupuesto,
		an_ped_vent.estado_pedido,
		ped_financ.id_pedido_financiamiento,
		ped_financ.numeracion_pedido AS numeracion_pedido_financiamiento,
		ped_financ.estatus_pedido AS estatus_pedido_financiamiento,
		adicional_contrato.nombre_agencia_seguro,
		adicional_contrato.direccion_agencia_seguro,
		adicional_contrato.ciudad_agencia_seguro,
		adicional_contrato.pais_agencia_seguro,
		adicional_contrato.telefono_agencia_seguro,
		
		IF(vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido an_ped_vent
		INNER JOIN cj_cc_cliente cliente ON (an_ped_vent.id_cliente = cliente.id)
		INNER JOIN pg_monedas moneda_local ON (an_ped_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (an_ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN pg_clave_movimiento clave_mov ON (an_ped_vent.id_clave_movimiento = clave_mov.id_clave_movimiento)
		LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN an_adicionales_contrato adicional_contrato ON (an_ped_vent.id_pedido = adicional_contrato.id_pedido)
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
			LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
				LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (an_ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (an_ped_vent.id_factura_cxc = cxc_fact.idFactura)
	WHERE an_ped_vent.id_pedido = %s;",
		valTpDato($frmListaItemPedido['txtIdPedidoItems'], "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedido = mysql_fetch_array($rsPedido);
	
	$idFactura = $rowPedido['id_factura_cxc'];
	$idPedidoFinanciamiento = $rowPedido['id_pedido_financiamiento'];
	$idEmpresa = $rowPedido['id_empresa'];
	$idCliente = $rowPedido['id_cliente'];
	$condicionPago = ($rowPedido['porcentaje_inicial'] == 100) ? "1" : "0"; // 0 = Credito, 1 = Contado
	$txtTasaCambio = $rowPedido['monto_tasa_cambio'];
	
	// VERIFICA VALORES DE CONFIGURACION (Incluir Saldo Negativo del Trade In en Precio de Venta de la Unidad (Copia Banco))
	$queryConfig207 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 207 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig207 = mysql_query($queryConfig207);
	if (!$rsConfig207) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig207 = mysql_num_rows($rsConfig207);
	$rowConfig207 = mysql_fetch_assoc($rsConfig207);
	
	// CARGA LOS DATOS DEL EMPLEADO VENDEDOR
	$queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s",
		valTpDato($rowPedido['asesor_ventas'], "text"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
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
		$objResponse->assign("txtTipoPago","value","CRÉDITO");
		
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
	
	// VERIFICA SI EL CLIENTE TIENE UN ANTICIPO NORMAL, CON CASH BACK / BONO DEALER, TRADE-IN, BONO SUPLIDOR AUN DISPONIBLE PARA ASIGNAR
	// 0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado)
	// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
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
	WHERE cxc_ant.idCliente = %s
		AND (cxc_ant.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
				WHERE suc.id_empresa_padre = cxc_ant.id_empresa)
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_ant.id_empresa)
			OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
												WHERE suc.id_empresa = cxc_ant.id_empresa))
		AND cxc_ant.idDepartamento IN (%s)
		AND ((cxc_pago.id_concepto IN (2)
				AND (cxc_ant.saldoAnticipo > 0
					OR (cxc_ant.saldoAnticipo = 0 AND cxc_ant.estadoAnticipo IN (1))))
			OR (cxc_pago.id_concepto IN (1,6,7,8,9)
				AND cxc_ant.saldoAnticipo > 0)
			OR (cxc_pago.id_concepto IS NULL
				AND cxc_ant.saldoAnticipo > 0))
		AND cxc_ant.estatus = 1;",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idModuloPpal, "campo"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	if ((!in_array(idArrayPais,array(1,2)) || $totalRowsAnticipo > 0) && !($idFactura > 0)) {
		$objResponse->script("
		byId('trFormaDePago').style.display = '';");
		
		$objResponse->loadCommands(cargaLstTipoPago("","7"));
		$objResponse->call(asignarTipoPago,"7");
		
		while ($rowAnticipo = mysql_fetch_assoc($rsAnticipo)) {
			$idAnticipo = $rowAnticipo['idAnticipo'];
			
			// VERIFICA SI EL CLIENTE TIENE UN ANTICIPO CON CASH BACK / BONO DEALER, TRADE-IN, BONO SUPLIDOR, PND AUN DISPONIBLE PARA ASIGNAR
			// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
			$queryConceptoFormaPago = sprintf("SELECT *
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago)
			WHERE cxc_ant.idAnticipo = %s
				AND forma_pago.idFormaPago = 11
				AND concepto_forma_pago.id_concepto IN (1,2,6,7,8,9);",
				valTpDato($idAnticipo, "int"));
			$rsConceptoFormaPago = mysql_query($queryConceptoFormaPago);
			if (!$rsConceptoFormaPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsConceptoFormaPago = mysql_num_rows($rsConceptoFormaPago);
			while ($rowConceptoFormaPago = mysql_fetch_assoc($rsConceptoFormaPago)) {
				$arrayConceptoFormaPago[] = $rowConceptoFormaPago['descripcion'];
			}
			
			// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
			if (in_array($rowAnticipo['id_concepto'],array(1,6,7,8,9))) {
				$Result1 = insertarItemMetodoPago($contFila2, 7, $idAnticipo, $rowAnticipo['numeroAnticipo'], "", "", "", "", "", "", "", "", "", "", $rowAnticipo['saldo_anticipo']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila2 = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObjPiePago[] = $contFila2;
				}
			}
		}
		
		if ($totalRowsAnticipo > 0) {
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(
			"$('#trFormaDePago').before('".
			"<tr align=\"left\" id=\"trMsj\">".
				"<td colspan=\"3\">".
					"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo\" width=\"100%\">".
					"<tr>".
						"<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\"/></td>".
						"<td align=\"center\">"."El cliente tiene anticipo(s)".((count($arrayConceptoFormaPago) > 0) ? " con \"".implode(", ",$arrayConceptoFormaPago)."\"" : "")." disponible(s)"."</td>".
					"</tr>".
					"</table>".
				"</td>".
			"</tr>');");
		}
	}
	
	// VERIFICA SI EXISTE ALGUN CREDITO DEL FINANCIMIENTO AL QUE PERTENECE LA FACTURA
	$queryFinanciamiento = sprintf("SELECT ped_financ_det.*,
		cxc_nc.*
	FROM an_pedido an_ped_vent
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
			LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
				LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
				INNER JOIN cj_cc_notacredito cxc_nc ON (ped_financ_det.id_notadecredito_cxc = cxc_nc.idNotaCredito)
	WHERE an_ped_vent.id_pedido = %s;",
		valTpDato($frmListaItemPedido['txtIdPedidoItems'], "int"));
	$rsFinanciamiento = mysql_query($queryFinanciamiento);
	if (!$rsFinanciamiento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFinanciamiento = mysql_num_rows($rsFinanciamiento);
	while ($rowFinanciamiento = mysql_fetch_assoc($rsFinanciamiento)) {
		$idNotaCredito = $rowFinanciamiento['id_notadecredito_cxc'];
		
		$objResponse->script("
		byId('trFormaDePago').style.display = '';");
		
		$Result1 = insertarItemMetodoPago($contFila2, 8, $idNotaCredito, $rowFinanciamiento['numeracion_nota_credito'], "", "", "", "", "", "", "", "", "", "", $rowFinanciamiento['saldoNotaCredito']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila2 = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObjPiePago[] = $contFila2;
		}
	}
	
	// DATOS DEL CLIENTE
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode(elimCaracter($rowCliente['direccion'],";")));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
	
	// DATOS DEL PEDIDO
	$objResponse->assign("txtIdEmpresa","value",$rowPedido['id_empresa']);
	$objResponse->assign("txtEmpresa","value",$rowPedido['nombre_empresa']);
	$objResponse->assign("txtIdPresupuesto","value",$rowPedido['id_presupuesto']);
	$objResponse->assign("txtNumeroPresupuesto","value",$rowPedido['numeracion_presupuesto']);
	$objResponse->assign("txtIdPedido","value",$rowPedido['id_pedido']);
	$objResponse->assign("txtNumeroPedido","value",$rowPedido['numeracion_pedido']);
	$objResponse->assign("txtFechaFactura","value",date(spanDateFormat));
	$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat, strtotime($fechaVencimiento)));
	$objResponse->assign("hddIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	$objResponse->call("selectedOption","lstTipoClave",3);
	$objResponse->script("byId('lstTipoClave').onchange = function(){ selectedOption(this.id,'".(3)."'); };");
	$objResponse->assign("hddIdClaveMovimiento","value",$rowPedido['id_clave_movimiento']);
	$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowPedido['clave'].") ".$rowPedido['descripcion_clave_movimiento']));
	$objResponse->loadCommands(cargaLstCreditoTradeIn($rowConfig207['valor']));
	$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
	
	$objResponse->assign("txtIdFacturaEditada","value",$idFactura);
	if ($idFactura > 0) {
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFactura = sprintf("SELECT *,
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
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
		$objDcto->tipoDocumento = "FA";
		$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $rowFactura['idDepartamentoOrigenFactura'];
		$objDcto->idDocumento = $rowFactura['idFactura'];
		$aVerDcto = $objDcto->verDocumento();
		
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
	
	$objResponse->assign("txtObservacion","value",utf8_encode($rowPedido['observaciones']));
	
	if (isset($frmListaItemPedido['cbxItm'])) {
		foreach($frmListaItemPedido['cbxItm'] as $indiceItm=>$valorItm) {	
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			
			$hddTpItm = $frmListaItemPedido['hddTpItm'.$valorItm]; // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
			$hddIdItm = $frmListaItemPedido['hddIdItm'.$valorItm]; // id_unidad_fisica, id_paquete_pedido, id_accesorio_pedido
			
			if (in_array($hddTpItm,array(1,2))) { // 1 = Por Paquete, 2 = Individual
				$Result1 = insertarItemAdicional($contFila, $hddIdItm, $hddTpItm, (($idPedidoFinanciamiento > 0) ? true : false));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObjPieDetalle[] = $contFila;
				}
			} else if ($hddTpItm == 3) { // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
				$Result1 = insertarItemUnidad($contFila, $hddIdItm, $hddTpItm, (($idPedidoFinanciamiento > 0) ? true : false));
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObjPieDetalle[] = $contFila;
				}
				
				// BUSCA LAS NOTAS DE DEBITO COMO BONO DE LA UNIDAD
				$queryNotaDebito = sprintf("SELECT cxc_nd.*,
					cliente.id AS id_cliente,
					CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
					CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
					
					(SELECT GROUP_CONCAT(motivo.id_motivo SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS id_motivo,
					
					(SELECT GROUP_CONCAT(motivo.descripcion SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo) AS descripcion_motivo
				FROM cj_cc_notadecargo cxc_nd
					INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
				WHERE cxc_nd.id_unidad_fisica_bono = %s;",
					valTpDato($hddIdItm, "int"));
				$rsNotaDebito = mysql_query($queryNotaDebito);
				if (!$rsNotaDebito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowNotaDebito = mysql_fetch_array($rsNotaDebito)) {
					$hddNumeroItmBono = $contFila;
					$hddIdNotaDebitoBono = $rowNotaDebito['idNotaCargo'];
					$hddIdUnidadFisicaBono = $rowNotaDebito['id_unidad_fisica_bono'];
					$hddIdAnticipoPagoBono = $rowNotaDebito['id_anticipo_bono'];
					$txtIdClienteBono = $rowNotaDebito['id_cliente'];
					$txtNombreClienteBono = $rowNotaDebito['nombre_cliente'];
					$txtIdMotivoBono = $rowNotaDebito['id_motivo'];
					$txtMotivoBono = $rowNotaDebito['descripcion_motivo'];
					$txtMontoBono = $rowNotaDebito['montoTotalNotaCargo'];
					
					$Result1 = insertarItemBono($contFila4, $hddNumeroItmBono, $hddIdNotaDebitoBono, $hddIdUnidadFisicaBono, $hddIdAnticipoPagoBono, $txtIdClienteBono, $txtNombreClienteBono, $txtIdMotivoBono, $txtMotivoBono, $txtMontoBono);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$contFila4 = $Result1[2];
						$objResponse->script($Result1[1]);
						$arrayObj4[] = $contFila4;
					}
				}
			} else if ($hddTpItm == 4) { // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
				// OPCIONALES
				$queryPedidoDet = sprintf("SELECT *
				FROM an_pedido_venta_detalle an_ped_vent_det
					INNER JOIN iv_articulos art ON (an_ped_vent_det.id_articulo = art.id_articulo)
				WHERE an_ped_vent_det.id_pedido_venta_detalle = %s
				ORDER BY an_ped_vent_det.id_pedido_venta_detalle ASC;",
					valTpDato($hddIdItm, "int"));
				$rsPedidoDet = mysql_query($queryPedidoDet);
				if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
					$Result1 = insertarItemArticulo($contFila, $rowPedidoDet['id_pedido_venta_detalle'], $rowPedidoDet['id_presupuesto_venta_detalle'], $idCliente, $rowPedidoDet['id_articulo'], $rowPedidoDet['id_casilla'], $rowPedidoDet['cantidad'], $rowPedidoDet['pendiente'], $rowPedidoDet['id_precio'], $rowPedidoDet['precio_unitario'], $rowPedidoDet['precio_sugerido'], "", "", $rowPedidoDet['monto_pagado'], $rowPedidoDet['id_iva'], $rowPedidoDet['id_condicion_pago'], $rowPedidoDet['id_condicion_mostrar'], $rowPedidoDet['id_condicion_mostrar_pendiente'], (($idPedidoFinanciamiento > 0) ? true : false), $hddIdItm, $hddTpItm);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$contFila = $Result1[2];
						$objResponse->script($Result1[1]);
						$arrayObjPieDetalle[] = $contFila;
					}
				}
			}
			
			$subtotalFact += $precioItm;
		}
	}
	
	$Result1 = buscarNumeroControl($idEmpresa, $rowPedido['id_clave_movimiento']);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$objResponse->assign("txtNumeroControlFactura","value",($Result1[1]));
	}
	
	$objResponse->script("
	byId('btnCancelarListaItemPedido').onclick = '';
	byId('imgCerrarDivFlotante1').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));");
	
	return $objResponse;
}

function insertarPago($frmListaPagos, $frmDetallePago, $frmDeposito, $frmLista, $frmDcto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (PAGOS DE LA FACTURA)
	$arrayObjPiePago = $frmListaPagos['cbxPiePago'];
	$contFila = $arrayObjPiePago[count($arrayObjPiePago)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS (DETALLE DEL DEPOSITO)
	$arrayObj3 = $frmDeposito['cbx3'];
	
	if (str_replace(",", "", $frmTotalDcto['txtTotalOrden']) < str_replace(",", "", $frmDetallePago['txtMontoPago'])) {
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Factura");
	}
	
    foreach ($arrayObjPiePago as $indicePiePago => $valorPiePago) {
		$hddIdPago = $frmListaPagos['hddIdPago'.$valorPiePago];
		$txtIdFormaPago = $frmListaPagos['txtIdFormaPago'.$valorPiePago];
		$txtIdNumeroDctoPago = $frmListaPagos['txtIdNumeroDctoPago'.$valorPiePago];
		
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
		$arrayObjPiePago[] = $contFila;
	}
	
	if ($idFormaPago == 3) { // 3 = Deposito
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObjPieDeposito = explode("|", $frmDeposito['hddObjDetallePagoDeposito']);
		
		$cadenaFormaPagoDeposito = "";
		$cadenaNroDocumentoDeposito = "";
		$cadenaBancoClienteDeposito = "";
		$cadenaNroCuentaDeposito = "";
		$cadenaMontoDeposito = "";
		foreach ($arrayObjPieDeposito as $indicePieDeposito => $valorPieDeposito) {
			if (isset($frmDeposito['txtIdFormaPagoDetalleDeposito'.$valorPieDeposito])) {
				$cadenaPosicionDeposito .= $contFila."|";
				$cadenaFormaPagoDeposito .= $frmDeposito['txtIdFormaPagoDetalleDeposito'.$valorPieDeposito]."|";		
				$cadenaNroDocumentoDeposito .= $frmDeposito['txtNumeroDocumentoDetalleDeposito'.$valorPieDeposito]."|";
				$cadenaBancoClienteDeposito .= $frmDeposito['txtIdBancoClienteDetalleDeposito'.$valorPieDeposito]."|";
				$cadenaNroCuentaDeposito .= $frmDeposito['txtNumeroCuentaDetalleDeposito'.$valorPieDeposito]."|";
				$cadenaMontoDeposito .= $frmDeposito['txtMontoDetalleDeposito'.$valorPieDeposito]."|";
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
					$arrayObjPiePago[] = $contFila;
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
	
	$objResponse->assign("hddObjDetallePago","value",((count($arrayObjPiePago) > 0) ? implode("|",$arrayObjPiePago) : ""));
	
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
			"<td title=\"trItmDetalle:%s\"><input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>".
				"<input type=\"checkbox\" id=\"cbx3\" name=\"cbx3[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMontoDetalleDeposito%s\" name=\"txtMontoDetalleDeposito%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
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

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanClienteCxC;
	
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
		END) AS descripcion_tipo_cuenta_cliente
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id) %s", $sqlBusq);
	
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
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$valCadBusq[2]."', '".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista2","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "56%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "14%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transacción");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['modulo']) {
			case "CC" :
				$imgDctoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>";
				$descripcionModulo = "Cuentas por Cobrar";
				break;
			case "CP" :
				$imgDctoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>";
				$descripcionModulo = "Cuentas por Pagar";
				break;
			case "CJ" :
				$imgDctoModulo = "";
				$descripcionModulo = "Caja"; break;
			case "TE" :
				$imgDctoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>";
				$descripcionModulo = "Tesoreria";
				break;
			default : $imgDctoModulo = ""; $descripcionModulo = $row['modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgDctoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($descripcionModulo)."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".(($row['ingreso_egreso'] == "I") ? "Ingreso" : "Egreso")."</td>";
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

$xajax->register(XAJAX_FUNCTION,"asignarAnticipoBono");
$xajax->register(XAJAX_FUNCTION,"asignarArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularPagos");
$xajax->register(XAJAX_FUNCTION,"calcularPagosDeposito");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnticipoBono");
$xajax->register(XAJAX_FUNCTION,"cargaLstCreditoTradeIn");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuentaCompania");
$xajax->register(XAJAX_FUNCTION,"cargaLstIva");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"eliminarBono");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"formArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"formBono");
$xajax->register(XAJAX_FUNCTION,"formDeposito");
$xajax->register(XAJAX_FUNCTION,"formItemsPedido");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarBono");
$xajax->register(XAJAX_FUNCTION,"insertarItem");
$xajax->register(XAJAX_FUNCTION,"insertarPago");
$xajax->register(XAJAX_FUNCTION,"insertarPagoDeposito");
$xajax->register(XAJAX_FUNCTION,"listaAnticipoNotaCreditoChequeTransferencia");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");

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

function cargaLstMostrarItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$array = array(1 => "Incluir en el Precio", 2 => "Incluir en el Costo");
	$totalRows = count($array);
	
	$html .= "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	
	return $html;
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

function guardarNotaCargoCxC($frmNotaDebito) {
	$idAnticipoBono = $frmNotaDebito['idAnticipoBono'];
	$idUnidadFisicaBono = $frmNotaDebito['idUnidadFisicaBono'];
	
	$idFactura = $frmNotaDebito['idFactura'];
	$idFacturaDetAccesorio = $frmNotaDebito['idFacturaDetAccesorio'];
	$idAccesorio = $frmNotaDebito['idAccesorio'];
	$idTipoComision = $frmNotaDebito['hddIdTipoComisionItm'];
	$porcComision = $frmNotaDebito['hddPorcentajeComisionItm'];
	$montoComision = $frmNotaDebito['hddMontoComisionItm'];
	
	$idEmpresa = $frmNotaDebito['txtIdEmpresa'];
	
	if ($idFacturaDetAccesorio > 0) {
		// BUSCA LOS DATOS DE LA FACTURA
		$queryFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowFacturas = mysql_num_rows($rsFactura);
		$rowFactura = mysql_fetch_assoc($rsFactura);
		
		if ($idTipoComision > 0) {
			// BUSCA LOS DATOS DEL ADICIONAL
			$queryFacturaDetAccesorio = sprintf("SELECT *
			FROM cj_cc_factura_detalle_accesorios fact_det_acc
				INNER JOIN an_accesorio acc ON (fact_det_acc.id_accesorio = acc.id_accesorio)
			WHERE id_factura_detalle_accesorios = %s;",
				valTpDato($idFacturaDetAccesorio, "int"));
			$rsFacturaDetAccesorio = mysql_query($queryFacturaDetAccesorio);
			if (!$rsFacturaDetAccesorio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowFacturaDetAccesorio = mysql_num_rows($rsFacturaDetAccesorio);
			$rowFacturaDetAccesorio = mysql_fetch_assoc($rsFacturaDetAccesorio);
			
			$tipoComision = ($idTipoComision == 1) ? $porcComision."%" : cAbrevMoneda.$montoComision;
			$txtFechaRegistro = $rowFactura['fechaRegistroFactura'];
			$idModulo = $rowFactura['idDepartamentoOrigenFactura'];
			$precioUnitario = ($idTipoComision == 1) ? ($porcComision * $rowFacturaDetAccesorio['precio_unitario']) / 100 : $montoComision;
			$txtObservacion = "NOTA DE CARGO POR COMISION DE ".$tipoComision." DEL ADICIONAL (".$rowFacturaDetAccesorio['nom_accesorio'].") PERTENECIENTE A LA FACTURA NRO. ".$rowFactura['numeroFactura'];
		} else {
			// BUSCA LOS DATOS DEL ADICIONAL
			$queryFacturaDetAccesorio = sprintf("SELECT * FROM an_accesorio acc WHERE acc.id_accesorio = %s;",
				valTpDato($idAccesorio, "int"));
			$rsFacturaDetAccesorio = mysql_query($queryFacturaDetAccesorio);
			if (!$rsFacturaDetAccesorio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowFacturaDetAccesorio = mysql_num_rows($rsFacturaDetAccesorio);
			$rowFacturaDetAccesorio = mysql_fetch_assoc($rsFacturaDetAccesorio);
			
			$txtFechaRegistro = $rowFactura['fechaRegistroFactura'];
			$idModulo = $rowFactura['idDepartamentoOrigenFactura'];
			$precioUnitario = $montoComision;
			$txtObservacion = "NOTA DE CARGO AUTOMATICA DEL ADICIONAL (".$rowFacturaDetAccesorio['nom_accesorio'].") PERTENECIENTE A LA FACTURA NRO. ".$rowFactura['numeroFactura'];
		}
	} else if ($idAnticipoBono > 0 || $idUnidadFisicaBono > 0) {
		$txtFechaRegistro = date(spanDateFormat);
		$idModulo = $frmNotaDebito['idModulo'];
		$precioUnitario = $frmNotaDebito['txtSubTotalNotaCargo'];
		$txtObservacion = "NOTA DE CARGO POR BONO";
	}
	
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
		valTpDato(24, "int"), // 24 = Nota de Cargo CxC
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
	
	$idCliente = $frmNotaDebito['txtIdCliente'];
	$idMotivo = $frmNotaDebito['txtIdMotivoCxC'];
	$txtFechaRegistro = date(spanDateFormat,strtotime($txtFechaRegistro));
	$idModulo = $idModulo;
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
	$txtObservacion = $txtObservacion;
	
	// INSERTA LA NOTA DE CREDITO
	$insertSQL = sprintf("INSERT INTO cj_cc_notadecargo (id_empresa, idCliente, numeroNotaCargo, numeroControlNotaCargo, fechaRegistroNotaCargo, fechaVencimientoNotaCargo, idDepartamentoOrigenNotaCargo, referencia_nota_cargo, id_anticipo_bono, id_unidad_fisica_bono, tipoNotaCargo, diasDeCreditoNotaCargo, estadoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, observacionNotaCargo, subtotalNotaCargo, fletesNotaCargo, interesesNotaCargo, descuentoNotaCargo, baseImponibleNotaCargo, porcentajeIvaNotaCargo, calculoIvaNotaCargo, base_imponible_iva_lujo, porcentaje_iva_lujo, ivaLujoNotaCargo, montoExentoNotaCargo, montoExoneradoNotaCargo, aplicaLibros, id_empleado_creador)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idCliente, "int"),
		valTpDato($numeroActual, "text"),
		valTpDato($numeroActualControl, "text"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistro)), "date"),
		valTpDato(date("Y-m-d", strtotime($txtFechaVencimiento)), "date"),
		valTpDato($idModulo, "int"),
		valTpDato(1, "int"), // 0 = Cheque Devuelto, 1 = Otros
		valTpDato($idAnticipoBono, "int"),
		valTpDato($idUnidadFisicaBono, "int"),
		valTpDato($lstTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($txtDiasCreditoCliente, "int"),
		valTpDato("0", "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato($txtObservacion, "text"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
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
	VALUES (%s, %s, %s, %s);",
		valTpDato("ND", "text"),
		valTpDato($idNotaCargo, "int"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistro)), "date"),
		valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	return array(true, $idNotaCargo, $idModulo);
}

function guardarNotaCreditoCxC($idFactura) {
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT cxc_fact.*,
		(SELECT clave_mov.id_clave_movimiento_contra FROM pg_clave_movimiento clave_mov
		WHERE clave_mov.id_clave_movimiento = cxc_fact.id_clave_movimiento) AS id_clave_movimiento_contra
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFact = mysql_fetch_array($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura'];
	$idClaveMovimiento = $rowFact['id_clave_movimiento_contra'];
	
	// INSERTA LOS DATOS DE LA NOTA DE CREDITO
	$updateSQL = sprintf("INSERT INTO cj_cc_notacredito (id_empresa, idCliente, numeracion_nota_credito, numeroControl, fechaNotaCredito, idDepartamentoNotaCredito, id_empleado_vendedor, id_clave_movimiento, idDocumento, tipoDocumento, estadoNotaCredito, montoNetoNotaCredito, saldoNotaCredito, observacionesNotaCredito, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, baseimponibleNotaCredito, porcentajeIvaNotaCredito, ivaNotaCredito, base_imponible_iva_lujo, porcentajeIvaDeLujoNotaCredito, ivaLujoNotaCredito, montoExentoCredito, montoExoneradoCredito, estatus_nota_credito, aplicaLibros, id_empleado_creador)
	SELECT
		cxc_fa.id_empresa,
		cxc_fa.idCliente,
		cxc_fa.numeroFactura,
		cxc_fa.numeroControl,
		cxc_fa.fechaRegistroFactura,
		cxc_fa.idDepartamentoOrigenFactura,
		cxc_fa.idVendedor,
		%s,
		cxc_fa.idFactura,
		'FA',
		%s,
		cxc_fa.montoTotalFactura,
		cxc_fa.montoTotalFactura,
		%s,
		cxc_fa.subtotalFactura,
		cxc_fa.porcentaje_descuento,
		cxc_fa.descuentoFactura,
		cxc_fa.baseImponible,
		cxc_fa.porcentajeIvaFactura,
		cxc_fa.calculoIvaFactura,
		cxc_fa.base_imponible_iva_lujo,
		cxc_fa.porcentajeIvaDeLujoFactura,
		cxc_fa.calculoIvaDeLujoFactura,
		cxc_fa.montoExento,
		cxc_fa.montoExonerado,
		%s,
		%s,
		%s
	FROM cj_cc_encabezadofactura cxc_fa
	WHERE cxc_fa.idFactura = %s;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato("EDICION DE LA FACTURA", "text"),
		valTpDato(2, "int"), // 1 = Aprobada, 2 = Aplicada
		valTpDato(0, "int"), // 0 = No, 1 = Si
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idNotaCredito,
		$idModulo,
		"NOTA_CREDITO");
	
	// VERIFICA SI LA FACTURA FUE AGREGADA POR VENTA DE VEHICULO O POR CUENTAS POR COBRAR
	$queryFacturaVehiculo = sprintf("SELECT
		cxc_fact.idFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroPedido,
		uni_fis.id_unidad_fisica,
		uni_fis.id_uni_bas
	FROM an_pedido an_ped_vent
		INNER JOIN an_factura_venta cxc_fact ON (an_ped_vent.id_pedido = cxc_fact.numeroPedido)
		INNER JOIN cj_cc_cliente cliente ON (an_ped_vent.id_cliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
	WHERE cxc_fact.idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFacturaVehiculo = mysql_query($queryFacturaVehiculo);
	if (!$rsFacturaVehiculo) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsFacturaVehiculo = mysql_num_rows($rsFacturaVehiculo);
	$rowFacturaVehiculo = mysql_fetch_array($rsFacturaVehiculo);
	
	if ($totalRowsFacturaVehiculo > 0) { // FUE AGREGADA POR VENTAS DE VEHÍCULOS
		// INSERTA LOS VEHICULOS DEVUELTOS
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo (id_nota_credito, id_unidad_fisica, id_condicion_unidad, precio_unitario, costo_compra, id_iva, iva, precio_compra, costo_agregado, costo_depreciado, costo_trade_in)
		SELECT
			%s,
			id_unidad_fisica,
			id_condicion_unidad,
			precio_unitario,
			costo_compra,
			id_iva,
			iva,
			precio_compra,
			costo_agregado,
			costo_depreciado,
			costo_trade_in
		FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
		WHERE cxc_fact_det_vehic.id_factura = %s;",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCreditoDetalleVehiculo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// BUSCA LOS VEHICULOS EN EL DETALLE
		$queryNCDetVehic = sprintf("SELECT * FROM cj_cc_nota_credito_detalle_vehiculo WHERE id_nota_credito = %s;",
			valTpDato($idNotaCredito, "int"));
		$rsNCDetVehic = mysql_query($queryNCDetVehic);
		if (!$rsNCDetVehic) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNCDetVehic = mysql_num_rows($rsNCDetVehic);
		while ($rowNCDetVehic = mysql_fetch_array($rsNCDetVehic)) {
			// INSERTA LOS IMPUESTOS DE LOS VEHICULOS DEVUELTOS
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_vehiculo_impuesto (id_nota_credito_detalle_vehiculo, id_impuesto, impuesto)
			SELECT
				%s,
				cxc_fact_det_vehic_impuesto.id_impuesto,
				cxc_fact_det_vehic_impuesto.impuesto
			FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
				INNER JOIN cj_cc_factura_detalle_vehiculo_impuesto cxc_fact_det_vehic_impuesto ON (cxc_fact_det_vehic.id_factura_detalle_vehiculo = cxc_fact_det_vehic_impuesto.id_factura_detalle_vehiculo)
			WHERE cxc_fact_det_vehic.id_unidad_fisica = %s
				AND cxc_fact_det_vehic.id_factura = %s;",
				valTpDato($rowNCDetVehic['id_nota_credito_detalle_vehiculo'], "int"),
				valTpDato($rowNCDetVehic['id_unidad_fisica'], "int"),
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		
		// INSERTA LOS ACCESORIOS DEVUELTOS
		$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios (id_nota_credito, id_accesorio, id_tipo_accesorio, cantidad, precio_unitario, costo_compra, id_iva, iva, monto_pagado, id_condicion_pago, id_condicion_mostrar, monto_pendiente, id_condicion_mostrar_pendiente, tipo_accesorio)
		SELECT
			%s,
			id_accesorio,
			id_tipo_accesorio,
			cantidad,
			precio_unitario,
			costo_compra,
			id_iva,
			iva,
			monto_pagado,
			id_condicion_pago,
			id_condicion_mostrar,
			monto_pendiente,
			id_condicion_mostrar_pendiente,
			tipo_accesorio
		FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		WHERE cxc_fact_det_acc.id_factura = %s;",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCreditoDetalleAccesorio = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// BUSCA LOS ACCESORIOS EN EL DETALLE
		$queryNCDetAcc = sprintf("SELECT * FROM cj_cc_nota_credito_detalle_accesorios WHERE id_nota_credito = %s;",
			valTpDato($idNotaCredito, "int"));
		$rsNCDetAcc = mysql_query($queryNCDetAcc);
		if (!$rsNCDetAcc) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNCDetAcc = mysql_num_rows($rsNCDetAcc);
		while ($rowNCDetAcc = mysql_fetch_array($rsNCDetAcc)) {
			// INSERTA LOS IMPUESTOS DE LOS ACCESORIOS DEVUELTOS
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_accesorios_impuesto (id_nota_credito_detalle_accesorios, id_impuesto, impuesto)
			SELECT
				%s,
				cxc_fact_det_acc_impuesto.id_impuesto,
				cxc_fact_det_acc_impuesto.impuesto
			FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
				INNER JOIN cj_cc_factura_detalle_accesorios_impuesto cxc_fact_det_acc_impuesto ON (cxc_fact_det_acc.id_factura_detalle_accesorios = cxc_fact_det_acc_impuesto.id_factura_detalle_accesorios)
			WHERE cxc_fact_det_acc.id_accesorio = %s
				AND cxc_fact_det_acc.id_factura = %s;",
				valTpDato($rowNCDetAcc['id_nota_credito_detalle_accesorios'], "int"),
				valTpDato($rowNCDetAcc['id_accesorio'], "int"),
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		
		// BUSCA SI LA FACTURA A DEVOLVER TIENE UNA UNIDAD
		$queryFADetVehic = sprintf("SELECT * FROM cj_cc_factura_detalle_vehiculo WHERE id_factura = %s;",
			valTpDato($idFactura, "int"));
		$rsFADetVehic = mysql_query($queryFADetVehic);
		if (!$rsFADetVehic) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsFADetVehic = mysql_num_rows($rsFADetVehic);
		while ($rowFADetVehic = mysql_fetch_array($rsFADetVehic)) {
			$idUnidadFisica = $rowFADetVehic['id_unidad_fisica'];
			
			// REGISTRA EL MOVIMIENTO DE LA UNIDAD
			$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
			SELECT %s, idUnidadBasica, idUnidadFisica, %s, %s, %s, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, %s, %s FROM an_kardex kardex
			WHERE kardex.id_documento = %s
				AND kardex.idUnidadFisica = %s
				AND kardex.tipoMovimiento = 3;",
				valTpDato($idNotaCredito, "int"),
				valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($idClaveMovimiento, "int"),
				valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
				valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
				valTpDato("DATE_ADD(fechaMovimiento, INTERVAL 1 SECOND)", "campo"),
				valTpDato($idFactura, "int"),
				valTpDato($idUnidadFisica, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA EL ESTADO DE VENTA DEL VEHÍCULO
			$updateSQL = sprintf("UPDATE an_unidad_fisica SET
				estado_venta = 'DISPONIBLE',
				fecha_pago_venta = '0000-00-00'
			WHERE id_unidad_fisica = %s;",
				valTpDato($idUnidadFisica, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA EN VEHICULOS
		$updateSQL = sprintf("UPDATE an_factura_venta SET
			anulada = 'SI'
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ACTUALIZA LOS DATOS DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_pedido SET
			estado_pedido = 4
		WHERE id_pedido = %s;",
			valTpDato($rowFact['numeroPedido'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	SELECT %s, %s, cxc_fa.fechaRegistroFactura, %s FROM cj_cc_encabezadofactura cxc_fa
	WHERE cxc_fa.idFactura = %s;",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato("3", "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
		valTpDato($idFactura, "int"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// CALCULO DE LAS COMISIONES
	$Result1 = devolverComision($idNotaCredito);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
	
	// VERIFICA SI LA FACTURA TIENE COMO PAGO UN ANTICIPO CON CASH BACK / BONO DEALER, TRADE-IN, BONO SUPLIDOR, PND, CANCELADO O SIN CANCELAR
	// 1 = Cash Back / Bono Dealer, 2 = Trade In, 6 = Bono Suplidor, 7 = PND Seguro, 8 = PND Garantia Extendida, 9 = PND GAP
	$queryAnticipo = sprintf("SELECT DISTINCT cxc_pago_an.*
	FROM cj_cc_anticipo cxc_ant
		INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
		LEFT JOIN formapagos forma_pago ON (concepto_forma_pago.id_formapago = forma_pago.idFormaPago),
		an_pagos cxc_pago_an
	WHERE cxc_pago_an.id_factura = %s
		AND ((((cxc_pago_an.numeroDocumento = cxc_ant.idAnticipo
						AND (cxc_pago.id_concepto IN (2)
							OR cxc_pago.id_concepto IN (1,6,7,8,9)
							OR cxc_ant.totalPagadoAnticipo <= cxc_ant.montoNetoAnticipo))
					OR cxc_pago_an.estatus = 2)
				AND cxc_pago_an.formaPago = 7)
			OR cxc_pago_an.formaPago = 8);",
		valTpDato($idFactura, "int"));
	$rsAnticipo = mysql_query($queryAnticipo);
	if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
	if ($totalRowsAnticipo > 0) {
		while($rowAnticipo = mysql_fetch_assoc($rsAnticipo)) {
			$txtIdFormaPago = $rowAnticipo['formaPago'];
			
			// ANULA EL PAGO
			$updateSQL = sprintf("UPDATE an_pagos SET
				estatus = NULL,
				fecha_anulado = %s,
				id_empleado_anulado = %s
			WHERE idPago = %s;",
				valTpDato("NOW()", "campo"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowAnticipo['idPago'], "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			if ($txtIdFormaPago == 7) { // 7 = Anticipo
				$idAnticipo = $rowAnticipo['numeroDocumento'];
				
				$objDcto = new Documento;
				$Result1 = $objDcto->actualizarAnticipo($idAnticipo);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
				
				// VERIFICA SI ALGUN ANTICIPO DE TRADE IN TIENE ALGUN DOCUMENTO ASOCIADO QUE AFECTE AL COSTO DE LA UNIDAD VENDIDA
				$queryTradeInCxC = sprintf("SELECT
					(CASE 
						WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
							'ND_CXC'
						WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
							'NC_CXC'
					END) AS tipo_documento,
					(CASE 
						WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
							cxc_nd.idNotaCargo
						WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
							cxc_nc.idNotaCredito
					END) AS id_documento,
					(CASE 
						WHEN (tradein_cxc.id_nota_cargo_cxc IS NOT NULL) THEN
							cxc_nd.montoTotalNotaCargo
						WHEN (tradein_cxc.id_nota_credito_cxc IS NOT NULL) THEN
							cxc_nc.montoNetoNotaCredito
					END) AS monto_total
				FROM an_tradein_cxc tradein_cxc
					LEFT JOIN cj_cc_notadecargo cxc_nd ON (tradein_cxc.id_nota_cargo_cxc = cxc_nd.idNotaCargo AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL)
					LEFT JOIN cj_cc_notacredito cxc_nc ON (tradein_cxc.id_nota_credito_cxc = cxc_nc.idNotaCredito AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)
				WHERE tradein_cxc.id_anticipo = %s
					AND tradein_cxc.estatus = 1;",
					valTpDato($idAnticipo, "int"));
				$rsTradeInCxC = mysql_query($queryTradeInCxC);
				if (!$rsTradeInCxC) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsTradeInCxC = mysql_num_rows($rsTradeInCxC);
				while($rowTradeInCxC = mysql_fetch_assoc($rsTradeInCxC)) {
					$tipoDocumento = $rowTradeInCxC['tipo_documento'];
					$idDocumento = $rowTradeInCxC['id_documento'];
					
					// ANULA EL DETALLE DEL AGREGADO
					if ($idDocumento > 0) {
						$contAgregado++;
						
						if ($tipoDocumento == 'ND_CXC') {
							$updateSQL = sprintf("UPDATE an_unidad_fisica_agregado SET
								estatus = NULL,
								fecha_anulado = %s,
								id_empleado_anulado = %s
							WHERE id_unidad_fisica = %s
								AND id_nota_cargo_cxc = %s
								AND estatus = 1;",
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($idUnidadFisica, "int"),
								valTpDato($idDocumento, "int"));
						} else if ($tipoDocumento == 'NC_CXC') {
							// ANULA EL PAGO
							$updateSQL = sprintf("UPDATE an_pagos SET
								estatus = NULL,
								fecha_anulado = %s,
								id_empleado_anulado = %s
							WHERE id_factura = %s
								AND numeroDocumento = %s
								AND formaPago IN (8);",
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($idFactura, "int"),
								valTpDato($idDocumento, "int"));
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							
							$idNotaCreditoAgregado = $idDocumento;
							
							$objDcto = new Documento;
							$Result1 = $objDcto->actualizarNotaCredito($idNotaCreditoAgregado);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
							
							$updateSQL = sprintf("UPDATE an_unidad_fisica_agregado SET
								estatus = NULL,
								fecha_anulado = %s,
								id_empleado_anulado = %s
							WHERE id_unidad_fisica = %s
								AND id_nota_credito_cxc = %s
								AND estatus = 1;",
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($idUnidadFisica, "int"),
								valTpDato($idDocumento, "int"));
						}
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					}
				}
			} else if ($txtIdFormaPago == 8) { // 8 = Nota de Crédito
				$idNotaCredito = $rowAnticipo['numeroDocumento'];
				
				$objDcto = new Documento;
				$Result1 = $objDcto->actualizarNotaCredito($idNotaCredito);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
				
			}
		}
		
		if ($contAgregado > 0) {
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
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarFactura($idFactura);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
	}
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFact = mysql_fetch_array($rsFact);
	
	if (in_array($rowFact['estadoFactura'],array(0,2))) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
		// BUSCA LOS DATOS DE LA NOTA DE CREDITO
		$queryNotaCredito = sprintf("SELECT cxc_nc.*,
			(CASE cxc_nc.estadoNotaCredito
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado (No Asignado)'
				WHEN 2 THEN 'Cancelado Parcial'
				WHEN 3 THEN 'Asignado'
			END) AS estado_nota_credito,
			motivo.descripcion AS descripcion_motivo
		FROM pg_motivo motivo
			RIGHT JOIN cj_cc_notacredito cxc_nc ON (motivo.id_motivo = cxc_nc.id_motivo)
		WHERE idNotaCredito = %s",
			valTpDato($idNotaCredito, "int"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
		
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
		if (!$rsAperturaCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$idApertura = $rowAperturaCaja['id'];
		$fechaRegistroPago = $rowAperturaCaja['fechaAperturaCaja'];
		
		if ($rowFact['estadoFactura'] == 0) { // 0 = No Cancelado
			if ($rowFact['saldoFactura'] == $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] > $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
		} else if ($rowFact['estadoFactura'] == 2) { // 2 = Parcialmente Cancelado
			if ($rowFact['saldoFactura'] == $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] > $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = $rowNotaCredito['saldoNotaCredito'];
			} else if ($rowFact['saldoFactura'] < $rowNotaCredito['saldoNotaCredito']) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = $rowFact['saldoFactura'];
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
		}
		
		$arrayObjPago = array();
		$arrayDetallePago = array(
			"idCajaPpal" => $idCajaPpal,
			"apertCajaPpal" => $apertCajaPpal,
			"idApertura" => $idApertura,
			"numeroActualFactura" => $rowFact['numeroFactura'],
			"fechaRegistroPago" => $fechaRegistroPago,
				//"idEncabezadoReciboPago" => $idEncabezadoReciboPago,
				//"idEncabezadoPago" => $idEncabezadoPago,
				//"cbxPosicionPago" => $cbxPosicionPago,
				//"hddIdPago" => $hddIdPago,
			"txtIdFormaPago" => 8, // 8 = Nota de Crédito
			"txtIdNumeroDctoPago" => $idNotaCredito,
				//"txtNumeroDctoPago" => $txtNumeroDctoPago,
				//"txtIdBancoCliente" => $txtIdBancoCliente,
				//"txtCuentaClientePago" => $txtCuentaClientePago,
				//"txtIdBancoCompania" => $txtIdBancoCompania,
				//"txtIdCuentaCompaniaPago" => $txtIdCuentaCompaniaPago,
				//"txtCuentaCompaniaPago" => txtCuentaCompaniaPago,
				//"txtFechaDeposito" => $txtFechaDeposito,
				//"txtTipoTarjeta" => $txtTipoTarjeta,
				//"hddObjDetalleDeposito" => $hddObjDetalleDeposito,
				//"hddObjDetalleDepositoFormaPago" => $hddObjDetalleDepositoFormaPago,
				//"hddObjDetalleDepositoBanco" => $hddObjDetalleDepositoBanco,
				//"hddObjDetalleDepositoNroCuenta" => $hddObjDetalleDepositoNroCuenta,
				//"hddObjDetalleDepositoNroCheque" => $hddObjDetalleDepositoNroCheque,
				//"hddObjDetalleDepositoMonto" => $hddObjDetalleDepositoMonto,
			"txtMonto" => $saldoNotaCred,
			"cbxCondicionMostrar" => $frmListaDctoPagado['cbxCondicionMostrar'.$valorPiePago],
			"lstSumarA" => $frmListaDctoPagado['cbxMostrarContado'.$valorPiePago]
		);
		
		$arrayObjPago[] = $arrayDetallePago;
		
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
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
		$idEncabezadoReciboPago = $Result1['idEncabezadoReciboPago'];
		
		$arrayIdReciboVentana[] = $idEncabezadoReciboPago;
		
	} else if ($rowFact['estadoFactura'] == 1) { // 1 = Cancelado
		// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
			anulada = 'SI'
		WHERE idFactura = %s;",
			valTpDato($idFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarNotaCredito($idNotaCredito);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return array(false, $Result1[1]); }
	}
	
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
											- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
													WHERE anticip.idCliente = cliente_emp.id_cliente
														AND anticip.id_empresa = cliente_emp.id_empresa
														AND anticip.estadoAnticipo IN (1,2)
														AND anticip.estatus = 1), 0)
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
							- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
							WHERE anticip.idCliente = cliente_emp.id_cliente
								AND anticip.id_empresa = cliente_emp.id_empresa
								AND anticip.estadoAnticipo IN (1,2)
								AND anticip.estatus = 1), 0)
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
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	return array(true, $idNotaCredito, $idModulo);
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

function insertarItemAdicional($contFila, $hddIdItm, $hddTpItm, $bloquearObj = false){
	$contFila++;
	
	if ($hddIdItm > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		if ($hddTpItm == 1) { // 1 = Por Paquete, 2 = Individual
			$queryPedidoDet = sprintf("SELECT 
				an_ped_vent.id_pedido,
				an_ped_vent.id_empresa,
				an_ped_vent.id_cliente,
				an_ped_vent.id_moneda,
				an_ped_vent.id_moneda_tasa_cambio,
				an_ped_vent.id_tasa_cambio,
				an_ped_vent.monto_tasa_cambio,
				an_ped_vent.fecha_tasa_cambio,
				paq_ped.id_paquete_pedido,
				acc.id_accesorio,
				(CASE paq_ped.iva_accesorio
					WHEN 1 THEN
						acc.nom_accesorio
					ELSE
						CONCAT(acc.nom_accesorio, ' (E)')
				END) AS nom_accesorio,
				paq_ped.id_tipo_accesorio,
				(CASE paq_ped.id_tipo_accesorio
					WHEN 1 THEN 'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
					WHEN 4 THEN 'Cargo'
				END) AS descripcion_tipo_accesorio,
				paq_ped.precio_accesorio,
				paq_ped.costo_accesorio,
				paq_ped.iva_accesorio,
				paq_ped.porcentaje_iva_accesorio,
				paq_ped.monto_pagado,
				paq_ped.id_condicion_pago,
				paq_ped.id_condicion_mostrar,
				paq_ped.monto_pendiente,
				paq_ped.id_condicion_mostrar_pendiente,
				paq_ped.estatus_paquete_pedido,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo,
				cliente.id AS id_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				acc.id_tipo_comision,
				acc.porcentaje_comision,
				acc.monto_comision,
				motivo_cargo.id_motivo AS id_motivo_cargo,
				motivo_cargo.descripcion AS descripcion_motivo_cargo
			FROM an_pedido an_ped_vent
				INNER JOIN an_paquete_pedido paq_ped ON (an_ped_vent.id_pedido = paq_ped.id_pedido)
					INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.Id_acc_paq)
						INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
							LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
							LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
							LEFT JOIN pg_motivo motivo_cargo ON (acc.id_motivo_cargo = motivo_cargo.id_motivo)
			WHERE paq_ped.id_paquete_pedido = %s;", 
				valTpDato($hddIdItm, "int"));
		} else if ($hddTpItm == 2) { // 1 = Por Paquete, 2 = Individual
			$queryPedidoDet = sprintf("SELECT 
				an_ped_vent.id_pedido,
				an_ped_vent.id_empresa,
				an_ped_vent.id_cliente,
				an_ped_vent.id_moneda,
				an_ped_vent.id_moneda_tasa_cambio,
				an_ped_vent.id_tasa_cambio,
				an_ped_vent.monto_tasa_cambio,
				an_ped_vent.fecha_tasa_cambio,
				acc_ped.id_accesorio_pedido,
				acc.id_accesorio,
				(CASE acc_ped.iva_accesorio
					WHEN 1 THEN
						acc.nom_accesorio
					ELSE
						CONCAT(acc.nom_accesorio, ' (E)')
				END) AS nom_accesorio,
				acc_ped.id_tipo_accesorio,
				(CASE acc_ped.id_tipo_accesorio
					WHEN 1 THEN 'Adicional'
					WHEN 2 THEN 'Accesorio'
					WHEN 3 THEN 'Contrato'
					WHEN 4 THEN 'Cargo'
				END) AS descripcion_tipo_accesorio,
				acc_ped.precio_accesorio,
				acc_ped.costo_accesorio,
				acc_ped.iva_accesorio,
				acc_ped.porcentaje_iva_accesorio,
				acc_ped.monto_pagado,
				acc_ped.id_condicion_pago,
				acc_ped.id_condicion_mostrar,
				acc_ped.monto_pendiente,
				acc_ped.id_condicion_mostrar_pendiente,
				acc_ped.estatus_accesorio_pedido,
				motivo.id_motivo,
				motivo.descripcion AS descripcion_motivo,
				cliente.id AS id_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				acc.id_tipo_comision,
				acc.porcentaje_comision,
				acc.monto_comision,
				motivo_cargo.id_motivo AS id_motivo_cargo,
				motivo_cargo.descripcion AS descripcion_motivo_cargo
			FROM an_pedido an_ped_vent
				INNER JOIN an_accesorio_pedido acc_ped ON (an_ped_vent.id_pedido = acc_ped.id_pedido)
					INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
						LEFT JOIN cj_cc_cliente cliente ON (acc.id_cliente = cliente.id)
						LEFT JOIN pg_motivo motivo ON (acc.id_motivo = motivo.id_motivo)
						LEFT JOIN pg_motivo motivo_cargo ON (acc.id_motivo_cargo = motivo_cargo.id_motivo)
			WHERE acc_ped.id_accesorio_pedido = %s;", 
				valTpDato($hddIdItm, "int"));
		}
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$txtTasaCambio = (!in_array($rowPedidoDet['monto_tasa_cambio'],array("",0))) ? $rowPedidoDet['monto_tasa_cambio'] : 1;
		$hddIdAccesorioItm = $rowPedidoDet['id_accesorio'];
		$hddTipoAdicionalItm = $rowPedidoDet['id_tipo_accesorio'];
		if ((in_array(idArrayPais,array(1,2)) && $totalRowsPedidoDet == 0) 
		|| ($rowPedidoDet['id_condicion_pago'] == 1 && $totalRowsPedidoDet > 0 && $rowPedidoDet['monto_pagado'] > 0)) {
			$checkedCondicionItm = "checked=\"checked\"";
		}
		$displayCondicionItm = ($bloquearObj == true) ? "style=\"display:none\"" : "";
		$classNamePrecioPagadoItm = ($bloquearObj == true) ? "class=\"inputCompleto\"" : "class=\"inputCompletoHabilitado\"";
		$readOnlyPrecioPagadoItm = ($bloquearObj == true) ? "readonly=\"readonly\"" : "";
		$divCodigoItm = "";
		$divDescripcionItm = "<div>".utf8_encode($rowPedidoDet['nom_accesorio'])."</div>";
			if (!in_array(idArrayPais,array(1)) && in_array($hddTipoAdicionalItm,array(1))) {
				$divDescripcionItm .= sprintf(
				"<div>".
					"<div id=\"divCondicionItm%s\" class=\"checkbox-label\"><label %s><input type=\"checkbox\" id=\"cbxCondicionItm%s\" name=\"cbxCondicionItm%s\" %s value=\"1\"/>Pagado</label></div>".
					
					"<div id=\"divPrecioPagadoItm%s\">Monto Pagado: <input type=\"text\" id=\"txtPrecioPagadoItm%s\" name=\"txtPrecioPagadoItm%s\" class=\"inputHabilitado\" onkeypress=\"return validarSoloNumerosReales(event);\" size=\"12\" style=\"text-align:right;\" value=\"%s\"/></div>".
					
					"<div>%s</div>".
					
					"<div id=\"%s\">Monto Restante: %s</div>".
				"</div>",
					$contFila, $displayCondicionItm,
						$contFila, $contFila, $checkedCondicionItm,
					
					$contFila,$contFila, $contFila, number_format($rowPedidoDet['monto_pagado'], 2, ".", ","),
					
					cargaLstMostrarItm("lstMostrarItm".$contFila, $rowPedidoDet['id_condicion_mostrar']),
					
					("divMostrarPendienteItm".$contFila), cargaLstMostrarItm("lstMostrarPendienteItm".$contFila, $rowPedidoDet['id_condicion_mostrar_pendiente']));
			}
		$txtPrecioItm = $rowPedidoDet['precio_accesorio'];
		$hddCostoItm = $rowPedidoDet['costo_accesorio'];
		
		$htmlContrato = "";
		if (in_array($hddTipoAdicionalItm,array(3,4))) { // 1 = Adicional, 2 = Accesorio, 3 = Contrato, 4 = Cargo
			$htmlContrato = "<div>";
				$htmlContrato .= (strlen($rowPedidoDet['nombre_cliente']) > 0) ? "<div class=\"textoNegrita_10px\">".utf8_encode($rowPedidoDet['nombre_cliente'])." ".(($rowPedidoDet['id_motivo'] > 0) ? "(Comisión: ".(($rowPedidoDet['id_tipo_comision'] == 1) ? number_format($rowPedidoDet['porcentaje_comision'], 2, ".", ",")."%" : number_format($rowPedidoDet['monto_comision'], 2, ".", ",")).")" : "")."</div>" : "";
				$htmlContrato .= ($rowPedidoDet['id_motivo'] > 0) ? "<div class=\"textoNegrita_9px\">".$rowPedidoDet['id_motivo'].".- ".utf8_encode($rowPedidoDet['descripcion_motivo'])."</div>" : "";
				$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddIdClienteItm%s\" name=\"hddIdClienteItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdMotivoItm%s\" name=\"hddIdMotivoItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdTipoComisionItm%s\" name=\"hddIdTipoComisionItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddPorcentajeComisionItm%s\" name=\"hddPorcentajeComisionItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddMontoComisionItm%s\" name=\"hddMontoComisionItm%s\" value=\"%s\">",
					$contFila, $contFila, $rowPedidoDet['id_cliente'],
					$contFila, $contFila, $rowPedidoDet['id_motivo'],
					$contFila, $contFila, $rowPedidoDet['id_tipo_comision'],
					$contFila, $contFila, $rowPedidoDet['porcentaje_comision'],
					$contFila, $contFila, $rowPedidoDet['monto_comision']);
			$htmlContrato .= "</div>";
			$htmlContrato .= "<div>";
				$htmlContrato .= ($rowPedidoDet['id_motivo_cargo'] > 0) ? "<div class=\"textoNegrita_9px\">".$rowPedidoDet['id_motivo_cargo'].".- ".utf8_encode($rowPedidoDet['descripcion_motivo_cargo'])."</div>" : "";
				$htmlContrato .= sprintf("<input type=\"hidden\" id=\"hddIdMotivoCargoItm%s\" name=\"hddIdMotivoCargoItm%s\" value=\"%s\">",
					$contFila, $contFila, $rowPedidoDet['id_motivo_cargo']);
			$htmlContrato .= "</div>";
		}
		
		if ($rowPedidoDet['iva_accesorio'] == 1) {
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva;");
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
			$contIva = 0;
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
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
		}
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf(
	"$('#".((in_array($hddTipoAdicionalItm,array(3,4))) ? "trItmPieAdicionalOtro" : "trItmPieDetalle")."').before('".
		"<tr id=\"trItmDetalle_%s\" align=\"left\" height=\"24\">".
			"<td title=\"trItmDetalle_%s\">".
				"<input type=\"checkbox\" id=\"cbxItmAdicional\" name=\"cbxItmAdicional[]\" value=\"%s\">".
				"<input type=\"checkbox\" id=\"cbxPieDetalle\" name=\"cbxPieDetalle[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmDetalle_%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><div id=\"divCodigoItm%s\">%s</div></td>".
			"<td><div id=\"divDescripcionItm%s\">%s</div>%s</td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddMontoDescuentoItm%s\" name=\"hddMontoDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" value=\"%s\"/></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td style=\"display:none\"><input type=\"text\" id=\"txtTotalConImpuestoItm%s\" name=\"txtTotalConImpuestoItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAccesorioItm%s\" name=\"hddIdAccesorioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTotalDescuentoItm%s\" name=\"hddTotalDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoAdicionalItm%s\" name=\"hddTipoAdicionalItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('cbxCondicionItm%s').onclick = function() {
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		}
		byId('lstMostrarItm%s').onchange = function() {
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		}
		byId('lstMostrarPendienteItm%s').onchange = function() {
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		}
		byId('txtPrecioPagadoItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		}",
		$contFila,
			$contFila,
				$contFila,
				$contFila,
			$contFila, $contFila, 
			$contFila, $divCodigoItm, 
			$contFila, $divDescripcionItm, $htmlContrato,
			$contFila, $contFila, number_format(1, 2, ".", ","), 
			$contFila, $contFila, number_format($txtTasaCambio * $txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, number_format($txtTasaCambio * $hddMontoDescuentoItm, 2, ".", ","), 
				$contFila, $contFila, number_format($hddCostoItm, 2, ".", ","), 
			$contFila, $ivaUnidad, 
			$contFila, $contFila, number_format($txtTasaCambio * $txtPrecioItm, 2, ".", ","),
			$contFila, $contFila, number_format($txtTasaCambio * $txtPrecioItm, 2, ".", ","),
				$contFila, $contFila, $hddIdPedidoDet, 
				$contFila, $contFila, $hddIdAccesorioItm, 
				$contFila, $contFila, $hddIdItm, 
				$contFila, $contFila, $hddTpItm, // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
				$contFila, $contFila, number_format($txtTasaCambio * $hddTotalDescuentoItm, 2, ".", ","), 
				$contFila, $contFila, $hddTipoAdicionalItm,
		
		$contFila,
		$contFila,
		$contFila,
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemArticulo($contFila, $hddIdPedidoDet = "", $hddIdPresupuestoDet = "", $idCliente = "", $idArticulo = "", $idCasilla = "", $cantPedida = "", $cantPendiente = "", $hddIdPrecioItm = "", $precioUnitario = "", $precioSugerido = "", $costoUnitario = "", $abrevMonedaCostoUnitario = "", $txtPrecioPagadoItm = "", $idIva = "", $cbxCondicion = "", $lstMostrarItm = "", $lstMostrarPendienteItm = "", $bloquearObj = false, $hddIdItm = "", $hddTpItm = "") {
	$contFila++;
	
	$totalRowsPresupuestoDetalle = 0;
	if ($hddIdPresupuestoDet > 0 || $hddIdPedidoDet > 0) {
		$totalRowsPresupuestoDetalle = 1;
		
		if ($hddIdPedidoDet > 0) {
			$queryIdEmpresa = sprintf("SELECT an_ped_vent.id_empresa
			FROM an_pedido an_ped_vent
				INNER JOIN an_pedido_venta_detalle an_ped_vent_det ON (an_ped_vent.id_pedido = an_ped_vent_det.id_pedido_venta)
			WHERE an_ped_vent_det.id_pedido_venta_detalle = %s;",
				valTpDato($hddIdPedidoDet, "int"));
		} else if ($hddIdPresupuestoDet > 0) {
			$queryIdEmpresa = sprintf("SELECT pres_vent.id_empresa
			FROM an_presupuesto pres_vent
				INNER JOIN an_presupuesto_venta_detalle an_pres_vent_det ON (pres_vent.id_presupuesto = an_pres_vent_det.id_presupuesto_venta)
			WHERE an_pres_vent_det.id_presupuesto_venta_detalle = %s;",
				valTpDato($hddIdPresupuestoDet, "int"));
		}
		$rsEmpresa = mysql_query($queryIdEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = $rowEmpresa['id_empresa'];
		
		// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			return array(false, $ResultConfig12[1], $contFila);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
		// BUSCA EL ULTIMO COSTO DEL ARTICULO
		$queryArtCosto = sprintf("SELECT art_costo.*,
			moneda.abreviacion
		FROM iv_articulos_costos art_costo
			INNER JOIN pg_monedas moneda ON (art_costo.id_moneda = moneda.idmoneda)
		WHERE art_costo.id_articulo = %s
			AND art_costo.id_empresa = %s
		ORDER BY art_costo.fecha_registro DESC LIMIT 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
		
		$costoUnitarioDet = (in_array($ResultConfig12, array(1,3))) ? round($rowArtCosto['costo'],3) : round($rowArtCosto['costo_promedio'],3);
		$abrevMonedaCostoUnitario = $rowArtCosto['abreviacion'];
	}
	
	$costoUnitario = ($costoUnitario == "" && $totalRowsPresupuestoDetalle > 0) ? $costoUnitarioDet : $costoUnitario;
	if ((in_array(idArrayPais,array(1,2)) && $totalRowsPresupuestoDetalle == 0) 
	|| ($cbxCondicion == 1 && $totalRowsPresupuestoDetalle > 0 && $txtPrecioPagadoItm > 0)) {
		$checkedCondicionItm = "checked=\"checked\"";
	}
	$displayCondicionItm = ($bloquearObj == true) ? "style=\"display:none\"" : "";
	$classNamePrecioPagadoItm = ($bloquearObj == true) ? "class=\"inputInicial\"" : "class=\"inputHabilitado\"";
	$readOnlyPrecioPagadoItm = ($bloquearObj == true) ? "readonly=\"readonly\"" : "";
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	if ($idCasilla > 0) {
		// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
		$queryUbicacion = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
			valTpDato($idCasilla, "int"));
		$rsUbicacion = mysql_query($queryUbicacion);
		if (!$rsUbicacion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsUbicacion = mysql_num_rows($rsUbicacion);
		$rowUbicacion = mysql_fetch_assoc($rsUbicacion);
	}
	
	$ubicacion = $rowUbicacion['descripcion_almacen']." ".$rowUbicacion['ubicacion'];
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
	FROM pg_iva iva
		INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
	WHERE art_impuesto.id_articulo = %s
		AND iva.tipo IN (6,9,2)
		AND art_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
											WHERE cliente_imp_exento.id_cliente = %s);", 
		valTpDato($idArticulo, "int"), 
		valTpDato($idCliente, "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contIva = 0;
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
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
	
	// BUSCA LOS PRECIOS DEL ARTICULO
	$queryArtPrecio = sprintf("SELECT
		art_precio.id_precio,
		precio.descripcion_precio,
		art_precio.precio,
		moneda.abreviacion,
		precio.tipo
	FROM iv_articulos_precios art_precio
		INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
		INNER JOIN pg_precios precio ON (art_precio.id_precio = precio.id_precio)
	WHERE art_precio.id_articulo = %s
		AND art_precio.id_empresa = %s
		AND precio.estatus IN (1)
	ORDER BY precio.porcentaje DESC;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArtPrecio = mysql_query($queryArtPrecio);
	if (!$rsArtPrecio) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$htmlPreciosArt = "<table width=\"360\">";
	while ($rowArtPrecio = mysql_fetch_assoc($rsArtPrecio)) {
		$styleTr = ($rowArtPrecio['id_precio'] == $hddIdPrecioItm) ? "style=\"font-weight:bold\"" : "";
		
		$htmlPreciosArt .= "<tr align=\"left\" ".$styleTr.">";
			$htmlPreciosArt .= "<td>".utf8_encode($rowArtPrecio['descripcion_precio'])."</td>";
			$htmlPreciosArt .= "<td align=\"right\">".utf8_encode($rowArtPrecio['abreviacion']).number_format($rowArtPrecio['precio'], 2, ".", ",")."</td>";
		$htmlPreciosArt .= "</tr>";
		
		if ($rowArtPrecio['id_precio'] == $hddIdPrecioItm && $rowArtPrecio['tipo'] == 0) { // PRECIO SOBRE COSTO
			$utilidad = ((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($costoUnitario);
		} else if ($rowArtPrecio['id_precio'] == $hddIdPrecioItm && $rowArtPrecio['tipo'] == 1) { // PRECIO SOBRE VENTA
			$utilidad = ((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($precioUnitario);
		}
		$utilidad = number_format($utilidad, 2, ".", ",")."%";
	}
	if (in_array($hddIdPrecioItm, array(6,7,12,13,18))) {
		$utilidad = "S/V: ".number_format(((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($precioUnitario), 2, ".", ",")."%";
		$utilidad .= " - ";
		$utilidad .= "S/C: ".number_format(((doubleval($precioUnitario) - doubleval($costoUnitario)) * 100) / doubleval($costoUnitario), 2, ".", ",")."%";
	}
	$htmlPreciosArt .= "<tr><td colspan=\"2\"><hr></td></tr>";
	$htmlPreciosArt .= "<tr align=\"left\">";
		$htmlPreciosArt .= "<td><b>"."Costo:"."</b></td>";
		$htmlPreciosArt .= "<td align=\"right\"><b>".utf8_encode($abrevMonedaCostoUnitario).number_format($costoUnitario, 2, ".", ",")."</b></td>";
	$htmlPreciosArt .= "</tr>";
	$htmlPreciosArt .= "<tr align=\"left\">";
		$htmlPreciosArt .= "<td><b>"."Utl. Bruta:"."</b></td>";
		$htmlPreciosArt .= "<td align=\"right\"><b>".$utilidad."</b></td>";
	$htmlPreciosArt .= "</tr>";
	$htmlPreciosArt .= "</table>";
	
	// CREA LA TABLA DE GASTOS
	if ($hddIdPedidoDet > 0) {
		$queryDetGasto = sprintf("SELECT * FROM an_pedido_venta_detalle_gastos
		WHERE id_pedido_venta_detalle = %s;",
			valTpDato($hddIdPedidoDet, "int"));
	} else if ($hddIdPresupuestoDet > 0) {
		$queryDetGasto = sprintf("SELECT * FROM an_presupuesto_venta_detalle_gastos
		WHERE id_presupuesto_venta_detalle = %s;",
			valTpDato($hddIdPresupuestoDet, "int"));
	}
	if (strlen($queryDetGasto) > 0) {
		$rsDetGasto = mysql_query($queryDetGasto);
		if (!$rsDetGasto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$contFilaObj = 0;
		$totalGastoArt = 0;
		$htmlGastoArtObj = "";
		while ($rowDetGasto = mysql_fetch_assoc($rsDetGasto)) {
			$contFilaObj++;
			
			$htmlGastoArtObj .= sprintf("<input type=\"hidden\" id=\"hddIdGastoArt:%s:%s\" name=\"hddIdGastoArt:%s:%s\" readonly=\"readonly\" value=\"%s\">",
				$contFila, $contFilaObj, $contFila, $contFilaObj, $rowDetGasto['id_gasto']);
			$htmlGastoArtObj .= sprintf("<input type=\"hidden\" id=\"txtMontoGastoArt:%s:%s\" name=\"txtMontoGastoArt:%s:%s\" readonly=\"readonly\" value=\"%s\"/>",
				$contFila, $contFilaObj, $contFila, $contFilaObj, $rowDetGasto['monto_gasto']);
			
			$totalGastoArt += $rowDetGasto['monto_gasto'];
		}
	}
	
	$htmlGastoArt = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$htmlGastoArt .= "<tr>";
		$htmlGastoArt .= "<td><a class=\"modalImg\" id=\"aGastoArt:".$contFila."\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\"/></a></td>";
		$htmlGastoArt .= "<td id=\"tdItmGastoObj:".$contFila."\" title=\"tdItmGastoObj:".$contFila."\">".$htmlGastoArtObj."</td>";
		$htmlGastoArt .= "<td width=\"100%\"><input type=\"text\" id=\"hddGastoItm".$contFila."\" name=\"hddGastoItm".$contFila."\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"".number_format($totalGastoArt, 2, ".", ",")."\"/></td>";
	$htmlGastoArt .= "</tr>";
	$htmlGastoArt .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf(
	"$('#trItmPieDetalle').before('".
		"<tr align=\"left\" id=\"trItmDetalle_%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmDetalle_%s\">".
				"<input type=\"checkbox\" id=\"cbxPieDetalle\" name=\"cbxPieDetalle[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmArticulo_%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><input type=\"text\" id=\"txtCodigoArtItm%s\" name=\"txtCodigoArtItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:left;\" value=\"%s\"></td>".
			"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
				"<div id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</div>".
				"%s".
				
				"<div %s>%s <input type=\"text\" id=\"hddPrecioSugeridoItm%s\" name=\"hddPrecioSugeridoItm%s\" class=\"inputSinFondo\" size=\"12\" readonly=\"readonly\" value=\"%s\"/></div>".
				
				"<div id=\"divCondicionItmArticulo%s\" class=\"checkbox-label\" %s><label><input type=\"checkbox\" id=\"cbxCondicionItmArticulo%s\" name=\"cbxCondicionItmArticulo%s\" %s value=\"1\"/>Pagado</label></div>".
				
				"<div id=\"divPrecioPagadoItmArticulo%s\">Monto Pagado: <input type=\"text\" id=\"txtPrecioPagadoItmArticulo%s\" name=\"txtPrecioPagadoItmArticulo%s\" %s onkeypress=\"return validarSoloNumerosReales(event);\" %s size=\"12\" style=\"text-align:right;\" value=\"%s\"/></div>".
			"</td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdCantPend:%s\" align=\"right\" style=\"display:none\">%s</td>".
			"<td align=\"right\" style=\"display:none\">%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" title=\"Costo Unitario\" value=\"%s\"/></td>".
			"<td id=\"tdIvaItm%s\">%s</td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td style=\"display:none\"><input type=\"text\" id=\"txtTotalConImpuestoItm%s\" name=\"txtTotalConImpuestoItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFactDet%s\" name=\"hddIdFactDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPresupuestoDet%s\" name=\"hddIdPresupuestoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPrecioItm%s\" name=\"hddIdPrecioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('cbxCondicionItmArticulo%s').onchange = function() {
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		}
		byId('txtPrecioPagadoItmArticulo%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmListaPagos'));
		}
		
		byId('aGastoArt:%s').onclick = function() { abrirDivFlotante1(this, 'tblLista', 'Gasto', '%s'); }
		
		byId('txtPrecioItm%s').onmouseover = function() { Tip('%s', TITLE, 'Lista de Precios'); }
		byId('txtPrecioItm%s').onmouseout = function() { UnTip(); }",
		$contFila, $clase,
			$contFila,
				$contFila,
			$contFila, $contFila,
			$contFila, $contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))),
				$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
				((in_array($ResultConfig12, array(1,2)) || !($hddIdArticuloCosto > 0)) ? "" : "<div id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</div>"),
				
				(($precioSugerido != 0) ? "" : "style=\"display:none\""), "<span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">Precio Sugerido:</span>", $contFila, $contFila, number_format($precioSugerido, 2, ".", ","),
				
				$contFila, $displayCondicionItm,
					$contFila, $contFila, $checkedCondicionItm,
				
				$contFila, $contFila, $contFila, $classNamePrecioPagadoItm, $readOnlyPrecioPagadoItm, number_format($txtPrecioPagadoItm, 2, ".", ","),
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$contFila, number_format($cantPendiente, 2, ".", ","),
			$htmlGastoArt,
			$contFila, $contFila, number_format($precioUnitario, 2, ".", ","),
				$contFila, $contFila, $costoUnitario,
			$contFila, $ivaUnidad,
			$contFila, $contFila, number_format((($cantPedida * $precioUnitario) + $totalGastoArt), 2, ".", ","),
			$contFila, $contFila, number_format((($cantPedida * $precioUnitario) + $totalGastoArt), 2, ".", ","),
				$contFila, $contFila, "",
				$contFila, $contFila, $hddIdPresupuestoDet,
				$contFila, $contFila, $hddIdPedidoDet,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $hddIdPrecioItm,
				$contFila, $contFila, $idCasilla,
				$contFila, $contFila, $hddIdItm, 
				$contFila, $contFila, $hddTpItm, // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
		
		$contFila,
		$contFila,
		
		$contFila, $contFila,
		
		$contFila, $htmlPreciosArt,
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemBono($contFila, $hddNumeroItmBono, $hddIdNotaDebitoBono, $hddIdUnidadFisicaBono, $hddIdAnticipoPagoBono, $txtIdClienteBono, $txtNombreClienteBono, $txtIdMotivoBono, $txtMotivoBono, $txtMontoBono) {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($hddIdAnticipoPagoBono > 0) {
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
			valTpDato($hddIdAnticipoPagoBono, "int"));
		$rsAnticipo = mysql_query($queryAnticipo);
		if (!$rsAnticipo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsAnticipo = mysql_num_rows($rsAnticipo);
		$rowAnticipo = mysql_fetch_array($rsAnticipo);
	}
	
	$htmlItmPie = sprintf(
	"$('#trItmPieBono%s').before('".
		"<tr id=\"trItmBono%s\">".
			"<td colspan=\"5\">".
			"<fieldset><legend class=\"legend\">%s</legend>".
				"<table border=\"0\">".
				"<tr>".
					"<td rowspan=\"3\" title=\"trItmBono%s\"><input type=\"checkbox\" id=\"cbxItmBono\" name=\"cbxItmBono[]\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxPieBono\" name=\"cbxPieBono[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td width=\"%s\">%s</td>".
					"<td></td>".
					"<td width=\"%s\"><input type=\"text\" id=\"txtMontoBono%s\" name=\"txtMontoBono%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
					"<td rowspan=\"3\"><button type=\"button\" id=\"btnEliminar%s\" title=\"Eliminar\"><img src=\"../img/iconos/delete.png\"/></button>".
						"<input type=\"hidden\" id=\"hddNumeroItmBono%s\" name=\"hddNumeroItmBono%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdNotaDebitoBono%s\" name=\"hddIdNotaDebitoBono%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdUnidadFisicaBono%s\" name=\"hddIdUnidadFisicaBono%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdClienteBono%s\" name=\"hddIdClienteBono%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdMotivoBono%s\" name=\"hddIdMotivoBono%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdAnticipoPagoBono%s\" name=\"hddIdAnticipoPagoBono%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdConceptoBono%s\" name=\"hddIdConceptoBono%s\" readonly=\"readonly\" value=\"%s\"/></td>".
				"</tr>".
				"<tr>".
					"<td>%s</td>".
					"<td>-</td>".
					"<td><input type=\"text\" id=\"txtMontoDescuentoBono%s\" name=\"txtMontoDescuentoBono%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
				"</tr>".
				"</tr>".
					"<td colspan=\"2\"></td>".
					"<td align=\"right\" class=\"trResaltarTotal\">%s</td>".
				"<tr>".
				"</table>".
			"</fieldset>".
			"</td>".
		"</tr>');
		
		byId('btnEliminar%s').onclick = function() {
			validarEliminarBono('%s');
		}",
	$hddNumeroItmBono,
		$hddNumeroItmBono."_".$contFila,
			(($rowAnticipo['montoNetoAnticipo'] > 0) ? "Anticipo Nro. ".$rowAnticipo['numeroAnticipo']." (".$rowAnticipo['descripcion'].")" : ""),
					$contFila, $contFila,
						$contFila,
					"75%", $txtIdClienteBono.".- ".$txtNombreClienteBono,
					"25%", $hddNumeroItmBono."_".$contFila, $hddNumeroItmBono."_".$contFila, number_format($txtMontoBono, 2, ".", ","),
					$hddNumeroItmBono."_".$contFila,
						$contFila, $contFila, $hddNumeroItmBono."_".$contFila,
						$hddNumeroItmBono."_".$contFila, $hddNumeroItmBono."_".$contFila, $hddIdNotaDebitoBono,
						$hddNumeroItmBono."_".$contFila, $hddNumeroItmBono."_".$contFila, $hddIdUnidadFisicaBono,
						$hddNumeroItmBono."_".$contFila, $hddNumeroItmBono."_".$contFila, $txtIdClienteBono,
						$hddNumeroItmBono."_".$contFila, $hddNumeroItmBono."_".$contFila, $txtIdMotivoBono,
						$hddNumeroItmBono."_".$contFila, $hddNumeroItmBono."_".$contFila, $hddIdAnticipoPagoBono,
						$hddNumeroItmBono."_".$contFila, $hddNumeroItmBono."_".$contFila, $rowAnticipo['id_concepto'],
					$txtIdMotivoBono.".- ".$txtMotivoBono,
					$hddNumeroItmBono."_".$contFila, $hddNumeroItmBono."_".$contFila, number_format($rowAnticipo['montoNetoAnticipo'], 2, ".", ","),
					number_format($txtMontoBono - $rowAnticipo['montoNetoAnticipo'], 2, ".", ","),
		
		$hddNumeroItmBono."_".$contFila,
			$hddNumeroItmBono."_".$contFila);
	
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
	$htmlItmPie = sprintf(
	"$('#trItmPiePago').before('".
		"<tr align=\"left\" id=\"trItmPago_%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmPago_%s\"><input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>".
				"<input type=\"checkbox\" id=\"cbxPiePago\" name=\"cbxPiePago[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"divMsjInfo2\">%s</td>".
			"<td class=\"divMsjInfo\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><table width=\"%s\">".
				"<tr><td>%s</td><td><input type=\"text\" id=\"txtNumeroDctoPago%s\" name=\"txtNumeroDctoPago%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"txtIdNumeroDctoPago%s\" name=\"txtIdNumeroDctoPago%s\" readonly=\"readonly\" value=\"%s\"/></td></tr>".
				"%s".
				"%s".
				"</table></td>".
			"<td><input type=\"text\" id=\"txtBancoClientePago%s\" name=\"txtBancoClientePago%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaClientePago%s\" name=\"txtCuentaClientePago%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtBancoCompaniaPago%s\" name=\"txtBancoCompaniaPago%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"text\" id=\"txtCuentaCompaniaPago%s\" name=\"txtCuentaCompaniaPago%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td align=\"right\"><input type=\"text\" id=\"txtMonto%s\" name=\"txtMonto%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
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

function insertarItemUnidad($contFila, $idUnidadFisica, $hddTpItm, $bloquearObj = false){
	$contFila++;
	
	if ($idUnidadFisica > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT
			an_ped_vent.id_pedido,
			an_ped_vent.id_empresa,
			an_ped_vent.id_cliente,
			an_ped_vent.id_moneda,
			an_ped_vent.id_moneda_tasa_cambio,
			an_ped_vent.id_tasa_cambio,
			an_ped_vent.monto_tasa_cambio,
			an_ped_vent.fecha_tasa_cambio,
			vw_iv_modelo.id_uni_bas,
			vw_iv_modelo.nom_uni_bas,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.nom_modelo,
			vw_iv_modelo.nom_version,
			vw_iv_modelo.nom_ano,
			uni_fis.id_unidad_fisica,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			color_ext1.nom_color AS color_externo,
			color_int1.nom_color AS color_interno,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.codigo_unico_conversion,
			uni_fis.serial1,
			an_ped_vent.precio_venta,
			an_ped_vent.monto_descuento,
			an_ped_vent.porcentaje_iva,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in
		FROM an_pedido an_ped_vent
			INNER JOIN cj_cc_cliente cliente ON (an_ped_vent.id_cliente = cliente.id)
			INNER JOIN pg_monedas moneda_local ON (an_ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (an_ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
			INNER JOIN pg_clave_movimiento clave_mov ON (an_ped_vent.id_clave_movimiento = clave_mov.id_clave_movimiento)
			LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
			LEFT JOIN an_presupuesto pres_vent ON (pres_vent.id_presupuesto = an_ped_vent.id_presupuesto)
			LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE an_ped_vent.id_unidad_fisica = %s
			AND an_ped_vent.estado_pedido IN (1);", 
			valTpDato($idUnidadFisica, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$txtTasaCambio = (!in_array($rowPedidoDet['monto_tasa_cambio'],array("",0))) ? $rowPedidoDet['monto_tasa_cambio'] : 1;
		$hddIdUnidadBasicaItm = $rowPedidoDet['id_uni_bas'];
		$hddIdUnidadFisicaItm = $rowPedidoDet['id_unidad_fisica'];
		$divCodigoItm = $rowPedidoDet['nom_uni_bas'];
		$txtPrecioItm = $rowPedidoDet['precio_venta'];
		$hddMontoDescuentoItm = $rowPedidoDet['monto_descuento'];
		$hddTotalDescuentoItm = $rowPedidoDet['monto_descuento'];
		$hddCostoItm = $rowPedidoDet['precio_compra'] + $rowPedidoDet['costo_agregado'] - $rowPedidoDet['costo_depreciado'] - $row['costo_trade_in'];
		$porcIva = $rowPedidoDet['porcentaje_iva'];
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT uni_bas_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
			INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo IN (6,9,2)
			AND (%s IS NOT NULL AND %s > 0);", 
			valTpDato($hddIdUnidadBasicaItm, "int"),
			valTpDato($porcIva, "real_inglesa"),
			valTpDato($porcIva, "real_inglesa"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$contIva = 0;
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$contIva++;
			
			$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
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
	}
	
	$divDescripcionItm = "<table border=\"0\" width=\"100%\">".
	"<tr height=\"22\">".
		"<td align=\"right\" class=\"tituloCampo\">Marca:</td>"."<td>".utf8_encode($rowPedidoDet['nom_marca'])."</td>".
		"<td align=\"right\" colspan=\"2\">".
			((!in_array(idArrayPais,array(1,2))) ?
				sprintf("<a class=\"modalImg\" id=\"aAgregarBono%s\" rel=\"#divFlotante1\">".
					"<button type=\"button\"><table align=\"center\" cellpadding=\"0\" cellspacing=\"0\"><tr><td><img class=\"puntero\" src=\"../img/iconos/add.png\" title=\"Agregar Bono\"/></td><td>&nbsp;</td><td>Agregar Bono</td></tr></table></button>".
				"</a>",
					$contFila) : "").
		"</td>".
	"</tr>".
	"<tr height=\"22\">".
		"<td align=\"right\" class=\"tituloCampo\" width=\"20%\">Modelo:</td>"."<td width=\"30%\">".utf8_encode($rowPedidoDet['nom_modelo'])."</td>".
		"<td align=\"right\" class=\"tituloCampo\" width=\"20%\">Versión:</td>"."<td width=\"30%\">".utf8_encode($rowPedidoDet['nom_version'])."</td>".
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Año:</td>".
		"<td>".$rowPedidoDet['nom_ano']."</td>".
		((strlen($rowPedidoDet['placa']) > 1) ? "<td align=\"right\" class=\"tituloCampo\">Placa:</td>"."<td>".utf8_encode($rowPedidoDet['placa'])."</td>" : "").
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Serial Carroceria:</td>".
		"<td>".utf8_encode($rowPedidoDet['serial_carroceria'])."</td>".
		"<td align=\"right\" class=\"tituloCampo\">Serial Motor:</td>".
		"<td>".utf8_encode($rowPedidoDet['serial_motor'])."</td>".
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Nro. Vehículo:</td>".
		"<td>".utf8_encode($rowPedidoDet['serial_chasis'])."</td>".
		"<td align=\"right\" class=\"tituloCampo\">Id. Unidad Física:</td>".
		"<td>".$idUnidadFisica."</td>".
	"</tr>".
	"<tr>".
		"<td align=\"right\" class=\"tituloCampo\">Color Carroceria:</td>".
		"<td>".utf8_encode($rowPedidoDet['color_externo'])."</td>".
		((strlen($rowPedidoDet['color_interno']) > 1) ? "<td align=\"right\" class=\"tituloCampo\">Tipo Tapiceria:</td>"."<td>".utf8_encode($rowPedidoDet['color_interno'])."</td>" : "").
	"</tr>".
	"<tr>".
		((strlen($rowPedidoDet['registro_legalizacion']) > 1) ? "<td align=\"right\" class=\"tituloCampo\">Registro Legalización:</td>"."<td>".utf8_encode($rowPedidoDet['registro_legalizacion'])."</td>" : "").
		((strlen($rowPedidoDet['registro_federal']) > 1) ? "<td align=\"right\" class=\"tituloCampo\">Registro Federal:</td>"."<td>".utf8_encode($rowPedidoDet['registro_federal'])."</td>" : "").
	"</tr>";
	if (in_array($rowPedidoDet['id_combustible'],array(2,5))) {
		$divDescripcionItm .= "<tr><td align=\"center\" class=\"tituloArea\" colspan=\"4\">SISTEMA GNV</td></tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Serial 1:</td>".
			"<td>".utf8_encode($rowPedidoDet['serial1'])."</td>".
			"<td align=\"right\" class=\"tituloCampo\">Código Único:</td>".
			"<td>".utf8_encode($rowPedidoDet['codigo_unico_conversion'])."</td>".
		"</tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Marca Kit:</td>".
			"<td>".utf8_encode($rowPedidoDet['marca_kit'])."</td>".
		"</tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Modelo Regulador:</td>".
			"<td>".utf8_encode($rowPedidoDet['modelo_regulador'])."</td>".
			"<td align=\"right\" class=\"tituloCampo\">Serial Regulador:</td>".
			"<td>".utf8_encode($rowPedidoDet['serial_regulador'])."</td>".
		"</tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Marca Cilindro:</td>".
			"<td>".utf8_encode($rowPedidoDet['marca_cilindro'])."</td>".
			"<td align=\"right\" class=\"tituloCampo\">Capacidad Cilindro (NG):</td>".
			"<td>".utf8_encode($rowPedidoDet['capacidad_cilindro'])."</td>".
		"</tr>".
		"<tr>".
			"<td align=\"right\" class=\"tituloCampo\">Fecha Elab. Cilindro:</td>".
			"<td>".$rowPedidoDet['fecha_elaboracion_cilindro']."</td>".
		"</tr>";
	}
	$divDescripcionItm .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf(
	"$('#trItmPieDetalle').before('".
		"<tr id=\"trItmDetalle_%s\" align=\"left\">".
			"<td title=\"trItmDetalle_%s\">".
				"<input type=\"checkbox\" id=\"cbxPieDetalle\" name=\"cbxPieDetalle[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmDetalle_%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><div id=\"divCodigoItm%s\">%s</div></td>".
			"<td><div id=\"divDescripcionItm%s\">%s</div>".
				"<div id=\"divBonoItm%s\">".
					"<table border=\"0\" width=\"%s\"><tr id=\"trItmPieBono%s\"></tr></table>".
				"</div></td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddMontoDescuentoItm%s\" name=\"hddMontoDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" value=\"%s\"/></td>".
			"<td id=\"tdIvaItm%s\">%s</td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td style=\"display:none\"><input type=\"text\" id=\"txtTotalConImpuestoItm%s\" name=\"txtTotalConImpuestoItm%s\" class=\"inputCompletoSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDet%s\" name=\"hddIdPedidoDet%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdItm%s\" name=\"hddIdItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTpItm%s\" name=\"hddTpItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTotalDescuentoItm%s\" name=\"hddTotalDescuentoItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aAgregarBono%s').onclick = function() {
			abrirDivFlotante1(this, 'tblBono', %s);
		}",
		$contFila, 
			$contFila,
				$contFila,
			$contFila, $contFila, 
			$contFila, $divCodigoItm, 
			$contFila, $divDescripcionItm,
				$contFila,
					"100%", $contFila,
			$contFila, $contFila, number_format(1, 2, ".", ","), 
			$contFila, $contFila, number_format($txtTasaCambio * $txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, number_format($txtTasaCambio * $hddMontoDescuentoItm, 2, ".", ","), 
				$contFila, $contFila, number_format($hddCostoItm, 2, ".", ","), 
			$contFila, $ivaUnidad, 
			$contFila, $contFila, number_format($txtTasaCambio * $txtPrecioItm, 2, ".", ","), 
			$contFila, $contFila, number_format($txtTasaCambio * $txtPrecioItm, 2, ".", ","), 
				$contFila, $contFila, $hddIdPedidoDet, 
				$contFila, $contFila, $idUnidadFisica, 
				$contFila, $contFila, $hddTpItm, // 1 = Por Paquete, 2 = Individual, 3 = Unidad Física, 4 = Repuesto
				$contFila, $contFila, number_format($txtTasaCambio * $hddTotalDescuentoItm, 2, ".", ","),
			
			$contFila,
				$contFila);
	
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