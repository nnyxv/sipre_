<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedidoVenta(0, "numeroControl", "DESC", $valBusq));
		
	return $objResponse;
}

function cancelarDespacho($frmDespacho, $frmListaDespacho) {
	$objResponse = new xajaxResponse();
	
	$idFacturaVenta = $frmListaDespacho['hddIdFactura'];
	$idPedidoVenta = $frmListaDespacho['txtIdPedido'];
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM iv_bulto_venta WHERE id_bulto_venta = %s",
		valTpDato($frmDespacho['hddIdBultoVenta'], "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("
	byId('btnCancelarDespachoOculto').click();");
	
	$objResponse->loadCommands(formListaDespacho($idFacturaVenta, $idPedidoVenta));
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN vw_pg_empleados empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	WHERE fact_vent.idDepartamentoOrigenFactura = 0
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
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

function formListaDespacho($idFacturaVenta, $idPedidoVenta) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($idFacturaVenta > 0) {
		// ACTUALIZA LA CANTIDAD A DISTRIBUIR DE LA PIEZA
		$updateSQL = sprintf("UPDATE cj_cc_factura_detalle SET
			por_distribuir = cantidad - (SELECT COUNT(bulto_vent_det.id_factura_detalle) FROM iv_bulto_venta_detalle bulto_vent_det
										WHERE bulto_vent_det.id_factura_detalle = cj_cc_factura_detalle.id_factura_detalle)
		WHERE id_factura = %s;",
			valTpDato($idFacturaVenta, "int"));
	} else if ($idPedidoVenta > 0) {
		// ACTUALIZA LA CANTIDAD A DISTRIBUIR DE LA PIEZA
		$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle SET
			pendiente = cantidad - (SELECT COUNT(bulto_vent_det.id_factura_detalle) FROM iv_bulto_venta_detalle bulto_vent_det
										WHERE bulto_vent_det.id_factura_detalle = iv_pedido_venta_detalle.id_pedido_venta)
		WHERE id_pedido_venta = %s;",
			valTpDato($idPedidoVenta, "int"));
	}
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	if ($idFacturaVenta > 0) {
		// BUSCA LOS DATOS DE LA FACTURA
		$query = sprintf("SELECT *,
			(SELECT SUM(fact_vent_det.cantidad) FROM cj_cc_factura_detalle fact_vent_det
			WHERE fact_vent_det.id_factura = vw_iv_facturas_venta.idFactura) AS cant_despachar,
			
			(SELECT COUNT(bulto_vent.id_factura_venta)
			FROM iv_bulto_venta_detalle bulto_vent_det
				INNER JOIN iv_bulto_venta bulto_vent ON (bulto_vent_det.id_bulto_venta = bulto_vent.id_bulto_venta)
			WHERE bulto_vent.id_factura_venta = vw_iv_facturas_venta.idFactura) AS cant_incluida
		FROM vw_iv_facturas_venta
		WHERE idFactura = %s;",
			valTpDato($idFacturaVenta, "int"));
	} else if ($idPedidoVenta > 0) {
		// BUSCA LOS DATOS DEL PEDIDO
		$query = sprintf("SELECT *,
			(SELECT SUM(ped_vent_det.cantidad) FROM iv_pedido_venta_detalle ped_vent_det
			WHERE ped_vent_det.id_pedido_venta = ped_vent.id_pedido_venta) AS cant_despachar,
			
			(SELECT COUNT(bulto_vent.id_pedido_venta)
			FROM iv_bulto_venta_detalle bulto_vent_det
				INNER JOIN iv_bulto_venta bulto_vent ON (bulto_vent_det.id_bulto_venta = bulto_vent.id_bulto_venta)
			WHERE bulto_vent.id_pedido_venta = ped_vent.id_pedido_venta) AS cant_incluida
		FROM iv_pedido_venta ped_vent
		WHERE ped_vent.id_pedido_venta = %s;",
			valTpDato($idPedidoVenta, "int"));
	}
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("hddIdFactura","value",$idFacturaVenta);
	$objResponse->assign("txtNumeroFactura","value",$row['numeroFactura']);
	$objResponse->assign("txtIdPedido","value",$row['id_pedido_venta']);
	$objResponse->assign("txtNumeroPedidoPropio","value",$row['id_pedido_venta_propio']);
	
	$objResponse->assign("txtCantDespachar","value",number_format($row['cant_despachar'], 2, ".", ","));
	$objResponse->assign("txtCantIncluida","value",number_format($row['cant_incluida'], 2, ".", ","));
	$objResponse->assign("txtCantPendiente","value",number_format($row['cant_despachar'] - $row['cant_incluida'], 2, ".", ","));
	
	$objResponse->loadCommands(listaDespachoVenta(0, "numero_bulto", "DESC", $idFacturaVenta."|".$idPedidoVenta));
	
	return $objResponse;
}

