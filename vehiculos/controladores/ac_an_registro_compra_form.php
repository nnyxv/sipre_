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
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idFacturaCompra = $frmDcto['txtIdFactura'];
	$idNotaCargo = $frmDcto['txtIdNotaCargo'];
	$idExpediente = $frmTotalDcto['hddIdExpediente'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = str_replace(",", "", $frmDcto['txtTasaCambio']);
	
	if (($idModoCompra == 1 && !xvalidaAcceso($objResponse,"an_registro_compra_nacional","insertar"))
	|| ($idModoCompra == 2 && !xvalidaAcceso($objResponse,"an_registro_compra_importacion","insertar"))) { errorGuardarDcto($objResponse); return $objResponse; }
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig205 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 205 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig205 = mysql_query($queryConfig205);
	if (!$rsConfig205) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig205 = mysql_num_rows($rsConfig205);
	$rowConfig205 = mysql_fetch_assoc($rsConfig205);
	
	$valor = explode("|",$rowConfig205['valor']);
	
	$txtFechaRegistroCompra = date(spanDateFormat);
	$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
	if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
		if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
			if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
			|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
				$txtFechaRegistroCompra = $txtFechaProveedor;
			} else {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
			}
		} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
			$objResponse->script("byId('cbxFechaRegistro').checked = false;");
			$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
		} else {
			$txtFechaRegistroCompra = $txtFechaProveedor;
		}
	} else if ($frmDcto['cbxFechaRegistro'] == 1) {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
		errorGuardarDcto($objResponse);
		return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
	}
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script("
			byId('lstCondicionUnidad".$valor."').className = 'inputHabilitado';
			byId('txtFechaFabricacion".$valor."').className = 'inputHabilitado';
			byId('lstClase".$valor."').className = 'inputHabilitado';
			byId('lstUso".$valor."').className = 'inputHabilitado';
			byId('lstColorExterno".$valor."').className = 'inputHabilitado';
			byId('lstColorInterno".$valor."').className = 'inputHabilitado';
			byId('txtRegistroLegalizacion".$valor."').className = 'inputHabilitado';
			byId('txtRegistroFederal".$valor."').className = 'inputHabilitado';
			byId('txtCostoItm".$valor."').className = 'inputCompleto';");
			
			$hddIdUnidadBasicaItm = $frmListaArticulo['hddIdUnidadBasicaItm'.$valor];
			
			$hddIdPedidoDetalleItm = $frmListaArticulo['hddIdPedidoDetalleItm'.$valor];
			$hddIdFacturaDetalleItm = $frmListaArticulo['hddIdFacturaDetalleItm'.$valor];
			
			if ($hddIdUnidadBasicaItm > 0) {
				if (!($frmListaArticulo['lstCondicionUnidad'.$valor] > 0)) { $arrayInvalido[] = "lstCondicionUnidad".$valor; }
				if (!(strlen($frmListaArticulo['txtFechaFabricacion'.$valor]) > 0)) { $arrayInvalido[] = "txtFechaFabricacion".$valor; }
				if (!($frmListaArticulo['lstClase'.$valor] > 0)) { $arrayInvalido[] = "lstClase".$valor; }
				if (!($frmListaArticulo['lstUso'.$valor] > 0)) { $arrayInvalido[] = "lstUso".$valor; }
				if (!($frmListaArticulo['lstColorExterno'.$valor] > 0)) { $arrayInvalido[] = "lstColorExterno".$valor; }
				if (!($frmListaArticulo['lstColorInterno'.$valor] > 0)) { $arrayInvalido[] = "lstColorInterno".$valor; }
				if (!(strlen($frmListaArticulo['txtRegistroLegalizacion'.$valor]) > 0)) { $arrayInvalido[] = "txtRegistroLegalizacion".$valor; }
				if (!(strlen($frmListaArticulo['txtRegistroFederal'.$valor]) > 0)) { $arrayInvalido[] = "txtRegistroFederal".$valor; }
			}
			
			if (str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]) <= 0) { $arrayInvalido[] = "txtCostoItm".$valor; }
		}
	}
	
	if (isset($arrayInvalido)) {
		foreach ($arrayInvalido as $indice => $valor) {
			$objResponse->script("byId('".$valor."').className = 'inputErrado'");
		}
		
		if (count($arrayInvalido) > 0) {
			errorGuardarDcto($objResponse);
			return $objResponse->alert("Los campos señalados en rojo son invalidos");
		}
	}
	
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
			if ($frmTotalDcto['lstGastoItem'] == 0 && $frmTotalDcto['hddIdTipoMedida'.$valor] == 1 && $txtMedidaGasto > 0) { // 0 = No, 1 = Si // 1 = Peso
				if ($txtMedidaGasto != str_replace(",", "", $frmListaArticulo['txtTotalPesoItem'])) {
					errorGuardarDcto($objResponse); 
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
	if (!$rsProv) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
		errorGuardarDcto($objResponse);
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
	
	// INSERTA EL DETALLE DE LA FACTURA
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$hddIdUnidadBasicaItm = $frmListaArticulo['hddIdUnidadBasicaItm'.$valor];
			$hddIdAccesorioItm = $frmListaArticulo['hddIdAccesorioItm'.$valor];
			
			$hddIdPedidoDetalleItm = $frmListaArticulo['hddIdPedidoDetalleItm'.$valor];
			$hddIdFacturaDetalleItm = $frmListaArticulo['hddIdFacturaDetalleItm'.$valor];
			
			if ($hddIdUnidadBasicaItm > 0 || $hddIdAccesorioItm > 0) {
				$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
				$cantRecibida = $cantPedida;
				
				switch ($idModoCompra) {
					case 1 : // 1 = Nacional
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor];
						$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor];
					
						$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
						$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
						$txtGastosItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]) / $cantRecibida;
						$hddPorcDescuentoItm = str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$valor]);
						$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
						$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
						$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
						
						$costoUnitItmConGasto = $txtCostoItm + $txtGastosItm;
						
						// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
						$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal;
						
						$porcDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddPorcDescuentoItm : $txtDescuento; 
						$montoDescuentoUnitItm = ($subTotalDescuentoItm > 0) ? $hddMontoDescuentoItm : $totalDescuentoItm / $cantRecibida;
						
						$costoUnitItmFinal = $costoUnitItmConGasto - $montoDescuentoUnitItm;
						break;
					case 2 : // 2 = Importacion
						$hddIdIvaItm = "";
						$hddIvaItm = "";
						$txtGastosItm = str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]);
						$hddPorcDescuentoItm = str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$valor]);
						$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
						$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
						$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]);
						
						// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
						$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * ($txtSubTotalDescuento / $txtTasaCambio)) / ($txtSubTotal / $txtTasaCambio);
						
						$porcDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddPorcDescuentoItm : $txtDescuento; 
						$montoDescuentoUnitItm = ($subTotalDescuentoItm > 0) ? $hddMontoDescuentoItm : $totalDescuentoItm / $cantRecibida;
					
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
						$precioUnitario = $precioTotal / $cantRecibida;
						$diferenciaCambiariaUnit = ($totalCIF * str_replace(",", "", $frmTotalDcto['txtDiferenciaCambiaria'])) / $cantRecibida;
						$totalPrecioUnitario = $precioUnitario + $diferenciaCambiariaUnit;
						
						// TRANSFORMA AL TIPO DE MONEDA NACIONAL
						$txtCostoItm = (str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]) * $txtTasaCambio) + ($tarifaAdValorem / $cantRecibida);
						$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
						$txtGastosItm = ((str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]) / $cantRecibida) * $txtTasaCambio) + ($gastosImportNacItm / $cantRecibida);
						$hddPorcDescuentoItm = str_replace(",", "", $frmListaArticulo['hddPorcDescuentoItm'.$valor]);
						$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]) * $txtTasaCambio;
						$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]) * $txtTasaCambio;
						$txtTotalItm = str_replace(",", "", $frmListaArticulo['txtTotalItm'.$valor]) * $txtTasaCambio;
						
						$costoUnitItmConGasto = $txtCostoItm + $txtGastosItm;
						
						// VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
						$totalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal;
						
						$porcDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddPorcDescuentoItm : $txtDescuento; 
						$montoDescuentoUnitItm = ($subTotalDescuentoItm > 0) ? $hddMontoDescuentoItm : $totalDescuentoItm / $cantRecibida;
						
						$costoUnitItmFinal = $totalPrecioUnitario;
						break;
				}
				
				if ($hddIdUnidadBasicaItm > 0) {
					$hddIdUnidadFisicaItm = $frmListaArticulo['hddIdUnidadFisicaItm'.$valor];
					$txtSerialCarroceria = $frmListaArticulo['txtSerialCarroceria'.$valor];
					
					// REGISTRA EL DETALLE DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO cp_factura_detalle_unidad (id_factura, id_pedido_compra_detalle, id_unidad_basica, costo_unitario, id_cliente)
					VALUE (%s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($hddIdPedidoDetalleItm, "int"),
						valTpDato($hddIdUnidadBasicaItm, "int"),
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdClienteItm'.$valor], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$hddIdFacturaDetalleItm = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					if ($idModoCompra == 1) { // 1 = Nacional
						$costoUnitarioPedido = $txtCostoItm;
					} else if ($idModoCompra == 2) { // 2 = Importacion
						$costoUnitarioPedido = $precioTotalFOB / $cantRecibida;
						
						// REGISTRA EL DETALLE DE LA FACTURA
						$insertSQL = sprintf("INSERT INTO cp_factura_detalle_unidad_importacion (id_factura_detalle_unidad, id_arancel_familia, costo_unitario, gasto_unitario, gastos_import_nac_unitario, gastos_import_unitario, porcentaje_grupo)
						VALUE (%s, %s, %s, %s, %s, %s, %s);",
							valTpDato($hddIdFacturaDetalleItm, "int"),
							valTpDato($frmListaArticulo['hddIdArancelFamiliaItm'.$valor], "int"),
							valTpDato($precioTotalFOB / $cantRecibida, "real_inglesa"),
							valTpDato(str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]) / $cantRecibida, "real_inglesa"),
							valTpDato(str_replace(",", "", $frmListaArticulo['hddGastosImportNacItm'.$valor]) / $cantRecibida, "real_inglesa"),
							valTpDato(str_replace(",", "", $frmListaArticulo['hddGastosImportItm'.$valor]) / $cantRecibida, "real_inglesa"),
							valTpDato($frmListaArticulo['lstTarifaAdValorem'.$valor], "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) {
							errorGuardarDcto($objResponse);
							if (mysql_errno() == 1048) {
								return $objResponse->alert("Verifique que los items tengan asignado su código arancelario"."\n\nLine: ".__LINE__);
							} else {
								return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							}
						}
						mysql_query("SET NAMES 'latin1';");
					}
					
					// REGISTRA EL MOVIMIENTO KARDEX DE LA UNIDAD
					$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($hddIdUnidadBasicaItm, "int"),
						valTpDato($hddIdUnidadFisicaItm, "int"),
						valTpDato(1, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($frmDcto['lstClaveMovimiento'], "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($costoUnitItmConGasto, "real_inglesa"),
						valTpDato($costoUnitItmConGasto, "real_inglesa"),
						valTpDato(($otrosCargos / $cantRecibida), "real_inglesa"),
						valTpDato($porcDescuentoItm, "real_inglesa"),
						valTpDato($montoDescuentoUnitItm, "real_inglesa"),
						valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
						valTpDato("NOW()", "campo"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL COSTO DE COMPRA DE LA UNIDAD
					$updateSQL = sprintf("UPDATE an_uni_bas SET
						pvp_costo = %s
					WHERE id_uni_bas = %s;",
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato($hddIdUnidadBasicaItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL ESTATUS DE COMPRA DE LA UNIDAD FISICA
					$updateSQL = sprintf("UPDATE an_unidad_fisica SET
						estado_compra = %s
					WHERE id_unidad_fisica = %s;",
						valTpDato("REGISTRADO", "text"), // 1 = ALTA, 2 = IMPRESO, 3 = COMPRADO, 4 = REGISTRADO, 5 = CANCELADO
						valTpDato($hddIdUnidadFisicaItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL ESTATUS DE VENTA DE LA UNIDAD FISICA
					$updateSQL = sprintf("UPDATE an_unidad_fisica SET
						estado_venta = %s
					WHERE id_unidad_fisica = %s
						AND estado_venta IN ('POR REGISTRAR');",
						valTpDato("DISPONIBLE", "text"), // 1 = TRANSITO, 2 = POR REGISTRAR, 3 = SINIESTRADO, 4 = DISPONIBLE, 5 = RESERVADO, 6 = VENDIDO, 7 = ENTREGADO, 8 = PRESTADO, 9 = ACTIVO FIJO, 10 = INTERCAMBIO, 11 = DEVUELTO, 12 = ERROR EN TRASPASO
						valTpDato($hddIdUnidadFisicaItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA LA OBSERVACION DE LA NOTA DE DEBITO AGREGANDOLE EL SERIAL DE CHASIS DE LA UNIDAD FISICA
					$updateSQL = sprintf("UPDATE cp_notadecargo SET
						observacion_notacargo = REPLACE(observacion_notacargo, (SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
																				WHERE uni_fis.id_unidad_fisica = %s), %s)
					WHERE id_notacargo = %s;",
						valTpDato($hddIdUnidadFisicaItm, "int"),
						valTpDato($frmListaArticulo['txtSerialCarroceria'.$valor], "text"),
						valTpDato($idNotaCargo, "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// GUARDA LOS DATOS DE LA UNIDAD FISICA
					$Result1 = guardarUnidadFisica($hddIdUnidadFisicaItm, $hddIdFacturaDetalleItm, $hddIdFacturaDetalleItm, $valor, $frmDcto, $frmListaArticulo, $frmTotalDcto);
					if ($Result1[0] != true) {
						errorGuardarDcto($objResponse);
						return $objResponse->alert($Result1[1]);
					}
				} else if ($hddIdAccesorioItm > 0) {
					// REGISTRA EL DETALLE DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO cp_factura_detalle_accesorio (id_factura, id_pedido_compra_detalle, id_accesorio, cantidad, costo_unitario)
					VALUE (%s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($hddIdPedidoDetalleItm, "int"),
						valTpDato($hddIdAccesorioItm, "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($txtCostoItm, "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$hddIdFacturaDetalleItm = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					if (isset($arrayObjIvaItm)) {
						foreach ($arrayObjIvaItm as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							
							if ($valor1[0] == $valor) {
								$updateSQL = sprintf("UPDATE cp_factura_detalle_accesorio SET
									id_iva = %s,
									iva = %s
								WHERE id_factura_detalle_accesorio = %s;",
									valTpDato($frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]], "int"),
									valTpDato($frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]], "real_inglesa"),
									valTpDato($hddIdFacturaDetalleItm, "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
								
								$hddIvaItm = str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]]);
							}
						}
					}
			
					if ($idModoCompra == 1) { // 1 = Nacional
						$costoUnitarioPedido = $txtCostoItm;
					} else if ($idModoCompra == 2) { // 2 = Importacion
						$costoUnitarioPedido = $precioTotalFOB / $cantRecibida;
						
						// REGISTRA EL DETALLE DE LA FACTURA
						$insertSQL = sprintf("INSERT INTO cp_factura_detalle_accesorio_importacion (id_factura_detalle_accesorio, id_arancel_familia, costo_unitario, gasto_unitario, gastos_import_nac_unitario, gastos_import_unitario, porcentaje_grupo)
						VALUE (%s, %s, %s, %s, %s, %s, %s);",
							valTpDato($hddIdFacturaDetalleItm, "int"),
							valTpDato($frmListaArticulo['hddIdArancelFamiliaItm'.$valor], "int"),
							valTpDato($precioTotalFOB / $cantRecibida, "real_inglesa"),
							valTpDato(str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]) / $cantRecibida, "real_inglesa"),
							valTpDato(str_replace(",", "", $frmListaArticulo['hddGastosImportNacItm'.$valor]) / $cantRecibida, "real_inglesa"),
							valTpDato(str_replace(",", "", $frmListaArticulo['hddGastosImportItm'.$valor]) / $cantRecibida, "real_inglesa"),
							valTpDato($frmListaArticulo['lstTarifaAdValorem'.$valor], "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
					
					$insertSQL = sprintf("INSERT INTO an_partida (id_unidad_fisica, id_factura_compra, tipo_partida, tipo_registro, operador, id_tabla_tipo_partida, id_accesorio, precio_partida, costo_partida, iva_partida, clave_iva_partida, porcentaje_iva_partida, cantidad)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($hddIdUnidadFisicaItm, "int"),
						valTpDato($idFactura, "int"),
						valTpDato("COMPRA", "text"),
						valTpDato("ACCESORIO", "text"),
						valTpDato("NORMAL", "text"),
						valTpDato($hddIdAccesorioItm, "int"),
						valTpDato($hddIdAccesorioItm, "int"),
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato((($hddIvaItm > 0) ? 1 : 0), "boolean"), // 0 = No, 1 = Si
						valTpDato(0, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($cantRecibida, "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	// INSERTA LOS GASTOS DEL PEDIDO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$hddIdGasto = $frmTotalDcto['hddIdGasto'.$valor];
			$hddIdModoGasto = $frmTotalDcto['hddIdModoGasto'.$valor];
			
			if ($idModoCompra == 2 && $hddIdModoGasto == 1) { // 2 = Importacion && 1 = Gastos
				$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]) * $txtTasaCambio;
				$txtPorcGasto = ($txtMontoGasto * 100) / $txtSubTotal;
			} else {
				$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
				$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
			}
			$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
			
			if ($txtMontoGasto != 0) {
				$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto, monto_medida, id_iva, iva, id_modo_gasto, id_tipo_medida)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($hddIdGasto, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($txtMedidaGasto, "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"),
					valTpDato($frmTotalDcto['hddIvaGasto'.$valor], "real_inglesa"),
					valTpDato($hddIdModoGasto, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos por Importacion
					valTpDato($frmTotalDcto['hddIdTipoMedida'.$valor], "int")); // 1 = Peso
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
		if (!$rsNumeracion) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
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
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idRetencionCabezera = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$porcRetencion = doubleval($frmTotalDcto['lstRetencionImpuesto']);
		
		$comprasSinIva = $txtTotalExento + $txtTotalExonerado;
	} else if ($frmTotalDcto['lstRetencionImpuesto'] > 0
	&& $txtTotalExento + $txtTotalExonerado == $txtTotalOrden) {
		errorGuardarDcto($objResponse);
		return $objResponse->alert("Este Registro No Posee Impuesto(s) para Aplicar(les) Retención, Por Favor Verifique la Opción de Retención Seleccionada");
	}
	
	switch ($idModoCompra) {
		case 1 : // 1 = Nacional
			// INSERTA LOS IMPUESTOS DEL PEDIDO
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
						if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
							if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
							$Result1 = guardarNotaCreditoPago($idFactura, $idNotaCargo, $frmTotalDcto['txtIdMotivo'], $idRetencionCabezera, $frmTotalDcto['txtIva'.$valor], $porcRetencion, $ivaRetenido);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) {
								errorGuardarDcto($objResponse);
								return $objResponse->alert($Result1[1]);
							} else if ($Result1[0] == true) {
								$arrayIdDctoContabilidad[] = array(
									$Result1[1],
									$Result1[2],
									"NOTA CREDITO CXP");
							}
						}
					}
				}
			}
			
			break;
		case 2 : // 2 = Importacion
			// INSERTA LOS IMPUESTOS DEL PEDIDO
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
						if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
							if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
							$Result1 = guardarNotaCreditoPago($idFactura, $idNotaCargo, $frmTotalDcto['txtIdMotivo'], $idRetencionCabezera, $frmTotalDcto['txtIva'.$valor], $porcRetencion, $ivaRetenido);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) {
								errorGuardarDcto($objResponse);
								return $objResponse->alert($Result1[1]);
							} else if ($Result1[0] == true) {
								$arrayIdDctoContabilidad[] = array(
									$Result1[1],
									$Result1[2],
									"NOTA CREDITO CXP");
							}
						}
					}
				}
			}
			
			// ACTUALIZA EL EXPEDIENTE
			$updateSQL = sprintf("UPDATE an_expediente SET
				estatus = %s
			WHERE id_expediente = %s;",
				valTpDato(1, "int"), // 0 = Abierto, 1 = Cerrado
				valTpDato($idExpediente, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA LA RELACION FACTURA DE COMPRA EXPEDIENTE
			$updateSQL = sprintf("UPDATE an_expediente_detalle_factura SET
				id_factura = %s,
				id_factura_compra = NULL
			WHERE id_expediente = %s
				AND id_factura_compra = %s;",
				valTpDato($idFactura, "int"),
				valTpDato($idExpediente, "int"),
				valTpDato($idFacturaCompra, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO cp_factura_importacion (id_factura, nacionalizada, id_moneda_tasa_cambio, id_tasa_cambio, tasa_cambio, total_advalorem, id_actividad_importador, id_clase_importador, id_clase_solicitud, puerto_llegada, destino_final, compania_transportadora, id_proveedor_exportador, id_proveedor_consignatario, id_aduana, id_pais_origen, id_pais_compra, puerto_embarque, id_via_envio, tasa_cambio_diferencia, numero_embarque, porcentaje_seguro, numero_dcto_transporte, fecha_dcto_transporte, fecha_vencimiento_dcto_transporte, fecha_estimada_llegada, numero_planilla_importacion, numero_expediente) 
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato($frmDcto['lstNacionalizar'], "boolean"),
				valTpDato($frmTotalDcto['txtIdMonedaNegociacion'], "int"),
				valTpDato($frmDcto['lstTasaCambio'], "int"),
				valTpDato($txtTasaCambio, "real_inglesa"),
				valTpDato($frmTotalDcto['txtTotalAdValorem'], "real_inglesa"),
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
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// GUARDA LOS DATOS DE LAS FACTURA DE IMPORTACION
			$Result1 = guardarDctoImportacion($idFactura, $frmDcto, $frmTotalDcto, "REGISTRO");
			if ($Result1[0] != true) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
			
			// BUSCA LOS DATOS DE LA MONEDA NACIONAL
			$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
				valTpDato($idMonedaLocal, "int"));
			$rsMonedaLocal = mysql_query($queryMonedaLocal);
			if (!$rsMonedaLocal) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
			
			// SI LA MONEDA NACIONAL APLICA I.V.A ACTUALIZARA LOS ESTATUS DE IMPUESTO PARA EFECTO DE LA DECLARACION DEL IMPUESTO
			if ($rowMonedaLocal['incluir_impuestos'] == 1 && $frmDcto['lstNacionalizar'] == 1) { // 0 = No, 1 = Si
				// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva;");
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$updateSQL = sprintf("UPDATE cp_factura_detalle_accesorio SET
						id_iva = %s,
						iva = %s
					WHERE id_factura = %s;",
						valTpDato($rowIva['idIva'], "int"),
						valTpDato($rowIva['iva'], "real_inglesa"),
						valTpDato($idFactura, "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
					// INSERTA LOS IMPUESTOS A LOS GASTOS DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO cp_factura_gasto_impuesto (id_factura_gasto, id_impuesto, impuesto)
					SELECT id_factura_gasto, %s, %s FROM cp_factura_gasto
					WHERE id_factura = %s
						AND id_modo_gasto IN (1)
						AND id_factura_gasto NOT IN (SELECT id_factura_gasto FROM cp_factura_gasto_impuesto
														WHERE id_impuesto = %s);",
						valTpDato($rowIva['idIva'], "int"),
						valTpDato($rowIva['iva'], "int"),
						valTpDato($idFactura, "int"),
						valTpDato($rowIva['idIva'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
				
				$updateSQL = sprintf("UPDATE cp_factura SET
					monto_exento = (SELECT SUM(monto) FROM cp_factura_gasto
									WHERE id_factura = cp_factura.id_factura
										AND (iva <= 0 OR iva IS NULL)
										AND id_modo_gasto IN (1,3)),
					monto_exonerado = 0
				WHERE id_factura = %s;",
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			} else {
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
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
			
			break;
	}
	
	// ELIMINA LOS DATOS DE LA FACTURA DE COMPRA EN VEHICULOS
	$deleteSQL = sprintf("DELETE FROM an_factura_compra WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// INSERTA EL PAGO DE RETENCION ISLR
	if (str_replace(",","",$frmTotalDcto['txtTotalRetencionISLR']) > 0) {
		// BUSCA LOS DATOS DE LA RETENCION
		$queryRetencionISLR = sprintf("SELECT * FROM te_retenciones WHERE id = %s;",
			valTpDato($frmTotalDcto['lstRetencionISLR'], "int"));
		$rsRetencionISLR = mysql_query($queryRetencionISLR);
		if (!$rsRetencionISLR) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsRetencionISLR = mysql_num_rows($rsRetencionISLR);
		$rowRetencionISLR = mysql_fetch_assoc($rsRetencionISLR);
		
		if ($idNotaCargo > 0) {
			$insertSQL = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo_documento, tipo, fecha_registro)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idNotaCargo, "int"),
				valTpDato($frmTotalDcto['lstRetencionISLR'], "int"),
				valTpDato($frmTotalDcto['txtBaseImpISLR'], "real_inglesa"),
				valTpDato($rowRetencionISLR['sustraendo'], "real_inglesa"),
				valTpDato($rowRetencionISLR['porcentaje'], "real_inglesa"),
				valTpDato($frmTotalDcto['txtTotalRetencionISLR'], "real_inglesa"),
				valTpDato($rowRetencionISLR['codigo'], "real_inglesa"),
				valTpDato(2, "int"), // 0 = Cheque, 1 = Transferencia, 2 = Sin Documento
				valTpDato(1, "int"), // 0 = Factura, 1 = Nota de Cargo
				valTpDato(date("Y-m-d", strtotime($txtFechaRegistroCompra)), "date"));
		} else {
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
		}
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idISLR = mysql_insert_id();
		
		if ($idNotaCargo > 0) {
			$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, numero_documento, fecha_pago, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($idNotaCargo, "int"), 
				valTpDato("ND", "text"),
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
		} else {
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
		}
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	if ($idNotaCargo > 0) {
		$Result1 = guardarNotaCreditoPago($idFactura, $idNotaCargo, $frmTotalDcto['txtIdMotivoNCPlanMayor']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			errorGuardarDcto($objResponse);
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$arrayIdDctoContabilidad[] = array(
				$Result1[1],
				$Result1[2],
				"NOTA CREDITO CXP");
		}
		
		// ACTUALIZA EL NRO DE CONTROL DE LA NOTA DE DEBITO
		$updateSQL = sprintf("UPDATE cp_notadecargo nota_cargo SET
			numero_control_notacargo = %s
		WHERE nota_cargo.id_notacargo = %s;",
			valTpDato($txtSerialCarroceria, "text"),
			valTpDato($idNotaCargo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL SALDO DE LA NOTA DE CARGO
		$updateSQL = sprintf("UPDATE cp_notadecargo nota_cargo SET
			saldo_notacargo = saldo_notacargo - (SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE pago_dcto.id_documento_pago = nota_cargo.id_notacargo
													AND pago_dcto.tipo_documento_pago = 'ND'
													AND pago_dcto.estatus = 1)
		WHERE nota_cargo.id_notacargo = %s
			AND (SELECT COUNT(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
				WHERE pago_dcto.id_documento_pago = nota_cargo.id_notacargo
					AND pago_dcto.tipo_documento_pago = 'ND') > 0
			AND nota_cargo.estatus_notacargo IN (0,2);",
			valTpDato($idNotaCargo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// ACTUALIZA EL ESTADO DE LA NOTA DE CARGO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cp_notadecargo nota_cargo SET
			estatus_notacargo = (CASE
									WHEN saldo_notacargo = 0 THEN
										1
									WHEN (saldo_notacargo > 0 AND saldo_notacargo < (IFNULL(nota_cargo.subtotal_notacargo, 0)
																						- IFNULL(nota_cargo.subtotal_descuento_notacargo, 0)
																						+ IFNULL((SELECT SUM(nota_cargo_gasto.monto) AS total_gasto
																								FROM cp_notacargo_gastos nota_cargo_gasto
																								WHERE nota_cargo_gasto.id_notacargo = nota_cargo.id_notacargo), 0)
																						+ IFNULL((SELECT SUM(nota_cargo_iva.subtotal_iva) AS total_iva
																								FROM cp_notacargo_iva nota_cargo_iva
																								WHERE nota_cargo_iva.id_notacargo = nota_cargo.id_notacargo), 0)
																				)) THEN
										2
								END)
		WHERE id_notacargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	switch ($idModoCompra) {
		case 1 : // 1 = Nacional
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
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
			// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				estatus_factura = (CASE
									WHEN (ROUND(saldo_factura, 2) = 0) THEN
										1
									WHEN (ROUND(saldo_factura, 2) > 0
										AND ROUND(saldo_factura, 2) < ROUND(cxp_fact.total_cuenta_pagar, 2)) THEN
										2
									ELSE
										0
								END)
			WHERE id_factura = %s;",
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			break;
		case 2 : // 2 = Importacion
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
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
			// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				estatus_factura = (CASE
									WHEN (ROUND(saldo_factura, 2) = 0) THEN
										1
									WHEN (ROUND(saldo_factura, 2) > 0
										AND ROUND(saldo_factura, 2) < ROUND(cxp_fact.total_cuenta_pagar, 2)) THEN
										2
									ELSE
										0
								END)
			WHERE id_factura = %s;",
				valTpDato($idFactura, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			break;
	}
	
	// ACTUALIZA EL ESTATUS DEL PEDIDO SI NO EXISTEN UNIDADES PENDIENTES
	$updateSQL = sprintf("UPDATE an_pedido_compra ped_comp SET
		estatus_pedido = %s
	WHERE ped_comp.idPedidoCompra = %s
		AND (SELECT COUNT(*) AS cant_pendiente
			FROM an_solicitud_factura ped_comp_det
				LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
			WHERE ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra
				AND (ped_comp_det.estado IN (1,2,3,4)
					OR (ped_comp_det.estado IN (5) AND uni_fis.estado_compra IN ('COMPRADO')))) = 0;",
		valTpDato(3, "int"), // 0 = Forma Pago Sin Asignar, 1 = Forma Pago Asignado Parcial, 2 = Forma Pago Asignado, 3 = Facturado, 5 = Anulado
		valTpDato($idPedidoCompra, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdFactura","value",$idFactura);
	
	errorGuardarDcto($objResponse); 
	$objResponse->alert("Registro de Compra guardado con éxito.");
	
	$comprobanteRetencion = ($frmTotalDcto['lstRetencionImpuesto'] > 0) ? 1 : 0;
	
	$objResponse->script(sprintf("
	verVentana('reportes/an_registro_compra_pdf.php?valBusq=%s|%s', 900, 700);",
		$idFactura,
		$hddIdUnidadFisicaItm));
	
	if ($comprobanteRetencion == 1 && $idRetencionCabezera > 0) {
		$objResponse->script(sprintf("
		verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 900, 700);",
			$idRetencionCabezera));
	}
	
	if ($idISLR > 0) {
		$objResponse->script(sprintf("
		verVentana('../tesoreria/reportes/te_imprimir_constancia_retencion_pdf.php?id=%s&documento=3', 900, 700);",
			$idISLR));
	}
	
	$objResponse->script("
	byId('btnCancelar').click();");
	
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
			} else if ($tipoDcto == "NOTA CREDITO CXP") {
				$idNotaCredito = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasCreditoCpRe")) { generarNotasCreditoCpRe($idNotaCredito,"",""); } break;
					case 1 : if (function_exists("generarNotasCreditoCpSe")) { generarNotasCreditoCpSe($idNotaCredito,"",""); } break;
					case 2 : if (function_exists("generarNotasCreditoCpVe")) { generarNotasCreditoCpVe($idNotaCredito,"",""); } break;
					case 3 : if (function_exists("generarNotasCreditoCpAd")) { generarNotasCreditoCpAd($idNotaCredito,"",""); } break;
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
		$txtFechaFacturaGasto = date(spanDateFormat, strtotime($row['fecha_origen']));
		$txtIdProvFacturaGasto = $row['id_proveedor'];
		
		$hddSubTotalFacturaGasto = $row['subtotal_factura'] - $row['subtotal_descuento'];
	} else {
		$hddItmGasto = $frmFacturaGasto['hddItmGasto'];
		
		$hddSubTotalFacturaGasto = str_replace(",", "", $frmFacturaGasto['txtSubTotalFacturaGasto']);
	}
	
	$objResponse->assign("hddCondicionGasto".$hddItmGasto,"value",$frmFacturaGasto['lstCondicionGasto']); // 1 = Real, 2 = Estimado
	$objResponse->assign("hddIdFacturaCargo".$hddItmGasto,"value",$hddIdFacturaCargo);
	$objResponse->assign("txtNumeroFacturaGasto".$hddItmGasto,"value",$txtNumeroFacturaGasto);
	$objResponse->assign("txtNumeroControlFacturaGasto".$hddItmGasto,"value",$txtNumeroControlFacturaGasto);
	$objResponse->assign("txtFechaFacturaGasto".$hddItmGasto,"value",$txtFechaFacturaGasto);
	$objResponse->loadCommands(asignarProveedor($txtIdProvFacturaGasto, "ProvFacturaGasto".$hddItmGasto, "false"));
	
	$objResponse->assign("hddSubTotalFacturaGasto".$hddItmGasto,"value",number_format($hddSubTotalFacturaGasto, 2, ".", ","));
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarFacturaGasto').click();");
	}
	
	return $objResponse;
}

function asignarFechaRegistro($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig205 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 205 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig205 = mysql_query($queryConfig205);
	if (!$rsConfig205) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig205 = mysql_num_rows($rsConfig205);
	$rowConfig205 = mysql_fetch_assoc($rsConfig205);
	
	$valor = explode("|",$rowConfig205['valor']);
	
	$txtFechaProveedor = explode("-",date("Y-m-d",strtotime($frmDcto['txtFechaProveedor'])));
	if ($txtFechaProveedor[1] > 0 && $txtFechaProveedor[2] > 0 && $txtFechaProveedor[0] > 0) {
		if (checkdate($txtFechaProveedor[1], $txtFechaProveedor[2], $txtFechaProveedor[0])) { // EVALUA QUE LA FECHA EXISTA
			$txtFechaRegistroCompra = date(spanDateFormat);
			$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
			if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
				if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
					&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
					if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
					|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
						$txtFechaRegistroCompra = $txtFechaProveedor;
					} else {
						$objResponse->script("byId('cbxFechaRegistro').checked = false;");
						$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
					}
				} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
					&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
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
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	
	$objResponse->script("
	byId('tdNacionalizar').style.display = 'none';
	byId('tdlstNacionalizar').style.display = 'none';
	byId('trlstArancelGrupo').style.display = 'none';
	
	byId('fieldsetGastosImportacion').style.display = 'none';
	
	byId('trDatosImportacion').style.display = 'none';

	byId('btnAprobar').style.display = 'none';");
	
	if ($idModoCompra == 1) { // 1 = Nacional, 2 = Importacion
		$objResponse->assign("txtTasaCambio", "value", number_format(0, 3, ".", ","));
		if ($frmDcto['txtIdFactura'] > 0) {
			$objResponse->script("
			byId('btnAprobar').style.display = '';
			byId('btnCancelar').onclick = function () { window.open('an_registro_unidad_fisica_list.php', '_self'); }");
		} else {
			$objResponse->script("
	 		byId('btnCancelar').onclick = function () { window.open('an_registro_unidad_fisica_list.php', '_self'); }");
		}
	} else {
		if ($frmDcto['txtIdFactura'] > 0) {
			$objResponse->script("
			byId('fieldsetGastosImportacion').style.display = '';
			
			byId('tdNacionalizar').style.display = '';
			byId('tdlstNacionalizar').style.display = '';
			byId('trlstArancelGrupo').style.display = '';
			byId('trDatosImportacion').style.display = '';
			byId('btnAprobar').style.display = '';
			byId('btnCancelar').onclick = function () { window.open('an_registro_unidad_fisica_list.php', '_self'); }");
		} else {
			$objResponse->script("
	 		byId('btnCancelar').onclick = function () { window.open('an_registro_unidad_fisica_list.php', '_self'); }");
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
	
	// HABILITA O INHABILITA POR GASTO EL IMPUESTO DEPENDIENDO SI SE INCLUYE O NO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE pg_gastos.id_gasto = %s;", 
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
				$objResponse->script("byId('tblIvaGasto".$valor."').style.visibility = 'hidden';");
			} else if (($rowGasto['id_modo_gasto'] == 1 && $rowMonedaOrigen['incluir_impuestos'] == 1)	// 1 = Gastos && 1 = Si
			|| ($rowGasto['id_modo_gasto'] == 3 && $rowMonedaLocal['incluir_impuestos'] == 1)) {		// 3 = Gastos por Importacion && 1 = Si
				$objResponse->script("byId('tblIvaGasto".$valor."').style.visibility = '';");
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
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						if ($rowMonedaOrigen['incluir_impuestos'] == 1) { // 1 = Si
							$objResponse->script("byId('hddIvaItm".$valor.":".$valor1[1]."').style.visibility = '';");
							$objResponse->script("
							if (byId('hddIdIvaItm".$valor.":".$valor1[1]."').value > 0) {
								byId('hddEstatusIvaItm".$valor.":".$valor1[1]."').value = 1;
							}");
						} else if ($rowMonedaOrigen['incluir_impuestos'] == 0) { // 0 = No
							$objResponse->script("byId('hddIvaItm".$valor.":".$valor1[1]."').style.visibility = 'hidden';");
							$objResponse->script("
							if (byId('hddIdIvaItm".$valor.":".$valor1[1]."').value > 0) {
								byId('hddEstatusIvaItm".$valor.":".$valor1[1]."').value = 0;
							}");
						}
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

function asignarRetencionImpuesto($porcRetencion, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('txtIdMotivo').value = '';
	byId('txtMotivo').value = '';");
	
	$objResponse->script("
	byId('trMotivo').style.display = '".(($porcRetencion > 0 && $frmDcto['txtIdNotaCargo'] > 0) ? "" : "none")."';");
	
	return $objResponse;
}

function buscarAdicional($frmBuscarAdicional) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarAdicional['txtCriterioBuscarAdicional']);
	
	$objResponse->loadCommands(listaAdicional(0, "nom_accesorio", "ASC", $valBusq));
	
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
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscarMotivo['hddObjDestinoMotivo'],
		$frmBuscarMotivo['hddPagarCobrarMotivo'],
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

function buscarPaquete($frmBuscarPaquete) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarPaquete['txtCriterioBuscarPaquete']);
	
	$objResponse->loadCommands(listaPaquete(0, "nom_paquete", "ASC", $valBusq));
		
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
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
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
			
			if ($frmListaArticulo['hddIdUnidadBasicaItm'.$valor] > 0 || $frmListaArticulo['hddIdAccesorioItm'.$valor] > 0) {
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
						$hddSubTotalFacturaGasto = str_replace(",", "", $frmTotalDcto['hddSubTotalFacturaGasto'.$valor2]);
						$montoOtrosCargosItm = (($txtTotalItm - $hddTotalDescuentoItm) * $hddSubTotalFacturaGasto) / $txtSubTotal;
						
						$hddGastosImportItm += $montoOtrosCargosItm;
						
						$spnTotalOtrosCargos += $hddSubTotalFacturaGasto;
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
				
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;",
					valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaOrigen == 1) ? $frmTotalDcto['hddEstatusIvaGasto'.$valor] : 0;
					
					if ($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosSinIvaOrigen += $txtMontoGasto; break;
							default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
						}
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valor]) : $porcIva;
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
						
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosConIvaOrigen += $txtMontoGasto; break;
							default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
						}
					}
				}
				
				if ($totalRowsIva == 0) {
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
				
				$subTotalItmCambio = $totalPrecioCIF + $tarifaAdValorem;
				
				$txtSubTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $txtSubTotal - $txtSubTotalDescuento : $txtSubTotal;
				// GASTOS INCLUIDOS EN FACTURA
				$arrayGastosItm = array();
				$arrayGastosImportNacionalItm = array();
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
					foreach ($arrayArancel as $indice2 => $valor2) {
						if ($frmListaArticulo['hddIdArancelFamiliaItm'.$valor] == $arrayArancel[$indice2][0]
						&& str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor]) == $arrayArancel[$indice2][1]) {
							$existeArancel = true;
							
							$arrayArancel[$indice2][2] = $arrayArancel[$indice2][2] + $txtTotalItm;
							$arrayGasto = array();
							if (isset($arrayArancel[$indice2][3])) {
								foreach ($arrayArancel[$indice2][3] as $indice3 => $valor3) {
									$arrayGasto[$indice3] = $valor3 + $arrayGastosItm[$indice3];
									
									($frmTotalDcto['lstGastoItem'] == 1) ? $arrayGasto = array($valor3 + $txtGastosItm) : "";
								}
							}
							$arrayArancel[$indice2][3] = $arrayGasto;
							$arrayArancel[$indice2][4] = $arrayArancel[$indice2][4] + $totalPrecioCIF;
							$arrayArancel[$indice2][5] += ($txtCantRecibItm * $txtPesoItm);
							$arrayArancel[$indice2][6] += $txtCantRecibItm;
							$arrayArancel[$indice2][7]++;
						}
					}
				}
				
				if ($existeArancel == false) {
					($frmTotalDcto['lstGastoItem'] == 1) ? $arrayGastosItm = array($txtGastosItm) : "";
					
					$arrayArancel[] = array(
						$frmListaArticulo['hddIdArancelFamiliaItm'.$valor],
						str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor]), // % ADV
						$txtTotalItm, // F.O.B.
						$arrayGastosItm, // GASTOS
						$totalPrecioCIF, // C.I.F.
						($txtCantRecibItm * $txtPesoItm), // Peso Neto
						$txtCantRecibItm, // Cant. Articulos
						1); // Cant. Items
				}
				
				// VERIFICA SI EXISTE LA FAMILIA ARANCELARIA
				$existeAdValorem = false;
				if (isset($arrayAdValorem)) {
					foreach ($arrayAdValorem as $indice2 => $valor2) {
						if (str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor]) == $arrayAdValorem[$indice2][0]) {
							$existeAdValorem = true;
							$arrayAdValorem[$indice2][1] = $arrayAdValorem[$indice2][1] + $tarifaAdValorem;
							$arrayAdValorem[$indice2][2]++;
							$arrayAdValorem[$indice2][3] += $txtCantRecibItm;
						}
					}
				}
				
				if ($existeAdValorem == false) {
					$arrayAdValorem[] = array(
						str_replace(",", "", $frmListaArticulo['lstTarifaAdValorem'.$valor]),
						$tarifaAdValorem,
						1,
						$txtCantRecibItm);
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
						$totalExentoLocal += $subTotalItmCambio;
					} else if ($estatusIva != 0) {
						$subTotalIvaItm = ($subTotalItmCambio * $porcIva) / 100;
						
						$existIva = false;
						if (isset($arrayIvaLocal)) {
							foreach ($arrayIvaLocal as $indiceIva => $valorIva) {
								if ($arrayIvaLocal[$indiceIva][0] == $idIva) {
									$arrayIvaLocal[$indiceIva][1] += $subTotalItmCambio;
									$arrayIvaLocal[$indiceIva][2] += $subTotalIvaItm;
									$existIva = true;
								}
							}
						}
						
						if ($idIva > 0 && $existIva == false
						&& ($txtTotalItm - $hddTotalDescuentoItm) > 0) {
							$arrayIvaLocal[] = array(
								$idIva,
								$subTotalItmCambio,
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
			foreach ($arrayObjGasto as $indice => $valor) {
				// BUSCA LOS DATOS DEL GASTO
				$queryGasto = sprintf("SELECT * FROM pg_gastos
				WHERE id_gasto = %s
					AND id_modo_gasto IN (3);", 
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
					
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;",
						valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"));
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
							$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
							
							$gastosConIva += $txtMontoGasto;
						}
					}
					
					if ($totalRowsIva == 0) {
						$gastosSinIva += $txtMontoGasto;
					}
				}
			}
		}
	}
	
	// CREA LOS ELEMENTOS DE LOS ARANCELES
	if (isset($arrayAdValorem)) {
		foreach ($arrayAdValorem as $indice => $valor) {
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
				$indice,
					"% ADV ".$arrayAdValorem[$indice][0].":",
					"100%",
					$arrayAdValorem[$indice][2],
					"100%",
					number_format($arrayAdValorem[$indice][3], 2, ".", ","),
					$abrevMonedaLocal,
					$indice, $indice, number_format($arrayAdValorem[$indice][1], 2, ".", ","),
						$indice,
				
				$indice));
				
				$totalItems += $arrayAdValorem[$indice][2];
				$totalArticulos += $arrayAdValorem[$indice][3];
				$totalAdValorem += $arrayAdValorem[$indice][1];
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
			$indice + 1,
				"Total ADV:",
				$totalItems,
				number_format($totalArticulos, 2, ".", ","),
				$abrevMonedaLocal,
				number_format($totalAdValorem, 2, ".", ","),
					$indice + 1,
			
			$indice + 1));
	}
	
	// CREA LOS ELEMENTOS DE LOS ARANCELES
	if (isset($arrayArancel)) {
		foreach ($arrayArancel as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			// BUSCA LOS DATOS DEL ARANCEL
			$query = sprintf("SELECT * FROM pg_arancel_familia WHERE id_arancel_familia = %s;",
				valTpDato($arrayArancel[$indice][0], "int"));
			$rs = mysql_query($query);
			if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$htmlGastos = "";
			if (isset($arrayArancel[$indice][3])) {
				$htmlGastos = "<table width=\"100%\">";
				$htmlGastos .= "<tr>";
				foreach($arrayArancel[$indice][3] as $indice2 => $valor2) {
					$htmlGastos .= "<td align=\"right\" width=\"".(100 / count($arrayArancel[$indice][3]))."%\">".number_format($valor2, 2, ".", ",")."</td>";
				}
				$htmlGastos .= "</tr>";
				$htmlGastos .= "</table>";
			}
			
			$htmlTotalGastos = "";
			if (isset($arrayTotalGastosItm)) {
				$htmlTotalGastos = "<table width=\"100%\">";
				$htmlTotalGastos .= "<tr>";
				foreach($arrayTotalGastosItm as $indice2 => $valor2) {
					$htmlTotalGastos .= "<td align=\"right\" width=\"".(100 / count($arrayTotalGastosItm))."%\">".number_format($valor2, 2, ".", ",")."</td>";
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
				$indice, $clase,
					$indice, $contFila,
					utf8_encode($row['codigo_arancel']),
						$indice,
					utf8_encode($row['descripcion_arancel']),
					number_format($arrayArancel[$indice][1], 2, ".", ","),
					number_format($arrayArancel[$indice][7], 2, ".", ","),
					number_format($arrayArancel[$indice][2], 2, ".", ","),
					$htmlGastos,
					number_format($arrayArancel[$indice][4], 2, ".", ","),
					number_format($arrayArancel[$indice][5], 2, ".", ","),
					number_format($arrayArancel[$indice][6], 2, ".", ","),
				
				$indice));
			
			$spnTotalFOBArancel += $arrayArancel[$indice][2];
			$spnTotalCIFArancel += $arrayArancel[$indice][4];
			$spnTotalPesoNetoArancel += $arrayArancel[$indice][5];
			$spnCantArticulosArancel += $arrayArancel[$indice][6];
			$spnCantItemsArancel += $arrayArancel[$indice][7];
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
							"<label><table cellpadding=\"0\" cellspacing=\"0\"><tr><td><input type=\"checkbox\" id=\"cbxRedondeoIva%s\" name=\"cbxRedondeoIva%s\" %s value=\"1\"></td><td class=\"textoNegrita_9px\">&nbsp;Redondear</td><td>&nbsp;%s:</td></tr></table></label>".
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
							$indiceIva, $indiceIva, (($frmTotalDcto['cbxRedondeoIva'.$indiceIva] > 0) ? "checked=\"checked\"" : ""), utf8_encode($arrayIva[$indiceIva][5]), 
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
			
			$subTotalIva += ($frmTotalDcto['cbxRedondeoIva'.$indiceIva] > 0) ? round(doubleval($arrayIva[$indiceIva][2]), 2) : truncateFloat(doubleval($arrayIva[$indiceIva][2]), 2);
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
	
	$objResponse->script("
	byId('trMotivoNCPlanMayor').style.display = '".(($frmDcto['txtIdNotaCargo'] > 0) ? "" : "none")."';");
	
	return $objResponse;
}

function cargaLstArancelGrupo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstArancelGrupo\" name=\"lstArancelGrupo\" class=\"inputHabilitado\" onchange=\"validarAsignarADV();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_arancel_grupo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_arancel_grupo']."\">".utf8_encode($row['porcentaje_grupo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstArancelGrupo","innerHTML",$html);
	
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
	$totalRows = mysql_num_rows($rs);
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
	$totalRows = mysql_num_rows($rs);
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

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" onchange=\"xajax_cargaLstTasaCambio(this.value); xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\" style=\"width:150px\">";
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
	$html = "<select id=\"lstRetencionImpuesto\" name=\"lstRetencionImpuesto\" onchange=\"xajax_asignarRetencionImpuesto(this.value, xajax.getFormValues('frmDcto'));\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= "<option ".(($selId == 0 && strlen($selId) > 0) ? "selected=\"selected\"" : "")." value=\"0\">".("Sin Retención")."</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['iva']) ? "selected=\"selected\"" : "";
		
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
	$html = "<select id=\"lstTasaCambio\" name=\"lstTasaCambio\" onchange=\"xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\" style=\"width:150px\">";
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
	$queryFactura = sprintf("SELECT fact_comp.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_factura_compra fact_comp
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (fact_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	
	// BUSCA LA NOTA DE CARGO
	$queryNotaCargo = sprintf("SELECT
		nota_cargo.id_notacargo,
		nota_cargo.numero_notacargo
	FROM cp_notadecargo nota_cargo
		INNER JOIN an_factura_compra_detalle_unidad fact_comp_det_unidad ON (nota_cargo.id_detalles_pedido_compra = fact_comp_det_unidad.id_pedido_compra_detalle)
	WHERE fact_comp_det_unidad.id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsNotaCargo = mysql_query($queryNotaCargo);
	if (!$rsNotaCargo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);
	
	$objResponse->assign("txtIdNotaCargo","value",$rowNotaCargo['id_notacargo']);
	$objResponse->assign("txtNumeroNotaCargo","value",$rowNotaCargo['numero_notacargo']);
	
	// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
	$queryFacturaDetalle = sprintf("SELECT * FROM an_factura_compra_detalle_unidad fact_comp_det WHERE id_factura_compra = %s
	ORDER BY id_factura_compra_detalle_unidad ASC;",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
	if (!$rsFacturaDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
		$Result1 = insertarItemUnidad($contFila, "", $rowFacturaDetalle['id_factura_compra_detalle_unidad']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$frmListaArticulo['hddIdFacturaDetalleItm'.$contFila] = $rowFacturaDetalle['id_factura_compra_detalle_unidad'];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
	$queryFacturaDetalle = sprintf("SELECT * FROM an_factura_compra_detalle_accesorio fact_comp_det WHERE id_factura_compra = %s
	ORDER BY id_factura_compra_detalle_accesorio ASC;",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
	if (!$rsFacturaDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
		$Result1 = insertarItemAdicional($contFila, "", $rowFacturaDetalle['id_factura_compra_detalle_accesorio']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$frmListaArticulo['hddIdFacturaDetalleItm'.$contFila] = $rowFacturaDetalle['id_factura_compra_detalle_accesorio'];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	// BUSCA LOS GASTOS DE LA FACTURA
	$queryFacturaDetalle = sprintf("SELECT * FROM an_factura_compra_gasto fact_comp_gasto
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
		fact_comp_gasto.id_factura_compra_gasto,
		fact_comp_gasto.id_gasto
	FROM an_factura_compra_gasto fact_comp_gasto
		INNER JOIN pg_gastos gasto ON (fact_comp_gasto.id_gasto = gasto.id_gasto)
	WHERE fact_comp_gasto.id_factura_compra = %s
		AND fact_comp_gasto.id_modo_gasto IN (2)
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
	
	$objResponse->loadCommands(asignarProveedor($rowFactura['id_proveedor'], "Prov", "false"));
	
	$objResponse->assign("txtIdEmpresa","value",utf8_encode($idEmpresa));
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowFactura['nombre_empresa']));
	
	$idMonedaLocal = $rowFactura['id_moneda'];
	$idMonedaOrigen = ($rowFactura['id_moneda_tasa_cambio'] > 0) ? $rowFactura['id_moneda_tasa_cambio'] : $rowFactura['id_moneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	
	if (($idModoCompra == 1 && !xvalidaAcceso($objResponse, "an_registro_compra_nacional", "insertar", true, NULL, NULL, NULL, true, "an_registro_unidad_fisica_list.php"))
	|| ($idModoCompra == 2 && !xvalidaAcceso($objResponse,"an_registro_compra_importacion","insertar", true, NULL, NULL, NULL, true, "an_registro_unidad_fisica_list.php"))) { errorGuardarDcto($objResponse); return $objResponse; }
	
	$txtTasaCambio = ($rowFactura['monto_tasa_cambio'] >= 0) ? $rowFactura['monto_tasa_cambio'] : 0;
	$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
	$objResponse->assign("txtTasaCambio", "value", number_format($txtTasaCambio, 3, ".", ","));
	$objResponse->assign("hddIdMoneda", "value", $idMonedaLocal);
	$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $rowFactura['id_tasa_cambio']));
	$objResponse->script("byId('lstMoneda').onchange = function() { selectedOption(this.id, '".$idMonedaOrigen."'); }");
	$objResponse->script("byId('lstTasaCambio').onchange = function() { selectedOption(this.id, '".$rowFactura['id_tasa_cambio']."'); }");
	
	$objResponse->assign("txtDescuento","value",number_format($rowFactura['porcentaje_descuento'], 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($rowFactura['subtotal_descuento'], 2, ".", ","));
	$objResponse->assign("txtMontoTotalFactura","value",number_format($rowFactura['saldo_factura'], 2, ".", ","));
	$objResponse->loadCommands(cargaLstRetencionISLR($rowFactura['id_retencion_islr']));
	$objResponse->assign("txtBaseImpISLR","value",number_format($rowFactura['base_imponible_islr'], 2, ".", ","));
	$objResponse->loadCommands(cargaLstRetencionImpuesto($rowFactura['porcentaje_retencion']));
	$objResponse->loadCommands(asignarRetencionImpuesto($rowFactura['porcentaje_retencion'], array("txtIdNotaCargo" => $rowNotaCargo['id_notacargo'])));
	$objResponse->loadCommands(asignarMotivo($rowFactura['id_motivo_nota_credito_retencion'], 'Motivo', 'CP', 'I'));
	$objResponse->loadCommands(asignarMotivo($rowFactura['id_motivo_nota_credito_plan_mayor'], 'MotivoNCPlanMayor', 'CP', 'I'));
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = $rowFactura['id_modo_compra'];
	
	$objResponse->assign("txtIdFactura","value",$rowFactura['id_factura_compra']);
	$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat, strtotime($rowFactura['fecha_origen'])));
	$objResponse->assign("txtNumeroFacturaProveedor","value",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("txtNumeroControl","value",$rowFactura['numero_control_factura']);
	$objResponse->assign("txtFechaProveedor","value",date(spanDateFormat, strtotime($rowFactura['fecha_factura_proveedor'])));
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento","2","1","","1",$rowFactura['id_clave_movimiento']));
	$objResponse->loadCommands(cargaLstGrupoItem('lstViaEnvio','ViaEnvio',$rowFactura['id_via_envio']));
	$objResponse->call("seleccionarEnvio",$rowFactura['id_via_envio']);
	$objResponse->call("selectedOption","lstNacionalizar",$rowFactura['nacionalizada']);
	$objResponse->call("selectedOption","lstGastoItem",$rowFactura['gasto_manual_item']);
	$objResponse->assign("txtObservacionFactura","innerHTML",utf8_encode($rowFactura['observacion_factura']));
	
	// BUSCA SI LA FACTURA ESTA INCLUIDA EN UN EXPEDIENTE
	$query = sprintf("SELECT
		expediente.id_expediente,
		expediente.numero_expediente,
		expediente.numero_embarque
	FROM an_expediente_detalle_factura expediente_det_fact
		INNER JOIN an_expediente expediente ON (expediente_det_fact.id_expediente = expediente.id_expediente)
	WHERE expediente_det_fact.id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdExpediente","value",$row['id_expediente']);
	$objResponse->assign("txtExpediente","value",$row['numero_expediente']);
	$objResponse->assign("txtNumeroEmbarque","value",$row['numero_embarque']);
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function cargarDetalleCosto($hddNumeroArt, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	$txtTasaCambio = str_replace(",", "", $frmDcto['txtTasaCambio']);
	
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
			$txtDescuento = str_replace(",","",$frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
					
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (($frmListaArticulo['hddIdUnidadBasicaItm'.$valor] > 0 || $frmListaArticulo['hddIdAccesorioItm'.$valor] > 0) && $valor == $hddNumeroArt) {
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
	
	$hddIdGasto = $frmTotalDcto['hddIdGastoCargo'.$hddItmGasto];
	$hddCondicionGasto = $frmTotalDcto['hddCondicionGasto'.$hddItmGasto];
	
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
		$objResponse->assign("txtSubTotalFacturaGasto","value",$frmTotalDcto['hddSubTotalFacturaGasto'.$hddItmGasto]);
	}
	
	$objResponse->script("byId('lstCondicionGasto').onchange();");
	
	return $objResponse;
}

function eliminarAdicional($frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarAdicional(xajax.getFormValues('frmListaArticulo'));");
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

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_preregistro_compra_form","insertar")) { errorGuardarDcto($objResponse); return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaLocal = $frmTotalDcto['cbxIvaLocal'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idFacturaCompra = $frmDcto['txtIdFactura'];
	$idNotaCargo = $frmDcto['txtIdNotaCargo'];
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
	$queryConfig205 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 205 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig205 = mysql_query($queryConfig205);
	if (!$rsConfig205) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig205 = mysql_num_rows($rsConfig205);
	$rowConfig205 = mysql_fetch_assoc($rsConfig205);
	
	$valor = explode("|",$rowConfig205['valor']);
	
	$txtFechaRegistroCompra = date(spanDateFormat);
	$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
	if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
		if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
			if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
			|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
				$txtFechaRegistroCompra = $txtFechaProveedor;
			} else {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
			}
		} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
			$objResponse->script("byId('cbxFechaRegistro').checked = false;");
			$objResponse->alert("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra));
		} else {
			$txtFechaRegistroCompra = $txtFechaProveedor;
		}
	} else if ($frmDcto['cbxFechaRegistro'] == 1) {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
		errorGuardarDcto($objResponse);
		return $objResponse->alert(("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
	}
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script("
			byId('lstCondicionUnidad".$valor."').className = 'inputHabilitado';
			byId('txtFechaFabricacion".$valor."').className = 'inputHabilitado';
			byId('lstClase".$valor."').className = 'inputHabilitado';
			byId('lstUso".$valor."').className = 'inputHabilitado';
			byId('lstColorExterno".$valor."').className = 'inputHabilitado';
			byId('lstColorInterno".$valor."').className = 'inputHabilitado';
			byId('txtRegistroLegalizacion".$valor."').className = 'inputHabilitado';
			byId('txtRegistroFederal".$valor."').className = 'inputHabilitado';
			byId('txtCostoItm".$valor."').className = 'inputCompleto';");
			
			$hddIdUnidadBasicaItm = $frmListaArticulo['hddIdUnidadBasicaItm'.$valor];
			
			$hddIdPedidoDetalleItm = $frmListaArticulo['hddIdPedidoDetalleItm'.$valor];
			$hddIdFacturaDetalleItm = $frmListaArticulo['hddIdFacturaDetalleItm'.$valor];
			
			if ($hddIdUnidadBasicaItm > 0) {
				if (!($frmListaArticulo['lstCondicionUnidad'.$valor] > 0)) { $arrayInvalido[] = "lstCondicionUnidad".$valor; }
				if (!(strlen($frmListaArticulo['txtFechaFabricacion'.$valor]) > 0)) { $arrayInvalido[] = "txtFechaFabricacion".$valor; }
				if (!($frmListaArticulo['lstClase'.$valor] > 0)) { $arrayInvalido[] = "lstClase".$valor; }
				if (!($frmListaArticulo['lstUso'.$valor] > 0)) { $arrayInvalido[] = "lstUso".$valor; }
				if (!($frmListaArticulo['lstColorExterno'.$valor] > 0)) { $arrayInvalido[] = "lstColorExterno".$valor; }
				if (!($frmListaArticulo['lstColorInterno'.$valor] > 0)) { $arrayInvalido[] = "lstColorInterno".$valor; }
				if (!(strlen($frmListaArticulo['txtRegistroLegalizacion'.$valor]) > 0)) { $arrayInvalido[] = "txtRegistroLegalizacion".$valor; }
				if (!(strlen($frmListaArticulo['txtRegistroFederal'.$valor]) > 0)) { $arrayInvalido[] = "txtRegistroFederal".$valor; }
			}
			
			if (str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]) <= 0) { $arrayInvalido[] = "txtCostoItm".$valor; }
		}
	}
	
	if (isset($arrayInvalido)) {
		foreach ($arrayInvalido as $indice => $valor) {
			$objResponse->script("byId('".$valor."').className = 'inputErrado'");
		}
		
		if (count($arrayInvalido) > 0) {
			errorGuardarDcto($objResponse);
			return $objResponse->alert("Los campos señalados en rojo son invalidos");
		}
	}
	
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
			if ($frmTotalDcto['lstGastoItem'] == 0 && $frmTotalDcto['hddIdTipoMedida'.$valor] == 1 && $txtMedidaGasto > 0) { // 0 = No, 1 = Si // 1 = Peso
				if ($txtMedidaGasto != str_replace(",", "", $frmListaArticulo['txtTotalPesoItem'])) {
					errorGuardarDcto($objResponse); 
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
	if (!$rsProv) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$txtDiasCreditoProv = ($rowProv['diascredito'] > 0) ? $rowProv['diascredito'] : 0;
	
	// SI EL PROVEEDOR ES A CREDITO SE LE SUMA LA CANTIDAD DE DIAS DE CREDITO PARA LA FECHA DE VENCIMIENTO
	$fechaVencimiento = ($rowProv['credito'] == "Si") ? suma_fechas(spanDateFormat,$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
	
	if ($idFacturaCompra > 0) {
		$updateSQL = sprintf("UPDATE an_factura_compra SET
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
			aplica_libros = %s,
			porcentaje_retencion = %s,
			id_motivo_nota_credito_retencion = %s,
			id_motivo_nota_credito_plan_mayor = %s
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
			valTpDato(2, "int"), // 0 = Repuestos, 1 = Servicios, 2 = Autos, 3 = Administración
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
			valTpDato($frmTotalDcto['lstRetencionImpuesto'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtIdMotivo'], "int"),
			valTpDato($frmTotalDcto['txtIdMotivoNCPlanMayor'], "int"),
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		// INSERTA LOS DATOS DE LA FACTURA
		$insertSQL = sprintf("INSERT INTO an_factura_compra (id_empresa, id_modo_compra, numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, id_modulo, id_clave_movimiento, estatus_factura, observacion_factura, tipo_pago, porcentaje_descuento, subtotal_descuento, id_retencion_islr, base_imponible_islr, gasto_manual_item, aplica_libros, porcentaje_retencion, id_motivo_nota_credito_retencion, id_motivo_nota_credito_plan_mayor)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
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
			valTpDato(2, "int"), // 0 = Repuestos, 1 = Servicios, 2 = Vehiculos, 3 = Administración
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
			valTpDato($frmTotalDcto['lstRetencionImpuesto'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtIdMotivo'], "int"),
			valTpDato($frmTotalDcto['txtIdMotivoNCPlanMayor'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idFacturaCompra = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICA SI LOS ITEMS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryFacturaDetalle = sprintf("SELECT * FROM an_factura_compra_detalle_unidad WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
	if (!$rsFacturaDetalle) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
		$idFacturaDetalle = $rowFacturaDetalle['id_factura_compra_detalle_unidad'];
		
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$hddIdUnidadBasicaItm = $frmListaArticulo['hddIdUnidadBasicaItm'.$valor];
				$hddIdUnidadFisicaItm = $frmListaArticulo['hddIdUnidadFisicaItm'.$valor];
				
				$hddIdPedidoDetalleItm = $frmListaArticulo['hddIdPedidoDetalleItm'.$valor];
				$hddIdFacturaDetalleItm = $frmListaArticulo['hddIdFacturaDetalleItm'.$valor];
				
				if ($hddIdUnidadBasicaItm > 0 && $idFacturaDetalle == $hddIdFacturaDetalleItm) {
					$existRegDet = true;
					
					$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
					$cantRecibida = $cantPedida;
					$hddIdArancelFamiliaItm = $frmListaArticulo['hddIdArancelFamiliaItm'.$valor];
					$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
					
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
					
					// EDITA LOS DATOS DEL DETALLE
					$updateSQL = sprintf("UPDATE an_factura_compra_detalle_unidad SET
						id_factura_compra = %s,
						id_unidad_basica = %s,
						costo_unitario = %s,
						gasto_unitario = %s,
						peso_unitario = %s,
						id_arancel_familia = %s,
						porcentaje_grupo = %s,
						id_cliente = %s
					WHERE id_factura_compra_detalle_unidad = %s;",
						valTpDato($idFacturaCompra, "int"),
						valTpDato($hddIdUnidadBasicaItm, "int"),
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato($txtGastosItm, "real_inglesa"),
						valTpDato($txtPesoItm, "real_inglesa"),
						valTpDato($hddIdArancelFamiliaItm, "int"),
						valTpDato($lstTarifaAdValorem, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdClienteItm'.$valor], "int"),
						valTpDato($idFacturaDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ELIMINA LOS IMPUESTOS DEL DETALLE
					$deleteSQL = sprintf("DELETE FROM an_factura_compra_detalle_unidad_iva
					WHERE id_factura_compra_detalle_unidad = %s;",
						valTpDato($hddIdFacturaDetalleItm, "int"));
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					if (isset($arrayObjIvaItm)) {
						foreach ($arrayObjIvaItm as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							
							if ($valor1[0] == $valor) {
								$insertSQL = sprintf("INSERT INTO an_factura_compra_detalle_unidad_iva (id_factura_compra_detalle_unidad, id_iva, iva)
								VALUE (%s, %s, %s);",
									valTpDato($hddIdFacturaDetalleItm, "int"),
									valTpDato($frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]], "int"),
									valTpDato($frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]], "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
					
					// ACTUALIZA EL ESTATUS DEL DETALLE DEL PEDIDO
					$updateSQL = sprintf("UPDATE an_solicitud_factura SET
						costo_unidad = %s,
						estado = %s
					WHERE idSolicitud = %s;",
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato(5, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Transito, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
						valTpDato($hddIdPedidoDetalleItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA LA OBSERVACION DE LA NOTA DE DEBITO AGREGANDOLE EL SERIAL DE CHASIS DE LA UNIDAD FISICA
					$updateSQL = sprintf("UPDATE cp_notadecargo SET
						observacion_notacargo = REPLACE(observacion_notacargo, (SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
																				WHERE uni_fis.id_unidad_fisica = %s), %s)
					WHERE id_notacargo = %s;",
						valTpDato($hddIdUnidadFisicaItm, "int"),
						valTpDato($frmListaArticulo['txtSerialCarroceria'.$valor], "text"),
						valTpDato($idNotaCargo, "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// GUARDA LOS DATOS DE LA UNIDAD FISICA
					$Result1 = guardarUnidadFisica($hddIdUnidadFisicaItm, $hddIdFacturaDetalleItm, NULL, $valor, $frmDcto, $frmListaArticulo, $frmTotalDcto);
					if ($Result1[0] != true) {
						errorGuardarDcto($objResponse);
						return $objResponse->alert($Result1[1]);
					}
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM an_factura_compra_detalle_unidad WHERE id_factura_compra_detalle_unidad = %s;",
				valTpDato($rowFacturaDetalle['id_factura_compra_detalle_unidad'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// VERIFICA SI LOS ITEMS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryFacturaDetalle = sprintf("SELECT * FROM an_factura_compra_detalle_accesorio
	WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
	if (!$rsFacturaDetalle) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
		$idFacturaDetalle = $rowFacturaDetalle['id_factura_compra_detalle_accesorio'];
		
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$hddIdAccesorioItm = $frmListaArticulo['hddIdAccesorioItm'.$valor];
				
				$hddIdFacturaDetalleItm = $frmListaArticulo['hddIdFacturaDetalleItm'.$valor];
				
				if ($hddIdAccesorioItm > 0 && $idFacturaDetalle == $hddIdFacturaDetalleItm) {
					$existRegDet = true;
					
					$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
					$cantRecibida = $cantPedida;
					$hddIdArancelFamiliaItm = $frmListaArticulo['hddIdArancelFamiliaItm'.$valor];
					$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
					
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
					
					// EDITA LOS DATOS DEL DETALLE
					$updateSQL = sprintf("UPDATE an_factura_compra_detalle_accesorio SET
						id_factura_compra = %s,
						id_accesorio = %s,
						cantidad = %s,
						costo_unitario = %s,
						gasto_unitario = %s,
						peso_unitario = %s,
						id_arancel_familia = %s,
						porcentaje_grupo = %s
					WHERE id_factura_compra_detalle_accesorio = %s;",
						valTpDato($idFacturaCompra, "int"),
						valTpDato($hddIdAccesorioItm, "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato($txtGastosItm, "real_inglesa"),
						valTpDato($txtPesoItm, "real_inglesa"),
						valTpDato($hddIdArancelFamiliaItm, "int"),
						valTpDato($lstTarifaAdValorem, "real_inglesa"),
						valTpDato($idFacturaDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					if (isset($arrayObjIvaItm)) {
						foreach ($arrayObjIvaItm as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							
							if ($valor1[0] == $valor) {
								$updateSQL = sprintf("UPDATE an_factura_compra_detalle_accesorio SET
									id_iva = %s,
									iva = %s
								WHERE id_factura_compra_detalle_accesorio = %s;",
									valTpDato($frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]], "int"),
									valTpDato($frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]], "real_inglesa"),
									valTpDato($hddIdFacturaDetalleItm, "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSQL: ".$insertSQL); }
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM an_factura_compra_detalle_accesorio
			WHERE id_factura_compra_detalle_accesorio = %s;",
				valTpDato($rowFacturaDetalle['id_factura_compra_detalle_accesorio'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA EL DETALLE DE LA FACTURA
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$hddIdUnidadBasicaItm = $frmListaArticulo['hddIdUnidadBasicaItm'.$valor];
			$hddIdAccesorioItm = $frmListaArticulo['hddIdAccesorioItm'.$valor];
			
			$hddIdPedidoDetalleItm = $frmListaArticulo['hddIdPedidoDetalleItm'.$valor];
			$hddIdFacturaDetalleItm = $frmListaArticulo['hddIdFacturaDetalleItm'.$valor];
			
			if (($hddIdUnidadBasicaItm > 0 || $hddIdAccesorioItm > 0) && !($hddIdFacturaDetalleItm > 0)) {
				$cantPedida = str_replace(",", "", $frmListaArticulo['txtCantRecibItm'.$valor]);
				$cantRecibida = $cantPedida;
				$hddIdArancelFamiliaItm = $frmListaArticulo['hddIdArancelFamiliaItm'.$valor];
				$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
			
				$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
				$txtPesoItm = str_replace(",","",$frmListaArticulo['txtPesoItm'.$valor]);
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
				
				if ($hddIdUnidadBasicaItm > 0) {
					$hddIdUnidadFisicaItm = $frmListaArticulo['hddIdUnidadFisicaItm'.$valor];
					
					// REGISTRA EL DETALLE DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO an_factura_compra_detalle_unidad (id_factura_compra, id_pedido_compra_detalle, id_unidad_basica, cantidad, costo_unitario, gasto_unitario, peso_unitario, id_arancel_familia, porcentaje_grupo, id_cliente)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFacturaCompra, "int"),
						valTpDato($hddIdPedidoDetalleItm, "int"),
						valTpDato($hddIdUnidadBasicaItm, "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato($txtGastosItm, "real_inglesa"),
						valTpDato($txtPesoItm, "real_inglesa"),
						valTpDato($hddIdArancelFamiliaItm, "int"),
						valTpDato($lstTarifaAdValorem, "real_inglesa"),
						valTpDato($frmListaArticulo['hddIdClienteItm'.$valor], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$hddIdFacturaDetalleItm = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					if (isset($arrayObjIvaItm)) {
						foreach ($arrayObjIvaItm as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							
							if ($valor1[0] == $valor) {
								$insertSQL = sprintf("INSERT INTO an_factura_compra_detalle_unidad_iva (id_factura_compra_detalle_unidad, id_iva, iva)
								VALUE (%s, %s, %s);",
									valTpDato($hddIdFacturaDetalleItm, "int"),
									valTpDato($frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]], "int"),
									valTpDato($frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]], "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
					
					// ACTUALIZA EL ESTATUS DEL DETALLE DEL PEDIDO
					$updateSQL = sprintf("UPDATE an_solicitud_factura SET
						costo_unidad = %s,
						estado = %s
					WHERE idSolicitud = %s;",
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato(5, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Transito, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
						valTpDato($hddIdPedidoDetalleItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA LA OBSERVACION DE LA NOTA DE DEBITO AGREGANDOLE EL SERIAL DE CHASIS DE LA UNIDAD FISICA
					$updateSQL = sprintf("UPDATE cp_notadecargo SET
						observacion_notacargo = REPLACE(observacion_notacargo, (SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
																				WHERE uni_fis.id_unidad_fisica = %s), %s)
					WHERE id_notacargo = %s;",
						valTpDato($hddIdUnidadFisicaItm, "int"),
						valTpDato($frmListaArticulo['txtSerialCarroceria'.$valor], "text"),
						valTpDato($idNotaCargo, "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// GUARDA LOS DATOS DE LA UNIDAD FISICA
					$Result1 = guardarUnidadFisica($hddIdUnidadFisicaItm, $hddIdFacturaDetalleItm, NULL, $valor, $frmDcto, $frmListaArticulo, $frmTotalDcto);
					if ($Result1[0] != true) {
						errorGuardarDcto($objResponse);
						return $objResponse->alert($Result1[1]);
					}
				} else if ($hddIdAccesorioItm > 0) {
					// REGISTRA EL DETALLE DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO an_factura_compra_detalle_accesorio (id_factura_compra, id_accesorio, cantidad, costo_unitario, gasto_unitario, peso_unitario, id_arancel_familia, porcentaje_grupo)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFacturaCompra, "int"),
						valTpDato($hddIdAccesorioItm, "int"),
						valTpDato($cantRecibida, "real_inglesa"),
						valTpDato($txtCostoItm, "real_inglesa"),
						valTpDato($txtGastosItm, "real_inglesa"),
						valTpDato($txtPesoItm, "real_inglesa"),
						valTpDato($hddIdArancelFamiliaItm, "int"),
						valTpDato($lstTarifaAdValorem, "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$hddIdFacturaDetalleItm = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					if (isset($arrayObjIvaItm)) {
						foreach ($arrayObjIvaItm as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							
							if ($valor1[0] == $valor) {
								$updateSQL = sprintf("UPDATE an_factura_compra_detalle_accesorio SET
									id_iva = %s,
									iva = %s
								WHERE id_factura_compra_detalle_accesorio = %s;",
									valTpDato($frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]], "int"),
									valTpDato($frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]], "real_inglesa"),
									valTpDato($hddIdFacturaDetalleItm, "int"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($updateSQL);
								if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
				}
			}
		}
	}
	
	if ($idModoCompra == 2) { // 2 = Importacion
		// GUARDA LOS DATOS DE LAS FACTURA DE IMPORTACION
		$Result1 = guardarDctoImportacion($idFacturaCompra, $frmDcto, $frmTotalDcto, "PREREGISTRO");
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); return $objResponse->alert($Result1[1]); }
	}
	
	// VERIFICA SI LOS GASTOS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryFacturaGasto = sprintf("SELECT * FROM an_factura_compra_gasto
	WHERE id_factura_compra = %s
		AND id_modo_gasto IN (1,3);",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaGasto = mysql_query($queryFacturaGasto);
	if (!$rsFacturaGasto) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowFacturaGasto = mysql_fetch_assoc($rsFacturaGasto)) {
		$idGasto = $rowFacturaGasto['id_gasto'];
		
		$existRegDet = false;
		if (isset($arrayObjGasto)) {
			foreach ($arrayObjGasto as $indice => $valor) {
				$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
				$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
				$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
				
				if ($idGasto == $frmTotalDcto['hddIdGasto'.$valor] && $txtMontoGasto != 0) {
					$existRegDet = true;
					
					// EDITA LOS DATOS DEL GASTO
					$updateSQL = sprintf("UPDATE an_factura_compra_gasto fact_comp_gasto, pg_gastos gasto SET
						fact_comp_gasto.tipo = %s,
						fact_comp_gasto.porcentaje_monto = %s,
						fact_comp_gasto.monto = %s,
						fact_comp_gasto.monto_medida = %s,
						fact_comp_gasto.id_iva = %s,
						fact_comp_gasto.iva = %s,
						fact_comp_gasto.id_modo_gasto = gasto.id_modo_gasto,
						fact_comp_gasto.id_tipo_medida = gasto.id_tipo_medida,
						fact_comp_gasto.afecta_documento = gasto.afecta_documento
					WHERE gasto.id_gasto = %s
						AND fact_comp_gasto.id_factura_compra_gasto = %s;",
						valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
						valTpDato($txtPorcGasto, "real_inglesa"),
						valTpDato($txtMontoGasto, "real_inglesa"),
						valTpDato($txtMedidaGasto, "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"),
						valTpDato($frmTotalDcto['hddIvaGasto'.$valor], "real_inglesa"),
						valTpDato($idGasto, "int"),
						valTpDato($rowFacturaGasto['id_factura_compra_gasto'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM an_factura_compra_gasto WHERE id_factura_compra_gasto = %s;",
				valTpDato($rowFacturaGasto['id_factura_compra_gasto'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA LOS GASTOS DEL PEDIDO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$idGasto = $frmTotalDcto['hddIdGasto'.$valor];
			
			// VERIFICA SI LOS GASTOS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
			$queryFacturaGasto = sprintf("SELECT * FROM an_factura_compra_gasto
			WHERE id_gasto = %s
				AND id_factura_compra = %s;",
				valTpDato($idGasto, "int"),
				valTpDato($idFacturaCompra, "int"));
			$rsFacturaGasto = mysql_query($queryFacturaGasto);
			if (!$rsFacturaGasto) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsFacturaGasto = mysql_num_rows($rsFacturaGasto);
			
			$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
			$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
			$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]);
			
			if (round($txtMontoGasto, 2) > 0 && $totalRowsFacturaGasto == 0) {
				$insertSQL = sprintf("INSERT INTO an_factura_compra_gasto (id_factura_compra, id_gasto, tipo, porcentaje_monto, monto, monto_medida, id_iva, iva, id_modo_gasto, id_tipo_medida, afecta_documento)
				SELECT %s, id_gasto, %s, %s, %s, %s, %s, %s, id_modo_gasto, id_tipo_medida, afecta_documento FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idFacturaCompra, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($txtMedidaGasto, "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"),
					valTpDato($frmTotalDcto['hddIvaGasto'.$valor], "real_inglesa"),
					valTpDato($idGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// ELIMINA LOS IMPUESTOS DE LA FACTURA DE COMPRA
	$deleteSQL = sprintf("DELETE FROM an_factura_compra_iva
	WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO an_factura_compra_iva (id_factura_compra, base_imponible, subtotal_iva, id_iva, iva, lujo)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idFacturaCompra, "int"),
				valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
				valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['hddLujoIva'.$valor], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	// ACTUALIZA LOS MONTOS DE LA FACTURA DE COMPRA
	$updateSQL = sprintf("UPDATE an_factura_compra SET
		monto_exento = IFNULL((SELECT SUM(fact_comp_det.cantidad * fact_comp_det.costo_unitario)
								FROM an_factura_compra_detalle_unidad fact_comp_det
								WHERE (SELECT COUNT(fact_comp_det_unidad_iva.id_factura_compra_detalle_unidad)
										FROM an_factura_compra_detalle_unidad_iva fact_comp_det_unidad_iva
										WHERE fact_comp_det_unidad_iva.id_factura_compra_detalle_unidad = fact_comp_det.id_factura_compra_detalle_unidad) = 0
									AND fact_comp_det.id_factura_compra = an_factura_compra.id_factura_compra), 0)
						+ IFNULL((SELECT SUM(fact_comp_det_acc.cantidad * fact_comp_det_acc.costo_unitario)
								FROM an_factura_compra_detalle_accesorio fact_comp_det_acc
								WHERE fact_comp_det_acc.id_iva IS NULL
									AND fact_comp_det_acc.id_factura_compra = an_factura_compra.id_factura_compra), 0)
						+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
								FROM an_factura_compra_gasto fact_comp_gasto
								WHERE fact_comp_gasto.id_modo_gasto IN (1)
									AND fact_comp_gasto.afecta_documento = 1
									AND fact_comp_gasto.id_iva IS NULL
									AND fact_comp_gasto.id_factura_compra = an_factura_compra.id_factura_compra), 0),
		monto_exonerado = 0,
		subtotal_factura = IFNULL((SELECT SUM(fact_comp_det.cantidad * fact_comp_det.costo_unitario)
									FROM an_factura_compra_detalle_unidad fact_comp_det
									WHERE fact_comp_det.id_factura_compra = an_factura_compra.id_factura_compra), 0)
							+ IFNULL((SELECT SUM(fact_comp_det_acc.cantidad * fact_comp_det_acc.costo_unitario)
									FROM an_factura_compra_detalle_accesorio fact_comp_det_acc
									WHERE fact_comp_det_acc.id_factura_compra = an_factura_compra.id_factura_compra), 0),
		saldo_factura = %s
	WHERE id_factura_compra = %s;",
		valTpDato($frmTotalDcto['txtMontoTotalFactura'], "real_inglesa"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdFactura","value",$idFacturaCompra);
	
	errorGuardarDcto($objResponse);
	$objResponse->alert("Registro de Compra guardado con éxito.");
	
	$objResponse->script("
	byId('btnCancelar').click();");
	
	return $objResponse;
}

function insertarAdicional($idAccesorio, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$existe = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdAccesorioItm'.$valor] == $idAccesorio) {
				$existe = true;
			}
		}
	}
	
	if ($existe == false) {
		$Result1 = insertarItemAdicional($contFila, $idAccesorio);
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
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarAdicional%s').disabled = false;",
			$cont));
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
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarGasto%s').disabled = false;",
			$cont));
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
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarGastoImportacion%s').disabled = false;",
			$cont));
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
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarOtroCargo%s').disabled = false;",
			$cont));
	}
	
	return $objResponse;
}

function insertarPaquete($idPaquete, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// BUSCA LOS ADICIONALES DEL PAQUETE
	$query = sprintf("SELECT *
	FROM an_acc_paq acc_paquete
		INNER JOIN an_accesorio acc ON (acc_paquete.id_accesorio = acc.id_accesorio)
	WHERE acc_paquete.id_paquete = %s;",
		valTpDato($idPaquete, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$idAccesorio = $row['id_accesorio'];
		
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmListaArticulo['hddIdAccesorioItm'.$valor] == $idAccesorio) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemAdicional($contFila, $idAccesorio);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert("Este item \"".utf8_encode($row['nom_accesorio'])."\" ya se encuentra incluido");
		}
	}
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarPaquete%s').disabled = false;",
			$cont));
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function listaAdicional($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio IN (1)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT acc.*,
		(CASE acc.iva_accesorio
			WHEN 1 THEN
				(SELECT iva FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva)
			ELSE
				'-'
		END) AS iva
	FROM an_accesorio acc %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "72%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "14%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaAdicional", "14%", $pageNum, "costo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarAdicional%s\" onclick=\"validarInsertarAdicional('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_accesorio']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td align=\"right\">".(($row['iva'] > 0) ? number_format($row['iva'], 2, ".", ",") : $row['iva'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo_accesorio'], 2, ".", ",")."</td>";
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
	
	$objResponse->assign("divListaAdicional","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("divListaGasto","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGastoImportacion(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("divListaGastoImportacion","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
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
	
	$query = sprintf("SELECT motivo.*,
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
	FROM pg_motivo motivo %s", $sqlBusq);
	
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
			case "CC" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>"; break;
			case "CP" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>"; break;
			case "CJ" : break;
			case "TE" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>"; break;
			default : $imgPedidoModulo = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMotivo('".$row['id_motivo']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($row['descripcion_modulo_transaccion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".($row['descripcion_tipo_transaccion'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
		$htmlTb .= "<td colspan=\"50\">";
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtroCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaOtroCargo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("divListaOtrosCargos","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPais(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPais(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("divListaPais","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaPaquete($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_paquete LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_paquete %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPaquete", "100%", $pageNum, "nom_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$queryPaqueteAccesorio = sprintf("SELECT acc.*,
			(CASE acc.iva_accesorio
				WHEN 1 THEN
					(SELECT iva FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1 ORDER BY iva)
				ELSE
					'-'
			END) AS iva
		FROM an_acc_paq acc_paquete
			INNER JOIN an_accesorio acc ON (acc_paquete.id_accesorio = acc.id_accesorio)
		WHERE id_paquete = %s
		ORDER BY acc.nom_accesorio ASC;",
			valTpDato($row['id_paquete'], "int"));
		$rsPaqueteAccesorio = mysql_query($queryPaqueteAccesorio);
		if (!$rsPaqueteAccesorio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$htmlTb .= "<tr align=\"left\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarPaquete%s\" onclick=\"validarInsertarPaquete('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_paquete']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td><fieldset><legend class=\"legend\">".utf8_encode($row['nom_paquete'])."</legend>";
				$htmlTb .= "<table width=\"100%\">";
				$cont = 0;
				while ($rowPaqueteAccesorio = mysql_fetch_assoc($rsPaqueteAccesorio)) {
					$cont++;
					
					$htmlTb .= (fmod($cont, 2) == 1) ? "<tr align=\"left\" class=\"".$clase."\" height=\"24\">" : "";
					
					$htmlTb .= "<td width=\"30%\">- ".utf8_encode($rowPaqueteAccesorio['nom_accesorio'])."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".(($rowPaqueteAccesorio['iva'] > 0) ? number_format($rowPaqueteAccesorio['iva'], 2, ".", ",") : $row['iva'])."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".number_format($rowPaqueteAccesorio['costo_accesorio'], 2, ".", ",")."</td>";
						
					$htmlTb .= (fmod($cont, 2) == 0) ? "</tr>" : "";
				}
				$htmlTb .= "</table>";
			$htmlTb .= "</fieldset></td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"2\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPaquete(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"2\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPaquete","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaRegistroCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_comp.id_modulo = 3");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_comp.id_factura NOT IN (SELECT id_factura_compra_cargo FROM cp_factura_gasto fact_comp_gasto
															WHERE id_factura_compra_cargo IS NOT NULL)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR fact_comp.numero_control_factura LIKE %s
		OR fact_comp.numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		fact_comp.id_factura,
		fact_comp.fecha_origen,
		fact_comp.numero_factura_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(IFNULL(fact_comp.subtotal_factura, 0)
			- IFNULL(fact_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
						AND fact_compra_gasto.id_modo_gasto = 1
						AND fact_compra_gasto.afecta_documento = 1), 0)
			+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva fact_compra_iva
					WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)
		) AS total,
		
		moneda_local.abreviacion AS abreviacion_moneda,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = fact_comp.id_factura
		LIMIT 1) AS idRetencionCabezera
	FROM cp_factura fact_comp
		INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda) %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
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
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("divListaRegistroCompra","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function nuevoDcto($idPedidoDetalle, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$query = sprintf("SELECT
		ped_comp_det.idSolicitud, 
		ped_comp.id_empresa, 
		ped_comp.id_proveedor, 
		ped_comp.id_moneda, 
		ped_comp.id_moneda_tasa_cambio, 
		ped_comp.id_tasa_cambio, 
		ped_comp.monto_tasa_cambio, 
		ped_comp_det.idSolicitud, 
		ped_comp_det.idUnidadBasica, 
		uni_fis.id_unidad_fisica, 
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo, 
		vw_iv_modelo.nom_ano, 
		ped_comp_det.idFormaPagoAsignacion, 
		forma_pago_asig.idProveedor AS id_proveedor_plan_mayor, 
		ped_comp_det.costo_unidad,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_solicitud_factura ped_comp_det
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN formapagoasignacion forma_pago_asig ON (ped_comp_det.idFormaPagoAsignacion = forma_pago_asig.idFormaPagoAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE ped_comp_det.idSolicitud = %s;", 
		valTpDato($idPedidoDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	
	$Result1 = insertarItemUnidad($contFila, $row['idSolicitud']);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$frmListaArticulo['hddIdPedidoDetalleItm'.$contFila] = $idPedidoDetalle;
		$objResponse->script($Result1[1]);
		$arrayObj[] = $contFila;
	}
	
	$objResponse->loadCommands(asignarProveedor($row['id_proveedor'], "Prov", "false"));
	
	$objResponse->assign("txtIdEmpresa", "value", utf8_encode($idEmpresa));
	$objResponse->assign("txtEmpresa", "value", utf8_encode($row['nombre_empresa']));
	
	$idMonedaLocal = $row['id_moneda'];
	$idMonedaOrigen = ($row['id_moneda_tasa_cambio'] > 0) ? $row['id_moneda_tasa_cambio'] : $row['id_moneda'];
	
	$txtTasaCambio = ($row['monto_tasa_cambio'] >= 0) ? $row['monto_tasa_cambio'] : 0;
	$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
	$objResponse->assign("txtTasaCambio", "value", number_format($txtTasaCambio, 3, ".", ","));
	$objResponse->assign("hddIdMoneda", "value", $idMonedaLocal);
	$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $row['id_tasa_cambio']));
	$objResponse->script("byId('lstMoneda').onchange = function() { selectedOption(this.id, '".$idMonedaOrigen."'); }");
	$objResponse->script("byId('lstTasaCambio').onchange = function() { selectedOption(this.id, '".$row['id_tasa_cambio']."'); }");
	
	$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format(0, 2, ".", ","));
	
	$objResponse->call("selectedOption","lstGastoItem",0);
		
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	
	$objResponse->assign("txtFechaRegistroCompra", "value", date(spanDateFormat));
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento","2","1","","1"));
	$objResponse->call("seleccionarEnvio", "");
	$objResponse->assign("txtMontoTotalFactura","value",number_format($row['costo_unidad'], 2, ".", ","));
	$objResponse->loadCommands(asignarRetencionImpuesto(0, array("txtIdNotaCargo" => "")));
	
	// BUSCA LA NOTA DE CARGO
	$query = sprintf("SELECT
		nota_cargo.id_notacargo,
		nota_cargo.numero_notacargo
	FROM cp_notadecargo nota_cargo
	WHERE nota_cargo.id_detalles_pedido_compra = %s;",
		valTpDato($idPedidoDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdNotaCargo","value",$row['id_notacargo']);
	$objResponse->assign("txtNumeroNotaCargo","value",$row['numero_notacargo']);
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION, "aprobarDcto");
$xajax->register(XAJAX_FUNCTION, "asignarADV");
$xajax->register(XAJAX_FUNCTION, "asignarFacturaCargo");
$xajax->register(XAJAX_FUNCTION, "asignarFechaRegistro");
$xajax->register(XAJAX_FUNCTION, "asignarMoneda");
$xajax->register(XAJAX_FUNCTION, "asignarMotivo");
$xajax->register(XAJAX_FUNCTION, "asignarPais");
$xajax->register(XAJAX_FUNCTION, "asignarProveedor");
$xajax->register(XAJAX_FUNCTION, "asignarRetencionImpuesto");
$xajax->register(XAJAX_FUNCTION, "buscarAdicional");
$xajax->register(XAJAX_FUNCTION, "buscarGasto");
$xajax->register(XAJAX_FUNCTION, "buscarGastoImportacion");
$xajax->register(XAJAX_FUNCTION, "buscarMotivo");
$xajax->register(XAJAX_FUNCTION, "buscarPais");
$xajax->register(XAJAX_FUNCTION, "buscarPaquete");
$xajax->register(XAJAX_FUNCTION, "buscarProveedor");
$xajax->register(XAJAX_FUNCTION, "buscarRegistroCompra");
$xajax->register(XAJAX_FUNCTION, "calcularDcto");
$xajax->register(XAJAX_FUNCTION, "cargaLstArancelGrupo");
$xajax->register(XAJAX_FUNCTION, "cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION, "cargaLstGrupoItem");
$xajax->register(XAJAX_FUNCTION, "cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION, "cargaLstRetencionImpuesto");
$xajax->register(XAJAX_FUNCTION, "cargaLstRetencionISLR");
$xajax->register(XAJAX_FUNCTION, "cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION, "cargarDcto");
$xajax->register(XAJAX_FUNCTION, "cargarDetalleCosto");
$xajax->register(XAJAX_FUNCTION, "cargarFacturaCargo");
$xajax->register(XAJAX_FUNCTION, "eliminarAdicional");
$xajax->register(XAJAX_FUNCTION, "eliminarGasto");
$xajax->register(XAJAX_FUNCTION, "eliminarOtroCargo");
$xajax->register(XAJAX_FUNCTION, "guardarDcto");
$xajax->register(XAJAX_FUNCTION, "insertarAdicional");
$xajax->register(XAJAX_FUNCTION, "insertarGasto");
$xajax->register(XAJAX_FUNCTION, "insertarGastoImportacion");
$xajax->register(XAJAX_FUNCTION, "insertarOtroCargo");
$xajax->register(XAJAX_FUNCTION, "insertarPaquete");
$xajax->register(XAJAX_FUNCTION, "listaAdicional");
$xajax->register(XAJAX_FUNCTION, "listaGasto");
$xajax->register(XAJAX_FUNCTION, "listaGastoImportacion");
$xajax->register(XAJAX_FUNCTION, "listaMotivo");
$xajax->register(XAJAX_FUNCTION, "listaOtroCargo");
$xajax->register(XAJAX_FUNCTION, "listaPais");
$xajax->register(XAJAX_FUNCTION, "listaPaquete");
$xajax->register(XAJAX_FUNCTION, "listaProveedor");
$xajax->register(XAJAX_FUNCTION, "listaRegistroCompra");
$xajax->register(XAJAX_FUNCTION, "nuevoDcto");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice=>$valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return null;
}

function cargaLstArancelGrupoItm($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputCompleto\" style=\"min-width:60px\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = (doubleval($selId) == doubleval($row['porcentaje_grupo'])) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['porcentaje_grupo']."\">".number_format($row['porcentaje_grupo'], 2, ".", ",")."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstClaseItm($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT * FROM an_clase ORDER BY nom_clase");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_clase']) ? "selected=\"selected\"" : "";
			
		$html .= "<option ".$selected." value=\"".$row['id_clase']."\">".utf8_encode($row['nom_clase'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstColorItm($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT * FROM an_color ORDER BY nom_color");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_color']) ? "selected=\"selected\"" : "";
			
		$html .= "<option ".$selected." value=\"".$row['id_color']."\">".utf8_encode($row['nom_color'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstCondicionUnidadItm($nombreObjeto, $selId = "") {
	
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_condicion_unidad'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_condicion_unidad']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstUsoItm($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT * FROM an_uso ORDER BY nom_uso");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uso']) ? "selected=\"selected\"" : "";
			
		$html .= "<option ".$selected." value=\"".$row['id_uso']."\">".utf8_encode($row['nom_uso'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function errorGuardarDcto($objResponse) {
	$objResponse->script("
	byId('btnGuardar').disabled = false;
	byId('btnAprobar').disabled = false;
	byId('btnCancelar').disabled = false;");
}

function guardarDctoImportacion($idFactura, $frmDcto, $frmTotalDcto, $accionFactura) {
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjOtroCargo = $frmTotalDcto['cbxOtroCargo'];
	
	if ($accionFactura == "PREREGISTRO") {
		// VERIFICA SI LOS CARGOS ALMACENADOS EN LA BD EN LA FACTURA DE COMPRA AUN ESTAN AGREGADOS EN EL FORMULARIO
		$query = sprintf("SELECT * FROM an_factura_compra_gasto fact_comp_gasto
		WHERE fact_comp_gasto.id_factura_compra = %s
			AND fact_comp_gasto.id_modo_gasto = 2;",
			valTpDato($idFactura, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$existRegDet = false;
			if (isset($arrayObjOtroCargo)) {
				foreach ($arrayObjOtroCargo as $indice => $valor) {
					if ($row['id_factura_compra_gasto'] == $frmTotalDcto['hddIdFacturaCompraGasto'.$valor]) {
						$existRegDet = true;
					}
				}
			}
			
			if ($existRegDet == false) {
				$deleteSQL = sprintf("DELETE FROM an_factura_compra_gasto WHERE id_factura_compra_gasto = %s;",
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
			$hddCondicionGasto = $frmTotalDcto['hddCondicionGasto'.$hddItmGasto]; // 1 = Real, 2 = Estimado
			$idFacturaCargo = $frmTotalDcto['hddIdFacturaCargo'.$hddItmGasto];
			
			$hddSubTotalFacturaGasto = str_replace(",", "", $frmTotalDcto['hddSubTotalFacturaGasto'.$hddItmGasto]);
			
			// INSERTA LOS CARGOS DE LA FACTURA
			if ($accionFactura == "PREREGISTRO") {
				if ($frmTotalDcto['hddIdFacturaCompraGasto'.$hddItmGasto] > 0) {
					$updateSQL = sprintf("UPDATE an_factura_compra_gasto SET
						id_gasto = %s,
						tipo = %s,
						porcentaje_monto = %s,
						monto = %s,
						monto_medida = %s,
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
						valTpDato($hddSubTotalFacturaGasto, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato("", "int"),
						valTpDato(0, "real_inglesa"),
						valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
						valTpDato("", "int"), // 1 = Peso
						valTpDato(0, "int"), // 0 = No, 1 = Si
						valTpDato($idFacturaCargo, "int"),
						valTpDato($hddCondicionGasto, "int"), // 1 = Real, 2 = Estimado;
						valTpDato($frmTotalDcto['hddIdFacturaCompraGasto'.$hddItmGasto], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				} else {
					$insertSQL = sprintf("INSERT INTO an_factura_compra_gasto (id_factura_compra, id_gasto, tipo, porcentaje_monto, monto, monto_medida, id_iva, iva, id_modo_gasto, id_tipo_medida, afecta_documento, id_factura_compra_cargo, id_condicion_gasto)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($frmTotalDcto['hddIdGastoCargo'.$hddItmGasto], "int"),
						valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
						valTpDato($frmTotalDcto['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
						valTpDato($hddSubTotalFacturaGasto, "real_inglesa"),
						valTpDato(0, "real_inglesa"),
						valTpDato("", "int"),
						valTpDato(0, "real_inglesa"),
						valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
						valTpDato("", "int"), // 1 = Peso
						valTpDato(0, "int"), // 0 = No, 1 = Si
						valTpDato($idFacturaCargo, "int"),
						valTpDato($hddCondicionGasto, "int")); // 1 = Real, 2 = Estimado;
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			} else if ($accionFactura == "REGISTRO") {
				$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto, estatus_iva, id_iva, iva, id_modo_gasto, id_factura_compra_cargo, id_condicion_gasto)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['hddIdGastoCargo'.$hddItmGasto], "int"),
					valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
					valTpDato($frmTotalDcto['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
					valTpDato($hddSubTotalFacturaGasto, "real_inglesa"),
					valTpDato(0, "boolean"), // 0 = No, 1 = Si
					valTpDato("", "int"),
					valTpDato(0, "real_inglesa"),
					valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
					valTpDato($idFacturaCargo, "int"),
					valTpDato($hddCondicionGasto, "int")); // 1 = Real, 2 = Estimado;;
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	return array(true, "");
}

function guardarNotaCreditoPago($idFactura, $idNotaCargo, $idMotivo, $idRetencionCabezera = "", $porcIva = "", $porcRetencion = "", $ivaRetenido = "") {
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFactura = sprintf("SELECT * FROM cp_factura WHERE id_factura = %s;",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowFacturas = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	
	// BUSCA LOS DATOS DE LA NOTA DE CARGO
	$query = sprintf("SELECT * FROM cp_notadecargo WHERE id_notacargo = %s;",
		valTpDato($idNotaCargo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	// BUSCA LOS DATOS DE LA RETENCION
	$queryRetencion = sprintf("SELECT * FROM cp_retencioncabezera WHERE idRetencionCabezera = %s;",
		valTpDato($idRetencionCabezera, "int"));
	$rsRetencion = mysql_query($queryRetencion);
	if (!$rsRetencion) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsRetencion = mysql_num_rows($rsRetencion);
	$rowRetencion = mysql_fetch_assoc($rsRetencion);
	
	if ($idFactura > 0 && !($idNotaCargo > 0) && $idRetencionCabezera > 0) {
		// INSERTA EL PAGO DEBIDO A LA RETENCION
		$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idFactura, "int"),
			valTpDato("FA", "text"),
			valTpDato("RETENCION", "text"),
			valTpDato($idRetencionCabezera, "int"),
			valTpDato("NOW()", "campo"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($rowRetencion['numeroComprobante'], "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato($ivaRetenido, "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		$idModulo = 3; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(9, "int"), // 9 = Nota Crédito CxP
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
		mysql_query("SET NAMES 'latin1';");
		
		if ($idRetencionCabezera > 0) {
			$idProveedor = ($idNotaCargo > 0) ? $row['id_proveedor'] : $rowFactura['id_proveedor'];
			$observacionNC = "RETENCION DEL ".$porcRetencion."% DEL COMPROBANTE NRO. ".$rowRetencion['numeroComprobante']." SOBRE EL IMPUESTO DEL ".$porcIva."%, DE LA FACTURA DE COMPRA NRO. ".$rowFactura['numero_factura_proveedor'];
			$precioUnitario = $ivaRetenido;
			$subTotalNC = $precioUnitario;
			$subTotalDescuentoNC = 0;
			$montoExoneradoNC = 0;
			$montoExentoNC = 0;
			$totalCuentaPagarNC = $subTotalNC - $subTotalDescuentoNC;
			
			$idDocumentoPago = ($idNotaCargo > 0) ? $idNotaCargo : $idFactura;
			$tipoDocumentoPago = ($idNotaCargo > 0) ? "ND" : "FA";
			$montoCancelado = $ivaRetenido;
		} else {
			$idProveedor = $rowFactura['id_proveedor'];
			$observacionNC = "VEHICULO POR PLAN MAYOR, CUENTA POR PAGAR REFLEJADA EN LA NOTA DE DEBITO NRO. ".$row['numero_notacargo'];
			$precioUnitario = $rowFactura['subtotal_factura'];
			$subTotalNC = $precioUnitario;
			$subTotalDescuentoNC = $rowFactura['subtotal_descuento'];
			$montoExoneradoNC = $rowFactura['monto_exonerado'];
			$montoExentoNC = $rowFactura['monto_exento'];
			$totalCuentaPagarNC = $subTotalNC - $subTotalDescuentoNC;
			
			$idDocumentoPago = $idFactura;
			$tipoDocumentoPago = "FA";
			$montoCancelado = $rowFactura['saldo_factura'];
		}
		
		// INSERTA LA NOTA DE CREDITO
		$insertSQL = sprintf("INSERT INTO cp_notacredito (id_empresa, numero_nota_credito, numero_control_notacredito, fecha_notacredito, fecha_registro_notacredito, id_proveedor, id_departamento_notacredito, id_documento, tipo_documento, estado_notacredito, observacion_notacredito, monto_exento_notacredito, monto_exonerado_notacredito, subtotal_notacredito, subtotal_descuento, total_cuenta_pagar, saldo_notacredito, aplica_libros_notacredito, id_empleado_creador)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($numeroActual, "text"),
			valTpDato($numeroActual, "text"),
			valTpDato(date("Y-m-d", strtotime($rowFactura['fecha_origen'])), "date"),
			valTpDato(date("Y-m-d", strtotime($rowFactura['fecha_origen'])), "date"),
			valTpDato($idProveedor, "int"),
			valTpDato($idModulo, "int"),
			valTpDato(0, "int"),
			valTpDato("NC", "text"),
			valTpDato(3, ""), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
			valTpDato($observacionNC, "text"),
			valTpDato($montoExentoNC, "real_inglesa"),
			valTpDato($montoExoneradoNC, "real_inglesa"),
			valTpDato($subTotalNC, "real_inglesa"),
			valTpDato($subTotalDescuentoNC, "real_inglesa"),
			valTpDato($totalCuentaPagarNC, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, ""), // 0 = No, 1 = Si
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCredito = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA EL DETALLE DEL DOCUMENTO
		$insertSQL = sprintf("INSERT INTO cp_notacredito_detalle_motivo (id_notacredito, id_motivo, precio_unitario)
		VALUE (%s, %s, %s);",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idMotivo, "int"),
			valTpDato($precioUnitario, "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idNotaCreditoDetalle = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL MOTIVO DE LA NOTA DE DEBITO
		$updateSQL = sprintf("UPDATE cp_notacredito SET
			id_motivo = %s
		WHERE id_notacredito = %s;",
			valTpDato($idMotivo, "int"),
			valTpDato($idNotaCredito, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA EL PAGO DE LA NOTA DE CREDITO A LA NOTA DE CARGO
		$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idDocumentoPago, "int"),
			valTpDato($tipoDocumentoPago, "text"),
			valTpDato("NC", "text"),
			valTpDato($idNotaCredito, "int"),
			valTpDato("NOW()", "campo"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($numeroActual, "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato($montoCancelado, "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	return array(true, $idNotaCredito, $idModulo);
}

function guardarUnidadFisica($idUnidadFisica, $idFacturaDetalle, $idFacturaDetalleCxC, $contFila, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	
	// VERIFICA QUE NO EXISTA EL SERIAL DEL CHASIS, CARROCERIA Y MOTOR
	$query = sprintf("SELECT * FROM an_unidad_fisica
	WHERE (serial_carroceria LIKE %s)
		AND estatus = 1
		AND id_unidad_fisica <> %s;", // OR serial_motor LIKE %s OR serial_chasis LIKE %s
		valTpDato($frmListaArticulo['txtSerialCarroceria'.$contFila], "text")/*,
		valTpDato($frmListaArticulo['txtSerialMotor'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtNumeroVehiculo'.$contFila], "text")*/,
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		return array(false, "Ya existe una unidad con alguno de los datos de ".$spanSerialCarroceria.", ".$spanSerialMotor." o Nro. Vehículo ingresados");
	}
	
	// VERIFICA QUE NO EXISTA LA PLACA
	$query = sprintf("SELECT * FROM an_unidad_fisica
	WHERE placa LIKE %s
		AND estatus = 1
		AND id_unidad_fisica <> %s;",
		valTpDato($frmListaArticulo['txtPlaca'], "text"),
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0 && $idModoCompra == 1) {
		return $objResponse->alert("Ya existe una unidad con los datos de ".$spanPlaca." ingresados");
	}
	
	$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET 
		id_factura_compra_detalle_unidad = %s
	WHERE id_unidad_fisica = %s;",
		valTpDato($idFacturaDetalle, "int"),
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
		id_uso = %s,
		id_clase = %s,
		id_condicion_unidad = %s,
		id_color_externo1 = %s,
		id_color_interno1 = %s,
		id_origen = %s,
		placa = %s,
		serial_carroceria = %s,
		serial_motor = %s,
		serial_chasis = %s,
		fecha_fabricacion = %s,
		registro_legalizacion = %s,
		registro_federal = %s,
		
		moneda_costo_compra = %s,
		tasa_cambio_costo_compra = %s,
		moneda_precio_compra = %s,
		tasa_cambio_precio_compra = %s,
		
		marca_cilindro = %s,
		capacidad_cilindro = %s,
		fecha_elaboracion_cilindro = %s,
		marca_kit = %s,
		modelo_regulador = %s,
		serial_regulador = %s,
		codigo_unico_conversion = %s,
		serial1 = %s,
		fecha_ingreso = %s
	WHERE id_unidad_fisica = %s;",
		valTpDato($frmListaArticulo['lstUso'.$contFila], "int"),
		valTpDato($frmListaArticulo['lstClase'.$contFila], "int"),
		valTpDato($frmListaArticulo['lstCondicionUnidad'.$contFila], "int"),
		valTpDato($frmListaArticulo['lstColorExterno'.$contFila], "int"),
		valTpDato($frmListaArticulo['lstColorInterno'.$contFila], "int"),
		valTpDato($frmTotalDcto['txtIdPaisOrigen'.$contFila], "int"),
		valTpDato($frmListaArticulo['txtPlaca'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtSerialCarroceria'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtSerialMotor'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtNumeroVehiculo'.$contFila], "text"),
		valTpDato(date("Y-m-d", strtotime($frmListaArticulo['txtFechaFabricacion'.$contFila])), "date"),
		valTpDato($frmListaArticulo['txtRegistroLegalizacion'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtRegistroFederal'.$contFila], "text"),
		
		valTpDato($idMonedaOrigen, "int"),
		valTpDato($frmDcto['txtTasaCambio'], "real_inglesa"),
		valTpDato($idMonedaOrigen, "int"),
		valTpDato($frmDcto['txtTasaCambio'], "real_inglesa"),
		
		valTpDato($frmListaArticulo['txtMarcaCilindro'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtCapacidadCilindro'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtFechaCilindro'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtMarcaKit'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtModeloRegulador'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtSerialRegulador'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtCodigoUnico'.$contFila], "text"),
		valTpDato($frmListaArticulo['txtSerial1'.$contFila], "text"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($idUnidadFisica, "int"));//return array(false, $updateSQL);
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis, cp_factura_detalle_unidad fact_det_unidad, an_factura_compra_detalle_unidad fact_comp_det_unidad SET
		descuento_compra = %s,
		porcentaje_iva_compra = %s,
		iva_compra = IFNULL((SELECT
						iva.iva * (CASE uni_fis.estado_compra
										WHEN 'COMPRADO' THEN
											fact_comp_det_unidad.costo_unitario
										WHEN 'REGISTRADO' THEN
											fact_det_unidad.costo_unitario
									END) / 100
					FROM an_uni_bas uni_bas
						INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (uni_bas.id_uni_bas = uni_bas_impuesto.id_unidad_basica)
						INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
					WHERE iva.tipo IN (1)
						AND uni_bas.id_uni_bas = uni_fis.id_uni_bas), 0),
		porcentaje_impuesto_lujo_compra = %s,
		impuesto_lujo_compra = IFNULL((SELECT iva.iva * (CASE uni_fis.estado_compra
															WHEN 'COMPRADO' THEN
																fact_comp_det_unidad.costo_unitario
															WHEN 'REGISTRADO' THEN
																fact_det_unidad.costo_unitario
														END) / 100
								FROM an_uni_bas uni_bas
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (uni_bas.id_uni_bas = uni_bas_impuesto.id_unidad_basica)
									INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
								WHERE iva.tipo IN (3)
									AND uni_bas.id_uni_bas = uni_fis.id_uni_bas), 0),
		costo_compra = (CASE uni_fis.estado_compra
							WHEN 'COMPRADO' THEN
								fact_comp_det_unidad.costo_unitario
							WHEN 'REGISTRADO' THEN
								fact_det_unidad.costo_unitario
						END),
		precio_compra = (CASE uni_fis.estado_compra
							WHEN 'COMPRADO' THEN
								(CASE uni_fis.tasa_cambio_precio_compra
									WHEN 1 THEN
										uni_fis.tasa_cambio_precio_compra *
											(fact_comp_det_unidad.costo_unitario
											+ IFNULL((SELECT SUM(fact_comp_gasto.monto) FROM an_factura_compra_gasto fact_comp_gasto
													WHERE fact_comp_gasto.id_modo_gasto IN (1)
														AND fact_comp_gasto.id_factura_compra = fact_comp_det_unidad.id_factura_compra), 0))
									ELSE 
										fact_comp_det_unidad.costo_unitario
								END)
							WHEN 'REGISTRADO' THEN
								(SELECT
									IFNULL(kardex.costo, 0)
									- IFNULL(kardex.subtotal_descuento, 0)
									+ IFNULL(kardex.costo_cargo, 0)
								FROM an_kardex kardex
								WHERE kardex.idUnidadFisica = uni_fis.id_unidad_fisica
									AND tipoMovimiento IN (1))
						END)
	WHERE id_unidad_fisica = %s
		AND ((uni_fis.estado_compra IN ('COMPRADO') AND uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_compra_detalle_unidad)
			OR (uni_fis.estado_compra IN ('REGISTRADO') AND uni_fis.id_factura_compra_detalle_unidad = fact_det_unidad.id_factura_detalle_unidad));",
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtIva0'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtIva1'], "real_inglesa"),
		
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	return array(true, "");
}

function insertarItemAdicional($contFila, $hddIdAccesorioItm, $idFacturaDetalle = "") {
	$contFila++;
	
	if ($idFacturaDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT *
		FROM an_factura_compra_detalle_accesorio fact_comp_det_acc
			INNER JOIN an_accesorio acc ON (fact_comp_det_acc.id_accesorio = acc.id_accesorio)
		WHERE fact_comp_det_acc.id_factura_compra_detalle_accesorio = %s;", 
			valTpDato($idFacturaDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdAccesorioItm = $rowPedidoDet['id_accesorio'];
		$txtCantRecibItm = 1;
		$txtCostoItm = $rowPedidoDet['costo_unitario'];
		$gastoUnitario = $rowPedidoDet['gasto_unitario'];
		$pesoUnitario = $rowPedidoDet['peso_unitario'];
		$hddIdArancelFamiliaItm = $rowPedidoDet['id_arancel_familia'];
		$lstTarifaAdValorem = $rowPedidoDet['porcentaje_grupo'];
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1
			AND (SELECT COUNT(*) FROM an_accesorio
				WHERE id_accesorio = %s
					AND iva_accesorio = 1) > 0;", 
			valTpDato($hddIdAccesorioItm, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
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
	} else {
		$txtCantRecibItm = 1;
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1
			AND (SELECT COUNT(*) FROM an_accesorio
				WHERE id_accesorio = %s
					AND iva_accesorio = 1) > 0;", 
			valTpDato($hddIdAccesorioItm, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
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
	}
	
	// BUSCA LOS DATOS DEL ADICIONAL
	$query = sprintf("SELECT * FROM an_accesorio WHERE id_accesorio = %s;",
		valTpDato($hddIdAccesorioItm, "int"));
	$rs = mysql_query($query);
	if (!$rs) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
	$row = mysql_fetch_assoc($rs);
	
	$codigoUnidad = "-";
	$descripUnidad = $row['nom_accesorio'];
	$txtCostoItm = ($totalRowsPedidoDet > 0) ? $txtCostoItm : $row['costo_accesorio'];
	
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
	WHERE id_arancel_familia = %s;", 
		valTpDato($row['id_arancel_familia'], "int"));
	$rsArancelFamilia = mysql_query($queryArancelFamilia);
	if (!$rsArancelFamilia) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
	
	$arancelArticulo = sprintf("<span class=\"textoNegroNegrita_10px\" title=\"%s\">%s</span>", 
		utf8_encode($rowArancelFamilia['descripcion_arancel']), 
		utf8_encode($rowArancelFamilia['codigo_arancel']));
	
	$hddIdArancelFamiliaItm = ($hddIdArancelFamiliaItm > 0) ? $hddIdArancelFamiliaItm : $rowArancelFamilia['id_arancel_familia'];
	$lstTarifaAdValorem = ($lstTarifaAdValorem != "") ? $lstTarifaAdValorem : $rowArancelFamilia['porcentaje_grupo'];
	
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
			"<td><a class=\"modalImg\" id=\"aEditarItm:%s\" rel=\"#divFlotante1\" style=\"display:none\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a></td>".
			"<td>%s</td>".
			"<td id=\"tdCodigoArticuloItm%s\">%s</td>".
			"<td><div id=\"divDescripcionArticuloItm%s\">%s</div>%s</td>".
			"<td><input type=\"text\" id=\"txtCantRecibItm%s\" name=\"txtCantRecibItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCostoItm%s\" name=\"txtCostoItm%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdArancelFamiliaItm%s\" name=\"hddIdArancelFamiliaItm%s\" class=\"inputSinFondo\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtPesoItm%s\" name=\"txtPesoItm%s\" class=\"inputSinFondo\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtGastosItm%s\" name=\"txtGastosItm%s\" class=\"inputSinFondo\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaDetalleItm%s\" name=\"hddIdFacturaDetalleItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdAccesorioItm%s\" name=\"hddIdAccesorioItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosImportNacItm%s\" name=\"hddGastosImportNacItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosImportItm%s\" name=\"hddGastosImportItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdClienteItm%s\" name=\"hddIdClienteItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtCostoItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('aEditarItm:%s').onclick = function() { abrirDivFlotante1(this, 'tblArticulo', '%s'); }
		byId('%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
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
			$contFila, utf8_encode($codigoUnidad), 
			$contFila, utf8_encode($descripUnidad), $arancelArticulo, 
			$contFila, $contFila, number_format($txtCantRecibItm, 2, ".", ","), 
			$contFila, $contFila, number_format($txtCostoItm, 2, ".", ","), 
			$ivaUnidad, 
			cargaLstArancelGrupoItm("lstTarifaAdValorem".$contFila, $lstTarifaAdValorem), 
				$contFila, $contFila, $hddIdArancelFamiliaItm, 
			$contFila, $contFila, number_format($pesoUnitario, 2, ".", ","),
			$contFila, $contFila, number_format(($txtCantRecibItm * $gastoUnitario), 3, ".", ","),
			$contFila, $contFila, number_format(($txtCantRecibItm * $txtCostoItm), 2, ".", ","), 
				$contFila, $contFila, $idFacturaDetalle, 
				$contFila, $contFila, $hddIdAccesorioItm, 
				$contFila, $contFila, number_format(0, 2, ".", ","), 
				$contFila, $contFila, number_format(0, 2, ".", ","), 
				$contFila, $contFila, $hddIdClienteItm, 
		
		$contFila, 
		$contFila, $contFila, 
		"lstTarifaAdValorem".$contFila, 
		
		$contFila,
		$contFila,
				
		$contFila, $htmlCostosArt, $contFila, 
		$contFila);
	
	if ($hddIdClienteItm > 0) {
		$htmlItmPie .= sprintf("
		byId('aClienteItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblCliente', '%s'); }", 
			$contFila, $hddIdClienteItm);
	}
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemGasto($contFila, $hddIdGasto, $hddIdFacturaCompraGasto = "") {
	$contFila++;
	
	if ($hddIdFacturaCompraGasto > 0) {
		$queryPedidoDet = sprintf("SELECT fact_comp_gasto.*
		FROM pg_iva iva
			RIGHT JOIN pg_gastos gasto ON (iva.idIva = gasto.id_iva)
			INNER JOIN an_factura_compra_gasto fact_comp_gasto ON (gasto.id_gasto = fact_comp_gasto.id_gasto)
		WHERE fact_comp_gasto.id_factura_compra_gasto = %s;", 
			valTpDato($hddIdFacturaCompraGasto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdGasto = $rowPedidoDet['id_gasto'];
		$hddIvaGasto = $rowPedidoDet['iva'];
		$hddIdIvaGasto = $rowPedidoDet['id_iva'];
		//$hddEstatusIvaGasto = $rowPedidoDet['estatus_iva'];
		$txtPorcGasto = $rowPedidoDet['porcentaje_monto'];
		$txtMontoGasto = $rowPedidoDet['monto'];
		$txtMedidaGasto = $rowPedidoDet['monto_medida'];
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
	
	$hddIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIvaGasto : $rowGasto['iva'];
	$hddIdIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIdIvaGasto : $rowGasto['id_iva'];
	$hddEstatusIvaGasto = ($hddEstatusIvaGasto != "" && $totalRowsPedidoDet > 0) ? $hddEstatusIvaGasto : $rowGasto['estatus_iva'];
	
	$displayTblIvaGasto = ($rowGasto['id_iva'] > 0) ? "" : "style=\"display:none\"";
	
	$htmlIva .= sprintf("<table id=\"tblIvaGasto%s\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" %s width=\"%s\">",
		$contFila,
		$displayTblIvaGasto,
		"100%");
	$htmlIva .= "<tr>";
		$htmlIva .= "<td>"."<img src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/>"."</td>";
		$htmlIva .= "<td align=\"right\">";
			$htmlIva .= sprintf("<input type=\"text\" id=\"hddIvaGasto%s\" name=\"hddIvaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">".
			"<input type=\"hidden\" id=\"hddIdIvaGasto%s\" name=\"hddIdIvaGasto%s\" value=\"%s\">".
			"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s\" name=\"hddEstatusIvaGasto%s\" value=\"%s\">",
				$contFila, $contFila, $hddIvaGasto,
				$contFila, $contFila, $hddIdIvaGasto,
				$contFila, $contFila, $hddEstatusIvaGasto);
		$htmlIva .= "</td>";
		$htmlIva .= "<td>%</td>";
	$htmlIva .= "</tr>";
	$htmlIva .= "</table>";
	
	
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
			"<td title=\"trItmGasto:%s\"><input id=\"cbxItmGasto%s\" name=\"cbxItmGasto[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"tituloCampo\">%s:</td>".
			"<td><input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\">%s</td>".
			"<td id=\"spnGastoMoneda%s\"></td>".
			"<td><input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td %s><input type=\"text\" id=\"txtMedidaGasto%s\" name=\"txtMedidaGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s%s".
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
			$contFila, $contFila, $contFila,
				$contFila,
			$rowGasto['nombre'],
			$contFila, $contFila, number_format($txtPorcGasto, 2, ".", ","), "%",
			$contFila,
			$contFila, $contFila, number_format($txtMontoGasto, 2, ".", ","),
			(($rowGasto['id_tipo_medida'] == 1) ? "title=\"Peso Total (g)\"" : ""), $contFila, $contFila, number_format($txtMedidaGasto, 2, ".", ","),
			$htmlIva, $htmlAfecta,
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
		$queryPedidoDet = sprintf("SELECT fact_comp_gasto.*
		FROM pg_iva iva
			RIGHT JOIN pg_gastos gasto ON (iva.idIva = gasto.id_iva)
			INNER JOIN an_factura_compra_gasto fact_comp_gasto ON (gasto.id_gasto = fact_comp_gasto.id_gasto)
		WHERE fact_comp_gasto.id_factura_compra_gasto = %s;", 
			valTpDato($hddIdFacturaCompraGasto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdGasto = $rowPedidoDet['id_gasto'];
		$hddIvaGasto = $rowPedidoDet['iva'];
		$hddIdIvaGasto = $rowPedidoDet['id_iva'];
		//$hddEstatusIvaGasto = $rowPedidoDet[''];
		$txtPorcGasto = $rowPedidoDet['porcentaje_monto'];
		$txtMontoGasto = $rowPedidoDet['monto'];
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
	
	$hddIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIvaGasto : $rowGasto['iva'];
	$hddIdIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIdIvaGasto : $rowGasto['id_iva'];
	$hddEstatusIvaGasto = ($totalRowsPedidoDet > 0) ? $hddEstatusIvaGasto : $rowGasto['estatus_iva'];
	
	$displayTblIvaGasto = ($rowGasto['id_iva'] > 0) ? "" : "style=\"display:none\"";
	
	$htmlIva .= sprintf("<table id=\"tblIvaGasto%s\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" %s width=\"%s\">",
		$contFila,
		$displayTblIvaGasto,
		"100%");
	$htmlIva .= "<tr>";
		$htmlIva .= "<td>"."<img src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/>"."</td>";
		$htmlIva .= "<td align=\"right\">";
			$htmlIva .= sprintf("<input type=\"text\" id=\"hddIvaGasto%s\" name=\"hddIvaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">".
			"<input type=\"hidden\" id=\"hddIdIvaGasto%s\" name=\"hddIdIvaGasto%s\" value=\"%s\">".
			"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s\" name=\"hddEstatusIvaGasto%s\" value=\"%s\">",
				$contFila, $contFila, $hddIvaGasto,
				$contFila, $contFila, $hddIdIvaGasto,
				$contFila, $contFila, $hddEstatusIvaGasto);
		$htmlIva .= "</td>";
		$htmlIva .= "<td>%</td>";
	$htmlIva .= "</tr>";
	$htmlIva .= "</table>";
	
	
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
			"<td>%s%s".
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
			$htmlIva, $htmlAfecta,
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
			fact_comp_gasto.id_factura_compra_gasto,
			fact_comp_cargo.id_factura,
			gasto.id_gasto,
			gasto.nombre,
			fact_comp_cargo.fecha_origen,
			fact_comp_cargo.numero_factura_proveedor,
			fact_comp_cargo.numero_control_factura,
			prov.id_proveedor,
			prov.nombre AS nombre_proveedor,
			fact_comp_gasto.monto,
			fact_comp_gasto.id_condicion_gasto,
			fact_comp_gasto.id_expediente_detalle_cargo
		FROM an_factura_compra_gasto fact_comp_gasto
			LEFT JOIN cp_factura fact_comp_cargo ON (fact_comp_gasto.id_factura_compra_cargo = fact_comp_cargo.id_factura)
			INNER JOIN pg_gastos gasto ON (fact_comp_gasto.id_gasto = gasto.id_gasto)
			LEFT JOIN cp_proveedor prov ON (fact_comp_cargo.id_proveedor = prov.id_proveedor)
		WHERE fact_comp_gasto.id_factura_compra_gasto = %s;",
			valTpDato($idFacturaCompraGasto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	}
	
	$txtFechaFacturaGasto = ($txtFechaFacturaGasto == "" && $totalRows > 0) ? date(spanDateFormat,strtotime($row['fecha_origen'])) : $txtFechaFacturaGasto;
	$txtNumeroFacturaGasto = ($txtNumeroFacturaGasto == "" && $totalRows > 0) ? $row['numero_factura_proveedor'] : $txtNumeroFacturaGasto;
	$txtNumeroControlFacturaGasto = ($txtNumeroControlFacturaGasto == "" && $totalRows > 0) ? $row['numero_control_factura'] : $txtNumeroControlFacturaGasto;
	$txtIdProvFacturaGasto = ($txtIdProvFacturaGasto == "" && $totalRows > 0) ? $row['id_proveedor'] : $txtIdProvFacturaGasto;
	$txtNombreProvFacturaGasto = ($txtNombreProvFacturaGasto == "" && $totalRows > 0) ? $row['nombre_proveedor'] : $txtNombreProvFacturaGasto;
	$hddSubTotalFacturaGasto = ($hddSubTotalFacturaGasto == "" && $totalRows > 0) ? $row['monto'] : $hddSubTotalFacturaGasto;
	$hddCondicionGasto = ($hddCondicionGasto == "" && $totalRows > 0) ? $row['id_condicion_gasto'] : $hddCondicionGasto;
	$hddIdFacturaCargo = ($hddIdFacturaCargo == "" && $totalRows > 0) ? $row['id_factura'] : $hddIdFacturaCargo;
	$hddIdGastoCargo = ($hddIdGastoCargo == "" && $totalRows > 0) ? $row['id_gasto'] : $hddIdGastoCargo;
	$hddIdFacturaCompraGasto = ($hddIdFacturaCompraGasto == "" && $totalRows > 0) ? $row['id_factura_compra_gasto'] : $hddIdFacturaCompraGasto;
	
	// BUSCA LOS DATOS DEL GASTO
	$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
		valTpDato($hddIdGastoCargo, "int"));
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowGastos = mysql_fetch_assoc($rsGasto);
	
	if (!($hddCondicionGasto > 0)) {
		$hddCondicionGasto = ($rowGastos['asocia_documento'] == 1) ? 1 : 2; // 1 = Real, 2 = Estimado
	}
	$cbxItmOtroCargo = ($row['id_expediente_detalle_cargo'] > 0) ? "" : sprintf("<input id=\"cbxItmOtroCargo\" name=\"cbxItmOtroCargo[]\" type=\"checkbox\" value=\"%s\"/>",
		$contFila);
	$display = ($row['id_expediente_detalle_cargo'] > 0) ? "style=\"display:none\"" : ""; 
	
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
			"<td><input type=\"text\" id=\"hddSubTotalFacturaGasto%s\" name=\"hddSubTotalFacturaGasto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddCondicionGasto%s\" name=\"hddCondicionGasto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaCargo%s\" name=\"hddIdFacturaCargo%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdGastoCargo%s\" name=\"hddIdGastoCargo%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaCompraGasto%s\" name=\"hddIdFacturaCompraGasto%s\" value=\"%s\"/></td>".
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
			$contFila, $contFila, number_format($hddSubTotalFacturaGasto, 2, ".", ","),
				$contFila, $contFila, $hddCondicionGasto,
				$contFila, $contFila, $hddIdFacturaCargo,
				$contFila, $contFila, $hddIdGastoCargo,
				$contFila, $contFila, $hddIdFacturaCompraGasto,
		
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemUnidad($contFila, $idPedidoDetalle, $idFacturaDetalle = "") {
	$contFila++;
	
	global $arrayValidarCarroceria;
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	global $spanRegistroLegalizacion;
	
	if ($idPedidoDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT
			uni_fis.id_pedido_compra_detalle,
			
			ped_comp_det.idUnidadBasica, 
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
			uni_fis.fecha_fabricacion,
			uni_fis.registro_legalizacion, 
			uni_fis.registro_federal, 
			uni_fis.id_condicion_unidad, 
			uni_fis.id_clase, 
			uni_fis.id_uso, 
			uni_fis.id_color_externo1, 
			uni_fis.id_color_interno1, 
			almacen.nom_almacen, 
			
			(SELECT combustible.id_combustible
			FROM an_uni_bas uni_bas
				INNER JOIN an_combustible combustible ON (uni_bas.com_uni_bas = combustible.id_combustible)
			WHERE uni_bas.id_uni_bas = ped_comp_det.idUnidadBasica) AS id_combustible, 
			
			uni_fis.serial1, 
			uni_fis.codigo_unico_conversion, 
			uni_fis.marca_kit, 
			uni_fis.modelo_regulador, 
			uni_fis.serial_regulador, 
			uni_fis.marca_cilindro, 
			uni_fis.capacidad_cilindro, 
			uni_fis.fecha_elaboracion_cilindro, 
			
			(SELECT uni_bas.pvp_costo FROM an_uni_bas uni_bas
			WHERE uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas) AS costo_unidad
		FROM an_solicitud_factura ped_comp_det
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
			INNER JOIN an_almacen almacen ON (uni_fis.id_almacen = almacen.id_almacen)
		WHERE ped_comp_det.idSolicitud = %s;", 
			valTpDato($idPedidoDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdUnidadBasicaItm = $rowPedidoDet['idUnidadBasica'];
		$hddIdUnidadFisicaItm = $rowPedidoDet['id_unidad_fisica'];
		$codigoUnidad = $rowPedidoDet['nom_uni_bas'];
		$txtCantRecibItm = 1;
		$txtCostoItm = $rowPedidoDet['costo_unidad'];
		$gastoUnitario = $rowPedidoDet['gasto_unitario'];
		$pesoUnitario = $rowPedidoDet['peso_unitario'];
		$hddIdArancelFamiliaItm = $rowPedidoDet['id_arancel_familia'];
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
			INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo IN (1,8,3);", 
			valTpDato($hddIdUnidadBasicaItm, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
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
	} else if ($idFacturaDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT
			uni_fis.id_pedido_compra_detalle,
			
			ped_comp_det.idUnidadBasica, 
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
			uni_fis.fecha_fabricacion,
			uni_fis.registro_legalizacion, 
			uni_fis.registro_federal, 
			uni_fis.id_condicion_unidad, 
			uni_fis.id_clase, 
			uni_fis.id_uso, 
			uni_fis.id_color_externo1, 
			uni_fis.id_color_interno1, 
			almacen.nom_almacen, 
			
			(SELECT combustible.id_combustible
			FROM an_uni_bas uni_bas
				INNER JOIN an_combustible combustible ON (uni_bas.com_uni_bas = combustible.id_combustible)
			WHERE uni_bas.id_uni_bas = ped_comp_det.idUnidadBasica) AS id_combustible, 
			
			uni_fis.serial1, 
			uni_fis.codigo_unico_conversion, 
			uni_fis.marca_kit, 
			uni_fis.modelo_regulador, 
			uni_fis.serial_regulador, 
			uni_fis.marca_cilindro, 
			uni_fis.capacidad_cilindro, 
			uni_fis.fecha_elaboracion_cilindro, 
			
			fact_comp_det_unidad.costo_unitario,
			fact_comp_det_unidad.gasto_unitario,
			fact_comp_det_unidad.peso_unitario,
			fact_comp_det_unidad.id_arancel_familia,
			fact_comp_det_unidad.porcentaje_grupo
		FROM an_solicitud_factura ped_comp_det
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
			INNER JOIN an_almacen almacen ON (uni_fis.id_almacen = almacen.id_almacen)
			INNER JOIN an_factura_compra_detalle_unidad fact_comp_det_unidad ON (ped_comp_det.idSolicitud = fact_comp_det_unidad.id_pedido_compra_detalle)
		WHERE fact_comp_det_unidad.id_factura_compra_detalle_unidad = %s;", 
			valTpDato($idFacturaDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$idPedidoDetalle = $rowPedidoDet['id_pedido_compra_detalle'];
		$hddIdUnidadBasicaItm = $rowPedidoDet['idUnidadBasica'];
		$hddIdUnidadFisicaItm = $rowPedidoDet['id_unidad_fisica'];
		$codigoUnidad = $rowPedidoDet['nom_uni_bas'];
		$txtCantRecibItm = 1;
		$txtCostoItm = $rowPedidoDet['costo_unitario'];
		$gastoUnitario = $rowPedidoDet['gasto_unitario'];
		$pesoUnitario = $rowPedidoDet['peso_unitario'];
		$hddIdArancelFamiliaItm = $rowPedidoDet['id_arancel_familia'];
		$lstTarifaAdValorem = $rowPedidoDet['porcentaje_grupo'];
		
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
			INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
		WHERE uni_bas_impuesto.id_unidad_basica = %s
			AND iva.tipo IN (1,8,3);", 
			valTpDato($hddIdUnidadBasicaItm, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
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
	}
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryUnidadBasica = sprintf("SELECT * FROM an_uni_bas WHERE id_uni_bas = %s;",
		valTpDato($hddIdUnidadBasicaItm, "int"));
	$rsUnidadBasica = mysql_query($queryUnidadBasica);
	if (!$rsUnidadBasica) return array(false, mysql_error()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
	$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
	
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
	WHERE id_arancel_familia = %s;", 
		valTpDato($rowUnidadBasica['id_arancel_familia'], "int"));
	$rsArancelFamilia = mysql_query($queryArancelFamilia);
	if (!$rsArancelFamilia) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
	
	$arancelArticulo = sprintf("<span class=\"textoNegroNegrita_10px\" title=\"%s\">%s</span>", 
		utf8_encode($rowArancelFamilia['descripcion_arancel']), 
		utf8_encode($rowArancelFamilia['codigo_arancel']));
	
	$hddIdArancelFamiliaItm = ($hddIdArancelFamiliaItm > 0) ? $hddIdArancelFamiliaItm : $rowArancelFamilia['id_arancel_familia'];
	$lstTarifaAdValorem = ($lstTarifaAdValorem != "") ? $lstTarifaAdValorem : $rowArancelFamilia['porcentaje_grupo'];
	
	$descripUnidad = "<table width=\"100%\">";
	$descripUnidad .= "<tr height=\"22\">";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Marca:</td>"."<td>".utf8_encode($rowPedidoDet['nom_marca'])."</td>";
	$descripUnidad .= "</tr>";
	$descripUnidad .= "<tr height=\"22\">";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\" width=\"20%\">Modelo:</td>"."<td width=\"30%\">".utf8_encode($rowPedidoDet['nom_modelo'])."</td>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\" width=\"20%\">Versión:</td>"."<td width=\"30%\">".utf8_encode($rowPedidoDet['nom_version'])."</td>";
	$descripUnidad .= "</tr>";
	$descripUnidad .= "<tr>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Año:</td>";
		$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtAno%s\" name=\"txtAno%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"></td>", 
			$contFila, $contFila, $rowPedidoDet['nom_ano']);
		if (strlen($rowPedidoDet['placa']) > 0) {
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">".$spanPlaca.":</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtPlaca%s\" name=\"txtPlaca%s\" class=\"inputHabilitado\" style=\"text-align:left\" value=\"%s\"></td>", 
				$contFila, $contFila, utf8_encode($rowPedidoDet['placa']));
		}
	$descripUnidad .= "</tr>";
	$descripUnidad .= "<tr>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">".$spanSerialCarroceria.":</td>";
		$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtSerialCarroceria%s\" name=\"txtSerialCarroceria%s\" class=\"inputHabilitado\" maxlength=\"%s\" style=\"text-align:left\" value=\"%s\"></td>", 
			$contFila, $contFila, substr($arrayValidarCarroceria[0], -6,2), utf8_encode($rowPedidoDet['serial_carroceria']));
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">".$spanSerialMotor.":</td>";
		$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtSerialMotor%s\" name=\"txtSerialMotor%s\" class=\"inputHabilitado\" style=\"text-align:left\" value=\"%s\"></td>", 
			$contFila, $contFila, utf8_encode($rowPedidoDet['serial_motor']));
	$descripUnidad .= "</tr>";
	$descripUnidad .= "<tr>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Nro. Vehículo:</td>";
		$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtNumeroVehiculo%s\" name=\"txtNumeroVehiculo%s\" class=\"inputHabilitado\" style=\"text-align:left\" value=\"%s\"></td>", 
			$contFila, $contFila, utf8_encode($rowPedidoDet['serial_chasis']));
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Almacén:</td>";
		$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtAlmacen%s\" name=\"txtAlmacen%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"></td>", 
			$contFila, $contFila, utf8_encode($rowPedidoDet['nom_almacen']));
	$descripUnidad .= "</tr>";
	$descripUnidad .= "<tr>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Estado Vehículo:</td>";
		$descripUnidad .= "<td>".cargaLstCondicionUnidadItm("lstCondicionUnidad".$contFila, $rowPedidoDet['id_condicion_unidad'])."</td>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Fecha Fabricación:</td>";
		$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtFechaFabricacion%s\" name=\"txtFechaFabricacion%s\" class=\"inputHabilitado\" style=\"text-align:center\" value=\"%s\"></td>", 
			$contFila, $contFila, date(spanDateFormat,strtotime($rowPedidoDet['fecha_fabricacion'])));
	
	$descripUnidad .= "</tr>";
	$descripUnidad .= "<tr>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Clase:</td>";
		$descripUnidad .= "<td>".cargaLstClaseItm("lstClase".$contFila, $rowPedidoDet['id_clase'])."</td>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Uso:</td>";
		$descripUnidad .= "<td>".cargaLstUsoItm("lstUso".$contFila, $rowPedidoDet['id_uso'])."</td>";
	$descripUnidad .= "</tr>";
	$descripUnidad .= "<tr>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Color Carroceria:</td>";
		$descripUnidad .= "<td>".cargaLstColorItm("lstColorExterno".$contFila, $rowPedidoDet['id_color_externo1'])."</td>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Tipo Tapiceria:</td>";
		$descripUnidad .= "<td>".cargaLstColorItm("lstColorInterno".$contFila, $rowPedidoDet['id_color_interno1'])."</td>";
	$descripUnidad .= "</tr>";
	$descripUnidad .= "<tr>";
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>".$spanRegistroLegalizacion.":</td>";
		$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtRegistroLegalizacion%s\" name=\"txtRegistroLegalizacion%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
			$contFila, $contFila, $rowPedidoDet['registro_legalizacion']);
		$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\"><span class=\"textoRojoNegrita\">*</span>Registro Federal:</td>";
		$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtRegistroFederal%s\" name=\"txtRegistroFederal%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
			$contFila, $contFila, $rowPedidoDet['registro_federal']);
	$descripUnidad .= "</tr>";
	if ($rowPedidoDet['id_combustible'] == 2 || $rowPedidoDet['id_combustible'] == 5) {
		$descripUnidad .= "<tr><td align=\"center\" class=\"tituloArea\" colspan=\"4\">SISTEMA GNV</td></tr>";
		$descripUnidad .= "<tr>";
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Serial 1:</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtSerial1%s\" name=\"txtSerial1%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
				$contFila, $contFila, $rowPedidoDet['serial1']);
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Código Único:</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtCodigoUnico%s\" name=\"txtCodigoUnico%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
				$contFila, $contFila, $rowPedidoDet['codigo_unico_conversion']);
		$descripUnidad .= "</tr>";
		$descripUnidad .= "<tr>";
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Marca Kit:</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtMarcaKit%s\" name=\"txtMarcaKit%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
				$contFila, $contFila, $rowPedidoDet['marca_kit']);
		$descripUnidad .= "</tr>";
		$descripUnidad .= "<tr>";
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Modelo Regulador:</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtModeloRegulador%s\" name=\"txtModeloRegulador%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
				$contFila, $contFila, $rowPedidoDet['modelo_regulador']);
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Serial Regulador:</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtSerialRegulador%s\" name=\"txtSerialRegulador%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
				$contFila, $contFila, $rowPedidoDet['serial_regulador']);
		$descripUnidad .= "</tr>";
		$descripUnidad .= "<tr>";
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Marca Cilindro:</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtMarcaCilindro%s\" name=\"txtMarcaCilindro%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
				$contFila, $contFila, $rowPedidoDet['marca_cilindro']);
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Capacidad Cilindro (NG):</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtCapacidadCilindro%s\" name=\"txtCapacidadCilindro%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
				$contFila, $contFila, $rowPedidoDet['capacidad_cilindro']);
		$descripUnidad .= "</tr>";
		$descripUnidad .= "<tr>";
			$descripUnidad .= "<td align=\"right\" class=\"tituloCampo\">Fecha Elab. Cilindro:</td>";
			$descripUnidad .= sprintf("<td><input type=\"text\" id=\"txtFechaCilindro%s\" name=\"txtFechaCilindro%s\" class=\"inputHabilitado\" value=\"%s\"></td>", 
				$contFila, $contFila, date(spanDateFormat,strtotime($rowPedidoDet['fecha_elaboracion_cilindro'])));
		$descripUnidad .= "</tr>";
	}
	$descripUnidad .= "</table>";
	
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
	
	if ($hddIdClienteItm > 0 && $hddIdClienteItm != "") {
		$imgCliente = sprintf("<a class=\"modalImg\" id=\"aClienteItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_cliente.gif\" title=\"Ver Cliente\"/>", 
			$contFila);
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItmNoVa\" name=\"cbxItmNoVa[]\" style=\"display:none\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><a class=\"modalImg\" id=\"aEditarItm:%s\" rel=\"#divFlotante1\" style=\"display:none\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a></td>".
			"<td>%s</td>".
			"<td id=\"tdCodigoArticuloItm%s\">%s</td>".
			"<td><div id=\"divDescripcionArticuloItm%s\">%s</div>%s</td>".
			"<td><input type=\"text\" id=\"txtCantRecibItm%s\" name=\"txtCantRecibItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCostoItm%s\" name=\"txtCostoItm%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdArancelFamiliaItm%s\" name=\"hddIdArancelFamiliaItm%s\" class=\"inputSinFondo\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtPesoItm%s\" name=\"txtPesoItm%s\" class=\"inputSinFondo\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtGastosItm%s\" name=\"txtGastosItm%s\" class=\"inputSinFondo\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFacturaDetalleItm%s\" name=\"hddIdFacturaDetalleItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedidoDetalleItm%s\" name=\"hddIdPedidoDetalleItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdUnidadBasicaItm%s\" name=\"hddIdUnidadBasicaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdUnidadFisicaItm%s\" name=\"hddIdUnidadFisicaItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosImportNacItm%s\" name=\"hddGastosImportNacItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosImportItm%s\" name=\"hddGastosImportItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdClienteItm%s\" name=\"hddIdClienteItm%s\" value=\"%s\"/></td>".
		"</tr>');
		
		jQuery(function($){
			$(\"#txtFechaFabricacion%s\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		});
		
		new JsDatePick({
			useMode:2,
			target:\"txtFechaFabricacion%s\",
			dateFormat:\"%s\",
			cellColorScheme:\"orange\"
		});
		
		byId('txtCostoItm%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		byId('aEditarItm:%s').onclick = function() { abrirDivFlotante1(this, 'tblArticulo', '%s'); }
		byId('%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
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
			$contFila, utf8_encode($codigoUnidad), 
			$contFila, $descripUnidad, $arancelArticulo, 
			$contFila, $contFila, number_format($txtCantRecibItm, 2, ".", ","), 
			$contFila, $contFila, number_format($txtCostoItm, 2, ".", ","), 
			$ivaUnidad, 
			cargaLstArancelGrupoItm("lstTarifaAdValorem".$contFila, $lstTarifaAdValorem), 
				$contFila, $contFila, $hddIdArancelFamiliaItm, 
			$contFila, $contFila, number_format($pesoUnitario, 2, ".", ","),
			$contFila, $contFila, number_format(($txtCantRecibItm * $gastoUnitario), 3, ".", ","),
			$contFila, $contFila, number_format(($txtCantRecibItm * $txtCostoItm), 2, ".", ","), 
				$contFila, $contFila, $idFacturaDetalle,
				$contFila, $contFila, $idPedidoDetalle, 
				$contFila, $contFila, $hddIdUnidadBasicaItm, 
				$contFila, $contFila, $hddIdUnidadFisicaItm, 
				$contFila, $contFila, number_format(0, 2, ".", ","), 
				$contFila, $contFila, number_format(0, 2, ".", ","), 
				$contFila, $contFila, $hddIdClienteItm, 
		
		$contFila,
		$contFila,
		spanDatePick,
		
		$contFila, 
		$contFila, 
			$contFila, 
		"lstTarifaAdValorem".$contFila, 
		
		$contFila,
		$contFila,
				
		$contFila, $htmlCostosArt, $contFila, 
		$contFila);
	
	if ($hddIdClienteItm > 0) {
		$htmlItmPie .= sprintf("
		byId('aClienteItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblCliente', '%s'); }", 
			$contFila, $hddIdClienteItm);
	}
	
	return array(true, $htmlItmPie, $contFila);
}
?>