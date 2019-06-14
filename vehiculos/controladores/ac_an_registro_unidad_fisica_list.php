<?php


function anularUnidad($frmAnularUnidad, $frmPedido, $frmListaPedidoCompra) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_registrar_vehiculo_form","eliminar")) { return $objResponse; }
	
	$idPedidoCompraDetalle = $frmAnularUnidad['hddIdPedidoCompraDetalleAnular'];
	$idMotivo = $frmAnularUnidad['txtIdMotivoAnular'];
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$query = sprintf("SELECT
		ped_comp.idPedidoCompra,
		ped_comp.id_empresa,
		ped_comp.id_moneda,
		ped_comp.id_moneda_tasa_cambio,
		ped_comp.monto_tasa_cambio,
		ped_comp_det.idSolicitud,
		ped_comp_det.idPedidoCompra,
		ped_comp_det.idUnidadBasica,
		uni_fis.id_unidad_fisica,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		cxp_nd.id_notacargo AS id_nota_cargo,
		cxp_nd.numero_notacargo AS numero_nota_cargo,
		forma_pago_asig.idProveedor AS id_proveedor_plan_mayor
	FROM an_pedido_compra ped_comp
		INNER JOIN an_solicitud_factura ped_comp_det ON (ped_comp.idPedidoCompra = ped_comp_det.idPedidoCompra)
		INNER JOIN formapagoasignacion forma_pago_asig ON (ped_comp_det.idFormaPagoAsignacion = forma_pago_asig.idFormaPagoAsignacion)
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
		LEFT JOIN cp_notadecargo cxp_nd ON (ped_comp_det.idSolicitud = cxp_nd.id_detalles_pedido_compra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
	WHERE ped_comp_det.idSolicitud = %s;",
		valTpDato($idPedidoCompraDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$idPedidoCompra = $row['idPedidoCompra'];
	$idUnidadFisica = $row['id_unidad_fisica'];
	$idNotaCargo = $row['id_nota_cargo'];
	
	mysql_query("START TRANSACTION;");
	
	if ($row['id_proveedor_plan_mayor'] > 0) {
		// BUSCA LOS DATOS DE LA NOTA DE CARGO
		$queryNotaCargo = sprintf("SELECT * FROM cp_notadecargo cxp_nd WHERE cxp_nd.id_notacargo = %s;",
			valTpDato($idNotaCargo, "int"));
		$rsNotaCargo = mysql_query($queryNotaCargo);
		if (!$rsNotaCargo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);
		
		$numeroNotaCargo = $rowNotaCargo['numero_notacargo'];
		$idEmpresa = $rowNotaCargo['id_empresa'];
		$idModulo = $rowNotaCargo['id_modulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
		$precioUnitario = $rowNotaCargo['subtotal_notacargo'];
		
		if ($rowNotaCargo['saldo_notacargo'] > 0) {
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
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA LOS DATOS DE LA NOTA DE CREDITO
			$insertSQL = sprintf("INSERT INTO cp_notacredito (id_empresa, numero_nota_credito, numero_control_notacredito, fecha_notacredito, fecha_registro_notacredito, id_proveedor, id_departamento_notacredito, id_documento, tipo_documento, estado_notacredito, observacion_notacredito, monto_exento_notacredito, monto_exonerado_notacredito, subtotal_notacredito, subtotal_descuento, total_cuenta_pagar, saldo_notacredito, aplica_libros_notacredito, id_empleado_creador)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($rowNotaCargo['id_empresa'], "int"),
				valTpDato($numeroActual, "text"),
				valTpDato($numeroActual, "text"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato($rowNotaCargo['id_proveedor'], "int"),
				valTpDato($idModulo, "int"),
				valTpDato($idNotaCargo, "int"),
				valTpDato("ND", "text"),
				valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
				valTpDato("ANULACION DE LA NOTA DE DEBITO NRO. ".$numeroNotaCargo." DE VEHICULO POR PLAN MAYOR", "text"),
				valTpDato($rowNotaCargo['monto_exento_notacargo'], "real_inglesa"),
				valTpDato($rowNotaCargo['monto_exonerado_notacargo'], "real_inglesa"),
				valTpDato($rowNotaCargo['subtotal_notacargo'], "real_inglesa"),
				valTpDato($rowNotaCargo['subtotal_descuento_notacargo'], "real_inglesa"),
				valTpDato($rowNotaCargo['total_cuenta_pagar'], "real_inglesa"),
				valTpDato($rowNotaCargo['saldo_notacargo'], "real_inglesa"),
				valTpDato($rowNotaCargo['aplica_libros_notacargo'], "int"), // 1 = Si, 0 = No
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idNotaCredito = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$arrayIdDctoContabilidad[] = array(
				$idNotaCredito,
				$idModulo,
				"NOTA CREDITO CXP");
			
			// INSERTA EL DETALLE DEL DOCUMENTO
			$insertSQL = sprintf("INSERT INTO cp_notacredito_detalle_motivo (id_notacredito, id_motivo, precio_unitario)
			VALUE (%s, %s, %s);",
				valTpDato($idNotaCredito, "int"),
				valTpDato($idMotivo, "int"),
				valTpDato($precioUnitario, "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// REGISTRA EL ESTADO DE CUENTA
			$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
			VALUE (%s, %s, %s, %s);",
				valTpDato("NC", "text"),
				valTpDato($idNotaCredito, "int"),
				valTpDato(date("Y-m-d"), "date"),
				valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			if ($rowNotaCargo['saldo_notacargo'] > 0) {
				// INSERTA EL PAGO DE LA NOTA DE CARGO DEBIDO A LA ANULACION DE LA UNIDAD
				$insertSQL = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
				VALUE(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idNotaCargo, "int"),
					valTpDato('ND', "text"),
					valTpDato('NC', "text"),
					valTpDato($idNotaCredito, "text"),
					valTpDato("NOW()", "campo"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($numeroActual, "int"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato($rowNotaCargo['saldo_notacargo'], "real_inglesa"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						
				// ACTUALIZA EL SALDO DE LA NOTA DE CARGO
				$updateSQL = sprintf("UPDATE cp_notadecargo SET
					saldo_notacargo = saldo_notacargo - %s
				WHERE id_notacargo = %s;",
					valTpDato($rowNotaCargo['saldo_notacargo'], "real_inglesa"),
					valTpDato($idNotaCargo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA EL ESTADO DE LA NOTA DE CARGO (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
				$updateSQL = sprintf("UPDATE cp_notadecargo SET
					estatus_notacargo = (CASE
												WHEN saldo_notacargo = 0 THEN	1
												WHEN saldo_notacargo > 0 THEN	2
											END)
				WHERE id_notacargo = %s;",
					valTpDato($idNotaCargo, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// ACTUALIZA LOS DATOS DE LA NOTA DE CREDITO
				$updateSQL = sprintf("UPDATE cp_notacredito SET
					saldo_notacredito = saldo_notacredito - %s,
					estado_notacredito = %s
				WHERE id_notacredito = %s;",
					valTpDato($rowNotaCargo['saldo_notacargo'], "real_inglesa"),
					valTpDato(3, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
					valTpDato($idNotaCredito, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// ACTUALIZA EL ESTATUS DE LA UNIDAD FISICA
	$updateSQL = sprintf("UPDATE an_unidad_fisica SET
		estatus = NULL,
		estado_venta = %s
	WHERE id_unidad_fisica = %s
		AND estado_venta IN ('TRANSITO','POR REGISTRAR','SINIESTRADO');",
		valTpDato("ERROR EN TRASPASO", "text"),
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA LOS DATOS DEL DETALLE DEL PEDIDO
	$updateSQL = sprintf("UPDATE an_solicitud_factura SET
		estado = %s
	WHERE idSolicitud = %s;",
		valTpDato(6, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Transito, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
		valTpDato($idPedidoCompraDetalle, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
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
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA CREDITO CXP") {
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
	
	$objResponse->alert("Unidad Anulada con éxito.");
	
	$objResponse->script("byId('btnCancelarAnularUnidad').click();");
	
	$objResponse->loadCommands(listaPedidoDetalle(
		$frmPedido['pageNum'],
		$frmPedido['campOrd'],
		$frmPedido['tpOrd'],
		$frmPedido['valBusq']));
	
	$objResponse->loadCommands(listaPedidoCompra(
		$frmListaPedidoCompra['pageNum'],
		$frmListaPedidoCompra['campOrd'],
		$frmListaPedidoCompra['tpOrd'],
		$frmListaPedidoCompra['valBusq']));
	
	return $objResponse;
}

function asignarEstadoUnidad($frmInspeccion) {
	$objResponse = new xajaxResponse();
	
	if ($frmInspeccion['lstEstadoUnidad'] == 3) { // 2 = POR REGISTRAR, 3 = SINIESTRADO
		$objResponse->script("byId('fieldsetDescripcionSiniestro').style.display = '';");
	} else {
		$objResponse->script("byId('fieldsetDescripcionSiniestro').style.display = 'none';");
	}
	
	return $objResponse;
}

function asignarFormaPago($frmPedido) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmPedido['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmPedido['cbx1'];
	
	$existePlanMayor = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$idFormaPago = $frmPedido['lstFormaPago'.$valor.':'.$valor1[1]];
						
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

function buscarPedidoCompra($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedidoCompra(0, "idPedidoCompra", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarPedidoDetalle($frmPedido) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmPedido['hddIdPedido'],
		$frmPedido['txtCriterioPedido']);
	
	$objResponse->loadCommands(listaPedidoDetalle(0, "idSolicitud", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstAlmacen($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM an_almacen
	WHERE an_almacen.id_empresa = %s
	ORDER BY nom_almacen;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAlmacen\" name=\"lstAlmacen\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".utf8_encode($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAlmacen","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstAno($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_ano']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_ano']."\">".htmlentities($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstColor($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM an_color ORDER BY nom_color;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_color']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_color']."\">".utf8_encode($row['nom_color'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCondicion($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstCondicion\" name=\"lstCondicion\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_condicion_unidad'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_condicion_unidad']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicion","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" onchange=\"xajax_asignarMoneda(xajax.getFormValues('frmPedido'));\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idmoneda']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".htmlentities($row['descripcion']." (".$row['abreviacion'].")")."</option>";
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

function formAnularUnidad($idPedidoCompraDetalle) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_registrar_vehiculo_form","eliminar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAnularUnidad').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$query = sprintf("SELECT
		ped_comp_det.idSolicitud, 
		ped_comp.id_empresa, 
		ped_comp.id_proveedor, 
		ped_comp.id_moneda, 
		ped_comp.id_moneda_tasa_cambio, 
		ped_comp.monto_tasa_cambio, 
		ped_comp_det.idSolicitud, 
		ped_comp_det.idUnidadBasica, 
		uni_fis.id_unidad_fisica, 
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo, 
		vw_iv_modelo.nom_ano, 
		ped_comp_det.idFormaPagoAsignacion, 
		forma_pago_asig.idProveedor AS id_proveedor_plan_mayor, 
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_solicitud_factura ped_comp_det
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN formapagoasignacion forma_pago_asig ON (ped_comp_det.idFormaPagoAsignacion = forma_pago_asig.idFormaPagoAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE ped_comp_det.idSolicitud = %s;",
		valTpDato($idPedidoCompraDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdPedidoCompraDetalleAnular","value",$idPedidoCompraDetalle);
	$objResponse->assign("txtIdMotivoAnular","value","");
	
	if (!($row['id_proveedor_plan_mayor'] > 0)) {
		$objResponse->script("
		if (confirm('¿Seguro desea anular este registro?') == true) {
			xajax_anularUnidad(xajax.getFormValues('frmAnularUnidad'), xajax.getFormValues('frmPedido'), xajax.getFormValues('frmListaPedidoCompra'));
		}");
	}
	
	return $objResponse;
}

function formPedido($idPedidoCompra, $accionPedido, $frmPedido) {
	$objResponse = new xajaxResponse();
	
	if ($idPedidoCompra > 0) {
		$query = sprintf("SELECT *
		FROM an_asignacion asig
			INNER JOIN an_pedido_compra ped_comp ON (asig.idAsignacion = ped_comp.idAsignacion)
		WHERE idPedidoCompra = %s;",
			valTpDato($idPedidoCompra, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
	
		$idEmpresa = $row['id_empresa'];
		
		$idMonedaLocal = $row['id_moneda'];
		$idMonedaOrigen = ($row['id_moneda_tasa_cambio'] > 0) ? $row['id_moneda_tasa_cambio'] : $row['id_moneda'];
		
		$txtTasaCambio = ($row['monto_tasa_cambio'] >= 0) ? $row['monto_tasa_cambio'] : 0;
		/*$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
		$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
		$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);*/
		
		// VERIFICA SI LA FACTURA ES DE IMPORTACION
		$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
		
		$objResponse->assign("hddIdPedido","value",utf8_encode($idPedidoCompra));
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", "", false));
		$objResponse->loadCommands(asignarProveedor($row['id_proveedor'], "true", "false"));
		$objResponse->assign("txtFecha","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_asignacion']))));
		$objResponse->assign("txtReferencia","value",utf8_encode($row['referencia_asignacion']));
		$objResponse->assign("txtAsignacion","value",utf8_encode($row['asunto_asignacion']));
		$objResponse->assign("txtFechaCierreCompra","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_cierre_compra']))));
		$objResponse->assign("txtFechaCierreVenta","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_cierre_venta']))));
		$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
		$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
		$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);
		$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $row['id_tasa_cambio']));
		$objResponse->script("byId('lstMoneda').onchange = function() { selectedOption(this.id, '".$idMonedaOrigen."'); }");
		$objResponse->script("byId('lstTasaCambio').onchange = function() { selectedOption(this.id, '".$row['id_tasa_cambio']."'); }");
		$objResponse->assign("spanTituloUnidadAsignacion","innerHTML",utf8_encode($row['referencia_asignacion']));
		
		switch ($accionPedido) {
			case "Registrar" :
				$objResponse->script("byId('btnGuardarPedido').style.display = 'none';");
				$objResponse->loadCommands(listaPedidoDetalle(0, "idSolicitud", "ASC", $idPedidoCompra)); break;
			case "Pagar" :
				$objResponse->script("byId('btnGuardarPedido').style.display = '';");
				if (!xvalidaAcceso($objResponse,"an_pedido_compra","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPedido').click();"); return $objResponse; }
				$objResponse->loadCommands(listaPlanPagoDetalle(0, "idSolicitud", "ASC", $idPedidoCompra)); break;
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
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$objResponse->assign("tdFlotanteTitulo1","innerHTML","Unidades del Pedido Nro. ".utf8_encode($row['idPedidoCompra']));
	}
	
	return $objResponse;
}

function formInspeccion($idPedidoCompraDetalle) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_inspeccion_preentrega_form","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarInspeccion').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$query = sprintf("SELECT
		ped_comp.id_empresa,
		ped_comp.id_moneda,
		ped_comp.id_moneda_tasa_cambio,
		ped_comp.monto_tasa_cambio,
		ped_comp_det.idSolicitud,
		ped_comp_det.idUnidadBasica,
		uni_fis.id_unidad_fisica,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		vw_iv_modelo.nom_ano
	FROM an_solicitud_factura ped_comp_det
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
	WHERE ped_comp_det.idSolicitud = %s;",
		valTpDato($idPedidoCompraDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	$idUnidadFisica = $row['id_unidad_fisica'];
	
	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
	$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig403 = mysql_query($queryConfig403);
	if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig403 = mysql_num_rows($rsConfig403);
	$rowConfig403 = mysql_fetch_assoc($rsConfig403);
	
	$idMonedaLocal = $row['id_moneda'];
	$idMonedaOrigen = ($row['id_moneda_tasa_cambio'] > 0) ? $row['id_moneda_tasa_cambio'] : $row['id_moneda'];
	
	$txtTasaCambio = ($row['monto_tasa_cambio'] >= 0) ? $row['monto_tasa_cambio'] : 0;
	/*$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
	$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
	$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);*/
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	
	$objResponse->assign("spanTituloInspeccion","innerHTML",utf8_encode($row['vehiculo']));
	
	$objResponse->assign("hddIdPedidoCompraDetalleInspeccion","value",$idPedidoCompraDetalle);
	$objResponse->loadCommands(cargaLstAno($row['ano']));
	
	if ($idModoCompra == 1 && in_array($rowConfig403['valor'],array(1))) { // 1 = Nacional
		$objResponse->script("byId('trPlacaInspeccion').style.display = ''");
	} else if ($idModoCompra == 2 || !in_array($rowConfig403['valor'],array(1))) { // 2 = Importacion
		$objResponse->script("byId('trPlacaInspeccion').style.display = 'none'");
	}
	
	$checkValue = array("0" => "", "1" => "checked=\"checked\"");
	
	$queryCheck = sprintf("SELECT * FROM an_checklist
	WHERE ISNULL(parent)
		AND tipo = 1
		AND n_checklist = 0
		AND (id_empresa = %s
			OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
					WHERE suc.id_empresa_padre = an_checklist.id_empresa))
	ORDER BY columna ASC, order_level ASC;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCheck = @mysql_query($queryCheck);
	if (!$rsCheck) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html .= "<table border=\"0\" width=\"100%\">";
	$html .= "<caption class=\"tituloArea\">INSPECCI&Oacute;N PRE-ENTREGA</caption>";
	$html .= "<tr>";
		$html .= "<td width=\"50%\" valign=\"top\">";
	$lastcolumn = 0;
	while ($rowCheck = mysql_fetch_assoc($rsCheck)) {
		//imprimiendo:
		if ($lastcolumn != $rowCheck['columna']) {
			$html .= "</td>";
			$html .= "<td width=\"50%\" valign=\"top\">";
			
			$lastcolumn = $rowCheck['columna'];
		}
		
		$html .= sprintf('<div class="%s">%s</div>',
			$rowCheck['clasecss'],
			$rowCheck['texto']);
	
		// EXTRAYENDO LAS SUBORDINADAS
		$queryData = sprintf("SELECT an_checklist.*,
			(SELECT an_compra_unidad_checklist.id_compra_unidad_checklist 
			FROM an_compra_unidad_checklist
			WHERE an_compra_unidad_checklist.id_unidad_fisica = %s
				AND an_compra_unidad_checklist.id_checklist = an_checklist.id_checklist) AS id_compra_unidad_checklist,
			
			(SELECT an_compra_unidad_checklist.valor from an_compra_unidad_checklist
			WHERE an_compra_unidad_checklist.id_unidad_fisica = %s
				AND an_compra_unidad_checklist.id_checklist = an_checklist.id_checklist) AS valor
		FROM an_checklist
		WHERE parent = %s
			AND n_checklist = 0;",
			valTpDato($idUnidadFisica, "int"),
			valTpDato($idUnidadFisica, "int"),
			valTpDato($rowCheck['id_checklist'], "int"));
		$rsData = mysql_query($queryData);
		if (!$rsData) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowData = mysql_fetch_assoc($rsData)) {
			$idCheck = $rowData['id_checklist'];
			
			if ($rowData['tipo'] == 0) {
				$html .= sprintf("
					<label for=\"check%s\">
						<div class=\"%s\">
							<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
							<tr align=\"left\">
								<td><input type=\"checkbox\" name=\"check[%s]\" id=\"check%s\" %s / ></td>
								<td><div>%s</div></td>
								<td><input type=\"hidden\" name=\"checkid[%s]\" value=\"%s\" /></td>
							</tr>
							</table>
						</div>
					</label>",
					$idCheck,
					$rowData['clasecss'],
					$idCheck, $idCheck, $checkValue[$rowData['valor']],
					$rowData['texto'],
					$idCheck, $rowData['id_compra_unidad_checklist']);
			}
		}
	}
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign("divInspeccion","innerHTML",$html);
	
	return $objResponse;
}

function formRegistrarUnidad($idPedidoCompraDetalle) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_registrar_vehiculo_form","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarRegistrarUnidad').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$query = sprintf("SELECT
		ped_comp.id_empresa,
		ped_comp.id_moneda,
		ped_comp.id_moneda_tasa_cambio,
		ped_comp.monto_tasa_cambio,
		ped_comp_det.idSolicitud,
		ped_comp_det.idUnidadBasica,
		uni_fis.id_unidad_fisica,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
	FROM an_solicitud_factura ped_comp_det
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
	WHERE ped_comp_det.idSolicitud = %s;",
		valTpDato($idPedidoCompraDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	
	$objResponse->assign("spanTituloRegistrarUnidad","innerHTML",utf8_encode($row['vehiculo']));
	
	$objResponse->assign("hddIdPedidoCompraDetalle","value",$idPedidoCompraDetalle);
	$objResponse->loadCommands(cargaLstCondicion());
	$objResponse->loadCommands(cargaLstAlmacen($idEmpresa));
	$objResponse->loadCommands(cargaLstColor("lstColorExterno1"));
	$objResponse->loadCommands(cargaLstColor("lstColorExterno2"));
	$objResponse->loadCommands(cargaLstColor("lstColorInterno1"));
	$objResponse->loadCommands(cargaLstColor("lstColorInterno2"));
	
	return $objResponse;
}

function guardarInspeccion($frmInspeccion, $frmPedido) {
	$objResponse = new xajaxResponse();
	
	global $spanPlaca;
	
	if (!xvalidaAcceso($objResponse,"an_inspeccion_preentrega_form","insertar")) { $objResponse->script("byId('btnCancelarInspeccion').click();"); return $objResponse; }
	
	$idPedidoCompraDetalle = $frmInspeccion['hddIdPedidoCompraDetalleInspeccion'];
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$query = sprintf("SELECT
		ped_comp.id_empresa,
		ped_comp.id_moneda,
		ped_comp.id_moneda_tasa_cambio,
		ped_comp.monto_tasa_cambio,
		ped_comp_det.idSolicitud,
		ped_comp_det.idUnidadBasica,
		uni_fis.id_unidad_fisica,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		vw_iv_modelo.nom_ano
	FROM an_solicitud_factura ped_comp_det
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
	WHERE ped_comp_det.idSolicitud = %s;",
		valTpDato($idPedidoCompraDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	$idUnidadFisica = $row['id_unidad_fisica'];
	
	$idMonedaLocal = $row['id_moneda'];
	$idMonedaOrigen = ($row['id_moneda_tasa_cambio'] > 0) ? $row['id_moneda_tasa_cambio'] : $row['id_moneda'];
	
	$txtTasaCambio = ($row['monto_tasa_cambio'] >= 0) ? $row['monto_tasa_cambio'] : 0;
	/*$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
	$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
	$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);*/
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	
	// VERIFICA QUE NO EXISTA LA PLACA
	$query = sprintf("SELECT * FROM an_unidad_fisica
	WHERE placa LIKE %s
		AND estatus = 1;",
		valTpDato($frmInspeccion['txtPlaca'], "text"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0 && $idModoCompra == 1) {
		return $objResponse->alert("Ya existe una unidad con los datos de ".$spanPlaca." ingresados");
	}
			
	switch ($frmInspeccion['lstEstadoUnidad']) { // 1 = TRANSITO, 2 = POR REGISTRAR, 3 = SINIESTRADO, 4 = DISPONIBLE, 5 = RESERVADO, 6 = VENDIDO, 7 = ENTREGADO, 8 = PRESTADO, 9 = ACTIVO FIJO, 10 = INTERCAMBIO, 11 = DEVUELTO, 12 = ERROR EN TRASPASO
		case 2 : $lstEstadoUnidad = "POR REGISTRAR"; break;
		case 3 : $lstEstadoUnidad = "SINIESTRADO"; break;
	}
	
	// ACTUALIZA LOS DATOS DE LA UNIDAD FISICA
	$updateSQL = sprintf("UPDATE an_unidad_fisica SET 
		placa = %s,
		ano = %s,
		estado_venta = %s,
		descripcion_siniestro = %s
	WHERE id_unidad_fisica = %s
		AND estado_venta IN ('TRANSITO');",
		valTpDato($frmInspeccion['txtPlaca'], "text"),
		valTpDato($frmInspeccion['lstAno'], "int"),
		valTpDato($lstEstadoUnidad, "text"),
		valTpDato($frmInspeccion['txtDescripcionSiniestro'], "text"),
		valTpDato($idUnidadFisica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL DETALLE DEL PEDIDO
	$updateSQL = sprintf("UPDATE an_solicitud_factura SET
		fecha_registro_pdi = NOW(),
		id_empleado_pdi = %s,
		estado = %s
	WHERE idSolicitud = %s;",
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato(4, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Aprobado, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
		valTpDato($idPedidoCompraDetalle, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// GUARDA LOS DATOS EN LA AUDITORIA DE ALMACEN
	/*$insertSQL = sprintf("INSERT INTO an_auditoria_almacen (id_unidad_fisica, fecha, hora, id_almacen_origen, id_almacen_destino, estado_venta_origen, estado_venta_destino, id_empleado_elaborado, id_empleado_autorizado)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idUnidadFisica, "int"),
		valTpDato("NOW()", "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmRegistrarUnidad['lstAlmacen'], "int"),//<----
		valTpDato($frmRegistrarUnidad['lstAlmacen'], "int"),//<----
		valTpDato("TRANSITO", "text"),
		valTpDato($lstEstadoUnidad, "text"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");*/
	
	if (isset($frmInspeccion['checkid'])) {
		$checkId = $frmInspeccion['checkid'];
		$checkValue = $frmInspeccion['check'];
		
		foreach ($checkId as $key => $value) {
			$valor = ($checkValue[$key] != "") ? 1 : 0;
			
			if ($valor == 1) {
				if ($value > 0) {
					$SQL = sprintf("UPDATE an_compra_unidad_checklist SET
						valor = %s
					WHERE id_compra_unidad_checklist = %s;",
						valTpDato($valor, "boolean"),
						valTpDato($value, "int"));
				} else {
					$SQL = sprintf("INSERT INTO an_compra_unidad_checklist (id_unidad_fisica, id_checklist, valor)
					VALUES (%s, %s, %s);",
						valTpDato($idUnidadFisica, "int"),
						valTpDato($key, "int"),
						valTpDato($valor, "boolean"));
				}
			} else {
				$SQL = sprintf("DELETE FROM an_compra_unidad_checklist WHERE id_compra_unidad_checklist = %s;",
					valTpDato($value, "int"));
			}
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($SQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Inspección guardada con éxito.");
	
	$objResponse->script("byId('btnCancelarInspeccion').click();");
	
	$objResponse->loadCommands(listaPedidoDetalle(
		$frmPedido['pageNum'],
		$frmPedido['campOrd'],
		$frmPedido['tpOrd'],
		$frmPedido['valBusq']));
	
	return $objResponse;
}

function guardarPlanPago($frmPedido, $frmListaPedidoCompra) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_compra","insertar")) { $objResponse->script("byId('btnCancelarPedido').click();"); return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmPedido['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmPedido['cbx1'];
	
	$idEmpresa = $frmPedido['txtIdEmpresa'];
	$idPedidoCompra = $frmPedido['hddIdPedido'];
	$idMotivo = $frmPedido['txtIdMotivo'];
	
	$idMonedaLocal = $frmPedido['hddIdMoneda'];
	$idMonedaOrigen = ($frmPedido['hddIdMoneda'] == $frmPedido['lstMoneda']) ? $frmPedido['hddIdMoneda'] : $frmPedido['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = str_replace(",","",$frmPedido['txtTasaCambio']);
	
	$fechaRegistroPlanPago = date("Y-m-d H:i:s");
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script("
			byId('txtIdMotivo').className = 'inputHabilitado';
			byId('txtCosto".$valor."').className = 'inputHabilitado';");
			
			$costoUnit = $frmPedido['txtCosto'.$valor];
			
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice1 => $valor1) {
					$objResponse->script("byId('lstFormaPago".$valor1."').className = 'inputHabilitado'");
					
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$idFormaPago = $frmPedido['lstFormaPago'.$valor.':'.$valor1[1]];
						
						if ($idFormaPago > 4 && !($frmPedido['txtIdMotivo'] > 0)) {
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
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$costoUnit = str_replace(",","",$frmPedido['txtCosto'.$valor]);
						
						$idPedidoCompraDetalle = $frmPedido['hddIdPedidoDetalle'.$valor.':'.$valor1[1]];
						$idFormaPago = $frmPedido['lstFormaPago'.$valor.':'.$valor1[1]];
						$costoUnit = ($idFormaPago == 4) ? 0 : $costoUnit;
						$fechaPlanPago = ($idFormaPago == 4) ? "" : $fechaRegistroPlanPago;
						
						$updateSQL = sprintf("UPDATE an_solicitud_factura SET
							costo_unidad = %s,
							idFormaPagoAsignacion = %s,
							estado = %s,
							fecha_registro_plan_pago = %s
						WHERE idSolicitud = %s;",
							valTpDato($costoUnit, "real_inglesa"),
							valTpDato($idFormaPago, "int"),
							valTpDato(1, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Transito, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
							valTpDato($fechaPlanPago, "date"),
							valTpDato($idPedidoCompraDetalle, "int"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						if ($idFormaPago > 4) {
							$precioUnitario = ($idModoCompra == 1) ? $costoUnit : $costoUnit * $txtTasaCambio;
							$costoUnit = ($idModoCompra == 1) ? $costoUnit : $costoUnit * $txtTasaCambio;
							
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
							if (!$rsProv) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$rowProv = mysql_fetch_assoc($rsProv);
							
							$idProveedor = $rowProv['id_proveedor'];
							$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
							$txtDiasCreditoProv = ($rowProv['diascredito'] > 0) ? $rowProv['diascredito'] : 0;
							$txtFechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoProv);
							$tipoPago = ($rowProv['diascredito'] == 'Si' || $rowProv['diascredito'] == 1) ? 0 : 1; // 0 = Credito, 1 = Contado
							
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
							
							// GUARDA LOS DATOS DE LA NOTA DE CARGO
							$insertSQL = sprintf("INSERT INTO cp_notadecargo (id_empresa, numero_notacargo, numero_control_notacargo, fecha_notacargo, fecha_vencimiento_notacargo, fecha_origen_notacargo, id_proveedor, id_modulo, estatus_notacargo, observacion_notacargo, tipo_pago_notacargo, monto_exento_notacargo, monto_exonerado_notacargo, subtotal_notacargo, subtotal_descuento_notacargo, total_cuenta_pagar, saldo_notacargo, aplica_libros_notacargo, chasis, id_detalles_pedido_compra, id_empleado_creador)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
								valTpDato($idEmpresa, "int"),
								valTpDato($numeroActual, "text"),
								valTpDato($numeroActual, "text"),
								valTpDato(date("Y-m-d"), "date"),
								valTpDato(date("Y-m-d", strtotime($txtFechaVencimiento)), "date"),
								valTpDato(date("Y-m-d"), "date"),
								valTpDato($idProveedor, "int"),
								valTpDato($idModulo, "int"),
								valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
								valTpDato("VEHICULO POR PLAN MAYOR", "text"),
								valTpDato($tipoPago, "int"), // 0 = Credito, 1 = Contado
								valTpDato($costoUnit, "real_inglesa"),
								valTpDato(0, "real_inglesa"),
								valTpDato($costoUnit, "real_inglesa"),
								valTpDato(0, "real_inglesa"),
								valTpDato($costoUnit, "real_inglesa"),
								valTpDato($costoUnit, "real_inglesa"),
								valTpDato(0, "int"), // 1 = Si, 0 = No
								valTpDato("", "text"),
								valTpDato($idPedidoCompraDetalle, "int"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
							mysql_query("SET NAMES 'utf8'");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							$idNotaCargo = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");
							
							$arrayIdDctoContabilidad[] = array(
								$idNotaCargo,
								$idModulo,
								"NOTA CARGO CXP");
							
							// INSERTA EL DETALLE DEL DOCUMENTO
							$insertSQL = sprintf("INSERT INTO cp_notacargo_detalle_motivo (id_notacargo, id_motivo, precio_unitario)
							VALUE (%s, %s, %s);",
								valTpDato($idNotaCargo, "int"),
								valTpDato($idMotivo, "int"),
								valTpDato($precioUnitario, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
							// REGISTRA EL ESTADO DE CUENTA
							$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
							VALUE (%s, %s, %s, %s);",
								valTpDato("ND", "text"),
								valTpDato($idNotaCargo, "int"),
								valTpDato(date("Y-m-d"), "date"),
								valTpDato("2", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
							
							// ACTUALIZA EL CREDITO DISPONIBLE
							$updateSQL = sprintf("UPDATE cp_prove_credito prov_cred SET
								saldoDisponible = limitecredito - (IFNULL((SELECT SUM(fact_comp.saldo_factura) FROM cp_factura fact_comp
																	WHERE fact_comp.id_proveedor = prov_cred.id_proveedor
																		AND fact_comp.estatus_factura IN (0,2)), 0)
																	+
																	IFNULL((SELECT SUM(nota_cargo.saldo_notacargo) FROM cp_notadecargo nota_cargo
																	WHERE nota_cargo.id_proveedor = prov_cred.id_proveedor
																		AND nota_cargo.estatus_notacargo IN (0,2)), 0)
																	-
																	IFNULL((SELECT SUM(anticip.saldoanticipo) FROM cp_anticipo anticip
																	WHERE anticip.id_proveedor = prov_cred.id_proveedor
																		AND anticip.estado IN (1,2)), 0)
																	-
																	IFNULL((SELECT nota_cred.saldo_notacredito FROM cp_notacredito nota_cred
																	WHERE nota_cred.id_proveedor = prov_cred.id_proveedor
																		AND nota_cred.estado_notacredito IN (1,2)), 0))
							WHERE prov_cred.id_proveedor = %s;",
								valTpDato($idProveedor, "int"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($updateSQL);
							if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
							mysql_query("SET NAMES 'latin1';");
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
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	
	// GUARDA LA CABECERA DE LA CARTA DE SOLICITUD
	$insertSQL = sprintf("INSERT INTO an_encabezadocartasolicitud (idPedidoCompra, fechaCartaSolicitud)
	VALUES (%s, %s);",
		valTpDato($idPedidoCompra, "int"),
		valTpDato(date("Y-m-d"), "date"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	if (!$rsPedidoDetalle) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsPedidoDetalle = mysql_num_rows($rsPedidoDetalle);
	while ($rowPedidoDetalle = mysql_fetch_assoc($rsPedidoDetalle)) {
		$insertSQL = sprintf("INSERT INTO an_detallecartasolicitud (idCartaSolicitud, idSolicitud)
		VALUES (%s, %s);",
			valTpDato($idCarta, "int"),
			valTpDato($rowPedidoDetalle['idSolicitud'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL ESTADO DE LA UNIDAD EN EL DETALLE DEL PEDIDO
		$updateSQL = sprintf("UPDATE an_solicitud_factura SET
			estado = %s
		WHERE idSolicitud = %s;",
			valTpDato(2, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Aprobado, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
			valTpDato($rowPedidoDetalle['idSolicitud'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "NOTA CARGO CXP") {
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
	
	$objResponse->script("byId('btnCancelarPedido').click();");
	
	$objResponse->loadCommands(listaPedidoCompra(
		$frmListaPedidoCompra['pageNum'],
		$frmListaPedidoCompra['campOrd'],
		$frmListaPedidoCompra['tpOrd'],
		$frmListaPedidoCompra['valBusq']));
	
	return $objResponse;
}

function guardarUnidadFisica($frmRegistrarUnidad, $frmPedido) {
	$objResponse = new xajaxResponse();
	
	global $arrayValidarCarroceria;
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	
	if (!xvalidaAcceso($objResponse,"an_registrar_vehiculo_form","insertar")) { return $objResponse; }
	
	$idPedidoCompraDetalle = $frmRegistrarUnidad['hddIdPedidoCompraDetalle'];
	
	$fechaRegistroUnidadFisica = date("Y-m-d H:i:s");
		
	mysql_query("START TRANSACTION;");
	
	$arrayValidar = $arrayValidarCarroceria;
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmRegistrarUnidad['txtSerialCarroceria'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtSerialCarroceria').className = 'inputErrado'");
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$query = sprintf("SELECT
		ped_comp.idPedidoCompra,
		ped_comp.id_empresa,
		ped_comp.id_moneda,
		ped_comp.id_moneda_tasa_cambio,
		ped_comp.monto_tasa_cambio,
		ped_comp_det.idSolicitud,
		ped_comp_det.idPedidoCompra,
		ped_comp_det.idUnidadBasica,
		uni_fis.id_unidad_fisica,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		cxp_nd.id_notacargo AS id_nota_cargo,
		cxp_nd.numero_notacargo AS numero_nota_cargo,
		forma_pago_asig.idProveedor AS id_proveedor_plan_mayor
	FROM an_pedido_compra ped_comp
		INNER JOIN an_solicitud_factura ped_comp_det ON (ped_comp.idPedidoCompra = ped_comp_det.idPedidoCompra)
		INNER JOIN formapagoasignacion forma_pago_asig ON (ped_comp_det.idFormaPagoAsignacion = forma_pago_asig.idFormaPagoAsignacion)
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
		LEFT JOIN cp_notadecargo cxp_nd ON (ped_comp_det.idSolicitud = cxp_nd.id_detalles_pedido_compra)
		INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
	WHERE ped_comp_det.idSolicitud = %s;",
		valTpDato($idPedidoCompraDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$idUnidadBasica = $row['idUnidadBasica'];
	$idNotaCargo = $row['id_nota_cargo'];
	
	// VERIFICA QUE NO EXISTA EL SERIAL DEL CHASIS, CARROCERIA Y MOTOR
	$query = sprintf("SELECT * FROM an_unidad_fisica
	WHERE (serial_carroceria LIKE %s)
		AND estatus = 1;", // OR serial_motor LIKE %s OR serial_chasis LIKE %s
		valTpDato($frmRegistrarUnidad['txtSerialCarroceria'], "text")/*,
		valTpDato($frmRegistrarUnidad['txtSerialMotor'], "text"),
		valTpDato($frmRegistrarUnidad['txtNumeroVehiculo'], "text")*/);
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		return $objResponse->alert("Ya existe una unidad con alguno de los datos de ".$spanSerialCarroceria.", ".$spanSerialMotor." o Nro. Vehículo ingresados");
	}
	
	// GUARDA LOS DATOS DE LA UNIDAD FISICA
	$insertSQL = sprintf("INSERT INTO an_unidad_fisica (id_pedido_compra_detalle, id_uni_bas, ano, id_uso, id_clase, capacidad, id_condicion_unidad, id_color_externo1, id_color_externo2, id_color_interno1, id_color_interno2, id_origen, id_almacen, serial_carroceria, serial_motor, serial_chasis, estado_compra, estado_venta, estatus) 
	SELECT %s, id_uni_bas, ano_uni_bas, tip_uni_bas, cla_uni_bas, cap_uni_bas, %s, %s, %s, %s, %s, ori_uni_bas, %s, %s, %s, %s, %s, %s, %s FROM an_uni_bas uni_bas
	WHERE uni_bas.id_uni_bas = %s;",
		valTpDato($idPedidoCompraDetalle, "int"),
		valTpDato($frmRegistrarUnidad['lstCondicion'], "int"),
		valTpDato($frmRegistrarUnidad['lstColorExterno1'], "int"),
		valTpDato($frmRegistrarUnidad['lstColorExterno2'], "int"),
		valTpDato($frmRegistrarUnidad['lstColorInterno1'], "int"),
		valTpDato($frmRegistrarUnidad['lstColorInterno2'], "int"),
		valTpDato($frmRegistrarUnidad['lstAlmacen'], "int"),
		valTpDato($frmRegistrarUnidad['txtSerialCarroceria'], "text"),
		valTpDato($frmRegistrarUnidad['txtSerialMotor'], "text"),
		valTpDato($frmRegistrarUnidad['txtNumeroVehiculo'], "text"),
		valTpDato("COMPRADO", "text"), // 1 = ALTA, 2 = IMPRESO, 3 = COMPRADO, 4 = REGISTRADO, 5 = CANCELADO
		valTpDato("TRANSITO", "text"), // 1 = TRANSITO, 2 = POR REGISTRAR, 3 = SINIESTRADO, 4 = DISPONIBLE, 5 = RESERVADO, 6 = VENDIDO, 7 = ENTREGADO, 8 = PRESTADO, 9 = ACTIVO FIJO, 10 = INTERCAMBIO, 11 = DEVUELTO, 12 = ERROR EN TRASPASO
		valTpDato(1, "int"), // Null = Anulada, 1 = Activa
		valTpDato($idUnidadBasica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idUnidadFisica = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL DETALLE DEL PEDIDO
	$updateSQL = sprintf("UPDATE an_solicitud_factura SET
		fecha_registro_uni_fis = %s,
		id_emple_reg_uni_fis = %s,
		estado = %s
	WHERE idSolicitud = %s;",
		valTpDato($fechaRegistroUnidadFisica, "date"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato(3, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Aprobado, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
		valTpDato($idPedidoCompraDetalle, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// GUARDA LOS DATOS EN LA AUDITORIA DE ALMACEN
	$insertSQL = sprintf("INSERT INTO an_auditoria_almacen (id_unidad_fisica, fecha, hora, id_almacen_origen, id_almacen_destino, estado_venta_origen, estado_venta_destino, id_empleado_elaborado, id_empleado_autorizado)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idUnidadFisica, "int"),
		valTpDato("NOW()", "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmRegistrarUnidad['lstAlmacen'], "int"),
		valTpDato($frmRegistrarUnidad['lstAlmacen'], "int"),
		valTpDato("TRANSITO", "text"),
		valTpDato("TRANSITO", "text"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA LA OBSERVACION DE LA NOTA DE DEBITO AGREGANDOLE EL SERIAL DE CHASIS DE LA UNIDAD FISICA
	$updateSQL = sprintf("UPDATE cp_notadecargo SET
		observacion_notacargo = CONCAT_WS(' ', %s, observacion_notacargo)
	WHERE id_notacargo = %s;",
		valTpDato($frmRegistrarUnidad['txtSerialCarroceria'], "text"),
		valTpDato($idNotaCargo, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Unidad Física guardada con éxito.");
	
	$objResponse->script("byId('btnCancelarRegistrarUnidad').click();");
	
	$objResponse->loadCommands(listaPedidoDetalle(
		$frmPedido['pageNum'],
		$frmPedido['campOrd'],
		$frmPedido['tpOrd'],
		$frmPedido['valBusq']));
	
	return $objResponse;
}

function importarDcto($frmImportarPedido, $frmPedido) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
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
		$estatus = $archivoExcel->getActiveSheet()->getCell('P'.$i)->getValue();
		
		if ($itemExcel == true && strlen($estatus) > 0) {
			$arrayFilaImportar[] = array(
				"nro_pedido"		=> $archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(), // Nro. Pedido
				"id_detalle"		=> $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(), // Id. Detalle
				"id_unidad"			=> $archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue(), // Id. Unidad
				"codigo_unidad"		=> $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue(), // Código Unidad
				"id_cliente"		=> $archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue(), // Id. Cliente
				"ci_cliente"		=> $archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue(), // C.I. / R.I.F.
				"serial_carrocería"	=> $archivoExcel->getActiveSheet()->getCell('G'.$i)->getValue(), // Serial Carrocería
				"serial_motor"		=> $archivoExcel->getActiveSheet()->getCell('H'.$i)->getValue(), // Serial Motor
				"nro_vehiculo"		=> $archivoExcel->getActiveSheet()->getCell('I'.$i)->getValue(), // Nro. Vehículo
				"condicion"			=> $archivoExcel->getActiveSheet()->getCell('J'.$i)->getValue(), // Condición
				"almacen"			=> $archivoExcel->getActiveSheet()->getCell('K'.$i)->getValue(), // Almacén
				"color_externo_1"	=> $archivoExcel->getActiveSheet()->getCell('L'.$i)->getValue(), // Color Externo 1
				"color_interno_1"	=> $archivoExcel->getActiveSheet()->getCell('M'.$i)->getValue(), // Color Interno 1
				"ano"				=> $archivoExcel->getActiveSheet()->getCell('N'.$i)->getValue(), // Año
				"placa"				=> $archivoExcel->getActiveSheet()->getCell('O'.$i)->getValue(), // Placa
				"estatus"			=> $archivoExcel->getActiveSheet()->getCell('P'.$i)->getValue()); // Estatus
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == strtoupper("Nro. Pedido")
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Nro. Pedido")
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Nro. Pedido")
		|| trim(strtoupper(htmlentities($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Nro. Pedido")
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == strtoupper("Nro. Pedido")) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmPedido['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayFilaImportar)) {
		mysql_query("START TRANSACTION;");
		
		foreach ($arrayFilaImportar as $indiceFila => $valorFila) {
			// RUTINA PARA AGREGAR EL ARTICULO
			$idEmpresa = ($frmPedido['txtIdEmpresa'] > 0) ? $frmPedido['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];
			
			// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
			$queryPedidoDetalle = sprintf("SELECT * FROM an_solicitud_factura ped_comp_det
			WHERE ped_comp_det.idPedidoCompra = %s
				AND ped_comp_det.idPedidoCompra = %s
				AND ped_comp_det.idSolicitud = %s
				AND ped_comp_det.idUnidadBasica = %s;",
				valTpDato($frmPedido['hddIdPedido'], "int"),
				valTpDato($arrayFilaImportar[$indiceFila]['nro_pedido'], "int"),
				valTpDato($arrayFilaImportar[$indiceFila]['id_detalle'], "int"),
				valTpDato($arrayFilaImportar[$indiceFila]['id_unidad'], "int"));
			$rsPedidoDetalle = mysql_query($queryPedidoDetalle);
			if (!$rsPedidoDetalle) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsPedidoDetalle = mysql_num_rows($rsPedidoDetalle);
			$rowPedidoDetalle = mysql_fetch_assoc($rsPedidoDetalle);
			
			$existe = (in_array($rowPedidoDetalle['estado'],array(3,4))) ? true : false;
			$existeSerial = false;
			$existePlaca = false;
			
			$idPedidoCompraDetalle = $arrayFilaImportar[$indiceFila]['id_detalle'];
			$lstCondicion = explode(")",$arrayFilaImportar[$indiceFila]['condicion']);
			$lstColorExterno1 = explode(")",$arrayFilaImportar[$indiceFila]['color_externo_1']);
			$lstColorInterno1 = explode(")",$arrayFilaImportar[$indiceFila]['color_interno_1']);
			$lstAlmacen = explode(")",$arrayFilaImportar[$indiceFila]['almacen']);
			$lstEstadoUnidad = explode(")",$arrayFilaImportar[$indiceFila]['estatus']);
			
			if ($totalRowsPedidoDetalle > 0) {
				// VERIFICA QUE NO EXISTA EL SERIAL DEL CHASIS, CARROCERIA Y MOTOR
				$query = sprintf("SELECT * FROM an_unidad_fisica
				WHERE (serial_carroceria LIKE %s)
					AND estatus = 1;", // OR serial_motor LIKE %s OR serial_chasis LIKE %s
					valTpDato($arrayFilaImportar[$indiceFila]['serial_carrocería'], "text")/*,
					valTpDato($arrayFilaImportar[$indiceFila]['serial_motor'], "text"),
					valTpDato($arrayFilaImportar[$indiceFila]['nro_vehiculo'], "text")*/);
				$rs = mysql_query($query);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRows = mysql_num_rows($rs);
				$row = mysql_fetch_array($rs);
				
				if ($totalRows > 0 && $existe == false) {
					$existeSerial = true;
				}
				
				if ($existe == false && $existeSerial == false) {
					// GUARDA LOS DATOS DE LA UNIDAD FISICA
					$insertSQL = sprintf("INSERT INTO an_unidad_fisica (id_pedido_compra_detalle, id_uni_bas, ano, id_uso, id_clase, capacidad, id_condicion_unidad, id_color_externo1, id_color_externo2, id_color_interno1, id_color_interno2, id_origen, id_almacen, serial_carroceria, serial_motor, serial_chasis, estado_compra, estado_venta, estatus) 
					SELECT %s, id_uni_bas, ano_uni_bas, tip_uni_bas, cla_uni_bas, cap_uni_bas, %s, %s, %s, %s, %s, ori_uni_bas, %s, %s, %s, %s, %s, %s, %s FROM an_uni_bas uni_bas
					WHERE uni_bas.id_uni_bas = %s;",
						valTpDato($idPedidoCompraDetalle, "int"),
						valTpDato($lstCondicion[0], "int"),
						valTpDato($lstColorExterno1[0], "int"),
						valTpDato($frmRegistrarUnidad['lstColorExterno2'], "int"),
						valTpDato($lstColorInterno1[0], "int"),
						valTpDato($frmRegistrarUnidad['lstColorInterno2'], "int"),
						valTpDato($lstAlmacen[0], "int"),
						valTpDato($arrayFilaImportar[$indiceFila]['serial_carrocería'], "text"),
						valTpDato($arrayFilaImportar[$indiceFila]['serial_motor'], "text"),
						valTpDato($arrayFilaImportar[$indiceFila]['nro_vehiculo'], "text"),
						valTpDato("COMPRADO", "text"), // 1 = ALTA, 2 = IMPRESO, 3 = COMPRADO, 4 = REGISTRADO, 5 = CANCELADO
						valTpDato("TRANSITO", "text"), // 1 = TRANSITO, 2 = POR REGISTRAR, 3 = SINIESTRADO, 4 = DISPONIBLE, 5 = RESERVADO, 6 = VENDIDO, 7 = ENTREGADO, 8 = PRESTADO, 9 = ACTIVO FIJO, 10 = INTERCAMBIO, 11 = DEVUELTO, 12 = ERROR EN TRASPASO
						valTpDato(1, "int"), // Null = Anulada, 1 = Activa
						valTpDato($arrayFilaImportar[$indiceFila]['id_unidad'], "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idUnidadFisica = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
				
				
				// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
				$query = sprintf("SELECT
					ped_comp.id_empresa,
					ped_comp.id_moneda,
					ped_comp.id_moneda_tasa_cambio,
					ped_comp.monto_tasa_cambio,
					ped_comp_det.idSolicitud,
					ped_comp_det.idUnidadBasica,
					uni_fis.id_unidad_fisica,
					CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
					vw_iv_modelo.nom_ano
				FROM an_solicitud_factura ped_comp_det
					LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
					INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
					INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
					INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
				WHERE ped_comp_det.idSolicitud = %s;",
					valTpDato($idPedidoCompraDetalle, "int"));
				$rs = mysql_query($query);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$row = mysql_fetch_assoc($rs);
				
				$idEmpresa = $row['id_empresa'];
				$idUnidadFisica = $row['id_unidad_fisica'];
				
				$idMonedaLocal = $row['id_moneda'];
				$idMonedaOrigen = ($row['id_moneda_tasa_cambio'] > 0) ? $row['id_moneda_tasa_cambio'] : $row['id_moneda'];
				
				$txtTasaCambio = ($row['monto_tasa_cambio'] >= 0) ? $row['monto_tasa_cambio'] : 0;
				/*$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
				$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
				$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);*/
				
				// VERIFICA SI LA FACTURA ES DE IMPORTACION
				$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
				
				// VERIFICA QUE NO EXISTA LA PLACA
				$query = sprintf("SELECT * FROM an_unidad_fisica
				WHERE placa LIKE %s
					AND estatus = 1;",
					valTpDato($arrayFilaImportar[$indiceFila]['placa'], "text"));
				$rs = mysql_query($query);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRows = mysql_num_rows($rs);
				$row = mysql_fetch_array($rs);
				
				if ($totalRows > 0 && $existe == false && $idModoCompra == 1) {
					$existePlaca = true;
				}
				
				if ($idUnidadFisica > 0 && $existe == false && $existeSerial == false) {
					switch ($lstEstadoUnidad[0]) { // 1 = TRANSITO, 2 = POR REGISTRAR, 3 = SINIESTRADO, 4 = DISPONIBLE, 5 = RESERVADO, 6 = VENDIDO, 7 = ENTREGADO, 8 = PRESTADO, 9 = ACTIVO FIJO, 10 = INTERCAMBIO, 11 = DEVUELTO, 12 = ERROR EN TRASPASO
						case 2 : $lstEstadoUnidad = "POR REGISTRAR"; break;
						case 3 : $lstEstadoUnidad = "SINIESTRADO"; break;
					}
					
					// ACTUALIZA LOS DATOS DE LA UNIDAD FISICA
					$updateSQL = sprintf("UPDATE an_unidad_fisica SET 
						placa = %s,
						ano = (SELECT ano.id_ano FROM an_ano ano WHERE ano.nom_ano LIKE %s),
						estado_venta = %s,
						descripcion_siniestro = %s
					WHERE id_unidad_fisica = %s
						AND estado_venta IN ('TRANSITO');",
						valTpDato($arrayFilaImportar[$indiceFila]['placa'], "text"),
						valTpDato($arrayFilaImportar[$indiceFila]['ano'], "text"),
						valTpDato($lstEstadoUnidad, "text"),
						valTpDato($arrayFilaImportar['descripcion_siniestro'], "text"),
						valTpDato($idUnidadFisica, "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					// ACTUALIZA EL DETALLE DEL PEDIDO
					$updateSQL = sprintf("UPDATE an_solicitud_factura SET
						fecha_registro_pdi = NOW(),
						id_empleado_pdi = %s,
						estado = %s
					WHERE idSolicitud = %s;",
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato(4, "int"), // 1 = Pendiente, 2 = Forma Pago Asignado, 3 = Aprobado, 4 = Inspeccionado, 5 = Registrado, 6 = Anulado
						valTpDato($idPedidoCompraDetalle, "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
			
			if ($existe == false && $existeSerial == false && $existePlaca == false ) {
				if ($totalRowsPedidoDetalle > 0) {
					$arrayObjImportado[] = $idPedidoCompraDetalle;
				} else if ($lstEstadoUnidad[0] > 0) {
					$arrayObjNoExiste[] = $idPedidoCompraDetalle;
				}
			} else {
				if ($existeSerial != false) {
					$arrayObjSerialDuplicado[] = $arrayFilaImportar[$indiceFila]['serial_carrocería'];
				} else if ($existePlaca != false) {
					$arrayObjPlacaDuplicado[] = $arrayFilaImportar[$indiceFila]['placa'];
				} else {
					$arrayObjExiste[] = $idPedidoCompraDetalle;
				}
			}
		}
		
		if (count($arrayObjSerialDuplicado) > 0) {
			$objResponse->alert(("Ya existe(n) en el sistema ".count($arrayObjSerialDuplicado)." items con alguno de los datos de ".$spanSerialCarroceria.", ".$spanSerialMotor." o Nro. Vehículo:\n\n".implode(", ",$arrayObjSerialDuplicado)));
		}
		
		if (count($arrayObjPlacaDuplicado) > 0) {
			$objResponse->alert(("Ya existe(n) en el sistema ".count($arrayObjPlacaDuplicado)." items con los datos de ".$spanPlaca." ingresados:\n\n".implode(", ",$arrayObjPlacaDuplicado)));
		}
		
		if (count($arrayObjExiste) > 0) {
			$objResponse->alert(utf8_encode("Ya se encuentra(n) incluido(s) ".count($arrayObjExiste)." items (Id. Detalle):\n\n".implode(", ",$arrayObjExiste)));
		}
		
		if (count($arrayObjNoExiste) > 0) {
			$objResponse->alert(("No existe(n) en el sistema ".count($arrayObjNoExiste)." items (Id. Detalle):\n\n".implode(", ",$arrayObjNoExiste)));
		}
		
		if (count($arrayObjImportado) > 0) {
			mysql_query("COMMIT;");
			
			$objResponse->alert(("Fueron importados con éxito ".count($arrayObjImportado)." items (Id. Detalle):\n\n".implode(", ",$arrayObjImportado)));
		} else {
			$objResponse->alert(utf8_encode("No se pudo importar el archivo"));
		}
		
		$objResponse->script("
		byId('btnCancelarImportarPedido').click();");
	
		$objResponse->loadCommands(listaPedidoDetalle(
			$frmPedido['pageNum'],
			$frmPedido['campOrd'],
			$frmPedido['tpOrd'],
			$frmPedido['valBusq']));
	} else {
		$objResponse->alert(utf8_encode("Verifique que el Pedido tenga Cantidades Solicitadas"));
	}
	
	return $objResponse;
}

function listaCarta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_comp.idPedidoCompra = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT
		carta_sol.idCartaSolicitud,
		carta_sol.fechaCartaSolicitud,
		ped_comp.idPedidoCompra,
		forma_pago_asig.idFormaPagoAsignacion,
		
		(CASE
			WHEN (forma_pago_asig.descripcionFormaPagoAsignacion IS NULL OR forma_pago_asig.descripcionFormaPagoAsignacion = '') THEN
				prov.nombre
			WHEN (forma_pago_asig.descripcionFormaPagoAsignacion IS NOT NULL AND forma_pago_asig.descripcionFormaPagoAsignacion <> '') THEN
				forma_pago_asig.descripcionFormaPagoAsignacion
		END) AS descripcionFormaPagoAsignacion,
		
		COUNT(*) AS cant_unidades
	FROM an_pedido_compra ped_comp
		INNER JOIN an_encabezadocartasolicitud carta_sol ON (ped_comp.idPedidoCompra = carta_sol.idPedidoCompra)
		INNER JOIN an_solicitud_factura ped_comp_det ON (ped_comp.idPedidoCompra = ped_comp_det.idPedidoCompra)
		INNER JOIN an_detallecartasolicitud carta_sol_det ON (carta_sol.idCartaSolicitud = carta_sol_det.idCartaSolicitud)
			AND (carta_sol_det.idSolicitud = ped_comp_det.idSolicitud)
		INNER JOIN formapagoasignacion forma_pago_asig ON (ped_comp_det.idFormaPagoAsignacion = forma_pago_asig.idFormaPagoAsignacion)
		LEFT JOIN cp_proveedor prov ON (forma_pago_asig.idProveedor = prov.id_proveedor) %s
	GROUP BY carta_sol.idCartaSolicitud, ped_comp.idPedidoCompra, ped_comp_det.idFormaPagoAsignacion", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">";
		$htmlTh .= ordenarCampo("xajax_listaCarta", "16%", $pageNum, "fechaCartaSolicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaCarta", "20%", $pageNum, "idCartaSolicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Carta");
		$htmlTh .= ordenarCampo("xajax_listaCarta", "48%", $pageNum, "descripcionFormaPagoAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaCarta", "16%", $pageNum, "cant_unidades", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidades");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaCartaSolicitud']))."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['idCartaSolicitud'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['descripcionFormaPagoAsignacion'])."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['cant_unidades'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_solicitud_compra_pdf.php?valBusq=%s|%s|%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Carta PDF\"/>",
					$row['idPedidoCompra'],
					$row['idCartaSolicitud'],
					$row['idFormaPagoAsignacion']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCarta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCarta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCarta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCarta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCarta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCarta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
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

function listaPedidoCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_comp.estatus_pedido IN (0,1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_comp.id_empresa = %s",
			valTpDato($valCadBusq[0], "text"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_comp.fecha_pedido BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(idPedidoCompra LIKE %s
		OR asig.idAsignacion LIKE %s
		OR referencia_asignacion LIKE %s
		OR asunto_asignacion LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		ped_comp.idPedidoCompra,
		ped_comp.fecha_pedido,
		ped_comp.estatus_pedido,
		asig.idAsignacion,
		asig.referencia_asignacion,
		asig.asunto_asignacion,
		prov.nombre AS nombre_proveedor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_asignacion asig
		INNER JOIN an_pedido_compra ped_comp ON (asig.idAsignacion = ped_comp.idAsignacion)
		INNER JOIN cp_proveedor prov ON (asig.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "fecha_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "idPedidoCompra", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "8%", $pageNum, "idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Asignacion");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "14%", $pageNum, "referencia_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Referencia");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "22%", $pageNum, "asunto_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Asignación");
		$htmlTh .= ordenarCampo("xajax_listaPedidoCompra", "26%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= "<td colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch ($row['estatus_pedido']) {
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/unidadesPendientesPorAsignarPago.png\" title=\"Forma de Pago Parcialmente Asignado\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/unidadesAsignadas.png\" title=\"Forma de Pago Asignado\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_pedido']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['idPedidoCompra']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['idAsignacion']."</td>";
			$htmlTb .= "<td>".$row['referencia_asignacion']."</td>";
			$htmlTb .= "<td>".$row['asunto_asignacion']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAceptar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPedido', '%s', 'Registrar');\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Detalle\"/></a>",
					$contFila,
					$row['idPedidoCompra']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (in_array($row['estatus_pedido'], array(0,1))) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aPlanPago%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPedido', '%s', 'Pagar');\"><img class=\"puntero\" src=\"../img/iconos/ico_asignar_plan_pago.png\" title=\"Asignar Plan de Pago\"/></a>",
					$contFila,
					$row['idPedidoCompra']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aCarta%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCarta', '%s');\"><img class=\"puntero\" src=\"../img/iconos/page_green.png\" title=\"Cartas de Solicitud\"/></a>",
					$contFila,
					$row['idPedidoCompra']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_pedido_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Pedido PDF\"/>",
					$row['idPedidoCompra']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"reportes/an_plan_pago_excel.php?idPedido=%s\"><img class=\"puntero\" src=\"../img/iconos/page_excel.png\" title=\"Exportar Excel\"/></a>",
				$row['idPedidoCompra']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPedidoCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaPedidoDetalle($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("forma_pago_asig.idFormaPagoAsignacion NOT IN (4)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_comp.idPedidoCompra = %s",
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		ped_comp.idPedidoCompra,
		
		ped_comp_det.idSolicitud,
		uni_fis.id_unidad_fisica,
		uni_bas.id_uni_bas,
		uni_bas.nom_uni_bas,
		modelo.nom_modelo,
		vers.nom_version,
		ped_comp_det.flotilla,
		
		forma_pago_asig.idFormaPagoAsignacion,
		prov.id_proveedor,
		prov.nombre,
		(CASE
			WHEN (descripcionFormaPagoAsignacion IS NULL OR descripcionFormaPagoAsignacion = '') THEN
				prov.nombre
			WHEN (descripcionFormaPagoAsignacion IS NOT NULL AND descripcionFormaPagoAsignacion <> '') THEN
				forma_pago_asig.descripcionFormaPagoAsignacion
		END) AS descripcionFormaPagoAsignacion,
		
		ped_comp_det.estado,
		
		(uni_fis.estado_venta + 0) AS estado_venta,
		uni_fis.estado_compra,
		
		ano.id_ano,
		uni_fis.id_condicion_unidad,
		uni_fis.id_color_externo1,
		uni_fis.id_color_interno1,
		uni_fis.id_almacen,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		
		(CASE estado_compra
			WHEN 'COMPRADO' THEN
				(SELECT fact_comp_det_unidad.id_factura_compra FROM an_factura_compra_detalle_unidad fact_comp_det_unidad
				WHERE fact_comp_det_unidad.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			WHEN 'REGISTRADO' THEN
				(SELECT fact_det_unidad.id_factura FROM cp_factura_detalle_unidad fact_det_unidad
				WHERE fact_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad)
		END) AS id_factura_compra,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_factura_detalle_unidad fact_comp_det_unidad
			INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
			INNER JOIN cp_retenciondetalle retencion_det ON (fact_comp_det_unidad.id_factura = retencion_det.idFactura)
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT id_notacargo FROM cp_notadecargo
		WHERE id_detalles_pedido_compra = ped_comp_det.idSolicitud) AS id_nota_cargo,
		
		(SELECT numero_notacargo FROM cp_notadecargo
		WHERE id_detalles_pedido_compra = ped_comp_det.idSolicitud) AS numero_nota_cargo
			
	FROM cp_proveedor prov
		RIGHT JOIN formapagoasignacion forma_pago_asig ON (prov.id_proveedor = forma_pago_asig.idProveedor)
		INNER JOIN an_solicitud_factura ped_comp_det ON (forma_pago_asig.idFormaPagoAsignacion = ped_comp_det.idFormaPagoAsignacion)
		LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp_det.idSolicitud = uni_fis.id_pedido_compra_detalle)
		INNER JOIN an_uni_bas uni_bas ON (ped_comp_det.idUnidadBasica = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "8%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad Básica");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "14%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "20%", $pageNum, "nom_version", $campOrd, $tpOrd, $valBusq, $maxRows, "Versión");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "24%", $pageNum, "descripcionFormaPagoAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Forma de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "8%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Unidad Física");
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "16%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaPedidoDetalle", "10%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= "<td colspan=\"7\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		if ($row['estado'] <= 5) {
			switch ($row['estado_venta']) {
				case "" : $imgEstatusVehiculo = "<img src=\"../img/iconos/error.png\" title=\"No Registrado\"/>"; break;
				case 1 : $imgEstatusVehiculo = "<img src=\"../img/iconos/transito.png\" title=\"En Transito\"/>"; break;
				case 2 : $imgEstatusVehiculo = "<img src=\"../img/iconos/almacen_buen_estado.png\" title=\"Inspeccionado\"/>"; break;
				case 3 : $imgEstatusVehiculo = "<img src=\"../img/iconos/siniestrado.png\" title=\"Siniestrado\"/>"; break;
				case 4 : $imgEstatusVehiculo = "<img src=\"../img/iconos/accept.png\" title=\"Disponible\"/>"; break;
				case 5 : $imgEstatusVehiculo = "<img src=\"../img/iconos/car_error.png\" title=\"Reservado\"/>"; break;
				case 6 : $imgEstatusVehiculo = "<img src=\"../img/iconos/car_go.png\" title=\"Vendido\"/>"; break;
				default : $imgEstatusVehiculo = $row['estado_venta'];
			}
		} else {
			if ($row['estado'] == 6)
				$imgEstatusVehiculo = "<img src=\"../img/iconos/cancel.png\" title=\"Anulado\"/>";
			
			$queryNotaCred = sprintf("SELECT nota_cred.numero_nota_credito FROM cp_pagos_documentos pagos_doc
				INNER JOIN cp_notacredito nota_cred ON (pagos_doc.id_documento = nota_cred.id_notacredito)
			WHERE (pagos_doc.id_documento_pago = %s
				AND pagos_doc.tipo_documento_pago = 'ND')
				AND pagos_doc.tipo_pago = 'NC';",
				valTpDato($row['id_nota_cargo'], "int"));
			$rsNotaCred = mysql_query($queryNotaCred);
			if (!$rsNotaCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		switch ($row['flotilla']) {
			case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
			case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
			default : $imgEstatusUnidadAsignacion = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusVehiculo."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_modelo'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nom_version'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".$row['descripcionFormaPagoAsignacion']."</div>";
				if ($row['idFormaPagoAsignacion'] > 4) {
					$htmlTb .= "<div class=\"textoNegrita_10px\">Nota Cargo Nro.: ".$row['numero_nota_cargo']."</div>";
					if ($rsNotaCred) {
						while ($rowNotaCred = mysql_fetch_assoc($rsNotaCred)) {
							$htmlTb .= "<div class=\"textoNegrita_10px textoRojoNegrita\">Nota Cred Nro.: ".$rowNotaCred['numero_nota_credito']."</div>";
						}
					}
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_unidad_fisica']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['serial_carroceria']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$row['placa']."</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 2) {
				if (xvalidaAcceso(NULL,"an_registrar_vehiculo_form","insertar")) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aTransito%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblRegistrarUnidad', '%s');\"><img class=\"puntero\" src=\"../img/iconos/registrar_estado_vehiculo.png\" title=\"Registrar Unidad\"/></a>",
						$contFila,
						$row['idSolicitud']);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 3) {
				if (xvalidaAcceso(NULL,"an_inspeccion_preentrega_form","insertar")) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aInspeccion%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblInspeccion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/registro_pdi.png\" title=\"Inspección de Pre-Entrega\"/></a>",
						$contFila,
						$row['idSolicitud']);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 4) {
				if (xvalidaAcceso(NULL,"an_preregistro_compra_form","insertar")) {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_registro_compra_form.php?idPedidoDetalle=%s', '_self');\" src=\"../img/iconos/book_next.png\" title=\"Registrar Compra\"/>",
						$row["idSolicitud"]);
				}
			} else if ($row['estado'] == 5 && $row['estado_compra'] == "COMPRADO") {
				if (xvalidaAcceso(NULL,"an_registro_compra_form","insertar")) {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_registro_compra_form.php?id=%s', '_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/>",
						$row["id_factura_compra"]);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 4 || $row['estado'] == 5) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_checklist_compra.php?id_unidad_fisica=%s&view=print', 960, 550);\" src=\"../img/iconos/chk_list_act.png\" title=\"Imprimir Inspección\"/>",
					$row['id_unidad_fisica']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 5 && $row['estado_compra'] == "REGISTRADO") {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro de Compra PDF\"/>",
					$row['id_factura_compra']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] == 5 && $row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir Comprobante de Retención\"/>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estado'] != 6 && $row['estado_compra'] != "REGISTRADO" && $row['estado_venta'] != "RESERVADO") {
				if (xvalidaAcceso(NULL,"an_registrar_vehiculo_form","insertar")) {
					if ($row['id_proveedor'] > 0) {
						$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aInspeccion%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAnularUnidad', '%s');\"><img class=\"puntero\" src=\"../img/iconos/cancel.png\" title=\"Anular Unidad\"/></a>",
							$contFila,
							$row['idSolicitud']);
					} else {
						$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aInspeccion%s\" rel=\"#divFlotante2\" onclick=\"xajax_formAnularUnidad('%s');\"><img class=\"puntero\" src=\"../img/iconos/cancel.png\" title=\"Anular Unidad\"/></a>",
							$contFila,
							$row['idSolicitud']);
					}
				}
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"16\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadPedido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaPlanPagoDetalle($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_comp_det.idPedidoCompra = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_comp_det.idFormaPagoAsignacion IN (4)");
	
	$query = sprintf("SELECT
		vw_iv_modelo.id_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		COUNT(vw_iv_modelo.id_uni_bas) AS cant_confirmada,
		ped_comp_det.flotilla
	FROM an_solicitud_factura ped_comp_det
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (ped_comp_det.idUnidadBasica = vw_iv_modelo.id_uni_bas)
		LEFT JOIN cj_cc_cliente cliente ON (ped_comp_det.id_cliente = cliente.id) %s
	GROUP BY ped_comp_det.idUnidadBasica, ped_comp_det.flotilla", $sqlBusq);
	
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
							$htmlTb .= sprintf("<input type=\"text\" id=\"txtCosto%s\" name=\"txtCosto%s\" class=\"inputHabilitado\" maxlength=\"12\" onblur=\"setFormatoRafk(this,2);\" onkeypress=\"return validarSoloNumerosReales(event)\" size=\"16\" style=\"text-align:right\" value=\"%s\"/>",
								$contFila,
								$contFila,
								number_format(0, 2, "." , ","));
						$htmlTb .= "</td>";
						$htmlTb .= "<td colspan=\"2\" width=\"30%\"></td>";
						$htmlTb .= "<td width=\"20%\">";
							$htmlTb .= sprintf("<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">",
								$contFila);
						$htmlTb .= "</td>";
					$htmlTb .= "</tr>";
					
					$sqlBusq2 = "";
					$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
					$sqlBusq2 .= $cond.sprintf("ped_comp_det.idUnidadBasica = %s",
						valTpDato($row['id_uni_bas'], "int"));
					
					$cond = (strlen($sqlBusq) > 0 || strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
					$sqlBusq2 .= $cond.sprintf("ped_comp_det.flotilla = %s",
						valTpDato($row['flotilla'], "int"));
					
					$queryPedidoCompraDetalle = sprintf("SELECT * FROM an_solicitud_factura ped_comp_det %s %s;", $sqlBusq, $sqlBusq2);
					$rsPedidoCompraDetalle = mysql_query($queryPedidoCompraDetalle);
					if (!$rsPedidoCompraDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$contFila2 = 0;
					while ($rowPedidoCompraDetalle = mysql_fetch_assoc($rsPedidoCompraDetalle)) {
						$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila2++;
						
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
								$htmlTb .= sprintf("<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
								"<input type=\"hidden\" id=\"hddIdPedidoDetalle%s\" name=\"hddIdPedidoDetalle%s\" value=\"%s\">",
									$contFila.":".$contFila2,
									$contFila.":".$contFila2, $contFila.":".$contFila2, $rowPedidoCompraDetalle['idSolicitud']);
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
	
	$objResponse->assign("divListaUnidadPedido","innerHTML",$htmlTblIni.$htmlTb.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularUnidad");
$xajax->register(XAJAX_FUNCTION,"asignarEstadoUnidad");
$xajax->register(XAJAX_FUNCTION,"asignarFormaPago");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"buscarPedidoDetalle");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstColor");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicion");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION,"formAnularUnidad");
$xajax->register(XAJAX_FUNCTION,"formPedido");
$xajax->register(XAJAX_FUNCTION,"formInspeccion");
$xajax->register(XAJAX_FUNCTION,"formRegistrarUnidad");
$xajax->register(XAJAX_FUNCTION,"guardarInspeccion");
$xajax->register(XAJAX_FUNCTION,"guardarPlanPago");
$xajax->register(XAJAX_FUNCTION,"guardarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"importarDcto");
$xajax->register(XAJAX_FUNCTION,"listaCarta");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"listaPedidoDetalle");
$xajax->register(XAJAX_FUNCTION,"listaPlanPagoDetalle");

function cargaLstFormaPagoItm($nombreObjeto, $selId = "") {
	$query = sprintf("SELECT 
		forma_pago_asig.idFormaPagoAsignacion,
		prov.id_proveedor,
		(CASE
			WHEN prov.nombre IS NOT NULL THEN
				CONCAT_WS(' ', forma_pago_asig.descripcionFormaPagoAsignacion, prov.nombre)
			ELSE
				forma_pago_asig.descripcionFormaPagoAsignacion
		END) AS descripcion_forma_pago,
		forma_pago_asig.alias,
		prov_cred.planMayor
	FROM cp_proveedor prov
		RIGHT JOIN formapagoasignacion forma_pago_asig ON (prov.id_proveedor = forma_pago_asig.idProveedor)
		LEFT JOIN cp_prove_credito prov_cred ON (prov.id_proveedor = prov_cred.id_proveedor)
	WHERE (prov.status = 'Activo' OR prov.status IS NULL)
		AND (prov_cred.planMayor = 1 OR prov_cred.planMayor IS NULL)
		AND (prov.credito = 'Si' OR prov.credito IS NULL)
	GROUP BY forma_pago_asig.idFormaPagoAsignacion
	ORDER BY planMayor, forma_pago_asig.descripcionFormaPagoAsignacion, prov.nombre");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_asignarFormaPago(xajax.getFormValues('frmPedido'));\" style=\"width:100%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idFormaPagoAsignacion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idFormaPagoAsignacion']."\">".htmlentities($row['descripcion_forma_pago'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}
?>