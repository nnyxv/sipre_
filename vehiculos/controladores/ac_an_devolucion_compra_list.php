<?php


function asignarProveedor($idProveedor, $asigDescuento = true) {
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
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowProv['nombre']));
	$objResponse->assign("txtRifProv","value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccionProv","innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContactoProv","value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContactoProv","value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonoProv","value",utf8_encode($rowProv['telefono']));
	
	if ($asigDescuento == true) {
		$objResponse->assign("txtDescuento","value",number_format($rowProv['descuento'], 2, ".", ","));
	}
	
	$objResponse->script("
	byId('btnCancelarLista').click();");
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['lstModoCompra'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaRegistroCompra(0, "id_factura", "DESC", $valBusq));
		
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
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
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

function formNotaCredito($idFacturaCompra) {
	$objResponse = new xajaxResponse();
	
	// BUSCA LOS DATOS DE LA FACTURA DE COMPRA
	$queryFactura = sprintf("SELECT cxp_fact.*,
		
		(CASE kardex.tipoMovimiento
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento,
		kardex.claveKardex AS id_clave_movimiento,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva cxp_fact_iva
					WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total
	FROM an_kardex kardex
		INNER JOIN cp_factura cxp_fact ON (kardex.id_documento = cxp_fact.id_factura)
		INNER JOIN pg_clave_movimiento clave_mov ON (kardex.claveKardex = clave_mov.id_clave_movimiento)
	WHERE id_factura = %s
		AND kardex.tipoMovimiento IN (1);",
		valTpDato($idFacturaCompra, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	
	$objResponse->assign("txtFechaRegistroNotaCredito","value",date(spanDateFormat));
	$objResponse->assign("txtIdFactura","value",$idFacturaCompra);
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimientoNotaCredito",$rowFactura['id_modulo'],"4","","3"));
	$objResponse->call("selectedOption","lstAplicaLibro",$rowFactura['aplica_libros']);
	$objResponse->call("asignarAplicaLibro",$rowFactura['aplica_libros']);
	$objResponse->assign("txtTotalOrden","value",number_format($rowFactura['total'], 2, ".", ","));
	
	$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("txtNumeroControlFactura","value",$rowFactura['numero_control_factura']);
	$objResponse->assign("txtFechaRegistroFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_origen'])));
	$objResponse->assign("txtFechaProveedorFactura","value",date(spanDateFormat,strtotime($rowFactura['fecha_factura_proveedor'])));
	$objResponse->assign("txtTipoClaveFactura","value",utf8_encode($rowFactura['tipo_movimiento']));
	$objResponse->assign("hddIdClaveMovimiento","value",$rowFactura['id_clave_movimiento']);
	$objResponse->assign("txtClaveMovimiento","value",utf8_encode($rowFactura['descripcion_clave_movimiento']));
	$objResponse->assign("txtTotalFacturaCompra","value",number_format($rowFactura['total'], 2, ".", ","));
	
	$objResponse->loadCommands(asignarEmpresaUsuario($rowFactura['id_empresa'], "Empresa", "ListaEmpresa"));
	$objResponse->loadCommands(asignarProveedor($rowFactura['id_proveedor'],false));
	
	/*$queryFacturaDet = sprintf("SELECT
		art.id_articulo,
		cxp_fact_det.cantidad,
		cxp_fact_det.devuelto,
		art.id_arancel_familia,
		art.codigo_articulo,
		art.descripcion,
		vw_iv_ped_comp.id_pedido_compra_referencia,
		cxp_fact_det.precio_unitario,
		cxp_fact_det.id_iva,
		cxp_fact_det.iva
	FROM cp_factura_detalle cxp_fact_det
		INNER JOIN iv_articulos art ON (cxp_fact_det.id_articulo = art.id_articulo)
		INNER JOIN vw_iv_pedidos_compra vw_iv_ped_comp ON (cxp_fact_det.id_pedido_compra = vw_iv_ped_comp.id_pedido_compra)
	WHERE cxp_fact_det.id_factura = %s
		AND (cxp_fact_det.cantidad - cxp_fact_det.devuelto) > 0;",
		valTpDato($idFacturaCompra, "int"));
	$rsFacturaDet = mysql_query($queryFacturaDet);
	if (!$rsFacturaDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$html .= "<tr align=\"center\" class=\"tituloColumna\">";
		$html .= "<td>"."<input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/>"."</td>";
		$html .= "<td width=\"4%\">"."Nro."."</td>";
		$html .= "<td></td>";
		$html .= "<td></td>";
		$html .= "<td width=\"10%\">Ubic.</td>";
		$html .= "<td width=\"14%\">Código</td>";
		$html .= "<td width=\"27%\">Descripción</td>";
		$html .= "<td width=\"4%\">Cant.</td>";
		$html .= "<td width=\"4%\">Dev.</td>";
		$html .= "<td width=\"4%\">Pend.</td>";
		$html .= "<td width=\"9%\">Nro. Ref.</td>";
		$html .= "<td width=\"8%\">Costo Unit.</td>";
		$html .= "<td width=\"4%\">% Impuesto</td>";
		$html .= "<td width=\"4%\">% ADV</td>";
		$html .= "<td width=\"8%\">Total</td>";
	$html .= "</tr>";
	$contFila = 0;
	while ($rowFacturaDet = mysql_fetch_assoc($rsFacturaDet)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$idArticulo = $rowFacturaDet['id_articulo'];
		$cantFacturadaDisponible = $rowFacturaDet['cantidad'] - $rowFacturaDet['devuelto'];
		$cantDevolver = $rowFacturaDet['cantidad'] - $rowFacturaDet['devuelto'];
		$cantPendiente = doubleval($cantFacturadaDisponible) - doubleval($cantDevolver);
		
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
			valTpDato($rowFacturaDet['id_arancel_familia'], "int"));
		$rsArancelFamilia = mysql_query($queryArancelFamilia);
		if (!$rsArancelFamilia) return array(false, mysql_error()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
	
		$arancelArticulo = sprintf("<span class=\"textoNegroNegrita_10px\" title=\"%s\">%s</span>",
			utf8_encode($rowArancelFamilia['descripcion_arancel']),
			utf8_encode($rowArancelFamilia['codigo_arancel']));
		
		$html .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$html .= sprintf("<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
					"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>",
				$contFila, $contFila,
					$contFila);
			$html .= sprintf("<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>",
				$contFila, $contFila);
			$html .= sprintf("<td><a class=\"modalImg\" id=\"aEditarItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a></td>",
				$contFila);
			$html .= "<td></td>";
			$html .= sprintf("<td class=\"%s texto_9px\"><table><tr><td>".
					"<a class=\"modalImg\" id=\"aAlmacenItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_transferir_para_almacen.gif\" title=\"Ubicación\"/>".
					"</td><td id=\"spanUbicacion:%s\" align=\"center\" nowrap=\"nowrap\" width=\"%s\" title=\"spanUbicacion:%s\">%s</td></tr></table></td>",
				$claseAlmacen,
					$contFila,
					$contFila, $contFila, "100%", preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))));
			$html .= sprintf("<td id=\"tdCodArt:%s\">%s</td>",
				$contFila, elimCaracter($rowFacturaDet['codigo_articulo'],";"));
			$html .= sprintf("<td><div id=\"tdDescArt:%s\">%s</div>%s</td>",
				$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($rowFacturaDet['descripcion']))), $arancelArticulo);
			$html .= sprintf("<td><input type=\"text\" id=\"hddCantArt%s\" name=\"hddCantArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>",
				$contFila, $contFila, number_format($cantFacturadaDisponible, 2, ".", ","));
			$html .= sprintf("<td><input type=\"text\" id=\"hddCantRecibArt%s\" name=\"hddCantRecibArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>",
				$contFila, $contFila, number_format($cantDevolver, 2, ".", ","));
			$html .= sprintf("<td id=\"tdCantPend:%s\" align=\"right\">%s</td>",
				$contFila, number_format(($cantFacturadaDisponible - $cantDevolver), 2, ".", ","));
			$html .= sprintf("<td>%s</td>",
				utf8_encode($rowFacturaDet['id_pedido_compra_referencia']));
			$html .= sprintf("<td><input type=\"text\" id=\"hddCostoArt%s\" name=\"hddCostoArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddMontoDescuentoArt%s\" name=\"hddMontoDescuentoArt%s\" value=\"%s\"/></td>",
				$contFila, $contFila, number_format($rowFacturaDet['precio_unitario'], 2, ".", ","),
					$contFila, $contFila, number_format(0, 2, ".", ","));
			$html .= sprintf("<td><input type=\"text\" id=\"hddIvaArt%s\" name=\"hddIvaArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdIvaArt%s\" name=\"hddIdIvaArt%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddEstatusIvaArt%s\" name=\"hddEstatusIvaArt%s\" value=\"%s\"></td>",
				$contFila, $contFila, $rowFacturaDet['iva'],
					$contFila, $contFila, $rowFacturaDet['id_iva'],
					$contFila, $contFila, $hddEstatusIvaArt);
			$html .= sprintf("<td>%s".
					"<input type=\"hidden\" id=\"hddIdArancelFamilia%s\" name=\"hddIdArancelFamilia%s\" class=\"inputSinFondo\" value=\"%s\"></td>",
				$rowArancelFamilia['porcentaje_grupo'],
					$contFila, $contFila, $rowArancelFamilia['id_arancel_familia']);
			$html .= sprintf("<td><input type=\"text\" id=\"hddTotalArt%s\" name=\"hddTotalArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
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
					"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/></td>",
				$contFila, $contFila, number_format(($cantDevolver * $rowFacturaDet['precio_unitario']), 2, ".", ","),
					$contFila, $contFila, $idArticulo,
					$contFila, $contFila, "",
					$contFila, $contFila, 0,
					$contFila, $contFila, number_format(0, 2, ".", ","),
					$contFila, $contFila, number_format(0, 2, ".", ","),
					$contFila, $contFila, number_format($cantDevolver * $rowPedidoDet['gasto_unitario'], 2, ".", ","),
					$contFila, $contFila, number_format(0, 2, ".", ","),
					$contFila, $contFila, number_format(0, 2, ".", ","),
					$contFila, $contFila, $hddTipoArt,
					$contFila, $contFila, $idClienteArt,
					$contFila, $contFila, $idPedidoCompraDetalle,
					$contFila, $contFila, $idFacturaCompraDetalle,
					$contFila, $contFila, $idCasilla,
					$contFila, $contFila, $lujoIva);
		$html .= "</tr>";
	}
	$html .= "</table>";
	$objResponse->assign("tdListaArticulosFactura","innerHTML",$html);*/
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Devolución de Compra (Nro. Factura: ".$rowFactura['numero_factura_proveedor'].")");
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaFacturaCompra) {
	$objResponse = new xajaxResponse();
	
	$idFacturaCompra = $frmDcto['txtIdFactura'];
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DE LA FACTURA A DEVOLVER
	$queryFactura = sprintf("SELECT * FROM cp_factura cxp_fact
	WHERE cxp_fact.id_factura = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	$idModoCompra = $rowFactura['id_modo_compra'];
	$idModulo = $rowFactura['id_modulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idClaveMovimiento = $frmDcto['lstClaveMovimientoNotaCredito'];
	
	// VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto)
	$queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig12 = mysql_query($queryConfig12);
	if (!$rsConfig12) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig12 = mysql_num_rows($rsConfig12);
	$rowConfig12 = mysql_fetch_assoc($rsConfig12);
	
	if ($frmDcto['lstActivo'] == "" && str_replace(",", "", $frmDcto['txtTotalFacturaCompra']) != str_replace(",", "", $frmDcto['txtTotalOrden'])) {
		errorGuardarDcto($objResponse); 
		return $objResponse->alert("Para anular el registro de compra, la devolucion debe tener el mismo monto");
	}
	
	if (str_replace(",", "", $frmDcto['txtTotalOrden']) > str_replace(",", "", $frmDcto['txtTotalFacturaCompra'])) {
		errorGuardarDcto($objResponse); 
		return $objResponse->alert("La devolución no puede tener un monto mayor al del registro de compra");
	}
	
	if ($frmDcto['lstAplicaLibro'] == 0) {
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
		
		$frmDcto['txtNumeroControl'] = $numeroActual;
	} else {
		$numeroActual = $frmDcto['txtNumeroNotaCredito'];
	}
	
	// GUARDA LOS DATOS DE DEVOLUCION
	$insertSQL = sprintf("INSERT INTO cp_notacredito (id_empresa, numero_nota_credito, numero_control_notacredito, fecha_notacredito, fecha_registro_notacredito, id_proveedor, id_departamento_notacredito, id_documento, tipo_documento, estado_notacredito, observacion_notacredito, monto_exento_notacredito, monto_exonerado_notacredito, subtotal_notacredito, subtotal_descuento, total_cuenta_pagar, saldo_notacredito, aplica_libros_notacredito, id_empleado_creador)
	SELECT 
		id_empresa,
		%s,
		%s,
		%s,
		%s,
		id_proveedor,
		id_modulo,
		id_factura,
		'FA',
		%s,
		%s,
		monto_exento,
		monto_exonerado,
		subtotal_factura,
		subtotal_descuento,
		total_cuenta_pagar,
		%s,
		%s,
		%s
	FROM cp_factura
	WHERE id_factura = %s;",
		valTpDato($numeroActual, "text"),
		valTpDato($frmDcto['txtNumeroControl'], "text"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaProveedor'])), "date"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaRegistroNotaCredito'])), "date"),
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
		valTpDato($frmDcto['txtObservacionNotaCredito'], "text"),
		valTpDato($frmDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmDcto['lstAplicaLibro'], "boolean"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idNotaCredito,
		$idModulo,
		"NOTA CREDITO CXP");
	
	// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
	$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
	SELECT 
		%s,
		kardex.idUnidadBasica,
		kardex.idUnidadFisica,
		%s,
		%s,
		%s,
		kardex.cantidad,
		kardex.precio,
		kardex.costo,
		kardex.costo_cargo,
		kardex.porcentaje_descuento,
		kardex.subtotal_descuento,
		%s,
		NOW()
	FROM an_kardex kardex
	WHERE kardex.id_documento = %s
		AND kardex.tipoMovimiento IN (1);",
		valTpDato($idNotaCredito, "int"),
		valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// BUSCA LOS KARDEX DEL DOCUMENTO
	$query = sprintf("SELECT * FROM an_kardex
	WHERE id_documento = %s
		AND tipoMovimiento IN (%s)
		AND claveKardex = %s
		AND tipo_documento_movimiento = %s;",
		valTpDato($idNotaCredito, "int"),
		valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(2, "int")); // 1 = Vale Entrada / Salida, 2 = Nota Credito
	$rs = mysql_query($query);
	if (!$rs) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($row = mysql_fetch_assoc($rs)) {
		$idUnidadFisica = $row['idUnidadFisica'];
		$idAlmacen = $row['id_almacen'];
		$costoUnitario = $row['costo'];
		$precioUnitario = $row['precio'];
		$costoCargo = $row['costo_cargo'];
		$porcentajeDescuento = $row['porcentaje_descuento'];
		$subtotalDescuento = $row['subtotal_descuento'];
		
		// VERIFICA QUE LA UNIDAD FISICA ESTE DISPONIBLE PARA DEVOLVERLA
		$queryUnidadFisica = sprintf("SELECT * FROM an_unidad_fisica
		WHERE id_unidad_fisica = %s
			AND estado_venta IN ('DISPONIBLE');",
			valTpDato($idUnidadFisica, "int"));
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsUnidadFisica = mysql_num_rows($rsUnidadFisica);
		$rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica);
		
		if ($totalRowsUnidadFisica > 0) {
			// MODIFICA EL MOVIMIENTO DEL ARTICULO
			$updateSQL = sprintf("UPDATE an_unidad_fisica SET
				estado_venta = 'DEVUELTO',
				estatus = NULL
			WHERE id_unidad_fisica = %s;",
				valTpDato($idUnidadFisica, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else {
			$contSinDisponibilidad++;
			
			$msjArticulo .= ($msjArticulo != "") ? "": "El(Los) registro(s):\n";
			$msjArticulo .= ($contSinDisponibilidad % 4 == 1) ? "\n" : "";
			
			$msjArticulo .= str_pad("(".elimCaracter($rowUnidadFisica['id_unidad_fisica'],";").")", 30, " ", STR_PAD_RIGHT);
		}
	}
	
	if ($contSinDisponibilidad > 0) {
		$msjArticulo .= "\n\nno posee(n) disponible la cantidad suficiente";
		errorGuardarDcto($objResponse); 
		return $objResponse->alert(utf8_encode($msjArticulo));
	}
	
	// INSERTA LOS GASTOS DEL PEDIDO
	$insertSQL = sprintf("INSERT INTO cp_notacredito_gastos (id_notacredito, id_gastos_notacredito, tipo_gasto_notacredito, porcentaje_monto, monto_gasto_notacredito, estatus_iva_notacredito, id_iva_notacredito, iva_notacredito, id_modo_gasto, afecta_documento, id_factura_compra_cargo, id_condicion_gasto)
	SELECT 
		%s,
		id_gasto,
		tipo,
		porcentaje_monto,
		monto,
		estatus_iva,
		id_iva,
		iva,
		id_modo_gasto,
		afecta_documento,
		id_factura_compra_cargo,
		id_condicion_gasto
	FROM cp_factura_gasto
	WHERE id_factura = %s;",
		valTpDato($idNotaCredito, "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	$insertSQL = sprintf("INSERT INTO cp_notacredito_iva (id_notacredito, baseimponible_notacredito, subtotal_iva_notacredito, id_iva_notacredito, iva_notacredito, lujo)
	SELECT 
		%s,
		base_imponible,
		subtotal_iva,
		id_iva,
		iva,
		lujo
	FROM cp_factura_iva
	WHERE id_factura = %s;",
		valTpDato($idNotaCredito, "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaRegistroNotaCredito'])), "date"),
		valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// CREACION DE LA RETENCION DEL IMPUESTO
	// BUSCA EL COMPROBANTE DE RETENCION DE LA FACTURA DE COMPRA A DEVOLVER
	$queryRetencionDetalle = sprintf("SELECT
		retencion.idRetencionCabezera,
		retencion.numeroComprobante,
		retencion.fechaComprobante,
		SUM(retencion_det.IvaRetenido) AS IvaRetenido
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
	WHERE retencion_det.idFactura = %s
	GROUP BY retencion_det.idFactura;",
		valTpDato($idFacturaCompra, "int"));
	$rsRetencionDetalle = mysql_query($queryRetencionDetalle);
	if (!$rsRetencionDetalle) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsRetencionDetalle = mysql_num_rows($rsRetencionDetalle);
	$rowRetencionDetalle = mysql_fetch_assoc($rsRetencionDetalle);
	
	// VERIFICA QUE LA DEVOLUCION PERTENEZCA AL MISMO PERIODO FISCAL DE LA FACTURA DE COMPRA
	if ($totalRowsRetencionDetalle > 0
	&& ((date("d", strtotime($rowRetencionDetalle['fechaComprobante'])) <= 15 && date("d", strtotime($frmDcto['txtFechaRegistroNotaCredito'])) <= 15)
		|| (date("d", strtotime($rowRetencionDetalle['fechaComprobante'])) > 15 && date("d", strtotime($frmDcto['txtFechaRegistroNotaCredito'])) > 15))
	&& date(str_replace("d","01",spanDateFormat), strtotime($rowRetencionDetalle['fechaComprobante'])) == date(str_replace("d","01",spanDateFormat), strtotime($frmDcto['txtFechaRegistroNotaCredito']))) {
		$idRetencionCabezera = $rowRetencionDetalle['idRetencionCabezera'];
		$ivaRetenido = $rowRetencionDetalle['IvaRetenido'];
		
		// INSERTA EL DETALLE DE LA RETENCION
		$insertSQL = sprintf("INSERT INTO cp_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, id_nota_cargo, id_nota_credito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
		SELECT
			retencion.idRetencionCabezera,
			%s,
			retencion_det.idFactura,
			retencion_det.numeroControlFactura,
			NULL,
			%s,
			%s,
			retencion_det.idFactura,
			(-1) * retencion_det.totalCompraIncluyendoIva,
			(-1) * retencion_det.comprasSinIva,
			(-1) * retencion_det.baseImponible,
			(-1) * retencion_det.porcentajeAlicuota,
			(-1) * retencion_det.impuestoIva,
			(-1) * retencion_det.IvaRetenido,
			(-1) * retencion_det.porcentajeRetencion
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = %s;",
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaProveedor'])), "date"),
			valTpDato($idNotaCredito, "int"),
			valTpDato("03", "text"), // 01 = FACTURA, 02 = NOTA DEBITO, 03 = NOTA CREDITO
			valTpDato($idFacturaCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA EL PAGO DEBIDO A LA RETENCION
		$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idNotaCredito, "int"),
			valTpDato("NC", "text"),
			valTpDato("RETENCION", "text"),
			valTpDato($idRetencionCabezera, "int"),
			valTpDato(date("Y-m-d", strtotime($frmDcto['txtFechaRegistroNotaCredito'])), "date"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($rowRetencionDetalle['numeroComprobante'], "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato("-", "text"),
			valTpDato($ivaRetenido, "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	switch ($idModoCompra) {
		case 1 : // 1 = Nacional
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
			$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
				cxp_nc.saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
									- IFNULL(cxp_nc.subtotal_descuento, 0)
									+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
											FROM cp_notacredito_gastos cxp_nc_gasto
											WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
												AND cxp_nc_gasto.id_modo_gasto IN (1,3)
												AND cxp_nc_gasto.afecta_documento IN (1)), 0)
									+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
											FROM cp_notacredito_iva cxp_nc_iva
											WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
									- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = cxp_nc.id_notacredito)
													OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = cxp_nc.id_notacredito))
												AND pago_dcto.estatus = 1), 0))
			WHERE cxp_nc.id_notacredito = %s
				AND cxp_nc.estado_notacredito IN (1,2);",
				valTpDato($idNotaCredito, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			break;
		case 2 : // 2 = Importacion
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
			$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
				cxp_nc.saldo_notacredito = (IFNULL((SELECT 
													SUM(1 * b.costo_unitario * cxp_fact_imp.tasa_cambio)
												FROM cp_factura_detalle_unidad a
													INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
													INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
												WHERE a.id_factura = %s), 0)
										+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
												FROM cp_factura_gasto cxp_fact_gasto
												WHERE cxp_fact_gasto.id_modo_gasto IN (1)
													AND cxp_fact_gasto.afecta_documento IN (1)
													AND cxp_fact_gasto.id_factura = %s), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = cxp_nc.id_notacredito)
														OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = cxp_nc.id_notacredito))
													AND pago_dcto.estatus = 1), 0))
			WHERE cxp_nc.estado_notacredito IN (1,2)
				AND cxp_nc.id_notacredito = %s
				AND (SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
					WHERE cxp_fact_det_unidad.id_factura = %s) > 0;",
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idNotaCredito, "int"),
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			break;
	}
	
	// BUSCA LOS DATOS DE LA NOTA DE CREDITO
	$queryNotaCredito = sprintf("SELECT * FROM cp_notacredito cxp_nc WHERE cxp_nc.id_notacredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$rsNotaCredito = mysql_query($queryNotaCredito);
	if (!$rsNotaCredito) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsNotaCredito = mysql_num_rows($rsNotaCredito);
	$rowNotaCredito = mysql_fetch_assoc($rsNotaCredito);
	
	if ($rowFactura['estatus_factura'] == 0 || $rowFactura['estatus_factura'] == 2) { // 0 = No Cancelado, 2 = Parcialmente Cancelado
		if (doubleval($rowFactura['saldo_factura']) >= doubleval($rowNotaCredito['saldo_notacredito'])) {
			// INSERTA EL PAGO DEBIDO A LA NOTA DE CREDITO
			$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFacturaCompra, "int"),
				valTpDato("FA", "text"),
				valTpDato("NC", "text"),
				valTpDato($idNotaCredito, "int"),
				valTpDato("NOW()", "campo"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowNotaCredito['numero_nota_credito'], "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato($rowNotaCredito['saldo_notacredito'], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else if (doubleval($rowFactura['saldo_factura']) < doubleval($rowNotaCredito['saldo_notacredito'])) {
			// INSERTA EL PAGO DEBIDO A LA NOTA DE CREDITO
			$insertSQL = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, id_empleado_creador, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idFacturaCompra, "int"),
				valTpDato("FA", "text"),
				valTpDato("NC", "text"),
				valTpDato($idNotaCredito, "int"),
				valTpDato("NOW()", "campo"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($rowNotaCredito['numero_nota_credito'], "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato("-", "text"),
				valTpDato($rowFactura['saldo_factura'], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	switch ($idModoCompra) {
		case 1 : // 1 = Nacional
			// ACTUALIZA EL SALDO DE LA FACTURA
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				cxp_fact.saldo_factura = (IFNULL(cxp_fact.subtotal_factura, 0)
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
			WHERE cxp_fact.id_factura = %s
				AND cxp_fact.estatus_factura NOT IN (1);",
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
			// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				cxp_fact.estatus_factura = (CASE
									WHEN (ROUND(saldo_factura, 2) = 0 OR ROUND(saldo_factura, 2) < 0) THEN
										1
									WHEN (ROUND(saldo_factura, 2) > 0
										AND ROUND(saldo_factura, 2) < (IFNULL(cxp_fact.subtotal_factura, 0)
																		- IFNULL(cxp_fact.subtotal_descuento, 0)
																		+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
																				FROM cp_factura_gasto cxp_fact_gasto
																				WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
																					AND cxp_fact_gasto.id_modo_gasto IN (1,3)
																					AND cxp_fact_gasto.afecta_documento IN (1)), 0)
																		+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
																				FROM cp_factura_iva cxp_fact_iva
																				WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0))) THEN
										2
									ELSE
										0
								END)
			WHERE cxp_fact.id_factura = %s;",
				valTpDato($idFacturaCompra, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
			$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
				cxp_nc.saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
									- IFNULL(cxp_nc.subtotal_descuento, 0)
									+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
											FROM cp_notacredito_gastos cxp_nc_gasto
											WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
												AND cxp_nc_gasto.id_modo_gasto IN (1,3)
												AND cxp_nc_gasto.afecta_documento IN (1)), 0)
									+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
											FROM cp_notacredito_iva cxp_nc_iva
											WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
									- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = cxp_nc.id_notacredito)
													OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = cxp_nc.id_notacredito))
												AND pago_dcto.estatus = 1), 0))
			WHERE cxp_nc.id_notacredito = %s
				AND cxp_nc.estado_notacredito IN (1,2);",
				valTpDato($idNotaCredito, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
			// ACTUALIZA EL ESTADO DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
			$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
				cxp_nc.estado_notacredito = (CASE
										WHEN (saldo_notacredito = 0) THEN
											3
										WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL(cxp_nc.subtotal_notacredito, 0)
											- IFNULL(cxp_nc.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
													FROM cp_notacredito_gastos cxp_nc_gasto
													WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
														AND cxp_nc_gasto.id_modo_gasto IN (1,3)
														AND cxp_nc_gasto.afecta_documento IN (1)), 0)
											+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
													FROM cp_notacredito_iva cxp_nc_iva
													WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = cxp_nc.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = cxp_nc.id_notacredito))
														AND pago_dcto.estatus = 1), 0))) THEN
											2
										WHEN (saldo_notacredito = (IFNULL(cxp_nc.subtotal_notacredito, 0)
											- IFNULL(cxp_nc.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
													FROM cp_notacredito_gastos cxp_nc_gasto
													WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
														AND cxp_nc_gasto.id_modo_gasto IN (1,3)
														AND cxp_nc_gasto.afecta_documento IN (1)), 0)
											+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
													FROM cp_notacredito_iva cxp_nc_iva
													WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = cxp_nc.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = cxp_nc.id_notacredito))
														AND pago_dcto.estatus = 1), 0))) THEN
											1
										ELSE
											0
									END)
			WHERE cxp_nc.id_notacredito = %s;",
				valTpDato($idNotaCredito, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			break;
		case 2 : // 2 = Importacion
			// ACTUALIZA EL SALDO DE LA FACTURA DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				cxp_fact.saldo_factura = (IFNULL((SELECT 
														SUM(1 * b.costo_unitario * cxp_fact_imp.tasa_cambio)
													FROM cp_factura_detalle_unidad a
														INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
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
														AND pago_dcto.estatus = 1), 0))
			WHERE cxp_fact.id_modo_compra IN (2)
				AND cxp_fact.estatus_factura IN (0,2)
				AND cxp_fact.id_factura = %s
				AND (SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
					WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura) > 0;",
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
				cxp_fact.estatus_factura = (CASE
									WHEN (ROUND(saldo_factura, 2) = 0 OR ROUND(saldo_factura, 2) < 0) THEN
										1
									WHEN (ROUND(saldo_factura, 2) > 0
										AND ROUND(saldo_factura, 2) < (IFNULL((SELECT 
															SUM(1 * b.costo_unitario * cxp_fact_imp.tasa_cambio)
														FROM cp_factura_detalle_unidad a
															INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
															INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
														WHERE a.id_factura = cxp_fact.id_factura), 0)
												+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
														FROM cp_factura_gasto cxp_fact_gasto
														WHERE cxp_fact_gasto.id_modo_gasto IN (1)
															AND cxp_fact_gasto.afecta_documento IN (1)
															AND cxp_fact_gasto.id_factura = cxp_fact.id_factura), 0))) THEN
										2
									ELSE
										0
								END)
			WHERE cxp_fact.id_factura = %s;",
				valTpDato($idFacturaCompra, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
			$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
				cxp_nc.saldo_notacredito = (IFNULL((SELECT 
														SUM(1 * b.costo_unitario * cxp_fact_imp.tasa_cambio)
													FROM cp_factura_detalle_unidad a
														INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = %s), 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
													FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_modo_gasto IN (1)
														AND cxp_fact_gasto.afecta_documento IN (1)
														AND cxp_fact_gasto.id_factura = %s), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = cxp_nc.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = cxp_nc.id_notacredito))
														AND pago_dcto.estatus = 1), 0))
			WHERE cxp_nc.estado_notacredito IN (1,2)
				AND cxp_nc.id_notacredito = %s
				AND (SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
					WHERE cxp_fact_det_unidad.id_factura = %s) > 0;",
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idNotaCredito, "int"),
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
			// ACTUALIZA EL ESTADO DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
			$updateSQL = sprintf("UPDATE cp_notacredito cxp_nc SET
				cxp_nc.estado_notacredito = (CASE
										WHEN (saldo_notacredito = 0) THEN
											3
										WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL((SELECT 
														SUM(1 * b.costo_unitario * cxp_fact_imp.tasa_cambio)
													FROM cp_factura_detalle_unidad a
														INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = cxp_nc.id_documento), 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
													FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_modo_gasto IN (1)
														AND cxp_fact_gasto.afecta_documento IN (1)
														AND cxp_fact_gasto.id_factura = cxp_nc.id_documento), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = cxp_nc.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = cxp_nc.id_notacredito))
														AND pago_dcto.estatus = 1), 0))) THEN
											2
										WHEN (saldo_notacredito = (IFNULL((SELECT 
														SUM(1 * b.costo_unitario * cxp_fact_imp.tasa_cambio)
													FROM cp_factura_detalle_unidad a
														INNER JOIN cp_factura_detalle_unidad_importacion b ON (b.id_factura_detalle_unidad = a.id_factura_detalle_unidad)
														INNER JOIN cp_factura_importacion cxp_fact_imp ON (a.id_factura = cxp_fact_imp.id_factura)
													WHERE a.id_factura = cxp_nc.id_documento), 0)
											+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
													FROM cp_factura_gasto cxp_fact_gasto
													WHERE cxp_fact_gasto.id_modo_gasto IN (1)
														AND cxp_fact_gasto.afecta_documento IN (1)
														AND cxp_fact_gasto.id_factura = cxp_nc.id_documento), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = cxp_nc.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = cxp_nc.id_notacredito))
														AND pago_dcto.estatus = 1), 0))) THEN
											1
										ELSE
											0
									END)
			WHERE cxp_nc.id_notacredito = %s;",
				valTpDato($idNotaCredito, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			break;
	}
	
	// ACTUALIZA EL ESTATUS DE LA FACTURA DE COMPRA
	$updateSQL = sprintf("UPDATE cp_factura SET
		aplica_libros = %s,
		activa = %s
	WHERE id_factura = %s;",
		valTpDato($frmDcto['lstAplicaLibro'], "boolean"),
		valTpDato($frmDcto['lstActivo'], "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	errorGuardarDcto($objResponse); 
	$objResponse->alert("Devolución de registro de compra guardado con éxito.");
	
	$objResponse->script("verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=".$idNotaCredito."', 960, 550);");
	
	if ($idRetencionCabezera > 0) {
		$objResponse->script("verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=".$idRetencionCabezera."', 900, 700);");
	}
	
	$objResponse->script("
	byId('btnCancelarDcto').click();");

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
	
	$objResponse->loadCommands(listaRegistroCompra(
		$frmListaFacturaCompra['pageNum'],
		$frmListaFacturaCompra['campOrd'],
		$frmListaFacturaCompra['tpOrd'],
		$frmListaFacturaCompra['valBusq']));
	
	return $objResponse;
}

function listaRegistroCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_modulo IN (2)
		OR cxp_fact.id_modulo IS NULL)
	AND activa IS NOT NULL
	AND cxp_fact.id_factura NOT IN (SELECT cxp_nc.id_documento FROM cp_notacredito cxp_nc
									WHERE cxp_nc.tipo_documento LIKE 'FA')");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxp_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			reg_comp_uni_fis.fechaActualizado
		ELSE
			cxp_fact.fecha_origen
		END) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT kardex.claveKardex FROM an_kardex kardex
			INNER JOIN vw_pg_clave_movimiento ON (kardex.claveKardex = vw_pg_clave_movimiento.id_clave_movimiento)
		WHERE kardex.tipoMovimiento IN (1)
			AND kardex.id_documento = cxp_fact.id_factura
		LIMIT 1) = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_fact.id_modo_compra = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		((CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			CONCAT_WS('*','',(SELECT reg_comp_uni_fis.numeroFactura
			FROM an_registro_compras_unidades_fisicas reg_comp_uni_fis
			WHERE reg_comp_uni_fis.idUnidadFisica = uni_fis.id_unidad_fisica
			LIMIT 1))
		ELSE
			cxp_fact.numero_factura_proveedor
		END) LIKE %s
		OR (CASE WHEN(cxp_fact.id_factura IS NULL) THEN
				reg_comp_uni_fis.referenciaPedido
			ELSE
				cxp_fact.id_pedido_compra
			END) LIKE %s
		OR (SELECT prov.nombre FROM cp_proveedor prov
			WHERE prov.id_proveedor = cxp_fact.id_proveedor
				OR prov.id_proveedor = reg_comp_uni_fis.proveedor
			LIMIT 1) LIKE %s
		OR serial_carroceria LIKE %s
		OR placa LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cxp_fact.id_factura,
		cxp_fact.id_modo_compra,
		
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			CONCAT_WS('*','',(SELECT reg_comp_uni_fis.numeroFactura
			FROM an_registro_compras_unidades_fisicas reg_comp_uni_fis
			WHERE reg_comp_uni_fis.idUnidadFisica = uni_fis.id_unidad_fisica
			LIMIT 1))
		ELSE
			cxp_fact.numero_factura_proveedor
		END) AS numero_factura_proveedor,
		
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.numeroControl, cxp_fact.numero_control_factura) AS numero_control_factura,
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.referenciaPedido, ped_comp.idPedidoCompra) AS id_pedido_compra,
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaCompra, cxp_fact.fecha_factura_proveedor) AS fecha_factura_proveedor,
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaActualizado, cxp_fact.fecha_origen) AS fecha_origen,
		IF(cxp_fact.id_factura IS NULL, reg_comp_uni_fis.fechaVencimiento, cxp_fact.fecha_vencimiento) AS fecha_vencimiento,
		
		(SELECT CONCAT_WS('-', prov.lrif, prov.rif) FROM cp_proveedor prov
		WHERE prov.id_proveedor IN (cxp_fact.id_proveedor, reg_comp_uni_fis.proveedor)
		LIMIT 1) AS rif_proveedor,
		
		(SELECT prov.nombre FROM cp_proveedor prov
		WHERE prov.id_proveedor IN (cxp_fact.id_proveedor, reg_comp_uni_fis.proveedor)
		LIMIT 1) AS nombre_proveedor,
		
		cxp_fact.id_modulo,
		origen.nom_origen,
		uni_fis.placa,
		uni_fis.serial_carroceria,
		
		(CASE id_modulo
			WHEN 1 THEN
				(SELECT COUNT(orden_tot.id_factura)
				FROM sa_orden_tot orden_tot
					INNER JOIN sa_orden_tot_detalle orden_tot_det ON (orden_tot.id_orden_tot = orden_tot_det.id_orden_tot)
				WHERE orden_tot.id_factura = cxp_fact.id_factura)
			WHEN 2 THEN
				(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
				+
				(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura)
			ELSE
				(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
				WHERE cxp_fact_det.id_factura = cxp_fact.id_factura)
		END) AS cant_items,
		
		moneda_local.abreviacion AS abreviacion_moneda_local,
		
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			(reg_comp_uni_fis.importeVehiculo + reg_comp_uni_fis.totalPaquete)
		ELSE
			cxp_fact.subtotal_factura
		END) AS subtotal_factura,
		
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			reg_comp_uni_fis.descuentoVehiculo
		ELSE
			cxp_fact.subtotal_descuento
		END) AS subtotal_descuento,
		
		reg_comp_uni_fis.porcentajeIvaVehiculo AS porcentaje_iva,
		reg_comp_uni_fis.ivaVehiculo AS subtotal_iva,
		reg_comp_uni_fis.porcentajeImpuestoLujoVehiculo AS porcentaje_iva_lujo,
		reg_comp_uni_fis.impuestoLujoVehiculo AS subtotal_iva_lujo,
		reg_comp_uni_fis.montoExento AS monto_exento,
		reg_comp_uni_fis.montoExonerado AS monto_exonerado,
		uni_fis.id_unidad_fisica,
		uni_fis.placa,
		uni_fis.serial_carroceria,
		ped_comp_det.flotilla,
		
		(CASE WHEN(cxp_fact.id_factura IS NULL) THEN
			reg_comp_uni_fis.montoTotal
		ELSE
			(IFNULL(cxp_fact.subtotal_factura, 0)
				- IFNULL(cxp_fact.subtotal_descuento, 0)
				+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
						FROM cp_factura_gasto cxp_fact_gasto
						WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
							AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
				+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
						FROM cp_factura_iva cxp_fact_iva
						WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0))
		END) AS total,
		
		cxp_fact.activa,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact.id_factura
			AND cxp_fact.id_modulo IN (2)
		LIMIT 1) AS idRetencionCabezera,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
		LEFT JOIN an_registro_compras_unidades_fisicas reg_comp_uni_fis ON (uni_fis.id_unidad_fisica = reg_comp_uni_fis.idUnidadFisica)
		LEFT JOIN an_origen origen ON (uni_fis.id_origen = origen.id_origen)
		INNER JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		INNER JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"4\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "id_pedido_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "28%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "12%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		switch ($row['activa']) {
			case "" : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Compra Registrada (Con Devolución)\"/>"; break;
			case 1 : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
			default : $imgEstatusRegistroCompra = "";
		}
		
		switch ($row['flotilla']) {
			case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
			case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
			default : $imgEstatusUnidadAsignacion = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusRegistroCompra."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_compra']."</td>";
			$htmlTb .= "<td>".$row['nombre_proveedor']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['serial_carroceria']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$row['placa']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda_local']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDevolver%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblNotaCredito', '%s', 1);\"><img class=\"puntero\" src=\"../img/iconos/arrow_rotate_clockwise.png\" title=\"Devolver Compra\"/></a>",
					$contFila,
					$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/an_registro_compra_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro Compra PDF\"/></a>",
					$row['id_factura']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[13] += $row['total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"5\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[13] += $row['total'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"5\"></td>";
			$htmlTb .= "</tr>";
		}
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
	
	$htmlTblFin .= "</table>";
	
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
		
	$objResponse->assign("divListaFacturaCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"formNotaCredito");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"listaRegistroCompra");

function errorGuardarDcto($objResponse) {
	$objResponse->script("
	byId('btnGuardarDcto').disabled = false;
	byId('btnCancelarDcto').disabled = false;");
}
?>