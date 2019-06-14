<?php


function asignarFacturaCargo($frmListaRegistroCompra, $frmExpediente, $frmFacturaGasto, $idFacturaCargo = "", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmExpediente['cbx1'];
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			if ($frmExpediente['hddIdFacturaCargo'.$valor1] == $idFacturaCargo && $idFacturaCargo > 0) {
				return $objResponse->alert("Este item ya se encuentra incluido");
			}
		}
	}
	
	if ($idFacturaCargo > 0) {
		$hddItmGasto = $frmListaRegistroCompra['hddItmGastoListaRegistroCompra'];
		
		$query = sprintf("SELECT
			fact_comp_cargo.id_factura,
			fact_comp_cargo.id_modo_compra,
			fact_comp_cargo.numero_factura_proveedor,
			fact_comp_cargo.numero_control_factura,
			fact_comp_cargo.fecha_origen,
			prov.id_proveedor,
			fact_comp_cargo.subtotal_factura,
			fact_comp_cargo.subtotal_descuento,
			moneda_local.abreviacion AS abreviacion_moneda
		FROM cp_factura fact_comp_cargo
			INNER JOIN cp_proveedor prov ON (fact_comp_cargo.id_proveedor = prov.id_proveedor)
			LEFT JOIN pg_monedas moneda_local ON (fact_comp_cargo.id_moneda = moneda_local.idmoneda)
		WHERE id_factura = %s;",
			valTpDato($idFacturaCargo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$hddIdFacturaCargo = $row['id_factura'];
		$txtNumeroFacturaGasto = $row['numero_factura_proveedor'];
		$txtNumeroControlFacturaGasto = $row['numero_control_factura'];
		$txtFechaFacturaGasto = date(spanDateFormat,strtotime($row['fecha_origen']));
		$txtIdProvFacturaGasto = $row['id_proveedor'];
		
		$hddSubTotalFacturaGasto = $row['subtotal_factura'] - $row['subtotal_descuento'];
	} else {
		$hddItmGasto = $frmFacturaGasto['hddItmGasto'];
		
		$hddSubTotalFacturaGasto = str_replace(",","",$frmFacturaGasto['txtSubTotalFacturaGasto']);
	}
	
	$objResponse->assign("hddCondicionGasto".$hddItmGasto,"value",$frmFacturaGasto['lstCondicionGasto']); // 1 = Real, 2 = Estimado
	$objResponse->assign("hddIdFacturaCargo".$hddItmGasto,"value",$hddIdFacturaCargo);
	$objResponse->assign("txtNumeroFacturaGasto".$hddItmGasto,"value",$txtNumeroFacturaGasto);
	$objResponse->assign("txtNumeroControlFacturaGasto".$hddItmGasto,"value",$txtNumeroControlFacturaGasto);
	$objResponse->assign("txtFechaFacturaGasto".$hddItmGasto,"value",$txtFechaFacturaGasto);
	$objResponse->loadCommands(asignarProveedor($txtIdProvFacturaGasto, "ProvFacturaGasto".$hddItmGasto, "false"));
	
	$objResponse->assign("hddSubTotalFacturaGasto".$hddItmGasto,"value",number_format($hddSubTotalFacturaGasto, 2, ".", ","));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaRegistroCompra').click();");
	}
	
	$objResponse->script("xajax_calcularExpediente(xajax.getFormValues('frmExpediente'));");
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $nombreObjeto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		prov.nombre,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.direccion,
		prov.contacto,
		prov.correococtacto,
		prov.telefono,
		prov.fax,
		prov.credito
	FROM cp_proveedor prov
	WHERE prov.id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",htmlentities($rowProv['nombre']));
	$objResponse->assign("txtRif".$nombreObjeto,"value",htmlentities($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",htmlentities($rowProv['direccion']));
	$objResponse->assign("txtContacto".$nombreObjeto,"value",htmlentities($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$nombreObjeto,"value",htmlentities($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonos".$nombreObjeto,"value",$rowProv['telefono']);
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value",$rowProvCredito['diascredito']);
		
		$objResponse->assign("rbtTipoPagoCredito".$nombreObjeto,"checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;");
		
	} else {
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value","0");
		
		$objResponse->assign("rbtTipoPagoContado".$nombreObjeto,"checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;");
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaProveedor').click();");
	}
	
	return $objResponse;
}

function buscarExpediente($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstEstatus'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaExpediente(0, "id_expediente", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarRegistroCompra($frmBuscarRegistroCompra) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarRegistroCompra['hddIdModulo'],
		$frmBuscarRegistroCompra['txtCriterioBuscarRegistroCompra']);
	
	if ($frmBuscarRegistroCompra['hddIdModulo'] == 0) {
		$campOrd = "id_factura_compra";
	} else if ($frmBuscarRegistroCompra['hddIdModulo'] == 3) {
		$campOrd = "id_factura";
	}
	
	$objResponse->loadCommands(listaFacturaCompra(0, $campOrd, "DESC", $valBusq));
	
	return $objResponse;
}

function calcularExpediente($frmExpediente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmExpediente['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmFactura:".$valor,"className",$clase." textoGris_11px");
		}
	}
	if (isset($arrayObj))
		$objResponse->assign("hddObjFactura","value",implode("|", $arrayObj));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmExpediente['cbx1'];
	if (isset($arrayObj1)) {
		$i = 0;
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmCargo:".$valor1,"className",$clase." textoGris_11px");
		}
	}
	if (isset($arrayObj1))
		$objResponse->assign("hddObjCargo","value",implode("|", $arrayObj1));
	
	// RECORRE LOS GASTOS PARA PRORRATEARLOS PARA CADA FACTURA
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$hddSubTotalFacturaGasto = str_replace(",","",$frmExpediente['hddSubTotalFacturaGasto'.$valor1]);
			
			$totalOtrosCargos += $hddSubTotalFacturaGasto;
		}
	}
	
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalOtrosCargos, 2, ".", ","));
	
	return $objResponse;
}

