<?php

function asignarArticuloImpuesto($frmArticuloImpuesto, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$hddIdIvaItm = implode(",",$frmArticuloImpuesto['lstIvaCbx']);
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
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
			
			$objResponse->assign("divIvaItm".$contFila,"innerHTML",$ivaUnidad);
		}
	}
	
	$objResponse->script("
	byId('btnCancelarArticuloImpuesto').click();");
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
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
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
	}
	byId('txtTotalExento').onblur = function() {
		setFormatoRafk(this,2);
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
	}
	byId('txtTotalExonerado').onblur = function() {
		setFormatoRafk(this,2);
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
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
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
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
					xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
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
			$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
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

function buscarCliente($frmBuscarCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarConcepto($frmBuscarConcepto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarConcepto['lstTipoConceptoBuscar'],
		$frmBuscarConcepto['txtCriterioBuscarConcepto']);
	
	$objResponse->loadCommands(listaConcepto(0, "", "", $valBusq));
	
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

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
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
		$txtSubTotalDescuento = round(str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']),2);
		$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
		$totalFactura = $txtSubTotal - $txtSubTotalDescuento + $subTotalIva;
		
		$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($txtDescuento, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
		$objResponse->assign("txtTotalPedido","value",number_format($totalFactura, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';");
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
		$objResponse->assign("txtTotalPedido","value",number_format($totalDcto, 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpresa').readOnly = false;
		byId('aListarEmpresa').style.display = '';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtIdCliente').readOnly = false;
		byId('aListarCliente').style.display = '';");
	}
	
	if ($frmDcto['lstModulo'] >= 0) {
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		
		byId('txtIdCliente').onblur = function() { }");
	} else {
		$objResponse->script("
		byId('txtIdCliente').onblur = function() { xajax_asignarCliente(this.value, byId('txtIdEmpresa').value, 'Activo', '', '', 'true', 'false'); }");
	}
	
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
		$sqlBusq .= $cond.sprintf("(clave_mov.id_clave_movimiento = %s
		AND clave_mov.id_modulo IN (%s))",
			valTpDato($selId, "int"),
			valTpDato($idModulo, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		clave_mov.tipo,
		(CASE clave_mov.tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento clave_mov %s
	ORDER BY clave_mov.tipo", $sqlBusq);
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
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento clave_mov %s %s ORDER BY clave_mov.clave", $sqlBusq, $sqlBusq2);
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
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	$idModulo = ($selId > 0) ? $selId : 3;
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo = %s",
		valTpDato($idModulo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
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

function cargaLstVendedor($idEmpresa = "", $selId = ""){
	$objResponse = new xajaxResponse();
	
	if (!$selId){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((clave_filtro IN (1,3,5,7,9,10) AND activo = 1)
		OR id_empleado = %s)",
			valTpDato($selId, "int"));
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(id_empresa = %s
			OR %s IN (SELECT usu_emp.id_empresa
						FROM pg_usuario usu
							INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
						WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		$query = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado %s ORDER BY nombre_empleado;", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select id=\"lstVendedor\" name=\"lstVendedor\" class=\"inputHabilitado\" style=\"width:99%\">";
			$html .= "<option value=\"0\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {                   
			$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
		}
		$html .= "</select>";
	} else {
		$query = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s;",
			valTpDato($selId, "int"));
		$rs = mysql_query($query);
		if (!$rs) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		$html .= "<input type=\"text\" id=\"lstVendedor\" name=\"lstVendedor\" readonly=\"readonly\" value=\"".utf8_encode($row['nombre_empleado'])."\"/>";
	}
	
	$objResponse->assign("tdlstVendedor","innerHTML",$html);
		
	return $objResponse;
}

function eliminarArticulo($trItmArticulo, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($trItmArticulo) && $trItmArticulo > 0) {
		$objResponse->script("
		fila = document.getElementById('trItmArticulo:".$trItmArticulo."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
			
		$objResponse->script("xajax_eliminarArticulo('', xajax.getFormValues('frmListaArticulo'));");
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
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
		byId('aListarEmpresa').style.display = 'none';
		byId('aListarCliente').style.display = 'none';
		
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdCliente').readOnly = true;
		byId('txtObservacion').readOnly = true;
		
		byId('btnPedidoVentaPDF').style.display = 'none';
		
		byId('txtSubTotalDescuento').className = 'inputSinFondo';
		byId('txtSubTotalDescuento').readOnly = true;");
		
		if ($acc == 1) {// EDITAR
			$objResponse->script("
			byId('txtObservacion').className = 'inputHabilitado';
			byId('txtObservacion').readOnly = false;
			
			byId('txtSubTotalDescuento').className = 'inputHabilitado';
			byId('txtSubTotalDescuento').readOnly = false;");
		}
		
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
		
		if ($acc == 0) { // 0 = CONSULTAR, 1 = EDITAR
			$objResponse->script("byId('btnGuardar').style.display = 'none'");
			$objResponse->script("byId('aImpuestoArticulo').style.display = 'none'");
			$objResponse->script("byId('aAgregarArticulo').style.display = 'none'");
		}
				
		$objResponse->loadCommands(asignarEmpresaUsuario($rowPedido['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(asignarCliente($rowPedido['id_cliente'], $rowPedido['id_empresa'], "", $rowPedido['condicion_pago'], $rowPedido['id_clave_movimiento'], "false", "false", "false"));
		$objResponse->loadCommands(asignarEmpleado($rowPedido['id_empleado_creador']));
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $rowPedido['id_modulo'], "3", $rowPedido['condicion_pago'], "1", $rowPedido['id_clave_movimiento'], "onchange=\"xajax_asignarClaveMovimiento(this.value); xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"")); 
		
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat, strtotime($rowPedido['fecha_registro'])));
		$objResponse->assign("hddIdPedido","value",$idPedido);
		$objResponse->assign("txtNumeroPedido","value",$rowPedido['numero_pedido']);
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat, strtotime($rowPedido['fecha_registro'])));
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
		
		$objResponse->assign("txtSubTotal","value",number_format($rowPedido['subtotal'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($rowPedido['porcentaje_descuento'], 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowPedido['subtotal_descuento'], 2, ".", ","));
		//$objResponse->assign("txtFlete","value",number_format($rowPedido['fletesFactura'], 2, ".", ","));
		$objResponse->assign("txtGastosConIva","value",number_format($txtGastosConIva, 2, ".", ","));
		$objResponse->assign("txtGastosSinIva","value",number_format($txtGastosSinIva, 2, ".", ","));
		$objResponse->assign("txtTotalPedido","value",number_format($rowPedido['monto_total'], 2, ".", ","));
		$objResponse->assign("txtTotalExento","value",number_format($rowPedido['monto_exento'], 2, ".", ","));
		$objResponse->assign("txtTotalExonerado","value",number_format($rowPedido['monto_exonerado'], 2, ".", ","));
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->script("
		byId('aListarCliente').style.display = '';
		
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdCliente').className = 'inputHabilitado';
		byId('txtSubTotalDescuento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';
		
		byId('btnPedidoVentaPDF').style.display = 'none';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'), 'true');"));
		$objResponse->loadCommands(cargaLstModulo(-1, "validarAsignarDepartamento();"));
		$objResponse->loadCommands(cargaLstMoneda());
		$objResponse->loadCommands(cargaLstVendedor($idEmpresa));
		$objResponse->assign("txtFechaPedido","value",date(spanDateFormat));
		
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$objResponse->assign("hddIdMoneda","value",$rowMoneda['idmoneda']);
		
		$objResponse->script("
		byId('lstTipoMovimiento').onchange = function() {
			selectedOption('lstTipoMovimiento', '3');
		}");
		$objResponse->call("selectedOption","lstTipoMovimiento",3);
		
		$objResponse->script("xajax_asignarDepartamento(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idPedido = $frmDcto['hddIdPedido'];
	$idModulo = $frmDcto['lstModulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idCliente = $frmDcto['txtIdCliente'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	$idTipoPago = $frmDcto['rbtTipoPago'];
	
	mysql_query("START TRANSACTION;");

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
	
	if ($idPedido > 0) {
		if (!xvalidaAcceso($objResponse,"cc_captura_pedido_list","editar")) { return $objResponse; }

		// COMPRUEBA EL ESTADO DEL PEDIDO
		$query = sprintf("SELECT * FROM cj_cc_pedido WHERE id_pedido = %s AND estado_pedido IN (0,3)",
			valTpDato($idPedido, "int"));// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		if(mysql_num_rows($rs) == 0){
			return $objResponse->alert("El pedido no posee el status correcto para ser editado");
		}

		// ACTUALIZA LOS DATOS DEL PEDIDO
		$updateSQL = sprintf("UPDATE cj_cc_pedido SET 
			id_empresa = %s, 
			id_cliente = %s, 
			id_moneda = %s, 
			id_modulo = %s, 
			monto_total = %s, 
			observacion = %s, 
			subtotal = %s, 
			porcentaje_descuento = %s, 
			subtotal_descuento = %s, 
			base_imponible = %s, 
			monto_exento = %s, 
			monto_exonerado = %s
		WHERE id_pedido = %s",
			valTpDato($idEmpresa, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($idModulo, "int"),
			valTpDato($frmTotalDcto['txtTotalPedido'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($txtBaseImponibleIva, "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato($idPedido, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"cc_captura_pedido_list","insertar")) { return $objResponse; }
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(55, "int"), // 55 = Pedido CXC
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado la numeración de pedidos"); }
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// INSERTAR LOS DATOS DEL PEDIDO
		$insertSQL = sprintf("INSERT INTO cj_cc_pedido (id_empresa, id_cliente, numero_pedido, fecha_registro, id_moneda, id_modulo, id_vendedor, id_clave_movimiento, condicion_pago, estado_pedido, monto_total, observacion, subtotal, porcentaje_descuento, subtotal_descuento, base_imponible, monto_exento, monto_exonerado, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idEmpresa, "int"),
			valTpDato($idCliente, "int"),
			valTpDato($numeroActual, "text"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaPedido'])), "date"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($idModulo, "int"),
			valTpDato($frmDcto['lstVendedor'], "int"),
			valTpDato($idClaveMovimiento, "int"),
			valTpDato($idTipoPago, "int"), // 0 = Credito, 1 = Contado
			valTpDato(0, "int"), // 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
			valTpDato($frmTotalDcto['txtTotalPedido'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($txtBaseImponibleIva, "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idPedido = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	$deleteSQL = sprintf("DELETE FROM cj_cc_pedido_detalle WHERE id_pedido = %s",
		valTpDato($idPedido, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$deleteSQL = sprintf("DELETE FROM cj_cc_pedido_impuesto WHERE id_pedido = %s",
		valTpDato($idPedido, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
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
			
			$insertSQL = sprintf("INSERT INTO cj_cc_pedido_detalle (id_pedido, id_concepto, descripcion, cantidad, precio_unitario, costo_unitario)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idPedido, "int"),
				valTpDato($idConcepto, "int"),
				valTpDato($descripcionConcepto, "text"),
				valTpDato($cantPedida, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idPedidoDetalle = mysql_insert_id();
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
							$insertSQL = sprintf("INSERT INTO cj_cc_pedido_detalle_impuesto (id_pedido_detalle, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idPedidoDetalle, "int"),
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
				$insertSQL = sprintf("INSERT INTO cj_cc_pedido_impuesto (id_pedido, base_imponible, subtotal_impuesto, id_impuesto, impuesto, lujo)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idPedido, "int"),
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
	
	mysql_query("COMMIT;");		
	
	$objResponse->alert("Pedido Guardado con Éxito");	
	$objResponse->script(sprintf("window.location.href='cc_captura_pedido_list.php';"));
	
	$objResponse->script("verVentana('reportes/cc_pedido_venta_pdf.php?valBusq=".$idPedido."', 960, 550);");
	
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
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
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

function listaConcepto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus = 1");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(concep.id_concepto = %s
		OR concep.descripcion LIKE %s)",
			valTpDato($valCadBusq[1], "int"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT concep.*,
		tipo_concep.descripcion AS tipo_concepto
	FROM cj_cc_concepto concep
		INNER JOIN cj_cc_tipo_concepto tipo_concep ON (concep.id_tipo_concepto = tipo_concep.id_tipo_concepto) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "20%", $pageNum, "codigo_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "60%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaConcepto", "20%", $pageNum, "tipo_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Concepto");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarArticulo%s\" onclick=\"validarInsertarArticulo('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_concepto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['codigo_concepto'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_concepto'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaConcepto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
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

$xajax->register(XAJAX_FUNCTION,"asignarArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"asignarClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarDepartamento");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarConcepto");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstIva");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"formArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"formDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaConcepto");

function cargaLstIvaItm($nombreObjeto, $selId = "", $selVal = "") {
	//BLOQUEAR AL VISUALIZAR
	$class = "inputHabilitado";
	$onchange = "onchange=\"xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"";
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
			"<td><input type=\"text\" id=\"txtDescItm%s\" name=\"txtDescItm%s\" class=\"inputCompleto\" maxlength=\"255\" style=\"text-align:left\" %s value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" %s value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" %s value=\"%s\"></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td title=\"trItmArticulo:%s\"><a id=\"aEliminar:%s\" %s><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" readonly=\"readonly\" value=\"%s\"></td>".
		"</tr>');
		
		byId('txtCantItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtPrecioItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('aEliminar:%s').onclick = function() {
			xajax_eliminarArticulo('%s', xajax.getFormValues('frmListaArticulo'));
		}",
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

?>