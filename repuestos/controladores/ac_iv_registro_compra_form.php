<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function aprobarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaLocal = $frmTotalDcto['cbxIvaLocal'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idFacturaCompra = $frmDcto['txtIdFactura'];
	$idNotaCargo = $frmDcto['txtIdNotaCargo'];
	$idExpediente = $frmTotalDcto['hddIdExpediente'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = str_replace(",", "", $frmDcto['txtTasaCambio']);
	
	if (($idModoCompra == 1 && !xvalidaAcceso($objResponse,"iv_registro_compra_nacional","insertar"))
	|| ($idModoCompra == 2 && !xvalidaAcceso($objResponse,"iv_registro_compra_importacion","insertar"))) { return $objResponse; }
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig17 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 17 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig17 = mysql_query($queryConfig17);
	if (!$rsConfig17) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig17 = mysql_num_rows($rsConfig17);
	$rowConfig17 = mysql_fetch_assoc($rsConfig17);
	
	$valor = explode("|",$rowConfig17['valor']);
	
	$txtFechaRegistroCompra = date(spanDateFormat);
	$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
	if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
		if ((date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat))))
			&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
			if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
			|| date("m",strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
				$txtFechaRegistroCompra = $txtFechaProveedor;
			} else {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
			}
		} else if (!(date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat))))
			&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
			$objResponse->script("byId('cbxFechaRegistro').checked = false;");
			$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
		} else {
			$txtFechaRegistroCompra = $txtFechaProveedor;
		}
	} else if ($frmDcto['cbxFechaRegistro'] == 1) {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
		return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
	}
	
	// VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($valor > 0 && strlen($frmListaArticulo['hddIdCasillaItm'.$valor]) == "") {
				return $objResponse->alert("Existen artículos los cuales no tienen ubicación asignada");
			}
		}
	}
	
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
			if ($frmTotalDcto['lstGastoItem'] == 0 && $frmTotalDcto['hddIdTipoMedida'.$valor] == 1 && $txtMedidaGasto > 0) { // 0 = No, 1 = Si // 1 = Peso
				if ($txtMedidaGasto != str_replace(",", "", $frmListaArticulo['txtTotalPesoItem'])) {
					return $objResponse->alert("El Peso Total por Item no coincide con el Peso Total");
				}
			}
		}
	}
	
	// CALCULA EL SUBTOTAL
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
			
			$subTotalDescuentoItm += $txtCantRecibItm * $hddMontoDescuentoItm;
		}
	}
	
	$txtSubTotal = str_replace(",", "", $frmTotalDcto['txtSubTotal']);
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$txtTotalOrden = str_replace(",", "", $frmTotalDcto['txtTotalOrden']);
	$txtTotalSaldoOrden = str_replace(",", "", $frmTotalDcto['txtTotalOrden']);
	$txtTotalExento = str_replace(",", "", $frmTotalDcto['txtTotalExento']);
	$txtTotalExonerado = str_replace(",", "", $frmTotalDcto['txtTotalExonerado']);
	
	mysql_query("START TRANSACTION;");
	
	if ($idModoCompra == 2) { // 2 = Importacion
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$txtGastosItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]);
				$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
				$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
				
				// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
				$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal;
				
				$txtTotalItm = $txtTotalItm - $totalDescuentoItm;
				
				// CALCULA LOS DATOS DE IMPORTACION
				$precioTotalFOB = $txtTotalItm;
				$totalCIF = $precioTotalFOB + $txtGastosItm;
				$totalPrecioCIF = $totalCIF * $txtTasaCambio;
				$tarifaAdValorem = ($totalPrecioCIF * str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor])) / 100;
				$tarifaAdValoremDif = ($totalPrecioCIF * str_replace(",", "", $frmListaArticulo['lstTarifaAdValoremDif'.$valor])) / 100;
				$gastosImportNacItm = str_replace(",", "", $frmListaArticulo['hddGastosImportNacItm'.$valor]);
				
				$subTotalArancel += $tarifaAdValorem;
				$subTotalGastosImportNacItm += $gastosImportNacItm;
			}
		}
		$txtSubTotal = (str_replace(",", "", $frmTotalDcto['txtSubTotal']) * $txtTasaCambio) + $subTotalArancel;
		$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']) * $txtTasaCambio;
		$txtTotalOrden = (str_replace(",", "", $frmTotalDcto['txtTotalOrden']) * $txtTasaCambio) + $subTotalArancel + $subTotalGastosImportNacItm;
		$txtTotalSaldoOrden = (str_replace(",", "", $frmTotalDcto['txtTotalOrden']) * $txtTasaCambio);
		$txtTotalExento = str_replace(",", "", $frmTotalDcto['txtTotalExento']) * $txtTasaCambio;
		$txtTotalExonerado = str_replace(",", "", $frmTotalDcto['txtTotalExonerado']) * $txtTasaCambio;
	}
	
	$queryProv = sprintf("SELECT prov.credito, prov_cred.*
	FROM cp_proveedor prov
		LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	WHERE prov.id_proveedor = %s;",
		valTpDato($frmDcto['txtIdProv'], "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$txtDiasCreditoProv = ($rowProv['diascredito'] > 0) ? $rowProv['diascredito'] : 0;
	
	// SI EL PROVEEDOR ES A CREDITO SE LE SUMA LA CANTIDAD DE DIAS DE CREDITO PARA LA FECHA DE VENCIMIENTO
	$fechaVencimiento = ($rowProv['credito'] == "Si") ? suma_fechas(spanDateFormat,$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
	
	// INSERTA LOS DATOS DE LA FACTURA
	$insertSQL = sprintf("INSERT INTO cp_factura (id_empresa, id_modo_compra, numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_moneda, id_modulo, estatus_factura, observacion_factura, tipo_pago, monto_exento, monto_exonerado, subtotal_factura, porcentaje_descuento, subtotal_descuento, total_cuenta_pagar, saldo_factura, aplica_libros, activa, fecha_registro, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idModoCompra, "int"),
		valTpDato($frmDcto['txtNumeroFacturaProveedor'], "text"),
		valTpDato($frmDcto['txtNumeroControl'], "text"),
		valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
		valTpDato($frmDcto['txtIdProv'], "int"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
		valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
		valTpDato($idMonedaLocal, "int"),
		valTpDato($idModulo, "int"),
		valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
		valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Contado, 1 = Credito
		valTpDato($txtTotalExento, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($txtSubTotal, "real_inglesa"),
		valTpDato($txtDescuento, "real_inglesa"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtTotalSaldoOrden, "real_inglesa"),
		valTpDato($txtTotalSaldoOrden, "real_inglesa"),
		valTpDato(1, "boolean"), // 0 = No, 1 = Si
		valTpDato(1, "int"), // Null = Anulada, 1 = Activa
		valTpDato("NOW()", "campo"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) {
		if (mysql_errno() == 1062) {
			return $objResponse->alert("Registro de Compra Duplicado"."\n\nLine: ".__LINE__);
		} else {
			return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	$idFactura = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idFactura,
		$idModulo,
		"COMPRA");
	
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($frmDcto['lstClaveMovimiento'], "int"),
		valTpDato($idFactura, "int"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmDcto['txtIdProv'], "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato("NOW()", "campo"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmDcto['rbtTipoPago'], "boolean"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idMovimiento = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
		
	// INSERTA LOS DATOS PARA EL BLOQUEO DE VENTA
	$insertSQL = sprintf("INSERT INTO iv_bloqueo_venta (id_factura_compra, id_empleado) 
	VALUE (%s, %s);",
		valTpDato($idFactura, "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idBloqueoVenta = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DE LA FACTURA
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idArticuloOrg = $frmListaArticulo['hddIdArticuloItm'.$valor];
			$idArticuloSust = $frmListaArticulo['hddIdArticuloSustItm'.$valor];
			$idArticulo = ($idArticuloSust > 0) ? $idArticuloSust : $idArticuloOrg;
			$idCasilla = $frmListaArticulo['hddIdCasillaItm'.$valor];
			$idClienteItm = $frmListaArticulo['hddIdClienteItm'.$valor];
			
			$idPedidoDet = $frmListaArticulo['hddIdPedidoDetItm'.$valor];
			
			// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
			$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle WHERE id_pedido_compra_detalle = %s;",
				valTpDato($idPedidoDet, "int"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			
			$idPedido = $rowPedidoDet['id_pedido_compra'];
			
			$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
			$cantRecibida = $cantPedida;
			$cantPendiente = $cantPedida - $cantRecibida;
			
			switch ($idModoCompra) {
				case 1 : // 1 = Nacional
					$txtGastosItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]);
					$txtOtrosCargosItm = str_replace(",", "", $frmListaArticulo['hddGastosImportItm'.$valor]);
				
					$txtCostoItmUnit = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
					$txtGastosItmUnit = $txtGastosItm / $cantRecibida;
					$txtOtrosCargosItmUnit = $txtOtrosCargosItm / $cantRecibida;
					$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
					$hddPorcDescuentoItm = str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$valor]);
					$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
					$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
					$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
					
					$precioTotal = $txtTotalItm + $txtGastosItm + $txtOtrosCargosItm;
					$diferenciaCambiariaTotal = 0;
					$precioUnitario = $precioTotal / $cantRecibida;
					$diferenciaCambiariaUnit = 0;
					$totalPrecioUnitario = $precioUnitario + $diferenciaCambiariaUnit;
					
					$costoUnitItmConGasto = $txtCostoItmUnit + $txtGastosItmUnit;
					
					// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
					$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal;
					
					$porcDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddPorcDescuentoItm : $txtDescuento; 
					$montoDescuentoUnitItm = ($subTotalDescuentoItm > 0) ? $hddMontoDescuentoItm : $totalDescuentoItm / $cantRecibida;
					
					$costoUnitItmFinal = $totalPrecioUnitario - $montoDescuentoUnitItm;
					break;
				case 2 : // 2 = Importacion
					$txtGastosFOBItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]);
					$txtOtrosCargosItm = str_replace(",", "", $frmListaArticulo['hddGastosImportItm'.$valor]);
					
					$txtCostoFOBItmUnit = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
					$txtGastosFOBItmUnit = $txtGastosFOBItm / $cantRecibida;
					$hddPorcDescuentoItm = str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$valor]);
					$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
					$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
					$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
					
					// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
					$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * ($txtSubTotalDescuento / $txtTasaCambio)) / ($txtSubTotal / $txtTasaCambio);
					
					$porcDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddPorcDescuentoItm : $txtDescuento; 
					$montoDescuentoUnitItm = ($subTotalDescuentoItm > 0) ? $hddMontoDescuentoItm : $totalDescuentoItm / $cantRecibida;
					
					// CALCULA LOS DATOS DE IMPORTACION
					$costoTotalFOB = $txtTotalItm - $totalDescuentoItm;
					$totalCIF = $costoTotalFOB + $txtGastosFOBItm;
					$totalPrecioCIF = $totalCIF * $txtTasaCambio;
					$tarifaAdValorem = ($totalPrecioCIF * str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor])) / 100;
					$tarifaAdValoremDif = ($totalPrecioCIF * str_replace(",", "", $frmListaArticulo['lstTarifaAdValoremDif'.$valor])) / 100;
					$gastosImportNacItm = str_replace(",", "", $frmListaArticulo['hddGastosImportNacItm'.$valor]);
					$precioTotal = $totalPrecioCIF + $tarifaAdValorem + $gastosImportNacItm + $txtOtrosCargosItm;
					$diferenciaCambiariaTotal = $totalCIF * str_replace(",", "", $frmTotalDcto['txtDiferenciaCambiaria']);
					$precioUnitario = $precioTotal / $cantRecibida;
					$diferenciaCambiariaUnit = ($totalCIF * str_replace(",", "", $frmTotalDcto['txtDiferenciaCambiaria'])) / $cantRecibida;
					$totalPrecioUnitario = $precioUnitario + $diferenciaCambiariaUnit;
					
					// TRANSFORMA AL TIPO DE MONEDA NACIONAL
					$txtCostoItmUnit = ($txtCostoFOBItmUnit * $txtTasaCambio) + ($tarifaAdValorem / $cantRecibida);
					$txtGastosItmUnit = ($txtGastosFOBItmUnit * $txtTasaCambio) + ($gastosImportNacItm / $cantRecibida);
					$txtOtrosCargosItmUnit = $txtOtrosCargosItm / $cantRecibida;
					$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
					$hddPorcDescuentoItm = str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$valor]);
					$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]) * $txtTasaCambio;
					$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]) * $txtTasaCambio;
					$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]) * $txtTasaCambio;
					
					$costoUnitItmConGasto = $txtCostoItmUnit + $txtGastosItmUnit;
					
					// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
					$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal;
					
					$porcDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddPorcDescuentoItm : $txtDescuento; 
					$montoDescuentoUnitItm = ($subTotalDescuentoItm > 0) ? $hddMontoDescuentoItm : $totalDescuentoItm / $cantRecibida;
					
					$costoUnitItmFinal = $totalPrecioUnitario;
					break;
			}
			$hddPrecioJusto = 0;
			
			$estatusDet = ($cantPendiente == 0) ? 1 : 0; // 0 = En Espera, 1 = Recibido
			
			// REGISTRA EL DETALLE DE LA FACTURA
			$insertSQL = sprintf("INSERT INTO cp_factura_detalle (id_factura, id_pedido_compra, id_articulo, id_casilla, cantidad, pendiente, precio_unitario, tipo_descuento, porcentaje_descuento, subtotal_descuento, peso_unitario, tipo, id_cliente, estatus, por_distribuir)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato($idPedido, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($cantRecibida, "int"),
				valTpDato($cantPendiente, "int"),
				valTpDato($txtCostoItmUnit, "real_inglesa"),
				valTpDato($frmListaArticulo['hddTipoDescuentoItm'.$valor], "boolean"), // 0 = Porcentaje, 1 = Monto Fijo
				valTpDato($porcDescuentoItm, "real_inglesa"),
				valTpDato($montoDescuentoUnitItm, "real_inglesa"),
				valTpDato($txtPesoItm, "real_inglesa"),
				valTpDato($frmListaArticulo['hddTipoItm'.$valor], "int"), // 0 = Reposicion, 1 = Cliente
				valTpDato($frmListaArticulo['hddIdClienteItm'.$valor], "int"),
				valTpDato($estatusDet, "boolean"), // 0 = En Espera, 1 = Recibido
				valTpDato($cantRecibida, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$hddIdFacturaDetalleItm = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// ELIMINA LOS IMPUESTOS DEL DETALLE DE LA FACTURA
			$deleteSQL = sprintf("DELETE FROM cp_factura_detalle_impuesto WHERE id_factura_detalle = %s;",
				valTpDato($hddIdFacturaDetalleItm, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$contIvaItm = 0;
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					if ($valor1[0] == $valor) {
						$contIvaItm++;
						
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
						
						$insertSQL = sprintf("INSERT INTO cp_factura_detalle_impuesto (id_factura_detalle, id_impuesto, impuesto) 
						VALUE (%s, %s, %s);",
							valTpDato($hddIdFacturaDetalleItm, "int"),
							valTpDato($hddIdIvaItm, "int"),
							valTpDato($hddIvaItm, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
					}
				}
			}
			
			$hddIdIvaItm = ($contIvaItm == 1) ? $hddIdIvaItm : "";
			$hddIvaItm = ($contIvaItm == 1) ? $hddIvaItm : 0;
			
			// ALMACENA LA CANTIDAD FALTANTE POR DISTRIBUIR DENTRO DE LOS ALMACENES DE LA EMPRESA
			$updateSQL = sprintf("UPDATE cp_factura_detalle SET
				id_iva = %s,
				iva = %s,
				por_distribuir = %s
			WHERE id_factura_detalle = %s;",
				valTpDato($hddIdIvaItm, "int"),
				valTpDato($hddIvaItm, "real_inglesa"),
				valTpDato($cantRecibida - $cantRecibida, "int"),
				valTpDato($hddIdFacturaDetalleItm, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			if ($idModoCompra == 1) { // 1 = Nacional
				$costoUnitarioPedido = $txtCostoItmUnit;
			} else if ($idModoCompra == 2) { // 2 = Importacion
				$costoUnitarioPedido = $txtCostoFOBItmUnit;
				
				// REGISTRA EL DETALLE DE LA FACTURA
				$insertSQL = sprintf("INSERT INTO cp_factura_detalle_importacion (id_factura_detalle, id_arancel_familia, costo_unitario, gasto_unitario, costo_unitario_cambio, gasto_unitario_cambio, gastos_import_nac_unitario, gastos_import_unitario, porcentaje_grupo, porcentaje_grupo_diferencia)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($hddIdFacturaDetalleItm, "int"),
					valTpDato($frmListaArticulo['hddIdArancelFamiliaItm'.$valor], "int"),
					valTpDato($txtCostoFOBItmUnit, "real_inglesa"),
					valTpDato($txtGastosFOBItmUnit, "real_inglesa"),
					valTpDato($txtCostoFOBItmUnit * $txtTasaCambio, "real_inglesa"),
					valTpDato($txtGastosFOBItmUnit * $txtTasaCambio, "real_inglesa"),
					valTpDato(str_replace(",", "", $frmListaArticulo['hddGastosImportNacItm'.$valor]) / $cantRecibida, "real_inglesa"),
					valTpDato(str_replace(",", "", $frmListaArticulo['hddGastosImportItm'.$valor]) / $cantRecibida, "real_inglesa"),
					valTpDato($frmListaArticulo['lstTarifaAdValorem'.$valor], "real_inglesa"),
					valTpDato($frmListaArticulo['lstTarifaAdValoremDif'.$valor], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) {
					if (mysql_errno() == 1048) {
						return $objResponse->alert("Verifique que los items tengan asignado su código arancelario"."\n\nLine: ".__LINE__);
					} else {
						return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					}
				}
				mysql_query("SET NAMES 'latin1';");
			}
			
			// ACTUALIZA EL PRECIO DEL DETALLE DEL PEDIDO
			$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
				id_articulo = %s,
				precio_unitario = %s
			WHERE id_pedido_compra_detalle = %s;",
				valTpDato($idArticulo, "int"),
				valTpDato($costoUnitarioPedido, "real_inglesa"),
				valTpDato($idPedidoDet, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, id_clave_movimiento, cantidad, precio, costo, costo_cargo, costo_diferencia, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idModulo, "int"),
				valTpDato($idFactura, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($frmDcto['lstClaveMovimiento'], "int"),
				valTpDato($cantRecibida, "real_inglesa"),
				valTpDato($costoUnitItmConGasto, "real_inglesa"),
				valTpDato($costoUnitItmConGasto, "real_inglesa"),
				valTpDato($txtOtrosCargosItmUnit, "real_inglesa"),
				valTpDato($diferenciaCambiariaUnit, "real_inglesa"),
				valTpDato($porcDescuentoItm, "real_inglesa"),
				valTpDato($montoDescuentoUnitItm, "real_inglesa"),
				valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
				valTpDato("NOW()", "campo"),
				valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
				valTpDato("SYSDATE()", "campo"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL DETALLE DEL MOVIMIENTO
			$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, costo, costo_cargo, costo_diferencia, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idMovimiento, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idKardex, "int"),
				valTpDato($cantRecibida, "real_inglesa"),
				valTpDato($costoUnitItmConGasto, "real_inglesa"),
				valTpDato($costoUnitItmConGasto, "real_inglesa"),
				valTpDato($txtOtrosCargosItmUnit, "real_inglesa"),
				valTpDato($diferenciaCambiariaUnit, "real_inglesa"),
				valTpDato($porcDescuentoItm, "real_inglesa"),
				valTpDato($montoDescuentoUnitItm, "real_inglesa"),
				valTpDato(0, "int"), // 0 = Unitario, 1 = Import
				valTpDato(0, "boolean"), // 0 = No, 1 = Si
				valTpDato($idMonedaLocal, "int"),
				valTpDato($idMonedaOrigen, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idMovimientoDetalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL DETALLE PARA EL BLOQUEO DE VENTA
			$idBloqueoVentaDetalle = "";
			if ($idClienteItm > 0) {
				$insertSQL = sprintf("INSERT INTO iv_bloqueo_venta_detalle (id_bloqueo_venta, id_articulo, id_casilla, cantidad_bloquear, cantidad, estatus, id_cliente)
				VALUE (%s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idBloqueoVenta, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($cantRecibida, "real_inglesa"),
					valTpDato($cantRecibida, "real_inglesa"),
					valTpDato(1, "int"), // 1 = Bloqueado, 2 = Desbloqueado
					valTpDato($idClienteItm, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idBloqueoVentaDetalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// REGISTRA EL COSTO DE COMPRA DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, precio_justo, id_moneda, fecha_registro)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idEmpresa, "int"),
				valTpDato($frmDcto['txtIdProv'], "int"),
				valTpDato($idArticulo, "int"),
				valTpDato("NOW()", "campo"),
				valTpDato($costoUnitItmFinal, "real_inglesa"),
				valTpDato($hddPrecioJusto, "real_inglesa"),
				valTpDato($idMonedaLocal, "int"),
				valTpDato("NOW()", "campo"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$hddIdArticuloCosto = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			if ($idModoCompra == 2) { // 1 = Nacional, 2 = Importacion
				$updateSQL = sprintf("UPDATE iv_articulos_costos SET
					costo_origen = %s,
					id_moneda_origen = %s
				WHERE id_articulo_costo = %s;",
					valTpDato($txtCostoFOBItm, "real_inglesa"),
					valTpDato($idMonedaOrigen, "int"),
					valTpDato($hddIdArticuloCosto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
			
			$arrayAlmacenCosto[$indice] = array($idKardex, $idMovimientoDetalle, $idBloqueoVentaDetalle, $hddIdArticuloCosto);
			
			// ACTIVA LA RELACION DEL ARTICULO CON LA EMPRESA Y LAS UBICACIONES
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArt = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsArt = mysql_query($queryArt);
			if (!$rsArt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArt = mysql_fetch_assoc($rsArt);
			
			// VERIFICA SI EL ARTICULO ESTA LIGADO A LA EMPRESA
			$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rsArtEmp = mysql_query($queryArtEmp);
			if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
			
			if ($totalRowsArtEmp > 0) { // SI EXISTE EL ARTICULO PARA LA EMPRESA
				if ($idArticuloSust > 0) { // SI SE AGREGO UN ARTICULO SUSTITUTO
					// SE LE QUITA LA UBICACION PREDETERMINADA AL ARTICULO QUE FUE SUSTITUIDO
					$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
						id_casilla_predeterminada = NULL,
						id_casilla_predeterminada_compra = NULL,
						sustituido = 1,
						estatus = NULL
					WHERE id_articulo = %s
						AND id_empresa = %s;",
						valTpDato($idArticuloOrg, "int"),
						valTpDato($idEmpresa, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			} else { // SI NO EXISTE EL ARTICULO PARA LA EMPRESA
				if ($idArticuloSust > 0) { // SI SE AGREGO UN ARTICULO SUSTITUTO
					$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
						id_casilla_predeterminada = NULL,
						id_casilla_predeterminada_compra = NULL,
						sustituido = 1,
						estatus = NULL
					WHERE id_articulo = %s
						AND id_empresa = %s;",
						valTpDato($idArticuloOrg, "int"),
						valTpDato($idEmpresa, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// SE LIGA EL ARTICULO SUSTITUTO CON LA EMPRESA
					$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, id_casilla_predeterminada, id_casilla_predeterminada_compra, clasificacion, estatus)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato("F", "text"),
						valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				} else {
					// COMO EL ARTICULO NO ESTA LIGADO CON LA EMPRESA, SE LIGARA
					$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, clasificacion, estatus)
					VALUE (%s, %s, %s, %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato("F", "text"),
						valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
			
			
			// VERIFICA SI HAY RELACION ENTRE ARTICULO Y LA UBICACION SELECCIONADA
			$queryArtAlmacen = sprintf("SELECT * FROM iv_articulos_almacen art_almacen
			WHERE art_almacen.id_articulo = %s
				AND art_almacen.id_casilla = %s;",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"));
			$rsArtAlmacen = mysql_query($queryArtAlmacen);
			if (!$rsArtAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtAlmacen = mysql_num_rows($rsArtAlmacen);
			$rowArtAlmacen = mysql_fetch_assoc($rsArtAlmacen);
			if ($totalRowsArtAlmacen > 0) {
				// ACTIVA LA UBICACION SELECCIONADA EN EL REGISTRO DE COMPRA
				$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
					estatus = 1
				WHERE id_articulo = %s
					AND id_casilla = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else {
				 // SI EL ARTICULO NO TIENE UBICACION, SE LE ASIGNA LA SELECCIONADA
				if ($idArticuloSust > 0) {
					// DESACTIVA LA UBICACION Y PONE EL ESTATUS SUSTITUIDO
					$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
						sustituido = 1,
						estatus = NULL
					WHERE id_articulo = %s
						AND id_casilla = %s;",
						valTpDato($idArticuloOrg, "int"),
						valTpDato($idCasilla, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// LE AGREGA LA UBICACION AL ARTICULO SUSTITUTO
					$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
					VALUE (%s, %s, %s);",
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				} else {
					$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
					VALUE (%s, %s, %s);",
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nCod Art: ".$rowArt['codigo_articulo']); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
			
			
			// VERIFICA SI EL ARTICULO TIENE UNA UBICACION PREDETERMINADA EN UN ALMACEN DE LA EMPRESA
			$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rsArtEmp = mysql_query($queryArtEmp);
			if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
			
			// SI LA CASILLA SELECCIONADA ES DISTINTA A LA CASILLA PREDETERMINADA Y NO TIENE CASILLA PREDETERMINADA LE ASIGNA LA SELECCIONADA EN EL REGISTRO DE COMPRA
			$idCasillaPredetVenta = ($idCasilla != $rowArtEmp['id_casilla_predeterminada'] && $rowArtEmp['id_casilla_predeterminada'] == "") ? $idCasilla : $rowArtEmp['id_casilla_predeterminada'];
			$idCasillaPredetCompra = ($idCasilla != $rowArtEmp['id_casilla_predeterminada_compra'] && $rowArtEmp['id_casilla_predeterminada_compra'] == "") ? $idCasilla : $rowArtEmp['id_casilla_predeterminada_compra'];
			
			// MODIFICA LA CASILLA PREDETERMINADA DEL ARTICULO EN LA EMPRESA
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				id_casilla_predeterminada = %s,
				id_casilla_predeterminada_compra = %s,
				cantidad_pedida = 0,
				estatus = 1
			WHERE id_articulo_empresa = %s;",
				valTpDato($idCasillaPredetVenta, "int"),
				valTpDato($idCasillaPredetCompra, "int"),
				valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// VERIFICACION PARA SABER SI LA CASILLA PREDETERMINADA ES VÁLIDA
			$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_casilla = %s
				AND estatus = 1;",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasillaPredetCompra, "int"));
			$rsArtAlm = mysql_query($queryArtAlm);
			if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
			$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
			if (!($totalRowsArtAlm > 0)) {
				// BUSCA LA PRIMERA UBICACION ACTIVA DEL ARTICULO PARA PONERSELA COMO PREDETERMINADA
				$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
					id_casilla_predeterminada = (SELECT art_alm.id_casilla
												FROM iv_almacenes almacen
													INNER JOIN iv_calles calle ON (almacen.id_almacen = calle.id_almacen)
													INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
													INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
													INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
													INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
												WHERE almacen.id_empresa = art_emp.id_empresa
													AND art_alm.id_articulo = art_emp.id_articulo
													AND art_alm.estatus = 1
												LIMIT 1),
					id_casilla_predeterminada_compra = (SELECT art_alm.id_casilla
												FROM iv_almacenes almacen
													INNER JOIN iv_calles calle ON (almacen.id_almacen = calle.id_almacen)
													INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
													INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
													INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
													INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
												WHERE almacen.id_empresa = art_emp.id_empresa
													AND art_alm.id_articulo = art_emp.id_articulo
													AND art_alm.estatus = 1
												LIMIT 1)
				WHERE art_emp.id_empresa = %s
					AND art_emp.id_articulo = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nId Casilla: ".$idCasilla); }
				mysql_query("SET NAMES 'latin1';");
			}
			
			
			$idKardex = $arrayAlmacenCosto[$indice][0];
			$idMovimientoDetalle = $arrayAlmacenCosto[$indice][1];
			$idBloqueoVentaDetalle = $arrayAlmacenCosto[$indice][2];
			$hddIdArticuloCosto = $arrayAlmacenCosto[$indice][3];
			
			// VERIFICA SI EL LOTE TIENE LA UBICACION ASIGNADA
			$queryArtAlmCosto = sprintf("SELECT *
			FROM iv_articulos_almacen art_almacen
				INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
			WHERE art_almacen.id_articulo = %s
				AND art_almacen.id_casilla = %s
				AND art_almacen_costo.id_articulo_costo = %s;",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($hddIdArticuloCosto, "int"));
			$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
			if (!$rsArtAlmCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlmCosto);
			$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
			
			$hddIdArticuloAlmacenCosto = $rowArtAlmCosto['id_articulo_almacen_costo'];
			
			if ($totalRowsArtAlm > 0) {
				// ACTUALIZA EL ESTATUS DE LA UBICACION DEL LOTE
				$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
					estatus = 1
				WHERE id_articulo_almacen_costo = %s;",
					valTpDato($hddIdArticuloAlmacenCosto, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			} else {
				// LE ASIGNA EL LOTE A LA UBICACION
				$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
				SELECT art_almacen.id_articulo_almacen, %s, 1 FROM iv_articulos_almacen art_almacen
				WHERE art_almacen.id_casilla = %s
					AND art_almacen.id_articulo = %s
					AND art_almacen.estatus = 1;",
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$hddIdArticuloAlmacenCosto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// ACTUALIZA LA UBICACION DEL LOTE Y EL LOTE EN EL KARDEX
			$updateSQL = sprintf("UPDATE iv_kardex SET
				id_articulo_almacen_costo = %s,
				id_articulo_costo = %s
			WHERE id_kardex = %s;",
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($idKardex, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

			// ACTUALIZA LA UBICACION DEL LOTE Y EL LOTE EN EL DETALLE DEL MOVIMIENTO
			$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
				id_articulo_almacen_costo = %s,
				id_articulo_costo = %s
			WHERE id_movimiento_detalle = %s;",
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($idMovimientoDetalle, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

			// ACTUALIZA LA UBICACION DEL LOTE Y EL LOTE EN EL DETALLE DEL BLOQUEO DE VENTA
			$updateSQL = sprintf("UPDATE iv_bloqueo_venta_detalle SET
				id_articulo_almacen_costo = %s,
				id_articulo_costo = %s
			WHERE id_bloqueo_venta_detalle = %s;",
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($idBloqueoVentaDetalle, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// ACTUALIZA EL LOTE DEL COSTO
			$Result1 = actualizarLote($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
			$Result1 = actualizarMovimientoTotal($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			if ($idArticuloSust > 0) {
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
				$Result1 = actualizarSaldos($idArticuloOrg, $idCasilla, $idCasillaPredetCompra);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
				$Result1 = actualizarPedidas($idArticuloOrg);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			}
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
			$Result1 = actualizarSaldos($idArticulo, $idCasilla, $idCasillaPredetCompra);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
			$Result1 = actualizarPedidas($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA EL COSTO PROMEDIO
			$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA EL PRECIO DE VENTA
			$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			$arrayEtiqueta[] = implode(",", array($idArticulo, $idCasilla, $hddIdArticuloCosto, $cantRecibida));
		}
	}
	
	// INSERTA LOS GASTOS DE LA FACTURA
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indiceGasto => $valorGasto) {
			$hddIdGasto = $frmTotalDcto['hddIdGasto'.$valorGasto];
			$hddIdModoGasto = $frmTotalDcto['hddIdModoGasto'.$valorGasto];
			
			if ($idModoCompra == 2 && $hddIdModoGasto == 1) { // 2 = Importacion && 1 = Gastos
				$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valorGasto]) * $txtTasaCambio;
				$txtPorcGasto = ($txtSubTotal == 0) ? 0 : $txtMontoGasto * (100 / $txtSubTotal);
			} else {
				$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valorGasto]);
				$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valorGasto]);
			}
			$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valorGasto]);
			
			if (round($txtMontoGasto, 2) != 0) {
				$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto, monto_medida, id_modo_gasto, id_tipo_medida, afecta_documento)
				SELECT %s, id_gasto, %s, %s, %s, %s, id_modo_gasto, %s, afecta_documento FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valorGasto], "int"), // 0 = Porcentaje, 1 = Monto Fijo
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($txtMedidaGasto, "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdTipoMedida'.$valorGasto], "int"), // 1 = Peso
					valTpDato($hddIdGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$hddIdFacturaCompraGasto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$contIvaGasto = 0;
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indiceIvaGasto => $valorIvaGasto) {
						$valorIvaGasto = explode(":", $valorIvaGasto);
						if ($valorIvaGasto[0] == $valorGasto) {
							$contIvaGasto++;
							
							$hddIdIvaGasto = $frmTotalDcto['hddIdIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
							$hddIvaGasto = $frmTotalDcto['hddIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
							$hddEstatusIvaGasto = $frmTotalDcto['hddEstatusIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
							
							$insertSQL = sprintf("INSERT INTO cp_factura_gasto_impuesto (id_factura_gasto, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($hddIdFacturaCompraGasto, "int"),
								valTpDato($hddIdIvaGasto, "int"),
								valTpDato($hddIvaGasto, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
						}
					}
				}
				
				$hddIdIvaGasto = ($contIvaGasto == 1) ? $hddIdIvaGasto : "";
				$hddIvaGasto = ($contIvaGasto == 1) ? $hddIvaGasto : 0;
				$hddEstatusIvaGasto = ($contIvaGasto == 1) ? $hddEstatusIvaGasto : "1";
				
				// EDITA EL IMPUESTO DEL GASTO
				$updateSQL = sprintf("UPDATE cp_factura_gasto SET
					id_iva = %s,
					iva = %s,
					estatus_iva = %s
				WHERE id_factura_gasto = %s;",
					valTpDato($hddIdIvaGasto, "int"),
					valTpDato($hddIvaGasto, "real_inglesa"),
					valTpDato($hddEstatusIvaGasto, "boolean"),
					valTpDato($hddIdFacturaCompraGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("FA", "text"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
		valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// CREACION DE LA RETENCION DEL IMPUESTO
	if ($frmTotalDcto['lstRetencionImpuesto'] > 0
	&& $txtTotalExento + $txtTotalExonerado != $txtTotalOrden) {
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(2, "int"), // 2 = Comprobante Retenciones
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
		
		$insertSQL = sprintf("INSERT INTO cp_retencioncabezera (id_empresa, numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idProveedor)
		VALUE (%s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
			valTpDato("NOW()", "campo"),
			valTpDato(date("Y"), "int"),
			valTpDato(date("m"), "int"),
			valTpDato($frmDcto['txtIdProv'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idRetencionCabezera = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$porcRetencion = doubleval($frmTotalDcto['lstRetencionImpuesto']);
		
		$comprasSinIva = $txtTotalExento + $txtTotalExonerado;
	} else if ($frmTotalDcto['lstRetencionImpuesto'] > 0
	&& $txtTotalExento + $txtTotalExonerado == $txtTotalOrden) {
		return $objResponse->alert("Este Registro No Posee Impuesto(s) para Aplicar(les) Retención, Por Favor Verifique la Opción de Retención Seleccionada");
	}
			
	// GUARDA LOS DATOS DE LAS FACTURA DE OTROS CARGOS
	$Result1 = guardarDctoImportacion($idFactura, $frmDcto, $frmTotalDcto, "REGISTRO");
	if ($Result1[0] != true) { return $objResponse->alert($Result1[1]); }
	
	// INSERTA EL PAGO DE RETENCION ISLR
	if (str_replace(",","",$frmTotalDcto['txtTotalRetencionISLR']) > 0) {
		// BUSCA LOS DATOS DE LA RETENCION
		$queryRetencionISLR = sprintf("SELECT * FROM te_retenciones WHERE id = %s;",
			valTpDato($frmTotalDcto['lstRetencionISLR'], "int"));
		$rsRetencionISLR = mysql_query($queryRetencionISLR);
		if (!$rsRetencionISLR) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsRetencionISLR = mysql_num_rows($rsRetencionISLR);
		$rowRetencionISLR = mysql_fetch_assoc($rsRetencionISLR);
		
		$insertSQL = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo_documento, tipo, fecha_registro)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idFactura, "int"),
			valTpDato($frmTotalDcto['lstRetencionISLR'], "int"),
			valTpDato($frmTotalDcto['txtBaseImpISLR'], "real_inglesa"),
			valTpDato($rowRetencionISLR['sustraendo'], "real_inglesa"),
			valTpDato($rowRetencionISLR['porcentaje'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalRetencionISLR'], "real_inglesa"),
			valTpDato($rowRetencionISLR['codigo'], "real_inglesa"),
			valTpDato(2, "int"), // 0 = Cheque, 1 = Transferencia, 2 = Sin Documento
			valTpDato(0, "int"), // 0 = Factura, 1 = Nota de Cargo
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idISLR = mysql_insert_id();
		
		$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, numero_documento, fecha_pago, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idFactura, "int"), 
			valTpDato("FA", "text"),
			valTpDato("ISLR", "text"),
			valTpDato($idISLR, "int"),
			valTpDato($idISLR, "int"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato($frmTotalDcto['txtTotalRetencionISLR'], "real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	switch ($idModoCompra) {
		case 1 : // 1 = Nacional
			// INSERTA LOS IMPUESTOS DE LA FACTURA
			if (isset($arrayObjIva)) {
				foreach ($arrayObjIva as $indice => $valor) {
					if ($frmTotalDcto['txtSubTotalIva'.$valor] > 0) {
						$insertSQL = sprintf("INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
						VALUE (%s, %s, %s, %s, %s, %s);",
							valTpDato($idFactura, "int"),
							valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
							valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
							valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
							valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
							valTpDato($frmTotalDcto['hddLujoIva'.$valor], "boolean"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
						
						if ($idRetencionCabezera > 0) {
							$ivaRetenido = round((doubleval($porcRetencion) * str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor])) / 100, 2);
							
							$insertSQL = sprintf("INSERT INTO cp_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idRetencionCabezera, "int"),
								valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
								valTpDato($idFactura, "int"),
								valTpDato($frmDcto['txtNumeroControl'], "text"),
								valTpDato(" ", "text"),
								valTpDato(" ", "text"),
								valTpDato("01", "text"), // 01 = FACTURA, 02 = NOTA DEBITO, 03 = NOTA CREDITO
								valTpDato(" ", "text"), // CUANDO ES NOTA DE CREDITO O DE DEBITO
								valTpDato($txtTotalOrden, "real_inglesa"),
								valTpDato($comprasSinIva, "real_inglesa"),
								valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
								valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
								valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
								valTpDato($ivaRetenido, "real_inglesa"),
								valTpDato($porcRetencion, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
							
							// INSERTA EL PAGO DEBIDO A LA RETENCION
							$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idFactura, "int"),
								valTpDato("FA", "text"),
								valTpDato("RETENCION", "text"),
								valTpDato($idRetencionCabezera, "int"),
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato($ivaRetenido, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
						}
					}
				}
			}
			
			// ACTUALIZA EL SALDO DE LA FACTURA
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				total_cuenta_pagar = (IFNULL(cxp_fact.subtotal_factura, 0)
											- IFNULL(cxp_fact.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
														AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
											+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva FROM cp_factura_iva cxp_fact_iva
													WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)),
				saldo_factura = (IFNULL(cxp_fact.subtotal_factura, 0)
									- IFNULL(cxp_fact.subtotal_descuento, 0)
									+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
											FROM cp_factura_gasto cxp_fact_gasto
											WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
												AND cxp_fact_gasto.id_modo_gasto IN (1,3)
												AND cxp_fact_gasto.afecta_documento IN (1)), 0)
									+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
											FROM cp_factura_iva cxp_fact_iva
											WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)
									- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE pago_dcto.id_documento_pago = cxp_fact.id_factura
												AND pago_dcto.tipo_documento_pago LIKE 'FA'
												AND pago_dcto.estatus = 1), 0))
			WHERE id_factura = %s
				AND estatus_factura NOT IN (1);",
				valTpDato($idFactura, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
			// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				estatus_factura = (CASE
									WHEN (ROUND(cxp_fact.saldo_factura, 2) = 0) THEN
										1
									WHEN (ROUND(cxp_fact.saldo_factura, 2) > 0
										AND ROUND(cxp_fact.saldo_factura, 2) < ROUND(cxp_fact.total_cuenta_pagar, 2)) THEN
										2
									ELSE
										0
								END)
			WHERE cxp_fact.id_factura = %s;",
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			break;
		case 2 : // 2 = Importacion
			// INSERTA LOS IMPUESTOS DE LA FACTURA
			if (isset($arrayObjIvaLocal)) {
				foreach ($arrayObjIvaLocal as $indice => $valor) {
					if ($frmTotalDcto['txtSubTotalIvaLocal'.$valor] > 0) {
						$insertSQL = sprintf("INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
						VALUE (%s, %s, %s, %s, %s, %s);",
							valTpDato($idFactura, "int"),
							valTpDato($frmTotalDcto['txtBaseImpIvaLocal'.$valor], "real_inglesa"),
							valTpDato($frmTotalDcto['txtSubTotalIvaLocal'.$valor], "real_inglesa"),
							valTpDato($frmTotalDcto['hddIdIvaLocal'.$valor], "int"),
							valTpDato($frmTotalDcto['txtIvaLocal'.$valor], "real_inglesa"),
							valTpDato($frmTotalDcto['hddLujoIvaLocal'.$valor], "boolean"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						mysql_query("SET NAMES 'latin1';");
						
						if ($idRetencionCabezera > 0) {
							$ivaRetenido = round((doubleval($porcRetencion) * str_replace(",", "", $frmTotalDcto['txtSubTotalIvaLocal'.$valor])) / 100, 2);
							
							$insertSQL = sprintf("INSERT INTO cp_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
							VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idRetencionCabezera, "int"),
								valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
								valTpDato($idFactura, "int"),
								valTpDato($frmDcto['txtNumeroControl'], "text"),
								valTpDato(" ", "text"),
								valTpDato(" ", "text"),
								valTpDato("01", "text"), // 01 = FACTURA, 02 = NOTA DEBITO, 03 = NOTA CREDITO
								valTpDato(" ", "text"), // CUANDO ES NOTA DE CREDITO O DE DEBITO
								valTpDato($txtTotalOrden, "real_inglesa"),
								valTpDato($comprasSinIva, "real_inglesa"),
								valTpDato($frmTotalDcto['txtBaseImpIvaLocal'.$valor], "real_inglesa"),
								valTpDato($frmTotalDcto['txtIvaLocal'.$valor], "real_inglesa"),
								valTpDato($frmTotalDcto['txtSubTotalIvaLocal'.$valor], "real_inglesa"),
								valTpDato($ivaRetenido, "real_inglesa"),
								valTpDato($porcRetencion, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
							
							// INSERTA EL PAGO DEBIDO A LA RETENCION
							$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idFactura, "int"),
								valTpDato("FA", "text"),
								valTpDato("RETENCION", "text"),
								valTpDato($idRetencionCabezera, "int"),
								valTpDato("NOW()", "campo"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato("-", "text"),
								valTpDato($ivaRetenido, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
						}
					}
				}
			}
			
			// ACTUALIZA EL EXPEDIENTE
			$updateSQL = sprintf("UPDATE iv_expediente SET
				estatus = %s
			WHERE id_expediente = %s;",
				valTpDato(1, "int"), // 0 = Abierto, 1 = Cerrado
				valTpDato($idExpediente, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA LA RELACION FACTURA DE COMPRA EXPEDIENTE
			$updateSQL = sprintf("UPDATE iv_expediente_detalle_factura SET
				id_factura = %s,
				id_factura_compra = NULL
			WHERE id_expediente = %s
				AND id_factura_compra = %s;",
				valTpDato($idFactura, "int"),
				valTpDato($idExpediente, "int"),
				valTpDato($idFacturaCompra, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO cp_factura_importacion (id_factura, nacionalizada, id_moneda_tasa_cambio, id_tasa_cambio, tasa_cambio, total_advalorem, total_advalorem_diferencia, id_actividad_importador, id_clase_importador, id_clase_solicitud, puerto_llegada, destino_final, compania_transportadora, id_proveedor_exportador, id_proveedor_consignatario, id_aduana, id_pais_origen, id_pais_compra, puerto_embarque, id_via_envio, tasa_cambio_diferencia, numero_embarque, porcentaje_seguro, numero_dcto_transporte, fecha_dcto_transporte, fecha_vencimiento_dcto_transporte, fecha_estimada_llegada, numero_planilla_importacion, numero_expediente) 
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato($frmDcto['lstNacionalizar'], "boolean"),
				valTpDato($frmTotalDcto['txtIdMonedaNegociacion'], "int"),
				valTpDato($frmDcto['lstTasaCambio'], "int"),
				valTpDato($txtTasaCambio, "real_inglesa"),
				valTpDato($frmTotalDcto['txtTotalAdValorem'], "real_inglesa"),
				valTpDato($frmTotalDcto['txtTotalAdValoremDif'], "real_inglesa"),
				valTpDato($frmTotalDcto['lstActividadImportador'], "int"),
				valTpDato($frmTotalDcto['lstClaseImportador'], "int"),
				valTpDato($frmTotalDcto['lstClaseSolicitud'], "int"),
				valTpDato($frmTotalDcto['txtPuertoLlegada'], "text"),
				valTpDato($frmTotalDcto['txtDestinoFinal'], "text"),
				valTpDato($frmTotalDcto['txtCompaniaTransporte'], "text"),
				valTpDato($frmTotalDcto['txtIdProvExportador'], "int"),
				valTpDato($frmTotalDcto['txtIdProvConsignatario'], "int"),
				valTpDato($frmTotalDcto['txtIdPaisAduana'], "int"),
				valTpDato($frmTotalDcto['txtIdPaisOrigen'], "int"),
				valTpDato($frmTotalDcto['txtIdPaisCompra'], "int"),
				valTpDato($frmTotalDcto['txtPuertoEmbarque'], "text"),
				valTpDato($frmDcto['lstViaEnvio'], "int"),
				valTpDato($frmTotalDcto['txtDiferenciaCambiaria'], "real_inglesa"),
				valTpDato($frmTotalDcto['txtNumeroEmbarque'], "text"),
				valTpDato($frmTotalDcto['txtPorcSeguro'], "real_inglesa"),
				valTpDato($frmTotalDcto['txtDctoTransporte'], "text"),
				valTpDato(date("Y-m-d", strtotime($frmTotalDcto['txtFechaDctoTransporte'])), "date"),
				valTpDato(date("Y-m-d", strtotime($frmTotalDcto['txtFechaVencDctoTransporte'])), "date"),
				valTpDato(date("Y-m-d", strtotime($frmTotalDcto['txtFechaEstimadaLlegada'])), "date"),
				valTpDato($frmTotalDcto['txtPlanillaImportacion'], "text"),
				valTpDato($frmTotalDcto['txtExpediente'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// BUSCA LOS DATOS DE LA MONEDA NACIONAL
			$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
				valTpDato($idMonedaLocal, "int"));
			$rsMonedaLocal = mysql_query($queryMonedaLocal);
			if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
			
			// SI LA MONEDA NACIONAL APLICA IMPUESTO ACTUALIZARA LOS ESTATUS DE IMPUESTO PARA EFECTO DE LA DECLARACION DEL IMPUESTO
			if ($rowMonedaLocal['incluir_impuestos'] == 1 && $frmDcto['lstNacionalizar'] == 1) { // 0 = No, 1 = Si
				// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva;");
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					// INSERTA LOS IMPUESTOS AL DETALLE DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO cp_factura_detalle_impuesto (id_factura_detalle, id_impuesto, impuesto)
					SELECT id_factura_detalle, %s, %s FROM cp_factura_detalle
					WHERE id_factura = %s
						AND id_factura_detalle NOT IN (SELECT id_factura_detalle FROM cp_factura_detalle_impuesto
														WHERE id_impuesto = %s);",
						valTpDato($rowIva['idIva'], "int"),
						valTpDato($rowIva['iva'], "int"),
						valTpDato($idFactura, "int"),
						valTpDato($rowIva['idIva'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// INSERTA LOS IMPUESTOS A LOS GASTOS DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO cp_factura_gasto_impuesto (id_factura_gasto, id_impuesto, impuesto)
					SELECT id_factura_gasto, %s, %s FROM cp_factura_gasto
					WHERE id_factura = %s
						AND id_modo_gasto IN (%s)
						AND id_factura_gasto NOT IN (SELECT id_factura_gasto FROM cp_factura_gasto_impuesto
														WHERE id_impuesto = %s);",
						valTpDato($rowIva['idIva'], "int"),
						valTpDato($rowIva['iva'], "int"),
						valTpDato($idFactura, "int"),
						valTpDato(1, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Nacional de Importacion
						valTpDato($rowIva['idIva'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					if ($totalRowsIva == 1) {
						$updateSQL = sprintf("UPDATE cp_factura_detalle SET
							id_iva = %s,
							iva = %s
						WHERE id_factura = %s;",
							valTpDato($rowIva['idIva'], "int"),
							valTpDato($rowIva['iva'], "real_inglesa"),
							valTpDato($idFactura, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						$updateSQL = sprintf("UPDATE cp_factura_gasto SET
							id_iva = %s,
							iva = %s,
							estatus_iva = %s
						WHERE id_factura = %s
							AND id_modo_gasto IN (%s);",
							valTpDato($rowIva['idIva'], "int"),
							valTpDato($rowIva['iva'], "real_inglesa"),
							valTpDato(1, "int"), // 0 = No, 1 = Si
							valTpDato($idFactura, "int"),
							valTpDato(1, "int")); // 1 = Gastos, 2 = Otros Cargos, 3 = Nacional de Importacion
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					}
				}
				
				$updateSQL = sprintf("UPDATE cp_factura SET
					monto_exento = (SELECT SUM(monto) FROM cp_factura_gasto
									WHERE id_factura = cp_factura.id_factura
										AND id_iva IS NULL
										AND id_modo_gasto IN (1,3)),
					monto_exonerado = 0
				WHERE id_factura = %s;",
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			} else {
				// ELIMINA IMPUESTO EN EL DETALLE DE LA FACTURA
				$updateSQL = sprintf("UPDATE cp_factura_detalle SET
					id_iva = %s,
					iva = %s
				WHERE id_factura = %s;",
					valTpDato("", "int"),
					valTpDato(0, "real_inglesa"),
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// ELIMINA LOS IMPUESTOS DEL DETALLE DE LA FACTURA
				$deleteSQL = sprintf("DELETE FROM cp_factura_detalle_impuesto
				WHERE id_factura_detalle IN (SELECT id_factura_detalle FROM cp_factura_detalle WHERE id_factura = %s);",
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// ELIMINA IMPUESTO EN EL GASTO DE LA FACTURA
				$updateSQL = sprintf("UPDATE cp_factura_gasto SET
					id_iva = %s,
					iva = %s,
					estatus_iva = %s
				WHERE id_factura = %s
					AND id_modo_gasto IN (%s);",
					valTpDato("", "int"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "int"), // 0 = No, 1 = Si
					valTpDato($idFactura, "int"),
					valTpDato("1,3", "campo")); // 1 = Gastos, 2 = Otros Cargos, 3 = Nacional de Importacion
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// ELIMINA LOS IMPUESTOS DEL GASTO DE LA FACTURA
				$deleteSQL = sprintf("DELETE FROM cp_factura_gasto_impuesto
				WHERE id_factura_gasto IN (SELECT id_factura_gasto FROM cp_factura_gasto WHERE id_factura = %s);",
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
			
			// ACTUALIZA EL SALDO DE LA FACTURA DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				total_cuenta_pagar = (CASE cxp_fact.id_modulo
										WHEN 0 THEN
											IFNULL((SELECT 
														SUM(a.cantidad * (b.costo_unitario * cxp_fact_imp.tasa_cambio))
													FROM cp_factura_detalle a
														INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = cxp_fact.id_factura), 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
													FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_modo_gasto IN (1)
														AND cxp_fact_gasto.afecta_documento IN (1)
														AND cxp_fact_gasto.id_factura = cxp_fact.id_factura), 0)
										WHEN 2 THEN
											IFNULL((SELECT 
														SUM((b.costo_unitario * cxp_fact_imp.tasa_cambio))
													FROM cp_factura_detalle_unidad a
														INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = cxp_fact.id_factura), 0)
											 + IFNULL((SELECT 
														SUM(a.cantidad * (b.costo_unitario * cxp_fact_imp.tasa_cambio))
													FROM cp_factura_detalle_accesorio a
														INNER JOIN cp_factura_detalle_accesorio_importacion b ON (b.id_factura_detalle_accesorio = a.id_factura_detalle_accesorio)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = cxp_fact.id_factura), 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
													FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_modo_gasto IN (1)
														AND cxp_fact_gasto.afecta_documento IN (1)
														AND cxp_fact_gasto.id_factura = cxp_fact.id_factura), 0)
									END),
				saldo_factura = (CASE cxp_fact.id_modulo
										WHEN 0 THEN
											IFNULL((SELECT 
														SUM(a.cantidad * (b.costo_unitario * cxp_fact_imp.tasa_cambio))
													FROM cp_factura_detalle a
														INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = cxp_fact.id_factura), 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
													FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_modo_gasto IN (1)
														AND cxp_fact_gasto.afecta_documento IN (1)
														AND cxp_fact_gasto.id_factura = cxp_fact.id_factura), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE pago_dcto.id_documento_pago = cxp_fact.id_factura
														AND pago_dcto.tipo_documento_pago LIKE 'FA'
														AND pago_dcto.estatus = 1), 0)
										WHEN 2 THEN
											IFNULL((SELECT 
														SUM((b.costo_unitario * cxp_fact_imp.tasa_cambio))
													FROM cp_factura_detalle_unidad a
														INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = cxp_fact.id_factura), 0)
											 + IFNULL((SELECT 
														SUM(a.cantidad * (b.costo_unitario * cxp_fact_imp.tasa_cambio))
													FROM cp_factura_detalle_accesorio a
														INNER JOIN cp_factura_detalle_accesorio_importacion b ON (b.id_factura_detalle_accesorio = a.id_factura_detalle_accesorio)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = cxp_fact.id_factura), 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
													FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_modo_gasto IN (1)
														AND cxp_fact_gasto.afecta_documento IN (1)
														AND cxp_fact_gasto.id_factura = cxp_fact.id_factura), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE pago_dcto.id_documento_pago = cxp_fact.id_factura
														AND pago_dcto.tipo_documento_pago LIKE 'FA'
														AND pago_dcto.estatus = 1), 0)
									END)
			WHERE cxp_fact.id_modo_compra IN (2)
				AND cxp_fact.estatus_factura IN (0,2)
				AND cxp_fact.id_factura = %s
				AND ((SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
						WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) > 0
					OR (SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det
						WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) > 0);",
				valTpDato($idFactura, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
			// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				estatus_factura = (CASE
									WHEN (ROUND(cxp_fact.saldo_factura, 2) = 0) THEN
										1
									WHEN (ROUND(cxp_fact.saldo_factura, 2) > 0
										AND ROUND(cxp_fact.saldo_factura, 2) < ROUND(cxp_fact.total_cuenta_pagar, 2)) THEN
										2
									ELSE
										0
								END)
			WHERE cxp_fact.id_factura = %s;",
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			break;
	}
	
	// ELIMINA LOS DATOS DE LA FACTURA DE COMPRA EN REPUESTOS
	$deleteSQL = sprintf("DELETE FROM iv_factura_compra WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ELIMINA LOS DATOS DEL BLOQUEO DE VENTA SI NO POSEE DETALLE
	$deleteSQL = sprintf("DELETE FROM iv_bloqueo_venta
	WHERE id_bloqueo_venta NOT IN (SELECT id_bloqueo_venta FROM iv_bloqueo_venta_detalle)
		AND id_bloqueo_venta = %s;",
		valTpDato($idBloqueoVenta, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ACTUALIZA LA CANTIDAD DE ARTICULOS PENDIENTES EN EL DETALLE DEL PEDIDO
	$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
		pendiente = cantidad - (IFNULL((SELECT SUM(cxp_fact_det.pendiente)
										FROM iv_factura_compra_detalle cxp_fact_det
										WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
											AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
								+ IFNULL((SELECT SUM(cxp_fact_det.cantidad)
										FROM cp_factura_detalle cxp_fact_det
										WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
											AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)),
		estatus = (CASE 
					WHEN (cantidad - (IFNULL((SELECT SUM(cxp_fact_det.pendiente)
											FROM iv_factura_compra_detalle cxp_fact_det
											WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
									+ IFNULL((SELECT SUM(cxp_fact_det.cantidad)
											FROM cp_factura_detalle cxp_fact_det
											WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) = 0 THEN
						1
					WHEN (cantidad - (IFNULL((SELECT SUM(cxp_fact_det.pendiente)
											FROM iv_factura_compra_detalle cxp_fact_det
											WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
									+ IFNULL((SELECT SUM(cxp_fact_det.cantidad)
											FROM cp_factura_detalle cxp_fact_det
											WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) > 0 THEN
						0
				END)
	WHERE estatus IN (0,1)
		AND id_pedido_compra IN (SELECT cxp_fact_det.id_pedido_compra FROM cp_factura_detalle cxp_fact_det
								WHERE cxp_fact_det.id_factura IN (%s));",
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DEL PEDIDO DE COMPRA (0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelto, 5 = Anulado)
	$updateSQL = sprintf("UPDATE iv_pedido_compra SET 
		estatus_pedido_compra = (CASE
									WHEN ((SELECT COUNT(ped_comp_det.id_pedido_compra) FROM iv_pedido_compra_detalle ped_comp_det
											WHERE ped_comp_det.estatus IN (2)
												AND ped_comp_det.id_pedido_compra = iv_pedido_compra.id_pedido_compra) > 0
										AND (SELECT COUNT(ped_comp_det.id_pedido_compra) FROM iv_pedido_compra_detalle ped_comp_det
											WHERE ped_comp_det.estatus IN (0,1)
												AND ped_comp_det.id_pedido_compra = iv_pedido_compra.id_pedido_compra) = 0) THEN
										5
									WHEN ((SELECT SUM(pendiente) FROM iv_pedido_compra_detalle ped_comp_det
											WHERE ped_comp_det.estatus IN (0,1)
												AND ped_comp_det.id_pedido_compra = iv_pedido_compra.id_pedido_compra) = 0) THEN
										3
									WHEN ((SELECT SUM(pendiente) FROM iv_pedido_compra_detalle ped_comp_det
											WHERE ped_comp_det.estatus IN (0,1)
												AND ped_comp_det.id_pedido_compra = iv_pedido_compra.id_pedido_compra) > 0) THEN
										2
								END)
	WHERE id_pedido_compra IN (SELECT cxp_fact_det.id_pedido_compra FROM cp_factura_detalle cxp_fact_det
								WHERE cxp_fact_det.id_factura IN (%s));",
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdFactura","value",$idFactura);
	
	$objResponse->alert("Registro de Compra Guardado con Éxito");
	
	$comprobanteRetencion = ($frmTotalDcto['lstRetencionImpuesto'] > 0) ? 1 : 0;
	
	$objResponse->script(sprintf("window.location.href='iv_registro_compra_formato_pdf.php?valBusq=%s|%s|%s|%s&valBusq2=%s';",
		$comprobanteRetencion,
		$idFactura,
		$idRetencionCabezera,
		$idISLR,
		implode("|", $arrayEtiqueta)));
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "COMPRA") {
				$idFactura = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarComprasRe")) { generarComprasRe($idFactura,"",""); } break;
					case 1 : if (function_exists("generarComprasSe")) { generarComprasSe($idFactura,"",""); } break;
					case 2 : if (function_exists("generarComprasVe")) { generarComprasVe($idFactura,"",""); } break;
					case 3 : if (function_exists("generarComprasAd")) { generarComprasAd($idFactura,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	return $objResponse;
}

function asignarADV($frmDcto, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$queryArancelGrupo = sprintf("SELECT * FROM pg_arancel_grupo WHERE id_arancel_grupo = %s;",
		valTpDato($frmDcto['lstArancelGrupo'], "int"));
	$rsArancelGrupo = mysql_query($queryArancelGrupo);
	if (!$rsArancelGrupo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArancelGrupo = mysql_fetch_assoc($rsArancelGrupo);
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->call("selectedOption","lstTarifaAdValorem".$valor,$rowArancelGrupo['porcentaje_grupo']);
		}
		
		$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
	
	return $objResponse;
}

function asignarAlmacen($frmAlmacen, $frmListaArticulo, $frmTotalDcto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$hddNumeroArt = $frmAlmacen['hddNumeroArt2'];
	
	$idArticulo = ($frmListaArticulo['hddIdArticuloSustItm'.$hddNumeroArt] > 0) ? $frmListaArticulo['hddIdArticuloSustItm'.$hddNumeroArt] : $frmListaArticulo['hddIdArticuloItm'.$hddNumeroArt];
	$idCasilla = $frmAlmacen['lstCasillaAct'];
	
	// VERIFICA SI ALGUN ARTICULO DE LA LISTA TIENE LA UBICACION YA OCUPADA
	$existe = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdCasillaItm'.$valor] == $idCasilla
			&& $frmListaArticulo['hddIdArticuloItm'.$valor] != $idArticulo)
				$existe = true;
		}
	}
	
	$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
	WHERE id_casilla = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idCasilla, "int"));
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
	
	// VERIFICA SI ALGUN ARTICULO DE LA BASE DE DATOS TIENE LA UBICACION YA OCUPADA
	if ($totalRowsArtAlm > 0 && $rowArtAlm['id_articulo'] != $idArticulo)
		$existe = true;
	
	if ($existe == false) {
		$clase = (fmod($hddNumeroArt, 2) == 0) ? "trResaltar4" : "trResaltar5";
		
		// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
		$queryArtAlm = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
			valTpDato($idCasilla, "int"));
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		$objResponse->assign("hddIdCasillaItm".$hddNumeroArt,"value",$idCasilla);
		$objResponse->assign("spanUbicacion:".$hddNumeroArt,"innerHTML",$rowArtAlm['descripcion_almacen']."<br>".str_replace("-[]", "", $rowArtAlm['ubicacion']));
		
		$objResponse->script(sprintf("byId('trItm:%s').className = 'textoGris_11px %s';", $hddNumeroArt, $clase));
		
		if (in_array($cerrarVentana, array("1", "true"))) {
			$objResponse->script("byId('btnCancelarAlmacen').click();");
		}
	} else {
		$objResponse->alert("No puede agregar una ubicación ya ocupada");
	}
	
	return $objResponse;
}

function asignarArticulo($hddNumeroArt, $frmDcto, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	$contFila = $hddNumeroArt;
	
	$objResponse->script("
	document.forms['frmArticulo'].reset();
	byId('hddIdArticulo').value = '';
	byId('hddIdArticuloSust').value = '';
	byId('hddNumeroArt').value = '';
	
	byId('txtCantidadRecibArt').className = 'inputHabilitado';
	byId('txtCostoArt').className = 'inputHabilitado';");
	
	if ($frmListaArticulo['hddIdArticuloSustItm'.$contFila] > 0) {
		$idArticulo = $frmListaArticulo['hddIdArticuloSustItm'.$contFila];
		$objResponse->assign("hddIdArticulo","value",$frmListaArticulo['hddIdArticuloItm'.$contFila]);
	} else {
		$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$contFila];
		$objResponse->assign("hddIdArticulo","value",$frmListaArticulo['hddIdArticuloItm'.$contFila]);
		$objResponse->assign("hddIdArticuloSust","value","");
	}
	
	// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
	$arrayPosIvaItm = array(-1);
	$arrayIdIvaItm = array(-1);
	$arrayIvaItm = array(-1);
	if (isset($arrayObjIvaItm)) {
		foreach ($arrayObjIvaItm as $indice1 => $valor1) {
			$valor1 = explode(":", $valor1);
			
			if ($valor1[0] == $contFila) {
				$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$contFila.':'.$valor1[1]]] = $valor1[1];
				$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$contFila.':'.$valor1[1]];
				$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$contFila.':'.$valor1[1]];
			}
		}
	}
	$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	$hddIvaItm = implode(",",$arrayIvaItm);
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT *,
	
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_datos_bas.id_articulo
			AND kardex.tipo_movimiento IN (1)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_datos_bas.id_articulo
			AND kardex.tipo_movimiento IN (3)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta
		
	FROM vw_iv_articulos_datos_basicos vw_iv_art_datos_bas
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "text"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS SALDOS DEL ARTICULO
	$queryArtSaldo = sprintf("SELECT 
		art_alm.id_articulo,
		SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0)) AS existencia,
		SUM(IFNULL(art_alm.cantidad_reservada, 0)) AS cantidad_reservada,	
		SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0)) AS cantidad_disponible_fisica,
		SUM(IFNULL(art_alm.cantidad_espera, 0)) AS cantidad_espera,
		SUM(IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_bloqueada,
		SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_disponible_logica
	FROM iv_articulos_almacen art_alm
	WHERE art_alm.id_articulo = %s
	GROUP BY art_alm.id_articulo;",
		valTpDato($idArticulo, "int"));
	$rsArtSaldo = mysql_query($queryArtSaldo);
	if (!$rsArtSaldo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtSaldo = mysql_fetch_assoc($rsArtSaldo);
	
	$objResponse->assign("txtCodigoArt","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
	$objResponse->assign("txtCantDisponible","value",$rowArtSaldo['cantidad_disponible_logica']);
	
	$objResponse->script(sprintf("
	if (navigator.appName == 'Netscape') {
		byId('txtCantidadRecibArt').onkeypress = function(e){ %s }
		byId('txtCantidadRecibArt').onblur = function(e){ %s }
	} else if (navigator.appName == 'Microsoft Internet Explorer') {
		byId('txtCantidadRecibArt').onkeypress = function(e){ %s }
		byId('txtCantidadRecibArt').onblur = function(e){ %s }
	}",
		(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(e);" : "return validarSoloNumerosReales(e);"),
		(($rowArticulo['decimales'] == 0) ? "setFormatoRafk(this,0);" : "setFormatoRafk(this,2);"),
		(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(event);" : "return validarSoloNumerosReales(event);"),
		(($rowArticulo['decimales'] == 0) ? "setFormatoRafk(this,0);" : "setFormatoRafk(this,2);")));
	
	$objResponse->script("byId('txtCantDisponible').className = '".(($rowArticulo['cantidad_disponible_logica'] > 0) ? "inputCantidadDisponible" : "inputCantidadNoDisponible")."'");
	
	$objResponse->assign("txtCantidadRecibArt","value",str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$contFila]));
	$objResponse->assign("txtCantidadArt","value",str_replace(",", "", $frmListaArticulo['txtCantItm'.$contFila]));
	$objResponse->assign("txtCostoArt","value",str_replace(",", "", $frmListaArticulo['txtCostoItm'.$contFila]));
	if ($frmListaArticulo['hddTipoDescuentoItm'.$contFila] == 0) {
		$objResponse->script("byId('rbtPorcDescuentoArt').click();");
	} else if ($frmListaArticulo['hddTipoDescuentoItm'.$contFila] == 1) {
		$objResponse->script("byId('rbtMontoDescuentoArt').click();");
	}
	$objResponse->assign("hddTipoDescuento","value",$frmListaArticulo['hddTipoDescuentoItm'.$contFila]);
	$objResponse->assign("txtPorcDescuentoArt","value",str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$contFila]));
	$objResponse->assign("txtMontoDescuentoArt","value",str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$contFila]));
	$objResponse->loadCommands(cargaLstIva("lstIvaArt", $hddIdIvaItm, $hddIvaItm));
	
	if ($frmListaArticulo['hddTipoItm'.$contFila] == 0) {
		$objResponse->script("
		byId('rbtTipoArtReposicion').checked = true;
		byId('aInsertarClienteArt').style.display = 'none';");
	} else {
		$objResponse->script("
		byId('rbtTipoArtCliente').checked = true;
		byId('aInsertarClienteArt').style.display = '';");
	}
	
	$objResponse->loadCommands(asignarCliente($frmListaArticulo['hddIdClienteItm'.$contFila], $frmDcto['txtIdEmpresa'], "false"));
	
	$objResponse->assign("hddNumeroArt","value",$contFila);
	
	$objResponse->script("
	byId('txtCantidadRecibArt').focus();
	byId('txtCantidadRecibArt').select();");
	
	return $objResponse;
}

function asignarArticuloImpuesto($frmArticuloImpuesto, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$hddIdIvaItm = implode(",",$frmArticuloImpuesto['lstIvaCbx']);
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
			$contFila = $valorItm;
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (1,8,3)
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
				"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
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
	
	if (isset($frmTotalDcto['cbxItmGasto'])) {
		foreach ($frmTotalDcto['cbxItmGasto'] as $indiceItm => $valorItm) {
			$contFila = $valorItm;
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (1,8,3)
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
				"<input id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
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
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function asignarArticuloSustituto($idArticulo, $artSustituto = true, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// BUSQUEDA DEL ARTICULO POR EL ID
	$queryArticulo = sprintf("SELECT *,
	
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_datos_bas.id_articulo
			AND kardex.tipo_movimiento IN (1)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art_datos_bas.id_articulo
			AND kardex.tipo_movimiento IN (3)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta
		
	FROM vw_iv_articulos_datos_basicos vw_iv_art_datos_bas
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "text"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS SALDOS DEL ARTICULO
	$queryArtSaldo = sprintf("SELECT 
		art_alm.id_articulo,
		SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0)) AS existencia,
		SUM(IFNULL(art_alm.cantidad_reservada, 0)) AS cantidad_reservada,	
		SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0)) AS cantidad_disponible_fisica,
		SUM(IFNULL(art_alm.cantidad_espera, 0)) AS cantidad_espera,
		SUM(IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_bloqueada,
		SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_disponible_logica
	FROM iv_articulos_almacen art_alm
	WHERE art_alm.id_articulo = %s
	GROUP BY art_alm.id_articulo;",
		valTpDato($idArticulo, "int"));
	$rsArtSaldo = mysql_query($queryArtSaldo);
	if (!$rsArtSaldo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtSaldo = mysql_fetch_assoc($rsArtSaldo);
	
	if ($artSustituto == true) {
		$objResponse->assign("hddIdArticuloSust","value",$idArticulo);
	} else {
		$objResponse->assign("hddIdArticuloSust","value","");
	}
	
	$objResponse->assign("txtCodigoArt","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtTipoPiezaArt","value",$rowArticulo['tipo_articulo']);
	$objResponse->assign("txtCantDisponible","value",$rowArtSaldo['cantidad_disponible_logica']);
	
	if ($rowArticulo['decimales'] == 0) {
		$objResponse->script("
		if (navigator.appName == 'Netscape') {
			byId('txtCantidadRecibArt').onkeypress = function(e){ return validarSoloNumeros(e); }
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			byId('txtCantidadRecibArt').onkeypress = function(e){ return validarSoloNumeros(event); }
		}");
	} else if ($rowArticulo['decimales'] == 1) {
		$objResponse->script("
		if (navigator.appName == 'Netscape') {
			byId('txtCantidadRecibArt').onkeypress = function(e){ return validarSoloNumerosReales(e); }
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			byId('txtCantidadRecibArt').onkeypress = function(e){ return validarSoloNumeros(event); }
		}");
	}
	
	$objResponse->script("
	byId('txtCantidadRecibArt').focus();
	byId('txtCantidadRecibArt').select();");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarArtSust').click();");
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	WHERE id = %s
		AND id_empresa = %s
		AND status = 'Activo';",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$objResponse->assign("txtIdClienteArt","value",$rowCliente['id']);
	$objResponse->assign("txtNombreClienteArt","value",utf8_encode($rowCliente['nombre_cliente']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaCliente').click();");
	}
	
	return $objResponse;
}

function asignarFacturaCargo($frmListaRegistroCompra, $frmTotalDcto, $frmFacturaGasto, $idFacturaCargo = "", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjOtroCargo = $frmTotalDcto['cbxOtroCargo'];
	
	$existe = false;
	if (isset($arrayObjOtroCargo)) {
		foreach ($arrayObjOtroCargo as $indice => $valor) {
			if ($frmTotalDcto['hddIdFacturaCargo'.$valor] == $idFacturaCargo && $idFacturaCargo > 0) {
				return $objResponse->alert("Este item ya se encuentra incluido");
			}
		}
	}
	
	if ($idFacturaCargo > 0) {
		$hddItmGasto = $frmListaRegistroCompra['hddItmGastoListaRegistroCompra'];
		
		$query = sprintf("SELECT
			cxp_fact_cargo.id_factura,
			cxp_fact_cargo.id_modo_compra,
			cxp_fact_cargo.numero_factura_proveedor,
			cxp_fact_cargo.numero_control_factura,
			cxp_fact_cargo.fecha_origen,
			prov.id_proveedor,
			cxp_fact_cargo.subtotal_factura,
			cxp_fact_cargo.subtotal_descuento,
			moneda_local.abreviacion AS abreviacion_moneda
		FROM cp_factura cxp_fact_cargo
			INNER JOIN cp_proveedor prov ON (cxp_fact_cargo.id_proveedor = prov.id_proveedor)
			LEFT JOIN pg_monedas moneda_local ON (cxp_fact_cargo.id_moneda = moneda_local.idmoneda)
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
		
		$hddSubTotalFacturaGastoCargo = $row['subtotal_factura'] - $row['subtotal_descuento'];
	} else {
		$hddItmGasto = $frmFacturaGasto['hddItmGasto'];
		
		$hddSubTotalFacturaGastoCargo = str_replace(",", "", $frmFacturaGasto['txtSubTotalFacturaGasto']);
	}
	
	$objResponse->assign("hddCondicionGastoCargo".$hddItmGasto,"value",$frmFacturaGasto['lstCondicionGasto']); // 1 = Real, 2 = Estimado
	$objResponse->assign("hddIdFacturaCargo".$hddItmGasto,"value",$hddIdFacturaCargo);
	$objResponse->assign("txtNumeroFacturaGasto".$hddItmGasto,"value",$txtNumeroFacturaGasto);
	$objResponse->assign("txtNumeroControlFacturaGasto".$hddItmGasto,"value",$txtNumeroControlFacturaGasto);
	$objResponse->assign("txtFechaFacturaGasto".$hddItmGasto,"value",$txtFechaFacturaGasto);
	$objResponse->loadCommands(asignarProveedor($txtIdProvFacturaGasto, "ProvFacturaGasto".$hddItmGasto, "false"));
	
	$objResponse->assign("hddSubTotalFacturaGastoCargo".$hddItmGasto,"value",number_format($hddSubTotalFacturaGastoCargo, 2, ".", ","));
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

function asignarFechaRegistro($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig17 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 17 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig17 = mysql_query($queryConfig17);
	if (!$rsConfig17) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig17 = mysql_num_rows($rsConfig17);
	$rowConfig17 = mysql_fetch_assoc($rsConfig17);
	
	$valor = explode("|",$rowConfig17['valor']);
	
	$txtFechaProveedor = explode("-",date("Y-m-d",strtotime($frmDcto['txtFechaProveedor'])));
	if ($txtFechaProveedor[1] > 0 && $txtFechaProveedor[2] > 0 && $txtFechaProveedor[0] > 0) {
		if (checkdate($txtFechaProveedor[1], $txtFechaProveedor[2], $txtFechaProveedor[0])) { // EVALUA QUE LA FECHA EXISTA
			$txtFechaRegistroCompra = date(spanDateFormat);
			$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
			if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
				if ((date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat))))
					&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
					if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
					|| date("m",strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
						$txtFechaRegistroCompra = $txtFechaProveedor;
					} else {
						$objResponse->script("byId('cbxFechaRegistro').checked = false;");
						$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
					}
				} else if (!(date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat))))
					&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
					$objResponse->script("byId('cbxFechaRegistro').checked = false;");
					$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
				} else {
					$txtFechaRegistroCompra = $txtFechaProveedor;
				}
			} else if ($frmDcto['cbxFechaRegistro'] == 1) {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
			}
			
			$objResponse->assign("txtFechaRegistroCompra","value",$txtFechaRegistroCompra);
		} else {
			$objResponse->assign("txtFechaProveedor","value","");
		}
	} else {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
	}
	
	return $objResponse;
}

function asignarMoneda($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	
	$objResponse->script("
	byId('tdNacionalizar').style.display = 'none';
	byId('tdlstNacionalizar').style.display = 'none';
	byId('trlstArancelGrupo').style.display = 'none';
	
	byId('aImpuestoArticulo').style.display = 'none';
	
	byId('fieldsetGastosImportacion').style.display = 'none';
	
	byId('trDatosImportacion').style.display = 'none';

	byId('btnAprobar').style.display = 'none';");
	
	if ($idModoCompra == 1) { // 1 = Nacional, 2 = Importacion
		$objResponse->assign("txtTasaCambio", "value", number_format(0, 3, ".", ","));
		if ($frmDcto['txtIdFactura'] > 0) {
			$objResponse->script("
			byId('aAgregarArticulo').style.display = 'none';
			byId('btnQuitarArticulo').style.display = 'none';
			byId('btnExportar').style.display = '';
			byId('aImportar').style.display = 'none';
			byId('trDatosImportacion').style.display = '';
			byId('liBasicos').style.display = 'none';
			byId('liRegistro').style.display = 'none';
			byId('liOtrosCargos').style.display = '';
			byId('liCodigosArancelarios').style.display = 'none';
			byId('btnGuardar').style.display = '';
			byId('btnAprobar').style.display = '';
			byId('btnCancelar').onclick = function () { window.open('iv_registro_compra_list.php', '_self'); }
			
			$(function() {
				$('ul.tabs').tabs('> .pane', {initialIndex: 2});
			});");
		} else {
			$objResponse->script("
			byId('aAgregarArticulo').style.display = '';
			byId('btnQuitarArticulo').style.display = '';
			byId('btnExportar').style.display = 'none';
			byId('aImportar').style.display = '';
			byId('btnGuardar').style.display = '';
			byId('btnAprobar').style.display = 'none';
	 		byId('btnCancelar').onclick = function () { window.open('iv_preregistro_compra_list.php', '_self'); }");
		}
	} else {
		if ($frmDcto['txtIdFactura'] > 0) {
			$objResponse->script("
			byId('fieldsetGastosImportacion').style.display = '';
			
			byId('btnExportar').style.display = '';
			byId('aImportar').style.display = 'none';
			
			byId('tdNacionalizar').style.display = '';
			byId('tdlstNacionalizar').style.display = '';
			byId('trlstArancelGrupo').style.display = '';
			byId('trDatosImportacion').style.display = '';
			byId('liBasicos').style.display = '';
			byId('liRegistro').style.display = '';
			byId('liOtrosCargos').style.display = '';
			byId('liCodigosArancelarios').style.display = '';
			byId('btnGuardar').style.display = '';
			byId('btnAprobar').style.display = '';
			byId('btnCancelar').onclick = function () { window.open('iv_registro_compra_list.php', '_self'); }");
			
			$objResponse->script("
			if (byId('trDatosImportacion').style.display == 'none') {
				xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			}");
		} else {
			$objResponse->script("
	 		byId('btnCancelar').onclick = function () { window.open('iv_preregistro_compra_list.php', '_self'); }");
		}
		
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
		
		$objResponse->script("
		byId('lstNacionalizar').className = 'inputHabilitado';
		byId('txtIdProvExportador').className = 'inputHabilitado';
		byId('txtIdProvConsignatario').className = 'inputHabilitado';
		byId('txtIdPaisAduana').className = 'inputHabilitado';
		byId('txtIdPaisOrigen').className = 'inputHabilitado';
		byId('txtIdPaisCompra').className = 'inputHabilitado';
		byId('txtDiferenciaCambiaria').className = 'inputHabilitado';
		byId('txtExpediente').className = 'inputInicial';
		byId('txtNumeroEmbarque').className = 'inputInicial';
		byId('txtPuertoEmbarque').className = 'inputHabilitado';
		byId('txtPorcSeguro').className = 'inputHabilitado';
		byId('txtPlanillaImportacion').className = 'inputHabilitado';
		byId('txtDctoTransporte').className = 'inputHabilitado';
		byId('txtFechaDctoTransporte').className = 'inputHabilitado';
		byId('txtFechaVencDctoTransporte').className = 'inputHabilitado';
		byId('txtFechaEstimadaLlegada').className = 'inputHabilitado';");
		
		$objResponse->loadCommands(cargaLstGrupoItem('lstActividadImportador','ActividadImportador',$frmTotalDcto['lstActividadImportador']));
		$objResponse->loadCommands(cargaLstGrupoItem('lstClaseImportador','ClaseImportador',$frmTotalDcto['lstClaseImportador']));
		$objResponse->loadCommands(cargaLstGrupoItem('lstClaseSolicitud','ClaseSolicitud',$frmTotalDcto['lstClaseSolicitud']));
		$objResponse->loadCommands(cargaLstGrupoItem('lstViaEnvio','ViaEnvio',$frmDcto['lstViaEnvio']));
		$objResponse->assign("txtDiferenciaCambiaria","value",number_format($frmTotalDcto['txtDiferenciaCambiaria'], 3, ".", ","));
		$objResponse->assign("txtPorcSeguro","value",number_format($frmTotalDcto['txtPorcSeguro'], 2, ".", ","));
	}
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	
	$objResponse->assign("hddIncluirImpuestos", "value", $rowMonedaOrigen['incluir_impuestos']);
	$objResponse->assign("txtIdMonedaNegociacion", "value", $rowMonedaOrigen['idmoneda']);
	$objResponse->assign("txtMonedaNegociacion", "value", $rowMonedaOrigen['descripcion']);
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	if ($rowMonedaOrigen['incluir_impuestos'] == 1) {
		$objResponse->script("
		byId('aImpuestoArticulo').style.display = '';");
	}
	
	// HABILITA O INHABILITA POR GASTO EL IMPUESTO DEPENDIENDO SI SE INCLUYE O NO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE pg_gastos.id_gasto = %s;", 
				valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGasto = mysql_fetch_assoc($rsGasto);
			
			if ($rowGasto['id_modo_gasto'] == 1) { // 1 = Gastos
				$objResponse->assign("spnGastoMoneda".$valor, "innerHTML", $abrevMonedaOrigen);
			} else if ($rowGasto['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
				$objResponse->assign("spnGastoMoneda".$valor, "innerHTML", $abrevMonedaLocal);
			}
			
			if (($rowGasto['id_modo_gasto'] == 1 && $rowMonedaOrigen['incluir_impuestos'] == 0)			// 1 = Gastos && 0 = No
			|| ($rowGasto['id_modo_gasto'] == 3 && $rowMonedaLocal['incluir_impuestos'] == 0)) {		// 3 = Gastos por Importacion && 0 = No
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						
						if ($valor1[0] == $valor) {
							$objResponse->script(sprintf("
							byId('imgIvaGasto%s:%s').style.visibility = '%s';
							byId('hddIvaGasto%s:%s').style.visibility = '%s';
							if (byId('hddIdIvaGasto%s:%s').value > 0) {
								byId('hddEstatusIvaGasto%s:%s').value = %s;
							}",
								$valor, $valor1[1], "hidden",
								$valor, $valor1[1], "hidden",
								$valor, $valor1[1],
								$valor, $valor1[1], 0));
						}
					}
				}
			} else if (($rowGasto['id_modo_gasto'] == 1 && $rowMonedaOrigen['incluir_impuestos'] == 1)	// 1 = Gastos && 1 = Si
			|| ($rowGasto['id_modo_gasto'] == 3 && $rowMonedaLocal['incluir_impuestos'] == 1)) {		// 3 = Gastos por Importacion && 1 = Si
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						
						if ($valor1[0] == $valor) {
							$objResponse->script(sprintf("
							byId('imgIvaGasto%s:%s').style.visibility = '%s';
							byId('hddIvaGasto%s:%s').style.visibility = '%s';
							if (byId('hddIdIvaGasto%s:%s').value > 0) {
								byId('hddEstatusIvaGasto%s:%s').value = %s;
							}",
								$valor, $valor1[1], "",
								$valor, $valor1[1], "",
								$valor, $valor1[1],
								$valor, $valor1[1], 1));
						}
					}
				}
			}
			$objResponse->script("byId('txtMontoGasto".$valor."').className = 'inputHabilitado';");
			
			$objResponse->script("
			byId('txtMedidaGasto".$valor."').style.display = '".(($rowGasto['id_tipo_medida'] > 0) ? "" : "none")."';
			byId('txtMedidaGasto".$valor."').className = 'inputCompletoHabilitado';");
			
			$existeTipoMedidaPeso = ($rowGasto['id_tipo_medida'] == 1 && str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]) > 0) ? true : $existeTipoMedidaPeso;
		}
	}
	
	// HABILITA O INHABILITA POR ARTICULO EL IMPUESTO Y EL ARANCEL DEPENDIENDO SI SE INCLUYE O NO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$objResponse->script(sprintf("
						byId('hddIvaItm%s:%s').style.visibility = '%s';
						if (byId('hddIdIvaItm%s:%s').value > 0) {
							byId('hddEstatusIvaItm%s:%s').value = %s;
						}",
							$valor, $valor1[1], (($rowMonedaOrigen['incluir_impuestos'] == 1) ? "" : "hidden"), // 0 = No , 1 = Si
							$valor, $valor1[1],
							$valor, $valor1[1], (($rowMonedaOrigen['incluir_impuestos'] == 1) ? 1 : 0)));
					}
				}
			}
			
			$objResponse->script("
			byId('lstTarifaAdValorem".$valor."').style.visibility = '".(($idModoCompra == 1) ? "hidden" : "")."';");
			
			$objResponse->script("byId('txtPesoItm".$valor."').style.display = 'none';");
			if ($existeTipoMedidaPeso == true) {
				$objResponse->script("byId('txtPesoItm".$valor."').style.display = '';");
				
				$objResponse->script("
				byId('txtPesoItm".$valor."').className = '".(($frmTotalDcto['lstGastoItem'] == 1) ? "inputSinFondo" : "inputCompleto")."';
				byId('txtPesoItm".$valor."').readOnly = ".(($frmTotalDcto['lstGastoItem'] == 1) ? "true" : "false").";");
			}
			
			$objResponse->script("
			byId('txtGastosItm".$valor."').className = '".(($frmTotalDcto['lstGastoItem'] == 1) ? "inputCompleto" : "inputSinFondo")."';
			byId('txtGastosItm".$valor."').readOnly = ".(($frmTotalDcto['lstGastoItem'] == 1) ? "false" : "true").";");
		}
	}
	
	$objResponse->assign("tdSubTotalMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdDescuentoMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdGastoConIvaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdGastoSinIvaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdTotalRegistroMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdTotalFacturaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdExentoMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdExoneradoMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdRetencionISLRMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdSustraendoISLRMoneda","innerHTML",$abrevMonedaOrigen);
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function asignarMotivo($idMotivo, $objDestino, $ingresoEgreso = NULL, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CP'");
	
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
	
	$objResponse->assign("txtId".$objDestino,"value",$row['id_motivo']);
	$objResponse->assign("txt".$objDestino,"value",htmlentities($row['descripcion']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaMotivo').click();");
	}
	
	return $objResponse;
}

function asignarPais($idPais, $nombreObjeto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryPais = sprintf("SELECT * FROM an_origen WHERE id_origen = %s;", valTpDato($idPais, "int"));
	$rsPais = mysql_query($queryPais);
	if (!$rsPais) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPais = mysql_fetch_assoc($rsPais);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowPais['id_origen']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowPais['nom_origen']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaPais').click();");
	}
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $nombreObjeto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor
	WHERE id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowProv['nombre']));
	$objResponse->assign("txtRif".$nombreObjeto,"value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContacto".$nombreObjeto,"value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$nombreObjeto,"value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefono".$nombreObjeto,"value",$rowProv['telefono']);
	
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

function buscarArticuloSustituto($frmBuscarArtSust, $frmArticulo) {
	$objResponse = new xajaxResponse();
	
	$idArticulo = ($frmArticulo['hddIdArticuloSust'] > 0) ? $frmArticulo['hddIdArticuloSust'] : $frmArticulo['hddIdArticulo'];
	
	$valBusq = sprintf("%s|%s",
		$idArticulo,
		$frmBuscarArtSust['txtCriterioBuscarArtSust']);
	
	$objResponse->loadCommands(listaArticuloSustituto(0, "", "", $valBusq));
	
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

function buscarDisponibilidadUbicacion($frmAlmacen) {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_empresa = %s",
		valTpDato($frmAlmacen['lstEmpresa'], "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_articulo_almacen = %s",
		valTpDato(1, "int"));
	
	if ($frmAlmacen['lstAlmacenAct'] != "-1" && $frmAlmacen['lstAlmacenAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_almacen = %s",
			valTpDato($frmAlmacen['lstAlmacenAct'], "int"));
	}
	
	if ($frmAlmacen['lstCalleAct'] != "-1" && $frmAlmacen['lstCalleAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_calle = %s",
			valTpDato($frmAlmacen['lstCalleAct'], "int"));
	}
	
	if ($frmAlmacen['lstEstanteAct'] != "-1" && $frmAlmacen['lstEstanteAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_estante = %s",
			valTpDato($frmAlmacen['lstEstanteAct'], "int"));
	}
	
	if ($frmAlmacen['lstTramoAct'] != "-1" && $frmAlmacen['lstTramoAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tramo = %s",
			valTpDato($frmAlmacen['lstTramoAct'], "int"));
	}
	
	if ($frmAlmacen['lstCasillaAct'] != "-1" && $frmAlmacen['lstCasillaAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_casilla = %s",
			valTpDato($frmAlmacen['lstCasillaAct'], "int"));
	}
	
	$queryArtUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion %s", $sqlBusq);
	$rsArtUbic = mysql_query($queryArtUbic);
	if (!$rsArtUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtUbic = mysql_num_rows($rsArtUbic);
	$rowArtUbic = mysql_fetch_assoc($rsArtUbic);
	
	if ($totalRowsArtUbic > 0) {
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($rowArtUbic['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $rowArtUbic['foto'];
		
		$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
		$html .= "<tr>";
			$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
			$html .= "<td align=\"center\">"."Ubicación Ocupada"."</td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$html .= "<table border=\"1\" class=\"tabla divMsjInfo2\" cellpadding=\"2\" width=\"100%\">";
		$html .= "<tr>";
			$html .= "<td>"."<img src=\"".$imgFoto."\" height=\"100\"/>"."</td>";
			$html .= "<td valign=\"top\" width=\"100%\">";
				$html .= "<table width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"20%\">"."Código:"."</td>";
					$html .= "<td width=\"80%\">".elimCaracter($rowArtUbic['codigo_articulo'],";")."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">"."Descripción:"."</td>";
					$html .= "<td>".utf8_encode($rowArtUbic['descripcion'])."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">"."Unid. Disponible:"."</td>";
					$html .= "<td>".$rowArtUbic['cantidad_disponible_logica']."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "</table>";
	} else {
		$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo\" width=\"100%\">";
		$html .= "<tr>";
			$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"/></td>";
			$html .= "<td align=\"center\">"."Ubicación Disponible"."</td>";
		$html .= "</tr>";
		$html .= "</table>";
	}
	
	$objResponse->assign("tdMsj","innerHTML",$html);

	return $objResponse;
}

function buscarGasto($frmBuscarGasto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarGasto['txtCriterioBuscarGasto']);
	
	$objResponse->loadCommands(listaGasto(0, "nombre", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarGastoImportacion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterioBuscarGastoImportacion']);
	
	$objResponse->loadCommands(listaGastoImportacion(0, "nombre", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarMotivo($frmBuscarMotivo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarMotivo['hddObjDestinoMotivo'],
		$frmBuscarMotivo['hddIngresoEgresoMotivo'],
		$frmBuscarMotivo['txtCriterioBuscarMotivo']);
	
	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarPais($frmBuscarPais) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarPais['txtCriterioBuscarPais'],
		$frmBuscarPais['hddObjDestinoPais']);
	
	$objResponse->loadCommands(listaPais(0, "id_origen", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarPedido($frmBuscar, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedidoCompra(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarProveedor['txtCriterioBuscarProveedor'],
		$frmBuscarProveedor['hddObjDestino']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarRegistroCompra($frmBuscarRegistroCompra) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarRegistroCompra['txtCriterioBuscarRegistroCompra']);
	
	$objResponse->loadCommands(listaRegistroCompra(0, "id_factura", "DESC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
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
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjAdv = $frmTotalDcto['cbxAdv'];
	if (isset($arrayObjAdv)) {
		foreach ($arrayObjAdv as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trAdValorem:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjAdvDif = $frmTotalDcto['cbxAdvDif'];
	if (isset($arrayObjAdvDif)) {
		foreach ($arrayObjAdvDif as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trAdValoremDif:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjArancel = $frmTotalDcto['cbxArancel'];
	if (isset($arrayObjArancel)) {
		foreach ($arrayObjArancel as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trArancel:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$arrayObjIvaLocal = $frmTotalDcto['cbxIvaLocal'];
	if (isset($arrayObjIvaLocal)) {
		foreach ($arrayObjIvaLocal as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trIvaLocal:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valorItm,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valorItm,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	if (isset($arrayObjGasto)) {
		$i = 0;
		foreach ($arrayObjGasto as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmGasto:".$valor, "className", $clase." textoGris_11px");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjOtroCargo = $frmTotalDcto['cbxOtroCargo'];
	if (isset($arrayObjOtroCargo)) {
		$i = 0;
		foreach ($arrayObjOtroCargo as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmOtroCargo:".$valor,"className",$clase." textoGris_11px");
		}
	}
	$objResponse->assign("hddObjOtroCargo","value",((count($arrayObjOtroCargo) > 0) ? implode("|",$arrayObjOtroCargo) : ""));
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdFactura'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = (str_replace(",", "", $frmDcto['txtTasaCambio']) > 0) ? str_replace(",", "", $frmDcto['txtTasaCambio']) : 1;
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	$incluirIvaMonedaOrigen = $rowMonedaOrigen['incluir_impuestos'];
	
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
			$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
			$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
			$txtTotalItm = $txtCantRecibItm * $txtCostoItm;
			
			$txtSubTotal += $txtTotalItm;
			$subTotalDescuentoItm += $txtCantRecibItm * $hddMontoDescuentoItm;
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
	
	// VERIFICA LOS VALORES DE CADA ITEM PARA CALCULAR EL IMPUESTO Y EL SUBTOTAL
	$txtTotalExentoOrigen = 0;
	$txtTotalExoneradoOrigen = 0;
	$arrayIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
			$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
			$txtTotalItm = $txtCantRecibItm * $txtCostoItm;
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
			
			$hddTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
					$valorIvaItm = explode(":", $valorIvaItm);
					
					if ($valorIvaItm[0] == $valor) {
						$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$valor.':'.$valorIvaItm[1]]] = $valorIvaItm[1];
						$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valorIvaItm[1]];
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valor.':'.$valorIvaItm[1]];
					}
				}
			}
			
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);", 
				valTpDato(implode(",", $arrayIdIvaItm), "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$idIva = $rowIva['idIva'];
				$porcIva = $rowIva['iva'];
				$lujoIva = $rowIva['lujo'];
				$estatusIva = ($incluirIvaMonedaOrigen == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if ($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
					$txtTotalExentoOrigen += $txtTotalNetoItm;
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
					
					if ($idIva > 0 && $existIva == false && $txtTotalNetoItm > 0) {
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
				$txtTotalExentoOrigen += $txtTotalNetoItm;
			}
			
			$objResponse->assign("txtTotalItm".$valor, "value", number_format($txtTotalItm, 2, ".", ","));
		}
	}
	
	// CALCULA LOS GASTOS DE CADA ARTICULO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtGastosItm = 0;
			$hddGastosImportNacItm = 0;
			$hddGastosImportItm = 0;
			
			if ($frmListaArticulo['hddIdArticuloItm'.$valor] > 0) {
				$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
				$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
				$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
				$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
				$txtTotalItm = $txtCantRecibItm * $txtCostoItm;
				
				$txtSubTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $txtSubTotal - $txtSubTotalDescuento : $txtSubTotal;
				// GASTOS INCLUIDOS EN FACTURA
				if (isset($arrayObjGasto)) {
					foreach ($arrayObjGasto as $indice2 => $valor2) {
						$idGasto = $frmTotalDcto['hddIdGasto'.$valor2];
						$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor2]);
						$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor2]);
						$hddIdModoGasto = $frmTotalDcto['hddIdModoGasto'.$valor2];
						
						if (round($txtMontoGasto, 2) != 0) {
							if ($hddIdModoGasto == 1) { // 1 = Gastos
								if ($txtMedidaGasto > 0) {
									$gastosItm = $txtMontoGasto / $txtMedidaGasto * ($txtCantRecibItm * $txtPesoItm);
								} else {
									$gastosItm = ($txtSubTotalDescuentoItm > 0) ? (($txtTotalItm - $hddTotalDescuentoItm) * $txtMontoGasto) / $txtSubTotalDescuentoItm : ($txtTotalItm * $txtMontoGasto) / $txtSubTotal;
								}
								
								$txtGastosItm += $gastosItm;
							} else if ($hddIdModoGasto == 3) { // 3 = Gastos por Importacion
								$gastosItm = (($txtSubTotalDescuentoItm * $txtTasaCambio) > 0) ? ((($txtTotalItm - $hddTotalDescuentoItm) * $txtTasaCambio) * $txtMontoGasto) / ($txtSubTotalDescuentoItm * $txtTasaCambio) : (($txtTotalItm * $txtTasaCambio) * $txtMontoGasto) / ($txtSubTotal * $txtTasaCambio);
								
								$hddGastosImportNacItm += $gastosItm;
							}
						}
					}
				}
				$txtGastosItm = ($frmTotalDcto['lstGastoItem'] == 1) ? str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]) : $txtGastosItm;
				
				$frmListaArticulo['txtGastosItm'.$valor] = $txtGastosItm;
				$objResponse->assign("txtGastosItm".$valor, "value", number_format($txtGastosItm, 3, ".", ","));
				$objResponse->assign("hddGastosImportNacItm".$valor, "value", number_format($hddGastosImportNacItm, 2, ".", ","));
				
				$txtTotalPesoItem += $txtCantRecibItm * $txtPesoItm;
				$txtTotalGastoItem += $txtGastosItm;
				
				// OTROS CARGOS
				if (isset($arrayObjOtroCargo)) {
					$spnTotalOtrosCargos = 0;
					foreach ($arrayObjOtroCargo as $indice2 => $valor2) {
						$hddSubTotalFacturaGastoCargo = str_replace(",", "", $frmTotalDcto['hddSubTotalFacturaGastoCargo'.$valor2]);
						$montoOtrosCargosItm = (($txtTotalItm - $hddTotalDescuentoItm) * $hddSubTotalFacturaGastoCargo) / $txtSubTotal;
						
						$hddGastosImportItm += $montoOtrosCargosItm;
						
						$spnTotalOtrosCargos += $hddSubTotalFacturaGastoCargo;
					}
				}
				$objResponse->assign("hddGastosImportItm".$valor, "value", number_format($hddGastosImportItm, 2, ".", ","));
			}
		}
	}
	
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IMPUESTO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s
				AND id_modo_gasto IN (1);", 
				valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				if ($frmTotalDcto['hddTipoGasto'.$valor] == 0) { // SACA EL MONTO MEDIANTE EL PORCENTAJE
					$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
					$txtMontoGasto = ($txtSubTotal == 0) ? 0 : $txtPorcGasto * ($txtSubTotal / 100);
					$objResponse->assign('txtMontoGasto'.$valor, "value", number_format($txtMontoGasto, 2, ".", ","));
				} else if ($frmTotalDcto['hddTipoGasto'.$valor] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
					$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
					$txtPorcGasto = ($txtSubTotal == 0) ? 0 : $txtMontoGasto * (100 / $txtSubTotal);
					$objResponse->assign('txtPorcGasto'.$valor, "value", number_format($txtPorcGasto, 2, ".", ","));
				}
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$arrayPosIvaItm = array(-1);
				$arrayIdIvaItm = array(-1);
				$arrayIvaItm = array(-1);
				$arrayEstatusIvaItm = array(-1);
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						
						if ($valor1[0] == $valor) {
							$arrayPosIvaItm[$frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]]] = $valor1[1];
							$arrayIdIvaItm[] = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]];
							$arrayIvaItm[] = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valor1[1]];
							$arrayEstatusIvaItm[] = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valor1[1]];
						}
					}
				}
				
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);",
					valTpDato(implode(",", $arrayIdIvaItm), "campo"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaOrigen == 1) ? $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if ($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosSinIvaOrigen += $txtMontoGasto; break;
							default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
						}
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
						$subTotalIvaGasto = ($txtMontoGasto * $porcIva) / 100;
						
						$existIva = false;
						if (isset($arrayIva)) {
							foreach ($arrayIva as $indiceIva => $valorIva) {
								if ($arrayIva[$indiceIva][0] == $idIva) {
									$arrayIva[$indiceIva][1] += $txtMontoGasto;
									$arrayIva[$indiceIva][2] += $subTotalIvaGasto;
									$existIva = true;
								}
							}
						}
						
						if ($idIva > 0 && $existIva == false
						&& $txtMontoGasto > 0) {
							$arrayIva[] = array(
								$idIva,
								$txtMontoGasto,
								$subTotalIvaGasto,
								$porcIva,
								$lujoIva,
								$rowIva['observacion']);
						}
					}
				}
				
				if ($totalRowsIva > 0 && in_array(1,$arrayEstatusIvaItm)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosConIvaOrigen += $txtMontoGasto; break;
						default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
					}
				} else if (!($totalRowsIva > 0)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosSinIvaOrigen += $txtMontoGasto; break;
						default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
					}
				}
				
				$htmlGastos .= "<td width=\"".(100 / count($arrayObjGasto))."%\">".$rowGasto['nombre']."</td>";
			}
			
			$txtTotalGasto += ($frmTotalDcto['hddIdModoGasto'.$valor] == 1) ? str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]) : 0;
			$txtTotalGastoImportacion += ($frmTotalDcto['hddIdModoGasto'.$valor] == 3) ? str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]) : 0;
		}
		
		$htmlGastos = ($frmTotalDcto['lstGastoItem'] == 1) ? "<td></td>" : $htmlGastos;
		
		$objResponse->assign("tdGastosArancel","innerHTML","<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr align=\"center\">".$htmlGastos."</tr></table>");
	}
	
	if ($idModoCompra == 2) { // 2 = Importacion
		// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL ARANCEL Y EL PRECIO CIF PARA INCLUIRLO EN EL IMPUESTO
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
				$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
				$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
				$txtGastosItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]);
				$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
				$txtTotalItm = $txtCantRecibItm * $txtCostoItm;
				
				$subTotalItm = $txtTotalItm;
				$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($subTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
				$subTotalItm = $subTotalItm - $totalDescuentoItm;
				
				// CALCULA LOS DATOS DE IMPORTACION
				$precioTotalFOB = $subTotalItm;
				$totalCIF = $precioTotalFOB + $txtGastosItm;
				$totalPrecioCIF = $totalCIF * $txtTasaCambio;
				$tarifaAdValorem = ($totalPrecioCIF * str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor])) / 100;
				$tarifaAdValoremDif = ($totalPrecioCIF * str_replace(",", "", $frmListaArticulo['lstTarifaAdValoremDif'.$valor])) / 100;
				
				$subTotalItmCambio = $totalPrecioCIF + $tarifaAdValorem;
				
				$txtSubTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $txtSubTotal - $txtSubTotalDescuento : $txtSubTotal;
				// GASTOS INCLUIDOS EN FACTURA
				$arrayGastosItm = array();
				$arrayGastosImportNacionalItm = array();
				if (isset($arrayObjGasto)) {
					foreach ($arrayObjGasto as $indiceGasto => $valorGasto) {
						$idGasto = $frmTotalDcto['hddIdGasto'.$valorGasto];
						$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valorGasto]);
						$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valorGasto]);
						$hddIdModoGasto = $frmTotalDcto['hddIdModoGasto'.$valorGasto];
						
						if (round($txtMontoGasto, 2) != 0) {
							if ($hddIdModoGasto == 1) { // 1 = Gastos
								if ($txtMedidaGasto > 0) {
									$gastosItm = $txtMontoGasto / $txtMedidaGasto * ($txtCantRecibItm * $txtPesoItm);
								} else {
									$gastosItm = ($txtSubTotalDescuentoItm > 0) ? (($txtTotalItm - $hddTotalDescuentoItm) * $txtMontoGasto) / $txtSubTotalDescuentoItm : ($txtTotalItm * $txtMontoGasto) / $txtSubTotal;
								}
								
								$arrayGastosItm[$idGasto] = $gastosItm;
								$arrayTotalGastosItm[$idGasto] += $gastosItm;
									
								($frmTotalDcto['lstGastoItem'] == 1) ? $arrayTotalGastosItm = array($arrayTotalGastosItm[0] + $gastosItm) : "";
							} else if ($hddIdModoGasto == 3) { // 3 = Gastos por Importacion
								$gastosItm = (($txtSubTotalDescuentoItm * $txtTasaCambio) > 0) ? ((($txtTotalItm - $hddTotalDescuentoItm) * $txtTasaCambio) * $txtMontoGasto) / ($txtSubTotalDescuentoItm * $txtTasaCambio) : (($txtTotalItm * $txtTasaCambio) * $txtMontoGasto) / ($txtSubTotal * $txtTasaCambio);
								
								$arrayGastosImportNacionalItm[$idGasto] = $gastosItm;
								$arrayTotalGastosImportNacionalItm[$idGasto] += $gastosItm;
								
								($frmTotalDcto['lstGastoItem'] == 1) ? $arrayTotalGastosImportNacionalItm = array($arrayTotalGastosImportNacionalItm[0] + $gastosItm) : "";
							}
						}
					}
				}
				
				$existeArancel = false;
				if (isset($arrayArancel)) {
					foreach ($arrayArancel as $indiceArancel => $valorArancel) {
						if ($frmListaArticulo['hddIdArancelFamiliaItm'.$valor] == $arrayArancel[$indiceArancel]['id_arancel_familia']
						&& str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor]) == $arrayArancel[$indiceArancel]['porcentaje_grupo']) {
							$existeArancel = true;
							
							$arrayArancel[$indiceArancel]['monto_fob'] = $arrayArancel[$indiceArancel]['monto_fob'] + $txtTotalItm;
							$arrayGasto = array();
							if (isset($arrayArancel[$indiceArancel]['monto_gasto'])) {
								foreach ($arrayArancel[$indiceArancel]['monto_gasto'] as $indice3 => $valor3) {
									$arrayGasto[$indice3] = $valor3 + $arrayGastosItm[$indice3];
									
									($frmTotalDcto['lstGastoItem'] == 1) ? $arrayGasto = array($valor3 + $txtGastosItm) : "";
								}
							}
							$arrayArancel[$indiceArancel]['monto_gasto'] = $arrayGasto;
							$arrayArancel[$indiceArancel]['monto_cif'] = $arrayArancel[$indiceArancel]['monto_cif'] + $totalPrecioCIF;
							$arrayArancel[$indiceArancel]['peso_neto'] += ($txtCantRecibItm * $txtPesoItm);
							$arrayArancel[$indiceArancel]['cant_articulos'] += $txtCantRecibItm;
							$arrayArancel[$indiceArancel]['cant_items']++;
						}
					}
				}
				
				if ($existeArancel == false) {
					($frmTotalDcto['lstGastoItem'] == 1) ? $arrayGastosItm = array($txtGastosItm) : "";
					
					$arrayArancel[] = array(
						"id_arancel_familia" => $frmListaArticulo['hddIdArancelFamiliaItm'.$valor],
						"porcentaje_grupo" => str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor]), // % ADV
						"monto_fob" => $txtTotalItm, // F.O.B.
						"monto_gasto" => $arrayGastosItm, // GASTOS
						"monto_cif" => $totalPrecioCIF, // C.I.F.
						"peso_neto" => ($txtCantRecibItm * $txtPesoItm), // Peso Neto
						"cant_articulos" => $txtCantRecibItm, // Cant. Articulos
						"cant_items" => 1); // Cant. Items
				}
				
				
				// VERIFICA SI EXISTE LA FAMILIA ARANCELARIA
				$existeAdValorem = false;
				if (isset($arrayAdValorem)) {
					foreach ($arrayAdValorem as $indiceAdValorem => $valorAdValorem) {
						if (str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor]) == $arrayAdValorem[$indiceAdValorem]['porcentaje_grupo']) {
							$existeAdValorem = true;
							$arrayAdValorem[$indiceAdValorem]['monto_tarifa_advalorem'] = $arrayAdValorem[$indiceAdValorem]['monto_tarifa_advalorem'] + $tarifaAdValorem;
							$arrayAdValorem[$indiceAdValorem]['cant_items']++;
							$arrayAdValorem[$indiceAdValorem]['cant_articulos'] += $txtCantRecibItm;
						}
					}
				}
				
				if ($existeAdValorem == false) {
					$arrayAdValorem[] = array(
						"porcentaje_grupo" => str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor]),
						"monto_tarifa_advalorem" => $tarifaAdValorem,
						"cant_articulos" => $txtCantRecibItm,
						"cant_items" => 1);
				}
				
				
				if (str_replace(",", "", $frmListaArticulo['lstTarifaAdValoremDif'.$valor]) != 0) {
					// VERIFICA SI EXISTE LA FAMILIA ARANCELARIA DE LAS QUE AFECTAN SOLO LA NACIONALIZACION
					$existeAdValoremDif = false;
					if (isset($arrayAdValoremDif)) {
						foreach ($arrayAdValoremDif as $indiceAdValoremDif => $valorAdValoremDif) {
							if (str_replace(",", "", $frmListaArticulo['lstTarifaAdValoremDif'.$valor]) == $arrayAdValoremDif[$indiceAdValoremDif]['porcentaje_grupo']) {
								$existeAdValoremDif = true;
								$arrayAdValoremDif[$indiceAdValoremDif]['monto_tarifa_advalorem'] = $arrayAdValoremDif[$indiceAdValoremDif]['monto_tarifa_advalorem'] + $tarifaAdValoremDif;
								$arrayAdValoremDif[$indiceAdValoremDif]['cant_items']++;
								$arrayAdValoremDif[$indiceAdValoremDif]['cant_articulos'] += $txtCantRecibItm;
							}
						}
					}
					
					if ($existeAdValoremDif == false) {
						$arrayAdValoremDif[] = array(
							"porcentaje_grupo" => str_replace(",", "", $frmListaArticulo['lstTarifaAdValoremDif'.$valor]),
							"monto_tarifa_advalorem" => $tarifaAdValoremDif,
							"cant_articulos" => $txtCantRecibItm,
							"cant_items" => 1);
					}
				}
				
				
				// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva;");
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($frmDcto['lstNacionalizar'] == 1 && $incluirIvaMonedaLocal == 1) ? 1 : 0;
					
					if ($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
						$totalExentoLocal += ($subTotalItmCambio + $tarifaAdValoremDif);
					} else if ($estatusIva != 0) {
						$subTotalIvaItm = (($subTotalItmCambio + $tarifaAdValoremDif) * $porcIva) / 100;
						
						$existIva = false;
						if (isset($arrayIvaLocal)) {
							foreach ($arrayIvaLocal as $indiceIva => $valorIva) {
								if ($arrayIvaLocal[$indiceIva][0] == $idIva) {
									$arrayIvaLocal[$indiceIva][1] += ($subTotalItmCambio + $tarifaAdValoremDif);
									$arrayIvaLocal[$indiceIva][2] += $subTotalIvaItm;
									$existIva = true;
								}
							}
						}
						
						if ($idIva > 0 && $existIva == false
						&& ($txtTotalItm - $hddTotalDescuentoItm) > 0) {
							$arrayIvaLocal[] = array(
								$idIva,
								($subTotalItmCambio + $tarifaAdValoremDif),
								$subTotalIvaItm,
								$porcIva,
								$lujoIva,
								$rowIva['observacion']);
						}
					}
				}
			}
		}
		
		// GASTOS POR IMPORTACION
		if (isset($arrayObjGasto)) {
			foreach ($arrayObjGasto as $indiceGasto => $valorGasto) {
				// BUSCA LOS DATOS DEL GASTO
				$queryGasto = sprintf("SELECT * FROM pg_gastos
				WHERE id_gasto = %s
					AND id_modo_gasto IN (3);", 
					valTpDato($frmTotalDcto['hddIdGasto'.$valorGasto], "int"));
				$rsGasto = mysql_query($queryGasto);
				if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
					if ($frmTotalDcto['hddTipoGasto'.$valorGasto] == 0) { // SACA EL MONTO MEDIANTE EL PORCENTAJE
						$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valorGasto]);
						$txtMontoGasto = ($txtSubTotal == 0) ? 0 : $txtPorcGasto * ($txtSubTotal / 100);
						$objResponse->assign('txtMontoGasto'.$valorGasto, "value", number_format($txtMontoGasto, 2, ".", ","));
					} else if ($frmTotalDcto['hddTipoGasto'.$valorGasto] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
						$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valorGasto]);
						$txtPorcGasto = ($txtSubTotal == 0) ? 0 : $txtMontoGasto * (100 / $txtSubTotal);
						$objResponse->assign('txtPorcGasto'.$valorGasto, "value", number_format($txtPorcGasto, 2, ".", ","));
					}
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					$arrayPosIvaItm = array(-1);
					$arrayIdIvaItm = array(-1);
					$arrayIvaItm = array(-1);
					if (isset($arrayObjIvaGasto)) {
						foreach ($arrayObjIvaGasto as $indiceIvaGasto => $valorIvaGasto) {
							$valorIvaGasto = explode(":", $valorIvaGasto);
							
							if ($valorIvaGasto[0] == $valorGasto) {
								$arrayPosIvaItm[$frmTotalDcto['hddIdIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]]] = $valorIvaGasto[1];
								$arrayIdIvaItm[] = $frmTotalDcto['hddIdIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
								$arrayIvaItm[] = $frmTotalDcto['hddIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
							}
						}
					}
					
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);",
						valTpDato(implode(",", $arrayIdIvaItm), "campo"));
					$rsIva = mysql_query($queryIva);
					if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsIva = mysql_num_rows($rsIva);
					while ($rowIva = mysql_fetch_assoc($rsIva)) {
						$idIva = $rowIva['idIva'];
						$porcIva = $rowIva['iva'];
						$lujoIva = $rowIva['lujo'];
						$estatusIva = ($incluirIvaMonedaLocal == 1 && $frmDcto['lstNacionalizar'] == 1) ? 1 : 0;
						
						if ($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
							$gastosSinIva += $txtMontoGasto;
						} else if ($estatusIva != 0) {
							$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valorGasto.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
							$subTotalIvaGasto = ($txtMontoGasto * $porcIva) / 100;
							
							$existIva = false;
							if (isset($arrayIvaLocal)) {
								foreach ($arrayIvaLocal as $indiceIva => $valorIva) {
									if ($arrayIvaLocal[$indiceIva][0] == $idIva) {
										$arrayIvaLocal[$indiceIva][1] += $txtMontoGasto;
										$arrayIvaLocal[$indiceIva][2] += $subTotalIvaGasto;
										$existIva = true;
									}
								}
							}
							
							if ($idIva > 0 && $existIva == false
							&& $txtMontoGasto > 0) {
								$arrayIvaLocal[] = array(
									$idIva,
									$txtMontoGasto,
									$subTotalIvaGasto,
									$porcIva,
									$lujoIva,
									$rowIva['observacion']);
							}
						}
					}
					
					if ($totalRowsIva > 0) {
						$gastosConIva += $txtMontoGasto;
					} else {
						$gastosSinIva += $txtMontoGasto;
					}
				}
			}
		}
	}
	
	
	// CREA LOS ELEMENTOS DE LOS ARANCELES
	if (isset($arrayAdValorem)) {
		foreach ($arrayAdValorem as $indiceAdValorem => $valorAdValorem) {
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trAdValorem:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\">%s</td>".
					"<td>".
						"<table width=\"%s\">".
						"<tr align=\"left\">".
							"<td>Cant. Items:</td><td align=\"right\">%s</td>".
						"</tr>".
						"</table>".
					"</td>".
					"<td>".
						"<table width=\"%s\">".
						"<tr align=\"left\">".
							"<td>Cant. Art.:</td><td align=\"right\">%s</td>".
						"</tr>".
						"</table>".
					"</td>".
					"<td>%s</td>".
					"<td><input type=\"text\" id=\"txtSubTotalAdValorem%s\" name=\"txtSubTotalAdValorem%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxAdv\" name=\"cbxAdv[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"</tr>';
				
				obj = byId('trAdValorem:%s');
				if (obj == undefined)
					$('#trRetencionISLR').before(elemento);",
				$indiceAdValorem,
					"% ADV ".$arrayAdValorem[$indiceAdValorem]['porcentaje_grupo'].":",
					"100%",
					$arrayAdValorem[$indiceAdValorem]['cant_items'],
					"100%",
					number_format($arrayAdValorem[$indiceAdValorem]['cant_articulos'], 2, ".", ","),
					$abrevMonedaLocal,
					$indiceAdValorem, $indiceAdValorem, number_format($arrayAdValorem[$indiceAdValorem]['monto_tarifa_advalorem'], 2, ".", ","),
						$indiceAdValorem,
				
				$indiceAdValorem));
				
				$arrayTotalAdValorem['cant_items'] += $arrayAdValorem[$indiceAdValorem]['cant_items'];
				$arrayTotalAdValorem['cant_articulos'] += $arrayAdValorem[$indiceAdValorem]['cant_articulos'];
				$arrayTotalAdValorem['monto_tarifa_advalorem'] += $arrayAdValorem[$indiceAdValorem]['monto_tarifa_advalorem'];
		}
		
		// INSERTA EL ARTICULO SIN INJECT
		$objResponse->script(sprintf("
		var elemento = '<tr align=\"right\" id=\"trAdValorem:%s\" class=\"trResaltarTotal\">".
				"<td class=\"tituloCampo\">%s</td>".
				"<td>%s</td>".
				"<td>%s</td>".
				"<td>%s</td>".
				"<td><input type=\"text\" id=\"txtTotalAdValorem\" name=\"txtTotalAdValorem\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/>".
					"<input type=\"checkbox\" id=\"cbxAdv\" name=\"cbxAdv[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"</tr>';
			
			obj = byId('trAdValorem:%s');
			if (obj == undefined)
				$('#trRetencionISLR').before(elemento);",
			$indiceAdValorem + 1,
				"Total ADV:",
				$arrayTotalAdValorem['cant_items'],
				number_format($arrayTotalAdValorem['cant_articulos'], 2, ".", ","),
				$abrevMonedaLocal,
				number_format($arrayTotalAdValorem['monto_tarifa_advalorem'], 2, ".", ","),
					$indiceAdValorem + 1,
			
			$indiceAdValorem + 1));
	}
	
	// CREA LOS ELEMENTOS DE LOS ARANCELES
	if (isset($arrayAdValoremDif)) {
		$indiceAdValoremDif = -1;
		$objResponse->script(sprintf("
		var elemento = '".
			"<tr align=\"right\" id=\"trAdValoremDif:%s\" class=\"textoGris_11px\">".
				"<td colspan=\"5\"><hr>".
					"<input type=\"checkbox\" id=\"cbxAdvDif\" name=\"cbxAdvDif[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"</tr>';
			
			obj = byId('trAdValoremDif:%s');
			if (obj == undefined)
				$('#trRetencionISLR').before(elemento);",
			$indiceAdValoremDif,
					$indiceAdValoremDif,
			
			$indiceAdValoremDif));
		
		foreach ($arrayAdValoremDif as $indiceAdValoremDif => $valorAdValoremDif) {
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"right\" id=\"trAdValoremDif:%s\" class=\"textoGris_11px\">".
					"<td class=\"tituloCampo\">%s</td>".
					"<td>".
						"<table width=\"%s\">".
						"<tr align=\"left\">".
							"<td>Cant. Items:</td><td align=\"right\">%s</td>".
						"</tr>".
						"</table>".
					"</td>".
					"<td>".
						"<table width=\"%s\">".
						"<tr align=\"left\">".
							"<td>Cant. Art.:</td><td align=\"right\">%s</td>".
						"</tr>".
						"</table>".
					"</td>".
					"<td>%s</td>".
					"<td><input type=\"text\" id=\"txtSubTotalAdValoremDif%s\" name=\"txtSubTotalAdValoremDif%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxAdvDif\" name=\"cbxAdvDif[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"</tr>';
				
				obj = byId('trAdValoremDif:%s');
				if (obj == undefined)
					$('#trRetencionISLR').before(elemento);",
				$indiceAdValoremDif,
					"% ADV ".$arrayAdValoremDif[$indiceAdValoremDif]['porcentaje_grupo'].":",
					"100%",
					$arrayAdValoremDif[$indiceAdValoremDif]['cant_items'],
					"100%",
					number_format($arrayAdValoremDif[$indiceAdValoremDif]['cant_articulos'], 2, ".", ","),
					$abrevMonedaLocal,
					$indiceAdValoremDif, $indiceAdValoremDif, number_format($arrayAdValoremDif[$indiceAdValoremDif]['monto_tarifa_advalorem'], 2, ".", ","),
						$indiceAdValoremDif,
				
				$indiceAdValoremDif));
				
				$arrayTotalAdValoremDif['cant_items'] += $arrayAdValoremDif[$indiceAdValoremDif]['cant_items'];
				$arrayTotalAdValoremDif['cant_articulos'] += $arrayAdValoremDif[$indiceAdValoremDif]['cant_articulos'];
				$arrayTotalAdValoremDif['monto_tarifa_advalorem'] += $arrayAdValoremDif[$indiceAdValoremDif]['monto_tarifa_advalorem'];
		}
		
		// INSERTA EL ARTICULO SIN INJECT
		$objResponse->script(sprintf("
		var elemento = '<tr align=\"right\" id=\"trAdValoremDif:%s\" class=\"trResaltarTotal\">".
				"<td class=\"tituloCampo\">%s</td>".
				"<td>%s</td>".
				"<td>%s</td>".
				"<td>%s</td>".
				"<td><input type=\"text\" id=\"txtTotalAdValoremDif\" name=\"txtTotalAdValoremDif\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/>".
					"<input type=\"checkbox\" id=\"cbxAdvDif\" name=\"cbxAdvDif[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"</tr>';
			
			obj = byId('trAdValoremDif:%s');
			if (obj == undefined)
				$('#trRetencionISLR').before(elemento);",
			$indiceAdValoremDif + 1,
				"Total ADV:",
				$arrayTotalAdValoremDif['cant_items'],
				number_format($arrayTotalAdValoremDif['cant_articulos'], 2, ".", ","),
				$abrevMonedaLocal,
				number_format($arrayTotalAdValoremDif['monto_tarifa_advalorem'], 2, ".", ","),
					$indiceAdValoremDif + 1,
			
			$indiceAdValoremDif + 1));
	}
	
	
	// CREA LOS ELEMENTOS DE LOS ARANCELES
	if (isset($arrayArancel)) {
		foreach ($arrayArancel as $indiceArancel => $valorArancel) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			// BUSCA LOS DATOS DEL ARANCEL
			$query = sprintf("SELECT * FROM pg_arancel_familia WHERE id_arancel_familia = %s;",
				valTpDato($arrayArancel[$indiceArancel]['id_arancel_familia'], "int"));
			$rs = mysql_query($query);
			if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$htmlGastos = "";
			if (isset($arrayArancel[$indiceArancel]['monto_gasto'])) {
				$htmlGastos = "<table width=\"100%\">";
				$htmlGastos .= "<tr>";
				foreach($arrayArancel[$indiceArancel]['monto_gasto'] as $indice2 => $valor2) {
					$htmlGastos .= "<td align=\"right\" width=\"".(100 / count($arrayArancel[$indiceArancel]['monto_gasto']))."%\">".number_format($valor2, 2, ".", ",")."</td>";
				}
				$htmlGastos .= "</tr>";
				$htmlGastos .= "</table>";
			}
			
			$htmlTotalGastos = "";
			if (isset($arrayTotalGastosItm)) {
				$htmlTotalGastos = "<table width=\"100%\">";
				$htmlTotalGastos .= "<tr>";
				foreach($arrayTotalGastosItm as $indiceTotalGastosItm => $valorTotalGastosItm) {
					$htmlTotalGastos .= "<td align=\"right\" width=\"".(100 / count($arrayTotalGastosItm))."%\">".number_format($valorTotalGastosItm, 2, ".", ",")."</td>";
				}
				$htmlTotalGastos .= "</tr>";
				$htmlTotalGastos .= "</table>";
			}
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("
			var elemento = '".
				"<tr align=\"left\" id=\"trArancel:%s\" class=\"textoGris_11px %s\">".
					"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
					"<td>%s".
						"<input type=\"checkbox\" id=\"cbxArancel\" name=\"cbxArancel[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td>%s</td>".
					"<td align=\"right\">%s</td>".
					"<td align=\"right\">%s</td>".
					"<td align=\"right\">%s</td>".
					"<td align=\"right\">%s</td>".
					"<td align=\"right\">%s</td>".
					"<td align=\"right\">%s</td>".
					"<td align=\"right\">%s</td>".
				"</tr>';
				
				obj = byId('trArancel:%s');
				if (obj == undefined)
					$('#trItmPieArancel').before(elemento);",
				$indiceArancel, $clase,
					$indiceArancel, $contFila,
					utf8_encode($row['codigo_arancel']),
						$indiceArancel,
					utf8_encode($row['descripcion_arancel']),
					number_format($arrayArancel[$indiceArancel]['porcentaje_grupo'], 2, ".", ","),
					number_format($arrayArancel[$indiceArancel]['cant_items'], 2, ".", ","),
					number_format($arrayArancel[$indiceArancel]['monto_fob'], 2, ".", ","),
					$htmlGastos,
					number_format($arrayArancel[$indiceArancel]['monto_cif'], 2, ".", ","),
					number_format($arrayArancel[$indiceArancel]['peso_neto'], 2, ".", ","),
					number_format($arrayArancel[$indiceArancel]['cant_articulos'], 2, ".", ","),
				
				$indiceArancel));
			
			$spnTotalFOBArancel += $arrayArancel[$indiceArancel]['monto_fob'];
			$spnTotalCIFArancel += $arrayArancel[$indiceArancel]['monto_cif'];
			$spnTotalPesoNetoArancel += $arrayArancel[$indiceArancel]['peso_neto'];
			$spnCantArticulosArancel += $arrayArancel[$indiceArancel]['cant_articulos'];
			$spnCantItemsArancel += $arrayArancel[$indiceArancel]['cant_items'];
		}
		
		$objResponse->assign("spnCantItemsArancel","innerHTML",number_format($spnCantItemsArancel, 2, ".", ","));
		$objResponse->assign("spnTotalFOBArancel","innerHTML",number_format($spnTotalFOBArancel, 2, ".", ","));
		$objResponse->assign("spnTotalGastosArancel","innerHTML",$htmlTotalGastos);
		$objResponse->assign("spnTotalCIFArancel","innerHTML",number_format($spnTotalCIFArancel, 2, ".", ","));
		$objResponse->assign("spnTotalPesoNetoArancel","innerHTML",number_format($spnTotalPesoNetoArancel, 2, ".", ","));
		$objResponse->assign("spnCantArticulosArancel","innerHTML",number_format($spnCantArticulosArancel, 2, ".", ","));
		
		$objResponse->assign("spnFOBArancelMoneda","innerHTML"," (".$abrevMonedaOrigen.")");
		$objResponse->assign("spnGastosArancelMoneda","innerHTML"," (".$abrevMonedaOrigen.")");
		$objResponse->assign("spnCIFArancelMoneda","innerHTML"," (".$abrevMonedaOrigen.")");
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIva)) {
		foreach ($arrayIva as $indiceIva => $valorIva) {
			if ($arrayIva[$indiceIva][2] > 0) {
				$arrayIva[$indiceIva][2] = ($frmTotalDcto['cbxRedondeoIva'.$indiceIva] > 0) ? round(doubleval($arrayIva[$indiceIva][2]), 2) : truncateFloat(doubleval($arrayIva[$indiceIva][2]), 2);
				
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIva:%s\">".
							"<label><table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr align=\"right\">".
								"<td><input type=\"checkbox\" id=\"cbxRedondeoIva%s\" name=\"cbxRedondeoIva%s\" %s value=\"1\"></td>".
								"<td class=\"textoNegrita_9px\">&nbsp;Redondear</td>".
								"<td width=\"%s\">&nbsp;%s:</td></tr></table></label>".
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
						$('#trGastosSinIva').before(elemento);
					
					byId('cbxRedondeoIva%s').onclick = function() {
						setFormatoRafk(this,2);
						xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
					}", 
					$indiceIva, 
						$indiceIva,
							"100%",
								$indiceIva, $indiceIva, (($frmTotalDcto['cbxRedondeoIva'.$indiceIva] > 0) ? "checked=\"checked\"" : ""),
								"100%", utf8_encode($arrayIva[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format($arrayIva[$indiceIva][1], 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIva[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format($arrayIva[$indiceIva][2], 2, ".", ","), 
					
					$indiceIva,
					$indiceIva));
			}
			
			$subTotalIva += round(doubleval($arrayIva[$indiceIva][2]), 2);
		}
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIvaLocal)) {
		foreach ($arrayIvaLocal as $indiceIva => $valorIva) {
			if ($arrayIvaLocal[$indiceIva][2] > 0) {
				$arrayIva[$indiceIva][2] = ($frmTotalDcto['cbxRedondeoIvaLocal'.$indiceIva] > 0) ? round(doubleval($arrayIva[$indiceIva][2]), 2) : truncateFloat(doubleval($arrayIva[$indiceIva][2]), 2);
				
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIvaLocal:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIvaLocal:%s\">".
							"<label><table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr align=\"right\">".
								"<td><input type=\"checkbox\" id=\"cbxRedondeoIvaLocal%s\" name=\"cbxRedondeoIvaLocal%s\" %s value=\"1\"></td>".
								"<td class=\"textoNegrita_9px\">&nbsp;Redondear</td>".
								"<td width=\"%s\">&nbsp;%s:</td></tr></table></label>".
							"<input type=\"hidden\" id=\"hddIdIvaLocal%s\" name=\"hddIdIvaLocal%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIvaLocal%s\" name=\"hddLujoIvaLocal%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIvaLocal\" name=\"cbxIvaLocal[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIvaLocal%s\" name=\"txtBaseImpIvaLocal%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIvaLocal%s\" name=\"txtIvaLocal%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIvaLocal%s\" name=\"txtSubTotalIvaLocal%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIvaLocal:%s');
					if (obj == undefined)
						$('#trRetencionISLR').before(elemento);
					
					byId('cbxRedondeoIvaLocal%s').onclick = function() {
						setFormatoRafk(this,2);
						xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
					}", 
					$indiceIva, 
						$indiceIva,
							"100%",
								$indiceIva, $indiceIva, (($frmTotalDcto['cbxRedondeoIvaLocal'.$indiceIva] > 0) ? "checked=\"checked\"" : ""),
								"100%", utf8_encode($arrayIvaLocal[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format($arrayIvaLocal[$indiceIva][1], 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIvaLocal[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format($arrayIvaLocal[$indiceIva][2], 2, ".", ","), 
					
					$indiceIva,
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
	$txtTotalOrden += round(doubleval($subTotalIva) + doubleval($txtGastosConIvaOrigen) + doubleval($txtGastosSinIvaOrigen), 2);
	
	$objResponse->assign("hddModoCompra","value",$idModoCompra);
	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento", "value", number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign('txtGastosConIva', "value", number_format($txtGastosConIvaOrigen, 2, ".", ","));
	$objResponse->assign('txtGastosSinIva', "value", number_format($txtGastosSinIvaOrigen, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExentoOrigen + $txtGastosSinIvaOrigen), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExoneradoOrigen, 2, ".", ","));
	
	$objResponse->assign("spnTotalOtrosCargos", "innerHTML", number_format($spnTotalOtrosCargos, 2, ".", ","));
	
	$objResponse->assign("txtTotalPesoItem", "value", number_format($txtTotalPesoItem, 2, ".", ","));
	$objResponse->assign("txtTotalGastoItem", "value", number_format($txtTotalGastoItem, 2, ".", ","));
	$objResponse->assign("txtTotalGasto", "value", number_format($txtTotalGasto, 2, ".", ","));
	$objResponse->assign("txtTotalGastoImportacion", "value", number_format($txtTotalGastoImportacion, 2, ".", ","));
	
	// BUSCA LOS DATOS DE LA RETENCION
	$queryRetencionISLR = sprintf("SELECT * FROM te_retenciones WHERE id = %s;",
		valTpDato($frmTotalDcto['lstRetencionISLR'], "int"));
	$rsRetencionISLR = mysql_query($queryRetencionISLR);
	if (!$rsRetencionISLR) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsRetencionISLR = mysql_num_rows($rsRetencionISLR);
	$rowRetencionISLR = mysql_fetch_assoc($rsRetencionISLR);
	
	if ($txtTotalOrden > 0) {
		$objResponse->script("
		byId('trRetencionISLR').style.display = '';
		byId('trBaseImponibleISLR').style.display = '';
		byId('trMontoMayorISLR').style.display = '';");
		
		if ($rowRetencionISLR['porcentaje'] == 0) {
			$objResponse->script("
			byId('trBaseImponibleISLR').style.display = 'none';
			byId('trMontoMayorISLR').style.display = 'none';");
			
			$txtBaseImpISLR = 0;
			$txtTotalRetencionISLR = 0;
		} else {
			$txtBaseImpISLR = str_replace(",","",$frmTotalDcto['txtBaseImpISLR']);
			if ($txtBaseImpISLR == 0) {
				$txtBaseImpISLR = $arrayIva[0][1];
			}
		}
		
		if ($rowRetencionISLR['importe'] == 0 || ($rowRetencionISLR['importe'] > 0 && $txtTotalOrden > $rowRetencionISLR['importe'])) {
			$txtTotalRetencionISLR = $txtBaseImpISLR * ($rowRetencionISLR['porcentaje'] / 100) - $rowRetencionISLR['sustraendo'];
		} else {
			$txtBaseImpISLR = 0;
			$txtTotalRetencionISLR = 0;
			$objResponse->alert("La retención seleccionada no aplica para este registro de compra");
		}
	} else {
		$objResponse->script("
		byId('trRetencionISLR').style.display = 'none';
		byId('trBaseImponibleISLR').style.display = 'none';
		byId('trMontoMayorISLR').style.display = 'none';");
		
		$txtBaseImpISLR = 0;
		$txtTotalRetencionISLR = 0;
	}
	
	$objResponse->assign("txtPorcentajeISLR","value",number_format($rowRetencionISLR['porcentaje'], 2, ".", ","));
	$objResponse->assign("txtMontoMayorISLR","value",number_format($rowRetencionISLR['importe'], 2, ".", ","));
	$objResponse->assign("txtSustraendoISLR","value",number_format($rowRetencionISLR['sustraendo'], 2, ".", ","));
	$objResponse->assign("txtBaseImpISLR","value",number_format($txtBaseImpISLR, 2, ".", ","));
	$objResponse->assign("txtTotalRetencionISLR","value",number_format($txtTotalRetencionISLR, 2, ".", ","));
	
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	if ($rowEmpresa['contribuyente_especial'] == 1 && (count($arrayIva) > 0 || count($arrayIvaLocal) > 0)) {
		$objResponse->loadCommands(cargaLstRetencionImpuesto($frmTotalDcto['lstRetencionImpuesto']));
		$objResponse->script("
		byId('trRetencionIva').style.display = '';");
	} else {
		$objResponse->loadCommands(cargaLstRetencionImpuesto(0));
		$objResponse->script("
		byId('trRetencionIva').style.display = 'none';");
	}
	
	if (!(count($arrayObj) > 0)) {
		$objResponse->assign("txtIdProv","value","");
		$objResponse->assign("txtNombreProv","value","");
		$objResponse->assign("txtRifProv","value","");
		$objResponse->assign("txtDireccionProv","innerHTML","");
		$objResponse->assign("txtTelefonoProv","value","");
		
		//$objResponse->assign("txtIdEmpresa","value","");
		//$objResponse->assign("txtEmpresa","value","");
	}
	
	return $objResponse;
}

function cargaLst($idLstOrigen, $adjLst, $padreId = "", $selId = ""){
	$objResponse = new xajaxResponse();
	
	$arraySelec = array("lstPadre","lstAlmacen","lstCalle","lstEstante","lstTramo","lstCasilla");
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	// SI EL OBJETO NO ES EL ULTIMO ASIGNA LA FUNCION PARA CARGAR EL SIGUIENTE OBJETO
	if (($posList+1) != count($arraySelec)-1)
		$onChange = "onchange=\"xajax_cargaLst('".$arraySelec[$posList+1]."', '".$adjLst."', this.value);\"";
	else if (($posList+1) == count($arraySelec)-1)
		$onChange = "onchange=\"xajax_buscarDisponibilidadUbicacion(xajax.getFormValues('frmAlmacen'));\"";
	
	$html = "<select id=\"".$arraySelec[$posList+1].$adjLst."\" name=\"".$arraySelec[$posList+1].$adjLst."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1') {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\" class=\"inputHabilitado\"style=\"width:99%\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html);
			}
		}
		
		return $objResponse;
	} else {
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 :
				$query = sprintf("SELECT
					almacen.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
						INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
						INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
					WHERE calle.id_almacen = almacen.id_almacen
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_almacenes almacen
				WHERE almacen.id_empresa = %s
					AND almacen.estatus = 1
					AND almacen.estatus_almacen_compra = 1
				ORDER BY almacen.descripcion;",
					valTpDato($padreId, "int"));
				$campoId = "id_almacen";
				$campoDesc = "descripcion"; break;
			case 1 :
				$query = sprintf("SELECT
					calle.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
						INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
					WHERE estante.id_calle = calle.id_calle
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_calles calle
				WHERE calle.id_almacen = %s
					AND calle.estatus = 1
				ORDER BY calle.descripcion_calle;",
					valTpDato($padreId, "int"));
				$campoId = "id_calle";
				$campoDesc = "descripcion_calle"; break;
			case 2 :
				$query = sprintf("SELECT
					estante.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
					WHERE tramo.id_estante = estante.id_estante
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_estantes estante
				WHERE estante.id_calle = %s
					AND estante.estatus = 1
				ORDER BY estante.descripcion_estante;",
					valTpDato($padreId, "int"));
				$campoId = "id_estante";
				$campoDesc = "descripcion_estante"; break;
			case 3 :
				$query = sprintf("SELECT
					tramo.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
					WHERE casilla.id_tramo = tramo.id_tramo
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_tramos tramo
				WHERE tramo.id_estante = %s
					AND tramo.estatus = 1
				ORDER BY tramo.descripcion_tramo;",
					valTpDato($padreId, "int"));
				$campoId = "id_tramo";
				$campoDesc = "descripcion_tramo"; break;
			case 4 :
				$query = sprintf("SELECT
					casilla.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = casilla.id_casilla
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_casillas casilla
				WHERE casilla.id_tramo = %s
					AND casilla.estatus = 1
				ORDER BY casilla.descripcion_casilla;",
					valTpDato($padreId, "int"));
				$campoId = "id_casilla";
				$campoDesc = "descripcion_casilla"; break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
			$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
			$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
			
			$html .= "<option value=\"".$row[$campoId]."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row[$campoDesc].$ocupada)."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->assign("td".$arraySelec[$posList+1].$adjLst, 'innerHTML', $html);
	
	$objResponse->assign("tdMsj","innerHTML","");
	
	return $objResponse;
}

function cargaLstArancelGrupo($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"validarAsignarADV();\"";
	
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstArancelGrupo\" name=\"lstArancelGrupo\" ".$class." ".$onChange." style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_arancel_grupo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_arancel_grupo']."\">".utf8_encode($row['porcentaje_grupo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstArancelGrupo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstArancelGrupoBuscar($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputCompleto\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"buscarEnColumna(this.value, 'porcentaje_grupo');\"";
	
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"min-width:60px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId != "" && doubleval($selId) == doubleval($row['porcentaje_grupo'])) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['porcentaje_grupo']."\">".number_format($row['porcentaje_grupo'], 2, ".", ",")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstArancelGrupoBuscar","innerHTML",$html);
	
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
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstGrupoItem($nombreObjeto, $grupo, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$onChange = ($grupo == "ViaEnvio") ? "onchange=\"seleccionarEnvio(this.value)\"" : "";
	
	$query = sprintf("SELECT grupo_items.*
	FROM grupos grupo
		INNER JOIN grupositems grupo_items ON (grupo.idGrupo = grupo_items.idGrupo)
	WHERE grupo.grupo = %s
	ORDER BY item",
		valTpDato($grupo, "text"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstIva($nombreObjeto, $selId = "", $selVal = "", $bloquearObj = false, $alturaObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	$style = ($alturaObj == true) ? "style=\"height:200px; width:99%\"" : " style=\"width:99%\"";
	
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,8,3) AND iva.estado = 1 ORDER BY iva;");
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

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstTasaCambio(this.value); xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\" style=\"width:150px\">";
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

function cargaLstRetencionImpuesto($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (5) AND iva.estado = 1 ORDER BY iva");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstRetencionImpuesto\" name=\"lstRetencionImpuesto\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= "<option ".(($selId == 0 && strlen($selId) > 0) ? "selected=\"selected\"" : "")." value=\"0\">".("Sin Retención")."</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['iva'] || $totalRows == 0) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['iva']."\">".utf8_encode($row['observacion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstRetencionImpuesto","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstRetencionISLR($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_retenciones WHERE activo = 1;";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstRetencionISLR\" name=\"lstRetencionISLR\" class=\"inputHabilitado\" onchange=\"xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\" style=\"width:99%\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id'] || $totalRows == 0) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id']."\">".utf8_encode($row['descripcion']." (".$row['porcentaje'])."%)</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstRetencionISLR","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTasaCambio($idMoneda, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
	FROM pg_tasa_cambio tasa_cambio
		INNER JOIN pg_monedas moneda_local ON (tasa_cambio.id_moneda_nacional = moneda_local.idmoneda)
	WHERE tasa_cambio.id_moneda_extranjera = %s;",
		valTpDato($idMoneda, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstTasaCambio\" name=\"lstTasaCambio\" class=\"inputHabilitado\" onchange=\"xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\" style=\"width:150px\">";
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

function cargaLstUbicacion($tpLst, $adjLst, $idEmpresa, $idAlmacen, $idCalle, $idEstante, $idTramo, $idCasilla) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		almacen.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
			INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
			INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
		WHERE calle.id_almacen = almacen.id_almacen
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_almacenes almacen
	WHERE almacen.id_empresa = %s
		AND almacen.estatus = 1
		AND almacen.estatus_almacen_compra = 1
	ORDER BY descripcion;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstAlmacen".$adjLst."\" name=\"lstAlmacen".$adjLst."\" onchange=\"xajax_cargaLst('lstAlmacen', '".$adjLst."', this.value);\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($idAlmacen == $row['id_almacen']) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\" class=\"".$classUbic."\">".utf8_encode($row['descripcion'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAlmacen".$adjLst,"innerHTML",$html);
	
	
	$query = sprintf("SELECT
		calle.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
			INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
		WHERE estante.id_calle = calle.id_calle
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_calles calle
	WHERE calle.id_almacen = %s
		AND calle.estatus = 1
	ORDER BY calle.descripcion_calle;",
		valTpDato($idAlmacen, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstCalle".$adjLst."\" name=\"lstCalle".$adjLst."\" onchange=\"xajax_cargaLst('lstCalle', '".$adjLst."', this.value);\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($idCalle == $row['id_calle']) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option value=\"".$row['id_calle']."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row['descripcion_calle'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCalle".$adjLst,"innerHTML",$html);
	
	
	$query = sprintf("SELECT
		estante.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
		WHERE tramo.id_estante = estante.id_estante
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_estantes estante
	WHERE estante.id_calle = %s
		AND estante.estatus = 1
	ORDER BY estante.descripcion_estante;",
		valTpDato($idCalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstEstante".$adjLst."\" name=\"lstEstante".$adjLst."\" onchange=\"xajax_cargaLst('lstEstante', '".$adjLst."', this.value);\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($idEstante == $row['id_estante']) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option value=\"".$row['id_estante']."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row['descripcion_estante'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstante".$adjLst,"innerHTML",$html);
	
	
	$query = sprintf("SELECT
		tramo.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
		WHERE casilla.id_tramo = tramo.id_tramo
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_tramos tramo
	WHERE tramo.id_estante = %s
		AND tramo.estatus = 1
	ORDER BY tramo.descripcion_tramo;",
		valTpDato($idEstante, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstTramo".$adjLst."\" name=\"lstTramo".$adjLst."\" onchange=\"xajax_cargaLst('lstTramo', '".$adjLst."', this.value);\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($idTramo == $row['id_tramo']) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option value=\"".$row['id_tramo']."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row['descripcion_tramo'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTramo".$adjLst,"innerHTML",$html);
	
	
	$query = sprintf("SELECT
		casilla.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_casillas casilla
	WHERE casilla.id_tramo = %s
		AND casilla.estatus = 1
	ORDER BY casilla.descripcion_casilla;",
		valTpDato($idTramo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstCasilla".$adjLst."\" name=\"lstCasilla".$adjLst."\" onchange=\"xajax_buscarDisponibilidadUbicacion(xajax.getFormValues('frmAlmacen'));\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$selected = ($idCasilla == $row['id_casilla']) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option value=\"".$row['id_casilla']."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row['descripcion_casilla'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCasilla".$adjLst,"innerHTML",$html);
	
	return $objResponse;
}

function cargarDcto($idFacturaCompra, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	$contFilaGasto = $arrayObjGasto[count($arrayObjGasto)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjOtroCargo = $frmTotalDcto['cbxOtroCargo'];
	$contFilaOtroCargo = $arrayObjOtroCargo[count($arrayObjOtroCargo)-1];
	
	// BUSCA LOS DATOS DE LA FACTURA DE COMPRA
	$queryFactura = sprintf("SELECT cxp_fact.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_factura_compra cxp_fact
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = ($totalRowsFactura > 0) ? $rowFactura['id_empresa'] : $_SESSION['idEmpresaUsuarioSysGts'];
	
	$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico inv_fis
	WHERE inv_fis.id_empresa = %s
		AND inv_fis.estatus = 0;",
		valTpDato($idEmpresa , "int"));
	$rsInvFis = mysql_query($queryInvFis);
	if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsInvFis = mysql_num_rows($rsInvFis);
	
	if ($totalRowsInvFis > 0) {
		$objResponse->alert("Usted no puede Registrar Compras, debido a que está en Proceso un Inventario Físico");
		
		return $objResponse->script("
		byId('btnCancelar').onclick = function () { window.open('iv_preregistro_compra_list.php','_self'); }
		byId('btnCancelar').click();");
	}
		
	if ($idFacturaCompra > 0) {
		// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
		$queryFacturaDetalle = sprintf("SELECT * FROM iv_factura_compra_detalle cxp_fact_det WHERE id_factura_compra = %s
		ORDER BY id_factura_compra_detalle ASC;",
			valTpDato($idFacturaCompra, "int"));
		$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
		if (!$rsFacturaDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
			// BUSCA LOS DATOS DE LA UBICACION
			$queryUbic = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
				valTpDato($rowFacturaDetalle['id_casilla'], "int"));
			$rsUbic = mysql_query($queryUbic);
			if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsUbic = mysql_num_rows($rsUbic);
			$rowUbic = mysql_fetch_assoc($rsUbic);
			
			$almacen = $rowUbic['descripcion_almacen'];
			$ubicacion = $rowUbic['ubicacion'];
			$idClienteArt = $rowFacturaDetalle['id_cliente'];
			
			$Result1 = insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, "", $rowFacturaDetalle['id_factura_compra_detalle'], $idMonedaLocal, $idMonedaOrigen, "", "", "", "", "", $almacen, $ubicacion, $idClienteArt);
			$arrayObjUbicacion = $Result1[3];
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$frmListaArticulo['hddIdPedidoDetItm'.$contFila] = $idPedidoDetalle;
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		$objResponse->loadCommands(asignarProveedor($rowFactura['id_proveedor'], "Prov", "false"));
		
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($idEmpresa));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowFactura['nombre_empresa']));
		
		$idMonedaLocal = $rowFactura['id_moneda'];
		$idMonedaOrigen = ($rowFactura['id_moneda_tasa_cambio'] > 0) ? $rowFactura['id_moneda_tasa_cambio'] : $rowFactura['id_moneda'];
		
		// VERIFICA SI LA FACTURA ES DE IMPORTACION
		$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
		
		if (($idModoCompra == 1 && !xvalidaAcceso($objResponse, "iv_registro_compra_nacional", "insertar", true, NULL, NULL, NULL, true, "iv_registro_compra_list.php"))
		|| ($idModoCompra == 2 && !xvalidaAcceso($objResponse,"iv_registro_compra_importacion","insertar", true, NULL, NULL, NULL, true, "iv_registro_compra_list.php"))) { return $objResponse; }
		
		$txtTasaCambio = ($rowFactura['monto_tasa_cambio'] >= 0) ? $rowFactura['monto_tasa_cambio'] : 0;
		$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
		$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
		$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);
		$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $rowFactura['id_tasa_cambio']));
		
		$objResponse->assign("txtDescuento","value",number_format($rowFactura['porcentaje_descuento'], 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowFactura['subtotal_descuento'], 2, ".", ","));
		$objResponse->assign("txtMontoTotalFactura","value",number_format($rowFactura['saldo_factura'], 2, ".", ","));
		$objResponse->loadCommands(cargaLstRetencionISLR($rowFactura['id_retencion_islr']));
		$objResponse->assign("txtBaseImpISLR","value",number_format($rowFactura['base_imponible_islr'], 2, ".", ","));
		
		// VERIFICA SI LA FACTURA ES DE IMPORTACION
		$idModoCompra = $rowFactura['id_modo_compra'];
		
		$objResponse->assign("txtIdFactura","value",$rowFactura['id_factura_compra']);
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat,strtotime($rowFactura['fecha_origen'])));
		$objResponse->assign("txtNumeroFacturaProveedor","value",$rowFactura['numero_factura_proveedor']);
		$objResponse->assign("txtNumeroControl","value",$rowFactura['numero_control_factura']);
		$objResponse->assign("txtFechaProveedor","value",date(spanDateFormat,strtotime($rowFactura['fecha_factura_proveedor'])));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento","0","1","","1",$rowFactura['id_clave_movimiento']));
		$objResponse->loadCommands(cargaLstGrupoItem('lstViaEnvio','ViaEnvio',$rowFactura['id_via_envio']));
		$objResponse->call("seleccionarEnvio",$rowFactura['id_via_envio']);
		//$objResponse->call("selectedOption","lstNacionalizar",$rowFactura['nacionalizada']);
		$objResponse->call("selectedOption","lstGastoItem",$rowFactura['gasto_manual_item']);
		$objResponse->assign("txtObservacionFactura","innerHTML",$rowFactura['observacion_factura']);
		
		// BUSCA SI LA FACTURA ESTA INCLUIDA EN UN EXPEDIENTE
		$query = sprintf("SELECT
			expediente.id_expediente,
			expediente.numero_expediente,
			expediente.numero_embarque
		FROM iv_expediente_detalle_factura expediente_det_fact
			INNER JOIN iv_expediente expediente ON (expediente_det_fact.id_expediente = expediente.id_expediente)
		WHERE expediente_det_fact.id_factura_compra = %s;",
			valTpDato($idFacturaCompra, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdExpediente","value",$row['id_expediente']);
		$objResponse->assign("txtExpediente","value",$row['numero_expediente']);
		$objResponse->assign("txtNumeroEmbarque","value",$row['numero_embarque']);
		
		// BUSCA LOS GASTOS DE LA FACTURA
		$queryFacturaDetalle = sprintf("SELECT * FROM iv_factura_compra_gasto cxp_fact_gasto
		WHERE id_factura_compra = %s
			AND id_modo_gasto IN (1,3)
		ORDER BY id_factura_compra_gasto ASC;",
			valTpDato($idFacturaCompra, "int"));
		$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
		if (!$rsFacturaDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
			switch ($rowFacturaDetalle['id_modo_gasto']) {
				case 1 : $Result1 = insertarItemGasto($contFilaGasto, "", $rowFacturaDetalle['id_factura_compra_gasto']); break;
				case 3 : $Result1 = insertarItemGastoImportacion($contFilaGasto, "", $rowFacturaDetalle['id_factura_compra_gasto']); break;
			}
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFilaGasto = $Result1[2];
				$frmListaArticulo['hddIdFacturaCompraGasto'.$contFilaGasto] = $rowFacturaDetalle['id_factura_compra_gasto'];
				$objResponse->script($Result1[1]);
				$arrayObjGasto[] = $contFilaGasto;
			}
		}
		
		// BUSCA LOS CARGOS DE LA FACTURA
		$query = sprintf("SELECT
			cxp_fact_gasto.id_factura_compra_gasto,
			cxp_fact_gasto.id_gasto
		FROM iv_factura_compra_gasto cxp_fact_gasto
			INNER JOIN pg_gastos gasto ON (cxp_fact_gasto.id_gasto = gasto.id_gasto)
		WHERE cxp_fact_gasto.id_factura_compra = %s
			AND cxp_fact_gasto.id_modo_gasto IN (2)
		ORDER BY gasto.nombre ASC;",
			valTpDato($idFacturaCompra, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemOtroCargo($contFilaOtroCargo, $row['id_gasto'], $row['id_factura_compra_gasto']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFilaOtroCargo = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObjOtroCargo[] = $contFilaOtroCargo;
			}
		}
		
		$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));"));
		$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento","0","1","","1"));
		$objResponse->call("seleccionarEnvio","");
		
		$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	}
		
	return $objResponse;
}

function cargarDetalleCosto($hddNumeroArt, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = (str_replace(",", "", $frmDcto['txtTasaCambio']) > 0) ? str_replace(",", "", $frmDcto['txtTasaCambio']) : 1;
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
			$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
			$txtTotalItm = $txtCantRecibItm * $txtCostoItm;
			
			$txtSubTotal += $txtTotalItm;
			$subTotalDescuentoItm += $txtCantRecibItm * $hddMontoDescuentoItm;
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
					
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdArticuloItm'.$valor] > 0 && $valor == $hddNumeroArt) {
				$txtGastosItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]);
				$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
				$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
				
				// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
				$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal;
				
				$txtTotalItm = $txtTotalItm - $totalDescuentoItm;
				
				// CALCULA LOS DATOS DE IMPORTACION
				$precioTotalFOB = $txtTotalItm;
				$totalCIF = $precioTotalFOB + $txtGastosItm;
				$totalPrecioCIF = $totalCIF * $txtTasaCambio;
				$tarifaAdValorem = ($totalPrecioCIF * str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor])) / 100;
				$gastosImportNacItm = str_replace(",", "", $frmListaArticulo['hddGastosImportNacItm'.$valor]);
				$otrosCargos = str_replace(",", "", $frmListaArticulo['hddGastosImportItm'.$valor]);
				$precioTotal = $totalPrecioCIF + $tarifaAdValorem + $gastosImportNacItm + $otrosCargos;
				$diferenciaCambiariaTotal = $totalCIF * str_replace(",", "", $frmTotalDcto['txtDiferenciaCambiaria']);
				$precioUnitario = $precioTotal / str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
				$diferenciaCambiariaUnit = ($totalCIF * str_replace(",", "", $frmTotalDcto['txtDiferenciaCambiaria'])) / str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
				$totalPrecioUnitario = $precioUnitario + $diferenciaCambiariaUnit;
				
				if ($totalPrecioUnitario > 0) {
					for ($cont = 1; $cont <= 3; $cont++) {
						$objResponse->assign("tdMoneda".$cont.":".$valor,"innerHTML",$abrevMonedaOrigen);
					}
					
					for ($cont = 4; $cont <= 12; $cont++) {
						$objResponse->assign("tdMoneda".$cont.":".$valor,"innerHTML",$abrevMonedaLocal);
					}
					
					$objResponse->assign("tdPrecioTotalFOB".$valor,"innerHTML",number_format($precioTotalFOB, 2, ".", ","));
					$objResponse->assign("tdTotalGastos".$valor,"innerHTML",number_format($txtGastosItm, 2, ".", ","));
					$objResponse->assign("tdTotalCIF".$valor,"innerHTML",number_format($totalCIF, 2, ".", ","));
					$objResponse->assign("tdTotalPrecioCIF".$valor,"innerHTML",number_format($totalPrecioCIF, 2, ".", ","));
					$objResponse->assign("tdTarifaAdValorem".$valor,"innerHTML",number_format($tarifaAdValorem, 2, ".", ","));
					$objResponse->assign("tdTotalGastosImportNacional".$valor,"innerHTML",number_format($gastosImportNacItm, 2, ".", ","));
					$objResponse->assign("tdOtrosCargos".$valor,"innerHTML",number_format($otrosCargos, 2, ".", ","));
					$objResponse->assign("tdPrecioTotal".$valor,"innerHTML",number_format($precioTotal, 2, ".", ","));
					$objResponse->assign("tdDiferenciaCambiariaTotal".$valor,"innerHTML",number_format($diferenciaCambiariaTotal, 2, ".", ","));
					$objResponse->assign("tdPrecioUnitario".$valor,"innerHTML",number_format($precioUnitario, 2, ".", ","));
					$objResponse->assign("tdDiferenciaCambiariaUnit".$valor,"innerHTML",number_format($diferenciaCambiariaUnit, 2, ".", ","));
					$objResponse->assign("tdTotalPrecioUnitario".$valor,"innerHTML",number_format($totalPrecioUnitario, 2, ".", ","));
					
					$objResponse->script("byId('trMsjDetallesCosto".$valor."').style.display = 'none'");
					$objResponse->script("byId('tbodyDetallesCosto".$valor."').style.display = ''");
				} else {
					$objResponse->script("byId('trMsjDetallesCosto".$valor."').style.display = ''");
					$objResponse->script("byId('tbodyDetallesCosto".$valor."').style.display = 'none'");
				}
			}
		}
	}
	
	return $objResponse;
}

function cargarFacturaCargo($hddItmGasto, $frmTotalDcto, $frmFacturaGasto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmBuscarRegistroCompra'].reset();
	document.forms['frmListaRegistroCompra'].reset();
	byId('hddItmGastoListaRegistroCompra').value = '';
	
	byId('btnBuscarRegistroCompra').click();
	
	document.forms['frmFacturaGasto'].reset();
	byId('hddItmGasto').value = '';
	
	byId('lstCondicionGasto').className = 'inputHabilitado';");
	
	$hddIdGastoCargo = $frmTotalDcto['hddIdGastoCargo'.$hddItmGasto];
	$hddCondicionGastoCargo = $frmTotalDcto['hddCondicionGastoCargo'.$hddItmGasto];
	
	// BUSCA LOS DATOS DEL GASTO DE IMPORTACION
	$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
		valTpDato($hddIdGastoCargo, "int"));
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGastos = mysql_fetch_assoc($rsGasto);
	
	$lstAsociaDocumento = $rowGastos['asocia_documento'];
	
	switch ($lstAsociaDocumento) {
		case 0 : // 0 = No
			$objResponse->script("
			byId('lstCondicionGasto').onchange = function () {
				seleccionarCondicionGasto(this.value);
				selectedOption(this.id,".(1)."');
			}"); break;
		case 1 : // 1 = Si
			$objResponse->script(sprintf("
			byId('lstCondicionGasto').onchange = function () {
				seleccionarCondicionGasto(this.value);
			}")); break;
	}
	
	$objResponse->script("
	byId('lstAsociaDocumento').onchange = function () {
		selectedOption(this.id,'".$lstAsociaDocumento."');
	}");
	
	$objResponse->assign("hddItmGastoListaRegistroCompra","value",$hddItmGasto);
	$objResponse->assign("hddItmGasto","value",$hddItmGasto);
	$objResponse->call("selectedOption","lstCondicionGasto",$hddCondicionGastoCargo);
	$objResponse->call("selectedOption","lstAsociaDocumento",$lstAsociaDocumento);
	
	if ($hddCondicionGastoCargo == 2 || $lstAsociaDocumento == 0) { // 2 = Estimado || 0 = No
		$objResponse->assign("txtSubTotalFacturaGasto","value",$frmTotalDcto['hddSubTotalFacturaGastoCargo'.$hddItmGasto]);
	}
	
	$objResponse->script("byId('lstCondicionGasto').onchange();");
	
	return $objResponse;
}

function editarArticulo($frmArticulo) {
	$objResponse = new xajaxResponse();
	
	$contFila = $frmArticulo['hddNumeroArt'];
	$hddIdIvaItm = implode(",",$frmArticulo['lstIvaArt']);
	
	if ($frmArticulo['hddIdArticuloSust'] > 0) {
		$idArticulo = $frmArticulo['hddIdArticuloSust'];
		$objResponse->assign("hddIdArticuloSustItm".$contFila,"value",$frmArticulo['hddIdArticuloSust']);
	} else {
		$idArticulo = $frmArticulo['hddIdArticulo'];
		$objResponse->assign("hddIdArticuloSustItm".$contFila,"value","");
	}
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (1,8,3)
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
		"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	$objResponse->assign("tdCodigoArticuloItm".$contFila,"innerHTML",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
	$objResponse->assign("divDescripcionArticuloItm".$contFila,"innerHTML",utf8_encode($rowArticulo['descripcion']));
	
	$objResponse->assign("txtCantRecibItm".$contFila,"value",number_format(str_replace(",", "", $frmArticulo['txtCantidadRecibArt']), 2, ".", ","));
	$objResponse->assign("tdCantPendItm".$contFila,"innerHTML",number_format((str_replace(",", "", $frmArticulo['txtCantidadArt']) - str_replace(",", "", $frmArticulo['txtCantidadRecibArt'])), 2, ".", ","));
	$objResponse->assign("txtCostoItm".$contFila,"value",number_format(str_replace(",", "", $frmArticulo['txtCostoArt']), 2, ".", ","));
	$objResponse->assign("txtTotalItm".$contFila,"value",number_format((str_replace(",", "", $frmArticulo['txtCantidadRecibArt']) * str_replace(",", "", $frmArticulo['txtCostoArt'])), 2, ".", ","));
	$objResponse->assign("hddTipoDescuentoItm".$contFila,"value",$frmArticulo['hddTipoDescuento']);
	$objResponse->assign("hddPorcDescuentoItm".$contFila,"value",number_format(str_replace(",", "", $frmArticulo['txtPorcDescuentoArt']), 2, ".", ","));
	$objResponse->assign("hddMontoDescuentoItm".$contFila,"value",number_format(str_replace(",", "", $frmArticulo['txtMontoDescuentoArt']), 2, ".", ","));
	$objResponse->assign("hddTotalDescuentoItm".$contFila,"value",number_format(str_replace(",", "", $frmArticulo['txtCantidadRecibArt']) * str_replace(",", "", $frmArticulo['txtMontoDescuentoArt']), 2, ".", ","));
	
	$objResponse->assign("divIvaItm".$contFila,"innerHTML",$ivaUnidad);
	$objResponse->assign("hddTipoItm".$contFila,"value",$frmArticulo['rbtTipoArt']);
	$objResponse->assign("hddIdClienteItm".$contFila,"value",$frmArticulo['txtIdClienteArt']);
	
	$objResponse->script("
	byId('btnCancelarArticulo').click();");
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function eliminarArticulo($frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function eliminarGasto($frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmTotalDcto['cbxItmGasto'])) {
		foreach ($frmTotalDcto['cbxItmGasto'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmGasto:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarGasto(xajax.getFormValues('frmTotalDcto'));");
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function eliminarOtroCargo($frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmTotalDcto['cbxItmOtroCargo'])) {
		foreach ($frmTotalDcto['cbxItmOtroCargo'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmOtroCargo:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarOtroCargo(xajax.getFormValues('frmTotalDcto'));");
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function exportarRegistroCompra($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmDcto['txtIdFactura']);
	
	$objResponse->script("window.open('reportes/iv_registro_compra_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formAlmacen($hddNumeroArt, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("tdMsj","innerHTML","");
	$objResponse->script("document.forms['frmAlmacen'].reset();");
	
	$idDetallePedido = $frmListaArticulo['hddIdPedidoDetItm'.$hddNumeroArt];
	
	if ($frmListaArticulo['hddIdArticuloSustItm'.$hddNumeroArt] > 0) {
		$idArticulo = $frmListaArticulo['hddIdArticuloSustItm'.$hddNumeroArt];
	} else {
		$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$hddNumeroArt];
	}
	$idCasilla = $frmListaArticulo['hddIdCasillaItm'.$hddNumeroArt];
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$queryDctoDet = sprintf("SELECT *
	FROM iv_pedido_compra_detalle ped_comp_det
		INNER JOIN iv_pedido_compra ped_comp ON (ped_comp_det.id_pedido_compra = ped_comp.id_pedido_compra)
	WHERE id_pedido_compra_detalle = %s;",
		valTpDato($idDetallePedido, "int"));
	$rsDctoDet = mysql_query($queryDctoDet);
	if (!$rsDctoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDctoDet = mysql_fetch_assoc($rsDctoDet);
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DEL ALMACEN EN EL CUAL SE GUARDARA LA EXISTENCIA
	$queryCasilla = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
		valTpDato($idCasilla, "int"));
	$rsCasilla = mysql_query($queryCasilla);
	if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCasilla = mysql_num_rows($rsCasilla);
	$rowCasilla = mysql_fetch_assoc($rsCasilla);
	
	$idEmpresa = ($rowCasilla['id_casilla'] > 0) ? $rowCasilla['id_empresa'] : $rowDctoDet['id_empresa'];
	
	$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"xajax_cargaLst('lstPadre', 'Act', this.value);\""));
	
	if ($rowCasilla['id_casilla'] > 0) {
		$objResponse->loadCommands(cargaLstUbicacion("almacenes", "Act",
			$idEmpresa,
			$rowCasilla['id_almacen'],
			$rowCasilla['id_calle'],
			$rowCasilla['id_estante'],
			$rowCasilla['id_tramo'],
			$rowCasilla['id_casilla']));
	} else {
		$objResponse->loadCommands(cargaLst("lstPadre", "Act", $idEmpresa));
	}
	
	$objResponse->assign("hddNumeroArt2","value",$hddNumeroArt);
	$objResponse->assign("txtCodigoArticulo","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("txtArticulo","value",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtCantidadDisponible","value",number_format($frmListaArticulo['txtCantRecibItm'.$hddNumeroArt], 2, ".", ","));
	
	if ($$totalRowsCasilla > 0) {
		$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic
		WHERE id_articulo = %s
			AND id_casilla = %s
			AND id_empresa = %s;",
			valTpDato($idArticulo, "int"),
			valTpDato($rowCasilla['id_casilla'], "int"),
			valTpDato($idEmpresa, "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArticulo = mysql_num_rows($rsArticulo);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($rowArticulo['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $rowArticulo['foto'];
		
		$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
		$html .= "<tr>";
			$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
			$html .= "<td align=\"center\">"."Ubicación Ocupada"."</td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$html .= "<table border=\"1\" class=\"tabla divMsjInfo2\" cellpadding=\"2\" width=\"100%\">";
		$html .= "<tr>";
			$html .= "<td>"."<img src=\"".$imgFoto."\" height=\"100\"/>"."</td>";
			$html .= "<td valign=\"top\" width=\"100%\">";
				$html .= "<table width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"20%\">"."Código:"."</td>";
					$html .= "<td width=\"80%\">".elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";")."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">"."Descripción:"."</td>";
					$html .= "<td>".utf8_encode($rowArticulo['descripcion'])."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">"."Unid. Disponible:"."</td>";
					$html .= "<td>".$rowArticulo['cantidad_disponible_logica']."</td>";
				$html .= "</tr>";
				$html .= "</table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$objResponse->assign("tdMsj","innerHTML",$html);
	}
	
	$queryArtEmpUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion 
	WHERE id_articulo = %s
		AND estatus_articulo_almacen = 1
		AND estatus_almacen_compra = 1
		AND id_casilla IS NOT NULL
		AND id_empresa = %s
	ORDER BY CONCAT_WS(' ', descripcion_almacen, ubicacion);",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArtEmpUbic = mysql_query($queryArtEmpUbic);
	if (!$rsArtEmpUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtEmpUbic = mysql_num_rows($rsArtEmpUbic);
	$html = "";
	if ($totalRowsArtEmpUbic > 1) {
		while ($rowArtEmpUbic = mysql_fetch_assoc($rsArtEmpUbic)) {
			$imgPredetAlmacen = ($rowArtEmpUbic['casilla_predeterminada_compra'] == 1 && $rowArtEmpUbic['id_casilla'] > 0) ? "<img src=\"../img/iconos/aprob_control_calidad.png\" title=\"Ubicación Predeterminada para Compra\"/>" : "";
			
			$html .= sprintf("<div class=\"divMsjAlerta puntero\" onclick=\"xajax_cargaLstUbicacion('%s','%s','%s','%s','%s','%s','%s','%s');\" style=\"float:left; margin:1px; padding:2px\"><table><tr align=\"center\"><td width=\"10\">%s</td><td>%s<br>%s</td><td width=\"10\"></td></tr></table></div>",
				"almacenes",
				"Act",
				$idEmpresa,
				$rowArtEmpUbic['id_almacen'],
				$rowArtEmpUbic['id_calle'],
				$rowArtEmpUbic['id_estante'],
				$rowArtEmpUbic['id_tramo'],
				$rowArtEmpUbic['id_casilla'],
				$imgPredetAlmacen,
				$rowArtEmpUbic['descripcion_almacen'],
				utf8_encode(str_replace("-[]", "", $rowArtEmpUbic['ubicacion'])));
		}
	}
	$objResponse->assign("tdOtrasUbic","innerHTML",$html);
	
	return $objResponse;
}

function formArticuloImpuesto() {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(cargaLstIva("lstIvaCbx", "", "", false, true));
	
	return $objResponse;
}

function formArticuloMultiple($idPedidoDetalle) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	for (cont = 1; cont <= byId('hddCantItmArticuloMultiple').value; cont++) {
		fila = byId('trItmArtMult:' + cont);
		padre = fila.parentNode;
		padre.removeChild(fila);
	}");
	
	$objResponse->script("
	document.forms['frmArticuloMultiple'].reset();
	byId('hddCantItmArticuloMultiple').value = '';");
			
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle WHERE id_pedido_compra_detalle = %s;",
		valTpDato($idPedidoDetalle, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	
	$idArticulo = $rowPedidoDet['id_articulo'];
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$objResponse->assign("hddIdPedidoCompraDetalle","value",$rowPedidoDet['id_pedido_compra_detalle']);
	$objResponse->assign("txtCodigoArtMultiple","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
	$objResponse->assign("txtDescripcionArtMultiple","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtCantidadArtMultiple","value",$rowPedidoDet['pendiente']);
	$objResponse->assign("txtUnidadArtMultiple","value",$rowArticulo['unidad']);
	
	$objResponse->script(sprintf("byId('aAgregarArtMult').onclick = function() { insertarFilaArticuloMultiple('%s'); }",
		$rowArticulo['decimales']));
	
	return $objResponse;
}

function formDatosCliente($idCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	WHERE id = %s
		AND id_empresa = %s
		AND status = 'Activo';",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	
	return $objResponse;
}

function formImportar() {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmImportarArchivo'].reset();
	byId('hddUrlArchivo').value = '';
	
	byId('fleUrlArchivo').className = 'inputHabilitado';");
	
	$objResponse->script("
	byId('fleUrlArchivo').focus();
	byId('fleUrlArchivo').select();");
	
	return $objResponse;
}

function formListadoArticulosPedido() {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmBuscar'].reset();");
	
	$objResponse->assign("divListaPedidoCompra","innerHTML","");
	$objResponse->assign("divArticulosPedido","innerHTML","");
	
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
	byId('txtCriterio').focus();
	byId('txtCriterio').select();");
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_preregistro_compra_list","insertar")) { return $objResponse; }
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaLocal = $frmTotalDcto['cbxIvaLocal'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idFacturaCompra = $frmDcto['txtIdFactura'];
	$txtSubTotal = str_replace(",", "", $frmTotalDcto['txtSubTotal']);
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$txtDescuento = $txtSubTotalDescuento * 100 / $txtSubTotal;
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = str_replace(",", "", $frmDcto['txtTasaCambio']);
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	$incluirIvaMonedaOrigen = $rowMonedaOrigen['incluir_impuestos'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig17 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 17 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig17 = mysql_query($queryConfig17);
	if (!$rsConfig17) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig17 = mysql_num_rows($rsConfig17);
	$rowConfig17 = mysql_fetch_assoc($rsConfig17);
	
	$valor = explode("|",$rowConfig17['valor']);
	
	$txtFechaRegistroCompra = date(spanDateFormat);
	$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
	if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
		if ((date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat))))
			&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
			if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
			|| date("m",strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
				$txtFechaRegistroCompra = $txtFechaProveedor;
			} else {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
			}
		} else if (!(date("Y",strtotime($txtFechaProveedor)) == date("Y",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat))))
			&& date("m",strtotime($txtFechaProveedor)) == date("m",strtotime("-".$valor[2]." month",strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
			$objResponse->script("byId('cbxFechaRegistro').checked = false;");
			$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
		} else {
			$txtFechaRegistroCompra = $txtFechaProveedor;
		}
	} else if ($frmDcto['cbxFechaRegistro'] == 1) {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
		return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
	}
	
	// VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($valor > 0 && strlen($frmListaArticulo['hddIdCasillaItm'.$valor]) == "") {
				return $objResponse->alert("Existen artículos los cuales no tienen ubicación asignada");
			}
		}
	}
	
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
			if ($frmTotalDcto['lstGastoItem'] == 0 && $frmTotalDcto['hddIdTipoMedida'.$valor] == 1 && $txtMedidaGasto > 0) { // 0 = No, 1 = Si // 1 = Peso
				if ($txtMedidaGasto != str_replace(",", "", $frmListaArticulo['txtTotalPesoItem'])) {
					return $objResponse->alert("El Peso Total por Item no coincide con el Peso Total");
				}
			}
		}
	}
	
	// CALCULA EL SUBTOTAL
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
			
			$subTotalDescuentoItm += $txtCantRecibItm * $hddMontoDescuentoItm;
		}
	}
	
	mysql_query("START TRANSACTION;");
	
	$queryProv = sprintf("SELECT prov.credito, prov_cred.*
	FROM cp_proveedor prov
		LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	WHERE prov.id_proveedor = %s;",
		valTpDato($frmDcto['txtIdProv'], "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$txtDiasCreditoProv = ($rowProv['diascredito'] > 0) ? $rowProv['diascredito'] : 0;
	
	// SI EL PROVEEDOR ES A CREDITO SE LE SUMA LA CANTIDAD DE DIAS DE CREDITO PARA LA FECHA DE VENCIMIENTO
	$fechaVencimiento = ($rowProv['credito'] == "Si") ? suma_fechas(spanDateFormat,$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
	
	if ($idFacturaCompra > 0) {
		$updateSQL = sprintf("UPDATE iv_factura_compra SET
			id_empresa = %s,
			id_modo_compra = %s,
			nacionalizada = %s,
			id_via_envio = %s,
			numero_factura_proveedor = %s,
			numero_control_factura = %s,
			fecha_factura_proveedor = %s,
			id_proveedor = %s,
			fecha_origen = %s,
			fecha_vencimiento = %s,
			id_moneda = %s,
			id_moneda_tasa_cambio = %s,
			id_tasa_cambio = %s,
			id_modulo = %s,
			id_clave_movimiento = %s,
			estatus_factura = %s,
			observacion_factura = %s,
			tipo_pago = %s,
			porcentaje_descuento = %s,
			subtotal_descuento = %s,
			id_retencion_islr = %s,
			base_imponible_islr = %s,
			gasto_manual_item = %s,
			aplica_libros = %s
		WHERE id_factura_compra = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idModoCompra, "int"),
			valTpDato($frmDcto['lstNacionalizar'], "int"),
			valTpDato($frmDcto['lstViaEnvio'], "int"),
			valTpDato($frmDcto['txtNumeroFacturaProveedor'], "text"),
			valTpDato($frmDcto['txtNumeroControl'], "text"),
			valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
			valTpDato($idMonedaLocal, "int"),
			valTpDato($idMonedaOrigen, "int"),
			valTpDato($frmDcto['lstTasaCambio'], "int"),
			valTpDato(0, "int"), // 0 = Repuestos, 1 = Servicios, 2 = Autos, 3 = Administración
			valTpDato($frmDcto['lstClaveMovimiento'], "int"),
			valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
			valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
			valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Contado, 1 = Credito
			valTpDato($txtDescuento, "real_inglesa"),
			valTpDato($txtSubTotalDescuento, "real_inglesa"),
			valTpDato($frmTotalDcto['lstRetencionISLR'], "int"),
			valTpDato($frmTotalDcto['txtBaseImpISLR'], "real_inglesa"),
			valTpDato($frmTotalDcto['lstGastoItem'], "boolean"), // 0 No, 1 = Si
			valTpDato("1", "boolean"), // 0 = No, 1 = Si
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		// INSERTA LOS DATOS DE LA FACTURA
		$insertSQL = sprintf("INSERT INTO iv_factura_compra (id_empresa, id_modo_compra, numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, id_modulo, id_clave_movimiento, estatus_factura, observacion_factura, tipo_pago, porcentaje_descuento, subtotal_descuento, id_retencion_islr, base_imponible_islr, gasto_manual_item, aplica_libros, fecha_registro)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($idModoCompra, "int"),
			valTpDato($frmDcto['txtNumeroFacturaProveedor'], "text"),
			valTpDato($frmDcto['txtNumeroControl'], "text"),
			valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
			valTpDato($idMonedaLocal, "int"),
			valTpDato($idMonedaOrigen, "int"),
			valTpDato($frmDcto['lstTasaCambio'], "int"),
			valTpDato(0, "int"), // 0 = Repuestos, 1 = Servicios, 2 = Vehiculos, 3 = Administración
			valTpDato($frmDcto['lstClaveMovimiento'], "int"),
			valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
			valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
			valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Contado, 1 = Credito
			valTpDato($txtDescuento, "real_inglesa"),
			valTpDato($txtSubTotalDescuento, "real_inglesa"),
			valTpDato($frmTotalDcto['lstRetencionISLR'], "int"),
			valTpDato($frmTotalDcto['txtBaseImpISLR'], "real_inglesa"),
			valTpDato($frmTotalDcto['lstGastoItem'], "boolean"), // 0 No, 1 = Si
			valTpDato("1", "boolean"), // 0 = No, 1 = Si
			valTpDato("NOW()", "campo"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idFacturaCompra = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICA SI LOS ITEMS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryFacturaDetalle = sprintf("SELECT * FROM iv_factura_compra_detalle WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
	if (!$rsFacturaDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
		$existeDet = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$hddIdFacturaDetItm = $frmListaArticulo['hddIdFacturaDetItm'.$valor];
				$hddIdPedidoDetItm = $frmListaArticulo['hddIdPedidoDetItm'.$valor];
				$idArticuloOrg = $frmListaArticulo['hddIdArticuloItm'.$valor];
				$idArticuloSust = $frmListaArticulo['hddIdArticuloSustItm'.$valor];
				$idArticulo = ($idArticuloSust > 0) ? $idArticuloSust : $idArticuloOrg;
				$idCasilla = $frmListaArticulo['hddIdCasillaItm'.$valor];
				
				$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
				$cantRecibida = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
				$cantPendiente = round($cantPedida, 2) - round($cantRecibida, 2);
				$hddIdArancelFamiliaItm = $frmListaArticulo['hddIdArancelFamiliaItm'.$valor];
				$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
				$lstTarifaAdValoremDif = $frmListaArticulo['lstTarifaAdValoremDif'.$valor];
				
				$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
				$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
				$txtGastosItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]) / $cantRecibida;
				$hddPorcDescuentoItm = str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$valor]);
				$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
				$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
				$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
				
				$costoUnitItmConGasto = round($txtCostoItm, 2) + round($txtGastosItm, 2);
				
				// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
				$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal;
				
				$porcDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddPorcDescuentoItm : $txtDescuento; 
				$montoDescuentoUnitItm = ($subTotalDescuentoItm > 0) ? $hddMontoDescuentoItm : $totalDescuentoItm / $cantRecibida;
				
				$costoUnitItmFinal = $costoUnitItmConGasto - $montoDescuentoUnitItm;
				
				$estatusDet = ($cantPendiente == 0) ? 1 : 0;
				
				if ($rowFacturaDetalle['id_factura_compra_detalle'] == $hddIdFacturaDetItm) {
					$existeDet = true;
					
					// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
					$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle WHERE id_pedido_compra_detalle = %s;",
						valTpDato($hddIdPedidoDetItm, "int"));
					$rsPedidoDet = mysql_query($queryPedidoDet);
					if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
					
					$idPedido = $rowPedidoDet['id_pedido_compra'];
					
					// EDITA LOS DATOS DEL DETALLE
					$updateSQL = sprintf("UPDATE iv_factura_compra_detalle SET
						id_factura_compra = %s,
						id_pedido_compra_detalle = %s,
						id_pedido_compra = %s,
						id_articulo = %s,
						id_casilla = %s,
						cantidad = %s,
						pendiente = %s,
						precio_unitario = %s,
						gasto_unitario = %s,
						peso_unitario = %s,
						tipo_descuento = %s,
						porcentaje_descuento = %s,
						subtotal_descuento = %s,
						id_arancel_familia = %s,
						porcentaje_grupo = %s,
						porcentaje_grupo_diferencia = %s,
						tipo = %s,
						id_cliente = %s,
						estatus = %s,
						por_distribuir = %s
					WHERE id_factura_compra_detalle = %s;",
						valTpDato($idFacturaCompra, "int"),
						valTpDato($hddIdPedidoDetItm, "int"),
						valTpDato($idPedido, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($cantPedida, "int"),
						valTpDato($cantRecibida, "int"),
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato($txtGastosItm, "real_inglesa"),
						valTpDato($txtPesoItm, "real_inglesa"),
						valTpDato($frmListaArticulo['hddTipoDescuentoItm'.$valor], "boolean"), // 0 = Porcentaje, 1 = Monto Fijo
						valTpDato($hddPorcDescuentoItm, "real_inglesa"),
						valTpDato($hddMontoDescuentoItm, "real_inglesa"),
						valTpDato($hddIdArancelFamiliaItm, "int"),
						valTpDato($lstTarifaAdValorem, "real_inglesa"),
						valTpDato($lstTarifaAdValoremDif, "real_inglesa"), 
						valTpDato($frmListaArticulo['hddTipoItm'.$valor], "int"), // 0 = Reposicion, 1 = Cliente
						valTpDato($frmListaArticulo['hddIdClienteItm'.$valor], "int"),
						valTpDato($estatusDet, "boolean"), // 0 = En Espera, 1 = Recibido
						valTpDato($cantRecibida, "int"),
						valTpDato($hddIdFacturaDetItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// ELIMINA LOS IMPUESTOS DEL DETALLE DE LA FACTURA
					$deleteSQL = sprintf("DELETE FROM iv_factura_compra_detalle_impuesto WHERE id_factura_compra_detalle = %s;",
						valTpDato($hddIdFacturaDetItm, "int"));
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					$contIvaItm = 0;
					if (isset($arrayObjIvaItm)) {
						foreach ($arrayObjIvaItm as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							if ($valor1[0] == $valor) {
								$contIvaItm++;
								
								$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
								$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
								
								$insertSQL = sprintf("INSERT INTO iv_factura_compra_detalle_impuesto (id_factura_compra_detalle, id_impuesto, impuesto) 
								VALUE (%s, %s, %s);",
									valTpDato($hddIdFacturaDetItm, "int"),
									valTpDato($hddIdIvaItm, "int"),
									valTpDato($hddIvaItm, "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
					
					$hddIdIvaItm = ($contIvaItm == 1) ? $hddIdIvaItm : "";
					$hddIvaItm = ($contIvaItm == 1) ? $hddIvaItm : 0;
					
					// ALMACENA LA CANTIDAD FALTANTE POR DISTRIBUIR DENTRO DE LOS ALMACENES DE LA EMPRESA
					$updateSQL = sprintf("UPDATE iv_factura_compra_detalle SET
						id_iva = %s,
						iva = %s,
						por_distribuir = %s
					WHERE id_factura_compra_detalle = %s;",
						valTpDato($hddIdIvaItm, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($cantRecibida - $cantRecibida, "int"),
						valTpDato($hddIdFacturaDetItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		if ($existeDet == false) {
			$deleteSQL = sprintf("DELETE FROM iv_factura_compra_detalle WHERE id_factura_compra_detalle = %s;",
				valTpDato($rowFacturaDetalle['id_factura_compra_detalle'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
			$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
			WHERE id_pedido_compra = %s
				AND id_articulo = %s;",
				valTpDato($rowFacturaDetalle['id_pedido_compra'], "int"),
				valTpDato($rowFacturaDetalle['id_articulo'], "int"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			
			$hddIdPedidoDetItm = $rowPedidoDet['id_pedido_compra_detalle'];
		}
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
		if ($idArticuloSust > 0) {
			$Result1 = actualizarPedidas($idArticuloOrg);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
		$Result1 = actualizarPedidas($idArticulo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	// INSERTA EL DETALLE DE LA FACTURA
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$hddIdFacturaDetItm = $frmListaArticulo['hddIdFacturaDetItm'.$valor];
			$hddIdPedidoDetItm = $frmListaArticulo['hddIdPedidoDetItm'.$valor];
			$idArticuloOrg = $frmListaArticulo['hddIdArticuloItm'.$valor];
			$idArticuloSust = $frmListaArticulo['hddIdArticuloSustItm'.$valor];
			$idArticulo = ($idArticuloSust > 0) ? $idArticuloSust : $idArticuloOrg;
			$idCasilla = $frmListaArticulo['hddIdCasillaItm'.$valor];
				
			$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$cantRecibida = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
			$cantPendiente = round($cantPedida, 2) - round($cantRecibida, 2);
			$hddIdArancelFamiliaItm = $frmListaArticulo['hddIdArancelFamiliaItm'.$valor];
			$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
			$lstTarifaAdValoremDif = $frmListaArticulo['lstTarifaAdValoremDif'.$valor];
			
			$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
			$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
			$txtGastosItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]) / $cantRecibida;
			$hddPorcDescuentoItm = str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$valor]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
			$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
			
			$costoUnitItmConGasto = round($txtCostoItm, 2) + round($txtGastosItm, 2);
			
			// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
			$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal;
			
			$porcDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddPorcDescuentoItm : $txtDescuento; 
			$montoDescuentoUnitItm = ($subTotalDescuentoItm > 0) ? $hddMontoDescuentoItm : $totalDescuentoItm / $cantRecibida;
			
			$costoUnitItmFinal = $costoUnitItmConGasto - $montoDescuentoUnitItm;
			
			$estatusDet = ($cantPendiente == 0) ? 1 : 0;
			
			if ($idArticulo > 0 && !($hddIdFacturaDetItm > 0)) {
				// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
				$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle WHERE id_pedido_compra_detalle = %s;",
					valTpDato($hddIdPedidoDetItm, "int"));
				$rsPedidoDet = mysql_query($queryPedidoDet);
				if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
				
				$idPedido = $rowPedidoDet['id_pedido_compra'];
				
				// REGISTRA EL DETALLE DE LA FACTURA
				$insertSQL = sprintf("INSERT INTO iv_factura_compra_detalle (id_factura_compra, id_pedido_compra_detalle, id_pedido_compra, id_articulo, id_casilla, cantidad, pendiente, precio_unitario, gasto_unitario, peso_unitario, tipo_descuento, porcentaje_descuento, subtotal_descuento, id_arancel_familia, porcentaje_grupo, porcentaje_grupo_diferencia, tipo, id_cliente, estatus, por_distribuir)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFacturaCompra, "int"),
					valTpDato($hddIdPedidoDetItm, "int"),
					valTpDato($idPedido, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($cantPedida, "int"),
					valTpDato($cantRecibida, "int"),
					valTpDato($txtCostoItm, "real_inglesa"),
					valTpDato($txtGastosItm, "real_inglesa"),
					valTpDato($txtPesoItm, "real_inglesa"),
					valTpDato($frmListaArticulo['hddTipoDescuentoItm'.$valor], "boolean"), // 0 = Porcentaje, 1 = Monto Fijo
					valTpDato($porcDescuentoArt, "real_inglesa"),
					valTpDato($montoDescuentoUnitArt, "real_inglesa"),
					valTpDato($hddIdArancelFamiliaItm, "int"),
					valTpDato($lstTarifaAdValorem, "real_inglesa"),
					valTpDato($lstTarifaAdValoremDif, "real_inglesa"),
					valTpDato($frmListaArticulo['hddTipoItm'.$valor], "int"), // 0 = Reposicion, 1 = Cliente
					valTpDato($frmListaArticulo['hddIdClienteItm'.$valor], "int"),
					valTpDato($estatusDet, "boolean"), // 0 = En Espera, 1 = Recibido
					valTpDato($cantRecibida, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$hddIdFacturaDetItm = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
					
				// ELIMINA LOS IMPUESTOS DEL DETALLE DE LA FACTURA
				$deleteSQL = sprintf("DELETE FROM iv_factura_compra_detalle_impuesto WHERE id_factura_compra_detalle = %s;",
					valTpDato($hddIdFacturaDetItm, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$contIvaItm = 0;
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor) {
							$contIvaItm++;
							
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
							
							$insertSQL = sprintf("INSERT INTO iv_factura_compra_detalle_impuesto (id_factura_compra_detalle, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($hddIdFacturaDetItm, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
						}
					}
				}
				
				$hddIdIvaItm = ($contIvaItm == 1) ? $hddIdIvaItm : "";
				$hddIvaItm = ($contIvaItm == 1) ? $hddIvaItm : 0;
				
				// ALMACENA LA CANTIDAD FALTANTE POR DISTRIBUIR DENTRO DE LOS ALMACENES DE LA EMPRESA
				$updateSQL = sprintf("UPDATE iv_factura_compra_detalle SET
					id_iva = %s,
					iva = %s,
					por_distribuir = %s
				WHERE id_factura_compra_detalle = %s;",
					valTpDato($hddIdIvaItm, "int"),
					valTpDato($hddIvaItm, "real_inglesa"),
					valTpDato($cantRecibida - $cantRecibida, "int"),
					valTpDato($hddIdFacturaDetItm, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
			
			if ($idModoCompra == 1) { // 1 = Nacional
				$costoUnitarioPedido = $txtCostoItm;
			} else if ($idModoCompra == 2) { // 2 = Importacion
				$costoUnitarioPedido = $txtCostoItm;
			}
			
			// ACTUALIZA EL PRECIO DEL DETALLE DEL PEDIDO
			$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
				id_articulo = %s,
				precio_unitario = %s
			WHERE id_pedido_compra_detalle = %s;",
				valTpDato($idArticulo, "int"),
				valTpDato($costoUnitarioPedido, "real_inglesa"),
				valTpDato($hddIdPedidoDetItm, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
			if ($idArticuloSust > 0) {
				$Result1 = actualizarPedidas($idArticuloOrg);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			}
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
			$Result1 = actualizarPedidas($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
	}
	
	// ACTIVA LA RELACION DEL ARTICULO CON LA EMPRESA Y LAS UBICACIONES
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idArticuloOrg = $frmListaArticulo['hddIdArticuloItm'.$valor];
			$idArticuloSust = $frmListaArticulo['hddIdArticuloSustItm'.$valor];
			$idArticulo = ($idArticuloSust > 0) ? $idArticuloSust : $idArticuloOrg;
			$idCasilla = $frmListaArticulo['hddIdCasillaItm'.$valor];
			
			$idPedidoDet = $frmListaArticulo['hddIdPedidoDetItm'.$valor];
			
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArt = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsArt = mysql_query($queryArt);
			if (!$rsArt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArt = mysql_fetch_assoc($rsArt);
			
			// VERIFICA SI EL ARTICULO ESTA LIGADO A LA EMPRESA
			$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rsArtEmp = mysql_query($queryArtEmp);
			if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
			
			if ($totalRowsArtEmp > 0) { // SI EXISTE EL ARTICULO PARA LA EMRPESA
				if ($idArticuloSust > 0) { // SI SE AGREGO UN ARTICULO SUSTITUTO
					// SE LE QUITA LA UBICACION PREDETERMINADA AL ARTICULO QUE FUE SUSTITUIDO
					$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
						id_casilla_predeterminada = NULL,
						id_casilla_predeterminada_compra = NULL,
						sustituido = 1,
						estatus = NULL
					WHERE id_articulo = %s
						AND id_empresa = %s;",
						valTpDato($idArticuloOrg, "int"),
						valTpDato($idEmpresa, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			} else { // SI NO EXISTE EL ARTICULO PARA LA EMPRESA
				if ($idArticuloSust > 0) { // SI SE AGREGO UN ARTICULO SUSTITUTO
					$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
						id_casilla_predeterminada = NULL,
						id_casilla_predeterminada_compra = NULL,
						sustituido = 1,
						estatus = NULL
					WHERE id_articulo = %s
						AND id_empresa = %s;",
						valTpDato($idArticuloOrg, "int"),
						valTpDato($idEmpresa, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// SE LIGA EL ARTICULO SUSTITUTO CON LA EMPRESA
					$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, id_casilla_predeterminada, id_casilla_predeterminada_compra, clasificacion, estatus)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato("F", "text"),
						valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				} else {
					// COMO EL ARTICULO NO ESTA LIGADO CON LA EMPRESA, SE LIGARA
					$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, clasificacion, estatus)
					VALUE (%s, %s, %s, %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato("F", "text"),
						valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
			
			
			// VERIFICA SI HAY RELACION ENTRE ARTICULO Y LA UBICACION SELECCIONADA
			$queryArtAlmacen = sprintf("SELECT * FROM iv_articulos_almacen art_almacen
			WHERE art_almacen.id_articulo = %s
				AND art_almacen.id_casilla = %s;",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"));
			$rsArtAlmacen = mysql_query($queryArtAlmacen);
			if (!$rsArtAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtAlmacen = mysql_num_rows($rsArtAlmacen);
			$rowArtAlmacen = mysql_fetch_assoc($rsArtAlmacen);
			if ($totalRowsArtAlmacen > 0) {
				// ACTIVA LA UBICACION SELECCIONADA EN EL REGISTRO DE COMPRA
				$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
					estatus = 1
				WHERE id_articulo = %s
					AND id_casilla = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else {
				 // SI EL ARTICULO NO TIENE UBICACION, SE LE ASIGNA LA SELECCIONADA
				if ($idArticuloSust > 0) {
					// DESACTIVA LA UBICACION Y PONE EL ESTATUS SUSTITUIDO
					$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
						sustituido = 1,
						estatus = NULL
					WHERE id_articulo = %s
						AND id_casilla = %s;",
						valTpDato($idArticuloOrg, "int"),
						valTpDato($idCasilla, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// LE AGREGA LA UBICACION AL ARTICULO SUSTITUTO
					$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
					VALUE (%s, %s, %s);",
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				} else {
					$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
					VALUE (%s, %s, %s);",
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nCod Art: ".$rowArt['codigo_articulo']); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
			
			
			// VERIFICA SI EL ARTICULO TIENE UNA UBICACION PREDETERMINADA EN UN ALMACEN DE LA EMPRESA
			$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rsArtEmp = mysql_query($queryArtEmp);
			if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
			
			// SI LA CASILLA SELECCIONADA ES DISTINTA A LA CASILLA PREDETERMINADA Y NO TIENE CASILLA PREDETERMINADA LE ASIGNA LA SELECCIONADA EN EL REGISTRO DE COMPRA
			$idCasillaPredetVenta = ($idCasilla != $rowArtEmp['id_casilla_predeterminada'] && $rowArtEmp['id_casilla_predeterminada'] == "") ? $idCasilla : $rowArtEmp['id_casilla_predeterminada'];
			$idCasillaPredetCompra = ($idCasilla != $rowArtEmp['id_casilla_predeterminada_compra'] && $rowArtEmp['id_casilla_predeterminada_compra'] == "") ? $idCasilla : $rowArtEmp['id_casilla_predeterminada_compra'];
			
			// MODIFICA LA CASILLA PREDETERMINADA DEL ARTICULO EN LA EMPRESA
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				id_casilla_predeterminada = %s,
				id_casilla_predeterminada_compra = %s,
				cantidad_pedida = 0,
				estatus = 1
			WHERE id_articulo_empresa = %s;",
				valTpDato($idCasillaPredetVenta, "int"),
				valTpDato($idCasillaPredetCompra, "int"),
				valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// VERIFICACION PARA SABER SI LA CASILLA PREDETERMINADA ES VÁLIDA
			$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_casilla = %s
				AND estatus = 1;",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasillaPredetCompra, "int"));
			$rsArtAlm = mysql_query($queryArtAlm);
			if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
			$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
			if (!($totalRowsArtAlm > 0)) {
				// BUSCA LA PRIMERA UBICACION ACTIVA DEL ARTICULO PARA PONERSELA COMO PREDETERMINADA
				$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
					id_casilla_predeterminada = (SELECT art_alm.id_casilla
												FROM iv_almacenes almacen
													INNER JOIN iv_calles calle ON (almacen.id_almacen = calle.id_almacen)
													INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
													INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
													INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
													INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
												WHERE almacen.id_empresa = art_emp.id_empresa
													AND art_alm.id_articulo = art_emp.id_articulo
													AND art_alm.estatus = 1
												LIMIT 1),
					id_casilla_predeterminada_compra = (SELECT art_alm.id_casilla
												FROM iv_almacenes almacen
													INNER JOIN iv_calles calle ON (almacen.id_almacen = calle.id_almacen)
													INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
													INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
													INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
													INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
												WHERE almacen.id_empresa = art_emp.id_empresa
													AND art_alm.id_articulo = art_emp.id_articulo
													AND art_alm.estatus = 1
												LIMIT 1)
				WHERE art_emp.id_empresa = %s
					AND art_emp.id_articulo = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nId Casilla: ".$idCasilla); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	if ($idModoCompra == 2) { // 2 = Importacion
		// GUARDA LOS DATOS DE LAS FACTURA DE IMPORTACION
		$Result1 = guardarDctoImportacion($idFacturaCompra, $frmDcto, $frmTotalDcto, "PREREGISTRO");
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	// VERIFICA SI LOS GASTOS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryFacturaGasto = sprintf("SELECT * FROM iv_factura_compra_gasto
	WHERE id_factura_compra = %s
		AND id_modo_gasto IN (1,3);",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaGasto = mysql_query($queryFacturaGasto);
	if (!$rsFacturaGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowFacturaGasto = mysql_fetch_assoc($rsFacturaGasto)) {
		$idGasto = $rowFacturaGasto['id_gasto'];
		
		$existeDet = false;
		if (isset($arrayObjGasto)) {
			foreach ($arrayObjGasto as $indice => $valor) {
				$hddIdFacturaCompraGasto = $frmTotalDcto['hddIdFacturaCompraGasto'.$valor];
				$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
				$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
				$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
				
				if (round($txtMontoGasto, 2) != 0 && $rowFacturaGasto['id_factura_compra_gasto'] == $hddIdFacturaCompraGasto) {
					$existeDet = true;
					
					// ELIMINA LOS IMPUESTOS DEL GASTO DE LA FACTURA
					$deleteSQL = sprintf("DELETE FROM iv_factura_compra_gasto_impuesto WHERE id_factura_compra_gasto = %s;",
						valTpDato($hddIdFacturaCompraGasto, "int"));
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// EDITA LOS DATOS DEL GASTO
					$updateSQL = sprintf("UPDATE iv_factura_compra_gasto cxp_fact_gasto, pg_gastos gasto SET
						cxp_fact_gasto.tipo = %s,
						cxp_fact_gasto.porcentaje_monto = %s,
						cxp_fact_gasto.monto = %s,
						cxp_fact_gasto.monto_medida = %s,
						cxp_fact_gasto.id_modo_gasto = gasto.id_modo_gasto,
						cxp_fact_gasto.id_tipo_medida = gasto.id_tipo_medida,
						cxp_fact_gasto.afecta_documento = gasto.afecta_documento
					WHERE gasto.id_gasto = %s
						AND cxp_fact_gasto.id_factura_compra_gasto = %s;",
						valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
						valTpDato($txtPorcGasto, "real_inglesa"),
						valTpDato($txtMontoGasto, "real_inglesa"),
						valTpDato($txtMedidaGasto, "real_inglesa"),
						valTpDato($idGasto, "int"),
						valTpDato($rowFacturaGasto['id_factura_compra_gasto'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					$contIvaGasto = 0;
					if (isset($arrayObjIvaGasto)) {
						foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							if ($valor1[0] == $valor) {
								$contIvaGasto++;
								
								$hddIdIvaGasto = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]];
								$hddIvaGasto = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valor1[1]];
								$hddEstatusIvaGasto = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valor1[1]];
								
								$insertSQL = sprintf("INSERT INTO iv_factura_compra_gasto_impuesto (id_factura_compra_gasto, id_impuesto, impuesto) 
								VALUE (%s, %s, %s);",
									valTpDato($hddIdFacturaCompraGasto, "int"),
									valTpDato($hddIdIvaGasto, "int"),
									valTpDato($hddIvaGasto, "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
					
					$hddIdIvaGasto = ($contIvaGasto == 1) ? $hddIdIvaGasto : "";
					$hddIvaGasto = ($contIvaGasto == 1) ? $hddIvaGasto : 0;
					$hddEstatusIvaGasto = ($contIvaGasto == 1) ? $hddEstatusIvaGasto : "1";
					
					// EDITA EL IMPUESTO DEL GASTO
					$updateSQL = sprintf("UPDATE iv_factura_compra_gasto SET
						id_iva = %s,
						iva = %s,
						estatus_iva = %s
					WHERE id_factura_compra_gasto = %s;",
						valTpDato($hddIdIvaGasto, "int"),
						valTpDato($hddIvaGasto, "real_inglesa"),
						valTpDato($hddEstatusIvaGasto, "boolean"),
						valTpDato($hddIdFacturaCompraGasto, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		if ($existeDet == false) {
			$deleteSQL = sprintf("DELETE FROM iv_factura_compra_gasto WHERE id_factura_compra_gasto = %s;",
				valTpDato($rowFacturaGasto['id_factura_compra_gasto'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// INSERTA LOS GASTOS DE LA FACTURA
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$hddIdFacturaCompraGasto = $frmTotalDcto['hddIdFacturaCompraGasto'.$valor];
			$hddIdGasto = $frmTotalDcto['hddIdGasto'.$valor];
			
			$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
			$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
			$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
			
			if (round($txtMontoGasto, 2) != 0 && !($hddIdFacturaCompraGasto > 0)) {
				$insertSQL = sprintf("INSERT INTO iv_factura_compra_gasto (id_factura_compra, id_gasto, tipo, porcentaje_monto, monto, monto_medida, id_modo_gasto, id_tipo_medida, afecta_documento)
				SELECT %s, id_gasto, %s, %s, %s, %s, id_modo_gasto, id_tipo_medida, afecta_documento FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idFacturaCompra, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($txtMedidaGasto, "real_inglesa"),
					valTpDato($hddIdGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$hddIdFacturaCompraGasto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$contIvaGasto = 0;
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor) {
							$contIvaGasto++;
							
							$hddIdIvaGasto = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]];
							$hddIvaGasto = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valor1[1]];
							$hddEstatusIvaGasto = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valor1[1]];
							
							$insertSQL = sprintf("INSERT INTO iv_factura_compra_gasto_impuesto (id_factura_compra_gasto, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($hddIdFacturaCompraGasto, "int"),
								valTpDato($hddIdIvaGasto, "int"),
								valTpDato($hddIvaGasto, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
						}
					}
				}
				
				$hddIdIvaGasto = ($contIvaGasto == 1) ? $hddIdIvaGasto : "";
				$hddIvaGasto = ($contIvaGasto == 1) ? $hddIvaGasto : 0;
				$hddEstatusIvaGasto = ($contIvaGasto == 1) ? $hddEstatusIvaGasto : "1";
				
				// EDITA EL IMPUESTO DEL GASTO
				$updateSQL = sprintf("UPDATE iv_factura_compra_gasto SET
					id_iva = %s,
					iva = %s,
					estatus_iva = %s
				WHERE id_factura_compra_gasto = %s;",
					valTpDato($hddIdIvaGasto, "int"),
					valTpDato($hddIvaGasto, "real_inglesa"),
					valTpDato($hddEstatusIvaGasto, "boolean"),
					valTpDato($hddIdFacturaCompraGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// ELIMINA LOS IMPUESTOS DE LA FACTURA
	$deleteSQL = sprintf("DELETE FROM iv_factura_compra_iva WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// INSERTA LOS IMPUESTOS DE LA FACTURA
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO iv_factura_compra_iva (id_factura_compra, base_imponible, subtotal_iva, id_iva, iva, lujo)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idFacturaCompra, "int"),
				valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
				valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['hddLujoIva'.$valor], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	// ACTUALIZA LOS MONTOS DE LA FACTURA DE COMPRA
	$updateSQL = sprintf("UPDATE iv_factura_compra SET
		monto_exento = IFNULL((SELECT SUM(cxp_fact_det.pendiente * cxp_fact_det.precio_unitario)
								FROM iv_factura_compra_detalle cxp_fact_det
								WHERE (cxp_fact_det.id_iva = 0 OR cxp_fact_det.id_iva IS NULL)
									AND cxp_fact_det.id_factura_compra = iv_factura_compra.id_factura_compra), 0)
						+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
								FROM iv_factura_compra_gasto cxp_fact_gasto
								WHERE cxp_fact_gasto.id_modo_gasto IN (1)
									AND cxp_fact_gasto.afecta_documento IN (1)
									AND cxp_fact_gasto.estatus_iva = 0
									AND cxp_fact_gasto.id_factura_compra = iv_factura_compra.id_factura_compra), 0),
		monto_exonerado = 0,
		subtotal_factura = IFNULL((SELECT SUM(cxp_fact_det.pendiente * cxp_fact_det.precio_unitario)
									FROM iv_factura_compra_detalle cxp_fact_det
									WHERE cxp_fact_det.id_factura_compra = iv_factura_compra.id_factura_compra), 0),
		saldo_factura = %s
	WHERE id_factura_compra = %s;",
		valTpDato($frmTotalDcto['txtMontoTotalFactura'], "real_inglesa"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA LA CANTIDAD DE ARTICULOS PENDIENTES EN EL DETALLE DEL PEDIDO
	$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
		pendiente = cantidad - (IFNULL((SELECT SUM(cxp_fact_det.pendiente)
										FROM iv_factura_compra_detalle cxp_fact_det
										WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
											AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
								+ IFNULL((SELECT SUM(cxp_fact_det.cantidad)
										FROM cp_factura_detalle cxp_fact_det
										WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
											AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)),
		estatus = (CASE 
					WHEN (cantidad - (IFNULL((SELECT SUM(cxp_fact_det.pendiente)
											FROM iv_factura_compra_detalle cxp_fact_det
											WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
									+ IFNULL((SELECT SUM(cxp_fact_det.cantidad)
											FROM cp_factura_detalle cxp_fact_det
											WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) = 0 THEN
						1
					WHEN (cantidad - (IFNULL((SELECT SUM(cxp_fact_det.pendiente)
											FROM iv_factura_compra_detalle cxp_fact_det
											WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
									+ IFNULL((SELECT SUM(cxp_fact_det.cantidad)
											FROM cp_factura_detalle cxp_fact_det
											WHERE cxp_fact_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND cxp_fact_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) > 0 THEN
						0
				END)
	WHERE estatus IN (0,1)
		AND id_pedido_compra IN (SELECT cxp_fact_det.id_pedido_compra FROM iv_factura_compra_detalle cxp_fact_det
								WHERE cxp_fact_det.id_factura_compra IN (%s));",
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdFactura","value",$idFacturaCompra);
	
	$objResponse->alert("Registro de Compra Guardado con Éxito");
	
	$objResponse->script(sprintf("
	byId('btnCancelar').click();"));
	
	return $objResponse;
}

function importarDcto($frmImportarArchivo, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$frmImportarArchivo['hddUrlArchivo'];
	
	$phpExcel = new PHPExcel_Reader_Excel2007();
	$archivoExcel = $phpExcel->load($inputFileName);
	
	$archivoExcel->setActiveSheetIndex(0);
	$i = 1;
	while ($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue() != '') {
		$cantPedida = $archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue();
		$cantRecibida = $archivoExcel->getActiveSheet()->getCell('G'.$i)->getValue();
		
		if ($itemExcel == true && $cantRecibida <= $cantPedida && $cantRecibida > 0) {
			$arrayFilaImportar[] = array(
				"numero_referencia" => $archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(),
				"codigo_articulo" => $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(),
				"descripcion_articulo" => $archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue(),
				"codigo_arancel" => $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue(),
				"porcentaje_grupo" => $archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue(),
				"cantidad_pedida" => $archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue(),
				"cantidad_recibida" => $archivoExcel->getActiveSheet()->getCell('G'.$i)->getValue(),
				"cantidad_pendiente" => $archivoExcel->getActiveSheet()->getCell('H'.$i)->getValue(),
				"costo_unitario" => $archivoExcel->getActiveSheet()->getCell('I'.$i)->getValue(),
				"porcentaje_impuesto" => $archivoExcel->getActiveSheet()->getCell('J'.$i)->getValue(),
				"total" => $archivoExcel->getActiveSheet()->getCell('K'.$i)->getValue(),
				"descripcion_almacen" => $archivoExcel->getActiveSheet()->getCell('L'.$i)->getValue(),
				"descripcion_ubicacion" => $archivoExcel->getActiveSheet()->getCell('M'.$i)->getValue(),
				"id_cliente" => $archivoExcel->getActiveSheet()->getCell('N'.$i)->getValue());
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Nro. Referencia"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Nro. Referencia"))
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Nro. Referencia"))
		|| trim(strtoupper(htmlentities($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Nro. Referencia"))
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Nro. Referencia"))) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayFilaImportar)) {
		foreach ($arrayFilaImportar as $indiceFilaImportar => $valorFilaImportar) {
			$queryPedidoDet = sprintf("SELECT *
			FROM iv_pedido_compra_detalle ped_comp_det
				INNER JOIN iv_pedido_compra ped_comp ON (ped_comp_det.id_pedido_compra = ped_comp.id_pedido_compra)
				INNER JOIN iv_articulos art ON (ped_comp_det.id_articulo = art.id_articulo)
			WHERE ped_comp.id_pedido_compra_referencia = %s
				AND art.codigo_articulo LIKE %s
				AND ped_comp.estatus_pedido_compra = 2;",
				valTpDato($arrayFilaImportar[$indiceFilaImportar]['numero_referencia'], "text"),
				valTpDato($arrayFilaImportar[$indiceFilaImportar]['codigo_articulo'], "text"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			
			$idPedidoDetalle = $rowPedidoDet['id_pedido_compra_detalle'];
			
			// RUTINA PARA AGREGAR EL ARTICULO
			$idEmpresa = ($frmDcto['txtIdEmpresa'] > 0) ? $frmDcto['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];
			
			// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Compra)
			$queryConfig6 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 6 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
			$rsConfig6 = mysql_query($queryConfig6);
			if (!$rsConfig6) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowConfig6 = mysql_fetch_assoc($rsConfig6);
			
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if ($frmListaArticulo['hddIdPedidoDetItm'.$valor] == $idPedidoDetalle
					&& str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]) == $arrayFilaImportar[$indiceFilaImportar]['costo_unitario']) {
						$existe = true;
					}
				}
			}
			
			$idMonedaLocal = $frmDcto['hddIdMoneda'];
			$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
			
			if ($existe == false) {
				if (count($arrayObj) < $rowConfig6['valor']) {
					// BUSCA LOS DATOS DEL ARANCEL
					$queryArancelFamilia = sprintf("SELECT 
						arancel_fam.id_arancel_familia,
						arancel_fam.id_arancel_grupo,
						arancel_fam.codigo_familia,
						arancel_fam.codigo_arancel,
						arancel_fam.descripcion_arancel,
						arancel_grupo.codigo_grupo,
						arancel_grupo.porcentaje_grupo
					FROM pg_arancel_familia arancel_fam
						INNER JOIN pg_arancel_grupo arancel_grupo ON (arancel_fam.id_arancel_grupo = arancel_grupo.id_arancel_grupo)
					WHERE arancel_fam.codigo_arancel LIKE %s;", 
						valTpDato($arrayFilaImportar[$indiceFilaImportar]['codigo_arancel'], "text"));
					$rsArancelFamilia = mysql_query($queryArancelFamilia);
					if (!$rsArancelFamilia) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
					$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
					
					$hddIdArancelFamiliaItm = ($arrayFilaImportar[$indiceFilaImportar]['codigo_arancel'] == -1) ? $arrayFilaImportar[$indiceFilaImportar]['codigo_arancel'] : $rowArancelFamilia['id_arancel_familia'];
					
					$cantPedida = $arrayFilaImportar[$indiceFilaImportar]['cantidad_pedida'];
					$cantRecibida = $arrayFilaImportar[$indiceFilaImportar]['cantidad_recibida'];
					$costoUnitario = $arrayFilaImportar[$indiceFilaImportar]['costo_unitario'];
					$lstTarifaAdValorem = $arrayFilaImportar[$indiceFilaImportar]['porcentaje_grupo'];
					$almacen = $arrayFilaImportar[$indiceFilaImportar]['descripcion_almacen'];
					$ubicacion = $arrayFilaImportar[$indiceFilaImportar]['descripcion_ubicacion'];
					$idClienteArt = $arrayFilaImportar[$indiceFilaImportar]['id_cliente'];
					
					$Result1 = insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, $idPedidoDetalle, "", $idMonedaLocal, $idMonedaOrigen, $cantPedida, $cantRecibida, $costoUnitario, $hddIdArancelFamiliaItm, $lstTarifaAdValorem, $almacen, $ubicacion, $idClienteArt);
					$arrayObjUbicacion = $Result1[3];
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$contFila = $Result1[2];
						$frmListaArticulo['hddIdPedidoDetItm'.$contFila] = $idPedidoDetalle;
						$objResponse->script($Result1[1]);
						$arrayObj[] = $contFila;
					}
				} else {
					$msjCantidadExcedida = "Solo puede agregar un máximo de ".$rowConfig6['valor']." items por Registro";
				}
			} else {
				$arrayObjExiste[] = $arrayFilaImportar[$indiceFilaImportar]['codigo_articulo'];
			}
		}
		$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
		if (strlen($msjCantidadExcedida) > 0)
			$objResponse->alert(utf8_encode($msjCantidadExcedida));
			
		if (count($arrayObjExiste) > 0) {
			$objResponse->alert(("Ya se encuentra(n) incluido(s) ".count($arrayObjExiste)." items: ".implode(", ",$arrayObjExiste)));
		} else if (count($arrayObj) > 0) {
			$objResponse->alert(("Pedido Importado con Éxito"));
		} else {
			$objResponse->alert(("No se pudo importar el archivo"));
		}
		
		$objResponse->script("
		byId('btnCancelarImportarPedido').click();");
	} else {
		$objResponse->alert("Verifique que el Pedido tenga Cantidades Recibidas");
	}
	
	return $objResponse;
}

function insertarArticulo($idPedidoDetalle, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = ($frmDcto['txtIdEmpresa'] > 0) ? $frmDcto['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];

	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Compra)
	$queryConfig6 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 6 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig6 = mysql_query($queryConfig6);
	if (!$rsConfig6) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig6 = mysql_fetch_assoc($rsConfig6);
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$existe = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdPedidoDetItm'.$valor] == $idPedidoDetalle) {
				$existe = true;
			}
		}
	}
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	if ($existe == false) {
		if (count($arrayObj) < $rowConfig6['valor']) {
			$Result1 = insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, $idPedidoDetalle, "", $idMonedaLocal, $idMonedaOrigen, "", "", "", -1, -1);
			$arrayObjUbicacion = $Result1[3];
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert("Solo puede agregar un máximo de ".$rowConfig6['valor']." items por Registro de Compra");
		}
	} else {
		$objResponse->alert("Este item ya se encuentra incluido");
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function insertarArticuloMult($frmArticuloMultiple, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = ($frmDcto['txtIdEmpresa'] > 0) ? $frmDcto['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];

	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Compra)
	$queryConfig6 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 6 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig6 = mysql_query($queryConfig6);
	if (!$rsConfig6) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig6 = mysql_fetch_assoc($rsConfig6);
	
	if ($frmArticuloMultiple['txtTotalCantArtMult'] == $frmArticuloMultiple['txtCantidadArtMultiple']) {
		$cantDetAgregados = 0;
		for ($cont = 1; $cont <= $frmArticuloMultiple['hddCantItmArticuloMultiple']; $cont++) {
			$objResponse->script("byId('txtCantArtMult".$cont."').className = 'inputInicial'");
			$objResponse->script("byId('txtCantEntregadaArtMult".$cont."').className = 'inputInicial'");
			$objResponse->script("byId('txtCostoArtMult".$cont."').className = 'inputInicial'");
			
			// SI LA CANTIDAD RECIBIDA ES MAYOR A LA CANTIDAD DE DICHO COSTO
			if (doubleval($frmArticuloMultiple['txtCantEntregadaArtMult'.$cont]) > doubleval($frmArticuloMultiple['txtCantArtMult'.$cont])) {
				$arrayCantidadInvalida[] = "txtCantEntregadaArtMult".$cont;
			}
		
			if ($frmArticuloMultiple['txtCantEntregadaArtMult'.$cont] > 0) {
				if (doubleval($frmArticuloMultiple['txtCostoArtMult'.$cont]) <= 0) {
					$arrayCantidadInvalida[] = "txtCostoArtMult".$cont;
				} else {
					$cantDetAgregados++;
				}
			}
			
		}
		
		// SI HAY CANTIDADES INVALIDAS SE PINTARAN DICHOS OBJETOS, CONTENIDOS EN LAS MATRICES
		if (count($arrayCantidadInvalida) > 0 ) {
			if (count($arrayCantidadInvalida) > 0) {
				foreach ($arrayCantidadInvalida as $indice => $valor) {
					$objResponse->script("byId('".$valor."').className = 'inputErrado'");
				}
			}
			
			$objResponse->alert(("Los campos señalados en rojo son invalidos"));
		} else if ($cantDetAgregados > 0) {// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
			$arrayObj = $frmListaArticulo['cbx'];
			$contFila = $arrayObj[count($arrayObj)-1];
			
			for ($cont = 1; $cont <= $frmArticuloMultiple['hddCantItmArticuloMultiple']; $cont++) {
				$idPedidoDetalle = $frmArticuloMultiple['hddIdPedidoCompraDetalle'];
				
				if ($frmArticuloMultiple['txtCantEntregadaArtMult'.$cont] > 0) {
					$existe = false;
					if (isset($arrayObj)) {
						foreach ($arrayObj as $indice => $valor) {
							if ($frmListaArticulo['hddIdPedidoDetItm'.$valor] == $idPedidoDetalle) {
								$existe = true;
							}
						}
					}
					
					if ($existe == false) {
						if (count($arrayObj) + $cantDetAgregados < $rowConfig6['valor']) {
							$Result1 = insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, $idPedidoDetalle, "", $idMonedaLocal, $idMonedaOrigen, $frmArticuloMultiple['txtCantArtMult'.$cont], $frmArticuloMultiple['txtCantEntregadaArtMult'.$cont], $frmArticuloMultiple['txtCostoArtMult'.$cont], -1, -1);
							$arrayObjUbicacion = $Result1[3];
							if ($Result1[0] != true && strlen($Result1[1]) > 0) {
								return $objResponse->alert($Result1[1]);
							} else if ($Result1[0] == true) {
								$contFila = $Result1[2];
								$objResponse->script($Result1[1]);
								$arrayObj[] = $contFila;
							}
						} else {
							$objResponse->alert("Solo puede agregar un máximo de ".$rowConfig6['valor']." items por Registro de Compra");
						}
					} else {
						$objResponse->alert("Este item ya se encuentra incluido");
					}
				}
			}
			
			$objResponse->script("byId('btnCancelarArticuloMultiple').click();");
		}
	} else {
		$objResponse->script("byId('txtTotalCantArtMult').className = 'inputErrado'");
		
		$objResponse->alert(("Los campos señalados en rojo son invalidos"));
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function insertarGasto($idGasto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	$contFilaGasto = $arrayObjGasto[count($arrayObjGasto)-1];
	
	$existe = false;
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			if ($frmTotalDcto['hddIdGasto'.$valor] == $idGasto) {
				$existe = true;
			}
		}
	}
	
	if ($existe == false) {
		$Result1 = insertarItemGasto($contFilaGasto, $idGasto);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFilaGasto = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObjGasto[] = $contFilaGasto;
		}
	} else {
		$objResponse->alert("Este item ya se encuentra incluido");
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function insertarGastoImportacion($idGasto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	$contFilaGasto = $arrayObjGasto[count($arrayObjGasto)-1];
	
	$existe = false;
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			if ($frmTotalDcto['hddIdGasto'.$valor] == $idGasto) {
				$existe = true;
			}
		}
	}
	
	if ($existe == false) {
		$Result1 = insertarItemGastoImportacion($contFilaGasto, $idGasto);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFilaGasto = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObjGasto[] = $contFilaGasto;
		}
	} else {
		$objResponse->alert("Este item ya se encuentra incluido");
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function insertarOtroCargo($idGasto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjOtroCargo = $frmTotalDcto['cbxOtroCargo'];
	$contFilaOtroCargo = $arrayObjOtroCargo[count($arrayObjOtroCargo)-1];
	
	$Result1 = insertarItemOtroCargo($contFilaOtroCargo, $idGasto);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFilaOtroCargo = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObjOtroCargo[] = $contFilaOtroCargo;
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function listaArticuloPedido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT 
		ped_comp_det.*,
		art.codigo_articulo,
		art.descripcion,
		
		(SELECT SUM(ped_comp_det_impsto.impuesto) FROM iv_pedido_compra_detalle_impuesto ped_comp_det_impsto
		WHERE ped_comp_det_impsto.id_pedido_compra_detalle = ped_comp_det.id_pedido_compra_detalle) AS porc_iva
	FROM iv_pedido_compra_detalle ped_comp_det
		INNER JOIN iv_articulos art ON (ped_comp_det.id_articulo = art.id_articulo)
	WHERE id_pedido_compra = %s",
		valTpDato($valCadBusq[0], "int"));
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "52%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "6%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Ped.");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "6%", $pageNum, "pendiente", $campOrd, $tpOrd, $valBusq, $maxRows, "Pend.");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "8%", $pageNum, "precio_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unit.");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "4%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaArticuloPedido", "10%", $pageNum, "(cantidad*precio_unitario)", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= " </tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$porcIva = ($row['porc_iva'] > 0 && $row['estatus_iva'] == 1) ? $row['porc_iva']."%" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
			if ($row['pendiente'] > 0) {
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarArticulo%s\" onclick=\"validarInsertarArticulo('%s','%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_pedido_compra_detalle'],
					$row['pendiente']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cantidad']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['pendiente']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$porcIva."</td>";
			$htmlTb .= "<td align=\"right\">".number_format(($row['cantidad']*$row['precio_unitario']), 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloPedido(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divArticulosPedido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$queryPedido = sprintf("SELECT * FROM iv_pedido_compra
	WHERE estatus_pedido_compra = 2
		AND id_pedido_compra = %s",
		valTpDato($valCadBusq[0], "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPedido = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$objResponse->assign("spanTituloPedido","innerHTML",$rowPedido['id_pedido_compra_referencia']);
	
	$objResponse->script("
	if (byId('trArticulosPedido').style.display == 'none') {
		byId('trArticulosPedido').style.display = '';
	}");
	
	return $objResponse;
}

function listaArticuloSustituto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 18, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo_sustituido = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("vw_iv_art_datos_bas.codigo_articulo LIKE %s
			OR vw_iv_art_datos_bas.descripcion LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM iv_articulos_codigos_sustitutos art_cod_sust
		INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_bas ON (art_cod_sust.id_articulo = vw_iv_art_datos_bas.id_articulo) %s", $sqlBusq);
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
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr>" : "";
		
		$class = "class=\"divGris\"";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['foto'];
		
		$htmlTb .= "<td width=\"33%\" valign=\"top\">";
			$htmlTb .= sprintf("<table border=\"0\" %s width=\"%s\">",
				$class,
				"100%");
			$htmlTb .= "<tr>";
				$htmlTb .= "<td rowspan=\"2\">"."<button type=\"button\" onclick=\"xajax_asignarArticuloSustituto('".$row['id_articulo']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
				$htmlTb .= sprintf("<td rowspan=\"2\" style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "100%",
					elimCaracter(utf8_encode($row['codigo_articulo']),";"));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode(substr($row['descripcion'],0,40)."..."));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloSustituto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloSustituto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticuloSustituto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloSustituto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloSustituto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"3\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArtSust","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "58%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaGasto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modo_gasto IN (1)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		gasto.id_gasto,
		gasto.nombre,
		gasto.estatus_iva,
		gasto.id_modo_gasto,
		gasto.afecta_documento,
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
		$htmlTh .= ordenarCampo("xajax_listaGasto", "60%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "20%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "20%", $pageNum, "afecta_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Afecta Cuenta por Pagar");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$afectaCuentaPorPagar = ($row['afecta_documento'] == 1) ? "Si" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarGasto%s\" onclick=\"validarInsertarGasto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_gasto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".(($row['iva'] > 0) ? number_format($row['iva'], 2, ".", ",") : "-")."</td>";
			$htmlTb .= "<td align=\"center\">".($afectaCuentaPorPagar)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"4\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaGasto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaGasto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaGastoImportacion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modo_gasto IN (3)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		gasto.id_gasto,
		gasto.nombre,
		gasto.estatus_iva,
		gasto.id_modo_gasto,
		gasto.afecta_documento,
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
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "60%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "20%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaGastoImportacion", "20%", $pageNum, "afecta_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Afecta Cuenta por Pagar");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$afectaCuentaPorPagar = ($row['afecta_documento'] == 1) ? "Si" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarGastoImportacion%s\" onclick=\"validarInsertarGastoImportacion('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_gasto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['iva'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"center\">".($afectaCuentaPorPagar)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"4\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaGastoImportacion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CP'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ingreso_egreso LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(descripcion LIKE %s
		OR id_motivo LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "8%", $pageNum, "id_motivo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "74%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "14%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Módulo"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "14%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Transacción"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['modulo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['ingreso_egreso'])."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaOtroCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
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
		$htmlTh .= ordenarCampo("xajax_listaOtroCargo", "85%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaOtroCargo", "15%", $pageNum, "asocia_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Asociar Documento");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$documentoAsociado = ($row['asocia_documento'] == 1) ? "Si" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarOtroCargo%s\" onclick=\"validarInsertarOtroCargo('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtroCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtroCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaOtroCargo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtroCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtroCargo(%s,'%s','%s','%s',%s);\">%s</a>",
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

function listaPais($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_origen LIKE %s
		OR des_origen LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_origen %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaPais", "8%", $pageNum, "id_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaPais", "46%", $pageNum, "nom_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaPais", "46%", $pageNum, "des_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarPais('".$row['id_origen']."','".$valCadBusq[1]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_origen'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_origen'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_origen'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPais(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPais(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPais(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPais(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPais(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPais","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPedidoCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 4, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_comp.estatus_pedido_compra IN (2)");
			
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("ped_comp.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
		}
			
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("ped_comp.id_pedido_compra_referencia LIKE %s",
				valTpDato("%".$valCadBusq[1]."%", "text"));
		}
		
		$queryPedido = sprintf("SELECT * FROM iv_pedido_compra ped_comp %s", $sqlBusq);
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPedido = mysql_num_rows($rsPedido);
		$rowPedido = mysql_fetch_assoc($rsPedido);
	}
	
	if ($totalRowsPedido == 0) {
		$objResponse->script("
		byId('trListaPedidoCompra').style.display = '';
		byId('trArticulosPedido').style.display = 'none';");
		
		if (!($totalRows > 0)) {
			$htmlTb .= "<td colspan=\"8\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		}
		
		$objResponse->assign("divListaPedidoCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		$objResponse->assign("divArticulosPedido","innerHTML","");
	} else if ($totalRowsPedido >= 1) {
		$query = sprintf("SELECT 
			orden_comp.id_orden_compra,
			ped_comp.id_pedido_compra,
			ped_comp.fecha,
			ped_comp.id_pedido_compra_propio,
			tipo_ped_comp.id_tipo_pedido_compra,
			tipo_ped_comp.tipo_pedido_compra,
			ped_comp.id_pedido_compra_referencia,
			prov.id_proveedor,
			CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
			prov.nombre,
			
			(SELECT COUNT(ped_det.id_pedido_compra) AS items
			FROM iv_pedido_compra_detalle ped_det
			WHERE ped_det.id_pedido_compra = ped_comp.id_pedido_compra) AS items,
			
			(SELECT SUM(ped_det.cantidad) AS pedidos
			FROM iv_pedido_compra_detalle ped_det
			WHERE ped_det.id_pedido_compra = ped_comp.id_pedido_compra) AS pedidos,
			
			(SELECT SUM(ped_det.pendiente) AS pendientes
			FROM iv_pedido_compra_detalle ped_det
			WHERE (ped_det.id_pedido_compra = ped_comp.id_pedido_compra)
				AND ped_det.estatus <> 2) AS pendientes,
			
			(IFNULL(ped_comp.subtotal, 0)
				- IFNULL(ped_comp.subtotal_descuento, 0)
				+ IFNULL((SELECT SUM(ped_gasto.monto) AS total_gasto
						FROM iv_pedido_compra_gasto ped_gasto
						WHERE ped_gasto.id_pedido_compra = ped_comp.id_pedido_compra), 0)
				+ IFNULL((SELECT SUM(ped_iva.subtotal_iva) AS total_iva
						FROM iv_pedido_compra_iva ped_iva
						WHERE ped_iva.id_pedido_compra = ped_comp.id_pedido_compra), 0)) AS total,
			
			IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
			
			ped_comp.estatus_pedido_compra
		FROM iv_pedido_compra ped_comp
			INNER JOIN iv_tipo_pedido_compra tipo_ped_comp ON (ped_comp.id_tipo_pedido_compra = tipo_ped_comp.id_tipo_pedido_compra)
			INNER JOIN cp_proveedor prov ON (ped_comp.id_proveedor = prov.id_proveedor)
			INNER JOIN pg_monedas moneda_local ON (ped_comp.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (ped_comp.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
			INNER JOIN iv_orden_compra orden_comp ON (ped_comp.id_pedido_compra = orden_comp.id_pedido_compra) %s", $sqlBusq);
		
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
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td></td>";
			$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
			$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "id_pedido_compra_propio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
			$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "22%", $pageNum, "tipo_pedido_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pedido");
			$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "14%", $pageNum, "id_pedido_compra_referencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
			$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "30%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
			$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
			$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Orden");
		$htmlTh .= "</tr>";
		
		while ($row = mysql_fetch_assoc($rsLimit)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcIvaArt = ($row['id_iva'] != "" && $row['id_iva'] != "0") ? $row['iva'] : "-";
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_listaArticuloPedido(0,'id_pedido_compra_detalle','ASC','".$row['id_pedido_compra']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
				$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha']))."</td>";
				$htmlTb .= "<td align=\"right\">".$row['id_pedido_compra_propio']."</td>";
				$htmlTb .= "<td>".utf8_encode($row['tipo_pedido_compra'])."</td>";
				$htmlTb .= "<td align=\"right\">".$row['id_pedido_compra_referencia']."</td>";
				$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
				$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda']."</td>";
			$htmlTb .= "</tr>";
		}
		
		$htmlTf = "<tr>";
			$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
								$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
									0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
							}
							$htmlTf .= "</td>";
							$htmlTf .= "<td width=\"25\">";
							if ($pageNum > 0) { 
								$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
									max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
							}
							$htmlTf .= "</td>";
							$htmlTf .= "<td width=\"100\">";
							
								$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s)\">",
									"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
								for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
								}
								$htmlTf .= "</select>";
								
							$htmlTf .= "</td>";
							$htmlTf .= "<td width=\"25\">";
							if ($pageNum < $totalPages) {
								$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
									min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
							}
							$htmlTf .= "</td>";
							$htmlTf .= "<td width=\"25\">";
							if ($pageNum < $totalPages) {
								$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
		$htmlTblFin .= "</table>";
		
		if (!($totalRows > 0)) {
			$htmlTb .= "<td colspan=\"8\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		}
		
		$objResponse->assign("divListaPedidoCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
		$objResponse->script("
		byId('trListaPedidoCompra').style.display = '';");
		
		if ($totalRowsPedido == 1) {
			$objResponse->loadCommands(listaArticuloPedido(0, "id_pedido_compra_detalle", "ASC", $rowPedido['id_pedido_compra']));
		} else if ($totalRowsPedido > 1) {
			$objResponse->script("
			byId('trArticulosPedido').style.display = 'none';");
			
			$objResponse->assign("divArticulosPedido","innerHTML","");
		}
	}
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."','".$valCadBusq[1]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaRegistroCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo = 3");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_fact.id_factura NOT IN (SELECT cxp_fact_gasto2.id_factura_compra_cargo
															FROM cp_factura_gasto cxp_fact_gasto2
																INNER JOIN cp_factura cxp_fact2 ON (cxp_fact_gasto2.id_factura = cxp_fact2.id_factura)
															WHERE cxp_fact2.activa IS NOT NULL
																AND cxp_fact_gasto2.id_factura_compra_cargo IS NOT NULL)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_fact.numero_control_factura LIKE %s
		OR cxp_fact.numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxp_fact.id_factura,
		cxp_fact.fecha_origen,
		cxp_fact.numero_factura_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1)
						AND cxp_fact_gasto.afecta_documento IN (1)), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva cxp_fact_iva
					WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)
		) AS total,
		
		moneda_local.abreviacion AS abreviacion_moneda,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact.id_factura
		LIMIT 1) AS idRetencionCabezera
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "12%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "20%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "54%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "14%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarRegistroCompra%s\" onclick=\"xajax_asignarFacturaCargo(xajax.getFormValues('frmListaRegistroCompra'), xajax.getFormValues('frmTotalDcto'), xajax.getFormValues('frmFacturaGasto'), '%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../compras/reportes/ga_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_view.png\" title=\"Ver\"/>",
					$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Comprobante de Retención\"/>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
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

$xajax->register(XAJAX_FUNCTION, "aprobarDcto");
$xajax->register(XAJAX_FUNCTION, "asignarADV");
$xajax->register(XAJAX_FUNCTION, "asignarAlmacen");
$xajax->register(XAJAX_FUNCTION, "asignarArticulo");
$xajax->register(XAJAX_FUNCTION, "asignarArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION, "asignarArticuloSustituto");
$xajax->register(XAJAX_FUNCTION, "asignarCliente");
$xajax->register(XAJAX_FUNCTION, "asignarFacturaCargo");
$xajax->register(XAJAX_FUNCTION, "asignarFechaRegistro");
$xajax->register(XAJAX_FUNCTION, "asignarMoneda");
$xajax->register(XAJAX_FUNCTION, "asignarMotivo");
$xajax->register(XAJAX_FUNCTION, "asignarPais");
$xajax->register(XAJAX_FUNCTION, "asignarProveedor");

$xajax->register(XAJAX_FUNCTION, "buscarArticuloSustituto");
$xajax->register(XAJAX_FUNCTION, "buscarCliente");
$xajax->register(XAJAX_FUNCTION, "buscarDisponibilidadUbicacion");
$xajax->register(XAJAX_FUNCTION, "buscarGasto");
$xajax->register(XAJAX_FUNCTION, "buscarGastoImportacion");
$xajax->register(XAJAX_FUNCTION, "buscarMotivo");
$xajax->register(XAJAX_FUNCTION, "buscarPais");
$xajax->register(XAJAX_FUNCTION, "buscarPedido");
$xajax->register(XAJAX_FUNCTION, "buscarProveedor");
$xajax->register(XAJAX_FUNCTION, "buscarRegistroCompra");

$xajax->register(XAJAX_FUNCTION, "calcularDcto");
$xajax->register(XAJAX_FUNCTION, "cargaLst");
$xajax->register(XAJAX_FUNCTION, "cargaLstArancelGrupo");
$xajax->register(XAJAX_FUNCTION, "cargaLstArancelGrupoBuscar");
$xajax->register(XAJAX_FUNCTION, "cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION, "cargaLstIva");
$xajax->register(XAJAX_FUNCTION, "cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION, "cargaLstRetencionImpuesto");
$xajax->register(XAJAX_FUNCTION, "cargaLstRetencionISLR");
$xajax->register(XAJAX_FUNCTION, "cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION, "cargaLstUbicacion");
$xajax->register(XAJAX_FUNCTION, "cargaLstGrupoItem");
$xajax->register(XAJAX_FUNCTION, "cargarDcto");
$xajax->register(XAJAX_FUNCTION, "cargarDetalleCosto");
$xajax->register(XAJAX_FUNCTION, "cargarFacturaCargo");

$xajax->register(XAJAX_FUNCTION, "editarArticulo");
$xajax->register(XAJAX_FUNCTION, "eliminarArticulo");
$xajax->register(XAJAX_FUNCTION, "eliminarGasto");
$xajax->register(XAJAX_FUNCTION, "eliminarOtroCargo");
$xajax->register(XAJAX_FUNCTION, "exportarRegistroCompra");

$xajax->register(XAJAX_FUNCTION, "formAlmacen");
$xajax->register(XAJAX_FUNCTION, "formArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION, "formArticuloMultiple");
$xajax->register(XAJAX_FUNCTION, "formDatosCliente");
$xajax->register(XAJAX_FUNCTION, "formImportar");
$xajax->register(XAJAX_FUNCTION, "formListadoArticulosPedido");

$xajax->register(XAJAX_FUNCTION, "guardarDcto");

$xajax->register(XAJAX_FUNCTION, "importarDcto");
$xajax->register(XAJAX_FUNCTION, "insertarArticulo");
$xajax->register(XAJAX_FUNCTION, "insertarArticuloMult");
$xajax->register(XAJAX_FUNCTION, "insertarGasto");
$xajax->register(XAJAX_FUNCTION, "insertarGastoImportacion");
$xajax->register(XAJAX_FUNCTION, "insertarOtroCargo");

$xajax->register(XAJAX_FUNCTION, "listaArticuloPedido");
$xajax->register(XAJAX_FUNCTION, "listaArticuloSustituto");
$xajax->register(XAJAX_FUNCTION, "listaCliente");
$xajax->register(XAJAX_FUNCTION, "listaGasto");
$xajax->register(XAJAX_FUNCTION, "listaGastoImportacion");
$xajax->register(XAJAX_FUNCTION, "listaMotivo");
$xajax->register(XAJAX_FUNCTION, "listaOtroCargo");
$xajax->register(XAJAX_FUNCTION, "listaPais");
$xajax->register(XAJAX_FUNCTION, "listaPedidoCompra");
$xajax->register(XAJAX_FUNCTION, "listaProveedor");
$xajax->register(XAJAX_FUNCTION, "listaRegistroCompra");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice=>$valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return null;
}

function cargaLstArancelGrupoItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "" : "class=\"inputCompleto\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"min-width:60px\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId != "" && doubleval($selId) == doubleval($row['porcentaje_grupo'])) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['porcentaje_grupo']."\">".number_format($row['porcentaje_grupo'], 2, ".", ",")."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function guardarDctoImportacion($idFactura, $frmDcto, $frmTotalDcto, $accionFactura) {
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjOtroCargo = $frmTotalDcto['cbxOtroCargo'];
	
	if ($accionFactura == "PREREGISTRO") {
		// VERIFICA SI LOS CARGOS ALMACENADOS EN LA BD EN LA FACTURA DE COMPRA AUN ESTAN AGREGADOS EN EL FORMULARIO
		$query = sprintf("SELECT * FROM iv_factura_compra_gasto cxp_fact_gasto
		WHERE cxp_fact_gasto.id_factura_compra = %s
			AND cxp_fact_gasto.id_modo_gasto = 2;",
			valTpDato($idFactura, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$existeDet = false;
			if (isset($arrayObjOtroCargo)) {
				foreach ($arrayObjOtroCargo as $indice => $valor) {
					if ($row['id_factura_compra_gasto'] == $frmTotalDcto['hddIdFacturaCompraGastoCargo'.$valor]) {
						$existeDet = true;
					}
				}
			}
			
			if ($existeDet == false) {
				$deleteSQL = sprintf("DELETE FROM iv_factura_compra_gasto WHERE id_factura_compra_gasto = %s;",
					valTpDato($row['id_factura_compra_gasto'], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
	}
	
	if (isset($arrayObjOtroCargo)) {
		foreach ($arrayObjOtroCargo as $indice => $valor) {
			$hddItmGasto = $valor;
			
			$hddIdGastoCargo = $frmTotalDcto['hddIdGastoCargo'.$hddItmGasto];
			
			// BUSCA LOS DATOS DEL CARGO
			$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
				valTpDato($hddIdGastoCargo, "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGastos = mysql_fetch_assoc($rsGasto);
			
			$lstAsociaDocumento = $rowGastos['asocia_documento'];
			$hddCondicionGastoCargo = $frmTotalDcto['hddCondicionGastoCargo'.$hddItmGasto]; // 1 = Real, 2 = Estimado
			$idFacturaCargo = $frmTotalDcto['hddIdFacturaCargo'.$hddItmGasto];
			
			$txtSubTotal = str_replace(",", "", $frmTotalDcto['hddSubTotalFacturaGastoCargo'.$hddItmGasto]);
			
			// INSERTA LOS CARGOS DE LA FACTURA
			if ($accionFactura == "PREREGISTRO") {
				if ($frmTotalDcto['hddIdFacturaCompraGastoCargo'.$hddItmGasto] > 0) {
					$updateSQL = sprintf("UPDATE iv_factura_compra_gasto SET
						id_gasto = %s,
						tipo = %s,
						porcentaje_monto = %s,
						monto = %s,
						monto_medida = %s,
						estatus_iva = %s,
						id_iva = %s,
						iva = %s,
						id_modo_gasto = %s,
						id_tipo_medida = %s,
						afecta_documento = %s,
						id_factura_compra_cargo = %s,
						id_condicion_gasto = %s
					WHERE id_factura_compra_gasto = %s;",
						valTpDato($frmTotalDcto['hddIdGastoCargo'.$hddItmGasto], "int"),
						valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
						valTpDato($frmTotalDcto['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
						valTpDato($txtSubTotal, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato(0, "boolean"), // 0 = No, 1 = Si
						valTpDato("", "int"),
						valTpDato(0, "real_inglesa"),
						valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
						valTpDato("", "int"), // 1 = Peso
						valTpDato(0, "int"), // 0 = No, 1 = Si
						valTpDato($idFacturaCargo, "int"),
						valTpDato($hddCondicionGastoCargo, "int"), // 1 = Real, 2 = Estimado;
						valTpDato($frmTotalDcto['hddIdFacturaCompraGastoCargo'.$hddItmGasto], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				} else {
					$insertSQL = sprintf("INSERT INTO iv_factura_compra_gasto (id_factura_compra, id_gasto, tipo, porcentaje_monto, monto, monto_medida, estatus_iva, id_iva, iva, id_modo_gasto, id_tipo_medida, afecta_documento, id_factura_compra_cargo, id_condicion_gasto)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($frmTotalDcto['hddIdGastoCargo'.$hddItmGasto], "int"),
						valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
						valTpDato($frmTotalDcto['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
						valTpDato($txtSubTotal, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato(0, "boolean"), // 0 = No, 1 = Si
						valTpDato("", "int"),
						valTpDato(0, "real_inglesa"),
						valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
						valTpDato("", "int"), // 1 = Peso
						valTpDato(0, "int"), // 0 = No, 1 = Si
						valTpDato($idFacturaCargo, "int"),
						valTpDato($hddCondicionGastoCargo, "int")); // 1 = Real, 2 = Estimado;
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			} else if ($accionFactura == "REGISTRO") {
				$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto, estatus_iva, id_iva, iva, id_modo_gasto, id_factura_compra_cargo, id_condicion_gasto)
				SELECT %s, id_gasto, %s, %s, %s, %s, %s, %s, id_modo_gasto, %s, %s FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idFactura, "int"),
					valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
					valTpDato($frmTotalDcto['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
					valTpDato($txtSubTotal, "real_inglesa"),
					valTpDato(0, "boolean"), // 0 = No, 1 = Si
					valTpDato("", "int"),
					valTpDato(0, "real_inglesa"),
					valTpDato($idFacturaCargo, "int"),
					valTpDato($hddCondicionGastoCargo, "int"), // 1 = Real, 2 = Estimado
					valTpDato($frmTotalDcto['hddIdGastoCargo'.$hddItmGasto], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	return array(true, "");
}

function insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, $idPedidoDetalle, $idFacturaDetalle, $idMonedaLocal, $idMonedaOrigen, $cantPedida = "", $cantRecibida = "", $costoUnitario = "", $hddIdArancelFamiliaItm = "", $lstTarifaAdValorem = "", $almacen = "", $ubicacion = "", $hddIdClienteItm = "") {
	$contFila++;
	
	if ($idPedidoDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle WHERE id_pedido_compra_detalle = %s;",
			valTpDato($idPedidoDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$queryPedidoDetImpuesto = sprintf("SELECT * FROM iv_pedido_compra_detalle_impuesto WHERE id_pedido_compra_detalle = %s;",
			valTpDato($idPedidoDetalle, "int"));
		$rsPedidoDetImpuesto = mysql_query($queryPedidoDetImpuesto);
		if (!$rsPedidoDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDetImpuesto = mysql_num_rows($rsPedidoDetImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowPedidoDetImpuesto = mysql_fetch_assoc($rsPedidoDetImpuesto)) {
			$arrayIdIvaItm[] = $rowPedidoDetImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
		
		$idPedComp = $rowPedidoDet['id_pedido_compra'];
		$idArticulo = $rowPedidoDet['id_articulo'];
		$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
		$cantRecibida = ($cantRecibida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['pendiente'] : $cantRecibida;
		$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $costoUnitario;
		$gastoUnitario = ($gastoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['gasto_unitario'] : $gastoUnitario;
		$pesoUnitario = ($pesoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['peso_unitario'] : $pesoUnitario;
		$hddIdArancelFamiliaItm = ($hddIdArancelFamiliaItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_arancel_familia'] : $hddIdArancelFamiliaItm;
		$lstTarifaAdValorem = ($lstTarifaAdValorem == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['porcentaje_grupo'] : $lstTarifaAdValorem;
		$lstTarifaAdValoremDif = ($lstTarifaAdValoremDif == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['porcentaje_grupo_diferencia'] : $lstTarifaAdValoremDif;
		$hddTipoItm = ($hddTipoItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['tipo'] : $hddTipoItm;
		$hddIdClienteItm = ($hddIdClienteItm == "" && $totalRowsPedidoDet > 0 && $ubicacion == "") ? $rowPedidoDet['id_cliente'] : $hddIdClienteItm;
		
		// BUSCA LOS DATOS DEL PEDIDO
		$queryPedido = sprintf("SELECT * FROM iv_pedido_compra WHERE id_pedido_compra = %s;",
			valTpDato($idPedComp, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		$idEmpresa = $rowPedido['id_empresa'];
		$numeroReferencia = $rowPedido['id_pedido_compra_referencia'];
		
		if (count($arrayObj) > 0) {
			if ($frmDcto['txtIdProv'] > 0 && $frmDcto['txtIdProv'] != $rowPedido['id_proveedor'] && $contFila > 1) {
				return array(false, "Solo puede agregar items de Pedidos del mismo Proveedor", $arrayObjUbicacion);
			}
		} else {
			$objResponse->loadCommands(asignarProveedor($rowPedido['id_proveedor'], "Prov", "false"));
			
			$queryEmpresa = sprintf("SELECT
				id_empresa_reg,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM vw_iv_empresas_sucursales vw_iv_emp_suc
			WHERE id_empresa_reg = %s;",
				valTpDato($idEmpresa, "int"));
			$rsEmpresa = mysql_query($queryEmpresa);
			if (!$rsEmpresa) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
			$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
			$objResponse->assign("txtIdEmpresa","value",utf8_encode($idEmpresa));
			$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa']));
			
			$idMonedaLocal = $rowPedido['id_moneda'];
			$idMonedaOrigen = ($rowPedido['id_moneda_tasa_cambio'] > 0) ? $rowPedido['id_moneda_tasa_cambio'] : $rowPedido['id_moneda'];
			
			$txtTasaCambio = ($rowPedido['monto_tasa_cambio'] >= 0) ? $rowPedido['monto_tasa_cambio'] : 0;
			$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
			$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
			$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);
			$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $rowPedido['id_tasa_cambio']));
			
			$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
			$objResponse->assign("txtSubTotalDescuento","value",number_format(0, 2, ".", ","));
			
			$objResponse->call("selectedOption","lstGastoItem",0);
			
			// VERIFICA SI LA FACTURA ES DE IMPORTACION
			$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
		}
	} else if ($idFacturaDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
		$queryPedidoDet = sprintf("SELECT
			vw_iv_ped_comp.id_empresa,
			cxp_fact_det.id_pedido_compra_detalle,
			cxp_fact_det.id_articulo,
			cxp_fact_det.cantidad,
			cxp_fact_det.pendiente,
			cxp_fact_det.precio_unitario,
			cxp_fact_det.gasto_unitario,
			cxp_fact_det.peso_unitario,
			cxp_fact_det.tipo_descuento,
			cxp_fact_det.porcentaje_descuento,
			cxp_fact_det.subtotal_descuento,
			cxp_fact_det.id_iva,
			cxp_fact_det.iva,
			cxp_fact_det.id_arancel_familia,
			cxp_fact_det.porcentaje_grupo,
			cxp_fact_det.porcentaje_grupo_diferencia,
			cxp_fact_det.tipo,
			cxp_fact_det.id_cliente,
			vw_iv_ped_comp.id_pedido_compra_referencia
		FROM vw_iv_pedidos_compra vw_iv_ped_comp
			INNER JOIN iv_factura_compra_detalle cxp_fact_det ON (vw_iv_ped_comp.id_pedido_compra = cxp_fact_det.id_pedido_compra)
		WHERE id_factura_compra_detalle = %s;",
			valTpDato($idFacturaDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$idEmpresa = $rowPedidoDet['id_empresa'];
		
		$idPedidoDetalle = $rowPedidoDet['id_pedido_compra_detalle'];
		$numeroReferencia = $rowPedidoDet['id_pedido_compra_referencia'];
		$idArticulo = ($idArticulo == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_articulo'] : $idArticulo;
		$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
		$cantRecibida = ($cantRecibida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['pendiente'] : $cantRecibida;
		$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $costoUnitario;
		$gastoUnitario = ($gastoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['gasto_unitario'] : $gastoUnitario;
		$pesoUnitario = ($pesoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['peso_unitario'] : $pesoUnitario;
		$hddTipoDescuentoItm = $rowPedidoDet['tipo_descuento'];
		$hddPorcDescuentoItm = $rowPedidoDet['porcentaje_descuento'];
		$hddMontoDescuentoItm = $rowPedidoDet['subtotal_descuento'];
		$hddIdArancelFamiliaItm = ($hddIdArancelFamiliaItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_arancel_familia'] : $hddIdArancelFamiliaItm;
		$lstTarifaAdValorem = ($lstTarifaAdValorem == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['porcentaje_grupo'] : $lstTarifaAdValorem;
		$lstTarifaAdValoremDif = ($lstTarifaAdValoremDif == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['porcentaje_grupo_diferencia'] : $lstTarifaAdValoremDif;
		$hddTipoItm = ($hddTipoItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['tipo'] : $hddTipoItm;
		$hddIdClienteItm = ($hddIdClienteItm == "" && $totalRowsPedidoDet > 0 && $ubicacion == "") ? $rowPedidoDet['id_cliente'] : $hddIdClienteItm;
		
		$queryPedidoDetImpuesto = sprintf("SELECT * FROM iv_factura_compra_detalle_impuesto WHERE id_factura_compra_detalle = %s;",
			valTpDato($idFacturaDetalle, "int"));
		$rsPedidoDetImpuesto = mysql_query($queryPedidoDetImpuesto);
		if (!$rsPedidoDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDetImpuesto = mysql_num_rows($rsPedidoDetImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowPedidoDetImpuesto = mysql_fetch_assoc($rsPedidoDetImpuesto)) {
			$arrayIdIvaItm[] = $rowPedidoDetImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	} else {
		$arrayIdIvaItm = array(-1);
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	}
	
	// VERIFICA LA CANTIDAD DE UBICACIONES QUE TIENE
	$queryUbicArt = sprintf("SELECT * FROM vw_iv_articulos_almacen
	WHERE id_empresa = %s
		AND id_articulo = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	$rsUbicArt = mysql_query($queryUbicArt);
	if (!$rsUbicArt) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
	$totalRowsUbicArt = mysql_num_rows($rsUbicArt);
	
	if ($almacen == "" && $ubicacion == "") {
		// BUSCA LA UBICACION PREDETERMINADA
		$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
		WHERE id_empresa = %s
			AND id_articulo = %s
			AND estatus_almacen_compra = 1
			AND casilla_predeterminada_compra = 1;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"));
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		$idCasilla = $rowArtAlm['id_casilla'];
		$ubicacion = $rowArtAlm['descripcion_almacen']."\n".$rowArtAlm['ubicacion'];
	} else {
		// BUSCA SI EL ARTICULO TIENE ASIGNADA LA UBICACION PARA LA COMPRA
		$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
		WHERE id_empresa = %s
			AND id_articulo = %s
			AND descripcion_almacen LIKE %s
			AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', '')
			AND estatus_almacen_compra = 1
			AND estatus_articulo_almacen = 1;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($almacen, "text"),
			valTpDato($ubicacion, "text"));
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		if ($totalRowsArtAlm > 0) {
			$idCasilla = $rowArtAlm['id_casilla'];
			$ubicacion = $rowArtAlm['descripcion_almacen']."\n".$rowArtAlm['ubicacion'];
		} else {
			// BUSCA LOS DATOS DE LA UBICACION PARA LA COMPRA
			$queryUbic = sprintf("SELECT * FROM vw_iv_casillas
			WHERE descripcion_almacen LIKE %s
				AND REPLACE(CONVERT(ubicacion USING utf8), '-[]', '') LIKE REPLACE(%s, '-[]', '')
				AND estatus_almacen_compra = 1;",
				valTpDato($almacen, "text"),
				valTpDato($ubicacion, "text"));
			$rsUbic = mysql_query($queryUbic);
			if (!$rsUbic) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
			$totalRowsUbic = mysql_num_rows($rsUbic);
			$rowUbic = mysql_fetch_assoc($rsUbic);
			
			$idCasilla = $rowUbic['id_casilla'];
			
			// VERIFICA SI ALGUN ARTICULO DE LA LISTA TIENE LA UBICACION YA OCUPADA
			$existe = false;
			if (isset($arrayObjUbicacion)) {
				foreach ($arrayObjUbicacion as $indice => $valor) {
					if ($arrayObjUbicacion[$indice][0] != $idArticulo && $arrayObjUbicacion[$indice][1] == $idCasilla) {
						$existe = true;
					}
				}
			}
			
			// VERIFICA SI ALGUN OTRO ARTICULO DE LA BASE DE DATOS TIENE LA UBICACION YA OCUPADA
			$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
			WHERE id_empresa = %s
				AND id_articulo <> %s
				AND descripcion_almacen LIKE %s
				AND REPLACE(ubicacion, '-[]', '') LIKE REPLACE(%s, '-[]', '')
				AND estatus_articulo_almacen = 1;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($almacen, "text"),
				valTpDato($ubicacion, "text"));
			$rsArtAlm = mysql_query($queryArtAlm);
			if (!$rsArtAlm) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
			$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
			
			if ($totalRowsArtAlm > 0)
				$existe = true;
			
			if ($existe == false) {
				$idCasilla = $rowUbic['id_casilla'];
				$ubicacion = $rowUbic['descripcion_almacen']."\n".$rowUbic['ubicacion'];
			} else {
				$totalRowsArtAlm = 0;
				$idCasilla = "";
				$ubicacion = "";
			}
		}
	}
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM iv_articulos art WHERE art.id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$pesoUnitario = ($idFacturaDetalle > 0) ? $pesoUnitario : $rowArticulo['peso_articulo'];
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (1,8,3)
		AND iva.idIva IN (%s);",
		valTpDato($hddIdIvaItm, "campo"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
	$contIva = 0;
	$ivaUnidad = "";
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
	
	if ((!($totalRowsPedidoDet > 0) && !($hddIdArancelFamiliaItm > 0) && $rowArticulo['id_arancel_familia'] > 0) || $hddIdArancelFamiliaItm == -1 || $hddIdArancelFamiliaItm == "") {
		$hddIdArancelFamiliaItm = $rowArticulo['id_arancel_familia'];
		$lstTarifaAdValorem = "";
	}
	
	// BUSCA LOS DATOS DEL ARANCEL
	$queryArancelFamilia = sprintf("SELECT 
		arancel_fam.id_arancel_familia,
		arancel_fam.id_arancel_grupo,
		arancel_fam.codigo_familia,
		arancel_fam.codigo_arancel,
		arancel_fam.descripcion_arancel,
		arancel_grupo.codigo_grupo,
		arancel_grupo.porcentaje_grupo
	FROM pg_arancel_familia arancel_fam
		INNER JOIN pg_arancel_grupo arancel_grupo ON (arancel_fam.id_arancel_grupo = arancel_grupo.id_arancel_grupo)
	WHERE arancel_fam.id_arancel_familia = %s;", 
		valTpDato($hddIdArancelFamiliaItm, "int"));
	$rsArancelFamilia = mysql_query($queryArancelFamilia);
	if (!$rsArancelFamilia) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
	
	$lstTarifaAdValorem = ((!($totalRowsPedidoDet > 0) && !($lstTarifaAdValorem > 0) && $rowArticulo['id_arancel_familia'] > 0) || $lstTarifaAdValorem == -1 || $lstTarifaAdValorem == "") ? $rowArancelFamilia['porcentaje_grupo'] : $lstTarifaAdValorem;
	
	$arancelArticulo = sprintf("<span class=\"textoNegroNegrita_10px\" title=\"%s\">%s</span>",
		utf8_encode($rowArancelFamilia['descripcion_arancel']),
		utf8_encode($rowArancelFamilia['codigo_arancel']));
	
	$htmlCostosArt = "<table width=\"400\">".
	"<tr id=\"trMsjDetallesCosto".$contFila."\" style=\"display:none\">".
		"<td colspan=\"3\">".
			"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">".
			"<tr>".
				"<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>".
				"<td align=\"center\">Opción válida solo para Compras de Importación</td>".
			"</tr>".
			"</table>".
		"</td>".
	"</tr>".
	"<tbody id=\"tbodyDetallesCosto".$contFila."\">".
	"<tr align=\"left\">"."<td><b>"."Costo Total FOB:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda1:".$contFila."\"></td>"."<td align=\"right\" id=\"tdPrecioTotalFOB".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Total Gastos:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda2:".$contFila."\"></td>"."<td align=\"right\" id=\"tdTotalGastos".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Total CIF:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda3:".$contFila."\"></td>"."<td align=\"right\" id=\"tdTotalCIF".$contFila."\"></td>".
	"</tr>".
	"<tr><td colspan=\"3\"><hr></td></tr>".
	"<tr align=\"left\">"."<td><b>"."Total CIF:."."</b></td>".
		"<td align=\"right\" id=\"tdMoneda4:".$contFila."\"></td>"."<td align=\"right\" id=\"tdTotalPrecioCIF".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Tarifa AdValorem (ADV):"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda5:".$contFila."\"></td>"."<td align=\"right\" id=\"tdTarifaAdValorem".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Total Gastos Importación Nacional:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda6:".$contFila."\"></td>"."<td align=\"right\" id=\"tdTotalGastosImportNacional".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Otros Cargos:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda7:".$contFila."\"></td>"."<td align=\"right\" id=\"tdOtrosCargos".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Costo Total:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda8:".$contFila."\"></td>"."<td align=\"right\" id=\"tdPrecioTotal".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Diferencia Cambiaria Total:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda9:".$contFila."\"></td>"."<td align=\"right\" id=\"tdDiferenciaCambiariaTotal".$contFila."\"></td>".
	"</tr>".
	"<tr><td colspan=\"3\"><hr></td></tr>".
	"<tr align=\"left\">"."<td><b>"."Costo Unitario:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda10:".$contFila."\"></td>"."<td align=\"right\" id=\"tdPrecioUnitario".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Diferencia Cambiaria Unit.:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda11:".$contFila."\"></td>"."<td align=\"right\" id=\"tdDiferenciaCambiariaUnit".$contFila."\"></td>".
	"</tr>".
	"<tr align=\"left\">"."<td><b>"."Total Costo Unitario:"."</b></td>".
		"<td align=\"right\" id=\"tdMoneda12:".$contFila."\"></td>"."<td align=\"right\" id=\"tdTotalPrecioUnitario".$contFila."\"></td>".
	"</tr>".
	"</tbody>".
	"</table>";
	
	if ($totalRowsUbicArt > 1) {
		$claseAlmacen = "trResaltar7";
	} else if (!($idCasilla > 0) && $totalRowsArtAlm == 0) {
		$claseAlmacen = "trResaltar6";
	}
	
	if ($hddIdClienteItm > 0 && $hddIdClienteItm != "") {
		$imgCliente = sprintf("<a class=\"modalImg\" id=\"aClienteItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_cliente.gif\" title=\"Ver Cliente\"/>",
			$contFila);
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><a class=\"modalImg\" id=\"aEditarItm%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a></td>".
			"<td>%s</td>".
			"<td class=\"%s\"><table><tr><td>".
				"<a class=\"modalImg\" id=\"aAlmacenItm%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_transferir_para_almacen.gif\" title=\"Ubicación\"/>".
				"</td><td id=\"spanUbicacion:%s\" align=\"center\" nowrap=\"nowrap\" width=\"%s\" title=\"spanUbicacion:%s\">%s</td></tr></table></td>".
			"<td id=\"tdCodigoArticuloItm%s\">%s</td>".
			"<td><div id=\"divDescripcionArticuloItm%s\">%s</div>%s</td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCantRecibItm%s\" name=\"txtCantRecibItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdCantPendItm%s\" align=\"right\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtCostoItm%s\" name=\"txtCostoItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPorcDescuentoItm%s\" name=\"hddPorcDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddMontoDescuentoItm%s\" name=\"hddMontoDescuentoItm%s\" value=\"%s\"/></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td>".
				"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"%s\">".
				"<tr>".
					"<td>%s</td>".
					"<td %s><a id=\"aAgregarTarifaAdValorem%s\"><img src=\"../img/iconos/add.png\" style=\"cursor:pointer\" title=\"Agregar\"/></a></td>".
				"</tr>".
				"<tr id=\"trlstTarifaAdValoremDif%s\" style=\"display:none\">".
					"<td>%s</td>".
					"<td><a id=\"aQuitarTarifaAdValorem%s\"><img src=\"../img/iconos/delete.png\" style=\"cursor:pointer\" title=\"Quitar\"/></a></td>".
				"</tr>".
				"</table>".
				"<input type=\"hidden\" id=\"hddIdArancelFamiliaItm%s\" name=\"hddIdArancelFamiliaItm%s\" class=\"inputSinFondo\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtPesoItm%s\" name=\"txtPesoItm%s\" class=\"inputSinFondo\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtGastosItm%s\" name=\"txtGastosItm%s\" class=\"inputSinFondo\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaDetItm%s\" name=\"hddIdFacturaDetItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDetItm%s\" name=\"hddIdPedidoDetItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloSustItm%s\" name=\"hddIdArticuloSustItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoDescuentoItm%s\" name=\"hddTipoDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTotalDescuentoItm%s\" name=\"hddTotalDescuentoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosImportNacItm%s\" name=\"hddGastosImportNacItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosImportItm%s\" name=\"hddGastosImportItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoItm%s\" name=\"hddTipoItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdClienteItm%s\" name=\"hddIdClienteItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasillaItm%s\" name=\"hddIdCasillaItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarItm%s').onclick = function() { abrirDivFlotante1(this, 'tblArticulo', '%s'); }
		byId('aAlmacenItm%s').onclick = function() { abrirDivFlotante1(this, 'tblAlmacen', '%s'); }
		byId('%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('aAgregarTarifaAdValorem%s').onclick = function() {
			seleccionarTarifaAdValorem('agregar', %s)
		}
		byId('aQuitarTarifaAdValorem%s').onclick = function() {
			seleccionarTarifaAdValorem('quitar', %s)
		}
		%s
		
		byId('txtPesoItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('txtGastosItm%s').onblur = function() {
			setFormatoRafk(this,3);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('txtTotalItm%s').onmouseover = function() { Tip('%s', TITLE, 'Detalle del Costo'); xajax_cargarDetalleCosto('%s', xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto')); }
		byId('txtTotalItm%s').onmouseout = function() { UnTip(); }",
		$contFila,
			$contFila, $contFila,
				$contFila,
			$contFila, $contFila,
			$contFila,
			$imgCliente,
			$claseAlmacen,
				$contFila,
				$contFila, "100%", $contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
			$contFila, elimCaracter($rowArticulo['codigo_articulo'],";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($rowArticulo['descripcion']))), $arancelArticulo,
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$contFila, $contFila, number_format($cantRecibida, 2, ".", ","),
			$contFila, number_format(($cantPedida - $cantRecibida), 2, ".", ","),
			utf8_encode($numeroReferencia),
			$contFila, $contFila, number_format($costoUnitario, 2, ".", ","),
				$contFila, $contFila, number_format($hddPorcDescuentoItm, 2, ".", ","),
				$contFila, $contFila, number_format($hddMontoDescuentoItm, 2, ".", ","),
			$contFila, $ivaUnidad,
			"100%",
					cargaLstArancelGrupoItm("lstTarifaAdValorem".$contFila, $lstTarifaAdValorem),
					((in_array(idArrayPais,array(1))) ? "" : "style=\"display:none\""),$contFila,
				$contFila,
					cargaLstArancelGrupoItm("lstTarifaAdValoremDif".$contFila, $lstTarifaAdValoremDif),
					$contFila,
				$contFila, $contFila, $hddIdArancelFamiliaItm,
			$contFila, $contFila, number_format($pesoUnitario, 2, ".", ","),
			$contFila, $contFila, number_format(($cantRecibida * $gastoUnitario), 3, ".", ","),
			$contFila, $contFila, number_format(($cantRecibida * $costoUnitario), 2, ".", ","),
				$contFila, $contFila, $idFacturaDetalle,
				$contFila, $contFila, $idPedidoDetalle,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, "",
				$contFila, $contFila, $hddTipoDescuentoItm,
				$contFila, $contFila, number_format(($cantRecibida * $hddMontoDescuentoItm), 2, ".", ","),
				$contFila, $contFila, number_format(0, 2, ".", ","),
				$contFila, $contFila, number_format(0, 2, ".", ","),
				$contFila, $contFila, $hddTipoItm, // 0 = Reposicion, 1 = Cliente
				$contFila, $contFila, $hddIdClienteItm,
				$contFila, $contFila, $idCasilla,
		
		$contFila, $contFila,
		$contFila, $contFila,
		"lstTarifaAdValorem".$contFila,
		"lstTarifaAdValoremDif".$contFila,
		$contFila,
			$contFila,
		$contFila,
			$contFila,
		(($lstTarifaAdValoremDif != 0) ? "seleccionarTarifaAdValorem('agregar', ".$contFila.");" : ""),
		
		$contFila,
		$contFila,
				
		$contFila, $htmlCostosArt, $contFila,
		$contFila);
	
	if ($hddIdClienteItm > 0) {
		$htmlItmPie .= sprintf("
		byId('aClienteItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblCliente', '%s'); }",
			$contFila, $hddIdClienteItm);
	}
	
	$arrayObjUbicacion[] = array(
		$idArticulo,
		$idCasilla);
	
	return array(true, $htmlItmPie, $contFila, $arrayObjUbicacion);
}

function insertarItemGasto($contFila, $hddIdGasto, $hddIdFacturaCompraGasto = "") {
	$contFila++;
	
	if ($hddIdFacturaCompraGasto > 0) {
		$queryPedidoDet = sprintf("SELECT cxp_fact_gasto.*
		FROM pg_iva iva
			RIGHT JOIN pg_gastos gasto ON (iva.idIva = gasto.id_iva)
			INNER JOIN iv_factura_compra_gasto cxp_fact_gasto ON (gasto.id_gasto = cxp_fact_gasto.id_gasto)
		WHERE cxp_fact_gasto.id_factura_compra_gasto = %s;", 
			valTpDato($hddIdFacturaCompraGasto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdGasto = $rowPedidoDet['id_gasto'];
		$txtPorcGasto = $rowPedidoDet['porcentaje_monto'];
		$txtMontoGasto = $rowPedidoDet['monto'];
		$txtMedidaGasto = $rowPedidoDet['monto_medida'];
		
		// BUSCA LOS IMPUESTOS DEL GASTO
		$queryPedidoDetImpuesto = sprintf("SELECT * FROM iv_factura_compra_gasto_impuesto WHERE id_factura_compra_gasto = %s;",
			valTpDato($hddIdFacturaCompraGasto, "int"));
		$rsPedidoDetImpuesto = mysql_query($queryPedidoDetImpuesto);
		if (!$rsPedidoDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDetImpuesto = mysql_num_rows($rsPedidoDetImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowPedidoDetImpuesto = mysql_fetch_assoc($rsPedidoDetImpuesto)) {
			$arrayIdIvaItm[] = $rowPedidoDetImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	} else {
		// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryGastoImpuesto = sprintf("SELECT gasto_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
  			INNER JOIN pg_gastos_impuesto gasto_impuesto ON (iva.idIva = gasto_impuesto.id_impuesto)
		WHERE iva.tipo IN (1,8,3) AND iva.estado = 1
			AND gasto_impuesto.id_gasto = %s
		ORDER BY iva;",
			valTpDato($hddIdGasto, "int"));
		$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
		if (!$rsGastoImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsGastoImpuesto = mysql_num_rows($rsGastoImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
			$arrayIdIvaItm[] = $rowGastoImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	}
	
	// BUSCA LOS DATOS DEL GASTO
	$queryGasto = sprintf("SELECT *
	FROM pg_gastos gasto
		LEFT JOIN pg_iva iva ON (gasto.id_iva = iva.idIva)
	WHERE id_gasto = %s;",
		valTpDato($hddIdGasto, "int"));
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowGasto = mysql_fetch_assoc($rsGasto);
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (1,8,3)
		AND iva.idIva IN (%s);",
		valTpDato($hddIdIvaItm, "campo"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contIva = 0;
	$ivaUnidad = "";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td><img id=\"imgIvaGasto%s:%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td><td width=\"%s\">".
		"<input type=\"text\" id=\"hddIvaGasto%s:%s\" name=\"hddIvaGasto%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaGasto%s:%s\" name=\"hddIdIvaGasto%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaGasto%s:%s\" name=\"hddLujoIvaGasto%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s:%s\" name=\"hddEstatusIvaGasto%s:%s\" value=\"%s\">".
		"<input id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
			"100%", $contFila, $contIva, "100%",
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	$hddIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIvaGasto : $rowGasto['iva'];
	$hddIdIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIdIvaGasto : $rowGasto['id_iva'];
	$hddEstatusIvaGasto = ($hddEstatusIvaGasto != "" && $totalRowsPedidoDet > 0) ? $hddEstatusIvaGasto : $rowGasto['estatus_iva'];
	
	$displayTblAfectaGasto = ($rowGasto['id_modo_gasto'] == 1 && $rowGasto['afecta_documento'] == 0) ? "" : "style=\"display:none\"";
	
	$htmlAfecta .= sprintf("<table id=\"tblAfectaGasto%s\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" %s width=\"%s\">",
		$contFila,
		$displayTblAfectaGasto,
		"100%");
	$htmlAfecta .= "<tr>";
		$htmlAfecta .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
	$htmlAfecta .= "</tr>";
	$htmlAfecta .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieGasto').before('".
		"<tr align=\"right\" id=\"trItmGasto:%s\">".
			"<td title=\"trItmGasto:%s\"><input id=\"cbxItmGasto\" name=\"cbxItmGasto[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"tituloCampo\">%s:</td>".
			"<td><input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\">%s</td>".
			"<td id=\"spnGastoMoneda%s\"></td>".
			"<td><input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td %s><input type=\"text\" id=\"txtMedidaGasto%s\" name=\"txtMedidaGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><div id=\"divIvaGasto%s\">%s</div>%s".
				"<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdModoGasto%s\" name=\"hddIdModoGasto%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdTipoMedida%s\" name=\"hddIdTipoMedida%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddTipoGasto%s\" name=\"hddTipoGasto%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdFacturaCompraGasto%s\" name=\"hddIdFacturaCompraGasto%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtMontoGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcGasto%s');
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('txtMedidaGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}",
		$contFila,
			$contFila, $contFila,
				$contFila,
			$rowGasto['nombre'],
			$contFila, $contFila, number_format($txtPorcGasto, 2, ".", ","), "%",
			$contFila,
			$contFila, $contFila, number_format($txtMontoGasto, 2, ".", ","),
			(($rowGasto['id_tipo_medida'] == 1) ? "title=\"Peso Total (g)\"" : ""), $contFila, $contFila, number_format($txtMedidaGasto, 2, ".", ","),
			$contFila, $ivaUnidad, $htmlAfecta,
				$contFila, $contFila, $hddIdGasto,
				$contFila, $contFila, $rowGasto['id_modo_gasto'],
				$contFila, $contFila, $rowGasto['id_tipo_medida'],
				$contFila, $contFila, 1,
				$contFila, $contFila, $hddIdFacturaCompraGasto,
		
		$contFila,
			$contFila,
		
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemGastoImportacion($contFila, $hddIdGasto, $hddIdFacturaCompraGasto = "") {
	$contFila++;
	
	if ($hddIdFacturaCompraGasto > 0) {
		$queryPedidoDet = sprintf("SELECT cxp_fact_gasto.*
		FROM pg_iva iva
			RIGHT JOIN pg_gastos gasto ON (iva.idIva = gasto.id_iva)
			INNER JOIN iv_factura_compra_gasto cxp_fact_gasto ON (gasto.id_gasto = cxp_fact_gasto.id_gasto)
		WHERE cxp_fact_gasto.id_factura_compra_gasto = %s;", 
			valTpDato($hddIdFacturaCompraGasto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdGasto = $rowPedidoDet['id_gasto'];
		$txtPorcGasto = $rowPedidoDet['porcentaje_monto'];
		$txtMontoGasto = $rowPedidoDet['monto'];
		
		// BUSCA LOS IMPUESTOS DEL GASTO
		$queryPedidoDetImpuesto = sprintf("SELECT * FROM iv_factura_compra_gasto_impuesto WHERE id_factura_compra_gasto = %s;",
			valTpDato($hddIdFacturaCompraGasto, "int"));
		$rsPedidoDetImpuesto = mysql_query($queryPedidoDetImpuesto);
		if (!$rsPedidoDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDetImpuesto = mysql_num_rows($rsPedidoDetImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowPedidoDetImpuesto = mysql_fetch_assoc($rsPedidoDetImpuesto)) {
			$arrayIdIvaItm[] = $rowPedidoDetImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	} else {
		// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryGastoImpuesto = sprintf("SELECT gasto_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
  			INNER JOIN pg_gastos_impuesto gasto_impuesto ON (iva.idIva = gasto_impuesto.id_impuesto)
		WHERE iva.tipo IN (1,8,3) AND iva.estado = 1
			AND gasto_impuesto.id_gasto = %s
		ORDER BY iva;",
			valTpDato($hddIdGasto, "int"));
		$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
		if (!$rsGastoImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsGastoImpuesto = mysql_num_rows($rsGastoImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
			$arrayIdIvaItm[] = $rowGastoImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	}
	
	// BUSCA LOS DATOS DEL GASTO
	$queryGasto = sprintf("SELECT *
	FROM pg_gastos gasto
		LEFT JOIN pg_iva iva ON (gasto.id_iva = iva.idIva)
	WHERE id_gasto = %s;",
		valTpDato($hddIdGasto, "int"));
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowGasto = mysql_fetch_assoc($rsGasto);
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (1,8,3)
		AND iva.idIva IN (%s);",
		valTpDato($hddIdIvaItm, "campo"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contIva = 0;
	$ivaUnidad = "";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td><img id=\"imgIvaGasto%s:%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td><td width=\"%s\">".
		"<input type=\"text\" id=\"hddIvaGasto%s:%s\" name=\"hddIvaGasto%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaGasto%s:%s\" name=\"hddIdIvaGasto%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaGasto%s:%s\" name=\"hddLujoIvaGasto%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s:%s\" name=\"hddEstatusIvaGasto%s:%s\" value=\"%s\">".
		"<input id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
			"100%", $contFila, $contIva, "100%",
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	$hddIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIvaGasto : $rowGasto['iva'];
	$hddIdIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIdIvaGasto : $rowGasto['id_iva'];
	$hddEstatusIvaGasto = ($totalRowsPedidoDet > 0) ? $hddEstatusIvaGasto : $rowGasto['estatus_iva'];
	
	$displayTblAfectaGasto = ($rowGasto['id_modo_gasto'] == 1 && $rowGasto['afecta_documento'] == 0) ? "" : "style=\"display:none\"";
	
	$htmlAfecta .= sprintf("<table id=\"tblAfectaGasto%s\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" %s width=\"%s\">",
		$contFila,
		$displayTblAfectaGasto,
		"100%");
	$htmlAfecta .= "<tr>";
		$htmlAfecta .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
	$htmlAfecta .= "</tr>";
	$htmlAfecta .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieGastoImportacion').before('".
		"<tr align=\"right\" id=\"trItmGasto:%s\">".
			"<td title=\"trItmGasto:%s\"><input id=\"cbxItmGasto\" name=\"cbxItmGasto[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"tituloCampo\">%s:</td>".
			"<td><input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\">%s</td>".
			"<td id=\"spnGastoMoneda%s\"></td>".
			"<td><input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td></td>".
			"<td><div id=\"divIvaGasto%s\">%s</div>%s".
				"<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdModoGasto%s\" name=\"hddIdModoGasto%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddTipoGasto%s\" name=\"hddTipoGasto%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdFacturaCompraGasto%s\" name=\"hddIdFacturaCompraGasto%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtMontoGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcGasto%s');
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}",
		$contFila,
			$contFila, $contFila,
				$contFila,
			$rowGasto['nombre'],
			$contFila, $contFila, number_format($txtPorcGasto, 2, ".", ","), "%",
			$contFila,
			$contFila, $contFila, number_format($txtMontoGasto, 2, ".", ","),
			$contFila, $ivaUnidad, $htmlAfecta,
				$contFila, $contFila, $hddIdGasto,
				$contFila, $contFila, $rowGasto['id_modo_gasto'],
				$contFila, $contFila, 1,
				$contFila, $contFila, $hddIdFacturaCompraGasto,
		
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemOtroCargo($contFila, $hddIdGastoCargo = "", $idFacturaCompraGasto = "") {
	$contFila++;
	
	if ($idFacturaCompraGasto > 0) {
		// BUSCA LOS DATOS DEL DETALLE DE LOS CARGOS DEL EXPEDIENTE
		$query = sprintf("SELECT
			cxp_fact_gasto.id_factura_compra_gasto,
			cxp_fact_cargo.id_factura,
			gasto.id_gasto,
			gasto.nombre,
			cxp_fact_cargo.fecha_origen,
			cxp_fact_cargo.numero_factura_proveedor,
			cxp_fact_cargo.numero_control_factura,
			prov.id_proveedor,
			prov.nombre AS nombre_proveedor,
			cxp_fact_gasto.monto,
			cxp_fact_gasto.id_condicion_gasto,
			(SELECT expediente_det_cargo.id_expediente FROM iv_expediente_detalle_cargos expediente_det_cargo
			WHERE expediente_det_cargo.id_gasto = gasto.id_gasto
				AND expediente_det_cargo.id_expediente = (SELECT exp_det_fact.id_expediente FROM iv_expediente_detalle_factura exp_det_fact
															WHERE exp_det_fact.id_factura_compra = cxp_fact_gasto.id_factura_compra)
				AND expediente_det_cargo.id_factura_compra_cargo = cxp_fact_cargo.id_factura) AS id_expediente
		FROM iv_factura_compra_gasto cxp_fact_gasto
			LEFT JOIN cp_factura cxp_fact_cargo ON (cxp_fact_gasto.id_factura_compra_cargo = cxp_fact_cargo.id_factura)
			INNER JOIN pg_gastos gasto ON (cxp_fact_gasto.id_gasto = gasto.id_gasto)
			LEFT JOIN cp_proveedor prov ON (cxp_fact_cargo.id_proveedor = prov.id_proveedor)
		WHERE cxp_fact_gasto.id_factura_compra_gasto = %s;",
			valTpDato($idFacturaCompraGasto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	}
	
	$txtFechaFacturaGasto = ($txtFechaFacturaGasto == "" && $totalRows > 0 && $row['fecha_origen'] != "") ? date(spanDateFormat, strtotime($row['fecha_origen'])) : $txtFechaFacturaGasto;
	$txtNumeroFacturaGasto = ($txtNumeroFacturaGasto == "" && $totalRows > 0) ? $row['numero_factura_proveedor'] : $txtNumeroFacturaGasto;
	$txtNumeroControlFacturaGasto = ($txtNumeroControlFacturaGasto == "" && $totalRows > 0) ? $row['numero_control_factura'] : $txtNumeroControlFacturaGasto;
	$txtIdProvFacturaGasto = ($txtIdProvFacturaGasto == "" && $totalRows > 0) ? $row['id_proveedor'] : $txtIdProvFacturaGasto;
	$txtNombreProvFacturaGasto = ($txtNombreProvFacturaGasto == "" && $totalRows > 0) ? $row['nombre_proveedor'] : $txtNombreProvFacturaGasto;
	$hddSubTotalFacturaGastoCargo = ($hddSubTotalFacturaGastoCargo == "" && $totalRows > 0) ? $row['monto'] : $hddSubTotalFacturaGastoCargo;
	$hddCondicionGastoCargo = ($hddCondicionGastoCargo == "" && $totalRows > 0) ? $row['id_condicion_gasto'] : $hddCondicionGastoCargo;
	$hddIdFacturaCargo = ($hddIdFacturaCargo == "" && $totalRows > 0) ? $row['id_factura'] : $hddIdFacturaCargo;
	$hddIdGastoCargo = ($hddIdGastoCargo == "" && $totalRows > 0) ? $row['id_gasto'] : $hddIdGastoCargo;
	$hddIdFacturaCompraGastoCargo = ($hddIdFacturaCompraGastoCargo == "" && $totalRows > 0) ? $row['id_factura_compra_gasto'] : $hddIdFacturaCompraGastoCargo;
	
	
	// BUSCA LOS DATOS DEL GASTO
	$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
		valTpDato($hddIdGastoCargo, "int"));
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
	$rowGastos = mysql_fetch_assoc($rsGasto);
	
	if (!($hddCondicionGastoCargo > 0)) {
		$hddCondicionGastoCargo = ($rowGastos['asocia_documento'] == 1) ? 1 : 2; // 1 = Real, 2 = Estimado
	}
	$cbxItmOtroCargo = ($row['id_expediente'] > 0) ? "" : sprintf("<input id=\"cbxItmOtroCargo\" name=\"cbxItmOtroCargo[]\" type=\"checkbox\" value=\"%s\"/>",
		$contFila);
	$display = ($row['id_expediente'] > 0) ? "style=\"display:none\"" : ""; 
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieOtroCargo').before('".
		"<tr id=\"trItmOtroCargo:%s\" align=\"left\" class=\"textoGris_11px\">".
			"<td title=\"trItmOtroCargo:%s\">%s".
				"<input id=\"cbxOtroCargo\" name=\"cbxOtroCargo[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>".
				"<a class=\"modalImg\" id=\"btnEditarOtroCargo:%s\" rel=\"#divFlotante1\" %s>".
					"<button type=\"button\" title=\"Editar\"><img src=\"../img/iconos/pencil.png\"/></button>".
				"</a>".
			"</td>".
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
			"<td><input type=\"text\" id=\"hddSubTotalFacturaGastoCargo%s\" name=\"hddSubTotalFacturaGastoCargo%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCondicionGastoCargo%s\" name=\"hddCondicionGastoCargo%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaCargo%s\" name=\"hddIdFacturaCargo%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdGastoCargo%s\" name=\"hddIdGastoCargo%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaCompraGastoCargo%s\" name=\"hddIdFacturaCompraGastoCargo%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('btnEditarOtroCargo:%s').onclick = function() {
			abrirDivFlotante1(this, 'tblFacturaOtroCargo', '%s');
		}",
		$contFila,
			$contFila, $cbxItmOtroCargo,
				$contFila,
			$contFila, $display,
			utf8_encode($rowGastos['nombre']),
			$contFila, $contFila, $txtFechaFacturaGasto,
			$contFila, $contFila, $txtNumeroFacturaGasto,
			$contFila, $contFila, $txtNumeroControlFacturaGasto,
				$contFila, $contFila, $txtIdProvFacturaGasto,
				$contFila, $contFila, $txtNombreProvFacturaGasto,
			$contFila, $contFila, number_format($hddSubTotalFacturaGastoCargo, 2, ".", ","),
				$contFila, $contFila, $hddCondicionGastoCargo,
				$contFila, $contFila, $hddIdFacturaCargo,
				$contFila, $contFila, $hddIdGastoCargo,
				$contFila, $contFila, $hddIdFacturaCompraGastoCargo,
		
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila, $arrayObjUbicacion);
}
?>