function formDespacho($frmListaSerialDespacho) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaSerialDespacho['cbx'])) {
		foreach ($frmListaSerialDespacho['cbx'] as $indiceItm => $valorItm) {
			$arrayObj[] = $valorItm;
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	return $objResponse;
}

function guardarDespacho($frmDespacho, $frmListaSerialDespacho, $frmListaDespacho) {
	$objResponse = new xajaxResponse();
	
	$idFacturaVenta = $frmListaDespacho['hddIdFactura'];
	$idPedidoVenta = $frmListaDespacho['txtIdPedido'];
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE iv_bulto_venta SET
		estatus = %s
	WHERE id_bulto_venta = %s;",
		valTpDato(1, "boolean"),
		valTpDato($frmDespacho['hddIdBultoVenta'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idBultoVenta = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Bulto de Despacho Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarDespachoOculto').click();");
	
	$objResponse->loadCommands(formListaDespacho($idFacturaVenta, $idPedidoVenta));
	
	return $objResponse;
}

function insertarSerialDespacho($frmAgregarSerialDespacho, $frmDespacho, $frmListaSerialDespacho, $frmListaDespacho) {
	$objResponse = new xajaxResponse();
	
	$idFacturaVenta = $frmListaDespacho['hddIdFactura'];
	$idPedidoVenta = $frmListaDespacho['txtIdPedido'];
	$idArticulo = $frmAgregarSerialDespacho['txtIdArticuloDespacho'];
	
	if (strlen($idArticulo) > 0 && strlen($frmAgregarSerialDespacho['txtSerialArticuloDespacho']) == 0) {
		if ($idFacturaVenta > 0) {
			$query = sprintf("SELECT
				fact_vent_det.por_distribuir
			FROM cj_cc_factura_detalle fact_vent_det
			WHERE fact_vent_det.id_factura = %s
				AND fact_vent_det.id_articulo = %s;",
				valTpDato($idFacturaVenta, "int"),
				valTpDato($idArticulo, "int"));
		} else if ($idPedidoVenta > 0) {
			$query = sprintf("SELECT
				ped_vent_det.pendiente AS por_distribuir
			FROM iv_pedido_venta_detalle ped_vent_det
			WHERE ped_vent_det.id_pedido_venta = %s
				AND ped_vent_det.id_articulo = %s;",
				valTpDato($idPedidoVenta, "int"),
				valTpDato($idArticulo, "int"));
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		if ($totalRows > 0 && $row['por_distribuir'] > 0) {
			$objResponse->script("
			byId('txtSerialArticuloDespacho').focus();
			byId('txtSerialArticuloDespacho').select();");
			
			$objResponse->script(sprintf("mensajeJquery('divMsj','%s','1500');",
				("Item encontrado de exitosamente")));
		} else if ($totalRows > 0 && $row['por_distribuir'] == 0) {
			$objResponse->script("
			document.forms['frmAgregarSerialDespacho'].reset();");
			
			$objResponse->script("
			byId('txtIdArticuloDespacho').focus();
			byId('txtIdArticuloDespacho').select();");
			
			$objResponse->script(sprintf("alertaJquery('divMsj','%s','1500');",
				("Item despachado en su totalidad")));
		} else {
			$objResponse->script("
			document.forms['frmAgregarSerialDespacho'].reset();");
			
			$objResponse->script("
			byId('txtIdArticuloDespacho').focus();
			byId('txtIdArticuloDespacho').select();");
			
			$objResponse->script(sprintf("errorJquery('divMsj','%s','1500');",
				("Item no encontrado")));
		}
	} else {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		if (isset($frmListaSerialDespacho['cbx'])) {
			foreach ($frmListaSerialDespacho['cbx'] as $indiceItm => $valorItm) {
				$arrayObj[] = $valorItm;
			}
		}
		
		$txtSerialArticuloDespacho = $frmAgregarSerialDespacho['txtSerialArticuloDespacho'];
		
		$contFila = count($arrayObj);
		$sigValor = $arrayObj[count($arrayObj)-1];
		
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmListaSerialDespacho['hddIdArticulo'.$valor] == $idArticulo
				&& $frmListaSerialDespacho['txtSerialArticulo'.$valor] == $txtSerialArticuloDespacho) {
					$existe = true;
				}
			}
		}
		
		if ($idFacturaVenta > 0) {
			// BUSCA A VER SI TODAVIA EXISTE CANTIDAD POR DESPACHAR DE LA PIEZA
			$query = sprintf("SELECT
				fact_vent_det.id_factura_detalle
			FROM cj_cc_factura_detalle fact_vent_det
			WHERE fact_vent_det.id_factura = %s
				AND fact_vent_det.id_articulo = %s
				AND fact_vent_det.por_distribuir > 0;",
				valTpDato($idFacturaVenta, "int"),
				valTpDato($idArticulo, "int"));
		} else if ($idPedidoVenta > 0) {
			// BUSCA A VER SI TODAVIA EXISTE CANTIDAD POR DESPACHAR DE LA PIEZA
			$query = sprintf("SELECT
				ped_vent_det.id_pedido_venta_detalle
			FROM iv_pedido_venta_detalle ped_vent_det
			WHERE ped_vent_det.id_pedido_venta = %s
				AND ped_vent_det.id_articulo = %s
				AND ped_vent_det.pendiente > 0;",
				valTpDato($idPedidoVenta, "int"),
				valTpDato($idArticulo, "int"));
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		$idFacturaDetalle = $row['id_factura_detalle'];
		$idPedidoDetalle = $row['id_pedido_venta_detalle'];
		
		if ($totalRows == 0) {
			$objResponse->script("
			document.forms['frmAgregarSerialDespacho'].reset();");
			
			$objResponse->script("
			byId('txtIdArticuloDespacho').focus();
			byId('txtIdArticuloDespacho').select();");
			
			$objResponse->script(sprintf("alertaJquery('divMsj','%s','1500');",
				("Este item ya fue despachado en su totalidad")));
		} else if ($existe == false) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			mysql_query("START TRANSACTION;");
			
			// SI ES EL PRIMER ARTICULO
			if ($contFila == 1) {
				// BUSCA EL ULTIMO BULTO DE LA FACTURA DE VENTA
				$query = sprintf("SELECT * FROM iv_bulto_venta WHERE id_factura_venta = %s ORDER BY numero_bulto DESC;",
					valTpDato($idFacturaVenta, "int"));
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$row = mysql_fetch_assoc($rs);
				
				$numeroBulto = $row['numero_bulto'] + 1;
				
				// INSERTA LA CABECERA DEL BULTO
				$insertSQL = sprintf("INSERT INTO iv_bulto_venta (numero_guia, id_factura_venta, id_pedido_venta, numero_bulto, fecha_creacion, id_empleado_creador, estatus)
				VALUE (%s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFacturaVenta."-".$frmListaDespacho['txtIdPedido'], "text"),
					valTpDato($idFacturaVenta, "int"),
					valTpDato($frmListaDespacho['txtIdPedido'], "int"),
					valTpDato($numeroBulto, "int"),
					valTpDato("NOW()", "campo"),
					valTpDato($_SESSION['idUsuarioSysGts'], "int"),
					valTpDato(0, "boolean"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idBultoVenta = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				$objResponse->assign("hddIdBultoVenta","value",$idBultoVenta);
				$objResponse->assign("txtNumeroBulto","value",$numeroBulto);
			} else {
				$idBultoVenta = $frmDespacho['hddIdBultoVenta'];
			}
			
			// INSERTA EL DETALLE DEL BULTO
			$insertSQL = sprintf("INSERT INTO iv_bulto_venta_detalle (id_bulto_venta, id_factura_detalle, id_pedido_venta_detalle, serial_articulo, fecha_creacion)
			VALUE (%s, %s, %s, %s, %s);",
				valTpDato($idBultoVenta, "int"),
				valTpDato($idFacturaDetalle, "int"),
				valTpDato($idPedidoDetalle, "int"),
				valTpDato($txtSerialArticuloDespacho, "text"),
				valTpDato("NOW()", "campo"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idBultoVentaDetalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA LA CANTIDAD A DISTRIBUIR DE LA PIEZA
			if ($idFacturaVenta > 0) {
				$updateSQL = sprintf("UPDATE cj_cc_factura_detalle SET
					por_distribuir = cantidad - (SELECT COUNT(bulto_vent_det.id_factura_detalle) FROM iv_bulto_venta_detalle bulto_vent_det
												WHERE bulto_vent_det.id_factura_detalle = cj_cc_factura_detalle.id_factura_detalle)
				WHERE id_factura_detalle = %s;",
					valTpDato($idFacturaDetalle, "int"));
			} else if ($idPedidoVenta > 0) {
				$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle SET
					pendiente = cantidad - (SELECT COUNT(bulto_vent_det.id_pedido_venta_detalle) FROM iv_bulto_venta_detalle bulto_vent_det
												WHERE bulto_vent_det.id_pedido_venta_detalle = iv_pedido_venta_detalle.id_pedido_venta_detalle)
				WHERE id_pedido_venta_detalle = %s;",
					valTpDato($idPedidoDetalle, "int"));
			}
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// BUSCA LOS DATOS DEL ARTICULO
			if ($idFacturaVenta > 0) {
				$queryBultoDet = sprintf("SELECT 
					bulto_vent_det.*,
					vw_iv_art_datos_bas.id_articulo,
					vw_iv_art_datos_bas.codigo_articulo,
					vw_iv_art_datos_bas.descripcion
				FROM cj_cc_factura_detalle fact_vent_det
					INNER JOIN iv_bulto_venta_detalle bulto_vent_det ON (fact_vent_det.id_factura_detalle = bulto_vent_det.id_factura_detalle)
					INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_bas ON (fact_vent_det.id_articulo = vw_iv_art_datos_bas.id_articulo)
				WHERE bulto_vent_det.id_bulto_venta_detalle = %s;",
					valTpDato($idBultoVentaDetalle, "int"));
			} else if ($idPedidoVenta > 0) {
				$queryBultoDet = sprintf("SELECT 
					bulto_vent_det.*,
					vw_iv_art_datos_bas.id_articulo,
					vw_iv_art_datos_bas.codigo_articulo,
					vw_iv_art_datos_bas.descripcion
				FROM iv_pedido_venta_detalle ped_vent_det
					INNER JOIN iv_bulto_venta_detalle bulto_vent_det ON (ped_vent_det.id_pedido_venta_detalle = bulto_vent_det.id_pedido_venta_detalle)
					INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_bas ON (ped_vent_det.id_articulo = vw_iv_art_datos_bas.id_articulo)
				WHERE bulto_vent_det.id_bulto_venta_detalle = %s;",
					valTpDato($idBultoVentaDetalle, "int"));
			}
			$rsBultoDet = mysql_query($queryBultoDet);
			if (!$rsBultoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsBultoDet = mysql_num_rows($rsBultoDet);
			$rowBultoDet = mysql_fetch_assoc($rsBultoDet);
			
			// INSERTA EL ARTICULO MEDIANTE INJECT
			$objResponse->script(sprintf("$('#trItmPie').before('".
				"<tr align=\"left\" id=\"trItm:%s\" class=\"textoGris_11px %s\" title=\"trItm:%s\">".
					"<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
						"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td align=\"center\">%s</td>".
					"<td>%s</td>".
					"<td>%s</td>".
					"<td><input type=\"text\" id=\"txtSerialArticulo%s\" name=\"txtSerialArticulo%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdBultoVentaDetalle%s\" name=\"hddIdBultoVentaDetalle%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArticulo%s\" name=\"hddIdArticulo%s\" readonly=\"readonly\" value=\"%s\"/></td>".
				"</tr>');",
				$contFila, $clase, $contFila,
					$contFila,
						$contFila,
					date(spanDateFormat." h:i:s a",strtotime($rowBultoDet['fecha_creacion'])),
					elimCaracter(utf8_encode($rowBultoDet['codigo_articulo']),";"),
					preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($rowBultoDet['descripcion']))),
					$contFila, $contFila, utf8_encode($rowBultoDet['serial_articulo']),
						$contFila, $contFila, $idBultoVentaDetalle,
						$contFila, $contFila, $idArticulo));
			
			$arrayObj[] = $contFila;
			
			mysql_query("COMMIT;");
			
			$objResponse->script("
			document.forms['frmAgregarSerialDespacho'].reset();");
			
			$objResponse->script("
			byId('txtIdArticuloDespacho').focus();
			byId('txtIdArticuloDespacho').select();");
		} else {
			$objResponse->script("
			document.forms['frmAgregarSerialDespacho'].reset();");
			
			$objResponse->script("
			byId('txtIdArticuloDespacho').focus();
			byId('txtIdArticuloDespacho').select();");
			
			$objResponse->script(sprintf("alertaJquery('divMsj','%s','1500');",
				("Este item ya se encuentra incluido")));
		}
	}
	
	return $objResponse;
}

function listaDespachoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(bulto_vent.id_factura_venta = %s
	OR bulto_vent.id_pedido_venta = %s)",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[1], "int"));
	
	/*if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}*/
	
	$query = sprintf("SELECT 
		bulto_vent.*,
		vw_pg_empleado.nombre_empleado,
		
		(SELECT COUNT(bulto_vent_det.id_bulto_venta)
		FROM iv_bulto_venta_detalle bulto_vent_det
		WHERE bulto_vent_det.id_bulto_venta = bulto_vent.id_bulto_venta) AS cant_items
	FROM iv_bulto_venta bulto_vent
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (bulto_vent.id_empleado_creador = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaDespachoVenta", "16%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaDespachoVenta", "12%", $pageNum, "numero_bulto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Bulto");
		$htmlTh .= ordenarCampo("xajax_listaDespachoVenta", "62%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado");
		$htmlTh .= ordenarCampo("xajax_listaDespachoVenta", "10%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= "<td class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus']) {
			case 0 : $imgEstatusBulto = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pendiente\"/>"; break;
			case 1 : $imgEstatusBulto = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Aprobado\"/>"; break;
			default : $imgEstatusBulto = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusBulto."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i:s a",strtotime($row['fecha_creacion']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_bulto']."</td>";
			$htmlTb .= "<td>".$row['nombre_empleado']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cant_items']."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/iv_despacho_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_green.png\" title=\"Reporte\"/></td>",
				$row['id_bulto_venta']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDespachoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDespachoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaDespachoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDespachoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDespachoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaDespacho","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPedidoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT SUM(fact_vent_det.por_distribuir)
	FROM cj_cc_factura_detalle fact_vent_det
	WHERE fact_vent_det.id_factura = vw_iv_fact_vent.idFactura) > 0");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(estatus_pedido_venta NOT IN (0,1,2)
	OR estatus_pedido_venta IS NULL)");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("ped_vent.estatus_pedido_venta IN (1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("ped_vent.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("ped_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado_preparador = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
			INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
		WHERE vw_pg_clave_movimiento.tipo = 3
			AND mov.id_documento = vw_iv_fact_vent.idFactura) = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numeroFactura LIKE %s
		OR numeroControl LIKE %s
		OR id_pedido_venta_propio LIKE %s
		OR id_pedido_venta_referencia LIKE %s
		OR numero_siniestro LIKE %s
		OR ci_cliente LIKE %s
		OR nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT
		idFactura,
		id_pedido_venta,
		fechaRegistroFactura,
		numeroFactura,
		numeroControl,
		id_pedido_venta_propio,
		id_pedido_venta_referencia,
		numero_siniestro,
		nombre_cliente,
		condicion_pago,
		estatus_pedido_venta,
		id_empleado_aprobador,
		
		(SELECT COUNT(ped_vent_det.id_pedido_venta) AS items
		FROM iv_pedido_venta_detalle ped_vent_det
		WHERE ped_vent_det.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta) AS items,
		
		(SELECT SUM(ped_vent_det.cantidad) AS pedidos
		FROM iv_pedido_venta_detalle ped_vent_det
		WHERE ped_vent_det.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta) AS pedidos,
		
		(SELECT SUM(ped_vent_det.pendiente) AS pendientes
		FROM iv_pedido_venta_detalle ped_vent_det
		WHERE ped_vent_det.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta) AS pendientes,
		
		(IFNULL(vw_iv_fact_vent.subtotal, 0)
			- IFNULL(vw_iv_fact_vent.subtotal_descuento, 0)) AS total_neto,
		
		(IFNULL((CASE
			WHEN (estatus_pedido_venta IS NULL) THEN
				calculoIvaFactura
			WHEN (estatus_pedido_venta IS NOT NULL) THEN
				(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
				FROM iv_pedido_venta_iva ped_iva
				WHERE ped_iva.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta)
		END), 0)) AS total_iva,
		
		(IFNULL(vw_iv_fact_vent.subtotal, 0)
			- IFNULL(vw_iv_fact_vent.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(ped_gasto.monto) AS total_gasto
					FROM iv_pedido_venta_gasto ped_gasto
					WHERE (ped_gasto.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta)), 0)
			+ IFNULL((CASE
				WHEN (estatus_pedido_venta IS NULL) THEN
					calculoIvaFactura
				WHEN (estatus_pedido_venta IS NOT NULL) THEN
					(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
						FROM iv_pedido_venta_iva ped_iva
						WHERE (ped_iva.id_pedido_venta = vw_iv_fact_vent.id_pedido_venta))
			END), 0)) AS total
	FROM vw_iv_facturas_venta vw_iv_fact_vent %s
	
	UNION
	
	SELECT
		NULL,
		id_pedido_venta,
		ped_vent.fecha,
		NULL,
		NULL,
		id_pedido_venta_propio,
		id_pedido_venta_referencia,
		pres_vent.numero_siniestro,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		ped_vent.condicion_pago,
		ped_vent.estatus_pedido_venta,
		ped_vent.id_empleado_aprobador,
		
		(SELECT COUNT(ped_vent_det.id_pedido_venta) AS items
		FROM iv_pedido_venta_detalle ped_vent_det
		WHERE ped_vent_det.id_pedido_venta = ped_vent.id_pedido_venta) AS items,
		
		(SELECT SUM(ped_vent_det.cantidad) AS pedidos
		FROM iv_pedido_venta_detalle ped_vent_det
		WHERE ped_vent_det.id_pedido_venta = ped_vent.id_pedido_venta) AS pedidos,
		
		(SELECT SUM(ped_vent_det.pendiente) AS pendientes
		FROM iv_pedido_venta_detalle ped_vent_det
		WHERE ped_vent_det.id_pedido_venta = ped_vent.id_pedido_venta) AS pendientes,
		
		(IFNULL(ped_vent.subtotal, 0)
			- IFNULL(ped_vent.subtotal_descuento, 0)) AS total_neto,
		
		(SELECT SUM(ped_iva.subtotal_iva) AS total_iva
		FROM iv_pedido_venta_iva ped_iva
		WHERE ped_iva.id_pedido_venta = ped_vent.id_pedido_venta) AS total_iva,
		
		(IFNULL(ped_vent.subtotal, 0)
			- IFNULL(ped_vent.subtotal_descuento, 0)) AS total
	FROM iv_presupuesto_venta pres_vent
		RIGHT JOIN iv_pedido_venta ped_vent ON (pres_vent.id_presupuesto_venta = ped_vent.id_presupuesto_venta)
		INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id) %s", $sqlBusq, $sqlBusq2);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "7%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "7%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "7%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "7%", $pageNum, "id_pedido_venta_propio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "9%", $pageNum, "id_pedido_venta_referencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "12%", $pageNum, "numero_siniestro", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Siniestro");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "28%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "7%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgPedidoModulo = "";
		if ($row['estatus_pedido_venta'] == "") {
			$imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Factura CxC\"/>";
			$imgEstatusPedido = "";
		} else {
			$imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Factura Repuestos\"/>";
			
			switch($row['estatus_pedido_venta']) {
				case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pendiente por Terminar\"/>"; break;
				case ($row['estatus_pedido_venta'] == 1 || ($row['estatus_pedido_venta'] == 2 && $row['id_empleado_aprobador'] == "")) : 
					$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Convertido a Pedido\"/>"; break;
				case ($row['estatus_pedido_venta'] == 2 && $row['id_empleado_aprobador'] != "") :
					$imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Aprobado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
				default : $imgEstatusPedido = "";
			}
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_propio']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_referencia']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_siniestro']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= ($row['condicion_pago'] == 0) ? "<td align=\"center\" class=\"divMsjAlerta\">" : "<td align=\"center\" class=\"divMsjInfo\">";
				$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><a class=\"modalImg\" id=\"aDespacho%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblListaDespacho', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/package_add.png\" title=\"Bultos\"/></a></td>",
				$contFila,
				$row['idFactura'],
				$row['id_pedido_venta']);
			$htmlTb .= "<td>";
			if ($row['idFactura'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/iv_factura_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/>",
					$row['idFactura']);
			} else if ($row['id_pedido_venta'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/iv_pedido_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Pedido Venta PDF\"/>",
					$row['id_pedido_venta']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaPedidoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cancelarDespacho");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"formListaDespacho");
$xajax->register(XAJAX_FUNCTION,"formDespacho");
$xajax->register(XAJAX_FUNCTION,"guardarDespacho");
$xajax->register(XAJAX_FUNCTION,"insertarSerialDespacho");
$xajax->register(XAJAX_FUNCTION,"listaDespachoVenta");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVenta");
?>