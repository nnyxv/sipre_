<?php


function asignarMoneda($frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	
	$idEmpresa = $frmAsignacion['txtIdEmpresa'];
	
	$idMoneda = $frmAsignacion['hddIdMoneda'];
	$idMonedaFactura = ($frmAsignacion['hddIdMoneda'] == $frmAsignacion['lstMoneda']) ? $frmAsignacion['hddIdMoneda'] : $frmAsignacion['lstMoneda'];
	
	if ($idMonedaFactura != $idMoneda) {
		$queryTasaCambio = sprintf("SELECT * FROM pg_tasa_cambio
		WHERE id_moneda_extranjera = %s
			AND id_moneda_nacional = %s
			AND id_tasa_cambio = %s;",
			valTpDato($frmAsignacion['lstMoneda'], "int"),
			valTpDato($frmAsignacion['hddIdMoneda'], "int"),
			valTpDato($frmAsignacion['lstTasaCambio'], "int"));
		$rsTasaCambio = mysql_query($queryTasaCambio);
		if (!$rsTasaCambio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTasaCambio = mysql_fetch_assoc($rsTasaCambio);
		
		$objResponse->assign("txtTasaCambio","value",number_format($rowTasaCambio['monto_tasa_cambio'], 3, ".", ","));
	} else {
		$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
	}
	
	// BUSCA LOS DATOS DE LA MONEDA DE LA FACTURA
	$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaFactura, "int"));
	$rsMoneda = mysql_query($queryMoneda);
	if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMoneda = mysql_fetch_assoc($rsMoneda);
	
	$abrevMonedaFactura = $rowMoneda['abreviacion'];
	
	$objResponse->assign("hddIncluirImpuestos","value",$rowMoneda['incluir_impuestos']);
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaNacional = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMoneda, "int"));
	$rsMonedaNacional = mysql_query($queryMonedaNacional);
	if (!$rsMonedaNacional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaNacional = mysql_fetch_assoc($rsMonedaNacional);
	
	$abrevMonedaNacional = $rowMonedaNacional['abreviacion'];
	
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

function asignarFormaPago($frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmAsignacion['cbx1'];
	
	$existePlanMayor = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$idFormaPago = $frmAsignacion['lstFormaPago'.$valor.':'.$valor1[1]];
						
						if ($idFormaPago > 4) {
							$existePlanMayor = true;
						}
					}
				}
			}
		}
	}
	
	if ($existePlanMayor == true) {
		$objResponse->script("
		byId('trMotivo').style.display = '';
		
		byId('txtIdMotivo').className = 'inputHabilitado';");
	} else {
		$objResponse->script("
		byId('trMotivo').style.display = 'none';
		
		byId('txtIdMotivo').value = '';
		byId('txtMotivo').value = '';");
	}
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $asigDescuento = "true", $cerrarVentana = "true") {
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
		valTpDato($idProveedor, "text"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtIdProv","value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",htmlentities($rowProv['nombre']));
	$objResponse->assign("txtRifProv","value",htmlentities($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccionProv","innerHTML",htmlentities($rowProv['direccion']));
	$objResponse->assign("txtContactoProv","value",htmlentities($rowProv['contacto']));
	$objResponse->assign("txtEmailContactoProv","value",htmlentities($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonosProv","value",htmlentities($rowProv['telefono']));
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowProv['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaProveedor').click();");
	}
	
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

function buscarPlanPago($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPlanPago(0, "idAsignacion", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstTasaCambio(this.value); xajax_asignarMoneda(xajax.getFormValues('frmAsignacion'));\" style=\"width:150px\">";
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
	$html = "<select id=\"lstTasaCambio\" name=\"lstTasaCambio\" class=\"inputHabilitado\" onchange=\"xajax_asignarMoneda(xajax.getFormValues('frmAsignacion'));\" style=\"width:150px\">";
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

function eliminarPlanPago($idAsignacion, $frmListaPlanPago) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_asignar_plan_pago_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_asignacion WHERE idAsignacion = %s;",
		valTpDato($idAsignacion, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPlanPago(
		$frmListaPlanPago['pageNum'],
		$frmListaPlanPago['campOrd'],
		$frmListaPlanPago['tpOrd'],
		$frmListaPlanPago['valBusq']));
	
	return $objResponse;
}

function formAsignacion($idAsignacion, $frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	if ($idAsignacion > 0) {
		if (!xvalidaAcceso($objResponse,"an_pedido_compra","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAsignacion').click();"); return $objResponse; }
		
		$queryMoneda = sprintf("SELECT * FROM pg_monedas
		WHERE estatus = 1
			AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$idMoneda = $rowMoneda['idmoneda'];
		
		$query = sprintf("SELECT * FROM an_asignacion
		WHERE idAsignacion = %s;",
			valTpDato($idAsignacion, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdAsignacion","value",utf8_encode($idAsignacion));
		$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "Empresa", "ListaEmpresa", "", false));
		$objResponse->loadCommands(asignarProveedor($row['id_proveedor'], "true", "false"));
		$objResponse->assign("txtFecha","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_asignacion']))));
		$objResponse->assign("txtReferencia","value",utf8_encode($row['referencia_asignacion']));
		$objResponse->assign("txtAsignacion","value",utf8_encode($row['asunto_asignacion']));
		$objResponse->assign("txtFechaCierreCompra","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_cierre_compra']))));
		$objResponse->assign("txtFechaCierreVenta","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_cierre_venta']))));
		$objResponse->loadCommands(cargaLstMoneda($idMoneda));
		$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
		$objResponse->assign("hddIdMoneda","value",$idMoneda);
		$objResponse->assign("spanTituloUnidadAsignacion","innerHTML",utf8_encode($row['referencia_asignacion']));
		
		$objResponse->loadCommands(listaAsignacionDetalle(0, 'idDetalleAsignacion', 'ASC', $idAsignacion));
	}
	
	return $objResponse;
}

function guardarPlanPago($frmAsignacion, $frmListaPlanPago) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_compra","insertar")) { $objResponse->script("byId('btnCancelarAsignacion').click();"); return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmAsignacion['cbx1'];
	
	$idEmpresa = $frmAsignacion['txtIdEmpresa'];
	
	$idMonedaLocal = $frmAsignacion['hddIdMoneda'];
	$idMonedaOrigen = ($frmAsignacion['hddIdMoneda'] == $frmAsignacion['lstMoneda']) ? $frmAsignacion['hddIdMoneda'] : $frmAsignacion['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = str_replace(",","",$frmAsignacion['txtTasaCambio']);
	
	$fechaRegistroPlanPago = date("Y-m-d H:i:s");
	
	// VERIFICA SI A LA ASIGNACION SE LE GENERO SU PEDIDO
	$query = sprintf("SELECT * FROM an_pedido_compra WHERE idAsignacion = %s;",
		valTpDato($idAsignacion, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	if ($totalRows > 0) {
		return $objResponse->alert("Esta Asignación No Puede Generar Nuevamente un Pedido");
	}
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script("
			byId('txtIdMotivo').className = 'inputHabilitado'
			byId('txtCosto".$valor."').className = 'inputHabilitado'");
			
			$costoUnit = $frmAsignacion['txtCosto'.$valor];
			
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice1 => $valor1) {
					$objResponse->script("byId('lstFormaPago".$valor1."').className = 'inputHabilitado'");
					
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$idFormaPago = $frmAsignacion['lstFormaPago'.$valor.':'.$valor1[1]];
						
						if ($idFormaPago > 4 && !($frmAsignacion['txtIdMotivo'] > 0)) {
							$arrayInvalido[] = "txtIdMotivo";
						}
						
						if ($idFormaPago != 4 && $costoUnit <= 0) {
							$arrayInvalido[] = "txtCosto".$valor;
						}
						
						if (!($idFormaPago > 0)) {
							$arrayInvalido[] = "lstFormaPago".$valor.':'.$valor1[1];
						}
					}
				}
			}
		}
	}
	
	if (isset($arrayInvalido)) {
		foreach ($arrayInvalido as $indice => $valor) {
			$objResponse->script("byId('".$valor."').className = 'inputErrado'");
		}
		
		if (count($arrayInvalido) > 0) {
			return $objResponse->alert("Los campos señalados en rojo son invalidos");
		}
	}
	
	mysql_query("START TRANSACTION;");
	
	$insertSQL = sprintf("INSERT INTO an_pedido_compra (id_empresa, id_proveedor, idAsignacion, fecha_pedido, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, monto_tasa_cambio, estatus_pedido)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($frmAsignacion['txtIdEmpresa'], "int"),
		valTpDato($frmAsignacion['txtIdProv'], "int"),
		valTpDato($frmAsignacion['hddIdAsignacion'], "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($idMonedaLocal, "int"),
		valTpDato($idMonedaOrigen, "int"),
		valTpDato($frmAsignacion['lstTasaCambio'], "int"),
		valTpDato($frmAsignacion['txtTasaCambio'], "real_inglesa"),
		valTpDato(0, "int")); // 0 = Forma Pago Sin Asignar, 1 = Forma Pago Asignado Parcial, 2 = Forma Pago Asignado, 3 = Facturado, 5 = Anulado
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idPedidoCompra = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			// BUSCA LOS DATOS DEL DETALLE DE LA ASIGNACION
			$queryAsignacionDetalle = sprintf("SELECT * FROM an_det_asignacion
			WHERE idDetalleAsignacion = %s;",
				valTpDato($frmAsignacion['hddIdDetalleAsignacion'.$valor], "int"));
			$rsAsignacionDetalle = mysql_query($queryAsignacionDetalle);
			if (!$rsAsignacionDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowAsignacionDetalle = mysql_fetch_assoc($rsAsignacionDetalle);
			
			$idUnidadBasica = $rowAsignacionDetalle['idUnidadesBasicas'];
			$idCliente = $rowAsignacionDetalle['idCliente'];
			$flotilla = $rowAsignacionDetalle['flotilla'];
			
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$costoUnitario = str_replace(",","",$frmAsignacion['txtCosto'.$valor]);
						
						$idFormaPago = $frmAsignacion['lstFormaPago'.$valor.':'.$valor1[1]];
						$costoUnitario = ($idFormaPago == 4) ? 0 : $costoUnitario;
						$fechaPlanPago = ($idFormaPago == 4) ? "" : $fechaRegistroPlanPago;
						
						$insertSQL = sprintf("INSERT INTO an_solicitud_factura (idPedidoCompra, idUnidadBasica, id_cliente, numero_vehiculo, flotilla, costo_unidad, idFormaPagoAsignacion, estado, fecha_registro_plan_pago)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
							valTpDato($idPedidoCompra, "int"),
							valTpDato($idUnidadBasica, "int"),
							valTpDato($idCliente, "int"),
							valTpDato($valor1[1], "int"),
							valTpDato($flotilla, "boolean"), // 0 = No, 1 = Si
							valTpDato($costoUnitario, "real_inglesa"),
							valTpDato($idFormaPago, "int"),
							valTpDato(1, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Transito, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
							valTpDato($fechaPlanPago, "date"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$idPedidoCompraDetalle = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						
						if ($idFormaPago > 4) {
							$costoUnitario = ($idModoCompra == 1) ? $costoUnitario : $costoUnitario * $txtTasaCambio;
							
							$Result1 = guardarNotaCargoCxP($idFormaPago, $costoUnitario, $idPedidoCompraDetalle, $frmAsignacion, $frmListaPlanPago);
							if ($Result1[0] != true && strlen($Result1[1]) > 0) {
								return $objResponse->alert($Result1[1]);
							} else if ($Result1[0] == true) {
								$arrayIdDctoContabilidad[] = array(
									$Result1[1],
									$Result1[2],
									"NOTA_CARGO_CXP");
								$idNotaCargoCxP = $Result1[1];
							}
						}
					}
				}
			}
		}
	}
	
	// BUSCA LOS DATOS DEL PEDIDO
	$query = sprintf("SELECT
		(SELECT COUNT(ped_comp_det.idPedidoCompra) FROM an_solicitud_factura ped_comp_det
		WHERE ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra) AS cantidad,
		
		(SELECT COUNT(ped_comp_det.idPedidoCompra) FROM an_solicitud_factura ped_comp_det
		WHERE ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra
			AND ped_comp_det.idFormaPagoAsignacion IN (4)) AS cantidad_pendiente,
			
		(SELECT COUNT(ped_comp_det.idPedidoCompra) FROM an_solicitud_factura ped_comp_det
		WHERE ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra
			AND ped_comp_det.idFormaPagoAsignacion NOT IN (4)) AS cantidad_forma_pago
		
	FROM an_pedido_compra ped_comp
	WHERE ped_comp.idPedidoCompra = %s;",
		valTpDato($idPedidoCompra, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['cantidad_pendiente'] == $row['cantidad']) {
		$estatusPedido = 0; // FORMA PAGO SIN ASIGNAR
	} else if ($row['cantidad_forma_pago'] == $row['cantidad']) {
		$estatusPedido = 2; // FORMA PAGO ASIGNADO
	} else {
		$estatusPedido = 1; // FORMA PAGO PARCIAL ASIGNADO
	}
	
	// ACTUALIZA EL ESTADO DEL PEDIDO DE COMPRA
	$updateSQL = sprintf("UPDATE an_pedido_compra SET
		estatus_pedido = %s
	WHERE idPedidoCompra = %s;",
		valTpDato($estatusPedido, "int"),
		valTpDato($idPedidoCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// GUARDA LA CABECERA DE LA CARTA DE SOLICITUD
	$insertSQL = sprintf("INSERT INTO an_encabezadocartasolicitud (idPedidoCompra, fechaCartaSolicitud)
	VALUES (%s, %s);",
		valTpDato($idPedidoCompra, "int"),
		valTpDato(date("Y-m-d"), "date"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idCarta = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// BUSCA LAS UNIDADES DEL PEDIDO CON FORMA DE PAGO ASIGNADO
	$queryPedidoDetalle = sprintf("SELECT * FROM an_solicitud_factura
	WHERE idPedidoCompra = %s
		AND fecha_registro_plan_pago = %s
		AND estado = 1
		AND idFormaPagoAsignacion NOT IN (4);",
		valTpDato($idPedidoCompra, "int"),
		valTpDato($fechaRegistroPlanPago, "date"));
	$rsPedidoDetalle = mysql_query($queryPedidoDetalle);
	if (!$rsPedidoDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPedidoDetalle = mysql_num_rows($rsPedidoDetalle);
	while ($rowPedidoDetalle = mysql_fetch_assoc($rsPedidoDetalle)) {
		$insertSQL = sprintf("INSERT INTO an_detallecartasolicitud (idCartaSolicitud, idSolicitud)
		VALUES (%s, %s);",
			valTpDato($idCarta, "int"),
			valTpDato($rowPedidoDetalle['idSolicitud'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL ESTADO DE LA UNIDAD EN EL DETALLE DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_solicitud_factura SET
			estado = %s
		WHERE idSolicitud = %s;",
			valTpDato(2, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Aprobado, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
			valTpDato($rowPedidoDetalle['idSolicitud'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA_CARGO_CXP") {
				$idNotaCargo = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarNotasCargoCpRe")) { generarNotasCargoCpRe($idNotaCargo,"",""); } break;
					case 1 : if (function_exists("generarNotasCargoCpSe")) { generarNotasCargoCpSe($idNotaCargo,"",""); } break;
					case 2 : if (function_exists("generarNotasCargoCpVe")) { generarNotasCargoCpVe($idNotaCargo,"",""); } break;
					case 3 : if (function_exists("generarNotasCargoCpAd")) { generarNotasCargoCpAd($idNotaCargo,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert("Pedido de Compra guardado con éxito.");
	
	$objResponse->script("byId('btnCancelarAsignacion').click();");
	
	$objResponse->loadCommands(listaPlanPago(
		$frmListaPlanPago['pageNum'],
		$frmListaPlanPago['campOrd'],
		$frmListaPlanPago['tpOrd'],
		$frmListaPlanPago['valBusq']));
	
	return $objResponse;
}

function importarDcto($frmImportarPedido, $frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmAsignacion['cbx1'];
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$frmImportarPedido['hddUrlArchivo'];
	
	$phpExcel = new PHPExcel_Reader_Excel2007();
	$archivoExcel = $phpExcel->load($inputFileName);
	
	$archivoExcel->setActiveSheetIndex(0);
	$i = 1;
	while (strlen($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()) > 0) {
		$cantAsignada = $archivoExcel->getActiveSheet()->getCell('G'.$i)->getValue();
		$cantAceptada = $archivoExcel->getActiveSheet()->getCell('H'.$i)->getValue();
		$cantConfirmada = $archivoExcel->getActiveSheet()->getCell('I'.$i)->getValue();
		$formaPago = $archivoExcel->getActiveSheet()->getCell('K'.$i)->getValue();
		
		if ($itemExcel == true && ((doubleval($cantAsignada) > 0 && doubleval($cantAceptada) > 0 && doubleval($cantConfirmada) > 0) || strlen($formaPago) > 0)) {
			$arrayFilaImportar[] = array(
				"id_asignacion"		=> $archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(), // Id. Asignacion
				"nro_referencia"	=> $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(), // Nro. Referencia
				"id_unidad"			=> $archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue(), // Id. Unidad
				"codigo_unidad"		=> $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue(), // Código Unidad
				"id_cliente"		=> $archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue(), // Id. Cliente
				"ci_cliente"		=> $archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue(), // C.I. / R.I.F.
				"cant_asignada"		=> $archivoExcel->getActiveSheet()->getCell('G'.$i)->getValue(), // Asignados
				"cant_aceptada"		=> $archivoExcel->getActiveSheet()->getCell('H'.$i)->getValue(), // Aceptados
				"cant_confirmada"	=> $archivoExcel->getActiveSheet()->getCell('I'.$i)->getValue(), // Confirmados
				"costo_unitario"	=> $archivoExcel->getActiveSheet()->getCell('J'.$i)->getValue(), // Costo Unit.
				"forma_pago"		=> $archivoExcel->getActiveSheet()->getCell('K'.$i)->getValue()); // Forma Pago
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == strtoupper("Id. Asignación")
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Id. Asignación")
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Id. Asignación")
		|| trim(strtoupper(htmlentities($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Id. Asignación")
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == strtoupper("Id. Asignacion")) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayFilaImportar)) {
		foreach ($arrayFilaImportar as $indiceFila => $valorFila) {
			// RUTINA PARA AGREGAR EL ARTICULO
			$idEmpresa = ($frmAsignacion['txtIdEmpresa'] > 0) ? $frmAsignacion['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];
			
			// BUSCA LOS DATOS DEL DETALLE DE LA ASIGNACION
			$queryAsignacionDetalle = sprintf("SELECT 
				asig_det.*,
				vw_an_uni_bas.nom_uni_bas,
				cliente.id,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
			FROM an_det_asignacion asig_det
				INNER JOIN vw_an_unidad_basica vw_an_uni_bas ON (asig_det.idUnidadesBasicas = vw_an_uni_bas.id_uni_bas)
				LEFT JOIN cj_cc_cliente cliente ON (asig_det.idCliente = cliente.id)
				INNER JOIN an_uni_bas uni_bas ON (asig_det.idUnidadesBasicas = uni_bas.id_uni_bas)
			WHERE asig_det.idAsignacion = %s
				AND uni_bas.nom_uni_bas LIKE %s
				AND (CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s OR (cliente.id IS NULL AND %s IS NULL))
				AND asig_det.cantidadConfirmada = %s
			ORDER BY asig_det.idDetalleAsignacion ASC;",
				valTpDato($arrayFilaImportar[$indiceFila]['id_asignacion'], "int"),
				valTpDato($arrayFilaImportar[$indiceFila]['codigo_unidad'], "text"),
				valTpDato($arrayFilaImportar[$indiceFila]['ci_cliente'], "text"),
				valTpDato($arrayFilaImportar[$indiceFila]['ci_cliente'], "text"),
				valTpDato($arrayFilaImportar[$indiceFila]['cant_confirmada'], "real_inglesa"));
			$rsAsignacionDetalle = mysql_query($queryAsignacionDetalle);
			if (!$rsAsignacionDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsAsignacionDetalle = mysql_num_rows($rsAsignacionDetalle);
			$rowAsignacionDetalle = mysql_fetch_assoc($rsAsignacionDetalle);
			
			$idDetalleAsignacion = $rowAsignacionDetalle['idDetalleAsignacion'];
			$idUnidadBasica = $rowAsignacionDetalle['idUnidadesBasicas'];
			$idCliente = $rowAsignacionDetalle['id'];
			
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if ($frmAsignacion['hddIdDetalleAsignacion'.$valor] == $idDetalleAsignacion && $idDetalleAsignacion > 0) {
						$existe = true;
						
						$objResponse->assign("txtCosto".$valor,"value",number_format($arrayFilaImportar[$indiceFila]['costo_unitario'], 2, ".", ","));
						
						if (isset($arrayObj1)) {
							foreach ($arrayObj1 as $indice1 => $valor1) {
								$valor1 = explode(":", $valor1);
								
								if ($valor1[0] == $valor) {
									$indiceFila++;
									
									$formaPago = explode(")",$arrayFilaImportar[$indiceFila]['forma_pago']);
									
									$objResponse->call("selectedOption","lstFormaPago".$valor1[0].":".$valor1[1],$formaPago[0]);
								}
							}
						}
					}
				}
			}
			
			if ($existe == false) {
				if ($totalRowsAsignacionDetalle > 0) {
				} else if (doubleval($cantAsignada) > 0 && doubleval($cantAceptada) > 0 && doubleval($cantConfirmada) > 0) {
					$arrayObjNoExiste[] = $arrayFilaImportar[$indiceFila][0];
				}
			} else {
				//$arrayObjExiste[] = $arrayFilaImportar[$indiceFila][0];
			}
		}
		
		if (strlen($msjCantidadExcedida) > 0)
			$objResponse->alert(utf8_encode($msjCantidadExcedida));
			
		if (count($arrayObjNoExiste) > 0)
			$objResponse->alert(("No existe(n) en el sistema ".count($arrayObjNoExiste)." items: ".implode(", ",$arrayObjNoExiste)));
			
		if (count($arrayObjExiste) > 0) {
			$objResponse->alert(("Ya se encuentra(n) incluido(s) ".count($arrayObjExiste)." items: ".implode(", ",$arrayObjExiste)));
		} else if (count($arrayObj) > 0) {
			$objResponse->alert(("Asignación importada con éxito."));
		} else {
			$objResponse->alert(("No se pudo importar el archivo"));
		}
		
		$objResponse->script("
		byId('btnCancelarImportarPedido').click();");
		
		$objResponse->script("xajax_asignarFormaPago(xajax.getFormValues('frmAsignacion'));");
	} else {
		$objResponse->alert(utf8_encode("Verifique que el pedido tenga cantidades solicitadas"));
	}
	
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

function listaPlanPago($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("asig.estatus_asignacion IN (3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("asig.idAsignacion NOT IN (SELECT ped_comp.idAsignacion FROM an_pedido_compra ped_comp)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("asig.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("asig.fecha_asignacion BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(asig.asunto_asignacion LIKE %s
		OR asig.referencia_asignacion LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT asig.*,
		prov.nombre AS nombre_proveedor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_asignacion asig
		INNER JOIN cp_proveedor prov ON (asig.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (asig.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaPlanPago", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPlanPago", "8%", $pageNum, "fecha_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPlanPago", "8%", $pageNum, "idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Asignacion");
		$htmlTh .= ordenarCampo("xajax_listaPlanPago", "14%", $pageNum, "referencia_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Referencia");
		$htmlTh .= ordenarCampo("xajax_listaPlanPago", "22%", $pageNum, "asunto_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Asignación");
		$htmlTh .= ordenarCampo("xajax_listaPlanPago", "34%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities(date(spanDateFormat, strtotime($row['fecha_asignacion'])))."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['idAsignacion'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['referencia_asignacion'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['asunto_asignacion'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAceptar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAsignacion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_asignar_plan_pago.png\" title=\"Asignar Plan de Pago\"/></a>",
					$contFila,
					$row['idAsignacion']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_asignacion_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Asignación PDF\"/>",
					$row['idAsignacion']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"reportes/an_asignacion_excel.php?idAsignacion=%s\"><img class=\"puntero\" src=\"../img/iconos/page_excel.png\" title=\"Exportar Excel\"/></a>",
				$row['idAsignacion']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s');\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar Asignación\"/></td>",
				$row['idAsignacion']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPlanPago(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPlanPago(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPlanPago(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPlanPago(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPlanPago(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPlanPago","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaAsignacionDetalle($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("asig.estatus_asignacion IN (3,4)");
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("det_asig.cantidadConfirmada > 0");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("asig.idAsignacion = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT
		det_asig.idDetalleAsignacion,
		asig.idAsignacion,
		asig.id_proveedor,
		asig.estatus_asignacion,
		vw_iv_modelo.id_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		SUM(det_asig.cantidadConfirmada) AS cant_confirmada,
		det_asig.flotilla
	FROM an_det_asignacion det_asig
		INNER JOIN an_asignacion asig ON (det_asig.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (det_asig.idUnidadesBasicas = vw_iv_modelo.id_uni_bas)
		LEFT JOIN cj_cc_cliente cliente ON (det_asig.idCliente = cliente.id) %s
	GROUP BY det_asig.idUnidadesBasicas, det_asig.flotilla", $sqlBusq);
	
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
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<fieldset><legend class=\"legend\">Nro. Unidades : %s -> %s</legend>",
					number_format($row['cant_confirmada'], 2, "." , ","),
					utf8_encode($row['vehiculo']));
					$htmlTb .= "<table class=\"texto_9px\" width=\"100%\">";
					$htmlTb .= "<tr align=\"left\" height=\"24\">";
						$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\" width=\"30%\"><span class=\"textoRojoNegrita\">*</span>Precio Total de la Factura por Unidad:</td>";
						$htmlTb .= "<td width=\"20%\">";
							$htmlTb .= sprintf("<input type=\"text\" id=\"txtCosto%s\" name=\"txtCosto%s\" class=\"inputHabilitado\" maxlength=\"16\" onblur=\"setFormatoRafk(this,2);\" onkeypress=\"return validarSoloNumerosReales(event)\" size=\"16\" style=\"text-align:right\" value=\"%s\"/>",
								$contFila,
								$contFila,
								number_format(0, 2, "." , ","));
						$htmlTb .= "</td>";
						$htmlTb .= "<td colspan=\"2\" width=\"30%\"></td>";
						$htmlTb .= "<td width=\"20%\">";
							$htmlTb .= sprintf("<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
							"<input type=\"hidden\" id=\"hddIdDetalleAsignacion%s\" name=\"hddIdDetalleAsignacion%s\" value=\"%s\">",
								$contFila,
								$contFila, $contFila, $row['idDetalleAsignacion']);
						$htmlTb .= "</td>";
					$htmlTb .= "</tr>";
					for ($contFila2 = 1; $contFila2 <= $row['cant_confirmada']; $contFila2++) {
						$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
						
						switch($row['flotilla']) {
							case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
							case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
							default : $imgEstatusUnidadAsignacion = "";
						}
						
						$htmlTb .= (fmod($contFila2, 2) == 1) ? "<tr align=\"left\" class=\"".$clase."\" height=\"24\">" : "";
							
							$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
							$htmlTb .= "<td>".utf8_encode($row['vehiculo'])."</td>";
							$htmlTb .= "<td>";
								$htmlTb .= cargaLstFormaPagoItm("lstFormaPago".$contFila.":".$contFila2);
								$htmlTb .= sprintf("<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">",
									$contFila.":".$contFila2);
							$htmlTb .= "</td>";
							
						$htmlTb .= (fmod($contFila2, 2) == 0) ? "</tr>" : "";
					}
					$htmlTb .= "</table>";
				$htmlTb .= "</fieldset>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$totalUnidades += $row['cant_confirmada'];
	}
	$htmlTb .= "<tr>";
		$htmlTb .= "<td>";
			$htmlTb .= "<table width=\"100%\">";
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" width=\"80%\">"."Total de Unidades:"."</td>";
				$htmlTb .= "<td width=\"20%\">".number_format($totalUnidades, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadAsignacion","innerHTML",$htmlTblIni.$htmlTb.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarMoneda");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarFormaPago");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarPlanPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION,"eliminarPlanPago");
$xajax->register(XAJAX_FUNCTION,"formAsignacion");
$xajax->register(XAJAX_FUNCTION,"guardarPlanPago");
$xajax->register(XAJAX_FUNCTION,"importarDcto");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaPlanPago");
$xajax->register(XAJAX_FUNCTION,"listaAsignacionDetalle");

function cargaLstFormaPagoItm($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT 
		forma_pago.idFormaPagoAsignacion,
		prov.id_proveedor,
		(CASE
			WHEN prov.nombre IS NOT NULL THEN
				CONCAT_WS(' ', forma_pago.descripcionFormaPagoAsignacion, prov.nombre)
			ELSE
				forma_pago.descripcionFormaPagoAsignacion
		END) AS descripcion_forma_pago,
		forma_pago.alias,
		prov_cred.planMayor
	FROM cp_proveedor prov
		RIGHT JOIN formapagoasignacion forma_pago ON (prov.id_proveedor = forma_pago.idProveedor)
		LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	WHERE (prov.status = 'Activo' OR prov.status IS NULL)
		AND (prov_cred.planMayor = 1 OR prov_cred.planMayor IS NULL)
		AND (prov.credito = 'Si' OR prov.credito IS NULL)
	GROUP BY forma_pago.idFormaPagoAsignacion
	ORDER BY planMayor, forma_pago.descripcionFormaPagoAsignacion, prov.nombre");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_asignarFormaPago(xajax.getFormValues('frmAsignacion'));\" style=\"width:100%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idFormaPagoAsignacion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPagoAsignacion']."\">".htmlentities($row['descripcion_forma_pago'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function guardarNotaCargoCxP($idFormaPago, $costoUnitario, $idPedidoCompraDetalle, $frmAsignacion, $frmListaPlanPago) {
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$idEmpresa = $frmAsignacion['txtIdEmpresa'];
	
	// BUSCA LOS DATOS DEL PROVEEDOR DEL PLAN MAYOR
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		prov.nombre,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.direccion,
		prov.contacto,
		prov.correococtacto,
		prov.telefono,
		prov.fax,
		prov.credito,
		(SELECT prov_cred.diascredito FROM cp_prove_credito prov_cred
		WHERE prov_cred.id_proveedor = prov.id_proveedor) AS diascredito
	FROM cp_proveedor prov
	WHERE prov.id_proveedor = (SELECT forma_pago.idProveedor FROM formapagoasignacion forma_pago
								WHERE forma_pago.idFormaPagoAsignacion = %s);",
		valTpDato($idFormaPago, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$idProveedor = $rowProv['id_proveedor'];
	$txtDiasCreditoProv = ($rowProv['diascredito'] > 0) ? $rowProv['diascredito'] : 0;
	$lstTipoPago = ($rowProv['diascredito'] == 'Si' || $rowProv['diascredito'] == 1) ? 0 : 1; // 0 = Credito, 1 = Contado
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(3, "int"), // 3 = Nota Cargo CxP
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
	
	$idMotivo = $frmAsignacion['txtIdMotivo'];
	$precioUnitario = $costoUnitario;
	$txtFechaRegistro = date(spanDateFormat);
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$txtFechaProveedor = date(spanDateFormat);
	$txtFechaVencimiento = ($lstTipoPago == 0) ? suma_fechas(spanDateFormat,$txtFechaProveedor,$txtDiasCreditoProv) : $txtFechaProveedor;
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
	$txtObservacion = "VEHICULO POR PLAN MAYOR";
	
	// GUARDA LOS DATOS DE LA NOTA DE CARGO
	$insertSQL = sprintf("INSERT INTO cp_notadecargo (id_empresa, numero_notacargo, numero_control_notacargo, fecha_notacargo, fecha_vencimiento_notacargo, fecha_origen_notacargo, id_proveedor, id_modulo, estatus_notacargo, observacion_notacargo, tipo_pago_notacargo, monto_exento_notacargo, monto_exonerado_notacargo, subtotal_notacargo, subtotal_descuento_notacargo, total_cuenta_pagar, saldo_notacargo, aplica_libros_notacargo, chasis, id_detalles_pedido_compra, id_empleado_creador)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($numeroActual, "text"),
		valTpDato($numeroActual, "text"),
		valTpDato(date("Y-m-d", strtotime($txtFechaProveedor)), "date"),
		valTpDato(date("Y-m-d", strtotime($txtFechaVencimiento)), "date"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistro)), "date"),
		valTpDato($idProveedor, "int"),
		valTpDato($idModulo, "int"),
		valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato($txtObservacion, "text"),
		valTpDato($lstTipoPago, "int"), // 0 = Credito, 1 = Contado
		valTpDato($txtMontoExento, "real_inglesa"),
		valTpDato($txtMontoExonerado, "real_inglesa"),
		valTpDato($txtSubTotalNotaCargo, "real_inglesa"),
		valTpDato($txtSubTotalDescuento, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato($txtTotalNotaCargo, "real_inglesa"),
		valTpDato(0, "int"), // 1 = Si, 0 = No
		valTpDato("", "text"),
		valTpDato($idPedidoCompraDetalle, "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idNotaCargo = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DEL DOCUMENTO
	$insertSQL = sprintf("INSERT INTO cp_notacargo_detalle_motivo (id_notacargo, id_motivo, precio_unitario)
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
	$updateSQL = sprintf("UPDATE cp_notadecargo SET
		id_motivo = %s
	WHERE id_notacargo = %s;",
		valTpDato($idMotivo, "int"),
		valTpDato($idNotaCargo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("ND", "text"),
		valTpDato($idNotaCargo, "int"),
		valTpDato(date("Y-m-d", strtotime($txtFechaRegistro)), "date"),
		valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL CREDITO DISPONIBLE
	$updateSQL = sprintf("UPDATE cp_prove_credito prov_cred SET
		saldoDisponible = limitecredito - (IFNULL((SELECT SUM(fact_comp.saldo_factura) FROM cp_factura fact_comp
													WHERE fact_comp.id_proveedor = prov_cred.id_proveedor
														AND fact_comp.estatus_factura IN (0,2)), 0)
											+ IFNULL((SELECT SUM(nota_cargo.saldo_notacargo) FROM cp_notadecargo nota_cargo
													WHERE nota_cargo.id_proveedor = prov_cred.id_proveedor
														AND nota_cargo.estatus_notacargo IN (0,2)), 0)
											- IFNULL((SELECT SUM(anticip.saldoanticipo) FROM cp_anticipo anticip
													WHERE anticip.id_proveedor = prov_cred.id_proveedor
														AND anticip.estado IN (1,2)), 0)
											- IFNULL((SELECT SUM(nota_cred.saldo_notacredito) FROM cp_notacredito nota_cred
													WHERE nota_cred.id_proveedor = prov_cred.id_proveedor
														AND nota_cred.estado_notacredito IN (1,2)), 0))
	WHERE prov_cred.id_proveedor = %s;",
		valTpDato($idProveedor, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$script = sprintf("verVentana('../cxp/reportes/cp_nota_cargo_pdf.php?valBusq=%s',960,550)", $idNotaCargo);
	
	return array(true, $idNotaCargo, $idModulo, $script);
}
?>