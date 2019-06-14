<?php


function asignarArticulo($hddNumeroArt, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	$idFacturaDet = $frmListaArticulo['hddIdFactDet'.$hddNumeroArt];
	$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$hddNumeroArt];
	
	// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
	$queryFacturaDet = sprintf("SELECT * FROM cj_cc_factura_detalle fact_vent_det WHERE fact_vent_det.id_factura_detalle = %s;",
		valTpDato($idFacturaDet, "text"));
	$rsFacturaDet = mysql_query($queryFacturaDet);
	if (!$rsFacturaDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFacturaDet = mysql_fetch_assoc($rsFacturaDet);
	
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
		SUM(IFNULL(art_alm.cantidad_inicio, 0) + IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0)) AS existencia,
		SUM(IFNULL(art_alm.cantidad_reservada, 0)) AS cantidad_reservada,	
		SUM(IFNULL(art_alm.cantidad_inicio, 0) + IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0)) AS cantidad_disponible_fisica,
		SUM(IFNULL(art_alm.cantidad_espera, 0)) AS cantidad_espera,
		SUM(IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_bloqueada,
		SUM(IFNULL(art_alm.cantidad_inicio, 0) + IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0)) AS cantidad_disponible_logica
	FROM iv_articulos_almacen art_alm
	WHERE art_alm.id_articulo = %s
	GROUP BY art_alm.id_articulo;",
		valTpDato($idArticulo, "int"));
	$rsArtSaldo = mysql_query($queryArtSaldo);
	if (!$rsArtSaldo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtSaldo = mysql_fetch_assoc($rsArtSaldo);
	
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	$objResponse->assign("txtCodigoArt","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",htmlentities($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtTipoPiezaArt","value",$rowArticulo['tipo_articulo']);
	$objResponse->assign("txtCantDisponible","value",$rowArtSaldo['cantidad_disponible_logica']);
	
	$objResponse->assign("txtCantidadArt","value",($rowFacturaDet['cantidad'] - $rowFacturaDet['devuelto']));
	
	$objResponse->script(sprintf("
	if (navigator.appName == 'Netscape') {
		byId('txtCantidadRecibArt').onkeypress = function(e){ %s }
	} else if (navigator.appName == 'Microsoft Internet Explorer') {
		byId('txtCantidadRecibArt').onkeypress = function(e){ %s }
	}",
		(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(e);" : "return validarSoloNumerosReales(e);"),
		(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(event);" : "return validarSoloNumerosReales(event);")));
	
	$objResponse->script("byId('txtCantDisponible').className = '".(($rowArtSaldo['cantidad_disponible_logica'] > 0) ? "inputCantidadDisponible" : "inputCantidadNoDisponible")."'");
	
	$objResponse->assign("txtCantidadRecibArt","value",number_format($frmListaArticulo['txtCantItm'.$hddNumeroArt], 2, ".", ","));
	
	$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
	
	return $objResponse;
}

function cargarDcto($idFactura, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	$contFila = $arrayObjPieDetalle[count($arrayObjPieDetalle)-1];
	
	// ELIMINA LOS DETALLES DEL PEDIDO QUE SE CARGARON EN PANTALLA
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$objResponse->script("
			fila = document.getElementById('trItmDetalle_".$valorPieDetalle."');
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
		vw_iv_fact_vent.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_facturas_venta vw_iv_fact_vent
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_fact_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE vw_iv_fact_vent.idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idCliente = $rowFact['id_cliente'];
	$idPedido = $rowFact['id_pedido_venta'];
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		errorInsertarArticulo($objResponse); return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico inv_fis
	WHERE inv_fis.id_empresa = %s
		AND inv_fis.estatus = 0",
		valTpDato($idEmpresa , "int"));
	$rsInvFis = mysql_query($queryInvFis);
	if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsInvFis = mysql_num_rows($rsInvFis);
	
	if ($totalRowsInvFis == 0) {
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "2", "1", "3"));
		
		// BUSCA LOS DATOS DEL PEDIDO
		$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_venta WHERE id_pedido_venta = %s;",
			valTpDato($idPedido, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		$idCondicionPago = $rowPedido['condicion_pago'];
		$idClaveMovimiento = $rowPedido['id_clave_movimiento'];
		
		// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
		$queryFacturaDet = sprintf("SELECT * FROM cj_cc_factura_detalle fact_vent_det
		WHERE fact_vent_det.id_factura = %s
			AND (fact_vent_det.cantidad - fact_vent_det.devuelto) > 0;",
			valTpDato($idFactura, "int"));
		$rsFacturaDet = mysql_query($queryFacturaDet);
		if (!$rsFacturaDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowFacturaDet = mysql_fetch_assoc($rsFacturaDet)) {
			$contFila++;
			
			$idArticulo = $rowFacturaDet['id_articulo'];
		
			// BUSCA LOS IMPUESTOS DEL DETALLE DE LA FACTURA
			$queryFacturaDetImpuesto = sprintf("SELECT * FROM cj_cc_factura_detalle_impuesto WHERE id_factura_detalle = %s;",
				valTpDato($rowFacturaDet['id_factura_detalle'], "int"));
			$rsFacturaDetImpuesto = mysql_query($queryFacturaDetImpuesto);
			if (!$rsFacturaDetImpuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$arrayDetImpuesto = NULL;
			while ($rowFacturaDetImpuesto = mysql_fetch_assoc($rsFacturaDetImpuesto)) {
				$arrayDetImpuesto[] = $rowFacturaDetImpuesto['id_impuesto'];
			}
			$idIva = (count($arrayDetImpuesto) > 0) ? implode(",",$arrayDetImpuesto) : -1;
			
			// BUSCA LA INFORMACION EN EL KARDEX
			$queryKardex = sprintf("SELECT * FROM iv_kardex kardex
			WHERE kardex.tipo_movimiento IN (3)
				AND kardex.id_documento = %s
				AND kardex.id_articulo = %s
				AND kardex.cantidad = %s
				AND kardex.precio = %s
				AND kardex.costo = %s;",
				valTpDato($idFactura, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($rowFacturaDet['cantidad'], "real_inglesa"),
				valTpDato($rowFacturaDet['precio_unitario'], "real_inglesa"),
				valTpDato($rowFacturaDet['costo_compra'], "real_inglesa"));
			$rsKardex = mysql_query($queryKardex);
			if (!$rsKardex) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsKardex = mysql_num_rows($rsKardex);
			$rowKardex = mysql_fetch_assoc($rsKardex);
			
			$hddIdArticuloCosto = $rowKardex['id_articulo_costo'];
			
			$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_venta_detalle ped_venta_det
			WHERE ped_venta_det.id_pedido_venta = %s
				AND ped_venta_det.id_articulo = %s
				AND ped_venta_det.id_casilla = %s
				AND ped_venta_det.cantidad = %s
				AND (ped_venta_det.id_articulo_almacen_costo = %s OR ped_venta_det.id_articulo_almacen_costo IS NULL);",
				valTpDato($idPedido, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($rowKardex['id_casilla'], "int"),
				valTpDato($rowKardex['cantidad'], "real_inglesa"),
				valTpDato($rowKardex['id_articulo_almacen_costo'], "int"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
			
			// BUSCA LA UBICACION PREDETERMINADA
			$queryArtAlm = sprintf("SELECT * FROM vw_iv_articulos_almacen
			WHERE id_empresa = %s
				AND id_articulo = %s
				AND casilla_predeterminada = 1;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rsArtAlm = mysql_query($queryArtAlm);
			if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
			$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
			
			$idCasilla = $rowArtAlm['id_casilla'];
			$ubicacion = $rowArtAlm['descripcion_almacen']." ".$rowArtAlm['ubicacion'];
			$claseAlmacen = ($totalRowsArtAlm > 0) ? "" : "trResaltar6";
			
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArticulo = mysql_num_rows($rsArticulo);
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
			
			$cantDisponible = ($rowFacturaDet['cantidad'] - $rowFacturaDet['devuelto']);
			$cantDevolver = ($rowFacturaDet['cantidad'] - $rowFacturaDet['devuelto']);
			$cantPendiente = doubleval($cantDisponible) - doubleval($cantDevolver);
			$porcIva = ($rowPedidoDet['id_iva'] > 0) ? $rowPedidoDet['iva'] : "-";
			
			// SI ES POR REPOSICION O COSTO PROMEDIO BUSCA EL UNICO LOTE DE LA UBICACION
			// O PRIMER LOTE PORQUE QUE NO TIENE ASIGNADO ALGUNO, DEBIDO A QUE SON DEVOLUCIONES DE FACTURAS DE CUANDO NO SE MANEJABAN LOTES
			if (in_array($ResultConfig12, array(1,2)) || !($hddIdArticuloCosto > 0)) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
				// BUSCA EL LOTE PARA LA DEVOLUCION
				$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
				WHERE vw_iv_art_almacen_costo.id_articulo = %s
					AND vw_iv_art_almacen_costo.id_casilla = %s
					AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
				ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				$rsArtCosto = mysql_query($queryArtCosto);
				if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
				$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
				
				$hddIdArticuloAlmacenCosto = $rowArtCosto['id_articulo_almacen_costo'];
				$hddIdArticuloCosto = $rowArtCosto['id_articulo_costo'];
			}
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (6,9,2)
				AND iva.idIva IN (%s);", 
				valTpDato($idIva, "campo"));
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
					$contFila, $contIva, $contFila, $contIva, 1, 
					$contFila.":".$contIva);
			}
			
			// INSERTA EL ARTICULO MEDIANTE INJECT
			$objResponse->script(sprintf(
			"$('#trItmPieDetalle').before('".
				"<tr id=\"trItmDetalle_%s\" align=\"left\">".
					"<td title=\"trItmDetalle_%s\"><input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbxPieDetalle\" name=\"cbxPieDetalle[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td id=\"tdNumItmDetalle_%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
					"<td><a class=\"modalImg\" id=\"aEditarItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a></td>".
					"<td id=\"tdCodArt:%s\" class=\"%s\">%s</td>".
					"<td><div id=\"tdDescripcionItm:%s\">%s</div>".
						"<span id=\"spnUbicacion%s\" class=\"textoNegrita_9px\">%s</span>".
						"%s</td>".
					"<td><input type=\"text\" id=\"txtCantFactItm%s\" name=\"txtCantFactItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
					"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
					"<td id=\"tdCantPendienteItm:%s\" align=\"right\">%s</td>".
					"<td><input type=\"text\" id=\"txtPrecioItm%s\" name=\"txtPrecioItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddCostoItm%s\" name=\"hddCostoItm%s\" readonly=\"readonly\" title=\"Costo Unitario\" value=\"%s\"/></td>".
					"<td id=\"tdIvaItm%s\">%s</td>".
					"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdFactDet%s\" name=\"hddIdFactDet%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArticuloAlmacenCosto%s\" name=\"hddIdArticuloAlmacenCosto%s\" readonly=\"readonly\" title=\"Ubicacion del Lote\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" readonly=\"readonly\" title=\"Lote\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/></td>".
				"</tr>');
				
				byId('aEditarItem:%s').onclick = function() {
					abrirDivFlotante1(this, 'tblArticulo', '%s');
				}",
				$contFila,
					$contFila, $contFila,
						 $contFila,
					$contFila, $contFila,
					$contFila,
					$contFila, $claseAlmacen, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
					$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($rowArticulo['descripcion']))),
						$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
						((in_array($ResultConfig12, array(1,2)) || $hddIdArticuloCosto == 0) ? "" : "<div id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</div>"),
					$contFila, $contFila, number_format($cantDisponible, 2, ".", ","),
					$contFila, $contFila, number_format($cantDevolver, 2, ".", ","),
					$contFila, number_format($cantPendiente, 2, ".", ","),
					$contFila, $contFila, number_format($rowFacturaDet['precio_unitario'], 2, ".", ","),
						$contFila, $contFila, number_format($rowFacturaDet['costo_compra'], 2, ".", ","),
					$contFila, $ivaUnidad,
					$contFila, $contFila, number_format(($cantDevolver * $rowFacturaDet['precio_unitario']), 2, ".", ","),
						$contFila, $contFila, $rowFacturaDet['id_factura_detalle'],
						$contFila, $contFila, $idArticulo,
						$contFila, $contFila, $hddIdArticuloAlmacenCosto,
						$contFila, $contFila, $hddIdArticuloCosto,
						$contFila, $contFila, $idCasilla,
					
					$contFila,
						$contFila));
		}
		
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
			valTpDato($idCliente, "text"));
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsCliente = mysql_num_rows($rsCliente);
		$rowCliente = mysql_fetch_assoc($rsCliente);
		
		if ($idCondicionPago >= 0 && $idClaveMovimiento != "") { // 0 = Credito, 1 = Contado
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
				
				$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
		
				$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "2", "0", "3"));
				
				$objResponse->script(sprintf("
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,2);
					xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '2', '0', '3');
				}"));
			} else {
				$objResponse->assign("txtDiasCreditoCliente","value","0");
				
				$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "2", "1", "3"));
				
				$objResponse->script(sprintf("
				byId('lstTipoClave').onchange = function () {
					selectedOption(this.id,2);
					xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '2', '1', '3');
				}"));
			}
		}
		
		$nombreCondicionPago = ($idCondicionPago == 0) ? "Crédito" : "Contado";
		$objResponse->assign("hddTipoPago","value",$idCondicionPago);
		$objResponse->assign("txtTipoPago","value",$nombreCondicionPago);
		
		$objResponse->assign("tdGastos","innerHTML",formularioGastos(false,$idFactura,"FACTURA_VENTA"));
		
		// DATOS DE LA NOTA DE CREDITO
		$objResponse->assign("txtFechaNotaCredito","value",date(spanDateFormat));
		
		// DATOS DEL CLIENTE
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
		$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
		$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
		$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
		$objResponse->assign("hddPagaImpuesto","value",$rowCliente['paga_impuesto']);
		$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0 && $totalRowsCliente > 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));
		
		// DATOS DE LA FACTURA
		$objResponse->assign("txtIdEmpresa","value",$idEmpresa);
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowFact['nombre_empresa']));
		$objResponse->assign("txtIdFactura","value",$rowFact['idFactura']);
		$objResponse->assign("txtFechaFactura","value",date(spanDateFormat,strtotime($rowFact['fechaRegistroFactura'])));
		$objResponse->assign("txtFechaVencimientoFactura","value",date(spanDateFormat,strtotime($rowFact['fechaVencimientoFactura'])));
		$objResponse->assign("txtNumeroFactura","value",$rowFact['numeroFactura']);
		$objResponse->assign("txtNumeroControlFactura","value",$rowFact['numeroControl']);
		$objResponse->assign("txtIdPedido","value",$rowFact['id_pedido_venta']);
		$objResponse->assign("hddFechaPedido","value",date("Y-m-d",strtotime($rowPedido['fecha'])));
		$objResponse->assign("txtNumeroPedidoPropio","value",(($rowPedido['id_pedido_venta_propio'] != "") ? $rowPedido['id_pedido_venta_propio'] : ""));
		$objResponse->assign("hddIdMoneda","value",$rowMoneda['idmoneda']);
		$objResponse->assign("txtMoneda","value",utf8_encode($rowMoneda['descripcion']));
		$objResponse->assign("hddIdEmpleado","value",$rowFact['id_empleado_preparador']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowFact['nombre_empleado']));
		$objResponse->assign("txtTipoClaveFactura","value","3.- VENTA");
		$objResponse->assign("hddIdClaveMovimiento","value",$rowPedido['id_clave_movimiento']);
		$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowPedido['descripcion_clave_movimiento']));
		$objResponse->assign("txtDescuento","value",number_format($rowFact['porcentaje_descuento'], 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format($rowFact['subtotal_descuento'], 2, ".", ","));
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	} else {
		$objResponse->script("
		alert('Usted no puede Aprobar Devoluciones de Venta, debido a que está en Proceso un Inventario Físico');
		location='iv_devolucion_venta_list.php';");
	}
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $bloquearForm = "") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			if ($valorPieDetalle > 0 && strlen($frmListaArticulo['hddIdCasilla'.$valorPieDetalle]) == "") {
				$objResponse->alert("Existen artículos los cuales no tienen una ubicación asignada");
			}
		}
	}
	
	$idFactura = $frmDcto['txtIdFactura'];
	$hddPagaImpuesto = ($frmDcto['hddPagaImpuesto'] != "") ? $frmDcto['hddPagaImpuesto'] : 1;
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DE LA FACTURA
	$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
		valTpDato($idFactura, "int"));
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFact = mysql_fetch_assoc($rsFact);
		
	$txtBaseImponibleIva = 0;
	$txtIva = 0;
	$txtSubTotalIva = 0;
	$txtBaseImponibleIvaLujo = 0;
	$txtIvaLujo = 0;
	$txtSubTotalIvaLujo = 0;
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indiceIva => $valorIva) {
			if ($frmTotalDcto["hddLujoIva".$valorIva] == 1) {
				$txtBaseImponibleIvaLujo = str_replace(",","",$frmTotalDcto['txtBaseImpIva'.$valorIva]);
				$txtIvaLujo += str_replace(",","",$frmTotalDcto['txtIva'.$valorIva]);
				$txtSubTotalIvaLujo += str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valorIva]);
			} else {
				$txtBaseImponibleIva = str_replace(",","",$frmTotalDcto['txtBaseImpIva'.$valorIva]);
				$txtIva += str_replace(",","",$frmTotalDcto['txtIva'.$valorIva]);
				$txtSubTotalIva += str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valorIva]);
			}
		}
	}
	
	// INSERTA LOS DATOS DE LA NOTA DE CRÉDITO
	$insertSQL = sprintf("INSERT INTO cj_cc_notacredito (numeracion_nota_credito, numeroControl, idCliente, montoNetoNotaCredito, saldoNotaCredito, fechaNotaCredito, id_clave_movimiento, id_empleado_vendedor, observacionesNotaCredito, estadoNotaCredito, idDocumento, tipoDocumento, porcentajeIvaNotaCredito, ivaNotaCredito, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, base_imponible_iva_lujo, porcentajeIvaDeLujoNotaCredito, ivaLujoNotaCredito, idDepartamentoNotaCredito, montoExoneradoCredito, montoExentoCredito, aplicaLibros, baseimponibleNotaCredito, id_empresa, id_orden, estatus_nota_credito, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato("", "text"),
		valTpDato("-", "text"),
		valTpDato($rowFact['idCliente'], "int"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaNotaCredito'])), "date"),
		valTpDato($frmDcto['lstClaveMovimiento'], "int"),
		valTpDato($frmDcto['hddIdEmpleado'], "int"),
		valTpDato($frmTotalDcto['txtObservacionNotaCredito'], "text"),
		valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($rowFact['idFactura'], "int"),
		valTpDato("FA", "text"),
		valTpDato($txtIva, "real_inglesa"),
		valTpDato($txtSubTotalIva, "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($txtBaseImponibleIvaLujo, "real_inglesa"),
		valTpDato($txtIvaLujo, "real_inglesa"),
		valTpDato($txtSubTotalIvaLujo, "real_inglesa"),
		valTpDato(0, "int"), // 0 = Repuesto, 1 = Sevicios, 2 = Autos, 3 = Administracion
		valTpDato($frmTotalDcto['txtTotalExonerado'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato(1, "int"), // 0 = No, 1 = Si
		valTpDato($txtBaseImponibleIva, "real_inglesa"),
		valTpDato($rowFact['id_empresa'], "int"),
		valTpDato("", "int"),
		valTpDato("", "int"), // 1 = Aprobada, 2 = Aplicada
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DEL DOCUMENTO
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			if ($frmListaArticulo['hddIdFactDet'.$valorPieDetalle] > 0) {
				// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
				$queryFacturaDet = sprintf("SELECT * FROM cj_cc_factura_detalle fact_vent_det WHERE fact_vent_det.id_factura_detalle = %s;",
					valTpDato($frmListaArticulo['hddIdFactDet'.$valorPieDetalle], "int"));
				$rsFacturaDet = mysql_query($queryFacturaDet);
				if (!$rsFacturaDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowFacturaDet = mysql_fetch_assoc($rsFacturaDet);
				
				$idArticulo = $rowFacturaDet['id_articulo'];
				$idCasilla = $frmListaArticulo['hddIdCasilla'.$valorPieDetalle];
				
				// CANTIDADES DEL PEDIDO
				$cantFacturada = str_replace(",", "", $frmListaArticulo['txtCantFactItm'.$valorPieDetalle]);
				$cantPedida = doubleval(str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]));
				$cantDevuelta = doubleval(str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]));
				$cantPendiente = $cantPedida - $cantDevuelta;
				$precioUnitario = $rowFacturaDet['precio_unitario'];
				$costoUnitario = $rowFacturaDet['costo_compra'];
				
				// EDITA EL ESTADO DEL DETALLE DE LA FACTURA
				$updateSQL = sprintf("UPDATE cj_cc_factura_detalle SET 
					devuelto = (devuelto + %s)
				WHERE id_factura_detalle = %s;",
					valTpDato($cantDevuelta, "real_inglesa"),
					valTpDato($frmListaArticulo['hddIdFactDet'.$valorPieDetalle], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA EL DETALLE DE LA NOTA DE CREDITO
				$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle (id_nota_credito, id_articulo, cantidad, pendiente, costo_compra, precio_unitario, id_iva, iva)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idNotaCredito, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($cantPedida, "real_inglesa"),
					valTpDato($cantPendiente, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($precioUnitario, "real_inglesa"),
					valTpDato($rowFacturaDet['id_iva'], "int"),
					valTpDato($rowFacturaDet['iva'], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idNotaCreditoDetalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
						$valorIvaItm = explode(":", $valorIvaItm);
						if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
							
							$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_impuesto (id_nota_credito_detalle, id_impuesto, impuesto) 
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
				
				$objResponse->assign("hddIdOrdDet".$valorPieDetalle,"value",$$idNotaCreditoDetalle);
			}
		}
	}
	
	// INSERTA LOS GASTOS DEL PEDIDO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indiceGasto => $valorGasto) {
			$idGasto = $frmTotalDcto['hddIdGasto'.$valorGasto];
			
			$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valorGasto]);
			$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valorGasto]);
			
			// BUSCA LOS DATOS DEL GASTO FACTURA
			$queryGastoFact = sprintf("SELECT 
				fact_vent_gasto.monto
			FROM cj_cc_factura_gasto fact_vent_gasto
			WHERE fact_vent_gasto.id_gasto = %s
				AND fact_vent_gasto.id_factura = %s;",
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
			
			if (round($txtMontoGasto, 2) != 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_gasto (id_nota_credito, id_gasto, tipo, porcentaje_monto, monto)
				SELECT %s, id_gasto, %s, %s, %s FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idNotaCredito, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valorGasto], "int"),
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($idGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$hddIdNotaCreditoGasto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				if ($hddPagaImpuesto == 1) {
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
								
								$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_gasto_impuesto (id_nota_credito_gasto, id_impuesto, impuesto) 
								VALUE (%s, %s, %s);",
									valTpDato($hddIdNotaCreditoGasto, "int"),
									valTpDato($hddIdIvaGasto, "int"),
									valTpDato($hddIvaGasto, "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
					
					$hddIdIvaGasto = ($contIvaGasto == 1) ? $hddIdIvaGasto : "";
					$hddIvaGasto = ($contIvaGasto == 1) ? $hddIvaGasto : 0;
					$hddEstatusIvaGasto = ($contIvaGasto == 1) ? $hddEstatusIvaGasto : "1";
					
					// EDITA EL IMPUESTO
					$updateSQL = sprintf("UPDATE cj_cc_nota_credito_gasto SET
						id_iva = %s,
						iva = %s,
						estatus_iva = %s
					WHERE id_nota_credito_gasto = %s;",
						valTpDato($hddIdIvaGasto, "int"),
						valTpDato($hddIvaGasto, "real_inglesa"),
						valTpDato($hddEstatusIvaGasto, "boolean"),
						valTpDato($hddIdNotaCreditoGasto, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indiceIva => $valorIva) {
			if ($frmTotalDcto['txtSubTotalIva'.$valorIva] > 0) {
				$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_iva (id_nota_credito, base_imponible, subtotal_iva, id_iva, iva, lujo)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idNotaCredito, "int"),
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
	
	// ACTUALIZA EL ESTATUS DEL PEDIDO DE VENTA
	$updateSQL = sprintf("UPDATE iv_pedido_venta SET
		estatus_pedido_venta = %s
	WHERE id_pedido_venta = %s
		AND estatus_pedido_venta IN (3,4)",
		valTpDato(4, "int"), // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelta, 5 = Anulada
		valTpDato($frmDcto['txtIdPedido'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Aprobación de Nota de Crédito Guardada con Éxito");
	
	$objResponse->script(sprintf("window.location.href='iv_devolucion_venta_list.php';"));
	
	return $objResponse;
}

function editarArticulo($frmDatosArticulo, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$hddNumeroArt = $frmDatosArticulo['hddNumeroArt'];
	$idFacturaDet = $frmListaArticulo['hddIdFactDet'.$hddNumeroArt];
	
	$cantPedida = str_replace(",", "", $frmDatosArticulo['txtCantidadArt']);
	$cantDevuelta = str_replace(",", "", $frmDatosArticulo['txtCantidadRecibArt']);
	$cantPendiente = doubleval($cantPedida) - doubleval($cantDevuelta);
	
	if ($cantPendiente >= 0) {
		// BUSCA LOS DATOS DEL DETALLE DE LA FACTURA
		$queryFacturaDet = sprintf("SELECT * FROM cj_cc_factura_detalle fact_vent_det WHERE fact_vent_det.id_factura_detalle = %s;",
			valTpDato($idFacturaDet, "int"));
		$rsFacturaDet = mysql_query($queryFacturaDet);
		if (!$rsFacturaDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowFacturaDet = mysql_fetch_assoc($rsFacturaDet);
		
		$precioUnitario = $rowFacturaDet['precio_unitario'];
		
		$objResponse->assign("txtCantFactItm".$hddNumeroArt,"value",number_format($cantPedida, 2, ".", ","));
		$objResponse->assign("txtCantItm".$hddNumeroArt,"value",number_format($cantDevuelta, 2, ".", ","));
		$objResponse->assign("tdCantPendienteItm:".$hddNumeroArt,"innerHTML",number_format($cantPendiente, 2, ".", ","));
		$objResponse->assign("txtTotalItm".$hddNumeroArt,"value",number_format($cantDevuelta * $precioUnitario, 2, ".", ","));
		
		$objResponse->script("
		byId('btnCancelarArticulo').click();");
		
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'))");
	} else {
		$objResponse->script("
		byId('txtCantidadRecibArt').focus();
		byId('txtCantidadRecibArt').select();");
	
		$objResponse->alert("No puede devolver una cantidad mayor a la facturada");
	}
	
	return $objResponse;
}

function eliminarArticulo($frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItmDetalle_%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	} else {
		$objResponse->alert("Debe seleccionar registro(s) para poder eliminar(los)");
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieDetalle = $frmListaArticulo['cbxPieDetalle'];
	if (isset($arrayObjPieDetalle)) {
		$i = 0;
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmDetalle_".$valorPieDetalle,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItmDetalle_".$valorPieDetalle,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObjPieDetalle) > 0) ? implode("|",$arrayObjPieDetalle) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	if (isset($arrayObjGasto)) {
		$i = 0;
		foreach ($arrayObjGasto as $indiceGasto => $valorGasto) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmGasto:".$valorGasto, "className", $clase." textoGris_11px");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indiceIva => $valorIva) {
			$objResponse->script("
			fila = document.getElementById('trIva:".$valorIva."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdNotaCredito'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	$hddPagaImpuesto = ($frmDcto['hddPagaImpuesto'] != "") ? $frmDcto['hddPagaImpuesto'] : 1;
	
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
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]);
			$txtPrecioItm = str_replace(",", "", $frmListaArticulo['txtPrecioItm'.$valorPieDetalle]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valorPieDetalle]);
			$txtTotalItm = $txtCantItm * $txtPrecioItm;
			
			$txtSubTotal += $txtTotalItm;
			$subTotalDescuentoItm += $txtCantItm * $hddMontoDescuentoItm;
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1 || isset($frmDcto['txtIdNotaCredito'])) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IMPUESTO Y EL SUBTOTAL
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$arrayIva = NULL;
	if (isset($arrayObjPieDetalle)) {
		foreach ($arrayObjPieDetalle as $indicePieDetalle => $valorPieDetalle) {
			$txtCantItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valorPieDetalle]);
			$txtTotalItm = str_replace(",","",$frmListaArticulo['txtTotalItm'.$valorPieDetalle]);
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valorPieDetalle]);
			
			$hddTotalDescuentoItm = ($hddTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indiceIvaItm => $valorIvaItm) {
					$valorIvaItm = explode(":", $valorIvaItm);
					
					if ($valorIvaItm[0] == $valorPieDetalle && $hddPagaImpuesto == 1) {
						$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
						
						$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]]] = $valorIvaItm[1];
						$arrayIdIvaItm[] = ($hddIdIvaItm > 0) ? $hddIdIvaItm : -1;
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$valorIvaItm[1]];
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
				$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valorPieDetalle.':'.$arrayPosIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if (($estatusIva == 0 && $rowIva['tipo'] == 6 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) || $hddPagaImpuesto == 0) {
					$txtTotalExento += $txtTotalNetoItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valorPieDetalle.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
			
			$objResponse->assign("txtTotalItm".$valorPieDetalle, "value", number_format($txtTotalItm, 2, ".", ","));
			
			$subTotalDescuentoItm += $txtCantItm * str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valorPieDetalle]);
		}
	}
	
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IMPUESTO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indiceGasto => $valorGasto) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s
				AND id_modo_gasto IN (1);", 
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
				$arrayEstatusIvaItm = array(-1);
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indiceIvaGasto => $valorIvaGasto) {
						$valorIvaGasto = explode(":", $valorIvaGasto);
						
						if ($valorIvaGasto[0] == $valorGasto) {
							$arrayPosIvaItm[$frmTotalDcto['hddIdIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]]] = $valorIvaGasto[1];
							$arrayIdIvaItm[] = $frmTotalDcto['hddIdIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
							$arrayIvaItm[] = $frmTotalDcto['hddIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
							$arrayEstatusIvaItm[] = $frmTotalDcto['hddEstatusIvaGasto'.$valorGasto.':'.$valorIvaGasto[1]];
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
					$estatusIva = ($incluirIvaMonedaLocal == 1) ? $frmTotalDcto['hddEstatusIvaGasto'.$valorGasto.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if (($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) || $hddPagaImpuesto == 0) {
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosSinIva += $txtMontoGasto; break;
							default : $gastosNoAfecta += $txtMontoGasto; break;
						}
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valorGasto.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
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
						case 1 : $txtGastosConIva += $txtMontoGasto; break;
						default : $gastosNoAfecta += $txtMontoGasto; break;
					}
				} else if (!($totalRowsIva > 0)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosSinIva += $txtMontoGasto; break;
						default : $gastosNoAfecta += $txtMontoGasto; break;
					}
				}
			}
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
	$txtTotalOrden += round(doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva), 2);
	
	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));	
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign('txtGastosConIva', "value", number_format($txtGastosConIva, 2, ".", ","));
	$objResponse->assign('txtGastosSinIva', "value", number_format($txtGastosSinIva, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("tdSubTotalMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdDescuentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdGastoConIvaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdGastoSinIvaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalRegistroMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalFacturaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExoneradoMoneda", "innerHTML", $abrevMonedaLocal);
	
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
			
			$selected = "";
			if ($selId == $rowClaveMov['id_clave_movimiento']) {
				$selected = "selected=\"selected\"";
				
				$objResponse->loadCommands(bloquearLstClaveMovimiento($rowClaveMov['id_clave_movimiento']));
			}
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"editarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
?>