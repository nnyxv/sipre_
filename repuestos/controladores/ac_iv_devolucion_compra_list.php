<?php
//reconversión monetaria///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function reconversion($numFactura,$idprov,$freg){
	$objResponse = new xajaxResponse();
	$id_usuario = $_SESSION['idUsuarioSysGts'];
	$numFactura2 = $numFactura;
	
	$freg = explode("-",$freg);
	$dia = $freg[0];
	$mes = $freg[1];
	$anio = $freg[2];
	
	$freg = $anio."-".$mes."-".$dia;
	
	//con $numFactura se busca el Id de la factura a convertir para su posterior devolución///////////////////////
	$queryValID = "SELECT id_factura FROM cp_factura  WHERE numero_factura_proveedor = '$numFactura2' and id_proveedor = $idprov and fecha_origen = '$freg'";
	
	$objResponse->alert($queryValID);
	
	$rsValID = mysql_query($queryValID);
	if (!$rsValID) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowID = mysql_fetch_assoc($rsValID);
	$idFactura2 = $rowID['id_factura'];
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////

	//Verifico si la nota de carggo ya tiene reconversion
	$queryValidacion = "SELECT * FROM cp_reconversion WHERE id_factura = $idFactura2";
	$objResponse->alert($queryValidacion);
	$rsValidacion = mysql_query($queryValidacion);
	$numReg = mysql_num_rows($rsValidacion);


	$queryValidacion2 = "SELECT * FROM cp_factura  WHERE id_factura = $idFactura2 ";
	$rsValidacion2 = mysql_query($queryValidacion2);
	$numReg2 = mysql_fetch_array($rsValidacion2);

	$fechaRegistro = $numReg2['fecha_origen'];
	//if ($rsValidacion2) return $objResponse->alert($fechaRegistro);
	$dateTime_fechaReconversion = '2018-08-20';

	if($fechaRegistro < $dateTime_fechaReconversion){
		if($numReg == 0){	
			//TABLA1
			$queryFactura1 = "UPDATE cp_factura 
								SET monto_exento = monto_exento/100000,
								monto_exonerado = monto_exonerado/100000,
								subtotal_factura = subtotal_factura/100000,
								subtotal_descuento = subtotal_descuento/100000,
								total_cuenta_pagar = total_cuenta_pagar/100000,
								saldo_factura = saldo_factura/100000
								WHERE id_factura = $idFactura2 ";
			$rsNota1 = mysql_query($queryFactura1);
			if (!$rsNota1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura1);
	
			//TABLA2
			$queryFactura2 = "UPDATE cp_factura_iva 
								SET subtotal_iva = subtotal_iva/100000,
								base_imponible = base_imponible/100000
								WHERE id_factura = $idFactura2 ";
			$rsNota2 = mysql_query($queryFactura2);
			if (!$rsNota2) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura2);
	
			//TABLA3
			$queryFactura3 = "UPDATE cp_factura_gasto 
								SET monto = monto/1000
								WHERE id_factura = $idFactura2 ";
			$rsNota3 = mysql_query($queryFactura3);
			if (!$rsNota3) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura3);
	
			//TABLA4
			$queryFactura4 = "UPDATE cp_factura_detalle 
								SET precio_unitario = precio_unitario/100000
								WHERE id_factura = $idFactura2 ";
			$rsNota4 = mysql_query($queryFactura4);
			if (!$rsNota4) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura4);
	
			//TABLA5
			$queryFactura5 = "UPDATE cp_pagos_documentos 
								SET monto_cancelado = monto_cancelado/100000
								WHERE id_documento_pago = $idFactura2 ";
			$rsNota5 = mysql_query($queryFactura5);
			if (!$rsNota5) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactura5);
	
			//TABLA6
				$queryReconversion = "INSERT INTO cp_reconversion (id_factura,id_usuarios) VALUES ($idFactura2,$id_usuario)";
			$rsReconversion = mysql_query($queryReconversion);
			if (!$rsReconversion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryReconversion);
		
	
			$mensaje = "Items Actualizados";
			$objResponse->alert("$mensaje");
			//$objResponse->script("location.reload()");
			//return $objResponse;						
				
		}else{
			return $objResponse->alert("Los items de esta factura ya fueron convertidos a Bolivares Soberanos, no puede repetir el proceso");
			
		}
	}else{
		return $objResponse->alert("No está permitido reconvertir una factura con fecha igual o posterior a 20-Agosto-2018");
	}
	return $objResponse;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function asignarProveedor($idProveedor, $asigDescuento = true) {
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
	$queryFactura = sprintf("SELECT fact_comp.*,
		
		(CASE mov.id_tipo_movimiento
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento,
		mov.id_clave_movimiento,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		
		(IFNULL(fact_comp.subtotal_factura, 0)
			- IFNULL(fact_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
						AND fact_compra_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva fact_compra_iva
					WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)) AS total
	FROM iv_movimiento mov
		INNER JOIN cp_factura fact_comp ON (mov.id_documento = fact_comp.id_factura)
		INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento)
	WHERE id_factura = %s
		AND mov.id_tipo_movimiento IN (1);",
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
		fact_comp_det.cantidad,
		fact_comp_det.devuelto,
		art.id_arancel_familia,
		art.codigo_articulo,
		art.descripcion,
		vw_iv_ped_comp.id_pedido_compra_referencia,
		fact_comp_det.precio_unitario,
		fact_comp_det.id_iva,
		fact_comp_det.iva
	FROM cp_factura_detalle fact_comp_det
		INNER JOIN iv_articulos art ON (fact_comp_det.id_articulo = art.id_articulo)
		INNER JOIN vw_iv_pedidos_compra vw_iv_ped_comp ON (fact_comp_det.id_pedido_compra = vw_iv_ped_comp.id_pedido_compra)
	WHERE fact_comp_det.id_factura = %s
		AND (fact_comp_det.cantidad - fact_comp_det.devuelto) > 0;",
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
	$queryFactura = sprintf("SELECT * FROM cp_factura fact_comp
	WHERE fact_comp.id_factura = %s;",
		valTpDato($idFacturaCompra, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsFactura = mysql_num_rows($rsFactura);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$idEmpresa = $rowFactura['id_empresa'];
	$idModoCompra = $rowFactura['id_modo_compra'];
	$idModulo = $rowFactura['id_modulo']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idClaveMovimiento = $frmDcto['lstClaveMovimientoNotaCredito'];
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		errorInsertarArticulo($objResponse); return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	if ($frmDcto['lstActivo'] == "" && str_replace(",", "", $frmDcto['txtTotalFacturaCompra']) != str_replace(",", "", $frmDcto['txtTotalOrden'])) {		
		return $objResponse->alert("Para anular el registro de compra, la devolucion debe tener el mismo monto");
	}
	
	if (str_replace(",", "", $frmDcto['txtTotalOrden']) > str_replace(",", "", $frmDcto['txtTotalFacturaCompra'])) {		
		return $objResponse->alert("La devolución no puede tener un monto mayor al del registro de compra");
	}
	
	if ($frmDcto['lstAplicaLibro'] == 0) {
		// NUMERACION DEL DOCUMENTO
		if ($frmDcto['lstActivo'] == 0 && $frmDcto['lstActivo'] != ""){
		} else {
			$idNumeraciones = 9; // 9 = Nota Crédito CxP
		}
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE (emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
										WHERE clave_mov.id_clave_movimiento = %s)
				OR emp_num.id_numeracion = %s)
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato($idClaveMovimiento, "int"),
			valTpDato($idNumeraciones, "int"),
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
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayIdDctoContabilidad[] = array(
		$idNotaCredito,
		$idModulo,
		"NOTA CREDITO CXP");
	
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	SELECT
		%s,
		%s,
		%s,
		%s,
		%s,
		id_cliente_proveedor,
		tipo_costo,
		NOW(),
		%s,
		credito
	FROM iv_movimiento
	WHERE id_documento = %s
		AND id_tipo_movimiento = 1;",
		valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaRegistroNotaCredito'])), "date"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimiento = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
	$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, costo_diferencia, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
	SELECT 
		kardex.id_modulo,
		%s,
		kardex.id_articulo,
		kardex.id_casilla,
		kardex.id_articulo_almacen_costo,
		kardex.id_articulo_costo,
		%s,
		%s,
		%s,
		kardex.cantidad,
		kardex.precio,
		kardex.costo,
		kardex.costo_cargo,
		kardex.costo_diferencia,
		kardex.porcentaje_descuento,
		kardex.subtotal_descuento,
		%s,
		NOW(),
		%s,
		SYSDATE()
	FROM iv_movimiento_detalle mov_det
		INNER JOIN iv_kardex kardex ON (mov_det.id_kardex = kardex.id_kardex)
		INNER JOIN iv_movimiento mov ON (mov_det.id_movimiento = mov.id_movimiento)
	WHERE mov.id_documento = %s
		AND mov.id_tipo_movimiento IN (1);",
		valTpDato($idNotaCredito, "int"),
		valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
		valTpDato($frmDcto['txtObservacionNotaCredito'], "text"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DEL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, costo_diferencia, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
	SELECT 
		%s,
		mov_det.id_articulo,
		(SELECT id_kardex FROM iv_kardex kardex
		WHERE kardex.id_documento = %s
			AND kardex.id_articulo = mov_det.id_articulo
			AND kardex.id_articulo_almacen_costo = mov_det.id_articulo_almacen_costo
			AND kardex.id_articulo_costo = mov_det.id_articulo_costo
			AND kardex.cantidad = mov_det.cantidad
			AND kardex.precio = mov_det.precio
			AND kardex.costo = mov_det.costo
			AND kardex.costo_cargo = mov_det.costo_cargo
			AND kardex.costo_diferencia = mov_det.costo_diferencia
			AND kardex.porcentaje_descuento = mov_det.porcentaje_descuento
			AND kardex.subtotal_descuento = mov_det.subtotal_descuento),
		mov_det.id_articulo_almacen_costo,
		mov_det.id_articulo_costo,
		mov_det.cantidad,
		mov_det.precio,
		mov_det.costo,
		mov_det.costo_cargo,
		mov_det.costo_diferencia,
		mov_det.porcentaje_descuento,
		mov_det.subtotal_descuento,
		mov_det.tipo_costo,
		mov_det.promocion,
		mov_det.id_moneda_costo,
		mov_det.id_moneda_costo_cambio
	FROM iv_movimiento mov
		INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
	WHERE mov.id_documento = %s
		AND mov.id_tipo_movimiento = 1;",
		valTpDato($idMovimiento, "int"),
		valTpDato($idNotaCredito, "int"),
		valTpDato($idFacturaCompra, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// BUSCA LOS KARDEX DEL DOCUMENTO
	$query = sprintf("SELECT * FROM iv_kardex
	WHERE id_documento = %s
		AND tipo_movimiento IN (%s)
		AND id_clave_movimiento = %s
		AND tipo_documento_movimiento = %s;",
		valTpDato($idNotaCredito, "int"),
		valTpDato($frmDcto['lstTipoClave'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(2, "int")); // 1 = Vale Entrada / Salida, 2 = Nota Credito
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($row = mysql_fetch_assoc($rs)) {
		$idArticulo = $row['id_articulo'];
		$idCasilla = $row['id_casilla'];
		$hddIdArticuloAlmacenCosto = $row['id_articulo_almacen_costo'];
		$hddIdArticuloCosto = $row['id_articulo_costo'];
		$cantPedida = $row['cantidad'];
		$costoUnitario = $row['costo'];
		$precioUnitario = $row['precio'];
		$costoCargo = $row['costo_cargo'];
		$costoDiferencia = $row['costo_diferencia'];
		$porcentajeDescuento = $row['porcentaje_descuento'];
		$subtotalDescuento = $row['subtotal_descuento'];
		
		if (in_array($ResultConfig12, array(1,2))) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
			$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
			WHERE vw_iv_art_almacen_costo.id_articulo = %s
				AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
				AND vw_iv_art_almacen_costo.casilla_predeterminada = 1
			ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC, orden_prioridad_venta ASC LIMIT 1;",
				valTpDato($idArticulo, "int"));
		} else {
			$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
			WHERE vw_iv_art_almacen_costo.id_articulo = %s
				AND ((vw_iv_art_almacen_costo.id_articulo_costo = %s AND %s IS NOT NULL
					OR vw_iv_art_almacen_costo.id_articulo_costo IS NOT NULL AND %s IS NULL))
				AND (vw_iv_art_almacen_costo.casilla_predeterminada = 1
					OR (vw_iv_art_almacen_costo.casilla_predeterminada IS NULL AND vw_iv_art_almacen_costo.id_casilla = %s))
			ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC, orden_prioridad_venta ASC LIMIT 1;",
				valTpDato($idArticulo, "int"),
				valTpDato($hddIdArticuloCosto, "int"), valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($idCasilla, "int"));
		}
		$rsArtEmp = mysql_query($queryArtEmp);
		if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
		$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
		
		$idCasilla = $rowArtEmp['id_casilla'];
		$hddIdArticuloAlmacenCosto = $rowArtEmp['id_articulo_almacen_costo'];
		$hddIdArticuloCosto = $rowArtEmp['id_articulo_costo'];
		
		if (doubleval($rowArtEmp['cantidad_disponible_logica'] - $cantPedida) >= 0) {
			// BUSCA EL COSTO DEL LOTE
			$queryArtCosto = sprintf("SELECT art_costo.*,
				moneda.abreviacion
			FROM iv_articulos_costos art_costo
				INNER JOIN pg_monedas moneda ON (art_costo.id_moneda = moneda.idmoneda)
			WHERE art_costo.id_articulo = %s
				AND art_costo.id_empresa = %s
				AND art_costo.id_articulo_costo = %s
			ORDER BY art_costo.fecha_registro DESC;",
				valTpDato($idArticulo, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($hddIdArticuloCosto, "int"));
			$rsArtCosto = mysql_query($queryArtCosto);
			if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
			
			$costoUnitarioKardex = (in_array($ResultConfig12, array(1,3))) ? round($costoUnitario,3) : round($rowArtCosto['costo_promedio'],3);
			$precioUnitario = $costoUnitarioKardex;
			$costoCargo = (in_array($ResultConfig12, array(1,3))) ? $costoCargo : 0;
			$costoDiferencia = (in_array($ResultConfig12, array(1,3))) ? $costoDiferencia : 0;
			$porcentajeDescuento = (in_array($ResultConfig12, array(1,3))) ? $porcentajeDescuento : 0;
			$subtotalDescuento = (in_array($ResultConfig12, array(1,3))) ? $subtotalDescuento : 0;
			
			// MODIFICA EL MOVIMIENTO DEL ARTICULO
			$updateSQL = sprintf("UPDATE iv_kardex SET
				id_casilla = %s,
				id_articulo_almacen_costo = %s,
				id_articulo_costo = %s,
				precio = %s,
				costo = %s,
				costo_cargo = %s,
				costo_diferencia = %s,
				porcentaje_descuento = %s,
				subtotal_descuento = %s
			WHERE id_kardex = %s;",
				valTpDato($idCasilla, "int"),
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitarioKardex, "real_inglesa"),
				valTpDato($costoCargo, "real_inglesa"),
				valTpDato($costoDiferencia, "real_inglesa"),
				valTpDato($porcentajeDescuento, "real_inglesa"),
				valTpDato($subtotalDescuento, "real_inglesa"),
				valTpDato($row['id_kardex'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// MODIFICA EL DETALLE DEL MOVIMIENTO
			$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
				id_kardex = %s,
				id_articulo_almacen_costo = %s,
				id_articulo_costo = %s
			WHERE id_movimiento = %s
				AND id_articulo = %s
				AND cantidad = %s
				AND precio = %s
				AND costo = %s
				AND costo_cargo = %s
				AND costo_diferencia = %s
				AND porcentaje_descuento = %s
				AND subtotal_descuento = %s
			LIMIT 1;",
				valTpDato($row['id_kardex'], "int"),
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($idMovimiento, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($cantPedida, "real_inglesa"),
				valTpDato($row['precio'], "real_inglesa"),
				valTpDato($row['costo'], "real_inglesa"),
				valTpDato($row['costo_cargo'], "real_inglesa"),
				valTpDato($row['costo_diferencia'], "real_inglesa"),
				valTpDato($row['porcentaje_descuento'], "real_inglesa"),
				valTpDato($row['subtotal_descuento'], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
				
			// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
			$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
			$Result1 = actualizarSaldos($idArticulo, $idCasilla);
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
		} else {
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
			
			// BUSCA LOS DATOS DE LA UBICACION
			$queryUbic = sprintf("SELECT * FROM vw_iv_casillas WHERE id_casilla = %s;",
				valTpDato($idCasilla, "int"));
			$rsUbic = mysql_query($queryUbic);
			if (!$rsUbic) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsUbic = mysql_num_rows($rsUbic);
			$rowUbic = mysql_fetch_assoc($rsUbic);
			
			$ubicacion = $rowUbic['descripcion_almacen']." ".$rowUbic['ubicacion'];
			
			$contSinDisponibilidad++;
			
			$msjArticulo .= ($msjArticulo != "") ? "": "El(Los) registro(s):\n";
			$msjArticulo .= ($contSinDisponibilidad % 2 == 1) ? "\n" : "";
			
			$msjArticuloInd = "(";
				$msjArticuloInd .= elimCaracter($rowArticulo['codigo_articulo'],";");
				$msjArticuloInd .= utf8_decode(", Ubicación: ").preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion))));
				$msjArticuloInd .= (in_array($ResultConfig12, array(1,2))) ? "" : ", LOTE: ".$hddIdArticuloCosto;
			$msjArticuloInd .= ")";
			
			$msjArticulo .= str_pad($msjArticuloInd, 90, " ", STR_PAD_RIGHT);
		}
	}
	
	if ($contSinDisponibilidad > 0) {
		$msjArticulo .= "\n\nno posee(n) disponible la cantidad suficiente";
		
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
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	if (!$rsRetencionDetalle) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsRetencionDetalle = mysql_num_rows($rsRetencionDetalle);
	$rowRetencionDetalle = mysql_fetch_assoc($rsRetencionDetalle);
	
	// VERIFICA QUE LA DEVOLUCION PERTENEZCA AL MISMO PERIODO FISCAL DE LA FACTURA DE COMPRA
	if ($totalRowsRetencionDetalle > 0
	&& ((date("d", strtotime($rowRetencionDetalle['fechaComprobante'])) <= 15 && date("d",strtotime($frmDcto['txtFechaRegistroNotaCredito'])) <= 15)
		|| (date("d", strtotime($rowRetencionDetalle['fechaComprobante'])) > 15 && date("d",strtotime($frmDcto['txtFechaRegistroNotaCredito'])) > 15))
	&& date("m-Y", strtotime($rowRetencionDetalle['fechaComprobante'])) == date("m-Y",strtotime($frmDcto['txtFechaRegistroNotaCredito']))) {
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
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	switch ($idModoCompra) {
		case 1 : // 1 = Nacional
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
			$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
				nota_cred.saldo_notacredito = (IFNULL(nota_cred.subtotal_notacredito, 0)
									- IFNULL(nota_cred.subtotal_descuento, 0)
									+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
											FROM cp_notacredito_gastos nota_cred_gasto
											WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito
												AND nota_cred_gasto.id_modo_gasto IN (1,3)
												AND nota_cred_gasto.afecta_documento IN (1)), 0)
									+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
											FROM cp_notacredito_iva nota_cred_iva
											WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
									- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
													OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
												AND pago_dcto.estatus = 1), 0))
			WHERE nota_cred.id_notacredito = %s
				AND nota_cred.estado_notacredito IN (1,2);",
				valTpDato($idNotaCredito, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			break;
		case 2 : // 2 = Importacion
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
			$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
				nota_cred.saldo_notacredito = (IFNULL((SELECT 
													SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
												FROM cp_factura_detalle a
													INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
													INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
												WHERE a.id_factura = %s), 0)
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_modo_gasto IN (1)
													AND fact_comp_gasto.afecta_documento IN (1)
													AND fact_comp_gasto.id_factura = %s), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
														OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
													AND pago_dcto.estatus = 1), 0))
			WHERE nota_cred.estado_notacredito IN (1,2)
				AND nota_cred.id_notacredito = %s
				AND (SELECT COUNT(fact_comp_det.id_factura)
					FROM cp_factura_detalle fact_comp_det
					WHERE fact_comp_det.id_factura = %s) > 0;",
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idNotaCredito, "int"),
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			break;
	}
	
	// BUSCA LOS DATOS DE LA NOTA DE CREDITO
	$queryNotaCredito = sprintf("SELECT * FROM cp_notacredito nota_cred
	WHERE nota_cred.id_notacredito = %s;",
		valTpDato($idNotaCredito, "int"));
	$rsNotaCredito = mysql_query($queryNotaCredito);
	if (!$rsNotaCredito) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	switch ($idModoCompra) {
		case 1 : // 1 = Nacional
			// ACTUALIZA EL SALDO DE LA FACTURA
			$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
				fact_comp.saldo_factura = (IFNULL(fact_comp.subtotal_factura, 0)
									- IFNULL(fact_comp.subtotal_descuento, 0)
									+ IFNULL((SELECT SUM(fact_comp_gasto.monto) AS total_gasto
											FROM cp_factura_gasto fact_comp_gasto
											WHERE fact_comp_gasto.id_factura = fact_comp.id_factura
												AND fact_comp_gasto.id_modo_gasto IN (1,3)
												AND fact_comp_gasto.afecta_documento IN (1)), 0)
									+ IFNULL((SELECT SUM(fact_comp_iva.subtotal_iva) AS total_iva
											FROM cp_factura_iva fact_comp_iva
											WHERE fact_comp_iva.id_factura = fact_comp.id_factura), 0)
									- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE pago_dcto.id_documento_pago = fact_comp.id_factura
												AND pago_dcto.tipo_documento_pago LIKE 'FA'
												AND pago_dcto.estatus = 1), 0))
			WHERE fact_comp.id_factura = %s
				AND fact_comp.estatus_factura NOT IN (1);",
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					
			// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
				fact_comp.estatus_factura = (CASE
									WHEN (ROUND(saldo_factura, 2) = 0 OR ROUND(saldo_factura, 2) < 0) THEN
										1
									WHEN (ROUND(saldo_factura, 2) > 0 
										AND ROUND(saldo_factura, 2) < (IFNULL(fact_comp.subtotal_factura, 0)
										- IFNULL(fact_comp.subtotal_descuento, 0)
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto) AS total_gasto
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_factura = fact_comp.id_factura
													AND fact_comp_gasto.id_modo_gasto IN (1,3)
													AND fact_comp_gasto.afecta_documento IN (1)), 0)
										+ IFNULL((SELECT SUM(fact_comp_iva.subtotal_iva) AS total_iva
												FROM cp_factura_iva fact_comp_iva
												WHERE fact_comp_iva.id_factura = fact_comp.id_factura), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE pago_dcto.id_documento_pago = fact_comp.id_factura
													AND pago_dcto.tipo_documento_pago LIKE 'FA'), 0))) THEN
										2
									ELSE
										0
								END)
			WHERE fact_comp.id_factura = %s;",
				valTpDato($idFacturaCompra, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO
			$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
				nota_cred.saldo_notacredito = (IFNULL(nota_cred.subtotal_notacredito, 0)
									- IFNULL(nota_cred.subtotal_descuento, 0)
									+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
											FROM cp_notacredito_gastos nota_cred_gasto
											WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito
												AND nota_cred_gasto.id_modo_gasto IN (1,3)
												AND nota_cred_gasto.afecta_documento IN (1)), 0)
									+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
											FROM cp_notacredito_iva nota_cred_iva
											WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
									- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
											WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
													OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
												AND pago_dcto.estatus = 1), 0))
			WHERE nota_cred.id_notacredito = %s
				AND nota_cred.estado_notacredito IN (1,2);",
				valTpDato($idNotaCredito, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
			// ACTUALIZA EL ESTADO DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
			$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
				nota_cred.estado_notacredito = (CASE
										WHEN (saldo_notacredito = 0) THEN
											3
										WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL(nota_cred.subtotal_notacredito, 0)
											- IFNULL(nota_cred.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
													FROM cp_notacredito_gastos nota_cred_gasto
													WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito
														AND nota_cred_gasto.id_modo_gasto IN (1,3)
														AND nota_cred_gasto.afecta_documento IN (1)), 0)
											+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
													FROM cp_notacredito_iva nota_cred_iva
													WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
														AND pago_dcto.estatus = 1), 0))) THEN
											2
										WHEN (saldo_notacredito = (IFNULL(nota_cred.subtotal_notacredito, 0)
											- IFNULL(nota_cred.subtotal_descuento, 0)
											+ IFNULL((SELECT SUM(nota_cred_gasto.monto_gasto_notacredito) AS total_gasto
													FROM cp_notacredito_gastos nota_cred_gasto
													WHERE nota_cred_gasto.id_notacredito = nota_cred.id_notacredito
														AND nota_cred_gasto.id_modo_gasto IN (1,3)
														AND nota_cred_gasto.afecta_documento IN (1)), 0)
											+ IFNULL((SELECT SUM(nota_cred_iva.subtotal_iva_notacredito) AS total_iva
													FROM cp_notacredito_iva nota_cred_iva
													WHERE nota_cred_iva.id_notacredito = nota_cred.id_notacredito), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
														AND pago_dcto.estatus = 1), 0))) THEN
											1
										ELSE
											0
									END)
			WHERE nota_cred.id_notacredito = %s;",
				valTpDato($idNotaCredito, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			break;
		case 2 : // 2 = Importacion
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
														AND pago_dcto.tipo_documento_pago LIKE 'FA'
														AND pago_dcto.estatus = 1), 0))
			WHERE fact_comp.id_modo_compra IN (2)
				AND fact_comp.estatus_factura IN (0,2)
				AND fact_comp.id_factura = %s
				AND (SELECT COUNT(fact_comp_det.id_factura)
					FROM cp_factura_detalle fact_comp_det
					WHERE fact_comp_det.id_factura = fact_comp.id_factura) > 0;",
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cp_factura fact_comp SET
				fact_comp.estatus_factura = (CASE
									WHEN (ROUND(saldo_factura, 2) = 0) THEN
										1
									WHEN (ROUND(saldo_factura, 2) > 0
										AND ROUND(saldo_factura, 2) < (IFNULL((SELECT 
													SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
												FROM cp_factura_detalle a
													INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
													INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
												WHERE a.id_factura = fact_comp.id_factura), 0)
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_modo_gasto IN (1)
													AND fact_comp_gasto.afecta_documento IN (1)
													AND fact_comp_gasto.id_factura = fact_comp.id_factura), 0))) THEN
										2
									ELSE
										0
								END)
			WHERE fact_comp.id_factura = %s;",
				valTpDato($idFacturaCompra, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA EL SALDO DE LA NOTA DE CREDITO DE IMPORTACION (SUMA(CANTIDAD * COSTO FOB * TASA CAMBIO) + GASTOS QUE AFECTEN CUENTA POR PAGAR)
			$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
				nota_cred.saldo_notacredito = (IFNULL((SELECT 
													SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
												FROM cp_factura_detalle a
													INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
													INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
												WHERE a.id_factura = %s), 0)
										+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
												FROM cp_factura_gasto fact_comp_gasto
												WHERE fact_comp_gasto.id_modo_gasto IN (1)
													AND fact_comp_gasto.afecta_documento IN (1)
													AND fact_comp_gasto.id_factura = %s), 0)
										- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
												WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
														OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
													AND pago_dcto.estatus = 1), 0))
			WHERE nota_cred.estado_notacredito IN (1,2)
				AND nota_cred.id_notacredito = %s
				AND (SELECT COUNT(fact_comp_det.id_factura)
					FROM cp_factura_detalle fact_comp_det
					WHERE fact_comp_det.id_factura = %s) > 0;",
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idFacturaCompra, "int"),
				valTpDato($idNotaCredito, "int"),
				valTpDato($idFacturaCompra, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
			// ACTUALIZA EL ESTADO DE LA NOTA DE CREDITO (0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado)
			$updateSQL = sprintf("UPDATE cp_notacredito nota_cred SET
				nota_cred.estado_notacredito = (CASE
										WHEN (saldo_notacredito = 0) THEN
											3
										WHEN (saldo_notacredito > 0 AND saldo_notacredito < (IFNULL((SELECT 
														SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
													FROM cp_factura_detalle a
														INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
														INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
													WHERE a.id_factura = nota_cred.id_documento), 0)
											+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
													FROM cp_factura_gasto fact_comp_gasto
													WHERE fact_comp_gasto.id_modo_gasto IN (1)
														AND fact_comp_gasto.afecta_documento IN (1)
														AND fact_comp_gasto.id_factura = nota_cred.id_documento), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
														AND pago_dcto.estatus = 1), 0))) THEN
											2
										WHEN (saldo_notacredito = (IFNULL((SELECT 
														SUM(a.cantidad * b.costo_unitario * fact_comp_imp.tasa_cambio)
													FROM cp_factura_detalle a
														INNER JOIN cp_factura_detalle_importacion b ON (b.id_factura_detalle = a.id_factura_detalle)
														INNER JOIN cp_factura_importacion fact_comp_imp ON (a.id_factura = fact_comp_imp.id_factura)
													WHERE a.id_factura = nota_cred.id_documento), 0)
											+ IFNULL((SELECT SUM(fact_comp_gasto.monto)
													FROM cp_factura_gasto fact_comp_gasto
													WHERE fact_comp_gasto.id_modo_gasto IN (1)
														AND fact_comp_gasto.afecta_documento IN (1)
														AND fact_comp_gasto.id_factura = nota_cred.id_documento), 0)
											- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
													WHERE ((pago_dcto.tipo_pago LIKE 'NC' AND pago_dcto.id_documento = nota_cred.id_notacredito)
															OR (pago_dcto.tipo_documento_pago LIKE 'NC' AND pago_dcto.id_documento_pago = nota_cred.id_notacredito))
														AND pago_dcto.estatus = 1), 0))) THEN
											1
										ELSE
											0
									END)
			WHERE nota_cred.id_notacredito = %s;",
				valTpDato($idNotaCredito, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
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
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	
	$objResponse->alert("Devolución de registro de compra guardado con éxito");
	
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
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(id_modulo IN (0)
	AND activa IS NOT NULL)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_comp.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = fact_comp.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_origen BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
			INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
		WHERE mov.id_tipo_movimiento IN (1)
			AND mov.id_documento = fact_comp.id_factura
		LIMIT 1) = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_comp.id_modo_compra = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR fact_comp.numero_control_factura LIKE %s
		OR fact_comp.numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT
		fact_comp.id_factura,
		fact_comp.id_modo_compra,
		fact_comp.fecha_origen,
		fact_comp.fecha_factura_proveedor,
		fact_comp.numero_factura_proveedor,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		fact_comp.id_modulo,
		
		(SELECT SUM(fact_compra_det.cantidad)
		FROM cp_factura_detalle fact_compra_det
		WHERE (fact_compra_det.id_factura = fact_comp.id_factura)) AS cant_piezas,
		
		(SELECT COUNT(fact_compra_det.id_factura)
		FROM cp_factura_detalle fact_compra_det
		WHERE (fact_compra_det.id_factura = fact_comp.id_factura)) AS cant_items,
		
		moneda_local.abreviacion AS abreviacion_moneda_local,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = fact_comp.id_factura
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT DISTINCT ped_comp.estatus_pedido_compra
		FROM cp_factura_detalle fact_comp_det
			INNER JOIN iv_pedido_compra ped_comp ON (fact_comp_det.id_pedido_compra = ped_comp.id_pedido_compra)
		WHERE fact_comp_det.id_factura = fact_comp.id_factura
		LIMIT 1) AS estatus_pedido_compra,
		
		(IFNULL(fact_comp.subtotal_factura, 0)	
			- IFNULL(fact_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
						AND fact_compra_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
		
		(IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
				FROM cp_factura_iva fact_compra_iva
				WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)) AS total_iva,
		
		(IFNULL(fact_comp.subtotal_factura, 0)
			- IFNULL(fact_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
						AND fact_compra_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva fact_compra_iva
					WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)) AS total,
		
		fact_comp.activa,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_factura fact_comp
		INNER JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (fact_comp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= "<td width=\"16%\">"."Tipo de Pedido"."</td>";
		$htmlTh .= "<td width=\"8%\">"."Nro. Pedido"."</td>";
		$htmlTh .= "<td width=\"8%\">"."Nro. Referencia"."</td>";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "16%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "4%", $pageNum, "cant_piezas", $campOrd, $tpOrd, $valBusq, $maxRows, "Piezas");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Factura Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Factura Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Factura Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Factura Administración\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		switch ($row['activa']) {
			case "" : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Compra Registrada (Con Devolución)\"/>"; break;
			case 1 : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
			default : $imgEstatusRegistroCompra = "";
		}
		
		$queryFactDet = sprintf("SELECT id_pedido_compra FROM cp_factura_detalle
		WHERE id_factura = %s
		GROUP BY id_pedido_compra;",
			valTpDato($row['id_factura'], "int"));
		$rsFactDet = mysql_query($queryFactDet);
		if (!$rsFactDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayFactDet = NULL;
		while ($rowFactDet = mysql_fetch_assoc($rsFactDet)) {
			$queryPedComp = sprintf("SELECT * FROM vw_iv_pedidos_compra WHERE id_pedido_compra = %s;",
				valTpDato($rowFactDet['id_pedido_compra'], "int"));
			$rsPedComp = mysql_query($queryPedComp);
			if (!$rsPedComp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowPedComp = mysql_fetch_assoc($rsPedComp);
			
			$arrayFactDet[] = array(
				$rowPedComp['estatus_pedido_compra'],
				$rowPedComp['tipo_pedido_compra'],
				$rowPedComp['id_pedido_compra_propio'],
				$rowPedComp['id_pedido_compra_referencia']);
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusRegistroCompra."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td>";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						switch($arrayFactDet[$indice][0]) {
							case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Pendiente por Terminar\"/>"; break;
							case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Pedido Cerrado\"/>"; break;
							case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Orden Aprobada\"/>"; break;
							case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
							case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Compra Registrada (Con Devolución)\"/>"; break;
							case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Pedido Anulado\"/>"; break;
							default : $imgEstatusPedido = "";
						}
						
						$htmlTb .= "<tr align=\"left\">";
							$htmlTb .= "<td>".$imgEstatusPedido."</td>";
							$htmlTb .= "<td>".utf8_encode($arrayFactDet[$indice][1])."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>".$arrayFactDet[$indice][2]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>".$arrayFactDet[$indice][3]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_piezas'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda_local']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDevolver%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblNotaCredito', '%s', 1);\"><img class=\"puntero\" src=\"../img/iconos/arrow_rotate_clockwise.png\" title=\"Devolver Compra\"/></a>",
					$contFila,
					$row['id_factura']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/iv_registro_compra_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Registro Compra PDF\"/></a>",
					$row['id_factura']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[12] += $row['cant_items'];
		$arrayTotal[13] += $row['cant_piezas'];
		$arrayTotal[14] += $row['total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"5\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[12] += $row['cant_items'];
				$arrayTotalFinal[13] += $row['cant_piezas'];
				$arrayTotalFinal[14] += $row['total'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"5\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"16\">";
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
$xajax->register(XAJAX_FUNCTION,"reconversion");
?>