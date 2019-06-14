<?php


function asignarClaveMov($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	
	$Result1 = buscarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$objResponse->assign("txtNumeroControlNotaCredito","value",($Result1[1]));
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
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
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
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
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdNotaCredito'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
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
			$txtSubTotal += str_replace(",","",$frmListaArticulo['txtCantItm'.$valor]) * str_replace(",","",$frmListaArticulo['txtPrecioItm'.$valor]);;
		}
	} else {
		$txtSubTotal = round(str_replace(",","",$frmTotalDcto['txtSubTotal']),2);
	}
	
	$txtDescuento = round(str_replace(",","",$frmTotalDcto['txtDescuento']),2);
	$txtSubTotalDescuento = round(str_replace(",","",$frmTotalDcto['txtSubTotalDescuento']),2);
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IMPUESTO
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",","",$frmListaArticulo['txtCantItm'.$valor]) * str_replace(",","",$frmListaArticulo['txtPrecioItm'.$valor]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
			
			$hddTotalDescuentoItm = ($hddTotalDescuentoItm > 0 || !($txtSubTotal > 0)) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						
						$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = ($hddIdIvaItm > 0) ? $hddIdIvaItm : -1;
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
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
					$txtTotalExento += $txtTotalNetoItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
			
			$objResponse->assign("txtTotalItm".$valor,"value",number_format($txtTotalItm, 2, ".", ","));
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
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
	
	$txtSubTotalDescuento = str_replace(",","",$frmTotalDcto['txtSubTotalDescuento']);
	$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
	$txtTotalFactura = $txtSubTotal - $txtSubTotalDescuento + $subTotalIva;
	
	$objResponse->assign("txtSubTotal","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento","value",number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalFactura","value",number_format($txtTotalFactura, 2, ".", ","));
	$objResponse->assign("txtTotalExento","value",number_format($txtTotalExento, 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($txtTotalExonerado, 2, ".", ","));
	
	return $objResponse;
}

function cargarDcto($idDocumento, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// ELIMINA LOS DETALLES QUE SE CARGARON EN PANTALLA
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
	$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
	$rsMoneda = mysql_query($queryMoneda);
	if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMoneda = mysql_fetch_assoc($rsMoneda);
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT
		cxc_pedido.numero_pedido,
		cxc_fact.*,
		cxc_fact.idVendedor,
		clave_mov.id_clave_movimiento_contra,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		vw_pg_empleado.nombre_empleado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado)
		LEFT JOIN cj_cc_pedido cxc_pedido ON (cxc_pedido.id_pedido = cxc_fact.numeroPedido)
		LEFT JOIN pg_clave_movimiento clave_mov ON (cxc_fact.id_clave_movimiento = clave_mov.id_clave_movimiento)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cxc_fact.idFactura = %s;",
		valTpDato($idDocumento, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idCliente = $rowFact['idCliente'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura'];
	
	$idCondicionPago = $rowFact['condicionDePago'];
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { $objResponse->alert($Result1[1]); return $objResponse->script("byId('btnCancelar').click();"); }
	
	$queryPedidoDet = sprintf("SELECT * FROM cj_cc_factura_detalle_adm cxc_fact_det_adm
	WHERE cxc_fact_det_adm.id_factura = %s
	ORDER BY cxc_fact_det_adm.id_factura_detalle_adm ASC;",
		valTpDato($idDocumento, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayObj = NULL;
	while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
		$Result1 = insertarItemArticulo($contFila, $rowPedidoDet['id_factura_detalle_adm']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	// CARGA LOS DATOS DEL CLIENTE
	$queryCliente = sprintf("SELECT
		cliente.id AS id_cliente,
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
	
	if ($idCondicionPago >= 0) { // 0 = Credito, 1 = Contado
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
		
		if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
			$objResponse->assign("txtDiasCreditoCliente","value",$rowClienteCredito['diascredito']);
	
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "2", "0", "3"));
			
			$objResponse->call("selectedOption","lstTipoClave","2");
			$objResponse->script(sprintf("
			byId('lstTipoClave').onchange = function () {
				selectedOption(this.id,2);
				xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '".$idModulo."', '2', '0', '3');
			}"));
		} else {
			$objResponse->assign("txtDiasCreditoCliente","value","0");
			
			$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "2", "1", "3"));
			
			$objResponse->call("selectedOption","lstTipoClave","2");
			$objResponse->script(sprintf("
			byId('lstTipoClave').onchange = function () {
				selectedOption(this.id,2);
				xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '".$idModulo."', '2', '1', '3');
			}"));
		}
	}
	
	if ($rowFact['numero_pedido'] > 0) {
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", $idModulo, "2", "", "", $rowFact['id_clave_movimiento_contra']));
		
		$objResponse->call("selectedOption","lstTipoClave","2");
		$objResponse->script(sprintf("
		byId('lstTipoClave').onchange = function () {
			selectedOption(this.id,2);
		}"));
		
		$objResponse->script("
		byId('lstClaveMovimiento').onchange = function () {
			selectedOption(this.id,".$rowFact['id_clave_movimiento_contra'].");
		}");
	}
	
	$nombreCondicionPago = ($idCondicionPago == 0) ? "Crédito" : "Contado";
	$objResponse->assign("hddTipoPago","value",$idCondicionPago);
	$objResponse->assign("txtTipoPago","value",$nombreCondicionPago);
	
	$objResponse->assign("tdGastos","innerHTML",formularioGastos(false,$idDocumento,"FACTURA_VENTA"));
	
	// DATOS DE LA NOTA DE CREDITO
	$objResponse->assign("txtFechaNotaCredito","value",date(spanDateFormat));
	
	// DATOS DEL CLIENTE
	$objResponse->assign("txtIdCliente","value",$rowCliente['id_cliente']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
	
	// DATOS DE LA FACTURA
	$objResponse->assign("txtIdEmpresa","value",$idEmpresa);
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowFact['nombre_empresa']));
	$objResponse->assign("txtIdFactura","value",$rowFact['idFactura']);
	$objResponse->assign("txtFechaFactura","value",date(spanDateFormat, strtotime($rowFact['fechaRegistroFactura'])));
	$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat, strtotime($rowFact['fechaVencimientoFactura'])));
	$objResponse->assign("txtNumeroFactura","value",$rowFact['numeroFactura']);
	$objResponse->assign("txtNumeroControlFactura","value",$rowFact['numeroControl']);
	$objResponse->assign("hddIdPedido","value",$rowFact['numeroPedido']);
	$objResponse->assign("txtNumeroPedido","value",$rowFact['numero_pedido']);
	$objResponse->assign("hddFechaPedido","value",date("Y-m-d", strtotime($rowPedido['fecha'])));
	$objResponse->assign("hddIdMoneda","value",$rowMoneda['idmoneda']);
	$objResponse->assign("txtMoneda","value",utf8_encode($rowMoneda['descripcion']));
	$objResponse->assign("hddIdEmpleado","value",$rowFact['idVendedor']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowFact['nombre_empleado']));
	$objResponse->assign("txtTipoClaveFactura","value","3.- VENTA");
	$objResponse->assign("hddIdClaveMovimientoFactura","value",$rowFact['id_clave_movimiento']);
	$objResponse->assign("txtClaveMovimientoFactura","value",utf8_encode($rowFact['descripcion_clave_movimiento']));
	$objResponse->assign("txtDescuento","value",$rowFact['porcentaje_descuento']);
	$objResponse->assign("txtSubTotalDescuento","value",$rowFact['descuentoFactura']);
	
	$objResponse->script("xajax_asignarClaveMov(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "onchange=\"xajax_asignarClaveMov(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\"") {
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
				
				//$objResponse->loadCommands(bloquearLstClaveMovimiento($rowClaveMov['id_clave_movimiento']));
			}
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $bloquearForm = "") {
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	if (!xvalidaAcceso($objResponse,"cj_devolucion_admon_form","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	$idFactura = $frmDcto['txtIdFactura'];
	$idPedido = $frmDcto['hddIdPedido'];
	$hddPagaImpuesto = $frmDcto['hddPagaImpuesto'];
	$idClaveMovimiento = $frmDcto['lstClaveMovimiento'];
	
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
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
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// VERIFICA SI EL DOCUMENTO YA HA SIDO DEVUELTO
	$queryVerif = sprintf("SELECT * FROM cj_cc_notacredito
	WHERE idDocumento = %s
		AND tipoDocumento LIKE 'FA'
		AND idDepartamentoNotaCredito IN (%s);",
		valTpDato($idFactura, "int"),
		valTpDato($idModulo, "int"));
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if (mysql_num_rows($rsVerif) > 0) {
		return $objResponse->alert('Este documento ya ha sido devuelto');
	}
	
	if ($idPedido > 0) {
		$updateSQL = sprintf("UPDATE cj_cc_pedido SET estado_pedido = 4 WHERE id_pedido = %s;",
			valTpDato($idPedido, "int"));// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }	
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
	
	if ($rowNumeracion['numero_actual'] == "") { return $objResponse->alert("No se ha configurado la numeracion de notas de credito"); }
	
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$numeroActualNota = $numeroActual;
		
	// INSERTA LOS DATOS DE LA NOTA DE CRÉDITO
	$insertSQL = sprintf("INSERT INTO cj_cc_notacredito (numeracion_nota_credito, numeroControl, id_empresa, idCliente, id_clave_movimiento, id_empleado_vendedor, idDepartamentoNotaCredito, montoNetoNotaCredito, saldoNotaCredito, fechaNotaCredito, observacionesNotaCredito, estadoNotaCredito, idDocumento, tipoDocumento, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, baseimponibleNotaCredito, porcentajeIvaNotaCredito, ivaNotaCredito, base_imponible_iva_lujo, porcentajeIvaDeLujoNotaCredito, ivaLujoNotaCredito, montoExentoCredito, montoExoneradoCredito, aplicaLibros, id_orden, estatus_nota_credito, impreso, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActualNota, "text"),
		valTpDato($frmDcto['txtNumeroControlNotaCredito'], "text"),
		valTpDato($idEmpresa, "int"),
		valTpDato($rowFact['idCliente'], "int"),
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($rowFact['idVendedor'], "int"),
		valTpDato($idModulo, "int"), // 0 = Repuesto, 1 = Sevicios, 2 = Autos, 3 = Administracion
		valTpDato($frmTotalDcto['txtTotalFactura'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalFactura'], "real_inglesa"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($rowFact['idFactura'], "int"),
		valTpDato("FA", "text"),
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
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato("", "int"),
		valTpDato(2, "int"), // 1 = Aprobada, 2 = Aplicada
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idNotaCredito,
		$idModulo,
		"NOTA CREDITO");
	
	// INSERTA EL DETALLE DE LA NOTA DE CRÉDITO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (strlen($frmListaArticulo['hddIdFactDet'.$valor]) > 0) {
				$queryFactDet = sprintf("SELECT * FROM cj_cc_factura_detalle_adm WHERE id_factura_detalle_adm = %s;",
					valTpDato($frmListaArticulo['hddIdFactDet'.$valor], "int"));
				$rsFactDet = mysql_query($queryFactDet);
				if (!$rsFactDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowFactDet = mysql_fetch_assoc($rsFactDet);
				
				$idConcepto = $rowFactDet['id_concepto'];
				
				// CANTIDADES DEL DETALLE
				$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
				$cantDevuelta = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
				$cantPendiente = $cantPedida - $cantDevuelta;
				
				// CANTIDADES DEL DETALLE DE LA NOTA DE CRÉDITO
				$cantPedidaNotaCred = doubleval($cantDevuelta);
				$cantDevueltaNotaCred = doubleval($cantDevuelta);
				$cantPendienteNotaCred = $cantPedidaNotaCred - $cantDevueltaNotaCred;
				$precioUnitario = $rowFactDet['precio_unitario'];
				$costoUnitario = $rowFactDet['costo_unitario'];
				$hddIdIvaItm = "";
				$hddIvaItm = 0;
				if (isset($arrayObjIvaItm)) {// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					foreach ($arrayObjIvaItm as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
						}
					}
				}
				
				// EDITA EL ESTADO DEL DETALLE DE LA FACTURA
				$updateSQL = sprintf("UPDATE cj_cc_factura_detalle_adm SET 
					devuelto = (devuelto + %s)
				WHERE id_factura_detalle_adm = %s;",
					valTpDato($cantDevueltaNotaCred, "real_inglesa"),
					valTpDato($frmListaArticulo['hddIdFactDet'.$valor], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA EL DETALLE DE LA NOTA DE CRÉDITO
				$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_adm (id_nota_credito, id_concepto, descripcion, cantidad, precio_unitario, costo_unitario, id_iva, iva)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idNotaCredito, "int"),
					valTpDato($idConcepto, "int"),
					valTpDato($rowFactDet['descripcion'], "text"),
					valTpDato($cantPedidaNotaCred, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($rowFactDet['id_iva'], "int"),
					valTpDato($rowFactDet['iva'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idNotaCreditoDetalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
							
							$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_adm_impuesto (id_nota_credito_detalle_adm, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($idNotaCreditoDetalle, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
						}
					}
				}
			}
		}
	}
	
	// INSERTA LOS GASTOS DE LA NOTA DE CRÉDITO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$idGasto = $frmTotalDcto['hddIdGasto'.$valor];
			
			$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
			$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
			$hddIdIvaItm = "";
			$hddIvaItm = 0;
			$hddEstatusIvaGasto = "";
			if ($hddPagaImpuesto == 1) {
				$hddIdIvaGasto = $frmTotalDcto['hddIdIvaGasto'.$valor];
				$hddIvaGasto = $frmTotalDcto['hddIvaGasto'.$valor];
				$hddEstatusIvaGasto = $frmTotalDcto['hddEstatusIvaGasto'.$valor];
			}
			
			// BUSCA LOS DATOS DEL GASTO FACTURA
			$queryGastoFact = sprintf("SELECT 
				cxc_fact_gasto.monto
			FROM cj_cc_factura_gasto cxc_fact_gasto
			WHERE cxc_fact_gasto.id_gasto = %s
				AND cxc_fact_gasto.id_factura = %s;",
				valTpDato($idGasto, "int"),
				valTpDato($rowFact['idFactura'], "int"));
			$rsGastoFact = mysql_query($queryGastoFact);
			if (!$rsGastoFact) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowGastoFact = mysql_fetch_assoc($rsGastoFact);
			
			// BUSCA LOS TOTALES DEL GASTO ENTRE TODAS LAS DEVOLUCIONES DE LA MISMA FACTURA
			$queryGastoNotaCred = sprintf("SELECT 
				SUM(nota_cred_gasto.monto) AS total_monto_gasto
			FROM cj_cc_nota_credito_gasto nota_cred_gasto
				INNER JOIN cj_cc_notacredito nota_cred ON (nota_cred_gasto.id_nota_credito = nota_cred.idNotaCredito)
			WHERE nota_cred_gasto.id_gasto = %s
				AND nota_cred.idDocumento = %s
				AND nota_cred.tipoDocumento LIKE 'FA';",
				valTpDato($idGasto, "int"),
				valTpDato($rowFact['idFactura'], "int"));
			$rsGastoNotaCred = mysql_query($queryGastoNotaCred);
			if (!$rsGastoNotaCred) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowGastoNotaCred = mysql_fetch_assoc($rsGastoNotaCred);
			
			if (round($txtMontoGasto, 2) > (round($rowGastoFact['monto'],2) - round($rowGastoNotaCred['total_monto_gasto'],2))) {
				return $objResponse->alert("El monto ".number_format(round($txtMontoGasto, 2), 2, ".", ",")." del gasto es invalido debido a que es superior al facturado");
			}
			
			if (round($txtMontoGasto, 2) > 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_gasto (id_nota_credito, id_gasto, tipo, porcentaje_monto, monto, id_iva, iva, estatus_iva)
				SELECT %s, id_gasto, %s, %s, %s, %s, %s, %s FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idNotaCredito, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($hddIdIvaGasto, "int"),
					valTpDato($hddIvaGasto, "real_inglesa"),
					valTpDato($hddEstatusIvaGasto, "boolean"),
					valTpDato($idGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// INSERTA LOS IMPUESTOS DE LA NOTA DE CRÉDITO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			if ($frmTotalDcto['txtSubTotalIva'.$valor] > 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_iva (id_nota_credito, base_imponible, subtotal_iva, id_iva, iva, lujo)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idNotaCredito, "int"),
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
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
		valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// CALCULO DE LAS COMISIONES
	$Result1 = devolverComision($idNotaCredito);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	if (in_array($rowFact['estadoFactura'],array(0,2))) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
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
		
		if ($rowFact['estadoFactura'] == 0) { // 0 = No Cancelado
			if ($rowFact['saldoFactura'] == str_replace(",","",$frmTotalDcto['txtTotalFactura'])) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = str_replace(",","",$frmTotalDcto['txtTotalFactura']);
			} else if ($rowFact['saldoFactura'] > str_replace(",","",$frmTotalDcto['txtTotalFactura'])) {
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = str_replace(",","",$frmTotalDcto['txtTotalFactura']);
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
		} else if ($rowFact['estadoFactura'] == 2) { // 2 = Parcialmente Cancelado
			if ($rowFact['saldoFactura'] == str_replace(",","",$frmTotalDcto['txtTotalFactura'])) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = doubleval($rowFact['saldoFactura']);
			} else if ($rowFact['saldoFactura'] > str_replace(",","",$frmTotalDcto['txtTotalFactura'])) {
				$anuladaFactura = $rowFact['anulada'];
				
				$saldoNotaCred = str_replace(",","",$frmTotalDcto['txtTotalFactura']);
			} else if ($rowFact['saldoFactura'] < str_replace(",","",$frmTotalDcto['txtTotalFactura'])) {
				$anuladaFactura = "SI";
				
				$saldoNotaCred = doubleval($rowFact['saldoFactura']);
			}
			
			// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura SET
				anulada = %s
			WHERE idFactura = %s;",
				valTpDato($anuladaFactura, "text"), // NO, SI
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
				//"cbxPosicionPago" => $valor,
				//"hddIdPago" => $hddIdPago,
			"txtIdFormaPago" => 8, // 8 = Nota de Crédito
			"txtIdNumeroDctoPago" => $idNotaCredito,
				//"txtNumeroDctoPago" => $frmListaPagos['txtNumeroDctoPago'.$valor2],
				//"txtIdBancoCliente" => $frmListaPagos['txtIdBancoCliente'.$valor2],
				//"txtCuentaClientePago" => $frmListaPagos['txtCuentaClientePago'.$valor2],
				//"txtIdBancoCompania" => $frmListaPagos['txtIdBancoCompania'.$valor2],
				//"txtIdCuentaCompaniaPago" => $frmListaPagos['txtIdCuentaCompaniaPago'.$valor2],
				//"txtCuentaCompaniaPago" => asignarNumeroCuenta($frmListaPagos['txtIdCuentaCompaniaPago'.$valor2]),
				//"txtFechaDeposito" => $frmListaPagos['txtFechaDeposito'.$valor2],
				//"txtTipoTarjeta" => $frmListaPagos['txtTipoTarjeta'.$valor2],
				//"hddObjDetalleDeposito" => $frmDetallePago['hddObjDetalleDeposito'],
				//"hddObjDetalleDepositoFormaPago" => $frmDetallePago['hddObjDetalleDepositoFormaPago'],
				//"hddObjDetalleDepositoBanco" => $frmDetallePago['hddObjDetalleDepositoBanco'],
				//"hddObjDetalleDepositoNroCuenta" => $frmDetallePago['hddObjDetalleDepositoNroCuenta'],
				//"hddObjDetalleDepositoNroCheque" => $frmDetallePago['hddObjDetalleDepositoNroCheque'],
				//"hddObjDetalleDepositoMonto" => $frmDetallePago['hddObjDetalleDepositoMonto'],
			"txtMonto" => $saldoNotaCred,
			"cbxCondicionMostrar" => $frmListaDctoPagado['cbxCondicionMostrar'.$valorPago],
			"lstSumarA" => $frmListaDctoPagado['cbxMostrarContado'.$valorPago]
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
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
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
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		$objDcto = new Documento;
		$Result1 = $objDcto->actualizarNotaCredito($idNotaCredito);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
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
											- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
													WHERE cxc_ant.idCliente = cliente_emp.id_cliente
														AND cxc_ant.id_empresa = cliente_emp.id_empresa
														AND cxc_ant.estadoAnticipo IN (1,2)
														AND cxc_ant.estatus = 1), 0)
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
							- IFNULL((SELECT SUM(cxc_ant.saldoAnticipo) FROM cj_cc_anticipo cxc_ant
									WHERE cxc_ant.idCliente = cliente_emp.id_cliente
										AND cxc_ant.id_empresa = cliente_emp.id_empresa
										AND cxc_ant.estadoAnticipo IN (1,2)
										AND cxc_ant.estatus = 1), 0)
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
	
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("COMMIT;");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA CREDITO") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasRe")) { generarNotasRe($idNotaCredito,"",""); } break;
					case 1 : if (function_exists("generarNotasVentasSe")) { generarNotasVentasSe($idNotaCredito,"",""); } break;
					case 2 : if (function_exists("generarNotasVentasVe")) { generarNotasVentasVe($idNotaCredito,"",""); } break;
					case 3 : if (function_exists("generarNotasVentasAd")) { generarNotasVentasAd($idNotaCredito,"",""); } break;
					case 4 : if (function_exists("generarNotasVentasAl")) { generarNotasVentasAl($idNotaCredito,"",""); } break;
					case 5 : if (function_exists("generarNotasVentasFi")) { generarNotasVentasFi($idNotaCredito,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert("Aprobación de Nota de Crédito Guardada con Éxito");
	
	$objResponse->script("
	window.location.href='cj_devolucion_venta_list.php';
	verVentana('../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=".$idNotaCredito."', 960, 550);");
	
	return $objResponse;
}



$xajax->register(XAJAX_FUNCTION,"asignarClaveMov");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");

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
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	return array(true, "");
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

function insertarItemArticulo($contFila, $hddIdFacturaVentaDetalle = "", $idConcepto = "", $txtCantItm = "", $txtPrecioItm = "", $txtCostoItm = "", $abrevMonedaCostoUnitario = "", $idIva = "") {
	$contFila++;
	
	if ($hddIdFacturaVentaDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
		$queryPedidoDet = sprintf("SELECT * FROM cj_cc_factura_detalle_adm WHERE id_factura_detalle_adm = %s;",
			valTpDato($hddIdFacturaVentaDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		// BUSCA LOS IMPUESTOS DEL DETALLE DE LA FACTURA
		$queryPedidoDetImpuesto = sprintf("SELECT * FROM cj_cc_factura_detalle_adm_impuesto WHERE id_factura_detalle_adm = %s;",
			valTpDato($hddIdFacturaVentaDetalle, "int"));
		$rsPedidoDetImpuesto = mysql_query($queryPedidoDetImpuesto);
		if (!$rsPedidoDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$arrayDetImpuesto = NULL;
		while ($rowPedidoDetImpuesto = mysql_fetch_assoc($rsPedidoDetImpuesto)) {
			$arrayDetImpuesto[] = $rowPedidoDetImpuesto['id_impuesto'];
		}
		$idIva = (count($arrayDetImpuesto) > 0) ? implode(",",$arrayDetImpuesto) : -1;
	}
	
	$idConcepto = ($idConcepto == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_concepto'] : $idConcepto;
	$txtCantItm = ($txtCantItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $txtCantItm;
	$txtPrecioItm = ($txtPrecioItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $txtPrecioItm;
	$txtCostoItm = ($txtCostoItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['costo_unitario'] : $txtCostoItm;
	$idIva = ($idIva == "" && $totalRowsPedidoDet > 0) ? implode(",",$arrayDetImpuesto) : $idIva;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryConcepto = sprintf("SELECT * FROM cj_cc_concepto WHERE id_concepto = %s;",
		valTpDato($idConcepto, "int"));
	$rsConcepto = mysql_query($queryConcepto);
	if (!$rsConcepto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsConcepto = mysql_num_rows($rsConcepto);
	$rowConcepto = mysql_fetch_assoc($rsConcepto);
	
	$txtDescItm = ($txtDescItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['descripcion'] : $rowConcepto['descripcion'];
		
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (6,9,2)
		AND iva.idIva IN (%s);",
		valTpDato($idIva, "campo"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$contIva = 0;
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
		"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItmArticulo:%s\" class=\"textoGris_11px %s\">".
			"<td></td>".
			"<td id=\"tdNumItmArticulo:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDescItm%s\" name=\"txtDescItm%s\" class=\"inputSinFondo\" maxlength=\"255\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" onkeypress=\"return validarSoloNumerosReales(event);\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" onkeypress=\"return validarSoloNumerosReales(event);\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td align=\"center\" title=\"trItmArticulo:%s\"><a id=\"aEliminar:%s\" style=\"display:none\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" readonly=\"readonly\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdFactDet%s\" name=\"hddIdFactDet%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtCantItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtPrecioItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('lstIvaItm%s').onchange = function() {
			xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('aEliminar:%s').onclick = function() {
			xajax_eliminarArticulo('%s', xajax.getFormValues('frmListaArticulo'));
		}",
		$contFila, $clase,
			$contFila, $contFila,
			$rowConcepto['codigo_concepto'],
			$contFila, $contFila, utf8_encode($txtDescItm),
			$contFila, $contFila, number_format($txtCantItm, 2, ".", ","),
			$contFila, $contFila, number_format($txtPrecioItm, 2, ".", ","),
			$ivaUnidad,
			$contFila, $contFila, number_format($txtCantItm * $txtPrecioItm, 2, ".", ","),
			$contFila, $contFila,
				$contFila,
				$contFila, $contFila, $idConcepto,
				$contFila, $contFila, $hddIdFacturaVentaDetalle,
		
		$contFila,
		$contFila,
		$contFila,
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