function cargarExpediente($idExpediente, $frmExpediente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmExpediente['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmExpediente['cbx1'];
	$contFila1 = $arrayObj1[count($arrayObj)-1];
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmFactura:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valor));
		}
	}
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmCargo:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valor1));
		}
	}
	
	$arrayObj = NULL;
	$contFila = 0;
	
	$arrayObj1 = NULL;
	$contFila1 = 0;
	
	// BUSCA LOS DATOS DEL EXPEDIENTE
	$query = sprintf("SELECT * FROM iv_expediente
	WHERE id_expediente = %s;",
		valTpDato($idExpediente, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdExpediente","value",$idExpediente);
	$objResponse->assign("txtNumeroExpediente","value",$row['numero_expediente']);
	$objResponse->assign("txtNumeroEmbarque","value",$row['numero_embarque']);
	
	// BUSCA LOS CARGOS DEL EXPEDIENTE
	$query = sprintf("SELECT * FROM iv_expediente_detalle_factura expediente_det_fact
	WHERE id_expediente = %s;",
		valTpDato($idExpediente, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = insertarItemFacturaCompra($contFila, $row['id_factura_compra'], $row['id_expediente_detalle_factura']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	// BUSCA LOS CARGOS DEL EXPEDIENTE
	$query = sprintf("SELECT * FROM iv_expediente_detalle_cargos expediente_det_cargo
	WHERE id_expediente = %s;",
		valTpDato($idExpediente, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = insertarItemCargo($contFila1, $row['id_gasto'], $row['id_expediente_detalle_cargo']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila1 = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj1[] = $contFila1;
		}
	}
	
	$objResponse->script("xajax_calcularExpediente(xajax.getFormValues('frmExpediente'));");
	
	return $objResponse;
}

function cargarFacturaCargo($hddItmGasto, $frmExpediente, $frmFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	$hddIdGasto = $frmExpediente['hddIdGastoCargo'.$hddItmGasto];
	$hddCondicionGasto = $frmExpediente['hddCondicionGasto'.$hddItmGasto];
	
	// BUSCA LOS DATOS DEL GASTO DE IMPORTACION
	$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
		valTpDato($hddIdGasto, "int"));
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGastos = mysql_fetch_assoc($rsGasto);
	
	$lstAsociaDocumento = $rowGastos['asocia_documento'];
	
	switch ($lstAsociaDocumento) {
		case 0 : // 0 = No
			$objResponse->script("
			byId('lstCondicionGasto').onchange = function () {
				seleccionarCondicion(this.value);
				selectedOption(this.id,".(1)."');
			}"); break;
		case 1 : // 1 = Si
			$objResponse->script(sprintf("
			byId('lstCondicionGasto').onchange = function () {
				seleccionarCondicion(this.value);
			}")); break;
	}
	
	$objResponse->script("
	byId('lstAsociaDocumento').onchange = function () {
		selectedOption(this.id,'".$lstAsociaDocumento."');
	}");
	
	$objResponse->assign("hddItmGastoListaRegistroCompra","value",$hddItmGasto);
	$objResponse->assign("hddItmGasto","value",$hddItmGasto);
	$objResponse->call("selectedOption","lstCondicionGasto",$hddCondicionGasto);
	$objResponse->call("selectedOption","lstAsociaDocumento",$lstAsociaDocumento);
	
	if ($hddCondicionGasto == 2 || $lstAsociaDocumento == 0) { // 2 = Estimado || 0 = No
		$objResponse->assign("txtSubTotalFacturaGasto","value",$frmExpediente['hddSubTotalFacturaGasto'.$hddItmGasto]);
	}
	
	$objResponse->script("
	byId('lstCondicionGasto').onchange();
	byId('btnBuscarRegistroCompra').click();");
	
	return $objResponse;
}

function eliminarCargo($frmExpediente) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmExpediente['cbxItmCargo'])) {
		foreach ($frmExpediente['cbxItmCargo'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmCargo:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarCargo(xajax.getFormValues('frmExpediente'));");
	}
	
	return $objResponse;
}

function eliminarFacturaCompra($frmExpediente) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmExpediente['cbxItmFactura'])) {
		foreach ($frmExpediente['cbxItmFactura'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmFactura:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
		$objResponse->script("xajax_eliminarFacturaCompra(xajax.getFormValues('frmExpediente'));");
	}
	
	return $objResponse;
}

function formExpediente($frmExpediente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmExpediente['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmExpediente['cbx1'];
	$contFila1 = $arrayObj1[count($arrayObj)-1];
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmFactura:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valor));
		}
	}
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmCargo:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valor1));
		}
	}
	
	$objResponse->script("xajax_calcularExpediente(xajax.getFormValues('frmExpediente'));");
	
	return $objResponse;
}

function guardarExpediente($frmExpediente, $frmListaExpediente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmExpediente['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmExpediente['cbx1'];
	
	$idExpediente = $frmExpediente['hddIdExpediente'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idExpediente > 0) {
		if (!xvalidaAcceso($objResponse,"iv_expediente_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE iv_expediente SET
			numero_expediente = %s,
			numero_embarque = %s
		WHERE id_expediente = %s;",
			valTpDato($frmExpediente['txtNumeroExpediente'], "text"),
			valTpDato($frmExpediente['txtNumeroEmbarque'], "text"),
			valTpDato($idExpediente, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_expediente_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO iv_expediente (numero_expediente, numero_embarque)
		VALUE (%s, %s);",
			valTpDato($frmExpediente['txtNumeroExpediente'], "text"),
			valTpDato($frmExpediente['txtNumeroEmbarque'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idExpediente = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
			
	// ELIMINA LOS CARGOS DE LA FACTURA DE COMPRA
	$deleteSQL = sprintf("DELETE FROM iv_factura_compra_gasto
	WHERE id_factura_compra IN (SELECT expediente_det_fact.id_factura_compra FROM iv_expediente_detalle_factura expediente_det_fact
								WHERE expediente_det_fact.id_expediente = %s)
		AND id_modo_gasto IN (2)
		AND id_expediente_detalle_cargo IN (SELECT expediente_det_cargo.id_expediente_detalle_cargo FROM iv_expediente_detalle_cargos expediente_det_cargo
											WHERE expediente_det_cargo.id_expediente = %s);",
		valTpDato($idExpediente, "int"),
		valTpDato($idExpediente, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// ELIMINA LAS FACTURAS DE COMPRA DEL EXPEDIENTE
	$deleteSQL = sprintf("DELETE FROM iv_expediente_detalle_factura WHERE id_expediente = %s;",
		valTpDato($idExpediente, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// ELIMINA LOS CARGOS DEL EXPEDIENTE
	$deleteSQL = sprintf("DELETE FROM iv_expediente_detalle_cargos WHERE id_expediente = %s;",
		valTpDato($idExpediente, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// RECORRE LAS FACTURAS DE COMPRA
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			// INSERTA EL DETALLE DE LAS FACTURAS DE COMPRA DEL EXPEDIENTE
			$insertSQL = sprintf("INSERT INTO iv_expediente_detalle_factura (id_expediente, id_factura_compra)
			VALUE (%s, %s);",
				valTpDato($idExpediente, "int"),
				valTpDato($frmExpediente['hddIdFacturaCompra'.$valor], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			$query = sprintf("SELECT 
				fact_comp.id_moneda,
				fact_comp.id_moneda_tasa_cambio
			FROM iv_factura_compra fact_comp
				INNER JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda)
				LEFT JOIN pg_monedas moneda_extranjera ON (fact_comp.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
			WHERE fact_comp.id_factura_compra = %s;",
				valTpDato($frmExpediente['hddIdFacturaCompra'.$valor], "int"));
			$rs = mysql_query($query);
			if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$row = mysql_fetch_assoc($rs);
			
			$idMonedaLocal = $row['id_moneda'];
			$idMonedaOrigen = ($row['id_moneda_tasa_cambio'] > 0) ? $row['id_moneda_tasa_cambio'] : $row['id_moneda'];
			
			$hddTotalFactura = explode(" ",$frmExpediente['hddTotalFactura'.$valor]);
			
			if ($idMonedaLocal == $idMonedaOrigen) {
				$hddTotalFactura = str_replace(",","",$hddTotalFactura[0]);
			} else {
				$query = sprintf("SELECT * FROM pg_tasa_cambio tasa_cambio
				WHERE tasa_cambio.id_moneda_nacional = %s
					AND tasa_cambio.id_moneda_extranjera = %s;",
					valTpDato($idMonedaLocal, "int"),
					valTpDato($idMonedaOrigen, "int"));
				$rs = mysql_query($query);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$row = mysql_fetch_assoc($rs);
				
				$tasaCambio = ($row['monto_tasa_cambio'] > 0) ? $row['monto_tasa_cambio'] : 1;
				
				$hddTotalFactura = str_replace(",","",$hddTotalFactura[0]) * $tasaCambio;
			}
			
			$arrayFactura[] = array(
				$frmExpediente['hddIdFacturaCompra'.$valor],
				$hddTotalFactura);
			$totalFacturas += $hddTotalFactura;
		}
	}
	
	// RECORRE LOS GASTOS PARA PRORRATEARLOS PARA CADA FACTURA
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			// INSERTA EL DETALLE DE LOS CARGOS DEL EXPEDIENTE
			$insertSQL = sprintf("INSERT INTO iv_expediente_detalle_cargos (id_expediente, id_gasto, id_factura_compra_cargo, monto)
			VALUE (%s, %s, %s, %s);",
				valTpDato($idExpediente, "int"),
				valTpDato($frmExpediente['hddIdGastoCargo'.$valor1], "int"),
				valTpDato($frmExpediente['hddIdFacturaCargo'.$valor1], "int"),
				valTpDato($frmExpediente['hddSubTotalFacturaGasto'.$valor1], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { 
				if (mysql_errno() == 1048) {
					return $objResponse->alert("El cargo debe tener una factura de compra asignada");
				} else {
					return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
			$idExpedienteDetalleCargo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			if (isset($arrayFactura)) {
				foreach ($arrayFactura as $indice2 => $valor2) {
					$hddItmGasto = $valor1;
					
					$hddIdGastoCargo = $frmExpediente['hddIdGastoCargo'.$hddItmGasto];
					
					// BUSCA LOS DATOS DEL CARGO
					$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
						valTpDato($hddIdGastoCargo, "int"));
					$rsGasto = mysql_query($queryGasto);
					if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowGastos = mysql_fetch_assoc($rsGasto);
					
					$lstAsociaDocumento = $rowGastos['asocia_documento'];
					$hddCondicionGasto = $frmExpediente['hddCondicionGasto'.$hddItmGasto]; // 1 = Real, 2 = Estimado
					$idFacturaCargo = $frmExpediente['hddIdFacturaCargo'.$hddItmGasto];
					
					$hddTotalFactura = $arrayFactura[$indice2][1];
					$hddSubTotalFacturaGasto = str_replace(",", "", $frmExpediente['hddSubTotalFacturaGasto'.$hddItmGasto]);
					$hddSubTotalFacturaGasto = ($hddTotalFactura * $hddSubTotalFacturaGasto) / $totalFacturas;
					
					$insertSQL = sprintf("INSERT INTO iv_factura_compra_gasto (id_factura_compra, id_gasto, tipo, porcentaje_monto, monto, monto_medida, id_iva, iva, id_modo_gasto, id_tipo_medida, afecta_documento, id_factura_compra_cargo, id_condicion_gasto, id_expediente_detalle_cargo)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($arrayFactura[$indice2][0], "int"),
						valTpDato($frmExpediente['hddIdGastoCargo'.$hddItmGasto], "int"),
						valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
						valTpDato($frmExpediente['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
						valTpDato($hddSubTotalFacturaGasto, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato("", "int"),
						valTpDato(0, "real_inglesa"),
						valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
						valTpDato("", "int"), // 1 = Peso
						valTpDato(0, "int"), // 0 = No, 1 = Si
						valTpDato($idFacturaCargo, "int"),
						valTpDato($hddCondicionGasto, "int"), // 1 = Real, 2 = Estimado
						valTpDato($idExpedienteDetalleCargo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(("Expediente Guardado con Éxito"));
	
	$objResponse->script("
	byId('btnCancelarExpediente').click();");
	
	$objResponse->loadCommands(listaExpediente(
		$frmListaExpediente['pageNum'],
		$frmListaExpediente['campOrd'],
		$frmListaExpediente['tpOrd'],
		$frmListaExpediente['valBusq']));
	
	return $objResponse;
}

function insertarCargo($idGasto, $frmExpediente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmExpediente['cbx1'];
	$contFila1 = $arrayObj1[count($arrayObj1)-1];
	
	$Result1 = insertarItemCargo($contFila1, $idGasto);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila1 = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj1[] = $contFila1;
	}
	
	$objResponse->script("xajax_calcularExpediente(xajax.getFormValues('frmExpediente'));");
	
	return $objResponse;
}

function insertarFacturaCompra($idFacturaCompra, $frmExpediente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmExpediente['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$existe = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmExpediente['hddIdFacturaCompra'.$valor] == $idFacturaCompra) {
				$existe = true;
			}
		}
	}
	
	if ($existe == false) {
		$Result1 = insertarItemFacturaCompra($contFila, $idFacturaCompra);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	} else {
		$objResponse->alert("Este item ya se encuentra incluido");
	}
	
	$objResponse->script("xajax_calcularExpediente(xajax.getFormValues('frmExpediente'));");
	
	return $objResponse;
}

function listaExpediente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus = 0");
	
	/*if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_comp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}*/
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "date"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_expediente LIKE %s
		OR numero_embarque LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM iv_expediente %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "8%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "46%", $pageNum, "numero_expediente", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Expediente");
		$htmlTh .= ordenarCampo("xajax_listaExpediente", "46%", $pageNum, "numero_embarque", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Embarque / BL");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Abierto\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Cerrado\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['numero_expediente'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['numero_embarque'])."</td>";
			$htmlTb .= "<td>";
			if ($row['estatus'] == 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblExpediente', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_expediente']);
			}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaExpediente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaExpediente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaExpediente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaExpediente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaExpediente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaExpediente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaOtrosCargos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(id_modo_gasto = 2
	AND estatus_iva = 0)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		gasto.id_gasto,
		gasto.nombre,
		gasto.estatus_iva,
		gasto.id_modo_gasto,
		gasto.asocia_documento,
		iva.idIva,
		iva.iva
	FROM pg_gastos gasto
		LEFT JOIN pg_iva iva ON (gasto.id_iva = iva.idIva) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargos", "85%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaOtrosCargos", "15%", $pageNum, "asocia_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Asociar Documento");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$documentoAsociado = ($row['asocia_documento'] == 1) ? "Si" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarCargo%s\" onclick=\"validarInsertarCargo('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_gasto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".($documentoAsociado)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"3\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtrosCargos(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaOtrosCargos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaFacturaCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] == 0) {
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(numero_factura_proveedor LIKE %s
			OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
			OR prov.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"));
		}
		
		$query = sprintf("SELECT 
			fact_comp.id_factura_compra,
			fact_comp.fecha_origen,
			fact_comp.fecha_factura_proveedor,
			fact_comp.numero_factura_proveedor,
			prov.nombre AS nombre_proveedor,
			
			(SELECT COUNT(fact_compra_det.id_factura_compra) AS items
			FROM iv_factura_compra_detalle fact_compra_det
			WHERE (fact_compra_det.id_factura_compra = fact_comp.id_factura_compra)) AS cant_items,
				
			(IFNULL(fact_comp.subtotal_factura, 0)
				- IFNULL(fact_comp.subtotal_descuento, 0)
				+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
						FROM iv_factura_compra_gasto fact_compra_gasto
						WHERE fact_compra_gasto.id_factura_compra = fact_comp.id_factura_compra
							AND fact_compra_gasto.id_modo_gasto = 1
							AND fact_compra_gasto.afecta_documento = 1), 0)
				+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
						FROM iv_factura_compra_iva fact_compra_iva
						WHERE fact_compra_iva.id_factura_compra = fact_comp.id_factura_compra), 0)) AS total,
				
			IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
		FROM iv_factura_compra fact_comp
			INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
			INNER JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (fact_comp.id_moneda_tasa_cambio = moneda_extranjera.idmoneda) %s", $sqlBusq);
	} else if ($valCadBusq[0] == 3) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_comp.id_modulo IN (3)");
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_comp.id_factura NOT IN (SELECT id_factura_compra_cargo
																FROM cp_factura_gasto fact_comp_gasto
																	INNER JOIN cp_factura fact_comp ON (fact_comp_gasto.id_factura = fact_comp.id_factura)
																WHERE fact_comp.activa IS NOT NULL
																	AND id_factura_compra_cargo IS NOT NULL)");
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
			OR prov.nombre LIKE %s
			OR fact_comp.numero_control_factura LIKE %s
			OR fact_comp.numero_factura_proveedor LIKE %s)",
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"));
		}
		
		$query = sprintf("SELECT
			fact_comp.id_factura,
			fact_comp.fecha_origen,
			fact_comp.fecha_factura_proveedor,
			fact_comp.numero_factura_proveedor,
			prov.nombre AS nombre_proveedor,
			
			(CASE id_modulo
				WHEN 2 THEN
					(SELECT COUNT(fact_comp_det_unidad.id_factura) FROM cp_factura_detalle_unidad fact_comp_det_unidad
					WHERE fact_comp_det_unidad.id_factura = fact_comp.id_factura)
					+
					(SELECT COUNT(fact_comp_det_acc.id_factura) FROM cp_factura_detalle_accesorio fact_comp_det_acc
					WHERE fact_comp_det_acc.id_factura = fact_comp.id_factura)
				ELSE
					(SELECT COUNT(fact_comp_det.id_factura) FROM cp_factura_detalle fact_comp_det
					WHERE fact_comp_det.id_factura = fact_comp.id_factura)
			END) AS cant_items,
			
			(IFNULL(fact_comp.subtotal_factura, 0)
				- IFNULL(fact_comp.subtotal_descuento, 0)
				+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
						FROM cp_factura_gasto fact_compra_gasto
						WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
							AND fact_compra_gasto.id_modo_gasto = 1
							AND fact_compra_gasto.afecta_documento = 1), 0)
				+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
						FROM cp_factura_iva fact_compra_iva
						WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)) AS total,
			
			moneda_local.abreviacion AS abreviacion_moneda,
			
			(SELECT retencion.idRetencionCabezera
			FROM cp_retenciondetalle retencion_det
				INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
			WHERE retencion_det.idFactura = fact_comp.id_factura
			LIMIT 1) AS idRetencionCabezera
		FROM cp_factura fact_comp
			INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
			LEFT JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda) %s", $sqlBusq);
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "58%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "6%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "12%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($valCadBusq[0]) {
			case 0 :
				$onclick = sprintf("validarInsertarFacturaCompra('%s');",
					$row['id_factura_compra']); break;
			case 3 :
				$onclick = sprintf("xajax_asignarFacturaCargo(xajax.getFormValues('frmListaRegistroCompra'), xajax.getFormValues('frmExpediente'), xajax.getFormValues('frmFacturaGasto'), '%s');",
					$row['id_factura']); break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarFacturaCompra%s\" onclick=\"%s\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$onclick);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cant_items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda']."</td>";
			$htmlTb .= "<td>";
			if ($valCadBusq[0] == 3) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../compras/reportes/ga_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_view.png\" title=\"Ver\"/>",
					$row['id_factura']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($valCadBusq[0] == 3 && $row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Comprobante de Retención\"/>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";

			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaRegistroCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarFacturaCargo");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarExpediente");
$xajax->register(XAJAX_FUNCTION,"buscarRegistroCompra");
$xajax->register(XAJAX_FUNCTION,"calcularExpediente");
$xajax->register(XAJAX_FUNCTION,"cargarExpediente");
$xajax->register(XAJAX_FUNCTION,"cargarFacturaCargo");
$xajax->register(XAJAX_FUNCTION,"eliminarCargo");
$xajax->register(XAJAX_FUNCTION,"eliminarFacturaCompra");
$xajax->register(XAJAX_FUNCTION,"formExpediente");
$xajax->register(XAJAX_FUNCTION,"guardarExpediente");
$xajax->register(XAJAX_FUNCTION,"insertarCargo");
$xajax->register(XAJAX_FUNCTION,"insertarFacturaCompra");
$xajax->register(XAJAX_FUNCTION,"listaExpediente");
$xajax->register(XAJAX_FUNCTION,"listaOtrosCargos");
$xajax->register(XAJAX_FUNCTION,"listaFacturaCompra");

function insertarItemCargo($contFila1, $hddIdGastoCargo = "", $idExpedienteDetalleCargo = "") {
	$contFila1++;
	
	if ($idExpedienteDetalleCargo > 0) {
		// BUSCA LOS DATOS DEL DETALLE DE LOS CARGOS DEL EXPEDIENTE
		$query = sprintf("SELECT 
			fact_comp_cargo.id_factura,
			gasto.id_gasto,
			gasto.nombre,
			fact_comp_cargo.fecha_origen,
			fact_comp_cargo.numero_factura_proveedor,
			fact_comp_cargo.numero_control_factura,
			prov.id_proveedor,
			prov.nombre AS nombre_proveedor,
			fact_comp_cargo.subtotal_factura,
			fact_comp_cargo.subtotal_descuento
		FROM iv_expediente_detalle_cargos expediente_det_cargo
			INNER JOIN cp_factura fact_comp_cargo ON (expediente_det_cargo.id_factura_compra_cargo = fact_comp_cargo.id_factura)
			INNER JOIN pg_gastos gasto ON (expediente_det_cargo.id_gasto = gasto.id_gasto)
			INNER JOIN cp_proveedor prov ON (fact_comp_cargo.id_proveedor = prov.id_proveedor)
		WHERE expediente_det_cargo.id_expediente_detalle_cargo = %s;",
			valTpDato($idExpedienteDetalleCargo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	}
	
	$txtFechaFacturaGasto = ($txtFechaFacturaGasto == "" && $totalRows > 0) ? date(spanDateFormat,strtotime($row['fecha_origen'])) : $txtFechaFacturaGasto;
	$txtNumeroFacturaGasto = ($txtNumeroFacturaGasto == "" && $totalRows > 0) ? $row['numero_factura_proveedor'] : $txtNumeroFacturaGasto;
	$txtNumeroControlFacturaGasto = ($txtNumeroControlFacturaGasto == "" && $totalRows > 0) ? $row['numero_control_factura'] : $txtNumeroControlFacturaGasto;
	$txtIdProvFacturaGasto = ($txtIdProvFacturaGasto == "" && $totalRows > 0) ? $row['id_proveedor'] : $txtIdProvFacturaGasto;
	$txtNombreProvFacturaGasto = ($txtNombreProvFacturaGasto == "" && $totalRows > 0) ? $row['nombre_proveedor'] : $txtNombreProvFacturaGasto;
	$hddSubTotalFacturaGasto = ($hddSubTotalFacturaGasto == "" && $totalRows > 0) ? ($row['subtotal_factura'] - $row['subtotal_descuento']) : $hddSubTotalFacturaGasto;
	$hddCondicionGasto = ($hddCondicionGasto == "" && $totalRows > 0) ? $row['id_condicion_gasto'] : $hddCondicionGasto;
	$hddIdFacturaCargo = ($hddIdFacturaCargo == "" && $totalRows > 0) ? $row['id_factura'] : $hddIdFacturaCargo;
	$hddIdGastoCargo = ($hddIdGastoCargo == "" && $totalRows > 0) ? $row['id_gasto'] : $hddIdGastoCargo;
	
	// BUSCA LOS DATOS DEL GASTO
	$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
		valTpDato($hddIdGastoCargo, "int"));
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGastos = mysql_fetch_assoc($rsGasto);
	
	if (!($hddCondicionGasto > 0)) {
		$hddCondicionGasto = ($rowGastos['asocia_documento'] == 1) ? 1 : 2; // 1 = Real, 2 = Estimado
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieCargo').before('".
		"<tr id=\"trItmCargo:%s\" align=\"left\" class=\"textoGris_11px\">".
			"<td title=\"trItmCargo:%s\"><input id=\"cbxItmCargo\" name=\"cbxItmCargo[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtFechaFacturaGasto%s\" name=\"txtFechaFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" size=\"10\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtNumeroFacturaGasto%s\" name=\"txtNumeroFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" size=\"20\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtNumeroControlFacturaGasto%s\" name=\"txtNumeroControlFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" size=\"20\" style=\"text-align:center\" value=\"%s\"/></td>".
			"<td><table cellpadding=\"0\" cellspacing=\"0\">".
				"<tr>".
					"<td><input type=\"hidden\" id=\"txtIdProvFacturaGasto%s\" name=\"txtIdProvFacturaGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/></td>".
					"<td>&nbsp;</td>".
					"<td><input type=\"text\" id=\"txtNombreProvFacturaGasto%s\" name=\"txtNombreProvFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" size=\"45\" style=\"text-align:left\" value=\"%s\"/></td>".
				"</tr>".
				"</table></td>".
			"<td><input type=\"text\" id=\"hddSubTotalFacturaGasto%s\" name=\"hddSubTotalFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td>".
				"<a class=\"modalImg\" id=\"btnEditarCargo:%s\" rel=\"#divFlotante2\">".
					"<button type=\"button\" title=\"Asignar Dcto.\"><img src=\"../img/iconos/page_link.png\"/></button>".
				"</a>".
				"<input type=\"hidden\" id=\"hddCondicionGasto%s\" name=\"hddCondicionGasto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaCargo%s\" name=\"hddIdFacturaCargo%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdGastoCargo%s\" name=\"hddIdGastoCargo%s\" value=\"%s\"/>".
			"</td>".
		"</tr>');
		
		byId('btnEditarCargo:%s').onclick = function() {
			abrirDivFlotante2(this, 'tblFacturaOtroCargo', 3, '%s');
		}",
		$contFila1,
			$contFila1, $contFila1,
				 $contFila1,
			utf8_encode($rowGastos['nombre']),
			$contFila1, $contFila1, $txtFechaFacturaGasto,
			$contFila1, $contFila1, $txtNumeroFacturaGasto,
			$contFila1, $contFila1, $txtNumeroControlFacturaGasto,
				$contFila1, $contFila1, $txtIdProvFacturaGasto,
				$contFila1, $contFila1, $txtNombreProvFacturaGasto,
			$contFila1, $contFila1, number_format($hddSubTotalFacturaGasto, 2, ".", ","),
			$contFila1,
				$contFila1, $contFila1, $hddCondicionGasto,
				$contFila1, $contFila1, $hddIdFacturaCargo,
				$contFila1, $contFila1, $hddIdGastoCargo,
		
		$contFila1,
			$contFila1);
	
	return array(true, $htmlItmPie, $contFila1, $arrayObjUbicacion);
}

function insertarItemFacturaCompra($contFila, $hddIdFacturaCompra = "", $idExpedienteDetalleFactura = "") {
	$contFila++;
	
	// BUSCA LOS DATOS DE LA FACTURA DE COMPRA
	$query = sprintf("SELECT 
		fact_comp.id_factura_compra,
		fact_comp.fecha_origen,
		fact_comp.fecha_factura_proveedor,
		fact_comp.numero_factura_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(SELECT COUNT(fact_compra_det.id_factura_compra) AS items
		FROM iv_factura_compra_detalle fact_compra_det
		WHERE fact_compra_det.id_factura_compra = fact_comp.id_factura_compra) AS items,
			
		(IFNULL(fact_comp.subtotal_factura, 0)
			- IFNULL(fact_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM iv_factura_compra_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura_compra = fact_comp.id_factura_compra
						AND fact_compra_gasto.id_modo_gasto = 1
						AND fact_compra_gasto.afecta_documento = 1), 0)
			+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM iv_factura_compra_iva fact_compra_iva
					WHERE fact_compra_iva.id_factura_compra = fact_comp.id_factura_compra), 0)
		) AS total,
			
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
	FROM iv_factura_compra fact_comp
		INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
		INNER JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (fact_comp.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	WHERE fact_comp.id_factura_compra = %s;",
		valTpDato($hddIdFacturaCompra, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
		
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieFactura').before('".
		"<tr id=\"trItmFactura:%s\" align=\"left\" class=\"textoGris_11px\">".
			"<td title=\"trItmFactura:%s\"><input id=\"cbxItmFactura\" name=\"cbxItmFactura[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td></td>".
			"<td></td>".
			"<td></td>".
			"<td>%s</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"right\"><input type=\"text\" id=\"hddTotalFactura%s\" name=\"hddTotalFactura%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaCompra%s\" name=\"hddIdFacturaCompra%s\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila,
			$contFila, $contFila,
			$contFila,
			date(spanDateFormat, strtotime($row['fecha_origen'])),
			date(spanDateFormat, strtotime($row['fecha_factura_proveedor'])),
			$row['numero_factura_proveedor'],
			$row['nombre_proveedor'],
			$row['items'],
			$contFila, $contFila, number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda'],
				$contFila, $contFila, $hddIdFacturaCompra);
	
	return array(true, $htmlItmPie, $contFila, $arrayObjUbicacion);
}
?>