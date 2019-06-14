<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function aprobarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idFacturaCompra = $frmDcto['txtIdFactura'];
	$idExpediente = $frmTotalDcto['hddIdExpediente'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = str_replace(",","",$frmDcto['txtTasaCambio']);
	
	if (($idModoCompra == 1 && !xvalidaAcceso($objResponse,"iv_registro_compra_nacional","insertar"))
	|| ($idModoCompra == 2 && !xvalidaAcceso($objResponse,"iv_registro_compra_importacion","insertar"))) { return $objResponse; }
	
	// VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	$sinAlmacen = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($valor > 0 && strlen($frmListaArticulo['hddIdCasilla'.$valor]) == "") {
				return $objResponse->alert("Existen artículos los cuales no tienen ubicación asignada");
			}
		}
	}
	
	mysql_query("START TRANSACTION;");
	
	if ($idModoCompra == 1) { // 1 = Nacional
		$txtSubTotal = str_replace(",","",$frmTotalDcto['txtSubTotal']);
		$txtSubTotalDescuento = str_replace(",","",$frmTotalDcto['txtSubTotalDescuento']);
		$txtTotalOrden = str_replace(",","",$frmTotalDcto['txtTotalOrden']);
		$txtTotalSaldoOrden = str_replace(",","",$frmTotalDcto['txtTotalOrden']);
		$txtTotalExento = str_replace(",","",$frmTotalDcto['txtTotalExento']);
		$txtTotalExonerado = str_replace(",","",$frmTotalDcto['txtTotalExonerado']);
	} else if ($idModoCompra == 2) { // 2 = Importacion
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$hddTotalArt = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
				$hddGastosArt = str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]);
				
				if (str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]) > 0) {
					$totalDescuentoArt = str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]);
				} else {
					$totalDescuentoArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $hddTotalArt) / 100;
				}
				$hddTotalArt = $hddTotalArt - $totalDescuentoArt;
				
				// CALCULA LOS DATOS DE IMPORTACION
				$precioTotalFOB = $hddTotalArt;
				$totalCIF = $precioTotalFOB + $hddGastosArt;
				$totalPrecioCIF = $totalCIF * $txtTasaCambio;
				$tarifaAdValorem = ($totalPrecioCIF * str_replace(",","",$frmListaArticulo['lstTarifaAdValorem'.$valor])) / 100;
				$gastosImportNacArt = str_replace(",","",$frmListaArticulo['hddGastosImportNacArt'.$valor]);
				
				$subTotalArancel += $tarifaAdValorem;
				$subTotalGastosImportNacArt += $gastosImportNacArt;
			}
		}
		$txtSubTotal = (str_replace(",","",$frmTotalDcto['txtSubTotal']) * $txtTasaCambio) + $subTotalArancel;
		$txtSubTotalDescuento = str_replace(",","",$frmTotalDcto['txtSubTotalDescuento']) * $txtTasaCambio;
		$txtTotalOrden = (str_replace(",","",$frmTotalDcto['txtTotalOrden']) * $txtTasaCambio) + $subTotalArancel + $subTotalGastosImportNacArt;
		$txtTotalSaldoOrden = (str_replace(",","",$frmTotalDcto['txtTotalOrden']) * $txtTasaCambio);
		$txtTotalExento = str_replace(",","",$frmTotalDcto['txtTotalExento']) * $txtTasaCambio;
		$txtTotalExonerado = str_replace(",","",$frmTotalDcto['txtTotalExonerado']) * $txtTasaCambio;
	}
	
	$queryProv = sprintf("SELECT prov.credito, prov_cred.*
	FROM cp_proveedor prov
		LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	WHERE prov.id_proveedor = %s;",
		valTpDato($frmDcto['txtIdProv'], "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowProv = mysql_fetch_assoc($rsProv);
	
	// SI EL PROVEEDOR ES A CREDITO SE LE SUMA LA CANTIDAD DE DIAS DE CREDITO PARA LA FECHA DE VENCIMIENTO
	$fechaVencimiento = ($rowProv['credito'] == "Si") ? suma_fechas("d-m-Y",$frmDcto['txtFechaProveedor'],$rowProv['diascredito']) : $frmDcto['txtFechaProveedor'];
	
	// INSERTA LOS DATOS DE LA FACTURA
	$insertSQL = sprintf("INSERT INTO cp_factura (id_empresa, id_modo_compra, numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_moneda, id_modulo, estatus_factura, observacion_factura, tipo_pago, monto_exento, monto_exonerado, subtotal_factura, porcentaje_descuento, subtotal_descuento, saldo_factura, aplica_libros, activa, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idModoCompra, "int"),
		valTpDato($frmDcto['txtNumeroFacturaProveedor'], "text"),
		valTpDato($frmDcto['txtNumeroControl'], "text"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaProveedor'])), "date"),
		valTpDato($frmDcto['txtIdProv'], "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
		valTpDato($idMonedaLocal, "int"),
		valTpDato($idModulo, "int"),
		valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
		valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Contado, 1 = Credito
		valTpDato($txtTotalExento, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($txtSubTotal, "real_inglesa"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtTotalSaldoOrden, "real_inglesa"),
		valTpDato(1, "boolean"), // 0 = No, 1 = Si
		valTpDato(1, "int"), // Null = Anulada, 1 = Activa
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idFactura = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayDetIdDctoContabilidad[0] = $idFactura;
	$arrayDetIdDctoContabilidad[1] = $idModulo;
	$arrayDetIdDctoContabilidad[2] = "COMPRA";
	$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
	
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
		valTpDato($frmDcto['lstTipoClave'], "int"),
		valTpDato($frmDcto['lstClaveMovimiento'], "int"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($frmDcto['txtIdProv'], "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmDcto['rbtTipoPago'], "boolean"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimiento = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
		
	// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	// INSERTA LOS DATOS PARA EL BLOQUEO DE VENTA
	$insertSQL = sprintf("INSERT INTO iv_bloqueo_venta (id_factura_compra, id_empleado) 
	VALUE (%s, %s);",
		valTpDato($idFactura, "int"),
		valTpDato($rowUsuario['id_empleado'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idBloqueoVenta = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DE LA FACTURA
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idArticuloOrg = $frmListaArticulo['hddIdArt'.$valor];
			$idArticuloSust = $frmListaArticulo['hddIdArtSust'.$valor];
			$idArticulo = ($idArticuloSust > 0) ? $idArticuloSust : $idArticuloOrg;
			$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
			$idClienteArt = $frmListaArticulo['hddIdClienteArt'.$valor];
			
			$idPedCompDet = $frmListaArticulo['hddIdPedCompDetArt'.$valor];
			
			$costoUnitArtFinal = 0;
			
			// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
			$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
			WHERE id_pedido_compra_detalle = %s;",
				valTpDato($idPedCompDet, "int"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			
			$idPedido = $rowPedidoDet['id_pedido_compra'];
			
			$cantPedida = str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$valor]);
			$cantRecibida = $cantPedida;
			$cantPendiente = $cantPedida - $cantRecibida;
			
			switch ($idModoCompra) {
				case 1 : // 1 = Nacional
					$hddIdIvaArt = $frmListaArticulo['hddIdIvaArt'.$valor];
					$hddIvaArt = $frmListaArticulo['hddIvaArt'.$valor];
				
					$costoUnitArt = str_replace(",","",$frmListaArticulo['hddCostoArt'.$valor]);
					$gastoUnitArt = str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]) / $cantRecibida;
					$costoUnitArtConGasto = $costoUnitArt + $gastoUnitArt;
					
					// VERIFICA SI EL DESCUENTO ES INDIVIDUAL
					if (str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]) > 0) {
						$porcDescuentoArt = str_replace(",","",$frmListaArticulo['hddPorcDescuentoArt'.$valor]);
						$montoDescuentoUnitArt = str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]);
					} else {
						$porcDescuentoArt = str_replace(",","",$frmTotalDcto['txtDescuento']);
						$montoDescuentoUnitArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $costoUnitArtConGasto) / 100;
					}
					$costoUnitArtFinal = $costoUnitArtConGasto - $montoDescuentoUnitArt;
					break;
				case 2 : // 2 = Importacion
					$hddIdIvaArt = "";
					$hddIvaArt = "";
					$hddTotalArt = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
					$hddGastosArt = str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]);
					
					// CALCULA LOS DATOS DE IMPORTACION
					$precioTotalFOB = $hddTotalArt;
					$totalCIF = $precioTotalFOB + $hddGastosArt;
					$totalPrecioCIF = $totalCIF * $txtTasaCambio;
					$tarifaAdValorem = ($totalPrecioCIF * str_replace(",","",$frmListaArticulo['lstTarifaAdValorem'.$valor])) / 100;
					$gastosImportNacArt = str_replace(",","",$frmListaArticulo['hddGastosImportNacArt'.$valor]);
					$otrosCargos = str_replace(",","",$frmListaArticulo['hddGastosImportArt'.$valor]);
					$precioTotal = $totalPrecioCIF + $tarifaAdValorem + $gastosImportNacArt + $otrosCargos;
					$diferenciaCambiariaTotal = $totalCIF * str_replace(",","",$frmTotalDcto['txtDiferenciaCambiaria']);
					$precioUnitario = $precioTotal / $cantRecibida;
					$diferenciaCambiariaUnit = ($totalCIF * str_replace(",","",$frmTotalDcto['txtDiferenciaCambiaria'])) / $cantRecibida;
					$totalPrecioUnitario = $precioUnitario + $diferenciaCambiariaUnit;
					
					// TRANSFORMA AL TIPO DE MONEDA NACIONAL
					$costoUnitArt = (str_replace(",","",$frmListaArticulo['hddCostoArt'.$valor]) * $txtTasaCambio) + ($tarifaAdValorem / $cantRecibida);
					$gastoUnitArt = ((str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]) / $cantRecibida) * $txtTasaCambio) + ($gastosImportNacArt / $cantRecibida);
					$costoUnitArtConGasto = $costoUnitArt + $gastoUnitArt;
					
					// VERIFICA SI EL DESCUENTO ES INDIVIDUAL
					if (str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]) > 0) {
						$porcDescuentoArt = str_replace(",","",$frmListaArticulo['hddPorcDescuentoArt'.$valor]);
						$montoDescuentoUnitArt = str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]) * $txtTasaCambio;
					} else {
						$porcDescuentoArt = str_replace(",","",$frmTotalDcto['txtDescuento']);
						$montoDescuentoUnitArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $costoUnitArtConGasto) / 100;
					}
					$costoUnitArtFinal = $totalPrecioUnitario;
					break;
			}
			
			$estatusDet = ($cantPendiente == 0) ? 1 : 0;
			
			// REGISTRA EL DETALLE DE LA FACTURA
			$insertSQL = sprintf("INSERT INTO cp_factura_detalle (id_factura, id_pedido_compra, id_articulo, id_casilla, cantidad, pendiente, precio_unitario, tipo_descuento, porcentaje_descuento, subtotal_descuento, id_iva, iva, tipo, id_cliente, estatus, por_distribuir)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato($idPedido, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($cantRecibida, "int"),
				valTpDato($cantPendiente, "int"),
				valTpDato($costoUnitArt, "real_inglesa"),
				valTpDato($frmListaArticulo['hddTipoDescuentoArt'.$valor], "boolean"),
				valTpDato($porcDescuentoArt, "real_inglesa"),
				valTpDato($montoDescuentoUnitArt, "real_inglesa"),
				valTpDato($hddIdIvaArt, "int"),
				valTpDato($hddIvaArt, "real_inglesa"),
				valTpDato($frmListaArticulo['hddTipoArt'.$valor], "int"),
				valTpDato($frmListaArticulo['hddIdClienteArt'.$valor], "int"),
				valTpDato($estatusDet, "boolean"),
				valTpDato($cantRecibida, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idFacturaDetalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// ALMACENA LA CANTIDAD FALTANTE POR DISTRIBUIR DENTRO DE LOS ALMACENES DE LA EMPRESA
			$updateSQL = sprintf("UPDATE cp_factura_detalle SET
				por_distribuir = %s
			WHERE id_factura_detalle = %s;",
				valTpDato($cantRecibida - $cantRecibida, "int"),
				valTpDato($idFacturaDetalle, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			if ($idModoCompra == 1) { // 1 = Nacional
				$costoUnitarioPedido = $costoUnitArt;
			} else if ($idModoCompra == 2) { // 2 = Importacion
				$costoUnitarioPedido = $precioTotalFOB / $cantRecibida;
				
				// REGISTRA EL DETALLE DE LA FACTURA
				$insertSQL = sprintf("INSERT INTO cp_factura_detalle_importacion (id_factura_detalle, id_arancel_familia, costo_unitario, gasto_unitario, gastos_import_nac_unitario, gastos_import_unitario, porcentaje_grupo)
				VALUE (%s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFacturaDetalle, "int"),
					valTpDato($frmListaArticulo['hddIdArancelFamilia'.$valor], "int"),
					valTpDato($precioTotalFOB / $cantRecibida, "real_inglesa"),
					valTpDato(str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]) / $cantRecibida, "real_inglesa"),
					valTpDato(str_replace(",","",$frmListaArticulo['hddGastosImportNacArt'.$valor]) / $cantRecibida, "real_inglesa"),
					valTpDato(str_replace(",","",$frmListaArticulo['hddGastosImportArt'.$valor]) / $cantRecibida, "real_inglesa"),
					valTpDato($frmListaArticulo['lstTarifaAdValorem'.$valor], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
			
			// ACTUALIZA EL PRECIO DEL DETALLE DEL PEDIDO
			$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
				id_articulo = %s,
				precio_unitario = %s
			WHERE id_pedido_compra_detalle = %s;",
				valTpDato($idArticulo, "int"),
				valTpDato($costoUnitarioPedido, "real_inglesa"),
				valTpDato($idPedCompDet, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, id_clave_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
				valTpDato($idModulo, "int"),
				valTpDato($idFactura, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato(1, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($frmDcto['lstClaveMovimiento'], "int"),
				valTpDato($cantRecibida, "real_inglesa"),
				valTpDato($costoUnitArtConGasto, "real_inglesa"),
				valTpDato($costoUnitArtConGasto, "real_inglesa"),
				valTpDato(($otrosCargos / $cantRecibida), "real_inglesa"),
				valTpDato($porcDescuentoArt, "real_inglesa"),
				valTpDato($montoDescuentoUnitArt, "real_inglesa"),
				valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
				valTpDato($frmTotalDcto['txtObservacionFactura'], "text"));
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
				valTpDato($cantRecibida, "real_inglesa"),
				valTpDato($costoUnitArtConGasto, "real_inglesa"),
				valTpDato($costoUnitArtConGasto, "real_inglesa"),
				valTpDato(($otrosCargos / $cantRecibida), "real_inglesa"),
				valTpDato($porcDescuentoArt, "real_inglesa"),
				valTpDato($montoDescuentoUnitArt, "real_inglesa"),
				valTpDato(0, "int"), // 0 = Unitario, 1 = Import
				valTpDato(0, "boolean"), // 0 = No, 1 = Si
				valTpDato($idMonedaLocal, "int"),
				valTpDato($idMonedaOrigen, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL DETALLE PARA EL BLOQUEO DE VENTA
			if ($idClienteArt > 0) {
				$insertSQL = sprintf("INSERT INTO iv_bloqueo_venta_detalle (id_bloqueo_venta, id_articulo, id_casilla, cantidad_bloquear, cantidad, estatus, id_cliente)
				VALUE (%s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idBloqueoVenta, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($cantRecibida, "real_inglesa"),
					valTpDato($cantRecibida, "real_inglesa"),
					valTpDato(1, "int"), // 1 = Bloqueado, 2 = Desbloqueado
					valTpDato($idClienteArt, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
			
			// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
			$Result1 = actualizarMovimientoTotal($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			if ($idArticuloSust > 0) {
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
				$Result1 = actualizarSaldos($idArticuloOrg, $idCasilla, $idCasillaPredet);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
				$Result1 = actualizarPedidas($idArticuloOrg);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			}
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
			$Result1 = actualizarSaldos($idArticulo, $idCasilla, $idCasillaPredet);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
			$Result1 = actualizarPedidas($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// REGISTRA EL COSTO DE COMPRA DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, costo, fecha, fecha_registro)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idEmpresa, "int"),
				valTpDato($frmDcto['txtIdProv'], "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($costoUnitArtFinal, "real_inglesa"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato("NOW()","campo"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA EL COSTO PROMEDIO
			$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA EL PRECIO DE VENTA
			$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			$urlEtiqueta[] = $idArticulo.",".$idCasilla.",".$cantRecibida;
		}
	}
	
	// ACTIVA LA RELACION DEL ARTICULO CON LA EMPRESA Y LAS UBICACIONES
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idArticuloOrg = $frmListaArticulo['hddIdArt'.$valor];
			$idArticuloSust = $frmListaArticulo['hddIdArtSust'.$valor];
			$idArticulo = ($frmListaArticulo['hddIdArtSust'.$valor] > 0) ? $idArticuloSust : $idArticuloOrg;
			$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
			
			$idPedCompDet = $frmListaArticulo['hddIdPedCompDetArt'.$valor];
			
			$costoUnitArtFinal = 0;
			
			if (strlen($idArticulo) > 0) {
				// BUSCA LOS DATOS DEL ARTICULO
				$queryArt = sprintf("SELECT * FROM iv_articulos
				WHERE id_articulo = %s;",
					valTpDato($idArticulo, "int"));
				$rsArt = mysql_query($queryArt);
				if (!$rsArt) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArt = mysql_fetch_assoc($rsArt);
				
				// VERIFICA SI EL ARTICULO ESTA LIGADO A LA EMPRESA
				$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
				WHERE id_empresa = %s
					AND id_articulo = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"));
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
				
				if ($totalRowsArtEmp == 0) { // SI NO EXISTE EL ARTICULO PARA LA EMPRESA
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				} else { // SI EXISTE EL ARTICULO PARA LA EMRPESA
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
				
				
				// VERIFICA SI HAY RELACION ENTRE ARTICULO Y LA UBICACION SELECCIONADA
				$queryArtAlmacen = sprintf("SELECT * FROM vw_iv_articulos_almacen
				WHERE id_articulo = %s
					AND id_casilla = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				$rsArtAlmacen = mysql_query($queryArtAlmacen);
				if (!$rsArtAlmacen) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsArtAlmacen = mysql_num_rows($rsArtAlmacen);
				$rowArtAlmacen = mysql_fetch_assoc($rsArtAlmacen);
				if ($totalRowsArtAlmacen == 0) { // SI EL ARTICULO NO TIENE UBICACION, SE LE ASIGNA LA SELECCIONADA
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						// LE AGREGA LA UBICACION AL ARTICULO SUSTITUTO
						$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
						VALUE (%s, %s, %s);",
							valTpDato($idCasilla, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
				if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				
				$idCasillaPredet = $rowArtEmp['id_casilla_predeterminada_compra'];
				
				// SI LA CASILLA SELECCIONADA ES DISTINTA A LA CASILLA PREDETERMINADA
				if ($idCasilla != $idCasillaPredet) {
					if ($idCasillaPredet == "") { // SI NO TIENE CASILLA PREDETERMINADA LE ASIGNA LA SELECCIONADA EN EL REGISTRO DE COMPRA
						$idCasillaPredet = $idCasilla;
						
						// MODIFICA LA CASILLA PREDETERMINADA DEL ARTICULO EN LA EMPRESA
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
							id_casilla_predeterminada_compra = %s,
							cantidad_pedida = 0,
							estatus = 1
						WHERE id_articulo_empresa = %s;",
							valTpDato($idCasilla, "int"),
							valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
				
				// ACTIVA LA UBICACION SELECCIONADA EN EL REGISTRO DE COMPRA
				$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
					estatus = 1
				WHERE id_articulo = %s
					AND id_casilla = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// VERIFICACION PARA SABER SI LA CASILLA PREDETERMINADA ES VÁLIDA
				$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen
				WHERE id_articulo = %s
					AND id_casilla = %s
					AND estatus = 1;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasillaPredet, "int"));
				$rsArtAlm = mysql_query($queryArtAlm);
				if (!$rsArtAlm) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
				$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
				if ($totalRowsArtAlm == 0) {
					// BUSCA LA PRIMERA UBICACION ACTIVA DEL ARTICULO PARA PONERSELA COMO PREDETERMINADA
					$queryArtAlm2 = sprintf("SELECT * FROM iv_articulos_almacen
					WHERE id_articulo = %s
						AND estatus = 1;",
						valTpDato($idArticulo, "int"));
					$rsArtAlm2 = mysql_query($queryArtAlm2);
					if (!$rsArtAlm2) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtAlm2 = mysql_num_rows($rsArtAlm2);
					$rowArtAlm2 = mysql_fetch_assoc($rsArtAlm2);
					
					if ($totalRowsArtAlm2 > 0) {
						$queryCasillaError = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
							valTpDato($idCasilla, "int"));
						$rsCasillaError = mysql_query($queryCasillaError);
						if (!$rsCasillaError) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowCasillaError = mysql_fetch_assoc($rsCasillaError);
						
						// MODIFICA LA CASILLA PREDETERMINADA DEL ARTICULO EN LA EMPRESA
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
							id_casilla_predeterminada_compra = %s
						WHERE id_articulo = %s;",
							valTpDato($rowArtAlm2['id_casilla'], "int"),
							valTpDato($idArticulo, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nId: ".$idCasilla."\nCasilla: ".$rowCasillaError['ubicacion']); }
						mysql_query("SET NAMES 'latin1';");
						
					} else if ($totalRowsArtAlm2 == 0) {
						// COMO NO TIENE CASILLAS ACTIVAS LE PONE COMO PREDETERMINADA NINGUNA
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
							id_casilla_predeterminada_compra = NULL
						WHERE id_articulo = %s;",
							valTpDato($idArticulo, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
			}
		}
	}
	
	if ($idModoCompra == 2) { // 2 = Importacion
		// ACTUALIZA EL EXPEDIENTE
		$updateSQL = sprintf("UPDATE iv_expediente SET
			estatus = %s
		WHERE id_expediente = %s;",
			valTpDato(1, "int"), // 0 = Abierto, 1 = Cerrado
			valTpDato($idExpediente, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// GUARDA LOS DATOS DE LAS FACTURA DE IMPORTACION
		$Result1 = guardarDctoImportacion($idFactura, $frmDcto, $frmTotalDcto, "REGISTRO");
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	// INSERTA LOS GASTOS DEL PEDIDO
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice => $valor) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s;",
				valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGastos = mysql_fetch_assoc($rsGasto);
			
			if ($idModoCompra == 2 && $rowGastos['id_modo_gasto'] == 1) { // 2 = Importacion && 1 = Gastos
				$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor]) * $txtTasaCambio;
				$porcMontoGasto = ($montoGasto * 100) / $txtSubTotal;
			} else {
				$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor]);
				$porcMontoGasto = str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valor]);
			}
			
			if ($montoGasto != 0) {
				$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto, estatus_iva, id_iva, iva, id_modo_gasto, afecta_documento)
				SELECT %s, id_gasto, %s, %s, %s, estatus_iva, %s, %s, id_modo_gasto, afecta_documento
				FROM pg_gastos
				WHERE id_gasto = %s;",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($porcMontoGasto, "real_inglesa"),
					valTpDato($montoGasto, "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"),
					valTpDato($frmTotalDcto['hddIvaGasto'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	for ($cont = 0; isset($frmTotalDcto['hddIdIva'.$cont]); $cont++) {
		if ($frmTotalDcto['txtSubTotalIva'.$cont] > 0) {
			$insertSQL = sprintf("INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idFactura, "int"),
				valTpDato($frmTotalDcto['txtBaseImpIva'.$cont], "real_inglesa"),
				valTpDato($frmTotalDcto['txtSubTotalIva'.$cont], "real_inglesa"),
				valTpDato($frmTotalDcto['hddIdIva'.$cont], "int"),
				valTpDato($frmTotalDcto['txtIva'.$cont], "real_inglesa"),
				valTpDato($frmTotalDcto['hddLujoIva'.$cont], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("FA", "text"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato("1", "int")); // 1 = FA, 2 = ND, 3 = AN, 4 = NC
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
		
		$insertSQL = sprintf("INSERT INTO cp_retencioncabezera (id_empresa, numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idProveedor)
		VALUE (%s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato(date("Y"), "int"),
			valTpDato(date("m"), "int"),
			valTpDato($frmDcto['txtIdProv'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idRetencionCabezera = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$porcRetencion = doubleval($frmTotalDcto['lstRetencionImpuesto']);
		
		$comprasSinIva = $txtTotalExento + $txtTotalExonerado;
		
		// RECORRE LOS IMPUESTOS DEL PEDIDO PARA CREARLE SU RETENCION
		for ($valor = 0; isset($frmTotalDcto['hddIdIva'.$valor]); $valor++) {
			if ($frmTotalDcto['txtSubTotalIva'.$valor] > 0) {
				$ivaRetenido = ($porcRetencion * str_replace(",", "", $frmTotalDcto['txtSubTotalIva'.$valor])) / 100;
				
				$insertSQL = sprintf("INSERT INTO cp_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idRetencionCabezera, "int"),
					valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaProveedor'])), "date"),
					valTpDato($idFactura, "int"),
					valTpDato($frmDcto['txtNumeroControl'], "text"),
					valTpDato(" ", "text"),
					valTpDato(" ", "text"),
					valTpDato("01", "text"), // 01 = FACTURA, 02 = NOTA DEBITO, 03 = NOTA CREDITO
					valTpDato(" ", "text"), // CUANDO ES NOTA DE CREDITO O DE DEBITO
					valTpDato($txtTotalOrden, "real_inglesa"),
					valTpDato($comprasSinIva, "double"),
					valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
					valTpDato($ivaRetenido, "real_inglesa"),
					valTpDato($porcRetencion, "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA EL PAGO DEBIDO A LA RETENCION
				$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato("FA", "text"),
					valTpDato("RETENCION", "text"),
					valTpDato($idRetencionCabezera, "int"),
					valTpDato(date("Y-m-d"), "date"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato($ivaRetenido, "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	} else if ($frmTotalDcto['lstRetencionImpuesto'] > 0
	&& $txtTotalExento + $txtTotalExonerado == $txtTotalOrden) {
		return $objResponse->alert("Este Registro No Posee Impuesto(s) para Aplicar(les) Retención, Por Favor Verifique la Opción de Retención Seleccionada");
	}
	
	// SI ES DE IMPORTACION
	if ($idModoCompra == 1) { // 1 = Nacional, 2 = Importacion
		// ACTUALIZA EL SALDO DE LA FACTURA
		$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
			saldo_factura = (IFNULL(fact_comp.subtotal_factura, 0)
								- IFNULL(fact_comp.subtotal_descuento, 0)
								+ IFNULL((SELECT SUM(fact_comp_gasto.monto) AS total_gasto
										FROM cp_factura_gasto fact_comp_gasto
										WHERE fact_comp_gasto.id_factura = fact_comp.id_factura
											AND fact_comp_gasto.id_modo_gasto IN (1,3)), 0)
								+ IFNULL((SELECT SUM(fact_comp_iva.subtotal_iva) AS total_iva
										FROM cp_factura_iva fact_comp_iva
										WHERE fact_comp_iva.id_factura = fact_comp.id_factura), 0)
								- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
										WHERE pago_dcto.id_documento_pago = fact_comp.id_factura
											AND pago_dcto.tipo_documento_pago LIKE 'FA'), 0))
		WHERE id_factura = %s
			AND estatus_factura NOT IN (1);",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	} else if ($idModoCompra == 2) { // 1 = Nacional, 2 = Importacion
		// BUSCA LOS DATOS DE LA MONEDA NACIONAL
		$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
			valTpDato($frmDcto['hddIdMoneda'], "int"));
		$rsMonedaLocal = mysql_query($queryMonedaLocal);
		if (!$rsMonedaLocal) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
		
		// SI LA MONEDA NACIONAL APLICA I.V.A ACTUALIZARA LOS ESTATUS DE IMPUESTO PARA EFECTO DE LA DECLARACION DEL IMPUESTO
		if ($rowMonedaLocal['incluir_impuestos'] == 1 && $frmDcto['lstNacionalizar'] == 1) { // 0 = No, 1 = Si
			// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1;");
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$updateSQL = sprintf("UPDATE cp_factura_detalle SET
				id_iva = %s,
				iva = %s
			WHERE id_factura = %s;",
				valTpDato($rowIva['idIva'], "int"),
				valTpDato($rowIva['iva'], "real_inglesa"),
				valTpDato($idFactura, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
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
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$updateSQL = sprintf("UPDATE cp_factura SET
				monto_exento = (SELECT SUM(monto) FROM cp_factura_gasto
								WHERE id_factura = cp_factura.id_factura
									AND (iva <= 0 OR iva IS NULL)
									AND id_modo_gasto IN (1,3)),
				monto_exonerado = 0
			WHERE id_factura = %s;",
				valTpDato($idFactura, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
		
		// ACTUALIZA EL SALDO DE LA FACTURA DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
		$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
			fact_comp.saldo_factura = (IFNULL((SELECT 
													SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
												FROM cp_factura_detalle a
													INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
													INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
												WHERE a.id_factura = fact_comp.id_factura), 0)
										
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_modo_gasto IN (1)
													AND fact_comp_gasto.afecta_documento IN (1)
													AND fact_comp_gasto.id_factura = fact_comp.id_factura), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE pago_dcto.id_documento_pago = fact_comp.id_factura
													AND pago_dcto.tipo_documento_pago LIKE 'FA'), 0))
		WHERE fact_comp.id_modo_compra IN (2)
			AND fact_comp.estatus_factura IN (0,2)
			AND fact_comp.id_factura = %s
			AND (SELECT COUNT(fact_comp_det.id_factura)
				FROM cp_factura_detalle fact_comp_det
				WHERE fact_comp_det.id_factura = fact_comp.id_factura) > 0;",
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
					
	// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
	$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
		estatus_factura = (CASE
							WHEN (saldo_factura = 0) THEN
								1
							WHEN (saldo_factura > 0 AND saldo_factura < (IFNULL(fact_comp.subtotal_factura, 0)
																	- IFNULL(fact_comp.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
																		FROM cp_factura_gasto fact_compra_gasto
																		WHERE (fact_compra_gasto.id_factura = fact_comp.id_factura)), 0)
																	+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
																		FROM cp_factura_iva fact_compra_iva
																		WHERE (fact_compra_iva.id_factura = fact_comp.id_factura)), 0))) THEN
								2
							ELSE
								0
						END)
	WHERE id_factura = %s;",
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// ELIMINA LOS DATOS DEL BLOQUEO DE VENTA SI NO POSEE DETALLE
	$deleteSQL = sprintf("DELETE FROM iv_bloqueo_venta
	WHERE id_bloqueo_venta NOT IN (SELECT id_bloqueo_venta FROM iv_bloqueo_venta_detalle)
		AND id_bloqueo_venta = %s;",
		valTpDato($idBloqueoVenta, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// ELIMINA LOS DATOS DE LA FACTURA DE COMPRA EN REPUESTOS
	$deleteSQL = sprintf("DELETE FROM iv_factura_compra WHERE id_factura_compra = %s;",
		valTpDato($idFacturaCompra, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// ACTUALIZA LA CANTIDAD DE ARTICULOS PENDIENTES EN EL DETALLE DEL PEDIDO
	$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
		pendiente = cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
										FROM iv_factura_compra_detalle fact_comp_det
										WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
											AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
								+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
										FROM cp_factura_detalle fact_comp_det
										WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
											AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)),
		estatus = (CASE 
					WHEN (cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
											FROM iv_factura_compra_detalle fact_comp_det
											WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
									+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
											FROM cp_factura_detalle fact_comp_det
											WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) = 0 THEN
						1
					WHEN (cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
											FROM iv_factura_compra_detalle fact_comp_det
											WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
									+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
											FROM cp_factura_detalle fact_comp_det
											WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) > 0 THEN
						0
				END)
	WHERE estatus IN (0,1)
		AND id_pedido_compra IN (SELECT fact_comp_det.id_pedido_compra FROM cp_factura_detalle fact_comp_det
								WHERE fact_comp_det.id_factura IN (%s));",
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	WHERE id_pedido_compra IN (SELECT fact_comp_det.id_pedido_compra FROM cp_factura_detalle fact_comp_det
								WHERE fact_comp_det.id_factura IN (%s));",
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdFactura","value",$idFactura);
	
	
	$objResponse->alert("Registro de Compra Guardado con Éxito");
	
	$comprobanteRetencion = ($frmTotalDcto['lstRetencionImpuesto'] > 0) ? 1 : 0;
	
	$objResponse->script(sprintf("window.location.href='iv_registro_compra_formato_pdf.php?valBusq=%s|%s|%s&valBusq2=%s';",
		$comprobanteRetencion,
		$idFactura,
		$idRetencionCabezera,
		implode("|", $urlEtiqueta)));

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
	
	$idArticulo = ($frmListaArticulo['hddIdArtSust'.$hddNumeroArt] > 0) ? $frmListaArticulo['hddIdArtSust'.$hddNumeroArt] : $frmListaArticulo['hddIdArt'.$hddNumeroArt];
	$idCasilla = $frmAlmacen['lstCasillaAct'];
	
	// VERIFICA SI ALGUN ARTICULO DE LA LISTA TIENE LA UBICACION YA OCUPADA
	$existe = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdCasilla'.$valor] == $idCasilla)
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
		
		$objResponse->assign("hddIdCasilla".$hddNumeroArt,"value",$idCasilla);
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
	
	$objResponse->script("
	document.forms['frmArticulo'].reset();
	byId('hddIdArt').value = '';
	byId('hddIdArtSust').value = '';
	byId('hddNumeroArt').value = '';
	
	byId('txtCantidadRecibArt').className = 'inputHabilitado';
	byId('txtCostoArt').className = 'inputHabilitado';");
	
	if ($frmListaArticulo['hddIdArtSust'.$hddNumeroArt] > 0) {
		$idArticulo = $frmListaArticulo['hddIdArtSust'.$hddNumeroArt];
		$objResponse->assign("hddIdArt","value",$frmListaArticulo['hddIdArt'.$hddNumeroArt]);
	} else {
		$idArticulo = $frmListaArticulo['hddIdArt'.$hddNumeroArt];
		$objResponse->assign("hddIdArt","value",$frmListaArticulo['hddIdArt'.$hddNumeroArt]);
		$objResponse->assign("hddIdArtSust","value","");
	}
	
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
	
	$fechaUltimaCompra = ($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx";
	$fechaUltimaVenta = ($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx";
	
	$objResponse->assign("txtCodigoArt","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",$fechaUltimaCompra);
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",$fechaUltimaVenta);
	$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
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
	
	$objResponse->script("byId('txtCantDisponible').className = '".(($rowArtSaldo['cantidad_disponible_logica'] > 0) ? "inputCantidadDisponible" : "inputCantidadNoDisponible")."'");
	
	$objResponse->assign("txtCostoArt","value",$frmListaArticulo['hddCostoArt'.$hddNumeroArt]);
	if ($frmListaArticulo['hddTipoDescuentoArt'.$hddNumeroArt] == 0) {
		$objResponse->script("byId('rbtPorcDescuentoArt').click();");
	} else if ($frmListaArticulo['hddTipoDescuentoArt'.$hddNumeroArt] == 1) {
		$objResponse->script("byId('rbtMontoDescuentoArt').click();");
	}
	$objResponse->assign("hddTipoDescuento","value",$frmListaArticulo['hddTipoDescuentoArt'.$hddNumeroArt]);
	$objResponse->assign("txtPorcDescuentoArt","value",str_replace(",","",$frmListaArticulo['hddPorcDescuentoArt'.$hddNumeroArt]));
	$objResponse->assign("txtMontoDescuentoArt","value",str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$hddNumeroArt]));
	
	$objResponse->assign("txtCantidadRecibArt","value",str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$hddNumeroArt]));
	
	$objResponse->assign("txtCantidadArt","value",str_replace(",","",$frmListaArticulo['hddCantArt'.$hddNumeroArt]));
	$objResponse->loadCommands(cargaLstIva($frmListaArticulo['hddIdIvaArt'.$hddNumeroArt], $frmListaArticulo['hddIvaArt'.$hddNumeroArt]));
	
	if ($frmListaArticulo['hddTipoArt'.$hddNumeroArt] == 0) {
		$objResponse->script("
		byId('rbtTipoArtReposicion').checked = true;
		byId('aInsertarClienteArt').style.display = 'none';");
	} else {
		$objResponse->script("
		byId('rbtTipoArtCliente').checked = true;
		byId('aInsertarClienteArt').style.display = '';");
	}
	
	$objResponse->loadCommands(asignarCliente($frmListaArticulo['hddIdClienteArt'.$hddNumeroArt], $frmDcto['txtIdEmpresa'], "false"));
	
	$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
	
	$objResponse->script("
	byId('txtCantidadRecibArt').focus();
	byId('txtCantidadRecibArt').select();");
	
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
	
	$fechaUltimaCompra = ($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx";
	$fechaUltimaVenta = ($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx";
	
	if ($artSustituto == true) {
		$objResponse->assign("hddIdArtSust","value",$idArticulo);
	} else {
		$objResponse->assign("hddIdArtSust","value","");
	}
	
	$objResponse->assign("txtCodigoArt","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",$fechaUltimaCompra);
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",$fechaUltimaVenta);
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
		$txtFechaFacturaGasto = date("d-m-Y",strtotime($row['fecha_origen']));
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
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarFacturaGasto').click();");
	}
	
	return $objResponse;
}

function asignarMoneda($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	$objResponse->script("
	byId('tdNacionalizar').style.display = 'none';
	byId('tdlstNacionalizar').style.display = 'none';
	byId('trlstArancelGrupo').style.display = 'none';");
	
	if ($idMonedaLocal == $idMonedaOrigen) {
		$objResponse->assign("tdGastos","innerHTML",formularioGastos(false, "", "", 1, $frmTotalDcto));
		
		$objResponse->script("
		byId('trDatosImportacion').style.display = 'none';");
		
		$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
		if ($frmDcto['txtIdFactura'] > 0) {
			$objResponse->script("
			byId('aAgregarArticulo').style.display = 'none';
			byId('btnQuitarArticulo').style.display = 'none';
			byId('btnExportar').style.display = '';
			byId('aImportar').style.display = 'none';
			byId('btnGuardar').style.display = '';
			byId('btnAprobar').style.display = '';
			byId('btnCancelar').onclick = function () { window.open('iv_registro_compra_list.php','_self'); }");
		} else {
			$objResponse->script("
			byId('aAgregarArticulo').style.display = '';
			byId('btnQuitarArticulo').style.display = '';
			byId('btnExportar').style.display = 'none';
			byId('aImportar').style.display = '';
			byId('btnGuardar').style.display = '';
			byId('btnAprobar').style.display = 'none';
	 		byId('btnCancelar').onclick = function () { window.open('iv_preregistro_compra_list.php','_self'); }");
		}
	} else {
		if ($frmDcto['txtIdFactura'] > 0) {
			$objResponse->assign("tdGastos","innerHTML",formularioGastos(false, "", "", 2, $frmTotalDcto));
			
			$objResponse->script("
			if (byId('trDatosImportacion').style.display == 'none') {
				xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
			}");
		} else {
			$objResponse->assign("tdGastos","innerHTML",formularioGastos(false, "", "", 1, $frmTotalDcto));
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
		
		$objResponse->assign("txtTasaCambio","value",number_format($rowTasaCambio['monto_tasa_cambio'], 3, ".", ","));
		
		if ($frmDcto['txtIdFactura'] > 0) {
			$objResponse->script("
			byId('aAgregarArticulo').style.display = 'none';
			byId('btnQuitarArticulo').style.display = 'none';
			byId('btnExportar').style.display = '';
			byId('aImportar').style.display = 'none';
			byId('tdNacionalizar').style.display = '';
			byId('tdlstNacionalizar').style.display = '';
			byId('trlstArancelGrupo').style.display = '';
			byId('trDatosImportacion').style.display = '';
			byId('btnGuardar').style.display = '';
			byId('btnAprobar').style.display = '';
			byId('btnCancelar').onclick = function () { window.open('iv_registro_compra_list.php','_self'); }");
		}
		
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
	
	$objResponse->assign("hddIncluirImpuestos","value",$rowMonedaOrigen['incluir_impuestos']);
	$objResponse->assign("txtIdMonedaNegociacion","value",$rowMonedaOrigen['idmoneda']);
	$objResponse->assign("txtMonedaNegociacion","value",$rowMonedaOrigen['descripcion']);
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	// HABILITA O INHABILITA POR ARTICULO EL IMPUESTO Y EL ARANCEL DEPENDIENDO SI SE INCLUYE O NO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($rowMonedaOrigen['incluir_impuestos'] == 1) { // 1 = Si
				$objResponse->script("byId('hddIvaArt".$valor."').style.visibility = '';");
				$objResponse->script("
				if (byId('hddIdIvaArt".$valor."').value > 0) {
					byId('hddEstatusIvaArt".$valor."').value = 1;
				}");
			} else if ($rowMonedaOrigen['incluir_impuestos'] == 0) { // 0 = No
				$objResponse->script("byId('hddIvaArt".$valor."').style.visibility = 'hidden';");
				$objResponse->script("
				if (byId('hddIdIvaArt".$valor."').value > 0) {
					byId('hddEstatusIvaArt".$valor."').value = 0;
				}");
			}
			
			if ($idMonedaLocal == $idMonedaOrigen) {
				$objResponse->script("byId('lstTarifaAdValorem".$valor."').style.visibility = 'hidden';");
			} else {
				$objResponse->script("byId('lstTarifaAdValorem".$valor."').style.visibility = '';");
			}
		}
	}
	
	// HABILITA O INHABILITA POR GASTO EL IMPUESTO DEPENDIENDO SI SE INCLUYE O NO
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice2 => $valor2) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE pg_gastos.id_gasto = %s;",
				valTpDato($frmTotalDcto['hddIdGasto'.$valor2], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGastos = mysql_fetch_assoc($rsGasto);
			
			if ($rowGastos['id_modo_gasto'] == 1) { // 1 = Gastos
				$objResponse->assign("spnGastoMoneda".$valor2,"innerHTML",$abrevMonedaOrigen);
			} else if ($rowGastos['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
				$objResponse->assign("spnGastoMoneda".$valor2,"innerHTML",$abrevMonedaLocal);
			}
			
			if (($rowGastos['id_modo_gasto'] == 1 && $rowMonedaOrigen['incluir_impuestos'] == 0)		// 1 = Gastos && 0 = No
			|| ($rowGastos['id_modo_gasto'] == 3 && $rowMonedaLocal['incluir_impuestos'] == 0)) {		// 3 = Gastos por Importacion && 0 = No
				$objResponse->script("byId('trIvaGasto".$valor2."').style.visibility = 'hidden';");
			} else if (($rowGastos['id_modo_gasto'] == 1 && $rowMonedaOrigen['incluir_impuestos'] == 1)	// 1 = Gastos && 1 = Si
			|| ($rowGastos['id_modo_gasto'] == 3 && $rowMonedaLocal['incluir_impuestos'] == 1)) {		// 3 = Gastos por Importacion && 1 = Si
				$objResponse->script("byId('trIvaGasto".$valor2."').style.visibility = '';");
			}
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
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
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
	
	$idArticulo = ($frmArticulo['hddIdArtSust'] > 0) ? $frmArticulo['hddIdArtSust'] : $frmArticulo['hddIdArt'];
	
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
					$html .= "<td width=\"80%\">".elimCaracter(utf8_encode($rowArtUbic['codigo_articulo']),";")."</td>";
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
	
	for ($cont = 0; isset($frmTotalDcto['txtIva'.$cont]); $cont++) {
		$objResponse->script("
		fila = document.getElementById('trIva:".$cont."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
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
	
	for ($cont = 0; isset($frmTotalDcto['txtIvaLocal'.$cont]); $cont++) {
		$objResponse->script("
		fila = document.getElementById('trIvaLocal:".$cont."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
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
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = (str_replace(",","",$frmDcto['txtTasaCambio']) > 0) ? str_replace(",","",$frmDcto['txtTasaCambio']) : 1;
	
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
	
	// VERIFICA LOS VALORES DE CADA ITEM PARA CALCULAR EL IMPUESTO Y EL SUBTOTAL
	$subTotal = 0;
	$totalExentoOrigen = 0;
	$totalExoneradoOrigen = 0;
	$arrayIva = NULL;
	$arrayDetalleIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($idModoCompra == 2 && $incluirIvaMonedaLocal == 1) { // 2 = Importacion
				$estatusIva = 0;
				// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1;");
			} else {
				$estatusIva = $frmListaArticulo['hddEstatusIvaArt'.$valor];
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmListaArticulo['hddIdIvaArt'.$valor], "int"));
			}
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$idIva = $rowIva['idIva'];
			$porcIva = $rowIva['iva'];
			$lujoIva = ($rowIva['tipo'] == 2 || $rowIva['tipo'] == 3) ? "1" : "0";
			
			$subTotalArt = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
			if (str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]) > 0) {
				$totalDescuentoArt = str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]);
			} else {
				$totalDescuentoArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $subTotalArt) / 100;
			}
			$subTotalArt = $subTotalArt - $totalDescuentoArt;
			
			if ($totalRowsIva == 0 || $estatusIva == 0) {
				$totalExentoOrigen += $subTotalArt;
			} else {
				if ($idModoCompra == 2 && $incluirIvaMonedaLocal == 1) { // 2 = Importacion
					$ivaArt = $porcIva;
				} else {
					$ivaArt = ($frmDcto['txtIdFactura'] > 0) ? str_replace(",","",$frmListaArticulo['hddIvaArt'.$valor]) : $porcIva;
				}
				$subTotalIvaArt = ($subTotalArt * $ivaArt) / 100;
				
				$existIva = false;
				if (isset($arrayIva)) {
					foreach ($arrayIva as $indiceIva => $valorIva) {
						if ($arrayIva[$indiceIva][0] == $idIva) {
							$arrayIva[$indiceIva][1] += $subTotalArt;
							$arrayIva[$indiceIva][2] += $subTotalIvaArt;
							$existIva = true;
						}
					}
				}
				
				if ($idIva > 0 && $existIva == false
				&& (str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]) - (str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$valor]) * str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]))) > 0) {
					$arrayDetalleIva[0] = $idIva;
					$arrayDetalleIva[1] = $subTotalArt;
					$arrayDetalleIva[2] = $subTotalIvaArt;
					$arrayDetalleIva[3] = $ivaArt;
					$arrayDetalleIva[4] = $lujoIva;
					$arrayDetalleIva[5] = $rowIva['observacion'];
					$arrayIva[] = $arrayDetalleIva;
				}
			}
			
			$subTotalDescuentoArt += str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$valor]) * str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]);
			
			$subTotal += str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
		}
	}
	
	// CALCULA LOS GASTOS DE CADA ARTICULO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$totGastosArt = 0;
			$totGastosImportNacionalArt = 0;
			$totalOtrosCargosArt = 0;
			
			if (isset($frmListaArticulo['hddIdArt'.$valor])) {
				$hddTotalArt = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
				$hddTotalDescuentoArt = str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]);
				
				// GASTOS INCLUIDOS EN FACTURA
				for ($cont = 1; isset($frmTotalDcto['hddIdGasto'.$cont]); $cont++) {
					if ($frmTotalDcto['txtMontoGasto'.$cont] != 0) {
						// BUSCA LOS DATOS DEL GASTO
						$queryGasto = sprintf("SELECT * FROM pg_gastos
						WHERE pg_gastos.id_gasto = %s;",
							valTpDato($frmTotalDcto['hddIdGasto'.$cont], "int"));
						$rsGasto = mysql_query($queryGasto);
						if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$rowGasto = mysql_fetch_assoc($rsGasto);
						
						if ($rowGasto['id_modo_gasto'] == 1) { // 1 = Gastos
							$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$cont]);
							$gastosArt = (($hddTotalArt - $hddTotalDescuentoArt) * $montoGasto) / $subTotal;
							
							$totGastosArt += round($gastosArt, 2);
						} else if ($rowGasto['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
							$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$cont]);
							$gastosArt = ((($hddTotalArt - $hddTotalDescuentoArt) * $txtTasaCambio) * $montoGasto) / ($subTotal * $txtTasaCambio);
							
							$totGastosImportNacionalArt += round($gastosArt, 2);
						}
					}
				}
				$frmListaArticulo['hddGastosArt'.$valor] = $totGastosArt;
				$objResponse->assign("hddGastosArt".$valor,"value",number_format($totGastosArt, 2, ".", ","));
				$objResponse->assign("hddGastosImportNacArt".$valor,"value",number_format($totGastosImportNacionalArt, 2, ".", ","));
				
				// OTROS CARGOS
				if (isset($arrayObjOtroCargo)) {
					$totalOtrosCargos = 0;
					foreach ($arrayObjOtroCargo as $indice2 => $valor2) {
						$hddSubTotalFacturaGasto = str_replace(",","",$frmTotalDcto['hddSubTotalFacturaGasto'.$valor2]);
						$montoOtrosCargosArt = (($hddTotalArt - $hddTotalDescuentoArt) * $hddSubTotalFacturaGasto) / $subTotal;
						
						$totalOtrosCargosArt += round($montoOtrosCargosArt, 2);
						
						$totalOtrosCargos += $hddSubTotalFacturaGasto;
					}
				}
				$objResponse->assign("hddGastosImportArt".$valor,"value",number_format($totalOtrosCargosArt, 2, ".", ","));
			}
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL ARANCEL Y EL PRECIO CIF PARA INCLUIRLO EN EL IMPUESTO
	if ($idModoCompra == 2) { // 2 = Importacion
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$hddTotalArt = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
				$hddGastosArt = str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]);
				
				if (str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]) > 0) {
					$totalDescuentoArt = str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]);
				} else {
					$totalDescuentoArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $hddTotalArt) / 100;
				}
				$hddTotalArt = $hddTotalArt - $totalDescuentoArt;
				
				// CALCULA LOS DATOS DE IMPORTACION
				$precioTotalFOB = $hddTotalArt;
				$totalCIF = $precioTotalFOB + $hddGastosArt;
				$totalPrecioCIF = $totalCIF * $txtTasaCambio;
				$tarifaAdValorem = ($totalPrecioCIF * str_replace(",","",$frmListaArticulo['lstTarifaAdValorem'.$valor])) / 100;
				
				$monto = $totalPrecioCIF + $tarifaAdValorem;
				
				$existeAdValorem = false;
				if (isset($arrayAdValorem)) {
					foreach ($arrayAdValorem as $indice2 => $valor2) {
						if (str_replace(",","",$frmListaArticulo['lstTarifaAdValorem'.$valor]) == $arrayAdValorem[$indice2][0]) {
							$existeAdValorem = true;
							$arrayAdValorem[$indice2][1] = $arrayAdValorem[$indice2][1] + $tarifaAdValorem;
							$arrayAdValorem[$indice2][2]++;
						}
					}
				}
				
				if ($existeAdValorem == false) {
					$arrayDetalle[0] = str_replace(",","",$frmListaArticulo['lstTarifaAdValorem'.$valor]);
					$arrayDetalle[1] = $tarifaAdValorem;
					$arrayDetalle[2] = 1;
					$arrayAdValorem[] = $arrayDetalle;
				}
				
				$estatusIva = ($frmDcto['lstNacionalizar'] == 1 && $incluirIvaMonedaLocal == 1) ? 1 : 0;
				// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1;");
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				$rowIva = mysql_fetch_assoc($rsIva);
				
				$idIva = $rowIva['idIva'];
				$porcIva = $rowIva['iva'];
				$lujoIva = ($rowIva['tipo'] == 2 || $rowIva['tipo'] == 3) ? "1" : "0";
				
				if ($totalRowsIva == 0 || $estatusIva == 0) {
					$totalExentoLocal += $monto;
				} else {
					$ivaArt = $porcIva;
					
					$existIva = false;
					if (isset($arrayIva)) {
						foreach ($arrayIva as $indiceIva => $valorIva) {
							if ($arrayIva[$indiceIva][0] == $idIva) {
								$arrayIva[$indiceIva][1] += $monto;
								$arrayIva[$indiceIva][2] += ($monto * ($ivaArt / 100));
								$existIva = true;
							}
						}
					}
					
					if ($idIva > 0 && $existIva == false && $monto > 0) {
						$arrayDetalleIva[0] = $idIva;
						$arrayDetalleIva[1] = $monto;
						$arrayDetalleIva[2] = ($monto * ($ivaArt / 100));
						$arrayDetalleIva[3] = $ivaArt;
						$arrayDetalleIva[4] = $lujoIva;
						$arrayDetalleIva[5] = $rowIva['observacion'];
						$arrayIva[] = $arrayDetalleIva;
					}
				}
			}
		}
	}
	
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IMPUESTO
	$gastosConIvaOrigen = 0;
	$gastosSinIvaOrigen = 0;
	if (isset($frmTotalDcto['cbxGasto'])) {
		foreach ($frmTotalDcto['cbxGasto'] as $indice2 => $valor2) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s;",
				valTpDato($frmTotalDcto['hddIdGasto'.$valor2], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGasto = mysql_fetch_assoc($rsGasto);
			
			if ($frmTotalDcto['hddTipoGasto'.$valor2] == 0) { // SACA EL MONTO MEDIANTE EL PORCENTAJE
				$porcentaje = ($subTotal == 0) ? 0 : str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valor2]);
				$monto = ($subTotal == 0) ? 0 : $porcentaje * ($subTotal / 100);
				$objResponse->assign('txtMontoGasto'.$valor2,"value",number_format($monto, 2, ".", ","));
			} else if ($frmTotalDcto['hddTipoGasto'.$valor2] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
				$monto = ($subTotal == 0) ? 0 : str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor2]);
				$porcentaje = ($subTotal == 0) ? 0 : $monto * (100 / $subTotal);
				$objResponse->assign('txtPorcGasto'.$valor2,"value",number_format($porcentaje, 2, ".", ","));
			}
			
			$monto = str_replace(",","",$monto);
			
			if ($idModoCompra == 2 && ($incluirIvaMonedaOrigen == 1 || $incluirIvaMonedaLocal == 1)) { // 2 = Importacion
				if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 1) {
					$estatusIva = 1;
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
				} else if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 0 && $incluirIvaMonedaLocal == 1) {
					$estatusIva = 0;
					// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1;");
				} else if ($rowGasto['id_modo_gasto'] == 3 && $incluirIvaMonedaLocal == 1) {
					$estatusIva = 1;
					$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
				}
			} else {
				$estatusIva = 1;
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor2], "int"));
			}
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$idIva = $rowIva['idIva'];
			$porcIva = $rowIva['iva'];
			$lujoIva = ($rowIva['tipo'] == 2 || $rowIva['tipo'] == 3) ? "1" : "0";
			
			if ($totalRowsIva == 0 || $estatusIva == 0) {
				if ($rowGasto['id_modo_gasto'] == 1) { // 1 = Gastos
					if ($rowGasto['afecta_documento'] == 1) {
						$gastosSinIvaOrigen += $monto;
					} else {
						$gastosNoAfectaOrigen += $monto;
					}
				} else if ($rowGasto['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
					$gastosSinIva += $monto;
				}
			} else {
				if ($idModoCompra == 2 && $incluirIvaMonedaLocal == 1) { // 2 = Importacion
					$ivaArt = $porcIva;
				} else {
					$ivaArt = ($frmDcto['txtIdFactura'] > 0) ? str_replace(",","",$frmTotalDcto['hddIvaGasto'.$valor2]) : $porcIva;
				}
				
				$existIva = false;
				if (isset($arrayIva)) {
					foreach ($arrayIva as $indiceIva => $valorIva) {
						if ($arrayIva[$indiceIva][0] == $idIva) {
							$arrayIva[$indiceIva][1] += $monto;
							$arrayIva[$indiceIva][2] += ($monto * ($ivaArt / 100));
							$existIva = true;
						}
					}
				}
				
				if ($idIva > 0 && $existIva == false && $monto > 0) {
					$arrayDetalleIva[0] = $idIva;
					$arrayDetalleIva[1] = $monto;
					$arrayDetalleIva[2] = ($monto * ($ivaArt / 100));
					$arrayDetalleIva[3] = $ivaArt;
					$arrayDetalleIva[4] = $lujoIva;
					$arrayDetalleIva[5] = $rowIva['observacion'];
					$arrayIva[] = $arrayDetalleIva;
				}
				
				if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 1) {
					if ($rowGasto['afecta_documento'] == 1) {
						$gastosConIvaOrigen += $monto;
					} else {
						$gastosNoAfectaOrigen += $monto;
					}
				} else if ($rowGasto['id_modo_gasto'] == 1 && $incluirIvaMonedaOrigen == 0 && $incluirIvaMonedaLocal == 1) {
					$gastosConIva += $monto;
				} else if ($rowGasto['id_modo_gasto'] == 3 && $incluirIvaMonedaLocal == 1) {
					$gastosConIva += $monto;
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
							"<td>Cant. Items:</td>".
							"<td align=\"right\">%s</td>".
						"</tr>".
						"</table>".
					"</td>".
					"<td></td>".
					"<td>%s</td>".
					"<td><input type=\"text\" id=\"txtSubTotalAdValorem%s\" name=\"txtSubTotalAdValorem%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxAdv\" name=\"cbxAdv[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"</tr>';
				
				obj = byId('trAdValorem:%s');
				if (obj == undefined)
					$('#trRetencionIva').before(elemento);",
				$indice,
					"% ADV ".$arrayAdValorem[$indice][0].":",
					"100%",
					$arrayAdValorem[$indice][2],
					$abrevMonedaLocal,
					$indice, $indice, number_format(round($arrayAdValorem[$indice][1],2), 2, ".", ","),
						$indice,
				
				$indice));
				
				$totalItems += round($arrayAdValorem[$indice][2],2);
				$totalAdValorem += round($arrayAdValorem[$indice][1],2);
		}
		
		// INSERTA EL ARTICULO SIN INJECT
		$objResponse->script(sprintf("
		var elemento = '<tr align=\"right\" id=\"trAdValorem:%s\" class=\"trResaltarTotal\">".
				"<td class=\"tituloCampo\">%s</td>".
				"<td>%s</td>".
				"<td></td>".
				"<td>%s</td>".
				"<td><input type=\"text\" id=\"txtTotalAdValorem\" name=\"txtTotalAdValorem\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/>".
					"<input type=\"checkbox\" id=\"cbxAdv\" name=\"cbxAdv[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"</tr>';
			
			obj = byId('trAdValorem:%s');
			if (obj == undefined)
				$('#trRetencionIva').before(elemento);",
			$indice + 1,
				"Total ADV:",
				$totalItems,
				$abrevMonedaLocal,
				number_format($totalAdValorem, 2, ".", ","),
					$indice + 1,
			
			$indice + 1));
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIva)) {
		foreach ($arrayIva as $indiceIva => $valorIva) {
			if ($arrayIva[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$htmlIva = sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';",
					$indiceIva,
						$indiceIva, utf8_encode($arrayIva[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][0],
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][4], 
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][1],2), 2, ".", ","),
						$indiceIva, $indiceIva, $arrayIva[$indiceIva][3], "%",
						$abrevMonedaLocal,
						$indiceIva, $indiceIva, number_format(round($arrayIva[$indiceIva][2],2), 2, ".", ","));
				
				if ($idModoCompra == 2) { // 2 = Importacion
					$objResponse->script(sprintf("
						%s
						
						obj = byId('trIva:%s');
						if (obj == undefined)
							$('#trRetencionIva').before(elemento);",
						$htmlIva,
						$indiceIva));
				} else {
					$objResponse->script(sprintf("
						%s
						
						obj = byId('trIva:%s');
						if (obj == undefined)
							$('#trGastosSinIva').before(elemento);",
						$htmlIva,
						$indiceIva));
				}
			}
			
			$subTotalIva += ($idModoCompra == 1) ? doubleval($arrayIva[$indiceIva][2]) : 0; // 1 = Nacional
		}
	}
	
	if ($subTotalDescuentoArt > 0) {
		$porcDescuento = ($subTotalDescuentoArt * 100) / $subTotal;
		$subTotalDescuento = $subTotalDescuentoArt;
		
		$objResponse->script("byId('txtDescuento').readOnly = true;");
		$objResponse->assign("txtDescuento","value",number_format($porcDescuento, 2, ".", ","));
	} else {
		$porcDescuento = str_replace(",","",$frmTotalDcto['txtDescuento']);
		$objResponse->script("byId('txtDescuento').readOnly = false;");
	}
	
	$subTotalDescuento = $subTotal * ($porcDescuento / 100);
	$totalOrden = doubleval($subTotal) - doubleval($subTotalDescuento);
	$totalOrden += doubleval($subTotalIva) + doubleval($gastosConIvaOrigen) + doubleval($gastosSinIvaOrigen);
	
	$objResponse->assign("txtSubTotal","value",number_format($subTotal, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($subTotalDescuento, 2, ".", ","));	
	$objResponse->assign("txtTotalOrden","value",number_format($totalOrden, 2, ".", ","));
	
	$objResponse->assign('txtGastosConIva',"value",number_format($gastosConIvaOrigen, 2, ".", ","));
	$objResponse->assign('txtGastosSinIva',"value",number_format($gastosSinIvaOrigen, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento","value",number_format(($totalExentoOrigen + $gastosSinIvaOrigen), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($totalExoneradoOrigen, 2, ".", ","));
	
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalOtrosCargos, 2, ".", ","));
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	if ($rowEmpresa['contribuyente_especial'] == 1 && count($arrayIva) > 0) {
		$objResponse->loadCommands(cargaLstRetencionImpuesto());
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
	
	$html = "<select id=\"".$arraySelec[$posList+1].$adjLst."\" name=\"".$arraySelec[$posList+1].$adjLst."\" class=\"inputHabilitado\" ".$onChange.">";
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1') {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\" class=\"inputHabilitado\">";
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
				$html2 = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\" class=\"inputHabilitado\">";
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

function cargaLstArancelGrupo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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

function cargaLstIva($selId = "", $selVal = "") {
	$objResponse = new xajaxResponse();
	
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,8,3) AND iva.estado = 1 ORDER BY iva");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstIvaArt\" name=\"lstIvaArt\" class=\"inputHabilitado\" style=\"width:100px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
	$selected = "";
	if ($selId == 0) {
		$selected = "selected=\"selected\"";
		$opt = "Si";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if ($selVal == $rowIva['iva'] && $selId == $rowIva['id_iva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if ($selVal == $rowIva['iva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if ($selId == $rowIva['id_iva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if (($rowIva['tipo'] == 1 && $rowIva['activo'] == 1) && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstIvaArt","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstIvaItm($nombreObjeto, $selId = "") {
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,8,3) AND iva.estado = 1 ORDER BY iva");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputCompleto\">";
		
	$selected = "";
	if ($selId == 0 && $selId != "") {
		$selected = "selected=\"selected\"";
		$opt = "Si";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if ($selVal == $rowIva['iva'] && $selId == $rowIva['idIva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if ($selVal == $rowIva['iva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if ($selId == $rowIva['idIva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if (($rowIva['tipo'] == 1 && $rowIva['activo'] == 1) && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
			
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
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
	$html = "<select id=\"lstRetencionImpuesto\" name=\"lstRetencionImpuesto\" class=\"inputHabilitado\" style=\"width:150px\">";
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
	$html = "<select id=\"lstAlmacen".$adjLst."\" name=\"lstAlmacen".$adjLst."\" onchange=\"xajax_cargaLst('lstAlmacen', '".$adjLst."', this.value);\" class=\"inputHabilitado\" style=\"width:100px\">";
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
	$html = "<select id=\"lstCalle".$adjLst."\" name=\"lstCalle".$adjLst."\" onchange=\"xajax_cargaLst('lstCalle', '".$adjLst."', this.value);\" class=\"inputHabilitado\" style=\"width:100px\">";
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
	$html = "<select id=\"lstEstante".$adjLst."\" name=\"lstEstante".$adjLst."\" onchange=\"xajax_cargaLst('lstEstante', '".$adjLst."', this.value);\" class=\"inputHabilitado\" style=\"width:100px\">";
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
	$html = "<select id=\"lstTramo".$adjLst."\" name=\"lstTramo".$adjLst."\" onchange=\"xajax_cargaLst('lstTramo', '".$adjLst."', this.value);\" class=\"inputHabilitado\" style=\"width:100px\">";
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
	$html = "<select id=\"lstCasilla".$adjLst."\" name=\"lstCasilla".$adjLst."\" onchange=\"xajax_buscarDisponibilidadUbicacion(xajax.getFormValues('frmAlmacen'));\" class=\"inputHabilitado\" style=\"width:100px\">";
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

function cargarDcto($idFacturaCompra, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjOtroCargo = $frmTotalDcto['cbxOtroCargo'];
	$contFilaOtroCargo = $arrayObjOtroCargo[count($arrayObjOtroCargo)-1];
	
	// BUSCA LOS DATOS DE LA FACTURA DE COMPRA
	$queryFactura = sprintf("SELECT fact_comp.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_factura_compra fact_comp
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (fact_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg)
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
		$queryFacturaDetalle = sprintf("SELECT * FROM iv_factura_compra_detalle fact_comp_det WHERE id_factura_compra = %s
		ORDER BY id_factura_compra_detalle ASC;",
			valTpDato($idFacturaCompra, "int"));
		$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
		if (!$rsFacturaDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
			// BUSCA LOS DATOS DE LA UBICACION
			$queryUbic = sprintf("SELECT * FROM vw_iv_casillas
			WHERE id_casilla = %s;",
				valTpDato($rowFacturaDetalle['id_casilla'], "int"));
			$rsUbic = mysql_query($queryUbic);
			if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsUbic = mysql_num_rows($rsUbic);
			$rowUbic = mysql_fetch_assoc($rsUbic);
			
			$almacen = $rowUbic['descripcion_almacen'];
			$ubicacion = $rowUbic['ubicacion'];
			$idClienteArt = $rowFacturaDetalle['id_cliente'];
			
			$Result1 = insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, "", $rowFacturaDetalle['id_factura_compra_detalle'], $idMonedaLocal, $idMonedaOrigen, "", "", "", $almacen, $ubicacion, $idClienteArt);
			$arrayObjUbicacion = $Result1[3];
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$frmListaArticulo['hddIdPedCompDetArt'.$contFila] = $idPedidoCompraDetalle;
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
		$objResponse->assign("txtMontoTotalFactura","value",number_format($rowFactura['saldo_factura'], 2, ".", ","));
		
		// VERIFICA SI LA FACTURA ES DE IMPORTACION
		$idModoCompra = $rowFactura['id_modo_compra'];
		$objResponse->assign("tdGastos","innerHTML",formularioGastos(false, $idFacturaCompra, "PREREGISTRO", $idModoCompra, $frmTotalDcto));
		
		$objResponse->assign("txtIdFactura","value",$rowFactura['id_factura_compra']);
		$objResponse->assign("txtFechaRegistroCompra","value",date("d-m-Y",strtotime($rowFactura['fecha_origen'])));
		$objResponse->assign("txtNumeroFacturaProveedor","value",$rowFactura['numero_factura_proveedor']);
		$objResponse->assign("txtNumeroControl","value",$rowFactura['numero_control_factura']);
		$objResponse->assign("txtFechaProveedor","value",date("d-m-Y",strtotime($rowFactura['fecha_factura_proveedor'])));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento","0","1","","1",$rowFactura['id_clave_movimiento']));
		$objResponse->loadCommands(cargaLstGrupoItem('lstViaEnvio','ViaEnvio',$rowFactura['id_via_envio']));
		$objResponse->call("seleccionarEnvio",$rowFactura['id_via_envio']);
		$objResponse->call("selectedOption","lstNacionalizar",$rowFactura['nacionalizada']);
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
		
		// BUSCA LOS GASTOS DE LA FACTURA DE COMPRA
		$query = sprintf("SELECT
			fact_comp_gasto.id_factura_compra_gasto,
			fact_comp_gasto.id_gasto
		FROM iv_factura_compra_gasto fact_comp_gasto
			INNER JOIN pg_gastos gasto ON (fact_comp_gasto.id_gasto = gasto.id_gasto)
		WHERE fact_comp_gasto.id_factura_compra = %s
			AND fact_comp_gasto.id_modo_gasto = 2
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
		$objResponse->assign("txtFechaRegistroCompra","value",date("d-m-Y"));
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
	
	$txtTasaCambio = str_replace(",","",$frmDcto['txtTasaCambio']);
	
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
					
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (isset($frmListaArticulo['hddIdArt'.$valor]) && $valor == $hddNumeroArt) {
				$hddTotalArt = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
				$hddGastosArt = str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]);
				
				if (str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]) > 0) {
					$totalDescuentoArt = str_replace(",","",$frmListaArticulo['hddTotalDescuentoArt'.$valor]);
				} else {
					$totalDescuentoArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $hddTotalArt) / 100;
				}
				$hddTotalArt = $hddTotalArt - $totalDescuentoArt;
				
				// CALCULA LOS DATOS DE IMPORTACION
				$precioTotalFOB = $hddTotalArt;
				$totalCIF = $precioTotalFOB + $hddGastosArt;
				$totalPrecioCIF = $totalCIF * $txtTasaCambio;
				$tarifaAdValorem = ($totalPrecioCIF * str_replace(",","",$frmListaArticulo['lstTarifaAdValorem'.$valor])) / 100;
				$gastosImportNacArt = str_replace(",","",$frmListaArticulo['hddGastosImportNacArt'.$valor]);
				$otrosCargos = str_replace(",","",$frmListaArticulo['hddGastosImportArt'.$valor]);
				$precioTotal = $totalPrecioCIF + $tarifaAdValorem + $gastosImportNacArt + $otrosCargos;
				$diferenciaCambiariaTotal = $totalCIF * str_replace(",","",$frmTotalDcto['txtDiferenciaCambiaria']);
				$precioUnitario = $precioTotal / str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$valor]);
				$diferenciaCambiariaUnit = ($totalCIF * str_replace(",","",$frmTotalDcto['txtDiferenciaCambiaria'])) / str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$valor]);
				$totalPrecioUnitario = $precioUnitario + $diferenciaCambiariaUnit;
				
				if ($totalPrecioUnitario > 0) {
					for ($cont = 1; $cont <= 3; $cont++) {
						$objResponse->assign("tdMoneda".$cont.":".$valor,"innerHTML",$abrevMonedaOrigen);
					}
					
					for ($cont = 4; $cont <= 12; $cont++) {
						$objResponse->assign("tdMoneda".$cont.":".$valor,"innerHTML",$abrevMonedaLocal);
					}
					
					$objResponse->assign("tdPrecioTotalFOB".$valor,"innerHTML",number_format($precioTotalFOB, 2, ".", ","));
					$objResponse->assign("tdTotalGastos".$valor,"innerHTML",number_format($hddGastosArt, 2, ".", ","));
					$objResponse->assign("tdTotalCIF".$valor,"innerHTML",number_format($totalCIF, 2, ".", ","));
					$objResponse->assign("tdTotalPrecioCIF".$valor,"innerHTML",number_format($totalPrecioCIF, 2, ".", ","));
					$objResponse->assign("tdTarifaAdValorem".$valor,"innerHTML",number_format($tarifaAdValorem, 2, ".", ","));
					$objResponse->assign("tdTotalGastosImportNacional".$valor,"innerHTML",number_format($gastosImportNacArt, 2, ".", ","));
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
	
	if ($lstAsociaDocumento == 1) { // 1 = Si
		$objResponse->script(sprintf("
		byId('lstCondicionGasto').onchange = function () {
			seleccionarCondicion(this.value);
		}"));
	} else if ($lstAsociaDocumento == 0) { // 0 = No
		$objResponse->script(sprintf("
		byId('lstCondicionGasto').onchange = function () {
			seleccionarCondicion(this.value);
			selectedOption(this.id,'%s');
		}",
			1));
	}
	
	$objResponse->script(sprintf("
	byId('lstAsociaDocumento').onchange = function () {
		selectedOption(this.id,'%s');
	}",
		$lstAsociaDocumento));
	
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

function editarArticulo($frmArticulo) {
	$objResponse = new xajaxResponse();
	
	$hddNumeroArt = $frmArticulo['hddNumeroArt'];
	
	if ($frmArticulo['hddIdArtSust'] > 0) {
		$idArticulo = $frmArticulo['hddIdArtSust'];
		$objResponse->assign("hddIdArtSust".$hddNumeroArt,"value",$frmArticulo['hddIdArtSust']);
	} else {
		$idArticulo = $frmArticulo['hddIdArt'];
		$objResponse->assign("hddIdArtSust".$hddNumeroArt,"value","");
	}
	
	// BUSQUEDA DEL ARTICULO POR EL ID
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmArticulo['lstIvaArt'], "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowIva = mysql_fetch_assoc($rsIva);
	
	$porcIvaArt = ($rowIva['idIva'] != "") ? $rowIva['iva'] : "-";
	
	$objResponse->assign("tdCodArt:".$hddNumeroArt,"innerHTML",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("tdDescArt:".$hddNumeroArt,"innerHTML",utf8_encode($rowArticulo['descripcion']));
	
	$objResponse->assign("hddCantRecibArt".$hddNumeroArt,"value",number_format(str_replace(",","",$frmArticulo['txtCantidadRecibArt']), 2, ".", ","));
	$objResponse->assign("tdCantPend:".$hddNumeroArt,"innerHTML",number_format((str_replace(",","",$frmArticulo['txtCantidadArt']) - str_replace(",","",$frmArticulo['txtCantidadRecibArt'])), 2, ".", ","));
	$objResponse->assign("hddCostoArt".$hddNumeroArt,"value",number_format(str_replace(",","",$frmArticulo['txtCostoArt']), 2, ".", ","));
	$objResponse->assign("hddTotalArt".$hddNumeroArt,"value",number_format((str_replace(",","",$frmArticulo['txtCantidadRecibArt']) * str_replace(",","",$frmArticulo['txtCostoArt'])), 2, ".", ","));
	$objResponse->assign("hddTipoDescuentoArt".$hddNumeroArt,"value",$frmArticulo['hddTipoDescuento']);
	$objResponse->assign("hddPorcDescuentoArt".$hddNumeroArt,"value",number_format(str_replace(",","",$frmArticulo['txtPorcDescuentoArt']), 2, ".", ","));
	$objResponse->assign("hddMontoDescuentoArt".$hddNumeroArt,"value",number_format(str_replace(",","",$frmArticulo['txtMontoDescuentoArt']), 2, ".", ","));
	$objResponse->assign("hddTotalDescuentoArt".$hddNumeroArt,"value",number_format(str_replace(",","",$frmArticulo['txtCantidadRecibArt']) * str_replace(",","",$frmArticulo['txtMontoDescuentoArt']), 2, ".", ","));
	
	$objResponse->assign("hddIdIvaArt".$hddNumeroArt,"value",$frmArticulo['lstIvaArt']);
	$objResponse->assign("hddIvaArt".$hddNumeroArt,"value",$porcIvaArt);
	$objResponse->assign("hddTipoArt".$hddNumeroArt,"value",$frmArticulo['rbtTipoArt']);
	$objResponse->assign("hddIdClienteArt".$hddNumeroArt,"value",$frmArticulo['txtIdClienteArt']);
	
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
	
	$idDetallePedido = $frmListaArticulo['hddIdPedCompDetArt'.$hddNumeroArt];
	
	if ($frmListaArticulo['hddIdArtSust'.$hddNumeroArt] > 0) {
		$idArticulo = $frmListaArticulo['hddIdArtSust'.$hddNumeroArt];
	} else {
		$idArticulo = $frmListaArticulo['hddIdArt'.$hddNumeroArt];
	}
	$idCasilla = $frmListaArticulo['hddIdCasilla'.$hddNumeroArt];
	
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
	$objResponse->assign("txtCantidadDisponible","value",number_format($frmListaArticulo['hddCantRecibArt'.$hddNumeroArt], 2, ".", ","));
	
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

function formArticuloMultiple($idPedidoCompraDetalle) {
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
	$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
	WHERE id_pedido_compra_detalle = %s;",
		valTpDato($idPedidoCompraDetalle, "int"));
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
	$objResponse->assign("txtCodigoArtMultiple","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("txtDescripcionArtMultiple","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtCantidadArtMultiple","value",$rowPedidoDet['pendiente']);
	$objResponse->assign("txtUnidadArtMultiple","value",$rowArticulo['unidad']);
	
	$objResponse->script(sprintf("byId('aAgregarArtMult').onclick = function() { insertFilaArticuloMultiple('%s'); }",
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
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idFacturaCompra = $frmDcto['txtIdFactura'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = str_replace(",","",$frmDcto['txtTasaCambio']);
	
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
	
	// VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	$sinAlmacen = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($valor > 0 && strlen($frmListaArticulo['hddIdCasilla'.$valor]) == "") {
				$sinAlmacen = true;
			}
		}
	}
	
	if ($sinAlmacen == false) {
		mysql_query("START TRANSACTION;");
		
		$queryProv = sprintf("SELECT prov.credito, prov_cred.*
		FROM cp_proveedor prov
			LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
		WHERE prov.id_proveedor = %s;",
			valTpDato($frmDcto['txtIdProv'], "int"));
		$rsProv = mysql_query($queryProv);
		if (!$rsProv) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowProv = mysql_fetch_assoc($rsProv);
		
		// SI EL PROVEEDOR ES A CREDITO SE LE SUMA LA CANTIDAD DE DIAS DE CREDITO PARA LA FECHA DE VENCIMIENTO
		$fechaVencimiento = ($rowProv['credito'] == "Si") ? suma_fechas("d-m-Y",$frmDcto['txtFechaProveedor'],$rowProv['diascredito']) : $frmDcto['txtFechaProveedor'];
		
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
				aplica_libros = %s
			WHERE id_factura_compra = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idModoCompra, "int"),
				valTpDato($frmDcto['lstNacionalizar'], "int"),
				valTpDato($frmDcto['lstViaEnvio'], "int"),
				valTpDato($frmDcto['txtNumeroFacturaProveedor'], "text"),
				valTpDato($frmDcto['txtNumeroControl'], "text"),
				valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaProveedor'])), "date"),
				valTpDato($frmDcto['txtIdProv'], "int"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
				valTpDato($idMonedaLocal, "int"),
				valTpDato($idMonedaOrigen, "int"),
				valTpDato($frmDcto['lstTasaCambio'], "int"),
				valTpDato(0, "int"), // 0 = Repuestos, 1 = Servicios, 2 = Autos, 3 = Administración
				valTpDato($frmDcto['lstClaveMovimiento'], "int"),
				valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
				valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Contado, 1 = Credito
				valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
				valTpDato("1", "boolean"),
				valTpDato($idFacturaCompra, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else {
			// INSERTA LOS DATOS DE LA FACTURA
			$insertSQL = sprintf("INSERT INTO iv_factura_compra (id_empresa, id_modo_compra, numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, id_modulo, id_clave_movimiento, estatus_factura, observacion_factura, tipo_pago, porcentaje_descuento, aplica_libros)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idEmpresa, "int"),
				valTpDato($idModoCompra, "int"),
				valTpDato($frmDcto['txtNumeroFacturaProveedor'], "text"),
				valTpDato($frmDcto['txtNumeroControl'], "text"),
				valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaProveedor'])), "date"),
				valTpDato($frmDcto['txtIdProv'], "int"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato(date("Y-m-d", strtotime($fechaVencimiento)), "date"),
				valTpDato($idMonedaLocal, "int"),
				valTpDato($idMonedaOrigen, "int"),
				valTpDato($frmDcto['lstTasaCambio'], "int"),
				valTpDato(0, "int"), // 0 = Repuestos, 1 = Servicios, 2 = Autos, 3 = Administración
				valTpDato($frmDcto['lstClaveMovimiento'], "int"),
				valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
				valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Contado, 1 = Credito
				valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
				valTpDato("1", "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idFacturaCompra = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
		
		// VERIFICA SI LOS ITEMS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryFacturaCompraDetalle = sprintf("SELECT * FROM iv_factura_compra_detalle WHERE id_factura_compra = %s;",
			valTpDato($idFacturaCompra, "int"));
		$rsFacturaCompraDetalle = mysql_query($queryFacturaCompraDetalle);
		if (!$rsFacturaCompraDetalle) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($rowFacturaCompraDetalle = mysql_fetch_assoc($rsFacturaCompraDetalle)) {
			$idFactCompDet = $rowFacturaCompraDetalle['id_factura_compra_detalle'];
			
			$existRegDet = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					$idArticuloOrg = $frmListaArticulo['hddIdArt'.$valor];
					$idArticuloSust = $frmListaArticulo['hddIdArtSust'.$valor];
					$idArticulo = ($frmListaArticulo['hddIdArtSust'.$valor] > 0) ? $idArticuloSust : $idArticuloOrg;
					$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
					
					$idPedCompDet = $frmListaArticulo['hddIdPedCompDetArt'.$valor];
					
					$costoUnitArtFinal = 0;
					
					if ($idFactCompDet == $frmTotalDcto['hddIdFactCompDetArt'.$valor]) {
						$existRegDet = true;
						
						// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
						$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
						WHERE id_pedido_compra_detalle = %s;",
							valTpDato($idPedCompDet, "int"));
						$rsPedidoDet = mysql_query($queryPedidoDet);
						if (!$rsPedidoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
						
						$idPedido = $rowPedidoDet['id_pedido_compra'];
						
						$cantPedida = str_replace(",","",$frmListaArticulo['hddCantArt'.$valor]);
						$cantRecibida = str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$valor]);
						$cantPendiente = round($cantPedida, 2) - round($cantRecibida, 2);
						$hddIdIvaArt = $frmListaArticulo['hddIdIvaArt'.$valor];
						$hddIvaArt = $frmListaArticulo['hddIvaArt'.$valor];
						$hddIdArancelFamilia = $frmListaArticulo['hddIdArancelFamilia'.$valor];
						$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
					
						$costoUnitArt = str_replace(",","",$frmListaArticulo['hddCostoArt'.$valor]);
						$gastoUnitArt = str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]) / $cantRecibida;
						$costoUnitArtConGasto = round($costoUnitArt, 2) + round($gastoUnitArt, 2);
						
						// VERIFICA SI EL DESCUENTO ES INDIVIDUAL
						if (str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]) > 0) {
							$porcDescuentoArt = str_replace(",","",$frmListaArticulo['hddPorcDescuentoArt'.$valor]);
							$montoDescuentoUnitArt = str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]);
						} else {
							$porcDescuentoArt = str_replace(",","",$frmTotalDcto['txtDescuento']);
							$montoDescuentoUnitArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $costoUnitArtConGasto) / 100;
						}
						$costoUnitArtFinal = $costoUnitArtConGasto - $montoDescuentoUnitArt;
						
						$estatusDet = ($cantPendiente == 0) ? 1 : 0;
						
						// EDITA LOS DATOS DEL GASTO
						$updateSQL = sprintf("UPDATE iv_factura_compra_detalle SET
							id_factura_compra = %s,
							id_pedido_compra_detalle = %s,
							id_pedido_compra = %s,
							id_articulo = %s,
							id_casilla = %s,
							cantidad = %s,
							pendiente = %s,
							precio_unitario = %s,
							tipo_descuento = %s,
							porcentaje_descuento = %s,
							subtotal_descuento = %s,
							id_iva = %s,
							iva = %s,
							id_arancel_familia = %s,
							porcentaje_grupo = %s,
							tipo = %s,
							id_cliente = %s,
							estatus = %s,
							por_distribuir = %s
						WHERE id_factura_compra_detalle = %s;",
							valTpDato($idFacturaCompra, "int"),
							valTpDato($idPedCompDet, "int"),
							valTpDato($idPedido, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idCasilla, "int"),
							valTpDato($cantPedida, "int"),
							valTpDato($cantRecibida, "int"),
							valTpDato($costoUnitArt, "real_inglesa"),
							valTpDato($frmListaArticulo['hddTipoDescuentoArt'.$valor], "boolean"),
							valTpDato($porcDescuentoArt, "real_inglesa"),
							valTpDato($montoDescuentoUnitArt, "real_inglesa"),
							valTpDato($hddIdIvaArt, "int"),
							valTpDato($hddIvaArt, "real_inglesa"),
							valTpDato($hddIdArancelFamilia, "int"),
							valTpDato($lstTarifaAdValorem, "real_inglesa"),
							valTpDato($frmListaArticulo['hddTipoArt'.$valor], "int"),
							valTpDato($frmListaArticulo['hddIdClienteArt'.$valor], "int"),
							valTpDato($estatusDet, "boolean"), // 0 = En Espera, 1 = Recibido
							valTpDato($cantRecibida, "int"),
							valTpDato($idFactCompDet, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
			}
			
			if ($existRegDet == false) {
				$deleteSQL = sprintf("DELETE FROM iv_factura_compra_detalle WHERE id_factura_compra_detalle = %s;",
					valTpDato($rowFacturaCompraDetalle['id_factura_compra_detalle'], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
				$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
				WHERE id_pedido_compra = %s
					AND id_articulo = %s;",
					valTpDato($rowFacturaCompraDetalle['id_pedido_compra'], "int"),
					valTpDato($rowFacturaCompraDetalle['id_articulo'], "int"));
				$rsPedidoDet = mysql_query($queryPedidoDet);
				if (!$rsPedidoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
				
				$idPedCompDet = $rowPedidoDet['id_pedido_compra_detalle'];
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
				$idArticuloOrg = $frmListaArticulo['hddIdArt'.$valor];
				$idArticuloSust = $frmListaArticulo['hddIdArtSust'.$valor];
				$idArticulo = ($frmListaArticulo['hddIdArtSust'.$valor] > 0) ? $idArticuloSust : $idArticuloOrg;
				$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
				
				$idPedCompDet = $frmListaArticulo['hddIdPedCompDetArt'.$valor];
				
				$costoUnitArtFinal = 0;
				if ($idArticulo > 0) {
					// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
					$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
					WHERE id_pedido_compra_detalle = %s;",
						valTpDato($idPedCompDet, "int"));
					$rsPedidoDet = mysql_query($queryPedidoDet);
					if (!$rsPedidoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
					
					$idPedido = $rowPedidoDet['id_pedido_compra'];
					
					$cantPedida = str_replace(",","",$frmListaArticulo['hddCantArt'.$valor]);
					$cantRecibida = str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$valor]);
					$cantPendiente = round($cantPedida, 2) - round($cantRecibida, 2);
					$hddIdIvaArt = $frmListaArticulo['hddIdIvaArt'.$valor];
					$hddIvaArt = $frmListaArticulo['hddIvaArt'.$valor];
					$hddIdArancelFamilia = $frmListaArticulo['hddIdArancelFamilia'.$valor];
					$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
				
					$costoUnitArt = str_replace(",","",$frmListaArticulo['hddCostoArt'.$valor]);
					$gastoUnitArt = str_replace(",","",$frmListaArticulo['hddGastosArt'.$valor]) / $cantRecibida;
					$costoUnitArtConGasto = round($costoUnitArt, 2) + round($gastoUnitArt, 2);
					
					// VERIFICA SI EL DESCUENTO ES INDIVIDUAL
					if (str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]) > 0) {
						$porcDescuentoArt = str_replace(",","",$frmListaArticulo['hddPorcDescuentoArt'.$valor]);
						$montoDescuentoUnitArt = str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]);
					} else {
						$porcDescuentoArt = str_replace(",","",$frmTotalDcto['txtDescuento']);
						$montoDescuentoUnitArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $costoUnitArtConGasto) / 100;
					}
					$costoUnitArtFinal = $costoUnitArtConGasto - $montoDescuentoUnitArt;
					
					$estatusDet = ($cantPendiente == 0) ? 1 : 0;
					
					// REGISTRA EL DETALLE DE LA FACTURA
					$insertSQL = sprintf("INSERT INTO iv_factura_compra_detalle (id_factura_compra, id_pedido_compra_detalle, id_pedido_compra, id_articulo, id_casilla, cantidad, pendiente, precio_unitario, tipo_descuento, porcentaje_descuento, subtotal_descuento, id_iva, iva, id_arancel_familia, porcentaje_grupo, tipo, id_cliente, estatus, por_distribuir)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFacturaCompra, "int"),
						valTpDato($idPedCompDet, "int"),
						valTpDato($idPedido, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($cantPedida, "int"),
						valTpDato($cantRecibida, "int"),
						valTpDato($costoUnitArt, "real_inglesa"),
						valTpDato($frmListaArticulo['hddTipoDescuentoArt'.$valor], "boolean"),
						valTpDato($porcDescuentoArt, "real_inglesa"),
						valTpDato($montoDescuentoUnitArt, "real_inglesa"),
						valTpDato($hddIdIvaArt, "int"),
						valTpDato($hddIvaArt, "real_inglesa"),
						valTpDato($hddIdArancelFamilia, "int"),
						valTpDato($lstTarifaAdValorem, "real_inglesa"),
						valTpDato($frmListaArticulo['hddTipoArt'.$valor], "int"),
						valTpDato($frmListaArticulo['hddIdClienteArt'.$valor], "int"),
						valTpDato($estatusDet, "boolean"), // 0 = En Espera, 1 = Recibido
						valTpDato($cantRecibida, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idFacturaCompraDetalle = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					if ($idModoCompra == 1) { // 1 = Nacional
						$costoUnitarioPedido = $costoUnitArt;
					} else if ($idModoCompra == 2) { // 2 = Importacion
						$costoUnitarioPedido = $costoUnitArt;
					}
					
					// ACTUALIZA EL PRECIO DEL DETALLE DEL PEDIDO
					$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
						id_articulo = %s,
						precio_unitario = %s
					WHERE id_pedido_compra_detalle = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($costoUnitarioPedido, "real_inglesa"),
						valTpDato($idPedCompDet, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
		}
		
		// ACTIVA LA RELACION DEL ARTICULO CON LA EMPRESA Y LAS UBICACIONES
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$idArticuloOrg = $frmListaArticulo['hddIdArt'.$valor];
				$idArticuloSust = $frmListaArticulo['hddIdArtSust'.$valor];
				$idArticulo = ($idArticuloSust > 0) ? $idArticuloSust : $idArticuloOrg;
				$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
				
				$idPedCompDet = $frmListaArticulo['hddIdPedCompDetArt'.$valor];
				
				$costoUnitArtFinal = 0;
				
				// BUSCA LOS DATOS DEL ARTICULO
				$queryArt = sprintf("SELECT * FROM iv_articulos
				WHERE id_articulo = %s;",
					valTpDato($idArticulo, "int"));
				$rsArt = mysql_query($queryArt);
				if (!$rsArt) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArt = mysql_fetch_assoc($rsArt);
				
				// VERIFICA SI EL ARTICULO ESTA LIGADO A LA EMPRESA
				$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
				WHERE id_empresa = %s
					AND id_articulo = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"));
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
				
				if ($totalRowsArtEmp == 0) { // SI NO EXISTE EL ARTICULO PARA LA EMPRESA
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				} else { // SI EXISTE EL ARTICULO PARA LA EMRPESA
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
				
				
				// VERIFICA SI HAY RELACION ENTRE ARTICULO Y LA UBICACION SELECCIONADA
				$queryArtAlmacen = sprintf("SELECT * FROM vw_iv_articulos_almacen
				WHERE id_articulo = %s
					AND id_casilla = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				$rsArtAlmacen = mysql_query($queryArtAlmacen);
				if (!$rsArtAlmacen) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsArtAlmacen = mysql_num_rows($rsArtAlmacen);
				$rowArtAlmacen = mysql_fetch_assoc($rsArtAlmacen);
				if ($totalRowsArtAlmacen == 0) { // SI EL ARTICULO NO TIENE UBICACION, SE LE ASIGNA LA SELECCIONADA
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
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						// LE AGREGA LA UBICACION AL ARTICULO SUSTITUTO
						$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus)
						VALUE (%s, %s, %s);",
							valTpDato($idCasilla, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
				if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				
				$idCasillaPredet = $rowArtEmp['id_casilla_predeterminada_compra'];
				
				// SI LA CASILLA SELECCIONADA ES DISTINTA A LA CASILLA PREDETERMINADA
				if ($idCasilla != $idCasillaPredet) {
					if ($idCasillaPredet == "") { // SI NO TIENE CASILLA PREDETERMINADA LE ASIGNA LA SELECCIONADA EN EL REGISTRO DE COMPRA
						$idCasillaPredet = $idCasilla;
						
						// MODIFICA LA CASILLA PREDETERMINADA DEL ARTICULO EN LA EMPRESA
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
							id_casilla_predeterminada_compra = %s,
							cantidad_pedida = 0,
							estatus = 1
						WHERE id_articulo_empresa = %s;",
							valTpDato($idCasilla, "int"),
							valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
				
				// ACTIVA LA UBICACION SELECCIONADA EN EL REGISTRO DE COMPRA
				$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
					estatus = 1
				WHERE id_articulo = %s
					AND id_casilla = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) {
					if (mysql_errno() == 1062) {
						return $objResponse->alert("Existe una ubicación que ya esta asignada a otro artículo");
					} else {
						return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					}
				}
				mysql_query("SET NAMES 'latin1';");
				
				
				// VERIFICACION PARA SABER SI LA CASILLA PREDETERMINADA ES VÁLIDA
				$queryArtAlm = sprintf("SELECT * FROM iv_articulos_almacen
				WHERE id_articulo = %s
					AND id_casilla = %s
					AND estatus = 1;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasillaPredet, "int"));
				$rsArtAlm = mysql_query($queryArtAlm);
				if (!$rsArtAlm) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
				$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
				if ($totalRowsArtAlm == 0) {
					// BUSCA LA PRIMERA UBICACION ACTIVA DEL ARTICULO PARA PONERSELA COMO PREDETERMINADA
					$queryArtAlm2 = sprintf("SELECT * FROM iv_articulos_almacen
					WHERE id_articulo = %s
						AND estatus = 1;",
						valTpDato($idArticulo, "int"));
					$rsArtAlm2 = mysql_query($queryArtAlm2);
					if (!$rsArtAlm2) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtAlm2 = mysql_num_rows($rsArtAlm2);
					$rowArtAlm2 = mysql_fetch_assoc($rsArtAlm2);
					
					if ($totalRowsArtAlm2 > 0) {
						$queryCasillaError = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
							valTpDato($idCasilla, "int"));
						$rsCasillaError = mysql_query($queryCasillaError);
						if (!$rsCasillaError) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$rowCasillaError = mysql_fetch_assoc($rsCasillaError);
						
						// MODIFICA LA CASILLA PREDETERMINADA DEL ARTICULO EN LA EMPRESA
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
							id_casilla_predeterminada_compra = %s
						WHERE id_articulo = %s;",
							valTpDato($rowArtAlm2['id_casilla'], "int"),
							valTpDato($idArticulo, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n\nId: ".$idCasilla."\nCasilla: ".$rowCasillaError['ubicacion']); }
						mysql_query("SET NAMES 'latin1';");
						
					} else if ($totalRowsArtAlm2 == 0) {
						// COMO NO TIENE CASILLAS ACTIVAS LE PONE COMO PREDETERMINADA NINGUNA
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
							id_casilla_predeterminada_compra = NULL
						WHERE id_articulo = %s;",
							valTpDato($idArticulo, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
			}
		}
		
		if ($idModoCompra == 2) { // 2 = Importacion
			// GUARDA LOS DATOS DE LAS FACTURA DE IMPORTACION
			$Result1 = guardarDctoImportacion($idFacturaCompra, $frmDcto, $frmTotalDcto, "PREREGISTRO");
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
		
		// VERIFICA SI LOS GASTOS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryFacturaCompraGastos = sprintf("SELECT * FROM iv_factura_compra_gasto
		WHERE id_factura_compra = %s
			AND id_modo_gasto IN (1,3);",
			valTpDato($idFacturaCompra, "int"));
		$rsFacturaCompraGastos = mysql_query($queryFacturaCompraGastos);
		if (!$rsFacturaCompraGastos) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($rowFacturaCompraGastos = mysql_fetch_assoc($rsFacturaCompraGastos)) {
			$idGasto = $rowFacturaCompraGastos['id_gasto'];
			
			$existRegDet = false;
			if (isset($frmTotalDcto['cbxGasto'])) {
				foreach ($frmTotalDcto['cbxGasto'] as $indice => $valor) {
					$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor]);
					$porcMontoGasto = str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valor]);
					
					if ($idGasto == $frmTotalDcto['hddIdGasto'.$valor] && round($montoGasto, 2) > 0) {
						// BUSCA LOS DATOS DEL GASTO
						$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",
							valTpDato($idGasto, "int"));
						$rsGasto = mysql_query($queryGasto);
						if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$rowGastos = mysql_fetch_assoc($rsGasto);
						
						$existRegDet = true;
						
						// EDITA LOS DATOS DEL GASTO
						$updateSQL = sprintf("UPDATE iv_factura_compra_gasto SET
							tipo = %s,
							porcentaje_monto = %s,
							monto = %s,
							id_iva = %s,
							iva = %s,
							id_modo_gasto = %s,
							afecta_documento = %s
						WHERE id_factura_compra_gasto = %s;",
							valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
							valTpDato($porcMontoGasto, "real_inglesa"),
							valTpDato($montoGasto, "real_inglesa"),
							valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"),
							valTpDato($frmTotalDcto['hddIvaGasto'.$valor], "real_inglesa"),
							valTpDato($rowGastos['id_modo_gasto'], "int"),
							valTpDato($rowGastos['afecta_documento'], "boolean"),
							valTpDato($rowFacturaCompraGastos['id_factura_compra_gasto'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
			}
			
			if ($existRegDet == false) {
				$deleteSQL = sprintf("DELETE FROM iv_factura_compra_gasto WHERE id_factura_compra_gasto = %s;",
					valTpDato($rowFacturaCompraGastos['id_factura_compra_gasto'], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
		}
		
		// INSERTA LOS GASTOS DEL PEDIDO
		if (isset($frmTotalDcto['cbxGasto'])) {
			foreach ($frmTotalDcto['cbxGasto'] as $indice => $valor) {
				$idGasto = $frmTotalDcto['hddIdGasto'.$valor];
				
				// VERIFICA SI LOS GASTOS ALMACENADOS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
				$queryFacturaCompraGastos = sprintf("SELECT * FROM iv_factura_compra_gasto
				WHERE id_gasto = %s
					AND id_factura_compra = %s;",
					valTpDato($idGasto, "int"),
					valTpDato($idFacturaCompra, "int"));
				$rsFacturaCompraGastos = mysql_query($queryFacturaCompraGastos);
				if (!$rsFacturaCompraGastos) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRowsFacturaCompraGastos = mysql_num_rows($rsFacturaCompraGastos);
				
				$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valor]);
				$porcMontoGasto = str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valor]);
				
				if (round($montoGasto, 2) > 0 && $totalRowsFacturaCompraGastos == 0) {
					$insertSQL = sprintf("INSERT INTO iv_factura_compra_gasto (id_factura_compra, id_gasto, tipo, porcentaje_monto, monto, id_iva, iva, id_modo_gasto, afecta_documento)
					SELECT %s, id_gasto, %s, %s, %s, %s, %s, id_modo_gasto, afecta_documento FROM pg_gastos WHERE id_gasto = %s;",
						valTpDato($idFacturaCompra, "int"),
						valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
						valTpDato($porcMontoGasto, "real_inglesa"),
						valTpDato($montoGasto, "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIvaGasto'.$valor], "int"),
						valTpDato($frmTotalDcto['hddIvaGasto'.$valor], "real_inglesa"),
						valTpDato($idGasto, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// ELIMINA LOS IMPUESTOS DE LA FACTURA DE COMPRA
		$deleteSQL = sprintf("DELETE FROM iv_factura_compra_iva WHERE id_factura_compra = %s;",
			valTpDato($idFacturaCompra, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		if ($idModoCompra == 1) { // 1 = Nacional
			// INSERTA LOS IMPUESTOS DEL PEDIDO
			for ($cont = 0; isset($frmTotalDcto['hddIdIva'.$cont]); $cont++) {
				if ($frmTotalDcto['txtSubTotalIva'.$cont] > 0) {
					$insertSQL = sprintf("INSERT INTO iv_factura_compra_iva (id_factura_compra, base_imponible, subtotal_iva, id_iva, iva, lujo)
					VALUE (%s, %s, %s, %s, %s, %s);",
						valTpDato($idFacturaCompra, "int"),
						valTpDato($frmTotalDcto['txtBaseImpIva'.$cont], "real_inglesa"),
						valTpDato($frmTotalDcto['txtSubTotalIva'.$cont], "real_inglesa"),
						valTpDato($frmTotalDcto['hddIdIva'.$cont], "int"),
						valTpDato($frmTotalDcto['txtIva'.$cont], "real_inglesa"),
						valTpDato($frmTotalDcto['hddLujoIva'.$cont], "boolean"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		// ACTUALIZA LOS MONTOS DE LA FACTURA DE COMPRA
		$updateSQL = sprintf("UPDATE iv_factura_compra SET
			monto_exento = IFNULL((SELECT SUM(fact_comp_det.pendiente * fact_comp_det.precio_unitario)
									FROM iv_factura_compra_detalle fact_comp_det
									WHERE (fact_comp_det.id_iva = 0 OR fact_comp_det.id_iva IS NULL)
										AND fact_comp_det.id_factura_compra = iv_factura_compra.id_factura_compra), 0)
							+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
									FROM iv_factura_compra_gasto fact_comp_gasto
									WHERE fact_comp_gasto.id_modo_gasto IN (1)
										AND fact_comp_gasto.afecta_documento IN (1)
										AND fact_comp_gasto.estatus_iva = 0
										AND fact_comp_gasto.id_factura_compra = iv_factura_compra.id_factura_compra), 0),
			monto_exonerado = 0,
			subtotal_factura = IFNULL((SELECT SUM(fact_comp_det.pendiente * fact_comp_det.precio_unitario)
										FROM iv_factura_compra_detalle fact_comp_det
										WHERE fact_comp_det.id_factura_compra = iv_factura_compra.id_factura_compra), 0),
			subtotal_descuento = (porcentaje_descuento * subtotal_factura) / 100,
			saldo_factura = %s
		WHERE id_factura_compra = %s;",
			valTpDato($frmTotalDcto['txtMontoTotalFactura'], "real_inglesa"),
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA LA CANTIDAD DE ARTICULOS PENDIENTES EN EL DETALLE DEL PEDIDO
		$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
			pendiente = cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
											FROM iv_factura_compra_detalle fact_comp_det
											WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
									+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
											FROM cp_factura_detalle fact_comp_det
											WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
												AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)),
			estatus = (CASE 
						WHEN (cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
												FROM iv_factura_compra_detalle fact_comp_det
												WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
													AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
										+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
												FROM cp_factura_detalle fact_comp_det
												WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
													AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) = 0 THEN
							1
						WHEN (cantidad - (IFNULL((SELECT SUM(fact_comp_det.pendiente)
												FROM iv_factura_compra_detalle fact_comp_det
												WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
													AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0)
										+ IFNULL((SELECT SUM(fact_comp_det.cantidad)
												FROM cp_factura_detalle fact_comp_det
												WHERE fact_comp_det.id_pedido_compra = iv_pedido_compra_detalle.id_pedido_compra
													AND fact_comp_det.id_articulo = iv_pedido_compra_detalle.id_articulo), 0))) > 0 THEN
							0
					END)
		WHERE estatus IN (0,1)
			AND id_pedido_compra IN (SELECT fact_comp_det.id_pedido_compra FROM iv_factura_compra_detalle fact_comp_det
									WHERE fact_comp_det.id_factura_compra IN (%s));",
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		mysql_query("COMMIT;");
		
		$objResponse->assign("txtIdFactura","value",$idFacturaCompra);
		
		$objResponse->alert("Registro de Compra Guardado con Éxito");
		
		$objResponse->script(sprintf("
		byId('btnCancelar').click();"));
	} else {
		
		$objResponse->alert("Existen artículos los cuales no tienen ubicación asignada");
	}
	
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
		$cantPedida = $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue();
		$cantRecibida = $archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue();
		
		if ($itemExcel == true && $cantRecibida <= $cantPedida && $cantRecibida > 0) {
			$arrayFilaImportar[] = array(
				$archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('G'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('H'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('I'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('J'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('K'.$i)->getValue(),
				$archivoExcel->getActiveSheet()->getCell('L'.$i)->getValue());
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
		foreach ($arrayFilaImportar as $indice => $valor) {
			$queryPedidoDet = sprintf("SELECT *
			FROM iv_pedido_compra_detalle ped_comp_det
				INNER JOIN iv_pedido_compra ped_comp ON (ped_comp_det.id_pedido_compra = ped_comp.id_pedido_compra)
				INNER JOIN iv_articulos art ON (ped_comp_det.id_articulo = art.id_articulo)
			WHERE ped_comp.id_pedido_compra_referencia = %s
				AND art.codigo_articulo LIKE %s
				AND ped_comp.estatus_pedido_compra = 2;",
				valTpDato($arrayFilaImportar[$indice][0], "text"),
				valTpDato($arrayFilaImportar[$indice][1], "text"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			
			$idPedidoCompraDetalle = $rowPedidoDet['id_pedido_compra_detalle'];
			
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
				foreach ($arrayObj as $indice2 => $valor2) {
					if ($frmListaArticulo['hddIdPedCompDetArt'.$valor2] == $idPedidoCompraDetalle) {
						$existe = true;
					}
				}
			}
			
			$idMonedaLocal = $frmDcto['hddIdMoneda'];
			$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
			
			if ($existe == false) {
				if (count($arrayObj) < $rowConfig6['valor']) {
					$cantPedida = $arrayFilaImportar[$indice][3];
					$cantRecibida = $arrayFilaImportar[$indice][4];
					$costoUnitario = $arrayFilaImportar[$indice][6];
					$almacen = $arrayFilaImportar[$indice][9];
					$ubicacion = $arrayFilaImportar[$indice][10];
					$idClienteArt = $arrayFilaImportar[$indice][11];
					
					$Result1 = insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, $idPedidoCompraDetalle, "", $idMonedaLocal, $idMonedaOrigen, $cantPedida, $cantRecibida, $costoUnitario, $almacen, $ubicacion, $idClienteArt);
					$arrayObjUbicacion = $Result1[3];
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$contFila = $Result1[2];
						$frmListaArticulo['hddIdPedCompDetArt'.$contFila] = $idPedidoCompraDetalle;
						$objResponse->script($Result1[1]);
						$arrayObj[] = $contFila;
					}
				} else {
					$msjCantidadExcedida = "Solo puede agregar un máximo de ".$rowConfig6['valor']." items por Registro";
				}
			} else {
				$arrayObjExiste[] = $arrayFilaImportar[$indice][1];
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

function insertarArticulo($idPedidoCompraDetalle, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
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
			if ($frmListaArticulo['hddIdPedCompDetArt'.$valor] == $idPedidoCompraDetalle) {
				$existe = true;
			}
		}
	}
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	if ($existe == false) {
		if (count($arrayObj) < $rowConfig6['valor']) {
			$Result1 = insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, $idPedidoCompraDetalle, "", $idMonedaLocal, $idMonedaOrigen);
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
				$idPedidoCompraDetalle = $frmArticuloMultiple['hddIdPedidoCompraDetalle'];
				
				if ($frmArticuloMultiple['txtCantEntregadaArtMult'.$cont] > 0) {
					$existe = false;
					if (isset($arrayObj)) {
						foreach ($arrayObj as $indice => $valor) {
							if ($frmListaArticulo['hddIdPedCompDetArt'.$valor] == $idPedidoCompraDetalle) {
								$existe = true;
							}
						}
					}
					
					if ($existe == false) {
						if (count($arrayObj) + $cantDetAgregados < $rowConfig6['valor']) {
							$Result1 = insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, $idPedidoCompraDetalle, "", $idMonedaLocal, $idMonedaOrigen, $frmArticuloMultiple['txtCantArtMult'.$cont], $frmArticuloMultiple['txtCantEntregadaArtMult'.$cont], $frmArticuloMultiple['txtCostoArtMult'.$cont]);
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
		art.descripcion
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
		
		$porcIvaArt = ($row['id_iva'] > 0 && $row['estatus_iva'] == 1) ? $row['iva'] : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
			if ($row['pendiente'] > 0) {
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarArticulo%s\" onclick=\"validarInsertarArticulo('%s','%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_pedido_compra_detalle'],
					$row['pendiente']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cantidad']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['pendiente']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_unitario'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$porcIvaArt."</td>";
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

function listaOtrosCargos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
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
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarOtroCargo%s\" onclick=\"validarInsertarCargo('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
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
	$sqlBusq .= $cond.sprintf("fact_comp.id_modulo = 3");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_comp.id_factura NOT IN (SELECT id_factura_compra_cargo
															FROM cp_factura_gasto fact_comp_gasto
																INNER JOIN cp_factura fact_comp ON (fact_comp_gasto.id_factura = fact_comp.id_factura)
															WHERE fact_comp.activa IS NOT NULL
																AND id_factura_compra_cargo IS NOT NULL)");
	
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
						AND fact_compra_gasto.id_modo_gasto IN (1)
						AND fact_compra_gasto.afecta_documento IN (1)), 0)
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

function guardarDctoImportacion($idFactura, $frmDcto, $frmTotalDcto, $accionFactura) {
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjOtroCargo = $frmTotalDcto['cbxOtroCargo'];
	
	if ($accionFactura == "PREREGISTRO") {
		// VERIFICA SI LOS CARGOS ALMACENADOS EN LA BD EN LA FACTURA DE COMPRA AUN ESTAN AGREGADOS EN EL FORMULARIO
		$query = sprintf("SELECT * FROM iv_factura_compra_gasto fact_comp_gasto
		WHERE fact_comp_gasto.id_factura_compra = %s
			AND fact_comp_gasto.id_modo_gasto = 2;",
			valTpDato($idFactura, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
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
				$deleteSQL = sprintf("DELETE FROM iv_factura_compra_gasto WHERE id_factura_compra_gasto = %s;",
					valTpDato($row['id_factura_compra_gasto'], "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
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
			
			$txtSubTotal = str_replace(",","",$frmTotalDcto['hddSubTotalFacturaGasto'.$hddItmGasto]);
			
			// INSERTA LOS CARGOS DE LA FACTURA
			if ($accionFactura == "PREREGISTRO") {
				if ($frmTotalDcto['hddIdFacturaCompraGasto'.$hddItmGasto] > 0) {
					$updateSQL = sprintf("UPDATE iv_factura_compra_gasto SET
						id_gasto = %s,
						tipo = %s,
						porcentaje_monto = %s,
						monto = %s,
						estatus_iva = %s,
						id_iva = %s,
						iva = %s,
						id_modo_gasto = %s,
						afecta_documento = %s,
						id_factura_compra_cargo = %s,
						id_condicion_gasto = %s
					WHERE id_factura_compra_gasto = %s;",
						valTpDato($frmTotalDcto['hddIdGastoCargo'.$hddItmGasto], "int"),
						valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
						valTpDato($frmTotalDcto['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
						valTpDato($txtSubTotal, "real_inglesa"),
						valTpDato(0, "boolean"), // 0 = No, 1 = Si
						valTpDato("", "int"),
						valTpDato(0, "real_inglesa"),
						valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
						valTpDato(0, "int"), // 0 = No, 1 = Si
						valTpDato($idFacturaCargo, "int"),
						valTpDato($hddCondicionGasto, "int"), // 1 = Real, 2 = Estimado;
						valTpDato($frmTotalDcto['hddIdFacturaCompraGasto'.$hddItmGasto], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
					mysql_query("SET NAMES 'latin1';");
				} else {
					$insertSQL = sprintf("INSERT INTO iv_factura_compra_gasto (id_factura_compra, id_gasto, tipo, porcentaje_monto, monto, estatus_iva, id_iva, iva, id_modo_gasto, afecta_documento, id_factura_compra_cargo, id_condicion_gasto)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($frmTotalDcto['hddIdGastoCargo'.$hddItmGasto], "int"),
						valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
						valTpDato($frmTotalDcto['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
						valTpDato($txtSubTotal, "real_inglesa"),
						valTpDato(0, "boolean"), // 0 = No, 1 = Si
						valTpDato("", "int"),
						valTpDato(0, "real_inglesa"),
						valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
						valTpDato(0, "int"), // 0 = No, 1 = Si
						valTpDato($idFacturaCargo, "int"),
						valTpDato($hddCondicionGasto, "int")); // 1 = Real, 2 = Estimado;
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
					mysql_query("SET NAMES 'latin1';");
				}
			} else if ($accionFactura == "REGISTRO") {
				$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, porcentaje_monto, monto, estatus_iva, id_iva, iva, id_modo_gasto, id_factura_compra_cargo, id_condicion_gasto)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['hddIdGastoCargo'.$hddItmGasto], "int"),
					valTpDato(1, "int"), // 0 = Porcentaje, 1 = Monto Fijo
					valTpDato($frmTotalDcto['txtPorcGasto'.$hddItmGasto], "real_inglesa"),
					valTpDato($txtSubTotal, "real_inglesa"),
					valTpDato(0, "boolean"), // 0 = No, 1 = Si
					valTpDato("", "int"),
					valTpDato(0, "real_inglesa"),
					valTpDato(2, "int"), // 1 = Gastos, 2 = Otros Cargos, 3 = Gastos de Importacion
					valTpDato($idFacturaCargo, "int"),
					valTpDato($hddCondicionGasto, "int")); // 1 = Real, 2 = Estimado;;
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	return array(true, "");
}

$xajax->register(XAJAX_FUNCTION,"aprobarDcto");
$xajax->register(XAJAX_FUNCTION,"asignarADV");
$xajax->register(XAJAX_FUNCTION,"asignarAlmacen");
$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFacturaCargo");
$xajax->register(XAJAX_FUNCTION,"asignarMoneda");
$xajax->register(XAJAX_FUNCTION,"asignarPais");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");

$xajax->register(XAJAX_FUNCTION,"buscarArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarDisponibilidadUbicacion");
$xajax->register(XAJAX_FUNCTION,"buscarPais");
$xajax->register(XAJAX_FUNCTION,"buscarPedido");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarRegistroCompra");

$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLst");
$xajax->register(XAJAX_FUNCTION,"cargaLstArancelGrupo");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstIva");
$xajax->register(XAJAX_FUNCTION,"cargaLstIvaItm");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstRetencionImpuesto");
$xajax->register(XAJAX_FUNCTION,"cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION,"cargaLstUbicacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstGrupoItem");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarDetalleCosto");
$xajax->register(XAJAX_FUNCTION,"cargarFacturaCargo");

$xajax->register(XAJAX_FUNCTION,"editarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarOtroCargo");
$xajax->register(XAJAX_FUNCTION,"exportarRegistroCompra");

$xajax->register(XAJAX_FUNCTION,"formAlmacen");
$xajax->register(XAJAX_FUNCTION,"formArticuloMultiple");
$xajax->register(XAJAX_FUNCTION,"formDatosCliente");
$xajax->register(XAJAX_FUNCTION,"formImportar");
$xajax->register(XAJAX_FUNCTION,"formListadoArticulosPedido");

$xajax->register(XAJAX_FUNCTION,"guardarDcto");

$xajax->register(XAJAX_FUNCTION,"importarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarArticuloMult");
$xajax->register(XAJAX_FUNCTION,"insertarOtroCargo");

$xajax->register(XAJAX_FUNCTION,"listaArticuloPedido");
$xajax->register(XAJAX_FUNCTION,"listaArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaOtrosCargos");
$xajax->register(XAJAX_FUNCTION,"listaPais");
$xajax->register(XAJAX_FUNCTION,"listaPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaRegistroCompra");

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
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputCompleto\" style=\"min-width:60px\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['porcentaje_grupo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['porcentaje_grupo']."\">".number_format($row['porcentaje_grupo'], 2, ".", ",")."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function insertarItemArticulo($objResponse, $arrayObj, $arrayObjUbicacion, $frmDcto, $contFila, $idPedidoCompraDetalle, $idFacturaCompraDetalle, $idMonedaLocal, $idMonedaOrigen, $cantPedida = "", $cantRecibida = "", $costoUnitario = "", $almacen = "", $ubicacion = "", $idClienteArt = "") {
	$contFila++;
	
	if ($idPedidoCompraDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle
		WHERE id_pedido_compra_detalle = %s;",
			valTpDato($idPedidoCompraDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$idPedComp = $rowPedidoDet['id_pedido_compra'];
		$idArticulo = $rowPedidoDet['id_articulo'];
		$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
		$cantRecibida = ($cantRecibida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['pendiente'] : $cantRecibida;
		$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $costoUnitario;
		$idIvaArt = ($idIvaArt == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_iva'] : $idIvaArt;
		$porcIvaArt = ($porcIvaArt == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['iva'] : $porcIvaArt;
		$porcIvaArt = ($porcIvaArt > 0) ? $porcIvaArt : "-";
		$hddEstatusIvaArt = ($hddEstatusIvaArt == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['estatus_iva'] : $hddEstatusIvaArt;
		$hddTipoArt = ($hddTipoArt == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['tipo'] : $hddTipoArt;
		$idClienteArt = ($idClienteArt == "" && $totalRowsPedidoDet > 0 && $ubicacion == "") ? $rowPedidoDet['id_cliente'] : $idClienteArt;
		
		// BUSCA LOS DATOS DEL IMPUESTO
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($idIvaArt, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$rowIva = mysql_fetch_assoc($rsIva);
		
		$lujoIva = ($rowIva['tipo'] == 2 || $rowIva['tipo'] == 3) ? "1" : "";
		
		// BUSCA LOS DATOS DEL PEDIDO
		$queryPedido = sprintf("SELECT * FROM iv_pedido_compra
		WHERE id_pedido_compra = %s;",
			valTpDato($idPedComp, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		$idEmpresa = $rowPedido['id_empresa'];
		$numeroReferencia = $rowPedido['id_pedido_compra_referencia'];
		
		if (!(count($arrayObj) > 0)) {
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
		} else {
			if ($frmDcto['txtIdProv'] > 0 && $frmDcto['txtIdProv'] != $rowPedido['id_proveedor'] && $contFila > 1) {
				return array(false, "Solo puede agregar items de Pedidos del mismo Proveedor", $arrayObjUbicacion);
			}
		}
	} else if ($idFacturaCompraDetalle > 0) {
		// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
		$queryPedidoDet = sprintf("SELECT
			vw_iv_ped_comp.id_empresa,
			fact_comp_det.id_pedido_compra_detalle,
			fact_comp_det.id_articulo,
			fact_comp_det.cantidad,
			fact_comp_det.pendiente,
			fact_comp_det.precio_unitario,
			fact_comp_det.id_iva,
			fact_comp_det.iva,
			fact_comp_det.id_arancel_familia,
			fact_comp_det.porcentaje_grupo,
			fact_comp_det.tipo,
			fact_comp_det.id_cliente,
			vw_iv_ped_comp.id_pedido_compra_referencia
		FROM vw_iv_pedidos_compra vw_iv_ped_comp
			INNER JOIN iv_factura_compra_detalle fact_comp_det ON (vw_iv_ped_comp.id_pedido_compra = fact_comp_det.id_pedido_compra)
		WHERE id_factura_compra_detalle = %s;",
			valTpDato($idFacturaCompraDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$idEmpresa = $rowPedidoDet['id_empresa'];
		
		$idPedidoCompraDetalle = $rowPedidoDet['id_pedido_compra_detalle'];
		$numeroReferencia = $rowPedidoDet['id_pedido_compra_referencia'];
		$idArticulo = ($idArticulo == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_articulo'] : $idArticulo;
		$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
		$cantRecibida = ($cantRecibida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['pendiente'] : $cantRecibida;
		$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $costoUnitario;
		$idIvaArt = ($idIvaArt == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_iva'] : $idIvaArt;
		$porcIvaArt = ($porcIvaArt == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['iva'] : $porcIvaArt;
		$porcIvaArt = ($porcIvaArt > 0) ? $porcIvaArt : "-";
		$hddIdArancelFamilia = ($hddIdArancelFamilia == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_arancel_familia'] : $hddIdArancelFamilia;
		$lstTarifaAdValorem = ($lstTarifaAdValorem == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['porcentaje_grupo'] : $lstTarifaAdValorem;
		$hddTipoArt = ($hddTipoArt == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['tipo'] : $hddTipoArt;
		$idClienteArt = ($idClienteArt == "" && $totalRowsPedidoDet > 0 && $ubicacion == "") ? $rowPedidoDet['id_cliente'] : $idClienteArt;
		
		// BUSCA LOS DATOS DEL IMPUESTO
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($idIvaArt, "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$rowIva = mysql_fetch_assoc($rsIva);
		
		$hddEstatusIvaArt = ($hddEstatusIvaArt == "" && $totalRowsPedidoDet > 0) ? $rowIva['estatus_iva'] : $hddEstatusIvaArt;
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
		valTpDato($rowArticulo['id_arancel_familia'], "int"));
	$rsArancelFamilia = mysql_query($queryArancelFamilia);
	if (!$rsArancelFamilia) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
	$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
	
	$arancelArticulo = sprintf("<span class=\"textoNegroNegrita_10px\" title=\"%s\">%s</span>",
		utf8_encode($rowArancelFamilia['descripcion_arancel']),
		utf8_encode($rowArancelFamilia['codigo_arancel']));
	
	if (!($hddIdArancelFamilia > 0)) {
		$hddIdArancelFamilia = $rowArancelFamilia['id_arancel_familia'];
	}
	
	if (!($lstTarifaAdValorem > 0)) {
		$lstTarifaAdValorem = $rowArancelFamilia['porcentaje_grupo'];
	}
	
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
	
	if ($idClienteArt > 0 && $idClienteArt != "") {
		$imgCliente = sprintf("<a class=\"modalImg\" id=\"aClienteItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_cliente.gif\" title=\"Ver Cliente\"/>",
			$contFila);
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><a class=\"modalImg\" id=\"aEditarItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a></td>".
			"<td>%s</td>".
			"<td class=\"%s texto_9px\"><table><tr><td>".
				"<a class=\"modalImg\" id=\"aAlmacenItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_transferir_para_almacen.gif\" title=\"Ubicación\"/>".
				"</td><td id=\"spanUbicacion:%s\" align=\"center\" nowrap=\"nowrap\" width=\"%s\" title=\"spanUbicacion:%s\">%s</td></tr></table></td>".
			"<td id=\"tdCodArt:%s\">%s</td>".
			"<td><div id=\"tdDescArt:%s\">%s</div>%s</td>".
			"<td><input type=\"text\" id=\"hddCantArt%s\" name=\"hddCantArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"hddCantRecibArt%s\" name=\"hddCantRecibArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdCantPend:%s\" align=\"right\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"hddCostoArt%s\" name=\"hddCostoArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddMontoDescuentoArt%s\" name=\"hddMontoDescuentoArt%s\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"hddIvaArt%s\" name=\"hddIvaArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaArt%s\" name=\"hddIdIvaArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaArt%s\" name=\"hddEstatusIvaArt%s\" value=\"%s\"></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdArancelFamilia%s\" name=\"hddIdArancelFamilia%s\" class=\"inputSinFondo\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"hddTotalArt%s\" name=\"hddTotalArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArtSust%s\" name=\"hddIdArtSust%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoDescuentoArt%s\" name=\"hddTipoDescuentoArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPorcDescuentoArt%s\" name=\"hddPorcDescuentoArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTotalDescuentoArt%s\" name=\"hddTotalDescuentoArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosArt%s\" name=\"hddGastosArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosImportNacArt%s\" name=\"hddGastosImportNacArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosImportArt%s\" name=\"hddGastosImportArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoArt%s\" name=\"hddTipoArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdClienteArt%s\" name=\"hddIdClienteArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedCompDetArt%s\" name=\"hddIdPedCompDetArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdFactCompDetArt%s\" name=\"hddIdFactCompDetArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblArticulo', '%s'); }
		byId('aAlmacenItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblAlmacen', '%s'); }
		byId('%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('hddTotalArt%s').onmouseover = function() { Tip('%s', TITLE, 'Detalle del Costo'); xajax_cargarDetalleCosto('%s', xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto')); }
		byId('hddTotalArt%s').onmouseout = function() { UnTip(); }",
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
				$contFila, $contFila, number_format(0, 2, ".", ","),
			$contFila, $contFila, $porcIvaArt,
				$contFila, $contFila, $idIvaArt,
				$contFila, $contFila, $hddEstatusIvaArt,
			cargaLstArancelGrupoItm("lstTarifaAdValorem".$contFila, $lstTarifaAdValorem),
				$contFila, $contFila, $hddIdArancelFamilia,
			$contFila, $contFila, number_format(($cantRecibida * $costoUnitario), 2, ".", ","),
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, "",
				$contFila, $contFila, 0,
				$contFila, $contFila, number_format(0, 2, ".", ","),
				$contFila, $contFila, number_format(0, 2, ".", ","),
				$contFila, $contFila, number_format($cantRecibida * $rowPedidoDet['gasto_unitario'], 2, ".", ","),
				$contFila, $contFila, number_format(0, 2, ".", ","),
				$contFila, $contFila, number_format(0, 2, ".", ","),
				$contFila, $contFila, $hddTipoArt,
				$contFila, $contFila, $idClienteArt,
				$contFila, $contFila, $idPedidoCompraDetalle,
				$contFila, $contFila, $idFacturaCompraDetalle,
				$contFila, $contFila, $idCasilla,
				$contFila, $contFila, $lujoIva,
		
		$contFila,
			$contFila,
		$contFila,
			$contFila,
		"lstTarifaAdValorem".$contFila,
				
		$contFila, $htmlCostosArt, $contFila,
		$contFila);
	
	if ($idClienteArt > 0) {
		$htmlItmPie .= sprintf("
		byId('aClienteItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblCliente', '%s'); }",
			$contFila, $idClienteArt);
	}
	
	$arrayObjUbicacionDet[0] = $idArticulo;
	$arrayObjUbicacionDet[1] = $idCasilla;
	$arrayObjUbicacion[] = $arrayObjUbicacionDet;
	
	return array(true, $htmlItmPie, $contFila, $arrayObjUbicacion);
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
			(SELECT expediente_det_cargo.id_expediente FROM iv_expediente_detalle_cargos expediente_det_cargo
			WHERE expediente_det_cargo.id_gasto = gasto.id_gasto
				AND expediente_det_cargo.id_factura_compra_cargo = fact_comp_cargo.id_factura) AS id_expediente
		FROM iv_factura_compra_gasto fact_comp_gasto
			LEFT JOIN cp_factura fact_comp_cargo ON (fact_comp_gasto.id_factura_compra_cargo = fact_comp_cargo.id_factura)
			INNER JOIN pg_gastos gasto ON (fact_comp_gasto.id_gasto = gasto.id_gasto)
			LEFT JOIN cp_proveedor prov ON (fact_comp_cargo.id_proveedor = prov.id_proveedor)
		WHERE fact_comp_gasto.id_factura_compra_gasto = %s;",
			valTpDato($idFacturaCompraGasto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
	}
	
	$txtFechaFacturaGasto = ($txtFechaFacturaGasto == "" && $totalRows > 0 && $row['fecha_origen'] != "") ? date(spanDateFormat, strtotime($row['fecha_origen'])) : $txtFechaFacturaGasto;
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
	if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGastos = mysql_fetch_assoc($rsGasto);
	
	if (!($hddCondicionGasto > 0)) {
		$hddCondicionGasto = ($rowGastos['asocia_documento'] == 1) ? 1 : 2; // 1 = Real, 2 = Estimado
	}
	$display = ($row['id_expediente'] > 0) ? "style=\"display:none\"" : ""; 
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieOtroCargo').before('".
		"<tr id=\"trItmOtroCargo:%s\" align=\"left\" class=\"textoGris_11px\">".
			"<td title=\"trItmOtroCargo:%s\"><input id=\"cbxItmOtroCargo\" name=\"cbxItmOtroCargo[]\" %s type=\"checkbox\" value=\"%s\"/>".
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
			$contFila, $display, $contFila,
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
	
	return array(true, $htmlItmPie, $contFila, $arrayObjUbicacion);
}
?>